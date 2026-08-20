/**
 * Appointment Manager
 * Handles appointment operations, multi-doctor consultations, Select2 integration, and strict availability logic
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
        this.doctorsCache = [];
        this.departmentsCache = [];
        this.doctorRowCounter = 0;
        this.isEditMode = false;
        this.editAppointmentId = null;
    }

    async init() {
        await this.loadAllDoctors();
        this.loadAppointments();
        this.loadDepartments();
        this.loadFilterDoctors();
        this.initPatientSearch();
        this.attachEventListeners();

        // Handle deep-linking from patient list
        this.checkUrlParams();

        console.log('AppointmentManager initialized with Multi-Doctor Support');
    }

    /**
     * Check URL parameters or secure sessionStorage for auto-booking actions
     */
    checkUrlParams() {
        let pendingPatient = null;
        try {
            const raw = sessionStorage.getItem('pending_appointment_patient') || localStorage.getItem('pending_appointment_patient');
            if (raw) {
                pendingPatient = JSON.parse(raw);
                sessionStorage.removeItem('pending_appointment_patient');
                localStorage.removeItem('pending_appointment_patient');
            }
        } catch (e) {
            console.error('Failed to parse pending appointment patient', e);
        }

        if (pendingPatient && pendingPatient.patient_id) {
            const patientId = pendingPatient.patient_id;
            const patientName = pendingPatient.patient_name || `${pendingPatient.first_name || ''} ${pendingPatient.last_name || ''}`.trim();
            const patientPhone = pendingPatient.phone || '';

            setTimeout(() => {
                this.openModal('create');
                const displayText = patientName ? `${patientId} - ${patientName}${patientPhone ? ' (' + patientPhone + ')' : ''}` : patientId;
                const patientOption = new Option(displayText, patientId, true, true);
                $('#patientSelect').append(patientOption).trigger('change');
                if (patientPhone) {
                    $('#patientPhone').val(patientPhone);
                }
                if (patientName) {
                    this.showToast(`Booking for: ${patientName}`, 'info');
                }
            }, 600);
            return;
        }

        // Fallback to URL parameters if present
        const urlParams = new URLSearchParams(window.location.search);
        const patientId = urlParams.get('patient_id');
        const action = urlParams.get('action');
        const autoBilling = urlParams.get('auto_billing');

        if (autoBilling === 'true') {
            this.autoBilling = true;
            this.autoBillingPatientId = patientId;
        }

        if (patientId && action === 'new') {
            setTimeout(() => {
                this.openModal('create');

                this.apiCall('GET', `/${patientId}`, null, this.patientApiBase).then(response => {
                    if (response.success && response.data) {
                        const p = response.data;
                        const patientOption = new Option(`${p.patient_id} - ${p.first_name} ${p.last_name}`, p.patient_id, true, true);
                        $('#patientSelect').append(patientOption).trigger('change');

                        this.showToast(`Booking for: ${p.first_name} ${p.last_name}`, 'info');
                    }
                });

                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
            }, 800);
        }
    }

    // --- 1. PATIENT SEARCH (Select2) ---
    initPatientSearch() {
        $('#patientSelect').select2({
            dropdownParent: $('#appointmentModal'),
            placeholder: 'Search for a patient...',
            allowClear: true,
            minimumInputLength: 1,
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
                    const patients = (data.success && data.data && data.data.data) ? data.data.data : (Array.isArray(data.data) ? data.data : []);
                    return {
                        results: patients.map(p => ({
                            id: p.patient_id,
                            text: `${p.patient_id} - ${p.first_name} ${p.last_name} (${p.phone || 'No Phone'})`,
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

    // --- 2. DOCTOR & DEPARTMENT CACHE ---
    async loadAllDoctors() {
        try {
            const response = await this.apiCall('GET', '', null, '/GM_HMS/api/doctors');
            if (response.success && response.data) {
                this.doctorsCache = response.data.data || response.data;
            }
        } catch (error) {
            console.error('Error caching doctors:', error);
        }
    }

    async loadDepartments() {
        try {
            const response = await this.apiCall('GET', '/departments');
            if (response.success) {
                this.departmentsCache = response.data || [];
            }
        } catch (error) {
            console.error('Error loading departments:', error);
        }
    }

    async loadFilterDoctors() {
        try {
            const response = await this.apiCall('GET', '', null, '/GM_HMS/api/doctors');
            if (response.success && response.data) {
                const doctors = response.data.data || response.data;
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

    // --- 3. MULTI-DOCTOR CONSULTATION ROWS ---

    getDepartmentOptionsHtml(selectedDept = '') {
        let html = '<option value="">All Departments</option>';
        const depts = new Set();
        
        if (this.departmentsCache && Array.isArray(this.departmentsCache)) {
            this.departmentsCache.forEach(d => {
                const name = d.department_name || (typeof d === 'string' ? d : '');
                if (name) depts.add(name);
            });
        }
        if (this.doctorsCache && Array.isArray(this.doctorsCache)) {
            this.doctorsCache.forEach(doc => {
                if (doc.specialization) depts.add(doc.specialization);
            });
        }

        Array.from(depts).sort().forEach(dept => {
            const isSelected = dept.toLowerCase() === (selectedDept || '').toLowerCase() ? 'selected' : '';
            html += `<option value="${dept}" ${isSelected}>${dept}</option>`;
        });
        return html;
    }

    getDoctorOptionsHtml(selectedDocId = '', filterDept = '') {
        let html = '<option value="">Search Doctor...</option>';
        if (this.doctorsCache && this.doctorsCache.length > 0) {
            this.doctorsCache.forEach(doc => {
                const id = doc.doctor_id || doc.id || '';
                const name = (doc.full_name || doc.name || '').replace(/"/g, '&quot;');
                const dept = (doc.specialization || '').replace(/"/g, '&quot;');
                const days = (doc.available_days || '').replace(/"/g, '&quot;');
                const inTime = doc.in_time || '';
                const outTime = doc.out_time || '';
                const fee = doc.consultation_fee || 0;
                const isSelected = id == selectedDocId ? 'selected' : '';

                if (filterDept && filterDept !== '' && dept.toLowerCase() !== filterDept.toLowerCase()) {
                    return;
                }

                html += `<option value="${id}" 
                                data-department="${dept}" 
                                data-days="${days}" 
                                data-in="${inTime}" 
                                data-out="${outTime}" 
                                data-fee="${fee}" 
                                ${isSelected}>${name} (${dept})</option>`;
            });
        }
        return html;
    }

    addDoctorRow(initialData = null) {
        const container = document.getElementById('doctorRowsContainer');
        if (!container) return;

        const rowId = `docRow_${++this.doctorRowCounter}`;
        const initialDocId = initialData?.doctor_id || '';
        const initialTime24 = initialData?.appointment_time || '09:00';
        
        let initialDept = '';
        if (initialDocId && this.doctorsCache && this.doctorsCache.length > 0) {
            const matchedDoc = this.doctorsCache.find(d => (d.doctor_id || d.id) == initialDocId);
            if (matchedDoc) initialDept = matchedDoc.specialization || '';
        }

        // Time calculations
        const timeParts = this.parse24To12(initialTime24);

        const hoursOptions = ['01','02','03','04','05','06','07','08','09','10','11','12'].map(h => 
            `<option value="${h}" ${h === timeParts.hour ? 'selected' : ''}>${h}</option>`
        ).join('');

        const mins = ['00','05','10','15','20','25','30','35','40','45','50','55'];
        const minsOptions = mins.map(m => 
            `<option value="${m}" ${m === timeParts.minute ? 'selected' : ''}>${m}</option>`
        ).join('');

        const rowHtml = `
            <div class="doctor-consultation-row" id="${rowId}" style="background: #ffffff; border: 1.5px solid #E2E8F0; border-radius: 10px; padding: 12px 14px; box-shadow: 0 2px 6px rgba(0,0,0,0.03); transition: all 0.2s ease;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; border-bottom: 1px dashed #E2E8F0; padding-bottom: 6px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="badge-row-number" style="background: #144D34; color: #fff; font-size: 0.72rem; font-weight: 700; padding: 2px 8px; border-radius: 6px;">Doctor</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div class="row-avail-status"></div>
                        ${!this.isEditMode ? `
                        <button type="button" class="btn-remove-doc-row" onclick="appointmentManager.removeDoctorRow('${rowId}')" title="Remove Doctor" style="background: #fee2e2; color: #ef4444; border: none; border-radius: 6px; width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;">
                            <i class="fas fa-trash-alt" style="font-size: 0.75rem;"></i>
                        </button>
                        ` : ''}
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1.6fr 1.1fr 1.3fr; gap: 10px; align-items: end;">
                    <!-- 1. Doctor Selection (Advance Search Select2) -->
                    <div>
                        <label style="font-size: 0.72rem; font-weight: 700; color: #475569; display: block; margin-bottom: 3px;">Doctor (Advance Search) <span style="color: #ef4444;">*</span></label>
                        <select class="doctor-select-field" style="width: 100%;">
                            ${this.getDoctorOptionsHtml(initialDocId, initialDept)}
                        </select>
                        <div class="row-schedule-info" style="font-size: 0.72rem; color: #475569; margin-top: 4px; display: none;"></div>
                    </div>

                    <!-- 2. Department Selection -->
                    <div>
                        <label style="font-size: 0.72rem; font-weight: 700; color: #475569; display: block; margin-bottom: 3px;">Department</label>
                        <select class="department-select-field" style="width: 100%; padding: 6px 10px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 0.85rem; background: #fff; height: 38px;" onchange="appointmentManager.onDepartmentRowChange('${rowId}')">
                            ${this.getDepartmentOptionsHtml(initialDept)}
                        </select>
                    </div>

                    <!-- 3. 12-Hour Time Picker -->
                    <div>
                        <label style="font-size: 0.72rem; font-weight: 700; color: #475569; display: block; margin-bottom: 3px;">
                            Time Slot <span style="font-size: 0.65rem; background: #144D34; color: #fff; padding: 1px 5px; border-radius: 4px; margin-left: 2px;">12-HR</span>
                        </label>
                        <div class="time-picker-card" style="background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 8px; padding: 2px 6px; display: flex; align-items: center; gap: 4px; height: 38px;">
                            <select class="time-hour-select" style="height: 26px; padding: 0 4px; font-size: 0.8rem; font-weight: 700; border: 1px solid #cbd5e1; border-radius: 4px; background: #fff;" onchange="appointmentManager.syncRowTime('${rowId}')">
                                ${hoursOptions}
                            </select>
                            <span style="font-weight: 800; color: #144D34;">:</span>
                            <select class="time-min-select" style="height: 26px; padding: 0 4px; font-size: 0.8rem; font-weight: 700; border: 1px solid #cbd5e1; border-radius: 4px; background: #fff;" onchange="appointmentManager.syncRowTime('${rowId}')">
                                ${minsOptions}
                            </select>
                            <div class="ampm-segmented-toggle" style="display: flex; gap: 2px; margin-left: 2px;">
                                <button type="button" class="ampm-pill ampm-am ${timeParts.period === 'AM' ? 'active' : ''}" data-period="AM" onclick="appointmentManager.toggleRowAmPm('${rowId}', 'AM')" style="padding: 2px 6px; font-size: 0.72rem; font-weight: 700; border: none; border-radius: 4px; cursor: pointer; ${timeParts.period === 'AM' ? 'background: #144D34; color: #fff;' : 'background: #e2e8f0; color: #475569;'}">AM</button>
                                <button type="button" class="ampm-pill ampm-pm ${timeParts.period === 'PM' ? 'active' : ''}" data-period="PM" onclick="appointmentManager.toggleRowAmPm('${rowId}', 'PM')" style="padding: 2px 6px; font-size: 0.72rem; font-weight: 700; border: none; border-radius: 4px; cursor: pointer; ${timeParts.period === 'PM' ? 'background: #144D34; color: #fff;' : 'background: #e2e8f0; color: #475569;'}">PM</button>
                            </div>
                            <input type="hidden" class="row-time-24" value="${initialTime24}">
                            <div class="row-time-preview" style="margin-left: auto; font-size: 0.72rem; font-weight: 700; color: #144D34;">${timeParts.hour}:${timeParts.minute} ${timeParts.period}</div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', rowHtml);

        // Initialize Select2 Advance Search on Doctor Dropdown
        const docSelectEl = $(`#${rowId} .doctor-select-field`);
        if (typeof docSelectEl.select2 === 'function') {
            docSelectEl.select2({
                dropdownParent: $('#appointmentModal'),
                placeholder: 'Search doctor by name or specialty...',
                width: '100%'
            }).on('select2:select change', () => {
                this.onDoctorRowChange(rowId);
            });
        }

        this.updateRowNumbers();
        this.onDoctorRowChange(rowId);
    }

    onDepartmentRowChange(rowId) {
        const row = document.getElementById(rowId);
        if (!row) return;

        const deptSelect = row.querySelector('.department-select-field');
        const docSelect = row.querySelector('.doctor-select-field');
        const selectedDept = deptSelect ? deptSelect.value : '';

        if (docSelect) {
            const currentDocId = $(docSelect).val();
            $(docSelect).html(this.getDoctorOptionsHtml(currentDocId, selectedDept));
            $(docSelect).trigger('change.select2');
        }

        this.onDoctorRowChange(rowId);
    }

    removeDoctorRow(rowId) {
        const row = document.getElementById(rowId);
        if (row) {
            row.remove();
            this.updateRowNumbers();
            this.checkAllAvailability();
        }
    }

    updateRowNumbers() {
        const rows = document.querySelectorAll('.doctor-consultation-row');
        rows.forEach((row, index) => {
            const numBadge = row.querySelector('.badge-row-number');
            if (numBadge) {
                numBadge.textContent = `Doctor #${index + 1}`;
            }
            const delBtn = row.querySelector('.btn-remove-doc-row');
            if (delBtn) {
                delBtn.style.display = rows.length > 1 ? 'flex' : 'none';
            }
        });

        const countBadge = document.getElementById('appointmentCountBadge');
        if (countBadge) {
            countBadge.textContent = `${rows.length} Doctor${rows.length > 1 ? 's' : ''} Selected`;
        }

        const btnSaveText = document.getElementById('btnSaveText');
        if (btnSaveText) {
            if (this.isEditMode) {
                btnSaveText.textContent = 'Update Appointment';
            } else {
                btnSaveText.textContent = rows.length > 1 ? `Schedule All (${rows.length}) Appointments` : 'Schedule Appointment';
            }
        }
    }

    onDoctorRowChange(rowId) {
        const row = document.getElementById(rowId);
        if (!row) return;

        const docSelect = row.querySelector('.doctor-select-field');
        const deptSelect = row.querySelector('.department-select-field');
        const selectedOption = docSelect ? docSelect.options[docSelect.selectedIndex] : null;
        const schedInfo = row.querySelector('.row-schedule-info');

        if (selectedOption && selectedOption.value) {
            const dept = selectedOption.getAttribute('data-department');
            const days = selectedOption.getAttribute('data-days');
            const inTime = selectedOption.getAttribute('data-in');
            const outTime = selectedOption.getAttribute('data-out');

            // Auto-sync Department dropdown if empty or different
            if (dept && deptSelect && deptSelect.value !== dept) {
                deptSelect.value = dept;
            }

            if (schedInfo && inTime && outTime) {
                schedInfo.innerHTML = `<i class="far fa-calendar-check" style="color:#144D34; margin-right:4px;"></i> <strong>Duty:</strong> ${days || 'All Days'} (${this.format12HourTime(inTime)} - ${this.format12HourTime(outTime)})`;
                schedInfo.style.display = 'block';
            } else if (schedInfo) {
                schedInfo.style.display = 'none';
            }
        } else {
            if (schedInfo) schedInfo.style.display = 'none';
        }

        this.checkRowAvailability(rowId);
    }

    toggleRowAmPm(rowId, period) {
        const row = document.getElementById(rowId);
        if (!row) return;

        const amBtn = row.querySelector('.ampm-am');
        const pmBtn = row.querySelector('.ampm-pm');

        if (period === 'AM') {
            if (amBtn) { amBtn.style.background = '#144D34'; amBtn.style.color = '#fff'; amBtn.classList.add('active'); }
            if (pmBtn) { pmBtn.style.background = '#e2e8f0'; pmBtn.style.color = '#475569'; pmBtn.classList.remove('active'); }
        } else {
            if (pmBtn) { pmBtn.style.background = '#144D34'; pmBtn.style.color = '#fff'; pmBtn.classList.add('active'); }
            if (amBtn) { amBtn.style.background = '#e2e8f0'; amBtn.style.color = '#475569'; amBtn.classList.remove('active'); }
        }

        this.syncRowTime(rowId);
    }

    syncRowTime(rowId) {
        const row = document.getElementById(rowId);
        if (!row) return;

        const hrSelect = row.querySelector('.time-hour-select');
        const minSelect = row.querySelector('.time-min-select');
        const activeAmPm = row.querySelector('.ampm-pill.active');
        const hiddenTime24 = row.querySelector('.row-time-24');
        const previewEl = row.querySelector('.row-time-preview');

        if (!hrSelect || !minSelect || !hiddenTime24) return;

        const hr12 = parseInt(hrSelect.value, 10) || 9;
        const minStr = minSelect.value || '00';
        const period = activeAmPm ? activeAmPm.getAttribute('data-period') : 'AM';

        let hr24 = hr12;
        if (period === 'PM' && hr12 < 12) hr24 = hr12 + 12;
        if (period === 'AM' && hr12 === 12) hr24 = 0;

        const hr24Str = String(hr24).padStart(2, '0');
        const time24 = `${hr24Str}:${minStr}`;

        hiddenTime24.value = time24;

        if (previewEl) {
            previewEl.textContent = `${String(hr12).padStart(2, '0')}:${minStr} ${period}`;
        }

        this.checkRowAvailability(rowId);
    }

    parse24To12(time24) {
        if (!time24) time24 = '09:00';
        const parts = time24.split(':');
        let h = parseInt(parts[0], 10);
        if (isNaN(h)) h = 9;
        const m = parts[1] ? parts[1].substring(0, 2) : '00';
        const period = h >= 12 ? 'PM' : 'AM';
        let h12 = h % 12;
        if (h12 === 0) h12 = 12;

        const mNum = parseInt(m, 10) || 0;
        const roundedMin = String(Math.round(mNum / 5) * 5 % 60).padStart(2, '0');

        return {
            hour: String(h12).padStart(2, '0'),
            minute: roundedMin,
            period: period
        };
    }

    onMainDateChange(newDate) {
        this.checkAllAvailability();
    }

    checkRowAvailability(rowId) {
        const row = document.getElementById(rowId);
        if (!row) return false;

        const docSelect = row.querySelector('.doctor-select-field');
        const hiddenTime = row.querySelector('.row-time-24');
        const statusEl = row.querySelector('.row-avail-status');
        const dateInput = document.getElementById('appointmentDateMain');

        if (!docSelect || !hiddenTime || !statusEl) return false;

        const doctorId = docSelect.value;
        const dateVal = dateInput ? dateInput.value : '';
        const timeVal = hiddenTime.value;

        if (!doctorId) {
            statusEl.innerHTML = '';
            return false;
        }

        if (!dateVal || !timeVal) {
            statusEl.innerHTML = `<span style="color: #d97706; font-size: 0.72rem; font-weight: 700;">Select Date & Time</span>`;
            return false;
        }

        const selectedOption = docSelect.options[docSelect.selectedIndex];
        const days = (selectedOption.getAttribute('data-days') || '').split(',');
        const inTime = selectedOption.getAttribute('data-in');
        const outTime = selectedOption.getAttribute('data-out');

        if (!inTime || !outTime) {
            statusEl.innerHTML = `<span style="color: #065f46; background: #d1fae5; padding: 2px 8px; border-radius: 12px; font-size: 0.72rem; font-weight: 700;"><i class="fas fa-check-circle"></i> Available</span>`;
            return true;
        }

        // Check Day of Week
        const dateObj = new Date(dateVal);
        const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const currentDay = dayNames[dateObj.getDay()];

        const isDayValid = days.some(d => {
            const dbDay = d.trim().toLowerCase();
            const curDay = currentDay.toLowerCase();
            return dbDay === curDay || dbDay === curDay.substring(0, 3);
        });

        if (!isDayValid) {
            statusEl.innerHTML = `<span style="color: #991b1b; background: #fee2e2; padding: 2px 8px; border-radius: 12px; font-size: 0.72rem; font-weight: 700;"><i class="fas fa-times-circle"></i> Doctor Off Duty on ${currentDay}</span>`;
            this.updateSubmitButtonState();
            return false;
        }

        // Check Time Range
        const [reqH, reqM] = timeVal.split(':').map(Number);
        const reqMinutes = reqH * 60 + reqM;
        const [inH, inM] = inTime.split(':').map(Number);
        const inMinutes = inH * 60 + inM;
        const [outH, outM] = outTime.split(':').map(Number);
        const outMinutes = outH * 60 + outM;

        if (reqMinutes < inMinutes || reqMinutes > outMinutes) {
            statusEl.innerHTML = `<span style="color: #991b1b; background: #fee2e2; padding: 2px 8px; border-radius: 12px; font-size: 0.72rem; font-weight: 700;"><i class="fas fa-times-circle"></i> Out of Duty Hours (${this.format12HourTime(inTime)} - ${this.format12HourTime(outTime)})</span>`;
            this.updateSubmitButtonState();
            return false;
        }

        statusEl.innerHTML = `<span style="color: #065f46; background: #d1fae5; padding: 2px 8px; border-radius: 12px; font-size: 0.72rem; font-weight: 700;"><i class="fas fa-check-circle"></i> Available</span>`;
        this.updateSubmitButtonState();
        return true;
    }

    checkAllAvailability() {
        const rows = document.querySelectorAll('.doctor-consultation-row');
        let allValid = rows.length > 0;

        rows.forEach(row => {
            const isValid = this.checkRowAvailability(row.id);
            if (!isValid) allValid = false;
        });

        this.updateSubmitButtonState(allValid);
        return allValid;
    }

    updateSubmitButtonState(explicitState = null) {
        const saveBtn = document.getElementById('btnSaveOnly');
        if (!saveBtn) return;

        if (explicitState === false) {
            saveBtn.disabled = true;
            saveBtn.style.opacity = '0.5';
            saveBtn.style.cursor = 'not-allowed';
            return;
        }

        const rows = document.querySelectorAll('.doctor-consultation-row');
        let allReady = rows.length > 0;

        rows.forEach(row => {
            const docSelect = row.querySelector('.doctor-select-field');
            if (!docSelect || !docSelect.value) allReady = false;
            const statusEl = row.querySelector('.row-avail-status');
            if (statusEl && statusEl.innerHTML.includes('fa-times-circle')) allReady = false;
        });

        if (allReady) {
            saveBtn.disabled = false;
            saveBtn.style.opacity = '1';
            saveBtn.style.cursor = 'pointer';
        } else {
            saveBtn.disabled = true;
            saveBtn.style.opacity = '0.5';
            saveBtn.style.cursor = 'not-allowed';
        }
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

    // --- 4. CRUD ACTIONS & MODAL CONTROLS ---

    openModal(mode, id = null) {
        const modal = document.getElementById('appointmentModal');
        const form = document.getElementById('appointmentForm');
        const container = document.getElementById('doctorRowsContainer');
        const btnAddDoc = document.getElementById('btnAddDoctorRow');

        if (!modal || !form || !container) return;

        form.reset();
        container.innerHTML = '';
        this.doctorRowCounter = 0;

        // Reset Select2
        $('#patientSelect').val(null).trigger('change');

        if (mode === 'edit' && id) {
            this.isEditMode = true;
            this.editAppointmentId = id;
            document.getElementById('modalTitle').textContent = 'Reschedule / Edit Appointment';
            document.getElementById('editAppointmentId').value = id;
            document.getElementById('displayAppointmentId').value = id;

            if (btnAddDoc) btnAddDoc.style.display = 'none';

            this.loadAppointmentData(id);
        } else {
            this.isEditMode = false;
            this.editAppointmentId = null;
            document.getElementById('modalTitle').textContent = 'New Appointment';
            document.getElementById('editAppointmentId').value = '';
            document.getElementById('displayAppointmentId').value = 'APT-AUTO';

            const today = new Date().toISOString().split('T')[0];
            const dateInput = document.getElementById('appointmentDateMain');
            if (dateInput) dateInput.value = today;

            if (btnAddDoc) btnAddDoc.style.display = 'inline-flex';

            // Add 1 default Doctor Consultation row
            this.addDoctorRow();
        }

        modal.classList.remove('hidden');
        this.updateSubmitButtonState();
    }

    closeModal() {
        const modal = document.getElementById('appointmentModal');
        if (modal) modal.classList.add('hidden');
    }

    async loadAppointmentData(id) {
        try {
            this.showLoading(true);
            const response = await this.apiCall('GET', `/${id}`);
            if (response.success && response.data) {
                const apt = response.data;

                // Patient selection (Select2)
                const patientOption = new Option(`${apt.patient_id} - ${apt.patient_name}`, apt.patient_id, true, true);
                $('#patientSelect').append(patientOption).trigger('change');
                $('#patientPhone').val(apt.phone || '');

                // Main fields
                const dateInput = document.getElementById('appointmentDateMain');
                if (dateInput) dateInput.value = apt.appointment_date;

                const reasonInput = document.getElementById('appointmentReasonMain');
                if (reasonInput) reasonInput.value = apt.reason || '';

                const notesInput = document.getElementById('appointmentNotes');
                if (notesInput) notesInput.value = apt.notes || '';

                // Doctor Row
                this.addDoctorRow({
                    doctor_id: apt.doctor_id,
                    appointment_time: apt.appointment_time,
                    reason: apt.reason
                });
            }
        } catch (error) {
            console.error('Error loading appointment:', error);
            this.showToast('Failed to load appointment details', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async handleFormSubmit(event) {
        event.preventDefault();

        const patientId = $('#patientSelect').val();
        if (!patientId) {
            this.showToast('Please select a patient', 'error');
            return;
        }

        const dateVal = document.getElementById('appointmentDateMain')?.value;
        if (!dateVal) {
            this.showToast('Please select an appointment date', 'error');
            return;
        }

        const phone = document.getElementById('patientPhone')?.value || '';
        const mainReason = document.getElementById('appointmentReasonMain')?.value || 'Consultation';
        const notes = document.getElementById('appointmentNotes')?.value || '';

        const rows = document.querySelectorAll('.doctor-consultation-row');
        if (rows.length === 0) {
            this.showToast('Please add at least one doctor consultation', 'error');
            return;
        }

        const appointmentsPayload = [];
        let hasIncomplete = false;

        rows.forEach(row => {
            const docSelect = row.querySelector('.doctor-select-field');
            const doctorId = docSelect ? docSelect.value : '';
            const time24 = row.querySelector('.row-time-24')?.value || '09:00';
            const rowReason = mainReason;

            if (!doctorId) {
                hasIncomplete = true;
            } else {
                const opt = docSelect.options[docSelect.selectedIndex];
                const fee = opt ? opt.getAttribute('data-fee') : 0;

                appointmentsPayload.push({
                    patient_id: patientId,
                    phone: phone,
                    doctor_id: doctorId,
                    appointment_date: dateVal,
                    appointment_time: time24,
                    reason: rowReason,
                    notes: notes,
                    status: '1',
                    consultation_fee: fee,
                    total_amount: fee,
                    appointment_type: 'OPD'
                });
            }
        });

        if (hasIncomplete || appointmentsPayload.length === 0) {
            this.showToast('Please select a doctor for all added consultations', 'error');
            return;
        }

        // Handle Edit Single Appointment vs Batch Create
        if (this.isEditMode && this.editAppointmentId) {
            const singleData = appointmentsPayload[0];
            await this.updateAppointment(this.editAppointmentId, singleData);
        } else {
            await this.createAppointmentsBatch(appointmentsPayload);
        }
    }

    async createAppointmentsBatch(appointmentsList) {
        try {
            this.showLoading(true);
            const response = await this.apiCall('POST', '', { appointments: appointmentsList });

            if (response && response.success) {
                this.closeModal();

                const count = appointmentsList.length;
                Swal.fire({
                    title: `${count} Appointment${count > 1 ? 's' : ''} Scheduled!`,
                    text: 'Redirecting to OPD Billing Terminal...',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    const redirectPatientId = this.autoBillingPatientId || appointmentsList[0].patient_id;
                    window.location.href = `opd_billing.php?patient_id=${encodeURIComponent(redirectPatientId)}`;
                });

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
            console.error('Create batch error:', error);
            const errorMsg = error.message || 'Failed to schedule appointments';
            if (errorMsg.includes('already has an appointment')) {
                this.showDuplicateAppointmentPopup(errorMsg);
            } else {
                this.showErrorPopup('Error', errorMsg);
            }
        } finally {
            this.showLoading(false);
        }
    }

    async updateAppointment(id, data) {
        try {
            this.showLoading(true);
            const response = await this.apiCall('PUT', `/${id}`, data);
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

    renderAppointments() {
        const tableBody = document.getElementById('appointmentTableBody');
        if (!tableBody) return;

        if (this.appointments.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="10" class="text-center p-4">No appointments found</td></tr>`;
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

    attachEventListeners() {
        const form = document.getElementById('appointmentForm');
        if (form) form.addEventListener('submit', (e) => this.handleFormSubmit(e));

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
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.includes("application/json")) {
                const json = await response.json();
                if (!response.ok || !json.success) {
                    console.error("API Error Detailed:", json);
                    const errorMsg = json.error || json.message || 'Unknown server error';
                    throw new Error(errorMsg);
                }
                return json;
            } else {
                const text = await response.text();
                console.error("API Error (Non-JSON):", text);
                throw new Error(`Server Error (${response.status}): The server returned an invalid response.`);
            }
        } catch (error) {
            console.error("Network or Logic Error:", error);
            return { success: false, error: error.message };
        }
    }

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
                confirmButtonColor: type === 'error' ? '#dc2626' : (type === 'warning' ? '#d97706' : '#144D34'),
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
                confirmButtonColor: '#144D34',
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
                title: 'Duplicate Doctor Appointment',
                html: `
                    <div style="text-align: center; padding: 10px 5px;">
                        <div style="background-color: #fef3c7; color: #d97706; width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto;">
                            <i class="fas fa-calendar-times" style="font-size: 2rem;"></i>
                        </div>
                        <h4 style="font-weight: 700; font-size: 1.15rem; color: #1e293b; margin-bottom: 8px;">
                            Appointment Conflict
                        </h4>
                        <p style="color: #475569; font-size: 0.95rem; line-height: 1.5; margin-bottom: 12px;">
                            ${message || 'Patient already has an active appointment scheduled with this doctor on this date.'}
                        </p>
                        <div style="background-color: #f8fafc; border-left: 4px solid #f59e0b; padding: 10px 12px; border-radius: 6px; text-align: left; margin-top: 10px;">
                            <p style="color: #64748b; font-size: 0.85rem; margin: 0;">
                                <i class="fas fa-info-circle" style="color: #f59e0b; margin-right: 5px;"></i>
                                <strong>Tip:</strong> Choose a different time slot, different doctor, or different date.
                            </p>
                        </div>
                    </div>
                `,
                position: 'center',
                showConfirmButton: true,
                confirmButtonText: '<i class="fas fa-check"></i> Understood',
                confirmButtonColor: '#144D34',
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
