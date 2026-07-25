<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../core/Autoloader.php';
use GM_HMS\Database\SecureDatabase;

$patientId = $_GET['patient_id'] ?? null;
$admissionId = $_GET['admission_id'] ?? null;
$date = $_GET['date'] ?? date('Y-m-d'); // Fetch records for this date by default

if (!$patientId || !$admissionId) {
    echo json_encode(['success' => false, 'message' => 'Missing patient_id or admission_id']);
    exit();
}

try {
    $db = SecureDatabase::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("
        SELECT * 
        FROM ipd_clinical_records 
        WHERE patient_id = ? AND admission_id = ? AND record_date = ?
        LIMIT 1
    ");
    
    $stmt->bind_param("sss", $patientId, $admissionId, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $records = [];
    if ($row = $result->fetch_assoc()) {
        $jsonColumns = [
            'consultant_visits', 'lab_tests', 'radiology_tests', 'other_tests', 
            'pharmacy_orders', 'pharmacy_returns', 'grbs_chart', 'nebulization_chart', 
            'dialysis_chart', 'oxygen_chart', 'ventilation_chart', 'blood_transfusion_chart', 
            'nurses_record', 'vitals', 'nursing_notes', 'procedures', 'billing_items', 'attachments'
        ];
        
        foreach ($jsonColumns as $col) {
            if (!empty($row[$col])) {
                $decoded = json_decode($row[$col], true);
                if (is_array($decoded)) {
                    $records[$col] = $decoded;
                }
            }
        }
    }
    
    echo json_encode(['success' => true, 'data' => $records]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
