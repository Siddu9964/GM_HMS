<?php
/**
 * RESTful API for Patient Vitals JSON Management
 * Supports full CRUD operations with daily JSON serialization.
 * 
 * @package GM_HMS\Controllers\API
 * @version 1.0.0
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../../core/Autoloader.php';

use GM_HMS\Models\PatientVitalsStoreModel;

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

session_start();

// Basic Role Validation (Assuming Nurse or Doctor)
$allowedRoles = ['Nurse', 'Doctor', 'Admin'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized Access']);
    exit();
}

$model = new PatientVitalsStoreModel();
$method = $_SERVER['REQUEST_METHOD'];

// Handle Preflight
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    switch ($method) {
        case 'GET':
            $patientId = $_GET['patient_id'] ?? null;
            if (!$patientId) {
                throw new Exception("Patient ID is required");
            }
            
            // Getting history from the "1 Row per Patient" table
            $history = $model->getHistory($patientId);

            echo json_encode([
                'success' => true,
                'patient_id' => $patientId,
                'count' => count($history),
                'history' => $history
            ]);
            break;

        case 'DELETE':
            $patientId = $_GET['patient_id'] ?? null;
            if (!$patientId) {
                throw new Exception("Patient ID is required for deletion");
            }

            $success = $model->deleteHistory($patientId);

            echo json_encode([
                'success' => $success,
                'message' => $success ? "Historical JSON cleared" : 'Deletion failed'
            ]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
