/**
 * Prescription Management Logic
 */

// Initialize
$(document).ready(function () {
    loadPrescriptions();
    loadPatients();

    // Add Row Button
    $('#add-medicine-row').click(function () {
        addMedicineRow();
    });

    // Form Submission
    $('#add-prescription-form').submit(function (e) {
        e.preventDefault();
        savePrescription();
    });
});

// Load Prescriptions
async function loadPrescriptions() {
    const doctorId = Storage.get('doctor_id');
    if (!doctorId) {
        console.error('No doctor ID found');
        return;
    }

    showLoading('Loading prescriptions...');

    try {
        const response = await API.get(`prescriptions/doctor/${doctorId}`);

        if (response.success) {
            renderPrescriptionsTable(response.data.prescriptions);
        } else {
            showToast(response.error || 'Failed to load prescriptions', 'error');
        }
    } catch (error) {
        showToast('Error connecting to server', 'error');
        console.error(error);
    } finally {
        hideLoading();
    }
}

// Render Table
function renderPrescriptionsTable(prescriptions) {
    const tableBody = $('#prescriptions-table-body');
    tableBody.empty();

    if (prescriptions.length === 0) {
        tableBody.append(`
            <tr>
                <td colspan="7" class="text-center p-4">
                    <div class="text-muted">No prescriptions found</div>
                </td>
            </tr>
        `);
        return;
    }

    prescriptions.forEach(p => {
        let medicinesHtml = '';
        const row = `
            <tr>
                <td><span class="font-weight-bold text-primary">${p.prescription_id}</span></td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-circle mr-2 bg-light text-primary">
                            ${(p.first_name || '').charAt(0)}${(p.last_name || '').charAt(0) || 'P'}
                        </div>
                        <div>
                            <div class="font-weight-bold">${p.first_name} ${p.last_name}</div>
                            <small class="text-muted">ID: ${p.patient_id}</small>
                        </div>
                    </div>
                </td>
                <td>${p.prescription_date}</td>
                <td class="text-right">
                    <button onclick="openNotebookView('${p.prescription_id}', '${p.patient_id}')" 
                            class="btn btn-sm btn-info shadow-sm" title="Open Notebook View">
                        <i class="fas fa-book-medical mr-1"></i> View Details
                    </button>
                </td>
            </tr>
        `;
        tableBody.append(row);
    });

    // Initialize DataTable if needed (optional)
    if ($.fn.DataTable.isDataTable('#prescriptions-table')) {
        $('#prescriptions-table').DataTable().destroy();
    }
    $('#prescriptions-table').DataTable({
        "order": [[2, "desc"]], // Sort by date
        "pageLength": 10
    });
}

