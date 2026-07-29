const API_URL = '/GM_HMS/api/';

document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
    
    // Attach event listeners for calculations
    attachCalculationListeners();
    
    // Attach autocomplete for doctors
    initDoctorAutocomplete();
    
    // Mock Fetch Patient Details
    document.getElementById('btnSearchPatient').addEventListener('click', fetchPatientDetails);
    document.getElementById('searchQuery').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') fetchPatientDetails();
    });

    // Save Button
    document.getElementById('btnSave').addEventListener('click', saveOTBilling);
});

// Calculate Row and Totals
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
        const drCharge = parseAmt(row.querySelector('.dr-charge').value);
        const hCharge = parseAmt(row.querySelector('.h-charge').value);
        const sCharge = parseAmt(row.querySelector('.s-charge').value);
        
        const rowAmount = drCharge + hCharge + sCharge;
        row.querySelector('.row-amt').textContent = '₹' + rowAmount.toLocaleString('en-IN', {minimumFractionDigits: 2});
        
        totalDoctorCharges += rowAmount;
    });

    // Additional Charges
    const anesGas = parseAmt(document.getElementById('anesGas').value);
    const extOt = parseAmt(document.getElementById('extOt').value);
    const extAnes = parseAmt(document.getElementById('extAnes').value);
    
    const additionalTotal = anesGas + extOt + extAnes;
    
    // Subtotal
    const subTotal = totalDoctorCharges + additionalTotal;
    
    // Discount
    const discountPercent = parseAmt(document.getElementById('discountPercent').value);
    const discountAmt = (subTotal * discountPercent) / 100;
    
    // GST
    const gstPercent = parseAmt(document.getElementById('gstPercent').value);
    const afterDiscount = Math.max(0, subTotal - discountAmt);
    const gstAmt = (afterDiscount * gstPercent) / 100;
    
    // Grand Total
    const grandTotal = afterDiscount + gstAmt;

    // Amount Paid & Balance Due
    const amountPaid = parseAmt(document.getElementById('amountPaid').value);
    const balanceDue = Math.max(0, grandTotal - amountPaid);

    // Update Summary UI
    document.getElementById('sumTotalCharges').textContent = '₹' + subTotal.toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('sumDiscount').textContent = '- ₹' + discountAmt.toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('sumGst').textContent = '+ ₹' + gstAmt.toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('sumGrandTotal').textContent = '₹' + grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('sumBalanceDue').textContent = '₹' + balanceDue.toLocaleString('en-IN', {minimumFractionDigits: 2});
}

