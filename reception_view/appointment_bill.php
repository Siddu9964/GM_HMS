<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Receptionist', 'admin', 'Admin'])) {
    header("Location: ../receptionist_login.php");
    exit();
}
$pageTitle = 'Registration / Appointment Billing';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Billing — GM HMS</title>
    <link rel="icon" href="data:,">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/reception_dashboard.css">
    <style>
        /* ═══════════════════════════════════════════════
           APPOINTMENT BILLING — Page Styles
           ═══════════════════════════════════════════════ */
        :root {
            --primary:      #1f6b4a;
            --primary-dark: #144d34;
            --primary-soft: rgba(31, 107, 74, 0.1);
            --secondary:    #2a8c62;
            --success:      #10b981;
            --warning:      #f59e0b;
            --danger:       #ef4444;
            --bg-gray:      #f8fafc;
            --text-dark:    #1e293b;
            --text-muted:   #64748b;
            --glass-bg:     rgba(255, 255, 255, 0.95);
            --glass-border: rgba(31, 107, 74, 0.1);
            --radius-xl:    20px;
            --radius-lg:    12px;
            --radius-md:    8px;
            --shadow-sm:    0 2px 4px rgba(0,0,0,0.02);
            --shadow-md:    0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
            --shadow-lg:    0 10px 25px -3px rgba(31, 107, 74, 0.1), 0 4px 6px -2px rgba(31, 107, 74, 0.05);
            --shadow-glow:  0 0 15px rgba(31, 107, 74, 0.2);
            --transition:   all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Page wrapper */
        .ab-page {
            padding: 1.5rem 2rem;
            max-width: 1400px;
            margin: 0 auto;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ── Header Area ── */
        .ab-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .header-main {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .header-icon-box {
            width: 3.5rem;
            height: 3.5rem;
            border-radius: var(--radius-lg);
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .header-icon-box::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            animation: pulse 4s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.1); opacity: 0.5; }
        }

        .header-text h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-dark);
            margin: 0;
            letter-spacing: -0.02em;
        }

        .header-text p {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 0.2rem;
        }

        /* ── stats Row ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .stat-icon {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 50%;
            background: var(--primary-soft);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .stat-info .value {
            display: block;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .stat-info .label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.05em;
        }

        /* ── Layout ── */
        .ab-layout {
            display: grid;
            grid-template-columns: 420px 1fr;
            gap: 1.5rem;
            align-items: start;
        }

        @media (max-width: 1024px) {
            .ab-layout { grid-template-columns: 1fr; }
        }

        /* ── Cards ── */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            transition: var(--transition);
        }

        .glass-card:hover {
            box-shadow: var(--shadow-lg);
        }

        .card-header-premium {
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid var(--glass-border);
            background: linear-gradient(to right, rgba(31, 107, 74, 0.05), transparent);
        }

        .card-header-premium i {
            color: var(--primary);
            font-size: 1.1rem;
        }

        .card-header-premium h3 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0;
            letter-spacing: -0.01em;
        }

        .card-body-premium {
            padding: 1.25rem;
        }

        /* ── Search Component ── */
        .search-group {
            background: var(--bg-gray);
            border-radius: var(--radius-lg);
            padding: 0.5rem;
            display: flex;
            gap: 0.25rem;
            margin-bottom: 1.25rem;
        }

        .search-type-btn {
            flex: 1;
            border: none;
            padding: 0.6rem 0.5rem;
            background: transparent;
            color: var(--text-muted);
            font-size: 0.8rem;
            font-weight: 600;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }

        .search-type-btn.active {
            background: white;
            color: var(--primary);
            box-shadow: var(--shadow-sm);
        }

        .input-container {
            position: relative;
            margin-bottom: 1rem;
        }

        .input-container i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .input-premium {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.75rem;
            border: 2px solid transparent;
            background: var(--bg-gray);
            border-radius: var(--radius-lg);
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-dark);
            outline: none;
            transition: var(--transition);
        }

        .input-premium:focus {
            border-color: var(--primary);
            background: white;
            box-shadow: var(--shadow-glow);
        }

        .input-premium:focus + i {
            transform: translateY(-50%) scale(1.1);
        }

        .btn-search {
            width: 100%;
            padding: 0.85rem;
            border: none;
            border-radius: var(--radius-lg);
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            transition: var(--transition);
            box-shadow: var(--shadow-md);
        }

        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(31, 107, 74, 0.3);
            filter: brightness(1.05);
        }

        /* ── Result Items ── */
        .result-scroll {
            margin-top: 1.5rem;
            max-height: 400px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .result-scroll::-webkit-scrollbar { width: 5px; }
        .result-scroll::-webkit-scrollbar-track { background: var(--bg-gray); }
        .result-scroll::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 10px; }

        .patient-result-card {
            padding: 1rem;
            background: var(--bg-gray);
            border-radius: var(--radius-lg);
            margin-bottom: 0.75rem;
            border: 2px solid transparent;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .patient-result-card:hover, .patient-result-card.selected {
            background: white;
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
            transform: translateX(5px);
        }

        .patient-avatar {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            background: var(--primary-soft);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.1rem;
            flex-shrink: 0;
            border: 2px solid white;
            box-shadow: var(--shadow-sm);
        }

        .patient-meta {
            flex: 1;
            min-width: 0;
        }

        .patient-meta h4 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .patient-meta p {
            margin: 0.15rem 0 0;
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .status-badge {
            font-size: 0.65rem;
            font-weight: 800;
            padding: 0.25rem 0.6rem;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge-new { background: #dcfce7; color: #15803d; }
        .badge-returning { background: #e0f2fe; color: #0369a1; }

        /* ── Bill Summary ── */
        .billing-grid {
            margin-top: 1rem;
        }

        .bill-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px dashed var(--gray-200);
        }

        .bill-item:last-child { border-bottom: none; }

        .bill-label {
            font-size: 0.9rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .bill-value {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .total-row {
            background: var(--primary-soft);
            padding: 1.25rem;
            border-radius: var(--radius-lg);
            margin-top: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .total-label {
            font-size: 1rem;
            font-weight: 800;
            color: var(--primary-dark);
        }

        .total-amount {
            font-size: 1.5rem;
            font-weight: 900;
            color: var(--primary);
        }

        /* ── Payment Modes ── */
        .payment-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .pay-option {
            padding: 1rem;
            background: var(--bg-gray);
            border: 2px solid transparent;
            border-radius: var(--radius-lg);
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
        }

        .pay-option i {
            display: block;
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--text-muted);
        }

        .pay-option span {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-muted);
        }

        .pay-option.active {
            background: white;
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
        }

        .pay-option.active i, .pay-option.active span {
            color: var(--primary);
        }

        /* ── Action Buttons ── */
        .action-flex {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-premium {
            flex: 1;
            padding: 1rem;
            border-radius: var(--radius-lg);
            border: none;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .btn-primary-premium {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 8px 20px rgba(31, 107, 74, 0.2);
        }

        .btn-success-premium {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.2);
        }

        .btn-secondary-premium {
            background: white;
            color: var(--text-dark);
            border: 2px solid var(--gray-200);
        }

        .btn-premium:hover {
            transform: translateY(-3px);
            filter: brightness(1.05);
        }

        /* ── Table Styling ── */
        .recent-table-container {
            margin-top: 1rem;
            border-radius: var(--radius-lg);
            overflow: auto;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border: 1px solid var(--glass-border);
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
        }

        .modern-table th {
            background: rgba(31, 107, 74, 0.03);
            padding: 1rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            color: var(--primary-dark);
            letter-spacing: 0.05em;
        }

        .modern-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--bg-gray);
            font-size: 0.85rem;
            color: var(--text-dark);
            font-weight: 500;
        }

        .modern-table tr:last-child td { border-bottom: none; }

        .modern-table tr:hover td { background: rgba(31, 107, 74, 0.01); }

        /* Modal Preview */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(8px);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background: white;
            width: 100%;
            max-width: 800px;
            max-height: 90vh;
            border-radius: var(--radius-xl);
            overflow-y: auto;
            position: relative;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .modal-close {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background: var(--bg-gray);
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .modal-close:hover {
            background: var(--danger);
            color: white;
            transform: rotate(90deg);
        }

        /* Print styles */
        @media print {
            .reception-sidebar, .reception-navbar, #searchCol, .ab-header, .stats-grid, .modal-close, .btn-premium {
                display: none !important;
            }
            .ab-page { padding: 0; margin: 0; }
            .modal { position: absolute; background: white; padding: 0; }
            .modal-content { box-shadow: none; width: 100%; max-width: 100%; }
        }
    </style>

</head>
<body>
<div class="reception-layout">
    <?php include 'includes/reception_sidebar.php'; ?>

    <div class="reception-main-content">
        <?php
$pageTitle = 'Registration / Appointment Billing';
include 'includes/reception_navbar.php';
?>

        <main class="reception-content">
            <div class="ab-page">

                <!-- ── Header Area ── -->
                <div class="ab-header">
                    <div class="header-main">
                        <div class="header-icon-box">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <div class="header-text">
                            <h1>Billing Terminal</h1>
                            <p>Appointment & Registration Management</p>
                        </div>
                    </div>
                </div>

                <!-- ── Stats Summary ── -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                        <div class="stat-info">
                            <span class="value" id="statTodayCount">0</span>
                            <span class="label">Today's Bills</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                        <div class="stat-info">
                            <span class="value" id="statTodayAmount">₹0.00</span>
                            <span class="label">Today's Revenue</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-info">
                            <span class="value" id="statPendingCount">0</span>
                            <span class="label">Pending Bills</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
                        <div class="stat-info">
                            <span class="value" id="statNewPatients">0</span>
                            <span class="label">New Registrations</span>
                        </div>
                    </div>
                </div>

                <div class="ab-layout">

                    <!-- ═══ LEFT: Search Terminal ═══ -->
                    <div id="searchCol">
                        <div class="glass-card">
                            <div class="card-header-premium">
                                <i class="fas fa-fingerprint"></i>
                                <h3>Patient Identification</h3>
                            </div>
                            <div class="card-body-premium">
                                <div class="search-group">
                                    <button class="search-type-btn active" data-type="name" onclick="switchSearchType(this, 'name')">
                                        <i class="fas fa-user"></i> Name
                                    </button>
                                    <button class="search-type-btn" data-type="id" onclick="switchSearchType(this, 'id')">
                                        <i class="fas fa-hashtag"></i> ID
                                    </button>
                                    <button class="search-type-btn" data-type="phone" onclick="switchSearchType(this, 'phone')">
                                        <i class="fas fa-phone"></i> Phone
                                    </button>
                                </div>

                                <div class="input-container">
                                    <input type="text" id="searchInput" class="input-premium" placeholder="Search by name..." autocomplete="off">
                                    <i class="fas fa-search" id="searchSpinner"></i>
                                </div>

                                <button class="btn-search" onclick="performFinalSearch()">
                                    <i class="fas fa-sparkles"></i> Scan Database
                                </button>

                                <div class="result-scroll" id="resultList">
                                    <div style="text-align:center; padding:3rem 1rem; color:var(--text-muted);">
                                        <i class="fas fa-search" style="font-size:2rem; margin-bottom:1rem; opacity:0.3;"></i>
                                        <p style="font-size:0.85rem;">Ready to search...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ═══ RIGHT: Action & History Column ═══ -->
                    <div id="rightCol">
                        
                        <div id="billPanel" style="display:none;">
                        
                        <!-- Core Billing Card -->
                        <div class="glass-card" id="billingDetailsCard" style="margin-bottom:1.5rem;">
                            <div class="card-header-premium">
                                <i class="fas fa-receipt"></i>
                                <h3>Billing Details</h3>
                                <button onclick="clearBill()" style="margin-left:auto; background:var(--bg-gray); border:none; color:var(--text-muted); padding:0.4rem 0.8rem; border-radius:10px; cursor:pointer; font-size:0.75rem; font-weight:700;">
                                    <i class="fas fa-sync-alt"></i> CHANGE
                                </button>
                            </div>
                            <div class="card-body-premium">
                                <!-- Patient Mini Profile -->
                                <div style="display:flex; align-items:center; gap:1.25rem; margin-bottom:2rem; padding:1.25rem; background:var(--bg-gray); border-radius:var(--radius-lg);">
                                    <div class="patient-avatar" id="billPatientInitial" style="width:4.5rem; height:4.5rem; font-size:1.8rem;">P</div>
                                    <div style="flex:1;">
                                        <h2 style="margin:0; font-size:1.25rem; color:var(--text-dark);" id="billPatientName">—</h2>
                                        <p style="margin:0.25rem 0 0; color:var(--text-muted); font-size:0.85rem;" id="billPatientMeta">—</p>
                                        <div style="margin-top:0.5rem; display:flex; gap:0.5rem;" id="billPatientBadges"></div>
                                    </div>
                                </div>

                                <h4 style="font-size:0.8rem; text-transform:uppercase; color:var(--text-muted); letter-spacing:0.05em; margin-bottom:1rem;">Charge Breakdown</h4>
                                <style>
                                    .bill-input-edit {
                                        width: 100px;
                                        padding: 0.3rem;
                                        border: 1px solid var(--gray-200);
                                        border-radius: 6px;
                                        font-size: 0.9rem;
                                        text-align: right;
                                        font-weight: 600;
                                        color: var(--text-dark);
                                        background: var(--bg-white);
                                    }
                                    .bill-input-edit:focus {
                                        border-color: var(--primary);
                                        outline: none;
                                        box-shadow: 0 0 0 2px rgba(31, 107, 74, 0.1);
                                    }
                                </style>
                                <div class="billing-grid">
                                    <div class="bill-item">
                                        <span class="bill-label">Professional Consultation</span>
                                        <div style="display:flex; align-items:center; gap:0.5rem;">
                                            <span>₹</span>
                                            <input type="number" id="feeConsult" class="bill-input-edit" value="0.00" step="0.01" oninput="calculateTotal()">
                                        </div>
                                    </div>
                                    <div class="bill-item">
                                        <span class="bill-label">Administrative Registration</span>
                                        <div style="display:flex; align-items:center; gap:0.5rem;">
                                            <span>₹</span>
                                            <input type="number" id="feeReg" class="bill-input-edit" value="0.00" step="0.01" oninput="calculateTotal()">
                                        </div>
                                    </div>
                                    <div class="bill-item" style="border-bottom:none;">
                                        <div class="bill-label">
                                            <span>Adjustments (Discount)</span>
                                            <div style="display:flex; gap:0.5rem; margin-top:0.4rem;">
                                                <input type="number" id="billDiscountPct" value="0" placeholder="%" style="width:50px; padding:0.3rem; border:1px solid var(--gray-200); border-radius:6px; font-size:0.75rem;" oninput="onDiscountPctChange()">
                                                <input type="number" id="billDiscount" value="0.00" placeholder="Amount" style="width:80px; padding:0.3rem; border:1px solid var(--gray-200); border-radius:6px; font-size:0.75rem;" oninput="onDiscountAmountChange()">
                                            </div>
                                        </div>
                                        <span class="bill-value" style="color:var(--danger);" id="displayDiscount">−₹0.00</span>
                                    </div>

                                    <div class="total-row">
                                        <span class="total-label">Payable Total</span>
                                        <span class="total-amount" id="feeTotal">₹0.00</span>
                                    </div>
                                </div>

                                <h4 style="font-size:0.8rem; text-transform:uppercase; color:var(--text-muted); letter-spacing:0.05em; margin:2rem 0 1rem;">Payment Method</h4>
                                <div class="payment-options">
                                    <div class="pay-option active" data-mode="Cash" onclick="setPaymentMode(this)">
                                        <i class="fas fa-coins"></i>
                                        <span>CASH</span>
                                    </div>
                                    <div class="pay-option" data-mode="UPI" onclick="setPaymentMode(this)">
                                        <i class="fas fa-qrcode"></i>
                                        <span>UPI / DIGITAL</span>
                                    </div>
                                    <div class="pay-option" data-mode="Card" onclick="setPaymentMode(this)">
                                        <i class="fas fa-credit-card"></i>
                                        <span>CARD</span>
                                    </div>
                                    <div class="pay-option" data-mode="Insurance" onclick="setPaymentMode(this)">
                                        <i class="fas fa-shield-alt"></i>
                                        <span>INSURANCE</span>
                                    </div>
                                </div>

                                <div class="action-flex">
                                    <button class="btn-premium btn-secondary-premium" onclick="clearBill()">
                                        <i class="fas fa-times"></i> RESET
                                    </button>
                                    <button class="btn-premium btn-primary-premium" id="generateBtn" onclick="generateBill()">
                                        <i class="fas fa-check-circle"></i> FINALIZE BILL
                                    </button>
                                    <button class="btn-premium btn-success-premium" id="printBtn" onclick="openPrintModal()" style="display:none;">
                                        <i class="fas fa-print"></i> PRINT INVOICE
                                    </button>
                                </div>
                            </div>
                        </div>

                        </div><!-- /billPanel -->

                        <!-- ═══ Recent Transactions ═══ -->
                        <div class="glass-card">
                            <div class="card-header-premium">
                                <i class="fas fa-history"></i>
                                <h3>Recent Activity</h3>
                                <div style="margin-left:auto; display:flex; gap:0.5rem;">
                                    <button onclick="loadRecentBills()" style="background:var(--bg-gray); border:none; color:var(--primary); padding:0.4rem 1rem; border-radius:10px; cursor:pointer; font-size:0.75rem; font-weight:700;">
                                        <i class="fas fa-sync-alt"></i> REFRESH
                                    </button>
                                </div>
                            </div>
                            <div class="card-body-premium" style="padding:0;">
                                <div class="recent-table-container">
                                    <table class="modern-table">
                                        <thead>
                                            <tr>
                                                <th>Transaction ID</th>
                                                <th>Patient Name</th>
                                                <th>Time / Date</th>
                                                <th style="text-align:right;">Amount</th>
                                                <th style="text-align:center;">Status</th>
                                                <th style="text-align:center;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="recentBillsTbody">
                                            <tr>
                                                <td colspan="6" style="text-align:center; padding:3rem;">
                                                    <i class="fas fa-circle-notch fa-spin" style="font-size:1.5rem; color:var(--primary); margin-bottom:1rem;"></i>
                                                    <p style="color:var(--text-muted); font-size:0.85rem;">Securing ledger data...</p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div><!-- /rightCol -->

                </div><!-- /ab-layout -->
            </div><!-- /ab-page -->
        </main>

        <!-- ── Print Preview Modal ── -->
        <div class="modal" id="printModal">
            <div class="modal-content">
                <button class="modal-close" onclick="closePrintModal()"><i class="fas fa-times"></i></button>
                <div id="modalPrintTarget" style="padding:3rem;">
                    <!-- Print content will be injected here -->
                </div>
                <div style="padding:1.5rem 3rem; background:var(--bg-gray); display:flex; justify-content:flex-end; gap:1rem; border-radius:0 0 var(--radius-xl) var(--radius-xl);">
                    <button class="btn-premium btn-secondary-premium" onclick="closePrintModal()" style="max-width:150px;">CLOSE</button>
                    <button class="btn-premium btn-primary-premium" onclick="executeActualPrint()" style="max-width:200px;"><i class="fas fa-print"></i> CONFIRM PRINT</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/* ═══════════════════════════════════════════════════════
/* ═══════════════════════════════════════════════════════
   APPOINTMENT BILLING — Frontend Engine v2.0
   ═══════════════════════════════════════════════════════ */

let currentPatient = null;
let searchMode = 'name';
let currentBillId = null;
let paymentMode = 'Cash';
let _results = [];

// ── Initialization ──
document.addEventListener('DOMContentLoaded', () => {
    loadRecentBills();
    refreshStats();
});

function refreshStats() {
    fetch('/GM_HMS/api/index.php/api/billing/stats/daily')
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                document.getElementById('statTodayCount').textContent = res.data.total_bills || 0;
                document.getElementById('statTodayAmount').textContent = '₹' + (parseFloat(res.data.total_amount) || 0).toLocaleString('en-IN', {minimumFractionDigits:2});
                document.getElementById('statPendingCount').textContent = res.data.pending_count || 0;
                document.getElementById('statNewPatients').textContent = res.data.new_registrations || 0;
            }
        });
}

// ── Search & Identification ──
function switchSearchType(btn, type) {
    document.querySelectorAll('.search-type-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    searchMode = type;
    const input = document.getElementById('searchInput');
    input.placeholder = `Search by ${type}...`;
    input.focus();
}

function performFinalSearch() {
    const q = document.getElementById('searchInput').value.trim();
    if(q.length < 2) return;

    const spinner = document.getElementById('searchSpinner');
    spinner.className = 'fas fa-circle-notch fa-spin';
    
    fetch(`api/search_appointment_patient.php?q=${encodeURIComponent(q)}&mode=${searchMode}`)
        .then(r => r.json())
        .then(res => {
            spinner.className = 'fas fa-search';
            if(res.success && res.data.length) {
                _results = res.data;
                renderResults(res.data);
            } else {
                setResultHTML('<div style="text-align:center; padding:3rem 1rem; color:var(--text-muted);"><i class="fas fa-ghost" style="font-size:2rem; margin-bottom:1rem; opacity:0.3;"></i><p>No matches found in records.</p></div>');
            }
        })
        .catch(() => {
            spinner.className = 'fas fa-search';
            setResultHTML('<div style="color:var(--danger); text-align:center; padding:2rem;">Connection Error</div>');
        });
}

function renderResults(data) {
    let html = '';
    data.forEach((p, i) => {
        const initial = (p.patient_name || '?')[0].toUpperCase();
        const badge = p.is_first_time ? '<span class="status-badge badge-new">New Patient</span>' : '<span class="status-badge badge-returning">Returning</span>';
        const sub = `ID: ${p.patient_id} | ${p.phone || 'No Phone'}`;
        
        html += `
            <div class="patient-result-card" onclick="selectPatient(${i}, this)">
                <div class="patient-avatar">${initial}</div>
                <div class="patient-meta">
                    <h4>${escHtml(p.patient_name)}</h4>
                    <p>${escHtml(sub)}</p>
                </div>
                ${badge}
            </div>`;
    });
    setResultHTML(html);
}

function selectPatient(idx, el) {
    currentPatient = _results[idx];
    document.querySelectorAll('.patient-result-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');

    // UI Updates
    document.getElementById('billPanel').style.display = 'block';
    
    document.getElementById('billPatientName').textContent = currentPatient.patient_name;
    document.getElementById('billPatientMeta').textContent = `ID: ${currentPatient.patient_id} • ${currentPatient.gender || '—'} • ${currentPatient.age || '—'} yrs`;
    document.getElementById('billPatientInitial').textContent = currentPatient.patient_name[0];
    
    let badges = `<span class="status-badge ${currentPatient.is_first_time?'badge-new':'badge-returning'}">${currentPatient.is_first_time?'NEW REGISTRATION':'RETURNING PATIENT'}</span>`;
    if(currentPatient.doctor_name) badges += `<span class="status-badge badge-returning" style="background:#fef3c7; color:#92400e;">DR. ${currentPatient.doctor_name.toUpperCase()}</span>`;
    document.getElementById('billPatientBadges').innerHTML = badges;

    // Fees
    document.getElementById('feeConsult').value = (parseFloat(currentPatient.consultation_fee) || 0).toFixed(2);
    document.getElementById('feeReg').value = (parseFloat(currentPatient.registration_fee) || 0).toFixed(2);
    
    // Auto-apply discount if previously saved (though usually 0 on load)
    document.getElementById('billDiscount').value = (parseFloat(currentPatient.discount) || 0).toFixed(2);
    
    document.getElementById('billingDetailsCard').style.display = 'block';
    
    resetBillingState();
    calculateTotal();
    
    document.getElementById('billPanel').style.display = 'block';
    document.getElementById('billPanel').scrollIntoView({behavior:'smooth'});
}

// ── Billing Operations ──
function resetBillingState() {
    document.getElementById('billDiscount').value = '0.00';
    document.getElementById('billDiscountPct').value = '0';
    document.getElementById('printBtn').style.display = 'none';
    document.getElementById('generateBtn').style.display = '';
}

function calculateTotal() {
    const consult = parseFloat(document.getElementById('feeConsult').value) || 0;
    const reg = parseFloat(document.getElementById('feeReg').value) || 0;
    const subtotal = consult + reg;
    const disc = parseFloat(document.getElementById('billDiscount').value) || 0;
    const total = Math.max(0, subtotal - disc);
    
    document.getElementById('displayDiscount').textContent = disc > 0 ? `−₹${disc.toFixed(2)}` : '₹0.00';
    document.getElementById('feeTotal').textContent = '₹' + total.toFixed(2);
}

function onDiscountPctChange() {
    const consult = parseFloat(document.getElementById('feeConsult').value) || 0;
    const reg = parseFloat(document.getElementById('feeReg').value) || 0;
    const subtotal = consult + reg;
    const pct = parseFloat(document.getElementById('billDiscountPct').value) || 0;
    const amt = (subtotal * pct) / 100;
    document.getElementById('billDiscount').value = amt.toFixed(2);
    calculateTotal();
}

function onDiscountAmountChange() {
    const consult = parseFloat(document.getElementById('feeConsult').value) || 0;
    const reg = parseFloat(document.getElementById('feeReg').value) || 0;
    const subtotal = consult + reg;
    const amt = parseFloat(document.getElementById('billDiscount').value) || 0;
    const pct = subtotal > 0 ? (amt / subtotal) * 100 : 0;
    document.getElementById('billDiscountPct').value = pct.toFixed(1);
    calculateTotal();
}

function setPaymentMode(el) {
    document.querySelectorAll('.pay-option').forEach(o => o.classList.remove('active'));
    el.classList.add('active');
    paymentMode = el.dataset.mode;
}

// ── Billing Operations ──
function generateBill() {
    if(!currentPatient) return;
    
    const btn = document.getElementById('generateBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> PROCESSING...';

    const consultFee = parseFloat(document.getElementById('feeConsult').value) || 0;
    const regFee = parseFloat(document.getElementById('feeReg').value) || 0;
    const items = [];
    
    if (consultFee > 0) {
        items.push({
            item_name: 'Consultation Fee',
            unit_price: consultFee,
            quantity: 1,
            bill_purpose: 'Registration/Appointment'
        });
    }
    
    if (regFee > 0) {
        items.push({
            item_name: 'New Registration Fee',
            unit_price: regFee,
            quantity: 1,
            bill_purpose: 'Registration/Appointment'
        });
    }

    const data = {
        patient_id: currentPatient.patient_id,
        doctor_id: currentPatient.doctor_id || null,
        appointment_id: currentPatient.appointment_id || '',
        discount_amount: document.getElementById('billDiscount').value,
        purpose: 'Registration/Appointment',
        items: items,
        payment: {
            amount: (consultFee + regFee) - (parseFloat(document.getElementById('billDiscount').value) || 0),
            payment_mode: paymentMode
        }
    };

    fetch('/GM_HMS/api/index.php/api/billing/create', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle"></i> FINALIZE BILL';
        
        if(res.success) {
            currentBillId = res.bill_id;
            showToast('Invoice generated successfully', 'success');
            document.getElementById('generateBtn').style.display = 'none';
            document.getElementById('printBtn').style.display = '';
            
            // Hide billing details card after success
            document.getElementById('billingDetailsCard').style.display = 'none';
            
            loadRecentBills();
            refreshStats();
        } else {
            showToast(res.message || 'Billing error', 'error');
        }
    })
    .catch(() => {
        btn.disabled = false;
        showToast('Connection failed', 'error');
    });
}

// ── Print & Modals ──
function openPrintModal(billId = null) {
    const id = billId || currentBillId;
    if(!id) return;
    
    document.getElementById('modalPrintTarget').innerHTML = `
        <div style="text-align:center; padding:5rem;">
            <i class="fas fa-circle-notch fa-spin" style="font-size:2rem; color:var(--primary);"></i>
            <p style="margin-top:1rem; color:var(--text-muted);">Generating HD Preview...</p>
        </div>`;
    document.getElementById('printModal').style.display = 'flex';

    fetch(`includes/bill_print_template.php?bill_id=${id}`)
        .then(r => r.text())
        .then(html => {
            document.getElementById('modalPrintTarget').innerHTML = html;
        });
}

function closePrintModal() {
    document.getElementById('printModal').style.display = 'none';
}

function executeActualPrint() {
    window.print();
}

function triggerPrint(billId) {
    openPrintModal(billId);
}

// ── Utils ──
function loadRecentBills() {
    const tbody = document.getElementById('recentBillsTbody');
    
    fetch('/GM_HMS/api/index.php/api/billing/opd?purpose=Registration/Appointment')
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                renderBillsTable(res.data);
            }
        });
}

