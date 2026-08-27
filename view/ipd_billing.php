<?php
session_start();
require_once '../config/SecurityConfig.php';
require_once '../security/EncryptionManager.php';
require_once '../Database/SecureDatabase.php';
use GM_HMS\Database\SecureDatabase;

if (!isset($_SESSION['user_id']) || !in_array(strtolower($_SESSION['role'] ?? ''), ['admin', 'receptionist', 'accountant', 'doctor'])) {
    header("Location: ../login.php");
    exit();
}

$pageTitle = 'IP Billing Terminal';
$userRole  = $_SESSION['role'] ?? 'admin';
$userName  = $_SESSION['username'] ?? 'Staff';

$allDoctors = [];
try {
    $db = SecureDatabase::getInstance();
    $conn = $db->getConnection();
    $res = $conn->query("SELECT doctor_id, full_name, designation, specialization, consultation_fee FROM doctors WHERE status = 'Active' OR status IS NULL OR status = '' ORDER BY full_name ASC");
    if ($res) {
        while ($r = $res->fetch_assoc()) $allDoctors[] = $r;
    }
} catch(Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPD Billing Terminal — GM HMS</title>
    <meta name="description" content="IPD Billing Terminal for GM Hospital Management System">
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="assets/css/admin_common.css">
    <link rel="stylesheet" href="assets/css/ipd_billing.css?v=<?= time() ?>">
    <style>
        /* Strict 2-Color Theme System: #f3efe6 (Cream) and #1f6b4a (Forest Green) ONLY */
        :root {
            --green: #1f6b4a;
            --cream: #f3efe6;
        }

        body, .ipd-billing-page, .bg-slate-50 {
            background-color: #f3efe6 !important;
            color: #1f6b4a !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
        }

        .patient-header-card, .qs-item, .panel-card, .financial-summary-card, .estat-card, .billing-modal {
            background: #f3efe6 !important;
            border: 1.5px solid #1f6b4a !important;
            box-shadow: 0 4px 16px rgba(31, 107, 74, 0.08) !important;
            border-radius: 12px !important;
            color: #1f6b4a !important;
        }

        .patient-header-card {
            padding: 14px 20px !important;
        }

        .phc-avatar {
            background: #1f6b4a !important;
            color: #f3efe6 !important;
            font-weight: 800 !important;
            border-radius: 10px !important;
        }

        .phc-name {
            color: #1f6b4a !important;
            font-size: 1.35rem !important;
            font-weight: 800 !important;
        }

        .phc-name-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 4px;
        }

        .phc-clearance-wrap {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .clearance-pill-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.73rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            border: 1.5px solid transparent;
            text-decoration: none;
        }
        .clearance-pill-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.12);
        }

        .dept-badge-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2.5px 8px;
            border-radius: 8px;
            font-size: 0.7rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid #cbd5e1;
            background: #ffffff;
        }
        .dept-badge-btn:hover {
            transform: translateY(-1px);
        }

        .badge-patient-active {
            background: #dcfce7 !important;
            color: #14532d !important;
            border: 1.5px solid #4ade80 !important;
            padding: 4px 12px !important;
            border-radius: 20px !important;
            font-size: 0.8rem !important;
            font-weight: 800 !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05) !important;
        }
        .badge-patient-active span.dot {
            width: 8px !important;
            height: 8px !important;
            border-radius: 50% !important;
            background: #16a34a !important;
            display: inline-block !important;
        }

        .badge-patient-discharged {
            background: #f1f5f9 !important;
            color: #334155 !important;
            border: 1.5px solid #94a3b8 !important;
            padding: 4px 12px !important;
            border-radius: 20px !important;
            font-size: 0.8rem !important;
            font-weight: 800 !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
        }
        .badge-patient-discharged span.dot {
            width: 8px !important;
            height: 8px !important;
            border-radius: 50% !important;
            background: #64748b !important;
            display: inline-block !important;
        }

        .badge-payment-verified {
            background: #dcfce7 !important;
            color: #14532d !important;
            border: 1.5px solid #4ade80 !important;
            padding: 4px 10px !important;
            border-radius: 20px !important;
            font-weight: 800 !important;
            font-size: 0.75rem !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 5px !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05) !important;
        }

        .badge-payment-pending {
            background: #fef3c7 !important;
            color: #92400e !important;
            border: 1.5px solid #fcd34d !important;
            padding: 4px 10px !important;
            border-radius: 20px !important;
            font-weight: 800 !important;
            font-size: 0.75rem !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 5px !important;
        }

        /* ── BALANCE DUE High Contrast Styling ── */
        .fs-balance-box, #fsBalanceBox {
            background: linear-gradient(135deg, #dc2626, #b91c1c) !important;
            border: 2px solid #991b1b !important;
            border-radius: 12px !important;
            padding: 14px 18px !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            color: #ffffff !important;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.3) !important;
            margin: 14px 0 !important;
        }

        .fs-balance-box span, #fsBalanceBox span, .fs-balance-box div, #fsBalanceBox div {
            color: #ffffff !important;
            font-weight: 800 !important;
            font-size: 0.95rem !important;
            letter-spacing: 0.5px !important;
        }

        .fs-balance-box #fsBalanceDue, #fsBalanceBox #fsBalanceDue {
            color: #ffffff !important;
            font-size: 1.4rem !important;
            font-weight: 900 !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3) !important;
        }

        .fs-balance-box.is-paid, #fsBalanceBox.is-paid {
            background: linear-gradient(135deg, #15803d, #166534) !important;
            border-color: #14532d !important;
            box-shadow: 0 4px 14px rgba(21, 128, 61, 0.3) !important;
        }

        .fs-balance-box.is-partial, #fsBalanceBox.is-partial {
            background: linear-gradient(135deg, #d97706, #b45309) !important;
            border-color: #92400e !important;
            box-shadow: 0 4px 14px rgba(217, 119, 6, 0.3) !important;
        }

        #qsBalanceDue {
            color: #dc2626 !important;
            font-weight: 900 !important;
            font-size: 1.35rem !important;
        }

        .phc-meta {
            color: #1f6b4a !important;
            opacity: 0.9;
        }

        .phc-tag {
            background: #f3efe6 !important;
            color: #1f6b4a !important;
            border: 1px solid #1f6b4a !important;
            font-weight: 700 !important;
            border-radius: 6px !important;
            padding: 2px 8px !important;
        }

        .phc-btn {
            background: #f3efe6 !important;
            color: #1f6b4a !important;
            border: 1.5px solid #1f6b4a !important;
            font-weight: 700 !important;
            border-radius: 8px !important;
            padding: 6px 12px !important;
            font-size: 0.82rem !important;
            transition: all 0.2s ease !important;
            cursor: pointer;
        }

        .phc-btn:hover, .phc-btn.active, #btnPrintFinal {
            background: #1f6b4a !important;
            color: #f3efe6 !important;
            border-color: #1f6b4a !important;
        }

        .phc-billing-status {
            background: #1f6b4a !important;
            color: #f3efe6 !important;
            border: 1px solid #1f6b4a !important;
            font-weight: 800 !important;
            border-radius: 20px !important;
            padding: 3px 10px !important;
            font-size: 0.75rem !important;
        }

        /* Stat Cards */
        .qs-item {
            position: relative;
            border: 1.5px solid #1f6b4a !important;
            padding: 16px 20px !important;
            background: #f3efe6 !important;
        }

        .qs-icon {
            background: #1f6b4a !important;
            color: #f3efe6 !important;
            border-radius: 10px !important;
        }

        .qs-val {
            font-size: 1.4rem !important;
            font-weight: 800 !important;
            color: #1f6b4a !important;
        }

        .qs-lbl {
            font-size: 0.72rem !important;
            font-weight: 700 !important;
            color: #1f6b4a !important;
            text-transform: uppercase;
            letter-spacing: 0.04em !important;
            opacity: 0.85;
        }

        /* Filter Pills */
        .category-filter-tabs {
            padding: 12px 18px !important;
            background: #f3efe6 !important;
            border-bottom: 1px solid rgba(31, 107, 74, 0.25) !important;
            gap: 8px !important;
        }

        .cat-tab {
            background: #f3efe6 !important;
            color: #1f6b4a !important;
            border: 1.5px solid #1f6b4a !important;
            border-radius: 20px !important;
            padding: 5px 14px !important;
            font-weight: 700 !important;
            font-size: 0.8rem !important;
            transition: all 0.2s ease !important;
            cursor: pointer;
        }

        .cat-tab:hover, .cat-tab.active {
            background: #1f6b4a !important;
            color: #f3efe6 !important;
            border-color: #1f6b4a !important;
        }

        /* Table Header */
        .billing-items-table th {
            background: #1f6b4a !important;
            color: #f3efe6 !important;
            font-size: 0.75rem !important;
            font-weight: 800 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            padding: 10px 14px !important;
            border: none !important;
        }

        .billing-items-table td {
            color: #1f6b4a !important;
            border-bottom: 1px solid rgba(31, 107, 74, 0.2) !important;
        }

        .btn-add-charge, .btn-room-rent, .btn-add-payment, .fs-btn {
            border-radius: 8px !important;
            font-weight: 700 !important;
            font-size: 0.82rem !important;
            padding: 7px 14px !important;
            transition: all 0.2s ease !important;
            cursor: pointer;
        }

        .btn-add-charge, .fs-btn-payment, .bm-btn-primary {
            background: #1f6b4a !important;
            color: #f3efe6 !important;
            border: 1.5px solid #1f6b4a !important;
        }

        .btn-add-charge:hover, .fs-btn-payment:hover, .bm-btn-primary:hover {
            opacity: 0.92;
        }

        .btn-room-rent, .btn-add-payment, .fs-btn-discount, .fs-btn-ins, .bm-btn-secondary {
            background: #f3efe6 !important;
            color: #1f6b4a !important;
            border: 1.5px solid #1f6b4a !important;
        }

        .btn-room-rent:hover, .btn-add-payment:hover, .fs-btn-discount:hover, .fs-btn-ins:hover {
            background: #1f6b4a !important;
            color: #f3efe6 !important;
        }

        /* Financial Summary Card */
        .fs-header {
            font-size: 1.05rem !important;
            font-weight: 800 !important;
            color: #1f6b4a !important;
            border-bottom: 1.5px solid rgba(31, 107, 74, 0.2) !important;
            padding-bottom: 10px !important;
        }

        .fs-cat-row {
            font-size: 0.86rem !important;
            font-weight: 600 !important;
            color: #1f6b4a !important;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 0;
        }

        .fs-cat-val {
            font-weight: 700 !important;
            color: #1f6b4a !important;
        }

        .fs-grand-total {
            background: #1f6b4a !important;
            color: #f3efe6 !important;
            border: 1.5px solid #1f6b4a !important;
            border-radius: 10px !important;
            padding: 12px 14px !important;
            font-weight: 800 !important;
            font-size: 1.05rem !important;
        }

        .fs-grand-total span, .fs-grand-total div {
            color: #f3efe6 !important;
        }

        .fs-balance-box {
            background: #1f6b4a !important;
            color: #f3efe6 !important;
            border: 1.5px solid #1f6b4a !important;
            border-radius: 10px !important;
            padding: 14px 16px !important;
        }

        .fs-balance-box .bold {
            color: #f3efe6 !important;
            font-size: 1.25rem !important;
            font-weight: 800 !important;
        }

        .item-count-badge {
            background: #1f6b4a !important;
            color: #f3efe6 !important;
            font-weight: 700;
            border-radius: 12px;
            padding: 2px 8px;
        }

        /* Inputs & Modals */
        input, select, textarea {
            background: #f3efe6 !important;
            color: #1f6b4a !important;
            border: 1.5px solid #1f6b4a !important;
        }

        .bm-head {
            background: #1f6b4a !important;
            color: #f3efe6 !important;
        }

        .bm-head h3, .bm-head .bm-title, .bm-head .bm-close {
            color: #f3efe6 !important;
        }

        /* Toast notifications */
        .billing-toast {
            background: #1f6b4a !important;
            color: #f3efe6 !important;
            border: 1.5px solid #f3efe6 !important;
            border-radius: 10px !important;
            box-shadow: 0 6px 20px rgba(31, 107, 74, 0.3) !important;
            font-weight: 600 !important;
        }

        /* ── Nurse Workspace Pattern Styles ── */
        .treatment-subtabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }
        .t-tab {
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 700;
            background: #f3efe6;
            color: #1f6b4a;
            border: 1.5px solid #1f6b4a;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .t-tab:hover, .t-tab.active {
            background: #1f6b4a !important;
            color: #f3efe6 !important;
            border-color: #1f6b4a !important;
            box-shadow: 0 4px 12px rgba(31, 107, 74, 0.22);
        }
        .t-panel {
            display: none;
            width: 100%;
            flex-direction: column;
            gap: 12px;
        }
        .t-panel.active {
            display: flex;
        }
        .t-title {
            font-size: 0.95rem;
            font-weight: 800;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #1f6b4a;
        }

        /* Search Dropdowns */
        #doc-results, #lab-results, #rad-results, #other-results, #ph-results, #proc-doc-results, #dia-doc-results, #oxy-doc-results, #vent-doc-results, #bt-doc-results, #wt-doc-results {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            background: #f3efe6;
            border: 1.5px solid #1f6b4a;
            border-radius: 10px;
            z-index: 500;
            display: none;
            box-shadow: 0 10px 25px rgba(31, 107, 74, 0.22);
            max-height: 240px;
            overflow-y: auto;
        }
        .ts-item, .ph-item {
            padding: 10px 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(31, 107, 74, 0.15);
            transition: background 0.15s;
        }
        .ts-item:last-child, .ph-item:last-child {
            border-bottom: none;
        }
        .ts-item:hover, .ph-item:hover {
            background: rgba(31, 107, 74, 0.15);
        }
        .cart-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: #f3efe6;
            border-radius: 8px;
            margin-bottom: 6px;
            border: 1.5px solid #1f6b4a;
        }
        .cart-row-n {
            flex: 1;
            font-size: 0.84rem;
            font-weight: 700;
            color: #1f6b4a;
        }
        .cart-row input[type=number] {
            width: 70px;
            padding: 4px 6px;
            border: 1px solid #1f6b4a;
            border-radius: 6px;
            text-align: center;
            font-size: 0.82rem;
            color: #1f6b4a;
            font-weight: 700;
            background: #f3efe6;
        }
        .cart-row .rm-btn {
            background: none;
            border: none;
            color: #dc2626;
            cursor: pointer;
            padding: 4px 8px;
            font-size: 0.9rem;
            transition: transform 0.15s ease;
        }
        .cart-row .rm-btn:hover {
            transform: scale(1.15);
        }
        .badge {
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.72rem;
            font-weight: 700;
            background: rgba(31, 107, 74, 0.15);
            color: #1f6b4a;
            border: 1px solid #1f6b4a;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .fg {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            width: 100%;
        }
        .fmg {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .fmg label {
            font-size: 0.78rem;
            font-weight: 700;
            color: #1f6b4a;
        }
        .fmg input, .fmg select, .fmg textarea {
            padding: 8px 10px;
            border-radius: 8px;
            border: 1.5px solid #1f6b4a;
            background: #f3efe6;
            color: #1f6b4a;
            font-size: 0.84rem;
            font-weight: 600;
            outline: none;
        }
        .fmg input:focus, .fmg select:focus, .fmg textarea:focus {
            box-shadow: 0 0 0 2px rgba(31, 107, 74, 0.3);
        }
    </style>
</head>
<body class="bg-slate-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Navbar -->
            <?php include 'includes/navbar.php'; ?>

            <main class="flex-1 ipd-billing-page" id="ipdBillingPage">
                
                <!-- ═══════════ ZONE 1: TOP SEARCH BAR (REMOVED) ═══════════ -->
                <!-- The top search zone has been removed per user request -->

                <!-- ═══════════ ZONE 2: ADMITTED PATIENTS LIST ═══════════ -->
                <div class="billing-empty-state" id="billingEmptyState" style="padding:20px; align-items: stretch; justify-content: flex-start; height: calc(100vh - 100px); display: flex; flex-direction: column;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 15px; border-bottom: 2px solid #1f6b4a; padding-bottom: 10px; flex-shrink: 0;">
                        <h2 style="font-size: 1.5rem; color: #1f6b4a; margin: 0; text-align: left; font-weight: 800;"><i data-lucide="users" style="display:inline; vertical-align:middle; margin-right:8px;"></i> All IPD Patients</h2>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <select id="patientStatusFilter" onchange="billing.filterPatientsTable()" style="padding: 8px 12px; border-radius: 6px; border: 1.5px solid #1f6b4a; font-size: 0.9rem; outline: none; background: #f3efe6; color: #1f6b4a; cursor: pointer; font-weight: 700;">
                                <option value="ALL" selected>All Patients</option>
                                <option value="ACTIVE">Active Patients Only</option>
                                <option value="DISCHARGED">Discharged</option>
                            </select>
                            <div style="position: relative;">
                                <i data-lucide="search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: #1f6b4a;"></i>
                                <input type="text" id="patientTableSearch" onkeyup="billing.filterPatientsTable()" placeholder="Search in table..." style="padding: 8px 10px 8px 30px; border-radius: 6px; border: 1.5px solid #1f6b4a; font-size: 0.9rem; outline: none; width: 200px; background: #f3efe6; color: #1f6b4a;">
                            </div>
                            <button class="bm-btn" style="background: #1f6b4a; color: #f3efe6; border: 1.5px solid #1f6b4a; padding: 8px 16px; border-radius: 6px; font-weight: 700; display: flex; align-items: center; gap: 6px; cursor: pointer; transition: all 0.2s;" onclick="billing.openDischargeHistory()">
                                <i data-lucide="history" style="width: 16px; height: 16px;"></i> Discharge History
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive rounded shadow-sm" style="flex-grow: 1; overflow-y: auto; position: relative; background: #f3efe6; border: 1.5px solid #1f6b4a;">
                        <table class="billing-items-table" style="width: 100%; text-align: left; border-collapse: separate; border-spacing: 0;">
                            <thead style="position: sticky; top: 0; z-index: 20;">
                                <tr>
                                    <th style="padding: 12px; border-bottom: 2px solid #1f6b4a; cursor: pointer;" onclick="billing.sortPatientsTable('admission_id')">Admission ID <i data-lucide="chevrons-up-down" style="width:12px;height:12px;display:inline;"></i></th>
                                    <th style="padding: 12px; border-bottom: 2px solid #1f6b4a; cursor: pointer;" onclick="billing.sortPatientsTable('patient_name')">Patient Name <i data-lucide="chevrons-up-down" style="width:12px;height:12px;display:inline;"></i></th>
                                    <th style="padding: 12px; border-bottom: 2px solid #1f6b4a;">Age/Sex</th>
                                    <th style="padding: 12px; border-bottom: 2px solid #1f6b4a;">Phone</th>
                                    <th style="padding: 12px; border-bottom: 2px solid #1f6b4a;">Ward & Bed</th>
                                    <th style="padding: 12px; border-bottom: 2px solid #1f6b4a;">Doctor</th>
                                    <th style="padding: 12px; border-bottom: 2px solid #1f6b4a; cursor: pointer;" onclick="billing.sortPatientsTable('status')">Status <i data-lucide="chevrons-up-down" style="width:12px;height:12px;display:inline;"></i></th>
                                    <th style="padding: 12px; border-bottom: 2px solid #1f6b4a;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="admittedPatientsList">
                                <tr><td colspan="7" style="text-align:center; padding:20px; color: #1f6b4a;">Loading patients...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ═══════════ ZONE 3: BILLING WORKSPACE ═══════════ -->
                <div class="billing-workspace" id="billingWorkspace" style="display:none;">
                    
                    <!-- ── HEADER ACTIONS ── -->
                    <div class="workspace-header-actions">
                        <div class="patient-header-card" id="patientHeaderCard">
                            <div class="phc-left">
                                <div class="phc-avatar" id="phcAvatar">JD</div>
                                <div class="phc-info">
                                    <div class="phc-name" id="phcName">Patient Name</div>
                                    <div class="phc-meta">
                                        <span class="phc-tag" id="phcAdmId"></span>
                                        <span class="phc-dot">·</span>
                                        <span id="phcAge"></span>
                                        <span class="phc-dot">·</span>
                                        <span id="phcDoctor"></span>
                                    </div>
                                    <div class="phc-meta">
                                        <i data-lucide="bed"></i>
                                        <span id="phcBed"></span>
                                        <span class="phc-dot">·</span>
                                        <i data-lucide="calendar"></i>
                                        <span id="phcDates"></span>
                                        <span class="phc-dot">·</span>
                                        <span id="phcDays" class="phc-days-badge"></span>
                                    </div>
                                    <div class="phc-meta" id="phcExtraInfo" style="display:none; margin-top: 6px;"></div>
                                </div>
                            </div>
                            <div class="phc-right" style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                                <!-- Clearance Status Badges & Bill Status (Above buttons) -->
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 10px; flex-wrap: wrap;">
                                    <!-- Discharge Clearance Multi-Module Badges -->
                                    <div class="phc-clearance-wrap" id="phcClearanceContainer"></div>

                                    <div class="phc-bill-card" style="margin: 0; display: flex; align-items: center; gap: 6px;">
                                        <div class="phc-bill-no" id="phcBillNo" style="margin-bottom: 0;">BILL-0000</div>
                                        <div class="phc-billing-status" id="phcBillingStatus"></div>
                                    </div>
                                </div>

                                <div class="phc-actions">
                                    <button class="phc-btn" id="btnToggleItems" onclick="billing.toggleDetailedCharges()" title="View/Add Charges Breakdown">
                                        <i data-lucide="list"></i> Charges Breakdown
                                    </button>
                                    <button class="phc-btn phc-btn-print" onclick="billing.printInterim()" title="Interim Bill">
                                        <i data-lucide="printer"></i> Interim
                                    </button>
                                    <button class="phc-btn phc-btn-print" onclick="billing.printFinal()" id="btnPrintFinal" title="Final Bill">
                                        <i data-lucide="file-text"></i> Final Bill
                                    </button>
                                    <button class="phc-btn phc-btn-print" onclick="billing.printReceipt()" title="Receipt">
                                        <i data-lucide="receipt"></i> Receipt
                                    </button>
                                    <button class="phc-btn" onclick="billing.openStatusModal()" title="Change Status">
                                        <i data-lucide="toggle-right"></i> Status
                                    </button>
                                    <button class="phc-btn" onclick="billing.dischargePatient()" title="Discharge Patient">
                                        <i data-lucide="log-out"></i> Discharge
                                    </button>
                                    <button class="phc-btn" id="btnInsuranceInfo" onclick="billing.openInsuranceModal()">
                                        <i data-lucide="shield"></i> Insurance
                                    </button>
                                    <button class="phc-btn" onclick="billing.closeWorkspace()" title="Back to All IP Patients" style="background:#1f6b4a !important; color:#ffffff !important; font-weight:800 !important;">
                                        <i data-lucide="arrow-left"></i> Back to Patients
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── QUICK STATS KPI CARDS ── -->
                    <div class="quick-stats-bar" id="quickStatsBar">
                        <div class="qs-item">
                            <div class="qs-icon"><i data-lucide="calculator"></i></div>
                            <div><div class="qs-val" id="qsGrandTotal">₹0</div><div class="qs-lbl">Grand Total</div></div>
                        </div>
                        <div class="qs-item">
                            <div class="qs-icon"><i data-lucide="check-circle-2"></i></div>
                            <div><div class="qs-val" id="qsAmountPaid">₹0</div><div class="qs-lbl">Amount Paid</div></div>
                        </div>
                        <div class="qs-item">
                            <div class="qs-icon"><i data-lucide="alert-circle"></i></div>
                            <div><div class="qs-val" id="qsBalanceDue">₹0</div><div class="qs-lbl">Balance Due</div></div>
                        </div>
                        <div class="qs-item">
                            <div class="qs-icon"><i data-lucide="list"></i></div>
                            <div><div class="qs-val" id="qsItemCount">0</div><div class="qs-lbl">Total Charges</div></div>
                        </div>
                    </div>

                    <!-- ── MAIN 2-PANEL LAYOUT ── -->
                    <div class="billing-main-panels">
                        
                        <!-- ───────── LEFT PANEL (Scrollable) ───────── -->
                        <div class="billing-left-panel">
                            
                            <!-- Billing Items Section (Fully Visible for Admin) -->
                            <div class="panel-card" id="billingItemsCard">
                                <div class="panel-card-head">
                                    <div class="panel-card-title">
                                        <i data-lucide="list"></i> Billing Items
                                        <span class="item-count-badge" id="itemCountBadge">0</span>
                                    </div>
                                    <div class="panel-card-actions">
                                        <div class="add-charge-wrap">
                                            <button class="btn-add-charge" id="btnAddCharge" onclick="billing.toggleChargeMenu()">
                                                <i data-lucide="plus-circle"></i> Add Charge
                                                <i data-lucide="chevron-down" class="charge-arrow" id="chargeArrow"></i>
                                            </button>
                                            <div class="charge-menu" id="chargeMenu">
                                                <?php
                                                $chargeTypes = [
                                                    ['ROOM_RENT',        'bed-double',      'Room Rent',           'Opens room rent generator'],
                                                    ['DOCTOR_VISIT',     'stethoscope',     'Doctor Visit',        'Consultant/visiting doctor'],
                                                    ['LAB',              'flask-conical',   'Laboratory',          'Lab tests & reports'],
                                                    ['RADIOLOGY',        'radio',           'Radiology',           'X-ray, MRI, CT, USG'],
                                                    ['PHARMACY',         'pill',            'Pharmacy',            'Medicines & drugs'],
                                                    ['OT',               'syringe',         'Operation Theatre',   'OT charges'],
                                                    ['PROCEDURE',        'activity',        'Procedure',           'Minor procedures'],
                                                    ['DIALYSIS',         'filter',          'Dialysis Record',     'Hemodialysis & PD charges'],
                                                    ['OXYGEN',           'wind',            'Oxygen Therapy',      'L/min oxygen charges'],
                                                    ['VENTILATION',      'activity',        'Ventilator Support',  'Invasive / Non-invasive'],
                                                    ['BLOOD_TRANSFUSION','droplet',         'Blood Transfusion',   'Blood unit & crossmatch'],
                                                    ['WARD_TRANSFER',    'arrow-right-left','Ward Transfer',       'Shift & transfer charges'],
                                                    ['CONSUMABLE',       'bandage',         'Consumables',         'Dressings, gloves etc.'],
                                                    ['MISC',             'more-horizontal', 'Miscellaneous',       'Misc charges'],
                                                    ['OTHER',            'layers',          'Other',               'Other charges'],
                                                ];
                                                foreach ($chargeTypes as [$type, $icon, $label, $desc]):
                                                    if ($type === 'ROOM_RENT'):
                                                ?>
                                                <div class="charge-menu-item" onclick="billing.closeChargeMenu(); billing.openRoomRentModal();">
                                                    <i data-lucide="<?= $icon ?>" class="charge-menu-icon"></i>
                                                    <div><div class="cmi-label"><?= $label ?></div><div class="cmi-desc"><?= $desc ?></div></div>
                                                </div>
                                                <?php else: ?>
                                                <div class="charge-menu-item" onclick="billing.closeChargeMenu(); billing.openAddChargeModal('<?= $type ?>');">
                                                    <i data-lucide="<?= $icon ?>" class="charge-menu-icon"></i>
                                                    <div><div class="cmi-label"><?= $label ?></div><div class="cmi-desc"><?= $desc ?></div></div>
                                                </div>
                                                <?php endif; endforeach; ?>
                                            </div>
                                        </div>
                                        <button class="btn-room-rent" onclick="billing.openRoomRentModal()">
                                            <i data-lucide="bed-double"></i> Room Rent
                                        </button>
                                    </div>
                                </div>

                                <!-- Category Filter Tabs -->
                                <div class="category-filter-tabs" id="categoryFilterTabs">
                                    <button class="cat-tab active" data-type="" onclick="billing.filterItems(this,'')">All</button>
                                    <button class="cat-tab" data-type="ROOM_RENT"         onclick="billing.filterItems(this,'ROOM_RENT')">Room</button>
                                    <button class="cat-tab" data-type="DOCTOR_VISIT"      onclick="billing.filterItems(this,'DOCTOR_VISIT')">Doctor</button>
                                    <button class="cat-tab" data-type="LAB"               onclick="billing.filterItems(this,'LAB')">Lab</button>
                                    <button class="cat-tab" data-type="RADIOLOGY"         onclick="billing.filterItems(this,'RADIOLOGY')">Radiology</button>
                                    <button class="cat-tab" data-type="PHARMACY"          onclick="billing.filterItems(this,'PHARMACY')">Pharmacy</button>
                                    <button class="cat-tab" data-type="OT"                onclick="billing.filterItems(this,'OT')">OT</button>
                                    <button class="cat-tab" data-type="PROCEDURE"         onclick="billing.filterItems(this,'PROCEDURE')">Procedure</button>
                                    <button class="cat-tab" data-type="DIALYSIS"          onclick="billing.filterItems(this,'DIALYSIS')">Dialysis</button>
                                    <button class="cat-tab" data-type="OXYGEN"            onclick="billing.filterItems(this,'OXYGEN')">Oxygen</button>
                                    <button class="cat-tab" data-type="VENTILATION"       onclick="billing.filterItems(this,'VENTILATION')">Ventilator</button>
                                    <button class="cat-tab" data-type="BLOOD_TRANSFUSION" onclick="billing.filterItems(this,'BLOOD_TRANSFUSION')">Blood</button>
                                    <button class="cat-tab" data-type="WARD_TRANSFER"     onclick="billing.filterItems(this,'WARD_TRANSFER')">Transfer</button>
                                    <button class="cat-tab" data-type="CONSUMABLE"        onclick="billing.filterItems(this,'CONSUMABLE')">Consumables</button>
                                    <button class="cat-tab" data-type="MISC"              onclick="billing.filterItems(this,'MISC')">Misc</button>
                                </div>

                                <!-- Items Table -->
                                <div class="items-table-wrap">
                                    <table class="billing-items-table" id="billingItemsTable">
                                        <thead>
                                            <tr>
                                                <th>Sl.No</th>
                                                <th>Date</th>
                                                <th>Category</th>
                                                <th>Description</th>
                                                <th>Qty</th>
                                                <th>Rate</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="itemsTableBody">
                                            <tr class="empty-row" id="itemsEmptyRow">
                                                <td colspan="9">
                                                    <div class="table-empty-state">
                                                        <i data-lucide="clipboard-list"></i>
                                                        <p>No charges added yet</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Payment History & Inline Record Payment Section -->
                            <div class="panel-card" id="paymentHistoryCard">
                                <div class="panel-card-head">
                                    <div class="panel-card-title">
                                        <i data-lucide="credit-card"></i> Record Payment & Payment History
                                        <span class="item-count-badge blue-badge" id="payCountBadge">0</span>
                                    </div>
                                    <div class="panel-card-actions">
                                        <button class="btn-ins-receipt" id="btnInsReceipt" onclick="billing.openInsuranceReceiptModal()" style="display:none;">
                                            <i data-lucide="shield"></i> Insurance Receipt
                                        </button>
                                    </div>
                                </div>

                                <!-- ── INLINE RECORD PAYMENT FORM ── -->
                                <div class="inline-payment-container" style="padding: 16px; background: #ffffff; border-bottom: 1.5px solid #1f6b4a;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 8px; border-bottom: 1px solid rgba(31,107,74,0.15);">
                                        <div style="font-weight: 800; color: #1f6b4a; font-size: 0.95rem; display: flex; align-items: center; gap: 6px;">
                                            <i data-lucide="plus-circle" style="width: 18px; height: 18px;"></i> Quick Record Payment
                                        </div>
                                        <div style="font-size: 0.88rem; color: #991b1b !important; font-weight: 800; background: #fee2e2 !important; padding: 5px 14px; border-radius: 20px; border: 1.5px solid #f87171 !important; display: inline-flex; align-items: center; gap: 6px;">
                                            <span style="color: #991b1b !important; font-weight: 800;">Current Balance Due:</span> <span id="inlinePayBalanceVal" style="font-weight: 900; color: #dc2626 !important; font-size: 1.05rem;">₹0.00</span>
                                        </div>
                                    </div>

                                    <div class="bm-form-row two-col" style="margin-bottom: 12px;">
                                        <div class="bm-form-group" style="margin-bottom: 0;">
                                            <label style="font-size: 11px; font-weight: 700; color: #1f6b4a; text-transform: uppercase; margin-bottom: 4px; display: block;">Payment Date <span class="req">*</span></label>
                                            <input type="date" id="inlinePayDate" style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid #1f6b4a; border-radius: 6px; background: #f3efe6; color: #1f6b4a; font-weight: 600;">
                                        </div>
                                        <div class="bm-form-group" style="margin-bottom: 0;">
                                            <label style="font-size: 11px; font-weight: 700; color: #1f6b4a; text-transform: uppercase; margin-bottom: 4px; display: block;">Payment Type <span class="req">*</span></label>
                                            <div class="pay-type-group" id="inlinePayTypeGroup">
                                                <button type="button" class="pay-type-btn" data-type="ADVANCE">Advance</button>
                                                <button type="button" class="pay-type-btn active" data-type="PARTIAL">Partial</button>
                                                <button type="button" class="pay-type-btn" data-type="FINAL">Final</button>
                                                <button type="button" class="pay-type-btn refund-btn" data-type="REFUND">Refund</button>
                                            </div>
                                            <div class="advance-quick-chips" id="inlineAdvanceChips" style="display: flex; gap: 6px; align-items: center; margin-top: 6px; flex-wrap: wrap;">
                                                <span style="font-size: 10px; font-weight: 700; color: #1f6b4a; text-transform: uppercase;">Quick Deposit:</span>
                                                <button type="button" class="adv-chip-btn" onclick="billing.setQuickAdvance(2000)" style="padding: 2px 8px; font-size: 11px; font-weight: 700; border-radius: 4px; background: #e6f0eb; color: #1f6b4a; border: 1px solid #1f6b4a; cursor: pointer;">₹2,000</button>
                                                <button type="button" class="adv-chip-btn" onclick="billing.setQuickAdvance(5000)" style="padding: 2px 8px; font-size: 11px; font-weight: 700; border-radius: 4px; background: #e6f0eb; color: #1f6b4a; border: 1px solid #1f6b4a; cursor: pointer;">₹5,000</button>
                                                <button type="button" class="adv-chip-btn" onclick="billing.setQuickAdvance(10000)" style="padding: 2px 8px; font-size: 11px; font-weight: 700; border-radius: 4px; background: #e6f0eb; color: #1f6b4a; border: 1px solid #1f6b4a; cursor: pointer;">₹10,000</button>
                                                <button type="button" class="adv-chip-btn" onclick="billing.setQuickAdvance(25000)" style="padding: 2px 8px; font-size: 11px; font-weight: 700; border-radius: 4px; background: #e6f0eb; color: #1f6b4a; border: 1px solid #1f6b4a; cursor: pointer;">₹25,000</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bm-form-group" style="margin-bottom: 12px;">
                                        <label style="font-size: 11px; font-weight: 700; color: #1f6b4a; text-transform: uppercase; margin-bottom: 4px; display: block;">Payment Mode <span class="req">*</span></label>
                                        <div class="pay-mode-group" id="inlinePayModeGroup">
                                            <button type="button" class="pay-mode-btn active" data-mode="CASH"><i data-lucide="banknote"></i> Cash</button>
                                            <button type="button" class="pay-mode-btn" data-mode="UPI"><i data-lucide="smartphone"></i> UPI</button>
                                            <button type="button" class="pay-mode-btn" data-mode="CARD"><i data-lucide="credit-card"></i> Card</button>
                                            <button type="button" class="pay-mode-btn" data-mode="BANK"><i data-lucide="landmark"></i> Bank</button>
                                            <button type="button" class="pay-mode-btn" data-mode="CHEQUE"><i data-lucide="receipt"></i> Cheque</button>
                                            <button type="button" class="pay-mode-btn" data-mode="INSURANCE"><i data-lucide="shield"></i> Insurance</button>
                                        </div>
                                    </div>

                                    <!-- Insurance / TPA Sub-section (Sponsor Type & Company Name) -->
                                    <div id="inlineInsuranceBlock" style="display:none; background: rgba(31,107,74,0.05); border: 1.5px dashed #1f6b4a; border-radius: 8px; padding: 12px; margin-bottom: 12px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding-bottom: 6px; border-bottom: 1px dashed rgba(31,107,74,0.2);">
                                            <div style="display: flex; gap: 10px; align-items: center;">
                                                <label style="font-size: 11px; font-weight: 800; color: #1f6b4a; text-transform: uppercase; margin: 0;">Sponsor Type <span class="req">*</span>:</label>
                                                <div class="pay-type-group" id="inlineSponsorTypeGroup" style="display: flex; gap: 6px;">
                                                    <button type="button" class="pay-type-btn active" data-sponsor-type="INSURANCE" style="padding: 4px 14px; min-height: 30px; font-size: 12px;"><i data-lucide="shield"></i> Insurance</button>
                                                    <button type="button" class="pay-type-btn" data-sponsor-type="TPA" style="padding: 4px 14px; min-height: 30px; font-size: 12px;"><i data-lucide="building-2"></i> TPA</button>
                                                </div>
                                            </div>
                                            <span style="font-size: 10px; font-weight: 800; background: #1f6b4a; color: #fff; padding: 2px 8px; border-radius: 12px;"><i class="fas fa-file-medical"></i> Advance Insurance View</span>
                                        </div>

                                        <div style="position: relative; margin-bottom: 4px;">
                                            <label style="font-size: 11px; font-weight: 700; color: #1f6b4a; text-transform: uppercase; margin-bottom: 4px; display: block;">
                                                <span id="inlineSponsorLabel">Insurance Company Name</span> <span class="req">*</span>
                                                <span style="background: #1f6b4a; color: #f3efe6; font-size: 10px; padding: 2px 6px; border-radius: 4px; margin-left: 6px; font-weight: 700;"><i class="fas fa-search"></i> Advance Search</span>
                                            </label>
                                            <input type="text" id="inlineSponsorSearchInput" placeholder="Type to search Insurance company (e.g. Star Health, HDFC ERGO...)" autocomplete="off" style="width: 100%; height: 38px; padding: 0 10px; border: 1.5px solid #1f6b4a; border-radius: 6px; background: #f3efe6; color: #1f6b4a; font-weight: 700;">
                                            <input type="hidden" id="inlineSelectedSponsorName">
                                            <div id="inlineSponsorResults" style="display:none; position:absolute; left:0; right:0; top:100%; z-index:1000; background:#ffffff; border:1.5px solid #1f6b4a; border-radius:6px; max-height:220px; overflow-y:auto; box-shadow:0 6px 18px rgba(0,0,0,0.18); margin-top:2px;"></div>
                                        </div>
                                    </div>

                                    <div class="bm-form-row two-col" style="margin-bottom: 12px;">
                                        <div class="bm-form-group" style="margin-bottom: 0;">
                                            <label id="inlineAmountLabel" style="font-size: 11px; font-weight: 700; color: #1f6b4a; text-transform: uppercase; margin-bottom: 4px; display: block;">Amount (₹) <span class="req">*</span></label>
                                            <div style="position:relative;">
                                                <input type="number" id="inlinePayAmount" min="0.01" step="0.01" placeholder="0.00" oninput="billing.updateInlinePayPreview()" style="width: 100%; height: 38px; padding: 0 50px 0 10px; border: 1px solid #1f6b4a; border-radius: 6px; background: #f3efe6; color: #1f6b4a; font-weight: 800; font-size: 15px;">
                                                <button class="btn-full-amount" id="btnInlineFullAmount" onclick="billing.fillInlineFullAmount()" title="Fill full balance" style="position: absolute; right: 4px; top: 50%; transform: translateY(-50%); padding: 3px 8px; font-size: 0.75rem; border-radius: 4px; background: #1f6b4a; color: #f3efe6; border: none; font-weight: 700; cursor: pointer;">Full</button>
                                            </div>
                                        </div>
                                        <div class="bm-form-group" id="inlinePayRefGroup" style="margin-bottom: 0; display:none;">
                                            <label style="font-size: 11px; font-weight: 700; color: #1f6b4a; text-transform: uppercase; margin-bottom: 4px; display: block;">Reference No.</label>
                                            <input type="text" id="inlinePayRef" placeholder="UPI txn / Cheque no." style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid #1f6b4a; border-radius: 6px; background: #f3efe6; color: #1f6b4a; font-weight: 600;">
                                        </div>
                                    </div>

                                    <!-- Refund extra fields -->
                                    <div id="inlineRefundExtraFields" style="display:none; margin-bottom: 12px;">
                                        <div class="bm-form-row two-col">
                                            <div class="bm-form-group" style="margin-bottom: 0;">
                                                <label style="font-size: 11px; font-weight: 700; color: #1f6b4a; text-transform: uppercase; margin-bottom: 4px; display: block;">Refund Reason <span class="req">*</span></label>
                                                <input type="text" id="inlineRefundReason" placeholder="Reason for refund (required)" style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid #1f6b4a; border-radius: 6px; background: #f3efe6; color: #1f6b4a;">
                                            </div>
                                            <div class="bm-form-group" style="margin-bottom: 0;">
                                                <label style="font-size: 11px; font-weight: 700; color: #1f6b4a; text-transform: uppercase; margin-bottom: 4px; display: block;">Authorized By <span class="req">*</span></label>
                                                <input type="text" id="inlineRefundApprovedBy" placeholder="Manager / Doctor name" style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid #1f6b4a; border-radius: 6px; background: #f3efe6; color: #1f6b4a;">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Discount / Concession row -->
                                    <div class="bm-form-row two-col" style="margin-bottom: 12px;">
                                        <div class="bm-form-group" style="margin-bottom: 0;">
                                            <label style="font-size: 11px; font-weight: 700; color: #1f6b4a; text-transform: uppercase; margin-bottom: 4px; display: block;">
                                                Discount / Concession
                                                <span style="font-size: 10px; opacity: 0.8; font-weight: 500; text-transform: none;">(₹ or %)</span>
                                            </label>
                                            <div style="display: flex; gap: 8px;">
                                                <div style="position: relative; width: 60%;">
                                                    <span style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); font-size: 12px; color: #1f6b4a; font-weight: 700;">₹</span>
                                                    <input type="number" id="inlinePayDiscount" min="0" step="0.01" placeholder="0.00" oninput="billing.calcInlineDiscountAmt()" style="width: 100%; height: 38px; padding: 0 10px 0 22px; border: 1px solid #1f6b4a; border-radius: 6px; background: #f3efe6; color: #1f6b4a; font-weight: 700; font-size: 14px;">
                                                </div>
                                                <div style="position: relative; width: 40%;">
                                                    <input type="number" id="inlinePayDiscountPct" min="0" max="100" step="0.1" placeholder="0" oninput="billing.calcInlineDiscountPct()" style="width: 100%; height: 38px; padding: 0 22px 0 8px; border: 1px solid #1f6b4a; border-radius: 6px; background: #f3efe6; color: #1f6b4a; font-weight: 700; font-size: 14px;">
                                                    <span style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); font-size: 12px; color: #1f6b4a; font-weight: 700;">%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="bm-form-group" style="margin-bottom: 0;">
                                            <label style="font-size: 11px; font-weight: 700; color: #1f6b4a; text-transform: uppercase; margin-bottom: 4px; display: block;">Discount Reason</label>
                                            <input type="text" id="inlinePayDiscountReason" placeholder="Reason (e.g. Concession, Staff)" style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid #1f6b4a; border-radius: 6px; background: #f3efe6; color: #1f6b4a; font-weight: 600;">
                                        </div>
                                    </div>

                                    <div class="bm-form-group" style="margin-bottom: 14px;">
                                        <label style="font-size: 11px; font-weight: 700; color: #1f6b4a; text-transform: uppercase; margin-bottom: 4px; display: block;">Remarks</label>
                                        <input type="text" id="inlinePayRemarks" placeholder="Optional notes" style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid #1f6b4a; border-radius: 6px; background: #f3efe6; color: #1f6b4a; font-weight: 600;">
                                    </div>

                                    <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 10px; border-top: 1px solid rgba(31,107,74,0.15);">
                                        <div style="font-size: 13px; color: #1f6b4a; font-weight: 600;">
                                            Balance after payment: <strong id="inlinePayAfterVal" style="color: #166534; font-size: 15px; font-weight: 800;">—</strong>
                                        </div>
                                        <button class="bm-btn bm-btn-primary" id="btnSaveInlinePayment" onclick="billing.saveInlinePayment()" style="background: #1f6b4a; color: #f3efe6; padding: 10px 24px; border-radius: 6px; font-weight: 800; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 13px; box-shadow: 0 2px 8px rgba(31,107,74,0.25);">
                                            <i data-lucide="check-circle-2" style="width: 18px; height: 18px;"></i> Record Payment
                                        </button>
                                    </div>
                                </div>
                                <div class="payments-table-wrap">
                                    <table class="billing-items-table" id="paymentsTable">
                                        <thead>
                                            <tr>
                                                <th style="width: 50px; text-align: center;">#</th>
                                                <th>Date</th>
                                                <th>Type</th>
                                                <th>Mode</th>
                                                <th>Amount</th>
                                                <th>Reference</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="paymentsTableBody">
                                            <tr class="empty-row" id="paymentsEmptyRow">
                                                <td colspan="7">
                                                    <div class="table-empty-state">
                                                        <i data-lucide="wallet"></i>
                                                        <p>No payments recorded yet</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- ───────── RIGHT PANEL (Financial Summary) ───────── -->
                        <div class="billing-right-panel">
                            <div class="financial-summary-card" id="financialSummaryCard">
                                <div class="fs-header">
                                    <i data-lucide="pie-chart"></i> Financial Summary
                                </div>

                                <div class="fs-section">
                                    <?php
                                    $categories = [
                                        ['ROOM_RENT',    'Room Rent',        'room_charges'],
                                        ['DOCTOR_VISIT', 'Doctor Visit',     'doctor_charges'],
                                        ['LAB',          'Laboratory',       'lab_charges'],
                                        ['RADIOLOGY',    'Radiology',        'radiology_charges'],
                                        ['PHARMACY',     'Pharmacy',         'pharmacy_charges'],
                                        ['OT',           'Operation Theatre','ot_charges'],
                                        ['PROCEDURE',    'Procedure & Nursing', 'procedure_charges'],
                                        ['CONSUMABLE',   'Consumables',      'consumable_charges'],
                                        ['MISC',         'Miscellaneous',    'other_charges'],
                                    ];
                                    foreach ($categories as [$type, $label, $col]):
                                    ?>
                                    <div class="fs-cat-row" id="fsCat_<?= $type ?>" data-col="<?= $col ?>">
                                        <div class="fs-cat-label"><?= $label ?></div>
                                        <div class="fs-cat-val" id="fsVal_<?= $type ?>">₹0</div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="fs-divider"></div>

                                <div class="fs-row discount-row">
                                    <span class="fs-label">Discount <button class="btn-edit-discount" onclick="billing.openDiscountModal()"><i data-lucide="pen-line"></i></button></span>
                                    <span class="fs-val red" id="fsDiscount">-₹0.00</span>
                                </div>
                                
                                <div class="fs-insurance-block" id="fsInsuranceBlock" style="display:none;">
                                    <div class="fs-row"><span class="fs-label">Ins. Approved</span><span class="fs-val blue" id="fsInsApproved">₹0.00</span></div>
                                    <div class="fs-row"><span class="fs-label">Ins. Received</span><span class="fs-val green" id="fsInsReceived">₹0.00</span></div>
                                    <div class="fs-row"><span class="fs-label">Patient Payable</span><span class="fs-val bold" id="fsPatientPayable">₹0.00</span></div>
                                </div>

                                <div class="fs-grand-total" id="fsGrandTotalBox">
                                    <span>GRAND TOTAL</span>
                                    <span id="fsGrandTotal">₹0.00</span>
                                </div>

                                <div class="fs-row">
                                    <span class="fs-label">Advance Paid</span>
                                    <span class="fs-val green" id="fsAmountPaid">₹0.00</span>
                                </div>

                                <div class="fs-balance-box" id="fsBalanceBox">
                                    <span>BALANCE DUE</span>
                                    <span id="fsBalanceDue">₹0.00</span>
                                </div>

                                <div class="fs-action-buttons">
                                    <button class="fs-btn fs-btn-payment" onclick="billing.openPaymentModal('PARTIAL')">
                                        <i data-lucide="plus-circle"></i> Record Payment
                                    </button>
                                    <button class="fs-btn fs-btn-discount" onclick="billing.openDiscountModal()">
                                        <i data-lucide="tag"></i> Discount
                                    </button>
                                    <button class="fs-btn fs-btn-ins" id="fsBtnIns" onclick="billing.openInsuranceModal()">
                                        <i data-lucide="shield"></i> Insurance
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════ TOAST CONTAINER ═══════════ -->
                <div class="billing-toast-container" id="billingToastContainer"></div>

            </main>

    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════
     MODALS
