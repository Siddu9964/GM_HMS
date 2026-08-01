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
    <link rel="stylesheet" href="/GM_HMS/reception_view/assets/css/opd_billing.css">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    <!-- Reception Dashboard CSS -->
    <link rel="stylesheet" href="../../../assets/css/reception_dashboard.css">

    <!-- Patient Module CSS (for ref-modal styles) -->
    <link rel="stylesheet" href="../../../assets/css/patient.css">

    <!-- Flatpickr for Date/Time UI -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

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
            padding: 6px 16px;
            border-radius: 100px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .admission-status.admitted {
            background-color: #f3efe6;
            color: #1f6b4a;
            border: 1px solid rgba(31, 107, 74, 0.2);
            box-shadow: 0 2px 10px rgba(31, 107, 74, 0.1);
        }

        .admission-status.discharged {
            background-color: #f8fafc;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }
/* Table Enhancement - Advanced View */
        .table-container {
            background: transparent;
            padding: 10px;
        }

        .table-header h2 {
            color: #1f6b4a;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .table-header .btn-primary {
            background: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%);
            border: 1px solid #d4af37;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
            font-weight: 600;
            border-radius: 20px;
            padding: 8px 24px;
            color: #fff;
            transition: all 0.3s ease;
        }
        .table-header .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.5);
        }

        #admissionsTable {
            border-collapse: separate;
            border-spacing: 0 10px;
            margin-top: 15px;
        }

        #admissionsTable thead th {
            border: none;
            padding: 1.2rem 1rem;
            text-transform: capitalize;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            color: #ffffff;
            font-weight: 600;
            background: #1f6b4a;
        }

        #admissionsTable thead th:first-child { border-top-left-radius: 8px; border-bottom-left-radius: 8px; }
        #admissionsTable thead th:last-child { border-top-right-radius: 8px; border-bottom-right-radius: 8px; }

        #admissionsTable tbody tr {
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            border-radius: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
        }

        #admissionsTable tbody tr td:first-child {
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }

        #admissionsTable tbody tr td:last-child {
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        #admissionsTable tbody tr:hover {
            transform: translateY(-2px) scale(1.005);
            box-shadow: 0 12px 25px rgba(212, 175, 55, 0.15);
            outline: 2px solid #d4af37;
            outline-offset: -2px;
            z-index: 10;
            position: relative;
        }

        #admissionsTable td {
            padding: 1rem 1rem;
            vertical-align: middle;
            border: none;
            color: #333;
            font-weight: 500;
            font-size: 0.9rem;
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
        
        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable,
        .select2-container--default .select2-results__option--highlighted[aria-selected],
        .select2-container--default .select2-results__option--highlighted[data-selected] {
            background-color: #1f6b4a !important;
            color: #f3efe6 !important;
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
            background: #f3efe6 !important;
            border: 1px solid #1f6b4a !important;
            border-radius: 0.75rem;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 1rem;
        }

        .patient-selection-summary h6,
        .patient-selection-summary .text-muted,
        .patient-selection-summary .badge,
        .patient-selection-summary .badge i,
        .patient-selection-summary .btn {
            color: #1f6b4a !important;
        }

        .p-avatar {
            width: 48px;
            height: 48px;
            background: #1f6b4a !important;
            color: #f3efe6 !important;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
    
        /* Exact Modal UI Styles */
        .glass-modal-overlay {
            background: rgba(243, 239, 230, 0.6) !important;
            backdrop-filter: blur(8px);
        }
        
        .glass-modal-card {
            background: linear-gradient(to bottom, rgba(225, 240, 230, 0.95) 0%, rgba(243, 239, 230, 0.98) 100%) !important;
            border-radius: 20px !important;
            border: 1px solid rgba(255, 255, 255, 0.5) !important;
            box-shadow: 0 25px 50px -12px rgba(31, 107, 74, 0.25) !important;
            padding: 2rem !important;
        }

        .glass-modal-card h2 {
            color: #1f6b4a;
            font-weight: 800;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .gold-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            overflow: hidden;
            border: 1px solid rgba(212, 175, 55, 0.2);
        }

        .gold-card-header {
            background: linear-gradient(135deg, #d4af37 0%, #f9f0d0 100%);
            padding: 12px 20px;
            font-weight: 700;
            color: #333;
            font-size: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(212, 175, 55, 0.3);
        }

        .gold-card-body {
            padding: 20px;
        }

        .material-input {
            border: none !important;
            border-bottom: 2px solid #ccc !important;
            border-radius: 0 !important;
            background: transparent !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            font-weight: 500;
            color: #333 !important;
            box-shadow: none !important;
            transition: all 0.3s ease;
        }

        .material-input:focus {
            border-bottom-color: #d4af37 !important;
        }

        .material-label {
            font-size: 0.75rem;
            color: #1f6b4a;
            font-weight: 600;
            text-transform: capitalize;
            margin-bottom: 2px;
        }
        
        .btn-register {
            background: #1f6b4a !important;
            color: white !important;
            border: 2px solid #d4af37 !important;
            border-radius: 8px !important;
            padding: 10px 30px !important;
            font-weight: 700 !important;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4) !important;
            transition: all 0.3s ease;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.6) !important;
        }

    </style>
</head>

