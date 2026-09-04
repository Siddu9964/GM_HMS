<?php
session_start();

// Allow all nurse roles and admins
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Nurse', 'Superintendent_Nurse', 'Superintendent Nurse', 'Nursing_Superintendent', 'admin', 'Admin', 'Head Nurse'])) {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['branch'])) {
    $_SESSION['branch'] = strtolower(trim($_GET['branch']));
    $_SESSION['hospital_branch'] = $_SESSION['branch'];
} elseif (!isset($_SESSION['branch']) && !isset($_SESSION['hospital_branch'])) {
    $_SESSION['branch'] = 'basaveshwranagara';
    $_SESSION['hospital_branch'] = 'basaveshwranagara';
}

$nurseId   = $_SESSION['user_id']   ?? null;
$nurseName = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Nurse');
require_once __DIR__ . '/../core/Autoloader.php';

$allFloors = [];
$allWards = [];
$assignedPatients = [];
$nurseWard = null;
$preselectedPatient = null;

try {
    $db = GM_HMS\Database\SecureDatabase::getInstance();
    $conn = $db->getConnection();
    
    // 1. Fetch Floors
    $resFloors = $conn->query("SELECT DISTINCT floor_number, floor_name FROM hospital_beds WHERE floor_name IS NOT NULL AND floor_name != '' ORDER BY floor_number ASC, floor_name ASC");
    if ($resFloors) {
        while ($r = $resFloors->fetch_assoc()) $allFloors[] = $r;
    }

    // 2. Fetch Wards
    $resWards = $conn->query("SELECT DISTINCT ward_name FROM hospital_beds WHERE ward_name IS NOT NULL AND ward_name != '' ORDER BY ward_name ASC");
    if ($resWards) {
        while ($r = $resWards->fetch_assoc()) {
            $allWards[] = $r['ward_name'];
        }
    }
    
    // 3. Fetch Assigned Shift & Patients
    require_once __DIR__ . '/includes/nurse_auth_helper.php';
    $roleId = $_SESSION['role_id'] ?? $_SESSION['user_id'] ?? null;
    $shiftModel = new \GM_HMS\Models\NurseShiftModel();

    if ($nurseId) {
        $nurseWard = getCurrentNurseWard($conn, $nurseId);
        $assignedPatients = $shiftModel->getAssignedPatientsRedesigned($nurseId, $roleId, $nurseWard);
    }

    // If no ward assigned or assignedPatients is empty, get all currently active inpatients
    if (empty($assignedPatients)) {
        $assignedPatients = $shiftModel->getAssignedPatientsRedesigned(null, $roleId, null);
    }

    // 4. Check if patient pre-selected via URL
    $paramPatientId = trim($_GET['patient_id'] ?? '');
    $paramAdmissionId = trim($_GET['admission_id'] ?? '');

    if (!empty($paramPatientId)) {
        foreach ($assignedPatients as $p) {
            if ($p['patient_id'] == $paramPatientId && (empty($paramAdmissionId) || $p['admission_id'] == $paramAdmissionId)) {
                $preselectedPatient = $p;
                break;
            }
        }
        // If not in assigned list, query directly
        if (!$preselectedPatient) {
            $ptStmt = $conn->prepare("
                SELECT DISTINCT 
                    p.patient_id, p.first_name, p.last_name, p.age, p.sex, p.blood_group,
                    ia.admission_id, ia.admission_date, ia.diagnosis, ia.bed_id,
                    ia.room_no as room_number, ia.room_name,
                    COALESCE(b.room_type, ia.room_type, ia.ward_name) as room_type,
                    COALESCE(b.floor_name, ia.floor_name) as floor_name,
                    COALESCE(b.ward_name, ia.ward_name) as ward_name,
                    COALESCE(b.bed_number, CAST(ia.bed_id AS CHAR)) as bed_number,
                    d.full_name as doctor_name
                FROM ipd_admissions ia
                INNER JOIN patient p ON ia.patient_id = p.patient_id
                LEFT JOIN hospital_beds b ON ia.bed_id = b.sl_no
                LEFT JOIN doctors d ON ia.admitting_doctor_id = d.doctor_id
                WHERE ia.patient_id = ? AND ia.status IN ('Active', 'Admitted')
                LIMIT 1
            ");
            if ($ptStmt) {
                $ptStmt->bind_param("s", $paramPatientId);
                $ptStmt->execute();
                $preselectedPatient = $ptStmt->get_result()->fetch_assoc();
                $ptStmt->close();
            }
        }
    }
} catch(Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Bed Transfer - GM HMS</title>
<link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
/* ── GM HMS Signature 2-Color Theme System (#f3efe6 & #1f6b4a) ── */
:root {
    --gm-bg: #f3efe6;
    --gm-bg-card: #ffffff;
    --gm-primary: #1f6b4a;
    --gm-primary-dark: #144d34;
    --gm-primary-light: rgba(31, 107, 74, 0.08);
    --gm-primary-mid: rgba(31, 107, 74, 0.15);
    --gm-border: rgba(31, 107, 74, 0.22);
    --gm-border-strong: #1f6b4a;
    --gm-text: #1f6b4a;
    --gm-text-body: #2c3e35;
    --gm-text-muted: #527967;
    --gm-sidebar-w: 185px;
}

html, body {
    margin: 0;
    padding: 0;
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    background-color: var(--gm-bg);
    color: var(--gm-text-body);
    min-height: 100vh;
    box-sizing: border-box;
    -webkit-font-smoothing: antialiased;
}

*, *::before, *::after {
    box-sizing: border-box;
}

.main-layout {
    display: flex;
    min-height: 100vh;
    width: 100%;
    position: relative;
    overflow-x: hidden;
}

.content-wrapper {
    flex: 1;
    min-width: 0;
    padding: 20px 24px;
    background-color: var(--gm-bg);
    margin-left: var(--gm-sidebar-w, 185px);
    width: calc(100% - var(--gm-sidebar-w, 185px));
    box-sizing: border-box;
    transition: margin-left 0.25s ease, width 0.25s ease, padding 0.2s ease;
}

/* Patient Dashboard & Selection View */
.nopt-dashboard {
    background: #ffffff;
    border-radius: 14px;
    border: 1.5px solid var(--gm-border);
    padding: 22px 24px;
    box-shadow: 0 4px 16px rgba(31, 107, 74, 0.06);
    margin-bottom: 24px;
}

.dash-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1.5px solid var(--gm-border);
}

.search-box-wrap {
    position: relative;
    width: 100%;
    max-width: 380px;
}

.search-box-wrap i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gm-primary);
}

.search-box-wrap input {
    width: 100%;
    padding: 10px 14px 10px 42px;
    border-radius: 10px;
    border: 1.5px solid var(--gm-border);
    font-size: 0.88rem;
    outline: none;
    background: var(--gm-bg);
    color: var(--gm-primary);
    font-weight: 600;
    transition: all 0.2s ease;
}

.search-box-wrap input:focus {
    background: #ffffff;
    border-color: var(--gm-primary);
    box-shadow: 0 0 0 3px var(--gm-primary-light);
}

.dash-patient-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}

.dash-patient-card {
    background: #ffffff;
    border: 1.5px solid var(--gm-border);
    border-radius: 12px;
    padding: 16px 18px;
    cursor: pointer;
    transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 6px rgba(31, 107, 74, 0.05);
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.dash-patient-card:hover {
    border-color: var(--gm-primary);
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(31, 107, 74, 0.16);
    background: #ffffff;
}

.dash-patient-name {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--gm-primary);
    display: flex;
    align-items: center;
    gap: 8px;
}

