<?php
session_start();
require_once '../config/SecurityConfig.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$pageTitle = 'OT Billing (Add Charges)';
$userName  = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OT Billing — GM HMS</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS (required for Sidebar and Navbar) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="assets/css/admin_common.css">
    <link rel="stylesheet" href="assets/css/ot_billing.css?v=<?= time() ?>">
</head>
<body class="bg-slate-50">

    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include 'includes/navbar.php'; ?>

            <main class="w-full flex-grow p-6 overflow-y-auto">
                
                <div class="page-header">
                    <h1 class="page-title"><i data-lucide="syringe"></i> Operation Theater Billing</h1>
                    
                    <div class="search-container">
                        <div class="search-input-group">
                            <i data-lucide="search" style="width:16px;height:16px;"></i>
                            <input type="text" id="searchQuery" class="form-control" placeholder="IP No. or Admission ID" autocomplete="off">
                        </div>
                        <button class="btn-primary-custom" id="btnSearchPatient">Auto Fetch</button>
                    </div>
                </div>

                <!-- Patient Details -->
                <div class="ot-card">
                    <div class="ot-card-header">
                        <i data-lucide="user"></i>
                        <h5>Patient Details</h5>
                    </div>
                    <div class="ot-card-body">
                        <div class="patient-grid">
                            <div class="detail-item">
                                <label class="detail-label">IP No.</label>
                                <input type="text" id="patIpNo" class="form-control" readonly>
                            </div>
                            <div class="detail-item">
                                <label class="detail-label">Admission ID</label>
                                <input type="text" id="patAdmId" class="form-control" readonly>
                            </div>
                            <div class="detail-item" style="grid-column: span 2;">
                                <label class="detail-label">Patient Name</label>
                                <input type="text" id="patName" class="form-control" readonly>
                            </div>
                            <div class="detail-item">
                                <label class="detail-label">Age</label>
                                <input type="text" id="patAge" class="form-control" readonly>
                            </div>
                            <div class="detail-item">
                                <label class="detail-label">Gender</label>
                                <input type="text" id="patGender" class="form-control" readonly>
                            </div>
                            
                            <!-- Second Row -->
                            <div class="detail-item">
                                <label class="detail-label">Ward</label>
                                <input type="text" id="patWard" class="form-control" readonly>
                            </div>
                            <div class="detail-item">
                                <label class="detail-label">Room</label>
                                <input type="text" id="patRoom" class="form-control" readonly>
                            </div>
                            <div class="detail-item">
                                <label class="detail-label">Bed No.</label>
                                <input type="text" id="patBed" class="form-control" readonly>
                            </div>
                            <div class="detail-item">
                                <label class="detail-label">Admission Date</label>
                                <input type="text" id="patAdmDate" class="form-control" readonly>
                            </div>
                            <div class="detail-item" style="grid-column: span 2;">
                                <label class="detail-label">Consultant</label>
                                <input type="text" id="patConsultant" class="form-control" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Surgery Details -->
                <div class="ot-card">
                    <div class="ot-card-header">
                        <i data-lucide="activity"></i>
                        <h5>Surgery Details</h5>
                    </div>
                    <div class="ot-card-body">
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem;">
                            <div>
                                <label class="form-label">Department *</label>
                                <input type="text" id="surgDept" class="form-control" placeholder="Enter Department">
                            </div>
                            <div>
                                <label class="form-label">Theatre *</label>
                                <select class="form-select" id="surgTheatre">
                                    <option value="">Select Theatre</option>
                                    <option value="OPERATION THEATER">Operation Theater</option>
                                    <option value="CATHLAB">CATHLAB</option>
                                    <option value="LABOUR ROOM">LABOUR ROOM</option>
                                    <option value="MAJOR OT">MAJOR OT</option>
                                    <option value="MINOR OT">MINOR OT</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Surgery Date</label>
                                <input type="date" id="surgDate" class="form-control" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div>
                                <label class="form-label">Anesthesia Type *</label>
                                <select class="form-select" id="surgAnesType">
                                    <option value="">Select Type</option>
                                    <option value="General Anaesthesia">General Anaesthesia</option>
                                    <option value="Local Anaesthesia">Local Anaesthesia</option>
                                    <option value="Spinal Anaesthesia">Spinal Anaesthesia</option>
                                    <option value="Epidural Anaesthesia">Epidural Anaesthesia</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Doctor Charges Table -->
                <div class="ot-card">
                    <div class="ot-card-header">
                        <i data-lucide="stethoscope"></i>
                        <h5>Doctor Charges</h5>
                    </div>
                    <div class="ot-card-body p-0">
                        <div class="table-responsive">
                            <table class="table ot-table">
                                <thead>
                                    <tr>
                                        <th>Particulars</th>
                                        <th>Consultant</th>
                                        <th class="text-center">Dr. %</th>
                                        <th class="text-end">Dr. Charges</th>
                                        <th class="text-center">H. %</th>
                                        <th class="text-end">H. Charges</th>
                                        <th class="text-end">Service Charge</th>
                                        <th class="text-end" style="width: 120px;">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Surgeon -->
                                    <tr class="doc-row" data-type="SURGEON">
                                        <td class="row-title">Surgeon Name <span class="text-danger">*</span></td>
                                        <td>
                                            <div class="autocomplete-wrapper">
                                                <input type="text" class="form-control select-consultant" placeholder="Dr. Name" autocomplete="off">
                                                <i data-lucide="search" class="search-icon"></i>
                                                <div class="autocomplete-dropdown"></div>
                                            </div>
                                        </td>
                                        <td><input type="number" class="form-control input-percent dr-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount dr-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-percent h-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount h-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-amount s-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td class="calculated-amt row-amt">₹0.00</td>
                                    </tr>
                                    <!-- Asst Surgeon 1 -->
                                    <tr class="doc-row" data-type="ASST_SURGEON_1">
                                        <td class="row-title">Asst. Surgeon 1</td>
                                        <td>
                                            <div class="autocomplete-wrapper">
                                                <input type="text" class="form-control select-consultant" placeholder="Dr. Name" autocomplete="off">
                                                <i data-lucide="search" class="search-icon"></i>
                                                <div class="autocomplete-dropdown"></div>
                                            </div>
                                        </td>
                                        <td><input type="number" class="form-control input-percent dr-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount dr-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-percent h-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount h-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-amount s-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td class="calculated-amt row-amt">₹0.00</td>
                                    </tr>
                                    <!-- Asst Surgeon 2 -->
                                    <tr class="doc-row" data-type="ASST_SURGEON_2">
                                        <td class="row-title">Asst. Surgeon 2</td>
                                        <td>
                                            <div class="autocomplete-wrapper">
                                                <input type="text" class="form-control select-consultant" placeholder="Dr. Name" autocomplete="off">
                                                <i data-lucide="search" class="search-icon"></i>
                                                <div class="autocomplete-dropdown"></div>
                                            </div>
                                        </td>
                                        <td><input type="number" class="form-control input-percent dr-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount dr-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-percent h-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount h-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-amount s-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td class="calculated-amt row-amt">₹0.00</td>
                                    </tr>
                                    <!-- Other Doctor 1 -->
                                    <tr class="doc-row" data-type="OTHER_DOC_1">
                                        <td class="row-title">Other Doctor 1</td>
                                        <td>
                                            <div class="autocomplete-wrapper">
                                                <input type="text" class="form-control select-consultant" placeholder="Dr. Name" autocomplete="off">
                                                <i data-lucide="search" class="search-icon"></i>
                                                <div class="autocomplete-dropdown"></div>
                                            </div>
                                        </td>
                                        <td><input type="number" class="form-control input-percent dr-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount dr-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-percent h-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount h-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-amount s-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td class="calculated-amt row-amt">₹0.00</td>
                                    </tr>
                                    <!-- Other Doctor 2 -->
                                    <tr class="doc-row" data-type="OTHER_DOC_2">
                                        <td class="row-title">Other Doctor 2</td>
                                        <td>
                                            <div class="autocomplete-wrapper">
                                                <input type="text" class="form-control select-consultant" placeholder="Dr. Name" autocomplete="off">
                                                <i data-lucide="search" class="search-icon"></i>
                                                <div class="autocomplete-dropdown"></div>
                                            </div>
                                        </td>
                                        <td><input type="number" class="form-control input-percent dr-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount dr-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-percent h-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount h-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-amount s-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td class="calculated-amt row-amt">₹0.00</td>
                                    </tr>
                                    <!-- Anesthetist -->
                                    <tr class="doc-row" data-type="ANESTHETIST">
                                        <td class="row-title">Anesthetist</td>
                                        <td>
                                            <div class="autocomplete-wrapper">
                                                <input type="text" class="form-control select-consultant" placeholder="Dr. Name" autocomplete="off">
                                                <i data-lucide="search" class="search-icon"></i>
                                                <div class="autocomplete-dropdown"></div>
                                            </div>
                                        </td>
                                        <td><input type="number" class="form-control input-percent dr-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount dr-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-percent h-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount h-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-amount s-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td class="calculated-amt row-amt">₹0.00</td>
                                    </tr>
                                    <!-- Stand-by Anesthetist -->
                                    <tr class="doc-row" data-type="STANDBY_ANES">
                                        <td class="row-title">St. by Anesthetist</td>
                                        <td>
                                            <div class="autocomplete-wrapper">
                                                <input type="text" class="form-control select-consultant" placeholder="Dr. Name" autocomplete="off">
                                                <i data-lucide="search" class="search-icon"></i>
                                                <div class="autocomplete-dropdown"></div>
                                            </div>
                                        </td>
                                        <td><input type="number" class="form-control input-percent dr-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount dr-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-percent h-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount h-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-amount s-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td class="calculated-amt row-amt">₹0.00</td>
                                    </tr>
                                    <!-- OT Service -->
                                    <tr class="doc-row" data-type="OT_SERVICE">
                                        <td class="row-title">OT Service</td>
                                        <td><input type="text" class="form-control select-consultant" placeholder="Description"></td>
                                        <td><input type="number" class="form-control input-percent dr-perc mx-auto" placeholder="0" disabled></td>
                                        <td><input type="number" class="form-control input-amount dr-charge ms-auto calc-trigger" placeholder="0.00" disabled></td>
                                        <td><input type="number" class="form-control input-percent h-perc mx-auto" placeholder="0" disabled></td>
                                        <td><input type="number" class="form-control input-amount h-charge ms-auto calc-trigger" placeholder="0.00" disabled></td>
                                        <td><input type="number" class="form-control input-amount s-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td class="calculated-amt row-amt">₹0.00</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Bottom Section: Additional Charges & Summary -->
                <div class="row">
                    <!-- Additional Charges -->
                    <div class="col-lg-7 col-xl-8">
                        <div class="ot-card h-100">
                            <div class="ot-card-header">
                                <i data-lucide="plus-circle"></i>
                                <h5>Additional Charges</h5>
                            </div>
                            <div class="ot-card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Anesthesia Gas (₹)</label>
                                        <input type="number" id="anesGas" class="form-control calc-trigger" placeholder="0.00">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Ext. OT Charges (₹)</label>
                                        <input type="number" id="extOt" class="form-control calc-trigger" placeholder="0.00">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Ext. Anes. Charges (₹)</label>
                                        <input type="number" id="extAnes" class="form-control calc-trigger" placeholder="0.00">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Purpose / Notes</label>
                                        <textarea id="chargePurpose" class="form-control" rows="2" placeholder="Enter notes or remarks..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Billing Summary -->
                    <div class="col-lg-5 col-xl-4">
                        <div class="ot-card h-100">
                            <div class="ot-card-header">
                                <i data-lucide="calculator"></i>
                                <h5>Billing Summary</h5>
                            </div>
                            <div class="ot-card-body">
                                <div class="summary-box">
                                    <div class="summary-row">
                                        <span>Total Charges</span>
                                        <span id="sumTotalCharges">₹0.00</span>
                                    </div>
                                    
                                    <div class="summary-row">
                                        <div class="d-flex align-items-center gap-2">
                                            <span>Discount</span>
                                            <input type="number" id="discountPercent" class="form-control form-control-sm calc-trigger" style="width:60px;" placeholder="%">
                                        </div>
                                        <span id="sumDiscount" class="text-danger">- ₹0.00</span>
                                    </div>
                                    
                                    <div class="summary-row">
                                        <div class="d-flex align-items-center gap-2">
                                            <span>GST</span>
                                            <input type="number" id="gstPercent" class="form-control form-control-sm calc-trigger" style="width:60px;" placeholder="%">
                                        </div>
                                        <span id="sumGst" class="text-success">+ ₹0.00</span>
                                    </div>
                                    
                                    <div class="summary-row grand-total">
                                        <span>Grand Total</span>
                                        <span id="sumGrandTotal">₹0.00</span>
                                    </div>
                                    
                                    <div class="summary-row mt-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <span>Amount Paid <span class="text-danger">*</span></span>
                                        </div>
                                        <input type="number" id="amountPaid" class="form-control form-control-sm calc-trigger fw-bold text-success" style="width:100px; text-align:right;" placeholder="0.00">
                                    </div>
                                    
                                    <div class="summary-row text-danger mt-1" style="font-weight: 700;">
                                        <span>Balance Due</span>
                                        <span id="sumBalanceDue">₹0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
            
            <!-- Sticky Action Bar -->
            <div class="action-bar">
                <button class="btn-outline-custom" onclick="window.location.reload()"><i class="fas fa-times me-1"></i> Cancel</button>
                <button class="btn-primary-custom" id="btnSave"><i class="fas fa-save me-1"></i> Save Charges</button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay">
        <div class="spinner"></div>
        <div style="font-weight: 500; color: var(--primary-color);">Processing...</div>
    </div>

    <script src="assets/js/ot_billing.js?v=<?= time() ?>"></script>
</body>
</html>
