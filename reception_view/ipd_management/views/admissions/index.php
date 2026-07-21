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
    <title>IPD Admissions - GM HMS</title>

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

    <style>
        /* Professional Action Column Styles */
        .btn-action {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            color: #6c757d;
            transition: all 0.2s ease;
            margin: 0 auto;
            padding: 0;
        }

        .btn-action:hover {
            background-color: #ffffff;
            color: #0d6efd;
            border-color: #0d6efd;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
            transform: translateY(-2px);
        }

        .btn-action::after {
            display: none;
        }

        .dropdown-menu {
            border: none;
            padding: 0.5rem;
            border-radius: 12px;
            min-width: 200px;
        }

        .dropdown-item {
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9rem;
            color: #495057;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
            color: #0d6efd;
            transform: translateX(4px);
        }

        .dropdown-item.text-danger:hover {
            background-color: #fff5f5;
            color: #dc3545;
        }

        /* Status Badges Enhancement */
        .admission-status {
            padding: 4px 12px;
            border-radius: 100px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .admission-status.admitted {
            background-color: #ecfdf5;
            color: #059669;
            border: 1px solid #10b98133;
        }

        .admission-status.discharged {
            background-color: #f3f4f6;
            color: #6b7280;
            border: 1px solid #9ca3af33;
        }

        /* Table Enhancement */
        #admissionsTable {
            border-collapse: separate;
            border-spacing: 0 8px;
        }

        #admissionsTable tr {
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
            transition: all 0.2s ease;
        }

        #admissionsTable tr:hover {
            background: #fcfcfc;
            transform: scale(1.002);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        #admissionsTable td {
            padding: 1rem 0.75rem;
            vertical-align: middle;
            border: none;
        }

        #admissionsTable th {
            border: none;
            padding: 1rem 0.75rem;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            color: #94a3b8;
        }

        .animated {
            animation-duration: 0.3s;
            animation-fill-mode: both;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fadeIn {
            animation-name: fadeIn;
        }

        /* ── Final Select2 CSS Fix: Resolved Black Box & Overlap ── */
        .select2-container .select2-selection--single,
        .select2-container--default .select2-selection--single,
        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default.select2-container--open .select2-selection--single {
            background-color: #ffffff !important;
            background-image: none !important;
            border: 1px solid #dee2e6 !important;
            height: 42px !important;
            display: flex !important;
            align-items: center !important;
            box-shadow: none !important;
            border-radius: 0.75rem !important;
            margin-bottom: 0 !important;
        }

        .select2-container .select2-selection--single .select2-selection__rendered {
            color: #212529 !important;
            line-height: normal !important;
            padding-left: 12px !important;
            background: #ffffff !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
            top: 1px !important;
            right: 8px !important;
        }

        .select2-dropdown {
            background-color: #ffffff !important;
            border: 1px solid #dee2e6 !important;
            border-radius: 0.75rem !important;
            z-index: 99999 !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
        }

        /* ── Modern Wizard UI Styles ── */
        .wizard-stepper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            position: relative;
            padding: 0 1rem;
        }

        .wizard-stepper::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: #e2e8f0;
            z-index: 1;
            transform: translateY(-50%);
        }

        .step-item {
            position: relative;
            z-index: 2;
            background: #fff;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #e2e8f0;
            color: #64748b;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .step-item.active {
            border-color: #1f6b4a;
            background: #1f6b4a;
            color: #fff;
            box-shadow: 0 0 0 4px rgba(31, 107, 74, 0.2);
        }

        .step-item.completed {
            border-color: #10b981;
            background: #10b981;
            color: #fff;
        }

        .step-label {
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            font-size: 0.75rem;
            margin-top: 0.5rem;
            font-weight: 600;
            color: #64748b;
        }

        .step-item.active .step-label {
            color: #1f6b4a;
        }

        .step-item.completed .step-label {
            color: #10b981;
        }

        .wiz-step {
            display: none;
            animation: fadeIn 0.4s ease-in-out;
        }

        .wiz-step.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .wiz-card {
            background: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
            margin-bottom: 1.5rem;
        }

        /* ── Custom Patient Search Styles ── */
        .search-results-floating {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            z-index: 1060;
            max-height: 250px;
            overflow-y: auto;
            margin-top: 5px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .search-result-item {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: background 0.2s;
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-result-item:hover {
            background: #f8fafc;
        }

        .search-result-item .p-name {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 2px;
            display: block;
        }

        .search-result-item .p-meta {
            font-size: 0.75rem;
            color: #64748b;
        }

        .patient-selection-summary {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 0.75rem;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 1rem;
        }

        .p-avatar {
            width: 48px;
            height: 48px;
            background: #3b82f6;
            color: #fff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
    </style>
</head>

<body>
    <div class="reception-layout">
        <!-- Sidebar -->
        <?php include '../../../includes/reception_sidebar.php'; ?>

        <!-- Main Content -->
        <div class="reception-main-content">
            <!-- Top Navbar -->
            <?php
            $pageTitle = 'IPD Admissions';
            include '../../../includes/reception_navbar.php';
            ?>

            <!-- Dashboard Content -->
            <div class="reception-content">
                <!-- Page Header -->
                <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;">
                            <i class="fas fa-hospital-user"></i> IPD Admissions
                        </h1>
                        <p style="color: var(--gray-600);">Manage patient admissions and discharges</p>
                    </div>
                    <a href="/GM_HMS/reception_view/ipd_management/public/index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>

                <!-- Admissions Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h2>All Admissions</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdmissionModal">
                            <i class="fas fa-plus"></i> New Admission
                        </button>
                    </div>

                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select class="form-select" id="filterStatus">
                                <option value="">All Status</option>
                                <option value="Admitted">Admitted</option>
                                <option value="Discharged">Discharged</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control" id="searchBox"
                                placeholder="🔍 Search: Phone, Patient ID, Name, Bed...">
                        </div>
                    </div>

                    <table id="admissionsTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Admission ID</th>
                                <th>Patient ID</th>
                                <th>Patient Name</th>
                                <th>Phone</th>
                                <th>Doctor</th>
                                <th>Bed</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Days</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <!-- End Reception Content -->\n
        </div>
        <!-- End Reception Main Content -->
    </div>
    <!-- End Reception Layout -->

    <!-- Add Admission Modal -->
    <div class="modal fade" id="addAdmissionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Admission</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Progress Stepper -->
                    <div class="wizard-stepper">
                        <div class="step-item active" data-step="1">1 <span class="step-label">Patient</span></div>
                        <div class="step-item" data-step="2">2 <span class="step-label">Stay & Bed</span></div>
                        <div class="step-item" data-step="3">3 <span class="step-label">Medical</span></div>
                    </div>

                    <form id="addAdmissionForm">
                        <!-- STEP 1: Patient & Doctor -->
                        <div class="wiz-step active" id="wizStep1">
                            <div class="wiz-card">
                                <h6 class="text-primary mb-4 border-0 pb-0"><i class="fas fa-id-card me-2"></i>Patient
                                    Selection</h6>
                                <div class="row">
                                    <div class="col-md-12 mb-4 position-relative">
                                        <label class="form-label">Search Patient (Name or Mobile) *</label>
                                        <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                            <span class="input-group-text bg-white border-end-0"><i
                                                    class="fas fa-search text-muted"></i></span>
                                            <input type="text" class="form-control border-start-0 py-2"
                                                id="patientSearchInput" placeholder="Type name, ID or phone number..."
                                                autocomplete="off">
                                        </div>
                                        <input type="hidden" id="patientSelect" name="patient_id" required>

                                        <!-- Custom Dropdown Results -->
                                        <div id="patientSearchResults" class="search-results-floating"
                                            style="display:none;"></div>

                                        <!-- Selected Patient Info -->
                                        <div id="patientSelectedSummary" style="display:none;">
                                            <div class="patient-selection-summary shadow-sm">
                                                <div class="p-avatar">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <h6 class="mb-0 fw-bold text-dark" id="selPatientName">-</h6>
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-danger border-0 py-0"
                                                            onclick="clearPatientSelection()">
                                                            <i class="fas fa-times"></i> Change
                                                        </button>
                                                    </div>
                                                    <span class="text-muted small" id="selPatientID">-</span>
                                                    <div class="mt-1">
                                                        <span class="badge bg-soft-primary text-primary me-2"><i
                                                                class="fas fa-phone-alt me-1"></i> <span
                                                                id="selPatientPhone">-</span></span>
                                                        <span class="badge bg-soft-info text-info"><i
                                                                class="fas fa-venus-mars me-1"></i> <span
                                                                id="selPatientGender">-</span></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Consulting Doctor *</label>
                                        <select class="form-select" id="doctorSelect" name="admitting_doctor_id" required>
                                            <option value="">-- Select a patient first --</option>
                                        </select>
                                        <small class="text-muted"><i class="fas fa-magic me-1 text-primary"></i>Doctor is auto-filled when you select a patient above</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 2: Stay & Bed Details -->
                        <div class="wiz-step" id="wizStep2">
                            <div class="wiz-card mb-3">
                                <h6 class="text-primary mb-4 border-0 pb-0"><i class="fas fa-bed me-2"></i>Bed
                                    Allocation</h6>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label small">Floor No.</label>
                                        <select class="form-select form-select-sm" id="selFloorNumber"
                                            onchange="onFloorNumberChange()">
                                            <option value="">-- No. --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Floor Name</label>
                                        <select class="form-select form-select-sm" id="selFloorName" disabled
                                            onchange="onFloorNameChange()">
                                            <option value="">-- Name --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Ward</label>
                                        <select class="form-select form-select-sm" id="selWardName" disabled
                                            onchange="onWardNameChange()">
                                            <option value="">-- Ward --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Type</label>
                                        <select class="form-select form-select-sm" id="selWardType" disabled
                                            onchange="onWardTypeChange()">
                                            <option value="">-- Type --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Room Allocation</label>
                                        <select class="form-select" id="selRoomNumber" disabled
                                            onchange="onRoomNumberChange()">
                                            <option value="">-- Select Room --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Bed Assignment *</label>
                                        <select class="form-select" id="bedSelect" name="bed_id" required disabled
                                            onchange="showBedDetails(this.value)">
                                            <option value="">-- Select Bed --</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Bed Summary -->
                            <div id="bedDetailCard" style="display:none;" class="mb-3">
                                <div class="alert alert-success border-0 small py-3 px-4 shadow-sm mb-0 rounded-4">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-check-circle text-success me-2 fs-5"></i>
                                        <h6 class="mb-0 text-success">Bed Selection Verified</h6>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <span class="text-muted d-block small">Location</span>
                                            <strong>Floor <span id="bdFloorNo"></span> (<span
                                                    id="bdFloorName"></span>)</strong>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="text-muted d-block small">Ward Details</span>
                                            <strong><span id="bdWardName"></span> (<span
                                                    id="bdWardType"></span>)</strong>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="text-muted d-block small">Room & Bed</span>
                                            <strong>Room <span id="bdRoomNo"></span> (<span
                                                    id="bdRoomName"></span>)</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="wiz-card">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Admission Type</label>
                                        <select class="form-select" name="admission_type">
                                            <option value="Planned">Planned</option>
                                            <option value="Emergency" selected>Emergency</option>
                                            <option value="Transfer">Transfer</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Date</label>
                                        <input type="date" class="form-control" id="admissionDate" name="admission_date"
                                            required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Time</label>
                                        <input type="time" class="form-control" id="admissionTime" name="admission_time"
                                            required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 3: Medical & Contact -->
                        <div class="wiz-step" id="wizStep3">
                            <div class="wiz-card mb-3">
                                <h6 class="text-primary mb-4 border-0 pb-0"><i
                                        class="fas fa-notes-medical me-2"></i>Medical Notes</h6>
                                <div class="mb-3">
                                    <label class="form-label">Chief Complaint</label>
                                    <textarea class="form-control" name="chief_complaint" rows="2"
                                        placeholder="Primary symptoms..."></textarea>
                                </div>
                                <div>
                                    <label class="form-label">Preliminary Diagnosis</label>
                                    <textarea class="form-control" name="diagnosis" rows="2"
                                        placeholder="Initial findings..."></textarea>
                                </div>
                            </div>
                            <div class="wiz-card">
                                <h6 class="text-primary mb-4 border-0 pb-0"><i
                                        class="fas fa-hospital-user me-2"></i>Emergency Contact</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Contact Person</label>
                                        <input type="text" class="form-control" name="emergency_contact_name"
                                            placeholder="Full Name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Mobile Number</label>
                                        <input type="tel" class="form-control" name="emergency_contact_phone"
                                            placeholder="10 Digits" pattern="[0-9]{10}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" id="wizBtnPrev" style="display:none;"
                        onclick="handleWizard('prev')">
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>
                    <button type="button" class="btn btn-primary" id="wizBtnNext" onclick="handleWizard('next')">
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                    <button type="button" class="btn btn-success" id="wizBtnSave" style="display:none;"
                        onclick="saveAdmission()">
                        Complete Admission <i class="fas fa-check-double"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Admission Modal -->
    <div class="modal fade" id="editAdmissionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Admission</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editAdmissionForm">
                        <input type="hidden" id="editAdmissionId" name="admission_id">
                        <input type="hidden" id="editSlNo" name="sl_no">

                        <div class="row">
                            <!-- Patient & Doctor Information -->
                            <div class="col-12 mb-3">
                                <h6 class="text-primary mb-3"><i class="fas fa-user-injured"></i> Patient & Doctor
                                    Information</h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-user"></i> Patient *</label>
                                <div class="select2-spacing">
                                    <select class="form-select" id="editPatientSelect" name="patient_id"
                                        required></select>
                                    <small class="text-muted d-block mt-1">Search by name or patient ID</small>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-user-md"></i> Admitting Doctor *</label>
                                <div class="select2-spacing">
                                    <select class="form-select" id="editDoctorSelect" name="admitting_doctor_id"
                                        required></select>
                                </div>
                            </div>

                            <!-- Bed & Admission Details -->
                            <div class="col-12 mb-3 mt-2">
                                <h6 class="text-primary mb-3"><i class="fas fa-bed"></i> Bed & Admission Details</h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-bed"></i> Bed Assignment *</label>
                                <select class="form-select" id="editBedSelect" name="bed_id" required></select>
                                <small class="text-muted">Available beds and current bed shown</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-calendar-check"></i> Admission Type *</label>
                                <select class="form-select" id="editAdmissionType" name="admission_type">
                                    <option value="Planned">Planned</option>
                                    <option value="Emergency">Emergency</option>
                                    <option value="Transfer">Transfer</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><i class="fas fa-calendar"></i> Admission Date *</label>
                                <input type="date" class="form-control" id="editAdmissionDate" name="admission_date"
                                    required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><i class="fas fa-clock"></i> Admission Time *</label>
                                <input type="time" class="form-control" id="editAdmissionTime" name="admission_time"
                                    required>
                            </div>


                            <!-- Medical Information -->
                            <div class="col-12 mb-3 mt-2">
                                <h6 class="text-primary mb-3"><i class="fas fa-notes-medical"></i> Medical Information
                                </h6>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label"><i class="fas fa-stethoscope"></i> Chief Complaint</label>
                                <textarea class="form-control" id="editChiefComplaint" name="chief_complaint"
                                    rows="2"></textarea>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label"><i class="fas fa-diagnoses"></i> Preliminary Diagnosis</label>
                                <textarea class="form-control" id="editDiagnosis" name="diagnosis" rows="2"></textarea>
                            </div>

                            <!-- Emergency Contact -->
                            <div class="col-12 mb-3 mt-2">
                                <h6 class="text-primary mb-3"><i class="fas fa-phone-alt"></i> Emergency Contact</h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-user-friends"></i> Contact Name</label>
                                <input type="text" class="form-control" id="editEmergencyName"
                                    name="emergency_contact_name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-mobile-alt"></i> Contact Phone</label>
                                <input type="tel" class="form-control" id="editEmergencyPhone"
                                    name="emergency_contact_phone" pattern="[0-9]{10}">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateAdmission()">Update Admission</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="../../public/assets/js/ipd_main.js"></script>

    <script>
        let admissionsTable;

        $(document).ready(function () {
            // Initialize DataTable
            admissionsTable = $('#admissionsTable').DataTable({
                ajax: {
                    url: IPD.API_BASE + '/admissions',
                    dataSrc: 'data.admissions'
                },
                columns: [
                    { data: 'admission_id' },
                    { data: 'patient_id' },
                    { data: 'patient_name' },
                    { data: 'patient_contact' },
                    { data: 'doctor_name' },
                    { data: 'bed_number' },
                    { data: 'admission_date', render: (data) => IPD.formatDateTime(data) },
                    { data: 'admission_time', render: (data) => data ? IPD.formatTime(data) : '-' },
                    { data: 'days_admitted' },
                    {
                        data: 'status',
                        render: (data) => `<span class="admission-status ${data.toLowerCase()}">${data}</span>`
                    },
                    {
                        data: null,
                        orderable: false,
                        className: 'text-center',
                        render: (data) => `
                            <div class="dropdown">
                                <button class="btn btn-action dropdown-toggle show-on-hover" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm animated fadeIn">
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="viewAdmission('${data.admission_id}')">
                                        <i class="fas fa-eye text-info me-3"></i>View Details
                                    </a></li>
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="editAdmission('${data.admission_id}')">
                                        <i class="fas fa-edit text-primary me-3"></i>Edit Admission
                                    </a></li>
                                    ${data.status === 'Admitted' ? `
                                        <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="dischargePatient('${data.admission_id}')">
                                            <i class="fas fa-sign-out-alt text-warning me-3"></i>Discharge Patient
                                        </a></li>
                                    ` : ''}
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item py-2 text-danger" href="javascript:void(0)" onclick="deleteAdmission('${data.admission_id}')">
                                        <i class="fas fa-trash me-3"></i>Delete Record
                                    </a></li>
                                </ul>
                            </div>
                        `
                    }
                ],
                order: [[0, 'desc']]
            });

            // ── Admission Wizard Logic ──────────────────────────────────────
            let currentStep = 1;

            window.handleWizard = function (direction) {
                if (direction === 'next') {
                    if (!validateStep(currentStep)) return;
                    currentStep++;
                } else {
                    currentStep--;
                }
                updateWizardUI();
            };

            function validateStep(step) {
                if (step === 1) {
                    const patient = $('#patientSelect').val();
                    const doctor = $('#doctorSelect').val();
                    if (!patient || !doctor) {
                        IPD.toast('Please select both Patient and Doctor', 'warning');
                        return false;
                    }
                }
                if (step === 2) {
                    const bed = $('#bedSelect').val();
                    const date = $('#admissionDate').val();
                    const time = $('#admissionTime').val();
                    if (!bed || !date || !time) {
                        IPD.toast('Please complete Bed and Admission details', 'warning');
                        return false;
                    }
                }
                return true;
            }

            function updateWizardUI() {
                // Toggle steps
                $('.wiz-step').removeClass('active');
                $(`#wizStep${currentStep}`).addClass('active');

                // Update stepper items
                $('.step-item').each(function () {
                    const stepNum = parseInt($(this).data('step'));
                    $(this).removeClass('active completed');
                    if (stepNum === currentStep) {
                        $(this).addClass('active');
                    } else if (stepNum < currentStep) {
                        $(this).addClass('completed');
                    }
                });

                // Update footer buttons
                $('#wizBtnPrev').toggle(currentStep > 1);
                $('#wizBtnNext').toggle(currentStep < 3);
                $('#wizBtnSave').toggle(currentStep === 3);
            }

            // ── Custom Appointment-based Selection Logic ──────────────────────
            let patientSearchTimeout;

            $('#patientSearchInput').on('input', function () {
                const query = $(this).val().trim();
                clearTimeout(patientSearchTimeout);

                if (query.length < 2) {
                    $('#patientSearchResults').hide().empty();
                    return;
                }

                patientSearchTimeout = setTimeout(() => {
                    IPD.ajax(`dashboard/appointments?search=${encodeURIComponent(query)}&limit=10`, 'GET')
                        .then(response => {
                            renderPatientResults(response.data.appointments);
                        })
                        .catch(() => {
                            $('#patientSearchResults').hide();
                        });
                }, 400);
            });

            function renderPatientResults(appointments) {
                const $results = $('#patientSearchResults');
                if (!appointments || appointments.length === 0) {
                    $results.html('<div class="p-3 text-muted small text-center">No recent appointments found</div>').show();
                    return;
                }

                let html = '';
                appointments.forEach(apt => {
                    const displayPhone = apt.phone || 'N/A';
                    const doctorDisplay = apt.doctor_name || 'N/A';
                    html += `
                        <div class="search-result-item" onclick="selectPatientAppointment(${JSON.stringify(apt).replace(/"/g, '&quot;')})">
                            <span class="p-name">${apt.patient_name}</span>
                            <div class="p-meta">
                                <span class="me-2"><i class="fas fa-phone-alt me-1"></i>${displayPhone}</span>
                                <span class="d-block mt-1 text-primary fw-semibold"><i class="fas fa-user-md me-1"></i>Dr. ${doctorDisplay}</span>
                            </div>
                        </div>
                    `;
                });
                $results.html(html).show();
            }

            window.selectPatientAppointment = function (apt) {
                // Set hidden patient_id field
                $('#patientSelect').val(apt.patient_id || '');
                // Hide search input, show selected name
                $('#patientSearchInput').val(apt.patient_name).parent().hide();
                $('#patientSearchResults').hide();

                // Show selection summary card
                $('#selPatientName').text(apt.patient_name || '-');
                $('#selPatientID').text(apt.patient_id || '-');
                $('#selPatientPhone').text(apt.phone || 'N/A');
                $('#selPatientGender').text('-');
                $('#patientSelectedSummary').show();

                // Auto-populate Consulting Doctor from appointment record
                const doctorName = apt.doctor_name || '';
                const doctorId   = apt.doctor_id   || '';

                if (doctorName) {
                    // Populate the plain <select> directly (no Select2 in Add modal)
                    const $doc = $('#doctorSelect');
                    $doc.empty();
                    $doc.append(
                        $('<option>', { value: doctorId, text: doctorName, selected: true })
                    );
                    IPD.toast('Doctor "' + doctorName + '" auto-selected from appointment', 'success');
                } else {
                    $('#doctorSelect').empty().append('<option value="">-- No doctor found --</option>');
                    IPD.toast('No doctor linked to this appointment', 'warning');
                }
            };

            window.clearPatientSelection = function () {
                $('#patientSelect').val('');
                $('#patientSearchInput').val('').parent().show();
                $('#patientSelectedSummary').hide();
                // Reset doctor dropdown back to placeholder
                $('#doctorSelect').empty().append('<option value="">-- Select a patient first --</option>');
            };

            // Hide results on click outside
            $(document).on('click', function (e) {
                if (!$(e.target).closest('#wizStep1').length) {
                    $('#patientSearchResults').hide();
                }
            });

            // Initialize shown.bs.modal
            $('#addAdmissionModal').on('shown.bs.modal', function () {
                // Reset Wizard
                currentStep = 1;
                updateWizardUI();

                // Reset Search
                clearPatientSelection();

                // Set current date (Robust Local Time)
                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                const localDate = `${year}-${month}-${day}`;
                document.getElementById('admissionDate').value = localDate;

                // Set current time
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                document.getElementById('admissionTime').value = `${hours}:${minutes}`;

                // Reset doctor dropdown — doctor is auto-filled from patient appointment selection
                $('#doctorSelect').empty().append('<option value="">-- Select a patient first --</option>');
            });

            function fetchLatestDoctor(patientId) {
                IPD.ajax('admissions?action=get_latest_doctor&patient_id=' + patientId, 'GET')
                    .then(response => {
                        if (response.data) {
                            const doctor = response.data;
                            // Create a DOM Option and pre-select it
                            const option = new Option(doctor.doctor_name, doctor.doctor_id, true, true);
                            $('#doctorSelect').append(option).trigger('change');

                            // Manually trigger the select2:select event if needed for other handlers
                            $('#doctorSelect').trigger({
                                type: 'select2:select',
                                params: {
                                    data: {
                                        id: doctor.doctor_id,
                                        text: doctor.doctor_name,
                                        data: doctor
                                    }
                                }
                            });

                            Toastify({
                                text: "Doctor details auto-fetched",
                                duration: 3000,
                                gravity: "top",
                                position: "right",
                                style: { background: "#10b981" }
                            }).showToast();
                        }
                    })
                    .catch(error => {
                        console.log('No recent doctor found or error fetching doctor');
                    });
            }

            // Load available beds
            loadAvailableBeds();

            // Filter handlers
            $('#filterStatus').change(function () {
                const status = $(this).val();
                const search = $('#searchBox').val();
                admissionsTable.ajax.url(IPD.API_BASE + '/admissions?status=' + status + '&search=' + search).load();
            });

            // Search with debouncing for better performance
            let searchTimeout;
            $('#searchBox').on('keyup', function () {
                const searchValue = $(this).val();
                const status = $('#filterStatus').val();

                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function () {
                    admissionsTable.ajax.url(IPD.API_BASE + '/admissions?status=' + status + '&search=' + searchValue).load();
                }, 500); // Wait 500ms after user stops typing
            });


            // Check for admission_id in URL
            const urlParams = new URLSearchParams(window.location.search);
            const admId = urlParams.get('admission_id');
            if (admId) {
                viewAdmission(admId);
            }
        });

        // ── Bed Cascading Dropdowns ──────────────────────────────────────────
        let allAvailableBeds = [];

        function loadAvailableBeds() {
            IPD.ajax('beds?available=1', 'GET').then(response => {
                allAvailableBeds = response.data;
                // Step 1: Populate Floor Number
                const floorNums = [...new Map(allAvailableBeds.map(b => [b.floor_number, b])).values()]
                    .sort((a, b) => a.floor_number - b.floor_number);
                const sel = document.getElementById('selFloorNumber');
                sel.innerHTML = '<option value="">-- Select Floor No. --</option>';
                floorNums.forEach(b => {
                    sel.innerHTML += `<option value="${b.floor_number}">${b.floor_number}</option>`;
                });
                resetFrom('selFloorName');
            });
        }

        // Helper: reset a dropdown and all downstream ones
        function resetFrom(startId) {
            const order = ['selFloorName', 'selWardName', 'selWardType', 'selRoomNumber', 'bedSelect'];
            const placeholders = {
                selFloorName: '-- Select Floor No. first --',
                selWardName:  '-- Select Floor first --',
                selWardType:  '-- Select Ward first --',
                selRoomNumber:'-- Select Ward Type first --',
                bedSelect:    '-- Select Room first --'
            };
            let found = false;
            order.forEach(id => {
                if (id === startId) found = true;
                if (found) {
                    const el = document.getElementById(id);
                    if (el) {
                        el.innerHTML = `<option value="">${placeholders[id]}</option>`;
                        el.disabled = true;
                    }
                }
            });
            document.getElementById('bedDetailCard').style.display = 'none';
        }

        function getFiltered(field, value) {
            return allAvailableBeds.filter(b => String(b[field]) === String(value));
        }

        function onFloorNumberChange() {
            const val = document.getElementById('selFloorNumber').value;
            resetFrom('selFloorName');
            if (!val) return;
            // Floor Name: unique floor names for this floor number
            const names = [...new Map(getFiltered('floor_number', val).map(b => [b.floor_name, b])).values()];
            const sel = document.getElementById('selFloorName');
            sel.innerHTML = '<option value="">-- Select Floor Name --</option>';
            names.forEach(b => sel.innerHTML += `<option value="${b.floor_name}">${b.floor_name}</option>`);
            sel.disabled = false;
            // If only one option, auto-select it
            if (names.length === 1) { sel.value = names[0].floor_name; onFloorNameChange(); }
        }

        function onFloorNameChange() {
            const floorNo = document.getElementById('selFloorNumber').value;
            const floorName = document.getElementById('selFloorName').value;
            resetFrom('selWardName');
            if (!floorName) return;
            const filtered = allAvailableBeds.filter(b =>
                String(b.floor_number) === floorNo && b.floor_name === floorName);
            const wards = [...new Set(filtered.map(b => b.ward_name))].sort();
            const sel = document.getElementById('selWardName');
            sel.innerHTML = '<option value="">-- Select Ward Name --</option>';
            wards.forEach(w => sel.innerHTML += `<option value="${w}">${w}</option>`);
            sel.disabled = false;
            if (wards.length === 1) { sel.value = wards[0]; onWardNameChange(); }
        }

        function onWardNameChange() {
            const floorNo = document.getElementById('selFloorNumber').value;
            const floorName = document.getElementById('selFloorName').value;
            const wardName = document.getElementById('selWardName').value;
            resetFrom('selWardType');
            if (!wardName) return;
            const filtered = allAvailableBeds.filter(b =>
                String(b.floor_number) === floorNo && b.floor_name === floorName && b.ward_name === wardName);
            const types = [...new Set(filtered.map(b => b.ward_type))].sort();
            const sel = document.getElementById('selWardType');
            sel.innerHTML = '<option value="">-- Select Ward Type --</option>';
            types.forEach(t => sel.innerHTML += `<option value="${t}">${t}</option>`);
            sel.disabled = false;
            if (types.length === 1) { sel.value = types[0]; onWardTypeChange(); }
        }

        function onWardTypeChange() {
            const floorNo = document.getElementById('selFloorNumber').value;
            const floorName = document.getElementById('selFloorName').value;
            const wardName = document.getElementById('selWardName').value;
            const wardType = document.getElementById('selWardType').value;
            resetFrom('selRoomNumber');
            if (!wardType) return;
            const filtered = allAvailableBeds.filter(b =>
                String(b.floor_number) === floorNo && b.floor_name === floorName &&
                b.ward_name === wardName && b.ward_type === wardType);
            const rooms = [...new Map(filtered.map(b => [b.room_number, b])).values()]
                .sort((a, b) => a.room_number.localeCompare(b.room_number));
            const sel = document.getElementById('selRoomNumber');
            sel.innerHTML = '<option value="">-- Select Room Number --</option>';
            rooms.forEach(r => sel.innerHTML += `<option value="${r.room_number}">${r.room_number}</option>`);
            sel.disabled = false;
            if (rooms.length === 1) { sel.value = rooms[0].room_number; onRoomNumberChange(); }
        }

        function onRoomNumberChange() {
            const floorNo    = document.getElementById('selFloorNumber').value;
            const floorName  = document.getElementById('selFloorName').value;
            const wardName   = document.getElementById('selWardName').value;
            const wardType   = document.getElementById('selWardType').value;
            const roomNumber = document.getElementById('selRoomNumber').value;

            // Reset bed select
            const bedSel = document.getElementById('bedSelect');
            bedSel.innerHTML = '<option value="">-- Select Bed --</option>';
            bedSel.disabled = true;
            document.getElementById('bedDetailCard').style.display = 'none';

            if (!roomNumber) return;

            // Filter beds matching all selected criteria
            const filtered = allAvailableBeds.filter(b =>
                String(b.floor_number) === floorNo &&
                b.floor_name  === floorName &&
                b.ward_name   === wardName &&
                b.ward_type   === wardType &&
                b.room_number === roomNumber
            );

            filtered.forEach(bed => {
                const opt = document.createElement('option');
                opt.value = bed.bed_id;
                opt.textContent = `Bed ${bed.bed_number}` + (bed.room_name ? ` (${bed.room_name})` : '');
                opt.dataset.bed = JSON.stringify(bed);
                bedSel.appendChild(opt);
            });

            bedSel.disabled = false;

            // Auto-select if only one bed
            if (filtered.length === 1) {
                bedSel.value = filtered[0].bed_id;
                showBedDetails(filtered[0].bed_id);
            }
        }

        function showBedDetails(bedId) {
            if (!bedId) { document.getElementById('bedDetailCard').style.display = 'none'; return; }
            const opt = document.querySelector(`#bedSelect option[value="${bedId}"]`);
            if (!opt || !opt.dataset.bed) { document.getElementById('bedDetailCard').style.display = 'none'; return; }
            const bed = JSON.parse(opt.dataset.bed);
            document.getElementById('bdFloorNo').textContent = bed.floor_number || '-';
            document.getElementById('bdFloorName').textContent = bed.floor_name || '-';
            document.getElementById('bdWardName').textContent = bed.ward_name || '-';
            document.getElementById('bdWardType').textContent = bed.ward_type || '-';
            document.getElementById('bdRoomNo').textContent = bed.room_number || '-';
            document.getElementById('bdRoomName').textContent = bed.room_name || '-';
            document.getElementById('bedDetailCard').style.display = 'block';
        }


        function saveAdmission() {
            const formData = {};
            $('#addAdmissionForm').serializeArray().forEach(field => {
                formData[field.name] = field.value;
            });

            IPD.ajax('admissions', 'POST', formData)
                .then(response => {
                    IPD.toast('Admission created successfully!', 'success');
                    $('#addAdmissionModal').modal('hide');
                    $('#addAdmissionForm')[0].reset();
                    admissionsTable.ajax.reload();
                })
                .catch(error => {
                    IPD.toast(error.message || 'Failed to create admission', 'error');
                });
        }

        function viewAdmission(id) {
            // Fetch admission details via API and show in modal
            IPD.ajax(`admissions?id=${id}`, 'GET')
                .then(response => {
                    const admission = response.data;

                    // Create modal content
                    const modalContent = `
                        <div class="modal fade" id="viewAdmissionModal" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Admission Details - ${admission.admission_id}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Patient Information</h6>
                                                <p><strong>Name:</strong> ${admission.patient_name}</p>
                                                <p><strong>Age:</strong> ${admission.patient_age}</p>
                                                <p><strong>Gender:</strong> ${admission.patient_gender}</p>
                                                <p><strong>Contact:</strong> ${admission.patient_contact}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Admission Information</h6>
                                                <p><strong>Doctor:</strong> ${admission.doctor_name}</p>
                                                <p><strong>Bed:</strong> ${admission.bed_number} (${admission.ward_name})</p>
                                                <p><strong>Location:</strong> Floor: ${admission.floor_name || '-'}, Room: ${admission.room_name || admission.room_no || '-'}</p>
                                                <p><strong>Ward Type:</strong> ${admission.ward_type || '-'}</p>
                                                <p><strong>Admission Date:</strong> ${IPD.formatDate(admission.admission_date)}</p>
                                                <p><strong>Days Admitted:</strong> ${admission.days_admitted}</p>
                                                <p><strong>Status:</strong> <span class="badge bg-${admission.status === 'Admitted' ? 'success' : 'secondary'}">${admission.status}</span></p>
                                            </div>
                                        </div>
                                        ${admission.financials ? `
                                            <hr>
                                            <h6>Financial Summary</h6>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <p><strong>Total Charges:</strong> ${IPD.formatCurrency(admission.financials.total_charges)}</p>
                                                </div>
                                                <div class="col-md-4">
                                                    <p><strong>Total Payments:</strong> ${IPD.formatCurrency(admission.financials.total_payments)}</p>
                                                </div>
                                                <div class="col-md-4">
                                                    <p><strong>Balance Due:</strong> <span class="text-${admission.financials.balance_due > 0 ? 'danger' : 'success'}">${IPD.formatCurrency(admission.financials.balance_due)}</span></p>
                                                </div>
                                            </div>
                                        ` : ''}
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    // Remove existing modal if any
                    $('#viewAdmissionModal').remove();

                    // Add modal to body and show
                    $('body').append(modalContent);
                    const modal = new bootstrap.Modal(document.getElementById('viewAdmissionModal'));
                    modal.show();

                    // Clean up modal after it's hidden
                    $('#viewAdmissionModal').on('hidden.bs.modal', function () {
                        $(this).remove();
                    });
                })
                .catch(error => {
                    IPD.toast(error.message || 'Failed to load admission details', 'error');
                });
        }


        function dischargePatient(id) {
            IPD.confirm('Are you sure you want to discharge this patient?', () => {
                // Get current date and time
                const now = new Date();
                const today = now.toISOString().split('T')[0];  // YYYY-MM-DD
                const currentTime = now.toTimeString().split(' ')[0];  // HH:MM:SS

                IPD.ajax('admissions?action=discharge', 'POST', {
                    admission_id: id,
                    discharge_date: today,
                    discharge_time: currentTime
                })
                    .then(response => {
                        IPD.toast('Patient discharged successfully!', 'success');
                        admissionsTable.ajax.reload();
                    })
                    .catch(error => {
                        IPD.toast(error.message || 'Failed to discharge patient', 'error');
                    });
            });
        }

        function deleteAdmission(id) {
            IPD.confirm('Are you sure you want to delete this admission?', () => {
                IPD.ajax('admissions?id=' + id, 'DELETE')
                    .then(response => {
                        IPD.toast('Admission deleted successfully!', 'success');
                        admissionsTable.ajax.reload();
                    })
                    .catch(error => {
                        IPD.toast(error.message || 'Failed to delete admission', 'error');
                    });
            });
        }

        function editAdmission(id) {
            // Fetch admission details
            IPD.ajax(`admissions?id=${id}`, 'GET')
                .then(response => {
                    const admission = response.data;

                    // Populate hidden fields
                    $('#editAdmissionId').val(admission.admission_id);
                    $('#editSlNo').val(admission.sl_no);

                    // Initialize Select2 for edit modal
                    IPD.initPatientSearch('#editPatientSelect', '#editAdmissionModal');
                    IPD.initDoctorSearch('#editDoctorSelect', '#editAdmissionModal');

                    // Pre-populate patient
                    const patientOption = new Option(admission.patient_name, admission.patient_id, true, true);
                    $('#editPatientSelect').append(patientOption).trigger('change');

                    // Pre-populate doctor
                    const doctorOption = new Option(admission.doctor_name, admission.admitting_doctor_id, true, true);
                    $('#editDoctorSelect').append(doctorOption).trigger('change');

                    // Load beds (available + current bed) - pass current bed info
                    loadBedsForEdit(admission.bed_id, {
                        bed_number: admission.bed_number,
                        ward_name: admission.ward_name,
                        room_category: admission.room_category || admission.room_type
                    });

                    // Populate form fields
                    $('#editAdmissionType').val(admission.admission_type || 'Emergency');
                    $('#editAdmissionDate').val(admission.admission_date);
                    $('#editAdmissionTime').val(admission.admission_time || '');
                    $('#editChiefComplaint').val(admission.chief_complaint || '');
                    $('#editDiagnosis').val(admission.diagnosis || '');
                    $('#editEmergencyName').val(admission.emergency_contact_name || '');
                    $('#editEmergencyPhone').val(admission.emergency_contact_phone || '');

                    // Show modal
                    $('#editAdmissionModal').modal('show');
                })
                .catch(error => {
                    IPD.toast(error.message || 'Failed to load admission details', 'error');
                });
        }


        function loadBedsForEdit(currentBedId, currentBedInfo) {
            IPD.ajax('beds?available=1', 'GET')
                .then(response => {
                    const select = $('#editBedSelect');
                    select.empty();

                    // Check if current bed is in available list
                    const currentBedInList = response.data.find(bed => bed.bed_id == currentBedId);

                    if (currentBedInList) {
                        // Current bed is available, add all beds including current
                        response.data.forEach(bed => {
                            const isSelected = bed.bed_id == currentBedId;
                            const label = `${bed.bed_number} - ${bed.ward_name} (${bed.room_category || bed.room_type})${isSelected ? ' - Current' : ''}`;
                            select.append(`<option value="${bed.bed_id}" ${isSelected ? 'selected' : ''}>${label}</option>`);
                        });
                    } else {
                        // Current bed is occupied, add it first with current bed info
                        if (currentBedInfo && currentBedInfo.bed_number) {
                            const currentLabel = `${currentBedInfo.bed_number} - ${currentBedInfo.ward_name} (${currentBedInfo.room_category || currentBedInfo.room_type}) - Current`;
                            select.append(`<option value="${currentBedId}" selected>${currentLabel}</option>`);
                        } else {
                            // Fallback if bed info not available
                            select.append(`<option value="${currentBedId}" selected>Current Bed (ID: ${currentBedId})</option>`);
                        }

                        // Add available beds
                        response.data.forEach(bed => {
                            select.append(`<option value="${bed.bed_id}">${bed.bed_number} - ${bed.ward_name} (${bed.room_category || bed.room_type})</option>`);
                        });
                    }
                })
                .catch(error => {
                    console.error('Failed to load beds:', error);
                    // Fallback: just show current bed
                    const select = $('#editBedSelect');
                    select.empty();
                    if (currentBedInfo && currentBedInfo.bed_number) {
                        const currentLabel = `${currentBedInfo.bed_number} - ${currentBedInfo.ward_name} (${currentBedInfo.room_category || currentBedInfo.room_type}) - Current`;
                        select.append(`<option value="${currentBedId}" selected>${currentLabel}</option>`);
                    } else {
                        select.append(`<option value="${currentBedId}" selected>Current Bed (ID: ${currentBedId})</option>`);
                    }
                });
        }

        function updateAdmission() {
            const admissionId = $('#editAdmissionId').val();
            const formData = {};

            $('#editAdmissionForm').serializeArray().forEach(field => {
                if (field.name !== 'admission_id' && field.name !== 'sl_no') {
                    formData[field.name] = field.value;
                }
            });

            IPD.ajax(`admissions?id=${admissionId}`, 'PUT', formData)
                .then(response => {
                    IPD.toast('Admission updated successfully!', 'success');
                    $('#editAdmissionModal').modal('hide');
                    admissionsTable.ajax.reload();
                })
                .catch(error => {
                    IPD.toast(error.message || 'Failed to update admission', 'error');
                });
        }
    </script>
</body>

</html>