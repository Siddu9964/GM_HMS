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
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS (required for Sidebar and Navbar) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="assets/css/admin_common.css">
    <link rel="stylesheet" href="assets/css/ot_billing.css?v=<?= time() ?>">
</head>
<body style="background-color: #f3efe6; color: #1f6b4a;">

    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include 'includes/navbar.php'; ?>

            <main class="w-full flex-grow p-6 overflow-y-auto" style="background: #f3efe6;">
                
                <!-- Page Header & Live Patient Search -->
                <div class="page-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="page-title-icon">
                            <i class="fas fa-procedures"></i>
                        </div>
                        <div>
                            <h1 class="page-title">Operation Theater Billing</h1>
                            <p style="margin:0; font-size: 0.8rem; color: #1f6b4a; font-weight:600; opacity: 0.85;">
                                Intra-operative & surgical charge allocation for admitted IPD patients
                            </p>
                        </div>
                    </div>
                    
                    <div class="search-container">
                        <div class="search-input-group">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchQuery" class="form-control" placeholder="Search Patient Name, IP No, or Bed..." autocomplete="off">
                        </div>
                        <button class="btn-fetch" id="btnSearchPatient">
                            <i class="fas fa-bolt"></i> Auto Fetch
                        </button>
                        <!-- Live Search Results Dropdown -->
                        <div id="patientDropdown" class="patient-dropdown-menu"></div>
                    </div>
                </div>

                <!-- Step 1: Patient Identity & Demographics -->
                <div class="ot-card">
                    <div class="ot-card-header">
                        <div class="ot-card-header-left">
                            <div class="ot-card-header-icon">
                                <i class="fas fa-user-injured"></i>
                            </div>
                            <h5>Patient Clinical Demographics</h5>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="preset-pill" onclick="resetPatientDetails(); showToast('Patient selection cleared.', 'info');" style="border-radius:8px; padding:3px 10px;">
                                <i class="fas fa-times me-1"></i> Clear Patient
                            </button>
                            <span class="ot-card-badge"><i class="fas fa-id-card me-1"></i> Patient Info</span>
                        </div>
                    </div>
                    <div class="ot-card-body">
                        <div class="patient-grid">
                            <div class="detail-item">
                                <label class="detail-label"><i class="fas fa-hashtag"></i> IP Number</label>
                                <input type="text" id="patIpNo" class="form-control" placeholder="—" readonly>
                            </div>
                            <div class="detail-item">
                                <label class="detail-label"><i class="fas fa-file-medical"></i> Admission ID</label>
                                <input type="text" id="patAdmId" class="form-control" placeholder="—" readonly>
                            </div>
                            <div class="detail-item" style="grid-column: span 2;">
                                <label class="detail-label"><i class="fas fa-user"></i> Patient Name</label>
                                <input type="text" id="patName" class="form-control" placeholder="Search and select patient above..." readonly>
                            </div>
                            <div class="detail-item">
                                <label class="detail-label"><i class="fas fa-birthday-cake"></i> Age</label>
                                <input type="text" id="patAge" class="form-control" placeholder="—" readonly>
                            </div>
                            <div class="detail-item">
                                <label class="detail-label"><i class="fas fa-venus-mars"></i> Gender</label>
                                <input type="text" id="patGender" class="form-control" placeholder="—" readonly>
                            </div>
                            
                            <!-- Second Row -->
                            <div class="detail-item">
                                <label class="detail-label"><i class="fas fa-hospital-alt"></i> Ward</label>
                                <input type="text" id="patWard" class="form-control" placeholder="—" readonly>
                            </div>
                            <div class="detail-item">
                                <label class="detail-label"><i class="fas fa-door-open"></i> Room</label>
                                <input type="text" id="patRoom" class="form-control" placeholder="—" readonly>
                            </div>
                            <div class="detail-item">
                                <label class="detail-label"><i class="fas fa-bed"></i> Bed No.</label>
                                <input type="text" id="patBed" class="form-control" placeholder="—" readonly>
                            </div>
                            <div class="detail-item">
                                <label class="detail-label"><i class="fas fa-calendar-alt"></i> Admission Date</label>
                                <input type="text" id="patAdmDate" class="form-control" placeholder="—" readonly>
                            </div>
                            <div class="detail-item" style="grid-column: span 2;">
                                <label class="detail-label"><i class="fas fa-user-md"></i> Primary Consultant</label>
                                <input type="text" id="patConsultant" class="form-control" placeholder="—" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Surgery & Anesthesia Protocol with Quick Presets -->
                <div class="ot-card">
                    <div class="ot-card-header">
                        <div class="ot-card-header-left">
                            <div class="ot-card-header-icon">
                                <i class="fas fa-heartbeat"></i>
                            </div>
                            <h5>Surgery & OT Theater Protocol</h5>
                        </div>
                        <span class="ot-card-badge"><i class="fas fa-clock me-1"></i> Surgical Session</span>
                    </div>
                    <div class="ot-card-body">
                        <!-- Quick Fill Surgery Presets -->
                        <div class="presets-ribbon">
                            <span class="presets-label"><i class="fas fa-magic"></i> Quick Presets:</span>
                            <span class="preset-pill" onclick="applySurgeryPreset('Laparoscopic Appendectomy', 'General Surgery', 'MAJOR OT', 'General Anaesthesia')">
                                <i class="fas fa-bolt"></i> Appendectomy
                            </span>
                            <span class="preset-pill" onclick="applySurgeryPreset('Cesarean Section (LSCS)', 'Obstetrics & Gynaecology', 'LABOUR ROOM', 'Spinal Anaesthesia')">
                                <i class="fas fa-baby"></i> C-Section (LSCS)
                            </span>
                            <span class="preset-pill" onclick="applySurgeryPreset('Total Knee Replacement', 'Orthopaedics', 'MAJOR OT', 'Spinal Anaesthesia')">
                                <i class="fas fa-bone"></i> Knee Replacement
                            </span>
                            <span class="preset-pill" onclick="applySurgeryPreset('Coronary Angiography', 'Cardiology', 'CATHLAB', 'Local Anaesthesia')">
                                <i class="fas fa-heart"></i> Angiography
                            </span>
                            <span class="preset-pill" onclick="applySurgeryPreset('Inguinal Hernia Repair', 'General Surgery', 'MINOR OT', 'General Anaesthesia')">
                                <i class="fas fa-shield-virus"></i> Hernia Repair
                            </span>
                        </div>

                        <div class="surgery-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                            <div>
                                <label class="form-label-custom"><i class="fas fa-file-medical-alt"></i> Surgery / Procedure Name *</label>
                                <input type="text" id="surgName" class="form-control-custom" placeholder="e.g. Appendectomy, Angioplasty, Knee Replacement...">
                            </div>
                            <div>
                                <label class="form-label-custom"><i class="fas fa-clinic-medical"></i> Department *</label>
                                <input type="text" id="surgDept" class="form-control-custom" placeholder="e.g. General Surgery, Ortho, Cardio...">
                            </div>
                            <div>
                                <label class="form-label-custom"><i class="fas fa-door-closed"></i> Operating Theatre *</label>
                                <select class="form-select-custom" id="surgTheatre">
                                    <option value="">-- Select Theatre --</option>
                                    <option value="OPERATION THEATER">Operation Theater</option>
                                    <option value="CATHLAB">CATHLAB</option>
                                    <option value="LABOUR ROOM">LABOUR ROOM</option>
                                    <option value="MAJOR OT">MAJOR OT</option>
                                    <option value="MINOR OT">MINOR OT</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label-custom"><i class="fas fa-calendar-check"></i> Surgery Date</label>
                                <input type="date" id="surgDate" class="form-control-custom" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div>
                                <label class="form-label-custom"><i class="fas fa-syringe"></i> Anesthesia Type *</label>
                                <select class="form-select-custom" id="surgAnesType">
                                    <option value="">-- Select Type --</option>
                                    <option value="General Anaesthesia">General Anaesthesia</option>
                                    <option value="Local Anaesthesia">Local Anaesthesia</option>
                                    <option value="Spinal Anaesthesia">Spinal Anaesthesia</option>
                                    <option value="Epidural Anaesthesia">Epidural Anaesthesia</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Doctor & Surgical Team Charges Matrix -->
                <div class="ot-card">
                    <div class="ot-card-header">
                        <div class="ot-card-header-left">
                            <div class="ot-card-header-icon">
                                <i class="fas fa-user-md"></i>
                            </div>
                            <h5>Surgical Team & Doctor Charges Allocation</h5>
                        </div>
                        <span class="ot-card-badge"><i class="fas fa-coins me-1"></i> Fee Distribution</span>
                    </div>
                    <div class="ot-card-body p-0">
                        <div class="table-responsive">
                            <table class="table ot-table">
                                <thead>
                                    <tr>
                                        <th>Team Particulars</th>
                                        <th>Doctor / Consultant</th>
                                        <th class="text-center">Dr. %</th>
                                        <th class="text-end">Dr. Charges (₹)</th>
                                        <th class="text-center">Hosp %</th>
                                        <th class="text-end">Hosp Charges (₹)</th>
                                        <th class="text-end">Service Charge (₹)</th>
                                        <th class="text-end" style="width: 130px;">Line Total (₹)</th>
                                        <th class="text-center" style="width: 45px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Surgeon -->
                                    <tr class="doc-row" data-type="SURGEON">
                                        <td>
                                            <div class="role-pill">
                                                <span class="role-dot"></span>
                                                <span>Surgeon Name *</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="autocomplete-wrapper">
                                                <input type="text" class="form-control select-consultant" placeholder="Type doctor name..." autocomplete="off">
                                                <i class="fas fa-search search-icon"></i>
                                                <div class="autocomplete-dropdown"></div>
                                            </div>
                                        </td>
                                        <td><input type="number" class="form-control input-percent dr-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount dr-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-percent h-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount h-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-amount s-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td class="calculated-amt row-amt">₹0.00</td>
                                        <td class="text-center"><button type="button" class="btn-row-clear" onclick="clearDoctorRow(this)" title="Clear Row"><i class="fas fa-times"></i></button></td>
                                    </tr>
                                    <!-- Asst Surgeon 1 -->
                                    <tr class="doc-row" data-type="ASST_SURGEON_1">
                                        <td>
                                            <div class="role-pill">
                                                <span class="role-dot"></span>
                                                <span>Asst. Surgeon 1</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="autocomplete-wrapper">
                                                <input type="text" class="form-control select-consultant" placeholder="Type doctor name..." autocomplete="off">
                                                <i class="fas fa-search search-icon"></i>
                                                <div class="autocomplete-dropdown"></div>
                                            </div>
                                        </td>
                                        <td><input type="number" class="form-control input-percent dr-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount dr-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-percent h-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount h-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-amount s-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td class="calculated-amt row-amt">₹0.00</td>
                                        <td class="text-center"><button type="button" class="btn-row-clear" onclick="clearDoctorRow(this)" title="Clear Row"><i class="fas fa-times"></i></button></td>
                                    </tr>
                                    <!-- Asst Surgeon 2 -->
                                    <tr class="doc-row" data-type="ASST_SURGEON_2">
                                        <td>
                                            <div class="role-pill">
                                                <span class="role-dot"></span>
                                                <span>Asst. Surgeon 2</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="autocomplete-wrapper">
                                                <input type="text" class="form-control select-consultant" placeholder="Type doctor name..." autocomplete="off">
                                                <i class="fas fa-search search-icon"></i>
                                                <div class="autocomplete-dropdown"></div>
                                            </div>
                                        </td>
                                        <td><input type="number" class="form-control input-percent dr-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount dr-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-percent h-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount h-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-amount s-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td class="calculated-amt row-amt">₹0.00</td>
                                        <td class="text-center"><button type="button" class="btn-row-clear" onclick="clearDoctorRow(this)" title="Clear Row"><i class="fas fa-times"></i></button></td>
                                    </tr>
                                    <!-- Other Doctor 1 -->
                                    <tr class="doc-row" data-type="OTHER_DOC_1">
                                        <td>
                                            <div class="role-pill">
                                                <span class="role-dot"></span>
                                                <span>Other Doctor 1</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="autocomplete-wrapper">
                                                <input type="text" class="form-control select-consultant" placeholder="Type doctor name..." autocomplete="off">
                                                <i class="fas fa-search search-icon"></i>
                                                <div class="autocomplete-dropdown"></div>
                                            </div>
                                        </td>
                                        <td><input type="number" class="form-control input-percent dr-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount dr-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-percent h-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount h-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-amount s-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td class="calculated-amt row-amt">₹0.00</td>
                                        <td class="text-center"><button type="button" class="btn-row-clear" onclick="clearDoctorRow(this)" title="Clear Row"><i class="fas fa-times"></i></button></td>
                                    </tr>
                                    <!-- Other Doctor 2 -->
                                    <tr class="doc-row" data-type="OTHER_DOC_2">
                                        <td>
                                            <div class="role-pill">
                                                <span class="role-dot"></span>
                                                <span>Other Doctor 2</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="autocomplete-wrapper">
                                                <input type="text" class="form-control select-consultant" placeholder="Type doctor name..." autocomplete="off">
                                                <i class="fas fa-search search-icon"></i>
                                                <div class="autocomplete-dropdown"></div>
                                            </div>
                                        </td>
                                        <td><input type="number" class="form-control input-percent dr-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount dr-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-percent h-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount h-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-amount s-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td class="calculated-amt row-amt">₹0.00</td>
                                        <td class="text-center"><button type="button" class="btn-row-clear" onclick="clearDoctorRow(this)" title="Clear Row"><i class="fas fa-times"></i></button></td>
                                    </tr>
                                    <!-- Anesthetist -->
                                    <tr class="doc-row" data-type="ANESTHETIST">
                                        <td>
                                            <div class="role-pill">
                                                <span class="role-dot"></span>
                                                <span>Anesthetist</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="autocomplete-wrapper">
                                                <input type="text" class="form-control select-consultant" placeholder="Type doctor name..." autocomplete="off">
                                                <i class="fas fa-search search-icon"></i>
                                                <div class="autocomplete-dropdown"></div>
                                            </div>
                                        </td>
                                        <td><input type="number" class="form-control input-percent dr-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount dr-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-percent h-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount h-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-amount s-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td class="calculated-amt row-amt">₹0.00</td>
                                        <td class="text-center"><button type="button" class="btn-row-clear" onclick="clearDoctorRow(this)" title="Clear Row"><i class="fas fa-times"></i></button></td>
                                    </tr>
                                    <!-- Stand-by Anesthetist -->
                                    <tr class="doc-row" data-type="STANDBY_ANES">
                                        <td>
                                            <div class="role-pill">
                                                <span class="role-dot"></span>
                                                <span>St. by Anesthetist</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="autocomplete-wrapper">
                                                <input type="text" class="form-control select-consultant" placeholder="Type doctor name..." autocomplete="off">
                                                <i class="fas fa-search search-icon"></i>
                                                <div class="autocomplete-dropdown"></div>
                                            </div>
                                        </td>
                                        <td><input type="number" class="form-control input-percent dr-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount dr-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-percent h-perc mx-auto" placeholder="0"></td>
                                        <td><input type="number" class="form-control input-amount h-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td><input type="number" class="form-control input-amount s-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td class="calculated-amt row-amt">₹0.00</td>
                                        <td class="text-center"><button type="button" class="btn-row-clear" onclick="clearDoctorRow(this)" title="Clear Row"><i class="fas fa-times"></i></button></td>
                                    </tr>
                                    <!-- OT Service -->
                                    <tr class="doc-row" data-type="OT_SERVICE">
                                        <td>
                                            <div class="role-pill">
                                                <span class="role-dot"></span>
                                                <span>OT Service Charge</span>
                                            </div>
                                        </td>
                                        <td><input type="text" class="form-control select-consultant" placeholder="Description of OT service..."></td>
                                        <td><input type="number" class="form-control input-percent dr-perc mx-auto" placeholder="0" disabled></td>
                                        <td><input type="number" class="form-control input-amount dr-charge ms-auto calc-trigger" placeholder="0.00" disabled></td>
                                        <td><input type="number" class="form-control input-percent h-perc mx-auto" placeholder="0" disabled></td>
                                        <td><input type="number" class="form-control input-amount h-charge ms-auto calc-trigger" placeholder="0.00" disabled></td>
                                        <td><input type="number" class="form-control input-amount s-charge ms-auto calc-trigger" placeholder="0.00"></td>
                                        <td class="calculated-amt row-amt">₹0.00</td>
                                        <td class="text-center"><button type="button" class="btn-row-clear" onclick="clearDoctorRow(this)" title="Clear Row"><i class="fas fa-times"></i></button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Step 4 & 5: Additional Charges & Financial Summary -->
                <div class="row g-4">
                    <!-- Additional Charges -->
                    <div class="col-lg-7 col-xl-7">
                        <div class="ot-card h-100">
                            <div class="ot-card-header">
                                <div class="ot-card-header-left">
                                    <div class="ot-card-header-icon">
                                        <i class="fas fa-plus-circle"></i>
                                    </div>
                                    <h5>Additional OT & Clinical Overheads</h5>
                                </div>
                                <span class="ot-card-badge"><i class="fas fa-gas-pump me-1"></i> Consumables & Overheads</span>
                            </div>
                            <div class="ot-card-body">
                                <!-- 3 Fee Matrix Cards -->
                                <div class="overhead-cards-grid">
                                    <div class="overhead-card-item">
                                        <div class="overhead-header-tag">
                                            <div class="overhead-icon-bubble"><i class="fas fa-wind"></i></div>
                                            <span>Anesthesia Gas</span>
                                        </div>
                                        <div class="overhead-input-wrapper">
                                            <span class="overhead-currency-symbol">₹</span>
                                            <input type="number" id="anesGas" class="overhead-input calc-trigger" placeholder="0.00" step="0.01">
                                        </div>
                                    </div>
                                    
                                    <div class="overhead-card-item">
                                        <div class="overhead-header-tag">
                                            <div class="overhead-icon-bubble"><i class="fas fa-hospital"></i></div>
                                            <span>Ext. OT Charges</span>
                                        </div>
                                        <div class="overhead-input-wrapper">
                                            <span class="overhead-currency-symbol">₹</span>
                                            <input type="number" id="extOt" class="overhead-input calc-trigger" placeholder="0.00" step="0.01">
                                        </div>
                                    </div>
                                    
                                    <div class="overhead-card-item">
                                        <div class="overhead-header-tag">
                                            <div class="overhead-icon-bubble"><i class="fas fa-syringe"></i></div>
                                            <span>Ext. Anesthesia</span>
                                        </div>
                                        <div class="overhead-input-wrapper">
                                            <span class="overhead-currency-symbol">₹</span>
                                            <input type="number" id="extAnes" class="overhead-input calc-trigger" placeholder="0.00" step="0.01">
                                        </div>
                                    </div>
                                </div>

                                <!-- Clinical Remarks & Quick Tags -->
                                <div class="remarks-container">
                                    <div class="remarks-header">
                                        <div class="remarks-header-title">
                                            <i class="fas fa-notes-medical"></i> Surgical Clinical Remarks / Justification
                                        </div>
                                        <span style="font-size:0.72rem; font-weight:700; color:#1f6b4a; opacity:0.8;">Optional</span>
                                    </div>
                                    <textarea id="chargePurpose" class="remarks-textarea" placeholder="Enter surgical notes, procedure justification, or special equipment requirements..."></textarea>
                                    <div class="quick-tags-wrap">
                                        <span class="quick-tag-pill" onclick="appendNote('Routine Elective OT')">+ Routine Elective</span>
                                        <span class="quick-tag-pill" onclick="appendNote('Emergency Surgical Procedure')">+ Emergency OT</span>
                                        <span class="quick-tag-pill" onclick="appendNote('Extended 2+ Hours Session')">+ Extended Hours</span>
                                        <span class="quick-tag-pill" onclick="appendNote('Special Implants Used')">+ Implants Used</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Billing Summary Terminal -->
                    <div class="col-lg-5 col-xl-5">
                        <div class="ot-card h-100">
                            <div class="ot-card-header">
                                <div class="ot-card-header-left">
                                    <div class="ot-card-header-icon">
                                        <i class="fas fa-calculator"></i>
                                    </div>
                                    <h5>OT Billing Summary Terminal</h5>
                                </div>
                                <span class="ot-card-badge"><i class="fas fa-receipt me-1"></i> Live Invoice</span>
                            </div>
                            <div class="ot-card-body">
                                <div class="summary-terminal">
                                    <!-- Gross Total Banner -->
                                    <div class="summary-header-card">
                                        <div class="summary-header-label">
                                            <i class="fas fa-layer-group me-1"></i> Total Gross Charges
                                        </div>
                                        <div class="summary-gross-figure" id="sumTotalCharges">₹0.00</div>
                                    </div>
                                    
                                    <div class="summary-content-body">
                                        <!-- Dual Calc Grid (Discount & GST) -->
                                        <div class="summary-calc-grid">
                                            <div class="summary-calc-item">
                                                <div class="summary-calc-item-label">
                                                    <span><i class="fas fa-tag me-1"></i> Discount</span>
                                                    <span id="sumDiscount" class="summary-calc-amount">- ₹0.00</span>
                                                </div>
                                                <div class="summary-calc-input-row">
                                                    <span style="font-size:0.75rem; font-weight:800; color:#1f6b4a;">Rate %</span>
                                                    <input type="number" id="discountPercent" class="summary-percent-input calc-trigger" placeholder="0" min="0" max="100">
                                                </div>
                                                <div class="quick-chips-row">
                                                    <span class="calc-chip" onclick="setDiscount(0)">0%</span>
                                                    <span class="calc-chip" onclick="setDiscount(5)">5%</span>
                                                    <span class="calc-chip" onclick="setDiscount(10)">10%</span>
                                                    <span class="calc-chip" onclick="setDiscount(15)">15%</span>
                                                </div>
                                            </div>

                                            <div class="summary-calc-item">
                                                <div class="summary-calc-item-label">
                                                    <span><i class="fas fa-percent me-1"></i> GST Tax</span>
                                                    <span id="sumGst" class="summary-calc-amount">+ ₹0.00</span>
                                                </div>
                                                <div class="summary-calc-input-row">
                                                    <span style="font-size:0.75rem; font-weight:800; color:#1f6b4a;">Rate %</span>
                                                    <input type="number" id="gstPercent" class="summary-percent-input calc-trigger" placeholder="0" min="0" max="100">
                                                </div>
                                                <div class="quick-chips-row">
                                                    <span class="calc-chip" onclick="setGst(0)">0%</span>
                                                    <span class="calc-chip" onclick="setGst(5)">5%</span>
                                                    <span class="calc-chip" onclick="setGst(12)">12%</span>
                                                    <span class="calc-chip" onclick="setGst(18)">18%</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Grand Total Showcase Banner -->
                                        <div class="grand-total-banner">
                                            <div class="grand-total-info">
                                                <span class="grand-total-caption">Net Payable Amount</span>
                                                <span class="grand-total-badge">Inclusive of all taxes</span>
                                            </div>
                                            <div class="grand-total-figure" id="sumGrandTotal">₹0.00</div>
                                        </div>

                                        <!-- Payment & Settlement Console -->
                                        <div class="payment-settle-card">
                                            <div class="payment-row-item">
                                                <div class="payment-row-label">
                                                    <i class="fas fa-hand-holding-usd"></i>
                                                    <span>Amount Paid *</span>
                                                </div>
                                                <input type="number" id="amountPaid" class="payment-input-box calc-trigger" placeholder="0.00" step="0.01">
                                            </div>
                                            
                                            <div class="quick-chips-row" style="justify-content: flex-end; margin-top: -4px;">
                                                <span class="calc-chip" onclick="setPaymentMode('full')">Full 100%</span>
                                                <span class="calc-chip" onclick="setPaymentMode('half')">50% Advance</span>
                                                <span class="calc-chip" onclick="setPaymentMode('zero')">Pay Later</span>
                                            </div>
                                            
                                            <div class="balance-due-pill" id="balanceDueRow">
                                                <div class="balance-due-label">
                                                    <i class="fas fa-balance-scale me-1"></i> Balance Due
                                                </div>
                                                <div class="balance-due-figure" id="sumBalanceDue">₹0.00</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
            
            <!-- Sticky Action Bar with Live Counter -->
            <div class="action-bar">
                <div class="action-bar-info">
                    <div class="sticky-pat-tag" id="stickyPatientName">
                        <i class="fas fa-user-circle me-1"></i> <span>No Patient Selected</span>
                    </div>
                    <div class="sticky-total-tag">
                        <span>Grand Total: </span><strong id="stickyGrandTotal">₹0.00</strong>
                    </div>
                    <span style="font-size:0.75rem; color:#1f6b4a; opacity:0.8; font-weight:700;">
                        <i class="fas fa-keyboard me-1"></i> Press [Ctrl + S] to Save
                    </span>
                </div>
                <div class="action-bar-buttons">
                    <button class="btn-outline-custom" onclick="window.location.reload()">
                        <i class="fas fa-times me-1"></i> Reset Form
                    </button>
                    <button class="btn-primary-custom" id="btnSave">
                        <i class="fas fa-save me-1"></i> Save OT Charges
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Centered Message Modal Overlay -->
    <div id="otMsgModal" class="custom-modal-overlay" style="display: none;">
        <div class="custom-modal-box">
            <div id="otModalIcon" style="font-size: 2.6rem; margin-bottom: 12px; color: #1f6b4a;">
                <i class="fas fa-check-circle"></i>
            </div>
            <h4 id="otModalTitle" style="font-weight: 800; color: #1f6b4a; margin-bottom: 8px; font-size: 1.25rem;">Notice</h4>
            <p id="otModalText" style="color: #1f6b4a; font-weight: 700; font-size: 0.92rem; line-height: 1.5; margin-bottom: 20px;">Message content here</p>
            <button id="otModalBtn" class="btn-primary-custom w-100 justify-content-center" onclick="closeOtModal()">
                OK
            </button>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay">
        <div class="loading-spinner-box">
            <div class="spinner"></div>
            <div style="font-weight: 800; color: #1f6b4a; font-size: 0.95rem;">
                Processing OT Billing Data...
            </div>
        </div>
    </div>

    <script src="assets/js/ot_billing.js?v=<?= time() ?>"></script>
</body>
</html>
