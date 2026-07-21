<?php
// Extend session to 8 hours for full-shift use
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.cookie_lifetime', 28800);
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
require_once 'includes/db.php';
$pageTitle = 'Sales History';
include 'includes/ph_head.php';
?>
<style>
/* Compress table to prevent horizontal scrolling */
.ph-table th, .ph-table td {
    padding: 0.5rem 0.5rem !important;
    font-size: 0.78rem !important;
}
.ph-table th {
    font-size: 0.65rem !important;
}
.ph-table-wrap {
    overflow-x: hidden !important; /* Hide scrollbar if it just barely overshoots */
}
.actions-cell {
    white-space: nowrap;
    width: 80px;
}
</style>
<div class="ph-wrap">
<?php include 'includes/pharmacy_sidebar.php'; ?>
<div id="ph-content">
<?php include 'includes/pharmacy_navbar.php'; ?>
<div class="ph-page-body">

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="ph-page-title">Sales History</h1>
    <p class="ph-page-subtitle">All invoices and sales records</p>
  </div>
  <a href="billing_pos.php" class="ph-btn ph-btn-primary"><i class="fas fa-cash-register"></i> New Sale</a>
</div>

<!-- Filters -->
<div class="ph-searchbar">
  <div class="ph-search-input-wrap"><i class="fas fa-search"></i>
    <input type="text" id="searchInput" placeholder="Search invoice no, customer name...">
  </div>
  <input type="date" class="ph-input" id="dateFrom" style="width:auto;" value="<?= date('Y-m-01') ?>">
  <input type="date" class="ph-input" id="dateTo"   style="width:auto;" value="<?= date('Y-m-d') ?>">
  <select class="ph-select" id="pharmacistFilter" style="width:140px; padding:.55rem;">
    <option value="">All Pharmacists</option>
  </select>
  <select class="ph-select" id="payFilter" style="width:140px; padding:.55rem;">
    <option value="">All Payments</option>
    <option value="cash">Cash</option>
    <option value="upi">UPI</option>
    <option value="offered_plan">Offered Plan</option>
    <option value="card">Card</option>
    <option value="dd">DD</option>
    <option value="credit">Credit (Sponsor)</option>
  </select>
  <button class="ph-btn ph-btn-primary" onclick="load()"><i class="fas fa-filter"></i> Filter</button>
</div>

<!-- Summary Stats -->
<div class="ph-stat-grid mb-4" id="statRow" style="grid-template-columns: repeat(4, 1fr); gap: 1rem;"></div>

<!-- Sales Table -->
<div class="ph-card">
  <div class="ph-table-wrap">
    <table class="ph-table">
      <thead>
        <tr>
          <th>Invoice No</th>
          <th>Date & Time</th>
          <th>Customer</th>
          <th>Items</th>
          <th>Subtotal</th>
          <th>Discount</th>
          <th>Tax</th>
          <th>Grand Total</th>
          <th>Payment</th>
          <th>Pharmacist</th>
          <th>Status</th>
          <th class="text-end actions-cell">Actions</th>
        </tr>
      </thead>
      <tbody id="tableBody">
        <tr><td colspan="11" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="ph-card-body pt-0 pb-3">
    <div id="pager" class="ph-pagination justify-content-end"></div>
  </div>
</div>

</div></div></div>

