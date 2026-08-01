/**
 * Billing Management - Admin Panel
 * Handles OPD and IPD billing operations
 */

let billingItems = [];
let editingBillId = null;
let currentTab = 'opd';

// Initialize on page load
$(document).ready(function () {
    initializeSelects();
    loadStatistics();
    loadBills();

    // Initialize billing form
    $('#opd-billing-form').on('submit', handleBillSubmit);

    // Patient select change
    $('#patient-select').on('change', handlePatientSelect);
});

/**
 * Initialize Select2 dropdowns
 */
function initializeSelects() {
    // Patient select with advanced AJAX search
    $('#patient-select').select2({
        ajax: {
            url: '../api/index.php/api/patients',
            dataType: 'json',
            delay: 300,
            data: function (params) {
                return {
                    term: params.term || '',
                    limit: 50
                };
            },
            processResults: function (data) {
                if (data.status === 'success' && data.data) {
                    return {
                        results: data.data.map(patient => ({
                            id: patient.patient_id,
                            text: `${patient.first_name} ${patient.last_name} (${patient.patient_id})`,
                            patient: patient
                        }))
                    };
                }
                return { results: [] };
            },
            cache: true
        },
        placeholder: 'Type patient name, ID, phone, or Aadhar...',
        allowClear: true,
        minimumInputLength: 0,
        width: '100%',
        dropdownParent: $('#billing-form-container'),
        templateResult: formatPatientResult,
        templateSelection: formatPatientSelection,
        language: {
            inputTooShort: function () {
                return 'Start typing to search patients...';
            },
            searching: function () {
                return 'Searching patients...';
            },
            noResults: function () {
                return 'No patients found. Try different keywords.';
            },
            errorLoading: function () {
                return 'Error loading patients. Please try again.';
            }
        }
    });

    // Doctor select with AJAX
    $('#doctor-select').select2({
        ajax: {
            url: '../api/index.php/api/doctors',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    term: params.term
                };
            },
            processResults: function (data) {
                if (data.status === 'success') {
                    return {
                        results: data.data.map(doctor => ({
                            id: doctor.doctor_id,
                            text: `Dr. ${doctor.full_name} - ${doctor.specialization}`
                        }))
                    };
                }
                return { results: [] };
            }
        },
        placeholder: 'Select doctor (optional)...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#billing-form-container')
    });
}

/**
 * Format patient result in dropdown with rich details
 */
function formatPatientResult(patient) {
    if (patient.loading) {
        return patient.text;
    }

    if (!patient.patient) {
        return patient.text;
    }

    const p = patient.patient;
    const age = p.age || calculateAge(p.birth_date);

    return $(`
        <div class="patient-result-item" style="padding: 8px 0;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="flex-shrink: 0; width: 40px; height: 40px; background: linear-gradient(135deg, #3b82f6, #2563eb); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 16px;">
                    ${((p.first_name || '').charAt(0) + (p.last_name || '').charAt(0)).toUpperCase() || 'P'}
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: 600; color: #1e293b; font-size: 14px; margin-bottom: 2px;">
                        ${p.first_name} ${p.last_name}
                        <span style="color: #64748b; font-weight: 500; font-size: 12px; margin-left: 8px;">
                            ${age}Y / ${p.sex}
                        </span>
                    </div>
                    <div style="display: flex; gap: 16px; font-size: 11px; color: #64748b;">
                        <span><i class="fas fa-id-card" style="margin-right: 4px; color: #3b82f6;"></i>${p.patient_id}</span>
                        ${p.phone ? `<span><i class="fas fa-phone" style="margin-right: 4px; color: #10b981;"></i>${p.phone}</span>` : ''}
                        ${p.aadhar ? `<span><i class="fas fa-fingerprint" style="margin-right: 4px; color: #f59e0b;"></i>${p.aadhar.slice(-4)}</span>` : ''}
                    </div>
                </div>
            </div>
        </div>
    `);
}

/**
 * Format selected patient (compact view)
 */
function formatPatientSelection(patient) {
    if (!patient.patient) {
        return patient.text;
    }

    const p = patient.patient;
    return `${p.first_name} ${p.last_name} (${p.patient_id})`;
}

/**
 * Calculate age from birth date
 */
function calculateAge(birthDate) {
    if (!birthDate) return 0;
    const today = new Date();
    const birth = new Date(birthDate);
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--;
    }
    return age;
}

/**
 * Handle patient selection
 */
function handlePatientSelect(e) {
    const selectedData = $(this).select2('data')[0];
    if (selectedData && selectedData.patient) {
        const patient = selectedData.patient;

        // Show patient info
        $('#patient-info').removeClass('hidden');
        $('#info-patient-id').text(patient.patient_id);
        $('#info-age-sex').text(`${patient.age} years / ${patient.sex}`);
        $('#info-phone').text(patient.phone || '-');
    } else {
        $('#patient-info').addClass('hidden');
    }
}

/**
 * Toggle billing form visibility
 */
function toggleBillingForm() {
    const container = document.getElementById('billing-form-container');
    container.classList.toggle('hidden');

    if (!container.classList.contains('hidden')) {
        // Reset form when opening
        if (!editingBillId) {
            document.getElementById('opd-billing-form').reset();
            $('#patient-select').val(null).trigger('change');
            $('#doctor-select').val(null).trigger('change');
            $('#patient-info').addClass('hidden');
            billingItems = [];
            renderBillingItems();
            calculateTotals();
        }
    } else {
        // Reset state on close
        editingBillId = null;
        $('#form-mode-title').html('<i class="fas fa-file-circle-plus text-blue-600"></i> New OPD Invoice');
        $('#btn-submit-bill').html('<i class="fas fa-check-double"></i> Confirm & Generate');
    }
}

/**
 * Add billing item row
 */
function addBillingItem() {
    const item = {
        id: Date.now(),
        item_type: 'Consultation',
        item_name: '',
        quantity: 1,
        unit_price: 0,
        total_price: 0
    };

    billingItems.push(item);
    renderBillingItems();
}

/**
 * Remove billing item
 */
function removeBillingItem(itemId) {
    billingItems = billingItems.filter(item => item.id !== itemId);
    renderBillingItems();
    calculateTotals();
}

/**
 * Render billing items table
 */
