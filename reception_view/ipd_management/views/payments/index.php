<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Receptionist', 'admin', 'Admin'])) {
    header("Location: ../../../../receptionist_login.php");
    exit();
}
$currentUser = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'Staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPD Payments — Premium Dashboard</title>
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Frameworks -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <!-- Base Styles (Existing dependencies) -->
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">
    <link rel="stylesheet" href="../../../assets/css/reception_dashboard.css">
    <link rel="stylesheet" href="../../public/assets/css/ipd_main.css">
    
    <style>
        /* ════════════════════════════════════════════════════════════
           PREMIUM COMPACT ENTERPRISE DESIGN SYSTEM
           Optimized for 1080p Single-Page View
        ════════════════════════════════════════════════════════════ */
        :root {
            /* Luxury Color Palette */
            --bg-color: #f3efe6;
            --surface-white: #ffffff;
            
            --green-primary: #1f6b4a;
            --green-dark: #12402c;
            --green-light: #e6f0eb;
            --green-accent: #2ab87a;
            
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            
            --border-light: rgba(31, 107, 74, 0.08);
            
            /* Compact Radii & Shadows */
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 14px;
            --radius-xl: 18px;
            --radius-pill: 9999px;
            
            --shadow-sm: 0 2px 6px rgba(31, 107, 74, 0.04);
            --shadow-md: 0 4px 12px rgba(31, 107, 74, 0.06);
            --shadow-lg: 0 8px 24px rgba(31, 107, 74, 0.08);
            
            /* Status Colors */
            --success: #22c55e;
            --success-bg: #dcfce7;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        body {
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(circle at 15% 50%, rgba(31, 107, 74, 0.03) 0%, transparent 50%);
            background-attachment: fixed;
            font-family: 'Inter', sans-serif;
            color: var(--text-primary);
            font-size: 13px; /* Reduced base font size */
        }
        
        .animated-ring {
            stroke-dasharray: 251.2; /* 2 * pi * 40 */
            stroke-dashoffset: 251.2;
            transition: stroke-dashoffset 1s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ── LAYOUT ── */
        .page-container {
            max-width: 100%;
            padding: 0.5rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            min-height: calc(100vh - 60px);
            overflow-y: auto;
        }

        /* ── HEADER / TOP NAV ── */
        .top-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.25rem 0.5rem;
            margin-bottom: 0.25rem;
        }
        .search-module {
            display: flex;
            gap: 0.75rem;
            flex: 1;
            max-width: 600px;
        }
        .premium-input-wrap {
            position: relative;
            flex: 1;
        }
        .premium-input-wrap i.icon-left {
            position: absolute;
            left: 0.8rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--green-primary);
            font-size: 0.9rem;
            opacity: 0.7;
        }
        .premium-input {
            width: 100%;
            background: var(--surface-white);
            border: 1px solid rgba(31, 107, 74, 0.15);
            border-radius: var(--radius-pill);
            padding: 0.4rem 1rem 0.4rem 2.2rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-primary);
            box-shadow: var(--shadow-sm);
        }
        .premium-input:focus {
            outline: none;
            border-color: var(--green-primary);
        }
        .premium-select {
            appearance: none;
            cursor: pointer;
            padding-right: 1.8rem;
            min-width: 150px;
        }
        .premium-input-wrap i.icon-right {
            position: absolute;
            right: 0.8rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 0.75rem;
            pointer-events: none;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-premium {
            background: var(--surface-white);
            border: 1px solid rgba(31, 107, 74, 0.15);
            border-radius: var(--radius-pill);
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-primary);
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
        }
        .btn-premium:hover {
            border-color: var(--green-primary);
            color: var(--green-primary);
        }
        .btn-premium.solid {
            background: linear-gradient(135deg, var(--green-primary), var(--green-dark));
            color: white;
            border: none;
        }
        .btn-premium.solid:hover { color: white; opacity: 0.9; }

        /* SEARCH DROPDOWN */
        #admSearchDropdown {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            width: 100%;
            background: var(--surface-white);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-light);
            z-index: 1000;
            display: none;
        }
        .search-result-item {
            padding: 0.5rem 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            border-bottom: 1px solid var(--border-light);
        }
        .search-result-item:hover { background: var(--green-light); }
        .sr-avatar {
            width: 32px; height: 32px;
            border-radius: 8px;
            background: var(--green-primary);
            color: white;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.8rem;
        }
        .sr-name { font-weight: 700; font-size: 0.85rem; }
        .sr-meta { font-size: 0.75rem; color: var(--text-secondary); }
        .sr-status {
            font-size: 0.65rem; font-weight: 700; padding: 0.15rem 0.4rem;
            border-radius: var(--radius-pill);
            background: var(--success-bg); color: var(--success);
        }

        /* ── PATIENT HERO SECTION ── */
        .hero-banner {
            background: linear-gradient(135deg, var(--green-dark), var(--green-primary));
            border-radius: var(--radius-lg);
            padding: 0.75rem 1.25rem;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            box-shadow: var(--shadow-md);
            display: none;
        }
        .hero-banner.show { display: flex; animation: fadeUp 0.3s ease; }
        .hero-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .hero-avatar {
            width: 44px; height: 44px;
            border-radius: 10px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; font-weight: 800;
        }
        .hero-details h1 {
            font-size: 1.1rem;
            font-weight: 800;
            margin: 0 0 0.15rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .hero-tag {
            font-size: 0.65rem;
            padding: 0.15rem 0.4rem;
            border-radius: var(--radius-pill);
            background: rgba(255,255,255,0.15);
        }
        .hero-meta {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.8);
            display: flex;
            gap: 0.75rem;
        }
        .hero-right {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }
        .hero-card {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: var(--radius-sm);
            padding: 0.4rem 0.75rem;
            min-width: 120px;
        }
        .hero-card-label {
            font-size: 0.6rem;
            text-transform: uppercase;
            color: rgba(255,255,255,0.7);
            margin-bottom: 0.15rem;
            font-weight: 700;
        }
        .hero-card-value {
            font-size: 0.85rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        /* ── FINANCIAL OVERVIEW HORIZONTAL PANEL ── */
        .finance-panel {
            background: var(--surface-white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            padding: 0.75rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            display: none;
        }
        .finance-panel.show { display: flex; animation: fadeUp 0.4s ease forwards; }
        
        .fin-block {
            display: flex;
            flex-direction: column;
            width: 150px;
        }
        .fin-header {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            margin-bottom: 0.25rem;
        }
        .fin-icon {
            width: 22px; height: 22px;
            border-radius: 6px;
            background: var(--green-light);
            color: var(--green-primary);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.7rem;
        }
        .fin-label {
            font-size: 0.65rem;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
        }
        .fin-value {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text-primary);
        }
        
        /* Center Dial */
        .fin-center-dial {
            position: relative;
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dial-svg {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            transform: rotate(-90deg);
        }
        .dial-bg { fill: none; stroke: var(--green-light); stroke-width: 6; }
        .dial-prog { fill: none; stroke: var(--green-primary); stroke-width: 6; stroke-linecap: round; }
        .dial-content {
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-sm);
            z-index: 2;
        }
        .dial-label { font-size: 0.55rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; }
        .dial-val { font-size: 1rem; font-weight: 800; color: var(--green-primary); margin: 0.1rem 0; }
        .dial-status { font-size: 0.55rem; font-weight: 600; color: var(--success); display: flex; align-items: center; gap: 0.2rem; }

        /* ── TWO COLUMN COMPACT LAYOUT ── */
        .grid-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            flex: 1; /* take remaining height */
            min-height: 0;
            display: none;
        }
        .grid-layout.show { display: grid; animation: fadeUp 0.5s ease forwards; }

        .premium-panel {
            background: var(--surface-white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.6rem 1rem;
            border-bottom: 1px solid var(--border-light);
            background: rgba(31, 107, 74, 0.02);
        }
        .panel-title {
            font-size: 0.85rem;
            font-weight: 800;
            color: var(--green-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0;
        }
        
        /* ── BILL BREAKDOWN TABLE ── */
        .bill-container {
            padding: 0 1rem;
            overflow-y: auto;
            flex: 1;
            scrollbar-width: thin;
        }
        .bill-table {
            width: 100%;
            border-collapse: collapse;
        }
        .bill-table th {
            font-size: 0.65rem;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            padding: 0.5rem 0.25rem;
            text-align: right;
            border-bottom: 1px solid var(--border-light);
            position: sticky; top: 0; background: var(--surface-white); z-index: 2;
        }
        .bill-table th:first-child { text-align: left; }
        .bill-table td {
            padding: 0.4rem 0.25rem;
            font-size: 0.85rem;
            color: var(--text-primary);
            border-bottom: 1px dashed var(--border-light);
        }
        .bill-row td:first-child { display: flex; align-items: center; gap: 0.5rem; font-weight: 500; }
        .bill-row i { color: var(--green-primary); font-size: 0.85rem; width: 16px; text-align: center; }
        .bill-row td:last-child { text-align: right; font-weight: 700; }
        .bill-discount td { color: var(--success); }

        .grand-total-bar {
            background: linear-gradient(135deg, var(--green-dark), var(--green-primary));
            padding: 0.6rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            flex-shrink: 0;
        }
        .gt-label { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .gt-value { font-size: 1.1rem; font-weight: 800; }

        /* ── PAYMENT TIMELINE ── */
        .history-container {
            padding: 0.75rem 1rem;
            overflow-y: auto;
            flex: 1;
            scrollbar-width: thin;
        }
        .timeline-container {
            position: relative;
            padding-left: 1rem;
        }
        .timeline-container::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 2px;
            background: var(--green-light);
        }
        .tl-item {
            position: relative;
            margin-bottom: 0.75rem;
            padding: 0.5rem 0.75rem;
            background: var(--surface-white);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .tl-item::before {
            content: '';
            position: absolute;
            left: -1.35rem; top: 50%;
            transform: translateY(-50%);
            width: 10px; height: 10px;
            border-radius: 50%;
            background: var(--surface-white);
            border: 2px solid var(--text-muted);
            z-index: 2;
        }
        .tl-item.latest::before { border-color: var(--green-primary); background: var(--green-primary); box-shadow: 0 0 0 3px var(--green-light); }
        
        .tl-left { display: flex; flex-direction: column; }
        .tl-date { font-size: 0.75rem; font-weight: 700; color: var(--text-primary); }
        .tl-date span { color: var(--text-secondary); font-size: 0.65rem; font-weight: 500; }
        
        .tl-mid { display: flex; flex-direction: column; }
        .tl-mode { font-size: 0.8rem; font-weight: 600; }
        .tl-by { font-size: 0.65rem; color: var(--text-secondary); }
        
        .tl-right { text-align: right; font-size: 0.95rem; font-weight: 800; color: var(--success); }
        .tl-right.refund { color: var(--danger); }
        
        /* Payment Progress Bar (Right Panel Header) */
        .progress-box {
            padding: 0.5rem 1rem;
            border-bottom: 1px solid var(--border-light);
            background: rgba(31, 107, 74, 0.01);
            flex-shrink: 0;
        }
        .prog-head { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 0.3rem; }
        .prog-title { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; color: var(--text-secondary); }
        .prog-pct { font-size: 0.85rem; font-weight: 800; }
        .prog-track { height: 6px; background: var(--green-light); border-radius: var(--radius-pill); }
        .prog-fill { height: 100%; background: linear-gradient(90deg, var(--green-primary), var(--green-accent)); border-radius: var(--radius-pill); transition: width 0.5s ease; }

        /* Settled State Card */
        .settled-card {
            background: var(--success-bg);
            border: 1px solid rgba(34,197,94,0.2);
            border-radius: var(--radius-md);
            padding: 0.6rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-top: 0.5rem;
            display: none;
        }
        .settled-icon { width: 32px; height: 32px; border-radius: 8px; background: var(--success); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; }
        .settled-text h4 { margin: 0; font-size: 0.85rem; font-weight: 800; color: var(--green-dark); }
        .settled-text p { margin: 0; font-size: 0.75rem; color: var(--green-primary); }

        /* ── COMPACT MODAL ── */
        .luxury-modal-overlay {
            position: fixed; inset: 0; background: rgba(30, 41, 59, 0.5); z-index: 2000;
            display: none; align-items: center; justify-content: center;
        }
        .luxury-modal-overlay.open { display: flex; }
        .luxury-modal-card {
            background: var(--bg-color); width: 100%; max-width: 480px;
            border: 2px solid var(--green-primary);
            border-radius: var(--radius-xl); box-shadow: var(--shadow-lg);
            padding: 1.5rem; position: relative;
        }
        .modal-close {
            position: absolute; top: 1rem; right: 1rem;
            width: 28px; height: 28px; border-radius: 50%; background: var(--bg-color); color: var(--green-primary);
            border: 2px solid var(--green-primary); display: flex; align-items: center; justify-content: center; cursor: pointer;
        }
        .modal-header-l { text-align: center; margin-bottom: 1rem; }
        .modal-header-l h2 { font-size: 1.25rem; font-weight: 800; color: var(--green-primary); margin: 0; }
        
        .modal-ribbon {
            background: var(--green-primary); border-radius: var(--radius-md);
            padding: 0.75rem 1rem; display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;
        }
        .modal-ribbon-lbl { font-size: 0.65rem; font-weight: 700; color: var(--bg-color); text-transform: uppercase; }
        .modal-ribbon-val { font-size: 1.1rem; font-weight: 800; color: var(--bg-color); }
        
        .form-label-lux { font-size: 0.7rem; font-weight: 700; color: var(--green-primary); text-transform: uppercase; margin-bottom: 0.3rem; display: block; }
        .form-input-lux {
            width: 100%; border: 1px solid var(--green-primary); border-radius: var(--radius-sm);
            padding: 0.6rem 1rem; font-size: 0.9rem; font-weight: 600; margin-bottom: 0.75rem; outline: none; background: var(--bg-color); color: var(--green-primary);
        }
        .form-input-lux:focus { border-color: var(--green-primary); }
        
        .grid-modes { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.4rem; margin-bottom: 1rem; }
        .mode-btn {
            border: 1px solid var(--green-primary); border-radius: var(--radius-sm); background: var(--bg-color);
            padding: 0.5rem 0.2rem; text-align: center; font-size: 0.65rem; font-weight: 700; color: var(--green-primary); cursor: pointer;
        }
        .mode-btn i { display: block; font-size: 1rem; margin-bottom: 0.15rem; }
        .mode-btn.active { background: var(--green-primary); border-color: var(--green-primary); color: var(--bg-color); }

        .btn-lux-submit {
            width: 100%; background: var(--green-primary);
            color: var(--bg-color); border: 2px solid var(--green-primary); border-radius: var(--radius-sm); padding: 0.75rem;
            font-size: 0.9rem; font-weight: 700; cursor: pointer; margin-top: 0.5rem;
        }

        @keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
<div class="reception-layout" style="background: var(--bg-color);">
    <?php include '../../../includes/reception_sidebar.php'; ?>

    <div class="reception-main-content">
        <?php $pageTitle = 'IPD Financials'; include '../../../includes/reception_navbar.php'; ?>

        <div class="page-container">
            
            <!-- TOP NAV -->
            <div class="top-nav">
                <div class="search-module">
                    <div class="premium-input-wrap">
                        <i class="fas fa-search icon-left"></i>
                        <input type="text" class="premium-input" id="admissionSearchInput" placeholder="FIND ADMISSION (Name, Phone, IPD No.)" autocomplete="off" oninput="searchAdmissions(this.value)">
                        <i class="fas fa-spinner fa-spin icon-right" id="searchSpinner" style="display:none; color:var(--green-primary);"></i>
                        <div id="admSearchDropdown"><div id="admSearchList" style="max-height:200px; overflow-y:auto;"></div></div>
                    </div>
                    <div class="premium-input-wrap" style="max-width: 180px;">
                        <i class="fas fa-filter icon-left"></i>
                        <select class="premium-input premium-select" id="statusFilter" onchange="applyStatusFilter()">
                            <option value="">ALL STATUSES</option>
                            <option value="Admitted" selected>ADMITTED (ACTIVE)</option>
                            <option value="Discharged">DISCHARGED</option>
                        </select>
                        <i class="fas fa-chevron-down icon-right"></i>
                    </div>
                </div>
                
                <div class="header-actions">
                    <button class="btn-premium" id="btnAdmDetails" style="display:none;" onclick="viewAdmDetails()">
                        <i class="far fa-file-alt"></i> Details
                    </button>
                    <button class="btn-premium solid" onclick="openPayModal()">
                        <i class="fas fa-plus"></i> Record Payment
                    </button>
                </div>
            </div>

            <!-- HERO SECTION -->
            <div class="hero-banner" id="patientBanner">
                <div class="hero-left">
                    <div class="hero-avatar" id="patientAvatar">NH</div>
                    <div class="hero-details">
                        <h1 id="bannerName">Patient Name <span class="hero-tag" id="bannerGenderAge">N/A</span></h1>
                        <div class="hero-meta">
                            <span><i class="far fa-id-card"></i> UHID: <span id="bannerUHID">-</span></span>
                            <span>•</span>
                            <span><i class="fas fa-procedures"></i> IPD No: <span id="bannerIPD">-</span></span>
                        </div>
                    </div>
                </div>
                
                <div class="hero-right">
                    <div class="hero-card">
                        <div class="hero-card-label">Admission Date</div>
                        <div class="hero-card-value"><i class="far fa-calendar-alt"></i> <span id="bannerAdmDate">-</span></div>
                    </div>
                    <div class="hero-card">
                        <div class="hero-card-label">Consultant</div>
                        <div class="hero-card-value"><i class="far fa-user-md"></i> <span id="bannerDoctor">-</span></div>
                        <div style="font-size:0.6rem; color:rgba(255,255,255,0.7);" id="bannerWard">-</div>
                    </div>
                    <div class="hero-card" style="background:rgba(34,197,94,0.15); border-color:rgba(34,197,94,0.3);">
                        <div class="hero-card-label" style="color:var(--success-bg);">Status</div>
                        <div class="hero-card-value" style="color:white;"><i class="fas fa-heartbeat"></i> <span id="bannerStatus">-</span></div>
                    </div>
                </div>
            </div>

            <!-- FINANCE PANEL -->
            <div class="finance-panel" id="financePanel">
                <div class="fin-block">
                    <div class="fin-header"><div class="fin-icon"><i class="fas fa-clipboard-list"></i></div><div class="fin-label">Total Bill</div></div>
                    <div class="fin-value" id="finTotal">₹0.00</div>
                </div>
                <div class="fin-block">
                    <div class="fin-header"><div class="fin-icon"><i class="fas fa-wallet"></i></div><div class="fin-label">Amount Paid</div></div>
                    <div class="fin-value" id="finPaid">₹0.00</div>
                </div>
                
                <div class="fin-center-dial">
                    <svg class="dial-svg"><circle class="dial-bg" cx="50" cy="50" r="40"></circle><circle class="dial-prog animated-ring" id="dialRing" cx="50" cy="50" r="40"></circle></svg>
                    <div class="dial-content">
                        <div class="dial-label">Balance Due</div>
                        <div class="dial-val" id="finBalance">₹0</div>
                        <div class="dial-status" id="finStatus"><i class="fas fa-check-circle"></i> Clear</div>
                    </div>
                </div>
                
                <div class="fin-block" style="align-items: flex-end; text-align: right;">
                    <div class="fin-header" style="flex-direction: row-reverse;"><div class="fin-icon"><i class="fas fa-receipt"></i></div><div class="fin-label">Transactions</div></div>
                    <div class="fin-value" id="finTxnCount">0</div>
                </div>
            </div>

            <!-- GRID (BOTTOM SPLIT) -->
            <div class="grid-layout" id="gridLayout">
                
                <!-- LEFT: BILL BREAKDOWN -->
                <div class="premium-panel">
                    <div class="panel-header">
                        <h3 class="panel-title"><i class="fas fa-file-invoice-dollar"></i> Bill Breakdown</h3>
                    </div>
                    <div class="bill-container">
                        <table class="bill-table" id="breakdownTable">
                            <thead><tr><th>Description</th><th>Amount (₹)</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="grand-total-bar">
                        <div class="gt-label"><i class="fas fa-shield-check"></i> Grand Total</div>
                        <div class="gt-value" id="bdGrandTotal">₹0.00</div>
                    </div>
                </div>

                <!-- RIGHT: PAYMENT HISTORY -->
                <div class="premium-panel">
                    <div class="progress-box">
                        <div class="prog-head">
                            <div class="prog-title">Payment Progress</div>
                            <div class="prog-pct" id="progPctText">0%</div>
                        </div>
                        <div class="prog-track"><div class="prog-fill" id="progFill" style="width:0%"></div></div>
                        <div class="settled-card" id="settledCard">
                            <div class="settled-icon"><i class="fas fa-check"></i></div>
                            <div class="settled-text"><h4>No outstanding balance</h4><p>All payments are settled.</p></div>
                        </div>
                    </div>
                    <div class="panel-header" style="border-top: 1px solid var(--border-light); border-bottom: none;"><h3 class="panel-title"><i class="fas fa-history"></i> Payment History</h3></div>
                    <div class="history-container">
                        <div class="timeline-container" id="paymentTimeline"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ── MODAL ── -->
<div class="luxury-modal-overlay" id="payModalOverlay" onclick="closePayModal(event)">
    <div class="luxury-modal-card" onclick="event.stopPropagation()">
        <button class="modal-close" onclick="closePayModal()"><i class="fas fa-times"></i></button>
        <div class="modal-header-l"><h2><i class="fas fa-wallet"></i> Record Payment</h2></div>
        <div class="modal-ribbon">
            <div><div class="modal-ribbon-lbl">Balance</div><div class="modal-ribbon-val" id="modalBalance">₹0.00</div></div>
            <div style="text-align:right;"><div class="modal-ribbon-lbl">Patient</div><div style="font-size:0.8rem; font-weight:700; color:var(--bg-color);" id="modalPatientName">—</div></div>
        </div>
        <label class="form-label-lux">Payment Mode</label>
        <div class="grid-modes" id="ptypeGrid">
            <div class="mode-btn active" data-mode="CASH" onclick="selectMode('CASH',this)"><i class="fas fa-money-bill-wave"></i>Cash</div>
            <div class="mode-btn" data-mode="UPI" onclick="selectMode('UPI',this)"><i class="fas fa-mobile-alt"></i>UPI</div>
            <div class="mode-btn" data-mode="CARD" onclick="selectMode('CARD',this)"><i class="fas fa-credit-card"></i>Card</div>
            <div class="mode-btn" data-mode="NETBANKING" onclick="selectMode('NETBANKING',this)"><i class="fas fa-university"></i>Net Bank</div>
        </div>
        <label class="form-label-lux">Amount *</label>
        <input type="number" class="form-input-lux" id="payAmount" placeholder="Enter amount..." min="0.01" step="0.01">
        <div class="row g-2">
            <div class="col-6"><label class="form-label-lux">Date</label><input type="date" class="form-input-lux" id="payDate"></div>
            <div class="col-6"><label class="form-label-lux">Time</label><input type="time" class="form-input-lux" id="payTime"></div>
        </div>
        <div id="refGroup" style="display:none;"><label class="form-label-lux">Reference</label><input type="text" class="form-input-lux" id="payRef"></div>
        <button class="btn-lux-submit" id="submitPayBtn" onclick="submitPayment()">Confirm Payment</button>
    </div>
</div>

<!-- Center Error Modal -->
<div class="luxury-modal-overlay" id="errorModalOverlay" onclick="closeErrorModal(event)">
    <div class="luxury-modal-card" style="text-align: center; max-width: 400px; border: 2px solid var(--danger, #ef4444); background: #fef2f2;" onclick="event.stopPropagation()">
        <button class="modal-close" style="background:#fef2f2; color:#ef4444; border: 2px solid #ef4444;" onclick="closeErrorModal()"><i class="fas fa-times"></i></button>
        <div style="margin-bottom: 1rem; color: #ef4444; font-size: 3rem;"><i class="fas fa-exclamation-triangle"></i></div>
        <h3 style="color: #ef4444; margin-bottom: 0.5rem;">Error Occurred</h3>
        <p id="centerErrorMessage" style="color: #7f1d1d; font-size: 0.9rem; font-weight: 600;">Server Error</p>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

<script>
const API = '/GM_HMS/reception_view/ipd_management/public/api.php/api/';
const CURRENT_USER = '<?= htmlspecialchars($currentUser) ?>';
let currentAdmission = null; let selectedMode = 'CASH'; let searchTimer = null;

function fmt(n) { return '₹' + (parseFloat(n)||0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
function toast(msg, type='success') { Toastify({ text: msg, duration: 3200, gravity: 'top', position: 'right', style: { background: type==='success'?'#1f6b4a':'#ef4444', color: '#fff', borderRadius: '8px', fontSize: '0.8rem' } }).showToast(); }

function searchAdmissions(val) {
    clearTimeout(searchTimer);
    const dd = document.getElementById('admSearchDropdown'), list = document.getElementById('admSearchList'), sp = document.getElementById('searchSpinner'), st = document.getElementById('statusFilter').value;
    if (val.trim().length < 2) { dd.style.display = 'none'; return; }
    sp.style.display = 'inline';
    searchTimer = setTimeout(() => {
        fetch(`${API}admissions?search=${encodeURIComponent(val.trim())}${st?'&status='+encodeURIComponent(st):''}`)
            .then(r=>r.json()).then(res => {
                sp.style.display = 'none';
                const items = res.data?.admissions || [];
                if(!items.length) { list.innerHTML = '<div style="padding:1rem;text-align:center;color:#94a3b8;font-size:0.8rem;">No admissions found</div>'; dd.style.display='block'; return; }
                list.innerHTML = items.map(a => `<div class="search-result-item" onclick='selectAdmission(${JSON.stringify(a).replace(/'/g, "&#39;")})'><div class="sr-avatar">${(a.patient_name||'U').substring(0,2).toUpperCase()}</div><div style="flex:1"><div class="sr-name">${a.patient_name||a.patient_first_name}</div><div class="sr-meta">${a.admission_id}</div></div><div class="sr-status">${a.status||'Active'}</div></div>`).join('');
                dd.style.display = 'block';
            }).catch(()=>sp.style.display='none');
    }, 300);
}
function applyStatusFilter() { const v = document.getElementById('admissionSearchInput').value; if(v.trim().length>=2) searchAdmissions(v); }
document.addEventListener('click', e => { if(!e.target.closest('.premium-input-wrap')) document.getElementById('admSearchDropdown').style.display = 'none'; });
function viewAdmDetails() { if(currentAdmission) window.location.href = `/GM_HMS/reception_view/ipd_management/views/admissions/details.php?id=${currentAdmission.admission_id}`; }

function selectAdmission(adm) {
    currentAdmission = adm; document.getElementById('admSearchDropdown').style.display = 'none';
    document.getElementById('admissionSearchInput').value = (adm.patient_name||adm.patient_first_name) + ' — ' + adm.admission_id;
    document.getElementById('btnAdmDetails').style.display = 'inline-flex';
    document.getElementById('patientAvatar').textContent = (adm.patient_name||'U').substring(0,2).toUpperCase();
    document.getElementById('bannerName').innerHTML = `${adm.patient_name||adm.patient_first_name} <span class="hero-tag">${adm.gender||'N/A'} • ${adm.age||'N/A'} Yrs</span>`;
    document.getElementById('bannerUHID').textContent = adm.patient_id||'-'; document.getElementById('bannerIPD').textContent = adm.admission_id||'-';
    document.getElementById('bannerAdmDate').textContent = adm.admission_date?new Date(adm.admission_date).toLocaleString('en-IN',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}):'-';
    document.getElementById('bannerDoctor').textContent = adm.doctor_name?'Dr. '+adm.doctor_name:'-';
    document.getElementById('bannerWard').textContent = adm.ward_name||'General Ward'; document.getElementById('bannerStatus').textContent = adm.status||'Admitted';
    document.getElementById('patientBanner').classList.add('show'); loadPaymentData();
}

function loadPaymentData() {
    if (!currentAdmission) return;
    fetch(`${API}ipd-billing-master`, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({action:'create', admission_id:currentAdmission.admission_id, patient_id:currentAdmission.patient_id, created_by:CURRENT_USER}) })
    .then(r=>r.json()).then(res => {
        const b = res.data || {}; currentAdmission.bill_id = b.bill_id;
        const total = parseFloat(b.grand_total)||0, paid = parseFloat(b.amount_paid)||0, bal = parseFloat(b.balance_due)||0;
        document.getElementById('financePanel').classList.add('show'); document.getElementById('gridLayout').classList.add('show');
        document.getElementById('finTotal').textContent = fmt(total); document.getElementById('finPaid').textContent = fmt(paid); document.getElementById('finBalance').textContent = fmt(bal);
        
        const pct = total>0 ? Math.min(100, Math.round((paid/total)*100)) : 0;
        const off = 251.2 - (pct/100)*251.2; setTimeout(()=>document.getElementById('dialRing').style.strokeDashoffset = off, 100);
        
        if (bal<=0 && total>0) { document.getElementById('finStatus').innerHTML = '<i class="fas fa-check-circle"></i> Clear'; document.getElementById('finStatus').style.color='var(--success)'; document.getElementById('dialRing').style.stroke='var(--success)'; document.getElementById('settledCard').style.display='flex'; } 
        else { document.getElementById('finStatus').innerHTML = '<i class="fas fa-exclamation-circle"></i> Pending'; document.getElementById('finStatus').style.color='var(--warning)'; document.getElementById('dialRing').style.stroke='var(--green-primary)'; document.getElementById('settledCard').style.display='none'; }
        
        document.getElementById('progFill').style.width = pct+'%'; document.getElementById('progPctText').textContent = pct+'%';
        document.getElementById('progFill').style.background = (pct===100&&total>0) ? 'var(--success)' : '';

        const bdTbody = document.querySelector('#breakdownTable tbody');
        const items = [
            { icon: 'fa-bed', desc: 'Room Charges', val: b.room_charges },
            { icon: 'fa-user-md', desc: 'Doctor Charges', val: b.doctor_charges },
            { icon: 'fa-flask', desc: 'Lab & Radio', val: parseFloat(b.lab_charges||0) + parseFloat(b.radiology_charges||0) },
            { icon: 'fa-pills', desc: 'Pharmacy', val: b.pharmacy_charges },
            { icon: 'fa-heartbeat', desc: 'Proc / OT', val: parseFloat(b.procedure_charges||0) + parseFloat(b.ot_charges||0) },
            { icon: 'fa-box', desc: 'Other', val: parseFloat(b.other_charges||0) + parseFloat(b.consumable_charges||0) }
        ];
        
        let html = '';
        items.forEach(i => {
            html += `<tr class="bill-row">
                <td><i class="fas ${i.icon}"></i> ${i.desc}</td>
                <td>${fmt(i.val)}</td>
            </tr>`;
        });
        
        if (parseFloat(b.discount_amount) > 0) {
            html += `<tr class="bill-row bill-discount">
                <td><i class="fas fa-tags"></i> Discount</td>
                <td>- ${fmt(b.discount_amount)}</td>
            </tr>`;
        }
        bdTbody.innerHTML = html;
        document.getElementById('bdGrandTotal').textContent = fmt(total);

        return fetch(`${API}ipd-payment?bill_id=${b.bill_id}&action=list`);
    })
    .then(r=>r.json()).then(res => {
        const p = res.data?.payments || []; document.getElementById('finTxnCount').textContent = p.length;
        if(!p.length) { document.getElementById('paymentTimeline').innerHTML = '<div style="text-align:center;color:var(--text-muted);font-size:0.8rem;margin-top:1rem;">No payments found</div>'; return; }
        document.getElementById('paymentTimeline').innerHTML = [...p].reverse().map((x,i) => {
            const dt = new Date(x.payment_date); return `<div class="tl-item ${i===0?'latest':''}">
            <div class="tl-left"><div class="tl-date">${dt.toLocaleString('en-IN',{day:'2-digit',month:'short'})}</div><div class="tl-date"><span>${dt.toLocaleString('en-IN',{hour:'2-digit',minute:'2-digit'})}</span></div></div>
            <div class="tl-mid"><div class="tl-mode">${x.payment_mode}</div><div class="tl-by">By: ${x.created_by||'Sys'}</div></div>
            <div class="tl-right ${x.payment_type==='REFUND'?'refund':''}">${x.payment_type==='REFUND'?'-':'+'}${fmt(x.amount)}</div></div>`;
        }).join('');
    }).catch(console.error);
}

function openPayModal() {
    if(!currentAdmission?.bill_id) return toast('Select admission','error');
    const n = new Date(); document.getElementById('payDate').value=n.toISOString().split('T')[0]; document.getElementById('payTime').value=n.toTimeString().slice(0,5);
    document.getElementById('payAmount').value=''; document.getElementById('payRef').value=''; selectMode('CASH',document.querySelector('[data-mode="CASH"]'));
    document.getElementById('modalBalance').textContent = document.getElementById('finBalance').textContent; document.getElementById('modalPatientName').textContent = currentAdmission.patient_name;
    document.getElementById('payModalOverlay').classList.add('open'); setTimeout(()=>document.getElementById('payAmount').focus(),100);
}
function closePayModal(e) { if(!e||e.target===e.currentTarget) document.getElementById('payModalOverlay').classList.remove('open'); }
function selectMode(m, el) { selectedMode=m; document.querySelectorAll('.mode-btn').forEach(b=>b.classList.remove('active')); if(el) el.classList.add('active'); document.getElementById('refGroup').style.display=(m==='CASH')?'none':'block'; }
function submitPayment() {
    const amt = parseFloat(document.getElementById('payAmount').value); if(!amt||amt<=0) return toast('Invalid amount','error');
    const btn = document.getElementById('submitPayBtn'); btn.disabled=true; btn.innerHTML='Processing...';
    const ref = document.getElementById('payRef').value;
    fetch(`${API}ipd-billing-master`,{ method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'add_payment',bill_id:currentAdmission.bill_id,admission_id:currentAdmission.admission_id,amount:amt,updated_by:CURRENT_USER,payment_mode:selectedMode,reference:ref}) })
    .then(r=>{ 
        if(!r.ok) return r.text().then(txt => {
            let errMsg = 'Server Error: ' + r.status;
            try { const json = JSON.parse(txt); if(json.error) errMsg = json.error; else if(json.message) errMsg = json.message; } catch(e) { errMsg = txt.substring(0, 200); }
            throw new Error(errMsg);
        });
        return r.json(); 
    }).then(res => { 
        btn.disabled=false; btn.innerHTML='Confirm Payment'; 
        if(res.status === 'success' || res.success) { 
            toast('Payment recorded'); closePayModal(); loadPaymentData(); 
        } else { 
            showCenterError(res.message || res.error || 'Unknown error occurred');
        } 
    })
    .catch((err)=>{ 
        btn.disabled=false; btn.innerHTML='Confirm Payment'; 
        showCenterError(err.message || 'Network error'); 
    });
}
function showCenterError(msg) {
    document.getElementById('centerErrorMessage').textContent = msg;
    document.getElementById('errorModalOverlay').classList.add('open');
}
function closeErrorModal(e) {
    if(!e||e.target===e.currentTarget) document.getElementById('errorModalOverlay').classList.remove('open');
}
document.addEventListener('DOMContentLoaded', () => { const id = new URLSearchParams(window.location.search).get('admission_id'); if(id) fetch(`${API}admissions?id=${id}`).then(r=>r.json()).then(res=>{if(res.data)selectAdmission(res.data);}); });
</script>
</body>
</html>