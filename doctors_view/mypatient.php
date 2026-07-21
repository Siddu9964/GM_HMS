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
    <title>My Patients - GM HMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/doctor_dashboard.css?v=<?= time() ?>">
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
                    <h1 class="welcome-title">
                        <i class="fas fa-id-card-clip"></i> Patient Master List
                    </h1>
                    <p class="welcome-subtitle">
                        Manage your clinical follow-ups and records
                    </p>
                    <i class="fas fa-users welcome-icon-bg"></i>
                </div>
                
                <div class="d-flex gap-2" style="margin-bottom: 1rem;">
                        <button onclick="toggleView('table')" id="btn-table-view" class="btn btn-primary" style="background: #1f6b4a; border: none; border-radius: 10px;">
                            <i class="fas fa-table"></i>
                        </button>
                        <button onclick="toggleView('cards')" id="btn-cards-view" class="btn btn-outline" style="border-radius: 10px; border-color: rgba(255,255,255,0.2); color: white;">
                            <i class="fas fa-th-large"></i>
                        </button>
                    </div>
                
                <!-- Filters Card -->
                <div class="bento-card mb-4 fade-in-up delay-1">
                    <div class="card-body" style="padding: 0.5rem;">
                        <div class="filter-group-elite" style="display: flex; gap: 1rem; align-items: flex-end;">
                            <div style="flex: 2;">
                                <label style="font-size: 0.65rem; font-weight: 800; color: #94A3B8; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px;">Quick Search</label>
                                <div style="position: relative;">
                                    <i class="fas fa-search" style="position: absolute; left: 15px; top: 12px; color: #94A3B8;"></i>
                                    <input type="text" id="search-patient" class="form-control" placeholder="Name, ID, or Phone..." style="padding-left: 40px; border-radius: 12px; height: 44px; border: 2px solid #F1F5F9;">
                                </div>
                            </div>
                            <div style="flex: 1;">
                                <label style="font-size: 0.65rem; font-weight: 800; color: #94A3B8; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px;">Filter Status</label>
                                <select id="filter-status" class="form-control form-select" style="border-radius: 12px; height: 44px; border: 2px solid #F1F5F9;">
                                    <option value="">All Status</option>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Completed">Completed</option>
                                </select>
                            </div>
                            <div style="flex: 1; display: flex; gap: 0.5rem;">
                                <button onclick="applyFilters()" class="btn btn-primary" style="flex: 2; height: 44px; border-radius: 12px; background: #144d34; border: none; font-weight: 700;">
                                    <i class="fas fa-filter"></i> Apply
                                </button>
                                <button onclick="Modal.show('advanced-search-modal')" class="btn btn-outline" style="flex: 1; height: 44px; border-radius: 12px; border: 2px solid #E2E8F0; color: #64748B;" title="Advanced Search">
                                    <i class="fas fa-sliders-h"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics -->
                <div class="bento-grid">
                    <div class="bento-card gm-kpi-card col-span-3 fade-in-up delay-1">
                        <div>
                            <div class="gm-kpi-icon-wrap icon-teal"><i class="fas fa-users"></i></div>
                            <div class="gm-kpi-value" id="stat-total">0</div>
                            <div class="gm-kpi-label">Total</div>
                        </div>
                        <i class="fas fa-hospital-user bg-icon"></i>
                    </div>
                    <div class="bento-card gm-kpi-card col-span-3 fade-in-up delay-1">
                        <div>
                            <div class="gm-kpi-icon-wrap icon-teal" style="background: rgba(16,185,129,0.1); color: #10B981;"><i class="fas fa-user-check"></i></div>
                            <div class="gm-kpi-value" id="stat-active">0</div>
                            <div class="gm-kpi-label">Active</div>
                        </div>
                        <i class="fas fa-check-circle bg-icon"></i>
                    </div>
                    <div class="bento-card gm-kpi-card col-span-3 fade-in-up delay-2">
                        <div>
                            <div class="gm-kpi-icon-wrap icon-orange"><i class="fas fa-clock-rotate-left"></i></div>
                            <div class="gm-kpi-value" id="stat-followup">0</div>
                            <div class="gm-kpi-label">Follow-up</div>
                        </div>
                        <i class="fas fa-calendar-alt bg-icon"></i>
                    </div>
                    <div class="bento-card gm-kpi-card col-span-3 fade-in-up delay-2">
                        <div>
                            <div class="gm-kpi-icon-wrap icon-red"><i class="fas fa-triangle-exclamation"></i></div>
                            <div class="gm-kpi-value" id="stat-critical">0</div>
                            <div class="gm-kpi-label">Review</div>
                        </div>
                        <i class="fas fa-exclamation-circle bg-icon"></i>
                    </div>
                </div>
                
                <!-- Table View -->
                <div id="table-view" class="bento-card col-span-12 fade-in-up delay-3">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-list"></i>
                            Patient List
                        </div>
                        <button onclick="exportPatients()" class="btn btn-sm btn-outline">
                            <i class="fas fa-download"></i> Export CSV
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table id="patients-table" class="table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Patient ID</th>
                                        <th>Name</th>
                                        <th>Age/Gender</th>
                                        <th>Blood Group</th>
                                        <th>Last Visit</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="patients-table-body">
                                    <!-- Data loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Cards View -->
                <div id="cards-view" class="bento-grid" style="display: none;">
                    <!-- Cards loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Patient Details Modal -->
    <div id="patient-modal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h2 id="modal-patient-name">Patient Details</h2>
                <button onclick="Modal.hide('patient-modal')" class="btn btn-sm">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="modal-patient-details">
                <!-- Patient details loaded here -->
            </div>
        </div>
    </div>

    <!-- Advanced Search Modal -->
    <div id="advanced-search-modal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2><i class="fas fa-search-plus" style="color: #1f6b4a; margin-right: 10px;"></i>Advanced Search</h2>
                <button onclick="Modal.hide('advanced-search-modal')" class="btn btn-sm">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="advanced-search-form" onsubmit="event.preventDefault(); performAdvancedSearch();">
                    <div class="d-grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label style="font-size: 0.8rem; font-weight: 700; color: #64748B; margin-bottom: 0.5rem; display: block;">Patient ID</label>
                            <input type="text" id="adv-patient-id" class="form-control" placeholder="e.g. PID-2023..." style="border-radius: 8px;">
                        </div>
                        <div>
                            <label style="font-size: 0.8rem; font-weight: 700; color: #64748B; margin-bottom: 0.5rem; display: block;">Phone Number</label>
                            <input type="text" id="adv-phone" class="form-control" placeholder="10-digit number" style="border-radius: 8px;">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label style="font-size: 0.8rem; font-weight: 700; color: #64748B; margin-bottom: 0.5rem; display: block;">Patient Name</label>
                        <input type="text" id="adv-name" class="form-control" placeholder="First or Last Name" style="border-radius: 8px;">
                    </div>
                    
                    <div class="d-grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label style="font-size: 0.8rem; font-weight: 700; color: #64748B; margin-bottom: 0.5rem; display: block;">City</label>
                            <input type="text" id="adv-city" class="form-control" placeholder="City Name" style="border-radius: 8px;">
                        </div>
                        <div>
                            <label style="font-size: 0.8rem; font-weight: 700; color: #64748B; margin-bottom: 0.5rem; display: block;">Gender</label>
                            <select id="adv-gender" class="form-control form-select" style="border-radius: 8px;">
                                <option value="">Any</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label style="font-size: 0.8rem; font-weight: 700; color: #64748B; margin-bottom: 0.5rem; display: block;">Registration Date Range</label>
                        <div class="d-flex gap-2">
                            <input type="date" id="adv-date-from" class="form-control" style="border-radius: 8px;">
                            <span style="align-self: center; color: #94A3B8;">to</span>
                            <input type="date" id="adv-date-to" class="form-control" style="border-radius: 8px;">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label style="font-size: 0.8rem; font-weight: 700; color: #64748B; margin-bottom: 0.5rem; display: block;">Status</label>
                        <div class="d-flex gap-3">
                            <label class="d-flex align-items-center gap-2" style="cursor: pointer;">
                                <input type="radio" name="adv-status" value="" checked> Any
                            </label>
                            <label class="d-flex align-items-center gap-2" style="cursor: pointer;">
                                <input type="radio" name="adv-status" value="Active"> Active
                            </label>
                            <label class="d-flex align-items-center gap-2" style="cursor: pointer;">
                                <input type="radio" name="adv-status" value="Inactive"> Inactive
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="button" onclick="Modal.hide('advanced-search-modal')" class="btn btn-outline" style="flex: 1; border-radius: 10px;">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="flex: 2; border-radius: 10px; background: #1f6b4a; border: none;">
                            <i class="fas fa-search"></i> Search Patients
                        </button>
                    </div>
                </form>
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
    <script src="assets/js/mypatient.js"></script>
</body>
</html>
