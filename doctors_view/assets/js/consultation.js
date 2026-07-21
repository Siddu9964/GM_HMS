/**
 * Consultation JavaScript - FIXED VERSION
 * Handles SOAP notes, AI analysis, vitals, and consultation workflow
 */

let currentPatient = null;


// ============================================================================
// INITIALIZE
// ============================================================================

document.addEventListener('DOMContentLoaded', function () {
    loadPatients();
    // Initialize medication table with one row using enhanced dropdown
    if (typeof addMedicationRowEnhanced === 'function') {
        addMedicationRowEnhanced();
    } else {
        // Fallback to basic version if enhanced not loaded
        addMedicationRow();
    }
});

// ============================================================================
// LOAD PATIENTS
// ============================================================================

async function loadPatients() {
    try {
        const response = await API.get('patients');
        if (response.success) {
            const select = document.getElementById('patient-select');
            // API returns { data: [...], pagination: {...} }
            const patients = response.data.data || response.data;

            // Clear existing options except default
            select.innerHTML = '<option value="">-- Select Patient from Queue --</option>';

            patients.forEach(patient => {
                const option = document.createElement('option');
                option.value = patient.patient_id;
                option.textContent = `${patient.patient_id} - ${patient.first_name} ${patient.last_name}`;
                select.appendChild(option);
            });

            // If we have a patient ID from URL or Session, auto-select it
            const urlParams = new URLSearchParams(window.location.search);
            let targetPatientId = urlParams.get('patient_id');

            // Fallback to session storage if not in URL (as per user request to hide from URL)
            if (!targetPatientId) {
                targetPatientId = sessionStorage.getItem('consultation_patient_id');
            }

            if (targetPatientId) {
                select.value = targetPatientId;
                // Ensure it's in sessionStorage for persistence across refreshes
                sessionStorage.setItem('consultation_patient_id', targetPatientId);
                // Trigger change event to load info
                loadPatientInfo();

                // Optional: Clear session storage after use to avoid sticking to same patient? 
                // defaulting to keeping it for specific flows.
            }

            // Also check for hidden appointment ID
            let targetAppointmentId = urlParams.get('appointment_id');
            if (!targetAppointmentId) {
                targetAppointmentId = sessionStorage.getItem('consultation_appointment_id');
            }
            if (targetAppointmentId) {
                window.currentAppointmentId = targetAppointmentId;
            }

            // Cleanup URL if parameters were present (for clean appearance)
            if (urlParams.get('patient_id') || urlParams.get('appointment_id')) {
                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
            }

            // Initialize Select2
            if ($.fn.select2) {
                $(select).select2({
                    placeholder: "-- Select Patient from Queue --",
                    allowClear: true,
                    width: '100%'
                });

                // Bind Select2 change event to loadPatientInfo
                $(select).on('change', function () {
                    loadPatientInfo();
                });

                // Set value if pre-selected
                if (targetPatientId) {
                    $(select).val(targetPatientId).trigger('change');
                }
            }

            // Check for auto-show history flag
            const showHistory = sessionStorage.getItem('consultation_show_history');
            if (showHistory === 'true') {
                sessionStorage.removeItem('consultation_show_history');
                // Small delay to ensure patient info is loaded first
                setTimeout(() => {
                    if (typeof viewPatientHistory === 'function') {
                        viewPatientHistory();
                    }
                }, 800);
            }
        }
    } catch (error) {
        console.error('Error loading patients:', error);
        showToast('Failed to load patient list', 'error');
    }
}

// ============================================================================
// LOAD PATIENT INFO
// ============================================================================

async function loadPatientInfo() {
    const patientId = document.getElementById('patient-select').value;

    // Persist selection in sessionStorage so it survives refreshes/redirects
    if (patientId) {
        sessionStorage.setItem('consultation_patient_id', patientId);
    } else {
        sessionStorage.removeItem('consultation_patient_id');
        document.getElementById('patient-info-display').style.display = 'none';
        return;
    }

    try {
        showLoading('Loading patient information...');
        const response = await API.get(`patients/${patientId}`);

        if (response.success) {
            currentPatient = response.data;

            // Display patient info (safety checks for elements that might be hidden in compact UI)
            const nameEl = document.getElementById('patient-name-display');
            const idEl = document.getElementById('patient-id-display');

            if (nameEl) nameEl.textContent = `${currentPatient.first_name} ${currentPatient.last_name}`;
            if (idEl) idEl.textContent = `ID: ${currentPatient.patient_id}`;

            document.getElementById('patient-age').textContent =
                currentPatient.age || DateUtils.calculateAge(currentPatient.birth_date) || '--';
            document.getElementById('patient-gender').textContent = currentPatient.sex || '--';
            document.getElementById('patient-blood').textContent = currentPatient.blood_group || 'Unknown';
            document.getElementById('patient-last-visit').textContent =
                currentPatient.last_visit ? DateUtils.formatDateReadable(currentPatient.last_visit) : 'Never';

            document.getElementById('patient-info-display').style.display = 'flex';

            // Load AI symptom analysis if available (FIXED - direct database query)
            await loadAIAnalysisFromDB(patientId);

            // Check for previous instructions passed from queue
            checkPreviousInstructions(patientId);
        } else {
            // Handle error responses
            if (response.error_code === 'PATIENT_NOT_ALLOCATED') {
                // Patient not allocated to this doctor
                showAccessDeniedMessage(response.message || response.error);
                // Reset patient selection
                document.getElementById('patient-select').value = '';
                document.getElementById('patient-info-display').style.display = 'none';
            } else {
                // Other errors
                showToast(response.error || 'Failed to load patient information', 'error');
            }
        }

        hideLoading();
    } catch (error) {
        hideLoading();
        showToast('Failed to load patient information', 'error');
        console.error('Patient load error:', error);
    }
}

// ============================================================================
// LOAD AI ANALYSIS - FIXED VERSION
// ============================================================================

async function loadAIAnalysisFromDB(patientId) {
    try {
        // Direct query to patient_issue_description table via new route
        const response = await API.get(`patients/${patientId}/issues`);

        if (response.success && response.data && response.data.length > 0) {
            const latestIssue = response.data[0]; // Get most recent
            displayAIAnalysis(latestIssue);
        } else {
            // No AI analysis available - hide the card
            document.getElementById('ai-analysis-card').style.display = 'none';
        }
    } catch (error) {
        console.error('Error loading AI analysis:', error);
        // Hide AI card if error
        document.getElementById('ai-analysis-card').style.display = 'none';
    }
}

function displayAIAnalysis(latestIssue) {
    const aiCard = document.getElementById('ai-analysis-card');
    const aiContent = document.getElementById('ai-analysis-content');

    aiContent.innerHTML = `
        <div class="d-grid grid-cols-2 gap-3">
            <div>
                <h4 style="font-size: 0.875rem; font-weight: 600; color: var(--gray-600); margin-bottom: 0.5rem;">
                    Patient's Description
                </h4>
                <p style="background: var(--gray-50); padding: 1rem; border-radius: var(--radius-md); font-size: 0.875rem;">
                    ${latestIssue.issue_text_raw}
                </p>
            </div>
            <div>
                <h4 style="font-size: 0.875rem; font-weight: 600; color: var(--gray-600); margin-bottom: 0.5rem;">
                    AI Extracted Symptoms
                </h4>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    ${latestIssue.symptoms.split(',').map(s =>
        `<span class="badge badge-info">${s.trim()}</span>`
    ).join('')}
                </div>
                <div style="margin-top: 1rem;">
                    <strong>Duration:</strong> ${latestIssue.duration}<br>
                    <strong>Severity:</strong> <span class="badge badge-${getSeverityColor(latestIssue.severity)}">${latestIssue.severity}</span><br>
                    <strong>Affected Area:</strong> ${latestIssue.affected_body_part}
                </div>
            </div>
            <div>
                <h4 style="font-size: 0.875rem; font-weight: 600; color: var(--gray-600); margin-bottom: 0.5rem;">
                    AI Possible Conditions
                </h4>
                <p style="font-size: 0.875rem;">
                    ${latestIssue.ai_possible_conditions}
                </p>
                <div style="margin-top: 0.5rem;">
                    <strong>Confidence:</strong> ${latestIssue.ai_confidence_score}%
                </div>
            </div>
            <div>
                <h4 style="font-size: 0.875rem; font-weight: 600; color: var(--gray-600); margin-bottom: 0.5rem;">
                    Risk Assessment
                </h4>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span class="badge badge-${getRiskColor(latestIssue.ai_risk_level)}" style="font-size: 1rem; padding: 0.5rem 1rem;">
                        ${latestIssue.ai_risk_level} Risk
                    </span>
                    <button onclick="useAIAnalysis()" class="btn btn-sm btn-primary">
                        <i class="fas fa-magic"></i> Auto-fill SOAP
                    </button>
                </div>
            </div>
        </div>
    `;

    aiCard.style.display = 'block';

    // Store for later use
    window.currentAIAnalysis = latestIssue;
}

