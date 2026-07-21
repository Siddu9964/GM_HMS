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
    <title>Ward & Room Management - GM HMS</title>
    
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
        /* Ward Tabs - Horizontal Layout */
        .ward-tabs-container {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow-x: hidden;
        }
        
        .ward-tabs {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 1rem;
            padding-bottom: 0.5rem;
        }
        
        .ward-tab {
            width: 100%;
            height: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .ward-tab::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
            transform: scaleX(0);
            transition: transform 0.3s;
        }
        
        .ward-tab:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(31, 107, 74, 0.2);
            border-color: var(--primary-color);
        }
        
        .ward-tab.active {
            background: var(--primary-gradient);
            border-color: var(--primary-dark);
            color: white;
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(31, 107, 74, 0.4);
        }
        
        .ward-tab.active::before {
            transform: scaleX(1);
        }
        
        .ward-tab-name {
            font-size: 1.125rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .ward-tab-stats {
            font-size: 0.875rem;
            opacity: 0.9;
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        
        .ward-tab.active .ward-tab-stats {
            opacity: 1;
        }
        
        /* Rooms Container */
        .rooms-section {
            display: none;
            animation: slideDown 0.4s ease-out;
        }
        
        .rooms-section.active {
            display: block;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .rooms-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 1rem 1rem 0 0;
            margin-bottom: 0;
        }
        
        .rooms-header h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .rooms-grid {
            background: white;
            padding: 2rem;
            border-radius: 0 0 1rem 1rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        /* Room Card */
        .room-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 2px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.5rem;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .room-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 5px;
            background: var(--primary-gradient);
        }
        
        .room-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
            border-color: var(--primary-color);
        }
        
        .room-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .room-number {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .room-category {
            padding: 0.375rem 0.875rem;
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: var(--primary-dark);
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .beds-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 0.75rem;
        }
        
        /* Bed Status Card */
        .bed-status {
            padding: 1rem;
            border-radius: 0.75rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid;
            position: relative;
        }
        
        .bed-status:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .bed-status.available {
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            border-color: #10b981;
            color: #065f46;
        }
        
        .bed-status.occupied {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            border-color: #ef4444;
            color: #991b1b;
        }
        
        .bed-status.blocked {
            background: linear-gradient(135deg, #fffbeb, #fef3c7);
            border-color: #f59e0b;
            color: #92400e;
        }
        
        .bed-status.maintenance {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-color: #64748b;
            color: #475569;
        }
        
        .bed-number {
            font-size: 0.875rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .bed-status-label {
            font-size: 0.625rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }
        
        .bed-patient {
            font-size: 0.75rem;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid currentColor;
            opacity: 0.8;
        }
        
        /* Legend */
        .legend {
            display: flex;
            gap: 2rem;
            justify-content: center;
            padding: 1rem 2rem;
            background: white;
            border-radius: 0.75rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            flex-wrap: wrap;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .legend-dot {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid;
        }
        
        /* Auto-refresh */
        .auto-refresh {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: white;
            padding: 0.875rem 1.25rem;
            border-radius: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 0.625rem;
            font-size: 0.8125rem;
            font-weight: 600;
            z-index: 1000;
        }
        
        .refresh-dot {
            width: 10px;
            height: 10px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(0.9); }
        }
        
        @media (max-width: 768px) {
            .rooms-grid {
                grid-template-columns: 1fr;
            }
            .ward-tab {
                min-width: 160px;
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
            $pageTitle = 'Bed Management';
            include '../../../includes/reception_navbar.php'; 
            ?>
            
            <!-- Dashboard Content -->
            <div class="reception-content">
                <!-- Page Header -->
                <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;">
                            <i class="fas fa-hospital"></i> Ward & Room Management
                        </h1>
                        <p style="color: var(--gray-600);">Select a ward to view rooms and bed status</p>
                    </div>
                    <a href="/GM_HMS/reception_view/ipd_management/public/index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
        
        <!-- Legend -->
        <div class="legend">
            <div class="legend-item">
                <div class="legend-dot" style="background: #10b981; border-color: #10b981;"></div>
                <span>Available</span>
            </div>
            <div class="legend-item">
                <div class="legend-dot" style="background: #ef4444; border-color: #ef4444;"></div>
                <span>Occupied</span>
            </div>
            <div class="legend-item">
                <div class="legend-dot" style="background: #f59e0b; border-color: #f59e0b;"></div>
                <span>Blocked</span>
            </div>
            <div class="legend-item">
                <div class="legend-dot" style="background: #64748b; border-color: #64748b;"></div>
                <span>Maintenance</span>
            </div>
        </div>
        
        <!-- Ward Tabs -->
        <div class="ward-tabs-container">
            <div class="ward-tabs" id="wardTabs"></div>
        </div>
        
        <!-- Rooms Section -->
        <div id="roomsContainer"></div>
        
        <!-- Auto-refresh indicator -->
        <div class="auto-refresh">
            <div class="refresh-dot"></div>
            <span>Auto-refresh: 30s</span>
        </div>
        </div>
            </div>
            <!-- End Reception Content -->
        </div>
        <!-- End Reception Main Content -->
    </div>
    <!-- End Reception Layout -->
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="../../public/assets/js/ipd_main.js"></script>
    
    <script>
        let wardsData = {};
        let activeWard = null;
        
        function loadBedData() {
            IPD.ajax('beds', 'GET')
                .then(response => {
                    const beds = response.data.beds || [];
                    organizeByWard(beds);
                    displayWardTabs();
                })
                .catch(error => {
                    IPD.toast(error.message || 'Failed to load bed data', 'error');
                });
        }
        
        function organizeByWard(beds) {
            wardsData = {};
            
            beds.forEach(bed => {
                const ward = bed.ward_name;
                const room = bed.room_number;
                
                // Initialize ward
                if (!wardsData[ward]) {
                    wardsData[ward] = {
                        wardName: ward,
                        wardType: bed.ward_type,
                        floorName: bed.floor_name,
                        rooms: {},
                        totalBeds: 0,
                        availableBeds: 0,
                        occupiedBeds: 0,
                        stats: {
                            Available: 0,
                            Occupied: 0,
                            Blocked: 0,
                            Maintenance: 0,
                            Retain: 0
                        }
                    };
                }
                
                // Initialize room
                if (!wardsData[ward].rooms[room]) {
                    wardsData[ward].rooms[room] = {
                        roomNumber: room,
                        roomName: bed.room_name,
                        roomCategory: bed.room_category || bed.room_type,
                        beds: []
                    };
                }
                
                // Add bed
                wardsData[ward].rooms[room].beds.push(bed);
                
                // Update stats
                wardsData[ward].totalBeds++;
                
                // Normalize status key
                let statusKey = bed.bed_status;
                if (!statusKey) statusKey = 'Available'; // Default
                
                // Case correction map
                const map = {
                    'available': 'Available',
                    'occupied': 'Occupied',
                    'blocked': 'Blocked',
                    'maintenance': 'Maintenance',
                    'maintainance': 'Maintenance',
                    'retain': 'Retain'
                };
                
                let normalized = map[statusKey.toLowerCase()] || statusKey;
                
                // Fix stale occupied check
                if (normalized === 'Occupied' && !bed.patient_id) {
                     normalized = 'Available';
                }
                
                if (wardsData[ward].stats[normalized] !== undefined) {
                    wardsData[ward].stats[normalized]++;
                } else {
                     // Just incase
                     wardsData[ward].stats[normalized] = 1;
                }
                
                // Keep legacy counts for compatibility if used elsewhere
                if (normalized === 'Available') wardsData[ward].availableBeds++;
                if (normalized === 'Occupied') wardsData[ward].occupiedBeds++;
            });
        }
        
        function displayWardTabs() {
            const container = $('#wardTabs');
            container.empty();
            
            Object.keys(wardsData).forEach(wardKey => {
                const ward = wardsData[wardKey];
                const isActive = activeWard === wardKey ? 'active' : '';
                const totalRooms = Object.keys(ward.rooms).length;
                
                // Generate badges
                let badgesHtml = '';
                const colors = {
                    'Available': '#10b981',
                    'Occupied': '#ef4444',
                    'Blocked': '#f59e0b',
                    'Maintenance': '#64748b',
                    'Retain': '#8b5cf6'
                };
                
                Object.entries(ward.stats).forEach(([status, count]) => {
                    if (count > 0) {
                        const color = colors[status] || '#94a3b8';
                        badgesHtml += `
                            <span class="badge" style="background: ${color}20; color: ${color}; font-size: 0.7rem; margin-right: 4px; padding: 4px 8px; border-radius: 4px; border: 1px solid ${color}40;">
                                ${status}: ${count}
                            </span>
                        `;
                    }
                });
                
                const tabHtml = `
                    <div class="ward-tab ${isActive}" data-ward="${wardKey}" style="flex-direction: column; align-items: flex-start; gap: 4px; height: auto;">
                        <div style="display: flex; justify-content: space-between; width: 100%; align-items: center;">
                            <div class="ward-tab-name" style="font-size: 0.9rem;">
                                <i class="fas fa-hospital-alt"></i> ${ward.wardName}
                            </div>
                        </div>
                        
                        <div style="font-size: 0.75rem; color: #64748b; display: flex; gap: 8px;">
                            <span><i class="fas fa-door-open"></i> R: ${totalRooms}</span>
                            <span><i class="fas fa-bed"></i> B: ${ward.totalBeds}</span>
                        </div>
                        
                        <div style="display: flex; flex-wrap: wrap; gap: 4px; margin-top: 4px;">
                            ${badgesHtml}
                        </div>
                    </div>
                `;
                container.append(tabHtml);
            });
            
            // Add click handlers
            $('.ward-tab').click(function() {
                const wardName = $(this).data('ward');
                selectWard(wardName);
            });
            
            // Display rooms for active ward
            if (activeWard && wardsData[activeWard]) {
                displayRooms(activeWard);
            }
        }
        
        function selectWard(wardName) {
            activeWard = wardName;
            
            // Hide all other ward tabs
            $('.ward-tab').hide();
            $(`.ward-tab[data-ward="${wardName}"]`).show().addClass('active').css('width', '100%');
            
            // Setup container for single view
            $('.ward-tabs-container').addClass('single-view');
            
            displayRooms(wardName);
        }
        
        function showAllWards() {
            activeWard = null;
            $('.ward-tab').show().removeClass('active').css('width', '');
            $('.ward-tabs-container').removeClass('single-view');
            $('#roomsContainer').empty();
        }
        
        function displayRooms(wardName) {
            const container = $('#roomsContainer');
            container.empty();
            
            const ward = wardsData[wardName];
            if (!ward) return;
            
            const roomsHtml = `
                <div class="rooms-section active">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <button class="btn btn-outline btn-sm" onclick="showAllWards()">
                            <i class="fas fa-arrow-left"></i> Back to Wards
                        </button>
                    </div>
                    <div class="rooms-header">
                        <h3>
                            <i class="fas fa-door-open"></i>
                            ${ward.wardName} - ${ward.floorName}
                            <span style="opacity: 0.8; font-size: 1rem; margin-left: 1rem;">
                                ${Object.keys(ward.rooms).length} Rooms • ${ward.totalBeds} Beds
                            </span>
                        </h3>
                    </div>
                    <div class="rooms-grid">
                        ${displayRoomCards(ward.rooms)}
                    </div>
                </div>
            `;
            
            container.html(roomsHtml);
        }
        
        function displayRoomCards(rooms) {
            let html = '';
            
            Object.values(rooms).forEach(room => {
                html += `
                    <div class="room-card">
                        <div class="room-header">
                            <div class="room-number">
                                <i class="fas fa-door-closed"></i>
                                Room ${room.roomNumber}
                            </div>
                            <div class="room-category">${room.roomCategory}</div>
                        </div>
                        <div class="beds-container">
                            ${displayBedCards(room.beds)}
                        </div>
                    </div>
                `;
            });
            
            return html;
        }
        
        function displayBedCards(beds) {
            let html = '';
            
            beds.forEach(bed => {
                // Handle empty status as 'Available' for better UX
                const rawStatus = bed.bed_status || '';
                let statusClass = rawStatus.toLowerCase();
                
                if (statusClass === '' && !bed.patient_id) {
                    statusClass = 'available';
                } else if (statusClass === '') {
                    statusClass = 'unknown';
                }
                
                // Fix for Stale "Occupied" status (Patient discharged but status not updated)
                if (statusClass === 'occupied' && !bed.patient_id) {
                    statusClass = 'available';
                }

                let bedContent = '';

                // Handle specific DB statuses
                if (statusClass === 'occupied' && bed.patient_name) {
                    const admissionDate = IPD.formatDate(bed.admission_date);
                    bedContent = `
                        <div class="bed-detail-row patient-name">
                            <i class="fas fa-user-injured"></i> ${bed.patient_name}
                        </div>
                        <div class="bed-detail-row">
                            <i class="fas fa-id-badge"></i> PID: ${bed.patient_id}
                        </div>
                        <div class="bed-detail-row">
                            <i class="fas fa-notes-medical"></i> ADM: #${bed.admission_id}
                        </div>
                         <div class="bed-detail-row">
                            <i class="fas fa-calendar-alt"></i> ${admissionDate}
                        </div>
                    `;
                } else if (statusClass === 'available') {
                    bedContent = `<div class="bed-available-text">Available for Admission</div>`;
                } else if (statusClass === 'maintainance' || statusClass === 'maintenance') {
                    bedContent = `<div class="bed-status-text text-secondary">Maintenance Mode</div>`;
                } else if (statusClass === 'blocked') {
                    bedContent = `<div class="bed-status-text text-warning">Bed Blocked</div>`;
                } else if (statusClass === 'retain') {
                     bedContent = `<div class="bed-status-text text-info">Retained</div>`;
                } else {
                     // Fallback 
                     const displayStatus = bed.bed_status || 'Unknown';
                     bedContent = `<div class="bed-status-text">${displayStatus}</div>`;
                }
                
                html += `
                    <div class="bed-status ${statusClass}" 
                         onclick="manageBed('${bed.bed_id}', '${bed.bed_status}')"
                         title="${bed.bed_number} - ${bed.bed_status}">
                        <div class="bed-number">
                            <span>🛏️ ${bed.bed_number}</span>
                             ${bed.bed_status === 'Occupied' ? '<span class="status-dot occupied"></span>' : ''}
                        </div>
                        
                        <div class="bed-info-container">
                            ${bedContent}
                        </div>
                    </div>
                `;
            });
            
            return html;
        }
        
        function manageBed(bedId, status) {
            if (status === 'Occupied') {
                if (confirm('Release this bed?')) {
                    IPD.ajax('beds?action=release', 'POST', { bed_id: bedId })
                        .then(() => {
                            IPD.toast('Bed released successfully', 'success');
                            loadBedData();
                        })
                        .catch(error => {
                            IPD.toast(error.message || 'Failed to release bed', 'error');
                        });
                }
            } else if (status === 'Available') {
                const newStatus = prompt('Change status to (Blocked/Maintenance):', 'Maintenance');
                if (newStatus && ['Blocked', 'Maintenance', 'Available'].includes(newStatus)) {
                    IPD.ajax('beds?id=' + bedId, 'PUT', { status: newStatus })
                        .then(() => {
                            IPD.toast('Bed status updated', 'success');
                            loadBedData();
                        })
                        .catch(error => {
                            IPD.toast(error.message || 'Failed to update bed status', 'error');
                        });
                }
            } else {
                IPD.toast('This bed is in ' + status + ' status', 'info');
            }
        }
        
        // Initialize
        $(document).ready(function() {
            loadBedData();
            setInterval(loadBedData, 30000); // Auto-refresh every 30s
        });
    </script>
</body>
</html>
