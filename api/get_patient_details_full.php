<?php
session_start();
header('Content-Type: application/json');

if (!isset($_GET['patient_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing patient_id']);
    exit;
}

$patient_id = $_GET['patient_id'];

$conn = new mysqli('localhost', 'root', '', 'hmsc_basaveshwranagara');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Prepare statement
$stmt = $conn->prepare("
    SELECT 
        p.patient_id, 
        CONCAT(IFNULL(p.first_name, ''), ' ', IFNULL(p.last_name, '')) AS full_name,
        p.age AS age_years,
        p.sex AS gender,
        p.blood_group,
        p.phone AS phone_number,
        i.admission_date,
        i.chief_complaint,
        i.diagnosis
    FROM patient p
    LEFT JOIN ipd_admissions i ON p.patient_id = i.patient_id AND i.status = 'Admitted'
    WHERE p.patient_id = ?
    ORDER BY i.admission_date DESC
    LIMIT 1
");

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => $conn->error]);
    exit;
}

$stmt->bind_param("s", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'data' => $row
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Patient not found']);
}

$stmt->close();
$conn->close();
?>
