<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Management - GM Hospital</title>
    
    <!-- Tailwind CSS CDN -->
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
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
        }
        
        /* Table Styles */
        .patient-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .patient-table thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .patient-table thead th {
            padding: 16px 12px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .patient-table tbody tr {
            background: white;
            transition: all 0.2s ease;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .patient-table tbody tr:hover {
            background: #f9fafb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .patient-table tbody td {
            padding: 14px 12px;
            font-size: 14px;
            color: #374151;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-discharged {
            background: #e0e7ff;
            color: #3730a3;
        }
        
        /* Action Icons */
        .action-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin: 0 4px;
        }
        
        .action-icon:hover {
            transform: translateY(-2px);
        }
        
        .action-icon.edit {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .action-icon.edit:hover {
            background: #3b82f6;
            color: white;
        }
        
        .action-icon.delete {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .action-icon.delete:hover {
            background: #ef4444;
            color: white;
        }
        
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }
        
        .modal-content {
            background: white;
            border-radius: 24px;
            max-width: 1000px;
            width: 95%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
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
        .form-grid {
            display: grid;
            gap: 20px;
        }
        
        .form-grid.cols-2 {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .form-grid.cols-3 {
            grid-template-columns: repeat(3, 1fr);
        }
        
        .input-group {
            margin-bottom: 16px;
        }
        
        .input-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }
        
        .input-group label .required {
            color: #ef4444;
            margin-left: 2px;
        }
        
        .input-group input,
        .input-group select,
        .input-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            transition: all 0.2s ease;
            outline: none;
        }
        
        .input-group textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        .input-group input:focus,
        .input-group select:focus,
        .input-group textarea:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        /* Radio Button Styles */
        .radio-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .radio-option {
            position: relative;
        }
        
        .radio-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .radio-option label {
            display: flex;
            align-items: center;
            padding: 8px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
            gap: 6px;
        }
        
        .radio-option input[type="radio"]:checked + label {
            border-color: #6366f1;
            background: #E6FAFA;
            color: #144d34;
        }
        
        .radio-option label:hover {
            border-color: #d1d5db;
        }
        
        /* Button Styles */
        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.4);
        }
        
        .btn-secondary {
            background: white;
            color: #6b7280;
            border: 2px solid #e5e7eb;
        }
        
        .btn-secondary:hover {
            border-color: #d1d5db;
            background: #f9fafb;
        }
        
        /* Toast Notification */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 12px;
            color: white;
            font-weight: 500;
            z-index: 2000;
            animation: slideIn 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 12px;
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
        
        .toast.success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .toast.error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        /* Loading Skeleton */
        .skeleton {
            background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 4px;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 64px;
            color: #d1d5db;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 20px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .empty-state p {
            color: #6b7280;
            margin-bottom: 24px;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            background: white;
            border-top: 1px solid #e5e7eb;
        }
        
        .pagination-info {
            color: #6b7280;
            font-size: 14px;
        }
        
        .pagination-controls {
            display: flex;
            gap: 8px;
        }
        
        .pagination-btn {
            padding: 8px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 14px;
            font-weight: 500;
        }
        
        .pagination-btn:hover:not(:disabled) {
            background: #f9fafb;
            border-color: #d1d5db;
        }
        
        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Search and Filter Bar */
        .filter-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 10px 14px 10px 40px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            background: white url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%236b7280' viewBox='0 0 24 24'%3E%3Cpath d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'/%3E%3C/svg%3E") no-repeat 12px center;
            background-size: 20px;
        }
        
        .filter-select {
            padding: 10px 14px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            background: white;
            cursor: pointer;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .form-grid.cols-2,
            .form-grid.cols-3 {
                grid-template-columns: 1fr;
            }
            
            .filter-bar {
                flex-direction: column;
            }
            
            .search-input {
                width: 100%;
            }
        }
        
        .hidden {
            display: none !important;
        }
        
        .loader {
            border: 3px solid #f3f4f6;
            border-top: 3px solid #1f6b4a;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800">Patient Management</h1>
                            <p class="text-gray-600 mt-1">Manage all patient records and information</p>
                        </div>
                        <button onclick="openAddPatientModal()" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i>
                            Patient Registration
                        </button>
                    </div>
                </div>
                
                <!-- Search and Filter Bar -->
                <div class="bg-white rounded-xl p-4 shadow-sm mb-6">
                    <div class="filter-bar">
                        <input type="text" id="searchInput" class="search-input" placeholder="Search by name, email, or patient ID...">
                        
                        <select id="genderFilter" class="filter-select">
                            <option value="">All Genders</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                        
                        <select id="statusFilter" class="filter-select">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Discharged">Discharged</option>
                        </select>
                        
                        <select id="pageSizeSelect" class="filter-select">
                            <option value="10">10 per page</option>
                            <option value="25">25 per page</option>
                            <option value="50">50 per page</option>
                            <option value="100">100 per page</option>
                        </select>
                    </div>
                </div>
                
                <!-- Patient Table -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div id="tableContainer">
                        <!-- Loading skeleton will be shown here initially -->
                        <div id="loadingSkeleton" class="p-6">
                            <div class="skeleton h-12 mb-4"></div>
                            <div class="skeleton h-12 mb-4"></div>
                            <div class="skeleton h-12 mb-4"></div>
                            <div class="skeleton h-12 mb-4"></div>
                            <div class="skeleton h-12"></div>
                        </div>
                        
                        <!-- Actual table will be inserted here -->
                        <div id="patientTableWrapper" class="hidden">
                            <div style="overflow-x: auto;">
                                <table class="patient-table">
                                    <thead>
                                        <tr>
                                            <th>Patient ID</th>
                                            <th>Full Name</th>
                                            <th>Age</th>
                                            <th>Gender</th>
                                            <th>Phone</th>
                                            <th>Aadhar</th>
                                            <th>City</th>
                                            <th>Registration Date</th>
                                            <th>Status</th>
                                            <th style="text-align: center;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="patientTableBody">
                                        <!-- Rows will be inserted here dynamically -->
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <div class="pagination">
                                <div class="pagination-info">
                                    Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalRecords">0</span> patients
                                </div>
                                <div class="pagination-controls">
                                    <button id="prevBtn" class="pagination-btn" onclick="changePage(-1)">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </button>
                                    <button id="nextBtn" class="pagination-btn" onclick="changePage(1)">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Empty State -->
                        <div id="emptyState" class="hidden empty-state">
                            <i class="fas fa-users"></i>
                            <h3>No Patients Found</h3>
                            <p>Start by adding your first patient to the system</p>
                            <button onclick="openAddPatientModal()" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i>
                                Add First Patient
                            </button>
                        </div>
                    </div>
                </div>
                
            </main>
            
        </div>
    </div>
    
    <!-- Patient Form Modal -->
    <div id="patientModal" class="modal-overlay hidden" onclick="closeModalOnBackdrop(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <!-- Modal Header -->
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 id="modalTitle" class="text-2xl font-bold text-gray-800">Add New Patient</h2>
                        <p class="text-gray-600 text-sm mt-1">Fill in the patient information below</p>
                    </div>
                    <button onclick="closePatientModal()" class="text-gray-400 hover:text-gray-600 transition">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="p-6">
                <form id="patientForm">
                    <input type="hidden" id="editPatientId" name="patient_id">
                    
                    <!-- Personal Information -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-user text-purple-600"></i>
                            Personal Information
                        </h3>
                        
                        <div class="form-grid cols-3">
                            <div class="input-group">
                                <label>Title</label>
                                <select name="title">
                                    <option value="">Select</option>
                                    <option value="Mr">Mr</option>
                                    <option value="Mrs">Mrs</option>
                                    <option value="Ms">Ms</option>
                                    <option value="Dr">Dr</option>
                                </select>
                            </div>
                            
                            <div class="input-group">
                                <label>First Name <span class="required">*</span></label>
                                <input type="text" name="first_name" required placeholder="Enter first name">
                            </div>
                            
                            <div class="input-group">
                                <label>Last Name <span class="required">*</span></label>
                                <input type="text" name="last_name" required placeholder="Enter last name">
                            </div>
                        </div>
                        
                        <div class="form-grid cols-2">
                            <div class="input-group">
                                <label>Aadhar <span class="required">*</span></label>
                                <input type="text" name="aadhar" required placeholder="Enter Aadhar Number">
                            </div>
                            
                            <div class="input-group">
                                <label>Phone</label>
                                <input type="tel" name="phone" placeholder="+91 1234567890">
                            </div>
                        </div>
                        
                        <div class="input-group">
                            <label>Gender <span class="required">*</span></label>
                            <div class="radio-group">
                                <div class="radio-option">
                                    <input type="radio" name="sex" value="Male" id="male" required>
                                    <label for="male">
                                        <i class="fas fa-mars"></i> Male
                                    </label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" name="sex" value="Female" id="female" required>
                                    <label for="female">
                                        <i class="fas fa-venus"></i> Female
                                    </label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" name="sex" value="Other" id="other" required>
                                    <label for="other">
                                        <i class="fas fa-genderless"></i> Other
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Medical Information -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-heartbeat text-blue-600"></i>
                            Medical Information
                        </h3>
                        
                        <div class="form-grid cols-3">
                            <div class="input-group">
                                <label>Birth Date <span class="required">*</span></label>
                                <input type="date" name="birth_date" id="birthDate" required>
                            </div>
                            
                            <div class="input-group">
                                <label>Age</label>
                                <input type="number" name="age" id="age" readonly class="bg-gray-50 cursor-not-allowed" placeholder="Auto-calculated">
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
                        </div>
                        
                        <div class="form-grid cols-2">
                            <div class="input-group">
                                <label>Occupation</label>
                                <input type="text" name="occupation" placeholder="Enter occupation">
                            </div>
                            
                            <div class="input-group">
                                <label>Vaccine Status</label>
                                <select name="vaccine_status">
                                    <option value="">Select Status</option>
                                    <option value="Not Vaccinated">Not Vaccinated</option>
                                    <option value="Partially Vaccinated">Partially Vaccinated</option>
                                    <option value="Fully Vaccinated">Fully Vaccinated</option>
                                    <option value="Booster Taken">Booster Taken</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Address Information -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-map-marker-alt text-orange-600"></i>
                            Address Information
                        </h3>
                        
                        <div class="input-group">
                            <label>Address</label>
                            <textarea name="address" rows="2" placeholder="Enter full address"></textarea>
                        </div>
                        
                        <div class="form-grid cols-3">
                            <div class="input-group">
                                <label>Country</label>
                                <input type="text" name="country" placeholder="Enter country">
                            </div>
                            
                            <div class="input-group">
                                <label>State</label>
                                <input type="text" name="state" placeholder="Enter state">
                            </div>
                            
                            <div class="input-group">
                                <label>District</label>
                                <input type="text" name="district" placeholder="Enter district">
                            </div>
                            
                            <div class="input-group">
                                <label>City</label>
                                <input type="text" name="city" placeholder="Enter city">
                            </div>
                            
                            <div class="input-group">
                                <label>Area</label>
                                <input type="text" name="area" placeholder="Enter area">
                            </div>
                            
                            <div class="input-group">
                                <label>Pincode</label>
                                <input type="text" name="pincode" placeholder="Enter pincode">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Referral & Other Information -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-info-circle text-green-600"></i>
                            Referral & Other Information
                        </h3>
                        
                        <div class="form-grid cols-3">
                            <div class="input-group">
                                <label>Referral Type</label>
                                <select name="referral_type">
                                    <option value="">Not Specified</option>
                                    <option value="Doctor">Doctor</option>
                                    <option value="Friend">Friend</option>
                                    <option value="Family">Family</option>
                                    <option value="Self">Self</option>
                                    <option value="Advertisement">Advertisement</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            
                            <div class="input-group">
                                <label>Referral Name</label>
                                <input type="text" name="referral_name" placeholder="Enter referral name">
                            </div>
                            
                            <div class="input-group">
                                <label>Sponsor</label>
                                <input type="text" name="sponsor" placeholder="Enter sponsor">
                            </div>
                            
                            <div class="input-group">
                                <label>Preferred Language</label>
                                <select name="preferred_language">
                                    <option value="">Select Language</option>
                                    <option value="English">English</option>
                                    <option value="Hindi">Hindi</option>
                                    <option value="Kannada">Kannada</option>
                                    <option value="Tamil">Tamil</option>
                                    <option value="Telugu">Telugu</option>
                                    <option value="Malayalam">Malayalam</option>
                                </select>
                            </div>
                            
                            <div class="input-group">
                                <label>Status</label>
                                <select name="status">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Discharged">Discharged</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                        <button type="button" onclick="closePatientModal()" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                        <button type="submit" id="submitBtn" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            <span id="submitBtnText">Save Patient</span>
                            <div id="submitLoader" class="loader hidden"></div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Global variables
        let allPatients = [];
        let filteredPatients = [];
        let currentPage = 1;
        let pageSize = 10;
        let isEditMode = false;
        
        // Load patients on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadPatients();
            setupEventListeners();
        });
        
        // Setup event listeners
        function setupEventListeners() {
            document.getElementById('searchInput').addEventListener('input', filterPatients);
            document.getElementById('genderFilter').addEventListener('change', filterPatients);
            document.getElementById('statusFilter').addEventListener('change', filterPatients);
            document.getElementById('pageSizeSelect').addEventListener('change', function() {
                pageSize = parseInt(this.value);
                currentPage = 1;
                renderTable();
            });
            
            document.getElementById('birthDate').addEventListener('change', function() {
                const age = calculateAge(this.value);
                document.getElementById('age').value = age !== '' ? age : '';
            });
            
            document.getElementById('patientForm').addEventListener('submit', handleFormSubmit);
        }
        
        // Load patients from API
        async function loadPatients() {
            try {
                // Fetch with a high limit so local pagination and filtering works correctly
                const response = await fetch('/GM_HMS/api/patients?limit=1000');
                
                const result = await response.json();
                
                if (result.success) {
                    // Extract data array from the paginated response object, or fallback to the object itself if it is an array
                    allPatients = (result.data && Array.isArray(result.data.data)) ? result.data.data : (Array.isArray(result.data) ? result.data : []);
                    filteredPatients = [...allPatients];
                    renderTable();
                } else {
                    showToast('Failed to load patients: ' + (result.error || 'Unknown error'), 'error');
                }
            } catch (error) {
                console.error('Error loading patients:', error);
                showToast('Error loading patients. Please refresh the page.', 'error');
            } finally {
                document.getElementById('loadingSkeleton').classList.add('hidden');
            }
        }
        
        // Filter patients based on search and filters
        function filterPatients() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const genderFilter = document.getElementById('genderFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            
            filteredPatients = allPatients.filter(patient => {
                const matchesSearch = !searchTerm || 
                    (patient.first_name && patient.first_name.toLowerCase().includes(searchTerm)) ||
                    (patient.last_name && patient.last_name.toLowerCase().includes(searchTerm)) ||
                    (patient.aadhar && patient.aadhar.toLowerCase().includes(searchTerm)) ||
                    (patient.patient_id && patient.patient_id.toLowerCase().includes(searchTerm));
                
                const matchesGender = !genderFilter || patient.sex === genderFilter;
                const matchesStatus = !statusFilter || patient.status === statusFilter;
                
                return matchesSearch && matchesGender && matchesStatus;
            });
            
            currentPage = 1;
            renderTable();
        }
        
        // Render table
        function renderTable() {
            const tbody = document.getElementById('patientTableBody');
            const tableWrapper = document.getElementById('patientTableWrapper');
            const emptyState = document.getElementById('emptyState');
            
            if (filteredPatients.length === 0) {
                tableWrapper.classList.add('hidden');
                emptyState.classList.remove('hidden');
                return;
            }
            
            tableWrapper.classList.remove('hidden');
            emptyState.classList.add('hidden');
            
            const startIndex = (currentPage - 1) * pageSize;
            const endIndex = Math.min(startIndex + pageSize, filteredPatients.length);
            const patientsToShow = filteredPatients.slice(startIndex, endIndex);
            
            tbody.innerHTML = patientsToShow.map(patient => {
                const fullName = `${patient.title || ''} ${patient.first_name || ''} ${patient.last_name || ''}`.trim();
                const statusClass = `status-${(patient.status || 'active').toLowerCase()}`;
                
                return `
                    <tr>
                        <td><strong>${patient.patient_id || 'N/A'}</strong></td>
                        <td>${fullName || 'N/A'}</td>
                        <td>${patient.age || 'N/A'}</td>
                        <td>${patient.sex || 'N/A'}</td>
                        <td>${patient.phone || 'N/A'}</td>
                        <td>${patient.aadhar || 'N/A'}</td>
                        <td>${patient.city || 'N/A'}</td>
                        <td>${patient.date || 'N/A'}</td>
                        <td><span class="status-badge ${statusClass}">${patient.status || 'Active'}</span></td>
                        <td style="text-align: center;">
                            <div style="display: inline-flex; gap: 4px;">
                                <span class="action-icon edit" onclick="editPatient('${patient.patient_id}')" title="Edit Patient">
                                    <i class="fas fa-edit"></i>
                                </span>
                                <span class="action-icon delete" onclick="deletePatient('${patient.patient_id}')" title="Delete Patient">
                                    <i class="fas fa-trash"></i>
                                </span>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
            
            updatePaginationInfo();
        }
        
        // Update pagination info
        function updatePaginationInfo() {
            const startIndex = (currentPage - 1) * pageSize + 1;
            const endIndex = Math.min(currentPage * pageSize, filteredPatients.length);
            
            document.getElementById('showingFrom').textContent = filteredPatients.length > 0 ? startIndex : 0;
            document.getElementById('showingTo').textContent = endIndex;
            document.getElementById('totalRecords').textContent = filteredPatients.length;
            
            document.getElementById('prevBtn').disabled = currentPage === 1;
            document.getElementById('nextBtn').disabled = endIndex >= filteredPatients.length;
        }
        
        // Change page
        function changePage(direction) {
            const totalPages = Math.ceil(filteredPatients.length / pageSize);
            currentPage += direction;
            currentPage = Math.max(1, Math.min(currentPage, totalPages));
            renderTable();
        }
        
        // Open add patient modal
        function openAddPatientModal() {
            isEditMode = false;
            document.getElementById('modalTitle').textContent = 'Add New Patient';
            document.getElementById('submitBtnText').textContent = 'Save Patient';
            document.getElementById('patientForm').reset();
            document.getElementById('editPatientId').value = '';
            document.getElementById('patientModal').classList.remove('hidden');
        }
        
        // Edit patient
        async function editPatient(patientId) {
            try {
                const response = await fetch(`/GM_HMS/api/patients/${patientId}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    isEditMode = true;
                    const patient = result.data;
                    
                    document.getElementById('modalTitle').textContent = 'Edit Patient';
                    document.getElementById('submitBtnText').textContent = 'Update Patient';
                    document.getElementById('editPatientId').value = patient.patient_id;
                    
                    // Fill form with patient data
                    const form = document.getElementById('patientForm');
                    Object.keys(patient).forEach(key => {
                        const input = form.elements[key];
                        if (input) {
                            if (input.type === 'radio') {
                                const radio = form.querySelector(`input[name="${key}"][value="${patient[key]}"]`);
                                if (radio) radio.checked = true;
                            } else {
                                input.value = patient[key] || '';
                            }
                        }
                    });
                    
                    // Calculate age
                    if (patient.birth_date) {
                        const age = calculateAge(patient.birth_date);
                        document.getElementById('age').value = age !== '' ? age : '';
                    }
                    
                    document.getElementById('patientModal').classList.remove('hidden');
                } else {
                    showToast('Failed to load patient data', 'error');
                }
            } catch (error) {
                console.error('Error loading patient:', error);
                showToast('Error loading patient data', 'error');
            }
        }
        
        // Delete patient
        async function deletePatient(patientId) {
            if (!confirm('Are you sure you want to delete this patient? This action cannot be undone.')) {
                return;
            }
            
            try {
                const response = await fetch(`/GM_HMS/api/patients/${patientId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('Patient deleted successfully', 'success');
                    loadPatients();
                } else {
                    showToast('Failed to delete patient: ' + (result.error || 'Unknown error'), 'error');
                }
            } catch (error) {
                console.error('Error deleting patient:', error);
                showToast('Error deleting patient', 'error');
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
                // Skip patient_id and status fields
                if (key !== 'patient_id' && key !== 'status' && value) {
                    if (key === 'age') {
                        data[key] = parseInt(value, 10);
                    } else {
                        data[key] = value;
                    }
                }
            });
            
            try {
                const patientId = document.getElementById('editPatientId').value;
                const url = isEditMode 
                    ? `/GM_HMS/api/patients/${patientId}`
                    : '/GM_HMS/api/patients';
                
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
                    showToast(isEditMode ? 'Patient updated successfully' : `Patient registered successfully! ID: ${result.data.patient_id}`, 'success');
                    closePatientModal();
                    loadPatients();
                } else {
                    showToast(result.error || 'Operation failed', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error. Please try again.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtnText.textContent = isEditMode ? 'Update Patient' : 'Save Patient';
                submitLoader.classList.add('hidden');
            }
        }
        
        // Close modal
        function closePatientModal() {
            document.getElementById('patientModal').classList.add('hidden');
            document.getElementById('patientForm').reset();
        }
        
        // Close modal on backdrop click
        function closeModalOnBackdrop(event) {
            if (event.target.id === 'patientModal') {
                closePatientModal();
            }
        }
        
        // Calculate age
        function calculateAge(birthDateInput) {
            const birthDate = new Date(birthDateInput);
            const today = new Date();
            
            if (isNaN(birthDate.getTime()) || birthDate > today) return '';
            
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            
            return age >= 0 && age <= 150 ? age : '';
        }
        
        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} text-xl"></i>
                <span>${message}</span>
            `;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideIn 0.3s ease reverse';
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }
    </script>
    
    <script src="assets/js/admin_common.js"></script>
</body>
</html>