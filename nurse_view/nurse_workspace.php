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
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
/* ═══════════════════════════════════════════════════════════════════
   GM HMS NURSE WORKSPACE — Complete Modern UI/UX Redesign
   Color System: STRICTLY #f3efe6 (Warm Cream) & #1f6b4a (Forest Green)
   High-Efficiency Clinical Dashboard • Responsive • Accessible
   ═══════════════════════════════════════════════════════════════════ */

:root {
    --gm-bg:             #f3efe6;
    --gm-surface:        #ffffff;
    --gm-surface-warm:   #faf8f3;
    --gm-primary:        #1f6b4a;
    --gm-primary-dark:   #154c34;
    --gm-primary-deep:   #0e3524;
    --gm-primary-light:  rgba(31, 107, 74, 0.08);
    --gm-primary-mid:    rgba(31, 107, 74, 0.14);
    --gm-primary-glow:   rgba(31, 107, 74, 0.22);
    --gm-border:         rgba(31, 107, 74, 0.18);
    --gm-border-strong:  #1f6b4a;
    --gm-border-light:   rgba(31, 107, 74, 0.10);
    --gm-text:           #1f6b4a;
    --gm-text-body:      #1a3829;
    --gm-text-muted:     #466e5a;
    --gm-text-light:     #6b907c;
    --gm-radius-sm:      6px;
    --gm-radius-md:      10px;
    --gm-radius-lg:      14px;
    --gm-radius-xl:      18px;
    --gm-shadow-sm:      0 2px 8px rgba(31, 107, 74, 0.05);
    --gm-shadow-md:      0 6px 20px rgba(31, 107, 74, 0.09);
    --gm-shadow-lg:      0 12px 32px rgba(31, 107, 74, 0.14);
    --gm-transition:     all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

*, *::before, *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

html, body {
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background-color: var(--gm-bg);
    color: var(--gm-text-body);
    min-height: 100vh;
    box-sizing: border-box;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    line-height: 1.5;
}

/* ── Main Layout Wrapper ── */
.main-layout {
    display: flex;
    min-height: 100vh;
    width: 100%;
    position: relative;
    overflow-x: hidden;
    background: var(--gm-bg);
}

.content-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
    background-color: var(--gm-bg);
    font-size: 13.5px;
}

.main-content {
    flex: 1;
    padding: 20px 24px 36px;
    box-sizing: border-box;
}

/* ══════════════════════════════════════════════════════════════
   1. INPATIENT SELECTION DASHBOARD
   ══════════════════════════════════════════════════════════════ */
.nopt-dashboard {
    background: var(--gm-surface);
    border-radius: var(--gm-radius-lg);
    border: 1.5px solid var(--gm-border);
    padding: 22px 24px;
    box-shadow: var(--gm-shadow-md);
    margin-bottom: 22px;
    position: relative;
    overflow: hidden;
}

.nopt-dashboard::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--gm-primary) 0%, var(--gm-primary-dark) 100%);
}

.dash-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 18px;
    padding-bottom: 16px;
    border-bottom: 1.5px solid var(--gm-border-light);
}

.dash-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--gm-primary);
    display: flex;
    align-items: center;
    gap: 10px;
    letter-spacing: -0.2px;
}

.search-box-wrap {
    position: relative;
    width: 100%;
    max-width: 360px;
}

.search-box-wrap i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gm-primary);
    font-size: 0.9rem;
    pointer-events: none;
}

.search-box-wrap input {
    width: 100%;
    padding: 10px 14px 10px 40px;
    border-radius: var(--gm-radius-md);
    border: 1.5px solid var(--gm-border);
    font-size: 0.86rem;
    outline: none;
    background: var(--gm-bg);
    color: var(--gm-text-body);
    font-weight: 600;
    font-family: inherit;
    transition: var(--gm-transition);
}

.search-box-wrap input:focus {
    background: #ffffff;
    border-color: var(--gm-primary);
    box-shadow: 0 0 0 3.5px var(--gm-primary-light);
}

.dash-patient-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
    gap: 14px;
}

.dash-patient-card {
    background: #ffffff;
    border: 1.5px solid var(--gm-border);
    border-radius: var(--gm-radius-md);
    padding: 14px 16px;
    cursor: pointer;
    transition: var(--gm-transition);
    box-shadow: var(--gm-shadow-sm);
    display: flex;
    flex-direction: column;
    gap: 10px;
    user-select: none;
    position: relative;
}

.dash-patient-card:hover {
    border-color: var(--gm-primary);
    transform: translateY(-2px);
    box-shadow: var(--gm-shadow-md);
    background: var(--gm-surface-warm);
}

.dash-patient-name {
    font-size: 0.98rem;
    font-weight: 800;
    color: var(--gm-primary);
    line-height: 1.25;
}

.dash-chips-row {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.chip-mini {
    font-size: 0.73rem;
    font-weight: 700;
    padding: 3px 8px;
    background: var(--gm-primary-light);
    color: var(--gm-primary);
    border: 1px solid var(--gm-border);
    border-radius: var(--gm-radius-sm);
    display: inline-flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
}

/* ══════════════════════════════════════════════════════════════
   2. STICKY ACTIVE PATIENT BANNER
   ══════════════════════════════════════════════════════════════ */
.pt-banner {
    background: linear-gradient(135deg, var(--gm-primary) 0%, var(--gm-primary-dark) 100%);
    color: #f3efe6;
    padding: 12px 18px;
    border-radius: var(--gm-radius-lg);
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    box-shadow: var(--gm-shadow-lg);
    margin-bottom: 18px;
    position: sticky;
    top: 10px;
    z-index: 85;
    border: 1.5px solid rgba(243, 239, 230, 0.25);
    backdrop-filter: blur(8px);
}

.pt-banner-av {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: rgba(243, 239, 230, 0.2);
    border: 1.5px solid rgba(243, 239, 230, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    font-weight: 800;
    color: #f3efe6;
    flex-shrink: 0;
    letter-spacing: 0.5px;
}

.pt-banner-info {
    display: flex;
    flex-direction: column;
    gap: 3px;
    min-width: 0;
}

.pt-banner-nm {
    font-size: 1.15rem;
    font-weight: 800;
    color: #ffffff;
    display: flex;
    align-items: center;
    gap: 8px;
    letter-spacing: -0.2px;
}

.pt-banner-chips {
    display: flex;
    gap: 7px;
    flex-wrap: wrap;
}

.ptchip {
    font-size: 0.74rem;
    background: rgba(243, 239, 230, 0.16);
    padding: 3px 8px;
    border-radius: var(--gm-radius-sm);
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: #f3efe6;
    font-weight: 600;
    border: 1px solid rgba(243, 239, 230, 0.2);
}

.ptchip strong {
    color: #ffffff;
}

.pt-banner-ac {
    margin-left: auto;
    display: flex;
    gap: 8px;
    flex-shrink: 0;
    align-items: center;
}

.pba {
    padding: 7px 13px;
    border-radius: var(--gm-radius-md);
    font-size: 0.8rem;
    font-weight: 750;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: var(--gm-transition);
    white-space: nowrap;
    min-height: 36px;
    font-family: inherit;
    text-decoration: none;
}

.pba.ksheet {
    background: #f3efe6;
    color: var(--gm-primary);
    box-shadow: 0 2px 6px rgba(0,0,0,0.12);
}
.pba.ksheet:hover {
    background: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.18);
}
.pba.warn {
    background: rgba(243, 239, 230, 0.25);
    color: #ffffff;
    border: 1px solid rgba(243, 239, 230, 0.4);
}
.pba.warn:hover {
    background: #f3efe6;
    color: var(--gm-primary);
}
.pba.sec {
    background: transparent;
    color: #f3efe6;
    border: 1px solid rgba(243, 239, 230, 0.35);
}
.pba.sec:hover {
    background: rgba(243, 239, 230, 0.2);
}

/* ══════════════════════════════════════════════════════════════
   3. QUICK CATEGORY NAVIGATION PILLS
   ══════════════════════════════════════════════════════════════ */
.workspace-tabs {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding: 4px 2px 14px;
    margin-bottom: 12px;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
}

.workspace-tabs::-webkit-scrollbar {
    display: none;
}

.tab-pill {
    padding: 7px 16px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 750;
    background: #ffffff;
    color: var(--gm-primary);
    border: 1.5px solid var(--gm-border);
    cursor: pointer;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: var(--gm-transition);
    min-height: 35px;
    font-family: inherit;
    box-shadow: var(--gm-shadow-sm);
}

.tab-pill:hover {
    background: var(--gm-primary-light);
    border-color: var(--gm-primary);
    transform: translateY(-1px);
}

.tab-pill.active {
    background: var(--gm-primary);
    color: #f3efe6;
    border-color: var(--gm-primary);
    box-shadow: 0 4px 12px rgba(31, 107, 74, 0.22);
}

/* ══════════════════════════════════════════════════════════════
   4. BENTO-GRID & CLINICAL CARDS
   ══════════════════════════════════════════════════════════════ */
.ws-grid-new {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
    width: 100%;
}

.card-new {
    background: var(--gm-surface);
    border: 1.5px solid var(--gm-border);
    border-radius: var(--gm-radius-lg);
    padding: 18px 20px;
    box-shadow: var(--gm-shadow-sm);
    display: flex;
    flex-direction: column;
    transition: var(--gm-transition);
    position: relative;
}

.card-new:hover {
    box-shadow: var(--gm-shadow-md);
    border-color: rgba(31, 107, 74, 0.32);
}

.card-new.full-width {
    grid-column: 1 / -1;
}

.card-title-new {
    color: var(--gm-primary);
    font-weight: 800;
    font-size: 0.98rem;
    margin-bottom: 14px;
    padding-bottom: 10px;
    border-bottom: 1.5px solid var(--gm-border-light);
    display: flex;
    align-items: center;
    justify-content: space-between;
    letter-spacing: -0.1px;
}

.card-title-new span {
    display: flex;
    align-items: center;
    gap: 8px;
}

.card-title-new i {
    color: var(--gm-primary);
    font-size: 1.05rem;
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
    margin-top: 2px;
}

.ht-title {
    font-size: 0.78rem;
    font-weight: 800;
    color: var(--gm-primary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* ══════════════════════════════════════════════════════════════
   5. FORM CONTROLS & FIELD GROUPS
   ══════════════════════════════════════════════════════════════ */
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
    font-size: 0.72rem;
    font-weight: 750;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: var(--gm-primary);
    display: flex;
    align-items: center;
    gap: 4px;
}

.fmg input, .fmg select, .fmg textarea {
    padding: 8px 12px;
    border: 1.5px solid var(--gm-border);
    border-radius: var(--gm-radius-sm);
    font-size: 0.86rem;
    background: #fdfcf9;
    color: var(--gm-text-body);
    font-weight: 600;
    outline: none;
    font-family: inherit;
    width: 100%;
    transition: var(--gm-transition);
}

.fmg input:focus, .fmg select:focus, .fmg textarea:focus {
    background: #ffffff;
    border-color: var(--gm-primary);
    box-shadow: 0 0 0 3px var(--gm-primary-light);
}

.fmg input[readonly] {
    background: rgba(31, 107, 74, 0.05) !important;
    color: var(--gm-primary) !important;
    border-color: rgba(31, 107, 74, 0.2) !important;
    cursor: default !important;
    font-weight: 700 !important;
}

.btn-sv-out {
    background: var(--gm-primary);
    border: 1.5px solid var(--gm-primary);
    color: #f3efe6;
    padding: 8px 16px;
    border-radius: var(--gm-radius-md);
    font-size: 0.82rem;
    font-weight: 750;
    cursor: pointer;
    margin-top: 12px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: var(--gm-transition);
    min-height: 38px;
    font-family: inherit;
    box-shadow: 0 2px 6px rgba(31, 107, 74, 0.18);
}

.btn-sv-out:hover {
    background: var(--gm-primary-dark);
    border-color: var(--gm-primary-dark);
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(31, 107, 74, 0.28);
}

.btn-sv-out:active {
    transform: translateY(0);
}

.btn-cancel-edit {
    background: rgba(220, 38, 38, 0.1);
    color: #dc2626;
    border: 1px solid rgba(220, 38, 38, 0.25);
    padding: 8px 14px;
    border-radius: var(--gm-radius-md);
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    margin-top: 12px;
    margin-left: 8px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: var(--gm-transition);
    min-height: 38px;
    font-family: inherit;
}

.btn-cancel-edit:hover {
    background: #dc2626;
    color: #ffffff;
}

/* ══════════════════════════════════════════════════════════════
   6. STREAMLINED GLASGOW COMA SCALE (GCS) UI
   Standard Native Form Controls • High Speed • Clean Score Bar
   ══════════════════════════════════════════════════════════════ */
.gcs-streamlined-card {
    grid-column: 1 / -1;
    background: var(--gm-surface-warm);
    border: 1.5px solid var(--gm-border);
    border-radius: var(--gm-radius-md);
    padding: 14px 16px;
    margin-top: 6px;
    margin-bottom: 6px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.gcs-summary-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    background: #ffffff;
    border: 1.5px solid var(--gm-border-light);
    border-radius: var(--gm-radius-md);
    padding: 10px 14px;
    box-shadow: var(--gm-shadow-sm);
}

.gcs-score-badge-wrap {
    display: flex;
    align-items: center;
    gap: 12px;
}

.gcs-dial-pill {
    padding: 4px 12px;
    border-radius: var(--gm-radius-md);
    background: var(--gm-primary-light);
    border: 2px solid var(--gm-primary);
    color: var(--gm-primary);
    display: flex;
    align-items: baseline;
    gap: 4px;
    font-weight: 800;
}

.gcs-dial-num {
    font-size: 1.45rem;
    line-height: 1;
}

.gcs-dial-max {
    font-size: 0.75rem;
    opacity: 0.8;
}

.gcs-dial-pill.gcs-mild {
    border-color: #16a34a;
    background: #f0fdf4;
    color: #15803d;
}
.gcs-dial-pill.gcs-moderate {
    border-color: #d97706;
    background: #fffbeb;
    color: #b45309;
}
.gcs-dial-pill.gcs-severe {
    border-color: #dc2626;
    background: #fef2f2;
    color: #dc2626;
}

.gcs-status-meta {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.gcs-status-badge {
    font-size: 0.82rem;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: var(--gm-primary);
}

.gcs-status-badge.gcs-mild { color: #15803d; }
.gcs-status-badge.gcs-moderate { color: #b45309; }
.gcs-status-badge.gcs-severe { color: #dc2626; }

.gcs-status-desc {
    font-size: 0.7rem;
    color: var(--gm-text-muted);
    font-weight: 600;
}

.gcs-breakdown-tags {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.gcs-tag {
    font-size: 0.74rem;
    font-weight: 750;
    padding: 4px 8px;
    border-radius: var(--gm-radius-sm);
    background: var(--gm-bg);
    border: 1px solid var(--gm-border);
    color: var(--gm-text-body);
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
}

.gcs-tag strong {
    color: var(--gm-primary);
}

.gcs-selects-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}

@media (max-width: 900px) {
    .gcs-selects-grid {
        grid-template-columns: 1fr;
    }
}

.gcs-native-select {
    padding: 9px 12px;
    font-size: 0.86rem;
    font-weight: 600;
    border-radius: var(--gm-radius-sm);
    border: 1.5px solid var(--gm-border);
    background: #ffffff;
    color: var(--gm-text-body);
    font-family: inherit;
    outline: none;
    cursor: pointer;
    width: 100%;
    transition: var(--gm-transition);
}

.gcs-native-select:focus {
    border-color: var(--gm-primary);
    box-shadow: 0 0 0 3px var(--gm-primary-light);
}

/* ══════════════════════════════════════════════════════════════
   7. HISTORY LOG TABLES
   ══════════════════════════════════════════════════════════════ */
.ht-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-radius: var(--gm-radius-md);
    border: 1px solid var(--gm-border);
    max-height: 165px;
    background: #ffffff;
}

.ht {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.78rem;
    min-width: 320px;
}

.ht thead th {
    background: var(--gm-surface-warm);
    padding: 8px 10px;
    text-align: left;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    color: var(--gm-primary);
    border-bottom: 1.5px solid var(--gm-border);
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 10;
}

.ht tbody td {
    padding: 7px 10px;
    border-bottom: 1px solid var(--gm-border-light);
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
    font-style: italic;
}

.main-content .badge {
    padding: 3px 8px;
    border-radius: var(--gm-radius-sm);
    font-size: 0.72rem;
    font-weight: 750;
    background: var(--gm-primary-light);
    color: var(--gm-primary);
    border: 1px solid var(--gm-border);
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.btn-edit-log {
    background: var(--gm-primary-light);
    color: var(--gm-primary);
    border: 1px solid var(--gm-border);
    padding: 3px 8px;
    border-radius: var(--gm-radius-sm);
    font-size: 0.72rem;
    font-weight: 750;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: var(--gm-transition);
}

.btn-edit-log:hover {
    background: var(--gm-primary);
    color: #f3efe6;
    border-color: var(--gm-primary);
}

/* ══════════════════════════════════════════════════════════════
   8. SEARCH DROPDOWNS & CARTS (LAB & PHARMACY)
   ══════════════════════════════════════════════════════════════ */
#ts-results, #ph-results {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: #ffffff;
    border: 1.5px solid var(--gm-primary);
    border-radius: var(--gm-radius-md);
    z-index: 500;
    display: none;
    box-shadow: var(--gm-shadow-lg);
    max-height: 240px;
    overflow-y: auto;
}

.ts-item, .ph-item {
    padding: 9px 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--gm-border-light);
    transition: background 0.15s;
    font-size: 0.82rem;
}

.ts-item:hover, .ph-item:hover {
    background: var(--gm-primary-light);
}

.cart-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    background: #ffffff;
    border: 1px solid var(--gm-border);
    border-radius: var(--gm-radius-sm);
    margin-bottom: 6px;
    font-size: 0.82rem;
}

.cart-row-n {
    flex: 1;
    font-weight: 700;
    color: var(--gm-primary);
}

.cart-row input {
    width: 60px;
    padding: 4px 6px;
    border: 1px solid var(--gm-border);
    border-radius: var(--gm-radius-sm);
    text-align: center;
    font-weight: 700;
}

.rm-btn {
    background: rgba(220, 38, 38, 0.1);
    color: #dc2626;
    border: 1px solid rgba(220, 38, 38, 0.25);
    border-radius: var(--gm-radius-sm);
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--gm-transition);
}

.rm-btn:hover {
    background: #dc2626;
    color: #ffffff;
}

/* Diagnostic subtabs */
.treatment-subtabs {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
}

.t-tab {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 750;
    background: var(--gm-bg);
    color: var(--gm-primary);
    border: 1.5px solid var(--gm-border);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: var(--gm-transition);
}

.t-tab:hover, .t-tab.active {
    background: var(--gm-primary);
    color: #f3efe6;
    border-color: var(--gm-primary);
}

.t-panel {
    width: 100%;
}

.t-title {
    font-size: 0.92rem;
    font-weight: 800;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--gm-primary);
}

/* ══════════════════════════════════════════════════════════════
   9. MODALS & TOAST NOTIFICATIONS
   ══════════════════════════════════════════════════════════════ */
.modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(14, 53, 36, 0.5);
    backdrop-filter: blur(4px);
    z-index: 1000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
}

