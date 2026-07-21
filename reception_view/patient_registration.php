<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Receptionist', 'admin', 'Admin'])) {
    header("Location: /GM_HMS/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registered Patients - GM HMS</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Main Dashboard CSS -->
    <link rel="stylesheet" href="assets/css/reception_dashboard.css">

    <!-- Patient Module CSS -->
    <link rel="stylesheet" href="assets/css/patient.css">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        /* Additional page-specific overrides if needed */
    </style>
</head>

<body>

    <div class="reception-layout">

        <!-- Include Sidebar -->
        <?php include 'includes/reception_sidebar.php'; ?>

        <!-- Main Content -->
        <div class="reception-main-content">

            <!-- Include Navbar -->
            <?php
$pageTitle = 'Registered Patients';
include 'includes/reception_navbar.php';
?>

            <!-- Page Content -->
            <main class="reception-content">

                <!-- Page Header -->
                <div class="mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800" style="color: #144d34;">Patient Records</h1>
                            <p class="text-gray-600 mt-1">Manage all patient records and information</p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="openOldPatientSearch()" class="btn btn-outline" style="border: 2px solid #1f6b4a; color: #1f6b4a; padding: 10px 16px; border-radius: 10px; font-weight: 600; background: white;">
                                <i class="fas fa-search"></i>
                                Search Old Patient
                            </button>
                            <button onclick="openAddPatientModal()" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i>
                                Patient Registration
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter Bar -->
                <div class="bg-white rounded-xl p-4 shadow-sm mb-6">
                    <div class="filter-bar" style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <input type="text" id="searchInput" class="search-input"
                            placeholder="Search by phone number or patient ID..."
                            style="flex: 1; min-width: 250px; padding: 10px 14px 10px 40px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px;">

                        <select id="genderFilter" class="filter-select"
                            style="padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; background: white; cursor: pointer;">
                            <option value="">All Genders</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>

                        <select id="statusFilter" class="filter-select"
                            style="padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; background: white; cursor: pointer;">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>

                        <select id="pageSizeSelect" class="filter-select"
                            style="padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; background: white; cursor: pointer;">
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
                        <!-- Loading skeleton -->
                        <div id="loadingSkeleton" class="p-6">
                            <div class="skeleton h-12 mb-4"></div>
                            <div class="skeleton h-12 mb-4"></div>
                            <div class="skeleton h-12 mb-4"></div>
                            <div class="skeleton h-12 mb-4"></div>
                            <div class="skeleton h-12"></div>
                        </div>

                        <!-- Actual table -->
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
                            <div class="pagination"
                                style="display: flex; align-items: center; justify-content: space-between; padding: 20px; background: white; border-top: 1px solid #e5e7eb;">
                                <div class="pagination-info" style="color: #6b7280; font-size: 14px;">
                                    Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span
                                        id="totalRecords">0</span> patients
                                </div>
                                <div class="pagination-controls" style="display: flex; gap: 8px;">
                                    <button id="prevBtn" class="pagination-btn" onclick="changePage(-1)"
                                        style="padding: 8px 16px; border: 1px solid #e5e7eb; border-radius: 8px; background: white; cursor: pointer; transition: all 0.2s ease; font-size: 14px; font-weight: 500;">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </button>
                                    <button id="nextBtn" class="pagination-btn" onclick="changePage(1)"
                                        style="padding: 8px 16px; border: 1px solid #e5e7eb; border-radius: 8px; background: white; cursor: pointer; transition: all 0.2s ease; font-size: 14px; font-weight: 500;">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </main>

        </div>
    </div>

    <!-- Patient Form Modal -->
    <div id="patientModal" class="ref-modal-overlay hidden" onclick="closeModalOnBackdrop(event)">
        <div class="ref-modal-card" onclick="event.stopPropagation()">
            <div class="ref-modal-header">
                <h2 id="modalTitle">Add New Patient</h2>
                <button onclick="closePatientModal()" class="ref-modal-close" title="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="ref-modal-body">
                <form id="patientForm">
                    <input type="hidden" id="editPatientId" name="patient_id">

                    <div class="ref-form-grid">
                        <!-- Section 1: Basic Information -->
                        <div class="ref-section-title">
                            <i class="fas fa-info-circle"></i> Basic Information
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>Patient ID</label>
                            <input type="text" id="displayPatientId" class="readonly-mint" value="PID-AUTO" readonly>
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>Title</label>
                            <select name="title">
                                <option value="">Select</option>
                                <option value="Mr">Mr</option>
                                <option value="Mrs">Mrs</option>
                                <option value="Miss">Miss</option>
                                <option value="Dr">Dr</option>
                                <option value="Master">Mast</option>
                                <option value="B/O">B/O</option>
                                <option value="Baby Boy">Baby Boy</option>
                                <option value="Baby Girl">Baby Girl</option>
                                <option value="NA">N/A</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>First Name <span class="req">*</span></label>
                            <input type="text" name="first_name" placeholder="First Name" required>
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>Last Name</label>
                            <input type="text" name="last_name" placeholder="Last Name">
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>Phone <span class="req">*</span></label>
                            <input type="tel" id="patientPhone" name="phone" required placeholder="Phone Number" maxlength="10">
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>Aadhar Number</label>
                            <input type="text" id="patientAadhar" name="aadhar" placeholder="XXXX XXXX XXXX" maxlength="14">
                        </div>

                        <div class="ref-field ref-col-2">
                            <label>Gender</label>
                            <div class="radio-group" style="display:flex; gap:10px; margin-top:2px;">
                                <div class="radio-option">
                                    <input type="radio" name="sex" value="Male" id="male">
                                    <label for="male" style="padding: 6px 14px; border-radius: 6px; font-size: 0.8rem;"><i class="fas fa-mars"></i> Male</label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" name="sex" value="Female" id="female">
                                    <label for="female" style="padding: 6px 14px; border-radius: 6px; font-size: 0.8rem;"><i class="fas fa-venus"></i> Female</label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" name="sex" value="Other" id="other">
                                    <label for="other" style="padding: 6px 14px; border-radius: 6px; font-size: 0.8rem;"><i class="fas fa-genderless"></i> Other</label>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Medical Details -->
                        <div class="ref-section-title">
                            <i class="fas fa-heartbeat"></i> Medical Details
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>Birth Date</label>
                            <input type="date" name="birth_date" id="birthDate">
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>Age</label>
                            <input type="number" name="age" id="age" min="0" max="150" placeholder="Years">
                        </div>

                        <div class="ref-field ref-col-1">
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

                        <div class="ref-field ref-col-1">
                            <label>Vaccine Status</label>
                            <select name="vaccine_status">
                                <option value="">Select Status</option>
                                <option value="Not Vaccinated">Not Vaccinated</option>
                                <option value="Partially Vaccinated">Partially Vaccinated</option>
                                <option value="Fully Vaccinated">Fully Vaccinated</option>
                                <option value="Booster Taken">Booster Taken</option>
                            </select>
                        </div>

                        <div class="ref-field ref-col-4">
                            <label>Occupation</label>
                            <input type="text" name="occupation" placeholder="Patient Occupation">
                        </div>

                        <!-- Section 3: Location & Address -->
                        <div class="ref-section-title">
                            <i class="fas fa-map-marked-alt"></i> Location & Address
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>Pincode</label>
                            <div style="position:relative;">
                                <input type="text" name="pincode" id="patientPincode" placeholder="6-digit pincode" maxlength="6">
                                <span id="pincodeStatus" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); font-size:14px; pointer-events:none;"></span>
                            </div>
                            <span id="pincodeMessage" style="font-size:11px; margin-top:2px; display:block; color:#64748b;"></span>
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>Country</label>
                            <input type="text" name="country" id="patientCountry" placeholder="Country">
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>State</label>
                            <input type="text" name="state" id="patientState" placeholder="State">
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>District</label>
                            <input type="text" name="district" id="patientDistrict" list="districtDatalist" placeholder="District" autocomplete="off">
                            <datalist id="districtDatalist"></datalist>
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>City / Taluk</label>
                            <input type="text" name="city" id="patientCity" list="cityDatalist" placeholder="City" autocomplete="off">
                            <datalist id="cityDatalist"></datalist>
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>Region</label>
                            <input type="text" name="region" id="patientRegion" placeholder="Region">
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>Division</label>
                            <input type="text" name="division" id="patientDivision" placeholder="Division">
                        </div>

                        <div class="ref-field ref-col-1" style="position:relative;">
                            <label>Area / Post Office</label>
                            <input type="hidden" name="area" id="patientAreaValue">
                            <div style="position:relative;">
                                <input type="text" id="patientAreaSearch" placeholder="Search Area" autocomplete="off">
                                <span id="patientAreaClear" onclick="window._clearAreaSearch()" title="Clear" style="display:none; position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:12px; color:#9ca3af;">✕</span>
                            </div>
                            <div id="patientAreaDropdown" style="display:none; position:absolute; top:100%; left:0; right:0; background:#fff; border:1.5px solid #144D34; border-radius:0 0 8px 8px; max-height:160px; overflow-y:auto; z-index:999; box-shadow:0 8px 20px rgba(0,0,0,0.15);"></div>
                        </div>

                        <div class="ref-field ref-col-4">
                            <label>Full Address</label>
                            <textarea name="address" rows="2" placeholder="Full residential address..."></textarea>
                        </div>
                    </div>

                    <div class="ref-modal-footer">
                        <button type="button" onclick="closePatientModal()" class="ref-btn-cancel">Cancel</button>
                        <button type="submit" class="ref-btn-submit"><i class="fas fa-save"></i> Conform Registretion </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Duplicate Alert Modal -->
    <div id="duplicateModal" class="modal-overlay hidden">
        <div class="modal-content alert-modal">
            <div class="modal-body" style="padding: 40px 30px;">
                <div class="alert-icon-wrapper">
                    <i class="fas fa-id-card"></i>
                </div>
                <h2 class="alert-title">Patient Already Exists</h2>
                <div id="duplicateInfo" class="alert-message">
                    Patient details already exist. Please proceed to appointment booking.
                </div>
                <div class="alert-footer">
                    <button id="proceedToBookingBtn" class="btn btn-primary btn-full">
                        <i class="fas fa-calendar-check"></i> Proceed to Booking
                    </button>
                    <button onclick="document.getElementById('duplicateModal').classList.add('hidden')"
                        class="btn btn-secondary btn-full">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Premium Old Patient Search Modal -->
    <div id="oldPatientModal" class="modal-overlay hidden" style="z-index: 1000; backdrop-filter: blur(8px); background: rgba(15, 23, 42, 0.6); position: fixed; top: 0; left: 0; right: 0; bottom: 0; display: flex; justify-content: center; align-items: center;">
        <!-- Backdrop click listener added directly to the overlay -->
        <div style="position: absolute; top:0; left:0; right:0; bottom:0;" onclick="closeOldPatientModal()"></div>
        
        <div class="modal-content" style="position: relative; width: 100%; max-width: 550px; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1px solid rgba(255,255,255,0.1); background: #ffffff; overflow: visible; transform: translateY(0); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);">
            
            <div style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); padding: 32px 32px 24px; text-align: center; position: relative; border-radius: 24px 24px 0 0;">
                <button onclick="closeOldPatientModal()" style="position: absolute; top: 20px; right: 20px; background: white; border: none; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #64748b; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.05); transition: all 0.2s;"><i class="fas fa-times"></i></button>
                
                <div style="width: 64px; height: 64px; background: #dcfce7; color: #16a34a; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 16px; box-shadow: 0 10px 25px rgba(22, 163, 74, 0.2); transform: rotate(-5deg);">
                    <i class="fas fa-search"></i>
                </div>
                <h2 style="margin: 0 0 8px; font-size: 1.5rem; font-weight: 800; color: #0f172a; letter-spacing: -0.5px;">Find Existing Patient</h2>
                <p style="margin: 0; color: #64748b; font-size: 0.95rem;">Quickly locate patient records by name, phone, or ID</p>
            </div>
            
            <div class="modal-body" style="padding: 32px;">
                <div class="custom-search-container" style="position: relative; margin-bottom: 32px; text-align: left;">
                    <label style="font-weight: 600; color: #334155; margin-bottom: 12px; display: block; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Search Patient</label>
                    <div style="position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 1.1rem;"></i>
                        <input type="text" id="customPatientSearch" placeholder="Type name, phone, or ID..." style="width: 100%; height: 56px; border-radius: 16px; border: 2px solid #e2e8f0; padding: 0 48px; font-size: 1.05rem; background: #f8fafc; transition: all 0.2s ease; outline: none; color: #334155; font-weight: 500; box-sizing: border-box;" autocomplete="off">
                        <i class="fas fa-spinner fa-spin" id="searchLoadingIcon" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: #1f6b4a; display: none; font-size: 1.1rem;"></i>
                    </div>
                    
                    <!-- Custom Dropdown Results -->
                    <div id="searchResultsDropdown" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border: 1px solid #e2e8f0; margin-top: 8px; max-height: 280px; overflow-y: auto; display: none; z-index: 100;">
                        <!-- Results injected here -->
                    </div>
                </div>
                
                <div style="display: flex; justify-content: center; gap: 16px;">
                    <button type="button" onclick="closeOldPatientModal()" class="btn" style="padding: 12px 24px; border-radius: 12px; font-weight: 600; color: #64748b; background: #f1f5f9; border: none; transition: all 0.2s; cursor: pointer;">Cancel</button>
                    <button type="button" onclick="goToPatientProfile()" id="btnGoToProfile" disabled style="padding: 12px 32px; border-radius: 12px; font-weight: 700; color: #ffffff; background: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%); border: none; cursor: pointer; transition: all 0.3s; box-shadow: 0 8px 20px rgba(31, 107, 74, 0.3); display: flex; align-items: center; gap: 10px; opacity: 0.5;">
                        View Profile <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        #customPatientSearch:focus {
            border-color: #1f6b4a !important;
            background: #ffffff !important;
            box-shadow: 0 0 0 4px rgba(31, 107, 74, 0.1) !important;
        }
        
        .search-result-item:hover {
            background: #f8fafc !important;
        }
        
        #btnGoToProfile:not(:disabled):hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(31, 107, 74, 0.4);
            opacity: 1 !important;
        }
        #btnGoToProfile:not(:disabled) {
            opacity: 1 !important;
        }
    </style>

    <!-- Select2 & jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- JavaScript -->
    <script src="assets/js/patient.js"></script>
    <script>
        // Initialize patient manager
        window.patientManager = new PatientManager();
        window.patientManager.init();

        let searchTimeout;
        let selectedPatientId = null;
        
        function openOldPatientSearch() {
            document.getElementById('oldPatientModal').classList.remove('hidden');
            document.getElementById('customPatientSearch').value = '';
            document.getElementById('searchResultsDropdown').style.display = 'none';
            document.getElementById('btnGoToProfile').disabled = true;
            document.getElementById('btnGoToProfile').style.opacity = '0.5';
            selectedPatientId = null;
            
            setTimeout(() => {
                document.getElementById('customPatientSearch').focus();
            }, 100);
        }

        function closeOldPatientModal() {
            document.getElementById('oldPatientModal').classList.add('hidden');
            document.getElementById('searchResultsDropdown').style.display = 'none';
        }

        document.getElementById('customPatientSearch').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            const resultsDropdown = document.getElementById('searchResultsDropdown');
            const loadingIcon = document.getElementById('searchLoadingIcon');
            const btnGoToProfile = document.getElementById('btnGoToProfile');
            
            selectedPatientId = null;
            btnGoToProfile.disabled = true;
            btnGoToProfile.style.opacity = '0.5';
            
            if (query.length < 2) {
                resultsDropdown.style.display = 'none';
                return;
            }
            
            loadingIcon.style.display = 'block';
            
            searchTimeout = setTimeout(() => {
                fetch(`/GM_HMS/api/patients?search=${encodeURIComponent(query)}&limit=10`)
                    .then(res => res.json())
                    .then(res => {
                        loadingIcon.style.display = 'none';
                        const patients = res.data?.data || res.data || [];
                        
                        if (patients.length === 0) {
                            resultsDropdown.innerHTML = `<div style="padding: 24px; text-align: center; color: #94a3b8;"><i class="fas fa-search mb-2" style="font-size: 24px; opacity: 0.5; display:block;"></i>No patients found</div>`;
                        } else {
                            resultsDropdown.innerHTML = patients.map(p => `
                                <div class="search-result-item" onclick="selectCustomPatient('${p.patient_id}', '${p.full_name.replace(/'/g, "\\'")}')" style="padding: 12px 16px; border-bottom: 1px solid #f1f5f9; cursor: pointer; display: flex; align-items: center; gap: 14px; transition: background 0.2s;">
                                    <div style="width: 44px; height: 44px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.1rem; flex-shrink: 0;">
                                        ${p.full_name.charAt(0).toUpperCase()}
                                    </div>
                                    <div style="min-width: 0; flex: 1;">
                                        <div style="font-weight: 600; color: #1e293b; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${p.full_name}</div>
                                        <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;"><span style="color:#1f6b4a; font-weight: 500;">${p.patient_id}</span> • <i class="fas fa-phone-alt" style="font-size:0.7rem;"></i> ${p.phone || 'N/A'}</div>
                                    </div>
                                </div>
                            `).join('');
                        }
                        resultsDropdown.style.display = 'block';
                    })
                    .catch(err => {
                        loadingIcon.style.display = 'none';
                    });
            }, 300);
        });

        // Hide dropdown on click outside
        document.addEventListener('click', (e) => {
            const searchInput = document.getElementById('customPatientSearch');
            const resultsDropdown = document.getElementById('searchResultsDropdown');
            if (searchInput && resultsDropdown && !searchInput.contains(e.target) && !resultsDropdown.contains(e.target)) {
                resultsDropdown.style.display = 'none';
            }
        });

        function selectCustomPatient(id, name) {
            selectedPatientId = id;
            document.getElementById('customPatientSearch').value = name;
            document.getElementById('searchResultsDropdown').style.display = 'none';
            const btn = document.getElementById('btnGoToProfile');
            btn.disabled = false;
            btn.style.opacity = '1';
        }

        function goToPatientProfile() {
            if (selectedPatientId) {
                sessionStorage.setItem('currentPatientId', selectedPatientId);
                window.location.href = `patient_profile.php`;
            }
        }
    </script>
</body>

</html>