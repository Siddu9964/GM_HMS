<?php
// Extend session to 8 hours for full-shift use
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.cookie_lifetime', 28800);
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
require_once 'includes/db.php';
$pageTitle = 'Purchase Orders';
$db = getDB();
$suppliers = $db->query("SELECT supplier_id, supplier_name, company_name FROM ph_suppliers WHERE status='active' ORDER BY company_name")->fetchAll();
$products  = $db->query("SELECT product_id, product_name FROM ph_product ORDER BY product_name")->fetchAll();
include 'includes/ph_head.php';
?>
<div class="ph-wrap">
<?php include 'includes/pharmacy_sidebar.php'; ?>
<div id="ph-content">
<?php include 'includes/pharmacy_navbar.php'; ?>
<div class="ph-page-body">

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="ph-page-title">Purchase Orders</h1>
    <p class="ph-page-subtitle">Create and track purchase orders to suppliers</p>
  </div>
  <button class="ph-btn ph-btn-primary" onclick="openPOModal()"><i class="fas fa-plus"></i> New PO</button>
</div>

<!-- Search -->
<div class="ph-searchbar">
  <div class="ph-search-input-wrap"><i class="fas fa-search"></i>
    <input type="text" id="searchInput" placeholder="Search PO no, supplier...">
  </div>
  <select class="ph-select" id="statusFilter" style="width:160px; padding:.55rem;">
    <option value="">All Statuses</option>
    <option value="draft">Draft</option>
    <option value="ordered">Ordered</option>
    <option value="received">Received</option>
    <option value="cancelled">Cancelled</option>
  </select>
  <button class="ph-btn ph-btn-outline" onclick="load()"><i class="fas fa-sync-alt"></i></button>
</div>

<!-- PO Table -->
<div class="ph-card">
  <div class="ph-table-wrap">
    <table class="ph-table">
      <thead>
        <tr>
          <th>PO No</th>
          <th>Date</th>
          <th>Supplier</th>
          <th>Expected Date</th>
          <th>Grand Total</th>
          <th>Status</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody id="tableBody">
        <tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
      </tbody>
    </table>
  </div>
  <div class="ph-card-body pt-0 pb-3">
    <div id="pager" class="ph-pagination justify-content-end"></div>
  </div>
</div>

</div></div></div>

