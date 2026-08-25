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
        #cardQrCodeContainer {
            width: 130px !important;
            height: 130px !important;
            min-width: 130px !important;
            min-height: 130px !important;
            max-width: 130px !important;
            max-height: 130px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            margin: 0 auto !important;
            background: #ffffff !important;
            overflow: hidden !important;
        }
        #cardQrCodeContainer img, #cardQrCodeContainer canvas {
            width: 130px !important;
            height: 130px !important;
            min-width: 130px !important;
            min-height: 130px !important;
            max-width: 130px !important;
            max-height: 130px !important;
            aspect-ratio: 1 / 1 !important;
            object-fit: contain !important;
            display: block !important;
            margin: 0 auto !important;
        }
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
    <!-- JS Handlers for Patient Registration Modal -->
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- QRCode & JsBarcode Libraries (Loaded BEFORE patient.js) -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    
    <!-- JavaScript -->
    <script src="assets/js/patient.js?v=<?= time() ?>"></script>
    <script>
        // Initialize patient manager
        window.patientManager = new PatientManager();
        document.addEventListener('DOMContentLoaded', () => {
            window.patientManager.init();
        });

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

        document.addEventListener('DOMContentLoaded', function() {
            fetchReferredDoctors();
        });
        
        function fetchReferredDoctors() {
            $('#existingDoctorSelect').select2({
                placeholder: "Search or add new doctor...",
                allowClear: true,
                tags: true,
                createTag: function (params) {
                    var term = $.trim(params.term);
                    if (term === '') {
                        return null;
                    }
                    return {
                        id: 'new:' + term,
                        text: term + ' (Add New)',
                        newTag: true
                    }
                }
            });
            
            // Fetch doctors list from API
            fetch('/GM_HMS/api/referred-doctors')
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data) {
                        const select = $('#existingDoctorSelect');
                        select.empty().append(new Option('Search existing doctor...', '', true, true));
                        
                        // Sort alphabetically by doctor_name
                        result.data.sort((a, b) => (a.doctor_name || '').localeCompare(b.doctor_name || ''));

                        result.data.forEach(doc => {
                            const option = new Option(doc.doctor_name, doc.sl_no, false, false);
                            // Store extra data for later
                            $(option).data('email', doc.email);
                            $(option).data('phone', doc.phone);
                            select.append(option);
                        });
                        
                        // Handle change event to update the details automatically
                        select.on('change', function() {
                            const val = $(this).val();
                            const selectedOption = $(this).find(':selected');
                            const referralNameInput = document.getElementById('referral_name_input');
                            const phoneInput = document.getElementById('ref_doctor_phone');
                            const emailInput = document.getElementById('ref_doctor_email');
                            const extraDetailsDiv = document.getElementById('doctorExtraDetailsDiv');
                            const isNewDocInput = document.getElementById('is_new_doctor');
                            
                            if (val && val.startsWith('new:')) {
                                // It's a new doctor
                                const newName = val.substring(4);
                                referralNameInput.value = newName;
                                extraDetailsDiv.style.display = 'block'; // Show the extra fields
                                phoneInput.readOnly = false;
                                emailInput.readOnly = false;
                                phoneInput.value = '';
                                emailInput.value = '';
                                phoneInput.focus();
                                isNewDocInput.value = '1';
                            } else if (val) {
                                // Existing doctor
                                referralNameInput.value = selectedOption.text();
                                extraDetailsDiv.style.display = 'block'; // ALWAYS show extra fields
                                isNewDocInput.value = '0';
                                
                                // Auto-fill existing doctor details
                                phoneInput.value = selectedOption.data('phone') || '';
                                emailInput.value = selectedOption.data('email') || '';
                                
                            } else {
                                // Cleared
                                referralNameInput.value = '';
                                extraDetailsDiv.style.display = 'block'; // Keep visible if "Doctor" is still the selected type
                                isNewDocInput.value = '0';
                            }
                        });
                    }
                })
                .catch(err => console.error("Error fetching referred doctors:", err));
        }

        // Patient Card Actions
        window.closePatientCardModal = function() {
            document.getElementById('patientCardModal').classList.add('hidden');
        };

        window.closePatientCardModalOnBackdrop = function(e) {
            if (e.target.id === 'patientCardModal') {
                closePatientCardModal();
            }
        };

        window.printPatientCard = function() {
            const printContent = document.getElementById('printablePatientCard').outerHTML;
            
            const printWindow = window.open('', '_blank', 'width=600,height=600');
            printWindow.document.write('<html><head><title>Patient ID Card</title>');
            printWindow.document.write('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">');
            printWindow.document.write('<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">');
            printWindow.document.write('<style>body{margin:20px; display:flex; justify-content:center; align-items:center; font-family:"Inter", sans-serif;} @media print { body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; } }</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write(printContent);
            printWindow.document.write('<script>window.onload = function() { window.print(); setTimeout(function(){ window.close(); }, 500); }<\/script>');
            printWindow.document.write('</body></html>');
            printWindow.document.close();
        };
    </script>

    <!-- Patient Card Modal -->
    <div id="patientCardModal" class="ref-modal-overlay hidden" onclick="closePatientCardModalOnBackdrop(event)" style="z-index: 2000; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 20px;">
        <!-- Printable Card Div -->
        <div id="printablePatientCard" class="patient-id-card" data-branch="<?php echo htmlspecialchars($_SESSION['hospital_branch'] ?? $_SESSION['branch'] ?? 'Basaveshwaranagar'); ?>" style="width: 100%; max-width: 330px; background: white; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1.5px solid #cbd5e1; overflow: hidden; font-family: 'Inter', sans-serif;" onclick="event.stopPropagation()">
            <!-- Header -->
            <div style="background: #144d34; padding: 14px 18px; color: white; display: flex; align-items: center; justify-content: space-between; border-bottom: 3px solid #f59e0b;">
                <div>
                    <div style="font-weight: 800; font-size: 1.05rem; letter-spacing: 0.5px;">GM HOSPITAL</div>
                    <div style="font-size: 0.68rem; opacity: 0.85; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px;"><?php echo htmlspecialchars($_SESSION['hospital_branch'] ?? $_SESSION['branch'] ?? 'Basaveshwaranagar'); ?></div>
                </div>
                <div style="width: 36px; height: 36px; background: rgba(255,255,255,0.18); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.15rem;">
                    <i class="fas fa-hospital-alt"></i>
                </div>
            </div>
            <!-- Body -->
            <div style="padding: 16px 18px; display: flex; flex-direction: column; gap: 12px; color: #1e293b;">
                <!-- Avatar & Name & Blood Badge -->
                <div style="display: flex; gap: 12px; align-items: center; justify-content: space-between;">
                    <div style="display: flex; gap: 12px; align-items: center; min-width: 0; flex: 1;">
                        <div style="width: 48px; height: 48px; border-radius: 50%; background: #f0fdf4; color: #16a34a; border: 2px solid #bbf7d0; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; font-weight: 800; flex-shrink: 0;" id="cardInitials">
                            P
                        </div>
                        <div style="min-width: 0;">
                            <div id="cardPatientName" style="font-weight: 800; font-size: 1.02rem; color: #0f172a; line-height: 1.2; word-break: break-word;">Patient Name</div>
                            <div id="cardPatientId" style="font-size: 0.78rem; color: #1f6b4a; font-weight: 800; margin-top: 2px;">PID-XXXX</div>
                        </div>
                    </div>
                    <!-- Blood Group Badge in top right -->
                    <div id="cardBloodBadgeWrap" style="flex-shrink: 0; text-align: right;">
                        <span id="cardPatientBloodGroup" style="background: #fee2e2; color: #dc2626; border: 1.5px solid #fca5a5; font-weight: 800; font-size: 0.75rem; padding: 4px 8px; border-radius: 8px; display: inline-flex; align-items: center; gap: 4px;">
                            <i class="fas fa-tint" style="font-size: 0.7rem;"></i> <span id="cardBloodVal">O+</span>
                        </span>
                    </div>
                </div>
                
                <!-- 2-Column Info Grid -->
                <div style="border-top: 1px dashed #e2e8f0; padding-top: 10px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; font-size: 0.75rem;">
                    <div>
                        <span style="display: block; color: #64748b; font-weight: 600; font-size: 0.62rem; text-transform: uppercase; letter-spacing: 0.3px;">Date of Birth</span>
                        <span id="cardPatientDob" style="font-weight: 700; color: #334155;">1990-01-01</span>
                    </div>
                    <div>
                        <span style="display: block; color: #64748b; font-weight: 600; font-size: 0.62rem; text-transform: uppercase; letter-spacing: 0.3px;">Age / Gender</span>
                        <span id="cardPatientAgeGender" style="font-weight: 700; color: #334155;">34 yrs / Male</span>
                    </div>
                    <div>
                        <span style="display: block; color: #64748b; font-weight: 600; font-size: 0.62rem; text-transform: uppercase; letter-spacing: 0.3px;">Phone Number</span>
                        <span id="cardPatientPhone" style="font-weight: 700; color: #334155;">9876543210</span>
                    </div>
                    <div>
                        <span style="display: block; color: #64748b; font-weight: 600; font-size: 0.62rem; text-transform: uppercase; letter-spacing: 0.3px;">Aadhar Number</span>
                        <span id="cardPatientAadhar" style="font-weight: 700; color: #334155;">N/A</span>
                    </div>
                    <div>
                        <span style="display: block; color: #64748b; font-weight: 600; font-size: 0.62rem; text-transform: uppercase; letter-spacing: 0.3px;">City / Location</span>
                        <span id="cardPatientCity" style="font-weight: 700; color: #334155;">Bangalore</span>
                    </div>
                    <div>
                        <span style="display: block; color: #64748b; font-weight: 600; font-size: 0.62rem; text-transform: uppercase; letter-spacing: 0.3px;">Reg. Date</span>
                        <span id="cardPatientRegDate" style="font-weight: 700; color: #334155;">2026-08-24</span>
                    </div>
                </div>

                <!-- QR Code Area (Full Data Scan) -->
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; margin-top: 4px; padding-top: 10px; border-top: 1px solid #f1f5f9; text-align: center;">
                    <div style="background: #ffffff; padding: 6px; border: 1.5px solid #cbd5e1; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.06); width: 142px; height: 142px;">
                        <div id="cardQrCodeContainer" style="width: 130px; height: 130px; display: flex; align-items: center; justify-content: center; margin: 0 auto; background: #ffffff;"></div>
                    </div>
                    <div id="cardBarcodeId" style="font-size: 0.88rem; font-weight: 800; color: #144d34; margin-top: 6px; letter-spacing: 0.5px;">PID-XXXX</div>
                </div>
            </div>
        </div>

        <!-- Footer Buttons -->
        <div style="display: flex; gap: 12px; justify-content: center;" onclick="event.stopPropagation()">
            <button onclick="printPatientCard()" class="btn" style="padding: 10px 20px; border-radius: 10px; font-weight: 600; background: #144d34; color: white; border: none; display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <i class="fas fa-print"></i> Print Card
            </button>
            <button onclick="closePatientCardModal()" class="btn" style="padding: 10px 20px; border-radius: 10px; font-weight: 600; background: white; color: #475569; border: 1px solid #d1d5db; cursor: pointer; font-size: 0.9rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                Close
            </button>
        </div>
    </div>
    
    <?php include 'includes/global_modals.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('action') === 'new') {
                window.history.replaceState({}, document.title, window.location.pathname);
                setTimeout(() => {
                    if (typeof openAddPatientModal === 'function') {
                        openAddPatientModal();
                    }
                }, 300);
            }
        });
    </script>
</body>

</html>
