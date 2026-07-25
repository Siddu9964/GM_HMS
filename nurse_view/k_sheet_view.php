<?php
session_start();

// Require Superintendent Nurse or Admin to view the page
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Superintendent_Nurse', 'Superintendent Nurse', 'admin', 'Admin', 'Head Nurse'])) {
    header('Location: dashboard.php');
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
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1F6B4A;
            --primary-light: #2c8c64;
            --bg-cream: #F3EFE6;
            --white: #FFFFFF;
            --text-main: #1B1B1B;
            --text-muted: #5e646a;
            --border: #D9D3C7;
            --radius-lg: 20px;
        }
        body { background-color: var(--bg-cream); margin: 0; padding: 0; color: #333; overflow-x: hidden; display: flex; width: 100%; }
        .main-layout { display: flex; width: 100%; }
        .content-wrapper { flex: 1; padding: 20px; min-height: 100vh; background-color: var(--bg-cream); display: flex; flex-direction: column; overflow: hidden; }
        .top-navbar {
            padding: 15px 30px; display: flex; justify-content: space-between; align-items: center;
            background: rgba(243, 239, 230, 0.9); backdrop-filter: blur(20px);
            border-radius: var(--radius-lg); border: 1px solid var(--border); margin-bottom: 20px;
        }
        .header-actions button { background: var(--white); border: 1px solid var(--border); border-radius: 50px; padding: 10px 20px; font-weight: 600; color: var(--primary); cursor: pointer; margin-left: 10px; transition: all 0.2s; }
        .header-actions button:hover { background: var(--bg-cream); }
        
        .page { background: white; max-width: 1100px; margin: 0 auto; padding: 40px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-radius: var(--radius-lg); }
        .header { text-align: center; border-bottom: 3px solid var(--primary); padding-bottom: 20px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: var(--primary); font-size: 28px; text-transform: uppercase; letter-spacing: 1px; }
        .header h2 { margin: 5px 0 0 0; color: #555; font-size: 18px; }
        
        .patient-info { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px; background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
        .info-group { font-size: 14px; }
        .info-group strong { color: var(--primary); display: inline-block; width: 100px; }
        
        .section { margin-bottom: 30px; }
        .section-title { font-size: 18px; font-weight: 700; color: white; background: var(--primary); padding: 8px 15px; margin: 0 0 10px 0; border-radius: 4px; }
        
        table { width: 100%; border-collapse: collapse; min-width: 600px; font-size: 13px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #f2f2f2; color: #333; font-weight: 600; white-space: nowrap; }
        
        /* Table Wrapper for Mobile Scrolling */
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; width: 100%; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 15px; }
        .table-responsive table { border: none; margin-bottom: 0; }
        .table-responsive th, .table-responsive td { border-left: none; border-right: none; }
        
        /* Patient Grid */
        .patient-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .patient-card { background: white; border-radius: 12px; padding: 20px; border: 1px solid var(--border); box-shadow: 0 2px 10px rgba(0,0,0,0.05); cursor: pointer; transition: transform 0.2s; }
        .patient-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-color: var(--primary); }
        .patient-card h3 { margin: 0 0 10px 0; color: var(--primary); }
        .patient-card .details { font-size: 13px; color: var(--text-muted); line-height: 1.6; }
        .patient-card .details strong { color: var(--text-main); }
        
        /* Mobile View Adjustments */
        @media (max-width: 768px) {
            .page { padding: 20px; }
            .patient-info { grid-template-columns: 1fr; gap: 10px; }
            .header-actions { flex-direction: column; width: 100%; }
            .header-actions button { margin-left: 0; width: 100%; padding: 12px; min-height: 44px; margin-bottom: 10px; }
        }

        @media print {
            body { background: white; padding: 0; }
            .nurse-sidebar, .top-navbar { display: none !important; }
            .content-wrapper { margin-left: 0 !important; padding: 0 !important; background: transparent; }
            .page { box-shadow: none; max-width: 100%; padding: 0; border-radius: 0; }
            .table-responsive { overflow-x: visible; }
            @page { margin: 1.5cm; }
        }
    </style>
</head>
<body>
<div class="main-layout">
    <!-- Sidebar Navigation -->
    <?php include 'includes/nurse_sidebar.php'; ?>

    <div class="content-wrapper">
        <!-- Header -->
        <?php 
        $pageTitle = 'K-Sheet Preview';
        include 'includes/nurse_navbar.php'; 
        ?>
            <div class="header-actions" style="display: flex; justify-content: flex-end; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
                <?php if (!$isListView): ?>
                    <button onclick="window.location.href='k_sheet_view.php?clear=1'" style="padding: 10px; border-radius: 8px; border: 1px solid #ccc; background: white; cursor: pointer; min-height: 44px;"><i class="fas fa-list"></i> Patient List</button>
                    <button onclick="window.print()" style="padding: 10px; border-radius: 8px; background: var(--primary); color: white; border: none; cursor: pointer; min-height: 44px;"><i class="fas fa-print"></i> Print K-Sheet</button>
                <?php endif; ?>
            </div>

        <?php if ($isListView): ?>
            <div style="margin-bottom: 20px; font-size: 18px; color: var(--text-muted);">Select an active patient to view their K-Sheet</div>
            <div class="patient-grid">
                <?php foreach ($patientsList as $p): ?>
                <div class="patient-card" onclick="window.location.href='k_sheet_view.php?patient_id=<?php echo urlencode($p['patient_id']); ?>&admission_id=<?php echo urlencode($p['admission_id']); ?>'">
                    <h3><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></h3>
                    <div class="details">
                        <div><strong>PID:</strong> <?php echo htmlspecialchars($p['patient_id']); ?></div>
                        <div><strong>IP No:</strong> <?php echo htmlspecialchars($p['admission_id']); ?></div>
                        <div><strong>Ward:</strong> <?php echo htmlspecialchars($p['ward_name'] . ' / ' . $p['room_no']); ?></div>
                        <div><strong>Doctor:</strong> <?php echo htmlspecialchars($p['consultant_name'] ?: 'N/A'); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(empty($patientsList)): ?>
                    <p style="color:var(--text-muted);">No active admitted patients found.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
        <div class="page">
            <div class="header">
                <h1>K-Sheet (Clinical Record)</h1>
                <h2>GM Hospital & Research Center</h2>
            </div>
        
            <div class="patient-info">
                <div class="info-group"><strong>Patient Name:</strong> <?php echo htmlspecialchars($patientName); ?></div>
                <div class="info-group"><strong>PID:</strong> <?php echo htmlspecialchars($patientPID); ?></div>
                <div class="info-group"><strong>IP No:</strong> <?php echo htmlspecialchars($patientIP); ?></div>
                <div class="info-group"><strong>Age/Sex:</strong> <?php echo htmlspecialchars($patientAgeSex); ?></div>
                <div class="info-group"><strong>Blood Group:</strong> <?php echo htmlspecialchars($patientBlood); ?></div>
                <div class="info-group"><strong>Consultant:</strong> <?php echo htmlspecialchars($patientConsultant); ?></div>
                <div class="info-group"><strong>Location:</strong> <?php echo htmlspecialchars($patientLocation); ?></div>
                <div class="info-group"><strong>Admitted On:</strong> <?php echo htmlspecialchars($admissionDateVal); ?></div>
            </div>

            <!-- Vitals -->
            <?php if (!empty($records['vitals'])): ?>
            <div class="section">
                <h3 class="section-title">Vitals Monitoring</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr><th>Date</th><th>Time</th><th>Temp (°F)</th><th>Pulse</th><th>Resp</th><th>BP</th><th>SpO2</th><th>GCS</th><th>Signature</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records['vitals'] as $v): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($v['vitals_date'] ?? ''); ?></td>
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
            <div class="section">
                <h3 class="section-title">Consultant Visits</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr><th>Date</th><th>Time</th><th>Consultant</th><th>Shift</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records['consultant_visits'] as $v): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($v['date'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['time'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['consultant'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['shift'] ?? ''); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- GRBS Chart -->
            <?php if (!empty($records['grbs_chart'])): ?>
            <div class="section">
                <h3 class="section-title">GRBS Chart</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr><th>Date</th><th>Time</th><th>Value (mg/dL)</th><th>Nurse</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records['grbs_chart'] as $v): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($v['grbs_date'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['grbs_time'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['grbs_value'] ?? ''); ?></td>
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
            <div class="section">
                <h3 class="section-title">Nebulization Chart</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr><th>Date</th><th>Time</th><th>Medicine</th><th>Route</th><th>Frequency</th><th>Remarks</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records['nebulization_chart'] as $v): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($v['nebu_date'] ?? ''); ?></td>
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
            <div class="section">
                <h3 class="section-title">Dialysis Chart</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr><th>Date</th><th>Connecting Time</th><th>Disconnecting Time</th><th>Duration</th><th>Signature</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records['dialysis_chart'] as $v): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($v['dia_date'] ?? ''); ?></td>
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
            <div class="section">
                <h3 class="section-title">Oxygen Chart</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr><th>Date</th><th>Connecting Time</th><th>Disconnecting Time</th><th>Duration</th><th>Signature</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records['oxygen_chart'] as $v): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($v['oxy_date'] ?? ''); ?></td>
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
            <div class="section">
                <h3 class="section-title">Ventilation Chart</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr><th>Date</th><th>Connecting Time</th><th>Disconnecting Time</th><th>Duration</th><th>Signature</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records['ventilation_chart'] as $v): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($v['vent_date'] ?? ''); ?></td>
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
            <div class="section">
                <h3 class="section-title">Blood Transfusion Chart</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr><th>Date</th><th>Blood Group</th><th>Bag Number</th><th>Started</th><th>Ended</th><th>Vitals</th><th>Signature</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records['blood_transfusion_chart'] as $v): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($v['trans_date'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($v['blood_group'] ?? ''); ?></td>
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
            <div class="section">
                <h3 class="section-title">Nurses Record</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr><th>Date</th><th>Time</th><th>Particulars</th><th>Units</th><th>Signature</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records['nurses_record'] as $v): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($v['date'] ?? ''); ?></td>
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

        </div>
        <?php endif; ?>
    </div>
</body>
</html>
