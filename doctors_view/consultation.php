<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Doctor', 'admin', 'Admin'])) {
    header("Location: /GM_HMS/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinical Consultation - GM HMS</title>
    
    <!-- Google Fonts - Professional Medical Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/doctor_dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/consultation_redesign.css">
    <link rel="stylesheet" href="assets/css/voice-input.css">
    <link rel="stylesheet" href="assets/css/medication-dropdown.css">
    
    <!-- Select2 CSS for Searchable Dropdown -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Custom Select2 Styling for Elite Design */
        .select2-container .select2-selection--single {
            height: 44px !important;
            border: 2px solid #F1F5F9 !important;
            border-radius: 12px !important;
            background-color: #f3efe6 !important;
            display: flex !important;
            align-items: center !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #1E293B !important;
            font-weight: 700 !important;
            font-size: 0.95rem !important;
            padding-left: 16px !important;
            line-height: normal !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 42px !important;
            right: 10px !important;
        }
        .select2-dropdown {
            border: 2px solid #F1F5F9 !important;
            border-radius: 12px !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1) !important;
            overflow: hidden !important;
        }
        .select2-results__option {
            padding: 10px 16px !important;
            font-size: 0.9rem !important;
            font-weight: 500 !important;
        }
        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
            background-color: #0EA5E9 !important;
            color: white !important;
        }
        .select2-search__field {
            border-radius: 8px !important;
            border: 1px solid #E2E8F0 !important;
            padding: 8px !important;
        }
    </style>
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
                <!-- Page Header: Command Center Style -->
                <div class="welcome-banner fade-in-up" style="display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 2;">
                    <div class="header-main-info" style="position: relative; z-index: 2;">
                        <div>
                            <h1 class="welcome-title">
                                <i class="fas fa-stethoscope"></i> Clinical Command Center
                            </h1>
                            <p class="welcome-subtitle flex items-center gap-2">
                                <span class="status-dot"></span> Active Consultation Session
                            </p>
                        </div>
                    </div>
                    <i class="fas fa-stethoscope welcome-icon-bg"></i>
                </div>
                
                <div class="header-actions-elite" style="margin-bottom: 1.5rem; text-align: right;">
                        <button onclick="viewPatientHistory()" class="btn-glass">
                            <i class="fas fa-file-medical"></i> Medical Records
                        </button>
                        <button onclick="typeof showVitalsModal === 'function' ? showVitalsModal() : viewPatientHistory()" class="btn-primary-glass">
                            <i class="fas fa-plus"></i> Add Vitals
                        </button>
                    </div>
                
                <!-- Patient Identity Bar: Unified Dashboard Element -->
                <div class="identity-bar-elite">
                    <div class="id-selection-panel">
                        <div class="panel-tag">Patient Profile</div>
                        <div style="display: flex; gap: 0.5rem;">
                            <select id="patient-select" class="elite-select" onchange="loadPatientInfo()" style="flex: 1;">
                                <option value="">Select Patient from Queue...</option>
                            </select>
                            <button onclick="Modal.show('advanced-search-modal')" class="btn-glass" style="background: white; border: 2px solid #E2E8F0; color: #64748B; padding: 0 12px; border-radius: 12px;" title="Advanced Patient Search">
                                <i class="fas fa-search-plus"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div id="patient-info-display" class="id-vitals-panel" style="display: none;">
                        <div class="vital-chip">
                            <span class="chip-label">Age/Sex</span>
                            <span class="chip-value" id="patient-age">--</span> / <span class="chip-value" id="patient-gender">--</span>
                        </div>
                        <div class="vital-chip bg-blood">
                            <span class="chip-label">Blood</span>
                            <span class="chip-value" id="patient-blood">--</span>
                        </div>
                        <div class="vital-chip">
                            <span class="chip-label">Last Seen</span>
                            <span class="chip-value" id="patient-last-visit">Never</span>
                        </div>
                        <div class="chip-actions">
                            <button onclick="loadPatientInfo()" class="circle-btn" title="Refresh Data">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <style>
                :root {
                    --elite-primary: #0EA5E9;
                    --elite-secondary: #0F172A;
                    --elite-teal: #1f6b4a;
                    --elite-bg: #f3efe6;
                    --elite-glass: rgba(255, 255, 255, 0.9);
                    --elite-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                }

                .consultation-container-elite {
                    padding: 1.5rem 2rem;
                    background: var(--elite-bg);
                    min-height: 100vh;
                }

                /* Glassmorphism Command Header */
                .header-glass {
                    background: transparent;
                    padding: 1.75rem 2.5rem;
                    color: #1e293b;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 2rem;
                }

                @keyframes pulse-slow {
                    from { transform: scale(1); }
                    to { transform: scale(1.2); opacity: 0.5; }
                }

                .header-main-info { display: flex; align-items: center; gap: 1.5rem; position: relative; z-index: 10; }
                .pulse-icon { 
                    width: 60px; height: 60px; background: rgba(31, 107, 74, 0.2); 
                    border-radius: 18px; display: flex; align-items: center; justify-content: center; 
                    font-size: 1.75rem; color: #1f6b4a; border: 1px solid rgba(31, 107, 74, 0.3);
                    animation: float 3s ease-in-out infinite;
                }

                @keyframes float {
                    0%, 100% { transform: translateY(0); }
                    50% { transform: translateY(-8px); }
                }

                .main-title { font-size: 2rem; font-weight: 800; margin: 0; letter-spacing: -1px; }
                .main-subtitle { font-size: 0.9rem; opacity: 0.7; margin: 4px 0 0 0; display: flex; align-items: center; gap: 8px; }
                
                .status-dot { width: 8px; height: 8px; background: #10B981; border-radius: 50%; box-shadow: 0 0 10px #10B981; animation: blink 2s infinite; }
                @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }

                .header-actions-elite { display: flex; gap: 1rem; position: relative; z-index: 10; }
                .btn-glass { 
                    background: #3b82f6; border: 2px solid #2563eb; 
                    color: white; padding: 0.8rem 1.75rem; border-radius: 14px; font-weight: 700; 
                    transition: 0.3s; cursor: pointer; display: flex; align-items: center; gap: 10px;
                    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
                }
                .btn-glass:hover { background: #2563eb; transform: translateY(-2px); border-color: #1d4ed8; box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4); }
                .btn-primary-glass { 
                    background: #10b981; color: white; padding: 0.8rem 1.75rem; border-radius: 14px; 
                    font-weight: 700; border: 2px solid #059669; cursor: pointer; transition: 0.3s; 
                    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
                    display: flex; align-items: center; gap: 10px;
                }
                .btn-primary-glass:hover { background: #144d34; transform: scale(1.05); }

                /* Elite Identity Bar */
                .identity-bar-elite {
                    background: white; border-radius: 20px; border: 1px solid #E2E8F0;
                    display: flex; align-items: center; justify-content: space-between;
                    padding: 0.75rem 1.5rem; margin-bottom: 2rem; box-shadow: var(--elite-shadow);
                    gap: 2rem; min-height: 80px;
                }

                .id-selection-panel { flex: 0 0 400px; }
                .panel-tag { font-size: 0.65rem; font-weight: 800; color: #94A3B8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
                .elite-select { 
                    width: 100%; border: 2px solid #F1F5F9; background: #f3efe6; padding: 10px 16px; 
                    border-radius: 12px; font-size: 0.95rem; font-weight: 700; color: #1E293B; cursor: pointer; transition: 0.3s;
                }
                .elite-select:hover { border-color: #1f6b4a; background: white; }

                .id-vitals-panel { flex: 1; display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem; }
                .vital-chip { 
                    background: #f3efe6; border: 1px solid #E2E8F0; padding: 8px 16px; border-radius: 14px;
                    display: flex; flex-direction: column; min-width: 120px; transition: 0.3s;
                }
                .vital-chip:hover { transform: translateY(-4px); border-color: #1f6b4a; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
                .chip-label { font-size: 0.6rem; font-weight: 800; color: #64748B; text-transform: uppercase; }
                .chip-value { font-size: 0.9rem; font-weight: 700; color: #1E293B; }
                .bg-blood { background: #FFF1F2; border-color: #FFE4E6; }
                .bg-blood .chip-value { color: #E11D48; }

                .circle-btn {
                    width: 44px; height: 44px; border-radius: 50%; border: none; background: #F1F5F9;
                    color: #64748B; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.3s;
                }
                .circle-btn:hover { background: #1f6b4a; color: white; transform: rotate(180deg); }

                /* Elite Cards & Grid */
                .elite-soap-grid { display: grid; grid-template-columns: 1fr; gap: 2rem; margin-bottom: 2rem; }
                .elite-card { background: white; border-radius: 24px; border: 1px solid #E2E8F0; box-shadow: var(--elite-shadow); position: relative; }
                
                .el-card-header { 
                    padding: 1.5rem 2rem; border-bottom: 2px solid #F1F5F9; background: #f3efe6; 
                    display: flex; align-items: center; gap: 1.25rem;
                }
                .el-icon-wrapper { 
                    width: 50px; height: 50px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white;
                }
                .sub-bg { background: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%); }
                .plan-bg { background: linear-gradient(135deg, #6366F1 0%, #4338CA 100%); }
                
                .el-header-content h3 { font-size: 1.25rem; font-weight: 800; color: #1E293B; margin: 0; }
                .el-header-content p { font-size: 0.8rem; color: #64748B; margin: 2px 0 0 0; }

                .ai-assist-pills { display: flex; gap: 8px; margin-left: auto; }
                .pill-btn { 
                    padding: 8px 16px; border-radius: 50px; border: 1px solid #E2E8F0; background: white; 
                    font-size: 0.75rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: 0.2s;
                }
                .pill-btn.voice { color: #E11D48; } .pill-btn.voice:hover { background: #FFF1F2; border-color: #E11D48; }
                .pill-btn.magic { color: #0EA5E9; } .pill-btn.magic:hover { background: #F0F9FF; border-color: #0EA5E9; }

                .el-card-body { padding: 2rem; }
                .elite-textarea { 
                    width: 100%; border: 2px solid #F1F5F9; border-radius: 16px; padding: 1.25rem; 
                    font-size: 1rem; color: #1E293B; font-family: inherit; resize: vertical; transition: 0.3s;
                }
                .elite-textarea:focus { outline: none; border-color: #1f6b4a; box-shadow: 0 0 0 4px rgba(31, 107, 74, 0.15); }
                .el-footer-tip { display: flex; align-items: center; gap: 8px; font-size: 0.75rem; color: #64748B; margin-top: 1rem; }
                .el-footer-tip i { color: #018abd; }

                /* Elite Plan Table */
                .btn-add-pills {
                    margin-left: auto; background: white; border: 2px solid #0EA5E9; color: #0EA5E9;
                    padding: 8px 16px; border-radius: 12px; font-weight: 700; font-size: 0.8rem; cursor: pointer; transition: 0.2s;
                }
                .btn-add-pills:hover { background: #0EA5E9; color: white; transform: scale(1.05); }

                .elite-table-wrapper { border-radius: 16px; border: 1px solid #E2E8F0; margin-bottom: 1.5rem; position: relative; z-index: 100; }
                .elite-med-table { width: 100%; border-collapse: collapse; }
                .elite-med-table th { background: #f3efe6; padding: 12px 16px; text-align: left; font-size: 0.7rem; font-weight: 800; color: #94A3B8; text-transform: uppercase; border-bottom: 2px solid #F1F5F9; }
                .elite-med-table td { padding: 8px 16px; border-bottom: 1px solid #F1F5F9; }
                .elite-med-table input, .elite-med-table select { width: 100%; border: 1px solid transparent; background: transparent; padding: 8px; font-size: 0.85rem; font-weight: 600; transition: 0.2s; }
                .elite-med-table input:focus, .elite-med-table select:focus { background: #f3efe6; border-color: #E2E8F0; border-radius: 6px; outline: none; }
                
                .elite-context-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
                .context-field { 
                    display: flex; flex-direction: column; gap: 10px; padding: 1.25rem; 
                    background: #f3efe6; border-radius: 18px; border: 1px solid #E2E8F0; transition: 0.3s;
                }
                .context-field:focus-within { background: white; border-color: #0EA5E9; box-shadow: 0 10px 25px rgba(14, 165, 233, 0.1); transform: translateY(-2px); }
                .context-field.danger:focus-within { border-color: #E11D48; box-shadow: 0 10px 25px rgba(225, 29, 72, 0.1); }

                .context-field label { 
                    font-size: 0.75rem; font-weight: 800; color: #64748B; text-transform: uppercase; 
                    display: flex; align-items: center; gap: 8px;
                }
                .context-field label i { 
                    font-size: 1rem; color: #0EA5E9; 
                    animation: pulse-icon 2s infinite ease-in-out;
                }
                .context-field.danger label i { color: #E11D48; }

                @keyframes pulse-icon {
                    0%, 100% { transform: scale(1); opacity: 1; }
                    50% { transform: scale(1.2); opacity: 0.7; }
                }

                .context-field textarea { 
                    border: none; background: transparent; padding: 0; font-size: 0.95rem; 
                    font-weight: 600; color: #1E293B; resize: vertical; width: 100%; transition: 0.3s;
                    min-height: 150px; line-height: 1.6;
                }
                .context-field textarea:focus { outline: none; }
                .context-field textarea::placeholder { color: #94A3B8; font-weight: 500; }

                .note-bg { background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%); }
                
                .final-fields-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem; }
                .final-block { display: flex; flex-direction: column; gap: 8px; }
                .final-label { font-size: 0.75rem; font-weight: 800; color: #64748B; text-transform: uppercase; }
                .final-block textarea, .elite-input { 
                    border: 2px solid #F1F5F9; border-radius: 12px; padding: 12px; font-size: 0.9rem; font-weight: 600; transition: 0.3s;
                }
                .final-block textarea:focus, .elite-input:focus { border-color: #1f6b4a; outline: none; background: #f3efe6; }
                .final-sub-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; }

                .center-save-action { text-align: center; padding-top: 1rem; border-top: 2px solid #F1F5F9; }
                .btn-elite-save { 
                    background: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%); color: white;
                    padding: 1.25rem 4rem; border-radius: 20px; border: none; font-weight: 800; font-size: 1.1rem;
                    cursor: pointer; transition: 0.3s; box-shadow: 0 15px 30px rgba(31, 107, 74, 0.4);
                    display: inline-flex; align-items: center; gap: 12px;
                }
                .btn-elite-save:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(31, 107, 74, 0.5); transform: scale(1.02); }

                @media (max-width: 1200px) {
                    .identity-bar-elite { flex-direction: column; align-items: stretch; padding: 1.5rem; }
                    .id-selection-panel { flex: none; }
                    .id-vitals-panel { justify-content: flex-start; overflow-x: auto; padding-bottom: 5px; }
                    .elite-context-row { grid-template-columns: 1fr; }
                    .final-fields-layout { grid-template-columns: 1fr; }
                }
                </style>
                
                <!-- AI Analysis Card (if available) -->
                <div id="ai-analysis-card" class="ai-analysis-card" style="display: none;">
                    <div class="ai-card-header">
                        <div class="ai-badge">
                            <i class="fas fa-brain"></i>
                            AI-Powered Analysis
                        </div>
                        <h3>Pre-Consultation Symptom Analysis</h3>
                    </div>
                    <div id="ai-analysis-content" class="ai-analysis-content">
                        <!-- AI analysis loaded here -->
                    </div>
                </div>
                
                <!-- SOAP Notes Grid -->
                <div class="elite-soap-grid">
                    <!-- Subjective Card -->
                    <div class="elite-card elite-subjective">
                        <div class="el-card-header">
                            <div class="el-icon-wrapper sub-bg">
                                <i class="fas fa-microscope"></i>
                            </div>
                            <div class="el-header-content">
                                <h3>Subjective Documentation</h3>
                                <p>Patient's history & symptoms</p>
                            </div>
                            <div class="ai-assist-pills">
                                <button onclick="startVoiceInput('subjective')" class="pill-btn voice">
                                    <i class="fas fa-microphone"></i> Voice
                                </button>
                                <button onclick="aiAutoComplete()" class="pill-btn magic">
                                    <i class="fas fa-wand-sparkles"></i> AI Suggest
                                </button>
                            </div>
                        </div>
                        <div class="el-card-body">
                            <textarea id="soap-subjective" class="elite-textarea" rows="10" 
                                placeholder="Record patient symptoms here... Use AI Suggest to structure your notes automatically."></textarea>
                            <div class="el-footer-tip">
                                <i class="fas fa-circle-info"></i>
                                Professional Tip: Be specific about duration and severity.
                            </div>
                        </div>
                    </div>
                    
                <!-- Plan Card: Digital Prescription -->
                    <div class="elite-card elite-plan">
                        <div class="el-card-header">
                            <div class="el-icon-wrapper plan-bg">
                                <i class="fas fa-prescription"></i>
                            </div>
                            <div class="el-header-content">
                                <h3>Treatment & Medication Plan</h3>
                                <p>Structured clinical intervention</p>
                            </div>
                            <button type="button" class="btn-add-pills" onclick="typeof addMedicationRowEnhanced === 'function' ? addMedicationRowEnhanced() : addMedicationRow()">
                                <i class="fas fa-plus-circle"></i> Add Medication
                            </button>
                        </div>
                        <div class="el-card-body">
                            <!-- Objective & Physical Exam (Merged) -->
                            <div style="margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid #f1f5f9;">
                                <h4 style="font-size: 1rem; font-weight: 700; color: #0EA5E9; margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <i class="fas fa-microscope" style="margin-right: 8px;"></i> Objective & Physical Exam
                                </h4>
                                <div class="final-block" style="margin-bottom: 1rem;">
                                    <label class="final-label">Physical Examination Details</label>
                                    <textarea id="physical-examination" class="elite-textarea" rows="3" placeholder="Detailed physical examination findings..."></textarea>
                                </div>
                                <div class="final-block">
                                    <label class="final-label">Objective Notes (Vitals/Labs)</label>
                                    <textarea id="soap-objective" class="elite-textarea" rows="3" placeholder="Summary of objective findings..."></textarea>
                                </div>
                            </div>
                            
                            <!-- Assessment & Diagnosis (Merged) -->
                            <div style="margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid #f1f5f9;">
                                <h4 style="font-size: 1rem; font-weight: 700; color: #F59E0B; margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <i class="fas fa-clipboard-check" style="margin-right: 8px;"></i> Assessment & Diagnosis
                                </h4>
                                <div class="final-block" style="margin-bottom: 1rem;">
                                    <label class="final-label">Final Diagnosis</label>
                                    <input type="text" id="final-diagnosis" class="elite-input" placeholder="e.g. Acute Viral Bronchitis">
                                </div>
                                <div class="final-block">
                                    <label class="final-label">Assessment Notes</label>
                                    <textarea id="soap-assessment" class="elite-textarea" rows="4" placeholder="Detailed clinical assessment notes..."></textarea>
                                </div>
                            </div>

                            <!-- Medication Table -->
                            <h4 style="font-size: 1rem; font-weight: 700; color: #6366F1; margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                <i class="fas fa-pills" style="margin-right: 8px;"></i> Medication List
                            </h4>
                            <div class="elite-table-wrapper">
                                <table class="elite-med-table" id="medication-entry-table">
                                    <thead>
                                        <tr>
                                            <th>Medication Name</th>
                                            <th style="width: 80px;">Dose</th>
                                            <th style="width: 140px;">Timing</th>
                                            <th style="width: 120px;">Freq.</th>
                                            <th style="width: 100px;">Duration</th>
                                            <th style="width: 70px;">Qty</th>
                                            <th style="width: 50px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="medication-list-body">
                                        <!-- Rows added via JS -->
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="elite-context-row">
                                <div class="context-field">
                                    <label><i class="fas fa-bullseye"></i> Clinical Indication / Purpose</label>
                                    <textarea id="plan-purpose" rows="2" placeholder="e.g., Hypertension management"></textarea>
                                </div>
                                <div class="context-field danger">
                                    <label><i class="fas fa-shield-halved"></i> Safety & Precautions</label>
                                    <textarea id="plan-warnings" rows="2" placeholder="e.g., Monitor for lightheadedness"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Finalization Row -->
                <div class="elite-final-grid">
                    <div class="elite-card elite-final">
                        <div class="el-card-header">
                            <div class="el-icon-wrapper note-bg">
                                <i class="fas fa-feather-pointed"></i>
                            </div>
                            <div class="el-header-content">
                                <h3>Clinical Summary & Follow-up</h3>
                                <p>Finalize patient journey & next steps</p>
                            </div>
                        </div>
                        <div class="el-card-body">
                            <div class="final-fields-layout">
                                <div class="final-block">
                                    <label class="final-label">Additional Observations</label>
                                    <textarea id="clinical-notes" rows="4" placeholder="Any secondary findings..."></textarea>
                                </div>
                                <div class="final-sub-grid">
                                    <div class="final-block">
                                        <label class="final-label">Follow-up Date</label>
                                        <input type="date" id="followup-date" class="elite-input">
                                    </div>
                                    <div class="final-block">
                                        <label class="final-label">Instructions</label>
                                        <input type="text" id="followup-instructions" class="elite-input" placeholder="e.g., Review labs">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="center-save-action">
                                <button type="button" onclick="openSaveConsultationModal()" class="btn-elite-save">
                                    <i class="fas fa-file-export"></i> Save & Close Consultation
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Advanced Search Modal -->
    <div id="advanced-search-modal" class="modal" style="display: none; z-index: 10000; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
        <div class="modal-content" style="background: white; border-radius: 20px; width: 500px; padding: 2rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2 style="font-size: 1.5rem; font-weight: 800; color: #0F172A; margin: 0; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-search-plus" style="color: #1f6b4a;"></i>
                    Advanced Search
                </h2>
                <button onclick="Modal.hide('advanced-search-modal')" style="background: none; border: none; font-size: 1.25rem; color: #64748B; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="advanced-search-form" onsubmit="event.preventDefault(); performAdvancedSearch();">
                <div class="d-grid grid-cols-2 gap-3 mb-3" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="font-size: 0.75rem; font-weight: 800; color: #64748B; margin-bottom: 0.5rem; display: block; text-transform: uppercase;">Patient ID</label>
                        <input type="text" id="adv-patient-id" class="elite-input" placeholder="e.g. PID-2023..." style="width: 100%;">
                    </div>
                    <div>
                        <label style="font-size: 0.75rem; font-weight: 800; color: #64748B; margin-bottom: 0.5rem; display: block; text-transform: uppercase;">Phone Number</label>
                        <input type="text" id="adv-phone" class="elite-input" placeholder="10-digit number" style="width: 100%;">
                    </div>
                </div>
                
                <div class="mb-3" style="margin-bottom: 1rem;">
                    <label style="font-size: 0.75rem; font-weight: 800; color: #64748B; margin-bottom: 0.5rem; display: block; text-transform: uppercase;">Patient Name</label>
                    <input type="text" id="adv-name" class="elite-input" placeholder="First or Last Name" style="width: 100%;">
                </div>
                
                <div class="d-grid grid-cols-2 gap-3 mb-3" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                    <div>
                        <label style="font-size: 0.75rem; font-weight: 800; color: #64748B; margin-bottom: 0.5rem; display: block; text-transform: uppercase;">City</label>
                        <input type="text" id="adv-city" class="elite-input" placeholder="City Name" style="width: 100%;">
                    </div>
                    <div>
                        <label style="font-size: 0.75rem; font-weight: 800; color: #64748B; margin-bottom: 0.5rem; display: block; text-transform: uppercase;">Gender</label>
                        <select id="adv-gender" class="elite-input" style="width: 100%;">
                            <option value="">Any</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem;">
                    <button type="button" onclick="Modal.hide('advanced-search-modal')" class="btn-glass" style="flex: 1; background: #F1F5F9; color: #64748B; border: none; justify-content: center;">Cancel</button>
                    <button type="submit" class="btn-primary-glass" style="flex: 2; justify-content: center;">
                        <i class="fas fa-search"></i> Find Patient
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Unified Save Consultation Modal -->
    <div id="save-consultation-modal" class="modern-modal" style="display: none;">
        <div class="modern-modal-content" style="max-width: 600px;">
            <div class="modern-modal-header">
                <h2><i class="fas fa-save"></i> Save Consultation</h2>
                <button onclick="Modal.hide('save-consultation-modal')" class="modal-close-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modern-modal-body">
                <div class="save-options-container">
                    <p class="save-modal-description">
                        <i class="fas fa-info-circle"></i>
                        Choose how you want to save this consultation. All data including SOAP notes, medications, and follow-up plans will be saved.
                    </p>
                    
                    <div class="save-option-cards">
                        <!-- Draft Option -->
                        <div class="save-option-card" onclick="selectSaveOption('draft')">
                            <div class="save-option-icon draft-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <h3>Save as Draft</h3>
                            <p>Save your work in progress. You can continue editing later.</p>
                            <ul class="save-option-features">
                                <li><i class="fas fa-check"></i> Saves all current data</li>
                                <li><i class="fas fa-check"></i> Allows further editing</li>
                                <li><i class="fas fa-check"></i> Stays on this page</li>
                            </ul>
                        </div>
                        
                        <!-- Complete Option -->
                        <div class="save-option-card" onclick="selectSaveOption('completed')">
                            <div class="save-option-icon complete-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3>Complete Consultation</h3>
                            <p>Finalize and complete this consultation session.</p>
                            <ul class="save-option-features">
                                <li><i class="fas fa-check"></i> Marks as completed</li>
                                <li><i class="fas fa-check"></i> Generates prescription</li>
                                <li><i class="fas fa-check"></i> Returns to patient list</li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Validation Summary -->
                    <div id="save-validation-summary" class="save-validation-summary" style="display: none;">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    /* Main Save Consultation Button - Centered in Follow-up Section */
    .btn-save-consultation-main {
        padding: 1rem 3rem;
        background: linear-gradient(135deg, #0EA5E9 0%, #0284C7 100%);
        color: white;
        border: none;
        border-radius: 14px;
        font-weight: 700;
        font-size: 1.1rem;
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        cursor: pointer;
        box-shadow: 0 8px 25px rgba(14, 165, 233, 0.35);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    
    .btn-save-consultation-main::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: left 0.5s;
    }
    
    .btn-save-consultation-main:hover::before {
        left: 100%;
    }
    
    .btn-save-consultation-main:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 35px rgba(14, 165, 233, 0.45);
        background: linear-gradient(135deg, #0284C7 0%, #0369A1 100%);
    }
    
    .btn-save-consultation-main:active {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(14, 165, 233, 0.35);
    }
    
    .btn-save-consultation-main i {
        font-size: 1.2rem;
    }
    
    /* Unified Save Button Styling */
    .btn-head-unified {
        padding: 0.75rem 2rem;
        background: linear-gradient(135deg, #0EA5E9 0%, #0284C7 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3);
        transition: all 0.3s ease;
    }
    
    .btn-head-unified:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(14, 165, 233, 0.4);
        background: linear-gradient(135deg, #0284C7 0%, #0369A1 100%);
    }
    
    .btn-head-unified:active {
        transform: translateY(0);
    }
    
    /* Save Options Container */
    .save-options-container {
        padding: 1rem 0;
    }
    
    .save-modal-description {
        background: #F0F9FF;
        border-left: 4px solid #0EA5E9;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        color: #0C4A6E;
        font-size: 0.9rem;
        display: flex;
        align-items: start;
        gap: 0.75rem;
    }
    
    .save-modal-description i {
        margin-top: 0.2rem;
        font-size: 1.1rem;
    }
    
    .save-option-cards {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .save-option-card {
        background: white;
        border: 2px solid #E2E8F0;
        border-radius: 12px;
        padding: 1.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .save-option-card:hover {
        border-color: #0EA5E9;
        box-shadow: 0 8px 20px rgba(14, 165, 233, 0.15);
        transform: translateY(-4px);
    }
    
    .save-option-card.selected {
        border-color: #0EA5E9;
        background: linear-gradient(135deg, #F0F9FF 0%, #E0F2FE 100%);
        box-shadow: 0 8px 20px rgba(14, 165, 233, 0.2);
    }
    
    .save-option-card.selected::after {
        content: '\f00c';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: #0EA5E9;
        color: white;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
    }
    
    .save-option-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
        font-size: 1.5rem;
    }
    
    .draft-icon {
        background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
        color: white;
    }
    
    .complete-icon {
        background: linear-gradient(135deg, #10B981 0%, #059669 100%);
        color: white;
    }
    
    .save-option-card h3 {
        margin: 0 0 0.5rem 0;
        font-size: 1.1rem;
        font-weight: 700;
        color: #1E293B;
    }
    
    .save-option-card p {
        margin: 0 0 1rem 0;
        font-size: 0.85rem;
        color: #64748B;
        line-height: 1.5;
    }
    
    .save-option-features {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .save-option-features li {
        font-size: 0.8rem;
        color: #475569;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .save-option-features li i {
        color: #10B981;
        font-size: 0.7rem;
    }
    
    .save-validation-summary {
        background: #FEF3C7;
        border-left: 4px solid #F59E0B;
        padding: 1rem;
        border-radius: 8px;
        margin-top: 1rem;
    }
    
    .save-validation-summary.error {
        background: #FEE2E2;
        border-left-color: #EF4444;
    }
    
    .save-validation-summary.success {
        background: #D1FAE5;
        border-left-color: #10B981;
    }
    
    @media (max-width: 640px) {
        .save-option-cards {
            grid-template-columns: 1fr;
        }
    }
    </style>
    

    
    <!-- Scripts -->
    <!-- jQuery (Required for Select2) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="assets/js/doctor_utils.js"></script>
    <script src="assets/js/voice-input.js"></script>
    <script src="assets/js/medication-dropdown.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        const CURRENT_DOCTOR_ID = "<?php echo $_SESSION['user_id']; ?>";
    </script>
    <script src="assets/js/consultation.js"></script>
    <script src="assets/js/prescription_notebook_ui.js"></script>
    <script src="assets/js/consultation_history_fix.js"></script>
</body>
</html>


