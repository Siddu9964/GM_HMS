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

$allDoctors = [];
$allWards = [];
$assignedPatients = [];
$nurseWard = null;

try {
    $db = GM_HMS\Database\SecureDatabase::getInstance();
    $conn = $db->getConnection();
    
    $res = $conn->query("SELECT full_name FROM doctors ORDER BY full_name ASC");
    if ($res) {
        while ($r = $res->fetch_assoc()) $allDoctors[] = $r['full_name'];
    }
    
    $resWards = $conn->query("SELECT DISTINCT room_type FROM hospital_beds WHERE room_type IS NOT NULL AND room_type != '' ORDER BY room_type ASC");
    if ($resWards) {
        while ($r = $resWards->fetch_assoc()) {
            $allWards[] = $r['room_type'];
        }
    }
    
    // Fetch assigned shift and patients
    require_once __DIR__ . '/includes/nurse_auth_helper.php';
    if ($nurseId) {
        $nurseWard = getCurrentNurseWard($conn, $nurseId);
        $roleId = $_SESSION['role_id'] ?? $_SESSION['user_id'] ?? null;
        $shiftModel = new \GM_HMS\Models\NurseShiftModel();
        $assignedPatients = $shiftModel->getAssignedPatientsRedesigned($nurseId, $roleId, $nurseWard);
    }
} catch(Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Nurse Workspace - GM HMS</title>
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

/* ============================================================
   PATIENT DASHBOARD & SELECTION VIEW
   ============================================================ */

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

/* ============================================================
   STICKY ACTIVE PATIENT BANNER
   ============================================================ */

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
    margin-bottom: 16px;
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

.pba.ksheet { background: #f3efe6; color: var(--gm-primary); }
.pba.ksheet:hover { background: #ffffff; transform: translateY(-1px); }
.pba.warn { background: rgba(243, 239, 230, 0.25); color: #f3efe6; border: 1px solid rgba(243, 239, 230, 0.4); }
.pba.warn:hover { background: #f3efe6; color: var(--gm-primary); }
.pba.sec { background: transparent; color: #f3efe6; border: 1px solid rgba(243, 239, 230, 0.3); }
.pba.sec:hover { background: rgba(243, 239, 230, 0.2); }

/* ============================================================
   WORKSPACE CATEGORY NAVIGATION PILLS
   ============================================================ */

.workspace-tabs {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding-bottom: 8px;
    margin-bottom: 18px;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
}

.workspace-tabs::-webkit-scrollbar {
    display: none;
}

.tab-pill {
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.82rem;
    font-weight: 700;
    background: #ffffff;
    color: var(--gm-primary);
    border: 1.5px solid var(--gm-border);
    cursor: pointer;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s ease;
    min-height: 36px;
}

.tab-pill:hover, .tab-pill.active {
    background: var(--gm-primary);
    color: #f3efe6;
    border-color: var(--gm-primary);
    box-shadow: 0 4px 12px rgba(31, 107, 74, 0.2);
}

/* ============================================================
   RESPONSIVE BENTO WORKSPACE CARDS
   ============================================================ */

.ws-grid-new {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    width: 100%;
}

.card-new {
    background: #ffffff;
    border: 1.5px solid var(--gm-border);
    border-radius: 14px;
    padding: 18px 20px;
    box-shadow: 0 2px 10px rgba(31, 107, 74, 0.04);
    display: flex;
    flex-direction: column;
}

.card-new.full-width {
    grid-column: 1 / -1;
}

.card-title-new {
    color: var(--gm-primary);
    font-weight: 800;
    font-size: 1.02rem;
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
    font-size: 0.8rem;
    font-weight: 800;
    color: var(--gm-primary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Forms */
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
    background: var(--gm-bg);
    color: var(--gm-text-muted);
    cursor: not-allowed;
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

/* History logs */
.ht-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-radius: 8px;
    border: 1px solid var(--gm-border);
    max-height: 160px;
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

/* Treatments Subtabs & Panels */
.treatment-subtabs {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
}

.t-tab {
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.82rem;
    font-weight: 700;
    background: var(--gm-bg);
    color: var(--gm-primary);
    border: 1.5px solid var(--gm-border);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s ease;
}

.t-tab:hover, .t-tab.active {
    background: var(--gm-primary);
    color: #f3efe6;
    border-color: var(--gm-primary);
    box-shadow: 0 4px 12px rgba(31, 107, 74, 0.22);
}

.t-panel {
    width: 100%;
}

.t-title {
    font-size: 0.95rem;
    font-weight: 800;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--gm-primary);
}

/* Search Dropdowns */
#ts-results, #ph-results {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: #ffffff;
    border: 1.5px solid var(--gm-primary);
    border-radius: 10px;
    z-index: 500;
    display: none;
    box-shadow: 0 10px 25px rgba(31, 107, 74, 0.18);
    max-height: 240px;
    overflow-y: auto;
}

.ts-item, .ph-item {
    padding: 10px 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--gm-bg);
    transition: background 0.15s;
}

.ts-item:hover, .ph-item:hover {
    background: var(--gm-primary-light);
}

.cart-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: #ffffff;
    border-radius: 8px;
    margin-bottom: 6px;
    border: 1px solid var(--gm-border);
}

.cart-row-n {
    flex: 1;
    font-size: 0.84rem;
    font-weight: 700;
    color: var(--gm-primary);
}

.cart-row input[type=number] {
    width: 60px;
    padding: 4px 6px;
    border: 1px solid var(--gm-border);
    border-radius: 6px;
    text-align: center;
    font-size: 0.82rem;
    color: var(--gm-primary);
    font-weight: 700;
}

.cart-row .rm-btn {
    background: none;
    border: none;
    color: #dc2626;
    cursor: pointer;
    padding: 4px;
}

/* Floating Toast */
#toast {
    position: fixed;
    top: 24px;
    right: 24px;
    background: var(--gm-primary);
    color: #f3efe6;
    padding: 14px 22px;
    border-radius: 10px;
    font-size: 0.88rem;
    font-weight: 700;
    z-index: 9999;
    display: none;
    box-shadow: 0 10px 30px rgba(31, 107, 74, 0.25);
    max-width: 400px;
    line-height: 1.4;
    border: 1.5px solid rgba(243, 239, 230, 0.3);
}

/* Sticky Save Bottom Bar */
.sticky-save-bar {
    position: sticky;
    bottom: 15px;
    z-index: 80;
    background: #ffffff;
    border: 2px solid var(--gm-primary);
    border-radius: 14px;
    padding: 16px 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 10px 35px rgba(31, 107, 74, 0.2);
    margin-top: 24px;
    grid-column: 1 / -1;
}

.btn-sv-main {
    background: var(--gm-primary);
    color: #f3efe6;
    border: none;
    padding: 12px 26px;
    border-radius: 10px;
    font-weight: 800;
    font-size: 0.96rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 4px 14px rgba(31, 107, 74, 0.3);
    transition: all 0.2s ease;
    min-height: 44px;
}

.btn-sv-main:hover {
    background: var(--gm-primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(31, 107, 74, 0.4);
}

.btn-sv-main:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* Select2 overrides */
.select2-container .select2-selection--single {
    height: 38px;
    border: 1.5px solid var(--gm-border);
    border-radius: 8px;
    background-color: var(--gm-bg);
    display: flex;
    align-items: center;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: var(--gm-primary);
    font-size: 0.86rem;
    font-weight: 600;
    padding-left: 10px;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
    right: 8px;
}
.select2-container--default.select2-container--focus .select2-selection--single {
    border-color: var(--gm-primary);
    background-color: #ffffff;
}

/* ============================================================
   RESPONSIVE BREAKPOINTS (Desktop, Tablet, Mobile)
   ============================================================ */

/* 1. Tablet View (768px to 1023px) */
@media (max-width: 1023px) {
    .content-wrapper {
        margin-left: 0 !important;
        width: 100% !important;
        padding: 16px 20px;
    }
    .ws-grid-new {
        grid-template-columns: 1fr;
    }
    .pt-banner {
        padding: 12px 16px;
    }
}

/* 2. Mobile Screens (< 768px) */
@media (max-width: 767px) {
    .content-wrapper {
        margin-left: 0 !important;
        width: 100% !important;
        padding: 12px;
    }
    .ws-grid-new {
        grid-template-columns: 1fr;
        gap: 14px;
    }
    .card-new {
        padding: 15px 16px;
        border-radius: 12px;
    }
    .fg {
        grid-template-columns: 1fr !important;
        gap: 10px;
    }
    .fmg input, .fmg select, .fmg textarea {
        font-size: 0.95rem; /* Prevents auto-zoom in mobile Safari */
        padding: 10px 12px;
    }
    .btn-sv-out {
        width: 100%;
        justify-content: center;
    }
    .pt-banner {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
        padding: 14px;
        position: static; /* Better scrolling experience on small screens */
    }
    .pt-banner-ac {
        width: 100%;
        display: grid;
        grid-template-columns: 1fr;
        gap: 8px;
        margin-top: 6px;
    }
    .pba {
        width: 100%;
    }
    .sticky-save-bar {
        flex-direction: column;
        gap: 12px;
        align-items: stretch;
        text-align: center;
        padding: 14px 16px;
    }
    .btn-sv-main {
        width: 100%;
    }
    .treatments-grid {
        grid-template-columns: 1fr;
    }
    #toast {
        left: 16px;
        right: 16px;
        top: 16px;
        max-width: calc(100% - 32px);
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
    <?php $pageTitle = 'Nurse Workspace'; include 'includes/nurse_navbar.php'; ?>

    <!-- 1. Patient Selection Grid Dashboard -->
    <div class="nopt-dashboard" id="nopt-state">
      <div class="dash-header">
        <div>
          <h3 style="margin: 0; font-size: 1.3rem; font-weight: 800; color: var(--gm-primary); display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-hospital-user"></i> Active Inpatients 
            <span class="chip-mini"><?php echo count($assignedPatients); ?> Admitted</span>
          </h3>
          <p style="margin: 4px 0 0 0; font-size: 0.84rem; color: var(--gm-text-muted);">
            <?php echo $nurseWard ? htmlspecialchars($nurseWard['floor_name'] . ' • ' . $nurseWard['ward_name'] . ' (' . ($nurseWard['room_type'] ?: 'Ward') . ')') : 'Select a patient below to record charts, vitals, test orders, and medications.'; ?>
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
                <span class="chip-mini"><i class="fas fa-bed"></i> <?php echo htmlspecialchars(($p['room_type'] ?? 'Ward') . ' - ' . ($p['room_number'] ?? 'Bed')); ?></span>
                <span class="chip-mini"><i class="fas fa-venus-mars"></i> <?php echo htmlspecialchars(($p['age'] ?? '-') . 'Y / ' . ($p['sex'] ?? '-')); ?></span>
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
          <h3 style="color: var(--gm-primary);">No Active Inpatients</h3>
          <p>There are currently no admitted patients assigned to this ward.</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- 2. Sticky Patient Banner (when patient is selected) -->
    <div class="pt-banner" id="pt-banner" style="display: none;">
      <div class="pt-banner-av" id="pt-av">PT</div>
      <div class="pt-banner-info">
        <div class="pt-banner-nm" id="pt-nm">–</div>
        <div class="pt-banner-chips" id="pt-chips"></div>
      </div>
      <div class="pt-banner-ac">
        <button class="pba ksheet" onclick="goToKSheet()"><i class="fas fa-file-medical-alt"></i> View K-Sheet</button>
        <button class="pba warn" onclick="openDischargeModal()"><i class="fas fa-bell"></i> Notify Discharge</button>
        <button class="pba sec" onclick="openSearch()"><i class="fas fa-exchange-alt"></i> Change Patient</button>
      </div>
    </div>

    <!-- 3. Main Workspace Form Layout -->
    <div id="ws-layout" style="display: none;">

      <!-- Quick Category Navigation Filter Tabs -->
      <div class="workspace-tabs">
        <button class="tab-pill active" onclick="filterCategory('all', this)"><i class="fas fa-th-large"></i> All Sections</button>
        <button class="tab-pill" onclick="filterCategory('cat-vitals', this)"><i class="fas fa-heartbeat"></i> Vitals & Activity</button>
        <button class="tab-pill" onclick="filterCategory('cat-doctor', this)"><i class="fas fa-user-md"></i> Doctor & Notes</button>
        <button class="tab-pill" onclick="filterCategory('cat-orders', this)"><i class="fas fa-pills"></i> Lab & Pharmacy</button>
        <button class="tab-pill" onclick="filterCategory('cat-treatments', this)"><i class="fas fa-lungs"></i> Special Treatments</button>
      </div>

      <div class="ws-grid-new">

        <!-- 1. Activity Record -->
        <div class="card-new full-width ws-sec cat-vitals" id="s-act">
          <div class="card-title-new">
            <span><i class="fas fa-clipboard-list"></i> 1. Admission & Activity Record</span>
          </div>
          <div class="split-card card-body" id="f-act">
            <div class="split-left">
              <div class="fg">
                <div class="fmg">
                  <label>Activity Status</label>
                  <select name="status">
                    <option>Active Treatment</option>
                    <option>Discharged</option>
                    <option>LAMA</option>
                    <option>Referred Out</option>
                  </select>
                </div>
                <div class="fmg">
                  <label>Current Ward / Room</label>
                  <input type="text" name="ward_room" id="act-wr" placeholder="e.g. General Ward / 1101">
                </div>
                <div class="fmg">
                  <label>Admission Date & Time</label>
                  <input type="datetime-local" name="adm_date">
                </div>
                <div class="fmg">
                  <label>Discharge Date & Time</label>
                  <input type="datetime-local" name="dis_date">
                </div>
                <div class="fmg">
                  <label>Primary Consultant</label>
                  <select name="consultant">
                    <option value="">-- Select Doctor --</option>
                    <?php foreach($allDoctors as $doc): ?>
                      <option value="<?php echo htmlspecialchars($doc); ?>"><?php echo htmlspecialchars($doc); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="fmg">
                  <label>Reference Doctor</label>
                  <select name="ref_doctor">
                    <option value="">-- Select Doctor --</option>
                    <?php foreach($allDoctors as $doc): ?>
                      <option value="<?php echo htmlspecialchars($doc); ?>"><?php echo htmlspecialchars($doc); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <button class="btn-sv-out btn-sv" data-ct="activity_record" data-f="f-act"><i class="fas fa-plus"></i> Add Activity Record</button>
            </div>
          </div>
        </div>

        <!-- 2. Vitals (BP Chart) -->
        <div class="card-new ws-sec cat-vitals" id="s-bp">
          <div class="card-title-new">
            <span><i class="fas fa-heartbeat"></i> 2. BP & Vitals Chart</span>
          </div>
          <div class="split-card card-body" id="f-bp">
            <div class="split-left">
              <div class="fg">
                <div class="fmg"><label>Date</label><input type="date" name="bp_date"></div>
                <div class="fmg"><label>Time</label><input type="time" name="bp_time"></div>
                <div class="fmg"><label>BP (mmHg)</label><input type="text" name="bp_value" placeholder="e.g. 120/80"></div>
                <div class="fmg"><label>Pulse (bpm)</label><input type="number" name="bp_pulse" placeholder="e.g. 72"></div>
                <div class="fmg"><label>Temp (°F)</label><input type="number" name="bp_temp" step="0.1" placeholder="e.g. 98.6"></div>
                <div class="fmg"><label>SpO2 (%)</label><input type="number" name="bp_spo2" placeholder="e.g. 98"></div>
                <div class="fmg" style="grid-column: 1 / -1;"><label>Nurse Signature</label><input type="text" name="bp_nurse"></div>
              </div>
              <button class="btn-sv-out btn-sv" data-ct="bp_chart" data-f="f-bp"><i class="fas fa-plus"></i> Add Vitals</button>
            </div>
            <div class="split-right">
              <div class="ht-title"><i class="fas fa-history"></i> Recent Vitals Logs</div>
              <div class="ht-wrap">
                <table class="ht">
                  <thead><tr><th>Date & Time</th><th>BP</th><th>Pulse</th><th>Temp</th><th>By</th><th>Action</th></tr></thead>
                  <tbody id="h-bp"><tr class="et"><td colspan="6">No records yet.</td></tr></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- 3. GRBS Chart -->
        <div class="card-new ws-sec cat-vitals" id="s-gr">
          <div class="card-title-new">
            <span><i class="fas fa-vial"></i> 3. GRBS (Blood Sugar) Chart</span>
          </div>
          <div class="split-card card-body" id="f-gr">
            <div class="split-left">
              <div class="fg">
                <div class="fmg"><label>Date</label><input type="date" name="grbs_date"></div>
                <div class="fmg"><label>Time</label><input type="time" name="grbs_time"></div>
                <div class="fmg"><label>GRBS (mg/dL)</label><input type="number" name="grbs_value" placeholder="e.g. 120"></div>
                <div class="fmg"><label>Nurse Signature</label><input type="text" name="grbs_nurse"></div>
              </div>
              <button class="btn-sv-out btn-sv" data-ct="grbs_chart" data-f="f-gr"><i class="fas fa-plus"></i> Add Blood Sugar</button>
            </div>
            <div class="split-right">
              <div class="ht-title"><i class="fas fa-history"></i> Recent GRBS Logs</div>
              <div class="ht-wrap">
                <table class="ht">
                  <thead><tr><th>Date & Time</th><th>GRBS</th><th>By</th><th>Action</th></tr></thead>
                  <tbody id="h-gr"><tr class="et"><td colspan="4">No records yet.</td></tr></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- 4. Doctor Visits -->
        <div class="card-new ws-sec cat-doctor" id="s-vi">
          <div class="card-title-new">
            <span><i class="fas fa-user-md"></i> 4. Consultant Round Visits</span>
          </div>
          <div class="split-card card-body" id="f-vi">
            <div class="split-left">
              <div class="fg">
                <div class="fmg"><label>Visit Date</label><input type="date" name="date"></div>
                <div class="fmg"><label>Time</label><input type="time" name="time"></div>
                <div class="fmg">
                  <label>Doctor Name</label>
                  <select name="consultant">
                    <option value="">-- Select Doctor --</option>
                    <?php foreach($allDoctors as $doc): ?>
                      <option value="<?php echo htmlspecialchars($doc); ?>"><?php echo htmlspecialchars($doc); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="fmg">
                  <label>Shift</label>
                  <select name="shift">
                    <option value="">-- Select Shift --</option>
                    <option>Morning</option>
                    <option>Afternoon</option>
                    <option>Evening</option>
                    <option>Night</option>
                  </select>
                </div>
                <div class="fmg" style="grid-column: 1 / -1;"><label>Doctor Remarks</label><input type="text" name="remarks" placeholder="Enter clinical observations or round instructions..."></div>
              </div>
              <button class="btn-sv-out btn-sv" data-ct="consultant_visit" data-f="f-vi"><i class="fas fa-plus"></i> Add Round Visit</button>
            </div>
            <div class="split-right">
              <div class="ht-title"><i class="fas fa-history"></i> Recent Visit Logs</div>
              <div class="ht-wrap">
                <table class="ht">
                  <thead><tr><th>Date & Time</th><th>Doctor</th><th>Remarks</th><th>By</th><th>Action</th></tr></thead>
                  <tbody id="h-vi"><tr class="et"><td colspan="5">No records yet.</td></tr></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- 5. Ward Transfer -->
        <div class="card-new ws-sec cat-doctor" id="s-tr">
          <div class="card-title-new">
            <span><i class="fas fa-bed"></i> 5. Ward / Bed Transfer</span>
          </div>
          <div class="split-card card-body" id="f-tr">
            <div class="split-left">
              <div class="fg">
                <div class="fmg"><label>Transfer Date & Time</label><input type="datetime-local" name="transfer_date"></div>
                <div class="fmg">
                  <label>From Ward</label>
                  <select name="from_ward">
                    <option value="">-- Select Ward --</option>
                    <?php foreach($allWards as $ward): ?>
                      <option value="<?php echo htmlspecialchars($ward); ?>"><?php echo htmlspecialchars($ward); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="fmg">
                  <label>To Ward</label>
                  <select name="to_ward">
                    <option value="">-- Select Ward --</option>
                    <?php foreach($allWards as $ward): ?>
                      <option value="<?php echo htmlspecialchars($ward); ?>"><?php echo htmlspecialchars($ward); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="fmg" style="grid-column: 1 / -1;"><label>Reason for Transfer</label><input type="text" name="transfer_remarks" placeholder="e.g. Upgraded to ICU, Shifted to Room..."></div>
              </div>
              <button class="btn-sv-out btn-sv" data-ct="ward_transfer" data-f="f-tr"><i class="fas fa-plus"></i> Add Transfer</button>
            </div>
            <div class="split-right">
              <div class="ht-title"><i class="fas fa-history"></i> Transfer Logs</div>
              <div class="ht-wrap">
                <table class="ht">
                  <thead><tr><th>Date</th><th>From</th><th>To</th><th>Reason</th><th>Action</th></tr></thead>
                  <tbody id="h-tr"><tr class="et"><td colspan="5">No records yet.</td></tr></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- 6. Nursing Notes -->
        <div class="card-new ws-sec cat-doctor" id="s-nn">
          <div class="card-title-new">
            <span><i class="fas fa-clipboard-check"></i> 6. Nursing Shift Notes</span>
          </div>
          <div class="split-card card-body" id="f-nn">
            <div class="split-left">
              <div class="fg">
                <div class="fmg"><label>Date</label><input type="date" name="nurse_date"></div>
                <div class="fmg"><label>Time</label><input type="time" name="nurse_time"></div>
                <div class="fmg"><label>Units / Care Items</label><input type="text" name="nurse_units" placeholder="e.g. IV Fluid 500ml"></div>
                <div class="fmg"><label>Signature</label><input type="text" name="nurse_sign"></div>
                <div class="fmg" style="grid-column: 1 / -1;"><label>Shift Nursing Note</label><textarea name="nurse_part" rows="2" placeholder="Record patient status, symptoms, responses to medication..."></textarea></div>
              </div>
              <button class="btn-sv-out btn-sv" data-ct="nurse_record" data-f="f-nn"><i class="fas fa-plus"></i> Add Note</button>
            </div>
            <div class="split-right">
              <div class="ht-title"><i class="fas fa-history"></i> Recent Notes</div>
              <div class="ht-wrap">
                <table class="ht">
                  <thead><tr><th>Date & Time</th><th>Note</th><th>Nurse</th><th>Action</th></tr></thead>
                  <tbody id="h-nn"><tr class="et"><td colspan="4">No records yet.</td></tr></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- 7. Tests Order -->
        <div class="card-new ws-sec cat-orders" id="s-ts">
          <div class="card-title-new">
            <span><i class="fas fa-microscope"></i> 7. Diagnostic Tests Order</span>
          </div>
          <div class="split-card card-body" id="f-ts">
            <div class="split-left">
              <div class="fg">
                <div class="fmg" style="position: relative; grid-column: 1 / -1;">
                  <label>Search Diagnostic Test (Lab, Radiology, ECG)</label>
                  <input type="text" id="ts-input" placeholder="Type test name e.g. CBC, X-Ray, Lipid Profile..." autocomplete="off">
                  <div id="ts-results"></div>
                </div>
              </div>
              <div id="ts-cart" style="margin-top: 10px;"></div>
              <button class="btn-sv-out btn-sv" id="ts-save-btn" onclick="saveTests()"><i class="fas fa-shopping-cart"></i> Submit Tests Order</button>
            </div>
            <div class="split-right">
              <div class="ht-title"><i class="fas fa-history"></i> Ordered Tests History</div>
              <div class="ht-wrap">
                <table class="ht">
                  <thead><tr><th>Date</th><th>Test Name</th><th>Category</th><th>Qty</th></tr></thead>
                  <tbody id="h-ts"><tr class="et"><td colspan="4">No records yet.</td></tr></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- 8. Pharmacy Order -->
        <div class="card-new ws-sec cat-orders" id="s-ph">
          <div class="card-title-new">
            <span><i class="fas fa-pills"></i> 8. Pharmacy Medicine Order</span>
          </div>
          <div class="split-card card-body" id="f-ph">
            <div class="split-left">
              <div class="fg">
                <div class="fmg" style="position: relative; grid-column: 1 / -1;">
                  <label>Search Medicine from Pharmacy</label>
                  <input type="text" id="ph-input" placeholder="Type brand or generic name e.g. Paracetamol, Pantoprazole..." autocomplete="off">
                  <div id="ph-results"></div>
                </div>
              </div>
              <div id="ph-cart" style="margin-top: 10px;"></div>
              <button class="btn-sv-out btn-sv" id="ph-save-btn" onclick="savePharmacy()"><i class="fas fa-paper-plane"></i> Submit Pharmacy Order</button>
            </div>
            <div class="split-right">
              <div class="ht-title"><i class="fas fa-history"></i> Ordered Medicines</div>
              <div class="ht-wrap">
                <table class="ht">
                  <thead><tr><th>Date</th><th>Medicine</th><th>Batch</th><th>Qty</th></tr></thead>
                  <tbody id="h-ph"><tr class="et"><td colspan="4">No records yet.</td></tr></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- 9A. Nebulization Record -->
        <div class="card-new ws-sec cat-treatments" id="s-nb">
          <div class="card-title-new">
            <span><i class="fas fa-wind"></i> 9A. Nebulization Record</span>
          </div>
          <div class="split-card card-body" id="f-nb">
            <div class="split-left">
              <div class="fg">
                <div class="fmg"><label>Date</label><input type="date" name="nebu_date"></div>
                <div class="fmg"><label>Time</label><input type="time" name="nebu_time"></div>
                <div class="fmg"><label>Drug / Medicine</label><input type="text" name="nebu_drug" placeholder="e.g. Duolin, Budecort"></div>
                <div class="fmg"><label>Frequency</label><input type="text" name="nebu_freq" placeholder="e.g. TID / Q8H / SOS"></div>
                <div class="fmg" style="grid-column: 1 / -1;"><label>Nurse Signature</label><input type="text" name="nebu_nurse"></div>
              </div>
              <button class="btn-sv-out btn-sv" data-ct="nebulization_chart" data-f="f-nb"><i class="fas fa-plus"></i> Add Nebulization</button>
            </div>
            <div class="split-right">
              <div class="ht-title"><i class="fas fa-history"></i> Recent Nebulization</div>
              <div class="ht-wrap">
                <table class="ht">
                  <thead><tr><th>Date & Time</th><th>Medicine (Freq)</th><th>Nurse</th><th>Action</th></tr></thead>
                  <tbody id="h-nb"><tr class="et"><td colspan="4">No records yet.</td></tr></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- 9B. Dialysis Record -->
        <div class="card-new ws-sec cat-treatments" id="s-di">
          <div class="card-title-new">
            <span><i class="fas fa-filter"></i> 9B. Dialysis Record</span>
          </div>
          <div class="split-card card-body" id="f-di">
            <div class="split-left">
              <div class="fg">
                <div class="fmg"><label>Date</label><input type="date" name="dia_date"></div>
                <div class="fmg"><label>Duration</label><input type="text" name="dia_dur" class="tcd" placeholder="Auto / e.g. 4h"></div>
                <div class="fmg"><label>Start Time</label><input type="time" name="dia_start" class="tcs" onchange="calcDur(this)"></div>
                <div class="fmg"><label>End Time</label><input type="time" name="dia_end" class="tce" onchange="calcDur(this)"></div>
                <div class="fmg" style="grid-column: 1 / -1;"><label>Nurse Signature</label><input type="text" name="dia_nurse"></div>
              </div>
              <button class="btn-sv-out btn-sv" data-ct="dialysis_chart" data-f="f-di"><i class="fas fa-plus"></i> Add Dialysis</button>
            </div>
            <div class="split-right">
              <div class="ht-title"><i class="fas fa-history"></i> Recent Dialysis</div>
              <div class="ht-wrap">
                <table class="ht">
                  <thead><tr><th>Date</th><th>Duration</th><th>Start - End</th><th>Nurse</th><th>Action</th></tr></thead>
                  <tbody id="h-di"><tr class="et"><td colspan="5">No records yet.</td></tr></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- 9C. Oxygen Therapy -->
        <div class="card-new ws-sec cat-treatments" id="s-ox">
          <div class="card-title-new">
            <span><i class="fas fa-lungs"></i> 9C. Oxygen Therapy</span>
          </div>
          <div class="split-card card-body" id="f-ox">
            <div class="split-left">
              <div class="fg">
                <div class="fmg"><label>Date</label><input type="date" name="oxy_date"></div>
                <div class="fmg"><label>Flow Rate (L/min)</label><input type="text" name="oxy_flow" placeholder="e.g. 2 L/min"></div>
                <div class="fmg"><label>Start Time</label><input type="time" name="oxy_start" class="tcs" onchange="calcDur(this)"></div>
                <div class="fmg"><label>End Time</label><input type="time" name="oxy_end" class="tce" onchange="calcDur(this)"></div>
                <div class="fmg" style="grid-column: 1 / -1;"><label>Duration</label><input type="text" name="oxy_dur" class="tcd" placeholder="Auto / e.g. 2h"></div>
                <div class="fmg" style="grid-column: 1 / -1;"><label>Nurse Signature</label><input type="text" name="oxy_nurse"></div>
              </div>
              <button class="btn-sv-out btn-sv" data-ct="oxygen_chart" data-f="f-ox"><i class="fas fa-plus"></i> Add Oxygen</button>
            </div>
            <div class="split-right">
              <div class="ht-title"><i class="fas fa-history"></i> Recent Oxygen Therapy</div>
              <div class="ht-wrap">
                <table class="ht">
                  <thead><tr><th>Date</th><th>Flow Rate</th><th>Duration / Time</th><th>Nurse</th><th>Action</th></tr></thead>
                  <tbody id="h-ox"><tr class="et"><td colspan="5">No records yet.</td></tr></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- 9D. Ventilator Support -->
        <div class="card-new ws-sec cat-treatments" id="s-ve">
          <div class="card-title-new">
            <span><i class="fas fa-procedures"></i> 9D. Ventilator Support</span>
          </div>
          <div class="split-card card-body" id="f-ve">
            <div class="split-left">
              <div class="fg">
                <div class="fmg"><label>Date</label><input type="date" name="vent_date"></div>
                <div class="fmg"><label>Vent Mode</label><select name="vent_mode"><option>CMV</option><option>SIMV</option><option>CPAP</option><option>BiPAP</option></select></div>
                <div class="fmg"><label>Start Time</label><input type="time" name="vent_start" class="tcs" onchange="calcDur(this)"></div>
                <div class="fmg"><label>End Time</label><input type="time" name="vent_end" class="tce" onchange="calcDur(this)"></div>
                <div class="fmg" style="grid-column: 1 / -1;"><label>Duration</label><input type="text" name="vent_dur" class="tcd" placeholder="Auto / e.g. 6h"></div>
                <div class="fmg" style="grid-column: 1 / -1;"><label>Nurse Signature</label><input type="text" name="vent_nurse"></div>
              </div>
              <button class="btn-sv-out btn-sv" data-ct="ventilation_chart" data-f="f-ve"><i class="fas fa-plus"></i> Add Ventilator</button>
            </div>
            <div class="split-right">
              <div class="ht-title"><i class="fas fa-history"></i> Recent Ventilator</div>
              <div class="ht-wrap">
                <table class="ht">
                  <thead><tr><th>Date</th><th>Mode</th><th>Duration / Time</th><th>Nurse</th><th>Action</th></tr></thead>
                  <tbody id="h-ve"><tr class="et"><td colspan="5">No records yet.</td></tr></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- 9E. Blood Transfusion Record -->
        <div class="card-new full-width ws-sec cat-treatments" id="s-bl">
          <div class="card-title-new">
            <span><i class="fas fa-syringe"></i> 9E. Blood Transfusion Record</span>
          </div>
          <div class="split-card card-body" id="f-bl">
            <div class="split-left">
              <div class="fg">
                <div class="fmg"><label>Date</label><input type="date" name="trans_date"></div>
                <div class="fmg"><label>Blood Group</label><input type="text" name="blood_group" placeholder="e.g. O+ / AB+"></div>
                <div class="fmg"><label>Bag Number</label><input type="text" name="bag_number" placeholder="e.g. 2563"></div>
                <div class="fmg"><label>Qty (ml)</label><input type="number" name="quantity" placeholder="350"></div>
                <div class="fmg"><label>Vitals During Transfusion</label><input type="text" name="vitals_during" placeholder="BP, Pulse..."></div>
                <div class="fmg"><label>Nurse Signature</label><input type="text" name="nurse_sign"></div>
              </div>
              <button class="btn-sv-out btn-sv" data-ct="blood_transfusion" data-f="f-bl"><i class="fas fa-plus"></i> Add Transfusion</button>
            </div>
            <div class="split-right">
              <div class="ht-title"><i class="fas fa-history"></i> Recent Transfusion History</div>
              <div class="ht-wrap">
                <table class="ht">
                  <thead><tr><th>Date</th><th>Group & Bag #</th><th>Quantity</th><th>Vitals</th><th>Nurse</th><th>Action</th></tr></thead>
                  <tbody id="h-bl"><tr class="et"><td colspan="6">No records yet.</td></tr></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Sticky Global Save Bar -->
        <div class="sticky-save-bar">
          <div>
            <h4 style="margin: 0; font-size: 1.05rem; font-weight: 800; color: var(--gm-primary);">Batch Save Clinical Forms</h4>
            <p style="margin: 3px 0 0 0; font-size: 0.82rem; color: var(--gm-text-muted);">Saves all modified sections and carts directly to the patient's daily K-Sheet.</p>
          </div>
          <button id="btn-save-all" class="btn-sv-main" onclick="saveAllRecords()">
            <i class="fas fa-save"></i> Save All Entered Records
          </button>
        </div>

      </div><!-- /ws-grid-new -->
    </div><!-- /ws-layout -->

  </div><!-- /content-wrapper -->
</div><!-- /main-layout -->

<!-- Discharge Modal -->
<div id="dis-modal" style="position:fixed;inset:0;background:rgba(31, 107, 74, 0.4);backdrop-filter:blur(4px);z-index:9000;display:none;align-items:center;justify-content:center">
  <div style="background:#ffffff;border-radius:14px;max-width:420px;width:90%;overflow:hidden;box-shadow:0 25px 50px rgba(31, 107, 74, 0.25);border:1.5px solid var(--gm-border);">
    <div style="background:var(--gm-primary);padding:16px 20px;font-weight:800;font-size:0.95rem;color:#f3efe6;display:flex;align-items:center;gap:10px;">
      <i class="fas fa-bell"></i> Send Discharge Notification
    </div>
    <div style="padding:20px;font-size:0.9rem;color:var(--gm-text-body);line-height:1.6">
      Are you sure you want to notify Admin and Billing that this patient is ready for discharge clearance?
    </div>
    <div style="padding:14px 20px;background:var(--gm-bg);border-top:1px solid var(--gm-border);display:flex;gap:10px;justify-content:flex-end">
      <button onclick="closeDischargeModal()" style="padding:8px 16px;border-radius:8px;font-weight:700;font-size:0.84rem;border:1.5px solid var(--gm-border);background:#ffffff;color:var(--gm-primary);cursor:pointer">Cancel</button>
      <button id="dis-btn" onclick="doDischarge()" style="padding:8px 18px;border-radius:8px;font-weight:700;font-size:0.84rem;border:none;background:var(--gm-primary);color:#f3efe6;cursor:pointer"><i class="fas fa-check"></i> Yes, Notify</button>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
const NN = "<?php echo addslashes($nurseName); ?>";
let cp = null;
let tsCart = [], phCart = [];

/* ── Category Filter Tabs ── */
function filterCategory(cat, btn) {
    document.querySelectorAll('.tab-pill').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    
    const sections = document.querySelectorAll('.ws-sec');
    if (cat === 'all') {
        sections.forEach(s => s.style.display = 'flex');
    } else {
        sections.forEach(s => {
            if (s.classList.contains(cat)) {
                s.style.display = 'flex';
            } else {
                s.style.display = 'none';
            }
        });
    }
}

/* ── Treatment Sub-Tabs Switcher ── */
function switchTreatment(panelId, btn) {
    document.querySelectorAll('.t-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    
    document.querySelectorAll('.t-panel').forEach(p => p.style.display = 'none');
    const target = document.getElementById(panelId);
    if(target) target.style.display = 'flex';
}

/* ── Patient Search / Dashboard ── */
function openSearch(){
  document.getElementById('ws-layout').style.display='none';
  document.getElementById('pt-banner').style.display='none';
  document.getElementById('nopt-state').style.display='block';
  cp = null;
  
  const searchInput = document.getElementById('dash-search');
  if(searchInput) {
      searchInput.value = '';
      searchInput.dispatchEvent(new Event('input'));
      searchInput.focus();
  }
}

// Direct jump to K-Sheet
function goToKSheet(){
  if(!cp) return;
  window.location.href = `k_sheet_view.php?patient_id=${encodeURIComponent(cp.patient_id)}&admission_id=${encodeURIComponent(cp.admission_id || '')}`;
}

// Live filter for the dashboard cards
const ds = document.getElementById('dash-search');
if(ds) {
    ds.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        const cards = document.querySelectorAll('.dash-card');
        let visibleCount = 0;
        cards.forEach(c => {
            const text = c.getAttribute('data-search') || '';
            if(text.includes(q)) {
                c.style.display = 'flex';
                visibleCount++;
            } else {
                c.style.display = 'none';
            }
        });
        const noRes = document.getElementById('dash-no-res');
        if(noRes) noRes.style.display = (visibleCount === 0) ? 'block' : 'none';
    });
}

function selectPatient(p){
  cp=p;
  document.getElementById('nopt-state').style.display='none';
  const ini=((p.first_name||'')[0]||'')+((p.last_name||'')[0]||'')||'PT';
  document.getElementById('pt-av').textContent=ini.toUpperCase();
  document.getElementById('pt-nm').textContent=`${p.first_name} ${p.last_name||''}`;
  document.getElementById('pt-chips').innerHTML=[
    {ic:'fa-id-card',l:'PID',v:p.patient_id},
    {ic:'fa-file-invoice',l:'IP#',v:p.admission_id || 'N/A'},
    {ic:'fa-bed',l:'Bed',v:`${p.room_type||'Ward'}/${p.room_number||'Bed'}`},
    {ic:'fa-user',l:'Age/Sex',v:`${p.age||'?'}Y / ${p.sex||'?'}`},
    {ic:'fa-tint',l:'Blood',v:p.blood_group||'N/A'}
  ].map(c=>`<span class="ptchip"><i class="fas ${c.ic}"></i><strong>${c.l}:</strong> ${c.v}</span>`).join('');
  
  document.getElementById('pt-banner').style.display='flex';
  document.getElementById('ws-layout').style.display='block';
  
  const ci=document.querySelector('#f-act select[name="consultant"]');
  if(ci&&p.doctor_name){ Array.from(ci.options).forEach(o=>{if(o.value===p.doctor_name)o.selected=true;}); }
  const wi=document.getElementById('act-wr');
  if(wi&&(p.room_type||p.room_number))wi.value=`${p.room_type||''}/${p.room_number||''}`;
  
  autoFill();
  loadAllRecords();
}

/* ── Auto-fill dates & Nurse Signature ── */
function autoFill(ctx=document){
  const now=new Date();
  const ym=`${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}`;
  const hm=`${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;
  ctx.querySelectorAll('input[type=date]').forEach(i=>{if(!i.value)i.value=ym;});
  ctx.querySelectorAll('input[type=time]').forEach(i=>{if(!i.value)i.value=hm;});
  ctx.querySelectorAll('input[type=datetime-local]').forEach(i=>{if(!i.value)i.value=ym+'T'+hm;});
  if(NN) ctx.querySelectorAll('input[name*="_nurse"],input[name*="_sign"],input[name="nurse_sign"]').forEach(i=>{if(!i.value)i.value=NN;});
}

function clrF(id){
  const c=document.getElementById(id); if(!c)return;
  c.querySelectorAll('input:not([type=hidden]),select,textarea').forEach(e=>e.value='');
  autoFill(c);
}

/* ── Duration auto calc ── */
function calcDur(el){
  const c=el.closest('.card-body');
  const s=c?.querySelector('.tcs'), e=c?.querySelector('.tce'), d=c?.querySelector('.tcd');
  if(!s?.value||!e?.value)return;
  let st=new Date('1970-01-01T'+s.value+':00'), en=new Date('1970-01-01T'+e.value+':00');
  if(en<st)en.setDate(en.getDate()+1);
  const ms=en-st; d.value=Math.floor(ms/3600000)+'h '+Math.round((ms%3600000)/60000)+'m';
}

/* ── Universal Save & Individual Section Saves ── */
document.addEventListener('input', e => { const f=e.target.closest('.card-body'); if(f) f.classList.add('is-dirty'); });
document.addEventListener('change', e => { const f=e.target.closest('.card-body'); if(f) f.classList.add('is-dirty'); });

// Individual Section Add Button Handler
document.addEventListener('click', async function(e) {
  const btn = e.target.closest('.btn-sv');
  if (!btn || btn.id === 'btn-save-all' || btn.id === 'ts-save-btn' || btn.id === 'ph-save-btn') return;
  
  e.preventDefault();
  if (!cp) {
    showToast('Please select an admitted patient first!', true);
    return;
  }
  
  const ct = btn.getAttribute('data-ct');
  const formId = btn.getAttribute('data-f');
  const f = document.getElementById(formId);
  if (!f || !ct) return;
  
  const originalText = btn.innerHTML;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
  btn.disabled = true;
  
  const fd = new FormData();
  fd.append('patient_id', cp.patient_id);
  fd.append('admission_id', cp.admission_id || '');
  fd.append('chart_type', ct);
  
  let i = 0;
  f.querySelectorAll('input,select,textarea').forEach(inp => {
    const k = inp.name || ('f' + i);
    if (inp.type === 'file') {
      if (inp.files.length > 0) fd.append(k, inp.files[0]);
    } else {
      fd.append(k, inp.value);
    }
    i++;
  });
  
  try {
    const r = await fetch('api/save_clinical_record.php', { method: 'POST', body: fd });
    const text = await r.text();
    let res;
    try { res = JSON.parse(text); } catch(err) { throw new Error(text.substring(0, 80)); }
    
    if (res.success) {
      const label = ct.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
      showToast(`✅ ${res.message || (label + ' saved successfully!')}`);
      f.classList.remove('is-dirty');
      cancelEdit(formId);
      loadAllRecords();
    } else {
      showToast('❌ Error: ' + (res.message || 'Failed to save record'), true);
    }
  } catch(err) {
    console.error('Section save error:', err);
    showToast('Network error while saving record.', true);
  } finally {
    btn.innerHTML = originalText;
    btn.disabled = false;
  }
});

async function saveAllRecords(){
  if(!cp){showToast('Please select a patient first!',true);return;}
  
  const saveTasks = [];
  const results = [];
  const dirtyForms = document.querySelectorAll('.card-body.is-dirty');
  
  dirtyForms.forEach(f => {
    const btn = f.querySelector('.btn-sv[data-ct]');
    if(btn){
      const ct = btn.getAttribute('data-ct');
      const sectionName = ct.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
      const fd = new FormData();
      fd.append('patient_id',cp.patient_id); 
      fd.append('admission_id',cp.admission_id || ''); 
      fd.append('chart_type',ct);
      let i=0; 
      f.querySelectorAll('input:not([type=hidden]),select,textarea').forEach(inp=>{
        const k=inp.name||('f'+i);
        if(inp.type==='file'){ if(inp.files.length>0)fd.append(k,inp.files[0]); }
        else fd.append(k,inp.value);
        i++;
      });
      saveTasks.push(async () => {
        try {
          const r = await fetch('api/save_clinical_record.php',{method:'POST',body:fd});
          const text = await r.text();
          let res;
          try { res = JSON.parse(text); } catch(e) { throw new Error('Invalid server response: ' + text.substring(0, 50)); }
          
          if(res.success) { f.classList.remove('is-dirty'); clrF(f.id); }
          results.push({ section: sectionName, success: res.success, err: res.message });
        } catch(e) { results.push({ section: sectionName, success: false, err: e.message }); }
      });
    }
  });

  if(tsCart.length > 0) {
    saveTasks.push(async () => {
      try {
        const r = await fetch('api/save_tests.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({patient_id:cp.patient_id,admission_id:cp.admission_id || '',cart:tsCart})});
        const res = await r.json();
        if(res.success){tsCart=[];renderTestCart();}
        results.push({ section: 'Tests Order', success: res.success, err: res.message });
      } catch(e) { results.push({ section: 'Tests Order', success: false, err: e.message }); }
    });
  }
  if(phCart.length > 0) {
    saveTasks.push(async () => {
      try {
        const r = await fetch('api/save_pharmacy_order.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({patient_id:cp.patient_id,admission_id:cp.admission_id || '',cart:phCart})});
        const res = await r.json();
        if(res.success){phCart=[];renderPhCart();}
        results.push({ section: 'Pharmacy Order', success: res.success, err: res.message });
      } catch(e) { results.push({ section: 'Pharmacy Order', success: false, err: e.message }); }
    });
  }

  if(saveTasks.length === 0){ showToast('No new data entered to save.', true); return; }

  const btn = document.getElementById('btn-save-all');
  const oh = btn.innerHTML;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving All Records...'; btn.disabled = true;

  try {
    for (const task of saveTasks) { await task(); }
    loadAllRecords();
    
    const successCount = results.filter(r => r.success).length;
    const failedCount = results.length - successCount;
    
    let msg = ``;
    if(successCount > 0) msg += `✅ Saved: ` + results.filter(r => r.success).map(r => r.section).join(', ') + `. `;
    if(failedCount > 0) {
      msg += `<br>❌ Failed:<br>` + results.filter(r => !r.success).map(r => `- ${r.section}: ${r.err || 'Unknown error'}`).join('<br>');
    }
    
    showToast(msg, failedCount > 0);
  } catch(e) { 
    showToast('Network error while saving.',true); 
  }
  finally { btn.innerHTML = oh; btn.disabled = false; }
}

/* ── Load All Records with Edit Option ── */
async function loadAllRecords(){
  if(!cp)return;
  try{
    const r=await fetch(`api/get_clinical_records.php?patient_id=${encodeURIComponent(cp.patient_id)}&admission_id=${encodeURIComponent(cp.admission_id || '')}`);
    const d=(await r.json())?.data||{};
    
    // 1. Ward Transfers
    rH('h-tr', d.ward_transfer || [], r => `
      <td>${r.transfer_date || r.date || r.created_date || ''}</td>
      <td>${r.from_ward || ''}</td>
      <td>${r.to_ward || ''}</td>
      <td>${r.transfer_remarks || r.remarks || ''}</td>
      <td style="text-align:center;"><button type="button" class="btn-edit-log" onclick='editLog("f-tr", ${JSON.stringify(r)})' title="Edit transfer"><i class="fas fa-edit"></i> Edit</button></td>
    `, 5);

    // 2. Consultant Visits
    rH('h-vi', d.consultant_visits || [], r => `
      <td>${r.date || r.created_date || ''} ${r.time || ''}</td>
      <td><strong>${r.consultant || r.doctor || ''}</strong></td>
      <td>${r.remarks || r.notes || ''}</td>
      <td>${r.created_by_name || ''}</td>
      <td style="text-align:center;"><button type="button" class="btn-edit-log" onclick='editLog("f-vi", ${JSON.stringify(r)})' title="Edit visit"><i class="fas fa-edit"></i> Edit</button></td>
    `, 5);

    // 3. GRBS (Blood Sugar)
    rH('h-gr', d.grbs_chart || [], r => `
      <td>${r.grbs_date || r.date || r.created_date || ''} ${r.grbs_time || r.time || ''}</td>
      <td><strong>${r.grbs_value || r.value || ''} mg/dL</strong></td>
      <td>${r.grbs_nurse || r.created_by_name || ''}</td>
      <td style="text-align:center;"><button type="button" class="btn-edit-log" onclick='editLog("f-gr", ${JSON.stringify(r)})' title="Edit GRBS"><i class="fas fa-edit"></i> Edit</button></td>
    `, 4);

    // 4. Nebulization
    rH('h-nb', d.nebulization_chart || [], r => `
      <td>${r.nebu_date || r.date || ''} ${r.nebu_time || r.time || ''}</td>
      <td><strong>${r.nebu_drug || r.medicine || '-'}</strong>${r.nebu_freq ? ' (' + r.nebu_freq + ')' : ''}</td>
      <td>${r.nebu_nurse || r.created_by_name || '-'}</td>
      <td style="text-align:center;"><button type="button" class="btn-edit-log" onclick='editLog("f-nb", ${JSON.stringify(r)})' title="Edit nebulization"><i class="fas fa-edit"></i> Edit</button></td>
    `, 4);

    // 5. BP & Vitals
    rH('h-bp', d.bp_chart || d.vitals || [], r => `
      <td>${r.bp_date || r.date || r.created_date || ''} ${r.bp_time || r.time || ''}</td>
      <td><strong>${r.bp_value || ((r.bp_systolic || '') + '/' + (r.bp_diastolic || ''))}</strong></td>
      <td>${r.bp_pulse || r.pulse || ''} bpm</td>
      <td>${r.bp_temp || r.temp || ''}</td>
      <td>${r.bp_nurse || r.nurse_sign || r.created_by_name || ''}</td>
      <td style="text-align:center;"><button type="button" class="btn-edit-log" onclick='editLog("f-bp", ${JSON.stringify(r)})' title="Edit vitals"><i class="fas fa-edit"></i> Edit</button></td>
    `, 6);

    // 6. Dialysis
    rH('h-di', d.dialysis_chart || [], r => `
      <td>${r.dia_date || r.date || ''}</td>
      <td><strong style="color:var(--gm-primary)">${r.dia_dur || r.duration || '-'}</strong></td>
      <td><small>${r.dia_start || r.start_time || ''}${r.dia_end ? ' - ' + r.dia_end : ''}</small></td>
      <td>${r.dia_nurse || r.created_by_name || '-'}</td>
      <td style="text-align:center;"><button type="button" class="btn-edit-log" onclick='editLog("f-di", ${JSON.stringify(r)})' title="Edit dialysis"><i class="fas fa-edit"></i> Edit</button></td>
    `, 5);

    // 7. Oxygen Therapy
    rH('h-ox', d.oxygen_chart || [], r => `
      <td>${r.oxy_date || r.date || ''}</td>
      <td><strong style="color:var(--gm-primary)">${r.oxy_flow || r.flow || '-'}</strong></td>
      <td><small>${r.oxy_dur || r.duration || (r.oxy_start ? (r.oxy_start + ' - ' + r.oxy_end) : '-')}</small></td>
      <td>${r.oxy_nurse || r.created_by_name || '-'}</td>
      <td style="text-align:center;"><button type="button" class="btn-edit-log" onclick='editLog("f-ox", ${JSON.stringify(r)})' title="Edit oxygen"><i class="fas fa-edit"></i> Edit</button></td>
    `, 5);

    // 8. Ventilator Support
    rH('h-ve', d.ventilation_chart || [], r => `
      <td>${r.vent_date || r.date || ''}</td>
      <td><span class="badge">${r.vent_mode || r.mode || '-'}</span></td>
      <td><small>${r.vent_dur || r.duration || (r.vent_start ? (r.vent_start + ' - ' + r.vent_end) : '-')}</small></td>
      <td>${r.vent_nurse || r.created_by_name || '-'}</td>
      <td style="text-align:center;"><button type="button" class="btn-edit-log" onclick='editLog("f-ve", ${JSON.stringify(r)})' title="Edit ventilator"><i class="fas fa-edit"></i> Edit</button></td>
    `, 5);

    // 9. Blood Transfusion
    rH('h-bl', d.blood_transfusion_chart || [], r => `
      <td>${r.trans_date || r.date || ''}</td>
      <td><strong style="color:var(--gm-primary)">${r.blood_group || ''}</strong> #${r.bag_number || r.bag_no || '-'}</td>
      <td>${r.quantity ? r.quantity + ' ml' : '-'}</td>
      <td><small>${r.vitals_during || r.vitals || '-'}</small></td>
      <td>${r.nurse_sign || r.nurse || r.created_by_name || '-'}</td>
      <td style="text-align:center;"><button type="button" class="btn-edit-log" onclick='editLog("f-bl", ${JSON.stringify(r)})' title="Edit transfusion"><i class="fas fa-edit"></i> Edit</button></td>
    `, 6);

    // 10. Nursing Notes
    rH('h-nn', d.nurses_record || [], r => `
      <td>${r.nurse_date || r.date || ''} ${r.nurse_time || r.time || ''}</td>
      <td>${r.nurse_part || r.particulars || ''}</td>
      <td>${r.nurse_sign || r.created_by_name || ''}</td>
      <td style="text-align:center;"><button type="button" class="btn-edit-log" onclick='editLog("f-nn", ${JSON.stringify(r)})' title="Edit note"><i class="fas fa-edit"></i> Edit</button></td>
    `, 4);
    
    // Tests history
    let at=[];
    (d.lab_tests||[]).forEach(t=>{const i=t.data||t;at.push({dt:t.created_date||t.date||'',nm:i.name||i.test_name||'',cat:'LAB',qty:i.qty||1});});
    (d.radiology_tests||[]).forEach(t=>{const i=t.data||t;at.push({dt:t.created_date||t.date||'',nm:i.name||i.test_name||'',cat:'RADIOLOGY',qty:i.qty||1});});
    (d.other_tests||[]).forEach(t=>{const i=t.data||t;at.push({dt:t.created_date||t.date||'',nm:i.name||i.test_name||'',cat:'OTHER',qty:i.qty||1});});
    at.sort((a,b)=>new Date(b.dt)-new Date(a.dt));
    document.getElementById('h-ts').innerHTML=at.length?at.map(t=>`<tr><td>${t.dt}</td><td><strong>${t.nm}</strong></td><td><span class="badge">${t.cat}</span></td><td>${t.qty}</td></tr>`).join(''):'<tr class="et"><td colspan="4">No tests ordered yet.</td></tr>';
    
    // Pharmacy history
    const phHistory=d.pharmacy_orders||[];
    document.getElementById('h-ph').innerHTML=phHistory.length?phHistory.map(o=>{
      const i=o.data||o;
      return `<tr><td>${o.created_date||i.date||''}</td><td><strong>${i.medicine||i.name||i.product_name||''}</strong></td><td>${i.batch||i.batch_no||'N/A'}</td><td>${i.qty||i.quantity||1}</td></tr>`;
    }).join(''):'<tr class="et"><td colspan="4">No pharmacy orders yet.</td></tr>';
  }catch(er){console.error('loadAllRecords:',er);}
}

function rH(tid,rows,fn,cols){
  const tb=document.getElementById(tid); if(!tb)return;
  tb.innerHTML=rows&&rows.length?[...rows].reverse().map(r=>`<tr>${fn(r)}</tr>`).join(''):`<tr class="et"><td colspan="${cols}">No records yet.</td></tr>`;
}

/* ── Tests Cart ── */
let tsT=null;
document.getElementById('ts-input').addEventListener('input',function(){
  clearTimeout(tsT); const q=this.value.trim(), res=document.getElementById('ts-results');
  if(q.length<2){res.style.display='none';return;}
  tsT=setTimeout(()=>{
    fetch('api/search_tests.php?type=all&q='+encodeURIComponent(q)).then(r=>r.json()).then(d=>{
      res.innerHTML='';
      if(d.success&&d.data.length>0){
        d.data.forEach(item=>{
          const cl=item.category?.toLowerCase().includes('lab')?'bl':item.category==='Other'?'bo':'br';
          const el=document.createElement('div'); el.className='ts-item';
          el.innerHTML=`<div><strong style="color:var(--gm-primary)">${item.name}</strong><br><small style="color:var(--gm-text-muted)">Category: ${item.category}</small></div><span class="badge"><i class="fas fa-plus"></i> Add</span>`;
          el.onclick=()=>addToTestCart(item); res.appendChild(el);
        });
      } else { res.innerHTML='<div style="padding:12px;text-align:center;color:var(--gm-text-muted);font-size:.82rem">No tests found.</div>'; }
      res.style.display='block';
    });
  },280);
});
document.addEventListener('click',e=>{if(!document.getElementById('ts-input').contains(e.target)&&!document.getElementById('ts-results').contains(e.target))document.getElementById('ts-results').style.display='none';});

function addToTestCart(item){
  document.getElementById('ts-results').style.display='none'; document.getElementById('ts-input').value='';
  const ex=tsCart.find(x=>x.id===item.id);
  if(ex)ex.qty++; else tsCart.push({id:item.id,name:item.name,category:item.category,qty:1});
  renderTestCart();
}
function renderTestCart(){
  const ca=document.getElementById('ts-cart');
  if(!tsCart.length){ca.innerHTML='';return;}
  ca.innerHTML=tsCart.map(t=>`<div class="cart-row"><div class="cart-row-n">${t.name} <span class="badge">${t.category}</span></div><input type="number" value="${t.qty}" min="1" onchange="tsCart.find(x=>x.id==='${t.id}').qty=parseInt(this.value)||1;renderTestCart()"><button class="rm-btn" onclick="tsCart=tsCart.filter(x=>x.id!=='${t.id}');renderTestCart()"><i class="fas fa-trash-alt"></i></button></div>`).join('');
}
async function saveTests(){
  if(!cp){showToast('No patient selected!',true);return;}
  if(!tsCart.length){showToast('Add at least one test.',true);return;}
  const b=document.getElementById('ts-save-btn'); b.innerHTML='<i class="fas fa-spinner fa-spin"></i> Saving...'; b.disabled=true;
  try{
    const r=await fetch('api/save_tests.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({patient_id:cp.patient_id,admission_id:cp.admission_id || '',cart:tsCart})});
    const res=await r.json();
    if(res.success){showToast('Tests order saved!');tsCart=[];renderTestCart();loadAllRecords();}
    else showToast('Error: '+(res.message||'Unknown'),true);
  }catch{showToast('Network error!',true);}
  finally{b.innerHTML='<i class="fas fa-shopping-cart"></i> Submit Tests Order';b.disabled=false;}
}

/* ── Pharmacy Cart ── */
let phT=null;
document.getElementById('ph-input').addEventListener('input',function(){
  clearTimeout(phT); const q=this.value.trim(), res=document.getElementById('ph-results');
  if(q.length<2){res.style.display='none';return;}
  phT=setTimeout(()=>{
    fetch('api/search_medicine.php?q='+encodeURIComponent(q)).then(r=>r.json()).then(d=>{
      res.innerHTML='';
      const items=d.data||d.medicines||d||[];
      if(Array.isArray(items)&&items.length>0){
        items.forEach(item=>{
          const el=document.createElement('div'); el.className='ph-item';
          el.innerHTML=`<div><strong style="color:var(--gm-primary)">${item.name||item.medicine_name||item.product_name}</strong><br><small style="color:var(--gm-text-muted)">Batch: ${item.batch_number||'N/A'} | Stock: ${item.quantity||item.stock||item.available_stock||'?'}</small></div><span class="badge"><i class="fas fa-plus"></i> Add</span>`;
          el.onclick=()=>addToPhCart(item); res.appendChild(el);
        });
      } else { res.innerHTML='<div style="padding:12px;text-align:center;color:var(--gm-text-muted);font-size:.82rem">No medicines found.</div>'; }
      res.style.display='block';
    });
  },280);
});
document.addEventListener('click',e=>{if(!document.getElementById('ph-input').contains(e.target)&&!document.getElementById('ph-results').contains(e.target))document.getElementById('ph-results').style.display='none';});

function addToPhCart(item){
  document.getElementById('ph-results').style.display='none'; document.getElementById('ph-input').value='';
  const id=item.id||item.medicine_id||item.product_id;
  const ex=phCart.find(x=>x.id===id);
  if(ex)ex.qty++; else phCart.push({id,name:item.name||item.medicine_name||item.product_name,batch:item.batch_number||'',stock:item.quantity||item.stock||item.available_stock||'?',qty:1});
  renderPhCart();
}
function renderPhCart(){
  const ca=document.getElementById('ph-cart');
  if(!phCart.length){ca.innerHTML='';return;}
  ca.innerHTML=phCart.map(m=>`<div class="cart-row"><div class="cart-row-n">${m.name} <span class="badge">Batch: ${m.batch||'N/A'}</span></div><input type="number" value="${m.qty}" min="1" onchange="phCart.find(x=>x.id==='${m.id}').qty=parseInt(this.value)||1;renderPhCart()"><button class="rm-btn" onclick="phCart=phCart.filter(x=>x.id!=='${m.id}');renderPhCart()"><i class="fas fa-trash-alt"></i></button></div>`).join('');
}
async function savePharmacy(){
  if(!cp){showToast('No patient selected!',true);return;}
  if(!phCart.length){showToast('Add at least one medicine.',true);return;}
  const b=document.getElementById('ph-save-btn'); b.innerHTML='<i class="fas fa-spinner fa-spin"></i> Submitting...'; b.disabled=true;
  try{
    const r=await fetch('api/save_pharmacy_order.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({patient_id:cp.patient_id,admission_id:cp.admission_id || '',cart:phCart})});
    const res=await r.json();
    if(res.success){showToast('Pharmacy order submitted!');phCart=[];renderPhCart();loadAllRecords();}
    else showToast('Error: '+(res.message||'Unknown'),true);
  }catch{showToast('Network error!',true);}
  finally{b.innerHTML='<i class="fas fa-paper-plane"></i> Submit Pharmacy Order';b.disabled=false;}
}

/* ── Edit Log & Cancel Edit ── */
function editLog(formId, data){
  const f = document.getElementById(formId);
  if (!f) return;
  
  // Set hidden tracking inputs: entry_id, _db_row_id, _arr_idx
  ['entry_id', '_db_row_id', '_arr_idx'].forEach(k => {
    let inp = f.querySelector(`input[name="${k}"]`);
    if (!inp) {
      inp = document.createElement('input');
      inp.type = 'hidden';
      inp.name = k;
      const fg = f.querySelector('.fg');
      if (fg) fg.appendChild(inp);
    }
    inp.value = (data[k] !== undefined && data[k] !== null) ? data[k] : '';
  });
  
  // Populate standard matching form inputs
  f.querySelectorAll('input:not([type=hidden]),select,textarea').forEach(inp => {
    const name = inp.name;
    if (!name) return;
    if (data[name] !== undefined && data[name] !== null && data[name] !== '') {
      inp.value = data[name];
    }
  });
  
  // Specific mappings for legacy fields
  if (formId === 'f-bp') {
    if (data.date && !data.bp_date) { const el = f.querySelector('input[name="bp_date"]'); if(el) el.value = data.date; }
    if (data.time && !data.bp_time) { const el = f.querySelector('input[name="bp_time"]'); if(el) el.value = data.time; }
    if (data.bp && !data.bp_value) { const el = f.querySelector('input[name="bp_value"]'); if(el) el.value = data.bp; }
    if (data.pulse && !data.bp_pulse) { const el = f.querySelector('input[name="bp_pulse"]'); if(el) el.value = data.pulse; }
    if (data.temp && !data.bp_temp) { const el = f.querySelector('input[name="bp_temp"]'); if(el) el.value = data.temp; }
    if (data.spo2 && !data.bp_spo2) { const el = f.querySelector('input[name="bp_spo2"]'); if(el) el.value = data.spo2; }
  }
  if (formId === 'f-gr') {
    if (data.date && !data.grbs_date) { const el = f.querySelector('input[name="grbs_date"]'); if(el) el.value = data.date; }
    if (data.time && !data.grbs_time) { const el = f.querySelector('input[name="grbs_time"]'); if(el) el.value = data.time; }
    if (data.value && !data.grbs_value) { const el = f.querySelector('input[name="grbs_value"]'); if(el) el.value = data.value; }
  }
  if (formId === 'f-nn') {
    if (data.date && !data.nurse_date) { const el = f.querySelector('input[name="nurse_date"]'); if(el) el.value = data.date; }
    if (data.time && !data.nurse_time) { const el = f.querySelector('input[name="nurse_time"]'); if(el) el.value = data.time; }
    if (data.particulars && !data.nurse_part) { const el = f.querySelector('textarea[name="nurse_part"]'); if(el) el.value = data.particulars; }
  }
  if (formId === 'f-di') {
    if (data.date && !data.dia_date) { const el = f.querySelector('input[name="dia_date"]'); if(el) el.value = data.date; }
    if (data.duration && !data.dia_dur) { const el = f.querySelector('input[name="dia_dur"]'); if(el) el.value = data.duration; }
    if (data.start_time && !data.dia_start) { const el = f.querySelector('input[name="dia_start"]'); if(el) el.value = data.start_time; }
    if (data.end_time && !data.dia_end) { const el = f.querySelector('input[name="dia_end"]'); if(el) el.value = data.end_time; }
  }
  if (formId === 'f-ox') {
    if (data.date && !data.oxy_date) { const el = f.querySelector('input[name="oxy_date"]'); if(el) el.value = data.date; }
    if (data.flow && !data.oxy_flow) { const el = f.querySelector('input[name="oxy_flow"]'); if(el) el.value = data.flow; }
    if (data.duration && !data.oxy_dur) { const el = f.querySelector('input[name="oxy_dur"]'); if(el) el.value = data.duration; }
  }
  if (formId === 'f-nb') {
    if (data.date && !data.nebu_date) { const el = f.querySelector('input[name="nebu_date"]'); if(el) el.value = data.date; }
    if (data.time && !data.nebu_time) { const el = f.querySelector('input[name="nebu_time"]'); if(el) el.value = data.time; }
    if (data.medicine && !data.nebu_drug) { const el = f.querySelector('input[name="nebu_drug"]'); if(el) el.value = data.medicine; }
  }
  if (formId === 'f-ve') {
    if (data.date && !data.vent_date) { const el = f.querySelector('input[name="vent_date"]'); if(el) el.value = data.date; }
    if (data.mode && !data.vent_mode) { const el = f.querySelector('select[name="vent_mode"]'); if(el) el.value = data.mode; }
    if (data.duration && !data.vent_dur) { const el = f.querySelector('input[name="vent_dur"]'); if(el) el.value = data.duration; }
  }
  if (formId === 'f-bl') {
    if (data.date && !data.trans_date) { const el = f.querySelector('input[name="trans_date"]'); if(el) el.value = data.date; }
    if (data.bag_no && !data.bag_number) { const el = f.querySelector('input[name="bag_number"]'); if(el) el.value = data.bag_no; }
    if (data.vitals && !data.vitals_during) { const el = f.querySelector('input[name="vitals_during"]'); if(el) el.value = data.vitals; }
  }

  // Update Select2 dropdowns if present
  $(f).find('select').trigger('change.select2');
  
  // Update button text and show cancel button
  const btn = f.querySelector('.btn-sv');
  if (btn) {
    btn.innerHTML = '<i class="fas fa-save"></i> Update Record';
    btn.style.background = 'var(--gm-primary)';
    btn.style.color = '#f3efe6';
  }
  
  let cancelBtn = f.querySelector('.btn-cancel-edit');
  if (!cancelBtn) {
    cancelBtn = document.createElement('button');
    cancelBtn.type = 'button';
    cancelBtn.className = 'btn-cancel-edit';
    cancelBtn.innerHTML = '<i class="fas fa-times"></i> Cancel Edit';
    cancelBtn.onclick = () => cancelEdit(formId);
    if (btn && btn.parentNode) {
      btn.parentNode.insertBefore(cancelBtn, btn.nextSibling);
    }
  }
  cancelBtn.style.display = 'inline-flex';
  
  f.scrollIntoView({ behavior: 'smooth', block: 'center' });
  showToast('✏️ Loaded record for editing. Modify fields and click "Update Record".');
}

function cancelEdit(formId){
  const f = document.getElementById(formId);
  if (!f) return;
  
  ['entry_id', '_db_row_id', '_arr_idx'].forEach(k => {
    const inp = f.querySelector(`input[name="${k}"]`);
    if (inp) inp.value = '';
  });
  
  clrF(formId);
  
  const btn = f.querySelector('.btn-sv');
  if (btn) {
    const ct = btn.getAttribute('data-ct') || '';
    const label = ct.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    btn.innerHTML = `<i class="fas fa-plus"></i> Add ${label}`;
    btn.style.background = '';
    btn.style.color = '';
  }
  
  const cancelBtn = f.querySelector('.btn-cancel-edit');
  if (cancelBtn) cancelBtn.style.display = 'none';
}

/* ── Discharge ── */
function openDischargeModal(){document.getElementById('dis-modal').style.display='flex';}
function closeDischargeModal(){document.getElementById('dis-modal').style.display='none';}
async function doDischarge(){
  if(!cp)return;
  const b=document.getElementById('dis-btn'); b.innerHTML='<i class="fas fa-spinner fa-spin"></i>'; b.disabled=true;
  const fd=new FormData(); fd.append('patient_id',cp.patient_id); fd.append('admission_id',cp.admission_id || '');
  try{const r=await fetch('api/send_discharge_notification.php',{method:'POST',body:fd});const res=await r.json();showToast(res.success?(res.message||'Notification sent!'):'Error: '+res.message,!res.success);}
  catch{showToast('Network error!',true);}
  finally{b.innerHTML='<i class="fas fa-check"></i> Yes, Notify';b.disabled=false;closeDischargeModal();}
}

/* ── Floating Toast ── */
function showToast(msg,err=false){
  const t=document.getElementById('toast');
  t.innerHTML=msg; 
  t.style.background=err?'#dc2626':'#1f6b4a';
  t.style.display='block'; 
  clearTimeout(t._t); 
  t._t=setTimeout(()=>t.style.display='none',4500);
}

/* ── Initialize ── */
$(document).ready(function() {
    $('select[name="consultant"], select[name="ref_doctor"]').select2({
        placeholder: "-- Search Doctor --",
        allowClear: true,
        width: '100%'
    });
    $('select[name="from_ward"], select[name="to_ward"]').select2({
        placeholder: "-- Search Ward --",
        allowClear: true,
        width: '100%'
    });
    $('select').on('change', function() {
        const f = this.closest('.card-body');
        if(f) f.classList.add('is-dirty');
    });
});
</script>
</body>
</html>
