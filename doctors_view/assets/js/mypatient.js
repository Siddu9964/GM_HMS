/**
 * My Patients - JavaScript
 * Handles patient list, filters, and actions
 */

let patientsTable = null;
let currentView = 'table';
let allPatients = [];

// ============================================================================
// INITIALIZE
// ============================================================================

document.addEventListener('DOMContentLoaded', function () {
    loadPatients();

    // Check for hidden patient ID to view history
    const hiddenId = sessionStorage.getItem('history_patient_id');
    const action = sessionStorage.getItem('history_action');
    if (hiddenId && action === 'view') {
        // Clear it so it doesn't open again on refresh
        sessionStorage.removeItem('history_patient_id');
        sessionStorage.removeItem('history_action');

        // Show the history modal (or profile)
        PatientDetail.show(hiddenId);
    }

    // Real-time search
    const searchInput = document.getElementById('search-patient');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function () {
            applyFilters(false);
        }, 300));
    }
    
    // Prevent form submission on enter for the search input
    const searchForm = document.getElementById('search-filter-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
        });
    }
});

// ============================================================================
// LOAD PATIENTS
// ============================================================================

async function loadPatients() {
    try {
        showLoading('Loading patients...');

        // Use injected session ID if available
        const doctorId = typeof CURRENT_DOCTOR_ID !== 'undefined' ? CURRENT_DOCTOR_ID : Storage.get('doctor_id');

        if (!doctorId) {
            console.error('No doctor ID found');
            hideLoading();
            return;
        }

        // Fetch patients restricted to this doctor
        const response = await API.get(`patients?doctor_id=${doctorId}`);

        if (response.success) {
            // The API returns { data: [...], pagination: {...} }
            allPatients = response.data.data;

            // Update statistics
            updateStatistics(allPatients);

            // Populate table
            populateTable(allPatients);

            // Populate cards
            populateCards(allPatients);
        }

        hideLoading();
    } catch (error) {
        hideLoading();
        console.error('Error loading patients:', error);
        showToast('Failed to load patients', 'error');
    }
}

// ============================================================================
// UPDATE STATISTICS
// ============================================================================

function updateStatistics(patients) {
    if (!patients) return;

    // 1. Calculate Statistics
    const totalCount = patients.length;

    // Updated status counts based on latest appointment status
    const activeCount = patients.filter(p => {
        if (p.latest_appointment_status !== null) {
            return p.latest_appointment_status == '1' || p.latest_appointment_status === 1;
        }
        return p.status === 'Active' || !p.status;
    }).length;

    // Restoration of mock values to prevent ReferenceError (can be linked to real data later)
    const followupCount = Math.floor(totalCount * 0.2);
    const criticalCount = Math.floor(totalCount * 0.05);

    // 2. Update UI
    const elTotal = document.getElementById('stat-total');
    const elActive = document.getElementById('stat-active');
    const elFollowup = document.getElementById('stat-followup');
    const elCritical = document.getElementById('stat-critical');

    if (elTotal) elTotal.textContent = totalCount;
    if (elActive) elActive.textContent = activeCount;
    if (elFollowup) elFollowup.textContent = followupCount;
    if (elCritical) elCritical.textContent = criticalCount;
}

// ============================================================================
// POPULATE TABLE
// ============================================================================