function getSeverityColor(severity) {
    const colors = {
        'Mild': 'success',
        'Moderate': 'warning',
        'Severe': 'danger',
        'Critical': 'danger'
    };
    return colors[severity] || 'info';
}

function getRiskColor(risk) {
    const colors = {
        'Low': 'success',
        'Medium': 'warning',
        'High': 'danger',
        'Critical': 'danger'
    };
    return colors[risk] || 'info';
}

// ============================================================================
// USE AI ANALYSIS
// ============================================================================

function useAIAnalysis() {
    if (!window.currentAIAnalysis) return;

    const ai = window.currentAIAnalysis;

    // Auto-fill Subjective
    document.getElementById('soap-subjective').value =
        `Chief Complaint: ${ai.issue_text_raw}\n\n` +
        `Symptoms: ${ai.symptoms}\n` +
        `Duration: ${ai.duration}\n` +
        `Severity: ${ai.severity}\n` +
        `Affected Area: ${ai.affected_body_part}`;

    // Auto-fill Assessment
    document.getElementById('soap-assessment').value =
        `Provisional Diagnosis: ${ai.ai_possible_conditions}\n` +
        `AI Confidence: ${ai.ai_confidence_score}%\n` +
        `Risk Level: ${ai.ai_risk_level}`;

    showToast('SOAP notes auto-filled from AI analysis', 'success');
}



// ============================================================================
// VOICE INPUT (Web Speech API) - DEPRECATED
// NOTE: This old implementation has been replaced by Groq API voice input
// in voice-input.js which supports multilingual translation to English
// ============================================================================



// ============================================================================
// AI AUTO-COMPLETE SOAP NOTES
// ============================================================================

async function aiAutoComplete() {
    const subjective = document.getElementById('soap-subjective').value.trim();

    if (!subjective) {
        showToast('⚠️ Please enter patient complaints in Subjective section first', 'warning');
        return;
    }

    if (!currentPatient) {
        showToast('⚠️ Please select a patient first', 'warning');
        return;
    }

    showLoading('🤖 AI analyzing symptoms and generating medication recommendations...');

    try {
        // Prepare patient data for AI analysis
        const requestData = {
            complaint: subjective,
            patient_age: currentPatient.age || DateUtils.calculateAge(currentPatient.birth_date),
            patient_gender: currentPatient.sex,
            patient_allergies: currentPatient.allergies ? currentPatient.allergies.split(',').map(a => a.trim()) : []
        };

        // Call Gemini AI API via centralized route
        const response = await API.post('opd/analyze-symptoms', requestData);

        if (!response.success) {
            throw new Error(response.error || 'AI analysis failed');
        }

        const analysis = response.data;
        let structuredData = null;

        try {
            // Robust parsing: Find the first { and last }
            let rawText = analysis.treatment_plan;
            const start = rawText.indexOf('{');
            if (start !== -1) {
                // Find where it SHOULD end or just take from start
                const potentialJson = rawText.substring(start);

                try {
                    structuredData = JSON.parse(potentialJson);
                } catch (firstErr) {
                    // SMART REPAIR: If truncated, try to close it
                    console.warn("Attempting to repair truncated AI JSON...");
                    let repaired = potentialJson.trim();

                    // Count if we need to close an array of objects
                    if (repaired.includes('"medications": [') && !repaired.includes(']')) {
                        // Close last object if it was half-written
                        const lastComma = repaired.lastIndexOf(',');
                        const lastBrace = repaired.lastIndexOf('{');
                        if (lastComma > lastBrace) repaired = repaired.substring(0, lastComma);

                        repaired += '}] }'; // Try emergency closure
                    } else if (!repaired.endsWith('}')) {
                        repaired += ' }';
                    }

                    try {
                        structuredData = JSON.parse(repaired);
                    } catch (finalErr) {
                        throw new Error("UI could not repair AI response structure");
                    }
                }
            } else {
                throw new Error("No JSON structure found in AI response");
            }
        } catch (e) {
            console.error("JSON Error:", e);
            // Fallback: Dump to purpose but try to make it look decent
            const purposeElement = document.getElementById('plan-purpose');
            if (purposeElement) {
                purposeElement.value = analysis.treatment_plan.replace(/```json|```/g, '').trim();
            }
            hideLoading();
            return;
        }

        if (structuredData) {
            // Fill Purpose and Warnings
            const purposeElement = document.getElementById('plan-purpose');
            const warningsElement = document.getElementById('plan-warnings');

            if (purposeElement) {
                purposeElement.value = `Diagnosis: ${structuredData.diagnosis || ''}\n\nPlan: ${structuredData.purpose_summary || ''}\n\nLifestyle: ${structuredData.lifestyle || ''}`;
            }
            if (warningsElement) {
                warningsElement.value = structuredData.safety_warnings || '';
            }

            // Fill Medication Table
            if (structuredData.medications && structuredData.medications.length > 0) {
                const tbody = document.getElementById('medication-list-body');

                // Clear existing empty rows or ask to replace
                const rows = tbody.querySelectorAll('.med-row');
                const hasExistingData = Array.from(rows).some(r => r.querySelector('.med-name').value.trim() !== '');

                if (!hasExistingData || confirm('AI has recommended medications. Add them to the table?')) {
                    if (!hasExistingData) tbody.innerHTML = ''; // Clean start

                    structuredData.medications.forEach(med => {
                        const addFunc = typeof addMedicationRowEnhanced === 'function' ? addMedicationRowEnhanced : addMedicationRow;
                        addFunc({
                            name: med.name,
                            dosage: med.dosage,
                            timing: med.timing || 'After Food',
                            frequency: med.frequency
                        });
                    });
                }
            }
        }

        hideLoading();
        showToast('✅ AI Treatment Plan loaded into table and notes!', 'success');

    } catch (error) {
        hideLoading();
        console.error('AI Analysis Error:', error);
        showToast('❌ AI analysis failed: ' + error.message, 'error');
    }
}

// ============================================================================
// DISPLAY AI MEDICATION ANALYSIS
// ============================================================================