<body>
    <div class="reception-layout" style="background-color: #f3efe6;">
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
            <div class="reception-content" style="background: transparent;">
                <!-- Page Header -->
                <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem; color: #1e293b;">
                            <i class="fas fa-hospital-user text-primary me-2"></i> IPD Admissions
                        </h1>
                        <p style="color: #64748b; font-size: 1.05rem;">Manage patient admissions and discharges</p>
                    </div>
                    <div class="d-flex gap-3">
                        <a href="/GM_HMS/reception_view/ipd_management/public/index.php" class="btn btn-light border shadow-sm px-4 py-2" style="font-weight: 600; border-radius: 10px; color: #475569;">
                            <i class="fas fa-chart-pie me-2"></i> IPD Dashboard
                        </a>
                        <a href="/GM_HMS/reception_view/index.php" class="btn btn-primary shadow-sm px-4 py-2" style="font-weight: 600; border-radius: 10px; background: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%); border: none;">
                            <i class="fas fa-home me-2"></i> Main Dashboard
                        </a>
                    </div>
                </div>

                <!-- Admissions Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h2>All Admissions</h2>
                        <button class="btn btn-primary" onclick="showAddAdmissionModal()">
                            <i class="fas fa-plus-circle"></i> New Admission
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

    
    <style>
        /* Product Configuration Style Overrides for IPD Admission Modal */
        #addAdmissionModal.ref-modal-overlay {
            overflow-y: auto !important;
            padding: 40px 0 !important;
            align-items: flex-start !important;
        }
        #addAdmissionModal .ref-modal-card {
            background-color: #f6f8f5 !important;
            border-radius: 8px !important;
            border: 1px solid #d1e0d7 !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;
            margin: auto;
            max-height: none !important;
            height: auto !important;
            overflow: visible !important;
        }
        #addAdmissionModal .ref-modal-head {
            padding: 16px 24px;
            border-bottom: 1px solid #e0e8e3;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f6f8f5;
            border-radius: 8px 8px 0 0;
        }
        #addAdmissionModal h2 {
            color: #1f6b4a !important;
            font-size: 1.1rem;
            font-weight: 800;
            margin: 0;
            font-family: 'Inter', sans-serif;
        }
        #addAdmissionModal .ref-modal-close {
            position: relative !important;
            right: auto !important;
            top: auto !important;
            color: #1f6b4a !important;
            font-size: 1.2rem !important;
            padding: 4px;
            opacity: 0.7;
        }
        #addAdmissionModal .ref-modal-close:hover {
            opacity: 1;
        }
        #addAdmissionModal .ref-modal-body {
            padding: 24px !important;
            overflow: visible !important;
            max-height: none !important;
            height: auto !important;
        }
        #addAdmissionModal .gold-card {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            height: 100%;
        }
        #addAdmissionModal .gold-card-header {
            background: transparent !important;
            padding: 0 0 12px 0 !important;
            border-bottom: none !important;
            color: #1f6b4a !important;
            font-size: 13px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        #addAdmissionModal .gold-card-header i {
            font-size: 14px;
        }
        #addAdmissionModal .material-label {
            font-size: 11px;
            font-weight: 800;
            color: #1f6b4a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            display: block;
        }
        #addAdmissionModal .material-input {
            background: #ffffff !important;
            border: 1px solid #d1e0d7 !important;
            border-radius: 6px !important;
            padding: 8px 12px !important;
            font-size: 14px !important;
            color: #333 !important;
            height: 40px !important;
            box-shadow: none !important;
        }
        #addAdmissionModal .material-input:focus {
            border-color: #1f6b4a !important;
            outline: none !important;
        }
        /* Style for the first input to match screenshot's pale green background */
        #addAdmissionModal #patientSearchInput {
            background-color: #eaf3ed !important;
        }
        #addAdmissionModal .modal-footer-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding-top: 16px;
            border-top: 1px solid #e0e8e3;
            margin-top: 24px;
        }
        #addAdmissionModal .btn-cancel-new {
            background: #ffffff;
            color: #1f6b4a;
            border: 1px solid #1f6b4a;
            padding: 8px 20px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        #addAdmissionModal .btn-cancel-new:hover {
            background: #f0f5f2;
        }
        #addAdmissionModal .btn-submit-new {
            background: #1f6b4a;
            color: #ffffff;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        #addAdmissionModal .btn-submit-new:hover {
            background: #144d34;
        }
    </style>

    <style>
        /* Bridge between GM HMS .hidden and opd_billing.css */
        #addAdmissionModal:not(.hidden) {
            display: flex !important;
        }
        #addAdmissionModal .billing-modal-card {
            height: auto !important;
            max-height: 95vh;
            display: flex;
            flex-direction: column;
            overflow: hidden !important; /* Keep card boundaries clean */
        }
        #addAdmissionModal .modal-body-scroll {
            overflow-y: auto !important;
            flex: 1;
            padding-bottom: 1rem; /* Extra padding at the bottom */
        }
        
        /* Fix for Select2 in Referral Name */
        #refNameContainer .select2-container {
            width: 100% !important;
        }
        #refNameContainer .select2-selection--single {
            min-height: 25.5px !important; /* To match other fields padding+borders */
            height: auto !important;
            border: 1px solid var(--gray-300) !important;
            border-radius: 4px !important;
            font-size: 0.75rem !important;
            display: flex;
            align-items: center;
        }
        #refNameContainer .select2-selection__rendered {
            padding: 0.2rem 0.4rem !important;
            line-height: normal !important;
            color: #333 !important;
        }
        #refNameContainer .select2-selection__arrow {
            height: 100% !important;
        }
    </style>

    <!-- Add Admission Modal (OPD Billing Terminal Style) -->
    <div id="addAdmissionModal" class="modal-overlay hidden" onclick="closeAddAdmissionModalOnBackdrop(event)">
        <div class="billing-modal-card" onclick="event.stopPropagation()">
            <div class="billing-modal-head">
                <h3><i class="fas fa-procedures"></i> New IPD Admission</h3>
                <button class="btn-close-modal" type="button" onclick="closeAddAdmissionModal()" title="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body-scroll">
                <form id="addAdmissionForm">
                    
                    <div class="modal-section-card">
                        <div class="modal-section-body" style="padding: 0.4rem;">
                            <!-- Ultra-Dense Grid with 6 Columns -->
                            <div class="form-row" style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 0.2rem 0.4rem; align-items: end;">
                                
                                <!-- SECTION 1: PATIENT & MEDICAL INFO -->
                                <div style="grid-column: 1 / -1; font-weight: 700; font-size: 0.8rem; color: var(--teal-dark); border-bottom: 1px solid var(--gray-300); padding-bottom: 0.1rem; margin-top: 0;">
                                    <i class="fas fa-user-md me-1"></i> Patient & Medical Info
                                </div>

                                <div class="form-group" style="margin-bottom: 0; grid-column: span 2;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Patient *</label>
                                    <div class="input-with-icon-inside suggestion-wrapper">
                                        <input type="text" id="patientSearchInput" placeholder="Select Patient..." style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">
                                        <div id="patientSearchResults" class="suggestion-list"></div>
                                        <input type="hidden" id="patientSelect" name="patient_id" required>
                                    </div>
                                </div>
                                <div class="form-group" style="margin-bottom: 0; grid-column: span 2;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Admitting Doctor *</label>
                                    <select id="doctorSelect" name="admitting_doctor_id" required style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem; background-color: #fff;">
                                        <option value="">Select Doctor...</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-bottom: 0; grid-column: span 1;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Chief Complaint</label>
                                    <input type="text" name="chief_complaint" placeholder="Brief complaint..." style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">
                                </div>
                                <div class="form-group" style="margin-bottom: 0; grid-column: span 1;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Diagnosis</label>
                                    <input type="text" name="diagnosis" placeholder="Diagnosis..." style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">
                                </div>
                                <div class="form-group" style="margin-bottom: 0; grid-column: span 3;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Emergency Contact Name</label>
                                    <input type="text" name="emergency_contact_name" placeholder="Contact Name" style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">
                                </div>
                                <div class="form-group" style="margin-bottom: 0; grid-column: span 3;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Emergency Contact Phone</label>
                                    <input type="text" name="emergency_contact_phone" placeholder="Phone Number" style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">
                                </div>

                                <!-- SECTION 2: BED ALLOCATION -->
                                <div style="grid-column: 1 / -1; font-weight: 700; font-size: 0.8rem; color: var(--teal-dark); border-bottom: 1px solid var(--gray-300); padding-bottom: 0.1rem; margin-top: 0.2rem;">
                                    <i class="fas fa-bed me-1"></i> Hospital Bed Allocation
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Floor No.</label>
                                    <select id="selFloorNumber" onchange="onFloorNumberChange()" style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem; background-color: #f1f5f9;">
                                        <option value="">-- Floor No --</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Floor Name</label>
                                    <select id="selFloorName" disabled onchange="onFloorNameChange()" style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem; background-color: #f1f5f9;">
                                        <option value="">-- Floor Name --</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Ward Name</label>
                                    <select id="selWardName" disabled onchange="onWardNameChange()" style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem; background-color: #f1f5f9;">
                                        <option value="">-- Ward Name --</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Room Type</label>
                                    <select id="selWardType" disabled onchange="onWardTypeChange()" style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem; background-color: #f1f5f9;">
                                        <option value="">-- Room Type --</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Room No.</label>
                                    <select id="selRoomNumber" disabled onchange="onRoomNumberChange()" style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem; background-color: #f1f5f9;">
                                        <option value="">-- Room No --</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Bed Select *</label>
                                    <select id="bedSelect" name="bed_id" required disabled onchange="showBedDetails(this.value)" style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem; background-color: #f1f5f9;">
                                        <option value="">-- Bed --</option>
                                    </select>
                                </div>

                                <!-- SECTION 3: ADMISSION & REFERRAL -->
                                <div style="grid-column: 1 / -1; font-weight: 700; font-size: 0.8rem; color: var(--teal-dark); border-bottom: 1px solid var(--gray-300); padding-bottom: 0.1rem; margin-top: 0.3rem;">
                                    <i class="fas fa-hospital-user me-1"></i> Admission & Referral
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Admission Type</label>
                                    <select name="admission_type" style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem; background-color: #fff;">
                                        <option value="Emergency">Emergency</option>
                                        <option value="OPD">OPD</option>
                                        <option value="Routine">Routine</option>
                                        <option value="Transfer">Transfer</option>
                                        <option value="Insurance">Insurance</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Admission Date *</label>
                                    <input type="date" id="admissionDate" name="admission_date" required style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Admission Time</label>
                                    <input type="time" name="admission_time" value="<?= date('H:i') ?>" style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">
                                    <script>document.querySelector('input[name="admission_time"]').value = new Date().toTimeString().slice(0,5);</script>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Referral Type</label>
                                    <select id="referralTypeSelect" name="referral_type" onchange="onReferralTypeChange()" style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem; background-color: #fff;">
                                        <option value="">None</option>
                                        <option value="Internal">Internal</option>
                                        <option value="External">External</option>
                                    </select>
                                </div>
                                <div class="form-group position-relative" style="margin-bottom: 0;" id="refNameContainer">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Referral Name</label>
                                    <input type="text" id="refNameText" name="referral_name" placeholder="Referral Name" style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">
                                    <i class="fas fa-user-plus d-none" id="btnExternalRefAdd" onclick="toggleExternalRefCard()" style="position: absolute; right: 25px; bottom: 6px; cursor: pointer; color: var(--teal-dark); font-size: 0.8rem; z-index: 10;" title="Add External Referral Details"></i>
                                    <select id="refNameSelect" class="d-none" style="width: 100%;"><option value="">Select Doctor...</option></select>

                                    <!-- External Referral Popup Card -->
                                    <div id="externalRefCard" class="d-none" style="position: absolute; top: 100%; left: 0; width: 220px; background: #fff; border: 1px solid var(--gray-300); border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 1050; padding: 0.6rem; margin-top: 0.3rem;">
                                        <div style="font-size: 0.7rem; font-weight: 700; color: var(--teal-dark); margin-bottom: 0.4rem;">Add External Referral</div>
                                        <input type="text" id="extRefName" placeholder="Name" style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.7rem; margin-bottom: 0.3rem;">
                                        <input type="text" id="extRefPhone" placeholder="Phone Number" style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.7rem; margin-bottom: 0.4rem;">
                                        <div class="d-flex gap-1 justify-content-end">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" style="font-size: 0.65rem; padding: 0.1rem 0.4rem; border-radius: 4px;" onclick="toggleExternalRefCard()">Cancel</button>
                                            <button type="button" class="btn btn-sm btn-success" style="font-size: 0.65rem; padding: 0.1rem 0.4rem; border-radius: 4px;" onclick="saveExternalRef()">Add</button>
                                        </div>
                                    </div>
                                </div>
                                <div style="grid-column: span 1;"></div>

                                <!-- SECTION 4: BILLING & CHARGES -->
                                <div style="grid-column: 1 / -1; font-weight: 700; font-size: 0.8rem; color: var(--teal-dark); border-bottom: 1px solid var(--gray-300); padding-bottom: 0.1rem; margin-top: 0.2rem;">
                                    <i class="fas fa-tags me-1"></i> Billing & Charges
                                </div>

                                <!-- ROW 1: Charges -->
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Amount/Day</label>
                                    <input type="number" id="bdAmountPerDay" name="amount_per_day" value="0" readonly style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem; background: #f1f5f9;">
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Nursing Chg</label>
                                    <input type="number" id="bdNursingCharge" name="nursig_charge" value="0" readonly style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem; background: #f1f5f9;">
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Doctor Chg</label>
                                    <input type="number" id="bdDoctorCharge" name="doctor_charge" value="0" readonly style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem; background: #f1f5f9;">
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Service Chg</label>
                                    <input type="number" id="bdServiceCharge" name="service_charge" value="0" readonly style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem; background: #f1f5f9;">
                                </div>
                                <div class="form-group" style="margin-bottom: 0; grid-column: span 2;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Total Bed Amt</label>
                                    <input type="number" id="bdTotalAmount" name="total_bed_amount" value="0" readonly style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem; background: #e0f2fe; color: #0284c7; font-weight: bold;">
                                </div>

                                <!-- ROW 2: Payment Splits & Buttons -->
                                <div style="grid-column: 1 / -1; margin-top: 0.4rem; background: #fdfdfd; padding: 0.5rem; border: 1px solid rgba(31, 107, 74, 0.2); border-radius: 8px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">
                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-1" style="border-bottom: 1px dashed rgba(31, 107, 74, 0.2);">
                                        <label style="font-size: 0.85rem; font-weight: 800; color: #1f6b4a; margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">
                                            <i class="fas fa-wallet me-1"></i> Advance Payments
                                        </label>
                                        <button type="button" class="btn btn-sm" style="font-size: 0.7rem; padding: 0.2rem 0.6rem; font-weight: 700; border-radius: 6px; background: rgba(31, 107, 74, 0.1); color: #1f6b4a; border: 1px solid rgba(31, 107, 74, 0.2); transition: all 0.2s;" onmouseover="this.style.background='#1f6b4a'; this.style.color='#fff';" onmouseout="this.style.background='rgba(31, 107, 74, 0.1)'; this.style.color='#1f6b4a';" onclick="addPaymentSplitRow()">
                                            <i class="fas fa-plus"></i> Add Split
                                        </button>
                                    </div>
                                    <div id="paymentSplitsContainer" class="d-flex flex-column gap-2">
                                        <!-- Default first row added via JS -->
                                    </div>
                                    <!-- Hidden old field for backward compatibility or serialization ease if needed -->
                                    <input type="hidden" name="advance_payment" id="totalAdvancePaymentInput" value="0">
                                </div>

                                <div class="form-group" style="margin-bottom: 0; grid-column: span 2;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Total Due Now</label>
                                    <div class="d-flex align-items-center justify-content-between" style="border: 1px solid var(--gray-300); border-radius: 4px; padding: 0.2rem 0.4rem; background: #fff;">
                                        <span id="lblGrandTotal" style="color: var(--teal-dark); font-weight: 700; font-size: 0.75rem; text-align: center; width: 100%;">-₹0.00</span>
                                    </div>
                                    <div class="d-none">
                                        <input type="hidden" id="bdAdmissionCharge" name="admission_charge" value="350">
                                        <input type="hidden" id="bdMrdCharge" name="mrd_charge" value="400">
                                        <input type="hidden" id="bdFoodCharge" name="food_charge" value="570">
                                    </div>
                                </div>
                                
                                <div style="grid-column: span 1;"></div>
                                
                                <div class="form-group" style="margin-bottom: 0; grid-column: span 2; display: flex; justify-content: flex-end; gap: 0.5rem; align-items: flex-end; height: 100%;">
                                    <button type="button" class="btn btn-outline" style="border: 1px solid var(--gray-300); background: #fff; padding: 0.3rem 1rem; border-radius: 4px; color: var(--gray-700); font-weight: 600; font-size: 0.75rem; height: 26px; line-height: 1;" onclick="closeAddAdmissionModal()">Cancel</button>
                                    <button type="button" class="btn btn-success" style="padding: 0.3rem 1.5rem; border-radius: 4px; background: #0d9488; color: #ffffff !important; border: none; font-weight: 700; font-size: 0.75rem; height: 26px; line-height: 1;" onclick="saveAdmission()">
                                        <i class="fas fa-check-circle me-1"></i> Admit
                                    </button>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

        <!-- Edit Admission Modal -->
    <div id="editAdmissionModal" class="modal-overlay hidden" onclick="closeEditAdmissionModalOnBackdrop(event)">
        <div class="billing-modal-card" onclick="event.stopPropagation()">
            <div class="billing-modal-head">
                <h3><i class="fas fa-edit"></i> Edit IPD Admission</h3>
                <button class="btn-close-modal" type="button" onclick="closeEditAdmissionModal()" title="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body-scroll">
                <form id="editAdmissionForm">
                    <input type="hidden" id="editAdmissionId" name="admission_id">
                    <input type="hidden" id="editSlNo" name="sl_no">

                    <div class="modal-section-card">
                        <div class="modal-section-body" style="padding: 0.75rem;">
                            <!-- Ultra-Dense Grid with 5 Columns -->
                            <div class="form-row" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.4rem 0.6rem; align-items: end;">
                                
                                <!-- SECTION 1: PATIENT & MEDICAL -->
                                <div style="grid-column: 1 / -1; font-weight: 700; font-size: 0.8rem; color: var(--teal-dark); border-bottom: 1px solid var(--gray-300); padding-bottom: 0.1rem; margin-bottom: 0.2rem;">
                                    <i class="fas fa-user-injured me-1"></i> Patient & Medical Info
                                </div>
                                
                                <div class="form-group" style="margin-bottom: 0; grid-column: span 2;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Patient *</label>
                                    <select id="editPatientSelect" name="patient_id" required style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem; background-color: #fff;"></select>
                                </div>
                                <div class="form-group" style="margin-bottom: 0; grid-column: span 2;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Admitting Doctor *</label>
                                    <select id="editDoctorSelect" name="admitting_doctor_id" required style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem; background-color: #fff;"></select>
                                </div>
                                <div class="form-group" style="margin-bottom: 0; grid-column: span 1;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Bed Assignment *</label>
                                    <select id="editBedSelect" name="bed_id" required style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem; background-color: #fff;"></select>
                                </div>

                                <div class="form-group" style="margin-bottom: 0; grid-column: span 2;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Chief Complaint</label>
                                    <input type="text" id="editChiefComplaint" name="chief_complaint" placeholder="Brief complaint..." style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">
                                </div>
                                <div class="form-group" style="margin-bottom: 0; grid-column: span 3;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Diagnosis</label>
                                    <input type="text" id="editDiagnosis" name="diagnosis" placeholder="Diagnosis..." style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">
                                </div>
                                
                                <div class="form-group" style="margin-bottom: 0; grid-column: span 2;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Contact Name</label>
                                    <input type="text" id="editEmergencyName" name="emergency_contact_name" placeholder="Contact Name" style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">
                                </div>
                                <div class="form-group" style="margin-bottom: 0; grid-column: span 3;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Contact Phone</label>
                                    <input type="text" id="editEmergencyPhone" name="emergency_contact_phone" placeholder="Phone Number" style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">
                                </div>
                                
                                <!-- SECTION 2: ADMISSION DETAILS -->
                                <div style="grid-column: 1 / -1; font-weight: 700; font-size: 0.8rem; color: var(--teal-dark); border-bottom: 1px solid var(--gray-300); padding-bottom: 0.1rem; margin-top: 0.3rem;">
                                    <i class="fas fa-sign-in-alt me-1"></i> Admission Details
                                </div>
                                
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Admission Type</label>
                                    <select id="editAdmissionType" name="admission_type" style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem; background-color: #fff;">
                                        <option value="Emergency">Emergency</option>
                                        <option value="Planned">Planned</option>
                                        <option value="Transfer">Transfer</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Admission Date *</label>
                                    <input type="date" id="editAdmissionDate" name="admission_date" required style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem; background-color: #ecfdf5; color: #065f46; font-weight: 600;">
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Admission Time</label>
                                    <input type="time" id="editAdmissionTime" name="admission_time" style="width: 100%; padding: 0.2rem 0.4rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">
                                </div>

                                <div class="form-group" style="margin-bottom: 0; grid-column: span 2; display: flex; justify-content: flex-end; gap: 0.5rem; align-items: flex-end; height: 100%;">
                                    <button type="button" class="btn btn-outline" style="border: 1px solid var(--gray-300); background: #fff; padding: 0.3rem 1rem; border-radius: 4px; color: var(--gray-700); font-weight: 600; font-size: 0.75rem; height: 26px; line-height: 1;" onclick="closeEditAdmissionModal()">Cancel</button>
                                    <button type="button" class="btn btn-primary" style="padding: 0.3rem 1.5rem; border-radius: 4px; background: #0ea5e9; color: #ffffff !important; border: none; font-weight: 700; font-size: 0.75rem; height: 26px; line-height: 1;" onclick="updateAdmission()">
                                        <i class="fas fa-save me-1"></i> Update
                                    </button>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                </form>
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
    <!-- App Scripts -->
    <script src="../../public/assets/js/ipd_main.js?v=<?= time() ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        let admissionsTable; // Global scope so functions outside $(document).ready can access it

        $(document).ready(function () {
            
            // Initialize Flatpickr for Date
            flatpickr("#admissionDate", {
                dateFormat: "Y-m-d",
                defaultDate: "today"
            });

            // Initialize Custom Time Dropdowns with current time
            function initTimeDropdowns() {
                const now = new Date();
                let h = now.getHours();
                const m = now.getMinutes();
                const ampm = h >= 12 ? 'PM' : 'AM';
                
                h = h % 12;
                h = h ? h : 12; // the hour '0' should be '12'
                
                const hourStr = h.toString().padStart(2, '0');
                // Round minutes to nearest 5
                const minStr = (Math.round(m / 5) * 5).toString().padStart(2, '0').replace('60', '00');

                $('#timeHour').val(hourStr);
                // If minStr is not in the list (e.g. 60 became 00), fallback to 00
                $('#timeMinute').val(minStr).length ? null : $('#timeMinute').val('00');
                $('#timeAmPm').val(ampm);
                
                updateHiddenTime();
            }

            function updateHiddenTime() {
                let h = parseInt($('#timeHour').val(), 10);
                const m = $('#timeMinute').val();
                const ampm = $('#timeAmPm').val();

                if (ampm === 'PM' && h < 12) h += 12;
                if (ampm === 'AM' && h === 12) h = 0;

                const dbTime = h.toString().padStart(2, '0') + ':' + m + ':00';
                $('#admissionTime').val(dbTime);
            }

            $('#timeHour, #timeMinute, #timeAmPm').on('change', updateHiddenTime);
            initTimeDropdowns();
            
            // Initialize Select2 for Doctor Search in Add Admission Modal
            IPD.initDoctorSearch('#doctorSelect', '#addAdmissionModal');

            window.openAdvancedPatientSearch = function() {
                IPD.toast('Advanced Patient Search modal will be implemented here.', 'info');
            };

            // Global Variables for Beds
            // admissionsTable is declared globally above

            // Initialize DataTable
            admissionsTable = $('#admissionsTable').DataTable({
                ajax: {
                    url: IPD.API_BASE + '/admissions',
                    dataSrc: 'data.admissions'
                },
                columns: [
                    { 
                        data: 'admission_id',
                        render: function(data, type, row) {
                            return `<a href="javascript:void(0)" onclick="viewAdmission('${data}')" style="color: #1f6b4a; font-weight: 800; text-decoration: underline; cursor: pointer;">${data}</a>`;
                        }
                    },
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

            // ── Admission Custom Modal Logic ──────────────────────────────────
            window.clearPatientSelection = function() {
                $('#patientSearchInput').val('');
                $('#patientSelect').val('');
                $('#doctorSelect').empty().append('<option value="">-- Select a patient first --</option>');
                $('#patientSearchResults').hide().empty();
            };

            window.showAddAdmissionModal = function () {
                $('#addAdmissionModal').removeClass('hidden');
                
                // Reset Search
                clearPatientSelection();
                
                // Reset Payment Splits
                if (typeof splitPaymentCount !== 'undefined') {
                    splitPaymentCount = 0;
                    document.getElementById('paymentSplitsContainer').innerHTML = '';
                    addPaymentSplitRow();
                }

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

                // Reset doctor dropdown
                $('#doctorSelect').empty().append('<option value="">-- Select a patient first --</option>');
            };

            window.closeAddAdmissionModal = function () {
                $('#addAdmissionModal').addClass('hidden');
            };

                        window.closeEditAdmissionModal = function () {
                $('#editAdmissionModal').addClass('hidden');
            };

            window.closeEditAdmissionModalOnBackdrop = function (e) {
                if (e.target === e.currentTarget) {
                    closeEditAdmissionModal();
                }
            };

            window.closeAddAdmissionModalOnBackdrop = function (e) {
                if (e.target === e.currentTarget) {
                    closeAddAdmissionModal();
                }
            };

            // ── Custom Appointment-based Selection Logic ──────────────────────
            let patientSearchTimeout;

            $('#patientSearchInput').on('input', function () {
                $('#patientSelect').val(''); // Clear the selected ID if they type
                const query = $(this).val().trim();
                clearTimeout(patientSearchTimeout);

                if (query.length < 2) {
                    $('#patientSearchResults').hide().empty();
                    return;
                }

                patientSearchTimeout = setTimeout(() => {
                    IPD.ajax(`dashboard/patients?search=${encodeURIComponent(query)}&limit=10`, 'GET')
                        .then(response => {
                            renderPatientResults(response.data.patients);
                        })
                        .catch(() => {
                            $('#patientSearchResults').hide();
                        });
                }, 400);
            });

            function renderPatientResults(patients) {
                const $results = $('#patientSearchResults');
                if (!patients || patients.length === 0) {
                    $results.html('<div class="p-3 text-muted small text-center">No patients found</div>').show();
                    return;
                }

                let html = '';
                patients.forEach(pat => {
                    const displayPhone = pat.contact || 'N/A';
                    const displayGender = pat.gender || 'Unknown';
                    const displayAge = pat.age ? `${pat.age} yrs` : 'Unknown age';
                    html += `
                        <div class="patient-result-item p-2 border-bottom" style="cursor:pointer;" onclick='selectPatient(${JSON.stringify(pat).replace(/'/g, "&#39;")})'>
                            <div class="fw-bold">${pat.name} <span class="badge bg-soft-info text-info ms-2">${pat.patient_id}</span></div>
                            <div class="small text-muted"><i class="fas fa-phone-alt"></i> ${displayPhone} | <i class="fas fa-venus-mars"></i> ${displayGender}, ${displayAge}</div>
                        </div>
                    `;
                });
                $results.html(html).show();
            }

            window.selectPatient = function (pat) {
                // Set hidden patient_id field
                $('#patientSelect').val(pat.patient_id || '');
                // Populate search input with name and ID
                $('#patientSearchInput').val(pat.name + ' (' + pat.patient_id + ')');
                $('#patientSearchResults').hide();

                // Clear out doctor select until we fetch the latest one
                $('#doctorSelect').empty().append('<option value="">-- Fetching doctor... --</option>');
                
                // Fetch the latest doctor for this patient
                fetchLatestDoctor(pat.patient_id);
            };

            // Hide results on click outside
            $(document).on('click', function (e) {
                if (!$(e.target).closest('#addAdmissionModal').length) {
                    $('#patientSearchResults').hide();
                }
            });

            // Removed Bootstrap shown.bs.modal logic since we use a custom ref-modal.

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
            const bdc = document.getElementById('bedDetailCard');
            if (bdc) bdc.style.display = 'none';
        }

        function getFiltered(field, value) {
            return allAvailableBeds.filter(b => String(b[field]) === String(value));
        }

        function onFloorNumberChange() {
            const val = document.getElementById('selFloorNumber').value;
            resetFrom('selFloorName');
            if (!val) return;
            // Floor Name: unique floor names for this floor number
            const names = [...new Map(getFiltered('floor_number', val).map(b => [b.floor_name || 'N/A', b])).values()];
            const sel = document.getElementById('selFloorName');
            sel.innerHTML = '<option value="">-- Select Floor Name --</option>';
            names.forEach(b => {
                const displayName = b.floor_name || 'N/A';
                sel.innerHTML += `<option value="${displayName}">${displayName}</option>`;
            });
            sel.disabled = false;
            // If only one option, auto-select it
            if (names.length === 1) { sel.value = names[0].floor_name || 'N/A'; onFloorNameChange(); }
        }

        function onFloorNameChange() {
            const floorNo = document.getElementById('selFloorNumber').value;
            const floorName = document.getElementById('selFloorName').value;
            resetFrom('selWardName');
            if (!floorName) return;
            const filtered = allAvailableBeds.filter(b =>
                String(b.floor_number) === floorNo && (b.floor_name || 'N/A') === floorName);
            const wards = [...new Set(filtered.map(b => b.ward_name || 'N/A'))].sort();
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
                String(b.floor_number) === floorNo && (b.floor_name || 'N/A') === floorName && (b.ward_name || 'N/A') === wardName);
            const types = [...new Set(filtered.map(b => b.room_type || 'N/A'))].sort();
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
                String(b.floor_number) === floorNo && (b.floor_name || 'N/A') === floorName &&
                (b.ward_name || 'N/A') === wardName && (b.room_type || 'N/A') === wardType);
            const rooms = [...new Map(filtered.map(b => [b.room_number || 'N/A', b])).values()]
                .sort((a, b) => String(a.room_number || '').localeCompare(String(b.room_number || '')));
            const sel = document.getElementById('selRoomNumber');
            sel.innerHTML = '<option value="">-- Select Room Number --</option>';
            rooms.forEach(r => {
                const rNum = r.room_number || 'N/A';
                sel.innerHTML += `<option value="${rNum}">${rNum}</option>`;
            });
            sel.disabled = false;
            if (rooms.length === 1) { sel.value = rooms[0].room_number || 'N/A'; onRoomNumberChange(); }
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
            const bdc = document.getElementById('bedDetailCard');
            if (bdc) bdc.style.display = 'none';

            if (!roomNumber) return;

            // Filter beds matching all selected criteria
            const filtered = allAvailableBeds.filter(b =>
                String(b.floor_number) === floorNo &&
                (b.floor_name || 'N/A') === floorName &&
                (b.ward_name || 'N/A') === wardName &&
                (b.room_type || 'N/A') === wardType &&
                (b.room_number || 'N/A') === roomNumber
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
            const bdc = document.getElementById('bedDetailCard');
            if (!bedId) { if(bdc) bdc.style.display = 'none'; return; }
            const opt = document.querySelector(`#bedSelect option[value="${bedId}"]`);
            if (!opt || !opt.dataset.bed) { if(bdc) bdc.style.display = 'none'; return; }
            const bed = JSON.parse(opt.dataset.bed);
            
            // Financial details
            document.getElementById('bdAmountPerDay').value = bed.amount_per_day || '0';
            document.getElementById('bdNursingCharge').value = bed.nursig_charge || '0';
            document.getElementById('bdDoctorCharge').value = bed.doctor_charge || '0';
            document.getElementById('bdServiceCharge').value = bed.service_charge || '0';
            document.getElementById('bdTotalAmount').value = bed.total_bed_amount || '0';
            
            calculateTotalRent(); // Update the ribbon total display
            
            if (bdc) bdc.style.display = 'block';
        }

        function calculateTotalRent() {
            const bed = parseFloat(document.getElementById('bdAmountPerDay').value) || 0;
            const nursing = parseFloat(document.getElementById('bdNursingCharge').value) || 0;
            const doctor = parseFloat(document.getElementById('bdDoctorCharge').value) || 0;
            const service = parseFloat(document.getElementById('bdServiceCharge').value) || 0;
            const dailyTotal = bed + nursing + doctor + service;
            document.getElementById('bdTotalAmount').value = dailyTotal.toString();
            
            const lbl = document.getElementById('lblDailyTotal');
            if(lbl) lbl.innerText = dailyTotal.toString();
            
            // Calculate Initial Total
            const adm = parseFloat(document.getElementById('bdAdmissionCharge').value) || 350;
            const mrd = parseFloat(document.getElementById('bdMrdCharge').value) || 400;
            const food = parseFloat(document.getElementById('bdFoodCharge').value) || 570;
            const initialTotal = adm + mrd + food;
            
            const lblInit = document.getElementById('lblInitialTotal');
            if(lblInit) lblInit.innerText = initialTotal.toString();
            
            // Grand Total (Daily + Initial)
            const grandTotal = dailyTotal + initialTotal;
            
            // Calculate Total Advance Payments
            let totalAdvance = 0;
            document.querySelectorAll('.split-amount-input').forEach(input => {
                totalAdvance += parseFloat(input.value) || 0;
            });
            const totalAdvanceInput = document.getElementById('totalAdvancePaymentInput');
            if (totalAdvanceInput) {
                totalAdvanceInput.value = totalAdvance;
            }
            
            const totalDue = grandTotal - totalAdvance;

            const lblGrand = document.getElementById('lblGrandTotal');
            if(lblGrand) lblGrand.innerText = "₹" + totalDue.toString();
        }


        function onReferralTypeChange() {
            const type = document.getElementById('referralTypeSelect').value;
            const txt = document.getElementById('refNameText');
            const sel = document.getElementById('refNameSelect');
            
            if ($(sel).data('select2')) {
                $(sel).select2('destroy');
                sel.innerHTML = '<option value="">Search...</option>';
            }
            
            if (type === 'Internal') {
                document.getElementById('refNameText').classList.add('d-none');
                txt.removeAttribute('name');
                
                sel.setAttribute('name', 'referral_name');
                $(sel).removeClass('d-none');
                
                $(sel).select2({
                    ajax: {
                        url: IPD.API_BASE + '/dashboard/doctors',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return { search: params.term || '' };
                        },
                        processResults: function (data) {
                            return {
                                results: data.data.doctors.map(d => ({
                                    id: d.name,
                                    text: `${d.name} - ${d.specialization}`
                                }))
                            };
                        }
                    },
                    placeholder: 'Search internal doctor...',
                    tags: true,
                    dropdownParent: $('#addAdmissionModal')
                });
                
                $(sel).next('.select2-container').show();
                document.getElementById('btnExternalRefAdd').classList.add('d-none');
                document.getElementById('externalRefCard').classList.add('d-none');
                
            } else if (type === 'External') {
                document.getElementById('refNameText').classList.add('d-none');
                txt.removeAttribute('name');
                
                sel.setAttribute('name', 'referral_name');
                $(sel).removeClass('d-none');
                
                let baseUrl = '';
                if (typeof IPD !== 'undefined' && IPD.API_BASE) {
                    baseUrl = IPD.API_BASE.split('/reception_view/')[0];
                } else {
                    baseUrl = '/GM_HMS'; // Fallback
                }
                
                $(sel).select2({
                    ajax: {
                        url: baseUrl + '/api/billing/opd/referral/search',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return { q: params.term || '' };
                        },
                        processResults: function (data) {
                            let refs = [];
                            if (data && data.data) {
                                refs = Array.isArray(data.data) ? data.data : (data.data.referrals || []);
                            } else if (Array.isArray(data)) {
                                refs = data;
                            }
                            return {
                                results: refs.map(d => ({
                                    id: d.mobile ? `${d.name} (${d.mobile})` : d.name,
                                    text: d.mobile ? `${d.name} - ${d.mobile}` : d.name
                                }))
                            };
                        }
                    },
                    placeholder: 'Search external referral...',
                    tags: true,
                    dropdownParent: $('#addAdmissionModal')
                });
                
                $(sel).next('.select2-container').show();
                document.getElementById('btnExternalRefAdd').classList.remove('d-none');
                document.getElementById('externalRefCard').classList.add('d-none');
                
            } else {
                sel.removeAttribute('name');
                sel.classList.add('d-none');
                
                txt.setAttribute('name', 'referral_name');
                document.getElementById('refNameText').classList.remove('d-none');
                
                document.getElementById('btnExternalRefAdd').classList.add('d-none');
                document.getElementById('externalRefCard').classList.add('d-none');
            }
        }
        
        function toggleExternalRefCard() {
            const card = document.getElementById('externalRefCard');
            card.classList.toggle('d-none');
            if (!card.classList.contains('d-none')) {
                document.getElementById('extRefName').focus();
            }
        }
        
        async function saveExternalRef() {
            const btn = document.querySelector('#externalRefCard .btn-success');
            const originalText = btn ? btn.innerText : 'Add';
            if (btn) { btn.innerText = 'Saving...'; btn.disabled = true; }
            
            const name = document.getElementById('extRefName').value.trim();
            const phone = document.getElementById('extRefPhone').value.trim();
            
            if (name) {
                try {
                    let baseUrl = '';
                    if (typeof IPD !== 'undefined' && IPD.API_BASE) {
                        baseUrl = IPD.API_BASE.split('/reception_view/')[0];
                    } else {
                        baseUrl = '/GM_HMS'; 
                    }
                    
                    const response = await fetch(baseUrl + '/api/billing/opd/referral', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ name: name, mobile: phone })
                    });
                    
                    if (!response.ok) console.error('Failed to save referral data');
                } catch(e) {
                    console.error('Error saving referral data', e);
                }
                
                let text = name;
                if (phone) text += ` (${phone})`;
                
                const type = document.getElementById('referralTypeSelect').value;
                if (type === 'External') {
                    const sel = $('#refNameSelect');
                    if (sel.length) {
                        const newOption = new Option(text, text, true, true);
                        sel.append(newOption).trigger('change');
                    }
                } else {
                    document.getElementById('refNameText').value = text;
                }
            }
            
            if (btn) { btn.innerText = originalText; btn.disabled = false; }
            document.getElementById('extRefName').value = '';
            document.getElementById('extRefPhone').value = '';
            toggleExternalRefCard();
        }

        function saveAdmission() {
            const form = document.getElementById('addAdmissionForm');
            if (!form.reportValidity()) {
                return;
            }

            const formData = {};
            $('#addAdmissionForm').serializeArray().forEach(field => {
                formData[field.name] = field.value;
            });

            IPD.ajax('admissions', 'POST', formData)
                .then(response => {
                    IPD.toast('Admission created successfully!', 'success');
                    closeAddAdmissionModal();
                    form.reset();
                    clearPatientSelection();
                    admissionsTable.ajax.reload();
                })
                .catch(error => {
                    IPD.showError(error);
                });
        }

        function viewAdmission(id) {
            IPD.ajax(`admissions?id=${id}`, 'GET')
                .then(response => {
                    const admission = response.data;

                    const modalContent = `
    <div id="viewAdmissionModal" class="modal-overlay" style="display: flex;" onclick="closeViewAdmissionModalOnBackdrop(event)">
        <div class="billing-modal-card" onclick="event.stopPropagation()">
            <div class="billing-modal-head">
                <h3><i class="fas fa-eye"></i> Admission Details - ${admission.admission_id}</h3>
                <button class="btn-close-modal" type="button" onclick="closeViewAdmissionModal()" title="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body-scroll">
                <div class="modal-section-card">
                    <div class="modal-section-body" style="padding: 0.75rem;">
                        <!-- Ultra-Dense Grid with 5 Columns -->
                        <div class="form-row" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.4rem 0.6rem; align-items: end;">
                            
                            <!-- SECTION 1: PATIENT & MEDICAL INFO -->
                            <div style="grid-column: 1 / -1; font-weight: 700; font-size: 0.8rem; color: var(--teal-dark); border-bottom: 1px solid var(--gray-300); padding-bottom: 0.1rem; margin-top: 0;">
                                <i class="fas fa-user-md me-1"></i> Patient & Medical Info
                            </div>

                            <div class="form-group" style="margin-bottom: 0; grid-column: span 2;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Patient Name</label>
                                <div style="font-weight: 700; color: #333; padding: 0.2rem 0.4rem; background: #f8fafc; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">${admission.patient_name || '-'}</div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0; grid-column: span 1;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Age</label>
                                <div style="font-weight: 500; color: #333; padding: 0.2rem 0.4rem; background: #f8fafc; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">${admission.patient_age || '-'}</div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0; grid-column: span 1;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Gender</label>
                                <div style="font-weight: 500; color: #333; padding: 0.2rem 0.4rem; background: #f8fafc; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">${admission.patient_gender || '-'}</div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0; grid-column: span 1;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Contact</label>
                                <div style="font-weight: 500; color: #333; padding: 0.2rem 0.4rem; background: #f8fafc; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">${admission.patient_contact || '-'}</div>
                            </div>

                            <div class="form-group" style="margin-bottom: 0; grid-column: span 1;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Doctor</label>
                                <div style="font-weight: 500; color: #333; padding: 0.2rem 0.4rem; background: #f8fafc; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">${admission.doctor_name || '-'}</div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0; grid-column: span 1;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Chief Complaint</label>
                                <div style="font-weight: 500; color: #333; padding: 0.2rem 0.4rem; background: #f8fafc; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">${admission.chief_complaint || '-'}</div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0; grid-column: span 1;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Diagnosis</label>
                                <div style="font-weight: 500; color: #333; padding: 0.2rem 0.4rem; background: #f8fafc; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">${admission.diagnosis || '-'}</div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0; grid-column: span 1;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Emerg. Name</label>
                                <div style="font-weight: 500; color: #333; padding: 0.2rem 0.4rem; background: #f8fafc; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">${admission.emergency_contact_name || '-'}</div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0; grid-column: span 1;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Emerg. Phone</label>
                                <div style="font-weight: 500; color: #333; padding: 0.2rem 0.4rem; background: #f8fafc; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">${admission.emergency_contact_phone || '-'}</div>
                            </div>

                            <!-- SECTION 2: BED ALLOCATION -->
                            <div style="grid-column: 1 / -1; font-weight: 700; font-size: 0.8rem; color: var(--teal-dark); border-bottom: 1px solid var(--gray-300); padding-bottom: 0.1rem; margin-top: 0.3rem;">
                                <i class="fas fa-bed me-1"></i> Hospital Bed Allocation
                            </div>

                            <div class="form-group" style="margin-bottom: 0; grid-column: span 1;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Floor</label>
                                <div style="font-weight: 500; color: #333; padding: 0.2rem 0.4rem; background: #f8fafc; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">${admission.floor_name || '-'}</div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0; grid-column: span 1;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Ward</label>
                                <div style="font-weight: 500; color: #333; padding: 0.2rem 0.4rem; background: #f8fafc; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">${admission.ward_name || '-'}</div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0; grid-column: span 1;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Room Type</label>
                                <div style="font-weight: 500; color: #333; padding: 0.2rem 0.4rem; background: #f8fafc; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">${admission.room_category || admission.room_type || '-'}</div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0; grid-column: span 1;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Room No</label>
                                <div style="font-weight: 500; color: #333; padding: 0.2rem 0.4rem; background: #f8fafc; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">${admission.room_no || '-'}</div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0; grid-column: span 1;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Bed No</label>
                                <div style="font-weight: 500; color: #333; padding: 0.2rem 0.4rem; background: #f8fafc; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">${admission.bed_number || '-'}</div>
                            </div>

                            <!-- SECTION 3: ADMISSION & REFERRAL -->
                            <div style="grid-column: 1 / -1; font-weight: 700; font-size: 0.8rem; color: var(--teal-dark); border-bottom: 1px solid var(--gray-300); padding-bottom: 0.1rem; margin-top: 0.3rem; display: flex; justify-content: space-between;">
                                <div><i class="fas fa-hospital-user me-1"></i> Admission & Referral</div>
                                <span style="background: #1f6b4a; color: white; padding: 0.1rem 0.5rem; border-radius: 12px; font-size: 0.65rem;">${admission.status}</span>
                            </div>

                            <div class="form-group" style="margin-bottom: 0; grid-column: span 1;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Admission Type</label>
                                <div style="font-weight: 500; color: #333; padding: 0.2rem 0.4rem; background: #f8fafc; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">${admission.admission_type || '-'}</div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0; grid-column: span 1;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Admiss. Date</label>
                                <div style="font-weight: 500; color: #333; padding: 0.2rem 0.4rem; background: #f8fafc; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">${IPD.formatDate(admission.admission_date)}</div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0; grid-column: span 1;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Admiss. Time</label>
                                <div style="font-weight: 500; color: #333; padding: 0.2rem 0.4rem; background: #f8fafc; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">${admission.admission_time || '-'}</div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0; grid-column: span 1;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Referral Type</label>
                                <div style="font-weight: 500; color: #333; padding: 0.2rem 0.4rem; background: #f8fafc; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">${admission.referral_type || '-'}</div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0; grid-column: span 1;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Referral Name</label>
                                <div style="font-weight: 500; color: #333; padding: 0.2rem 0.4rem; background: #f8fafc; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">${admission.referral_name || '-'}</div>
                            </div>

                            <!-- SECTION 4: FINANCIAL -->
                            ${admission.financials ? `
                            <div style="grid-column: 1 / -1; font-weight: 700; font-size: 0.8rem; color: var(--teal-dark); border-bottom: 1px solid var(--gray-300); padding-bottom: 0.1rem; margin-top: 0.3rem;">
                                <i class="fas fa-file-invoice-dollar me-1"></i> Financial Summary
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 0; grid-column: span 1;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Amount/Day</label>
                                <div style="font-weight: 700; color: #333; padding: 0.2rem 0.4rem; background: #f8fafc; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">${IPD.formatCurrency(admission.amount_per_day || 0)}</div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0; grid-column: span 1;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Nursing Chg</label>
                                <div style="font-weight: 700; color: #333; padding: 0.2rem 0.4rem; background: #f8fafc; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">${IPD.formatCurrency(admission.nursig_charge || 0)}</div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0; grid-column: span 1;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Doctor Chg</label>
                                <div style="font-weight: 700; color: #333; padding: 0.2rem 0.4rem; background: #f8fafc; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">${IPD.formatCurrency(admission.doctor_charge || 0)}</div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0; grid-column: span 1;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Service Chg</label>
                                <div style="font-weight: 700; color: #333; padding: 0.2rem 0.4rem; background: #f8fafc; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">${IPD.formatCurrency(admission.service_charge || 0)}</div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0; grid-column: span 1;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Total Bed Amt</label>
                                <div style="font-weight: 700; color: #0284c7; padding: 0.2rem 0.4rem; background: #e0f2fe; border: 1px solid #bae6fd; border-radius: 4px; font-size: 0.75rem;">${IPD.formatCurrency(admission.total_bed_amount || 0)}</div>
                            </div>

                            <div class="form-group" style="margin-bottom: 0; grid-column: span 1;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Payment Type</label>
                                <div style="font-weight: 700; color: #333; padding: 0.2rem 0.4rem; background: #f8fafc; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.75rem;">${admission.payment_method || '-'}</div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0; grid-column: span 2;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Total Payments / Adv</label>
                                <div style="font-weight: 700; color: #1f6b4a; padding: 0.2rem 0.4rem; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 4px; font-size: 0.75rem;">${IPD.formatCurrency(admission.financials.total_payments)}</div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0; grid-column: span 2;">
                                <label style="font-size: 0.7rem; font-weight: 700; color: var(--gray-600); margin-bottom: 0.1rem; display: block;">Balance Due</label>
                                <div style="font-weight: 800; padding: 0.2rem 0.4rem; background: ${admission.financials.balance_due > 0 ? '#fef2f2' : '#f0fdf4'}; border: 1px solid ${admission.financials.balance_due > 0 ? '#fca5a5' : '#bbf7d0'}; border-radius: 4px; font-size: 0.75rem; color: ${admission.financials.balance_due > 0 ? '#dc2626' : '#16a34a'};">${IPD.formatCurrency(admission.financials.balance_due)}</div>
                            </div>
                            ` : ''}

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
`;

            $('#viewAdmissionModal').remove();
            $('body').append(modalContent);
        })
        .catch(error => {
            IPD.toast(error.message || 'Failed to fetch admission details', 'error');
        });
}

