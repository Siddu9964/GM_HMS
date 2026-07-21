<?php
/**
 * Save Patient Vitals API
 * 
 * @package GM_HMS\Controllers
 * @version 1.1.0
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../../core/Autoloader.php';

use GM_HMS\Models\PatientVitalsStoreModel;

header('Content-Type: application/json');
session_start();

// Authentication
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Nurse', 'admin', 'Admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$nurseId = $_SESSION['user_id'] ?? null;
if (!$nurseId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nurse session expired']);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['patient_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid patient data']);
    exit();
}

try {
    // Exclusively using the JSON Store Model for "1 Patient 1 Row" architecture
    $jsonStore = new PatientVitalsStoreModel();
    
    $data = [
        'patient_id' => $input['patient_id'],
        'visit_id' => $input['visit_id'] ?? '---',
        'visit_type' => $input['visit_type'] ?? 'IPD',
        'temperature' => $input['temperature'],
        'bp_systolic' => $input['bp_systolic'],
        'bp_diastolic' => $input['bp_diastolic'],
        'pulse_rate' => $input['pulse_rate'],
        'respiratory_rate' => $input['respiratory_rate'],
        'spo2' => $input['spo2'],
        'weight' => $input['weight'],
        'consciousness_level' => $input['consciousness_level'] ?? 'Alert',
        'recorded_by' => $nurseId,
        'recorded_by_name' => $_SESSION['username'] ?? 'Nurse',
        'remarks' => $input['remarks'] ?? ''
    ];

    // This performs an UPSERT: Updates the single JSON row if patient exists, or creates it if new.
    $success = $jsonStore->saveDailyVitals($input['patient_id'], $data);

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Vitals recorded in JSON format (1 Row per Patient)',
            'patient_id' => $input['patient_id']
        ]);
    } else {
        throw new Exception("Failed to update patient JSON store");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server Error: ' . $e->getMessage()
    ]);
}
