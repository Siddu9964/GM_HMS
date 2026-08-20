const API_URL = '/GM_HMS/api/';

document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
    
    // Attach event listeners for calculations
    attachCalculationListeners();
    
    // Attach autocomplete for doctors & patients
    initDoctorAutocomplete();
    initPatientAutocomplete();
    
    // Fetch Patient Details Button & Enter Key
    const btnSearch = document.getElementById('btnSearchPatient');
    if (btnSearch) btnSearch.addEventListener('click', () => fetchPatientDetails());
    
    const searchInput = document.getElementById('searchQuery');
    if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                fetchPatientDetails();
            }
        });
    }

    // Save Button
    const btnSave = document.getElementById('btnSave');
    if (btnSave) btnSave.addEventListener('click', saveOTBilling);
    
    // Keyboard Shortcut: Ctrl + S to Save
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
            e.preventDefault();
            saveOTBilling();
        }
    });

    // Initial calculation run
    calculateAll();
});

// ----------------------------------------------------
// Calculation Engine
// ----------------------------------------------------
function attachCalculationListeners() {
    const inputs = document.querySelectorAll('.calc-trigger');
    inputs.forEach(input => {
        input.addEventListener('input', calculateAll);
    });
}

function parseAmt(val) {
    const num = parseFloat(val);
    return isNaN(num) ? 0 : Math.max(0, num);
}

