 <?php
// Extend session to 8 hours for full-shift use
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.cookie_lifetime', 28800);
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
require_once 'includes/db.php';
$pageTitle = 'Quotations';
$db = getDB();
$suppliers = $db->query("SELECT supplier_id, supplier_name, company_name, email FROM ph_suppliers WHERE status='active' ORDER BY company_name")->fetchAll();
$indents   = $db->query("SELECT indent_no, item_name FROM ph_indent_requests WHERE status='pending' ORDER BY id DESC")->fetchAll();
include 'includes/ph_head.php';
?>
<style>
  .ph-row-selected { background: #F0F9FF !important; }
  #bulkBar {
    position: fixed; bottom: 2rem; left: 50%; transform: translateX(-50%);
    background: rgba(15, 23, 42, 0.9); backdrop-filter: blur(16px);
    padding: 1rem 2rem; border-radius: 24px; display: none;
    align-items: center; gap: 1.5rem; z-index: 1000;
    box-shadow: 0 20px 50px rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1);
    color: white; animation: slideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  }
  @keyframes slideUp { from { opacity: 0; transform: translate(-50%, 20px); } to { opacity: 1; transform: translate(-50%, 0); } }
</style>

<div class="ph-wrap">
<?php include 'includes/pharmacy_sidebar.php'; ?>
<div id="ph-content">
<?php include 'includes/pharmacy_navbar.php'; ?>
<div class="ph-page-body">

<div id="bulkBar">
  <div style="font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
    <i class="fas fa-check-circle" style="color: #10B981;"></i>
    <span id="selectedCount">0 Selected</span>
  </div>
  <div style="width: 1px; height: 24px; background: rgba(255,255,255,0.2);"></div>
  <button class="ph-btn" style="background: #0EA5E9; color: white; border-radius: 12px; font-weight: 700; padding: 0.6rem 1.2rem;" onclick="bulkSendEmail()">
    <i class="fas fa-envelope me-2"></i> Send Group Email
  </button>
  <button class="ph-btn ph-btn-outline" style="color: white; border-color: rgba(255,255,255,0.3); border-radius: 12px;" onclick="selectedIds.clear(); render();">
    Cancel
  </button>
</div>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="ph-page-title">Quotation Management</h1>
    <p class="ph-page-subtitle">Collect and compare supplier quotations</p>
  </div>
  <button class="ph-btn ph-btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Add Quotation</button>
</div>

<!-- Search -->
<div class="ph-searchbar">
  <div class="ph-search-input-wrap"><i class="fas fa-search"></i>
    <input type="text" id="searchInput" placeholder="Search quotation no, supplier, item...">
  </div>
  <select class="ph-select" id="statusFilter" style="width:160px; padding:.55rem;">
    <option value="">All Statuses</option>
    <option value="pending">Pending</option>
    <option value="approved">Approved</option>
    <option value="rejected">Rejected</option>
  </select>
  <button class="ph-btn ph-btn-outline" onclick="load()"><i class="fas fa-sync-alt"></i></button>
</div>

<!-- Quotations Table -->
<div class="ph-card">
  <div class="ph-table-wrap">
    <table class="ph-table">
      <thead>
        <tr>
          <th style="width:40px"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" style="width:20px; height:20px; accent-color: #0EA5E9;"></th>
          <th>Quotation No</th>
          <th>Date</th>
          <th>Supplier</th>
          <th>Item Name</th>
          <th>Qty</th>
          <th>Rate</th>
          <th>Total</th>
          <th>Validity</th>
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

</div></div></div>

