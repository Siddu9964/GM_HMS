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
            color: #64748b;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .billing-tab.active {
            color: #2563eb;
        }

        .billing-tab.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: #2563eb;
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
        .premium-table thead th {
            background: #f8fafc;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            color: #64748b;
            padding: 1rem 1.5rem;
        }

        .premium-table tbody td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
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

                    <!-- Main Table Area -->
                    <div class="p-6">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                            <h3 class="text-lg font-bold text-slate-900">Recent Transactions</h3>
                            <div class="flex items-center gap-3 w-full md:w-auto">
                                <div class="relative flex-1 md:w-64">
                                    <i
                                        class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    <input type="text" id="search-bills" placeholder="Search invoices..."
                                        class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all">
                                </div>
                                <select id="filter-status" onchange="loadBills()"
                                    class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20">
                                    <option value="">All Status</option>
                                    <option value="Paid">Paid</option>
                                    <option value="Partial">Partial</option>
                                    <option value="Pending">Pending</option>
                                </select>
                            </div>
                        </div>

                        <div class="overflow-x-auto border border-slate-100 rounded-xl">
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
                                        <th class="text-right tracking-widest">•••</th>
                                    </tr>
                                </thead>
                                <tbody id="bills-tbody" class="bg-white divide-y divide-slate-100 text-sm font-medium">
                                    <tr>
                                        <td colspan="9" class="px-6 py-12 text-center">
                                            <div class="animate-pulse flex flex-col items-center gap-4">
                                                <div class="h-10 w-10 bg-slate-100 rounded-full"></div>
                                                <div class="h-4 w-48 bg-slate-100 rounded"></div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
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

    </main>
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

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="assets/js/billing_management.js"></script>
</body>

</html>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="assets/js/billing_management.js?v=<?= time() ?>"></script>
</body>

</html>
