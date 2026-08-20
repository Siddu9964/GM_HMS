<?php
if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/../../core/Autoloader.php';
use GM_HMS\Controllers\api\DischargeClearanceController;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$patientId = $_POST['patient_id'] ?? '';
$admissionId = $_POST['admission_id'] ?? '';

if (empty($patientId) || empty($admissionId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters (patient_id, admission_id)']);
    exit();
}

try {
    $controller = new DischargeClearanceController();
    $result = $controller->initiateClearance($_POST);
    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
?>
