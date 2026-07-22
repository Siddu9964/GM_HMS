/**
 * Patient Manager
 * 
 * Handles all patient-related operations via AJAX
 * Frontend JavaScript for Patient Management MVC
 */

class PatientManager {
    constructor() {
        this.apiBase = '/GM_HMS/api/patients';
        this.patients = [];
        this.currentPage = 1;
        this.totalPages = 1;
        this.perPage = 10;
        this.filters = {};
        this._listenersAttached = false; // Guard: prevent duplicate event listeners
    }

    /**
     * Initialize the patient manager
     */
    init() {
        this.applyFilters();
        this.attachEventListeners();
    }

    /**
     * Load patients from API
     */
    async loadPatients(page = 1) {
        try {
            this.showLoading(true);
            this.currentPage = page;

            // Build query parameters
            const params = new URLSearchParams({
                page: page,
                limit: this.perPage,
                ...this.filters
            });

            const response = await this.apiCall('GET', `?${params.toString()}`);

            if (response.success) {
                this.patients = response.data.data || response.data;

                // Handle pagination if available
                if (response.data.pagination) {
                    this.totalPages = Math.ceil(response.data.pagination.total / this.perPage);
                }

                this.renderPatients();
                this.renderPagination();
            } else {
                this.showToast(response.error || 'Failed to load patients', 'error');
            }
        } catch (error) {
            console.error('Load patients error:', error);
            this.showToast('Failed to load patients', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Get single patient by ID
     */
    async getPatientById(patientId) {
        try {
            const response = await this.apiCall('GET', `/${patientId}`);

            if (response.success) {
                return response.data;
            } else {
                this.showToast(response.error || 'Patient not found', 'error');
                return null;
            }
        } catch (error) {
            console.error('Get patient error:', error);
            this.showToast('Failed to get patient details', 'error');
            return null;
        }
    }

    /**
     * Create new patient
     */
    async createPatient(data) {
        try {
            this.showLoading(true);

            const response = await this.apiCall('POST', '', data);

            if (response.success) {
                this.showToast('Patient created successfully', 'success');
                this.closeModal();
                this.loadPatients(this.currentPage);
                return response.data;
            } else {
                this.showToast(response.error || 'Failed to create patient', 'error');
                return null;
            }
        } catch (error) {
            console.error('Create patient error:', error);
            this.showToast('Failed to create patient', 'error');
            return null;
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Update existing patient
     */
    async updatePatient(patientId, data) {
        try {
            this.showLoading(true);

            const response = await this.apiCall('PUT', `/${patientId}`, data);

            if (response.success) {
                this.showToast('Patient updated successfully', 'success');
                this.closeModal();
                this.loadPatients(this.currentPage);
                return response.data;
            } else {
                this.showToast(response.error || 'Failed to update patient', 'error');
                return null;
            }
        } catch (error) {
            console.error('Update patient error:', error);
            this.showToast('Failed to update patient', 'error');
            return null;
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Delete patient
     */
    async deletePatient(patientId) {
        if (!confirm('Are you sure you want to delete this patient?')) {
            return;
        }

        try {
            this.showLoading(true);

            const response = await this.apiCall('DELETE', `/${patientId}`);

            if (response.success) {
                this.showToast('Patient deleted successfully', 'success');
                // Remove the patient from the local array immediately.
                // A full reload would bring the record back because the backend
                // does a soft-delete (status = 'Inactive') and the table shows all statuses.
                this.patients = this.patients.filter(p => p.patient_id !== patientId);
                this.renderPatients();
                this.renderPagination();
            } else {
                this.showToast(response.error || 'Failed to delete patient', 'error');
            }
        } catch (error) {
            console.error('Delete patient error:', error);
            this.showToast('Failed to delete patient', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Redirect to appointment booking with patient ID
     */
    bookAppointment(patientId) {
        window.location.href = `appointment_management.php?patient_id=${patientId}&action=new`;
    }

    /**
     * Search patients
     */
    async searchPatients(query) {
        this.filters.search = query;
        this.loadPatients(1);
    }

    /**
     * Apply filters
     */
    applyFilters() {
        const genderFilter = document.getElementById('genderFilter');
        const statusFilter = document.getElementById('statusFilter');

        this.filters = {};

        if (genderFilter && genderFilter.value) {
            this.filters.gender = genderFilter.value;
        }

        if (statusFilter && statusFilter.value) {
            this.filters.status = statusFilter.value;
        }

        this.loadPatients(1);
    }

    /**
     * Render patients table
     */
    renderPatients() {
        const tableBody = document.getElementById('patientTableBody');

        if (!tableBody) {
            console.error('Patient table body not found');
            return;
        }

        if (this.patients.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="10" class="text-center p-4">
                        <div class="empty-state">
                            <i class="fas fa-users" style="font-size: 48px; color: #d1d5db;"></i>
                            <h3>No Patients Found</h3>
                            <p>Start by adding your first patient</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tableBody.innerHTML = this.patients.map(patient => `
            <tr>
                <td>${patient.patient_id || '-'}</td>
                <td>${patient.full_name || (patient.first_name + ' ' + patient.last_name)}</td>
                <td>${patient.age || '-'}</td>
                <td>${patient.sex || '-'}</td>
                <td>${patient.phone || '-'}</td>
                <td>${patient.aadhar || '-'}</td>
                <td>${patient.city || '-'}</td>
                <td>${patient.date || '-'}</td>
                <td>
                    <span class="status-badge status-${(patient.latest_appointment_status == '1' ? 'active' : (patient.latest_appointment_status == '0' ? 'completed' : 'inactive'))}">
                        ${patient.latest_appointment_status == '1' ? 'Active' : (patient.latest_appointment_status == '0' ? 'Completed' : 'New Patient')}
                    </span>
                </td>
                <td style="text-align: center;">
                    <div class="action-grid" style="display: flex; gap: 8px; justify-content: center;">
                        <button class="action-icon reschedule" onclick="patientManager.bookAppointment('${patient.patient_id}')" title="Book Appointment" style="color: #0d9488; background: #f0fdfa; border: 1px solid #99f6e4;">
                            <i class="fas fa-calendar-plus"></i>
                        </button>
                        <button class="action-icon edit" onclick="patientManager.openModal('edit', '${patient.patient_id}')" title="Edit" style="color: #1f6b4a; background: #f0f9fa; border: 1px solid #b2ebf2;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-icon delete" onclick="patientManager.deletePatient('${patient.patient_id}')" title="Delete" style="color: #ef4444; background: #fef2f2; border: 1px solid #fecaca;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    /**
     * Render pagination
     */
    renderPagination() {
        const paginationInfo = document.getElementById('showingFrom');
        const totalRecords = document.getElementById('totalRecords');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        if (paginationInfo && totalRecords) {
            const from = (this.currentPage - 1) * this.perPage + 1;
            const to = Math.min(this.currentPage * this.perPage, this.patients.length);

            document.getElementById('showingFrom').textContent = from;
            document.getElementById('showingTo').textContent = to;
            totalRecords.textContent = this.patients.length;
        }

        if (prevBtn) {
            prevBtn.disabled = this.currentPage === 1;
        }

        if (nextBtn) {
            nextBtn.disabled = this.currentPage >= this.totalPages;
        }
    }

    /**
     * Open modal for create/edit
     */
    async openModal(mode, patientId = null) {
        const modal = document.getElementById('patientModal');
        const modalTitle = document.getElementById('modalTitle');
        const form = document.getElementById('patientForm');

        if (!modal || !form) {
            console.error('Modal or form not found');
            return;
        }

        // Reset form
        form.reset();
        
        // Hide dynamic fields on reset
        $('#existingDoctorSelect').val(null).trigger('change');
        const extraDetailsDiv = document.getElementById('doctorExtraDetailsDiv');
        if (extraDetailsDiv) extraDetailsDiv.style.display = 'none';
        
        if (typeof toggleDoctorReferral === 'function') {
            toggleDoctorReferral();
        }

        if (mode === 'edit' && patientId) {
            modalTitle.textContent = 'Edit Patient';

            // Load patient data
            const patient = await this.getPatientById(patientId);

            if (patient) {
                // Populate form fields
                document.getElementById('editPatientId').value = patient.patient_id;
                const dispId = document.getElementById('displayPatientId');
                if (dispId) dispId.value = patient.patient_id;

                // Fields that are handled by the pincode logic (skip normal fill)
                const pincodeManaged = ['pincode', 'country', 'state', 'district', 'city', 'area'];

                Object.keys(patient).forEach(key => {
                    if (pincodeManaged.includes(key)) return; // handled below
                    const input = form.querySelector(`[name="${key}"]`);
                    if (input) {
                        if (input.type === 'radio') {
                            const radio = form.querySelector(`[name="${key}"][value="${patient[key]}"]`);
                            if (radio) radio.checked = true;
                        } else {
                            input.value = patient[key] || '';
                            // Dispatch change event to trigger dynamic UI updates
                            input.dispatchEvent(new Event('change'));
                        }
                    }
                });

                // Pre-fill address dropdowns using API
                await this._prefillAddressDropdowns(patient);
            }
        } else {
            modalTitle.textContent = 'Add New Patient';
            document.getElementById('editPatientId').value = '';
            const dispId = document.getElementById('displayPatientId');
            if (dispId) dispId.value = 'PID-AUTO';
        }

        modal.classList.remove('hidden');
    }

    /**
     * Close modal
     */
    closeModal() {
        const modal = document.getElementById('patientModal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    /**
     * Handle form submission
     */
    async handleFormSubmit(event) {
        event.preventDefault();

        const form = event.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // Strip spaces from Aadhar for backend validation
        if (data.aadhar) {
            data.aadhar = data.aadhar.replace(/\s/g, '');
        }

        // Parse numeric fields for validation
        if (data.age !== undefined && data.age !== '') {
            const parsedAge = parseInt(data.age, 10);
            if (!isNaN(parsedAge)) {
                data.age = parsedAge;
            } else {
                delete data.age;
            }
        } else {
            delete data.age;
        }

        // Remove patient_id if present (generated by backend or passed in URL)
        delete data.patient_id;
        
        // Handle saving new referred doctor
        if (data.referral_type === 'Doctor' && data.is_new_doctor === '1' && data.referral_name) {
            try {
                this.showLoading(true);
                const docResponse = await fetch('/GM_HMS/api/referred-doctors', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        doctor_name: data.referral_name,
                        phone: data.ref_doctor_phone,
                        email: data.ref_doctor_email
                    })
                });
                const docResult = await docResponse.json();
                if (!docResult.success) {
                    this.showToast('Failed to save new doctor', 'error');
                    this.showLoading(false);
                    return;
                }
                
                // Fetch the updated doctor list in background so it's ready next time
                if (typeof fetchReferredDoctors === 'function') {
                    fetchReferredDoctors();
                }
            } catch (err) {
                console.error("Error saving referred doctor:", err);
            } finally {
                this.showLoading(false);
            }
        }

        const patientId = document.getElementById('editPatientId').value;

        if (patientId) {
            // UPDATE (Edit) mode:
            // Send ALL fields including empty strings — the backend converts '' to NULL
            // so clearing a previously-filled field (e.g. Aadhar) actually clears it in DB.
            await this.updatePatient(patientId, data);
        } else {
            // CREATE (New) mode:
            // Remove empty fields — no point sending nulls for brand-new records.
            Object.keys(data).forEach(key => {
                if (data[key] === '') {
                    delete data[key];
                }
            });
            await this.createPatient(data);
        }
    }

    /**
     * Attach event listeners
     */
    attachEventListeners() {
        // Search input
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.searchPatients(e.target.value);
                }, 500);
            });
        }

        // Filter dropdowns
        const genderFilter = document.getElementById('genderFilter');
        const statusFilter = document.getElementById('statusFilter');

        if (genderFilter) {
            genderFilter.addEventListener('change', () => this.applyFilters());
        }

        if (statusFilter) {
            statusFilter.addEventListener('change', () => this.applyFilters());
        }

        // Page size selector
        const pageSizeSelect = document.getElementById('pageSizeSelect');
        if (pageSizeSelect) {
            pageSizeSelect.addEventListener('change', (e) => {
                this.perPage = parseInt(e.target.value);
                this.loadPatients(1);
            });
        }

        // Form submission — only attach once to prevent double-submit on re-init
        const form = document.getElementById('patientForm');
        if (form && !this._listenersAttached) {
            form.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }

        // Birth date change - calculate age
        const birthDateInput = document.getElementById('birthDate');
        const ageInput = document.getElementById('age');

        if (birthDateInput && ageInput) {
            birthDateInput.addEventListener('change', (e) => {
                const birthDate = new Date(e.target.value);
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();

                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }

                ageInput.value = age;
            });
        }

        // Registration Fee Calculation
        const regFeeInput = document.getElementById('fee_amount');
        const regDiscountInput = document.getElementById('discount');
        const regTotalInput = document.getElementById('total_amount');

        if (regFeeInput && regDiscountInput && regTotalInput) {
            const calculateRegTotal = () => {
                const fee = parseFloat(regFeeInput.value) || 0;
                const discount = parseFloat(regDiscountInput.value) || 0;
                regTotalInput.value = Math.max(0, fee - discount);
            };

            regFeeInput.addEventListener('input', calculateRegTotal);
            regDiscountInput.addEventListener('input', calculateRegTotal);
        }

        // Title → Gender auto-select
        const titleSelect = document.querySelector('#patientForm select[name="title"]');
        if (titleSelect) {
            titleSelect.addEventListener('change', () => {
                const titleGenderMap = {
                    'Mr':         'Male',
                    'Master':     'Male',
                    'Baby Boy':   'Male',
                    'Mrs':        'Female',
                    'Miss':       'Female',
                    'Baby Girl':  'Female',
                };

                const gender = titleGenderMap[titleSelect.value];
                if (gender) {
                    const radio = document.querySelector(`#patientForm input[name="sex"][value="${gender}"]`);
                    if (radio) {
                        radio.checked = true;
                    }
                }
                // Dr, B/O, NA, Other → leave gender as-is
            });
        }

        // Mark listeners as attached so this block never runs again
        this._listenersAttached = true;

        // Duplicate Check (Aadhar/Phone)
        const aadharInput = document.getElementById('patientAadhar');
        const phoneInput = document.getElementById('patientPhone');

        if (aadharInput && phoneInput) {
            const checkDuplicate = async (type, value) => {
                if (!value || (type === 'aadhar' && value.length < 12) || (type === 'phone' && value.length < 10)) return;

                try {
                    const response = await this.apiCall('GET', `/check-duplicate?${type}=${value}`);
                    if (response.success && response.data.exists) {
                        const modal = document.getElementById('duplicateModal');
                        const info = document.getElementById('duplicateInfo');
                        const bookingBtn = document.getElementById('proceedToBookingBtn');

                        if (modal && info && bookingBtn) {
                            info.innerHTML = `<strong>${response.data.name}</strong> (${response.data.patient_id}) already exists in our system.<br><br>Please proceed to appointment booking for this patient.`;

                            bookingBtn.onclick = () => {
                                window.location.href = `appointment_management.php?patient_id=${response.data.patient_id}&action=new`;
                            };

                            modal.classList.remove('hidden');
                        }

                        // Note: We intentionally do NOT clear the input here.
                        // The modal already warns the user. Clearing the field
                        // would cause the phone to be missing when saving a new patient.
                    }
                } catch (error) {
                    console.error('Duplicate check error:', error);
                }
            };

            let aadharTimeout, phoneTimeout;

            aadharInput.addEventListener('input', (e) => {
                // Remove all non-digits
                let value = e.target.value.replace(/\D/g, '');

                // Add space after every 4 digits
                let formattedValue = '';
                for (let i = 0; i < value.length && i < 12; i++) {
                    if (i > 0 && i % 4 === 0) formattedValue += ' ';
                    formattedValue += value[i];
                }

                e.target.value = formattedValue;

                // Strip spaces for duplicate check logic
                const pureValue = value;

                clearTimeout(aadharTimeout);
                aadharTimeout = setTimeout(() => checkDuplicate('aadhar', pureValue), 600);
            });

            phoneInput.addEventListener('input', (e) => {
                // Ensure only numbers
                e.target.value = e.target.value.replace(/\D/g, '');

                clearTimeout(phoneTimeout);
                phoneTimeout = setTimeout(() => checkDuplicate('phone', e.target.value), 600);
            });
        }

        // Pincode auto-fill
        this.attachPincodeListener();
    }

    /**
     * Pincode → Address auto-fill using postalpincode.in API
     * Populates Country, State (text) and District, City, Area (selects)
     */
    attachPincodeListener() {
        const pinInput   = document.getElementById('patientPincode');
        const statusEl   = document.getElementById('pincodeStatus');
        const messageEl  = document.getElementById('pincodeMessage');
        const countryEl  = document.getElementById('patientCountry');
        const stateEl    = document.getElementById('patientState');
        const regionEl   = document.getElementById('patientRegion');
        const divisionEl = document.getElementById('patientDivision');
        const districtEl = document.getElementById('patientDistrict');
        const cityEl     = document.getElementById('patientCity');

        // Area search-bar elements
        const areaSearch   = document.getElementById('patientAreaSearch');
        const areaValue    = document.getElementById('patientAreaValue');
        const areaDropdown = document.getElementById('patientAreaDropdown');
        const areaClear    = document.getElementById('patientAreaClear');

        if (!pinInput) return;

        // All areas available for the current district+city selection
        let allAreaOptions = [];

        const setStatus = (icon, msg, color) => {
            if (statusEl)  statusEl.textContent = icon;
            if (messageEl) { messageEl.textContent = msg; messageEl.style.color = color; }
        };

        // ── Area search-bar helpers ───────────────────────────────────────────

        const showAreaDropdown = (items) => {
            if (!areaDropdown) return;
            areaDropdown.innerHTML = '';
            if (items.length === 0) {
                areaDropdown.innerHTML = '<div style="padding:10px 14px;color:#9ca3af;font-size:13px;">No results found</div>';
            } else {
                items.forEach(v => {
                    const row = document.createElement('div');
                    row.textContent = v;
                    row.style.cssText = 'padding:10px 14px;cursor:pointer;font-size:13px;color:#1f2937;border-bottom:1px solid #f3f4f6;transition:background 0.15s;';
                    row.addEventListener('mouseenter', () => row.style.background = '#f0fdfa');
                    row.addEventListener('mouseleave', () => row.style.background = '');
                    row.addEventListener('mousedown', (e) => {
                        e.preventDefault(); // keep focus on search input
                        if (areaSearch) areaSearch.value = v;
                        if (areaValue)  areaValue.value  = v;
                        if (areaClear)  areaClear.style.display = 'block';
                        areaDropdown.style.display = 'none';
                        // Highlight input border
                        if (areaSearch) areaSearch.style.borderColor = '#1f6b4a';
                    });
                    areaDropdown.appendChild(row);
                });
            }
            areaDropdown.style.display = 'block';
        };

        const hideAreaDropdown = () => {
            if (areaDropdown) areaDropdown.style.display = 'none';
        };

        /** Populate area search-bar with a new set of options */
        const fillAreaSearchBar = (areas, savedValue = '') => {
            allAreaOptions = areas;
            if (areaSearch) {
                areaSearch.placeholder = areas.length === 0
                    ? 'Type area manually or enter pincode for suggestions'
                    : 'Type to search area...';
            }
            // Pre-select saved value in edit mode
            if (savedValue && areas.includes(savedValue)) {
                if (areaSearch) areaSearch.value = savedValue;
                if (areaValue)  areaValue.value  = savedValue;
                if (areaClear)  areaClear.style.display = 'block';
            } else {
                if (areaSearch) { areaSearch.value = ''; areaSearch.style.borderColor = ''; }
                if (areaValue)  areaValue.value  = '';
                if (areaClear)  areaClear.style.display = 'none';
            }
            hideAreaDropdown();
        };

        // Global clear handler (called from onclick in HTML)
        window._clearAreaSearch = () => {
            if (areaSearch) { areaSearch.value = ''; areaSearch.focus(); }
            if (areaValue)  areaValue.value  = '';
            if (areaClear)  areaClear.style.display = 'none';
            if (areaSearch) areaSearch.style.borderColor = '';
            showAreaDropdown(allAreaOptions); // show all options again
        };

        // Search-bar live filtering
        if (areaSearch) {
            areaSearch.addEventListener('input', () => {
                const q = areaSearch.value.toLowerCase().trim();
                if (areaValue) areaValue.value = '';         // clear selection on typing
                if (areaClear) areaClear.style.display = q ? 'block' : 'none';
                const filtered = q
                    ? allAreaOptions.filter(v => v.toLowerCase().includes(q))
                    : allAreaOptions;
                showAreaDropdown(filtered);
            });

            areaSearch.addEventListener('focus', () => {
                if (allAreaOptions.length > 0) {
                    const q = areaSearch.value.toLowerCase().trim();
                    showAreaDropdown(q
                        ? allAreaOptions.filter(v => v.toLowerCase().includes(q))
                        : allAreaOptions);
                }
            });

            areaSearch.addEventListener('blur', () => {
                // Small delay so mousedown on option fires first
                setTimeout(hideAreaDropdown, 200);
            });
        }

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (areaDropdown && !areaDropdown.contains(e.target) && e.target !== areaSearch) {
                hideAreaDropdown();
            }
        });

        // ── Datalist helper (for District & City) ────────────────────────────
        const fillDatalist = (datalistId, values) => {
            const dl = document.getElementById(datalistId);
            if (!dl) return;
            dl.innerHTML = '';
            values.forEach(v => {
                const opt = document.createElement('option');
                opt.value = v;
                dl.appendChild(opt);
            });
        };

        const resetDropdowns = () => {
            // Clear text inputs for district & city
            if (districtEl) { districtEl.value = ''; fillDatalist('districtDatalist', []); }
            if (cityEl)     { cityEl.value     = ''; fillDatalist('cityDatalist',     []); }
            // Reset area search bar options but keep it enabled for manual entry
            fillAreaSearchBar([], '');
            if (countryEl)  countryEl.value  = '';
            if (stateEl)    stateEl.value    = '';
            if (regionEl)   regionEl.value   = '';
            if (divisionEl) divisionEl.value = '';
        };

        // ── Pincode Stored data ───────────────────────────────────────────────
        let postOffices = [];

        const getDistricts = () => [...new Set(postOffices.map(p => p.District).filter(Boolean))].sort();

        const getCities = (district) => {
            const src = district ? postOffices.filter(p => p.District === district) : postOffices;
            return [...new Set(src.map(p => p.Block).filter(Boolean))].sort();
        };

        const getAreas = (district, city) => {
            let src = postOffices;
            if (district) src = src.filter(p => p.District === district);
            if (city)     src = src.filter(p => p.Block     === city);
            return [...new Set(src.map(p => p.Name).filter(Boolean))].sort();
        };

        // ── Cascade listeners ─────────────────────────────────────────────────
        if (districtEl) {
            // 'input' works for both selecting from datalist and free typing
            districtEl.addEventListener('input', () => {
                const d = districtEl.value;
                fillDatalist('cityDatalist', getCities(d));
                if (cityEl) cityEl.value = '';
                fillAreaSearchBar(getAreas(d, ''));
            });
        }

        if (cityEl) {
            cityEl.addEventListener('change', () => {
                const d = districtEl ? districtEl.value : '';
                const c = cityEl.value;
                fillAreaSearchBar(getAreas(d, c));
            });
        }

        // ── Main pincode listener ─────────────────────────────────────────────
        let pinTimeout;
        pinInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '');
            const pin = e.target.value;

            if (pin.length < 6) {
                setStatus('', '', '');
                resetDropdowns();
                return;
            }

            setStatus('⏳', 'Searching...', '#6b7280');
            clearTimeout(pinTimeout);
            pinTimeout = setTimeout(async () => {
                try {
                    const res  = await fetch(`https://api.postalpincode.in/pincode/${pin}`);
                    const json = await res.json();

                    if (!json || json[0].Status !== 'Success' || !json[0].PostOffice?.length) {
                        setStatus('❌', 'Invalid or unknown pincode', '#ef4444');
                        resetDropdowns();
                        return;
                    }

                    postOffices = json[0].PostOffice;

                    if (countryEl)  countryEl.value  = postOffices[0].Country  || 'India';
                    if (stateEl)    stateEl.value    = postOffices[0].State    || '';
                    if (regionEl)   regionEl.value   = postOffices[0].Region   || '';
                    if (divisionEl) divisionEl.value = postOffices[0].Division || '';

                    // Auto-fill district datalist and set value
                    const districts = getDistricts();
                    fillDatalist('districtDatalist', districts);
                    if (districts.length >= 1 && districtEl) districtEl.value = districts[0];

                    // Cascade city datalist
                    const autoDistrict = districtEl ? districtEl.value : '';
                    fillDatalist('cityDatalist', getCities(autoDistrict));

                    // Populate area search bar
                    fillAreaSearchBar(getAreas(autoDistrict, ''));

                    setStatus('✅', `${postOffices.length} post offices found`, '#059669');

                } catch (err) {
                    console.error('Pincode API error:', err);
                    setStatus('❌', 'Failed to fetch pincode data', '#ef4444');
                    resetDropdowns();
                }
            }, 600);
        });

        // Store for edit-mode
        this._fillAreaSearchBar = fillAreaSearchBar;
        this._getAreasForEdit   = getAreas;
    }

    async _prefillAddressDropdowns(patient) {
        const pin = patient.pincode;
        if (!pin || pin.length !== 6) return;

        const pinInput = document.getElementById('patientPincode');
        if (pinInput) pinInput.value = pin;

        const statusEl  = document.getElementById('pincodeStatus');
        const messageEl = document.getElementById('pincodeMessage');
        if (statusEl)  statusEl.textContent = '⏳';
        if (messageEl) { messageEl.textContent = 'Loading address...'; messageEl.style.color = '#6b7280'; }

        try {
            const res  = await fetch(`https://api.postalpincode.in/pincode/${pin}`);
            const json = await res.json();

            if (!json || json[0].Status !== 'Success' || !json[0].PostOffice?.length) return;

            const postOffices = json[0].PostOffice;

            const countryEl  = document.getElementById('patientCountry');
            const stateEl    = document.getElementById('patientState');
            const regionEl   = document.getElementById('patientRegion');
            const divisionEl = document.getElementById('patientDivision');
            const districtEl = document.getElementById('patientDistrict');
            const cityEl     = document.getElementById('patientCity');

            if (countryEl)  countryEl.value  = postOffices[0].Country  || 'India';
            if (stateEl)    stateEl.value    = postOffices[0].State    || '';
            if (regionEl)   regionEl.value   = postOffices[0].Region   || '';
            if (divisionEl) divisionEl.value = postOffices[0].Division || '';

            const unique = (arr) => [...new Set(arr.filter(Boolean))].sort();

            const districts = unique(postOffices.map(p => p.District));
            const cities    = unique(postOffices.filter(p => !patient.district || p.District === patient.district).map(p => p.Block));
            let areas = [];
            
            if (this._getAreasForEdit) {
                // If attachPincodeListener was called, we have this function
                areas = this._getAreasForEdit(patient.district || '', patient.city || '');
            } else {
                areas = unique(postOffices.filter(p => (!patient.district || p.District === patient.district) && (!patient.city || p.Block === patient.city)).map(p => p.Name));
            }

            // Fill datalists
            const fillDatalist = (datalistId, values) => {
                const dl = document.getElementById(datalistId);
                if (!dl) return;
                dl.innerHTML = '';
                values.forEach(v => {
                    const opt = document.createElement('option');
                    opt.value = v;
                    dl.appendChild(opt);
                });
            };

            fillDatalist('districtDatalist', districts);
            fillDatalist('cityDatalist', cities);

            // Set input values explicitly
            if (districtEl) districtEl.value = patient.district || '';
            if (cityEl)     cityEl.value     = patient.city || '';

            // Handle Area (search bar)
            if (this._fillAreaSearchBar) {
                this._fillAreaSearchBar(areas, patient.area || '');
            }

            if (statusEl)  statusEl.textContent = '✅';
            if (messageEl) { messageEl.textContent = 'Address loaded'; messageEl.style.color = '#059669'; }

        } catch (err) {
            console.error('Edit-mode pincode error:', err);
        }
    }

    /**
     * Change page
     */
    changePage(direction) {
        const newPage = this.currentPage + direction;

        if (newPage >= 1 && newPage <= this.totalPages) {
            this.loadPatients(newPage);
        }
    }

    /**
     * Show/hide loading overlay
     */
    showLoading(show) {
        const skeleton = document.getElementById('loadingSkeleton');
        const tableWrapper = document.getElementById('patientTableWrapper');

        if (skeleton && tableWrapper) {
            if (show) {
                skeleton.classList.remove('hidden');
                tableWrapper.classList.add('hidden');
            } else {
                skeleton.classList.add('hidden');
                tableWrapper.classList.remove('hidden');
            }
        }
    }

    /**
     * Show toast notification
     */
    showToast(message, type = 'success') {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast ${type === 'success' ? 'toast-success' : 'toast-error'}`;
        toast.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        `;

        // Add to container or body
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        container.appendChild(toast);

        // Remove after 3 seconds
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    /**
     * Make API call
     */
    async apiCall(method, endpoint, data = null) {
        const url = this.apiBase + endpoint;

        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            }
        };

        if (data && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, options);
            return await response.json();
        } catch (error) {
            console.error('API call error:', error);
            throw error;
        }
    }
}

// Global functions for onclick handlers
function changePage(direction) {
    if (window.patientManager) {
        window.patientManager.changePage(direction);
    }
}

function openAddPatientModal() {
    if (window.patientManager) {
        window.patientManager.openModal('create');
    }
}

function closePatientModal() {
    if (window.patientManager) {
        window.patientManager.closeModal();
    }
}

function closeModalOnBackdrop(event) {
    if (event.target.id === 'patientModal') {
        closePatientModal();
    }
}
