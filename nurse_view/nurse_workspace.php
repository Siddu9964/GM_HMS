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
$allFloors = [];
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
    
    $resFloors = $conn->query("SELECT DISTINCT floor_number, floor_name FROM hospital_beds WHERE floor_name IS NOT NULL AND floor_name != '' ORDER BY floor_number ASC, floor_name ASC");
    if ($resFloors) {
        while ($r = $resFloors->fetch_assoc()) $allFloors[] = $r;
    }

    $resWards = $conn->query("SELECT DISTINCT ward_name FROM hospital_beds WHERE ward_name IS NOT NULL AND ward_name != '' ORDER BY ward_name ASC");
    if ($resWards) {
        while ($r = $resWards->fetch_assoc()) {
            $allWards[] = $r['ward_name'];
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

/* Centered Floating Toast */
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
        width: 90vw;
        max-width: 480px;
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
        <div class="card-new full-width ws-sec cat-doctor" id="s-tr">
          <div class="card-title-new">
            <div style="display:flex; align-items:center; gap:10px;">
              <i class="fas fa-bed"></i> 5. Ward / Bed Transfer Module
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
                  <input type="text" name="nurse_sign" id="tr-nurse-sign" readonly>
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

<!-- Multi-Department Discharge Clearance Modal -->
<div id="dis-modal" style="position:fixed;inset:0;background:rgba(15, 35, 25, 0.6);backdrop-filter:blur(4px);z-index:9000;display:none;align-items:center;justify-content:center;">
  <div style="background:#ffffff;border-radius:16px;max-width:560px;width:92%;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,0.35);border:2px solid var(--gm-primary);animation:modalZoomIn 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);max-height:90vh;display:flex;flex-direction:column;">
    <div style="background:var(--gm-primary);padding:16px 22px;font-weight:800;font-size:1.05rem;color:#f3efe6;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
      <span style="display:flex; align-items:center; gap:8px;"><i class="fas fa-bell"></i> Multi-Department Discharge Clearance</span>
      <button type="button" onclick="closeDischargeModal()" style="background:none;border:none;color:#f3efe6;font-size:1.2rem;cursor:pointer;padding:2px 6px;"><i class="fas fa-times"></i></button>
    </div>
    
    <div style="padding:20px 22px;overflow-y:auto;flex:1;">
      <!-- Patient Summary -->
      <div style="background:#f8fafc;border:1.5px solid var(--gm-border);border-radius:10px;padding:12px 14px;margin-bottom:16px;">
        <div style="font-weight:800;color:var(--gm-primary);font-size:1.02rem;" id="dis-modal-pt-name">Patient Name</div>
        <div style="font-size:0.8rem;color:var(--gm-text-muted);margin-top:2px;" id="dis-modal-pt-meta">PID: – | IP#: – | Bed: –</div>
      </div>

      <!-- Live Clearance Status Tracker (Visible if initiated) -->
      <div id="dis-status-tracker" style="display:none;margin-bottom:16px;background:#fdfbf7;border:1.5px dashed var(--gm-border);border-radius:12px;padding:12px 14px;">
        <div style="font-size:0.75rem;font-weight:800;color:var(--gm-primary);text-transform:uppercase;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center;">
          <span><i class="fas fa-tasks"></i> Live Clearance Status</span>
          <span class="badge" id="dis-overall-status-badge" style="background:#fef3c7;color:#b45309;">Pending Clearance</span>
        </div>
        
        <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:8px;margin-bottom:10px;">
          <!-- Reception -->
          <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;padding:8px;text-align:center;">
            <div style="font-size:0.68rem;font-weight:700;color:#64748b;text-transform:uppercase;">Reception / Billing</div>
            <div id="dis-status-reception" style="font-weight:800;font-size:0.8rem;margin-top:2px;color:#f59e0b;">⏳ Pending</div>
          </div>
          <!-- Pharmacy -->
          <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;padding:8px;text-align:center;">
            <div style="font-size:0.68rem;font-weight:700;color:#64748b;text-transform:uppercase;">Pharmacy</div>
            <div id="dis-status-pharmacy" style="font-weight:800;font-size:0.8rem;margin-top:2px;color:#f59e0b;">⏳ Pending</div>
          </div>
          <!-- Lab -->
          <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;padding:8px;text-align:center;">
            <div style="font-size:0.68rem;font-weight:700;color:#64748b;text-transform:uppercase;">Laboratory</div>
            <div id="dis-status-lab" style="font-weight:800;font-size:0.8rem;margin-top:2px;color:#f59e0b;">⏳ Pending</div>
          </div>
        </div>

        <!-- Queries Box (if any query raised) -->
        <div id="dis-queries-container" style="display:none;background:#fff1f2;border:1px solid #fecdd3;border-radius:8px;padding:10px;font-size:0.8rem;">
          <div style="font-weight:800;color:#e11d48;margin-bottom:4px;"><i class="fas fa-exclamation-triangle"></i> Department Queries / Notes:</div>
          <div id="dis-queries-list" style="color:#9f1239;line-height:1.4;"></div>
        </div>
      </div>

      <!-- Department Notification Workflow Details -->
      <div style="font-size:0.82rem;color:var(--gm-text-body);margin-bottom:14px;line-height:1.5;">
        <strong style="color:var(--gm-primary);"><i class="fas fa-share-alt"></i> Automated Clearance Routing:</strong>
        <ul style="margin:6px 0 0 16px;padding:0;color:var(--gm-text-muted);font-size:0.8rem;">
          <li><strong>Reception / Billing:</strong> Verifies IPD bill settlements, deposits & bed release.</li>
          <li><strong>Pharmacy:</strong> Verifies unbilled medications & unused medicine returns.</li>
          <li><strong>Laboratory:</strong> Verifies all pending diagnostic test reports are completed.</li>
          <li><strong>Admin Dashboard:</strong> Live tracking until all 3 departments approve.</li>
        </ul>
      </div>

      <!-- Nurse Notes -->
      <div class="fmg">
        <label><i class="fas fa-sticky-note"></i> Clinical Discharge Notes / Instructions (Optional)</label>
        <textarea id="dis-nurse-notes" rows="2" placeholder="e.g. Patient stable, vitals normal, consultant Dr. Anand advised discharge..."></textarea>
      </div>
    </div>

    <div style="padding:14px 22px;background:#f8fafc;border-top:1px solid var(--gm-border);display:flex;gap:10px;justify-content:flex-end;flex-shrink:0;">
      <button type="button" onclick="closeDischargeModal()" style="padding:8px 16px;border-radius:8px;font-weight:700;font-size:0.84rem;border:1.5px solid var(--gm-border);background:#ffffff;color:var(--gm-primary);cursor:pointer">Close</button>
      <button type="button" id="dis-btn" onclick="doDischarge()" style="padding:8px 20px;border-radius:8px;font-weight:800;font-size:0.86rem;border:none;background:var(--gm-primary);color:#f3efe6;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
        <i class="fas fa-paper-plane"></i> <span id="dis-btn-label">Dispatch Multi-Department Clearance</span>
      </button>
    </div>
  </div>
</div>

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

<!-- 8. Out of Stock Pharmacy Medicine Order Modal -->
<div id="ph-oos-modal" style="position:fixed;inset:0;background:rgba(15, 35, 25, 0.6);backdrop-filter:blur(4px);z-index:9000;display:none;align-items:center;justify-content:center;">
  <div style="background:#ffffff;border-radius:16px;max-width:520px;width:92%;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,0.35);border:2px solid #dc2626;animation:modalZoomIn 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);">
    <div style="background:#dc2626;padding:16px 22px;font-weight:800;font-size:1.05rem;color:#ffffff;display:flex;align-items:center;justify-content:space-between;">
      <span style="display:flex; align-items:center; gap:8px;">
        <i class="fas fa-exclamation-triangle"></i> Medicine Stock Alert
      </span>
      <button type="button" onclick="closeOosModal()" style="background:none;border:none;color:#ffffff;font-size:1.2rem;cursor:pointer;padding:2px 6px;"><i class="fas fa-times"></i></button>
    </div>
    
    <div style="padding:22px;font-size:0.9rem;color:var(--gm-text-body);line-height:1.5;">
      <!-- Primary Alert Banner -->
      <div style="background:#fef2f2;border:1.5px solid #fca5a5;border-radius:12px;padding:14px;margin-bottom:16px;display:flex;align-items:center;gap:12px;">
        <div style="width:42px;height:42px;border-radius:50%;background:#fee2e2;color:#dc2626;display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0;">
          <i class="fas fa-box-open"></i>
        </div>
        <div>
          <div style="font-weight:800;color:#991b1b;font-size:1.05rem;">Medicine is not available in stock.</div>
          <div style="font-size:0.82rem;color:#7f1d1d;margin-top:2px;">Current pharmacy inventory stock is <strong>0 units</strong>.</div>
        </div>
      </div>

      <!-- Medicine Details Box -->
      <div style="background:#f8fafc;border:1px solid var(--gm-border);border-radius:10px;padding:12px 14px;margin-bottom:16px;">
        <div style="font-weight:800;color:var(--gm-primary);font-size:1.02rem;" id="oos-med-name">Medicine Name</div>
        <div style="font-size:0.82rem;color:var(--gm-text-muted);margin-top:3px;" id="oos-med-meta">Batch: N/A | Available Stock: 0</div>
      </div>

      <!-- Option to Save Order Even When Out of Stock -->
      <div style="background:#fffbeb;border:1.5px dashed #fcd34d;border-radius:10px;padding:12px 14px;margin-bottom:16px;font-size:0.82rem;color:#92400e;line-height:1.4;">
        <strong><i class="fas fa-info-circle"></i> Save Out-of-Stock Order Option:</strong>
        <p style="margin:4px 0 0 0;">You can still proceed and save this medicine order. It will be recorded in the patient's K-Sheet and forwarded to the pharmacy department as an emergency indent / pending stock request.</p>
      </div>

      <div class="fg" style="grid-template-columns: 1fr 1fr; gap:12px;">
        <div class="fmg">
          <label><i class="fas fa-sort-numeric-up"></i> Order Quantity <span style="color:#dc2626;">*</span></label>
          <input type="number" id="oos-med-qty" min="1" value="1" style="font-weight:700;">
        </div>
        <div class="fmg">
          <label><i class="fas fa-comment-medical"></i> Clinical Note / Reason</label>
          <input type="text" id="oos-med-note" placeholder="e.g. Urgent / Doctor prescribed">
        </div>
      </div>
    </div>

    <div style="padding:14px 22px;background:#f8fafc;border-top:1px solid var(--gm-border);display:flex;gap:10px;justify-content:flex-end;">
      <button type="button" onclick="closeOosModal()" style="padding:9px 16px;border-radius:8px;font-weight:700;font-size:0.85rem;border:1.5px solid var(--gm-border);background:#ffffff;color:var(--gm-primary);cursor:pointer">Cancel</button>
      <button type="button" id="btn-confirm-oos" onclick="confirmAddOosToCart()" style="padding:9px 20px;border-radius:8px;font-weight:800;font-size:0.88rem;border:none;background:var(--gm-primary);color:#f3efe6;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
        <i class="fas fa-plus-circle"></i> Add to Order & Save
      </button>
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
  initTransferForm();
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
    currentTransferLogs = d.ward_transfer || [];
    renderTransferHistory(currentTransferLogs);

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
      const isOos = i.is_out_of_stock || (i.stock !== undefined && parseInt(i.stock) <= 0);
      const oosBadge = isOos ? ' <span class="badge" style="background:#fee2e2;color:#dc2626;font-size:0.68rem;border-color:#fca5a5;"><i class="fas fa-exclamation-triangle"></i> Out of Stock</span>' : '';
      return `<tr><td>${o.created_date||i.date||''}</td><td><strong>${i.medicine||i.name||i.product_name||''}</strong>${oosBadge}</td><td>${i.batch||i.batch_no||'N/A'}</td><td>${i.qty||i.quantity||1}</td></tr>`;
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
let phT = null;
let pendingOosItem = null;

function openOosModal(item) {
  pendingOosItem = item;
  const medName = item.name || item.medicine_name || item.product_name || 'Selected Medicine';
  const batch = item.batch_number || item.batch || 'N/A';
  const rawStock = item.available_stock !== undefined ? item.available_stock : (item.quantity !== undefined ? item.quantity : (item.stock !== undefined ? item.stock : 0));
  const stock = parseInt(rawStock) || 0;
  
  const nameEl = document.getElementById('oos-med-name');
  if (nameEl) nameEl.textContent = medName;
  
  const metaEl = document.getElementById('oos-med-meta');
  if (metaEl) metaEl.innerHTML = `Batch: <strong>${batch}</strong> | Current Available Stock: <strong style="color:#dc2626;">${stock} units</strong>`;
  
  const qtyEl = document.getElementById('oos-med-qty');
  if (qtyEl) qtyEl.value = 1;
  
  const noteEl = document.getElementById('oos-med-note');
  if (noteEl) noteEl.value = '';
  
  const modal = document.getElementById('ph-oos-modal');
  if (modal) modal.style.display = 'flex';
}

function closeOosModal() {
  const modal = document.getElementById('ph-oos-modal');
  if (modal) modal.style.display = 'none';
  pendingOosItem = null;
}

function confirmAddOosToCart() {
  if (!pendingOosItem) return;
  const qtyInput = document.getElementById('oos-med-qty');
  const qty = Math.max(1, parseInt(qtyInput ? qtyInput.value : 1) || 1);
  const noteInput = document.getElementById('oos-med-note');
  const notes = noteInput ? noteInput.value.trim() : '';
  
  const id = pendingOosItem.id || pendingOosItem.medicine_id || pendingOosItem.product_id || ('OOS_' + Date.now());
  const name = pendingOosItem.name || pendingOosItem.medicine_name || pendingOosItem.product_name || 'Medicine';
  const batch = pendingOosItem.batch_number || pendingOosItem.batch || 'N/A';
  
  const ex = phCart.find(x => x.id === id);
  if (ex) {
    ex.qty += qty;
    ex.is_out_of_stock = true;
    if (notes) ex.notes = notes;
  } else {
    phCart.push({
      id: id,
      name: name,
      batch: batch,
      stock: 0,
      qty: qty,
      is_out_of_stock: true,
      notes: notes
    });
  }
  
  closeOosModal();
  renderPhCart();
  showToast(`⚠️ "${name}" (Out of stock) added to order.`);
}

function handlePhSelect(item) {
  document.getElementById('ph-results').style.display = 'none';
  document.getElementById('ph-input').value = '';
  
  const rawStock = item.available_stock !== undefined ? item.available_stock : (item.quantity !== undefined ? item.quantity : (item.stock !== undefined ? item.stock : 0));
  const numStock = parseInt(rawStock) || 0;
  
  if (numStock <= 0) {
    // Show Popup message that medicine is not available in stock, with option to save
    openOosModal(item);
  } else {
    addToPhCart(item);
  }
}

document.getElementById('ph-input').addEventListener('input', function() {
  clearTimeout(phT);
  const q = this.value.trim();
  const res = document.getElementById('ph-results');
  if (q.length < 2) { res.style.display = 'none'; return; }
  
  phT = setTimeout(() => {
    fetch('api/search_medicine.php?q=' + encodeURIComponent(q))
      .then(r => r.json())
      .then(d => {
        res.innerHTML = '';
        const items = d.data || d.medicines || d || [];
        if (Array.isArray(items) && items.length > 0) {
          items.forEach(item => {
            const medName = item.name || item.medicine_name || item.product_name || 'Unknown';
            const batch = item.batch_number || item.batch || 'N/A';
            const rawStock = item.available_stock !== undefined ? item.available_stock : (item.quantity !== undefined ? item.quantity : (item.stock !== undefined ? item.stock : 0));
            const stock = parseInt(rawStock) || 0;
            const isOos = isNaN(stock) || stock <= 0;
            
            const el = document.createElement('div');
            el.className = 'ph-item';
            if (isOos) el.style.background = '#fffafa';
            
            el.innerHTML = `
              <div>
                <strong style="color:var(--gm-primary)">${medName}</strong>
                <br>
                <small style="color:${isOos ? '#dc2626' : 'var(--gm-text-muted)'}; font-weight:600;">
                  Batch: ${batch} | Stock: ${isOos ? '<span style="color:#dc2626; font-weight:800;">0 (Out of Stock)</span>' : stock}
                </small>
              </div>
              <span class="badge" style="${isOos ? 'background:#fee2e2; color:#dc2626; border-color:#fca5a5;' : 'background:var(--gm-primary-light); color:var(--gm-primary);'}">
                <i class="fas ${isOos ? 'fa-exclamation-triangle' : 'fa-plus'}"></i> ${isOos ? 'Out of Stock' : 'Add'}
              </span>
            `;
            el.onclick = () => handlePhSelect(item);
            res.appendChild(el);
          });
        } else {
          const safeQ = q.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
          const safeQJs = q.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
          res.innerHTML = `
            <div style="padding:14px; text-align:center;">
              <div style="color:var(--gm-text-muted); font-size:0.84rem; margin-bottom:8px;">
                <i class="fas fa-search"></i> No matching medicines found in pharmacy catalog.
              </div>
              <button type="button" class="btn-sv-out" style="padding:6px 14px; font-size:0.8rem; margin:0 auto; display:inline-flex;" onclick="openOosModal({ name: '${safeQJs}', id: 'UNLISTED_' + Date.now(), available_stock: 0, batch_number: 'N/A' })">
                <i class="fas fa-plus-circle"></i> Add "${safeQ}" as Out-of-Stock / Unlisted Order
              </button>
            </div>
          `;
        }
        res.style.display = 'block';
      });
  }, 280);
});

document.addEventListener('click', e => {
  if (!document.getElementById('ph-input').contains(e.target) && !document.getElementById('ph-results').contains(e.target)) {
    document.getElementById('ph-results').style.display = 'none';
  }
});

function addToPhCart(item) {
  document.getElementById('ph-results').style.display = 'none';
  document.getElementById('ph-input').value = '';
  const id = item.id || item.medicine_id || item.product_id;
  const ex = phCart.find(x => x.id === id);
  const rawStock = item.available_stock !== undefined ? item.available_stock : (item.quantity !== undefined ? item.quantity : (item.stock !== undefined ? item.stock : 0));
  const stock = parseInt(rawStock) || 0;
  const medName = item.name || item.medicine_name || item.product_name;

  if (ex) {
    ex.qty++;
  } else {
    phCart.push({
      id: id,
      name: medName,
      batch: item.batch_number || item.batch || 'N/A',
      stock: stock,
      qty: 1,
      is_out_of_stock: stock <= 0
    });
  }
  renderPhCart();
  showToast(`✅ "${medName}" added to order.`);
}

function renderPhCart() {
  const ca = document.getElementById('ph-cart');
  if (!phCart.length) { ca.innerHTML = ''; return; }

  let hasOos = false;
  let rowsHtml = phCart.map(m => {
    const isOos = m.is_out_of_stock || (parseInt(m.stock) <= 0);
    if (isOos) hasOos = true;
    const badgeHtml = isOos 
      ? `<span class="badge" style="background:#fee2e2; color:#dc2626; border-color:#fca5a5;"><i class="fas fa-exclamation-triangle"></i> Out of Stock</span>`
      : `<span class="badge">Batch: ${m.batch || 'N/A'} | Stock: ${m.stock}</span>`;
    
    return `
      <div class="cart-row" style="${isOos ? 'border-left: 4px solid #dc2626; background:#fffafa;' : ''}">
        <div class="cart-row-n">
          <div>${m.name} ${badgeHtml}</div>
          ${m.notes ? `<small style="color:#b91c1c; font-size:0.75rem; display:block; margin-top:2px;"><i class="fas fa-sticky-note"></i> ${m.notes}</small>` : ''}
        </div>
        <input type="number" value="${m.qty}" min="1" onchange="const item=phCart.find(x=>x.id==='${m.id}'); if(item){item.qty=parseInt(this.value)||1; renderPhCart();}">
        <button class="rm-btn" onclick="phCart=phCart.filter(x=>x.id!=='${m.id}'); renderPhCart();" title="Remove"><i class="fas fa-trash-alt"></i></button>
      </div>
    `;
  }).join('');

  if (hasOos) {
    rowsHtml += `
      <div style="margin-top:8px; padding:8px 12px; background:#fffbeb; border:1px solid #fcd34d; border-radius:8px; font-size:0.78rem; color:#92400e; display:flex; align-items:center; gap:8px;">
        <i class="fas fa-info-circle" style="color:#d97706; font-size:0.95rem;"></i>
        <span><strong>Notice:</strong> Out-of-stock items will be recorded and forwarded to the pharmacy department as pending indents.</span>
      </div>
    `;
  }

  ca.innerHTML = rowsHtml;
}

async function savePharmacy() {
  if (!cp) { showToast('No patient selected!', true); return; }
  if (!phCart.length) { showToast('Add at least one medicine.', true); return; }
  
  const b = document.getElementById('ph-save-btn');
  const origHtml = b.innerHTML;
  b.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
  b.disabled = true;
  
  try {
    const oosCount = phCart.filter(x => x.is_out_of_stock || parseInt(x.stock) <= 0).length;
    const r = await fetch('api/save_pharmacy_order.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ patient_id: cp.patient_id, admission_id: cp.admission_id || '', cart: phCart })
    });
    const res = await r.json();
    if (res.success) {
      let msg = res.message || 'Pharmacy order submitted!';
      if (oosCount > 0) {
        msg = `Pharmacy order submitted successfully (Includes ${oosCount} out-of-stock item(s) flagged for indent)!`;
      }
      showToast(`✅ ${msg}`);
      phCart = [];
      renderPhCart();
      loadAllRecords();
    } else {
      showToast('Error: ' + (res.message || 'Unknown'), true);
    }
  } catch (err) {
    showToast('Network error while saving pharmacy order!', true);
  } finally {
    b.innerHTML = origHtml;
    b.disabled = false;
  }
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

/* ── Enhanced Ward & Bed Transfer Functions ── */
let currentTransferLogs = [];
let currentLoadedBeds = [];

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
      // If room types available, auto-load beds if single option
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

  const f = document.getElementById('f-tr');
  if (f) f.classList.add('is-dirty');

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

  autoFill(document.getElementById('f-tr'));
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
          { ic: 'fa-user', l: 'Age/Sex', v: `${cp.age || '?'}Y / ${cp.sex || '?'}` },
          { ic: 'fa-tint', l: 'Blood', v: cp.blood_group || 'N/A' }
        ].map(c => `<span class="ptchip"><i class="fas ${c.ic}"></i><strong>${c.l}:</strong> ${c.v}</span>`).join('');
      }

      // 3. Close Modal & Reset Form
      closeTransferConfirmModal();
      initTransferForm();

      // 4. Reload Transfer Logs & Global Records
      loadAllRecords();

      // 5. Reload bed grid if still looking at the same section
      loadBeds();

      // 6. Immediately trigger notification fetch
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