// Add Medicine Row
function addMedicineRow() {
    const rowId = Date.now();
    const row = `
        <div class="medicine-row" id="row-${rowId}">
            <div class="row align-items-center">
                <div class="col-md-3 mb-2 mb-md-0">
                    <input type="text" class="form-control med-name" placeholder="Medicine Name" required>
                </div>
                <div class="col-md-2 mb-2 mb-md-0">
                    <input type="text" class="form-control med-dosage" placeholder="Dosage (e.g. 500mg)" required>
                </div>
                <div class="col-md-2 mb-2 mb-md-0">
                    <input type="text" class="form-control med-freq" placeholder="Freq (1-0-1)" required>
                </div>
                <div class="col-md-2 mb-2 mb-md-0">
                    <input type="text" class="form-control med-dur" placeholder="Duration (5 Days)" required>
                </div>
                <div class="col-md-2 mb-2 mb-md-0">
                    <input type="text" class="form-control med-instr" placeholder="Instructions">
                </div>
                <div class="col-md-1 text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="$('#row-${rowId}').remove()" style="border-radius: 6px; padding: 6px 12px; border-width: 1px;" title="Remove Medicine">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    $('#medicine-rows-container').append(row);
}

// Save Prescription
async function savePrescription() {
    const patientId = $('#patient_select').val();
    const diagnosis = $('#diagnosis').val();
    const notes = $('#general_instructions').val();

    const dietaryAdvice = $('#dietary_advice').val();
    const followUpDate = $('#follow_up_date').val();

    // Collect Medicines
    const medicines = [];
    $('.medicine-row').each(function () {
        medicines.push({
            name: $(this).find('.med-name').val(),
            dosage: $(this).find('.med-dosage').val(),
            frequency: $(this).find('.med-freq').val(),
            duration: $(this).find('.med-dur').val(),
            instructions: $(this).find('.med-instr').val()
        });
    });

    if (medicines.length === 0) {
        showToast('Please add at least one medicine', 'warning');
        return;
    }

    const data = {
        patient_id: patientId,
        doctor_id: Storage.get('doctor_id'),
        diagnosis: diagnosis,
        general_instructions: notes,
        dietary_advice: dietaryAdvice,
        follow_up_date: followUpDate,
        medicines: medicines
    };

    showLoading('Saving prescription...');

    try {
        const response = await API.post('prescriptions', data);

        if (response.success) {
            showToast('Prescription saved successfully', 'success');
            $('#addPrescriptionModal').modal('hide');
            document.getElementById('add-prescription-form').reset();
            $('#medicine-rows-container').empty();
            addMedicineRow(); // Reset with one row
            loadPrescriptions();
        } else {
            showToast(response.error || 'Failed to save', 'error');
        }
    } catch (error) {
        showToast('Error saving prescription', 'error');
        console.error(error);
    } finally {
        hideLoading();
    }
}

// Load Patients for Dropdown
async function loadPatients() {
    // Reusing existing API or create a simple list endpoint
    // For now, let's assume we can search or load recent
    try {
        const response = await API.get('patients'); // Assuming this exists or we use search
        if (response.success) {
            const select = $('#patient_select');
            response.data.data.forEach(p => {
                select.append(new Option(`${p.first_name} ${p.last_name} (${p.patient_id})`, p.patient_id));
            });
        }
    } catch (e) {
        console.error("Failed to load patients for dropdown", e);
    }
}

// Notebook View Logic

async function openNotebookView(prescriptionId, patientId) {
    $('#notebook-view').css('display', 'flex');

    // Load Timeline
    await loadPatientHistory(patientId, prescriptionId);

    // Load Content
    loadNotebookPage(prescriptionId);
}

function closeNotebookView() {
    $('#notebook-view').fadeOut();
}

async function loadPatientHistory(patientId, currentIds) {
    const container = $('#notebook-timeline');
    container.html('<div class="text-center text-muted mt-3">Loading history...</div>');

    try {
        const response = await API.get(`prescriptions/patient/${patientId}`);
        if (response.success) {
            container.empty();
            const history = response.data.history;
            currentHistory = history; // Store for navigation

            if (history.length === 0) {
                container.html('<div class="p-2 text-muted">No history found</div>');
                return;
            }

            history.forEach(h => {
                const activeClass = (h.prescription_id === currentIds) ? 'active' : '';
                const item = `
                    <div class="timeline-item ${activeClass}" onclick="loadNotebookPage('${h.prescription_id}')">
                        <span class="timeline-date">${h.prescription_date}</span>
                        <span class="timeline-desc">${h.diagnosis || 'No Diagnosis'}</span>
                    </div>
                `;
                container.append(item);
            });
        }
    } catch (e) {
        container.html('<div class="text-danger p-2">Failed to load history</div>');
        console.error(e);
    }
}

async function loadNotebookPage(id) {
    const containerLeft = $('#book-left');
    const containerRight = $('#book-right');

    // Loading State
    containerLeft.html('');
    containerRight.html('<div class="text-center mt-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');

    // Highlight active timeline item
    $('.timeline-item').removeClass('active');

    try {
        const response = await API.get(`prescriptions/${id}`);
        if (response.success) {
            const p = response.data;

            // Format Medicines
            let medicinesTable = '';
            if (Array.isArray(p.medicines) && p.medicines.length > 0) {
                if (p.medicines[0].instructions === 'See Plan') {
                    medicinesTable = `<div class="p-3 bg-light border rounded" style="white-space: pre-wrap;">${p.medicines[0].name}</div>`;
                } else {
                    const rows = p.medicines.map(m => `
                        <tr>
                            <td style="font-weight: bold;">${m.name}</td>
                            <td>${m.dosage}</td>
                            <td>${m.frequency}</td>
                            <td>${m.duration}</td>
                            <td>${m.instructions || ''}</td>
                        </tr>
                    `).join('');

                    medicinesTable = `
                        <table class="table table-borderless table-sm mt-2">
                            <thead style="border-bottom: 2px solid #eee;">
                                <tr>
                                    <th>MEDICINE</th><th>DOSAGE</th><th>FREQ</th><th>DUR</th><th>INSTR</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>
                    `;
                }
            } else {
                medicinesTable = '<div class="text-muted font-italic">No medicines prescribed.</div>';
            }

            // === LEFT PAGE CONTENT (Cover) ===
            const leftContent = `
                <div class="h-100 d-flex flex-column justify-content-between p-4" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-right: 1px solid #dee2e6;">
                    <div class="text-right">
                        <span class="badge badge-danger text-uppercase letter-spacing-1 p-2">Confidentia Medical Record</span>
                    </div>
                    
                    <div class="text-center">
                        <div class="mb-4">
                            <i class="fas fa-hospital-alt fa-3x text-secondary opacity-50"></i>
                        </div>
                        <h1 class="font-weight-bold text-dark mb-0" style="font-family: 'Playfair Display', serif; letter-spacing: 1px;">MEDICAL<br>REPORT</h1>
                        <div class="mt-3 text-muted text-uppercase small letter-spacing-2">${moment(p.prescription_date).format('MMMM DD, YYYY')}</div>
                    </div>

                    <div class="text-center opacity-50">
                        <small>GM Hospital System</small>
                    </div>
                </div>
            `;
            containerLeft.html(leftContent);

            // === RIGHT PAGE CONTENT (Details) ===
            const rightContent = `
                <div class="p-4 h-100 d-flex flex-column bg-white">
                    <!-- Letterhead Header -->
                    <div class="border-bottom pb-4 mb-4">
                        <div class="row">
                            <div class="col-7 border-right">
                                <h5 class="text-primary font-weight-bold mb-1">${p.doctor_name || 'Dr. Unknown'}</h5>
                                <div class="text-muted small text-uppercase mb-2">${p.specialization || 'General Physician'}</div>
                                <div class="small text-secondary"><i class="fas fa-stethoscope mr-1"></i> Consultant Physician</div>
                            </div>
                            <div class="col-5 pl-4">
                                <small class="text-muted text-uppercase d-block mb-1">Patient Details</small>
                                <h6 class="font-weight-bold mb-0 text-dark">${p.first_name} ${p.last_name}</h6>
                                <div class="small text-muted mt-1">
                                    ID: ${p.patient_id} <span class="mx-1">|</span> 
                                    Age: ${p.age} <span class="mx-1">|</span> 
                                    Sex: ${p.gender || '-'}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Clinical Section -->
                    <div class="flex-grow-1">
                        <div class="mb-4">
                            <h6 class="text-uppercase text-secondary small font-weight-bold letter-spacing-1 mb-3">
                                <i class="fas fa-clipboard-check mr-2"></i>Clinical Assessment
                            </h6>
                            <div class="p-3 rounded" style="background-color: #f8fbff; border-left: 4px solid #007bff;">
                                <div class="row">
                                    <div class="col-md-12 mb-2">
                                        <span class="text-muted small text-uppercase">Diagnosis</span>
                                        <div class="font-weight-bold text-dark">${p.diagnosis || 'Not recorded'}</div>
                                    </div>
                                    <div class="col-md-12">
                                        <span class="text-muted small text-uppercase">Notes & Instructions</span>
                                        <div class="text-dark" style="white-space: pre-line;">${p.general_instructions || 'No specific instructions.'}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Rx Section -->
                        <div class="mb-4">
                            <h6 class="text-uppercase text-secondary small font-weight-bold letter-spacing-1 mb-3">
                                <i class="fas fa-pills mr-2"></i>Prescription (Rx)
                            </h6>
                            ${medicinesTable}
                        </div>
                    </div>

                    <!-- Footer / Signature -->
                    <div class="mt-auto pt-4 border-top">
                        <div class="row align-items-end">
                            <div class="col-6">
                                ${p.follow_up_date ? `
                                    <div class="p-2 border rounded d-inline-block bg-light">
                                        <small class="text-muted d-block text-uppercase">Next Follow-up</small>
                                        <strong class="text-primary"><i class="far fa-calendar-alt mr-1"></i> ${moment(p.follow_up_date).format('MMM DD, YYYY')}</strong>
                                    </div>
                                ` : ''}
                            </div>
                            <div class="col-6 text-right">
                                <div style="display: inline-block; text-align: center;">
                                    <div class="mb-1" style="font-family: 'Herr Von Muellerhoff', cursive; font-size: 24px; color: #007bff;">Dr. ${p.doctor_name ? p.doctor_name.split(' ').pop() : 'Signature'}</div>
                                    <div class="border-top" style="width: 150px; border-color: #333 !important;"></div>
                                    <small class="text-muted">Doctor's Signature</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex justify-content-center align-items-center mt-4 no-print">
                        <button class="btn btn-light shadow-sm btn-sm mr-2" onclick="navigateNotebook(-1)"><i class="fas fa-chevron-left"></i></button>
                        <button class="btn btn-dark shadow px-4" onclick="window.print()"><i class="fas fa-print mr-2"></i> Print Medical File</button>
                        <button class="btn btn-light shadow-sm btn-sm ml-2" onclick="navigateNotebook(1)"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
            `;
            containerRight.html(rightContent);

        }
    } catch (e) {
        containerRight.html('<div class="text-danger text-center">Failed to load prescription details</div>');
        console.error(e);
    }
}

// Navigation Logic
let currentHistory = []; // Store history globally for navigation

function navigateNotebook(direction) {
    // Find current index
    const currentId = $('.timeline-item.active').attr('onclick').match(/'([^']+)'/)[1];
    if (!currentHistory || currentHistory.length === 0) return;

    const currentIndex = currentHistory.findIndex(h => h.prescription_id === currentId);
    if (currentIndex === -1) return;

    const newIndex = currentIndex + direction;

    // Check bounds
    if (newIndex >= 0 && newIndex < currentHistory.length) {
        const nextId = currentHistory[newIndex].prescription_id;
        loadNotebookPage(nextId);

        // Update active class in timeline
        $('.timeline-item').removeClass('active');
        // Find the element with the correct onclick attribute
        $(`.timeline-item[onclick*='${nextId}']`).addClass('active');

        // Scroll timeline to keep active item in view
        const activeItem = $(`.timeline-item.active`);
        if (activeItem.length) {
            activeItem[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    } else {
        showToast('No more records in this direction', 'info');
    }
}
