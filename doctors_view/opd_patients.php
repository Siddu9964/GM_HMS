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
    <title>OPD Patients - GM HMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/doctor_dashboard.css?v=<?= time() ?>">
    <style>
        .opd-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-scheduled { background: #e0f2fe; color: #0369a1; }
        .badge-completed { background: #dcfce7; color: #15803d; }
        .badge-cancelled { background: #fee2e2; color: #b91c1c; }
        .patient-avatar-mini { width: 32px; height: 32px; border-radius: 50%; background: var(--primary-blue); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.8rem; }
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
                                <i class="fas fa-calendar-check"></i> OPD Appointments
                            </h1>
                            <p class="welcome-subtitle">Manage your outpatient visits and clinical encounters</p>
                        </div>
                        <button onclick="refreshData()" class="btn btn-outline" style="border-color: rgba(255,255,255,0.2); color: white;">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                    <i class="fas fa-calendar-plus welcome-icon-bg"></i>
                </div>
                
                <!-- KPI Section -->
                <div class="bento-grid">
                    <div class="bento-card gm-kpi-card col-span-3 fade-in-up delay-1">
                        <div>
                            <div class="gm-kpi-icon-wrap icon-blue"><i class="fas fa-user-clock"></i></div>
                            <div class="gm-kpi-value" id="kpi-total">0</div>
                            <div class="gm-kpi-label">Total Today</div>
                        </div>
                        <i class="fas fa-users bg-icon"></i>
                    </div>
                    <div class="bento-card gm-kpi-card col-span-3 fade-in-up delay-1">
                        <div>
                            <div class="gm-kpi-icon-wrap icon-orange"><i class="fas fa-clock"></i></div>
                            <div class="gm-kpi-value" id="kpi-scheduled">0</div>
                            <div class="gm-kpi-label">Scheduled</div>
                        </div>
                        <i class="fas fa-calendar-day bg-icon"></i>
                    </div>
                    <div class="bento-card gm-kpi-card col-span-3 fade-in-up delay-2">
                        <div>
                            <div class="gm-kpi-icon-wrap icon-teal"><i class="fas fa-check-circle"></i></div>
                            <div class="gm-kpi-value" id="kpi-completed">0</div>
                            <div class="gm-kpi-label">Completed</div>
                        </div>
                        <i class="fas fa-clipboard-check bg-icon"></i>
                    </div>
                    <div class="bento-card gm-kpi-card col-span-3 fade-in-up delay-2">
                        <div>
                            <div class="gm-kpi-icon-wrap icon-red"><i class="fas fa-times-circle"></i></div>
                            <div class="gm-kpi-value" id="kpi-cancelled">0</div>
                            <div class="gm-kpi-label">Cancelled</div>
                        </div>
                        <i class="fas fa-ban bg-icon"></i>
                    </div>
                </div>
                
                <!-- Appointments Table -->
                <div class="bento-card fade-in-up delay-3 col-span-12">
                    <div class="card-header d-flex" style="justify-content: space-between; align-items: center;">
                        <div class="card-title">
                            <i class="fas fa-list"></i>
                            Encounters List
                        </div>
                        <div class="d-flex gap-2">
                            <select id="status-filter" class="form-control" onchange="applyFilters()">
                                <option value="">All Status</option>
                                <option value="Scheduled">Scheduled</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                            <input type="text" id="search-patients" class="form-control" placeholder="Search patients...">
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table id="opd-table" class="table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>ID</th>
                                        <th>Time</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="opd-table-body">
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
    <script>
        const CURRENT_DOCTOR_ID = "<?php echo $_SESSION['user_id']; ?>";
    </script>
    <script src="assets/js/opd_patients.js"></script>
</body>
</html>