function calculateAll() {
    let totalDoctorCharges = 0;
    
    // Loop through each doctor row
    const rows = document.querySelectorAll('.doc-row');
    rows.forEach(row => {
        const drCharge = parseAmt(row.querySelector('.dr-charge') ? row.querySelector('.dr-charge').value : 0);
        const hCharge = parseAmt(row.querySelector('.h-charge') ? row.querySelector('.h-charge').value : 0);
        const sCharge = parseAmt(row.querySelector('.s-charge') ? row.querySelector('.s-charge').value : 0);
        
        const rowAmount = drCharge + hCharge + sCharge;
        const rowAmtEl = row.querySelector('.row-amt');
        if (rowAmtEl) {
            rowAmtEl.textContent = '₹' + rowAmount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
        
        totalDoctorCharges += rowAmount;
    });

    // Additional Charges
    const anesGas = parseAmt(document.getElementById('anesGas') ? document.getElementById('anesGas').value : 0);
    const extOt = parseAmt(document.getElementById('extOt') ? document.getElementById('extOt').value : 0);
    const extAnes = parseAmt(document.getElementById('extAnes') ? document.getElementById('extAnes').value : 0);
    
    const additionalTotal = anesGas + extOt + extAnes;
    
    // Subtotal
    const subTotal = totalDoctorCharges + additionalTotal;
    
    // Discount
    const discountPercent = parseAmt(document.getElementById('discountPercent') ? document.getElementById('discountPercent').value : 0);
    const discountAmt = (subTotal * discountPercent) / 100;
    
    // GST
    const gstPercent = parseAmt(document.getElementById('gstPercent') ? document.getElementById('gstPercent').value : 0);
    const afterDiscount = Math.max(0, subTotal - discountAmt);
    const gstAmt = (afterDiscount * gstPercent) / 100;
    
    // Grand Total
    const grandTotal = afterDiscount + gstAmt;

    // Amount Paid & Balance Due
    const amountPaid = parseAmt(document.getElementById('amountPaid') ? document.getElementById('amountPaid').value : 0);
    const balanceDue = Math.max(0, grandTotal - amountPaid);

    // Update Summary UI
    const elSubTotal = document.getElementById('sumTotalCharges');
    const elDiscount = document.getElementById('sumDiscount');
    const elGst = document.getElementById('sumGst');
    const elGrandTotal = document.getElementById('sumGrandTotal');
    const elBalanceDue = document.getElementById('sumBalanceDue');
    const stickyGrandTotal = document.getElementById('stickyGrandTotal');
    
    const fmtGrandTotal = '₹' + grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    
    if (elSubTotal) elSubTotal.textContent = '₹' + subTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    if (elDiscount) elDiscount.textContent = '- ₹' + discountAmt.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    if (elGst) elGst.textContent = '+ ₹' + gstAmt.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    if (elGrandTotal) elGrandTotal.textContent = fmtGrandTotal;
    if (elBalanceDue) elBalanceDue.textContent = '₹' + balanceDue.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    if (stickyGrandTotal) stickyGrandTotal.textContent = fmtGrandTotal;
}

// ----------------------------------------------------
// User Friendly Quick Presets & Helpers
// ----------------------------------------------------
function applySurgeryPreset(name, dept, theatre, anes) {
    const elName = document.getElementById('surgName');
    const elDept = document.getElementById('surgDept');
    const elTheatre = document.getElementById('surgTheatre');
    const elAnes = document.getElementById('surgAnesType');
    
    if (elName) elName.value = name;
    if (elDept) elDept.value = dept;
    if (elTheatre) elTheatre.value = theatre;
    if (elAnes) elAnes.value = anes;
    
    showToast(`Preset applied: ${name}`, 'info');
}

function setDiscount(pct) {
    const el = document.getElementById('discountPercent');
    if (el) {
        el.value = pct;
        calculateAll();
    }
}

function setGst(pct) {
    const el = document.getElementById('gstPercent');
    if (el) {
        el.value = pct;
        calculateAll();
    }
}

function setPaymentMode(mode) {
    const grandTotal = parseAmt(document.getElementById('sumGrandTotal') ? document.getElementById('sumGrandTotal').textContent.replace(/[^0-9.]/g, '') : 0);
    const elPaid = document.getElementById('amountPaid');
    if (!elPaid) return;
    
    if (mode === 'full') {
        elPaid.value = grandTotal.toFixed(2);
    } else if (mode === 'half') {
        elPaid.value = (grandTotal / 2).toFixed(2);
    } else if (mode === 'zero') {
        elPaid.value = '0.00';
    }
    calculateAll();
}

function clearDoctorRow(btn) {
    const row = btn.closest('.doc-row');
    if (!row) return;
    row.querySelectorAll('input').forEach(i => i.value = '');
    calculateAll();
}

function appendNote(tagText) {
    const textarea = document.getElementById('chargePurpose');
    if (!textarea) return;
    const current = textarea.value.trim();
    if (current.length > 0) {
        if (!current.includes(tagText)) {
            textarea.value = current + ' • ' + tagText;
        }
    } else {
        textarea.value = tagText;
    }
    textarea.focus();
}

// ----------------------------------------------------
// Patient Search & Live Autocomplete
// ----------------------------------------------------
function initPatientAutocomplete() {
    const searchInput = document.getElementById('searchQuery');
    const dropdown = document.getElementById('patientDropdown');
    if (!searchInput || !dropdown) return;
    
    let timeout = null;
    
    // Close dropdown on outside click
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-container')) {
            dropdown.style.display = 'none';
        }
    });

    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.trim();
        dropdown.style.display = 'none';
        
        if (query.length < 2) return;
        
        clearTimeout(timeout);
        timeout = setTimeout(async () => {
            try {
                const res = await fetch(`${API_URL}ipd-billing-master?action=search_admissions&q=${encodeURIComponent(query)}`);
                const result = await res.json();
                const data = result.data || result;
                
                dropdown.innerHTML = '';
                if (data && Array.isArray(data) && data.length > 0) {
                    data.forEach(patient => {
                        const item = document.createElement('div');
                        item.className = 'patient-dropdown-item';
                        item.innerHTML = `
                            <div>
                                <div class="patient-dd-name"><i class="fas fa-user-injured me-1"></i> ${patient.patient_name || 'Unknown'}</div>
                                <div class="patient-dd-sub">Ward: ${patient.ward_name || 'N/A'} • Bed: ${patient.bed_number || 'N/A'} • Doc: ${patient.doctor_name || 'N/A'}</div>
                            </div>
                            <span class="patient-dd-badge">${patient.patient_id || patient.admission_id}</span>
                        `;
                        item.addEventListener('click', () => {
                            populatePatientData(patient);
                            dropdown.style.display = 'none';
                            searchInput.value = `${patient.patient_name} (${patient.patient_id})`;
                        });
                        dropdown.appendChild(item);
                    });
                    dropdown.style.display = 'block';
                }
            } catch (err) {
                console.error('Patient search error:', err);
            }
        }, 280);
    });
}

