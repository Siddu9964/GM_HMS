/**
 * Patient Prescriptions & Consultation History Timeline - Reception View
 * Implements full clinical timeline, lab results, handwritten prescription viewer & printing
 */

let currentPatientData = null;
let allPrescriptions = [];
let zoomLevel = 1;
let globalViewMode = 'grid';
let globalFilterType = 'all';

document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('patient-id-input');
    if (input) {
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') searchPrescription();
        });
    }
    
    // Check if patient_id passed via URL (e.g. ?patient_id=PID-1001)
    const urlParams = new URLSearchParams(window.location.search);
    const paramPatientId = urlParams.get('patient_id') || urlParams.get('id');
    if (paramPatientId) {
        input.value = paramPatientId;
        searchPrescription();
    } else {
        loadAllPrescriptions();
    }
});

/**
 * Helper to parse soap_plan / medicines / plan_text into structured data
 */
function parseSoapPlanJS(presc) {
    let medicines = [];
    let planText = presc.plan_text || '';

    if (!presc) return { medicines: [], planText: '' };

    // 1. Check presc.medicines first
    if (presc.medicines) {
        if (Array.isArray(presc.medicines)) {
            medicines = presc.medicines;
        } else if (typeof presc.medicines === 'string') {
            try {
                const parsed = JSON.parse(presc.medicines);
                if (Array.isArray(parsed)) medicines = parsed;
                else if (parsed && typeof parsed === 'object') {
                    if (Array.isArray(parsed.medications)) medicines = parsed.medications;
                    else if (parsed.name || parsed.medicine_name) medicines = [parsed];
                    else if (parsed.plan) planText = parsed.plan;
                }
            } catch (e) {
                if (!planText && presc.medicines.trim()) planText = presc.medicines.trim();
            }
        }
    }

    // 2. Check presc.soap_plan if medicines is still empty
    if (medicines.length === 0 && presc.soap_plan) {
        if (Array.isArray(presc.soap_plan)) {
            medicines = presc.soap_plan;
        } else if (typeof presc.soap_plan === 'object' && presc.soap_plan !== null) {
            if (Array.isArray(presc.soap_plan.medications)) {
                medicines = presc.soap_plan.medications;
                if (!planText && presc.soap_plan.plan) planText = presc.soap_plan.plan;
            } else if (Array.isArray(presc.soap_plan)) {
                medicines = presc.soap_plan;
            } else if (presc.soap_plan.plan) {
                planText = presc.soap_plan.plan;
            } else if (presc.soap_plan.name || presc.soap_plan.medicine_name) {
                medicines = [presc.soap_plan];
            }
        } else if (typeof presc.soap_plan === 'string') {
            const trimmed = presc.soap_plan.trim();
            if (trimmed) {
                try {
                    const parsed = JSON.parse(trimmed);
                    if (Array.isArray(parsed)) {
                        medicines = parsed;
                    } else if (parsed && typeof parsed === 'object') {
                        if (Array.isArray(parsed.medications)) {
                            medicines = parsed.medications;
                            if (!planText && parsed.plan) planText = parsed.plan;
                        } else if (parsed.name || parsed.medicine_name) {
                            medicines = [parsed];
                        } else if (parsed.plan) {
                            planText = parsed.plan;
                        } else if (parsed.instructions) {
                            planText = parsed.instructions;
                        }
                    }
                } catch (e) {
                    if (!planText) planText = trimmed;
                }
            }
        }
    }

    return { medicines, planText };
}

/**
 * STEP 1 & 12: Search consultations by patient_id or phone or UHID
 */
async function searchPrescription() {
    let searchValue = document.getElementById('patient-id-input').value.trim();
    if (!searchValue) {
        showGlobalPrescriptionsView();
        loadAllPrescriptions();
        return;
    }

    try {
        if (typeof showLoading === 'function') showLoading('Loading consultation timeline...');
        
        const response = await API.get(`prescriptions/receptionist/view/${encodeURIComponent(searchValue)}`);
        if (typeof hideLoading === 'function') hideLoading();

        if (response.success && response.data) {
            currentPatientData = response.data;
            const consultations = response.data.consultations || response.data.prescriptions || [];
            
            if (consultations.length > 0) {
                renderTimelineResults(response.data);
            } else {
                showEmptyState('No prescriptions found');
            }
        } else {
            showEmptyState(response.error || 'No prescriptions found');
        }
    } catch (error) {
        if (typeof hideLoading === 'function') hideLoading();
        console.error('Search error:', error);
        showEmptyState('No prescriptions found');
    }
}

function showGlobalPrescriptionsView() {
    const resultsSection = document.getElementById('results-section');
    const allSection = document.getElementById('all-prescriptions-section');
    const emptyState = document.getElementById('empty-state');

    if (resultsSection) resultsSection.style.display = 'none';
    if (emptyState) emptyState.style.display = 'none';
    if (allSection) allSection.style.display = 'block';
}

/**
 * Load Recent Global Prescriptions
 */
async function loadAllPrescriptions() {
    const listContainer = document.getElementById('all-prescriptions-list');
    try {
        const response = await API.get('prescriptions?limit=50');
        if (response.success && Array.isArray(response.data)) {
            allPrescriptions = response.data;
            renderGlobalList(allPrescriptions);
        } else {
            allPrescriptions = [];
            if (listContainer) {
                listContainer.innerHTML = '<div class="no-records"><i class="fas fa-file-medical" style="color:#557365;"></i><p style="margin-top:0.5rem; font-weight:700; color:#144D34;">No recent global prescriptions found in system.</p></div>';
            }
        }
    } catch (error) {
        console.error('Failed to load global prescriptions:', error);
        allPrescriptions = [];
        if (listContainer) {
            listContainer.innerHTML = '<div class="no-records"><i class="fas fa-file-medical" style="color:#557365;"></i><p style="margin-top:0.5rem; font-weight:700; color:#144D34;">No recent global prescriptions found in system.</p></div>';
        }
    }
}

function setGlobalFilter(type, btnEl) {
    globalFilterType = type;
    document.querySelectorAll('.global-pill').forEach(el => el.classList.remove('active'));
    if (btnEl) btnEl.classList.add('active');
    renderGlobalList(allPrescriptions);
}

