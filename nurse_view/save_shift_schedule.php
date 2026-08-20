<?php
session_start();
require_once __DIR__ . '/../core/Autoloader.php';
use GM_HMS\Database\SecureDatabase;

header('Content-Type: application/json');

// Get POST data
$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true);

if (!is_array($data)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON payload.']);
    exit;
}

try {
    $db = SecureDatabase::getInstance();
    $conn = $db->getConnection();
    
    // Begin transaction for safe bulk insert
    $conn->begin_transaction();
    
    $startDate = $data['startDate'] ?? null;
    $endDate = $data['endDate'] ?? null;
    $shifts = $data['shifts'] ?? [];

    if (!$startDate || !$endDate) {
        echo json_encode(['status' => 'error', 'message' => 'Missing From Date or To Date in payload.']);
        if(isset($conn)) $conn->rollback();
        exit;
    }

    if ($startDate > $endDate) {
        echo json_encode(['status' => 'error', 'message' => "Invalid Date Range: 'From Date' ($startDate) cannot be later than 'To Date' ($endDate). Please select a valid date range."]);
        if(isset($conn)) $conn->rollback();
        exit;
    }

    // Check internal duplicates in payload
    $occupiedSlots = [];
    $nurseDutySlots = [];

    foreach ($shifts as $s) {
        $nId = $s['nurse_id'] ?? '';
        $nName = $s['nurse_name'] ?? 'Nurse';
        $sDate = $s['shift_date'] ?? '';
        $sType = $s['shift_type'] ?? '';
        $fName = $s['floor_name'] ?? '';
        $wName = $s['ward_name'] ?? '';
        $rType = $s['room_type'] ?? '';

        if ($sType === 'Week Off') {
            continue;
        }

        // Nurse Duplicate Check (same nurse on same date & shift in multiple rooms)
        $nurseSlotKey = $nId . '|' . $sDate . '|' . $sType;
        if (isset($nurseDutySlots[$nurseSlotKey]) && $nurseDutySlots[$nurseSlotKey]['ward_name'] !== $wName) {
            $prevLoc = $nurseDutySlots[$nurseSlotKey]['ward_name'] ?: 'another ward';
            echo json_encode([
                'status' => 'error',
                'message' => "Nurse {$nName} is already assigned to {$prevLoc} for the {$sType} shift on {$sDate}. A nurse cannot be in two places at the same time."
            ]);
            if(isset($conn)) $conn->rollback();
            exit;
        }
        $nurseDutySlots[$nurseSlotKey] = ['ward_name' => $wName, 'floor_name' => $fName];
    }

    // Check overlapping external schedules in database for the same nurse
    $stmtCheckOverlap = $conn->prepare("
        SELECT floor_name, ward_name, room_type, start_date, end_date, shift_data
        FROM shift_schedules
        WHERE (start_date <= ? AND end_date >= ?)
          AND NOT (start_date = ? AND end_date = ?)
    ");
    $stmtCheckOverlap->bind_param("ssss", $endDate, $startDate, $startDate, $endDate);
    $stmtCheckOverlap->execute();
    $overlapRes = $stmtCheckOverlap->get_result();

    while ($overlapRow = $overlapRes->fetch_assoc()) {
        $overlapJson = json_decode($overlapRow['shift_data'], true);
        if (is_array($overlapJson)) {
            foreach ($overlapJson as $extShift) {
                if ($extShift['shift_type'] === 'Week Off') continue;
                $extDate = $extShift['shift_date'];
                
                // Only check dates that fall within the current selected range
                if ($extDate >= $startDate && $extDate <= $endDate) {
                    $extFloor = $overlapRow['floor_name'];
                    $extWard = $overlapRow['ward_name'];
                    $extType = $extShift['shift_type'];
                    $extNurseId = $extShift['nurse_id'];
                    $extNurseName = $extShift['nurse_name'];

                    $nurseKey = $extNurseId . '|' . $extDate . '|' . $extType;
                    if (isset($nurseDutySlots[$nurseKey]) && $nurseDutySlots[$nurseKey]['ward_name'] !== $extWard) {
                        echo json_encode([
                            'status' => 'error',
                            'message' => "Nurse {$extNurseName} is already assigned to {$extWard} ({$extFloor}) on {$extDate} in an overlapping schedule. Please resolve the schedule conflict."
                        ]);
                        if(isset($conn)) $conn->rollback();
                        exit;
                    }
                }
            }
        }
    }

    // Determine specific floor & room types being updated so we only delete matching records
    $floorsBeingSaved = [];
    foreach ($shifts as $row) {
        if (!empty($row['floor_name']) && !empty($row['room_type'])) {
            $floorsBeingSaved[$row['floor_name'] . '|' . $row['room_type']] = [
                'floor_name' => $row['floor_name'],
                'room_type' => $row['room_type']
            ];
        }
    }

    if (!empty($floorsBeingSaved)) {
        $stmtDelete = $conn->prepare("DELETE FROM shift_schedules WHERE start_date = ? AND end_date = ? AND floor_name = ? AND room_type = ?");
        foreach ($floorsBeingSaved as $fItem) {
            $stmtDelete->bind_param("ssss", $startDate, $endDate, $fItem['floor_name'], $fItem['room_type']);
            $stmtDelete->execute();
        }
    } else {
        $stmtDelete = $conn->prepare("DELETE FROM shift_schedules WHERE start_date = ? AND end_date = ?");
        $stmtDelete->bind_param("ss", $startDate, $endDate);
        $stmtDelete->execute();
    }

    // Prepare statement to fetch bed/room data
    $stmtFetch = $conn->prepare("
        SELECT GROUP_CONCAT(DISTINCT room_name SEPARATOR ', ') as r_name
        FROM hospital_beds
        WHERE floor_name = ? AND ward_name = ? AND room_type = ?
    ");

    $stmt = $conn->prepare("
        INSERT INTO shift_schedules 
        (floor_name, ward_name, room_type, room_name, start_date, end_date, shift_data, assigned_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    // Assuming assigned_by comes from session, defaulting to 1 for demo
    $assigned_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; 

    $wards_data = [];

    foreach ($shifts as $row) {
        $fName = $row['floor_name'] ?? 'Unassigned';
        $wName = $row['ward_name'] ?? 'Unassigned';
        $rType = $row['room_type'] ?? 'Unassigned';
        
        $groupKey = $fName . '|' . $wName . '|' . $rType;
        
        if (!isset($wards_data[$groupKey])) {
            $rName = null;
            if ($fName !== 'Unassigned' && $wName !== 'Unassigned' && $rType !== 'Unassigned') {
                $stmtFetch->bind_param("sss", $fName, $wName, $rType);
                $stmtFetch->execute();
                $res = $stmtFetch->get_result();
                if ($fetchRow = $res->fetch_assoc()) {
                    $rName = $fetchRow['r_name'];
                }
            }
            $wards_data[$groupKey] = [
                'floor_name' => $fName === 'Unassigned' ? null : $fName,
                'ward_name' => $wName === 'Unassigned' ? null : $wName,
                'room_type' => $rType === 'Unassigned' ? null : $rType,
                'room_name' => $rName,
                'shift_data' => []
            ];
        }
        
        $wards_data[$groupKey]['shift_data'][] = [
            'nurse_id' => $row['nurse_id'],
            'nurse_name' => $row['nurse_name'],
            'shift_date' => $row['shift_date'],
            'shift_type' => $row['shift_type']
        ];
    }

    foreach($wards_data as $group) {
        $fName = $group['floor_name'];
        $wName = $group['ward_name'];
        $rType = $group['room_type'];
        $rName = $group['room_name'];
        $jsonData = json_encode($group['shift_data']);
        $stmt->bind_param("sssssssi", $fName, $wName, $rType, $rName, $startDate, $endDate, $jsonData, $assigned_by);
        $stmt->execute();
    }
    
    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Schedule saved perfectly!']);

} catch (Exception $e) {
    if(isset($conn)) $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