function populateTable(patients) {
    if ($.fn.DataTable.isDataTable('#patients-table')) {
        $('#patients-table').DataTable().destroy();
    }

    const tbody = document.getElementById('patients-table-body');

    if (patients.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; padding: 4rem 2rem; color: var(--gray-400);">
                    <i class="fas fa-user-clock" style="font-size: 3.5rem; opacity: 0.1; margin-bottom: 1.5rem; display: block;"></i>
                    <p style="font-size: 1.1rem; font-weight: 500;">No patients found in your records</p>
                    <p style="font-size: 0.875rem;">Patients will appear here once they take an appointment with you.</p>
                </td>
            </tr>
        `;
        return;
    }

    const rows = patients.map(patient => {
        const age = patient.age || DateUtils.calculateAge(patient.birth_date);
        const gender = patient.sex || '-';
        const lastVisit = patient.date || 'Never';

        // Handle numeric appointment status mapping
        let status = patient.status || 'Active';

        // Check Consultation Status first (Priority)
        // Handles: 0 (int), '0' (string), 'Completed' (legacy string)
        const consultStatus = patient.latest_consultation_status;
        const consultStatusStr = String(consultStatus || '').toLowerCase();

        if (consultStatus !== null && consultStatus !== undefined &&
            (consultStatus == 0 || consultStatusStr === '0' || consultStatusStr === 'completed')) {
            status = 'Completed';
        }
        // Fallback to Appointment Status
        else if (patient.latest_appointment_status !== null) {
            if (patient.latest_appointment_status == '1' || patient.latest_appointment_status === 1) {
                status = 'Active';
            } else if (patient.latest_appointment_status == '0' || patient.latest_appointment_status === 0) {
                status = 'Inactive';
            }
        }

        const fName = (patient.first_name && patient.first_name !== 'null') ? patient.first_name : '';
        const lName = (patient.last_name && patient.last_name !== 'null') ? patient.last_name : '';
        const fullName = `${fName} ${lName}`.trim() || 'Unknown';
        const initials = `${fName.charAt(0) || ''}${lName.charAt(0) || ''}`.toUpperCase() || 'P';

        let badgeBg, badgeColor;
        if (status === 'Active') {
            badgeBg = '#dcfce7'; badgeColor = '#166534';
        } else if (status === 'Completed') {
            badgeBg = '#dbeafe'; badgeColor = '#1e40af'; // Blue for Completed
        } else {
            badgeBg = '#f1f5f9'; badgeColor = '#475569'; // Gray for Inactive/Others
        }

        return `
            <tr style="transition: background 0.2s;">
                <td style="font-weight: 700; color: #1f6b4a;">${patient.patient_id}</td>
                <td>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; box-shadow: 0 4px 6px -1px rgba(31, 107, 74, 0.2);">
                            ${initials}
                        </div>
                        <div>
                            <div style="font-weight: 700; color: #1e293b; font-size: 0.95rem;">${fullName}</div>
                            <div style="font-size: 0.75rem; color: var(--gray-500);"><i class="fas fa-phone-alt" style="font-size: 0.7rem; margin-right: 4px;"></i>${patient.phone || 'No phone'}</div>
                        </div>
                    </div>
                </td>
                <td style="font-weight: 500; color: #475569;">${age} yrs / ${gender}</td>
                <td><span style="background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.75rem;">${patient.blood_group || 'N/A'}</span></td>
                <td>
                    <div style="font-weight: 600; color: #1e293b;">${DateUtils.formatDateReadable(lastVisit)}</div>
                    <div style="font-size: 0.7rem; color: #1f6b4a; font-weight: 700;">LATEST CONSULTATION</div>
                </td>
                <td>
                    <span style="display: inline-flex; align-items: center; gap: 4px; background: ${badgeBg}; color: ${badgeColor}; padding: 4px 12px; border-radius: 50px; font-weight: 700; font-size: 0.75rem; text-transform: uppercase;">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: currentColor;"></span>
                        ${status}
                    </span>
                </td>
                <td>
                    <div style="display: flex; gap: 0.5rem;">
                        <button onclick="PatientDetail.show('${patient.patient_id}')" class="btn btn-sm" style="background: #f1f5f9; color: #475569; border: none;" title="View Profile">
                            <i class="fas fa-user"></i>
                        </button>
                        <button onclick="startConsultation('${patient.patient_id}')" class="btn btn-sm btn-primary" title="Start Consultation">
                            <i class="fas fa-stethoscope"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    tbody.innerHTML = rows;
    
    // Re-initialize DataTable after replacing the content
    initializeDataTable();
}

// ============================================================================
// POPULATE CARDS
// ============================================================================

function populateCards(patients) {
    const container = document.getElementById('cards-view');

    if (patients.length === 0) {
        container.innerHTML = `
            <div style="grid-column: 1 / -1; text-align: center; padding: 4rem 2rem; color: var(--gray-400);">
                <i class="fas fa-id-card-alt" style="font-size: 3.5rem; opacity: 0.1; margin-bottom: 1.5rem; display: block;"></i>
                <p style="font-size: 1.1rem; font-weight: 500;">No patients found in your records</p>
                <p style="font-size: 0.875rem;">Switch to Table View for a detailed list.</p>
            </div>
        `;
        return;
    }

    const cards = patients.map(patient => {
        const age = patient.age || DateUtils.calculateAge(patient.birth_date);
        const fName = (patient.first_name && patient.first_name !== 'null') ? patient.first_name : '';
        const lName = (patient.last_name && patient.last_name !== 'null') ? patient.last_name : '';
        const fullName = `${fName} ${lName}`.trim() || 'Unknown';
        const initials = `${fName.charAt(0) || ''}${lName.charAt(0) || ''}`.toUpperCase() || 'P';
        const lastVisit = patient.date || 'Never';

        return `
            <div class="bento-card col-span-3" style="border: none; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border-radius: 16px; overflow: hidden; transition: transform 0.3s ease; padding: 0;">
                <div style="height: 80px; background: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%); position: relative;">
                    <div style="position: absolute; bottom: -30px; left: 20px; width: 70px; height: 70px; border-radius: 12px; background: white; padding: 4px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                        <div style="width: 100%; height: 100%; border-radius: 8px; background: #f1f5f9; color: #1f6b4a; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.5rem;">
                            ${initials}
                        </div>
                    </div>
                </div>
                <div class="card-body" style="padding: 40px 20px 20px;">
                    <div style="margin-bottom: 1.5rem;">
                        <h3 style="font-size: 1.15rem; font-weight: 700; color: #1e293b; margin-bottom: 0.25rem;">
                            ${fullName}
                        </h3>
                        <p style="color: #1f6b4a; font-size: 0.85rem; font-weight: 700; margin: 0;">${patient.patient_id}</p>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                        <div>
                            <span style="display: block; font-size: 0.7rem; color: #64748b; text-transform: uppercase; font-weight: 700;">Age / Sex</span>
                            <span style="font-weight: 600; color: #334155;">${age}y / ${patient.sex}</span>
                        </div>
                        <div>
                            <span style="display: block; font-size: 0.7rem; color: #64748b; text-transform: uppercase; font-weight: 700;">Blood Group</span>
                            <span style="font-weight: 600; color: #334155;">${patient.blood_group || 'N/A'}</span>
                        </div>
                        <div style="grid-column: 1 / -1;">
                             <span style="display: block; font-size: 0.7rem; color: #64748b; text-transform: uppercase; font-weight: 700;">Latest Consultation</span>
                             <span style="font-weight: 600; color: #334155;">${DateUtils.formatDateReadable(lastVisit)}</span>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <button onclick="PatientDetail.show('${patient.patient_id}')" class="btn" style="background: #f1f5f9; color: #475569; font-weight: 600;">
                            <i class="fas fa-user-circle" style="color: inherit;"></i> Profile
                        </button>
                        <button onclick="startConsultation('${patient.patient_id}')" class="btn btn-primary" style="font-weight: 600;">
                            <i class="fas fa-stethoscope" style="color: inherit;"></i> Consult
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    container.innerHTML = cards;
}

// ============================================================================
// INITIALIZE DATATABLE
// ============================================================================

function initializeDataTable() {
    if ($.fn.DataTable.isDataTable('#patients-table')) {
        $('#patients-table').DataTable().destroy();
    }

    patientsTable = $('#patients-table').DataTable({
        pageLength: 10,
        order: [[0, 'desc']],
        language: {
            search: '',
            searchPlaceholder: 'Search patients...'
        },
        dom: 'rtip' // Hide default search and length since we have custom ones
    });
}

// ============================================================================
// TOGGLE VIEW
// ============================================================================

function toggleView(view) {
    currentView = view;

    const btnTable = document.getElementById('btn-table-view');
    const btnCards = document.getElementById('btn-cards-view');

    if (view === 'table') {
        document.getElementById('table-view').style.display = 'block';
        document.getElementById('cards-view').style.display = 'none';
        
        btnTable.style.background = '#1f6b4a';
        btnTable.style.color = 'white';
        btnTable.style.border = 'none';
        
        btnCards.style.background = 'transparent';
        btnCards.style.color = '#1f6b4a';
        btnCards.style.border = '1px solid rgba(31, 107, 74, 0.2)';
    } else {
        document.getElementById('table-view').style.display = 'none';
        document.getElementById('cards-view').style.display = 'grid';
        
        btnCards.style.background = '#1f6b4a';
        btnCards.style.color = 'white';
        btnCards.style.border = 'none';
        
        btnTable.style.background = 'transparent';
        btnTable.style.color = '#1f6b4a';
        btnTable.style.border = '1px solid rgba(31, 107, 74, 0.2)';
    }
}

// ============================================================================
// APPLY FILTERS
// ============================================================================

function applyFilters(showToastMsg = true) {
    const term = document.getElementById('search-patient') ? document.getElementById('search-patient').value.toLowerCase() : '';
    const statusEl = document.getElementById('filter-status');
    const status = statusEl ? statusEl.value : '';

    let filtered = allPatients;

    if (term) {
        filtered = filtered.filter(p => {
            const fname = p.first_name && p.first_name !== 'null' ? String(p.first_name).toLowerCase() : '';
            const lname = p.last_name && p.last_name !== 'null' ? String(p.last_name).toLowerCase() : '';
            const pid = String(p.patient_id || '').toLowerCase();
            const phone = String(p.phone || '').toLowerCase();
            
            return fname.includes(term) || 
                   lname.includes(term) || 
                   pid.includes(term) || 
                   phone.includes(term);
        });
    }

    if (status) {
        filtered = filtered.filter(p => {
            let currentStatus = p.status || 'Active';

            // Check Consultation Status first (Priority)
            const consultStatus = p.latest_consultation_status;
            const consultStatusStr = String(consultStatus || '').toLowerCase();

            if (consultStatus !== null && consultStatus !== undefined &&
                (consultStatus == 0 || consultStatusStr === '0' || consultStatusStr === 'completed')) {
                currentStatus = 'Completed';
            }
            // Fallback to Appointment Status
            else if (p.latest_appointment_status !== null) {
                if (p.latest_appointment_status == '1' || p.latest_appointment_status === 1) currentStatus = 'Active';
                else if (p.latest_appointment_status == '0' || p.latest_appointment_status === 0) currentStatus = 'Inactive';
            }
            return currentStatus === status;
        });
    }

    populateTable(filtered);
    populateCards(filtered);
    updateStatistics(filtered);

    if (showToastMsg) {
        showToast(`Showing ${filtered.length} patient(s)`, 'info');
    }
}

// ============================================================================
// ============================================================================
// ACTIONS
// ============================================================================

async function performAdvancedSearch() {
    try {
        const filters = {
            patient_id: document.getElementById('adv-patient-id').value.trim(),
            phone: document.getElementById('adv-phone').value.trim(),
            search: document.getElementById('adv-name').value.trim(),
            city: document.getElementById('adv-city').value.trim(),
            gender: document.getElementById('adv-gender').value,
            status: document.querySelector('input[name="adv-status"]:checked').value,
            date_from: document.getElementById('adv-date-from').value,
            date_to: document.getElementById('adv-date-to').value
        };

        // Construct Query Params
        const params = new URLSearchParams();
        const doctorId = typeof CURRENT_DOCTOR_ID !== 'undefined' ? CURRENT_DOCTOR_ID : Storage.get('doctor_id');
        if (doctorId) params.append('doctor_id', doctorId);

        Object.keys(filters).forEach(key => {
            if (filters[key]) params.append(key, filters[key]);
        });

        Modal.hide('advanced-search-modal');
        showLoading('Searching patients...');

        // Call API with filters
        const response = await API.get(`patients?${params.toString()}`);

        if (response.success) {
            allPatients = response.data.data;
            updateStatistics(allPatients);
            populateTable(allPatients);
            populateCards(allPatients);
            showToast(`Found ${allPatients.length} matching patients`, 'success');
        } else {
            showToast('No patients found matching criteria', 'info');
            allPatients = [];
            populateTable([]);
            populateCards([]);
        }

        hideLoading();

    } catch (error) {
        hideLoading();
        console.error('Search error:', error);
        showToast('Search failed', 'error');
    }
}

async function viewPatient(patientId) {
    try {
        showLoading('Loading patient details...');
        const response = await API.get(`patients/${patientId}`);

        if (response.success) {
            const patient = response.data;

            const fName = (patient.first_name && patient.first_name !== 'null') ? patient.first_name : '';
            const lName = (patient.last_name && patient.last_name !== 'null') ? patient.last_name : '';
            document.getElementById('modal-patient-name').textContent = `${fName} ${lName}`.trim() || 'Unknown';

            document.getElementById('modal-patient-details').innerHTML = `
                <div class="d-grid grid-cols-2 gap-3">
                    <div>
                        <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 1rem; color: var(--primary-blue);">
                            <i class="fas fa-user"></i> Personal Information
                        </h3>
                        <table style="width: 100%; font-size: 0.875rem;">
                            <tr><td style="padding: 0.5rem 0;"><strong>Patient ID:</strong></td><td>${patient.patient_id}</td></tr>
                            <tr><td style="padding: 0.5rem 0;"><strong>Age:</strong></td><td>${patient.age || DateUtils.calculateAge(patient.birth_date)} years</td></tr>
                            <tr><td style="padding: 0.5rem 0;"><strong>Gender:</strong></td><td>${patient.sex}</td></tr>
                            <tr><td style="padding: 0.5rem 0;"><strong>Blood Group:</strong></td><td>${patient.blood_group || 'Unknown'}</td></tr>
                            <tr><td style="padding: 0.5rem 0;"><strong>Email:</strong></td><td>${patient.email}</td></tr>
                            <tr><td style="padding: 0.5rem 0;"><strong>Phone:</strong></td><td>${patient.phone || 'N/A'}</td></tr>
                        </table>
                    </div>
                    <div>
                        <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 1rem; color: var(--primary-blue);">
                            <i class="fas fa-map-marker-alt"></i> Address
                        </h3>
                        <p style="font-size: 0.875rem; line-height: 1.6;">
                            ${patient.address || 'No address provided'}<br>
                            ${patient.city ? patient.city + ', ' : ''}${patient.state || ''}<br>
                            ${patient.country || ''} - ${patient.pincode || ''}
                        </p>
                        
                        <div style="margin-top: 2rem; display: grid; gap: 0.75rem;">
                            <button onclick="startConsultationSession('${patient.patient_id}')" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-notes-medical"></i> Start Consultation
                            </button>
                            <button onclick="viewHistory('${patient.patient_id}')" class="btn btn-outline" style="width: 100%;">
                                <i class="fas fa-history"></i> Full History
                            </button>
                        </div>
                    </div>
                </div>
            `;

            Modal.show('patient-modal');
        }

        hideLoading();
    } catch (error) {
        hideLoading();
        showToast('Failed to load patient details', 'error');
    }
}

function startConsultation(patientId) {
    if (typeof startConsultationSession === 'function') {
        startConsultationSession(patientId);
    } else {
        window.location.href = `consultation.php?patient_id=${patientId}`;
    }
}

function viewHistory(patientId) {
    if (typeof viewMedicalHistory === 'function') {
        viewMedicalHistory(patientId);
    } else {
        window.location.href = `patient_history.php?patient_id=${patientId}`;
    }
}

function exportPatients() {
    // Convert to CSV
    const csv = convertToCSV(allPatients);
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `patients_${DateUtils.getToday()}.csv`;
    a.click();

    showToast('Patients exported successfully', 'success');
}

function convertToCSV(data) {
    const headers = ['Patient ID', 'Name', 'Age', 'Gender', 'Blood Group', 'Email', 'Phone'];
    const rows = data.map(p => [
        p.patient_id,
        `${(p.first_name && p.first_name !== 'null') ? p.first_name : ''} ${(p.last_name && p.last_name !== 'null') ? p.last_name : ''}`.trim() || 'Unknown',
        p.age || DateUtils.calculateAge(p.birth_date),
        p.sex,
        p.blood_group || '',
        p.email,
        p.phone || ''
    ]);

    return [headers, ...rows].map(row => row.join(',')).join('\n');
}
