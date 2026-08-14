<?php
session_start();
require_once __DIR__ . '/../core/Autoloader.php';
use GM_HMS\Database\SecureDatabase;

header('Content-Type: application/json');

$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true);

if (!is_array($data)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON payload.']);
    exit;
}

try {
    $db = SecureDatabase::getInstance();
    $conn = $db->getConnection();
    
    $conn->begin_transaction();
    
    $wardId = $data['ward_id'] ?? null;
    $floorName = $data['floor_name'] ?? null;
    $wardName = $data['ward_name'] ?? null;
    $roomType = $data['room_type'] ?? null;
    $startDate = $data['start_date'] ?? null;
    $endDate = $data['end_date'] ?? null;
    $weekOff = $data['week_off'] ?? -1;
    $assignments = $data['assignments'] ?? []; // Array of {shift_type, nurse_id, nurse_name}

    if (!$startDate || !$endDate || !$floorName || empty($assignments)) {
        throw new Exception('Missing required fields for bulk assignment or no shifts selected.');
    }

    $assigned_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; 

    // Helper to get week start (Monday) and end (Sunday) for a given date
    function getWeekBoundaries($dateStr) {
        $date = new DateTime($dateStr);
        $dayOfWeek = $date->format('N'); // 1 (Mon) - 7 (Sun)
        
        $start = clone $date;
        if ($dayOfWeek != 1) {
            $start->modify('-' . ($dayOfWeek - 1) . ' days');
        }
        
        $end = clone $start;
        $end->modify('+6 days');
        
        return [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d')
        ];
    }

    // Prepare statements
    $stmtGet = $conn->prepare("SELECT sl_no, shift_data FROM shift_schedules WHERE start_date = ? AND end_date = ? AND floor_name = ? AND ward_name = ? AND room_type = ?");
    $stmtUpdate = $conn->prepare("UPDATE shift_schedules SET shift_data = ? WHERE sl_no = ?");
    
    $stmtFetchRoom = $conn->prepare("SELECT GROUP_CONCAT(DISTINCT room_name SEPARATOR ', ') as r_name FROM hospital_beds WHERE floor_name = ? AND ward_name = ? AND room_type = ?");
    $stmtInsert = $conn->prepare("INSERT INTO shift_schedules (floor_name, ward_name, room_type, room_name, start_date, end_date, shift_data, assigned_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    // Group the assignments by week boundaries
    $currentDate = new DateTime($startDate);
    $endDateObj = new DateTime($endDate);
    
    $weeksMap = [];

    while ($currentDate <= $endDateObj) {
        $dateStr = $currentDate->format('Y-m-d');
        $bounds = getWeekBoundaries($dateStr);
        $weekKey = $bounds['start'] . '|' . $bounds['end'];
        
        if (!isset($weeksMap[$weekKey])) {
            $weeksMap[$weekKey] = [
                'start' => $bounds['start'],
                'end' => $bounds['end'],
                'dates' => []
            ];
        }
        
        // Check week off
        $dow = $currentDate->format('w'); // 0 (Sun) - 6 (Sat)
        $isWo = ($dow == $weekOff);
        
        $dayShifts = [];
        if ($isWo) {
             // If week off, assign Week Off to the primary nurse? Wait, week off applies to the nurse, not the ward.
             // If we assign 3 nurses to 3 shifts, week off applies to all 3?
             // Yes, typically bulk assign gives them all the same day off in this flow, or we just assign Week Off for each nurse.
             foreach ($assignments as $asn) {
                 $dayShifts[] = [
                     'type' => 'Week Off',
                     'nurse_id' => $asn['nurse_id'],
                     'nurse_name' => $asn['nurse_name']
                 ];
             }
        } else {
             foreach ($assignments as $asn) {
                 $dayShifts[] = [
                     'type' => $asn['shift_type'],
                     'nurse_id' => $asn['nurse_id'],
                     'nurse_name' => $asn['nurse_name']
                 ];
             }
        }
        
        $weeksMap[$weekKey]['dates'][] = [
            'date' => $dateStr,
            'shifts' => $dayShifts
        ];
        
        $currentDate->modify('+1 day');
    }
    
    // Process each week
    foreach ($weeksMap as $weekKey => $weekData) {
        $wStart = $weekData['start'];
        $wEnd = $weekData['end'];
        
        // 1. Fetch existing data
        $stmtGet->bind_param("sssss", $wStart, $wEnd, $floorName, $wardName, $roomType);
        $stmtGet->execute();
        $res = $stmtGet->get_result();
        
        $existingId = null;
        $shiftDataArray = [];
        
        if ($row = $res->fetch_assoc()) {
            $existingId = $row['sl_no'];
            $shiftDataArray = json_decode($row['shift_data'], true) ?: [];
        }
        
        // 2. Modify data
        foreach ($weekData['dates'] as $dItem) {
            $dStr = $dItem['date'];
            
            foreach ($dItem['shifts'] as $shData) {
                $nId = $shData['nurse_id'];
                $nName = $shData['nurse_name'];
                $sType = $shData['type'];
                
                // Remove any existing assignment for this nurse on this date
                $shiftDataArray = array_filter($shiftDataArray, function($item) use ($nId, $dStr) {
                    return !($item['nurse_id'] == $nId && $item['shift_date'] === $dStr);
                });
                
                // Add new assignment
                $shiftDataArray[] = [
                    'nurse_id' => $nId,
                    'nurse_name' => $nName,
                    'shift_date' => $dStr,
                    'shift_type' => $sType
                ];
            }
        }
        
        // Re-index array
        $shiftDataArray = array_values($shiftDataArray);
        $newJson = json_encode($shiftDataArray);
        
        // 3. Save
        if ($existingId) {
            $stmtUpdate->bind_param("si", $newJson, $existingId);
            $stmtUpdate->execute();
        } else {
            // Need room_name
            $rName = null;
            $stmtFetchRoom->bind_param("sss", $floorName, $wardName, $roomType);
            $stmtFetchRoom->execute();
            $rRes = $stmtFetchRoom->get_result();
            if ($fetchRow = $rRes->fetch_assoc()) {
                $rName = $fetchRow['r_name'];
            }
            
            $stmtInsert->bind_param("sssssssi", $floorName, $wardName, $roomType, $rName, $wStart, $wEnd, $newJson, $assigned_by);
            $stmtInsert->execute();
        }
    }
    
    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Bulk schedule assigned perfectly!']);

} catch (Exception $e) {
    if(isset($conn)) $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
