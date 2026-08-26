let tlAppointments = [];
let tlBills = [];
let tlPatient = null;

function buildTimeline() {
    const container = document.getElementById('patientTimelineContainer');
    if (!container) return;
    
    let events = [];
    
    // Add registration event if we have patient data
    if (tlPatient && tlPatient.registration_date) {
        events.push({
            type: 'registration',
            date: new Date(tlPatient.registration_date),
            title: 'Patient Registered',
            desc: 'Profile created in GM HMS system.',
            icon: '<i class="fas fa-user-plus"></i>',
            iconStyle: 'background:#f3efe6; color:#9a8f82; border-color:#c5bdb2;',
            meta: `<span><i class="fas fa-desktop"></i> Front Desk</span>`
        });
    }

    // Add appointments
    tlAppointments.forEach(appt => {
        if (!appt.appointment_date) return;
        let dateStr = appt.appointment_date;
        if (appt.appointment_time) dateStr += ' ' + appt.appointment_time;
        
        let title = 'Consultation';
        let icon = '<i class="fas fa-calendar-check"></i>';
        let iconStyle = '';
        
        if (appt.appointment_status === 'Completed') {
            title = 'Consultation Completed';
            icon = '<i class="fas fa-check-circle"></i>';
        } else if (appt.appointment_status === 'Cancelled') {
            title = 'Consultation Cancelled';
            icon = '<i class="fas fa-times-circle"></i>';
            iconStyle = 'background:#fef3e2; color:#e65100; border-color:#ffcc80;';
        } else {
            title = 'Appointment Scheduled';
            icon = '<i class="fas fa-calendar-alt"></i>';
            iconStyle = 'background:#e3f2fd; color:#1565c0; border-color:#64b5f6;';
        }
        
        events.push({
            type: 'appointment',
            date: new Date(dateStr),
            title: title,
            desc: appt.reason ? `Reason: ${appt.reason}` : 'Routine checkup scheduled.',
            icon: icon,
            iconStyle: iconStyle,
            meta: `<span><i class="fas fa-user-md"></i> ${appt.doctor_name || 'Doctor'}</span>
                   <span><i class="fas fa-clinic-medical"></i> ${appt.specialization || 'General'}</span>`
        });
    });

    // Add bills
    tlBills.forEach(bill => {
        if (!bill.bill_date) return;
        
        let title = bill.payment_status === 'Paid' ? 'Payment Completed' : 'Bill Generated';
        let icon = '<i class="fas fa-file-invoice-dollar"></i>';
        let iconStyle = bill.payment_status === 'Paid' ? 'background:#e8f5e9; color:#2e7d32; border-color:#4caf50;' : 'background:#fff8e1; color:#f57f17; border-color:#ffe082;';
        
        events.push({
            type: 'bill',
            date: new Date(bill.bill_date),
            title: title,
            desc: `Invoice #${bill.bill_id} for ₹${bill.grand_total} (${bill.payment_status || 'Pending'}).`,
            icon: icon,
            iconStyle: iconStyle,
            meta: `<span><i class="fas fa-receipt"></i> ${bill.purpose || 'OPD Billing'}</span>`
        });
    });

    // Sort descending (newest first)
    events.sort((a, b) => b.date - a.date);

    if (events.length === 0) {
        container.innerHTML = '<div style="padding:40px; text-align:center; color:#9a8f82;">No timeline events found.</div>';
        return;
    }

    container.innerHTML = events.map(ev => {
        let timeStr = ev.date.toLocaleString('en-IN', { day:'numeric', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
        if (timeStr === 'Invalid Date') timeStr = 'Unknown Date';
        
        return `
        <div class="gp-tl-item">
            <div class="gp-tl-icon" style="${ev.iconStyle}">${ev.icon}</div>
            <div class="gp-tl-content">
                <div class="gp-tl-header">
                    <div class="gp-tl-title">${ev.title}</div>
                    <div class="gp-tl-time">${timeStr}</div>
                </div>
                <div class="gp-tl-desc">${ev.desc}</div>
                <div class="gp-tl-meta">
                    ${ev.meta}
                </div>
            </div>
        </div>`;
    }).join('');

    updateHealthOverview();
}

function updateHealthOverview() {
    let score = 95;
    let risk = 'Low Risk';
    let riskColor = '#2e7d32'; // green
    
    // Calculate dynamic health score
    if (tlPatient && tlPatient.age) {
        let age = parseInt(tlPatient.age) || 0;
        if (age > 40) score -= 5;
        if (age > 60) score -= 10;
        if (age > 75) score -= 15;
    }
    
    if (tlAppointments.length > 2) {
        score -= (tlAppointments.length - 2) * 2;
    }

    if (score < 40) score = 40;

    if (score < 65) {
        risk = 'High Risk';
        riskColor = '#d32f2f'; // red
    } else if (score < 80) {
        risk = 'Moderate Risk';
        riskColor = '#f57f17'; // orange
    } else {
        riskColor = '#1f6b4a'; // default theme green for good health
    }

    const scoreChart = document.getElementById('healthScoreChart');
    if (scoreChart) {
        scoreChart.style.background = `conic-gradient(${riskColor} ${score}%, #ede8dd 0)`;
    }
    const scoreVal = document.getElementById('healthScoreVal');
    if (scoreVal) {
        scoreVal.textContent = score;
    }
    const riskVal = document.getElementById('riskLevelVal');
    if (riskVal) {
        riskVal.textContent = risk;
        riskVal.style.color = riskColor;
    }

    const physVal = document.getElementById('heroAssignedDoctor');
    if (physVal && tlAppointments.length > 0) {
        const latest = tlAppointments.find(a => a.doctor_name);
        if (latest && latest.doctor_name) {
            let docName = latest.doctor_name;
            if (!docName.toLowerCase().startsWith('dr')) docName = 'Dr. ' + docName;
            physVal.textContent = docName;
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    if (!patientId) {
        document.getElementById('profileCard').innerHTML = `
            <div class="gp-hero">
                <div class="gp-hero-bar"></div>
                <div style="padding:60px;text-align:center;">
                    <div class="gp-empty-ico" style="margin:0 auto 16px;"><i class="fas fa-exclamation-triangle"></i></div>
                    <p class="gp-empty" style="padding:0;">No Patient ID provided.</p>
                </div>
            </div>`;
        return;
    }
    loadPatientProfile();
});

/* ─── LOAD PATIENT PROFILE (logic unchanged) ─────────────── */
function loadPatientProfile() {
    fetch(`/GM_HMS/api/patients/${patientId}`)
        .then(response => response.json())
        .then(res => {
            if (res.status === 'success' && res.data) {
                tlPatient = res.data;
                buildTimeline();
                renderProfileCard(res.data);
                document.getElementById('bookApptBtn').disabled = false;
                document.getElementById('historyContainer').classList.remove('hidden');
                
                // Fetch dependent data now that the hero card is rendered
                loadAppointments();
                loadBills();
                loadLabResults();
            } else {
                throw new Error(res.message || 'Patient not found');
            }
        })
        .catch(err => {
            document.getElementById('profileCard').innerHTML = `
                <div class="gp-hero">
                    <div class="gp-emr-header">
                        <div class="gp-emr-identity">
                            <div class="gp-emr-avatar" style="background:#1f6b4a; color:#f3efe6;"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="gp-emr-name-box">
                                <h2>Patient Record Notice</h2>
                                <span class="gp-emr-pid">RECORD STATUS</span>
                            </div>
                        </div>
                    </div>
                    <div style="padding:32px 24px; text-align:center; color:#1f6b4a; font-weight:600;">
                        <p>${err.message}</p>
                    </div>
                </div>`;
        });
}

/* ─── RENDER PROFESSIONAL PATIENT DOSSIER (EMR BANNER) ──────── */
function renderProfileCard(patient) {
    const isOld = patient.latest_appointment_status || patient.last_visit;
    const badgeHtml = isOld
        ? `<div class="gp-status-chip gp-chip-secondary"><i class="fas fa-history" style="font-size:0.65rem;"></i> RETURNING</div>`
        : `<div class="gp-status-chip gp-chip-secondary"><i class="fas fa-star" style="font-size:0.65rem;"></i> NEW PATIENT</div>`;

    const initial = (patient.first_name || patient.full_name || 'P').charAt(0).toUpperCase();
    const addressParts = [patient.address, patient.area, patient.city, patient.state, patient.pincode].filter(Boolean);
    const fullAddress = addressParts.length > 0 ? addressParts.join(', ') : 'Address not provided';
    const regDate = patient.date
        ? new Date(patient.date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
        : 'N/A';
    const ageGender = (patient.age ? patient.age + ' yrs' : 'N/A') + ' • ' + (patient.gender || 'N/A');
    const emailVal = patient.email || 'Not Provided';
    const phoneVal = patient.phone || 'Not Provided';
    const aadhaarVal = patient.aadhaar_number || 'Not Linked';
    const emergencyVal = patient.emergency_contact || 'Not Provided';
    const insuranceVal = patient.insurance_status || 'Self Pay / Cash';
    const occupationVal = patient.occupation || 'Not Provided';
    const doctorVal = patient.assigned_doctor || 'Not Assigned';
    const bloodVal = patient.blood_group ? patient.blood_group : 'Not Tested';

    const html = `
        <div class="gp-hero">
            <!-- TOP STRIP: Master Patient Identity & Quick Indicators -->
            <div class="gp-emr-header">
                <div class="gp-emr-identity">
                    <div class="gp-emr-avatar">${initial}</div>
                    <div class="gp-emr-name-box">
                        <h2>${patient.full_name || 'Unknown Patient'}</h2>
                        <div class="gp-emr-badges">
                            <span class="gp-emr-pid" style="background-color:#1f6b4a !important; color:#ffffff !important; font-weight:800 !important; text-shadow:0 1px 2px rgba(0,0,0,0.3); font-family:monospace; padding:3px 10px; border-radius:6px; display:inline-flex; align-items:center;">${patient.patient_id || 'N/A'}</span>
                            <span class="gp-emr-pill"><i class="fas fa-user" style="font-size:0.65rem; margin-right:3px;"></i> ${ageGender}</span>
                            <span class="gp-emr-pill"><i class="fas fa-tint" style="font-size:0.65rem; margin-right:3px;"></i> ${bloodVal}</span>
                            <div class="gp-status-chip" id="heroActiveStatus" style="background-color:#1f6b4a !important; color:#ffffff !important; border:1px solid #144d34;">
                                <span class="gp-status-dot" style="background-color:#4ade80 !important; width:7px; height:7px; border-radius:50%; display:inline-block; margin-right:4px;"></span>
                                <span style="color:#ffffff !important; font-weight:800 !important; text-shadow:0 1px 2px rgba(0,0,0,0.3); letter-spacing:0.5px;">ACTIVE PATIENT</span>
                            </div>
                            ${badgeHtml}
                        </div>
                    </div>
                </div>

                <div class="gp-emr-metrics">
                    <div class="gp-emr-metric-box">
                        <div class="gp-emr-metric-val" id="kpiAppt">—</div>
                        <div class="gp-emr-metric-lbl">Appointments</div>
                    </div>
                    <div class="gp-emr-metric-box">
                        <div class="gp-emr-metric-val" id="kpiBills">—</div>
                        <div class="gp-emr-metric-lbl">Bills</div>
                    </div>
                    <div class="gp-emr-metric-box">
                        <div class="gp-emr-metric-val" style="font-size:0.95rem;">${regDate}</div>
                        <div class="gp-emr-metric-lbl">Registered</div>
                    </div>
                </div>
            </div>

            <!-- BOTTOM SECTION: Medical Dossier Columns -->
            <div class="gp-emr-dossier">
                <!-- Col 1: Contact -->
                <div class="gp-dossier-col">
                    <div class="gp-dossier-header"><i class="fas fa-phone-alt"></i> Contact Details</div>
                    <div class="gp-dossier-row">
                        <div class="gp-dossier-label">Primary Mobile</div>
                        <div class="gp-dossier-value">${phoneVal}</div>
                    </div>
                    <div class="gp-dossier-row">
                        <div class="gp-dossier-label">Email Address</div>
                        <div class="gp-dossier-value" style="word-break:break-all;">${emailVal}</div>
                    </div>
                </div>

                <!-- Col 2: Clinical Team -->
                <div class="gp-dossier-col">
                    <div class="gp-dossier-header"><i class="fas fa-user-md"></i> Clinical Care Team</div>
                    <div class="gp-dossier-row">
                        <div class="gp-dossier-label">Attending Doctor</div>
                        <div class="gp-dossier-value" id="heroAssignedDoctor" style="font-weight:700;">${doctorVal}</div>
                    </div>
                    <div class="gp-dossier-row">
                        <div class="gp-dossier-label">Emergency Contact</div>
                        <div class="gp-dossier-value">${emergencyVal}</div>
                    </div>
                </div>

                <!-- Col 3: Identification & Coverage -->
                <div class="gp-dossier-col">
                    <div class="gp-dossier-header"><i class="fas fa-id-card"></i> Identity &amp; Coverage</div>
                    <div class="gp-dossier-row">
                        <div class="gp-dossier-label">Aadhaar / National ID</div>
                        <div class="gp-dossier-value">${aadhaarVal}</div>
                    </div>
                    <div class="gp-dossier-row">
                        <div class="gp-dossier-label">Insurance / Billing Status</div>
                        <div class="gp-dossier-value">${insuranceVal}</div>
                    </div>
                    <div class="gp-dossier-row">
                        <div class="gp-dossier-label">Occupation</div>
                        <div class="gp-dossier-value">${occupationVal}</div>
                    </div>
                </div>

                <!-- Col 4: Residential Location -->
                <div class="gp-dossier-col">
                    <div class="gp-dossier-header"><i class="fas fa-map-marker-alt"></i> Residential Address</div>
                    <div class="gp-dossier-row">
                        <div class="gp-dossier-label">Full Address</div>
                        <div class="gp-dossier-value" style="line-height:1.4;">${fullAddress}</div>
                    </div>
                </div>
            </div>
        </div>`;

    document.getElementById('profileCard').innerHTML = html;
}

/* ─── LOAD APPOINTMENTS (logic unchanged) ─────────────────── */
function loadAppointments() {
    fetch(`/GM_HMS/api/appointments?patient_id=${patientId}`)
        .then(res => res.json())
        .then(res => {
            const tbody = document.querySelector('#appointmentsTable tbody');
            if (res.status === 'success' && res.data && res.data.length > 0) {
                // Filter out synthetic rows and match patient safely
                const appointments = res.data.filter(a => {
                    const dbPid = (a.patient_id || '').trim().toLowerCase();
                    const targetPid = (patientId || '').trim().toLowerCase();
                    const isRealAppt = !(a.appointment_id || '').startsWith('NOAPT-');
                    return dbPid === targetPid && isRealAppt;
                });
                tlAppointments = appointments;
                buildTimeline();

                const apptCountEl = document.getElementById('apptCount');
                if (apptCountEl) apptCountEl.textContent = appointments.length;
                const kpiApptEl = document.getElementById('kpiAppt');
                if (kpiApptEl) kpiApptEl.textContent = appointments.length;
                const sv = document.getElementById('statVisits');
                if (sv) sv.textContent = appointments.length;

                if (appointments.length === 0) {
                    tbody.innerHTML = `
                        <tr><td colspan="5">
                            <div class="gp-empty">
                                <div class="gp-empty-ico"><i class="fas fa-calendar-times"></i></div>
                                <p>No appointments found for this patient.</p>
                            </div>
                        </td></tr>`;
                    return;
                }

                // Update Hero Card Status based on latest appointment
                const heroStatusEl = document.getElementById('heroActiveStatus');
                if (heroStatusEl && appointments.length > 0) {
                    const latestStatus = (appointments[0].appointment_status || '').toString().toLowerCase();
                    if (latestStatus === '1' || latestStatus === 'completed') {
                        heroStatusEl.innerHTML = `<span class="gp-status-dot" style="background-color:#ffffff !important; opacity:0.8; width:7px; height:7px; border-radius:50%; display:inline-block; margin-right:4px;"></span> <span style="color:#ffffff !important; font-weight:800 !important; text-shadow:0 1px 2px rgba(0,0,0,0.3); letter-spacing:0.5px;">VISIT COMPLETED</span>`;
                    } else {
                        heroStatusEl.innerHTML = `<span class="gp-status-dot" style="background-color:#4ade80 !important; width:7px; height:7px; border-radius:50%; display:inline-block; margin-right:4px;"></span> <span style="color:#ffffff !important; font-weight:800 !important; text-shadow:0 1px 2px rgba(0,0,0,0.3); letter-spacing:0.5px;">ACTIVE PATIENT</span>`;
                    }
                }

                tbody.innerHTML = appointments.map(appt => {
                    let sc = 'gp-sched';
                    let displayStatus = appt.appointment_status;
                    if (displayStatus === null || displayStatus === undefined) {
                        displayStatus = 'Scheduled';
                    }
                    
                    const stStr = displayStatus.toString().toLowerCase();
                    if (stStr === '1' || stStr === 'completed') {
                        sc = 'gp-done';
                        displayStatus = 'Completed';
                    } else if (stStr === '0' || stStr === 'active') {
                        sc = 'gp-sched';
                        displayStatus = 'Active';
                    } else if (stStr === 'cancelled') {
                        sc = 'gp-cancel';
                        displayStatus = 'Cancelled';
                    }

                    const di = (appt.doctor_name || 'D').charAt(0).toUpperCase();
                    return `<tr>
                        <td>
                            <div class="gp-td-d1">${appt.appointment_date}</div>
                            <div class="gp-td-d2">${appt.appointment_time}</div>
                        </td>
                        <td>
                            <div class="gp-doc-row">
                                <span class="gp-doc-chip">${di}</span>
                                <span class="gp-doc-name">${appt.doctor_name || '—'}</span>
                            </div>
                        </td>
                        <td style="color:#5a5047;">${appt.specialization || '—'}</td>
                        <td>${appt.reason || '—'}</td>
                        <td><span class="gp-pill ${sc}">${displayStatus}</span></td>
                    </tr>`;
                }).join('');
            } else {
                tbody.innerHTML = `
                    <tr><td colspan="5">
                        <div class="gp-empty">
                            <div class="gp-empty-ico"><i class="fas fa-calendar-times"></i></div>
                            <p>No appointments found for this patient.</p>
                        </div>
                    </td></tr>`;
            }
        })
        .catch(err => {
            console.error("Error loading appointments:", err);
            document.querySelector('#appointmentsTable tbody').innerHTML =
                `<tr><td colspan="5" style="text-align:center;color:#e65100;padding:32px;font-weight:600;">Failed to load appointments.</td></tr>`;
        });
}

/* ─── LOAD BILLS (logic unchanged) ───────────────────────── */
function loadBills() {
    fetch(`/GM_HMS/api/billing/opd?patient_id=${patientId}&all=true`)
        .then(res => res.json())
        .then(res => {
            const tbody = document.querySelector('#billsTable tbody');
            if (res.status === 'success' && res.data && res.data.length > 0) {
                tlBills = res.data;
                buildTimeline();
                
                const billCountEl = document.getElementById('billCount');
                if (billCountEl) billCountEl.textContent = res.data.length;
                const kpiBillsEl = document.getElementById('kpiBills');
                if (kpiBillsEl) kpiBillsEl.textContent = res.data.length;
                const sb = document.getElementById('statBillsLeft');
                if (sb) sb.textContent = res.data.length;

                let totalSum = 0;
                let totalPaid = 0;
                let totalPending = 0;
                const rowsHtml = res.data.map(bill => {
                    const sc = bill.payment_status === 'Paid' ? 'gp-paid' : 'gp-pending';
                    const bDate = bill.bill_date
                        ? new Date(bill.bill_date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
                        : '—';
                    const amtVal = parseFloat(bill.grand_total || 0);
                    totalSum += amtVal;
                    if (bill.payment_status === 'Paid') {
                        totalPaid += amtVal;
                    } else {
                        totalPending += amtVal;
                    }
                    const amt = amtVal.toLocaleString('en-IN', { minimumFractionDigits: 2 });
                    return `<tr>
                        <td><span class="gp-bid">${bill.bill_id}</span></td>
                        <td><div class="gp-td-d1">${bDate}</div></td>
                        <td>${bill.purpose || 'OPD Service'}</td>
                        <td><span class="gp-amt">₹${amt}</span></td>
                        <td><span class="gp-pill ${sc}">${bill.payment_status || 'Pending'}</span></td>
                    </tr>`;
                }).join('');

                const sumStr = totalSum.toLocaleString('en-IN', { minimumFractionDigits: 2 });
                const paidStr = totalPaid.toLocaleString('en-IN', { minimumFractionDigits: 2 });
                const pendStr = totalPending.toLocaleString('en-IN', { minimumFractionDigits: 2 });
                
                const footerHtml = `
                    <tr style="background:#f4f7f6; font-size:0.9rem;">
                        <td colspan="3" style="text-align:right; font-weight:800; padding-right:20px; color:#1f6b4a;">Summary:</td>
                        <td colspan="2" style="line-height:1.6;">
                            <div><span style="color:#5a5047;font-weight:600;display:inline-block;width:100px;">Total Bill:</span> <span class="gp-amt" style="font-size:1rem;">₹${sumStr}</span></div>
                            <div><span style="color:#2e7d32;font-weight:600;display:inline-block;width:100px;">Total Paid:</span> <span class="gp-amt" style="font-size:1rem;color:#2e7d32;">₹${paidStr}</span></div>
                            <div><span style="color:#e65100;font-weight:600;display:inline-block;width:100px;">Total Pending:</span> <span class="gp-amt" style="font-size:1rem;color:#e65100;">₹${pendStr}</span></div>
                        </td>
                    </tr>`;
                
                tbody.innerHTML = rowsHtml + footerHtml;
            } else {
                tbody.innerHTML = `
                    <tr><td colspan="5">
                        <div class="gp-empty">
                            <div class="gp-empty-ico"><i class="fas fa-file-invoice"></i></div>
                            <p>No billing history found for this patient.</p>
                        </div>
                    </td></tr>`;
            }
        })
        .catch(err => {
            console.error("Error loading bills:", err);
            document.querySelector('#billsTable tbody').innerHTML =
                `<tr><td colspan="5" style="text-align:center;color:#e65100;padding:32px;font-weight:600;">Failed to load bills.</td></tr>`;
        });
}

/* ─── LOAD LAB RESULTS ─────────────────────────────────────── */
function loadLabResults() {
    fetch(`/GM_HMS/api/get_patient_lab_results.php?patient_id=${patientId}`)
        .then(res => res.json())
        .then(res => {
            const tbody = document.querySelector('#labTable tbody');
            if (res.success && res.data && res.data.length > 0) {
                const labCountEl = document.getElementById('labCount');
                if (labCountEl) labCountEl.textContent = res.data.length;

                const rowsHtml = res.data.map(lab => {
                    let sc = 'gp-pending';
                    let displayStatus = lab.status || 'Pending';
                    const stStr = displayStatus.toLowerCase();
                    if (stStr.includes('reviewed')) {
                        sc = 'gp-done';
                    } else if (stStr.includes('critical')) {
                        sc = 'gp-cancel';
                    }

                    const lDate = lab.result_date 
                        ? new Date(lab.result_date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
                        : '—';
                    
                    let testName = lab.test_name || '—';
                    try {
                        const parsed = JSON.parse(testName);
                        if (Array.isArray(parsed)) testName = parsed.join(', ');
                    } catch(e) {}
                    
                    let actionHtml = '—';
                    if (lab.report_file) {
                        actionHtml = `<a href="/GM_HMS/${lab.report_file}" target="_blank" class="gp-pill gp-done" style="text-decoration:none;"><i class="fas fa-file-pdf"></i> View Report</a>`;
                    } else {
                        actionHtml = `<a href="/GM_HMS/laboratory_view/print_result.php?order_id=${lab.order_id}&source=${lab.source}" target="_blank" class="gp-pill gp-sched" style="text-decoration:none;"><i class="fas fa-print"></i> Print</a>`;
                    }

                    return `<tr>
                        <td><div class="gp-td-d1">${lDate}</div></td>
                        <td><span class="gp-bid">${lab.order_id}</span></td>
                        <td>${testName}</td>
                        <td><span class="gp-badge" style="color:#5a5047; border: 1.5px solid #ede8dd; background: #faf8f4;">${lab.source}</span></td>
                        <td><span class="gp-pill ${sc}">${displayStatus}</span></td>
                        <td>${actionHtml}</td>
                    </tr>`;
                }).join('');

                tbody.innerHTML = rowsHtml;
            } else {
                tbody.innerHTML = `
                    <tr><td colspan="6">
                        <div class="gp-empty">
                            <div class="gp-empty-ico"><i class="fas fa-flask"></i></div>
                            <p>No lab results found for this patient.</p>
                        </div>
                    </td></tr>`;
            }
        })
        .catch(err => {
            console.error("Error loading lab results:", err);
            document.querySelector('#labTable tbody').innerHTML =
                `<tr><td colspan="6" style="text-align:center;color:#e65100;padding:32px;font-weight:600;">Failed to load lab results.</td></tr>`;
        });
}