// ----------------------------------------------------
// Doctor Autocomplete Logic
// ----------------------------------------------------
function initDoctorAutocomplete() {
    const inputs = document.querySelectorAll('.select-consultant');
    
    // Close dropdowns when clicking outside
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
        
        // Handle Input (Typing)
        input.addEventListener('input', (e) => {
            const val = e.target.value.trim();
            dropdown.style.display = 'none';
            
            if (val.length < 2) return;
            
            clearTimeout(timeout);
            timeout = setTimeout(() => fetchDoctors(val, dropdown, input), 300);
        });
        
        // Handle Focus (Show previous results if they exist and match)
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
        
        // BaseController returns { success: true, data: [...] }
        const data = responseData.data || responseData;
        
        if (data && Array.isArray(data) && data.length > 0) {
            data.forEach(doc => {
                const item = document.createElement('div');
                item.className = 'autocomplete-item';
                item.innerHTML = `
                    <span class="ac-name">${doc.full_name}</span>
                    <span class="ac-spec">${doc.specialization || ''} - ${doc.department || ''}</span>
                `;
                
                // On select
                item.addEventListener('click', () => {
                    inputElement.value = doc.full_name;
                    dropdown.style.display = 'none';
                    // Trigger input event to re-calculate if needed (though consultant name doesn't affect numbers, it's good practice)
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

async function fetchPatientDetails() {
    const query = document.getElementById('searchQuery').value.trim();
    if (!query) {
        showToast('Please enter Patient ID or Admission ID', 'error');
        return;
    }
    
    showLoading(true);
    try {
        const res = await fetch(`${API_URL}ipd-billing-master?action=search_admissions&q=${encodeURIComponent(query)}`);
        const result = await res.json();
        
        showLoading(false);
        const data = result.data || result;
        
        if (data && data.length > 0) {
            // Take the first matching admission
            const patient = data[0];
            
            document.getElementById('patIpNo').value = patient.patient_id || '';
            document.getElementById('patAdmId').value = patient.admission_id || '';
            document.getElementById('patName').value = patient.patient_name || '';
            document.getElementById('patAge').value = patient.age || '';
            document.getElementById('patGender').value = patient.sex || '';
            document.getElementById('patWard').value = patient.ward_name || '';
            document.getElementById('patRoom').value = patient.room_name || '';
            document.getElementById('patBed').value = patient.bed_number || '';
            document.getElementById('patAdmDate').value = patient.admission_date ? patient.admission_date.split(' ')[0] : '';
            document.getElementById('patConsultant').value = patient.doctor_name || '';
            
            showToast('Patient details fetched successfully', 'success');
        } else {
            showToast('No active admitted patient found for this ID', 'error');
            resetPatientDetails();
        }
    } catch (error) {
        showLoading(false);
        console.error('Error fetching patient:', error);
        showToast('Network error while fetching patient details', 'error');
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
}

async function saveOTBilling() {
    // Validation
    const dept = document.getElementById('surgDept').value;
    const theatre = document.getElementById('surgTheatre').value;
    const anesType = document.getElementById('surgAnesType').value;
    const ipNo = document.getElementById('patIpNo').value;
    
    if (!ipNo) { showToast('Please fetch a patient first', 'error'); return; }
    if (!dept) { showToast('Department is mandatory', 'error'); return; }
    if (!theatre) { showToast('Theatre is mandatory', 'error'); return; }
    if (!anesType) { showToast('Anesthesia Type is mandatory', 'error'); return; }

    let hasSurgeon = false;
    const docCharges = [];
    document.querySelectorAll('.doc-row').forEach(row => {
        const type = row.dataset.type;
        const cons = row.querySelector('.select-consultant').value;
        const dCharge = parseAmt(row.querySelector('.dr-charge').value);
        const hCharge = parseAmt(row.querySelector('.h-charge').value);
        const sCharge = parseAmt(row.querySelector('.s-charge').value);
        const drPerc = parseAmt(row.querySelector('.dr-perc').value);
        const hPerc = parseAmt(row.querySelector('.h-perc').value);
        
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
        showToast('At least one Surgeon must be selected', 'error');
        return;
    }

    // Build Payload
    const payload = {
        patient: {
            patient_id: ipNo,
            admission_id: document.getElementById('patAdmId').value,
            patient_name: document.getElementById('patName').value
        },
        surgery: {
            department: dept,
            theatre: theatre,
            anesthesia_type: anesType,
            surgery_date: document.getElementById('surgDate').value
        },
        doctor_charges: docCharges,
        additional_charges: {
            anesthesia_gas: parseAmt(document.getElementById('anesGas').value),
            external_ot: parseAmt(document.getElementById('extOt').value),
            external_anesthesia: parseAmt(document.getElementById('extAnes').value),
            purpose: document.getElementById('chargePurpose').value
        },
        billing: {
            total_charges: parseAmt(document.getElementById('sumTotalCharges').textContent.replace(/[^0-9.]/g, '')),
            discount_percent: parseAmt(document.getElementById('discountPercent').value),
            gst_percent: parseAmt(document.getElementById('gstPercent').value),
            grand_total: parseAmt(document.getElementById('sumGrandTotal').textContent.replace(/[^0-9.]/g, '')),
            amount_paid: parseAmt(document.getElementById('amountPaid').value)
        }
    };

    console.log('Final Payload:', payload);
    
    // Add loading state to button
    const btnSave = document.getElementById('btnSave');
    const originalText = btnSave.innerHTML;
    btnSave.innerHTML = '<i class="lucide-loader animate-spin"></i> Saving...';
    btnSave.disabled = true;

    try {
        const response = await fetch(`${API_URL}ot-billing`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const result = await response.json();
        
        if (result.success || result.status === 'success') {
            showToast('OT Bill saved successfully!', 'success');
            setTimeout(() => {
                window.location.reload(); // Reload for now to reset form
            }, 1500);
        } else {
            showToast(result.message || 'Error saving bill', 'error');
            btnSave.innerHTML = originalText;
            btnSave.disabled = false;
        }
    } catch (error) {
        console.error('Error saving OT Bill:', error);
        showToast('Network error while saving bill', 'error');
        btnSave.innerHTML = originalText;
        btnSave.disabled = false;
    }
}

function showToast(message, type = 'info') {
    // Basic toast implementation for demo purposes
    alert(`${type.toUpperCase()}: ${message}`);
}

function showLoading(show) {
    document.getElementById('loadingOverlay').style.display = show ? 'flex' : 'none';
}
