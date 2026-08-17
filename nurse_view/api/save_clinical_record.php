<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../core/Autoloader.php';
use GM_HMS\Database\SecureDatabase;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$patientId = $_POST['patient_id'] ?? null;
$admissionId = $_POST['admission_id'] ?? null;
$chartType = $_POST['chart_type'] ?? null;
$recordedAt = $_POST['recorded_at'] ?? date('Y-m-d H:i:s');
$recordDate = date('Y-m-d', strtotime($recordedAt));

$nurseId = $_SESSION['user_id'] ?? 0;
$nurseName = $_SESSION['username'] ?? 'Unknown Nurse';

if (!$patientId || !$chartType) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields (patient_id, chart_type).']);
    exit();
}

$chartData = $_POST;
unset($chartData['patient_id'], $chartData['admission_id'], $chartData['chart_type'], $chartData['recorded_at'], $chartData['record_id']);

try {
    $db = SecureDatabase::getInstance();
    $conn = $db->getConnection();
    
    // Check if column actually exists (naive mapping for security)
    $validColumns = [
        'consultant_visits', 'lab_tests', 'radiology_tests', 'other_tests', 
        'pharmacy_orders', 'pharmacy_returns', 'grbs_chart', 'nebulization_chart', 
        'dialysis_chart', 'oxygen_chart', 'ventilation_chart', 'blood_transfusion_chart', 
        'nurses_record', 'vitals', 'nursing_notes', 'procedures', 'billing_items', 'attachments', 'bp_test', 'bed_trasfer'
    ];
    
    // Some legacy mappings to support the frontend out of the box
    $mapping = [
        'activity_record' => 'nurses_record',
        'ward_transfer' => 'bed_trasfer',
        'consultant_visit' => 'consultant_visits',
        'ot_procedure' => 'procedures',
        'lab_chart' => 'lab_tests',
        'radiology_chart' => 'radiology_tests',
        'cardiac_chart' => 'other_tests',
        'blood_transfusion' => 'blood_transfusion_chart',
        'nurse_record' => 'nurses_record',
        'support_charges' => 'billing_items',
        'bp_chart' => 'bp_test'
    ];
    
    $column = $mapping[$chartType] ?? $chartType;
    if (!in_array($column, $validColumns)) {
        throw new Exception("Invalid chart type: " . htmlspecialchars($column));
    }

    // Process File Uploads
    $uploadDir = __DIR__ . '/../../uploads/attachments/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    foreach ($_FILES as $key => $fileInfo) {
        if ($fileInfo['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
            $filename = uniqid('att_') . '.' . $ext;
            if (move_uploaded_file($fileInfo['tmp_name'], $uploadDir . $filename)) {
                $chartData[$key] = 'uploads/attachments/' . $filename;
            }
        }
    }

    // Serialize Form Data
    // Remove control fields to get the pure data payload
    $chartData = $_POST;
    unset($chartData['chart_type'], $chartData['patient_id'], $chartData['admission_id']);
    
    $chartData['entry_id'] = uniqid('ent_');
    $chartData['created_at'] = date('Y-m-d H:i:s');
    $chartData['created_by_name'] = $nurseName;
    
    $newEntry = $chartData;

    // Check if row exists for today
    $checkStmt = $conn->prepare("SELECT id, `$column` FROM ipd_clinical_records WHERE admission_id = ? AND record_date = ? LIMIT 1");
    $checkStmt->bind_param("ss", $admissionId, $recordDate);
    $checkStmt->execute();
    $res = $checkStmt->get_result();

    if ($res->num_rows > 0) {
        // Row exists -> UPDATE
        $row = $res->fetch_assoc();
        $recordId = $row['id'];
        
        $existingData = json_decode($row[$column], true);
        if (!is_array($existingData)) {
            $existingData = [];
        }
        
        $existingData[] = $newEntry;
        $jsonData = json_encode($existingData);
        
        $updateStmt = $conn->prepare("UPDATE ipd_clinical_records SET `$column` = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $updateStmt->bind_param("ssi", $jsonData, $nurseId, $recordId);
        $isSuccess = $updateStmt->execute();
        $updateStmt->close();
    } else {
        // Row doesn't exist -> INSERT
        $finalData = [$newEntry];
        $jsonData = json_encode($finalData);
        
        $insertStmt = $conn->prepare("INSERT INTO ipd_clinical_records (patient_id, admission_id, record_date, `$column`, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?)");
        $insertStmt->bind_param("ssssss", $patientId, $admissionId, $recordDate, $jsonData, $nurseId, $nurseId);
        $isSuccess = $insertStmt->execute();
        $recordId = $insertStmt->insert_id;
        $insertStmt->close();
    }
    
    $checkStmt->close();

    if ($isSuccess) {
        echo json_encode([
            'success' => true, 
            'message' => ucfirst(str_replace('_', ' ', $column)) . ' saved successfully!',
            'record_id' => $recordId
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed.']);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
