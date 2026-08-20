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

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Nurse', 'Superintendent_Nurse', 'Nursing_Superintendent', 'admin', 'Admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$nurseId = $_SESSION['user_id'] ?? null;
$roleId = $_SESSION['role_id'] ?? $_SESSION['user_id'] ?? null;

if (!$nurseId || !$roleId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nurse ID or Role ID not found in session']);
    exit();
}

try {
    $shiftModel = new NurseShiftModel();
    
    $db = GM_HMS\Database\SecureDatabase::getInstance();
    $conn = $db->getConnection();
    $currentWard = getCurrentNurseWard($conn, $nurseId);

    // Current shift (uses date range and dynamic shift type from shift_schedules)
    $currentShift = $shiftModel->getCurrentShift($roleId);

    // Shift-wide stats (nurse-wise for current shift)
    $shiftStats = $shiftModel->getShiftStatistics($nurseId, null, $currentWard);

    // Assigned patients (Redesigned: shift-wise, nurse-wise, ward/room-wise)
    $assignedPatients = $shiftModel->getAssignedPatientsRedesigned($nurseId, $roleId, $currentWard);

    // Upcoming shifts (nurse-wise)
    $upcomingShifts = $shiftModel->getUpcomingShifts($roleId);

    // Fetch Vitals, Medication & Nursing Notes stats from ipd_clinical_records (active system)
    $today = date('Y-m-d');
    $todayRecords = [];
    try {
        $todayRecords = $db->fetchAll(
            "SELECT cr.*, 
                    CONCAT(p.first_name, ' ', IFNULL(p.last_name, '')) as patient_name, 
                    p.age, p.gender, p.sex, 
                    ia.room_type, ia.ward_name, ia.room_number, ia.bed_number 
             FROM ipd_clinical_records cr 
             LEFT JOIN patient p ON cr.patient_id = p.patient_id 
             LEFT JOIN ipd_admissions ia ON (cr.admission_id = ia.admission_id OR cr.patient_id = ia.patient_id)
             WHERE cr.record_date = ?", 
            [$today]
        );
    } catch (Throwable $e) {
        $todayRecords = [];
    }

    $vitalsStats = [
        'total_recorded' => 0,
        'abnormal_count' => 0
    ];
    $recentVitals = [];
    $abnormalVitals = [];

    $marStats = [
        'total_scheduled' => 0,
        'administered'    => 0,
        'pending'         => 0,
        'missed'          => 0
    ];
    $overdueMeds = [];
    $handoverNotes = [];

    foreach ($todayRecords as $r) {
        // Parse BP / Vitals chart
        if (!empty($r['bp_chart'])) {
            $bpList = json_decode($r['bp_chart'], true);
            if (is_array($bpList)) {
                foreach ($bpList as $bp) {
                    $vitalsStats['total_recorded']++;
                    
                    $temp = (float)($bp['bp_temp'] ?? 0);
                    $spo2 = (int)($bp['bp_spo2'] ?? 100);
                    $pulse = (int)($bp['bp_pulse'] ?? 72);
                    $bpVal = $bp['bp_value'] ?? '';
                    $bpParts = explode('/', $bpVal);
                    $sys = isset($bpParts[0]) ? (int)$bpParts[0] : 0;
                    $dia = isset($bpParts[1]) ? (int)$bpParts[1] : 0;

                    $vItem = [
                        'patient_id'    => $r['patient_id'],
                        'patient_name'  => $r['patient_name'] ?: 'Patient',
                        'bed_number'    => $r['bed_number'] ?: '-',
                        'room_number'   => $r['room_number'] ?: '-',
                        'temperature'   => $temp,
                        'bp_systolic'   => $sys,
                        'bp_diastolic'  => $dia,
                        'bp_value'      => $bpVal,
                        'pulse_rate'    => $pulse,
                        'spo2'          => $spo2,
                        'recorded_at'   => ($bp['bp_date'] ?? $r['record_date']) . ' ' . ($bp['bp_time'] ?? '00:00')
                    ];

                    $recentVitals[] = $vItem;

                    if ($temp > 100.4 || ($temp > 0 && $temp < 96.0) || $sys > 140 || ($sys > 0 && $sys < 90) || $dia > 90 || ($spo2 > 0 && $spo2 < 95)) {
                        $vitalsStats['abnormal_count']++;
                        $abnormalVitals[] = $vItem;
                    }
                }
            }
        }

        // Parse Pharmacy Orders / MAR
        if (!empty($r['pharmacy_orders'])) {
            $orders = json_decode($r['pharmacy_orders'], true);
            if (is_array($orders)) {
                foreach ($orders as $ord) {
                    $marStats['total_scheduled']++;
                    $st = strtolower($ord['data']['status'] ?? 'pending');
                    if ($st === 'given' || $st === 'administered') {
                        $marStats['administered']++;
                    } else if ($st === 'missed') {
                        $marStats['missed']++;
                    } else {
                        $marStats['pending']++;
                    }
                }
            }
        }

        // Parse Nursing Notes
        if (!empty($r['nursing_notes'])) {
            $notes = json_decode($r['nursing_notes'], true);
            if (is_array($notes)) {
                foreach ($notes as $n) {
                    $handoverNotes[] = $n;
                }
            }
        }
    }

    // Limit recent vitals to top 5
    $recentVitals = array_slice($recentVitals, 0, 5);

    $response = [
        'success' => true,
        'data' => [
            'current_shift' => $currentShift,
            'upcoming_shifts' => $upcomingShifts,
            'statistics' => [
                'shift'       => $shiftStats,
                'medications' => $marStats,
                'vitals'      => $vitalsStats
            ],
            'assigned_patients'   => $assignedPatients,
            'overdue_medications' => $overdueMeds,
            'recent_vitals'       => $recentVitals,
            'handover_notes'      => $handoverNotes,
            'abnormal_vitals'     => $abnormalVitals
        ]
    ];

    echo json_encode($response);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

