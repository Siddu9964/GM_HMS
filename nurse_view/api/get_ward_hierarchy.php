<?php
/**
 * API: Get Ward Hierarchy & Bed Availability
 * Returns Floors, Wards, Room Types, and Beds with their availability status.
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../core/Autoloader.php';
use GM_HMS\Database\SecureDatabase;

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $db = SecureDatabase::getInstance();
    $conn = $db->getConnection();

    $action = $_GET['action'] ?? 'all';
    $floor = $_GET['floor'] ?? null;
    $ward = $_GET['ward'] ?? null;
    $roomType = $_GET['room_type'] ?? null;

    if ($action === 'floors') {
        // Get all distinct floors
        $sql = "SELECT DISTINCT floor_number, floor_name 
                FROM hospital_beds 
                WHERE floor_name IS NOT NULL AND floor_name != '' 
                ORDER BY floor_number ASC, floor_name ASC";
        $result = $conn->query($sql);
        $floors = [];
        while ($row = $result->fetch_assoc()) {
            $floors[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $floors]);
        exit();
    }

    if ($action === 'wards') {
        // Get wards for a specific floor (or all if floor not set)
        $sql = "SELECT DISTINCT ward_name 
                FROM hospital_beds 
                WHERE ward_name IS NOT NULL AND ward_name != ''";
        $params = [];
        $types = "";

        if (!empty($floor)) {
            $sql .= " AND floor_name = ?";
            $params[] = $floor;
            $types .= "s";
        }
        $sql .= " ORDER BY ward_name ASC";

        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $wards = [];
        while ($row = $res->fetch_assoc()) {
            $wards[] = $row['ward_name'];
        }
        $stmt->close();
        echo json_encode(['success' => true, 'data' => $wards]);
        exit();
    }

    if ($action === 'room_types') {
        // Get room types for a specific floor & ward
        $sql = "SELECT DISTINCT room_type 
                FROM hospital_beds 
                WHERE room_type IS NOT NULL AND room_type != ''";
        $params = [];
        $types = "";

        if (!empty($floor)) {
            $sql .= " AND floor_name = ?";
            $params[] = $floor;
            $types .= "s";
        }
        if (!empty($ward)) {
            $sql .= " AND ward_name = ?";
            $params[] = $ward;
            $types .= "s";
        }
        $sql .= " ORDER BY room_type ASC";

        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $roomTypes = [];
        while ($row = $res->fetch_assoc()) {
            $roomTypes[] = $row['room_type'];
        }
        $stmt->close();
        echo json_encode(['success' => true, 'data' => $roomTypes]);
        exit();
    }

    if ($action === 'beds') {
        // Get all beds with availability status for selected floor, ward, room_type
        $sql = "SELECT 
                    b.sl_no as bed_id,
                    b.bed_number,
                    b.floor_number,
                    b.floor_name,
                    b.ward_name,
                    b.room_type,
                    b.room_number,
                    b.room_name,
                    b.bed_status,
                    b.amount_per_day,
                    b.nursig_charge,
                    b.doctor_charge,
                    b.service_charge,
                    b.total_bed_amount,
                    b.patient_id,
                    b.admission_id,
                    CONCAT(COALESCE(p.first_name, ''), ' ', COALESCE(p.last_name, '')) as occupied_by_patient
                FROM hospital_beds b
                LEFT JOIN patient p ON b.patient_id COLLATE utf8mb4_general_ci = p.patient_id COLLATE utf8mb4_general_ci
                WHERE 1=1";
        
        $params = [];
        $types = "";

        if (!empty($floor)) {
            $sql .= " AND b.floor_name = ?";
            $params[] = $floor;
            $types .= "s";
        }
        if (!empty($ward)) {
            $sql .= " AND b.ward_name = ?";
            $params[] = $ward;
            $types .= "s";
        }
        if (!empty($roomType)) {
            $sql .= " AND b.room_type = ?";
            $params[] = $roomType;
            $types .= "s";
        }

        $sql .= " ORDER BY b.room_number ASC, b.bed_number ASC, b.sl_no ASC";

        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $beds = [];
        while ($row = $res->fetch_assoc()) {
            $beds[] = $row;
        }
        $stmt->close();
        echo json_encode(['success' => true, 'data' => $beds]);
        exit();
    }

    // Default action: Return full hierarchy (Floors, Wards, Room Types)
    $floorsRes = $conn->query("SELECT DISTINCT floor_number, floor_name FROM hospital_beds WHERE floor_name IS NOT NULL AND floor_name != '' ORDER BY floor_number ASC, floor_name ASC");
    $floors = [];
    while ($r = $floorsRes->fetch_assoc()) $floors[] = $r;

    $wardsRes = $conn->query("SELECT DISTINCT ward_name FROM hospital_beds WHERE ward_name IS NOT NULL AND ward_name != '' ORDER BY ward_name ASC");
    $wards = [];
    while ($r = $wardsRes->fetch_assoc()) $wards[] = $r['ward_name'];

    $typesRes = $conn->query("SELECT DISTINCT room_type FROM hospital_beds WHERE room_type IS NOT NULL AND room_type != '' ORDER BY room_type ASC");
    $roomTypes = [];
    while ($r = $typesRes->fetch_assoc()) $roomTypes[] = $r['room_type'];

    echo json_encode([
        'success' => true,
        'data' => [
            'floors' => $floors,
            'wards' => $wards,
            'room_types' => $roomTypes
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
