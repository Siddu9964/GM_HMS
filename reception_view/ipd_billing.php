<?php
session_start();
require_once '../config/SecurityConfig.php';
require_once '../security/EncryptionManager.php';
require_once '../Database/SecureDatabase.php';
use GM_HMS\Database\SecureDatabase;

if (!isset($_SESSION['user_id']) || !in_array(strtolower($_SESSION['role'] ?? ''), ['receptionist', 'admin', 'accountant', 'doctor'])) {
    header("Location: /GM_HMS/login.php");
    exit();
}

$pageTitle = 'IP Billing';
$userRole  = $_SESSION['role'] ?? 'Receptionist';
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
    <title>IP Billing — GM HMS</title>
    <meta name="description" content="IP Billing & Payments Terminal for GM Hospital Management System">
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Reception Base CSS -->
    <link rel="stylesheet" href="assets/css/reception_dashboard.css">
    
    <!-- IPD Billing Module CSS -->
    <link rel="stylesheet" href="/GM_HMS/view/assets/css/ipd_billing.css?v=<?= time() ?>">
    
    <style>
        :root {
            --green: #1f6b4a;
            --cream: #f3efe6;
        }

        body, .ipd-billing-page {
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
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            border: 1.5px solid transparent;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .clearance-pill-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
        }
        .clearance-pill-btn.pill-cleared {
            background: #dcfce7;
            color: #15803d;
            border-color: #86efac;
        }
        .clearance-pill-btn.pill-pending {
            background: #fef9c3;
            color: #a16207;
            border-color: #fde047;
        }
        .clearance-pill-btn.pill-query {
            background: #fee2e2;
            color: #b91c1c;
            border-color: #fca5a5;
            animation: pulse-query 1.5s infinite;
        }
        .clearance-pill-btn.pill-manage {
            background: #1f6b4a;
            color: #ffffff;
            border-color: #1f6b4a;
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

        .phc-btn {
            background: #f3efe6 !important;
            border: 1.5px solid #1f6b4a !important;
            color: #1f6b4a !important;
            border-radius: 8px !important;
            font-weight: 700 !important;
        }

        .phc-btn:hover {
            background: #1f6b4a !important;
            color: #f3efe6 !important;
        }

        .phc-btn-print {
            background: #1f6b4a !important;
            color: #f3efe6 !important;
        }

        .phc-btn-print:hover {
            background: #144d34 !important;
            color: #ffffff !important;
        }

        .treatment-subtabs {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            border-bottom: 2px solid #1f6b4a;
            padding-bottom: 8px;
        }
        .treatment-subtabs .t-tab {
            padding: 6px 12px;
            border-radius: 6px;
            border: 1.5px solid #1f6b4a;
            background: #f3efe6;
            color: #1f6b4a;
            font-weight: 700;
            font-size: 0.78rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .treatment-subtabs .t-tab.active,
        .treatment-subtabs .t-tab:hover {
            background: #1f6b4a;
            color: #f3efe6;
        }

        .t-panel {
            display: none;
        }
        .t-panel.active {
            display: block;
            animation: fadeIn 0.2s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .t-title {
            font-size: 1rem;
            font-weight: 800;
            color: #1f6b4a;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px dashed rgba(31, 107, 74, 0.3);
            padding-bottom: 6px;
        }

        .fg {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
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
        /* Accordion items */
        tr.group-header {
            cursor: pointer !important;
            transition: background 0.15s ease !important;
        }
        tr.group-header:hover {
            background: #e6f1eb !important;
        }
        .btn-group-toggle:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(31,107,74,0.2);
        }
        tr.child-row.is-hidden {
            display: none !important;
        }
        tr.child-row.is-visible {
            display: table-row !important;
        }
    </style>
</head>
<body>
    <div class="reception-layout">
        <!-- Sidebar -->
        <?php include 'includes/reception_sidebar.php'; ?>
        
        <div class="reception-main-content">
            <!-- Navbar -->
            <?php include 'includes/reception_navbar.php'; ?>

            <main class="reception-content ipd-billing-page" id="ipdBillingPage" style="padding: 1.5rem !important;">
                
                <!-- ═══════════ ZONE 1: ALL ADMITTED IP PATIENTS LIST ═══════════ -->
                <div class="billing-empty-state" id="billingEmptyState" style="padding:20px; align-items: stretch; justify-content: flex-start; min-height: calc(100vh - 140px); display: flex; flex-direction: column;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 15px; border-bottom: 2px solid #1f6b4a; padding-bottom: 10px; flex-shrink: 0;">
                        <h2 style="font-size: 1.5rem; color: #1f6b4a; margin: 0; text-align: left; font-weight: 800;">
                            <i data-lucide="users" style="display:inline; vertical-align:middle; margin-right:8px;"></i> Active IPD Patients
                        </h2>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <div style="position: relative;">
                                <i data-lucide="search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: #1f6b4a;"></i>
                                <input type="text" id="patientTableSearch" onkeyup="billing.filterPatientsTable()" placeholder="Search patients..." style="padding: 8px 10px 8px 30px; border-radius: 6px; border: 1.5px solid #1f6b4a; font-size: 0.9rem; outline: none; width: 220px; background: #f3efe6; color: #1f6b4a;">
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
                                <tr><td colspan="8" style="text-align:center; padding:30px; color: #1f6b4a;">Loading patients...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ═══════════ ZONE 2: IP BILLING PAYMENT WORKSPACE ═══════════ -->
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
                                <!-- Clearance Badges & Bill Status -->
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 10px; flex-wrap: wrap;">
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
                            
                            <!-- Payment History & Inline Record Payment Section (Primary Focus) -->
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

                                    <div class="bm-form-row two-col" id="inlinePayDateTypeRow" style="margin-bottom: 12px;">
                                        <div class="bm-form-group" style="margin-bottom: 0;">
                                            <label style="font-size: 11px; font-weight: 700; color: #1f6b4a; text-transform: uppercase; margin-bottom: 4px; display: block;">Payment Date <span class="req">*</span></label>
                                            <input type="date" id="inlinePayDate" style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid #1f6b4a; border-radius: 6px; background: #f3efe6; color: #1f6b4a; font-weight: 600;">
                                        </div>
                                        <div class="bm-form-group" id="inlinePayTypeGroupWrap" style="margin-bottom: 0;">
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
                                        
                                        <!-- Active Sponsor Banner & Quick Actions -->
                                        <div id="inlineActiveSponsorCard" style="display:none; background: #e6f0eb; border: 1px solid #1f6b4a; border-radius: 6px; padding: 8px 12px; margin-bottom: 10px; align-items: center; justify-content: space-between;">
                                            <div>
                                                <div style="font-size: 10px; font-weight: 700; color: #1f6b4a; text-transform: uppercase;">Current Attached Sponsor:</div>
                                                <div id="inlineActiveSponsorText" style="font-size: 13px; font-weight: 800; color: #166534;">Star Health Insurance</div>
                                            </div>
                                            <div style="display: flex; gap: 6px;">
                                                <button type="button" onclick="billing.focusChangeInsurance()" style="padding: 4px 10px; font-size: 11px; font-weight: 700; background: #1f6b4a; color: #fff; border-radius: 4px; border: none; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                                    <i class="fas fa-edit"></i> Change Sponsor
                                                </button>
                                                <button type="button" onclick="billing.cancelInsurance()" style="padding: 4px 10px; font-size: 11px; font-weight: 700; background: #dc2626; color: #fff; border-radius: 4px; border: none; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                                    <i class="fas fa-ban"></i> Cancel Insurance
                                                </button>
                                            </div>
                                        </div>

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

                                        <div style="position: relative; margin-bottom: 8px;">
                                            <label style="font-size: 11px; font-weight: 700; color: #1f6b4a; text-transform: uppercase; margin-bottom: 4px; display: block;">
                                                <span id="inlineSponsorLabel">Insurance Company Name</span> <span class="req">*</span>
                                                <span style="background: #1f6b4a; color: #f3efe6; font-size: 10px; padding: 2px 6px; border-radius: 4px; margin-left: 6px; font-weight: 700;"><i class="fas fa-search"></i> Advance Search</span>
                                            </label>
                                            <input type="text" id="inlineSponsorSearchInput" placeholder="Type to search Insurance company (e.g. Star Health, HDFC ERGO...)" autocomplete="off" style="width: 100%; height: 38px; padding: 0 10px; border: 1.5px solid #1f6b4a; border-radius: 6px; background: #f3efe6; color: #1f6b4a; font-weight: 700;">
                                            <input type="hidden" id="inlineSelectedSponsorName">
                                            <div id="inlineSponsorResults" style="display:none; position:absolute; left:0; right:0; top:100%; z-index:1000; background:#ffffff; border:1.5px solid #1f6b4a; border-radius:6px; max-height:220px; overflow-y:auto; box-shadow:0 6px 18px rgba(0,0,0,0.18); margin-top:2px;"></div>
                                        </div>

                                        <div class="bm-form-row two-col" style="margin-bottom: 4px;">
                                            <div class="bm-form-group" style="margin-bottom: 0;">
                                                <label style="font-size: 10px; font-weight: 700; color: #1f6b4a; text-transform: uppercase; margin-bottom: 2px; display: block;">Policy No (Optional)</label>
                                                <input type="text" id="inlinePolicyNumber" placeholder="e.g. POL-987654" style="width: 100%; height: 34px; padding: 0 8px; border: 1px solid #1f6b4a; border-radius: 4px; background: #f3efe6; color: #1f6b4a; font-weight: 600; font-size: 12px;">
                                            </div>
                                            <div class="bm-form-group" style="margin-bottom: 0;">
                                                <label style="font-size: 10px; font-weight: 700; color: #1f6b4a; text-transform: uppercase; margin-bottom: 2px; display: block;">Claim / Pre-Auth No (Optional)</label>
                                                <input type="text" id="inlineClaimNumber" placeholder="e.g. CLM-12345" style="width: 100%; height: 34px; padding: 0 8px; border: 1px solid #1f6b4a; border-radius: 4px; background: #f3efe6; color: #1f6b4a; font-weight: 600; font-size: 12px;">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bm-form-row two-col" id="inlinePayAmountRow" style="margin-bottom: 12px;">
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

                                    <div id="inlinePayBottomRow" style="display: flex; justify-content: space-between; align-items: center; padding-top: 10px; border-top: 1px solid rgba(31,107,74,0.15);">
                                        <div id="inlinePayAfterWrap" style="font-size: 13px; color: #1f6b4a; font-weight: 600;">
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

                            <!-- Billing Items Section (Always visible) -->
                            <div class="panel-card" id="billingItemsCard" style="margin-top: 16px;">
                                <div class="panel-card-head">
                                    <div class="panel-card-title">
                                        <i data-lucide="list"></i> Billing Items Breakdown
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
                                <div class="category-filter-tabs" id="categoryFilterTabs" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 6px;">
                                    <div style="display: flex; flex-wrap: wrap; gap: 4px; align-items: center;">
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
                                    <div style="display: flex; gap: 4px; align-items: center;">
                                        <button type="button" class="cat-tab" onclick="billing.expandAllGroups()" title="Expand all sections" style="background: rgba(31,107,74,0.12); color: #1f6b4a; font-weight: 700; border: 1px solid #1f6b4a; display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; font-size: 11px;">
                                            <i class="fas fa-angle-double-down"></i> Expand All
                                        </button>
                                        <button type="button" class="cat-tab" onclick="billing.collapseAllGroups()" title="Collapse all sections" style="background: rgba(31,107,74,0.12); color: #1f6b4a; font-weight: 700; border: 1px solid #1f6b4a; display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; font-size: 11px;">
                                            <i class="fas fa-angle-double-up"></i> Collapse All
                                        </button>
                                    </div>
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
                                        ['PROCEDURE',    'Procedure',        'procedure_charges'],
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

<!-- MODAL: Add Charge -->
<div class="billing-modal-overlay" id="modalAddCharge">
    <div class="billing-modal" style="max-width:680px;">
        <div class="bm-head">
            <div class="bm-title"><i class="fas fa-plus-circle"></i> Add Billing Charge</div>
            <button class="bm-close" onclick="billing.closeModal('modalAddCharge')"><i data-lucide="x"></i></button>
        </div>
        <div class="bm-body">
            <!-- Top Category Tabs -->
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
                    <i class="fas fa-filter"></i> Dialysis Chart
                </button>
                <button type="button" class="t-tab" data-tab="tab-oxygen" onclick="billing.selectSubTab('tab-oxygen', this)">
                    <i class="fas fa-lungs"></i> Oxygen Chart
                </button>
                <button type="button" class="t-tab" data-tab="tab-ventilator" onclick="billing.selectSubTab('tab-ventilator', this)">
                    <i class="fas fa-procedures"></i> Ventilation Chart
                </button>
                <button type="button" class="t-tab" data-tab="tab-transfusion" onclick="billing.selectSubTab('tab-transfusion', this)">
                    <i class="fas fa-syringe"></i> Blood Transfusion
                </button>
                <button type="button" class="t-tab" data-tab="tab-ward-transfer" onclick="billing.selectSubTab('tab-ward-transfer', this)">
                    <i class="fas fa-exchange-alt"></i> Ward Transfer
                </button>
                <button type="button" class="t-tab" data-tab="tab-consumables" onclick="billing.selectSubTab('tab-consumables', this)">
                    <i class="fas fa-box"></i> Consumables & Other
                </button>
            </div>

            <!-- 1. DOCTOR VISIT -->
            <div class="t-panel active" id="tab-doctor">
                <div class="t-title"><i class="fas fa-user-md"></i> Consultant Round Visit</div>
                
                <div class="fmg" style="position: relative; margin-bottom: 10px;">
                    <label>Doctor Name <span class="req">*</span> <span class="badge"><i class="fas fa-search"></i> Advance Search Doctor</span></label>
                    <input type="text" id="doc-search-input" placeholder="Type doctor name, specialization, or ID..." autocomplete="off">
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
                        <label>Entered By</label>
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

            <!-- 2. LAB TEST -->
            <div class="t-panel" id="tab-lab">
                <div class="t-title"><i class="fas fa-flask"></i> Laboratory Test Order</div>
                
                <div class="fmg" style="position: relative; margin-bottom: 10px;">
                    <label>Test Name <span class="req">*</span> <span class="badge"><i class="fas fa-search"></i> Advance Search Lab Tests</span></label>
                    <input type="text" id="lab-input" placeholder="Type lab test name e.g. CBC, Lipid Profile, Blood Urea..." autocomplete="off">
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
                        <label>Entered By</label>
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

            <!-- 3. RADIOLOGY -->
            <div class="t-panel" id="tab-radiology">
                <div class="t-title"><i class="fas fa-radiation"></i> Radiology Investigation</div>
                
                <div class="fmg" style="position: relative; margin-bottom: 10px;">
                    <label>Radiology Test Name <span class="req">*</span> <span class="badge"><i class="fas fa-search"></i> Advance Search Radiology</span></label>
                    <input type="text" id="rad-input" placeholder="Type radiology test name e.g. X-Ray Chest, CT Brain, USG..." autocomplete="off">
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
                        <label>Entered By</label>
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

            <!-- 4. OTHER SERVICES -->
            <div class="t-panel" id="tab-other-services">
                <div class="t-title"><i class="fas fa-stethoscope"></i> Hospital Services & Procedures</div>
                
                <div class="fmg" style="position: relative; margin-bottom: 10px;">
                    <label>Service Name <span class="req">*</span> <span class="badge"><i class="fas fa-search"></i> Advance Search Other Services</span></label>
                    <input type="text" id="other-input" placeholder="Type service name e.g. ECG, Nebulization, Dressing..." autocomplete="off">
                    <div id="other-results"></div>
                </div>

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
                        <label>Entered By</label>
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

            <!-- 5. PHARMACY MEDICINES -->
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

            <!-- 6. DIALYSIS RECORD -->
            <div class="t-panel" id="tab-dialysis">
                <div class="t-title"><i class="fas fa-filter"></i> Dialysis Record (dialysis_chart)</div>
                
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
                        <input type="number" id="dia-charge" value="2500" min="0" step="0.01" oninput="billing.calcDiaTotal()">
                    </div>
                    <div class="fmg">
                        <label>Discount (₹)</label>
                        <input type="number" id="dia-discount" value="0" min="0" step="0.01" oninput="billing.calcDiaTotal()">
                    </div>
                </div>

                <div class="bm-total-preview" style="margin-top: 14px;">
                    <span>Total Dialysis Charge:</span>
                    <span id="dia-total-preview" class="bm-total-val">₹ 2,500.00</span>
                </div>

                <div class="bm-footer" style="margin-top: 14px;">
                    <button type="button" class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalAddCharge')">Cancel</button>
                    <button type="button" class="bm-btn bm-btn-primary" id="dia-save-btn" onclick="billing.saveDialysisCharge()">
                        <i class="fas fa-plus"></i> Save & Post Dialysis Charge
                    </button>
                </div>
            </div>

            <!-- 7. OXYGEN RECORD -->
            <div class="t-panel" id="tab-oxygen">
                <div class="t-title"><i class="fas fa-lungs"></i> Oxygen Therapy Record (oxygen_chart)</div>
                <div class="fg">
                    <div class="fmg">
                        <label>Date (oxy_date) <span class="req">*</span></label>
                        <input type="date" id="oxy-date">
                    </div>
                    <div class="fmg">
                        <label>Flow Rate (oxy_flow) <span class="req">*</span></label>
                        <input type="text" id="oxy-flow" placeholder="e.g. 4 L/min via Nasal Cannula" style="font-weight:600;">
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
                        <label>Total Duration</label>
                        <input type="text" id="oxy-dur" placeholder="Auto / e.g. 6 hrs" readonly style="opacity:0.9;">
                    </div>
                    <div class="fmg">
                        <label>Nurse Signature <span class="req">*</span></label>
                        <input type="text" id="oxy-nurse" value="<?php echo htmlspecialchars($userName); ?>" readonly style="font-weight:600; opacity:0.9;">
                    </div>
                    <div class="fmg">
                        <label>Oxygen Charge (₹) <span class="req">*</span></label>
                        <input type="number" id="oxy-charge" value="500" min="0" step="0.01" oninput="billing.calcOxyTotal()">
                    </div>
                    <div class="fmg">
                        <label>Discount (₹)</label>
                        <input type="number" id="oxy-discount" value="0" min="0" step="0.01" oninput="billing.calcOxyTotal()">
                    </div>
                </div>

                <div class="bm-total-preview" style="margin-top: 14px;">
                    <span>Total Oxygen Charge:</span>
                    <span id="oxy-total-preview" class="bm-total-val">₹ 500.00</span>
                </div>

                <div class="bm-footer" style="margin-top: 14px;">
                    <button type="button" class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalAddCharge')">Cancel</button>
                    <button type="button" class="bm-btn bm-btn-primary" id="oxy-save-btn" onclick="billing.saveOxygenCharge()">
                        <i class="fas fa-plus"></i> Save & Post Oxygen Charge
                    </button>
                </div>
            </div>

            <!-- 8. VENTILATOR RECORD -->
            <div class="t-panel" id="tab-ventilator">
                <div class="t-title"><i class="fas fa-procedures"></i> Ventilation Support Record (ventilation_chart)</div>
                <div class="fg">
                    <div class="fmg">
                        <label>Date (vent_date) <span class="req">*</span></label>
                        <input type="date" id="vent-date">
                    </div>
                    <div class="fmg">
                        <label>Vent Mode (vent_mode) <span class="req">*</span></label>
                        <select id="vent-mode" style="font-weight:700;">
                            <option value="SIMV + PS">SIMV + PS (Invasive)</option>
                            <option value="CPAP / BiPAP">CPAP / BiPAP (Non-invasive)</option>
                            <option value="CMV / AC">CMV / AC (Assist Control)</option>
                            <option value="HFNC">High Flow Nasal Cannula (HFNC)</option>
                            <option value="NIV">Non-Invasive Ventilation (NIV)</option>
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
                        <label>Total Duration</label>
                        <input type="text" id="vent-dur" placeholder="Auto / e.g. 12 hrs" readonly style="opacity:0.9;">
                    </div>
                    <div class="fmg">
                        <label>Nurse / Operator <span class="req">*</span></label>
                        <input type="text" id="vent-nurse" value="<?php echo htmlspecialchars($userName); ?>" readonly style="font-weight:600; opacity:0.9;">
                    </div>
                    <div class="fmg">
                        <label>Ventilator Daily/Shift Charge (₹) <span class="req">*</span></label>
                        <input type="number" id="vent-charge" value="4500" min="0" step="0.01" oninput="billing.calcVentTotal()">
                    </div>
                    <div class="fmg">
                        <label>Discount (₹)</label>
                        <input type="number" id="vent-discount" value="0" min="0" step="0.01" oninput="billing.calcVentTotal()">
                    </div>
                </div>

                <div class="bm-total-preview" style="margin-top: 14px;">
                    <span>Total Ventilator Charge:</span>
                    <span id="vent-total-preview" class="bm-total-val">₹ 4,500.00</span>
                </div>

                <div class="bm-footer" style="margin-top: 14px;">
                    <button type="button" class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalAddCharge')">Cancel</button>
                    <button type="button" class="bm-btn bm-btn-primary" id="vent-save-btn" onclick="billing.saveVentilatorCharge()">
                        <i class="fas fa-plus"></i> Save & Post Ventilator Charge
                    </button>
                </div>
            </div>

            <!-- 9. BLOOD TRANSFUSION -->
            <div class="t-panel" id="tab-transfusion">
                <div class="t-title"><i class="fas fa-syringe"></i> Blood Transfusion Record (blood_transfusion_chart)</div>
                <div class="fg">
                    <div class="fmg">
                        <label>Date (bt_date) <span class="req">*</span></label>
                        <input type="date" id="bt-date">
                    </div>
                    <div class="fmg">
                        <label>Blood Bag No. (bt_bag) <span class="req">*</span></label>
                        <input type="text" id="bt-bag" placeholder="e.g. PRBC-2024-8891" style="font-weight:700;">
                    </div>
                    <div class="fmg">
                        <label>Blood Component / Group <span class="req">*</span></label>
                        <input type="text" id="bt-group" placeholder="e.g. O +ve (PRBC / FFP / Platelets)" style="font-weight:600;">
                    </div>
                    <div class="fmg">
                        <label>Start Time (bt_start)</label>
                        <input type="time" id="bt-start">
                    </div>
                    <div class="fmg">
                        <label>End Time (bt_end)</label>
                        <input type="time" id="bt-end">
                    </div>
                    <div class="fmg">
                        <label>Nurse Signature <span class="req">*</span></label>
                        <input type="text" id="bt-nurse" value="<?php echo htmlspecialchars($userName); ?>" readonly style="font-weight:600; opacity:0.9;">
                    </div>
                    <div class="fmg">
                        <label>Transfusion & Testing Charge (₹) <span class="req">*</span></label>
                        <input type="number" id="bt-charge" value="1800" min="0" step="0.01" oninput="billing.calcBtTotal()">
                    </div>
                    <div class="fmg">
                        <label>Discount (₹)</label>
                        <input type="number" id="bt-discount" value="0" min="0" step="0.01" oninput="billing.calcBtTotal()">
                    </div>
                </div>

                <div class="bm-total-preview" style="margin-top: 14px;">
                    <span>Total Blood Transfusion Charge:</span>
                    <span id="bt-total-preview" class="bm-total-val">₹ 1,800.00</span>
                </div>

                <div class="bm-footer" style="margin-top: 14px;">
                    <button type="button" class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalAddCharge')">Cancel</button>
                    <button type="button" class="bm-btn bm-btn-primary" id="bt-save-btn" onclick="billing.saveTransfusionCharge()">
                        <i class="fas fa-plus"></i> Save & Post Transfusion Charge
                    </button>
                </div>
            </div>

            <!-- 10. WARD TRANSFER -->
            <div class="t-panel" id="tab-ward-transfer">
                <div class="t-title"><i class="fas fa-exchange-alt"></i> Ward Shift & Bed Transfer (ward_transfer)</div>
                <div class="fg">
                    <div class="fmg">
                        <label>Transfer Date (wt_date) <span class="req">*</span></label>
                        <input type="date" id="wt-date">
                    </div>
                    <div class="fmg">
                        <label>Transfer Time (wt_time) <span class="req">*</span></label>
                        <input type="time" id="wt-time">
                    </div>
                    <div class="fmg">
                        <label>Shifted From (wt_from) <span class="req">*</span></label>
                        <input type="text" id="wt-from" placeholder="Current bed/ward (auto-filled)" style="font-weight:600;">
                    </div>
                    <div class="fmg">
                        <label>Shifted To Bed/Ward (wt_to) <span class="req">*</span></label>
                        <input type="text" id="wt-to" placeholder="Target bed/ward e.g. ICU Bed-02" style="font-weight:700;">
                    </div>
                    <div class="fmg">
                        <label>Transfer Reason / Note</label>
                        <input type="text" id="wt-reason" placeholder="e.g. Clinical deterioration / Step-down shift">
                    </div>
                    <div class="fmg">
                        <label>Nurse / In-charge <span class="req">*</span></label>
                        <input type="text" id="wt-nurse" value="<?php echo htmlspecialchars($userName); ?>" readonly style="font-weight:600; opacity:0.9;">
                    </div>
                    <div class="fmg">
                        <label>Transfer Handling Charge (₹)</label>
                        <input type="number" id="wt-charge" value="0" min="0" step="0.01" oninput="billing.calcWtTotal()">
                    </div>
                    <div class="fmg">
                        <label>Discount (₹)</label>
                        <input type="number" id="wt-discount" value="0" min="0" step="0.01" oninput="billing.calcWtTotal()">
                    </div>
                </div>

                <div class="bm-total-preview" style="margin-top: 14px;">
                    <span>Total Handling Charge:</span>
                    <span id="wt-total-preview" class="bm-total-val">₹ 0.00</span>
                </div>

                <div class="bm-footer" style="margin-top: 14px;">
                    <button type="button" class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalAddCharge')">Cancel</button>
                    <button type="button" class="bm-btn bm-btn-primary" id="wt-save-btn" onclick="billing.saveWardTransferCharge()">
                        <i class="fas fa-plus"></i> Save & Post Ward Transfer
                    </button>
                </div>
            </div>

            <!-- 11. CONSUMABLES & OTHER -->
            <div class="t-panel" id="tab-consumables">
                <div class="t-title"><i class="fas fa-box"></i> Consumables & Custom Charge Entry</div>
                <div class="fg">
                    <div class="fmg">
                        <label>Charge Category <span class="req">*</span></label>
                        <select id="con-type">
                            <option value="CONSUMABLE">Consumable (Gloves, Syringes, Gauze)</option>
                            <option value="OT">Operation Theatre (OT Charges / Anesthesia)</option>
                            <option value="MISC">Miscellaneous Hospital Charges</option>
                            <option value="OTHER">Other Custom Bill Entry</option>
                        </select>
                    </div>
                    <div class="fmg">
                        <label>Charge Date <span class="req">*</span></label>
                        <input type="date" id="con-date">
                    </div>
                    <div class="fmg" style="grid-column: 1 / -1;">
                        <label>Item Description <span class="req">*</span></label>
                        <input type="text" id="con-desc" placeholder="Enter full item / service description..." style="font-weight:600;">
                    </div>
                    <div class="fmg">
                        <label>Quantity</label>
                        <input type="number" id="con-qty" value="1" min="1" step="1" oninput="billing.calcConTotal()">
                    </div>
                    <div class="fmg">
                        <label>Unit Rate (₹) <span class="req">*</span></label>
                        <input type="number" id="con-rate" value="0" min="0" step="0.01" oninput="billing.calcConTotal()">
                    </div>
                    <div class="fmg">
                        <label>Discount (₹)</label>
                        <input type="number" id="con-discount" value="0" min="0" step="0.01" oninput="billing.calcConTotal()">
                    </div>
                    <div class="fmg">
                        <label>Entered By</label>
                        <input type="text" id="con-user" value="<?php echo htmlspecialchars($userName); ?>" readonly style="font-weight:600; opacity:0.9;">
                    </div>
                </div>

                <div class="bm-total-preview" style="margin-top: 14px;">
                    <span>Total Amount:</span>
                    <span id="con-total-preview" class="bm-total-val">₹ 0.00</span>
                </div>

                <div class="bm-footer" style="margin-top: 14px;">
                    <button type="button" class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalAddCharge')">Cancel</button>
                    <button type="button" class="bm-btn bm-btn-primary" id="con-save-btn" onclick="billing.saveConsumableCharge()">
                        <i class="fas fa-plus"></i> Add Charge Item
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- MODAL: Room Rent Generator -->
<div class="billing-modal-overlay" id="modalRoomRent">
    <div class="billing-modal" style="max-width:540px;">
        <div class="bm-head">
            <div class="bm-title"><i data-lucide="bed-double"></i> Room & Bed Rent Calculator</div>
            <button class="bm-close" onclick="billing.closeModal('modalRoomRent')"><i data-lucide="x"></i></button>
        </div>
        <div class="bm-body">
            <div class="rr-bed-info" id="rrBedInfo">
                <div class="rr-bed-tag" id="rrBedTag">Ward / Bed</div>
                <div class="rr-rate-grid">
                    <div><span>Bed Rent:</span> <strong id="rrBedRent">₹0</strong>/day</div>
                    <div><span>Nursing:</span> <strong id="rrNursing">₹0</strong>/day</div>
                    <div><span>Duty Doctor:</span> <strong id="rrDutyDr">₹0</strong>/day</div>
                    <div><span>Total/Day:</span> <strong id="rrTotalPerDay" style="color:var(--teal)">₹0</strong></div>
                </div>
            </div>

            <div class="bm-form-group">
                <label>Date Range (From - To)</label>
                <div class="bm-form-row two-col">
                    <input type="date" id="rrFromDate" onchange="billing.calcRoomRentDays()">
                    <input type="date" id="rrToDate" onchange="billing.calcRoomRentDays()">
                </div>
            </div>

            <div class="bm-form-row two-col">
                <div class="bm-form-group">
                    <label>Total Days</label>
                    <input type="number" id="rrTotalDays" min="1" value="1" oninput="billing.calcRoomRentTotal()">
                </div>
                <div class="bm-form-group">
                    <label>Per Day Rate (₹)</label>
                    <input type="number" id="rrDailyRate" min="0" step="0.01" oninput="billing.calcRoomRentTotal()">
                </div>
            </div>

            <div class="bm-form-group">
                <label>Charge Category</label>
                <select id="rrChargeType">
                    <option value="ROOM_RENT">Room Rent (Full Bed Amount)</option>
                    <option value="NURSING">Nursing Charges Only</option>
                    <option value="ICU">ICU / Special Care Charges</option>
                </select>
            </div>

            <div class="bm-total-preview">
                <span>Calculated Total:</span>
                <span id="rrTotalPreview" class="bm-total-val">₹0.00</span>
            </div>
        </div>
        <div class="bm-footer">
            <button class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalRoomRent')">Cancel</button>
            <button class="bm-btn bm-btn-primary" onclick="billing.applyRoomRent()">
                <i data-lucide="check"></i> Post to Bill
            </button>
        </div>
    </div>
</div>

<!-- MODAL: Record Payment -->
<div class="billing-modal-overlay" id="modalPayment">
    <div class="billing-modal" style="max-width:540px;">
        <div class="bm-head">
            <div class="bm-title"><i data-lucide="credit-card"></i> Record Payment / Advance</div>
            <button class="bm-close" onclick="billing.closeModal('modalPayment')"><i data-lucide="x"></i></button>
        </div>
        <div class="bm-body">
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
                </div>
            </div>

            <div class="bm-form-row two-col">
                <div class="bm-form-group">
                    <label>Amount (₹) <span class="req">*</span></label>
                    <div style="position:relative;">
                        <input type="number" id="payAmount" min="0.01" step="0.01" placeholder="0.00" oninput="billing.updatePayPreview()">
                        <button class="btn-full-amount" onclick="billing.fillFullBalance()" title="Fill full balance">Full</button>
                    </div>
                </div>
                <div class="bm-form-group" id="payRefGroup" style="display:none;">
                    <label>Reference No. / Txn ID</label>
                    <input type="text" id="payRefNo" placeholder="UPI txn / Cheque no.">
                </div>
            </div>

            <div class="bm-form-group">
                <label>Remarks / Notes</label>
                <input type="text" id="payRemarks" placeholder="Optional payment remarks">
            </div>

            <div class="bm-total-preview">
                <span>Balance after payment:</span>
                <span id="payBalancePreview" class="bm-total-val">—</span>
            </div>
        </div>
        <div class="bm-footer">
            <button class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalPayment')">Cancel</button>
            <button class="bm-btn bm-btn-primary" onclick="billing.savePayment()">
                <i data-lucide="check-circle-2"></i> Save & Generate Receipt
            </button>
        </div>
    </div>
</div>

<!-- MODAL: Discount -->
<div class="billing-modal-overlay" id="modalDiscount">
    <div class="billing-modal" style="max-width:440px;">
        <div class="bm-head">
            <div class="bm-title"><i data-lucide="tag"></i> Apply Discount / Concession</div>
            <button class="bm-close" onclick="billing.closeModal('modalDiscount')"><i data-lucide="x"></i></button>
        </div>
        <div class="bm-body">
            <div class="bm-form-group">
                <label>Discount Type</label>
                <div class="pay-type-group" id="discountTypeGroup">
                    <button class="pay-type-btn active" data-dtype="FLAT">Flat Amount (₹)</button>
                    <button class="pay-type-btn" data-dtype="PERCENT">Percentage (%)</button>
                </div>
            </div>
            <div class="bm-form-group">
                <label id="discountValLabel">Discount Amount (₹)</label>
                <input type="number" id="discountValue" min="0" step="0.01" placeholder="0.00" oninput="billing.calcDiscountPreview()">
            </div>
            <div class="bm-form-group">
                <label>Reason / Authorization <span class="req">*</span></label>
                <input type="text" id="discountReason" placeholder="e.g. Doctor Concession, Management Approval">
            </div>
            <div class="bm-total-preview">
                <span>New Grand Total:</span>
                <span id="discountGrandTotalPreview" class="bm-total-val">—</span>
            </div>
        </div>
        <div class="bm-footer">
            <button class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalDiscount')">Cancel</button>
            <button class="bm-btn bm-btn-primary" onclick="billing.applyDiscount()">
                <i data-lucide="check"></i> Apply Discount
            </button>
        </div>
    </div>
</div>

<!-- MODAL: Insurance Details -->
<div class="billing-modal-overlay" id="modalInsurance">
    <div class="billing-modal" style="max-width:520px;">
        <div class="bm-head">
            <div class="bm-title"><i data-lucide="shield"></i> Insurance / TPA Details</div>
            <button class="bm-close" onclick="billing.closeModal('modalInsurance')"><i data-lucide="x"></i></button>
        </div>
        <div class="bm-body">
            <div class="bm-form-group">
                <label>Billing Type</label>
                <select id="insBillType" onchange="billing.toggleInsFields()">
                    <option value="SELF">Self Pay (Cash)</option>
                    <option value="INSURANCE">Insurance (TPA / Mediclaim)</option>
                    <option value="CORPORATE">Corporate Tie-up</option>
                </select>
            </div>
            <div id="insFieldsBlock" style="display:none;">
                <div class="bm-form-group">
                    <label>Insurance / TPA Company Name <span class="req">*</span></label>
                    <input type="text" id="insCompanyName" placeholder="e.g. Star Health, Medi Assist, HDFC ERGO...">
                </div>
            </div>
        </div>
        <div class="bm-footer">
            <button class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalInsurance')">Cancel</button>
            <button class="bm-btn bm-btn-primary" onclick="billing.saveInsurance()">
                <i data-lucide="check"></i> Save Insurance Info
            </button>
        </div>
    </div>
</div>

<!-- MODAL: Change Billing Status -->
<div class="billing-modal-overlay" id="modalStatus">
    <div class="billing-modal" style="max-width:400px;">
        <div class="bm-head">
            <div class="bm-title"><i data-lucide="toggle-right"></i> Change Billing Status</div>
            <button class="bm-close" onclick="billing.closeModal('modalStatus')"><i data-lucide="x"></i></button>
        </div>
        <div class="bm-body">
            <div class="bm-form-group">
                <label>New Billing Status</label>
                <select id="newBillingStatus">
                    <option value="OPEN">OPEN — Active billing in progress</option>
                    <option value="FINALIZED">FINALIZED — Ready for discharge settlement</option>
                    <option value="SETTLED">SETTLED — All payments cleared & settled</option>
                    <option value="CANCELLED">CANCELLED — Void / Cancelled bill</option>
                </select>
            </div>
            <div class="bm-form-group" id="statusReasonGroup" style="display:none;">
                <label>Cancellation Reason <span class="req">*</span></label>
                <input type="text" id="statusCancelReason" placeholder="Required for cancellation">
            </div>
        </div>
        <div class="bm-footer">
            <button class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalStatus')">Cancel</button>
            <button class="bm-btn bm-btn-primary" onclick="billing.saveStatus()">
                <i data-lucide="check"></i> Update Status
            </button>
        </div>
    </div>
</div>

<!-- MODAL: Cancel Insurance Confirmation -->
<div class="billing-modal-overlay" id="modalCancelInsurance">
    <div class="billing-modal" style="max-width: 460px; border-radius: 12px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);">
        <div class="bm-head danger-head" style="background: #dc2626; color: #ffffff; padding: 14px 18px; display: flex; justify-content: space-between; align-items: center;">
            <div class="bm-title" style="color: #ffffff; font-weight: 800; font-size: 15px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-exclamation-triangle"></i> Cancel Insurance Sponsor?
            </div>
            <button class="bm-close" style="color: #ffffff; font-size: 20px; cursor: pointer; background: transparent; border: none;" onclick="billing.closeModal('modalCancelInsurance')">&times;</button>
        </div>
        <div class="bm-body" style="padding: 20px; background: #ffffff;">
            <div style="background: #fef2f2; border: 1.5px solid #fecaca; border-radius: 8px; padding: 12px 14px; margin-bottom: 16px;">
                <div style="font-size: 11px; font-weight: 700; color: #991b1b; text-transform: uppercase; margin-bottom: 4px;">Sponsor to be Removed:</div>
                <div id="cancelInsSponsorName" style="font-size: 15px; font-weight: 800; color: #b91c1c;">—</div>
            </div>
            
            <p style="font-size: 13px; color: #334155; line-height: 1.6; margin-bottom: 16px;">
                Are you sure you want to cancel the insurance sponsor (<strong id="cancelInsSponsorPromptName" style="color: #b91c1c;">Insurance</strong>) and convert this patient's bill to <strong>Self-Pay / Cash</strong>?
            </p>
            
            <div style="background: #fffbeb; border-left: 4px solid #f59e0b; padding: 10px 12px; margin-bottom: 20px; font-size: 11px; color: #92400e; border-radius: 0 6px 6px 0; line-height: 1.5;">
                <i class="fas fa-info-circle"></i> The insurance claim will be marked cancelled and the full outstanding balance will become payable directly by the patient.
            </div>

            <div class="bm-footer" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; padding: 0;">
                <button type="button" class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalCancelInsurance')" style="padding: 8px 18px; font-weight: 700; border-radius: 6px; cursor: pointer;">
                    Keep Insurance
                </button>
                <button type="button" class="bm-btn bm-btn-danger" id="btnConfirmCancelInsurance" onclick="billing.confirmCancelInsurance()" style="background: #dc2626; color: #ffffff; padding: 8px 18px; font-weight: 700; border: none; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-check-circle"></i> Yes, Cancel & Revert
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Discharge History -->
<div class="billing-modal-overlay" id="modalDischargeHistory">
    <div class="billing-modal" style="max-width:850px;">
        <div class="bm-head">
            <div class="bm-title"><i data-lucide="history"></i> Discharged Patients Billing History</div>
            <button class="bm-close" onclick="billing.closeModal('modalDischargeHistory')"><i data-lucide="x"></i></button>
        </div>
        <div class="bm-body">
            <div style="display: flex; gap: 10px; margin-bottom: 12px;">
                <input type="text" id="dhSearchInput" onkeyup="billing.filterDischargeHistory()" placeholder="Search patient name, admission ID, phone..." style="flex:1; padding: 8px 12px; border-radius: 6px; border: 1.5px solid #1f6b4a; background: #f3efe6; color: #1f6b4a; font-size: 0.9rem;">
            </div>
            <div class="table-responsive rounded" style="max-height: 400px; overflow-y: auto; border: 1.5px solid #1f6b4a;">
                <table class="billing-items-table" style="width: 100%; border-collapse: separate; border-spacing: 0;">
                    <thead style="position: sticky; top: 0; z-index: 10;">
                        <tr>
                            <th>Adm. ID</th>
                            <th>Patient Name</th>
                            <th>Phone</th>
                            <th>Discharge Date</th>
                            <th>Grand Total</th>
                            <th>Amount Paid</th>
                            <th>Balance</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="dischargeHistoryTbody">
                        <tr><td colspan="8" style="text-align:center; padding: 20px;">Loading discharge history...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="bm-footer">
            <button class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalDischargeHistory')">Close</button>
        </div>
    </div>
</div>

<script>
    window.BILLING_API = '/GM_HMS/api/';
    window.USER_ROLE   = '<?= htmlspecialchars($userRole) ?>';
    window.USER_NAME   = '<?= htmlspecialchars($userName) ?>';
    window.IS_RECEPTION_VIEW = true;
</script>
<script src="/GM_HMS/view/assets/js/ipd_billing.js?v=<?= time() ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.lucide) lucide.createIcons();
        if (window.billing && typeof billing.loadAllAdmittedPatients === 'function') {
            billing.loadAllAdmittedPatients();
        }
    });
</script>
</body>
</html>