/* ── Multi-Department Discharge Clearance ── */
async function openDischargeModal() {
  if (!cp) {
    showToast('Please select an admitted patient first!', true);
    return;
  }

  // Populate patient info
  const ptNameEl = document.getElementById('dis-modal-pt-name');
  if (ptNameEl) ptNameEl.textContent = `${cp.first_name} ${cp.last_name || ''}`;

  const ptMetaEl = document.getElementById('dis-modal-pt-meta');
  if (ptMetaEl) {
    const bedTxt = `${cp.ward_name || cp.room_type || 'Ward'} (Bed ${cp.bed_number || cp.room_number || '–'})`;
    ptMetaEl.textContent = `PID: ${cp.patient_id} | IP#: ${cp.admission_id || 'N/A'} | Location: ${bedTxt} | Doctor: Dr. ${cp.doctor_name || 'Consultant'}`;
  }

  // Clear notes input
  const notesEl = document.getElementById('dis-nurse-notes');
  if (notesEl) notesEl.value = '';

  // Check live clearance status
  await fetchDischargeStatusForModal();

  document.getElementById('dis-modal').style.display = 'flex';
}

function closeDischargeModal() {
  document.getElementById('dis-modal').style.display = 'none';
}

async function fetchDischargeStatusForModal() {
  if (!cp) return;

  const trackerEl = document.getElementById('dis-status-tracker');
  const badgeEl = document.getElementById('dis-overall-status-badge');
  const btnLabelEl = document.getElementById('dis-btn-label');
  const qContainer = document.getElementById('dis-queries-container');
  const qList = document.getElementById('dis-queries-list');

  try {
    const res = await fetch(`/GM_HMS/api/discharge_clearance.php?action=status&admission_id=${encodeURIComponent(cp.admission_id || '')}&patient_id=${encodeURIComponent(cp.patient_id)}`);
    const json = await res.json();

    if (json.success && json.has_clearance && json.data) {
      const d = json.data;
      if (trackerEl) trackerEl.style.display = 'block';

      // Update badge
      if (badgeEl) {
        badgeEl.textContent = d.overall_status || 'Pending Clearance';
        if (d.overall_status === 'All Cleared') {
          badgeEl.style.background = '#dcfce7'; badgeEl.style.color = '#15803d';
        } else if (d.overall_status === 'Queries Raised') {
          badgeEl.style.background = '#fee2e2'; badgeEl.style.color = '#dc2626';
        } else {
          badgeEl.style.background = '#fef3c7'; badgeEl.style.color = '#b45309';
        }
      }

      // Department status chips
      const setDeptStatus = (elId, status, by) => {
        const el = document.getElementById(elId);
        if (!el) return;
        if (status === 'Approved') {
          el.innerHTML = `<span style="color:#16a34a;"><i class="fas fa-check-circle"></i> Cleared</span>${by ? `<br><small style="color:#64748b;font-size:0.65rem;">by ${by}</small>` : ''}`;
        } else if (status === 'Query') {
          el.innerHTML = `<span style="color:#dc2626;"><i class="fas fa-exclamation-triangle"></i> Query</span>`;
        } else {
          el.innerHTML = `<span style="color:#f59e0b;"><i class="fas fa-clock"></i> Pending</span>`;
        }
      };

      setDeptStatus('dis-status-reception', d.reception_status, d.reception_by);
      setDeptStatus('dis-status-pharmacy', d.pharmacy_status, d.pharmacy_by);
      setDeptStatus('dis-status-lab', d.lab_status, d.lab_by);

      // Queries
      if (json.queries && json.queries.length > 0) {
        if (qContainer) qContainer.style.display = 'block';
        if (qList) {
          qList.innerHTML = json.queries.map(q => `
            <div style="margin-bottom:6px;padding-bottom:4px;border-bottom:1px dashed #fecdd3;">
              <strong>[${q.department.toUpperCase()}] ${q.user_name || 'Staff'}:</strong> ${q.query_text}
              <span class="badge" style="float:right;font-size:0.65rem;background:${q.status==='Resolved'?'#dcfce7':'#fee2e2'};color:${q.status==='Resolved'?'#15803d':'#b91c1c'};">${q.status}</span>
            </div>
          `).join('');
        }
      } else {
        if (qContainer) qContainer.style.display = 'none';
      }

      if (btnLabelEl) btnLabelEl.textContent = 'Re-send / Update Multi-Department Request';

    } else {
      if (trackerEl) trackerEl.style.display = 'none';
      if (btnLabelEl) btnLabelEl.textContent = 'Dispatch Multi-Department Clearance';
    }
  } catch (err) {
    console.error('Error fetching discharge status:', err);
    if (trackerEl) trackerEl.style.display = 'none';
  }
}

