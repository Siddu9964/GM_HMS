<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Receptionist', 'admin', 'Admin'])) {
    header("Location: ../receptionist_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OPD Billing - GM HMS</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Styles -->
    <link rel="stylesheet" href="assets/css/reception_dashboard.css">
    <link rel="stylesheet" href="assets/css/billing_advanced.css">
    
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body>
    
    <div class="reception-layout">
        <!-- Sidebar -->
        <?php include 'includes/reception_sidebar.php'; ?>
        
        <div class="reception-main-content">
            <!-- Navbar -->
            <?php 
            $pageTitle = 'OPD Billing';
            include 'includes/reception_navbar.php'; 
            ?>
            
            <main class="reception-content">
                
                <!-- Page Header -->
                <div class="billing-header">
                    <div>
                        <h1 class="page-title">
                            <i class="fas fa-file-invoice-dollar"></i>
                            OPD Billing
                        </h1>
                        <p class="page-subtitle">Create and manage outpatient bills</p>
                    </div>
                    <button class="btn btn-primary" onclick="toggleBillingForm()">
                        <i class="fas fa-plus"></i> New Bill
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card primary">
                        <div class="stat-icon">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="stat-today-revenue">₹0.00</h3>
                            <p>Today's Revenue</p>
                        </div>
                    </div>
                    
                    <div class="stat-card success">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="stat-month-revenue">₹0.00</h3>
                            <p>Monthly Revenue</p>
                        </div>
                    </div>
                    
                    <div class="stat-card warning">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="stat-pending-bills">0</h3>
                            <p>Pending Bills</p>
                        </div>
                    </div>
                    
                    <div class="stat-card danger">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="stat-outstanding">₹0.00</h3>
                            <p>Outstanding Amount</p>
                        </div>
                    </div>
                </div>

                <!-- Billing Form (Hidden by default) -->
                <div id="billing-form-container" class="billing-form-container hidden">
                    <div class="billing-form-card">
                        <div class="form-header">
                            <h3><i class="fas fa-file-invoice"></i> Create New Bill</h3>
                            <button class="btn-close" onclick="toggleBillingForm()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <form id="opd-billing-form">
                            <!-- Patient Selection -->
                            <div class="form-section">
                                <h4>Patient Information</h4>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Select Patient *</label>
                                        <select id="patient-select" name="patient_id" class="form-control" required>
                                            <option value="">Search patient by name or ID...</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Consulting Doctor</label>
                                        <select id="doctor-select" name="doctor_id" class="form-control">
                                            <option value="">Select doctor (optional)...</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div id="patient-info" class="patient-info-card hidden">
                                    <div class="info-row">
                                        <span class="info-label">Patient ID:</span>
                                        <span id="info-patient-id">-</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Age/Sex:</span>
                                        <span id="info-age-sex">-</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Phone:</span>
                                        <span id="info-phone">-</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Billing Items -->
                            <div class="form-section">
                                <div class="section-header">
                                    <h4>Billing Items</h4>
                                    <button type="button" class="btn btn-sm btn-success" onclick="addBillingItem()">
                                        <i class="fas fa-plus"></i> Add Item
                                    </button>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="billing-items-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 20%;">Type</th>
                                                <th style="width: 30%;">Service/Item</th>
                                                <th style="width: 10%;">Qty</th>
                                                <th style="width: 15%;">Rate (₹)</th>
                                                <th style="width: 15%;">Amount (₹)</th>
                                                <th style="width: 10%;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="billing-items-tbody">
                                            <!-- Items will be added here dynamically -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Bill Summary -->
                            <div class="form-section">
                                <h4>Bill Summary</h4>
                                <div class="bill-summary">
                                    <div class="summary-row">
                                        <span>Subtotal:</span>
                                        <span id="summary-subtotal">₹0.00</span>
                                    </div>
                                    <div class="summary-row">
                                        <span>Discount:</span>
                                        <div class="discount-input-group">
                                            <input type="number" id="discount-amount" name="discount_amount" 
                                                   placeholder="0.00" step="0.01" min="0" 
                                                   onchange="calculateTotals()">
                                            <span>₹</span>
                                        </div>
                                    </div>
                                    <div class="summary-row">
                                        <span>Taxable Amount:</span>
                                        <span id="summary-taxable">₹0.00</span>
                                    </div>
                                    <div class="summary-row">
                                        <span>GST (18%):</span>
                                        <span id="summary-tax">₹0.00</span>
                                    </div>
                                    <div class="summary-row total">
                                        <span>Grand Total:</span>
                                        <span id="summary-grand-total">₹0.00</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Details -->
                            <div class="form-section">
                                <h4>Payment Details</h4>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Payment Method *</label>
                                        <select name="payment_method" class="form-control" required>
                                            <option value="Cash">Cash</option>
                                            <option value="Card">Card</option>
                                            <option value="UPI">UPI</option>
                                            <option value="Net Banking">Net Banking</option>
                                            <option value="Cheque">Cheque</option>
                                            <option value="Insurance">Insurance</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Amount Paid *</label>
                                        <input type="number" name="amount_paid" id="amount-paid" 
                                               class="form-control" step="0.01" min="0" required>
                                    </div>
                                    
                                    <div class="form-group full-width">
                                        <label>Notes</label>
                                        <textarea name="notes" class="form-control" rows="2" 
                                                  placeholder="Additional notes..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary" onclick="toggleBillingForm()">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Generate Bill
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Bills Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3>Recent Bills</h3>
                        <div class="table-actions">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="search-bills" placeholder="Search bills...">
                            </div>
                            <select id="filter-status" class="form-control" onchange="loadBills()">
                                <option value="">All Status</option>
                                <option value="Paid">Paid</option>
                                <option value="Partial">Partial</option>
                                <option value="Pending">Pending</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Bill ID</th>
                                    <th>Receipt No</th>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Paid</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="bills-tbody">
                                <tr>
                                    <td colspan="9" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="assets/js/reception_utils.js"></script>
    <script src="assets/js/opd_billing_advanced.js"></script>
</body>
</html>
