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

                // Load notice board
                function loadNoticeBoard() {
                    IPD.ajax('beds', 'GET')
                        .then(response => {
                            const beds = response.data.beds || [];
                            let rooms = {};
                            
                            beds.forEach(bed => {
                                const fName = bed.floor_name || 'Unassigned';
                                const wName = bed.ward_name  || 'Unassigned Ward';
                                const rType = bed.room_type || bed.room_category || 'General';
                                const rNum  = bed.room_number || '0';
                                
                                let status = (bed.bed_status || 'Available').toLowerCase();
                                if (status === 'occupied' && !bed.patient_id) status = 'available';
                                
                                const key = `${fName}|${wName}|${rType}|${rNum}`;
                                
                                if (!rooms[key]) {
                                    rooms[key] = {
                                        floor: fName,
                                        ward: wName,
                                        type: rType,
                                        room: rNum,
                                        rent: bed.total_bed_amount || 0,
                                        stats: { total:0, occ:0, avail:0 }
                                    };
                                }
                                
                                rooms[key].stats.total++;
                                if (status === 'occupied') rooms[key].stats.occ++;
                                else if (status === 'available') rooms[key].stats.avail++;
                            });
                            
                            let html = `
                            <div class="card border-0 shadow-sm" style="border-radius: 12px; border: 1px solid rgba(31, 107, 74, 0.1) !important; overflow: hidden; margin-top: 5px;">
                                <div class="table-responsive board-scroll" style="max-height: 380px; overflow-y: auto;">
                                    <table class="table align-middle mb-0 board-table" style="font-size: 13.5px; border-collapse: separate; border-spacing: 0;">
                                        <thead>
                                            <tr>
                                                <th class="py-3 px-4 border-0 text-uppercase" style="font-size: 11px; font-weight: 700; color: #64748b; letter-spacing: 0.5px;">Floor</th>
                                                <th class="py-3 px-3 border-0 text-uppercase" style="font-size: 11px; font-weight: 700; color: #64748b; letter-spacing: 0.5px;">Ward</th>
                                                <th class="py-3 px-3 border-0 text-uppercase" style="font-size: 11px; font-weight: 700; color: #64748b; letter-spacing: 0.5px;">Room Type</th>
                                                <th class="py-3 px-3 border-0 text-uppercase" style="font-size: 11px; font-weight: 700; color: #64748b; letter-spacing: 0.5px;">Room</th>
                                                <th class="py-3 px-3 border-0 text-uppercase" style="font-size: 11px; font-weight: 700; color: #64748b; letter-spacing: 0.5px;">Rent</th>
                                                <th class="py-3 px-3 border-0 text-uppercase text-center" style="font-size: 11px; font-weight: 700; color: #64748b; letter-spacing: 0.5px;">Occ.</th>
                                                <th class="py-3 px-3 border-0 text-uppercase text-center" style="font-size: 11px; font-weight: 700; color: #64748b; letter-spacing: 0.5px;">Avail.</th>
                                                <th class="py-3 px-3 border-0 text-uppercase text-center" style="font-size: 11px; font-weight: 700; color: #64748b; letter-spacing: 0.5px;">Total</th>
                                                <th class="py-3 px-4 border-0 text-uppercase" style="font-size: 11px; font-weight: 700; color: #64748b; letter-spacing: 0.5px; width: 140px;">Occupancy</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                            `;
                            
                            Object.values(rooms).forEach(r => {
                                const pct = r.stats.total > 0 ? Math.round((r.stats.occ / r.stats.total) * 100) : 0;
                                html += `
                                    <tr class="board-row" style="cursor: pointer; border-bottom: 1px solid #f1f5f9;" onclick="window.location.href='../views/beds/'">
                                        <td class="py-3 px-4">
                                            <div class="d-flex align-items-center">
                                                <div style="width: 28px; height: 28px; border-radius: 6px; background: rgba(31, 107, 74, 0.1); color: #1f6b4a; display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                                                    <i class="fas fa-layer-group" style="font-size: 12px;"></i>
                                                </div>
                                                <span class="fw-semibold text-dark">${r.floor}</span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-3 fw-medium" style="color: #334155;">${r.ward}</td>
                                        <td class="py-3 px-3" style="color: #64748b; font-size: 13px;">${r.type}</td>
                                        <td class="py-3 px-3 fw-bold" style="color: #1f6b4a;">${r.room}</td>
                                        <td class="py-3 px-3 fw-medium text-dark">₹${r.rent}</td>
                                        <td class="py-3 px-3 text-center">
                                            <span class="status-pill" style="background: ${r.stats.occ > 0 ? '#fee2e2' : '#f1f5f9'}; color: ${r.stats.occ > 0 ? '#ef4444' : '#94a3b8'};">
                                                ${r.stats.occ}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3 text-center">
                                            <span class="status-pill" style="background: ${r.stats.avail > 0 ? '#d1fae5' : '#f1f5f9'}; color: ${r.stats.avail > 0 ? '#10b981' : '#94a3b8'};">
                                                ${r.stats.avail}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3 text-center fw-semibold" style="color: #475569;">${r.stats.total}</td>
                                        <td class="py-3 px-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 6px; background: #f1f5f9; border-radius: 3px;">
                                                    <div class="progress-bar" role="progressbar" style="width: ${pct}%; background-color: ${pct > 80 ? '#ef4444' : (pct > 0 ? '#1f6b4a' : '#cbd5e1')} !important; border-radius: 3px;"></div>
                                                </div>
                                                <span style="font-size: 12px; font-weight: 600; color: #475569; min-width: 32px; text-align: right;">${pct}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                `;
                            });
                            
                            html += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            `;
                            
                            $('#noticeBoardContainer').html(html);
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