/**
 * OPD Patients - JavaScript
 * Handles outpatient list for the specific doctor
 */

let opdTable = null;

document.addEventListener('DOMContentLoaded', function () {
    loadOpdData();

    // Search filter
    document.getElementById('search-patients').addEventListener('input', function (e) {
        if (opdTable) {
            opdTable.search(this.value).draw();
        }
    });
});

async function loadOpdData() {
    try {
        // Use injected session ID or storage
        const doctorId = typeof CURRENT_DOCTOR_ID !== 'undefined' ? CURRENT_DOCTOR_ID : Storage.get('doctor_id');
        if (!doctorId) {
            console.error('No doctor ID found');
            return;
        }
        const status = document.getElementById('status-filter').value;

        showLoading('Loading OPD patients...');

        const response = await API.get(`doctors/${doctorId}/opd-patients?status=${status || ''}`);

        if (response.success) {
            const appointments = response.data.appointments;
            updateKpis(appointments);
            populateTable(appointments);
        }

        hideLoading();
    } catch (error) {
        hideLoading();
        console.error('OPD Load Error:', error);
        showToast('Failed to load appointments', 'error');
    }
}

function updateKpis(appointments) {
    if (!appointments || !Array.isArray(appointments)) {
        appointments = [];
    }

    document.getElementById('kpi-scheduled').textContent = appointments.filter(a => {
        const val = a.appointment_status !== undefined ? a.appointment_status : a.status;
        return val == '1' || val === 'Scheduled';
    }).length;
    document.getElementById('kpi-completed').textContent = appointments.filter(a => {
        const val = a.appointment_status !== undefined ? a.appointment_status : a.status;
        return val == '0' || val === 'Completed';
    }).length;
    document.getElementById('kpi-cancelled').textContent = appointments.filter(a => {
        const val = a.appointment_status !== undefined ? a.appointment_status : a.status;
        return val === 'Cancelled' || val == '2'; // Assuming 2 might be cancelled
    }).length;
}

function populateTable(appointments) {
    const tbody = document.getElementById('opd-table-body');

    if (appointments.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5">No appointments found</td></tr>';
        return;
    }

    const rows = appointments.map(apt => {
        // Safely handle status (1 = Scheduled, 0 = Completed)
        let rawStatus = apt.appointment_status !== undefined ? apt.appointment_status : apt.status;
        let status = 'Scheduled';
        if (rawStatus == '0') {
            status = 'Completed';
        } else if (rawStatus == 'Scheduled' || rawStatus == '1') {
            status = 'Scheduled';
        } else if (rawStatus == 'Cancelled') {
            status = 'Cancelled';
        }

        const statusClass = status.toLowerCase();

        // Safely handle patient name
        const patientName = apt.patient_name || 'Unknown Patient';
        const initials = patientName.split(' ').map(n => n[0] || '').join('').toUpperCase().substring(0, 2) || 'PN';

        // Safely handle patient details
        const patientGender = apt.patient_gender || 'N/A';
        const patientAge = apt.patient_age || 0;
        const patientId = apt.patient_id || '';
        const appointmentDate = apt.appointment_date || '';
        const appointmentTime = apt.appointment_time || '';
        const reason = apt.reason || 'Consultation';

        return `
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="patient-avatar-mini">${initials}</div>
                        <div>
                            <div style="font-weight: 600;">${patientName}</div>
                            <div style="font-size: 0.7rem; color: var(--gray-500);">${patientGender}, ${patientAge} yrs</div>
                        </div>
                    </div>
                </td>
                <td><code style="font-size: 0.8rem;">${patientId || 'N/A'}</code></td>
                <td>
                    <div style="font-weight: 500;">${appointmentDate ? (typeof DateUtils !== 'undefined' && DateUtils.formatDateReadable ? DateUtils.formatDateReadable(appointmentDate) : appointmentDate) : 'N/A'}</div>
                    <div style="font-size: 0.75rem; color: var(--gray-500);">${appointmentTime || ''}</div>
                </td>
                <td><span style="font-style: italic; font-size: 0.875rem;">${reason}</span></td>
                <td><span class="opd-badge badge-${statusClass}">${status}</span></td>
                <td>
                    <div class="d-flex gap-2">
                        ${patientId ? `<button onclick="typeof PatientDetail !== 'undefined' && PatientDetail.show ? PatientDetail.show('${patientId}') : alert('Patient ID: ${patientId}')" class="btn btn-sm btn-info" title="View Patient Details">
                            <i class="fas fa-user-circle"></i>
                        </button>` : ''}
                        ${patientId ? `<button onclick="startConsultation('${patientId}', '${apt.appointment_id}')" class="btn btn-sm btn-primary" title="Start Consultation">
                            <i class="fas fa-notes-medical"></i>
                        </button>` : ''}
                        ${patientId ? `<button onclick="viewHistory('${patientId}')" class="btn btn-sm btn-outline" title="History">
                            <i class="fas fa-history"></i>
                        </button>` : ''}
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    tbody.innerHTML = rows;

    // Initialize/Re-initialize DataTable
    if ($.fn.DataTable.isDataTable('#opd-table')) {
        $('#opd-table').DataTable().destroy();
    }
    opdTable = $('#opd-table').DataTable({
        pageLength: 10,
        dom: 'rtp',
        order: [[2, 'desc']]
    });
}

function applyFilters() {
    loadOpdData();
}

function refreshData() {
    loadOpdData();
}

function startConsultation(patientId, appointmentId = null) {
    if (appointmentId) {
        sessionStorage.setItem('consultation_appointment_id', appointmentId);
    }

    if (typeof startConsultationSession === 'function') {
        startConsultationSession(patientId);
    } else {
        // Fallback
        window.location.href = `consultation.php?patient_id=${patientId}${appointmentId ? '&appointment_id=' + appointmentId : ''}`;
    }
}

function viewHistory(patientId) {
    if (typeof viewMedicalHistory === 'function') {
        viewMedicalHistory(patientId);
    } else {
        window.location.href = `mypatient.php?patient_id=${patientId}&action=view`;
    }
}
