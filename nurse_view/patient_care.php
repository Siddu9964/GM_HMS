<?php
session_start();

// Check authentication
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Nurse', 'admin', 'Admin'])) {
    header('Location: ../login.php');
    exit();
}

$nurseId = $_SESSION['user_id'] ?? null;
$nurseName = $_SESSION['username'] ?? 'Nurse';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Care - GM HMS</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        :root {
            --primary: #4A90E2;
            --primary-dark: #357ABD;
            --success: #28A745;
            --warning: #FFC107;
            --danger: #DC3545;
            --info: #17A2B8;
            --light: #F8F9FA;
            --dark: #343A40;
        }

        body {
            background: #F5F7FA;
            min-height: 100vh;
            display: flex;
        }

        .main-layout {
            display: flex;
            width: 100%;
        }

        .content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .main-content {
            flex: 1;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .page-header h1 {
            font-size: 24px;
            color: var(--dark);
            font-weight: 700;
        }

        .patient-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .patient-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-left: 5px solid var(--primary);
            transition: transform 0.2s;
        }

        .patient-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .patient-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .patient-avatar {
            width: 50px;
            height: 50px;
            background: #E3F2FD;
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 700;
        }

        .patient-details h3 {
            font-size: 18px;
            color: var(--dark);
            margin-bottom: 2px;
        }

        .patient-details p {
            font-size: 13px;
            color: #6C757D;
        }

        .admission-info {
            background: #F8F9FA;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            font-size: 13px;
        }

        .info-label {
            color: #6C757D;
            display: block;
            margin-bottom: 2px;
        }

        .info-value {
            color: var(--dark);
            font-weight: 600;
        }

        .card-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .btn-sm {
            padding: 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: 0.2s;
        }

        .btn-outline {
            border: 1px solid var(--primary);
            color: var(--primary);
            background: white;
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .loading,
        .empty-state {
            text-align: center;
            padding: 50px;
            grid-column: 1 / -1;
            color: #6C757D;
        }

        /* Search Styles */
        .search-container {
            display: flex;
            gap: 10px;
            background: white;
            padding: 5px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #E2E8F0;
            width: 100%;
            max-width: 450px;
        }

        .search-input {
            flex: 1;
            border: none;
            padding: 10px 15px;
            font-size: 14px;
            outline: none;
            border-radius: 8px;
        }

        .search-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-btn:hover {
            background: var(--primary-dark);
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .search-container {
                max-width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="main-layout">
        <!-- Sidebar -->
        <?php include 'includes/nurse_sidebar.php'; ?>

        <!-- Main Content Area -->
        <div class="content-wrapper">
            <!-- Navbar -->
            <?php include 'includes/nurse_navbar.php'; ?>

            <!-- Page Content -->
            <div class="main-content">
                <div class="container">
                    <div class="page-header">
                        <h1>All Admitted Patients</h1>
                        <div class="search-container">
                            <input type="text" id="patientSearch" class="search-input" placeholder="Search by name, ID, room or ward...">
                            <button id="searchBtn" class="search-btn">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </div>

                    <div class="patient-grid" id="patientList">
                        <div class="loading">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p>Loading admitted patients...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let allPatients = [];

        async function loadPatients() {
            const container = document.getElementById('patientList');
            try {
                const response = await fetch('api/dashboard.php');
                const result = await response.json();

                if (!result.success) {
                    container.innerHTML = `
                        <div class="empty-state" style="grid-column:1/-1;">
                            <i class="fas fa-exclamation-triangle" style="color:var(--danger);font-size:40px;margin-bottom:12px;display:block;"></i>
                            <h3>API Error</h3>
                            <p>${result.message || 'Could not load patient data.'}</p>
                        </div>`;
                    return;
                }

                allPatients = result.data.assigned_patients || [];
                renderPatients(allPatients);
                
            } catch (error) {
                console.error('Error:', error);
                container.innerHTML = `
                    <div class="empty-state" style="grid-column:1/-1;">
                        <i class="fas fa-wifi" style="color:var(--danger);font-size:40px;margin-bottom:12px;display:block;"></i>
                        <h3>Connection Error</h3>
                        <p>Could not reach the server. Please refresh the page.</p>
                    </div>`;
            }
        }

        function renderPatients(patients) {
            const container = document.getElementById('patientList');
            
            if (patients && patients.length > 0) {
                container.innerHTML = patients.map(p => `
                    <div class="patient-card">
                        <div class="patient-info">
                            <div class="patient-avatar">${p.first_name ? p.first_name.charAt(0).toUpperCase() : '?'}</div>
                            <div class="patient-details">
                                <h3>${p.first_name} ${p.last_name}</h3>
                                <p>ID: ${p.patient_id} &nbsp;|&nbsp; ${p.age}y &nbsp;|&nbsp; ${p.sex}
                                   ${p.blood_group ? '&nbsp;|&nbsp; <strong>' + p.blood_group + '</strong>' : ''}
                                </p>
                            </div>
                        </div>
                        <div class="admission-info">
                            <div>
                                <span class="info-label">Room / Bed</span>
                                <span class="info-value">${p.room_number ? 'R: ' + p.room_number : '—'} | B: ${p.bed_number || '—'}</span>
                            </div>
                            <div>
                                <span class="info-label">Ward / Type</span>
                                <span class="info-value">${p.room_type || p.room_name || '—'}</span>
                            </div>
                            <div>
                                <span class="info-label">Floor</span>
                                <span class="info-value">${p.floor_name || '—'}</span>
                            </div>
                            <div>
                                <span class="info-label">Admitted</span>
                                <span class="info-value">${p.admission_date ? new Date(p.admission_date).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'}</span>
                            </div>
                            <div style="grid-column: 1 / -1">
                                <span class="info-label">Diagnosis</span>
                                <span class="info-value">${p.diagnosis || 'General Observation'}</span>
                            </div>
                            ${p.doctor_name ? `<div style="grid-column: 1 / -1"><span class="info-label">Doctor</span><span class="info-value"><i class="fas fa-user-md" style="color:var(--primary);margin-right:4px;"></i>${p.doctor_name}</span></div>` : ''}
                        </div>
                        <div class="card-actions">
                            <button onclick="navigateWithPatient('vitals.php', '${p.patient_id}', '${p.admission_id}')" class="btn-sm btn-outline">
                                <i class="fas fa-heartbeat"></i> Vitals
                            </button>
                            <button onclick="navigateWithPatient('medication.php', '${p.patient_id}', '${p.admission_id}')" class="btn-sm btn-outline">
                                <i class="fas fa-pills"></i> Meds
                            </button>
                            <button onclick="navigateWithPatient('nurse_notes.php', '${p.patient_id}', '${p.admission_id}')" class="btn-sm btn-primary" style="grid-column: 1 / -1; margin-top: 5px;">
                                <i class="fas fa-file-medical"></i> Nursing Notes
                            </button>
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = `
                    <div class="empty-state" style="grid-column:1/-1;">
                        <i class="fas fa-search-minus" style="font-size:48px;margin-bottom:12px;display:block;"></i>
                        <h3>No Patients Match Your Search</h3>
                        <p>Try adjusting your search terms or view all patients.</p>
                    </div>`;
            }
        }

        function filterPatients() {
            const searchTerm = document.getElementById('patientSearch').value.toLowerCase().trim();
            
            if (searchTerm === '') {
                renderPatients(allPatients);
                return;
            }

            const filtered = allPatients.filter(p => {
                const fullName = `${p.first_name || ''} ${p.last_name || ''}`.toLowerCase();
                const patientId = (p.patient_id || '').toLowerCase();
                const roomNo = (p.room_number || '').toLowerCase();
                const wardName = (p.room_type || p.room_name || '').toLowerCase();
                const floorName = (p.floor_name || '').toLowerCase();

                return fullName.includes(searchTerm) || 
                       patientId.includes(searchTerm) || 
                       roomNo.includes(searchTerm) || 
                       wardName.includes(searchTerm) ||
                       floorName.includes(searchTerm);
            });

            renderPatients(filtered);
        }

        document.getElementById('searchBtn').addEventListener('click', filterPatients);
        document.getElementById('patientSearch').addEventListener('keyup', (e) => {
            if (e.key === 'Enter') filterPatients();
            if (e.target.value.length === 0) renderPatients(allPatients);
        });

        function navigateWithPatient(page, patientId, admissionId) {
            sessionStorage.setItem('selected_patient_id', patientId);
            if (admissionId) sessionStorage.setItem('selected_admission_id', admissionId);
            window.location.href = page;
        }

        loadPatients();
    </script>
</body>

</html>