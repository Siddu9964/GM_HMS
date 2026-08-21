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
    loadAdvancedReceipts(1);

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
    // If receipts tab is clicked, load advanced receipts
    if(tabId === 'payments') {
        loadAdvancedReceipts(1);
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

/**
 * ==============================================================
 * ADVANCED RECEIPTS & REVENUE WORKSTATION MODULE
 * ==============================================================
 */

let recState = {
    search: '',
    date_preset: 'all',
    date_from: '',
    date_to: '',
    created_by: '',
    department: '',
    doctor: '',
    payment_status: '',
    payment_mode: '',
    has_outstanding: false,
    is_duplicate: false,
    high_value: false,
    page: 1,
    limit: 25,
    sort_by: 'date_desc',
    active_subtab: 'ledger',
    records: [],
    summary: {},
    hourly_shift: [],
    breakdowns: {}
};

let recSearchDebounceTimer = null;
let recTrendsChartInstance = null;
let recModeChartInstance = null;
let activeCancelBillId = null;

/**
 * Load Advanced Receipts from API
 */
function loadAdvancedReceipts(page = 1) {
    recState.page = page;
    const tbody = document.getElementById('receipts-tbody');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="11" class="text-center py-12 text-slate-500">
                    <i class="fas fa-spinner fa-spin text-2xl text-[#1f6b4a]"></i>
                    <p class="mt-2 text-xs font-bold text-slate-600">Fetching live receipts & collection data...</p>
                </td>
            </tr>
        `;
    }

    const params = new URLSearchParams();
    if (recState.search) params.append('search', recState.search);
    if (recState.date_preset && !recState.date_from) params.append('date_preset', recState.date_preset);
    if (recState.date_from) params.append('date_from', recState.date_from);
    if (recState.date_to) params.append('date_to', recState.date_to);
    if (recState.created_by) params.append('created_by', recState.created_by);
    if (recState.department) params.append('department', recState.department);
    if (recState.doctor) params.append('doctor', recState.doctor);
    if (recState.payment_status) params.append('payment_status', recState.payment_status);
    if (recState.payment_mode) params.append('payment_mode', recState.payment_mode);
    if (recState.has_outstanding) params.append('has_outstanding', 'true');
    if (recState.high_value) params.append('high_value', 'true');
    if (recState.sort_by) params.append('sort_by', recState.sort_by);
    params.append('page', recState.page);
    params.append('limit', recState.limit);

    $.ajax({
        url: `../api/index.php/api/billing/receipts?${params.toString()}`,
        method: 'GET',
        success: function (res) {
            if (res.status === 'success') {
                const data = res.data;
                recState.records = data.records || [];
                recState.summary = data.summary_kpis || {};
                recState.hourly_shift = data.hourly_shift || [];
                recState.breakdowns = data.breakdowns || {};
                recState.pagination = data.pagination || {};

                // 1. Render Summary KPIs
                renderReceiptKPIs(recState.summary);

                // 2. Render Main Table & Pagination
                renderReceiptsTable(recState.records);
                renderReceiptPagination(recState.pagination);

                // 3. Render Sub-views
                renderCashierShiftHandoverMatrix(recState.breakdowns.staff || [], recState.hourly_shift || [], recState.summary || {});
                renderDepartmentHierarchyMatrix(recState.breakdowns.department_hierarchy || [], recState.summary);
                renderReceiptStaffBreakdown(recState.breakdowns.staff || []);

                // 4. Populate Dropdowns (if empty)
                populateReceiptFilterDropdowns(recState.breakdowns);

                // 5. Render Charts if on charts tab
                if (recState.active_subtab === 'charts') {
                    renderReceiptCharts(recState.hourly_shift, recState.breakdowns.payment_mode || []);
                }
            } else {
                showError(res.message || 'Failed to load receipts data');
            }
        },
        error: function (xhr) {
            console.error('Error fetching receipts:', xhr);
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="11" class="text-center py-8 text-rose-500 font-bold">
                            <i class="fas fa-exclamation-triangle text-xl mb-2"></i>
                            <p>Unable to connect to billing receipts service. Please refresh.</p>
                        </td>
                    </tr>
                `;
            }
        }
    });
}

/**
 * Render Receipt Bento KPIs
 */
function renderReceiptKPIs(kpi) {
    const fmt = (v) => '₹' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const elCount = document.getElementById('kpi-rec-total-count');
    const elCollected = document.getElementById('kpi-rec-total-collected');
    const elPending = document.getElementById('kpi-rec-total-pending');
    const elDiscount = document.getElementById('kpi-rec-total-discount');
    const elRefunds = document.getElementById('kpi-rec-total-refunds');
    const elAvg = document.getElementById('kpi-rec-avg-value');
    const elCountSub = document.getElementById('kpi-rec-count-sub');
    const elTodaySub = document.getElementById('kpi-rec-today-sub');
    const elCancelCount = document.getElementById('kpi-rec-cancel-count');

    if (elCount) elCount.innerText = (kpi.total_bills || 0).toLocaleString();
    if (elCollected) elCollected.innerText = fmt(kpi.total_collection);
    if (elPending) elPending.innerText = fmt(kpi.total_pending);
    if (elDiscount) elDiscount.innerText = fmt(kpi.total_discount);
    if (elRefunds) elRefunds.innerText = fmt(kpi.total_refunds);
    if (elAvg) elAvg.innerText = fmt(kpi.avg_bill_value);

    if (elCountSub) elCountSub.innerText = `${kpi.paid_bills_count || 0} Paid • ${kpi.pending_bills_count || 0} Pending`;
    if (elTodaySub) elTodaySub.innerText = `Today: ${fmt(kpi.today_collection)}`;
    if (elCancelCount) elCancelCount.innerText = `${kpi.cancelled_bills_count || 0} Cancelled / Refunded`;
}

/**
 * Render Receipts Main Data Table
 */
