<?php
// Extend session to 8 hours for full-shift use
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.cookie_lifetime', 28800);
session_start();
if (!isset($_SESSION["user_id"])) { header("Location: ../login.php"); exit; }
require_once "includes/db.php";
$pageTitle = "Indent Requests";
$db = getDB();
$threshold = (int)getSetting("low_stock_threshold", "20");
$lowStockItems = $db->query("SELECT product_id, product_name, quantity FROM ph_product WHERE quantity <= $threshold ORDER BY quantity ASC")->fetchAll();
$suppliers = $db->query("SELECT supplier_id, supplier_name, company_name, email FROM ph_suppliers WHERE status='active' ORDER BY company_name")->fetchAll();
$pendingCount = (int)$db->query("SELECT COUNT(*) FROM ph_indent_requests WHERE status='pending'")->fetchColumn();
$approvedCount = (int)$db->query("SELECT COUNT(*) FROM ph_indent_requests WHERE status='approved'")->fetchColumn();
$urgentCount = (int)$db->query("SELECT COUNT(*) FROM ph_indent_requests WHERE priority='urgent' AND status='pending'")->fetchColumn();
$totalCount = (int)$db->query("SELECT COUNT(*) FROM ph_indent_requests")->fetchColumn();
include "includes/ph_head.php";
?>

<style>
/* ==========================================
   ADVANCED PROCUREMENT WORKSPACE (v3.0)
   Lead UI/UX: Modern Medical SaaS Aesthetic
   ========================================== */
:root {
  --proc-primary: #0EA5E9;   /* Sky 500 */
  --proc-success: #10B981;   /* Emerald 500 */
  --proc-warning: #F59E0B;   /* Amber 500 */
  --proc-danger: #EF4444;    /* Red 500 */
  --proc-slate: #0F172A;
  --proc-bg: #F8FAFC;
  --glass-white: rgba(255, 255, 255, 0.7);
  --glass-border: 1px solid rgba(255, 255, 255, 0.5);
  --glass-shadow: 0 8px 32px 0 rgba(15, 23, 42, 0.08);
}

.ph-page-body { background: var(--proc-bg); font-family: 'Plus Jakarta Sans', sans-serif; padding: 1.75rem !important; }

/* ===== BENTO KPI GRID (GLASSMORPHISM) ===== */
.bento-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  margin-bottom: 2rem;
}
.bento-card {
  background: var(--glass-white);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: var(--glass-border);
  border-radius: 16px;
  padding: 1rem 1.25rem;
  box-shadow: var(--glass-shadow);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 1rem;
}
.bento-card:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(0,0,0,0.08); }
.bento-card::before {
  content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
  background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 60%);
  transform: rotate(30deg); pointer-events: none;
}

.bento-icon {
  width: 42px; height: 42px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.15rem; flex-shrink: 0;
  box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}
.bento-val { font-size: 1.5rem; font-weight: 800; color: var(--proc-slate); letter-spacing: -1px; line-height: 1; margin-bottom: 0.15rem; }
.bento-lbl { font-size: 0.7rem; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0; }

/* ===== SMART SUGGESTION BANNER ===== */
.smart-alert {
  background: white; border-radius: 24px; padding: 1.25rem 2rem;
  display: flex; align-items: center; gap: 2rem;
  box-shadow: var(--glass-shadow); border: 1px solid #E2E8F0;
  margin-bottom: 2.5rem; position: sticky; top: 1rem; z-index: 100;
}
.low-stock-scroll {
  display: flex; gap: 1rem; overflow-x: auto; flex: 1; padding: 0.5rem 0;
  scrollbar-width: none; -ms-overflow-style: none;
}
.low-stock-scroll::-webkit-scrollbar { display: none; }
.stock-item-tag {
  background: #F1F5F9; padding: 8px 16px; border-radius: 12px;
  white-space: nowrap; font-size: 0.85rem; font-weight: 700; color: #475569;
  border: 1px solid #E2E8F0; display: flex; align-items: center; gap: 8px;
}

/* ===== WORKSPACE TABLE (CARDS) ===== */
#indentsTable { border-collapse: separate; border-spacing: 0 12px; }
#indentsTable thead th { border: none; padding: 0 1.5rem 0.75rem; color: #94A3B8; font-weight: 800; font-size: 0.7rem; text-transform: uppercase; }

.indent-row {
  background: white; border-radius: 20px; transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(0,0,0,0.02); cursor: pointer;
}
.indent-row:hover { transform: scale(1.005); box-shadow: 0 12px 24px rgba(0,0,0,0.06); z-index: 2; }
.indent-row td { padding: 1.5rem; border: none; vertical-align: middle; }
.indent-row td:first-child { border-radius: 20px 0 0 20px; }
.indent-row td:last-child { border-radius: 0 20px 20px 0; }

/* Timeline Stepper */
.stepper { display: flex; gap: 0.5rem; margin-top: 0.75rem; }
.step { width: 30px; height: 6px; border-radius: 10px; background: #E2E8F0; position: relative; }
.step.active { background: var(--proc-primary); box-shadow: 0 0 10px rgba(14, 165, 233, 0.4); }

/* Inline Inputs */
.inline-qty {
  width: 70px; border: 1px solid transparent; border-radius: 8px; padding: 4px 8px;
  font-weight: 800; text-align: center; transition: all 0.2s;
}
.inline-qty:hover, .inline-qty:focus { border-color: var(--proc-primary); background: #F0F9FF; outline: none; }

/* ===== GLASS DARK BULK BAR ===== */
#bulkBar {
  position: fixed; bottom: 2rem; left: 50%; transform: translateX(-50%);
  background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px); padding: 1rem 2rem; border-radius: 24px;
  display: flex; align-items: center; gap: 1.5rem; z-index: 1000;
  box-shadow: 0 20px 50px rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1);
}