async function fetchPatientDetails(specificQuery = null) {
    const queryInput = document.getElementById('searchQuery');
    const query = specificQuery || (queryInput ? queryInput.value.trim() : '');
    if (!query) {
        showToast('Please enter an IP Number or Admission ID', 'error');
        return;
    }
    
    showLoading(true);
    try {
        const res = await fetch(`${API_URL}ipd-billing-master?action=search_admissions&q=${encodeURIComponent(query)}`);
        const result = await res.json();
        
        showLoading(false);
        const data = result.data || result;
        
        if (data && data.length > 0) {
            populatePatientData(data[0]);
            showToast('Patient details retrieved successfully!', 'success');
        } else {
            showToast('No active admitted patient found for this search term.', 'error');
            resetPatientDetails();
        }
    } catch (error) {
        showLoading(false);
        console.error('Error fetching patient:', error);
        showToast('Network error while retrieving patient details.', 'error');
    }
}

function populatePatientData(patient) {
    document.getElementById('patIpNo').value = patient.patient_id || '';
    document.getElementById('patAdmId').value = patient.admission_id || '';
    document.getElementById('patName').value = patient.patient_name || '';
    document.getElementById('patAge').value = patient.age ? `${patient.age} Yrs` : '';
    document.getElementById('patGender').value = patient.sex || '';
    document.getElementById('patWard').value = patient.ward_name || '';
    document.getElementById('patRoom').value = patient.room_name || '';
    document.getElementById('patBed').value = patient.bed_number || '';
    document.getElementById('patAdmDate').value = patient.admission_date ? patient.admission_date.split(' ')[0] : '';
    document.getElementById('patConsultant').value = patient.doctor_name || '';
    
    const stickyPat = document.getElementById('stickyPatientName');
    if (stickyPat) {
        stickyPat.innerHTML = `<i class="fas fa-user-check me-1"></i> <span>${patient.patient_name || 'Patient'}</span> (${patient.patient_id || 'ID'})`;
    }
}

function resetPatientDetails() {
    document.getElementById('patIpNo').value = '';
    document.getElementById('patAdmId').value = '';
    document.getElementById('patName').value = '';
    document.getElementById('patAge').value = '';
    document.getElementById('patGender').value = '';
    document.getElementById('patWard').value = '';
    document.getElementById('patRoom').value = '';
    document.getElementById('patBed').value = '';
    document.getElementById('patAdmDate').value = '';
    document.getElementById('patConsultant').value = '';
    
    const stickyPat = document.getElementById('stickyPatientName');
    if (stickyPat) {
        stickyPat.innerHTML = `<i class="fas fa-user-circle me-1"></i> <span>No Patient Selected</span>`;
    }
}

// ----------------------------------------------------
// Doctor Autocomplete Logic
// ----------------------------------------------------
function initDoctorAutocomplete() {
    const inputs = document.querySelectorAll('.select-consultant');
    
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.autocomplete-wrapper')) {
            document.querySelectorAll('.autocomplete-dropdown').forEach(d => d.style.display = 'none');
        }
    });

    inputs.forEach(input => {
        let timeout = null;
        const wrapper = input.closest('.autocomplete-wrapper');
        if (!wrapper) return;
        
        const dropdown = wrapper.querySelector('.autocomplete-dropdown');
        if (!dropdown) return;
        
        input.addEventListener('input', (e) => {
            const val = e.target.value.trim();
            dropdown.style.display = 'none';
            
            if (val.length < 2) return;
            
            clearTimeout(timeout);
            timeout = setTimeout(() => fetchDoctors(val, dropdown, input), 280);
        });
        
        input.addEventListener('focus', () => {
            if (input.value.trim().length >= 2 && dropdown.innerHTML !== '') {
                dropdown.style.display = 'block';
            }
        });
    });
}

