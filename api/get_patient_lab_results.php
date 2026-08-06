<?php
session_start();
header('Content-Type: application/json');

if (!isset($_GET['patient_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing patient_id']);
    exit;
}

$patient_id = $_GET['patient_id'];

// Database connection based on branch
$branch = strtolower($_SESSION['hospital_branch'] ?? $_SESSION['branch'] ?? '');
$db_name = ($branch === 'basaveshwaranagar' || $branch === 'basaveshwranagara') ? 'hmsc_basaveshwranagara' : 'hmsci';

$conn = new mysqli('localhost', 'root', '', $db_name);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Fetch results from both OPD and IPD tables
$sql = "
    SELECT 'OPD' as source, result_id, order_id, test_name, result_date, status, report_file 
    FROM lab_results 
    WHERE patient_id = ? 
    UNION ALL 
    SELECT 'IPD' as source, result_id, order_id, test_name, result_date, status, report_file 
    FROM ipd_lab_results 
    WHERE patient_id = ? 
    ORDER BY result_date DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("ss", $patient_id, $patient_id);
$stmt->execute();
$result = $stmt->get_result();

$lab_results = [];
while ($row = $result->fetch_assoc()) {
    $lab_results[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'data' => $lab_results]);