window.closeViewAdmissionModal = function() {
    $('#viewAdmissionModal').remove();
};
window.closeViewAdmissionModalOnBackdrop = function(e) {
    if (e.target === e.currentTarget) {
        closeViewAdmissionModal();
    }
};

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
                    
                    // New Fields Population
                    $('#editReferralTypeSelect').val(admission.referral_type || '');
                    $('#editRefNameText').val(admission.referral_name || '');
                    
                    $('#editSelFloorNumber').html(`<option value="${admission.floor_number || ''}">${admission.floor_number || '-'}</option>`);
                    $('#editSelFloorName').html(`<option value="${admission.floor_name || ''}">${admission.floor_name || '-'}</option>`);
                    $('#editSelWardName').html(`<option value="${admission.ward_name || ''}">${admission.ward_name || '-'}</option>`);
                    $('#editSelWardType').html(`<option value="${admission.room_category || admission.room_type || ''}">${admission.room_category || admission.room_type || '-'}</option>`);
                    $('#editSelRoomNumber').html(`<option value="${admission.room_no || ''}">${admission.room_no || '-'}</option>`);

                    $('#editBdAmountPerDay').val(admission.amount_per_day || 0);
                    $('#editBdNursingCharge').val(admission.nursig_charge || 0);
                    $('#editBdDoctorCharge').val(admission.doctor_charge || 0);
                    $('#editBdServiceCharge').val(admission.service_charge || 0);
                    $('#editBdTotalAmount').val(admission.total_bed_amount || 0);
                    
                    $('#editPaymentMethod').val(admission.payment_method || 'CASH');
                    $('#editAdvAmount').val(admission.advance_amount || 0);
                    $('#editTotalDue').val(admission.total_due || 0);


                    // Show modal
                    $('#editAdmissionModal').removeClass('hidden');
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
                    $('#editAdmissionModal').addClass('hidden');
                    admissionsTable.ajax.reload();
                })
                .catch(error => {
                    IPD.toast(error.message || 'Failed to update admission', 'error');
                });
        }
        
        // ── Payment Splits Logic ──────────────────────────────────────────────
        let splitPaymentCount = 0;

        function addPaymentSplitRow() {
            const container = document.getElementById('paymentSplitsContainer');
            if (!container) return;
            const rowId = 'split_row_' + splitPaymentCount;
            const html = `
                <div class="d-flex gap-2 align-items-center payment-split-row" id="${rowId}" style="animation: fadeIn 0.3s ease;">
                    <div style="flex: 1; position: relative;">
                        <i class="fas fa-money-check-alt" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #1f6b4a; font-size: 0.8rem;"></i>
                        <select name="payments[${splitPaymentCount}][mode]" class="form-select" style="padding: 0.3rem 0.4rem 0.3rem 2rem; font-size: 0.8rem; font-weight: 600; color: #1f6b4a; border: 1px solid #d1e0d7; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); cursor: pointer; height: auto;">
                            <option value="CASH">Cash</option>
                            <option value="UPI">UPI</option>
                            <option value="CARD">Card</option>
                        </select>
                    </div>
                    <div style="flex: 1; position: relative;">
                        <i class="fas fa-rupee-sign" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #1f6b4a; font-size: 0.8rem;"></i>
                        <input type="number" name="payments[${splitPaymentCount}][amount]" class="form-control split-amount-input" placeholder="Amount" value="0" style="padding: 0.3rem 0.4rem 0.3rem 2rem; font-size: 0.8rem; font-weight: 700; color: #1f6b4a; border: 1px solid #d1e0d7; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); height: auto;" oninput="calculateTotalRent()">
                    </div>
                    <button type="button" class="btn btn-link text-danger p-0 ms-1" style="font-size: 1.1rem; transition: transform 0.2s; opacity: 0.7;" onmouseover="this.style.opacity='1'; this.style.transform='scale(1.1)';" onmouseout="this.style.opacity='0.7'; this.style.transform='scale(1)';" onclick="removePaymentSplitRow('${rowId}')" title="Remove Split">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
            splitPaymentCount++;
            calculateTotalRent();
        }

        function removePaymentSplitRow(rowId) {
            const el = document.getElementById(rowId);
            if (el) el.remove();
            calculateTotalRent();
        }
    </script>
</body>

</html>