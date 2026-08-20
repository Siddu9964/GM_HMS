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

$jsonInput = [];
$rawBody = file_get_contents('php://input');
if (!empty($rawBody)) {
    $decoded = json_decode($rawBody, true);
    if (is_array($decoded)) {
        $jsonInput = $decoded;
    }
}
$params = array_merge($_GET, $_POST, $jsonInput);

$patientId = $params['patient_id'] ?? '';
$admissionId = $params['admission_id'] ?? '';

if (empty($patientId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameter: patient_id']);
    exit();
}

try {
    $controller = new DischargeClearanceController();
    $result = $controller->initiateClearance($params);
    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
?>
