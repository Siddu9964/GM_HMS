<?php
session_start();

// Allow all nurse roles and admins to view the page
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Nurse', 'Superintendent_Nurse', 'Superintendent Nurse', 'Nursing_Superintendent', 'admin', 'Admin', 'Head Nurse'])) {
    header('Location: ../login.php');
    exit();
}

$nurseId = $_SESSION['user_id'] ?? null;
$nurseName = $_SESSION['username'] ?? 'Nurse';

require_once __DIR__ . '/../core/Autoloader.php';
require_once __DIR__ . '/includes/nurse_auth_helper.php';
use GM_HMS\Database\SecureDatabase;

if (isset($_GET['clear']) && $_GET['clear'] == '1') {
    unset($_SESSION['ksheet_patient_id']);
    unset($_SESSION['ksheet_admission_id']);
    header("Location: k_sheet_view.php");
    exit();
}

if (isset($_GET['patient_id'])) {
    $_SESSION['ksheet_patient_id'] = $_GET['patient_id'];
    $_SESSION['ksheet_admission_id'] = $_GET['admission_id'] ?? '';
    header("Location: k_sheet_view.php");
    exit();
}

$patientId = $_SESSION['ksheet_patient_id'] ?? null;
$admissionId = $_SESSION['ksheet_admission_id'] ?? null;

$isListView = empty($patientId);
$patientsList = [];

