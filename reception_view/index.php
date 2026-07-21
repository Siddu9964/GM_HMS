<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Receptionist', 'admin', 'Admin'])) {
    header("Location: /GM_HMS/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Reception Dashboard - GM Hospital Management System. Manage patient registrations, appointments, and front desk operations.">
    <title>Reception Dashboard - GM HMS</title>

    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- GM Theme (CSS Variables & Global Resets) -->
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <!-- Reception Dashboard CSS -->
    <link rel="stylesheet" href="assets/css/reception_dashboard.css?v=<?= time() ?>">

    <style>
        /* ─── Dashboard-specific page enhancements ─── */

        /* Greeting card with subtle shimmer animation */
        @keyframes shimmer {
            0% { background-position: -400px 0; }
            100% { background-position: 400px 0; }
        }

        @keyframes countUp {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 0 0 rgba(31, 107, 74, 0.15); }
            50%       { box-shadow: 0 0 0 8px rgba(31, 107, 74, 0); }
        }

        @keyframes rotateDot {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }

        /* ─── Page body ─── */
        body {
            background: #f0ede6 !important;
        }

        /* ─── Reception content wrapper ─── */
        .reception-content {
            padding: 1.5rem 1.75rem 2rem;
            animation: fadeSlideIn 0.45s ease-out both;
        }

        /* ─── Page header: breadcrumb-style title bar ─── */
        .dash-page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .dash-page-header-left {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .dash-page-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: #1f6b4a;
            letter-spacing: -0.4px;
            margin: 0;
        }

        .dash-page-subtitle {
            font-size: 0.78rem;
            color: #64748b;
            font-weight: 500;
            margin: 0;
        }

        .dash-date-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(31, 107, 74, 0.08);
            color: #1f6b4a;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 30px;
            border: 1px solid rgba(31, 107, 74, 0.15);
        }

        .dash-live-dot {
            width: 7px;
            height: 7px;
            background: #22c55e;
            border-radius: 50%;
            animation: pulseGlow 2s ease-in-out infinite;
        }

        /* ─── Main dashboard grid ─── */
        .dashboard-main-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .dashboard-left-column {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            min-width: 0;
        }

        /* ─── Greeting card ─── */
        .greeting-card-compact {
            background: #f3efe6 !important;
            border-radius: 20px !important;
            padding: 1.75rem 2rem !important;
            margin-bottom: 0 !important;
            border: 1px solid rgba(31,107,74,0.1) !important;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.06) !important;
            position: relative !important;
            overflow: hidden !important;
            animation: fadeSlideIn 0.5s ease-out both;
        }

        .greeting-card-compact::before {
            content: '' !important;
            position: absolute !important;
            top: -60px !important;
            right: -60px !important;
            width: 220px !important;
            height: 220px !important;
            background: rgba(255,255,255,0.06) !important;
            border-radius: 50% !important;
            pointer-events: none !important;
        }

        .greeting-card-compact::after {
            content: '' !important;
            position: absolute !important;
            bottom: -40px !important;
            left: 30% !important;
            width: 140px !important;
            height: 140px !important;
            background: rgba(255,255,255,0.04) !important;
            border-radius: 50% !important;
            pointer-events: none !important;
        }

        .greeting-inner {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .greeting-title-compact {
            font-size: 1.5rem !important;
            font-weight: 800 !important;
            color: #1f6b4a !important;
            margin: 0 0 0.35rem !important;
            letter-spacing: -0.5px !important;
            line-height: 1.2 !important;
        }

        .greeting-date-compact {
            font-size: 0.82rem !important;
            color: #475569 !important;
            font-weight: 500 !important;
            margin: 0 !important;
        }

        .greeting-date-compact span {
            color: #1f6b4a !important;
            font-weight: 700 !important;
            background: rgba(31,107,74,0.1) !important;
            padding: 2px 10px !important;
            border-radius: 6px !important;
            display: inline-block !important;
        }

        .greeting-emoji-badge {
            font-size: 3rem;
            line-height: 1;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,0.2));
            animation: fadeSlideIn 0.6s ease-out 0.2s both;
            flex-shrink: 0;
        }

        /* ─── KPI cards grid ─── */
        .kpi-cards-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
        }

        .kpi-card {
            background: #ffffff !important;
            border-radius: 12px !important;
            padding: 0.75rem 0.9rem !important;
            border: 1px solid rgba(0,0,0,0.045) !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04) !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.7rem !important;
            transition: all 0.25s cubic-bezier(0.4,0,0.2,1) !important;
            cursor: default;
            animation: fadeSlideIn 0.5s ease-out both;
            min-width: 0 !important;
            position: relative !important;
            overflow: hidden !important;
        }

        .kpi-card:nth-child(1) { animation-delay: 0.05s; }
        .kpi-card:nth-child(2) { animation-delay: 0.10s; }
        .kpi-card:nth-child(3) { animation-delay: 0.15s; }

        .kpi-card:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 20px rgba(31,107,74,0.10) !important;
            border-color: rgba(31,107,74,0.18) !important;
        }

        .kpi-card::before,
        .kpi-card::after {
            display: none !important;
        }

        .kpi-icon-wrapper {
            width: 36px !important;
            height: 36px !important;
            border-radius: 9px !important;
            background: #f1f5f9 !important;
            color: #1f6b4a !important;
            font-size: 0.9rem !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            flex-shrink: 0 !important;
            transition: all 0.25s ease !important;
            position: static !important;
            transform: none !important;
            opacity: 1 !important;
        }

        .kpi-card:hover .kpi-icon-wrapper {
            background: #1f6b4a !important;
            color: #ffffff !important;
            transform: scale(1.06) !important;
        }

        .kpi-content-inline {
            display: flex !important;
            flex-direction: column !important;
            gap: 1px !important;
            min-width: 0 !important;
            flex: 1 !important;
        }

        .kpi-card-value {
            font-size: 1.3rem !important;
            font-weight: 800 !important;
            color: #1e293b !important;
            line-height: 1 !important;
            margin: 0 !important;
            letter-spacing: -0.3px !important;
            animation: countUp 0.5s ease-out both;
        }

        .kpi-card-label {
            font-size: 0.65rem !important;
            font-weight: 600 !important;
            color: #64748b !important;
            text-transform: uppercase !important;
            letter-spacing: 0.04em !important;
            margin: 0 !important;
            line-height: 1.3 !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Accent stripe on KPI cards */
        .kpi-card .kpi-stripe {
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 3px;
            background: linear-gradient(180deg, #1f6b4a, #22c55e);
            border-radius: 12px 0 0 12px;
        }

        /* ─── Quick Actions card ─── */
        .quick-actions-card {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid rgba(0,0,0,0.045);
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            overflow: hidden;
            animation: fadeSlideIn 0.5s ease-out 0.2s both;
        }

        .quick-actions-card .card-header {
            padding: 0.75rem 1.1rem;
            margin-bottom: 0;
            border-bottom: 1px solid #f1f5f9;
            background: #fafbfc;
        }

        .quick-actions-card .card-title {
            font-size: 0.82rem;
            font-weight: 700;
            color: #1f6b4a;
        }

        .quick-actions-card .card-body {
            padding: 0.9rem 1rem;
        }

        /* 2×3 icon grid */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.6rem;
        }

        .quick-action-btn {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.45rem !important;
            padding: 0.85rem 0.5rem !important;
            background: #f8fafc !important;
            border: 1.5px solid #e8edf2 !important;
            border-radius: 12px !important;
            color: #334155 !important;
            font-size: 0.7rem !important;
            font-weight: 600 !important;
            text-align: center !important;
            cursor: pointer;
            transition: all 0.22s cubic-bezier(0.4,0,0.2,1) !important;
            box-shadow: none !important;
            white-space: normal !important;
            line-height: 1.25 !important;
            height: auto !important;
            width: 100% !important;
        }

        .quick-action-btn i {
            width: 36px !important;
            height: 36px !important;
            border-radius: 10px !important;
            background: rgba(31,107,74,0.09) !important;
            color: #1f6b4a !important;
            font-size: 0.95rem !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: all 0.22s ease !important;
            transform: none !important;
            flex-shrink: 0 !important;
        }

        .quick-action-btn:hover {
            background: #1f6b4a !important;
            color: #ffffff !important;
            border-color: #1f6b4a !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 16px rgba(31,107,74,0.20) !important;
        }

        .quick-action-btn:hover i {
            background: rgba(255,255,255,0.18) !important;
            color: #ffffff !important;
            transform: scale(1.08) !important;
        }

        /* ─── Right column: Doctors Panel ─── */
        .dashboard-right-column {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .doctors-panel-compact {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid rgba(0,0,0,0.045);
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            max-height: 580px;
            overflow: hidden;
            animation: fadeSlideIn 0.5s ease-out 0.1s both;
            transition: box-shadow 0.25s ease;
        }

        .doctors-panel-compact:hover {
            box-shadow: 0 8px 32px rgba(31,107,74,0.08);
        }

        /* Doctors panel inner header */
        .doctors-panel-top {
            padding: 1.1rem 1.25rem 0.85rem;
            border-bottom: 1px solid #f1f5f9;
            background: #fafbfc;
            border-radius: 20px 20px 0 0;
            flex-shrink: 0;
        }

        .doctors-panel-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }

        .doctors-panel-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.88rem;
            font-weight: 700;
            color: #1f6b4a;
        }

        .doctors-panel-title i {
            font-size: 1rem;
            color: #1f6b4a;
        }

        .live-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(34,197,94,0.1);
            color: #15803d;
            font-size: 0.68rem;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: 20px;
            border: 1px solid rgba(34,197,94,0.2);
        }

        .live-badge-dot {
            width: 6px;
            height: 6px;
            background: #22c55e;
            border-radius: 50%;
            animation: pulseGlow 2s ease-in-out infinite;
        }

        .doctor-search-wrapper {
            position: relative;
            width: 100%;
        }

        .doctor-search-wrapper i {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.8rem;
            pointer-events: none;
        }

        .doctor-search-input {
            width: 100%;
            padding: 8px 12px 8px 32px;
            border-radius: 10px;
            border: 1.5px solid rgba(0,0,0,0.07);
            background: #f8fafc;
            font-size: 0.8rem;
            color: #334155;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: all 0.25s ease;
            box-sizing: border-box;
        }

        .doctor-search-input:focus {
            border-color: #1f6b4a;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(31,107,74,0.10);
        }

        .doctor-search-input::placeholder {
            color: #94a3b8;
        }

        .doctors-list {
            flex: 1;
            padding: 0.85rem;
            overflow-y: auto;
        }

        /* Doctor item styling */
        .doctor-item {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.75rem 0.9rem;
            margin-bottom: 0.5rem;
            background: #f8fafc;
            border-radius: 12px;
            border-left: 3px solid #1f6b4a;
            transition: all 0.22s ease;
            cursor: pointer;
            animation: fadeSlideIn 0.4s ease-out both;
        }

        .doctor-item:hover {
            background: #f0fdf4;
            transform: translateX(3px);
            border-left-color: #22c55e;
            box-shadow: 0 2px 10px rgba(31,107,74,0.08);
        }

        .doctor-item:last-child { margin-bottom: 0; }

        .doctor-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1f6b4a, #144d34);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(31,107,74,0.25);
        }

        .doctor-info { flex: 1; min-width: 0; }

        .doctor-name {
            font-weight: 600;
            font-size: 0.83rem;
            color: #1e293b;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .doctor-specialization {
            font-size: 0.72rem;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .doctor-specialization i {
            color: #1f6b4a;
            font-size: 0.68rem;
        }

        .loading-doctors {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.65rem;
            padding: 2rem;
            color: #94a3b8;
            font-size: 0.85rem;
        }

        .loading-doctors i { font-size: 1.1rem; }

        .no-doctors {
            text-align: center;
            padding: 2rem 1rem;
            color: #94a3b8;
        }

        .no-doctors i {
            font-size: 2.2rem;
            margin-bottom: 0.65rem;
            opacity: 0.45;
            display: block;
        }

        .no-doctors p {
            font-size: 0.83rem;
            margin: 0;
        }

        /* ─── Bottom row ─── */
        .bottom-row-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .bottom-row-grid .card {
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid rgba(0,0,0,0.045);
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            padding: 0;
            overflow: hidden;
            transition: box-shadow 0.25s ease, transform 0.25s ease;
            animation: fadeSlideIn 0.5s ease-out 0.25s both;
        }

        .bottom-row-grid .card:hover {
            box-shadow: 0 8px 28px rgba(31,107,74,0.08);
            transform: translateY(-2px);
        }

        .bottom-row-grid .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.35rem;
            border-bottom: 1px solid #f1f5f9;
            background: #fafbfc;
            margin-bottom: 0;
        }

        .bottom-row-grid .card-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: #1f6b4a;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .bottom-row-grid .card-title i {
            color: #1f6b4a;
            font-size: 0.9rem;
        }

        .bottom-row-grid .card-body {
            padding: 1.15rem 1.35rem;
            color: #334155;
        }

        .view-all-link {
            font-size: 0.75rem;
            font-weight: 600;
            color: #1f6b4a;
            text-decoration: none;
            padding: 4px 10px;
            border-radius: 20px;
            background: rgba(31,107,74,0.08);
            transition: all 0.2s ease;
        }

        .view-all-link:hover {
            background: rgba(31,107,74,0.15);
            color: #155638;
        }

        /* Activity list item */
        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.65rem 0;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s ease;
        }

        .activity-item:last-child { border-bottom: none; }

        .activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            flex-shrink: 0;
        }

        .activity-icon.green  { background: rgba(31,107,74,0.10); color: #1f6b4a; }
        .activity-icon.blue   { background: rgba(26,35,126,0.10); color: #1A237E; }
        .activity-icon.amber  { background: rgba(217,119,6,0.10); color: #D97706; }

        .activity-info { flex: 1; min-width: 0; }

        .activity-text {
            font-size: 0.8rem;
            color: #334155;
            font-weight: 500;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .activity-time {
            font-size: 0.7rem;
            color: #94a3b8;
            margin: 1px 0 0;
        }

        /* ─── Responsive ─── */
        @media (max-width: 1100px) {
            .dashboard-main-grid {
                grid-template-columns: 1fr;
            }
            .dashboard-right-column {
                flex-direction: row;
            }
            .doctors-panel-compact {
                flex: 1;
                max-height: 360px;
            }
            .kpi-cards-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
            .kpi-cards-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .bottom-row-grid {
                grid-template-columns: 1fr;
            }
            .dashboard-right-column {
                flex-direction: column;
            }
        }

        @media (max-width: 600px) {
            .reception-content { padding: 1rem; }
            .kpi-cards-grid { grid-template-columns: 1fr; }
            .greeting-title-compact { font-size: 1.1rem !important; }
            .greeting-emoji-badge { font-size: 2rem; }
            .dash-page-header { flex-direction: column; align-items: flex-start; gap: 0.5rem; }
            .quick-actions-grid { gap: 0.5rem; }
        }
    </style>
</head>
<body>
<div class="reception-layout">

    <!-- Sidebar -->
    <?php include 'includes/reception_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="reception-main-content">

        <!-- Top Navbar -->
        <?php include 'includes/reception_navbar.php'; ?>

        <!-- Dashboard Content -->
        <div class="reception-content">

            <!-- ── Page Header ── -->
            <div class="dash-page-header">
                <div class="dash-page-header-left">
                    <h1 class="dash-page-title">Reception Dashboard</h1>
                    <p class="dash-page-subtitle">Welcome back! Here's what's happening today.</p>
                </div>
                <div class="dash-date-pill">
                    <span class="dash-live-dot"></span>
                    <span id="current-date-pill">Loading...</span>
                </div>
            </div>

            <!-- ── Main Grid: Left Column + Right Column (Doctors) ── -->
            <div class="dashboard-main-grid">

                <!-- LEFT: Greeting + KPI Cards + Quick Actions -->
                <div class="dashboard-left-column">

                    <!-- Greeting Card -->
                    <div class="greeting-card-compact">
                        <div class="greeting-inner">
                            <div>
                                <h2 class="greeting-title-compact">
                                    Good <span id="greeting-time">Morning</span>,
                                    <?php echo htmlspecialchars($_SESSION['full_name']); ?>!
                                </h2>
                                <p class="greeting-date-compact">
                                    Today is <span id="current-date">Loading...</span>
                                </p>
                            </div>
                            <div class="greeting-emoji-badge">👋</div>
                        </div>
                    </div>

                    <!-- KPI Cards -->
                    <div class="kpi-cards-grid">

                        <!-- New Registrations -->
                        <div class="kpi-card">
                            <span class="kpi-stripe"></span>
                            <div class="kpi-icon-wrapper">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="kpi-content-inline">
                                <span class="kpi-card-value" id="kpi-registrations">0</span>
                                <span class="kpi-card-label">New Registrations</span>
                            </div>
                        </div>

                        <!-- OPD Waiting -->
                        <div class="kpi-card">
                            <span class="kpi-stripe" style="background: linear-gradient(180deg,#d97706,#f59e0b);"></span>
                            <div class="kpi-icon-wrapper" style="background: rgba(217,119,6,0.08) !important; color: #d97706 !important;">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="kpi-content-inline">
                                <span class="kpi-card-value" id="kpi-waiting">0</span>
                                <span class="kpi-card-label">OPD Waiting</span>
                            </div>
                        </div>

                        <!-- Active IPD Patients -->
                        <div class="kpi-card" onclick="window.location.href='ipd_management/public/index.php'" style="cursor: pointer;">
                            <span class="kpi-stripe" style="background: linear-gradient(180deg,#1A237E,#3949ab);"></span>
                            <div class="kpi-icon-wrapper" style="background: rgba(26,35,126,0.08) !important; color: #1A237E !important;">
                                <i class="fas fa-procedures"></i>
                            </div>
                            <div class="kpi-content-inline">
                                <span class="kpi-card-value" id="kpi-ipd">0</span>
                                <span class="kpi-card-label">Active IPD Patients</span>
                            </div>
                        </div>

                    </div><!-- /.kpi-cards-grid -->

                    <!-- Quick Actions -->
                    <div class="quick-actions-card">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fas fa-bolt"></i>
                                Quick Actions
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="quick-actions-grid">
                                <button onclick="openOldPatientSearch()" class="quick-action-btn" style="border-color: #1f6b4a; background: #f0fdf4;">
                                    <i class="fas fa-search" style="background: #1f6b4a; color: white;"></i>
                                    Old Patient Search
                                </button>
                                <button onclick="window.location.href='patient_registration.php'" class="quick-action-btn">
                                    <i class="fas fa-user-plus"></i>
                                    Register Patient
                                </button>
                                <button onclick="window.location.href='appointment_management.php'" class="quick-action-btn">
                                    <i class="fas fa-calendar-check"></i>
                                    Book Appointment
                                </button>
                                <button onclick="window.location.href='prescriptions.php'" class="quick-action-btn">
                                    <i class="fas fa-prescription"></i>
                                    View Prescription
                                </button>
                                <button onclick="window.location.href='billing.php'" class="quick-action-btn">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                    Create Invoice
                                </button>
                                <button onclick="window.location.href='opd_management.php'" class="quick-action-btn">
                                    <i class="fas fa-stethoscope"></i>
                                    OPD Management
                                </button>
                                <button onclick="window.location.href='doctor_availability.php'" class="quick-action-btn">
                                    <i class="fas fa-user-md"></i>
                                    Doctor Schedule
                                </button>
                            </div>
                        </div>
                    </div>

                </div><!-- /.dashboard-left-column -->

                <!-- RIGHT: Available Doctors Panel -->
                <div class="dashboard-right-column">
                    <div class="doctors-panel-compact">

                        <!-- Panel Header -->
                        <div class="doctors-panel-top">
                            <div class="doctors-panel-title-row">
                                <div class="doctors-panel-title">
                                    <i class="fas fa-user-md"></i>
                                    Available Doctors
                                </div>
                                <div class="live-badge">
                                    <span class="live-badge-dot"></span>
                                    Live
                                </div>
                            </div>
                            <!-- Search -->
                            <div class="doctor-search-wrapper">
                                <i class="fas fa-search"></i>
                                <input
                                    type="text"
                                    id="doctor-search-input"
                                    class="doctor-search-input"
                                    placeholder="Search by name or specialty…"
                                >
                            </div>
                        </div>

                        <!-- Doctors List -->
                        <div class="doctors-list" id="available-doctors-list">
                            <div class="loading-doctors">
                                <i class="fas fa-spinner fa-spin"></i>
                                <span>Loading doctors…</span>
                            </div>
                        </div>

                    </div>
                </div><!-- /.dashboard-right-column -->

            </div><!-- /.dashboard-main-grid -->

<!-- ── Bottom Row: Patient Flow Chart + Recent Activity ── -->
            <div class="bottom-row-grid">

                <!-- Patient Flow Chart -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-chart-line"></i>
                            Patient Flow (Weekly)
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="patientFlowChart" height="210"></canvas>
                    </div>
                </div>

                <!-- Recent Front Desk Activity -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-history"></i>
                            Recent Front Desk Activity
                        </div>
                        <a href="#" class="view-all-link">View All</a>
                    </div>
                    <div class="card-body" style="padding-top:0.75rem; padding-bottom:0.75rem;">
                        <div id="recent-activity-list">
                            <div style="text-align:center; padding:2rem; color:#94a3b8;">
                                <i class="fas fa-spinner fa-spin" style="font-size:1.75rem; margin-bottom:0.5rem; display:block;"></i>
                                <p style="font-size:0.82rem; margin:0; color:#94a3b8 !important;">Loading activity…</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /.bottom-row-grid -->

        </div><!-- /.reception-content -->
    </div><!-- /.reception-main-content -->
</div><!-- /.reception-layout -->

<!-- Premium Old Patient Search Modal -->
<div id="oldPatientModal" class="modal-overlay hidden" style="z-index: 1000; backdrop-filter: blur(8px); background: rgba(15, 23, 42, 0.6); position: fixed; top: 0; left: 0; right: 0; bottom: 0; display: flex; justify-content: center; align-items: center;">
    <!-- Backdrop click listener added directly to the overlay -->
    <div style="position: absolute; top:0; left:0; right:0; bottom:0;" onclick="closeOldPatientModal()"></div>
    
    <div class="modal-content" style="position: relative; width: 100%; max-width: 550px; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1px solid rgba(255,255,255,0.1); background: #ffffff; overflow: visible; transform: translateY(0); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);">
        
        <div style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); padding: 32px 32px 24px; text-align: center; position: relative; border-radius: 24px 24px 0 0;">
            <button onclick="closeOldPatientModal()" style="position: absolute; top: 20px; right: 20px; background: white; border: none; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #64748b; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.05); transition: all 0.2s;"><i class="fas fa-times"></i></button>
            
            <div style="width: 64px; height: 64px; background: #dcfce7; color: #16a34a; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 16px; box-shadow: 0 10px 25px rgba(22, 163, 74, 0.2); transform: rotate(-5deg);">
                <i class="fas fa-search"></i>
            </div>
            <h2 style="margin: 0 0 8px; font-size: 1.5rem; font-weight: 800; color: #0f172a; letter-spacing: -0.5px;">Find Existing Patient</h2>
            <p style="margin: 0; color: #64748b; font-size: 0.95rem;">Quickly locate patient records by name, phone, or ID</p>
        </div>
        
        <div class="modal-body" style="padding: 32px;">
            <div class="custom-search-container" style="position: relative; margin-bottom: 32px; text-align: left;">
                <label style="font-weight: 600; color: #334155; margin-bottom: 12px; display: block; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Search Patient</label>
                <div style="position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 1.1rem;"></i>
                    <input type="text" id="customPatientSearch" placeholder="Type name, phone, or ID..." style="width: 100%; height: 56px; border-radius: 16px; border: 2px solid #e2e8f0; padding: 0 48px; font-size: 1.05rem; background: #f8fafc; transition: all 0.2s ease; outline: none; color: #334155; font-weight: 500; box-sizing: border-box;" autocomplete="off">
                    <i class="fas fa-spinner fa-spin" id="searchLoadingIcon" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: #1f6b4a; display: none; font-size: 1.1rem;"></i>
                </div>
                
                <!-- Custom Dropdown Results -->
                <div id="searchResultsDropdown" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border: 1px solid #e2e8f0; margin-top: 8px; max-height: 280px; overflow-y: auto; display: none; z-index: 100;">
                    <!-- Results injected here -->
                </div>
            </div>
            
            <div style="display: flex; justify-content: center; gap: 16px;">
                <button type="button" onclick="closeOldPatientModal()" class="btn" style="padding: 12px 24px; border-radius: 12px; font-weight: 600; color: #64748b; background: #f1f5f9; border: none; transition: all 0.2s; cursor: pointer;">Cancel</button>
                <button type="button" onclick="goToPatientProfile()" id="btnGoToProfile" disabled style="padding: 12px 32px; border-radius: 12px; font-weight: 700; color: #ffffff; background: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%); border: none; cursor: pointer; transition: all 0.3s; box-shadow: 0 8px 20px rgba(31, 107, 74, 0.3); display: flex; align-items: center; gap: 10px; opacity: 0.5;">
                    View Profile <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    #customPatientSearch:focus {
        border-color: #1f6b4a !important;
        background: #ffffff !important;
        box-shadow: 0 0 0 4px rgba(31, 107, 74, 0.1) !important;
    }
    
    .search-result-item:hover {
        background: #f8fafc !important;
    }
    
    #btnGoToProfile:not(:disabled):hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 25px rgba(31, 107, 74, 0.4);
        opacity: 1 !important;
    }
    #btnGoToProfile:not(:disabled) {
        opacity: 1 !important;
    }
