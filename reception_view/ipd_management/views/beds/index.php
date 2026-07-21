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
    <title>Bed Management - GM HMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    
    <!-- Reception Dashboard CSS -->
    <link rel="stylesheet" href="../../../assets/css/reception_dashboard.css">
    
    <!-- Custom IPD CSS -->
    <link rel="stylesheet" href="../../public/assets/css/ipd_main.css">
    
    <style>
        :root {
            --bed-primary: #1F6B4A;
            --bed-bg: #F3EFE6;
            --bed-white: #FFFFFF;
            --bed-text-dark: #2d3748;
            --bed-text-muted: #64748b;
            --bed-border: rgba(31, 107, 74, 0.15);
            --bed-radius: 18px;
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --status-avail: #10b981;
            --status-occ: #ef4444;
            --status-block: #f59e0b;
            --status-maint: #64748b;
        }

        body {
            background-color: var(--bed-bg) !important;
            font-family: 'Inter', sans-serif;
        }

        /* Layout */
        .bed-app-layout {
            display: flex;
            height: calc(100vh - 70px); /* Adjust based on navbar height */
            background: var(--bed-bg);
            overflow: hidden;
        }

        /* Floor Sidebar Navigation */
        .floor-sidebar {
            width: 280px;
            background: var(--bed-white);
            border-right: 1px solid var(--bed-border);
            display: flex;
            flex-direction: column;
            z-index: 10;
        }

        .floor-sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--bed-border);
        }

        .floor-sidebar-header h2 {
            font-size: 1.25rem;
            color: var(--bed-primary);
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .floor-list {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .floor-item {
            padding: 1rem;
            border-radius: 12px;
            background: transparent;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .floor-item:hover {
            background: rgba(31, 107, 74, 0.05);
            border-color: var(--bed-border);
        }

        .floor-item.active {
            background: var(--bed-primary);
            color: var(--bed-white);
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .floor-item .icon {
            font-size: 1.5rem;
            color: var(--bed-primary);
            transition: color 0.3s;
        }

        .floor-item.active .icon {
            color: var(--bed-white);
        }

        .floor-item-content {
            flex: 1;
        }

        .floor-item-title {
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 0.25rem;
            color: var(--bed-text-dark);
            transition: color 0.3s;
        }

        .floor-item.active .floor-item-title,
        .floor-item.active .floor-item-meta {
            color: var(--bed-white);
        }

        .floor-item-meta {
            font-size: 0.75rem;
            color: var(--bed-text-muted);
            font-weight: 500;
        }

        /* Main Content */
        .bed-app-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
        }

        /* Top Header (Search & Stats) */
        .app-header {
            padding: 1.5rem 2rem;
            background: var(--bed-white);
            border-bottom: 1px solid var(--bed-border);
            z-index: 5;
        }

        .app-controls {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .search-input {
            flex: 1;
            position: relative;
        }

        .search-input i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--bed-text-muted);
        }

        .search-input input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border-radius: 50px;
            border: 1px solid var(--bed-border);
            background: var(--bed-bg);
            font-size: 0.95rem;
            color: var(--bed-text-dark);
            outline: none;
            transition: all 0.3s;
        }

        .search-input input:focus {
            box-shadow: 0 0 0 3px rgba(31, 107, 74, 0.15);
            background: var(--bed-white);
        }

        .filter-dropdown select {
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            border: 1px solid var(--bed-border);
            background: var(--bed-white);
            font-weight: 600;
            color: var(--bed-primary);
            outline: none;
            cursor: pointer;
            appearance: none;
        }

        /* Stats Grid */
        .stats-grid {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .stat-card {
            flex: 1;
            min-width: 140px;
            background: var(--bed-bg);
            border: 1px solid var(--bed-border);
            padding: 1rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--bed-white);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--bed-primary);
            font-size: 1.25rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .stat-info h4 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--bed-primary);
        }

        .stat-info p {
            margin: 0;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--bed-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Breadcrumbs */
        .app-breadcrumbs {
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--bed-text-muted);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            background: rgba(255,255,255,0.5);
        }

        .breadcrumb-item {
            cursor: pointer;
            transition: color 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .breadcrumb-item:hover {
            color: var(--bed-primary);
        }

        .breadcrumb-item.active {
            color: var(--bed-primary);
            font-weight: 700;
            pointer-events: none;
        }

        .breadcrumb-separator {
            color: #cbd5e1;
            font-size: 0.8rem;
        }

        /* Dynamic View Area */
        .app-dynamic-view {
            flex: 1;
            overflow-y: auto;
            padding: 2rem;
            position: relative;
        }

        .grid-layout {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            animation: fadeIn 0.4s ease-out forwards;
        }

        .bed-grid-layout {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            animation: fadeIn 0.4s ease-out forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Ward & Room Cards */
        .premium-card {
            background: var(--bed-white);
            border-radius: var(--bed-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(31, 107, 74, 0.05);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .premium-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--bed-primary);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .premium-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-hover);
        }

        .premium-card:hover::before {
            opacity: 1;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--bed-bg);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--bed-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .card-stat-item {
            background: var(--bed-bg);
            padding: 0.75rem;
            border-radius: 12px;
            text-align: center;
        }

        .card-stat-val {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--bed-text-dark);
            margin-bottom: 0.25rem;
        }
        
        .card-stat-val.occ { color: var(--status-occ); }
        .card-stat-val.ava { color: var(--status-avail); }

        .card-stat-label {
            font-size: 0.65rem;
            text-transform: uppercase;
            font-weight: 600;
            color: var(--bed-text-muted);
        }

        /* Progress Bar */
        .progress-bar-container {
            width: 100%;
            height: 6px;
            background: var(--bed-bg);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .progress-bar-fill {
            height: 100%;
            background: var(--bed-primary);
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        /* Individual Bed Cards */
        .bed-card {
            background: var(--bed-white);
            border-radius: var(--bed-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 2px solid transparent;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            transition: all 0.3s;
            position: relative;
        }

        .bed-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .bed-card.status-available { border-color: var(--status-avail); }
        .bed-card.status-occupied { border-color: var(--status-occ); }
        .bed-card.status-blocked { border-color: var(--status-block); }
        .bed-card.status-maintenance { border-color: var(--status-maint); }

        .bed-card-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .bed-num {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--bed-text-dark);
        }

        .bed-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .bed-card.status-available .bed-badge { background: #d1fae5; color: #065f46; }
        .bed-card.status-occupied .bed-badge { background: #fee2e2; color: #991b1b; }
        .bed-card.status-blocked .bed-badge { background: #fef3c7; color: #92400e; }
        .bed-card.status-maintenance .bed-badge { background: #f1f5f9; color: #475569; }

        .bed-info {
            flex: 1;
            font-size: 0.85rem;
            color: var(--bed-text-muted);
        }

        .bed-patient-name {
            font-size: 1rem;
            font-weight: 700;
            color: var(--bed-primary);
            margin-bottom: 0.25rem;
        }

        .bed-action {
            margin-top: 0.5rem;
        }

        .btn-bed-action {
            width: 100%;
            padding: 0.6rem;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-release { background: rgba(239, 68, 68, 0.1); color: var(--status-occ); }
        .btn-release:hover { background: var(--status-occ); color: white; }

        .btn-manage { background: rgba(31, 107, 74, 0.1); color: var(--bed-primary); }
        .btn-manage:hover { background: var(--bed-primary); color: white; }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--bed-text-muted);
            width: 100%;
            grid-column: 1 / -1;
        }

        .empty-state i {
            font-size: 3rem;
            color: rgba(31, 107, 74, 0.2);
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--bed-primary);
            margin-bottom: 0.5rem;
        }

        @media (max-width: 900px) {
            .bed-app-layout {
                flex-direction: column;
                height: auto;
                min-height: calc(100vh - 70px);
            }
            .floor-sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid var(--bed-border);
                height: auto;
            }
            .floor-list {
                flex-direction: row;
                overflow-x: auto;
                padding: 1rem;
            }
            .floor-item {
                min-width: 200px;
            }
            .app-controls {
                flex-direction: column;
            }
            .stats-grid {
                overflow-x: auto;
                flex-wrap: nowrap;
                padding-bottom: 0.5rem;
            }
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
            $pageTitle = 'Bed Allocation';
            include '../../../includes/reception_navbar.php'; 
            ?>
            
            <div class="reception-content" style="padding:0;">
                
                <!-- Main App Layout -->
                <div class="bed-app-layout">
                    <!-- Left: Floor Navigation Sidebar -->
                    <div class="floor-sidebar">
                        <div class="floor-sidebar-header">
                            <h2><i class="far fa-building"></i> Hospital Floors</h2>
                        </div>
                        <div class="floor-list" id="floorList">
                            <!-- Populated by JS -->
                            <div style="text-align:center; padding: 2rem; color: var(--bed-text-muted);">
                                <i class="fas fa-spinner fa-spin fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right: Main Data View -->
                    <div class="bed-app-content">
                        
                        <!-- Top Header -->
                        <div class="app-header">
                            <div class="app-controls">
                                <div class="search-input">
                                    <i class="fas fa-search"></i>
                                    <input type="text" id="globalSearch" placeholder="Search Ward, Room, or Bed..." onkeyup="filterView()">
                                </div>
                                <div class="filter-dropdown">
                                    <select id="statusFilter" onchange="filterView()">
                                        <option value="">All Bed Status</option>
                                        <option value="Available">Available</option>
                                        <option value="Occupied">Occupied</option>
                                        <option value="Blocked">Blocked</option>
                                        <option value="Maintenance">Maintenance</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Summary Stats -->
                            <div class="stats-grid" id="topStats">
                                <!-- Populated by JS -->
                            </div>
                        </div>

                        <!-- Breadcrumbs -->
                        <div class="app-breadcrumbs" id="appBreadcrumbs">
                            <div class="breadcrumb-item active"><i class="fas fa-hospital"></i> Hospital Overview</div>
                        </div>
                        
                        <!-- Dynamic View Area -->
                        <div class="app-dynamic-view" id="appDynamicView">
                            <div class="empty-state">
                                <i class="fas fa-hand-pointer"></i>
                                <h3>Select a Floor</h3>
                                <p>Choose a floor from the left panel to begin managing beds.</p>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="../../public/assets/js/ipd_main.js"></script>
    
    <script>
        // Data Structure
        let hospitalData = {
            floors: {},
            stats: {
                totalFloors: 0,
                totalWards: 0,
                totalRooms: 0,
                totalBeds: 0,
                occupied: 0,
                available: 0,
                maintenance: 0,
                blocked: 0
            }
        };

        // Navigation State
        let currentView = {
            level: 'hospital', // 'hospital', 'floor', 'ward', 'room'
            floor: null,
            ward: null,
            room: null
        };

        // Initialize
        $(document).ready(function() {
            loadBedData();
            // Auto refresh
            setInterval(() => {
                // Only refresh if not deeply interacting, or refresh transparently
                loadBedData(true); 
            }, 30000);
        });

        function loadBedData(isRefresh = false) {
            IPD.ajax('beds', 'GET')
                .then(response => {
                    const beds = response.data.beds || [];
                    buildHierarchy(beds);
                    
                    if(!isRefresh) {
                        renderFloorSidebar();
                        renderTopStats();
                        
                        // Optionally auto-select first floor
                        const floorKeys = Object.keys(hospitalData.floors);
                        if(floorKeys.length > 0) {
                            setTimeout(() => {
                                $('.floor-item').first().click();
                            }, 100);
                        }
                    } else {
                        // Soft refresh current view
                        updateStatsOnly();
                        refreshCurrentView();
                    }
                })
                .catch(error => {
                    IPD.toast(error.message || 'Failed to load bed data', 'error');
                });
        }

        // 1. Data Processing
        function buildHierarchy(beds) {
            // Reset
            hospitalData = {
                floors: {},
                stats: { totalFloors: 0, totalWards: 0, totalRooms: 0, totalBeds: 0, occupied: 0, available: 0, maintenance: 0, blocked: 0 }
            };

            let uniqueFloors = new Set();
            let uniqueWards = new Set();
            let uniqueRooms = new Set();

            beds.forEach(bed => {
                const fName = bed.floor_name || 'Unassigned Floor';
                const wName = bed.ward_name || 'Unassigned Ward';
                const rNum = bed.room_number || '0';

                // Status Normalization (Handle Stale Occupied)
                let status = (bed.bed_status || 'Available').toLowerCase();
                if (status === 'occupied' && !bed.patient_id) status = 'available';
                
                let normStatus = 'Available';
                if(status === 'occupied') normStatus = 'Occupied';
                if(status === 'blocked') normStatus = 'Blocked';
                if(status === 'maintenance' || status === 'maintainance') normStatus = 'Maintenance';

                // Ensure Floor exists
                if (!hospitalData.floors[fName]) {
                    hospitalData.floors[fName] = { name: fName, wards: {}, stats: { total:0, occ:0, avail:0 } };
                    uniqueFloors.add(fName);
                }
                
                // Ensure Ward exists
                if (!hospitalData.floors[fName].wards[wName]) {
                    hospitalData.floors[fName].wards[wName] = { 
                        name: wName, 
                        type: bed.ward_type,
                        rooms: {}, 
                        stats: { total:0, occ:0, avail:0 } 
                    };
                    uniqueWards.add(fName + '_' + wName);
                }

                // Ensure Room exists
                if (!hospitalData.floors[fName].wards[wName].rooms[rNum]) {
                    hospitalData.floors[fName].wards[wName].rooms[rNum] = {
                        number: rNum,
                        name: bed.room_name,
                        type: bed.room_category || bed.room_type,
                        beds: [],
                        stats: { total:0, occ:0, avail:0 }
                    };
                    uniqueRooms.add(fName + '_' + wName + '_' + rNum);
                }

                // Append Bed
                const bedObj = { ...bed, normalized_status: normStatus };
                hospitalData.floors[fName].wards[wName].rooms[rNum].beds.push(bedObj);

                // Aggregate Stats
                hospitalData.stats.totalBeds++;
                hospitalData.floors[fName].stats.total++;
                hospitalData.floors[fName].wards[wName].stats.total++;
                hospitalData.floors[fName].wards[wName].rooms[rNum].stats.total++;

                if (normStatus === 'Occupied') {
                    hospitalData.stats.occupied++;
                    hospitalData.floors[fName].stats.occ++;
                    hospitalData.floors[fName].wards[wName].stats.occ++;
                    hospitalData.floors[fName].wards[wName].rooms[rNum].stats.occ++;
                } else if (normStatus === 'Available') {
                    hospitalData.stats.available++;
                    hospitalData.floors[fName].stats.avail++;
                    hospitalData.floors[fName].wards[wName].stats.avail++;
                    hospitalData.floors[fName].wards[wName].rooms[rNum].stats.avail++;
                } else if (normStatus === 'Blocked') {
                    hospitalData.stats.blocked++;
                } else if (normStatus === 'Maintenance') {
                    hospitalData.stats.maintenance++;
                }
            });

            hospitalData.stats.totalFloors = uniqueFloors.size;
            hospitalData.stats.totalWards = uniqueWards.size;
            hospitalData.stats.totalRooms = uniqueRooms.size;
        }

        // 2. Sidebar & Global Stats
        function renderTopStats() {
            const s = hospitalData.stats;
            const html = `
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-bed"></i></div>
                    <div class="stat-info">
                        <h4>${s.totalBeds}</h4>
                        <p>Total Beds</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="color:var(--status-occ);"><i class="fas fa-user-injured"></i></div>
                    <div class="stat-info">
                        <h4 style="color:var(--status-occ);">${s.occupied}</h4>
                        <p>Occupied</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="color:var(--status-avail);"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <h4 style="color:var(--status-avail);">${s.available}</h4>
                        <p>Available</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="color:var(--status-maint);"><i class="fas fa-tools"></i></div>
                    <div class="stat-info">
                        <h4 style="color:var(--status-maint);">${s.maintenance}</h4>
                        <p>Maintenance</p>
                    </div>
                </div>
            `;
            $('#topStats').html(html);
        }

        function updateStatsOnly() {
            renderTopStats();
            // Just update inner counts on sidebar, keep active state
            Object.values(hospitalData.floors).forEach(floor => {
                const wardsCount = Object.keys(floor.wards).length;
                const el = $(\`.floor-item[data-floor="\${floor.name}"]\`);
                if(el.length) {
                    el.find('.floor-item-meta').text(\`\${wardsCount} Wards • \${floor.stats.total} Beds\`);
                }
            });
        }

        function renderFloorSidebar() {
            const list = $('#floorList');
            list.empty();

            Object.values(hospitalData.floors).forEach(floor => {
                const isActive = (currentView.floor === floor.name) ? 'active' : '';
                const wardsCount = Object.keys(floor.wards).length;
                
                const item = $(\`
                    <div class="floor-item \${isActive}" data-floor="\${floor.name}">
                        <div class="icon"><i class="fas fa-layer-group"></i></div>
                        <div class="floor-item-content">
                            <div class="floor-item-title">\${floor.name}</div>
                            <div class="floor-item-meta">\${wardsCount} Wards • \${floor.stats.total} Beds</div>
                        </div>
                    </div>
                \`);

                item.click(function() {
                    $('.floor-item').removeClass('active');
                    $(this).addClass('active');
                    navigateTo('floor', floor.name);
                });

                list.append(item);
            });
        }

        // 3. Navigation Engine
        function navigateTo(level, fName, wName = null, rNum = null) {
            currentView = { level, floor: fName, ward: wName, room: rNum };
            
            // Render Breadcrumbs
            renderBreadcrumbs();

            // Clear search filter when navigating hierarchically
            $('#globalSearch').val('');
            $('#statusFilter').val('');

            // Render content based on level
            if (level === 'floor') {
                renderWards(fName);
            } else if (level === 'ward') {
                renderRooms(fName, wName);
            } else if (level === 'room') {
                renderBeds(fName, wName, rNum);
            }
        }

        function refreshCurrentView() {
            if (currentView.level === 'floor') renderWards(currentView.floor);
            else if (currentView.level === 'ward') renderRooms(currentView.floor, currentView.ward);
            else if (currentView.level === 'room') renderBeds(currentView.floor, currentView.ward, currentView.room);
        }

        function renderBreadcrumbs() {
            let html = \`<div class="breadcrumb-item" onclick="resetHospital()"><i class="fas fa-hospital"></i> Hospital</div>\`;
            
            if (currentView.floor) {
                html += \`<i class="fas fa-chevron-right breadcrumb-separator"></i>\`;
                const act = currentView.level === 'floor' ? 'active' : '';
                html += \`<div class="breadcrumb-item \${act}" onclick="navigateTo('floor', '\${currentView.floor}')">\${currentView.floor}</div>\`;
            }
            if (currentView.ward) {
                html += \`<i class="fas fa-chevron-right breadcrumb-separator"></i>\`;
                const act = currentView.level === 'ward' ? 'active' : '';
                html += \`<div class="breadcrumb-item \${act}" onclick="navigateTo('ward', '\${currentView.floor}', '\${currentView.ward}')">\${currentView.ward}</div>\`;
            }
            if (currentView.room) {
                html += \`<i class="fas fa-chevron-right breadcrumb-separator"></i>\`;
                const act = currentView.level === 'room' ? 'active' : '';
                html += \`<div class="breadcrumb-item \${act}">Room \${currentView.room}</div>\`;
            }
            $('#appBreadcrumbs').html(html);
        }

        function resetHospital() {
            currentView = { level: 'hospital', floor: null, ward: null, room: null };
            $('.floor-item').removeClass('active');
            renderBreadcrumbs();
            $('#appDynamicView').html(\`
                <div class="empty-state">
                    <i class="fas fa-hand-pointer"></i>
                    <h3>Select a Floor</h3>
                    <p>Choose a floor from the left panel to begin managing beds.</p>
                </div>
            \`);
        }

        // 4. Content Rendering
        function renderWards(floorName) {
            const floor = hospitalData.floors[floorName];
            if (!floor) return;

            let html = \`<div class="grid-layout">\`;
            
            Object.values(floor.wards).forEach(ward => {
                const occPct = ward.stats.total > 0 ? Math.round((ward.stats.occ / ward.stats.total) * 100) : 0;
                
                html += \`
                    <div class="premium-card searchable-card" data-search="\${ward.name.toLowerCase()}" onclick="navigateTo('ward', '\${floorName}', '\${ward.name}')">
                        <div class="card-header">
                            <div class="card-title"><i class="fas fa-hospital-alt"></i> \${ward.name}</div>
                            <span class="bed-badge" style="background:var(--bed-bg); color:var(--bed-primary)">\${Object.keys(ward.rooms).length} Rooms</span>
                        </div>
                        <div class="card-stats">
                            <div class="card-stat-item">
                                <div class="card-stat-val occ">\${ward.stats.occ}</div>
                                <div class="card-stat-label">Occupied</div>
                            </div>
                            <div class="card-stat-item">
                                <div class="card-stat-val ava">\${ward.stats.avail}</div>
                                <div class="card-stat-label">Available</div>
                            </div>
                        </div>
                        <div style="font-size:0.75rem; color:var(--bed-text-muted); display:flex; justify-content:space-between; margin-top:1rem;">
                            <span>Total Beds: \${ward.stats.total}</span>
                            <span>\${occPct}% Full</span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: \${occPct}%; \${occPct > 80 ? 'background:var(--status-occ)' : ''}"></div>
                        </div>
                    </div>
                \`;
            });
            html += \`</div>\`;
            $('#appDynamicView').html(html);
        }

        function renderRooms(floorName, wardName) {
            const ward = hospitalData.floors[floorName].wards[wardName];
            if (!ward) return;

            let html = \`<div class="grid-layout">\`;
            
            Object.values(ward.rooms).forEach(room => {
                const occPct = room.stats.total > 0 ? Math.round((room.stats.occ / room.stats.total) * 100) : 0;
                
                html += \`
                    <div class="premium-card searchable-card" data-search="room \${room.number.toLowerCase()} \${room.name ? room.name.toLowerCase() : ''}" onclick="navigateTo('room', '\${floorName}', '\${wardName}', '\${room.number}')">
                        <div class="card-header">
                            <div class="card-title"><i class="fas fa-door-open"></i> Room \${room.number}</div>
                            <span class="bed-badge" style="background:var(--bed-bg); color:var(--bed-primary)">\${room.type || 'General'}</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:1rem;">
                            <div style="text-align:center;">
                                <div style="font-size:1.5rem; font-weight:700; color:var(--status-occ)">\${room.stats.occ}</div>
                                <div style="font-size:0.65rem; text-transform:uppercase; color:var(--bed-text-muted)">Occupied</div>
                            </div>
                            <div style="text-align:center;">
                                <div style="font-size:1.5rem; font-weight:700; color:var(--status-avail)">\${room.stats.avail}</div>
                                <div style="font-size:0.65rem; text-transform:uppercase; color:var(--bed-text-muted)">Available</div>
                            </div>
                            <div style="text-align:center;">
                                <div style="font-size:1.5rem; font-weight:700; color:var(--bed-text-dark)">\${room.stats.total}</div>
                                <div style="font-size:0.65rem; text-transform:uppercase; color:var(--bed-text-muted)">Total Beds</div>
                            </div>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: \${occPct}%; \${occPct === 100 ? 'background:var(--status-occ)' : ''}"></div>
                        </div>
                    </div>
                \`;
            });
            html += \`</div>\`;
            $('#appDynamicView').html(html);
        }

        function renderBeds(floorName, wardName, roomNum) {
            const room = hospitalData.floors[floorName].wards[wardName].rooms[roomNum];
            if (!room) return;

            let html = \`<div class="bed-grid-layout">\`;
            
            room.beds.forEach(bed => {
                const st = bed.normalized_status.toLowerCase(); // available, occupied, blocked, maintenance
                
                let actionHtml = '';
                let patientHtml = '';

                if (st === 'occupied') {
                    patientHtml = \`
                        <div class="bed-patient-name"><i class="fas fa-user-injured"></i> \${bed.patient_name || 'Unknown Patient'}</div>
                        <div>PID: \${bed.patient_id}</div>
                        <div>Adm: \${IPD.formatDate(bed.admission_date)}</div>
                    \`;
                    actionHtml = \`<button class="btn-bed-action btn-release" onclick="event.stopPropagation(); handleAction('\${bed.bed_id}', 'release')">Release Bed</button>\`;
                } else if (st === 'available') {
                    patientHtml = \`<div style="padding:1rem 0; text-align:center; color:var(--status-avail)"><i class="fas fa-check-circle fa-2x"></i></div>\`;
                    actionHtml = \`<button class="btn-bed-action btn-manage" onclick="event.stopPropagation(); handleAction('\${bed.bed_id}', 'manage')">Change Status</button>\`;
                } else {
                    patientHtml = \`<div style="padding:1rem 0; text-align:center;"><i class="fas fa-ban fa-2x" style="opacity:0.2"></i></div>\`;
                    actionHtml = \`<button class="btn-bed-action btn-manage" onclick="event.stopPropagation(); handleAction('\${bed.bed_id}', 'manage')">Change Status</button>\`;
                }

                html += \`
                    <div class="bed-card status-\${st} searchable-card" data-search="\${bed.bed_number.toLowerCase()} \${bed.patient_name ? bed.patient_name.toLowerCase() : ''}" data-status="\${bed.normalized_status}">
                        <div class="bed-card-head">
                            <div class="bed-num"><i class="fas fa-bed"></i> \${bed.bed_number}</div>
                            <span class="bed-badge">\${bed.normalized_status}</span>
                        </div>
                        <div class="bed-info">
                            \${patientHtml}
                        </div>
                        <div class="bed-action">
                            \${actionHtml}
                        </div>
                    </div>
                \`;
            });
            html += \`</div>\`;
            $('#appDynamicView').html(html);
        }

        // 5. Actions & Search
        function handleAction(bedId, action) {
            if (action === 'release') {
                if (confirm('Are you sure you want to release this bed?')) {
                    IPD.ajax('beds?action=release', 'POST', { bed_id: bedId })
                        .then(() => {
                            IPD.toast('Bed released successfully', 'success');
                            loadBedData();
                        })
                        .catch(err => IPD.toast(err.message, 'error'));
                }
            } else if (action === 'manage') {
                const newStatus = prompt('Change status to (Available/Blocked/Maintenance):', 'Maintenance');
                if (newStatus && ['Available', 'Blocked', 'Maintenance'].includes(newStatus)) {
                    IPD.ajax('beds?id=' + bedId, 'PUT', { status: newStatus })
                        .then(() => {
                            IPD.toast('Status updated', 'success');
                            loadBedData();
                        })
                        .catch(err => IPD.toast(err.message, 'error'));
                }
            }
        }

        function filterView() {
            const query = $('#globalSearch').val().toLowerCase();
            const statusFilter = $('#statusFilter').val();

            $('.searchable-card').each(function() {
                let match = true;
                const txt = $(this).data('search') || '';
                const st = $(this).data('status') || '';

                if (query && !txt.includes(query)) match = false;
                if (statusFilter && currentView.level === 'room' && st !== statusFilter) match = false;

                $(this).toggle(match);
            });
        }
    </script>
</body>
</html>