<!-- Modal -->
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
  .compact-modal .grid-5-cols { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 16px; }
  .compact-modal .grid-4-cols { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 16px; }
  .compact-modal .grid-3-cols { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 16px; }
  .compact-modal .grid-2-cols { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 16px; }
  .compact-modal .grid-split { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
  .compact-modal .grid-item { display: flex; flex-direction: column; }
  .compact-modal .grid-item-full { grid-column: 1 / -1; display: flex; flex-direction: column; }
</style>
<div class="modal fade compact-modal" id="mainModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 1000px;">
    <div class="modal-content" style="background: #F3EFE6; border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
      <div class="modal-header" style="border-bottom: 1px solid rgba(31,107,74,0.15);">
        <h5 class="modal-title" id="modalTitle" style="color: #1F6B4A; font-weight: 900; letter-spacing: -0.5px;"><i class="fas fa-file-invoice-dollar me-2"></i>Edit Quotation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(34%) sepia(16%) saturate(1637%) hue-rotate(107deg) brightness(97%) contrast(89%); opacity: 0.8;"></button>
      </div>
      <form id="mainForm" onsubmit="save(event)">
        <div class="modal-body">
          <input type="hidden" name="id" id="id">
          
          <!-- Customer & Quotation Details -->
          <div>
            <h6><i class="fas fa-building me-1"></i>Customer & Quotation Details</h6>
            <div class="grid-3-cols">
                <div class="grid-item">
                    <label class="ph-label">Supplier *</label>
                    <select class="ph-select" name="supplier_id" id="supplier_id" required onchange="fillSupplierName(this)">
                        <option value="">-- Select Supplier --</option>
                        <?php foreach($suppliers as $s): ?>
                        <option value="<?= $s['supplier_id'] ?>" 
                                data-name="<?= htmlspecialchars($s['supplier_name'] . ' (' . $s['company_name'] . ')') ?>"
                                data-email="<?= htmlspecialchars($s['email']) ?>">
                          <?= htmlspecialchars($s['company_name']) ?> — <?= htmlspecialchars($s['supplier_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="supplier_name" id="supplier_name">
                    <input type="hidden" name="supplier_email" id="supplier_email">
                </div>
                <div class="grid-item">
                    <label class="ph-label">Indent No</label>
                    <input type="text" class="ph-input" name="indent_no" id="indent_no" list="indentList" onchange="fillItemFromIndentInput(this)" placeholder="e.g. IND-0001">
                    <datalist id="indentList">
                        <?php foreach($indents as $i): ?>
                        <option value="<?= htmlspecialchars($i['indent_no']) ?>" data-item="<?= htmlspecialchars($i['item_name']) ?>">
                          <?= htmlspecialchars($i['item_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="grid-item">
                    <label class="ph-label">Item Name *</label>
                    <input type="text" class="ph-input" name="item_name" id="item_name" required>
                </div>
            </div>
          </div>

          <!-- Pricing -->
          <div>
            <h6><i class="fas fa-tags me-1"></i>Pricing</h6>
            <div class="grid-5-cols">
                <div class="grid-item">
                    <label class="ph-label">Qty</label>
                    <input type="number" class="ph-input" name="qty" id="qty" min="1" value="1" onchange="calcTotal()">
                </div>
                <div class="grid-item">
                    <label class="ph-label">Unit</label>
                    <input type="text" class="ph-input" name="unit" id="unit" list="unitList" placeholder="Pieces">
                </div>
                <div class="grid-item">
                    <label class="ph-label">Rate (₹)</label>
                    <input type="number" class="ph-input" name="rate" id="rate" step="0.01" min="0" value="0" onchange="calcTotal()">
                </div>
                <div class="grid-item">
                    <label class="ph-label">Tax (%)</label>
                    <input type="number" class="ph-input" name="tax_percent" id="tax_percent" step="0.01" min="0" value="0" onchange="calcTotal()">
                </div>
                <div class="grid-item">
                    <label class="ph-label">Total Amount (₹)</label>
                    <input type="text" class="ph-input" id="total_display" readonly style="background:#E3F2EC; font-weight:800;">
                    <input type="hidden" name="tax_amount" id="tax_amount">
                    <input type="hidden" name="total_amount" id="total_amount">
                </div>
            </div>
          </div>

          <!-- Terms & Status -->
          <div>
            <h6><i class="fas fa-clipboard-check me-1"></i>Terms & Status</h6>
            <div class="grid-4-cols" style="align-items: center;">
                <div class="grid-item">
                    <label class="ph-label">Delivery Days</label>
                    <input type="number" class="ph-input" name="delivery_days" id="delivery_days" min="0" value="7">
                </div>
                <div class="grid-item">
                    <label class="ph-label">Validity Date</label>
                    <input type="date" class="ph-input" name="validity_date" id="validity_date">
                </div>
                <div class="grid-item">
                    <label class="ph-label">Status</label>
                    <select class="ph-select" name="status" id="status">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="grid-item" style="flex-direction: row; align-items: center; padding-top: 18px;">
                    <input class="form-check-input me-2" type="checkbox" name="send_mail" value="1" id="send_mail" style="width:1.2rem; height:1.2rem; margin:0; accent-color:#1F6B4A;">
                    <label class="ph-label" for="send_mail" style="cursor:pointer; margin:0; margin-left: 4px;">Notify Supplier</label>
                </div>
            </div>
          </div>
          
          <div>
            <label class="ph-label">Remarks</label>
            <textarea class="ph-textarea" name="remarks" id="remarks" rows="2"></textarea>
          </div>

        </div>
        <div class="modal-footer" style="background: #F3EFE6; border-top: 1px solid rgba(31,107,74,0.15); border-radius: 0 0 12px 12px;">
          <button type="button" class="btn btn-sm" data-bs-dismiss="modal" style="background: transparent; color: #1F6B4A; border: 1.5px solid rgba(31,107,74,0.2); border-radius: 8px; font-weight: 700;">Cancel</button>
          <button type="submit" class="btn btn-sm" style="background: #1F6B4A; color: #FFFFFF; border: none; border-radius: 8px; font-weight: 700; box-shadow: 0 4px 10px rgba(31,107,74,0.2);">
            <i class="fas fa-check-circle me-1"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Email Modal -->
<div class="modal fade compact-modal" id="emailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
    <div class="modal-content" style="background: #F3EFE6; border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
      <div class="modal-header" style="border-bottom: 1px solid rgba(31,107,74,0.15);">
        <h5 class="modal-title" style="color: #1F6B4A; font-weight: 900; letter-spacing: -0.5px;"><i class="fas fa-envelope me-2"></i>Email Notification</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(34%) sepia(16%) saturate(1637%) hue-rotate(107deg) brightness(97%) contrast(89%); opacity: 0.8;"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="ph-label">Recipient Email *</label>
          <select class="ph-select w-100" id="emailTo">
            <option value="">Select a recipient...</option>
            <?php foreach($suppliers as $s): ?>
              <option value="<?= htmlspecialchars($s['email']) ?>"><?= htmlspecialchars($s['company_name'] . " (" . $s['email'] . ")") ?></option>
            <?php endforeach; ?>
          </select>
          <div class="small mt-1 px-1" style="color: #1F6B4A; font-weight: 600; font-size:0.65rem;">Or type a custom email below:</div>
          <input type="email" class="ph-input mt-1" id="customEmail" placeholder="custom@example.com">
        </div>
        <div class="mb-2">
          <label class="ph-label">Subject Line</label>
          <input type="text" class="ph-input" id="emailSubject" value="Pharmacy Quotation Notification">
        </div>
        <div>
          <label class="ph-label">Message Template</label>
          <textarea class="ph-textarea" id="emailBody" rows="4"></textarea>
        </div>
      </div>
      <div class="modal-footer" style="background: #F3EFE6; border-top: 1px solid rgba(31,107,74,0.15); border-radius: 0 0 12px 12px;">
        <button type="button" class="btn btn-sm" data-bs-dismiss="modal" style="background: transparent; color: #1F6B4A; border: 1.5px solid rgba(31,107,74,0.2); border-radius: 8px; font-weight: 700;">Discard</button>
        <button type="button" class="btn btn-sm" style="background: #1F6B4A; color: #FFFFFF; border: none; border-radius: 8px; font-weight: 700; box-shadow: 0 4px 10px rgba(31,107,74,0.2);" onclick="sendEmailNow()">
          <i class="fas fa-paper-plane me-1"></i> Dispatch Email
        </button>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/ph_foot.php'; ?>
<script>
let all = [], currentPage = 1, PER_PAGE = 10, selectedIds = new Set(), filteredData = [];
const modal = new bootstrap.Modal(document.getElementById('mainModal'));

document.addEventListener('DOMContentLoaded', () => {
    load();
    document.getElementById('searchInput').addEventListener('input', () => { currentPage = 1; render(); });
    document.getElementById('statusFilter').addEventListener('change', () => { currentPage = 1; render(); });
});

async function load() {
    const res = await phGet(API_BASE + 'pharmacy/quotations?_t=' + new Date().getTime());
    if (res.success) { all = res.data; render(); } else PH.error(res.message);
}

function render() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    const sf = document.getElementById('statusFilter').value;
    filteredData = all;
    if (q) filteredData = filteredData.filter(x => (x.quotation_no||'').toLowerCase().includes(q) || (x.supplier_name||'').toLowerCase().includes(q) || (x.item_name||'').toLowerCase().includes(q));
    if (sf) filteredData = filteredData.filter(x => x.status === sf);
    
    const pager = phPaginate(filteredData, currentPage, PER_PAGE);
    let html = '';
    if (!pager.items.length) { html = `<tr><td colspan="11" class="text-center py-4 text-muted">No quotations found.</td></tr>`; }
    else pager.items.forEach(x => {
        const valid = x.validity_date ? (new Date(x.validity_date) < new Date() ? `<span class="text-danger">${fmt.date(x.validity_date)} (expired)</span>` : fmt.date(x.validity_date)) : '—';
        const isSelected = selectedIds.has(x.id);
        html += `<tr class="${isSelected ? 'ph-row-selected' : ''}" onclick="toggleRow(${x.id}, !selectedIds.has(${x.id}))">
            <td onclick="event.stopPropagation()"><input type="checkbox" ${isSelected ? 'checked' : ''} onchange="toggleRow(${x.id}, this.checked)" style="width:20px; height:20px; accent-color: #0EA5E9;"></td>
            <td><span class="ph-badge badge-muted">${x.quotation_no}</span></td>
            <td>${fmt.date(x.quotation_date)}</td>
            <td>${x.supplier_name || '—'}</td>
            <td>${x.item_name}</td>
            <td>${x.qty}</td>
            <td>${fmt.currency(x.rate)}</td>
            <td class="fw-bold">${fmt.currency(x.total_amount)}</td>
            <td>${valid}</td>
            <td>${statusBadge(x.status)}</td>
            <td class="text-end">
                ${x.status === 'approved' ? `
                <button class="ph-btn ph-btn-sm ph-btn-success ph-btn-icon me-1" onclick="generatePO(${x.id})" title="Generate PO">
                    <i class="fas fa-file-invoice"></i>
                </button>` : ''}
                <button class="ph-btn ph-btn-sm ph-btn-outline ph-btn-icon me-1" onclick="edit(${x.id})"><i class="fas fa-edit"></i></button>
                <button class="ph-btn ph-btn-sm ph-btn-outline ph-btn-icon text-danger" onclick="del(${x.id})"><i class="fas fa-trash"></i></button>
            </td></tr>`;
    });
    document.getElementById('tableBody').innerHTML = html;
    phRenderPager(document.getElementById('pager'), pager.pages, currentPage, p => { currentPage = p; render(); });
    updateBulkBar();
}

async function toggleRow(id, checked) {
    const x = all.find(q => q.id == id);
    if (!x) return;

    if (checked) {
        selectedIds.add(id);
        if (x.status !== 'approved') {
            x.status = 'approved';
            try {
                await fetch(API_BASE + 'pharmacy/quotations', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(x)
                });
                PH.success('Quotation Auto-Approved');
            } catch(e) {}
        }
    } else {
        selectedIds.delete(id);
        if (x.status === 'approved') {
            x.status = 'pending';
            try {
                await fetch(API_BASE + 'pharmacy/quotations', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(x)
                });
                PH.success('Quotation reverted to Pending');
            } catch(e) {}
        }
    }
    render();
}

async function toggleSelectAll(cb) {
    const pager = phPaginate(filteredData, currentPage, PER_PAGE);
    let updated = false;

    if (cb.checked) {
        for (const x of pager.items) {
            selectedIds.add(x.id);
            if (x.status !== 'approved') {
                x.status = 'approved';
                updated = true;
                try {
                    await fetch(API_BASE + 'pharmacy/quotations', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(x)
                    });
                } catch(e) {}
            }
        }
        if (updated) PH.success('Quotations Auto-Approved');
    } else {
        for (const x of pager.items) {
            selectedIds.delete(x.id);
            if (x.status === 'approved') {
                x.status = 'pending';
                updated = true;
                try {
                    await fetch(API_BASE + 'pharmacy/quotations', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(x)
                    });
                } catch(e) {}
            }
        }
        if (updated) PH.success('Quotations reverted to Pending');
    }
    render();
}

