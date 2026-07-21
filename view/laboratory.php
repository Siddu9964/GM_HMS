<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
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
    <title>Laboratory Services - GM HMS</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Common Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin_common.css">
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f8fafc; }
        ::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }

        .service-tab {
            position: relative;
            cursor: pointer;
            padding: 1.25rem 2rem;
            color: #64748b;
            font-weight: 600;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border-bottom: 2px solid transparent;
            white-space: nowrap;
        }
        
        .service-tab.active {
            color: #0f172a;
            background: rgba(15, 23, 42, 0.04);
            border-bottom-color: #0f172a;
        }

        .premium-table thead th {
            background: #f8fafc;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.1em;
            color: #475569;
            padding: 1.25rem 1rem;
            font-weight: 800;
            border-bottom: 2px solid #f1f5f9;
        }

        .premium-table tbody tr { transition: all 0.2s ease; }
        .premium-table tbody tr:hover { background-color: #f1f5f9; }
        .premium-table td { padding: 1.25rem 1rem; border-bottom: 1px solid #f1f5f9; }

        .price-tag { font-family: 'Inter', monospace; font-weight: 700; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fadeIn 0.5s ease-out forwards; }
        
        .loader {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #0f172a;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-900">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Navbar -->
            <?php include 'includes/navbar.php'; ?>
            
            <main class="flex-1 overflow-y-auto p-6 md:p-10">
                <!-- Page Header -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-10 animate-fade-in">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <span class="px-3 py-1 bg-slate-100 text-slate-700 text-[10px] font-black uppercase tracking-widest rounded-full">Diagnostics Hub</span>
                        </div>
                        <h1 class="text-4xl font-black tracking-tight text-slate-900 flex items-center gap-4">
                            <div class="p-3 rounded-2xl shadow-xl shadow-slate-200" style="background: var(--gm-accent);">
                                <i class="fas fa-microscope text-white"></i>
                            </div>
                            Laboratory Services
                        </h1>
                        <p class="text-slate-500 mt-2 font-medium">Manage all diagnostic tests and radiology pricing.</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <button onclick="openCreateModal()" class="px-6 py-3 text-white font-black rounded-xl shadow-lg transition-all flex items-center gap-2" style="background: var(--gm-accent);">
                            <i class="fas fa-plus"></i> Add New Service
                        </button>
                        <button onclick="fetchServices()" class="p-3 bg-white border border-slate-200 rounded-xl text-slate-600 hover:text-slate-900 transition-all shadow-sm">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>

                <!-- Main Container -->
                <div class="table-container mb-8">
                    <!-- Search & Tabs -->
                    <div class="p-6 bg-slate-50/50 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
                        <div class="flex items-center border-b-2 border-slate-100 overflow-x-auto w-full md:w-auto">
                            <div class="service-tab active" onclick="switchTab('lab', this)">Lab Tests</div>
                            <div class="service-tab" onclick="switchTab('radiology', this)">Radiology</div>
                            <div class="service-tab" onclick="switchTab('other', this)">Others</div>
                        </div>
                        <div class="relative w-full md:w-80">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input type="text" id="search-services" onkeyup="filterServices()" placeholder="Search services..." class="w-full pl-12 pr-4 py-3 bg-white border border-slate-200 rounded-2xl focus:ring-4 focus:ring-slate-900/10 focus:border-slate-900 outline-none transition-all font-medium">
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 px-6 pt-6">
                        <div class="bento-card">
                            <div class="bento-title">Total Lab</div>
                            <h4 class="bento-value" id="stat-lab-count">0</h4>
                            <i class="fas fa-microscope bento-icon"></i>
                        </div>
                        <div class="bento-card">
                            <div class="bento-title">Radiology</div>
                            <h4 class="bento-value" id="stat-radiology-count">0</h4>
                            <i class="fas fa-x-ray bento-icon"></i>
                        </div>
                        <div class="bento-card">
                            <div class="bento-title">Others</div>
                            <h4 class="bento-value" id="stat-other-count">0</h4>
                            <i class="fas fa-vial bento-icon"></i>
                        </div>
                    </div>

                    <!-- Tables -->
                    <div class="flex-1 overflow-y-auto p-6 bg-slate-50/30 min-h-[400px]">
                        <div id="tab-lab" class="tab-content">
                            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                                <table class="w-full premium-table">
                                    <thead>
                                        <tr>
                                            <th class="text-left w-16">#</th>
                                            <th class="text-left">ID</th>
                                            <th class="text-left">Test Name</th>
                                            <th class="text-right">OPD</th>
                                            <th class="text-right">GW</th>
                                            <th class="text-right">SPVT</th>
                                            <th class="text-right">PVT/CCU</th>
                                            <th class="text-right">Suite</th>
                                            <th class="text-center w-24">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="lab-tbody"></tbody>
                                </table>
                            </div>
                        </div>

                        <div id="tab-radiology" class="tab-content hidden">
                            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                                <table class="w-full premium-table">
                                    <thead>
                                        <tr>
                                            <th class="text-left w-16">#</th>
                                            <th class="text-left">ID</th>
                                            <th class="text-left">Name</th>
                                            <th class="text-left">Modality</th>
                                            <th class="text-right">OPD</th>
                                            <th class="text-right">GW</th>
                                            <th class="text-right">SPVT</th>
                                            <th class="text-right">PVT/CCU</th>
                                            <th class="text-right">Suite</th>
                                            <th class="text-center w-24">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="radiology-tbody"></tbody>
                                </table>
                            </div>
                        </div>

                        <div id="tab-other" class="tab-content hidden">
                            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                                <table class="w-full premium-table">
                                    <thead>
                                        <tr>
                                            <th class="text-left w-16">#</th>
                                            <th class="text-left">ID</th>
                                            <th class="text-left">Name</th>
                                            <th class="text-right">OP/GW</th>
                                            <th class="text-right">SPVT</th>
                                            <th class="text-right">PVT/CCU</th>
                                            <th class="text-right">Suite</th>
                                            <th class="text-center w-24">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="other-tbody"></tbody>
                                </table>
                            </div>
                        </div>

                        <div id="loading-state" class="hidden flex flex-col items-center justify-center py-20 gap-4">
                            <div class="loader"></div>
                            <p class="text-slate-400 font-bold uppercase text-xs">Loading services...</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Create Modal -->
    <div id="create-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-2xl rounded-[2.5rem] shadow-2xl overflow-hidden animate-fade-in border border-white/20">
            <div class="p-4 md:p-6 bg-slate-50/50 border-b border-slate-100 flex justify-between items-center text-slate-900">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 rounded-2xl flex items-center justify-center text-white text-xl shadow-lg" style="background: var(--gm-accent);">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-black">Add New Service</h3>
                        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mt-1">Register new diagnostic item</p>
                    </div>
                </div>
                <button onclick="closeCreateModal()" class="h-10 w-10 rounded-full hover:bg-slate-200 transition-all flex items-center justify-center text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="create-service-form" class="p-4 md:p-6 space-y-6">
                <div class="space-y-2">
                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest">Select Category</label>
                    <select id="create-service-category" name="category" onchange="renderCreateFields()" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-bold focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all">
                        <option value="lab">Lab Test</option>
                        <option value="radiology">Radiology</option>
                        <option value="other">Other Service</option>
                    </select>
                </div>
                
                <div id="create-dynamic-fields" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Fields injected here -->
                </div>

                <div class="flex justify-end gap-4 pt-4">
                    <button type="button" onclick="closeCreateModal()" class="px-8 py-3 text-slate-500 font-bold hover:text-slate-900 transition-all">
                        Cancel
                    </button>
                    <button type="submit" class="px-10 py-3 text-white font-black rounded-xl shadow-lg shadow-slate-200 transition-all transform hover:-translate-y-1 active:scale-95 flex items-center gap-2" style="background: var(--gm-accent);">
                        <i class="fas fa-save"></i>
                        Add Service
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="edit-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-2xl rounded-[2.5rem] shadow-2xl overflow-hidden animate-fade-in border border-white/20">
            <div class="p-4 md:p-6 bg-slate-50/50 border-b border-slate-100 flex justify-between items-center text-slate-900">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 rounded-2xl flex items-center justify-center text-white text-xl shadow-lg" style="background: var(--gm-accent);">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-black">Edit Service</h3>
                        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mt-1">Update service parameters</p>
                    </div>
                </div>
                <button onclick="closeEditModal()" class="h-10 w-10 rounded-full hover:bg-slate-200 transition-all flex items-center justify-center text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="edit-service-form" class="p-4 md:p-6 space-y-6">
                <input type="hidden" name="service_type" id="edit-service-type">
                <input type="hidden" name="service_id" id="edit-service-id">
                <div id="edit-dynamic-fields" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
                <div class="flex justify-end gap-4 pt-4">
                    <button type="button" onclick="closeEditModal()" class="px-8 py-3 text-slate-500 font-bold hover:text-slate-900 transition-all">
                        Cancel
                    </button>
                    <button type="submit" class="px-10 py-3 text-white font-black rounded-xl shadow-lg shadow-slate-200 transition-all transform hover:-translate-y-1 active:scale-95 flex items-center gap-2" style="background: var(--gm-accent);">
                        <i class="fas fa-save"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Custom Toast Notification -->
    <div id="toast-container" class="fixed inset-0 flex items-center justify-center z-[10000] pointer-events-none hidden bg-slate-900/10 backdrop-blur-[2px]">
        <div id="toast-message" class="bg-white/95 backdrop-blur-xl border border-white/50 p-10 rounded-[3rem] shadow-[0_32px_120px_-20px_rgba(0,0,0,0.2)] transform scale-50 opacity-0 transition-all duration-500 ease-out flex flex-col items-center gap-6 max-w-sm w-full pointer-events-auto">
            <div id="toast-icon-container" class="w-24 h-24 rounded-[2rem] flex items-center justify-center text-4xl shadow-inner"></div>
            <div class="space-y-2 text-center">
                <h3 id="toast-title" class="text-2xl font-black text-slate-900 tracking-tight"></h3>
                <p id="toast-text" class="text-slate-500 font-bold leading-relaxed"></p>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let allServices = { lab: [], radiology: [], other: [] };
        let currentTab = 'lab';

        $(document).ready(function() {
            fetchServices();
            $('#create-service-form').submit(function(e) { e.preventDefault(); insertService(); });
            $('#edit-service-form').submit(function(e) { e.preventDefault(); updateService(); });
        });

        function fetchServices() {
            $('#loading-state').removeClass('hidden');
            $.ajax({
                url: '../api/laboratory/services',
                method: 'GET',
                success: function(response) {
                    $('#loading-state').addClass('hidden');
                    if (response.success) {
                        allServices = response.data;
                        renderAll();
                        updateStats();
                    }
                }
            });
        }

        function renderAll() {
            renderTable('lab', 'lab-tbody');
            renderTable('radiology', 'radiology-tbody');
            renderTable('other', 'other-tbody');
        }

        function renderTable(type, tbodyId) {
            const tbody = $(`#${tbodyId}`);
            tbody.empty();
            let rowsHtml = '';
            allServices[type].forEach((item, index) => {
                let row = `<tr class="searchable">
                    <td class="text-slate-400 font-bold">${index + 1}</td>
                    <td><span class="px-2 py-1 bg-slate-100 rounded text-xs font-bold text-slate-600">${item.service_id}</span></td>
                    <td class="font-bold text-slate-900">${item.test_name || item.billing_name}</td>`;
                
                if (type === 'radiology') row += `<td><span class="px-2 py-1 rounded text-[10px] font-black uppercase tracking-widest" style="background: #e2f2eb; color: var(--gm-accent);">${item.modality_name}</span></td>`;
                
                if (type === 'lab') {
                    row += `<td class="text-right price-tag">₹${parseFloat(item.opd_rate).toFixed(2)}</td>
                           <td class="text-right price-tag">₹${parseFloat(item.gw_rate).toFixed(2)}</td>
                           <td class="text-right price-tag">₹${parseFloat(item.spvt_rate).toFixed(2)}</td>
                           <td class="text-right price-tag">₹${parseFloat(item.pvt_ccu_rate).toFixed(2)}</td>
                           <td class="text-right price-tag">₹${parseFloat(item.suite_rate).toFixed(2)}</td>`;
                } else if (type === 'radiology') {
                    row += `<td class="text-right price-tag">₹${parseFloat(item.opd_price).toFixed(2)}</td>
                           <td class="text-right price-tag">₹${parseFloat(item.general_ward_price).toFixed(2)}</td>
                           <td class="text-right price-tag">₹${parseFloat(item.semi_private_price).toFixed(2)}</td>
                           <td class="text-right price-tag">₹${parseFloat(item.private_icu_price).toFixed(2)}</td>
                           <td class="text-right price-tag">₹${parseFloat(item.suite_price).toFixed(2)}</td>`;
                } else {
                    row += `<td class="text-right price-tag">₹${parseFloat(item.op_gw_price).toFixed(2)}</td>
                           <td class="text-right price-tag">₹${parseFloat(item.semi_private_price).toFixed(2)}</td>
                           <td class="text-right price-tag">₹${parseFloat(item.private_icu_price).toFixed(2)}</td>
                           <td class="text-right price-tag">₹${parseFloat(item.suite_price).toFixed(2)}</td>`;
                }

                row += `<td class="text-center">
                    <div class="flex items-center justify-center gap-2">
                        <button onclick="openEditModal('${type}', '${item.service_id}')" class="h-9 w-9 bg-slate-50 text-slate-400 hover:text-white rounded-xl transition-all shadow-sm" onmouseover="this.style.background='var(--gm-accent)'" onmouseout="this.style.background=''">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteService('${type}', '${item.service_id}')" class="h-9 w-9 bg-slate-50 text-slate-400 hover:bg-red-600 hover:text-white rounded-xl transition-all shadow-sm">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </td></tr>`;
                rowsHtml += row;
            });
            tbody.html(rowsHtml);
        }

        function switchTab(tab, el) {
            currentTab = tab;
            $('.service-tab').removeClass('active');
            $(el).addClass('active');
            $('.tab-content').addClass('hidden');
            $(`#tab-${tab}`).removeClass('hidden');
        }

        function openCreateModal() { renderCreateFields(); $('#create-modal').removeClass('hidden'); }
        function closeCreateModal() { $('#create-modal').addClass('hidden'); }

        function renderCreateFields() {
            const category = $('#create-service-category').val();
            const fields = $('#create-dynamic-fields');
            fields.empty();
            
            // Common Field
            fields.append(renderFormField('Service ID', 'service_id', '', 'text', 'md:col-span-1'));
            
            if (category === 'lab') {
                fields.append(renderFormField('Test Name', 'test_name', '', 'text', 'md:col-span-1'));
                fields.append(renderFormField('OPD Rate', 'opd_rate', '0', 'number'));
                fields.append(renderFormField('GW Rate', 'gw_rate', '0', 'number'));
                fields.append(renderFormField('SPVT Rate', 'spvt_rate', '0', 'number'));
                fields.append(renderFormField('PVT/CCU Rate', 'pvt_ccu_rate', '0', 'number'));
                fields.append(renderFormField('Suite Rate', 'suite_rate', '0', 'number'));
            } else if (category === 'radiology') {
                fields.append(renderFormField('Billing Name', 'billing_name', '', 'text', 'md:col-span-1'));
                fields.append(renderFormField('Modality', 'modality_name', '', 'text'));
                fields.append(renderFormField('OPD Price', 'opd_price', '0', 'number'));
                fields.append(renderFormField('General Ward', 'general_ward_price', '0', 'number'));
                fields.append(renderFormField('Semi Private', 'semi_private_price', '0', 'number'));
                fields.append(renderFormField('Private / ICU', 'private_icu_price', '0', 'number'));
                fields.append(renderFormField('Suite Price', 'suite_price', '0', 'number'));
            } else {
                fields.append(renderFormField('Billing Name', 'billing_name', '', 'text', 'md:col-span-1'));
                fields.append(renderFormField('OP/GW Price', 'op_gw_price', '0', 'number'));
                fields.append(renderFormField('Semi Private', 'semi_private_price', '0', 'number'));
                fields.append(renderFormField('Private / ICU', 'private_icu_price', '0', 'number'));
                fields.append(renderFormField('Suite Price', 'suite_price', '0', 'number'));
            }
        }

        function openEditModal(type, id) {
            const item = allServices[type].find(i => i.service_id === id);
            if(!item) return;

            $('#edit-service-type').val(type);
            $('#edit-service-id').val(id);
            const fields = $('#edit-dynamic-fields');
            fields.empty();

            if (type === 'lab') {
                fields.append(renderFormField('Test Name', 'test_name', item.test_name, 'text', 'md:col-span-2'));
                fields.append(renderFormField('OPD Rate', 'opd_rate', item.opd_rate, 'number'));
                fields.append(renderFormField('GW Rate', 'gw_rate', item.gw_rate, 'number'));
                fields.append(renderFormField('SPVT Rate', 'spvt_rate', item.spvt_rate, 'number'));
                fields.append(renderFormField('PVT/CCU Rate', 'pvt_ccu_rate', item.pvt_ccu_rate, 'number'));
                fields.append(renderFormField('Suite Rate', 'suite_rate', item.suite_rate, 'number'));
            } else if (type === 'radiology') {
                fields.append(renderFormField('Billing Name', 'billing_name', item.billing_name, 'text', 'md:col-span-2'));
                fields.append(renderFormField('Modality', 'modality_name', item.modality_name, 'text'));
                fields.append(renderFormField('OPD Price', 'opd_price', item.opd_price, 'number'));
                fields.append(renderFormField('General Ward', 'general_ward_price', item.general_ward_price, 'number'));
                fields.append(renderFormField('Semi Private', 'semi_private_price', item.semi_private_price, 'number'));
                fields.append(renderFormField('Private / ICU', 'private_icu_price', item.private_icu_price, 'number'));
                fields.append(renderFormField('Suite Price', 'suite_price', item.suite_price, 'number'));
            } else {
                fields.append(renderFormField('Billing Name', 'billing_name', item.billing_name, 'text', 'md:col-span-2'));
                fields.append(renderFormField('OP/GW Price', 'op_gw_price', item.op_gw_price, 'number'));
                fields.append(renderFormField('Semi Private', 'semi_private_price', item.semi_private_price, 'number'));
                fields.append(renderFormField('Private / ICU', 'private_icu_price', item.private_icu_price, 'number'));
                fields.append(renderFormField('Suite Price', 'suite_price', item.suite_price, 'number'));
            }
            $('#edit-modal').removeClass('hidden');
        }

        function closeEditModal() { $('#edit-modal').addClass('hidden'); }

        function renderFormField(label, name, value, type='text', className='') {
            return `
                <div class="${className} space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">${label}</label>
                    <input type="${type}" name="${name}" value="${value}" 
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all font-bold text-slate-900 shadow-sm">
                </div>
            `;
        }

        function showToast(message, type = 'success') {
            const container = $('#toast-container');
            const msgBox = $('#toast-message');
            const iconBox = $('#toast-icon-container');
            const title = $('#toast-title');
            const text = $('#toast-text');
            const isSuccess = type === 'success';

            // Theme Alignment
            if (isSuccess) {
                iconBox.html('<i class="fas fa-check-circle"></i>')
                       .removeClass('bg-red-100 text-red-600').addClass('bg-slate-100 text-slate-900');
                title.text('Success!').removeClass('text-red-600').addClass('text-slate-900');
            } else {
                iconBox.html('<i class="fas fa-exclamation-triangle"></i>')
                       .removeClass('bg-blue-100 text-blue-600').addClass('bg-red-100 text-red-600');
                title.text('Oops!').addClass('text-red-600');
            }

            text.text(message);
            container.removeClass('hidden');
            
            // Trigger animation
            setTimeout(() => {
                msgBox.removeClass('scale-50 opacity-0').addClass('scale-100 opacity-100');
            }, 50);

            // Auto-hide
            setTimeout(() => {
                msgBox.removeClass('scale-100 opacity-100').addClass('scale-50 opacity-0');
                setTimeout(() => container.addClass('hidden'), 500);
            }, 3000);
        }

        function insertService() {
            const formData = {};
            $('#create-service-form').serializeArray().forEach(item => formData[item.name] = item.value);
            
            $.ajax({
                url: '../api/laboratory/services',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function(response) {
                    if (response.success) {
                        showToast('Service inserted successfully!');
                        closeCreateModal();
                        fetchServices();
                    }
                },
                error: function(xhr) {
                    const err = xhr.responseJSON ? xhr.responseJSON.error : 'Failed to insert service';
                    showToast(err, 'error');
                }
            });
        }

        function updateService() {
            const formData = {};
            $('#edit-service-form').serializeArray().forEach(item => formData[item.name] = item.value);
            const type = $('#edit-service-type').val();
            const id = $('#edit-service-id').val();
            delete formData.service_type;
            delete formData.service_id;

            $.ajax({
                url: `../api/laboratory/services/${type}/${id}`,
                method: 'PUT',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function(response) {
                    if (response.success) {
                        showToast('Service updated successfully!');
                        closeEditModal();
                        fetchServices();
                    }
                },
                error: function(xhr) {
                    const err = xhr.responseJSON ? xhr.responseJSON.error : 'Failed to update service';
                    showToast(err, 'error');
                }
            });
        }

        function deleteService(type, id) {
            if (!confirm(`Are you sure you want to delete this ${type} service?`)) return;

            $.ajax({
                url: `../api/laboratory/services/${type}/${id}`,
                method: 'DELETE',
                success: function(response) {
                    if (response.success) {
                        showToast('Service deleted successfully!');
                        fetchServices();
                    }
                },
                error: function(xhr) {
                    const err = xhr.responseJSON ? xhr.responseJSON.error : 'Failed to delete service';
                    showToast(err, 'error');
                }
            });
        }

        function filterServices() {
            const q = $('#search-services').val().toLowerCase();
            $(`#tab-${currentTab} .searchable`).each(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(q) > -1);
            });
        }

        function updateStats() {
            $('#stat-lab-count').text(allServices.lab.length);
            $('#stat-radiology-count').text(allServices.radiology.length);
            $('#stat-other-count').text(allServices.other.length);
        }
    </script>
</body>
</html>