.modal-content {
    background: #ffffff;
    border-radius: var(--gm-radius-lg);
    border: 1.5px solid var(--gm-border);
    box-shadow: 0 20px 60px rgba(14, 53, 36, 0.3);
    width: 100%;
    max-width: 580px;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    padding: 22px 24px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1.5px solid var(--gm-border-light);
    padding-bottom: 14px;
    margin-bottom: 16px;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--gm-primary);
    display: flex;
    align-items: center;
    gap: 8px;
}

.modal-close {
    background: var(--gm-primary-light);
    color: var(--gm-primary);
    border: 1px solid var(--gm-border);
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--gm-transition);
}

.modal-close:hover {
    background: var(--gm-primary);
    color: #f3efe6;
}

#toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: #ffffff;
    color: var(--gm-primary);
    border: 2px solid var(--gm-primary);
    border-radius: var(--gm-radius-md);
    padding: 14px 20px;
    box-shadow: var(--gm-shadow-lg);
    display: none;
    z-index: 2000;
    max-width: 420px;
    font-size: 0.88rem;
    font-weight: 700;
    animation: toastSlide 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes toastSlide {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

/* ══════════════════════════════════════════════════════════════
   10. RESPONSIVE BREAKPOINTS
   ══════════════════════════════════════════════════════════════ */
@media (max-width: 1024px) {
    .main-content {
        padding: 16px;
    }
    .ws-grid-new {
        grid-template-columns: 1fr;
    }
    .pt-banner {
        top: 0;
    }
}

@media (max-width: 640px) {
    .dash-header {
        flex-direction: column;
        align-items: stretch;
    }
    .search-box-wrap {
        max-width: 100%;
    }
    .pt-banner-ac {
        margin-left: 0;
        width: 100%;
        justify-content: stretch;
    }
    .pt-banner-ac .pba {
        flex: 1;
    }
}

/* ── Diagnostic Requisition & History Tabs (Strictly #f3efe6 & #1f6b4a) ── */
.diag-view-nav {
    display: inline-flex;
    align-items: center;
    background: #f3efe6;
    border: 1.5px solid #1f6b4a;
    border-radius: var(--gm-radius-md);
    padding: 3px;
    gap: 4px;
}

.diag-tab-btn {
    padding: 8px 16px;
    border-radius: var(--gm-radius-sm);
    font-size: 0.82rem;
    font-weight: 750;
    border: 1.5px solid transparent;
    cursor: pointer;
    background: transparent;
    color: #1f6b4a;
    transition: var(--gm-transition);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-family: inherit;
    white-space: nowrap;
}

.diag-tab-btn:hover {
    background: rgba(31, 107, 74, 0.08);
}

.diag-tab-btn.active {
    background: #1f6b4a;
    color: #f3efe6;
    border-color: #1f6b4a;
    box-shadow: 0 2px 10px rgba(31, 107, 74, 0.25);
}

.diag-tab-btn .tab-badge {
    background: rgba(31, 107, 74, 0.12);
    color: #1f6b4a;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.72rem;
    font-weight: 800;
    transition: var(--gm-transition);
}

.diag-tab-btn.active .tab-badge {
    background: #f3efe6;
    color: #1f6b4a;
}

.diag-panel {
    display: none !important;
    width: 100%;
}

.diag-panel.active {
    display: block !important;
    animation: diagFade 0.22s ease-in-out;
}

@keyframes diagFade {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}

.diag-header-badge {
    background: #f3efe6;
    border: 1.5px solid rgba(31, 107, 74, 0.25);
    border-left: 5px solid #1f6b4a;
    border-radius: var(--gm-radius-sm);
    padding: 12px 16px;
    display: flex;
    flex-direction: column;
    gap: 3px;
    margin-bottom: 14px;
}

.diag-badge-title {
    font-size: 0.92rem;
    font-weight: 800;
    color: #1f6b4a;
    display: flex;
    align-items: center;
    gap: 8px;
}

.diag-badge-desc {
    font-size: 0.76rem;
    color: #1f6b4a;
    opacity: 0.85;
    font-weight: 600;
}

.diag-search-input {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid rgba(31, 107, 74, 0.28);
    border-radius: var(--gm-radius-sm);
    font-size: 0.86rem;
    background: #ffffff;
    color: #1f6b4a;
    font-weight: 600;
    outline: none;
    font-family: inherit;
    transition: var(--gm-transition);
}

.diag-search-input:focus {
    border-color: #1f6b4a;
    box-shadow: 0 0 0 3px rgba(31, 107, 74, 0.15);
}

.diag-results {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: #ffffff;
    border: 2px solid #1f6b4a;
    border-radius: var(--gm-radius-md);
    z-index: 600;
    display: none;
    box-shadow: 0 10px 28px rgba(31, 107, 74, 0.18);
    max-height: 250px;
    overflow-y: auto;
}

.diag-results .ts-item {
    padding: 10px 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(31, 107, 74, 0.12);
    transition: var(--gm-transition);
}

.diag-results .ts-item:last-child {
    border-bottom: none;
}

.diag-results .ts-item:hover {
    background: #f3efe6;
}

.diag-cart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.diag-cart-header strong {
    font-size: 0.84rem;
    color: #1f6b4a;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 7px;
}

.diag-cart-count {
    font-size: 0.74rem;
    font-weight: 800;
    color: #1f6b4a;
    background: #f3efe6;
    border: 1.5px solid #1f6b4a;
    padding: 2px 10px;
    border-radius: 12px;
}

#s-diag-orders .cart-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 14px;
    background: #f3efe6;
    border: 1.5px solid rgba(31, 107, 74, 0.22);
    border-radius: var(--gm-radius-sm);
    margin-bottom: 8px;
    transition: var(--gm-transition);
}

#s-diag-orders .cart-row:hover {
    border-color: #1f6b4a;
    box-shadow: 0 2px 8px rgba(31, 107, 74, 0.08);
}

#s-diag-orders .cart-row-n {
    flex: 1;
    font-weight: 750;
    color: #1f6b4a;
    font-size: 0.88rem;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

#s-diag-orders .cart-row-n .badge {
    background: #1f6b4a;
    color: #f3efe6;
    font-size: 0.72rem;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 4px;
}

#s-diag-orders .rm-btn {
    background: #ffffff;
    color: #1f6b4a;
    border: 1.5px solid #1f6b4a;
    border-radius: var(--gm-radius-sm);
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--gm-transition);
}

#s-diag-orders .rm-btn:hover {
    background: #1f6b4a;
    color: #f3efe6;
    border-color: #1f6b4a;
}

#s-diag-orders .btn-sv-out {
    background: #1f6b4a;
    color: #f3efe6;
    border: 2px solid #1f6b4a;
    border-radius: var(--gm-radius-sm);
    padding: 10px 22px;
    font-weight: 800;
    font-size: 0.88rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(31, 107, 74, 0.22);
    transition: var(--gm-transition);
    font-family: inherit;
}

#s-diag-orders .btn-sv-out:hover {
    background: #154c34;
    border-color: #154c34;
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(31, 107, 74, 0.3);
}

#s-diag-history .ht thead th {
    background: #1f6b4a;
    color: #f3efe6;
    font-weight: 800;
    font-size: 0.74rem;
    padding: 10px 14px;
    letter-spacing: 0.3px;
    border-bottom: 2px solid #154c34;
}

#s-diag-history .ht tbody td {
    color: #1f6b4a;
    padding: 9px 14px;
    border-bottom: 1px solid rgba(31, 107, 74, 0.12);
    font-size: 0.82rem;
}

#s-diag-history .ht tbody tr:hover td {
    background: #f3efe6;
}

/* ══════════════════════════════════════════════════════════════
   13. STICKY GLOBAL BATCH SAVE BAR & BUTTON
   ══════════════════════════════════════════════════════════════ */
.sticky-save-bar {
    position: sticky;
    bottom: 18px;
    z-index: 90;
    background: var(--gm-surface);
    border: 2px solid var(--gm-primary);
    border-radius: var(--gm-radius-xl);
    padding: 16px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    box-shadow: 0 10px 30px rgba(31, 107, 74, 0.18), 0 2px 8px rgba(0, 0, 0, 0.08);
    margin-top: 24px;
    grid-column: 1 / -1;
    backdrop-filter: blur(8px);
}

.sticky-save-bar h4 {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--gm-primary);
    letter-spacing: -0.2px;
    margin: 0 0 3px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.sticky-save-bar p {
    font-size: 0.82rem;
    color: var(--gm-text-muted);
    font-weight: 500;
    margin: 0;
}

.btn-sv-main {
    background: linear-gradient(135deg, var(--gm-primary) 0%, var(--gm-primary-dark) 100%);
    color: #f3efe6;
    border: none;
    padding: 12px 28px;
    border-radius: var(--gm-radius-md);
    font-weight: 800;
    font-size: 0.95rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 4px 16px var(--gm-primary-glow);
    transition: var(--gm-transition);
    min-height: 46px;
    font-family: inherit;
    letter-spacing: 0.3px;
    white-space: nowrap;
}

.btn-sv-main:hover {
    background: linear-gradient(135deg, var(--gm-primary-dark) 0%, var(--gm-primary-deep) 100%);
    color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px var(--gm-primary-glow);
}

.btn-sv-main:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px var(--gm-primary-glow);
}

.btn-sv-main:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

@media (max-width: 767px) {
    .sticky-save-bar {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
        padding: 14px 18px;
        bottom: 12px;
    }
    .btn-sv-main {
        width: 100%;
        justify-content: center;
    }
}

/* ══════════════════════════════════════════════════════════════
   14. BLOOD TRANSFUSION SEQUENTIAL STAGE ROWS
   ══════════════════════════════════════════════════════════════ */
.transfusion-stages-wrap {
    margin-top: 14px;
    margin-bottom: 14px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    width: 100%;
}