function renderReceiptsTable(records) {
    const tbody = document.getElementById('receipts-tbody');
    if (!tbody) return;

    if (!records || records.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="11" class="px-6 py-12 text-center text-slate-500">
                    <i class="fas fa-receipt text-3xl text-slate-300 mb-2"></i>
                    <p class="font-bold text-sm text-slate-700">No receipts match your search filters</p>
                    <p class="text-xs text-slate-400 mt-1">Try adjusting your date range, cashier, or status filter.</p>
                </td>
            </tr>
        `;
        return;
    }

    const fmt = (v) => '₹' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const statusBadgeClass = {
        'Paid': 'bg-emerald-100 text-emerald-800 border-emerald-300',
        'Partial': 'bg-amber-100 text-amber-800 border-amber-300',
        'Pending': 'bg-rose-100 text-rose-800 border-rose-300',
        'Cancelled': 'bg-slate-200 text-slate-800 border-slate-300',
        'Refunded': 'bg-purple-100 text-purple-800 border-purple-300'
    };

    const modeBadgeClass = {
        'Cash': 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'UPI': 'bg-blue-50 text-blue-700 border-blue-200',
        'Card': 'bg-purple-50 text-purple-700 border-purple-200',
        'Net Banking': 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'Cheque': 'bg-amber-50 text-amber-700 border-amber-200',
        'Insurance': 'bg-teal-50 text-teal-700 border-teal-200'
    };

    tbody.innerHTML = records.map(r => {
        const isDue = parseFloat(r.balance_due || 0) > 0;
        const isDuplicate = r.is_potential_duplicate;

        let dupTag = isDuplicate ? `<span class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-amber-100 text-amber-800 text-[9px] font-black rounded border border-amber-300 ml-1" title="Potential duplicate bill for same patient on same date"><i class="fas fa-clone text-[8px]"></i> Dup</span>` : '';
        let dueAlert = isDue ? `<div class="text-[10px] font-black text-rose-600 flex items-center gap-0.5 mt-0.5"><i class="fas fa-exclamation-circle text-[9px]"></i> Due ${fmt(r.balance_due)}</div>` : '';

        return `
            <tr class="hover:bg-[#f3efe6]/40 transition-colors text-xs border-b border-slate-100">
                <!-- Receipt / Bill ID -->
                <td class="px-4 py-3.5 whitespace-nowrap">
                    <div class="flex items-center">
                        <button type="button" onclick="openReceiptDrawer('${r.bill_id}')" class="font-black text-[#1f6b4a] hover:underline text-left" title="Click to view detailed receipt preview">
                            ${r.receipt_id}
                        </button>
                        ${dupTag}
                    </div>
                    <div class="text-[10px] text-slate-400 font-semibold mt-0.5">Ref: ${r.bill_id}</div>
                </td>

                <!-- Patient Details -->
                <td class="px-4 py-3.5 whitespace-nowrap">
                    <div class="font-bold text-slate-800 flex items-center gap-1.5">
                        <i class="fas fa-user-circle text-slate-400"></i>
                        <span>${r.patient_name || 'Walking Patient'}</span>
                    </div>
                    <div class="text-[10px] text-slate-500 font-medium mt-0.5">
                        ${r.patient_id ? `<span class="text-[#1f6b4a] font-bold">${r.patient_id}</span>` : ''} 
                        ${r.patient_phone && r.patient_phone !== '-' ? `• ${r.patient_phone}` : ''}
                    </div>
                </td>

                <!-- Department & Doctor -->
                <td class="px-4 py-3.5 whitespace-nowrap">
                    <div class="font-bold text-slate-800 flex items-center gap-1">
                        <i class="fas fa-user-md text-[#1f6b4a]"></i>
                        <span>${r.doctor_name || 'Direct Hospital Service'}</span>
                    </div>
                    <div class="text-[10px] text-slate-500 font-semibold mt-0.5 flex items-center gap-1.5">
                        <span class="px-2 py-0.5 bg-[#e8f4ed] text-[#1f6b4a] rounded-md text-[9.5px] font-black border border-[#1f6b4a25]">
                            <i class="fas fa-stethoscope text-[8px] mr-1"></i>${r.department_name || r.doctor_specialization || 'General Medicine'}
                        </span>
                        <span class="text-slate-400">(${r.item_count || 1} items)</span>
                    </div>
                </td>

                <!-- Date & Time -->
                <td class="px-4 py-3.5 whitespace-nowrap">
                    <div class="font-semibold text-slate-700">${r.bill_date}</div>
                    <div class="text-[10px] text-slate-400 font-medium mt-0.5">${r.bill_time || ''}</div>
                </td>

                <!-- Grand Total -->
                <td class="px-4 py-3.5 text-right font-bold text-slate-800 whitespace-nowrap">
                    ${fmt(r.grand_total)}
                </td>

                <!-- Received -->
                <td class="px-4 py-3.5 text-right font-black text-emerald-700 whitespace-nowrap">
                    ${fmt(r.amount_paid)}
                </td>

                <!-- Balance Due -->
                <td class="px-4 py-3.5 text-right whitespace-nowrap">
                    ${isDue ? `<span class="font-black text-rose-600">${fmt(r.balance_due)}</span>` : `<span class="text-emerald-600 font-bold"><i class="fas fa-check-circle mr-1"></i>0.00</span>`}
                </td>

                <!-- Payment Mode -->
                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border ${modeBadgeClass[r.payment_mode] || 'bg-slate-100 text-slate-700 border-slate-200'}">
                        ${r.payment_mode || 'Cash'}
                    </span>
                </td>

                <!-- Status -->
                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border ${statusBadgeClass[r.payment_status] || 'bg-slate-100 text-slate-700 border-slate-200'}">
                        ${r.payment_status || 'Paid'}
                    </span>
                </td>

                <!-- Cashier -->
                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                    <span class="text-[11px] font-semibold text-slate-600 bg-slate-100 px-2 py-0.5 rounded-md">
                        <i class="fas fa-user-tie text-[9px] mr-1 text-slate-400"></i>${r.created_by || 'System'}
                    </span>
                </td>

                <!-- Actions -->
                <td class="px-4 py-3.5 text-center whitespace-nowrap sticky right-0 bg-white shadow-[-5px_0_10px_rgba(0,0,0,0.02)]">
                    <div class="flex items-center justify-center gap-1">
                        <button type="button" onclick="openReceiptDrawer('${r.bill_id}')" class="p-1.5 text-slate-600 hover:text-[#1f6b4a] hover:bg-[#e8f4ed] rounded-lg transition-all" title="View Receipt Drawer">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" onclick="printBill('${r.bill_id}')" class="p-1.5 text-slate-600 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all" title="Print Standard Receipt">
                            <i class="fas fa-print"></i>
                        </button>
                        ${isDue ? `
                            <button type="button" onclick="recordQuickPayment('${r.bill_id}', ${r.balance_due})" class="p-1.5 text-emerald-600 hover:text-emerald-800 hover:bg-emerald-100 rounded-lg transition-all" title="Collect Balance Due (₹${r.balance_due})">
                                <i class="fas fa-hand-holding-dollar"></i>
                            </button>
                        ` : ''}
                        <button type="button" onclick="openRecCancelModal('${r.bill_id}', '${r.amount_paid}')" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all" title="Cancel / Refund">
                            <i class="fas fa-ban"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

/**
 * Render Receipt Pagination Bar
 */
function renderReceiptPagination(p) {
    const infoEl = document.getElementById('rec-pagination-info');
    const btnsEl = document.getElementById('rec-pagination-btns');
    if (!infoEl || !btnsEl) return;

    const total = p.total_records || 0;
    const page = p.current_page || 1;
    const limit = p.limit || 25;
    const totalPages = p.total_pages || 1;

    const start = total === 0 ? 0 : (page - 1) * limit + 1;
    const end = Math.min(page * limit, total);

    infoEl.innerText = `Showing ${start} to ${end} of ${total.toLocaleString()} receipts`;

    let html = '';
    // Previous button
    html += `
        <button type="button" onclick="loadAdvancedReceipts(${page - 1})" ${page <= 1 ? 'disabled' : ''} 
            class="px-2.5 py-1 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed font-bold">
            <i class="fas fa-chevron-left text-[10px]"></i> Prev
        </button>
    `;

    // Page numbers
    const maxBtns = 5;
    let startPage = Math.max(1, page - 2);
    let endPage = Math.min(totalPages, startPage + maxBtns - 1);
    if (endPage - startPage < maxBtns - 1) {
        startPage = Math.max(1, endPage - maxBtns + 1);
    }

    for (let i = startPage; i <= endPage; i++) {
        const activeClass = (i === page) ? 'bg-[#1f6b4a] text-white border-[#1f6b4a]' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50';
        html += `
            <button type="button" onclick="loadAdvancedReceipts(${i})" class="px-2.5 py-1 rounded-lg border text-xs font-black ${activeClass}">
                ${i}
            </button>
        `;
    }

    // Next button
    html += `
        <button type="button" onclick="loadAdvancedReceipts(${page + 1})" ${page >= totalPages ? 'disabled' : ''} 
            class="px-2.5 py-1 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed font-bold">
            Next <i class="fas fa-chevron-right text-[10px]"></i>
        </button>
    `;

    btnsEl.innerHTML = html;
}

/**
 * Render Shift Handover Matrix
 */
function renderShiftHandoverTable(hourlyShift) {
    const tbody = document.getElementById('rec-shift-tbody');
    const tfoot = document.getElementById('rec-shift-tfoot');
    if (!tbody || !hourlyShift) return;

    const fmt = (v) => '₹' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    let totalBills = 0, totalCash = 0, totalUpi = 0, totalCard = 0, totalOther = 0, totalGrand = 0;

    tbody.innerHTML = hourlyShift.map(slot => {
        totalBills += slot.bills_count;
        totalCash += slot.cash_collected;
        totalUpi += slot.upi_collected;
        totalCard += slot.card_collected;
        totalOther += slot.other_collected;
        totalGrand += slot.total_collected;

        const isBusy = slot.bills_count > 0;
        return `
            <tr class="hover:bg-slate-50 text-xs border-b border-slate-100 ${isBusy ? 'font-medium' : 'text-slate-400'}">
                <td class="px-4 py-3 font-bold text-[#1f6b4a]">${slot.time_label}</td>
                <td class="px-4 py-3 text-center">${slot.bills_count}</td>
                <td class="px-4 py-3 text-right text-emerald-700 font-semibold">${fmt(slot.cash_collected)}</td>
                <td class="px-4 py-3 text-right text-blue-700 font-semibold">${fmt(slot.upi_collected)}</td>
                <td class="px-4 py-3 text-right text-purple-700 font-semibold">${fmt(slot.card_collected)}</td>
                <td class="px-4 py-3 text-right text-slate-600">${fmt(slot.other_collected)}</td>
                <td class="px-4 py-3 text-right font-black text-slate-900 ${isBusy ? 'bg-[#e8f4ed]/50' : ''}">${fmt(slot.total_collected)}</td>
            </tr>
        `;
    }).join('');

    if (tfoot) {
        tfoot.innerHTML = `
            <tr>
                <td class="px-4 py-3 font-black uppercase text-[#1f6b4a]">Shift Reconciled Total</td>
                <td class="px-4 py-3 text-center font-black">${totalBills}</td>
                <td class="px-4 py-3 text-right font-black text-emerald-700">${fmt(totalCash)}</td>
                <td class="px-4 py-3 text-right font-black text-blue-700">${fmt(totalUpi)}</td>
                <td class="px-4 py-3 text-right font-black text-purple-700">${fmt(totalCard)}</td>
                <td class="px-4 py-3 text-right font-black text-slate-700">${fmt(totalOther)}</td>
                <td class="px-4 py-3 text-right font-black text-slate-900 bg-[#e8f4ed] text-sm">${fmt(totalGrand)}</td>
            </tr>
        `;
    }
}

/**
 * Render Department Hierarchy Matrix with Individual Doctor Details & Date-wise Buttons
 */
let currentActiveStatementDoc = null;

function renderDepartmentHierarchyMatrix(deptHierarchy, summaryKPIs) {
    const container = document.getElementById('rec-dept-hierarchy-container');
    if (!container) return;

    if (!deptHierarchy || deptHierarchy.length === 0) {
        container.innerHTML = `
            <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-400">
                <i class="fas fa-hospital text-4xl text-slate-300 mb-3"></i>
                <h5 class="text-base font-bold text-slate-700">No Department Revenue Records Found</h5>
                <p class="text-xs text-slate-400 mt-1">Try adjusting your date range or filter presets.</p>
            </div>
        `;
        return;
    }

    const fmt = (v) => '₹' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const grandCollected = parseFloat(summaryKPIs.total_collection || 0);

    container.innerHTML = deptHierarchy.map(dept => {
        const depRevenue = parseFloat(dept.total_revenue || 0);
        const depBilled = parseFloat(dept.total_billed || 0);
        const depDue = parseFloat(dept.total_due || 0);
        const depBills = parseInt(dept.total_bills || 0);
        const doctors = dept.doctors || [];

        const doctorRows = doctors.map(doc => {
            const bills = parseInt(doc.bills_count || 0);
            const billed = parseFloat(doc.total_billed || 0);
            const collected = parseFloat(doc.collected_amount || 0);
            const due = parseFloat(doc.pending_amount || 0);
            const avg = parseFloat(doc.avg_bill_amount || 0);
            const hasDue = due > 0;
            const docId = doc.doctor_id || ('DOC_' + (doc.doctor_name || '').replace(/[^a-zA-Z0-9]/g, ''));

            return `
                <tr class="hover:bg-[#f3efe6]/40 transition-colors border-b border-slate-100 doc-hierarchy-row" data-doctor="${(doc.doctor_name || '').toLowerCase()}" data-dept="${(dept.department_name || '').toLowerCase()}">
                    <!-- Doctor Name & Credentials -->
                    <td class="py-3 px-5 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-[#1f6b4a] text-white flex items-center justify-center font-black text-xs shadow-xs">
                                ${(doc.doctor_name || 'D').replace('Dr ', '').trim().charAt(0)}
                            </div>
                            <div>
                                <div class="font-bold text-slate-800 text-xs">${doc.doctor_name}</div>
                                <div class="text-[10px] text-slate-400 font-medium">
                                    ${doc.qualification || 'Consultant Specialist'} ${doc.room_number && doc.room_number.trim() ? `• Room ${doc.room_number}` : ''}
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Patients / Bills -->
                    <td class="py-3 px-3 text-center whitespace-nowrap">
                        <span class="font-black text-slate-800 text-xs">${bills}</span>
                        <span class="text-[10px] text-slate-400 font-semibold block">patients</span>
                    </td>

                    <!-- Gross Billed -->
                    <td class="py-3 px-4 text-right font-bold text-slate-700 whitespace-nowrap">
                        ${fmt(billed)}
                    </td>

                    <!-- Total Collected Revenue -->
                    <td class="py-3 px-4 text-right whitespace-nowrap">
                        <span class="font-black text-emerald-700 text-xs">${fmt(collected)}</span>
                    </td>

                    <!-- Pending Due -->
                    <td class="py-3 px-4 text-right whitespace-nowrap">
                        ${hasDue ? `<span class="font-black text-rose-600">${fmt(due)}</span>` : `<span class="text-slate-400 font-medium">₹0.00</span>`}
                    </td>

                    <!-- Avg Spend / Patient -->
                    <td class="py-3 px-4 text-right font-bold text-slate-800 whitespace-nowrap">
                        ${fmt(avg)}
                    </td>

                    <!-- Action Button: 1-Click Date-wise Statement -->
                    <td class="py-3 px-4 text-center whitespace-nowrap">
                        <button type="button" onclick="openDoctorDatewiseModal('${docId}', '${doc.doctor_name.replace(/'/g, "\\'")}')" class="px-3.5 py-1.5 bg-[#1f6b4a] hover:bg-[#144d34] text-white rounded-xl text-xs font-bold transition-all shadow-xs inline-flex items-center gap-1.5 transform hover:scale-[1.02]">
                            <i class="fas fa-calendar-alt text-[11px]"></i>
                            <span>Date-wise Details</span>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        return `
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden dept-hierarchy-card" data-dept="${(dept.department_name || '').toLowerCase()}">
                <!-- Department Header Banner -->
                <div class="px-6 py-4 bg-gradient-to-r from-[#1f6b4a] via-[#1f6b4a] to-[#144d34] text-white flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-white/15 text-white flex items-center justify-center text-lg font-bold shadow-inner">
                            <i class="fas fa-stethoscope"></i>
                        </div>
                        <div>
                            <h4 class="font-black text-base text-white flex items-center gap-2">
                                Department: ${dept.department_name}
                                <span class="px-2.5 py-0.5 bg-emerald-400/20 text-emerald-200 text-[10.5px] font-black rounded-full border border-emerald-400/30 uppercase tracking-wider">
                                    ${dept.doctors_count} Doctor${dept.doctors_count > 1 ? 's' : ''}
                                </span>
                            </h4>
                            <p class="text-xs text-emerald-100/90 font-medium mt-0.5">
                                ${depBills} Patient Consultation${depBills > 1 ? 's' : ''} • Gross Billed: ${fmt(depBilled)}
                            </p>
                        </div>
                    </div>

                    <!-- Department Total Revenue & Print Button -->
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="text-left sm:text-right bg-white/10 px-4 py-2 rounded-xl border border-white/10 backdrop-blur-xs">
                            <div class="text-[10px] uppercase font-bold text-emerald-200 tracking-wider">Total Department Revenue</div>
                            <div class="text-xl font-black text-white">${fmt(depRevenue)}</div>
                        </div>
                        <button type="button" onclick="printDepartmentStatement('${dept.department_name.replace(/'/g, "\\'")}')" class="px-3.5 py-2 bg-white text-[#1f6b4a] hover:bg-emerald-50 rounded-xl text-xs font-black transition-all flex items-center gap-1.5 shadow-sm" title="Print full report for Department of ${dept.department_name}">
                            <i class="fas fa-print"></i> Print Department Report
                        </button>
                    </div>
                </div>

                <!-- Doctors Table within this Department -->
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50 border-b text-slate-500 font-bold uppercase text-[10.5px] tracking-wider">
                            <tr>
                                <th class="py-3 px-5 text-left">Doctor Name & Credentials</th>
                                <th class="py-3 px-3 text-center">Patients / Bills</th>
                                <th class="py-3 px-4 text-right">Gross Billed</th>
                                <th class="py-3 px-4 text-right">Total Revenue (Collected)</th>
                                <th class="py-3 px-4 text-right">Pending Due</th>
                                <th class="py-3 px-4 text-right">Avg / Patient</th>
                                <th class="py-3 px-4 text-center">Date-wise Breakdown</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            ${doctorRows}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }).join('');
}

/**
 * Filter Department Hierarchy Cards in Real-Time
 */
function filterDepartmentHierarchyCards(query) {
    const q = (query || '').toLowerCase().trim();
    document.querySelectorAll('.dept-hierarchy-card').forEach(card => {
        const deptName = card.getAttribute('data-dept') || '';
        let cardHasMatch = !q || deptName.includes(q);

        card.querySelectorAll('.doc-hierarchy-row').forEach(row => {
            const docName = row.getAttribute('data-doctor') || '';
            if (!q || deptName.includes(q) || docName.includes(q)) {
                row.style.display = '';
                cardHasMatch = true;
            } else {
                row.style.display = 'none';
            }
        });

        if (cardHasMatch) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

/**
 * Open Doctor Date-wise Full Statement Modal
 */
function openDoctorDatewiseModal(docId, docName) {
    const modal = document.getElementById('doctor-datewise-modal');
    if (!modal) return;

    // Find doctor in recState
    let targetDoc = null;
    if (recState.breakdowns && recState.breakdowns.doctor) {
        targetDoc = recState.breakdowns.doctor.find(d => 
            d.doctor_id === docId || 
            (docName && d.doctor_name && d.doctor_name.toLowerCase() === docName.toLowerCase())
        );
    }

    if (!targetDoc && recState.breakdowns && recState.breakdowns.department_hierarchy) {
        for (const dept of recState.breakdowns.department_hierarchy) {
            const found = dept.doctors.find(d => 
                d.doctor_id === docId || 
                (docName && d.doctor_name && d.doctor_name.toLowerCase() === docName.toLowerCase())
            );
            if (found) {
                targetDoc = found;
                break;
            }
        }
    }

    if (!targetDoc) {
        showError('Doctor details not found for statement');
        return;
    }

    currentActiveStatementDoc = targetDoc;
    const fmt = (v) => '₹' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // Populate Header
    const elName = document.getElementById('doc-modal-name');
    const elBadge = document.getElementById('doc-modal-dept-badge');
    const elSubtitle = document.getElementById('doc-modal-subtitle');

    if (elName) elName.innerText = targetDoc.doctor_name;
    if (elBadge) elBadge.innerText = targetDoc.department || targetDoc.specialization || 'General Medicine';
    if (elSubtitle) {
        elSubtitle.innerText = `${targetDoc.qualification || 'Consultant Specialist'} ${targetDoc.room_number ? `• Room ${targetDoc.room_number}` : ''} • Date-wise Patient & Financial Statement`;
    }

    // Populate KPIs
    const elBills = document.getElementById('doc-modal-kpi-bills');
    const elBilled = document.getElementById('doc-modal-kpi-billed');
    const elCollected = document.getElementById('doc-modal-kpi-collected');
    const elDue = document.getElementById('doc-modal-kpi-due');

    if (elBills) elBills.innerText = targetDoc.bills_count || 0;
    if (elBilled) elBilled.innerText = fmt(targetDoc.total_billed);
    if (elCollected) elCollected.innerText = fmt(targetDoc.collected_amount);
    if (elDue) elDue.innerText = fmt(targetDoc.pending_amount);

    const datesList = targetDoc.dates_list || (targetDoc.dates ? Object.values(targetDoc.dates) : []);

    // Populate Date Dropdown Selector
    const dateSelect = document.getElementById('doc-modal-date-select');
    if (dateSelect) {
        dateSelect.innerHTML = '<option value="all">📅 All Available Dates</option>' + datesList.map(dt => {
            const formattedDate = new Date(dt.date).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
            return `<option value="${dt.date}">📅 ${formattedDate} (${dt.bills_count} Patients • ${fmt(dt.total_collected)})</option>`;
        }).join('');
        dateSelect.value = 'all';
    }

    // Reset date picker inputs
    const fromInput = document.getElementById('doc-modal-from-date');
    const toInput = document.getElementById('doc-modal-to-date');
    if (fromInput) fromInput.value = '';
    if (toInput) toInput.value = '';

    // Populate Date-wise Patient Lists
    const body = document.getElementById('doc-modal-datewise-body');
    if (body) {
        if (datesList.length === 0) {
            body.innerHTML = `
                <div class="text-center py-12 text-slate-400">
                    <i class="fas fa-calendar-times text-4xl text-slate-300 mb-2"></i>
                    <p class="font-bold">No date-wise patient records available for this doctor</p>
                </div>
            `;
        } else {
            body.innerHTML = datesList.map(dateItem => {
                const formattedDate = new Date(dateItem.date).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
                const bills = dateItem.bills || [];

                const billRows = bills.map(bill => {
                    const mode = bill.payment_mode || 'Cash';
                    let modeBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    if (mode === 'UPI') modeBadge = 'bg-purple-50 text-purple-700 border-purple-200';
                    else if (mode === 'Card') modeBadge = 'bg-blue-50 text-blue-700 border-blue-200';

                    return `
                        <tr class="hover:bg-[#f3efe6]/40 transition-colors border-b border-slate-100">
                            <!-- Receipt ID -->
                            <td class="py-2.5 px-4 whitespace-nowrap">
                                <span class="font-black text-[#1f6b4a] text-xs">${bill.receipt_id || bill.bill_id}</span>
                                <span class="text-[10px] text-slate-400 block">${bill.bill_time ? bill.bill_time.substring(0, 5) : ''}</span>
                            </td>

                            <!-- Patient Info -->
                            <td class="py-2.5 px-4 whitespace-nowrap">
                                <div class="font-bold text-slate-800 text-xs">${bill.patient_name}</div>
                                <div class="text-[10px] text-slate-400 font-medium">${bill.patient_id} • ${bill.patient_phone || '-'}</div>
                            </td>

                            <!-- Purpose / Services -->
                            <td class="py-2.5 px-4">
                                <span class="text-xs text-slate-700 font-medium line-clamp-1">${bill.purpose || 'Clinical Consultation & Billing'}</span>
                            </td>

                            <!-- Amount Billed -->
                            <td class="py-2.5 px-3 text-right font-bold text-slate-700 whitespace-nowrap">
                                ${fmt(bill.grand_total)}
                            </td>

                            <!-- Amount Paid -->
                            <td class="py-2.5 px-3 text-right font-black text-emerald-700 whitespace-nowrap">
                                ${fmt(bill.amount_paid)}
                            </td>

                            <!-- Balance Due -->
                            <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                ${parseFloat(bill.balance_due) > 0 ? `<span class="font-black text-rose-600">${fmt(bill.balance_due)}</span>` : `<span class="text-slate-400">₹0.00</span>`}
                            </td>

                            <!-- Payment Mode -->
                            <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border ${modeBadge}">
                                    ${mode}
                                </span>
                            </td>

                            <!-- Action: Print / View Receipt -->
                            <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                <button type="button" onclick="printReceiptDirect('${bill.receipt_id || bill.bill_id}', '${bill.bill_id}')" class="p-1.5 text-slate-500 hover:text-[#1f6b4a] hover:bg-[#e8f4ed] rounded-lg transition-all" title="Print Payment Receipt">
                                    <i class="fas fa-print text-xs"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');

                return `
                    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden doc-date-block" data-date="${dateItem.date}" data-bills="${dateItem.bills_count}" data-billed="${dateItem.total_billed}" data-collected="${dateItem.total_collected}" data-due="${dateItem.total_due}">
                        <!-- Date Header Banner -->
                        <div class="px-5 py-3 bg-[#e8f4ed] border-b border-emerald-100 flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="w-7 h-7 rounded-lg bg-[#1f6b4a] text-white flex items-center justify-center text-xs font-bold">
                                    <i class="fas fa-calendar-day"></i>
                                </span>
                                <span class="font-black text-slate-800 text-xs">${formattedDate}</span>
                                <span class="text-slate-400 text-xs">•</span>
                                <span class="text-xs font-bold text-[#1f6b4a]">${dateItem.bills_count} Patient Bill${dateItem.bills_count > 1 ? 's' : ''}</span>
                            </div>
                            <div class="flex items-center gap-3 text-xs">
                                <div>
                                    <span class="text-slate-500 font-medium">Billed:</span>
                                    <span class="font-bold text-slate-800 ml-1">${fmt(dateItem.total_billed)}</span>
                                </div>
                                <div>
                                    <span class="text-emerald-800 font-bold">Collected:</span>
                                    <span class="font-black text-emerald-700 ml-1">${fmt(dateItem.total_collected)}</span>
                                </div>
                                <button type="button" onclick="printSingleDoctorDate('${dateItem.date}')" class="px-2.5 py-1 bg-white hover:bg-emerald-100 text-[#1f6b4a] rounded-lg border border-emerald-300 text-[11px] font-black transition-all flex items-center gap-1 shadow-xs" title="Print statement for ${formattedDate} only">
                                    <i class="fas fa-print"></i> Print This Date
                                </button>
                            </div>
                        </div>

                        <!-- Date's Patients Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead class="bg-slate-50/70 border-b text-slate-400 font-bold uppercase text-[10px]">
                                    <tr>
                                        <th class="py-2.5 px-4 text-left">Receipt #</th>
                                        <th class="py-2.5 px-4 text-left">Patient Details</th>
                                        <th class="py-2.5 px-4 text-left">Purpose / Service</th>
                                        <th class="py-2.5 px-3 text-right">Billed</th>
                                        <th class="py-2.5 px-3 text-right">Paid</th>
                                        <th class="py-2.5 px-3 text-right">Due</th>
                                        <th class="py-2.5 px-3 text-center">Mode</th>
                                        <th class="py-2.5 px-3 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    ${billRows}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
            }).join('');
        }
    }

    modal.classList.remove('hidden');
}

/**
 * Filter Doctor Statement Modal by Dropdown Date
 */
function filterDoctorStatementByDate(selectedDate) {
    const fmt = (v) => '₹' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    let totBills = 0, totBilled = 0, totCollected = 0, totDue = 0;

    document.querySelectorAll('.doc-date-block').forEach(block => {
        const bDate = block.getAttribute('data-date');
        if (selectedDate === 'all' || bDate === selectedDate) {
            block.style.display = '';
            totBills += parseInt(block.getAttribute('data-bills') || 0);
            totBilled += parseFloat(block.getAttribute('data-billed') || 0);
            totCollected += parseFloat(block.getAttribute('data-collected') || 0);
            totDue += parseFloat(block.getAttribute('data-due') || 0);
        } else {
            block.style.display = 'none';
        }
    });

    const elBills = document.getElementById('doc-modal-kpi-bills');
    const elBilled = document.getElementById('doc-modal-kpi-billed');
    const elCollected = document.getElementById('doc-modal-kpi-collected');
    const elDue = document.getElementById('doc-modal-kpi-due');

    if (elBills) elBills.innerText = totBills;
    if (elBilled) elBilled.innerText = fmt(totBilled);
    if (elCollected) elCollected.innerText = fmt(totCollected);
    if (elDue) elDue.innerText = fmt(totDue);
}

/**
 * Apply Custom Date Range Filter in Doctor Statement Modal
 */
function applyDoctorModalCustomDateRange() {
    const fromVal = document.getElementById('doc-modal-from-date')?.value;
    const toVal = document.getElementById('doc-modal-to-date')?.value;
    const fmt = (v) => '₹' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    let totBills = 0, totBilled = 0, totCollected = 0, totDue = 0;

    document.querySelectorAll('.doc-date-block').forEach(block => {
        const bDate = block.getAttribute('data-date');
        let match = true;
        if (fromVal && bDate < fromVal) match = false;
        if (toVal && bDate > toVal) match = false;

        if (match) {
            block.style.display = '';
            totBills += parseInt(block.getAttribute('data-bills') || 0);
            totBilled += parseFloat(block.getAttribute('data-billed') || 0);
            totCollected += parseFloat(block.getAttribute('data-collected') || 0);
            totDue += parseFloat(block.getAttribute('data-due') || 0);
        } else {
            block.style.display = 'none';
        }
    });

    const elBills = document.getElementById('doc-modal-kpi-bills');
    const elBilled = document.getElementById('doc-modal-kpi-billed');
    const elCollected = document.getElementById('doc-modal-kpi-collected');
    const elDue = document.getElementById('doc-modal-kpi-due');

    if (elBills) elBills.innerText = totBills;
    if (elBilled) elBilled.innerText = fmt(totBilled);
    if (elCollected) elCollected.innerText = fmt(totCollected);
    if (elDue) elDue.innerText = fmt(totDue);
}

/**
 * Print Single Selected Date for a Doctor
 */
function printSingleDoctorDate(dateStr) {
    if (!currentActiveStatementDoc) {
        showError('No active doctor statement selected');
        return;
    }
    printDoctorDatewiseStatement([dateStr]);
}

/**
 * Close Doctor Date-wise Full Statement Modal
 */
function closeDoctorDatewiseModal() {
    const modal = document.getElementById('doctor-datewise-modal');
    if (modal) modal.classList.add('hidden');
}

/**
 * Print Doctor Date-wise Statement Sheet (Supports Selected/Filtered Dates)
 */
function printDoctorDatewiseStatement(explicitDatesArray = null) {
    if (!currentActiveStatementDoc) {
        showError('No active doctor statement selected to print');
        return;
    }

    const doc = currentActiveStatementDoc;
    const fmt = (v) => '₹' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    let datesList = doc.dates_list || (doc.dates ? Object.values(doc.dates) : []);

    // Filter by explicit dates or active dropdown/range filter in modal
    if (explicitDatesArray && Array.isArray(explicitDatesArray)) {
        datesList = datesList.filter(d => explicitDatesArray.includes(d.date));
    } else {
        const dateSelect = document.getElementById('doc-modal-date-select');
        const fromVal = document.getElementById('doc-modal-from-date')?.value;
        const toVal = document.getElementById('doc-modal-to-date')?.value;

        if (dateSelect && dateSelect.value !== 'all') {
            datesList = datesList.filter(d => d.date === dateSelect.value);
        } else if (fromVal || toVal) {
            datesList = datesList.filter(d => {
                if (fromVal && d.date < fromVal) return false;
                if (toVal && d.date > toVal) return false;
                return true;
            });
        }
    }

    if (datesList.length === 0) {
        showError('No records match the selected date filter to print');
        return;
    }

    let datesHtml = '';
    datesList.forEach(d => {
        const formattedDate = new Date(d.date).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
        const bills = d.bills || [];

        let rowsHtml = '';
        bills.forEach((b, idx) => {
            rowsHtml += `
                <tr>
                    <td style="padding: 6px; border-bottom: 1px solid #e2e8f0; font-size: 11px;">${idx + 1}</td>
                    <td style="padding: 6px; border-bottom: 1px solid #e2e8f0; font-size: 11px; font-weight: bold;">${b.receipt_id || b.bill_id}</td>
                    <td style="padding: 6px; border-bottom: 1px solid #e2e8f0; font-size: 11px;"><strong>${b.patient_name}</strong> (${b.patient_id})</td>
                    <td style="padding: 6px; border-bottom: 1px solid #e2e8f0; font-size: 11px;">${b.purpose || 'Consultation'}</td>
                    <td style="padding: 6px; border-bottom: 1px solid #e2e8f0; font-size: 11px; text-align: right;">${fmt(b.grand_total)}</td>
                    <td style="padding: 6px; border-bottom: 1px solid #e2e8f0; font-size: 11px; text-align: right; font-weight: bold; color: #1f6b4a;">${fmt(b.amount_paid)}</td>
                    <td style="padding: 6px; border-bottom: 1px solid #e2e8f0; font-size: 11px; text-align: right;">${fmt(b.balance_due)}</td>
                    <td style="padding: 6px; border-bottom: 1px solid #e2e8f0; font-size: 11px; text-align: center;">${b.payment_mode}</td>
                </tr>
            `;
        });

        datesHtml += `
            <div style="margin-top: 15px; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden;">
                <div style="background-color: #f1f5f9; padding: 8px 12px; font-weight: bold; font-size: 12px; display: flex; justify-content: space-between; border-bottom: 1px solid #cbd5e1;">
                    <span>📅 Date: ${formattedDate} (${d.bills_count} Patients)</span>
                    <span>Total Date Collection: ${fmt(d.total_collected)}</span>
                </div>
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="background-color: #f8fafc; font-size: 10px; text-transform: uppercase; color: #64748b;">
                            <th style="padding: 6px; border-bottom: 1px solid #cbd5e1;">#</th>
                            <th style="padding: 6px; border-bottom: 1px solid #cbd5e1;">Receipt #</th>
                            <th style="padding: 6px; border-bottom: 1px solid #cbd5e1;">Patient Name</th>
                            <th style="padding: 6px; border-bottom: 1px solid #cbd5e1;">Purpose</th>
                            <th style="padding: 6px; border-bottom: 1px solid #cbd5e1; text-align: right;">Billed</th>
                            <th style="padding: 6px; border-bottom: 1px solid #cbd5e1; text-align: right;">Collected</th>
                            <th style="padding: 6px; border-bottom: 1px solid #cbd5e1; text-align: right;">Due</th>
                            <th style="padding: 6px; border-bottom: 1px solid #cbd5e1; text-align: center;">Mode</th>
                        </tr>
                    </thead>
                    <tbody>${rowsHtml}</tbody>
                </table>
            </div>
        `;
    });

    const printWin = window.open('', '_blank', 'width=950,height=750');
    if (!printWin) {
        showError('Please allow popups to print doctor statement');
        return;
    }

    printWin.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Doctor Statement - ${doc.doctor_name}</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #1e293b; margin: 25px; }
                .header { border-bottom: 2px solid #1f6b4a; padding-bottom: 12px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: flex-start; }
                .h-title { font-size: 20px; font-weight: bold; color: #1f6b4a; }
                @media print { body { margin: 0; } }
            </style>
        </head>
        <body>
            <div class="header">
                <div>
                    <div class="h-title">GM HOSPITAL</div>
                    <div style="font-size: 11px; color: #64748b;">Clinical Revenue & Doctor Date-wise Performance Statement</div>
                    <div style="margin-top: 6px; font-size: 14px; font-weight: bold; color: #0f172a;">${doc.doctor_name} (${doc.qualification || 'Specialist'})</div>
                    <div style="font-size: 12px; color: #1f6b4a; font-weight: bold;">Department: ${doc.department || doc.specialization}</div>
                </div>
                <div style="text-align: right; font-size: 11px; color: #64748b;">
                    <div>Generated on: ${new Date().toLocaleString()}</div>
                    <div style="font-weight: bold; color: #0f172a; margin-top: 4px;">Status: Reconciled</div>
                </div>
            </div>

            ${datesHtml}

            <div style="margin-top: 30px; display: flex; justify-content: space-between; font-size: 11px; color: #64748b; border-top: 1px solid #cbd5e1; padding-top: 15px;">
                <div>Hospital Billing Management System • GM HMS</div>
                <div>Authorized Signatory: _________________________</div>
            </div>

            <script>
                window.onload = function() {
                    window.print();
                };
            </script>
        </body>
        </html>
    `);
    printWin.document.close();
}