/* Animations */
@keyframes slideUp { from { opacity: 0; transform: translate(-50%, 20px); } to { opacity: 1; transform: translate(-50%, 0); } }
.animate-slide-up { animation: slideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
</style>

<div class="ph-wrap">
<?php include "includes/pharmacy_sidebar.php"; ?>
<div id="ph-content">
<?php include "includes/pharmacy_navbar.php"; ?>
<div class="ph-page-body">

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-5">
  <div>
    <h1 class="ph-page-title" style="font-weight: 900; letter-spacing: -1px; color: var(--proc-slate);">Procurement Workspace</h1>
    <p class="ph-page-subtitle" style="font-weight: 600; color: #64748B;">Manage internal requisitions and vendor workflows</p>
  </div>
  <div class="d-flex gap-3">
    <button class="ph-btn ph-btn-outline" style="border-radius: 14px; padding: 0.75rem 1.25rem;" onclick="exportCSV()"><i class="fas fa-file-csv me-2"></i> Export Data</button>
    <button class="ph-btn" style="background: var(--proc-primary); color: white; border-radius: 14px; padding: 0.75rem 1.5rem; font-weight: 700; box-shadow: 0 10px 20px rgba(14, 165, 233, 0.2);" onclick="openIndentModal()">
      <i class="fas fa-plus me-2"></i> New Requisition
    </button>
  </div>
</div>

<!-- Bento KPI Grid -->
<div class="bento-grid">
  <div class="bento-card">
    <div class="bento-icon" style="background: #E0F2FE; color: #0369A1;"><i class="fas fa-clock"></i></div>
    <div style="flex: 1;">
      <div class="bento-val" id="stat-pending"><?= $pendingCount ?></div>
      <div class="bento-lbl">Pending Review</div>
    </div>
  </div>
  <div class="bento-card">
    <div class="bento-icon" style="background: #DCFCE7; color: #15803D;"><i class="fas fa-check-double"></i></div>
    <div style="flex: 1;">
      <div class="bento-val" id="stat-approved"><?= $approvedCount ?></div>
      <div class="bento-lbl">Approved Requests</div>
    </div>
  </div>
  <div class="bento-card">
    <div class="bento-icon" style="background: #FEE2E2; color: #B91C1C;"><i class="fas fa-bolt"></i></div>
    <div style="flex: 1;">
      <div class="bento-val" id="stat-urgent"><?= $urgentCount ?></div>
      <div class="bento-lbl">Urgent Action</div>
    </div>
  </div>
  <div class="bento-card">
    <div class="bento-icon" style="background: #F1F5F9; color: #475569;"><i class="fas fa-archive"></i></div>
    <div style="flex: 1;">
      <div class="bento-val" id="stat-total"><?= $totalCount ?></div>
      <div class="bento-lbl">Total Requisitions</div>
    </div>
  </div>
</div>

<!-- Smart Suggestion Banner -->
<?php if(count($lowStockItems)>0): ?>
<div class="smart-alert">
  <div class="d-flex align-items-center gap-3">
    <div style="width:50px; height:50px; border-radius:14px; background: #FEF3C7; color: #D97706; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;"><i class="fas fa-lightbulb"></i></div>
    <div>
      <div style="font-weight: 800; color: var(--proc-slate); font-size: 1rem;">Smart Re-stock Suggestion</div>
      <div style="font-size: 0.8rem; color: #64748B; font-weight: 600;"><?= count($lowStockItems) ?> items below threshold</div>
    </div>
  </div>
  <div class="low-stock-scroll">
    <?php foreach($lowStockItems as $item): ?>
      <div class="stock-item-tag">
        <i class="fas fa-pills" style="color: var(--proc-primary)"></i>
        <?= htmlspecialchars($item['product_name']) ?> (<?= $item['quantity'] ?> left)
      </div>
    <?php endforeach; ?>
  </div>
  <button class="ph-btn" style="background: #0F172A; color: white; border-radius: 12px; font-weight: 700;" onclick="autoGenerateIndent()">
    <i class="fas fa-magic me-2"></i> Generate Drafts
  </button>
</div>
<?php endif; ?>

<!-- Workspace Header -->
<div class="d-flex align-items-center gap-3 mb-4">
  <div class="flex-grow-1 position-relative">
    <i class="fas fa-search" style="position: absolute; left: 1.25rem; top: 50%; transform: translateY(-50%); color: #94A3B8;"></i>
    <input type="text" id="searchInput" class="ph-input" style="padding-left: 3rem; height: 54px; border-radius: 16px; border-color: transparent; box-shadow: var(--glass-shadow);" placeholder="Quick search by indent no, item name, or department...">
  </div>
  <select class="ph-select" id="statusFilter" style="width:160px; height: 54px; border-radius: 16px; border-color: transparent; box-shadow: var(--glass-shadow);">
    <option value="">All Status</option>
    <option value="pending">Pending</option>
    <option value="approved">Approved</option>
  </select>
  <button class="ph-btn ph-btn-outline" style="height: 54px; width: 54px; border-radius: 16px; box-shadow: var(--glass-shadow); border-color: transparent; background: white;" onclick="loadIndents()">
    <i class="fas fa-sync-alt"></i>
  </button>
</div>

<!-- Table -->
<div class="ph-table-wrap p-0">
  <table class="ph-table w-100" id="indentsTable">
    <thead>
      <tr>
        <th style="width:50px"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" style="width:20px; height:20px; accent-color: var(--proc-primary);"></th>
        <th>Indent Reference</th>
        <th>Product & Source</th>
        <th>Logistics Info</th>
        <th>Qty & Priority</th>
        <th>Workflow State</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody id="indentsBody">
      <tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></td></tr>
    </tbody>
  </table>
</div>

<div class="d-flex align-items-center justify-content-between mt-4">
  <span id="tableInfo" style="font-weight: 700; color: #94A3B8; font-size: 0.85rem;"></span>
  <div id="pager" class="ph-pagination"></div>
</div>

</div>

<!-- ── Bulk Action Bar ── -->
<div id="bulkBar" style="display:none;">
  <span id="selectedCount" style="color:#fff; font-weight:700;"></span>
  <div style="display:flex; gap:10px;">
    <button class="ph-btn" style="background:#10B981;color:#fff;border-radius:12px;font-weight:700;padding:8px 18px;border:none;cursor:pointer;" onclick="bulkChangeStatus('approved')">
      <i class="fas fa-check me-2"></i>Approve
    </button>
    <button class="ph-btn" style="background:#F59E0B;color:#fff;border-radius:12px;font-weight:700;padding:8px 18px;border:none;cursor:pointer;" onclick="bulkChangeStatus('cancelled')">
      <i class="fas fa-ban me-2"></i>Cancel
    </button>
    <button class="ph-btn" style="background:#0EA5E9;color:#fff;border-radius:12px;font-weight:700;padding:8px 18px;border:none;cursor:pointer;" onclick="bulkSendEmail()">
      <i class="fas fa-envelope me-2"></i>Email
    </button>
    <button class="ph-btn" style="background:#EF4444;color:#fff;border-radius:12px;font-weight:700;padding:8px 18px;border:none;cursor:pointer;" onclick="bulkDelete()">
      <i class="fas fa-trash me-2"></i>Delete
    </button>
  </div>
</div>

</div></div></div>

<!-- Modal -->
<style>
  .compact-modal .ph-label { font-size: 0.65rem; font-weight: 800; color: #1F6B4A; margin-bottom: 2px; text-transform: uppercase; }
  .compact-modal .ph-input, .compact-modal .ph-select, .compact-modal .ph-textarea { padding: 4px 8px; font-size: 0.8rem; height: 32px; border: 1px solid rgba(31,107,74,0.2); border-radius: 6px; background: #FFF; color: #1F6B4A; font-weight: 600; width: 100%; box-shadow: none; box-sizing: border-box; }
  .compact-modal .ph-textarea { height: auto; min-height: 48px; }
  .compact-modal .ph-input:focus, .compact-modal .ph-select:focus, .compact-modal .ph-textarea:focus { border-color: #1F6B4A; outline: none; box-shadow: 0 0 0 2px rgba(31,107,74,0.1); }
  .compact-modal h6 { font-size: 0.8rem; margin-top: 4px; margin-bottom: 8px !important; color: #1F6B4A; font-weight: 800; border-bottom: 1px solid rgba(31,107,74,0.1); padding-bottom: 4px; }
  .compact-modal .modal-body { padding: 12px 20px; }
  .compact-modal .modal-header, .compact-modal .modal-footer { padding: 10px 20px; }
  .compact-modal .grid-4-cols { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 16px; }
  .compact-modal .grid-3-cols { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 16px; }
  .compact-modal .grid-2-cols { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 16px; }
  .compact-modal .grid-item { display: flex; flex-direction: column; }
</style>
<div class="modal fade compact-modal" id="indentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 900px;">
    <div class="modal-content" style="background: #F3EFE6; border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
      <div class="modal-header" style="border-bottom: 1px solid rgba(31,107,74,0.15);">
        <h5 class="modal-title" id="modalTitle" style="color: #1F6B4A; font-weight: 900; letter-spacing: -0.5px;">New Indent Request</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(34%) sepia(16%) saturate(1637%) hue-rotate(107deg) brightness(97%) contrast(89%); opacity: 0.8;"></button>
      </div>
      <form id="indentForm" onsubmit="saveIndent(event)">
        <div class="modal-body">
          <input type="hidden" name="id" id="id">
          
          <div>
            <h6><i class="fas fa-box-open me-1"></i>Indent & Supplier Details</h6>
            <div class="grid-4-cols">
                <div class="grid-item">
                  <label class="ph-label">Department / Ward</label>
                  <input type="text" class="ph-input" name="department" id="department" value="Pharmacy Store">
                </div>
                <div class="grid-item">
                  <label class="ph-label">Requested By</label>
                  <input type="text" class="ph-input" name="requested_by" id="requested_by" value="<?= htmlspecialchars($_SESSION['username'] ?? 'Pharmacist') ?>">
                </div>
                <div class="grid-item" style="grid-column: span 2;">
                  <label class="ph-label">Item Name *</label>
                  <input type="text" class="ph-input" name="item_name" id="item_name" list="lowStockList" required autocomplete="off">
                  <input type="hidden" name="product_id" id="product_id">
                  <datalist id="lowStockList">
                    <?php foreach($lowStockItems as $item): ?>
                      <option value="<?= htmlspecialchars($item['product_name']) ?>" data-id="<?= $item['product_id'] ?>"></option>
                    <?php endforeach; ?>
                  </datalist>
                </div>
                
                <div class="grid-item">
                  <label class="ph-label">Quantity *</label>
                  <input type="number" class="ph-input" name="qty" id="qty" required min="1" value="1">
                </div>
                <div class="grid-item">
                  <label class="ph-label">Priority</label>
                  <select class="ph-select" name="priority" id="priority">
                    <option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="urgent">Urgent</option>
                  </select>
                </div>
                <div class="grid-item">
                  <label class="ph-label">Status</label>
                  <select class="ph-select" name="status" id="status">
                    <option value="pending">Pending</option><option value="approved">Approved</option><option value="cancelled">Cancelled</option>
                  </select>
                </div>
                <div class="grid-item">
                  <label class="ph-label">Supplier *</label>
                  <select class="ph-select" name="supplier_id" id="supplier_id" required onchange="updateCompanyName(this)">
                    <option value="">Select Supplier</option>
                    <?php foreach($suppliers as $s): ?>
                      <option value="<?= $s['supplier_id'] ?>" data-company="<?= htmlspecialchars($s['company_name']) ?>" data-email="<?= htmlspecialchars($s['email'] ?? '') ?>">
                          <?= htmlspecialchars($s['supplier_name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="grid-item">
                  <label class="ph-label">Company Name</label>
                  <input type="text" class="ph-input" name="company_name" id="company_name" readonly placeholder="Auto-filled" style="background:#E3F2EC; font-weight:800; cursor:not-allowed;">
                </div>
                <div class="grid-item">
                  <label class="ph-label"><i class="fas fa-envelope me-1"></i>Notify by Email (optional)</label>
                  <input type="email" class="ph-input" name="notify_email" id="notify_email" placeholder="store@hospital.com">
                </div>
                <div class="grid-item" style="grid-column: span 2;">
                  <label class="ph-label">Remarks</label>
                  <textarea class="ph-textarea" name="remarks" id="remarks" rows="1" placeholder="e.g. Stock critically low..."></textarea>
                </div>
            </div>
          </div>
        </div>
        <div class="modal-footer" style="background: #F3EFE6; border-top: 1px solid rgba(31,107,74,0.15); border-radius: 0 0 12px 12px;">
          <button type="button" class="btn btn-sm" data-bs-dismiss="modal" style="background: transparent; color: #1F6B4A; border: 1.5px solid rgba(31,107,74,0.2); border-radius: 8px; font-weight: 700;">Cancel</button>
          <button type="submit" class="btn btn-sm" style="background: #1F6B4A; color: #FFFFFF; border: none; border-radius: 8px; font-weight: 700; box-shadow: 0 4px 10px rgba(31,107,74,0.2);">
            <i class="fas fa-save me-1"></i> Save Request
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
        
        <div id="smartDispatchBlock" style="display:none;"></div>
        
        <div class="mb-2" id="recipientBlock">
          <label class="ph-label">Recipient Email *</label>
          <select class="ph-select w-100" id="emailTo">
            <option value="">Select a recipient...</option>
            <?php foreach($suppliers as $s): ?>
              <option value="<?= htmlspecialchars($s['email'] ?? '') ?>" data-id="<?= htmlspecialchars($s['supplier_id']) ?>" data-name="<?= htmlspecialchars($s['company_name'] ?: $s['supplier_name']) ?>"><?= htmlspecialchars($s['company_name'] . " (" . ($s['email'] ?? '') . ")") ?></option>
            <?php endforeach; ?>
          </select>
          <div class="small fw-bold mt-1" style="font-size:0.65rem; color:#1F6B4A; opacity: 0.7;">Or type a custom email below:</div>
          <input type="email" class="ph-input mt-1" id="customEmail" placeholder="custom@example.com">
        </div>
        <div class="mb-2">
          <label class="ph-label">Subject Line</label>
          <input type="text" class="ph-input" id="emailSubject" value="Pharmacy Indent Request Notification">
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

<?php include "includes/ph_foot.php"; ?>
<script>
const SUPPLIERS = <?= json_encode($suppliers) ?>;
let allIndents=[],currentPage=1,selectedIds=new Set(),filteredData=[];
const PER_PAGE=12;
const modal=new bootstrap.Modal(document.getElementById('indentModal'));
const emailModal=new bootstrap.Modal(document.getElementById('emailModal'));

document.addEventListener('DOMContentLoaded',()=>{
  loadIndents();
  ['searchInput','statusFilter'].forEach(id=>document.getElementById(id).addEventListener(id==='searchInput'?'input':'change',()=>{currentPage=1;renderTable();}));
});

async function loadIndents(){
  try{
    const res=await phGet(API_BASE+'pharmacy/indents');
    if(res.success){allIndents=res.data;renderTable();}
    else PH.error(res.message);
  }catch(e){PH.error('Network error');}
}

function updateCompanyName(selectElement) {
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const companyInput = document.getElementById('company_name');
    const emailInput = document.getElementById('notify_email');
    
    if (selectedOption && selectedOption.value) {
        companyInput.value = selectedOption.getAttribute('data-company') || '';
        if (emailInput && !emailInput.value) { // Auto-fill email only if it's currently empty
            emailInput.value = selectedOption.getAttribute('data-email') || '';
        }
    } else {
        companyInput.value = '';
    }
}

function renderTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    const sf = document.getElementById('statusFilter').value;
    
    filteredData = allIndents.filter(ind => {
        if (q && !((ind.indent_no||'').toLowerCase().includes(q) || (ind.item_name||'').toLowerCase().includes(q) || (ind.department||'').toLowerCase().includes(q))) return false;
        if (sf && ind.status !== sf) return false;
        return true;
    });

    const pager = phPaginate(filteredData, currentPage, PER_PAGE);
    document.getElementById('tableInfo').textContent = `Showing ${pager.items.length} of ${filteredData.length} records`;
    
    let html = '';
    if (!pager.items.length) {
        html = `<tr><td colspan="7" class="text-center py-5 text-muted">No records found matching your filters.</td></tr>`;
    } else {
        pager.items.forEach(i => {
            const isSelected = selectedIds.has(i.id);
            const status = (i.status || 'pending').toLowerCase();
            
            // Workflow Stepper
            const steps = ['pending', 'approved', 'ordered', 'received'];
            const curIdx = steps.indexOf(status);
            let stepper = '<div class="stepper">';
            steps.forEach((s, idx) => stepper += `<div class="step ${idx <= curIdx ? 'active' : ''}" title="${s.toUpperCase()}"></div>`);
            stepper += '</div>';

            html += `
            <tr class="indent-row ${isSelected ? 'selected' : ''}" onclick="toggleRow(${i.id}, !selectedIds.has(${i.id}))">
                <td><input type="checkbox" ${isSelected ? 'checked' : ''} onclick="event.stopPropagation(); toggleRow(${i.id}, this.checked)" style="width:20px; height:20px; accent-color: var(--proc-primary);"></td>
                <td>
                    <div style="font-weight: 800; color: var(--proc-slate); font-size: 0.95rem;">${i.indent_no}</div>
                    <div style="font-size: 0.75rem; color: #94A3B8; font-weight: 600; margin-top: 4px;"><i class="far fa-calendar-alt me-1"></i>${fmt.date(i.request_date)}</div>
                </td>
                <td>
                    <div style="font-weight: 700; color: #475569;">${i.item_name}</div>
                    <div style="font-size: 0.75rem; color: var(--proc-primary); font-weight: 700; margin-top: 4px;">Dept: ${i.department || 'Pharmacy'}</div>
                </td>
                <td>
                    <div style="font-weight: 700; color: #64748B; font-size: 0.85rem;">${i.company_name || 'N/A'}</div>
                    <div style="font-size: 0.7rem; color: #94A3B8; margin-top: 4px;">ID: ${i.supplier_id || '—'}</div>
                </td>
                <td>
                    <input type="number" class="inline-qty" value="${i.qty}" onclick="event.stopPropagation()" onchange="updateQty(${i.id}, this.value)">
                    <div class="mt-2">${priorityBadge(i.priority)}</div>
                </td>
                <td>
                    <div style="font-weight: 800; color: var(--proc-slate); font-size: 0.75rem; text-transform: uppercase;">${status}</div>
                    ${stepper}
                </td>
                <td class="text-end" onclick="event.stopPropagation()">
                    <div class="d-flex justify-content-end gap-2">
                        <button class="ph-btn ph-btn-sm ph-btn-outline" style="border-radius:12px; width:40px; height:40px;" onclick='editIndent(${JSON.stringify(i).replace(/'/g, "&apos;")})'><i class="fas fa-pencil-alt"></i></button>
                        <button class="ph-btn ph-btn-sm" style="background: #0F172A; color: white; border-radius:12px; width:40px; height:40px;" onclick="sendToVendor(${i.id})"><i class="fas fa-paper-plane"></i></button>
                    </div>
                </td>
            </tr>`;
        });
    }
    document.getElementById('indentsBody').innerHTML = html;
    phRenderPager(document.getElementById('pager'), pager.pages, currentPage, p => { currentPage = p; renderTable(); });
    updateBulkBar();
}

function priorityBadge(p) {
    const map = { urgent: ['#FEE2E2','#B91C1C','URGENT'], high: ['#FFEDD5','#9A3412','HIGH'], medium: ['#FEF9C3','#92400E','MEDIUM'], low: ['#DCFCE7','#15803D','LOW'] };
    const c = map[(p||'medium').toLowerCase()];
    return `<span style="background:${c[0]}; color:${c[1]}; padding: 4px 10px; border-radius: 20px; font-size: 0.6rem; font-weight: 800;">${c[2]}</span>`;
}

function toggleRow(id, checked) {
    if (checked) selectedIds.add(id); else selectedIds.delete(id);
    renderTable();
}

function toggleSelectAll(cb) {
    filteredData.forEach(i => cb.checked ? selectedIds.add(i.id) : selectedIds.delete(i.id));
    renderTable();
}

function updateBulkBar() {
    const bar = document.getElementById('bulkBar');
    if (!bar) return;
    if (selectedIds.size > 0) {
        bar.style.display = 'flex';
        bar.classList.add('animate-slide-up');
        document.getElementById('selectedCount').innerHTML = `<i class="fas fa-check-circle me-2" style="color:#10B981"></i> ${selectedIds.size} Selected`;
    } else bar.style.display = 'none';
}

async function updateQty(id, qty) {
    try {
        const res = await phPost(API_BASE + 'pharmacy/indents/update-qty', { id: id, qty: qty });
        if (res.success) { PH.success('Quantity updated'); loadIndents(); }
        else PH.error(res.message);
    } catch (e) { PH.error('Sync failed'); }
}

async function bulkChangeStatus(status) {
    if (!selectedIds.size) return;
    try {
        const res = await phPost(API_BASE + 'pharmacy/indents/bulk-status', { ids: Array.from(selectedIds), status: status });
        if (res.success) { PH.success('Batch updated'); selectedIds.clear(); loadIndents(); }
        else PH.error(res.message);
    } catch (e) { PH.error('Sync failed'); }
}

async function bulkDelete() {
    if (!selectedIds.size) return;
    PH.confirm('Delete Selected?', `Permanently remove ${selectedIds.size} requisitions?`, async () => {
        try {
            const res = await phPost(API_BASE + 'pharmacy/indents/bulk-delete', { ids: Array.from(selectedIds) });
            if (res.success) { PH.success('Deleted'); selectedIds.clear(); loadIndents(); }
            else PH.error(res.message);
        } catch (e) { PH.error('Delete failed'); }
    });
}

function openIndentModal(){
  document.getElementById('indentForm').reset();
  document.getElementById('id').value='';
  document.getElementById('modalTitle').textContent='New Indent Request';
  document.getElementById('department').value='Pharmacy Store';
  document.getElementById('status').value='pending';
  modal.show();
}

function editIndent(i){
  document.getElementById('indentForm').reset();
  document.getElementById('id').value=i.id;
  document.getElementById('modalTitle').textContent='Edit Indent';
  ['department','requested_by','product_id','item_name','qty','priority','status','remarks', 'supplier_id', 'company_name'].forEach(f=>{if(document.getElementById(f))document.getElementById(f).value=i[f]||'';});
  modal.show();
}

async function saveIndent(e) {
  e.preventDefault();
  const data = Object.fromEntries(new FormData(e.target).entries());
  PH.loading('Saving...');
  try {
    const res = await fetch(API_BASE + 'pharmacy/indents', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    }).then(r => r.json());
    if (res.success) {
      // If notify email provided, send it after saving
      if (data.notify_email && data.notify_email.trim() !== '') {
        await sendEmailFor(data.notify_email, data.item_name, res.indent_no || data.id || '');
      }
      PH.success(res.message);
      modal.hide();
      loadIndents();
    } else {
      PH.error(res.message);
    }
  } catch (err) {
    PH.error('Failed to save. Please try again.');
  }
}

// Send a quick notification email after saving an indent
async function sendEmailFor(toEmail, itemName, indentRef) {
  const subject = `[QUOTATION REQUEST] New Pharmacy Indent: ${indentRef}`;
  const bodyText = `Dear Partner,\n\nA new procurement requisition has been raised for <strong>${itemName}</strong> (Ref: ${indentRef}).\n\nKindly review the requirements and submit your quotation through our digital portal using the link below.\n\nBest Regards,\nPharmacy Department\nGM Hospital`;
  const htmlBody = `
    <div style="font-family: 'Segoe UI', sans-serif; color: #334155; max-width: 800px; margin: 20px auto; border: 1px solid #E2E8F0; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
      <div style="background: #0EA5E9; padding: 30px; text-align: center;">
        <h2 style="color: white; margin: 0; font-size: 24px; font-weight: 800;">Pharmacy Procurement Requisition</h2>
        <p style="color: #BAE6FD; margin: 5px 0 0; font-size: 14px; font-weight: 600;">GM Hospital Management System</p>
      </div>
      <div style="padding: 40px; background: white;">
        <div style="font-size: 16px; line-height: 1.6; color: #475569; margin-bottom: 30px;">${bodyText.replace(/\n/g,'<br>')}</div>
        <div style="text-align: center;">
          <a href="${window.location.origin}/GM_HMS/vendor/vendor_view/login.php?indent_no=${indentRef}&branch=<?= urlencode($_SESSION['hospital_branch'] ?? 'nagarabhavi') ?>" 
             style="background: #0F172A; color: white; padding: 14px 32px; text-decoration: none; border-radius: 12px; font-weight: 700; display: inline-block;">
             ACCESS VENDOR PORTAL
          </a>
        </div>
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #F1F5F9; font-size: 12px; color: #94A3B8; text-align: center;">
          This is an automated system notification. Please do not reply directly.
        </div>
      </div>
    </div>`;
  try {
    await fetch('send_email.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ email_to: toEmail, subject, body: htmlBody })
    });
  } catch (e) { /* Silent fail — main save already succeeded */ }
}

function deleteIndent(id){
  PH.confirm('Delete Indent Request?','This cannot be undone.',async()=>{
    const res=await fetch(API_BASE+'pharmacy/indents/'+id,{method:'DELETE'}).then(r=>r.json());
    if(res.success){PH.success('Deleted');loadIndents();}else PH.error(res.message);
  });
}

function autoGenerateIndent() {
  PH.confirm(
    'Auto-Generate Indents?',
    'Draft indent requests will be created for all low-stock items without existing pending/approved indents.',
    async () => {
      PH.loading('Generating drafts...');
      try {
        const r   = await fetch(API_BASE + 'pharmacy/indents/auto-generate', { method: 'POST' });
        const res = await r.json();
        const msg = res.message || res.error || 'Unknown response';
        if (res.success) {
          PH.success(msg);
          loadIndents();
        } else {
          PH.error(msg);
        }
      } catch (e) {
        PH.error('Network error. Could not reach the server.');
      }
    },
    'Yes, Generate'
  );
}

// -- EMAIL ----------------------------------------------------------
function generateHtmlTable(items){
  const rows = items.map(i => {
    return `
    <tr>
      <td style="border:1px solid #e2e8f0; padding:12px; font-family: sans-serif; font-size: 14px; color: #475569;">${i.indent_no}</td>
      <td style="border:1px solid #e2e8f0; padding:12px; font-family: sans-serif; font-size: 14px; color: #1e293b;">${i.item_name}</td>
      <td style="border:1px solid #e2e8f0; padding:12px; font-family: sans-serif; font-size: 14px; text-align:center; color: #475569;">${i.qty}</td>
      <td style="border:1px solid #e2e8f0; padding:12px; font-family: sans-serif; font-size: 12px; text-align:center;">
        <span style="background-color: ${i.priority==='high'||i.priority==='urgent'?'#fee2e2':'#f1f5f9'}; color: ${i.priority==='high'||i.priority==='urgent'?'#991b1b':'#475569'}; padding: 4px 8px; border-radius: 4px; font-weight: 600; text-transform: uppercase;">
          ${i.priority}
        </span>
      </td>
      <td style="border:1px solid #e2e8f0; padding:12px; font-family: sans-serif; font-size: 14px; color: #475569;">${i.company_name || 'N/A'}</td>
    </tr>`;
  }).join('');

  return `
    <table style="width: 100%; border-collapse: collapse; margin: 20px 0; background-color: #ffffff; border: 1px solid #e2e8f0;">
      <thead>
        <tr style="background-color: #f8fafc;">
          <th style="border:1px solid #e2e8f0; padding:12px; text-align:left; font-family: sans-serif; font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Indent No</th>
          <th style="border:1px solid #e2e8f0; padding:12px; text-align:left; font-family: sans-serif; font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Item Name</th>
          <th style="border:1px solid #e2e8f0; padding:12px; text-align:center; font-family: sans-serif; font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Qty</th>
          <th style="border:1px solid #e2e8f0; padding:12px; text-align:center; font-family: sans-serif; font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Priority</th>
          <th style="border:1px solid #e2e8f0; padding:12px; text-align:left; font-family: sans-serif; font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Company</th>
        </tr>
      </thead>
      <tbody>
        ${rows}
      </tbody>
    </table>`;
}

let currentEmailItems = [];

const DEFAULT_EMAIL_MSG = `Dear Manager,\n\nI hope you are doing well.\n\nThis is to inform you that a pharmacy indent request has been generated and is pending your approval. Kindly review the request and take the necessary action at your earliest convenience.\n\nYour prompt approval will help ensure smooth pharmacy operations and avoid any stock shortages.\n\nThank you.\n\nBest Regards,\nPharmacy Department\nGM Hospital`;

function quickEmail(id){
  const item=allIndents.find(i=>i.id===id);
  if(!item)return;
  currentEmailItems = [item];
  document.getElementById('emailTo').value='';
  document.getElementById('emailSubject').value=`[APPROVAL REQUIRED] Pharmacy Indent Request: ${item.indent_no}`; 
  document.getElementById('emailBody').value=DEFAULT_EMAIL_MSG;
  emailModal.show();
}

function bulkSendEmail(){
  if(!selectedIds.size)return;
  currentEmailItems = allIndents.filter(i=>selectedIds.has(i.id));
  
  const uniqueSuppliers = [...new Set(currentEmailItems.map(i => i.supplier_id).filter(Boolean))];
  const emailModalEl = document.getElementById('emailModal');
  const recipientBlock = document.getElementById('recipientBlock');
  const smartDispatchBlock = document.getElementById('smartDispatchBlock');

  document.getElementById('emailTo').value='';
  document.getElementById('customEmail').value = '';

  if (uniqueSuppliers.length > 1) {
      // Smart Dispatch Mode
      emailModalEl.dataset.smartMode = "true";
      recipientBlock.style.display = 'none';
      smartDispatchBlock.style.display = 'block';
      smartDispatchBlock.innerHTML = `
        <div class="alert alert-info py-3 mb-3" style="border-radius:12px; border: 1px solid #BAE6FD;">
            <div class="d-flex align-items-center">
                <i class="fas fa-magic fa-2x text-primary me-3"></i>
                <div>
                    <h6 class="mb-1 text-primary fw-bold">Smart Dispatch Mode</h6>
                    <p class="mb-0 small text-secondary">You have selected items assigned to <strong>${uniqueSuppliers.length} different vendors</strong>. The system will automatically group the items and send separate, customized emails to each vendor.</p>
                </div>
            </div>
        </div>
      `;
      document.getElementById('emailSubject').value = `[APPROVAL REQUIRED] Pharmacy Indent Requests`;
      document.getElementById('emailBody').value = `Dear Partner,\n\nPlease find attached the pharmacy indent requests assigned to your company.\nKindly review the requirements and submit your quotation through our digital portal.\n\nBest Regards,\nPharmacy Department\nGM Hospital`;
  } else {
      // Normal Mode (0 or 1 supplier)
      emailModalEl.dataset.smartMode = "false";
      recipientBlock.style.display = 'block';
      smartDispatchBlock.style.display = 'none';
      
      const firstItemWithSupplier = currentEmailItems.find(i => i.supplier_id);
      if (firstItemWithSupplier) {
          const vendor = SUPPLIERS.find(s => s.supplier_id == firstItemWithSupplier.supplier_id);
          if (vendor && vendor.email) {
              document.getElementById('emailTo').value = vendor.email;
          }
      }
      document.getElementById('emailSubject').value=`[APPROVAL REQUIRED] Pending Pharmacy Indent Requests (${currentEmailItems.length})`;
      document.getElementById('emailBody').value=DEFAULT_EMAIL_MSG.replace('a pharmacy indent request has', currentEmailItems.length + ' pharmacy indent requests have');
  }

  emailModal.show();
}

// Reusable email template builder
function buildEmailTemplate(message, tableHtml, firstIndentNo) {
    return `
    <div style="font-family: 'Segoe UI', sans-serif; color: #334155; max-width: 800px; margin: 20px auto; border: 1px solid #E2E8F0; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
      <div style="background: #0EA5E9; padding: 30px; text-align: center;">
        <h2 style="color: white; margin: 0; font-size: 24px; font-weight: 800;">Pharmacy Procurement Requisition</h2>
        <p style="color: #BAE6FD; margin: 5px 0 0; font-size: 14px; font-weight: 600;">GM Hospital Management System</p>
      </div>
      <div style="padding: 40px; background: white;">
        <div style="font-size: 16px; line-height: 1.6; color: #475569; margin-bottom: 30px;">${message}</div>
        <div style="margin-bottom: 30px;">${tableHtml}</div>
        <div style="text-align: center;">
          <a href="${window.location.origin}/GM_HMS/vendor/vendor_view/login.php?indent_no=${firstIndentNo}&branch=<?= urlencode($_SESSION['hospital_branch'] ?? 'nagarabhavi') ?>" 
             style="background: #0F172A; color: white; padding: 14px 32px; text-decoration: none; border-radius: 12px; font-weight: 700; display: inline-block;">
             ACCESS VENDOR PORTAL
          </a>
        </div>
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #F1F5F9; font-size: 12px; color: #94A3B8; text-align: center;">
          This is an automated system notification. Please do not reply directly.
        </div>
      </div>
    </div>
    `;
}

async function sendEmailNow(){
  const isSmartMode = document.getElementById('emailModal').dataset.smartMode === "true";
  const subject = document.getElementById('emailSubject').value.trim();
  const message = document.getElementById('emailBody').value.trim().replace(/\n/g, '<br>');
  
  if (isSmartMode) {
      PH.loading('Dispatching multiple emails...');
      
      // Group items by supplier_id
      const groups = {};
      currentEmailItems.forEach(item => {
          const sid = item.supplier_id || 'unassigned';
          if(!groups[sid]) groups[sid] = [];
          groups[sid].push(item);
      });
      
      let successCount = 0;
      let failCount = 0;
      
      for (const [sid, items] of Object.entries(groups)) {
          if (sid === 'unassigned') continue; // Skip unassigned items in smart mode
          
          const vendor = SUPPLIERS.find(s => s.supplier_id == sid);
          if (!vendor || !vendor.email) {
              failCount++;
              continue;
          }
          
          const tableHtml = generateHtmlTable(items);
          const fullHtmlBody = buildEmailTemplate(message, tableHtml, items[0].indent_no);
          
          try {
              const res = await fetch('send_email.php', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json' },
                  credentials: 'include',
                  body: JSON.stringify({ email_to: vendor.email, subject: subject, body: fullHtmlBody })
              }).then(r => r.json());
              
              if (res.success) {
                  successCount++;
                  try {
                      await fetch(API_BASE + 'pharmacy/indents/bulk-status', {
                          method: 'POST',
                          headers: { 'Content-Type': 'application/json' },
                          body: JSON.stringify({ ids: items.map(i => i.id), status: 'ordered' })
                      });
                  } catch(e) {}
              }
              else failCount++;
          } catch(e) { failCount++; }
      }
      
      emailModal.hide();
      if (failCount === 0) PH.success(`Successfully dispatched ${successCount} emails.`);
      else if (successCount > 0) PH.success(`Sent ${successCount} emails. (${failCount} failed or missing vendor emails).`);
      else PH.error('Failed to send emails. Ensure selected vendors have valid email addresses.');
      
      if (successCount > 0) loadIndents();
      
  } else {
      // Normal Single-Email Mode
      const selectEl = document.getElementById('emailTo');
      const customTo = document.getElementById('customEmail').value.trim();
      const to = customTo || selectEl.value;
      
      if(!to){PH.error('Please select or enter a recipient email'); return;}
      
      let newSupplierId = '';
      let newCompanyName = '';
      if (!customTo && selectEl.selectedIndex > 0) {
          const opt = selectEl.options[selectEl.selectedIndex];
          newSupplierId = opt.getAttribute('data-id');
          newCompanyName = opt.getAttribute('data-name');
      }

      // Automatically assign this vendor to these indents in the database
      if (newSupplierId) {
          try {
              await fetch(API_BASE + 'pharmacy/indents/bulk-assign', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json' },
                  body: JSON.stringify({
                      ids: currentEmailItems.map(i => i.id),
                      supplier_id: newSupplierId,
                      company_name: newCompanyName,
                      email: to
                  })
              });
              // Update local state so UI doesn't require refresh
              currentEmailItems.forEach(i => {
                  i.supplier_id = newSupplierId;
                  i.company_name = newCompanyName;
              });
          } catch (e) {
              console.warn("Failed to auto-assign vendor", e);
          }
      }
      
      const tableHtml = generateHtmlTable(currentEmailItems);
      const fullHtmlBody = buildEmailTemplate(message, tableHtml, currentEmailItems[0].indent_no);
      
      PH.loading('Dispatching Requisition...');
      try {
        const res = await fetch('send_email.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include',
          body: JSON.stringify({ email_to: to, subject: subject, body: fullHtmlBody })
        }).then(r => r.json());
        
        if (res.success) {
          PH.success('Notification sent to ' + to);
          emailModal.hide();
          try {
              await fetch(API_BASE + 'pharmacy/indents/bulk-status', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json' },
                  body: JSON.stringify({ ids: currentEmailItems.map(i => i.id), status: 'ordered' })
              });
          } catch(e) {}
          loadIndents();
        } else PH.error(res.message);
      } catch(e) { PH.error('Dispatch failed'); }
  }
}