function displayAIMedicationAnalysis(analysis) {
    // Create or update AI medication card
    let medicationCard = document.getElementById('ai-medication-card');

    if (!medicationCard) {
        // Create new card after AI analysis card
        const aiAnalysisCard = document.getElementById('ai-analysis-card');
        medicationCard = document.createElement('div');
        medicationCard.id = 'ai-medication-card';
        medicationCard.className = 'ai-medication-card';

        if (aiAnalysisCard && aiAnalysisCard.parentNode) {
            aiAnalysisCard.parentNode.insertBefore(medicationCard, aiAnalysisCard.nextSibling);
        } else {
            // Insert before SOAP grid
            const soapGrid = document.querySelector('.soap-grid');
            if (soapGrid && soapGrid.parentNode) {
                soapGrid.parentNode.insertBefore(medicationCard, soapGrid);
            }
        }
    }

    // Build medication table HTML
    let medicationsHTML = '';
    if (analysis.medication_recommendations && analysis.medication_recommendations.length > 0) {
        medicationsHTML = `
            <table class="medication-table">
                <thead>
                    <tr>
                        <th>Medication</th>
                        <th>Dosage & Route</th>
                        <th>Frequency & Duration</th>
                        <th>Purpose</th>
                        <th>Safety Notes</th>
                    </tr>
                </thead>
                <tbody>
                    ${analysis.medication_recommendations.map(med => `
                        <tr>
                            <td><strong>${med.medication_name}</strong></td>
                            <td>${med.dosage}<br><small>${med.route}</small></td>
                            <td>${med.frequency}<br><small>${med.duration}</small><br><em>${med.timing || ''}</em></td>
                            <td>${med.purpose}</td>
                            <td>
                                ${med.safety_warnings && med.safety_warnings.length > 0 ?
                `<ul class="safety-list">${med.safety_warnings.map(w => `<li>${w}</li>`).join('')}</ul>` :
                'Standard precautions'}
                                ${med.side_effects && med.side_effects.length > 0 ?
                `<br><small><strong>Side effects:</strong> ${med.side_effects.join(', ')}</small>` :
                ''}
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    } else {
        medicationsHTML = '<p class="no-medications">No specific medications recommended. See clinical notes for guidance.</p>';
    }

    // Build allergy alerts
    let allergyAlertsHTML = '';
    if (analysis.allergy_alerts && analysis.allergy_alerts.length > 0) {
        allergyAlertsHTML = `
            <div class="allergy-alert-box">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>ALLERGY ALERTS:</strong>
                <ul>
                    ${analysis.allergy_alerts.map(alert => `<li>${alert}</li>`).join('')}
                </ul>
            </div>
        `;
    }

    // Build risk assessment badge
    const riskLevel = analysis.risk_assessment?.level || 'medium';
    const riskColors = {
        'low': 'success',
        'medium': 'warning',
        'high': 'danger',
        'critical': 'danger'
    };
    const riskColor = riskColors[riskLevel.toLowerCase()] || 'warning';

    // Build complete card HTML
    medicationCard.innerHTML = `
        <div class="ai-card-header">
            <div class="ai-badge">
                <i class="fas fa-pills"></i>
                AI Medication Recommendations
            </div>
            <span class="badge badge-${riskColor}" style="font-size: 0.9rem; padding: 0.5rem 1rem;">
                ${riskLevel.toUpperCase()} RISK
                ${analysis.risk_assessment?.urgent_care_needed ? ' - URGENT CARE NEEDED' : ''}
            </span>
        </div>
        
        <div class="ai-medication-content">
            ${allergyAlertsHTML}
            
            <div class="diagnosis-summary">
                <h4><i class="fas fa-stethoscope"></i> Provisional Diagnosis</h4>
                <p><strong>Primary:</strong> ${analysis.provisional_diagnosis?.primary || 'See clinical notes'}</p>
                ${analysis.provisional_diagnosis?.differential && analysis.provisional_diagnosis.differential.length > 0 ?
            `<p><strong>Differential:</strong> ${analysis.provisional_diagnosis.differential.join(', ')}</p>` :
            ''}
                <p><strong>Confidence:</strong> ${analysis.provisional_diagnosis?.confidence_score || 'N/A'}%</p>
            </div>
            
            <h4 style="margin-top: 1.5rem;"><i class="fas fa-prescription-bottle-alt"></i> Recommended Medications</h4>
            ${medicationsHTML}
            
            ${analysis.drug_interactions && analysis.drug_interactions.length > 0 ? `
                <div class="interaction-warning">
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>Drug Interaction Warnings:</strong>
                    <ul>
                        ${analysis.drug_interactions.map(interaction => `<li>${interaction}</li>`).join('')}
                    </ul>
                </div>
            ` : ''}
            
            ${analysis.lifestyle_recommendations && analysis.lifestyle_recommendations.length > 0 ? `
                <div class="lifestyle-recommendations">
                    <h4><i class="fas fa-heartbeat"></i> Lifestyle Recommendations</h4>
                    <ul>
                        ${analysis.lifestyle_recommendations.map(rec => `<li>${rec}</li>`).join('')}
                    </ul>
                </div>
            ` : ''}
            
            ${analysis.follow_up ? `
                <div class="follow-up-box">
                    <h4><i class="fas fa-calendar-check"></i> Follow-up</h4>
                    <p><strong>Recommended timeframe:</strong> ${analysis.follow_up.recommended_timeframe}</p>
                    ${analysis.follow_up.warning_signs && analysis.follow_up.warning_signs.length > 0 ? `
                        <p><strong>Warning signs requiring immediate attention:</strong></p>
                        <ul>
                            ${analysis.follow_up.warning_signs.map(sign => `<li>${sign}</li>`).join('')}
                        </ul>
                    ` : ''}
                </div>
            ` : ''}
            
            <div class="ai-disclaimer">
                <i class="fas fa-info-circle"></i>
                <strong>CLINICAL DECISION SUPPORT - ADVISORY ONLY</strong><br>
                ${analysis.disclaimer || 'All AI-generated recommendations must be reviewed and approved by the attending physician before prescription. This is decision support, not a replacement for clinical judgment.'}
            </div>
            
            <div class="ai-actions" style="margin-top: 1rem; text-align: center;">
                <button onclick="applyMedicationsToplan()" class="btn-primary-large">
                    <i class="fas fa-check"></i> Apply to Treatment Plan
                </button>
                <button onclick="closeMedicationCard()" class="btn-secondary-large">
                    <i class="fas fa-times"></i> Dismiss
                </button>
            </div>
        </div>
    `;

    medicationCard.style.display = 'block';

    // Store analysis for later use
    window.currentAIAnalysisFull = analysis;
}

// ============================================================================
// FORMAT TREATMENT PLAN
// ============================================================================

function formatTreatmentPlan(analysis) {
    let plan = '';

    // Diagnosis section
    plan += '=== DIAGNOSIS ===\n';
    plan += `Primary: ${analysis.provisional_diagnosis?.primary || 'See notes'}\n`;
    if (analysis.provisional_diagnosis?.differential && analysis.provisional_diagnosis.differential.length > 0) {
        plan += `Differential: ${analysis.provisional_diagnosis.differential.join(', ')}\n`;
    }
    plan += `Risk Level: ${analysis.risk_assessment?.level || 'Medium'}\n`;
    plan += '\n';

    // Medications section
    plan += '=== MEDICATIONS ===\n';
    if (analysis.medication_recommendations && analysis.medication_recommendations.length > 0) {
        analysis.medication_recommendations.forEach((med, index) => {
            plan += `${index + 1}. ${med.medication_name}\n`;
            plan += `   Dosage: ${med.dosage}\n`;
            plan += `   Frequency: ${med.frequency}\n`;
            plan += `   Duration: ${med.duration}\n`;
            plan += `   Route: ${med.route}\n`;
            if (med.timing) {
                plan += `   Timing: ${med.timing}\n`;
            }
            plan += `   Purpose: ${med.purpose}\n`;
            if (med.safety_warnings && med.safety_warnings.length > 0) {
                plan += `   ⚠️ Warnings: ${med.safety_warnings.join('; ')}\n`;
            }
            plan += '\n';
        });
    } else {
        plan += 'No specific medications recommended at this time.\n\n';
    }

    // Lifestyle recommendations
    if (analysis.lifestyle_recommendations && analysis.lifestyle_recommendations.length > 0) {
        plan += '=== LIFESTYLE & NON-PHARMACOLOGICAL ===\n';
        analysis.lifestyle_recommendations.forEach((rec, index) => {
            plan += `${index + 1}. ${rec}\n`;
        });
        plan += '\n';
    }

    // Follow-up
    if (analysis.follow_up) {
        plan += '=== FOLLOW-UP ===\n';
        plan += `Timeframe: ${analysis.follow_up.recommended_timeframe}\n`;
        if (analysis.follow_up.warning_signs && analysis.follow_up.warning_signs.length > 0) {
            plan += 'Warning signs requiring immediate attention:\n';
            analysis.follow_up.warning_signs.forEach(sign => {
                plan += `  - ${sign}\n`;
            });
        }
        plan += '\n';
    }

    // Clinical notes
    if (analysis.clinical_notes) {
        plan += '=== CLINICAL NOTES ===\n';
        plan += analysis.clinical_notes + '\n\n';
    }

    // Disclaimer
    plan += '=== IMPORTANT ===\n';
    plan += '⚠️ AI-GENERATED RECOMMENDATIONS - REQUIRES PHYSICIAN REVIEW\n';
    plan += analysis.disclaimer || 'All recommendations must be verified and approved by attending physician.';

    return plan;
}

// ============================================================================
// APPLY MEDICATIONS TO PLAN
// ============================================================================

function applyMedicationsToplan() {
    if (!window.currentAIAnalysisFull) {
        showToast('No AI analysis available', 'error');
        return;
    }

    const meds = window.currentAIAnalysisFull.medication_recommendations;
    if (meds && meds.length > 0) {
        const tbody = document.getElementById('medication-list-body');
        const rows = tbody.querySelectorAll('.med-row');

        // If there's only one row and it's empty, clear it
        if (rows.length === 1) {
            const firstName = rows[0].querySelector('.med-name').value.trim();
            if (!firstName) tbody.innerHTML = '';
        }

        meds.forEach(med => {
            addMedicationRow({
                name: med.name || med.medication_name,
                dosage: med.dosage,
                timing: med.timing || 'After Food',
                frequency: med.frequency,
                duration: med.duration,
                qty: med.qty || med.quantity || ''
            });
        });

        // Fill purpose and warnings
        const purpose = window.currentAIAnalysisFull.provisional_diagnosis?.primary || '';
        const purposeElement = document.getElementById('plan-purpose');
        if (purpose && purposeElement) {
            purposeElement.value = `Diagnosis: ${purpose}\n${window.currentAIAnalysisFull.clinical_notes || ''}`;
        }

        const warnings = meds.flatMap(m => m.safety_warnings || []).join('; ');
        const warningsElement = document.getElementById('plan-warnings');
        if (warnings && warningsElement) {
            warningsElement.value = warnings;
        }

        showToast('✅ AI medications added to table', 'success');

        // Scroll to plan section
        document.querySelector('.soap-plan').scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
        showToast('No medication recommendations found in analysis', 'warning');
    }
}

function closeMedicationCard() {
    const card = document.getElementById('ai-medication-card');
    if (card) {
        card.style.display = 'none';
    }
}

// ============================================================================
// PREVIOUS INSTRUCTIONS
// ============================================================================

