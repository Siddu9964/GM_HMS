<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - GM Hospital</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Common Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin_common.css">
    
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            /* Inherit global theme */
            min-height: 100vh;
        }
        
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
            box-shadow: 0 8px 16px rgba(245, 158, 11, 0.3);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 24px;
            width: 90%;
            max-width: 1200px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .form-grid {
            display: grid;
            gap: 24px;
            grid-template-columns: repeat(2, 1fr);
        }
        
        .input-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .input-group label {
            font-weight: 500;
            font-size: 14px;
        }
        
        .input-group input,
        .input-group select,
        .input-group textarea {
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 14px;
        }
        
        .input-group input:focus,
        .input-group select:focus,
        .input-group textarea:focus {
            outline: none;
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }

        /* Card Section Styling */
        .section-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            transition: all 0.3s ease;
        }

        .section-card:hover {
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
            border-color: #f59e0b;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e2e8f0;
        }

        .section-title i {
            color: #d97706;
            font-size: 1.2rem;
        }

        .section-title h3 {
            font-size: 1.1rem;
            font-weight: 800;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .tab-btn.active { color: #1f6b4a; border-color: #1f6b4a; background: #E6FAFA; }
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
        
        /* Premium Centered Toast */
        .toast-box {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.9);
            background: white;
            padding: 32px 48px;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            z-index: 2500;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            text-align: center;
            min-width: 320px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .toast-box.active {
            opacity: 1;
            visibility: visible;
            transform: translate(-50%, -50%) scale(1);
        }

        .toast-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
            position: relative;
        }

        .toast-success .toast-icon {
            background: #ecfdf5;
            color: #10b981;
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.2);
        }

        .toast-error .toast-icon {
            background: #fef2f2;
            color: #ef4444;
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.2);
        }

        .toast-title {
            font-size: 24px;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .toast-message {
            color: #64748b;
            font-weight: 500;
        }

        .total-card-premium {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 20px;
            border-radius: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
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
                            <h1 class="text-3xl font-bold text-gray-800">Staff Management</h1>
                            <p class="text-gray-600 mt-1">Manage nurses and hospital staff</p>
                        </div>
                        <button onclick="openAddStaffModal()" class="btn btn-primary">
                            <i class="fas fa-user-nurse"></i>
                            Add Staff
                        </button>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <div class="bento-card">
                        <div class="bento-title">Total Staff</div>
                        <h2 id="card-total-staff" class="bento-value">0</h2>
                        <i class="fas fa-users-cog bento-icon"></i>
                    </div>
                    <div class="bento-card">
                        <div class="bento-title">Active Staff</div>
                        <h2 id="card-active-staff" class="bento-value">0</h2>
                        <i class="fas fa-user-check bento-icon"></i>
                    </div>
                    <div class="bento-card">
                        <div class="bento-title">On Duty Today</div>
                        <h2 id="card-duty-staff" class="bento-value">0</h2>
                        <i class="fas fa-briefcase bento-icon"></i>
                    </div>
                </div>
                
                <!-- Main Content -->
                <div class="table-container">
                    <!-- Top Action Bar -->
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex gap-4 action-bar-flex filter-group">
                            <input type="text" id="searchInput" placeholder="Search staff..." 
                                   class="px-4 py-2 border-2 border-gray-200 rounded-lg focus:border-amber-500 focus:outline-none flex-1">
                            
                            <select id="roleFilter" class="px-4 py-2 border-2 border-gray-200 rounded-lg focus:border-amber-500 focus:outline-none">
                                <option value="">All Roles</option>
                                <option value="Nurse">Nurse</option>
                                <option value="Receptionist">Receptionist</option>
                                <option value="Technician">Technician</option>
                                <option value="Billing">Billing</option>
                            </select>
                            
                            <select id="statusFilter" class="px-4 py-2 border-2 border-gray-200 rounded-lg focus:border-amber-500 focus:outline-none">
                                <option value="">All Status</option>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Staff Table -->
                    <div class="overflow-x-auto">
                        <table>
                            <thead>
                                <tr>
                                    <th>Sl No</th>
                                    <th>Full Name</th>
                                    <th>Designation</th>
                                    <th>Gender</th>
                                    <th>Mobile</th>
                                    <th>Email</th>
                                    <th>Shift</th>
                                    <th>Joining Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="staffTableBody">
                                <!-- Data will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="p-6 border-t border-gray-200 flex justify-between items-center">
                        <div class="text-sm text-gray-600">
                            Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalStaff">0</span> staff
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
    
    <!-- Staff Modal -->
    <div id="staffModal" class="modal">
        <div class="modal-content">
            <div class="p-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800" id="modalTitle">Add New Staff</h2>
                    <button onclick="closeStaffModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
                
                <form id="staffForm" onsubmit="handleFormSubmit(event)">
                    <input type="hidden" id="editStaffId" name="sl_no">
                    
                    <!-- Personal Information -->
                    <div class="section-card">
                        <div class="section-title">
                            <i class="fas fa-user-circle"></i>
                            <h3>Personal Information</h3>
                        </div>
                        <div class="form-grid">
                            <div class="input-group">
                                <label>First Name <span style="color: #ef4444;">*</span></label>
                                <input type="text" name="first_name" required placeholder="Staff first name">
                            </div>
                            <div class="input-group">
                                <label>Last Name <span style="color: #ef4444;">*</span></label>
                                <input type="text" name="last_name" required placeholder="Staff last name">
                            </div>
                            <div class="input-group">
                                <label>Gender <span style="color: #ef4444;">*</span></label>
                                <select name="gender" required>
                                    <option value="">Select</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="input-group">
                                <label>Date of Birth</label>
                                <input type="date" name="date_of_birth" onchange="calculateAge()">
                            </div>
                            <div class="input-group">
                                <label>Age</label>
                                <input type="number" name="age" readonly placeholder="Auto-calculated">
                            </div>
                            <div class="input-group">
                                <label>Blood Group</label>
                                <select name="blood_group">
                                    <option value="">Select</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                </select>
                            </div>
                            <div class="input-group">
                                <label>Marital Status</label>
                                <select name="marital_status">
                                    <option value="">Select</option>
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Divorced">Divorced</option>
                                    <option value="Widowed">Widowed</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contact Information -->
                    <div class="section-card">
                        <div class="section-title">
                            <i class="fas fa-address-book"></i>
                            <h3>Contact Information</h3>
                        </div>
                        <div class="form-grid">
                            <div class="input-group">
                                <label>Mobile Number <span style="color: #ef4444;">*</span></label>
                                <input type="tel" name="mobile_number" required placeholder="+91 00000 00000">
                            </div>
                            <div class="input-group">
                                <label>Alternate Mobile</label>
                                <input type="tel" name="alternate_mobile">
                            </div>
                            <div class="input-group">
                                <label>Email <span style="color: #ef4444;">*</span></label>
                                <input type="email" name="email" required placeholder="staff@gmhospital.com">
                            </div>
                            <div class="input-group">
                                <label>City</label>
                                <input type="text" name="city">
                            </div>
                            <div class="input-group">
                                <label>State</label>
                                <input type="text" name="state">
                            </div>
                            <div class="input-group">
                                <label>Country</label>
                                <input type="text" name="country">
                            </div>
                            <div class="input-group">
                                <label>Pincode</label>
                                <input type="text" name="pincode">
                            </div>
                        </div>
                        <div class="input-group mt-4">
                            <label>Address</label>
                            <textarea name="address" rows="2" placeholder="Full residential address"></textarea>
                        </div>
                    </div>
                    
                    <!-- Professional & Employment -->
                    <div class="section-card">
                        <div class="section-title">
                            <i class="fas fa-briefcase-medical"></i>
                            <h3>Professional & Employment</h3>
                        </div>
                        <div class="form-grid">
                            <div class="input-group">
                                <label>Designation <span style="color: #ef4444;">*</span></label>
                                <input type="text" name="designation" required placeholder="e.g. Senior Nurse">
                            </div>
                            <div class="input-group">
                                <label>Qualification</label>
                                <input type="text" name="qualification">
                            </div>
                            <div class="input-group">
                                <label>Experience (Years)</label>
                                <input type="number" name="experience_years">
                            </div>
                            <div class="input-group">
                                <label>Previous Organization</label>
                                <input type="text" name="previous_organization">
                            </div>
                            <div class="input-group">
                                <label>Employment Type</label>
                                <select name="employment_type">
                                    <option value="">Select</option>
                                    <option value="Full-time">Full-time</option>
                                    <option value="Part-time">Part-time</option>
                                    <option value="Contract">Contract</option>
                                </select>
                            </div>
                            <div class="input-group">
                                <label>Joining Date</label>
                                <input type="date" name="joining_date">
                            </div>
                            <div class="input-group">
                                <label>Shift Type</label>
                                <select name="shift_type">
                                    <option value="">Select</option>
                                    <option value="Morning">Morning</option>
                                    <option value="Evening">Evening</option>
                                    <option value="Night">Night</option>
                                    <option value="Rotational">Rotational</option>
                                </select>
                            </div>
                            <div class="input-group">
                                <label>Working Hours</label>
                                <input type="text" name="working_hours" placeholder="e.g., 9 AM - 5 PM">
                            </div>
                            <div class="input-group">
                                <label>Salary</label>
                                <input type="number" name="salary" step="0.01">
                            </div>
                            <div class="input-group">
                                <label>Status</label>
                                <select name="status">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bank & Identity -->
                    <div class="section-card">
                        <div class="section-title">
                            <i class="fas fa-id-card-clip"></i>
                            <h3>Bank & Identity</h3>
                        </div>
                        <div class="form-grid">
                            <div class="input-group">
                                <label>Bank Name</label>
                                <input type="text" name="bank_name">
                            </div>
                            <div class="input-group">
                                <label>Account Number</label>
                                <input type="text" name="bank_account_number">
                            </div>
                            <div class="input-group">
                                <label>IFSC Code</label>
                                <input type="text" name="ifsc_code">
                            </div>
                            <div class="input-group">
                                <label>ID Proof Type</label>
                                <select name="id_proof_type">
                                    <option value="">Select</option>
                                    <option value="Aadhar Card">Aadhar Card</option>
                                    <option value="PAN Card">PAN Card</option>
                                    <option value="Driving License">Driving License</option>
                                    <option value="Passport">Passport</option>
                                </select>
                            </div>
                            <div class="input-group">
                                <label>ID Number</label>
                                <input type="text" name="id_proof_number">
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Credentials -->
                    <div class="section-card" style="background: #fffbeb; border-color: #fde68a;">
                        <div class="section-title">
                            <i class="fas fa-shield-halved"></i>
                            <h3>System Credentials</h3>
                        </div>
                        <div class="form-grid">
                            <div class="input-group">
                                <label>Access Role</label>
                                <select name="role" id="accessRoleSelect">
                                    <option value="">Select Access Role</option>
                                    <!-- Options will be populated dynamically from designations -->
                                </select>
                            </div>
                            <div class="input-group">
                                <label>Username <span style="color: #ef4444;">*</span></label>
                                <input type="text" name="username" required placeholder="Login username">
                            </div>
                            <div class="input-group">
                                <label>Password <span style="color: #ef4444;" id="password-required-star">*</span></label>
                                <input type="text" name="password" id="staffPassword" placeholder="Login password">
                                <small id="password-hint" class="text-slate-400 hidden">Leave blank to keep current password</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-4">
                        <button type="button" onclick="closeStaffModal()" class="px-6 py-3 border-2 border-gray-300 rounded-lg font-semibold hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" id="submitBtn" class="btn btn-primary">
                            <span id="submitBtnText">Save Staff</span>
                            <i id="submitLoader" class="fas fa-spinner fa-spin hidden"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        let allStaff = [];
        let filteredStaff = [];
        let currentPage = 1;
        let pageSize = 10;
        let isEditMode = false;
        
        window.addEventListener('DOMContentLoaded', () => {
            loadDesignations();
            loadStaff();
        });
        
        async function loadDesignations() {
            try {
                const response = await fetch('/GM_HMS/api/staff/designations');
                const result = await response.json();
                if (result.success) {
                    const roleSelect = document.getElementById('accessRoleSelect');
                    const options = ['<option value="">Select Access Role</option>'];
                    // Default generic role fallback
                    options.push('<option value="Staff">Staff</option>');
                    result.data.forEach(d => {
                        // Don't duplicate staff if it's already returned
                        if (d.toLowerCase() !== 'staff') {
                            options.push(`<option value="${d}">${d}</option>`);
                        }
                    });
                    roleSelect.innerHTML = options.join('');
                }
            } catch (error) {
                console.error('Error loading designations:', error);
            }
        }
        
        async function loadStaff() {
            try {
                const response = await fetch('/GM_HMS/api/staff');
                const result = await response.json();
                
                if (result.success) {
                    allStaff = result.data;
                    filteredStaff = allStaff;
                    renderTable();
                    updateKPIs();
                }
            } catch (error) {
                console.error('Error loading staff:', error);
                showToast('Failed to load staff', 'error');
            }
        }
        
        // Update KPIs
        function updateKPIs() {
            document.getElementById('card-total-staff').textContent = allStaff.length;
            document.getElementById('card-active-staff').textContent = allStaff.filter(s => s.status === 'Active').length;
            document.getElementById('card-duty-staff').textContent = allStaff.filter(s => s.status === 'Active').length; // Simplified for now
        }

        function renderTable() {
            const tbody = document.getElementById('staffTableBody');
            const start = (currentPage - 1) * pageSize;
            const end = start + pageSize;
            const paginatedStaff = filteredStaff.slice(start, end);
            
            if (paginatedStaff.length === 0) {
                tbody.innerHTML = '<tr><td colspan="10" class="text-center py-8 text-gray-500">No staff found</td></tr>';
                return;
            }
            
            tbody.innerHTML = paginatedStaff.map((staff, index) => `
                <tr>
                    <td>${start + index + 1}</td>
                    <td class="font-semibold">${staff.full_name || staff.first_name + ' ' + staff.last_name}</td>
                    <td>${staff.designation}</td>
                    <td>${staff.gender}</td>
                    <td>${staff.mobile_number}</td>
                    <td>${staff.email}</td>
                    <td>${staff.shift_type || '-'}</td>
                    <td>${staff.joining_date || '-'}</td>
                    <td><span class="status-${staff.status?.toLowerCase() || 'active'}">${staff.status || 'Active'}</span></td>
                    <td>
                        <div style="display: flex; gap: 12px;">
                            <i class="fas fa-edit action-icon edit" onclick="editStaff(${staff.sl_no})" title="Edit"></i>
                            <i class="fas fa-trash action-icon delete" onclick="deleteStaff(${staff.sl_no})" title="Delete"></i>
                        </div>
                    </td>
                </tr>
            `).join('');
            
            updatePagination();
        }
        
        function updatePagination() {
            const start = (currentPage - 1) * pageSize + 1;
            const end = Math.min(currentPage * pageSize, filteredStaff.length);
            
            document.getElementById('showingFrom').textContent = start;
            document.getElementById('showingTo').textContent = end;
            document.getElementById('totalStaff').textContent = filteredStaff.length;
            
            document.getElementById('prevBtn').disabled = currentPage === 1;
            document.getElementById('nextBtn').disabled = end >= filteredStaff.length;
        }
        
        function previousPage() {
            if (currentPage > 1) {
                currentPage--;
                renderTable();
            }
        }
        
        function nextPage() {
            if (currentPage * pageSize < filteredStaff.length) {
                currentPage++;
                renderTable();
            }
        }
        
        function openAddStaffModal() {
            isEditMode = false;
            document.getElementById('modalTitle').textContent = 'Add New Staff';
            document.getElementById('submitBtnText').textContent = 'Save Staff';
            document.getElementById('staffForm').reset();
            
            // Password logic for add
            const passwordInput = document.getElementById('staffPassword');
            passwordInput.required = true;
            document.getElementById('password-required-star').style.display = 'inline';
            document.getElementById('password-hint').classList.add('hidden');
            
            document.getElementById('staffModal').classList.add('active');
        }
        
        function closeStaffModal() {
            document.getElementById('staffModal').classList.remove('active');
            document.getElementById('staffForm').reset();
        }
        
        function calculateAge() {
            const dobInput = document.querySelector('input[name="date_of_birth"]');
            const ageInput = document.querySelector('input[name="age"]');
            
            if (dobInput.value) {
                const dob = new Date(dobInput.value);
                const today = new Date();
                let age = today.getFullYear() - dob.getFullYear();
                const monthDiff = today.getMonth() - dob.getMonth();
                
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }
                
                ageInput.value = age;
            }
        }
        
        async function editStaff(staffId) {
            try {
                const response = await fetch(`/GM_HMS/api/staff/${staffId}`);
                const result = await response.json();
                
                if (result.success) {
                    isEditMode = true;
                    const staff = result.data;
                    
                    document.getElementById('modalTitle').textContent = 'Edit Staff';
                    document.getElementById('submitBtnText').textContent = 'Update Staff';
                    document.getElementById('editStaffId').value = staff.sl_no;
                    
                    // Password logic for edit
                    const passwordInput = document.getElementById('staffPassword');
                    passwordInput.required = false;
                    document.getElementById('password-required-star').style.display = 'none';
                    document.getElementById('password-hint').classList.remove('hidden');
                    
                    // File input fields that should be skipped
                    const fileFields = ['photo', 'id_proof', 'resume', 'address_proof'];
                    
                    Object.keys(staff).forEach(key => {
                        const input = document.querySelector(`[name="${key}"]`);
                        // Skip password, file inputs, and non-existent fields
                        if (input && key !== 'password' && !fileFields.includes(key) && input.type !== 'file') {
                            input.value = staff[key] || '';
                        }
                    });
                    
                    document.getElementById('staffModal').classList.add('active');
                }
            } catch (error) {
                console.error('Error loading staff:', error);
                showToast('Failed to load staff details', 'error');
            }
        }
        
        async function deleteStaff(staffId) {
            if (!confirm('Are you sure you want to delete this staff member?')) {
                return;
            }
            
            try {
                const response = await fetch(`/GM_HMS/api/staff/${staffId}`, {
                    method: 'DELETE'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('Staff deleted successfully', 'success');
                    loadStaff();
                } else {
                    showToast(result.error || 'Failed to delete staff', 'error');
                }
            } catch (error) {
                console.error('Error deleting staff:', error);
                showToast('Error deleting staff', 'error');
            }
        }
        
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
            
            // File fields to skip (will be handled separately if needed)
            const fileFields = ['photo', 'id_proof', 'resume', 'id_proof_document'];
            
            formData.forEach((value, key) => {
                // Skip sl_no, empty values, and file inputs
                if (key !== 'sl_no' && value && value.toString().trim() !== '') {
                    // Skip file inputs
                    if (fileFields.includes(key)) {
                        return;
                    }
                    
                    // Skip empty password field when editing
                    if (key === 'password' && isEditMode && !value) {
                        return;
                    }
                    
                    // Convert data types
                    if (key === 'age' || key === 'experience_years' || key === 'role_id') {
                        data[key] = parseInt(value, 10);
                    } else if (key === 'salary') {
                        data[key] = parseFloat(value);
                    } else {
                        data[key] = value;
                    }
                }
            });
            
            try {
                const staffId = document.getElementById('editStaffId').value;
                const url = isEditMode 
                    ? `/GM_HMS/api/staff/${staffId}`
                    : '/GM_HMS/api/staff';
                
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
                    showToast(isEditMode ? 'Staff updated successfully' : 'Staff added successfully!', 'success');
                    closeStaffModal();
                    loadStaff();
                } else {
                    showToast(result.error || 'Operation failed', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error. Please try again.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtnText.textContent = isEditMode ? 'Update Staff' : 'Save Staff';
                submitLoader.classList.add('hidden');
            }
        }
        
        // Show Premium Toast Notification
        function showToast(message, type = 'success') {
            const container = $('#toast-container');
            const toastBox = $('#toast-box');
            const icon = $('#toast-icon');
            const title = $('#toast-title');
            const msg = $('#toast-message');

            // Reset classes
            toastBox.removeClass('toast-success toast-error');
            
            if (type === 'success') {
                toastBox.addClass('toast-success');
                icon.html('<i class="fas fa-check-circle"></i>');
                title.text('Success');
            } else {
                toastBox.addClass('toast-error');
                icon.html('<i class="fas fa-exclamation-circle"></i>');
                title.text('Error');
            }

            msg.text(message);
            container.removeClass('hidden');
            
            // Trigger animation
            setTimeout(() => toastBox.addClass('active'), 10);

            // Auto hide
            setTimeout(() => {
                toastBox.removeClass('active');
                setTimeout(() => container.addClass('hidden'), 500);
            }, 3000);
        }
        
        // Search
        document.getElementById('searchInput').addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            filteredStaff = allStaff.filter(staff => 
                staff.full_name?.toLowerCase().includes(searchTerm) ||
                staff.first_name?.toLowerCase().includes(searchTerm) ||
                staff.last_name?.toLowerCase().includes(searchTerm) ||
                staff.designation?.toLowerCase().includes(searchTerm)
            );
            currentPage = 1;
            renderTable();
        });
        
        // Role filter
        document.getElementById('roleFilter').addEventListener('change', (e) => {
            const role = e.target.value;
            filteredStaff = role ? allStaff.filter(s => s.designation === role) : allStaff;
            currentPage = 1;
            renderTable();
        });
        
        // Status filter
        document.getElementById('statusFilter').addEventListener('change', (e) => {
            const status = e.target.value;
            filteredStaff = status ? allStaff.filter(s => s.status === status) : allStaff;
            currentPage = 1;
            renderTable();
        });
    </script>
    <div id="toast-container" class="hidden">
        <div id="toast-box" class="toast-box">
            <div id="toast-icon" class="toast-icon"></div>
            <div id="toast-title" class="toast-title"></div>
            <div id="toast-message" class="toast-message"></div>
        </div>
    </div>

    <script src="assets/js/admin_common.js"></script>
</body>
</html>