function sendToVendor(id) {
    const item = allIndents.find(i => i.id === id);
    if (!item) return;
    
    // Group all items that share the exact same indent_no
    currentEmailItems = allIndents.filter(i => i.indent_no === item.indent_no);
    
    // Auto-fill logic
    document.getElementById('emailTo').value = '';
    document.getElementById('customEmail').value = '';
    
    if (item.supplier_id) {
        const vendor = SUPPLIERS.find(s => s.supplier_id == item.supplier_id);
        if (vendor && vendor.email) {
            document.getElementById('emailTo').value = vendor.email;
        }
    }
    
    document.getElementById('emailSubject').value = `[QUOTATION REQUEST] Requisition ${item.indent_no}`;
    document.getElementById('emailBody').value = `Dear Partner,\n\nPlease find our latest procurement requisition (${item.indent_no}) for ${item.item_name}. \n\nKindly review the requirements and submit your quotation through our digital portal using the link below.\n\nBest Regards,\nGM Hospital Procurement Team`;
    emailModal.show();
}
// -- EXPORT ---------------------------------------------------------
function exportCSV(){
  const data=filteredData.length?filteredData:allIndents;
  const cols=['indent_no','request_date','request_time','item_name','qty','priority','status','department','requested_by','supplier_id','company_name','remarks'];
  const hdr=cols.join(',');
  const rows=data.map(r=>cols.map(c=>JSON.stringify(r[c]||'')).join(','));
  const csv='data:text/csv;charset=utf-8,'+[hdr,...rows].join('\n');
  const a=document.createElement('a');a.href=encodeURI(csv);a.download='indent_requests_'+new Date().toISOString().slice(0,10)+'.csv';a.click();
  PH.success('CSV exported!');
}