.dash-chips-row {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.chip-mini {
    font-size: 0.75rem;
    font-weight: 700;
    padding: 4px 9px;
    background: var(--gm-primary-light);
    color: var(--gm-primary);
    border: 1px solid var(--gm-border);
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

/* Sticky Active Patient Banner */
.pt-banner {
    background: var(--gm-primary);
    color: #f3efe6;
    padding: 14px 20px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    box-shadow: 0 6px 20px rgba(31, 107, 74, 0.28);
    margin-bottom: 20px;
    position: sticky;
    top: 10px;
    z-index: 90;
}

.pt-banner-av {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: rgba(243, 239, 230, 0.2);
    border: 1.5px solid rgba(243, 239, 230, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    font-weight: 800;
    color: #f3efe6;
    flex-shrink: 0;
}

.pt-banner-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}

.pt-banner-nm {
    font-size: 1.2rem;
    font-weight: 800;
    color: #f3efe6;
    display: flex;
    align-items: center;
    gap: 10px;
}

.pt-banner-chips {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.ptchip {
    font-size: 0.76rem;
    background: rgba(243, 239, 230, 0.15);
    padding: 3px 9px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: #f3efe6;
}

.ptchip strong {
    color: #ffffff;
}

.pt-banner-ac {
    margin-left: auto;
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}

.pba {
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 700;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.2s;
    white-space: nowrap;
    min-height: 38px;
}
.pba.sec { background: transparent; color: #f3efe6; border: 1px solid rgba(243, 239, 230, 0.3); }
.pba.sec:hover { background: rgba(243, 239, 230, 0.2); }
.pba.ksheet { background: #f3efe6; color: var(--gm-primary); }
.pba.ksheet:hover { background: #ffffff; transform: translateY(-1px); }

/* Main Card */
.card-new {
    background: #ffffff;
    border: 1.5px solid var(--gm-border);
    border-radius: 14px;
    padding: 18px 20px;
    box-shadow: 0 2px 10px rgba(31, 107, 74, 0.04);
    display: flex;
    flex-direction: column;
}

.card-title-new {
    color: var(--gm-primary);
    font-weight: 800;
    font-size: 1.05rem;
    margin-bottom: 14px;
    padding-bottom: 10px;
    border-bottom: 1.5px solid var(--gm-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.split-card {
    display: flex;
    flex-direction: column;
    gap: 16px;
    flex: 1;
}

.split-left {
    width: 100%;
}

.split-right {
    width: 100%;
    border-top: 1.5px dashed var(--gm-border);
    padding-top: 14px;
    margin-top: 4px;
}

.ht-title {
    font-size: 0.82rem;
    font-weight: 800;
    color: var(--gm-primary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Form Styles */
.fg {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
    align-items: end;
}

.fmg {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.fmg label {
    font-size: 0.74rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: var(--gm-primary);
}

.fmg input, .fmg select, .fmg textarea {
    padding: 9px 12px;
    border: 1.5px solid var(--gm-border);
    border-radius: 8px;
    font-size: 0.86rem;
    background: var(--gm-bg);
    color: var(--gm-primary);
    font-weight: 600;
    outline: none;
    font-family: inherit;
    width: 100%;
    transition: all 0.2s ease;
}

.fmg input:focus, .fmg select:focus, .fmg textarea:focus {
    background: #ffffff;
    border-color: var(--gm-primary);
    box-shadow: 0 0 0 3px var(--gm-primary-light);
}

.fmg input[readonly] {
    background: rgba(31, 107, 74, 0.06) !important;
    color: var(--gm-primary) !important;
    border-color: rgba(31, 107, 74, 0.25) !important;
    cursor: not-allowed !important;
    font-weight: 700 !important;
    user-select: none;
}

.btn-sv-out {
    background: var(--gm-primary-light);
    border: 1.5px solid var(--gm-primary);
    color: var(--gm-primary);
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    margin-top: 12px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s ease;
    min-height: 38px;
}

.btn-sv-out:hover {
    background: var(--gm-primary);
    color: #f3efe6;
}

.btn-cancel-edit {
    background: rgba(220, 38, 38, 0.1);
    color: #dc2626;
    border: 1px solid rgba(220, 38, 38, 0.25);
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    margin-top: 12px;
    margin-left: 8px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.2s ease;
    min-height: 38px;
}

.btn-cancel-edit:hover {
    background: #dc2626;
    color: #ffffff;
}

/* ── Enhanced Ward & Bed Transfer Styles ── */
.tr-compare-box {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    background: #fdfbf7;
    border: 1.5px dashed var(--gm-border);
    border-radius: 12px;
    padding: 14px 16px;
    margin-bottom: 16px;
}
.tr-loc-card {
    padding: 12px 14px;
    border-radius: 10px;
    background: #ffffff;
    border: 1.5px solid var(--gm-border);
    display: flex;
    flex-direction: column;
    gap: 4px;
    box-shadow: 0 2px 6px rgba(31, 107, 74, 0.04);
}
.tr-loc-card.current {
    border-left: 4px solid #64748b;
}
.tr-loc-card.target {
    border-left: 4px solid var(--gm-primary);
    background: #fcfdfc;
}
.tr-loc-title {
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--gm-text-muted);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.tr-loc-val {
    font-size: 0.96rem;
    font-weight: 800;
    color: var(--gm-primary);
}
.tr-loc-meta {
    font-size: 0.76rem;
    color: var(--gm-text-body);
    font-weight: 600;
}
.tr-bed-grid-container {
    margin-top: 12px;
    margin-bottom: 16px;
    background: #ffffff;
    border: 1.5px solid var(--gm-border);
    border-radius: 12px;
    padding: 14px;
}
.tr-bed-grid-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 10px;
}
.tr-bed-legend {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
    font-size: 0.75rem;
    font-weight: 700;
}
.tr-legend-item {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.tr-legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}
.tr-bed-cards-wrap {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(135px, 1fr));
    gap: 10px;
    max-height: 240px;
    overflow-y: auto;
    padding: 4px;
}
.tr-bed-card {
    padding: 10px 12px;
    border-radius: 10px;
    border: 1.5px solid var(--gm-border);
    background: #ffffff;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    gap: 4px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}
.tr-bed-card.available {
    border-color: #16a34a;
    background: #f0fdf4;
}
.tr-bed-card.available:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(22, 163, 74, 0.25);
    border-width: 2px;
}
.tr-bed-card.available.selected {
    border-color: var(--gm-primary);
    background: var(--gm-primary);
    color: #ffffff !important;
    box-shadow: 0 6px 18px rgba(31, 107, 74, 0.4);
}
.tr-bed-card.available.selected .tr-bed-no,
.tr-bed-card.available.selected .tr-bed-sub,
.tr-bed-card.available.selected .tr-bed-price {
    color: #ffffff !important;
}
.tr-bed-card.occupied {
    border-color: #fca5a5;
    background: #fef2f2;
    opacity: 0.75;
    cursor: not-allowed;
}
.tr-bed-card.reserved {
    border-color: #fde68a;
    background: #fffbeb;
    opacity: 0.75;
    cursor: not-allowed;
}
.tr-bed-card.blocked, .tr-bed-card.maintenance {
    border-color: #cbd5e1;
    background: #f8fafc;
    opacity: 0.7;
    cursor: not-allowed;
}
.tr-bed-no {
    font-size: 0.92rem;
    font-weight: 800;
    color: var(--gm-primary);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.tr-bed-sub {
    font-size: 0.73rem;
    color: var(--gm-text-muted);
    font-weight: 600;
}
.tr-bed-price {
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--gm-primary-dark);
}
.emergency-toggle-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #fff1f2;
    border: 1.5px solid #fecdd3;
    padding: 10px 14px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}
.emergency-toggle-wrap.active {
    background: #ffe4e6;
    border-color: #e11d48;
    box-shadow: 0 0 0 2px rgba(225, 29, 72, 0.25);
}
.tr-hist-filter {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}
.tr-hist-filter input {
    flex: 1;
    padding: 6px 10px;
    border: 1px solid var(--gm-border);
    border-radius: 6px;
    font-size: 0.78rem;
    background: var(--gm-bg);
}

/* History logs */
.ht-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-radius: 8px;
    border: 1px solid var(--gm-border);
    max-height: 380px;
    background: #ffffff;
}

.ht {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.78rem;
    min-width: 320px;
}

.ht thead th {
    background: var(--gm-bg);
    padding: 8px 10px;
    text-align: left;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    color: var(--gm-primary);
    border-bottom: 1.5px solid var(--gm-border);
    white-space: nowrap;
}

.ht tbody td {
    padding: 8px 10px;
    border-bottom: 1px solid var(--gm-bg);
    color: var(--gm-text-body);
    vertical-align: middle;
}

.ht tbody tr:last-child td {
    border-bottom: none;
}

.ht tbody tr:hover td {
    background: var(--gm-primary-light);
}

.et td {
    text-align: center;
    color: var(--gm-text-muted);
    padding: 16px;
    font-size: 0.8rem;
}

.badge {
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 700;
    background: var(--gm-primary-light);
    color: var(--gm-primary);
    border: 1px solid var(--gm-border);
}

.btn-edit-log {
    background: var(--gm-primary-light);
    color: var(--gm-primary);
    border: 1px solid var(--gm-border);
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.15s ease;
}

.btn-edit-log:hover {
    background: var(--gm-primary);
    color: #f3efe6;
    border-color: var(--gm-primary);
}

/* Floating Toast */
#toast {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #ffffff;
    color: var(--gm-primary);
    padding: 22px 30px;
    border-radius: 16px;
    font-size: 1rem;
    font-weight: 800;
    z-index: 999999;
    display: none;
    box-shadow: 0 20px 60px rgba(31, 107, 74, 0.4);
    min-width: 320px;
    max-width: 520px;
    line-height: 1.5;
    border: 2px solid var(--gm-primary);
    text-align: center;
    animation: modalZoomIn 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes modalZoomIn {
    from { opacity: 0; transform: translate(-50%, -50%) scale(0.92); }
    to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
}

@media (max-width: 1023px) {
    .content-wrapper {
        margin-left: 0 !important;
        width: 100% !important;
        padding: 16px 20px;
    }
    .tr-compare-box {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>

<div class="main-layout">
  <!-- Sidebar Navigation -->
  <?php include 'includes/nurse_sidebar.php'; ?>

  <div class="content-wrapper">
    <!-- Navbar -->
    <?php $pageTitle = 'Bed Transfer'; include 'includes/nurse_navbar.php'; ?>

    <!-- 1. Patient Selection Grid Dashboard -->
    <div class="nopt-dashboard" id="nopt-state">
      <div class="dash-header">
        <div>
          <h3 style="margin: 0; font-size: 1.3rem; font-weight: 800; color: var(--gm-primary); display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-procedures"></i> Select Patient for Bed Transfer 
            <span class="chip-mini"><?php echo count($assignedPatients); ?> Admitted</span>
          </h3>
          <p style="margin: 4px 0 0 0; font-size: 0.84rem; color: var(--gm-text-muted);">
            <?php echo $nurseWard ? htmlspecialchars($nurseWard['floor_name'] . ' • ' . $nurseWard['ward_name'] . ' (' . ($nurseWard['room_type'] ?: 'Ward') . ')') : 'Select any admitted inpatient below to transfer between floors, wards, room types, or beds.'; ?>
          </p>
        </div>

        <div class="search-box-wrap">
          <i class="fas fa-search"></i>
          <input type="text" id="dash-search" placeholder="Search patient name, PID, bed...">
        </div>
      </div>

      <?php if (!empty($assignedPatients)): ?>
        <div class="dash-patient-grid" id="dash-grid">
          <?php foreach($assignedPatients as $p): 
            $fullName = trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''));
            $initials = strtoupper(substr($p['first_name'] ?? 'P', 0, 1) . (isset($p['last_name'][0]) ? substr($p['last_name'], 0, 1) : ''));
            $searchKey = strtolower($fullName . ' ' . ($p['patient_id'] ?? '') . ' ' . ($p['admission_id'] ?? '') . ' ' . ($p['room_type'] ?? '') . ' ' . ($p['room_number'] ?? ''));
          ?>
            <div class="dash-patient-card dash-card" data-search="<?php echo htmlspecialchars($searchKey); ?>" onclick='selectPatient(<?php echo json_encode($p); ?>)'>
              <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 42px; height: 42px; border-radius: 10px; background: var(--gm-primary); color: #f3efe6; font-weight: 800; display: flex; align-items: center; justify-content: center; font-size: 1rem;">
                  <?php echo $initials; ?>
                </div>
                <div>
                  <div class="dash-patient-name"><?php echo htmlspecialchars($fullName); ?></div>
                  <div style="font-size: 0.78rem; color: var(--gm-text-muted); font-weight: 600;">PID: <?php echo htmlspecialchars($p['patient_id'] ?? '-'); ?></div>
                </div>
              </div>

              <div class="dash-chips-row">
                <span class="chip-mini"><i class="fas fa-bed"></i> <?php echo htmlspecialchars(($p['room_type'] ?? 'Ward') . ' - Bed ' . ($p['bed_number'] ?? $p['room_number'] ?? '-')); ?></span>
                <span class="chip-mini"><i class="fas fa-layer-group"></i> <?php echo htmlspecialchars($p['floor_name'] ?? 'Floor'); ?></span>
                <span class="chip-mini"><i class="fas fa-user-md"></i> Dr. <?php echo htmlspecialchars($p['doctor_name'] ?? 'Consultant'); ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div id="dash-no-res" style="display: none; padding: 40px; text-align: center; color: var(--gm-text-muted);">
          <i class="fas fa-search-minus" style="font-size: 2.5rem; margin-bottom: 10px; opacity: 0.5;"></i>
          <h4>No matching admitted patients found.</h4>
        </div>
      <?php else: ?>
        <div style="text-align: center; padding: 50px 20px; color: var(--gm-text-muted);">
          <i class="fas fa-user-injured" style="font-size: 3rem; margin-bottom: 14px; opacity: 0.4;"></i>
          <h3 style="color: var(--gm-primary);">No Active Inpatients Found</h3>
          <p>There are currently no admitted patients recorded in this section.</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- 2. Sticky Active Patient Banner -->
    <div class="pt-banner" id="pt-banner" style="display: none;">
      <div class="pt-banner-av" id="pt-av">PT</div>
      <div class="pt-banner-info">
        <div class="pt-banner-nm" id="pt-nm">–</div>
        <div class="pt-banner-chips" id="pt-chips"></div>
      </div>
      <div class="pt-banner-ac">
        <button class="pba ksheet" onclick="goToKSheet()"><i class="fas fa-file-medical-alt"></i> View Case-Sheet</button>
        <button class="pba sec" onclick="openSearch()"><i class="fas fa-exchange-alt"></i> Change Patient</button>
      </div>
    </div>

    <!-- 3. Ward / Bed Transfer Module (Preserving Exact Original Markup & Functional Logic) -->
    <div id="ws-layout" style="display: none;">
      <div class="card-new full-width" id="s-tr">
        <div class="card-title-new">
          <div style="display:flex; align-items:center; gap:10px;">
            <i class="fas fa-bed"></i> Bed Transfer Module
            <span class="chip-mini"><i class="fas fa-sync-alt"></i> Real-Time Availability</span>
          </div>
          <button type="button" class="btn-edit-log" onclick="refreshBeds()" style="padding:4px 10px; font-size:0.75rem;">
            <i class="fas fa-redo"></i> Refresh Bed Status
          </button>
        </div>
        <div class="split-card card-body" id="f-tr">
          <div class="split-left">
            
            <!-- Side-by-Side Comparison Preview -->
            <div class="tr-compare-box" id="tr-compare-box">
              <div class="tr-loc-card current">
                <div class="tr-loc-title">
                  <span><i class="fas fa-map-marker-alt"></i> Current Bed Location</span>
                  <span class="badge" style="background:#f1f5f9; color:#475569;">Active</span>
                </div>
                <div class="tr-loc-val" id="tr-curr-val">Select a patient</div>
                <div class="tr-loc-meta" id="tr-curr-meta">Floor: – | Ward: – | Room: –</div>
              </div>
              <div class="tr-loc-card target">
                <div class="tr-loc-title">
                  <span><i class="fas fa-arrow-right"></i> Target Bed Location</span>
                  <span class="badge" id="tr-target-status-badge" style="background:#f0fdf4; color:#16a34a;">Not Selected</span>
                </div>
                <div class="tr-loc-val" id="tr-target-val">Select floor, ward & bed below</div>
                <div class="tr-loc-meta" id="tr-target-meta">Charge: – / day</div>
              </div>
            </div>

            <!-- Location Selectors -->
            <div class="fg">
              <div class="fmg">
                <label><i class="fas fa-layer-group"></i> 1. Select Floor <span style="color:#dc2626;">*</span></label>
                <select name="floor_name" id="tr-floor" onchange="onTrFloorChange()">
                  <option value="">-- Select Floor --</option>
                  <?php foreach($allFloors as $fl): ?>
                    <option value="<?php echo htmlspecialchars($fl['floor_name']); ?>"><?php echo htmlspecialchars($fl['floor_name'] . ' (Floor ' . $fl['floor_number'] . ')'); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="fmg">
                <label><i class="fas fa-hospital-alt"></i> 2. Select Ward <span style="color:#dc2626;">*</span></label>
                <select name="ward_name" id="tr-ward" onchange="onTrWardChange()">
                  <option value="">-- Select Ward --</option>
                  <?php foreach($allWards as $ward): ?>
                    <option value="<?php echo htmlspecialchars($ward); ?>"><?php echo htmlspecialchars($ward); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="fmg">
                <label><i class="fas fa-door-open"></i> 3. Room Type <span style="color:#dc2626;">*</span></label>
                <select name="room_type" id="tr-room-type" onchange="onTrRoomTypeChange()">
                  <option value="">-- Select Room Type --</option>
                </select>
              </div>
              <div class="fmg">
                <label><i class="fas fa-calendar-alt"></i> Transfer Date & Time <span style="color:#dc2626;">*</span></label>
                <input type="datetime-local" name="transfer_date" id="tr-date">
              </div>
            </div>

            <!-- Interactive Bed Availability Grid -->
            <div class="tr-bed-grid-container" id="tr-bed-grid-wrap">
              <div class="tr-bed-grid-header">
                <div style="font-weight: 800; font-size: 0.85rem; color: var(--gm-primary);">
                  <i class="fas fa-procedures"></i> Available Beds in Selected Room Type <span style="color:#dc2626;">*</span>
                </div>
                <div class="tr-bed-legend">
                  <span class="tr-legend-item"><span class="tr-legend-dot" style="background:#16a34a;"></span> Available</span>
                  <span class="tr-legend-item"><span class="tr-legend-dot" style="background:#dc2626;"></span> Occupied</span>
                  <span class="tr-legend-item"><span class="tr-legend-dot" style="background:#f59e0b;"></span> Reserved</span>
                  <span class="tr-legend-item"><span class="tr-legend-dot" style="background:#94a3b8;"></span> Blocked</span>
                </div>
              </div>

              <div class="tr-bed-cards-wrap" id="tr-bed-cards">
                <div style="grid-column:1/-1; text-align:center; padding:20px; color:var(--gm-text-muted); font-size:0.84rem;">
                  <i class="fas fa-info-circle"></i> Please select Floor, Ward, and Room Type to view available beds.
                </div>
              </div>
            </div>

            <!-- Hidden Target Bed Tracking Inputs -->
            <input type="hidden" name="new_bed_id" id="tr-target-bed-id">
            <input type="hidden" id="tr-target-bed-no">
            <input type="hidden" id="tr-target-room-no">
            <input type="hidden" id="tr-target-rate">

            <!-- Clinical Reason & Emergency Toggle -->
            <div class="fg" style="margin-top: 10px;">
              <div class="fmg" style="grid-column: 1 / -1;">
                <label><i class="fas fa-comment-medical"></i> Reason for Transfer <span style="color:#dc2626;">*</span></label>
                <input type="text" name="transfer_remarks" id="tr-remarks" placeholder="e.g. Upgraded to ICU due to SpO2 drop, Step-down to General Room, Patient Request..." required>
              </div>
              <div class="fmg">
                <label>Nurse Signature</label>
                <input type="text" name="nurse_sign" id="tr-nurse-sign" value="<?php echo htmlspecialchars($nurseName); ?>" readonly>
              </div>
              <div class="fmg">
                <label>Priority Setting</label>
                <div class="emergency-toggle-wrap" id="tr-emergency-wrap" onclick="toggleEmergencyCheckbox()">
                  <input type="checkbox" name="is_emergency" id="tr-emergency" onchange="toggleEmergencyStyle(this)" style="width:18px; height:18px; cursor:pointer;">
                  <span style="font-weight: 700; font-size: 0.82rem; color: #e11d48; display:flex; align-items:center; gap:6px;">
                    <i class="fas fa-ambulance"></i> Emergency / High-Priority Transfer
                  </span>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div style="display: flex; gap: 10px; align-items: center; margin-top: 14px;">
              <button type="button" class="btn-sv-out" id="btn-init-transfer" onclick="openTransferConfirmModal()" style="background:var(--gm-primary); color:#f3efe6; border-color:var(--gm-primary);">
                <i class="fas fa-exchange-alt"></i> Execute Patient Transfer
              </button>
              <button type="button" class="btn-cancel-edit" id="btn-reset-tr" onclick="resetTransferForm()" style="margin:0; display:inline-flex;">
                <i class="fas fa-undo"></i> Reset Form
              </button>
            </div>

          </div>

          <!-- Transfer History Table -->
          <div class="split-right">
            <div class="ht-title" style="display:flex; justify-content:space-between; align-items:center;">
              <span><i class="fas fa-history"></i> Complete Transfer History</span>
            </div>
            <div class="tr-hist-filter">
              <i class="fas fa-search" style="color:var(--gm-text-muted); font-size:0.8rem;"></i>
              <input type="text" id="tr-log-search" placeholder="Filter history by date, ward, bed, nurse, reason..." oninput="filterTransferHistory(this.value)">
            </div>
            <div class="ht-wrap" style="max-height: 380px;">
              <table class="ht">
                <thead>
                  <tr>
                    <th>Transfer Date</th>
                    <th>From Bed</th>
                    <th>To Bed</th>
                    <th>Reason</th>
                    <th>Nurse</th>
                  </tr>
                </thead>
                <tbody id="h-tr"><tr class="et"><td colspan="5">No transfer records yet.</td></tr></tbody>
              </table>
            </div>
          </div>

        </div>
      </div>
    </div>

  </div><!-- /content-wrapper -->
</div><!-- /main-layout -->

<!-- Ward Transfer Confirmation Modal -->
<div id="tr-confirm-modal" style="position:fixed;inset:0;background:rgba(15, 35, 25, 0.6);backdrop-filter:blur(4px);z-index:9000;display:none;align-items:center;justify-content:center">
  <div style="background:#ffffff;border-radius:16px;max-width:540px;width:92%;overflow:hidden;box-shadow:0 25px 60px rgba(0, 0, 0, 0.35);border:2px solid var(--gm-primary);animation:modalZoomIn 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);">
    <div style="background:var(--gm-primary);padding:16px 22px;font-weight:800;font-size:1.05rem;color:#f3efe6;display:flex;align-items:center;justify-content:space-between;">
      <span style="display:flex; align-items:center; gap:8px;"><i class="fas fa-exchange-alt"></i> Confirm Patient Bed Transfer</span>
      <span id="tr-modal-emergency-badge" style="display:none; background:#dc2626; color:#ffffff; font-size:0.72rem; padding:4px 9px; border-radius:6px; font-weight:800;">
        <i class="fas fa-ambulance"></i> EMERGENCY TRANSFER
      </span>
    </div>
    <div style="padding:22px;font-size:0.9rem;color:var(--gm-text-body);line-height:1.5;">
      <div style="margin-bottom:14px; background:#f8fafc; border:1px solid var(--gm-border); border-radius:10px; padding:12px 14px;">
        <div style="font-weight:800; color:var(--gm-primary); font-size:1rem; margin-bottom:4px;" id="tr-modal-pt-name">Patient Name</div>
        <div style="font-size:0.8rem; color:var(--gm-text-muted);" id="tr-modal-pt-details">PID: – | IP: –</div>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:14px;">
        <div style="background:#fef2f2; border:1px solid #fca5a5; border-radius:8px; padding:10px;">
          <div style="font-size:0.7rem; font-weight:800; color:#991b1b; text-transform:uppercase;">From (Current Bed)</div>
          <div style="font-weight:800; color:#7f1d1d; font-size:0.88rem; margin-top:2px;" id="tr-modal-from-val">–</div>
        </div>
        <div style="background:#f0fdf4; border:1px solid #86efac; border-radius:8px; padding:10px;">
          <div style="font-size:0.7rem; font-weight:800; color:#166534; text-transform:uppercase;">To (New Bed)</div>
          <div style="font-weight:800; color:#14532d; font-size:0.88rem; margin-top:2px;" id="tr-modal-to-val">–</div>
        </div>
      </div>

      <div style="background:var(--gm-bg); border-radius:8px; padding:10px 12px; font-size:0.84rem; margin-bottom:14px;">
        <strong>Reason for Transfer:</strong> <span id="tr-modal-reason-val">–</span>
      </div>

      <p style="margin:0; font-size:0.8rem; color:var(--gm-text-muted); line-height:1.4;">
        <i class="fas fa-info-circle"></i> Once confirmed:
        <br>• The previous bed will automatically be released (Status: Available).
        <br>• The new bed will become Occupied by this patient.
        <br>• Real-time notification will be dispatched to duty nurses on the target floor and ward.
      </p>
    </div>
    <div style="padding:14px 22px;background:#f8fafc;border-top:1px solid var(--gm-border);display:flex;gap:10px;justify-content:flex-end">
      <button type="button" onclick="closeTransferConfirmModal()" style="padding:9px 16px;border-radius:8px;font-weight:700;font-size:0.85rem;border:1.5px solid var(--gm-border);background:#ffffff;color:var(--gm-primary);cursor:pointer">Cancel</button>
      <button type="button" id="tr-confirm-btn" onclick="executeBedTransfer()" style="padding:9px 20px;border-radius:8px;font-weight:800;font-size:0.88rem;border:none;background:var(--gm-primary);color:#f3efe6;cursor:pointer;display:inline-flex;align-items:center;gap:6px;"><i class="fas fa-check-circle"></i> Confirm & Execute Transfer</button>
    </div>
  </div>
</div>

<!-- Centered High-Contrast Toast -->
<div id="toast"></div>

<script>
let cp = null;
const NN = <?php echo json_encode($nurseName); ?>;
let currentTransferLogs = [];
let currentLoadedBeds = [];

// Patient Search Listener
const ds = document.getElementById('dash-search');
if (ds) {
    ds.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        const cards = document.querySelectorAll('.dash-card');
        let visibleCount = 0;
        cards.forEach(c => {
            const text = c.getAttribute('data-search') || '';
            if (text.includes(q)) {
                c.style.display = 'flex';
                visibleCount++;
            } else {
                c.style.display = 'none';
            }
        });
        const noRes = document.getElementById('dash-no-res');
        if (noRes) noRes.style.display = (visibleCount === 0) ? 'block' : 'none';
    });
}

function selectPatient(p) {
  cp = p;
  document.getElementById('nopt-state').style.display = 'none';
  const ini = ((p.first_name || '')[0] || '') + ((p.last_name || '')[0] || '') || 'PT';
  document.getElementById('pt-av').textContent = ini.toUpperCase();
  document.getElementById('pt-nm').textContent = `${p.first_name} ${p.last_name || ''}`;
  document.getElementById('pt-chips').innerHTML = [
    { ic: 'fa-id-card', l: 'PID', v: p.patient_id },
    { ic: 'fa-file-invoice', l: 'IP#', v: p.admission_id || 'N/A' },
    { ic: 'fa-bed', l: 'Bed', v: `${p.ward_name || p.room_type || 'Ward'} - Bed ${p.bed_number || p.room_number || '-'}` },
    { ic: 'fa-layer-group', l: 'Floor', v: p.floor_name || '-' },
    { ic: 'fa-user', l: 'Age/Sex', v: `${p.age || '?'}Y / ${p.sex || '?'}` },
    { ic: 'fa-tint', l: 'Blood', v: p.blood_group || 'N/A' }
  ].map(c => `<span class="ptchip"><i class="fas ${c.ic}"></i><strong>${c.l}:</strong> ${c.v}</span>`).join('');
  
  document.getElementById('pt-banner').style.display = 'flex';
  document.getElementById('ws-layout').style.display = 'block';

  autoFillDate();
  initTransferForm();
  loadTransferHistory();
}

function openSearch() {
  document.getElementById('nopt-state').style.display = 'block';
  document.getElementById('pt-banner').style.display = 'none';
  document.getElementById('ws-layout').style.display = 'none';
}

function goToKSheet() {
  if (cp && cp.patient_id) {
    window.location.href = `k_sheet_view.php?patient_id=${encodeURIComponent(cp.patient_id)}&admission_id=${encodeURIComponent(cp.admission_id || '')}`;
  }
}

function autoFillDate() {
  const now = new Date();
  const ym = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
  const hm = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
  const trDate = document.getElementById('tr-date');
  if (trDate && !trDate.value) {
    trDate.value = ym + 'T' + hm;
  }
}

/* ── Enhanced Ward & Bed Transfer Functions (Preserved 100% Functionality) ── */

function initTransferForm() {
  if (!cp) return;

  // 1. Populate Current Location
  const currBedNo = cp.bed_number || cp.room_number || 'N/A';
  const currWard = cp.ward_name || cp.ward || cp.room_type || 'General Ward';
  const currRoomType = cp.room_type || 'Ward';
  const currFloor = cp.floor_name || 'Main Floor';

  const currValEl = document.getElementById('tr-curr-val');
  if (currValEl) currValEl.textContent = `${currWard} • ${currRoomType} (Bed ${currBedNo})`;

  const currMetaEl = document.getElementById('tr-curr-meta');
  if (currMetaEl) currMetaEl.textContent = `Floor: ${currFloor} | Bed ID: ${cp.bed_id || 'N/A'}`;

  // 2. Reset Target Location
  resetTransferForm();

  // 3. Populate Floor if match exists
  const floorSelect = document.getElementById('tr-floor');
  if (floorSelect && cp.floor_name) {
    Array.from(floorSelect.options).forEach(opt => {
      if (opt.value === cp.floor_name) opt.selected = true;
    });
    onTrFloorChange();
  }
}

async function onTrFloorChange() {
  const floor = document.getElementById('tr-floor').value;
  const wardSelect = document.getElementById('tr-ward');
  const roomTypeSelect = document.getElementById('tr-room-type');
  const bedCardsWrap = document.getElementById('tr-bed-cards');

  wardSelect.innerHTML = '<option value="">-- Select Ward --</option>';
  roomTypeSelect.innerHTML = '<option value="">-- Select Room Type --</option>';
  if (bedCardsWrap) {
    bedCardsWrap.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:20px; color:var(--gm-text-muted); font-size:0.84rem;"><i class="fas fa-info-circle"></i> Please select Ward and Room Type to view beds.</div>';
  }
  clearSelectedTargetBed();

  if (!floor) return;

  try {
    const res = await fetch(`api/get_ward_hierarchy.php?action=wards&floor=${encodeURIComponent(floor)}`);
    const json = await res.json();
    if (json.success && Array.isArray(json.data)) {
      json.data.forEach(w => {
        const opt = document.createElement('option');
        opt.value = w;
        opt.textContent = w;
        wardSelect.appendChild(opt);
      });
      // If patient is in this floor, pre-select ward
      if (cp && cp.ward_name && json.data.includes(cp.ward_name)) {
        wardSelect.value = cp.ward_name;
        onTrWardChange();
      }
    }
  } catch (err) {
    console.error('Error fetching wards:', err);
  }
}

async function onTrWardChange() {
  const floor = document.getElementById('tr-floor').value;
  const ward = document.getElementById('tr-ward').value;
  const roomTypeSelect = document.getElementById('tr-room-type');
  const bedCardsWrap = document.getElementById('tr-bed-cards');

  roomTypeSelect.innerHTML = '<option value="">-- Select Room Type --</option>';
  if (bedCardsWrap) {
    bedCardsWrap.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:20px; color:var(--gm-text-muted); font-size:0.84rem;"><i class="fas fa-info-circle"></i> Please select Room Type to view available beds.</div>';
  }
  clearSelectedTargetBed();

  if (!ward) return;

  try {
    const res = await fetch(`api/get_ward_hierarchy.php?action=room_types&floor=${encodeURIComponent(floor)}&ward=${encodeURIComponent(ward)}`);
    const json = await res.json();
    if (json.success && Array.isArray(json.data)) {
      json.data.forEach(rt => {
        const opt = document.createElement('option');
        opt.value = rt;
        opt.textContent = rt;
        roomTypeSelect.appendChild(opt);
      });
      // If single room type available, auto load beds
      if (json.data.length === 1) {
        roomTypeSelect.value = json.data[0];
        onTrRoomTypeChange();
      }
    }
  } catch (err) {
    console.error('Error fetching room types:', err);
  }
}

async function onTrRoomTypeChange() {
  loadBeds();
}

async function loadBeds() {
  const floor = document.getElementById('tr-floor').value;
  const ward = document.getElementById('tr-ward').value;
  const roomType = document.getElementById('tr-room-type').value;
  const bedCardsWrap = document.getElementById('tr-bed-cards');

  if (!floor || !ward || !roomType) {
    if (bedCardsWrap) {
      bedCardsWrap.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:20px; color:var(--gm-text-muted); font-size:0.84rem;"><i class="fas fa-info-circle"></i> Please select Floor, Ward, and Room Type to view available beds.</div>';
    }
    clearSelectedTargetBed();
    return;
  }

  if (bedCardsWrap) {
    bedCardsWrap.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:24px; color:var(--gm-primary);"><i class="fas fa-spinner fa-spin fa-2x"></i><p style="margin-top:8px; font-weight:700;">Loading real-time bed availability...</p></div>';
  }

  try {
    const res = await fetch(`api/get_ward_hierarchy.php?action=beds&floor=${encodeURIComponent(floor)}&ward=${encodeURIComponent(ward)}&room_type=${encodeURIComponent(roomType)}`);
    const json = await res.json();
    if (json.success && Array.isArray(json.data)) {
      currentLoadedBeds = json.data;
      renderBedCards(json.data);
    } else {
      bedCardsWrap.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:20px; color:var(--gm-text-muted);">No beds found for this room type.</div>';
    }
  } catch (err) {
    console.error('Error loading beds:', err);
    bedCardsWrap.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:20px; color:#dc2626;"><i class="fas fa-exclamation-triangle"></i> Failed to load beds. Click Refresh to retry.</div>';
  }
}

function refreshBeds() {
  const floor = document.getElementById('tr-floor').value;
  const ward = document.getElementById('tr-ward').value;
  const roomType = document.getElementById('tr-room-type').value;
  if (!floor || !ward || !roomType) {
    showToast('Please select Floor, Ward, and Room Type first.', true);
    return;
  }
  loadBeds();
  showToast('🔄 Bed availability refreshed!');
}

function renderBedCards(beds) {
  const wrap = document.getElementById('tr-bed-cards');
  if (!wrap) return;

  if (!beds.length) {
    wrap.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:20px; color:var(--gm-text-muted);">No beds configured in this section.</div>';
    return;
  }

  const selectedBedId = document.getElementById('tr-target-bed-id').value;

  wrap.innerHTML = beds.map(b => {
    const rawStatus = (b.bed_status || 'Available').trim();
    const statusLower = rawStatus.toLowerCase();
    const isCurrent = cp && parseInt(b.bed_id) === parseInt(cp.bed_id);
    const isAvailable = (statusLower === 'available' || statusLower === 'vacant') && !isCurrent;
    const isSelected = selectedBedId && parseInt(selectedBedId) === parseInt(b.bed_id);

    let cardClass = 'tr-bed-card ';
    let statusBadge = '';
    let clickAttr = '';

    if (isCurrent) {
      cardClass += 'occupied';
      statusBadge = '<span class="badge" style="background:#f1f5f9; color:#475569; font-size:0.68rem;">Current Bed</span>';
    } else if (isAvailable) {
      cardClass += 'available' + (isSelected ? ' selected' : '');
      statusBadge = '<span class="badge" style="background:#dcfce7; color:#15803d; font-size:0.68rem;"><i class="fas fa-check"></i> Available</span>';
      clickAttr = `onclick='selectTargetBed(${JSON.stringify(b)})'`;
    } else if (statusLower === 'occupied') {
      cardClass += 'occupied';
      const ptInfo = b.occupied_by_patient ? `<br><small style="color:#b91c1c;">${b.occupied_by_patient}</small>` : '';
      statusBadge = `<span class="badge" style="background:#fee2e2; color:#b91c1c; font-size:0.68rem;">Occupied</span>${ptInfo}`;
    } else if (statusLower === 'reserved') {
      cardClass += 'reserved';
      statusBadge = '<span class="badge" style="background:#fef3c7; color:#b45309; font-size:0.68rem;">Reserved</span>';
    } else {
      cardClass += 'blocked';
      statusBadge = `<span class="badge" style="background:#e2e8f0; color:#475569; font-size:0.68rem;">${rawStatus}</span>`;
    }

    const rate = b.total_bed_amount || b.amount_per_day || 0;

    return `
      <div class="${cardClass}" id="bed-card-${b.bed_id}" ${clickAttr} title="${isAvailable ? 'Click to select bed ' + b.bed_number : rawStatus}">
        <div class="tr-bed-no">
          <span><i class="fas fa-bed"></i> Bed ${b.bed_number}</span>
          ${isSelected ? '<i class="fas fa-check-circle" style="color:#ffffff;"></i>' : ''}
        </div>
        <div class="tr-bed-sub">Room: ${b.room_number || b.room_name || '-'}</div>
        <div class="tr-bed-price">₹${rate}/day</div>
        <div style="margin-top:2px;">${statusBadge}</div>
      </div>
    `;
  }).join('');
}

function selectTargetBed(bed) {
  // Update hidden inputs
  document.getElementById('tr-target-bed-id').value = bed.bed_id;
  document.getElementById('tr-target-bed-no').value = bed.bed_number;
  document.getElementById('tr-target-room-no').value = bed.room_number || '';
  document.getElementById('tr-target-rate').value = bed.total_bed_amount || bed.amount_per_day || 0;

  // Update visual selection
  document.querySelectorAll('.tr-bed-card').forEach(c => c.classList.remove('selected'));
  const activeCard = document.getElementById(`bed-card-${bed.bed_id}`);
  if (activeCard) activeCard.classList.add('selected');

  // Update target preview card
  const targetValEl = document.getElementById('tr-target-val');
  if (targetValEl) targetValEl.textContent = `${bed.ward_name} • ${bed.room_type} (Bed ${bed.bed_number})`;

  const targetMetaEl = document.getElementById('tr-target-meta');
  if (targetMetaEl) {
    const rate = bed.total_bed_amount || bed.amount_per_day || 0;
    targetMetaEl.textContent = `Floor: ${bed.floor_name || 'N/A'} | Room: ${bed.room_number || '-'} | ₹${rate}/day`;
  }

  const badgeEl = document.getElementById('tr-target-status-badge');
  if (badgeEl) {
    badgeEl.textContent = `✓ Selected: Bed ${bed.bed_number}`;
    badgeEl.style.background = '#dcfce7';
    badgeEl.style.color = '#15803d';
  }

  showToast(`✅ Bed ${bed.bed_number} selected for transfer.`);
}

function clearSelectedTargetBed() {
  document.getElementById('tr-target-bed-id').value = '';
  document.getElementById('tr-target-bed-no').value = '';
  document.getElementById('tr-target-room-no').value = '';
  document.getElementById('tr-target-rate').value = '';

  const targetValEl = document.getElementById('tr-target-val');
  if (targetValEl) targetValEl.textContent = 'Select floor, ward & bed below';

  const targetMetaEl = document.getElementById('tr-target-meta');
  if (targetMetaEl) targetMetaEl.textContent = 'Charge: – / day';

  const badgeEl = document.getElementById('tr-target-status-badge');
  if (badgeEl) {
    badgeEl.textContent = 'Not Selected';
    badgeEl.style.background = '#f1f5f9';
    badgeEl.style.color = '#64748b';
  }
}

function resetTransferForm() {
  clearSelectedTargetBed();
  document.getElementById('tr-remarks').value = '';
  document.getElementById('tr-emergency').checked = false;
  toggleEmergencyStyle(document.getElementById('tr-emergency'));

  const floorSelect = document.getElementById('tr-floor');
  if (floorSelect) floorSelect.value = '';
  const wardSelect = document.getElementById('tr-ward');
  if (wardSelect) wardSelect.innerHTML = '<option value="">-- Select Ward --</option>';
  const roomTypeSelect = document.getElementById('tr-room-type');
  if (roomTypeSelect) roomTypeSelect.innerHTML = '<option value="">-- Select Room Type --</option>';

  const bedCardsWrap = document.getElementById('tr-bed-cards');
  if (bedCardsWrap) {
    bedCardsWrap.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:20px; color:var(--gm-text-muted); font-size:0.84rem;"><i class="fas fa-info-circle"></i> Please select Floor, Ward, and Room Type to view available beds.</div>';
  }

  autoFillDate();
}

function toggleEmergencyStyle(cb) {
  const wrap = document.getElementById('tr-emergency-wrap');
  if (wrap) {
    if (cb.checked) {
      wrap.classList.add('active');
    } else {
      wrap.classList.remove('active');
    }
  }
}

function toggleEmergencyCheckbox() {
  const cb = document.getElementById('tr-emergency');
  if (cb && event.target !== cb) {
    cb.checked = !cb.checked;
    toggleEmergencyStyle(cb);
  }
}

function openTransferConfirmModal() {
  if (!cp) {
    showToast('Please select an admitted patient first!', true);
    return;
  }

  const newBedId = document.getElementById('tr-target-bed-id').value;
  if (!newBedId) {
    showToast('Please select an available target bed from the grid!', true);
    return;
  }

  const remarks = document.getElementById('tr-remarks').value.trim();
  if (!remarks) {
    showToast('Please enter the reason for transfer!', true);
    document.getElementById('tr-remarks').focus();
    return;
  }

  const trDate = document.getElementById('tr-date').value;
  if (!trDate) {
    showToast('Please specify the transfer date and time!', true);
    return;
  }

  const isEmergency = document.getElementById('tr-emergency').checked;

  // Populate Modal Summary
  document.getElementById('tr-modal-pt-name').textContent = `${cp.first_name} ${cp.last_name || ''}`;
  document.getElementById('tr-modal-pt-details').textContent = `PID: ${cp.patient_id} | IP#: ${cp.admission_id || 'N/A'} | Age/Sex: ${cp.age || '-'}Y / ${cp.sex || '-'}`;

  const currBedNo = cp.bed_number || cp.room_number || 'N/A';
  const currWard = cp.ward_name || cp.ward || cp.room_type || 'General Ward';
  document.getElementById('tr-modal-from-val').textContent = `${currWard} (Bed ${currBedNo})`;

  document.getElementById('tr-modal-to-val').textContent = document.getElementById('tr-target-val').textContent;
  document.getElementById('tr-modal-reason-val').textContent = remarks;

  const emergBadge = document.getElementById('tr-modal-emergency-badge');
  if (emergBadge) emergBadge.style.display = isEmergency ? 'inline-flex' : 'none';

  document.getElementById('tr-confirm-modal').style.display = 'flex';
}

function closeTransferConfirmModal() {
  document.getElementById('tr-confirm-modal').style.display = 'none';
}

async function executeBedTransfer() {
  if (!cp) return;

  const newBedId = document.getElementById('tr-target-bed-id').value;
  const remarks = document.getElementById('tr-remarks').value.trim();
  const trDate = document.getElementById('tr-date').value;
  const isEmergency = document.getElementById('tr-emergency').checked ? 1 : 0;

  const btn = document.getElementById('tr-confirm-btn');
  const origHtml = btn.innerHTML;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Executing Transfer...';
  btn.disabled = true;

  try {
    const payload = {
      patient_id: cp.patient_id,
      admission_id: cp.admission_id || '',
      new_bed_id: parseInt(newBedId),
      transfer_date: trDate,
      transfer_remarks: remarks,
      is_emergency: isEmergency
    };

    const res = await fetch('api/transfer_bed.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const data = await res.json();

    if (data.success) {
      showToast(`✅ ${data.message || 'Patient transferred successfully!'}`);

      // 1. Update active patient object
      cp.bed_id = data.data.bed_id;
      cp.floor_name = data.data.floor_name;
      cp.ward_name = data.data.ward_name;
      cp.room_type = data.data.room_type;
      cp.room_number = data.data.room_number;
      cp.bed_number = data.data.bed_number;

      // 2. Update Sticky Banner Chips
      const chipsEl = document.getElementById('pt-chips');
      if (chipsEl) {
        chipsEl.innerHTML = [
          { ic: 'fa-id-card', l: 'PID', v: cp.patient_id },
          { ic: 'fa-file-invoice', l: 'IP#', v: cp.admission_id || 'N/A' },
          { ic: 'fa-bed', l: 'Bed', v: `${cp.ward_name || cp.room_type} - Bed ${cp.bed_number || cp.room_number}` },
          { ic: 'fa-layer-group', l: 'Floor', v: cp.floor_name || '-' },
          { ic: 'fa-user', l: 'Age/Sex', v: `${cp.age || '?'}Y / ${cp.sex || '?'}` },
          { ic: 'fa-tint', l: 'Blood', v: cp.blood_group || 'N/A' }
        ].map(c => `<span class="ptchip"><i class="fas ${c.ic}"></i><strong>${c.l}:</strong> ${c.v}</span>`).join('');
      }

      // 3. Close Modal & Reset Form
      closeTransferConfirmModal();
      initTransferForm();

      // 4. Reload Transfer Logs
      loadTransferHistory();

      // 5. Reload bed grid
      loadBeds();

      // 6. Trigger notification refresh
      if (typeof fetchNurseNotifications === 'function') {
        fetchNurseNotifications();
      }

    } else {
      showToast(`❌ Transfer Failed: ${data.message || 'Unknown error'}`, true);
    }
  } catch (err) {
    console.error('Transfer execution error:', err);
    showToast('Network error during transfer.', true);
  } finally {
    btn.innerHTML = origHtml;
    btn.disabled = false;
  }
}

async function loadTransferHistory() {
  if (!cp) return;
  try {
    const r = await fetch(`api/get_clinical_records.php?patient_id=${encodeURIComponent(cp.patient_id)}&admission_id=${encodeURIComponent(cp.admission_id || '')}`);
    const d = (await r.json())?.data || {};
    currentTransferLogs = d.ward_transfer || [];
    renderTransferHistory(currentTransferLogs);
  } catch(e) {
    console.error('Failed to load transfer history:', e);
  }
}

function renderTransferHistory(logs) {
  const tb = document.getElementById('h-tr');
  if (!tb) return;
  if (!logs || !logs.length) {
    tb.innerHTML = '<tr class="et"><td colspan="5">No transfer records yet.</td></tr>';
    return;
  }

  tb.innerHTML = [...logs].reverse().map(r => {
    const fromTxt = (r.from_ward || r.from_bed || '-') + (r.from_bed_no ? ` (Bed ${r.from_bed_no})` : '');
    const toTxt = (r.to_ward || r.to_bed || '-') + (r.to_bed_no ? ` (Bed ${r.to_bed_no})` : '');
    const isEmerg = r.is_emergency == 1;
    const dt = (r.transfer_date || r.date || r.created_date || '').replace('T', ' ');

    return `
      <tr>
        <td><strong>${dt}</strong></td>
        <td><small style="color:var(--gm-text-muted);">${r.from_floor ? r.from_floor + ' • ' : ''}</small>${fromTxt}</td>
        <td><strong style="color:var(--gm-primary);">${toTxt}</strong><br><small style="color:var(--gm-text-muted);">${r.to_floor ? r.to_floor + ' • ' : ''}${r.to_room_type || ''}</small></td>
        <td>
          ${r.transfer_remarks || r.remarks || '-'}
          ${isEmerg ? '<br><span class="badge" style="background:#fee2e2; color:#dc2626; font-size:0.7rem; font-weight:800; border-color:#fca5a5;"><i class="fas fa-ambulance"></i> EMERGENCY</span>' : ''}
        </td>
        <td><small style="color:var(--gm-text-muted);">${r.created_by_name || r.nurse_name || r.nurse_sign || '-'}</small></td>
      </tr>
    `;
  }).join('');
}

function filterTransferHistory(q) {
  q = (q || '').toLowerCase().trim();
  if (!q) {
    renderTransferHistory(currentTransferLogs);
    return;
  }
  const filtered = currentTransferLogs.filter(r => {
    const str = `${r.transfer_date || ''} ${r.from_ward || ''} ${r.to_ward || ''} ${r.from_bed_no || ''} ${r.to_bed_no || ''} ${r.transfer_remarks || ''} ${r.created_by_name || ''} ${r.to_floor || ''} ${r.to_room_type || ''}`.toLowerCase();
    return str.includes(q);
  });
  renderTransferHistory(filtered);
}

function showToast(msg, err=false){
  const t = document.getElementById('toast');
  t.style.background = '#ffffff';
  t.style.color = '#23342b';
  t.style.border = err ? '2.5px solid #dc2626' : '2.5px solid #1f6b4a';
  t.style.boxShadow = '0 20px 60px rgba(0, 0, 0, 0.35)';
  t.innerHTML = `
    <div style="display:flex; align-items:center; justify-content:center; gap:10px; margin-bottom:8px; font-size:1.15rem; font-weight:800; color:${err ? '#dc2626' : '#1f6b4a'};">
        <i class="fas ${err ? 'fa-exclamation-circle' : 'fa-check-circle'}"></i> ${err ? 'Notice / Error' : 'Bed Transfer System'}
    </div>
    <div style="font-size:0.92rem; font-weight:700; color:#23342b; line-height:1.5;">${msg}</div>
  `;
  t.style.display = 'block'; 
  clearTimeout(t._t); 
  t._t = setTimeout(() => t.style.display = 'none', 4500);
}

// Auto-select patient if preselected from server
<?php if ($preselectedPatient): ?>
document.addEventListener('DOMContentLoaded', function() {
    selectPatient(<?php echo json_encode($preselectedPatient); ?>);
});
<?php endif; ?>
</script>
</body>
</html>
