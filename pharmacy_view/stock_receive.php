<?php
// Extend session to 8 hours for full-shift use
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.cookie_lifetime', 28800);
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
require_once 'includes/db.php';
$pageTitle = 'Stock Receive (GRN)';
$db = getDB();
$suppliers = $db->query("SELECT supplier_id, supplier_name, company_name FROM ph_suppliers WHERE status='active' ORDER BY company_name")->fetchAll();
$openPOs   = $db->query("SELECT po_no, supplier_name FROM ph_purchase_orders WHERE status='ordered' ORDER BY po_no DESC")->fetchAll();
$products  = $db->query("SELECT product_id, product_name, content, strength, form, therapeutic, hsn_code, manufacturer, mrp, tax_percent, purchase_rate, pack_rate, individual_rate, pack, unit, pack_size, min_stock, max_stock, rack_location FROM ph_product ORDER BY product_name")->fetchAll();
include 'includes/ph_head.php';
?>
<div class="ph-wrap">
<?php include 'includes/pharmacy_sidebar.php'; ?>
<div id="ph-content">
<?php include 'includes/pharmacy_navbar.php'; ?>
<div class="ph-page-body">

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="ph-page-title">Stock Receive / GRN</h1>
    <p class="ph-page-subtitle">Record goods received and auto-update inventory</p>
  </div>
  <button class="ph-btn ph-btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> New GRN</button>
</div>

<!-- Search -->
<div class="ph-searchbar">
  <div class="ph-search-input-wrap"><i class="fas fa-search"></i>
    <input type="text" id="searchInput" placeholder="Search GRN no, PO no, supplier...">
  </div>
  <button class="ph-btn ph-btn-outline" onclick="load()"><i class="fas fa-sync-alt"></i></button>
</div>

<style>
#bulkBar {
  animation: slideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}
@keyframes slideUp { 
  from { opacity: 0; transform: translate(-50%, 20px); } 
  to { opacity: 1; transform: translate(-50%, 0); } 
}
tr.selected {
  background-color: rgba(14, 165, 233, 0.05) !important;
}
tr.tr-submitted {
  opacity: 0.85;
  cursor: not-allowed;
}
tr.tr-submitted td {
  background-color: rgba(248, 250, 252, 0.6) !important;
}
</style>

<!-- GRN Table -->
<div class="ph-card">
  <div class="ph-table-wrap">
    <table class="ph-table">
      <thead>
        <tr>
          <th style="width:50px"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" style="width:20px; height:20px; accent-color: var(--ph-primary);"></th>
          <th style="width:40px"></th>
          <th>GRN No</th>
          <th>Date</th>
          <th>PO No</th>
          <th>Supplier</th>
          <th>Invoice No</th>
          <th>Total Qty</th>
          <th>Total Amount</th>
          <th>Status</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody id="tableBody">
        <tr><td colspan="10" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="ph-card-body pt-0 pb-3">
    <div id="pager" class="ph-pagination justify-content-end"></div>
  </div>
</div>

<!-- ── Bulk Action Bar ── -->
<div id="bulkBar" style="display:none; position: fixed; bottom: 2rem; left: 50%; transform: translateX(-50%); background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); padding: 1rem 2rem; border-radius: 24px; display: flex; align-items: center; gap: 1.5rem; z-index: 1000; box-shadow: 0 20px 50px rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1);">
  <span id="selectedCount" style="color:#fff; font-weight:700;"></span>
  <div style="display:flex; gap:10px;">
    <button class="ph-btn" style="background:#10B981;color:#fff;border-radius:12px;font-weight:700;padding:8px 18px;border:none;cursor:pointer;" onclick="bulkExport()">
      <i class="fas fa-file-csv me-2"></i>Export CSV
    </button>
    <button class="ph-btn" style="background:#0EA5E9;color:#fff;border-radius:12px;font-weight:700;padding:8px 18px;border:none;cursor:pointer;" onclick="bulkPrint()">
      <i class="fas fa-print me-2"></i>Print/Download
    </button>
    <button class="ph-btn" style="background:#009688;color:#fff;border-radius:12px;font-weight:700;padding:8px 18px;border:none;cursor:pointer;" onclick="bulkSubmitGRN()">
      <i class="fas fa-check-double me-2"></i>Submit GRN
    </button>
  </div>
</div>

</div></div></div>

