<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
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
    
    $explicitType = strtolower(trim($data['order_type'] ?? ''));

    foreach ($cart as $item) {
        $category = strtolower($item['category'] ?? '');
        $itemId = strtoupper(trim($item['id'] ?? ''));

        // Determine destination column strictly
        if ($explicitType === 'lab') {
            $column = 'lab_tests';
        } elseif ($explicitType === 'radiology' || $explicitType === 'rad') {
            $column = 'radiology_tests';
        } elseif ($explicitType === 'other' || $explicitType === 'oth') {
            $column = 'other_tests';
        } else {
            // Fallback detection based on service ID prefix and modality name
            if (strpos($itemId, 'RDS') === 0 || strpos($category, 'radiology') !== false || strpos($category, 'x-ray') !== false || strpos($category, 'x ray') !== false || strpos($category, 'ct') !== false || strpos($category, 'ultra sound') !== false || strpos($category, 'usg') !== false || strpos($category, 'mri') !== false || strpos($category, 'doppler') !== false) {
                $column = 'radiology_tests';
            } elseif (strpos($itemId, 'LAB') === 0 || strpos($category, 'lab') !== false) {
                $column = 'lab_tests';
            } else {
                $column = 'other_tests';
            }
        }
        
        // Save both ID and Name in the same JSON object in the main column
        $testData = [
            'id' => $item['id'] ?? '',
            'name' => $item['name'] ?? '',
            'qty' => $item['qty'] ?? 1,
            'category' => $item['category'] ?? ''
        ];
        
        $model->appendToDailyRecord($patientId, $admissionId, $column, $testData, $nurseId);
    }
    
    echo json_encode(['success' => true, 'message' => 'Test orders saved successfully.']);
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
