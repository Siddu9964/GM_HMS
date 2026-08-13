<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../core/Autoloader.php';
use GM_HMS\Database\SecureDatabase;

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$admissionId = $_GET['admission_id'] ?? '';

if (empty($admissionId)) {
    echo json_encode(['success' => false, 'message' => 'Missing admission ID']);
    exit;
}

try {
    $db = SecureDatabase::getInstance();
    
    $sql = "SELECT * FROM ipd_pharmacy_return_requests 
            WHERE admission_id = ? 
            ORDER BY requested_at DESC";
            
    $results = $db->fetchAll($sql, [$admissionId]);
    
    echo json_encode(['success' => true, 'data' => $results]);
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
