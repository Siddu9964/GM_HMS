/**
 * ot_view/assets/js/ot_main.js
 * Global JS helper for the GM OT Module
 */

const OT = (() => {

    // ── Helpers ────────────────────────────────────────────────────────────
    const esc = (str) => String(str ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    const statusBadge = (status) => {
        const map = {
            'Scheduled' : ['ot-badge-scheduled',  'fa-calendar-check'],
            'Ongoing'   : ['ot-badge-ongoing',     'fa-spinner'],
            'Completed' : ['ot-badge-completed',   'fa-check-circle'],
            'Cancelled' : ['ot-badge-cancelled',   'fa-times-circle'],
            'Postponed' : ['ot-badge-postponed',   'fa-pause-circle'],
            'Preponed'  : ['ot-badge-preponed',    'fa-fast-forward'],
        };
        const [cls, icon] = map[status] ?? ['ot-badge-scheduled', 'fa-circle'];
        return `<span class="ot-badge ${cls}"><i class="fas ${icon}"></i>${esc(status)}</span>`;
    };

    const showToast = (msg, type = 'success') => {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                position: 'center',
                toast: false,
                icon: type === 'success' ? 'success' : 'error',
                title: type === 'success' ? 'Success' : 'Error',
                text: msg,
                confirmButtonColor: '#1f6b4a',
                timer: type === 'success' ? 3000 : undefined,
                customClass: {
                    popup: 'shadow rounded-3'
                }
            });
        } else {
            alert(msg);
        }
    };

    // ── OT Rooms ───────────────────────────────────────────────────────────
    const loadRooms = () => {
        const sel = document.getElementById('field-ot-room');
        if (!sel) return;
        fetch(API_BASE + 'ot/rooms')
            .then(r => r.json())
            .then(res => {
                if (res.success && res.data && res.data.length > 0) {
                    sel.innerHTML = '<option value="">Select OT Room...</option>' +
                        res.data.map(r => {
                            const label = r.room_name;
                            return `<option value="${esc(label)}">${esc(label)}</option>`;
                        }).join('');
                } else {
                    // Fallback: allow manual entry
                    sel.outerHTML = '<input type="text" id="field-ot-room" class="ot-form-control" placeholder="Enter OT Room name" required>';
                }
            })
            .catch(() => {
                sel.outerHTML = '<input type="text" id="field-ot-room" class="ot-form-control" placeholder="Enter OT Room name" required>';
            });
    };

    // ── Load Doctors ─────────────────────────────────────────────────────
    let doctorsData = [];
    const loadDoctors = () => {
        fetch(API_BASE + 'doctors?limit=200')
            .then(r => r.json())
            .then(res => {
                if (res.success && res.data) {
                    doctorsData = res.data;
                }
            })
            .catch(() => console.error('Failed to load doctors list'));
    };

    // ── Load Departments ───────────────────────────────────────────────────
    let departmentsData = [];
    const loadDepartments = () => {
        fetch(API_BASE + 'ot/departments')
            .then(r => r.json())
            .then(res => {
                if (res.success && res.data) {
                    departmentsData = res.data;
                }
            })
            .catch(() => console.error('Failed to load departments list'));
    };

    const loadSurgeries = () => {
        const tbody = document.getElementById('surgeries-tbody');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="11" class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading...</td></tr>';

        const date   = document.getElementById('filter-date')?.value   ?? '';
        const status = document.getElementById('filter-status')?.value ?? '';
        const search = document.getElementById('filter-search')?.value ?? '';

        const params = new URLSearchParams({ date, status, search });
        fetch(API_BASE + 'ot/surgeries?' + params)
            .then(r => r.json())
            .then(res => {
                if (!res.success) { tbody.innerHTML = '<tr><td colspan="11" class="text-center py-4 text-danger">Failed to load.</td></tr>'; return; }
                const rows = res.data ?? [];
                const counter = document.getElementById('record-count');
                if (counter) counter.textContent = rows.length + ' record(s)';

                if (rows.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="11" class="text-center py-4 text-muted">No surgeries found.</td></tr>';
                    return;
                }
                tbody.innerHTML = rows.map((s, i) => `
                    <tr>
                        <td>${i + 1}</td>
                        <td>
                            <strong>${esc(s.patient_name)}</strong>
                            <br><small class="text-muted">${esc(s.patient_id)}</small>
                        </td>
                        <td>${esc(s.surgery_name)}</td>
                        <td>
                            <ul class="list-unstyled mb-0" style="font-size:0.85em;">
                                ${(() => {
                                    try {
                                        const types = JSON.parse(s.type);
                                        const names = JSON.parse(s.name);
                                        return types.map((t, idx) => `<li><span class="text-muted">${esc(t)}:</span> <strong>${esc(names[idx] || '')}</strong></li>`).join('');
                                    } catch(e) {
                                        return `<li><span class="text-muted">${esc(s.type)}:</span> <strong>${esc(s.name)}</strong></li>`;
                                    }
                                })()}
                            </ul>
                        </td>
                        <td>${esc(s.ot_room_name)}</td>
                        <td>${esc(s.schedule_date)}</td>
                        <td>
                            ${esc(s.start_time)}
                            <button class="btn btn-sm ms-1 p-0 border-0 bg-transparent" onclick="OT.quickEditTime(${s.id}, 'start', '${s.start_time}')" title="Edit Start Time" style="color: var(--d-green-40);">
                                <i class="fas fa-pencil-alt" style="font-size:0.75rem;"></i>
                            </button>
                        </td>
                        <td>
                            ${esc(s.end_time ?? '—')}
                            <button class="btn btn-sm ms-1 p-0 border-0 bg-transparent" onclick="OT.quickEditTime(${s.id}, 'end', '${s.end_time || ''}')" title="Edit End Time" style="color: var(--d-green-40);">
                                <i class="fas fa-pencil-alt" style="font-size:0.75rem;"></i>
                            </button>
                        </td>
                        <td>${statusBadge(s.status)}</td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <button class="ot-btn ot-btn-outline ot-btn-sm" onclick="OT.openEditModal(${s.id})" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="ot-btn ot-btn-outline ot-btn-sm" onclick="OT.openStatusModal(${s.id}, '${esc(s.status)}')" title="Update Status"
                                    style="border-color:var(--ot-accent);color:var(--ot-accent);">
                                    <i class="fas fa-exchange-alt"></i>
                                </button>
                                <button class="ot-btn ot-btn-sm" onclick="OT.openDeleteModal(${s.id})" title="Delete"
                                    style="background:#fee2e2;color:#991b1b;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            })
            .catch(() => {
                tbody.innerHTML = '<tr><td colspan="11" class="text-center py-4 text-danger">Error loading surgeries.</td></tr>';
            });
    };

    // ── Add Modal ──────────────────────────────────────────────────────────
    const openAddModal = () => {
        document.getElementById('surgery-id').value         = '';
        document.getElementById('field-patient-id').value   = '';
        document.getElementById('field-patient-name').value = '';
        document.getElementById('staff-container').innerHTML = '';
        addStaffRow(); // Add one empty row by default
        document.getElementById('field-surgery-name').value = '';
        document.getElementById('field-department').value   = '';
        document.getElementById('field-anesthesia-type').value = '';
        document.getElementById('field-date').value         = new Date().toISOString().split('T')[0];
        document.getElementById('field-start-time').value   = '';
        document.getElementById('field-end-time').value     = '';
        document.getElementById('field-description').value  = '';
        const otRoom = document.getElementById('field-ot-room');
        if (otRoom) otRoom.value = '';
        document.getElementById('modal-title').textContent  = 'Schedule Surgery';
        document.getElementById('save-btn-text').textContent = 'Save Surgery';
        document.getElementById('form-error').classList.add('d-none');
        new bootstrap.Modal(document.getElementById('surgeryModal')).show();
    };

    // ── Edit Modal ─────────────────────────────────────────────────────────
    const openEditModal = (id) => {
        fetch(API_BASE + 'ot/surgeries/' + id)
            .then(r => r.json())
            .then(res => {
                if (!res.success) { showToast('Failed to load surgery', 'error'); return; }
                const s = res.data;
                document.getElementById('surgery-id').value         = s.id;
                document.getElementById('field-patient-id').value   = s.patient_id   ?? '';
                document.getElementById('field-patient-name').value = s.patient_name ?? '';
                
                document.getElementById('staff-container').innerHTML = '';
                try {
                    const types = JSON.parse(s.type || '[]');
                    const names = JSON.parse(s.name || '[]');
                    if (types.length === 0) addStaffRow();
                    types.forEach((t, idx) => addStaffRow(t, names[idx] || ''));
                } catch(e) {
                    addStaffRow(s.type, s.name);
                }

                document.getElementById('field-surgery-name').value = s.surgery_name ?? '';
                document.getElementById('field-department').value   = s.department ?? '';
                document.getElementById('field-anesthesia-type').value = s.anesthesia_type ?? '';
                document.getElementById('field-date').value         = s.schedule_date ?? '';
                document.getElementById('field-start-time').value   = s.start_time   ?? '';
                document.getElementById('field-end-time').value     = s.end_time     ?? '';
                document.getElementById('field-description').value  = s.description  ?? '';
                const otRoom = document.getElementById('field-ot-room');
                if (otRoom) { otRoom.value = s.ot_room_name ?? ''; }
                document.getElementById('modal-title').textContent   = 'Edit Surgery';
                document.getElementById('save-btn-text').textContent = 'Update Surgery';
                document.getElementById('form-error').classList.add('d-none');
                new bootstrap.Modal(document.getElementById('surgeryModal')).show();
            })
            .catch(() => showToast('Error loading surgery', 'error'));
    };

    // ── Save Surgery (Add/Edit) ────────────────────────────────────────────
    const saveSurgery = () => {
        const id          = document.getElementById('surgery-id').value;
        const otRoomEl    = document.getElementById('field-ot-room');
        const errEl       = document.getElementById('form-error');

        // Gather dynamic staff rows
        const types = [];
        const names = [];
        document.querySelectorAll('.staff-row').forEach(row => {
            const t = row.querySelector('.staff-type').value.trim();
            const n = row.querySelector('.staff-name').value.trim();
            if (t && n) { types.push(t); names.push(n); }
        });

        const payload = {
            patient_id:    document.getElementById('field-patient-id').value.trim(),
            patient_name:  document.getElementById('field-patient-name').value.trim(),
            type:          JSON.stringify(types),
            name:          JSON.stringify(names),
            ot_room_name:  otRoomEl ? otRoomEl.value.trim() : '',
            surgery_name:  document.getElementById('field-surgery-name').value.trim(),
            department:    document.getElementById('field-department').value.trim(),
            anesthesia_type: document.getElementById('field-anesthesia-type').value,
            description:   document.getElementById('field-description').value.trim(),
            schedule_date: document.getElementById('field-date').value,
            start_time:    document.getElementById('field-start-time').value,
            end_time:      document.getElementById('field-end-time').value || null,
        };

        if (!payload.patient_name || !payload.schedule_date || !payload.start_time || !payload.surgery_name || types.length === 0) {
            errEl.textContent = 'Please fill in Patient Name, Date, Time, Surgery Name, and at least one Surgeon Name for advance booking.';
            errEl.classList.remove('d-none');
            return;
        }
        errEl.classList.add('d-none');

        const url    = API_BASE + 'ot/surgeries' + (id ? '/' + id : '');
        const method = id ? 'PUT' : 'POST';

        fetch(url, { method, headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) })
            .then(r => r.json())
            .then(res => {
                if (!res.success) { errEl.textContent = res.error ?? 'Failed to save.'; errEl.classList.remove('d-none'); return; }
                bootstrap.Modal.getInstance(document.getElementById('surgeryModal'))?.hide();
                showToast(id ? 'Surgery updated!' : 'Surgery scheduled!');
                loadSurgeries();
            })
            .catch(() => { errEl.textContent = 'Network error. Please try again.'; errEl.classList.remove('d-none'); });
    };

    // ── Status Modal ───────────────────────────────────────────────────────
    const openStatusModal = (id, currentStatus) => {
        document.getElementById('status-surgery-id').value = id;
        document.getElementById('status-select').value     = currentStatus;
        document.getElementById('status-reason').value     = '';
        document.getElementById('status-error').classList.add('d-none');
        onStatusChange();
        new bootstrap.Modal(document.getElementById('statusModal')).show();
    };

    const onStatusChange = () => {
        const status = document.getElementById('status-select').value;
        const requiresReason = ['Cancelled', 'Postponed', 'Preponed'];
        const reasonGroup = document.getElementById('reason-group');
        if (requiresReason.includes(status)) {
            reasonGroup.classList.remove('d-none');
        } else {
            reasonGroup.classList.add('d-none');
        }
    };

    const saveStatus = () => {
        const id     = document.getElementById('status-surgery-id').value;
        const status = document.getElementById('status-select').value;
        const reason = document.getElementById('status-reason').value.trim();
        const errEl  = document.getElementById('status-error');

        const requiresReason = ['Cancelled', 'Postponed', 'Preponed'];
        if (requiresReason.includes(status) && !reason) {
            errEl.textContent = 'A reason note is required for this status.';
            errEl.classList.remove('d-none');
            return;
        }
        errEl.classList.add('d-none');

        fetch(API_BASE + 'ot/surgeries/' + id + '/status', {
            method:  'PUT',
            headers: {'Content-Type': 'application/json'},
            body:    JSON.stringify({ status, status_reason_note: reason || null })
        })
        .then(r => r.json())
        .then(res => {
            if (!res.success) { errEl.textContent = res.error ?? 'Failed.'; errEl.classList.remove('d-none'); return; }
            bootstrap.Modal.getInstance(document.getElementById('statusModal'))?.hide();
            showToast('Status updated to "' + status + '"');
            loadSurgeries();
        })
        .catch(() => { errEl.textContent = 'Network error.'; errEl.classList.remove('d-none'); });
    };

    // ── Delete ─────────────────────────────────────────────────────────────
    const openDeleteModal = (id) => {
        document.getElementById('delete-surgery-id').value = id;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    };

    const confirmDelete = () => {
        const id = document.getElementById('delete-surgery-id').value;
        fetch(API_BASE + 'ot/surgeries/' + id, { method: 'DELETE' })
            .then(r => r.json())
            .then(res => {
                bootstrap.Modal.getInstance(document.getElementById('deleteModal'))?.hide();
                if (res.success) { showToast('Surgery deleted.'); loadSurgeries(); }
                else showToast(res.error ?? 'Failed to delete.', 'error');
            })
            .catch(() => showToast('Network error.', 'error'));
    };

    // ── Search Patient ─────────────────────────────────────────────────────
    let advancedSearchTimer = null;
    const advancedPatientSearch = (query) => {
        clearTimeout(advancedSearchTimer);
        const resultsDiv = document.getElementById('patient-search-results');
        
        if (query.trim().length < 2) {
            resultsDiv.style.display = 'none';
            return;
        }

        advancedSearchTimer = setTimeout(() => {
            fetch(API_BASE + 'billing/opd/search-patients?q=' + encodeURIComponent(query))
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.data && res.data.length > 0) {
                        resultsDiv.innerHTML = res.data.map(p => `
                            <a href="javascript:void(0)" class="dropdown-item py-2" onclick="OT.selectAdvancedPatient('${esc(p.patient_id)}', '${esc(p.patient_name)}')">
                                <div class="d-flex justify-content-between">
                                    <strong>${esc(p.patient_name)}</strong>
                                    <span class="text-muted small">${esc(p.patient_id)}</span>
                                </div>
                                <div class="small text-muted">
                                    <i class="fas fa-phone-alt me-1"></i> ${esc(p.phone || 'N/A')} | 
                                    <i class="fas fa-venus-mars me-1"></i> ${esc(p.sex || '-')} | 
                                    ${esc(p.age || '-')} yrs
                                </div>
                            </a>
                        `).join('');
                        resultsDiv.style.display = 'block';
                    } else {
                        resultsDiv.innerHTML = '<div class="dropdown-item text-muted disabled">No patients found...</div>';
                        resultsDiv.style.display = 'block';
                    }
                })
                .catch(() => {
                    resultsDiv.style.display = 'none';
                });
        }, 400);
    };

    const selectAdvancedPatient = (id, name) => {
        document.getElementById('field-patient-id').value = id;
        document.getElementById('patient-search-results').style.display = 'none';
        // Automatically run the IPD/OPD search check
        searchPatient();
    };

    // Click outside to close dropdown
    document.addEventListener('click', (e) => {
        const resultsDiv = document.getElementById('patient-search-results');
        if (resultsDiv && !e.target.closest('#patient-search-results') && e.target.id !== 'field-patient-id') {
            resultsDiv.style.display = 'none';
        }
    });
    const searchPatient = () => {
        const pidInput = document.getElementById('field-patient-id');
        const nameInput = document.getElementById('field-patient-name');
        const pid = pidInput.value.trim();

        if (!pid) {
            showToast('Please enter a Patient ID to search', 'error');
            return;
        }

        const icon = pidInput.nextElementSibling.querySelector('i');
        icon.className = 'fas fa-spinner fa-spin';

        fetch(API_BASE + 'ot/patient/search/' + encodeURIComponent(pid))
            .then(r => r.json())
            .then(res => {
                icon.className = 'fas fa-search';
                if (res.success && res.data.found) {
                    nameInput.value = res.data.patient_name;
                    if (res.data.source === 'opd') {
                        // Found in patient table but not in ipd_admissions
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                position: 'center',
                                toast: false,
                                icon: 'warning',
                                title: 'Patient Not Admitted',
                                text: 'Patient not in IP but patient registration is done.',
                                confirmButtonColor: '#1f6b4a',
                                customClass: { popup: 'shadow rounded-3' }
                            });
                        } else {
                            alert('Patient not in IP but patient registration is done.');
                        }
                    } else {
                        showToast('Patient details loaded from IPD Admissions.');
                    }
                } else {
                    nameInput.value = '';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            position: 'center',
                            toast: false,
                            icon: 'info',
                            title: 'Patient Not Found',
                            text: 'No matching record found. You can enter the patient name manually.',
                            confirmButtonColor: '#1f6b4a',
                            customClass: { popup: 'shadow rounded-3' }
                        });
                    } else {
                        showToast('Patient not found. Please enter name manually.', 'error');
                    }
                }
            })
            .catch(() => {
                icon.className = 'fas fa-search';
                showToast('Error searching for patient', 'error');
            });
    };

    // ── Staff Team Rows ────────────────────────────────────────────────────
    const addStaffRow = (typeVal = '', nameVal = '') => {
        const container = document.getElementById('staff-container');
        const row = document.createElement('div');
        row.className = 'row g-2 staff-row align-items-center mb-2';
        row.innerHTML = `
            <div class="col-md-5">
                <select class="ot-input-v2 staff-type">
                <option value="">Select role...</option>
                <option value="Surgeon" ${typeVal==='Surgeon'?'selected':''}>Surgeon</option>
                <option value="Asst. Surgeon" ${typeVal==='Asst. Surgeon'?'selected':''}>Asst. Surgeon</option>
                <option value="Anesthetist" ${typeVal==='Anesthetist'?'selected':''}>Anesthetist</option>
                <option value="St. by Anesthetist" ${typeVal==='St. by Anesthetist'?'selected':''}>St. by Anesthetist</option>
                <option value="Scrub Nurse" ${typeVal==='Scrub Nurse'?'selected':''}>Scrub Nurse</option>
                <option value="Circulating Nurse" ${typeVal==='Circulating Nurse'?'selected':''}>Circulating Nurse</option>
                <option value="Technician" ${typeVal==='Technician'?'selected':''}>Technician</option>
                </select>
            </div>
            <div class="col-md-6 position-relative">
                <input type="text" class="ot-input-v2 staff-name" placeholder="Doctor Name" value="${esc(nameVal)}" onkeyup="OT.filterDoctors(this)" onfocus="OT.filterDoctors(this)">
                <div class="doctor-search-results dropdown-menu w-100 shadow-sm" style="display:none; position:absolute; top:100%; left:0; max-height: 250px; overflow-y: auto; z-index: 1050; border-radius: 8px; border: 1px solid var(--d-green-12);"></div>
            </div>
            <div class="col-md-1 text-center">
                <button type="button" class="btn btn-sm remove-staff-btn" onclick="this.closest('.staff-row').remove()" style="background: #fee2e2; color: #991b1b; border: none; border-radius: 8px; padding: 0.4rem 0.6rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        container.appendChild(row);
        
        // Hide remove button if it's the only row
        const rows = container.querySelectorAll('.staff-row');
        if (rows.length === 1) {
            rows[0].querySelector('.remove-staff-btn').style.display = 'none';
        } else {
            rows.forEach(r => r.querySelector('.remove-staff-btn').style.display = 'inline-block');
        }
    };

    const filterDoctors = (inputEl) => {
        const query = inputEl.value.toLowerCase().trim();
        const resultsDiv = inputEl.nextElementSibling;
        
        let filtered = doctorsData;
        if (query) {
            filtered = doctorsData.filter(d => 
                (d.full_name && d.full_name.toLowerCase().includes(query)) ||
                (d.specialization && d.specialization.toLowerCase().includes(query))
            );
        }

        if (filtered.length > 0) {
            resultsDiv.innerHTML = filtered.map(d => {
                const name = d.full_name;
                const spec = d.specialization ? `<br><small class="text-muted"><i class="fas fa-stethoscope me-1"></i>${esc(d.specialization)}</small>` : '';
                // Pass element's parent container index or just use an inline onclick
                return `
                    <a href="javascript:void(0)" class="dropdown-item py-2" onclick="
                        const inp = this.closest('.position-relative').querySelector('.staff-name');
                        inp.value = '${esc(name)}';
                        this.closest('.doctor-search-results').style.display = 'none';
                    ">
                        <strong>${esc(name)}</strong>
                        ${spec}
                    </a>
                `;
            }).join('');
            resultsDiv.style.display = 'block';
        } else {
            resultsDiv.innerHTML = '<div class="dropdown-item text-muted disabled">No doctors found</div>';
            resultsDiv.style.display = 'block';
        }
    };

    document.addEventListener('click', (e) => {
        // Hide doctor dropdowns if clicked outside
        if (!e.target.closest('.position-relative')) {
            document.querySelectorAll('.doctor-search-results').forEach(el => el.style.display = 'none');
            const deptRes = document.getElementById('department-search-results');
            if (deptRes) deptRes.style.display = 'none';
        }
    });

    // ── Departments ────────────────────────────────────────────────────────
    const filterDepartments = (inputEl) => {
        const query = inputEl.value.toLowerCase().trim();
        const resultsDiv = document.getElementById('department-search-results');
        
        let filtered = departmentsData;
        if (query) {
            filtered = departmentsData.filter(d => d.toLowerCase().includes(query));
        }

        let html = '';
        if (filtered.length > 0) {
            html += filtered.map(d => `
                <a href="javascript:void(0)" class="dropdown-item py-2" onclick="
                    const inp = document.getElementById('field-department');
                    inp.value = '${esc(d)}';
                    document.getElementById('department-search-results').style.display = 'none';
                ">
                    <strong>${esc(d)}</strong>
                </a>
            `).join('');
        } else {
            html += '<div class="dropdown-item text-muted disabled">No matching departments</div>';
        }
        
        // Always append "Add New Specialization" button if query isn't exactly matched
        const exactMatch = departmentsData.find(d => d.toLowerCase() === query);
        if (!exactMatch) {
            html += `
                <div class="dropdown-divider my-1"></div>
                <a href="javascript:void(0)" class="dropdown-item py-2 text-primary" onclick="
                    const newSpec = prompt('Enter New Specialization/Department Name:');
                    if (newSpec && newSpec.trim() !== '') {
                        const val = newSpec.trim();
                        document.getElementById('field-department').value = val;
                        if (!OT.departmentsData.includes(val)) {
                            OT.departmentsData.push(val);
                        }
                    }
                    document.getElementById('department-search-results').style.display = 'none';
                ">
                    <i class="fas fa-plus-circle me-1"></i> Add New Specialization
                </a>
            `;
        }
        
        resultsDiv.innerHTML = html;
        resultsDiv.style.display = 'block';
    };

    // ── Check Room Availability ──────────────────────────────────────────────
    const checkRoomAvailability = () => {
        const room = document.getElementById('field-ot-room')?.value;
        const date = document.getElementById('field-date')?.value;
        const start = document.getElementById('field-start-time')?.value;
        const end = document.getElementById('field-end-time')?.value;
        const id = document.getElementById('surgery-id')?.value;

        if (!room || !date || !start) return; // Wait until they fill these

        const payload = {
            ot_room_name: room,
            schedule_date: date,
            start_time: start,
            end_time: end || null,
            exclude_id: id || null
        };

        fetch(API_BASE + 'ot/check-room', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(res => {
            if (res.success && !res.data.available) {
                const c = res.data.conflict;
                
                // Clear the time to force re-selection
                document.getElementById('field-start-time').value = '';
                
                if (typeof Swal !== 'undefined') {
                    let warningMsg = 'This OT Room is occupied.';
                    if (c.status === 'Ongoing') warningMsg = 'This OT process is currently Ongoing.';
                    else if (c.status === 'Scheduled') warningMsg = 'This OT Room is already Scheduled.';
                    else if (c.status === 'Completed') warningMsg = 'This OT Room was already used during this time.';
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Room Already Scheduled',
                        html: `
                            <div style="font-size: 0.9rem; text-align: left; background: var(--d-surface); border: 1px solid var(--d-green-12); border-radius: 8px; padding: 1rem; margin-top: 1rem;">
                                <p class="mb-3 text-danger fw-bold" style="font-size:1rem;"><i class="fas fa-exclamation-circle me-1"></i>${warningMsg}</p>
                                <table class="table table-sm table-borderless mb-0 align-middle">
                                    <tr><td class="text-muted" width="35%"><strong>Status:</strong></td><td>${statusBadge(c.status)}</td></tr>
                                    <tr><td class="text-muted"><strong>Patient:</strong></td><td>${esc(c.patient_name)}</td></tr>
                                    <tr><td class="text-muted"><strong>Surgery:</strong></td><td>${esc(c.surgery_name)}</td></tr>
                                    <tr><td class="text-muted"><strong>Time:</strong></td><td><span class="badge bg-danger text-white">${c.start_time} - ${c.end_time || 'TBD'}</span></td></tr>
                                </table>
                            </div>
                            <p class="mt-3 mb-0 fw-bold" style="font-size: 0.85rem; color: var(--d-text);">Please select a different OT Room or time.</p>
                        `,
                        confirmButtonColor: '#1f6b4a',
                        confirmButtonText: 'Understood',
                        customClass: { popup: 'shadow-lg rounded-4' }
                    });
                } else {
                    alert('This OT Room is already scheduled for another surgery at the selected date and time.\\nPatient: ' + c.patient_name + '\\nTime: ' + c.start_time + '\\n\\nPlease select a different time or room.');
                }
            }
        })
        .catch(err => console.error('Error checking room availability:', err));
    };

    // ── Quick Edit Time ──────────────────────────────────────────────────────
    const quickEditTime = (id, type, currentTime) => {
        const title = type === 'start' ? 'Edit Start Time' : 'Edit End Time';
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: `<span style="color: var(--d-green); font-size: 1.2rem;">${title}</span>`,
                html: `
                    <div class="text-start mt-2">
                        <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--d-text);">Select New Time:</label>
                        <input type="time" id="swal-input-time" class="form-control" style="border: 1.5px solid var(--d-green-20); border-radius: 8px; padding: 0.6rem; color: var(--d-text);" value="${currentTime}">
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Save Time',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#1f6b4a',
                cancelButtonColor: '#e5e7eb',
                customClass: {
                    popup: 'shadow-lg rounded-4',
                    cancelButton: 'text-dark'
                },
                preConfirm: () => {
                    const val = document.getElementById('swal-input-time').value;
                    if (type === 'start' && !val) {
                        Swal.showValidationMessage('Start time is required');
                    }
                    return val;
                }
            }).then(result => {
                if (result.isConfirmed) {
                    const payload = {};
                    payload[type === 'start' ? 'start_time' : 'end_time'] = result.value || null;
                    
                    fetch(API_BASE + 'ot/surgeries/' + id + '/time', {
                        method: 'PUT',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(payload)
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            showToast('Time updated successfully!', 'success');
                            loadSurgeries();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Overlap Detected',
                                text: res.error || 'Failed to update time.',
                                confirmButtonColor: '#1f6b4a'
                            });
                        }
                    })
                    .catch(() => showToast('Network error while saving time', 'error'));
                }
            });
        }
    };

    // Public API
    return { esc, statusBadge, loadRooms, loadSurgeries, openAddModal, openEditModal, saveSurgery, openStatusModal, onStatusChange, saveStatus, openDeleteModal, confirmDelete, searchPatient, advancedPatientSearch, selectAdvancedPatient, addStaffRow, loadDoctors, filterDoctors, checkRoomAvailability, quickEditTime, loadDepartments, filterDepartments, get departmentsData() { return departmentsData; } };

})();

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    OT.loadDoctors();
    OT.loadDepartments();
});
