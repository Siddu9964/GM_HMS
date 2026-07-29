<?php
session_start();
// Check authentication (TEMPORARILY BYPASSED FOR TESTING)
// if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Nurse', 'admin', 'Admin'])) {
//     header('Location: ../login.php');
//     exit();
// }

// Force branch for testing to connect to hmsc_basaveshwranagara database
$_SESSION['branch'] = 'basaveshwaranagar';

$nurseId = $_SESSION['user_id'] ?? null;
$nurseName = $_SESSION['username'] ?? 'Nurse';

require_once __DIR__ . '/../core/Autoloader.php';
use GM_HMS\Database\SecureDatabase;

if (isset($_POST['patient_id'])) {
    $_SESSION['current_patient_id'] = $_POST['patient_id'];
    $_SESSION['current_admission_id'] = $_POST['admission_id'] ?? '';
    header("Location: medication.php");
    exit();
}

// If patient_id is passed via GET, store in session and redirect to clear URL
if (isset($_GET['patient_id'])) {
    $_SESSION['current_patient_id'] = $_GET['patient_id'];
    $_SESSION['current_admission_id'] = $_GET['admission_id'] ?? '';
    header("Location: medication.php");
    exit();
}

if (isset($_GET['clear']) && $_GET['clear'] == '1') {
    unset($_SESSION['current_patient_id']);
    unset($_SESSION['current_admission_id']);
    header("Location: medication.php");
    exit();
}

// Retrieve from session for a clean URL experience
$patientId = $_SESSION['current_patient_id'] ?? null;
$admissionId = $_SESSION['current_admission_id'] ?? null;

$patientName = 'Select a Patient';
$patientPID = 'N/A';
$patientIP = $admissionId && $admissionId !== 'undefined' ? $admissionId : 'N/A';
$patientAgeSex = 'N/A';
$patientBlood = 'N/A';
$patientLocation = 'N/A';
$patientConsultant = 'N/A';
$admissionDateVal = '';



if ($patientId && $patientId !== 'undefined') {
    try {
        $db = SecureDatabase::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("
            SELECT p.first_name, p.last_name, p.patient_id, p.age, p.sex, p.blood_group, 
                   ia.ward_name, ia.room_no, ia.admission_date,
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
            $patientPID = $row['patient_id'];
            $patientAgeSex = $row['age'] . ' Y / ' . $row['sex'];
            $patientBlood = $row['blood_group'] ?: 'N/A';
            $patientLocation = $row['ward_name'] . ' / ' . $row['room_no'];
            $patientConsultant = $row['consultant_name'] ?: 'N/A';
            if ($row['admission_date']) {
                $admissionDateVal = date('Y-m-d\TH:i', strtotime($row['admission_date']));
            }
        }
    } catch (Throwable $e) {
        // Fallback gracefully on error
    }
}

