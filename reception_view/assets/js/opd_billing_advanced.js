/**
 * OPD Advanced Billing JavaScript
 * Handles itemized billing, calculations, and API interactions
 */

// Global variables
let billingItems = [];
let itemCounter = 0;
const TAX_RATE = 18; // 18% GST

// API endpoints
const API_BASE = '../controler/api';
const API_OPD_BILLING = `${API_BASE}/OpdBillingController.php`;
const API_BILLING = `${API_BASE}/BillingController.php`;

// =====================================================
// INITIALIZATION
// =====================================================
document.addEventListener('DOMContentLoaded', () => {
    initializeSelect2();
    loadStatistics();
    loadBills();
    setupEventListeners();

    // Add first billing item by default
    addBillingItem();
});

// =====================================================
// SELECT2 INITIALIZATION
// =====================================================
function initializeSelect2() {
    // Patient Search
    $('#patient-select').select2({
        ajax: {
            url: `${API_BILLING}/api/billing/search-patients`,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { term: params.term };
            },
            processResults: function (data) {
                if (data.success) {
                    return { results: data.data.results };
                }
                return { results: [] };
            },
            cache: true
        },
        placeholder: 'Search patient by name or ID...',
        minimumInputLength: 2,
        width: '100%'
    });

    // Doctor Search
    $('#doctor-select').select2({
        ajax: {
            url: `${API_BILLING}/api/billing/search-doctors`,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { term: params.term };
            },
            processResults: function (data) {
                if (data.success) {
                    return { results: data.data.results };
                }
                return { results: [] };
            },
            cache: true
        },
        placeholder: 'Search doctor...',
        minimumInputLength: 0,
        width: '100%',
        allowClear: true
    });

    // Patient selection change event
    $('#patient-select').on('change', function () {
        const selectedData = $(this).select2('data')[0];
        if (selectedData) {
            displayPatientInfo(selectedData);
        }
    });
}

// =====================================================
// EVENT LISTENERS
// =====================================================
function setupEventListeners() {
    // Form submission
    document.getElementById('opd-billing-form').addEventListener('submit', handleFormSubmit);

    // Search bills
    document.getElementById('search-bills').addEventListener('input', filterBills);
}

// =====================================================
// PATIENT INFO DISPLAY
// =====================================================
function displayPatientInfo(patient) {
    const infoCard = document.getElementById('patient-info');
    document.getElementById('info-patient-id').textContent = patient.patient_id || patient.id;
    document.getElementById('info-age-sex').textContent = `${patient.age || '-'} / ${patient.sex || '-'}`;
    document.getElementById('info-phone').textContent = patient.phone || '-';
    infoCard.classList.remove('hidden');
}

