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
    <title>Nursing Notes - GM HMS</title>
    
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

        .notes-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 20px;
        }

        .note-editor {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #495057;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #DEE2E6;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .soap-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .sidebar-list {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .sidebar-list h3 {
            font-size: 18px;
            margin-bottom: 15px;
            border-bottom: 1px solid #F1F3F5;
            padding-bottom: 10px;
        }

        .recent-note-item {
            padding: 12px;
            border-radius: 8px;
            border-bottom: 1px solid #F1F3F5;
            cursor: pointer;
            transition: 0.2s;
        }

        .recent-note-item:hover {
            background: #F8F9FA;
        }

        .recent-note-item h5 {
            font-size: 14px;
            margin-bottom: 4px;
        }

        .recent-note-item p {
            font-size: 12px;
            color: #6C757D;
        }

        .btn-save {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            width: 100%;
        }

        .btn-save:hover {
            background: var(--primary-dark);
            box-shadow: 0 4px 12px rgba(74, 144, 226, 0.2);
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
                        <h1>Nursing Notes (SOAP)</h1>
                        <div id="selectedPatient" class="text-muted">Select a patient to start noting</div>
                    </div>

                    <div class="notes-container">
                        <div class="note-editor">
                            <form id="noteForm">
                                <div class="form-group">
                                    <label>Patient Selection</label>
                                    <select class="form-control" id="patientSelect" required>
                                        <option value="">Choose Patient...</option>
                                    </select>
                                </div>

                                <div class="soap-grid">
                                    <div class="form-group">
                                        <label>Subjective (S)</label>
                                        <textarea class="form-control" placeholder="Patient's complaints, symptoms..."></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Objective (O)</label>
                                        <textarea class="form-control" placeholder="Vital signs, observations, lab results..."></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Assessment (A)</label>
                                        <textarea class="form-control" placeholder="Nursing diagnosis, patient progress..."></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Plan (P)</label>
                                        <textarea class="form-control" placeholder="Interventions, medication, follow-up..."></textarea>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>
                                        <input type="checkbox"> Important for Handover
                                    </label>
                                </div>

                                <button type="submit" class="btn-save">Save Nursing Note</button>
                            </form>
                        </div>

                        <div class="sidebar-list">
                            <h3>Recent Notes</h3>
                            <div id="recentNotes">
                                <p class="text-center py-4 text-muted">No recent notes found</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function loadInitialData() {
            try {
                // Get patient from URL or SessionStorage
                const urlParams = new URLSearchParams(window.location.search);
                let targetId = urlParams.get('patient_id') || sessionStorage.getItem('selected_patient_id');
                
                // Clear URL param if it exists to keep URL clean
                if (urlParams.has('patient_id')) {
                    sessionStorage.setItem('selected_patient_id', urlParams.get('patient_id'));
                    window.history.replaceState({}, document.title, window.location.pathname);
                }

                const response = await fetch('api/dashboard.php');
                const result = await response.json();

                if (result.success) {
                    const patients = result.data.assigned_patients;
                    const select = document.getElementById('patientSelect');
                    
                    if (patients) {
                        patients.forEach(p => {
                            const option = document.createElement('option');
                            option.value = p.patient_id;
                            option.textContent = `${p.first_name} ${p.last_name} (${p.bed_number})`;
                            if (p.patient_id === targetId) {
                                option.selected = true;
                                document.getElementById('selectedPatient').textContent = `Documenting for: ${p.first_name} ${p.last_name} (${p.patient_id})`;
                            }
                            select.appendChild(option);
                        });
                    }

                    const notes = result.data.handover_notes;
                    if (notes && notes.length > 0) {
                        document.getElementById('recentNotes').innerHTML = notes.map(n => `
                            <div class="recent-note-item">
                                <h5>${n.patient_name}</h5>
                                <p>${n.assessment ? n.assessment.substring(0, 50) + '...' : 'Quick Review'}</p>
                                <p style="font-size: 10px; margin-top: 5px;">${new Date(n.created_at).toLocaleString()}</p>
                            </div>
                        `).join('');
                    }
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        loadInitialData();
    </script>
</body>
</html>
