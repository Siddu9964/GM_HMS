<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Beds - GM HMS</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="stylesheet" href="/GM_HMS/assets/css/organic_map.css?v=<?php echo time(); ?>">
</head>
<body class="bg-[#f0ebe0] text-slate-900">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Navbar -->
            <?php include 'includes/navbar.php'; ?>
            
            <main class="flex-1 overflow-y-auto p-6">
                <!-- Page Header -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 animate-fade-in">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <span class="px-3 py-1 bg-white text-[#1E513B] text-[10px] font-black uppercase tracking-widest rounded-full shadow-sm">Facility Management</span>
                            <span class="text-xs font-bold text-slate-500"><i class="far fa-clock"></i> As of: <?php echo date('F j, Y'); ?></span>
                        </div>
                        <h1 class="text-3xl font-black tracking-tight text-[#1E513B] flex items-center gap-3">
                            <div class="p-3 rounded-2xl shadow-lg shadow-green-200" style="background: #1E513B;">
                                <i class="fas fa-procedures text-white"></i>
                            </div>
                            Hospital Beds Directory
                        </h1>
                        <p class="text-slate-500 mt-1 font-medium text-sm">Interactive 2D spatial floor plan map.</p>
                    </div>
                    <div class="flex flex-col items-end gap-3">
                        <div class="flex items-center gap-3">
                            <select class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-bold text-[#1E513B] shadow-sm outline-none">
                                <option>Floor 11</option>
                                <option>All Floors</option>
                            </select>
                            <button onclick="fetchBeds()" class="px-4 py-2 bg-white text-[#1E513B] font-bold rounded-xl hover:bg-slate-50 transition-all shadow-sm border border-slate-200 flex items-center gap-2 text-sm">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                            <button onclick="openRoomRegistration()" class="px-4 py-2 bg-[#1E513B] text-white font-bold rounded-xl hover:bg-[#133D2B] transition-all shadow-sm flex items-center gap-2 text-sm">
                                <i class="fas fa-plus"></i> Add Room
                            </button>
                        </div>
                        <div class="flex gap-3 text-[11px] font-bold bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-100 uppercase tracking-wide">
                            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-[#22C55E]"></span> Available</span>
                            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-[#EF4444]"></span> Occupied</span>
                            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-[#D97706]"></span> Maintenance</span>
                        </div>
                    </div>
                </div>

                <!-- Loading / Error -->
                <div id="loading" class="flex flex-col items-center justify-center py-24">
                    <div class="loader mb-4"></div>
                    <p class="text-[#1f6b4a] font-semibold">Loading floor plan...</p>
                </div>
                <div id="error-message" class="hidden bg-red-50 text-red-600 p-4 rounded-xl border border-red-200 mb-6 font-medium text-center"></div>

                <!-- Main Layout: All Floors + Side Panel -->
                <div id="main-layout" class="hidden flex-col lg:flex-row gap-6 animate-fade-in">
                    <!-- Left: All Floors Scrollable Area -->
                    <div class="flex-1 min-w-0">
                        <div id="all-floors-content" class="space-y-6">
                            <!-- All floors rendered here in order -->
                        </div>
                    </div>

                    <!-- Right: Side Panel -->
                    <div class="side-panel" id="side-panel" style="background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); border-radius: 24px;">
                        <div class="panel-header" style="background: #1E513B;">
                            <div class="text-[10px] font-black uppercase tracking-widest opacity-70 mb-1">Selected Bed</div>
                            <div id="panel-bed-title" class="text-xl font-black">—</div>
                            <div id="panel-bed-status-wrap" class="mt-2"></div>
                        </div>
                        <div id="panel-empty" class="panel-empty">
                            <i class="fas fa-hand-pointer text-4xl mb-3 opacity-40"></i>
                            <p class="text-sm font-semibold">Click any bed on the<br>floor plan to see details.</p>
                        </div>
                        <div id="panel-details" class="hidden">
                            <!-- Financials -->
                            <div class="panel-section">
                                <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Financials</div>
                                <div class="panel-row">
                                    <div class="panel-icon"><i class="fas fa-tag"></i></div>
                                    <div><div class="panel-label">Daily Price</div><div class="panel-value" id="panel-price">—</div></div>
                                </div>
                            </div>
                            
                            
                            <!-- Patient Context -->
                            <div id="panel-patient-section" class="hidden panel-section">
                                <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Patient Context</div>
                                <div style="background: #F9FAFB; border-radius: 16px; padding: 16px; border: 1px solid #E5E7EB; display: flex; flex-direction: column; gap: 12px;">
                                    <div><div style="font-size: 9px; font-weight: 800; color: #9CA3AF; text-transform: uppercase;">Name (PID)</div><div id="panel-patient-name" style="font-weight: 800; color: #1F2937; font-size: 13px;">—</div></div>
                                    <div style="display: flex; gap: 12px;">
                                        <div style="flex: 1;"><div style="font-size: 9px; font-weight: 800; color: #9CA3AF; text-transform: uppercase;">Age / Sex</div><div id="panel-age-sex" style="font-weight: 700; color: #374151; font-size: 12px;">—</div></div>
                                        <div style="flex: 1;"><div style="font-size: 9px; font-weight: 800; color: #9CA3AF; text-transform: uppercase;">Blood</div><div id="panel-blood" style="font-weight: 800; color: #EF4444; background: #FEF2F2; padding: 2px 6px; border-radius: 4px; display: inline-block; font-size: 11px;">—</div></div>
                                    </div>
                                    <div style="display: flex; gap: 12px;">
                                        <div style="flex: 1;"><div style="font-size: 9px; font-weight: 800; color: #9CA3AF; text-transform: uppercase;">Admitted On</div><div id="panel-adm-date" style="font-weight: 700; color: #374151; font-size: 12px;">—</div></div>
                                        <div style="flex: 1;"><div style="font-size: 9px; font-weight: 800; color: #9CA3AF; text-transform: uppercase;">Phone</div><div id="panel-phone" style="font-weight: 700; color: #374151; font-size: 12px;">—</div></div>
                                    </div>
                                    <div style="background: #fff; padding: 10px; border-radius: 8px; border: 1px solid #F3F4F6;">
                                        <div style="font-size: 9px; font-weight: 800; color: #9CA3AF; text-transform: uppercase; margin-bottom: 2px;">Chief Complaint</div>
                                        <div id="panel-complaint" style="font-weight: 600; color: #4B5563; font-size: 11px;">—</div>
                                    </div>
                                    <div style="background: #fff; padding: 10px; border-radius: 8px; border: 1px solid #F3F4F6;">
                                        <div style="font-size: 9px; font-weight: 800; color: #9CA3AF; text-transform: uppercase; margin-bottom: 2px;">Diagnosis</div>
                                        <div id="panel-diagnosis" style="font-weight: 600; color: #4B5563; font-size: 11px;">—</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Clinical Context -->
                            <div class="panel-section">
                                <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Clinical Context</div>
                                <div class="panel-row">
                                    <div class="panel-icon"><i class="fas fa-bed"></i></div>
                                    <div><div class="panel-label">Bed Type</div><div class="panel-value" id="panel-room-type">—</div></div>
                                </div>
                                <div class="panel-row">
                                    <div class="panel-icon"><i class="fas fa-map-marker-alt"></i></div>
                                    <div><div class="panel-label">Location</div><div class="panel-value" id="panel-location">—</div></div>
                                </div>
                                <div class="panel-row">
                                    <div class="panel-icon" style="background:#dcfce7;color:#166534;"><i class="fas fa-lungs"></i></div>
                                    <div><div class="panel-label">Oxygen Support</div><div class="panel-value" id="panel-oxygen">Available at bedside</div></div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="panel-section flex flex-col gap-3 pb-6">
                                
                                <button onclick="alert('Connecting to Nurse Station...')" class="w-full py-2.5 text-sm font-bold text-center rounded-xl bg-[#1E513B] text-white hover:bg-[#133D2B] transition-all flex items-center justify-center gap-2 shadow-lg shadow-green-900/20">
                                    <i class="fas fa-phone-alt"></i> Contact Nurse Station
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        let allBeds = [];
        let hierarchy = {};

        document.addEventListener('DOMContentLoaded', () => {
            fetchBeds();
        });

        function fetchBeds() {
            const loading = document.getElementById('loading');
            const layout = document.getElementById('main-layout');
            const errorMsg = document.getElementById('error-message');

            loading.style.display = 'flex';
            loading.classList.remove('hidden');
            layout.style.display = 'none';
            errorMsg.classList.add('hidden');

            fetch('/GM_HMS/api/hospital-beds/')
                .then(r => r.json())
                .then(data => {
                    loading.style.display = 'none';
                    if (data.success && data.data) {
                        allBeds = data.data;
                        buildHierarchy();
                        renderAllFloors();
                        layout.style.display = 'flex';
                    } else {
                        throw new Error(data.error || 'Failed to load beds');
                    }
                })
                .catch(err => {
                    loading.style.display = 'none';
                    errorMsg.textContent = 'Error: ' + err.message;
                    errorMsg.classList.remove('hidden');
                });
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

        function renderAllFloors() {
            const container = document.getElementById('all-floors-content');
            container.innerHTML = '';

            // Sort floors by floor number
            const sortedFloors = Object.entries(hierarchy).sort((a, b) => a[1].num - b[1].num);

            const roomColors = ['room-color-0', 'room-color-1', 'room-color-2', 'room-color-3', 'room-color-4'];
            let globalRoomColorIdx = 0;

            sortedFloors.forEach(([floorName, floorData]) => {
                // Floor section wrapper
                const floorSection = document.createElement('div');
                floorSection.className = 'floor-section-wrap';

                // Floor header pill
                const floorHeader = document.createElement('div');
                floorHeader.className = 'floor-heading-bar';
                floorHeader.innerHTML = `<i class="fas fa-building"></i> ${floorName}`;
                floorSection.appendChild(floorHeader);

                // Floor canvas
                const canvas = document.createElement('div');
                canvas.className = 'floor-canvas';

                // Sort wards alphabetically
                const sortedWards = Object.entries(floorData.wards).sort((a, b) => a[0].localeCompare(b[0]));

                sortedWards.forEach(([wardName, rooms]) => {
                    // Ward label
                    const wardDiv = document.createElement('div');
                    wardDiv.style.marginBottom = '24px';

                    const wardLabel = document.createElement('div');
                    wardLabel.className = 'ward-title';
                    wardLabel.innerHTML = `<i class="fas fa-layer-group"></i> ${wardName}`;
                    wardDiv.appendChild(wardLabel);

                    const roomsWrapper = document.createElement('div');
                    roomsWrapper.style.display = 'flex';
                    roomsWrapper.style.flexWrap = 'wrap';
                    roomsWrapper.style.gap = '20px';

                    // Sort rooms alphabetically
                    const sortedRooms = Object.entries(rooms).sort((a, b) => a[0].localeCompare(b[0]));

                    sortedRooms.forEach(([roomName, beds]) => {
                        const roomWrap = document.createElement('div');
                        roomWrap.className = 'room-blob-wrap';
                        
                        // Sort beds by bed_number to calculate proper blob width
                        const sortedBeds = [...beds].sort((a, b) =>
                            String(a.bed_number || '').localeCompare(String(b.bed_number || ''), undefined, {numeric: true})
                        );

                        // Calculate SVG size based on bed count (roughly)
                        const bedsCount = sortedBeds.length;
                        let rWidth = 200 + (bedsCount > 2 ? (bedsCount - 2) * 70 : 0);
                        let rHeight = 220 + (bedsCount > 4 ? 60 : 0);

                        const contentDiv = document.createElement('div');
                        contentDiv.className = 'room-content';
                        
                        const label = document.createElement('div');
                        label.className = 'room-label';
                        label.textContent = roomName + ` (${beds.length} Bed${beds.length !== 1 ? 's' : ''})`;
                        contentDiv.appendChild(label);

                        const bedsRow = document.createElement('div');
                        bedsRow.className = 'room-blob-beds';
                        bedsRow.style.marginTop = '20px';

                        sortedBeds.forEach(bed => {
                            const status = bed.bed_status || 'Available';
                            const statusClass = status === 'Occupied'    ? 'bed-occupied' :
                                                status === 'Maintenance' || status === 'Cleaning' ? 'bed-maintenance' :
                                                status === 'Reserved'   || status === 'Blocked'   ? 'bed-reserved' :
                                                'bed-available';
                            const icon = status === 'Occupied'    ? 'fa-user-injured' :
                                         status === 'Maintenance' || status === 'Cleaning' ? 'fa-tools' :
                                         status === 'Reserved'   || status === 'Blocked'   ? 'fa-lock' :
                                         'fa-bed';

                            const wrap = document.createElement('div');
                            wrap.className = `bed-icon-wrap ${statusClass}`;
                            wrap.id = `bed-wrap-${bed.sl_no}`;
                            wrap.onclick = () => selectBed(bed, wrap);

                            const patientBadge = (status === 'Occupied' && bed.patient_id)
                                ? `<div class="bed-patient-badge"><i class="fas fa-user"></i></div>` : '';

                            wrap.innerHTML = `
                                <div class="bed-circle">
                                    <i class="fas ${icon}"></i>
                                    ${patientBadge}
                                </div>
                                <div class="bed-number-label">${bed.bed_number}</div>
                                <div class="bed-status-label">${status}</div>
                            `;
                            bedsRow.appendChild(wrap);
                        });

                        contentDiv.appendChild(bedsRow);
                        roomWrap.appendChild(contentDiv);
                        roomsWrapper.appendChild(roomWrap);
                    });

                    wardDiv.appendChild(roomsWrapper);
                    canvas.appendChild(wardDiv);
                });

                floorSection.appendChild(canvas);
                container.appendChild(floorSection);
            });

            resetPanel();
        }

        async function selectBed(bed, wrapEl) {
            // Deactivate previous
            document.querySelectorAll('.bed-icon-wrap.active').forEach(el => el.classList.remove('active'));
            wrapEl.classList.add('active');

            const status = bed.bed_status || 'Available';
            const statusColors = {
                'Occupied':    'background:#fef2f2;color:#EF4444;', // red-50 to crimson
                'Available':   'background:#f0fdf4;color:#22C55E;', // green-50 to emerald
                'Maintenance': 'background:#fffbeb;color:#D97706;', // amber-50 to amber
                'Cleaning':    'background:#fffbeb;color:#D97706;',
                'Reserved':    'background:#eff6ff;color:#3B82F6;',
                'Blocked':     'background:#eff6ff;color:#3B82F6;'
            };
            const statusStyle = statusColors[status] || statusColors['Available'];

            document.getElementById('panel-bed-title').textContent = 'Bed ' + (bed.bed_number || '—');
            document.getElementById('panel-bed-status-wrap').innerHTML =
                `<span style="${statusStyle} font-weight:800; font-size:10px; padding:3px 10px; border-radius:99px; text-transform:uppercase; letter-spacing:0.07em;">${status}</span>`;

            document.getElementById('panel-price').textContent = bed.total_bed_amount ? '₹' + bed.total_bed_amount + '/day' : 'N/A';
            document.getElementById('panel-room-type').textContent = bed.room_type || '—';
            document.getElementById('panel-location').textContent = (bed.ward_name || '—') + ', Room ' + (bed.room_name || bed.room_number || '—');

            const patientSection = document.getElementById('panel-patient-section');

            if (status === 'Occupied' && bed.patient_id) {
                patientSection.classList.remove('hidden');
                
                // Show loading state temporarily
                document.getElementById('panel-patient-name').textContent = 'Loading...';
                document.getElementById('panel-age-sex').textContent = '—';
                document.getElementById('panel-blood').textContent = '—';
                document.getElementById('panel-phone').textContent = '—';
                document.getElementById('panel-adm-date').textContent = '—';
                document.getElementById('panel-complaint').textContent = '—';
                document.getElementById('panel-diagnosis').textContent = '—';

                try {
                    const res = await fetch(`/GM_HMS/api/get_patient_details_full.php?patient_id=${bed.patient_id}`);
                    const data = await res.json();
                    
                    if (data.success && data.data) {
                        const p = data.data;
                        document.getElementById('panel-patient-name').textContent = `${p.full_name} (${p.patient_id})`;
                        document.getElementById('panel-age-sex').textContent = `${p.age_years || '?'}Y / ${p.gender || '?'}`;
                        document.getElementById('panel-blood').textContent = p.blood_group || 'N/A';
                        document.getElementById('panel-phone').textContent = p.phone_number || 'N/A';
                        document.getElementById('panel-adm-date').textContent = p.admission_date ? new Date(p.admission_date).toLocaleDateString() : '—';
                        document.getElementById('panel-complaint').textContent = p.chief_complaint || 'Not specified';
                        document.getElementById('panel-diagnosis').textContent = p.diagnosis || 'Pending Diagnosis';
                    } else {
                        document.getElementById('panel-patient-name').textContent = 'Error loading details';
                    }
                } catch(e) {
                    console.error('Fetch error:', e);
                    document.getElementById('panel-patient-name').textContent = 'Network Error';
                }
            } else {
                patientSection.classList.add('hidden');
            }

            document.getElementById('panel-empty').classList.add('hidden');
            document.getElementById('panel-details').classList.remove('hidden');

            // Scroll to the panel on small screens
            document.getElementById('side-panel').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function resetPanel() {
            document.getElementById('panel-bed-title').textContent = '—';
            document.getElementById('panel-bed-status-wrap').innerHTML = '';
            document.getElementById('panel-empty').classList.remove('hidden');
            document.getElementById('panel-details').classList.add('hidden');
            document.getElementById('panel-patient-section').classList.add('hidden');
        }
    </script>

    <!-- Room Registration Modal -->
    <div id="roomRegModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="font-black text-lg flex items-center gap-2"><i class="fas fa-door-open"></i> Add Room</h3>
                <i class="fas fa-times btn-close text-xl" onclick="closeRoomRegistration()"></i>
            </div>
            <div class="modal-body">
                <form id="roomRegForm" onsubmit="event.preventDefault(); submitRoomForm();">
                    
                    <div class="input-label mb-1 border-b pb-1 text-[#1f6b4a]"><i class="fas fa-map-marker-alt"></i> Location</div>
                    <div class="form-grid grid-cols-3 mb-3">
                        <div class="input-group">
                            <label class="input-label">Floor Number</label>
                            <select id="regFloorNumSelect" name="floor_number" class="input-field" onchange="toggleCustomInput(this, 'regFloorNumCustom')" required>
                                <option value="" disabled selected>Select Num...</option>
                            </select>
                            <input type="number" id="regFloorNumCustom" name="floor_number_custom" class="input-field mt-1 hidden" placeholder="Enter Num">
                        </div>
                        <div class="input-group">
                            <label class="input-label">Floor Name</label>
                            <select id="regFloorSelect" name="floor_name" class="input-field" onchange="toggleCustomInput(this, 'regFloorCustom')" required>
                                <option value="" disabled selected>Select Floor...</option>
                            </select>
                            <input type="text" id="regFloorCustom" name="floor_name_custom" class="input-field mt-1 hidden" placeholder="Enter new Floor">
                        </div>
                        <div class="input-group">
                            <label class="input-label">Ward Name</label>
                            <select id="regWardSelect" name="ward_name" class="input-field" onchange="toggleCustomInput(this, 'regWardCustom')" required>
                                <option value="" disabled selected>Select Ward...</option>
                            </select>
                            <input type="text" id="regWardCustom" name="ward_name_custom" class="input-field mt-1 hidden" placeholder="Enter new Ward">
                        </div>
                    </div>

                    <div class="input-label mb-1 border-b pb-1 text-[#1f6b4a]"><i class="fas fa-bed"></i> Room & Bed Specs</div>
                    <div class="form-grid grid-cols-4 mb-3">
                        <div class="input-group">
                            <label class="input-label">Room Type</label>
                            <select id="regRoomTypeSelect" name="room_type" class="input-field" onchange="toggleCustomInput(this, 'regRoomTypeCustom')" required>
                                <option value="" disabled selected>Select Category...</option>
                            </select>
                            <input type="text" id="regRoomTypeCustom" name="room_type_custom" class="input-field mt-1 hidden" placeholder="Enter Category">
                        </div>
                        <div class="input-group">
                            <label class="input-label">Room Number</label>
                            <input type="text" name="room_number" class="input-field" placeholder="e.g. 101" required>
                        </div>
                        <div class="input-group">
                            <label class="input-label">Room Name</label>
                            <select id="regRoomNameSelect" name="room_name" class="input-field" onchange="toggleCustomInput(this, 'regRoomNameCustom')">
                                <option value="" disabled selected>Select Name...</option>
                            </select>
                            <input type="text" id="regRoomNameCustom" name="room_name_custom" class="input-field mt-1 hidden" placeholder="Enter Room Name">
                        </div>
                        <div class="input-group">
                            <label class="input-label">Bed Number</label>
                            <select id="regBedNumSelect" name="bed_number" class="input-field" onchange="toggleCustomInput(this, 'regBedNumCustom')" required>
                                <option value="" disabled selected>Select Bed...</option>
                            </select>
                            <input type="text" id="regBedNumCustom" name="bed_number_custom" class="input-field mt-1 hidden" placeholder="Enter Bed Number">
                        </div>
                    </div>

                    <div class="input-label mb-1 border-b pb-1 text-[#1f6b4a]"><i class="fas fa-rupee-sign"></i> Charges & Status</div>
                    <div class="form-grid grid-cols-6 mb-4">
                        <div class="input-group">
                            <label class="input-label">Amount/Day</label>
                            <input type="number" name="amount_per_day" id="amount_per_day" class="input-field calc-charge" value="0">
                        </div>
                        <div class="input-group">
                            <label class="input-label">Nursing</label>
                            <input type="number" name="nursig_charge" id="nursig_charge" class="input-field calc-charge" value="0">
                        </div>
                        <div class="input-group">
                            <label class="input-label">Doctor</label>
                            <input type="number" name="doctor_charge" id="doctor_charge" class="input-field calc-charge" value="0">
                        </div>
                        <div class="input-group">
                            <label class="input-label">Service</label>
                            <input type="number" name="service_charge" id="service_charge" class="input-field calc-charge" value="0">
                        </div>
                        <div class="input-group">
                            <label class="input-label text-[#1f6b4a]">Total Amount</label>
                            <input type="number" name="total_bed_amount" id="total_bed_amount" class="input-field" style="background: #e6f4ea; border-color: #1f6b4a;" readonly value="0">
                        </div>
                        <div class="input-group">
                            <label class="input-label">Bed Status</label>
                            <select name="bed_status" class="input-field" required>
                                <option value="Available" selected>Available</option>
                                <option value="Occupied">Occupied</option>
                                <option value="Reserved">Reserved</option>
                                <option value="Blocked">Blocked</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Cleaning">Cleaning</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-3">
                        <button type="button" class="btn-submit" style="background:#fff; color:#4b5563; border:2px solid #e5e7eb;" onclick="closeRoomRegistration()">Cancel</button>
                        <button type="submit" class="btn-submit"><i class="fas fa-check"></i> Add Room / Bed</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Success Popup Modal -->
    <div id="successPopupModal" class="modal-overlay" style="z-index: 100;">
        <div class="modal-card" style="max-width: 400px; text-align: center; padding: 40px 24px;">
            <div style="width: 80px; height: 80px; background: #e6f4ea; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; color: #1f6b4a; font-size: 36px; animation: popIn 0.5s cubic-bezier(0.34,1.56,0.64,1);">
                <i class="fas fa-check"></i>
            </div>
            <h2 style="color: #1f6b4a; font-size: 24px; font-weight: 900; margin-bottom: 10px;">Success!</h2>
            <p style="color: #6b7280; font-size: 14px; font-weight: 600; margin-bottom: 30px; line-height: 1.5;">Room configuration has been mapped.<br><span style="color:#9ca3af; font-size: 12px;">(Saved dynamically! Backend integration pending.)</span></p>
            <button onclick="document.getElementById('successPopupModal').classList.remove('active')" class="btn-submit" style="width: 100%; font-size: 14px; padding: 14px;">Awesome</button>
        </div>
    </div>

    <style>
        @keyframes popIn {
            0% { transform: scale(0) rotate(-45deg); opacity: 0; }
            80% { transform: scale(1.1) rotate(10deg); opacity: 1; }
            100% { transform: scale(1) rotate(0); opacity: 1; }
        }
    </style>

    <script>
        function toggleCustomInput(selectEl, customInputId) {
            const customInput = document.getElementById(customInputId);
            if (selectEl.value === 'ADD_NEW_CUSTOM') {
                customInput.classList.remove('hidden');
                customInput.required = true;
                selectEl.removeAttribute('name'); // ensure backend doesn't take 'ADD_NEW_CUSTOM'
                customInput.setAttribute('name', customInputId.replace('Custom', '').replace('reg', '').toLowerCase() + '_name'); 
                customInput.focus();
            } else {
                customInput.classList.add('hidden');
                customInput.required = false;
                customInput.removeAttribute('name');
                selectEl.setAttribute('name', customInputId.replace('Custom', '').replace('reg', '').toLowerCase() + '_name');
            }
        }

        const populateSelect = (id, items, defaultText) => {
            const sel = document.getElementById(id);
            if (sel) {
                // Keep the current selected value if it exists in the new items, else reset
                const currentVal = sel.value;
                sel.innerHTML = `<option value="" disabled ${!items.includes(currentVal) ? 'selected' : ''}>${defaultText}</option>` +
                                items.map(i => `<option value="${i}" ${currentVal === i ? 'selected' : ''}>${i}</option>`).join('') +
                                `<option value="ADD_NEW_CUSTOM" style="font-weight:bold; color:#1f6b4a;">+ Add New...</option>`;
            }
        };

        let modalEventsBound = false;

        function openRoomRegistration() {
            const beds = typeof allBeds !== 'undefined' ? allBeds : [];
            const natSort = (a,b) => String(a).localeCompare(String(b), undefined, {numeric:true, sensitivity:'base'});
            const isValid = v => v !== null && v !== undefined && v !== '';
            
            // Allow 0 by using isValid instead of Boolean
            const floorNums = [...new Set(beds.map(b => b.floor_number))].filter(isValid).sort((a,b) => Number(a) - Number(b));
            
            // Sort floor_name by floor_number
            const uniqueFloors = [];
            const seenFloorNames = new Set();
            for (const b of beds) {
                if (isValid(b.floor_name) && !seenFloorNames.has(b.floor_name)) {
                    seenFloorNames.add(b.floor_name);
                    uniqueFloors.push({ name: b.floor_name, num: Number(b.floor_number) || 0 });
                }
            }
            uniqueFloors.sort((a, b) => a.num - b.num);
            const floors = uniqueFloors.map(f => f.name);

            const wards = [...new Set(beds.map(b => b.ward_name))].filter(isValid).sort(natSort);
            const roomTypes = [...new Set(beds.map(b => b.room_type))].filter(isValid).sort(natSort);
            const roomNames = [...new Set(beds.map(b => b.room_name))].filter(isValid).sort(natSort);
            const bedNumbers = [...new Set(beds.map(b => b.bed_number))].filter(isValid).sort(natSort);

            populateSelect('regFloorNumSelect', floorNums, 'Select Num...');
            populateSelect('regFloorSelect', floors, 'Select Floor...');
            populateSelect('regWardSelect', wards, 'Select Ward...');
            populateSelect('regRoomTypeSelect', roomTypes, 'Select Category...');
            populateSelect('regRoomNameSelect', roomNames, 'Select Name...');
            populateSelect('regBedNumSelect', bedNumbers, 'Select Bed...');

            if (!modalEventsBound) {
                // 1. Cascading Wards based on Floor + Auto-fill Floor Number
                document.getElementById('regFloorSelect').addEventListener('change', function() {
                    if (this.value && this.value !== 'ADD_NEW_CUSTOM') {
                        const matchingBed = beds.find(b => b.floor_name === this.value);
                        if (matchingBed) {
                            const numSelect = document.getElementById('regFloorNumSelect');
                            numSelect.value = matchingBed.floor_number;
                            if(!numSelect.value) numSelect.value = ""; // fallback
                        }
                        
                        const floorWards = beds.filter(b => b.floor_name === this.value);
                        if(floorWards.length > 0) populateSelect('regWardSelect', [...new Set(floorWards.map(b => b.ward_name))].filter(Boolean).sort(natSort), 'Select Ward...');
                    }
                });

                // 2. Cascading Room Types based on Ward
                document.getElementById('regWardSelect').addEventListener('change', function() {
                    if (this.value && this.value !== 'ADD_NEW_CUSTOM') {
                        const floorVal = document.getElementById('regFloorSelect').value;
                        const wardBeds = beds.filter(b => b.ward_name === this.value && (floorVal === 'ADD_NEW_CUSTOM' || !floorVal || b.floor_name === floorVal));
                        if(wardBeds.length > 0) populateSelect('regRoomTypeSelect', [...new Set(wardBeds.map(b => b.room_type))].filter(Boolean).sort(natSort), 'Select Category...');
                    }
                });

                // 3. Smart Auto-fill Charges based on Room Type
                document.getElementById('regRoomTypeSelect').addEventListener('change', function() {
                    if (this.value && this.value !== 'ADD_NEW_CUSTOM') {
                        const floorVal = document.getElementById('regFloorSelect').value;
                        const wardVal = document.getElementById('regWardSelect').value;
                        
                        // Find a bed that matches the selected criteria to copy its price template
                        const matchingBed = beds.find(b => b.room_type === this.value && 
                            (!wardVal || wardVal === 'ADD_NEW_CUSTOM' || b.ward_name === wardVal) && 
                            (!floorVal || floorVal === 'ADD_NEW_CUSTOM' || b.floor_name === floorVal)
                        );
                        
                        if (matchingBed) {
                            document.getElementById('amount_per_day').value = matchingBed.amount_per_day || 0;
                            document.getElementById('nursig_charge').value = matchingBed.nursig_charge || 0; // Note: using exact DB schema spelling
                            document.getElementById('doctor_charge').value = matchingBed.doctor_charge || 0;
                            document.getElementById('service_charge').value = matchingBed.service_charge || 0;
                            // Trigger the input event to auto-calculate the total
                            document.getElementById('amount_per_day').dispatchEvent(new Event('input')); 
                        }
                    }
                });
                modalEventsBound = true;
            }
            
            document.getElementById('roomRegModal').classList.add('active');
        }

        function closeRoomRegistration() {
            document.getElementById('roomRegModal').classList.remove('active');
            document.getElementById('roomRegForm').reset();
            document.getElementById('total_bed_amount').value = '0';
            
            // Hide all custom inputs on reset
            document.querySelectorAll('input[id$="Custom"]').forEach(el => {
                el.classList.add('hidden');
                el.required = false;
            });
        }

        // Auto-calculate total amount
        document.querySelectorAll('.calc-charge').forEach(input => {
            input.addEventListener('input', () => {
                const amount = parseFloat(document.getElementById('amount_per_day').value) || 0;
                const nursing = parseFloat(document.getElementById('nursig_charge').value) || 0;
                const doctor = parseFloat(document.getElementById('doctor_charge').value) || 0;
                const service = parseFloat(document.getElementById('service_charge').value) || 0;
                document.getElementById('total_bed_amount').value = (amount + nursing + doctor + service).toFixed(2);
            });
        });

        async function submitRoomForm() {
            const form = document.getElementById('roomRegForm');
            const formData = new FormData(form);
            
            try {
                const response = await fetch('/GM_HMS/api/save_bed.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    closeRoomRegistration();
                    showSuccessPopup();
                    
                    // Optionally, reload the beds from DB after 2 seconds
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    alert('Error saving room: ' + result.message);
                }
            } catch (error) {
                console.error('Error submitting form:', error);
                alert('Network error saving room.');
            }
        }

        function showSuccessPopup() {
            document.getElementById('successPopupModal').classList.add('active');
        }
    </script>
    <script src="/GM_HMS/assets/js/gm-sidebar.js"></script>
    </body>
</html>
