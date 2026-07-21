<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Receptionist', 'Admin'])) {
    header("Location: /GM_HMS/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Management - GM HMS</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Main Dashboard CSS -->
    <link rel="stylesheet" href="assets/css/reception_dashboard.css">

    <!-- Shared Styles (Modals, Tables) -->
    <link rel="stylesheet" href="assets/css/patient.css">

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        /* Appointment specific styles override */
        .status-scheduled {
            background: #E0F2F1;
            color: #00796B;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .timeline-view {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding-bottom: 20px;
        }

        /* Professional Filter Styles */
        .premium-filter-container {
            display: flex;
            align-items: center;
            gap: 15px;
            background: #fff;
            padding: 10px 15px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            flex-wrap: wrap;
        }

        .search-wrapper,
        .select-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-wrapper i,
        .select-wrapper i {
            position: absolute;
            left: 15px;
            color: #94a3b8;
            font-size: 0.9rem;
            pointer-events: none;
        }

        .professional-input {
            padding: 10px 15px 10px 40px !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 10px !important;
            font-size: 0.9rem;
            color: #1e293b;
            transition: all 0.3s ease;
            min-width: 250px;
            background: #f8fafc;
        }

        .professional-input:focus {
            border-color: #1f6b4a !important;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(31, 107, 74, 0.1);
        }

        .professional-select {
            padding: 10px 35px 10px 40px !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 10px !important;
            appearance: none;
            background: #f8fafc;
            font-size: 0.9rem;
            color: #1e293b;
            cursor: pointer;
            min-width: 180px;
        }

        .select-wrapper::after {
            content: '\f078';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 15px;
            color: #94a3b8;
            font-size: 0.7rem;
            pointer-events: none;
        }

        /* Premium Modal Form Styles */
        .modal-body {
            padding: 2rem;
            background: #FDFBF7;
            border-radius: 0 0 1.25rem 1.25rem;
        }

        .modal-body label {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
            display: block;
        }

        .modal-body .required {
            color: #ef4444;
            font-weight: bold;
        }

        .modal-body input[type="text"],
        .modal-body input[type="date"],
        .modal-body input[type="time"],
        .modal-body select,
        .modal-body textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 0.95rem;
            background: #ffffff;
            color: #1e293b;
            transition: all 0.3s ease;
        }

        .modal-body input:focus,
        .modal-body select:focus,
        .modal-body textarea:focus {
            border-color: #1f6b4a;
            box-shadow: 0 0 0 4px rgba(31, 107, 74, 0.1);
            outline: none;
        }

        .modal-body .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        @media (max-width: 640px) {
            .modal-body .form-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Advanced Button Animations */
        .adv-btn-save {
            background: #1f6b4a !important;
            color: #ffffff !important;
            border: none !important;
            padding: 0.875rem 2rem !important;
            border-radius: 0.75rem !important;
            font-weight: 700 !important;
            font-size: 1rem !important;
            box-shadow: 0 4px 15px rgba(31, 107, 74, 0.3) !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            opacity: 1 !important;
        }
        .adv-btn-save:disabled {
            background: #1f6b4a !important;
            opacity: 1 !important;
            cursor: not-allowed;
            box-shadow: none !important;
            filter: none !important;
        }
        .adv-btn-save:hover {
            transform: translateY(-3px) scale(1.02) !important;
            box-shadow: 0 8px 25px rgba(31, 107, 74, 0.4) !important;
        }
        
        .adv-btn-cancel {
            background: #ffffff !important;
            color: #ef4444 !important;
            border: 2px solid #fee2e2 !important;
            padding: 0.875rem 2rem !important;
            border-radius: 0.75rem !important;
            font-weight: 600 !important;
            font-size: 1rem !important;
            transition: all 0.3s ease !important;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .adv-btn-cancel:hover {
            background: #fef2f2 !important;
            border-color: #ef4444 !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15) !important;
        }
        
        .adv-btn-back {
            background: transparent;
            border: none;
            color: #64748b;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0.5rem 0;
            margin-bottom: 1.25rem;
            transition: all 0.3s ease;
        }
        .adv-btn-back:hover {
            color: #1f6b4a;
            transform: translateX(-5px);
        }
        
        .adv-btn-next {
            background: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%) !important;
            color: white !important;
            border: none !important;
            padding: 0.875rem 2.5rem !important;
            border-radius: 0.75rem !important;
            font-weight: 700 !important;
            font-size: 1rem !important;
            box-shadow: 0 4px 15px rgba(31, 107, 74, 0.3) !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
            width: 100%;
        }
        .adv-btn-next:hover {
            transform: translateY(-3px) scale(1.01) !important;
            box-shadow: 0 8px 25px rgba(31, 107, 74, 0.4) !important;
        }

        /* Professional Select2 Overrides */
        .select2-container--default .select2-selection--single {
            height: auto !important;
            padding: 0.65rem 1rem !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 0.75rem !important;
            font-size: 0.95rem !important;
            background: #ffffff !important;
            color: #1e293b !important;
            transition: all 0.3s ease !important;
            display: flex;
            align-items: center;
        }

        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #1f6b4a !important;
            box-shadow: 0 0 0 4px rgba(31, 107, 74, 0.1) !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: normal !important;
            padding-left: 0 !important;
            color: #1e293b !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100% !important;
            right: 10px !important;
        }
        
        .select2-dropdown {
            border: 1.5px solid #1f6b4a !important;
            border-radius: 0.75rem !important;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .select2-search__field {
            border-radius: 0.5rem !important;
            padding: 0.5rem 1rem !important;
            border: 1.5px solid #e2e8f0 !important;
            outline: none !important;
        }

        .select2-search__field:focus {
            border-color: #1f6b4a !important;
        }
        
        .select2-results__option--highlighted[aria-selected] {
            background-color: #1f6b4a !important;
            color: white !important;
        }

        .select2-results__option {
            padding: 0.75rem 1rem !important;
        }

        /* ----------------------------------------------------
         * Reference Card & Modal Styles (Matching Attached Image)
         * ---------------------------------------------------- */
        .appointment-modal-content {
            background: #FBF9F3 !important;
            border-radius: 14px !important;
            box-shadow: 0 20px 50px rgba(20, 77, 52, 0.15) !important;
            max-width: 840px !important;
            width: 95% !important;
            overflow: hidden !important;
            animation: modalSlideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1) !important;
            border: 1px solid #E2E0D6 !important;
        }

        @keyframes modalSlideUp {
            from { opacity: 0; transform: translateY(20px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .appointment-modal-header {
            background: #FBF9F3 !important;
            color: #144D34 !important;
            padding: 1.1rem 1.6rem !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            border-bottom: 1px solid #E8E6DC !important;
        }

        .header-title-container {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .header-icon-badge {
            width: 38px;
            height: 38px;
            background: #E8F4EC;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            color: #144D34;
            border: 1px solid #C6E6D2;
        }

        .appointment-modal-header h2 {
            margin: 0 !important;
            font-size: 1.35rem !important;
            font-weight: 800 !important;
            color: #144D34 !important;
            letter-spacing: -0.01em;
        }

        .modal-subtitle {
            margin: 2px 0 0 0;
            font-size: 0.82rem;
            color: #557365;
        }

        .modal-close-btn {
            background: transparent !important;
            border: none !important;
            color: #144D34 !important;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .modal-close-btn:hover {
            background: #EDEAE0 !important;
            color: #144D34 !important;
            transform: rotate(90deg);
        }

        .appointment-modal-body {
            padding: 1.4rem 1.6rem !important;
            background: #FBF9F3 !important;
            max-height: 80vh;
            overflow-y: auto;
        }

        .form-section-card {
            background: #FBF9F3;
            border: none;
            box-shadow: none;
            padding: 0;
            margin-bottom: 1.25rem;
        }

        .section-badge-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.85rem;
            padding-bottom: 0.35rem;
            border-bottom: 1px solid #E8E6DC;
        }

        .badge-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: #e8f5e9;
            color: #1f6b4a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        .section-title {
            font-weight: 700;
            font-size: 0.92rem;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .input-with-icon {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-with-icon .input-icon {
            position: absolute;
            left: 14px;
            color: #94a3b8;
            font-size: 0.95rem;
            pointer-events: none;
        }

        .input-with-icon input {
            padding-left: 40px !important;
        }

        .format-tag {
            background: #1f6b4a;
            color: #ffffff;
            font-size: 0.7rem;
            padding: 2px 7px;
            border-radius: 6px;
            font-weight: 700;
            margin-left: 6px;
            text-transform: uppercase;
        }

        /* 12-Hour Time Picker Component Styles */
        .time-picker-card {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 14px;
            transition: all 0.25s ease;
        }

        .time-picker-card:focus-within {
            border-color: #1f6b4a;
            box-shadow: 0 0 0 3px rgba(31, 107, 74, 0.12);
            background: #ffffff;
        }

        .time-picker-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .time-select-wrapper {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .time-picker-select {
            padding: 7px 10px !important;
            border: 1.5px solid #cbd5e1 !important;
            border-radius: 8px !important;
            font-size: 0.95rem !important;
            font-weight: 700 !important;
            color: #0f172a !important;
            background: #ffffff !important;
            cursor: pointer;
            min-width: 65px;
            text-align: center;
        }

        .time-picker-select:focus {
            border-color: #1f6b4a !important;
            outline: none !important;
        }

        .select-unit {
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
        }

        .time-colon {
            font-size: 1.25rem;
            font-weight: 800;
            color: #1f6b4a;
            line-height: 1;
        }

        .ampm-segmented-toggle {
            display: flex;
            background: #e2e8f0;
            padding: 3px;
            border-radius: 8px;
            gap: 2px;
            margin-left: auto;
        }

        .ampm-pill {
            border: none;
            background: transparent;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 700;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .ampm-pill.active {
            background: #1f6b4a;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(31, 107, 74, 0.3);
        }

        .quick-time-section {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px dashed #e2e8f0;
        }

        .quick-time-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 4px;
            display: block;
        }

        .quick-time-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .time-pill-btn {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 20px;
            padding: 3px 9px;
            font-size: 0.76rem;
            font-weight: 600;
            color: #334155;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .time-pill-btn:hover, .time-pill-btn.active {
            background: #e8f5e9;
            border-color: #1f6b4a;
            color: #1f6b4a;
        }

        .time-preview-badge {
            font-size: 0.82rem;
            font-weight: 600;
            color: #1f6b4a;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
            background: #f0fdf4;
            padding: 4px 10px;
            border-radius: 6px;
            border: 1px solid #bbf7d0;
        }

        .availability-status-container {
            margin-left: auto;
        }

        .doctor-schedule-info {
            font-size: 0.8rem;
            color: #475569;
            background: #f1f5f9;
            padding: 6px 10px;
            border-radius: 8px;
            margin-top: 6px;
            border-left: 3px solid #1f6b4a;
        }

        .modal-actions-bar {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            padding-top: 1.25rem;
            border-top: 1.5px solid #e2e8f0;
            margin-top: 1rem;
        }
    </style>

</head>

<body>

    <div class="reception-layout">

        <!-- Include Sidebar -->
        <?php include 'includes/reception_sidebar.php'; ?>

        <!-- Main Content -->
        <div class="reception-main-content">

            <!-- Include Navbar -->
            <?php
            $pageTitle = 'Appointment Management';
            include 'includes/reception_navbar.php';
            ?>

            <!-- Page Content -->
            <main class="reception-content">

                <!-- Page Header -->
                <div class="mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800" style="color: #144d34;">Appointment Management
                            </h1>
                            <p class="text-gray-600 mt-1">Schedule and manage doctor appointments</p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="appointmentManager.openModal('create')" class="btn btn-primary">
                                <i class="fas fa-plus-circle"></i>
                                New Appointment
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="mb-6">
                    <div class="premium-filter-container">
                        <div class="search-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" class="professional-input"
                                placeholder="Search by patient, ID or doctor...">
                        </div>

                        <div class="select-wrapper">
                            <i class="fas fa-user-md"></i>
                            <select id="doctorFilter" class="professional-select">
                                <option value="">Filter By Doctor</option>
                                <!-- Populated via JS -->
                            </select>
                        </div>

                        <div class="select-wrapper">
                            <i class="fas fa-filter"></i>
                            <select id="statusFilter" class="professional-select">
                                <option value="">Filter By Status</option>
                                <option value="Active">Active</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>

                        <div style="margin-left: auto;">
                            <button onclick="appointmentManager.loadAppointments()" class="btn btn-outline"
                                style="padding: 8px 15px; border-radius: 10px;" title="Refresh List">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Appointments Table -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div id="tableContainer">
                        <!-- Loading skeleton -->
                        <div id="loadingSkeleton" class="p-6 hidden">
                            <div class="skeleton h-12 mb-4"></div>
                            <div class="skeleton h-12 mb-4"></div>
                            <div class="skeleton h-12 mb-4"></div>
                        </div>

                        <!-- Actual table -->
                        <div id="appointmentTableWrapper">
                            <div style="overflow-x: auto;">
                                <table class="patient-table">
                                    <thead>
                                        <tr>
                                            <th>Patient ID</th>
                                            <th>Apt ID</th>
                                            <th>Patient</th>
                                            <th>Phone</th>
                                            <th>Doctor</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th style="text-align: center;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="appointmentTableBody">
                                        <!-- Rows inserted via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </main>

        </div>
    </div>

    <!-- Appointment Modal -->
    <div id="appointmentModal" class="ref-modal-overlay hidden">
        <div class="ref-modal-card" onclick="event.stopPropagation()" style="max-width: 900px;">
            <div class="ref-modal-header">
                <h2 id="modalTitle">New Appointment</h2>
                <button onclick="appointmentManager.closeModal()" class="ref-modal-close" title="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="ref-modal-body">
                <form id="appointmentForm">
                    <input type="hidden" id="editAppointmentId" name="appointment_id">
                    <input type="hidden" id="patientPhone" name="phone">
                    <input type="hidden" id="appointment_time_hidden" name="appointment_time" required>

                    <div class="ref-form-grid">
                        <!-- Section 1: Patient Information -->
                        <div class="ref-section-title">
                            <i class="fas fa-info-circle"></i> Basic Information
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>Reference ID</label>
                            <input type="text" id="displayAppointmentId" class="readonly-mint" value="APT-AUTO" readonly>
                        </div>

                        <div class="ref-field ref-col-3">
                            <label>Select Patient <span class="req">*</span></label>
                            <select id="patientSelect" name="patient_id" style="width: 100%;" tabindex="1">
                                <option value="">Search by Patient ID, Name or Phone...</option>
                            </select>
                        </div>

                        <div class="ref-field ref-col-4">
                            <label>Reason for Visit</label>
                            <input type="text" name="reason" placeholder="Main complaint or reason for consultation..." tabindex="2">
                        </div>

                        <!-- Section 2: Doctor & Department -->
                        <div class="ref-section-title">
                            <i class="fas fa-user-md"></i> Doctor & Department
                        </div>

                        <div class="ref-field ref-col-2">
                            <label>Doctor <span class="req">*</span></label>
                            <select id="doctorSelect" name="doctor_id" required style="width: 100%;" tabindex="3">
                                <option value="">Select Doctor...</option>
                            </select>
                            <div id="doctorScheduleInfo" class="doctor-schedule-info hidden" style="margin-top:4px; font-size:0.78rem;"></div>
                        </div>

                        <div class="ref-field ref-col-2">
                            <label>Department <span class="req">*</span></label>
                            <select id="departmentSelect" name="department_id" required style="width: 100%;" tabindex="4">
                                <option value="">Select Department...</option>
                            </select>
                        </div>

                        <!-- Section 3: Schedule & 12-Hour Time Format -->
                        <div class="ref-section-title" style="justify-content: space-between;">
                            <span><i class="fas fa-clock"></i> Schedule & Time Details</span>
                            <div id="doctorAvailabilityStatus"></div>
                        </div>

                        <div class="ref-field ref-col-2">
                            <label>Appointment Date <span class="req">*</span></label>
                            <input type="date" name="appointment_date" required tabindex="5">
                        </div>

                        <!-- 12-HOUR TIME PICKER WIDGET -->
                        <div class="ref-field ref-col-2">
                            <label>Time Slot <span class="req">*</span> <span style="font-size:0.65rem; background:#144D34; color:#fff; padding:1px 5px; border-radius:4px; margin-left:4px;">12-HOUR</span></label>
                            <div class="time-picker-card" style="background:#fff; border:1.5px solid #DEDACF; border-radius:6px; padding:0 8px; display:flex; align-items:center; gap:6px; height:32px;">
                                <select id="time12HourSelect" class="time-picker-select" style="height:24px; padding:0 4px; font-size:0.82rem; font-weight:700;" tabindex="6">
                                    <option value="01">01</option>
                                    <option value="02">02</option>
                                    <option value="03">03</option>
                                    <option value="04">04</option>
                                    <option value="05">05</option>
                                    <option value="06">06</option>
                                    <option value="07">07</option>
                                    <option value="08">08</option>
                                    <option value="09" selected>09</option>
                                    <option value="10">10</option>
                                    <option value="11">11</option>
                                    <option value="12">12</option>
                                </select>
                                <span style="font-weight:800; color:#144D34;">:</span>
                                <select id="time12MinuteSelect" class="time-picker-select" style="height:24px; padding:0 4px; font-size:0.82rem; font-weight:700;" tabindex="7">
                                    <option value="00" selected>00</option>
                                    <option value="05">05</option>
                                    <option value="10">10</option>
                                    <option value="15">15</option>
                                    <option value="20">20</option>
                                    <option value="25">25</option>
                                    <option value="30">30</option>
                                    <option value="35">35</option>
                                    <option value="40">40</option>
                                    <option value="45">45</option>
                                    <option value="50">50</option>
                                    <option value="55">55</option>
                                </select>
                                <div class="ampm-segmented-toggle" style="display:flex; gap:2px;">
                                    <button type="button" class="ampm-pill active" data-period="AM" style="padding:2px 8px; font-size:0.75rem; font-weight:700;">AM</button>
                                    <button type="button" class="ampm-pill" data-period="PM" style="padding:2px 8px; font-size:0.75rem; font-weight:700;">PM</button>
                                </div>
                                <div class="time-preview-badge" style="margin-left:auto; font-size:0.75rem; font-weight:700; color:#144D34;">
                                    <strong id="time12Preview">09:00 AM</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Section 4: Notes -->
                        <div class="ref-section-title">
                            <i class="fas fa-comment-dots"></i> Additional Notes
                        </div>

                        <div class="ref-field ref-col-4">
                            <textarea name="notes" rows="2" placeholder="Additional instructions or notes..." tabindex="8"></textarea>
                        </div>
                    </div>

                    <div class="ref-modal-footer">
                        <button type="button" onclick="appointmentManager.closeModal()" class="ref-btn-cancel" tabindex="9">Cancel</button>
                        <button type="submit" id="btnSaveOnly" class="ref-btn-submit" tabindex="10"><i class="fas fa-save"></i> Commit Changes</button>
                    </div>
                </form>

                <script>
                    // Keyboard navigation and Focus Trap
                    document.addEventListener('DOMContentLoaded', function() {
                        const modal = document.getElementById('appointmentModal');
                        if (modal) {
                            modal.addEventListener('keydown', function(e) {
                                const focusableElements = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
                                const elements = Array.from(modal.querySelectorAll(focusableElements)).filter(el => !el.disabled && el.offsetParent !== null);
                                
                                // Tab key focus trap
                                if (e.key === 'Tab') {
                                    if(elements.length > 0) {
                                        const firstElement = elements[0];
                                        const lastElement = elements[elements.length - 1];
                                        
                                        if (e.shiftKey) { // Shift + Tab
                                            if (document.activeElement === firstElement) {
                                                lastElement.focus();
                                                e.preventDefault();
                                            }
                                        } else { // Tab
                                            if (document.activeElement === lastElement) {
                                                firstElement.focus();
                                                e.preventDefault();
                                            }
                                        }
                                    }
                                }
                                
                                // Enter key acts like Tab (skip textareas and buttons)
                                if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && e.target.tagName !== 'BUTTON') {
                                    e.preventDefault();
                                    const index = elements.indexOf(document.activeElement);
                                    if (index > -1 && index < elements.length - 1) {
                                        elements[index + 1].focus();
                                    }
                                }
                            });
                        }
                    });
                </script>
            </div>
        </div>
    </div>

    <script>
        window.HOSPITAL_BRANCH = '<?= addslashes($_SESSION['hospital_branch'] ?? '') ?>';
    </script>

    <!-- Select2 JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- JavaScript -->
    <script src="assets/js/appointment.js?v=<?= time() ?>"></script>
</body>

</html>