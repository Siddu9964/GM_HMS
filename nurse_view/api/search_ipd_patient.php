<?php
/**
 * API to search admitted patients (IPD)
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../core/Autoloader.php';
require_once __DIR__ . '/../includes/nurse_auth_helper.php';
use GM_HMS\Database\SecureDatabase;

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$nurseId = $_SESSION['user_id'];

$query = $_GET['q'] ?? '';

if (empty($query)) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

try {
    $db = SecureDatabase::getInstance();
    $conn = $db->getConnection();
    
    $q = "%{$query}%";
    
    // Search by Patient ID, Admission ID, First Name, Last Name, Phone
    $sql = "SELECT a.admission_id, a.patient_id, a.ward, a.room_no, a.bed_id, 
                   p.first_name, p.last_name, p.age, p.sex, p.phone
            FROM ipd_admissions a 
            JOIN patient p ON a.patient_id = p.patient_id 
            WHERE a.status != 'Discharged' 
              AND (
                  a.admission_id LIKE ? 
                  OR a.patient_id LIKE ? 
                  OR p.first_name LIKE ? 
                  OR p.last_name LIKE ? 
                  OR p.phone LIKE ?
              )
            LIMIT 10";
            
    $results = $db->fetchAll($sql, [
        $q, $q, $q, $q, $q
    ]);
    
    echo json_encode(['success' => true, 'data' => $results]);
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
