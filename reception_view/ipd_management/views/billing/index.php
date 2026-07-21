<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Receptionist', 'admin', 'Admin'])) {
    header("Location: ../../../../receptionist_login.php");
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
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <!-- Toastify -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../public/assets/css/ipd_main.css">
    
    <style>
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
    </style>
</head>
<body>
    <div class="container-fluid p-4">
            <!-- Header -->
            <div class="dashboard-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1><i class="fas fa-file-invoice-dollar"></i> IPD Billing & Payments</h1>
                        <p>Manage patient billing, charges, and payments</p>
                    </div>
                    <button class="btn btn-light" onclick="showNewBillModal()">
                        <i class="fas fa-plus"></i> New Bill
                    </button>
                </div>
            </div>
            
            <!-- Search by Admission -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-search"></i> Search by Admission ID or Patient</label>
                            <select class="form-select" id="admissionSearch"></select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button class="btn btn-primary" onclick="loadAdmissionBills()">
                                <i class="fas fa-search"></i> Load Bills
                            </button>
                            <button class="btn btn-secondary ms-2" onclick="clearSearch()">
                                <i class="fas fa-times"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Billing Summary (shown when admission is selected) -->
            <div id="billingSummaryCard" class="card mb-4" style="display: none;">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-chart-line"></i> Billing Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-icon success">
                                    <i class="fas fa-rupee-sign"></i>
                                </div>
                                <div class="stat-info">
                                    <h3 id="totalCharges">₹0</h3>
                                    <p>Total Charges</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-icon primary">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div class="stat-info">
                                    <h3 id="totalPaid">₹0</h3>
                                    <p>Total Paid</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-icon danger">
                                    <i class="fas fa-exclamation-circle"></i>
                                </div>
                                <div class="stat-info">
                                    <h3 id="balanceDue">₹0</h3>
                                    <p>Balance Due</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-icon info">
                                    <i class="fas fa-file-invoice"></i>
                                </div>
                                <div class="stat-info">
                                    <h3 id="billCount">0</h3>
                                    <p>Total Bills</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bills Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-list"></i> Bills</h5>
                </div>
                <div class="card-body">
                    <table id="billsTable" class="table table-striped table-hover">
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
        </div>
    </div>
    
    <!-- New Bill Modal -->
    <div class="modal fade" id="newBillModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-invoice-dollar"></i> Create New Bill</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="newBillForm">
                        <div class="row">
                            <!-- Left Column - Bill Details -->
                            <div class="col-md-8">
                                <!-- Admission Selection -->
                                <div class="mb-4">
                                    <h6 class="text-primary mb-3"><i class="fas fa-hospital-user"></i> Admission Details</h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Admission *</label>
                                            <select class="form-select" id="billAdmissionSelect" name="admission_id" required></select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Admission Date</label>
                                            <input type="date" class="form-control" id="billAdmissionDate" readonly>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Total Days</label>
                                            <input type="number" class="form-control" id="billTotalDays" name="total_days" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Billing Items -->
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="text-primary mb-0"><i class="fas fa-list-ul"></i> Billing Items</h6>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="addBillingItem()">
                                            <i class="fas fa-plus"></i> Add Item
                                        </button>
                                    </div>
                                    <div id="billingItemsContainer"></div>
                                </div>
                            </div>
                            
                            <!-- Right Column - Summary -->
                            <div class="col-md-4">
                                <div class="billing-summary">
                                    <h6 class="mb-3"><i class="fas fa-calculator"></i> Bill Summary</h6>
                                    
                                    <div class="summary-row">
                                        <span>Room Charges:</span>
                                        <span id="summaryRoom">₹0.00</span>
                                    </div>
                                    <div class="summary-row">
                                        <span>Procedures:</span>
                                        <span id="summaryProcedure">₹0.00</span>
                                    </div>
                                    <div class="summary-row">
                                        <span>Medications:</span>
                                        <span id="summaryMedication">₹0.00</span>
                                    </div>
                                    <div class="summary-row">
                                        <span>Investigations:</span>
                                        <span id="summaryInvestigation">₹0.00</span>
                                    </div>
                                    <div class="summary-row">
                                        <span>Nursing:</span>
                                        <span id="summaryNursing">₹0.00</span>
                                    </div>
                                    <div class="summary-row">
                                        <span>Consumables:</span>
                                        <span id="summaryConsumable">₹0.00</span>
                                    </div>
                                    <div class="summary-row">
                                        <span>Other:</span>
                                        <span id="summaryOther">₹0.00</span>
                                    </div>
                                    
                                    <div class="summary-row mt-3" style="font-weight: 600;">
                                        <span>Subtotal:</span>
                                        <span id="summarySubtotal">₹0.00</span>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-6">
                                            <label class="form-label" style="font-size: 0.875rem;">Discount %</label>
                                            <input type="number" class="form-control form-control-sm" id="discountPercentage" 
                                                   name="discount_percentage" min="0" max="100" step="0.01" value="0" onchange="calculateBillSummary()">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label" style="font-size: 0.875rem;">Tax %</label>
                                            <input type="number" class="form-control form-control-sm" id="taxPercentage" 
                                                   name="tax_percentage" min="0" max="100" step="0.01" value="18" onchange="calculateBillSummary()">
                                        </div>
                                    </div>
                                    
                                    <div class="summary-row">
                                        <span>Discount:</span>
                                        <span id="summaryDiscount">₹0.00</span>
                                    </div>
                                    <div class="summary-row">
                                        <span>Taxable Amount:</span>
                                        <span id="summaryTaxable">₹0.00</span>
                                    </div>
                                    <div class="summary-row">
                                        <span>Tax:</span>
                                        <span id="summaryTax">₹0.00</span>
                                    </div>
                                    
                                    <div class="summary-row total">
                                        <span>GRAND TOTAL:</span>
                                        <span id="summaryGrandTotal">₹0.00</span>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <label class="form-label" style="font-size: 0.875rem;">Amount Paid</label>
                                        <input type="number" class="form-control" id="amountPaid" name="amount_paid" 
                                               min="0" step="0.01" value="0" onchange="calculateBillSummary()">
                                    </div>
                                    
                                    <div class="summary-row mt-2">
                                        <span>Balance Due:</span>
                                        <span id="summaryBalance" class="text-warning">₹0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="2" placeholder="Additional notes..."></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveBill()">
                        <i class="fas fa-save"></i> Create Bill
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- View Bill Modal -->
    <div class="modal fade" id="viewBillModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-invoice"></i> Bill Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="billDetailsContent">
                    <!-- Content loaded dynamically -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="printBill()">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Bootstrap 5 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Toastify -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    
    <!-- IPD Main -->
    <script src="../../public/assets/js/ipd_main.js"></script>
    
    <script>
        let billsTable;
        let currentAdmissionId = null;
        let itemCounter = 0;
        
        $(document).ready(function() {
            // Initialize DataTable
            billsTable = $('#billsTable').DataTable({
                order: [[1, 'desc']],
                columnDefs: [
                    { targets: 8, orderable: false }
                ]
            });
            
            // Initialize admission search
            IPD.initAdmissionSearch('#admissionSearch');
            IPD.initAdmissionSearch('#billAdmissionSelect', '#newBillModal', onAdmissionSelected);
            
            // Add first billing item
            addBillingItem();
        });
        
        function onAdmissionSelected(admission) {
            if (admission) {
                $('#billAdmissionDate').val(admission.admission_date);
                const days = calculateDays(admission.admission_date);
                $('#billTotalDays').val(days);
            }
        }
        
        function calculateDays(admissionDate) {
            const start = new Date(admissionDate);
            const today = new Date();
            const diffTime = Math.abs(today - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            return diffDays;
        }
        
        function loadAdmissionBills() {
            const admissionId = $('#admissionSearch').val();
            if (!admissionId) {
                IPD.toast('Please select an admission', 'warning');
                return;
            }
            
            currentAdmissionId = admissionId;
            
            IPD.ajax(`billing?admission_id=${admissionId}`, 'GET')
                .then(response => {
                    billsTable.clear();
                    
                    if (response.data && response.data.length > 0) {
                        response.data.forEach(bill => {
                            billsTable.row.add([
                                bill.bill_id,
                                IPD.formatDate(bill.created_at),
                                bill.patient_name,
                                bill.admission_id,
                                IPD.formatCurrency(bill.grand_total),
                                IPD.formatCurrency(bill.amount_paid),
                                IPD.formatCurrency(bill.balance_due),
                                getPaymentStatusBadge(bill.payment_status),
                                getActionButtons(bill.bill_id)
                            ]);
                        });
                    }
                    
                    billsTable.draw();
                    
                    // Update summary
                    if (response.summary) {
                        $('#totalCharges').text(IPD.formatCurrency(response.summary.total_charges || 0));
                        $('#totalPaid').text(IPD.formatCurrency(response.summary.total_paid || 0));
                        $('#balanceDue').text(IPD.formatCurrency(response.summary.balance_due || 0));
                        $('#billCount').text(response.data.length);
                        $('#billingSummaryCard').show();
                    }
                })
                .catch(error => {
                    IPD.toast(error.message || 'Failed to load bills', 'error');
                });
        }
        
        function clearSearch() {
            $('#admissionSearch').val(null).trigger('change');
            currentAdmissionId = null;
            billsTable.clear().draw();
            $('#billingSummaryCard').hide();
        }
        
        function getPaymentStatusBadge(status) {
            const badges = {
                'Paid': '<span class="badge bg-success">Paid</span>',
                'Partial': '<span class="badge bg-warning">Partial</span>',
                'Pending': '<span class="badge bg-danger">Pending</span>',
                'Cancelled': '<span class="badge bg-secondary">Cancelled</span>'
            };
            return badges[status] || status;
        }
        
        function getActionButtons(billId) {
            return `
                <button class="btn btn-sm btn-info" onclick="viewBill('${billId}')" title="View">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-sm btn-success" onclick="addPayment('${billId}')" title="Add Payment">
                    <i class="fas fa-money-bill-wave"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteBill('${billId}')" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            `;
        }
        
        function showNewBillModal() {
            $('#newBillForm')[0].reset();
            $('#billingItemsContainer').empty();
            itemCounter = 0;
            addBillingItem();
            calculateBillSummary();
            
            const modal = new bootstrap.Modal(document.getElementById('newBillModal'));
            modal.show();
        }
        
        function addBillingItem() {
            itemCounter++;
            const itemHtml = `
                <div class="billing-item-row" id="item-${itemCounter}">
                    <div class="row">
                        <div class="col-md-2 mb-2">
                            <label class="form-label" style="font-size: 0.875rem;">Date</label>
                            <input type="date" class="form-control form-control-sm item-date" value="${new Date().toISOString().split('T')[0]}" required>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label" style="font-size: 0.875rem;">Type *</label>
                            <select class="form-select form-select-sm item-type" required onchange="calculateBillSummary()">
                                <option value="Room">Room</option>
                                <option value="Procedure">Procedure</option>
                                <option value="Medication">Medication</option>
                                <option value="Investigation">Investigation</option>
                                <option value="Nursing">Nursing</option>
                                <option value="Consumable">Consumable</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label" style="font-size: 0.875rem;">Item Name *</label>
                            <input type="text" class="form-control form-control-sm item-name" placeholder="Item description" required>
                        </div>
                        <div class="col-md-1 mb-2">
                            <label class="form-label" style="font-size: 0.875rem;">Qty</label>
                            <input type="number" class="form-control form-control-sm item-quantity" value="1" min="0.01" step="0.01" onchange="calculateItemTotal(${itemCounter})" required>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label" style="font-size: 0.875rem;">Unit Price *</label>
                            <input type="number" class="form-control form-control-sm item-price" min="0" step="0.01" onchange="calculateItemTotal(${itemCounter})" required>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label" style="font-size: 0.875rem;">Total</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control item-total" readonly>
                                <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(${itemCounter})">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('#billingItemsContainer').append(itemHtml);
        }
        
        function removeItem(itemId) {
            $(`#item-${itemId}`).remove();
            calculateBillSummary();
        }
        
        function calculateItemTotal(itemId) {
            const item = $(`#item-${itemId}`);
            const qty = parseFloat(item.find('.item-quantity').val()) || 0;
            const price = parseFloat(item.find('.item-price').val()) || 0;
            const total = qty * price;
            
            item.find('.item-total').val(total.toFixed(2));
            calculateBillSummary();
        }
        
        function calculateBillSummary() {
            const totals = {
                Room: 0,
                Procedure: 0,
                Medication: 0,
                Investigation: 0,
                Nursing: 0,
                Consumable: 0,
                Other: 0
            };
            
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
            
            $('#summaryRoom').text(IPD.formatCurrency(totals.Room));
            $('#summaryProcedure').text(IPD.formatCurrency(totals.Procedure));
            $('#summaryMedication').text(IPD.formatCurrency(totals.Medication));
            $('#summaryInvestigation').text(IPD.formatCurrency(totals.Investigation));
            $('#summaryNursing').text(IPD.formatCurrency(totals.Nursing));
            $('#summaryConsumable').text(IPD.formatCurrency(totals.Consumable));
            $('#summaryOther').text(IPD.formatCurrency(totals.Other));
            $('#summarySubtotal').text(IPD.formatCurrency(subtotal));
            $('#summaryDiscount').text(IPD.formatCurrency(discountAmount));
            $('#summaryTaxable').text(IPD.formatCurrency(taxableAmount));
            $('#summaryTax').text(IPD.formatCurrency(taxAmount));
            $('#summaryGrandTotal').text(IPD.formatCurrency(grandTotal));
            $('#summaryBalance').text(IPD.formatCurrency(balance));
        }
        
        function saveBill() {
            const admissionSelect = $('#billAdmissionSelect').val();
            if (!admissionSelect) {
                IPD.toast('Please select an admission', 'warning');
                return;
            }
            
            const items = [];
            let isValid = true;
            
            $('.billing-item-row').each(function() {
                const date = $(this).find('.item-date').val();
                const type = $(this).find('.item-type').val();
                const name = $(this).find('.item-name').val();
                const qty = parseFloat($(this).find('.item-quantity').val());
                const price = parseFloat($(this).find('.item-price').val());
                const total = parseFloat($(this).find('.item-total').val());
                
                if (!name || !price) {
                    isValid = false;
                    return false;
                }
                
                items.push({
                    charge_date: date,
                    charge_type: type,
                    item_name: name,
                    quantity: qty,
                    unit_price: price,
                    total_price: total,
                    is_taxable: 1,
                    tax_percentage: parseFloat($('#taxPercentage').val()) || 18,
                    discount_amount: 0
                });
            });
            
            if (!isValid) {
                IPD.toast('Please fill all required fields', 'warning');
                return;
            }
            
            if (items.length === 0) {
                IPD.toast('Please add at least one billing item', 'warning');
                return;
            }
            
            // Get admission details
            const admissionData = $('#billAdmissionSelect').select2('data')[0];
            
            const billData = {
                admission_id: admissionSelect,
                patient_id: admissionData.patient_id,
                doctor_id: admissionData.doctor_id,
                admission_date: $('#billAdmissionDate').val(),
                total_days: parseInt($('#billTotalDays').val()) || 0,
                discount_percentage: parseFloat($('#discountPercentage').val()) || 0,
                tax_percentage: parseFloat($('#taxPercentage').val()) || 18,
                amount_paid: parseFloat($('#amountPaid').val()) || 0,
                notes: $('textarea[name="notes"]').val(),
                items: items
            };
            
            IPD.ajax('billing', 'POST', billData)
                .then(response => {
                    IPD.toast('Bill created successfully!', 'success');
                    $('#newBillModal').modal('hide');
                    if (currentAdmissionId === admissionSelect) {
                        loadAdmissionBills();
                    }
                })
                .catch(error => {
                    IPD.toast(error.message || 'Failed to create bill', 'error');
                });
        }
        
        function viewBill(billId) {
            IPD.ajax(`billing?id=${billId}`, 'GET')
                .then(response => {
                    const bill = response.data;
                    let itemsHtml = '';
                    
                    if (bill.items && bill.items.length > 0) {
                        bill.items.forEach(item => {
                            itemsHtml += `
                                <tr>
                                    <td>${IPD.formatDate(item.charge_date)}</td>
                                    <td><span class="charge-type-badge charge-type-${item.charge_type}">${item.charge_type}</span></td>
                                    <td>${item.item_name}</td>
                                    <td>${item.quantity}</td>
                                    <td>${IPD.formatCurrency(item.unit_price)}</td>
                                    <td>${IPD.formatCurrency(item.total_price)}</td>
                                </tr>
                            `;
                        });
                    }
                    
                    const content = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Patient Information</h6>
                                <p><strong>Name:</strong> ${bill.patient_name}</p>
                                <p><strong>Phone:</strong> ${bill.patient_phone}</p>
                                <p><strong>Admission ID:</strong> ${bill.admission_id}</p>
                            </div>
                            <div class="col-md-6 text-end">
                                <h6>Bill Information</h6>
                                <p><strong>Bill ID:</strong> ${bill.bill_id}</p>
                                <p><strong>Date:</strong> ${IPD.formatDateTime(bill.created_at)}</p>
                                <p><strong>Status:</strong> ${getPaymentStatusBadge(bill.payment_status)}</p>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h6>Billing Items</h6>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Item</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${itemsHtml}
                            </tbody>
                        </table>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-md-6 offset-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <td>Subtotal:</td>
                                        <td class="text-end">${IPD.formatCurrency(bill.subtotal)}</td>
                                    </tr>
                                    <tr>
                                        <td>Discount (${bill.discount_percentage}%):</td>
                                        <td class="text-end">- ${IPD.formatCurrency(bill.discount_amount)}</td>
                                    </tr>
                                    <tr>
                                        <td>Tax (${bill.tax_percentage}%):</td>
                                        <td class="text-end">${IPD.formatCurrency(bill.tax_amount)}</td>
                                    </tr>
                                    <tr class="fw-bold">
                                        <td>Grand Total:</td>
                                        <td class="text-end">${IPD.formatCurrency(bill.grand_total)}</td>
                                    </tr>
                                    <tr class="text-success">
                                        <td>Amount Paid:</td>
                                        <td class="text-end">${IPD.formatCurrency(bill.amount_paid)}</td>
                                    </tr>
                                    <tr class="text-danger fw-bold">
                                        <td>Balance Due:</td>
                                        <td class="text-end">${IPD.formatCurrency(bill.balance_due)}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    `;
                    
                    $('#billDetailsContent').html(content);
                    const modal = new bootstrap.Modal(document.getElementById('viewBillModal'));
                    modal.show();
                })
                .catch(error => {
                    IPD.toast(error.message || 'Failed to load bill details', 'error');
                });
        }
        
        function addPayment(billId) {
            const amount = prompt('Enter payment amount:');
            if (amount && parseFloat(amount) > 0) {
                IPD.ajax('billing/payment', 'POST', {
                    bill_id: billId,
                    amount: parseFloat(amount)
                })
                .then(response => {
                    IPD.toast('Payment added successfully!', 'success');
                    loadAdmissionBills();
                })
                .catch(error => {
                    IPD.toast(error.message || 'Failed to add payment', 'error');
                });
            }
        }
        
        function deleteBill(billId) {
            IPD.confirm('Are you sure you want to delete this bill?', () => {
                IPD.ajax(`billing?id=${billId}`, 'DELETE')
                    .then(response => {
                        IPD.toast('Bill deleted successfully!', 'success');
                        loadAdmissionBills();
                    })
                    .catch(error => {
                        IPD.toast(error.message || 'Failed to delete bill', 'error');
                    });
            });
        }
        
        function printBill() {
            window.print();
        }
    </script>
</body>
</html>
