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
            document.getElementById('stat-opd-total').textContent = data.data.total_opd || 0;
            document.getElementById('stat-doctors-active').textContent = data.data.active_doctors || 0;
            document.getElementById('stat-revenue').textContent = formatCurrency(data.data.revenue_today || 0);
        }
    } catch (error) {
        console.error('Failed to load stats', error);
    }
}

async function loadQueue(filter) {
    const loader = document.getElementById('queue-loading');
    const list = document.getElementById('queue-list');
    const empty = document.getElementById('queue-empty');

    loader.style.display = 'block';
    list.style.display = 'none';
    empty.style.display = 'none';

    try {
        const res = await fetch(`${OPD_API_BASE}/api/opd/queue`);
        const json = await res.json();

        loader.style.display = 'none';

        if (!json.success || !json.data || json.data.length === 0) {
            empty.style.display = 'block';
            return;
        }

        const counts = {
            all: json.data.length,
            pending: json.data.filter(p => p.appointment_status === 'Pending').length,
            done: json.data.filter(p => p.appointment_status === 'Completed').length
        };
        updateTabCounts(counts);

        let patients = json.data;
        if (filter !== 'all') {
            patients = patients.filter(p => p.appointment_status === filter);
        }

        // Sort by appointment time (FIFO)
        patients.sort((a, b) => {
            const timeA = a.appointment_time || '23:59:59';
            const timeB = b.appointment_time || '23:59:59';
            return timeA.localeCompare(timeB);
        });

        if (patients.length === 0) {
            empty.style.display = 'block';
            return;
        }

        list.innerHTML = patients.map(p => createPatientCard(p)).join('');
        list.style.display = 'grid';

    } catch (error) {
        console.error('Error loading queue', error);
        loader.style.display = 'none';
        empty.style.display = 'block'; // Show empty or error state
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

function createPatientCard(p) {
    const statusLower = (p.appointment_status || 'waiting').toLowerCase().replace(' ', '-');
    const statusClass = `status-${statusLower}`;

    return `
        <div class="patient-card ${statusClass}" onclick="openEncounter('${p.appointment_id}')">
            <div class="card-glass-header">
                <span class="token-badge"><i class="fas fa-ticket-alt"></i> #${p.token_number || '---'}</span>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span class="status-text">${p.appointment_status}</span>
                    <span class="status-dot"></span>
                </div>
            </div>
            
            <div class="patient-info">
                <h4>${p.first_name} ${p.last_name}</h4>
                <div class="patient-detail id-tag">
                    <i class="fas fa-id-badge"></i> ${p.patient_id}
                </div>
                <div class="patient-detail" style="color: #1f6b4a; font-weight: 700;">
                    <i class="fas fa-clock"></i> ${formatApptTime(p.appointment_time)}
                </div>
                <div class="patient-detail">
                    <i class="fas fa-user-clock"></i> ${p.age} Y / ${p.sex}
                </div>
                <div class="patient-detail">
                    <i class="fas fa-stethoscope"></i> ${p.doctor_name || 'Not Assigned'}
                </div>
            </div>

            <div class="card-actions">
                <button class="vitals-btn" onclick="event.stopPropagation(); openEncounter('${p.appointment_id}', 'clinical')">
                    <i class="fas fa-heartbeat"></i> Vitals
                </button>
            </div>
        </div>
    `;
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

function updateTabCounts(counts) {
    const pendingTab = document.querySelector('[data-filter="Pending"]');
    const completedTab = document.querySelector('[data-filter="Completed"]');

    if (pendingTab) pendingTab.textContent = `Pending (${counts.pending})`;
    if (completedTab) completedTab.textContent = `Completed (${counts.done})`;
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