function checkPreviousInstructions(patientId) {
    const prevInstructions = sessionStorage.getItem('consultation_prev_instructions');
    const storedId = sessionStorage.getItem('consultation_patient_id'); // Just to be safe we match

    if (prevInstructions && (!storedId || storedId === patientId)) {
        // Create or update a display element
        let container = document.getElementById('previous-instructions-alert');
        if (!container) {
            container = document.createElement('div');
            container.id = 'previous-instructions-alert';
            container.style.cssText = `
                background: #f0fdf4; 
                border: 1px solid #86efac; 
                color: #166534; 
                padding: 1rem; 
                border-radius: 0.75rem; 
                margin-bottom: 2rem; 
                display: flex; 
                align-items: start; 
                gap: 1rem;
            `;
            // Insert after header or before SOAP grid
            const soapGrid = document.querySelector('.soap-grid');
            if (soapGrid) {
                soapGrid.parentNode.insertBefore(container, soapGrid);
            }
        }

        container.innerHTML = `
            <i class="fas fa-history" style="font-size: 1.25rem; margin-top: 0.25rem;"></i>
            <div style="flex: 1;">
                <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem; font-weight: 700;">Previous General Instructions</h4>
                <p style="margin: 0; white-space: pre-wrap;">${prevInstructions}</p>
            </div>
            <button onclick="this.parentElement.remove()" style="background:none; border:none; cursor:pointer; color: #166534;">
                <i class="fas fa-times"></i>
            </button>
        `;

        // Optional: clear it so it doesn't persist on refresh if desired, 
        // but keeping it until explictly closed or new patient selected is often better.
        // sessionStorage.removeItem('consultation_prev_instructions');
    }
}

// ============================================================================
// AI SUGGESTION GENERATOR (Medical Knowledge Base) - FALLBACK
// ============================================================================

function generateAISuggestions(subjective) {

    const lowerText = subjective.toLowerCase();

    // Extract symptoms and keywords
    const symptoms = {
        fever: /fever|temperature|pyrexia/i.test(subjective),
        headache: /headache|head pain|cephalalgia/i.test(subjective),
        cough: /cough|coughing/i.test(subjective),
        cold: /cold|runny nose|rhinorrhea/i.test(subjective),
        stomachPain: /stomach|abdominal pain|belly/i.test(subjective),
        backPain: /back pain|backache/i.test(subjective),
        chestPain: /chest pain|cardiac/i.test(subjective),
        breathless: /breathless|shortness of breath|dyspnea/i.test(subjective),
        dizziness: /dizzy|dizziness|vertigo/i.test(subjective),
        nausea: /nausea|vomit|vomiting/i.test(subjective),
        diarrhea: /diarrhea|loose stools/i.test(subjective),
        bodyAche: /body ache|myalgia|muscle pain/i.test(subjective)
    };

    // Determine likely condition based on symptom pattern
    let objective = '';
    let assessment = '';
    let plan = '';

    // Pattern 1: Fever alone = Simple Fever
    if (symptoms.fever && !symptoms.headache && !symptoms.bodyAche && !symptoms.cough && !symptoms.cold) {
        assessment = `Diagnosis: Fever`;
        plan = `💊 MEDICATIONS:

• Tab. Paracetamol 650mg - Take 1 tablet 3 times daily after meals × 5 days
• Tab. Cetirizine 10mg - Take 1 tablet at bedtime × 5 days

📝 Instructions:
- Drink plenty of water (3-4 liters/day)
- Take complete rest
- Review after 3 days if fever persists`;
    }

    // Pattern 1A: Headache alone
    else if (symptoms.headache && !symptoms.fever && !symptoms.bodyAche) {
        assessment = `Diagnosis: Headache`;
        plan = `💊 MEDICATIONS:

• Tab. Paracetamol 500mg - Take 1 tablet when needed (max 3 times/day)
• Tab. Ibuprofen 400mg - Alternative if Paracetamol doesn't help

📝 Instructions:
- Rest in a quiet, dark room
- Drink plenty of water
- Avoid screen time
- If severe or persistent >3 days, consult for further evaluation`;
    }

    // Pattern 2: Fever + Headache + Body ache = Viral Fever
    else if (symptoms.fever && symptoms.headache && symptoms.bodyAche) {
        objective = `Physical Examination:
- General: Patient appears ill, febrile
- Vital Signs: Temperature elevated (suggest recording actual vitals)
- ENT: Throat examination - check for pharyngeal congestion
- Respiratory: Chest clear to auscultation
- CVS: S1 S2 heard, no murmurs
- Abdomen: Soft, non-tender

Suggested Vitals to Record:
- Temperature: Expected 100-102°F
- Pulse: May be elevated
- BP: Usually normal
- SPO2: Should be >95%`;

        assessment = `Diagnosis: Viral Fever`;
        plan = `💊 MEDICATIONS:

• Tab. Paracetamol 650mg - Take 1 tablet 3 times daily after meals × 5 days
• Tab. Cetirizine 10mg - Take 1 tablet at bedtime × 5 days
• Tab. Vitamin C 500mg - Take 1 tablet daily × 5 days

📝 Instructions:
- Drink plenty of water (3-4 liters/day)
- Take complete rest
- Light diet recommended
- Review after 3 days if fever persists`;
    }

    // Pattern 3: Cough + Cold = Upper Respiratory Infection
    else if (symptoms.cough && symptoms.cold) {
        objective = `Physical Examination:
- General: Patient alert, afebrile/low-grade fever
- Vital Signs: Within normal limits (record actual values)
- ENT: Nasal congestion present, pharyngeal congestion noted
- Respiratory: Bilateral air entry equal, no added sounds
- CVS: S1 S2 heard, regular rhythm

Suggested Vitals:
- Temperature: Normal to 99°F
- Pulse: 70-90 bpm
- BP: Normal range
- SPO2: >95%`;

        assessment = `Diagnosis: Cough & Cold (URTI)`;
        plan = `💊 MEDICATIONS:

• Tab. Cetirizine 10mg - Take 1 tablet at bedtime × 5 days
• Syrup Salbutamol + Guaifenesin 10ml - Take 3 times daily × 5 days
• Tab. Paracetamol 500mg - Take when needed for fever/headache

📝 Instructions:
- Steam inhalation 2-3 times daily
- Warm salt water gargles
- Drink warm fluids
- Avoid cold drinks`;
    }

    // Pattern 3: Stomach Pain = Gastritis/Dyspepsia
    else if (symptoms.stomachPain || symptoms.nausea) {
        objective = `Physical Examination:
- General: Patient conscious, oriented
- Vital Signs: Stable (record actual values)
- Abdomen: 
  * Inspection: No distension
  * Palpation: Tenderness in epigastric region
  * Percussion: Tympanic
  * Auscultation: Normal bowel sounds
- No signs of peritonitis

Suggested Vitals:
- Pulse: May be slightly elevated if pain
- BP: Usually normal
- Temperature: Afebrile`;

        assessment = `Provisional Diagnosis: Acute Gastritis / Dyspepsia

Differential Diagnoses:
1. Acute gastritis (most likely)
2. Peptic ulcer disease
3. GERD (Gastroesophageal reflux)
4. Food poisoning (if associated with vomiting/diarrhea)

Severity: Mild to Moderate

Investigations (if symptoms persist):
- Upper GI Endoscopy
- H. pylori testing
- Ultrasound abdomen if indicated`;

        plan = `Treatment Plan:
1. Medications:
   - Tab. Pantoprazole 40mg BD before meals for 7 days
   - Syrup Sucralfate 10ml TDS for gastric protection
   - Tab. Domperidone 10mg TDS for nausea (if present)
   - Antacid gel 2 tsp SOS for immediate relief

2. Dietary Modifications:
   - Avoid spicy, oily, and fried foods
   - Small frequent meals
   - Avoid tea, coffee, alcohol
   - Eat bland diet (rice, banana, toast)
   - Avoid late-night meals

3. Lifestyle:
   - Avoid NSAIDs (painkillers)
   - Reduce stress
   - Adequate sleep
   - Avoid smoking if applicable

4. Follow-up:
   - Review after 7 days
   - Return if severe pain, vomiting blood, black stools

5. Patient Education:
   - Explained dietary restrictions
   - Advised to complete medication course
   - Counseled on stress management`;
    }

    // Pattern 4: Chest Pain + Breathlessness = Cardiac/Respiratory
    else if (symptoms.chestPain || symptoms.breathless) {
        objective = `Physical Examination:
⚠️ URGENT - Detailed cardiovascular and respiratory examination required

- General: Assess distress level
- Vital Signs: CRITICAL - Record immediately
- CVS: Heart sounds, murmurs, gallop rhythm
- Respiratory: Breath sounds, crackles, wheezing
- Peripheral: Edema, cyanosis, JVP

IMMEDIATE Vitals Required:
- BP: Check for hypertension/hypotension
- Pulse: Rate, rhythm, volume
- SPO2: CRITICAL - Check oxygen saturation
- Temperature: Rule out infection`;

        assessment = `⚠️ REQUIRES IMMEDIATE ATTENTION

Differential Diagnoses (URGENT):
1. Acute Coronary Syndrome (ACS)
2. Pulmonary embolism
3. Pneumonia
4. Bronchial asthma exacerbation
5. GERD (if pain is burning type)

Severity: MODERATE TO HIGH RISK

IMMEDIATE Investigations Required:
- ECG (12-lead) - STAT
- Chest X-ray
- Cardiac enzymes (Troponin I/T)
- D-dimer if PE suspected
- ABG if severe breathlessness`;

        plan = `URGENT Management Plan:

1. Immediate Actions:
   - Oxygen if SPO2 <94%
   - IV access
   - Continuous monitoring
   - ECG within 10 minutes

2. Medications (After ECG):
   - Tab. Aspirin 325mg STAT (if cardiac cause suspected)
   - Tab. Sorbitrate 5mg SL for angina
   - Bronchodilator if respiratory cause

3. Referral:
   - URGENT cardiology/pulmonology consultation
   - Consider emergency department transfer

4. Investigations:
   - ECG, Troponin, Chest X-ray - STAT
   - Echo if cardiac cause

⚠️ DO NOT DISCHARGE - Requires observation/admission`;
    }

    // Default generic suggestion
    else {
        objective = `Physical Examination:
- General: Patient appears [comfortable/distressed]
- Vital Signs: Record actual measurements
  * Temperature: ___°F
  * Pulse: ___ bpm
  * BP: ___/___ mmHg
  * SPO2: ___%
  * Weight: ___ kg

- Systemic Examination:
  * ENT: ___
  * Respiratory: ___
  * CVS: ___
  * Abdomen: ___
  * CNS: ___

💡 Click "Add Vitals" button to record measurements`;

        assessment = `Diagnosis: ${subjective.substring(0, 50)}...`;
        plan = `💊 SUGGESTED MEDICATIONS:

• Tab. Paracetamol 500mg - Take 1 tablet when needed (max 3 times/day)
• Adequate rest and hydration recommended

📝 Note: For specific medication recommendations, please provide more details about:
- Main symptoms (fever, headache, cough, stomach pain, etc.)
- Duration of symptoms
- Severity level

Examples:
- "Patient has fever" → Get fever medications
- "Patient has headache" → Get headache medications
- "Patient has cough and cold" → Get cold medications`;
    }

    return {
        objective: objective,
        assessment: assessment,
        plan: plan
    };
}

