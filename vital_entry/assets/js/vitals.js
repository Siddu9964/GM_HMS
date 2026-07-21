/**
 * vitals.js - Vanilla JS for Vitals Entry Module
 */

document.addEventListener('DOMContentLoaded', () => {
    // Elements
    const patientTableBody = document.getElementById('patientTableBody');
    const searchInput = document.getElementById('searchInput');
    const vitalsModal = document.getElementById('vitalsModal');
    const vitalsForm = document.getElementById('vitalsForm');
    const closeBtn = document.querySelector('.close-btn');
    const cancelBtn = document.getElementById('cancelBtn');
    const saveBtn = document.getElementById('saveBtn');
    const bpInput = document.getElementById('bp');
    const bpSys = document.getElementById('bp_sys');
    const bpDia = document.getElementById('bp_dia');
    
    // Toast Container
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container';
        document.body.appendChild(toastContainer);
    }

    let allPatients = [];
    let currentBranch = null;

    // Initially show a message to select branch
    patientTableBody.innerHTML = '<tr><td colspan="8" style="text-align:center">Please select a branch to view patients.</td></tr>';

    // Branch selection
    const branchBtns = document.querySelectorAll('.branch-btn');
    branchBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            // Update active styling
            branchBtns.forEach(b => {
                b.classList.remove('btn-primary', 'active');
                b.classList.add('btn-secondary');
                b.style.backgroundColor = 'var(--secondary-color)';
                b.style.color = 'var(--primary-color)';
            });
            const clickedBtn = e.target;
            clickedBtn.classList.remove('btn-secondary');
            clickedBtn.classList.add('btn-primary', 'active');
            clickedBtn.style.backgroundColor = 'var(--primary-color)';
            clickedBtn.style.color = 'var(--secondary-color)';
            
            // Set current branch and reload
            currentBranch = clickedBtn.getAttribute('data-branch');
            loadPatients();
        });
    });

    // Removed initial loadPatients() call

    // Event Listeners
    searchInput.addEventListener('input', (e) => filterPatients(e.target.value));
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    vitalsForm.addEventListener('submit', saveVitals);
    
    // Close modal on outside click
    window.addEventListener('click', (e) => {
        if (e.target === vitalsModal) {
            closeModal();
        }
    });

    /**
     * Fetch all patients
     */
    async function loadPatients() {
        showTableSkeleton();
        try {
            const response = await fetch('../api/get_patients.php', {
                headers: {
                    'X-Hospital-Branch': currentBranch
                }
            });
            const result = await response.json();
            
            if (result.status) {
                allPatients = result.data;
                renderPatientTable(allPatients);
            } else {
                showToast('Error loading patients: ' + result.message, 'error');
            }
        } catch (error) {
            showToast('Network error while loading patients.', 'error');
            console.error('Error fetching patients:', error);
        }
    }

    /**
     * Render patient table
     */
    function renderPatientTable(patients) {
        patientTableBody.innerHTML = '';
        
        if (patients.length === 0) {
            patientTableBody.innerHTML = '<tr><td colspan="8" style="text-align:center">No patients found.</td></tr>';
            return;
        }

        patients.forEach(p => {
            const tr = document.createElement('tr');
            
            // Format status class based on integer values
            let statusClass = 'status-default';
            let statusText = 'Unknown';
            if (p.status !== undefined && p.status !== null) {
                // Assuming 0: Pending, 1: Completed, 2: Cancelled (based on standard HMS practices)
                if (p.status == 0) {
                    statusClass = 'status-pending';
                    statusText = 'Pending';
                } else if (p.status == 1) {
                    statusClass = 'status-completed';
                    statusText = 'Completed';
                } else if (p.status == 2) {
                    statusClass = 'status-cancelled';
                    statusText = 'Cancelled';
                } else {
                    statusText = 'Status ' + p.status;
                }
            }

            // Create row HTML
            tr.innerHTML = `
                <td>${p.appointment_id || '-'}</td>
                <td><a href="#" class="open-vitals" data-id="${p.appointment_id}">${p.patient_id || '-'}</a></td>
                <td><strong>${p.patient_name || '-'}</strong></td>
                <td>${p.doctor_id || '-'}</td>
                <td>${p.appointment_date || '-'}</td>
                <td>${p.appointment_time || '-'}</td>
                <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                <td>
                    <button type="button" class="btn btn-primary btn-sm open-vitals" data-id="${p.appointment_id}">
                        Vitals Entry
                    </button>
                </td>
            `;
            patientTableBody.appendChild(tr);
        });

        // Add event listeners to new buttons
        document.querySelectorAll('.open-vitals').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const aptId = e.currentTarget.getAttribute('data-id');
                const patient = patients.find(p => p.appointment_id == aptId);
                if (patient) openModal(patient);
            });
        });
    }

    /**
     * Filter patients in table
     */
    function filterPatients(term) {
        term = term.toLowerCase().trim();
        if (!term) {
            renderPatientTable(allPatients);
            return;
        }
        
        const filtered = allPatients.filter(p => {
            return (p.patient_name && p.patient_name.toLowerCase().includes(term)) ||
                   (p.patient_id && p.patient_id.toLowerCase().includes(term)) ||
                   (p.appointment_id && p.appointment_id.toLowerCase().includes(term)) ||
                   (p.doctor_id && p.doctor_id.toLowerCase().includes(term));
        });
        renderPatientTable(filtered);
    }

    /**
     * Show skeleton loader in table
     */
    function showTableSkeleton() {
        patientTableBody.innerHTML = '';
        for (let i = 0; i < 5; i++) {
            patientTableBody.innerHTML += `
                <tr class="skeleton-row">
                    <td><div class="skeleton skeleton-text"></div></td>
                    <td><div class="skeleton skeleton-text"></div></td>
                    <td><div class="skeleton skeleton-text"></div></td>
                    <td><div class="skeleton skeleton-text"></div></td>
                    <td><div class="skeleton skeleton-text"></div></td>
                    <td><div class="skeleton skeleton-text"></div></td>
                    <td><div class="skeleton skeleton-text"></div></td>
                    <td><div class="skeleton skeleton-text"></div></td>
                </tr>
            `;
        }
    }

    /**
     * Open Modal and fetch existing vitals
     */
    async function openModal(patient) {
        // Reset form
        vitalsForm.reset();
        
        // Set Patient Info Banner
        document.getElementById('displayPatientName').textContent = patient.patient_name || '-';
        document.getElementById('displayPatientId').textContent = patient.patient_id || '-';
        document.getElementById('displayDoctorId').textContent = patient.doctor_id || '-';
        document.getElementById('displayAppointmentDate').textContent = `${patient.appointment_date || ''} ${patient.appointment_time || ''}`;
        
        // Hidden inputs
        document.getElementById('appointmentId').value = patient.appointment_id;
        document.getElementById('patientId').value = patient.patient_id;
        document.getElementById('doctorId').value = patient.doctor_id;

        // Show modal
        vitalsModal.classList.add('active');

        // Fetch existing vitals
        try {
            const response = await fetch(`../api/get_vitals.php?appointment_id=${patient.appointment_id}`, {
                headers: {
                    'X-Hospital-Branch': currentBranch
                }
            });
            const result = await response.json();
            
            if (result.status && result.vitals) {
                // Populate existing vitals
                if (result.vitals.bp) {
                    const parts = result.vitals.bp.split('/');
                    if (parts.length === 2) {
                        bpSys.value = parts[0];
                        bpDia.value = parts[1];
                        bpInput.value = result.vitals.bp;
                    } else {
                        bpInput.value = result.vitals.bp;
                    }
                }
                document.getElementById('pulse').value = result.vitals.pulse || '';
                if(result.vitals.temperature) document.getElementById('temperature').value = result.vitals.temperature;
                if(result.vitals.spo2) document.getElementById('spo2').value = result.vitals.spo2;
                if(result.vitals.weight) document.getElementById('weight').value = result.vitals.weight;
                if(result.vitals.height) document.getElementById('height').value = result.vitals.height;
            }
        } catch (error) {
            console.error('Error fetching existing vitals:', error);
            showToast('Could not load existing vitals.', 'warning');
        }
    }

    /**
     * Close modal
     */
    function closeModal() {
        vitalsModal.classList.remove('active');
    }

    /**
     * Save Vitals
     */
    async function saveVitals(e) {
        e.preventDefault();
        
        // Simple validation check (HTML5 does most of it)
        if (!vitalsForm.checkValidity()) {
            vitalsForm.reportValidity();
            return;
        }

        // Change button state
        const originalBtnHtml = saveBtn.innerHTML;
        saveBtn.innerHTML = '<div class="spinner"></div> Saving...';
        saveBtn.disabled = true;

        // Collect data
        // Combine BP
        if (bpSys.value || bpDia.value) {
            bpInput.value = `${bpSys.value}/${bpDia.value}`;
        } else {
            bpInput.value = '';
        }

        const formData = new FormData(vitalsForm);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('../api/update_vitals.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Hospital-Branch': currentBranch
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.status) {
                showToast(result.message, 'success');
                closeModal();
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            console.error('Error saving vitals:', error);
            showToast('Network error while saving vitals.', 'error');
        } finally {
            // Restore button
            saveBtn.innerHTML = originalBtnHtml;
            saveBtn.disabled = false;
        }
    }

    /**
     * Show Toast Notification
     */
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        let icon = '';
        if (type === 'success') icon = '✅';
        else if (type === 'error') icon = '❌';
        else if (type === 'warning') icon = '⚠️';

        toast.innerHTML = `<span>${icon}</span> <div>${message}</div>`;
        toastContainer.appendChild(toast);
        
        // Trigger reflow to animate
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        // Remove after 3s
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 400);
        }, 3000);
    }
});