══════════════════════════════════════════════════════════════════ -->

<!-- MODAL: Add Charge (Nurse Workspace Pattern Forms) -->
<div class="billing-modal-overlay" id="modalAddCharge">
    <div class="billing-modal" style="max-width:680px;">
        <div class="bm-head">
            <div class="bm-title"><i class="fas fa-plus-circle"></i> Add Billing Charge</div>
            <button class="bm-close" onclick="billing.closeModal('modalAddCharge')"><i data-lucide="x"></i></button>
        </div>
        <div class="bm-body">
            <!-- ── Top Category Tabs ── -->
            <div class="treatment-subtabs" style="margin-bottom: 16px;">
                <button type="button" class="t-tab active" data-tab="tab-doctor" onclick="billing.selectSubTab('tab-doctor', this)">
                    <i class="fas fa-user-md"></i> Doctor Visit
                </button>
                <button type="button" class="t-tab" data-tab="tab-lab" onclick="billing.selectSubTab('tab-lab', this)">
                    <i class="fas fa-flask"></i> Lab Test
                </button>
                <button type="button" class="t-tab" data-tab="tab-radiology" onclick="billing.selectSubTab('tab-radiology', this)">
                    <i class="fas fa-radiation"></i> Radiology
                </button>
                <button type="button" class="t-tab" data-tab="tab-other-services" onclick="billing.selectSubTab('tab-other-services', this)">
                    <i class="fas fa-stethoscope"></i> Other Services
                </button>
                <button type="button" class="t-tab" data-tab="tab-pharmacy" onclick="billing.selectSubTab('tab-pharmacy', this)">
                    <i class="fas fa-pills"></i> Pharmacy
                </button>
                <button type="button" class="t-tab" data-tab="tab-dialysis" onclick="billing.selectSubTab('tab-dialysis', this)">
                    <i class="fas fa-filter"></i> 14. Dialysis Chart
                </button>
                <button type="button" class="t-tab" data-tab="tab-oxygen" onclick="billing.selectSubTab('tab-oxygen', this)">
                    <i class="fas fa-lungs"></i> 15. Oxygen Chart
                </button>
                <button type="button" class="t-tab" data-tab="tab-ventilator" onclick="billing.selectSubTab('tab-ventilator', this)">
                    <i class="fas fa-procedures"></i> 16. Ventilation Chart
                </button>
                <button type="button" class="t-tab" data-tab="tab-transfusion" onclick="billing.selectSubTab('tab-transfusion', this)">
                    <i class="fas fa-syringe"></i> 17. Blood Transfusion
                </button>
                <button type="button" class="t-tab" data-tab="tab-ward-transfer" onclick="billing.selectSubTab('tab-ward-transfer', this)">
                    <i class="fas fa-exchange-alt"></i> 18. Ward Transfer
                </button>
                <button type="button" class="t-tab" data-tab="tab-consumables" onclick="billing.selectSubTab('tab-consumables', this)">
                    <i class="fas fa-box"></i> Consumables & Other
                </button>
            </div>

            <!-- ══════════════════════════════════════════════════════════
                 1. DOCTOR VISIT (Clean 2-Field Focus with Advance Search)
                 ══════════════════════════════════════════════════════════ -->
            <div class="t-panel active" id="tab-doctor">
                <div class="t-title"><i class="fas fa-user-md"></i> Consultant Round Visit</div>
                
                <!-- Advance Search Doctor Bar -->
                <div class="fmg" style="position: relative; margin-bottom: 10px;">
                    <label>Doctor Name <span class="req">*</span> <span class="badge"><i class="fas fa-search"></i> Advance Search Doctor</span></label>
                    <input type="text" id="doc-search-input" placeholder="Type doctor name, specialization, or ID (e.g. Dr. Girish, Cardiology, DOC011)..." autocomplete="off">
                    <div id="doc-results"></div>
                </div>

                <div class="fg">
                    <div class="fmg">
                        <label>Selected Doctor Name <span class="req">*</span></label>
                        <input type="text" id="doc-name" placeholder="Selected doctor name" readonly style="font-weight:700;">
                    </div>
                    <div class="fmg">
                        <label>Round Shift <span class="req">*</span></label>
                        <select id="doc-shift">
                            <option value="Morning">🌅 Morning Round</option>
                            <option value="Afternoon">☀️ Afternoon Round</option>
                            <option value="Evening">🌇 Evening Round</option>
                            <option value="Night">🌙 Night Emergency Visit</option>
                            <option value="Specialist Consultation">🩺 Specialist Consultation</option>
                        </select>
                    </div>
                    <div class="fmg">
                        <label>Visit Date <span class="req">*</span></label>
                        <input type="date" id="doc-date">
                    </div>
                    <div class="fmg">
                        <label>Visit Time <span class="req">*</span></label>
                        <input type="time" id="doc-time">
                    </div>
                    <div class="fmg">
                        <label>Entered By (Logged-in User)</label>
                        <input type="text" id="doc-user" value="<?php echo htmlspecialchars($userName); ?>" readonly style="opacity: 0.9; font-weight: 600;">
                    </div>
                    <div class="fmg">
                        <label>Consultation Fee (₹) <span class="req">*</span></label>
                        <input type="number" id="doc-fee" value="500" min="0" step="0.01" oninput="billing.calcDoctorTotal()">
                    </div>
                    <div class="fmg">
                        <label>Discount (₹)</label>
                        <input type="number" id="doc-discount" value="0" min="0" step="0.01" oninput="billing.calcDoctorTotal()">
                    </div>
                    <div class="fmg" style="grid-column: 1 / -1;">
                        <label>Doctor Remarks / Round Instructions</label>
                        <input type="text" id="doc-notes" placeholder="Enter doctor round notes, observations, or instructions...">
                    </div>
                </div>

                <div class="bm-total-preview" style="margin-top: 14px;">
                    <span>Total Amount:</span>
                    <span id="doc-total-preview" class="bm-total-val">₹ 500.00</span>
                </div>

                <div class="bm-footer" style="margin-top: 14px;">
                    <button type="button" class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalAddCharge')">Cancel</button>
                    <button type="button" class="bm-btn bm-btn-primary" id="doc-save-btn" onclick="billing.saveDoctorVisitCharge()">
                        <i class="fas fa-plus"></i> Add Round Visit Charge
                    </button>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════
                 2. LAB TEST (Table: lab_services with Room-Tier Pricing)
                 ══════════════════════════════════════════════════════════ -->
            <div class="t-panel" id="tab-lab">
                <div class="t-title"><i class="fas fa-flask"></i> Laboratory Test Order</div>
                
                <div class="fmg" style="position: relative; margin-bottom: 10px;">
                    <label>Test Name <span class="req">*</span> <span class="badge"><i class="fas fa-search"></i> Advance Search Lab Tests (Table: lab_services)</span></label>
                    <input type="text" id="lab-input" placeholder="Type lab test name e.g. CBC, Lipid Profile, Blood Urea, Fasting Blood Sugar..." autocomplete="off">
                    <div id="lab-results"></div>
                </div>

                <div class="fg">
                    <div class="fmg">
                        <label>Selected Test Name <span class="req">*</span></label>
                        <input type="text" id="lab-name" placeholder="Selected lab test" readonly style="font-weight:700;">
                    </div>
                    <div class="fmg">
                        <label>Test Code / ID</label>
                        <input type="text" id="lab-code" placeholder="Auto-filled" readonly style="opacity: 0.9;">
                    </div>
                    <div class="fmg">
                        <label>Order Date <span class="req">*</span></label>
                        <input type="date" id="lab-date">
                    </div>
                    <div class="fmg">
                        <label>Patient Room Tier Rate</label>
                        <input type="text" id="lab-tier" placeholder="Auto-calculated rate" readonly style="opacity: 0.9; font-weight:600;">
                    </div>
                    <div class="fmg">
                        <label>Entered By (Logged-in User)</label>
                        <input type="text" id="lab-user" value="<?php echo htmlspecialchars($userName); ?>" readonly style="opacity: 0.9; font-weight:600;">
                    </div>
                    <div class="fmg">
                        <label>Test Amount (₹) <span class="req">*</span></label>
                        <input type="number" id="lab-fee" value="0" min="0" step="0.01" oninput="billing.calcLabTotal()">
                    </div>
                    <div class="fmg">
                        <label>Discount (₹)</label>
                        <input type="number" id="lab-discount" value="0" min="0" step="0.01" oninput="billing.calcLabTotal()">
                    </div>
                    <div class="fmg" style="grid-column: 1 / -1;">
                        <label>Clinical Notes / Specimen Remarks</label>
                        <input type="text" id="lab-notes" placeholder="Optional specimen / clinical notes">
                    </div>
                </div>

                <div class="bm-total-preview" style="margin-top: 14px;">
                    <span>Total Amount:</span>
                    <span id="lab-total-preview" class="bm-total-val">₹ 0.00</span>
                </div>

                <div class="bm-footer" style="margin-top: 14px;">
                    <button type="button" class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalAddCharge')">Cancel</button>
                    <button type="button" class="bm-btn bm-btn-primary" id="lab-save-btn" onclick="billing.saveLabCharge()">
                        <i class="fas fa-plus"></i> Add Lab Test Charge
                    </button>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════
                 3. RADIOLOGY (Table: radiology_services with Room-Tier Pricing)
                 ══════════════════════════════════════════════════════════ -->
            <div class="t-panel" id="tab-radiology">
                <div class="t-title"><i class="fas fa-radiation"></i> Radiology Investigation</div>
                
                <div class="fmg" style="position: relative; margin-bottom: 10px;">
                    <label>Radiology Test Name <span class="req">*</span> <span class="badge"><i class="fas fa-search"></i> Advance Search Radiology (Table: radiology_services)</span></label>
                    <input type="text" id="rad-input" placeholder="Type radiology test name e.g. X-Ray Chest, CT Brain, USG Abdomen, MRI..." autocomplete="off">
                    <div id="rad-results"></div>
                </div>

                <div class="fg">
                    <div class="fmg">
                        <label>Selected Investigation <span class="req">*</span></label>
                        <input type="text" id="rad-name" placeholder="Selected radiology test" readonly style="font-weight:700;">
                    </div>
                    <div class="fmg">
                        <label>Modality / Code</label>
                        <input type="text" id="rad-code" placeholder="Auto-filled" readonly style="opacity: 0.9;">
                    </div>
                    <div class="fmg">
                        <label>Order Date <span class="req">*</span></label>
                        <input type="date" id="rad-date">
                    </div>
                    <div class="fmg">
                        <label>Room Tier Rate Applied</label>
                        <input type="text" id="rad-tier" placeholder="Auto-calculated rate" readonly style="opacity: 0.9; font-weight:600;">
                    </div>
                    <div class="fmg">
                        <label>Entered By (Logged-in User)</label>
                        <input type="text" id="rad-user" value="<?php echo htmlspecialchars($userName); ?>" readonly style="opacity: 0.9; font-weight:600;">
                    </div>
                    <div class="fmg">
                        <label>Radiology Amount (₹) <span class="req">*</span></label>
                        <input type="number" id="rad-fee" value="0" min="0" step="0.01" oninput="billing.calcRadTotal()">
                    </div>
                    <div class="fmg">
                        <label>Discount (₹)</label>
                        <input type="number" id="rad-discount" value="0" min="0" step="0.01" oninput="billing.calcRadTotal()">
                    </div>
                    <div class="fmg" style="grid-column: 1 / -1;">
                        <label>Clinical History / Instructions</label>
                        <input type="text" id="rad-notes" placeholder="e.g. Rule out fracture, Contrast study notes...">
                    </div>
                </div>

                <div class="bm-total-preview" style="margin-top: 14px;">
                    <span>Total Amount:</span>
                    <span id="rad-total-preview" class="bm-total-val">₹ 0.00</span>
                </div>

                <div class="bm-footer" style="margin-top: 14px;">
                    <button type="button" class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalAddCharge')">Cancel</button>
                    <button type="button" class="bm-btn bm-btn-primary" id="rad-save-btn" onclick="billing.saveRadCharge()">
                        <i class="fas fa-plus"></i> Add Radiology Charge
                    </button>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════
                 4. OTHER SERVICES (Table: other_services with Room-Tier Pricing)
                 ══════════════════════════════════════════════════════════ -->
            <div class="t-panel" id="tab-other-services">
                <div class="t-title"><i class="fas fa-stethoscope"></i> Hospital Services & Procedures</div>
                
                <div class="fmg" style="position: relative; margin-bottom: 10px;">
                    <label>Service Name <span class="req">*</span> <span class="badge"><i class="fas fa-search"></i> Advance Search Other Services (Table: other_services)</span></label>
                    <input type="text" id="other-input" placeholder="Type service name e.g. ECG, Nebulization, Dressing, Physiotherapy, Nursing..." autocomplete="off">
                    <div id="other-results"></div>
                </div>

                <!-- Advance Search Doctor for Other Services -->
                <div class="fmg" style="position: relative; margin-bottom: 10px;">
                    <label>Performing Doctor / Attending Staff <span class="badge"><i class="fas fa-search"></i> Advance Search Doctor</span></label>
                    <input type="text" id="proc-doc-search" placeholder="Type doctor or consultant name..." autocomplete="off">
                    <div id="proc-doc-results"></div>
                </div>

                <div class="fg">
                    <div class="fmg">
                        <label>Selected Service <span class="req">*</span></label>
                        <input type="text" id="other-name" placeholder="Selected service name" readonly style="font-weight:700;">
                    </div>
                    <div class="fmg">
                        <label>Doctor / Performed By</label>
                        <input type="text" id="proc-doctor" placeholder="Selected doctor name" readonly style="font-weight:700;">
                    </div>
                    <div class="fmg">
                        <label>Service Date <span class="req">*</span></label>
                        <input type="date" id="other-date">
                    </div>
                    <div class="fmg">
                        <label>Quantity</label>
                        <input type="number" id="other-qty" value="1" min="1" step="1" oninput="billing.calcOtherTotal()">
                    </div>
                    <div class="fmg">
                        <label>Room Tier Rate Applied</label>
                        <input type="text" id="other-tier" placeholder="Auto-calculated rate" readonly style="opacity: 0.9; font-weight:600;">
                    </div>
                    <div class="fmg">
                        <label>Entered By (Logged-in User)</label>
                        <input type="text" id="other-user" value="<?php echo htmlspecialchars($userName); ?>" readonly style="opacity: 0.9; font-weight:600;">
                    </div>
                    <div class="fmg">
                        <label>Unit Fee (₹) <span class="req">*</span></label>
                        <input type="number" id="other-fee" value="0" min="0" step="0.01" oninput="billing.calcOtherTotal()">
                    </div>
                    <div class="fmg">
                        <label>Discount (₹)</label>
                        <input type="number" id="other-discount" value="0" min="0" step="0.01" oninput="billing.calcOtherTotal()">
                    </div>
                    <div class="fmg" style="grid-column: 1 / -1;">
                        <label>Service Remarks / Instructions</label>
                        <input type="text" id="other-notes" placeholder="Optional service notes">
                    </div>
                </div>

                <div class="bm-total-preview" style="margin-top: 14px;">
                    <span>Total Amount:</span>
                    <span id="other-total-preview" class="bm-total-val">₹ 0.00</span>
                </div>

                <div class="bm-footer" style="margin-top: 14px;">
                    <button type="button" class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalAddCharge')">Cancel</button>
                    <button type="button" class="bm-btn bm-btn-primary" id="other-save-btn" onclick="billing.saveOtherCharge()">
                        <i class="fas fa-plus"></i> Add Service Charge
                    </button>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════
                 5. PHARMACY MEDICINES (Inventory search & order)
                 ══════════════════════════════════════════════════════════ -->
            <div class="t-panel" id="tab-pharmacy">
                <div class="t-title"><i class="fas fa-pills"></i> Pharmacy Medicine Order</div>
                <div class="fmg" style="margin-bottom: 8px;">
                    <label>Dispense Date <span class="req">*</span></label>
                    <input type="date" id="ph-date" style="max-width: 200px;">
                </div>
                <div class="fmg" style="position: relative;">
                    <label>Search Medicine from Pharmacy <span class="req">*</span> <span class="badge"><i class="fas fa-search"></i> Live Inventory</span></label>
                    <input type="text" id="ph-input" placeholder="Type brand or generic name e.g. Paracetamol, DNS, Pantoprazole..." autocomplete="off">
                    <div id="ph-results"></div>
                </div>

                <!-- Pharmacy Cart -->
                <div id="ph-cart" style="margin-top: 10px; max-height: 220px; overflow-y: auto;"></div>

                <div class="fmg" style="margin-top: 6px;">
                    <label>Dosage / Administration Instructions</label>
                    <input type="text" id="ph-notes" placeholder="e.g. 1 vial IV BD x 3 days / stat dose">
                </div>

                <div class="bm-total-preview" style="margin-top: 12px;">
                    <span>Total Pharmacy Amount:</span>
                    <span id="ph-total-preview" class="bm-total-val">₹ 0.00</span>
                </div>

                <div class="bm-footer" style="margin-top: 14px;">
                    <button type="button" class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalAddCharge')">Cancel</button>
                    <button type="button" class="bm-btn bm-btn-primary" id="ph-save-btn" onclick="billing.savePharmacyOrder()">
                        <i class="fas fa-paper-plane"></i> Submit Pharmacy Order
                    </button>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════
                 6. DIALYSIS RECORD (14. dialysis_chart from nurse_workspace.php)
                 ══════════════════════════════════════════════════════════ -->
            <div class="t-panel" id="tab-dialysis">
                <div class="t-title"><i class="fas fa-filter"></i> 14. Dialysis Record (dialysis_chart)</div>
                
                <!-- Advance Search Doctor -->
                <div class="fmg" style="position: relative; margin-bottom: 8px;">
                    <label>Doctor Name <span class="req">*</span> <span class="badge"><i class="fas fa-search"></i> Advance Search Doctor</span></label>
                    <input type="text" id="dia-doc-search" placeholder="Type doctor / nephrologist name..." autocomplete="off">
                    <div id="dia-doc-results"></div>
                </div>

                <div class="fg">
                    <div class="fmg">
                        <label>Selected Doctor Name <span class="req">*</span></label>
                        <input type="text" id="dia-doctor" placeholder="Selected doctor name" readonly style="font-weight:700;">
                    </div>
                    <div class="fmg">
                        <label>Date (dia_date) <span class="req">*</span></label>
                        <input type="date" id="dia-date">
                    </div>
                    <div class="fmg">
                        <label>Duration (dia_dur) <span class="req">*</span></label>
                        <input type="text" id="dia-dur" placeholder="Auto / e.g. 4h">
                    </div>
                    <div class="fmg">
                        <label>Start Time (dia_start)</label>
                        <input type="time" id="dia-start" onchange="billing.calcDiaDuration()">
                    </div>
                    <div class="fmg">
                        <label>End Time (dia_end)</label>
                        <input type="time" id="dia-end" onchange="billing.calcDiaDuration()">
                    </div>
                    <div class="fmg">
                        <label>Nurse Signature (dia_nurse) <span class="req">*</span></label>
                        <input type="text" id="dia-nurse" value="<?php echo htmlspecialchars($userName); ?>" readonly style="font-weight:600; opacity:0.9;">
                    </div>
                    <div class="fmg">
                        <label>Dialysis Charge (₹) <span class="req">*</span></label>
                        <input type="number" id="dia-fee" value="2500" min="0" step="0.01" oninput="billing.calcDiaTotal()">
                    </div>
                    <div class="fmg">
                        <label>Discount (₹)</label>
                        <input type="number" id="dia-discount" value="0" min="0" step="0.01" oninput="billing.calcDiaTotal()">
                    </div>
                </div>

                <div class="bm-total-preview" style="margin-top: 14px;">
                    <span>Total Amount:</span>
                    <span id="dia-total-preview" class="bm-total-val">₹ 2,500.00</span>
                </div>

                <div class="bm-footer" style="margin-top: 14px;">
                    <button type="button" class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalAddCharge')">Cancel</button>
                    <button type="button" class="bm-btn bm-btn-primary" id="dia-save-btn" onclick="billing.saveDialysisCharge()">
                        <i class="fas fa-plus"></i> Add Dialysis Charge
                    </button>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════
                 7. OXYGEN THERAPY (15. oxygen_chart from nurse_workspace.php)
                 ══════════════════════════════════════════════════════════ -->
            <div class="t-panel" id="tab-oxygen">
                <div class="t-title"><i class="fas fa-lungs"></i> 15. Oxygen Therapy (oxygen_chart)</div>
                
                <!-- Advance Search Doctor -->
                <div class="fmg" style="position: relative; margin-bottom: 8px;">
                    <label>Doctor Name <span class="req">*</span> <span class="badge"><i class="fas fa-search"></i> Advance Search Doctor</span></label>
                    <input type="text" id="oxy-doc-search" placeholder="Type prescribing doctor name..." autocomplete="off">
                    <div id="oxy-doc-results"></div>
                </div>

                <div class="fg">
                    <div class="fmg">
                        <label>Selected Doctor Name <span class="req">*</span></label>
                        <input type="text" id="oxy-doctor" placeholder="Selected doctor name" readonly style="font-weight:700;">
                    </div>
                    <div class="fmg">
                        <label>Date (oxy_date) <span class="req">*</span></label>
                        <input type="date" id="oxy-date">
                    </div>
                    <div class="fmg">
                        <label>Flow Rate (L/min) (oxy_flow) <span class="req">*</span></label>
                        <input type="text" id="oxy-flow" placeholder="e.g. 2 L/min">
                    </div>
                    <div class="fmg">
                        <label>Start Time (oxy_start)</label>
                        <input type="time" id="oxy-start" onchange="billing.calcOxyDuration()">
                    </div>
                    <div class="fmg">
                        <label>End Time (oxy_end)</label>
                        <input type="time" id="oxy-end" onchange="billing.calcOxyDuration()">
                    </div>
                    <div class="fmg">
                        <label>Duration (oxy_dur) <span class="req">*</span></label>
                        <input type="text" id="oxy-dur" placeholder="Auto / e.g. 2h">
                    </div>
                    <div class="fmg">
                        <label>Nurse Signature (oxy_nurse) <span class="req">*</span></label>
                        <input type="text" id="oxy-nurse" value="<?php echo htmlspecialchars($userName); ?>" readonly style="font-weight:600; opacity:0.9;">
                    </div>
                    <div class="fmg">
                        <label>Oxygen Charge (₹) <span class="req">*</span></label>
                        <input type="number" id="oxy-fee" value="500" min="0" step="0.01" oninput="billing.calcOxyTotal()">
                    </div>
                    <div class="fmg">
                        <label>Discount (₹)</label>
                        <input type="number" id="oxy-discount" value="0" min="0" step="0.01" oninput="billing.calcOxyTotal()">
                    </div>
                </div>

                <div class="bm-total-preview" style="margin-top: 14px;">
                    <span>Total Amount:</span>
                    <span id="oxy-total-preview" class="bm-total-val">₹ 500.00</span>
                </div>

                <div class="bm-footer" style="margin-top: 14px;">
                    <button type="button" class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalAddCharge')">Cancel</button>
                    <button type="button" class="bm-btn bm-btn-primary" id="oxy-save-btn" onclick="billing.saveOxygenCharge()">
                        <i class="fas fa-plus"></i> Add Oxygen Charge
                    </button>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════
                 8. VENTILATOR SUPPORT (16. ventilation_chart from nurse_workspace.php)
                 ══════════════════════════════════════════════════════════ -->
            <div class="t-panel" id="tab-ventilator">
                <div class="t-title"><i class="fas fa-procedures"></i> 16. Ventilator Support (ventilation_chart)</div>
                
                <!-- Advance Search Doctor -->
                <div class="fmg" style="position: relative; margin-bottom: 8px;">
                    <label>Doctor Name <span class="req">*</span> <span class="badge"><i class="fas fa-search"></i> Advance Search Doctor</span></label>
                    <input type="text" id="vent-doc-search" placeholder="Type doctor / intensivist name..." autocomplete="off">
                    <div id="vent-doc-results"></div>
                </div>

                <div class="fg">
                    <div class="fmg">
                        <label>Selected Doctor Name <span class="req">*</span></label>
                        <input type="text" id="vent-doctor" placeholder="Selected doctor name" readonly style="font-weight:700;">
                    </div>
                    <div class="fmg">
                        <label>Date (vent_date) <span class="req">*</span></label>
                        <input type="date" id="vent-date">
                    </div>
                    <div class="fmg">
                        <label>Vent Mode (vent_mode) <span class="req">*</span></label>
                        <select id="vent-mode">
                            <option value="CMV">CMV</option>
                            <option value="SIMV">SIMV</option>
                            <option value="CPAP">CPAP</option>
                            <option value="BiPAP">BiPAP</option>
                        </select>
                    </div>
                    <div class="fmg">
                        <label>Start Time (vent_start)</label>
                        <input type="time" id="vent-start" onchange="billing.calcVentDuration()">
                    </div>
                    <div class="fmg">
                        <label>End Time (vent_end)</label>
                        <input type="time" id="vent-end" onchange="billing.calcVentDuration()">
                    </div>
                    <div class="fmg">
                        <label>Duration (vent_dur) <span class="req">*</span></label>
                        <input type="text" id="vent-dur" placeholder="Auto / e.g. 6h">
                    </div>
                    <div class="fmg">
                        <label>Nurse Signature (vent_nurse) <span class="req">*</span></label>
                        <input type="text" id="vent-nurse" value="<?php echo htmlspecialchars($userName); ?>" readonly style="font-weight:600; opacity:0.9;">
                    </div>
                    <div class="fmg">
                        <label>Ventilator Charge (₹) <span class="req">*</span></label>
                        <input type="number" id="vent-fee" value="2000" min="0" step="0.01" oninput="billing.calcVentTotal()">
                    </div>
                    <div class="fmg">
                        <label>Discount (₹)</label>
                        <input type="number" id="vent-discount" value="0" min="0" step="0.01" oninput="billing.calcVentTotal()">
                    </div>
                </div>

                <div class="bm-total-preview" style="margin-top: 14px;">
                    <span>Total Amount:</span>
                    <span id="vent-total-preview" class="bm-total-val">₹ 2,000.00</span>
                </div>

                <div class="bm-footer" style="margin-top: 14px;">
                    <button type="button" class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalAddCharge')">Cancel</button>
                    <button type="button" class="bm-btn bm-btn-primary" id="vent-save-btn" onclick="billing.saveVentilatorCharge()">
                        <i class="fas fa-plus"></i> Add Ventilator Charge
                    </button>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════
                 9. BLOOD TRANSFUSION (17. blood_transfusion_chart from nurse_workspace.php)
                 ══════════════════════════════════════════════════════════ -->
            <div class="t-panel" id="tab-transfusion">
                <div class="t-title"><i class="fas fa-syringe"></i> 17. Blood Transfusion Record (blood_transfusion)</div>
                
                <!-- Advance Search Doctor -->
                <div class="fmg" style="position: relative; margin-bottom: 8px;">
                    <label>Doctor Name <span class="req">*</span> <span class="badge"><i class="fas fa-search"></i> Advance Search Doctor</span></label>
                    <input type="text" id="bt-doc-search" placeholder="Type prescribing doctor name..." autocomplete="off">
                    <div id="bt-doc-results"></div>
                </div>

                <div class="fg">
                    <div class="fmg">
                        <label>Selected Doctor Name <span class="req">*</span></label>
                        <input type="text" id="bt-doctor" placeholder="Selected doctor name" readonly style="font-weight:700;">
                    </div>
                    <div class="fmg">
                        <label>Date (trans_date) <span class="req">*</span></label>
                        <input type="date" id="trans-date">
                    </div>
                    <div class="fmg">
                        <label>Blood Group (blood_group) <span class="req">*</span></label>
                        <input type="text" id="blood-group" placeholder="e.g. O+ / AB+ / A+">
                    </div>
                    <div class="fmg">
                        <label>Bag Number (bag_number) <span class="req">*</span></label>
                        <input type="text" id="bag-number" placeholder="e.g. 2563">
                    </div>
                    <div class="fmg">
                        <label>Qty (ml) (quantity) <span class="req">*</span></label>
                        <input type="number" id="trans-qty" value="350" min="1" step="1">
                    </div>
                    <div class="fmg">
                        <label>Vitals During Transfusion (vitals_during)</label>
                        <input type="text" id="vitals-during" placeholder="BP, Pulse, Temp...">
                    </div>
                    <div class="fmg">
                        <label>Nurse Signature (nurse_sign) <span class="req">*</span></label>
                        <input type="text" id="bt-nurse" value="<?php echo htmlspecialchars($userName); ?>" readonly style="font-weight:600; opacity:0.9;">
                    </div>
                    <div class="fmg">
                        <label>Transfusion Charge (₹) <span class="req">*</span></label>
                        <input type="number" id="bt-fee" value="1200" min="0" step="0.01" oninput="billing.calcBtTotal()">
                    </div>
                    <div class="fmg">
                        <label>Discount (₹)</label>
                        <input type="number" id="bt-discount" value="0" min="0" step="0.01" oninput="billing.calcBtTotal()">
                    </div>
                </div>

                <div class="bm-total-preview" style="margin-top: 14px;">
                    <span>Total Amount:</span>
                    <span id="bt-total-preview" class="bm-total-val">₹ 1,200.00</span>
                </div>

                <div class="bm-footer" style="margin-top: 14px;">
                    <button type="button" class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalAddCharge')">Cancel</button>
                    <button type="button" class="bm-btn bm-btn-primary" id="bt-save-btn" onclick="billing.saveTransfusionCharge()">
                        <i class="fas fa-plus"></i> Add Transfusion Charge
                    </button>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════
                 10. WARD TRANSFER (18. ward_transfer from nurse_workspace.php)
                 ══════════════════════════════════════════════════════════ -->
            <div class="t-panel" id="tab-ward-transfer">
                <div class="t-title"><i class="fas fa-exchange-alt"></i> 18. Ward Transfer / Bed Shift</div>
                
                <!-- Advance Search Doctor -->
                <div class="fmg" style="position: relative; margin-bottom: 8px;">
                    <label>Doctor Name <span class="req">*</span> <span class="badge"><i class="fas fa-search"></i> Advance Search Doctor</span></label>
                    <input type="text" id="wt-doc-search" placeholder="Type authorising doctor name..." autocomplete="off">
                    <div id="wt-doc-results"></div>
                </div>

                <div class="fg">
                    <div class="fmg">
                        <label>Selected Doctor Name <span class="req">*</span></label>
                        <input type="text" id="wt-doctor" placeholder="Selected doctor name" readonly style="font-weight:700;">
                    </div>
                    <div class="fmg">
                        <label>Transfer Date <span class="req">*</span></label>
                        <input type="date" id="wt-date">
                    </div>
                    <div class="fmg">
                        <label>Transfer Time <span class="req">*</span></label>
                        <input type="time" id="wt-time">
                    </div>
                    <div class="fmg">
                        <label>From Ward / Bed <span class="req">*</span></label>
                        <input type="text" id="wt-from" placeholder="e.g. ICU / Bed 3">
                    </div>
                    <div class="fmg">
                        <label>To Ward / Bed <span class="req">*</span></label>
                        <input type="text" id="wt-to" placeholder="e.g. General Ward / Bed 12">
                    </div>
                    <div class="fmg">
                        <label>Staff Signature <span class="req">*</span></label>
                        <input type="text" id="wt-nurse" value="<?php echo htmlspecialchars($userName); ?>" readonly style="font-weight:600; opacity:0.9;">
                    </div>
                    <div class="fmg">
                        <label>Transfer Fee (₹)</label>
                        <input type="number" id="wt-fee" value="0" min="0" step="0.01" oninput="billing.calcWtTotal()">
                    </div>
                    <div class="fmg">
                        <label>Discount (₹)</label>
                        <input type="number" id="wt-discount" value="0" min="0" step="0.01" oninput="billing.calcWtTotal()">
                    </div>
                    <div class="fmg" style="grid-column: 1 / -1;">
                        <label>Transfer Reason / Notes</label>
                        <input type="text" id="wt-reason" placeholder="e.g. Condition stabilized, Step down to ward, Attender request">
                    </div>
                </div>

                <div class="bm-total-preview" style="margin-top: 14px;">
                    <span>Total Amount:</span>
                    <span id="wt-total-preview" class="bm-total-val">₹ 0.00</span>
                </div>

                <div class="bm-footer" style="margin-top: 14px;">
                    <button type="button" class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalAddCharge')">Cancel</button>
                    <button type="button" class="bm-btn bm-btn-primary" id="wt-save-btn" onclick="billing.saveWardTransferCharge()">
                        <i class="fas fa-plus"></i> Add Transfer Charge
                    </button>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════
                 11. CONSUMABLES & OTHER CHARGES
                 ══════════════════════════════════════════════════════════ -->
            <div class="t-panel" id="tab-consumables">
                <div class="t-title"><i class="fas fa-box"></i> Medical Consumables & Other Charges</div>
                <div class="fg">
                    <div class="fmg">
                        <label>Charge Date <span class="req">*</span></label>
                        <input type="date" id="misc-date">
                    </div>
                    <div class="fmg">
                        <label>Charge Category</label>
                        <select id="misc-type">
                            <option value="CONSUMABLE">🧪 Medical Consumables</option>
                            <option value="MISC">📦 Miscellaneous</option>
                            <option value="OTHER">📁 Other Charges</option>
                        </select>
                    </div>
                    <div class="fmg" style="grid-column: 1 / -1;">
                        <label>Description / Item Name <span class="req">*</span></label>
                        <input type="text" id="misc-desc" placeholder="e.g. IV Cannula 20G, Bandage Roll, Oxygen Mask, Ambulance...">
                    </div>
                    <div class="fmg">
                        <label>Department / Category</label>
                        <input type="text" id="misc-dept" placeholder="e.g. Nursing, General, Admin">
                    </div>
                    <div class="fmg">
                        <label>Quantity</label>
                        <input type="number" id="misc-qty" value="1" min="0.01" step="0.01" oninput="billing.calcConsumableTotal()">
                    </div>
                    <div class="fmg">
                        <label>Entered By (Logged-in User)</label>
                        <input type="text" id="misc-user" value="<?php echo htmlspecialchars($userName); ?>" readonly style="font-weight:600; opacity:0.9;">
                    </div>
                    <div class="fmg">
                        <label>Unit Price (₹) <span class="req">*</span></label>
                        <input type="number" id="misc-fee" value="0" min="0" step="0.01" oninput="billing.calcConsumableTotal()">
                    </div>
                    <div class="fmg">
                        <label>Discount (₹)</label>
                        <input type="number" id="misc-discount" value="0" min="0" step="0.01" oninput="billing.calcConsumableTotal()">
                    </div>
                    <div class="fmg" style="grid-column: 1 / -1;">
                        <label>Reference Notes</label>
                        <input type="text" id="misc-notes" placeholder="Optional reference notes">
                    </div>
                </div>

                <div class="bm-total-preview" style="margin-top: 14px;">
                    <span>Total Amount:</span>
                    <span id="misc-total-preview" class="bm-total-val">₹ 0.00</span>
                </div>

                <div class="bm-footer" style="margin-top: 14px;">
                    <button type="button" class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalAddCharge')">Cancel</button>
                    <button type="button" class="bm-btn bm-btn-primary" id="misc-save-btn" onclick="billing.saveConsumableCharge()">
                        <i class="fas fa-plus"></i> Add Consumable Charge
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- MODAL: Room Rent Generator -->
<div class="billing-modal-overlay" id="modalRoomRent">
    <div class="billing-modal" style="max-width:640px;">
        <div class="bm-head green-head">
            <div class="bm-title"><i data-lucide="bed-double"></i> Generate Room Rent</div>
            <button class="bm-close" onclick="billing.closeModal('modalRoomRent')"><i data-lucide="x"></i></button>
        </div>
        <div class="bm-body">
            <div class="rr-bed-info-card" id="rrBedInfoCard">
                <div class="rr-bed-details">
                    <div class="rr-bed-icon"><i data-lucide="bed-double"></i></div>
                    <div>
                        <div class="rr-bed-name" id="rrBedName">Loading...</div>
                        <div class="rr-bed-rate" id="rrBedRate"></div>
                    </div>
                </div>
                <div class="rr-rate-breakdown" id="rrRateBreakdown"></div>
            </div>
            <div class="bm-form-row two-col">
                <div class="bm-form-group">
                    <label>From Date <span class="req">*</span></label>
                    <input type="date" id="rrFromDate" onchange="billing.loadRoomRentPreview()">
                </div>
                <div class="bm-form-group">
                    <label>To Date <span class="req">*</span></label>
                    <input type="date" id="rrToDate" onchange="billing.loadRoomRentPreview()">
                </div>
            </div>
            <div class="rr-preview" id="rrPreview">
                <div class="rr-preview-loading"><i data-lucide="loader-2" class="lucide-spin"></i> Select dates to preview...</div>
            </div>
            <div class="rr-preview-summary" id="rrPreviewSummary" style="display:none;">
                <div class="rr-ps-item">
                    <span class="rr-ps-label">New Days</span>
                    <span class="rr-ps-val green" id="rrNewCount">0</span>
                </div>
                <div class="rr-ps-item">
                    <span class="rr-ps-label">Skipped (duplicate)</span>
                    <span class="rr-ps-val amber" id="rrSkipCount">0</span>
                </div>
                <div class="rr-ps-item">
                    <span class="rr-ps-label">Total to Add</span>
                    <span class="rr-ps-val bold" id="rrNewTotal">₹0</span>
                </div>
            </div>
            <div class="bm-footer">
                <button class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalRoomRent')">Cancel</button>
                <button class="bm-btn bm-btn-green" id="btnConfirmRoomRent" onclick="billing.confirmRoomRent()" disabled>
                    <i data-lucide="check-circle-2"></i> <span id="rrConfirmBtnLabel">Confirm & Generate</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Record Payment -->