// Generate initials
$nameParts = explode(' ', $patientName);
$initials = '';
if (!empty($nameParts[0])) $initials .= strtoupper(substr($nameParts[0], 0, 1));
if (!empty($nameParts[1])) $initials .= strtoupper(substr($nameParts[1], 0, 1));
if (empty($initials)) $initials = 'PT';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPD Activity Record - GM HMS</title>
    
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    
    
    <style>
        :root {
            --primary: #1F6B4A;
            --primary-light: #2c8c64;
            --primary-dark: #154d34;
            --primary-glow: rgba(31,107,74,0.08);
            --bg-body: #F8FAFC;
            --bg-surface: #FFFFFF;
            --text-main: #1E293B;
            --text-muted: #64748B;
            --border-light: #E2E8F0;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.05);
            --danger: #EF4444;
            --success: #10B981;
            --radius-md: 12px;
            --radius-lg: 16px;
        }
        * { font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; }
        body { background: var(--bg-body); color: var(--text-main); margin: 0; padding-bottom: 40px; }
        
        /* Patient Banner */
        .patient-banner {
            background: #fff;
            padding: 20px 24px;
            margin: 20px 24px 0 24px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .banner-avatar {
            width: 60px; height: 60px; border-radius: 50%;
            background: var(--primary-glow);
            color: var(--primary);
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; font-weight: 700;
        }
        .banner-info { flex: 1; }
        .banner-name { font-size: 20px; font-weight: 700; margin-bottom: 8px; color: var(--text-main); display: flex; align-items: center; gap: 10px; }
        .status-dot { width: 8px; height: 8px; background: var(--success); border-radius: 50%; display: inline-block; box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.2); }
        .banner-chips { display: flex; flex-wrap: wrap; gap: 12px; }
        .b-chip { font-size: 13px; color: var(--text-muted); display: flex; align-items: center; gap: 6px; }
        .b-chip strong { color: var(--text-main); font-weight: 600; }

        /* Main Layout */
        .medication-layout {
            display: flex;
            gap: 24px;
            margin: 24px;
            align-items: flex-start;
        }

        /* Sidebar Navigation */
        .medication-sidebar {
            width: 260px;
            background: var(--bg-surface);
            border-radius: var(--radius-lg);
            padding: 16px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            position: sticky;
            top: 150px;
            flex-shrink: 0;
            max-height: calc(100vh - 170px);
            overflow-y: auto;
        }
        /* Custom Scrollbar for Sidebar */
        .medication-sidebar::-webkit-scrollbar { width: 6px; }
        .medication-sidebar::-webkit-scrollbar-track { background: transparent; }
        .medication-sidebar::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 10px; }
        .medication-sidebar::-webkit-scrollbar-thumb:hover { background: rgba(0,0,0,0.2); }
        .med-nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 4px;
            border: 1px solid transparent;
        }
        .med-nav-item:hover { background: #f1f5f9; }
        .med-nav-item.active { background: var(--primary-glow); border-color: rgba(31,107,74,0.1); }
        .med-nav-icon {
            width: 36px; height: 36px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            background: #f1f5f9; color: var(--text-muted);
            font-size: 14px; transition: all 0.2s ease;
        }
        .med-nav-item.active .med-nav-icon { background: white; color: var(--primary); box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .med-nav-text { display: flex; flex-direction: column; }
        .med-nav-title { font-size: 14px; font-weight: 600; color: var(--text-main); }
        .med-nav-item.active .med-nav-title { color: var(--primary-dark); }
        .med-nav-desc { font-size: 11px; color: var(--text-muted); }

        /* Content Area */
        .medication-content { flex: 1; min-width: 0; }
        .section-container { display: none; animation: fadeIn 0.3s ease; }
        .section-container.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .section-header h2 { font-size: 22px; font-weight: 700; margin: 0; color: var(--text-main); display: flex; align-items: center; gap: 10px; }
        .section-header h2 i { color: var(--primary); font-size: 20px; }

        /* Cards & Forms */
        .glass-card, .card { background: var(--bg-surface); border-radius: var(--radius-lg); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm); margin-bottom: 24px; padding: 24px; overflow: hidden; }
        .card-header { padding-bottom: 16px; border-bottom: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .card-header h3 { font-size: 16px; font-weight: 600; margin: 0; color: var(--text-main); display: flex; align-items: center; gap: 8px; }
        .card-header h3 i { color: var(--primary); }
        
        .entry-form-card { background: #f8fafc; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 20px; margin-bottom: 20px; display: none; }
        .entry-form-card.active { display: block; animation: slideDown 0.3s ease; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .entry-form-card h4 { font-size: 14px; font-weight: 600; color: var(--primary); margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px; }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control { padding: 10px 14px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 14px; transition: all 0.2s ease; width: 100%; background: #fff; }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); }
        .form-control[readonly] { background: #f1f5f9; color: var(--text-muted); cursor: not-allowed; }

        /* Buttons */
        .btn-action { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s ease; background: var(--primary); color: white; box-shadow: var(--shadow-sm); }
        .btn-action:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .btn-action.btn-delete { background: var(--danger); }
        .btn-action.btn-delete:hover { background: #dc2626; }
        
        .btn-add, .section-header .btn-action { display: inline-flex; align-items: center; gap: 8px; background: white; color: var(--primary); border: 1px solid var(--primary); padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; box-shadow: none; }
        .btn-add:hover, .section-header .btn-action:hover { background: var(--primary-glow); transform: none; color: var(--primary); }

        /* Tables */
        .table-scroll, .data-table-wrapper { overflow-x: auto; border-radius: 8px; border: 1px solid var(--border-light); margin-top: 10px; }
        .data-table { width: 100%; border-collapse: collapse; background: white; }
        .data-table thead th { background: #f8fafc; padding: 12px 16px; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-light); text-align: left; }
        .data-table tbody td { padding: 14px 16px; font-size: 14px; color: var(--text-main); border-bottom: 1px solid var(--border-light); }
        .data-table tbody tr:hover td { background: #f8fafc; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .medication-layout { flex-direction: column; }
            .medication-sidebar { width: 100%; position: static; }
        }
    </style>


</head>
<body>
    <!-- Hidden Form for Patient Selection -->
    <form id="patient-select-form" method="POST" action="medication.php" style="display:none;">
        <input type="hidden" name="patient_id" id="sel_patient_id">
        <input type="hidden" name="admission_id" id="sel_admission_id">
    </form>

<div class="main-layout" style="display: flex; width: 100%;">
    <!-- Sidebar Navigation -->
    <?php include 'includes/nurse_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="content-wrapper" style="flex: 1; display: block !important; overflow-x: hidden !important; overflow-y: auto !important; height: 100%;">
        <!-- Header -->
        <?php 
        $pageTitle = 'Medications';
        include 'includes/nurse_navbar.php'; 
        ?>

        <?php if ($patientId && $patientId !== 'undefined'): ?>

                <!-- PATIENT BANNER -->
        <div class="patient-banner">
            <div class="banner-avatar"><?php echo htmlspecialchars($initials); ?></div>
            <div class="banner-info">
                <div class="banner-name"><?php echo htmlspecialchars($patientName); ?> <span class="status-dot"></span></div>
                <div class="banner-chips">
                    <div class="b-chip"><i class="fas fa-id-card"></i> <strong>PID:</strong> <?php echo htmlspecialchars($patientPID); ?></div>
                    <div class="b-chip"><i class="fas fa-file-invoice"></i> <strong>IP No:</strong> <?php echo htmlspecialchars($patientIP); ?></div>
                    <div class="b-chip"><i class="fas fa-bed"></i> <strong>Ward:</strong> <?php echo htmlspecialchars($patientLocation); ?></div>
                    <div class="b-chip"><i class="fas fa-user-md"></i> <strong>Doctor:</strong> <?php echo htmlspecialchars($patientConsultant); ?></div>
                    <div class="b-chip"><i class="fas fa-tint"></i> <strong>Blood:</strong> <?php echo htmlspecialchars($patientBlood); ?></div>
                    <div class="b-chip"><i class="fas fa-user"></i> <strong>Age/Sex:</strong> <?php echo htmlspecialchars($patientAgeSex); ?></div>
                </div>
            </div>
        </div>

        <!-- Quick Action Ribbon (Horizontal Nav) -->
        
        <div class="medication-layout">
            <div class="medication-sidebar">
                <div class="med-nav-item active" onclick="switchSection('sec-activity'); activateMedTab(this)">
                    <div class="med-nav-icon"><i class="fas fa-file-medical"></i></div>
                    <div class="med-nav-text"><span class="med-nav-title">Activity Record</span><span class="med-nav-desc">Admission & Status</span></div>
                </div>
                <div class="med-nav-item" onclick="switchSection('sec-transfer'); activateMedTab(this)">
                    <div class="med-nav-icon"><i class="fas fa-exchange-alt"></i></div>
                    <div class="med-nav-text"><span class="med-nav-title">Ward Transfer</span><span class="med-nav-desc">Patient movement</span></div>
                </div>
                <div class="med-nav-item" onclick="switchSection('sec-visits'); activateMedTab(this)">
                    <div class="med-nav-icon"><i class="fas fa-stethoscope"></i></div>
                    <div class="med-nav-text"><span class="med-nav-title">Doctor Visits</span><span class="med-nav-desc">Consultant logs</span></div>
                </div>
                <div class="med-nav-item" onclick="switchSection('sec-clinical'); activateMedTab(this)">
                    <div class="med-nav-icon"><i class="fas fa-heartbeat"></i></div>
                    <div class="med-nav-text"><span class="med-nav-title">Clinical Chart</span><span class="med-nav-desc">GRBS & Nebulization</span></div>
                </div>
                <div class="med-nav-item" onclick="switchSection('sec-dialysis'); activateMedTab(this)">
                    <div class="med-nav-icon"><i class="fas fa-filter"></i></div>
                    <div class="med-nav-text"><span class="med-nav-title">Dialysis</span><span class="med-nav-desc">Session records</span></div>
                </div>
                <div class="med-nav-item" onclick="switchSection('sec-oxygen'); activateMedTab(this)">
                    <div class="med-nav-icon"><i class="fas fa-wind"></i></div>
                    <div class="med-nav-text"><span class="med-nav-title">Oxygen</span><span class="med-nav-desc">Usage tracking</span></div>
                </div>
                <div class="med-nav-item" onclick="switchSection('sec-vent'); activateMedTab(this)">
                    <div class="med-nav-icon"><i class="fas fa-lungs"></i></div>
                    <div class="med-nav-text"><span class="med-nav-title">Ventilator</span><span class="med-nav-desc">Machine logs</span></div>
                </div>
                <div class="med-nav-item" onclick="switchSection('sec-blood'); activateMedTab(this)">
                    <div class="med-nav-icon"><i class="fas fa-tint"></i></div>
                    <div class="med-nav-text"><span class="med-nav-title">Blood Transfusion</span><span class="med-nav-desc">Bag tracking</span></div>
                </div>
                <div class="med-nav-item" onclick="switchSection('sec-nurse'); activateMedTab(this)">
                    <div class="med-nav-icon"><i class="fas fa-user-nurse"></i></div>
                    <div class="med-nav-text"><span class="med-nav-title">Nursing Notes</span><span class="med-nav-desc">Shift records</span></div>
                </div>
            </div>
            
            <div class="medication-content">


        <!-- Dynamic Workspace + Right Timeline -->
        
                
                <!-- Section 1: Activity Record -->
                <div id="sec-activity" class="section-container active">
                    <div class="section-header"><h2><i class="fas fa-file-medical"></i> Activity Record</h2></div>
                    <div class="glass-card">
                        <div class="form-grid">
                            <div class="form-group"><label>Admission Date & Time</label><input type="datetime-local" class="form-control" value="<?php echo htmlspecialchars($admissionDateVal); ?>"></div>
                            <div class="form-group"><label>Discharge Date & Time</label><input type="datetime-local" class="form-control"></div>
                            <div class="form-group"><label>Primary Consultant</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($patientConsultant); ?>"></div>
                            <div class="form-group"><label>Reference Doctor</label><input type="text" class="form-control"></div>
                            <div class="form-group"><label>Current Ward/Room</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($patientLocation); ?>"></div>
                            <div class="form-group"><label>Status</label>
                                <select class="form-control"><option>Active Treatment</option><option>Discharged</option></select>
                            </div>
                        </div>
                        <button class="btn-action" style="margin-top: 15px;"><i class="fas fa-save"></i> Update Record</button>
                    </div>

                </div>

                <!-- Section 2: Ward Transfer -->
                <div id="sec-transfer" class="section-container">
                    <div class="section-header"><h2><i class="fas fa-exchange-alt"></i> Ward Transfer</h2><button class="btn-action" onclick="toggleForm('transfer-form')"><i class="fas fa-plus"></i> Add Transfer</button></div>
                    <div id="transfer-form" class="entry-form-card active">
                        <h4><i class="fas fa-random"></i> New Transfer</h4>
                        <div class="form-grid">
                            <div class="form-group"><label>Date & Time</label><input type="datetime-local" class="form-control"></div>
                            <div class="form-group"><label>From Ward</label><input type="text" class="form-control"></div>
                            <div class="form-group"><label>To Ward</label><input type="text" class="form-control"></div>
                            <div class="form-group"><label>Remarks</label><input type="text" class="form-control"></div>
                        </div>
                        <button class="btn-action" style="margin-top:10px;"><i class="fas fa-save"></i> Save</button>
                    </div>
                </div>

                <!-- Section 3: Consultant Visits -->
                <div id="sec-visits" class="section-container">
                    <div class="section-header"><h2><i class="fas fa-stethoscope"></i> Consultant Visits</h2><button class="btn-action" onclick="toggleForm('visit-form')"><i class="fas fa-plus"></i> Log Visit</button></div>
                    <div id="visit-form" class="entry-form-card active">
                        <h4><i class="fas fa-user-md"></i> Log Visit</h4>
                        <div class="form-grid">
                            <div class="form-group"><label>Date</label><input type="date" class="form-control" name="date"></div>
                            <div class="form-group"><label>Time</label><input type="time" class="form-control" name="time"></div>
                            <div class="form-group"><label>Consultant</label><input type="text" class="form-control" name="consultant"></div>
                            <div class="form-group"><label>Shift</label><select class="form-control" name="shift"><option>Morning</option><option>Evening</option></select></div>
                        </div>
                        <button class="btn-action" style="margin-top:10px;"><i class="fas fa-save"></i> Save</button>
                    </div>
                    <div class="data-table-wrapper" style="margin-top:20px;">
                        <table class="data-table">
                            <thead><tr><th>Date</th><th>Time</th><th>Consultant</th><th>Shift</th><th>Action</th></tr></thead>
                            <tbody id="visits-tbody">
                                <tr><td colspan="5" style="text-align:center;">No records</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>


                <!-- Section: Clinical Chart (GRBS & Nebulization) -->
                <div id="sec-clinical" class="section-container">
                    <div class="section-header"><h2><i class="fas fa-heartbeat"></i> Clinical Chart</h2></div>

                    <div class="glass-card" style="margin-top: 20px;">
                        <div class="section-header" style="border:none; padding:0; margin-bottom:15px;">
                            <h3 style="margin:0; font-size:18px; color:var(--primary);">GRBS Chart</h3>
                            <button class="btn-action" onclick="toggleForm('grbs-form')"><i class="fas fa-plus"></i> Add GRBS</button>
                        </div>
                        <div id="grbs-form" class="entry-form-card active">
                            <div class="form-grid">
                                <div class="form-group"><label>Date</label><input type="date" class="form-control" name="grbs_date"></div>
                                <div class="form-group"><label>Time</label><input type="time" class="form-control" name="grbs_time"></div>
                                <div class="form-group"><label>GRBS Value (mg/dL)</label><input type="text" class="form-control" name="grbs_value"></div>
                                <div class="form-group"><label>Nurse Name</label><input type="text" class="form-control" name="grbs_nurse"></div>
                            </div>
                            <button class="btn-action" data-chart-type="grbs_chart" style="margin-top:10px;"><i class="fas fa-save"></i> Save GRBS</button>
                        </div>
                        <div class="data-table-wrapper">
                            <table class="data-table">
                                <thead><tr><th>Date</th><th>Time</th><th>Value (mg/dL)</th><th>Nurse</th><th>Action</th></tr></thead>
                                <tbody id="grbs-tbody">
                                    <tr><td colspan="5" style="text-align:center;">No records</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="glass-card" style="margin-top: 20px;">
                        <div class="section-header" style="border:none; padding:0; margin-bottom:15px;">
                            <h3 style="margin:0; font-size:18px; color:var(--primary);">Nebulization Chart</h3>
                            <button class="btn-action" onclick="toggleForm('nebu-form')"><i class="fas fa-plus"></i> Add Nebulization</button>
                        </div>
                        <div id="nebu-form" class="entry-form-card active">
                            <div class="form-grid">
                                <div class="form-group"><label>Date</label><input type="date" class="form-control" name="nebu_date"></div>
                                <div class="form-group"><label>Time</label><input type="time" class="form-control" name="nebu_time"></div>
                                <div class="form-group"><label>Medicine</label><input type="text" class="form-control" name="nebu_drug"></div>
                                <div class="form-group"><label>Route</label><input type="text" class="form-control" name="nebu_route"></div>
                                <div class="form-group"><label>Frequency</label><input type="text" class="form-control" name="nebu_freq"></div>
                                <div class="form-group"><label>Remarks</label><input type="text" class="form-control" name="nebu_remarks"></div>
                                <div class="form-group"><label>Nurse</label><input type="text" class="form-control" name="nebu_nurse"></div>
                            </div>
                            <button class="btn-action" data-chart-type="nebulization_chart" style="margin-top:10px;"><i class="fas fa-save"></i> Save Nebulization</button>
                        </div>
                        <div class="data-table-wrapper">
                            <table class="data-table">
                                <thead><tr><th>Date</th><th>Time</th><th>Medicine</th><th>Route</th><th>Frequency</th><th>Remarks</th><th>Action</th></tr></thead>
                                <tbody id="nebu-tbody">
                                    <tr><td colspan="7" style="text-align:center;">No records</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Section: Dialysis Chart -->
                <div id="sec-dialysis" class="section-container">
                    <div class="section-header"><h2><i class="fas fa-filter"></i> Dialysis Chart</h2></div>
                    <div class="glass-card">
                        <div class="section-header" style="border:none; padding:0; margin-bottom:15px;">
                            <h3 style="margin:0; font-size:18px; color:var(--primary);">Dialysis Chart</h3>
                            <button class="btn-action" onclick="toggleForm('dialysis-form')"><i class="fas fa-plus"></i> Add Dialysis</button>
                        </div>
                        <div id="dialysis-form" class="entry-form-card active">
                            <div class="form-grid">
                                <div class="form-group"><label>Date</label><input type="date" class="form-control" name="dia_date"></div>
                                <div class="form-group"><label>Connecting Time</label><input type="time" class="form-control time-calc-start" name="dia_start" onchange="calculateDuration(this)"></div>
                                <div class="form-group"><label>Disconnecting Time</label><input type="time" class="form-control time-calc-end" name="dia_end" onchange="calculateDuration(this)"></div>
                                <div class="form-group"><label>Duration</label><input type="text" class="form-control time-calc-dur" name="dia_dur" readonly></div>
                                <div class="form-group"><label>Nurse Name</label><input type="text" class="form-control" name="dia_nurse"></div>
                            </div>
                            <button class="btn-action" data-chart-type="dialysis_chart" style="margin-top:10px;"><i class="fas fa-save"></i> Save Dialysis</button>
                        </div>
                        <div class="data-table-wrapper">
                            <table class="data-table">
                                <thead><tr><th>Date</th><th>Connecting Time</th><th>Disconnecting Time</th><th>Duration</th><th>Nurse Name</th><th>Action</th></tr></thead>
                                <tbody id="dialysis-tbody">
                                    <tr><td colspan="6" style="text-align:center;">No records</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Section: Oxygen Chart -->
                <div id="sec-oxygen" class="section-container">
                    <div class="section-header"><h2><i class="fas fa-wind"></i> Oxygen Chart</h2></div>
                    <div class="glass-card">
                        <div class="section-header" style="border:none; padding:0; margin-bottom:15px;">
                            <h3 style="margin:0; font-size:18px; color:var(--primary);">Oxygen Chart</h3>
                            <button class="btn-action" onclick="toggleForm('oxy-form')"><i class="fas fa-plus"></i> Add Oxygen</button>
                        </div>
                        <div id="oxy-form" class="entry-form-card active">
                            <div class="form-grid">
                                <div class="form-group"><label>Date</label><input type="date" class="form-control" name="oxy_date"></div>
                                <div class="form-group"><label>Connecting Time</label><input type="time" class="form-control time-calc-start" name="oxy_start" onchange="calculateDuration(this)"></div>
                                <div class="form-group"><label>Disconnecting Time</label><input type="time" class="form-control time-calc-end" name="oxy_end" onchange="calculateDuration(this)"></div>
                                <div class="form-group"><label>Duration</label><input type="text" class="form-control time-calc-dur" name="oxy_dur" readonly></div>
                                <div class="form-group"><label>Nurse Name</label><input type="text" class="form-control" name="oxy_nurse"></div>
                            </div>
                            <button class="btn-action" data-chart-type="oxygen_chart" style="margin-top:10px;"><i class="fas fa-save"></i> Save Oxygen</button>
                        </div>
                        <div class="data-table-wrapper">
                            <table class="data-table">
                                <thead><tr><th>Date</th><th>Connecting Time</th><th>Disconnecting Time</th><th>Duration</th><th>Nurse Name</th><th>Action</th></tr></thead>
                                <tbody id="oxy-tbody">
                                    <tr><td colspan="6" style="text-align:center;">No records</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Section: Ventilation Chart -->
                <div id="sec-vent" class="section-container">
                    <div class="section-header"><h2><i class="fas fa-lungs-virus"></i> Ventilation Chart</h2></div>
                    <div class="glass-card">
                        <div class="section-header" style="border:none; padding:0; margin-bottom:15px;">
                            <h3 style="margin:0; font-size:18px; color:var(--primary);">Ventilation Chart</h3>
                            <button class="btn-action" onclick="toggleForm('vent-form')"><i class="fas fa-plus"></i> Add Ventilation</button>
                        </div>
                        <div id="vent-form" class="entry-form-card active">
                            <div class="form-grid">
                                <div class="form-group"><label>Date</label><input type="date" class="form-control" name="vent_date"></div>
                                <div class="form-group"><label>Connecting Time</label><input type="time" class="form-control time-calc-start" name="vent_start" onchange="calculateDuration(this)"></div>
                                <div class="form-group"><label>Disconnecting Time</label><input type="time" class="form-control time-calc-end" name="vent_end" onchange="calculateDuration(this)"></div>
                                <div class="form-group"><label>Duration</label><input type="text" class="form-control time-calc-dur" name="vent_dur" readonly></div>
                                <div class="form-group"><label>Nurse Name</label><input type="text" class="form-control" name="vent_nurse"></div>
                            </div>
                            <button class="btn-action" data-chart-type="ventilation_chart" style="margin-top:10px;"><i class="fas fa-save"></i> Save Ventilator</button>
                        </div>
                        <div class="data-table-wrapper">
                            <table class="data-table">
                                <thead><tr><th>Date</th><th>Connecting Time</th><th>Disconnecting Time</th><th>Duration</th><th>Nurse Name</th><th>Action</th></tr></thead>
                                <tbody id="vent-tbody">
                                    <tr><td colspan="6" style="text-align:center;">No records</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Section: Nurses Record -->
                <div id="sec-nurse" class="section-container">
                    <div class="section-header"><h2><i class="fas fa-user-nurse"></i> Nurses Record</h2></div>
                    <div class="glass-card">
                        <div class="section-header" style="border:none; padding:0; margin-bottom:15px;">
                            <h3 style="margin:0; font-size:18px; color:var(--primary);">Nursing Notes</h3>
                            <button class="btn-action" onclick="toggleForm('nurse-form')"><i class="fas fa-plus"></i> Add Record</button>
                        </div>
                        <div id="nurse-form" class="entry-form-card active">
                        <div class="form-grid">
                            <div class="form-group"><label>Date</label><input type="date" class="form-control" name="nurse_date"></div>
                            <div class="form-group"><label>Time</label><input type="time" class="form-control" name="nurse_time"></div>
                            <div class="form-group"><label>Units</label><input type="text" class="form-control" name="nurse_units"></div>
                            <div class="form-group"><label>Signature</label><input type="text" class="form-control" name="nurse_sign"></div>
                            <div class="form-group" style="grid-column: span 2;"><label>Particulars</label><input type="text" class="form-control" name="nurse_part"></div>
                        </div>
                        <button class="btn-action" style="margin-top:10px;"><i class="fas fa-save"></i> Save</button>
                    </div>
                    <div class="data-table-wrapper">
                        <table class="data-table">
                            <thead><tr><th>Date</th><th>Time</th><th>Particulars</th><th>Units</th><th>Signature</th><th>Action</th></tr></thead>
                            <tbody id="nurse-tbody"><tr><td colspan="6" style="text-align:center;">No records</td></tr></tbody>
                        </table>
                    </div>
                    </div> <!-- end glass-card -->
                </div>

                <!-- Section: Blood Transfusion -->
                <div id="sec-blood" class="section-container">
                    <div class="section-header"><h2><i class="fas fa-tint"></i> Blood Transfusion Chart</h2></div>
                    <div class="glass-card">
                        <div class="section-header" style="border:none; padding:0; margin-bottom:15px;">
                            <h3 style="margin:0; font-size:18px; color:var(--primary);">Blood Transfusion</h3>
                            <button class="btn-action" onclick="toggleForm('blood-form')"><i class="fas fa-plus"></i> Add Record</button>
                        </div>
                        <div id="blood-form" class="entry-form-card active">
                        <div class="form-grid">
                            <div class="form-group"><label>Date</label><input type="date" class="form-control" name="trans_date"></div>
                            <div class="form-group"><label>Blood Group</label><input type="text" class="form-control" name="blood_group"></div>
                            <div class="form-group"><label>Bag Number</label><input type="text" class="form-control" name="bag_number"></div>
                            <div class="form-group"><label>Quantity (ml)</label><input type="number" class="form-control" name="quantity"></div>
                            <div class="form-group"><label>Time Started</label><input type="time" class="form-control" name="time_started"></div>
                            <div class="form-group"><label>Time Ended</label><input type="time" class="form-control" name="time_ended"></div>
                            <div class="form-group"><label>Vitals (BP/Pulse)</label><input type="text" class="form-control" name="vitals_during"></div>
                            <div class="form-group"><label>Nurse Signature</label><input type="text" class="form-control" name="nurse_sign"></div>
                        </div>
                        <button class="btn-action" style="margin-top:10px;"><i class="fas fa-save"></i> Save Transfusion</button>
                    </div>
                    <div class="data-table-wrapper">
                        <table class="data-table">
                            <thead><tr><th>Date</th><th>Bag No</th><th>Started</th><th>Ended</th><th>Vitals</th><th>Action</th></tr></thead>
                            <tbody id="blood-tbody">
                                <tr><td colspan="6" style="text-align:center;">No records</td></tr>
                            </tbody>
                        </table>
                    </div>
                    </div> <!-- end glass-card -->
                </div>

                <!-- Section: Support / Misc Charges -->
                <div id="sec-support" class="section-container">
                    <div class="section-header"><h2><i class="fas fa-file-invoice-dollar"></i> Support Services & Charges</h2></div>
                    <div class="glass-card">
                        <div class="subsection-title">Ambulance Charges</div>
                        <button class="btn-action" onclick="toggleForm('amb-form')" style="margin-bottom:15px;"><i class="fas fa-plus"></i> Add Ambulance</button>
                        <div id="amb-form" class="entry-form-card active">
                            <div class="form-grid">
                                <div class="form-group"><label>Date</label><input type="date" class="form-control" name="amb_date"></div>
                                <div class="form-group"><label>Driver Name</label><input type="text" class="form-control" name="amb_driver"></div>
                                <div class="form-group"><label>From</label><input type="text" class="form-control" name="amb_from"></div>
                                <div class="form-group"><label>To</label><input type="text" class="form-control" name="amb_to"></div>
                                <div class="form-group"><label>Kilometers</label><input type="number" class="form-control" name="amb_km"></div>
                                <div class="form-group"><label>Charge</label><input type="number" class="form-control" name="amb_charge"></div>
                            </div>
                            <button class="btn-action" data-chart-type="ambulance_charges" style="margin-top:10px;"><i class="fas fa-save"></i> Save</button>
                        </div>
                        <div class="data-table-wrapper">
                            <table class="data-table">
                                <thead><tr><th>Date</th><th>Driver</th><th>From</th><th>To</th><th>Kilometers</th><th>Charge</th><th>Action</th></tr></thead>
                                <tbody id="amb-tbody">
                                    <tr><td colspan="7" style="text-align:center;">No records</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="glass-card" style="margin-top: 20px;">
                        <div class="subsection-title">Miscellaneous Charges</div>
                        <button class="btn-action" onclick="toggleForm('misc-form')" style="margin-bottom:15px;"><i class="fas fa-plus"></i> Add Charge</button>
                        <div id="misc-form" class="entry-form-card active">
                            <div class="form-grid">
                                <div class="form-group"><label>Date</label><input type="date" class="form-control" name="misc_date"></div>
                                <div class="form-group"><label>Service Name</label><input type="text" class="form-control" name="misc_service"></div>
                                <div class="form-group"><label>Quantity</label><input type="number" class="form-control" name="misc_qty"></div>
                                <div class="form-group"><label>Unit Price</label><input type="number" class="form-control" name="misc_price"></div>
                            </div>
                            <button class="btn-action" data-chart-type="billing_items" style="margin-top:10px;"><i class="fas fa-save"></i> Save Charge</button>
                        </div>
                        <div class="data-table-wrapper">
                            <table class="data-table">
                                <thead><tr><th>Date</th><th>Service</th><th>Qty</th><th>Price</th><th>Action</th></tr></thead>
                                <tbody id="misc-tbody">
                                    <tr><td colspan="5" style="text-align:center;">No records</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div> <!-- End of content-panels -->



        </div> <!-- End of workspace-grid -->
            </div> <!-- end medication-content -->
        </div> <!-- end medication-layout -->


            </div> <!-- end medication-content -->
        </div> <!-- end medication-layout -->
 
            </div> <!-- end medication-content -->
        </div> <!-- end medication-layout -->
 
            </div> <!-- end medication-content -->
        </div> <!-- end medication-layout -->
 
            </div> <!-- end medication-content -->
        </div> <!-- end medication-layout -->
 
            </div> <!-- end medication-content -->
        </div> <!-- end medication-layout -->


            </div> <!-- end medication-content -->
        </div> <!-- end medication-layout -->
 
            </div> <!-- end medication-content -->
        </div> <!-- end medication-layout -->
 
            </div> <!-- end medication-content -->
        </div> <!-- end medication-layout -->
 
            </div> <!-- end medication-content -->
        </div> <!-- end medication-layout -->
 
            </div> <!-- end medication-content -->
        </div> <!-- end medication-layout -->
            </div> <!-- end medication-content -->
        </div> <!-- end medication-layout -->


            </div> <!-- end medication-content -->
        </div> <!-- end medication-layout -->
<?php else: ?>
        <div class="glass-card" style="text-align: center; padding: 60px 20px; margin-top: 30px;">
            <i class="fas fa-search-plus" style="font-size: 64px; color: var(--border); margin-bottom: 20px;"></i>
            <h2 style="color: var(--text-muted); font-size: 24px; font-weight: 800;">No Patient Selected</h2>
            <p style="color: var(--text-muted); font-size: 16px;">Please use the search bar above to find and select an admitted patient.</p>
        </div>
    <?php endif; ?>
    </div> <!-- End of content-wrapper -->
</div> <!-- End of main-layout -->

    <script>
        
        function activateMedTab(element) {
            document.querySelectorAll('.med-nav-item').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
        }

        function activateRibbon(btn) {
            document.querySelectorAll('.ribbon-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        }

        function switchSection(sectionId) {
            document.querySelectorAll('.section-container').forEach(el => el.classList.remove('active'));
            const section = document.getElementById(sectionId);
            if (section) section.classList.add('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function toggleForm(formId) {
            const form = document.getElementById(formId);
            if(form.classList.contains('active')) {
                form.classList.remove('active');
            } else {
                form.classList.add('active');
                
                // Clear the form since this is an "Add New" action
                form.querySelectorAll('input:not([type="hidden"]), select, textarea').forEach(input => {
                    if (input.type !== 'datetime-local' && input.type !== 'date' && input.type !== 'time') {
                        input.value = '';
                    }
                });
                let hidden = form.querySelector('input[name="entry_id"]');
                if (hidden) hidden.remove();
                
                const delBtn = form.querySelector('.btn-delete');
                if (delBtn) delBtn.style.display = 'none';
            }
        }



        const currentNurseName = "<?php echo addslashes($nurseName); ?>";
        function autoFillDateTime(container = document) {
            const now = new Date();
            // Format YYYY-MM-DD in local time
            const localDate = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
            // Format HH:MM in local time
            const localTime = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');

            container.querySelectorAll('input[type="date"]').forEach(input => {
                if (!input.value) input.value = localDate;
            });
            container.querySelectorAll('input[type="time"]').forEach(input => {
                if (!input.value) input.value = localTime;
            });
            // Also handle datetime-local
            const localDateTime = localDate + 'T' + localTime;
            container.querySelectorAll('input[type="datetime-local"]').forEach(input => {
                if (!input.value) input.value = localDateTime;
            });
            // Auto fill nurse names
            if (currentNurseName) {
                container.querySelectorAll('input[type="text"]').forEach(input => {
                    const name = input.getAttribute('name');
                    if (name && (name.includes('_nurse') || name.includes('_sign') || name === 'nurse')) {
                        if (!input.value) input.value = currentNurseName;
                    }
                });
            }
        }

        async function loadExistingRecords() {
            try {
                const response = await fetch(`api/get_clinical_records.php?patient_id=<?php echo $patientId; ?>&admission_id=<?php echo $admissionId; ?>`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const data = result.data;
                    
                    // Helper to populate singletons (Activity Record, OT, Misc Charges)
                    const populateSingleton = (chartType, containerId) => {
                        if (data[chartType] && data[chartType].length > 0) {
                            const record = data[chartType][0];
                            const container = document.getElementById(containerId);
                            if (!container) return;
                            
                            // Add hidden input for entry_id so save updates it
                            let hidden = container.querySelector(`input[name="entry_id"]`);
                            if (!hidden) {
                                hidden = document.createElement('input');
                                hidden.type = 'hidden';
                                hidden.name = 'entry_id';
                                // append to first form-grid or just container
                                const target = container.querySelector('.form-grid') || container;
                                target.appendChild(hidden);
                            }
                            hidden.value = record['entry_id'];
                            
                            // Populate inputs exactly how they were saved
                            const inputs = container.querySelectorAll('input:not([type="hidden"]), select, textarea');
                            let i = 0;
                            inputs.forEach(input => {
                                let key = input.getAttribute('name');
                                if (!key) {
                                    const label = input.previousElementSibling || input.closest('.form-group')?.querySelector('label');
                                    if (label) {
                                        key = label.innerText.toLowerCase().replace(/[^a-z0-9]/g, '_');
                                    } else if (input.placeholder) {
                                        key = input.placeholder.toLowerCase().replace(/[^a-z0-9]/g, '_');
                                    } else {
                                        key = 'field_' + i;
                                    }
                                }
                                if (record[key] !== undefined) {
                                    input.value = record[key];
                                }
                                i++;
                            });
                        }
                    };
                    
                    populateSingleton('nurses_record', 'sec-activity');
                    populateSingleton('procedures', 'sec-ot');
                    populateSingleton('billing_items', 'sec-support');
                    if(typeof calculateTotal === 'function') calculateTotal();
                    
                    // For logs (consultant visits)
                    const populateLogTable = (chartType, tbodyId, formId, renderRowHtmlFn) => {
                        if (data[chartType] && data[chartType].length > 0) {
                            const tbody = document.getElementById(tbodyId);
                            if (!tbody) return;
                            tbody.innerHTML = ''; 
                            
                            data[chartType].forEach(record => {
                                const tr = document.createElement('tr');
                                const encodedData = btoa(JSON.stringify(record));
                                tr.innerHTML = renderRowHtmlFn(record) + `<td><button class="btn-action" style="padding:4px 8px; font-size:12px;" onclick='editLogRecord("${encodedData}", "${formId}")'><i class="fas fa-edit"></i> Edit</button></td>`;
                                tbody.appendChild(tr);
                            });
                        }
                    };
                    populateLogTable('consultant_visits', 'visits-tbody', 'visit-form', r => `<td>${r.date || ''}</td><td>${r.time || ''}</td><td>${r.consultant || ''}</td><td>${r.shift || ''}</td>`);
                    
                    populateLogTable('cardiac_chart', 'cardiac-tbody', 'cardiac-form', r => {
                        const attLink = (r.attachment_file && r.attachment_file !== '') ? `<a href="${r.attachment_file}" target="_blank" style="color:var(--primary); font-weight:bold;"><i class="fas fa-paperclip"></i> View Report</a>` : 'No Attachment';
                        return `<td>${r.test_date || ''}</td><td>${r.test_time || ''}</td><td><strong>${r.test_type || ''}</strong></td><td>${r.remarks || ''}</td><td>${attLink}</td>`;
                    });
                    
                    populateLogTable('blood_transfusion_chart', 'blood-tbody', 'blood-form', r => `<td>${r.trans_date || r.date || ''}</td><td><strong>${r.bag_number || ''}</strong> (${r.blood_group || ''})</td><td>${r.time_started || ''}</td><td>${r.time_ended || ''}</td><td>${r.vitals_during || r.vitals_bp_pulse_ || ''}</td>`);
                    
                    populateLogTable('nurses_record', 'nurse-tbody', 'nurse-form', r => `<td>${r.nurse_date || r.date || ''}</td><td>${r.nurse_time || r.time || ''}</td><td>${r.nurse_part || r.particulars || ''}</td><td>${r.nurse_units || r.units || ''}</td><td>${r.nurse_sign || r.signature || ''}</td>`);
                    
                    if (data['billing_items'] && data['billing_items'].length > 0) {
                        const ambData = data['billing_items'].filter(r => r.amb_driver !== undefined);
                        const miscData = data['billing_items'].filter(r => r.misc_service !== undefined);
                        
                        const ambTbody = document.getElementById('amb-tbody');
                        if (ambTbody && ambData.length > 0) {
                            ambTbody.innerHTML = '';
                            ambData.forEach(record => {
                                const tr = document.createElement('tr');
                                const encodedData = btoa(JSON.stringify(record));
                                tr.innerHTML = `<td>${record.amb_date || ''}</td><td>${record.amb_driver || ''}</td><td>${record.amb_from || ''}</td><td>${record.amb_to || ''}</td><td>${record.amb_km || ''}</td><td>${record.amb_charge || ''}</td><td><button class="btn-action" style="padding:4px 8px; font-size:12px;" onclick='editLogRecord("${encodedData}", "amb-form")'><i class="fas fa-edit"></i> Edit</button></td>`;
                                ambTbody.appendChild(tr);
                            });
                        }
                        
                        const miscTbody = document.getElementById('misc-tbody');
                        if (miscTbody && miscData.length > 0) {
                            miscTbody.innerHTML = '';
                            miscData.forEach(record => {
                                const tr = document.createElement('tr');
                                const encodedData = btoa(JSON.stringify(record));
                                tr.innerHTML = `<td>${record.misc_date || ''}</td><td>${record.misc_service || ''}</td><td>${record.misc_qty || ''}</td><td>${record.misc_price || ''}</td><td><button class="btn-action" style="padding:4px 8px; font-size:12px;" onclick='editLogRecord("${encodedData}", "misc-form")'><i class="fas fa-edit"></i> Edit</button></td>`;
                                miscTbody.appendChild(tr);
                            });
                        }
                    }
                    
                    populateLogTable('grbs_chart', 'grbs-tbody', 'grbs-form', r => `<td>${r.grbs_date || ''}</td><td>${r.grbs_time || ''}</td><td>${r.grbs_value || ''}</td><td>${r.grbs_nurse || ''}</td>`);
                    
                    populateLogTable('nebulization_chart', 'nebu-tbody', 'nebu-form', r => `<td>${r.nebu_date || ''}</td><td>${r.nebu_time || ''}</td><td>${r.nebu_drug || ''}</td><td>${r.nebu_route || ''}</td><td>${r.nebu_freq || ''}</td><td>${r.nebu_remarks || ''}</td>`);
                    
                    populateLogTable('dialysis_chart', 'dialysis-tbody', 'dialysis-form', r => `<td>${r.dia_date || ''}</td><td>${r.dia_start || ''}</td><td>${r.dia_end || ''}</td><td>${r.dia_dur || ''}</td><td>${r.dia_nurse || ''}</td>`);
                    
                    populateLogTable('oxygen_chart', 'oxy-tbody', 'oxy-form', r => `<td>${r.oxy_date || ''}</td><td>${r.oxy_start || ''}</td><td>${r.oxy_end || ''}</td><td>${r.oxy_dur || ''}</td><td>${r.oxy_nurse || ''}</td>`);
                    
                    populateLogTable('ventilation_chart', 'vent-tbody', 'vent-form', r => `<td>${r.vent_date || ''}</td><td>${r.vent_start || ''}</td><td>${r.vent_end || ''}</td><td>${r.vent_dur || ''}</td><td>${r.vent_nurse || ''}</td>`);
                }
            } catch (err) {
                console.error("Failed to load records", err);
            }
        }
        
        function editLogRecord(encodedData, formId) {
            const record = JSON.parse(atob(encodedData));
            const form = document.getElementById(formId);
            if (!form) return;
            form.classList.add('active'); // show form
            
            let hidden = form.querySelector(`input[name="entry_id"]`);
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'entry_id';
                form.appendChild(hidden);
            }
            hidden.value = record['entry_id'];
            
            const inputs = form.querySelectorAll('input:not([type="hidden"]), select, textarea');
            let i = 0;
            inputs.forEach(input => {
                let key = input.getAttribute('name');
                if (!key) {
                    const label = input.previousElementSibling || input.closest('.form-group')?.querySelector('label');
                    if (label) {
                        key = label.innerText.toLowerCase().replace(/[^a-z0-9]/g, '_');
                    } else if (input.placeholder) {
                        key = input.placeholder.toLowerCase().replace(/[^a-z0-9]/g, '_');
                    } else {
                        key = 'field_' + i;
                    }
                }
                if (record[key] !== undefined) {
                    input.value = record[key];
                }
                i++;
            });
            
            const delBtn = form.querySelector('.btn-delete');
            if (delBtn) delBtn.style.display = 'inline-flex';
            
            window.scrollTo({ top: form.offsetTop - 50, behavior: 'smooth' });
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            autoFillDateTime();
            loadExistingRecords();
            
            // Initialize form buttons (Cancel & Delete)
            document.querySelectorAll('.entry-form-card').forEach(form => {
                const saveBtn = form.querySelector('.btn-action');
                if (saveBtn && !form.querySelector('.form-actions')) {
                    const actionContainer = document.createElement('div');
                    actionContainer.className = 'form-actions';
                    actionContainer.style.cssText = 'display:flex; gap:10px; margin-top:15px;';
                    
                    saveBtn.parentNode.insertBefore(actionContainer, saveBtn);
                    saveBtn.style.marginTop = '0';
                    actionContainer.appendChild(saveBtn);
                    
                    const cancelBtn = document.createElement('button');
                    cancelBtn.type = 'button';
                    cancelBtn.className = 'btn-action btn-cancel';
                    cancelBtn.style.background = '#6b7280';
                    cancelBtn.innerHTML = '<i class="fas fa-times"></i> Cancel';
                    cancelBtn.onclick = () => { form.classList.remove('active'); };
                    actionContainer.appendChild(cancelBtn);
                    
                    const delBtn = document.createElement('button');
                    delBtn.type = 'button';
                    delBtn.className = 'btn-action btn-delete';
                    delBtn.style.background = 'var(--danger)';
                    delBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                    delBtn.style.display = 'none'; // Hidden by default, only shown when editing
                    
                    delBtn.onclick = async () => {
                        if (confirm('Are you sure you want to delete this record?')) {
                            // Find chart_type
                            let sectionId = form.closest('.section-container')?.id || 'patient_header';
                            // Use typeMap which we need to duplicate here since it's scoped below... wait!
                            const map = {
                                'sec-activity': 'activity_record', 'sec-transfer': 'ward_transfer', 'sec-visits': 'consultant_visit',
                                'sec-ot': 'ot_procedure', 'sec-clinical': 'lab_chart', 'sec-radio': 'radiology_chart',
                                'sec-oxygen': 'oxygen_chart', 'sec-vent': 'ventilation_chart', 'sec-cardiac': 'cardiac_chart',
                                'sec-blood': 'blood_transfusion', 'sec-nurse': 'nurse_record', 'sec-support': 'support_charges',
                                'patient_header': 'patient_demographics'
                            };
                            let chartType = saveBtn.getAttribute('data-chart-type') || map[sectionId] || sectionId;
                            let entryId = form.querySelector('input[name="entry_id"]')?.value;
                            if (!entryId) return;
                            
                            const formData = new FormData();
                            formData.append('patient_id', '<?php echo $patientId; ?>');
                            formData.append('admission_id', '<?php echo $admissionId; ?>');
                            formData.append('chart_type', chartType);
                            formData.append('entry_id', entryId);
                            formData.append('action', 'delete');
                            
                            delBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>...';
                            delBtn.disabled = true;
                            
                            try {
                                const response = await fetch('api/save_clinical_record.php', { method: 'POST', body: formData });
                                const result = await response.json();
                                if (result.success) {
                                    showToast('Record deleted successfully');
                                    form.classList.remove('active');
                                    loadExistingRecords();
                                } else {
                                    showToast('Error: ' + result.message, true);
                                    delBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                                    delBtn.disabled = false;
                                }
                            } catch (e) {
                                showToast('Network Error', true);
                                delBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                                delBtn.disabled = false;
                            }
                        }
                    };
                    actionContainer.appendChild(delBtn);
                }
            });
            
            // Patient Autocomplete Logic
            const searchInput = document.getElementById('patient-search-input');
            const searchResults = document.getElementById('search-results');
            const selPatientId = document.getElementById('sel_patient_id');
            const selAdmissionId = document.getElementById('sel_admission_id');
            const form = document.getElementById('patient-select-form');

            if(searchInput && searchResults) {
                let timeout = null;
                searchInput.addEventListener('input', function() {
                    clearTimeout(timeout);
                    const q = this.value.trim();
                    if(q.length < 2) {
                        searchResults.style.display = 'none';
                        return;
                    }
                    timeout = setTimeout(() => {
                        fetch(`api/search_ipd_patient.php?q=${encodeURIComponent(q)}`)
                        .then(res => res.json())
                        .then(res => {
                            searchResults.innerHTML = '';
                            if(res.success && res.data.length > 0) {
                                res.data.forEach(p => {
                                    const div = document.createElement('div');
                                    div.className = 'search-result-item';
                                    div.innerHTML = `<strong>${p.first_name} ${p.last_name}</strong>
                                                     <span>PID: ${p.patient_id} | Ward: ${p.ward}</span>`;
                                    div.addEventListener('click', () => {
                                        selPatientId.value = p.patient_id;
                                        selAdmissionId.value = p.admission_id;
                                        form.submit();
                                    });
                                    searchResults.appendChild(div);
                                });
                                searchResults.style.display = 'block';
                            } else {
                                searchResults.innerHTML = '<div class="search-result-item"><span>No admitted patients found.</span></div>';
                                searchResults.style.display = 'block';
                            }
                        });
                    }, 300);
                });

                // Hide results when clicking outside
                document.addEventListener('click', function(e) {
                    if(!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                        searchResults.style.display = 'none';
                    }
                });
            }
        });

        function calculateTotal() {
            let total = 0;
            document.querySelectorAll('.package-amount').forEach(input => {
                if(input.value) total += parseFloat(input.value);
            });
            const totalField = document.getElementById('package-total');
            if (totalField) {
                totalField.value = total;
            }
        }

        function calculateDuration(element) {
            let container = element.closest('.entry-form-card');
            if(!container) container = element.closest('tr');
            if(!container) return;
            const startInput = container.querySelector('.time-calc-start');
            const endInput = container.querySelector('.time-calc-end');
            const durInput = container.querySelector('.time-calc-dur');
            
            if (startInput.value && endInput.value) {
                const start = new Date(`1970-01-01T${startInput.value}:00`);
                let end = new Date(`1970-01-01T${endInput.value}:00`);
                
                if (end < start) {
                    end.setDate(end.getDate() + 1); // Crosses midnight
                }
                
                const diffMs = end - start;
                const diffHrs = Math.floor(diffMs / 3600000);
                const diffMins = Math.round((diffMs % 3600000) / 60000);
                
                durInput.value = `${diffHrs}h ${diffMins}m`;
            } else {
                durInput.value = '';
            }
        }
        
        function addTableRow(tbodyId) {
            const tbody = document.getElementById(tbodyId);
            if (!tbody) return;
            const firstRow = tbody.querySelector('tr');
            if (firstRow) {
                const newRow = firstRow.cloneNode(true);
                newRow.querySelectorAll('input').forEach(input => input.value = '');
                tbody.appendChild(newRow);
            }
        }

        // --- GLOBAL SAVE LOGIC ---
        function showToast(msg, isError = false) {
            let toast = document.getElementById('global-toast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'global-toast';
                toast.style.cssText = 'visibility:hidden; min-width:250px; background-color:#333; color:#fff; text-align:center; border-radius:5px; padding:16px; position:fixed; z-index:9999; right:30px; bottom:30px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); transition: opacity 0.3s, transform 0.3s; transform: translateY(20px); opacity: 0; font-weight: 600;';
                document.body.appendChild(toast);
            }
            toast.innerText = msg;
            toast.style.backgroundColor = isError ? 'var(--danger)' : '#10b981'; // green
            toast.style.visibility = 'visible';
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
            
            setTimeout(() => { 
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(20px)';
                setTimeout(() => { toast.style.visibility = 'hidden'; }, 300);
            }, 3000);
        }

        // Attach click listener to all buttons that contain a save icon
        document.addEventListener('click', async function(e) {
            const btn = e.target.closest('.btn-action');
            if (!btn) return;
            
            // Check if it's a save/update button
            if (!btn.innerHTML.includes('fa-save') && !btn.innerText.toLowerCase().includes('update')) return;
            
            // Don't save if it's just the "Add/Log" toggle button (which usually has fa-plus or is just toggling)
            if (btn.innerHTML.includes('fa-plus') && !btn.innerHTML.includes('fa-save')) return;

            // Find the closest container with inputs
            let container = btn.closest('.entry-form-card');
            if (!container) container = btn.closest('.glass-card');
            if (!container) return; 
            
            if (btn.closest('tr') && !btn.closest('.entry-form-card')) {
                container = btn.closest('tr');
            }

            const section = btn.closest('.section-container') || btn.closest('.patient-header');
            let sectionId = section ? section.id : 'patient_header';
            
            // IMPORTANT: If this section uses a singleton chart type that spans multiple glass cards,
            // we MUST serialize the ENTIRE section, otherwise we overwrite the JSON and lose data from the other tables.
            if (sectionId === 'sec-support' && !btn.closest('.entry-form-card')) {
                container = section;
            }
            
            // Map section to chart type
            const typeMap = {
                'sec-activity': 'activity_record',
                'sec-transfer': 'ward_transfer',
                'sec-visits': 'consultant_visit',
                'sec-ot': 'ot_procedure',
                'sec-clinical': 'lab_chart',
                'sec-radio': 'radiology_chart',
                'sec-oxygen': 'oxygen_chart',
                'sec-vent': 'ventilation_chart',
                'sec-cardiac': 'cardiac_chart',
                'sec-blood': 'blood_transfusion',
                'sec-nurse': 'nurse_record',
                'sec-support': 'support_charges',
                'patient_header': 'patient_demographics'
            };
            
            // Get chart type from button attribute OR fallback to type map
            const chartType = btn.getAttribute('data-chart-type') || typeMap[sectionId] || sectionId;

            // Gather data
            const inputs = container.querySelectorAll('input, select, textarea');
            if (inputs.length === 0) return;

            const formData = new FormData();
            formData.append('patient_id', '<?php echo $patientId; ?>');
            formData.append('admission_id', '<?php echo $admissionId; ?>');
            formData.append('chart_type', chartType);
            
            let i = 0;
            inputs.forEach(input => {
                let key = input.getAttribute('name');
                if (!key) {
                    const label = input.previousElementSibling || input.closest('.form-group')?.querySelector('label');
                    if (label) {
                        key = label.innerText.toLowerCase().replace(/[^a-z0-9]/g, '_');
                    } else if (input.placeholder) {
                        key = input.placeholder.toLowerCase().replace(/[^a-z0-9]/g, '_');
                    } else {
                        key = 'field_' + i;
                    }
                }
                
                if (input.type === 'file') {
                    if (input.files.length > 0) {
                        formData.append(key, input.files[0]);
                    }
                } else {
                    formData.append(key, input.value);
                }
                i++;
            });

            // Loading state
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            btn.disabled = true;

            try {
                const response = await fetch('api/save_clinical_record.php', {
                    method: 'POST',
                    body: formData
                    // DO NOT SET Content-Type manually, fetch will set it correctly with multipart boundary for files!
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast(result.message);
                    
                    // Clear inputs if it was an "Add New" form card
                    if (container.classList.contains('entry-form-card')) {
                        inputs.forEach(input => {
                            if (input.type !== 'datetime-local' && input.type !== 'date') {
                                input.value = '';
                            }
                        });
                        container.classList.remove('active');
                    }
                    
                    // Reload data to reflect edits/additions immediately
                    if (typeof loadExistingRecords === 'function') {
                        loadExistingRecords();
                    }
                } else {
                    showToast('Error: ' + result.message, true);
                }
            } catch (err) {
                console.error(err);
                showToast('Network Error: Could not save data.', true);
            } finally {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>
