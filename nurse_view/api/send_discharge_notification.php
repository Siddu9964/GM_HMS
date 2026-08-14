<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../core/Autoloader.php';
use GM_HMS\Database\SecureDatabase;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$patientId = $_POST['patient_id'] ?? '';
$admissionId = $_POST['admission_id'] ?? '';

if (empty($patientId) || empty($admissionId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

try {
    $db = SecureDatabase::getInstance();
    $conn = $db->getConnection();
    
    // Fetch patient name to build a helpful message
    $stmt = $conn->prepare("SELECT first_name, last_name FROM patient WHERE patient_id = ?");
    $stmt->bind_param("s", $patientId);
    $stmt->execute();
    $pResult = $stmt->get_result();
    $patientName = "Unknown Patient";
    if ($pRow = $pResult->fetch_assoc()) {
        $patientName = trim($pRow['first_name'] . ' ' . $pRow['last_name']);
    }
    $stmt->close();

    $message = "Patient {$patientName} ({$patientId}) under Admission ID {$admissionId} is ready for discharge. Please process billing clearance.";
    
    $stmtInsert = $conn->prepare("INSERT INTO discharge_notifications (patient_id, admission_id, message, status) VALUES (?, ?, ?, 'Pending')");
    $stmtInsert->bind_param("sss", $patientId, $admissionId, $message);
    
    if ($stmtInsert->execute()) {
        echo json_encode(['success' => true, 'message' => 'Discharge notification sent to Admin successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to log notification: ' . $conn->error]);
    }
    $stmtInsert->close();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
?>
