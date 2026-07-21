<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Receptionist', 'admin', 'Admin'])) {
    header("Location: ../receptionist_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OPD Management - GM HMS</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Base CSS -->
    <link rel="stylesheet" href="assets/css/reception_dashboard.css?v=<?= time() ?>">
    <!-- Module CSS -->
    <link rel="stylesheet" href="assets/css/opd_management.css?v=<?= time() ?>">
</head>

<body>

    <div class="reception-layout">

        <!-- Include Sidebar -->
        <?php include 'includes/reception_sidebar.php'; ?>

        <!-- Main Content -->
        <div class="reception-main-content">

            <!-- Include Navbar -->
            <?php
            $pageTitle = 'Out-patients department';
            include 'includes/reception_navbar.php';
            ?>

            <!-- Page Content -->
            <main class="reception-content">

                <!-- 1. Stats Overview -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i class="fas fa-user-injured"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="stat-opd-total">0</h3>
                            <p>Today's OPD</p>
                        </div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-icon success">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="stat-doctors-active">0</h3>
                            <p>Doctors Available</p>
                        </div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-icon warning">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="stat-revenue">0</h3>
                            <p>Today's Revenue</p>
                        </div>
                    </div>
                </div>

                <!-- 2. Live Queue -->
                <div class="card mb-4">
                    <div class="card-header queue-header">
                        <div class="card-title">
                            <i class="fas fa-procedures text-primary"></i>
                            <span>Live Patient Queue</span>
                        </div>
                        <div class="d-flex gap-2">

                            <div class="queue-filters">
                                <button class="filter-btn active" data-filter="all">All</button>
                                <button class="filter-btn" data-filter="Pending" id="tab-pending">Pending (0)</button>
                                <button class="filter-btn" data-filter="Completed" id="tab-completed">Completed
                                    (0)</button>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- Loading State -->
                        <div id="queue-loading" class="text-center p-4">
                            <div class="spinner"></div>
                            <p class="mt-2 text-gray-500">Loading queue...</p>
                        </div>

                        <!-- Queue Grid -->
                        <div class="queue-grid" id="queue-list" style="display: none;">
                            <!-- Queue Items injected via JS -->
                        </div>

                        <!-- Empty State -->
                        <div id="queue-empty" class="empty-state" style="display: none;">
                            <i class="fas fa-clipboard-check"></i>
                            <h3>No patients in queue</h3>
                            <p>All clear for now.</p>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Encounter Modal -->
    <div id="encounterModal" class="modal-overlay hidden" onclick="if(event.target === this) closeModal()">
        <div class="modal-content" onclick="event.stopPropagation()">
            <!-- Header -->
            <div class="modal-header border-0 pb-0" style="padding: 1.5rem 1.5rem 0.5rem 1.5rem;">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div class="d-flex align-items-center" style="gap: 1rem;">
                        <div class="patient-avatar" style="font-size: 2.5rem; color: #1f6b4a;">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div>
                            <h2 class="font-weight-bold mb-0 text-dark" style="font-size: 1.25rem;" id="modal-patient-name">Patient Name</h2>
                            <span class="badge mt-1" style="background: #f1f5f9; color: #475569; font-size: 0.75rem;"><i class="fas fa-id-card-alt mr-1"></i><span id="modal-patient-id">PID-000</span></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="tab-nav">
                <button class="tab-btn active" onclick="switchTab('clinical')">
                    <i class="fas fa-heartbeat mr-2"></i> Clinical & Vitals
                </button>
            </div>

            <div class="modal-body p-4" style="overflow-y: auto; max-height: calc(90vh - 120px);">
                <!-- Tab: Clinical -->
                <div id="tab-clinical" class="tab-content active">
                    <form id="vitals-form">
                        <input type="hidden" name="appointment_id" id="vitals-appt-id">
                        <input type="hidden" name="patient_id" id="vitals-patient-id">
                        <input type="hidden" name="doctor_id" id="vitals-doctor-id">

                        <div class="single-vitals-card">
                            <div class="vitals-row">
                                <div class="vital-item" style="flex: 1.4;">
                                    <label><i class="fas fa-heartbeat text-danger mr-1"></i> BP</label>
                                    <div class="input-wrap">
                                        <input type="text" name="bp_sys" placeholder="120" style="text-align: right; width: 35px; border-radius: 0;">
                                        <span class="mx-1 font-weight-bold text-muted" style="font-size: 1.2rem;">/</span>
                                        <input type="text" name="bp_dia" placeholder="80" style="text-align: left; width: 35px; border-radius: 0;">
                                        <span>mmHg</span>
                                    </div>
                                </div>
                                <div class="vital-item">
                                    <label><i class="fas fa-wave-square text-warning mr-1"></i> Pulse</label>
                                    <div class="input-wrap">
                                        <input type="text" name="pulse" placeholder="72">
                                        <span>bpm</span>
                                    </div>
                                </div>
                                <div class="vital-item">
                                    <label><i class="fas fa-thermometer-half text-warning mr-1"></i> Temp</label>
                                    <div class="input-wrap">
                                        <input type="text" name="temp" placeholder="98.6">
                                        <span>°F</span>
                                    </div>
                                </div>
                                <div class="vital-item">
                                    <label><i class="fas fa-weight text-success mr-1"></i> Weight</label>
                                    <div class="input-wrap">
                                        <input type="text" name="weight" placeholder="65">
                                        <span>kg</span>
                                    </div>
                                </div>
                                <div class="vital-item">
                                    <label><i class="fas fa-lungs text-primary mr-1"></i> SpO2</label>
                                    <div class="input-wrap">
                                        <input type="text" name="spo2" placeholder="98">
                                        <span>%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-0 mt-3">
                            <label class="form-label font-weight-bold text-dark mb-2">Chief Complaint <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="chief_complaint" rows="2"
                                placeholder="Enter patient's main problem or symptoms..." required></textarea>
                        </div>
                    
                        <div class="mt-4 d-flex justify-content-end gap-3 border-top pt-4">
                            <button type="button" class="btn btn-light font-weight-bold px-4" onclick="closeModal()">
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary px-6" id="save-vitals-btn">
                                <i class="fas fa-save mr-2"></i> Save Vitals
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>



    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/reception_utils.js?v=<?= time() ?>"></script>
    <script src="assets/js/opd_management.js?v=<?= time() ?>"></script>
</body>

</html>