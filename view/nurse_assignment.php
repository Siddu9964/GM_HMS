<?php
session_start();

// Check authentication
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Assignment Management - GM HMS</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .sidebar-item.active {
            background: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen flex">

    <!-- Sidebar Inclusion -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 sticky top-0 z-30">
            <div class="px-6 py-4 flex items-center justify-between">
                <h2 class="text-2xl font-bold text-gray-800">Nurse Assignment</h2>
                <button onclick="openAssignmentModal()"
                    class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                    <i class="fas fa-plus"></i> New Assignment
                </button>
            </div>
        </header>

        <div class="p-6 overflow-y-auto">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-xl">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Active Assignments</p>
                        <h3 id="statActive" class="text-2xl font-bold text-gray-800">0</h3>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-teal-100 text-teal-600 rounded-lg flex items-center justify-center text-xl">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Scheduled Today</p>
                        <h3 id="statToday" class="text-2xl font-bold text-gray-800">0</h3>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center text-xl">
                        <i class="fas fa-hospital-alt"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Covered Wards</p>
                        <h3 id="statWards" class="text-2xl font-bold text-gray-800">0</h3>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                    <h3 class="font-semibold text-gray-700">Recent Assignments</h3>
                    <div class="flex gap-2">
                        <input type="date" id="filterDate"
                            class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                        <select id="filterWard"
                            class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                            <option value="">All Wards</option>
                        </select>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-gray-600 text-xs uppercase font-semibold">
                            <tr>
                                <th class="px-6 py-4">Nurse Role</th>
                                <th class="px-6 py-4">Shift Type</th>
                                <th class="px-6 py-4">Ward</th>
                                <th class="px-6 py-4">Area</th>
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="assignmentTableBody" class="divide-y divide-gray-100">
                            <!-- Data populated via JS -->
                        </tbody>
                    </table>
                </div>
                <div id="tableLoading" class="p-8 text-center text-gray-500">
                    <i class="fas fa-spinner fa-spin mr-2"></i> Loading assignments...
                </div>
            </div>
        </div>
    </main>

    <!-- Assignment Modal -->
    <div id="assignmentModal" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4">
        <div
            class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-200">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-teal-600 text-white">
                <h3 id="modalTitle" class="font-bold text-lg">New Nurse Assignment</h3>
                <button onclick="closeAssignmentModal()" class="hover:bg-white/20 p-1 rounded-full transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="assignmentForm" class="p-6 space-y-4">
                <input type="hidden" name="shift_id" id="shift_id">

                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Nurse</label>
                        <div class="relative group" id="nurseSelectWrapper">
                            <input type="hidden" name="role_id" id="role_id" required>
                            <div
                                class="flex items-center border border-gray-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-teal-500 bg-white transition-all shadow-sm">
                                <div class="pl-3 text-gray-400">
                                    <i class="fas fa-search text-sm"></i>
                                </div>
                                <input type="text" id="nurseSearch" placeholder="Search nurse by name..."
                                    class="w-full px-3 py-2.5 outline-none text-gray-800 placeholder-gray-400 font-medium"
                                    autocomplete="off">
                                <div class="pr-3 text-gray-400 cursor-pointer hover:text-teal-600 transition-colors"
                                    onclick="toggleNurseList()">
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </div>
                            </div>

                            <!-- Premium Results List -->
                            <div id="nurseResultsList"
                                class="absolute left-0 right-0 mt-1 bg-white border border-gray-100 rounded-xl shadow-2xl z-[60] hidden max-h-64 overflow-y-auto animate-in fade-in slide-in-from-top-2 duration-200">
                                <div class="p-2 space-y-1" id="nurseOptionsContainer">
                                    <!-- Options populated via JS -->
                                    <div class="px-3 py-4 text-center text-gray-400 text-sm italic">
                                        Type to search...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Shift Date From</label>
                        <input type="date" name="shift_date_from" id="shift_date_from" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Shift Date To</label>
                        <input type="date" name="shift_date_to" id="shift_date_to" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none">
                        <p class="text-xs text-gray-400 mt-1">For single day, use same date</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Shift Type</label>
                        <select name="shift_type" id="shift_type" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none">
                            <option value="Morning">Morning</option>
                            <option value="Evening">Evening</option>
                            <option value="Night">Night</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Floor <span
                                class="text-gray-400 text-xs">(Optional)</span></label>
                        <select name="floor_name" id="floor_name"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none">
                            <option value="">All Floors</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ward Type <span
                                class="text-gray-400 text-xs">(Optional)</span></label>
                        <select name="ward_type" id="ward_type"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none">
                            <option value="">All Ward Types</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ward</label>
                        <select name="ward_name" id="ward_name" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none">
                            <option value="">Select Ward</option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1" id="wardTypeDisplay"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Work Area</label>
                        <select name="work_area" id="work_area"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none">
                            <option value="">Select Work Area</option>
                            <option value="ICU">ICU</option>
                            <option value="General Ward">General Ward</option>
                            <option value="Emergency">Emergency</option>
                            <option value="OPD">OPD</option>
                            <option value="Operation Theater">Operation Theater</option>
                            <option value="Pediatric">Pediatric</option>
                            <option value="Maternity">Maternity</option>
                            <option value="Cardiology">Cardiology</option>
                            <option value="Neurology">Neurology</option>
                            <option value="Orthopedic">Orthopedic</option>
                            <option value="Oncology">Oncology</option>
                            <option value="Dialysis">Dialysis</option>
                            <option value="Recovery Room">Recovery Room</option>
                            <option value="Isolation Ward">Isolation Ward</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Room No <span class="text-gray-400 text-xs">(Optional)</span>
                        </label>
                        <select name="assigned_beds" id="assigned_beds"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none">
                            <option value="">Select ward first to load rooms…</option>
                        </select>
                        <input type="hidden" name="room_name" id="room_name" value="">
                        <p class="text-xs text-gray-400 mt-1">Rooms are loaded from hospital beds when a ward is
                            selected</p>
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-gray-100">
                    <button type="button" onclick="closeAssignmentModal()"
                        class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">Cancel</button>
                    <button type="submit"
                        class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors shadow-lg shadow-teal-600/20">Save
                        Assignment</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const API_BASE = '../api/nurse-shifts';
        let assignments = [];
        let allNurses = [];

        // Load initial data
        document.addEventListener('DOMContentLoaded', () => {
            fetchAssignments();
            fetchNurses();
            fetchFloors();
            fetchWardTypes();
            fetchWards(); // Load all wards on page load

            // Set default dates
            const today = new Date();
            document.getElementById('shift_date_from').valueAsDate = today;
            document.getElementById('shift_date_to').valueAsDate = today;
        });

        async function fetchAssignments() {
            try {
                const ward = document.getElementById('filterWard').value;
                const date = document.getElementById('filterDate').value;
                let url = API_BASE;
                const query = [];
                if (ward) query.push(`ward=${ward}`);
                if (date) query.push(`date_from=${date}&date_to=${date}`);
                if (query.length) url += `?${query.join('&')}`;

                const res = await fetch(url);
                const result = await res.json();

                if (result.success) {
                    assignments = result.data;
                    renderTable();
                    updateStats();
                }
            } catch (err) {
                console.error('Error fetching assignments:', err);
            } finally {
                document.getElementById('tableLoading').classList.add('hidden');
            }
        }

        async function fetchNurses() {
            try {
                const res = await fetch(`${API_BASE}/nurses`);
                const result = await res.json();
                if (result.success) {
                    allNurses = result.data; // Store globally for filtering
                    renderNurseOptions(allNurses);
                }
            } catch (err) { console.error(err); }
        }

        function renderNurseOptions(nurses) {
            const container = document.getElementById('nurseOptionsContainer');
            if (nurses.length === 0) {
                container.innerHTML = '<div class="px-3 py-4 text-center text-gray-400 text-sm italic">No nurses found</div>';
                return;
            }

            container.innerHTML = '';
            nurses.forEach(nurse => {
                const item = document.createElement('div');
                item.className = 'px-3 py-2.5 hover:bg-teal-50 rounded-lg cursor-pointer transition-colors group flex items-center justify-between';
                item.innerHTML = `
                    <div class="flex flex-col">
                        <span class="text-gray-800 font-semibold text-sm group-hover:text-teal-700">${nurse.full_name}</span>
                        <span class="text-gray-400 text-xs">${nurse.designation}</span>
                    </div>
                    <i class="fas fa-plus text-teal-200 group-hover:text-teal-500 scale-0 group-hover:scale-100 transition-all text-xs"></i>
                `;
                item.onclick = () => selectNurse(nurse);
                container.appendChild(item);
            });
        }

        function selectNurse(nurse) {
            document.getElementById('role_id').value = nurse.role_id;
            document.getElementById('nurseSearch').value = nurse.full_name;
            closeNurseList();
        }

        function toggleNurseList() {
            const list = document.getElementById('nurseResultsList');
            if (list.classList.contains('hidden')) {
                openNurseList();
            } else {
                closeNurseList();
            }
        }

        function openNurseList() {
            document.getElementById('nurseResultsList').classList.remove('hidden');
        }

        function closeNurseList() {
            document.getElementById('nurseResultsList').classList.add('hidden');
        }

        // Search logic
        document.getElementById('nurseSearch').addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            openNurseList();
            const filtered = allNurses.filter(n => n.full_name.toLowerCase().includes(term));
            renderNurseOptions(filtered);

            // Clear role_id if they are typing (unless exact match, but safer to force re-select)
            if (!term) document.getElementById('role_id').value = '';
        });

        document.getElementById('nurseSearch').addEventListener('focus', openNurseList);

        // Click outside to close
        document.addEventListener('click', (e) => {
            const wrapper = document.getElementById('nurseSelectWrapper');
            if (!wrapper.contains(e.target)) {
                closeNurseList();
            }
        });

        async function fetchFloors() {
            try {
                const res = await fetch(`${API_BASE}/floors`);
                const result = await res.json();
                if (result.success) {
                    const select = document.getElementById('floor_name');
                    select.innerHTML = '<option value="">All Floors</option>';
                    result.data.forEach(item => {
                        const opt = document.createElement('option');
                        opt.value = item.floor_number;
                        opt.dataset.name = item.floor_name;
                        opt.textContent = `${item.floor_number} - ${item.floor_name}`;
                        select.appendChild(opt);
                    });
                }
            } catch (err) { console.error(err); }
        }

        async function fetchWardTypes() {
            try {
                const res = await fetch(`${API_BASE}/ward-types`);
                const result = await res.json();
                if (result.success) {
                    const select = document.getElementById('ward_type');
                    select.innerHTML = '<option value="">All Ward Types</option>';
                    result.data.forEach(item => {
                        const opt = document.createElement('option');
                        opt.value = item.ward_type;
                        opt.textContent = item.ward_type;
                        select.appendChild(opt);
                    });
                }
            } catch (err) { console.error(err); }
        }

        async function fetchWards(floorNumber = null, wardType = null) {
            try {
                let url = `${API_BASE}/wards`;
                const params = [];
                if (floorNumber) params.push(`floor_number=${encodeURIComponent(floorNumber)}`);
                if (wardType) params.push(`ward_type=${encodeURIComponent(wardType)}`);
                if (params.length) url += `?${params.join('&')}`;

                const res = await fetch(url);
                const result = await res.json();
                if (result.success) {
                    const select = document.getElementById('ward_name');
                    const filterSelect = document.getElementById('filterWard');

                    select.innerHTML = '<option value="">Select Ward</option>';
                    if (!floorNumber && !wardType) {
                        filterSelect.innerHTML = '<option value="">All Wards</option>';
                    }

                    result.data.forEach(ward => {
                        const opt = document.createElement('option');
                        opt.value = ward.ward_name;
                        opt.textContent = ward.ward_name;
                        opt.dataset.wardType = ward.ward_type || '';
                        select.appendChild(opt.cloneNode(true));
                        if (!floorNumber && !wardType) {
                            filterSelect.appendChild(opt.cloneNode(true));
                        }
                    });
                }
            } catch (err) { console.error(err); }
        }

        async function fetchRooms(ward = null) {
            const select = document.getElementById('assigned_beds');
            select.innerHTML = '<option value="" disabled>Loading rooms…</option>';
            try {
                let url = `${API_BASE}/rooms`;
                const params = [];
                if (ward) params.push(`ward=${encodeURIComponent(ward)}`);
                
                const floorNumber = document.getElementById('floor_name').value;
                if (floorNumber) params.push(`floor_number=${encodeURIComponent(floorNumber)}`);
                
                if (params.length) url += `?${params.join('&')}`;
                const res = await fetch(url);
                const result = await res.json();
                if (result.success && result.data.length > 0) {
                    select.innerHTML = '';
                    result.data.forEach(room => {
                        const opt = document.createElement('option');
                        opt.value = room.room_number;
                        opt.dataset.roomName = room.room_name || '';
                        // Build a descriptive label: Room 101 – Room Name | Floor: Ground | Type: General
                        let label = room.room_number;
                        if (room.room_name) label += ` – ${room.room_name}`;
                        const meta = [];
                        if (room.floor_name) meta.push(`Floor: ${room.floor_name}`);
                        if (room.ward_type) meta.push(`Type: ${room.ward_type}`);
                        if (meta.length) label += ` (${meta.join(' | ')})`;
                        opt.textContent = label;
                        select.appendChild(opt);
                    });
                } else {
                    select.innerHTML = '<option value="" disabled>No rooms found for this ward</option>';
                }
            } catch (err) {
                console.error(err);
                select.innerHTML = '<option value="" disabled>Error loading rooms</option>';
            }
        }

        // Floor change listener - filter wards and reset rooms
        document.getElementById('floor_name').addEventListener('change', (e) => {
            const floorNumber = e.target.value;
            const wardType = document.getElementById('ward_type').value;
            fetchWards(floorNumber || null, wardType || null);
            resetWardAndRoom();
        });

        // Ward Type change listener
        document.getElementById('ward_type').addEventListener('change', (e) => {
            const wardType = e.target.value;
            const floorNumber = document.getElementById('floor_name').value;
            fetchWards(floorNumber || null, wardType || null);
            resetWardAndRoom();
        });

        function resetWardAndRoom() {
            document.getElementById('ward_name').value = '';
            document.getElementById('wardTypeDisplay').textContent = '';
            document.getElementById('assigned_beds').innerHTML = '<option value="" disabled>Select ward first to load rooms…</option>';
            document.getElementById('room_name').value = '';
        }

        // Ward change listener - reload rooms for selected ward
        document.getElementById('ward_name').addEventListener('change', (e) => {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const wardType = selectedOption.dataset.wardType;
            const display = document.getElementById('wardTypeDisplay');
            if (wardType) {
                display.textContent = `Type: ${wardType}`;
            } else {
                display.textContent = '';
            }
            const ward = e.target.value;
            if (ward) {
                fetchRooms(ward);
            } else {
                document.getElementById('assigned_beds').innerHTML = '<option value="">Select ward first to load rooms…</option>';
                document.getElementById('room_name').value = '';
            }
        });

        // Room change listener - populate hidden room_name field
        document.getElementById('assigned_beds').addEventListener('change', (e) => {
            const selectedOption = e.target.options[e.target.selectedIndex];
            document.getElementById('room_name').value = selectedOption.dataset.roomName || '';
        });

        function renderTable() {
            const tbody = document.getElementById('assignmentTableBody');
            tbody.innerHTML = '';

            if (assignments.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-12 text-center text-gray-400">No assignments found</td></tr>';
                return;
            }

            assignments.forEach(item => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50 transition-colors';

                let statusClass = 'bg-gray-100 text-gray-700';
                if (item.status === 'Active') statusClass = 'bg-green-100 text-green-700';
                if (item.status === 'Scheduled') statusClass = 'bg-blue-100 text-blue-700';
                if (item.status === 'Completed') statusClass = 'bg-purple-100 text-purple-700';

                row.innerHTML = `
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-teal-100 text-teal-600 flex items-center justify-center font-bold text-xs uppercase">
                                ${item.nurse_name.split(' ').map(n => n[0]).join('')}
                            </div>
                            <span class="font-medium text-gray-800">${item.nurse_name}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-gray-600 text-sm italic">${item.shift_type}</td>
                    <td class="px-6 py-4 text-gray-600 text-sm font-semibold">${item.ward_name}</td>
                    <td class="px-6 py-4 text-gray-600 text-sm">${item.work_area || '-'}</td>
                    <td class="px-6 py-4 text-gray-600 text-sm">${new Date(item.shift_date).toLocaleDateString()}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded text-xs font-bold uppercase ${statusClass}">${item.status}</span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button onclick="editAssignment(${item.id})" class="text-blue-600 hover:bg-blue-50 p-2 rounded transition-colors"><i class="fas fa-edit"></i></button>
                            <button onclick="deleteAssignment(${item.id})" class="text-red-600 hover:bg-red-50 p-2 rounded transition-colors"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function updateStats() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('statActive').textContent = assignments.filter(a => a.status === 'Active').length;
            document.getElementById('statToday').textContent = assignments.filter(a => a.shift_date === today).length;
            document.getElementById('statWards').textContent = [...new Set(assignments.map(a => a.ward_name))].length;
        }

        function openAssignmentModal() {
            document.getElementById('assignmentForm').reset();
            document.getElementById('nurseSearch').value = '';
            document.getElementById('role_id').value = '';
            renderNurseOptions(allNurses);
            document.getElementById('shift_id').value = '';
            document.getElementById('modalTitle').textContent = 'New Nurse Assignment';
            document.getElementById('assignmentModal').classList.remove('hidden');
        }

        function closeAssignmentModal() {
            document.getElementById('assignmentModal').classList.add('hidden');
        }

        function editAssignment(id) {
            const item = assignments.find(a => a.id == id);
            if (!item) return;

            document.getElementById('nurseSearch').value = item.nurse_name;
            renderNurseOptions(allNurses);

            document.getElementById('shift_id').value = item.id;
            document.getElementById('role_id').value = item.role_id;
            // For edit, use same date for both from and to
            document.getElementById('shift_date_from').value = item.shift_date_from || item.shift_date;
            document.getElementById('shift_date_to').value = item.shift_date_to || item.shift_date;
            document.getElementById('shift_type').value = item.shift_type;
            document.getElementById('floor_name').value = item.floor_number || '';
            document.getElementById('ward_type').value = item.ward_type || '';
            document.getElementById('ward_name').value = item.ward_name;
            document.getElementById('work_area').value = item.work_area || '';
            // Load rooms for this ward then restore selection
            if (item.ward_name) {
                fetchRooms(item.ward_name).then(() => {
                    document.getElementById('assigned_beds').value = item.room_number || '';
                    document.getElementById('room_name').value = item.room_name || '';
                });
            }

            document.getElementById('modalTitle').textContent = 'Edit Assignment';
            document.getElementById('assignmentModal').classList.remove('hidden');
        }

        async function deleteAssignment(id) {
            const confirmed = await Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            });

            if (confirmed.isConfirmed) {
                try {
                    const res = await fetch(`${API_BASE}/${id}`, { method: 'DELETE' });
                    const result = await res.json();
                    if (result.success) {
                        Swal.fire('Deleted!', 'Assignment has been removed.', 'success');
                        fetchAssignments();
                    }
                } catch (err) { console.error(err); }
            }
        }

        document.getElementById('assignmentForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            const shiftId = data.shift_id;
            delete data.shift_id;

            // Handle Floor Name and Number
            const floorSelect = document.getElementById('floor_name');
            const floorOption = floorSelect.options[floorSelect.selectedIndex];
            data.floor_number = floorSelect.value ? parseInt(floorSelect.value) : null;
            data.floor_name = floorOption ? (floorOption.dataset.name || '') : '';

            // Collect selected room number from the dropdown
            const roomSelect = document.getElementById('assigned_beds');
            data.room_number = roomSelect.value || '';
            data.room_name = document.getElementById('room_name').value || '';
            delete data.assigned_beds; // already handled as room_number
            delete data.room_no; // backward compatibility

            // Convert role_id to integer
            if (data.role_id) {
                data.role_id = parseInt(data.role_id, 10);
            }

            // Validate date range
            if (!shiftId) {
                const dateFrom = new Date(data.shift_date_from);
                const dateTo = new Date(data.shift_date_to);

                if (dateTo < dateFrom) {
                    Swal.fire('Invalid Date Range', 'End date must be after or equal to start date', 'error');
                    return;
                }

                // Calculate days
                const diffTime = Math.abs(dateTo - dateFrom);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;

                if (diffDays > 365) {
                    Swal.fire('Date Range Too Long', 'Date range cannot exceed 365 days', 'error');
                    return;
                }

                // Warn for large ranges
                if (diffDays > 31) {
                    const confirmed = await Swal.fire({
                        title: 'Large Date Range',
                        text: `This will create ${diffDays} shift assignments. Continue?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, create them'
                    });
                    if (!confirmed.isConfirmed) return;
                }
            }

            try {
                const url = shiftId ? `${API_BASE}/${shiftId}` : API_BASE;
                const method = shiftId ? 'PUT' : 'POST';

                // Show loading
                Swal.fire({
                    title: 'Processing...',
                    text: shiftId ? 'Updating assignment' : 'Creating assignments',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const res = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await res.json();
                if (result.success) {
                    const message = shiftId
                        ? 'Assignment updated successfully'
                        : result.message || 'Shift assignment created successfully';
                    Swal.fire('Success!', message, 'success');
                    closeAssignmentModal();
                    fetchAssignments();
                } else {
                    Swal.fire('Error', result.error || 'Failed to save assignment', 'error');
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'An unexpected error occurred', 'error');
            }
        });

        document.getElementById('filterDate').addEventListener('change', fetchAssignments);
        document.getElementById('filterWard').addEventListener('change', fetchAssignments);
    </script>
</body>

</html>