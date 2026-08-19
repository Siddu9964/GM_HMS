<?php
ob_start();
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

// Maintain branch consistency
if (isset($_GET['branch'])) {
    $_SESSION['branch'] = strtolower(trim($_GET['branch']));
    $_SESSION['hospital_branch'] = $_SESSION['branch'];
} elseif (!isset($_SESSION['branch']) && !isset($_SESSION['hospital_branch'])) {
    $_SESSION['branch'] = 'basaveshwranagara';
    $_SESSION['hospital_branch'] = 'basaveshwranagara';
}

if (isset($_GET['clear']) && $_GET['clear'] == '1') {
    unset($_SESSION['ksheet_patient_id']);
    unset($_SESSION['ksheet_admission_id']);
    header("Location: k_sheet_view.php");
    exit();
}

if (isset($_GET['patient_id'])) {
    $_SESSION['ksheet_patient_id'] = trim($_GET['patient_id']);
    $_SESSION['ksheet_admission_id'] = trim($_GET['admission_id'] ?? '');
    header("Location: k_sheet_view.php");
    exit();
}

$patientId = trim($_SESSION['ksheet_patient_id'] ?? '');
$admissionId = trim($_SESSION['ksheet_admission_id'] ?? '');

$isListView = empty($patientId);
$patientsList = [];

