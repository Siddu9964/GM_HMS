/**
 * IPD Patients - JavaScript
 * Handles inpatient list for the specific doctor
 */

let ipdTable = null;

document.addEventListener('DOMContentLoaded', function () {
    loadIpdData();

    // Search filter
    document.getElementById('search-patients').addEventListener('input', function (e) {
        if (ipdTable) {
            ipdTable.search(this.value).draw();
        }
    });
});

async function loadIpdData() {
    try {
        const doctorId = Storage.get('doctor_id');
        if (!doctorId) {
            console.error('No doctor ID found');
            return;
        }

        showLoading('Syncing IPD admissions...');

        const response = await API.get(`doctors/${doctorId}/ipd-patients`);

        if (response.success) {
            const admissions = response.data.admissions;
            updateKpis(admissions);
            populateTable(admissions);
        }

        hideLoading();
    } catch (error) {
        hideLoading();
        console.error('IPD Load Error:', error);
        showToast('Failed to load admissions', 'error');
    }
}

function updateKpis(admissions) {
    document.getElementById('kpi-active').textContent = admissions.filter(a => a.status === 'Admitted').length;
    document.getElementById('kpi-total').textContent = admissions.length;
}

function populateTable(admissions) {
    const tbody = document.getElementById('ipd-table-body');

    if (admissions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5">No active admissions found</td></tr>';
        return;
    }

    const rows = admissions.map(adm => {
        const initials = (adm.patient_name || 'PN').split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
        const financials = adm.financials || { balance_due: 0 };

        return `
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="patient-avatar-mini">${initials}</div>
                        <div>
                            <div style="font-weight: 600;">${adm.patient_name}</div>
                            <div style="font-size: 0.7rem; color: var(--gray-500);">ID: ${adm.patient_id}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="ward-badge">${adm.ward_name}</div>
                    <div style="font-size: 0.8rem; font-weight: 600; margin-top: 4px;">Bed #${adm.bed_number}</div>
                </td>
                <td>
                    <div style="font-weight: 500;">${DateUtils.formatDateReadable(adm.admission_date)}</div>
                </td>
                <td>
                    <span class="days-badge">${adm.days_admitted} Days</span>
                </td>
                <td>
                    <div style="color: #b91c1c; font-weight: 700; font-size: 0.9rem;">₹${(financials.balance_due || 0).toLocaleString()}</div>
                    <div style="font-size: 0.7rem; color: var(--gray-500);">Due of ₹${(financials.total_charges || 0).toLocaleString()}</div>
                </td>
                <td>
                    <div class="d-flex gap-2">
                        <button onclick="PatientDetail.show('${adm.patient_id}')" class="btn btn-sm btn-info" title="View Patient Details">
                            <i class="fas fa-user-circle"></i>
                        </button>
                        <button onclick="viewClinicalNotes('${adm.admission_id}')" class="btn btn-sm btn-primary" title="Clinical Notes">
                            <i class="fas fa-file-medical"></i>
                        </button>
                        <button onclick="viewVitals('${adm.patient_id}')" class="btn btn-sm btn-outline" title="Vitals">
                            <i class="fas fa-heartbeat"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    tbody.innerHTML = rows;

    // Initialize/Re-initialize DataTable
    if ($.fn.DataTable.isDataTable('#ipd-table')) {
        $('#ipd-table').DataTable().destroy();
    }
    ipdTable = $('#ipd-table').DataTable({
        pageLength: 10,
        dom: 'rtp',
        order: [[2, 'desc']]
    });
}

function refreshData() {
    loadIpdData();
}

function viewClinicalNotes(admissionId) {
    showToast('Clinical Notes module coming soon', 'info');
}

function viewVitals(patientId) {
    showToast('Patient vitals dashboard coming soon', 'info');
}