<!-- PO Create/Edit Modal -->
<style>
  .compact-modal .ph-label { font-size: 0.65rem; font-weight: 800; color: #1F6B4A; margin-bottom: 2px; text-transform: uppercase; }
  .compact-modal .ph-input, .compact-modal .ph-select, .compact-modal .ph-textarea { padding: 4px 8px; font-size: 0.8rem; height: 32px; border: 1px solid rgba(31,107,74,0.2); border-radius: 6px; background: #FFF; color: #1F6B4A; font-weight: 600; width: 100%; box-shadow: none; box-sizing: border-box; }
  .compact-modal .ph-textarea { height: auto; min-height: 48px; }
  .compact-modal .ph-input:focus, .compact-modal .ph-select:focus, .compact-modal .ph-textarea:focus { border-color: #1F6B4A; outline: none; box-shadow: 0 0 0 2px rgba(31,107,74,0.1); }
  .compact-modal h6 { font-size: 0.8rem; margin-top: 4px; margin-bottom: 8px !important; color: #1F6B4A; font-weight: 800; border-bottom: 1px solid rgba(31,107,74,0.1); padding-bottom: 4px; }
  .compact-modal .modal-body { padding: 12px 20px; }
  .compact-modal .modal-header, .compact-modal .modal-footer { padding: 10px 20px; }
  .compact-modal .grid-3-cols { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 16px; }
  .compact-modal .grid-item { display: flex; flex-direction: column; }
</style>
<div class="modal fade compact-modal" id="mainModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 900px;">
    <div class="modal-content" style="background: #F3EFE6; border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
      <div class="modal-header" style="border-bottom: 1px solid rgba(31,107,74,0.15);">
        <h5 class="modal-title" id="modalTitle" style="color: #1F6B4A; font-weight: 900; letter-spacing: -0.5px;">New Purchase Order</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(34%) sepia(16%) saturate(1637%) hue-rotate(107deg) brightness(97%) contrast(89%); opacity: 0.8;"></button>
      </div>
      <form id="mainForm" onsubmit="save(event)">
        <div class="modal-body">
          <input type="hidden" name="po_id" id="po_id">
          
          <div>
            <h6><i class="fas fa-file-invoice-dollar me-1"></i>Order Information</h6>
            <div class="grid-3-cols">
                <div class="grid-item" style="grid-column: span 2;">
                    <label class="ph-label">Supplier *</label>
                    <select class="ph-select" name="supplier_id" id="supplier_id" required onchange="fillSupplier(this)">
                        <option value="">-- Select Supplier --</option>
                        <?php foreach($suppliers as $s): ?>
                        <option value="<?= $s['supplier_id'] ?>" data-name="<?= htmlspecialchars($s['company_name'] . ' — ' . $s['supplier_name']) ?>">
                          <?= htmlspecialchars($s['company_name']) ?> — <?= htmlspecialchars($s['supplier_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="supplier_name" id="supplier_name">
                </div>
                <div class="grid-item">
                    <label class="ph-label">Expected Delivery Date</label>
                    <input type="date" class="ph-input" name="expected_date" id="expected_date">
                </div>
                <div class="grid-item">
                    <label class="ph-label">Status</label>
                    <select class="ph-select" name="status" id="status">
                        <option value="draft">Draft</option>
                        <option value="ordered">Ordered</option>
                        <option value="received">Received</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="grid-item" style="grid-column: span 2;">
                    <label class="ph-label">Remarks</label>
                    <textarea class="ph-textarea" name="remarks" id="remarks" rows="1"></textarea>
                </div>
            </div>
          </div>
          <!-- Items section removed as requested -->
        </div>
        <div class="modal-footer" style="background: #F3EFE6; border-top: 1px solid rgba(31,107,74,0.15); border-radius: 0 0 12px 12px;">
          <button type="button" class="btn btn-sm" data-bs-dismiss="modal" style="background: transparent; color: #1F6B4A; border: 1.5px solid rgba(31,107,74,0.2); border-radius: 8px; font-weight: 700;">Cancel</button>
          <button type="submit" class="btn btn-sm" style="background: #1F6B4A; color: #FFFFFF; border: none; border-radius: 8px; font-weight: 700; box-shadow: 0 4px 10px rgba(31,107,74,0.2);">
            <i class="fas fa-save me-1"></i> Save PO
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- PO View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="background: #F3EFE6; border-radius: 20px; border: none; box-shadow: 0 25px 50px -12px rgba(31, 107, 74, 0.2);">
      <div class="modal-header px-4 py-3" style="border-bottom: 1px solid rgba(31,107,74,0.15);">
        <h5 class="modal-title" id="viewModalTitle" style="color: #1F6B4A; font-weight: 900; letter-spacing: -0.5px;">View Purchase Order</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(34%) sepia(16%) saturate(1637%) hue-rotate(107deg) brightness(97%) contrast(89%); opacity: 0.8;"></button>
      </div>
      <div class="modal-body p-4" id="viewModalBody">Loading...</div>
    </div>
  </div>
</div>

<?php include 'includes/ph_foot.php'; ?>
<script>
let all = [], rowCount = 0, currentPage = 1, PER_PAGE = 10;
const modal     = new bootstrap.Modal(document.getElementById('mainModal'));
const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));

const products = <?= json_encode($products) ?>;

document.addEventListener('DOMContentLoaded', () => {
    load();
    document.getElementById('searchInput').addEventListener('input', () => { currentPage = 1; render(); });
    document.getElementById('statusFilter').addEventListener('change', () => { currentPage = 1; render(); });
});

async function load() {
    const res = await phGet(API_BASE + 'pharmacy/purchase-orders');
    if (res.success) { all = res.data; render(); } else PH.error(res.message);
}

function render() {
    const q  = document.getElementById('searchInput').value.toLowerCase();
    const sf = document.getElementById('statusFilter').value;
    let filtered = all;
    if (q) filtered = filtered.filter(x => (x.po_no||'').toLowerCase().includes(q) || (x.supplier_name||'').toLowerCase().includes(q));
    if (sf) filtered = filtered.filter(x => x.status === sf);
    const pager = phPaginate(filtered, currentPage, PER_PAGE);
    let html = '';
    if (!pager.items.length) { html = `<tr><td colspan="7" class="text-center py-4 text-muted">No purchase orders found.</td></tr>`; }
    else pager.items.forEach(x => {
        html += `<tr>
            <td><span class="ph-badge badge-muted fw-bold">${x.po_no}</span></td>
            <td>${fmt.date(x.po_date)}</td>
            <td>${x.supplier_name || '—'}</td>
            <td>${fmt.date(x.expected_date)}</td>
            <td class="fw-bold text-primary">${fmt.currency(x.grand_total)}</td>
            <td>${statusBadge(x.status)}</td>
            <td class="text-end">
                <button class="ph-btn ph-btn-sm ph-btn-outline ph-btn-icon me-1" onclick="viewPO(${x.id})" title="View"><i class="fas fa-eye"></i></button>
                <button class="ph-btn ph-btn-sm ph-btn-outline ph-btn-icon me-1" onclick='editPO(${JSON.stringify(x).replace(/'/g,"&apos;")})' title="Edit"><i class="fas fa-edit"></i></button>
                <button class="ph-btn ph-btn-sm ph-btn-outline ph-btn-icon text-danger" onclick="del(${x.id})" title="Delete"><i class="fas fa-trash"></i></button>
            </td></tr>`;
    });
    document.getElementById('tableBody').innerHTML = html;
    phRenderPager(document.getElementById('pager'), pager.pages, currentPage, p => { currentPage = p; render(); });
}

function openPOModal() {
    document.getElementById('mainForm').reset();
    document.getElementById('po_id').value = '';
    document.getElementById('modalTitle').textContent = 'New Purchase Order';
    document.getElementById('expected_date').valueAsDate = new Date(Date.now() + 7*86400000);
    modal.show();
}

function editPO(x) {
    document.getElementById('modalTitle').textContent = 'Edit Purchase Order — ' + x.po_no;
    document.getElementById('po_id').value = x.id;
    document.getElementById('supplier_id').value = x.supplier_id || '';
    document.getElementById('supplier_name').value = x.supplier_name || '';
    document.getElementById('expected_date').value = x.expected_date || '';
    document.getElementById('status').value = x.status || 'draft';
    document.getElementById('remarks').value = x.remarks || '';
    modal.show();
}

async function viewPO(id) {
    document.getElementById('viewModalBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
    viewModal.show();
    const res = await phGet(API_BASE + 'pharmacy/purchase-orders/' + id);
    if (res.success) {
        const x = res.data.po;
        
        let downloadBtn = '';
        let printBtn = '';
        let attachmentHtml = '';

        if (x.attachment) {
            const ext = x.attachment.split('.').pop().toLowerCase();
            const filePath = '../' + x.attachment;
            
            downloadBtn = `<a href="${filePath}" download class="ph-btn ph-btn-outline-primary ph-btn-sm me-2"><i class="fas fa-download me-1"></i> Download</a>`;
            printBtn = `<button onclick="printAttachment('${filePath}')" class="ph-btn ph-btn-primary ph-btn-sm"><i class="fas fa-print me-1"></i> Print</button>`;

            if (['png','jpg','jpeg','gif','webp'].includes(ext)) {
                attachmentHtml = `<img src="${filePath}" style="width:100%; height:auto; border-radius:8px; border:1px solid #e2e8f0;">`;
            } else if (ext === 'pdf') {
                attachmentHtml = `<iframe src="${filePath}" width="100%" height="650px" style="border:1px solid #e2e8f0; border-radius:8px;"></iframe>`;
            } else {
                attachmentHtml = `<div class="p-5 text-center text-muted" style="border:1.5px dashed var(--ph-border); border-radius:8px;"><i class="fas fa-file-alt fs-1 mb-3"></i><br>Preview not available for this file type.</div>`;
            }
        } else {
            attachmentHtml = `<div class="p-5 text-center text-muted" style="border:1.5px dashed var(--ph-border); border-radius:8px; background:#F8FAFC;"><i class="fas fa-file-excel fs-1 mb-3 text-secondary"></i><br>No attachment provided for this order.</div>`;
        }

        if(document.getElementById('viewModalTitle')) {
            document.getElementById('viewModalTitle').innerHTML = `View PO: <span class="text-primary">${x.po_no}</span>`;
        }

        document.getElementById('viewModalBody').innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-3 p-2 rounded" style="background: #f8fafc; font-size: 0.85rem; border: 1px solid #e2e8f0;">
                <div><span class="text-muted">Supplier:</span> <strong>${x.supplier_name}</strong></div>
                <div><span class="text-muted">PO Date:</span> <strong>${fmt.date(x.po_date)}</strong></div>
                <div><span class="text-muted">Status:</span> ${statusBadge(x.status)}</div>
                <div><span class="text-muted">Total:</span> <strong class="text-primary fs-6">${fmt.currency(x.grand_total)}</strong></div>
            </div>
            
            ${(downloadBtn || printBtn) ? `<div class="d-flex justify-content-end mb-2">${downloadBtn}${printBtn}</div>` : ''}
            
            <div class="attachment-container">
                ${attachmentHtml}
            </div>
        `;
    } else { document.getElementById('viewModalBody').innerHTML = '<p class="text-danger">Failed to load PO details.</p>'; }
}

function printAttachment(url) {
    const w = window.open(url, '_blank');
    w.onload = function() {
        setTimeout(() => { w.print(); }, 500); // short delay to ensure PDF/Image renders before print prompt
    };
}

// Item logic functions removed

async function save(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    const data = {
        po_id: fd.get('po_id'),
        supplier_id: fd.get('supplier_id'),
        supplier_name: fd.get('supplier_name'),
        expected_date: fd.get('expected_date'),
        status: fd.get('status'),
        remarks: fd.get('remarks'),
        items: [] // Sent as empty array since items are managed elsewhere
    };

    PH.loading('Saving PO...');
    try {
        const res = await fetch(API_BASE + 'pharmacy/purchase-orders', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        }).then(r => r.json());
        if (res.success) { PH.success(res.message); modal.hide(); load(); } else PH.error(res.message);
    } catch(e) { PH.error('Failed to save PO'); }
}

function del(id) {
    PH.confirm('Delete Purchase Order?', 'This cannot be undone.', async () => {
        PH.loading('Deleting...');
        const res = await fetch(API_BASE + 'pharmacy/purchase-orders/' + id, { method: 'DELETE' }).then(r => r.json());
        if (res.success) { PH.success('Deleted'); load(); } else PH.error(res.message);
    });
}

function fillSupplier(sel) {
    document.getElementById('supplier_name').value = sel.options[sel.selectedIndex].getAttribute('data-name') || '';
}
</script>
