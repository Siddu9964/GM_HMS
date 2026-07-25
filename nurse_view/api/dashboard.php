<?php
/**
 * Nurse Dashboard API Controller
 * RESTful API for nurse dashboard data
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../../core/Autoloader.php';
require_once __DIR__ . '/../includes/nurse_auth_helper.php';

use GM_HMS\Models\NurseShiftModel;
use GM_HMS\Models\NurseVitalsModel;
use GM_HMS\Models\NurseMARModel;
use GM_HMS\Models\NurseNotesModel;

header('Content-Type: application/json');
session_start();

// Check authentication
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Nurse', 'Superintendent_Nurse', 'Nursing_Superintendent', 'admin', 'Admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$nurseId = $_SESSION['user_id'] ?? null;
// Use user_id for roleId since AuthenticationManager sets user_id to the staff sl_no and doesn't set role_id
$roleId = $_SESSION['role_id'] ?? $_SESSION['user_id'] ?? null;

if (!$nurseId || !$roleId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nurse ID or Role ID not found in session']);
    exit();
}

try {
    $shiftModel = new NurseShiftModel();
    $vitalsModel = new NurseVitalsModel();
    $marModel = new NurseMARModel();
    $notesModel = new NurseNotesModel();
    
    $db = GM_HMS\Database\SecureDatabase::getInstance();
    $conn = $db->getConnection();
    $currentWard = getCurrentNurseWard($conn, $nurseId);

    // Current shift (uses date range and dynamic shift type)
    $currentShift = $shiftModel->getCurrentShift($roleId);

    // Shift-wide stats (nurse-wise for current shift)
    $shiftStats = $shiftModel->getShiftStatistics($nurseId, null, $currentWard);

    // Assigned patients (Redesigned: shift-wise, nurse-wise, ward/room-wise)
    $assignedPatients = $shiftModel->getAssignedPatientsRedesigned($nurseId, $roleId, $currentWard);

    // Upcoming shifts (nurse-wise)
    $upcomingShifts = $shiftModel->getUpcomingShifts($roleId);

    // Individual nurse stats (user_id based)
    $marStats = $marModel->getMARStatistics($nurseId, null, $currentWard);
    $vitalsStats = $vitalsModel->getVitalsStatistics($nurseId);
    $overdueMeds = $marModel->getOverdueMedications($nurseId);
    $recentVitals = $vitalsModel->getRecentVitals($nurseId, 5);
    $abnormalVitals = $vitalsModel->getAbnormalVitals($nurseId);

    // Handover notes (today's important notes)
    $handoverNotes = $notesModel->getHandoverNotes(date('Y-m-d'));

    $response = [
        'success' => true,
        'data' => [
            'current_shift' => $currentShift,
            'upcoming_shifts' => $upcomingShifts,
            'statistics' => [
                'shift' => $shiftStats,
                'medications' => $marStats,
                'vitals' => $vitalsStats
            ],
            'assigned_patients' => $assignedPatients,
            'overdue_medications' => $overdueMeds,
            'recent_vitals' => $recentVitals,
            'handover_notes' => $handoverNotes,
            'abnormal_vitals' => $abnormalVitals
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
