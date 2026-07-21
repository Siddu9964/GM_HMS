<?php
/**
 * API Endpoint: Get Dashboard KPIs
 * Returns real KPI data for New Registrations, OPD Waiting, and Active IPD Patients
 */

header('Content-Type: application/json');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    // Include database connection
    require_once __DIR__ . '/../../models/Database.php';
    
    $db = new Database();
    $db->connect();
    
    // 1. New Registrations (Today)
    $sql_registrations = "SELECT COUNT(*) as count FROM patient WHERE date = CURDATE()";
    $result_reg = $db->fetchAll($sql_registrations);
    $registrations = $result_reg ? (int)$result_reg[0]['count'] : 0;
    
    // 2. OPD Waiting (Today's appointments with status '1' or 'Pending')
    $sql_waiting = "SELECT COUNT(*) as count FROM appointments WHERE appointment_date = CURDATE() AND (appointment_status = '1' OR appointment_status = 'Pending' OR appointment_status = 'Waiting')";
    $result_waiting = $db->fetchAll($sql_waiting);
    $waiting = $result_waiting ? (int)$result_waiting[0]['count'] : 0;
    
    // 3. Active IPD Patients (Admitted patients with no discharge date)
    $sql_ipd = "SELECT COUNT(*) as count FROM ipd_admissions WHERE discharge_date IS NULL OR discharge_date = ''";
    $result_ipd = $db->fetchAll($sql_ipd);
    $ipd = $result_ipd ? (int)$result_ipd[0]['count'] : 0;
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => [
            'today_registrations' => $registrations,
            'waiting_patients' => $waiting,
            'active_ipd' => $ipd
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching KPIs: ' . $e->getMessage()
    ]);
}
