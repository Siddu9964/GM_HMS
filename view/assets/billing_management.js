/**
 * Billing Management - Admin Panel
 * Handles OPD and IPD billing operations
 */

let billingItems = [];
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
    // Patient select with AJAX
    $('#patient-select').select2({
        ajax: {
            url: '../controler/api/PatientController.php?action=search',
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
                        results: data.data.map(patient => ({
                            id: patient.patient_id,
                            text: `${patient.first_name} ${patient.last_name} (${patient.patient_id})`,
                            patient: patient
                        }))
                    };
                }
                return { results: [] };
            }
        },
        placeholder: 'Search patient by name or ID...',
        minimumInputLength: 2
    });

    // Doctor select with AJAX
    $('#doctor-select').select2({
        ajax: {
            url: '../controler/api/DoctorController.php?action=search',
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
        allowClear: true
    });
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
        document.getElementById('opd-billing-form').reset();
        $('#patient-select').val(null).trigger('change');
        $('#doctor-select').val(null).trigger('change');
        $('#patient-info').addClass('hidden');
        billingItems = [];
        renderBillingItems();
        calculateTotals();
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
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-gray-500 py-4">No items added. Click "Add Item" to begin.</td></tr>';
        return;
    }

    tbody.innerHTML = billingItems.map(item => `
        <tr>
            <td>
                <select class="w-full px-2 py-1 border border-gray-300 rounded" 
                        onchange="updateItemField(${item.id}, 'item_type', this.value)">
                    <option value="Consultation" ${item.item_type === 'Consultation' ? 'selected' : ''}>Consultation</option>
                    <option value="Investigation" ${item.item_type === 'Investigation' ? 'selected' : ''}>Investigation</option>
                    <option value="Procedure" ${item.item_type === 'Procedure' ? 'selected' : ''}>Procedure</option>
                    <option value="Medication" ${item.item_type === 'Medication' ? 'selected' : ''}>Medication</option>
                    <option value="Other" ${item.item_type === 'Other' ? 'selected' : ''}>Other</option>
                </select>
            </td>
            <td>
                <input type="text" class="w-full px-2 py-1 border border-gray-300 rounded" 
                       value="${item.item_name}" 
                       onchange="updateItemField(${item.id}, 'item_name', this.value)"
                       placeholder="Item name">
            </td>
            <td>
                <input type="number" class="w-full px-2 py-1 border border-gray-300 rounded" 
                       value="${item.quantity}" min="1" step="1"
                       onchange="updateItemField(${item.id}, 'quantity', parseFloat(this.value))">
            </td>
            <td>
                <input type="number" class="w-full px-2 py-1 border border-gray-300 rounded" 
                       value="${item.unit_price}" min="0" step="0.01"
                       onchange="updateItemField(${item.id}, 'unit_price', parseFloat(this.value))">
            </td>
            <td class="font-semibold">₹${item.total_price.toFixed(2)}</td>
            <td>
                <button type="button" onclick="removeBillingItem(${item.id})" 
                        class="text-red-600 hover:text-red-800">
                    <i class="fas fa-trash"></i>
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
    // Calculate subtotal
    const subtotal = billingItems.reduce((sum, item) => sum + item.total_price, 0);

    // Get discount
    const discount = parseFloat(document.getElementById('discount-amount').value) || 0;

    // Calculate taxable amount
    const taxableAmount = subtotal - discount;

    // Calculate tax (18% GST)
    const taxAmount = taxableAmount * 0.18;

    // Calculate grand total
    const grandTotal = taxableAmount + taxAmount;

    // Update UI
    document.getElementById('summary-subtotal').textContent = `₹${subtotal.toFixed(2)}`;
    document.getElementById('summary-taxable').textContent = `₹${taxableAmount.toFixed(2)}`;
    document.getElementById('summary-tax').textContent = `₹${taxAmount.toFixed(2)}`;
    document.getElementById('summary-grand-total').textContent = `₹${grandTotal.toFixed(2)}`;

    // Set amount paid to grand total by default
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
    $.ajax({
        url: '../controler/api/OpdBillingController.php?action=create',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function (response) {
            if (response.status === 'success') {
                alert('Bill created successfully!\nBill ID: ' + response.data.bill_id);
                toggleBillingForm();
                loadBills();
                loadStatistics();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function (xhr) {
            console.error('Error creating bill:', xhr);
            alert('Failed to create bill. Please try again.');
        }
    });
}

/**
 * Load billing statistics
 */
function loadStatistics() {
    $.ajax({
        url: '../controler/api/OpdBillingController.php?action=stats',
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

    let url = '../controler/api/OpdBillingController.php?action=getAll';
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
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${bill.bill_id}</td>
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
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <button onclick="viewBill('${bill.bill_id}')" class="text-blue-600 hover:text-blue-800 mr-3" title="View">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button onclick="printBill('${bill.bill_id}')" class="text-green-600 hover:text-green-800" title="Print">
                        <i class="fas fa-print"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

/**
 * View bill details
 */
function viewBill(billId) {
    window.open(`bill_details.php?bill_id=${billId}`, '_blank');
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

    // Load appropriate data
    if (tab === 'opd') {
        loadBills();
    } else if (tab === 'ipd') {
        // Load IPD bills
        alert('IPD billing coming soon!');
    } else if (tab === 'payments') {
        // Load payments
        alert('Payments view coming soon!');
    } else if (tab === 'reports') {
        // Load reports
        alert('Reports coming soon!');
    }
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
