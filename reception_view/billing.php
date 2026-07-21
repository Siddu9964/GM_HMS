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
    <title>Billing & Invoices - GM HMS</title>
    <!-- Base Styles -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/reception_dashboard.css">
    <!-- Module Styles -->
    <link rel="stylesheet" href="assets/css/billing.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
    </style>
</head>
<body>
    
    <div class="reception-layout">
        <!-- Sidebar -->
        <?php include 'includes/reception_sidebar.php'; ?>
        
        <div class="reception-main-content">
            <!-- Navbar -->
            <?php 
            $pageTitle = 'Billing & Invoices';
            include 'includes/reception_navbar.php'; 
            ?>
            
            <main class="reception-content">
                
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Billing Center</h1>
                        <p class="text-gray-500">Manage invoices and hospital revenue</p>
                    </div>
                    <button class="btn btn-primary" onclick="toggleNewInvoice()">
                        <i class="fas fa-plus"></i> New Invoice
                    </button>
                </div>

                <!-- Stats Overview -->
                <div class="billing-stats mb-4">
                    <div class="bill-stat-card revenue">
                        <div class="bill-stat-icon"><i class="fas fa-rupee-sign"></i></div>
                        <div>
                            <h3 id="stat-today">0.00</h3>
                            <p class="text-gray-500 text-sm">Today's Revenue</p>
                        </div>
                    </div>
                    <div class="bill-stat-card total">
                        <div class="bill-stat-icon"><i class="fas fa-chart-pie"></i></div>
                        <div>
                            <h3 id="stat-month">0.00</h3>
                            <p class="text-gray-500 text-sm">Monthly Revenue</p>
                        </div>
                    </div>
                    <div class="bill-stat-card pending">
                        <div class="bill-stat-icon"><i class="fas fa-clock"></i></div>
                        <div>
                            <h3 id="stat-pending">0</h3>
                            <p class="text-gray-500 text-sm">Pending Bills</p>
                        </div>
                    </div>
                </div>

                <!-- New Invoice Section (Hidden by default) -->
                <div id="new-invoice-section" class="invoice-form-section hidden">
                    <h4 class="font-bold mb-3">Create New Invoice</h4>
                    <form id="create-invoice-form">
                        <div class="row">
                            <div class="col-md-3 form-group">
                                <label>Search Patient</label>
                                <select name="patient_id" class="form-control" id="patient-search-select" required style="width: 100%;">
                                    <option value="">Select Patient</option>
                                </select>
                            </div>
                            <div class="col-md-3 form-group">
                                <label>Consulting Doctor</label>
                                <select name="doctor_id" class="form-control" id="doctor-search-select" style="width: 100%;">
                                    <option value="">Select Doctor (Optional)</option>
                                </select>
                            </div>
                            <div class="col-md-2 form-group">
                                <label>Service / Title</label>
                                <input type="text" name="title" class="form-control" placeholder="e.g. X-Ray" required>
                            </div>
                            <div class="col-md-2 form-group">
                                <label>Amount (₹)</label>
                                <input type="number" name="amount" class="form-control" placeholder="0.00" required>
                            </div>
                            <div class="col-md-2 form-group">
                                <label>Payment Mode</label>
                                <select name="payment_method" class="form-control">
                                    <option value="Cash">Cash</option>
                                    <option value="Card">Card</option>
                                    <option value="UPI">UPI</option>
                                </select>
                            </div>
                            <div class="col-md-2 form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="Paid">Paid</option>
                                    <option value="Pending">Pending</option>
                                </select>
                            </div>
                        </div>
                        <div class="text-right mt-3">
                            <button type="button" class="btn btn-outline" onclick="toggleNewInvoice()">Cancel</button>
                            <button type="submit" class="btn btn-success">Generate Invoice</button>
                        </div>
                    </form>
                </div>

                <!-- Invoice Table -->
                <div class="invoice-table-container">
                    <div class="table-header">
                        <h3 class="font-bold text-lg">Transaction History</h3>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="invoice-search" placeholder="Search invoices...">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table w-100">
                            <thead>
                                <tr class="bg-light text-gray-500 text-xs">
                                    <th class="p-3">Invoice ID</th>
                                    <th class="p-3">Patient</th>
                                    <th class="p-3">Service</th>
                                    <th class="p-3">Amount</th>
                                    <th class="p-3">Date</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="invoice-table-body">
                                <!-- JS Injection -->
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="assets/js/reception_utils.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="assets/js/billing.js"></script>
</body>
</html>
