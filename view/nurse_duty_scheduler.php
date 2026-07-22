<?php
session_start();
// Auth check – mirror pattern of other admin pages
// if (!isset($_SESSION['user_id'])) { header('Location: /GM_HMS/login.php'); exit; }

// Connect to Database
$conn = new mysqli('localhost', 'root', '', 'hmsc_basaveshwranagara');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all beds to assign dynamically
$beds = [];
$res_beds = $conn->query("SELECT floor_name, ward_name, room_type, room_number FROM hospital_beds WHERE bed_status != 'Maintenance'");
if($res_beds) {
    while($row = $res_beds->fetch_assoc()) {
        $beds[] = $row;
    }
}
$beds_json = json_encode($beds);

// Fetch staff (nurses) for the schedule builder
$staff_nurses = [];
$res_staff = $conn->query("SELECT sl_no as id, full_name as name, designation as dept, qualification as qual, experience_years as exp, mobile_number as phone FROM staff WHERE full_name != ''");
if($res_staff) {
    while($row = $res_staff->fetch_assoc()) {
        $staff_nurses[] = [
            'id' => 'EMP-'.$row['id'],
            'name' => $row['name'],
            'dept' => $row['dept'] ?: 'Nursing',
            'qual' => $row['qual'] ?: 'N/A',
            'exp' => ($row['exp'] ? $row['exp'].' yrs' : 'N/A'),
            'phone' => $row['phone'] ?: 'N/A'
        ];
    }
}
$nurses_json = json_encode($staff_nurses);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">
    <link rel="stylesheet" href="/GM_HMS/assets/css/organic_map.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Duty Scheduler - GM HMS</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        /* Custom Shift Pills */
        .shift-pill {
            padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 800;
            cursor: pointer; transition: 0.2s; border: 2px solid transparent; background: #fff; color: #4b5563;
        }
        .shift-pill.active { background: #1f6b4a; color: #fff; box-shadow: 0 4px 12px rgba(31,107,74,0.2); }
        
        .nurse-slot {
            width: 50px; height: 50px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; font-weight: 800; color: #fff; margin: 0 auto 6px auto;
            position: relative; transition: 0.2s; border: 3px solid #fff; box-shadow: var(--shadow-sm);
        }
        .nurse-slot:hover { transform: scale(1.1); cursor: pointer; }
        .slot-unassigned { background: #e5e7eb; color: #9ca3af; border: 3px dashed #cbd5e1; box-shadow: none; }
        .slot-assigned { background: #1f6b4a; box-shadow: 0 0 0 5px rgba(31,107,74,0.1), 0 0 14px rgba(31,107,74,0.2); animation: glowPulse 2s infinite; }
        
        .panel-nurse-list { max-height: 500px; overflow-y: auto; margin-top: 10px; }
        .nurse-item { padding: 12px 16px; border-radius: 12px; border: 2px solid #e5e7eb; margin-bottom: 8px; cursor: pointer; display: flex; align-items: center; gap: 12px; transition: 0.2s; background: #fff;}
        .nurse-item:hover { background: #f3efe6; border-color: #1f6b4a; }
        .nurse-avatar { width: 40px; height: 40px; border-radius: 50%; background: #1f6b4a; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 15px; }
    </style>
</head>
<body class="bg-[#f0ebe0] text-slate-900">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Navbar -->
            <?php include 'includes/navbar.php'; ?>
            
            <main class="flex-1 overflow-y-auto p-6" id="main-layout">
                <!-- Page Header -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 animate-fade-in">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <span class="px-3 py-1 bg-white text-[#1f6b4a] text-[10px] font-black uppercase tracking-widest rounded-full shadow-sm">Staff Management</span>
                        </div>
                        <h1 class="text-3xl font-black tracking-tight text-[#1f6b4a] flex items-center gap-3">
                            <div class="p-3 rounded-2xl shadow-lg shadow-emerald-200" style="background: #1f6b4a;">
                                <i class="fas fa-user-nurse text-white text-xl"></i>
                            </div>
                            Nurse Duty Scheduler
                        </h1>
                        <p class="text-sm font-semibold text-slate-500 mt-2 ml-14">Map nurses to specific wards and rooms dynamically.</p>
                    </div>

                    <!-- Shift Toggles -->
                    <div class="flex items-center gap-2 bg-white p-1 rounded-full shadow-sm border border-[#e5e7eb]">
                        <button class="shift-pill active" onclick="switchShift('Morning')"><i class="fas fa-sun mr-2"></i>Morning</button>
                        <button class="shift-pill" onclick="switchShift('Afternoon')"><i class="fas fa-cloud-sun mr-2"></i>Afternoon</button>
                        <button class="shift-pill" onclick="switchShift('Night')"><i class="fas fa-moon mr-2"></i>Night</button>
                    </div>
                </div>

                <div id="all-floors-content"></div>
            </main>
        </div>
    </div>

    <!-- Side Panel -->
    <div id="side-panel-overlay" class="side-panel-overlay" onclick="closePanel()"></div>
    <div id="side-panel" class="side-panel" style="width: 400px; max-width: 90vw;">
        <button class="panel-close" onclick="closePanel()"><i class="fas fa-times"></i></button>
        <div class="panel-header">
            <h2 class="panel-title" id="panel-title">Assign Nurse</h2>
            <div class="panel-subtitle" id="panel-subtitle">Morning Shift</div>
        </div>
        <div class="panel-content" id="panel-details">
            <!-- Assignment List will be injected here -->
        </div>
    </div>

    <script>
        // Data injected from PHP
        const allBeds = <?php echo isset($beds_json) ? $beds_json : "[]"; ?>;
        const allNurses = <?php echo isset($nurses_json) ? $nurses_json : "[]"; ?>;
        
        let currentShift = 'Morning';
        let hierarchy = {};
        // Mock assignments: { "Floor 1|Ward A|Room 101|Morning": "EMP-1" }
        let assignments = {}; 

        document.addEventListener('DOMContentLoaded', () => {
            buildHierarchy();
            renderAllFloors();
        });

        function switchShift(shift) {
            currentShift = shift;
            document.querySelectorAll('.shift-pill').forEach(btn => {
                btn.classList.toggle('active', btn.innerText.includes(shift));
            });
            renderAllFloors();
        }

        function buildHierarchy() {
            hierarchy = {};
            allBeds.forEach(bed => {
                const floorKey = bed.floor_name  || ('Floor ' + (bed.floor_number || 0));
                const floorNum = Number(bed.floor_number) || 0;
                const ward     = bed.ward_name   || 'General Ward';
                const roomName = bed.room_name   || bed.room_number || 'Room';
                const room     = roomName + (bed.room_type && bed.room_type !== roomName ? ' - ' + bed.room_type : '');

                if (!hierarchy[floorKey]) hierarchy[floorKey] = { num: floorNum, wards: {} };
                if (!hierarchy[floorKey].wards[ward]) hierarchy[floorKey].wards[ward] = {};
                if (!hierarchy[floorKey].wards[ward][room]) hierarchy[floorKey].wards[ward][room] = [];
                hierarchy[floorKey].wards[ward][room].push(bed);
            });
        }

        function generateOrganicPath(width, height) {
            const rx = width / 2; const ry = height / 2;
            const variation = Math.min(rx, ry) * 0.15;
            const points = [];
            const numPoints = 8;
            for (let i = 0; i < numPoints; i++) {
                const angle = (i / numPoints) * Math.PI * 2;
                const r = rx - variation + Math.random() * variation * 2;
                points.push({ x: width/2 + Math.cos(angle) * r, y: height/2 + Math.sin(angle) * (ry - variation + Math.random() * variation * 2) });
            }
            let d = `M ${points[0].x} ${points[0].y}`;
            for (let i = 0; i < numPoints; i++) {
                const p1 = points[i]; const p2 = points[(i + 1) % numPoints];
                const cp1x = p1.x + (p2.x - p1.x) / 3; const cp1y = p1.y + (p2.y - p1.y) / 3;
                const cp2x = p1.x + (p2.x - p1.x) * 2/3; const cp2y = p1.y + (p2.y - p1.y) * 2/3;
                d += ` C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${p2.x} ${p2.y}`;
            }
            return d + ' Z';
        }

        function renderAllFloors() {
            const container = document.getElementById('all-floors-content');
            container.innerHTML = '';
            
            const sortedFloors = Object.entries(hierarchy).sort((a, b) => a[1].num - b[1].num);
            const roomColors = ['room-color-0', 'room-color-1', 'room-color-2', 'room-color-3', 'room-color-4'];
            let globalRoomColorIdx = 0;

            if (sortedFloors.length === 0) {
                container.innerHTML = `<div class="panel-empty" style="text-align: center; padding: 60px 0;">
                    <i class="fas fa-user-nurse" style="font-size: 40px; color: #1f6b4a; margin-bottom: 20px;"></i>
                    <p style="font-size: 18px; font-weight: 800; color: #4b5563;">No Rooms Configured</p>
                    <p style="font-size: 14px; margin-top: 6px; color: #6b7280;">Add rooms in Hospital Beds Directory first to schedule nurses.</p>
                </div>`;
                return;
            }

            sortedFloors.forEach(([floorName, floorData]) => {
                const floorSection = document.createElement('div');
                floorSection.className = 'floor-section-wrap';
                
                const floorHeader = document.createElement('div');
                floorHeader.className = 'floor-heading-bar';
                floorHeader.innerHTML = `<i class="fas fa-building"></i> ${floorName}`;
                floorSection.appendChild(floorHeader);

                const canvas = document.createElement('div');
                canvas.className = 'floor-canvas';

                const sortedWards = Object.entries(floorData.wards).sort((a, b) => a[0].localeCompare(b[0]));

                sortedWards.forEach(([wardName, rooms]) => {
                    const wardDiv = document.createElement('div');
                    wardDiv.style.marginBottom = '24px';

                    const wardTitle = document.createElement('div');
                    wardTitle.className = 'ward-title';
                    wardTitle.innerHTML = `<i class="fas fa-layer-group"></i> ${wardName}`;
                    wardDiv.appendChild(wardTitle);

                    const roomGrid = document.createElement('div');
                    roomGrid.style.display = 'flex'; roomGrid.style.flexWrap = 'wrap'; roomGrid.style.gap = '20px';

                    const sortedRooms = Object.entries(rooms).sort((a, b) => a[0].localeCompare(b[0]));

                    sortedRooms.forEach(([roomName, beds]) => {
                        const roomWrap = document.createElement('div');
                        roomWrap.className = 'room-blob-wrap';

                        const rWidth = 260; const rHeight = 220;
                        const svgHtml = `<svg width="${rWidth}" height="${rHeight}" viewBox="0 0 ${rWidth} ${rHeight}">
                            <path d="${generateOrganicPath(rWidth, rHeight)}" class="room-blob-path ${roomColors[globalRoomColorIdx % roomColors.length]}"></path>
                        </svg>`;
                        globalRoomColorIdx++;

                        roomWrap.innerHTML = svgHtml;

                        const contentDiv = document.createElement('div');
                        contentDiv.className = 'room-content';
                        
                        const assignKey = `${floorName}|${wardName}|${roomName}|${currentShift}`;
                        const assignedNurseId = assignments[assignKey];
                        const assignedNurse = allNurses.find(n => n.id === assignedNurseId);

                        let slotHtml = '';
                        if (assignedNurse) {
                            const initials = assignedNurse.name.split(' ').map(n=>n[0]).join('').substring(0,2);
                            slotHtml = `
                                <div class="nurse-slot slot-assigned" onclick="openPanel('${floorName}', '${wardName}', '${roomName}')">
                                    ${initials}
                                </div>
                                <div style="font-size:13px; font-weight:800; color:#1f6b4a;">${assignedNurse.name}</div>
                                <div style="font-size:11px; font-weight:600; color:#6b7280;">Assigned</div>
                            `;
                        } else {
                            slotHtml = `
                                <div class="nurse-slot slot-unassigned" onclick="openPanel('${floorName}', '${wardName}', '${roomName}')">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <div style="font-size:13px; font-weight:700; color:#9ca3af;">Unassigned</div>
                            `;
                        }

                        contentDiv.innerHTML = `
                            <div class="room-label" style="font-size:15px; margin-bottom: 20px;">${roomName}</div>
                            <div style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center;">
                                ${slotHtml}
                            </div>
                        `;

                        roomWrap.appendChild(contentDiv);
                        roomGrid.appendChild(roomWrap);
                    });

                    wardDiv.appendChild(roomGrid);
                    canvas.appendChild(wardDiv);
                });

                floorSection.appendChild(canvas);
                container.appendChild(floorSection);
            });
        }

        window.openPanel = function(floor, ward, room) {
            const title = document.getElementById('panel-title');
            const subtitle = document.getElementById('panel-subtitle');
            const details = document.getElementById('panel-details');
            
            title.innerHTML = `<i class="fas fa-user-md mr-2"></i>${room}`;
            subtitle.innerText = `${floor} • ${ward} • ${currentShift} Shift`;

            const assignKey = `${floor}|${ward}|${room}|${currentShift}`;
            
            let listHtml = '<div style="font-size:13px; font-weight:800; color:#1f6b4a; margin-bottom: 12px; text-transform:uppercase; letter-spacing:1px;">Select Nurse to Assign</div><div class="panel-nurse-list">';
            allNurses.forEach(nurse => {
                const isSelected = assignments[assignKey] === nurse.id;
                const initials = nurse.name.split(' ').map(n=>n[0]).join('').substring(0,2);
                listHtml += `
                    <div class="nurse-item" style="${isSelected ? 'border-color:#1f6b4a; background:#e6f4ea;' : ''}" onclick="assignNurse('${assignKey}', '${nurse.id}')">
                        <div class="nurse-avatar">${initials}</div>
                        <div style="flex:1;">
                            <div style="font-size:15px; font-weight:800; color:#1f2937;">${nurse.name}</div>
                            <div style="font-size:11px; font-weight:600; color:#6b7280;">${nurse.dept} • ${nurse.exp}</div>
                        </div>
                        ${isSelected ? '<i class="fas fa-check-circle text-[#1f6b4a] text-2xl"></i>' : ''}
                    </div>
                `;
            });
            listHtml += '</div>';

            if(assignments[assignKey]) {
                listHtml += `<button onclick="assignNurse('${assignKey}', null)" class="btn-submit" style="width:100%; margin-top:20px; background:#ef4444; color:#fff; border:none; padding:14px; font-size:14px; font-weight:700;"><i class="fas fa-trash-alt mr-2"></i> Remove Assignment</button>`;
            }

            details.innerHTML = listHtml;
            
            document.getElementById('side-panel').classList.add('active');
            document.getElementById('side-panel-overlay').classList.add('active');
        };

        window.assignNurse = function(assignKey, nurseId) {
            if(nurseId === null) {
                delete assignments[assignKey];
            } else {
                assignments[assignKey] = nurseId;
            }
            renderAllFloors();
            closePanel();
        };

        window.closePanel = function() {
            document.getElementById('side-panel').classList.remove('active');
            document.getElementById('side-panel-overlay').classList.remove('active');
        };
    </script>
</body>
</html>
