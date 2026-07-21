<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Receptionist', 'admin', 'Admin'])) {
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
    <title>Patient Prescriptions & Consultation History - GM HMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/reception_dashboard.css">
    <link rel="stylesheet" href="assets/css/prescription_view.css">
    
    <style>
        .search-container {
            background: #FBF9F3;
            border: 1.5px solid #E2E0D6;
            padding: 1.25rem 1.5rem;
            border-radius: 14px;
            box-shadow: 0 4px 14px rgba(20, 77, 52, 0.05);
            margin-bottom: 1.5rem;
        }
        
        .search-wrapper {
            display: flex;
            gap: 0.85rem;
        }
        
        .search-input-group {
            flex: 1;
            position: relative;
        }
        
        .search-input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #144D34;
        }
        
        .search-input {
            width: 100%;
            padding: 0.65rem 1rem 0.65rem 2.8rem;
            border: 1.5px solid #DEDACF;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            color: #144D34;
            background: #FFFFFF;
            transition: all 0.2s;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #144D34;
            box-shadow: 0 0 0 3px rgba(20, 77, 52, 0.12);
        }

        .vital-pill {
            background: #E8F4EC;
            border: 1px solid #C6E6D2;
            color: #144D34;
            padding: 3px 9px;
            border-radius: 6px;
            font-size: 0.76rem;
            font-weight: 600;
        }

        .vital-pill strong {
            color: #0E3826;
        }

        .row-abnormal {
            background: #FEF2F2 !important;
            color: #991B1B !important;
            font-weight: 700 !important;
        }

        .history-list {
            margin-top: 1rem;
        }

        .global-grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(460px, 1fr));
            gap: 1.15rem;
        }

        .global-card {
            background: #FBF9F3;
            border: 1.5px solid #E2E0D6;
            border-radius: 12px;
            padding: 1.1rem 1.25rem;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .global-card:hover {
            transform: translateY(-2px);
            border-color: #144D34;
            box-shadow: 0 10px 25px rgba(20, 77, 52, 0.1);
        }

        .global-card button[onclick*="selectPatientFromGlobal"] {
            background-color: #144D34 !important;
            color: #FFFFFF !important;
            font-weight: 800 !important;
        }

        .global-card button[onclick*="selectPatientFromGlobal"] * {
            color: #FFFFFF !important;
        }

        .global-pill.active {
            background: #144D34 !important;
            color: #FFFFFF !important;
        }

        .no-records {
            text-align: center;
            padding: 3rem 1.5rem;
            color: #557365;
            background: #FBF9F3;
            border: 1.5px dashed #DEDACF;
            border-radius: 14px;
            margin-top: 1rem;
        }

        /* Image & PDF Viewer Modals */
        .viewer-modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(5px);
            z-index: 3000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .viewer-modal-card {
            background: #F4F1EA;
            border-radius: 14px;
            border: 1.5px solid #DEDACF;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.4);
            max-width: 90%;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .viewer-modal-header {
            background: #F4F1EA;
            padding: 0.85rem 1.25rem;
            border-bottom: 1px solid #E2DDD0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .viewer-modal-header h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 800;
            color: #144D34;
        }

        .viewer-modal-body {
            padding: 1rem;
            overflow: auto;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #EBE7DC;
            min-height: 350px;
        }
    </style>
</head>
<body>
    <div class="reception-layout">
        <!-- Sidebar -->
        <?php include 'includes/reception_sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="reception-main-content">
            <!-- Top Navbar -->
            <?php include 'includes/reception_navbar.php'; ?>
            
            <div class="reception-content">
                <div class="page-header mb-4">
                    <h1 class="page-title" style="color: #144D34; font-weight: 800;">
                        <i class="fas fa-file-medical" style="color: #144D34;"></i>
                        Patient Prescriptions & Clinical Timeline
                    </h1>
                    <p class="page-subtitle" style="color: #557365;">Search and view full consultation history, lab reports, and doctor prescriptions</p>
                </div>

                <!-- Search Section -->
                <div class="search-container">
                    <div class="search-wrapper">
                        <div class="search-input-group">
                            <i class="fas fa-search"></i>
                            <input type="text" id="patient-id-input" class="search-input" placeholder="Enter Patient ID, UHID, or Mobile Number (e.g. PID-1001)">
                        </div>
                        <button onclick="searchPrescription()" class="btn btn-primary" id="search-btn" style="background:#144D34 !important; color:#FFFFFF !important; border:none; padding:0.65rem 1.4rem; border-radius:8px; font-weight:700;">
                            <i class="fas fa-search mr-2" style="color:#FFFFFF !important;"></i>
                            <span style="color:#FFFFFF !important;">Search History</span>
                        </button>
                    </div>
                </div>

                <!-- Results Section (Patient Timeline View) -->
                <div id="results-section" style="display: none; margin-bottom: 2rem;">
                    <!-- Patient Summary Card -->
                    <div id="patient-summary-card"></div>

                    <h3 class="section-title mb-3" style="color:#144D34; font-size:1.15rem; font-weight:800; display:flex; justify-content:space-between; align-items:center;">
                        <span><i class="fas fa-stream"></i> Consultation History Timeline</span>
                        <button onclick="showGlobalPrescriptionsView()" class="btn btn-sm btn-outline" style="font-size:0.78rem; font-weight:700;">
                            <i class="fas fa-arrow-left"></i> Back to Recent Global List
                        </button>
                    </h3>
                    <div id="prescription-history-list" class="history-list">
                        <!-- Consultation Cards loaded dynamically -->
                    </div>
                </div>

                <!-- Global Recent Prescriptions (Advance UI/UX Layout) -->
                <div id="all-prescriptions-section">
                    <!-- KPI Summary Stats Bar -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                        <div style="background: #FBF9F3; border: 1.5px solid #E2E0D6; border-radius: 12px; padding: 0.9rem 1.1rem; display: flex; align-items: center; gap: 0.85rem;">
                            <div style="width: 44px; height: 44px; border-radius: 10px; background: #E8F4EC; color: #144D34; border: 1px solid #C6E6D2; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                                <i class="fas fa-file-medical"></i>
                            </div>
                            <div>
                                <div style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: #557365;">Total Recent Records</div>
                                <div id="kpi-total-count" style="font-size: 1.35rem; font-weight: 900; color: #144D34;">--</div>
                            </div>
                        </div>

                        <div style="background: #FBF9F3; border: 1.5px solid #E2E0D6; border-radius: 12px; padding: 0.9rem 1.1rem; display: flex; align-items: center; gap: 0.85rem;">
                            <div style="width: 44px; height: 44px; border-radius: 10px; background: #E8F4EC; color: #144D34; border: 1px solid #C6E6D2; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                            <div>
                                <div style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: #557365;">Today's Consultations</div>
                                <div id="kpi-today-count" style="font-size: 1.35rem; font-weight: 900; color: #144D34;">--</div>
                            </div>
                        </div>

                        <div style="background: #FBF9F3; border: 1.5px solid #E2E0D6; border-radius: 12px; padding: 0.9rem 1.1rem; display: flex; align-items: center; gap: 0.85rem;">
                            <div style="width: 44px; height: 44px; border-radius: 10px; background: #E8F4EC; color: #144D34; border: 1px solid #C6E6D2; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                                <i class="fas fa-file-signature"></i>
                            </div>
                            <div>
                                <div style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: #557365;">Handwritten Uploads</div>
                                <div id="kpi-image-count" style="font-size: 1.35rem; font-weight: 900; color: #144D34;">--</div>
                            </div>
                        </div>

                        <div style="background: #FBF9F3; border: 1.5px solid #E2E0D6; border-radius: 12px; padding: 0.9rem 1.1rem; display: flex; align-items: center; gap: 0.85rem;">
                            <div style="width: 44px; height: 44px; border-radius: 10px; background: #E8F4EC; color: #144D34; border: 1px solid #C6E6D2; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                                <i class="fas fa-pills"></i>
                            </div>
                            <div>
                                <div style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: #557365;">Medication Plans</div>
                                <div id="kpi-meds-count" style="font-size: 1.35rem; font-weight: 900; color: #144D34;">--</div>
                            </div>
                        </div>
                    </div>

                    <!-- Global Filter & View Toolbar -->
                    <div style="background: #F4F1EA; border: 1.5px solid #DEDACF; border-radius: 12px; padding: 0.75rem 1rem; margin-bottom: 1.15rem; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 0.6rem; flex: 1; min-width: 260px;">
                            <div style="position: relative; width: 100%;">
                                <i class="fas fa-filter" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #144D34; font-size: 0.85rem;"></i>
                                <input type="text" id="global-search-filter" placeholder="Filter recent list by patient, doctor, ID, diagnosis..." onkeyup="filterGlobalList()" style="width: 100%; padding: 0.4rem 0.6rem 0.4rem 2.2rem; border: 1.5px solid #DEDACF; border-radius: 6px; font-size: 0.85rem; font-weight: 600; color: #144D34;">
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            <div class="filter-pills" style="display: flex; gap: 4px; background: #EBE7DC; padding: 3px; border-radius: 6px;">
                                <button type="button" class="btn btn-xs global-pill active" onclick="setGlobalFilter('all', this)" style="padding: 3px 10px; font-weight: 700; font-size: 0.75rem; border-radius: 4px;">All</button>
                                <button type="button" class="btn btn-xs global-pill" onclick="setGlobalFilter('image', this)" style="padding: 3px 10px; font-weight: 700; font-size: 0.75rem; border-radius: 4px;">With Image</button>
                                <button type="button" class="btn btn-xs global-pill" onclick="setGlobalFilter('meds', this)" style="padding: 3px 10px; font-weight: 700; font-size: 0.75rem; border-radius: 4px;">With Medicines</button>
                            </div>

                            <div style="display: flex; gap: 2px; background: #EBE7DC; padding: 3px; border-radius: 6px; margin-left: 6px;">
                                <button type="button" id="view-grid-btn" class="btn btn-xs active" onclick="setGlobalViewMode('grid')" title="Card Grid View" style="padding: 3px 8px; font-size: 0.8rem;"><i class="fas fa-th-large"></i></button>
                                <button type="button" id="view-table-btn" class="btn btn-xs" onclick="setGlobalViewMode('table')" title="Compact Table View" style="padding: 3px 8px; font-size: 0.8rem;"><i class="fas fa-list"></i></button>
                            </div>

                            <button onclick="loadAllPrescriptions()" class="btn btn-sm btn-outline" style="padding: 4px 10px; font-size: 0.78rem; font-weight: 700; border-radius: 6px; margin-left: 4px;">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>

                    <div id="all-prescriptions-list" class="history-list">
                        <div class="no-records">
                            <i class="fas fa-spinner fa-spin fa-2x" style="color:#144D34;"></i>
                            <p style="margin-top:0.5rem; font-weight:700;">Loading recent records...</p>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div id="empty-state" class="no-records" style="display:none;">
                    <div style="font-size: 3.5rem; opacity: 0.2; margin-bottom: 0.5rem; color:#144D34;">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <h3 style="color:#144D34; font-weight:800; font-size:1.2rem;">No prescriptions found</h3>
                    <p style="color:#557365; font-size:0.9rem;">Please double check the Patient ID or mobile number and try again.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Handwritten Prescription Image Viewer & Zoom -->
    <div id="image-viewer-modal" class="viewer-modal-overlay" style="display: none;">
        <div class="viewer-modal-card" style="width: 850px;">
            <div class="viewer-modal-header">
                <h3><i class="fas fa-file-signature"></i> Handwritten Prescription Preview</h3>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <button onclick="zoomImage(0.2)" class="btn btn-xs btn-outline" title="Zoom In"><i class="fas fa-search-plus"></i></button>
                    <button onclick="zoomImage(-0.2)" class="btn btn-xs btn-outline" title="Zoom Out"><i class="fas fa-search-minus"></i></button>
                    <button onclick="resetZoom()" class="btn btn-xs btn-outline" title="Reset Zoom"><i class="fas fa-sync-alt"></i></button>
                    <a id="image-download-link" href="#" download class="btn btn-xs btn-primary" style="background:#144D34 !important; border:none; color:#FFFFFF !important;">
                        <i class="fas fa-download" style="color:#FFFFFF !important;"></i> <span style="color:#FFFFFF !important;">Download Image</span>
                    </a>
                    <button onclick="closeImageModal()" class="btn btn-xs btn-outline" style="border:none; font-size:1.2rem; margin-left:8px; cursor:pointer;">✕</button>
                </div>
            </div>
            <div class="viewer-modal-body">
                <img id="modal-preview-image" src="" alt="Handwritten Prescription" style="max-width:100%; max-height:75vh; transition:transform 0.2s ease; border-radius:8px; box-shadow:0 10px 30px rgba(0,0,0,0.2);">
            </div>
        </div>
    </div>

    <!-- Modal for Lab Report PDF Viewer -->
    <div id="pdf-viewer-modal" class="viewer-modal-overlay" style="display: none;">
        <div class="viewer-modal-card" style="width: 900px; height: 85vh;">
            <div class="viewer-modal-header">
                <h3><i class="fas fa-file-pdf text-red-600"></i> Laboratory Report PDF Viewer</h3>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <a id="pdf-download-link" href="#" download class="btn btn-xs btn-primary" style="background:#144D34 !important; border:none; color:#FFFFFF !important;">
                        <i class="fas fa-download" style="color:#FFFFFF !important;"></i> <span style="color:#FFFFFF !important;">Download Report PDF</span>
                    </a>
                    <button onclick="closePdfModal()" class="btn btn-xs btn-outline" style="border:none; font-size:1.2rem; margin-left:8px; cursor:pointer;">✕</button>
                </div>
            </div>
            <div class="viewer-modal-body" style="padding:0; background:#fff;">
                <iframe id="pdf-modal-iframe" src="" style="width:100%; height:100%; border:none;"></iframe>
            </div>
        </div>
    </div>

    <!-- Modal for Professional A4 Prescription View (Existing) -->
    <div id="prescription-modal" class="modal-overlay" style="display: none;">
        <div class="modal-container">
            <div class="modal-header no-print">
                <h3><i class="fas fa-print"></i> Professional A4 Print Preview</h3>
                <div style="display: flex; gap: 0.5rem;">
                    <button onclick="window.print()" class="btn btn-primary" style="background:#144D34 !important; color:#FFFFFF !important; border:none; font-weight:700;">
                        <i class="fas fa-print" style="color:#FFFFFF !important; margin-right:4px;"></i> Print
                    </button>
                    <button onclick="closePrescriptionModal()" class="btn btn-outline" style="background:#FFFFFF !important; color:#144D34 !important; border:1.5px solid #144D34 !important; font-weight:700;">
                        <i class="fas fa-times" style="color:#144D34 !important; margin-right:4px;"></i> Close
                    </button>
                </div>
            </div>
            
            <div class="modal-body">
                <!-- A4 Prescription Layout -->
                <div id="professional-prescription-a4" class="prescription-a4">
                    <!-- Loaded dynamically via JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="assets/js/reception_utils.js"></script>
    <script src="assets/js/prescriptions.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof setPageTitle === 'function') {
            setPageTitle('Patient Prescriptions', 'Clinical timeline, prescriptions, and lab history');
        }
    });
    </script>
</body>
</html>