/**
 * Print Department-wise Revenue & Clinical Report
 */
function printDepartmentStatement(deptName) {
    if (!recState.breakdowns || !recState.breakdowns.department_hierarchy) {
        showError('Department hierarchy data not available to print');
        return;
    }

    const dept = recState.breakdowns.department_hierarchy.find(d => 
        d.department_name.toLowerCase() === (deptName || '').toLowerCase()
    );

    if (!dept) {
        showError(`Department records for "${deptName}" not found`);
        return;
    }

    const fmt = (v) => '₹' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const doctors = dept.doctors || [];

    // 1. Doctors Overview Table
    let docOverviewRows = '';
    doctors.forEach((d, idx) => {
        docOverviewRows += `
            <tr>
                <td style="padding: 6px; border-bottom: 1px solid #cbd5e1; font-size: 11px;">${idx + 1}</td>
                <td style="padding: 6px; border-bottom: 1px solid #cbd5e1; font-size: 11px; font-weight: bold;">${d.doctor_name}</td>
                <td style="padding: 6px; border-bottom: 1px solid #cbd5e1; font-size: 11px;">${d.qualification || 'Consultant'}</td>
                <td style="padding: 6px; border-bottom: 1px solid #cbd5e1; font-size: 11px; text-align: center;">${d.bills_count}</td>
                <td style="padding: 6px; border-bottom: 1px solid #cbd5e1; font-size: 11px; text-align: right;">${fmt(d.total_billed)}</td>
                <td style="padding: 6px; border-bottom: 1px solid #cbd5e1; font-size: 11px; text-align: right; font-weight: bold; color: #1f6b4a;">${fmt(d.collected_amount)}</td>
                <td style="padding: 6px; border-bottom: 1px solid #cbd5e1; font-size: 11px; text-align: right; color: ${d.pending_amount > 0 ? '#dc2626' : '#64748b'};">${fmt(d.pending_amount)}</td>
            </tr>
        `;
    });

    // 2. Detailed Patient Transactions per Doctor
    let doctorDetailsHtml = '';
    doctors.forEach(doc => {
        const datesList = doc.dates_list || (doc.dates ? Object.values(doc.dates) : []);
        let docDatesHtml = '';

        datesList.forEach(dt => {
            const formattedDate = new Date(dt.date).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
            const bills = dt.bills || [];

            let billRows = '';
            bills.forEach((b, bIdx) => {
                billRows += `
                    <tr>
                        <td style="padding: 5px; border-bottom: 1px solid #e2e8f0; font-size: 10.5px;">${bIdx + 1}</td>
                        <td style="padding: 5px; border-bottom: 1px solid #e2e8f0; font-size: 10.5px; font-weight: bold;">${b.receipt_id || b.bill_id}</td>
                        <td style="padding: 5px; border-bottom: 1px solid #e2e8f0; font-size: 10.5px;"><strong>${b.patient_name}</strong> (${b.patient_id})</td>
                        <td style="padding: 5px; border-bottom: 1px solid #e2e8f0; font-size: 10.5px;">${b.purpose || 'Consultation'}</td>
                        <td style="padding: 5px; border-bottom: 1px solid #e2e8f0; font-size: 10.5px; text-align: right;">${fmt(b.grand_total)}</td>
                        <td style="padding: 5px; border-bottom: 1px solid #e2e8f0; font-size: 10.5px; text-align: right; font-weight: bold; color: #1f6b4a;">${fmt(b.amount_paid)}</td>
                        <td style="padding: 5px; border-bottom: 1px solid #e2e8f0; font-size: 10.5px; text-align: right;">${fmt(b.balance_due)}</td>
                        <td style="padding: 5px; border-bottom: 1px solid #e2e8f0; font-size: 10.5px; text-align: center;">${b.payment_mode}</td>
                    </tr>
                `;
            });

            docDatesHtml += `
                <div style="margin-top: 10px; border: 1px solid #e2e8f0; border-radius: 4px; overflow: hidden;">
                    <div style="background-color: #f8fafc; padding: 6px 10px; font-size: 11px; font-weight: bold; display: flex; justify-content: space-between; border-bottom: 1px solid #e2e8f0;">
                        <span>📅 Date: ${formattedDate} (${dt.bills_count} Patients)</span>
                        <span>Date Collection: ${fmt(dt.total_collected)}</span>
                    </div>
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="background-color: #f1f5f9; font-size: 9.5px; text-transform: uppercase; color: #64748b;">
                                <th style="padding: 5px; border-bottom: 1px solid #e2e8f0;">#</th>
                                <th style="padding: 5px; border-bottom: 1px solid #e2e8f0;">Receipt #</th>
                                <th style="padding: 5px; border-bottom: 1px solid #e2e8f0;">Patient Name</th>
                                <th style="padding: 5px; border-bottom: 1px solid #e2e8f0;">Service</th>
                                <th style="padding: 5px; border-bottom: 1px solid #e2e8f0; text-align: right;">Billed</th>
                                <th style="padding: 5px; border-bottom: 1px solid #e2e8f0; text-align: right;">Collected</th>
                                <th style="padding: 5px; border-bottom: 1px solid #e2e8f0; text-align: right;">Due</th>
                                <th style="padding: 5px; border-bottom: 1px solid #e2e8f0; text-align: center;">Mode</th>
                            </tr>
                        </thead>
                        <tbody>${billRows}</tbody>
                    </table>
                </div>
            `;
        });

        doctorDetailsHtml += `
            <div style="margin-top: 20px; page-break-inside: avoid;">
                <div style="background-color: #1f6b4a; color: white; padding: 8px 12px; font-weight: bold; font-size: 12px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                    <span>👨‍⚕️ ${doc.doctor_name} (${doc.qualification || 'Consultant Specialist'})</span>
                    <span>Total: ${fmt(doc.collected_amount)}</span>
                </div>
                ${docDatesHtml}
            </div>
        `;
    });

    const printWin = window.open('', '_blank', 'width=950,height=800');
    if (!printWin) {
        showError('Please allow popups to print department report');
        return;
    }

    printWin.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Department Report - ${dept.department_name}</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #1e293b; margin: 25px; }
                .header { border-bottom: 2px solid #1f6b4a; padding-bottom: 12px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: flex-start; }
                .h-title { font-size: 20px; font-weight: bold; color: #1f6b4a; }
                .sec-title { font-size: 13px; font-weight: bold; color: #0f172a; text-transform: uppercase; margin: 15px 0 6px 0; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; }
                @media print { body { margin: 0; } }
            </style>
        </head>
        <body>
            <div class="header">
                <div>
                    <div class="h-title">GM HOSPITAL</div>
                    <div style="font-size: 11px; color: #64748b;">Clinical Department Revenue & Performance Statement</div>
                    <div style="margin-top: 6px; font-size: 15px; font-weight: bold; color: #0f172a;">Department: ${dept.department_name}</div>
                </div>
                <div style="text-align: right; font-size: 11px; color: #64748b;">
                    <div>Generated on: ${new Date().toLocaleString()}</div>
                    <div style="font-weight: bold; color: #1f6b4a; margin-top: 4px; font-size: 13px;">Total Revenue: ${fmt(dept.total_revenue)}</div>
                    <div style="font-size: 11px; color: #0f172a;">${dept.doctors_count} Active Doctors • ${dept.total_bills} Patient Consultations</div>
                </div>
            </div>

            <div class="sec-title">Department Physicians Performance Summary</div>
            <table style="width: 100%; border-collapse: collapse; text-align: left; margin-bottom: 15px;">
                <thead>
                    <tr style="background-color: #f1f5f9; font-size: 10.5px; text-transform: uppercase; color: #64748b;">
                        <th style="padding: 6px; border-bottom: 1px solid #cbd5e1;">#</th>
                        <th style="padding: 6px; border-bottom: 1px solid #cbd5e1;">Doctor Name</th>
                        <th style="padding: 6px; border-bottom: 1px solid #cbd5e1;">Qualification</th>
                        <th style="padding: 6px; border-bottom: 1px solid #cbd5e1; text-align: center;">Consults</th>
                        <th style="padding: 6px; border-bottom: 1px solid #cbd5e1; text-align: right;">Gross Billed</th>
                        <th style="padding: 6px; border-bottom: 1px solid #cbd5e1; text-align: right;">Collected</th>
                        <th style="padding: 6px; border-bottom: 1px solid #cbd5e1; text-align: right;">Balance Due</th>
                    </tr>
                </thead>
                <tbody>${docOverviewRows}</tbody>
                <tfoot>
                    <tr style="background-color: #f8fafc; font-weight: bold; font-size: 11px; border-top: 2px solid #cbd5e1;">
                        <td colspan="3" style="padding: 8px; text-transform: uppercase; color: #1f6b4a;">Department Total</td>
                        <td style="padding: 8px; text-align: center;">${dept.total_bills}</td>
                        <td style="padding: 8px; text-align: right;">${fmt(dept.total_billed)}</td>
                        <td style="padding: 8px; text-align: right; color: #1f6b4a;">${fmt(dept.total_revenue)}</td>
                        <td style="padding: 8px; text-align: right; color: ${dept.total_due > 0 ? '#dc2626' : '#64748b'};">${fmt(dept.total_due)}</td>
                    </tr>
                </tfoot>
            </table>

            <div class="sec-title" style="margin-top: 20px;">Doctor-wise Patient Consultation Statements</div>
            ${doctorDetailsHtml}

            <div style="margin-top: 30px; display: flex; justify-content: space-between; font-size: 11px; color: #64748b; border-top: 1px solid #cbd5e1; padding-top: 15px;">
                <div>Hospital Billing Management System • GM HMS</div>
                <div>Department In-Charge / Authorized Signatory: _________________________</div>
            </div>

            <script>
                window.onload = function() {
                    window.print();
                };
            </script>
        </body>
        </html>
    `);
    printWin.document.close();
}

/**
 * Render Complete Cashier Shift Handover & Reconciliation Matrix
 */
function renderCashierShiftHandoverMatrix(staffList, hourlyShift, summary) {
    const fmt = (v) => '₹' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // 1. Calculate & Render Shift Summary KPIs
    let totalCash = 0, totalDigital = 0, totalShiftRev = 0;
    staffList.forEach(s => {
        totalCash += parseFloat(s.cash_collected || 0);
        totalDigital += (parseFloat(s.upi_collected || 0) + parseFloat(s.card_collected || 0));
        totalShiftRev += parseFloat(s.collected_amount || 0);
    });

    const elCashiers = document.getElementById('shift-kpi-cashiers');
    const elCash = document.getElementById('shift-kpi-cash');
    const elDigital = document.getElementById('shift-kpi-digital');
    const elTotal = document.getElementById('shift-kpi-total');

    if (elCashiers) elCashiers.innerText = staffList.length;
    if (elCash) elCash.innerText = fmt(totalCash);
    if (elDigital) elDigital.innerText = fmt(totalDigital);
    if (elTotal) elTotal.innerText = fmt(totalShiftRev);

    // 2. Render Cashier / Staff User Login Cards
    const container = document.getElementById('rec-cashier-cards-container');
    if (container) {
        if (!staffList || staffList.length === 0) {
            container.innerHTML = `
                <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-slate-400">
                    <i class="fas fa-users-slash text-3xl mb-2 text-slate-300"></i>
                    <p class="font-bold text-xs">No cashier shift records found for this date range</p>
                </div>
            `;
        } else {
            container.innerHTML = staffList.map(st => {
                const safeUser = (st.staff_username || 'user').replace(/[^a-zA-Z0-9]/g, '_');
                const bills = st.bills_list || [];
                const roleBadge = st.role === 'Admin' ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-emerald-100 text-emerald-800 border-emerald-300';
                
                const timeSpanText = (st.shift_start_time && st.shift_end_time)
                    ? `${st.shift_start_time.substring(0, 5)} - ${st.shift_end_time.substring(0, 5)}`
                    : 'Active Shift';

                // Itemized patient bills table rows
                const itemRows = bills.map((b, idx) => {
                    const mode = b.payment_mode || 'Cash';
                    let modeBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    if (mode === 'UPI') modeBadge = 'bg-purple-50 text-purple-700 border-purple-200';
                    else if (mode === 'Card') modeBadge = 'bg-blue-50 text-blue-700 border-blue-200';

                    return `
                        <tr class="hover:bg-[#f3efe6]/40 transition-colors border-b border-slate-100">
                            <td class="py-2.5 px-4 font-bold text-slate-500">${idx + 1}</td>
                            <td class="py-2.5 px-4 whitespace-nowrap">
                                <span class="font-black text-[#1f6b4a]">${b.receipt_id || b.bill_id}</span>
                                <span class="text-[10px] text-slate-400 block">${b.bill_time ? b.bill_time.substring(0, 5) : ''}</span>
                            </td>
                            <td class="py-2.5 px-4 whitespace-nowrap">
                                <div class="font-bold text-slate-800">${b.patient_name}</div>
                                <div class="text-[10px] text-slate-400 font-medium">${b.patient_id} • ${b.patient_phone || '-'}</div>
                            </td>
                            <td class="py-2.5 px-4">
                                <span class="font-medium text-slate-700 line-clamp-1">${b.doctor_name || b.purpose || 'OPD Service'}</span>
                            </td>
                            <td class="py-2.5 px-3 text-right font-bold text-slate-700 whitespace-nowrap">
                                ${fmt(b.grand_total)}
                            </td>
                            <td class="py-2.5 px-3 text-right font-black text-emerald-700 whitespace-nowrap">
                                ${fmt(b.amount_paid)}
                            </td>
                            <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                ${parseFloat(b.balance_due) > 0 ? `<span class="font-black text-rose-600">${fmt(b.balance_due)}</span>` : `<span class="text-slate-400">₹0.00</span>`}
                            </td>
                            <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border ${modeBadge}">
                                    ${mode}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                <button type="button" onclick="printReceiptDirect('${b.receipt_id || b.bill_id}', '${b.bill_id}')" class="p-1.5 text-slate-500 hover:text-[#1f6b4a] hover:bg-[#e8f4ed] rounded-lg transition-all" title="Print Receipt">
                                    <i class="fas fa-print text-xs"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');

                return `
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden cashier-card" id="cashier-card-${safeUser}">
                        <!-- Cashier Main Header Strip -->
                        <div class="p-5 bg-gradient-to-r from-slate-50 via-white to-[#fdfbf7] flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200">
                            <div class="flex items-center gap-3.5">
                                <div class="h-11 w-11 rounded-2xl bg-gradient-to-br from-[#1f6b4a] to-[#144d34] text-white flex items-center justify-center text-lg font-black shadow-sm">
                                    ${(st.staff_username || 'U').substring(0, 1).toUpperCase()}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-black text-slate-800 text-sm md:text-base">${st.full_name || st.staff_username}</h4>
                                        <span class="px-2 py-0.5 text-[10px] font-black rounded-md border ${roleBadge} uppercase">
                                            ${st.role || 'Staff'}
                                        </span>
                                    </div>
                                    <div class="text-xs text-slate-500 font-medium mt-0.5 flex flex-wrap items-center gap-2">
                                        <span><i class="fas fa-user-circle text-slate-400 mr-1"></i> Login: <strong>${st.staff_username}</strong></span>
                                        <span>•</span>
                                        <span><i class="fas fa-clock text-slate-400 mr-1"></i> Activity: <strong>${timeSpanText}</strong></span>
                                        <span>•</span>
                                        <span><i class="fas fa-receipt text-slate-400 mr-1"></i> Receipts: <strong>${st.bills_count}</strong></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Cashier Collected Total & Action Buttons -->
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="bg-emerald-50 px-4 py-2 rounded-xl border border-emerald-200 text-left sm:text-right">
                                    <div class="text-[10px] uppercase font-bold text-emerald-800">Total Collected</div>
                                    <div class="text-lg font-black text-emerald-700">${fmt(st.collected_amount)}</div>
                                </div>
                                <button type="button" onclick="toggleCashierBillsDrawer('${safeUser}')" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-xs" id="btn-toggle-${safeUser}">
                                    <i class="fas fa-list-ul"></i> Detailed Transactions (${bills.length})
                                </button>
                                <button type="button" onclick="printSingleCashierShiftVoucher('${st.staff_username.replace(/'/g, "\\'")}')" class="px-3.5 py-2 bg-[#1f6b4a] hover:bg-[#144d34] text-white rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-xs" title="Print Shift Handover Voucher">
                                    <i class="fas fa-print"></i> Shift Voucher
                                </button>
                            </div>
                        </div>

                        <!-- Individual Payment Mode Collections Strip -->
                        <div class="p-4 bg-white grid grid-cols-2 sm:grid-cols-4 gap-3 border-b border-slate-100 text-xs">
                            <div class="bg-emerald-50/50 p-3 rounded-xl border border-emerald-100">
                                <div class="flex items-center justify-between text-emerald-800 font-bold text-[10.5px] uppercase">
                                    <span>💵 Cash Drawer</span>
                                    <i class="fas fa-money-bill-wave text-emerald-600"></i>
                                </div>
                                <div class="text-base font-black text-emerald-700 mt-1">${fmt(st.cash_collected)}</div>
                            </div>
                            <div class="bg-purple-50/50 p-3 rounded-xl border border-purple-100">
                                <div class="flex items-center justify-between text-purple-800 font-bold text-[10.5px] uppercase">
                                    <span>📱 UPI / QR</span>
                                    <i class="fas fa-qrcode text-purple-600"></i>
                                </div>
                                <div class="text-base font-black text-purple-700 mt-1">${fmt(st.upi_collected)}</div>
                            </div>
                            <div class="bg-blue-50/50 p-3 rounded-xl border border-blue-100">
                                <div class="flex items-center justify-between text-blue-800 font-bold text-[10.5px] uppercase">
                                    <span>💳 Card / POS</span>
                                    <i class="fas fa-credit-card text-blue-600"></i>
                                </div>
                                <div class="text-base font-black text-blue-700 mt-1">${fmt(st.card_collected)}</div>
                            </div>
                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                                <div class="flex items-center justify-between text-slate-600 font-bold text-[10.5px] uppercase">
                                    <span>🏦 Other Modes</span>
                                    <i class="fas fa-university text-slate-400"></i>
                                </div>
                                <div class="text-base font-black text-slate-700 mt-1">${fmt(st.other_collected)}</div>
                            </div>
                        </div>

                        <!-- Collapsible Itemized Transactions Table -->
                        <div id="cashier-drawer-${safeUser}" class="hidden bg-slate-50/50 border-t border-slate-200">
                            <div class="p-4 bg-slate-100/70 border-b border-slate-200 flex justify-between items-center text-xs">
                                <span class="font-bold text-slate-700 flex items-center gap-2">
                                    <i class="fas fa-file-invoice text-[#1f6b4a]"></i> Itemized Receipts Generated by <strong>${st.full_name || st.staff_username}</strong>
                                </span>
                                <span class="text-slate-500 font-semibold">${bills.length} Receipts • Total Billed: ${fmt(st.total_billed)}</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs">
                                    <thead class="bg-white border-b text-slate-400 font-bold uppercase text-[10px]">
                                        <tr>
                                            <th class="py-2.5 px-4 text-left">#</th>
                                            <th class="py-2.5 px-4 text-left">Receipt #</th>
                                            <th class="py-2.5 px-4 text-left">Patient Details</th>
                                            <th class="py-2.5 px-4 text-left">Service / Doctor</th>
                                            <th class="py-2.5 px-3 text-right">Billed</th>
                                            <th class="py-2.5 px-3 text-right">Paid</th>
                                            <th class="py-2.5 px-3 text-right">Due</th>
                                            <th class="py-2.5 px-3 text-center">Mode</th>
                                            <th class="py-2.5 px-3 text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        ${itemRows}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }
    }

    // 3. Populate All Shift Bills & Generating Staff Register
    currentShiftBillsList = [];
    staffList.forEach(st => {
        (st.bills_list || []).forEach(b => {
            currentShiftBillsList.push({
                ...b,
                staff_username: st.staff_username,
                staff_fullname: st.full_name || st.staff_username,
                staff_role: st.role || 'Staff'
            });
        });
    });

    // Populate Cashier Filter Dropdown
    const staffFilter = document.getElementById('rec-shift-staff-filter');
    if (staffFilter) {
        staffFilter.innerHTML = '<option value="all">👤 All Staff / Cashiers</option>' + staffList.map(st => {
            return `<option value="${st.staff_username}">👤 ${st.full_name || st.staff_username} (${st.role || 'Staff'})</option>`;
        }).join('');
    }

    // Render Filtered Bills Table
    filterShiftBills();
}

/**
 * Global Shift Bills Cache
 */
let currentShiftBillsList = [];

/**
 * Filter Shift Bills by Cashier, Mode, and Search Query
 */
function filterShiftBills() {
    const staffVal = document.getElementById('rec-shift-staff-filter')?.value || 'all';
    const modeVal = document.getElementById('rec-shift-mode-filter')?.value || 'all';
    const searchQuery = (document.getElementById('rec-shift-search-input')?.value || '').toLowerCase().trim();

    const tbody = document.getElementById('rec-shift-all-bills-tbody');
    const tfoot = document.getElementById('rec-shift-all-bills-tfoot');
    if (!tbody) return;

    const fmt = (v) => '₹' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const filtered = currentShiftBillsList.filter(b => {
        if (staffVal !== 'all' && b.staff_username !== staffVal) return false;
        if (modeVal !== 'all') {
            if (modeVal === 'Other' && ['Cash', 'UPI', 'Card'].includes(b.payment_mode)) return false;
            if (modeVal !== 'Other' && b.payment_mode !== modeVal) return false;
        }
        if (searchQuery) {
            const matchesText = (
                (b.receipt_id || '').toLowerCase().includes(searchQuery) ||
                (b.bill_id || '').toLowerCase().includes(searchQuery) ||
                (b.patient_name || '').toLowerCase().includes(searchQuery) ||
                (b.patient_id || '').toLowerCase().includes(searchQuery) ||
                (b.patient_phone || '').toLowerCase().includes(searchQuery) ||
                (b.doctor_name || '').toLowerCase().includes(searchQuery) ||
                (b.purpose || '').toLowerCase().includes(searchQuery) ||
                (b.staff_username || '').toLowerCase().includes(searchQuery) ||
                (b.staff_fullname || '').toLowerCase().includes(searchQuery)
            );
            if (!matchesText) return false;
        }
        return true;
    });

    if (filtered.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="11" class="text-center py-8 text-slate-400">
                    <i class="fas fa-search text-2xl text-slate-300 mb-2"></i>
                    <p class="font-bold">No receipts match the selected filters</p>
                </td>
            </tr>
        `;
        if (tfoot) tfoot.innerHTML = '';
        return;
    }

    let totBilled = 0, totPaid = 0, totDue = 0;

    tbody.innerHTML = filtered.map((b, idx) => {
        totBilled += parseFloat(b.grand_total || 0);
        totPaid += parseFloat(b.amount_paid || 0);
        totDue += parseFloat(b.balance_due || 0);

        const mode = b.payment_mode || 'Cash';
        let modeBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
        if (mode === 'UPI') modeBadge = 'bg-purple-50 text-purple-700 border-purple-200';
        else if (mode === 'Card') modeBadge = 'bg-blue-50 text-blue-700 border-blue-200';

        const roleBadge = b.staff_role === 'Admin' ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200';
        const formattedDate = b.bill_date ? new Date(b.bill_date).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '-';

        return `
            <tr class="hover:bg-[#f3efe6]/40 transition-colors border-b border-slate-100">
                <td class="py-2.5 px-4 font-bold text-slate-500">${idx + 1}</td>
                <td class="py-2.5 px-4 whitespace-nowrap">
                    <span class="font-black text-[#1f6b4a] text-xs">${b.receipt_id || b.bill_id}</span>
                </td>
                <td class="py-2.5 px-4 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-[#1f6b4a]/15 text-[#1f6b4a] flex items-center justify-center text-[10px] font-black">
                            ${(b.staff_username || 'U').substring(0, 1).toUpperCase()}
                        </span>
                        <div>
                            <span class="font-bold text-slate-800">${b.staff_fullname || b.staff_username}</span>
                            <span class="text-[9.5px] px-1.5 py-0.2 rounded border ${roleBadge} ml-1">${b.staff_role}</span>
                        </div>
                    </div>
                </td>
                <td class="py-2.5 px-4 whitespace-nowrap">
                    <div class="font-bold text-slate-800">${b.patient_name}</div>
                    <div class="text-[10px] text-slate-400 font-medium">${b.patient_id} • ${b.patient_phone || '-'}</div>
                </td>
                <td class="py-2.5 px-4">
                    <span class="font-medium text-slate-700 line-clamp-1">${b.doctor_name || b.purpose || 'Clinical Consultation'}</span>
                </td>
                <td class="py-2.5 px-4 whitespace-nowrap text-slate-600">
                    <div>${formattedDate}</div>
                    <div class="text-[10px] text-slate-400 font-medium">${b.bill_time ? b.bill_time.substring(0, 5) : ''}</div>
                </td>
                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold border ${modeBadge}">
                        ${mode}
                    </span>
                </td>
                <td class="py-2.5 px-3 text-right font-bold text-slate-700 whitespace-nowrap">
                    ${fmt(b.grand_total)}
                </td>
                <td class="py-2.5 px-3 text-right font-black text-emerald-700 whitespace-nowrap">
                    ${fmt(b.amount_paid)}
                </td>
                <td class="py-2.5 px-3 text-right whitespace-nowrap">
                    ${parseFloat(b.balance_due) > 0 ? `<span class="font-black text-rose-600">${fmt(b.balance_due)}</span>` : `<span class="text-slate-400">₹0.00</span>`}
                </td>
                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                    <button type="button" onclick="printReceiptDirect('${b.receipt_id || b.bill_id}', '${b.bill_id}')" class="p-1.5 text-slate-500 hover:text-[#1f6b4a] hover:bg-[#e8f4ed] rounded-lg transition-all" title="Print Receipt">
                        <i class="fas fa-print text-xs"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    if (tfoot) {
        tfoot.innerHTML = `
            <tr>
                <td colspan="7" class="py-3 px-4 uppercase font-bold text-[#1f6b4a]">
                    Total (${filtered.length} Filtered Receipts)
                </td>
                <td class="py-3 px-3 text-right font-bold text-slate-800">${fmt(totBilled)}</td>
                <td class="py-3 px-3 text-right font-black text-emerald-800 text-sm">${fmt(totPaid)}</td>
                <td class="py-3 px-3 text-right font-black ${totDue > 0 ? 'text-rose-600' : 'text-slate-500'}">${fmt(totDue)}</td>
                <td></td>
            </tr>
        `;
    }
}

/**
 * Export Cashier Shift Handover Data (CSV / Excel)
 */
function exportCashierShift(format = 'csv') {
    const staffList = (recState.breakdowns && recState.breakdowns.staff) ? recState.breakdowns.staff : [];
    if (staffList.length === 0) {
        showError('No cashier shift records available to export');
        return;
    }

    if (format === 'csv') {
        let csv = 'Cashier Shift Handover & Reconciliation Audit Report\n';
        csv += `Exported On:,"${new Date().toLocaleString()}"\n\n`;
        csv += 'Staff Username,Full Name,Role,Receipts Count,Cash Amount,UPI Amount,Card Amount,Other Amount,Total Gross Billed,Total Collected,Pending Due,Discount\n';

        staffList.forEach(s => {
            csv += `"${s.staff_username}","${s.full_name || s.staff_username}","${s.role || 'Staff'}",${s.bills_count},${s.cash_collected},${s.upi_collected},${s.card_collected},${s.other_collected},${s.total_billed},${s.collected_amount},${s.pending_amount},${s.discount_amount}\n`;
        });

        // Add Itemized Section
        csv += '\n\nItemized Shift Patient Receipts Ledger\n';
        csv += 'Cashier,Receipt ID,Bill Date,Bill Time,Patient Name,Patient ID,Phone,Doctor / Service,Payment Mode,Billed Amount,Amount Paid,Balance Due,Status\n';

        staffList.forEach(s => {
            (s.bills_list || []).forEach(b => {
                csv += `"${s.staff_username}","${b.receipt_id || b.bill_id}","${b.bill_date}","${b.bill_time}","${b.patient_name}","${b.patient_id}","${b.patient_phone}","${b.doctor_name || b.purpose}","${b.payment_mode}",${b.grand_total},${b.amount_paid},${b.balance_due},"${b.payment_status}"\n`;
            });
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `Cashier_Shift_Handover_${new Date().toISOString().substring(0, 10)}.csv`;
        link.click();
        showSuccess('Cashier shift report exported to CSV successfully');

    } else if (format === 'excel') {
        const fmt = (v) => '₹' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        let html = `
            <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
            <head><meta charset="utf-8"/></head>
            <body>
                <h2>GM HOSPITAL - Cashier Shift Handover & Reconciliation Audit</h2>
                <p>Generated on: ${new Date().toLocaleString()}</p>
                <table border="1">
                    <thead>
                        <tr style="background-color: #1f6b4a; color: white;">
                            <th>Cashier Username</th>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Receipts Count</th>
                            <th>Cash Amount (₹)</th>
                            <th>UPI Amount (₹)</th>
                            <th>Card Amount (₹)</th>
                            <th>Other Amount (₹)</th>
                            <th>Total Gross Billed (₹)</th>
                            <th>Total Collected (₹)</th>
                            <th>Pending Due (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        staffList.forEach(s => {
            html += `
                <tr>
                    <td>${s.staff_username}</td>
                    <td>${s.full_name || s.staff_username}</td>
                    <td>${s.role || 'Staff'}</td>
                    <td>${s.bills_count}</td>
                    <td>${s.cash_collected}</td>
                    <td>${s.upi_collected}</td>
                    <td>${s.card_collected}</td>
                    <td>${s.other_collected}</td>
                    <td>${s.total_billed}</td>
                    <td><b>${s.collected_amount}</b></td>
                    <td>${s.pending_amount}</td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </body>
            </html>
        `;

        const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `Cashier_Shift_Handover_${new Date().toISOString().substring(0, 10)}.xls`;
        link.click();
        showSuccess('Cashier shift report exported to Excel successfully');
    }
}

/**
 * Print Full Shift Handover & Reconciliation Report
 */
function printFullShiftHandoverReport() {
    const staffList = (recState.breakdowns && recState.breakdowns.staff) ? recState.breakdowns.staff : [];
    if (staffList.length === 0) {
        showError('No cashier shift data available to print');
        return;
    }

    const fmt = (v) => '₹' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    let totalCash = 0, totalUpi = 0, totalCard = 0, totalOther = 0, totalCollected = 0, totalBilled = 0, totalDue = 0, totalBills = 0;

    let staffOverviewRows = '';
    staffList.forEach((s, idx) => {
        totalCash += parseFloat(s.cash_collected || 0);
        totalUpi += parseFloat(s.upi_collected || 0);
        totalCard += parseFloat(s.card_collected || 0);
        totalOther += parseFloat(s.other_collected || 0);
        totalCollected += parseFloat(s.collected_amount || 0);
        totalBilled += parseFloat(s.total_billed || 0);
        totalDue += parseFloat(s.pending_amount || 0);
        totalBills += parseInt(s.bills_count || 0);

        staffOverviewRows += `
            <tr>
                <td style="padding: 6px; border-bottom: 1px solid #cbd5e1; font-size: 11px;">${idx + 1}</td>
                <td style="padding: 6px; border-bottom: 1px solid #cbd5e1; font-size: 11px; font-weight: bold;">${s.full_name || s.staff_username} (${s.staff_username})</td>
                <td style="padding: 6px; border-bottom: 1px solid #cbd5e1; font-size: 11px;">${s.role || 'Receptionist'}</td>
                <td style="padding: 6px; border-bottom: 1px solid #cbd5e1; font-size: 11px; text-align: center;">${s.bills_count}</td>
                <td style="padding: 6px; border-bottom: 1px solid #cbd5e1; font-size: 11px; text-align: right; font-weight: bold; color: #166534;">${fmt(s.cash_collected)}</td>
                <td style="padding: 6px; border-bottom: 1px solid #cbd5e1; font-size: 11px; text-align: right; color: #6b21a8;">${fmt(s.upi_collected)}</td>
                <td style="padding: 6px; border-bottom: 1px solid #cbd5e1; font-size: 11px; text-align: right; color: #1e40af;">${fmt(s.card_collected)}</td>
                <td style="padding: 6px; border-bottom: 1px solid #cbd5e1; font-size: 11px; text-align: right; font-weight: bold; color: #1f6b4a;">${fmt(s.collected_amount)}</td>
                <td style="padding: 6px; border-bottom: 1px solid #cbd5e1; font-size: 11px; text-align: right; color: ${s.pending_amount > 0 ? '#dc2626' : '#64748b'};">${fmt(s.pending_amount)}</td>
            </tr>
        `;
    });

    // Itemized transactions per cashier
    let cashierDetailsHtml = '';
    staffList.forEach(st => {
        const bills = st.bills_list || [];
        let billRows = '';
        bills.forEach((b, bIdx) => {
            billRows += `
                <tr>
                    <td style="padding: 5px; border-bottom: 1px solid #e2e8f0; font-size: 10px;">${bIdx + 1}</td>
                    <td style="padding: 5px; border-bottom: 1px solid #e2e8f0; font-size: 10px; font-weight: bold;">${b.receipt_id || b.bill_id}</td>
                    <td style="padding: 5px; border-bottom: 1px solid #e2e8f0; font-size: 10px;">${b.bill_date} ${b.bill_time ? b.bill_time.substring(0, 5) : ''}</td>
                    <td style="padding: 5px; border-bottom: 1px solid #e2e8f0; font-size: 10px;"><strong>${b.patient_name}</strong> (${b.patient_id})</td>
                    <td style="padding: 5px; border-bottom: 1px solid #e2e8f0; font-size: 10px;">${b.doctor_name || b.purpose}</td>
                    <td style="padding: 5px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-align: right;">${fmt(b.grand_total)}</td>
                    <td style="padding: 5px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-align: right; font-weight: bold; color: #1f6b4a;">${fmt(b.amount_paid)}</td>
                    <td style="padding: 5px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-align: center;">${b.payment_mode}</td>
                </tr>
            `;
        });

        cashierDetailsHtml += `
            <div style="margin-top: 15px; border: 1px solid #cbd5e1; border-radius: 4px; overflow: hidden; page-break-inside: avoid;">
                <div style="background-color: #f1f5f9; padding: 6px 10px; font-size: 11px; font-weight: bold; display: flex; justify-content: space-between; border-bottom: 1px solid #cbd5e1;">
                    <span>👤 Cashier: ${st.full_name || st.staff_username} (${st.role || 'Staff'}) • ${bills.length} Receipts</span>
                    <span>Total Collected: ${fmt(st.collected_amount)} (Cash: ${fmt(st.cash_collected)} | Digital: ${fmt(st.upi_collected + st.card_collected)})</span>
                </div>
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="background-color: #f8fafc; font-size: 9px; text-transform: uppercase; color: #64748b;">
                            <th style="padding: 5px; border-bottom: 1px solid #cbd5e1;">#</th>
                            <th style="padding: 5px; border-bottom: 1px solid #cbd5e1;">Receipt #</th>
                            <th style="padding: 5px; border-bottom: 1px solid #cbd5e1;">Date & Time</th>
                            <th style="padding: 5px; border-bottom: 1px solid #cbd5e1;">Patient</th>
                            <th style="padding: 5px; border-bottom: 1px solid #cbd5e1;">Service / Doctor</th>
                            <th style="padding: 5px; border-bottom: 1px solid #cbd5e1; text-align: right;">Billed</th>
                            <th style="padding: 5px; border-bottom: 1px solid #cbd5e1; text-align: right;">Collected</th>
                            <th style="padding: 5px; border-bottom: 1px solid #cbd5e1; text-align: center;">Mode</th>
                        </tr>
                    </thead>
                    <tbody>${billRows}</tbody>
                </table>
            </div>
        `;
    });

    const printWin = window.open('', '_blank', 'width=950,height=800');
    if (!printWin) {
        showError('Please allow popups to print shift handover report');
        return;
    }

    printWin.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Cashier Shift Handover & Reconciliation Audit</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #1e293b; margin: 25px; }
                .header { border-bottom: 2px solid #1f6b4a; padding-bottom: 12px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: flex-start; }
                .h-title { font-size: 20px; font-weight: bold; color: #1f6b4a; }
                .sec-title { font-size: 13px; font-weight: bold; color: #0f172a; text-transform: uppercase; margin: 15px 0 6px 0; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; }
                .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 15px; }
                .kpi-cell { border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px; text-align: center; }
                @media print { body { margin: 0; } }
            </style>
        </head>
        <body>
            <div class="header">
                <div>
                    <div class="h-title">GM HOSPITAL</div>
                    <div style="font-size: 11px; color: #64748b;">Cashier Shift Handover & Drawer Reconciliation Statement</div>
                    <div style="margin-top: 6px; font-size: 14px; font-weight: bold; color: #0f172a;">Shift Audit Report</div>
                </div>
                <div style="text-align: right; font-size: 11px; color: #64748b;">
                    <div>Generated on: ${new Date().toLocaleString()}</div>
                    <div style="font-weight: bold; color: #1f6b4a; margin-top: 4px; font-size: 13px;">Total Shift Collection: ${fmt(totalCollected)}</div>
                    <div style="font-size: 11px; color: #0f172a;">${staffList.length} Active Cashiers • ${totalBills} Receipts</div>
                </div>
            </div>

            <div class="kpi-row">
                <div class="kpi-cell" style="background-color: #f0fdf4; border-color: #bbf7d0;">
                    <div style="font-size: 10px; text-transform: uppercase; color: #166534; font-weight: bold;">Cash Drawer (Physical)</div>
                    <div style="font-size: 15px; font-weight: bold; color: #15803d; margin-top: 2px;">${fmt(totalCash)}</div>
                </div>
                <div class="kpi-cell" style="background-color: #faf5ff; border-color: #e9d5ff;">
                    <div style="font-size: 10px; text-transform: uppercase; color: #6b21a8; font-weight: bold;">UPI / QR Code</div>
                    <div style="font-size: 15px; font-weight: bold; color: #7e22ce; margin-top: 2px;">${fmt(totalUpi)}</div>
                </div>
                <div class="kpi-cell" style="background-color: #eff6ff; border-color: #bfdbfe;">
                    <div style="font-size: 10px; text-transform: uppercase; color: #1e40af; font-weight: bold;">Card / POS</div>
                    <div style="font-size: 15px; font-weight: bold; color: #2563eb; margin-top: 2px;">${fmt(totalCard)}</div>
                </div>
                <div class="kpi-cell">
                    <div style="font-size: 10px; text-transform: uppercase; color: #64748b; font-weight: bold;">Reconciled Total</div>
                    <div style="font-size: 15px; font-weight: bold; color: #0f172a; margin-top: 2px;">${fmt(totalCollected)}</div>
                </div>
            </div>

            <div class="sec-title">Staff / Cashier Login Collection Summary</div>
            <table style="width: 100%; border-collapse: collapse; text-align: left; margin-bottom: 15px;">
                <thead>
                    <tr style="background-color: #f1f5f9; font-size: 10px; text-transform: uppercase; color: #64748b;">
                        <th style="padding: 6px; border-bottom: 1px solid #cbd5e1;">#</th>
                        <th style="padding: 6px; border-bottom: 1px solid #cbd5e1;">Cashier User</th>
                        <th style="padding: 6px; border-bottom: 1px solid #cbd5e1;">Role</th>
                        <th style="padding: 6px; border-bottom: 1px solid #cbd5e1; text-align: center;">Bills</th>
                        <th style="padding: 6px; border-bottom: 1px solid #cbd5e1; text-align: right;">Cash (₹)</th>
                        <th style="padding: 6px; border-bottom: 1px solid #cbd5e1; text-align: right;">UPI (₹)</th>
                        <th style="padding: 6px; border-bottom: 1px solid #cbd5e1; text-align: right;">Card (₹)</th>
                        <th style="padding: 6px; border-bottom: 1px solid #cbd5e1; text-align: right;">Total Collected</th>
                        <th style="padding: 6px; border-bottom: 1px solid #cbd5e1; text-align: right;">Pending Due</th>
                    </tr>
                </thead>
                <tbody>${staffOverviewRows}</tbody>
                <tfoot>
                    <tr style="background-color: #f8fafc; font-weight: bold; font-size: 11px; border-top: 2px solid #cbd5e1;">
                        <td colspan="3" style="padding: 8px; text-transform: uppercase; color: #1f6b4a;">Shift Reconciled Total</td>
                        <td style="padding: 8px; text-align: center;">${totalBills}</td>
                        <td style="padding: 8px; text-align: right; color: #166534;">${fmt(totalCash)}</td>
                        <td style="padding: 8px; text-align: right; color: #6b21a8;">${fmt(totalUpi)}</td>
                        <td style="padding: 8px; text-align: right; color: #1e40af;">${fmt(totalCard)}</td>
                        <td style="padding: 8px; text-align: right; color: #1f6b4a;">${fmt(totalCollected)}</td>
                        <td style="padding: 8px; text-align: right; color: ${totalDue > 0 ? '#dc2626' : '#64748b'};">${fmt(totalDue)}</td>
                    </tr>
                </tfoot>
            </table>

            <div class="sec-title" style="margin-top: 20px;">Cashier Itemized Patient Receipts Breakdown</div>
            ${cashierDetailsHtml}

            <!-- Cash Drawer Handover Declaration Box -->
            <div style="margin-top: 25px; border: 1.5px dashed #94a3b8; border-radius: 6px; padding: 12px; font-size: 11px; background-color: #f8fafc; page-break-inside: avoid;">
                <div style="font-weight: bold; text-transform: uppercase; margin-bottom: 8px; color: #1f6b4a;">Physical Cash Drawer Handover Signoff</div>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 10px;">
                    <div>
                        <div>Physical Cash Counted: ₹_______________</div>
                        <div style="margin-top: 20px;">Outgoing Cashier Sign: _______________</div>
                    </div>
                    <div>
                        <div>Discrepancy (if any): ₹_______________</div>
                        <div style="margin-top: 20px;">Incoming Cashier Sign: _______________</div>
                    </div>
                    <div>
                        <div>Handover Time: _______________</div>
                        <div style="margin-top: 20px;">Supervisor / Admin Sign: _______________</div>
                    </div>
                </div>
            </div>

            <script>
                window.onload = function() {
                    window.print();
                };
            </script>
        </body>
        </html>
    `);
    printWin.document.close();
}

/**
 * Print Single Cashier Shift Voucher
 */
function printSingleCashierShiftVoucher(cashierUsername) {
    const staffList = (recState.breakdowns && recState.breakdowns.staff) ? recState.breakdowns.staff : [];
    const st = staffList.find(s => (s.staff_username || '').toLowerCase() === (cashierUsername || '').toLowerCase());
    if (!st) {
        showError(`Cashier records for "${cashierUsername}" not found`);
        return;
    }

    const fmt = (v) => '₹' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const bills = st.bills_list || [];

    let billRows = '';
    bills.forEach((b, idx) => {
        billRows += `
            <tr>
                <td style="padding: 5px; border-bottom: 1px solid #e2e8f0; font-size: 10.5px;">${idx + 1}</td>
                <td style="padding: 5px; border-bottom: 1px solid #e2e8f0; font-size: 10.5px; font-weight: bold;">${b.receipt_id || b.bill_id}</td>
                <td style="padding: 5px; border-bottom: 1px solid #e2e8f0; font-size: 10.5px;">${b.bill_date} ${b.bill_time ? b.bill_time.substring(0, 5) : ''}</td>
                <td style="padding: 5px; border-bottom: 1px solid #e2e8f0; font-size: 10.5px;"><strong>${b.patient_name}</strong> (${b.patient_id})</td>
                <td style="padding: 5px; border-bottom: 1px solid #e2e8f0; font-size: 10.5px; text-align: right; font-weight: bold; color: #1f6b4a;">${fmt(b.amount_paid)}</td>
                <td style="padding: 5px; border-bottom: 1px solid #e2e8f0; font-size: 10.5px; text-align: center;">${b.payment_mode}</td>
            </tr>
        `;
    });

    const printWin = window.open('', '_blank', 'width=900,height=750');
    if (!printWin) {
        showError('Please allow popups to print shift voucher');
        return;
    }

    printWin.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Cashier Handover Voucher - ${st.staff_username}</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #1e293b; margin: 25px; }
                .header { border-bottom: 2px solid #1f6b4a; padding-bottom: 10px; margin-bottom: 15px; display: flex; justify-content: space-between; }
                .h-title { font-size: 18px; font-weight: bold; color: #1f6b4a; }
                .kpi-box { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 15px; }
                .kpi-card { border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px; text-align: center; }
                @media print { body { margin: 0; } }
            </style>
        </head>
        <body>
            <div class="header">
                <div>
                    <div class="h-title">GM HOSPITAL</div>
                    <div style="font-size: 11px; color: #64748b;">Individual Cashier Shift Handover Voucher</div>
                    <div style="margin-top: 4px; font-size: 14px; font-weight: bold; color: #0f172a;">Cashier: ${st.full_name || st.staff_username} (${st.role || 'Receptionist'})</div>
                </div>
                <div style="text-align: right; font-size: 11px; color: #64748b;">
                    <div>Date: ${new Date().toLocaleDateString()}</div>
                    <div style="font-weight: bold; color: #1f6b4a; margin-top: 4px;">Total: ${fmt(st.collected_amount)}</div>
                </div>
            </div>

            <div class="kpi-box">
                <div class="kpi-card" style="background-color: #f0fdf4;">
                    <div style="font-size: 9.5px; text-transform: uppercase; color: #166534; font-weight: bold;">Cash In Drawer</div>
                    <div style="font-size: 14px; font-weight: bold; color: #15803d;">${fmt(st.cash_collected)}</div>
                </div>
                <div class="kpi-card" style="background-color: #faf5ff;">
                    <div style="font-size: 9.5px; text-transform: uppercase; color: #6b21a8; font-weight: bold;">UPI / QR</div>
                    <div style="font-size: 14px; font-weight: bold; color: #7e22ce;">${fmt(st.upi_collected)}</div>
                </div>
                <div class="kpi-card" style="background-color: #eff6ff;">
                    <div style="font-size: 9.5px; text-transform: uppercase; color: #1e40af; font-weight: bold;">Card / POS</div>
                    <div style="font-size: 14px; font-weight: bold; color: #2563eb;">${fmt(st.card_collected)}</div>
                </div>
                <div class="kpi-card">
                    <div style="font-size: 9.5px; text-transform: uppercase; color: #64748b; font-weight: bold;">Total Receipts</div>
                    <div style="font-size: 14px; font-weight: bold; color: #0f172a;">${st.bills_count}</div>
                </div>
            </div>

            <div style="font-size: 11px; font-weight: bold; text-transform: uppercase; margin-bottom: 6px; color: #0f172a;">Receipts Generated During Shift</div>
            <table style="width: 100%; border-collapse: collapse; text-align: left; margin-bottom: 15px;">
                <thead>
                    <tr style="background-color: #f1f5f9; font-size: 9.5px; text-transform: uppercase; color: #64748b;">
                        <th style="padding: 5px; border-bottom: 1px solid #cbd5e1;">#</th>
                        <th style="padding: 5px; border-bottom: 1px solid #cbd5e1;">Receipt #</th>
                        <th style="padding: 5px; border-bottom: 1px solid #cbd5e1;">Date & Time</th>
                        <th style="padding: 5px; border-bottom: 1px solid #cbd5e1;">Patient</th>
                        <th style="padding: 5px; border-bottom: 1px solid #cbd5e1; text-align: right;">Amount</th>
                        <th style="padding: 5px; border-bottom: 1px solid #cbd5e1; text-align: center;">Mode</th>
                    </tr>
                </thead>
                <tbody>${billRows}</tbody>
            </table>

            <div style="margin-top: 25px; border-top: 1px solid #cbd5e1; padding-top: 15px; display: flex; justify-content: space-between; font-size: 11px; color: #64748b;">
                <div>Cashier Signature: __________________</div>
                <div>Supervisor / Reliever Signature: __________________</div>
            </div>

            <script>
                window.onload = function() {
                    window.print();
                };
            </script>
        </body>
        </html>
    `);
    printWin.document.close();
}

/**
 * Render Cashier / Staff Breakdown
 */
function renderReceiptStaffBreakdown(staffList) {
    const tbody = document.getElementById('rec-staff-tbody');
    if (!tbody) return;

    if (!staffList || staffList.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-slate-400">No cashier data</td></tr>';
        return;
    }

    const fmt = (v) => '₹' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    tbody.innerHTML = staffList.map((s, idx) => {
        let badge = idx === 0 ? '🥇' : (idx === 1 ? '🥈' : (idx === 2 ? '🥉' : `#${idx + 1}`));
        return `
            <tr class="hover:bg-slate-50 border-b border-slate-100">
                <td class="py-2.5 px-4 font-black text-slate-500">${badge}</td>
                <td class="py-2.5 px-4 font-bold text-[#1f6b4a]">${s.staff_username} (${s.role || 'Staff'})</td>
                <td class="py-2.5 px-4 text-center font-semibold text-slate-700">${s.bills_count}</td>
                <td class="py-2.5 px-4 text-right font-bold text-emerald-700">${fmt(s.collected_amount)}</td>
                <td class="py-2.5 px-4 text-right font-bold text-rose-600">${fmt(s.pending_amount)}</td>
            </tr>
        `;
    }).join('');
}

/**
 * Populate Cashier and Doctor Dropdowns dynamically
 */
function populateReceiptFilterDropdowns(breakdowns) {
    const cashierSelect = document.getElementById('rec-filter-cashier');
    const doctorSelect = document.getElementById('rec-filter-doctor');
    const deptSelect = document.getElementById('rec-filter-dept');

    if (cashierSelect && cashierSelect.options.length <= 1 && breakdowns.staff) {
        breakdowns.staff.forEach(st => {
            if (st.staff_name && st.staff_name !== 'System') {
                const opt = document.createElement('option');
                opt.value = st.staff_name;
                opt.textContent = st.staff_name;
                cashierSelect.appendChild(opt);
            }
        });
    }

    if (doctorSelect && doctorSelect.options.length <= 1 && breakdowns.doctor) {
        breakdowns.doctor.forEach(d => {
            if (d.doctor_name && d.doctor_name !== 'Direct Service') {
                const opt = document.createElement('option');
                opt.value = d.doctor_name;
                opt.textContent = d.doctor_name;
                doctorSelect.appendChild(opt);
            }
        });
    }

    if (deptSelect && breakdowns.department) {
        breakdowns.department.forEach(dp => {
            if (dp.department && !deptSelect.querySelector(`option[value="${dp.department}"]`)) {
                const opt = document.createElement('option');
                opt.value = dp.department;
                opt.textContent = dp.department;
                deptSelect.appendChild(opt);
            }
        });
    }
}

/**
 * Render Receipt Charts
 */
function renderReceiptCharts(hourlyShift, paymentModes) {
    // 1. Trends Bar Chart
    const trendsCtx = document.getElementById('recTrendsChart');
    if (trendsCtx && hourlyShift && hourlyShift.length > 0) {
        const labels = hourlyShift.map(h => h.time_label);
        const data = hourlyShift.map(h => h.total_collected);

        if (recTrendsChartInstance) recTrendsChartInstance.destroy();

        recTrendsChartInstance = new Chart(trendsCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Hourly Collection (₹)',
                    data: data,
                    backgroundColor: 'rgba(31, 107, 74, 0.85)',
                    borderColor: '#1f6b4a',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    // 2. Payment Modes Doughnut Chart
    const modeCtx = document.getElementById('recModeChart');
    if (modeCtx && paymentModes && paymentModes.length > 0) {
        const labels = paymentModes.map(p => p.payment_mode);
        const data = paymentModes.map(p => parseFloat(p.collected_amount || 0));
        const colors = ['#1f6b4a', '#3b82f6', '#f59e0b', '#8b5cf6', '#ef4444', '#64748b'];

        if (recModeChartInstance) recModeChartInstance.destroy();

        recModeChartInstance = new Chart(modeCtx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
}

/**
 * Switch Sub-Tab inside Receipts
 */
function switchReceiptSubTab(subtab) {
    recState.active_subtab = subtab;

    // Toggle subtab buttons
    document.querySelectorAll('.rec-subtab-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.getAttribute('onclick').includes(subtab)) {
            btn.classList.add('active');
        }
    });

    // Toggle subviews
    document.querySelectorAll('.rec-subview').forEach(view => {
        view.classList.add('hidden');
    });

    const target = document.getElementById('rec-subview-' + subtab);
    if (target) target.classList.remove('hidden');

    if (subtab === 'charts') {
        renderReceiptCharts(recState.hourly_shift, recState.breakdowns.payment_mode || []);
    }
}

/**
 * Set Quick Date Preset Pill
 */
function setReceiptDatePreset(preset) {
    recState.date_preset = preset;
    recState.date_from = '';
    recState.date_to = '';

    const df = document.getElementById('rec-filter-date-from');
    const dt = document.getElementById('rec-filter-date-to');
    if (df) df.value = '';
    if (dt) dt.value = '';

    document.querySelectorAll('.rec-preset-btn').forEach(btn => {
        if (btn.dataset.preset) {
            btn.classList.remove('active');
            if (btn.dataset.preset === preset) btn.classList.add('active');
        }
    });

    loadAdvancedReceipts(1);
}

/**
 * Toggle Outstanding Due Filter Pill
 */
function toggleReceiptOutstanding() {
    recState.has_outstanding = !recState.has_outstanding;
    const btn = document.getElementById('rec-pill-outstanding');
    if (btn) {
        btn.classList.toggle('active', recState.has_outstanding);
    }
    loadAdvancedReceipts(1);
}

/**
 * Toggle Duplicates Filter Pill
 */
function toggleReceiptDuplicates() {
    recState.is_duplicate = !recState.is_duplicate;
    const btn = document.getElementById('rec-pill-duplicates');
    if (btn) {
        btn.classList.toggle('active', recState.is_duplicate);
    }
    loadAdvancedReceipts(1);
}

/**
 * Toggle High Value Filter Pill
 */
function toggleReceiptHighValue() {
    recState.high_value = !recState.high_value;
    const btn = document.getElementById('rec-pill-highval');
    if (btn) {
        btn.classList.toggle('active', recState.high_value);
    }
    loadAdvancedReceipts(1);
}

/**
 * Debounced Omni-Search Input Handler
 */
function handleReceiptSearchInput() {
    clearTimeout(recSearchDebounceTimer);
    recSearchDebounceTimer = setTimeout(() => {
        const val = document.getElementById('rec-search-input').value.trim();
        recState.search = val;
        loadAdvancedReceipts(1);
    }, 300);
}

/**
 * Trigger Filter from Custom Dates
 */
function triggerReceiptFilter() {
    const dFrom = document.getElementById('rec-filter-date-from')?.value || '';
    const dTo = document.getElementById('rec-filter-date-to')?.value || '';
    const cashier = document.getElementById('rec-filter-cashier')?.value || '';
    const dept = document.getElementById('rec-filter-dept')?.value || '';
    const doctor = document.getElementById('rec-filter-doctor')?.value || '';
    const mode = document.getElementById('rec-filter-mode')?.value || '';
    const status = document.getElementById('rec-filter-status')?.value || '';
    const perPage = document.getElementById('rec-per-page')?.value || 25;

    recState.date_from = dFrom;
    recState.date_to = dTo;
    recState.created_by = cashier;
    recState.department = dept;
    recState.doctor = doctor;
    recState.payment_mode = mode;
    recState.payment_status = status;
    recState.limit = parseInt(perPage) || 25;

    if (dFrom || dTo) {
        recState.date_preset = '';
        document.querySelectorAll('.rec-preset-btn').forEach(btn => {
            if (btn.dataset.preset) btn.classList.remove('active');
        });
    }

    loadAdvancedReceipts(1);
}

/**
 * Reset All Filters
 */
function resetReceiptFilters() {
    recState = {
        search: '',
        date_preset: 'all',
        date_from: '',
        date_to: '',
        created_by: '',
        department: '',
        doctor: '',
        payment_status: '',
        payment_mode: '',
        has_outstanding: false,
        is_duplicate: false,
        high_value: false,
        page: 1,
        limit: 25,
        sort_by: 'date_desc',
        active_subtab: 'ledger',
        records: [],
        summary: {},
        hourly_shift: [],
        breakdowns: {}
    };

    const sIn = document.getElementById('rec-search-input');
    const dfIn = document.getElementById('rec-filter-date-from');
    const dtIn = document.getElementById('rec-filter-date-to');
    const cIn = document.getElementById('rec-filter-cashier');
    const depIn = document.getElementById('rec-filter-dept');
    const docIn = document.getElementById('rec-filter-doctor');
    const mIn = document.getElementById('rec-filter-mode');
    const stIn = document.getElementById('rec-filter-status');
    const pIn = document.getElementById('rec-per-page');

    if (sIn) sIn.value = '';
    if (dfIn) dfIn.value = '';
    if (dtIn) dtIn.value = '';
    if (cIn) cIn.value = '';
    if (depIn) depIn.value = '';
    if (docIn) docIn.value = '';
    if (mIn) mIn.value = '';
    if (stIn) stIn.value = '';
    if (pIn) pIn.value = '25';

    document.querySelectorAll('.rec-preset-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.preset === 'all') btn.classList.add('active');
    });

    loadAdvancedReceipts(1);
}

/**
 * Open Slide-in Receipt Details Drawer
 */
function openReceiptDrawer(billId) {
    const drawer = document.getElementById('receipt-drawer-backdrop');
    if (!drawer) return;

    drawer.classList.remove('hidden');

    document.getElementById('drawer-rec-id').innerText = 'Loading...';
    document.getElementById('drawer-rec-patient-name').innerText = 'Fetching patient...';
    document.getElementById('drawer-rec-items-tbody').innerHTML = '<tr><td colspan="4" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i></td></tr>';

    $.ajax({
        url: `../api/index.php/api/billing/opd/${billId}`,
        method: 'GET',
        success: function (res) {
            if (res.status === 'success') {
                const b = res.data;
                const fmt = (v) => '₹' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                const recId = b.receipt_no || (b.bill_id ? b.bill_id.replace('OPB-', 'ORC-').replace('BILL-', 'REC-') : 'REC-000');
                document.getElementById('drawer-rec-id').innerText = recId;
                document.getElementById('drawer-rec-status').innerText = b.payment_status || 'Paid';
                document.getElementById('drawer-rec-datetime').innerText = `${b.bill_date} ${b.bill_time || ''}`;

                document.getElementById('drawer-rec-patient-name').innerText = b.patient_name || 'Walking Patient';
                document.getElementById('drawer-rec-patient-meta').innerText = `PID: ${b.patient_id || 'N/A'} | Age: ${b.age || '--'} (${b.sex || '--'})`;
                document.getElementById('drawer-rec-patient-phone').innerHTML = `<i class="fas fa-phone mr-1 text-slate-400"></i> ${b.patient_phone || b.mobile || '--'}`;

                document.getElementById('drawer-rec-doctor').innerText = b.doctor_name ? `Dr. ${b.doctor_name}` : 'Direct Hospital Service';
                document.getElementById('drawer-rec-dept').innerText = `Purpose: ${b.purpose || 'OPD Service'}`;
                document.getElementById('drawer-rec-cashier').innerText = `Cashier: ${b.created_by || 'System'}`;

                // Items list
                const itemsTbody = document.getElementById('drawer-rec-items-tbody');
                if (b.items && b.items.length > 0) {
                    itemsTbody.innerHTML = b.items.map(item => `
                        <tr class="border-b border-slate-100">
                            <td class="py-2 px-3 font-semibold text-slate-700">${item.item_name}</td>
                            <td class="py-2 px-3 text-center font-bold">${parseFloat(item.quantity || 1)}</td>
                            <td class="py-2 px-3 text-right text-slate-600">${fmt(item.unit_price)}</td>
                            <td class="py-2 px-3 text-right font-black text-slate-900">${fmt(item.total_price)}</td>
                        </tr>
                    `).join('');
                } else {
                    itemsTbody.innerHTML = `
                        <tr class="border-b border-slate-100">
                            <td class="py-2 px-3 font-semibold text-slate-700">${b.purpose || 'Consultation / Registration Service'}</td>
                            <td class="py-2 px-3 text-center font-bold">1</td>
                            <td class="py-2 px-3 text-right text-slate-600">${fmt(b.subtotal)}</td>
                            <td class="py-2 px-3 text-right font-black text-slate-900">${fmt(b.subtotal)}</td>
                        </tr>
                    `;
                }

                // Summary amounts
                document.getElementById('drawer-rec-subtotal').innerText = fmt(b.subtotal);
                document.getElementById('drawer-rec-discount').innerText = `- ${fmt(b.discount_amount)}`;
                document.getElementById('drawer-rec-grand-total').innerText = fmt(b.grand_total);
                document.getElementById('drawer-rec-mode').innerText = b.payment_mode || 'Cash';
                document.getElementById('drawer-rec-paid').innerText = fmt(b.amount_paid);
                document.getElementById('drawer-rec-due').innerText = fmt(b.balance_due);

                // Notes
                const notesBox = document.getElementById('drawer-rec-notes-box');
                const notesEl = document.getElementById('drawer-rec-notes');
                if (b.notes && b.notes.trim()) {
                    notesBox.classList.remove('hidden');
                    notesEl.innerText = b.notes;
                } else {
                    notesBox.classList.add('hidden');
                }

                // Print button action
                document.getElementById('drawer-btn-print').setAttribute('onclick', `printBill('${b.bill_id}')`);

                // Collect Due Button
                const collectDueBtn = document.getElementById('drawer-btn-collect-due');
                const dueVal = parseFloat(b.balance_due || 0);
                if (dueVal > 0) {
                    collectDueBtn.classList.remove('hidden');
                    document.getElementById('drawer-due-btn-val').innerText = dueVal.toFixed(2);
                    collectDueBtn.setAttribute('onclick', `recordQuickPayment('${b.bill_id}', ${dueVal})`);
                } else {
                    collectDueBtn.classList.add('hidden');
                }

                // Cancel/Refund Button
                document.getElementById('drawer-btn-cancel-refund').setAttribute('onclick', `openRecCancelModal('${b.bill_id}', '${b.amount_paid}')`);
            }
        }
    });
}

function closeReceiptDrawer() {
    const drawer = document.getElementById('receipt-drawer-backdrop');
    if (drawer) drawer.classList.add('hidden');
}

/**
 * Cancel / Refund Modal Handlers
 */
function openRecCancelModal(billId, amountPaid) {
    activeCancelBillId = billId;
    document.getElementById('cancel-modal-bill-id').innerText = billId;
    document.getElementById('cancel-modal-refund-amt').value = parseFloat(amountPaid || 0).toFixed(2);
    document.getElementById('cancel-modal-reason').value = '';
    document.getElementById('rec-cancel-modal').classList.remove('hidden');
}

function closeRecCancelModal() {
    document.getElementById('rec-cancel-modal').classList.add('hidden');
    activeCancelBillId = null;
}

function submitRecCancelRefund() {
    if (!activeCancelBillId) return;

    const action = document.getElementById('cancel-modal-action').value;
    const refundAmt = parseFloat(document.getElementById('cancel-modal-refund-amt').value || 0);
    const reason = document.getElementById('cancel-modal-reason').value.trim();

    if (!reason) {
        showError('Please provide a mandatory reason for cancellation/refund.');
        return;
    }

    const btn = document.getElementById('btn-submit-rec-cancel');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

    $.ajax({
        url: '../api/index.php/api/billing/receipts/refund',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            bill_id: activeCancelBillId,
            action: action,
            refund_amount: refundAmt,
            reason: reason
        }),
        success: function (res) {
            btn.disabled = false;
            btn.innerHTML = 'Confirm Action';
            if (res.status === 'success') {
                showSuccess(res.message || 'Operation processed successfully!');
                closeRecCancelModal();
                closeReceiptDrawer();
                loadAdvancedReceipts(recState.page);
                loadBills();
            } else {
                showError(res.message || 'Failed to process request');
            }
        },
        error: function (xhr) {
            btn.disabled = false;
            btn.innerHTML = 'Confirm Action';
            showError('Server error while processing refund/cancellation.');
        }
    });
}

/**
 * Export Receipts to Excel (.xls HTML table format)
 */
function exportReceiptsToExcel() {
    if (!recState.records || recState.records.length === 0) {
        showError('No receipt records available to export.');
        return;
    }

    let html = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head><meta charset="utf-8"/></head>
        <body>
        <table border="1">
            <tr style="background-color: #1f6b4a; color: #ffffff; font-weight: bold;">
                <th>Receipt ID</th>
                <th>Bill Reference</th>
                <th>Date</th>
                <th>Time</th>
                <th>Patient ID</th>
                <th>Patient Name</th>
                <th>Phone</th>
                <th>Department</th>
                <th>Consulting Doctor</th>
                <th>Payment Mode</th>
                <th>Grand Total (₹)</th>
                <th>Amount Paid (₹)</th>
                <th>Balance Due (₹)</th>
                <th>Status</th>
                <th>Cashier</th>
            </tr>
    `;

    recState.records.forEach(r => {
        html += `
            <tr>
                <td>${r.receipt_id}</td>
                <td>${r.bill_id}</td>
                <td>${r.bill_date}</td>
                <td>${r.bill_time || ''}</td>
                <td>${r.patient_id || ''}</td>
                <td>${r.patient_name || ''}</td>
                <td>${r.patient_phone || ''}</td>
                <td>${r.department_name || ''}</td>
                <td>${r.doctor_name || ''}</td>
                <td>${r.payment_mode || 'Cash'}</td>
                <td>${parseFloat(r.grand_total || 0).toFixed(2)}</td>
                <td>${parseFloat(r.amount_paid || 0).toFixed(2)}</td>
                <td>${parseFloat(r.balance_due || 0).toFixed(2)}</td>
                <td>${r.payment_status || 'Paid'}</td>
                <td>${r.created_by || ''}</td>
            </tr>
        `;
    });

    html += '</table></body></html>';

    const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `GM_Hospital_Receipts_${new Date().toISOString().split('T')[0]}.xls`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

/**
 * Export Receipts to CSV
 */
function exportReceiptsToCSV() {
    if (!recState.records || recState.records.length === 0) {
        showError('No receipt records available to export.');
        return;
    }

    const headers = ["Receipt ID", "Bill Ref", "Date", "Time", "Patient ID", "Patient Name", "Phone", "Department", "Doctor", "Payment Mode", "Grand Total", "Amount Paid", "Balance Due", "Status", "Cashier"];
    const rows = recState.records.map(r => [
        `"${r.receipt_id}"`,
        `"${r.bill_id}"`,
        `"${r.bill_date}"`,
        `"${r.bill_time || ''}"`,
        `"${r.patient_id || ''}"`,
        `"${(r.patient_name || '').replace(/"/g, '""')}"`,
        `"${r.patient_phone || ''}"`,
        `"${(r.department_name || '').replace(/"/g, '""')}"`,
        `"${(r.doctor_name || '').replace(/"/g, '""')}"`,
        `"${r.payment_mode || 'Cash'}"`,
        parseFloat(r.grand_total || 0).toFixed(2),
        parseFloat(r.amount_paid || 0).toFixed(2),
        parseFloat(r.balance_due || 0).toFixed(2),
        `"${r.payment_status || 'Paid'}"`,
        `"${r.created_by || ''}"`
    ]);

    const csvContent = "data:text/csv;charset=utf-8," + [headers.join(','), ...rows.map(e => e.join(','))].join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `GM_Hospital_Receipts_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/**
 * Printable Shift Handover Voucher & Summary
 */
function printShiftHandover() {
    const dateStr = recState.date_from || new Date().toISOString().split('T')[0];
    const cashier = recState.created_by || 'All Cashiers';

    const fmt = (v) => '₹' + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    let rowsHtml = '';
    let totBills = 0, totCash = 0, totUpi = 0, totCard = 0, totGrand = 0;

    recState.hourly_shift.forEach(s => {
        totBills += s.bills_count;
        totCash += s.cash_collected;
        totUpi += s.upi_collected;
        totCard += s.card_collected;
        totGrand += s.total_collected;

        if (s.bills_count > 0) {
            rowsHtml += `
                <tr>
                    <td style="padding: 6px 10px; border: 1px solid #ddd;">${s.time_label}</td>
                    <td style="padding: 6px 10px; border: 1px solid #ddd; text-align: center;">${s.bills_count}</td>
                    <td style="padding: 6px 10px; border: 1px solid #ddd; text-align: right;">${fmt(s.cash_collected)}</td>
                    <td style="padding: 6px 10px; border: 1px solid #ddd; text-align: right;">${fmt(s.upi_collected)}</td>
                    <td style="padding: 6px 10px; border: 1px solid #ddd; text-align: right;">${fmt(s.card_collected)}</td>
                    <td style="padding: 6px 10px; border: 1px solid #ddd; text-align: right; font-weight: bold;">${fmt(s.total_collected)}</td>
                </tr>
            `;
        }
    });

    if (!rowsHtml) {
        rowsHtml = '<tr><td colspan="6" style="text-align: center; padding: 20px;">No shift transactions recorded for this period.</td></tr>';
    }

    const printWin = window.open('', '_blank', 'width=900,height=700');
    printWin.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Shift Handover & Reconciliation Voucher - GM Hospital</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 30px; color: #1e293b; }
                .header { text-align: center; border-bottom: 2px solid #1f6b4a; padding-bottom: 12px; margin-bottom: 20px; }
                .header h1 { margin: 0; color: #1f6b4a; font-size: 24px; }
                .header p { margin: 3px 0; font-size: 12px; color: #64748b; }
                .meta-box { display: flex; justify-content: space-between; background: #f8fafc; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 20px; font-size: 13px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 12px; }
                th { background-color: #1f6b4a; color: #ffffff; padding: 8px 10px; text-align: left; }
                .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 30px; }
                .summary-card { border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; text-align: center; }
                .summary-card h4 { margin: 0; font-size: 11px; text-transform: uppercase; color: #64748b; }
                .summary-card p { margin: 5px 0 0 0; font-size: 18px; font-weight: bold; color: #1f6b4a; }
                .signatures { display: flex; justify-content: space-between; margin-top: 60px; padding-top: 20px; border-top: 1px dashed #cbd5e1; }
                .sig-box { text-align: center; width: 220px; }
                .sig-line { border-top: 1px solid #334155; margin-bottom: 6px; }
                @media print { button { display: none; } }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>GM HOSPITAL (Basaveshwar Nagar)</h1>
                <p>No. 335, 3rd Stage, 4th Block, Basaveshwara nagar, Bengaluru 560079 | Tel: 0802221160</p>
                <h2 style="margin: 8px 0 0 0; font-size: 16px; color: #0f172a; text-transform: uppercase; letter-spacing: 1px;">
                    Cashier Shift Handover & Reconciliation Voucher
                </h2>
            </div>

            <div class="meta-box">
                <div><strong>Shift Date:</strong> ${dateStr}</div>
                <div><strong>Cashier / Counter:</strong> ${cashier}</div>
                <div><strong>Printed On:</strong> ${new Date().toLocaleString()}</div>
            </div>

            <div class="summary-grid">
                <div class="summary-card">
                    <h4>Total Receipts</h4>
                    <p>${totBills}</p>
                </div>
                <div class="summary-card">
                    <h4>Total Cash Collected</h4>
                    <p style="color: #15803d;">${fmt(totCash)}</p>
                </div>
                <div class="summary-card">
                    <h4>UPI / Card Digital</h4>
                    <p style="color: #2563eb;">${fmt(totUpi + totCard)}</p>
                </div>
                <div class="summary-card">
                    <h4>Gross Shift Collection</h4>
                    <p style="color: #1f6b4a;">${fmt(totGrand)}</p>
                </div>
            </div>

            <h3 style="font-size: 14px; margin-bottom: 8px; color: #1f6b4a;">Hour-by-Hour Shift Cash Breakdown</h3>
            <table>
                <thead>
                    <tr>
                        <th>Time Slot</th>
                        <th style="text-align: center;">Bills Count</th>
                        <th style="text-align: right;">Cash Amount (₹)</th>
                        <th style="text-align: right;">UPI / QR (₹)</th>
                        <th style="text-align: right;">Card (₹)</th>
                        <th style="text-align: right;">Slot Total (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    ${rowsHtml}
                </tbody>
                <tfoot>
                    <tr style="background: #f1f5f9; font-weight: bold;">
                        <td style="padding: 8px 10px; border: 1px solid #ddd;">Shift Reconciled Total</td>
                        <td style="padding: 8px 10px; border: 1px solid #ddd; text-align: center;">${totBills}</td>
                        <td style="padding: 8px 10px; border: 1px solid #ddd; text-align: right;">${fmt(totCash)}</td>
                        <td style="padding: 8px 10px; border: 1px solid #ddd; text-align: right;">${fmt(totUpi)}</td>
                        <td style="padding: 8px 10px; border: 1px solid #ddd; text-align: right;">${fmt(totCard)}</td>
                        <td style="padding: 8px 10px; border: 1px solid #ddd; text-align: right; font-size: 14px; color: #1f6b4a;">${fmt(totGrand)}</td>
                    </tr>
                </tfoot>
            </table>

            <div class="signatures">
                <div class="sig-box">
                    <div class="sig-line"></div>
                    <span style="font-size: 12px; font-weight: bold;">Handover Cashier Signature</span>
                </div>
                <div class="sig-box">
                    <div class="sig-line"></div>
                    <span style="font-size: 12px; font-weight: bold;">Taking-over Cashier Signature</span>
                </div>
                <div class="sig-box">
                    <div class="sig-line"></div>
                    <span style="font-size: 12px; font-weight: bold;">Billing In-Charge / Supervisor</span>
                </div>
            </div>

            <script>
                window.onload = function() {
                    window.print();
                };
            </script>
        </body>
        </html>
    `);
    printWin.document.close();
}

// Initial setup on DOM Ready
document.addEventListener('DOMContentLoaded', () => {
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), 0, 1);
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    
    const aStart = document.getElementById('analytics-start');
    const aEnd = document.getElementById('analytics-end');
    if (aStart) aStart.value = firstDay.toISOString().split('T')[0];
    if (aEnd) aEnd.value = lastDay.toISOString().split('T')[0];
});

