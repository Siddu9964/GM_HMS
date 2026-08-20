/**
 * OPD Management Logic
 * Handles Queue Loading, Modal Interactions, and API calls
 */

document.addEventListener('DOMContentLoaded', () => {
    loadStats();
    loadQueue('all');

    // Filter Buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            e.target.classList.add('active');
            loadQueue(e.target.dataset.filter);
        });
    });

    // Vitals Form Submit
    const vitalsForm = document.getElementById('vitals-form');
    if (vitalsForm) {
        vitalsForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const sys = formData.get('bp_sys');
            const dia = formData.get('bp_dia');
            if (sys || dia) {
                formData.set('bp', `${sys}/${dia}`);
            }
            formData.delete('bp_sys');
            formData.delete('bp_dia');
            await saveVitals(formData);
        });
    }
});

// --- API Calls ---

const OPD_API_BASE = '/GM_HMS';

async function loadStats() {
    try {
        const res = await fetch(`${OPD_API_BASE}/api/opd/stats`);
        const data = await res.json();

        if (data.success) {
            const elTotal = document.getElementById('stat-opd-total');
            const elWaiting = document.getElementById('stat-opd-waiting');
            const elDoctors = document.getElementById('stat-doctors-active');
            const elRevenue = document.getElementById('stat-revenue');

            if (elTotal) elTotal.textContent = data.data.total_opd || 0;
            if (elWaiting) elWaiting.textContent = data.data.waiting_opd !== undefined ? data.data.waiting_opd : 0;
            if (elDoctors) elDoctors.textContent = data.data.active_doctors || 0;
            if (elRevenue) elRevenue.textContent = formatCurrency(data.data.revenue_today || 0);
        }
    } catch (error) {
        console.error('Failed to load stats', error);
    }
}

// Global Queue State
let rawQueueData = [];
let currentStatusFilter = 'all';
let currentDeptFilter = 'all';
let currentSearchQuery = '';

function getDepartmentIcon(dept) {
    if (!dept) return 'fa-stethoscope';
    const d = dept.toLowerCase();
    if (d.includes('surg')) return 'fa-procedures';
    if (d.includes('cardio')) return 'fa-heartbeat';
    if (d.includes('ent')) return 'fa-head-side-cough';
    if (d.includes('gyn') || d.includes('ob')) return 'fa-female';
    if (d.includes('ortho')) return 'fa-bone';
    if (d.includes('ped')) return 'fa-baby';
    if (d.includes('derm')) return 'fa-allergies';
    if (d.includes('neuro')) return 'fa-brain';
    if (d.includes('psych')) return 'fa-smile';
    if (d.includes('eye') || d.includes('opht')) return 'fa-eye';
    if (d.includes('dent') || d.includes('maxillo')) return 'fa-tooth';
    if (d.includes('uro') || d.includes('neph')) return 'fa-tint';
    if (d.includes('pulm') || d.includes('chest')) return 'fa-lungs';
    return 'fa-stethoscope';
}

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function onQueueSearch(query) {
    currentSearchQuery = (query || '').trim().toLowerCase();
    const clearBtn = document.getElementById('btn-clear-search');
    if (clearBtn) {
        clearBtn.style.display = currentSearchQuery ? 'block' : 'none';
    }
    renderQueue();
}
window.onQueueSearch = onQueueSearch;

function clearQueueSearch() {
    const input = document.getElementById('queue-search');
    if (input) input.value = '';
    onQueueSearch('');
}
window.clearQueueSearch = clearQueueSearch;

