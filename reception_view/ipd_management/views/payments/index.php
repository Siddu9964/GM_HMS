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
    <title>Payments & Charges - GM HMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    
    <!-- Reception Dashboard CSS -->
    <link rel="stylesheet" href="../../../assets/css/reception_dashboard.css">
    
    <!-- Custom IPD CSS -->
    <link rel="stylesheet" href="../../public/assets/css/ipd_main.css">
</head>
<body>
    <div class="reception-layout">
        <!-- Sidebar -->
        <?php include '../../../includes/reception_sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="reception-main-content">
            <!-- Top Navbar -->
            <?php 
            $pageTitle = 'IPD Billing & Payments';
            include '../../../includes/reception_navbar.php'; 
            ?>
            
            <!-- Dashboard Content -->
            <div class="reception-content">
                <!-- Page Header -->
                <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;">
                            <i class="fas fa-rupee-sign"></i> Payments & Charges
                        </h1>
                        <p style="color: var(--gray-600);">Manage charges and record payments for IPD admissions</p>
                    </div>
                    <a href="/GM_HMS/reception_view/ipd_management/public/index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
        
        <!-- Select Admission -->
        <div class="table-container mb-4">
            <h3>Select Admission</h3>
            <select class="form-select" id="admissionSelect">
                <option value="">Select an admission...</option>
            </select>
        </div>
        
        <!-- Financial Summary -->
        <div id="financialSummary" style="display:none;">
            <div class="stats-grid mb-4">
                <div class="stat-card warning">
                    <div class="stat-card-header">
                        <span class="stat-card-title">Total Charges</span>
                        <div class="stat-card-icon warning"><i class="fas fa-file-invoice"></i></div>
                    </div>
                    <div class="stat-card-value" id="totalCharges">₹0</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-card-header">
                        <span class="stat-card-title">Total Paid</span>
                        <div class="stat-card-icon success"><i class="fas fa-check-circle"></i></div>
                    </div>
                    <div class="stat-card-value" id="totalPaid">₹0</div>
                </div>
                <div class="stat-card danger">
                    <div class="stat-card-header">
                        <span class="stat-card-title">Balance Due</span>
                        <div class="stat-card-icon danger"><i class="fas fa-exclamation-circle"></i></div>
                    </div>
                    <div class="stat-card-value" id="balanceDue">₹0</div>
                </div>
            </div>
            
            <!-- Charges Section -->
            <div class="table-container mb-4">
                <div class="table-header">
                    <h3>Charges</h3>
                    <button class="btn btn-warning" onclick="showAddChargeModal()"><i class="fas fa-plus"></i> Add Charge</button>
                </div>
                <table class="table" id="chargesTable">
                    <thead>
                        <tr><th>Type</th><th>Description</th><th>Qty</th><th>Unit Price</th><th>Total</th><th>Date</th><th>Actions</th></tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            
            <!-- Payments Section -->
            <div class="table-container">
                <div class="table-header">
                    <h3>Payments</h3>
                    <button class="btn btn-success" onclick="showAddPaymentModal()"><i class="fas fa-plus"></i> Record Payment</button>
                </div>
                <table class="table" id="paymentsTable">
                    <thead>
                        <tr><th>Receipt No</th><th>Amount</th><th>Mode</th><th>Date</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
        </div>
            </div>
            <!-- End Reception Content -->
        </div>
        <!-- End Reception Main Content -->
    </div>
    <!-- End Reception Layout -->
    
    <!-- Add Charge Modal -->
    <div class="modal fade" id="addChargeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Charge</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addChargeForm">
                        <input type="hidden" name="admission_id" id="chargeAdmissionId">
                        <div class="mb-3">
                            <label class="form-label">Charge Type *</label>
                            <select class="form-select" name="charge_type" required>
                                <option value="Room">Room</option>
                                <option value="Consultation">Consultation</option>
                                <option value="Procedure">Procedure</option>
                                <option value="Medicine">Medicine</option>
                                <option value="Laboratory">Laboratory</option>
                                <option value="Radiology">Radiology</option>
                                <option value="Nursing">Nursing</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <input type="text" class="form-control" name="charge_description" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Quantity *</label>
                                <input type="number" class="form-control" name="quantity" value="1" step="0.01" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Unit Price *</label>
                                <input type="number" class="form-control" name="unit_price" step="0.01" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date *</label>
                            <input type="date" class="form-control" name="charge_date" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" onclick="saveCharge()">Save Charge</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Payment Modal -->
    <div class="modal fade" id="addPaymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addPaymentForm">
                        <input type="hidden" name="admission_id" id="paymentAdmissionId">
                        <div class="mb-3">
                            <label class="form-label">Amount *</label>
                            <input type="number" class="form-control" name="amount" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Mode *</label>
                            <select class="form-select" name="payment_mode" required>
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="UPI">UPI</option>
                                <option value="Net Banking">Net Banking</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Insurance">Insurance</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Transaction Reference</label>
                            <input type="text" class="form-control" name="transaction_reference">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Date *</label>
                            <input type="datetime-local" class="form-control" name="payment_date" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="savePayment()">Record Payment</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="../../public/assets/js/ipd_main.js"></script>
    
    <script>
        let currentAdmissionId = null;
        
        $(document).ready(function() {
            loadActiveAdmissions();
            
            $('#admissionSelect').change(function() {
                currentAdmissionId = $(this).val();
                if (currentAdmissionId) {
                    loadFinancialData();
                    $('#financialSummary').show();
                } else {
                    $('#financialSummary').hide();
                }
            });
        });
        
        function loadActiveAdmissions() {
            return IPD.ajax('api/admissions?status=Active', 'GET')
                .then(response => {
                    const select = $('#admissionSelect');
                    select.empty().append('<option value="">Select an admission...</option>');
                    response.data.admissions.forEach(adm => {
                        select.append(`<option value="${adm.admission_id}">
                            ${adm.patient_first_name} ${adm.patient_last_name} - Bed ${adm.bed_number}
                        </option>`);
                    });

                    // Check for admission_id in URL
                    const urlParams = new URLSearchParams(window.location.search);
                    const admId = urlParams.get('admission_id');
                    if (admId) {
                        select.val(admId).trigger('change');
                    }
                });
        }
        
        function loadFinancialData() {
            // Load charges
            IPD.ajax(`api/charges?admission_id=${currentAdmissionId}`, 'GET')
                .then(response => {
                    const tbody = $('#chargesTable tbody');
                    tbody.empty();
                    $('#totalCharges').text(IPD.formatCurrency(response.data.total_charges));
                    
                    response.data.charges.forEach(charge => {
                        tbody.append(`<tr>
                            <td>${charge.charge_type}</td>
                            <td>${charge.charge_description}</td>
                            <td>${charge.quantity}</td>
                            <td>${IPD.formatCurrency(charge.unit_price)}</td>
                            <td>${IPD.formatCurrency(charge.total_amount)}</td>
                            <td>${IPD.formatDate(charge.charge_date)}</td>
                            <td><button class="btn btn-sm btn-danger" onclick="deleteCharge(${charge.charge_id})"><i class="fas fa-trash"></i></button></td>
                        </tr>`);
                    });
                });
            
            // Load payments
            IPD.ajax(`api/payments?admission_id=${currentAdmissionId}`, 'GET')
                .then(response => {
                    const tbody = $('#paymentsTable tbody');
                    tbody.empty();
                    $('#totalPaid').text(IPD.formatCurrency(response.data.total_paid));
                    
                    response.data.payments.forEach(payment => {
                        tbody.append(`<tr>
                            <td>${payment.receipt_number}</td>
                            <td>${IPD.formatCurrency(payment.amount)}</td>
                            <td>${payment.payment_mode}</td>
                            <td>${IPD.formatDateTime(payment.payment_date)}</td>
                            <td><span class="badge bg-success">${payment.payment_status}</span></td>
                            <td><button class="btn btn-sm btn-danger" onclick="deletePayment(${payment.payment_id})"><i class="fas fa-trash"></i></button></td>
                        </tr>`);
                    });
                    
                    // Calculate balance
                    IPD.ajax(`api/admissions?id=${currentAdmissionId}`, 'GET')
                        .then(resp => {
                            const balance = resp.data.financials.balance_due;
                            $('#balanceDue').text(IPD.formatCurrency(balance));
                        });
                });
        }
        
        function showAddChargeModal() {
            $('#chargeAdmissionId').val(currentAdmissionId);
            $('#addChargeForm')[0].reset();
            $('#addChargeModal').modal('show');
        }
        
        function showAddPaymentModal() {
            $('#paymentAdmissionId').val(currentAdmissionId);
            $('#addPaymentForm')[0].reset();
            $('#addPaymentModal').modal('show');
        }
        
        function saveCharge() {
            const formData = {};
            $('#addChargeForm').serializeArray().forEach(field => {
                formData[field.name] = field.value;
            });
            
            IPD.ajax('api/charges', 'POST', formData)
                .then(() => {
                    IPD.toast('Charge added successfully', 'success');
                    $('#addChargeModal').modal('hide');
                    loadFinancialData();
                })
                .catch(error => IPD.toast(error.message, 'error'));
        }
        
        function savePayment() {
            const formData = {};
            $('#addPaymentForm').serializeArray().forEach(field => {
                formData[field.name] = field.value;
            });
            
            IPD.ajax('api/payments', 'POST', formData)
                .then(response => {
                    IPD.toast(`Payment recorded! Receipt: ${response.data.receipt_number}`, 'success');
                    $('#addPaymentModal').modal('hide');
                    loadFinancialData();
                })
                .catch(error => IPD.toast(error.message, 'error'));
        }
        
        function deleteCharge(id) {
            IPD.confirm('Delete this charge?', () => {
                IPD.ajax('api/charges?id=' + id, 'DELETE')
                    .then(() => {
                        IPD.toast('Charge deleted', 'success');
                        loadFinancialData();
                    });
            });
        }
        
        function deletePayment(id) {
            IPD.confirm('Delete this payment?', () => {
                IPD.ajax('api/payments?id=' + id, 'DELETE')
                    .then(() => {
                        IPD.toast('Payment deleted', 'success');
                        loadFinancialData();
                    });
            });
        }
    </script>
</body>
</html>