function updateBulkBar() {
    const bar = document.getElementById('bulkBar');
    if (selectedIds.size > 0) {
        bar.style.display = 'flex';
        document.getElementById('selectedCount').textContent = `${selectedIds.size} Selected`;
    } else bar.style.display = 'none';
}

async function bulkSendEmail() {
    if (!selectedIds.size) return;
    const selectedItems = all.filter(x => selectedIds.has(x.id));
    
    // Group by supplier
    const bySupplier = selectedItems.reduce((acc, x) => {
        if (!acc[x.supplier_id]) acc[x.supplier_id] = { name: x.supplier_name, email: '', items: [] };
        acc[x.supplier_id].items.push(x);
        return acc;
    }, {});

    // Try to find emails for these suppliers
    const supplierSelect = document.getElementById('supplier_id');
    for (let opt of supplierSelect.options) {
        if (bySupplier[opt.value]) bySupplier[opt.value].email = opt.getAttribute('data-email');
    }

    PH.loading('Dispatching Group Emails...');
    let successCount = 0;
    
    for (let sid in bySupplier) {
        const s = bySupplier[sid];
        if (!s.email) continue;

        const tableRows = s.items.map(item => `
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #F1F5F9; font-weight: 700;">${item.quotation_no}</td>
                <td style="padding: 10px; border-bottom: 1px solid #F1F5F9;">${item.item_name}</td>
                <td style="padding: 10px; border-bottom: 1px solid #F1F5F9;">${item.qty} ${item.unit || 'Units'}</td>
                <td style="padding: 10px; border-bottom: 1px solid #F1F5F9; font-weight: 700; color: #059669;">₹${parseFloat(item.total_amount).toLocaleString('en-IN')}</td>
            </tr>
        `).join('');

        const htmlBody = `
            <div style="font-family: sans-serif; color: #334155; max-width: 600px; margin: 20px auto; border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                <div style="background: #0EA5E9; padding: 25px; text-align: center; color: white;">
                    <h2 style="margin: 0; font-size: 22px; font-weight: 800;">Consolidated Approval Notice</h2>
                    <p style="margin: 5px 0 0; opacity: 0.9; font-weight: 600;">GM Hospital Procurement Selection</p>
                </div>
                <div style="padding: 40px; background: white;">
                    <p style="font-size: 16px; margin-top: 0;">Dear Partner,</p>
                    <p style="font-size: 15px; line-height: 1.6;">We are pleased to inform you that the following quotations have been <strong>SELECTED and APPROVED</strong>. Our team will follow up with formal Purchase Orders for these items.</p>
                    
                    <div style="margin: 25px 0; border: 1px solid #F1F5F9; border-radius: 12px; overflow: hidden;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                            <thead style="background: #F8FAFC;">
                                <tr>
                                    <th style="padding: 12px 10px; text-align: left; color: #64748B;">REF NO</th>
                                    <th style="padding: 12px 10px; text-align: left; color: #64748B;">ITEM</th>
                                    <th style="padding: 12px 10px; text-align: left; color: #64748B;">QTY</th>
                                    <th style="padding: 12px 10px; text-align: left; color: #64748B;">TOTAL</th>
                                </tr>
                            </thead>
                            <tbody>${tableRows}</tbody>
                        </table>
                    </div>

                    <p style="font-size: 15px;">Thank you for your partnership.</p>
                    
                    <div style="text-align: center; margin: 35px 0;">
                        <a href="http://localhost/GM_HMS/vendor/vendor_view/" style="background: #0EA5E9; color: white; text-decoration: none; padding: 12px 25px; border-radius: 8px; font-weight: 700; font-size: 15px; display: inline-block;">
                            ACCESS VENDOR PORTAL
                        </a>
                    </div>

                    <div style="margin-top: 40px; padding-top: 25px; border-top: 1px solid #F1F5F9; font-size: 12px; color: #94A3B8; text-align: center;">
                        <strong>GM Hospital Management System</strong>
                    </div>
                </div>
            </div>
        `;

        try {
            const res = await fetch('send_email.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    email_to: s.email,
                    subject: `[FINAL SELECTION] Consolidated Approval for ${s.items.length} Items`,
                    body: htmlBody
                })
            }).then(r => r.json());
            if (res.success) successCount++;
        } catch (e) {}
    }

    PH.success(`Dispatched notifications to ${successCount} vendors`);
    selectedIds.clear();
    render();
}

