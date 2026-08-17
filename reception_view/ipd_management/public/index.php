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

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

        /* Notice Board Nav Cards */
        .nav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
        }

        .nav-card {
            background: #fff;
            border: 1.5px solid rgba(31, 107, 74, 0.15);
            border-radius: 18px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .nav-card::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 4px;
            background: #1f6b4a;
            border-radius: 12px 0 0 12px;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .nav-card:hover {
            border-color: #1f6b4a;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        .nav-card:hover::before { opacity: 1; }

        .nav-card-icon {
            width: 40px; height: 40px;
            border-radius: 9px;
            background: #e6f0eb;
            display: flex; align-items: center; justify-content: center;
            color: #1f6b4a;
            font-size: 16px;
            margin-bottom: 12px;
        }

        .nav-card-title {
            font-size: 15px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 2px;
        }

        .nav-card-sub {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 15px;
        }

        .nav-card-stats {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .nav-stat { flex: 1; }
        .nav-stat-num { font-size: 16px; font-weight: 700; }
        .nav-stat-num.occ { color: #ef4444; }
        .nav-stat-num.ava { color: #10b981; }
        .nav-stat-num.tot { color: #64748b; }
        .nav-stat-label { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }

        .nav-card-footer {
            display: flex; justify-content: space-between; align-items: center;
            font-size: 12px; color: #64748b; font-weight: 500;
            margin-bottom: 6px;
        }
        
        .prog-bar-wrap {
            height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden;
        }
        .prog-bar-fill {
            height: 100%; background: #1f6b4a; border-radius: 3px; transition: width 0.3s;
        }
        .prog-bar-fill.danger { background: #ef4444; }

        /* Custom Scrollbar for Notice Board */
        .board-scroll::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .board-scroll::-webkit-scrollbar-track {
            background: #f1f5f9; 
            border-radius: 4px;
        }
        .board-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1; 
            border-radius: 4px;
        }
        .board-scroll::-webkit-scrollbar-thumb:hover {
            background: #94a3b8; 
        }

        .board-table th {
            position: sticky;
            top: 0;
            background: #f8fafc !important;
            z-index: 10;
            box-shadow: 0 1px 0 #e2e8f0;
        }
        .board-table tr:last-child td {
            border-bottom: none;
        }
        .board-row {
            transition: background 0.15s ease;
        }
        .board-row:hover {
            background: #f8fafc !important;
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 24px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        /* ── Modern Bento Grid Styles ── */
        .bento-overview-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }

        .bento-stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 1rem 1.25rem;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.03);
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            align-items: center;
            gap: 1rem;
            min-height: 85px;
            z-index: 1;
        }

        .bento-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.06);
        }



        .bento-stat-icon {
            font-size: 1.1rem;
            color: #1f6b4a;
            background: #eaf1ec;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            flex-shrink: 0;
            transition: transform 0.3s;
        }

        .bento-stat-card:hover .bento-stat-icon {
            transform: scale(1.1) rotate(-5deg);
        }

        .bento-stat-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1;
            margin-bottom: 0.2rem;
            letter-spacing: -0.02em;
        }

        .bento-stat-label {
            font-size: 0.7rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ── Quick Actions Bento ── */
        .bento-actions-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }

        .bento-action-tile {
            background: #ffffff;
            border: 1.5px solid rgba(31, 107, 74, 0.1);
            border-radius: 16px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            position: relative;
            overflow: hidden;
            min-height: 65px;
        }

        .bento-action-tile::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(135deg, #1f6b4a 0%, #11422d 100%);
            opacity: 0;
            transition: opacity 0.2s ease;
            z-index: 0;
        }

        .bento-action-tile:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(31, 107, 74, 0.15);
            border-color: transparent;
        }
        
        .bento-action-tile:hover::before {
            opacity: 1;
        }

        .bento-action-tile:hover * {
            color: white !important;
            position: relative;
            z-index: 1;
        }

        .bento-action-icon {
            font-size: 1.25rem;
            color: #1f6b4a;
            position: relative;
            z-index: 1;
            transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .bento-action-tile:hover .bento-action-icon {
            transform: scale(1.15);
        }

        .bento-action-label {
            font-size: 0.85rem;
            font-weight: 700;
            color: #334155;
            position: relative;
            z-index: 1;
        }

        /* ── Notice Board Cards ── */
        .notice-card {
            border: 1px solid rgba(0, 0, 0, 0.05) !important;
            border-radius: 16px !important;
            background: #fff;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .notice-card::after {
            content: '';
            position: absolute;
            left: 0; bottom: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, #1f6b4a 0%, #10b981 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .notice-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05) !important;
            border-color: transparent !important;
        }
        .notice-card:hover::after {
            opacity: 1;
        }
        .notice-card-icon {
            transition: all 0.3s ease;
        }
        .notice-card:hover .notice-card-icon {
            background: #1f6b4a !important;
            color: #ffffff !important;
            transform: rotate(-10deg) scale(1.1);
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

                <!-- Dashboard Top Section: KPIs + Quick Actions -->
                <div class="dashboard-top-section" style="display: flex; flex-direction: column; gap: 1.5rem; margin-bottom: 2rem;">
                    
                    <!-- Top: Bento Stats Overview (Horizontal) -->
                    <div>
                        <h2 style="font-size: 1rem; font-weight: 700; color: #475569; margin-bottom: 0.75rem; letter-spacing: 0.5px;">
                            <i class="fas fa-chart-pie me-2 text-primary" style="color: #1f6b4a !important;"></i>Overview
                        </h2>
                        <div class="bento-overview-grid" id="statsGrid">
                            
                            <!-- Active Admissions -->
                            <div class="bento-stat-card">
                                <div class="bento-stat-icon" style="color:#10b981; background:#d1fae5;"><i class="fas fa-bed"></i></div>
                                <div>
                                    <div class="bento-stat-value" id="activeAdmissions">-</div>
                                    <div class="bento-stat-label">Active Admissions</div>
                                </div>
                            </div>
                            
                            <!-- Bed Occupancy -->
                            <div class="bento-stat-card">
                                <div class="bento-stat-icon" style="color:#ef4444; background:#fee2e2;"><i class="fas fa-procedures"></i></div>
                                <div>
                                    <div class="bento-stat-value" id="bedOccupancy">-</div>
                                    <div class="bento-stat-label">Bed Occupancy</div>
                                </div>
                            </div>
                            
                            <!-- Admissions Today -->
                            <div class="bento-stat-card">
                                <div class="bento-stat-icon" style="color:#0ea5e9; background:#e0f2fe;"><i class="fas fa-user-plus"></i></div>
                                <div>
                                    <div class="bento-stat-value" id="admissionsToday">-</div>
                                    <div class="bento-stat-label">Admissions Today</div>
                                </div>
                            </div>
                            
                            <!-- Payments Today -->
                            <div class="bento-stat-card">
                                <div class="bento-stat-icon" style="color:#f59e0b; background:#fef3c7;"><i class="fas fa-rupee-sign"></i></div>
                                <div>
                                    <div class="bento-stat-value" id="paymentsToday">-</div>
                                    <div class="bento-stat-label">Payments Today</div>
                                </div>
                            </div>
                            
                        </div>
                    </div>

                    <!-- Bottom: Quick Actions Bento (Horizontal) -->
                    <div>
                        <h2 style="font-size: 1rem; font-weight: 700; color: #475569; margin-bottom: 0.75rem; letter-spacing: 0.5px;">
                            <i class="fas fa-bolt me-2 text-warning" style="color: #f59e0b !important;"></i>Quick Actions
                        </h2>
                        <div class="bento-actions-grid">
                            <div class="bento-action-tile" onclick="window.location.href='../views/admissions/'">
                                <div class="bento-action-icon"><i class="fas fa-user-plus"></i></div>
                                <div class="bento-action-label">New Admission</div>
                            </div>
                            <div class="bento-action-tile" onclick="window.location.href='../views/beds/'">
                                <div class="bento-action-icon"><i class="fas fa-bed"></i></div>
                                <div class="bento-action-label">Manage Beds</div>
                            </div>
                            <div class="bento-action-tile" onclick="window.location.href='../views/payments/'">
                                <div class="bento-action-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                                <div class="bento-action-label">Payments</div>
                            </div>
                            <div class="bento-action-tile" onclick="window.location.href='../views/discharge/'">
                                <div class="bento-action-icon"><i class="fas fa-sign-out-alt"></i></div>
                                <div class="bento-action-label">Discharge</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Floor Notice Board -->
                <div class="mb-4" style="margin-bottom: 2.5rem !important;">
                    <h2 class="section-heading" style="font-size: 1.1rem; color: #64748b; margin-bottom: 1rem;"><i class="fas fa-chalkboard"></i> Hospital Notice Board — Floor Overview</h2>
                    <div id="noticeBoardContainer">
                        <div class="text-center py-4"><div class="spinner-border text-success" role="status"></div></div>
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
                <!-- Patient Details Modal -->
                <div class="modal fade" id="patientDetailsModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title fw-bold" style="color: #1f6b4a;"><i class="fas fa-id-card-alt me-2"></i>Patient Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body pt-3 pb-4">
                                <div class="text-center mb-4">
                                    <div style="width: 80px; height: 80px; background: #eef2f6; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 35px; color: #64748b; margin-bottom: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <h4 id="modalPatientName" class="fw-bold mb-2 text-dark"></h4>
                                    <div id="modalBedStatus"></div>
                                </div>
                                
                                <div class="bg-light p-3 rounded-4 border" style="border-color: #f1f5f9 !important;">
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <label class="text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Admitted On</label>
                                            <div id="modalAdmitDate" class="fw-semibold text-dark" style="font-size: 0.9rem;"></div>
                                        </div>
                                        <div class="col-6">
                                            <label class="text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Rent / Day</label>
                                            <div id="modalRent" class="fw-bold text-success" style="font-size: 1rem;"></div>
                                        </div>
                                        <div class="col-6">
                                            <label class="text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Room Number</label>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-door-closed text-secondary"></i>
                                                <span id="modalBedInfo" class="fw-semibold text-dark" style="font-size: 0.9rem;"></span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Room Type</label>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-layer-group text-secondary"></i>
                                                <span id="modalBedType" class="fw-semibold text-dark" style="font-size: 0.9rem;"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer border-0 pt-0 pb-4 justify-content-center">
                                <button type="button" class="btn btn-light px-4 rounded-pill fw-semibold border" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-success px-4 rounded-pill fw-semibold shadow-sm" onclick="window.location.href='../views/admissions/'"><i class="fas fa-external-link-alt me-2"></i>Manage Patient</button>
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

                // Global function to show patient details modal
                window.showPatientDetails = function(bedId, patientName, admissionDate, rent, roomNum, bedType, bedStatus) {
                    if (!patientName || patientName === 'null') patientName = 'Unknown Patient';
                    
                    // Format date nicely if possible
                    let formattedDate = admissionDate;
                    if (admissionDate && admissionDate !== 'null') {
                        try {
                            const d = new Date(admissionDate);
                            if (!isNaN(d.getTime())) {
                                formattedDate = d.toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                            }
                        } catch(e) {}
                    } else {
                        formattedDate = 'N/A';
                    }
                    
                    $('#modalPatientName').text(patientName);
                    $('#modalAdmitDate').text(formattedDate);
                    $('#modalBedInfo').text(roomNum);
                    $('#modalBedType').text(bedType);
                    $('#modalRent').text('₹' + rent);
                    
                    let statusHtml = '';
                    if (bedStatus.toLowerCase() === 'occupied') {
                        statusHtml = '<span class="badge" style="background: #ef4444; color: white; padding: 6px 12px; border-radius: 8px;"><i class="fas fa-bed me-1"></i> Occupied</span>';
                    } else if (bedStatus.toLowerCase() === 'maintenance') {
                        statusHtml = '<span class="badge" style="background: #f59e0b; color: white; padding: 6px 12px; border-radius: 8px;"><i class="fas fa-tools me-1"></i> Maintenance</span>';
                    }
                    $('#modalBedStatus').html(statusHtml);
                    
                    const modal = new bootstrap.Modal(document.getElementById('patientDetailsModal'));
                    modal.show();
                };

                // Load notice board
                function loadNoticeBoard() {
                    IPD.ajax('beds', 'GET')
                        .then(response => {
                            const beds = response.data.beds || [];
                            
                            // Stats for graph/summary
                            let stats = { total: 0, occupied: 0, available: 0, maintenance: 0 };
                            let roomTypeStats = {};
                            let roomTypeDetails = {};
                            
                            beds.forEach(bed => {
                                const rType = bed.room_type || bed.room_category || 'General';
                                
                                let status = (bed.bed_status || 'Available').toLowerCase();
                                if (status === 'occupied' && !bed.patient_id) status = 'available';
                                
                                stats.total++;
                                if (status === 'occupied') stats.occupied++;
                                else if (status === 'available') stats.available++;
                                else if (status === 'maintenance') stats.maintenance++;
                                
                                if (!roomTypeStats[rType]) {
                                    roomTypeStats[rType] = 0;
                                    roomTypeDetails[rType] = {
                                        rent: bed.total_bed_amount || bed.amount_per_day || 0,
                                        patients: []
                                    };
                                }
                                roomTypeStats[rType]++;
                                
                                if (status === 'occupied' && bed.patient_id) {
                                    roomTypeDetails[rType].patients.push({
                                        name: bed.patient_name || 'Unknown',
                                        id: bed.patient_id || 'N/A',
                                        bed: (bed.room_number ? bed.room_number : '') + ' / B-' + bed.bed_number,
                                        date: bed.admission_date || 'N/A'
                                    });
                                }
                            });
                            
                            let html = `
                            <!-- Summary Graph -->
                            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px; border: 1px solid rgba(0,0,0,0.05) !important; background: #fff;">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0 fw-bold text-dark" style="font-size: 1rem;"><i class="fas fa-chart-pie text-primary me-2"></i>Room Types Distribution</h6>
                                    </div>
                                    <div style="position: relative; height: 350px; width: 100%; display: flex; justify-content: center;">
                                        <canvas id="roomTypeChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            `;
                            
                            $('#noticeBoardContainer').html(html);
                            
                            // Initialize Chart.js Donut Chart
                            const ctx = document.getElementById('roomTypeChart');
                            if (ctx) {
                                if (window.roomTypeChartInstance) {
                                    window.roomTypeChartInstance.destroy();
                                }
                                
                                const labels = Object.keys(roomTypeStats);
                                const dataValues = Object.values(roomTypeStats);
                                const backgroundColors = [
                                    '#1f6b4a', '#10b981', '#0ea5e9', '#6366f1', 
                                    '#8b5cf6', '#d946ef', '#f43f5e', '#f59e0b',
                                    '#14b8a6', '#84cc16', '#eab308'
                                ];
                                
                                window.roomTypeChartInstance = new Chart(ctx, {
                                    type: 'doughnut',
                                    data: {
                                        labels: labels,
                                        datasets: [{
                                            data: dataValues,
                                            backgroundColor: backgroundColors.slice(0, labels.length),
                                            borderWidth: 2,
                                            borderColor: '#ffffff',
                                            hoverOffset: 4
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: {
                                                position: 'right',
                                                labels: {
                                                    font: { family: 'Inter', size: 13, weight: '600' },
                                                    color: '#475569',
                                                    padding: 20
                                                }
                                            },
                                            tooltip: {
                                                enabled: false,
                                                external: function(context) {
                                                    // Tooltip Element
                                                    let tooltipEl = document.getElementById('chartjs-tooltip');
                                                    
                                                    // Create element on first render
                                                    if (!tooltipEl) {
                                                        tooltipEl = document.createElement('div');
                                                        tooltipEl.id = 'chartjs-tooltip';
                                                        tooltipEl.style.background = '#1f6b4a';
                                                        tooltipEl.style.borderRadius = '16px';
                                                        tooltipEl.style.color = '#f3efe6';
                                                        tooltipEl.style.opacity = 1;
                                                        tooltipEl.style.pointerEvents = 'none';
                                                        tooltipEl.style.position = 'absolute';
                                                        tooltipEl.style.transition = 'opacity 0.2s ease';
                                                        tooltipEl.style.boxShadow = '0 10px 30px rgba(31,107,74,0.4)';
                                                        tooltipEl.style.border = '2px solid #f3efe6';
                                                        tooltipEl.style.zIndex = 9999;
                                                        tooltipEl.style.minWidth = '280px';
                                                        tooltipEl.style.maxWidth = '340px';
                                                        
                                                        document.body.appendChild(tooltipEl);
                                                    }
                                                    
                                                    // Hide if no tooltip
                                                    const tooltipModel = context.tooltip;
                                                    if (tooltipModel.opacity === 0) {
                                                        tooltipEl.style.opacity = 0;
                                                        return;
                                                    }
                                                    
                                                    // Set Text
                                                    if (tooltipModel.body) {
                                                        const dataIndex = tooltipModel.dataPoints[0].dataIndex;
                                                        const rType = tooltipModel.dataPoints[0].label;
                                                        const parsedCount = tooltipModel.dataPoints[0].parsed;
                                                        const details = roomTypeDetails[rType];
                                                        
                                                        const bgColor = tooltipModel.dataPoints[0].dataset.backgroundColor[dataIndex];
                                                        
                                                        let innerHtml = `
                                                            <div style="padding: 18px;">
                                                                <div style="display: flex; align-items: center; border-bottom: 2px solid #f3efe6; padding-bottom: 12px; margin-bottom: 14px;">
                                                                    <div style="width: 16px; height: 16px; background: ${bgColor}; border-radius: 4px; margin-right: 12px; flex-shrink: 0; border: 2px solid #f3efe6;"></div>
                                                                    <h6 style="margin: 0; font-family: 'Inter', sans-serif; font-weight: 800; font-size: 16px; letter-spacing: 0.2px; color: #f3efe6;">${rType}</h6>
                                                                </div>
                                                                
                                                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                                                    <span style="font-family: 'Inter'; font-size: 14px; color: #f3efe6; font-weight: 600;">Total Beds:</span>
                                                                    <span style="font-family: 'Inter'; font-size: 14px; font-weight: 800; color: #f3efe6;">${parsedCount}</span>
                                                                </div>
                                                        `;
                                                        
                                                        if (details && details.rent) {
                                                            innerHtml += `
                                                                <div style="display: flex; justify-content: space-between; margin-bottom: 14px;">
                                                                    <span style="font-family: 'Inter'; font-size: 14px; color: #f3efe6; font-weight: 600;">Rent/Day:</span>
                                                                    <span style="font-family: 'Inter'; font-size: 14px; font-weight: 800; color: #f3efe6;">₹${details.rent}</span>
                                                                </div>
                                                            `;
                                                        }
                                                        
                                                        if (details && details.patients && details.patients.length > 0) {
                                                            innerHtml += `
                                                                <div style="background: #f3efe6; border-radius: 12px; padding: 12px; margin-top: 14px; border: 2px solid #f3efe6;">
                                                                    <div style="font-family: 'Inter'; font-size: 12px; text-transform: uppercase; letter-spacing: 0.8px; color: #1f6b4a; font-weight: 800; margin-bottom: 12px;">
                                                                        <i class="fas fa-users me-1" style="color: #1f6b4a;"></i> Occupied Patients (${details.patients.length})
                                                                    </div>
                                                                    <div style="max-height: 150px; overflow-y: auto; padding-right: 6px;" class="board-scroll">
                                                            `;
                                                            
                                                            details.patients.forEach(p => {
                                                                let pDate = p.date !== 'N/A' && p.date !== 'null' ? p.date.split(' ')[0] : 'N/A';
                                                                innerHtml += `
                                                                    <div style="border-left: 4px solid ${bgColor}; padding-left: 10px; margin-bottom: 12px;">
                                                                        <div style="font-family: 'Inter'; font-size: 14px; font-weight: 800; color: #1f6b4a; line-height: 1.2; margin-bottom: 4px;">
                                                                            ${p.name}
                                                                        </div>
                                                                        <div style="font-family: 'Inter'; font-size: 12px; color: #1f6b4a; font-weight: 600; margin-bottom: 6px;">
                                                                            <i class="fas fa-id-badge me-1"></i> ${p.id}
                                                                        </div>
                                                                        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed #1f6b4a; padding-top: 6px; margin-top: 4px;">
                                                                            <span style="font-family: 'Inter'; font-size: 11px; color: #1f6b4a; font-weight: 800;"><i class="fas fa-bed me-1" style="color: #1f6b4a;"></i> ${p.bed}</span>
                                                                            <span style="font-family: 'Inter'; font-size: 11px; color: #1f6b4a; font-weight: 800;"><i class="far fa-calendar-alt me-1" style="color: #1f6b4a;"></i> ${pDate}</span>
                                                                        </div>
                                                                    </div>
                                                                `;
                                                            });
                                                            
                                                            innerHtml += `
                                                                    </div>
                                                                </div>
                                                            `;
                                                        } else {
                                                            innerHtml += `
                                                                <div style="text-align: center; padding: 16px 0 8px 0; background: #f3efe6; border-radius: 12px; margin-top: 14px; border: 2px solid #f3efe6;">
                                                                    <div style="font-family: 'Inter'; font-size: 13px; color: #1f6b4a; font-weight: 800;">
                                                                        <i class="fas fa-check-circle me-1"></i> Fully Available
                                                                    </div>
                                                                </div>
                                                            `;
                                                        }
                                                        
                                                        innerHtml += `</div>`;
                                                        tooltipEl.innerHTML = innerHtml;
                                                    }
                                                    
                                                    const position = context.chart.canvas.getBoundingClientRect();
                                                    
                                                    // Dynamic positioning to keep tooltip on screen
                                                    let leftPos = position.left + window.pageXOffset + tooltipModel.caretX + 15;
                                                    let topPos = position.top + window.pageYOffset + tooltipModel.caretY + 15;
                                                    
                                                    // Prevent tooltip from overflowing the right edge
                                                    if (leftPos + 320 > window.innerWidth) {
                                                        leftPos = position.left + window.pageXOffset + tooltipModel.caretX - 335;
                                                    }
                                                    
                                                    tooltipEl.style.opacity = 1;
                                                    tooltipEl.style.position = 'absolute';
                                                    tooltipEl.style.left = leftPos + 'px';
                                                    tooltipEl.style.top = topPos + 'px';
                                                    tooltipEl.style.fontFamily = tooltipModel.options.bodyFont.family;
                                                }
                                            }
                                        },
                                        cutout: '65%'
                                    }
                                });
                            }
                        })
                        .catch(err => {
                            console.error('Failed to load beds for notice board:', err);
                            $('#noticeBoardContainer').html('<div class="alert alert-danger">Failed to load bed statistics.</div>');
                        });
                }

                // Load stats on page load
                $(document).ready(function () {
                    loadDashboardStats();
                    loadNoticeBoard();

                    // Refresh stats every 30 seconds
                    setInterval(function() {
                        loadDashboardStats();
                        loadNoticeBoard();
                    }, 30000);
                });
            </script>
</body>

</html>