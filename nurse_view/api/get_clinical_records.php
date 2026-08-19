<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../core/Autoloader.php';
use GM_HMS\Database\SecureDatabase;

$patientId = $_GET['patient_id'] ?? null;
$admissionId = $_GET['admission_id'] ?? null;

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
        WHERE patient_id = ? AND admission_id = ?
        ORDER BY record_date ASC, id ASC
    ");
    
    $stmt->bind_param("ss", $patientId, $admissionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $jsonColumns = [
        'consultant_visits', 'lab_tests', 'radiology_tests', 'other_tests', 
        'pharmacy_orders', 'pharmacy_returns', 'grbs_chart', 'nebulization_chart', 
        'dialysis_chart', 'oxygen_chart', 'ventilation_chart', 'blood_transfusion_chart', 
        'nurses_record', 'vitals', 'nursing_notes', 'procedures', 'billing_items', 'attachments', 'bp_chart', 'ward_transfer'
    ];
    
    // Initialize all arrays
    $records = [];
    foreach ($jsonColumns as $col) {
        $records[$col] = [];
    }

    while ($row = $result->fetch_assoc()) {
        $rowId = $row['id'];
        foreach ($jsonColumns as $col) {
            if (!empty($row[$col])) {
                $decoded = json_decode($row[$col], true);
                if (is_array($decoded)) {
                    foreach ($decoded as $k => $item) {
                        if (is_array($item)) {
                            if (empty($item['entry_id'])) {
                                $item['entry_id'] = 'ent_' . $rowId . '_' . $col . '_' . $k;
                            }
                            $item['_db_row_id'] = $rowId;
                            $item['_col_name'] = $col;
                            $item['_arr_idx'] = $k;
                            $records[$col][] = $item;
                        }
                    }
                }
            }
        }
    }
    
    echo json_encode(['success' => true, 'data' => $records]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
