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
        .floor-section {
            background: white;
            border-radius: 1.25rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-left: 6px solid #1f6b4a;
        }
        
        .floor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .floor-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .floor-stats {
            display: flex;
            gap: 1.5rem;
        }
        
        .floor-stat {
            text-align: center;
            padding: 0.5rem 1rem;
            background: #f8fafc;
            border-radius: 0.75rem;
        }
        
        .floor-stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
        }
        
        .floor-stat-label {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .ward-section {
            background: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 2px solid #e2e8f0;
        }
        
        .ward-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .ward-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .ward-info {
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .room-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1rem;
            border: 2px solid #e2e8f0;
            transition: all 0.3s;
        }
        
        .room-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .room-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .room-number {
            font-weight: 700;
            color: #1e293b;
            font-size: 1rem;
        }
        
        .room-category {
            font-size: 0.75rem;
            padding: 0.25rem 0.625rem;
            background: #dbeafe;
            color: #144d34;
            border-radius: 0.375rem;
            font-weight: 600;
        }
        
        .beds-in-room {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .bed-mini {
            flex: 1;
            min-width: 100px;
            padding: 0.75rem;
            border-radius: 0.5rem;
            text-align: center;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
        }
        
        .bed-mini:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
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
            font-size: 0.75rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .bed-mini-status {
            font-size: 0.625rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .legend {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            padding: 1.5rem;
            background: white;
            border-radius: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .legend-color {
            width: 24px;
            height: 24px;
            border-radius: 0.375rem;
            border: 2px solid;
        }
        
        .auto-refresh {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
            z-index: 1000;
        }
        
        .refresh-indicator {
            width: 12px;
            height: 12px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
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
