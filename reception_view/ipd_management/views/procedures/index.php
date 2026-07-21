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
    <title>Procedures - GM HMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- Select2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    
    <!-- Toastify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    
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
            $pageTitle = 'Medical Procedures';
            include '../../../includes/reception_navbar.php'; 
            ?>
            
            <!-- Dashboard Content -->
            <div class="reception-content">
                <!-- Page Header -->
                <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;">
                            <i class="fas fa-procedures"></i> Medical Procedures
                        </h1>
                        <p style="color: var(--gray-600);">Record procedures performed during IPD admissions</p>
                    </div>
                    <a href="/GM_HMS/reception_view/ipd_management/public/index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
        
        <div class="table-container">
            <div class="table-header">
                <h2>Procedures</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProcedureModal">
                    <i class="fas fa-plus"></i> Add Procedure
                </button>
            </div>
            <table id="proceduresTable" class="table table-striped">
                <thead>
                    <tr><th>ID</th><th>Patient</th><th>Procedure</th><th>Doctor</th><th>Date</th><th>Cost</th><th>Actions</th></tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        </div>
            </div>
            <!-- End Reception Content -->
        </div>
        <!-- End Reception Main Content -->
    </div>
    <!-- End Reception Layout -->
    
    <!-- Add Procedure Modal -->
    <div class="modal fade" id="addProcedureModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Procedure</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addProcedureForm">
                        <div class="mb-3">
                            <label class="form-label">Admission *</label>
                            <select class="form-select" name="admission_id" id="admissionSelect" required></select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Procedure Name *</label>
                            <input type="text" class="form-control" name="procedure_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Procedure Code</label>
                            <input type="text" class="form-control" name="procedure_code">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Performed By (Doctor) *</label>
                            <select class="form-select" name="performed_by_doctor_id" id="doctorSelect" required></select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Procedure Date *</label>
                                <input type="datetime-local" class="form-control" name="procedure_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Duration (minutes)</label>
                                <input type="number" class="form-control" name="duration_minutes">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cost</label>
                            <input type="number" class="form-control" name="cost" step="0.01">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Procedure Notes</label>
                            <textarea class="form-control" name="procedure_notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveProcedure()">Save Procedure</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="../../public/assets/js/ipd_main.js"></script>
    
    <script>
        let proceduresTable;
        
        $(document).ready(function() {
            proceduresTable = $('#proceduresTable').DataTable({
                ajax: {
                    url: IPD.API_BASE + '/procedures',
                    dataSrc: 'data.procedures'
                },
                columns: [
                    { data: 'sl_no', defaultContent: '-' },
                    { data: 'patient_name', defaultContent: '-' },
                    { data: 'procedure_name' },
                    { data: 'doctor_name' },
                    { data: 'procedure_date', render: (data) => IPD.formatDateTime(data) },
                    { data: 'charges', render: (data) => IPD.formatCurrency(data || 0), defaultContent: '₹0.00' },
                    {
                        data: null,
                        render: (data) => `
                            <button class="btn btn-sm btn-danger" onclick="deleteProcedure(${data.sl_no})">
                                <i class="fas fa-trash"></i>
                            </button>
                        `
                    }
                ]
            });
            
            loadActiveAdmissions();
            // Initialize Select2 after ensuring it's loaded
            setTimeout(function() {
                if (typeof $.fn.select2 !== 'undefined') {
                    IPD.initDoctorSearch('#doctorSelect');
                }
            }, 200);
        });
        
        function loadActiveAdmissions() {
            IPD.ajax('admissions?status=Admitted', 'GET')
                .then(response => {
                    const select = $('#admissionSelect');
                    select.empty().append('<option value="">Select admission...</option>');
                    response.data.admissions.forEach(adm => {
                        select.append(`<option value="${adm.admission_id}">
                            ${adm.patient_first_name} ${adm.patient_last_name} - Bed ${adm.bed_number}
                        </option>`);
                    });
                });
        }
        
        function saveProcedure() {
            const formData = {};
            $('#addProcedureForm').serializeArray().forEach(field => {
                formData[field.name] = field.value;
            });
            
            IPD.ajax('procedures', 'POST', formData)
                .then(() => {
                    IPD.toast('Procedure added successfully', 'success');
                    $('#addProcedureModal').modal('hide');
                    $('#addProcedureForm')[0].reset();
                    proceduresTable.ajax.reload();
                })
                .catch(error => IPD.toast(error.message, 'error'));
        }
        
        function deleteProcedure(id) {
            IPD.confirm('Delete this procedure?', () => {
                IPD.ajax('procedures?id=' + id, 'DELETE')
                    .then(() => {
                        IPD.toast('Procedure deleted', 'success');
                        proceduresTable.ajax.reload();
                    });
            });
        }
    </script>
</body>
</html>
