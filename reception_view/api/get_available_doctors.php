<?php
/**
 * API Endpoint: Get Available Doctors
 * Returns list of doctors with their specializations
 */

header('Content-Type: application/json');
session_start();

// Check if user is logged in as receptionist
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Receptionist', 'admin', 'Admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    // Include database connection
    require_once __DIR__ . '/../../models/Database.php';
    
    $db = new Database();
    $db->connect();
    
    // Get current day abbreviation (e.g., Mon, Tue, Wed)
    $today = date('D');
    
    // Query to get available doctors based on current day and time
    // 1. Must be Active
    // 2. Today must be in available_days list
    // 3. Current time must be between in_time and out_time
    $sql = "SELECT 
                doctor_id,
                full_name,
                specialization
            FROM doctors
            WHERE (status = 'Active' OR status IS NULL OR status = '')
              AND CONCAT(',', available_days, ',') LIKE CONCAT('%,', ?, ',%')
              AND (
                (CAST(in_time AS TIME) <= CURTIME() AND CAST(out_time AS TIME) >= CURTIME())
                OR (CAST(in_time AS TIME) <= CURTIME() AND CAST(out_time AS TIME) < CAST(in_time AS TIME)) -- Overnight shift start
                OR (CAST(out_time AS TIME) >= CURTIME() AND CAST(out_time AS TIME) < CAST(in_time AS TIME)) -- Overnight shift end
              )
            ORDER BY full_name ASC";
    
    $doctors = $db->fetchAll($sql, [$today]);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $doctors,
        'count' => count($doctors),
        'server_time' => date('H:i:s'),
        'server_day' => $today
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching doctors: ' . $e->getMessage()
    ]);
}