function openModal() {
    document.getElementById('mainForm').reset();
    document.getElementById('id').value = '';
    document.getElementById('modalTitle').textContent = 'Add Quotation';
    document.getElementById('validity_date').valueAsDate = new Date(Date.now() + 30*86400000);
    const sendMailEl = document.getElementById('send_mail');
    if (sendMailEl) sendMailEl.checked = false;
    calcTotal();
    modal.show();
}

function edit(id) {
    const x = all.find(q => q.id == id);
    if (!x) return;

    document.getElementById('id').value = x.id;
    document.getElementById('modalTitle').textContent = 'Edit Quotation';

    ['supplier_id','supplier_name','indent_no','item_name','qty','unit','rate','tax_percent','tax_amount','total_amount','delivery_days','validity_date','status','remarks'].forEach(f => {
        const el = document.getElementById(f);
        if (el) el.value = x[f] || '';
    });
    
    // Set supplier_email for email notifications
    const supplierSel = document.getElementById('supplier_id');
    if (supplierSel && supplierSel.selectedIndex >= 0) {
        const opt = supplierSel.options[supplierSel.selectedIndex];
        document.getElementById('supplier_email').value = opt.getAttribute('data-email') || '';
    }
    
    const sendMailEl = document.getElementById('send_mail');
    if (sendMailEl) sendMailEl.checked = false;

    calcTotal();
    modal.show();
}

