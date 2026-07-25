<?php
// includes/nurse_auth_helper.php

/**
 * Gets the current ward assignment for a nurse on a specific date based on JSON shift schedules.
 * Returns an array with floor_name, ward_name, room_type or null if not assigned.
 */
function getCurrentNurseWard($conn, $nurse_id, $target_date = null) {
    if (!$target_date) {
        $target_date = date('Y-m-d');
    }
    
    $stmt = $conn->prepare("
        SELECT floor_name, ward_name, room_type, shift_data 
        FROM shift_schedules 
        WHERE ? BETWEEN start_date AND end_date
    ");
    
    if (!$stmt) return null;
    
    $stmt->bind_param("s", $target_date);
    $stmt->execute();
    $res = $stmt->get_result();
    
    while ($row = $res->fetch_assoc()) {
        $jsonData = json_decode($row['shift_data'], true);
        if (is_array($jsonData)) {
            foreach ($jsonData as $shift) {
                // Match nurse + date + any working shift (not Week Off)
                if (isset($shift['nurse_id']) && $shift['nurse_id'] == $nurse_id 
                    && $shift['shift_date'] === $target_date
                    && $shift['shift_type'] !== 'Week Off') {
                    return [
                        'floor_name'  => $row['floor_name'],
                        'ward_name'   => $row['ward_name'],
                        'room_type'   => $row['room_type'],
                        'shift_type'  => $shift['shift_type']
                    ];
                }
            }
        }
    }
    
    return null; // No active ward assigned for today
}
?>
