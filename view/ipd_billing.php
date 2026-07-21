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
    <title>IPD Billing & Payments - GM HMS</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Common Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin_common.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <!-- Toastify -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        
        .billing-item-row {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #1f6b4a;
        }
        
        .billing-summary {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            position: sticky;
            top: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .summary-row.total {
            font-size: 1.25rem;
            font-weight: 700;
            border-bottom: none;
            padding-top: 15px;
            margin-top: 10px;
            border-top: 2px solid rgba(255,255,255,0.3);
        }
        
        .charge-type-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .charge-type-Room { background: #dbeafe; color: #1e40af; }
        .charge-type-Procedure { background: #fef3c7; color: #92400e; }
        .charge-type-Medication { background: #d1fae5; color: #065f46; }
        .charge-type-Investigation { background: #e0e7ff; color: #3730a3; }
        .charge-type-Nursing { background: #fce7f3; color: #9f1239; }
        .charge-type-Consumable { background: #f3e8ff; color: #6b21a8; }
        .charge-type-Other { background: #f1f5f9; color: #475569; }
        
        /* Select2 Styling */
        .select2-container--default .select2-selection--single {
            height: 42px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
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
            
            <main class="flex-1 overflow-y-auto p-4 md:p-8">
                
                <!-- Page Header -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                    <div>
                        <h1 class="text-3xl font-black tracking-tight text-slate-900 flex items-center gap-3">
                            <span class="p-2 rounded-lg" style="background: var(--gm-accent);">
                                <i class="fas fa-file-invoice-dollar text-white"></i>
                            </span>
                            IPD Billing & Payments
                        </h1>
                        <p class="text-slate-500 mt-1 font-medium">Comprehensive in-patient billing management</p>
                    </div>
                    <button onclick="showNewBillModal()" class="text-white px-6 py-3 rounded-xl shadow-lg transition-all transform hover:-translate-y-1 active:scale-95 flex items-center gap-2 font-bold" style="background: var(--gm-accent);">
                        <i class="fas fa-plus"></i>
                        New IPD Bill
                    </button>
                </div>
                
                <!-- Search by Admission -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 mb-8">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <label class="text-sm font-bold text-slate-700 mb-2 block">
                                <i class="fas fa-search"></i> Search by Admission ID or Patient
                            </label>
                            <select class="w-full" id="admissionSearch"></select>
                        </div>
                        <div class="flex items-end gap-2">
                            <button onclick="loadAdmissionBills()" class="flex-1 text-white px-6 py-3 rounded-xl font-bold transition-all" style="background: var(--gm-accent);">
                                <i class="fas fa-search"></i> Load Bills
                            </button>
                            <button onclick="clearSearch()" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-6 py-3 rounded-xl font-bold transition-all">
                                <i class="fas fa-times"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Billing Summary -->
                <div id="billingSummaryCard" class="hidden mb-8">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bento-card">
                            <div class="bento-title">Total Charges</div>
                            <h3 class="bento-value" id="totalCharges">₹0</h3>
                            <i class="fas fa-rupee-sign bento-icon"></i>
                        </div>
                        <div class="bento-card">
                            <div class="bento-title">Total Paid</div>
                            <h3 class="bento-value" id="totalPaid">₹0</h3>
                            <i class="fas fa-money-bill-wave bento-icon"></i>
                        </div>
                        <div class="bento-card">
                            <div class="bento-title">Balance Due</div>
                            <h3 class="bento-value" id="balanceDue">₹0</h3>
                            <i class="fas fa-exclamation-circle bento-icon"></i>
                        </div>
                        <div class="bento-card">
                            <div class="bento-title">Total Bills</div>
                            <h3 class="bento-value" id="billCount">0</h3>
                            <i class="fas fa-file-invoice bento-icon"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Bills Table -->
                <div class="table-container">
                    <div class="p-6 border-b border-slate-100">
                        <h5 class="text-lg font-bold text-slate-900"><i class="fas fa-list"></i> Bills</h5>
                    </div>
                    <div class="p-6">
                        <table id="billsTable" class="w-full">
                            <thead>
                                <tr>
                                    <th>Bill ID</th>
                                    <th>Date</th>
                                    <th>Patient</th>
                                    <th>Admission ID</th>
                                    <th>Grand Total</th>
                                    <th>Paid</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- New Bill Modal -->
    <div id="newBillModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-7xl max-h-[95vh] rounded-3xl shadow-2xl overflow-hidden flex flex-col">
            <div class="p-6 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-black text-slate-900 flex items-center gap-2">
                        <i class="fas fa-file-invoice-dollar text-blue-600"></i>
                        Create New IPD Bill
                    </h3>
                    <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-0.5">In-Patient Billing Portal</p>
                </div>
                <button onclick="closeNewBillModal()" class="h-10 w-10 rounded-full hover:bg-slate-200 transition-all flex items-center justify-center text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-8">
                <form id="newBillForm">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Left Column - Bill Details -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- Admission Selection -->
                            <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200">
                                <h6 class="text-sm font-black uppercase tracking-widest text-slate-400 mb-4">
                                    <i class="fas fa-hospital-user"></i> Admission Details
                                </h6>
                                <div class="grid grid-cols-3 gap-4">
                                    <div class="col-span-2">
                                        <label class="text-sm font-bold text-slate-700 mb-2 block">Admission *</label>
                                        <select class="w-full" id="billAdmissionSelect" name="admission_id" required></select>
                                    </div>
                                    <div>
                                        <label class="text-sm font-bold text-slate-700 mb-2 block">Total Days</label>
                                        <input type="number" class="w-full p-2.5 bg-white border border-slate-200 rounded-xl" id="billTotalDays" name="total_days" readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Billing Items -->
                            <div>
                                <div class="flex justify-between items-center mb-4">
                                    <h6 class="text-sm font-black uppercase tracking-widest text-slate-400">
                                        <i class="fas fa-list-ul"></i> Billing Items
                                    </h6>
                                    <button type="button" onclick="addBillingItem()" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-slate-800 transition-all">
                                        <i class="fas fa-plus"></i> Add Item
                                    </button>
                                </div>
                                <div id="billingItemsContainer" class="space-y-3"></div>
                            </div>
                        </div>
                        
                        <!-- Right Column - Summary -->
                        <div>
                            <div class="billing-summary">
                                <h6 class="mb-3 font-bold"><i class="fas fa-calculator"></i> Bill Summary</h6>
                                
                                <div class="summary-row"><span>Room Charges:</span><span id="summaryRoom">₹0.00</span></div>
                                <div class="summary-row"><span>Procedures:</span><span id="summaryProcedure">₹0.00</span></div>
                                <div class="summary-row"><span>Medications:</span><span id="summaryMedication">₹0.00</span></div>
                                <div class="summary-row"><span>Investigations:</span><span id="summaryInvestigation">₹0.00</span></div>
                                <div class="summary-row"><span>Nursing:</span><span id="summaryNursing">₹0.00</span></div>
                                <div class="summary-row"><span>Consumables:</span><span id="summaryConsumable">₹0.00</span></div>
                                <div class="summary-row"><span>Other:</span><span id="summaryOther">₹0.00</span></div>
                                
                                <div class="summary-row mt-3" style="font-weight: 600;">
                                    <span>Subtotal:</span>
                                    <span id="summarySubtotal">₹0.00</span>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-2 mt-3">
                                    <div>
                                        <label class="text-xs mb-1 block">Discount %</label>
                                        <input type="number" class="w-full p-2 bg-white/10 border border-white/20 rounded-lg text-white" id="discountPercentage" name="discount_percentage" min="0" max="100" step="0.01" value="0" onchange="calculateBillSummary()">
                                    </div>
                                    <div>
                                        <label class="text-xs mb-1 block">Tax %</label>
                                        <input type="number" class="w-full p-2 bg-white/10 border border-white/20 rounded-lg text-white" id="taxPercentage" name="tax_percentage" min="0" max="100" step="0.01" value="18" onchange="calculateBillSummary()">
                                    </div>
                                </div>
                                
                                <div class="summary-row"><span>Discount:</span><span id="summaryDiscount">₹0.00</span></div>
                                <div class="summary-row"><span>Taxable Amount:</span><span id="summaryTaxable">₹0.00</span></div>
                                <div class="summary-row"><span>Tax:</span><span id="summaryTax">₹0.00</span></div>
                                
                                <div class="summary-row total">
                                    <span>GRAND TOTAL:</span>
                                    <span id="summaryGrandTotal">₹0.00</span>
                                </div>
                                
                                <div class="mt-3">
                                    <label class="text-xs mb-1 block">Amount Paid</label>
                                    <input type="number" class="w-full p-2 bg-white/10 border border-white/20 rounded-lg text-white font-bold" id="amountPaid" name="amount_paid" min="0" step="0.01" value="0" onchange="calculateBillSummary()">
                                </div>
                                
                                <div class="summary-row mt-2">
                                    <span>Balance Due:</span>
                                    <span id="summaryBalance" class="text-yellow-300">₹0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <label class="text-sm font-bold text-slate-700 mb-2 block">Notes</label>
                        <textarea class="w-full p-3 bg-white border border-slate-200 rounded-xl" name="notes" rows="2" placeholder="Additional notes..."></textarea>
                    </div>
                </form>
            </div>
            
            <div class="p-6 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                <button type="button" onclick="closeNewBillModal()" class="px-8 py-3 text-slate-500 font-bold hover:text-slate-900 transition-all rounded-xl">
                    Cancel
                </button>
                <button type="button" onclick="saveBill()" class="px-10 py-3 text-white font-black rounded-xl shadow-lg transition-all" style="background: var(--gm-accent);">
                    <i class="fas fa-save"></i> Create Bill
                </button>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    
    <script>
        // API Base URL
        const API_BASE = '../reception_view/ipd_management/api';
        
        let billsTable;
        let currentAdmissionId = null;
        let itemCounter = 0;
        
        $(document).ready(function() {
            // Initialize DataTable
            billsTable = $('#billsTable').DataTable({
                order: [[1, 'desc']],
                columnDefs: [{ targets: 8, orderable: false }]
            });
            
            // Initialize admission search
            initAdmissionSearch('#admissionSearch');
            initAdmissionSearch('#billAdmissionSelect', onAdmissionSelected);
            
            // Add first billing item
            addBillingItem();
        });
        
        function initAdmissionSearch(selector, onSelect) {
            $(selector).select2({
                ajax: {
                    url: `${API_BASE}/admissions`,
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return { search: params.term, status: 'Admitted' };
                    },
                    processResults: function(data) {
                        return {
                            results: data.data.map(a => ({
                                id: a.admission_id,
                                text: `${a.admission_id} - ${a.patient_name} (Bed: ${a.bed_number})`,
                                patient_id: a.patient_id,
                                doctor_id: a.admitting_doctor_id,
                                admission_date: a.admission_date
                            }))
                        };
                    }
                },
                placeholder: 'Search by admission ID or patient name',
                minimumInputLength: 0,
                allowClear: true
            }).on('select2:select', function(e) {
                if (onSelect) onSelect(e.params.data);
            });
        }
        
        function onAdmissionSelected(admission) {
            if (admission) {
                const days = calculateDays(admission.admission_date);
                $('#billTotalDays').val(days);
            }
        }
        
        function calculateDays(admissionDate) {
            const start = new Date(admissionDate);
            const today = new Date();
            const diffTime = Math.abs(today - start);
            return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        }
        
        function showNewBillModal() {
            $('#newBillForm')[0].reset();
            $('#billingItemsContainer').empty();
            itemCounter = 0;
            addBillingItem();
            calculateBillSummary();
            $('#newBillModal').removeClass('hidden');
        }
        
        function closeNewBillModal() {
            $('#newBillModal').addClass('hidden');
        }
        
        function addBillingItem() {
            itemCounter++;
            const html = `
                <div class="billing-item-row grid grid-cols-12 gap-2" id="item-${itemCounter}">
                    <div class="col-span-2">
                        <input type="date" class="w-full p-2 border border-slate-200 rounded-lg text-sm item-date" value="${new Date().toISOString().split('T')[0]}" required>
                    </div>
                    <div class="col-span-2">
                        <select class="w-full p-2 border border-slate-200 rounded-lg text-sm item-type" required onchange="calculateBillSummary()">
                            <option value="Room">Room</option>
                            <option value="Procedure">Procedure</option>
                            <option value="Medication">Medication</option>
                            <option value="Investigation">Investigation</option>
                            <option value="Nursing">Nursing</option>
                            <option value="Consumable">Consumable</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-span-3">
                        <input type="text" class="w-full p-2 border border-slate-200 rounded-lg text-sm item-name" placeholder="Item name" required>
                    </div>
                    <div class="col-span-1">
                        <input type="number" class="w-full p-2 border border-slate-200 rounded-lg text-sm item-quantity" value="1" min="0.01" step="0.01" onchange="calculateItemTotal(${itemCounter})" required>
                    </div>
                    <div class="col-span-2">
                        <input type="number" class="w-full p-2 border border-slate-200 rounded-lg text-sm item-price" min="0" step="0.01" placeholder="Price" onchange="calculateItemTotal(${itemCounter})" required>
                    </div>
                    <div class="col-span-2 flex gap-1">
                        <input type="number" class="flex-1 p-2 border border-slate-200 rounded-lg text-sm item-total font-bold" readonly>
                        <button type="button" onclick="removeItem(${itemCounter})" class="px-3 bg-rose-500 text-white rounded-lg hover:bg-rose-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            $('#billingItemsContainer').append(html);
        }
        
        function removeItem(itemId) {
            $(`#item-${itemId}`).remove();
            calculateBillSummary();
        }
        
        function calculateItemTotal(itemId) {
            const item = $(`#item-${itemId}`);
            const qty = parseFloat(item.find('.item-quantity').val()) || 0;
            const price = parseFloat(item.find('.item-price').val()) || 0;
            item.find('.item-total').val((qty * price).toFixed(2));
            calculateBillSummary();
        }
        
        function calculateBillSummary() {
            const totals = { Room: 0, Procedure: 0, Medication: 0, Investigation: 0, Nursing: 0, Consumable: 0, Other: 0 };
            
            $('.billing-item-row').each(function() {
                const type = $(this).find('.item-type').val();
                const total = parseFloat($(this).find('.item-total').val()) || 0;
                totals[type] += total;
            });
            
            const subtotal = Object.values(totals).reduce((a, b) => a + b, 0);
            const discountPercentage = parseFloat($('#discountPercentage').val()) || 0;
            const discountAmount = (subtotal * discountPercentage) / 100;
            const taxableAmount = subtotal - discountAmount;
            const taxPercentage = parseFloat($('#taxPercentage').val()) || 0;
            const taxAmount = (taxableAmount * taxPercentage) / 100;
            const grandTotal = taxableAmount + taxAmount;
            const amountPaid = parseFloat($('#amountPaid').val()) || 0;
            const balance = grandTotal - amountPaid;
            
            $('#summaryRoom').text('₹' + totals.Room.toFixed(2));
            $('#summaryProcedure').text('₹' + totals.Procedure.toFixed(2));
            $('#summaryMedication').text('₹' + totals.Medication.toFixed(2));
            $('#summaryInvestigation').text('₹' + totals.Investigation.toFixed(2));
            $('#summaryNursing').text('₹' + totals.Nursing.toFixed(2));
            $('#summaryConsumable').text('₹' + totals.Consumable.toFixed(2));
            $('#summaryOther').text('₹' + totals.Other.toFixed(2));
            $('#summarySubtotal').text('₹' + subtotal.toFixed(2));
            $('#summaryDiscount').text('₹' + discountAmount.toFixed(2));
            $('#summaryTaxable').text('₹' + taxableAmount.toFixed(2));
            $('#summaryTax').text('₹' + taxAmount.toFixed(2));
            $('#summaryGrandTotal').text('₹' + grandTotal.toFixed(2));
            $('#summaryBalance').text('₹' + balance.toFixed(2));
        }
        
        function saveBill() {
            const admissionSelect = $('#billAdmissionSelect').val();
            if (!admissionSelect) {
                showToast('Please select an admission', 'error');
                return;
            }
            
            const items = [];
            let isValid = true;
            
            $('.billing-item-row').each(function() {
                const name = $(this).find('.item-name').val();
                const price = parseFloat($(this).find('.item-price').val());
                
                if (!name || !price) {
                    isValid = false;
                    return false;
                }
                
                items.push({
                    charge_date: $(this).find('.item-date').val(),
                    charge_type: $(this).find('.item-type').val(),
                    item_name: name,
                    quantity: parseFloat($(this).find('.item-quantity').val()),
                    unit_price: price,
                    total_price: parseFloat($(this).find('.item-total').val())
                });
            });
            
            if (!isValid || items.length === 0) {
                showToast('Please fill all required fields', 'error');
                return;
            }
            
            const admissionData = $('#billAdmissionSelect').select2('data')[0];
            
            const billData = {
                admission_id: admissionSelect,
                patient_id: admissionData.patient_id,
                doctor_id: admissionData.doctor_id,
                admission_date: admissionData.admission_date,
                total_days: parseInt($('#billTotalDays').val()) || 0,
                discount_percentage: parseFloat($('#discountPercentage').val()) || 0,
                tax_percentage: parseFloat($('#taxPercentage').val()) || 18,
                amount_paid: parseFloat($('#amountPaid').val()) || 0,
                notes: $('textarea[name="notes"]').val(),
                items: items
            };
            
            $.ajax({
                url: `${API_BASE}/billing`,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(billData),
                success: function(response) {
                    showToast('Bill created successfully!', 'success');
                    closeNewBillModal();
                    if (currentAdmissionId === admissionSelect) {
                        loadAdmissionBills();
                    }
                },
                error: function() {
                    showToast('Failed to create bill', 'error');
                }
            });
        }
        
        function loadAdmissionBills() {
            const admissionId = $('#admissionSearch').val();
            if (!admissionId) {
                showToast('Please select an admission', 'error');
                return;
            }
            
            currentAdmissionId = admissionId;
            
            $.get(`${API_BASE}/billing?admission_id=${admissionId}`, function(response) {
                billsTable.clear();
                
                if (response.data && response.data.length > 0) {
                    response.data.forEach(bill => {
                        billsTable.row.add([
                            bill.bill_id,
                            new Date(bill.created_at).toLocaleDateString(),
                            bill.patient_name,
                            bill.admission_id,
                            '₹' + parseFloat(bill.grand_total).toFixed(2),
                            '₹' + parseFloat(bill.amount_paid).toFixed(2),
                            '₹' + parseFloat(bill.balance_due).toFixed(2),
                            getPaymentStatusBadge(bill.payment_status),
                            `<button class="px-3 py-1 text-white rounded-lg text-xs" style="background: var(--gm-accent);" onclick="viewBill('${bill.bill_id}')">View</button>`
                        ]);
                    });
                }
                
                billsTable.draw();
                
                if (response.summary) {
                    $('#totalCharges').text('₹' + parseFloat(response.summary.total_charges || 0).toFixed(2));
                    $('#totalPaid').text('₹' + parseFloat(response.summary.total_paid || 0).toFixed(2));
                    $('#balanceDue').text('₹' + parseFloat(response.summary.balance_due || 0).toFixed(2));
                    $('#billCount').text(response.data.length);
                    $('#billingSummaryCard').removeClass('hidden');
                }
            });
        }
        
        function clearSearch() {
            $('#admissionSearch').val(null).trigger('change');
            currentAdmissionId = null;
            billsTable.clear().draw();
            $('#billingSummaryCard').addClass('hidden');
        }
        
        function getPaymentStatusBadge(status) {
            const badges = {
                'Paid': '<span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">Paid</span>',
                'Partial': '<span class="px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-bold">Partial</span>',
                'Pending': '<span class="px-3 py-1 bg-rose-100 text-rose-700 rounded-full text-xs font-bold">Pending</span>'
            };
            return badges[status] || status;
        }
        
        function viewBill(billId) {
            window.location.href = `bill_details.php?bill_id=${billId}`;
        }
        
        function showToast(message, type) {
            Toastify({
                text: message,
                duration: 3000,
                gravity: "top",
                position: "right",
                style: {
                    background: type === 'success' ? "#10b981" : "#ef4444"
                }
            }).showToast();
        }
    </script>
</body>
</html>
