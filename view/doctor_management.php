<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Management - GM Hospital</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Common Admin CSS -->
    <link rel="stylesheet" href="/GM_HMS/view/assets/css/admin_common.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            /* Inherit global theme */
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
            font-weight: 800;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        
        td {
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
            color: #374151;
        }
        
        tbody tr {
            transition: all 0.2s ease;
        }
        
        tbody tr:hover {
            background-color: #f1f5f9;
        }
        
        /* Button Styles */
        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #1f6b4a;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.1);
        }
        
        .btn-primary:hover {
            background: #1e293b;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.2);
        }
        
        /* Modal Styles */
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
            backdrop-filter: blur(4px);
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
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        /* Form Styles */
        .form-grid {
            display: grid;
            gap: 24px;
        }
        
        .form-grid.cols-2 {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .form-grid.cols-3 {
            grid-template-columns: repeat(3, 1fr);
        }

        /* Card Section Styling */
        .section-card {
            background: #f3efe6;
            border: 1px solid #1f6b4a;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            transition: all 0.3s ease;
        }

        .section-card:hover {
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
            border-color: #cbd5e1;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #1f6b4a;
        }

        .section-title i {
            color: #0f172a;
            font-size: 1.2rem;
        }

        .section-title h3 {
            font-size: 1.1rem;
            font-weight: 800;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .input-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .input-group label {
            font-weight: 500;
            font-size: 14px;
            color: #374151;
        }
        
        .input-group input,
        .input-group select,
        .input-group textarea {
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .input-group input:focus,
        .input-group select:focus,
        .input-group textarea:focus {
            outline: none;
            border-color: #0f172a;
            box-shadow: 0 0 0 4px rgba(15, 23, 42, 0.1);
        }
        
        .required {
            color: #ef4444;
        }
        
        /* Status Badge */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        /* Toast Notification System */
        #toast-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 10000;
            pointer-events: none;
        }

        .toast-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(15, 23, 42, 0.1);
            border-radius: 2rem;
            padding: 2.5rem 3.5rem;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
            text-align: center;
            transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            transform: scale(0.5);
            opacity: 0;
            min-width: 320px;
        }

        .toast-box.active {
            transform: scale(1);
            opacity: 1;
        }

        .toast-icon {
            width: 80px;
            height: 80px;
            border-radius: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .toast-success .toast-icon {
            background: rgba(15, 23, 42, 0.05);
            color: #0f172a;
        }

        .toast-error .toast-icon {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .toast-title {
            font-size: 1.5rem;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.025em;
        }

        .toast-message {
            color: #64748b;
            font-weight: 500;
            line-height: 1.6;
        }

        @keyframes toastProgress {
            from { width: 100%; }
            to { width: 0%; }
        }

        .toast-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 4px;
            background: #0f172a;
            border-radius: 0 0 2rem 2rem;
            animation: toastProgress 3s linear forwards;
        }

        .toast-error .toast-progress {
            background: #ef4444;
        }
        .action-icons {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .action-icon {
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        
        .action-icon:hover {
            background: #f3f4f6;
            transform: scale(1.1);
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
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
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
        
        /* Loading Skeleton */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% {
                background-position: 200% 0;
            }
            100% {
                background-position: -200% 0;
            }
        }
        
        .hidden {
            display: none !important;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .form-grid.cols-2,
            .form-grid.cols-3 {
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
            .search-input-group {
                width: 100%;
            }
            .search-input-group input, 
            .search-input-group select {
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
            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                
                <!-- Page Header -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-10">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <span class="px-3 py-1 bg-slate-100 text-slate-700 text-[10px] font-black uppercase tracking-widest rounded-full">Medical Directory</span>
                        </div>
                        <h1 class="text-4xl font-black tracking-tight text-slate-900 flex items-center gap-4">
                            <div class="p-3 bg-slate-900 rounded-2xl shadow-xl shadow-slate-200">
                                <i class="fas fa-user-md text-white"></i>
                            </div>
                            Doctor Management
                        </h1>
                        <p class="text-slate-500 mt-2 font-medium">Manage hospital medical staff and credentials.</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <button onclick="openAddDoctorModal()" class="px-6 py-3 bg-[#0f172a] hover:bg-slate-800 text-white font-black rounded-xl shadow-lg shadow-slate-200 transition-all flex items-center gap-2">
                            <i class="fas fa-plus"></i> Register New Doctor
                        </button>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bento-card">
                        <div class="bento-title">Total Doctors</div>
                        <h2 id="card-total-doctors" class="bento-value">0</h2>
                        <i class="fas fa-user-md bento-icon"></i>
                    </div>
                    <div class="bento-card">
                        <div class="bento-title">Active Now</div>
                        <h2 id="card-active-doctors" class="bento-value">0</h2>
                        <i class="fas fa-check-circle bento-icon"></i>
                    </div>
                    <div class="bento-card">
                        <div class="bento-title">Departments</div>
                        <h2 id="card-total-depts" class="bento-value">0</h2>
                        <i class="fas fa-hospital bento-icon"></i>
                    </div>
                    <div class="bento-card">
                        <div class="bento-title">Avg Rating</div>
                        <h2 class="bento-value">4.8</h2>
                        <i class="fas fa-star bento-icon"></i>
                    </div>
                </div>
                
                <!-- Main Content -->
                <div class="table-container">
                    <!-- Top Action Bar -->
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center action-bar-flex">
                            <div class="flex gap-4 search-input-group">
                                <!-- Search -->
                                <input type="text" id="searchInput" placeholder="Search doctors..." 
                                       class="px-4 py-2 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none flex-1">
                                
                                <!-- Filters -->
                                <select id="statusFilter" class="px-4 py-2 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none">
                                    <option value="">All Status</option>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Doctors Table -->
                    <div id="tableContainer" class="overflow-x-auto">
                        <table>
                            <thead>
                                <tr>
                                    <th>Doctor ID</th>
                                    <th>Full Name</th>
                                    <th>Gender</th>
                                    <th>Age</th>
                                    <th>Mobile</th>
                                    <th>Specialization</th>
                                    <th>Department</th>
                                    <th>Consultation Fee</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="doctorsTableBody">
                                <!-- Data will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="p-6 border-t border-gray-200 flex justify-between items-center">
                        <div class="text-sm text-gray-600">
                            Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalDoctors">0</span> doctors
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
    
    <!-- Doctor Modal -->
    <div id="doctorModal" class="modal">
        <div class="modal-content">
            <div class="p-8 bg-slate-50/50 border-b border-slate-100 flex justify-between items-center text-slate-900">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 bg-[#0f172a] rounded-2xl flex items-center justify-center text-white text-xl shadow-lg">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-black" id="modalTitle">Register Doctor</h3>
                        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mt-1">Medical Staff Credentials</p>
                    </div>
                </div>
                <button onclick="closeDoctorModal()" class="h-10 w-10 rounded-full hover:bg-slate-200 transition-all flex items-center justify-center text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
                
                <form id="doctorForm" onsubmit="handleFormSubmit(event)">
                    <input type="hidden" id="editDoctorId" name="doctor_id">
                    
                    <!-- Personal Information -->
                    <div class="section-card">
                        <div class="section-title">
                            <i class="fas fa-user"></i>
                            <h3>Personal Information</h3>
                        </div>
                        <div class="form-grid cols-2">
                            <div class="input-group">
                                <label>Full Name <span class="required">*</span></label>
                                <input type="text" name="full_name" required placeholder="Dr. John Doe">
                            </div>
                            <div class="input-group">
                                <label>Gender <span class="required">*</span></label>
                                <select name="gender" required>
                                    <option value="">Select</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="input-group">
                                <label>Date of Birth <span class="required">*</span></label>
                                <input type="date" name="date_of_birth" required onchange="calculateAge()">
                            </div>
                            <div class="input-group">
                                <label>Age</label>
                                <input type="number" name="age" readonly placeholder="Calculated automatically">
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
                                    <option value="Other">Other</option>
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
                        <div class="form-grid cols-2">
                            <div class="input-group">
                                <label>Mobile Number <span class="required">*</span></label>
                                <input type="tel" name="mobile_number" required placeholder="+91 98765 43210">
                            </div>
                            <div class="input-group">
                                <label>Alternate Mobile</label>
                                <input type="tel" name="alternate_mobile">
                            </div>
                            <div class="input-group">
                                <label>Email <span class="required">*</span></label>
                                <input type="email" name="email" required placeholder="doctor@gmhospital.com">
                            </div>
                            <div class="input-group">
                                <label>City</label>
                                <input type="text" name="city">
                            </div>
                        </div>
                        <div class="form-grid cols-1 mt-4">
                            <div class="input-group">
                                <label>Address</label>
                                <textarea name="address" rows="3" placeholder="Permanent or residential address"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Professional Details -->
                    <div class="section-card">
                        <div class="section-title">
                            <i class="fas fa-briefcase-medical"></i>
                            <h3>Professional Details</h3>
                        </div>
                        <div class="form-grid cols-2">
                            <div class="input-group">
                                <label>Qualification <span class="required">*</span></label>
                                <input type="text" name="qualification" required placeholder="e.g. MBBS, MD, MS">
                            </div>
                            <div class="input-group">
                                <label>Specialization <span class="required">*</span></label>
                                <select name="specialization" id="specializationDropdown" required>
                                    <option value="">Select Department</option>
                                    <!-- Options will be loaded dynamically -->
                                </select>
                            </div>
                            <div class="input-group">
                                <label>Sub-Specialization</label>
                                <input type="text" name="sub_specialization" placeholder="e.g. Cardiology, Neurology">
                            </div>
                            <div class="input-group">
                                <label>Medical Council</label>
                                <input type="text" name="medical_council">
                            </div>
                            <div class="input-group">
                                <label>Registration Number</label>
                                <input type="text" name="registration_number">
                            </div>
                            <div class="input-group">
                                <label>Experience (Years)</label>
                                <input type="number" name="experience_years">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Employment Details -->
                    <div class="section-card">
                        <div class="section-title">
                            <i class="fas fa-id-card-clip"></i>
                            <h3>Employment Details</h3>
                        </div>
                        <div class="form-grid cols-3">
                            <div class="input-group">
                                <label>Designation</label>
                                <input type="text" name="designation">
                            </div>
                            <div class="input-group">
                                <label>Employment Type</label>
                                <select name="employment_type">
                                    <option value="">Select</option>
                                    <option value="Full-time">Full-time</option>
                                    <option value="Part-time">Part-time</option>
                                    <option value="Visiting">Visiting</option>
                                </select>
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
                                <label>Consultation Fee</label>
                                <input type="number" name="consultation_fee" step="0.01">
                            </div>
                            <div class="input-group">
                                <label>Room Number</label>
                                <input type="text" name="room_number">
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
                    
                    <!-- Credentials -->
                    <div class="section-card" style="background: #f1f5f9; border-color: #cbd5e1;">
                        <div class="section-title">
                            <i class="fas fa-key"></i>
                            <h3>System Credentials</h3>
                        </div>
                        <div class="form-grid cols-2">
                            <div class="input-group">
                                <label>Username</label>
                                <input type="text" name="username" placeholder="Login username">
                            </div>
                            <div class="input-group">
                                <label>Password</label>
                                <input type="text" name="password" placeholder="Min 8 characters">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="flex justify-end gap-4">
                        <button type="button" onclick="closeDoctorModal()" class="px-6 py-3 border-2 border-gray-300 rounded-lg font-semibold hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" id="submitBtn" class="btn btn-primary">
                            <span id="submitBtnText">Save Doctor</span>
                            <i id="submitLoader" class="fas fa-spinner fa-spin hidden"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        let allDoctors = [];
        let filteredDoctors = [];
        let currentPage = 1;
        let pageSize = 10;
        let isEditMode = false;
        
        // Load doctors on page load
        window.addEventListener('DOMContentLoaded', () => {
            loadDoctors();
            loadDepartments();
        });
        
        // Load departments for specialization dropdown
        async function loadDepartments() {
            try {
                const response = await fetch('/GM_HMS/api/departments');
                const result = await response.json();
                
                if (result.success) {
                    const dropdown = document.getElementById('specializationDropdown');
                    
                    // Clear existing options except the first one
                    dropdown.innerHTML = '<option value="">Select Department</option>';
                    
                    // Add department options with ID and Name
                    result.data.forEach(dept => {
                        const option = document.createElement('option');
                        option.value = dept.department_name;
                        option.textContent = `${dept.department_id} - ${dept.department_name}`;
                        option.setAttribute('data-dept-id', dept.department_id);
                        dropdown.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading departments:', error);
            }
        }
        
        // Load all doctors
        async function loadDoctors() {
            try {
                const response = await fetch('/GM_HMS/api/doctors');
                const result = await response.json();
                
                if (result.success) {
                    allDoctors = result.data;
                    filteredDoctors = allDoctors;
                    renderTable();
                    updateKPIs();
                }
            } catch (error) {
                console.error('Error loading doctors:', error);
                showToast('Failed to load doctors', 'error');
            }
        }
        
        // Update KPIs
        function updateKPIs() {
            document.getElementById('card-total-doctors').textContent = allDoctors.length;
            document.getElementById('card-active-doctors').textContent = allDoctors.filter(d => d.status === 'Active').length;
            
            // Count unique departments
            const depts = new Set(allDoctors.map(d => d.specialization).filter(s => s));
            document.getElementById('card-total-depts').textContent = depts.size;
        }

        // Render table
        function renderTable() {
            const tbody = document.getElementById('doctorsTableBody');
            const start = (currentPage - 1) * pageSize;
            const end = start + pageSize;
            const paginatedDoctors = filteredDoctors.slice(start, end);
            
            if (paginatedDoctors.length === 0) {
                tbody.innerHTML = '<tr><td colspan="10" class="text-center py-8 text-gray-500">No doctors found</td></tr>';
                return;
            }
            
            tbody.innerHTML = paginatedDoctors.map(doctor => {
                return `
                <tr>
                    <td>${doctor.doctor_id}</td>
                    <td class="font-semibold">${doctor.full_name}</td>
                    <td>${doctor.gender || '-'}</td>
                    <td>${doctor.age || '-'}</td>
                    <td>${doctor.mobile_number || '-'}</td>
                    <td>${doctor.specialization || '-'}</td>
                    <td>${doctor.department || '-'}</td>
                    <td>₹${doctor.consultation_fee || '0'}</td>
                    <td><span class="status-badge status-${doctor.status?.toLowerCase() || 'active'}">${doctor.status || 'Active'}</span></td>
                    <td>
                        <div class="action-icons">
                            <i class="fas fa-edit action-icon edit" onclick="editDoctor('${doctor.doctor_id}')" title="Edit"></i>
                            <i class="fas fa-trash action-icon delete" onclick="deleteDoctor('${doctor.doctor_id}')" title="Delete"></i>
                        </div>
                    </td>
                </tr>
            `}).join('');
            
            updatePagination();
        }
        
        // Update pagination
        function updatePagination() {
            const start = (currentPage - 1) * pageSize + 1;
            const end = Math.min(currentPage * pageSize, filteredDoctors.length);
            
            document.getElementById('showingFrom').textContent = start;
            document.getElementById('showingTo').textContent = end;
            document.getElementById('totalDoctors').textContent = filteredDoctors.length;
            
            document.getElementById('prevBtn').disabled = currentPage === 1;
            document.getElementById('nextBtn').disabled = end >= filteredDoctors.length;
        }
        
        // Pagination functions
        function previousPage() {
            if (currentPage > 1) {
                currentPage--;
                renderTable();
            }
        }
        
        function nextPage() {
            if (currentPage * pageSize < filteredDoctors.length) {
                currentPage++;
                renderTable();
            }
        }
        
        // Open add doctor modal
        function openAddDoctorModal() {
            isEditMode = false;
            document.getElementById('modalTitle').textContent = 'Add New Doctor';
            document.getElementById('submitBtnText').textContent = 'Save Doctor';
            document.getElementById('doctorForm').reset();
            document.getElementById('doctorModal').classList.add('active');
        }
        
        // Close modal
        function closeDoctorModal() {
            document.getElementById('doctorModal').classList.remove('active');
            document.getElementById('doctorForm').reset();
        }
        
        // Calculate age from DOB
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
        
        // Edit doctor
        async function editDoctor(doctorId) {
            try {
                const response = await fetch(`/GM_HMS/api/doctors/${doctorId}`);
                const result = await response.json();
                
                if (result.success) {
                    isEditMode = true;
                    const doctor = result.data;
                    
                    document.getElementById('modalTitle').textContent = 'Edit Doctor';
                    document.getElementById('submitBtnText').textContent = 'Update Doctor';
                    document.getElementById('editDoctorId').value = doctor.doctor_id;
                    
                    // Fill form
                    Object.keys(doctor).forEach(key => {
                        const input = document.querySelector(`[name="${key}"]`);
                        if (input && key !== 'password') {
                            input.value = doctor[key] || '';
                        }
                    });
                    
                    document.getElementById('doctorModal').classList.add('active');
                }
            } catch (error) {
                console.error('Error loading doctor:', error);
                showToast('Failed to load doctor details', 'error');
            }
        }
        
        // Delete doctor
        async function deleteDoctor(doctorId) {
            if (!confirm('Are you sure you want to delete this doctor?')) {
                return;
            }
            
            try {
                const response = await fetch(`/GM_HMS/api/doctors/${doctorId}`, {
                    method: 'DELETE'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('Doctor deleted successfully', 'success');
                    loadDoctors();
                } else {
                    showToast(result.error || 'Failed to delete doctor', 'error');
                }
            } catch (error) {
                console.error('Error deleting doctor:', error);
                showToast('Error deleting doctor', 'error');
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
                if (key !== 'doctor_id' && key !== 'status' && value) {
                    if (key === 'age' || key === 'experience_years' || key === 'registration_year' || key === 'department_id') {
                        data[key] = parseInt(value, 10);
                    } else if (key === 'consultation_fee' || key === 'salary') {
                        data[key] = parseFloat(value);
                    } else {
                        data[key] = value;
                    }
                }
            });

            // Capture department_id from the specialization dropdown
            const specSelect = document.getElementById('specializationDropdown');
            if (specSelect && specSelect.selectedIndex > 0) {
                const deptId = specSelect.options[specSelect.selectedIndex].getAttribute('data-dept-id');
                if (deptId) {
                    data['department_id'] = deptId;
                }
            }
            
            try {
                const doctorId = document.getElementById('editDoctorId').value;
                const url = isEditMode 
                    ? `/GM_HMS/api/doctors/${doctorId}`
                    : '/GM_HMS/api/doctors';
                
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
                    showToast(isEditMode ? 'Doctor updated successfully' : `Doctor registered successfully! ID: ${result.data.doctor_id}`, 'success');
                    closeDoctorModal();
                    loadDoctors();
                } else {
                    showToast(result.error || 'Operation failed', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error. Please try again.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtnText.textContent = isEditMode ? 'Update Doctor' : 'Save Doctor';
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
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            filteredDoctors = allDoctors.filter(doctor => 
                doctor.full_name?.toLowerCase().includes(searchTerm) ||
                doctor.doctor_id?.toLowerCase().includes(searchTerm) ||
                doctor.specialization?.toLowerCase().includes(searchTerm)
            );
            currentPage = 1;
            renderTable();
        });
        
        // Status filter
        document.getElementById('statusFilter').addEventListener('change', (e) => {
            const status = e.target.value;
            filteredDoctors = status ? allDoctors.filter(d => d.status === status) : allDoctors;
            currentPage = 1;
            renderTable();
        });
    </script>
    <div id="toast-container" class="hidden">
        <div id="toast-box" class="toast-box">
            <div id="toast-icon" class="toast-icon"></div>
            <div id="toast-title" class="toast-title"></div>
            <div id="toast-message" class="toast-message"></div>
            <div class="toast-progress"></div>
        </div>
    </div>

    <script src="/GM_HMS/view/assets/js/admin_common.js"></script>
</body>
</html>