.transfusion-stages-header {
    font-size: 0.78rem;
    font-weight: 800;
    color: var(--gm-primary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 6px;
    padding-bottom: 4px;
    border-bottom: 1.5px dashed var(--gm-border-light);
    margin-bottom: 2px;
}

.tf-stage-row {
    display: flex;
    align-items: stretch;
    background: #ffffff;
    border: 1.5px solid var(--gm-border);
    border-radius: var(--gm-radius-md);
    overflow: hidden;
    transition: var(--gm-transition);
    box-shadow: 0 1px 4px rgba(31, 107, 74, 0.04);
}

.tf-stage-row:hover {
    border-color: var(--gm-primary);
    box-shadow: 0 3px 12px rgba(31, 107, 74, 0.08);
}

.tf-stage-badge {
    min-width: 175px;
    max-width: 185px;
    padding: 10px 14px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    background: var(--gm-bg);
    border-right: 1.5px solid var(--gm-border-light);
    flex-shrink: 0;
}

.tf-stage-badge i {
    font-size: 1.05rem;
    color: var(--gm-primary);
    margin-bottom: 2px;
}

.tf-stage-badge span {
    font-size: 0.68rem;
    text-transform: uppercase;
    font-weight: 800;
    color: var(--gm-text-muted);
    letter-spacing: 0.5px;
}

.tf-stage-badge strong {
    font-size: 0.82rem;
    font-weight: 800;
    color: var(--gm-primary);
    line-height: 1.25;
}

.tf-stage-field {
    flex: 1;
    padding: 10px 14px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 4px;
    background: #ffffff;
}

.tf-stage-field label {
    font-size: 0.72rem;
    font-weight: 750;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: var(--gm-primary);
    display: flex;
    align-items: center;
    gap: 4px;
    margin: 0;
}

.tf-stage-field input {
    width: 100%;
    padding: 9px 12px;
    border-radius: var(--gm-radius-sm);
    border: 1.5px solid var(--gm-border-light);
    font-size: 0.88rem;
    outline: none;
    background: var(--gm-bg);
    color: var(--gm-text-body);
    font-weight: 600;
    font-family: inherit;
    transition: var(--gm-transition);
}

.tf-stage-field input:focus {
    background: #ffffff;
    border-color: var(--gm-primary);
    box-shadow: 0 0 0 3px var(--gm-primary-light);
}

@media (max-width: 767px) {
    .tf-stage-row {
        flex-direction: column;
    }
    .tf-stage-badge {
        max-width: 100%;
        min-width: 100%;
        border-right: none;
        border-bottom: 1.5px solid var(--gm-border-light);
        padding: 8px 12px;
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

    <div class="main-content">
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
            <div class="dash-patient-card dash-card" data-search="<?php echo htmlspecialchars($searchKey); ?>" data-patient="<?php echo htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8'); ?>" onclick="selectPatientCard(this)">
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
        <button class="pba ksheet" onclick="goToKSheet()"><i class="fas fa-file-medical-alt"></i> View Case-Sheet</button>
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
                    <option>Death</option>
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

        <!-- 2. BP Chart -->
        <div class="card-new full-width ws-sec cat-vitals" id="s-bp">
          <div class="card-title-new">
            <span><i class="fas fa-heartbeat"></i> 2. BP Chart</span>
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
                <div class="fmg"><label>Pain Score (0-10)</label><input type="number" name="pain_score" min="0" max="10" placeholder="e.g. 0-10"></div>
                <div class="fmg" style="grid-column: 1 / -1;"><label>Nurse Signature</label><input type="text" name="bp_nurse" value="<?php echo htmlspecialchars($nurseName); ?>" readonly></div>
              </div>
              <button class="btn-sv-out btn-sv" data-ct="bp_chart" data-f="f-bp"><i class="fas fa-plus"></i> Add BP Record</button>
            </div>
            <div class="split-right">
              <div class="ht-title"><i class="fas fa-history"></i> Recent BP Logs</div>
              <div class="ht-wrap">
                <table class="ht">
                  <thead><tr><th>Date &amp; Time</th><th>BP</th><th>Pulse</th><th>Temp</th><th>SpO2</th><th>Pain</th><th>By</th><th>Action</th></tr></thead>
                  <tbody id="h-bp"><tr class="et"><td colspan="8">No records yet.</td></tr></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

                <!-- 3. Vitals Chart (GCS – Glasgow Coma Scale) -->
        <div class="card-new full-width ws-sec cat-vitals" id="s-gcs">
          <div class="card-title-new">
            <span><i class="fas fa-brain"></i> 3. Vitals Chart – Glasgow Coma Scale (GCS)</span>
          </div>
          <div class="split-card card-body" id="f-gcs">
            <div class="split-left">
              <div class="fg">

                <!-- GCS date/time linked to bp_chart store -->
                <div class="fmg"><label><i class="far fa-calendar-alt"></i> Date</label><input type="date" name="gcs_date"></div>
                <div class="fmg"><label><i class="far fa-clock"></i> Time</label><input type="time" name="gcs_time"></div>

                <!-- Glasgow Coma Scale (GCS) Clinical Assessment -->
                <div class="gcs-streamlined-card">
                  <div class="gcs-summary-bar">
                    <div class="gcs-score-badge-wrap">
                      <div class="gcs-dial-pill gcs-mild" id="gcs-adv-score-dial">
                        <span class="gcs-dial-num" id="gcs-score-num">15</span>
                        <span class="gcs-dial-max">/ 15</span>
                      </div>
                      <div class="gcs-status-meta">
                        <div class="gcs-status-badge gcs-mild" id="gcs-severity-label">
                          <i class="fas fa-check-circle"></i> Mild Impairment / Normal (Alert)
                        </div>
                        <div class="gcs-status-desc">Mild: 13–15 • Moderate: 9–12 • Severe: 3–8</div>
                      </div>
                    </div>
                    <div class="gcs-breakdown-tags">
                      <span class="gcs-tag"><i class="fas fa-eye"></i> Eye: <strong id="pill-eye-val">4/4</strong></span>
                      <span class="gcs-tag"><i class="fas fa-comment-medical"></i> Verbal: <strong id="pill-verbal-val">5/5</strong></span>
                      <span class="gcs-tag"><i class="fas fa-walking"></i> Motor: <strong id="pill-motor-val">6/6</strong></span>
                    </div>
                  </div>

                  <!-- 3 Standard Select Dropdowns: Eye, Verbal, Motor -->
                  <div class="gcs-selects-grid">
                    <div class="fmg">
                      <label><i class="fas fa-eye"></i> Eye Opening Response (1–4)</label>
                      <select id="sel_gcs_eye" class="gcs-native-select" onchange="onGcsDropdownChange()">
                        <option value="D" data-score="4" data-desc="Spontaneously" data-code="E4" selected>4 — Spontaneously (E4)</option>
                        <option value="C" data-score="3" data-desc="To Speech" data-code="E3">3 — To Speech (E3)</option>
                        <option value="B" data-score="2" data-desc="To Pain" data-code="E2">2 — To Pain (E2)</option>
                        <option value="A" data-score="1" data-desc="No Response" data-code="E1">1 — No Response (E1)</option>
                      </select>
                    </div>

                    <div class="fmg">
                      <label><i class="fas fa-comment-medical"></i> Verbal Response (1–5)</label>
                      <select id="sel_gcs_verbal" class="gcs-native-select" onchange="onGcsDropdownChange()">
                        <option value="E" data-score="5" data-desc="Oriented" data-code="V5" selected>5 — Oriented (V5)</option>
                        <option value="D" data-score="4" data-desc="Confused" data-code="V4">4 — Confused (V4)</option>
                        <option value="C" data-score="3" data-desc="Inappropriate Words" data-code="V3">3 — Inappropriate Words (V3)</option>
                        <option value="B" data-score="2" data-desc="Incomprehensible Sounds" data-code="V2">2 — Incomprehensible Sounds (V2)</option>
                        <option value="A" data-score="1" data-desc="No Response" data-code="V1">1 — No Response (V1)</option>
                      </select>
                    </div>

                    <div class="fmg">
                      <label><i class="fas fa-walking"></i> Motor Response (1–6)</label>
                      <select id="sel_gcs_motor" class="gcs-native-select" onchange="onGcsDropdownChange()">
                        <option value="G" data-score="6" data-desc="Obeys Commands" data-code="M6" selected>6 — Obeys Commands (M6)</option>
                        <option value="F" data-score="5" data-desc="Localizes Pain" data-code="M5">5 — Localizes Pain (M5)</option>
                        <option value="E" data-score="4" data-desc="Withdrawal from Pain" data-code="M4">4 — Normal Flexion / Withdrawal (M4)</option>
                        <option value="D" data-score="3" data-desc="Abnormal Flexion to Pain" data-code="M3">3 — Abnormal Flexion (Decorticate) (M3)</option>
                        <option value="C" data-score="2" data-desc="Abnormal Extension to Pain" data-code="M2">2 — Abnormal Extension (Decerebrate) (M2)</option>
                        <option value="A" data-score="1" data-desc="No Motor Response to Pain" data-code="M1">1 — No Motor Response to Pain (M1)</option>
                      </select>
                    </div>
                  </div>

                  <!-- Hidden Form Inputs for Record Submission (Maintains 100% backend compatibility) -->
                  <input type="hidden" name="gcs" id="gcs_total_input" value="15/15 (E4 V5 M6)">
                  <input type="hidden" name="gcs_score" id="gcs_score_input" value="15">
                  <input type="hidden" name="gcs_eye" id="gcs_eye_input" value="D. Spontaneously (E4)">
                  <input type="hidden" name="gcs_eye_score" id="gcs_eye_score_input" value="4">
                  <input type="hidden" name="gcs_verbal" id="gcs_verbal_input" value="E. Oriented (V5)">
                  <input type="hidden" name="gcs_verbal_score" id="gcs_verbal_score_input" value="5">
                  <input type="hidden" name="gcs_motor" id="gcs_motor_input" value="G. Obeys Commands (M6)">
                  <input type="hidden" name="gcs_motor_score" id="gcs_motor_score_input" value="6">
                  <input type="hidden" name="consciousness_level" id="gcs_consciousness_input" value="15/15 (Alert)">
                </div>

                <div class="fmg" style="grid-column: 1 / -1;"><label><i class="fas fa-signature"></i> Nurse Signature</label><input type="text" name="gcs_nurse" value="<?php echo htmlspecialchars($nurseName); ?>" readonly></div>
              </div>
              <button class="btn-sv-out btn-sv" data-ct="gcs_chart" data-f="f-gcs"><i class="fas fa-brain"></i> Save GCS Assessment</button>
            </div>
            <div class="split-right">
              <div class="ht-title"><i class="fas fa-history"></i> Recent GCS Assessments</div>
              <div class="ht-wrap">
                <table class="ht">
                  <thead><tr><th>Date &amp; Time</th><th>GCS Score</th><th>Eye (E)</th><th>Verbal (V)</th><th>Motor (M)</th><th>Consciousness</th><th>By</th><th>Action</th></tr></thead>
                  <tbody id="h-gcs"><tr class="et"><td colspan="8">No GCS records yet.</td></tr></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- 4. GRBS Chart -->
        <div class="card-new ws-sec cat-vitals" id="s-gr">
          <div class="card-title-new">
            <span><i class="fas fa-vial"></i> 4. GRBS (Blood Sugar) Chart</span>
          </div>
          <div class="split-card card-body" id="f-gr">
            <div class="split-left">
              <div class="fg">
                <div class="fmg"><label>Date</label><input type="date" name="grbs_date"></div>
                <div class="fmg"><label>Time</label><input type="time" name="grbs_time"></div>
                <div class="fmg"><label>GRBS (mg/dL)</label><input type="number" name="grbs_value" placeholder="e.g. 120"></div>
                <div class="fmg"><label>Nurse Signature</label><input type="text" name="grbs_nurse" value="<?php echo htmlspecialchars($nurseName); ?>" readonly></div>
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

        <!-- 5. Doctor Visits -->
        <div class="card-new ws-sec cat-doctor" id="s-vi">
          <div class="card-title-new">
            <span><i class="fas fa-user-md"></i> 5. Consultant Round Visits</span>
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
                <div class="fmg"><label>Nurse Signature</label><input type="text" name="nurse_sign" value="<?php echo htmlspecialchars($nurseName); ?>" readonly></div>
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

        <!-- 7. Diagnostic Test Orders Requisition -->
        <div class="card-new ws-sec cat-orders full-width" id="s-diag-orders">
          <div class="card-title-new" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <span><i class="fas fa-stethoscope"></i> 7. Diagnostic Orders Requisition</span>
            <div class="diag-view-nav">
              <button type="button" class="diag-tab-btn active" id="btn-tab-order-lab" onclick="switchDiagOrderView('lab')">
                <i class="fas fa-flask"></i> Laboratory Tests <span class="tab-badge" id="badge-cart-lab">0</span>
              </button>
              <button type="button" class="diag-tab-btn" id="btn-tab-order-rad" onclick="switchDiagOrderView('radiology')">
                <i class="fas fa-x-ray"></i> Radiology &amp; Imaging <span class="tab-badge" id="badge-cart-rad">0</span>
              </button>
              <button type="button" class="diag-tab-btn" id="btn-tab-order-oth" onclick="switchDiagOrderView('other')">
                <i class="fas fa-heartbeat"></i> Other Investigations <span class="tab-badge" id="badge-cart-oth">0</span>
              </button>
            </div>
          </div>

          <div class="card-body" id="f-ts" style="padding: 16px 20px;">
            
            <!-- VIEW 1: Diagnostic LAB Order -->
            <div class="diag-panel active" id="diag-panel-order-lab">
              <div class="diag-header-badge">
                <div class="diag-badge-title"><i class="fas fa-flask"></i> Clinical Pathology, Hematology &amp; Biochemistry</div>
                <div class="diag-badge-desc">Order inpatient laboratory tests, blood profiles, and biochemistry investigations</div>
              </div>
              <div class="fg" style="margin-top: 14px;">
                <div class="fmg" style="position: relative; grid-column: 1 / -1;">
                  <label><i class="fas fa-search"></i> Search Laboratory Tests</label>
                  <input type="text" id="diag-lab-input" class="diag-search-input" placeholder="Type test name e.g. CBC, Lipid Profile, LFT, Urine Sugar, Serum Electrolytes..." autocomplete="off">
                  <div id="diag-lab-results" class="diag-results"></div>
                </div>
              </div>
              <div style="margin-top: 16px; background: #ffffff; border: 1.5px solid var(--gm-border); border-radius: var(--gm-radius-md); padding: 14px 16px;">
                <div class="diag-cart-header">
                  <strong><i class="fas fa-clipboard-list"></i> Selected Lab Order Cart:</strong>
                  <span id="lab-cart-count" class="diag-cart-count">0 tests selected</span>
                </div>
                <div id="diag-lab-cart"></div>
                <div style="display:flex; justify-content:flex-end; margin-top:14px; border-top: 1px solid var(--gm-border-light); padding-top: 12px;">
                  <button class="btn-sv-out btn-sv" id="btn-save-lab" onclick="submitDiagOrder('lab')">
                    <i class="fas fa-paper-plane"></i> Save &amp; Dispatch Diagnostic LAB Order
                  </button>
                </div>
              </div>
            </div>

            <!-- VIEW 2: Diagnostic RADIOLOGY Order -->
            <div class="diag-panel" id="diag-panel-order-rad">
              <div class="diag-header-badge">
                <div class="diag-badge-title"><i class="fas fa-x-ray"></i> Inpatient Imaging, Scans &amp; Bedside Radiographs</div>
                <div class="diag-badge-desc">Order radiographs, ultrasound, CT brain/body, MRI, and Doppler imaging scans</div>
              </div>
              <div class="fg" style="margin-top: 14px;">
                <div class="fmg" style="position: relative; grid-column: 1 / -1;">
                  <label><i class="fas fa-search"></i> Search Radiology Scans &amp; Imaging</label>
                  <input type="text" id="diag-rad-input" class="diag-search-input" placeholder="Type scan name e.g. Chest X-Ray, Cervical Spine, CT Brain, USG Abdomen, Doppler..." autocomplete="off">
                  <div id="diag-rad-results" class="diag-results"></div>
                </div>
              </div>
              <div style="margin-top: 16px; background: #ffffff; border: 1.5px solid var(--gm-border); border-radius: var(--gm-radius-md); padding: 14px 16px;">
                <div class="diag-cart-header">
                  <strong><i class="fas fa-clipboard-list"></i> Selected Radiology Order Cart:</strong>
                  <span id="rad-cart-count" class="diag-cart-count">0 scans selected</span>
                </div>
                <div id="diag-rad-cart"></div>
                <div style="display:flex; justify-content:flex-end; margin-top:14px; border-top: 1px solid var(--gm-border-light); padding-top: 12px;">
                  <button class="btn-sv-out btn-sv" id="btn-save-rad" onclick="submitDiagOrder('radiology')">
                    <i class="fas fa-paper-plane"></i> Save &amp; Dispatch Diagnostic RADIOLOGY Order
                  </button>
                </div>
              </div>
            </div>

            <!-- VIEW 3: Diagnostic OTHER Order -->
            <div class="diag-panel" id="diag-panel-order-oth">
              <div class="diag-header-badge">
                <div class="diag-badge-title"><i class="fas fa-heartbeat"></i> Bedside ECG, Echo &amp; Special Investigations</div>
                <div class="diag-badge-desc">Order cardiac diagnostics, endoscopy, bedside procedures and specialized investigations</div>
              </div>
              <div class="fg" style="margin-top: 14px;">
                <div class="fmg" style="position: relative; grid-column: 1 / -1;">
                  <label><i class="fas fa-search"></i> Search Other Investigations</label>
                  <input type="text" id="diag-oth-input" class="diag-search-input" placeholder="Type investigation name e.g. 12-Lead ECG, 2D Echo, Dialysis..." autocomplete="off">
                  <div id="diag-oth-results" class="diag-results"></div>
                </div>
              </div>
              <div style="margin-top: 16px; background: #ffffff; border: 1.5px solid var(--gm-border); border-radius: var(--gm-radius-md); padding: 14px 16px;">
                <div class="diag-cart-header">
                  <strong><i class="fas fa-clipboard-list"></i> Selected Other Investigations Cart:</strong>
                  <span id="oth-cart-count" class="diag-cart-count">0 investigations selected</span>
                </div>
                <div id="diag-oth-cart"></div>
                <div style="display:flex; justify-content:flex-end; margin-top:14px; border-top: 1px solid var(--gm-border-light); padding-top: 12px;">
                  <button class="btn-sv-out btn-sv" id="btn-save-oth" onclick="submitDiagOrder('other')">
                    <i class="fas fa-paper-plane"></i> Save &amp; Dispatch Diagnostic OTHER Order
                  </button>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- 7. Inpatient Diagnostic Investigation History -->
        <div class="card-new ws-sec cat-orders full-width" id="s-diag-history">
          <div class="card-title-new" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <span><i class="fas fa-history"></i> 7. Inpatient Diagnostic Investigation History</span>
            <div class="diag-view-nav">
              <button type="button" class="diag-tab-btn active" id="btn-tab-hist-lab" onclick="switchDiagHistView('lab')">
                <i class="fas fa-flask"></i> Laboratory History <span class="tab-badge" id="badge-hist-lab">0</span>
              </button>
              <button type="button" class="diag-tab-btn" id="btn-tab-hist-rad" onclick="switchDiagHistView('radiology')">
                <i class="fas fa-x-ray"></i> Radiology History <span class="tab-badge" id="badge-hist-rad">0</span>
              </button>
              <button type="button" class="diag-tab-btn" id="btn-tab-hist-oth" onclick="switchDiagHistView('other')">
                <i class="fas fa-heartbeat"></i> Other History <span class="tab-badge" id="badge-hist-oth">0</span>
              </button>
            </div>
          </div>

          <div class="card-body" style="padding: 16px 20px;">
            <!-- LAB History Panel -->
            <div class="diag-panel active" id="diag-panel-hist-lab">
              <div class="ht-wrap" style="max-height: 250px;">
                <table class="ht">
                  <thead><tr><th>Order Date &amp; Time</th><th>Laboratory Test Name</th><th>Code / ID</th><th>Category</th><th>Qty</th><th>Ordered By</th></tr></thead>
                  <tbody id="h-ts-lab"><tr class="et"><td colspan="6">No Laboratory tests ordered for this patient yet.</td></tr></tbody>
                </table>
              </div>
            </div>

            <!-- RADIOLOGY History Panel -->
            <div class="diag-panel" id="diag-panel-hist-rad">
              <div class="ht-wrap" style="max-height: 250px;">
                <table class="ht">
                  <thead><tr><th>Order Date &amp; Time</th><th>Radiology Scan Name</th><th>Scan Code</th><th>Modality</th><th>Qty</th><th>Ordered By</th></tr></thead>
                  <tbody id="h-ts-rad"><tr class="et"><td colspan="6">No Radiology scans ordered for this patient yet.</td></tr></tbody>
                </table>
              </div>
            </div>

            <!-- OTHER History Panel -->
            <div class="diag-panel" id="diag-panel-hist-oth">
              <div class="ht-wrap" style="max-height: 250px;">
                <table class="ht">
                  <thead><tr><th>Order Date &amp; Time</th><th>Investigation Name</th><th>Code / ID</th><th>Category</th><th>Qty</th><th>Ordered By</th></tr></thead>
                  <tbody id="h-ts-oth"><tr class="et"><td colspan="6">No Other diagnostic investigations ordered for this patient yet.</td></tr></tbody>
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

        <!-- 8A. Nebulization Record -->
        <div class="card-new ws-sec cat-treatments" id="s-nb">
          <div class="card-title-new">
            <span><i class="fas fa-wind"></i> 8A. Nebulization Record</span>
          </div>
          <div class="split-card card-body" id="f-nb">
            <div class="split-left">
              <div class="fg">
                <div class="fmg"><label>Date</label><input type="date" name="nebu_date"></div>
                <div class="fmg"><label>Time</label><input type="time" name="nebu_time"></div>
                <div class="fmg"><label>Drug / Medicine</label><input type="text" name="nebu_drug" placeholder="e.g. Duolin, Budecort"></div>
                <div class="fmg"><label>Frequency</label><input type="text" name="nebu_freq" placeholder="e.g. TID / Q8H / SOS"></div>
                <div class="fmg" style="grid-column: 1 / -1;"><label>Nurse Signature</label><input type="text" name="nebu_nurse" value="<?php echo htmlspecialchars($nurseName); ?>" readonly></div>
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

        <!-- 8B. Dialysis Record -->
        <div class="card-new ws-sec cat-treatments" id="s-di">
          <div class="card-title-new">
            <span><i class="fas fa-filter"></i> 8B. Dialysis Record</span>
          </div>
          <div class="split-card card-body" id="f-di">
            <div class="split-left">
              <input type="hidden" name="dia_date" id="dia-date-hidden">
              <div class="fg">
                <div class="fmg">
                  <label><i class="fas fa-procedures"></i> Dialysis Type / Access</label>
                  <input type="text" name="dia_type" placeholder="e.g. Hemodialysis, AV Fistula / CRRT">
                </div>
                <div class="fmg">
                  <label><i class="fas fa-calendar-plus"></i> Start Date & Time</label>
                  <input type="datetime-local" name="dia_start_dt" class="tcs-dt" onchange="calcDur(this)">
                </div>
                <div class="fmg">
                  <label><i class="fas fa-calendar-check"></i> End Date & Time</label>
                  <input type="datetime-local" name="dia_end_dt" class="tce-dt" onchange="calcDur(this)">
                </div>
                <div class="fmg" style="justify-content: flex-end; padding-bottom: 2px;">
                  <label class="ongoing-check">
                    <input type="checkbox" name="is_ongoing" value="1" class="chk-ongoing" onchange="toggleOngoingTreatment(this)">
                    <span><i class="fas fa-sync-alt"></i> Continuous / Ongoing Dialysis</span>
                  </label>
                </div>
                <div class="fmg" style="grid-column: 1 / -1;">
                  <label><i class="fas fa-hourglass-half"></i> Total Duration (Auto Calculated)</label>
                  <input type="text" name="dia_dur" class="tcd" placeholder="Auto calculated duration (e.g. 4h 0m or 2 Days)">
                </div>
                <div class="fmg" style="grid-column: 1 / -1;">
                  <label>Nurse Signature</label>
                  <input type="text" name="dia_nurse" value="<?php echo htmlspecialchars($nurseName); ?>" readonly>
                </div>
              </div>
              <button class="btn-sv-out btn-sv" data-ct="dialysis_chart" data-f="f-di"><i class="fas fa-plus"></i> Add Dialysis</button>
            </div>
            <div class="split-right">
              <div class="ht-title"><i class="fas fa-history"></i> Recent Dialysis</div>
              <div class="ht-wrap">
                <table class="ht">
                  <thead><tr><th>Date</th><th>Type / Access</th><th>Duration & Period</th><th>Nurse</th><th>Action</th></tr></thead>
                  <tbody id="h-di"><tr class="et"><td colspan="5">No records yet.</td></tr></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- 8C. Oxygen Therapy -->
        <div class="card-new ws-sec cat-treatments" id="s-ox">
          <div class="card-title-new">
            <span><i class="fas fa-lungs"></i> 8C. Oxygen Therapy</span>
          </div>
          <div class="split-card card-body" id="f-ox">
            <div class="split-left">
              <input type="hidden" name="oxy_date" id="oxy-date-hidden">
              <div class="fg">
                <div class="fmg">
                  <label><i class="fas fa-tachometer-alt"></i> Flow Rate / Device</label>
                  <input type="text" name="oxy_flow" placeholder="e.g. 2 L/min, Nasal Cannula, Face Mask">
                </div>
                <div class="fmg">
                  <label><i class="fas fa-calendar-plus"></i> Start Date & Time</label>
                  <input type="datetime-local" name="oxy_start_dt" class="tcs-dt" onchange="calcDur(this)">
                </div>
                <div class="fmg">
                  <label><i class="fas fa-calendar-check"></i> End Date & Time</label>
                  <input type="datetime-local" name="oxy_end_dt" class="tce-dt" onchange="calcDur(this)">
                </div>
                <div class="fmg" style="justify-content: flex-end; padding-bottom: 2px;">
                  <label class="ongoing-check">
                    <input type="checkbox" name="is_ongoing" value="1" class="chk-ongoing" onchange="toggleOngoingTreatment(this)">
                    <span><i class="fas fa-sync-alt"></i> Continuous / Ongoing</span>
                  </label>
                </div>
                <div class="fmg" style="grid-column: 1 / -1;">
                  <label><i class="fas fa-hourglass-half"></i> Total Duration (Auto Calculated)</label>
                  <input type="text" name="oxy_dur" class="tcd" placeholder="Auto calculated duration (e.g. 2 Days 4 Hours)">
                </div>
                <div class="fmg" style="grid-column: 1 / -1;">
                  <label>Nurse Signature</label>
                  <input type="text" name="oxy_nurse" value="<?php echo htmlspecialchars($nurseName); ?>" readonly>
                </div>
              </div>
              <button class="btn-sv-out btn-sv" data-ct="oxygen_chart" data-f="f-ox"><i class="fas fa-plus"></i> Add Oxygen</button>
            </div>
            <div class="split-right">
              <div class="ht-title"><i class="fas fa-history"></i> Recent Oxygen Therapy</div>
              <div class="ht-wrap">
                <table class="ht">
                  <thead><tr><th>Date</th><th>Flow Rate</th><th>Duration & Period</th><th>Nurse</th><th>Action</th></tr></thead>
                  <tbody id="h-ox"><tr class="et"><td colspan="5">No records yet.</td></tr></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- 8D. Ventilator Support -->
        <div class="card-new full-width ws-sec cat-treatments" id="s-ve">
          <div class="card-title-new">
            <span><i class="fas fa-procedures"></i> 8D. Ventilator Support</span>
          </div>
          <div class="split-card card-body" id="f-ve">
            <div class="split-left">
              <input type="hidden" name="vent_date" id="vent-date-hidden">
              <div class="fg">
                <div class="fmg">
                  <label><i class="fas fa-lungs"></i> Ventilation Type</label>
                  <select name="vent_type" id="vent_type_select" onchange="onVentTypeChange(this.value)">
                    <option value="">-- Select Ventilation Type --</option>
                    <option value="Invasive Ventilation">1. Invasive Ventilation</option>
                    <option value="Non-Invasive Ventilation (NIV)">2. Non-Invasive Ventilation (NIV)</option>
                  </select>
                </div>
                <div class="fmg">
                  <label><i class="fas fa-sliders-h"></i> Vent Mode</label>
                  <select name="vent_mode" id="vent_mode_select">
                    <option value="">-- Select Ventilation Type First --</option>
                  </select>
                </div>
                <div class="fmg">
                  <label><i class="fas fa-calendar-plus"></i> Start Date & Time</label>
                  <input type="datetime-local" name="vent_start_dt" class="tcs-dt" onchange="calcDur(this)">
                </div>
                <div class="fmg">
                  <label><i class="fas fa-calendar-check"></i> End Date & Time</label>
                  <input type="datetime-local" name="vent_end_dt" class="tce-dt" onchange="calcDur(this)">
                </div>
                <div class="fmg" style="justify-content: flex-end; padding-bottom: 2px;">
                  <label class="ongoing-check">
                    <input type="checkbox" name="is_ongoing" value="1" class="chk-ongoing" onchange="toggleOngoingTreatment(this)">
                    <span><i class="fas fa-procedures"></i> Active on Ventilator</span>
                  </label>
                </div>
                <div class="fmg" style="grid-column: 1 / -1;">
                  <label><i class="fas fa-hourglass-half"></i> Total Duration (Auto Calculated)</label>
                  <input type="text" name="vent_dur" class="tcd" placeholder="Auto calculated duration (e.g. 3 Days 8 Hours)">
                </div>
                <div class="fmg" style="grid-column: 1 / -1;">
                  <label>Nurse Signature</label>
                  <input type="text" name="vent_nurse" value="<?php echo htmlspecialchars($nurseName); ?>" readonly>
                </div>
              </div>
              <button class="btn-sv-out btn-sv" data-ct="ventilation_chart" data-f="f-ve"><i class="fas fa-plus"></i> Add Ventilator</button>
            </div>
            <div class="split-right">
              <div class="ht-title"><i class="fas fa-history"></i> Recent Ventilator</div>
              <div class="ht-wrap">
                <table class="ht">
                  <thead><tr><th>Date</th><th>Mode</th><th>Duration & Period</th><th>Nurse</th><th>Action</th></tr></thead>
                  <tbody id="h-ve"><tr class="et"><td colspan="5">No records yet.</td></tr></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- 8E. Blood Transfusion Record -->
        <div class="card-new full-width ws-sec cat-treatments" id="s-bl">
          <div class="card-title-new">
            <span><i class="fas fa-syringe"></i> 8E. Blood Transfusion Record</span>
          </div>
          <div class="split-card card-body" id="f-bl">
            <div class="split-left">
              <div class="fg">
                <div class="fmg"><label><i class="fas fa-calendar-day"></i> Date</label><input type="date" name="trans_date"></div>
                <div class="fmg"><label><i class="fas fa-tint"></i> Blood Group</label><input type="text" name="blood_group" placeholder="e.g. O+ / AB+"></div>
                <div class="fmg">
                  <label><i class="fas fa-vial"></i> Blood Type</label>
                  <select name="blood_type" id="bt_type_select" onchange="onBloodTypeChange(this.value)">
                    <option value="">-- Select Blood Type --</option>
                    <option value="PRBC (Packed Red Blood Cells)">1. PRBC (Packed Red Blood Cells)</option>
                    <option value="Whole Blood">2. Whole Blood</option>
                    <option value="Platelets">3. Platelets</option>
                    <option value="Plasma Products">4. Plasma Products</option>
                    <option value="Other">5. Other</option>
                  </select>
                </div>
                <div class="fmg" id="fmg_blood_subtype" style="display: none;">
                  <label id="lbl_blood_subtype"><i class="fas fa-layer-group"></i> Component Sub-Type</label>
                  <select name="blood_subtype" id="bt_subtype_select" onchange="onBloodSubtypeChange(this.value)">
                    <option value="">-- Select Sub-Type --</option>
                  </select>
                </div>
                <div class="fmg" id="fmg_blood_details" style="display: none;">
                  <label id="lbl_blood_details"><i class="fas fa-edit"></i> Product Details / Specifications</label>
                  <input type="text" name="blood_type_details" id="bt_type_details_input" placeholder="Enter details...">
                </div>
                <div class="fmg"><label><i class="fas fa-hashtag"></i> Bag Number</label><input type="text" name="bag_number" placeholder="e.g. 2563"></div>
                <div class="fmg"><label><i class="fas fa-calendar-plus"></i> Date of Collection</label><input type="date" name="collection_date"></div>
                <div class="fmg"><label><i class="fas fa-calendar-times"></i> Date of Expiry</label><input type="date" name="expiry_date"></div>
                <div class="fmg"><label><i class="fas fa-flask"></i> Qty (ml)</label><input type="number" name="quantity" placeholder="350"></div>
                <div class="fmg"><label><i class="fas fa-user-nurse"></i> Nurse Signature</label><input type="text" name="nurse_sign" value="<?php echo htmlspecialchars($nurseName); ?>" readonly></div>
              </div>

              <!-- 4 Sequential Rows (Full-width for each stage) -->
              <div class="transfusion-stages-wrap">
                <div class="transfusion-stages-header">
                  <i class="fas fa-notes-medical"></i> Transfusion Vitals & Clinical Protocol (Sequential Stages)
                </div>

                <!-- Stage 1: Pre-Medication Before Transfusion -->
                <div class="tf-stage-row">
                  <div class="tf-stage-badge">
                    <i class="fas fa-pills"></i>
                    <span>Stage 1</span>
                    <strong>Pre-Medication</strong>
                  </div>
                  <div class="fmg tf-stage-field">
                    <label><i class="fas fa-capsules"></i> PRE-Medication Before Transfusion</label>
                    <input type="text" name="pre_medication" placeholder="e.g. Inj Avil 1 amp IV, Inj Hydrocortisone 100mg IV, Tab Paracetamol 650mg">
                  </div>
                </div>

                <!-- Stage 2: Vitals Before Transfusion -->
                <div class="tf-stage-row">
                  <div class="tf-stage-badge">
                    <i class="fas fa-heartbeat"></i>
                    <span>Stage 2</span>
                    <strong>Baseline Vitals</strong>
                  </div>
                  <div class="fmg tf-stage-field">
                    <label><i class="fas fa-heartbeat"></i> Vitals Before Transfusion (Baseline)</label>
                    <input type="text" name="vitals_before" placeholder="e.g. BP: 120/80 mmHg | Pulse: 76 bpm | Temp: 98.4°F | SpO2: 98% | RR: 18/min">
                  </div>
                </div>

                <!-- Stage 3: Vitals During Transfusion -->
                <div class="tf-stage-row">
                  <div class="tf-stage-badge">
                    <i class="fas fa-stethoscope"></i>
                    <span>Stage 3</span>
                    <strong>Intra-Transfusion</strong>
                  </div>
                  <div class="fmg tf-stage-field">
                    <label><i class="fas fa-stethoscope"></i> Vitals During Transfusion (15 min / Hourly)</label>
                    <input type="text" name="vitals_during" placeholder="e.g. 15 min: BP 122/82, P 78, T 98.6°F, SpO2 98% | 1 hr: BP 120/80, P 76, T 98.6°F">
                  </div>
                </div>

                <!-- Stage 4: Vitals After Transfusion -->
                <div class="tf-stage-row">
                  <div class="tf-stage-badge">
                    <i class="fas fa-heart-pulse"></i>
                    <span>Stage 4</span>
                    <strong>Post-Transfusion</strong>
                  </div>
                  <div class="fmg tf-stage-field">
                    <label><i class="fas fa-heart-pulse"></i> Vitals After Transfusion (Post-Completion)</label>
                    <input type="text" name="vitals_after" placeholder="e.g. BP: 120/80 mmHg | Pulse: 74 bpm | Temp: 98.4°F | SpO2: 99% | No adverse reactions">
                  </div>
                </div>
              </div>

              <button class="btn-sv-out btn-sv" data-ct="blood_transfusion" data-f="f-bl"><i class="fas fa-plus"></i> Add Transfusion</button>
            </div>
            <div class="split-right">
              <div class="ht-title"><i class="fas fa-history"></i> Recent Transfusion History</div>
              <div class="ht-wrap">
                <table class="ht">
                  <thead><tr><th>Date</th><th>Group, Type & Bag #</th><th>Quantity</th><th>Dates (Coll/Exp)</th><th>Pre-Med & Vitals</th><th>Nurse</th><th>Action</th></tr></thead>
                  <tbody id="h-bl"><tr class="et"><td colspan="7">No records yet.</td></tr></tbody>
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
    </div><!-- /main-content -->

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
        
        <div style="display:grid;grid-template-columns:repeat(4, 1fr);gap:8px;margin-bottom:10px;">
          <!-- Reception -->
          <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;padding:8px;text-align:center;">
            <div style="font-size:0.65rem;font-weight:700;color:#64748b;text-transform:uppercase;">Reception</div>
            <div id="dis-status-reception" style="font-weight:800;font-size:0.78rem;margin-top:2px;color:#f59e0b;">Pending</div>
          </div>
          <!-- Pharmacy -->
          <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;padding:8px;text-align:center;">
            <div style="font-size:0.65rem;font-weight:700;color:#64748b;text-transform:uppercase;">Pharmacy</div>
            <div id="dis-status-pharmacy" style="font-weight:800;font-size:0.78rem;margin-top:2px;color:#f59e0b;">Pending</div>
          </div>
          <!-- Lab -->
          <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;padding:8px;text-align:center;">
            <div style="font-size:0.65rem;font-weight:700;color:#64748b;text-transform:uppercase;">Laboratory</div>
            <div id="dis-status-lab" style="font-weight:800;font-size:0.78rem;margin-top:2px;color:#f59e0b;">Pending</div>
          </div>
          <!-- Radiology -->
          <div style="background:#f0fdf4;border:1.5px solid #1F6B4A;border-radius:8px;padding:8px;text-align:center;">
            <div style="font-size:0.65rem;font-weight:800;color:#1F6B4A;text-transform:uppercase;">Radiology</div>
            <div id="dis-status-radiology" style="font-weight:800;font-size:0.78rem;margin-top:2px;color:#f59e0b;">Pending</div>
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
          <li><strong>Radiology:</strong> Verifies all inpatient scans, X-rays & diagnostic imaging reports.</li>
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

function selectPatientCard(cardEl) {
  if (!cardEl) return;
  try {
    const raw = cardEl.getAttribute('data-patient');
    if (raw) {
      const p = JSON.parse(raw);
      selectPatient(p);
      return;
    }
  } catch (e) {
    console.error("Error parsing data-patient on card click:", e);
  }
}

function selectPatient(p){
  if (!p) return;
  cp = p;
  
  const nopt = document.getElementById('nopt-state');
  if (nopt) nopt.style.display = 'none';
  
  const ini = (((p.first_name || '')[0] || '') + ((p.last_name || '')[0] || '') || 'PT').toUpperCase();
  const av = document.getElementById('pt-av');
  if (av) av.textContent = ini;
  
  const nm = document.getElementById('pt-nm');
  if (nm) nm.textContent = `${p.first_name || ''} ${p.last_name || ''}`.trim();
  
  const chips = document.getElementById('pt-chips');
  if (chips) {
    chips.innerHTML = [
      {ic:'fa-id-card', l:'PID', v:p.patient_id || '-'},
      {ic:'fa-file-invoice', l:'IP#', v:p.admission_id || 'N/A'},
      {ic:'fa-bed', l:'Bed', v:`${p.room_type || 'Ward'}/${p.room_number || 'Bed'}`},
      {ic:'fa-user', l:'Age/Sex', v:`${p.age || '?'}Y / ${p.sex || '?'}`},
      {ic:'fa-tint', l:'Blood', v:p.blood_group || 'N/A'}
    ].map(c => `<span class="ptchip"><i class="fas ${c.ic}"></i><strong>${c.l}:</strong> ${c.v}</span>`).join('');
  }
  
  const ptBanner = document.getElementById('pt-banner');
  if (ptBanner) ptBanner.style.display = 'flex';
  
  const wsLayout = document.getElementById('ws-layout');
  if (wsLayout) wsLayout.style.display = 'block';
  
  const ci = document.querySelector('#f-act select[name="consultant"]');
  if (ci && p.doctor_name) {
    Array.from(ci.options).forEach(o => {
      if (o.value === p.doctor_name) o.selected = true;
    });
  }
  
  const wi = document.getElementById('act-wr');
  if (wi && (p.room_type || p.room_number)) {
    wi.value = `${p.room_type || ''}/${p.room_number || ''}`;
  }
  
  resetGcsToDefault();
  autoFill();
  loadAllRecords();
}

/* ── Auto-fill dates & Nurse Signature ── */
function autoFill(ctx=document){
  const now=new Date();
  const ym=`${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}`;
  const hm=`${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;
  ctx.querySelectorAll('input[type=date]:not([name*="expiry"]):not([name*="exp"])').forEach(i=>{if(!i.value)i.value=ym;});
  ctx.querySelectorAll('input[type=time]').forEach(i=>{if(!i.value)i.value=hm;});
  ctx.querySelectorAll('input[type=datetime-local]:not(.tce-dt)').forEach(i=>{if(!i.value)i.value=ym+'T'+hm;});
  if(NN) {
    ctx.querySelectorAll('input[name*="_nurse"],input[name*="_sign"],input[name="nurse_sign"]').forEach(i=>{
      i.value = NN;
      i.readOnly = true;
      i.setAttribute('readonly', 'readonly');
    });
  }
}

/* ══════════════════════════════════════════════════════════════
   GLASGOW COMA SCALE (GCS) CONTROLLER
   Eye Opening: E1 - E4 (1 to 4)
   Verbal Response: V1 - V5 (1 to 5)
   Motor Response: M1 - M6 (1 to 6)
   Total GCS = Eye + Verbal + Motor (3 to 15)
   Severity:
     13 - 15: Green (Mild impairment / Normal)
     9 - 12:  Yellow / Orange (Moderate impairment)
     3 - 8:   Red (Severe impairment / Critical)
   ══════════════════════════════════════════════════════════════ */
/* ══════════════════════════════════════════════════════════════
   GLASGOW COMA SCALE (GCS) CONTROLLER (Fast & Standard)
   Eye: 1 to 4 | Verbal: 1 to 5 | Motor: 1 to 6 (Total: 3 to 15)
   ══════════════════════════════════════════════════════════════ */
const GCS_CONFIG = {
    eye: {
        'A': { score: 1, desc: 'No Response', code: 'E1' },
        'B': { score: 2, desc: 'To Pain', code: 'E2' },
        'C': { score: 3, desc: 'To Speech', code: 'E3' },
        'D': { score: 4, desc: 'Spontaneously', code: 'E4' }
    },
    verbal: {
        'A': { score: 1, desc: 'No Response', code: 'V1' },
        'B': { score: 2, desc: 'Incomprehensible Sounds', code: 'V2' },
        'C': { score: 3, desc: 'Inappropriate Words', code: 'V3' },
        'D': { score: 4, desc: 'Confused', code: 'V4' },
        'E': { score: 5, desc: 'Oriented', code: 'V5' }
    },
    motor: {
        'A': { score: 1, desc: 'No Motor Response to Pain', code: 'M1' },
        'C': { score: 2, desc: 'Abnormal Extension to Pain', code: 'M2' },
        'D': { score: 3, desc: 'Abnormal Flexion to Pain', code: 'M3' },
        'E': { score: 4, desc: 'Withdrawal from Pain', code: 'M4' },
        'F': { score: 5, desc: 'Localizes Pain', code: 'M5' },
        'G': { score: 6, desc: 'Obeys Commands', code: 'M6' }
    }
};

function onGcsDropdownChange() {
    const eyeVal = document.getElementById('sel_gcs_eye')?.value || 'D';
    const verbalVal = document.getElementById('sel_gcs_verbal')?.value || 'E';
    const motorVal = document.getElementById('sel_gcs_motor')?.value || 'G';

    applyGcsSelection('eye', eyeVal);
    applyGcsSelection('verbal', verbalVal);
    applyGcsSelection('motor', motorVal);

    calculateTotalGcs();

    const f = document.getElementById('f-gcs') || document.getElementById('f-bp');
    if (f) f.classList.add('is-dirty');
}

function applyGcsSelection(cat, opt) {
    const cfg = GCS_CONFIG[cat]?.[opt];
    if (!cfg) return;

    // Set select value if not already
    const sel = document.getElementById(`sel_gcs_${cat}`);
    if (sel && sel.value !== opt) {
        sel.value = opt;
    }

    // Subscore pill in summary
    const pill = document.getElementById(`pill-${cat}-val`);
    const maxScore = (cat === 'eye') ? 4 : (cat === 'verbal') ? 5 : 6;
    if (pill) pill.textContent = `${cfg.score}/${maxScore}`;

    // Hidden inputs for backend save
    const inpText = document.getElementById(`gcs_${cat}_input`);
    if (inpText) inpText.value = `${opt}. ${cfg.desc} (${cfg.code})`;

    const inpScore = document.getElementById(`gcs_${cat}_score_input`);
    if (inpScore) inpScore.value = cfg.score;
}

function calculateTotalGcs() {
    const eyeScore = parseInt(document.getElementById('gcs_eye_score_input')?.value) || 4;
    const verbalScore = parseInt(document.getElementById('gcs_verbal_score_input')?.value) || 5;
    const motorScore = parseInt(document.getElementById('gcs_motor_score_input')?.value) || 6;

    const total = eyeScore + verbalScore + motorScore;

    let severity = 'Mild Impairment / Normal (Alert)';
    let colorClass = 'gcs-mild';
    let shortStatus = 'Alert';
    let iconClass = 'fa-check-circle';

    if (total >= 13) {
        shortStatus = (total === 15) ? 'Alert' : 'Mild Impairment';
        severity = `Mild Impairment / Normal (${shortStatus})`;
        colorClass = 'gcs-mild';
        iconClass = 'fa-check-circle';
    } else if (total >= 9) {
        shortStatus = 'Moderate Impairment';
        severity = 'Moderate Impairment (Drowsy / Stupor)';
        colorClass = 'gcs-moderate';
        iconClass = 'fa-exclamation-circle';
    } else {
        shortStatus = 'Severe Impairment';
        severity = 'Severe Impairment (Critical Coma / Unresponsive)';
        colorClass = 'gcs-severe';
        iconClass = 'fa-exclamation-triangle';
    }

    // Dial score
    const dial = document.getElementById('gcs-adv-score-dial');
    if (dial) dial.className = `gcs-dial-pill ${colorClass}`;
    const scoreNum = document.getElementById('gcs-score-num');
    if (scoreNum) scoreNum.textContent = total;

    // Severity badge
    const sevLabel = document.getElementById('gcs-severity-label');
    if (sevLabel) {
        sevLabel.className = `gcs-status-badge ${colorClass}`;
        sevLabel.innerHTML = `<i class="fas ${iconClass}"></i> ${severity}`;
    }

    // Hidden form inputs
    const inpScore = document.getElementById('gcs_score_input');
    if (inpScore) inpScore.value = total;

    const inpTotal = document.getElementById('gcs_total_input');
    if (inpTotal) inpTotal.value = `${total}/15 (E${eyeScore} V${verbalScore} M${motorScore})`;

    const inpConscious = document.getElementById('gcs_consciousness_input');
    if (inpConscious) inpConscious.value = `${total}/15 (${shortStatus})`;
}

function resetGcsToDefault() {
    applyGcsSelection('eye', 'D');
    applyGcsSelection('verbal', 'E');
    applyGcsSelection('motor', 'G');
    calculateTotalGcs();
}

function setGcsValuesFromRecord(r) {
    if (!r) return;

    let eOpt = null, vOpt = null, mOpt = null;

    if (r.gcs_eye && typeof r.gcs_eye === 'string') {
        const m = r.gcs_eye.match(/^([A-D])\./);
        if (m) eOpt = m[1];
    }
    if (r.gcs_verbal && typeof r.gcs_verbal === 'string') {
        const m = r.gcs_verbal.match(/^([A-E])\./);
        if (m) vOpt = m[1];
    }
    if (r.gcs_motor && typeof r.gcs_motor === 'string') {
        const m = r.gcs_motor.match(/^([A-G])\./);
        if (m) mOpt = m[1];
    }

    if (!eOpt && r.gcs_eye_score) {
        const s = parseInt(r.gcs_eye_score);
        for (const [k, v] of Object.entries(GCS_CONFIG.eye)) {
            if (v.score === s) { eOpt = k; break; }
        }
    }
    if (!vOpt && r.gcs_verbal_score) {
        const s = parseInt(r.gcs_verbal_score);
        for (const [k, v] of Object.entries(GCS_CONFIG.verbal)) {
            if (v.score === s) { vOpt = k; break; }
        }
    }
    if (!mOpt && r.gcs_motor_score) {
        const s = parseInt(r.gcs_motor_score);
        for (const [k, v] of Object.entries(GCS_CONFIG.motor)) {
            if (v.score === s) { mOpt = k; break; }
        }
    }

    if (!eOpt || !vOpt || !mOpt) {
        const raw = (r.consciousness_level || r.gcs || '').toLowerCase();
        if (raw.includes('unresponsive') || raw.includes('< 8') || raw.includes('severe')) {
            if (!eOpt) eOpt = 'A';
            if (!vOpt) vOpt = 'A';
            if (!mOpt) mOpt = 'A';
        } else if (raw.includes('pain') || raw.includes('8-11')) {
            if (!eOpt) eOpt = 'B';
            if (!vOpt) vOpt = 'B';
            if (!mOpt) mOpt = 'E';
        } else if (raw.includes('verbal') || raw.includes('voice') || raw.includes('12-14')) {
            if (!eOpt) eOpt = 'C';
            if (!vOpt) vOpt = 'C';
            if (!mOpt) mOpt = 'F';
        } else if (raw.includes('confused') || raw.includes('drowsy')) {
            if (!eOpt) eOpt = 'D';
            if (!vOpt) vOpt = 'D';
            if (!mOpt) mOpt = 'G';
        } else {
            if (!eOpt) eOpt = 'D';
            if (!vOpt) vOpt = 'E';
            if (!mOpt) mOpt = 'G';
        }
    }

    if (eOpt) applyGcsSelection('eye', eOpt);
    if (vOpt) applyGcsSelection('verbal', vOpt);
    if (mOpt) applyGcsSelection('motor', mOpt);

    calculateTotalGcs();
}

function clrF(id){
  const c=document.getElementById(id); if(!c)return;
  c.querySelectorAll('input:not([type=hidden]):not([readonly]),select,textarea').forEach(e=>e.value='');
  c.querySelectorAll('.chk-ongoing').forEach(cb=>{ cb.checked = false; toggleOngoingTreatment(cb); });
  if (id === 'f-bp' || id === 'f-gcs') {
    resetGcsToDefault();
  }
  if (id === 'f-ve') {
    onVentTypeChange('');
  }
  if (id === 'f-bl') {
    onBloodTypeChange('');
  }
  autoFill(c);
}

/* ── Smart Multi-Day & Single-Day Duration Calculation ── */
function calcDur(el){
  const c = el.closest('.card-body');
  if (!c) return;
  const sDt = c.querySelector('.tcs-dt')?.value;
  const eDt = c.querySelector('.tce-dt')?.value;
  const s = c.querySelector('.tcs')?.value;
  const e = c.querySelector('.tce')?.value;
  const d = c.querySelector('.tcd');
  const chkOngoing = c.querySelector('.chk-ongoing');
  if (!d) return;

  // Sync hidden legacy date field with start date if present
  if (sDt) {
    const hDate = c.querySelector('input[type=hidden][name*="_date"]');
    if (hDate) hDate.value = sDt.split('T')[0];
  }

  // 1. If marked Continuous / Ongoing
  if (chkOngoing && chkOngoing.checked) {
    if (sDt) {
      const st = new Date(sDt);
      const now = new Date();
      if (!isNaN(st.getTime()) && now >= st) {
        const totalMinutes = Math.floor((now - st) / 60000);
        const days = Math.floor(totalMinutes / (24 * 60));
        const hours = Math.floor((totalMinutes % (24 * 60)) / 60);
        const mins = totalMinutes % 60;
        let durStr = 'Ongoing (';
        if (days > 0) durStr += `${days}d `;
        durStr += `${hours}h ${mins}m active)`;
        d.value = durStr;
        return;
      }
    }
    d.value = 'Ongoing (Active)';
    return;
  }

  // 2. Multi-Day DateTime Calculation (e.g. Oxygen Therapy & Ventilator Support)
  if (sDt && eDt) {
    const st = new Date(sDt);
    const en = new Date(eDt);
    if (isNaN(st.getTime()) || isNaN(en.getTime())) return;
    if (en < st) {
      d.value = 'Invalid (End before Start)';
      return;
    }
    const totalMinutes = Math.floor((en - st) / 60000);
    const totalHours = Math.floor(totalMinutes / 60);
    const days = Math.floor(totalMinutes / (24 * 60));
    const hours = Math.floor((totalMinutes % (24 * 60)) / 60);
    const mins = totalMinutes % 60;

    if (days >= 1) {
      d.value = `${days} Day${days > 1 ? 's' : ''} ${hours}h ${mins}m (${totalHours} hrs total)`;
    } else {
      d.value = `${hours}h ${mins}m (${totalHours} hrs total)`;
    }
    return;
  }

  // 3. Fallback for Single-Day Time Inputs (e.g. Dialysis)
  if (s && e) {
    let st = new Date('1970-01-01T' + s + ':00');
    let en = new Date('1970-01-01T' + e + ':00');
    if (en < st) en.setDate(en.getDate() + 1);
    const ms = en - st;
    d.value = Math.floor(ms / 3600000) + 'h ' + Math.round((ms % 3600000) / 60000) + 'm';
  }
}

function toggleOngoingTreatment(cb){
  const c = cb.closest('.card-body');
  if (!c) return;
  const endInput = c.querySelector('.tce-dt, .tce');
  if (cb.checked) {
    if (endInput) {
      endInput.value = '';
      endInput.disabled = true;
      endInput.style.opacity = '0.5';
      endInput.style.cursor = 'not-allowed';
    }
  } else {
    if (endInput) {
      endInput.disabled = false;
      endInput.style.opacity = '1';
      endInput.style.cursor = 'default';
    }
  }
  calcDur(cb);
}

/* ── Ventilator Support Dynamic Modes (Invasive & NIV) ── */
const VENT_MODES_CONFIG = {
  'Invasive Ventilation': [
    { value: 'Volume-Controlled Ventilation (VCV)', label: 'Volume-Controlled Ventilation (VCV)', group: 'Invasive Ventilation' },
    { value: 'Pressure-Controlled Ventilation (PCV)', label: 'Pressure-Controlled Ventilation (PCV)', group: 'Invasive Ventilation' },
    { value: 'Synchronized Intermittent Mandatory Ventilation (SIMV)', label: 'Synchronized Intermittent Mandatory Ventilation (SIMV)', group: 'Invasive Ventilation' },
    { value: 'Assist-Control (A/C)', label: 'Assist-Control (A/C)', group: 'Invasive Ventilation' },
    { value: 'Pressure Support Ventilation (PSV)', label: 'Pressure Support Ventilation (PSV)', group: 'Invasive Ventilation' },
    { value: 'Positive End-Expiratory Pressure (PEEP)', label: 'Positive End-Expiratory Pressure (PEEP)', group: 'Invasive Ventilation' }
  ],
  'Non-Invasive Ventilation (NIV)': [
    { value: 'BiPAP (Bilevel Positive Airway Pressure)', label: 'BiPAP (Bilevel Positive Airway Pressure)', group: 'BiPAP' },
    { value: 'BiPAP - IPAP (Inspiratory Positive Airway Pressure)', label: 'BiPAP - IPAP (Inspiratory Positive Airway Pressure)', group: 'BiPAP' },
    { value: 'BiPAP - EPAP (Expiratory Positive Airway Pressure)', label: 'BiPAP - EPAP (Expiratory Positive Airway Pressure)', group: 'BiPAP' },
    { value: 'CPAP (Continuous Positive Airway Pressure)', label: 'CPAP (Continuous Positive Airway Pressure)', group: 'CPAP' },
    { value: 'CPAP - CPAP Pressure', label: 'CPAP - CPAP Pressure', group: 'CPAP' },
    { value: 'VPAP (Variable Positive Airway Pressure)', label: 'VPAP (Variable Positive Airway Pressure)', group: 'VPAP' },
    { value: 'VPAP - IPAP / EPAP (Device-Specific Pressure Settings)', label: 'VPAP - IPAP / EPAP (Device-Specific Pressure Settings)', group: 'VPAP' }
  ]
};

function onVentTypeChange(type, selectedMode = '') {
  const modeSelect = document.getElementById('vent_mode_select');
  if (!modeSelect) return;

  modeSelect.innerHTML = '';

  if (!type || !VENT_MODES_CONFIG[type]) {
    const opt = document.createElement('option');
    opt.value = '';
    opt.textContent = '-- Select Ventilation Type First --';
    modeSelect.appendChild(opt);
    return;
  }

  const defaultOpt = document.createElement('option');
  defaultOpt.value = '';
  defaultOpt.textContent = `-- Select Mode (${type}) --`;
  modeSelect.appendChild(defaultOpt);

  const list = VENT_MODES_CONFIG[type];
  const groups = {};
  list.forEach(item => {
    const grp = item.group || type;
    if (!groups[grp]) groups[grp] = [];
    groups[grp].push(item);
  });

  const groupKeys = Object.keys(groups);
  if (groupKeys.length > 1) {
    groupKeys.forEach(grp => {
      const optGroup = document.createElement('optgroup');
      optGroup.label = grp;
      groups[grp].forEach(item => {
        const opt = document.createElement('option');
        opt.value = item.value;
        opt.textContent = item.label;
        if (selectedMode && (item.value === selectedMode || item.label === selectedMode)) {
          opt.selected = true;
        }
        optGroup.appendChild(opt);
      });
      modeSelect.appendChild(optGroup);
    });
  } else {
    list.forEach(item => {
      const opt = document.createElement('option');
      opt.value = item.value;
      opt.textContent = item.label;
      if (selectedMode && (item.value === selectedMode || item.label === selectedMode)) {
        opt.selected = true;
      }
      modeSelect.appendChild(opt);
    });
  }

  // If a legacy or custom selectedMode was provided and is not in list, append it
  if (selectedMode && !Array.from(modeSelect.options).some(o => o.value === selectedMode)) {
    const customOpt = document.createElement('option');
    customOpt.value = selectedMode;
    customOpt.textContent = selectedMode;
    customOpt.selected = true;
    modeSelect.appendChild(customOpt);
  }
}

/* ── Blood Transfusion Dynamic Product Types & Subtypes ── */
const BLOOD_SUBTYPES_CONFIG = {
  'Platelets': [
    { value: 'RDP (Random Donor Platelets)', label: 'RDP (Random Donor Platelets)' },
    { value: 'SDP (Single Donor Platelets)', label: 'SDP (Single Donor Platelets)' },
    { value: 'Other', label: 'Other' }
  ],
  'Plasma Products': [
    { value: 'FFP (Fresh Frozen Plasma)', label: 'FFP (Fresh Frozen Plasma)' },
    { value: 'Cryoprecipitate (CRYO)', label: 'Cryoprecipitate (CRYO)' },
    { value: 'Other', label: 'Other' }
  ]
};

function onBloodTypeChange(type, selectedSubtype = '', customDetails = '') {
  const fmgSubtype = document.getElementById('fmg_blood_subtype');
  const subtypeSelect = document.getElementById('bt_subtype_select');
  const fmgDetails = document.getElementById('fmg_blood_details');
  const detailsInput = document.getElementById('bt_type_details_input');
  const lblSubtype = document.getElementById('lbl_blood_subtype');
  const lblDetails = document.getElementById('lbl_blood_details');

  if (!fmgSubtype || !subtypeSelect || !fmgDetails || !detailsInput) return;

  // Reset subtype dropdown
  subtypeSelect.innerHTML = '<option value="">-- Select Sub-Type --</option>';

  if (type === 'Platelets') {
    fmgSubtype.style.display = 'flex';
    if (lblSubtype) lblSubtype.innerHTML = '<i class="fas fa-layer-group"></i> Platelet Sub-Type';
    BLOOD_SUBTYPES_CONFIG['Platelets'].forEach(item => {
      const opt = document.createElement('option');
      opt.value = item.value;
      opt.textContent = item.label;
      if (selectedSubtype && (item.value === selectedSubtype || item.label === selectedSubtype)) {
        opt.selected = true;
      }
      subtypeSelect.appendChild(opt);
    });
    onBloodSubtypeChange(selectedSubtype || subtypeSelect.value, customDetails);
  } else if (type === 'Plasma Products') {
    fmgSubtype.style.display = 'flex';
    if (lblSubtype) lblSubtype.innerHTML = '<i class="fas fa-layer-group"></i> Plasma Product Sub-Type';
    BLOOD_SUBTYPES_CONFIG['Plasma Products'].forEach(item => {
      const opt = document.createElement('option');
      opt.value = item.value;
      opt.textContent = item.label;
      if (selectedSubtype && (item.value === selectedSubtype || item.label === selectedSubtype)) {
        opt.selected = true;
      }
      subtypeSelect.appendChild(opt);
    });
    onBloodSubtypeChange(selectedSubtype || subtypeSelect.value, customDetails);
  } else if (type === 'PRBC (Packed Red Blood Cells)' || type === 'Whole Blood') {
    fmgSubtype.style.display = 'none';
    subtypeSelect.value = '';
    // Show additional details for PRBC and Whole Blood
    fmgDetails.style.display = 'flex';
    const prodName = type.includes('PRBC') ? 'PRBC' : 'Whole Blood';
    if (lblDetails) lblDetails.innerHTML = `<i class="fas fa-info-circle"></i> ${prodName} Additional Details / Specs`;
    detailsInput.placeholder = type.includes('PRBC') 
      ? 'e.g. Leukoreduced, Irradiated, Saline-washed...' 
      : 'e.g. Fresh whole blood, specific donor notes...';
    if (customDetails !== undefined) detailsInput.value = customDetails;
  } else if (type === 'Other') {
    fmgSubtype.style.display = 'none';
    subtypeSelect.value = '';
    fmgDetails.style.display = 'flex';
    if (lblDetails) lblDetails.innerHTML = '<i class="fas fa-edit"></i> Specify Blood Product Details <span style="color:#dc2626;">*</span>';
    detailsInput.placeholder = 'Specify blood product type (e.g. Granulocytes, Stem Cells)...';
    if (customDetails !== undefined) detailsInput.value = customDetails;
  } else {
    // None selected
    fmgSubtype.style.display = 'none';
    subtypeSelect.value = '';
    fmgDetails.style.display = 'none';
    if (detailsInput) detailsInput.value = '';
  }
}

function onBloodSubtypeChange(subtype, customDetails = '') {
  const fmgDetails = document.getElementById('fmg_blood_details');
  const detailsInput = document.getElementById('bt_type_details_input');
  const lblDetails = document.getElementById('lbl_blood_details');
  const mainType = document.getElementById('bt_type_select')?.value || '';

  if (!fmgDetails || !detailsInput) return;

  if (subtype === 'Other' || subtype.includes('Other')) {
    fmgDetails.style.display = 'flex';
    if (lblDetails) lblDetails.innerHTML = `<i class="fas fa-edit"></i> Specify Other ${mainType} Details <span style="color:#dc2626;">*</span>`;
    detailsInput.placeholder = `Enter specific details for this ${mainType.toLowerCase()}...`;
    if (customDetails !== undefined && customDetails !== '') detailsInput.value = customDetails;
    detailsInput.focus();
  } else if (customDetails) {
    fmgDetails.style.display = 'flex';
    if (lblDetails) lblDetails.innerHTML = '<i class="fas fa-edit"></i> Additional Notes / Details';
    detailsInput.value = customDetails;
  } else {
    if (!detailsInput.value) {
      fmgDetails.style.display = 'none';
    }
  }
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
    } else if (inp.type === 'checkbox') {
      if (inp.checked) fd.append(k, inp.value || '1');
      else fd.append(k, '0');
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
        else if (inp.type === 'checkbox') {
          if (inp.checked) fd.append(k, inp.value || '1');
          else fd.append(k, '0');
        } else fd.append(k,inp.value);
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

  ['lab', 'radiology', 'other'].forEach(type => {
    if (diagCarts[type] && diagCarts[type].length > 0) {
      saveTasks.push(async () => {
        try {
          const r = await fetch('api/save_tests.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              patient_id: cp.patient_id,
              admission_id: cp.admission_id || '',
              order_type: type,
              cart: diagCarts[type]
            })
          });
          const res = await r.json();
          if (res.success) {
            diagCarts[type] = [];
            renderDiagCart(type);
          }
          const secName = type === 'lab' ? 'Diagnostic LAB Order (lab_tests)' : (type === 'radiology' ? 'Diagnostic RADIOLOGY Order (radiology_tests)' : 'Diagnostic OTHER Order (other_tests)');
          results.push({ section: secName, success: res.success, err: res.message });
        } catch(e) {
          results.push({ section: type, success: false, err: e.message });
        }
      });
    }
  });
  if (phCart.length > 0) {
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
    if (typeof renderTransferHistory === 'function') {
      renderTransferHistory(d.ward_transfer || []);
    }

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

    // 5a. BP Chart (Blood Pressure only, no GCS)
    rH('h-bp', d.bp_chart || d.vitals || [], r => `
        <td>${r.bp_date || r.date || r.created_date || ''} ${r.bp_time || r.time || ''}</td>
        <td><strong>${r.bp_value || ((r.bp_systolic || '') + '/' + (r.bp_diastolic || ''))}</strong></td>
        <td>${r.bp_pulse || r.pulse || ''} bpm</td>
        <td>${r.bp_temp || r.temp || ''}</td>
        <td>${r.bp_spo2 || r.spo2 ? (r.bp_spo2 || r.spo2) + '%' : '-'}</td>
        <td><span class="badge" style="font-weight:800;">${r.pain_score !== undefined && r.pain_score !== null && r.pain_score !== '' ? r.pain_score + '/10' : (r.bp_pain || r.bp_pain_score || '-')}</span></td>
        <td>${r.bp_nurse || r.nurse_sign || r.created_by_name || ''}</td>
        <td style="text-align:center;"><button type="button" class="btn-edit-log" onclick='editLog("f-bp", ${JSON.stringify(r)})' title="Edit BP record"><i class="fas fa-edit"></i> Edit</button></td>
    `, 8);

    // 5b. Vitals Chart – GCS Assessments
    rH('h-gcs', d.gcs_chart || d.bp_chart || d.vitals || [], r => {
      // Only render rows that have GCS data
      if (!r.gcs_score && !r.gcs && !r.consciousness_level) return null;

      let gcsScore = parseInt(r.gcs_score) || 0;
      let gcsText  = r.gcs || r.consciousness_level || '-';

      if (!gcsScore && typeof gcsText === 'string') {
        const m = gcsText.match(/(\d+)\s*\/\s*15/);
        if (m) gcsScore = parseInt(m[1]);
        else if (gcsText.toLowerCase().includes('alert')) gcsScore = 15;
        else if (gcsText.toLowerCase().includes('confused')) gcsScore = 14;
        else if (gcsText.toLowerCase().includes('drowsy')) gcsScore = 13;
        else if (gcsText.toLowerCase().includes('pain')) gcsScore = 8;
        else if (gcsText.toLowerCase().includes('unresponsive')) gcsScore = 3;
      }

      let gcsBg = '#f0fdf4', gcsFg = '#15803d', gcsBdr = '#86efac';
      if (gcsScore >= 13)      { gcsBg = '#f0fdf4'; gcsFg = '#15803d'; gcsBdr = '#86efac'; }
      else if (gcsScore >= 9)  { gcsBg = '#fffbeb'; gcsFg = '#b45309'; gcsBdr = '#fde68a'; }
      else if (gcsScore > 0)   { gcsBg = '#fef2f2'; gcsFg = '#dc2626'; gcsBdr = '#fca5a5'; }

      const eyeVal    = r.gcs_eye    || '-';
      const verbalVal = r.gcs_verbal || '-';
      const motorVal  = r.gcs_motor  || '-';
      const conscious = r.consciousness_level || gcsText;
      const dateStr   = r.gcs_date   || r.bp_date || r.date || r.created_date || '';
      const timeStr   = r.gcs_time   || r.bp_time || r.time || '';
      const nurseStr  = r.gcs_nurse  || r.bp_nurse || r.nurse_sign || r.created_by_name || '';

      return `
        <td>${dateStr} ${timeStr}</td>
        <td><span class="badge" style="background:${gcsBg};color:${gcsFg};border:1.5px solid ${gcsBdr};font-weight:900;font-size:0.9rem;" title="GCS Score">${gcsScore}/15</span></td>
        <td style="font-size:0.77rem;">${eyeVal}</td>
        <td style="font-size:0.77rem;">${verbalVal}</td>
        <td style="font-size:0.77rem;">${motorVal}</td>
        <td><span class="badge" style="background:${gcsBg};color:${gcsFg};font-weight:700;font-size:0.75rem;">${conscious}</span></td>
        <td>${nurseStr}</td>
        <td style="text-align:center;"><button type="button" class="btn-edit-log" onclick='editLog("f-gcs", ${JSON.stringify(r)})' title="Edit GCS"><i class="fas fa-edit"></i> Edit</button></td>
      `;
    }, 8);

    // 6. Dialysis Record
    rH('h-di', d.dialysis_chart || [], r => {
      const hasEnd = !!(r.dia_end_dt || r.dia_end);
      const isOngoing = !hasEnd && (r.is_ongoing == '1' || r.is_ongoing === true || (r.dia_dur && r.dia_dur.toLowerCase().includes('ongoing')));
      const durBadge = isOngoing 
        ? `<span class="badge" style="background:#dcfce7; color:#15803d; font-weight:800;"><i class="fas fa-sync-alt fa-spin"></i> ${r.dia_dur || 'Ongoing Dialysis'}</span>`
        : `<strong style="color:var(--gm-primary);">${r.dia_dur || r.duration || '-'}</strong>`;
      
      const startTxt = r.dia_start_dt ? r.dia_start_dt.replace('T', ' ') : ((r.dia_date || r.date || '') + (r.dia_start ? ' ' + r.dia_start : ''));
      const endTxt = r.dia_end_dt ? r.dia_end_dt.replace('T', ' ') : (r.dia_end ? r.dia_end : (isOngoing ? 'Still Active' : ''));
      const timeRange = (startTxt && endTxt) ? `${startTxt} → ${endTxt}` : (startTxt || '-');

      return `
        <td><strong>${r.dia_date || (r.dia_start_dt ? r.dia_start_dt.split('T')[0] : '') || r.date || ''}</strong></td>
        <td><strong style="color:var(--gm-primary)">${r.dia_type || r.type || '-'}</strong></td>
        <td>
          ${durBadge}
          <br><small style="color:var(--gm-text-muted); font-size:0.75rem;"><i class="far fa-clock"></i> ${timeRange}</small>
        </td>
        <td>${r.dia_nurse || r.created_by_name || '-'}</td>
        <td style="text-align:center;"><button type="button" class="btn-edit-log" onclick='editLog("f-di", ${JSON.stringify(r)})' title="Edit dialysis"><i class="fas fa-edit"></i> Edit</button></td>
      `;
    }, 5);

    // 7. Oxygen Therapy
    rH('h-ox', d.oxygen_chart || [], r => {
      const hasEnd = !!(r.oxy_end_dt || r.oxy_end);
      const isOngoing = !hasEnd && (r.is_ongoing == '1' || r.is_ongoing === true || (r.oxy_dur && r.oxy_dur.toLowerCase().includes('ongoing')));
      const durBadge = isOngoing 
        ? `<span class="badge" style="background:#dcfce7; color:#15803d; font-weight:800;"><i class="fas fa-sync-alt fa-spin"></i> ${r.oxy_dur || 'Ongoing'}</span>`
        : `<strong style="color:var(--gm-primary);">${r.oxy_dur || r.duration || '-'}</strong>`;
      
      const startTxt = r.oxy_start_dt ? r.oxy_start_dt.replace('T', ' ') : ((r.oxy_date || r.date || '') + (r.oxy_start ? ' ' + r.oxy_start : ''));
      const endTxt = r.oxy_end_dt ? r.oxy_end_dt.replace('T', ' ') : (r.oxy_end ? r.oxy_end : (isOngoing ? 'Still Active' : ''));
      const timeRange = (startTxt && endTxt) ? `${startTxt} → ${endTxt}` : (startTxt || '-');

      return `
        <td><strong>${r.oxy_date || (r.oxy_start_dt ? r.oxy_start_dt.split('T')[0] : '') || r.date || ''}</strong></td>
        <td><strong style="color:var(--gm-primary)">${r.oxy_flow || r.flow || '-'}</strong></td>
        <td>
          ${durBadge}
          <br><small style="color:var(--gm-text-muted); font-size:0.75rem;"><i class="far fa-clock"></i> ${timeRange}</small>
        </td>
        <td>${r.oxy_nurse || r.created_by_name || '-'}</td>
        <td style="text-align:center;"><button type="button" class="btn-edit-log" onclick='editLog("f-ox", ${JSON.stringify(r)})' title="Edit oxygen"><i class="fas fa-edit"></i> Edit</button></td>
      `;
    }, 5);

    // 8. Ventilator Support
    rH('h-ve', d.ventilation_chart || [], r => {
      const hasEnd = !!(r.vent_end_dt || r.vent_end);
      const isOngoing = !hasEnd && (r.is_ongoing == '1' || r.is_ongoing === true || (r.vent_dur && r.vent_dur.toLowerCase().includes('ongoing')));
      const durBadge = isOngoing 
        ? `<span class="badge" style="background:#dcfce7; color:#15803d; font-weight:800;"><i class="fas fa-procedures"></i> ${r.vent_dur || 'Active on Vent'}</span>`
        : `<strong style="color:var(--gm-primary);">${r.vent_dur || r.duration || '-'}</strong>`;
      
      const startTxt = r.vent_start_dt ? r.vent_start_dt.replace('T', ' ') : ((r.vent_date || r.date || '') + (r.vent_start ? ' ' + r.vent_start : ''));
      const endTxt = r.vent_end_dt ? r.vent_end_dt.replace('T', ' ') : (r.vent_end ? r.vent_end : (isOngoing ? 'Still Active' : ''));
      const timeRange = (startTxt && endTxt) ? `${startTxt} → ${endTxt}` : (startTxt || '-');

      const typeBadge = r.vent_type ? `<div style="font-size:0.7rem; color:var(--gm-text-muted); font-weight:800; text-transform:uppercase; letter-spacing:0.3px; margin-bottom:2px;">${r.vent_type}</div>` : '';
      return `
        <td><strong>${r.vent_date || (r.vent_start_dt ? r.vent_start_dt.split('T')[0] : '') || r.date || ''}</strong></td>
        <td>
          ${typeBadge}
          <span class="badge" style="font-weight:700;">${r.vent_mode || r.mode || '-'}</span>
        </td>
        <td>
          ${durBadge}
          <br><small style="color:var(--gm-text-muted); font-size:0.75rem;"><i class="far fa-clock"></i> ${timeRange}</small>
        </td>
        <td>${r.vent_nurse || r.created_by_name || '-'}</td>
        <td style="text-align:center;"><button type="button" class="btn-edit-log" onclick='editLog("f-ve", ${JSON.stringify(r)})' title="Edit ventilator"><i class="fas fa-edit"></i> Edit</button></td>
      `;
    }, 5);

    // 9. Blood Transfusion
    rH('h-bl', d.blood_transfusion_chart || [], r => {
      const collDate = r.collection_date || r.date_of_collection || '';
      const expDate = r.expiry_date || r.date_of_expiry || '';
      const datesHtml = (collDate || expDate) 
        ? `<div style="font-size:0.72rem; color:var(--gm-text-muted); line-height:1.35;"><i class="fas fa-calendar-alt"></i> Coll: ${collDate || '-'}<br><i class="fas fa-calendar-times"></i> Exp: ${expDate || '-'}</div>` 
        : '-';

      const vitalsHtml = `
        <div style="font-size:0.75rem; line-height:1.4;">
          ${r.pre_medication ? `<div><strong style="color:var(--gm-primary);">Pre-Med:</strong> ${r.pre_medication}</div>` : ''}
          ${r.vitals_before ? `<div><strong style="color:#0369a1;">Before:</strong> ${r.vitals_before}</div>` : ''}
          ${(r.vitals_during || r.vitals) ? `<div><strong style="color:#1F6B4A;">During:</strong> ${r.vitals_during || r.vitals}</div>` : ''}
          ${r.vitals_after ? `<div><strong style="color:#b45309;">After:</strong> ${r.vitals_after}</div>` : ''}
          ${(!r.pre_medication && !r.vitals_before && !r.vitals_during && !r.vitals && !r.vitals_after) ? '-' : ''}
        </div>
      `;

      // Format Blood Type & Subtype badge
      let bTypeDesc = '';
      if (r.blood_type) {
        bTypeDesc = `<div style="margin-top:3px;"><span class="badge" style="background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; font-size:0.71rem; font-weight:800;">${r.blood_type}</span></div>`;
        if (r.blood_subtype) {
          bTypeDesc += `<div style="font-size:0.72rem; color:var(--gm-primary); font-weight:700; margin-top:1px;">&bull; ${r.blood_subtype}</div>`;
        }
        if (r.blood_type_details) {
          bTypeDesc += `<small style="font-size:0.7rem; color:#64748b; display:block;">(${r.blood_type_details})</small>`;
        }
      }

      return `
        <td><strong>${r.trans_date || r.date || ''}</strong></td>
        <td>
          <strong style="color:var(--gm-primary); font-size:0.88rem;">${r.blood_group || ''}</strong> #${r.bag_number || r.bag_no || '-'}
          ${bTypeDesc}
        </td>
        <td>${r.quantity ? r.quantity + ' ml' : '-'}</td>
        <td>${datesHtml}</td>
        <td>${vitalsHtml}</td>
        <td>${r.nurse_sign || r.nurse || r.created_by_name || '-'}</td>
        <td style="text-align:center;"><button type="button" class="btn-edit-log" onclick='editLog("f-bl", ${JSON.stringify(r)})' title="Edit transfusion"><i class="fas fa-edit"></i> Edit</button></td>
      `;
    }, 7);

    // 10. Nursing Notes
    rH('h-nn', d.nurses_record || [], r => `
      <td>${r.nurse_date || r.date || ''} ${r.nurse_time || r.time || ''}</td>
      <td>${r.nurse_part || r.particulars || ''}</td>
      <td>${r.nurse_sign || r.created_by_name || ''}</td>
      <td style="text-align:center;"><button type="button" class="btn-edit-log" onclick='editLog("f-nn", ${JSON.stringify(r)})' title="Edit note"><i class="fas fa-edit"></i> Edit</button></td>
    `, 4);
    
    // ── Diagnostic Orders History (3 Separate Views) ──
    const labList = Array.isArray(d.lab_tests) ? d.lab_tests : [];
    const radList = Array.isArray(d.radiology_tests) ? d.radiology_tests : [];
    const othList = Array.isArray(d.other_tests) ? d.other_tests : [];

    const bLab = document.getElementById('badge-hist-lab'); if (bLab) bLab.textContent = labList.length;
    const bRad = document.getElementById('badge-hist-rad'); if (bRad) bRad.textContent = radList.length;
    const bOth = document.getElementById('badge-hist-oth'); if (bOth) bOth.textContent = othList.length;

    // View 1: LAB History (Column 7: lab_tests)
    const tbLab = document.getElementById('h-ts-lab');
    if (tbLab) {
      tbLab.innerHTML = labList.length ? [...labList].reverse().map(t => {
        const i = t.data || t;
        const dt = (t.created_date || i.created_date || t.date || i.date) 
          ? `${t.created_date || i.created_date || t.date || i.date || ''} ${t.created_time || i.created_time || ''}`.trim() 
          : (t.created_at || i.created_at || 'N/A');
        const by = t.created_by_name || i.created_by_name || (t.created_by ? (isNaN(t.created_by) ? t.created_by : 'Nurse #' + t.created_by) : 'Staff Nurse');
        const name = i.name || i.test_name || t.name || t.test_name || 'Lab Test';
        const code = i.id || i.test_id || t.id || t.test_id || 'LAB';
        const cat = i.category || t.category || 'Lab';
        const qty = i.qty || i.quantity || t.qty || t.quantity || 1;
        return `<tr>
          <td><strong>${dt}</strong></td>
          <td><strong style="color:#1f6b4a; font-size:0.88rem;">${name}</strong></td>
          <td><span class="badge" style="background:#f3efe6; color:#1f6b4a; border:1.5px solid #1f6b4a; font-weight:800; padding:2px 8px; border-radius:4px;">${code}</span></td>
          <td><span class="badge" style="background:#1f6b4a; color:#f3efe6; font-weight:750; padding:2px 8px; border-radius:4px;">${cat}</span></td>
          <td><strong style="color:#1f6b4a; font-weight:800;">${qty}</strong></td>
          <td><span style="color:#1f6b4a; font-weight:700;">${by}</span></td>
        </tr>`;
      }).join('') : '<tr class="et"><td colspan="6">No Laboratory tests ordered for this patient yet.</td></tr>';
    }

    // View 2: RADIOLOGY History (Column 8: radiology_tests)
    const tbRad = document.getElementById('h-ts-rad');
    if (tbRad) {
      tbRad.innerHTML = radList.length ? [...radList].reverse().map(t => {
        const i = t.data || t;
        const dt = (t.created_date || i.created_date || t.date || i.date) 
          ? `${t.created_date || i.created_date || t.date || i.date || ''} ${t.created_time || i.created_time || ''}`.trim() 
          : (t.created_at || i.created_at || 'N/A');
        const by = t.created_by_name || i.created_by_name || (t.created_by ? (isNaN(t.created_by) ? t.created_by : 'Nurse #' + t.created_by) : 'Staff Nurse');
        const name = i.name || i.test_name || t.name || t.test_name || 'Radiology Scan';
        const code = i.id || i.test_id || t.id || t.test_id || 'RDS';
        const cat = i.category || i.modality || t.category || 'Radiology';
        const qty = i.qty || i.quantity || t.qty || t.quantity || 1;
        return `<tr>
          <td><strong>${dt}</strong></td>
          <td><strong style="color:#1f6b4a; font-size:0.88rem;">${name}</strong></td>
          <td><span class="badge" style="background:#f3efe6; color:#1f6b4a; border:1.5px solid #1f6b4a; font-weight:800; padding:2px 8px; border-radius:4px;">${code}</span></td>
          <td><span class="badge" style="background:#1f6b4a; color:#f3efe6; font-weight:750; padding:2px 8px; border-radius:4px;">${cat}</span></td>
          <td><strong style="color:#1f6b4a; font-weight:800;">${qty}</strong></td>
          <td><span style="color:#1f6b4a; font-weight:700;">${by}</span></td>
        </tr>`;
      }).join('') : '<tr class="et"><td colspan="6">No Radiology scans ordered for this patient yet.</td></tr>';
    }

    // View 3: OTHER History (Column 9: other_tests)
    const tbOth = document.getElementById('h-ts-oth');
    if (tbOth) {
      tbOth.innerHTML = othList.length ? [...othList].reverse().map(t => {
        const i = t.data || t;
        const dt = (t.created_date || i.created_date || t.date || i.date) 
          ? `${t.created_date || i.created_date || t.date || i.date || ''} ${t.created_time || i.created_time || ''}`.trim() 
          : (t.created_at || i.created_at || 'N/A');
        const by = t.created_by_name || i.created_by_name || (t.created_by ? (isNaN(t.created_by) ? t.created_by : 'Nurse #' + t.created_by) : 'Staff Nurse');
        const name = i.name || i.test_name || t.name || t.test_name || 'Investigation';
        const code = i.id || i.test_id || t.id || t.test_id || 'OTH';
        const cat = i.category || t.category || 'Other';
        const qty = i.qty || i.quantity || t.qty || t.quantity || 1;
        return `<tr>
          <td><strong>${dt}</strong></td>
          <td><strong style="color:#1f6b4a; font-size:0.88rem;">${name}</strong></td>
          <td><span class="badge" style="background:#f3efe6; color:#1f6b4a; border:1.5px solid #1f6b4a; font-weight:800; padding:2px 8px; border-radius:4px;">${code}</span></td>
          <td><span class="badge" style="background:#1f6b4a; color:#f3efe6; font-weight:750; padding:2px 8px; border-radius:4px;">${cat}</span></td>
          <td><strong style="color:#1f6b4a; font-weight:800;">${qty}</strong></td>
          <td><span style="color:#1f6b4a; font-weight:700;">${by}</span></td>
        </tr>`;
      }).join('') : '<tr class="et"><td colspan="6">No Other diagnostic investigations ordered for this patient yet.</td></tr>';
    }
    
    // Pharmacy history
    const phHistory = d.pharmacy_orders || [];
    const phEl = document.getElementById('h-ph');
    if (phEl) {
      phEl.innerHTML = phHistory.length ? phHistory.map(o => {
        const i = o.data || o;
        const isOos = i.is_out_of_stock || (i.stock !== undefined && parseInt(i.stock) <= 0);
        const oosBadge = isOos ? ' <span class="badge" style="background:#fee2e2;color:#dc2626;font-size:0.68rem;border-color:#fca5a5;"><i class="fas fa-exclamation-triangle"></i> Out of Stock</span>' : '';
        return `<tr><td>${o.created_date || i.date || ''}</td><td><strong>${i.medicine || i.name || i.product_name || ''}</strong>${oosBadge}</td><td>${i.batch || i.batch_no || 'N/A'}</td><td>${i.qty || i.quantity || 1}</td></tr>`;
      }).join('') : '<tr class="et"><td colspan="4">No pharmacy orders yet.</td></tr>';
    }
  }catch(er){console.error('loadAllRecords:', er);}
}

function rH(tid,rows,fn,cols){
  const tb=document.getElementById(tid); if(!tb)return;
  if(rows&&rows.length){
    const html=[...rows].reverse().map(r=>{ const s=fn(r); return s?`<tr>${s}</tr>`:''; }).join('');
    tb.innerHTML=html||`<tr class="et"><td colspan="${cols}">No records yet.</td></tr>`;
  } else {
    tb.innerHTML=`<tr class="et"><td colspan="${cols}">No records yet.</td></tr>`;
  }
}

/* ══════════════════════════════════════════════════════════════
   DIAGNOSTIC ORDERS: 3 DEDICATED VIEWS CONTROLLERS
   View 1: LAB (lab_tests)
   View 2: RADIOLOGY (radiology_tests)
   View 3: OTHER (other_tests)
   ══════════════════════════════════════════════════════════════ */
let diagCarts = {
  lab: [],
  radiology: [],
  other: []
};

function switchDiagOrderView(type) {
  const norm = (type === 'rad' || type === 'radiology') ? 'rad' : ((type === 'oth' || type === 'other') ? 'oth' : 'lab');
  ['lab', 'rad', 'oth'].forEach(key => {
    const btn = document.getElementById('btn-tab-order-' + key);
    const pnl = document.getElementById('diag-panel-order-' + key);
    if (btn) btn.classList.toggle('active', key === norm);
    if (pnl) pnl.classList.toggle('active', key === norm);
  });
}

function switchDiagHistView(type) {
  const norm = (type === 'rad' || type === 'radiology') ? 'rad' : ((type === 'oth' || type === 'other') ? 'oth' : 'lab');
  ['lab', 'rad', 'oth'].forEach(key => {
    const btn = document.getElementById('btn-tab-hist-' + key);
    const pnl = document.getElementById('diag-panel-hist-' + key);
    if (btn) btn.classList.toggle('active', key === norm);
    if (pnl) pnl.classList.toggle('active', key === norm);
  });
}

// Bind search input listeners for each of the 3 separate views
['lab', 'radiology', 'other'].forEach(type => {
  const key = type === 'radiology' ? 'rad' : (type === 'other' ? 'oth' : 'lab');
  const input = document.getElementById(`diag-${key}-input`);
  const resultsBox = document.getElementById(`diag-${key}-results`);
  let searchTimer = null;

  if (input && resultsBox) {
    input.addEventListener('input', function() {
      clearTimeout(searchTimer);
      const q = this.value.trim();
      if (q.length < 2) {
        resultsBox.style.display = 'none';
        return;
      }
      searchTimer = setTimeout(() => {
        fetch(`api/search_tests.php?type=${type}&q=` + encodeURIComponent(q))
          .then(r => r.json())
          .then(d => {
            resultsBox.innerHTML = '';
            if (d.success && d.data && d.data.length > 0) {
              d.data.forEach(item => {
                const el = document.createElement('div');
                el.className = 'ts-item';
                el.innerHTML = `<div><strong style="color:#1f6b4a; font-size:0.88rem;">${item.name}</strong><br><small style="color:#1f6b4a; opacity:0.82; font-weight:600;">Code: ${item.id} &bull; Category: ${item.category || type}</small></div><span class="badge" style="background:#1f6b4a; color:#f3efe6; font-weight:800; padding:4px 12px; border-radius:6px; font-size:0.75rem;"><i class="fas fa-plus"></i> Add</span>`;
                el.onclick = () => addDiagToCart(type, item);
                resultsBox.appendChild(el);
              });
            } else {
              resultsBox.innerHTML = '<div style="padding:12px;text-align:center;color:#1f6b4a;font-weight:600;font-size:.82rem">No tests found matching your query.</div>';
            }
            resultsBox.style.display = 'block';
          })
          .catch(() => {
            resultsBox.innerHTML = '<div style="padding:12px;text-align:center;color:#1f6b4a;font-weight:700;font-size:.82rem">Unable to search tests right now. Please retry.</div>';
            resultsBox.style.display = 'block';
          });
      }, 250);
    });

    document.addEventListener('click', e => {
      if (!input.contains(e.target) && !resultsBox.contains(e.target)) {
        resultsBox.style.display = 'none';
      }
    });
  }
});

function addDiagToCart(type, item) {
  const key = type === 'radiology' ? 'rad' : (type === 'other' ? 'oth' : 'lab');
  const input = document.getElementById(`diag-${key}-input`);
  const resultsBox = document.getElementById(`diag-${key}-results`);
  if (resultsBox) resultsBox.style.display = 'none';
  if (input) input.value = '';

  const cart = diagCarts[type];
  const existing = cart.find(x => x.id === item.id);
  if (existing) {
    existing.qty++;
  } else {
    cart.push({
      id: item.id,
      name: item.name,
      category: item.category || (type === 'lab' ? 'Lab' : (type === 'radiology' ? 'Radiology' : 'Other')),
      qty: 1
    });
  }
  renderDiagCart(type);
}

function renderDiagCart(type) {
  const key = type === 'radiology' ? 'rad' : (type === 'other' ? 'oth' : 'lab');
  const container = document.getElementById(`diag-${key}-cart`);
  const badge = document.getElementById(`badge-cart-${key}`);
  const countSpan = document.getElementById(`${key}-cart-count`);
  const cart = diagCarts[type];

  if (badge) badge.textContent = cart.length;
  if (countSpan) countSpan.textContent = `${cart.length} test${cart.length === 1 ? '' : 's'} selected`;

  if (!container) return;
  if (!cart.length) {
    container.innerHTML = '<div style="padding:18px; background:#f3efe6; border:1.5px dashed rgba(31,107,74,0.35); border-radius:8px; font-size:0.82rem; color:#1f6b4a; text-align:center; font-weight:700;"><i class="fas fa-clipboard-list" style="font-size:1.5rem; color:#1f6b4a; margin-bottom:6px; display:block;"></i> No tests selected yet. Search above to add tests to this requisition cart.</div>';
    return;
  }

  container.innerHTML = cart.map(t => `
    <div class="cart-row">
      <div class="cart-row-n">
        <strong style="color:#1f6b4a; font-size:0.88rem;">${t.name}</strong>
        <span class="badge" style="background:#1f6b4a; color:#f3efe6; font-weight:800; font-size:0.72rem; padding:2px 7px; border-radius:4px;">${t.id}</span>
      </div>
      <div style="display:flex; align-items:center; gap:8px;">
        <label style="font-size:0.72rem; font-weight:800; color:#1f6b4a;">QTY:</label>
        <input type="number" value="${t.qty}" min="1" onchange="updateDiagQty('${type}', '${t.id}', this.value)" style="width:65px; padding:5px 8px; border:1.5px solid rgba(31,107,74,0.3); border-radius:6px; text-align:center; font-weight:800; color:#1f6b4a; background:#ffffff;">
        <button type="button" class="rm-btn" onclick="removeDiagFromCart('${type}', '${t.id}')" title="Remove test"><i class="fas fa-trash-alt"></i></button>
      </div>
    </div>
  `).join('');
}

function updateDiagQty(type, id, val) {
  const item = diagCarts[type].find(x => x.id === id);
  if (item) item.qty = Math.max(1, parseInt(val) || 1);
  renderDiagCart(type);
}

function removeDiagFromCart(type, id) {
  diagCarts[type] = diagCarts[type].filter(x => x.id !== id);
  renderDiagCart(type);
}

async function submitDiagOrder(type) {
  if (!cp) { showToast('Please select a patient first!', true); return; }
  const cart = diagCarts[type];
  if (!cart || !cart.length) { showToast('Please add at least one test to the cart.', true); return; }

  const key = type === 'radiology' ? 'rad' : (type === 'other' ? 'oth' : 'lab');
  const btn = document.getElementById(`btn-save-${key}`);
  const origHtml = btn ? btn.innerHTML : '';
  if (btn) { btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...'; btn.disabled = true; }

  const label = type === 'lab' ? 'Diagnostic LAB Order (lab_tests)' : (type === 'radiology' ? 'Diagnostic RADIOLOGY Order (radiology_tests)' : 'Diagnostic OTHER Order (other_tests)');

  try {
    const r = await fetch('api/save_tests.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        patient_id: cp.patient_id,
        admission_id: cp.admission_id || '',
        order_type: type,
        cart: cart
      })
    });
    const res = await r.json();
    if (res.success) {
      showToast(`✅ ${label} saved successfully against patient ${cp.patient_id}!`);
      diagCarts[type] = [];
      renderDiagCart(type);
      loadAllRecords();
      switchDiagHistView(type);
    } else {
      showToast('❌ Error: ' + (res.message || 'Failed to save test order'), true);
    }
  } catch (err) {
    console.error(err);
    showToast('Network error while saving test order!', true);
  } finally {
    if (btn) { btn.innerHTML = origHtml; btn.disabled = false; }
  }
}

// Backward compatibility alias for legacy scripts
tsCart = [];
function renderTestCart() {}
async function saveTests() {
  if (diagCarts.lab.length) await submitDiagOrder('lab');
  if (diagCarts.radiology.length) await submitDiagOrder('radiology');
  if (diagCarts.other.length) await submitDiagOrder('other');
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
  f.querySelectorAll('input:not([type=hidden]):not([readonly]),select,textarea').forEach(inp => {
    const name = inp.name;
    if (!name) return;
    if (data[name] !== undefined && data[name] !== null && data[name] !== '') {
      inp.value = data[name];
    }
  });
  
  // Always lock signature to current logged-in nurse and maintain readonly
  if (NN) {
    f.querySelectorAll('input[name*="_nurse"],input[name*="_sign"],input[name="nurse_sign"]').forEach(i => {
      i.value = NN;
      i.readOnly = true;
      i.setAttribute('readonly', 'readonly');
    });
  }
  
  // Specific mappings for legacy fields
  if (formId === 'f-bp') {
    if (data.date && !data.bp_date) { const el = f.querySelector('input[name="bp_date"]'); if(el) el.value = data.date; }
    if (data.time && !data.bp_time) { const el = f.querySelector('input[name="bp_time"]'); if(el) el.value = data.time; }
    if (data.bp && !data.bp_value) { const el = f.querySelector('input[name="bp_value"]'); if(el) el.value = data.bp; }
    if (data.pulse && !data.bp_pulse) { const el = f.querySelector('input[name="bp_pulse"]'); if(el) el.value = data.pulse; }
    if (data.temp && !data.bp_temp) { const el = f.querySelector('input[name="bp_temp"]'); if(el) el.value = data.temp; }
    if (data.spo2 && !data.bp_spo2) { const el = f.querySelector('input[name="bp_spo2"]'); if(el) el.value = data.spo2; }
    if ((data.pain_score !== undefined || data.bp_pain !== undefined || data.bp_pain_score !== undefined) && !f.querySelector('input[name="pain_score"]')?.value) {
      const el = f.querySelector('input[name="pain_score"]'); if(el) el.value = data.pain_score ?? data.bp_pain ?? data.bp_pain_score ?? '';
    }
  }
  if (formId === 'f-gcs') {
    if ((data.date || data.bp_date || data.gcs_date) && !f.querySelector('input[name="gcs_date"]')?.value) {
      const el = f.querySelector('input[name="gcs_date"]'); if(el) el.value = data.gcs_date || data.bp_date || data.date || '';
    }
    if ((data.time || data.bp_time || data.gcs_time) && !f.querySelector('input[name="gcs_time"]')?.value) {
      const el = f.querySelector('input[name="gcs_time"]'); if(el) el.value = data.gcs_time || data.bp_time || data.time || '';
    }
    // Restore GCS assessment options, calculate score and update color indicator
    setGcsValuesFromRecord(data);
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
    if (data.dia_start_dt) { const el = f.querySelector('input[name="dia_start_dt"]'); if(el) el.value = data.dia_start_dt; }
    else if (data.date && data.dia_start) { const el = f.querySelector('input[name="dia_start_dt"]'); if(el) el.value = data.date + 'T' + data.dia_start; }
    else if (data.dia_date) { const el = f.querySelector('input[name="dia_start_dt"]'); if(el && !el.value) el.value = data.dia_date + 'T00:00'; }

    if (data.dia_end_dt) { const el = f.querySelector('input[name="dia_end_dt"]'); if(el) el.value = data.dia_end_dt; }
    else if (data.date && data.dia_end) { const el = f.querySelector('input[name="dia_end_dt"]'); if(el) el.value = data.date + 'T' + data.dia_end; }

    const isOngoing = data.is_ongoing == '1' || data.is_ongoing === true || (data.dia_dur && data.dia_dur.toLowerCase().includes('ongoing'));
    const chk = f.querySelector('.chk-ongoing');
    if (chk) {
      chk.checked = isOngoing;
      toggleOngoingTreatment(chk);
    }
    if (data.type && !data.dia_type) { const el = f.querySelector('input[name="dia_type"]'); if(el) el.value = data.type; }
    if (data.duration && !data.dia_dur) { const el = f.querySelector('input[name="dia_dur"]'); if(el) el.value = data.duration; }
  }
  if (formId === 'f-ox') {
    if (data.oxy_start_dt) { const el = f.querySelector('input[name="oxy_start_dt"]'); if(el) el.value = data.oxy_start_dt; }
    else if (data.date && data.oxy_start) { const el = f.querySelector('input[name="oxy_start_dt"]'); if(el) el.value = data.date + 'T' + data.oxy_start; }
    else if (data.oxy_date) { const el = f.querySelector('input[name="oxy_start_dt"]'); if(el && !el.value) el.value = data.oxy_date + 'T00:00'; }

    if (data.oxy_end_dt) { const el = f.querySelector('input[name="oxy_end_dt"]'); if(el) el.value = data.oxy_end_dt; }
    else if (data.date && data.oxy_end) { const el = f.querySelector('input[name="oxy_end_dt"]'); if(el) el.value = data.date + 'T' + data.oxy_end; }

    const isOngoing = data.is_ongoing == '1' || data.is_ongoing === true || (data.oxy_dur && data.oxy_dur.toLowerCase().includes('ongoing'));
    const chk = f.querySelector('.chk-ongoing');
    if (chk) {
      chk.checked = isOngoing;
      toggleOngoingTreatment(chk);
    }
    if (data.flow && !data.oxy_flow) { const el = f.querySelector('input[name="oxy_flow"]'); if(el) el.value = data.flow; }
    if (data.duration && !data.oxy_dur) { const el = f.querySelector('input[name="oxy_dur"]'); if(el) el.value = data.duration; }
  }
  if (formId === 'f-nb') {
    if (data.date && !data.nebu_date) { const el = f.querySelector('input[name="nebu_date"]'); if(el) el.value = data.date; }
    if (data.time && !data.nebu_time) { const el = f.querySelector('input[name="nebu_time"]'); if(el) el.value = data.time; }
    if (data.medicine && !data.nebu_drug) { const el = f.querySelector('input[name="nebu_drug"]'); if(el) el.value = data.medicine; }
  }
  if (formId === 'f-ve') {
    if (data.vent_start_dt) { const el = f.querySelector('input[name="vent_start_dt"]'); if(el) el.value = data.vent_start_dt; }
    else if (data.date && data.vent_start) { const el = f.querySelector('input[name="vent_start_dt"]'); if(el) el.value = data.date + 'T' + data.vent_start; }
    else if (data.vent_date) { const el = f.querySelector('input[name="vent_start_dt"]'); if(el && !el.value) el.value = data.vent_date + 'T00:00'; }

    if (data.vent_end_dt) { const el = f.querySelector('input[name="vent_end_dt"]'); if(el) el.value = data.vent_end_dt; }
    else if (data.date && data.vent_end) { const el = f.querySelector('input[name="vent_end_dt"]'); if(el) el.value = data.date + 'T' + data.vent_end; }

    const isOngoing = data.is_ongoing == '1' || data.is_ongoing === true || (data.vent_dur && data.vent_dur.toLowerCase().includes('ongoing'));
    const chk = f.querySelector('.chk-ongoing');
    if (chk) {
      chk.checked = isOngoing;
      toggleOngoingTreatment(chk);
    }
    // Set vent_type and vent_mode
    let vType = data.vent_type || '';
    const vMode = data.vent_mode || data.mode || '';

    // If vent_type was not explicitly saved (older records), infer it from mode
    if (!vType && vMode) {
      const modeLower = vMode.toLowerCase();
      if (modeLower.includes('bipap') || modeLower.includes('cpap') || modeLower.includes('vpap') || modeLower.includes('niv') || modeLower.includes('non-invasive')) {
        vType = 'Non-Invasive Ventilation (NIV)';
      } else {
        vType = 'Invasive Ventilation';
      }
    }

    const typeSelect = document.getElementById('vent_type_select');
    if (typeSelect && vType) {
      typeSelect.value = vType;
    }
    onVentTypeChange(vType, vMode);

    if (data.duration && !data.vent_dur) { const el = f.querySelector('input[name="vent_dur"]'); if(el) el.value = data.duration; }
  }
  if (formId === 'f-bl') {
    if (data.date && !data.trans_date) { const el = f.querySelector('input[name="trans_date"]'); if(el) el.value = data.date; }
    if (data.bag_no && !data.bag_number) { const el = f.querySelector('input[name="bag_number"]'); if(el) el.value = data.bag_no; }
    if (data.vitals && !data.vitals_during) { const el = f.querySelector('input[name="vitals_during"]'); if(el) el.value = data.vitals; }
    if (data.date_of_collection && !data.collection_date) { const el = f.querySelector('input[name="collection_date"]'); if(el) el.value = data.date_of_collection; }
    if (data.date_of_expiry && !data.expiry_date) { const el = f.querySelector('input[name="expiry_date"]'); if(el) el.value = data.date_of_expiry; }
    if (data.before_vitals && !data.vitals_before) { const el = f.querySelector('input[name="vitals_before"]'); if(el) el.value = data.before_vitals; }
    if (data.after_vitals && !data.vitals_after) { const el = f.querySelector('input[name="vitals_after"]'); if(el) el.value = data.after_vitals; }
    if (data.pre_med && !data.pre_medication) { const el = f.querySelector('input[name="pre_medication"]'); if(el) el.value = data.pre_med; }

    // Restore Blood Type, Subtype, and Details
    const bType = data.blood_type || '';
    const bSubtype = data.blood_subtype || '';
    const bDetails = data.blood_type_details || data.details || '';
    const typeSelect = document.getElementById('bt_type_select');
    if (typeSelect && bType) {
      typeSelect.value = bType;
    }
    onBloodTypeChange(bType, bSubtype, bDetails);
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
      setDeptStatus('dis-status-radiology', d.radiology_status, d.radiology_by);

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
    resetGcsToDefault();
    onVentTypeChange('');
    onBloodTypeChange('');
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