function setGlobalViewMode(mode) {
    globalViewMode = mode;
    const gridBtn = document.getElementById('view-grid-btn');
    const tableBtn = document.getElementById('view-table-btn');
    if (gridBtn && tableBtn) {
        gridBtn.classList.toggle('active', mode === 'grid');
        tableBtn.classList.toggle('active', mode === 'table');
    }
    renderGlobalList(allPrescriptions);
}

function filterGlobalList() {
    renderGlobalList(allPrescriptions);
}

/**
 * Render Advanced UI/UX Recent Global Prescriptions
 */
function renderGlobalList(prescriptions) {
    const listContainer = document.getElementById('all-prescriptions-list');
    if (!listContainer) return;

    if (!prescriptions || prescriptions.length === 0) {
        listContainer.innerHTML = '<div class="no-records">No prescriptions found in system.</div>';
        return;
    }

    // Update KPI Counters
    const todayStr = new Date().toISOString().split('T')[0];
    const totalCount = prescriptions.length;
    const todayCount = prescriptions.filter(p => p.prescription_date === todayStr).length;
    const imageCount = prescriptions.filter(p => p.has_prescription_image).length;
    const medsCount = prescriptions.filter(p => {
        const { medicines, planText } = parseSoapPlanJS(p);
        return medicines.length > 0 || Boolean(planText);
    }).length;

    const elTotal = document.getElementById('kpi-total-count');
    const elToday = document.getElementById('kpi-today-count');
    const elImg = document.getElementById('kpi-image-count');
    const elMeds = document.getElementById('kpi-meds-count');

    if (elTotal) elTotal.textContent = totalCount;
    if (elToday) elToday.textContent = todayCount;
    if (elImg) elImg.textContent = imageCount;
    if (elMeds) elMeds.textContent = medsCount;

    // Apply Filters
    const filterText = (document.getElementById('global-search-filter')?.value || '').toLowerCase().trim();
    let filtered = prescriptions.filter(p => {
        const { medicines, planText } = parseSoapPlanJS(p);
        const hasPlan = medicines.length > 0 || Boolean(planText);

        if (globalFilterType === 'image' && !p.has_prescription_image) return false;
        if (globalFilterType === 'meds' && !hasPlan) return false;

        if (filterText) {
            const searchHaystack = [
                p.first_name, p.last_name, p.patient_id, p.patient_phone,
                p.doctor_name, p.diagnosis, p.prescription_id
            ].filter(Boolean).join(' ').toLowerCase();
            return searchHaystack.includes(filterText);
        }
        return true;
    });

    if (filtered.length === 0) {
        listContainer.innerHTML = '<div class="no-records"><i class="fas fa-filter text-slate-400"></i><p style="margin-top:0.5rem; color:#144D34; font-weight:700;">No matching global prescriptions for selected filter.</p></div>';
        return;
    }

    if (globalViewMode === 'grid') {
        renderGlobalGridView(filtered, listContainer);
    } else {
        renderGlobalTableView(filtered, listContainer);
    }
}

/**
 * Grid View Renderer for Global Prescriptions (High Contrast & Visible Text)
 */
