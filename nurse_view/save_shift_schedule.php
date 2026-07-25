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
        echo json_encode(['status' => 'error', 'message' => 'Missing date range in payload.']);
        if(isset($conn)) $conn->rollback();
        exit;
    }

    // Delete existing records for this week to properly handle removed assignments
    $stmtDelete = $conn->prepare("DELETE FROM shift_schedules WHERE start_date = ? AND end_date = ?");
    $stmtDelete->bind_param("ss", $startDate, $endDate);
    $stmtDelete->execute();

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