<!-- Sale Detail Modal -->
<style>
  .compact-modal .ph-label { font-size: 0.65rem; font-weight: 800; color: #1F6B4A; margin-bottom: 2px; text-transform: uppercase; }
  .compact-modal h6 { font-size: 0.8rem; margin-top: 4px; margin-bottom: 8px !important; color: #1F6B4A; font-weight: 800; border-bottom: 1px solid rgba(31,107,74,0.1); padding-bottom: 4px; }
  .compact-modal .modal-body { padding: 12px 20px; }
  .compact-modal .modal-header, .compact-modal .modal-footer { padding: 10px 20px; }
  .compact-modal .grid-3-cols { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 12px; }
  .compact-modal .grid-2-cols { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 12px; }
  .compact-modal .grid-split { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
  .compact-modal .grid-item { display: flex; flex-direction: column; }
  .compact-modal .grid-item-full { grid-column: 1 / -1; display: flex; flex-direction: column; }
</style>
<div class="modal fade compact-modal" id="detailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 900px;">
    <div class="modal-content" style="background: #F3EFE6; border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
      <div class="modal-header" style="border-bottom: 1px solid rgba(31,107,74,0.15);">
        <h5 class="modal-title" id="detailTitle" style="color: #1F6B4A; font-weight: 900; letter-spacing: -0.5px;">Invoice Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(34%) sepia(16%) saturate(1637%) hue-rotate(107deg) brightness(97%) contrast(89%); opacity: 0.8;"></button>
      </div>
      <div class="modal-body" id="detailBody">Loading...</div>
      <div class="modal-footer" style="background: #F3EFE6; border-top: 1px solid rgba(31,107,74,0.15); border-radius: 0 0 12px 12px;">
        <button class="btn btn-sm" data-bs-dismiss="modal" style="background: transparent; color: #1F6B4A; border: 1.5px solid rgba(31,107,74,0.2); border-radius: 8px; font-weight: 700;">Close</button>
        <button class="btn btn-sm" onclick="reprintInvoice()" style="background: #1F6B4A; color: #FFFFFF; border: none; border-radius: 8px; font-weight: 700; box-shadow: 0 4px 10px rgba(31,107,74,0.2);">
          <i class="fas fa-print me-1"></i> Reprint
        </button>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/ph_foot.php'; ?>
<script>
let all = [], currentPage = 1, PER_PAGE = 15, currentSaleId = null;
const detailModal = new bootstrap.Modal(document.getElementById('detailModal'));

document.addEventListener('DOMContentLoaded', () => {
    try {
        if (typeof phGet !== 'function') {
            console.error('phGet not defined');
            return;
        }
        load();
        const s = document.getElementById('searchInput');
        const p = document.getElementById('payFilter');
        const pf = document.getElementById('pharmacistFilter');
        if (s) s.addEventListener('input', () => { currentPage = 1; render(); });
        if (p) p.addEventListener('change', () => { currentPage = 1; render(); });
        if (pf) pf.addEventListener('change', () => { currentPage = 1; load(); });
    } catch (e) { console.error('Init error:', e); }
});

async function load() {
    try {
        const dateFrom = document.getElementById('dateFrom').value;
        const dateTo   = document.getElementById('dateTo').value;
        const pharmacist = document.getElementById('pharmacistFilter').value;
        const res = await phGet(API_BASE + `pharmacy/sales?date_from=${dateFrom}&date_to=${dateTo}&pharmacist=${pharmacist}`);
        if (res.success) { 
            all = res.data.data || []; 
            
            
            // Populate Pharmacist Dropdown dynamically
            if (res.data.pharmacists) {
                const pSelect = document.getElementById('pharmacistFilter');
                const currVal = pSelect.value;
                pSelect.innerHTML = '<option value="">All Pharmacists</option>' + 
                    res.data.pharmacists.map(p => `<option value="${p.created_by}" ${p.created_by===currVal?'selected':''}>${p.created_by}</option>`).join('');
            }
            
            render(); 
        } else {
            PH.error(res.message || res.error || "Failed to load sales");
        }
    } catch (e) {
        console.error('Load error:', e);
        PH.error("Network or server error: " + e.message);
    }
}

function renderStats(s) {
    const data = [
        { label:'Total Sales', val: fmt.currency(s.total_sales), icon:'fa-rupee-sign', color:'#1f6b4a' },
        { label:'Total Bills',  val: fmt.number(s.total_bills),  icon:'fa-receipt',    color:'#22C55E' },
        { label:'Total Tax',    val: fmt.currency(s.total_tax),  icon:'fa-percent',    color:'#F59E0B' },
        { label:'Total Discount',val:fmt.currency(s.total_disc), icon:'fa-tag',        color:'#8B5CF6' },
    ];
    document.getElementById('statRow').innerHTML = data.map(d => `
        <div class="ph-stat d-flex align-items-center" style="border-left:4px solid ${d.color}; padding: 1rem 1.25rem; gap: 1rem;">
            <div class="ph-stat-icon m-0" style="background:${d.color}20;color:${d.color};width:42px;height:42px;flex-shrink:0;"><i class="fas ${d.icon}"></i></div>
            <div class="flex-grow-1">
                <div class="ph-stat-val" style="font-size:1.5rem;margin-bottom:0;">${d.val}</div>
                <div class="ph-stat-lbl" style="font-size:0.7rem;margin-top:0;">${d.label}</div>
            </div>
        </div>`).join('');
}

function render() {
    const q  = document.getElementById('searchInput').value.toLowerCase();
    const pf = document.getElementById('payFilter').value;
    let filtered = all;
    if (q)  filtered = filtered.filter(x => (x.invoice_no||'').toLowerCase().includes(q) || (x.customer_name||'').toLowerCase().includes(q));
    if (pf) filtered = filtered.filter(x => x.payment_method === pf);
    
    const dStats = {
        total_bills: filtered.length,
        total_sales: filtered.reduce((acc, cur) => acc + parseFloat(cur.grand_total || 0), 0),
        total_tax: filtered.reduce((acc, cur) => acc + parseFloat(cur.tax_total || 0), 0),
        total_disc: filtered.reduce((acc, cur) => acc + parseFloat(cur.discount_amount || 0), 0),
    };
    renderStats(dStats);
    
    const pager = phPaginate(filtered, currentPage, PER_PAGE);
    let html = '';
    if (!pager.items.length) { html = `<tr><td colspan="11" class="text-center py-4 text-muted">No sales records found for the selected period.</td></tr>`; }
    else pager.items.forEach(x => {
        const payIcon = {cash:'💵', card:'💳', upi:'📱', credit:'📋', offered_plan:'📋', dd:'🏦'}[x.payment_method] || '';
        html += `<tr>
            <td><span class="ph-badge badge-primary">${x.invoice_no}</span></td>
            <td><div>${fmt.date(x.invoice_date)}</div><div class="fs-xs text-muted">${x.invoice_time||''}</div></td>
            <td>${x.customer_name||'Walk-in'}</td>
            <td class="fw-bold text-center">${x.item_count||0}</td>
            <td>${fmt.currency(x.subtotal)}</td>
            <td class="text-danger">-${fmt.currency(x.discount_amount)}</td>
            <td>${fmt.currency(x.tax_total)}</td>
            <td class="fw-bold text-primary">${fmt.currency(x.grand_total)}</td>
            <td>${payIcon} ${x.payment_method}</td>
            <td>${x.created_by || ''}</td>
            <td>${statusBadge(x.status)}</td>
            <td class="text-end actions-cell">
                <button class="ph-btn ph-btn-sm ph-btn-outline ph-btn-icon me-1" onclick="viewSale(${x.id})" title="View"><i class="fas fa-eye"></i></button>
                <button class="ph-btn ph-btn-sm ph-btn-outline ph-btn-icon" onclick="printSale(${x.id})" title="Print Invoice"><i class="fas fa-print"></i></button>
            </td></tr>`;
    });
    document.getElementById('tableBody').innerHTML = html;
    phRenderPager(document.getElementById('pager'), pager.pages, currentPage, p => { currentPage = p; render(); });
}

async function viewSale(id) {
    currentSaleId = id;
    document.getElementById('detailBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
    detailModal.show();
    const res = await phGet(API_BASE + 'pharmacy/sales/' + id);
    if (!res.success) { document.getElementById('detailBody').innerHTML = '<p class="text-danger">Error loading details</p>'; return; }
    const s = res.data.sale, items = res.data.items;
    document.getElementById('detailTitle').innerHTML = `<i class="fas fa-file-invoice me-2"></i>Invoice &mdash; ${s.invoice_no}`;
    let itemsHtml = items.map(i => `<tr style="border-bottom: 1px solid rgba(31,107,74,0.1);">
        <td style="color:#1F6B4A; font-weight:600;">${i.product_name}</td>
        <td style="color:#1F6B4A;">${i.batch_no||'—'}</td>
        <td style="color:#1F6B4A;">${i.qty}</td>
        <td style="color:#1F6B4A;">${fmt.currency(i.rate)}</td>
        <td style="color:#1F6B4A;">${i.discount_percent}%</td>
        <td style="color:#1F6B4A;">${i.tax_percent}%</td>
        <td style="color:#1F6B4A; font-weight:700;">${fmt.currency(i.subtotal)}</td></tr>`).join('');
    document.getElementById('detailBody').innerHTML = `
        <div class="mb-3">
            <div class="p-3" style="background:#FFFFFF; border:1px solid rgba(31,107,74,0.15); border-radius:12px;">
                <div class="grid-3-cols" style="margin-bottom:0;">
                    <div class="grid-item"><div style="font-size:0.7rem; font-weight:700; color:#1F6B4A; text-transform:uppercase; margin-bottom:4px;">Invoice No</div><strong style="color:#1F6B4A; font-size:1.1rem;">${s.invoice_no}</strong></div>
                    <div class="grid-item"><div style="font-size:0.7rem; font-weight:700; color:#1F6B4A; text-transform:uppercase; margin-bottom:4px;">Date</div><span style="color:#1F6B4A; font-weight:600;">${fmt.date(s.invoice_date)}</span></div>
                    <div class="grid-item"><div style="font-size:0.7rem; font-weight:700; color:#1F6B4A; text-transform:uppercase; margin-bottom:4px;">Customer</div><span style="color:#1F6B4A; font-weight:600;">${s.customer_name}</span></div>
                    <div class="grid-item"><div style="font-size:0.7rem; font-weight:700; color:#1F6B4A; text-transform:uppercase; margin-bottom:4px;">Phone</div><span style="color:#1F6B4A; font-weight:600;">${s.customer_phone||'—'}</span></div>
                    <div class="grid-item"><div style="font-size:0.7rem; font-weight:700; color:#1F6B4A; text-transform:uppercase; margin-bottom:4px;">Payment</div><span style="color:#1F6B4A; font-weight:600;">${s.payment_method === 'split' ? '<span style="color:#1F6B4A;font-weight:800;">SPLIT</span>' : s.payment_method}</span></div>
                    <div class="grid-item"><div style="font-size:0.7rem; font-weight:700; color:#1F6B4A; text-transform:uppercase; margin-bottom:4px;">Status</div>${statusBadge(s.status)}</div>
                </div>
            </div>
        </div>
        <div class="table-responsive mb-4" style="background:#FFFFFF; border-radius:12px; border:1px solid rgba(31,107,74,0.15); overflow:hidden;">
            <table class="table mb-0">
                <thead style="background:rgba(31,107,74,0.05);">
                    <tr>
                        <th style="color:#1F6B4A; font-size:0.75rem; font-weight:800; text-transform:uppercase; border-bottom:1px solid rgba(31,107,74,0.1);">Product</th>
                        <th style="color:#1F6B4A; font-size:0.75rem; font-weight:800; text-transform:uppercase; border-bottom:1px solid rgba(31,107,74,0.1);">Batch</th>
                        <th style="color:#1F6B4A; font-size:0.75rem; font-weight:800; text-transform:uppercase; border-bottom:1px solid rgba(31,107,74,0.1);">Qty</th>
                        <th style="color:#1F6B4A; font-size:0.75rem; font-weight:800; text-transform:uppercase; border-bottom:1px solid rgba(31,107,74,0.1);">Rate</th>
                        <th style="color:#1F6B4A; font-size:0.75rem; font-weight:800; text-transform:uppercase; border-bottom:1px solid rgba(31,107,74,0.1);">Disc%</th>
                        <th style="color:#1F6B4A; font-size:0.75rem; font-weight:800; text-transform:uppercase; border-bottom:1px solid rgba(31,107,74,0.1);">Tax%</th>
                        <th style="color:#1F6B4A; font-size:0.75rem; font-weight:800; text-transform:uppercase; border-bottom:1px solid rgba(31,107,74,0.1);">Subtotal</th>
                    </tr>
                </thead>
                <tbody>${itemsHtml}</tbody>
            </table>
        </div>
        <div class="grid-split mt-2">
            <div class="grid-item">
                ${s.payment_method === 'split' && s.split_payments ? `
                    <div class="p-3" style="background:#FFFFFF; border-radius:12px; border:1px solid rgba(31,107,74,0.2);">
                        <div style="font-weight:800; font-size:0.85rem; text-transform:uppercase; color:#1F6B4A; margin-bottom:12px; border-bottom:1px solid rgba(31,107,74,0.1); padding-bottom:6px;">Split Breakdown</div>
                        ${s.split_payments.map(p => `
                            <div style="display:flex; justify-content:space-between; font-size:0.95rem; padding:6px 0; border-bottom:1px dashed rgba(31,107,74,0.1);">
                                <span style="text-transform:capitalize; font-weight:700; color:#1F6B4A;">${p.payment_method}</span>
                                <span style="font-weight:800; color:#1F6B4A;">${fmt.currency(p.amount)}</span>
                            </div>
                        `).join('')}
                    </div>
                ` : ''}
            </div>
            <div class="grid-item">
                <div class="p-3" style="background:#1F6B4A; border-radius:12px; color:#F3EFE6;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-weight:600;"><span>Subtotal</span><span>${fmt.currency(s.subtotal)}</span></div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-weight:600;"><span style="color:#FFCDCD;">Discount</span><span style="color:#FFCDCD;">-${fmt.currency(s.discount_amount)}</span></div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:12px; font-weight:600; border-bottom:1px solid rgba(243,239,230,0.2); padding-bottom:12px;"><span>Tax</span><span>${fmt.currency(s.tax_total)}</span></div>
                    <div style="display:flex; justify-content:space-between; font-size:1.3rem; font-weight:900;"><span>Grand Total</span><span>${fmt.currency(s.grand_total)}</span></div>
                </div>
            </div>
        </div>`;
}

async function printSale(id) {
    PH.loading('Generating invoice...');
    phGet(API_BASE + `pharmacy/sales/${id}/reprint`).then(res => {
        PH.close();
        if (res.success) printInvoice(res.data.html); else PH.error('Failed to generate invoice');
    }).catch(e => {
        PH.close();
        PH.error('Network error');
    });
}

async function reprintInvoice() {
    if (!currentSaleId) return;
    printSale(currentSaleId);
}

function printInvoice(html) {
    const w = window.open('', '_blank', 'width=900,height=800');
    if (!w) {
        PH.error('Popup blocked! Please allow popups for this site.');
        return;
    }
    w.document.write(html);
    w.document.close();
}
</script>