function renderGlobalGridView(prescriptions, listContainer) {
    const cardsHtml = prescriptions.map(p => {
        const dateStr = p.prescription_date ? DateUtils.formatDateReadable(p.prescription_date) : '-';
        const initials = `${(p.first_name || 'P')[0]}${(p.last_name || '')[0] || ''}`.toUpperCase();
        const medsCount = (p.medicines && Array.isArray(p.medicines)) ? p.medicines.length : 0;

        return `
            <div class="global-card">
                <!-- Top Row: Date, Time & ID -->
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem; border-bottom:1px solid #E8E6DC; padding-bottom:0.6rem;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="background:#144D34 !important; color:#FFFFFF !important; padding:4px 10px; border-radius:6px; font-size:0.78rem; font-weight:800; display:inline-flex; align-items:center; gap:4px;">
                            <i class="fas fa-calendar-check" style="color:#FFFFFF !important;"></i>
                            <span style="color:#FFFFFF !important;">${dateStr}</span>
                        </span>
                        ${p.consultation_time ? `
                            <span style="font-size:0.78rem; color:#144D34 !important; font-weight:800; display:inline-flex; align-items:center; gap:4px;">
                                <i class="fas fa-clock" style="color:#144D34 !important;"></i>
                                <span style="color:#144D34 !important;">${p.consultation_time}</span>
                            </span>
                        ` : ''}
                    </div>
                    <span style="font-size:0.75rem; font-weight:800; color:#FFFFFF !important; background:#144D34 !important; padding:4px 10px; border-radius:6px; display:inline-flex; align-items:center; gap:4px;">
                        <i class="fas fa-hashtag" style="color:#FFFFFF !important;"></i>
                        <span style="color:#FFFFFF !important;">${p.prescription_id}</span>
                    </span>
                </div>

                <!-- Patient Details -->
                <div style="display:flex; gap:0.85rem; align-items:center; margin-bottom:0.75rem;">
                    <div style="width:48px; height:48px; border-radius:12px; background:#144D34 !important; color:#FFFFFF !important; display:flex; align-items:center; justify-content:center; font-size:1.15rem; font-weight:900; shrink:0; border:2px solid #C6E6D2;">
                        <span style="color:#FFFFFF !important;">${initials}</span>
                    </div>
                    <div style="flex:1;">
                        <h4 style="margin:0; font-size:1.1rem; font-weight:900; color:#144D34 !important;">
                            ${p.first_name || ''} ${p.last_name || ''}
                        </h4>
                        <div style="font-size:0.82rem; color:#0E3826 !important; font-weight:700; margin-top:2px;">
                            <span>ID: <strong style="color:#144D34 !important;">${p.patient_id}</strong></span>
                            ${p.patient_phone ? `<span style="margin:0 6px; color:#557365;">•</span><i class="fas fa-phone-alt" style="color:#144D34 !important; margin-right:3px;"></i> <span style="color:#144D34 !important;">${p.patient_phone}</span>` : ''}
                        </div>
                    </div>
                </div>

                <!-- Doctor & Diagnosis Box -->
                <div style="background:#FFFFFF; border:1.5px solid #DEDACF; border-radius:8px; padding:0.65rem 0.9rem; margin-bottom:0.75rem;">
                    <div style="font-size:0.82rem; color:#144D34 !important; font-weight:800; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:4px;">
                        <span><i class="fas fa-user-md" style="color:#144D34 !important; margin-right:4px;"></i> <span style="color:#144D34 !important;">Dr. ${p.doctor_name || 'Medical Officer'}</span></span>
                        ${(p.specialization || p.department) ? `
                            <span style="font-size:0.72rem; color:#144D34 !important; font-weight:800; background:#E8F4EC; border:1px solid #C6E6D2; padding:2px 8px; border-radius:4px; text-transform:uppercase;">
                                ${p.specialization || p.department}
                            </span>
                        ` : ''}
                    </div>
                    <div style="font-size:0.88rem; font-weight:800; color:#144D34 !important; margin-top:5px;">
                        <i class="fas fa-diagnoses" style="color:#144D34 !important; margin-right:4px;"></i>
                        <span style="color:#144D34 !important;">${p.diagnosis || p.soap_subjective || 'General Consultation'}</span>
                    </div>
                </div>

                <!-- Badges & Action Bar -->
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem; pt-2; border-top:1px solid #E8E6DC;">
                    <div style="display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
                        ${(() => {
                            const { medicines, planText } = parseSoapPlanJS(p);
                            if (medicines.length > 0) {
                                return `<span class="vital-pill" style="background:#E8F4EC !important; color:#144D34 !important; border:1px solid #C6E6D2 !important; font-weight:800;"><i class="fas fa-pills" style="color:#144D34 !important;"></i> ${medicines.length} Medicines</span>`;
                            } else if (planText) {
                                return `<span class="vital-pill" style="background:#E8F4EC !important; color:#144D34 !important; border:1px solid #C6E6D2 !important; font-weight:800;"><i class="fas fa-file-medical-alt" style="color:#144D34 !important;"></i> Plan Prescribed</span>`;
                            }
                            return '';
                        })()}
                        ${p.has_prescription_image ? `
                            <span class="vital-pill" style="background:#E8F4EC !important; color:#144D34 !important; border:1px solid #C6E6D2 !important; font-weight:800;"><i class="fas fa-camera" style="color:#144D34 !important;"></i> Handwritten</span>
                        ` : `
                            <span class="vital-pill" style="background:#F4F1EA !important; color:#144D34 !important; border:1px solid #D5D0C3 !important; font-weight:800;"><i class="fas fa-file-alt" style="color:#144D34 !important;"></i> Digital</span>
                        `}
                    </div>

                    <div style="display:flex; gap:6px; align-items:center;">
                        ${p.has_prescription_image ? `
                            <button onclick="openImageModal('${p.prescription_image_url}')" class="btn btn-xs" title="View Handwritten Image" style="background:#E8F4EC !important; color:#144D34 !important; border:1.5px solid #C6E6D2 !important; font-size:0.78rem; padding:5px 10px; font-weight:800; border-radius:6px; cursor:pointer;">
                                <i class="fas fa-image" style="color:#144D34 !important;"></i> Image
                            </button>
                        ` : ''}
                        <button onclick="viewProfessionalPrescription('${p.prescription_id}')" class="btn btn-xs" title="Print A4 Prescription" style="background:#FFFFFF !important; color:#144D34 !important; border:1.5px solid #144D34 !important; font-size:0.78rem; padding:5px 12px; font-weight:800; border-radius:6px; cursor:pointer;">
                            <i class="fas fa-print" style="color:#144D34 !important; margin-right:3px;"></i> Print
                        </button>
                        <button onclick="selectPatientFromGlobal('${p.patient_id}')" class="btn btn-xs" style="background:#144D34 !important; color:#FFFFFF !important; border:none !important; font-size:0.78rem; padding:6px 14px; font-weight:800; border-radius:6px; cursor:pointer;">
                            <i class="fas fa-history" style="color:#FFFFFF !important; margin-right:4px;"></i> <span style="color:#FFFFFF !important;">Full Timeline</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    listContainer.innerHTML = `<div class="global-grid-container">${cardsHtml}</div>`;
}

/**
 * Table View Renderer for Global Prescriptions
 */
function renderGlobalTableView(prescriptions, listContainer) {
    const rowsHtml = prescriptions.map(p => {
        const dateStr = p.prescription_date ? DateUtils.formatDateReadable(p.prescription_date) : '-';
        const medsCount = (p.medicines && Array.isArray(p.medicines)) ? p.medicines.length : 0;

        return `
            <tr style="border-bottom:1px solid #E2E0D6; font-size:0.85rem; color:#144D34 !important; background:#FBF9F3;">
                <td style="padding:10px 12px; font-weight:800;">
                    <div style="color:#144D34 !important;">${dateStr}</div>
                    <div style="font-size:0.75rem; color:#557365 !important; font-weight:600;">${p.consultation_time || ''}</div>
                </td>
                <td style="padding:10px 12px;">
                    <div style="font-weight:900; color:#144D34 !important;">${p.first_name || ''} ${p.last_name || ''}</div>
                    <div style="font-size:0.78rem; color:#0E3826 !important; font-weight:700;">ID: ${p.patient_id} | Phone: ${p.patient_phone || 'N/A'}</div>
                </td>
                <td style="padding:10px 12px;">
                    <div style="font-weight:800; color:#144D34 !important;">Dr. ${p.doctor_name || 'Officer'}</div>
                    <div style="font-size:0.75rem; color:#557365 !important; font-weight:600;">${p.specialization || p.department || ''}</div>
                </td>
                <td style="padding:10px 12px; font-weight:800; color:#144D34 !important;">
                    ${p.diagnosis || p.soap_subjective || 'General Consultation'}
                </td>
                <td style="padding:10px 12px;">
                    ${medsCount > 0 ? `<span class="vital-pill mr-1" style="background:#E8F4EC !important; color:#144D34 !important; border:1px solid #C6E6D2 !important;"><i class="fas fa-pills" style="color:#144D34 !important;"></i> ${medsCount}</span>` : ''}
                    ${p.has_prescription_image ? `<span class="vital-pill" style="background:#E8F4EC !important; color:#144D34 !important; border:1px solid #C6E6D2 !important;"><i class="fas fa-camera" style="color:#144D34 !important;"></i> Image</span>` : ''}
                </td>
                <td style="padding:10px 12px; text-align:right;">
                    <div style="display:flex; gap:4px; justify-content:flex-end;">
                        ${p.has_prescription_image ? `
                            <button onclick="openImageModal('${p.prescription_image_url}')" class="btn btn-xs" style="background:#E8F4EC !important; color:#144D34 !important; border:1px solid #C6E6D2 !important; font-size:0.75rem; padding:4px 8px; font-weight:800;"><i class="fas fa-image" style="color:#144D34 !important;"></i></button>
                        ` : ''}
                        <button onclick="viewProfessionalPrescription('${p.prescription_id}')" class="btn btn-xs" style="background:#FFFFFF !important; color:#144D34 !important; border:1.5px solid #144D34 !important; font-size:0.75rem; padding:4px 8px; font-weight:800;"><i class="fas fa-print" style="color:#144D34 !important;"></i></button>
                        <button onclick="selectPatientFromGlobal('${p.patient_id}')" class="btn btn-xs" style="background:#144D34 !important; color:#FFFFFF !important; border:none !important; font-size:0.75rem; padding:5px 10px; font-weight:800;"><i class="fas fa-history" style="color:#FFFFFF !important;"></i> <span style="color:#FFFFFF !important;">Timeline</span></button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    listContainer.innerHTML = `
        <table style="width:100%; border-collapse:collapse; background:#fff; border-radius:12px; overflow:hidden; border:1.5px solid #E2E0D6;">
            <thead>
                <tr style="background:#144D34 !important; color:#FFFFFF !important; font-size:0.78rem; text-transform:uppercase; text-align:left;">
                    <th style="padding:10px 12px; color:#FFFFFF !important;">Date & Time</th>
                    <th style="padding:10px 12px; color:#FFFFFF !important;">Patient Details</th>
                    <th style="padding:10px 12px; color:#FFFFFF !important;">Doctor & Dept</th>
                    <th style="padding:10px 12px; color:#FFFFFF !important;">Diagnosis / Note</th>
                    <th style="padding:10px 12px; color:#FFFFFF !important;">Features</th>
                    <th style="padding:10px 12px; text-align:right; color:#FFFFFF !important;">Actions</th>
                </tr>
            </thead>
            <tbody>
                ${rowsHtml}
            </tbody>
        </table>
    `;
}

function selectPatientFromGlobal(patientId) {
    const input = document.getElementById('patient-id-input');
    if (input) input.value = patientId;
    searchPrescription();
}

/**
 * STEP 9, 13: Render Full Consultation Timeline for Patient
 */
function renderTimelineResults(data) {
    const resultsSection = document.getElementById('results-section');
    const allSection = document.getElementById('all-prescriptions-section');
    const emptyState = document.getElementById('empty-state');
    const historyList = document.getElementById('prescription-history-list');

    const patient = data.patient || {};
    const consultations = data.consultations || data.prescriptions || [];
    const labResults = data.lab_results || [];

    // STEP 3: Render Patient Information Banner Card
    renderPatientBannerCard(patient, consultations[0]);

    // STEP 9 & 10: Render Consultation Cards (Newest First)
    if (consultations.length === 0) {
        showEmptyState('No prescriptions found');
        return;
    }

    historyList.innerHTML = consultations.map((c, idx) => renderConsultationCard(c, idx, labResults)).join('');

    resultsSection.style.display = 'block';
    allSection.style.display = 'none';
    emptyState.style.display = 'none';
}

/**
 * STEP 3: Patient Information Card
 */
function renderPatientBannerCard(patient, latestConsultation) {
    const p = patient.patient_id ? patient : (latestConsultation || {});
    const fullName = `${p.first_name || ''} ${p.last_name || ''}`.trim() || 'Patient Record';
    const initials = `${(p.first_name || 'P')[0]}${(p.last_name || '')[0] || ''}`.toUpperCase();

    const bannerHtml = `
        <div style="background: #FBF9F3; border: 1.5px solid #E2E0D6; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 14px rgba(20,77,52,0.06); margin-bottom: 1.5rem;">
            <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 1.25rem;">
                    <div style="width: 58px; height: 58px; border-radius: 14px; background: #144D34 !important; color: #FFFFFF !important; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; font-weight: 900; border: 2px solid #C6E6D2;">
                        <span style="color:#FFFFFF !important;">${initials}</span>
                    </div>
                    <div>
                        <h2 style="margin: 0; font-size: 1.35rem; font-weight: 900; color: #144D34 !important;">${fullName}</h2>
                        <div style="font-size: 0.85rem; color: #0E3826 !important; margin-top: 3px; font-weight: 700;">
                            <span>ID: <strong style="color:#144D34 !important;">${p.patient_id || '-'}</strong></span>
                            <span style="margin: 0 6px;">•</span>
                            <span>UHID: <strong style="color:#144D34 !important;">${p.uhid || 'N/A'}</strong></span>
                            <span style="margin: 0 6px;">•</span>
                            <span>Phone: <strong style="color:#144D34 !important;">${p.phone || p.patient_phone || 'N/A'}</strong></span>
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
                    <div class="pat-badge" style="background:#E8F4EC; border:1px solid #C6E6D2; color:#144D34 !important; padding:6px 12px; border-radius:8px; font-size:0.8rem; font-weight:800;">
                        <i class="fas fa-calendar-alt" style="color:#144D34 !important;"></i> Age: ${p.age || 'N/A'} Y
                    </div>
                    <div class="pat-badge" style="background:#E8F4EC; border:1px solid #C6E6D2; color:#144D34 !important; padding:6px 12px; border-radius:8px; font-size:0.8rem; font-weight:800;">
                        <i class="fas fa-venus-mars" style="color:#144D34 !important;"></i> Gender: ${p.gender || 'N/A'}
                    </div>
                    <div class="pat-badge" style="background:#E8F4EC; border:1px solid #C6E6D2; color:#144D34 !important; padding:6px 12px; border-radius:8px; font-size:0.8rem; font-weight:800;">
                        <i class="fas fa-tint text-red-600"></i> Blood: ${p.blood_group || 'N/A'}
                    </div>
                </div>
            </div>

            ${p.address ? `
                <div style="margin-top: 0.85rem; padding-top: 0.75rem; border-top: 1px solid #E8E6DC; font-size: 0.82rem; color: #144D34 !important; display: flex; align-items: center; gap: 0.4rem; font-weight:700;">
                    <i class="fas fa-map-marker-alt text-emerald-700"></i> 
                    <strong>Address:</strong> ${p.address} ${p.city ? ', ' + p.city : ''} ${p.state ? ', ' + p.state : ''} ${p.pincode ? ' - ' + p.pincode : ''}
                </div>
            ` : ''}
        </div>
    `;

    const summaryContainer = document.getElementById('patient-summary-card');
    if (summaryContainer) {
        summaryContainer.innerHTML = bannerHtml;
        summaryContainer.style.display = 'block';
    }
}

/**
 * STEP 9: Consultation Card Renderer
 */
function renderConsultationCard(c, idx, labResults) {
    const dateStr = c.consultation_date ? DateUtils.formatDateReadable(c.consultation_date) : 'Date N/A';
    const doctorName = c.doctor_name ? `Dr. ${c.doctor_name}` : 'Medical Officer';
    const deptInfo = [c.department, c.qualification, c.specialization].filter(Boolean).join(' • ');

    const patientLabResults = labResults || [];

    // Prescribed Medicines HTML
    const { medicines, planText } = parseSoapPlanJS(c);
    let medicinesTableHtml = '';

    if (medicines.length > 0) {
        medicinesTableHtml = `
            <div style="margin-top:0.75rem;">
                <div style="font-size:0.78rem; font-weight:800; text-transform:uppercase; color:#144D34 !important; letter-spacing:0.04em; margin-bottom:4px;">
                    <i class="fas fa-pills" style="color:#144D34 !important;"></i> Prescribed Medications
                </div>
                <table class="med-table-pro" style="width:100%; border-collapse:collapse; background:#fff; border-radius:8px; overflow:hidden; border:1px solid #DEDACF;">
                    <thead>
                        <tr style="background:#144D34 !important; color:#FFFFFF !important; font-size:0.75rem; text-transform:uppercase;">
                            <th style="padding:6px 10px; text-align:left; color:#FFFFFF !important;">Medicine</th>
                            <th style="padding:6px 10px; text-align:left; color:#FFFFFF !important;">Dosage</th>
                            <th style="padding:6px 10px; text-align:left; color:#FFFFFF !important;">Frequency</th>
                            <th style="padding:6px 10px; text-align:left; color:#FFFFFF !important;">Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${medicines.map(m => `
                            <tr style="border-bottom:1px solid #E2DDD0; font-size:0.82rem; color:#144D34 !important;">
                                <td style="padding:6px 10px; font-weight:800; color:#144D34 !important;">${m.name || m.medicine_name || m.title || 'Medicine'}</td>
                                <td style="padding:6px 10px; font-weight:600; color:#144D34 !important;">${m.dosage || '-'}</td>
                                <td style="padding:6px 10px; font-weight:800; color:#0D9488 !important;">${m.frequency || m.freq || '-'}</td>
                                <td style="padding:6px 10px; color:#144D34 !important;">${m.duration || '-'}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                ${planText ? `
                    <div style="margin-top:6px; background:#fff; border:1px solid #DEDACF; border-radius:8px; padding:8px 12px; font-size:0.82rem; color:#144D34 !important; font-weight:700; white-space:pre-line;">
                        <strong style="color:#144D34 !important;">Treatment Instructions:</strong> ${planText}
                    </div>
                ` : ''}
            </div>
        `;
    } else if (planText) {
        medicinesTableHtml = `
            <div style="margin-top:0.75rem; background:#fff; border:1.5px solid #DEDACF; border-radius:10px; padding:10px 14px;">
                <div style="font-size:0.78rem; font-weight:800; text-transform:uppercase; color:#144D34 !important; margin-bottom:4px;">
                    <i class="fas fa-file-medical-alt" style="color:#144D34 !important;"></i> Prescribed Treatment Plan & Instructions
                </div>
                <div style="font-size:0.85rem; color:#144D34 !important; font-weight:700; white-space:pre-line; line-height:1.5;">
                    ${planText}
                </div>
            </div>
        `;
    }

    // STEP 5: Prescription Image HTML
    let imageHtml = '';
    if (c.has_prescription_image && c.prescription_image_url) {
        imageHtml = `
            <div style="margin-top:0.85rem; padding:0.75rem; background:#fff; border:1.5px solid #DEDACF; border-radius:10px;">
                <div style="font-size:0.78rem; font-weight:800; text-transform:uppercase; color:#144D34 !important; margin-bottom:6px; display:flex; justify-content:space-between; align-items:center;">
                    <span><i class="fas fa-file-signature"></i> Handwritten Doctor Prescription</span>
                    <a href="${c.prescription_image_url}" download class="btn btn-xs btn-outline" style="font-size:0.72rem; padding:3px 8px; font-weight:800; color:#144D34 !important; border-color:#144D34 !important;">
                        <i class="fas fa-download"></i> Download Image
                    </a>
                </div>
                <div style="text-align:center; position:relative; cursor:pointer;" onclick="openImageModal('${c.prescription_image_url}')">
                    <img src="${c.prescription_image_url}" alt="Handwritten Prescription" 
                         style="max-height:180px; width:auto; border-radius:6px; border:1px solid #CBD5E1; transition:transform 0.2s;"
                         onerror="this.onerror=null; this.parentElement.innerHTML='<div style=\'padding:12px; color:#64748b; font-weight:600; font-size:0.8rem;\'><i class=\'fas fa-exclamation-triangle text-amber-500\'></i> No handwritten prescription uploaded</div>';">
                    <div style="margin-top:4px; font-size:0.72rem; color:#0D9488 !important; font-weight:800;">
                        <i class="fas fa-search-plus"></i> Click image to open full screen preview & zoom
                    </div>
                </div>
            </div>
        `;
    } else {
        imageHtml = `
            <div style="margin-top:0.85rem; padding:0.6rem 0.85rem; background:#F4F1EA; border:1px dashed #D5D0C3; border-radius:8px; font-size:0.8rem; color:#144D34 !important; font-weight:700; display:flex; align-items:center; gap:8px;">
                <i class="fas fa-file-image" style="font-size:1.1rem; color:#88A093;"></i> No handwritten prescription uploaded
            </div>
        `;
    }

    // STEP 6, 7, 8: Lab Results Section HTML
    const labResultsHtml = renderLabResultsSection(patientLabResults);

    // Vitals Grid HTML
    const vitals = c.parsed_vitals || {};
    const vitalsHtml = (vitals.bp || vitals.pulse || vitals.temp || vitals.spo2 || vitals.weight) ? `
        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:6px; margin-bottom:8px;">
            ${vitals.bp ? `<span class="vital-pill" style="background:#E8F4EC !important; color:#144D34 !important; border:1px solid #C6E6D2 !important; font-weight:800;"><strong>BP:</strong> ${vitals.bp}</span>` : ''}
            ${vitals.pulse ? `<span class="vital-pill" style="background:#E8F4EC !important; color:#144D34 !important; border:1px solid #C6E6D2 !important; font-weight:800;"><strong>Pulse:</strong> ${vitals.pulse} bpm</span>` : ''}
            ${vitals.temp ? `<span class="vital-pill" style="background:#E8F4EC !important; color:#144D34 !important; border:1px solid #C6E6D2 !important; font-weight:800;"><strong>Temp:</strong> ${vitals.temp} °F</span>` : ''}
            ${vitals.spo2 ? `<span class="vital-pill" style="background:#E8F4EC !important; color:#144D34 !important; border:1px solid #C6E6D2 !important; font-weight:800;"><strong>SPO2:</strong> ${vitals.spo2}%</span>` : ''}
            ${vitals.weight ? `<span class="vital-pill" style="background:#E8F4EC !important; color:#144D34 !important; border:1px solid #C6E6D2 !important; font-weight:800;"><strong>Weight:</strong> ${vitals.weight} kg</span>` : ''}
        </div>
    ` : '';

    return `
        <div class="consultation-card" style="background:#FBF9F3; border:1.5px solid #E2E0D6; border-radius:14px; padding:1.25rem 1.4rem; margin-bottom:1.25rem; box-shadow:0 4px 14px rgba(20,77,52,0.05);">
            <!-- Card Header -->
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:0.5rem; padding-bottom:0.75rem; border-bottom:1px solid #E8E6DC; margin-bottom:0.85rem;">
                <div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="background:#144D34 !important; color:#FFFFFF !important; padding:4px 10px; border-radius:6px; font-size:0.78rem; font-weight:800; display:inline-flex; align-items:center; gap:4px;">
                            <i class="fas fa-calendar-check" style="color:#FFFFFF !important;"></i> <span style="color:#FFFFFF !important;">${dateStr}</span>
                        </span>
                        <span style="font-size:0.8rem; color:#144D34 !important; font-weight:800; display:inline-flex; align-items:center; gap:4px;">
                            <i class="fas fa-clock" style="color:#144D34 !important;"></i> <span style="color:#144D34 !important;">${c.consultation_time || ''}</span>
                        </span>
                    </div>
                    <h3 style="margin:6px 0 2px 0; font-size:1.15rem; font-weight:900; color:#144D34 !important;">
                        ${c.final_diagnosis || c.complaint || 'Consultation Record'}
                    </h3>
                    <div style="font-size:0.85rem; color:#0E3826 !important; font-weight:700;">
                        <i class="fas fa-user-md" style="color:#144D34 !important;"></i> ${doctorName} ${deptInfo ? `(${deptInfo})` : ''}
                    </div>
                </div>

                <div style="display:flex; gap:8px;">
                    <button onclick="viewProfessionalPrescription('${c.consultation_id}')" class="btn btn-sm" style="background:#144D34 !important; color:#FFFFFF !important; border:none !important; padding:0.45rem 1rem; border-radius:6px; font-size:0.8rem; font-weight:800; cursor:pointer;">
                        <i class="fas fa-print" style="color:#FFFFFF !important; margin-right:4px;"></i> <span style="color:#FFFFFF !important;">Print A4 Prescription</span>
                    </button>
                </div>
            </div>

            <!-- Vitals Grid -->
            ${vitalsHtml}

            <!-- SOAP Summary -->
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:10px; margin-bottom:0.75rem;">
                ${c.soap_subjective ? `
                    <div style="background:#fff; border:1px solid #DEDACF; border-radius:8px; padding:8px 12px;">
                        <div style="font-size:0.72rem; font-weight:800; text-transform:uppercase; color:#144D34 !important;"><i class="fas fa-user-injured" style="color:#144D34 !important;"></i> Subjective (Complaints)</div>
                        <div style="font-size:0.85rem; color:#144D34 !important; font-weight:700; margin-top:2px;">${c.soap_subjective}</div>
                    </div>
                ` : ''}
                ${(c.soap_objective || c.physical_examination) ? `
                    <div style="background:#fff; border:1px solid #DEDACF; border-radius:8px; padding:8px 12px;">
                        <div style="font-size:0.72rem; font-weight:800; text-transform:uppercase; color:#144D34 !important;"><i class="fas fa-stethoscope" style="color:#144D34 !important;"></i> Examination & Objective</div>
                        <div style="font-size:0.85rem; color:#144D34 !important; font-weight:700; margin-top:2px;">${c.soap_objective || c.physical_examination}</div>
                    </div>
                ` : ''}
                ${c.clinical_notes ? `
                    <div style="background:#fff; border:1px solid #DEDACF; border-radius:8px; padding:8px 12px; grid-column: 1 / -1;">
                        <div style="font-size:0.72rem; font-weight:800; text-transform:uppercase; color:#144D34 !important;"><i class="fas fa-notes-medical" style="color:#144D34 !important;"></i> Clinical Notes</div>
                        <div style="font-size:0.85rem; color:#144D34 !important; font-weight:700; margin-top:2px;">${c.clinical_notes}</div>
                    </div>
                ` : ''}
            </div>

            <!-- Medicines Table -->
            ${medicinesTableHtml}

            <!-- Handwritten Prescription Image -->
            ${imageHtml}

            <!-- Lab Results Section (First Consultation Card renders patient labs) -->
            ${idx === 0 ? labResultsHtml : ''}

            <!-- Follow up date -->
            ${c.follow_up_date ? `
                <div style="margin-top:0.85rem; padding-top:0.6rem; border-top:1px solid #E8E6DC; display:flex; align-items:center; gap:8px; font-size:0.85rem; font-weight:800; color:#144D34 !important;">
                    <i class="fas fa-calendar-alt" style="color:#144D34 !important;"></i> Next Follow-up Date: <span style="background:#E8F4EC; border:1px solid #C6E6D2; padding:2px 8px; border-radius:6px; color:#144D34 !important;">${c.follow_up_date}</span>
                </div>
            ` : ''}
        </div>
    `;
}

/**
 * STEP 6, 7, 8: Lab Results Section Renderer
 */
function renderLabResultsSection(labResults) {
    if (!labResults || labResults.length === 0) {
        return `
            <div style="margin-top:0.85rem; padding:0.6rem 0.85rem; background:#F4F1EA; border:1px dashed #D5D0C3; border-radius:8px; font-size:0.8rem; color:#144D34 !important; font-weight:700;">
                <i class="fas fa-flask" style="color:#88A093;"></i> No laboratory reports available
            </div>
        `;
    }

    return `
        <div style="margin-top:0.85rem; padding:0.85rem; background:#fff; border:1.5px solid #DEDACF; border-radius:10px;">
            <div style="font-size:0.8rem; font-weight:800; text-transform:uppercase; color:#144D34 !important; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                <i class="fas fa-vials" style="color:#144D34 !important;"></i> Laboratory Reports & Investigation Results
            </div>

            ${labResults.map(lab => {
                const params = lab.parsed_parameters || [];
                return `
                    <div style="border:1px solid #E2E0D6; border-radius:8px; padding:8px 12px; margin-bottom:8px; background:#FDFBF7;">
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:6px; margin-bottom:6px;">
                            <div style="font-weight:800; font-size:0.88rem; color:#144D34 !important;">
                                <i class="fas fa-flask" style="color:#144D34 !important;"></i> ${lab.test_name || 'Lab Test'}
                                <span style="font-size:0.75rem; font-weight:700; color:#557365 !important; margin-left:6px;">(${lab.result_date || ''} ${lab.result_time || ''})</span>
                            </div>

                            <div style="display:flex; gap:6px; align-items:center;">
                                <span style="font-size:0.72rem; font-weight:800; padding:2px 6px; border-radius:4px; background:${lab.status === 'Reviewed' ? '#E8F4EC' : '#FEF3C7'}; color:${lab.status === 'Reviewed' ? '#144D34' : '#92400E'} !important; border:1px solid ${lab.status === 'Reviewed' ? '#C6E6D2' : '#FDE68A'};">
                                    ${lab.status || 'Pending'}
                                </span>

                                ${lab.has_report_file ? `
                                    <button onclick="openPdfModal('${lab.report_file_url}')" class="btn btn-xs" style="font-size:0.72rem; padding:3px 8px; font-weight:800; background:#E8F4EC !important; color:#144D34 !important; border:1px solid #C6E6D2 !important;">
                                        <i class="fas fa-file-pdf text-red-600"></i> View PDF
                                    </button>
                                    <a href="${lab.report_file_url}" download class="btn btn-xs" style="font-size:0.72rem; padding:3px 8px; font-weight:800; background:#144D34 !important; color:#FFFFFF !important; border:none;">
                                        <i class="fas fa-download" style="color:#FFFFFF !important;"></i> Download Report
                                    </a>
                                ` : `
                                    <button disabled title="No PDF report file available" class="btn btn-xs" style="font-size:0.72rem; padding:3px 8px; opacity:0.5; cursor:not-allowed;">
                                        <i class="fas fa-file-pdf"></i> View PDF
                                    </button>
                                `}
                            </div>
                        </div>

                        ${params.length > 0 ? `
                            <table style="width:100%; border-collapse:collapse; margin-top:4px; font-size:0.8rem; background:#fff; border-radius:6px; overflow:hidden; border:1px solid #E2E0D6;">
                                <thead>
                                    <tr style="background:#F4F1EA; color:#144D34 !important; font-size:0.72rem; text-transform:uppercase;">
                                        <th style="padding:4px 8px; text-align:left; color:#144D34 !important;">Parameter</th>
                                        <th style="padding:4px 8px; text-align:left; color:#144D34 !important;">Result Value</th>
                                        <th style="padding:4px 8px; text-align:left; color:#144D34 !important;">Normal Range / Unit</th>
                                        <th style="padding:4px 8px; text-align:left; color:#144D34 !important;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${params.map(p => `
                                        <tr style="border-bottom:1px solid #F1F5F9; ${p.is_abnormal ? 'background:#FEF2F2 !important; color:#991B1B !important; font-weight:800;' : 'color:#144D34 !important; font-weight:700;'}">
                                            <td style="padding:4px 8px; font-weight:800;">
                                                ${p.is_abnormal ? '<i class="fas fa-exclamation-triangle text-red-600 mr-1" title="Abnormal Value"></i>' : ''}
                                                ${p.parameter}
                                            </td>
                                            <td style="padding:4px 8px; font-weight:900;">${p.value}</td>
                                            <td style="padding:4px 8px;">${p.range}</td>
                                            <td style="padding:4px 8px;">
                                                <span style="padding:1px 6px; border-radius:4px; font-size:0.72rem; font-weight:800; background:${p.is_abnormal ? '#FEE2E2' : '#E8F4EC'}; color:${p.is_abnormal ? '#991B1B' : '#144D34'} !important;">
                                                    ${p.status}
                                                </span>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        ` : ''}
                    </div>
                `;
            }).join('')}
        </div>
    `;
}

/**
 * STEP 10: Empty State Handler
 */
function showEmptyState(message) {
    const resultsSection = document.getElementById('results-section');
    const allSection = document.getElementById('all-prescriptions-section');
    const emptyState = document.getElementById('empty-state');

    if (resultsSection) resultsSection.style.display = 'none';
    if (allSection) allSection.style.display = 'none';
    if (emptyState) {
        emptyState.style.display = 'block';
        const h3 = emptyState.querySelector('h3');
        if (h3) h3.textContent = message || 'No prescriptions found';
        const p = emptyState.querySelector('p');
        if (p) p.textContent = 'Please double check the Patient ID or mobile number and try again.';
    }
}

/**
 * Image Viewer Modal & Zoom
 */
function openImageModal(url) {
    const modal = document.getElementById('image-viewer-modal');
    const img = document.getElementById('modal-preview-image');
    const downloadBtn = document.getElementById('image-download-link');
    if (!modal || !img) return;

    zoomLevel = 1;
    img.style.transform = `scale(${zoomLevel})`;
    img.src = url;
    if (downloadBtn) downloadBtn.href = url;

    modal.style.display = 'flex';
}

function closeImageModal() {
    const modal = document.getElementById('image-viewer-modal');
    if (modal) modal.style.display = 'none';
}

function zoomImage(delta) {
    const img = document.getElementById('modal-preview-image');
    if (!img) return;
    zoomLevel = Math.max(0.5, Math.min(3, zoomLevel + delta));
    img.style.transform = `scale(${zoomLevel})`;
}

function resetZoom() {
    const img = document.getElementById('modal-preview-image');
    if (!img) return;
    zoomLevel = 1;
    img.style.transform = `scale(${zoomLevel})`;
}

/**
 * PDF Viewer Modal
 */
function openPdfModal(url) {
    const modal = document.getElementById('pdf-viewer-modal');
    const iframe = document.getElementById('pdf-modal-iframe');
    const downloadBtn = document.getElementById('pdf-download-link');
    if (!modal || !iframe) return;

    iframe.src = url;
    if (downloadBtn) downloadBtn.href = url;

    modal.style.display = 'flex';
}

function closePdfModal() {
    const modal = document.getElementById('pdf-viewer-modal');
    const iframe = document.getElementById('pdf-modal-iframe');
    if (modal) modal.style.display = 'none';
    if (iframe) iframe.src = '';
}

/**
 * Print A4 Prescription Modal (Existing)
 */
async function viewProfessionalPrescription(prescriptionId) {
    const consultations = (currentPatientData && currentPatientData.consultations) ? currentPatientData.consultations : allPrescriptions;
    const presc = consultations.find(p => p.prescription_id === prescriptionId || p.consultation_id === prescriptionId);
    
    if (!presc) {
        console.error('Prescription not found:', prescriptionId);
        return;
    }

    const modal = document.getElementById('prescription-modal');
    const container = document.getElementById('professional-prescription-a4');

    const { medicines, planText } = parseSoapPlanJS(presc);

    let medicinesHTML = '';
    if (medicines.length > 0) {
        medicinesHTML = medicines.map(m => `
            <tr>
                <td><div class="med-pro-name">${m.name || m.medicine_name || m.title || 'N/A'}</div>${(m.instructions || m.notes || m.purpose) ? `<div class="med-pro-sub">${m.instructions || m.notes || m.purpose}</div>` : ''}</td>
                <td style="font-weight: 600;">${m.dosage || '-'}</td>
                <td>${m.timing || 'After Food'}</td>
                <td style="font-weight: 700; color: #0D9488;">${m.frequency || m.freq || '-'}</td>
                <td>${m.duration || '-'}</td>
                <td class="med-pro-qty">${m.qty || '0'}</td>
            </tr>
        `).join('');

        if (planText) {
            medicinesHTML += `
                <tr>
                    <td colspan="6" style="padding: 10px 12px; background: #F8FAFC; border-top: 1px solid #CBD5E1;">
                        <div style="font-weight: 800; color: #0D9488; font-size: 10px; text-transform: uppercase; margin-bottom: 2px;">Additional Plan / Instructions:</div>
                        <div style="font-size: 11px; color: #1E293B; white-space: pre-line; line-height: 1.4;">${planText}</div>
                    </td>
                </tr>
            `;
        }
    } else if (planText) {
        medicinesHTML = `
            <tr>
                <td colspan="6" style="padding: 16px 14px; background: #FFFFFF; font-size: 11px; color: #1E293B; line-height: 1.5; white-space: pre-line;">
                    <div style="font-weight: 800; color: #0D9488; font-size: 10px; text-transform: uppercase; margin-bottom: 4px;">Prescribed Treatment Plan & Instructions:</div>
                    ${planText}
                </td>
            </tr>
        `;
    } else {
        medicinesHTML = '<tr><td colspan="6" style="text-align: center; color: #94a3b8; padding: 2rem;">No medications prescribed</td></tr>';
    }

    container.innerHTML = `
        <div class="presc-inner-frame">
            <div class="watermark-pro">GM<br>HOSPITAL</div>
            
            <header class="presc-header">
                <div class="hospital-brand">
                    <h1>${presc.hospital_name || 'GM - Hospital'}</h1>
                    <p><i class="fas fa-map-marker-alt"></i> ${presc.hospital_address || 'Health City Circle'}</p>
                    <p><i class="fas fa-phone"></i> ${presc.hospital_phone || '+91 98765 43210'}</p>
                </div>
                <div class="doc-info-block">
                    <h2>Dr. ${presc.doctor_name || 'Doctor'}</h2>
                    <div class="spec">${presc.specialization || 'General Medicine'}</div>
                    <div class="reg">${presc.department ? 'DEPT: ' + presc.department : ''}</div>
                </div>
            </header>

            <section class="patient-cli-banner">
                <div class="cli-item"><label>Patient Name</label><span>${presc.first_name || ''} ${presc.last_name || ''}</span></div>
                <div class="cli-item"><label>Patient ID</label><span>${presc.patient_id || '-'}</span></div>
                <div class="cli-item"><label>Age</label><span>${presc.age || 'N/A'}Y</span></div>
                <div class="cli-item"><label>Sex</label><span>${presc.gender || 'N/A'}</span></div>
                <div class="cli-item" style="text-align: right; border-right: none;"><label>Date</label><span>${DateUtils.formatDateReadable(presc.prescription_date || presc.consultation_date)}</span></div>
            </section>

            <section>
                <div class="presc-section-header">Prescribed Plan (Medicines)</div>
                <table class="med-table-pro">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Medicine & Instructions</th>
                            <th>Dosage</th>
                            <th>Timing</th>
                            <th>Frequency</th>
                            <th>Duration</th>
                            <th style="text-align: center;">Qty</th>
                        </tr>
                    </thead>
                    <tbody>${medicinesHTML}</tbody>
                </table>
            </section>

            <footer class="presc-footer-pro">
                <div style="font-size: 11px; color: #94A3B8;">
                    <p style="margin-bottom: 2px;"><strong>Note:</strong> Electronic Prescription Record.</p>
                    <p>Printed on: ${new Date().toLocaleDateString('en-GB')}</p>
                </div>
            </footer>
        </div>
    `;

    modal.style.display = 'flex';
}

function closePrescriptionModal() {
    document.getElementById('prescription-modal').style.display = 'none';
}