// ============================================================================
// AI DIAGNOSIS SUGGESTION (REMOVED - Assessment section not in use)
// ============================================================================

// Removed since Assessment section was removed from HTML
// function getAIDiagnosis() { ... }

// ============================================================================
// PRESCRIPTION HELPER
// ============================================================================

function openPrescriptionHelper() {
    const patientId = document.getElementById('patient-select').value;
    if (!patientId) {
        showToast('Please select a patient first', 'warning');
        return;
    }

    window.open(`prescription.php?patient_id=${patientId}`, '_blank');
}

// ============================================================================
// MEDICATION TABLE MANAGEMENT
// ============================================================================

function addMedicationRow(medData = null) {
    const tbody = document.getElementById('medication-list-body');
    const row = document.createElement('tr');
    row.className = 'med-row';

    row.innerHTML = `
        <td>
            <input type="text" class="med-name" placeholder="Search Tablet..." value="${(medData && medData.name) ? medData.name : ''}">
        </td>
        <td>
            <input type="text" class="med-dosage" placeholder="e.g. 1 Tab" value="${(medData && medData.dosage) ? medData.dosage : ''}">
        </td>
        <td>
            <select class="med-timing">
                <option value="After Food" ${(medData && medData.timing === 'After Food') ? 'selected' : ''}>After Food</option>
                <option value="Before Food" ${(medData && medData.timing === 'Before Food') ? 'selected' : ''}>Before Food</option>
                <option value="With Food" ${(medData && medData.timing === 'With Food') ? 'selected' : ''}>With Food</option>
                <option value="Empty Stomach" ${(medData && medData.timing === 'Empty Stomach') ? 'selected' : ''}>Empty Stomach</option>
            </select>
        </td>
        <td>
            <input type="text" class="med-frequency" placeholder="e.g. 1-0-1" value="${(medData && medData.frequency) ? medData.frequency : ''}">
        </td>
        <td>
            <input type="text" class="med-duration" placeholder="e.g. 5 Days" value="${(medData && medData.duration) ? medData.duration : ''}">
        </td>
        <td>
            <input type="number" class="med-qty" placeholder="0" value="${(medData && medData.qty) ? medData.qty : ''}">
        </td>
        <td>
            <button type="button" class="btn-remove-row" onclick="removeMedicationRow(this)" title="Remove Row">
                <i class="fas fa-times"></i>
            </button>
        </td>
    `;

    tbody.appendChild(row);

    // Scroll to bottom if list is long
    const wrapper = document.querySelector('.prescription-table-wrapper');
    if (wrapper) wrapper.scrollTop = wrapper.scrollHeight;
}

function removeMedicationRow(button) {
    const row = button.closest('tr');
    const tbody = document.getElementById('medication-list-body');

    // Keep at least one row
    if (tbody.querySelectorAll('tr').length > 1) {
        row.remove();
    } else {
        // Just clear the inputs if it's the last row
        row.querySelectorAll('input').forEach(input => input.value = '');
        row.querySelector('select').selectedIndex = 0;
    }
}

// ============================================================================
// SAVE CONSULTATION
// ============================================================================

// ============================================================================
// UNIFIED SAVE CONSULTATION - Integrates all three previous functions
// ============================================================================

/**
 * Direct Save Consultation (Finalize and Close)
 */
async function directSaveConsultation() {
    const patientId = document.getElementById('patient-select').value;

    if (!patientId) {
        showToast('⚠️ Please select a patient first', 'warning');
        return;
    }

    // Validate essential data quickly
    const subjective = document.getElementById('soap-subjective')?.value.trim();
    if (!subjective) {
        showToast('⚠️ Subjective (patient complaints) is required', 'warning');
        return;
    }

    selectedSaveOption = 'completed';
    await executeUnifiedSave();
}

/**
 * @deprecated
 */
function openSaveConsultationModal() {
    directSaveConsultation();
}

// Direct Save Consultation is now the primary entry point.
// Modal logic removed for faster doctor workflow.

/**
 * Selects a save option (draft or completed)
 */
