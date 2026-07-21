/**
 * Appointment Manager
 * Handles appointment operations, Select2 integration, and strict availability logic
 */
class AppointmentManager {
    constructor() {
        this.apiBase = '/GM_HMS/api/appointments';
        this.patientApiBase = '/GM_HMS/api/patients';
        this.currentView = 'list';
        this.filters = {
            status: '',
            date_from: '',
            date_to: '',
            doctor_id: ''
        };
        this.appointments = [];
    }

    init() {
        this.loadAppointments();
        this.loadDepartments();
        this.loadAllDoctors();
        this.loadFilterDoctors();
        this.initPatientSearch();
        this.init12HourTimePicker();
        this.attachEventListeners();
        this.initializeDatePicker();

        // Handle deep-linking from patient list
        this.checkUrlParams();

        console.log('AppointmentManager initialized');
    }

    /**
     * Check URL parameters for auto-booking actions
     */
    checkUrlParams() {
        const urlParams = new URLSearchParams(window.location.search);
        const patientId = urlParams.get('patient_id');
        const action = urlParams.get('action');

        if (patientId && action === 'new') {
            console.log('Auto-opening booking modal for patient:', patientId);

            // Allow dynamic loading to complete, then trigger
            setTimeout(() => {
                this.openModal('create');

                // Fetch basic name info via API to display in Select2
                this.apiCall('GET', `/${patientId}`, null, this.patientApiBase).then(response => {
                    if (response.success && response.data) {
                        const p = response.data;
                        const patientOption = new Option(`${p.patient_id} - ${p.first_name} ${p.last_name}`, p.patient_id, true, true);
                        $('#patientSelect').append(patientOption).trigger('change');

                        this.showToast(`Booking for: ${p.first_name} ${p.last_name}`, 'info');
                    }
                });

                // Clean URL after handling
                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
            }, 800);
        }
    }

