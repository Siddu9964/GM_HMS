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

$pageTitle = 'IP Insurance Management';
$userRole  = $_SESSION['role'] ?? 'admin';
$userName  = $_SESSION['username'] ?? 'Staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IP Insurance Management — GM HMS</title>
    <meta name="description" content="Dedicated IP Insurance Management Terminal for GM Hospital Management System">
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="assets/css/admin_common.css">
    <style>
        /* Strict 2-Color Theme System: #f3efe6 (Cream) and #1f6b4a (Forest Green) */
        :root {
            --green: #1f6b4a;
            --green-dark: #165238;
            --green-light: #2d8a62;
            --cream: #f3efe6;
            --cream-light: #faf8f4;
            --cream-dark: #e8e2d4;
        }

        body, .ip-ins-page {
            background-color: var(--cream) !important;
            color: var(--green) !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
        }

        .ins-card {
            background: var(--cream) !important;
            border: 1.5px solid var(--green) !important;
            box-shadow: 0 4px 16px rgba(31, 107, 74, 0.08) !important;
            border-radius: 12px !important;
            color: var(--green) !important;
        }

        .kpi-card {
            background: var(--cream) !important;
            border: 1.5px solid var(--green) !important;
            border-radius: 12px !important;
            padding: 12px 14px !important;
            box-shadow: 0 3px 10px rgba(31, 107, 74, 0.06) !important;
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
            overflow: hidden;
            transition: all 0.2s ease;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(31, 107, 74, 0.12) !important;
        }

        .kpi-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--green);
            color: var(--cream);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .kpi-info-wrap {
            min-width: 0;
            flex: 1;
            overflow: hidden;
        }

        .kpi-val {
            font-size: 1.15rem;
            font-weight: 900;
            color: var(--green);
            line-height: 1.25;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .kpi-lbl {
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--green);
            opacity: 0.85;
            margin-top: 1px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Filter Controls */
        .filter-panel {
            background: var(--cream-light);
            border: 1.5px solid var(--green);
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 16px;
            box-shadow: 0 4px 14px rgba(31, 107, 74, 0.06);
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 12px;
        }

        .f-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .f-label {
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--green);
        }

        .f-input, .f-select {
            height: 38px;
            padding: 0 10px;
            border-radius: 8px;
            border: 1.5px solid var(--green);
            background: var(--cream);
            color: var(--green);
            font-size: 0.85rem;
            font-weight: 600;
            outline: none;
            transition: all 0.15s ease;
        }
        .f-input:focus, .f-select:focus {
            box-shadow: 0 0 0 2px rgba(31, 107, 74, 0.25);
            background: #ffffff;
        }

        /* Table Styling */
        .ins-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.84rem;
        }

        .ins-table th {
            background: var(--green) !important;
            color: var(--cream) !important;
            font-size: 0.73rem !important;
            font-weight: 800 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            padding: 12px 14px !important;
            border: none !important;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .ins-table th.sortable {
            cursor: pointer;
            user-select: none;
        }
        .ins-table th.sortable:hover {
            background: var(--green-light) !important;
        }

        .ins-table td {
            padding: 12px 14px;
            color: var(--green);
            border-bottom: 1px solid rgba(31, 107, 74, 0.18);
            background: var(--cream);
            vertical-align: middle;
        }

        .ins-table tr:hover td {
            background: rgba(31, 107, 74, 0.06);
        }

        /* Status Badges */
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }
        .status-APPROVED { background: #dcfce7; color: #14532d; border: 1.5px solid #4ade80; }
        .status-SETTLED { background: #dbeafe; color: #1e40af; border: 1.5px solid #60a5fa; }
        .status-PENDING { background: #fef3c7; color: #92400e; border: 1.5px solid #fcd34d; }
        .status-SUBMITTED { background: #f3e8ff; color: #6b21a8; border: 1.5px solid #c084fc; }
        .status-PARTIAL_APPROVED, .status-PARTIAL_RECEIVED { background: #ffedd5; color: #9a3412; border: 1.5px solid #fb923c; }
        .status-REJECTED { background: #fee2e2; color: #991b1b; border: 1.5px solid #f87171; }
        .status-DISPUTE { background: #ffe4e6; color: #9f1239; border: 1.5px solid #fb7185; }

        .type-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 800;
            background: rgba(31, 107, 74, 0.15);
            color: var(--green);
            border: 1px solid var(--green);
        }

        /* Action Buttons */
        .btn-ins-action {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.76rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
            border: 1.5px solid var(--green);
        }
        .btn-ins-edit {
            background: var(--green);
            color: var(--cream);
        }
        .btn-ins-edit:hover {
            background: var(--green-light);
            transform: translateY(-1px);
        }
        .btn-ins-view {
            background: var(--cream);
            color: var(--green);
        }
        .btn-ins-view:hover {
            background: rgba(31, 107, 74, 0.15);
        }

        /* Modals */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(4px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            padding: 20px;
        }
        .modal-overlay.active {
            display: flex;
        }

        .ins-modal-box {
            background: var(--cream) !important;
            border: 2px solid var(--green) !important;
            border-radius: 14px !important;
            width: 100%;
            max-width: 860px;
            max-height: 92vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 16px 36px rgba(31, 107, 74, 0.25) !important;
            overflow: hidden;
            animation: modalFadeIn 0.2s ease-out;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.96); }
            to { opacity: 1; transform: scale(1); }
        }

        .modal-header {
            background: var(--green);
            color: var(--cream);
            padding: 16px 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }

        .modal-body {
            padding: 22px;
            overflow-y: auto;
            flex: 1;
        }

        .modal-footer {
            padding: 14px 22px;
            border-top: 1.5px solid rgba(31, 107, 74, 0.2);
            background: var(--cream);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            flex-shrink: 0;
        }

        .form-section-title {
            font-size: 0.88rem;
            font-weight: 800;
            color: var(--green);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 12px;
            padding-bottom: 6px;
            border-bottom: 1.5px solid rgba(31, 107, 74, 0.25);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 14px;
        }

        .form-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 14px;
        }

        .form-grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 14px;
        }

        .m-form-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .m-form-group label {
            font-size: 0.74rem;
            font-weight: 800;
            text-transform: uppercase;
            color: var(--green);
        }
        .m-form-group input, .m-form-group select, .m-form-group textarea {
            padding: 8px 10px;
            border-radius: 8px;
            border: 1.5px solid var(--green);
            background: var(--cream);
            color: var(--green);
            font-size: 0.86rem;
            font-weight: 600;
            outline: none;
        }
        .m-form-group input:focus, .m-form-group select:focus, .m-form-group textarea:focus {
            box-shadow: 0 0 0 2px rgba(31, 107, 74, 0.25);
            background: #ffffff;
        }

        .req { color: #dc2626; font-weight: 900; margin-left: 2px; }

        /* Pagination Bar */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 18px;
            background: var(--cream);
            border-top: 1.5px solid var(--green);
            flex-wrap: wrap;
            gap: 12px;
        }

        .page-btn {
            padding: 6px 12px;
            border: 1.5px solid var(--green);
            background: var(--cream);
            color: var(--green);
            font-weight: 800;
            font-size: 0.8rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .page-btn:hover:not(:disabled) {
            background: var(--green);
            color: var(--cream);
        }
        .page-btn.active {
            background: var(--green);
            color: var(--cream);
        }
        .page-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 2000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .toast-msg {
            background: var(--green);
            color: var(--cream);
            border: 1.5px solid var(--cream);
            border-radius: 10px;
            padding: 12px 18px;
            box-shadow: 0 6px 20px rgba(31, 107, 74, 0.35);
            font-weight: 700;
            font-size: 0.88rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideInRight 0.2s ease-out;
        }
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
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

            <main class="flex-1 overflow-y-auto p-4 md:p-6 ip-ins-page" id="ipInsurancePage">
                
                <!-- ═══════════ HEADER BAR ═══════════ -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 pb-4 border-b-2" style="border-color: var(--green);">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-black flex items-center gap-3" style="color: var(--green);">
                            <i data-lucide="shield-check" class="w-8 h-8"></i> IP Insurance Management
                        </h1>
                        <p class="text-xs md:text-sm font-semibold opacity-80 mt-1" style="color: var(--green);">
                            Track, search, and manage all In-Patient Department (IPD) insurance, TPA, and corporate claim records
                        </p>
                    </div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <button class="page-btn flex items-center gap-2" onclick="insManager.loadData()">
                            <i data-lucide="refresh-cw" class="w-4 h-4"></i> Refresh
                        </button>
                        <a href="ipd_billing.php" class="page-btn flex items-center gap-2" style="background: var(--green); color: var(--cream);">
                            <i data-lucide="bed" class="w-4 h-4"></i> IP Billing Terminal
                        </a>
                    </div>
                </div>

                <!-- ═══════════ KPI SUMMARY CARDS ═══════════ -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-5">
                    <div class="kpi-card">
                        <div class="kpi-icon"><i data-lucide="file-text" class="w-5 h-5"></i></div>
                        <div class="kpi-info-wrap">
                            <div class="kpi-val" id="kpiTotalCount">0</div>
                            <div class="kpi-lbl">Total Policies</div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-icon" style="background:#15803d;"><i data-lucide="check-circle-2" class="w-5 h-5"></i></div>
                        <div class="kpi-info-wrap">
                            <div class="kpi-val" id="kpiApproved" title="₹0.00">₹0.00</div>
                            <div class="kpi-lbl">Total Approved</div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-icon" style="background:#1d4ed8;"><i data-lucide="landmark" class="w-5 h-5"></i></div>
                        <div class="kpi-info-wrap">
                            <div class="kpi-val" id="kpiReceived" title="₹0.00">₹0.00</div>
                            <div class="kpi-lbl">Total Settled</div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-icon" style="background:#b91c1c;"><i data-lucide="alert-circle" class="w-5 h-5"></i></div>
                        <div class="kpi-info-wrap">
                            <div class="kpi-val" style="color:#b91c1c;" id="kpiPending" title="₹0.00">₹0.00</div>
                            <div class="kpi-lbl">Pending Claims</div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-icon" style="background:#7c3aed;"><i data-lucide="user" class="w-5 h-5"></i></div>
                        <div class="kpi-info-wrap">
                            <div class="kpi-val" id="kpiPatientPayable" title="₹0.00">₹0.00</div>
                            <div class="kpi-lbl">Patient Payable</div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════ 1-BOX UNIVERSAL SEARCH & QUICK FILTER BAR ═══════════ -->
                <div class="filter-panel mb-5" id="unifiedSearchPanel">
                    <div class="flex flex-col lg:flex-row items-stretch lg:items-center gap-3">
                        <!-- Universal 1-Box Search Input -->
                        <div class="relative flex-1">
                            <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none" style="color: var(--green);"></i>
                            <input type="text" id="fSearch" class="f-input w-full pl-10 pr-9 text-sm font-semibold" placeholder="Search anything: Patient Name, ID, Policy No, Claim No, Insurance Company, TPA, Admission ID, Bill ID, Phone..." onkeyup="insManager.debouncedSearch()" autocomplete="off">
                            <button type="button" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-600 hidden" id="btnClearSearch" onclick="insManager.clearSearch()" title="Clear Search">
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        </div>

                        <!-- Integrated Quick Filters -->
                        <div class="flex items-center gap-2 flex-wrap">
                            <!-- Claim Status Filter -->
                            <select id="fClaimStatus" class="f-select text-xs font-bold" style="height: 38px; min-width: 135px;" onchange="insManager.loadData()">
                                <option value="ALL">All Statuses</option>
                                <option value="PENDING">Pending</option>
                                <option value="SUBMITTED">Submitted</option>
                                <option value="APPROVED">Approved</option>
                                <option value="PARTIAL_APPROVED">Partial Approved</option>
                                <option value="RECEIVED">Received</option>
                                <option value="SETTLED">Settled</option>
                                <option value="REJECTED">Rejected</option>
                                <option value="DISPUTE">Dispute</option>
                            </select>

                            <!-- Insurance Sponsor Type -->
                            <select id="fInsuranceType" class="f-select text-xs font-bold" style="height: 38px; min-width: 115px;" onchange="insManager.loadData()">
                                <option value="ALL">All Types</option>
                                <option value="INSURANCE">Insurance</option>
                                <option value="TPA">TPA</option>
                                <option value="CORPORATE">Corporate</option>
                            </select>

                            <!-- Date Filter Range -->
                            <div class="flex items-center gap-1.5 bg-white px-2 rounded-lg border border-[#1f6b4a]" style="height: 38px;">
                                <i data-lucide="calendar" class="w-3.5 h-3.5" style="color: var(--green);"></i>
                                <input type="date" id="fDateFrom" class="text-xs bg-transparent border-0 outline-none text-[#1f6b4a] font-semibold" title="From Date" onchange="insManager.loadData()">
                                <span class="text-xs font-bold text-[#1f6b4a] opacity-60">to</span>
                                <input type="date" id="fDateTo" class="text-xs bg-transparent border-0 outline-none text-[#1f6b4a] font-semibold" title="To Date" onchange="insManager.loadData()">
                            </div>

                            <!-- Records Per Page -->
                            <select id="fPerPage" class="f-select text-xs font-bold" style="height: 38px; width: 95px;" onchange="insManager.changePerPage(this.value)">
                                <option value="10">10 / pg</option>
                                <option value="25" selected>25 / pg</option>
                                <option value="50">50 / pg</option>
                                <option value="100">100 / pg</option>
                            </select>

                            <!-- Reset Button -->
                            <button type="button" class="page-btn" title="Reset Search & Filters" onclick="insManager.resetFilters()">
                                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5 inline mr-1"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ═══════════ PATIENT INSURANCE TABLE CARD ═══════════ -->
                <div class="ins-card overflow-hidden shadow-sm">
                    <div class="table-responsive overflow-x-auto" style="max-height: calc(100vh - 420px); min-height: 320px;">
                        <table class="ins-table" id="insurancePatientsTable">
                            <thead>
                                <tr>
                                    <th class="sortable" onclick="insManager.sortBy('insurance_id')">
                                        ID / Bill No <i data-lucide="chevrons-up-down" class="w-3.5 h-3.5 inline"></i>
                                    </th>
                                    <th class="sortable" onclick="insManager.sortBy('patient_name')">
                                        Patient Details <i data-lucide="chevrons-up-down" class="w-3.5 h-3.5 inline"></i>
                                    </th>
                                    <th>Admission / Ward</th>
                                    <th class="sortable" onclick="insManager.sortBy('company_name')">
                                        Insurance Sponsor & TPA <i data-lucide="chevrons-up-down" class="w-3.5 h-3.5 inline"></i>
                                    </th>
                                    <th class="sortable" onclick="insManager.sortBy('policy_number')">
                                        Policy & Claim <i data-lucide="chevrons-up-down" class="w-3.5 h-3.5 inline"></i>
                                    </th>
                                    <th class="sortable text-right" onclick="insManager.sortBy('approved_amount')">
                                        Approved (₹) <i data-lucide="chevrons-up-down" class="w-3.5 h-3.5 inline"></i>
                                    </th>
                                    <th class="sortable text-right" onclick="insManager.sortBy('received_amount')">
                                        Settled (₹) <i data-lucide="chevrons-up-down" class="w-3.5 h-3.5 inline"></i>
                                    </th>
                                    <th class="sortable text-right" onclick="insManager.sortBy('pending_amount')">
                                        Pending (₹) <i data-lucide="chevrons-up-down" class="w-3.5 h-3.5 inline"></i>
                                    </th>
                                    <th class="sortable text-center" onclick="insManager.sortBy('claim_status')">
                                        Claim Status <i data-lucide="chevrons-up-down" class="w-3.5 h-3.5 inline"></i>
                                    </th>
                                    <th class="sortable" onclick="insManager.sortBy('created_at')">
                                        Dates <i data-lucide="chevrons-up-down" class="w-3.5 h-3.5 inline"></i>
                                    </th>
                                    <th class="text-center" style="width: 140px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="insuranceTableBody">
                                <tr>
                                    <td colspan="11" class="text-center py-10">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <i data-lucide="loader-2" class="w-8 h-8 animate-spin" style="color: var(--green);"></i>
                                            <span class="font-bold">Loading insurance records...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- ═══════════ PAGINATION CONTROL BAR ═══════════ -->
                    <div class="pagination-container" id="paginationBar">
                        <div class="text-xs md:text-sm font-bold" style="color: var(--green);">
                            <span id="pageInfoText">Showing 0 to 0 of 0 records</span>
                        </div>
                        <div class="flex items-center gap-1.5" id="paginationControls">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>
                </div>

                <!-- Toast Container -->
                <div class="toast-container" id="toastContainer"></div>

            </main>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════
         MODAL 1: EDIT COMPLETE INSURANCE RECORD (All 25 Fields)
    ══════════════════════════════════════════════════════════════════ -->
    <div class="modal-overlay" id="modalEditInsurance">
        <div class="ins-modal-box">
            <div class="modal-header">
                <div class="text-base md:text-lg font-black flex items-center gap-2">
                    <i data-lucide="file-edit" class="w-5 h-5"></i> Edit IPD Insurance Record
                </div>
                <button type="button" class="text-white hover:opacity-75" onclick="insManager.closeModal('modalEditInsurance')">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="editInsuranceForm" onsubmit="event.preventDefault(); insManager.submitUpdate();">
                    <input type="hidden" id="edit_insurance_id">
                    <input type="hidden" id="edit_bill_id">
                    <input type="hidden" id="edit_admission_id">
                    <input type="hidden" id="edit_patient_id">

                    <!-- Patient Summary Banner -->
                    <div class="mb-5 p-3 rounded-lg flex flex-wrap justify-between items-center gap-2 border" style="background: rgba(31,107,74,0.06); border-color: var(--green);">
                        <div>
                            <div class="text-base font-extrabold" id="editBannerPtName">Patient Name</div>
                            <div class="text-xs opacity-80" id="editBannerPtMeta">PID: - | Admission ID: - | Bill: -</div>
                        </div>
                        <div class="text-right">
                            <span class="type-pill" id="editBannerType">INSURANCE</span>
                            <div class="text-xs font-bold mt-1" id="editBannerBed">Ward: -</div>
                        </div>
                    </div>

                    <!-- ── 1. Policy & Sponsor Details ── -->
                    <div class="form-section-title">
                        <i data-lucide="shield" class="w-4 h-4"></i> 1. Sponsor & Policy Identification
                    </div>
                    <div class="form-grid-3">
                        <div class="m-form-group">
                            <label>Insurance Type <span class="req">*</span></label>
                            <select id="edit_insurance_type" required>
                                <option value="INSURANCE">Insurance</option>
                                <option value="TPA">TPA (Third Party Admin)</option>
                                <option value="CORPORATE">Corporate</option>
                            </select>
                        </div>
                        <div class="m-form-group">
                            <label>Company Name <span class="req">*</span></label>
                            <input type="text" id="edit_company_name" placeholder="e.g. Star Health, HDFC ERGO..." required>
                        </div>
                        <div class="m-form-group">
                            <label>Insurance Company ID / Code</label>
                            <input type="text" id="edit_insurance_company_id" placeholder="Optional Company Code">
                        </div>
                    </div>

                    <div class="form-grid-3">
                        <div class="m-form-group">
                            <label>TPA Name</label>
                            <input type="text" id="edit_tpa_name" placeholder="e.g. Medi Assist, Vidal Health...">
                        </div>
                        <div class="m-form-group">
                            <label>TPA Reference Number</label>
                            <input type="text" id="edit_tpa_reference_no" placeholder="TPA Ref / Authorization No.">
                        </div>
                        <div class="m-form-group">
                            <label>Policy / Card Number</label>
                            <input type="text" id="edit_policy_number" placeholder="Policy / Membership Number">
                        </div>
                    </div>

                    <!-- ── 2. Claim & Authorization ── -->
                    <div class="form-section-title mt-4">
                        <i data-lucide="file-check" class="w-4 h-4"></i> 2. Claim Numbers & Pre-Authorization
                    </div>
                    <div class="form-grid-3">
                        <div class="m-form-group">
                            <label>Claim Number</label>
                            <input type="text" id="edit_claim_number" placeholder="Insurance Claim Number">
                        </div>
                        <div class="m-form-group">
                            <label>Approval / Pre-Auth Number</label>
                            <input type="text" id="edit_approval_number" placeholder="Pre-authorization Approval Number">
                        </div>
                        <div class="m-form-group">
                            <label>Claim Status <span class="req">*</span></label>
                            <select id="edit_claim_status" required onchange="insManager.onStatusChange(this.value)">
                                <option value="PENDING">Pending</option>
                                <option value="SUBMITTED">Submitted</option>
                                <option value="APPROVED">Approved</option>
                                <option value="PARTIAL_APPROVED">Partial Approved</option>
                                <option value="RECEIVED">Received</option>
                                <option value="PARTIAL_RECEIVED">Partial Received</option>
                                <option value="SETTLED">Settled</option>
                                <option value="REJECTED">Rejected</option>
                                <option value="DISPUTE">Dispute</option>
                            </select>
                        </div>
                    </div>

                    <!-- ── 3. Financials & Amounts ── -->
                    <div class="form-section-title mt-4">
                        <i data-lucide="calculator" class="w-4 h-4"></i> 3. Insurance Financials & Ledger (₹)
                    </div>
                    <div class="form-grid-4">
                        <div class="m-form-group">
                            <label>Approved Amount (₹) <span class="req">*</span></label>
                            <input type="number" id="edit_approved_amount" min="0" step="0.01" value="0.00" oninput="insManager.calcPendingAmount()" required>
                        </div>
                        <div class="m-form-group">
                            <label>Received Amount (₹)</label>
                            <input type="number" id="edit_received_amount" min="0" step="0.01" value="0.00" oninput="insManager.calcPendingAmount()">
                        </div>
                        <div class="m-form-group">
                            <label>Pending / Claim Balance (₹)</label>
                            <input type="number" id="edit_pending_amount" min="0" step="0.01" value="0.00" readonly style="background: rgba(31,107,74,0.08); font-weight:800;">
                        </div>
                        <div class="m-form-group">
                            <label>Patient Payable (₹)</label>
                            <input type="number" id="edit_patient_payable" min="0" step="0.01" value="0.00">
                        </div>
                    </div>

                    <!-- ── 4. Key Timeline Dates ── -->
                    <div class="form-section-title mt-4">
                        <i data-lucide="calendar" class="w-4 h-4"></i> 4. Claim Lifecycle Dates
                    </div>
                    <div class="form-grid-3">
                        <div class="m-form-group">
                            <label>Submitted Date</label>
                            <input type="date" id="edit_submitted_date">
                        </div>
                        <div class="m-form-group">
                            <label>Approved Date</label>
                            <input type="date" id="edit_approved_date">
                        </div>
                        <div class="m-form-group">
                            <label>Settled Date</label>
                            <input type="date" id="edit_settled_date">
                        </div>
                    </div>

                    <!-- ── 5. Rejection Reason & Remarks ── -->
                    <div class="form-section-title mt-4">
                        <i data-lucide="message-square" class="w-4 h-4"></i> 5. Notes & Justifications
                    </div>
                    <div class="m-form-group mb-3" id="editRejectionGroup">
                        <label>Rejection / Dispute Reason</label>
                        <textarea id="edit_rejection_reason" rows="2" placeholder="Explain why claim was rejected, disputed, or deduction reason..."></textarea>
                    </div>

                    <div class="m-form-group mb-3">
                        <label>Internal Insurance Remarks</label>
                        <textarea id="edit_remarks" rows="2" placeholder="Internal coordinator remarks or notes..."></textarea>
                    </div>

                    <!-- Audit info footer -->
                    <div class="text-xs opacity-75 pt-2 flex justify-between flex-wrap gap-2 border-t" style="border-color: rgba(31,107,74,0.15);">
                        <span>Created by: <strong id="editAuditCreatedBy">-</strong> on <span id="editAuditCreatedAt">-</span></span>
                        <span>Last Updated: <strong id="editAuditUpdatedAt">-</strong></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="page-btn" onclick="insManager.closeModal('modalEditInsurance')">Cancel</button>
                <button type="button" class="page-btn" style="background: var(--green); color: var(--cream);" id="btnUpdateInsurance" onclick="insManager.submitUpdate()">
                    <i data-lucide="check-circle" class="w-4 h-4 inline mr-1"></i> Update Record
                </button>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════
         MODAL 2: VIEW COMPLETE INSURANCE SUMMARY
    ══════════════════════════════════════════════════════════════════ -->
    <div class="modal-overlay" id="modalViewInsurance">
        <div class="ins-modal-box" style="max-width: 680px;">
            <div class="modal-header">
                <div class="text-base font-black flex items-center gap-2">
                    <i data-lucide="file-search" class="w-5 h-5"></i> Insurance Record Details
                </div>
                <button type="button" class="text-white hover:opacity-75" onclick="insManager.closeModal('modalViewInsurance')">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="modal-body" id="viewModalBody">
                <!-- Injected dynamically via JS -->
            </div>
            <div class="modal-footer">
                <button type="button" class="page-btn" onclick="insManager.closeModal('modalViewInsurance')">Close</button>
                <button type="button" class="page-btn" style="background: var(--green); color: var(--cream);" id="btnViewToEdit" onclick="insManager.switchViewToEdit()">
                    <i data-lucide="edit-3" class="w-4 h-4 inline mr-1"></i> Edit This Record
                </button>
            </div>
        </div>
    </div>

    <!-- Script Configuration -->
    <script>
        window.BILLING_API = '/GM_HMS/api/';
        window.USER_ROLE   = '<?= htmlspecialchars($userRole) ?>';
        window.USER_NAME   = '<?= htmlspecialchars($userName) ?>';
    </script>
    <script src="assets/js/ip_insurance.js?v=<?= time() ?>"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
