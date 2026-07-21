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
    <title>Visitor Log - GM HMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
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
            $pageTitle = 'Visitor Management';
            include '../../../includes/reception_navbar.php'; 
            ?>
            
            <!-- Dashboard Content -->
            <div class="reception-content">
                <!-- Page Header -->
                <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;">
                            <i class="fas fa-users"></i> Visitor Log
                        </h1>
                        <p style="color: var(--gray-600);">Track visitors for admitted patients</p>
                    </div>
                    <a href="/GM_HMS/reception_view/ipd_management/public/index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
        
        <div class="table-container">
            <div class="table-header">
                <h2>Visitor Logs</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVisitorModal">
                    <i class="fas fa-plus"></i> Add Visitor
                </button>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-3">
                    <input type="date" class="form-control" id="filterDate" placeholder="Filter by date">
                </div>
            </div>
            
            <table id="visitorsTable" class="table table-striped">
                <thead>
                    <tr><th>ID</th><th>Patient</th><th>Visitor Name</th><th>Relation</th><th>Visit Date</th><th>Time In</th><th>Actions</th></tr>
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
    
    <!-- Add Visitor Modal -->
    <div class="modal fade" id="addVisitorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Visitor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addVisitorForm">
                        <div class="mb-3">
                            <label class="form-label">Admission *</label>
                            <select class="form-select" name="admission_id" id="admissionSelect" required></select>
                        </div>
                        <input type="hidden" name="patient_id" id="patientId">
                        <div class="mb-3">
                            <label class="form-label">Visitor Name *</label>
                            <input type="text" class="form-control" name="visitor_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Relation to Patient</label>
                            <input type="text" class="form-control" name="relation">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Visitor Phone</label>
                            <input type="text" class="form-control" name="visitor_phone">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Visit Date *</label>
                                <input type="date" class="form-control" name="visit_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Time In *</label>
                                <input type="time" class="form-control" name="visit_time" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">ID Proof Type</label>
                            <input type="text" class="form-control" name="id_proof_type" placeholder="e.g., Aadhar, Driving License">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ID Proof Number</label>
                            <input type="text" class="form-control" name="id_proof_number">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveVisitor()">Save Visitor</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="../../public/assets/js/ipd_main.js"></script>
    
    <script>
        let visitorsTable;
        
        $(document).ready(function() {
            visitorsTable = $('#visitorsTable').DataTable({
                ajax: {
                    url: IPD.API_BASE + '/visitors',
                    dataSrc: 'data.visitors'
                },
                columns: [
                    { data: 'visitor_id' },
                    { data: null, render: (data) => `${data.patient_first_name} ${data.patient_last_name} - Bed ${data.bed_number}` },
                    { data: 'visitor_name' },
                    { data: 'relation' },
                    { data: 'visit_date', render: (data) => IPD.formatDate(data) },
                    { data: 'visit_time' },
                    {
                        data: null,
                        render: (data) => `
                            <button class="btn btn-sm btn-danger" onclick="deleteVisitor(${data.visitor_id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        `
                    }
                ],
                order: [[4, 'desc'], [5, 'desc']]
            });
            
            loadActiveAdmissions();
            
            $('#filterDate').change(function() {
                const date = $(this).val();
                visitorsTable.ajax.url(IPD.API_BASE + '/visitors?visit_date=' + date).load();
            });
            
            $('#admissionSelect').change(function() {
                const selectedOption = $(this).find('option:selected');
                const patientId = selectedOption.data('patient-id');
                $('#patientId').val(patientId);
            });
        });
        
        function loadActiveAdmissions() {
            IPD.ajax('admissions?status=Active', 'GET')
                .then(response => {
                    const select = $('#admissionSelect');
                    select.empty().append('<option value="">Select admission...</option>');
                    response.data.admissions.forEach(adm => {
                        select.append(`<option value="${adm.admission_id}" data-patient-id="${adm.patient_id}">
                            ${adm.patient_first_name} ${adm.patient_last_name} - Bed ${adm.bed_number}
                        </option>`);
                    });
                });
        }
        
        function saveVisitor() {
            const formData = {};
            $('#addVisitorForm').serializeArray().forEach(field => {
                formData[field.name] = field.value;
            });
            
            IPD.ajax('visitors', 'POST', formData)
                .then(() => {
                    IPD.toast('Visitor logged successfully', 'success');
                    $('#addVisitorModal').modal('hide');
                    $('#addVisitorForm')[0].reset();
                    visitorsTable.ajax.reload();
                })
                .catch(error => IPD.toast(error.message, 'error'));
        }
        
        function deleteVisitor(id) {
            IPD.confirm('Delete this visitor log?', () => {
                IPD.ajax('visitors?id=' + id, 'DELETE')
                    .then(() => {
                        IPD.toast('Visitor log deleted', 'success');
                        visitorsTable.ajax.reload();
                    });
            });
        }
    </script>
</body>
</html>