function renderBillingItems() {
    const tbody = document.getElementById('billing-items-tbody');

    if (billingItems.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-slate-400 py-12 font-medium">No services added yet. Click "Add Line Item" to begin.</td></tr>';
        return;
    }

    tbody.innerHTML = billingItems.map(item => `
        <tr class="border-b border-slate-100 group hover:bg-slate-50 transition-all">
            <td class="px-4 py-3">
                <input type="text" class="w-full bg-white border border-slate-200 rounded-lg p-2 outline-none focus:ring-2 focus:ring-blue-500/20 text-sm placeholder:text-slate-300" 
                       value="${item.item_name}" 
                       onchange="updateItemField(${item.id}, 'item_name', this.value)"
                       placeholder="Enter service name...">
            </td>
            <td class="px-4 py-3">
                <input type="number" class="w-20 mx-auto block bg-white border border-slate-200 rounded-lg p-2 outline-none focus:ring-2 focus:ring-blue-500/20 text-center font-bold" 
                       value="${item.quantity}" min="1" step="1"
                       onchange="updateItemField(${item.id}, 'quantity', parseFloat(this.value))">
            </td>
            <td class="px-4 py-3">
                <input type="number" class="w-28 ml-auto block bg-white border border-slate-200 rounded-lg p-2 outline-none focus:ring-2 focus:ring-blue-500/20 text-right font-bold" 
                       value="${item.unit_price}" min="0" step="0.01"
                       onchange="updateItemField(${item.id}, 'unit_price', parseFloat(this.value))">
            </td>
            <td class="px-4 py-3 text-right">
                <span class="text-slate-900 font-black">₹${item.total_price.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</span>
            </td>
            <td class="px-4 py-3 text-center">
                <button type="button" onclick="removeBillingItem(${item.id})" 
                        class="h-8 w-8 text-rose-500 hover:bg-rose-50 rounded-lg transition-all flex items-center justify-center">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

/**
 * Update billing item field
 */
function updateItemField(itemId, field, value) {
    const item = billingItems.find(i => i.id === itemId);
    if (item) {
        item[field] = value;

        // Recalculate total price
        item.total_price = item.quantity * item.unit_price;

        renderBillingItems();
        calculateTotals();
    }
}

/**
 * Calculate bill totals
 */
function calculateTotals() {
    let subtotal = 0;
    billingItems.forEach(item => {
        subtotal += item.total_price;
    });

    const discountAmount = parseFloat(document.getElementById('discount-amount').value) || 0;
    const taxableAmount = Math.max(0, subtotal - discountAmount);
    // Hospital does not use GST
    const taxAmount = 0;
    const grandTotal = taxableAmount + taxAmount;

    document.getElementById('summary-subtotal').innerText = `₹${subtotal.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
    document.getElementById('summary-taxable').innerText = `₹${taxableAmount.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
    document.getElementById('summary-grand-total').innerText = `₹${grandTotal.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
    document.getElementById('summary-grand-total').innerText = `₹${grandTotal.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;

    // Default amount paid to grand total for convenience
    document.getElementById('amount-paid').value = grandTotal.toFixed(2);
}

/**
 * Handle bill form submission
 */
function handleBillSubmit(e) {
    e.preventDefault();

    if (billingItems.length === 0) {
        alert('Please add at least one billing item');
        return;
    }

    const formData = new FormData(e.target);
    const data = {
        patient_id: formData.get('patient_id'),
        doctor_id: formData.get('doctor_id') || null,
        discount_amount: parseFloat(formData.get('discount_amount')) || 0,
        items: billingItems.map(item => ({
            item_type: item.item_type,
            item_name: item.item_name,
            quantity: item.quantity,
            unit_price: item.unit_price
        })),
        payment: {
            amount: parseFloat(formData.get('amount_paid')),
            payment_method: formData.get('payment_method'),
            notes: formData.get('notes')
        }
    };

    // Submit to API
    const isEditing = editingBillId !== null;
    const url = isEditing
        ? `../api/index.php/api/billing/opd/${editingBillId}`
        : '../api/index.php/api/billing/opd';
    const method = isEditing ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        method: method,
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function (response) {
            if (response.status === 'success') {
                showSuccess(isEditing ? 'Bill updated successfully!' : 'Bill created successfully!\nBill ID: ' + response.data.bill_id);
                toggleBillingForm();
                loadBills();
                loadStatistics();
            } else {
                showError('Error: ' + response.message);
            }
        },
        error: function (xhr) {
            console.error('Error with bill:', xhr);
            showError('Failed to ' + (isEditing ? 'update' : 'create') + ' bill. Please try again.');
        }
    });
}

/**
 * Load billing statistics
 */
function loadStatistics() {
    $.ajax({
        url: '../api/index.php/api/billing/opd/stats',
        method: 'GET',
        success: function (response) {
            if (response.status === 'success') {
                const stats = response.data;
                document.getElementById('stat-today-revenue').textContent = `₹${parseFloat(stats.today_revenue || 0).toFixed(2)}`;
                document.getElementById('stat-month-revenue').textContent = `₹${parseFloat(stats.month_revenue || 0).toFixed(2)}`;
                document.getElementById('stat-pending-bills').textContent = stats.pending_bills || 0;
                document.getElementById('stat-outstanding').textContent = `₹${parseFloat(stats.outstanding_amount || 0).toFixed(2)}`;
            }
        },
        error: function (xhr) {
            console.error('Error loading statistics:', xhr);
        }
    });
}

/**
 * Load bills list
 */
function loadBills() {
    const status = document.getElementById('filter-status').value;

    let url = '../api/index.php/api/billing/opd?all=1';
    if (status) {
        url += `&payment_status=${status}`;
    }

    $.ajax({
        url: url,
        method: 'GET',
        success: function (response) {
            if (response.status === 'success') {
                renderBillsTable(response.data);
            }
        },
        error: function (xhr) {
            console.error('Error loading bills:', xhr);
            document.getElementById('bills-tbody').innerHTML =
                '<tr><td colspan="9" class="text-center text-red-500 py-4">Error loading bills</td></tr>';
        }
    });
}

/**
 * Render bills table
 */
function renderBillsTable(bills) {
    const tbody = document.getElementById('bills-tbody');

    // 1. Calculate Stats
    let todayRevenue = 0;
    let monthRevenue = 0;
    let pendingCount = 0;
    let outstandingAmount = 0;

    // We'll use JS Date to format today and month to match bill_date formats (assuming YYYY-MM-DD)
    const now = new Date();
    // Use local date string padded manually just in case
    const todayStr = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
    const monthStr = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');

    bills.forEach(bill => {
        const balance = parseFloat(bill.balance_due) || 0;
        const paid = parseFloat(bill.amount_paid) || 0;
        
        if (balance > 0) {
            pendingCount++;
            outstandingAmount += balance;
        }
        
        if (bill.bill_date && bill.bill_date.includes(todayStr)) {
            todayRevenue += paid;
        }
        if (bill.bill_date && bill.bill_date.includes(monthStr)) {
            monthRevenue += paid;
        }
    });

    const statToday = document.getElementById('stat-today-revenue');
    const statMonth = document.getElementById('stat-month-revenue');
    const statPending = document.getElementById('stat-pending-bills');
    const statOut = document.getElementById('stat-outstanding');
    
    if(statToday) statToday.innerText = '₹' + todayRevenue.toFixed(2);
    if(statMonth) statMonth.innerText = '₹' + monthRevenue.toFixed(2);
    if(statPending) statPending.innerText = pendingCount;
    if(statOut) statOut.innerText = '₹' + outstandingAmount.toFixed(2);

    // 2. Populate Receipts Table
    const receiptsTbody = document.getElementById('receipts-tbody');
    if (receiptsTbody) {
        const paidBills = bills.filter(b => parseFloat(b.amount_paid) > 0);
        if (paidBills.length === 0) {
            receiptsTbody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">No receipts found.</td></tr>';
        } else {
            receiptsTbody.innerHTML = paidBills.map(bill => {
                const recId = bill.bill_id.replace('BILL-', 'REC-');
                return `
                    <tr class="hover:bg-gray-50 text-sm">
                        <td class="px-6 py-4 font-black text-[#1f6b4a]">${recId}</td>
                        <td class="px-6 py-4 font-medium text-gray-700">${bill.bill_id}</td>
                        <td class="px-6 py-4">${bill.patient_name || '-'}</td>
                        <td class="px-6 py-4 text-gray-500">${bill.bill_date}</td>
                        <td class="px-6 py-4 text-right font-bold text-green-600">₹${parseFloat(bill.amount_paid).toFixed(2)}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2 py-1 bg-[#e8f4ed] text-[#1f6b4a] rounded-full text-xs font-bold">${bill.payment_mode || 'Cash'}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button onclick="showBillDetails('${bill.bill_id}')" class="text-[#1f6b4a] hover:opacity-80 p-2 border border-[#1f6b4a40] rounded shadow-sm"><i class="fas fa-print"></i></button>
                        </td>
                    </tr>
                `;
            }).join('');
        }
    }

    // 3. Render Main OPD Bills Table
    if (bills.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-gray-500 py-4">No bills found</td></tr>';
        return;
    }

    tbody.innerHTML = bills.map(bill => {
        const statusColors = {
            'Paid': 'bg-green-100 text-green-800',
            'Partial': 'bg-yellow-100 text-yellow-800',
            'Pending': 'bg-red-100 text-red-800',
            'Cancelled': 'bg-gray-100 text-gray-800'
        };

        return `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <a href="javascript:void(0)" onclick="showBillDetails('${bill.bill_id}')" class="text-[#1f6b4a] hover:opacity-80 font-black decoration-2 underline-offset-4 hover:underline">
                        ${bill.bill_id}
                    </a>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${bill.patient_name || '-'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${bill.doctor_name || '-'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${bill.bill_date}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">₹${parseFloat(bill.grand_total).toFixed(2)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">₹${parseFloat(bill.amount_paid).toFixed(2)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">₹${parseFloat(bill.balance_due).toFixed(2)}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full ${statusColors[bill.payment_status] || 'bg-gray-100 text-gray-800'}">
                        ${bill.payment_status}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-center sticky right-0 bg-white z-10 shadow-[inset_1px_0_0_rgba(0,0,0,0.05)]">
                    <div class="flex items-center justify-center gap-2">
                        <button onclick="viewBill('${bill.bill_id}')" title="View Details" class="text-blue-500 hover:bg-blue-50 p-2 rounded-lg transition-colors shadow-sm border border-transparent hover:border-blue-100">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="editBill('${bill.bill_id}')" title="Edit Bill" class="text-amber-500 hover:bg-amber-50 p-2 rounded-lg transition-colors shadow-sm border border-transparent hover:border-amber-100">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="printBill('${bill.bill_id}')" title="Print Invoice" class="text-emerald-500 hover:bg-emerald-50 p-2 rounded-lg transition-colors shadow-sm border border-transparent hover:border-emerald-100">
                            <i class="fas fa-print"></i>
                        </button>
                        <button onclick="deleteBill('${bill.bill_id}')" title="Delete Bill" class="text-rose-500 hover:bg-rose-50 p-2 rounded-lg transition-colors shadow-sm border border-transparent hover:border-rose-100">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

/**
 * View bill details in modal
 */
function showBillDetails(billId) {
    // Show modal and loading state
    toggleBillModal();
    document.getElementById('modal-bill-id').textContent = 'Loading...';

    $.ajax({
        url: `../api/index.php/api/billing/opd/${billId}`,
        method: 'GET',
        success: function (response) {
            if (response.status === 'success') {
                const bill = response.data;
                const fmt = (val) => `₹${parseFloat(val || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;

                // Populate Modal Header & Patient Info
                document.getElementById('modal-bill-id').textContent = bill.bill_id;
                document.getElementById('detail-patient-name').textContent = bill.patient_name || 'Walking Patient';
                document.getElementById('detail-patient-id').textContent = bill.patient_id || 'N/A';
                document.getElementById('detail-patient-phone').textContent = bill.patient_phone || 'N/A';
                document.getElementById('detail-doctor-name').textContent = bill.doctor_name ? `Dr. ${bill.doctor_name}` : 'Direct Service';

                // Populate Metadata
                document.getElementById('detail-appointment-id').textContent = bill.appointment_id || 'No Appt';
                document.getElementById('detail-bill-time').textContent = bill.bill_time || '00:00:00';
                document.getElementById('detail-created-by').textContent = bill.created_by || 'System';
                document.getElementById('detail-payment-mode').textContent = bill.payment_mode || 'N/A';

                // Populate Bill Meta
                document.getElementById('detail-bill-date').textContent = bill.bill_date;
                document.getElementById('detail-bill-purpose').textContent = bill.purpose || 'General Service';
                document.getElementById('detail-balance-due').textContent = fmt(bill.balance_due);

                // Status Badge
                const statusEl = document.getElementById('detail-payment-status');
                statusEl.textContent = bill.payment_status;
                statusEl.className = `px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest ${bill.payment_status === 'Paid' ? 'bg-green-100 text-green-700' :
                        bill.payment_status === 'Partial' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'
                    }`;

                // Financial Footer Stats
                document.getElementById('foot-subtotal').textContent = fmt(bill.subtotal);
                document.getElementById('foot-discount-percent').textContent = parseFloat(bill.discount_percentage || 0);
                document.getElementById('foot-discount').textContent = `- ${fmt(bill.discount_amount)}`;
                document.getElementById('foot-taxable').textContent = fmt(bill.taxable_amount);
                document.getElementById('foot-grand-total').textContent = fmt(bill.grand_total);
                document.getElementById('foot-amount-paid').textContent = fmt(bill.amount_paid);

                // Populate Items Table
                const itemsTbody = document.getElementById('detail-items-tbody');
                let itemsHtml = '';

                if (bill.items && bill.items.length > 0) {
                    itemsHtml = bill.items.map(item => `
                        <tr class="border-b border-slate-50">
                            <td class="px-6 py-4 font-medium text-slate-700">${item.item_name}</td>
                            <td class="px-6 py-4 text-center text-slate-500 font-bold">${parseFloat(item.quantity)}</td>
                            <td class="px-6 py-4 text-right text-slate-500">${fmt(item.unit_price)}</td>
                            <td class="px-6 py-4 text-right font-black text-slate-900">${fmt(item.total_price)}</td>
                        </tr>
                    `).join('');
                } else {
                    // Fallback to master record info if items are missing (e.g. Registration)
                    itemsHtml = `
                        <tr class="border-b border-slate-50">
                            <td class="px-6 py-4 font-medium text-slate-700">${bill.item_name || bill.purpose || 'Main Service'}</td>
                            <td class="px-6 py-4 text-center text-slate-500 font-bold">1.00</td>
                            <td class="px-6 py-4 text-right text-slate-500">${fmt(bill.subtotal)}</td>
                            <td class="px-6 py-4 text-right font-black text-slate-900">${fmt(bill.subtotal)}</td>
                        </tr>
                    `;
                }
                itemsTbody.innerHTML = itemsHtml;

                // Notes
                const notesContainer = document.getElementById('detail-notes-container');
                if (bill.notes && bill.notes.trim() !== '') {
                    notesContainer.classList.remove('hidden');
                    document.getElementById('detail-notes').textContent = bill.notes;
                } else {
                    notesContainer.classList.add('hidden');
                }

                // Update Print Button
                document.getElementById('btn-print-modal').setAttribute('onclick', `printBill('${bill.bill_id}')`);

                // Update Payment Button
                const payBtn = document.getElementById('btn-pay-modal');
                if (parseFloat(bill.balance_due) > 0) {
                    payBtn.classList.remove('hidden');
                    payBtn.setAttribute('onclick', `recordQuickPayment('${bill.bill_id}', ${bill.balance_due})`);
                } else {
                    payBtn.classList.add('hidden');
                }
            }
        },
        error: function (xhr) {
            console.error('Error fetching bill details:', xhr);
            alert('Could not fetch bill details. Please try again.');
            toggleBillModal();
        }
    });
}

/**
 * Record payment from modal
 */
function recordQuickPayment(billId, amount) {
    const confirmPay = confirm(`Record full payment of ₹${amount} for ${billId}?`);
    if (confirmPay) {
        $.ajax({
            url: '../api/index.php/api/billing/opd/payment',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                bill_id: billId,
                amount: amount,
                payment_mode: 'Cash',
                notes: 'Quick payment from details card'
            }),
            success: function (response) {
                if (response.status === 'success') {
                    showSuccess('Payment recorded successfully!');
                    toggleBillModal();
                    loadBills();
                    loadStatistics();
                }
            }
        });
    }
}

/**
 * Toggle bill modal visibility
 */
function toggleBillModal() {
    document.getElementById('bill-details-modal').classList.toggle('hidden');
}

/**
 * View bill details
 */
function viewBill(billId) {
    showBillDetails(billId);
}

/**
 * Print bill
 */
function printBill(billId) {
    window.open(`print_bill.php?bill_id=${billId}`, '_blank');
}

/**
 * Switch between tabs
 */
function switchTab(tab) {
    currentTab = tab;

    // Update tab UI
    document.querySelectorAll('.billing-tab').forEach(t => t.classList.remove('active'));
    event.target.closest('.billing-tab').classList.add('active');

    // Load appropriate data based on tab
    if (tab === 'opd') {
        loadOPDBills();
    } else if (tab === 'ipd') {
        loadIPDBills();
    } else if (tab === 'payments') {
        loadPayments();
    } else if (tab === 'reports') {
        loadReports();
    }
}

/**
 * Load OPD Bills
 */
function loadOPDBills() {
    const tbody = document.getElementById('bills-tbody');
    tbody.innerHTML = '<tr><td colspan="9" class="text-center text-gray-500 py-4">Loading OPD bills...</td></tr>';
    loadBills(); // Use existing function
}

/**
 * Load IPD Bills
 */
function loadIPDBills() {
    const tbody = document.getElementById('bills-tbody');
    tbody.innerHTML = '<tr><td colspan="9" class="text-center text-gray-500 py-4">Loading IPD bills...</td></tr>';
    
    const status = document.getElementById('filter-status').value;
    let url = '../api/index.php/api/billing/ipd';
    if (status) {
        url += `?payment_status=${status}`;
    }

    $.ajax({
        url: url,
        method: 'GET',
        success: function (response) {
            if (response.status === 'success') {
                renderIPDBillsTable(response.data);
            }
        },
        error: function (xhr) {
            console.error('Error loading IPD bills:', xhr);
            document.getElementById('bills-tbody').innerHTML =
                '<tr><td colspan="9" class="text-center text-red-500 py-4">Error loading IPD bills</td></tr>';
        }
    });
}

function renderIPDBillsTable(bills) {
    const tbody = document.getElementById('bills-tbody');

    if (bills.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-gray-500 py-4">No IPD bills found</td></tr>';
        return;
    }

    tbody.innerHTML = bills.map(bill => {
        const statusColors = {
            'Paid': 'bg-green-100 text-green-800',
            'Partial': 'bg-yellow-100 text-yellow-800',
            'Pending': 'bg-red-100 text-red-800',
            'Cancelled': 'bg-gray-100 text-gray-800'
        };

        return `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <a href="javascript:void(0)" onclick="showIPDBillDetails('${bill.bill_id}')" class="text-blue-600 hover:text-blue-800 font-black decoration-2 underline-offset-4 hover:underline">
                        ${bill.bill_id}
                    </a>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${bill.patient_name || '-'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${bill.doctor_name || '-'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${bill.admission_date}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">₹${parseFloat(bill.grand_total).toFixed(2)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">₹${parseFloat(bill.amount_paid).toFixed(2)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">₹${parseFloat(bill.balance_due).toFixed(2)}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full ${statusColors[bill.payment_status] || 'bg-gray-100 text-gray-800'}">
                        ${bill.payment_status}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right relative">
                    <button onclick="showIPDBillDetails('${bill.bill_id}')" class="text-blue-600 hover:text-blue-800 p-2 rounded-full hover:bg-blue-50 transition-colors">
                        <i class="fas fa-eye w-4 text-center"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function showIPDBillDetails(billId) {
    toggleIPDBillModal();
    document.getElementById('ipd-modal-bill-id').textContent = 'Loading...';

    $.ajax({
        url: `../api/index.php/api/billing/ipd/${billId}`,
        method: 'GET',
        success: function (response) {
            if (response.status === 'success') {
                const bill = response.data;
                const fmt = (val) => `₹${parseFloat(val || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;

                document.getElementById('ipd-modal-bill-id').textContent = bill.bill_id;
                document.getElementById('ipd-detail-patient-name').textContent = bill.patient_name || 'N/A';
                document.getElementById('ipd-detail-patient-id').textContent = bill.patient_id || 'N/A';
                document.getElementById('ipd-detail-patient-phone').textContent = bill.phone || 'N/A';
                document.getElementById('ipd-detail-doctor-name').textContent = bill.doctor_name ? `Dr. ${bill.doctor_name}` : 'N/A';
                document.getElementById('ipd-detail-admission-id').textContent = bill.admission_id || 'N/A';
                
                document.getElementById('ipd-detail-bill-date').textContent = bill.admission_date;
                document.getElementById('ipd-detail-balance-due').textContent = fmt(bill.balance_due);
                document.getElementById('ipd-detail-total-days').textContent = bill.total_days || '1';

                const statusEl = document.getElementById('ipd-detail-payment-status');
                statusEl.textContent = bill.payment_status;
                statusEl.className = `px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest ${bill.payment_status === 'Paid' ? 'bg-green-100 text-green-700' :
                        bill.payment_status === 'Partial' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'
                    }`;

                // Footer Stats
                document.getElementById('ipd-foot-subtotal').textContent = fmt(bill.subtotal);
                document.getElementById('ipd-foot-tax').textContent = fmt(bill.tax_amount);
                document.getElementById('ipd-foot-grand-total').textContent = fmt(bill.grand_total);
                document.getElementById('ipd-foot-amount-paid').textContent = fmt(bill.amount_paid);

                // Group items by category
                const itemsTbody = document.getElementById('ipd-detail-items-tbody');
                if (bill.items && bill.items.length > 0) {
                    let currentCategory = '';
                    let itemsHtml = '';
                    
                    bill.items.forEach(item => {
                        if (item.charge_type !== currentCategory) {
                            currentCategory = item.charge_type;
                            itemsHtml += `
                                <tr class="bg-slate-100">
                                    <td colspan="5" class="px-6 py-2 font-black text-slate-700 text-xs uppercase tracking-widest">
                                        <i class="fas fa-layer-group mr-2 text-blue-500"></i> ${currentCategory} Charges
                                    </td>
                                </tr>
                            `;
                        }
                        itemsHtml += `
                            <tr class="border-b border-slate-50 hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-3 text-xs font-medium text-slate-500">${item.charge_date}</td>
                                <td class="px-6 py-3 font-medium text-slate-700">${item.item_name}</td>
                                <td class="px-6 py-3 text-center text-slate-500 font-bold">${parseFloat(item.quantity)}</td>
                                <td class="px-6 py-3 text-right text-slate-500">${fmt(item.unit_price)}</td>
                                <td class="px-6 py-3 text-right font-black text-slate-900">${fmt(item.total_price)}</td>
                            </tr>
                        `;
                    });
                    itemsTbody.innerHTML = itemsHtml;
                } else {
                    itemsTbody.innerHTML = '<tr><td colspan="5" class="text-center py-6 text-slate-400">No itemized charges found for this admission.</td></tr>';
                }

                // Add Manual Charge Button Setup
                document.getElementById('btn-add-ipd-charge').setAttribute('onclick', `openAddIpdChargeModal('${bill.bill_id}')`);

                // Payment Button Setup
                const payBtn = document.getElementById('ipd-btn-pay-modal');
                if (parseFloat(bill.balance_due) > 0) {
                    payBtn.classList.remove('hidden');
                    payBtn.setAttribute('onclick', `recordIpdQuickPayment('${bill.bill_id}', ${bill.balance_due})`);
                } else {
                    payBtn.classList.add('hidden');
                }
            }
        }
    });
}

function toggleIPDBillModal() {
    document.getElementById('ipd-bill-details-modal').classList.toggle('hidden');
}

function openAddIpdChargeModal(billId) {
    const chargeType = prompt("Enter Charge Category (e.g. Room, Nursing, Lab, Pharmacy, Procedure):", "Procedure");
    if(!chargeType) return;
    
    const itemName = prompt("Enter specific service/item name:", "General Service");
    if(!itemName) return;
    
    const qty = prompt("Enter Quantity:", "1");
    if(!qty) return;
    
    const price = prompt("Enter Unit Price (₹):", "0.00");
    if(!price) return;

    $.ajax({
        url: `../api/index.php/api/billing/ipd/${billId}/add-item`,
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            charge_type: chargeType,
            item_name: itemName,
            quantity: parseFloat(qty),
            unit_price: parseFloat(price)
        }),
        success: function (response) {
            if (response.status === 'success') {
                showSuccess('Charge added successfully!');
                showIPDBillDetails(billId); // Refresh modal
                loadIPDBills(); // Refresh table
            } else {
                showError('Error adding charge');
            }
        }
    });
}

function recordIpdQuickPayment(billId, amount) {
    const confirmPay = confirm(`Record full payment of ₹${amount} for IPD Bill ${billId}?`);
    if (confirmPay) {
        $.ajax({
            url: '../api/index.php/api/billing/ipd/payment',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                bill_id: billId,
                amount: amount,
                payment_method: 'Cash',
                notes: 'Quick payment from IPD details card'
            }),
            success: function (response) {
                if (response.status === 'success') {
                    showSuccess('Payment recorded successfully!');
                    showIPDBillDetails(billId); // Refresh modal
                    loadIPDBills();
                }
            }
        });
    }
}

/**
 * Load Payments
 */
function loadPayments() {
    const tbody = document.getElementById('bills-tbody');
    tbody.innerHTML = `
        <tr><td colspan="9" class="text-center py-8">
            <div class="text-gray-500">
                <i class="fas fa-money-bill-wave text-4xl mb-3"></i>
                <p class="font-semibold">Payment Tracking</p>
                <p class="text-sm mt-2">All payment receipts from OPD and IPD billing will appear here</p>
                <p class="text-xs mt-4 text-gray-400">Create bills with payments to see them listed here</p>
            </div>
        </td></tr>
    `;
}

/**
 * Load Reports
 */
function loadReports() {
    const tbody = document.getElementById('bills-tbody');

    tbody.innerHTML = `
        <tr><td colspan="9" class="p-0">
            <div class="p-8">
                <h3 class="text-xl font-bold text-gray-900 mb-6">
                    <i class="fas fa-chart-bar text-blue-600 mr-2"></i>
                    Billing Reports & Analytics
                </h3>
                
                <!-- Report Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Today's Collection -->
                    <div class="bento-card">
                        <div class="bento-title">Today's Collection</div>
                        <h3 class="bento-value" id="report-today-collection">₹0.00</h3>
                        <i class="fas fa-calendar-day bento-icon"></i>
                    </div>
                    
                    <!-- This Month -->
                    <div class="bento-card">
                        <div class="bento-title">This Month</div>
                        <h3 class="bento-value" id="report-month-collection">₹0.00</h3>
                        <i class="fas fa-calendar-alt bento-icon"></i>
                    </div>
                    
                    <!-- Outstanding -->
                    <div class="bento-card">
                        <div class="bento-title">Outstanding</div>
                        <h3 class="bento-value" id="report-outstanding">₹0.00</h3>
                        <i class="fas fa-exclamation-triangle bento-icon"></i>
                    </div>
                </div>
                
                <!-- Report Tables -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Payment Method Breakdown -->
                    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                            <h5 class="font-semibold text-gray-900">Payment Method Breakdown</h5>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Cash</span>
                                    <span class="text-sm font-semibold text-gray-900">₹0.00</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Card</span>
                                    <span class="text-sm font-semibold text-gray-900">₹0.00</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">UPI</span>
                                    <span class="text-sm font-semibold text-gray-900">₹0.00</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Others</span>
                                    <span class="text-sm font-semibold text-gray-900">₹0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bill Status Summary -->
                    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                            <h5 class="font-semibold text-gray-900">Bill Status Summary</h5>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Paid Bills</span>
                                    <span class="text-sm font-semibold text-green-600">0</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Partial Payments</span>
                                    <span class="text-sm font-semibold text-yellow-600">0</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Pending Bills</span>
                                    <span class="text-sm font-semibold text-red-600">0</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Total Bills</span>
                                    <span class="text-sm font-semibold text-gray-900">0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Export Options -->
                <div class="mt-8 flex gap-3">
                    <button onclick="alert('Exporting as PDF...')" class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all flex items-center gap-2">
                        <i class="fas fa-file-pdf"></i>
                        Export as PDF
                    </button>
                    <button onclick="alert('Exporting as Excel...')" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all flex items-center gap-2">
                        <i class="fas fa-file-excel"></i>
                        Export as Excel
                    </button>
                    <button onclick="window.print()" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all flex items-center gap-2">
                        <i class="fas fa-print"></i>
                        Print Report
                    </button>
                </div>
            </div>
        </td></tr>
    `;

    // Load report data
    loadReportData();
}

/**
 * Load Report Data
 */
function loadReportData() {
    $.ajax({
        url: '../api/index.php/api/billing/opd/stats',
        method: 'GET',
        success: function (response) {
            if (response.status === 'success') {
                const stats = response.data;
                document.getElementById('report-today-collection').textContent = `₹${parseFloat(stats.today_revenue || 0).toFixed(2)}`;
                document.getElementById('report-month-collection').textContent = `₹${parseFloat(stats.month_revenue || 0).toFixed(2)}`;
                document.getElementById('report-outstanding').textContent = `₹${parseFloat(stats.outstanding_amount || 0).toFixed(2)}`;
            }
        }
    });
}

/**
 * Search bills
 */
$('#search-bills').on('keyup', function () {
    const searchTerm = $(this).val().toLowerCase();
    $('#bills-tbody tr').each(function () {
        const text = $(this).text().toLowerCase();
        $(this).toggle(text.indexOf(searchTerm) > -1);
    });
});

/**
 * Show success notification
 */
function showSuccess(message) {
    // Remove ✅ from message if it was passed by any older call
    message = message.replace('✅ ', '');
    
    const toast = document.createElement('div');
    toast.className = 'fixed inset-0 flex items-center justify-center z-[9999] pointer-events-none transition-opacity duration-300';
    toast.innerHTML = `
        <div class="bg-white px-10 py-8 rounded-3xl shadow-2xl flex flex-col items-center gap-4 transform scale-95 transition-transform duration-300 border border-green-100" style="box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
            <div class="w-20 h-20 rounded-full bg-green-100 flex items-center justify-center text-green-500 text-4xl mb-2">
                <i class="fas fa-check"></i>
            </div>
            <p class="text-xl font-black text-slate-800 text-center whitespace-pre-wrap">${message}</p>
        </div>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        const inner = toast.querySelector('div');
        if(inner) { inner.classList.remove('scale-95'); inner.classList.add('scale-100'); }
    }, 10);

    setTimeout(() => {
        toast.classList.add('opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 2500);
}

/**
 * Show error notification
 */
function showError(message) {
    // Remove ❌ from message if it was passed
    message = message.replace('❌ ', '');
    
    const toast = document.createElement('div');
    toast.className = 'fixed inset-0 flex items-center justify-center z-[9999] pointer-events-none transition-opacity duration-300';
    toast.innerHTML = `
        <div class="bg-white px-10 py-8 rounded-3xl shadow-2xl flex flex-col items-center gap-4 transform scale-95 transition-transform duration-300 border border-red-100" style="box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
            <div class="w-20 h-20 rounded-full bg-red-100 flex items-center justify-center text-red-500 text-4xl mb-2">
                <i class="fas fa-times"></i>
            </div>
            <p class="text-xl font-black text-slate-800 text-center whitespace-pre-wrap">${message}</p>
        </div>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        const inner = toast.querySelector('div');
        if(inner) { inner.classList.remove('scale-95'); inner.classList.add('scale-100'); }
    }, 10);

    setTimeout(() => {
        toast.classList.add('opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Toggle Action Dropdown
 */
function toggleActionDropdown(event, billId) {
    event.stopPropagation();
    closeAllDropdowns();
    const dropdown = document.getElementById(`dropdown-${billId}`);
    if (dropdown) {
        dropdown.classList.remove('hidden');
    }
}

/**
 * Close All Dropdowns
 */
function closeAllDropdowns() {
    document.querySelectorAll('.action-dropdown').forEach(dropdown => {
        dropdown.classList.add('hidden');
    });
}

// Close dropdowns when clicking outside
document.addEventListener('click', function (event) {
    if (!event.target.closest('.action-dropdown-container')) {
        closeAllDropdowns();
    }
});

/**
 * Delete Bill
 */
function deleteBill(billId) {
    if (confirm(`Are you sure you want to completely delete Bill ${billId}? This action cannot be undone.`)) {
        $.ajax({
            url: `../api/index.php/api/billing/opd/${billId}`,
            method: 'DELETE',
            success: function (response) {
                if (response.status === 'success') {
                    showSuccess('Bill deleted successfully');
                    loadBills();
                    loadStatistics();
                } else {
                    showError(response.message || 'Error deleting bill');
                }
            },
            error: function (xhr) {
                console.error('Delete error:', xhr);
                showError('Failed to delete bill.');
            }
        });
    }
}

/**
 * Edit Bill
 */
function editBill(billId) {
    // Fetch full bill details
    $.ajax({
        url: `../api/index.php/api/billing/opd/${billId}`,
        method: 'GET',
        success: function (response) {
            if (response.status === 'success') {
                const bill = response.data;
                editingBillId = bill.bill_id;

                // Open form and switch context
                const container = document.getElementById('billing-form-container');
                container.classList.remove('hidden');

                $('#form-mode-title').html(`<i class="fas fa-edit text-amber-500"></i> Edit Bill: ${bill.bill_id}`);
                $('#btn-submit-bill').html('<i class="fas fa-save"></i> Update Invoice');

                // Populate basic fields
                $('#patient-select').append(new Option(`${bill.patient_name} (${bill.patient_id})`, bill.patient_id, true, true)).trigger('change');
                if (bill.doctor_id) {
                    $('#doctor-select').append(new Option(`Dr. ${bill.doctor_name}`, bill.doctor_id, true, true)).trigger('change');
                }

                $('#discount-amount').val(parseFloat(bill.discount_amount) || 0);
                $('textarea[name="notes"]').val(bill.notes || '');
                $('select[name="payment_method"]').val(bill.payment_mode || 'Cash');

                // Populate items
                billingItems = [];
                if (bill.items && bill.items.length > 0) {
                    bill.items.forEach(item => {
                        billingItems.push({
                            id: Date.now() + Math.random(),
                            item_type: 'Other', // In a full implementation, derive from service_id
                            item_name: item.item_name,
                            quantity: parseInt(item.quantity) || 1,
                            unit_price: parseFloat(item.unit_price) || 0,
                            total_price: parseFloat(item.total_price) || 0
                        });
                    });
                } else if (bill.purpose === 'Registration/Appointment') {
                    // Fallback for registration fees
                    billingItems.push({
                        id: Date.now(),
                        item_type: 'Consultation',
                        item_name: 'Registration/Consultation Fee',
                        quantity: 1,
                        unit_price: parseFloat(bill.subtotal) || 0,
                        total_price: parseFloat(bill.subtotal) || 0
                    });
                }

                renderBillingItems();
                calculateTotals();
                // Override the amount_paid that was auto-calculated by calculateTotals
                $('#amount-paid').val(parseFloat(bill.amount_paid) || 0);

                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                showError('Failed to load bill details');
            }
        },
        error: function (xhr) {
            console.error('Fetch error:', xhr);
            showError('Failed to fetch bill data for editing');
        }
    });
}

/**
 * Handle Tab Switching
 */
function switchTab(tabId) {
    // 1. Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(el => {
        el.classList.add('hidden');
    });
    
    // 2. Remove 'active' class from all tabs
    document.querySelectorAll('.billing-tab').forEach(el => {
        el.classList.remove('active');
        el.style.fontWeight = '500';
    });
    
    // 3. Show selected tab content
    const targetContent = document.getElementById('tab-' + tabId);
    if(targetContent) targetContent.classList.remove('hidden');
    
    // 4. Set 'active' on clicked tab
    document.querySelectorAll('.billing-tab').forEach(el => {
        if(el.getAttribute('onclick').includes(tabId)) {
            el.classList.add('active');
            el.style.fontWeight = '700';
        }
    });

    // If analytics tab is clicked, load data
    if(tabId === 'reports') {
        loadAnalytics();
    }
}

/**
 * ==============================================================
 * OPD BILLING ANALYTICS DASHBOARD
 * ==============================================================
 */

let weeklyRevenueChartInstance = null;
let paymentChartInstance = null;

function loadAnalytics() {
    const startDate = document.getElementById('analytics-start').value;
    const endDate = document.getElementById('analytics-end').value;
    const receptionist = document.getElementById('analytics-receptionist').value;
    const method = document.getElementById('analytics-method').value;

    let url = '../api/index.php/api/billing/opd/analytics?';
    if(startDate) url += `start_date=${startDate}&`;
    if(endDate) url += `end_date=${endDate}&`;
    if(receptionist) url += `receptionist=${receptionist}&`;
    if(method) url += `payment_mode=${method}&`;

    $.ajax({
        url: url,
        method: 'GET',
        success: function (res) {
            if (res.status === 'success') {
                renderAnalytics(res.data);
            }
        },
        error: function (xhr) {
            console.error('Failed to load analytics', xhr);
        }
    });
}

function renderAnalytics(data) {
    const metrics = data.metrics;
    const receptionistPerf = data.receptionist_performance;
    const paymentMethods = data.payment_methods;
    const trends = data.trends;

    // 1. Update KPI Cards
    document.getElementById('kpi-total-bills').innerText = metrics.total_bills || 0;
    document.getElementById('kpi-total-billing').innerText = '₹' + parseFloat(metrics.total_billing_amount || 0).toFixed(2);
    document.getElementById('kpi-collected').innerText = '₹' + parseFloat(metrics.total_collected || 0).toFixed(2);
    document.getElementById('kpi-pending').innerText = '₹' + parseFloat(metrics.total_pending || 0).toFixed(2);
    document.getElementById('kpi-discount').innerText = '₹' + parseFloat(metrics.total_discount || 0).toFixed(2);
    document.getElementById('kpi-refunds').innerText = '₹' + parseFloat(metrics.total_refund || 0).toFixed(2);
    document.getElementById('kpi-cancelled').innerText = metrics.cancelled_bills || 0;
    
    let avg = 0;
    if (metrics.total_bills > 0) {
        avg = parseFloat(metrics.total_billing_amount) / parseFloat(metrics.total_bills);
    }
    document.getElementById('kpi-avg').innerText = '₹' + avg.toFixed(2);

    // 2. Populate Receptionist Table & Dropdown
    const rTable = document.getElementById('receptionist-performance-tbody');
    const rSelect = document.getElementById('analytics-receptionist');
    const currentSelected = rSelect.value;
    
    if (receptionistPerf.length === 0) {
        rTable.innerHTML = '<tr><td colspan="6" class="text-center py-6 text-slate-500">No data available for this period.</td></tr>';
    } else {
        let html = '';
        let options = '<option value="">All Receptionists</option>';
        receptionistPerf.forEach((r, index) => {
            let badge = '';
            if(index === 0) badge = '<i class="fas fa-medal text-yellow-500 mr-2"></i>';
            else if(index === 1) badge = '<i class="fas fa-medal text-gray-400 mr-2"></i>';
            else if(index === 2) badge = '<i class="fas fa-medal text-amber-700 mr-2"></i>';

            html += `
                <tr class="hover:bg-gray-50 text-sm">
                    <td class="px-6 py-4 font-bold text-slate-500">#${index + 1}</td>
                    <td class="px-6 py-4 font-bold text-[#1f6b4a]">${badge}${r.receptionist || 'System'}</td>
                    <td class="px-6 py-4 text-center">${r.bills_generated}</td>
                    <td class="px-6 py-4 text-right font-medium">₹${parseFloat(r.total_billing).toFixed(2)}</td>
                    <td class="px-6 py-4 text-right font-bold text-green-600">₹${parseFloat(r.collected).toFixed(2)}</td>
                    <td class="px-6 py-4 text-right font-bold text-red-500">₹${parseFloat(r.pending).toFixed(2)}</td>
                </tr>
            `;
            // Only populate dropdown once (if it's empty other than default)
            if (rSelect.options.length <= 1 || rSelect.querySelector(`option[value="${r.receptionist}"]`)) {
                options += `<option value="${r.receptionist}">${r.receptionist || 'System'}</option>`;
            }
        });
        rTable.innerHTML = html;
        if(rSelect.options.length <= 1) {
            rSelect.innerHTML = options;
            rSelect.value = currentSelected;
        }
    }

    // 3. Render Charts
    const receptionist = document.getElementById('analytics-receptionist').value;
    const method = document.getElementById('analytics-method').value;
    renderWeeklyRevenueChart(receptionist, method);
    renderPaymentChart(paymentMethods);
}

function renderWeeklyRevenueChart(receptionist = '', method = '') {
    const end = new Date();
    const start = new Date();
    start.setDate(end.getDate() - 6);
    
    const startDateStr = start.getFullYear() + '-' + String(start.getMonth() + 1).padStart(2, '0') + '-' + String(start.getDate()).padStart(2, '0');
    const endDateStr = end.getFullYear() + '-' + String(end.getMonth() + 1).padStart(2, '0') + '-' + String(end.getDate()).padStart(2, '0');
    
    let url = `../api/index.php/api/billing/opd/analytics?start_date=${startDateStr}&end_date=${endDateStr}`;
    if (receptionist) url += `&receptionist=${receptionist}`;
    if (method) url += `&payment_mode=${method}`;

    $.ajax({
        url: url,
        method: 'GET',
        success: function(res) {
            if (res.status === 'success') {
                const trends = res.data.trends || [];
                const ctx = document.getElementById('weeklyRevenueChart').getContext('2d');
                
                const labels = [];
                const revenue = [];
                
                for (let i = 6; i >= 0; i--) {
                    const d = new Date();
                    d.setDate(d.getDate() - i);
                    const dateStr = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                    const displayStr = d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
                    
                    labels.push(displayStr);
                    const trendData = trends.find(t => t.trend_date === dateStr);
                    revenue.push(trendData ? parseFloat(trendData.revenue || 0) : 0);
                }

                if(weeklyRevenueChartInstance) weeklyRevenueChartInstance.destroy();

                weeklyRevenueChartInstance = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Revenue (₹)',
                            data: revenue,
                            backgroundColor: 'rgba(31, 107, 74, 0.8)',
                            borderColor: '#1f6b4a',
                            borderWidth: 1,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }
        }
    });
}

function renderPaymentChart(paymentMethods) {
    const ctx = document.getElementById('paymentChart').getContext('2d');
    
    const labels = paymentMethods.map(p => p.method);
    const data = paymentMethods.map(p => parseFloat(p.total));
    const bgColors = ['#1f6b4a', '#3b82f6', '#f59e0b', '#8b5cf6', '#64748b'];

    if(paymentChartInstance) paymentChartInstance.destroy();

    paymentChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: bgColors,
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
}

// Set default dates on load
document.addEventListener('DOMContentLoaded', () => {
    const now = new Date();
    // Default to the beginning of the current year so old dummy data is included
    const firstDay = new Date(now.getFullYear(), 0, 1);
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    
    document.getElementById('analytics-start').value = firstDay.toISOString().split('T')[0];
    document.getElementById('analytics-end').value = lastDay.toISOString().split('T')[0];
});
