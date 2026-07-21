<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Receptionist', 'admin', 'Admin'])) {
    header("Location: ../../../receptionist_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inpatients - GM HMS</title>

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
    <link rel="stylesheet" href="../../assets/css/reception_dashboard.css?v=<?= time() ?>">

    <!-- Custom IPD CSS -->
    <link rel="stylesheet" href="assets/css/ipd_main.css?v=<?= time() ?>">

    <style>
        .quick-action-btn {
            width: 100%;
            padding: 20px;
            margin-bottom: 15px;
            font-size: 1.1rem;
            border-radius: 12px;
            transition: all 0.3s;
        }

        .quick-action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>

<body>
    <div class="reception-layout">
        <!-- Sidebar -->
        <?php include '../../includes/reception_sidebar.php'; ?>

        <!-- Main Content -->
        <div class="reception-main-content">
            <!-- Top Navbar -->
            <?php
            $pageTitle = 'Inpatients';
            include '../../includes/reception_navbar.php';
            ?>

            <!-- Dashboard Content -->
            <div class="reception-content">
                <!-- IPD Dashboard Header -->
                <div style="margin-bottom: 1.5rem;">
                    <h1 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 0.25rem; color: #1f6b4a;">
                        <i class="fas fa-hospital-user"></i> Inpatient Services
                    </h1>
                    <p style="color: #6b7280; font-size: 0.875rem;">Admissions, bed occupancy and payments overview
                    </p>
                </div>

                <!-- Dashboard Top Section: KPIs + Quick Actions Side by Side -->
                <div class="dashboard-top-section" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
                    
                    <!-- Left: Stats Cards -->
                    <div>
                        <h2 class="section-heading" style="font-size: 1.1rem; color: #64748b; margin-bottom: 1rem;"><i class="fas fa-chart-pie"></i> Overview</h2>
                        <div class="kpi-cards-grid" id="statsGrid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1rem;">
                            <!-- Active Admissions -->
                            <div class="kpi-card card border-0 shadow-sm" style="width: 100% !important; max-width: none !important;">
                                <div class="kpi-icon-wrapper"><i class="fas fa-bed"></i></div>
                                <div class="kpi-content-inline">
                                    <span class="kpi-card-value" id="activeAdmissions">-</span>
                                    <span class="kpi-card-label">Active Admissions</span>
                                </div>
                            </div>
                            
                            <!-- Bed Occupancy -->
                            <div class="kpi-card card border-0 shadow-sm" style="width: 100% !important; max-width: none !important;">
                                <div class="kpi-icon-wrapper"><i class="fas fa-procedures"></i></div>
                                <div class="kpi-content-inline">
                                    <span class="kpi-card-value" id="bedOccupancy">-</span>
                                    <span class="kpi-card-label">Bed Occupancy</span>
                                </div>
                            </div>
                            
                            <!-- Admissions Today -->
                            <div class="kpi-card card border-0 shadow-sm" style="width: 100% !important; max-width: none !important;">
                                <div class="kpi-icon-wrapper"><i class="fas fa-user-plus"></i></div>
                                <div class="kpi-content-inline">
                                    <span class="kpi-card-value" id="admissionsToday">-</span>
                                    <span class="kpi-card-label">Admissions Today</span>
                                </div>
                            </div>
                            
                            <!-- Payments Today -->
                            <div class="kpi-card card border-0 shadow-sm" style="width: 100% !important; max-width: none !important;">
                                <div class="kpi-icon-wrapper"><i class="fas fa-rupee-sign"></i></div>
                                <div class="kpi-content-inline">
                                    <span class="kpi-card-value" id="paymentsToday">-</span>
                                    <span class="kpi-card-label">Payments Today</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Quick Actions -->
                    <div>
                        <h2 class="section-heading" style="font-size: 1.1rem; color: #64748b; margin-bottom: 1rem;"><i class="fas fa-bolt"></i> Quick Actions</h2>
                        <div class="adv-actions-grid" style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <button class="adv-action-btn" onclick="window.location.href='../views/admissions/'" style="width: 100%;">
                                <div class="adv-action-icon"><i class="fas fa-user-plus"></i></div>
                                <span>New Admission</span>
                            </button>
                            <button class="adv-action-btn" onclick="window.location.href='../views/beds/'" style="width: 100%;">
                                <div class="adv-action-icon"><i class="fas fa-bed"></i></div>
                                <span>Manage Beds</span>
                            </button>
                            <button class="adv-action-btn" onclick="window.location.href='../views/payments/'" style="width: 100%;">
                                <div class="adv-action-icon"><i class="fas fa-money-bill-wave"></i></div>
                                <span>Record Payment</span>
                            </button>
                            <button class="adv-action-btn" onclick="window.location.href='../views/discharge/'" style="width: 100%;">
                                <div class="adv-action-icon"><i class="fas fa-sign-out-alt"></i></div>
                                <span>Discharge Patient</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Advanced Navigation Modules -->
                <div class="mb-4">
                    <h2 class="section-heading"><i class="fas fa-th-large"></i> All Modules</h2>
                    <div class="adv-modules-grid">
                        
                        <div class="adv-module-card">
                            <div class="adv-module-header">
                                <div class="adv-module-icon"><i class="fas fa-hospital-user"></i></div>
                                <h5 class="adv-module-title">IPD Admissions</h5>
                            </div>
                            <p class="adv-module-desc">Manage patient admissions, bed assignments, and discharge.</p>
                            <a href="../views/admissions/" class="adv-btn-outline">Open Module</a>
                        </div>
                        
                        <div class="adv-module-card">
                            <div class="adv-module-header">
                                <div class="adv-module-icon"><i class="fas fa-bed"></i></div>
                                <h5 class="adv-module-title">Hospital Beds</h5>
                            </div>
                            <p class="adv-module-desc">View bed status, allocate and seamlessly release beds.</p>
                            <a href="../views/beds/" class="adv-btn-outline">Open Module</a>
                        </div>
                        
                        <div class="adv-module-card">
                            <div class="adv-module-header">
                                <div class="adv-module-icon"><i class="fas fa-procedures"></i></div>
                                <h5 class="adv-module-title">Procedures</h5>
                            </div>
                            <p class="adv-module-desc">Record medical procedures performed during admission securely.</p>
                            <a href="../views/procedures/" class="adv-btn-outline">Open Module</a>
                        </div>
                        
                        <div class="adv-module-card">
                            <div class="adv-module-header">
                                <div class="adv-module-icon"><i class="fas fa-file-medical"></i></div>
                                <h5 class="adv-module-title">Discharge Details</h5>
                            </div>
                            <p class="adv-module-desc">Manage comprehensive discharge summaries and instructions.</p>
                            <a href="../views/discharge/" class="adv-btn-outline">Open Module</a>
                        </div>
                        
                        <div class="adv-module-card">
                            <div class="adv-module-header">
                                <div class="adv-module-icon"><i class="fas fa-users"></i></div>
                                <h5 class="adv-module-title">Visitor Log</h5>
                            </div>
                            <p class="adv-module-desc">Track and manage visitors for admitted patients accurately.</p>
                            <a href="../views/visitors/" class="adv-btn-outline">Open Module</a>
                        </div>
                        
                    </div>
                </div>
                    </div>
                    <!-- End Reception Content -->
                </div>
                <!-- End Reception Main Content -->
            </div>
            <!-- End Reception Layout -->

            <!-- jQuery -->
            <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

            <!-- Bootstrap 5 JS -->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

            <!-- DataTables JS -->
            <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
            <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

            <!-- Select2 JS -->
            <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

            <!-- Toastify JS -->
            <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

            <!-- Custom JS -->
            <script src="assets/js/ipd_main.js"></script>

            <script>
                // Load dashboard statistics
                function loadDashboardStats() {
                    IPD.ajax('dashboard', 'GET')
                        .then(response => {
                            const data = response.data;

                            // Update active admissions
                            $('#activeAdmissions').text(data.admissions.active || 0);

                            // Update bed occupancy
                            const beds = data.beds;
                            $('#bedOccupancy').html(`${beds.occupied_beds}/${beds.total_beds}`);

                            // Update admissions today
                            $('#admissionsToday').text(data.admissions.today.total_admissions || 0);

                            // Update payments today
                            $('#paymentsToday').text(IPD.formatCurrency(data.payments.today.total_amount || 0));
                        })
                        .catch(error => {
                            console.error('Failed to load dashboard stats:', error);
                        });
                }

                // Load stats on page load
                $(document).ready(function () {
                    loadDashboardStats();

                    // Refresh stats every 30 seconds
                    setInterval(loadDashboardStats, 30000);
                });
            </script>
</body>

</html>