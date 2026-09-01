<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../core/Autoloader.php';
use GM_HMS\Database\SecureDatabase;

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $db = SecureDatabase::getInstance();
    
    $status = $_GET['status'] ?? 'PENDING';
    
    $sql = "SELECT r.*, p.first_name, p.last_name, a.ward_name, a.room_no, a.bed_id 
            FROM ipd_pharmacy_return_requests r
            JOIN patient p ON r.patient_id COLLATE utf8mb4_unicode_ci = p.patient_id COLLATE utf8mb4_unicode_ci
            JOIN ipd_admissions a ON r.admission_id COLLATE utf8mb4_unicode_ci = a.admission_id COLLATE utf8mb4_unicode_ci
            WHERE r.status = ?
            ORDER BY r.requested_at DESC";
            
    $results = $db->fetchAll($sql, [$status]);
    
    echo json_encode(['success' => true, 'data' => $results]);
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
