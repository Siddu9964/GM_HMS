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
                        <div class="billing-tab" onclick="switchTab('ipd')">
                            <i class="fas fa-bed mr-2"></i> IPD Billing
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
        <div
            class="bg-white w-full max-w-5xl max-h-[90vh] rounded-3xl shadow-2xl overflow-hidden flex flex-col animate-modal">

            <!-- Modal Header -->
            <div class="p-6 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                <div>
                    <h3 id="form-mode-title" class="text-xl font-black text-slate-900 flex items-center gap-2">
                        <i class="fas fa-file-circle-plus" style="color: var(--gm-accent);"></i>
                        New OPD Invoice
                    </h3>
                    <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-0.5">Patient Billing Portal
                    </p>
                </div>
                <button onclick="toggleBillingForm()"
                    class="h-10 w-10 rounded-full hover:bg-slate-200 transition-all flex items-center justify-center text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form id="opd-billing-form" class="flex-1 overflow-y-auto">
                <div class="p-8 space-y-8">

                    <!-- Section: Patient Selection -->
                    <div>
                        <div class="flex items-center gap-2 mb-4">
                            <div class="h-6 w-1 rounded-full" style="background: var(--gm-accent);"></div>
                            <h4 class="text-sm font-black uppercase tracking-widest text-slate-400">Step 1: Patient
                                Selection</h4>
                        </div>
                        <div
                            class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50 p-6 rounded-2xl border border-slate-200">
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-slate-700">Search Patient *</label>
                                <select id="patient-select" name="patient_id" class="w-full" required></select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-slate-700">Consulting Physician</label>
                                <select id="doctor-select" name="doctor_id" class="w-full"></select>
                            </div>

                            <!-- Hidden Patient Info Card -->
                            <div id="patient-info"
                                class="hidden md:col-span-2 mt-4 p-4 bg-white rounded-xl border border-blue-100 flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                        class="h-12 w-12 rounded-full flex items-center justify-center text-white text-xl" style="background: var(--gm-accent);">
                                        <i class="fas fa-user-injured"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-slate-400 uppercase tracking-tighter">Selected
                                            Patient</p>
                                        <h5 class="text-lg font-black text-slate-900 leading-none" id="info-patient-id">
                                            --</h5>
                                    </div>
                                </div>
                                <div class="flex gap-8">
                                    <div class="text-center">
                                        <p class="text-xs font-bold text-slate-400">AGE/SEX</p>
                                        <p class="font-black" id="info-age-sex">--</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-xs font-bold text-slate-400">PHONE</p>
                                        <p class="font-black" id="info-phone">--</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Billing Items -->
                    <div>
                        <div class="flex justify-between items-center mb-4">
                            <div class="flex items-center gap-2">
                                <div class="h-6 w-1 rounded-full" style="background: var(--gm-accent);"></div>
                                <h4 class="text-sm font-black uppercase tracking-widest text-slate-400">Step 2: Pricing
                                    & Services</h4>
                            </div>
                            <button type="button" onclick="addBillingItem()"
                                class="bg-slate-900 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-slate-800 transition-all flex items-center gap-2">
                                <i class="fas fa-plus"></i> Add Line Item
                            </button>
                        </div>

                        <div class="border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr
                                        class="text-left font-bold text-slate-500 uppercase text-[10px] tracking-widest">
                                        <th class="px-4 py-4 w-40">Category</th>
                                        <th class="px-4 py-4">Service/Item Name</th>
                                        <th class="px-4 py-4 w-24 text-center">Qty</th>
                                        <th class="px-4 py-4 w-32 text-right">Unit Rate</th>
                                        <th class="px-4 py-4 w-32 text-right">Amout (₹)</th>
                                        <th class="px-4 py-4 w-12"></th>
                                    </tr>
                                </thead>
                                <tbody id="billing-items-tbody">
                                    <!-- Dynamic Items -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Section: Checkout & Summary -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                        <!-- Left: Payment Method -->
                        <div class="space-y-6">
                            <div class="flex items-center gap-2">
                                <div class="h-6 w-1 rounded-full" style="background: var(--gm-accent);"></div>
                                <h4 class="text-sm font-black uppercase tracking-widest text-slate-400">Step 3: Payment
                                </h4>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="text-sm font-bold text-slate-700">Method</label>
                                    <select name="payment_method"
                                        class="w-full p-2.5 bg-white border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20">
                                        <option value="Cash">Cash Payment</option>
                                        <option value="Card">Credit/Debit Card</option>
                                        <option value="UPI">UPI / QR Code</option>
                                        <option value="Net Banking">Net Banking</option>
                                        <option value="Cheque">Bank Cheque</option>
                                    </select>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-bold text-slate-700">Immediate Payment</label>
                                    <input type="number" name="amount_paid" id="amount-paid" step="0.01"
                                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-bold text-blue-600 outline-none focus:ring-2 focus:ring-blue-500/20 placeholder:text-slate-300">
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-slate-700">Internal Remarks</label>
                                <textarea name="notes" rows="3"
                                    class="w-full p-3 bg-white border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 text-sm"
                                    placeholder="Any special instructions or reference numbers..."></textarea>
                            </div>
                        </div>

                        <!-- Right: Totals -->
                        <div class="bg-slate-50 p-8 rounded-3xl border border-slate-200 space-y-4">
                            <div
                                class="flex justify-between items-center text-slate-500 font-bold text-sm tracking-tight">
                                <span>Subtotal</span>
                                <span id="summary-subtotal">₹0.00</span>
                            </div>
                            <div
                                class="flex justify-between items-center text-slate-500 font-bold text-sm tracking-tight">
                                <span>Adjustment/Discount (-)</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-rose-500 font-black">-</span>
                                    <input type="number" id="discount-amount" name="discount_amount"
                                        onchange="calculateTotals()" placeholder="0.00"
                                        class="w-24 text-right bg-transparent border-b border-rose-200 text-rose-600 font-black outline-none focus:border-rose-500">
                                </div>
                            </div>
                            <div
                                class="flex justify-between items-center text-slate-400 font-bold text-xs uppercase tracking-widest pt-4 border-t border-slate-200">
                                <span>Taxable Base</span>
                                <span id="summary-taxable">₹0.00</span>
                            </div>
                            <div class="flex justify-between items-center pt-4 mt-4 border-t-2 border-slate-900">
                                <span class="text-lg font-black text-slate-900 uppercase">Grand Total</span>
                                <span class="text-3xl font-black text-slate-900" id="summary-grand-total">₹0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="p-6 bg-slate-50 border-t border-slate-200 flex justify-end gap-3 sticky bottom-0">
                    <button type="button" onclick="toggleBillingForm()"
                        class="px-8 py-3 text-slate-500 font-bold hover:text-slate-900 transition-all rounded-xl">
                        Cancel
                    </button>
                    <button type="submit" id="btn-submit-bill"
                        class="px-10 py-3 text-white font-black rounded-xl shadow-lg transition-all transform hover:-translate-y-1 active:scale-95 flex items-center gap-2" style="background: var(--gm-accent);">
                        <i class="fas fa-check-double"></i>
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
    <div id="bill-details-modal"
        class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4">
        <div
            class="bg-white w-full max-w-4xl max-h-[90vh] rounded-3xl shadow-2xl overflow-hidden flex flex-col animate-modal">

            <!-- Modal Header -->
            <div class="p-6 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-black text-slate-900" id="modal-bill-id">BILL-ID</h3>
                    <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-0.5">Invoice & Patient
                        Detail Card</p>
                </div>
                <button onclick="toggleBillModal()"
                    class="h-10 w-10 rounded-full hover:bg-slate-200 transition-all flex items-center justify-center text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-8">
                <!-- Top Section: Patient & Doctor Card -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <!-- Patient & Appointment Card -->
                    <div class="rounded-3xl p-6 text-white shadow-xl" style="background: var(--gm-accent);">
                        <div class="flex items-center gap-4 mb-6">
                            <div
                                class="h-16 w-16 bg-white/20 rounded-2xl backdrop-blur-md flex items-center justify-center text-3xl">
                                <i class="fas fa-user-injured"></i>
                            </div>
                            <div>
                                <h4 class="text-2xl font-black leading-none" id="detail-patient-name">Patient Name</h4>
                                <p class="text-blue-100 font-medium mt-1 uppercase tracking-widest text-[10px]"
                                    id="detail-patient-id">PID-00000000</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                            <div>
                                <p class="text-[10px] font-bold text-blue-100 uppercase tracking-widest">Phone Number
                                </p>
                                <p class="font-bold text-sm" id="detail-patient-phone">9876543210</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-blue-100 uppercase tracking-widest">Appointment ID
                                </p>
                                <p class="font-bold text-sm" id="detail-appointment-id">--</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-blue-100 uppercase tracking-widest">Consulting
                                    Doctor</p>
                                <p class="font-bold text-sm" id="detail-doctor-name">Dr. Name</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-blue-100 uppercase tracking-widest">Billing Time
                                </p>
                                <p class="font-bold text-sm" id="detail-bill-time">00:00:00</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Financial Stats -->
                    <div class="bg-slate-50 rounded-3xl p-6 border border-slate-200 flex flex-col justify-between">
                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Invoice
                                        Date</p>
                                    <p class="text-slate-900 font-black" id="detail-bill-date">--/--/----</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Payment
                                        Mode</p>
                                    <p class="text-slate-900 font-bold" id="detail-payment-mode">Cash</p>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status</p>
                                    <span id="detail-payment-status"
                                        class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-[10px] font-black uppercase tracking-widest inline-block mt-1">Paid</span>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Purpose
                                    </p>
                                    <p class="text-slate-900 font-bold text-sm truncate" id="detail-bill-purpose"
                                        title="Purpose">OPD Service</p>
                                </div>
                            </div>
                        </div>
                        <div class="pt-4 mt-4 border-t border-slate-200">
                            <div class="flex justify-between items-end">
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                                        Current Balance Due</p>
                                    <h5 class="text-4xl font-black text-rose-500 leading-none" id="detail-balance-due">
                                        ₹0.00</h5>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Generated
                                        By</p>
                                    <p class="text-slate-900 font-bold" id="detail-created-by">System Admin</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Middle Section: Billing Items -->
                <div class="mb-8">
                    <h4
                        class="text-sm font-black uppercase tracking-widest text-slate-400 mb-4 flex items-center gap-2">
                        <div class="h-4 w-1 rounded-full" style="background: var(--gm-accent);"></div>
                        Invoice Line Items
                    </h4>
                    <div class="border border-slate-100 rounded-2xl overflow-hidden shadow-sm overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr class="text-left font-bold text-slate-400 uppercase text-[10px] tracking-widest">
                                    <th class="px-6 py-4">Service / Description</th>
                                    <th class="px-6 py-4 text-center">Qty</th>
                                    <th class="px-6 py-4 text-right">Unit Rate</th>
                                    <th class="px-6 py-4 text-right">Row Total (₹)</th>
                                </tr>
                            </thead>
                            <tbody id="detail-items-tbody">
                                <!-- Dynamic Items -->
                            </tbody>
                            <tfoot id="detail-summary-tfoot" class="bg-slate-50/50">
                                <tr>
                                    <td colspan="3"
                                        class="px-6 py-2 text-right text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        Gross Subtotal</td>
                                    <td class="px-6 py-2 text-right font-bold text-slate-700" id="foot-subtotal">₹0.00
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3"
                                        class="px-6 py-2 text-right text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        Discount (<span id="foot-discount-percent">0</span>%)</td>
                                    <td class="px-6 py-2 text-right font-bold text-rose-600" id="foot-discount">- ₹0.00
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3"
                                        class="px-6 py-2 text-right text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        Taxable Amount</td>
                                    <td class="px-6 py-2 text-right font-bold text-slate-700" id="foot-taxable">₹0.00
                                    </td>
                                </tr>
                                <tr class="bg-slate-100/50">
                                    <td colspan="3"
                                        class="px-6 py-4 text-right text-xs font-black uppercase tracking-widest text-slate-900">
                                        Grand Total Invoice</td>
                                    <td class="px-6 py-4 text-right font-black text-slate-900 text-lg"
                                        id="foot-grand-total">₹0.00</td>
                                </tr>
                                <tr class="border-t border-slate-200">
                                    <td colspan="3"
                                        class="px-6 py-2 text-right text-[10px] font-black uppercase tracking-widest text-green-600">
                                        Total Amount Paid</td>
                                    <td class="px-6 py-2 text-right font-black text-green-600" id="foot-amount-paid">
                                        ₹0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Bottom Section: Remarks -->
                <div id="detail-notes-container" class="hidden">
                    <h4
                        class="text-sm font-black uppercase tracking-widest text-slate-400 mb-2 flex items-center gap-2">
                        <div class="h-4 w-1 bg-amber-500 rounded-full"></div>
                        Internal Notes / Remarks
                    </h4>
                    <div class="p-4 bg-amber-50 border border-amber-100 rounded-xl text-slate-600 text-sm whitespace-pre-line"
                        id="detail-notes">
                        No additional notes for this invoice.
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="p-6 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                <button id="btn-print-modal" onclick="printBill('')"
                    class="px-8 py-3 bg-white border border-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-100 transition-all flex items-center gap-2">
                    <i class="fas fa-print"></i> Print Invoice
                </button>
                <button id="btn-pay-modal" onclick="alert('Proceed to payment...')"
                    class="px-10 py-3 text-white font-black rounded-xl shadow-lg transition-all transform hover:-translate-y-1 flex items-center gap-2" style="background: var(--gm-accent);">
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
