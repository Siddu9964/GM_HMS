<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../core/Autoloader.php';
use GM_HMS\Models\NurseClinicalModel;

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$patientId = $data['patient_id'] ?? '';
$admissionId = $data['admission_id'] ?? '';
$cart = $data['cart'] ?? [];

if (empty($patientId) || empty($admissionId) || empty($cart)) {
    echo json_encode(['success' => false, 'message' => 'Missing patient details or cart is empty.']);
    exit;
}

try {
    $model = new NurseClinicalModel();
    $nurseId = $_SESSION['user_id'];
    
    foreach ($cart as $item) {
        $model->appendToDailyRecord($patientId, $admissionId, 'pharmacy_orders', $item, $nurseId);
    }
    
    echo json_encode(['success' => true, 'message' => 'Pharmacy order saved successfully.']);
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