function selectSaveOption(option) {
    selectedSaveOption = option;

    // Update UI
    document.querySelectorAll('.save-option-card').forEach(card => {
        card.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');

    // Validate and show summary
    const validation = validateConsultationData(option);
    const summaryDiv = document.getElementById('save-validation-summary');

    if (validation.valid) {
        summaryDiv.className = 'save-validation-summary success';
        summaryDiv.innerHTML = `
            <h4 style="margin: 0 0 0.5rem 0; color: #065F46; font-size: 0.95rem;">
                <i class="fas fa-check-circle"></i> Ready to Save
            </h4>
            <p style="margin: 0; font-size: 0.85rem; color: #047857;">
                ${validation.message}
            </p>
            <button type="button" onclick="executeUnifiedSave()" class="btn-primary-large" style="margin-top: 1rem; width: 100%;">
                <i class="fas fa-save"></i> ${option === 'draft' ? 'Save as Draft' : 'Complete Consultation'}
            </button>
        `;
        summaryDiv.style.display = 'block';
    } else {
        summaryDiv.className = 'save-validation-summary error';
        summaryDiv.innerHTML = `
            <h4 style="margin: 0 0 0.5rem 0; color: #991B1B; font-size: 0.95rem;">
                <i class="fas fa-exclamation-triangle"></i> Validation Issues
            </h4>
            <ul style="margin: 0; padding-left: 1.5rem; font-size: 0.85rem; color: #B91C1C;">
                ${validation.errors.map(err => `<li>${err}</li>`).join('')}
            </ul>
            ${option === 'draft' ? `
                <button type="button" onclick="executeUnifiedSave()" class="btn-secondary-large" style="margin-top: 1rem; width: 100%;">
                    <i class="fas fa-save"></i> Save Draft Anyway
                </button>
            ` : ''}
        `;
        summaryDiv.style.display = 'block';
    }
}

/**
 * Validates consultation data before saving
 */
function validateConsultationData(saveType) {
    const errors = [];
    let itemCount = 0;

    // Check SOAP notes
    const subjective = document.getElementById('soap-subjective')?.value.trim();
    if (subjective) itemCount++;

    // Check medications
    const medicationRows = document.querySelectorAll('.elite-med-row');
    let medicationCount = 0;
    medicationRows.forEach(row => {
        const name = row.querySelector('.med-name').value.trim();
        if (name) medicationCount++;
    });
    if (medicationCount > 0) itemCount++;

    // Check follow-up
    const followUpDate = document.getElementById('followup-date')?.value;
    if (followUpDate) itemCount++;

    // Check clinical notes
    const clinicalNotes = document.getElementById('clinical-notes')?.value.trim();
    if (clinicalNotes) itemCount++;

    // Validation for "Complete" status
    if (saveType === 'completed') {
        if (!subjective) {
            errors.push('Subjective (patient complaints) is required for completion');
        }
        // Removed medication requirement as per user request
    }

    // General validation
    if (itemCount === 0) {
        errors.push('No data to save. Please fill in at least one section.');
    }

    return {
        valid: errors.length === 0,
        errors: errors,
        message: errors.length === 0
            ? `All data will be saved including ${medicationCount} medication(s)${followUpDate ? ' and follow-up plan' : ''}.`
            : 'Please address the issues above before saving.'
    };
}

/**
 * Executes the unified save operation
 * Combines: Consultation save + Follow-up save
 */
async function executeUnifiedSave() {
    if (!selectedSaveOption) {
        showToast('Please select a save option', 'warning');
        return;
    }

    const patientId = document.getElementById('patient-select').value;

    if (!patientId) {
        showToast('Please select a patient', 'warning');
        return;
    }

    // Close the modal
    Modal.hide('save-consultation-modal');

    // Collect medication table data
    const medicationRows = document.querySelectorAll('.elite-med-row');
    const medications = [];
    medicationRows.forEach(row => {
        const name = row.querySelector('.med-name').value.trim();
        if (name) {
            medications.push({
                name: name,
                dosage: row.querySelector('.med-dosage').value.trim(),
                timing: row.querySelector('.med-timing').value,
                frequency: row.querySelector('.med-frequency').value.trim(),
                duration: (row.querySelector('.med-duration-num').value + ' ' + row.querySelector('.med-duration-unit').value).trim(),
                qty: row.querySelector('.med-qty').value.trim(),
                unit: row.querySelector('.med-qty-unit')?.value || 'Tabs'
            });
        }
    });

    // Collect Purpose and Warnings
    const purpose = document.getElementById('plan-purpose')?.value.trim() || '';
    const warnings = document.getElementById('plan-warnings')?.value.trim() || '';

    // Combine for general instructions (if needed by old system)
    let generalInstructions = '';
    if (purpose) generalInstructions += `PURPOSE: ${purpose}\n`;
    if (warnings) generalInstructions += `WARNINGS: ${warnings}\n`;

    // Collect follow-up data
    const followUpDate = document.getElementById('followup-date')?.value || null;
    const followUpInstructions = document.getElementById('followup-instructions')?.value || null;

    // Capture all fields for the new schema
    const consultationData = {
        patient_id: patientId,
        doctor_id: CURRENT_DOCTOR_ID,
        appointment_id: window.currentAppointmentId || null,
        issue_description_id: window.currentIssueDescriptionId || null, // Ensure this global var is set if available

        // SOAP Notes
        soap_subjective: document.getElementById('soap-subjective')?.value || '',
        soap_objective: document.getElementById('soap-objective')?.value || '', // If you have this field in UI
        soap_assessment: document.getElementById('soap-assessment')?.value || '', // If you have this field in UI
        soap_plan: JSON.stringify({
            medications: medications,
            purpose: purpose,
            warnings: warnings
        }),

        // Extended Clinical Data
        vital_signs: JSON.stringify({}), // Should be populated from actual vitals input if available
        physical_examination: document.getElementById('physical-examination')?.value || '', // Add this input to UI if missing
        final_diagnosis: document.getElementById('final-diagnosis')?.value || '', // Add this input to UI if missing
        clinical_notes: document.getElementById('clinical-notes')?.value || '',

        // Follow-up & Instructions
        follow_up_date: followUpDate,
        follow_up_instructions: followUpInstructions,
        general_instructions: generalInstructions,

        // Metadata
        consultation_duration: 15, // Default or calculate time difference
        status: selectedSaveOption === 'draft' ? 1 : 0,

        // Medicines for Prescriptions Table
        medicines: JSON.stringify(medications)
    };

    // Legacy support/Mapping (if needed by backend logic not yet updated)
    // consultationData.diagnosis = consultationData.soap_assessment; // Example mapping

    console.log('=== UNIFIED SAVE CONSULTATION ===');
    console.log('Save Type:', selectedSaveOption);
    console.log('Medications array:', medications);
    console.log('Medicines JSON:', consultationData.medicines);
    console.log('Full consultation data:', consultationData);


    try {
        showLoading('💾 Saving consultation...');

        // Save consultation (includes follow-up data)
        const response = await API.post('consultations', consultationData);

        if (response.success) {
            hideLoading();

            // Success message
            const successMsg = selectedSaveOption === 'draft'
                ? '✅ Consultation saved as draft successfully!'
                : '✅ Consultation completed successfully!';

            showToast(successMsg, 'success');

            // If completed, redirect to patient list
            if (selectedSaveOption === 'completed') {
                setTimeout(() => {
                    window.location.href = 'mypatient.php';
                }, 1500);
            } else {
                // For draft, optionally clear the form or keep it
                // Currently keeping it for further editing
                showToast('You can continue editing or close this page', 'info');
            }
        } else {
            hideLoading();
            showToast(response.error || 'Failed to save consultation', 'error');
        }
    } catch (error) {
        hideLoading();
        showToast('Failed to save consultation', 'error');
        console.error('Error:', error);
    }
}

// ============================================================================
// LEGACY FUNCTION - Keep for backward compatibility
// ============================================================================

/**
 * @deprecated Use openSaveConsultationModal() instead
 * Kept for backward compatibility with any external references
 */
async function saveConsultation(status) {
    console.warn('saveConsultation() is deprecated. Use openSaveConsultationModal() instead.');
    selectedSaveOption = status;
    await executeUnifiedSave();
}


// ============================================================================
// SAVE FOLLOW-UP (Standalone)
// ============================================================================

async function saveFollowUpMain() {
    const patientId = document.getElementById('patient-select')?.value;
    const followUpDate = document.getElementById('followup-date')?.value;
    const notes = document.getElementById('followup-instructions')?.value || '';
    const clinicalNotes = document.getElementById('clinical-notes')?.value || '';

    if (!patientId) {
        showToast('Please select a patient', 'warning');
        return;
    }

    if (!followUpDate) {
        showToast('Please select a follow-up date', 'warning');
        return;
    }

    const data = {
        patient_id: patientId,
        doctor_id: CURRENT_DOCTOR_ID,
        // appointment_id: Try to get from URL or session if available, otherwise backend handles it
        // We don't have explicit appointment_id in this view context usually
        appointment_id: null,
        follow_up_date: followUpDate,
        notes: notes,
        clinical_notes: clinicalNotes
    };

    try {
        showLoading('Saving follow-up plan...');
        const response = await API.post('opd/follow-up', data);

        if (response.success) {
            showToast('Follow-up plan saved successfully', 'success');
        } else {
            showToast(response.error || 'Failed to save follow-up', 'error');
        }
    } catch (error) {
        showToast('Error connecting to server', 'error');
        console.error(error);
    } finally {
        hideLoading();
    }
}

/**
 * View complete patient medical history in a modal
 */
async function viewPatientHistory() {
    const patientId = document.getElementById('patient-select').value;
    if (!patientId) {
        showToast('Please select a patient first', 'warning');
        return;
    }

    try {
        showLoading('Loading patient medical history...');

        const [consultations, prescriptions] = await Promise.all([
            API.get(`consultations?patient_id=${patientId}`).catch(() => ({ data: [] })),
            API.get(`prescriptions/patient/${patientId}`).catch(() => ({ data: [] }))
        ]);

        hideLoading();

        // Show history modal
        showMedicalHistoryModal({
            patient: currentPatient,
            consultations: consultations.data || [],
            prescriptions: prescriptions.data || [],
            vitals: []
        });

    } catch (error) {
        hideLoading();
        console.error('Error loading medical history:', error);
        showToast('Failed to load medical history', 'error');
    }
}

/**
 * Display medical history in a modal
 */
function showMedicalHistoryModal(data) {
    const { patient, consultations, prescriptions, vitals } = data;

    // Create modal
    const modal = document.createElement('div');
    modal.className = 'medical-history-modal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        padding: 2rem;
        animation: fadeIn 0.3s ease;
    `;

    // Create modal content
    const content = document.createElement('div');
    content.style.cssText = `
        background: white;
        border-radius: 16px;
        width: 100%;
        max-width: 1200px;
        max-height: 90vh;
        overflow: hidden;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        display: flex;
        flex-direction: column;
        animation: slideUp 0.3s ease;
    `;

    // Build HTML
    content.innerHTML = `
        <div style="
            background: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%);
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 3px solid #1f6b4a;
        ">
            <div>
                <h2 style="margin: 0; font-size: 1.5rem; font-weight: 700;">
                    <i class="fas fa-file-medical"></i> Complete Medical History
                </h2>
                <p style="margin: 0.5rem 0 0 0; opacity: 0.9; font-size: 0.95rem;">
                    ${patient.first_name} ${patient.last_name} (${patient.patient_id})
                </p>
            </div>
            <button onclick="this.closest('.medical-history-modal').remove()" style="
                background: rgba(255, 255, 255, 0.2);
                border: none;
                color: white;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                cursor: pointer;
                font-size: 1.2rem;
                transition: all 0.2s ease;
            " onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'" 
               onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div style="
            flex: 1;
            overflow-y: auto;
            padding: 2rem;
        ">
            ${buildHistoryContent(consultations, prescriptions, vitals)}
        </div>
    `;

    modal.appendChild(content);
    document.body.appendChild(modal);

    // Close on backdrop click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

/**
 * Format medications list for display
 */
function formatMedicationsList(medications) {
    if (!medications) return 'No medications prescribed';

    // If it's already formatted as HTML or has line breaks
    if (typeof medications === 'string') {
        // Check if it's JSON array
        try {
            const parsed = JSON.parse(medications);
            if (Array.isArray(parsed)) {
                return parsed.map(med => {
                    if (typeof med === 'object') {
                        return `<div style="margin-bottom: 0.5rem; padding-left: 1rem; position: relative;">
                            <span style="position: absolute; left: 0; color: #10B981;">●</span>
                            <strong>${med.name || med.tablet_name || 'Unknown'}</strong> - 
                            ${med.dosage || ''} ${med.timing || ''} ${med.frequency || ''} 
                            ${med.duration ? `for ${med.duration}` : ''}
                        </div>`;
                    }
                    return `<div style="margin-bottom: 0.5rem; padding-left: 1rem; position: relative;">
                        <span style="position: absolute; left: 0; color: #10B981;">●</span>
                        ${med}
                    </div>`;
                }).join('');
            }
        } catch (e) {
            // Not JSON, treat as plain text
        }

        // Split by common delimiters and format
        const lines = medications.split(/[\n,;]/).filter(line => line.trim());
        if (lines.length > 1) {
            return lines.map(line => `
                <div style="margin-bottom: 0.5rem; padding-left: 1rem; position: relative;">
                    <span style="position: absolute; left: 0; color: #10B981;">●</span>
                    ${line.trim()}
                </div>
            `).join('');
        }

        // Single line medication
        return `<div style="padding-left: 1rem; position: relative;">
            <span style="position: absolute; left: 0; color: #10B981;">●</span>
            ${medications}
        </div>`;
    }

    // If it's an array
    if (Array.isArray(medications)) {
        return medications.map(med => {
            if (typeof med === 'object') {
                return `<div style="margin-bottom: 0.5rem; padding-left: 1rem; position: relative;">
                    <span style="position: absolute; left: 0; color: #10B981;">●</span>
                    <strong>${med.name || med.tablet_name || 'Unknown'}</strong> - 
                    ${med.dosage || ''} ${med.timing || ''} ${med.frequency || ''} 
                    ${med.duration ? `for ${med.duration}` : ''}
                </div>`;
            }
            return `<div style="margin-bottom: 0.5rem; padding-left: 1rem; position: relative;">
                <span style="position: absolute; left: 0; color: #10B981;">●</span>
                ${med}
            </div>`;
        }).join('');
    }

    return medications.toString();
}

/**
 * Build history content HTML
 */
function buildHistoryContent(consultations, prescriptions, vitals) {
    let html = '';

    // Summary Cards
    html += `
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
            <div style="background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%); padding: 1.5rem; border-radius: 12px; border: 1px solid #93C5FD;">
                <div style="color: #1E40AF; font-size: 2rem; margin-bottom: 0.5rem;">
                    <i class="fas fa-notes-medical"></i>
                </div>
                <div style="font-size: 2rem; font-weight: 700; color: #1E3A8A; margin-bottom: 0.25rem;">
                    ${consultations.length}
                </div>
                <div style="color: #1E40AF; font-size: 0.875rem; font-weight: 600;">
                    Total Consultations
                </div>
            </div>
            
            <div style="background: linear-gradient(135deg, #F0FDF4 0%, #DCFCE7 100%); padding: 1.5rem; border-radius: 12px; border: 1px solid #86EFAC;">
                <div style="color: #15803D; font-size: 2rem; margin-bottom: 0.5rem;">
                    <i class="fas fa-prescription-bottle-alt"></i>
                </div>
                <div style="font-size: 2rem; font-weight: 700; color: #14532D; margin-bottom: 0.25rem;">
                    ${prescriptions.length}
                </div>
                <div style="color: #15803D; font-size: 0.875rem; font-weight: 600;">
                    Prescriptions Issued
                </div>
            </div>
            
            <div style="background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); padding: 1.5rem; border-radius: 12px; border: 1px solid #FCD34D;">
                <div style="color: #92400E; font-size: 2rem; margin-bottom: 0.5rem;">
                    <i class="fas fa-heartbeat"></i>
                </div>
                <div style="font-size: 2rem; font-weight: 700; color: #78350F; margin-bottom: 0.25rem;">
                    ${vitals.length}
                </div>
                <div style="color: #92400E; font-size: 0.875rem; font-weight: 600;">
                    Vital Records
                </div>
            </div>
        </div>
    `;

    // Consultations Timeline
    if (consultations.length > 0) {
        html += `
            <div style="margin-bottom: 2rem;">
                <h3 style="font-size: 1.25rem; font-weight: 700; color: #0F172A; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-clipboard-list" style="color: #1f6b4a;"></i>
                    Consultation History
                </h3>
                <div style="position: relative; padding-left: 2rem;">
                    <div style="position: absolute; left: 0.5rem; top: 0; bottom: 0; width: 2px; background: linear-gradient(to bottom, #1f6b4a, #e2e8f0);"></div>
                    ${consultations.map((consult, index) => {
            // Find matching prescription for this consultation
            const relatedPrescription = prescriptions.find(rx =>
                rx.consultation_id === consult.consultation_id ||
                rx.appointment_id === consult.appointment_id ||
                rx.prescription_date === consult.consultation_date
            );

            return `
                        <div style="position: relative; margin-bottom: 1.5rem;">
                            <div style="position: absolute; left: -1.5rem; top: 0.5rem; width: 12px; height: 12px; background: #1f6b4a; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 0 2px #1f6b4a;"></div>
                            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                    <div>
                                        <div style="font-weight: 700; color: #0F172A; font-size: 1rem; margin-bottom: 0.25rem;">
                                            ${consult.consultation_date || 'Date not recorded'}
                                        </div>
                                        <div style="font-size: 0.875rem; color: #64748B;">
                                            Dr. ${consult.doctor_name || 'Unknown'}
                                        </div>
                                    </div>
                                    <span style="background: ${(consult.status == 0 || consult.status === 'completed') ? '#DCFCE7' : '#FEF3C7'}; color: ${(consult.status == 0 || consult.status === 'completed') ? '#15803D' : '#92400E'}; padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
                                        ${(consult.status == 0 || consult.status === 'completed') ? 'Completed' : 'Draft'}
                                    </span>
                                </div>
                                
                                ${consult.subjective ? `
                                    <div style="margin-bottom: 0.75rem;">
                                        <div style="font-weight: 600; color: #475569; font-size: 0.875rem; margin-bottom: 0.5rem;">
                                            <i class="fas fa-comment-medical"></i> Chief Complaint:
                                        </div>
                                        <div style="color: #64748B; font-size: 0.875rem; line-height: 1.6; background: #F8FAFC; padding: 0.75rem; border-radius: 8px;">
                                            ${consult.subjective}
                                        </div>
                                    </div>
                                ` : ''}

                                ${consult.physical_examination ? `
                                    <div style="margin-bottom: 0.75rem;">
                                        <div style="font-weight: 600; color: #475569; font-size: 0.875rem; margin-bottom: 0.5rem;">
                                            <i class="fas fa-user-md"></i> Physical Examination:
                                        </div>
                                        <div style="color: #64748B; font-size: 0.875rem; line-height: 1.6; background: #F8FAFC; padding: 0.75rem; border-radius: 8px;">
                                            ${consult.physical_examination}
                                        </div>
                                    </div>
                                ` : ''}

                                ${consult.final_diagnosis ? `
                                    <div style="margin-bottom: 0.75rem;">
                                        <div style="font-weight: 600; color: #475569; font-size: 0.875rem; margin-bottom: 0.5rem;">
                                            <i class="fas fa-clipboard-check"></i> Final Diagnosis:
                                        </div>
                                        <div style="color: #64748B; font-size: 0.875rem; line-height: 1.6; background: #FFF7ED; padding: 0.75rem; border-radius: 8px; border-left: 3px solid #F97316;">
                                            ${consult.final_diagnosis}
                                        </div>
                                    </div>
                                ` : ''}

                                ${consult.clinical_notes ? `
                                    <div style="margin-bottom: 0.75rem;">
                                        <div style="font-weight: 600; color: #475569; font-size: 0.875rem; margin-bottom: 0.5rem;">
                                            <i class="fas fa-notes-medical"></i> Clinical Notes:
                                        </div>
                                        <div style="color: #64748B; font-size: 0.875rem; line-height: 1.6; background: #F8FAFC; padding: 0.75rem; border-radius: 8px;">
                                            ${consult.clinical_notes}
                                        </div>
                                    </div>
                                ` : ''}
                                
                                ${consult.plan ? `
                                    <div style="margin-bottom: 0.75rem;">
                                        <div style="font-weight: 600; color: #475569; font-size: 0.875rem; margin-bottom: 0.5rem;">
                                            <i class="fas fa-tasks"></i> Treatment Plan:
                                        </div>
                                        <div style="color: #64748B; font-size: 0.875rem; line-height: 1.6; background: #F8FAFC; padding: 0.75rem; border-radius: 8px;">
                                            ${consult.plan.substring(0, 200)}${consult.plan.length > 200 ? '...' : ''}
                                        </div>
                                    </div>
                                ` : ''
                }
                                
                                ${relatedPrescription && relatedPrescription.medications ? `
                                    <div style="margin-top: 0.75rem; background: linear-gradient(135deg, #F0FDF4 0%, #DCFCE7 100%); padding: 1rem; border-radius: 10px; border: 1px solid #86EFAC;">
                                        <div style="font-weight: 600; color: #15803D; font-size: 0.875rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                                            <i class="fas fa-prescription"></i> Prescribed Medications:
                                        </div>
                                        <div style="color: #166534; font-size: 0.875rem; line-height: 1.8;">
                                            ${formatMedicationsList(relatedPrescription.medications)}
                                        </div>
                                    </div>
                                ` : (consult.medications ? `
                                    <div style="margin-top: 0.75rem; background: linear-gradient(135deg, #F0FDF4 0%, #DCFCE7 100%); padding: 1rem; border-radius: 10px; border: 1px solid #86EFAC;">
                                        <div style="font-weight: 600; color: #15803D; font-size: 0.875rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                                            <i class="fas fa-prescription"></i> Prescribed Medications:
                                        </div>
                                        <div style="color: #166534; font-size: 0.875rem; line-height: 1.8;">
                                            ${formatMedicationsList(consult.medications)}
                                        </div>
                                    </div>
                                ` : '')}
                            </div>
                        </div>
                    `}).join('')
            }
                </div >
            </div >
    `;
    } else {
        html += `
    < div style = "text-align: center; padding: 3rem; background: #F8FAFC; border-radius: 12px; margin-bottom: 2rem;" >
                <i class="fas fa-clipboard-list" style="font-size: 3rem; color: #CBD5E1; margin-bottom: 1rem;"></i>
                <h3 style="color: #64748B; font-size: 1.125rem; margin: 0;">No Consultation Records Found</h3>
                <p style="color: #94A3B8; margin: 0.5rem 0 0 0;">This patient has no previous consultations.</p>
            </div >
    `;
    }

    // Prescriptions
    if (prescriptions.length > 0) {
        html += `
    < div style = "margin-bottom: 2rem;" >
                <h3 style="font-size: 1.25rem; font-weight: 700; color: #0F172A; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-prescription" style="color: #10B981;"></i>
                    Prescription History
                </h3>
                <div style="display: grid; gap: 1rem;">
                    ${prescriptions.slice(0, 5).map(rx => `
                        <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                <div style="font-weight: 700; color: #0F172A;">
                                    ${rx.prescription_date || 'Date not recorded'}
                                </div>
                                <div style="font-size: 0.875rem; color: #64748B;">
                                    Rx #${rx.prescription_id || 'N/A'}
                                </div>
                            </div>
                            ${rx.medications ? `
                                <div style="background: #F0FDF4; padding: 0.75rem; border-radius: 8px; border: 1px solid #86EFAC;">
                                    <div style="font-size: 0.875rem; color: #15803D; font-weight: 600; margin-bottom: 0.5rem;">
                                        Medications:
                                    </div>
                                    <div style="font-size: 0.875rem; color: #166534; line-height: 1.6;">
                                        ${rx.medications}
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    `).join('')}
                </div>
            </div >
    `;
    }



    return html;
}

// ============================================================================
// ACCESS DENIED MESSAGE
// ============================================================================

/**
 * Show professional access denied message for patients not allocated to doctor
 */
function showAccessDeniedMessage(message) {
    // Create modal overlay
    const modal = document.createElement('div');
    modal.className = 'access-denied-modal';
    modal.style.cssText = `
position: fixed;
top: 0;
left: 0;
right: 0;
bottom: 0;
background: rgba(0, 0, 0, 0.6);
display: flex;
align - items: center;
justify - content: center;
z - index: 10000;
animation: fadeIn 0.3s ease;
`;

    // Create modal content
    const content = document.createElement('div');
    content.style.cssText = `
background: white;
border - radius: 16px;
padding: 2rem;
max - width: 500px;
width: 90 %;
box - shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
animation: slideUp 0.3s ease;
`;

    content.innerHTML = `
    < div style = "text-align: center;" >
            <div style="
                width: 80px;
                height: 80px;
                background: linear-gradient(135deg, #FEE2E2 0%, #FCA5A5 100%);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 1.5rem;
            ">
                <i class="fas fa-user-lock" style="font-size: 2.5rem; color: #DC2626;"></i>
            </div>
            
            <h2 style="
                font-size: 1.5rem;
                font-weight: 700;
                color: #1F2937;
                margin-bottom: 1rem;
            ">Patient Not Allocated</h2>
            
            <p style="
                font-size: 1rem;
                color: #6B7280;
                line-height: 1.6;
                margin-bottom: 2rem;
            ">${message}</p>
            
            <div style="
                background: #FEF3C7;
                border: 1px solid #FCD34D;
                border-radius: 8px;
                padding: 1rem;
                margin-bottom: 2rem;
                text-align: left;
            ">
                <div style="display: flex; align-items: start; gap: 0.75rem;">
                    <i class="fas fa-info-circle" style="color: #D97706; margin-top: 2px;"></i>
                    <div style="flex: 1;">
                        <strong style="color: #92400E; display: block; margin-bottom: 0.5rem;">What to do?</strong>
                        <p style="color: #78350F; font-size: 0.875rem; margin: 0; line-height: 1.5;">
                            Please contact the reception desk to schedule an appointment with this patient, 
                            or verify that the patient has an active appointment with you.
                        </p>
                    </div>
                </div>
            </div>
            
            <button onclick="this.closest('.access-denied-modal').remove()" style="
                background: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%);
                color: white;
                border: none;
                padding: 0.75rem 2rem;
                border-radius: 8px;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s ease;
                box-shadow: 0 4px 12px rgba(31, 107, 74, 0.3);
            " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(31, 107, 74, 0.4)';" 
               onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(31, 107, 74, 0.3)';">
                <i class="fas fa-check"></i> Understood
            </button>
        </div >
    `;

    modal.appendChild(content);
    document.body.appendChild(modal);

    // Add animations
    const style = document.createElement('style');
    style.textContent = `
@keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
}
@keyframes slideUp {
            from {
        opacity: 0;
        transform: translateY(20px);
    }
            to {
        opacity: 1;
        transform: translateY(0);
    }
}
`;
    document.head.appendChild(style);

    // Close on backdrop click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

// ============================================================================
// ADVANCED SEARCH FUNCTIONALITY
// ============================================================================

async function performAdvancedSearch() {
    try {
        const filters = {
            patient_id: document.getElementById('adv-patient-id').value.trim(),
            phone: document.getElementById('adv-phone').value.trim(),
            search: document.getElementById('adv-name').value.trim(),
            city: document.getElementById('adv-city').value.trim(),
            gender: document.getElementById('adv-gender').value
        };

        // Construct Query Params
        const params = new URLSearchParams();

        Object.keys(filters).forEach(key => {
            if (filters[key]) params.append(key, filters[key]);
        });

        // If no filters provided
        if ([...params].length === 0) {
            showToast('Please enter at least one search criteria.', 'info');
            return;
        }

        Modal.hide('advanced-search-modal');
        showLoading('Searching patients...');

        const response = await API.get(`patients?${params.toString()}`);
        hideLoading();

        if (response.success) {
            let patients = [];
            // Handle different API response structures
            if (response.data && Array.isArray(response.data.data)) {
                patients = response.data.data;
            } else if (Array.isArray(response.data)) {
                patients = response.data;
            }

            const select = document.getElementById('patient-select');

            if (patients && patients.length > 0) {
                // Clear existing options
                select.innerHTML = '<option value="">-- Select Found Patient --</option>';

                patients.forEach(patient => {
                    const option = document.createElement('option');
                    option.value = patient.patient_id;
                    option.textContent = `${patient.patient_id} - ${patient.first_name} ${patient.last_name}`;
                    select.appendChild(option);
                });

                showToast(`Found ${patients.length} patients. Please select from dropdown.`, 'success');

                // If only one result, auto-select
                if (patients.length === 1) {
                    select.value = patients[0].patient_id;
                    // Trigger change event manually or call loadPatientInfo
                    loadPatientInfo();
                } else {
                    // Flash the dropdown
                    select.focus();
                    select.style.borderColor = '#1f6b4a';
                    setTimeout(() => select.style.borderColor = '', 2000);
                }
            } else {
                showToast('No patients found matching your criteria.', 'info');
            }
        } else {
            showToast('Search failed or no access.', 'error');
        }

    } catch (error) {
        hideLoading();
        console.error('Search error:', error);
        showToast('Error occurred during search', 'error');
    }
}
