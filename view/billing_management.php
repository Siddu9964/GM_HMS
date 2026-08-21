<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Management - GM HMS</title>

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Common Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin_common.css">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .billing-tab {
            position: relative;
            cursor: pointer;
            padding: 1rem 1.5rem;
            color: rgba(31, 107, 74, 0.6);
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .billing-tab:hover {
            color: rgba(31, 107, 74, 0.9);
        }

        .billing-tab.active {
            color: #1f6b4a;
            font-weight: 700;
        }

        .billing-tab.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: #1f6b4a;
        }

        /* Preset buttons */
        .rec-preset-btn {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            background: #ffffff;
            color: #1f6b4a;
            border: 1.5px solid rgba(31, 107, 74, 0.25);
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }
        .rec-preset-btn:hover {
            border-color: #1f6b4a;
            background: #f3efe6;
        }
        .rec-preset-btn.active {
            background: #1f6b4a !important;
            color: #ffffff !important;
            border-color: #1f6b4a !important;
            box-shadow: 0 4px 12px rgba(31, 107, 74, 0.25);
        }

        /* Sub-tab Navigation within Receipts */
        .rec-subtab-btn {
            padding: 10px 18px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #64748b;
            border-bottom: 2.5px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
        }
        .rec-subtab-btn:hover {
            color: #1f6b4a;
        }
        .rec-subtab-btn.active {
            color: #1f6b4a;
            border-bottom-color: #1f6b4a;
            font-weight: 800;
        }

        /* Slide-in Receipt Drawer */
        .drawer-slide-in {
            animation: slideInRight 0.28s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Select2 Premium Styling */
        .select2-container--default .select2-selection--single {
            height: 42px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
        }

        /* Select2 Dropdown Enhancements */
        .select2-dropdown {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .select2-results__option {
            padding: 0 !important;
        }

        .select2-results__option--highlighted {
            background-color: #f8fafc !important;
        }

        .select2-results__option--selected {
            background-color: #eff6ff !important;
        }

        .patient-result-item:hover {
            background-color: #f1f5f9;
        }

        .select2-search--dropdown {
            padding: 12px;
            background: #f8fafc;
        }

        .select2-search__field {
            border: 1px solid #e2e8f0 !important;
            border-radius: 8px !important;
            padding: 8px 12px !important;
            font-size: 14px !important;
        }

        .select2-search__field:focus {
            border-color: #3b82f6 !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }

        /* Modal Animation */
        @keyframes modalFade {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-modal {
            animation: modalFade 0.3s ease-out forwards;
        }

        /* Table Styles */
        .premium-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }

        .premium-table thead th {
            background: #1f6b4a;
            color: #f3efe6;
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            padding: 1rem 1.5rem;
            border-bottom: none;
        }
        
        .premium-table thead th:first-child {
            border-top-left-radius: 0.75rem;
        }
        
        .premium-table thead th:last-child {
            border-top-right-radius: 0.75rem;
        }

        .premium-table tbody {
            background: #fdfdfc;
        }

        .premium-table tbody td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(31, 107, 74, 0.15);
            color: #1f6b4a;
            font-weight: 500;
        }
        
        .premium-table tbody tr:hover td {
            background-color: #f3efe6;
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-900">

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Navbar -->
            <?php include 'includes/navbar.php'; ?>

            <main class="flex-1 overflow-y-auto p-4 md:p-6">

                <!-- Page Header -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                    <div>
                        <h1 class="text-3xl font-black tracking-tight text-slate-900 flex items-center gap-3">
                            <span class="p-2 rounded-lg" style="background: var(--gm-accent);">
                                <i class="fas fa-file-invoice-dollar text-white"></i>
                            </span>
                            Billing Management
                        </h1>
                        <p class="text-slate-500 mt-1 font-medium">Streamlined financial operations for OPD/IPD</p>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bento-card">
                        <div class="bento-title">Today's Revenue</div>
                        <h3 class="bento-value" id="stat-today-revenue">₹0.00</h3>
                        <i class="fas fa-rupee-sign bento-icon"></i>
                    </div>

                    <div class="bento-card">
                        <div class="bento-title">Month to Date</div>
                        <h3 class="bento-value" id="stat-month-revenue">₹0.00</h3>
                        <i class="fas fa-chart-line bento-icon"></i>
                    </div>

                    <div class="bento-card">
                        <div class="bento-title">Pending Bills</div>
                        <h3 class="bento-value" id="stat-pending-bills">0</h3>
                        <i class="fas fa-clock bento-icon"></i>
                    </div>

                    <div class="bento-card">
                        <div class="bento-title">Outstanding</div>
                        <h3 class="bento-value" id="stat-outstanding">₹0.00</h3>
                        <i class="fas fa-exclamation-triangle bento-icon"></i>
                    </div>
                </div>

                <!-- Tabs Container -->
                <div class="table-container mb-8">
                    <div class="flex border-b border-slate-100 overflow-x-auto">
                        <div class="billing-tab active" onclick="switchTab('opd')">
                            <i class="fas fa-stethoscope mr-2"></i> OPD Billing
                        </div>
                        <div class="billing-tab" onclick="switchTab('payments')">
                            <i class="fas fa-receipt mr-2"></i> Receipts
                        </div>
                        <div class="billing-tab" onclick="switchTab('reports')">
                            <i class="fas fa-chart-pie mr-2"></i> Analytics
                        </div>
                    </div>

                    <!-- OPD Billing Tab Content -->
                    <div id="tab-opd" class="tab-content">
                        <div class="p-6 rounded-xl" style="background-color: #f3efe6; border: 1px solid rgba(31, 107, 74, 0.2); box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                            <h3 class="text-xl font-black flex items-center gap-2" style="color: #1f6b4a;">
                                <i class="fas fa-list-alt"></i> Recent Transactions
                            </h3>
                            <div class="flex items-center gap-3 w-full md:w-auto">
                                <div class="relative flex-1 md:w-64">
                                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2" style="color: rgba(31, 107, 74, 0.6);"></i>
                                    <input type="text" id="search-bills" placeholder="Search invoices..."
                                        class="w-full pl-10 pr-4 py-2 bg-white rounded-xl outline-none transition-all"
                                        style="border: 1px solid rgba(31, 107, 74, 0.3); color: #1f6b4a;"
                                        onfocus="this.style.borderColor='#1f6b4a'; this.style.boxShadow='0 0 0 3px rgba(31, 107, 74, 0.15)';"
                                        onblur="this.style.borderColor='rgba(31, 107, 74, 0.3)'; this.style.boxShadow='none';">
                                </div>
                                <select id="filter-status" onchange="loadBills()"
                                    class="px-4 py-2 bg-white rounded-xl outline-none transition-all font-medium"
                                    style="border: 1px solid rgba(31, 107, 74, 0.3); color: #1f6b4a; cursor: pointer;"
                                    onfocus="this.style.borderColor='#1f6b4a'; this.style.boxShadow='0 0 0 3px rgba(31, 107, 74, 0.15)';"
                                    onblur="this.style.borderColor='rgba(31, 107, 74, 0.3)'; this.style.boxShadow='none';">
                                    <option value="">All Status</option>
                                    <option value="Paid">Paid</option>
                                    <option value="Partial">Partial</option>
                                    <option value="Pending">Pending</option>
                                </select>
                            </div>
                        </div>

                        <div class="overflow-x-auto bg-white rounded-xl" style="border: 1px solid rgba(31, 107, 74, 0.2); box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                            <table class="w-full premium-table">
                                <thead>
                                    <tr>
                                        <th>Bill ID</th>
                                        <th>Patient Details</th>
                                        <th>Consultant</th>
                                        <th>Date</th>
                                        <th class="text-right">Amount</th>
                                        <th class="text-right">Received</th>
                                        <th class="text-right">Balance</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center sticky right-0 bg-white z-10 shadow-[inset_1px_0_0_rgba(0,0,0,0.05)]">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="bills-tbody">
                                    <tr>
                                        <td colspan="9" class="px-6 py-12 text-center" style="background-color: #fdfdfc;">
                                            <div class="animate-pulse flex flex-col items-center gap-4">
                                                <div class="h-10 w-10 rounded-full" style="background-color: #e8f4ed;"></div>
                                                <div class="h-4 w-48 rounded" style="background-color: #e8f4ed;"></div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    </div>

                    <!-- Receipts Tab Content (Advanced & User-Friendly) -->
                    <div id="tab-payments" class="tab-content hidden">
                        <div class="space-y-6">

                            <!-- 1. Header & Quick Preset Navigation Pills -->
                            <div class="p-6 rounded-2xl bg-white border border-[#1f6b4a30] shadow-sm flex flex-col gap-4">
                                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 border-b border-[#1f6b4a15] pb-4">
                                    <div>
                                        <div class="flex items-center gap-3">
                                            <span class="p-2.5 rounded-xl bg-[#1f6b4a] text-white shadow-sm">
                                                <i class="fas fa-receipt text-lg"></i>
                                            </span>
                                            <div>
                                                <h2 class="text-2xl font-black text-[#1f6b4a] tracking-tight">Receipts & Cash Management</h2>
                                                <p class="text-xs text-slate-500 font-semibold mt-0.5">Centralized payment tracking, cashier shift handovers, and collection audit ledger</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto">
                                        <button type="button" onclick="exportReceiptsToExcel()" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm flex items-center gap-1.5">
                                            <i class="fas fa-file-excel"></i> Export Excel
                                        </button>
                                        <button type="button" onclick="exportReceiptsToCSV()" class="px-3.5 py-2 bg-slate-700 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition-all shadow-sm flex items-center gap-1.5">
                                            <i class="fas fa-file-csv"></i> Export CSV
                                        </button>
                                        <button type="button" onclick="printShiftHandover()" class="px-3.5 py-2 bg-[#1f6b4a] hover:bg-[#144d34] text-white rounded-xl text-xs font-bold transition-all shadow-sm flex items-center gap-1.5">
                                            <i class="fas fa-print"></i> Shift Handover Sheet
                                        </button>
                                        <button type="button" onclick="resetReceiptFilters()" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5" title="Reset All Filters">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Quick Preset Filter Pills -->
                                <div class="flex flex-wrap items-center gap-2 pt-1">
                                    <span class="text-xs font-bold text-[#1f6b4a] uppercase tracking-wider mr-1 flex items-center gap-1">
                                        <i class="fas fa-bolt text-amber-500"></i> Quick Presets:
                                    </span>
                                    <button type="button" class="rec-preset-btn" data-preset="today" onclick="setReceiptDatePreset('today')">Today</button>
                                    <button type="button" class="rec-preset-btn" data-preset="yesterday" onclick="setReceiptDatePreset('yesterday')">Yesterday</button>
                                    <button type="button" class="rec-preset-btn" data-preset="this_week" onclick="setReceiptDatePreset('this_week')">This Week</button>
                                    <button type="button" class="rec-preset-btn" data-preset="this_month" onclick="setReceiptDatePreset('this_month')">This Month</button>
                                    <button type="button" class="rec-preset-btn" data-preset="this_year" onclick="setReceiptDatePreset('this_year')">This Year</button>
                                    <button type="button" class="rec-preset-btn active" data-preset="all" onclick="setReceiptDatePreset('all')">All Time</button>
                                    <div class="h-4 w-px bg-slate-300 mx-1"></div>
                                    <button type="button" class="rec-preset-btn text-rose-700 border-rose-200 hover:bg-rose-50" id="rec-pill-outstanding" onclick="toggleReceiptOutstanding()">
                                        <i class="fas fa-exclamation-circle text-rose-500"></i> Outstanding Due Only
                                    </button>
                                    <button type="button" class="rec-preset-btn text-amber-700 border-amber-200 hover:bg-amber-50" id="rec-pill-duplicates" onclick="toggleReceiptDuplicates()">
                                        <i class="fas fa-clone text-amber-500"></i> Potential Duplicates
                                    </button>
                                    <button type="button" class="rec-preset-btn text-blue-700 border-blue-200 hover:bg-blue-50" id="rec-pill-highval" onclick="toggleReceiptHighValue()">
                                        <i class="fas fa-shield-alt text-blue-500"></i> High Value (≥₹5,000)
                                    </button>
                                </div>
                            </div>

                            <!-- 2. Sticky Multi-Filter Control Console -->
                            <div class="p-5 rounded-2xl bg-white border border-[#1f6b4a30] shadow-sm space-y-4" id="rec-sticky-filter-panel">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                                    <!-- Omni-Search -->
                                    <div class="lg:col-span-2 relative">
                                        <label class="block text-[10px] font-bold text-[#1f6b4a] uppercase tracking-wider mb-1">
                                            <i class="fas fa-search"></i> Omni-Search (Bill #, Receipt #, Patient, PID, Mobile, Doctor)
                                        </label>
                                        <div class="relative">
                                            <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                                            <input type="text" id="rec-search-input" placeholder="Type Bill/Receipt #, Patient Name, PID, Mobile..." 
                                                class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-[#1f6b4a] outline-none transition-all focus:bg-white focus:border-[#1f6b4a] focus:ring-2 focus:ring-[#1f6b4a20]"
                                                oninput="handleReceiptSearchInput()">
                                        </div>
                                    </div>

                                    <!-- Date From -->
                                    <div>
                                        <label class="block text-[10px] font-bold text-[#1f6b4a] uppercase tracking-wider mb-1">From Date</label>
                                        <input type="date" id="rec-filter-date-from" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-[#1f6b4a] outline-none focus:bg-white focus:border-[#1f6b4a]" onchange="triggerReceiptFilter()">
                                    </div>

                                    <!-- Date To -->
                                    <div>
                                        <label class="block text-[10px] font-bold text-[#1f6b4a] uppercase tracking-wider mb-1">To Date</label>
                                        <input type="date" id="rec-filter-date-to" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-[#1f6b4a] outline-none focus:bg-white focus:border-[#1f6b4a]" onchange="triggerReceiptFilter()">
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Summary Performance Bento KPIs -->
                            <div class="grid grid-cols-2 lg:grid-cols-6 gap-3.5">
                                <div class="bg-white p-4 rounded-2xl border border-[#1f6b4a25] shadow-sm flex flex-col justify-between">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Total Receipts</span>
                                        <span class="h-7 w-7 rounded-lg bg-[#e8f4ed] text-[#1f6b4a] flex items-center justify-center text-xs"><i class="fas fa-file-invoice"></i></span>
                                    </div>
                                    <div class="text-2xl font-black text-[#1f6b4a] mt-2" id="kpi-rec-total-count">0</div>
                                    <span class="text-[10px] text-slate-400 font-semibold" id="kpi-rec-count-sub">0 Paid • 0 Pending</span>
                                </div>

                                <div class="bg-white p-4 rounded-2xl border border-emerald-200 shadow-sm flex flex-col justify-between">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-black text-emerald-700 uppercase tracking-widest">Total Collected</span>
                                        <span class="h-7 w-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs"><i class="fas fa-rupee-sign"></i></span>
                                    </div>
                                    <div class="text-2xl font-black text-emerald-700 mt-2" id="kpi-rec-total-collected">₹0.00</div>
                                    <span class="text-[10px] text-emerald-600 font-semibold" id="kpi-rec-today-sub">Today: ₹0.00</span>
                                </div>

                                <div class="bg-white p-4 rounded-2xl border border-rose-200 shadow-sm flex flex-col justify-between">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-black text-rose-700 uppercase tracking-widest">Pending Due</span>
                                        <span class="h-7 w-7 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center text-xs"><i class="fas fa-clock"></i></span>
                                    </div>
                                    <div class="text-2xl font-black text-rose-700 mt-2" id="kpi-rec-total-pending">₹0.00</div>
                                    <span class="text-[10px] text-rose-500 font-semibold">Unsettled Balances</span>
                                </div>

                                <div class="bg-white p-4 rounded-2xl border border-amber-200 shadow-sm flex flex-col justify-between">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-black text-amber-700 uppercase tracking-widest">Total Discounts</span>
                                        <span class="h-7 w-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-xs"><i class="fas fa-tags"></i></span>
                                    </div>
                                    <div class="text-2xl font-black text-amber-700 mt-2" id="kpi-rec-total-discount">₹0.00</div>
                                    <span class="text-[10px] text-amber-600 font-semibold">Concessions Allowed</span>
                                </div>

                                <div class="bg-white p-4 rounded-2xl border border-purple-200 shadow-sm flex flex-col justify-between">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-black text-purple-700 uppercase tracking-widest">Refunds / Cancels</span>
                                        <span class="h-7 w-7 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center text-xs"><i class="fas fa-undo"></i></span>
                                    </div>
                                    <div class="text-2xl font-black text-purple-700 mt-2" id="kpi-rec-total-refunds">₹0.00</div>
                                    <span class="text-[10px] text-purple-500 font-semibold" id="kpi-rec-cancel-count">0 Cancelled</span>
                                </div>

                                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-black text-slate-600 uppercase tracking-widest">Avg Bill Value</span>
                                        <span class="h-7 w-7 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center text-xs"><i class="fas fa-chart-line"></i></span>
                                    </div>
                                    <div class="text-2xl font-black text-slate-800 mt-2" id="kpi-rec-avg-value">₹0.00</div>
                                    <span class="text-[10px] text-slate-400 font-semibold">Per Receipt Mean</span>
                                </div>
                            </div>

                            <!-- 4. Sub-Navigation Tabs within Receipts Section -->
                            <div class="flex border-b border-slate-200 overflow-x-auto bg-white rounded-t-2xl px-4 pt-2 shadow-sm gap-2">
                                <button type="button" class="rec-subtab-btn active" onclick="switchReceiptSubTab('ledger')">
                                    <i class="fas fa-table-list mr-1.5"></i> All Receipts Ledger
                                </button>
                                <button type="button" class="rec-subtab-btn" onclick="switchReceiptSubTab('shift')">
                                    <i class="fas fa-business-time mr-1.5"></i> Shift Handover Matrix
                                </button>
                                <button type="button" class="rec-subtab-btn" onclick="switchReceiptSubTab('dept')">
                                    <i class="fas fa-hospital mr-1.5"></i> Department & Doctor Breakdown
                                </button>
                                <button type="button" class="rec-subtab-btn" onclick="switchReceiptSubTab('staff')">
                                    <i class="fas fa-users-cog mr-1.5"></i> Cashier / Staff Collection
                                </button>
                                <button type="button" class="rec-subtab-btn" onclick="switchReceiptSubTab('charts')">
                                    <i class="fas fa-chart-pie mr-1.5"></i> Visual Trends & Charts
                                </button>
                            </div>

                            <!-- SUB-TAB 1: All Receipts Ledger -->
                            <div id="rec-subview-ledger" class="rec-subview">
                                <div class="bg-white rounded-b-2xl border border-slate-200 overflow-hidden shadow-sm">
                                    <div class="overflow-x-auto">
                                        <table class="w-full premium-table" id="rec-main-table">
                                            <thead>
                                                <tr>
                                                    <th>Receipt / Bill ID</th>
                                                    <th>Patient Details</th>
                                                    <th>Department & Doctor</th>
                                                    <th>Date & Time</th>
                                                    <th class="text-right">Grand Total</th>
                                                    <th class="text-right">Received</th>
                                                    <th class="text-right">Balance Due</th>
                                                    <th class="text-center">Mode</th>
                                                    <th class="text-center">Status</th>
                                                    <th class="text-center">Cashier</th>
                                                    <th class="text-center sticky right-0 bg-[#1f6b4a] z-10">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="receipts-tbody">
                                                <tr>
                                                    <td colspan="11" class="text-center py-12 text-slate-500">
                                                        <i class="fas fa-spinner fa-spin text-2xl text-[#1f6b4a]"></i>
                                                        <p class="mt-2 text-xs font-bold text-slate-600">Loading receipts data...</p>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Pagination Footer -->
                                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                                        <div class="font-bold text-slate-600" id="rec-pagination-info">
                                            Showing 0 to 0 of 0 receipts
                                        </div>
                                        <div class="flex items-center gap-1.5" id="rec-pagination-btns">
                                            <!-- Injected by JS -->
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SUB-TAB 2: Shift Handover & Staff Multi-Mode Reconciliation -->
                            <div id="rec-subview-shift" class="rec-subview hidden space-y-6">
                                <div class="bg-white rounded-b-2xl border border-slate-200 p-6 shadow-sm space-y-6">
                                    
                                    <!-- Header & Export/Print Actions Strip -->
                                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-gradient-to-r from-[#fdfbf7] via-white to-[#f3efe6]/40 p-5 rounded-2xl border border-[#1f6b4a25] shadow-xs">
                                        <div class="flex items-center gap-3">
                                            <div class="h-12 w-12 rounded-2xl bg-[#1f6b4a] text-white flex items-center justify-center text-xl shadow-sm">
                                                <i class="fas fa-cash-register"></i>
                                            </div>
                                            <div>
                                                <h3 class="text-base sm:text-lg font-black text-[#1f6b4a] flex items-center gap-2">
                                                    Cashier Shift Handover & Reconciliation
                                                    <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-[10.5px] font-black rounded-full border border-emerald-300 uppercase tracking-wider">
                                                        User Audit
                                                    </span>
                                                </h3>
                                                <p class="text-xs text-slate-500 font-medium mt-0.5">
                                                    Staff login activity, payment mode breakdown (Cash, UPI, Card, Other), and shift drawer handover.
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Action Buttons: Export CSV, Excel & Print -->
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button" onclick="exportCashierShift('csv')" class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-xs" title="Export Cashier Shift Data to CSV">
                                                <i class="fas fa-file-csv text-emerald-600"></i> Export CSV
                                            </button>
                                            <button type="button" onclick="exportCashierShift('excel')" class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-xs" title="Export Cashier Shift Data to Excel">
                                                <i class="fas fa-file-excel text-emerald-700"></i> Export Excel
                                            </button>
                                            <button type="button" onclick="printFullShiftHandoverReport()" class="px-4 py-2 bg-[#1f6b4a] hover:bg-[#144d34] text-white rounded-xl text-xs font-black transition-all flex items-center gap-1.5 shadow-sm" title="Print Full Shift Handover Statement">
                                                <i class="fas fa-print"></i> Print Shift Handover Report
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Summary Shift KPIs -->
                                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                                        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
                                            <div class="text-[10px] uppercase font-bold text-slate-400">Active Cashiers / Users</div>
                                            <div class="text-xl font-black text-slate-800 mt-1" id="shift-kpi-cashiers">0</div>
                                            <div class="text-[11px] text-slate-500 font-medium mt-0.5">Logged in staff</div>
                                        </div>
                                        <div class="bg-emerald-50/70 p-4 rounded-xl border border-emerald-200 shadow-xs">
                                            <div class="text-[10px] uppercase font-bold text-emerald-800">Cash Drawer Collection</div>
                                            <div class="text-xl font-black text-emerald-700 mt-1" id="shift-kpi-cash">₹0.00</div>
                                            <div class="text-[11px] text-emerald-600 font-semibold mt-0.5">Physical cash drawer</div>
                                        </div>
                                        <div class="bg-blue-50/70 p-4 rounded-xl border border-blue-200 shadow-xs">
                                            <div class="text-[10px] uppercase font-bold text-blue-800">Digital (UPI + Card)</div>
                                            <div class="text-xl font-black text-blue-700 mt-1" id="shift-kpi-digital">₹0.00</div>
                                            <div class="text-[11px] text-blue-600 font-semibold mt-0.5">Electronic payments</div>
                                        </div>
                                        <div class="bg-[#1f6b4a]/10 p-4 rounded-xl border border-[#1f6b4a]/30 shadow-xs">
                                            <div class="text-[10px] uppercase font-bold text-[#1f6b4a]">Total Shift Revenue</div>
                                            <div class="text-xl font-black text-[#1f6b4a] mt-1" id="shift-kpi-total">₹0.00</div>
                                            <div class="text-[11px] text-emerald-700 font-semibold mt-0.5">100% Reconciled</div>
                                        </div>
                                    </div>

                                    <!-- Cashier User Login Cards Container -->
                                    <div class="space-y-4">
                                        <h4 class="font-black text-[#1f6b4a] text-sm flex items-center gap-2">
                                            <i class="fas fa-users-viewfinder text-[#1f6b4a]"></i> Cashier / User Shift Handover Matrix
                                        </h4>
                                        <div id="rec-cashier-cards-container" class="space-y-4">
                                            <!-- Injected dynamically by JS -->
                                        </div>
                                    </div>

                                    <!-- All Shift Bills with Generating Staff Register & Live Filters -->
                                    <div class="space-y-4 pt-4 border-t border-slate-200">
                                        
                                        <!-- Header & Filter Bar -->
                                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
                                            <div>
                                                <h4 class="font-black text-[#1f6b4a] text-sm flex items-center gap-2">
                                                    <i class="fas fa-file-invoice-dollar text-[#1f6b4a]"></i> All Shift Receipts Ledger (With Generating Staff)
                                                </h4>
                                                <p class="text-xs text-slate-500 font-medium mt-0.5">
                                                    Showing every bill with the cashier who generated it, payment mode, and direct receipt print action.
                                                </p>
                                            </div>

                                            <!-- Live Filter Toolbar -->
                                            <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
                                                <!-- Cashier Filter -->
                                                <select id="rec-shift-staff-filter" onchange="filterShiftBills()" class="px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-800 outline-none focus:border-[#1f6b4a] shadow-xs">
                                                    <option value="all">👤 All Staff / Cashiers</option>
                                                </select>

                                                <!-- Payment Mode Filter -->
                                                <select id="rec-shift-mode-filter" onchange="filterShiftBills()" class="px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-800 outline-none focus:border-[#1f6b4a] shadow-xs">
                                                    <option value="all">💳 All Payment Modes</option>
                                                    <option value="Cash">💵 Cash</option>
                                                    <option value="UPI">📱 UPI / QR</option>
                                                    <option value="Card">💳 Card / POS</option>
                                                    <option value="Other">🏦 Other / Bank</option>
                                                </select>

                                                <!-- Search Input -->
                                                <div class="relative flex-1 sm:w-48 md:w-56">
                                                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                                    <input type="text" id="rec-shift-search-input" placeholder="Search bill, patient, staff..." 
                                                        class="w-full pl-8 pr-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 outline-none focus:border-[#1f6b4a] shadow-xs"
                                                        oninput="filterShiftBills()">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Shift Bills Table -->
                                        <div class="overflow-x-auto border border-slate-200 rounded-xl bg-white shadow-xs">
                                            <table class="w-full text-xs">
                                                <thead class="bg-slate-50 border-b text-slate-400 font-bold uppercase text-[10.5px] tracking-wider">
                                                    <tr>
                                                        <th class="py-3 px-4 text-left">#</th>
                                                        <th class="py-3 px-4 text-left">Receipt # / Bill ID</th>
                                                        <th class="py-3 px-4 text-left">Generated By (Staff)</th>
                                                        <th class="py-3 px-4 text-left">Patient Details</th>
                                                        <th class="py-3 px-4 text-left">Doctor / Service</th>
                                                        <th class="py-3 px-4 text-left">Date & Time</th>
                                                        <th class="py-3 px-3 text-center">Payment Mode</th>
                                                        <th class="py-3 px-3 text-right">Billed Amount</th>
                                                        <th class="py-3 px-3 text-right font-black text-emerald-800">Collected</th>
                                                        <th class="py-3 px-3 text-right">Balance Due</th>
                                                        <th class="py-3 px-3 text-center">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="rec-shift-all-bills-tbody" class="divide-y divide-slate-100">
                                                    <!-- Injected dynamically by JS -->
                                                </tbody>
                                                <tfoot id="rec-shift-all-bills-tfoot" class="bg-slate-50 font-black text-xs text-[#1f6b4a] border-t-2 border-slate-200">
                                                    <!-- Injected dynamically by JS -->
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- SUB-TAB 3: Doctor & Department Revenue Intelligence Matrix -->
                            <div id="rec-subview-dept" class="rec-subview hidden space-y-6">
                                
                                <!-- Top Bar with Live Search & Controls -->
                                <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-xl bg-[#1f6b4a] text-white flex items-center justify-center text-lg shadow-sm">
                                            <i class="fas fa-hospital-user"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-black text-slate-800 text-sm md:text-base flex items-center gap-2">
                                                Department & Doctor Revenue Intelligence
                                                <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-black rounded-full border border-emerald-300 uppercase tracking-wider">
                                                    Clinical Hierarchy
                                                </span>
                                            </h4>
                                            <p class="text-xs text-slate-500 font-medium mt-0.5">
                                                Department-level totals with individual physician breakdowns and 1-click date-wise full statement drilldown.
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Live Filter inside Matrix -->
                                    <div class="flex items-center gap-2">
                                        <div class="relative w-full md:w-72">
                                            <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                            <input type="text" id="rec-doc-matrix-search" placeholder="Search department or doctor..." 
                                                class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-[#1f6b4a] outline-none focus:border-[#1f6b4a] focus:bg-white shadow-xs transition-all"
                                                oninput="filterDepartmentHierarchyCards(this.value)">
                                        </div>
                                    </div>
                                </div>

                                <!-- Dynamic Department-wise Doctor Cards Container -->
                                <div id="rec-dept-hierarchy-container" class="space-y-6">
                                    <!-- Injected dynamically by JS -->
                                </div>
                            </div>

                            <!-- SUB-TAB 4: Cashier / Staff Collection -->
                            <div id="rec-subview-staff" class="rec-subview hidden">
                                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                                    <h4 class="font-black text-[#1f6b4a] text-sm mb-4 flex items-center gap-2">
                                        <i class="fas fa-users-cog text-[#1f6b4a]"></i> Cashier / User Collection Audit
                                    </h4>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-xs">
                                            <thead class="bg-slate-50 border-b text-slate-500 font-bold uppercase">
                                                <tr>
                                                    <th class="py-2.5 px-4 text-left">Rank</th>
                                                    <th class="py-2.5 px-4 text-left">Staff Username</th>
                                                    <th class="py-2.5 px-4 text-center">Receipts Count</th>
                                                    <th class="py-2.5 px-4 text-right">Collected Amount</th>
                                                    <th class="py-2.5 px-4 text-right">Pending Balance</th>
                                                </tr>
                                            </thead>
                                            <tbody id="rec-staff-tbody">
                                                <!-- Injected by JS -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- SUB-TAB 5: Visual Trends & Charts -->
                            <div id="rec-subview-charts" class="rec-subview hidden">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                                        <h4 class="font-bold text-[#1f6b4a] text-sm mb-4">Daily Collection Trends</h4>
                                        <div class="h-64 relative flex items-center justify-center">
                                            <canvas id="recTrendsChart"></canvas>
                                        </div>
                                    </div>
                                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                                        <h4 class="font-bold text-[#1f6b4a] text-sm mb-4">Payment Mode Share</h4>
                                        <div class="h-64 relative flex items-center justify-center">
                                            <canvas id="recModeChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Analytics Tab Content -->
                    <div id="tab-reports" class="tab-content hidden">
                        <div class="p-6 rounded-xl" style="background-color: #f3efe6; border: 1px solid rgba(31, 107, 74, 0.2); box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                            
                            <!-- Analytics Header & Filters -->
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                                <h3 class="text-xl font-black flex items-center gap-2" style="color: #1f6b4a;">
                                    <i class="fas fa-chart-pie"></i> OPD Financial Analytics
                                </h3>
                                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                                    <input type="date" id="analytics-start" class="px-3 py-2 bg-white rounded-lg outline-none transition-all text-sm font-medium" style="border: 1px solid rgba(31, 107, 74, 0.3); color: #1f6b4a;" onchange="loadAnalytics()">
                                    <span style="color: #1f6b4a;">to</span>
                                    <input type="date" id="analytics-end" class="px-3 py-2 bg-white rounded-lg outline-none transition-all text-sm font-medium" style="border: 1px solid rgba(31, 107, 74, 0.3); color: #1f6b4a;" onchange="loadAnalytics()">
                                    
                                    <select id="analytics-receptionist" class="px-3 py-2 bg-white rounded-lg outline-none transition-all text-sm font-medium" style="border: 1px solid rgba(31, 107, 74, 0.3); color: #1f6b4a;" onchange="loadAnalytics()">
                                        <option value="">All Receptionists</option>
                                    </select>
                                    
                                    <select id="analytics-method" class="px-3 py-2 bg-white rounded-lg outline-none transition-all text-sm font-medium" style="border: 1px solid rgba(31, 107, 74, 0.3); color: #1f6b4a;" onchange="loadAnalytics()">
                                        <option value="">All Payment Modes</option>
                                        <option value="Cash">Cash</option>
                                        <option value="UPI">UPI</option>
                                        <option value="Card">Card</option>
                                        <option value="Bank Transfer">Bank Transfer</option>
                                    </select>
                                </div>
                            </div>

                            <!-- KPI Cards -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm">
                                    <div class="text-xs font-bold text-slate-500 uppercase mb-1">Total OPD Bills</div>
                                    <div class="text-2xl font-black text-[#1f6b4a]" id="kpi-total-bills">0</div>
                                </div>
                                <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm">
                                    <div class="text-xs font-bold text-slate-500 uppercase mb-1">Total Billing Amt</div>
                                    <div class="text-2xl font-black text-[#1f6b4a]" id="kpi-total-billing">₹0</div>
                                </div>
                                <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm">
                                    <div class="text-xs font-bold text-green-600 uppercase mb-1">Amount Collected</div>
                                    <div class="text-2xl font-black text-green-600" id="kpi-collected">₹0</div>
                                </div>
                                <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm">
                                    <div class="text-xs font-bold text-red-500 uppercase mb-1">Pending Balance</div>
                                    <div class="text-2xl font-black text-red-500" id="kpi-pending">₹0</div>
                                </div>
                                <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm">
                                    <div class="text-xs font-bold text-amber-500 uppercase mb-1">Total Discounts</div>
                                    <div class="text-2xl font-black text-amber-500" id="kpi-discount">₹0</div>
                                </div>
                                <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm">
                                    <div class="text-xs font-bold text-purple-500 uppercase mb-1">Total Refunds</div>
                                    <div class="text-2xl font-black text-purple-500" id="kpi-refunds">₹0</div>
                                </div>
                                <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm">
                                    <div class="text-xs font-bold text-slate-500 uppercase mb-1">Average Bill Value</div>
                                    <div class="text-2xl font-black text-slate-700" id="kpi-avg">₹0</div>
                                </div>
                                <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm">
                                    <div class="text-xs font-bold text-slate-500 uppercase mb-1">Cancelled Bills</div>
                                    <div class="text-2xl font-black text-slate-700" id="kpi-cancelled">0</div>
                                </div>
                            </div>

                            <!-- Charts -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                                <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm">
                                    <h4 class="font-bold mb-4 text-[#1f6b4a]">Weekly Revenue (Last 7 Days)</h4>
                                    <div class="relative h-64 flex justify-center items-center">
                                        <canvas id="weeklyRevenueChart"></canvas>
                                    </div>
                                </div>
                                <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm">
                                    <h4 class="font-bold mb-4 text-[#1f6b4a]">Payment Method Breakdown</h4>
                                    <div class="relative h-64 flex justify-center items-center">
                                        <canvas id="paymentChart"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- Receptionist Performance Table -->
                            <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                                <div class="p-4 border-b border-slate-100">
                                    <h4 class="font-bold text-[#1f6b4a]"><i class="fas fa-users-cog mr-2"></i> Receptionist-wise Performance</h4>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full premium-table">
                                        <thead>
                                            <tr>
                                                <th>Rank</th>
                                                <th>Receptionist</th>
                                                <th class="text-center">Bills Gen.</th>
                                                <th class="text-right">Total Billing</th>
                                                <th class="text-right">Collected</th>
                                                <th class="text-right">Pending</th>
                                            </tr>
                                        </thead>
                                        <tbody id="receptionist-performance-tbody">
                                            <tr>
                                                <td colspan="6" class="text-center py-6 text-slate-500">Loading performance data...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- NEW BILL MODAL (Professional Redesign) -->
    <div id="billing-form-container"
        class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4">
        <div class="w-full max-w-6xl max-h-[90vh] rounded-lg shadow-2xl overflow-hidden flex flex-col animate-modal" style="background-color: #f3efe6;">

            <!-- Modal Header -->
            <div class="px-6 py-4 bg-[#f9f9f9] border-b border-gray-200 flex justify-between items-center rounded-t-lg">
                <h3 id="form-mode-title" class="text-lg font-black flex items-center gap-2" style="color: #1f6b4a;">
                    New OPD Invoice
                </h3>
                <button onclick="toggleBillingForm()"
                    class="hover:opacity-70 transition-all flex items-center justify-center" style="color: #1f6b4a;">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form id="opd-billing-form" class="flex-1 overflow-y-auto flex flex-col">
                <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6 flex-1">
                    
                    <!-- LEFT COLUMN -->
                    <div class="space-y-6">
                        <!-- Primary Details -->
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <i class="fas fa-file-invoice" style="color: #1f6b4a;"></i>
                                <h4 class="text-sm font-bold" style="color: #1f6b4a;">Primary Details</h4>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold uppercase tracking-wider" style="color: #1f6b4a;">Search Patient *</label>
                                    <select id="patient-select" name="patient_id" class="w-full py-1.5 px-2 bg-white border border-gray-300 rounded outline-none focus:border-[#1f6b4a] text-xs" required></select>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold uppercase tracking-wider" style="color: #1f6b4a;">Consulting Physician</label>
                                    <select id="doctor-select" name="doctor_id" class="w-full py-1.5 px-2 bg-white border border-gray-300 rounded outline-none focus:border-[#1f6b4a] text-xs"></select>
                                </div>

                                <!-- Hidden Patient Info Card -->
                                <div id="patient-info"
                                    class="hidden col-span-2 p-3 bg-white rounded border border-[#1f6b4a40] flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="h-8 w-8 rounded flex items-center justify-center text-white text-sm" style="background: #1f6b4a;">
                                            <i class="fas fa-user-injured"></i>
                                        </div>
                                        <div>
                                            <p class="text-[9px] font-bold uppercase tracking-wider" style="color: #1f6b4a;">Selected Patient</p>
                                            <h5 class="text-sm font-black text-slate-900 leading-none" id="info-patient-id">--</h5>
                                        </div>
                                    </div>
                                    <div class="flex gap-6">
                                        <div class="text-center">
                                            <p class="text-[9px] font-bold uppercase tracking-wider" style="color: #1f6b4a;">AGE/SEX</p>
                                            <p class="font-black text-xs" id="info-age-sex">--</p>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-[9px] font-bold uppercase tracking-wider" style="color: #1f6b4a;">PHONE</p>
                                            <p class="font-black text-xs" id="info-phone">--</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing & Services -->
                        <div>
                            <div class="flex justify-between items-center mb-3">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-list" style="color: #1f6b4a;"></i>
                                    <h4 class="text-sm font-bold" style="color: #1f6b4a;">Pricing & Services</h4>
                                </div>
                                <button type="button" onclick="addBillingItem()"
                                    class="px-2 py-1 rounded text-[10px] font-bold transition-all flex items-center gap-1 border" style="background: #e8f4ed; color: #1f6b4a; border-color: #1f6b4a40;">
                                    <i class="fas fa-plus"></i> Add Line Item
                                </button>
                            </div>

                            <div class="border rounded bg-white overflow-hidden shadow-sm" style="border-color: #1f6b4a40;">
                                <table class="w-full text-xs">
                                    <thead style="background-color: #f9f9f9; border-bottom: 1px solid #1f6b4a40;">
                                        <tr class="text-left font-bold uppercase text-[9px] tracking-wider" style="color: #1f6b4a;">
                                            <th class="px-3 py-2">Service/Item Name</th>
                                            <th class="px-3 py-2 w-16 text-center">Qty</th>
                                            <th class="px-3 py-2 w-24 text-right">Unit Rate</th>
                                            <th class="px-3 py-2 w-24 text-right">Amount</th>
                                            <th class="px-2 py-2 w-8"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="billing-items-tbody">
                                        <!-- Dynamic Items -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN -->
                    <div class="space-y-6">
                        <!-- Payment Information -->
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <i class="fas fa-money-bill-wave" style="color: #1f6b4a;"></i>
                                <h4 class="text-sm font-bold" style="color: #1f6b4a;">Payment Information</h4>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold uppercase tracking-wider" style="color: #1f6b4a;">Method</label>
                                    <select name="payment_method"
                                        class="w-full py-1.5 px-2 bg-white border border-gray-300 rounded outline-none focus:border-[#1f6b4a] text-xs">
                                        <option value="Cash">Cash Payment</option>
                                        <option value="Card">Credit/Debit Card</option>
                                        <option value="UPI">UPI / QR Code</option>
                                        <option value="Net Banking">Net Banking</option>
                                        <option value="Cheque">Bank Cheque</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold uppercase tracking-wider" style="color: #1f6b4a;">Immediate Payment</label>
                                    <input type="number" name="amount_paid" id="amount-paid" step="0.01"
                                        class="w-full py-1.5 px-2 border border-gray-300 rounded font-bold outline-none focus:border-[#1f6b4a] text-xs" style="background-color: #e8f4ed; color: #1f6b4a;">
                                </div>
                                <div class="space-y-1 col-span-2">
                                    <label class="text-[10px] font-bold uppercase tracking-wider" style="color: #1f6b4a;">Internal Remarks</label>
                                    <input type="text" name="notes"
                                        class="w-full py-1.5 px-2 bg-white border border-gray-300 rounded outline-none focus:border-[#1f6b4a] text-xs"
                                        placeholder="Any special instructions...">
                                </div>
                            </div>
                        </div>

                        <!-- Summary -->
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <i class="fas fa-calculator" style="color: #1f6b4a;"></i>
                                <h4 class="text-sm font-bold" style="color: #1f6b4a;">Summary</h4>
                            </div>
                            <div class="bg-white p-4 rounded border space-y-2" style="border-color: #1f6b4a40;">
                                <div class="flex justify-between items-center text-xs font-bold" style="color: #1f6b4a;">
                                    <span>Subtotal</span>
                                    <span id="summary-subtotal">₹0.00</span>
                                </div>
                                <div class="flex justify-between items-center text-xs font-bold" style="color: #1f6b4a;">
                                    <span>Adjustment/Discount (-)</span>
                                    <div class="flex items-center gap-1">
                                        <span class="text-rose-500 font-black">-</span>
                                        <input type="number" id="discount-amount" name="discount_amount"
                                            onchange="calculateTotals()" placeholder="0.00"
                                            class="w-20 py-0.5 text-right bg-transparent border-b border-rose-200 text-rose-600 font-black outline-none focus:border-rose-500 text-xs">
                                    </div>
                                </div>
                                <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-wider pt-2 border-t" style="border-color: #1f6b4a20; color: #1f6b4a;">
                                    <span>Taxable Base</span>
                                    <span id="summary-taxable">₹0.00</span>
                                </div>
                                <div class="flex justify-between items-center pt-2 mt-2 border-t-2" style="border-color: #1f6b4a;">
                                    <span class="text-sm font-black uppercase" style="color: #1f6b4a;">Grand Total</span>
                                    <span class="text-xl font-black" id="summary-grand-total" style="color: #1f6b4a;">₹0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="px-6 py-4 flex justify-end gap-3 rounded-b-lg border-t" style="border-color: #1f6b4a20;">
                    <button type="button" onclick="toggleBillingForm()"
                        class="px-4 py-1.5 bg-white font-bold rounded text-xs transition-all border" style="color: #1f6b4a; border-color: #1f6b4a;">
                        Cancel
                    </button>
                    <button type="submit" id="btn-submit-bill"
                        class="px-4 py-1.5 text-white font-bold rounded shadow text-xs transition-all flex items-center gap-2" style="background: #1f6b4a;">
                        <i class="fas fa-save"></i>
                        Confirm & Generate
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- BILL DETAILS MODAL (Patient Card & Invoice View) -->
    <div id="bill-details-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4">
        <div class="w-full max-w-4xl max-h-[90vh] rounded-xl shadow-2xl overflow-hidden flex flex-col animate-modal" style="background-color: #f3efe6;">

            <!-- Header Controls -->
            <div class="px-6 py-4 flex justify-between items-center" style="background-color: #f3efe6;">
                <h3 class="text-lg font-bold" style="color: #1f6b4a;">
                    <i class="fas fa-file-invoice"></i> Bill Details
                </h3>
                <div class="flex items-center gap-3">
                    <button id="btn-print-modal" onclick="printBill('')" class="px-4 py-2 bg-white font-bold text-sm rounded hover:bg-slate-50 transition-all flex items-center gap-2 border shadow-sm" style="color: #1f6b4a; border-color: #1f6b4a40;">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button onclick="toggleBillModal()" class="h-8 w-8 rounded-full hover:bg-slate-200/50 transition-all flex items-center justify-center text-slate-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Receipt Content (Scrollable) -->
            <div class="flex-1 overflow-y-auto px-10 py-8 relative bg-[#fdfdfc] mx-4 mb-4 rounded-lg shadow-inner">

                <!-- Hospital Header -->
                <div class="flex justify-between items-start border-b-2 pb-4 mb-6" style="border-color: #1f6b4a;">
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight" style="color: #1f6b4a;">GM HOSPITAL (Basaveshwar Nagar)</h1>
                        <p class="text-xs text-slate-500 mt-1">No. 335, 3rd Stage, 4th Block, Siddaiah Puranik Road,</p>
                        <p class="text-xs text-slate-500">Basaveshwara nagar, Bengaluru 560079</p>
                        <p class="text-xs text-slate-500">Tel. No 0802221160 Mob. No 9900003527</p>
                        <p class="text-xs text-slate-500">GST NO: 29AAFCP8756N3ZE</p>
                    </div>
                    <div class="text-right flex flex-col items-end">
                        <div class="flex items-center gap-2 mb-1">
                            <span id="detail-payment-status"></span>
                            <h2 class="text-xl font-bold uppercase tracking-wide" style="color: #1f6b4a;">PAYMENT RECEIPT</h2>
                        </div>
                        <div class="text-sm font-bold mt-1" id="modal-bill-id" style="color: #1f6b4a;">BILL-ID</div>
                        <div class="text-xs text-slate-500 mt-1">
                            <span id="detail-bill-date">--/--/----</span> <span id="detail-bill-time"></span>
                        </div>
                    </div>
                </div>

                <!-- Patient Info Box -->
                <div class="grid grid-cols-2 gap-4 rounded-lg p-5 mb-8 border" style="background-color: #fdfdfc; border-color: #1f6b4a40;">
                    <div class="space-y-3">
                        <div class="flex items-center text-xs">
                            <span class="w-28 font-bold text-slate-400 uppercase tracking-wider">Patient</span>
                            <span class="font-bold text-slate-800" id="detail-patient-name">Patient Name</span>
                        </div>
                        <div class="flex items-center text-xs">
                            <span class="w-28 font-bold text-slate-400 uppercase tracking-wider">Phone</span>
                            <span class="font-medium text-slate-700" id="detail-patient-phone">9876543210</span>
                        </div>
                        <div class="flex items-center text-xs">
                            <span class="w-28 font-bold text-slate-400 uppercase tracking-wider">Appointment</span>
                            <span class="font-medium text-slate-700" id="detail-appointment-id">--</span>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center text-xs">
                            <span class="w-28 font-bold text-slate-400 uppercase tracking-wider">Patient ID</span>
                            <span class="font-medium text-slate-700" id="detail-patient-id">PID-00000000</span>
                        </div>
                        <div class="flex items-center text-xs">
                            <span class="w-28 font-bold text-slate-400 uppercase tracking-wider">Doctor</span>
                            <span class="font-medium text-slate-700" id="detail-doctor-name">Dr. Name</span>
                        </div>
                        <div class="flex items-center text-xs">
                            <span class="w-28 font-bold text-slate-400 uppercase tracking-wider">Created By</span>
                            <span class="font-medium text-slate-700" id="detail-created-by">System Admin</span>
                        </div>
                        <div class="flex items-center text-xs hidden">
                            <span id="detail-bill-purpose"></span>
                        </div>
                    </div>
                </div>

                <!-- Billing Items -->
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Billing Items</div>
                <table class="w-full text-sm mb-6 border-collapse">
                    <thead>
                        <tr style="background-color: #1f6b4a; color: white;">
                            <th class="px-4 py-2 text-left font-semibold text-xs rounded-l">DESCRIPTION</th>
                            <th class="px-4 py-2 text-center font-semibold text-xs">QTY</th>
                            <th class="px-4 py-2 text-right font-semibold text-xs">RATE (₹)</th>
                            <th class="px-4 py-2 text-right font-semibold text-xs rounded-r">AMOUNT (₹)</th>
                        </tr>
                    </thead>
                    <tbody id="detail-items-tbody" class="border-b" style="border-color: #1f6b4a40;">
                        <!-- Dynamic Items injected by JS -->
                    </tbody>
                </table>

                <!-- Summary Totals -->
                <div class="flex justify-end mb-6">
                    <div class="w-72 space-y-2 text-sm">
                        <div class="flex justify-between items-center text-slate-600">
                            <span>Subtotal</span>
                            <span id="foot-subtotal">₹0.00</span>
                        </div>
                        <div class="flex justify-between items-center text-slate-600">
                            <span>Discount (<span id="foot-discount-percent">0</span>%)</span>
                            <span id="foot-discount">- ₹0.00</span>
                        </div>
                        <div class="flex justify-between items-center text-slate-600 hidden">
                            <span>Taxable</span>
                            <span id="foot-taxable">₹0.00</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-y-2 mt-2 font-bold" style="border-color: #1f6b4a; color: #1f6b4a;">
                            <span>Receipt Amount</span>
                            <span id="foot-grand-total">₹0.00</span>
                        </div>
                        <div class="flex justify-between items-center text-slate-600 font-semibold pt-1">
                            <span>Payment Mode</span>
                            <span id="detail-payment-mode">Cash</span>
                        </div>
                        <div class="flex justify-between items-center text-slate-600 pt-1">
                            <span>Amount Paid</span>
                            <span id="foot-amount-paid">₹0.00</span>
                        </div>
                        <div class="flex justify-between items-center text-rose-600 font-bold pt-1">
                            <span>Balance Due</span>
                            <span id="detail-balance-due">₹0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Bottom Notes -->
                <div id="detail-notes-container" class="hidden mb-6">
                    <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Remarks</div>
                    <div class="p-3 bg-white border border-slate-200 rounded text-slate-600 text-xs whitespace-pre-line" id="detail-notes">
                    </div>
                </div>

                <!-- Footer Signatures -->
                <div class="flex justify-between items-end mt-12 pt-6 border-t border-slate-200 border-dashed">
                    <div class="text-[10px] text-slate-400">
                        <p>Printed on: <?php echo date('d M Y, h:i A'); ?></p>
                        <p>Thank you for choosing GM Hospital.</p>
                        <p>This is a computer-generated bill and does not require a signature.</p>
                    </div>
                    <div class="text-center">
                        <div class="w-32 border-t border-slate-800 mx-auto mb-1"></div>
                        <span class="text-[10px] text-slate-500">Authorised Signatory</span>
                    </div>
                </div>
            </div>

            <!-- Modal Footer Action -->
            <div class="p-4 border-t flex justify-end gap-3" style="background-color: #f3efe6; border-color: #1f6b4a20;">
                <button id="btn-pay-modal" onclick="alert('Proceed to payment...')" class="px-8 py-2 text-white font-bold rounded shadow transition-all flex items-center gap-2" style="background: #1f6b4a;">
                    <i class="fas fa-hand-holding-dollar"></i>
                    Collect Due Payment
                </button>
            </div>
        </div>
    </div>

    <!-- IPD BILL DETAILS MODAL (Itemized View) -->
    <div id="ipd-bill-details-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-5xl max-h-[90vh] rounded-3xl shadow-2xl overflow-hidden flex flex-col animate-modal">

            <!-- Modal Header -->
            <div class="p-6 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-black text-slate-900" id="ipd-modal-bill-id">IPD-BILL-ID</h3>
                    <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-0.5">IPD Continuous Billing Ledger</p>
                </div>
                <button onclick="toggleIPDBillModal()" class="h-10 w-10 rounded-full hover:bg-slate-200 transition-all flex items-center justify-center text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-8">
                <!-- Top Section: Patient & Admission Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <!-- Patient Card -->
                    <div class="rounded-3xl p-6 text-white shadow-xl" style="background: linear-gradient(135deg, #1e293b, #0f172a);">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="h-16 w-16 bg-white/20 rounded-2xl backdrop-blur-md flex items-center justify-center text-3xl">
                                <i class="fas fa-procedures"></i>
                            </div>
                            <div>
                                <h4 class="text-2xl font-black leading-none" id="ipd-detail-patient-name">Patient Name</h4>
                                <p class="text-blue-100 font-medium mt-1 uppercase tracking-widest text-[10px]" id="ipd-detail-patient-id">PID-00000000</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Phone Number</p>
                                <p class="font-bold text-sm" id="ipd-detail-patient-phone">--</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Admission ID</p>
                                <p class="font-bold text-sm" id="ipd-detail-admission-id">--</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Attending Doctor</p>
                                <p class="font-bold text-sm" id="ipd-detail-doctor-name">--</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Stay (Days)</p>
                                <p class="font-bold text-sm" id="ipd-detail-total-days">--</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Financial Stats -->
                    <div class="bg-slate-50 rounded-3xl p-6 border border-slate-200 flex flex-col justify-between">
                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Admission Date</p>
                                    <p class="text-slate-900 font-black" id="ipd-detail-bill-date">--/--/----</p>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Payment Status</p>
                                    <span id="ipd-detail-payment-status" class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-[10px] font-black uppercase tracking-widest inline-block mt-1">Paid</span>
                                </div>
                            </div>
                        </div>
                        <div class="pt-4 mt-4 border-t border-slate-200">
                            <div class="flex justify-between items-end">
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Current Balance Due</p>
                                    <h5 class="text-4xl font-black text-rose-500 leading-none" id="ipd-detail-balance-due">₹0.00</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Middle Section: Detailed Itemized Billing -->
                <div class="mb-8">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-sm font-black uppercase tracking-widest text-slate-400 flex items-center gap-2">
                            <div class="h-4 w-1 rounded-full" style="background: var(--gm-accent);"></div>
                            Itemized Charges Ledger
                        </h4>
                        <button id="btn-add-ipd-charge" class="bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 px-4 py-2 rounded-lg text-xs font-bold transition-all shadow-sm">
                            <i class="fas fa-plus mr-1"></i> Add Manual Charge
                        </button>
                    </div>
                    
                    <div class="border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr class="text-left font-bold text-slate-400 uppercase text-[10px] tracking-widest">
                                    <th class="px-6 py-4">Date</th>
                                    <th class="px-6 py-4">Service / Description</th>
                                    <th class="px-6 py-4 text-center">Qty</th>
                                    <th class="px-6 py-4 text-right">Unit Price (₹)</th>
                                    <th class="px-6 py-4 text-right">Row Total (₹)</th>
                                </tr>
                            </thead>
                            <tbody id="ipd-detail-items-tbody">
                                <!-- Dynamic Itemized List grouped by Category -->
                            </tbody>
                            <tfoot id="ipd-detail-summary-tfoot" class="bg-slate-100 border-t-2 border-slate-200">
                                <tr>
                                    <td colspan="4" class="px-6 py-3 text-right text-xs font-black uppercase tracking-widest text-slate-500">Gross Subtotal</td>
                                    <td class="px-6 py-3 text-right font-bold text-slate-700" id="ipd-foot-subtotal">₹0.00</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="px-6 py-3 text-right text-xs font-black uppercase tracking-widest text-slate-500">Taxes Added</td>
                                    <td class="px-6 py-3 text-right font-bold text-slate-700" id="ipd-foot-tax">₹0.00</td>
                                </tr>
                                <tr class="bg-slate-200/50 border-t border-slate-300">
                                    <td colspan="4" class="px-6 py-5 text-right text-sm font-black uppercase tracking-widest text-slate-900">Grand Total Billed</td>
                                    <td class="px-6 py-5 text-right font-black text-slate-900 text-xl" id="ipd-foot-grand-total">₹0.00</td>
                                </tr>
                                <tr class="border-t border-slate-300 bg-green-50/50">
                                    <td colspan="4" class="px-6 py-3 text-right text-xs font-black uppercase tracking-widest text-green-700">Total Amount Paid</td>
                                    <td class="px-6 py-3 text-right font-black text-green-700" id="ipd-foot-amount-paid">₹0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="p-6 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                <button id="ipd-btn-pay-modal" class="px-10 py-3 text-white font-black rounded-xl shadow-lg transition-all transform hover:-translate-y-1 flex items-center gap-2" style="background: var(--gm-accent);">
                    <i class="fas fa-hand-holding-dollar"></i>
                    Collect Due Payment
                </button>
            </div>
        </div>
    </div>

    <!-- SLIDE-IN RECEIPT DETAILS DRAWER (Instant Preview) -->
    <div id="receipt-drawer-backdrop" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[9999] flex justify-end" onclick="if(event.target===this)closeReceiptDrawer()">
        <div class="w-full max-w-2xl bg-white h-full shadow-2xl overflow-y-auto flex flex-col drawer-slide-in">
            <!-- Drawer Header -->
            <div class="p-5 bg-[#1f6b4a] text-white flex justify-between items-center sticky top-0 z-20 shadow-md">
                <div class="flex items-center gap-3">
                    <span class="p-2 rounded-lg bg-white/20 text-white"><i class="fas fa-receipt"></i></span>
                    <div>
                        <h3 class="font-black text-lg leading-tight" id="drawer-rec-id">ORC-000000</h3>
                        <p class="text-[11px] text-emerald-100 font-semibold uppercase tracking-widest mt-0.5">Payment Receipt Summary</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="drawer-btn-print" class="px-3 py-1.5 bg-white text-[#1f6b4a] font-bold rounded-lg text-xs hover:bg-emerald-50 transition-all flex items-center gap-1.5 shadow-sm">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button type="button" onclick="closeReceiptDrawer()" class="h-8 w-8 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition-all">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Drawer Body -->
            <div class="p-6 flex-1 space-y-6 bg-[#fdfdfc]">
                <!-- Hospital Summary Banner -->
                <div class="p-4 rounded-xl bg-[#f3efe6] border border-[#1f6b4a25] flex justify-between items-start">
                    <div>
                        <h4 class="font-black text-sm text-[#1f6b4a]">GM HOSPITAL (Basaveshwar Nagar)</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5">No. 335, 3rd Stage, 4th Block, Bengaluru 560079</p>
                        <p class="text-[11px] text-slate-500">GST: 29AAFCP8756N3ZE | Ph: 0802221160</p>
                    </div>
                    <div class="text-right">
                        <span id="drawer-rec-status" class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-[10px] font-black uppercase tracking-widest inline-block">Paid</span>
                        <div class="text-xs text-slate-500 font-bold mt-1.5" id="drawer-rec-datetime">--/--/---- --:--</div>
                    </div>
                </div>

                <!-- Patient & Doctor Card -->
                <div class="grid grid-cols-2 gap-3 p-4 rounded-xl border border-slate-200 bg-white">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Patient Details</span>
                        <div class="font-black text-sm text-[#1f6b4a] mt-0.5" id="drawer-rec-patient-name">Patient Name</div>
                        <div class="text-xs text-slate-500 font-semibold" id="drawer-rec-patient-meta">PID: -- | Age: --</div>
                        <div class="text-xs text-slate-500" id="drawer-rec-patient-phone"><i class="fas fa-phone mr-1 text-slate-400"></i> --</div>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Doctor & Department</span>
                        <div class="font-black text-sm text-slate-800 mt-0.5" id="drawer-rec-doctor">Dr. --</div>
                        <div class="text-xs text-slate-500 font-semibold" id="drawer-rec-dept">Department: --</div>
                        <div class="text-xs text-slate-400 mt-1" id="drawer-rec-cashier">Cashier: --</div>
                    </div>
                </div>

                <!-- Itemized Charges -->
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-2">Itemized Services</span>
                    <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                        <table class="w-full text-xs">
                            <thead class="bg-[#1f6b4a] text-white font-bold uppercase">
                                <tr>
                                    <th class="py-2.5 px-3 text-left">Description</th>
                                    <th class="py-2.5 px-3 text-center w-14">Qty</th>
                                    <th class="py-2.5 px-3 text-right w-20">Rate (₹)</th>
                                    <th class="py-2.5 px-3 text-right w-24">Amount (₹)</th>
                                </tr>
                            </thead>
                            <tbody id="drawer-rec-items-tbody">
                                <!-- Injected by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Financial Calculation Matrix -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2 text-xs">
                    <div class="flex justify-between items-center text-slate-600 font-semibold">
                        <span>Subtotal</span>
                        <span id="drawer-rec-subtotal">₹0.00</span>
                    </div>
                    <div class="flex justify-between items-center text-slate-600 font-semibold">
                        <span>Discount (-)</span>
                        <span id="drawer-rec-discount" class="text-amber-600">- ₹0.00</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-y-2 border-[#1f6b4a] font-black text-[#1f6b4a] text-sm">
                        <span>Grand Total</span>
                        <span id="drawer-rec-grand-total">₹0.00</span>
                    </div>
                    <div class="flex justify-between items-center text-emerald-700 font-black pt-1">
                        <span>Amount Paid (<span id="drawer-rec-mode">Cash</span>)</span>
                        <span id="drawer-rec-paid">₹0.00</span>
                    </div>
                    <div class="flex justify-between items-center text-rose-600 font-black pt-1" id="drawer-rec-due-row">
                        <span>Balance Due</span>
                        <span id="drawer-rec-due">₹0.00</span>
                    </div>
                </div>

                <!-- Notes / Audit Info -->
                <div id="drawer-rec-notes-box" class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900 hidden">
                    <div class="font-bold flex items-center gap-1.5 mb-1"><i class="fas fa-sticky-note"></i> Remarks & Audit Trail:</div>
                    <div id="drawer-rec-notes" class="whitespace-pre-line font-medium"></div>
                </div>
            </div>

            <!-- Drawer Footer Actions -->
            <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-wrap justify-between items-center gap-3 sticky bottom-0 z-20">
                <button type="button" id="drawer-btn-cancel-refund" class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5">
                    <i class="fas fa-ban"></i> Cancel / Refund
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" id="drawer-btn-collect-due" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black transition-all flex items-center gap-1.5 shadow hidden">
                        <i class="fas fa-hand-holding-dollar"></i> Collect Due (₹<span id="drawer-due-btn-val">0</span>)
                    </button>
                    <button type="button" onclick="closeReceiptDrawer()" class="px-4 py-2 bg-white border border-slate-300 text-slate-700 hover:bg-slate-100 rounded-xl text-xs font-bold transition-all">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- CANCEL / REFUND MODAL -->
    <div id="rec-cancel-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[99999] flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border-2 border-rose-500 animate-modal">
            <div class="p-4 bg-rose-600 text-white font-black flex justify-between items-center text-sm">
                <span class="flex items-center gap-2"><i class="fas fa-exclamation-triangle"></i> Cancel / Refund Receipt</span>
                <button type="button" onclick="closeRecCancelModal()" class="text-white hover:opacity-80"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-5 space-y-4 text-xs">
                <p class="text-slate-600">
                    You are cancelling/refunding receipt <strong id="cancel-modal-bill-id" class="text-rose-600">--</strong>. This will update the financial ledger and log an audit reason.
                </p>
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Action Type</label>
                    <select id="cancel-modal-action" class="w-full p-2 bg-slate-50 border border-slate-300 rounded-lg outline-none font-bold">
                        <option value="cancel">Cancel Bill (Void Receipt)</option>
                        <option value="refund">Issue Patient Refund</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Refund Amount (₹)</label>
                    <input type="number" id="cancel-modal-refund-amt" step="0.01" class="w-full p-2 bg-slate-50 border border-slate-300 rounded-lg outline-none font-bold text-[#1f6b4a]" value="0">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Mandatory Reason *</label>
                    <textarea id="cancel-modal-reason" rows="2" class="w-full p-2 bg-slate-50 border border-slate-300 rounded-lg outline-none" placeholder="Enter clinical or billing reason..."></textarea>
                </div>
            </div>
            <div class="p-4 bg-slate-50 border-t flex justify-end gap-2 text-xs">
                <button type="button" onclick="closeRecCancelModal()" class="px-4 py-2 bg-white border border-slate-300 rounded-lg font-bold">Close</button>
                <button type="button" id="btn-submit-rec-cancel" onclick="submitRecCancelRefund()" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-black rounded-lg shadow">Confirm Action</button>
            </div>
        </div>
    </div>

    <!-- DOCTOR DATE-WISE FULL STATEMENT MODAL (Clinical Ledger & Patient Breakdown) -->
    <div id="doctor-datewise-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[99999] flex items-center justify-center p-4">
        <div class="w-full max-w-5xl max-h-[92vh] bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col animate-modal border border-slate-200">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-gradient-to-r from-[#1f6b4a] to-[#144d34] text-white flex justify-between items-center shadow-md">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-white/20 text-white flex items-center justify-center text-xl font-bold">
                        <i class="fas fa-user-doctor"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-black text-lg text-white" id="doc-modal-name">Dr. Name</h3>
                            <span class="px-2.5 py-0.5 bg-emerald-300 text-emerald-950 text-[10px] font-black rounded-full uppercase tracking-wider" id="doc-modal-dept-badge">ENT</span>
                        </div>
                        <p class="text-xs text-emerald-100/90 font-medium" id="doc-modal-subtitle">
                            MBBS, MS • Clinical Revenue & Date-wise Patient Statement
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" onclick="printDoctorDatewiseStatement()" class="px-3.5 py-1.5 bg-white text-[#1f6b4a] hover:bg-emerald-50 rounded-xl text-xs font-black transition-all flex items-center gap-1.5 shadow-sm">
                        <i class="fas fa-print"></i> Print Statement
                    </button>
                    <button type="button" onclick="closeDoctorDatewiseModal()" class="h-8 w-8 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition-all">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Doctor Summary Financial Cards -->
            <div class="p-5 bg-gradient-to-r from-slate-50 via-white to-[#f3efe6]/40 border-b border-slate-200 grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
                    <div class="text-[10px] uppercase font-bold text-slate-400">Total Consultations</div>
                    <div class="text-lg font-black text-slate-800 mt-0.5" id="doc-modal-kpi-bills">0</div>
                </div>
                <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
                    <div class="text-[10px] uppercase font-bold text-slate-400">Gross Billed</div>
                    <div class="text-lg font-black text-slate-800 mt-0.5" id="doc-modal-kpi-billed">₹0.00</div>
                </div>
                <div class="bg-emerald-50 p-3 rounded-xl border border-emerald-200 shadow-xs">
                    <div class="text-[10px] uppercase font-bold text-emerald-800">Total Collected Revenue</div>
                    <div class="text-lg font-black text-emerald-700 mt-0.5" id="doc-modal-kpi-collected">₹0.00</div>
                </div>
                <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
                    <div class="text-[10px] uppercase font-bold text-slate-400">Pending Balance Due</div>
                    <div class="text-lg font-black text-rose-600 mt-0.5" id="doc-modal-kpi-due">₹0.00</div>
                </div>
            </div>

            <!-- Date Selection & Printing Toolbar -->
            <div class="px-6 py-3 bg-white border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <i class="fas fa-calendar-check text-[#1f6b4a]"></i> Select Date:
                        </span>
                        <select id="doc-modal-date-select" onchange="filterDoctorStatementByDate(this.value)" class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 outline-none focus:border-[#1f6b4a] shadow-xs">
                            <option value="all">📅 All Available Dates</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-1.5 bg-slate-50 px-2.5 py-1 rounded-xl border border-slate-200">
                        <span class="text-[10px] uppercase font-bold text-slate-400">From</span>
                        <input type="date" id="doc-modal-from-date" class="bg-transparent text-xs font-semibold text-slate-800 outline-none w-28">
                        <span class="text-[10px] uppercase font-bold text-slate-400">To</span>
                        <input type="date" id="doc-modal-to-date" class="bg-transparent text-xs font-semibold text-slate-800 outline-none w-28">
                        <button type="button" onclick="applyDoctorModalCustomDateRange()" class="px-2.5 py-1 bg-[#1f6b4a] hover:bg-[#144d34] text-white rounded-lg text-[11px] font-black transition-all">
                            Filter
                        </button>
                    </div>
                </div>

                <!-- Print Options -->
                <div class="flex items-center gap-2">
                    <button type="button" onclick="printDoctorDatewiseStatement()" class="px-4 py-1.5 bg-[#1f6b4a] hover:bg-[#144d34] text-white rounded-xl text-xs font-black transition-all flex items-center gap-1.5 shadow-sm">
                        <i class="fas fa-print"></i> Print Selected Statement
                    </button>
                </div>
            </div>

            <!-- Date-wise Grouped Patient List Content (Scrollable) -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6 bg-slate-50" id="doc-modal-datewise-body">
                <!-- Injected dynamically by JS -->
            </div>

            <!-- Modal Footer -->
            <div class="p-4 bg-white border-t border-slate-200 flex justify-between items-center text-xs">
                <span class="text-slate-400 font-semibold" id="doc-modal-footer-stats">Reconciled Clinical Statement</span>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="closeDoctorDatewiseModal()" class="px-5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all">
                        Close
                    </button>
                    <button type="button" onclick="printDoctorDatewiseStatement()" class="px-5 py-2 bg-[#1f6b4a] hover:bg-[#144d34] text-white font-black rounded-xl transition-all shadow-sm flex items-center gap-1.5">
                        <i class="fas fa-file-pdf"></i> Download / Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/billing_management.js?v=<?= time() ?>"></script>
</body>
</html>
