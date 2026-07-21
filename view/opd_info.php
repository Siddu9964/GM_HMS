<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OPD Management - GM HMS</title>
    
    <!-- Tailwind CSS (Standard Build for Production Stability) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Common Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin_common.css">
    
    <style>
        /* Fallback for Tailwind 2 vs 3 discrepancies and custom styles */
        .glass-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border: 1px solid rgba(241, 245, 249, 1); }
        .stat-card { transition: all 0.2s; border-radius: 12px; }
        .stat-card:hover { transform: translateY(-4px); }
        .data-table-container { border-radius: 12px; overflow: hidden; background: white; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .badge-scheduled { background: #e0f2fe; color: #0369a1; }
        .badge-completed { background: #dcfce7; color: #15803d; }
        .badge-cancelled { background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar Inclusion -->
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navbar -->
            <?php include 'includes/navbar.php'; ?>

            <main class="flex-1 overflow-y-auto p-8">
                <!-- KPI Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Today's Visits -->
                    <div class="bento-card">
                        <div class="bento-title">Today's Visits</div>
                        <h2 id="kpi-today-visits" class="bento-value">0</h2>
                        <i class="fas fa-users bento-icon"></i>
                        <p class="text-xs text-green-600 mt-2 font-medium z-10"><i class="fas fa-arrow-up mr-1"></i> +8% from yesterday</p>
                    </div>

                    <!-- Revenue -->
                    <div class="bento-card">
                        <div class="bento-title">Total Revenue</div>
                        <h2 id="kpi-revenue" class="bento-value">₹0</h2>
                        <i class="fas fa-rupee-sign bento-icon"></i>
                        <p class="text-xs text-gray-500 mt-2 z-10">Current Month Billing</p>
                    </div>

                    <!-- Pending Appointments -->
                    <div class="bento-card">
                        <div class="bento-title">Pending</div>
                        <h2 id="kpi-pending" class="bento-value">0</h2>
                        <i class="fas fa-clock bento-icon"></i>
                        <p class="text-xs text-orange-600 mt-2 z-10">Require Confirmation</p>
                    </div>

                    <!-- Avg Waiting Time -->
                    <div class="bento-card">
                        <div class="bento-title">Avg Wait Time</div>
                        <h2 class="bento-value">18m</h2>
                        <i class="fas fa-hourglass-half bento-icon"></i>
                        <p class="text-xs text-red-600 mt-2 z-10"><i class="fas fa-arrow-up mr-1"></i> High Traffic</p>
                    </div>
                </div>

                <!-- Filters & Table -->
                <div class="data-table-container">
                    <div class="p-6 border-b border-gray-100 flex flex-wrap gap-4 items-center justify-between">
                        <div class="flex items-center gap-4">
                            <h3 class="text-lg font-bold text-gray-800">All OPD Encounters</h3>
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                <input type="text" id="tableSearch" placeholder="Search patients..." 
                                       class="pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none w-64">
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <select id="statusFilter" class="bg-gray-50 border border-gray-200 rounded-lg px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">All Statuses</option>
                                <option value="Scheduled">Scheduled</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                            <button onclick="fetchData()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-500 text-xs font-semibold uppercase tracking-wider">
                                    <th class="px-6 py-4">ID</th>
                                    <th class="px-6 py-4">Patient Details</th>
                                    <th class="px-6 py-4">Assigned Doctor</th>
                                    <th class="px-6 py-4">Visit Purpose</th>
                                    <th class="px-6 py-4">Date & Time</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="opd-table-body" class="divide-y divide-gray-100 text-sm">
                                <!-- Data will be loaded via JS -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div id="loading-state" class="py-20 text-center text-gray-500">
                        <i class="fas fa-spinner fa-spin text-4xl mb-4 text-blue-500"></i>
                        <p class="font-medium">Fetching OPD data...</p>
                    </div>

                    <div id="empty-state" class="hidden py-20 text-center text-gray-400">
                        <i class="fas fa-folder-open text-5xl mb-4"></i>
                        <p class="font-medium">No OPD visits found for this criteria.</p>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Encounter Detail Modal -->
    <div id="enModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
            <div class="p-6 border-b flex justify-between items-center bg-gray-50">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Encounter Details</h2>
                    <p class="text-xs text-gray-500" id="mod-apt-id">#0000</p>
                </div>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-8">
                <!-- Header Info -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 bg-blue-50 p-6 rounded-xl border border-blue-100">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-blue-400 block mb-1">Patient</span>
                        <p class="font-bold text-gray-800" id="mod-pat-name">-</p>
                        <p class="text-xs text-gray-500" id="mod-pat-id">-</p>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-blue-400 block mb-1">Doctor</span>
                        <p class="font-bold text-gray-800" id="mod-doc-name">-</p>
                        <p class="text-xs text-gray-500" id="mod-doc-spec">-</p>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-blue-400 block mb-1">Visit Date</span>
                        <p class="font-bold text-gray-800" id="mod-date">-</p>
                        <p class="text-xs text-gray-500" id="mod-time">-</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Left: Clinical -->
                    <div>
                        <h4 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                            <i class="fas fa-stethoscope text-blue-500"></i> Clinical Assessment
                        </h4>
                        <div class="space-y-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="text-[10px] uppercase font-bold text-gray-400">Chief Complaint</label>
                                <p class="text-sm text-gray-700 italic mt-1" id="mod-reason">-</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="text-[10px] uppercase font-bold text-gray-400">Vitals</label>
                                <div id="mod-vitals" class="grid grid-cols-2 gap-2 mt-2">
                                    <div class="text-xs">BP: <span class="font-bold">-</span></div>
                                    <div class="text-xs">Pulse: <span class="font-bold">-</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Right: Plan & Billing -->
                    <div>
                        <h4 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                            <i class="fas fa-file-prescription text-green-500"></i> Prescription & Plan
                        </h4>
                        <div id="mod-prescriptions" class="text-sm text-gray-600 space-y-2">
                            <p class="italic text-gray-400">No prescriptions found.</p>
                        </div>
                        
                        <h4 class="text-sm font-bold text-gray-700 mt-6 mb-4 flex items-center gap-2">
                            <i class="fas fa-file-invoice-dollar text-orange-500"></i> Billing Status
                        </h4>
                        <div id="mod-billing" class="bg-orange-50 p-4 rounded-lg border border-orange-100 flex justify-between items-center">
                            <div class="text-sm font-bold text-orange-700" id="mod-bill-amount">₹0.00</div>
                            <span class="badge badge-scheduled" id="mod-bill-status">Unknown</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-6 border-t bg-gray-50 flex justify-end gap-3">
                <button onclick="closeModal()" class="px-5 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Close</button>
                <button id="mod-print-btn" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-bold flex items-center gap-2">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>

    <script>
        async function fetchData() {
            const tableBody = document.getElementById('opd-table-body');
            const loading = document.getElementById('loading-state');
            const empty = document.getElementById('empty-state');
            
            loading.classList.remove('hidden');
            empty.classList.add('hidden');
            tableBody.innerHTML = '';

            try {
                // Fetch Summary for KPIs
                const summaryRes = await fetch('/GM_HMS/api/admin/opd-summary');
                const summaryData = await summaryRes.json();
                
                if (summaryData.success) {
                    const stats = summaryData.data.stats;
                    const revenue = summaryData.data.revenue;
                    document.getElementById('kpi-today-visits').textContent = stats.today_appointments || 0;
                    document.getElementById('kpi-pending').textContent = stats.upcoming_appointments || 0;
                    document.getElementById('kpi-revenue').textContent = '₹' + (revenue.monthly_revenue || 0).toLocaleString();
                }

                const status = document.getElementById('statusFilter').value;
                const detailsRes = await fetch(`/GM_HMS/api/admin/opd-details?status=${status}`);
                const responseText = await detailsRes.text();
                
                let detailsData;
                try {
                    detailsData = JSON.parse(responseText);
                } catch (e) {
                    throw new Error(`Invalid JSON response: ${responseText.substring(0, 100)}...`);
                }

                loading.classList.add('hidden');

                if (detailsData.success && detailsData.data.appointments.length > 0) {
                    detailsData.data.appointments.forEach(apt => {
                        const aptStatus = apt.appointment_status;
                        const statusRaw = (aptStatus === 0 || aptStatus === 'Completed') ? 'Completed' : (aptStatus === 1 || aptStatus === 'Pending') ? 'Active' : 'Unknown';
                        const statusClass = (aptStatus === 0 || aptStatus === 'Completed') ? 'completed' : (aptStatus === 1 || aptStatus === 'Pending') ? 'scheduled' : 'cancelled';
                        
                        const displayId = typeof apt.appointment_id === 'string' && apt.appointment_id.includes('-') 
                            ? apt.appointment_id.split('-').pop() 
                            : apt.appointment_id;
                        const row = `
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-semibold text-gray-700">#${displayId}</td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900">${apt.patient_name || 'Walking Patient'}</div>
                                    <div class="text-xs text-gray-500">${apt.patient_id}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-800">${apt.doctor_name ? 'Dr. ' + apt.doctor_name : '<span class="text-gray-400">Not Assigned</span>'}</div>
                                    <div class="text-xs text-blue-500 font-semibold">${apt.specialization || ''}</div>
                                </td>
                                <td class="px-6 py-4 text-gray-600 italic">"${apt.reason || 'No appointment'}"</td>
                                <td class="px-6 py-4">
                                    <div class="text-gray-800">${apt.appointment_date || '<span class="text-gray-400">Never Visited</span>'}</div>
                                    <div class="text-xs text-gray-500">${apt.appointment_time || ''}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="badge badge-${statusClass}">${statusRaw}</span>
                                </td>
                                <td class="px-6 py-4">
                                    ${apt.appointment_date ? `<button onclick="viewEncounter('${apt.appointment_id}')" class="text-blue-600 hover:text-blue-800 font-bold p-1" title="View Details"><i class="fas fa-eye"></i></button>
                                    <button onclick="printReceipt('${apt.patient_id}', '${apt.appointment_date}')" class="text-gray-400 hover:text-red-600 font-bold p-1 ml-2" title="Print Receipt"><i class="fas fa-print"></i></button>` : '<span class="text-xs text-gray-400 italic">No Action</span>'}
                                </td>
                            </tr>
                        `;
                        tableBody.innerHTML += row;
                    });
                } else {
                    empty.classList.remove('hidden');
                }

            } catch (error) {
                console.error('OPD Fetch Error:', error);
                loading.classList.add('hidden');
                empty.classList.remove('hidden');
                empty.innerHTML = `<i class='fas fa-exclamation-circle text-red-500 text-5xl mb-4'></i>
                                   <p class='text-red-500 font-bold'>Failed to load OPD data.</p>
                                   <p class='text-gray-500 text-xs mt-2'>Error: ${error.message}</p>
                                   <button onclick='location.reload()' class='mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm'>Retry Page</button>`;
            }
        }

        async function viewEncounter(id) {
            try {
                const res = await fetch(`/GM_HMS/api/opd/encounter/${id}`);
                const data = await res.json();
                if(!data.success) throw new Error(data.message);

                const enc = data.data;
                const apt = enc.appointment;
                
                // Set Header
                document.getElementById('mod-apt-id').textContent = '#' + apt.appointment_id;
                document.getElementById('mod-pat-name').textContent = apt.patient_name || (apt.first_name + ' ' + apt.last_name);
                document.getElementById('mod-pat-id').textContent = apt.patient_id;
                document.getElementById('mod-doc-name').textContent = 'Dr. ' + (apt.doctor_name || 'TBD');
                document.getElementById('mod-doc-spec').textContent = apt.specialization || 'General';
                document.getElementById('mod-date').textContent = apt.appointment_date;
                document.getElementById('mod-time').textContent = apt.appointment_time;
                document.getElementById('mod-reason').textContent = apt.reason || 'No clinical reason provided.';
                
                // Vitals
                const vitalsDiv = document.getElementById('mod-vitals');
                vitalsDiv.innerHTML = '';
                if(enc.consultation && enc.consultation.vital_signs) {
                    const v = JSON.parse(enc.consultation.vital_signs);
                    Object.entries(v).forEach(([key, val]) => {
                        vitalsDiv.innerHTML += `<div class="text-xs capitalize">${key}: <span class="font-bold text-gray-800">${val || '-'}</span></div>`;
                    });
                } else {
                    vitalsDiv.innerHTML = '<p class="text-[10px] text-gray-400">Vitals not recorded.</p>';
                }

                // Prescriptions
                const prescDiv = document.getElementById('mod-prescriptions');
                prescDiv.innerHTML = '';
                if(enc.prescriptions && enc.prescriptions.length > 0) {
                    enc.prescriptions.forEach(m => {
                        prescDiv.innerHTML += `<div class="p-2 border-l-2 border-green-500 bg-green-50 rounded">
                            <p class="font-bold text-green-800 text-xs">${m.name}</p>
                            <p class="text-[10px] text-green-600">${m.dosage} | ${m.frequency} | ${m.duration}</p>
                        </div>`;
                    });
                } else {
                    prescDiv.innerHTML = '<p class="italic text-gray-400 text-xs">No medications prescribed in this session.</p>';
                }

                // Billing
                document.getElementById('mod-bill-amount').textContent = '₹' + (apt.total_amount || 0);
                document.getElementById('mod-bill-status').textContent = apt.payment_status || 'Pending';
                document.getElementById('mod-bill-status').className = 'badge ' + (apt.payment_status === 'Paid' ? 'badge-completed' : 'badge-scheduled');

                // Print Button
                document.getElementById('mod-print-btn').onclick = () => printReceipt(apt.patient_id, apt.appointment_date);

                document.getElementById('enModal').classList.remove('hidden');
            } catch (err) {
                alert("Error loading details: " + err.message);
            }
        }

        function closeModal() {
            document.getElementById('enModal').classList.add('hidden');
        }

        function printReceipt(pid, date) {
            window.open(`/GM_HMS/reception_view/print_opd_receipt.php?patient_id=${pid}&date=${date}`, '_blank', 'width=900,height=800');
        }

        // Initialize search filter
        document.getElementById('tableSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#opd-table-body tr');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Initial Data Fetch
        document.addEventListener('DOMContentLoaded', fetchData);
    </script>
    <script src="assets/js/admin_common.js"></script>
</body>
</html>
