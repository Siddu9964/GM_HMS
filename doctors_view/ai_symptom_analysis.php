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
    <title>AI Symptom Analysis - GM HMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/doctor_dashboard.css?v=<?= time() ?>">
</head>
<body>
    <div class="doctor-layout">
        <!-- Sidebar -->
        <?php include 'includes/doctor_sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="doctor-main-content">
            <!-- Top Navbar -->
            <?php include 'includes/doctor_navbar.php'; ?>
            
            <!-- Page Content -->
            <div class="doctor-content">
                <!-- Page Header -->
                <div class="welcome-banner fade-in-up" style="display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 2; margin-bottom: 2rem;">
                    <div>
                        <h1 class="welcome-title">
                            <i class="fas fa-brain"></i> AI Symptom Analysis
                        </h1>
                        <p class="welcome-subtitle">AI-powered symptom extraction and diagnosis prediction</p>
                    </div>
                    <i class="fas fa-robot welcome-icon-bg"></i>
                </div>
                
                <!-- Patient Issues List -->
                <div class="bento-card fade-in-up delay-1">
                    <div class="card-header" style="background: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%); color: white;">
                        <div class="card-title">
                            <i class="fas fa-list"></i>
                            Patient Symptom Descriptions
                        </div>
                        <span class="badge" style="background: rgba(255,255,255,0.3);">AI-Powered</span>
                    </div>
                    <div class="card-body">
                        <div id="issues-list">
                            <!-- Issues loaded via AJAX -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="assets/js/doctor_utils.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadPatientIssues();
        });
        
        async function loadPatientIssues() {
            try {
                showLoading('Loading AI symptom analysis...');
                
                const response = await fetch('/GM_HMS/controler/api/get_patient_issues.php');
                const data = await response.json();
                
                if (data.success && data.data.length > 0) {
                    displayIssues(data.data);
                } else {
                    document.getElementById('issues-list').innerHTML = `
                        <div style="text-align: center; padding: 3rem; color: var(--gray-400);">
                            <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <p>No patient issues found</p>
                        </div>
                    `;
                }
                
                hideLoading();
            } catch (error) {
                hideLoading();
                showToast('Failed to load patient issues', 'error');
            }
        }
        
        function displayIssues(issues) {
            const html = issues.map(issue => `
                <div class="bento-card mb-3" style="border-left: 4px solid ${getRiskColor(issue.ai_risk_level)}; padding: 1rem;">
                    <div class="card-body" style="padding: 0;">
                        <div class="d-flex" style="justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                            <div>
                                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">
                                    Patient: ${issue.patient_id}
                                </h3>
                                <p style="color: var(--gray-500); font-size: 0.875rem;">
                                    <i class="fas fa-calendar"></i> ${new Date(issue.created_at).toLocaleString()}
                                </p>
                            </div>
                            <span class="badge badge-${getRiskBadge(issue.ai_risk_level)}" style="font-size: 1rem; padding: 0.5rem 1rem;">
                                ${issue.ai_risk_level} Risk
                            </span>
                        </div>
                        
                        <div class="d-grid grid-cols-2 gap-3">
                            <div>
                                <h4 style="font-size: 0.875rem; font-weight: 600; color: var(--gray-600); margin-bottom: 0.5rem;">
                                    Patient's Description
                                </h4>
                                <p style="background: var(--gray-50); padding: 1rem; border-radius: var(--radius-md);">
                                    ${issue.issue_text_raw}
                                </p>
                            </div>
                            
                            <div>
                                <h4 style="font-size: 0.875rem; font-weight: 600; color: var(--gray-600); margin-bottom: 0.5rem;">
                                    AI Extracted Symptoms
                                </h4>
                                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem;">
                                    ${issue.symptoms.split(',').map(s => 
                                        `<span class="badge badge-info">${s.trim()}</span>`
                                    ).join('')}
                                </div>
                                <div style="font-size: 0.875rem;">
                                    <strong>Duration:</strong> ${issue.duration}<br>
                                    <strong>Severity:</strong> <span class="badge badge-${getSeverityBadge(issue.severity)}">${issue.severity}</span><br>
                                    <strong>Affected Area:</strong> ${issue.affected_body_part}
                                </div>
                            </div>
                            
                            <div>
                                <h4 style="font-size: 0.875rem; font-weight: 600; color: var(--gray-600); margin-bottom: 0.5rem;">
                                    AI Possible Conditions
                                </h4>
                                <p style="font-size: 0.875rem; margin-bottom: 0.5rem;">
                                    ${issue.ai_possible_conditions}
                                </p>
                                <div>
                                    <strong>AI Confidence:</strong> 
                                    <div style="background: var(--gray-200); height: 8px; border-radius: 4px; overflow: hidden; margin-top: 0.25rem;">
                                        <div style="background: #144d34; height: 100%; width: ${issue.ai_confidence_score}%;"></div>
                                    </div>
                                    <span style="font-size: 0.75rem; color: var(--gray-500);">${issue.ai_confidence_score}%</span>
                                </div>
                            </div>
                            
                            <div>
                                <h4 style="font-size: 0.875rem; font-weight: 600; color: var(--gray-600); margin-bottom: 0.5rem;">
                                    Actions
                                </h4>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button onclick="startConsultation('${issue.patient_id}')" class="btn btn-primary">
                                        <i class="fas fa-notes-medical"></i> Start Consultation
                                    </button>
                                    <button onclick="viewPatient('${issue.patient_id}')" class="btn btn-outline">
                                        <i class="fas fa-eye"></i> View Patient
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
            
            document.getElementById('issues-list').innerHTML = html;
        }
        
        function getRiskColor(risk) {
            const colors = {
                'Low': '#10b981',
                'Medium': '#f59e0b',
                'High': '#ef4444',
                'Critical': '#dc2626'
            };
            return colors[risk] || '#6b7280';
        }
        
        function getRiskBadge(risk) {
            const badges = {
                'Low': 'success',
                'Medium': 'warning',
                'High': 'danger',
                'Critical': 'danger'
            };
            return badges[risk] || 'info';
        }
        
        function getSeverityBadge(severity) {
            const badges = {
                'Mild': 'success',
                'Moderate': 'warning',
                'Severe': 'danger',
                'Critical': 'danger'
            };
            return badges[severity] || 'info';
        }
        
        function startConsultation(patientId) {
            if (typeof startConsultationSession === 'function') {
                startConsultationSession(patientId);
            } else {
                window.location.href = `consultation.php?patient_id=${patientId}`;
            }
        }
        
        function viewPatient(patientId) {
            if (typeof viewMedicalHistory === 'function') {
                viewMedicalHistory(patientId);
            } else {
                window.location.href = `mypatient.php?patient_id=${patientId}`;
            }
        }
    </script>
</body>
</html>