function updateDepartmentUI(patients) {
    const deptSelect = document.getElementById('dept-select');
    const pillsContainer = document.getElementById('dept-pills-container');
    if (!deptSelect) return;

    // Count by department
    const deptCounts = {};
    let total = patients.length;

    patients.forEach(p => {
        const dept = p.department || p.specialization || 'General Medicine';
        deptCounts[dept] = (deptCounts[dept] || 0) + 1;
    });

    const depts = Object.keys(deptCounts).sort();

    // 1. Dropdown options
    let selectHtml = `<option value="all" ${currentDeptFilter === 'all' ? 'selected' : ''}>🏥 All Departments (${total})</option>`;
    depts.forEach(dept => {
        const sel = (currentDeptFilter === dept) ? 'selected' : '';
        selectHtml += `<option value="${escapeHtml(dept)}" ${sel}>${dept} (${deptCounts[dept]})</option>`;
    });
    deptSelect.innerHTML = selectHtml;

    // 2. Quick Pills
    if (pillsContainer) {
        if (depts.length > 1) {
            let pillsHtml = `
                <button type="button" class="dept-pill ${currentDeptFilter === 'all' ? 'active' : ''}" onclick="onDepartmentChange('all')">
                    <i class="fas fa-hospital-alt"></i> All <span class="dept-pill-count">${total}</span>
                </button>
            `;
            depts.forEach(dept => {
                const isActive = (currentDeptFilter === dept) ? 'active' : '';
                const icon = getDepartmentIcon(dept);
                pillsHtml += `
                    <button type="button" class="dept-pill ${isActive}" onclick="onDepartmentChange('${escapeHtml(dept)}')">
                        <i class="fas ${icon}"></i> ${dept} <span class="dept-pill-count">${deptCounts[dept]}</span>
                    </button>
                `;
            });
            pillsContainer.innerHTML = pillsHtml;
            pillsContainer.style.display = 'flex';
        } else {
            pillsContainer.style.display = 'none';
        }
    }
}

function onDepartmentChange(dept) {
    currentDeptFilter = dept;
    const deptSelect = document.getElementById('dept-select');
    if (deptSelect && deptSelect.value !== dept) {
        deptSelect.value = dept;
    }
    document.querySelectorAll('.dept-pill').forEach(btn => {
        const isMatch = (btn.getAttribute('onclick') || '').includes(`'${dept}'`);
        btn.classList.toggle('active', isMatch);
    });
    renderQueue();
}
window.onDepartmentChange = onDepartmentChange;

