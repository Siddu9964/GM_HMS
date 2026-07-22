<?php
session_start();
header('Content-Type: application/json');

// Auth check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'hmsc_basaveshwranagara');
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $floor_number = $conn->real_escape_string($_POST['floor_number'] ?? '');
    $floor_name = $conn->real_escape_string($_POST['floor_name'] ?? '');
    $ward_name = $conn->real_escape_string($_POST['ward_name'] ?? '');
    $room_type = $conn->real_escape_string($_POST['room_type'] ?? '');
    $room_number = $conn->real_escape_string($_POST['room_number'] ?? '');
    $room_name = $conn->real_escape_string($_POST['room_name'] ?? '');
    $bed_number = $conn->real_escape_string($_POST['bed_number'] ?? '');
    
    $amount_per_day = (float)($_POST['amount_per_day'] ?? 0);
    $nursig_charge = (float)($_POST['nursig_charge'] ?? 0); // Spelled as per DB schema expectation in form
    $doctor_charge = (float)($_POST['doctor_charge'] ?? 0);
    $service_charge = (float)($_POST['service_charge'] ?? 0);
    $total_bed_amount = (float)($_POST['total_bed_amount'] ?? 0);
    $bed_status = $conn->real_escape_string($_POST['bed_status'] ?? 'Available');
    
    $user_id = $conn->real_escape_string($_SESSION['user_id']);

    // Handle custom overrides if 'ADD_NEW_CUSTOM' was selected
    if (!empty($_POST['floor_number_custom'])) $floor_number = $conn->real_escape_string($_POST['floor_number_custom']);
    if (!empty($_POST['floor_name_custom'])) $floor_name = $conn->real_escape_string($_POST['floor_name_custom']);
    if (!empty($_POST['ward_name_custom'])) $ward_name = $conn->real_escape_string($_POST['ward_name_custom']);
    if (!empty($_POST['room_type_custom'])) $room_type = $conn->real_escape_string($_POST['room_type_custom']);
    if (!empty($_POST['room_name_custom'])) $room_name = $conn->real_escape_string($_POST['room_name_custom']);
    if (!empty($_POST['bed_number_custom'])) $bed_number = $conn->real_escape_string($_POST['bed_number_custom']);

    $query = "INSERT INTO hospital_beds (
        floor_number, floor_name, ward_name, room_type, room_number, 
        room_name, bed_number, amount_per_day, nursig_charge, 
        doctor_charge, service_charge, total_bed_amount, bed_status
    ) VALUES (
        '$floor_number', '$floor_name', '$ward_name', '$room_type', '$room_number', 
        '$room_name', '$bed_number', $amount_per_day, $nursig_charge, 
        $doctor_charge, $service_charge, $total_bed_amount, '$bed_status'
    )";

    if ($conn->query($query) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Room/Bed mapped successfully.']);
    } else {
        // If columns don't exist exactly, we will fail. Let's try to handle basic schema variations.
        echo json_encode(['status' => 'error', 'message' => 'DB Insert Failed: ' . $conn->error]);
    }
}
$conn->close();
?>
