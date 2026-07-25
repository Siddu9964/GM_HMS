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
            --bg-cream: #F3EFE6;
            --white: #FFFFFF;
            --text-main: #1B1B1B;
            --text-muted: #5e646a;
            --border: #D9D3C7;
            --success: #2E7D32;
            --warning: #FF9800;
            --danger: #D32F2F;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 255, 255, 0.6);
            --shadow-soft: 0 10px 30px rgba(31,107,74,.08);
            --shadow-hover: 0 18px 40px rgba(31,107,74,.12);
            --radius-lg: 24px;
            --radius-md: 16px;
            --radius-sm: 12px;
            --transition: all 250ms cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Hide number input arrows/spinners */
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="number"] { -moz-appearance: textfield; }

        * { font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; }
        body { background-color: var(--bg-cream); color: var(--text-main); margin: 0; padding: 0; padding-bottom: 80px; }
        
        /* Master Layout Container (With Sidebar Margin) */
        
        /* Glass Card Base */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-soft);
            transition: var(--transition);
            padding: 25px;
        }
        .glass-card:hover { box-shadow: var(--shadow-hover); }

        /* Patient Header */
        .patient-header {
            position: sticky; top: 15px; z-index: 100;
            display: grid; grid-template-columns: auto 1fr auto; gap: 25px; align-items: center;
        }
        .patient-avatar { width: 80px; height: 80px; border-radius: 50%; background: var(--primary); color: var(--white); display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 800; box-shadow: var(--shadow-soft); }
        .patient-details h1 { font-weight: 800; font-size: 24px; margin: 0 0 10px 0; color: var(--primary); }
        .patient-meta { display: flex; gap: 10px; flex-wrap: wrap; }
        
        /* Modern Chips */
        .chip {
            font-size: 13px; font-weight: 700; color: var(--primary);
            background: rgba(31,107,74,0.1); padding: 6px 14px;
            border-radius: 50px; border: 1px solid rgba(31,107,74,0.2);
            display: inline-flex; align-items: center; gap: 6px;
        }
        .chip span { color: var(--text-muted); font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Floating Search */
        .floating-search { display: flex; align-items: center; background: var(--white); border-radius: 50px; padding: 12px 24px; width: 350px; border: 1px solid var(--border); box-shadow: var(--shadow-soft); }
        .floating-search input { border: none; outline: none; width: 100%; padding-left: 10px; font-size: 15px; font-weight: 500; background: transparent; }

        /* Summary Cards Row */
        .summary-row { display: flex; gap: 15px; overflow-x: auto; padding-bottom: 10px; scrollbar-width: none; }
        .summary-row::-webkit-scrollbar { display: none; }
        .summary-card {
            flex: 0 0 auto; width: 180px; display: flex; flex-direction: column; gap: 8px;
            padding: 15px 20px; border-radius: var(--radius-md); text-align: left;
        }
        .summary-card i { font-size: 20px; color: var(--primary); }
        .summary-card .count { font-size: 28px; font-weight: 800; color: var(--text-main); line-height: 1; }
        .summary-card .label { font-size: 13px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; }

        /* Quick Action Ribbon */
        .quick-action-ribbon { 
            display: flex; 
            flex-wrap: nowrap; 
            gap: 10px; 
            padding: 15px 5px; 
            border-bottom: 2px solid var(--border); 
            margin-bottom: 15px; 
            overflow-x: auto; 
            -webkit-overflow-scrolling: touch; 
            scrollbar-width: none; 
        }
        .quick-action-ribbon::-webkit-scrollbar { display: none; }
        .ribbon-btn {
            background: var(--white); color: var(--text-muted); border: 1px solid var(--border);
            padding: 12px 20px; border-radius: 50px; font-weight: 700; font-size: 14px;
            cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
            transition: var(--transition); white-space: nowrap; box-shadow: 0 4px 10px rgba(0,0,0,0.02);
            flex: 0 0 auto; justify-content: center; min-height: 44px; /* Touch target size */
        }
        .ribbon-btn:hover { background: var(--primary-light); color: var(--white); border-color: var(--primary-light); transform: translateY(-2px); box-shadow: var(--shadow-hover); }
        .ribbon-btn.active { background: var(--primary); color: var(--white); border-color: var(--primary); box-shadow: var(--shadow-soft); }

        /* Dynamic Workspace */
        .workspace-grid { display: block; }
        
        .section-container { display: none; animation: fadeIn 0.4s ease-out; }
        .section-container.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid var(--border); flex-wrap: wrap; gap: 10px; }
        .section-header h2 { font-size: 22px; font-weight: 800; color: var(--primary); margin: 0; }
        
        /* Buttons */
        .btn-action { background: var(--primary); color: var(--white); border: none; padding: 12px 24px; border-radius: var(--radius-sm); font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: var(--transition); box-shadow: 0 4px 12px rgba(31,107,74,0.2); min-height: 44px; }
        .btn-action:hover { background: var(--primary-light); transform: translateY(-2px); box-shadow: 0 6px 16px rgba(31,107,74,0.3); }

        /* Forms */
        .entry-form-card { background: var(--white); border: 1px dashed var(--primary); padding: 25px; border-radius: var(--radius-lg); margin-bottom: 25px; display: none; }
        .entry-form-card.active { display: block; animation: fadeIn 0.3s; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 15px; }
        .form-group label { font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control { padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--border); background: var(--bg-cream); font-size: 15px; font-weight: 500; transition: var(--transition); min-height: 48px; }
        .form-control:focus { outline: none; border-color: var(--primary); background: var(--white); box-shadow: 0 0 0 4px rgba(31,107,74,0.15); }

        /* Tables */
        .data-table-wrapper { background: var(--white); border-radius: var(--radius-md); overflow-x: auto; border: 1px solid var(--border); margin-bottom: 25px; -webkit-overflow-scrolling: touch; }
        .data-table { width: 100%; border-collapse: collapse; min-width: 600px; }
        .data-table th, .data-table td { padding: 16px; text-align: left; font-size: 15px; border-bottom: 1px solid var(--border); }
        .data-table th { background: var(--bg-cream); font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px; white-space: nowrap; }
        .data-table tr:hover td { background: rgba(243, 239, 230, 0.5); }
        
        .subsection-title { font-size: 18px; font-weight: 800; color: var(--primary); margin: 30px 0 20px 0; border-bottom: 2px solid var(--border); padding-bottom: 10px; }

        /* Right Timeline Panel */
        .timeline-panel { background: var(--white); border-radius: var(--radius-lg); padding: 25px; border: 1px solid var(--border); box-shadow: var(--shadow-soft); position: sticky; top: 150px; }
        .timeline-panel h3 { font-size: 18px; font-weight: 800; color: var(--primary); margin-top: 0; margin-bottom: 20px; }
        .timeline { padding-left: 15px; border-left: 2px solid rgba(31,107,74,0.2); }
        .timeline-item { position: relative; margin-bottom: 20px; padding: 15px; background: var(--bg-cream); border-radius: var(--radius-md); border: 1px solid var(--border); }
        .timeline-item::before { content: ''; position: absolute; left: -22px; top: 20px; width: 12px; height: 12px; border-radius: 50%; background: var(--primary); border: 2px solid var(--white); }
        .timeline-item .time { font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 5px; display: block; }
        .timeline-item .event { font-size: 14px; font-weight: 600; color: var(--text-main); }

        /* Smart Bottom Toolbar */
        .bottom-toolbar {
            position: fixed; bottom: 0; left: 0; width: 100%; background: rgba(255,255,255,0.9);
            backdrop-filter: blur(20px); border-top: 1px solid var(--border);
            padding: 15px 40px; display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 -10px 30px rgba(0,0,0,0.05); z-index: 1000;
        }
        .toolbar-status { display: flex; gap: 20px; align-items: center; }
        .status-pill { font-size: 13px; font-weight: 700; color: var(--text-muted); background: var(--bg-cream); padding: 8px 16px; border-radius: 50px; }
        .status-pill.unsaved { color: var(--warning); background: rgba(255,152,0,0.1); }
        
        .toolbar-actions { display: flex; gap: 15px; }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .patient-header { grid-template-columns: 1fr; text-align: center; justify-items: center; gap: 15px; }
            .patient-details h1 { font-size: 20px; }
            .patient-details > div { justify-content: center; }
            .patient-meta { justify-content: center; }
            .patient-avatar { width: 60px; height: 60px; font-size: 20px; }
            .entry-form-card { padding: 15px; }
            .bottom-toolbar { flex-direction: column; padding: 15px; gap: 10px; }
            .content-wrapper { padding: 10px; }
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
    <div class="content-wrapper" style="flex: 1; display: flex; flex-direction: column; overflow-x: hidden;">
        <!-- Header -->
        <?php 
        $pageTitle = 'Medications';
        include 'includes/nurse_navbar.php'; 
        ?>

        <?php if ($patientId && $patientId !== 'undefined'): ?>

        <!-- Sticky Patient Header -->
        <div class="glass-card patient-header">
            <div class="patient-avatar"><?php echo htmlspecialchars($initials); ?></div>
            <div class="patient-details">
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 8px;">
                    <h1><?php echo htmlspecialchars($patientName); ?></h1>
                    <div class="chip" style="background: rgba(255,152,0,0.1); color: var(--warning); border-color: rgba(255,152,0,0.2);"><span>Status</span> ACTIVE TREATMENT</div>
                </div>
                <div class="patient-meta">
                    <div class="chip"><span>PID</span> <?php echo htmlspecialchars($patientPID); ?></div>
                    <div class="chip"><span>Admission</span> <?php echo htmlspecialchars($patientIP); ?></div>
                    <div class="chip"><span>Ward</span> <?php echo htmlspecialchars($patientLocation); ?></div>
                    <div class="chip"><span>Doctor</span> <?php echo htmlspecialchars($patientConsultant); ?></div>
                    <div class="chip"><span>Blood Group</span> <?php echo htmlspecialchars($patientBlood); ?></div>
                    <div class="chip"><span>Age/Sex</span> <?php echo htmlspecialchars($patientAgeSex); ?></div>
                    <div class="chip"><span>Allergy</span> ❌ NKA</div>
                </div>
            </div>
            
            <div class="floating-search" style="display:none;">
                <!-- Hidden since we restored the main top-navbar search -->
                <i class="fas fa-search" style="color:var(--text-muted);"></i>
                <input type="text" placeholder="Search entire patient record...">
            </div>
        </div>

        <!-- Quick Action Ribbon (Horizontal Nav) -->
        <div class="quick-action-ribbon">
            <button class="ribbon-btn active" onclick="switchSection('sec-activity'); activateRibbon(this)"><i class="fas fa-file-medical"></i> Activity</button>
            <button class="ribbon-btn" onclick="switchSection('sec-transfer'); activateRibbon(this)"><i class="fas fa-exchange-alt"></i> Transfer</button>
            <button class="ribbon-btn" onclick="switchSection('sec-visits'); activateRibbon(this)"><i class="fas fa-stethoscope"></i> Doctor Visits</button>
            <button class="ribbon-btn" onclick="switchSection('sec-clinical'); activateRibbon(this)"><i class="fas fa-heartbeat"></i> Clinical Chart</button>
            <button class="ribbon-btn" onclick="switchSection('sec-dialysis'); activateRibbon(this)"><i class="fas fa-filter"></i> Dialysis</button>
            <button class="ribbon-btn" onclick="switchSection('sec-oxygen'); activateRibbon(this)"><i class="fas fa-wind"></i> Oxygen</button>
            <button class="ribbon-btn" onclick="switchSection('sec-vent'); activateRibbon(this)"><i class="fas fa-lungs"></i> Ventilator</button>
            <button class="ribbon-btn" onclick="switchSection('sec-blood'); activateRibbon(this)"><i class="fas fa-tint"></i> Blood</button>
            <button class="ribbon-btn" onclick="switchSection('sec-nurse'); activateRibbon(this)"><i class="fas fa-user-nurse"></i> Nursing</button>
            <button class="ribbon-btn" onclick="switchSection('sec-support'); activateRibbon(this)"><i class="fas fa-file-invoice-dollar"></i> Support</button>
        </div>

        <!-- Dynamic Workspace + Right Timeline -->
        <div class="workspace-grid">
            <div class="content-panels">
                
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
                    <div id="transfer-form" class="entry-form-card">
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
                    <div id="visit-form" class="entry-form-card">
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
                        <div id="grbs-form" class="entry-form-card">
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
                        <div id="nebu-form" class="entry-form-card">
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
                        <div id="dialysis-form" class="entry-form-card">
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
                        <div id="oxy-form" class="entry-form-card">
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
                        <div id="vent-form" class="entry-form-card">
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
                    <div class="section-header"><h2><i class="fas fa-user-nurse"></i> Nurses Record</h2><button class="btn-action" onclick="toggleForm('nurse-form')"><i class="fas fa-plus"></i> Add Record</button></div>
                    <div id="nurse-form" class="entry-form-card">
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
                </div>


                <!-- Section: Blood Transfusion -->
                <div id="sec-blood" class="section-container">
                    <div class="section-header"><h2><i class="fas fa-tint"></i> Blood Transfusion Chart</h2><button class="btn-action" onclick="toggleForm('blood-form')"><i class="fas fa-plus"></i> Add Record</button></div>
                    <div id="blood-form" class="entry-form-card">
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
                </div>

                <!-- Section: Support / Misc Charges -->
                <div id="sec-support" class="section-container">
                    <div class="section-header"><h2><i class="fas fa-file-invoice-dollar"></i> Support Services & Charges</h2></div>
                    <div class="glass-card">
                        <div class="subsection-title">Ambulance Charges</div>
                        <button class="btn-action" onclick="toggleForm('amb-form')" style="margin-bottom:15px;"><i class="fas fa-plus"></i> Add Ambulance</button>
                        <div id="amb-form" class="entry-form-card">
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
                        <div id="misc-form" class="entry-form-card">
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
            const row = element.closest('tr');
            const startInput = row.querySelector('.time-calc-start');
            const endInput = row.querySelector('.time-calc-end');
            const durInput = row.querySelector('.time-calc-dur');
            
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