function renderBillsTable(bills) {
    const tbody = document.getElementById('recentBillsTbody');
    if(!bills.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:4rem; color:var(--text-muted);">No transactions found today.</td></tr>';
        return;
    }

    tbody.innerHTML = bills.map(b => `
        <tr>
            <td>#${b.bill_id}</td>
            <td style="font-weight:700;">${escHtml(b.patient_name)}</td>
            <td>${b.created_at || '—'}</td>
            <td style="text-align:right; font-weight:800; color:var(--primary);">₹${parseFloat(b.grand_total).toFixed(2)}</td>
            <td style="text-align:center;">
                <span class="status-badge" style="background:#dcfce7; color:#15803d; font-size:0.6rem;">SUCCESS</span>
            </td>
            <td style="text-align:center;">
                <button onclick="triggerPrint('${b.bill_id}')" style="background:none; border:none; color:var(--primary); cursor:pointer; font-size:1.1rem; transition:0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'">
                    <i class="fas fa-print"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function showToast(msg, type) {
    const toast = document.createElement('div');
    toast.style = `position:fixed; bottom:2rem; right:2rem; padding:1rem 2rem; border-radius:12px; background:white; box-shadow:0 10px 25px rgba(0,0,0,0.1); border-left:5px solid ${type==='success'?'#10b981':'#ef4444'}; z-index:100000; animation:slideIn 0.3s ease; display:flex; align-items:center; gap:1rem; font-weight:700; color:#1e293b;`;
    toast.innerHTML = `<i class="fas fa-${type==='success'?'check-circle':'times-circle'}" style="color:${type==='success'?'#10b981':'#ef4444'};"></i> ${msg}`;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.animation = 'fadeOut 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

function setResultHTML(html) { document.getElementById('resultList').innerHTML = html; }
function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function clearBill() {
    currentPatient = null;
    currentBillId = null;
    paymentMode = 'Cash';
    
    document.getElementById('billingDetailsCard').style.display = 'block';
    
    document.getElementById('billPanel').style.display = 'none';
    document.querySelectorAll('.patient-result-card').forEach(c => c.classList.remove('selected'));
    window.scrollTo({top:0, behavior:'smooth'});
}

</script>
</body>
</html>