// =====================================================
// BILLING ITEMS MANAGEMENT
// =====================================================
function addBillingItem() {
    itemCounter++;
    const tbody = document.getElementById('billing-items-tbody');

    const row = document.createElement('tr');
    row.id = `item-row-${itemCounter}`;
    row.innerHTML = `
        <td>
            <select class="item-type" data-item-id="${itemCounter}" onchange="handleItemTypeChange(${itemCounter})">
                <option value="">Select Type</option>
                <option value="Consultation">Consultation</option>
                <option value="Investigation">Investigation</option>
                <option value="Procedure">Procedure</option>
                <option value="Medication">Medication</option>
                <option value="Other">Other</option>
                <option value="Emergency">Emergency</option>
                <option value="Registration Fee">Registration Fee</option>
            </select>
        </td>
        <td>
            <input type="text" class="item-name" data-item-id="${itemCounter}" 
                   placeholder="Service/Item name" list="service-list-${itemCounter}">
            <datalist id="service-list-${itemCounter}"></datalist>
        </td>
        <td>
            <input type="number" class="item-qty" data-item-id="${itemCounter}" 
                   value="1" min="1" step="1" onchange="calculateItemTotal(${itemCounter})">
        </td>
        <td>
            <input type="number" class="item-rate" data-item-id="${itemCounter}" 
                   placeholder="0.00" min="0" step="0.01" onchange="calculateItemTotal(${itemCounter})">
        </td>
        <td>
            <span class="item-amount" id="item-amount-${itemCounter}">₹0.00</span>
        </td>
        <td>
            <button type="button" class="btn-remove-item" onclick="removeItem(${itemCounter})">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(row);

    // Load service catalog for this item type
    loadServiceCatalog(itemCounter);
}

function removeItem(itemId) {
    const row = document.getElementById(`item-row-${itemId}`);
    if (row) {
        row.remove();
        calculateTotals();
    }
}

function handleItemTypeChange(itemId) {
    loadServiceCatalog(itemId);
}

async function loadServiceCatalog(itemId) {
    const typeSelect = document.querySelector(`.item-type[data-item-id="${itemId}"]`);
    const itemType = typeSelect.value;

    if (!itemType) return;

    try {
        const response = await fetch(`${API_BASE}/ServiceCatalogController.php?action=getByCategory&category=${itemType}`);
        const data = await response.json();

        if (data.success && data.data) {
            const datalist = document.getElementById(`service-list-${itemId}`);
            datalist.innerHTML = '';

            data.data.forEach(service => {
                const option = document.createElement('option');
                option.value = service.service_name;
                option.setAttribute('data-price', service.unit_price);
                option.setAttribute('data-code', service.service_code);
                datalist.appendChild(option);
            });

            // Auto-fill price when service is selected
            const nameInput = document.querySelector(`.item-name[data-item-id="${itemId}"]`);
            nameInput.addEventListener('change', function () {
                const selectedOption = datalist.querySelector(`option[value="${this.value}"]`);
                if (selectedOption) {
                    const price = selectedOption.getAttribute('data-price');
                    const rateInput = document.querySelector(`.item-rate[data-item-id="${itemId}"]`);
                    rateInput.value = price;
                    calculateItemTotal(itemId);
                }
            });
        }
    } catch (error) {
        console.error('Failed to load service catalog:', error);
    }
}

function calculateItemTotal(itemId) {
    const qtyInput = document.querySelector(`.item-qty[data-item-id="${itemId}"]`);
    const rateInput = document.querySelector(`.item-rate[data-item-id="${itemId}"]`);
    const amountSpan = document.getElementById(`item-amount-${itemId}`);

    const qty = parseFloat(qtyInput.value) || 0;
    const rate = parseFloat(rateInput.value) || 0;
    const total = qty * rate;

    amountSpan.textContent = formatCurrency(total);

    calculateTotals();
}

// =====================================================
// CALCULATIONS
// =====================================================
function calculateTotals() {
    let subtotal = 0;

    // Sum all item amounts
    document.querySelectorAll('.item-amount').forEach(span => {
        const amount = parseFloat(span.textContent.replace('₹', '').replace(',', '')) || 0;
        subtotal += amount;
    });

    // Get discount
    const discountAmount = parseFloat(document.getElementById('discount-amount').value) || 0;

    // Calculate taxable amount
    const taxableAmount = subtotal - discountAmount;

    // Calculate tax
    const taxAmount = (taxableAmount * TAX_RATE) / 100;

    // Calculate grand total
    const grandTotal = taxableAmount + taxAmount;

    // Update summary
    document.getElementById('summary-subtotal').textContent = formatCurrency(subtotal);
    document.getElementById('summary-taxable').textContent = formatCurrency(taxableAmount);
    document.getElementById('summary-tax').textContent = formatCurrency(taxAmount);
    document.getElementById('summary-grand-total').textContent = formatCurrency(grandTotal);

    // Auto-fill amount paid
    document.getElementById('amount-paid').value = grandTotal.toFixed(2);
}

// =====================================================
// FORM SUBMISSION
// =====================================================
async function handleFormSubmit(e) {
    e.preventDefault();

    // Validate form
    if (!validateForm()) {
        return;
    }

    // Collect form data
    const formData = collectFormData();

    try {
        showLoading('Generating bill...');

        const response = await fetch(`${API_OPD_BILLING}?action=create`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const result = await response.json();

        hideLoading();

        if (result.success) {
            showToast('Bill generated successfully!', 'success');

            // Ask if user wants to print
            if (confirm('Bill created successfully! Do you want to print the bill?')) {
                printBill(result.data.bill_id);
            }

            // Reset form
            resetForm();

            // Reload data
            loadStatistics();
            loadBills();
        } else {
            showToast(result.message || 'Failed to generate bill', 'error');
        }
    } catch (error) {
        hideLoading();
        console.error('Error:', error);
        showToast('Network error. Please try again.', 'error');
    }
}

function validateForm() {
    const patientId = document.getElementById('patient-select').value;
    if (!patientId) {
        showToast('Please select a patient', 'error');
        return false;
    }

    // Check if at least one item exists
    const items = collectBillingItems();
    if (items.length === 0) {
        showToast('Please add at least one billing item', 'error');
        return false;
    }

    // Validate all items have required fields
    for (let item of items) {
        if (!item.item_type || !item.item_name || !item.unit_price) {
            showToast('Please fill all item details', 'error');
            return false;
        }
    }

    return true;
}

function collectFormData() {
    const form = document.getElementById('opd-billing-form');
    const formData = new FormData(form);

    const data = {
        patient_id: formData.get('patient_id'),
        doctor_id: formData.get('doctor_id') || null,
        bill_date: new Date().toISOString().split('T')[0],
        bill_time: new Date().toTimeString().split(' ')[0],
        items: collectBillingItems(),
        discount_amount: parseFloat(formData.get('discount_amount')) || 0,
        payment: {
            payment_method: formData.get('payment_method'),
            amount: parseFloat(formData.get('amount_paid')),
            payment_date: new Date().toISOString().split('T')[0],
            payment_time: new Date().toTimeString().split(' ')[0],
            notes: formData.get('notes')
        }
    };

    return data;
}

function collectBillingItems() {
    const items = [];

    document.querySelectorAll('#billing-items-tbody tr').forEach(row => {
        const itemId = row.id.split('-')[2];

        const typeSelect = row.querySelector('.item-type');
        const nameInput = row.querySelector('.item-name');
        const qtyInput = row.querySelector('.item-qty');
        const rateInput = row.querySelector('.item-rate');

        if (typeSelect && nameInput && qtyInput && rateInput) {
            const itemType = typeSelect.value;
            const itemName = nameInput.value;
            const qty = parseFloat(qtyInput.value) || 0;
            const rate = parseFloat(rateInput.value) || 0;

            if (itemType && itemName && rate > 0) {
                items.push({
                    item_type: itemType,
                    item_name: itemName,
                    quantity: qty,
                    unit_price: rate,
                    is_taxable: true,
                    tax_percentage: TAX_RATE
                });
            }
        }
    });

    return items;
}

function resetForm() {
    document.getElementById('opd-billing-form').reset();
    $('#patient-select').val(null).trigger('change');
    $('#doctor-select').val(null).trigger('change');
    document.getElementById('patient-info').classList.add('hidden');
    document.getElementById('billing-items-tbody').innerHTML = '';
    itemCounter = 0;
    addBillingItem();
    calculateTotals();
    toggleBillingForm();
}

// =====================================================
// LOAD STATISTICS
// =====================================================
async function loadStatistics() {
    try {
        const response = await fetch(`${API_OPD_BILLING}?action=stats`);
        const data = await response.json();

        if (data.success) {
            const stats = data.data;
            document.getElementById('stat-today-revenue').textContent = formatCurrency(stats.today_revenue || 0);
            document.getElementById('stat-month-revenue').textContent = formatCurrency(stats.month_revenue || 0);
            document.getElementById('stat-pending-bills').textContent = stats.pending_bills || 0;
            document.getElementById('stat-outstanding').textContent = formatCurrency(stats.outstanding_amount || 0);
        }
    } catch (error) {
        console.error('Failed to load statistics:', error);
    }
}

// =====================================================
// LOAD BILLS
// =====================================================
async function loadBills() {
    const tbody = document.getElementById('bills-tbody');
    tbody.innerHTML = '<tr><td colspan="9" class="text-center">Loading...</td></tr>';

    try {
        const status = document.getElementById('filter-status').value;
        let url = `${API_OPD_BILLING}?action=getAll`;
        if (status) {
            url += `&payment_status=${status}`;
        }

        const response = await fetch(url);
        const data = await response.json();

        if (data.success && data.data.length > 0) {
            tbody.innerHTML = data.data.map(bill => `
                <tr>
                    <td><span class="font-mono">${bill.bill_id}</span></td>
                    <td><span class="font-bold" style="color:var(--primary);">${bill.receipt_no || '—'}</span></td>
                    <td>${bill.patient_name || 'Unknown'}</td>
                    <td>${bill.doctor_name || '-'}</td>
                    <td>${formatDate(bill.bill_date)}</td>
                    <td class="font-bold">${formatCurrency(bill.grand_total)}</td>
                    <td>${formatCurrency(bill.amount_paid)}</td>
                    <td>${formatCurrency(bill.balance_due)}</td>
                    <td><span class="badge badge-${bill.payment_status.toLowerCase()}">${bill.payment_status}</span></td>
                    <td>
                        <button class="btn btn-sm btn-icon" onclick="viewBill('${bill.bill_id}')" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-icon" onclick="printBill('${bill.bill_id}')" title="Print">
                            <i class="fas fa-print"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center">No bills found</td></tr>';
        }
    } catch (error) {
        console.error('Failed to load bills:', error);
        tbody.innerHTML = '<tr><td colspan="9" class="text-center">Error loading bills</td></tr>';
    }
}

function filterBills() {
    const searchTerm = document.getElementById('search-bills').value.toLowerCase();
    const rows = document.querySelectorAll('#bills-tbody tr');

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
}

// =====================================================
// BILL ACTIONS
// =====================================================
function viewBill(billId) {
    window.location.href = `view_opd_bill.php?bill_id=${billId}`;
}

function printBill(billId) {
    window.open(`print_opd_invoice.php?bill_id=${billId}`, '_blank', 'width=800,height=600');
}

// =====================================================
// UI HELPERS
// =====================================================
function toggleBillingForm() {
    const container = document.getElementById('billing-form-container');
    container.classList.toggle('hidden');
}

function formatCurrency(amount) {
    return '₹' + parseFloat(amount).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
}

function showLoading(message = 'Loading...') {
    // Implement loading overlay
    console.log(message);
}

function hideLoading() {
    // Hide loading overlay
}

function showToast(message, type = 'info') {
    // Use existing toast function or implement new one
    if (typeof window.showToast === 'function') {
        window.showToast(message, type);
    } else {
        alert(message);
    }
}