async function doDischarge() {
  if (!cp) return;
  const b = document.getElementById('dis-btn');
  const origHtml = b.innerHTML;
  b.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Dispatching Clearance...';
  b.disabled = true;

  const notes = document.getElementById('dis-nurse-notes') ? document.getElementById('dis-nurse-notes').value.trim() : '';

  const payload = {
    action: 'initiate',
    patient_id: cp.patient_id,
    admission_id: cp.admission_id || '',
    nurse_notes: notes,
    nurse_name: NN
  };

  try {
    const r = await fetch('/GM_HMS/api/discharge_clearance.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const res = await r.json();

    if (res.success) {
      showToast(`✅ ${res.message || 'Multi-department clearance request dispatched!'}`);
      closeDischargeModal();
    } else {
      showToast(`❌ Error: ${res.message || 'Failed to dispatch request'}`, true);
    }
  } catch (err) {
    console.error('Discharge request error:', err);
    showToast('Network error while sending discharge notification.', true);
  } finally {
    b.innerHTML = origHtml;
    b.disabled = false;
  }
}

/* ── Centered Floating Toast with High-Contrast Visible Text ── */
function showToast(msg, err=false){
  const t = document.getElementById('toast');
  t.style.background = '#ffffff';
  t.style.color = '#23342b';
  t.style.border = err ? '2.5px solid #dc2626' : '2.5px solid #1f6b4a';
  t.style.boxShadow = '0 20px 60px rgba(0, 0, 0, 0.35)';
  t.innerHTML = `
    <div style="display:flex; align-items:center; justify-content:center; gap:10px; margin-bottom:8px; font-size:1.15rem; font-weight:800; color:${err ? '#dc2626' : '#1f6b4a'};">
        <i class="fas ${err ? 'fa-exclamation-circle' : 'fa-check-circle'}"></i> ${err ? 'Notice / Error' : 'Record Saved Successfully'}
    </div>
    <div style="font-size:0.92rem; font-weight:700; color:#23342b; line-height:1.5;">${msg}</div>
  `;
  t.style.display = 'block'; 
  clearTimeout(t._t); 
  t._t = setTimeout(() => t.style.display = 'none', 4500);
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