async function save(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    const data = Object.fromEntries(fd.entries());
    
    PH.loading('Saving...');
    try {
        const res = await fetch(API_BASE + 'pharmacy/quotations', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        }).then(r => r.json());
        
        if (res.success) { 
            // If approved and checkbox is checked, send email
            if (data.status === 'approved' && data.send_mail === '1') {
                await sendApprovalEmail(data);
            }
            PH.success(res.message); 
            modal.hide(); 
            load(); 
        } else PH.error(res.message);
    } catch(e) { PH.error('Failed to save'); }
}

async function sendApprovalEmail(q) {
    const to = q.supplier_email || document.getElementById('supplier_email').value;
    if (!to) return;

    const htmlBody = `
        <div style="font-family: sans-serif; color: #334155; max-width: 600px; margin: 20px auto; border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <div style="background: #10B981; padding: 25px; text-align: center; color: white;">
                <h2 style="margin: 0; font-size: 22px; font-weight: 800;">Final Approval Notice</h2>
                <p style="margin: 5px 0 0; opacity: 0.9; font-weight: 600;">GM Hospital Procurement Selection</p>
            </div>
            <div style="padding: 40px; background: white;">
                <p style="font-size: 16px; margin-top: 0;">Dear Partner,</p>
                <p style="font-size: 15px; line-height: 1.6;">We are pleased to inform you that after a comprehensive review of all submissions, your quotation has been selected as the **FINAL APPROVED** bid for this requirement. A formal Purchase Order is being processed and will be issued shortly.</p>
                
                <div style="background: #F8FAFC; padding: 25px; border-radius: 12px; margin: 25px 0; border: 1px solid #F1F5F9;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr><td style="padding: 8px 0; color: #64748B; font-size: 13px;">ITEM NAME</td><td style="padding: 8px 0; font-weight: 700; color: #1E293B;">${q.item_name}</td></tr>
                        <tr><td style="padding: 8px 0; color: #64748B; font-size: 13px;">QUANTITY</td><td style="padding: 8px 0; font-weight: 700; color: #1E293B;">${q.qty} ${q.unit || 'Units'}</td></tr>
                        <tr><td style="padding: 8px 0; color: #64748B; font-size: 13px;">TOTAL VALUE</td><td style="padding: 8px 0; font-weight: 700; color: #059669; font-size: 16px;">₹${parseFloat(q.total_amount).toLocaleString('en-IN')}</td></tr>
                        <tr><td style="padding: 8px 0; color: #64748B; font-size: 13px;">EST. DELIVERY</td><td style="padding: 8px 0; font-weight: 700; color: #1E293B;">Within ${q.delivery_days} Days</td></tr>
                    </table>
                </div>

                <p style="font-size: 15px;">Thank you for your partnership and for choosing to grow with GM Hospital.</p>
                
                <div style="text-align: center; margin: 35px 0;">
                    <a href="http://localhost/GM_HMS/vendor/vendor_view/" style="background: #10B981; color: white; text-decoration: none; padding: 12px 25px; border-radius: 8px; font-weight: 700; font-size: 15px; display: inline-block;">
                        ACCESS VENDOR PORTAL
                    </a>
                </div>

                <div style="margin-top: 40px; padding-top: 25px; border-top: 1px solid #F1F5F9; font-size: 12px; color: #94A3B8; text-align: center;">
                    <strong>GM Hospital Management System</strong><br>
                    This is an automated notification regarding procurement status. Please do not reply directly to this email.
                </div>
            </div>
        </div>
    `;

    try {
        await fetch('send_email.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                email_to: to,
                subject: `[APPROVED] Procurement Selection: ${q.item_name}`,
                body: htmlBody
            })
        });
    } catch (e) { console.error('Approval notification failed', e); }
}