<div class="billing-modal-overlay" id="modalPayment">
    
    <div class="billing-modal" style="max-width:520px;">
        <div class="bm-head">
            <div class="bm-title"><i data-lucide="credit-card"></i> Record Payment</div>
            <button class="bm-close" onclick="billing.closeModal('modalPayment')"><i data-lucide="x"></i></button>
        </div>
        <div class="bm-body">
            <div class="pay-balance-preview" id="payBalancePreview">
                <div class="pbp-label">Current Balance Due</div>
                <div class="pbp-val" id="payBalanceVal">₹0.00</div>
            </div>
            <div class="bm-form-row two-col">
                <div class="bm-form-group">
                    <label>Payment Date <span class="req">*</span></label>
                    <input type="date" id="payDate">
                </div>
                <div class="bm-form-group">
                    <label>Payment Type <span class="req">*</span></label>
                    <div class="pay-type-group" id="payTypeGroup">
                        <button class="pay-type-btn" data-type="ADVANCE">Advance</button>
                        <button class="pay-type-btn active" data-type="PARTIAL">Partial</button>
                        <button class="pay-type-btn" data-type="FINAL">Final</button>
                        <button class="pay-type-btn refund-btn" data-type="REFUND">Refund</button>
                    </div>
                </div>
            </div>
            <div class="bm-form-group">
                <label>Payment Mode <span class="req">*</span></label>
                <div class="pay-mode-group" id="payModeGroup">
                    <button class="pay-mode-btn active" data-mode="CASH"><i data-lucide="banknote"></i> Cash</button>
                    <button class="pay-mode-btn" data-mode="UPI"><i data-lucide="smartphone"></i> UPI</button>
                    <button class="pay-mode-btn" data-mode="CARD"><i data-lucide="credit-card"></i> Card</button>
                    <button class="pay-mode-btn" data-mode="BANK"><i data-lucide="landmark"></i> Bank</button>
                    <button class="pay-mode-btn" data-mode="CHEQUE"><i data-lucide="receipt"></i> Cheque</button>
                    <button class="pay-mode-btn" data-mode="INSURANCE"><i data-lucide="shield"></i> Insurance</button>
                </div>
            </div>

            <!-- Insurance / TPA Sub-section for Modal -->
            <div id="modalInsuranceBlock" style="display:none; background: rgba(31,107,74,0.05); border: 1.5px dashed #1f6b4a; border-radius: 8px; padding: 12px; margin-bottom: 12px;">
                <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 10px;">
                    <label style="font-size: 11px; font-weight: 800; color: #1f6b4a; text-transform: uppercase; margin: 0;">Sponsor Type <span class="req">*</span>:</label>
                    <div class="pay-type-group" id="modalSponsorTypeGroup" style="display: flex; gap: 6px;">
                        <button type="button" class="pay-type-btn active" data-sponsor-type="INSURANCE" style="padding: 4px 14px; min-height: 32px; font-size: 12px;"><i data-lucide="shield"></i> Insurance</button>
                        <button type="button" class="pay-type-btn" data-sponsor-type="TPA" style="padding: 4px 14px; min-height: 32px; font-size: 12px;"><i data-lucide="building-2"></i> TPA</button>
                    </div>
                </div>

                <div style="position: relative;">
                    <label style="font-size: 11px; font-weight: 700; color: #1f6b4a; text-transform: uppercase; margin-bottom: 4px; display: block;">
                        <span id="modalSponsorLabel">Insurance Company Name</span> <span class="req">*</span>
                        <span style="background: #1f6b4a; color: #f3efe6; font-size: 10px; padding: 2px 6px; border-radius: 4px; margin-left: 6px; font-weight: 700;"><i class="fas fa-search"></i> Advance Search</span>
                    </label>
                    <input type="text" id="modalSponsorSearchInput" placeholder="Type to search Insurance company (e.g. Star Health, HDFC ERGO...)" autocomplete="off" style="width: 100%; height: 38px; padding: 0 10px; border: 1.5px solid #1f6b4a; border-radius: 6px; background: #f3efe6; color: #1f6b4a; font-weight: 700;">
                    <input type="hidden" id="modalSelectedSponsorName">
                    <div id="modalSponsorResults" style="display:none; position:absolute; left:0; right:0; top:100%; z-index:100; background:#ffffff; border:1.5px solid #1f6b4a; border-radius:6px; max-height:220px; overflow-y:auto; box-shadow:0 4px 12px rgba(31,107,74,0.15); margin-top:2px;"></div>
                </div>
            </div>
            <div class="bm-form-row two-col">
                <div class="bm-form-group">
                    <label>Amount (₹) <span class="req">*</span></label>
                    <div style="position:relative;">
                        <input type="number" id="payAmount" min="0.01" step="0.01" placeholder="0.00" oninput="billing.updatePayPreview()">
                        <button class="btn-full-amount" id="btnFullAmount" onclick="billing.fillFullAmount()" title="Fill full balance">Full</button>
                    </div>
                </div>
                <div class="bm-form-group" id="payRefGroup">
                    <label>Reference No.</label>
                    <input type="text" id="payRef" placeholder="UPI txn / Cheque no.">
                </div>
            </div>
            <!-- Refund extra fields -->
            <div id="refundExtraFields" style="display:none;">
                <div class="bm-form-group">
                    <label>Refund Reason <span class="req">*</span></label>
                    <textarea id="refundReason" rows="2" placeholder="Reason for refund (required)"></textarea>
                </div>
                <div class="bm-form-group">
                    <label>Authorized By <span class="req">*</span></label>
                    <input type="text" id="refundApprovedBy" placeholder="Manager / Doctor name">
                </div>
            </div>
            <!-- Discount / Concession row for Modal -->
            <div class="bm-form-row two-col" style="margin-bottom: 12px;">
                <div class="bm-form-group">
                    <label>Discount / Concession (₹ or %)</label>
                    <div style="display: flex; gap: 8px;">
                        <div style="position: relative; width: 60%;">
                            <span style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); font-weight: 700;">₹</span>
                            <input type="number" id="modalPayDiscount" min="0" step="0.01" placeholder="0.00" oninput="billing.calcModalDiscountAmt()" style="padding-left: 22px;">
                        </div>
                        <div style="position: relative; width: 40%;">
                            <input type="number" id="modalPayDiscountPct" min="0" max="100" step="0.1" placeholder="0" oninput="billing.calcModalDiscountPct()" style="padding-right: 22px;">
                            <span style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); font-weight: 700;">%</span>
                        </div>
                    </div>
                </div>
                <div class="bm-form-group">
                    <label>Discount Reason</label>
                    <input type="text" id="modalPayDiscountReason" placeholder="Reason (e.g. Concession, Management)">
                </div>
            </div>

            <div class="bm-form-group">
                <label>Remarks</label>
                <input type="text" id="payRemarks" placeholder="Optional notes">
            </div>
            <div class="pay-after-preview" id="payAfterPreview">
                Balance after payment: <strong id="payAfterVal">—</strong>
            </div>
            <div class="bm-footer">
                <button class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalPayment')">Cancel</button>
                <button class="bm-btn bm-btn-primary" id="btnSavePayment" onclick="billing.savePayment()">
                    <i data-lucide="check-circle-2"></i> Record Payment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Insurance Receipt -->
