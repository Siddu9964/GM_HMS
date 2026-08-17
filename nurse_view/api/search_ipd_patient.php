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
    $currentWard = getCurrentNurseWard($conn, $nurseId);
    $roleId = $_SESSION['role_id'] ?? $_SESSION['user_id'] ?? null;

    $shiftModel = new \GM_HMS\Models\NurseShiftModel();
    $assignedPatients = $shiftModel->getAssignedPatientsRedesigned($nurseId, $roleId, $currentWard);
    
    $filtered = array_filter($assignedPatients, function($p) use ($query) {
        $q = strtolower($query);
        $name = strtolower(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''));
        $pid = strtolower($p['patient_id'] ?? '');
        $phone = strtolower($p['phone'] ?? '');
        $room = strtolower($p['room_no'] ?? $p['room_number'] ?? '');
        return str_contains($name, $q) || str_contains($pid, $q) || str_contains($phone, $q) || str_contains($room, $q);
    });
    
    echo json_encode(['success' => true, 'data' => array_values($filtered)]);
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