async function fetchDoctors(query, dropdown, inputElement) {
    try {
        const res = await fetch(`${API_URL}doctors?search=${encodeURIComponent(query)}`);
        const responseData = await res.json();
        dropdown.innerHTML = '';
        const data = responseData.data || responseData;
        
        if (data && Array.isArray(data) && data.length > 0) {
            data.forEach(doc => {
                const item = document.createElement('div');
                item.className = 'autocomplete-item';
                item.innerHTML = `
                    <span class="ac-name"><i class="fas fa-user-md me-1" style="color:var(--primary-color);"></i> ${doc.full_name}</span>
                    <span class="ac-spec">${doc.specialization || 'Consultant'} ${doc.department ? '• ' + doc.department : ''}</span>
                `;
                
                item.addEventListener('click', () => {
                    inputElement.value = doc.full_name;
                    dropdown.style.display = 'none';
                    inputElement.dispatchEvent(new Event('input'));
                });
                
                dropdown.appendChild(item);
            });
            dropdown.style.display = 'block';
        } else {
            dropdown.style.display = 'none';
        }
    } catch (error) {
        console.error('Error fetching doctors:', error);
        dropdown.style.display = 'none';
    }
}

// ----------------------------------------------------
// Save OT Billing
// ----------------------------------------------------
async function saveOTBilling() {
    const name = document.getElementById('surgName') ? document.getElementById('surgName').value.trim() : '';
    const dept = document.getElementById('surgDept') ? document.getElementById('surgDept').value.trim() : '';
    const theatre = document.getElementById('surgTheatre') ? document.getElementById('surgTheatre').value : '';
    const anesType = document.getElementById('surgAnesType') ? document.getElementById('surgAnesType').value : '';
    const ipNo = document.getElementById('patIpNo') ? document.getElementById('patIpNo').value.trim() : '';
    
    if (!ipNo) { showToast('Please select or search an admitted patient first.', 'error'); return; }
    if (!name) { showToast('Surgery / Procedure Name is required.', 'error'); return; }
    if (!dept) { showToast('Surgery Department is required.', 'error'); return; }
    if (!theatre) { showToast('Operating Theatre selection is required.', 'error'); return; }
    if (!anesType) { showToast('Anesthesia Type is required.', 'error'); return; }

    let hasSurgeon = false;
    const docCharges = [];
    document.querySelectorAll('.doc-row').forEach(row => {
        const type = row.dataset.type;
        const cons = row.querySelector('.select-consultant') ? row.querySelector('.select-consultant').value.trim() : '';
        const dCharge = parseAmt(row.querySelector('.dr-charge') ? row.querySelector('.dr-charge').value : 0);
        const hCharge = parseAmt(row.querySelector('.h-charge') ? row.querySelector('.h-charge').value : 0);
        const sCharge = parseAmt(row.querySelector('.s-charge') ? row.querySelector('.s-charge').value : 0);
        const drPerc = parseAmt(row.querySelector('.dr-perc') ? row.querySelector('.dr-perc').value : 0);
        const hPerc = parseAmt(row.querySelector('.h-perc') ? row.querySelector('.h-perc').value : 0);
        
        if (cons || dCharge > 0 || hCharge > 0 || sCharge > 0) {
            if (type === 'SURGEON' && cons) hasSurgeon = true;
            docCharges.push({
                particular: type,
                consultant: cons,
                doctor_percent: drPerc,
                doctor_charge: dCharge,
                hospital_percent: hPerc,
                hospital_charge: hCharge,
                service_charge: sCharge,
                amount: dCharge + hCharge + sCharge
            });
        }
    });

    if (!hasSurgeon) {
        showToast('At least one Surgeon must be assigned.', 'error');
        return;
    }

    const payload = {
        patient: {
            patient_id: ipNo,
            admission_id: document.getElementById('patAdmId') ? document.getElementById('patAdmId').value : '',
            patient_name: document.getElementById('patName') ? document.getElementById('patName').value : ''
        },
        surgery: {
            name: name,
            department: dept,
            theatre: theatre,
            anesthesia_type: anesType,
            surgery_date: document.getElementById('surgDate') ? document.getElementById('surgDate').value : ''
        },
        doctor_charges: docCharges,
        additional_charges: {
            anesthesia_gas: parseAmt(document.getElementById('anesGas') ? document.getElementById('anesGas').value : 0),
            external_ot: parseAmt(document.getElementById('extOt') ? document.getElementById('extOt').value : 0),
            external_anesthesia: parseAmt(document.getElementById('extAnes') ? document.getElementById('extAnes').value : 0),
            purpose: document.getElementById('chargePurpose') ? document.getElementById('chargePurpose').value : ''
        },
        billing: {
            total_charges: parseAmt(document.getElementById('sumTotalCharges') ? document.getElementById('sumTotalCharges').textContent.replace(/[^0-9.]/g, '') : 0),
            discount_percent: parseAmt(document.getElementById('discountPercent') ? document.getElementById('discountPercent').value : 0),
            gst_percent: parseAmt(document.getElementById('gstPercent') ? document.getElementById('gstPercent').value : 0),
            grand_total: parseAmt(document.getElementById('sumGrandTotal') ? document.getElementById('sumGrandTotal').textContent.replace(/[^0-9.]/g, '') : 0),
            amount_paid: parseAmt(document.getElementById('amountPaid') ? document.getElementById('amountPaid').value : 0)
        }
    };

    const btnSave = document.getElementById('btnSave');
    const originalText = btnSave.innerHTML;
    btnSave.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving OT Charges...';
    btnSave.disabled = true;

    try {
        const response = await fetch(`${API_URL}ot-billing`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const result = await response.json();
        
        if (result.success || result.status === 'success') {
            showToast('Operation Theater Charges saved successfully!', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1800);
        } else {
            showToast(result.message || 'Error saving OT bill records.', 'error');
            btnSave.innerHTML = originalText;
            btnSave.disabled = false;
        }
    } catch (error) {
        console.error('Error saving OT Bill:', error);
        showToast('Network error occurred while saving OT Bill.', 'error');
        btnSave.innerHTML = originalText;
        btnSave.disabled = false;
    }
}

// ----------------------------------------------------
// Centered Modal Feedback System
// ----------------------------------------------------
function showToast(message, type = 'info') {
    const modal = document.getElementById('otMsgModal');
    const icon = document.getElementById('otModalIcon');
    const title = document.getElementById('otModalTitle');
    const text = document.getElementById('otModalText');
    const btn = document.getElementById('otModalBtn');

    if (!modal) {
        alert(message);
        return;
    }

    icon.innerHTML = '<i class="fas fa-info-circle" style="color: #1f6b4a;"></i>';
    title.textContent = (type === 'success') ? 'Success' : (type === 'error' ? 'Notice' : 'Information');
    title.style.color = '#1f6b4a';
    btn.style.background = '#1f6b4a';
    btn.style.color = '#f3efe6';
    btn.style.border = '2px solid #1f6b4a';

    text.textContent = message;
    text.style.color = '#1f6b4a';
    modal.style.display = 'flex';
}

function closeOtModal() {
    const modal = document.getElementById('otMsgModal');
    if (modal) modal.style.display = 'none';
}

function showLoading(show) {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) overlay.style.display = show ? 'flex' : 'none';
}
