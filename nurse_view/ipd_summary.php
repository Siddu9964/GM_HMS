<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Nurse', 'admin', 'Admin'])) {
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
    <title>IPD Summary Dashboard - GM HMS</title>

    <!-- Google Fonts: Inter & Outfit -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4A90E2 0%, #357ABD 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --card-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            --accent-color: #4A90E2;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --sidebar-color: #1e293b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #f8fafc;
            color: var(--text-main);
            overflow-x: hidden;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        .outfit-font {
            font-family: 'Outfit', sans-serif;
        }

        /* Layout Enhancements */
        .main-layout {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        .content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Modern Card Styling */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1.25rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .glass-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .card-header-premium {
            padding: 1.5rem 2rem;
            background: #fff;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header-premium h5 {
            margin: 0;
            font-weight: 700;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-header-premium i {
            color: var(--accent-color);
            background: rgba(74, 144, 226, 0.1);
            padding: 0.6rem;
            border-radius: 0.75rem;
        }

        /* Search Section Redesign */
        .search-container {
            padding: 2.5rem;
            background: white;
            border-radius: 1.5rem;
            margin-bottom: 2.5rem;
            box-shadow: var(--card-shadow);
        }

        .search-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--text-main);
        }

        /* Stats & Stats Components */
        .stats-badge-container {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stats-pill {
            flex: 1;
            padding: 1rem 1.25rem;
            background: white;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid #f1f5f9;
            height: 100%;
        }

        .stats-pill .icon {
            width: 42px;
            height: 42px;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .stats-pill.blue .icon {
            background: rgba(74, 144, 226, 0.1);
            color: #4A90E2;
        }

        .stats-pill.green .icon {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .stats-pill.purple .icon {
            background: rgba(30, 41, 59, 0.1);
            color: #1e293b;
        }

        .stats-pill .info h4 {
            font-size: 1.1rem;
            font-weight: 800;
            margin: 0;
            color: var(--text-main);
        }

        .stats-pill .info p {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin: 0;
            font-weight: 700;
        }

        /* Timeline Compact Redesign */
        .modern-timeline {
            position: relative;
            padding: 1rem 0;
        }

        .modern-timeline::before {
            content: '';
            position: absolute;
            left: 1.5rem;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #e2e8f0;
            border-radius: 4px;
        }

        .timeline-row {
            position: relative;
            padding-left: 3.5rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .timeline-row:hover {
            transform: translateX(8px);
        }

        .timeline-dot {
            position: absolute;
            left: 1.05rem;
            top: 50%;
            transform: translateY(-50%);
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            background: white;
            border: 3px solid var(--accent-color);
            z-index: 2;
            box-shadow: 0 0 0 4px white;
        }

        .timeline-row:hover .timeline-dot {
            background: var(--accent-color);
            box-shadow: 0 0 0 6px rgba(74, 144, 226, 0.2);
        }

        .summary-card {
            background: white;
            border-radius: 1rem;
            padding: 1rem 1.5rem;
            border: 1px solid #f1f5f9;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .summary-card .date-section {
            min-width: 110px;
            border-right: 2px solid #f1f5f9;
            padding-right: 1rem;
        }

        .summary-card .assessment-preview {
            flex-grow: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* Modal Premium Styling */
        .modal-clinical {
            border: none;
            border-radius: 1.5rem;
            overflow: hidden;
        }

        .modal-clinical .modal-header {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 1.5rem 2rem;
        }

        .modal-clinical .modal-body {
            padding: 2.5rem;
            background: #f8fafc;
            max-height: 70vh;
            overflow-y: auto;
        }

        /* Custom Scrollbar for Modal Body */
        .modal-clinical .modal-body::-webkit-scrollbar {
            width: 6px;
        }

        .modal-clinical .modal-body::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        .modal-clinical .modal-body::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .modal-clinical .modal-body::-webkit-scrollbar-thumb:hover {
            background: var(--accent-color);
        }

        .assessment-box {
            background: white;
            border-left: 5px solid var(--accent-color);
            padding: 2rem;
            border-radius: 0 1rem 1rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
        }

        .procedure-pill {
            background: #fff;
            border: 1px solid #e2e8f0;
            padding: 0.75rem 1.25rem;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .procedure-pill .time {
            color: var(--accent-color);
            font-weight: 800;
            border-right: 2px solid #e2e8f0;
            padding-right: 0.75rem;
            font-family: 'Monaco', 'Consolas', monospace;
        }

        /* Clinical View Enhancements */
        .clinical-view-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .tracking-wider {
            letter-spacing: 0.1em;
        }

        .rounded-xl {
            border-radius: 1rem;
        }

        .shadow-sm {
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
        }

        .italic {
            font-style: italic;
        }

        .text-slate-400 {
            color: #94a3b8;
        }

        .text-slate-500 {
            color: #64748b;
        }

        .text-slate-600 {
            color: #475569;
        }

        .border-slate-300 {
            border-color: #cbd5e1 !important;
        }

        .report-section-title {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Button Styling */
        .btn-action {
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary-gradient {
            background: var(--primary-gradient);
            color: white;
            border: none;
            box-shadow: 0 4px 6px -1px rgba(74, 144, 226, 0.3);
        }

        .btn-primary-gradient:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 15px -3px rgba(74, 144, 226, 0.4);
            color: white;
        }

        /* Form Controls */
        .form-label-sharp {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .input-premium {
            padding: 0.8rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            transition: all 0.2s;
        }

        .input-premium:focus {
            background: white;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 4px rgba(74, 144, 226, 0.1);
            outline: none;
        }

        /* Loading Animation */
        .pulse-loader {
            width: 60px;
            height: 60px;
            border: 5px solid rgba(74, 144, 226, 0.1);
            border-top: 5px solid var(--accent-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Animations */
        .fade-in-up {
            animation: fadeInUp 0.5s ease-out forwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Floating Alerts */
        #alertContainer {
            position: fixed;
            top: 2rem;
            right: 2rem;
            z-index: 9999;
            min-width: 320px;
            max-width: 450px;
        }

        .alert-premium {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            animation: slideInRight 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Print Optimization */
        @media print {

            .nurse-sidebar,
            .nurse-navbar,
            .search-container,
            .btn-action,
            .glass-card:has(#dailyReportForm),
            .card-header-premium .btn-action,
            #alertContainer,
            .welcome-state,
            #welcomeState,
            .col-xl-5 {
                display: none !important;
            }

            .content-wrapper {
                margin: 0 !important;
                padding: 0 !important;
            }

            .col-xl-7 {
                width: 100% !important;
                flex: 0 0 100% !important;
                max-width: 100% !important;
            }

            .glass-card {
                border: none !important;
                box-shadow: none !important;
                background: white !important;
            }

            .timeline-block {
                opacity: 1 !important;
                transform: none !important;
                break-inside: avoid;
            }

            .timeline-content-card {
                border: 1px solid #ddd !important;
                break-inside: avoid;
            }

            .modern-timeline::before {
                background: #ddd !important;
            }
        }

        /* Helper Classes */
        .bg-glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
        }

        .text-accent {
            color: var(--accent-color);
        }

        .report-section-title {
            font-family: 'Outfit', sans-serif;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .min-vh-10 {
            min-height: 100px;
        }

        .rounded-xl {
            border-radius: 1rem;
        }
    </style>
</head>

<body>
    <div class="main-layout">
        <!-- Sidebar -->
        <?php include 'includes/nurse_sidebar.php'; ?>

        <!-- Main Content -->
        <div class="content-wrapper">
            <!-- Top Navbar -->
            <?php
            $pageTitle = 'IPD Summary Dashboard';
            include 'includes/nurse_navbar.php';
            ?>

            <main class="main-content">
                <div class="container-fluid py-4">

                    <!-- Top Search Bar -->
                    <div class="glass-card p-4">
                        <div class="row align-items-center">
                            <div class="col-lg-1 d-none d-lg-block text-center">
                                <div class="icon-circle bg-primary bg-opacity-10 p-3 rounded-xl">
                                    <i class="fas fa-search text-primary fs-3"></i>
                                </div>
                            </div>
                            <div class="col-lg-8 col-md-9">
                                <div class="px-lg-3">
                                    <h4 class="fw-bold mb-1">Search IPD Admission</h4>
                                    <p class="text-muted small mb-0">Search by Patient Name, ID, or <b>Mobile Number</b>
                                        to view history</p>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-3 text-md-end mt-3 mt-md-0">
                                <select id="searchIpdNo" class="form-select select2-selection--single"
                                    style="width: 100%;">
                                    <option value="">Search by Mobile / Name...</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="alertContainer"></div>

                    <!-- Main Dashboard Section -->
                    <div id="patientDashboard" style="display: none;">

                        <!-- Quick Stats -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="stats-pill blue h-100">
                                    <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                                    <div class="info">
                                        <h4 id="totalDays">0</h4>
                                        <p>Total Days</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-pill green h-100">
                                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                                    <div class="info">
                                        <h4 id="patientStatus" style="font-size: 1rem;">Active</h4>
                                        <p>Patient Status</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-pill purple h-100">
                                    <div class="icon"><i class="fas fa-bed"></i></div>
                                    <div class="info">
                                        <h4 id="displayLocation" style="font-size: 0.9rem;">---</h4>
                                        <p>Ward / Bed</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="glass-card m-0 p-3 bg-primary text-white h-100"
                                    style="background: var(--primary-gradient) !important;">
                                    <div class="h-100 d-flex flex-column justify-content-center py-1">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="opacity-75 small fw-medium text-uppercase letter-spacing-1"
                                                style="font-size: 0.65rem;">Patient Identity</span>
                                            <span class="fw-bold" id="displayPatientId"
                                                style="font-size: 0.95rem;">---</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="opacity-75 small fw-medium text-uppercase letter-spacing-1"
                                                style="font-size: 0.65rem;">IPD Ref ID</span>
                                            <span class="fw-bold" id="displayIpdNo"
                                                style="font-size: 0.95rem;">---</span>
                                        </div>
                                        <div class="pt-2 border-top border-white border-opacity-20">
                                            <div class="d-flex align-items-center gap-2 overflow-hidden">
                                                <i class="fas fa-stethoscope opacity-75 small"></i>
                                                <span id="displayDiagnosis" class="fw-bold small text-truncate"
                                                    style="font-size: 0.75rem;">---</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4">
                            <!-- Layout: Full Width Patient Journey -->
                            <div class="col-12">
                                <div class="glass-card mb-0">
                                    <div class="card-header-premium">
                                        <h5><i class="fas fa-history"></i> Patient Journey Timeline</h5>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="position-relative d-none d-lg-block">
                                                <i
                                                    class="fas fa-filter position-absolute top-50 start-0 translate-middle-y ms-3 text-muted small"></i>
                                                <input type="text" id="timelineFilter"
                                                    class="form-control form-control-sm rounded-pill ps-5"
                                                    placeholder="Filter journey..." style="width: 180px;">
                                            </div>
                                            <div
                                                class="badge rounded-pill bg-light text-dark border px-3 d-none d-md-block">
                                                Admitted: <span id="displayAdmissionDate" class="fw-bold">---</span>
                                            </div>
                                            <button onclick="showAddReportForm()" id="addReportBtn"
                                                class="btn btn-action btn-sm btn-primary-gradient mx-2">
                                                <i class="fas fa-plus"></i> New Entry
                                            </button>
                                            <button onclick="window.print()"
                                                class="btn btn-sm btn-outline-secondary rounded-pill">
                                                <i class="fas fa-print me-1"></i> Print
                                            </button>
                                        </div>
                                    </div>

                                    <div class="card-body p-4">
                                        <div id="loadingSpinner" class="loading-spinner py-5">
                                            <div class="d-flex flex-column align-items-center">
                                                <div class="pulse-loader mb-3"></div>
                                                <p class="fw-bold text-accent">Syncing patient data...</p>
                                            </div>
                                        </div>

                                        <div id="timelineContainer" class="modern-timeline"></div>

                                        <div id="emptyState" class="empty-state">
                                            <i class="fas fa-clipboard-check mb-4"></i>
                                            <h3>Records Clear</h3>
                                            <p>No daily reports have been archived yet for this admission ID.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Welcome / Default State -->
                    <div id="welcomeState" class="text-center py-5">
                        <img src="https://cdni.iconscout.com/illustration/premium/thumb/medical-records-illustration-download-in-svg-png-gif-formats--folder-patient-report-prescription-file-document-analysis-pack-lab-illustrations-3315750.png"
                            style="width: 300px; filter: grayscale(0.2); opacity: 0.8;" alt="Medical Records">
                        <h2 class="fw-bold mt-4">IPD Clinical Intelligence</h2>
                        <p class="text-muted mx-auto" style="max-width: 500px;">
                            Access complete patient history, document daily physician visits, and monitor nursing
                            procedures through our unified summary dashboard.
                        </p>
                        <div class="d-flex justify-content-center gap-3 mt-4">
                            <div class="badge bg-light text-dark p-2 px-3 rounded-pill border"><i
                                    class="fas fa-shield-alt text-success me-2"></i> HIPAA Compliant</div>
                            <div class="badge bg-light text-dark p-2 px-3 rounded-pill border"><i
                                    class="fas fa-sync text-primary me-2"></i> Real-time Sync</div>
                        </div>
                    </div> <!-- End Welcome State -->
                </div> <!-- End Container Fluid -->
            </main> <!-- End Main Content -->
        </div> <!-- End Content Wrapper -->
    </div> <!-- End Main Layout -->

    <!-- Modern Documentation Modal -->
    <div class="modal fade" id="addReportModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content modal-clinical">
                <div class="modal-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-2 bg-white bg-opacity-25 rounded-lg text-white">
                            <i class="fas fa-file-edit fs-4"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold">Clinical Documentation</h5>
                            <small class="opacity-75">Archive new physician assessment</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light bg-opacity-50">
                    <form id="dailyReportForm" class="row g-4">
                        <!-- Top Row: Logistics & Provider -->
                        <div class="col-12">
                            <div class="glass-card m-0 p-4 border-0 shadow-sm">
                                <div class="row g-4">
                                    <div class="col-md-6 border-end">
                                        <p class="report-section-title text-primary"><i class="fas fa-clock"></i> Visit Timestamp</p>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <label class="form-label-sharp">Date</label>
                                                <input type="date" id="reportDate" class="form-control input-premium" required>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label-sharp">Time</label>
                                                <input type="time" id="visitTime" class="form-control input-premium" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="report-section-title text-success"><i class="fas fa-user-md"></i> Attending Physician</p>
                                        <div class="row g-2">
                                            <div class="col-4">
                                                <label class="form-label-sharp">ID</label>
                                                <input type="text" id="doctorId" class="form-control input-premium" placeholder="ID" required>
                                            </div>
                                            <div class="col-8">
                                                <label class="form-label-sharp">Physician Name</label>
                                                <input type="text" id="doctorName" class="form-control input-premium" placeholder="Dr. Name" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Main Clinical Content -->
                        <div class="col-12">
                            <div class="glass-card m-0 p-4 border-0 shadow-sm">
                                <p class="report-section-title text-info"><i class="fas fa-notes-medical"></i> Assessment & Plan</p>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label-sharp">Daily Summary / Assessment</label>
                                        <textarea id="dailySummary" class="form-control input-premium" rows="3" placeholder="Primary findings and assessment..." required></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label-sharp">Treatment Changes</label>
                                        <textarea id="medicalChanges" class="form-control input-premium" rows="2" placeholder="Medication or treatment protocol changes..."></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label-sharp">Physical Observations</label>
                                        <textarea id="observations" class="form-control input-premium" rows="2" placeholder="New symptoms or vital trends..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Procedures Section -->
                        <div class="col-12">
                            <div class="glass-card m-0 p-4 border-0 shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <p class="report-section-title text-warning m-0"><i class="fas fa-syringe"></i> Nursing Activities</p>
                                    <button type="button" onclick="addProcedureRow()" class="btn btn-sm btn-outline-warning rounded-pill px-3">
                                        <i class="fas fa-plus"></i> Add Item
                                    </button>
                                </div>
                                <div id="proceduresContainer" class="p-2 border rounded-xl bg-light bg-opacity-50 min-vh-10">
                                    <div class="text-center py-3 no-procedures-msg">
                                        <p class="small text-muted mb-0">No procedures logged for this session.</p>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label class="form-label-sharp">Confidential Notes (Staff Only)</label>
                                    <textarea id="additionalNotes" class="form-control input-premium" rows="1" placeholder="Internal communication..."></textarea>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-white border-top p-3 d-flex gap-2">
                    <button type="button" class="btn btn-action btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="dailyReportForm" class="btn btn-action btn-primary-gradient flex-grow-1 justify-content-center py-3">
                        <i class="fas fa-cloud-upload-alt"></i> Commit Clinical Record
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Report Modal -->
    <div class="modal fade" id="reportDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content modal-clinical">
                <div class="modal-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-2 bg-white bg-opacity-25 rounded-lg text-white">
                            <i class="fas fa-file-medical-alt fs-4"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold" id="modalReportDate">Jan 24, 2026</h5>
                            <small class="opacity-75" id="modalReportTime">Visit Time: 10:30 AM</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalReportBody">
                    <!-- Populated via JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery (required for Select2) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- Bootstrap JS Bundle (Required for Modals) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // Configuration
        const API_BASE_URL = '/GM_HMS/controler/api/ipd-summary-api.php';
        let currentIpdNo = null;
        let isDischarged = false;
        let currentAdmissionData = null;
        let timelineReports = []; // Store reports for modal access

        // Initialize
        document.addEventListener('DOMContentLoaded', function () {
            // Set today's date as default
            const now = new Date();
            document.getElementById('reportDate').valueAsDate = now;

            // Auto-fill time (HH:mm format)
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            document.getElementById('visitTime').value = `${hours}:${minutes}`;

            // Initialize Select2
            initializeSelect2();

            // Load active admissions
            loadActiveAdmissions();

            // Form submission
            document.getElementById('dailyReportForm').addEventListener('submit', function (e) {
                e.preventDefault();
                submitDailyReport();
            });

            // Timeline Filter Logic
            document.getElementById('timelineFilter').addEventListener('input', function (e) {
                const query = e.target.value.toLowerCase();
                const rows = document.querySelectorAll('.timeline-row');

                rows.forEach(row => {
                    const content = row.textContent.toLowerCase();
                    if (content.includes(query)) {
                        row.style.display = 'block';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });

        // Initialize Select2 dropdown
        function initializeSelect2() {
            $('#searchIpdNo').select2({
                theme: 'bootstrap-5',
                placeholder: 'Type Mobile, Name or ID...',
                allowClear: true,
                width: '100%'
            });

            // Auto-load when selection changes
            $('#searchIpdNo').on('change', function () {
                const selectedValue = $(this).val();
                if (selectedValue) {
                    loadDailyReports();
                }
            });
        }

        // Load active IPD admissions
        async function loadActiveAdmissions() {
            try {
                const response = await fetch(`${API_BASE_URL}/admissions?status=Admitted`);
                const data = await response.json();

                if (data.success && data.data) {
                    const select = $('#searchIpdNo');

                    // Clear existing options except the first one
                    select.find('option:not(:first)').remove();

                    // Add new options
                    data.data.forEach(admission => {
                        const option = new Option(
                            admission.display_text,
                            admission.admission_id,
                            false,
                            false
                        );

                        // Store additional data
                        $(option).data('admission', admission);
                        select.append(option);
                    });

                    // Trigger change to update Select2
                    select.trigger('change');

                    console.log(`Loaded ${data.data.length} active admissions`);
                } else {
                    console.error('Failed to load admissions:', data.error);
                }
            } catch (error) {
                console.error('Error loading admissions:', error);
                showAlert('Failed to load admissions list', 'warning');
            }
        }

        // Show floating premium alert
        function showAlert(message, type = 'success') {
            const container = document.getElementById('alertContainer');
            const alertId = 'alert_' + Date.now();

            const icon = type === 'success' ? 'check-circle' :
                (type === 'danger' ? 'times-circle' : 'exclamation-circle');

            const alertHtml = `
                <div id="${alertId}" class="alert-premium alert-${type} text-white bg-${type === 'danger' ? 'danger' : (type === 'warning' ? 'warning' : 'primary')}">
                    <i class="fas fa-${icon} fs-4"></i>
                    <div class="flex-grow-1">
                        <div class="fw-bold">${type.charAt(0).toUpperCase() + type.slice(1)}</div>
                        <div class="small">${message}</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" onclick="this.parentElement.remove()"></button>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', alertHtml);

            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const alert = document.getElementById(alertId);
                if (alert) {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateX(20px)';
                    alert.style.transition = 'all 0.4s';
                    setTimeout(() => alert.remove(), 400);
                }
            }, 5000);
        }

        // Load daily reports
        async function loadDailyReports() {
            const ipdNo = $('#searchIpdNo').val();

            if (!ipdNo) {
                document.getElementById('patientDashboard').style.display = 'none';
                document.getElementById('welcomeState').style.display = 'block';
                return;
            }

            // Get selected admission data from dropdown metadata
            const selectedOption = $('#searchIpdNo').find(':selected');
            currentAdmissionData = selectedOption.data('admission');

            if (currentAdmissionData) {
                // Populate basic info immediately from dropdown data for better UX
                document.getElementById('displayIpdNo').textContent = currentAdmissionData.admission_id || '---';
                document.getElementById('displayPatientId').textContent = currentAdmissionData.patient_id || '---';
                document.getElementById('displayAdmissionDate').textContent = formatDateTime(currentAdmissionData.admission_date);

                // Pre-fill doctor fields in the form
                document.getElementById('doctorId').value = currentAdmissionData.doctor_id || '';
                document.getElementById('doctorName').value = currentAdmissionData.doctor_name || '';
            }

            currentIpdNo = ipdNo;

            // UI State Transitions
            document.getElementById('welcomeState').style.display = 'none';
            document.getElementById('patientDashboard').style.display = 'block';
            document.getElementById('loadingSpinner').style.display = 'block';
            document.getElementById('timelineContainer').innerHTML = '';
            document.getElementById('emptyState').style.display = 'none';

            try {
                const response = await fetch(`${API_BASE_URL}/daily-reports?ipd_no=${ipdNo}`);
                const data = await response.json();

                if (data.success && data.data) {
                    displayPatientInfo(data.data);
                    displayTimeline(data.data.daily_reports);
                    isDischarged = data.data.is_discharged;

                    // Update doctor info if API has more specific/recent data
                    if (data.data.doctor_id) document.getElementById('doctorId').value = data.data.doctor_id;
                    if (data.data.doctor_name) document.getElementById('doctorName').value = data.data.doctor_name;

                    // Disable add button if discharged
                    const addBtn = document.getElementById('addReportBtn');
                    addBtn.disabled = isDischarged;
                    if (isDischarged) {
                        addBtn.innerHTML = '<i class="fas fa-lock"></i> Admission Closed';
                        addBtn.classList.remove('btn-primary-gradient');
                        addBtn.classList.add('btn-secondary');
                        showAlert('Patient has been discharged. Documentation is locked.', 'warning');
                    } else {
                        addBtn.innerHTML = '<i class="fas fa-plus"></i> New Report';
                        addBtn.classList.add('btn-primary-gradient');
                        addBtn.classList.remove('btn-secondary');
                    }
                } else {
                    document.getElementById('emptyState').style.display = 'block';
                }
            } catch (error) {
                console.error('Error fetching reports:', error);
                showAlert('Could not sync historical records, but you can still add new documentation.', 'warning');
            } finally {
                document.getElementById('loadingSpinner').style.display = 'none';
            }
        }

        // Display patient info
        function displayPatientInfo(data) {
            document.getElementById('totalDays').textContent = data.total_days || 0;
            document.getElementById('patientStatus').textContent = data.is_discharged ? 'Discharged' : 'Active';
            document.getElementById('displayIpdNo').textContent = data.ipd_no;
            document.getElementById('displayPatientId').textContent = data.patient_id;
            document.getElementById('displayAdmissionDate').textContent = formatDateTime(data.admission_date);

            // New Location Info
            const location = `${data.ward || 'General'} / ${data.bed_no || '---'}`;
            document.getElementById('displayLocation').textContent = location;
            document.getElementById('displayDiagnosis').textContent = data.provisional_diagnosis || '---';

            // Update status color
            const statusBox = document.querySelector('.stats-pill.green .icon');
            if (data.is_discharged) {
                statusBox.parentElement.classList.replace('green', 'purple');
                statusBox.innerHTML = '<i class="fas fa-door-open"></i>';
            } else {
                statusBox.parentElement.classList.replace('purple', 'green');
                statusBox.innerHTML = '<i class="fas fa-check-circle"></i>';
            }
        }

        // Render Patient Journey Timeline (Summary List)
        function displayTimeline(reports) {
            const container = document.getElementById('timelineContainer');
            timelineReports = reports; // Cache globally

            if (!reports || reports.length === 0) {
                document.getElementById('emptyState').style.display = 'block';
                container.innerHTML = '';
                return;
            }

            document.getElementById('emptyState').style.display = 'none';

            container.innerHTML = reports.map((report, index) => {
                const dateStr = formatDate(report.date);
                const visitTime = report.doctor_visit ? report.doctor_visit.visit_time : '---';
                const summary = report.doctor_visit ? report.doctor_visit.summary : (report.observations || 'No summary provided');

                return `
                    <div class="timeline-row" onclick="showReportDetails(${index})">
                        <div class="timeline-dot"></div>
                        <div class="summary-card">
                            <div class="date-section">
                                <span class="fw-bold d-block text-dark small">${dateStr}</span>
                                <span class="text-accent fw-bold" style="font-size: 0.75rem;">
                                    <i class="far fa-clock me-1"></i> ${visitTime}
                                </span>
                            </div>
                            <div class="assessment-preview">
                                <span class="badge domain-physician me-2" style="font-size: 0.6rem;">ASSESSMENT</span>
                                ${summary}
                            </div>
                            <div class="ms-auto">
                                <i class="fas fa-chevron-right text-muted opacity-50"></i>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Show detailed report in Modal
        function showReportDetails(index) {
            const report = timelineReports[index];
            if (!report) return;

            // Cache modal element to prevent duplicate instances
            const modalEl = document.getElementById('reportDetailsModal');
            let modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (!modalInstance) {
                modalInstance = new bootstrap.Modal(modalEl);
            }

            document.getElementById('modalReportDate').textContent = formatDate(report.date);
            document.getElementById('modalReportTime').textContent = `Clinical Session: ${report.doctor_visit ? report.doctor_visit.visit_time : '---'}`;

            let html = `
                <div class="clinical-view-container">
                    <!-- Quick Stats Header -->
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="p-3 bg-white border rounded-xl shadow-sm d-flex align-items-center gap-3">
                                <div class="p-2 bg-primary bg-opacity-10 rounded-circle text-primary"><i class="fas fa-user-md"></i></div>
                                <div>
                                    <small class="text-muted d-block">Recording Physician</small>
                                    <span class="fw-bold small">${report.doctor_visit ? report.doctor_visit.doctor_name : '---'}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-white border rounded-xl shadow-sm d-flex align-items-center gap-3">
                                <div class="p-2 bg-info bg-opacity-10 rounded-circle text-info"><i class="fas fa-calendar-check"></i></div>
                                <div>
                                    <small class="text-muted d-block">Status</small>
                                    <span class="badge bg-success bg-opacity-10 text-success fw-bold">Validated</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Physician Section -->
                    ${report.doctor_visit ? `
                        <div class="assessment-box mb-4">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <div class="badge bg-primary rounded-pill px-3 py-2 shadow-sm">Assessment</div>
                            </div>
                            <p class="mb-0 text-dark fw-medium" style="line-height: 1.8; font-size: 1rem;">
                                ${report.doctor_visit.summary}
                            </p>
                        </div>
                    ` : ''}

                    <!-- Observations & Rx Section -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="bg-white p-4 rounded-xl border h-100 shadow-sm">
                                <div class="d-flex align-items-center gap-2 mb-3 text-info">
                                    <div class="p-2 bg-info bg-opacity-10 rounded-lg"><i class="fas fa-stethoscope"></i></div>
                                    <span class="small fw-bold text-uppercase tracking-wider">Clinical Observations</span>
                                </div>
                                <div class="text-muted" style="font-size: 0.95rem; line-height: 1.6;">
                                    ${report.observations || '<span class="opacity-50 italic">No specific vitals/observations recorded.</span>'}
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="bg-white p-4 rounded-xl border h-100 shadow-sm">
                                <div class="d-flex align-items-center gap-2 mb-3 text-success">
                                    <div class="p-2 bg-success bg-opacity-10 rounded-lg"><i class="fas fa-pills"></i></div>
                                    <span class="small fw-bold text-uppercase tracking-wider">Pharmacology / Rx Plan</span>
                                </div>
                                <div class="text-muted" style="font-size: 0.95rem; line-height: 1.6;">
                                    ${report.medical_changes || '<span class="opacity-50 italic">Maintain current medication protocol.</span>'}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nursing Section -->
                    ${report.nurse_procedures && report.nurse_procedures.length > 0 ? `
                        <div class="bg-white p-4 rounded-xl border shadow-sm mb-4">
                            <div class="d-flex align-items-center gap-2 mb-4 text-warning">
                                <div class="p-2 bg-warning bg-opacity-10 rounded-lg"><i class="fas fa-syringe"></i></div>
                                <span class="fw-bold text-uppercase tracking-wider">Nursing & Procedural Log</span>
                            </div>
                            <div class="row g-3">
                                ${report.nurse_procedures.map(proc => `
                                    <div class="col-md-6">
                                        <div class="procedure-pill border-0 bg-light p-3">
                                            <span class="time py-0">${proc.time}</span>
                                            <span class="small fw-bold text-dark ms-2">${proc.procedure}</span>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}

                    <!-- Internal Staff Notes -->
                    ${report.extra_data && report.extra_data.notes ? `
                        <div class="p-3 bg-light rounded-xl border-start border-4 border-slate-300 d-flex gap-3">
                            <i class="fas fa-comment-medical text-slate-400 mt-1"></i>
                            <div>
                                <small class="fw-bold text-slate-500 d-block mb-1">Administrative / Clinical Notes</small>
                                <p class="small text-slate-600 mb-0">${report.extra_data.notes}</p>
                            </div>
                        </div>
                    ` : ''}
                </div>
            `;

            document.getElementById('modalReportBody').innerHTML = html;
            modalInstance.show();
        }

        // Show add report form (Modal Launcher)
        function showAddReportForm() {
            if (isDischarged) {
                showAlert('Admission Locked: Patient has been discharged.', 'danger');
                return;
            }

            // Set today's date if not set
            if (!document.getElementById('reportDate').value) {
                const now = new Date();
                document.getElementById('reportDate').valueAsDate = now;
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                document.getElementById('visitTime').value = `${hours}:${minutes}`;
            }

            const modal = new bootstrap.Modal(document.getElementById('addReportModal'));
            modal.show();
        }

        // Hide add report form
        function hideAddReportForm() {
            const modalEl = document.getElementById('addReportModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            document.getElementById('dailyReportForm').reset();
            document.getElementById('proceduresContainer').innerHTML = `
                <div class="text-center py-3 no-procedures-msg">
                    <p class="small text-muted mb-0">No procedures logged for this session.</p>
                </div>
            `;
        }

        // Add procedure row
        function addProcedureRow() {
            const container = document.getElementById('proceduresContainer');
            const msg = container.querySelector('.no-procedures-msg');
            if (msg) msg.style.display = 'none';

            const row = document.createElement('div');
            row.className = 'row g-2 mb-2 procedure-row align-items-center bg-white p-2 rounded-lg border shadow-sm mx-0 fade-in-up';
            row.innerHTML = `
                <div class="col-md-3">
                    <input type="time" class="form-control form-control-sm border-0 bg-light procedure-time" placeholder="Time">
                </div>
                <div class="col-md-8">
                    <input type="text" class="form-control form-control-sm border-0 bg-light procedure-desc" placeholder="Procedural description...">
                </div>
                <div class="col-md-1 text-end">
                    <button type="button" class="btn btn-link btn-sm text-danger p-0" onclick="removeProcedure(this)">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            `;
            container.appendChild(row);
        }

        // Remove procedure row
        function removeProcedure(button) {
            const container = document.getElementById('proceduresContainer');
            button.closest('.procedure-row').remove();

            if (container.querySelectorAll('.procedure-row').length === 0) {
                const msg = container.querySelector('.no-procedures-msg');
                if (msg) msg.style.display = 'block';
            }
        }

        // Submit daily report
        async function submitDailyReport() {
            if (!currentIpdNo) {
                showAlert('Please load an IPD admission first', 'danger');
                return;
            }

            // Target the specific submit button inside the form
            const form = document.getElementById('dailyReportForm');
            const submitBtn = form.querySelector('button[type="submit"]');

            // Collect procedures
            const procedures = [];
            form.querySelectorAll('.procedure-row').forEach(row => {
                const time = row.querySelector('.procedure-time').value;
                const desc = row.querySelector('.procedure-desc').value;
                if (time && desc) {
                    procedures.push({ time, procedure: desc });
                }
            });

            // Prepare data
            const reportData = {
                ipd_no: currentIpdNo,
                patient_id: document.getElementById('displayPatientId').textContent.trim(),
                doctor_id: document.getElementById('doctorId').value.trim(),
                date: document.getElementById('reportDate').value,
                doctor_visit: {
                    doctor_id: document.getElementById('doctorId').value.trim(),
                    doctor_name: document.getElementById('doctorName').value.trim(),
                    visit_time: document.getElementById('visitTime').value,
                    summary: document.getElementById('dailySummary').value.trim()
                },
                medical_changes: document.getElementById('medicalChanges').value.trim(),
                observations: document.getElementById('observations').value.trim(),
                nurse_procedures: procedures,
                extra_data: {
                    notes: document.getElementById('additionalNotes').value.trim()
                }
            };

            try {
                // UI Loading State
                const originalContent = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Archiving Record...';

                console.log('Submitting Report:', reportData);

                const response = await fetch(`${API_BASE_URL}/daily-reports`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(reportData)
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('Daily report archived successfully!', 'success');
                    hideAddReportForm();
                    // Smooth scroll to top and reload
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    setTimeout(() => loadDailyReports(), 500);
                } else {
                    showAlert(data.error || 'Failed to archive report. Please check mandatory fields.', 'danger');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalContent;
                }

            } catch (error) {
                console.error('Submission Error:', error);
                showAlert('Connection error. Please verify your internet and try again.', 'danger');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalContent;
            }
        }

        // Format date
        function formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        // Format datetime
        function formatDateTime(dateStr) {
            if (!dateStr) return '---';
            const date = new Date(dateStr);
            return date.toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    </script>
</body>

</html>