<div class="billing-modal-overlay" id="modalInsReceipt">
    <div class="billing-modal" style="max-width:480px;">
        <div class="bm-head blue-head">
            <div class="bm-title"><i data-lucide="shield"></i> Insurance Receipt</div>
            <button class="bm-close" onclick="billing.closeModal('modalInsReceipt')"><i data-lucide="x"></i></button>
        </div>
        <div class="bm-body">
            <div class="ins-summary-card" id="insReceiptSummaryCard">
                <div class="ins-sc-row"><span>Company:</span><strong id="insRcptCompany">—</strong></div>
                <div class="ins-sc-row"><span>Approved:</span><strong id="insRcptApproved">₹0</strong></div>
                <div class="ins-sc-row"><span>Already Received:</span><strong id="insRcptReceived">₹0</strong></div>
                <div class="ins-sc-row highlight"><span>Pending Claim:</span><strong id="insRcptPending">₹0</strong></div>
            </div>
            <div class="bm-form-row two-col">
                <div class="bm-form-group">
                    <label>Receipt Date <span class="req">*</span></label>
                    <input type="date" id="insRcptDate">
                </div>
                <div class="bm-form-group">
                    <label>Amount Received (₹) <span class="req">*</span></label>
                    <input type="number" id="insRcptAmount" min="0.01" step="0.01" placeholder="0.00">
                    <button class="btn-full-amount mt-1" onclick="billing.fillInsFullAmount()">Full Pending</button>
                </div>
            </div>
            <div class="bm-form-group">
                <label>Settlement Reference <span class="req">*</span></label>
                <input type="text" id="insRcptRef" placeholder="Claim settlement / NEFT reference">
            </div>
            <div class="bm-form-group">
                <label>Remarks</label>
                <input type="text" id="insRcptRemarks" placeholder="Optional">
            </div>
            <div class="bm-footer">
                <button class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalInsReceipt')">Cancel</button>
                <button class="bm-btn bm-btn-primary" onclick="billing.saveInsuranceReceipt()">
                    <i data-lucide="check-circle-2"></i> Record Receipt
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Apply Discount -->
<div class="billing-modal-overlay" id="modalDiscount">
    <div class="billing-modal" style="max-width:440px;">
        <div class="bm-head">
            <div class="bm-title"><i data-lucide="tags"></i> Apply Discount</div>
            <button class="bm-close" onclick="billing.closeModal('modalDiscount')"><i data-lucide="x"></i></button>
        </div>
        <div class="bm-body">
            <div class="discount-subtotal-info">
                Current Subtotal: <strong id="discSubtotalDisplay">₹0.00</strong>
            </div>
            <div class="bm-form-row two-col">
                <div class="bm-form-group">
                    <label>Discount Amount (₹)</label>
                    <input type="number" id="discAmount" min="0" step="0.01" placeholder="0.00" oninput="billing.calcDiscountPct()">
                </div>
                <div class="bm-form-group">
                    <label>Discount Percentage (%)</label>
                    <input type="number" id="discPct" min="0" max="100" step="0.01" placeholder="0.00" oninput="billing.calcDiscountAmt()">
                </div>
            </div>
            <div class="bm-form-group">
                <label>Reason <span class="req">*</span></label>
                <select id="discReason">
                    <option value="">Select reason</option>
                    <option>Doctor Approved</option>
                    <option>Management Decision</option>
                    <option>Insurance Negotiation</option>
                    <option>Below Poverty Line</option>
                    <option>Employee Discount</option>
                    <option>Loyalty Discount</option>
                </select>
            </div>
            <div class="discount-after-preview">
                <div class="dap-row"><span>Subtotal</span><span id="dapSubtotal">₹0.00</span></div>
                <div class="dap-row red"><span>Discount</span><span id="dapDiscount">-₹0.00</span></div>
                <div class="dap-row bold green"><span>Grand Total</span><span id="dapGrandTotal">₹0.00</span></div>
            </div>
            <div class="bm-footer">
                <button class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalDiscount')">Cancel</button>
                <button class="bm-btn bm-btn-primary" onclick="billing.saveDiscount()">
                    <i data-lucide="check-circle-2"></i> Apply Discount
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Insurance Details -->
<div class="billing-modal-overlay" id="modalInsurance">
    <div class="billing-modal" style="max-width:600px;">
        <div class="bm-head blue-head">
            <div class="bm-title"><i data-lucide="shield"></i> Insurance / Corporate Details</div>
            <button class="bm-close" onclick="billing.closeModal('modalInsurance')"><i data-lucide="x"></i></button>
        </div>
        <div class="bm-body">
            <div class="bm-form-group">
                <label>Bill Type</label>
                <div class="bill-type-group">
                    <button class="bill-type-btn active" data-type="SELF"      onclick="billing.selectBillType(this)">SELF</button>
                    <button class="bill-type-btn" data-type="INSURANCE"  onclick="billing.selectBillType(this)">INSURANCE</button>
                    <button class="bill-type-btn" data-type="CORPORATE"  onclick="billing.selectBillType(this)">CORPORATE</button>
                </div>
            </div>
            <div id="insFormFields">
                <div class="bm-form-row two-col">
                    <div class="bm-form-group">
                        <label>Sponsor Type <span class="req">*</span></label>
                        <select id="modalInsSponsorType" style="height:38px; width:100%; border:1px solid #1f6b4a; border-radius:6px; padding:0 8px; font-weight:600;">
                            <option value="INSURANCE">Insurance</option>
                            <option value="TPA">TPA</option>
                        </select>
                    </div>
                    <div class="bm-form-group">
                        <label>Insurance / TPA Company Name <span class="req">*</span></label>
                        <input type="text" id="insCompanyName" placeholder="e.g. Star Health, Medi Assist, HDFC ERGO...">
                    </div>
                </div>
            </div>
            <div class="bm-footer">
                <button class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalInsurance')">Cancel</button>
                <button class="bm-btn bm-btn-primary" onclick="billing.saveInsuranceDetails()">
                    <i class="fas fa-save"></i> Save Details
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Change Status -->
<div class="billing-modal-overlay" id="modalStatus">
    <div class="billing-modal" style="max-width:420px;">
        <div class="bm-head">
            <div class="bm-title"><i class="fas fa-toggle-on"></i> Change Billing Status</div>
            <button class="bm-close" onclick="billing.closeModal('modalStatus')"><i data-lucide="x"></i></button>
        </div>
        <div class="bm-body">
            <div class="bm-form-group">
                <label>Current Status</label>
                <div class="status-current-display" id="statusCurrentDisplay">OPEN</div>
            </div>
            <div class="bm-form-group">
                <label>New Status <span class="req">*</span></label>
                <div class="status-options" id="statusOptions">
                    <button class="status-option-btn" data-status="OPEN">OPEN</button>
                    <button class="status-option-btn" data-status="UNDER_TREATMENT">UNDER TREATMENT</button>
                    <button class="status-option-btn" data-status="DISCHARGE_PENDING">DISCHARGE PENDING</button>
                    <button class="status-option-btn" data-status="FINALIZED">FINALIZED</button>
                    <button class="status-option-btn danger-btn" data-status="CANCELLED">CANCELLED</button>
                </div>
            </div>
            <div class="bm-form-group">
                <label>Reason for change</label>
                <textarea id="statusReason" rows="2" placeholder="Optional notes..."></textarea>
            </div>
            <div class="bm-footer">
                <button class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalStatus')">Cancel</button>
                <button class="bm-btn bm-btn-primary" onclick="billing.saveStatus()">
                    <i data-lucide="check-circle-2"></i> Update Status
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Discharge Patient -->
<div class="billing-modal-overlay" id="modalDischarge">
    <div class="billing-modal" style="max-width:620px; max-height:90vh; display:flex; flex-direction:column; background:#f3efe6; border:1.5px solid #1f6b4a; border-radius:12px; box-shadow:0 10px 30px rgba(31,107,74,0.25);">
        <div class="bm-head" style="background:#1f6b4a; color:#f3efe6; padding:16px 20px; border-radius:10px 10px 0 0; display:flex; justify-content:space-between; align-items:center; flex-shrink:0;">
            <div class="bm-title" style="color:#f3efe6; font-size:1.1rem; font-weight:800; display:flex; align-items:center; gap:8px;"><i data-lucide="log-out"></i> Discharge Patient</div>
            <button class="bm-close" style="color:#f3efe6; background:transparent; border:none; cursor:pointer;" onclick="billing.closeModal('modalDischarge')"><i data-lucide="x"></i></button>
        </div>
        <div class="bm-body" style="overflow-y:auto; flex:1; padding:20px; max-height:calc(90vh - 130px);">
            <form id="dischargeFormLocal" onsubmit="event.preventDefault(); billing.submitDischarge();">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="bm-form-group">
                        <label style="font-size:12px; font-weight:700; color:#1f6b4a; text-transform:uppercase; margin-bottom:4px; display:block;">Discharge Date & Time <span class="req">*</span></label>
                        <input type="datetime-local" id="dsDate" class="bm-input" required style="width:100%; height:40px; padding:0 10px; border:1.5px solid #1f6b4a; border-radius:8px; background:#f3efe6; color:#1f6b4a; font-weight:600;">
                    </div>
                    <div class="bm-form-group">
                        <label style="font-size:12px; font-weight:700; color:#1f6b4a; text-transform:uppercase; margin-bottom:4px; display:block;">Discharge Type <span class="req">*</span></label>
                        <select id="dsType" class="bm-input" required style="width:100%; height:40px; padding:0 10px; border:1.5px solid #1f6b4a; border-radius:8px; background:#f3efe6; color:#1f6b4a; font-weight:600;">
                            <option value="Normal">Normal</option>
                            <option value="Against Medical Advice">Against Medical Advice (LAMA)</option>
                            <option value="Transferred">Transferred</option>
                            <option value="Deceased">Deceased</option>
                        </select>
                    </div>
                    <div class="bm-form-group">
                        <label style="font-size:12px; font-weight:700; color:#1f6b4a; text-transform:uppercase; margin-bottom:4px; display:block;">Follow-up Date</label>
                        <input type="date" id="dsFollowup" class="bm-input" style="width:100%; height:40px; padding:0 10px; border:1.5px solid #1f6b4a; border-radius:8px; background:#f3efe6; color:#1f6b4a; font-weight:600;">
                    </div>
                </div>
                
                <div class="bm-form-group" style="margin-bottom: 14px;">
                    <label style="font-size:12px; font-weight:700; color:#1f6b4a; text-transform:uppercase; margin-bottom:4px; display:block;">Final Diagnosis</label>
                    <textarea id="dsDiagnosis" class="bm-input" rows="2" placeholder="e.g. Acute appendicitis, Hypertension resolved..." style="width:100%; padding:8px 10px; border:1.5px solid #1f6b4a; border-radius:8px; background:#f3efe6; color:#1f6b4a; font-size:13px;"></textarea>
                </div>
                
                <div class="bm-form-group" style="margin-bottom: 14px;">
                    <label style="font-size:12px; font-weight:700; color:#1f6b4a; text-transform:uppercase; margin-bottom:4px; display:block;">Discharge Summary</label>
                    <textarea id="dsSummary" class="bm-input" rows="3" placeholder="Condition at discharge, main clinical events during stay..." style="width:100%; padding:8px 10px; border:1.5px solid #1f6b4a; border-radius:8px; background:#f3efe6; color:#1f6b4a; font-size:13px;"></textarea>
                </div>
                
                <div class="bm-form-group" style="margin-bottom: 10px;">
                    <label style="font-size:12px; font-weight:700; color:#1f6b4a; text-transform:uppercase; margin-bottom:4px; display:block;">Medications Prescribed</label>
                    <textarea id="dsMeds" class="bm-input" rows="2" placeholder="List of take-home discharge medications..." style="width:100%; padding:8px 10px; border:1.5px solid #1f6b4a; border-radius:8px; background:#f3efe6; color:#1f6b4a; font-size:13px;"></textarea>
                </div>
            </form>
        </div>
        <div class="bm-footer" style="padding:14px 20px; border-top:1.5px solid rgba(31,107,74,0.2); background:#f3efe6; display:flex; justify-content:flex-end; align-items:center; gap:10px; flex-shrink:0; border-radius:0 0 12px 12px;">
            <button type="button" class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalDischarge')" style="background:#f3efe6; color:#1f6b4a; border:1.5px solid #1f6b4a; font-weight:700; padding:9px 18px; border-radius:8px; cursor:pointer;">Cancel</button>
            <button type="button" class="bm-btn bm-btn-primary" id="btnSubmitDischarge" onclick="billing.submitDischarge()" style="background:#1f6b4a; color:#f3efe6; border:1.5px solid #1f6b4a; font-weight:800; padding:10px 22px; border-radius:8px; cursor:pointer; display:inline-flex; align-items:center; gap:6px; box-shadow:0 2px 8px rgba(31,107,74,0.3);">
                <i data-lucide="check-circle-2"></i> Complete Discharge
            </button>
        </div>
    </div>
