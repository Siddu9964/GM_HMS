<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vitals Entry - HMS</title>
    <!-- Google Fonts for Modern Typography -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/vitals.css">
</head>
<body>

<div class="vitals-container">
    <div class="vitals-header">
        <h1>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
            </svg>
            Patient Vitals Entry
        </h1>
        
        <div class="branch-selector" style="display: flex; gap: 0.5rem; margin-right: auto; margin-left: 2rem;">
            <button class="btn btn-secondary branch-btn" data-branch="main" style="background-color: var(--secondary-color); color: var(--primary-color); border: 1px solid var(--primary-color);">Main Branch</button>
            <button class="btn btn-secondary branch-btn" data-branch="basaveshwaranagar" style="background-color: var(--secondary-color); color: var(--primary-color); border: 1px solid var(--primary-color);">Basaveshwaranagar Branch</button>
        </div>

        <div class="search-container">
            <input type="text" id="searchInput" class="search-input" placeholder="Search by name, ID, or Doctor...">
        </div>
    </div>

    <!-- Patient List Table -->
    <div class="glass-card table-wrapper">
        <table class="vitals-table">
            <thead>
                <tr>
                    <th>Appointment ID</th>
                    <th>Patient ID</th>
                    <th>Patient Name</th>
                    <th>Doctor Name</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="patientTableBody">
                <!-- Data populated by JS -->
            </tbody>
        </table>
    </div>
</div>

<!-- Vitals Entry Modal -->
<div class="modal-overlay" id="vitalsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Record Patient Vitals</h2>
            <button class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
            
            <!-- Context Banner -->
            <div class="patient-info-banner">
                <div class="info-item">
                    <span class="info-label">Patient Name</span>
                    <span class="info-value" id="displayPatientName">-</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Patient ID</span>
                    <span class="info-value" id="displayPatientId">-</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Doctor</span>
                    <span class="info-value" id="displayDoctorId">-</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Appointment</span>
                    <span class="info-value" id="displayAppointmentDate">-</span>
                </div>
            </div>

            <!-- Vitals Form -->
            <form id="vitalsForm">
                <input type="hidden" id="appointmentId" name="appointment_id">
                <input type="hidden" id="patientId" name="patient_id">
                <input type="hidden" id="doctorId" name="doctor_id">
                
                <div class="clinical-form-grid">
                    
                    <div class="clinical-group">
                        <label>
                            Blood Pressure <span class="clinical-unit">(mmHg)</span>
                        </label>
                        <div class="bp-inputs-clinical">
                            <input type="number" id="bp_sys" placeholder="Sys" min="50" max="250" class="clinical-input">
                            <span class="bp-slash">/</span>
                            <input type="number" id="bp_dia" placeholder="Dia" min="30" max="180" class="clinical-input">
                        </div>
                        <input type="hidden" id="bp" name="bp">
                    </div>
                    
                    <div class="clinical-group">
                        <label for="pulse">
                            Pulse <span class="clinical-unit">(bpm)</span>
                        </label>
                        <input type="number" id="pulse" name="pulse" placeholder="e.g. 72" min="30" max="250" class="clinical-input">
                    </div>
                    
                    <div class="clinical-group">
                        <label for="temperature">
                            Temperature <span class="clinical-unit">(°F)</span>
                        </label>
                        <input type="number" id="temperature" name="temperature" placeholder="e.g. 98.6" step="0.1" min="90" max="110" class="clinical-input">
                    </div>
                    
                    <div class="clinical-group">
                        <label for="spo2">
                            SpO₂ <span class="clinical-unit">(%)</span>
                        </label>
                        <input type="number" id="spo2" name="spo2" placeholder="e.g. 99" min="50" max="100" class="clinical-input">
                    </div>
                    
                    <div class="clinical-group">
                        <label for="weight">
                            Weight <span class="clinical-unit">(kg)</span>
                        </label>
                        <input type="number" id="weight" name="weight" placeholder="e.g. 65" step="0.1" min="1" max="500" class="clinical-input">
                    </div>
                    
                    <div class="clinical-group">
                        <label for="height">
                            Height <span class="clinical-unit">(cm)</span>
                        </label>
                        <input type="number" id="height" name="height" placeholder="e.g. 172" step="0.1" min="30" max="300" class="clinical-input">
                    </div>

                </div>
            </form>

        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelBtn" style="background-color: #6c757d; color: white;">Cancel</button>
            <button type="submit" form="vitalsForm" class="btn btn-primary" id="saveBtn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Save Vitals
            </button>
        </div>
    </div>
</div>

<!-- Custom JS -->
<script src="../assets/js/vitals.js"></script>
</body>
</html>
