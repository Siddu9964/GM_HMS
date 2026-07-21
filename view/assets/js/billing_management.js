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
                <select class="w-full bg-white border border-slate-200 rounded-lg p-2 outline-none focus:ring-2 focus:ring-blue-500/20 text-xs font-bold" 
                        onchange="updateItemField(${item.id}, 'item_type', this.value)">
                    <option value="Consultation" ${item.item_type === 'Consultation' ? 'selected' : ''}>Consultation</option>
                    <option value="Investigation" ${item.item_type === 'Investigation' ? 'selected' : ''}>Investigation</option>
                    <option value="Procedure" ${item.item_type === 'Procedure' ? 'selected' : ''}>Procedure</option>
                    <option value="Medication" ${item.item_type === 'Medication' ? 'selected' : ''}>Medication</option>
                    <option value="Other" ${item.item_type === 'Other' ? 'selected' : ''}>Other</option>
                </select>
            </td>
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
                    <a href="javascript:void(0)" onclick="showBillDetails('${bill.bill_id}')" class="text-blue-600 hover:text-blue-800 font-black decoration-2 underline-offset-4 hover:underline">
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
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right relative">
                    <div class="relative inline-block text-left" class="action-dropdown-container">
                        <button onclick="toggleActionDropdown(event, '${bill.bill_id}')" class="text-slate-400 hover:text-blue-600 p-2 rounded-full hover:bg-blue-50 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <i class="fas fa-ellipsis-v w-4 text-center"></i>
                        </button>
                        <div id="dropdown-${bill.bill_id}" class="action-dropdown hidden absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl shadow-slate-200/50 border border-slate-100 z-[100] overflow-hidden origin-top-right">
                            <div class="py-1">
                                <a href="javascript:void(0)" onclick="viewBill('${bill.bill_id}'); closeAllDropdowns();" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition-colors">
                                    <i class="fas fa-eye w-4 text-center"></i> View Details
                                </a>
                                <a href="javascript:void(0)" onclick="editBill('${bill.bill_id}'); closeAllDropdowns();" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-amber-50 hover:text-amber-700 transition-colors">
                                    <i class="fas fa-edit w-4 text-center text-amber-500"></i> Edit Bill
                                </a>
                                <a href="javascript:void(0)" onclick="printBill('${bill.bill_id}'); closeAllDropdowns();" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition-colors">
                                    <i class="fas fa-print w-4 text-center text-emerald-500"></i> Print Invoice
                                </a>
                                <div class="h-px bg-slate-100 my-1"></div>
                                <a href="javascript:void(0)" onclick="deleteBill('${bill.bill_id}'); closeAllDropdowns();" class="flex items-center gap-3 px-4 py-2.5 text-sm font-black text-rose-600 hover:bg-rose-50 transition-colors group">
                                    <i class="fas fa-trash-alt w-4 text-center group-hover:scale-110 transition-transform"></i> Delete Bill
                                </a>
                            </div>
                        </div>
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
    tbody.innerHTML = `
        <tr><td colspan="9" class="text-center py-8">
            <div class="text-gray-500">
                <i class="fas fa-bed text-4xl mb-3"></i>
                <p class="font-semibold">IPD Billing</p>
                <p class="text-sm mt-2">In-Patient Department billing management</p>
                <p class="text-xs mt-4 text-orange-600">IPD billing module will be available soon</p>
            </div>
        </td></tr>
    `;
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
    alert('✅ ' + message);
}

/**
 * Show error notification
 */
function showError(message) {
    alert('❌ ' + message);
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