function generatePO(id) {
    const x = all.find(q => q.id == id);
    if (!x) return;
    window.location.href = `purchase_order.php?quotation_id=${x.id}&supplier_id=${x.supplier_id}`;
}

function del(id) {
    PH.confirm('Delete Quotation?', '', async () => {
        PH.loading('Deleting...');
        try {
            const res = await fetch(API_BASE + 'pharmacy/quotations/' + id, { method: 'DELETE' }).then(r => r.json());
            if (res.success) { PH.success('Deleted'); load(); } else PH.error(res.message);
        } catch(e) { PH.error('Failed to delete'); }
    });
}

function calcTotal() {
    const qty = parseFloat(document.getElementById('qty').value) || 0;
    const rate = parseFloat(document.getElementById('rate').value) || 0;
    const tax  = parseFloat(document.getElementById('tax_percent').value) || 0;
    const subtotal = qty * rate;
    const taxAmt   = subtotal * tax / 100;
    const total    = subtotal + taxAmt;
    document.getElementById('tax_amount').value = taxAmt.toFixed(2);
    document.getElementById('total_amount').value = total.toFixed(2);
    document.getElementById('total_display').value = '₹' + total.toFixed(2);
}

function fillSupplierName(sel) {
    const opt = sel.options[sel.selectedIndex];
    document.getElementById('supplier_name').value = opt.getAttribute('data-name') || '';
    document.getElementById('supplier_email').value = opt.getAttribute('data-email') || '';
}