function exportPrint(){
  const data=filteredData.length?filteredData:allIndents;
  const rows=data.map(r=>`<tr>
    <td>${r.indent_no}</td><td>${fmt.date(r.request_date)} ${r.request_time||''}</td><td>${r.item_name}</td>
    <td>${r.qty}</td><td>${r.priority}</td><td>${r.company_name||''}</td><td>${r.status}</td>
    <td>${r.department||''}</td><td>${r.requested_by||''}</td>
  </tr>`).join('');
  const html=`<!DOCTYPE html><html><head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">
<title>Indent Requests</title>
  <style>body{font-family:Arial,sans-serif;font-size:12px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:6px 8px;text-align:left}th{background:#1f6b4a;color:#fff}tr:nth-child(even){background:#f9f9f9}h2{color:#0F172A}</style>
  </head><body>
  <h2>Indent Requests Report</h2><p>Generated: ${new Date().toLocaleString()} | Total: ${data.length}</p>
  <table><thead><tr><th>Indent No</th><th>Date & Time</th><th>Item</th><th>Qty</th><th>Priority</th><th>Company</th><th>Status</th><th>Department</th><th>Requested By</th></tr></thead>
  <tbody>${rows}</tbody></table>
  <script>window.onload=()=>window.print()<\/script></body></html>`;
  const w=window.open('','_blank','width=1000,height=700');w.document.write(html);w.document.close();
}
</script>

















