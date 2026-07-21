<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Doctor', 'admin', 'Admin'])) {
    header("Location: ../doctor_login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescriptions Management - GM HMS</title>
    <!-- CSS Dependencies -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/doctor_dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/notebook.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.22/css/dataTables.bootstrap4.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Herr+Von+Muellerhoff&display=swap" rel="stylesheet">
    <style>
        .medicine-row {
            background: #f3efe6;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            transition: all 0.2s;
        }
        .medicine-row:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 6px rgba(0,0,0,0.04);
        }
        .medicine-row .form-control {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 0.9rem;
            background: #ffffff;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);
        }
        .medicine-row .form-control:focus {
            border-color: #1f6b4a;
            box-shadow: 0 0 0 3px rgba(31, 107, 74, 0.1);
        }
        .custom-modal-content {
            border: none;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
            overflow: hidden;
        }
        .custom-form-control {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 15px;
            font-size: 0.95rem;
            background-color: #f3efe6;
        }
        .custom-form-control:focus {
            background-color: #ffffff;
            border-color: #1f6b4a;
            box-shadow: 0 0 0 3px rgba(31, 107, 74, 0.1);
        }
        .form-label-custom {
            font-weight: 600;
            color: #475569;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
        }
        .form-label-custom i {
            color: #1f6b4a;
            margin-right: 6px;
        }
    </style>
</head>
<body>

    <!-- Notebook View Overlay -->
    <div id="notebook-view" class="notebook-overlay">
        <div class="notebook-container">
            <!-- Timeline Sidebar -->
            <div class="notebook-sidebar">
                <h5><i class="fas fa-history mr-2"></i>History</h5>
                <div id="notebook-timeline">
                    <!-- Loaded dynamically -->
                </div>
            </div>

            <!-- Notebook Page (Book Spread) -->
            <div class="book-spread">
                <i class="fas fa-times close-notebook" onclick="closeNotebookView()"></i>
                
                <!-- Left Page (Cover/Title) -->
                <div class="book-page left-page" id="book-left">
                    <!-- Dynamic Title Content -->
                </div>

                <!-- Right Page (Details) -->
                <div class="book-page right-page" id="book-right">
                    <!-- Dynamic Detailed Content -->
                </div>
            </div>
        </div>
    </div>

    <div class="doctor-layout">
        <!-- Sidebar -->
        <?php include 'includes/doctor_sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="doctor-main-content">
            <!-- Top Navbar -->
            <?php include 'includes/doctor_navbar.php'; ?>
            
            <div class="doctor-content">
                <div class="welcome-banner fade-in-up" style="display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 2; margin-bottom: 2rem;">
                    <div>
                        <h1 class="welcome-title"><i class="fas fa-prescription"></i> Prescription Management</h1>
                        <p class="welcome-subtitle">Create and manage digital prescriptions</p>
                    </div>
                    <button class="btn btn-outline" data-toggle="modal" data-target="#addPrescriptionModal" onclick="addMedicineRow()" style="border-color: rgba(255,255,255,0.2); color: white;">
                        <i class="fas fa-plus mr-2"></i> New Prescription
                    </button>
                    <i class="fas fa-pills welcome-icon-bg"></i>
                </div>

                <!-- Prescriptions Table -->
                <div class="bento-card fade-in-up delay-1">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="prescriptions-table">
                    <thead class="thead-light">
                        <tr>
                            <th>Prescription ID</th>
                            <th>Patient</th>
                            <th>Date</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody id="prescriptions-table-body">
                        <!-- Loaded dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Prescription Modal -->
<div class="modal fade" id="addPrescriptionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content custom-modal-content">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%); border: none; padding: 1.5rem;">
                <h5 class="modal-title" style="font-weight: 700; display: flex; align-items: center; gap: 0.75rem;">
                    <div style="background: rgba(255,255,255,0.2); padding: 0.5rem; border-radius: 0.5rem;"><i class="fas fa-prescription"></i></div>
                    Create New Prescription
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" style="opacity: 0.8; text-shadow: none;">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 2rem;">
                <form id="add-prescription-form">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="form-label-custom"><i class="fas fa-user"></i>Patient</label>
                            <select class="form-control custom-form-control" id="patient_select" required>
                                <option value="">Select Patient...</option>
                                <!-- Populated dynamically -->
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="form-label-custom"><i class="fas fa-stethoscope"></i>Diagnosis</label>
                            <input type="text" class="form-control custom-form-control" id="diagnosis" placeholder="e.g. Fever, Infection">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4 mb-3" style="border-bottom: 2px solid #f1f5f9; padding-bottom: 0.75rem;">
                        <h6 style="margin: 0; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-pills" style="color: #1f6b4a;"></i> Medicines & Dosage
                        </h6>
                        <button type="button" class="btn btn-sm" id="add-medicine-row" style="background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; border-radius: 0.5rem; font-weight: 600;">
                            <i class="fas fa-plus"></i> Add Row
                        </button>
                    </div>

                    <div id="medicine-rows-container" style="display: flex; flex-direction: column; gap: 1rem;">
                        <!-- Medicine rows go here -->
                    </div>

                    <div class="row mt-4 pt-3" style="border-top: 2px solid #f1f5f9;">
                        <div class="col-md-6 form-group">
                            <label class="form-label-custom"><i class="fas fa-clipboard-list"></i>General Instructions</label>
                            <textarea class="form-control custom-form-control" id="general_instructions" rows="3" placeholder="Rest instructions..."></textarea>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="form-label-custom"><i class="fas fa-apple-alt"></i>Dietary Advice</label>
                            <textarea class="form-control custom-form-control" id="dietary_advice" rows="3" placeholder="e.g. Avoid spicy food"></textarea>
                        </div>
                    </div>

                    <div class="form-group mt-2">
                        <label class="form-label-custom"><i class="fas fa-calendar-check"></i>Follow-up Date</label>
                        <input type="date" class="form-control custom-form-control" id="follow_up_date" style="max-width: 200px;">
                    </div>

                    <div class="text-right mt-5">
                        <button type="button" class="btn btn-light px-4 py-2" data-dismiss="modal" style="border-radius: 0.5rem; font-weight: 600; margin-right: 1rem;">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 py-2" style="background: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%); border: none; border-radius: 0.5rem; font-weight: 600; box-shadow: 0 4px 6px -1px rgba(31, 107, 74, 0.4);">Save & Issue</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

    <!-- View Prescription Modal -->
    <div class="modal fade" id="viewPrescriptionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Prescription Details</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="viewPrescriptionModalBody">
                    <!-- Content loaded dynamically -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Print</button>
                </div>
            </div>
        </div>
    </div>

    </div> <!-- End .doctor-main-content -->
</div> <!-- End .doctor-layout -->

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.22/js/dataTables.bootstrap4.min.js"></script>
<!-- Moment.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<!-- Custom Logic -->
<script src="assets/js/doctor_utils.js"></script>
<script src="assets/js/prescription.js"></script>

</body>
</html>