try {
    $db = SecureDatabase::getInstance();
    $conn = $db->getConnection();
    $currentWard = getCurrentNurseWard($conn, $nurseId);
    
    if ($isListView && $currentWard) {
        $stmt = $conn->prepare("
            SELECT p.first_name, p.last_name, p.patient_id, p.age, p.sex, p.blood_group, 
                   ia.admission_id, ia.ward_name, ia.room_no, ia.admission_date,
                   d.full_name as consultant_name 
            FROM patient p 
            JOIN ipd_admissions ia ON p.patient_id = ia.patient_id 
            LEFT JOIN hospital_beds b ON ia.bed_id = b.sl_no
            LEFT JOIN doctors d ON ia.admitting_doctor_id = d.doctor_id
            WHERE (ia.status = 'Admitted' OR ia.status = 'Active')
              AND b.floor_name = ? AND b.ward_name = ? AND b.room_type = ? 
            ORDER BY ia.admission_date DESC
        ");
        $stmt->bind_param("sss", $currentWard['floor_name'], $currentWard['ward_name'], $currentWard['room_type']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $patientsList[] = $row;
        }
    } elseif ($isListView && !$currentWard) {
        // Not assigned today
        $patientsList = [];
    } else {
        $patientName = 'N/A';
        $patientPID = $patientId;
        $patientIP = $admissionId ?? 'N/A';
        $patientAgeSex = 'N/A';
        $patientBlood = 'N/A';
        $patientLocation = 'N/A';
        $patientConsultant = 'N/A';
        $admissionDateVal = '';

        $stmt = $conn->prepare("
            SELECT p.first_name, p.last_name, p.patient_id, p.age, p.sex, p.blood_group, 
                   ia.ward_name, ia.room_no, ia.admission_date, ia.admission_id,
                   d.full_name as consultant_name 
            FROM patient p 
            LEFT JOIN ipd_admissions ia ON p.patient_id = ia.patient_id 
            LEFT JOIN doctors d ON ia.admitting_doctor_id = d.doctor_id
            WHERE p.patient_id = ?
            ORDER BY ia.admission_id DESC LIMIT 1
        ");
        $stmt->bind_param("s", $patientId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $patientName = trim($row['first_name'] . ' ' . $row['last_name']);
            $patientAgeSex = $row['age'] . ' Y / ' . $row['sex'];
            $patientBlood = $row['blood_group'] ?: 'N/A';
            $patientLocation = $row['ward_name'] . ' / ' . $row['room_no'];
            $patientConsultant = $row['consultant_name'] ?: 'N/A';
            $patientIP = $row['admission_id'] ?: 'N/A';
            if ($row['admission_date']) {
                $admissionDateVal = date('d-M-Y H:i', strtotime($row['admission_date']));
            }
        }
        
        $records = [
            'vitals' => [],
            'nurses_record' => [],
            'pharmacy_orders' => [],
            'lab_tests' => [],
            'radiology_tests' => [],
            'other_tests' => [],
            'grbs_chart' => [],
            'nebulization_chart' => [],
            'dialysis_chart' => [],
            'oxygen_chart' => [],
            'ventilation_chart' => [],
            'blood_transfusion_chart' => [],
            'consultant_visits' => [],
            'procedures' => [],
            'billing_items' => [],
            'nursing_notes' => []
        ];

        $records_stmt = $conn->prepare("SELECT * FROM ipd_clinical_records WHERE patient_id = ? ORDER BY record_date ASC");
        $records_stmt->bind_param("s", $patientId);
        $records_stmt->execute();
        $records_result = $records_stmt->get_result();
        
        while ($row = $records_result->fetch_assoc()) {
            foreach (array_keys($records) as $col) {
                if (!empty($row[$col])) {
                    $decoded = json_decode($row[$col], true);
                    if (is_array($decoded)) {
                        $records[$col] = array_merge($records[$col], $decoded);
                    }
                }
            }
        }
    }
} catch (Throwable $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>K-Sheet View</title>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/GM_HMS/assets/css/k_sheet_view.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="main-layout">
    <!-- Sidebar Navigation -->
    <?php include 'includes/nurse_sidebar.php'; ?>

    <div class="content-wrapper">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <h2 style="margin: 0; font-size: 1.5rem; color: #1F6B4A; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-file-medical-alt"></i> K-Sheet (Clinical Record)
            </h2>
            <div class="header-actions" style="display: flex; gap: 10px;">
                <?php if (!$isListView): ?>
                    <button class="btn-modern" onclick="window.location.href='k_sheet_view.php?clear=1'">
                        <i class="fas fa-list"></i> Patient List
                    </button>
                    <button class="btn-modern btn-primary" onclick="window.print()">
                        <i class="fas fa-print"></i> Print K-Sheet
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($isListView): ?>
            <div style="margin-bottom: 20px; font-size: 1.1rem; color: var(--text-muted); font-weight: 500;">Select an active patient to view their K-Sheet</div>
            
            <div class="bento-grid">
                <?php foreach ($patientsList as $p): 
                    $initials = strtoupper(substr($p['first_name'], 0, 1) . (isset($p['last_name'][0]) ? substr($p['last_name'], 0, 1) : ''));
                ?>
                <div class="patient-bento-card" onclick="window.location.href='k_sheet_view.php?patient_id=<?php echo urlencode($p['patient_id']); ?>&admission_id=<?php echo urlencode($p['admission_id']); ?>'">
                    <div class="bento-header">
                        <div class="avatar"><?php echo $initials; ?></div>
                        <div>
                            <h3><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></h3>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">Age: <?php echo htmlspecialchars($p['age']); ?> • <?php echo htmlspecialchars($p['sex']); ?></span>
                        </div>
                    </div>
                    <div class="bento-details">
                        <div>
                            <span style="display:block; font-size:0.75rem; text-transform:uppercase;">Patient ID</span>
                            <strong><?php echo htmlspecialchars($p['patient_id']); ?></strong>
                        </div>
                        <div>
                            <span style="display:block; font-size:0.75rem; text-transform:uppercase;">Admission ID</span>
                            <strong><?php echo htmlspecialchars($p['admission_id']); ?></strong>
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px; margin-top: auto;">
                        <span class="badge-chip"><i class="fas fa-bed"></i> <?php echo htmlspecialchars($p['ward_name'] . ' - ' . $p['room_no']); ?></span>
                        <span class="badge-chip" style="background: rgba(30, 64, 175, 0.1); color: #1e40af;"><i class="fas fa-user-md"></i> Dr. <?php echo htmlspecialchars($p['consultant_name'] ?: 'N/A'); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if(empty($patientsList)): ?>
                    <div class="glass-panel" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                        <i class="fas fa-info-circle" style="font-size: 3rem; color: #ccc; margin-bottom: 15px;"></i>
                        <h3 style="color: var(--text-muted);">No active admitted patients found.</h3>
                    </div>
                <?php endif; ?>
            </div>
            
        <?php else: ?>
        
            <!-- Premium Hero Header -->
            <div class="hero-card">
                <p class="hero-title">GM Hospital & Research Center</p>
                <h1 class="hero-name"><i class="fas fa-user-circle" style="opacity:0.8;"></i> <?php echo htmlspecialchars($patientName); ?></h1>
                
                <div class="hero-chips">
                    <div class="hero-chip"><i class="fas fa-id-card"></i> PID: <?php echo htmlspecialchars($patientPID); ?></div>
                    <div class="hero-chip"><i class="fas fa-file-medical"></i> IP: <?php echo htmlspecialchars($patientIP); ?></div>
                    <div class="hero-chip"><i class="fas fa-venus-mars"></i> <?php echo htmlspecialchars($patientAgeSex); ?></div>
                    <div class="hero-chip"><i class="fas fa-tint" style="color: #ff6b6b;"></i> <?php echo htmlspecialchars($patientBlood); ?></div>
                    <div class="hero-chip"><i class="fas fa-user-md"></i> <?php echo htmlspecialchars($patientConsultant); ?></div>
                    <div class="hero-chip"><i class="fas fa-procedures"></i> <?php echo htmlspecialchars($patientLocation); ?></div>
                    <div class="hero-chip"><i class="fas fa-calendar-check"></i> Admitted: <?php echo htmlspecialchars($admissionDateVal); ?></div>
                </div>
            </div>

            <!-- Vitals -->
            <?php if (!empty($records['vitals'])): ?>
            <div class="glass-panel">
                <div class="section-header">
                    <i class="fas fa-heartbeat"></i>
                    <h3>Vitals Monitoring</h3>
                </div>
                <div class="modern-table-wrapper">
                    <table class="modern-table">
                        <thead>
                            <tr><th>Date</th><th>Time</th><th>Temp (°F)</th><th>Pulse</th><th>Resp</th><th>BP</th><th>SpO2</th><th>GCS</th><th>Signature</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records['vitals'] as $v): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($v['vitals_date'] ?? ''); ?></strong></td>
                                <td><?php echo htmlspecialchars($v['vitals_time'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['temp'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['pulse'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['resp'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['bp'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['spo2'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['gcs'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['nurse_sign'] ?? ''); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Consultant Visits -->
            <?php if (!empty($records['consultant_visits'])): ?>
            <div class="glass-panel">
                <div class="section-header">
                    <i class="fas fa-user-md" style="color: #3b82f6; background: rgba(59, 130, 246, 0.1);"></i>
                    <h3>Consultant Visits</h3>
                </div>
                <div class="modern-table-wrapper">
                    <table class="modern-table">
                        <thead>
                            <tr><th>Date</th><th>Time</th><th>Consultant</th><th>Shift</th><th>Logged At</th><th>Logged By</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records['consultant_visits'] as $v): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($v['date'] ?? ''); ?></strong></td>
                                <td><?php echo htmlspecialchars($v['time'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['consultant'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['shift'] ?? ''); ?></td>
                                <td><small style="color:#64748b;"><?php echo htmlspecialchars($v['created_at'] ?? ''); ?></small></td>
                                <td><small style="color:#64748b;"><?php echo htmlspecialchars($v['created_by_name'] ?? ''); ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- GRBS Chart -->
            <?php if (!empty($records['grbs_chart'])): ?>
            <div class="glass-panel">
                <div class="section-header">
                    <i class="fas fa-vial" style="color: #f59e0b; background: rgba(245, 158, 11, 0.1);"></i>
                    <h3>GRBS Chart</h3>
                </div>
                <div class="modern-table-wrapper">
                    <table class="modern-table">
                        <thead>
                            <tr><th>Date</th><th>Time</th><th>Value (mg/dL)</th><th>Nurse</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records['grbs_chart'] as $v): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($v['grbs_date'] ?? ''); ?></strong></td>
                                <td><?php echo htmlspecialchars($v['grbs_time'] ?? ''); ?></td>
                                <td><strong><?php echo htmlspecialchars($v['grbs_value'] ?? ''); ?></strong></td>
                                <td><?php echo htmlspecialchars($v['grbs_nurse'] ?? ''); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Nebulization Chart -->
            <?php if (!empty($records['nebulization_chart'])): ?>
            <div class="glass-panel">
                <div class="section-header">
                    <i class="fas fa-lungs" style="color: #0ea5e9; background: rgba(14, 165, 233, 0.1);"></i>
                    <h3>Nebulization Chart</h3>
                </div>
                <div class="modern-table-wrapper">
                    <table class="modern-table">
                        <thead>
                            <tr><th>Date</th><th>Time</th><th>Medicine</th><th>Route</th><th>Frequency</th><th>Remarks</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records['nebulization_chart'] as $v): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($v['nebu_date'] ?? ''); ?></strong></td>
                                <td><?php echo htmlspecialchars($v['nebu_time'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['nebu_drug'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['nebu_route'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['nebu_freq'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['nebu_remarks'] ?? ''); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Dialysis Chart -->
            <?php if (!empty($records['dialysis_chart'])): ?>
            <div class="glass-panel">
                <div class="section-header">
                    <i class="fas fa-filter" style="color: #8b5cf6; background: rgba(139, 92, 246, 0.1);"></i>
                    <h3>Dialysis Chart</h3>
                </div>
                <div class="modern-table-wrapper">
                    <table class="modern-table">
                        <thead>
                            <tr><th>Date</th><th>Connecting Time</th><th>Disconnecting Time</th><th>Duration</th><th>Signature</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records['dialysis_chart'] as $v): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($v['dia_date'] ?? ''); ?></strong></td>
                                <td><?php echo htmlspecialchars($v['dia_start'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['dia_end'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['dia_dur'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['dia_sign'] ?? ''); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Oxygen Chart -->
            <?php if (!empty($records['oxygen_chart'])): ?>
            <div class="glass-panel">
                <div class="section-header">
                    <i class="fas fa-wind" style="color: #06b6d4; background: rgba(6, 182, 212, 0.1);"></i>
                    <h3>Oxygen Chart</h3>
                </div>
                <div class="modern-table-wrapper">
                    <table class="modern-table">
                        <thead>
                            <tr><th>Date</th><th>Connecting Time</th><th>Disconnecting Time</th><th>Duration</th><th>Signature</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records['oxygen_chart'] as $v): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($v['oxy_date'] ?? ''); ?></strong></td>
                                <td><?php echo htmlspecialchars($v['oxy_start'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['oxy_end'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['oxy_dur'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['oxy_sign'] ?? ''); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Ventilation Chart -->
            <?php if (!empty($records['ventilation_chart'])): ?>
            <div class="glass-panel">
                <div class="section-header">
                    <i class="fas fa-mask" style="color: #64748b; background: rgba(100, 116, 139, 0.1);"></i>
                    <h3>Ventilation Chart</h3>
                </div>
                <div class="modern-table-wrapper">
                    <table class="modern-table">
                        <thead>
                            <tr><th>Date</th><th>Connecting Time</th><th>Disconnecting Time</th><th>Duration</th><th>Signature</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records['ventilation_chart'] as $v): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($v['vent_date'] ?? ''); ?></strong></td>
                                <td><?php echo htmlspecialchars($v['vent_start'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['vent_end'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['vent_dur'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['vent_sign'] ?? ''); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Blood Transfusion Chart -->
            <?php if (!empty($records['blood_transfusion_chart'])): ?>
            <div class="glass-panel">
                <div class="section-header">
                    <i class="fas fa-tint" style="color: #ef4444; background: rgba(239, 68, 68, 0.1);"></i>
                    <h3>Blood Transfusion Chart</h3>
                </div>
                <div class="modern-table-wrapper">
                    <table class="modern-table">
                        <thead>
                            <tr><th>Date</th><th>Blood Group</th><th>Bag Number</th><th>Started</th><th>Ended</th><th>Vitals</th><th>Signature</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records['blood_transfusion_chart'] as $v): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($v['trans_date'] ?? ''); ?></strong></td>
                                <td><span class="badge-chip" style="background: rgba(239,68,68,0.1); color: #ef4444;"><?php echo htmlspecialchars($v['blood_group'] ?? ''); ?></span></td>
                                <td><?php echo htmlspecialchars($v['bag_number'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['time_started'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['time_ended'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['vitals_during'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['nurse_sign'] ?? ''); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Nurses Record -->
            <?php if (!empty($records['nurses_record'])): ?>
            <div class="glass-panel">
                <div class="section-header">
                    <i class="fas fa-notes-medical" style="color: #10b981; background: rgba(16, 185, 129, 0.1);"></i>
                    <h3>Nurses Record</h3>
                </div>
                <div class="modern-table-wrapper">
                    <table class="modern-table">
                        <thead>
                            <tr><th>Date</th><th>Time</th><th>Particulars</th><th>Units</th><th>Signature</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records['nurses_record'] as $v): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($v['date'] ?? ''); ?></strong></td>
                                <td><?php echo htmlspecialchars($v['time'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['particulars'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['units'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['signature'] ?? ''); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Lab Tests -->
            <?php if (!empty($records['lab_tests'])): ?>
            <div class="glass-panel">
                <div class="section-header">
                    <i class="fas fa-microscope" style="color: #9333ea; background: rgba(147, 51, 234, 0.1);"></i>
                    <h3>Lab Tests</h3>
                </div>
                <div class="modern-table-wrapper">
                    <table class="modern-table">
                        <thead>
                            <tr><th>Test ID</th><th>Test Name</th><th>Category</th><th>Qty</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records['lab_tests'] as $v): $d = $v['data'] ?? $v; ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($d['id'] ?? 'N/A'); ?></strong></td>
                                <td><?php echo htmlspecialchars($d['name'] ?? 'Unknown Test'); ?></td>
                                <td><?php echo htmlspecialchars($d['category'] ?? 'Lab'); ?></td>
                                <td><?php echo htmlspecialchars($d['qty'] ?? '1'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Radiology Tests -->
            <?php if (!empty($records['radiology_tests'])): ?>
            <div class="glass-panel">
                <div class="section-header">
                    <i class="fas fa-x-ray" style="color: #6366f1; background: rgba(99, 102, 241, 0.1);"></i>
                    <h3>Radiology / Imaging</h3>
                </div>
                <div class="modern-table-wrapper">
                    <table class="modern-table">
                        <thead>
                            <tr><th>Test ID</th><th>Test Name</th><th>Category</th><th>Qty</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records['radiology_tests'] as $v): $d = $v['data'] ?? $v; ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($d['id'] ?? 'N/A'); ?></strong></td>
                                <td><?php echo htmlspecialchars($d['name'] ?? 'Unknown Test'); ?></td>
                                <td><?php echo htmlspecialchars($d['category'] ?? 'Radiology'); ?></td>
                                <td><?php echo htmlspecialchars($d['qty'] ?? '1'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Other Tests -->
            <?php if (!empty($records['other_tests'])): ?>
            <div class="glass-panel">
                <div class="section-header">
                    <i class="fas fa-vials" style="color: #ec4899; background: rgba(236, 72, 153, 0.1);"></i>
                    <h3>Other Tests</h3>
                </div>
                <div class="modern-table-wrapper">
                    <table class="modern-table">
                        <thead>
                            <tr><th>Test ID</th><th>Test Name</th><th>Category</th><th>Qty</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records['other_tests'] as $v): $d = $v['data'] ?? $v; ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($d['id'] ?? 'N/A'); ?></strong></td>
                                <td><?php echo htmlspecialchars($d['name'] ?? 'Unknown Test'); ?></td>
                                <td><?php echo htmlspecialchars($d['category'] ?? 'Other'); ?></td>
                                <td><?php echo htmlspecialchars($d['qty'] ?? '1'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Pharmacy Orders -->
            <?php if (!empty($records['pharmacy_orders'])): ?>
            <div class="glass-panel">
                <div class="section-header">
                    <i class="fas fa-pills" style="color: #14b8a6; background: rgba(20, 184, 166, 0.1);"></i>
                    <h3>Pharmacy Orders</h3>
                </div>
                <div class="modern-table-wrapper">
                    <table class="modern-table">
                        <thead>
                            <tr><th>Medicine ID</th><th>Medicine Name</th><th>Batch / Stock</th><th>Qty</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records['pharmacy_orders'] as $v): $d = $v['data'] ?? $v; ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($d['id'] ?? 'N/A'); ?></strong></td>
                                <td><?php echo htmlspecialchars($d['name'] ?? 'Unknown Medicine'); ?></td>
                                <td><?php echo htmlspecialchars($d['batch'] ?? $d['category'] ?? 'N/A'); ?></td>
                                <td><strong><?php echo htmlspecialchars($d['qty'] ?? '1'); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Nursing Notes -->
            <?php if (!empty($records['nursing_notes'])): ?>
            <div class="glass-panel">
                <div class="section-header">
                    <i class="fas fa-clipboard" style="color: #f43f5e; background: rgba(244, 63, 94, 0.1);"></i>
                    <h3>Nursing Notes</h3>
                </div>
                <div class="modern-table-wrapper">
                    <table class="modern-table">
                        <thead>
                            <tr><th>Date</th><th>Note</th><th>Nurse</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records['nursing_notes'] as $v): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($v['date'] ?? date('Y-m-d')); ?></strong></td>
                                <td><?php echo htmlspecialchars($v['note'] ?? $v['particulars'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['nurse'] ?? $v['signature'] ?? 'N/A'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Procedures -->
            <?php if (!empty($records['procedures'])): ?>
            <div class="glass-panel">
                <div class="section-header">
                    <i class="fas fa-scalpel" style="color: #64748b; background: rgba(100, 116, 139, 0.1);"></i>
                    <h3>Procedures</h3>
                </div>
                <div class="modern-table-wrapper">
                    <table class="modern-table">
                        <thead>
                            <tr><th>Date</th><th>Procedure</th><th>Doctor / Nurse</th><th>Remarks</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records['procedures'] as $v): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($v['date'] ?? date('Y-m-d')); ?></strong></td>
                                <td><?php echo htmlspecialchars($v['procedure'] ?? $v['name'] ?? 'Unknown'); ?></td>
                                <td><?php echo htmlspecialchars($v['person'] ?? $v['signature'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($v['remarks'] ?? ''); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>
</body>
</html>>