</style>

<!-- Select2 CSS & JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- ── Scripts ── -->
<script src="assets/js/reception_utils.js"></script>
<script src="assets/js/dashboard.js"></script>

<script>
    /* ── Greeting time & date ── */
    (function() {
        const hour = new Date().getHours();
        const timeEl = document.getElementById('greeting-time');
        const dateEl = document.getElementById('current-date');
        const pillEl = document.getElementById('current-date-pill');

        if (timeEl) {
            if (hour < 12)      timeEl.textContent = 'Morning';
            else if (hour < 17) timeEl.textContent = 'Afternoon';
            else                timeEl.textContent = 'Evening';
        }

        const now = new Date();
        const opts = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const shortOpts = { weekday: 'short', month: 'short', day: 'numeric' };
        const formatted = now.toLocaleDateString('en-US', opts);
        const short = now.toLocaleDateString('en-US', shortOpts);

        if (dateEl) dateEl.textContent = formatted;
        if (pillEl) pillEl.textContent = short;
    })();

        let searchTimeout;
        let selectedPatientId = null;
        
        function openOldPatientSearch() {
            document.getElementById('oldPatientModal').classList.remove('hidden');
            document.getElementById('customPatientSearch').value = '';
            document.getElementById('searchResultsDropdown').style.display = 'none';
            document.getElementById('btnGoToProfile').disabled = true;
            document.getElementById('btnGoToProfile').style.opacity = '0.5';
            selectedPatientId = null;
            
            setTimeout(() => {
                document.getElementById('customPatientSearch').focus();
            }, 100);
        }

        function closeOldPatientModal() {
            document.getElementById('oldPatientModal').classList.add('hidden');
            document.getElementById('searchResultsDropdown').style.display = 'none';
        }

        document.getElementById('customPatientSearch').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            const resultsDropdown = document.getElementById('searchResultsDropdown');
            const loadingIcon = document.getElementById('searchLoadingIcon');
            const btnGoToProfile = document.getElementById('btnGoToProfile');
            
            selectedPatientId = null;
            btnGoToProfile.disabled = true;
            btnGoToProfile.style.opacity = '0.5';
            
            if (query.length < 2) {
                resultsDropdown.style.display = 'none';
                return;
            }
            
            loadingIcon.style.display = 'block';
            
            searchTimeout = setTimeout(() => {
                fetch(`/GM_HMS/api/patients?search=${encodeURIComponent(query)}&limit=10`)
                    .then(res => res.json())
                    .then(res => {
                        loadingIcon.style.display = 'none';
                        const patients = res.data?.data || res.data || [];
                        
                        if (patients.length === 0) {
                            resultsDropdown.innerHTML = `<div style="padding: 24px; text-align: center; color: #94a3b8;"><i class="fas fa-search mb-2" style="font-size: 24px; opacity: 0.5; display:block;"></i>No patients found</div>`;
                        } else {
                            resultsDropdown.innerHTML = patients.map(p => `
                                <div class="search-result-item" onclick="selectCustomPatient('${p.patient_id}', '${p.full_name.replace(/'/g, "\\'")}')" style="padding: 12px 16px; border-bottom: 1px solid #f1f5f9; cursor: pointer; display: flex; align-items: center; gap: 14px; transition: background 0.2s;">
                                    <div style="width: 44px; height: 44px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.1rem; flex-shrink: 0;">
                                        ${p.full_name.charAt(0).toUpperCase()}
                                    </div>
                                    <div style="min-width: 0; flex: 1;">
                                        <div style="font-weight: 600; color: #1e293b; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${p.full_name}</div>
                                        <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;"><span style="color:#1f6b4a; font-weight: 500;">${p.patient_id}</span> • <i class="fas fa-phone-alt" style="font-size:0.7rem;"></i> ${p.phone || 'N/A'}</div>
                                    </div>
                                </div>
                            `).join('');
                        }
                        resultsDropdown.style.display = 'block';
                    })
                    .catch(err => {
                        loadingIcon.style.display = 'none';
                    });
            }, 300);
        });

        // Hide dropdown on click outside
        document.addEventListener('click', (e) => {
            const searchInput = document.getElementById('customPatientSearch');
            const resultsDropdown = document.getElementById('searchResultsDropdown');
            if (searchInput && resultsDropdown && !searchInput.contains(e.target) && !resultsDropdown.contains(e.target)) {
                resultsDropdown.style.display = 'none';
            }
        });

        function selectCustomPatient(id, name) {
            selectedPatientId = id;
            document.getElementById('customPatientSearch').value = name;
            document.getElementById('searchResultsDropdown').style.display = 'none';
            const btn = document.getElementById('btnGoToProfile');
            btn.disabled = false;
            btn.style.opacity = '1';
        }

        function goToPatientProfile() {
            if (selectedPatientId) {
                sessionStorage.setItem('currentPatientId', selectedPatientId);
                window.location.href = `patient_profile.php`;
            }
        }
</script>
</body>
</html>