    // --- 1. PATIENT SEARCH (Select2) ---
    // --- 1. PATIENT SEARCH (Select2) ---
    initPatientSearch() {
        $('#patientSelect').select2({
            dropdownParent: $('#appointmentModal'),
            placeholder: 'Search for a patient...',
            allowClear: true,
            minimumInputLength: 1, // Require at least 1 character
            ajax: {
                url: this.patientApiBase,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        search: params.term,
                        limit: 10
                    };
                },
                processResults: function (data) {
                    // API returns { success: true, data: { data: [...], pagination: {...} } }
                    // So we need to access data.data.data for the array
                    const patients = (data.success && data.data && data.data.data) ? data.data.data : [];

                    return {
                        results: patients.map(p => ({
                            id: p.patient_id,
                            text: `${p.patient_id} - ${p.first_name} ${p.last_name} (${p.phone})`,
                            phone: p.phone
                        }))
                    };
                },
                cache: true
            }
        }).on('select2:select', function (e) {
            const data = e.params.data;
            if (data && data.phone) {
                $('#patientPhone').val(data.phone);
            }
        });
    }

    // --- 2. DEPARTMENT & DOCTOR CASCADE ---
    async loadDepartments() {
        try {
            const response = await this.apiCall('GET', '/departments');
            if (response.success) {
                const select = document.getElementById('departmentSelect');
                if (select) {
                    select.innerHTML = '<option value="">Select Department</option>' +
                        response.data.map(d => `<option value="${d.department_id}">${d.department_name}</option>`).join('');
                }
            }
        } catch (error) {
            console.error('Error loading departments:', error);
            this.showToast('Failed to load departments', 'error');
        }
    }

    async loadFilterDoctors() {
        try {
            // Use the top-level doctors API instead of the appointments-sub-API
            const response = await this.apiCall('GET', '', null, '/GM_HMS/api/doctors');
            if (response.success && response.data) {
                const doctors = response.data.data || response.data; // Handle potential nested structure
                const filter = document.getElementById('doctorFilter');
                if (filter && Array.isArray(doctors)) {
                    filter.innerHTML = '<option value="">Filter By Doctor</option>' +
                        doctors.map(doc => `<option value="${doc.full_name}">${doc.full_name}</option>`).join('');
                }
            }
        } catch (error) {
            console.error('Error loading filter doctors:', error);
        }
    }

    async loadAllDoctors() {
        const doctorSelect = document.getElementById('doctorSelect');
        if (!doctorSelect) return;

        doctorSelect.innerHTML = '<option value="">Loading...</option>';
        doctorSelect.disabled = true;
        this.resetAvailabilityStatus();

        try {
            const response = await this.apiCall('GET', '', null, '/GM_HMS/api/doctors');
            if (response.success && response.data) {
                const doctors = response.data.data || response.data;
                if (doctors.length > 0) {
                    doctorSelect.innerHTML = '<option value="">Select Doctor</option>' +
                        doctors.map(doc => {
                            const dept = String(doc.department_id || doc.department || doc.specialization || '').replace(/"/g, '&quot;');
                            const name = String(doc.full_name || doc.name || '').replace(/"/g, '&quot;');
                            return `
                                <option value="${doc.doctor_id || doc.id || ''}" 
                                        data-department="${dept}"
                                        data-days="${doc.available_days || ''}" 
                                        data-in="${doc.in_time || ''}" 
                                        data-out="${doc.out_time || ''}"
                                        data-fee="${doc.consultation_fee || 0}">
                                    ${name}
                                </option>
                            `;
                        }).join('');
                    doctorSelect.disabled = false;
                } else {
                    doctorSelect.innerHTML = '<option value="">No doctors available</option>';
                }
            } else {
                doctorSelect.innerHTML = '<option value="">Error loading doctors</option>';
            }
            
            if (typeof $(doctorSelect).select2 === 'function') {
                $(doctorSelect).select2({
                    dropdownParent: $('#appointmentModal'),
                    width: '100%'
                });
            }
        } catch (error) {
            console.error('Error loading all doctors:', error);
            doctorSelect.innerHTML = '<option value="">Error loading doctors</option>';
        }
    }

    // --- 3. STRICT AVAILABILITY CHECK ---
    checkAvailability() {
        const doctorSelect = document.getElementById('doctorSelect');
        const dateInput = document.querySelector('input[name="appointment_date"]');
        const timeInput = document.querySelector('input[name="appointment_time"]');
        const saveBtn = document.querySelector('#appointmentForm button[type="submit"]');
        const statusEl = document.getElementById('doctorAvailabilityStatus');

        if (!doctorSelect || !saveBtn || !statusEl) return;

        const doctorId = doctorSelect.value;
        const dateVal = dateInput ? dateInput.value : '';
        const timeVal = timeInput ? timeInput.value : '';

        // Strict: Invalid until proven valid
        if (!doctorId || !dateVal || !timeVal) {
            statusEl.innerHTML = '';
            saveBtn.disabled = true;
            saveBtn.style.opacity = '0.5';
            saveBtn.style.cursor = 'not-allowed';
            return;
        }

        const selectedOption = doctorSelect.options[doctorSelect.selectedIndex];
        if (!selectedOption) return;

        const days = (selectedOption.getAttribute('data-days') || '').split(',');
        const inTime = selectedOption.getAttribute('data-in');
        const outTime = selectedOption.getAttribute('data-out');

        if (!inTime || !outTime) return;

        let isAvailable = true;
        let failReason = '';

        // Check 1: Day of Week
        const dateObj = new Date(dateVal);
        const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const currentDay = dayNames[dateObj.getDay()];

        const isDayValid = days.some(d => {
            const dbDay = d.trim().toLowerCase();
            const curDay = currentDay.toLowerCase();
            return dbDay === curDay || dbDay === curDay.substring(0, 3);
        });

        if (!isDayValid) {
            isAvailable = false;
            failReason = 'Doctor Off Duty on ' + currentDay;
        }

        // Check 2: Time Range
        if (isAvailable) {
            const [reqH, reqM] = timeVal.split(':').map(Number);
            const reqMinutes = reqH * 60 + reqM;
            const [inH, inM] = inTime.split(':').map(Number);
            const inMinutes = inH * 60 + inM;
            const [outH, outM] = outTime.split(':').map(Number);
            const outMinutes = outH * 60 + outM;

            if (reqMinutes < inMinutes || reqMinutes > outMinutes) {
                isAvailable = false;
                failReason = `Time Unavailable (${this.format12HourTime(inTime)} - ${this.format12HourTime(outTime)})`;
            }
        }

        // UI Update with 12-Hour formatted badge
        const formatted12Val = this.format12HourTime(timeVal);
        if (isAvailable) {
            statusEl.innerHTML = `<span style="color: #065f46; background: #d1fae5; padding: 4px 12px; border-radius: 20px; font-size: 0.82rem; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;"><i class="fas fa-check-circle"></i> Available (${formatted12Val})</span>`;
            saveBtn.disabled = false;
            saveBtn.style.opacity = '1';
            saveBtn.style.cursor = 'pointer';
        } else {
            statusEl.innerHTML = `<span style="color: #991b1b; background: #fee2e2; padding: 4px 12px; border-radius: 20px; font-size: 0.82rem; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;"><i class="fas fa-times-circle"></i> ${failReason}</span>`;
            saveBtn.disabled = true;
            saveBtn.style.opacity = '0.5';
            saveBtn.style.cursor = 'not-allowed';
        }
    }

    /**
     * 12-Hour Time Picker Logic
     */
    init12HourTimePicker() {
        const hrSelect = document.getElementById('time12HourSelect');
        const minSelect = document.getElementById('time12MinuteSelect');
        const ampmBtns = document.querySelectorAll('.ampm-pill');
        const quickPills = document.querySelectorAll('.time-pill-btn');

        if (!hrSelect || !minSelect) return;

        hrSelect.addEventListener('change', () => this.sync12HourToHidden());
        minSelect.addEventListener('change', () => this.sync12HourToHidden());

        ampmBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                ampmBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.sync12HourToHidden();
            });
        });

        quickPills.forEach(pill => {
            pill.addEventListener('click', (e) => {
                e.preventDefault();
                quickPills.forEach(p => p.classList.remove('active'));
                pill.classList.add('active');

                const timeStr = pill.getAttribute('data-time');
                if (timeStr) {
                    const match = timeStr.match(/^(\d{2}):(\d{2})\s+(AM|PM)$/i);
                    if (match) {
                        const [, hr, min, period] = match;
                        if (hrSelect) hrSelect.value = hr;
                        if (minSelect) minSelect.value = min;

                        ampmBtns.forEach(b => {
                            if (b.getAttribute('data-period').toUpperCase() === period.toUpperCase()) {
                                b.classList.add('active');
                            } else {
                                b.classList.remove('active');
                            }
                        });

                        this.sync12HourToHidden();
                    }
                }
            });
        });

        this.sync12HourToHidden();
    }

    sync12HourToHidden() {
        const hrSelect = document.getElementById('time12HourSelect');
        const minSelect = document.getElementById('time12MinuteSelect');
        const activeAmPm = document.querySelector('.ampm-pill.active');
        const hiddenInput = document.getElementById('appointment_time_hidden');
        const previewBadge = document.getElementById('time12Preview');

        if (!hrSelect || !minSelect || !hiddenInput) return;

        let hr = parseInt(hrSelect.value, 10) || 9;
        const min = minSelect.value || '00';
        const period = activeAmPm ? activeAmPm.getAttribute('data-period') : 'AM';

        let hr24 = hr;
        if (period === 'PM' && hr < 12) hr24 = hr + 12;
        if (period === 'AM' && hr === 12) hr24 = 0;

        const hr24Str = String(hr24).padStart(2, '0');
        const minStr = String(min).padStart(2, '0');
        const time24 = `${hr24Str}:${minStr}`;

        hiddenInput.value = time24;

        const hrDisplay = String(hr).padStart(2, '0');
        const formatted12 = `${hrDisplay}:${minStr} ${period}`;

        if (previewBadge) {
            previewBadge.textContent = formatted12;
        }

        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    set12HourFrom24(time24) {
        if (!time24) time24 = '09:00';

        const parts = time24.split(':');
        let h = parseInt(parts[0], 10);
        if (isNaN(h)) h = 9;
        const m = parts[1] ? parts[1].substring(0, 2) : '00';

        const period = h >= 12 ? 'PM' : 'AM';
        let h12 = h % 12;
        if (h12 === 0) h12 = 12;

        const h12Str = String(h12).padStart(2, '0');
        const mNum = parseInt(m, 10) || 0;
        const roundedMin = String(Math.round(mNum / 5) * 5 % 60).padStart(2, '0');

        const hrSelect = document.getElementById('time12HourSelect');
        const minSelect = document.getElementById('time12MinuteSelect');
        const ampmBtns = document.querySelectorAll('.ampm-pill');

        if (hrSelect) hrSelect.value = h12Str;
        if (minSelect) {
            if ([...minSelect.options].some(opt => opt.value === roundedMin)) {
                minSelect.value = roundedMin;
            } else {
                minSelect.value = '00';
            }
        }

        ampmBtns.forEach(b => {
            if (b.getAttribute('data-period').toUpperCase() === period) {
                b.classList.add('active');
            } else {
                b.classList.remove('active');
            }
        });

        const hiddenInput = document.getElementById('appointment_time_hidden');
        if (hiddenInput) hiddenInput.value = time24;

        const previewBadge = document.getElementById('time12Preview');
        if (previewBadge) previewBadge.textContent = `${h12Str}:${roundedMin} ${period}`;
    }

    format12HourTime(timeStr) {
        if (!timeStr) return '';
        const parts = timeStr.split(':');
        let h = parseInt(parts[0], 10);
        if (isNaN(h)) return '';
        const m = parts[1] ? parts[1].substring(0, 2) : '00';
        const period = h >= 12 ? 'PM' : 'AM';
        h = h % 12;
        if (h === 0) h = 12;
        return `${String(h).padStart(2, '0')}:${m} ${period}`;
    }

    resetAvailabilityStatus() {
        const statusEl = document.getElementById('doctorAvailabilityStatus');
        if (statusEl) statusEl.innerHTML = '';
    }

    blockSubmission(reason) {
        // Fallback or legacy use
        const saveBtn = document.querySelector('#appointmentForm button[type="submit"]');
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.style.opacity = '0.5';
            saveBtn.style.cursor = 'not-allowed';
        }
    }

    // --- STANDARD CRUD ---
    async loadAppointments() {
        try {
            this.showLoading(true);
            const params = new URLSearchParams(this.filters);
            const response = await this.apiCall('GET', `?${params.toString()}`);
            if (response.success) {
                this.appointments = response.data;
                this.renderAppointments();
            } else {
                this.showToast(response.error || 'Failed to load appointments', 'error');
            }
        } catch (error) {
            console.error('Load error:', error);
            this.showToast('Error loading appointments', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async createAppointment(data) {
        let response;
        try {
            this.showLoading(true);
            response = await this.apiCall('POST', '', data);
            if (response && response.success) {
                this.closeModal();
                this.showSuccessPopup('Appointment Scheduled!', 'The appointment has been successfully booked and registered.');
                this.loadAppointments();
            } else {
                const errorMsg = response?.error || 'Failed to schedule appointment';
                if (errorMsg.includes('already has an appointment')) {
                    this.showDuplicateAppointmentPopup(errorMsg);
                } else {
                    this.showErrorPopup('Scheduling Failed', errorMsg);
                }
            }
        } catch (error) {
            console.error('Create error:', error);
            const errorMsg = error.message || 'Failed to schedule appointment';
            if (errorMsg.includes('already has an appointment')) {
                this.showDuplicateAppointmentPopup(errorMsg);
            } else {
                this.showErrorPopup('Error', errorMsg);
            }
        } finally {
            this.showLoading(false);
            return response;
        }
    }

    async updateAppointment(id, data) {
        let response;
        try {
            this.showLoading(true);
            response = await this.apiCall('PUT', `/${id}`, data);
            if (response && response.success) {
                this.closeModal();
                this.showSuccessPopup('Appointment Updated!', 'The appointment details have been updated successfully.');
                this.loadAppointments();
            } else {
                const errorMsg = response?.error || 'Failed to update appointment';
                if (errorMsg.includes('already has an appointment')) {
                    this.showDuplicateAppointmentPopup(errorMsg);
                } else {
                    this.showErrorPopup('Update Failed', errorMsg);
                }
            }
        } catch (error) {
            console.error('Update error:', error);
            this.showErrorPopup('Error', error.message || 'Failed to update appointment');
        } finally {
            this.showLoading(false);
            return response;
        }
    }

    /**
     * Prepare re-appointment/follow-up from existing record
     */
    async prepareReappointment(apt) {
        console.log('Preparing re-appointment for:', apt);

        // 1. Open the existing modal in create mode
        this.openModal('create');

        // 2. Auto-fill the patient (Select2)
        const patientOption = new Option(`${apt.patient_id} - ${apt.patient_name}`, apt.patient_id, true, true);
        $('#patientSelect').append(patientOption).trigger('change');

        // 3. Set Department 
        const deptSelect = document.getElementById('departmentSelect');
        if (deptSelect && apt.department_id) {
            deptSelect.value = apt.department_id;
            if (typeof $(deptSelect).select2 === 'function') $(deptSelect).trigger('change.select2');
        }

        // 4. Set Doctor
        const docSelect = document.getElementById('doctorSelect');
        if (docSelect && apt.doctor_id) {
            docSelect.value = apt.doctor_id;
            if (typeof $(docSelect).select2 === 'function') $(docSelect).trigger('change.select2');
            $(docSelect).trigger('change');
        }

        // 5. Set Date (Suggestion: Today + 7 days)
        const nextWeek = new Date();
        nextWeek.setDate(nextWeek.getDate() + 7);
        const dateInput = document.querySelector('input[name="appointment_date"]');
        if (dateInput) {
            dateInput.value = nextWeek.toISOString().split('T')[0];
        }

        // 6. Set Reason
        const reasonInput = document.querySelector('input[name="reason"]');
        if (reasonInput) {
            reasonInput.value = `Follow-up: ${apt.reason || 'General Checkup'}`;
        }

        // 7. Check availability for the new auto-filled data
        this.checkAvailability();

        this.showToast('Follow-up details pre-filled. Please select a time.', 'info');
    }

    /**
     * Shows a filtered list of appointments containing "Follow-up"
     */
    showFollowupSuggestions() {
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.classList.remove('hidden');
            searchInput.value = 'Follow-up';
            this.filterTable('follow-up');
            this.showToast('Filtered for follow-up appointments', 'info');
        } else {
            this.showToast('Search filters are not available', 'warning');
        }
    }

    /**
     * Client-side table filtering
     */
    filterTable(term) {
        const rows = document.querySelectorAll('#appointmentTableBody tr');
        const searchTerm = term.toLowerCase();

        rows.forEach(row => {
            if (row.cells.length < 2) return; // Skip empty/loading rows
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    }

    /**
     * Load specific appointment data into form
     */
    async loadAppointmentData(id) {
        try {
            this.showLoading(true);
            const response = await this.apiCall('GET', `/${id}`);
            if (response.success) {
                const apt = response.data;
                const form = document.getElementById('appointmentForm');

                // 1. Patient selection (Select2)
                const patientOption = new Option(`${apt.patient_id} - ${apt.patient_name}`, apt.patient_id, true, true);
                $('#patientSelect').append(patientOption).trigger('change');
                $('#patientPhone').val(apt.phone || '');

                // 2. Department & Doctor
                const deptSelect = document.getElementById('departmentSelect');
                if (deptSelect) {
                    // Safety: If departments aren't loaded yet, load them now
                    if (deptSelect.options.length <= 1) {
                        await this.loadDepartments();
                    }

                    if (apt.department_id) {
                        deptSelect.value = apt.department_id;
                        if (typeof $(deptSelect).select2 === 'function') $(deptSelect).trigger('change.select2');
                    }

                    const docSelect = document.getElementById('doctorSelect');
                    if (docSelect && apt.doctor_id) {
                        docSelect.value = apt.doctor_id;
                        if (typeof $(docSelect).select2 === 'function') $(docSelect).trigger('change.select2');
                        $(docSelect).trigger('change');
                        this.checkAvailability();
                    }
                }

                // Helper to safely set value
                const setVal = (selector, value) => {
                    const el = form.querySelector(selector);
                    if (el) el.value = value;
                };

                // 3. Date and Time
                setVal('input[name="appointment_date"]', apt.appointment_date);
                if (apt.appointment_time) {
                    this.set12HourFrom24(apt.appointment_time);
                }

                // 4. Status & Reason
                setVal('select[name="status"]', apt.status || 'Scheduled');
                setVal('input[name="reason"]', apt.reason || '');
                setVal('textarea[name="notes"]', apt.notes || '');

                // 5. Billing
                setVal('input[name="consultation_fee"]', apt.consultation_fee || '');
                setVal('input[name="discount"]', apt.discount || '0');
                setVal('input[name="total_amount"]', apt.total_amount || '');
                setVal('select[name="payment_status"]', apt.payment_status || 'Pending');
                setVal('select[name="payment_mode"]', apt.payment_mode || 'Cash');

                this.checkAvailability();
            }
        } catch (error) {
            console.error('Error loading appointment:', error);
            this.showToast('Failed to load appointment data', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async deleteAppointment(id) {
        if (!confirm('Are you sure you want to cancel this appointment?')) {
            return;
        }
        try {
            this.showLoading(true);
            const response = await this.apiCall('DELETE', `/${id}`);
            if (response.success) {
                this.showToast('Appointment cancelled successfully', 'success');
                this.loadAppointments();
            } else {
                this.showToast(response.error || 'Failed to cancel', 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            this.showToast('Failed to cancel appointment', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    // --- UI/UX Helpers ---

    openModal(mode, id = null) {
        console.log('Opening modal:', mode, id);
        const modal = document.getElementById('appointmentModal');
        const form = document.getElementById('appointmentForm');

        if (!modal || !form) {
            console.error('Modal or form not found');
            return;
        }

        form.reset();

        // Reset Select2
        $('#patientSelect').val(null).trigger('change');

        const deptSelect = document.getElementById('departmentSelect');
        if (deptSelect) {
            $(deptSelect).val(null).trigger('change.select2');
        }

        const docSelect = document.getElementById('doctorSelect');
        if (docSelect) {
            $(docSelect).val(null).trigger('change.select2');
        }

        // Clear availability msg/status
        this.resetAvailabilityStatus();
        const scheduleInfoEl = document.getElementById('doctorScheduleInfo');
        if (scheduleInfoEl) scheduleInfoEl.classList.add('hidden');

        const saveBtn = document.querySelector('#appointmentForm button[type="submit"]');
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.style.opacity = '0.5';
            saveBtn.style.cursor = 'not-allowed';
        }

        if (mode === 'edit' && id) {
            document.getElementById('modalTitle').textContent = 'Reschedule/Edit Appointment';
            const editIdInput = document.getElementById('editAppointmentId');
            if (editIdInput) editIdInput.value = id;
            const dispAptId = document.getElementById('displayAppointmentId');
            if (dispAptId) dispAptId.value = id;

            // Load existing data
            this.loadAppointmentData(id);
        } else {
            // Create Mode
            document.getElementById('modalTitle').textContent = 'New Appointment';
            const editIdInput = document.getElementById('editAppointmentId');
            if (editIdInput) editIdInput.value = '';
            const dispAptId = document.getElementById('displayAppointmentId');
            if (dispAptId) dispAptId.value = 'APT-AUTO';

            const today = new Date().toISOString().split('T')[0];
            const dateInput = form.querySelector('input[name="appointment_date"]');
            if (dateInput) dateInput.value = today;

            this.set12HourFrom24('09:00');
        }

        modal.classList.remove('hidden');
    }

    closeModal() {
        const modal = document.getElementById('appointmentModal');
        if (modal) modal.classList.add('hidden');
    }

    async handleFormSubmit(event) {
        event.preventDefault();
        const form = event.target;

        // Manual data collection to handle disabled fields and Select2
        const data = {};

        // 1. Get simple inputs
        new FormData(form).forEach((value, key) => data[key] = value);

        // 2. Get Select2 Patient ID explicitly
        const patientVal = $('#patientSelect').val();
        if (patientVal) data['patient_id'] = patientVal;

        // 3. Get Doctor ID explicitly (even if disabled)
        const doctorVal = document.getElementById('doctorSelect').value;
        if (doctorVal) data['doctor_id'] = doctorVal;

        // 4. Default Status to 1 (Active/Scheduled) since input was removed
        data['status'] = '1';

        console.log('Submitting Data:', data); // Debug logging

        // Basic Validation (Client side)
        if (!data.patient_id) {
            this.showToast('Please select a patient', 'error');
            return;
        }
        if (!data.doctor_id) {
            this.showToast('Please select a doctor', 'error');
            return;
        }

        const id = document.getElementById('editAppointmentId')?.value;
        let response;
        if (id) {
            response = await this.updateAppointment(id, data);
        } else {
            response = await this.createAppointment(data);
        }

        if (response && response.success && this.isPrintAction) {
            this.isPrintAction = false; // Reset
            // Open Print Window
            // We use patient_id and date to find the invoice since we don't have invoice_id directly yet
            // Or better, if response contained it. But for now, patient_id + date is reliable enough for "just created"
            const url = `print_opd_receipt.php?patient_id=${data.patient_id}&date=${data.appointment_date}`;
            window.open(url, '_blank');
        }
    }

    saveAndPrint() {
        this.isPrintAction = true;
        document.getElementById('appointmentForm').requestSubmit();
    }

    renderAppointments() {
        const tableBody = document.getElementById('appointmentTableBody');
        if (!tableBody) return;

        if (this.appointments.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="9" class="text-center p-4">No appointments found</td></tr>`;
            return;
        }

        tableBody.innerHTML = this.appointments.map(apt => `
            <tr>
                <td class="font-bold text-gray-800">${apt.patient_id}</td>
                <td>${apt.appointment_id}</td>
                <td>
                    <div class="font-medium text-gray-900">${apt.patient_name}</div>
                </td>
                <td>${apt.phone || '-'}</td>
                <td>
                    <div class="font-medium text-gray-900">${apt.doctor_name}</div>
                    <div class="text-xs text-gray-500">${apt.specialization || ''}</div>
                </td>
                <td>${this.formatDate(apt.appointment_date)}</td>
                <td>${this.formatTime(apt.appointment_time)}</td>
                <td>${apt.reason || '-'}</td>
                <td><span class="status-badge status-${(apt.status == 1 ? 'active' : (apt.status == 0 ? 'completed' : String(apt.status || 'Active').toLowerCase()))}">${apt.status == 1 ? 'Active' : (apt.status == 0 ? 'Completed' : (apt.status || 'Active'))}</span></td>
                <td>
                    <div class="flex gap-2 justify-center">
                        ${apt.appointment_id.startsWith('NOAPT-') ? `
                        <button class="action-icon" onclick="appointmentManager.openModal('create'); setTimeout(() => { $('#patientSelect').append(new Option('${apt.patient_id} - ${apt.patient_name}', '${apt.patient_id}', true, true)).trigger('change'); }, 200);" title="Create Appointment" style="color: #10B981;">
                            <i class="fas fa-calendar-plus"></i>
                        </button>
                        ` : `
                        <button class="action-icon reschedule" onclick="appointmentManager.openModal('edit', '${apt.appointment_id}')" title="Reschedule" style="color: #6366F1;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-icon delete" onclick="appointmentManager.deleteAppointment('${apt.appointment_id}')" title="Cancel">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                        `}
                    </div>
                </td>
            </tr>
        `).join('');
    }

    formatDate(dateStr) {
        if (!dateStr) return '-';
        return new Date(dateStr).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    }

    formatTime(timeStr) {
        if (!timeStr) return '-';
        return new Date(`2000-01-01T${timeStr}`).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
    }

    showLoading(show) {
        const loader = document.getElementById('loadingSkeleton');
        const content = document.getElementById('appointmentTableWrapper');
        if (loader && content) {
            if (show) {
                loader.classList.remove('hidden');
                content.classList.add('hidden');
            } else {
                loader.classList.add('hidden');
                content.classList.remove('hidden');
            }
        }
    }

    showToast(msg, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = msg;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    attachEventListeners() {
        const form = document.getElementById('appointmentForm');
        if (form) form.addEventListener('submit', (e) => this.handleFormSubmit(e));

        // Use jQuery for doctorSelect to reliably catch Select2 changes
        $('#doctorSelect').on('select2:select change', (e) => {
            const selectedOption = $(e.target).find('option:selected');
            const scheduleInfoEl = document.getElementById('doctorScheduleInfo');
            if (selectedOption.length > 0 && selectedOption.val()) {
                const dept = selectedOption.attr('data-department');
                const days = selectedOption.attr('data-days');
                const inTime = selectedOption.attr('data-in');
                const outTime = selectedOption.attr('data-out');

                if (dept) {
                    const deptSelect = document.getElementById('departmentSelect');
                    if (deptSelect) {
                        deptSelect.value = dept;
                        if (typeof $(deptSelect).select2 === 'function' && $(deptSelect).hasClass('select2-hidden-accessible')) {
                            $(deptSelect).trigger('change.select2');
                        } else {
                            $(deptSelect).trigger('change');
                        }
                    }
                }

                if (scheduleInfoEl && inTime && outTime) {
                    const in12 = this.format12HourTime(inTime);
                    const out12 = this.format12HourTime(outTime);
                    scheduleInfoEl.innerHTML = `<i class="far fa-calendar-check" style="color:#1f6b4a; margin-right:4px;"></i> <strong>Schedule:</strong> ${days || 'All Days'} (${in12} - ${out12})`;
                    scheduleInfoEl.classList.remove('hidden');
                } else if (scheduleInfoEl) {
                    scheduleInfoEl.classList.add('hidden');
                }
            } else if (scheduleInfoEl) {
                scheduleInfoEl.classList.add('hidden');
            }
            this.checkAvailability();
        });

        const triggers = ['appointment_date', 'appointment_time'];
        triggers.forEach(id => {
            const el = document.getElementById(id) || document.querySelector(`input[name="${id}"]`) || document.querySelector(`select[id="${id}"]`);
            if (el) el.addEventListener('change', () => this.checkAvailability());
        });


        // Client-side search and filters
        const searchInput = document.getElementById('searchInput');
        const docFilter = document.getElementById('doctorFilter');
        const statFilter = document.getElementById('statusFilter');

        const runFilter = () => {
            const search = searchInput?.value || '';
            const doctor = docFilter?.value || '';
            const status = statFilter?.value || '';
            this.multiFilterTable(search, doctor, status);
        };

        if (searchInput) searchInput.addEventListener('input', runFilter);
        if (docFilter) docFilter.addEventListener('change', runFilter);
        if (statFilter) statFilter.addEventListener('change', runFilter);
    }

    /**
     * Professional multi-column client-side filter
     */
    multiFilterTable(search, doctor, status) {
        const rows = document.querySelectorAll('#appointmentTableBody tr');
        const sTerm = search.toLowerCase();
        const dTerm = doctor.toLowerCase();
        const stTerm = status.toLowerCase();

        rows.forEach(row => {
            if (row.cells.length < 8) return;
            const text = row.innerText.toLowerCase();
            const doctorName = row.cells[4].innerText.toLowerCase();
            const statusText = row.cells[8].innerText.toLowerCase();

            const matchesSearch = text.includes(sTerm);
            const matchesDoctor = dTerm === '' || doctorName.includes(dTerm);
            const matchesStatus = stTerm === '' || statusText.includes(stTerm);

            row.style.display = (matchesSearch && matchesDoctor && matchesStatus) ? '' : 'none';
        });
    }

    initializeDatePicker() {
        // Placeholder
    }

    async apiCall(method, endpoint, data = null, overrideBase = null) {
        const url = (overrideBase || this.apiBase) + endpoint;
        const options = {
            method: method,
            headers: { 
                'Content-Type': 'application/json',
                'X-Hospital-Branch': window.HOSPITAL_BRANCH || ''
            }
        };
        if (data) options.body = JSON.stringify(data);

        try {
            const response = await fetch(url, options);

            // Check if response is JSON
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.includes("application/json")) {
                const json = await response.json();
                if (!response.ok || !json.success) {
                    console.error("API Error Detailed:", json);
                    const errorMsg = json.error || json.message || 'Unknown server error';
                    // Throw an error with the server's message so the UI can catch it
                    throw new Error(errorMsg);
                }
                return json;
            } else {
                // Non-JSON response (likely HTML error page)
                const text = await response.text();
                console.error("API Error (Non-JSON):", text);
                throw new Error(`Server Error (${response.status}): The server returned an invalid response.`);
            }
        } catch (error) {
            console.error("Network or Logic Error:", error);
            // Re-throw so specific actions can handle it (or return a mock failure object if preferring not to throw)
            return { success: false, error: error.message };
        }
    }

    /**
     * Display centered popup dialogs using SweetAlert2
     */
    showToast(message, type = 'info') {
        if (message && message.includes('already has an appointment')) {
            this.showDuplicateAppointmentPopup(message);
            return;
        }
        this.showPopup(message, type);
    }

    showPopup(message, type = 'info', title = null) {
        let defaultTitle = 'Notification';
        if (type === 'success') defaultTitle = 'Success!';
        if (type === 'error') defaultTitle = 'Error';
        if (type === 'warning') defaultTitle = 'Warning';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title || defaultTitle,
                text: message,
                icon: type,
                position: 'center',
                confirmButtonColor: type === 'error' ? '#dc2626' : (type === 'warning' ? '#d97706' : '#1f6b4a'),
                confirmButtonText: 'OK'
            });
        } else {
            alert(`${title || defaultTitle}: ${message}`);
        }
    }

    showSuccessPopup(title, message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title || 'Success!',
                text: message || 'Operation completed successfully.',
                icon: 'success',
                position: 'center',
                confirmButtonColor: '#1f6b4a',
                confirmButtonText: '<i class="fas fa-check"></i> OK',
                timer: 3500,
                timerProgressBar: true
            });
        } else {
            alert(`${title}: ${message}`);
        }
    }

    showDuplicateAppointmentPopup(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Duplicate Appointment Alert',
                html: `
                    <div style="text-align: center; padding: 10px 5px;">
                        <div style="background-color: #fef3c7; color: #d97706; width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto;">
                            <i class="fas fa-calendar-times" style="font-size: 2rem;"></i>
                        </div>
                        <h4 style="font-weight: 700; font-size: 1.15rem; color: #1e293b; margin-bottom: 8px;">
                            Patient Already Has an Appointment
                        </h4>
                        <p style="color: #475569; font-size: 0.95rem; line-height: 1.5; margin-bottom: 12px;">
                            ${message || 'Patient already has an active appointment scheduled on this date.'}
                        </p>
                        <div style="background-color: #f8fafc; border-left: 4px solid #f59e0b; padding: 10px 12px; border-radius: 6px; text-align: left; margin-top: 10px;">
                            <p style="color: #64748b; font-size: 0.85rem; margin: 0;">
                                <i class="fas fa-info-circle" style="color: #f59e0b; margin-right: 5px;"></i>
                                <strong>Action Required:</strong> Please choose a different appointment date or inspect existing appointments for this patient.
                            </p>
                        </div>
                    </div>
                `,
                position: 'center',
                showConfirmButton: true,
                confirmButtonText: '<i class="fas fa-check"></i> Understood',
                confirmButtonColor: '#1f6b4a',
                allowOutsideClick: false,
                focusConfirm: true
            });
        } else {
            alert(`Duplicate Appointment Alert:\n${message}`);
        }
    }

    showErrorPopup(title, message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title || 'Error',
                text: message || 'An unexpected error occurred.',
                icon: 'error',
                position: 'center',
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Close'
            });
        } else {
            alert(`${title}: ${message}`);
        }
    }
}

// Global Instance
const appointmentManager = new AppointmentManager();

document.addEventListener('DOMContentLoaded', () => {
    appointmentManager.init();
});
