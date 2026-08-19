<?php
session_start();

// Check authentication - exact backend logic preserved
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Nurse', 'Superintendent_Nurse', 'Nursing_Superintendent', 'admin', 'Admin'])) {
    header('Location: ../login.php');
    exit();
}

$nurseId = $_SESSION['user_id'] ?? null;
$nurseName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Nurse';
$nurseRole = $_SESSION['role'] ?? 'Staff Nurse';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>My Duty Command Center & Schedule - GM HMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700&display=swap" rel="stylesheet">
    <style>
        /* ── GM HMS Signature 2-Color Design System (#f3efe6 & #1f6b4a) ── */
        :root {
            --gm-bg: #f3efe6;
            --gm-bg-card: #ffffff;
            --gm-primary: #1f6b4a;
            --gm-primary-dark: #144d34;
            --gm-primary-light: rgba(31, 107, 74, 0.08);
            --gm-primary-mid: rgba(31, 107, 74, 0.16);
            --gm-border: rgba(31, 107, 74, 0.22);
            --gm-border-strong: #1f6b4a;
            --gm-text: #1f6b4a;
            --gm-text-body: #23342b;
            --gm-text-muted: #527967;
            --gm-sidebar-w: 185px;

            --shadow-subtle: 0 4px 16px rgba(31, 107, 74, 0.06);
            --shadow-elevated: 0 10px 30px rgba(31, 107, 74, 0.12);
        }

        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif; 
        }

        body { 
            background: var(--gm-bg); 
            min-height: 100vh; 
            display: flex; 
            color: var(--gm-text-body); 
            overflow-x: hidden; 
            -webkit-font-smoothing: antialiased;
        }

        .main-layout { 
            display: flex; 
            width: 100%; 
            min-height: 100vh;
        }

        .content-wrapper { 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
            min-width: 0;
            background-color: var(--gm-bg);
            transition: margin-left 0.25s ease;
        }
        
        @media (min-width: 1024px) {
            .content-wrapper { margin-left: var(--gm-sidebar-w, 185px); }
        }

        .main-content { 
            flex: 1; 
            padding: 24px 30px; 
            overflow-y: auto; 
        }

        .container { 
            max-width: 1240px; 
            margin: 0 auto; 
            animation: fadeIn 0.35s ease-out; 
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ── Top Header Toolbar ── */
        .top-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--gm-border);
            flex-wrap: wrap;
            gap: 16px;
        }

        .header-identity {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: var(--gm-primary);
            color: #f3efe6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            box-shadow: 0 4px 14px rgba(31, 107, 74, 0.25);
            flex-shrink: 0;
        }

        .header-identity h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--gm-primary);
            margin: 0;
            letter-spacing: -0.3px;
        }

        .header-identity p {
            color: var(--gm-text-muted);
            font-size: 0.84rem;
            font-weight: 600;
            margin-top: 2px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-modern {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px 18px;
            border-radius: 10px;
            font-size: 0.84rem;
            font-weight: 700;
            border: 1.5px solid var(--gm-border);
            background: #ffffff;
            color: var(--gm-primary);
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 38px;
            white-space: nowrap;
        }

        .btn-modern:hover {
            background: var(--gm-primary-light);
            border-color: var(--gm-primary);
            transform: translateY(-1px);
        }

        .btn-modern.btn-primary {
            background: var(--gm-primary);
            color: #f3efe6;
            border-color: var(--gm-primary);
            box-shadow: 0 4px 12px rgba(31, 107, 74, 0.25);
        }

        .btn-modern.btn-primary:hover {
            background: var(--gm-primary-dark);
            color: #ffffff;
        }

        /* ── Hero Live Duty Cockpit ── */
        .duty-cockpit-hero {
            background: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%);
            color: #f3efe6;
            border-radius: 20px;
            padding: 28px 32px;
            margin-bottom: 24px;
            box-shadow: 0 14px 40px rgba(31, 107, 74, 0.25);
            position: relative;
            overflow: hidden;
            border: 2px solid rgba(243, 239, 230, 0.25);
        }

        .cockpit-bg-glow {
            position: absolute;
            right: -60px;
            top: -60px;
            width: 260px;
            height: 260px;
            background: radial-gradient(circle, rgba(243, 239, 230, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .cockpit-top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 22px;
            position: relative;
            z-index: 1;
        }

        .nurse-profile-unit {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .nurse-avatar-box {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            background: rgba(243, 239, 230, 0.18);
            border: 2px solid rgba(243, 239, 230, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            color: #f3efe6;
            box-shadow: 0 6px 16px rgba(0,0,0,0.15);
            flex-shrink: 0;
        }

        .nurse-meta-text h2 {
            font-size: 1.45rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 4px 0;
            letter-spacing: -0.2px;
        }

        .nurse-role-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 10px;
            border-radius: 20px;
            background: rgba(243, 239, 230, 0.2);
            font-size: 0.76rem;
            font-weight: 800;
            color: #f3efe6;
            border: 1px solid rgba(243, 239, 230, 0.35);
        }

        .shift-badge-frosted {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(243, 239, 230, 0.18);
            backdrop-filter: blur(14px);
            border: 1.5px solid rgba(243, 239, 230, 0.4);
            padding: 10px 22px;
            border-radius: 40px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.14);
        }

        .shift-badge-frosted i {
            font-size: 1.35rem;
            color: #f3efe6;
        }

        .shift-badge-text {
            display: flex;
            flex-direction: column;
        }

        .shift-type-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.1;
        }

        .shift-time-sub {
            font-size: 0.74rem;
            color: rgba(243, 239, 230, 0.85);
            font-weight: 600;
            margin-top: 2px;
        }

        /* Cockpit Details Strip */
        .cockpit-info-strip {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            padding-top: 18px;
            border-top: 1.5px solid rgba(243, 239, 230, 0.2);
            position: relative;
            z-index: 1;
        }

        .c-stat-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .c-stat-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(243, 239, 230, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            color: #f3efe6;
            flex-shrink: 0;
        }

        .c-stat-text {
            display: flex;
            flex-direction: column;
        }

        .c-stat-lbl {
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: rgba(243, 239, 230, 0.75);
        }

        .c-stat-val {
            font-size: 0.92rem;
            font-weight: 800;
            color: #ffffff;
        }

        /* ── Real-Time Metrics Strip ── */
        .metrics-strip {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 16px;
            margin-bottom: 26px;
        }

        .metric-card {
            background: #ffffff;
            border: 1.5px solid var(--gm-border);
            border-radius: 14px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-subtle);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .metric-card:hover {
            border-color: var(--gm-primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-elevated);
        }

        .metric-info {
            display: flex;
            flex-direction: column;
        }

        .metric-val {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--gm-primary);
            line-height: 1.1;
        }

        .metric-label {
            font-size: 0.76rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gm-text-muted);
            margin-top: 3px;
        }

        .metric-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--gm-primary-light);
            color: var(--gm-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        /* ── Bento Dashboard Grid ── */
        .bento-grid-dashboard {
            display: grid;
            grid-template-columns: 1.7fr 1.1fr;
            gap: 24px;
            margin-bottom: 26px;
        }

        @media (max-width: 980px) {
            .bento-grid-dashboard {
                grid-template-columns: 1fr;
            }
        }

        .bento-card {
            background: #ffffff;
            border: 1.5px solid var(--gm-border);
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--shadow-subtle);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .bento-card:hover {
            border-color: var(--gm-primary);
            box-shadow: var(--shadow-elevated);
        }

        .bento-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1.5px solid var(--gm-border);
        }

        .bento-header-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .bento-header-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: var(--gm-primary-light);
            color: var(--gm-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .bento-header h3 {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--gm-primary);
            margin: 0;
        }

        /* Details Grid */
        .spec-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .spec-cell {
            background: var(--gm-bg);
            padding: 13px 16px;
            border-radius: 12px;
            border: 1px solid var(--gm-border);
            border-left: 4px solid var(--gm-primary);
            transition: all 0.15s ease;
        }

        .spec-cell:hover {
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(31, 107, 74, 0.06);
        }

        .spec-lbl {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gm-text-muted);
            margin-bottom: 3px;
        }

        .spec-val {
            font-size: 0.94rem;
            font-weight: 800;
            color: var(--gm-primary);
        }

        /* Assigned Beds Grid */
        .bed-matrix-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
            gap: 10px;
        }

        .bed-matrix-card {
            background: var(--gm-bg);
            border: 1.5px solid var(--gm-border);
            border-radius: 12px;
            padding: 12px 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            text-align: center;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            text-decoration: none;
            color: var(--gm-primary);
        }

        .bed-matrix-card i {
            font-size: 1.2rem;
            color: var(--gm-primary);
        }

        .bed-matrix-card span {
            font-size: 0.85rem;
            font-weight: 800;
        }

        .bed-matrix-card:hover {
            background: var(--gm-primary);
            color: #f3efe6;
            border-color: var(--gm-primary);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(31, 107, 74, 0.22);
        }

        .bed-matrix-card:hover i {
            color: #f3efe6;
        }

        /* ── Upcoming Schedule Timeline ── */
        .upcoming-timeline {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .timeline-shift-card {
            background: var(--gm-bg);
            border: 1.5px solid var(--gm-border);
            border-radius: 12px;
            padding: 16px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .timeline-shift-card:hover {
            background: #ffffff;
            border-color: var(--gm-primary);
            transform: translateX(4px);
            box-shadow: 0 6px 18px rgba(31, 107, 74, 0.08);
        }

        .tsc-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .tsc-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--gm-primary);
            color: #f3efe6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .tsc-details h4 {
            font-size: 0.94rem;
            font-weight: 800;
            color: var(--gm-primary);
            margin: 0 0 2px 0;
        }

        .tsc-details p {
            font-size: 0.78rem;
            color: var(--gm-text-muted);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            margin: 0;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.74rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .status-active    { background: var(--gm-primary); color: #f3efe6; box-shadow: 0 4px 10px rgba(31, 107, 74, 0.25); }
        .status-scheduled { background: var(--gm-primary-light); color: var(--gm-primary); border: 1px solid var(--gm-border); }
        .status-completed { background: rgba(100, 116, 139, 0.12); color: #475569; }

        /* ── Fast Navigation Dock ── */
        .quick-nav-dock {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
            margin-top: 10px;
        }

        .dock-btn {
            background: #ffffff;
            border: 1.5px solid var(--gm-border);
            border-radius: 14px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            color: var(--gm-primary);
            box-shadow: var(--shadow-subtle);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .dock-btn:hover {
            background: var(--gm-primary);
            color: #f3efe6;
            border-color: var(--gm-primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-elevated);
        }

        .dock-btn i {
            font-size: 1.4rem;
            color: var(--gm-primary);
            transition: color 0.2s;
        }

        .dock-btn:hover i {
            color: #f3efe6;
        }

        .dock-text h5 {
            font-size: 0.92rem;
            font-weight: 800;
            margin: 0;
        }

        .dock-text span {
            font-size: 0.74rem;
            color: var(--gm-text-muted);
            font-weight: 600;
            transition: color 0.2s;
        }

        .dock-btn:hover .dock-text span {
            color: rgba(243, 239, 230, 0.85);
        }

        /* ── Loading & Empty States ── */
        .cockpit-loading {
            background: #ffffff;
            border: 1.5px solid var(--gm-border);
            border-radius: 16px;
            padding: 60px 20px;
            text-align: center;
            color: var(--gm-primary);
            box-shadow: var(--shadow-subtle);
        }

        .cockpit-empty {
            background: #ffffff;
            border: 1.5px solid var(--gm-border);
            border-radius: 16px;
            padding: 50px 20px;
            text-align: center;
            color: var(--gm-primary);
        }

        .cockpit-empty i {
            font-size: 3.5rem;
            opacity: 0.35;
            margin-bottom: 14px;
            display: block;
        }

        /* ── Mobile Breakpoints ── */
        @media (max-width: 767px) {
            .main-content { padding: 14px; }
            .top-toolbar { flex-direction: column; align-items: flex-start; }
            .duty-cockpit-hero { padding: 20px; }
            .cockpit-top-row { flex-direction: column; align-items: flex-start; }
            .shift-badge-frosted { width: 100%; justify-content: center; }
            .spec-grid { grid-template-columns: 1fr; }
            .header-actions { width: 100%; }
            .header-actions .btn-modern { flex: 1; }
        }
    </style>
</head>
<body>
    <div class="main-layout">
        <!-- Sidebar Navigation -->
        <?php include 'includes/nurse_sidebar.php'; ?>

        <div class="content-wrapper">
            <!-- Top Navbar -->
            <?php include 'includes/nurse_navbar.php'; ?>

            <div class="main-content">
                <div class="container">
                    
                    <!-- Top Header Toolbar -->
                    <div class="top-toolbar">
                        <div class="header-identity">
                            <div class="brand-icon"><i class="fas fa-stethoscope"></i></div>
                            <div>
                                <h1>My Duty Shift Command Center</h1>
                                <p>Real-time inpatient care station, bed allocation & schedule manager.</p>
                            </div>
                        </div>
                        <div class="header-actions">
                            <a href="nurse_workspace.php" class="btn-modern btn-primary">
                                <i class="fas fa-user-injured"></i> Nurse Workspace
                            </a>
                            <a href="k_sheet_view.php" class="btn-modern">
                                <i class="fas fa-file-medical-alt"></i> Kardex K-Sheet
                            </a>
                        </div>
                    </div>

                    <!-- Dynamic Hero Cockpit Container -->
                    <div id="dutyCockpitContainer">
                        <div class="cockpit-loading">
                            <i class="fas fa-circle-notch fa-spin fa-3x"></i>
                            <p style="margin-top:16px; font-weight:700; font-size:1rem;">Connecting to GM Hospital Duty Dispatcher...</p>
                        </div>
                    </div>

                    <!-- Upcoming Rotation Schedule -->
                    <div class="bento-card" style="margin-top: 24px;">
                        <div class="bento-header">
                            <div class="bento-header-left">
                                <div class="bento-header-icon"><i class="fas fa-calendar-alt"></i></div>
                                <h3>Upcoming Shift Roster</h3>
                            </div>
                            <span style="font-size:0.76rem; font-weight:800; color:var(--gm-text-muted); text-transform:uppercase; letter-spacing:0.5px;">Hospital Scheduled Rotations</span>
                        </div>
                        <div id="upcomingShiftsList" class="upcoming-timeline">
                            <p style="color:var(--gm-text-muted); font-size:0.88rem; font-weight:600; text-align:center; padding:24px;">Loading upcoming schedule...</p>
                        </div>
                    </div>

                    <!-- Fast Navigation Action Dock -->
                    <div class="quick-nav-dock">
                        <a href="nurse_workspace.php" class="dock-btn">
                            <i class="fas fa-heartbeat"></i>
                            <div class="dock-text">
                                <h5>Inpatient Vitals & MAR</h5>
                                <span>Record clinical observations</span>
                            </div>
                        </a>
                        <a href="k_sheet_view.php" class="dock-btn">
                            <i class="fas fa-file-medical"></i>
                            <div class="dock-text">
                                <h5>Clinical Kardex (K-Sheet)</h5>
                                <span>19 full clinical flowsheets</span>
                            </div>
                        </a>
                        <a href="all_shift_assignments.php" class="dock-btn">
                            <i class="fas fa-calendar-week"></i>
                            <div class="dock-text">
                                <h5>Master Shift Roster</h5>
                                <span>All hospital nurse schedules</span>
                            </div>
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        function formatDate(dateStr) {
            if (!dateStr) return '—';
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
        }

        function daysBetween(from, to) {
            if (!from || !to) return 0;
            const d1 = new Date(from), d2 = new Date(to);
            return Math.round((d2 - d1) / (1000 * 60 * 60 * 24)) + 1;
        }

        function shiftTime(type) {
            const times = {
                'Morning': '6:00 AM – 2:00 PM (Morning Duty)',
                'Evening': '2:00 PM – 10:00 PM (Evening Duty)',
                'Night':   '10:00 PM – 6:00 AM (Night Duty)',
                'Week Off': 'Off Duty / Weekly Rest'
            };
            return times[type] || type || '—';
        }

        function shiftIcon(type) {
            const t = (type || '').toLowerCase();
            if (t.includes('morning')) return 'fa-sun';
            if (t.includes('evening')) return 'fa-cloud-sun';
            if (t.includes('night')) return 'fa-moon';
            if (t.includes('off')) return 'fa-coffee';
            return 'fa-clock';
        }

        function statusClass(status) {
            if (!status) return 'status-scheduled';
            const s = status.toLowerCase();
            if (s === 'active')    return 'status-active';
            if (s === 'scheduled') return 'status-scheduled';
            return 'status-completed';
        }

        async function loadShiftDashboard() {
            try {
                const response = await fetch('api/dashboard.php');
                const result = await response.json();

                if (!result.success) throw new Error(result.message || 'API error');

                const data = result.data;
                const shift = data.current_shift;
                const container = document.getElementById('dutyCockpitContainer');

                if (shift) {
                    const days = daysBetween(shift.shift_date_from, shift.shift_date_to);

                    // Assigned Beds
                    let bedsHtml = '<span style="color:var(--gm-text-muted);font-size:0.84rem;font-weight:600;padding:12px 0;">No specific individual beds assigned. Full ward duty.</span>';
                    if (shift.assigned_beds) {
                        const beds = shift.assigned_beds.split(',').map(b => b.trim()).filter(Boolean);
                        bedsHtml = beds.map(b =>
                            `<a href="nurse_workspace.php" class="bed-matrix-card" title="Click to view bed in workspace">
                                <i class="fas fa-bed"></i>
                                <span>Bed ${b}</span>
                            </a>`
                        ).join('');
                    }

                    // Metrics
                    const ptCount = Array.isArray(data.assigned_patients) ? data.assigned_patients.length : 0;
                    const vitalsCount = data.statistics?.vitals?.total_readings_today || data.recent_vitals?.length || 0;
                    const overdueCount = Array.isArray(data.overdue_medications) ? data.overdue_medications.length : 0;
                    const abnormalCount = Array.isArray(data.abnormal_vitals) ? data.abnormal_vitals.length : 0;

                    container.innerHTML = `
                        <!-- Hero Cockpit -->
                        <div class="duty-cockpit-hero">
                            <div class="cockpit-bg-glow"></div>
                            
                            <div class="cockpit-top-row">
                                <div class="nurse-profile-unit">
                                    <div class="nurse-avatar-box"><i class="fas fa-user-nurse"></i></div>
                                    <div class="nurse-meta-text">
                                        <h2><?php echo htmlspecialchars($nurseName); ?></h2>
                                        <div class="nurse-role-pill">
                                            <i class="fas fa-shield-alt"></i> <?php echo htmlspecialchars($nurseRole); ?> &bull; Active Duty Station
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="shift-badge-frosted">
                                    <i class="fas ${shiftIcon(shift.shift_type)}"></i>
                                    <div class="shift-badge-text">
                                        <span class="shift-type-title">${shift.shift_type || 'Active'} Shift</span>
                                        <span class="shift-time-sub">${shiftTime(shift.shift_type)}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="cockpit-info-strip">
                                <div class="c-stat-item">
                                    <div class="c-stat-icon"><i class="fas fa-calendar-day"></i></div>
                                    <div class="c-stat-text">
                                        <span class="c-stat-lbl">Active Date Range</span>
                                        <span class="c-stat-val">${formatDate(shift.shift_date_from)} → ${formatDate(shift.shift_date_to)}</span>
                                    </div>
                                </div>
                                <div class="c-stat-item">
                                    <div class="c-stat-icon"><i class="fas fa-hourglass-half"></i></div>
                                    <div class="c-stat-text">
                                        <span class="c-stat-lbl">Rotation Duration</span>
                                        <span class="c-stat-val">${days} Day${days !== 1 ? 's' : ''} Assignment</span>
                                    </div>
                                </div>
                                <div class="c-stat-item">
                                    <div class="c-stat-icon"><i class="fas fa-hospital-alt"></i></div>
                                    <div class="c-stat-text">
                                        <span class="c-stat-lbl">Assigned Ward</span>
                                        <span class="c-stat-val">${shift.ward_name || 'General Ward'}</span>
                                    </div>
                                </div>
                                <div class="c-stat-item">
                                    <div class="c-stat-icon"><i class="fas fa-layer-group"></i></div>
                                    <div class="c-stat-text">
                                        <span class="c-stat-lbl">Floor Location</span>
                                        <span class="c-stat-val">${shift.floor_name || 'Ground Floor'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Real-Time Metrics Strip -->
                        <div class="metrics-strip">
                            <div class="metric-card">
                                <div class="metric-info">
                                    <span class="metric-val">${ptCount}</span>
                                    <span class="metric-label">Assigned Patients</span>
                                </div>
                                <div class="metric-icon-box"><i class="fas fa-users"></i></div>
                            </div>
                            <div class="metric-card">
                                <div class="metric-info">
                                    <span class="metric-val">${vitalsCount}</span>
                                    <span class="metric-label">Vitals Logged Today</span>
                                </div>
                                <div class="metric-icon-box"><i class="fas fa-heartbeat"></i></div>
                            </div>
                            <div class="metric-card">
                                <div class="metric-info">
                                    <span class="metric-val" style="color:${overdueCount > 0 ? '#dc2626' : 'var(--gm-primary)'};">${overdueCount}</span>
                                    <span class="metric-label">Overdue Medications</span>
                                </div>
                                <div class="metric-icon-box" style="${overdueCount > 0 ? 'background:rgba(220,38,38,0.1);color:#dc2626;' : ''}"><i class="fas fa-pills"></i></div>
                            </div>
                            <div class="metric-card">
                                <div class="metric-info">
                                    <span class="metric-val" style="color:${abnormalCount > 0 ? '#dc2626' : 'var(--gm-primary)'};">${abnormalCount}</span>
                                    <span class="metric-label">Abnormal Alerts</span>
                                </div>
                                <div class="metric-icon-box" style="${abnormalCount > 0 ? 'background:rgba(220,38,38,0.1);color:#dc2626;' : ''}"><i class="fas fa-exclamation-triangle"></i></div>
                            </div>
                        </div>

                        <!-- Bento Grid Dashboard -->
                        <div class="bento-grid-dashboard">
                            <!-- Left: Shift Specifications -->
                            <div class="bento-card">
                                <div class="bento-header">
                                    <div class="bento-header-left">
                                        <div class="bento-header-icon"><i class="fas fa-info-circle"></i></div>
                                        <h3>Duty Station Specifications</h3>
                                    </div>
                                    <span class="status-badge ${statusClass(shift.status)}">
                                        <i class="fas fa-circle" style="font-size:6px;"></i> ${shift.status || 'Active Duty'}
                                    </span>
                                </div>
                                <div class="spec-grid">
                                    <div class="spec-cell">
                                        <div class="spec-lbl"><i class="far fa-calendar-alt"></i> Roster Start Date</div>
                                        <div class="spec-val">${formatDate(shift.shift_date_from)}</div>
                                    </div>
                                    <div class="spec-cell">
                                        <div class="spec-lbl"><i class="far fa-calendar-check"></i> Roster End Date</div>
                                        <div class="spec-val">${formatDate(shift.shift_date_to)}</div>
                                    </div>
                                    <div class="spec-cell">
                                        <div class="spec-lbl"><i class="fas fa-clock"></i> Duty Hours</div>
                                        <div class="spec-val">${shiftTime(shift.shift_type)}</div>
                                    </div>
                                    <div class="spec-cell">
                                        <div class="spec-lbl"><i class="fas fa-hourglass-start"></i> Days Active</div>
                                        <div class="spec-val">${days} Days Period</div>
                                    </div>
                                    <div class="spec-cell" style="grid-column: 1 / -1;">
                                        <div class="spec-lbl"><i class="fas fa-map-marked-alt"></i> Ward & Clinical Work Area</div>
                                        <div class="spec-val">
                                            ${shift.ward_name || 'General Inpatient Ward'} 
                                            <span style="color:var(--gm-text-muted); font-size:0.85rem; font-weight:600; margin-left:6px;">
                                                (${shift.floor_name || 'Floor not set'}${shift.work_area ? ' &bull; ' + shift.work_area : ''})
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Bed Allocation Matrix -->
                            <div class="bento-card">
                                <div class="bento-header">
                                    <div class="bento-header-left">
                                        <div class="bento-header-icon"><i class="fas fa-procedures"></i></div>
                                        <h3>Assigned Bed Matrix</h3>
                                    </div>
                                    <span style="font-size:0.75rem; font-weight:800; color:var(--gm-primary); background:var(--gm-primary-light); padding:3px 8px; border-radius:6px; border:1px solid var(--gm-border);">Live Beds</span>
                                </div>
                                <div class="bed-matrix-grid">
                                    ${bedsHtml}
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    container.innerHTML = `
                        <div class="cockpit-empty">
                            <i class="fas fa-calendar-times"></i>
                            <h3 style="font-size:1.3rem; font-weight:800; margin-bottom:6px;">No Active Duty Shift Assigned Today</h3>
                            <p style="color:var(--gm-text-muted); font-size:0.9rem; font-weight:600; max-width:480px; margin:0 auto 18px auto;">
                                You are currently off-duty or your scheduled shift roster has concluded. Please check upcoming rotations below or contact the Nursing Superintendent.
                            </p>
                            <a href="all_shift_assignments.php" class="btn-modern btn-primary">
                                <i class="fas fa-calendar-alt"></i> View Department Shift Schedule
                            </a>
                        </div>`;
                }

                renderUpcomingSchedule(data.upcoming_shifts);

            } catch (error) {
                console.error('Error fetching shift dashboard:', error);
                document.getElementById('dutyCockpitContainer').innerHTML = `
                    <div class="cockpit-empty">
                        <i class="fas fa-exclamation-circle" style="color:#dc2626;"></i>
                        <h3 style="color:#dc2626;">Unable to Retrieve Shift Roster</h3>
                        <p style="color:var(--gm-text-muted); font-size:0.88rem; font-weight:600;">Please verify your network connection or session authentication.</p>
                    </div>`;
            }
        }

        function renderUpcomingSchedule(shifts) {
            const listEl = document.getElementById('upcomingShiftsList');
            if (!shifts || shifts.length === 0) {
                listEl.innerHTML = '<p style="color:var(--gm-text-muted); font-size:0.88rem; font-weight:600; text-align:center; padding:24px;">No upcoming shifts found in the hospital roster.</p>';
                return;
            }

            listEl.innerHTML = shifts.map(s => `
                <div class="timeline-shift-card">
                    <div class="tsc-left">
                        <div class="tsc-icon"><i class="fas ${shiftIcon(s.shift_type)}"></i></div>
                        <div class="tsc-details">
                            <h4>${s.shift_type || 'General'} Shift &bull; ${s.ward_name || 'General Ward'}</h4>
                            <p>
                                <span><i class="far fa-calendar-alt"></i> ${formatDate(s.shift_date_from)} → ${formatDate(s.shift_date_to)}</span>
                                ${s.floor_name ? '<span>&bull;</span><span><i class="fas fa-layer-group"></i> ' + s.floor_name + '</span>' : ''}
                            </p>
                        </div>
                    </div>
                    <span class="status-badge ${statusClass(s.status)}">
                        <i class="fas fa-circle" style="font-size:6px;"></i> ${s.status || 'Scheduled'}
                    </span>
                </div>
            `).join('');
        }

        loadShiftDashboard();
    </script>
</body>
</html>