<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Floor-Wise Bed Management - GM HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="../../public/assets/css/ipd_main.css">
    <style>
        :root {
            --em: #1f6b4a;
            --em-d: #144d34;
            --em-l: #e8f5ef;
            --bg: #f8fafc;
            --border: #e2e8f0;
            --text: #1e293b;
            --text-lt: #64748b;
        }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            background-color: var(--bg);
            font-family: 'Inter', sans-serif;
            color: var(--text);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        .dashboard-container {
            padding: clamp(12px, 2.5vw, 24px);
            max-width: 1600px;
            margin: 0 auto;
            width: 100%;
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 12px;
        }
        .dashboard-header h1 {
            font-size: clamp(1.25rem, 2.2vw, 1.75rem);
            font-weight: 800;
            color: var(--text);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .dashboard-header h1 i { color: var(--em); }
        .dashboard-header p {
            font-size: clamp(0.75rem, 1.1vw, 0.875rem);
            color: var(--text-lt);
            margin: 4px 0 0;
        }
        .dashboard-header .btn {
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .floor-section {
            background: white;
            border-radius: 1.25rem;
            padding: clamp(1rem, 2vw, 2rem);
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.06);
            border-left: 6px solid var(--em);
            border-top: 1px solid var(--border);
            border-right: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }
        
        .floor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border);
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .floor-title {
            font-size: clamp(1.15rem, 1.8vw, 1.5rem);
            font-weight: 800;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .floor-title i { color: var(--em); }
        
        .floor-stats {
            display: grid;
            grid-template-columns: repeat(4, auto);
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        
        .floor-stat {
            text-align: center;
            padding: 0.4rem 0.8rem;
            background: #f8fafc;
            border-radius: 0.6rem;
            border: 1px solid var(--border);
            min-width: 70px;
        }
        
        .floor-stat-value {
            font-size: clamp(1.1rem, 1.6vw, 1.35rem);
            font-weight: 800;
            color: var(--text);
            line-height: 1.1;
        }
        
        .floor-stat-label {
            font-size: 0.65rem;
            color: var(--text-lt);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
            margin-top: 2px;
        }
        
        .ward-section {
            background: #f8fafc;
            border-radius: 1rem;
            padding: clamp(0.75rem, 1.5vw, 1.25rem);
            margin-bottom: 1.25rem;
            border: 1.5px solid var(--border);
        }
        
        .ward-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .ward-name {
            font-size: clamp(1rem, 1.4vw, 1.15rem);
            font-weight: 700;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .ward-name i { color: var(--em); font-size: 0.95rem; }
        
        .ward-info {
            font-size: 0.8rem;
            color: var(--text-lt);
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        
        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 0.75rem;
            margin-top: 0.75rem;
        }
        
        .room-card {
            background: white;
            border-radius: 0.75rem;
            padding: 0.85rem;
            border: 1.5px solid var(--border);
            transition: all 0.25s ease;
        }
        
        .room-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border-color: var(--em);
        }
        
        .room-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.6rem;
            padding-bottom: 0.6rem;
            border-bottom: 1px solid var(--border);
            gap: 6px;
        }
        
        .room-number {
            font-weight: 700;
            color: var(--text);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .room-number i { color: var(--em); font-size: 0.85rem; }
        
        .room-category {
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            background: #e8f5ef;
            color: var(--em);
            border-radius: 0.375rem;
            font-weight: 700;
            white-space: nowrap;
        }
        
        .beds-in-room {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 0.4rem;
        }
        
        .bed-mini {
            padding: 0.5rem 0.4rem;
            border-radius: 0.5rem;
            text-align: center;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: 1.5px solid transparent;
            min-width: 0;
        }
        
        .bed-mini:hover {
            transform: scale(1.04);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.12);
        }
        
        .bed-mini.available {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border-color: #10b981;
        }
        
        .bed-mini.occupied {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border-color: #ef4444;
        }
        
        .bed-mini.blocked {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border-color: #f59e0b;
        }
        
        .bed-mini.maintenance {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #475569;
            border-color: #64748b;
        }
        
        .bed-mini-number {
            font-size: 0.72rem;
            font-weight: 800;
            margin-bottom: 0.15rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .bed-mini-status {
            font-size: 0.6rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .legend {
            display: flex;
            gap: 1.25rem;
            justify-content: center;
            align-items: center;
            padding: 1rem 1.5rem;
            background: white;
            border-radius: 1rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border);
            flex-wrap: wrap;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .legend-color {
            width: 18px;
            height: 18px;
            border-radius: 0.3rem;
            border: 2px solid;
            flex-shrink: 0;
        }
        
        .auto-refresh {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            background: white;
            padding: 0.6rem 1rem;
            border-radius: 2rem;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-lt);
            z-index: 1000;
            backdrop-filter: blur(8px);
        }
        
        .refresh-indicator {
            width: 10px;
            height: 10px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse 2s infinite;
            flex-shrink: 0;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }

        /* Mobile Breakpoints */
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .dashboard-header .btn {
                width: 100%;
                justify-content: center;
            }
            .floor-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .floor-stats {
                width: 100%;
                grid-template-columns: repeat(2, 1fr);
            }
            .ward-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .legend {
                justify-content: flex-start;
                padding: 0.75rem 1rem;
                gap: 0.75rem 1rem;
            }
            .room-grid {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            }
            .auto-refresh {
                bottom: 1rem;
                right: 1rem;
                padding: 0.4rem 0.75rem;
                font-size: 0.7rem;
            }
        }
        @media (max-width: 480px) {
            .room-grid {
                grid-template-columns: 1fr;
            }
            .beds-in-room {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div>
                <h1><i class="fas fa-building"></i> Floor-Wise Bed Management</h1>
                <p>Real-time bed status organized by floors, wards, and rooms</p>
            </div>
            <a href="../../public/index.php" class="btn btn-light mt-2">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <!-- Legend -->
        <div class="legend">
            <div class="legend-item">
                <div class="legend-color available" style="background: #d1fae5; border-color: #10b981;"></div>
                <span>Available</span>
            </div>
            <div class="legend-item">
                <div class="legend-color occupied" style="background: #fee2e2; border-color: #ef4444;"></div>
                <span>Occupied</span>
            </div>
            <div class="legend-item">
                <div class="legend-color blocked" style="background: #fef3c7; border-color: #f59e0b;"></div>
                <span>Blocked</span>
            </div>
            <div class="legend-item">
                <div class="legend-color maintenance" style="background: #f1f5f9; border-color: #64748b;"></div>
                <span>Maintenance</span>
            </div>
        </div>
        
        <!-- Floors Container -->
        <div id="floorsContainer"></div>
        
        <!-- Auto-refresh indicator -->
        <div class="auto-refresh">
            <div class="refresh-indicator"></div>
            <span>Auto-refreshing every 30s</span>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="../../public/assets/js/ipd_main.js"></script>
    
    <script>
        let floorsData = {};
        
        function loadFloorWiseData() {
            IPD.ajax('beds', 'GET')
                .then(response => {
                    const beds = response.data.beds || [];
                    organizeByFloor(beds);
                    displayFloors();
                })
                .catch(error => {
                    IPD.toast(error.message || 'Failed to load bed data', 'error');
                });
        }
        
        function organizeByFloor(beds) {
            floorsData = {};
            
            beds.forEach(bed => {
                const floor = bed.floor_number;
                const ward = bed.ward_name;
                const room = bed.room_number;
                
                // Initialize floor
                if (!floorsData[floor]) {
                    floorsData[floor] = {
                        floorName: bed.floor_name,
                        floorNumber: floor,
                        wards: {},
                        totalBeds: 0,
                        availableBeds: 0,
                        occupiedBeds: 0
                    };
                }
                
                // Initialize ward
                if (!floorsData[floor].wards[ward]) {
                    floorsData[floor].wards[ward] = {
                        wardName: ward,
                        wardType: bed.ward_type,
                        rooms: {}
                    };
                }
                
                // Initialize room
                if (!floorsData[floor].wards[ward].rooms[room]) {
                    floorsData[floor].wards[ward].rooms[room] = {
                        roomNumber: room,
                        roomName: bed.room_name,
                        roomCategory: bed.room_category || bed.room_type,
                        beds: []
                    };
                }
                
                // Add bed to room
                floorsData[floor].wards[ward].rooms[room].beds.push(bed);
                
                // Update floor stats
                floorsData[floor].totalBeds++;
                if (bed.bed_status === 'Available') floorsData[floor].availableBeds++;
                if (bed.bed_status === 'Occupied') floorsData[floor].occupiedBeds++;
            });
        }
        
        function displayFloors() {
            const container = $('#floorsContainer');
            container.empty();
            
            // Sort floors by floor number
            const sortedFloors = Object.values(floorsData).sort((a, b) => a.floorNumber - b.floorNumber);
            
            sortedFloors.forEach(floor => {
                const floorHtml = `
                    <div class="floor-section">
                        <div class="floor-header">
                            <div class="floor-title">
                                <i class="fas fa-layer-group"></i>
                                ${floor.floorName}
                            </div>
                            <div class="floor-stats">
                                <div class="floor-stat">
                                    <div class="floor-stat-value">${Object.keys(floor.wards).length}</div>
                                    <div class="floor-stat-label">Wards</div>
                                </div>
                                <div class="floor-stat">
                                    <div class="floor-stat-value">${floor.totalBeds}</div>
                                    <div class="floor-stat-label">Total Beds</div>
                                </div>
                                <div class="floor-stat">
                                    <div class="floor-stat-value" style="color: #10b981;">${floor.availableBeds}</div>
                                    <div class="floor-stat-label">Available</div>
                                </div>
                                <div class="floor-stat">
                                    <div class="floor-stat-value" style="color: #ef4444;">${floor.occupiedBeds}</div>
                                    <div class="floor-stat-label">Occupied</div>
                                </div>
                            </div>
                        </div>
                        <div class="wards-container">
                            ${displayWards(floor.wards)}
                        </div>
                    </div>
                `;
                container.append(floorHtml);
            });
        }
        
        function displayWards(wards) {
            let wardsHtml = '';
            
            Object.values(wards).forEach(ward => {
                const totalRooms = Object.keys(ward.rooms).length;
                const totalBeds = Object.values(ward.rooms).reduce((sum, room) => sum + room.beds.length, 0);
                
                wardsHtml += `
                    <div class="ward-section">
                        <div class="ward-header">
                            <div class="ward-name">
                                <i class="fas fa-hospital"></i>
                                ${ward.wardName}
                            </div>
                            <div class="ward-info">
                                <span class="badge badge-primary">${ward.wardType}</span>
                                <span class="ms-2">${totalRooms} Rooms • ${totalBeds} Beds</span>
                            </div>
                        </div>
                        <div class="room-grid">
                            ${displayRooms(ward.rooms)}
                        </div>
                    </div>
                `;
            });
            
            return wardsHtml;
        }
        
        function displayRooms(rooms) {
            let roomsHtml = '';
            
            Object.values(rooms).forEach(room => {
                roomsHtml += `
                    <div class="room-card">
                        <div class="room-header">
                            <div class="room-number">
                                <i class="fas fa-door-open"></i> Room ${room.roomNumber}
                            </div>
                            <div class="room-category">${room.roomCategory}</div>
                        </div>
                        <div class="beds-in-room">
                            ${displayBeds(room.beds)}
                        </div>
                    </div>
                `;
            });
            
            return roomsHtml;
        }
        
        function displayBeds(beds) {
            let bedsHtml = '';
            
            beds.forEach(bed => {
                const statusClass = bed.bed_status.toLowerCase();
                const patientInfo = bed.patient_name ? `<br><small>👤 ${bed.patient_name}</small>` : '';
                
                bedsHtml += `
                    <div class="bed-mini ${statusClass}" onclick="manageBed('${bed.bed_id}', '${bed.bed_status}')" title="${bed.bed_number} - ${bed.bed_status}${bed.patient_name ? ' - ' + bed.patient_name : ''}">
                        <div class="bed-mini-number">🛏️ ${bed.bed_number}</div>
                        <div class="bed-mini-status">${bed.bed_status}</div>
                        ${patientInfo}
                    </div>
                `;
            });
            
            return bedsHtml;
        }
        
        function manageBed(bedId, status) {
            if (status === 'Occupied') {
                if (confirm('Release this bed?')) {
                    IPD.ajax('beds?action=release', 'POST', { bed_id: bedId })
                        .then(() => {
                            IPD.toast('Bed released successfully', 'success');
                            loadFloorWiseData();
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
                            loadFloorWiseData();
                        })
                        .catch(error => {
                            IPD.toast(error.message || 'Failed to update bed status', 'error');
                        });
                }
            } else {
                IPD.toast('This bed is in ' + status + ' status', 'info');
            }
        }
        
        // Initial load
        $(document).ready(function() {
            loadFloorWiseData();
            
            // Auto-refresh every 30 seconds
            setInterval(loadFloorWiseData, 30000);
        });
    </script>
</body>
</html>