</div>

<!-- MODAL: Discharge History -->
<div class="billing-modal-overlay" id="modalDischargeHistory">
    <div class="billing-modal" style="max-width:800px;">
        <div class="bm-head" style="background:var(--blue-600);">
            <div class="bm-title" style="color:white;"><i data-lucide="history"></i> Discharge History</div>
            <button class="bm-close" style="color:white;" onclick="billing.closeModal('modalDischargeHistory')"><i data-lucide="x"></i></button>
        </div>
        <div class="bm-body" style="max-height: 70vh; overflow-y: auto;">
            <div class="table-responsive">
                <table class="table table-bordered table-hover billing-items-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Patient Name</th>
                            <th>Admission ID</th>
                            <th>Discharge Date</th>
                            <th>Final Bill Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="dhTableBody">
                        <tr><td colspan="5" class="text-center">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Cancel Charge Confirmation -->
<div class="billing-modal-overlay" id="modalCancelCharge">
    <div class="billing-modal" style="max-width:420px;">
        <div class="bm-head danger-head">
            <div class="bm-title"><i data-lucide="alert-triangle"></i> Cancel Charge?</div>
            <button class="bm-close" onclick="billing.closeModal('modalCancelCharge')"><i data-lucide="x"></i></button>
        </div>
        <div class="bm-body">
            <div class="cancel-charge-info" id="cancelChargeInfo">
                <div class="cci-row"><i class="fas fa-tag"></i><span id="cciCategory"></span></div>
                <div class="cci-row"><i class="fas fa-info-circle"></i><span id="cciDesc"></span></div>
                <div class="cci-row"><i class="fas fa-calendar-alt"></i><span id="cciDate"></span></div>
                <div class="cci-row bold"><i class="fas fa-rupee-sign"></i><span id="cciAmount"></span></div>
            </div>
            <p class="cancel-charge-warn">This charge will be marked <strong>CANCELLED</strong> and removed from the total. This action cannot be undone.</p>
            <div class="bm-footer">
                <button class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalCancelCharge')">Go Back</button>
                <button class="bm-btn bm-btn-danger" id="btnConfirmCancelCharge" onclick="billing.confirmCancelCharge()">
                    <i class="fas fa-times-circle"></i> Yes, Cancel Charge
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Discharge Clearance Detail & Multi-Department Breakdown -->
<div class="billing-modal-overlay" id="modalClearanceDetail">
    <div class="billing-modal" style="max-width: 680px; width: 95%;">
        <div class="bm-head" style="background: #1f6b4a; color: #ffffff;">
            <div class="bm-title" style="color: #ffffff; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-clipboard-check"></i> Multi-Department Discharge Clearance Matrix
            </div>
            <button class="bm-close" style="color: #ffffff;" onclick="billing.closeModal('modalClearanceDetail')">&times;</button>
        </div>
        <div class="bm-body" style="padding: 20px; max-height: 80vh; overflow-y: auto;">
            <!-- Patient summary -->
            <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 12px 16px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                <div>
                    <div style="font-weight: 800; font-size: 1.1rem; color: #1f6b4a;" id="cdPtName">Patient Name</div>
                    <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;" id="cdPtDetails">PID: - | Admission: - | Ward & Bed: -</div>
                </div>
                <div id="cdOverallStatusBadge"></div>
            </div>

            <!-- Nurse Initiation Info -->
            <div id="cdNurseSection" style="background: #fdfbf7; border: 1px dashed #cbd5e1; border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; font-size: 0.82rem;">
                <div style="color: #475569; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 4px;">
                    <span><i class="fas fa-user-nurse" style="color:#1f6b4a;"></i> Initiated by: <strong id="cdNurseName">Nurse</strong></span>
                    <span id="cdInitiatedAt" style="color:#94a3b8; font-size:0.75rem;"></span>
                </div>
                <div id="cdNurseNotesWrap" style="margin-top: 6px; color: #334155; display:none;">
                    <strong>Nurse Notes:</strong> <span id="cdNurseNotes"></span>
                </div>
            </div>

            <!-- 3 Department Cards Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 16px;">
                <!-- Reception Card -->
                <div id="cdRecCard" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 12px; text-align: left;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 0.78rem; font-weight: 800; color: #1e293b;"><i class="fas fa-file-invoice-dollar" style="color:#1f6b4a;"></i> Reception</span>
                        <span id="cdRecStatus" style="font-size: 0.7rem; font-weight: 800;">Pending</span>
                    </div>
                    <div style="font-size: 0.72rem; color: #64748b;" id="cdRecBy">By: -</div>
                    <div style="font-size: 0.68rem; color: #94a3b8;" id="cdRecAt">-</div>
                    <div id="cdRecNotes" style="font-size: 0.72rem; color: #334155; margin-top: 6px; padding: 4px 6px; background: #f8fafc; border-radius: 6px; display: none;"></div>
                    <div id="cdRecQuery" style="font-size: 0.72rem; color: #991b1b; margin-top: 6px; padding: 4px 6px; background: #fee2e2; border-radius: 6px; display: none;"></div>
                </div>

                <!-- Pharmacy Card -->
                <div id="cdPhCard" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 12px; text-align: left;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 0.78rem; font-weight: 800; color: #1e293b;"><i class="fas fa-pills" style="color:#1f6b4a;"></i> Pharmacy</span>
                        <span id="cdPhStatus" style="font-size: 0.7rem; font-weight: 800;">Pending</span>
                    </div>
                    <div style="font-size: 0.72rem; color: #64748b;" id="cdPhBy">By: -</div>
                    <div style="font-size: 0.68rem; color: #94a3b8;" id="cdPhAt">-</div>
                    <div id="cdPhNotes" style="font-size: 0.72rem; color: #334155; margin-top: 6px; padding: 4px 6px; background: #f8fafc; border-radius: 6px; display: none;"></div>
                    <div id="cdPhQuery" style="font-size: 0.72rem; color: #991b1b; margin-top: 6px; padding: 4px 6px; background: #fee2e2; border-radius: 6px; display: none;"></div>
                </div>

                <!-- Laboratory Card -->
                <div id="cdLabCard" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 12px; text-align: left;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 0.78rem; font-weight: 800; color: #1e293b;"><i class="fas fa-microscope" style="color:#1f6b4a;"></i> Laboratory</span>
                        <span id="cdLabStatus" style="font-size: 0.7rem; font-weight: 800;">Pending</span>
                    </div>
                    <div style="font-size: 0.72rem; color: #64748b;" id="cdLabBy">By: -</div>
                    <div style="font-size: 0.68rem; color: #94a3b8;" id="cdLabAt">-</div>
                    <div id="cdLabNotes" style="font-size: 0.72rem; color: #334155; margin-top: 6px; padding: 4px 6px; background: #f8fafc; border-radius: 6px; display: none;"></div>
                    <div id="cdLabQuery" style="font-size: 0.72rem; color: #991b1b; margin-top: 6px; padding: 4px 6px; background: #fee2e2; border-radius: 6px; display: none;"></div>
                </div>
            </div>

            <!-- Admin Final Confirmation Section (if all cleared) -->
            <div id="cdAdminActionSection" style="display:none; background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 12px; padding: 14px; margin-bottom: 16px; text-align: center;">
                <div style="font-weight: 800; font-size: 0.95rem; color: #15803d; margin-bottom: 6px;">
                    🎉 All 3 Departments Have Cleared Discharge!
                </div>
                <p style="font-size: 0.8rem; color: #166534; margin: 0 0 10px 0;">
                    Reception/Billing, Pharmacy, and Laboratory approvals are complete. Admin can now confirm final discharge clearance.
                </p>
                <button type="button" onclick="billing.confirmAdminDischargeFromModal()" style="padding: 8px 24px; background: #16a34a; color: #ffffff; font-weight: 800; font-size: 0.85rem; border: none; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 3px 10px rgba(22,163,74,0.3);">
                    <i class="fas fa-check-double"></i> Confirm Final Discharge
                </button>
            </div>

            <div class="bm-footer" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px;">
                <button type="button" class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalClearanceDetail')">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.BILLING_API = '/GM_HMS/api/';
    window.USER_ROLE   = '<?= htmlspecialchars($userRole) ?>';
    window.USER_NAME   = '<?= htmlspecialchars($userName) ?>';
    window.IS_RECEPTION_VIEW = false;
</script>
<script src="assets/js/ipd_billing.js?v=<?= time() ?>"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
