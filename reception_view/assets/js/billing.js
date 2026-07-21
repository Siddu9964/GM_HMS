/**
 * Billing Management Logic
 */

document.addEventListener('DOMContentLoaded', () => {
    loadStats();
    loadInvoices();

    // Initialize Select2
    initializeSelect2();

    // New Invoice Button Toggle
    window.toggleNewInvoice = () => {
        const form = document.getElementById('new-invoice-section');
        form.classList.toggle('hidden');
    };

    // Form Submit
    document.getElementById('create-invoice-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        // Since Select2 uses native select value, FormData will capture it automatically
        await createGlobalInvoice(new FormData(e.target));
    });

    // Search Invoices
    document.getElementById('invoice-search').addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#invoice-table-body tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    });
});

const API_BILLING = '../controler/api/BillingController.php';

function initializeSelect2() {
    // Patient Search
    $('#patient-search-select').select2({
        ajax: {
            url: `${API_BILLING}/api/billing/search-patients`,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { term: params.term };
            },
            processResults: function (data) {
                return { results: data.data.results };
            },
            cache: true
        },
        placeholder: 'Search by Name or ID',
        minimumInputLength: 1
    });

    // Doctor Search
    $('#doctor-search-select').select2({
        ajax: {
            url: `${API_BILLING}/api/billing/search-doctors`,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { term: params.term };
            },
            processResults: function (data) {
                return { results: data.data.results };
            },
            cache: true
        },
        placeholder: 'Search Doctor',
        minimumInputLength: 0
    });
}

async function loadStats() {
    try {
        const res = await fetch(`${API_BILLING}/api/billing/stats`);
        const json = await res.json();

        if (json.success) {
            document.getElementById('stat-today').textContent = formatCurrency(json.data.today_revenue || 0);
            document.getElementById('stat-month').textContent = formatCurrency(json.data.month_revenue || 0);
            document.getElementById('stat-pending').textContent = json.data.pending_bills || 0;
        }
    } catch (e) {
        console.error('Stats error', e);
    }
}

async function loadInvoices() {
    const table = document.getElementById('invoice-table-body');
    table.innerHTML = '<tr><td colspan="7" class="text-center p-4">Loading...</td></tr>';

    try {
        const res = await fetch(`${API_BILLING}/api/billing/invoices`);
        const json = await res.json();

        if (json.success && json.data.length > 0) {
            table.innerHTML = json.data.map(inv => `
                <tr>
                    <td><span class="font-mono text-sm">${inv.invoice_id}</span></td>
                    <td>${inv.first_name ? `${inv.first_name} ${inv.last_name}` : `<span class="text-gray-400">Unknown (${inv.patient_id})</span>`}</td>
                    <td>${inv.title}</td>
                    <td class="font-weight-bold">${formatCurrency(inv.amount)}</td>
                    <td>${inv.date}</td>
                    <td>
                        <span class="badge-${inv.status.toLowerCase()}">${inv.status}</span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="printInvoice('${inv.invoice_id}', '${inv.first_name}', '${inv.amount}')">
                            <i class="fas fa-print"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        } else {
            table.innerHTML = '<tr><td colspan="7" class="text-center p-4 text-gray-500">No invoices found.</td></tr>';
        }

    } catch (e) {
        console.error('Invoice load error', e);
        table.innerHTML = '<tr><td colspan="7" class="text-center p-4 text-red-500">Failed to load data.</td></tr>';
    }
}

async function createGlobalInvoice(formData) {
    const data = Object.fromEntries(formData.entries());

    try {
        const res = await fetch(`${API_BILLING}/api/billing/create`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();

        if (json.success) {
            showToast('Invoice Created Successfully', 'success');
            document.getElementById('create-invoice-form').reset();
            toggleNewInvoice();
            loadStats();
            loadInvoices();
        } else {
            showToast(json.message || 'Failed to create invoice', 'error');
        }
    } catch (e) {
        showToast('Network Error', 'error');
    }
}

function printInvoice(id, name, amount) {
    // Simple print stub
    const win = window.open('', '_blank');
    win.document.write(`
        <html><body style="font-family: sans-serif; padding: 2rem; text-align: center;">
            <h1>GM HOSPITAL</h1>
            <h2>INVOICE</h2>
            <p>ID: ${id}</p>
            <p>Patient: ${name}</p>
            <p>Amount: ₹${amount}</p>
            <p>Status: PAID</p>
            <script>window.print();</script>
        </body></html>
    `);
    win.document.close();
}

function formatCurrency(amount) {
    return '₹' + parseFloat(amount).toFixed(2);
}