try {
    $db = SecureDatabase::getInstance();
    $conn = $db->getConnection();
    $currentWard = getCurrentNurseWard($conn, $nurseId);
    
    if ($isListView) {
        require_once __DIR__ . '/../models/NurseShiftModel.php';
        $shiftModel = new \GM_HMS\Models\NurseShiftModel();
        
        $assigned = $shiftModel->getAssignedPatientsRedesigned($nurseId, $_SESSION['role_id'] ?? $nurseId, $currentWard);
        if ($assigned) {
            foreach ($assigned as $row) {
                $patientsList[] = $row;
            }
        }
    } else {
        $patientName = 'N/A';
        $patientPID = $patientId;
        $patientIP = $admissionId ?: 'N/A';
        $patientAgeSex = 'N/A';
        $patientBlood = 'N/A';
        $patientLocation = 'N/A';
        $patientConsultant = 'N/A';
        $admissionDateVal = '';

        // Query Patient & Admission Details
        $stmt = $conn->prepare("
            SELECT p.first_name, p.last_name, p.patient_id, p.age, p.sex, p.blood_group, 
                   ia.ward_name, ia.room_no, ia.admission_date, ia.admission_id,
                   d.full_name as consultant_name 
            FROM patient p 
            LEFT JOIN ipd_admissions ia ON p.patient_id = ia.patient_id 
            LEFT JOIN doctors d ON ia.admitting_doctor_id = d.doctor_id
            WHERE p.patient_id = ? OR ia.admission_id = ?
            ORDER BY ia.admission_id DESC LIMIT 1
        ");
        $stmt->bind_param("ss", $patientId, $admissionId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $patientName = trim($row['first_name'] . ' ' . $row['last_name']);
            $patientPID = $row['patient_id'] ?: $patientId;
            $patientAgeSex = ($row['age'] ?: '-') . ' Y / ' . ($row['sex'] ?: '-');
            $patientBlood = $row['blood_group'] ?: 'N/A';
            $patientLocation = trim(($row['ward_name'] ?: 'Ward') . ' / ' . ($row['room_no'] ?: 'Room'));
            $patientConsultant = $row['consultant_name'] ?: 'N/A';
            $patientIP = $row['admission_id'] ?: ($admissionId ?: 'N/A');
            if (!empty($row['admission_date'])) {
                $admissionDateVal = date('d-M-Y H:i', strtotime($row['admission_date']));
            }
        }
        $stmt->close();
        
        $records = [
            'vitals' => [], 'nurses_record' => [], 'pharmacy_orders' => [], 'lab_tests' => [],
            'radiology_tests' => [], 'other_tests' => [], 'grbs_chart' => [], 'nebulization_chart' => [],
            'dialysis_chart' => [], 'oxygen_chart' => [], 'ventilation_chart' => [], 'blood_transfusion_chart' => [],
            'consultant_visits' => [], 'procedures' => [], 'billing_items' => [], 'nursing_notes' => [],
            'pharmacy_returns' => [], 'attachments' => [], 'bp_chart' => [], 'ward_transfer' => []
        ];

        // Query all clinical records matching Patient ID or Admission ID
        $records_stmt = $conn->prepare("
            SELECT * FROM ipd_clinical_records 
            WHERE patient_id = ? OR (admission_id = ? AND admission_id != '' AND admission_id IS NOT NULL) 
            ORDER BY record_date ASC, id ASC
        ");
        $records_stmt->bind_param("ss", $patientId, $admissionId);
        $records_stmt->execute();
        $records_result = $records_stmt->get_result();
        
        while ($row = $records_result->fetch_assoc()) {
            foreach (array_keys($records) as $col) {
                if (!empty($row[$col]) && $row[$col] !== '[]' && $row[$col] !== 'null') {
                    $decoded = json_decode($row[$col], true);
                    if (is_array($decoded)) {
                        // Check if associative array instead of list of entries
                        if (!empty($decoded) && array_keys($decoded) !== range(0, count($decoded) - 1)) {
                            $decoded = [$decoded];
                        }
                        $records[$col] = array_merge($records[$col], $decoded);
                    }
                }
            }
        }
        $records_stmt->close();
    }
} catch (Throwable $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Inpatient Clinical Kardex (K-Sheet) - GM HMS</title>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/GM_HMS/assets/css/k_sheet_view.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="main-layout">
    <!-- Sidebar Navigation -->
    <?php include 'includes/nurse_sidebar.php'; ?>

    <div class="content-wrapper">
        
        <!-- Screen Action Navbar (Hidden in Print) -->
        <div class="top-navbar no-print">
            <div style="display: flex; align-items: center; gap: 12px;">
                <h2 style="margin: 0; font-size: 1.35rem; color: #1F6B4A; font-weight: 800; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-file-medical-alt"></i> Inpatient Clinical Kardex (K-Sheet)
                </h2>
            </div>
            <div class="header-actions">
                <?php if (!$isListView): ?>
                    <button class="btn-modern" onclick="window.location.href='nurse_workspace.php'">
                        <i class="fas fa-edit"></i> Edit in Workspace
                    </button>
                    <button class="btn-modern" onclick="window.location.href='k_sheet_view.php?clear=1'">
                        <i class="fas fa-users"></i> Change Patient
                    </button>
                    <button class="btn-modern btn-primary" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Official K-Sheet
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($isListView): ?>
            <!-- Patient List Selection View -->
            <div class="nopt-dashboard">
                <div class="dash-header">
                    <div>
                        <h3 style="margin: 0; font-size: 1.3rem; font-weight: 800; color: #1F6B4A; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-hospital-user"></i> Active Inpatients Kardex Directory
                            <span class="badge-chip"><?php echo count($patientsList); ?> Admitted</span>
                        </h3>
                        <p style="margin: 4px 0 0 0; font-size: 0.84rem; color: #527967;">
                            Select an admitted inpatient to view their full official medical Kardex flowsheet.
                        </p>
                    </div>

                    <div class="search-box-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" id="patientFilterInput" placeholder="Filter by Name, PID, IP#, Ward/Bed..." onkeyup="filterPatientCards()">
                    </div>
                </div>
                
                <div class="bento-grid" id="patientsContainer">
                    <?php foreach ($patientsList as $p): 
                        $pName = trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''));
                        $initials = strtoupper(substr($p['first_name'] ?? 'P', 0, 1) . (isset($p['last_name'][0]) ? substr($p['last_name'], 0, 1) : ''));
                        $searchData = strtolower($pName . ' ' . ($p['patient_id'] ?? '') . ' ' . ($p['admission_id'] ?? '') . ' ' . ($p['room_type'] ?? '') . ' ' . ($p['room_number'] ?? ''));
                    ?>
                    <div class="patient-bento-card" data-search="<?php echo htmlspecialchars($searchData); ?>" onclick="window.location.href='k_sheet_view.php?patient_id=<?php echo urlencode($p['patient_id']); ?>&admission_id=<?php echo urlencode($p['admission_id'] ?? ''); ?>'">
                        <div class="bento-header">
                            <div class="avatar"><?php echo $initials; ?></div>
                            <div>
                                <h3 style="margin: 0; font-size: 1.05rem; font-weight: 800; color: #1F6B4A;"><?php echo htmlspecialchars($pName); ?></h3>
                                <span style="font-size: 0.8rem; color: #527967; font-weight: 600;">Age: <?php echo htmlspecialchars($p['age'] ?? '-'); ?> • <?php echo htmlspecialchars($p['sex'] ?? '-'); ?></span>
                            </div>
                        </div>
                        <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                            <div>
                                <span style="display:block; font-size:0.72rem; text-transform:uppercase; color: #527967; font-weight: 700;">Patient ID</span>
                                <strong style="color: #1F6B4A;"><?php echo htmlspecialchars($p['patient_id'] ?? 'N/A'); ?></strong>
                            </div>
                            <div>
                                <span style="display:block; font-size:0.72rem; text-transform:uppercase; color: #527967; font-weight: 700;">IP Admission #</span>
                                <strong style="color: #1F6B4A;"><?php echo htmlspecialchars($p['admission_id'] ?? 'N/A'); ?></strong>
                            </div>
                        </div>
                        <div style="display: flex; gap: 8px; align-items: center; margin-left: auto; flex-wrap: wrap;">
                            <span class="badge-chip"><i class="fas fa-bed"></i> <?php echo htmlspecialchars(($p['room_type'] ?? 'Ward') . ' - ' . ($p['room_number'] ?? 'Bed')); ?></span>
                            <span class="badge-chip"><i class="fas fa-user-md"></i> Dr. <?php echo htmlspecialchars($p['doctor_name'] ?? 'Consultant'); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if(empty($patientsList)): ?>
                        <div class="glass-panel" style="text-align: center; padding: 40px;">
                            <i class="fas fa-user-injured" style="font-size: 3rem; color: #1F6B4A; opacity: 0.4; margin-bottom: 15px;"></i>
                            <h3 style="color: #1F6B4A; margin: 0;">No active admitted patients found.</h3>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <script>
            function filterPatientCards() {
                const query = document.getElementById('patientFilterInput').value.toLowerCase().trim();
                const cards = document.querySelectorAll('#patientsContainer .patient-bento-card');
                cards.forEach(card => {
                    const searchData = card.getAttribute('data-search') || '';
                    if (searchData.includes(query)) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }
            </script>
            
        <?php else: ?>

            <!-- ============================================================
                 OFFICIAL HOSPITAL KARDEX SHEET (K-SHEET) CONTAINER
                 ============================================================ -->
            <div class="ksheet-hospital-sheet">
                
                <table class="k-print-container-table">
                    <!-- Repeating Header on Every Printed Page -->
                    <thead>
                        <tr>
                            <td>
                                <!-- 1. Official GM Hospitals Brand Header -->
                                <div class="hospital-kheader">
                                    <div class="gm-brand-group">
                                        <div class="gm-hosp-title">GM HOSPITALS</div>
                                        <div class="gm-hosp-sub">
                                            <span class="gm-sub-line"></span>
                                            <span class="gm-sub-text">Nagarabhavi | Basaveshwaranagar</span>
                                            <span class="gm-sub-line"></span>
                                        </div>
                                        <div class="hk-doc-title">DEPARTMENT OF NURSING SERVICES • INPATIENT CLINICAL KARDEX RECORD (K-SHEET)</div>
                                    </div>
                                    <div class="hk-meta-box">
                                        <div class="hk-meta-item"><strong>FORM:</strong> IPD-KS-2026</div>
                                        <div class="hk-meta-item"><strong>BRANCH:</strong> <?php echo strtoupper(htmlspecialchars($_SESSION['branch'] ?? 'Basaveshwaranagar')); ?></div>
                                        <div class="hk-meta-item"><strong>GENERATED:</strong> <?php echo date('d-M-Y H:i'); ?></div>
                                    </div>
                                </div>

                                <!-- 2. Patient Demographics Matrix -->
                                <div class="hospital-pt-matrix">
                                    <div class="matrix-cell pt-name-cell">
                                        <span class="m-lbl">PATIENT FULL NAME</span>
                                        <div class="m-val-strong"><i class="fas fa-user"></i> <?php echo htmlspecialchars($patientName); ?></div>
                                        <div class="m-val-sub"><?php echo htmlspecialchars($patientAgeSex); ?> • Blood: <strong><?php echo htmlspecialchars($patientBlood); ?></strong></div>
                                    </div>
                                    <div class="matrix-cell">
                                        <span class="m-lbl">UHID / PATIENT ID</span>
                                        <div class="m-val font-mono"><?php echo htmlspecialchars($patientPID); ?></div>
                                    </div>
                                    <div class="matrix-cell">
                                        <span class="m-lbl">IP ADMISSION NO</span>
                                        <div class="m-val font-mono"><?php echo htmlspecialchars($patientIP); ?></div>
                                    </div>
                                    <div class="matrix-cell">
                                        <span class="m-lbl">WARD / BED / ROOM</span>
                                        <div class="m-val"><?php echo htmlspecialchars($patientLocation); ?></div>
                                    </div>
                                    <div class="matrix-cell">
                                        <span class="m-lbl">ATTENDING CONSULTANT</span>
                                        <div class="m-val">Dr. <?php echo htmlspecialchars($patientConsultant); ?></div>
                                    </div>
                                    <div class="matrix-cell">
                                        <span class="m-lbl">ADMISSION DATE & TIME</span>
                                        <div class="m-val"><?php echo !empty($admissionDateVal) ? htmlspecialchars($admissionDateVal) : 'N/A'; ?></div>
                                    </div>
                                    <div class="matrix-cell">
                                        <span class="m-lbl">CLINICAL STATUS</span>
                                        <div class="m-val status-badge-admitted"><i class="fas fa-check-circle"></i> ACTIVE INPATIENT</div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </thead>
                    
                    <!-- Table Body: Contains all Flowsheet Sections -->
                    <tbody>
                        <tr>
                            <td>

                <?php 
                $hasAnyRecords = false;
                foreach ($records as $arr) {
                    if (!empty($arr)) { $hasAnyRecords = true; break; }
                }
                ?>

                <!-- ============================================================
                     CLINICAL FLOWSHEETS (ALL 19 HOSPITAL RECORD SECTIONS)
                     ============================================================ -->

                <!-- 1. BP & Vital Signs Flowsheet -->
                <?php 
                $mergedVitals = array_merge($records['vitals'] ?? [], $records['bp_chart'] ?? []);
                if (!empty($mergedVitals)): 
                ?>
                <div class="ksheet-panel">
                    <div class="ksection-header">
                        <div class="ksection-title"><i class="fas fa-heartbeat"></i> 1. Vital Signs & Hemodynamic Flowsheet</div>
                        <span class="ksection-badge"><?php echo count($mergedVitals); ?> Logs</span>
                    </div>
                    <div class="modern-table-wrapper">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Blood Pressure (mmHg)</th>
                                    <th>Pulse (bpm)</th>
                                    <th>Temperature (°F)</th>
                                    <th>SpO2 (%)</th>
                                    <th>GCS / Status</th>
                                    <th>Recorded By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                usort($mergedVitals, function($a, $b) {
                                    $dateA = $a['bp_date'] ?? $a['vitals_date'] ?? $a['date'] ?? $a['created_date'] ?? '';
                                    $timeA = $a['bp_time'] ?? $a['vitals_time'] ?? $a['time'] ?? $a['created_time'] ?? '';
                                    $dateB = $b['bp_date'] ?? $b['vitals_date'] ?? $b['date'] ?? $b['created_date'] ?? '';
                                    $timeB = $b['bp_time'] ?? $b['vitals_time'] ?? $b['time'] ?? $b['created_time'] ?? '';
                                    return strtotime("$dateA $timeA") <=> strtotime("$dateB $timeB");
                                });
                                foreach ($mergedVitals as $v): 
                                    $bpVal = $v['bp_value'] ?? $v['bp'] ?? '';
                                    if (empty($bpVal) && !empty($v['bp_systolic'])) {
                                        $bpVal = $v['bp_systolic'] . '/' . ($v['bp_diastolic'] ?? '');
                                    }
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($v['bp_date'] ?? $v['vitals_date'] ?? $v['date'] ?? $v['created_date'] ?? '-'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($v['bp_time'] ?? $v['vitals_time'] ?? $v['time'] ?? '-'); ?></td>
                                    <td><strong style="color: #1F6B4A;"><?php echo htmlspecialchars($bpVal ?: '-'); ?></strong></td>
                                    <td><?php echo htmlspecialchars(($v['bp_pulse'] ?? $v['pulse'] ?? $v['pulse_rate'] ?? '-') . ' bpm'); ?></td>
                                    <td><?php echo htmlspecialchars($v['bp_temp'] ?? $v['temp'] ?? $v['temperature'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars(($v['bp_spo2'] ?? $v['spo2'] ?? '-') . '%'); ?></td>
                                    <td><?php echo htmlspecialchars($v['gcs'] ?? $v['consciousness_level'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($v['bp_nurse'] ?? $v['nurse_sign'] ?? $v['created_by_name'] ?? $v['recorded_by_name'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 2. GRBS (Diabetic Monitoring) Flowsheet -->
                <?php if (!empty($records['grbs_chart'])): ?>
                <div class="ksheet-panel">
                    <div class="ksection-header">
                        <div class="ksection-title"><i class="fas fa-vial"></i> 2. Diabetic Monitoring Flowsheet (GRBS / Blood Sugar)</div>
                        <span class="ksection-badge"><?php echo count($records['grbs_chart']); ?> Logs</span>
                    </div>
                    <div class="modern-table-wrapper">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Blood Sugar Value (mg/dL)</th>
                                    <th>Remarks / Meal Status</th>
                                    <th>Nurse Signature</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records['grbs_chart'] as $v): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($v['grbs_date'] ?? $v['date'] ?? $v['created_date'] ?? '-'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($v['grbs_time'] ?? $v['time'] ?? '-'); ?></td>
                                    <td><strong style="color: #1F6B4A; font-size: 0.95rem;"><?php echo htmlspecialchars(($v['grbs_value'] ?? $v['value'] ?? '-') . ' mg/dL'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($v['grbs_remarks'] ?? $v['remarks'] ?? 'Routine GRBS'); ?></td>
                                    <td><?php echo htmlspecialchars($v['grbs_nurse'] ?? $v['nurse'] ?? $v['created_by_name'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 3. Consultant Visits & Clinical Rounds -->
                <?php if (!empty($records['consultant_visits'])): ?>
                <div class="ksheet-panel">
                    <div class="ksection-header">
                        <div class="ksection-title"><i class="fas fa-user-md"></i> 3. Consultant Doctor Round Visits & Clinical Notes</div>
                        <span class="ksection-badge"><?php echo count($records['consultant_visits']); ?> Visits</span>
                    </div>
                    <div class="modern-table-wrapper">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Visiting Consultant</th>
                                    <th>Shift</th>
                                    <th>Clinical Findings & Round Orders</th>
                                    <th>Logged By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records['consultant_visits'] as $v): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($v['date'] ?? $v['visit_date'] ?? $v['created_date'] ?? '-'); ?> <?php echo htmlspecialchars($v['time'] ?? $v['visit_time'] ?? ''); ?></strong></td>
                                    <td><strong style="color: #1F6B4A;">Dr. <?php echo htmlspecialchars($v['consultant'] ?? $v['doctor_name'] ?? $v['doctor'] ?? '-'); ?></strong></td>
                                    <td><span class="badge-chip"><?php echo htmlspecialchars($v['shift'] ?? $v['shift_type'] ?? '-'); ?></span></td>
                                    <td><?php echo htmlspecialchars($v['remarks'] ?? $v['notes'] ?? '-'); ?></td>
                                    <td><small style="color:#527967;"><?php echo htmlspecialchars($v['created_by_name'] ?? $v['nurse'] ?? '-'); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 4. Bed / Ward Transfers Log -->
                <?php if (!empty($records['ward_transfer'])): ?>
                <div class="ksheet-panel">
                    <div class="ksection-header">
                        <div class="ksection-title"><i class="fas fa-bed"></i> 4. Inpatient Ward & Bed Transfer Log</div>
                        <span class="ksection-badge"><?php echo count($records['ward_transfer']); ?> Transfers</span>
                    </div>
                    <div class="modern-table-wrapper">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Transfer Timestamp</th>
                                    <th>Shifted From Ward/Bed</th>
                                    <th>Shifted To Ward/Bed</th>
                                    <th>Clinical Reason / Remarks</th>
                                    <th>Transfer Nurse</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records['ward_transfer'] as $v): 
                                    $tDate = $v['transfer_date'] ?? $v['date'] ?? $v['created_date'] ?? '-';
                                    if (strpos($tDate, 'T') !== false) {
                                        $tDate = str_replace('T', ' ', $tDate);
                                    }
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($tDate); ?></strong></td>
                                    <td><?php echo htmlspecialchars($v['from_ward'] ?? $v['from_bed'] ?? '-'); ?></td>
                                    <td><strong style="color: #1F6B4A;"><?php echo htmlspecialchars($v['to_ward'] ?? $v['to_bed'] ?? '-'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($v['transfer_remarks'] ?? $v['remarks'] ?? $v['reason'] ?? '-'); ?></td>
                                    <td><small style="color:#527967;"><?php echo htmlspecialchars($v['created_by_name'] ?? $v['nurse_sign'] ?? $v['transferred_by'] ?? '-'); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 5. Inpatient MAR / Pharmacy Orders -->
                <?php if (!empty($records['pharmacy_orders'])): ?>
                <div class="ksheet-panel">
                    <div class="ksection-header">
                        <div class="ksection-title"><i class="fas fa-pills"></i> 5. Inpatient Medication Administration Record (MAR / Pharmacy Orders)</div>
                        <span class="ksection-badge"><?php echo count($records['pharmacy_orders']); ?> Orders</span>
                    </div>
                    <div class="modern-table-wrapper">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Order Date</th>
                                    <th>Medication Name / Formulation</th>
                                    <th>Batch Number</th>
                                    <th>Dispensed Qty</th>
                                    <th>Dispensed By / Nurse</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records['pharmacy_orders'] as $v): 
                                    $d = $v['data'] ?? $v; 
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($v['created_date'] ?? $v['date'] ?? '-'); ?></strong></td>
                                    <td><strong style="color: #1F6B4A;"><?php echo htmlspecialchars($d['name'] ?? $d['medicine_name'] ?? $d['product_name'] ?? 'Unknown Medicine'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($d['batch'] ?? $d['batch_no'] ?? $d['batch_number'] ?? 'N/A'); ?></td>
                                    <td><strong><?php echo htmlspecialchars($d['qty'] ?? $d['quantity'] ?? '1'); ?></strong></td>
                                    <td><small style="color:#527967;"><?php echo htmlspecialchars($v['created_by_name'] ?? $v['nurse'] ?? '-'); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 6. Diagnostic Laboratory Investigations -->
                <?php if (!empty($records['lab_tests'])): ?>
                <div class="ksheet-panel">
                    <div class="ksection-header">
                        <div class="ksection-title"><i class="fas fa-microscope"></i> 6. Diagnostic Laboratory Investigations Flowsheet</div>
                        <span class="ksection-badge"><?php echo count($records['lab_tests']); ?> Tests</span>
                    </div>
                    <div class="modern-table-wrapper">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Order Date</th>
                                    <th>Test Code / ID</th>
                                    <th>Investigation Name</th>
                                    <th>Department</th>
                                    <th>Qty</th>
                                    <th>Ordered By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records['lab_tests'] as $v): 
                                    $d = $v['data'] ?? $v; 
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($v['created_date'] ?? $v['date'] ?? '-'); ?></strong></td>
                                    <td><span class="font-mono"><?php echo htmlspecialchars($d['id'] ?? $d['test_id'] ?? 'N/A'); ?></span></td>
                                    <td><strong style="color: #1F6B4A;"><?php echo htmlspecialchars($d['name'] ?? $d['test_name'] ?? 'Unknown Test'); ?></strong></td>
                                    <td><span class="badge-chip"><?php echo htmlspecialchars($d['category'] ?? 'Clinical Pathology'); ?></span></td>
                                    <td><?php echo htmlspecialchars($d['qty'] ?? $d['quantity'] ?? '1'); ?></td>
                                    <td><small style="color:#527967;"><?php echo htmlspecialchars($v['created_by_name'] ?? $v['nurse'] ?? '-'); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 7. Radiology & Diagnostic Imaging -->
                <?php if (!empty($records['radiology_tests'])): ?>
                <div class="ksheet-panel">
                    <div class="ksection-header">
                        <div class="ksection-title"><i class="fas fa-x-ray"></i> 7. Radiology & Diagnostic Imaging Flowsheet</div>
                        <span class="ksection-badge"><?php echo count($records['radiology_tests']); ?> Imaging</span>
                    </div>
                    <div class="modern-table-wrapper">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Order Date</th>
                                    <th>Procedure Code</th>
                                    <th>Imaging Investigation Name</th>
                                    <th>Modality</th>
                                    <th>Qty</th>
                                    <th>Ordered By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records['radiology_tests'] as $v): 
                                    $d = $v['data'] ?? $v; 
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($v['created_date'] ?? $v['date'] ?? '-'); ?></strong></td>
                                    <td><span class="font-mono"><?php echo htmlspecialchars($d['id'] ?? $d['test_id'] ?? 'N/A'); ?></span></td>
                                    <td><strong style="color: #1F6B4A;"><?php echo htmlspecialchars($d['name'] ?? $d['test_name'] ?? 'Unknown Procedure'); ?></strong></td>
                                    <td><span class="badge-chip"><?php echo htmlspecialchars($d['category'] ?? 'Radiology'); ?></span></td>
                                    <td><?php echo htmlspecialchars($d['qty'] ?? $d['quantity'] ?? '1'); ?></td>
                                    <td><small style="color:#527967;"><?php echo htmlspecialchars($v['created_by_name'] ?? $v['nurse'] ?? '-'); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 8. Other Diagnostic Tests -->
                <?php if (!empty($records['other_tests'])): ?>
                <div class="ksheet-panel">
                    <div class="ksection-header">
                        <div class="ksection-title"><i class="fas fa-vials"></i> 8. Other Diagnostic Investigations</div>
                    </div>
                    <div class="modern-table-wrapper">
                        <table class="modern-table">
                            <thead>
                                <tr><th>Order Date</th><th>Test ID</th><th>Investigation Name</th><th>Category</th><th>Qty</th><th>Ordered By</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records['other_tests'] as $v): 
                                    $d = $v['data'] ?? $v; 
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($v['created_date'] ?? $v['date'] ?? '-'); ?></strong></td>
                                    <td><span class="font-mono"><?php echo htmlspecialchars($d['id'] ?? $d['test_id'] ?? 'N/A'); ?></span></td>
                                    <td><strong style="color: #1F6B4A;"><?php echo htmlspecialchars($d['name'] ?? $d['test_name'] ?? 'Unknown Test'); ?></strong></td>
                                    <td><span class="badge-chip"><?php echo htmlspecialchars($d['category'] ?? 'Other'); ?></span></td>
                                    <td><?php echo htmlspecialchars($d['qty'] ?? $d['quantity'] ?? '1'); ?></td>
                                    <td><small style="color:#527967;"><?php echo htmlspecialchars($v['created_by_name'] ?? $v['nurse'] ?? '-'); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 9. Nebulization Therapy Flowsheet -->
                <?php if (!empty($records['nebulization_chart'])): ?>
                <div class="ksheet-panel">
                    <div class="ksection-header">
                        <div class="ksection-title"><i class="fas fa-wind"></i> 9. Nebulization Therapy Flowsheet</div>
                    </div>
                    <div class="modern-table-wrapper">
                        <table class="modern-table">
                            <thead>
                                <tr><th>Date & Time</th><th>Drug / Medication</th><th>Frequency</th><th>Nurse Signature</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records['nebulization_chart'] as $v): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($v['nebu_date'] ?? $v['date'] ?? $v['created_date'] ?? '-'); ?> <?php echo htmlspecialchars($v['nebu_time'] ?? $v['time'] ?? ''); ?></strong></td>
                                    <td><strong style="color: #1F6B4A;"><?php echo htmlspecialchars($v['nebu_drug'] ?? $v['medicine'] ?? $v['drug'] ?? '-'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($v['nebu_freq'] ?? $v['frequency'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($v['nebu_nurse'] ?? $v['nurse'] ?? $v['created_by_name'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 10. Dialysis Therapy Flowsheet -->
                <?php if (!empty($records['dialysis_chart'])): ?>
                <div class="ksheet-panel">
                    <div class="ksection-header">
                        <div class="ksection-title"><i class="fas fa-filter"></i> 10. Hemodialysis Therapy Flowsheet</div>
                    </div>
                    <div class="modern-table-wrapper">
                        <table class="modern-table">
                            <thead>
                                <tr><th>Date</th><th>Duration</th><th>Start Time</th><th>End Time</th><th>Dialysis Nurse</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records['dialysis_chart'] as $v): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($v['dia_date'] ?? $v['date'] ?? $v['created_date'] ?? '-'); ?></strong></td>
                                    <td><strong style="color: #1F6B4A;"><?php echo htmlspecialchars($v['dia_dur'] ?? $v['duration'] ?? '-'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($v['dia_start'] ?? $v['start_time'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($v['dia_end'] ?? $v['end_time'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($v['dia_nurse'] ?? $v['nurse'] ?? $v['created_by_name'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 11. Oxygen Therapy Flowsheet -->
                <?php if (!empty($records['oxygen_chart'])): ?>
                <div class="ksheet-panel">
                    <div class="ksection-header">
                        <div class="ksection-title"><i class="fas fa-lungs"></i> 11. Oxygen Therapy Flowsheet</div>
                    </div>
                    <div class="modern-table-wrapper">
                        <table class="modern-table">
                            <thead>
                                <tr><th>Date</th><th>Flow Rate (L/min)</th><th>Duration</th><th>Start - End Time</th><th>Nurse Signature</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records['oxygen_chart'] as $v): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($v['oxy_date'] ?? $v['date'] ?? $v['created_date'] ?? '-'); ?></strong></td>
                                    <td><strong style="color: #1F6B4A;"><?php echo htmlspecialchars($v['oxy_flow'] ?? $v['flow'] ?? '-'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($v['oxy_dur'] ?? $v['duration'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars(($v['oxy_start'] ?? '-') . ' - ' . ($v['oxy_end'] ?? '-')); ?></td>
                                    <td><?php echo htmlspecialchars($v['oxy_nurse'] ?? $v['nurse'] ?? $v['created_by_name'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 12. Ventilator Support Flowsheet -->
                <?php if (!empty($records['ventilation_chart'])): ?>
                <div class="ksheet-panel">
                    <div class="ksection-header">
                        <div class="ksection-title"><i class="fas fa-procedures"></i> 12. Ventilator & Respiratory Support Flowsheet</div>
                    </div>
                    <div class="modern-table-wrapper">
                        <table class="modern-table">
                            <thead>
                                <tr><th>Date</th><th>Ventilator Mode</th><th>Duration</th><th>Start - End Time</th><th>Nurse Signature</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records['ventilation_chart'] as $v): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($v['vent_date'] ?? $v['date'] ?? $v['created_date'] ?? '-'); ?></strong></td>
                                    <td><span class="badge-chip"><?php echo htmlspecialchars($v['vent_mode'] ?? $v['vent_remarks'] ?? $v['mode'] ?? '-'); ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($v['vent_dur'] ?? $v['duration'] ?? '-'); ?></strong></td>
                                    <td><?php echo htmlspecialchars(($v['vent_start'] ?? '-') . ' - ' . ($v['vent_end'] ?? '-')); ?></td>
                                    <td><?php echo htmlspecialchars($v['vent_nurse'] ?? $v['nurse'] ?? $v['created_by_name'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 13. Blood Transfusion Flowsheet -->
                <?php if (!empty($records['blood_transfusion_chart'])): ?>
                <div class="ksheet-panel">
                    <div class="ksection-header">
                        <div class="ksection-title"><i class="fas fa-syringe"></i> 13. Blood & Blood Component Transfusion Flowsheet</div>
                    </div>
                    <div class="modern-table-wrapper">
                        <table class="modern-table">
                            <thead>
                                <tr><th>Date</th><th>Blood Group</th><th>Bag / Unit #</th><th>Quantity (ml)</th><th>Vitals During</th><th>Nurse Signature</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records['blood_transfusion_chart'] as $v): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($v['trans_date'] ?? $v['date'] ?? $v['created_date'] ?? '-'); ?></strong></td>
                                    <td><span class="badge-chip"><?php echo htmlspecialchars($v['blood_group'] ?? '-'); ?></span></td>
                                    <td><strong>#<?php echo htmlspecialchars($v['bag_number'] ?? $v['bag_no'] ?? '-'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($v['quantity'] ?? '-'); ?> ml</td>
                                    <td><?php echo htmlspecialchars($v['vitals_during'] ?? $v['vitals'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($v['nurse_sign'] ?? $v['nurse'] ?? $v['created_by_name'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 14. Nurses Shift Care Notes -->
                <?php if (!empty($records['nurses_record'])): ?>
                <div class="ksheet-panel">
                    <div class="ksection-header">
                        <div class="ksection-title"><i class="fas fa-notes-medical"></i> 14. Nursing Handover & Shift Care Notes</div>
                    </div>
                    <div class="modern-table-wrapper">
                        <table class="modern-table">
                            <thead>
                                <tr><th>Date & Time</th><th>Nursing Care Particulars / Interventions</th><th>Units / Fluid</th><th>Nurse Signature</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records['nurses_record'] as $v): 
                                    $nDate = $v['nurse_date'] ?? $v['date'] ?? $v['created_date'] ?? '';
                                    $nPart = $v['nurse_part'] ?? $v['particulars'] ?? $v['status'] ?? '';
                                    if (empty($nDate) && empty($nPart)) continue;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($nDate ?: '-'); ?> <?php echo htmlspecialchars($v['nurse_time'] ?? $v['time'] ?? ''); ?></strong></td>
                                    <td><?php echo htmlspecialchars($nPart ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($v['nurse_units'] ?? $v['units'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($v['nurse_sign'] ?? $v['nurse'] ?? $v['created_by_name'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 15. Clinical Nursing Notes -->
                <?php if (!empty($records['nursing_notes'])): ?>
                <div class="ksheet-panel">
                    <div class="ksection-header">
                        <div class="ksection-title"><i class="fas fa-clipboard-check"></i> 15. Clinical Nursing Progress Notes</div>
                    </div>
                    <div class="modern-table-wrapper">
                        <table class="modern-table">
                            <thead>
                                <tr><th>Date & Time</th><th>Clinical Nursing Observation Note</th><th>Nurse Signature</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records['nursing_notes'] as $v): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($v['note_date'] ?? $v['date'] ?? $v['created_date'] ?? '-'); ?> <?php echo htmlspecialchars($v['note_time'] ?? $v['time'] ?? ''); ?></strong></td>
                                    <td><?php echo htmlspecialchars($v['note_text'] ?? $v['note'] ?? $v['notes'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($v['nurse_sign'] ?? $v['nurse'] ?? $v['created_by_name'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 16. Procedures -->
                <?php if (!empty($records['procedures'])): ?>
                <div class="ksheet-panel">
                    <div class="ksection-header">
                        <div class="ksection-title"><i class="fas fa-procedures"></i> 16. Clinical Procedures & Nursing Actions</div>
                    </div>
                    <div class="modern-table-wrapper">
                        <table class="modern-table">
                            <thead>
                                <tr><th>Date</th><th>Procedure Performed</th><th>Doctor / Performing Staff</th><th>Remarks</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records['procedures'] as $v): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($v['date'] ?? $v['created_date'] ?? date('Y-m-d')); ?></strong></td>
                                    <td><strong style="color: #1F6B4A;"><?php echo htmlspecialchars($v['procedure'] ?? $v['procedure_name'] ?? $v['name'] ?? 'Unknown'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($v['person'] ?? $v['doctor'] ?? $v['signature'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($v['remarks'] ?? $v['notes'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 17. Pharmacy Returns -->
                <?php if (!empty($records['pharmacy_returns'])): ?>
                <div class="ksheet-panel">
                    <div class="ksection-header">
                        <div class="ksection-title"><i class="fas fa-undo"></i> 17. Pharmacy Medication Returns Log</div>
                    </div>
                    <div class="modern-table-wrapper">
                        <table class="modern-table">
                            <thead>
                                <tr><th>Medicine ID</th><th>Medicine Name</th><th>Batch</th><th>Return Qty</th><th>Amount</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records['pharmacy_returns'] as $v): 
                                    $d = $v['data'] ?? $v; 
                                ?>
                                <tr>
                                    <td><span class="font-mono"><?php echo htmlspecialchars($d['id'] ?? $d['product_id'] ?? $d['item_id'] ?? 'N/A'); ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($d['name'] ?? $d['medicine_name'] ?? $d['product_name'] ?? 'Unknown Medicine'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($d['batch'] ?? $d['batch_no'] ?? 'N/A'); ?></td>
                                    <td><strong><?php echo htmlspecialchars($d['qty'] ?? $d['return_qty'] ?? '1'); ?></strong></td>
                                    <td>₹<?php echo htmlspecialchars($d['return_amount'] ?? $d['amount'] ?? '0.00'); ?></td>
                                    <td><span class="badge-chip"><?php echo htmlspecialchars($d['status'] ?? 'PENDING'); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 18. Billing Items -->
                <?php if (!empty($records['billing_items'])): ?>
                <div class="ksheet-panel">
                    <div class="ksection-header">
                        <div class="ksection-title"><i class="fas fa-file-invoice-dollar"></i> 18. IPD Consumables & Billing Flowsheet</div>
                    </div>
                    <div class="modern-table-wrapper">
                        <table class="modern-table">
                            <thead>
                                <tr><th>Date</th><th>Item Description</th><th>Category</th><th>Qty</th><th>Amount</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records['billing_items'] as $v): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($v['date'] ?? $v['created_date'] ?? date('Y-m-d')); ?></strong></td>
                                    <td><?php echo htmlspecialchars($v['item_name'] ?? $v['description'] ?? $v['name'] ?? 'Unknown'); ?></td>
                                    <td><span class="badge-chip"><?php echo htmlspecialchars($v['category'] ?? 'General'); ?></span></td>
                                    <td><?php echo htmlspecialchars($v['qty'] ?? $v['quantity'] ?? '1'); ?></td>
                                    <td><strong style="color: #1F6B4A;">₹<?php echo htmlspecialchars($v['amount'] ?? $v['price'] ?? '0.00'); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 19. Clinical Attachments -->
                <?php if (!empty($records['attachments'])): ?>
                <div class="ksheet-panel">
                    <div class="ksection-header">
                        <div class="ksection-title"><i class="fas fa-paperclip"></i> 19. Clinical Attachments & Diagnostic Scans</div>
                    </div>
                    <div class="modern-table-wrapper">
                        <table class="modern-table">
                            <thead>
                                <tr><th>Date</th><th>Document Title</th><th>Type</th><th>Action</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records['attachments'] as $v): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($v['date'] ?? $v['created_date'] ?? date('Y-m-d')); ?></strong></td>
                                    <td><?php echo htmlspecialchars($v['name'] ?? $v['title'] ?? 'Document'); ?></td>
                                    <td><span class="badge-chip"><?php echo htmlspecialchars($v['type'] ?? 'File'); ?></span></td>
                                    <td>
                                        <?php if(!empty($v['file_url']) || !empty($v['url']) || !empty($v['attachment'])): ?>
                                            <a href="<?php echo htmlspecialchars($v['file_url'] ?? $v['url'] ?? $v['attachment']); ?>" target="_blank" class="btn-modern btn-primary" style="padding: 4px 10px; font-size: 0.78rem;">
                                                <i class="fas fa-download"></i> View File
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #999;">Unavailable</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- If no clinical entries exist for this patient -->
                <?php if (!$hasAnyRecords): ?>
                <div class="ksheet-empty-state">
                    <i class="fas fa-notes-medical" style="font-size: 3rem; color: #1F6B4A; opacity: 0.4; margin-bottom: 14px;"></i>
                    <h3 style="color: #1F6B4A; margin-bottom: 6px; font-weight: 800;">No Clinical Entries Recorded Yet</h3>
                    <p style="color: #527967; font-size: 0.88rem; max-width: 500px; margin: 0 auto 20px;">
                        This inpatient currently has no active K-Sheet flowsheets logged. Enter vitals, consultant rounds, pharmacy orders, or nursing notes in the workspace.
                    </p>
                    <button class="btn-modern btn-primary" onclick="window.location.href='nurse_workspace.php'">
                        <i class="fas fa-plus"></i> Open Nurse Workspace
                    </button>
                </div>
                <?php endif; ?>

                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- 3. Official Hospital Verification Sign-Off Footer (Only on Final Page) -->
                <div class="hospital-signoff-grid">
                    <div class="signoff-box">
                        <div class="signoff-role">STAFF NURSE IN-CHARGE</div>
                        <div class="signoff-line"></div>
                        <div class="signoff-meta">Name & Signature / Date</div>
                    </div>
                    <div class="signoff-box">
                        <div class="signoff-role">ATTENDING CONSULTANT / RMO</div>
                        <div class="signoff-line"></div>
                        <div class="signoff-meta">Dr. <?php echo htmlspecialchars($patientConsultant); ?> / Signature</div>
                    </div>
                    <div class="signoff-box">
                        <div class="signoff-role">NURSING SUPERINTENDENT</div>
                        <div class="signoff-line"></div>
                        <div class="signoff-meta">Hospital Verification Stamp</div>
                    </div>
                </div>

            </div><!-- /ksheet-hospital-sheet -->

        <?php endif; ?>

    </div><!-- /content-wrapper -->
</div><!-- /main-layout -->
</body>
</html>
<?php 
$html = ob_get_clean(); 
file_put_contents(__DIR__ . '/../k_sheet_last_render.html', $html);
echo $html; 
?>