function renderDepartmentTable(dept, patients) {
    const icon = getDepartmentIcon(dept);
    const waitingCount = patients.filter(p => p.appointment_status === 'Pending' || p.appointment_status === 'Scheduled').length;
    const completedCount = patients.filter(p => p.appointment_status === 'Completed').length;

    // Get doctor names in this department
    const doctors = [...new Set(patients.map(p => p.doctor_name).filter(Boolean))];
    const docText = doctors.length > 0 ? doctors.join(', ') : 'Assigned Doctor';

    let rowsHtml = '';
    patients.forEach(p => {
        const statusLower = (p.appointment_status || 'waiting').toLowerCase().replace(' ', '-');
        const tokenNo = String(p.token_number || '1').padStart(2, '0');
        const timeFormatted = formatApptTime(p.appointment_time);
        
        // Parse vitals
        let vitalsHtml = '<span class="vitals-pending-chip"><i class="fas fa-exclamation-circle"></i> Vitals Pending</span>';
        if (p.vital_signs) {
            try {
                const v = typeof p.vital_signs === 'string' ? JSON.parse(p.vital_signs) : p.vital_signs;
                const vParts = [];
                if (v.bp) vParts.push(`<span class="vital-chip recorded-badge"><i class="fas fa-heartbeat text-danger"></i> BP: ${escapeHtml(v.bp)}</span>`);
                if (v.pulse) vParts.push(`<span class="vital-chip"><i class="fas fa-wave-square text-warning"></i> P: ${escapeHtml(v.pulse)}</span>`);
                if (v.temp) vParts.push(`<span class="vital-chip"><i class="fas fa-thermometer-half text-warning"></i> ${escapeHtml(v.temp)}°F</span>`);
                if (v.spo2) vParts.push(`<span class="vital-chip"><i class="fas fa-lungs text-primary"></i> ${escapeHtml(v.spo2)}%</span>`);
                if (v.weight) vParts.push(`<span class="vital-chip"><i class="fas fa-weight text-success"></i> ${escapeHtml(v.weight)}kg</span>`);
                
                if (vParts.length > 0) {
                    vitalsHtml = `<div class="vitals-summary-box">${vParts.join('')}</div>`;
                }
            } catch (e) {
                // If not JSON
            }
        }

        // Status Badge
        let statusBadge = `<span class="tbl-status-chip status-pending">⏳ Waiting</span>`;
        if (p.appointment_status === 'Completed') {
            statusBadge = `<span class="tbl-status-chip status-completed">✅ Done</span>`;
        } else if (p.appointment_status === 'In-Progress' || p.appointment_status === 'With Doctor') {
            statusBadge = `<span class="tbl-status-chip status-in-progress">🩺 With Doctor</span>`;
        }

        rowsHtml += `
            <tr onclick="openEncounter('${p.appointment_id}')" style="cursor: pointer;">
                <td style="text-align: center; width: 60px;">
                    <span class="tbl-token-badge" style="background-color: #1f6b4a !important; color: #ffffff !important; font-weight: 900 !important; font-size: 0.9rem !important;">#${tokenNo}</span>
                </td>
                <td style="width: 110px;">
                    <span class="tbl-time-chip">
                        <i class="fas fa-clock"></i> ${timeFormatted}
                    </span>
                </td>
                <td>
                    <div class="tbl-patient-cell">
                        <div class="tbl-patient-name">${escapeHtml(p.first_name)} ${escapeHtml(p.last_name)}</div>
                        <div class="tbl-patient-meta">
                            <span class="tbl-pid-chip">${p.patient_id}</span>
                            <span>•</span>
                            <span>${p.age} Y / ${p.sex}</span>
                            ${p.phone ? `<span>•</span><span><i class="fas fa-phone-alt mr-1"></i>${p.phone}</span>` : ''}
                        </div>
                    </div>
                </td>
                <td>
                    <div class="tbl-doctor-cell">
                        <div class="tbl-doctor-name"><i class="fas fa-user-md text-primary mr-1"></i> ${escapeHtml(p.doctor_name || 'Not Assigned')}</div>
                        ${p.room_number ? `<div class="tbl-room-tag"><i class="fas fa-door-open"></i> Room: ${escapeHtml(p.room_number)}</div>` : ''}
                    </div>
                </td>
                <td>
                    ${vitalsHtml}
                </td>
                <td>
                    ${statusBadge}
                </td>
                <td style="text-align: right; width: 140px;" onclick="event.stopPropagation()">
                    <div class="tbl-actions-wrap justify-content-end">
                        <button type="button" class="tbl-btn-vitals" onclick="openEncounter('${p.appointment_id}', 'clinical')" title="Record / View Vitals">
                            <i class="fas fa-heartbeat"></i> Vitals
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });

    return `
        <div class="department-group-section mb-4">
            <div class="department-group-header">
                <div class="dept-group-title">
                    <i class="fas ${icon}"></i>
                    <span>${escapeHtml(dept)} Department</span>
                </div>
                <div class="dept-group-stats">
                    ${doctors.length > 0 ? `<div class="dept-doctor-pill"><i class="fas fa-user-md"></i> ${escapeHtml(docText)}</div>` : ''}
                    <span class="dept-group-count" style="background-color: #1f6b4a !important; color: #ffffff !important; font-weight: 800 !important; font-size: 0.8rem !important;">${patients.length} ${patients.length === 1 ? 'Patient' : 'Patients'} (${waitingCount} Waiting, ${completedCount} Done)</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="opd-clinical-table">
                    <thead>
                        <tr>
                            <th style="width: 60px; text-align: center;">Token</th>
                            <th style="width: 110px;">Time</th>
                            <th>Patient Information</th>
                            <th>Consulting Doctor</th>
                            <th>Vitals & Clinical Status</th>
                            <th>Queue Status</th>
                            <th style="text-align: right; width: 140px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rowsHtml}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

function renderQueue() {
    const list = document.getElementById('queue-list');
    const empty = document.getElementById('queue-empty');
    if (!list) return;

    if (!rawQueueData || rawQueueData.length === 0) {
        list.style.display = 'none';
        if (empty) empty.style.display = 'block';
        return;
    }

    // 1. Filter by Status
    let filtered = rawQueueData;
    if (currentStatusFilter !== 'all') {
        filtered = filtered.filter(p => p.appointment_status === currentStatusFilter);
    }

    // 2. Filter by Department
    if (currentDeptFilter !== 'all') {
        filtered = filtered.filter(p => (p.department || p.specialization || 'General Medicine') === currentDeptFilter);
    }

    // 3. Filter by Search Query
    if (currentSearchQuery) {
        filtered = filtered.filter(p => {
            const fullName = `${p.first_name || ''} ${p.last_name || ''}`.toLowerCase();
            const pid = (p.patient_id || '').toLowerCase();
            const phone = (p.phone || '').toLowerCase();
            const token = String(p.token_number || '');
            const doc = (p.doctor_name || '').toLowerCase();
            const dept = (p.department || p.specialization || '').toLowerCase();
            return fullName.includes(currentSearchQuery) ||
                   pid.includes(currentSearchQuery) ||
                   phone.includes(currentSearchQuery) ||
                   token.includes(currentSearchQuery) ||
                   doc.includes(currentSearchQuery) ||
                   dept.includes(currentSearchQuery);
        });
    }

    // Sort by appointment time (FIFO)
    filtered.sort((a, b) => {
        const timeA = a.appointment_time || '23:59:59';
        const timeB = b.appointment_time || '23:59:59';
        return timeA.localeCompare(timeB);
    });

    if (filtered.length === 0) {
        list.style.display = 'none';
        if (empty) {
            empty.innerHTML = `
                <div class="empty-icon-wrap mb-3">
                    <i class="fas fa-search"></i>
                </div>
                <h4 class="font-weight-bold text-dark mb-1">No matching patients</h4>
                <p class="text-muted">No appointments found matching your search and filter criteria.</p>
            `;
            empty.style.display = 'block';
        }
        return;
    }

    if (empty) empty.style.display = 'none';

    // If viewing All Departments, group by department!
    if (currentDeptFilter === 'all') {
        const grouped = {};
        filtered.forEach(p => {
            const dept = p.department || p.specialization || 'General Medicine';
            if (!grouped[dept]) grouped[dept] = [];
            grouped[dept].push(p);
        });

        let html = '';
        Object.keys(grouped).sort().forEach(dept => {
            html += renderDepartmentTable(dept, grouped[dept]);
        });
        list.innerHTML = html;
    } else {
        // Single department view
        list.innerHTML = renderDepartmentTable(currentDeptFilter, filtered);
    }

    list.style.display = 'block';
}

function updateTabCounts(counts) {
    const countAll = document.getElementById('count-all');
    const countPending = document.getElementById('count-pending');
    const countCompleted = document.getElementById('count-completed');

    if (countAll) countAll.textContent = counts.all || 0;
    if (countPending) countPending.textContent = counts.pending || 0;
    if (countCompleted) countCompleted.textContent = counts.done || 0;
}

async function loadQueue(filter = 'all') {
    currentStatusFilter = filter;
    const loader = document.getElementById('queue-loading');
    const list = document.getElementById('queue-list');
    const empty = document.getElementById('queue-empty');
    const refreshIcon = document.getElementById('refresh-icon');

    if (refreshIcon) refreshIcon.classList.add('fa-spin');
    loader.style.display = 'block';
    list.style.display = 'none';
    if (empty) empty.style.display = 'none';

    try {
        const res = await fetch(`${OPD_API_BASE}/api/opd/queue`);
        const json = await res.json();

        loader.style.display = 'none';
        if (refreshIcon) refreshIcon.classList.remove('fa-spin');

        if (!json.success || !json.data || json.data.length === 0) {
            rawQueueData = [];
            updateDepartmentUI([]);
            updateTabCounts({ all: 0, pending: 0, done: 0 });
            if (empty) {
                empty.innerHTML = `
                    <div class="empty-icon-wrap mb-3">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <h4 class="font-weight-bold text-dark mb-1">No patients in queue</h4>
                    <p class="text-muted">All clear for today.</p>
                `;
                empty.style.display = 'block';
            }
            return;
        }

        rawQueueData = json.data;

        const counts = {
            all: rawQueueData.length,
            pending: rawQueueData.filter(p => p.appointment_status === 'Pending' || p.appointment_status === 'Scheduled').length,
            done: rawQueueData.filter(p => p.appointment_status === 'Completed').length
        };
        updateTabCounts(counts);
        updateDepartmentUI(rawQueueData);
        renderQueue();

    } catch (error) {
        console.error('Error loading queue', error);
        loader.style.display = 'none';
        if (refreshIcon) refreshIcon.classList.remove('fa-spin');
        if (empty) {
            empty.innerHTML = `
                <div class="empty-icon-wrap mb-3">
                    <i class="fas fa-exclamation-triangle text-danger"></i>
                </div>
                <h4 class="font-weight-bold text-dark mb-1">Error Loading Queue</h4>
                <p class="text-muted">Could not fetch today's appointments. Please try refreshing.</p>
            `;
            empty.style.display = 'block';
        }
    }
}

function formatApptTime(timeString) {
    if (!timeString) return 'Time Not Set';
    try {
        const [h, m] = timeString.split(':');
        let hours = parseInt(h, 10);
        let ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        return hours + ':' + m + ' ' + ampm;
    } catch (e) {
        return timeString;
    }
}

// --- Encounter Modal ---

async function openEncounter(appointmentId, tab = 'clinical') {
    const modal = document.getElementById('encounterModal');

    // Reset and Load Data
    document.getElementById('modal-patient-name').textContent = 'Loading...';
    document.getElementById('vitals-form').reset();

    modal.classList.remove('hidden');
    switchTab(tab);

    try {
        const res = await fetch(`${OPD_API_BASE}/api/opd/encounter/${appointmentId}`);
        const json = await res.json();

        if (json.success) {
            populateEncounterData(json.data);
        }
    } catch (error) {
        console.error('Error loading encounter', error);
        showToast('Failed to load patient details', 'error');
    }
}
function populateEncounterData(data) {
    const pt = data.appointment;

    // Header
    const nameEl = document.getElementById('modal-patient-name');
    if (nameEl) nameEl.textContent = `${pt.first_name} ${pt.last_name}`;

    const idEl = document.getElementById('modal-patient-id');
    if (idEl) idEl.textContent = pt.patient_id;

    const detailsEl = document.getElementById('modal-patient-details');
    if (detailsEl) detailsEl.textContent = `${pt.age} Y / ${pt.sex} / ${pt.blood_group || '-'}`;

    const doctorEl = document.getElementById('modal-doctor-name');
    if (doctorEl) doctorEl.textContent = pt.doctor_name || 'Not assigned';

    // Vitals Form Hidden Fields
    const apptInput = document.getElementById('vitals-appt-id');
    if (apptInput) apptInput.value = pt.appointment_id;

    const ptInput = document.getElementById('vitals-patient-id');
    if (ptInput) ptInput.value = pt.patient_id;

    const docInput = document.getElementById('vitals-doctor-id');
    if (docInput) docInput.value = pt.doctor_id;

    // Fill Vitals if exist
    if (data.consultation && data.consultation.vital_signs) {
        try {
            const vitals = JSON.parse(data.consultation.vital_signs);
            const form = document.getElementById('vitals-form');
            if (vitals.bp) {
                const parts = vitals.bp.split('/');
                if (parts.length === 2) {
                    form.querySelector('[name="bp_sys"]').value = parts[0] || '';
                    form.querySelector('[name="bp_dia"]').value = parts[1] || '';
                } else {
                    form.querySelector('[name="bp_sys"]').value = vitals.bp;
                }
            } else {
                form.querySelector('[name="bp_sys"]').value = '';
                form.querySelector('[name="bp_dia"]').value = '';
            }
            form.querySelector('[name="pulse"]').value = vitals.pulse || '';
            form.querySelector('[name="temp"]').value = vitals.temp || '';
            form.querySelector('[name="weight"]').value = vitals.weight || '';
            form.querySelector('[name="spo2"]').value = vitals.spo2 || '';

            form.querySelector('[name="chief_complaint"]').value = data.consultation.complaint || data.consultation.soap_subjective || '';
        } catch (e) {
            console.error('Error parsing vitals', e);
        }
    }
}

async function saveVitals(formData) {
    const data = Object.fromEntries(formData.entries());

    // Explicitly add these in case FormData misses them
    if (!data.patient_id) data.patient_id = document.getElementById('vitals-patient-id').value;
    if (!data.appointment_id) data.appointment_id = document.getElementById('vitals-appt-id').value;
    if (!data.doctor_id) data.doctor_id = document.getElementById('vitals-doctor-id').value;

    try {
        const res = await fetch(`${OPD_API_BASE}/api/opd/vitals`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();

        if (json.success) {
            Swal.fire({
                title: 'Success!',
                text: 'Vitals saved successfully',
                icon: 'success',
                confirmButtonColor: '#1f6b4a'
            }).then(() => {
                closeModal();
                loadQueue('all');
            });
        } else {
            Swal.fire({
                title: 'Error!',
                text: 'Failed to save vitals',
                icon: 'error',
                confirmButtonColor: '#d33'
            });
        }
    } catch (error) {
        console.error('Error saving vitals', error);
        showToast('Network error', 'error');
    }
}

async function saveLabRequest(formData) {
    const data = Object.fromEntries(formData.entries());
    data.patient_id = document.getElementById('vitals-patient-id').value;
    data.doctor_id = document.getElementById('vitals-doctor-id').value; // Assuming doctor ID is available

    try {
        const res = await fetch(`${OPD_API_BASE}/api/opd/lab-request`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();

        if (json.success) {
            showToast('Lab request sent', 'success');
            document.getElementById('lab-form').reset();
            openEncounter(document.getElementById('vitals-appt-id').value, 'labs');
        } else {
            showToast('Failed to send lab request', 'error');
        }
    } catch (error) {
        console.error('Error saving lab request', error);
    }
}

async function saveFollowUp(formData) {
    const data = Object.fromEntries(formData.entries());
    data.appointment_id = document.getElementById('vitals-appt-id').value;

    try {
        const res = await fetch(`${OPD_API_BASE}/api/opd/follow-up`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();

        if (json.success) {
            showToast('Follow-up scheduled', 'success');
            openEncounter(data.appointment_id, 'followup');
        } else {
            showToast('Failed to schedule follow-up', 'error');
        }
    } catch (error) {
        console.error('Error saving follow-up', error);
    }
}

// --- Printing ---

function printPrescription() {
    const patientName = document.getElementById('modal-patient-name').textContent;
    const doctorName = document.getElementById('modal-doctor-name').textContent;
    const items = document.getElementById('rx-list').innerHTML;

    // Create a print window
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Prescription - ${patientName}</title>
            <style>
                body { font-family: 'Inter', sans-serif; padding: 2rem; }
                .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 1rem; margin-bottom: 2rem; }
                .meta { display: flex; justify-content: space-between; margin-bottom: 2rem; }
                .rx-header { font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem; }
                .rx-item { margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px dashed #ccc; }
                .rx-name { font-weight: bold; font-size: 1.1rem; }
                .footer { margin-top: 4rem; text-align: right; border-top: 1px solid #ccc; padding-top: 1rem; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>GM HOSPITAL</h1>
                <p>Excellence in Healthcare</p>
            </div>
            <div class="meta">
                <div>
                    <strong>Patient:</strong> ${patientName}<br>
                    <strong>Date:</strong> ${new Date().toLocaleDateString()}
                </div>
                <div>
                    <strong>Doctor:</strong> ${doctorName}
                </div>
            </div>
            <div class="rx-header">Rx</div>
            <div class="content">
                ${items}
            </div>
            <div class="footer">
                <p>Doctor's Signature</p>
            </div>
            <script>window.print();</script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

// --- Reports ---

async function openReportsModal() {
    const modal = document.getElementById('reportsModal');
    modal.classList.remove('hidden');
    await loadReports();
}

function closeReportsModal() {
    document.getElementById('reportsModal').classList.add('hidden');
}

async function loadReports() {
    // Clear previous
    document.getElementById('report-daily-trend').innerHTML = '<div class="spinner mx-auto"></div>';
    document.getElementById('report-revenue').innerHTML = '<div class="spinner mx-auto"></div>';
    document.getElementById('report-doctor-wise').innerHTML = '<tr><td colspan="2" class="text-center">Loading...</td></tr>';

    try {
        console.log('Fetching reports from:', `${OPD_API_BASE}/api/opd/reports`);
        const res = await fetch(`${OPD_API_BASE}/api/opd/reports`);
        console.log('Response status:', res.status);
        console.log('Response headers:', res.headers);

        const text = await res.text();
        console.log('Raw response:', text);

        let json;
        try {
            json = JSON.parse(text);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text was:', text);
            showToast('Server returned invalid JSON: ' + text.substring(0, 100), 'error');
            return;
        }

        if (json.success) {
            const data = json.data;

            // 1. Daily Trend
            if (data.daily_trend && data.daily_trend.length > 0) {
                const list = data.daily_trend.map(d => `<div class="d-flex justify-content-between border-bottom py-2">
                    <span>${d.date}</span>
                    <span class="font-weight-bold">${d.count}</span>
                </div>`).join('');
                document.getElementById('report-daily-trend').innerHTML = list;
            } else {
                document.getElementById('report-daily-trend').innerHTML = '<p class="text-secondary text-center">No data available</p>';
            }

            // 2. Revenue
            if (data.revenue) {
                document.getElementById('report-revenue').innerHTML = `
                    <div class="text-center py-4">
                        <h3 class="text-success font-weight-bold display-4">${formatCurrency(data.revenue.total || 0)}</h3>
                        <p class="text-secondary">${data.revenue.count || 0} Invoices generated this month</p>
                    </div>
                `;
            }

            // 3. Doctor Wise
            if (data.doctor_wise && data.doctor_wise.length > 0) {
                const rows = data.doctor_wise.map(d => `
                    <tr>
                        <td>${d.full_name}</td>
                        <td class="font-weight-bold">${d.count}</td>
                    </tr>
                `).join('');
                document.getElementById('report-doctor-wise').innerHTML = rows;
            } else {
                document.getElementById('report-doctor-wise').innerHTML = '<tr><td colspan="2" class="text-center text-secondary">No consults today</td></tr>';
            }

        } else {
            console.error('API returned error:', json);
            showToast('API Error - Loading sample data', 'warning');
            loadMockReports(); // Fallback to mock data
        }
    } catch (error) {
        console.error('Error loading reports:', error);
        showToast('Network error - Loading sample data', 'warning');
        loadMockReports(); // Fallback to mock data
    }
}

// Mock data fallback for testing/demo
function loadMockReports() {
    console.log('Loading mock reports data...');

    // 1. Daily Trend - Last 7 days
    const dailyTrend = [
        { date: '2025-12-24', count: 45 },
        { date: '2025-12-25', count: 38 },
        { date: '2025-12-26', count: 52 },
        { date: '2025-12-27', count: 61 },
        { date: '2025-12-28', count: 48 },
        { date: '2025-12-29', count: 55 },
        { date: '2025-12-30', count: 42 }
    ];

    const dailyList = dailyTrend.map(d => `<div class="d-flex justify-content-between border-bottom py-2">
        <span>${d.date}</span>
        <span class="font-weight-bold">${d.count}</span>
    </div>`).join('');
    document.getElementById('report-daily-trend').innerHTML = dailyList;

    // 2. Revenue
    document.getElementById('report-revenue').innerHTML = `
        <div class="text-center py-4">
            <h3 class="text-success font-weight-bold display-4">₹45,250.00</h3>
            <p class="text-secondary">127 Invoices generated this month</p>
        </div>
    `;

    // 3. Doctor Wise
    const doctorWise = [
        { full_name: 'Dr. Ravi Kumar', count: 18 },
        { full_name: 'Dr. Priya Sharma', count: 15 },
        { full_name: 'Dr. Amit Patel', count: 12 },
        { full_name: 'Dr. Sneha Reddy', count: 9 }
    ];

    const doctorRows = doctorWise.map(d => `
        <tr>
            <td>${d.full_name}</td>
            <td class="font-weight-bold">${d.count}</td>
        </tr>
    `).join('');
    document.getElementById('report-doctor-wise').innerHTML = doctorRows;
}

// --- Utils ---

function closeModal() {
    document.getElementById('encounterModal').classList.add('hidden');
}

function switchTab(tabId) {
    // Update Buttons
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    const activeBtn = document.querySelector(`.tab-btn[onclick="switchTab('${tabId}')"]`);
    if (activeBtn) activeBtn.classList.add('active');

    // Update Content
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    const targetContent = document.getElementById(`tab-${tabId}`);
    if (targetContent) targetContent.classList.add('active');
}

// Global click to close modal
window.onclick = function (event) {
    const modal = document.getElementById('encounterModal');
    const reportModal = document.getElementById('reportsModal');
    if (event.target == modal) {
        closeModal();
    }
    if (event.target == reportModal) {
        closeReportsModal();
    }
}

function formatCurrency(amount) {
    return '₹' + parseFloat(amount).toFixed(2);
}

// Toast helper from reception_utils.js is expected to be available