function fillItemFromIndentInput(inp) {
    const list = document.getElementById('indentList');
    if (!list) return;
    const opt = Array.from(list.options).find(o => o.value === inp.value);
    if (opt) {
        const item = opt.getAttribute('data-item');
        if (item) document.getElementById('item_name').value = item;
    }
}
let currentEmailItem = null;
const emailModal = new bootstrap.Modal(document.getElementById('emailModal'));

function openEmailModal(id) {
    const x = all.find(q => q.id == id);
    if (!x) return;
    currentEmailItem = x;
    
    document.getElementById('emailTo').value = '';
    document.getElementById('customEmail').value = '';
    
    // Find supplier email
    const supplierSelect = document.getElementById('supplier_id');
    for (let opt of supplierSelect.options) {
        if (opt.value == x.supplier_id) {
            document.getElementById('emailTo').value = opt.getAttribute('data-email') || '';
            break;
        }
    }
    
    const isApproved = x.status === 'approved';
    document.getElementById('emailSubject').value = isApproved 
        ? `[FINAL SELECTION] Official Approval for Quotation: ${x.quotation_no}` 
        : `Notification regarding Quotation: ${x.quotation_no}`;
    
    document.getElementById('emailBody').value = isApproved
        ? `Dear Partner,\n\nWe are pleased to inform you that after careful review, your quotation (${x.quotation_no}) for ${x.item_name} has been officially SELECTED and APPROVED as our final choice. \n\nOur procurement team will follow up with a formal Purchase Order shortly. Thank you for your partnership.\n\nBest Regards,\nGM Hospital Pharmacy`
        : `Dear Partner,\n\nThis is a notification regarding your quotation (${x.quotation_no}) for ${x.item_name}. \n\nKindly check the status in the vendor portal or contact us for further details.\n\nBest Regards,\nGM Hospital Pharmacy`;
    
    emailModal.show();
}

