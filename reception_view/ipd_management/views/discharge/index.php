<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Receptionist', 'admin', 'Admin'])) {
    header("Location: ../../../../receptionist_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discharge Management - GM HMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <!-- Reception Dashboard CSS -->
    <link rel="stylesheet" href="../../../assets/css/reception_dashboard.css">
    
    <!-- Custom IPD CSS -->
    <link rel="stylesheet" href="../../public/assets/css/ipd_main.css">
</head>
<body>
    <div class="reception-layout">
        <!-- Sidebar -->
        <?php include '../../../includes/reception_sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="reception-main-content">
            <!-- Top Navbar -->
            <?php 
            $pageTitle = 'Patient Discharge';
            include '../../../includes/reception_navbar.php'; 
            ?>
            
            <!-- Dashboard Content -->
            <div class="reception-content">
                <!-- Page Header -->
                <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;">
                            <i class="fas fa-file-medical"></i> Discharge Management
                        </h1>
                        <p style="color: var(--gray-600);">Create discharge summaries and manage patient discharge</p>
                    </div>
                    <a href="/GM_HMS/reception_view/ipd_management/public/index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
        
        <div class="table-container">
            <h3>Select Admission to Discharge</h3>
            <select class="form-select mb-4" id="admissionSelect">
                <option value="">Select an active admission...</option>
            </select>
            
            <div id="dischargeForm" style="display:none;">
                <form id="createDischargeForm">
                    <input type="hidden" name="admission_id" id="dischargeAdmissionId">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Discharge Date *</label>
                            <input type="datetime-local" class="form-control" name="discharge_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Discharge Type *</label>
                            <select class="form-select" name="discharge_type" required>
                                <option value="Normal">Normal</option>
                                <option value="Against Medical Advice">Against Medical Advice</option>
                                <option value="Transferred">Transferred</option>
                                <option value="Deceased">Deceased</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Discharged By (Doctor) *</label>
                            <select class="form-select" name="discharged_by_doctor_id" id="doctorSelect" required></select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Follow-up Date</label>
                            <input type="date" class="form-control" name="follow_up_date">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Final Diagnosis</label>
                            <textarea class="form-control" name="final_diagnosis" rows="3"></textarea>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Discharge Summary</label>
                            <textarea class="form-control" name="discharge_summary" rows="4"></textarea>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Treatment Given</label>
                            <textarea class="form-control" name="treatment_given" rows="3"></textarea>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Follow-up Instructions</label>
                            <textarea class="form-control" name="follow_up_instructions" rows="3"></textarea>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Medications Prescribed</label>
                            <textarea class="form-control" name="medications_prescribed" rows="3"></textarea>
                        </div>
                    </div>
                    <button type="button" class="btn btn-success btn-lg" onclick="createDischarge()">
                        <i class="fas fa-check"></i> Complete Discharge
                    </button>
                </form>
            </div>
        </div>
        </div>
            </div>
            <!-- End Reception Content -->
        </div>
        <!-- End Reception Main Content -->
    </div>
    <!-- End Reception Layout -->
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../../public/assets/js/ipd_main.js"></script>
    
    <script>
        let currentAdmissions = [];

        $(document).ready(function() {
            loadActiveAdmissions();
            IPD.initDoctorSearch('#doctorSelect');
            
            $('#admissionSelect').change(function() {
                const admissionId = $(this).val();
                if (admissionId) {
                    $('#dischargeAdmissionId').val(admissionId);
                    
                    // Find selected admission and populate doctor
                    const admission = currentAdmissions.find(a => a.admission_id == admissionId);
                    
                    // Reset doctor selector
                    $('#doctorSelect').empty();
                    if ($('#doctorSelect').data('select2')) {
                        $('#doctorSelect').select2('destroy');
                    }
                    
                    if (admission && admission.admitting_doctor_id) {
                        // Add single option
                        const newOption = new Option(admission.doctor_name, admission.admitting_doctor_id, true, true);
                        $('#doctorSelect').append(newOption);
                        
                        // Re-init as basic select (no ajax search) to restrict choice
                        $('#doctorSelect').select2({
                            minimumResultsForSearch: Infinity // Disable search box
                        });
                    } else {
                        // Fallback if no doctor (shouldn't happen ideally)
                         $('#doctorSelect').select2({
                            placeholder: 'No doctor found',
                            disabled: true
                        });
                    }
                    
                    $('#dischargeForm').show();
                } else {
                    $('#dischargeForm').hide();
                }
            });
        });
        
        function loadActiveAdmissions() {
            IPD.ajax('admissions?status=Admitted', 'GET')
                .then(response => {
                    currentAdmissions = response.data.admissions;
                    const select = $('#admissionSelect');
                    select.empty().append('<option value="">Select an active admission...</option>');
                    response.data.admissions.forEach(adm => {
                        select.append(`<option value="${adm.admission_id}">
                            ${adm.patient_name} - Bed ${adm.bed_number} (Admitted: ${IPD.formatDate(adm.admission_date)})
                        </option>`);
                    });
                });
        }
        
        function createDischarge() {
            const formData = {};
            $('#createDischargeForm').serializeArray().forEach(field => {
                formData[field.name] = field.value;
            });
            
            const admissionId = formData.admission_id;
            
            // First create discharge record
            IPD.ajax('discharge', 'POST', formData)
                .then(() => {
                    // Then discharge the admission
                    return IPD.ajax('admissions?action=discharge', 'POST', {
                        admission_id: admissionId,
                        discharge_date: formData.discharge_date
                    });
                })
                .then(() => {
                    IPD.toast('Patient discharged successfully!', 'success');
                    $('#createDischargeForm')[0].reset();
                    $('#dischargeForm').hide();
                    loadActiveAdmissions();
                })
                .catch(error => IPD.toast(error.message || 'Failed to discharge patient', 'error'));
        }
    </script>
</body>
</html>
