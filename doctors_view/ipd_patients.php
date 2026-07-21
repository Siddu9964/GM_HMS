<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Doctor', 'admin', 'Admin'])) {
    header("Location: ../doctor_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPD Patients - GM HMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/doctor_dashboard.css?v=<?= time() ?>">
    <style>
        .ward-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .days-badge { padding: 2px 8px; border-radius: 4px; background: #E6FAFA; color: #144d34; font-weight: 700; font-size: 0.75rem; }
        .patient-avatar-mini { width: 32px; height: 32px; border-radius: 50%; background: #10b981; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.8rem; }
    </style>
</head>
<body>
    <div class="doctor-layout">
        <!-- Sidebar -->
        <?php include 'includes/doctor_sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="doctor-main-content">
            <!-- Top Navbar -->
            <?php include 'includes/doctor_navbar.php'; ?>
            
            <!-- Page Content -->
            <div class="doctor-content">
                <!-- Page Header -->
                <div class="welcome-banner fade-in-up">
                    <div class="d-flex" style="justify-content: space-between; align-items: center; position: relative; z-index: 2;">
                        <div>
                            <h1 class="welcome-title">
                                <i class="fas fa-procedures"></i> In-Patient Management (IPD)
                            </h1>
                            <p class="welcome-subtitle">Monitor admitted patients, bed status, and recovery progress</p>
                        </div>
                        <button onclick="refreshData()" class="btn btn-outline" style="border-color: rgba(255,255,255,0.2); color: white;">
                            <i class="fas fa-sync-alt"></i> Sync Data
                        </button>
                    </div>
                    <i class="fas fa-hospital-user welcome-icon-bg"></i>
                </div>
                
                <!-- KPI Section -->
                <div class="bento-grid">
                    <div class="bento-card gm-kpi-card col-span-3 fade-in-up delay-1">
                        <div>
                            <div class="gm-kpi-icon-wrap icon-teal"><i class="fas fa-hospital-user"></i></div>
                            <div class="gm-kpi-value" id="kpi-active">0</div>
                            <div class="gm-kpi-label">Active Admissions</div>
                        </div>
                        <i class="fas fa-procedures bg-icon"></i>
                    </div>
                    <div class="bento-card gm-kpi-card col-span-3 fade-in-up delay-1">
                        <div>
                            <div class="gm-kpi-icon-wrap icon-blue"><i class="fas fa-bed"></i></div>
                            <div class="gm-kpi-value" id="kpi-total">0</div>
                            <div class="gm-kpi-label">Total Today</div>
                        </div>
                        <i class="fas fa-bed-pulse bg-icon"></i>
                    </div>
                    <!-- Empty slots -->
                    <div class="col-span-3"></div>
                    <div class="col-span-3"></div>
                </div>

                <!-- Admissions Table -->
                <div class="bento-card fade-in-up delay-3 col-span-12">
                    <div class="card-header d-flex" style="justify-content: space-between; align-items: center;">
                        <div class="card-title">
                            <i class="fas fa-user-shield"></i>
                            Admitted Patients List
                        </div>
                        <div class="d-flex gap-2">
                            <input type="text" id="search-patients" class="form-control" placeholder="Search admitted patients...">
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table id="ipd-table" class="table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Patient Details</th>
                                        <th>Bed / Ward</th>
                                        <th>Admission Date</th>
                                        <th>Duration</th>
                                        <th>Financial Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="ipd-table-body">
                                    <!-- Data loaded via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="assets/js/doctor_utils.js"></script>
    <script src="assets/js/ipd_patients.js"></script>
</body>
</html>
