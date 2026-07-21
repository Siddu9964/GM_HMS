<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPD Management - GM HMS</title>
    
    <!-- Tailwind CSS (Standard Build for Production Stability) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Common Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin_common.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; }
        .dashboard-container { display: flex; height: 100vh; overflow: hidden; }
        .main-content { flex: 1; overflow-y: auto; padding: 2rem; }
        .bed-card { padding: 1rem; border-radius: 12px; border: 1px solid #e2e8f0; transition: all 0.2s; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
        .bed-occupied { background: #fff1f2; border-color: #fecdd3; color: #991b1b; }
        .bed-available { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
        .bed-maintenance { background: #fefce8; border-color: #fef08a; color: #854d0e; }
        .bed-blocked { background: #f8fafc; border-color: #cbd5e1; color: #475569; }
        .tab-btn { padding: 0.75rem 1.5rem; font-weight: 600; border-bottom: 2px solid transparent; transition: all 0.2s; }
        .tab-btn.active { color: #1f6b4a; border-color: #1f6b4a; background: #E6FAFA; }
    </style>
</head>
<body>
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar Inclusion -->
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Include Navbar -->
            <?php include 'includes/navbar.php'; ?>
            
            <main class="flex-1 overflow-y-auto p-8">

            <!-- Tabs -->
            <div class="flex border-b border-slate-200 mb-8 bg-white rounded-t-xl px-4">
                <button onclick="switchTab('admissions')" id="tab-admissions" class="tab-btn active">Active Admissions</button>
                <button onclick="switchTab('beds')" id="tab-beds" class="tab-btn">Bed Status Grid</button>
            </div>

            <!-- Content Area: Active Admissions -->
            <div id="content-admissions" class="space-y-6">
                <!-- IPD KPIs -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                        <div class="text-slate-500 font-bold text-xs uppercase mb-1">Live Admissions</div>
                        <div id="kpi-active" class="text-4xl font-black text-blue-600">0</div>
                        <div class="text-xs text-slate-400 mt-2">Currently in-patient</div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                        <div class="text-slate-500 font-bold text-xs uppercase mb-1">Bed Occupancy</div>
                        <div id="kpi-occupancy" class="text-4xl font-black text-orange-600">0%</div>
                        <div class="text-xs text-slate-400 mt-2">Total capacity utilized</div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                        <div class="text-slate-500 font-bold text-xs uppercase mb-1">Monthly Billing</div>
                        <div id="kpi-financials" class="text-4xl font-black text-green-600">₹0</div>
                        <div class="text-xs text-slate-400 mt-2">Total revenue from IPD</div>
                    </div>
                </div>

                <!-- Admissions Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                        <h3 class="font-bold text-slate-800">Currently Admitted Patients</h3>
                        <input type="text" id="patientSearch" placeholder="Search name, bed, id..." class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none w-64">
                    </div>
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50 text-slate-400 font-bold text-xs uppercase tracking-wider">
                                <th class="px-6 py-4">Admission Details</th>
                                <th class="px-6 py-4">Patient & Contact</th>
                                <th class="px-6 py-4">Doctor & Bed</th>
                                <th class="px-6 py-4">Days</th>
                                <th class="px-6 py-4">Financials (Est.)</th>
                                <th class="px-6 py-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="admissions-table-body" class="divide-y divide-slate-100 text-sm">
                            <!-- JS Injection -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Content Area: Bed Status Grid -->
            <div id="content-beds" class="hidden space-y-8">
                <!-- Ward Summary Table -->
                <div id="ward-summary-container" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100">
                        <h3 class="font-bold text-slate-800">Ward Occupancy Summary</h3>
                    </div>
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50 text-slate-400 font-bold text-xs uppercase tracking-wider">
                                <th class="px-6 py-4">Ward Name</th>
                                <th class="px-6 py-4">Ward Type</th>
                                <th class="px-6 py-4 text-center">Occupied</th>
                                <th class="px-6 py-4 text-center">Available</th>
                                <th class="px-6 py-4 text-center">Total Beds</th>
                                <th class="px-6 py-4 text-right">Occupancy %</th>
                            </tr>
                        </thead>
                        <tbody id="ward-summary-body" class="divide-y divide-slate-100 text-sm">
                            <!-- JS Injection -->
                        </tbody>
                    </table>
                </div>

                <div id="wards-container" class="grid grid-cols-1 gap-8">
                    <!-- Ward Sections Injected via JS -->
                </div>
            </div>
            </main>
        </div>
    </div>

    <script>
        let currentTab = 'admissions';

        function switchTab(tab) {
            currentTab = tab;
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(`tab-${tab}`).classList.add('active');
            
            document.getElementById('content-admissions').classList.add('hidden');
            document.getElementById('content-beds').classList.add('hidden');
            document.getElementById(`content-${tab}`).classList.remove('hidden');
            
            fetchData();
        }

        async function fetchData() {
            try {
                // Fetch IPD Summary for KPIs (Universal)
                const summaryRes = await fetch('/GM_HMS/api/admin/ipd-summary');
                const summaryText = await summaryRes.text();
                let summary;
                try {
                    summary = JSON.parse(summaryText);
                } catch (e) {
                    throw new Error(`Summary API Error: ${summaryText.substring(0, 50)}...`);
                }
                
                if (summary.success) {
                    document.getElementById('kpi-active').textContent = summary.data.stats.active_admissions || 0;
                    document.getElementById('kpi-occupancy').textContent = (summary.data.bed_stats.occupancy_percentage || 0) + '%';
                }

                if (currentTab === 'admissions') {
                    // Fetch Detailed Admissions
                    const detRes = await fetch('/GM_HMS/api/admin/ipd-details?status=Admitted');
                    const detText = await detRes.text();
                    let details;
                    try {
                        details = JSON.parse(detText);
                    } catch (e) {
                        throw new Error(`Admissions API Error: ${detText.substring(0, 50)}...`);
                    }
                    
                    const tbody = document.getElementById('admissions-table-body');
                    tbody.innerHTML = '';
                    
                    if (details.success && details.data.admissions.length > 0) {
                        let totalMonthly = 0;
                        details.data.admissions.forEach(adm => {
                            totalMonthly += (adm.financials.total_charges || 0);
                            const row = `
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-900">${adm.admission_id}</div>
                                        <div class="text-xs text-slate-500">${adm.admission_date}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-900">${adm.patient_name}</div>
                                        <div class="text-xs text-slate-500">${adm.patient_contact}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-slate-800">Dr. ${adm.doctor_name}</div>
                                        <div class="text-xs text-blue-600 font-bold">${adm.ward_name} / Bed ${adm.bed_number}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="bg-blue-50 text-blue-600 font-bold px-2.5 py-1 rounded-lg">${adm.days_admitted} days</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-red-700 font-bold">₹${(adm.financials.balance_due || 0).toLocaleString()}</div>
                                        <div class="text-[10px] text-slate-400 capitalize">Due from total ₹${adm.financials.total_charges}</div>
                                    </td>
                                    <td class="px-6 py-4 flex gap-2">
                                        <button onclick="location.href='/GM_HMS/reception_view/ipd_management/views/admissions/index.php?admission_id=${adm.admission_id}'" class="bg-slate-100 p-2 rounded-lg text-slate-600 hover:bg-blue-600 hover:text-white transition-all" title="View Admission"><i class="fas fa-eye"></i></button>
                                        <button onclick="location.href='/GM_HMS/reception_view/ipd_management/views/payments/index.php?admission_id=${adm.admission_id}'" class="bg-slate-100 p-2 rounded-lg text-slate-600 hover:bg-green-600 hover:text-white transition-all" title="Billing & Payments"><i class="fas fa-file-invoice-dollar"></i></button>
                                    </td>
                                </tr>
                            `;
                            tbody.innerHTML += row;
                        });
                        document.getElementById('kpi-financials').textContent = '₹' + totalMonthly.toLocaleString();
                    } else {
                        tbody.innerHTML = '<tr><td colspan="6" class="p-10 text-center text-slate-400">No active admissions found.</td></tr>';
                    }
                } else {
                    // Fetch Bed Details Grid
                    const bedRes = await fetch('/GM_HMS/api/admin/bed-details');
                    const bedText = await bedRes.text();
                    let bedData;
                    try {
                        bedData = JSON.parse(bedText);
                    } catch (e) {
                        throw new Error(`Bed API Error: ${bedText.substring(0, 50)}...`);
                    }
                    
                    const container = document.getElementById('wards-container');
                    container.innerHTML = '';
                    
                    if (bedData.success) {
                        const beds = bedData.data.beds;
                        const wards = [...new Set(beds.map(b => b.ward_name))];
                        
                        const summaryBody = document.getElementById('ward-summary-body');
                        summaryBody.innerHTML = '';

                        wards.forEach(wardName => {
                            const wardBeds = beds.filter(b => b.ward_name === wardName);
                            const wardType = wardBeds[0]?.ward_type || 'N/A';
                            const occupiedCount = wardBeds.filter(b => b.bed_status === 'Occupied').length;
                            const availableCount = wardBeds.filter(b => b.bed_status === 'Available').length;
                            const totalBeds = wardBeds.length;
                            const occupancyPercent = ((occupiedCount / totalBeds) * 100).toFixed(1);

                            // Add to summary table
                            summaryBody.innerHTML += `
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4 font-bold text-slate-800 uppercase">${wardName}</td>
                                    <td class="px-6 py-4 text-slate-500 font-medium">${wardType}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="bg-red-50 text-red-600 px-2.5 py-1 rounded-lg font-bold">${occupiedCount}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="bg-green-50 text-green-600 px-2.5 py-1 rounded-lg font-bold">${availableCount}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold text-slate-700">${totalBeds}</td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <div class="w-16 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                                <div class="bg-blue-500 h-full" style="width: ${occupancyPercent}%"></div>
                                            </div>
                                            <span class="font-black text-slate-900">${occupancyPercent}%</span>
                                        </div>
                                    </td>
                                </tr>
                            `;

                            let wardHtml = `
                                <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200">
                                    <div class="flex justify-between items-center mb-6 border-b pb-4">
                                        <h4 class="text-xl font-black text-slate-800 uppercase tracking-tight"><i class="fas fa-hotel mr-3 text-blue-500"></i>${wardName}</h4>
                                        <div class="flex gap-4">
                                            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">${wardBeds.length} Total Beds</span>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                            `;
                            
                            wardBeds.forEach(bed => {
                                const statusClass = `bed-${bed.bed_status.toLowerCase()}`;
                                const patientInfo = bed.patient_name ? `<div class='text-[10px] truncate max-w-full font-bold mt-1 uppercase'>${bed.patient_name}</div>` : '';
                                wardHtml += `
                                    <div class="bed-card ${statusClass} flex flex-col items-center justify-center text-center">
                                        <i class="fas fa-bed text-2xl mb-2"></i>
                                        <div class="text-xs font-black">#${bed.bed_number}</div>
                                        <div class="text-[9px] uppercase font-bold opacity-75">${bed.room_category}</div>
                                        ${patientInfo}
                                        <div class="mt-2 text-[8px] font-black uppercase opacity-60 tracking-wider">${bed.bed_status}</div>
                                    </div>
                                `;
                            });
                            
                            wardHtml += `</div></div>`;
                            container.innerHTML += wardHtml;
                        });
                    }
                }
            } catch (error) {
                console.error('IPD Fetch Error:', error);
                const tbody = document.getElementById('admissions-table-body');
                if (tbody) {
                    tbody.innerHTML = `<tr><td colspan="6" class="p-10 text-center">
                        <i class='fas fa-exclamation-circle text-red-500 text-3xl mb-2'></i>
                        <p class='text-red-500 font-bold'>System error during data sync.</p>
                        <p class='text-slate-400 text-[10px]'>Details: ${error.message}</p>
                    </td></tr>`;
                }
            }
        }

        // Initialize Search logic for admissions
        document.getElementById('patientSearch')?.addEventListener('input', function(e) {
            const val = e.target.value.toLowerCase();
            document.querySelectorAll('#admissions-table-body tr').forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(val) ? '' : 'none';
            });
        });

        // Initial Load
        document.addEventListener('DOMContentLoaded', fetchData);
    </script>
    <script src="assets/js/admin_common.js"></script>
</body>
</html>