async function sendEmailNow() {
    const selectTo = document.getElementById('emailTo').value;
    const customTo = document.getElementById('customEmail').value.trim();
    const to = customTo || selectTo;
    const subject = document.getElementById('emailSubject').value.trim();
    const message = document.getElementById('emailBody').value.trim().replace(/\n/g, '<br>');
    
    if (!to) { PH.error('Please select or enter a recipient email'); return; }
    if (!currentEmailItem) return;

    const tableHtml = `
        <div style="background: #F8FAFC; padding: 25px; border-radius: 12px; margin: 25px 0; border: 1px solid #F1F5F9;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr><td style="padding: 8px 0; color: #64748B; font-size: 13px;">QUOTATION NO</td><td style="padding: 8px 0; font-weight: 700; color: #1E293B;">${currentEmailItem.quotation_no}</td></tr>
                <tr><td style="padding: 8px 0; color: #64748B; font-size: 13px;">ITEM NAME</td><td style="padding: 8px 0; font-weight: 700; color: #1E293B;">${currentEmailItem.item_name}</td></tr>
                <tr><td style="padding: 8px 0; color: #64748B; font-size: 13px;">QUANTITY</td><td style="padding: 8px 0; font-weight: 700; color: #1E293B;">${currentEmailItem.qty} ${currentEmailItem.unit || 'Units'}</td></tr>
                <tr><td style="padding: 8px 0; color: #64748B; font-size: 13px;">TOTAL VALUE</td><td style="padding: 8px 0; font-weight: 700; color: #059669; font-size: 16px;">₹${parseFloat(currentEmailItem.total_amount).toLocaleString('en-IN')}</td></tr>
                <tr><td style="padding: 8px 0; color: #64748B; font-size: 13px;">STATUS</td><td style="padding: 8px 0; font-weight: 700; color: #1E293B; text-transform: uppercase;">${currentEmailItem.status}</td></tr>
            </table>
        </div>
    `;

    const fullHtmlBody = `
        <div style="font-family: sans-serif; color: #334155; max-width: 600px; margin: 20px auto; border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <div style="background: #0EA5E9; padding: 25px; text-align: center; color: white;">
                <h2 style="margin: 0; font-size: 22px; font-weight: 800;">Quotation Update</h2>
                <p style="margin: 5px 0 0; opacity: 0.9; font-weight: 600;">GM Hospital Procurement</p>
            </div>
            <div style="padding: 40px; background: white;">
                <div style="font-size: 15px; line-height: 1.6; color: #475569;">${message}</div>
                ${tableHtml}
                <div style="margin-top: 40px; padding-top: 25px; border-top: 1px solid #F1F5F9; font-size: 12px; color: #94A3B8; text-align: center;">
                    <strong>GM Hospital Management System</strong><br>
                    This is an automated notification regarding your quotation status.
                </div>
            </div>
        </div>
    `;

    PH.loading('Dispatching Email...');
    try {
        const res = await fetch('send_email.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email_to: to, subject: subject, body: fullHtmlBody })
        }).then(r => r.json());
        
        if (res.success) {
            PH.success('Notification sent to ' + to);
            emailModal.hide();
        } else PH.error(res.message);
    } catch (e) { PH.error('Dispatch failed'); }
}
</script>