<!-- GRN Modal -->
<style>
  .compact-modal .ph-label { font-size: 0.65rem; font-weight: 800; color: #1F6B4A; margin-bottom: 2px; text-transform: uppercase; }
  .compact-modal .ph-input, .compact-modal .ph-select, .compact-modal .ph-textarea { padding: 4px 8px; font-size: 0.8rem; height: 32px; border: 1px solid rgba(31,107,74,0.2); border-radius: 6px; background: #FFF; color: #1F6B4A; font-weight: 600; width: 100%; box-shadow: none; box-sizing: border-box; }
  .compact-modal .ph-textarea { height: auto; min-height: 48px; }
  .compact-modal .ph-input:focus, .compact-modal .ph-select:focus, .compact-modal .ph-textarea:focus { border-color: #1F6B4A; outline: none; box-shadow: 0 0 0 2px rgba(31,107,74,0.1); }
  .compact-modal .input-group { display: flex; width: 100%; }
  .compact-modal .input-group-text { padding: 4px 8px; font-size: 0.8rem; height: 32px; border-radius: 6px 0 0 6px; border: 1px solid rgba(31,107,74,0.2); background: #F3EFE6; color: #1F6B4A; font-weight: 800; border-right: none; display: flex; align-items: center; box-sizing: border-box; }
  .compact-modal .input-group .ph-input { border-radius: 0 6px 6px 0; border-left: none; flex: 1; }
  .compact-modal h6 { font-size: 0.8rem; margin-top: 4px; margin-bottom: 8px !important; color: #1F6B4A; font-weight: 800; border-bottom: 1px solid rgba(31,107,74,0.1); padding-bottom: 4px; }
  .compact-modal .modal-body { padding: 12px 20px; }
  .compact-modal .modal-header, .compact-modal .modal-footer { padding: 10px 20px; }
  .compact-modal .grid-4-cols { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 16px; }
  .compact-modal .grid-3-cols { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
  .compact-modal .grid-2-cols { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 16px; }
  .compact-modal .grid-split { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
  .compact-modal .grid-item { display: flex; flex-direction: column; }
  .compact-modal .grid-item-full { grid-column: 1 / -1; display: flex; flex-direction: column; }
</style>
<div class="modal fade compact-modal" id="mainModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 1100px;">
    <div class="modal-content" style="background: #F3EFE6; border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
      <div class="modal-header" style="border-bottom: 1px solid rgba(31,107,74,0.15);">
        <h5 class="modal-title" id="modalTitle" style="color: #1F6B4A; font-weight: 900; letter-spacing: -0.5px;"><i class="fas fa-boxes me-2"></i>New Stock Receive (GRN)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(34%) sepia(16%) saturate(1637%) hue-rotate(107deg) brightness(97%) contrast(89%); opacity: 0.8;"></button>
      </div>
      <form id="mainForm" onsubmit="save(event)">
        <div class="modal-body">
          <input type="hidden" name="receive_no" id="receive_no">
          <div class="mb-3">
            <!-- GRN Details -->
            <div>
              <h6><i class="fas fa-file-invoice me-1"></i>GRN Details</h6>
              <div class="grid-4-cols">
                <div class="grid-item">
                  <label class="ph-label">Linked PO (Optional)</label>
                  <select class="ph-select" name="po_no" id="po_no" onchange="loadPOItems(this.value)">
                    <option value="">-- None (Direct GRN) --</option>
                    <?php foreach($openPOs as $p): ?>
                    <option value="<?= htmlspecialchars($p['po_no']) ?>" data-supplier="<?= htmlspecialchars($p['supplier_name']) ?>">
                      <?= htmlspecialchars($p['po_no']) ?> — <?= htmlspecialchars($p['supplier_name']) ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="grid-item">
                  <label class="ph-label">Supplier *</label>
                  <select class="ph-select" name="supplier_id" id="supplier_id" required onchange="fillSupplierName(this)">
                    <option value="">-- Select Supplier --</option>
                    <?php foreach($suppliers as $s): ?>
                    <option value="<?= $s['supplier_id'] ?>" data-name="<?= htmlspecialchars($s['company_name'] . ' — ' . $s['supplier_name']) ?>">
                      <?= htmlspecialchars($s['company_name']) ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                  <input type="hidden" name="supplier_name" id="supplier_name">
                </div>
                <div class="grid-item">
                  <label class="ph-label">Supplier Invoice No</label>
                  <input type="text" class="ph-input" name="invoice_no" id="invoice_no">
                </div>
                <div class="grid-item">
                  <label class="ph-label">Remarks</label>
                  <input type="text" class="ph-input" name="remarks" id="remarks">
                </div>
              </div>
            </div>
          </div>

          <!-- Items -->
          <div class="ph-card mb-0" style="background:#FFFFFF; border:1px solid rgba(31,107,74,0.15); border-radius:16px; overflow:hidden; box-shadow:none;">
            <div class="ph-card-header px-3 py-2" style="background:rgba(31,107,74,0.05); border-bottom:1px solid rgba(31,107,74,0.1); display:flex; justify-content:space-between; align-items:center;">
              <span style="color:#1F6B4A; font-weight:800; font-size:0.9rem; text-transform:uppercase;"><i class="fas fa-boxes me-2"></i>Items Received</span>
              <button type="button" class="btn btn-sm" onclick="addRow()" style="background:#1F6B4A; color:#FFFFFF; border:none; border-radius:8px; font-weight:700; padding:0.3rem 0.8rem;"><i class="fas fa-plus me-1"></i> Add Item</button>
            </div>
            <div class="ph-card-body p-0 table-responsive">
              <table class="table mb-0" style="font-size:.8rem;">
                <thead>
                  <tr>
                    <th style="width:28%; color:#1F6B4A; font-weight:700; border-bottom:1px solid rgba(31,107,74,0.1);">Product</th>
                    <th style="width:14%; color:#1F6B4A; font-weight:700; border-bottom:1px solid rgba(31,107,74,0.1);">Batch No</th>
                    <th style="width:12%; color:#1F6B4A; font-weight:700; border-bottom:1px solid rgba(31,107,74,0.1);">Expiry Date</th>
                    <th style="width:9%; color:#1F6B4A; font-weight:700; border-bottom:1px solid rgba(31,107,74,0.1);">Received</th>
                    <th style="width:9%; color:#1F6B4A; font-weight:700; border-bottom:1px solid rgba(31,107,74,0.1);">Damaged</th>
                    <th style="width:9%; color:#1F6B4A; font-weight:700; border-bottom:1px solid rgba(31,107,74,0.1);">Net Qty</th>
                    <th style="width:11%; color:#1F6B4A; font-weight:700; border-bottom:1px solid rgba(31,107,74,0.1);">Rate (₹)</th>
                    <th style="width:11%; color:#1F6B4A; font-weight:700; border-bottom:1px solid rgba(31,107,74,0.1);">Subtotal</th>
                    <th style="width:7%; border-bottom:1px solid rgba(31,107,74,0.1);"></th>
                  </tr>
                </thead>
                <tbody id="itemsBody"></tbody>
                <tfoot style="background:rgba(31,107,74,0.02);">
                  <tr>
                    <td colspan="7" class="text-end fw-bold" style="padding:.85rem 1rem; color:#1F6B4A; border-top:2px solid rgba(31,107,74,0.1);">Grand Total:</td>
                    <td class="fw-bold" style="padding:.85rem 1rem; color:#1F6B4A; font-size:1.1rem; border-top:2px solid rgba(31,107,74,0.1);" id="grandTotal">₹0.00</td>
                    <td style="border-top:2px solid rgba(31,107,74,0.1);"></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>
        <div class="modal-footer" style="background: #F3EFE6; border-top: 1px solid rgba(31,107,74,0.15); border-radius: 0 0 12px 12px;">
          <button type="button" class="btn btn-sm" data-bs-dismiss="modal" style="background: transparent; color: #1F6B4A; border: 1.5px solid rgba(31,107,74,0.2); border-radius: 8px; font-weight: 700;">Cancel</button>
          <button type="submit" class="btn btn-sm" style="background: #1F6B4A; color: #FFFFFF; border: none; border-radius: 8px; font-weight: 700; box-shadow: 0 4px 10px rgba(31,107,74,0.2);">
            <i class="fas fa-boxes me-1"></i> Save GRN & Update Stock
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'includes/ph_foot.php'; ?>
<script>
// Local fallback to prevent browser cache issues
if (typeof phPost !== 'function') {
    window.phPost = async function(url, data = {}) {
        if (typeof phFetch === 'function') return phFetch(url, data, 'POST');
        const fd = new FormData();
        Object.entries(data).forEach(([k, v]) => fd.append(k, v));
        const res = await fetch(url, { method: 'POST', body: fd });
        return res.json();
    };
}

let all = [], rowCount = 0, currentPage = 1, PER_PAGE = 10;
const modal    = new bootstrap.Modal(document.getElementById('mainModal'));
const products = <?= json_encode($products) ?>;

let selectedIds = new Set(), filteredData = [];

document.addEventListener('DOMContentLoaded', () => {
    try {
        if (typeof phGet !== 'function') {
            console.error('phGet not defined');
            return;
        }
        load();
        const s = document.getElementById('searchInput');
        if (s) s.addEventListener('input', () => { selectedIds.clear(); currentPage = 1; render(); });
    } catch (e) { console.error('Init error:', e); }
});

async function load() {
    try {
        selectedIds.clear();
        const res = await phGet(API_BASE + 'pharmacy/grn');
        if (res.success) { 
            all = res.data || []; 
            render(); 
        } else {
            PH.error(res.message || res.error || "Failed to load receipts");
        }
    } catch (e) {
        console.error('Load error:', e);
        PH.error("Network or server error");
    }
}

function toggleRow(id, checked) {
    const numId = parseInt(id, 10);
    if (checked === undefined) {
        checked = !selectedIds.has(numId);
    }
    if (checked) selectedIds.add(numId); else selectedIds.delete(numId);
    
    const row = document.querySelector(`tr[data-id="${numId}"]`);
    if (row) {
        if (checked) row.classList.add('selected'); else row.classList.remove('selected');
        const cb = row.querySelector('input[type="checkbox"]');
        if (cb) cb.checked = checked;
    }
    updateBulkBar();
}

function toggleSelectAll(cb) {
    filteredData.forEach(x => {
        const numId = parseInt(x.id, 10);
        if (cb.checked) selectedIds.add(numId); else selectedIds.delete(numId);
        
        const row = document.querySelector(`tr[data-id="${numId}"]`);
        if (row) {
            if (cb.checked) row.classList.add('selected'); else row.classList.remove('selected');
            const checkbox = row.querySelector('input[type="checkbox"]');
            if (checkbox) checkbox.checked = cb.checked;
        }
    });
    updateBulkBar();
}

function updateBulkBar() {
    const bar = document.getElementById('bulkBar');
    if (!bar) return;
    if (selectedIds.size > 0) {
        bar.style.display = 'flex';
        document.getElementById('selectedCount').innerHTML = `<i class="fas fa-check-circle me-2" style="color:#10B981"></i> ${selectedIds.size} Selected`;
    } else {
        bar.style.display = 'none';
    }
}

function bulkExport() {
    if (!selectedIds.size) return;
    const selectedGrns = all.filter(x => selectedIds.has(x.id));
    let csv = 'GRN No,Date,PO No,Supplier,Invoice No,Total Qty,Total Amount\n';
    selectedGrns.forEach(x => {
        csv += `"${x.receive_no}","${fmt.date(x.receive_date)}","${x.po_no||''}","${x.supplier_name||''}","${x.invoice_no||''}","${x.total_qty}","${x.total_amount}"\n`;
    });
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.setAttribute("download", `GRN_Export_${Date.now()}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    PH.success('CSV Downloaded');
}

async function bulkPrint() {
    if (!selectedIds.size) return;
    PH.loading('Preparing print documents...');
    
    let printContent = `
    <html>
    <head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

        <title>GRN Bulk Print</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; color: #333; }
            .grn-block { border-bottom: 2px dashed #ccc; padding-bottom: 30px; margin-bottom: 30px; page-break-after: always; }
            .grn-block:last-child { border-bottom: none; page-break-after: avoid; }
            h2 { color: #0EA5E9; margin-top: 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .meta-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 15px; }
            .meta-item { font-size: 0.9rem; }
            .meta-item strong { color: #555; }
        </style>
    </head>
    <body>
    `;

    try {
        for (const id of selectedIds) {
            const res = await phGet(API_BASE + 'pharmacy/grn/' + id);
            if (res.success) {
                const { grn, items } = res.data;
                printContent += `
                <div class="grn-block">
                    <h2>Goods Receipt Note (GRN) — ${grn.receive_no}</h2>
                    <div class="meta-grid">
                        <div class="meta-item"><strong>Date:</strong> ${fmt.date(grn.receive_date)}</div>
                        <div class="meta-item"><strong>Supplier:</strong> ${grn.supplier_name || '—'}</div>
                        <div class="meta-item"><strong>PO No:</strong> ${grn.po_no || '—'}</div>
                        <div class="meta-item"><strong>Invoice No:</strong> ${grn.invoice_no || '—'}</div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Batch No</th>
                                <th>Expiry Date</th>
                                <th>Recv Qty</th>
                                <th>Damaged Qty</th>
                                <th>Net Qty</th>
                                <th>Rate (₹)</th>
                                <th>Subtotal (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${items.map(i => `
                            <tr>
                                <td>${i.item_name}</td>
                                <td>${i.batch_no || '—'}</td>
                                <td>${fmt.date(i.expiry_date)}</td>
                                <td>${i.received_qty}</td>
                                <td>${i.damaged_qty}</td>
                                <td>${i.net_qty}</td>
                                <td>${fmt.currency(i.rate)}</td>
                                <td>${fmt.currency(i.subtotal)}</td>
                            </tr>
                            `).join('')}
                        </tbody>
                    </table>
                    <div style="text-align: right; margin-top: 15px; font-weight: bold; font-size: 1.1rem;">
                        Grand Total: ${fmt.currency(grn.total_amount)}
                    </div>
                </div>
                `;
            }
        }
        
        printContent += `</body></html>`;
        
        const win = window.open('', '_blank');
        win.document.write(printContent);
        win.document.close();
        setTimeout(() => {
            win.print();
            PH.success('Print dispatched');
        }, 500);
        
    } catch (e) {
        PH.error('Print generation failed');
    }
}

async function bulkSubmitGRN() {
    if (!selectedIds.size) return;
    const confirmRes = await Swal.fire({
        title: 'Submit Goods Receipt Notes?',
        text: `Are you sure you want to submit the ${selectedIds.size} selected GRN(s) and update/commit their quantities to active product inventory?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#009688',
        cancelButtonColor: '#64748B',
        confirmButtonText: 'Yes, Submit & Commit Stock!'
    });
    if (!confirmRes.isConfirmed) return;

    PH.loading('Submitting GRNs...');
    try {
        const res = await phPost(API_BASE + 'pharmacy/grn/bulk-submit', { ids: Array.from(selectedIds) });
        if (res.success) {
            PH.success(res.message || 'GRNs submitted and stock committed successfully!');
            selectedIds.clear();
            load();
        } else {
            PH.error(res.message || res.error || 'Failed to submit GRNs');
        }
    } catch(e) {
        PH.error('Failed to submit GRNs: ' + e.message);
        console.error('Submit GRN error:', e);
    }
}

function render() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    let filtered = all;
    if (q) filtered = filtered.filter(x => (x.receive_no||'').toLowerCase().includes(q) || (x.po_no||'').toLowerCase().includes(q) || (x.supplier_name||'').toLowerCase().includes(q) || (x.product_names||'').toLowerCase().includes(q));
    filteredData = filtered;
    const pager = phPaginate(filtered, currentPage, PER_PAGE);
    let html = '';
    if (!pager.items.length) { html = `<tr><td colspan="10" class="text-center py-4 text-muted">No GRNs found.</td></tr>`; }
    else pager.items.forEach(x => {
        const isSelected = selectedIds.has(parseInt(x.id, 10));
        const isSubmitted = x.status == 1;
        html += `<tr data-id="${x.id}" class="${isSelected ? 'selected' : ''} ${isSubmitted ? 'tr-submitted' : ''}">
            <td onclick="toggleRow(${x.id})">
                <input type="checkbox" 
                       ${isSelected ? 'checked' : ''} 
                       onclick="event.stopPropagation(); toggleRow(${x.id}, this.checked)" 
                       style="width:20px; height:20px; accent-color: var(--ph-primary); cursor: pointer;">
            </td>
            <td onclick="toggleExpand(${x.id})" style="cursor:pointer; text-align:center;">
                <i class="fas fa-chevron-right text-muted" id="icon_${x.id}" style="transition: transform 0.2s;"></i>
            </td>
            <td onclick="toggleExpand(${x.id})" style="cursor:pointer;">
                <span class="ph-badge badge-primary fw-bold">${x.receive_no}</span>
            </td>
            <td>${fmt.date(x.receive_date)}</td>
            <td>${x.po_no || '—'}</td>
            <td>${x.supplier_name || '—'}</td>
            <td>${x.invoice_no || '—'}</td>
            <td class="fw-bold">${x.total_qty}</td>
            <td class="fw-bold text-success">${fmt.currency(x.total_amount)}</td>
            <td>${isSubmitted ? '<span class="ph-badge badge-success"><i class="fas fa-check-double me-1"></i>Submitted</span>' : '<span class="ph-badge badge-warning"><i class="fas fa-edit me-1"></i>Draft</span>'}</td>
            <td class="text-end" onclick="event.stopPropagation()">
                <button class="ph-btn ph-btn-sm ph-btn-outline ph-btn-icon me-1 text-warning" onclick="${isSubmitted ? `Swal.fire('Cannot Edit', 'This GRN has already been submitted and added to live inventory. Editing is only allowed for Drafts.', 'warning')` : `editGRN(${x.id})`}" title="Edit GRN"><i class="fas fa-edit"></i></button>
                <button class="ph-btn ph-btn-sm ph-btn-outline ph-btn-icon me-1" onclick="viewGRN(${x.id})" title="View Full Receipt"><i class="fas fa-eye"></i></button>
                <button class="ph-btn ph-btn-sm ph-btn-outline ph-btn-icon text-danger" onclick="deleteGRN('${x.receive_no}', ${isSubmitted})" title="Delete Entire GRN"><i class="fas fa-trash"></i></button>
            </td></tr>`;
            
        let childHtml = `<tr id="child_${x.id}" style="display:none; background:#f8fafc;">
            <td colspan="11" style="padding: 15px 20px 20px 60px;">
                <div style="font-size:0.85rem; font-weight:bold; color:#475569; margin-bottom:8px;"><i class="fas fa-boxes me-2"></i>Products inside ${x.receive_no}</div>
                <table style="width:100%; border-collapse: collapse; background:#fff; border-radius:6px; overflow:hidden; box-shadow: 0 0 0 1px #e2e8f0;">
                    <thead style="background:#f1f5f9; border-bottom:1px solid #e2e8f0;">
                        <tr>
                            <th style="padding:8px 12px; color:#334155; font-weight:600;">Product</th>
                            <th style="padding:8px 12px; color:#334155; font-weight:600;">Batch</th>
                            <th style="padding:8px 12px; color:#334155; font-weight:600;">Expiry</th>
                            <th style="padding:8px 12px; color:#334155; font-weight:600; text-align:center;">Net Qty</th>
                            <th style="padding:8px 12px; color:#334155; font-weight:600; text-align:right;">Rate</th>
                            <th style="padding:8px 12px; color:#334155; font-weight:600; text-align:right;">Subtotal</th>
                            <th style="padding:8px 12px; color:#334155; font-weight:600; text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>`;
                    
        if (x.items && x.items.length) {
            x.items.forEach((item, idx) => {
                let bg = idx % 2 === 0 ? '#ffffff' : '#fafafa';
                childHtml += `<tr style="background:${bg}; border-bottom:1px solid #e2e8f0;">
                    <td style="padding:8px 12px; font-weight:500; color:#1e293b;">${item.item_name}</td>
                    <td style="padding:8px 12px; color:#64748b;">${item.batch_no || '—'}</td>
                    <td style="padding:8px 12px; color:#64748b;">${item.expiry_date ? fmt.date(item.expiry_date) : '—'}</td>
                    <td style="padding:8px 12px; text-align:center; font-weight:bold; color:#0ea5e9;">${item.net_qty}</td>
                    <td style="padding:8px 12px; text-align:right;">${fmt.currency(item.rate)}</td>
                    <td style="padding:8px 12px; text-align:right; font-weight:bold; color:#10b981;">${fmt.currency(item.subtotal)}</td>
                    <td style="padding:8px 12px; text-align:center;">
                        <button class="ph-btn ph-btn-sm ph-btn-outline ph-btn-icon text-danger" onclick="deleteGRNItem('${item.id}', '${item.item_name}', ${isSubmitted})" title="Delete Line Item"><i class="fas fa-trash-alt"></i></button>
                    </td>
                </tr>`;
            });
        } else {
            childHtml += `<tr><td colspan="7" style="padding:10px; text-align:center; color:#94a3b8;">No items found.</td></tr>`;
        }
        childHtml += `</tbody></table></td></tr>`;
        html += childHtml;
    });
    document.getElementById('tableBody').innerHTML = html;
    phRenderPager(document.getElementById('pager'), pager.pages, currentPage, p => { currentPage = p; render(); });
    updateBulkBar();
}

function openModal() {
    document.getElementById('mainForm').reset();
    document.getElementById('receive_no').value = '';
    document.getElementById('modalTitle').textContent = 'New Stock Receive (GRN)';
    document.getElementById('itemsBody').innerHTML = '';
    rowCount = 0;
    addRow();
    modal.show();
}

async function editGRN(id) {
    PH.loading('Loading GRN...');
    try {
        const res = await phGet(API_BASE + 'pharmacy/grn/' + id);
        if (!res.success) return PH.error('Failed to load GRN');
        
        const { grn, items } = res.data;
        
        document.getElementById('mainForm').reset();
        document.getElementById('modalTitle').textContent = 'Edit Stock Receive — ' + grn.receive_no;
        document.getElementById('receive_no').value = grn.receive_no;
        document.getElementById('po_no').value = grn.po_no || '';
        document.getElementById('supplier_id').value = grn.supplier_id || '';
        document.getElementById('supplier_name').value = grn.supplier_name || '';
        document.getElementById('invoice_no').value = grn.invoice_no || '';
        document.getElementById('remarks').value = grn.remarks || '';
        
        document.getElementById('itemsBody').innerHTML = '';
        rowCount = 0;
        
        items.forEach(i => addRow({
            product_id: i.product_id,
            item_name: i.item_name,
            content: i.content,
            strength: i.strength,
            form: i.form,
            therapeutic: i.therapeutic,
            hsn_code: i.hsn_code,
            manufacturer: i.manufacturer,
            mrp: i.mrp,
            tax_percent: i.tax_percent,
            pack_rate: i.pack_rate,
            individual_rate: i.individual_rate,
            pack: i.pack,
            unit: i.unit,
            pack_size: i.pack_size,
            min_stock: i.min_stock,
            max_stock: i.max_stock,
            rack_location: i.rack_location,
            batch_no: i.batch_no,
            expiry_date: i.expiry_date,
            received_qty: i.received_qty,
            damaged_qty: i.damaged_qty,
            net_qty: i.net_qty,
            rate: i.rate,
            subtotal: i.subtotal
        }));
        
        Swal.close();
        modal.show();
    } catch(e) {
        PH.error('Failed to load GRN for editing');
    }
}

function addRow(data = {}) {
    const rid = 'r' + rowCount++;
    const opts = products.map(p => `<option value="${p.product_id}" data-name="${p.product_name}" ${data.product_id === p.product_id ? 'selected' : ''}>${p.product_name}</option>`).join('');
    
    let pInfo = {};
    if (data.product_id) {
        pInfo = products.find(p => p.product_id === data.product_id) || {};
    }
    
    const itemName = data.item_name || pInfo.product_name || '';
    const content = data.content || pInfo.content || '';
    const strength = data.strength || pInfo.strength || '';
    const form = data.form || pInfo.form || '';
    const therapeutic = data.therapeutic || pInfo.therapeutic || '';
    const hsn_code = data.hsn_code || pInfo.hsn_code || '';
    const manufacturer = data.manufacturer || pInfo.manufacturer || '';
    const mrp = data.mrp !== undefined ? data.mrp : (pInfo.mrp !== undefined ? pInfo.mrp : 0.00);
    const tax_percent = data.tax_percent !== undefined ? data.tax_percent : (pInfo.tax_percent !== undefined ? pInfo.tax_percent : 12.00);
    const pack_rate = data.pack_rate !== undefined ? data.pack_rate : (pInfo.pack_rate !== undefined ? pInfo.pack_rate : 0.00);
    const individual_rate = data.individual_rate !== undefined ? data.individual_rate : (pInfo.individual_rate !== undefined ? pInfo.individual_rate : 0.00);
    const pack = data.pack || pInfo.pack || '';
    const unit = data.unit || pInfo.unit || 'Tablet';
    const pack_size = data.pack_size !== undefined ? data.pack_size : (pInfo.pack_size !== undefined ? pInfo.pack_size : 10);
    const min_stock = data.min_stock !== undefined ? data.min_stock : (pInfo.min_stock !== undefined ? pInfo.min_stock : 20);
    const max_stock = data.max_stock !== undefined ? data.max_stock : (pInfo.max_stock !== undefined ? pInfo.max_stock : 500);
    const rack_location = data.rack_location || pInfo.rack_location || '';

    const tr = document.createElement('tr');
    tr.id = rid;
    tr.innerHTML = `
        <td><select class="ph-select" name="items[${rid}][product_id]" onchange="fillProductData(this,'${rid}')" style="width:100%;font-size:.78rem;">
            <option value="">-- Select --</option>${opts}</select>
        <input type="hidden" name="items[${rid}][item_name]" id="${rid}_name" value="${itemName}">
        <input type="hidden" name="items[${rid}][content]" id="${rid}_content" value="${content}">
        <input type="hidden" name="items[${rid}][strength]" id="${rid}_strength" value="${strength}">
        <input type="hidden" name="items[${rid}][form]" id="${rid}_form" value="${form}">
        <input type="hidden" name="items[${rid}][therapeutic]" id="${rid}_therapeutic" value="${therapeutic}">
        <input type="hidden" name="items[${rid}][hsn_code]" id="${rid}_hsn_code" value="${hsn_code}">
        <input type="hidden" name="items[${rid}][manufacturer]" id="${rid}_manufacturer" value="${manufacturer}">
        <input type="hidden" name="items[${rid}][mrp]" id="${rid}_mrp" value="${mrp}">
        <input type="hidden" name="items[${rid}][tax_percent]" id="${rid}_tax_percent" value="${tax_percent}">
        <input type="hidden" name="items[${rid}][pack_rate]" id="${rid}_pack_rate" value="${pack_rate}">
        <input type="hidden" name="items[${rid}][individual_rate]" id="${rid}_individual_rate" value="${individual_rate}">
        <input type="hidden" name="items[${rid}][pack]" id="${rid}_pack" value="${pack}">
        <input type="hidden" name="items[${rid}][unit]" id="${rid}_unit" value="${unit}">
        <input type="hidden" name="items[${rid}][pack_size]" id="${rid}_pack_size" value="${pack_size}">
        <input type="hidden" name="items[${rid}][min_stock]" id="${rid}_min_stock" value="${min_stock}">
        <input type="hidden" name="items[${rid}][max_stock]" id="${rid}_max_stock" value="${max_stock}">
        <input type="hidden" name="items[${rid}][rack_location]" id="${rid}_rack_location" value="${rack_location}">
        </td>
        <td><input type="text" class="ph-input" name="items[${rid}][batch_no]" value="${data.batch_no||''}" style="font-size:.78rem;"></td>
        <td><input type="date" class="ph-input" name="items[${rid}][expiry_date]" value="${data.expiry_date||''}" style="font-size:.78rem;"></td>
        <td><input type="number" class="ph-input" name="items[${rid}][received_qty]" value="${data.received_qty||0}" min="0" onchange="calcRow('${rid}')" style="font-size:.78rem;"></td>
        <td><input type="number" class="ph-input" name="items[${rid}][damaged_qty]" value="${data.damaged_qty||0}" min="0" onchange="calcRow('${rid}')" style="font-size:.78rem;"></td>
        <td><input type="text" class="ph-input fw-bold text-success" id="${rid}_net" name="items[${rid}][net_qty]" value="${data.net_qty||0}" readonly style="background:#F8FAFC;font-size:.78rem;"></td>
        <td><input type="number" class="ph-input" name="items[${rid}][rate]" value="${data.rate||0}" step="0.01" onchange="calcRow('${rid}')" style="font-size:.78rem;"></td>
        <td><input type="text" class="ph-input fw-bold" id="${rid}_sub" name="items[${rid}][subtotal]" value="${data.subtotal||0}" readonly style="background:#F8FAFC;font-size:.78rem;"></td>
        <td><button type="button" class="ph-btn ph-btn-icon text-danger ph-btn-sm" onclick="document.getElementById('${rid}').remove();updateGrand();"><i class="fas fa-times"></i></button></td>`;
    document.getElementById('itemsBody').appendChild(tr);
    if (data.product_id) tr.querySelector('select').value = data.product_id;
    calcRow(rid);
}

function fillProductData(sel, rid) {
    const prodId = sel.value;
    const pInfo = products.find(p => p.product_id === prodId) || {};
    
    document.getElementById(rid + '_name').value = pInfo.product_name || '';
    document.getElementById(rid + '_content').value = pInfo.content || '';
    document.getElementById(rid + '_strength').value = pInfo.strength || '';
    document.getElementById(rid + '_form').value = pInfo.form || '';
    document.getElementById(rid + '_therapeutic').value = pInfo.therapeutic || '';
    document.getElementById(rid + '_hsn_code').value = pInfo.hsn_code || '';
    document.getElementById(rid + '_manufacturer').value = pInfo.manufacturer || '';
    document.getElementById(rid + '_mrp').value = pInfo.mrp !== undefined ? pInfo.mrp : 0.00;
    document.getElementById(rid + '_tax_percent').value = pInfo.tax_percent !== undefined ? pInfo.tax_percent : 12.00;
    document.getElementById(rid + '_pack_rate').value = pInfo.pack_rate !== undefined ? pInfo.pack_rate : 0.00;
    document.getElementById(rid + '_individual_rate').value = pInfo.individual_rate !== undefined ? pInfo.individual_rate : 0.00;
    document.getElementById(rid + '_pack').value = pInfo.pack || '';
    document.getElementById(rid + '_unit').value = pInfo.unit || 'Tablet';
    document.getElementById(rid + '_pack_size').value = pInfo.pack_size !== undefined ? pInfo.pack_size : 10;
    document.getElementById(rid + '_min_stock').value = pInfo.min_stock !== undefined ? pInfo.min_stock : 20;
    document.getElementById(rid + '_max_stock').value = pInfo.max_stock !== undefined ? pInfo.max_stock : 500;
    document.getElementById(rid + '_rack_location').value = pInfo.rack_location || '';
}

function calcRow(rid) {
    const row    = document.getElementById(rid);
    const recv   = parseInt(row.querySelector('[name*="received_qty"]').value) || 0;
    const dam    = parseInt(row.querySelector('[name*="damaged_qty"]').value) || 0;
    const rate   = parseFloat(row.querySelector('[name*="rate"]').value) || 0;
    const net    = Math.max(0, recv - dam);
    document.getElementById(rid + '_net').value = net;
    document.getElementById(rid + '_sub').value = (net * rate).toFixed(2);
    updateGrand();
}

function updateGrand() {
    let t = 0;
    document.querySelectorAll('[id$="_sub"]').forEach(el => t += parseFloat(el.value) || 0);
    document.getElementById('grandTotal').textContent = '₹' + t.toFixed(2);
}

async function loadPOItems(po_no) {
    if (!po_no) return;
    PH.loading('Fetching PO...');
    try {
        const res = await phGet(API_BASE + 'pharmacy/purchase-orders/' + encodeURIComponent(po_no));
        if (res.success && res.data.items.length) {
            document.getElementById('itemsBody').innerHTML = '';
            rowCount = 0;
            res.data.items.forEach(i => addRow({ product_id: i.product_id, item_name: i.item_name, received_qty: i.qty, rate: i.rate }));
        }
    } catch(e) { PH.error('Error loading PO items'); }
}

async function save(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    const data = {
        receive_no: fd.get('receive_no'),
        po_no: fd.get('po_no'),
        supplier_id: fd.get('supplier_id'),
        supplier_name: fd.get('supplier_name'),
        invoice_no: fd.get('invoice_no'),
        remarks: fd.get('remarks'),
        items: []
    };
    
    // Parse nested items from FormData (simpler to just iterate over rows)
    document.querySelectorAll('#itemsBody tr').forEach(tr => {
        const rid = tr.id;
        const item = {
            product_id: tr.querySelector(`[name="items[${rid}][product_id]"]`).value,
            item_name:  tr.querySelector(`[id="${rid}_name"]`).value,
            content:    tr.querySelector(`[id="${rid}_content"]`).value,
            strength:   tr.querySelector(`[id="${rid}_strength"]`).value,
            form:       tr.querySelector(`[id="${rid}_form"]`).value,
            therapeutic:tr.querySelector(`[id="${rid}_therapeutic"]`).value,
            hsn_code:   tr.querySelector(`[id="${rid}_hsn_code"]`).value,
            manufacturer: tr.querySelector(`[id="${rid}_manufacturer"]`).value,
            mrp:        parseFloat(tr.querySelector(`[id="${rid}_mrp"]`).value) || 0.00,
            tax_percent: parseFloat(tr.querySelector(`[id="${rid}_tax_percent"]`).value) || 12.00,
            pack_rate:   parseFloat(tr.querySelector(`[id="${rid}_pack_rate"]`).value) || 0.00,
            individual_rate: parseFloat(tr.querySelector(`[id="${rid}_individual_rate"]`).value) || 0.00,
            pack:       tr.querySelector(`[id="${rid}_pack"]`).value,
            unit:       tr.querySelector(`[id="${rid}_unit"]`).value,
            pack_size:  parseInt(tr.querySelector(`[id="${rid}_pack_size"]`).value) || 10,
            min_stock:  parseInt(tr.querySelector(`[id="${rid}_min_stock"]`).value) || 20,
            max_stock:  parseInt(tr.querySelector(`[id="${rid}_max_stock"]`).value) || 500,
            rack_location: tr.querySelector(`[id="${rid}_rack_location"]`).value,
            batch_no:   tr.querySelector(`[name="items[${rid}][batch_no]"]`).value,
            expiry_date:tr.querySelector(`[name="items[${rid}][expiry_date]"]`).value,
            received_qty:parseInt(tr.querySelector(`[name="items[${rid}][received_qty]"]`).value) || 0,
            damaged_qty: parseInt(tr.querySelector(`[name="items[${rid}][damaged_qty]"]`).value) || 0,
            net_qty:     parseInt(tr.querySelector(`[id="${rid}_net"]`).value) || 0,
            rate:        parseFloat(tr.querySelector(`[name="items[${rid}][rate]"]`).value) || 0,
            subtotal:    parseFloat(tr.querySelector(`[id="${rid}_sub"]`).value) || 0
        };
        if (item.product_id) data.items.push(item);
    });

    if (!data.items.length) { PH.warning('Please add at least one product.'); return; }

    PH.loading('Saving GRN...');
    try {
        const res = await fetch(API_BASE + 'pharmacy/grn', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        }).then(r => r.json());
        if (res.success) { PH.success(res.message); modal.hide(); load(); } else PH.error(res.message);
    } catch(e) { PH.error('Failed to save GRN'); }
}

async function viewGRN(id) {
    PH.loading('Loading...');
    const res = await phGet(API_BASE + 'pharmacy/grn/' + id);
    if (!res.success) return PH.error('Failed to load GRN');
    const { grn, items } = res.data;
    let body = `<div class="row g-4 mb-4">
        <div class="col-12">
            <div class="p-3" style="background:#FFFFFF; border:1px solid rgba(31,107,74,0.15); border-radius:12px;">
                <div class="row g-3">
                    <div class="col-md-4"><div style="font-size:0.7rem; font-weight:700; color:#1F6B4A; text-transform:uppercase; margin-bottom:4px;">GRN No</div><strong style="color:#1F6B4A; font-size:1.1rem;">${grn.receive_no}</strong></div>
                    <div class="col-md-4"><div style="font-size:0.7rem; font-weight:700; color:#1F6B4A; text-transform:uppercase; margin-bottom:4px;">Date</div><span style="color:#1F6B4A; font-weight:600;">${fmt.date(grn.receive_date)}</span></div>
                    <div class="col-md-4"><div style="font-size:0.7rem; font-weight:700; color:#1F6B4A; text-transform:uppercase; margin-bottom:4px;">Supplier</div><span style="color:#1F6B4A; font-weight:600;">${grn.supplier_name||'—'}</span></div>
                    <div class="col-md-4"><div style="font-size:0.7rem; font-weight:700; color:#1F6B4A; text-transform:uppercase; margin-bottom:4px;">PO No</div><span style="color:#1F6B4A; font-weight:600;">${grn.po_no||'—'}</span></div>
                    <div class="col-md-4"><div style="font-size:0.7rem; font-weight:700; color:#1F6B4A; text-transform:uppercase; margin-bottom:4px;">Invoice No</div><span style="color:#1F6B4A; font-weight:600;">${grn.invoice_no||'—'}</span></div>
                    <div class="col-md-4"><div style="font-size:0.7rem; font-weight:700; color:#1F6B4A; text-transform:uppercase; margin-bottom:4px;">Total Amount</div><strong style="color:#10B981; font-size:1.1rem;">${fmt.currency(grn.total_amount)}</strong></div>
                </div>
            </div>
        </div>
    </div>
    <div class="table-responsive" style="border: 1px solid rgba(31,107,74,0.15); border-radius: 12px; overflow: hidden; background:#FFFFFF;">
    <table class="table mb-0" style="font-size: 0.8rem; margin: 0; width: 100%;">
    <thead style="background: rgba(31,107,74,0.05);">
        <tr>
            <th style="color:#1F6B4A; font-size:0.75rem; font-weight:800; text-transform:uppercase; border-bottom:1px solid rgba(31,107,74,0.1);">Product</th>
            <th style="color:#1F6B4A; font-size:0.75rem; font-weight:800; text-transform:uppercase; border-bottom:1px solid rgba(31,107,74,0.1);">Batch No</th>
            <th style="color:#1F6B4A; font-size:0.75rem; font-weight:800; text-transform:uppercase; border-bottom:1px solid rgba(31,107,74,0.1);">Expiry Date</th>
            <th style="color:#1F6B4A; font-size:0.75rem; font-weight:800; text-transform:uppercase; border-bottom:1px solid rgba(31,107,74,0.1);">Recv.</th>
            <th style="color:#1F6B4A; font-size:0.75rem; font-weight:800; text-transform:uppercase; border-bottom:1px solid rgba(31,107,74,0.1);">Damaged</th>
            <th style="color:#1F6B4A; font-size:0.75rem; font-weight:800; text-transform:uppercase; border-bottom:1px solid rgba(31,107,74,0.1);">Net</th>
            <th style="color:#1F6B4A; font-size:0.75rem; font-weight:800; text-transform:uppercase; border-bottom:1px solid rgba(31,107,74,0.1);">Rate</th>
            <th style="color:#1F6B4A; font-size:0.75rem; font-weight:800; text-transform:uppercase; border-bottom:1px solid rgba(31,107,74,0.1);">Subtotal</th>
        </tr>
    </thead>
    <tbody>` +
    items.map(i => `
    <tr style="background: #ffffff; border-bottom: none;">
        <td style="color:#1F6B4A; font-weight:600;">${i.item_name}</td>
        <td style="color:#1F6B4A;">${i.batch_no||'—'}</td>
        <td>${expiryBadge(i.expiry_date)}</td>
        <td style="color:#1F6B4A;">${i.received_qty}</td>
        <td class="text-danger">${i.damaged_qty}</td>
        <td class="text-success fw-bold">${i.net_qty}</td>
        <td style="color:#1F6B4A;">${fmt.currency(i.rate)}</td>
        <td style="color:#1F6B4A; font-weight:700;">${fmt.currency(i.subtotal)}</td>
    </tr>
    <tr style="background: rgba(31,107,74,0.02); border-bottom: 1px solid rgba(31,107,74,0.1);">
        <td colspan="8" style="padding: 12px 16px;">
            <div style="display: flex; flex-wrap: wrap; gap: 16px 24px; font-size: 0.75rem; color: #1F6B4A;">
                <div><strong style="color: #1F6B4A; opacity:0.7;">Content:</strong> ${i.content||'—'}</div>
                <div><strong style="color: #1F6B4A; opacity:0.7;">Strength:</strong> ${i.strength||'—'}</div>
                <div><strong style="color: #1F6B4A; opacity:0.7;">Form:</strong> ${i.form||'—'}</div>
                <div><strong style="color: #1F6B4A; opacity:0.7;">Therapeutic:</strong> ${i.therapeutic||'—'}</div>
                <div><strong style="color: #1F6B4A; opacity:0.7;">HSN:</strong> ${i.hsn_code||'—'}</div>
                <div><strong style="color: #1F6B4A; opacity:0.7;">Manufacturer:</strong> ${i.manufacturer||'—'}</div>
                <div><strong style="color: #1F6B4A; opacity:0.7;">Pack:</strong> ${i.pack||'—'}</div>
                <div><strong style="color: #1F6B4A; opacity:0.7;">Unit:</strong> ${i.unit||'—'}</div>
                <div><strong style="color: #1F6B4A; opacity:0.7;">Size:</strong> ${i.pack_size||'—'}</div>
                <div><strong style="color: #1F6B4A; opacity:0.7;">MRP:</strong> ${fmt.currency(i.mrp)}</div>
                <div><strong style="color: #1F6B4A; opacity:0.7;">Tax:</strong> ${i.tax_percent||0}%</div>
                <div><strong style="color: #1F6B4A; opacity:0.7;">Pack Rate:</strong> ${fmt.currency(i.pack_rate)}</div>
                <div><strong style="color: #1F6B4A; opacity:0.7;">Ind. Rate:</strong> ${fmt.currency(i.individual_rate)}</div>
                <div><strong style="color: #1F6B4A; opacity:0.7;">Min Stock:</strong> ${i.min_stock||0}</div>
                <div><strong style="color: #1F6B4A; opacity:0.7;">Max Stock:</strong> ${i.max_stock||0}</div>
                <div><strong style="color: #1F6B4A; opacity:0.7;">Rack:</strong> ${i.rack_location||'—'}</div>
            </div>
        </td>
    </tr>
    `).join('') +
    `</tbody></table></div>`;

    Swal.fire({ title: 'GRN — ' + grn.receive_no, html: body, width: '900px', confirmButtonColor: '#1f6b4a', confirmButtonText: 'Close' });
}

async function deleteGRN(receive_no, isSubmitted) {
    if (!isSubmitted) {
        const confirmRes = await Swal.fire({
            title: 'Delete Draft GRN?',
            text: "This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748B',
            confirmButtonText: 'Yes, delete it!'
        });
        if (!confirmRes.isConfirmed) return;
        executeDeleteGRN(receive_no);
        return;
    }

    PH.loading('Checking stock status...');
    try {
        const checkRes = await phGet(API_BASE + 'pharmacy/grn/' + encodeURIComponent(receive_no) + '/check-delete');
        Swal.close();
        if (checkRes.success) {
            const data = checkRes.data;
            
            let tableHtml = `<table style="font-size:0.85rem; width:100%; text-align:left; margin-top:15px; border-collapse: collapse; border-radius: 8px; overflow: hidden; box-shadow: 0 0 0 1px #e2e8f0;">
                <thead style="background:#f1f5f9; border-bottom: 2px solid #e2e8f0;">
                    <tr>
                        <th style="padding:10px 12px; font-weight: 600; color:#334155;">Product</th>
                        <th style="padding:10px 12px; font-weight: 600; color:#334155;">Batch</th>
                        <th style="padding:10px 12px; font-weight: 600; color:#334155; text-align:center;">GRN Qty</th>
                        <th style="padding:10px 12px; font-weight: 600; color:#334155; text-align:center;">Present Stock</th>
                        <th style="padding:10px 12px; font-weight: 600; color:#334155; text-align:center;">Sales?</th>
                        <th style="padding:10px 12px; font-weight: 600; color:#334155; text-align:center;">Post-Delete</th>
                    </tr>
                </thead>
                <tbody>`;
                
            if (data.breakdown && data.breakdown.length > 0) {
                data.breakdown.forEach((b, index) => {
                    let afterDelete = b.current_stock - b.grn_qty;
                    let stockColor = afterDelete < 0 ? 'color: #e11d48; font-weight: 700;' : 'color: #10b981; font-weight: 600;';
                    let salesBadge = b.has_sales ? '<span style="background:#fee2e2; color:#ef4444; padding:2px 6px; border-radius:4px; font-size:0.75rem; font-weight:bold;">Yes</span>' : '<span style="background:#dcfce7; color:#22c55e; padding:2px 6px; border-radius:4px; font-size:0.75rem; font-weight:bold;">No</span>';
                    let bg = index % 2 === 0 ? '#ffffff' : '#f8fafc';
                    
                    tableHtml += `<tr style="background:${bg}; border-bottom:1px solid #e2e8f0;">
                        <td style="padding:10px 12px; color:#1e293b; font-weight: 500;">${b.item_name}</td>
                        <td style="padding:10px 12px; color:#475569;">${b.batch_no || '—'}</td>
                        <td style="padding:10px 12px; text-align:center; color:#3b82f6; font-weight:600;">${b.grn_qty}</td>
                        <td style="padding:10px 12px; text-align:center; color:#1e293b;">${b.current_stock}</td>
                        <td style="padding:10px 12px; text-align:center;">${salesBadge}</td>
                        <td style="padding:10px 12px; text-align:center; ${stockColor}">${afterDelete}</td>
                    </tr>`;
                });
            } else {
                tableHtml += `<tr><td colspan="6" style="padding:15px; text-align:center; color:#64748b;">No items found.</td></tr>`;
            }
            tableHtml += `</tbody></table>`;
            
            let htmlBody = `
                <div style="text-align:left; font-size: 0.95rem; margin-bottom:15px; color: ${data.warning ? '#e11d48' : '#334155'}; font-weight: ${data.warning ? 'bold' : 'normal'};">
                    ${data.message}
                </div>
                <div style="text-align:left; font-size:0.85rem; font-weight:bold; margin-bottom:5px; color:#475569;"><i class="fas fa-boxes me-2"></i>Stock Reversal Preview</div>
                ${tableHtml}
            `;

            const confirmRes = await Swal.fire({
                title: 'Review Stock Impact',
                html: htmlBody,
                width: '850px',
                icon: data.warning ? 'error' : 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748B',
                confirmButtonText: data.warning ? '<i class="fas fa-exclamation-triangle"></i> Yes, Force Delete' : '<i class="fas fa-undo"></i> Yes, Delete & Reverse Stock'
            });

            if (!confirmRes.isConfirmed) return;
            executeDeleteGRN(receive_no);
        } else {
            PH.error('Failed to check GRN status');
        }
    } catch(e) {
        PH.error('Failed to communicate with server');
    }
}

async function executeDeleteGRN(receive_no) {
    PH.loading('Deleting & Reversing Stock...');
    try {
        const res = await fetch(API_BASE + 'pharmacy/grn/' + encodeURIComponent(receive_no), {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' }
        }).then(r => r.json());
        
        if (res.success) {
            PH.success(res.message || 'GRN deleted successfully');
            load();
        } else {
            PH.error(res.message || 'Failed to delete GRN');
        }
    } catch(e) {
        PH.error('Failed to delete GRN');
    }
}

function toggleExpand(id) {
    const child = document.getElementById('child_' + id);
    const icon = document.getElementById('icon_' + id);
    if (!child) return;
    if (child.style.display === 'none') {
        child.style.display = 'table-row';
        icon.style.transform = 'rotate(90deg)';
    } else {
        child.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
    }
}

async function deleteGRNItem(itemId, itemName, isSubmitted) {
    if (!isSubmitted) {
        const confirmRes = await Swal.fire({
            title: `Delete ${itemName}?`,
            text: "This draft line item will be deleted permanently.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748B',
            confirmButtonText: 'Yes, delete it!'
        });
        if (!confirmRes.isConfirmed) return;
        executeDeleteGRNItem(itemId);
        return;
    }

    PH.loading('Checking item stock status...');
    try {
        const checkRes = await phGet(API_BASE + 'pharmacy/grn-item/' + itemId + '/check-delete');
        Swal.close();
        if (checkRes.success) {
            const data = checkRes.data;
            let afterDelete = data.post_delete;
            let stockColor = afterDelete < 0 ? 'color: #e11d48; font-weight: 700;' : 'color: #10b981; font-weight: 600;';
            let salesBadge = data.has_sales ? '<span style="background:#fee2e2; color:#ef4444; padding:2px 6px; border-radius:4px; font-size:0.75rem; font-weight:bold;">Yes</span>' : '<span style="background:#dcfce7; color:#22c55e; padding:2px 6px; border-radius:4px; font-size:0.75rem; font-weight:bold;">No</span>';
            
            let tableHtml = `<table style="font-size:0.85rem; width:100%; text-align:left; margin-top:15px; border-collapse: collapse; border-radius: 8px; overflow: hidden; box-shadow: 0 0 0 1px #e2e8f0;">
                <thead style="background:#f1f5f9; border-bottom: 2px solid #e2e8f0;">
                    <tr>
                        <th style="padding:10px 12px; font-weight: 600; color:#334155;">Product</th>
                        <th style="padding:10px 12px; font-weight: 600; color:#334155; text-align:center;">GRN Qty</th>
                        <th style="padding:10px 12px; font-weight: 600; color:#334155; text-align:center;">Present Stock</th>
                        <th style="padding:10px 12px; font-weight: 600; color:#334155; text-align:center;">Sales?</th>
                        <th style="padding:10px 12px; font-weight: 600; color:#334155; text-align:center;">Post-Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="background:#fff;">
                        <td style="padding:10px 12px; color:#1e293b; font-weight: 500;">${data.item_name}</td>
                        <td style="padding:10px 12px; text-align:center; color:#3b82f6; font-weight:600;">${data.grn_qty}</td>
                        <td style="padding:10px 12px; text-align:center; color:#1e293b;">${data.current_stock}</td>
                        <td style="padding:10px 12px; text-align:center;">${salesBadge}</td>
                        <td style="padding:10px 12px; text-align:center; ${stockColor}">${afterDelete}</td>
                    </tr>
                </tbody>
            </table>`;
            
            let htmlBody = `
                <div style="text-align:left; font-size: 0.95rem; margin-bottom:15px; color: ${data.warning ? '#e11d48' : '#334155'}; font-weight: ${data.warning ? 'bold' : 'normal'};">
                    ${data.message}
                </div>
                ${tableHtml}
            `;

            const confirmRes = await Swal.fire({
                title: 'Reverse Line Item Stock',
                html: htmlBody,
                width: '750px',
                icon: data.warning ? 'error' : 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748B',
                confirmButtonText: data.warning ? '<i class="fas fa-exclamation-triangle"></i> Yes, Force Delete Item' : '<i class="fas fa-undo"></i> Yes, Delete Item & Reverse Stock'
            });

            if (!confirmRes.isConfirmed) return;
            executeDeleteGRNItem(itemId);
        }
    } catch(e) {
        PH.error('Failed to communicate with server');
    }
}

async function executeDeleteGRNItem(itemId) {
    PH.loading('Deleting Line Item...');
    try {
        const res = await fetch(API_BASE + 'pharmacy/grn-item/' + itemId, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' }
        }).then(r => r.json());
        
        if (res.success) {
            PH.success(res.message || 'Item deleted successfully');
            load();
        } else {
            PH.error(res.message || 'Failed to delete item');
        }
    } catch(e) {
        PH.error('Failed to delete item');
    }
}

function fillSupplierName(sel) {
    document.getElementById('supplier_name').value = sel.options[sel.selectedIndex].getAttribute('data-name') || '';
}
</script>
