<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Management - GM Hospital</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Common Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin_common.css">
    
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            min-height: 100vh;
        }
        
        /* Table Styles */
        .table-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #1f6b4a;
            color: white;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
        }
        
        td {
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }
        
        tbody tr:hover {
            background-color: #f9fafb;
        }
        
        /* Button Styles */
        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #1f6b4a;
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(16, 185, 129, 0.3);
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: #f3efe6;
            border-radius: 12px;
            max-width: 1100px;
            width: 95%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
            color: #1f6b4a;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        /* Form Styles */
        .form-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(31, 107, 74, 0.08);
            transition: all 0.2s ease;
        }
        
        .form-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
        }

        .form-section-title {
            font-size: 15px;
            font-weight: 700;
            color: #1f6b4a;
            margin-bottom: 20px;
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid rgba(31, 107, 74, 0.1);
            padding-bottom: 12px;
        }

        .form-grid {
            display: grid;
            gap: 16px 24px;
        }
        
        .form-grid.cols-2 {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .form-grid.cols-3 {
            grid-template-columns: repeat(3, 1fr);
        }
        
        .form-grid.cols-4 {
            grid-template-columns: repeat(4, 1fr);
        }
        
        .input-group {
            margin-bottom: 0;
        }
        
        .input-group label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #1f6b4a;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }
        
        .input-group label .required,
        .required {
            color: #1f6b4a;
            margin-left: 2px;
        }
        
        .input-group input,
        .input-group select,
        .input-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid rgba(31, 107, 74, 0.3);
            border-radius: 6px;
            font-size: 14px;
            background: #ffffff;
            color: #1f6b4a;
            transition: all 0.2s ease;
            outline: none;
        }
        
        .input-group input::placeholder,
        .input-group textarea::placeholder {
            color: rgba(31, 107, 74, 0.5);
        }

        .input-group textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        .input-group input:focus,
        .input-group select:focus,
        .input-group textarea:focus {
            border-color: #1f6b4a;
            box-shadow: 0 0 0 2px rgba(31, 107, 74, 0.1);
        }
        
        /* Buttons */
        .btn-primary {
            background: #1f6b4a;
            color: #ffffff;
            border: 1px solid #1f6b4a;
            padding: 8px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary:hover {
            background: #144d34;
            border-color: #144d34;
        }

        .btn-secondary {
            background: transparent;
            color: #1f6b4a;
            border: 1px solid rgba(31, 107, 74, 0.5);
            padding: 8px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-secondary:hover {
            border-color: #1f6b4a;
            background: rgba(31, 107, 74, 0.05);
        }
        
        /* Modal Header */
        .modal-header {
            padding: 16px 24px;
            border-bottom: 1px solid rgba(31, 107, 74, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-header h2 {
            font-size: 18px;
            font-weight: 700;
            color: #1f6b4a;
            margin: 0;
        }

        .modal-close {
            background: transparent;
            border: none;
            color: #1f6b4a;
            font-size: 20px;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            opacity: 1;
        }

        /* Modal Body */
        .modal-body {
            padding: 0 24px 24px 24px;
        }
        
        /* Status Badge */
        .status-active {
            background: #d1fae5;
            color: #065f46;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        /* Action Icons */
        .action-icon {
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        
        .action-icon:hover {
            background: #f3f4f6;
        }
        
        .action-icon.edit {
            color: #1f6b4a;
        }
        
        .action-icon.delete {
            color: #ef4444;
        }
        
        /* Toast */
        .toast {
            position: fixed;
            top: 24px;
            right: 24px;
            padding: 16px 24px;
            border-radius: 12px;
            color: white;
            font-weight: 500;
            z-index: 2000;
            animation: slideIn 0.3s ease;
        }
        
        .toast.success {
            background: #1f6b4a;
        }
        
        .toast.error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .hidden {
            display: none !important;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .page-header-flex {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
            .action-bar-flex {
                flex-direction: column;
                gap: 12px;
            }
            .filter-group {
                width: 100%;
                flex-direction: column;
            }
            .filter-group input, 
            .filter-group select {
                width: 100%;
            }
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            position: absolute;
            right: -10px;
            bottom: -10px;
            font-size: 80px;
            opacity: 0.1;
            transform: rotate(-15deg);
        }
    </style>
</head>
<body>
    
    <div class="flex h-screen overflow-hidden">
        
        <!-- Include Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            
            <!-- Include Navbar -->
            <?php include 'includes/navbar.php'; ?>
            
            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-8">
                
                <!-- Page Header -->
                <div class="mb-6">
                    <div class="flex items-center justify-between page-header-flex">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800">Department Management</h1>
                            <p class="text-gray-600 mt-1">Manage hospital departments and information</p>
                        </div>
                        <button onclick="openAddDepartmentModal()" class="btn btn-primary">
                            <i class="fas fa-building"></i>
                            Add Department
                        </button>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <div class="bento-card">
                        <div class="bento-title">Total Departments</div>
                        <h2 id="card-total-depts" class="bento-value">0</h2>
                        <i class="fas fa-hospital bento-icon"></i>
                    </div>
                    <div class="bento-card">
                        <div class="bento-title">Active Units</div>
                        <h2 id="card-active-depts" class="bento-value">0</h2>
                        <i class="fas fa-check-circle bento-icon"></i>
                    </div>
                    <div class="bento-card">
                        <div class="bento-title">Clinical Depts</div>
                        <h2 id="card-clinical-depts" class="bento-value">0</h2>
                        <i class="fas fa-stethoscope bento-icon"></i>
                    </div>
                </div>
                
                <!-- Main Content -->
                <div class="table-container">
                    <!-- Top Action Bar -->
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex gap-4 action-bar-flex filter-group">
                            <input type="text" id="searchInput" placeholder="Search departments..." 
                                   class="px-4 py-2 border-2 border-gray-200 rounded-lg focus:border-green-500 focus:outline-none flex-1">
                            
                            <select id="typeFilter" class="px-4 py-2 border-2 border-gray-200 rounded-lg focus:border-green-500 focus:outline-none">
                                <option value="">All Types</option>
                                <option value="Clinical">Clinical</option>
                                <option value="Non-Clinical">Non-Clinical</option>
                                <option value="Support">Support</option>
                            </select>
                            
                            <select id="statusFilter" class="px-4 py-2 border-2 border-gray-200 rounded-lg focus:border-green-500 focus:outline-none">
                                <option value="">All Status</option>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Department Table -->
                    <div class="overflow-x-auto">
                        <table>
                            <thead>
                                <tr>
                                    <th>Sl No</th>
                                    <th>Department ID</th>
                                    <th>Department Name</th>
                                    <th>Type</th>
                                    <th>Floor</th>
                                    <th>Building</th>
                                    <th>Contact</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="departmentsTableBody">
                                <!-- Data will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="p-6 border-t border-gray-200 flex justify-between items-center">
                        <div class="text-sm text-gray-600">
                            Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalDepartments">0</span> departments
                        </div>
                        <div class="flex gap-2">
                            <button id="prevBtn" onclick="previousPage()" class="px-4 py-2 border-2 border-gray-200 rounded-lg hover:bg-gray-50">
                                Previous
                            </button>
                            <button id="nextBtn" onclick="nextPage()" class="px-4 py-2 border-2 border-gray-200 rounded-lg hover:bg-gray-50">
                                Next
                            </button>
                        </div>
                    </div>
                </div>
                
            </main>
        </div>
    </div>
    
    <!-- Department Modal -->
    <div id="departmentModal" class="modal">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Department</h2>
                <button type="button" onclick="closeDepartmentModal()" class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body" style="padding-top: 24px;">
                <form id="departmentForm" onsubmit="handleFormSubmit(event)">
                    <input type="hidden" id="editDepartmentId" name="department_id">
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Left Column -->
                        <div class="flex flex-col gap-6">
                            <!-- General Info Card -->
                            <div class="form-card mb-0">
                                <h3 class="form-section-title"><i class="fas fa-info-circle"></i> General Information</h3>
                                <div class="form-grid cols-2">
                                    <div class="input-group">
                                        <label>Department Name <span class="required">*</span></label>
                                        <input type="text" name="department_name" required>
                                    </div>
                                    <div class="input-group">
                                        <label>Department Type <span class="required">*</span></label>
                                        <select name="department_type" required>
                                            <option value="">Select</option>
                                            <option value="Clinical">Clinical</option>
                                            <option value="Non-Clinical">Non-Clinical</option>
                                            <option value="Support">Support</option>
                                        </select>
                                    </div>
                                    <div class="input-group">
                                        <label>Status</label>
                                        <select name="status">
                                            <option value="Active">Active</option>
                                            <option value="Inactive">Inactive</option>
                                        </select>
                                    </div>
                                    <div class="input-group">
                                        <label>Head Doctor ID</label>
                                        <input type="number" name="head_doctor_id">
                                    </div>
                                    <div class="input-group" style="grid-column: span 2;">
                                        <label>Description</label>
                                        <textarea name="description" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="flex flex-col gap-6">
                            <!-- Location Details Card -->
                            <div class="form-card mb-0">
                                <h3 class="form-section-title"><i class="fas fa-map-marker-alt"></i> Location Details</h3>
                                <div class="form-grid cols-2">
                                    <div class="input-group">
                                        <label>Building Name</label>
                                        <input type="text" name="building_name">
                                    </div>
                                    <div class="input-group">
                                        <label>Floor Number</label>
                                        <input type="number" name="floor_number">
                                    </div>
                                </div>
                            </div>

                            <!-- Contact & Leadership Card -->
                            <div class="form-card mb-0">
                                <h3 class="form-section-title"><i class="fas fa-address-book"></i> Contact Info</h3>
                                <div class="form-grid cols-2">
                                    <div class="input-group">
                                        <label>Contact Number</label>
                                        <input type="tel" name="contact_number">
                                    </div>
                                    <div class="input-group">
                                        <label>Email</label>
                                        <input type="email" name="email">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; border-top: 1px solid rgba(31, 107, 74, 0.1); padding-top: 20px;">
                        <button type="button" onclick="closeDepartmentModal()" class="btn-secondary">
                            Cancel
                        </button>
                        <button type="submit" id="submitBtn" class="btn-primary">
                            <i class="fas fa-save"></i> <span id="submitBtnText">Commit Changes</span>
                            <i id="submitLoader" class="fas fa-spinner fa-spin hidden"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        let allDepartments = [];
        let filteredDepartments = [];
        let currentPage = 1;
        let pageSize = 10;
        let isEditMode = false;
        
        // Load departments on page load
        window.addEventListener('DOMContentLoaded', () => {
            loadDepartments();
        });
        
        // Load all departments
        async function loadDepartments() {
            try {
                const response = await fetch('/GM_HMS/api/departments');
                const result = await response.json();
                
                if (result.success) {
                    allDepartments = result.data;
                    filteredDepartments = allDepartments;
                    renderTable();
                    updateKPIs();
                }
            } catch (error) {
                console.error('Error loading departments:', error);
                showToast('Failed to load departments', 'error');
            }
        }
        
        // Update KPIs
        function updateKPIs() {
            document.getElementById('card-total-depts').textContent = allDepartments.length;
            document.getElementById('card-active-depts').textContent = allDepartments.filter(d => d.status === 'Active').length;
            document.getElementById('card-clinical-depts').textContent = allDepartments.filter(d => d.department_type === 'Clinical').length;
        }

        // Render table
        function renderTable() {
            const tbody = document.getElementById('departmentsTableBody');
            const start = (currentPage - 1) * pageSize;
            const end = start + pageSize;
            const paginatedDepartments = filteredDepartments.slice(start, end);
            
            if (paginatedDepartments.length === 0) {
                tbody.innerHTML = '<tr><td colspan="10" class="text-center py-8 text-gray-500">No departments found</td></tr>';
                return;
            }
            
            tbody.innerHTML = paginatedDepartments.map(dept => `
                <tr>
                    <td>${dept.sl_no}</td>
                    <td class="font-semibold">${dept.department_id}</td>
                    <td>${dept.department_name}</td>
                    <td>${dept.department_type}</td>
                    <td>${dept.floor_number || '-'}</td>
                    <td>${dept.building_name || '-'}</td>
                    <td>${dept.contact_number || '-'}</td>
                    <td>${dept.email || '-'}</td>
                    <td><span class="status-${dept.status?.toLowerCase() || 'active'}">${dept.status || 'Active'}</span></td>
                    <td>
                        <div style="display: flex; gap: 12px;">
                            <i class="fas fa-edit action-icon edit" onclick="editDepartment('${dept.department_id}')" title="Edit"></i>
                            <i class="fas fa-trash action-icon delete" onclick="deleteDepartment('${dept.department_id}')" title="Delete"></i>
                        </div>
                    </td>
                </tr>
            `).join('');
            
            updatePagination();
        }
        
        // Update pagination
        function updatePagination() {
            const start = (currentPage - 1) * pageSize + 1;
            const end = Math.min(currentPage * pageSize, filteredDepartments.length);
            
            document.getElementById('showingFrom').textContent = start;
            document.getElementById('showingTo').textContent = end;
            document.getElementById('totalDepartments').textContent = filteredDepartments.length;
            
            document.getElementById('prevBtn').disabled = currentPage === 1;
            document.getElementById('nextBtn').disabled = end >= filteredDepartments.length;
        }
        
        // Pagination
        function previousPage() {
            if (currentPage > 1) {
                currentPage--;
                renderTable();
            }
        }
        
        function nextPage() {
            if (currentPage * pageSize < filteredDepartments.length) {
                currentPage++;
                renderTable();
            }
        }
        
        // Open add modal
        function openAddDepartmentModal() {
            isEditMode = false;
            document.getElementById('modalTitle').textContent = 'Add New Department';
            document.getElementById('submitBtnText').textContent = 'Save Department';
            document.getElementById('departmentForm').reset();
            document.getElementById('departmentModal').classList.add('active');
        }
        
        // Close modal
        function closeDepartmentModal() {
            document.getElementById('departmentModal').classList.remove('active');
            document.getElementById('departmentForm').reset();
        }
        
        // Edit department
        async function editDepartment(departmentId) {
            try {
                const response = await fetch(`/GM_HMS/api/departments/${departmentId}`);
                const result = await response.json();
                
                if (result.success) {
                    isEditMode = true;
                    const dept = result.data;
                    
                    document.getElementById('modalTitle').textContent = 'Edit Department';
                    document.getElementById('submitBtnText').textContent = 'Update Department';
                    document.getElementById('editDepartmentId').value = dept.department_id;
                    
                    Object.keys(dept).forEach(key => {
                        const input = document.querySelector(`[name="${key}"]`);
                        if (input) {
                            input.value = dept[key] || '';
                        }
                    });
                    
                    document.getElementById('departmentModal').classList.add('active');
                }
            } catch (error) {
                console.error('Error loading department:', error);
                showToast('Failed to load department details', 'error');
            }
        }
        
        // Delete department
        async function deleteDepartment(departmentId) {
            if (!confirm('Are you sure you want to delete this department?')) {
                return;
            }
            
            try {
                const response = await fetch(`/GM_HMS/api/departments/${departmentId}`, {
                    method: 'DELETE'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('Department deleted successfully', 'success');
                    loadDepartments();
                } else {
                    showToast(result.error || 'Failed to delete department', 'error');
                }
            } catch (error) {
                console.error('Error deleting department:', error);
                showToast('Error deleting department', 'error');
            }
        }
        
        // Handle form submit
        async function handleFormSubmit(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const submitBtnText = document.getElementById('submitBtnText');
            const submitLoader = document.getElementById('submitLoader');
            
            submitBtn.disabled = true;
            submitBtnText.textContent = isEditMode ? 'Updating...' : 'Saving...';
            submitLoader.classList.remove('hidden');
            
            const formData = new FormData(e.target);
            const data = {};
            
            formData.forEach((value, key) => {
                if (key !== 'department_id' && value) {
                    if (key === 'floor_number' || key === 'head_doctor_id') {
                        data[key] = parseInt(value, 10);
                    } else {
                        data[key] = value;
                    }
                }
            });
            
            try {
                const departmentId = document.getElementById('editDepartmentId').value;
                const url = isEditMode 
                    ? `/GM_HMS/api/departments/${departmentId}`
                    : '/GM_HMS/api/departments';
                
                const method = isEditMode ? 'PUT' : 'POST';
                
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast(isEditMode ? 'Department updated successfully' : `Department created successfully! ID: ${result.data.department_id}`, 'success');
                    closeDepartmentModal();
                    loadDepartments();
                } else {
                    showToast(result.error || 'Operation failed', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error. Please try again.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtnText.textContent = isEditMode ? 'Update Department' : 'Save Department';
                submitLoader.classList.add('hidden');
            }
        }
        
        // Show toast
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 4000);
        }
        
        // Search
        document.getElementById('searchInput').addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            filteredDepartments = allDepartments.filter(dept => 
                dept.department_name?.toLowerCase().includes(searchTerm) ||
                dept.department_id?.toLowerCase().includes(searchTerm)
            );
            currentPage = 1;
            renderTable();
        });
        
        // Type filter
        document.getElementById('typeFilter').addEventListener('change', (e) => {
            const type = e.target.value;
            filteredDepartments = type ? allDepartments.filter(d => d.department_type === type) : allDepartments;
            currentPage = 1;
            renderTable();
        });
        
        // Status filter
        document.getElementById('statusFilter').addEventListener('change', (e) => {
            const status = e.target.value;
            filteredDepartments = status ? allDepartments.filter(d => d.status === status) : allDepartments;
            currentPage = 1;
            renderTable();
        });
    </script>
    <script src="assets/js/admin_common.js"></script>
</body>
</html>
