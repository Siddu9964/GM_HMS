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

/* Responsive adjustments */
@media (max-width: 1200px) {
  .bento-grid { grid-template-columns: repeat(2, 1fr); gap: 1rem; }
}
@media (max-width: 768px) {
  .bento-grid { grid-template-columns: 1fr; }
  .smart-alert { flex-direction: column; align-items: flex-start; }
  .ph-page-title { font-size: 1.5rem !important; }
  .ph-page-subtitle { font-size: 0.85rem !important; }
  .d-flex.justify-content-between.align-items-center.mb-5 { flex-direction: column; align-items: flex-start !important; gap: 1rem; }
}
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

<!-- ===== WORKSPACE SECTION SWITCHER ===== -->
<div style="background:white;border-radius:20px;padding:6px;display:inline-flex;gap:4px;box-shadow:var(--glass-shadow);border:1px solid #E2E8F0;margin-bottom:1.75rem;">
  <button id="tab-btn-active" onclick="switchWorkspaceTab('active')" style="border:none;background:var(--proc-primary);color:#fff;border-radius:14px;font-weight:800;padding:0.65rem 1.75rem;font-size:0.9rem;cursor:pointer;display:flex;align-items:center;gap:8px;transition:all 0.25s ease;">
    <i class="fas fa-layer-group"></i> Active Workspace
    <span id="badge-active" style="background:rgba(255,255,255,0.25);color:#fff;border-radius:20px;padding:2px 10px;font-size:0.7rem;font-weight:900;">0</span>
  </button>
  <button id="tab-btn-history" onclick="switchWorkspaceTab('history')" style="border:none;background:transparent;color:#64748B;border-radius:14px;font-weight:800;padding:0.65rem 1.75rem;font-size:0.9rem;cursor:pointer;display:flex;align-items:center;gap:8px;transition:all 0.25s ease;">
    <i class="fas fa-history"></i> Sent History
    <span id="badge-history" style="background:#F1F5F9;color:#64748B;border-radius:20px;padding:2px 10px;font-size:0.7rem;font-weight:900;">0</span>
  </button>
</div>
<div id="panel-active-workspace">
  <div style="display:flex;align-items:center;gap:10px;margin-bottom:1.25rem;padding:12px 16px;background:linear-gradient(135deg,#EFF6FF,#F0FDF4);border-radius:14px;border:1px solid #BFDBFE;">
    <i class="fas fa-layer-group" style="color:var(--proc-primary);font-size:1rem;"></i>
    <div><div style="font-weight:800;color:#1E40AF;font-size:0.85rem;">Active Procurement Workspace</div>
    <div style="font-size:0.72rem;color:#3B82F6;font-weight:600;">Manage pending &amp; approved indent requests. Select items and use the action bar to Approve, Dispatch or Delete.</div></div>
  </div>
  <div class="d-flex align-items-center gap-3 mb-4">
    <div class="flex-grow-1 position-relative">
      <i class="fas fa-search" style="position:absolute;left:1.25rem;top:50%;transform:translateY(-50%);color:#94A3B8;"></i>
      <input type="text" id="searchInput" class="ph-input" style="padding-left:3rem;height:54px;border-radius:16px;border-color:transparent;box-shadow:var(--glass-shadow);" placeholder="Quick search by indent no, item name, or department...">
    </div>
    <select class="ph-select" id="statusFilter" style="width:160px;height:54px;border-radius:16px;border-color:transparent;box-shadow:var(--glass-shadow);">
      <option value="">All Status</option><option value="pending">Pending</option><option value="approved">Approved</option>
    </select>
    <button class="ph-btn ph-btn-outline" style="height:54px;width:54px;border-radius:16px;box-shadow:var(--glass-shadow);border-color:transparent;background:white;" onclick="loadIndents()"><i class="fas fa-sync-alt"></i></button>
  </div>
  <div class="ph-table-wrap p-0" style="overflow-x: auto;">
    <table class="ph-table w-100" id="indentsTable" style="min-width: 900px;">
      <thead><tr>
        <th style="width:40px; text-align:center;"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" style="width:18px;height:18px;accent-color:var(--proc-primary);"></th>
        <th style="width:25%">Product &amp; Source</th>
        <th style="width:20%">Logistics Info</th>
        <th style="width:15%">Qty &amp; Priority</th>
        <th style="width:15%">Workflow State</th>
        <th style="width:15%" class="text-end">Actions</th>
      </tr></thead>
      <tbody id="indentsBody"><tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></td></tr></tbody>
    </table>
  </div>
  <div class="d-flex align-items-center justify-content-between mt-4">
    <span id="tableInfo" style="font-weight:700;color:#94A3B8;font-size:0.85rem;"></span>
    <div id="pager" class="ph-pagination"></div>
  </div>
  <div id="bulkBar" style="display:none;position:fixed;bottom:2rem;left:50%;transform:translateX(-50%);background:rgba(15,23,42,0.9);backdrop-filter:blur(16px);padding:1rem 2rem;border-radius:24px;align-items:center;gap:1.5rem;z-index:1000;box-shadow:0 20px 50px rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.1);">
    <span id="selectedCount" style="color:#fff;font-weight:700;"></span>
    <div style="display:flex;gap:10px;">
      <button class="ph-btn" style="background:#10B981;color:#fff;border-radius:12px;font-weight:700;padding:8px 18px;border:none;cursor:pointer;" onclick="bulkChangeStatus('approved')"><i class="fas fa-check me-2"></i>Approve</button>
      <button class="ph-btn" style="background:#F59E0B;color:#fff;border-radius:12px;font-weight:700;padding:8px 18px;border:none;cursor:pointer;" onclick="bulkChangeStatus('cancelled')"><i class="fas fa-ban me-2"></i>Cancel</button>
      <button class="ph-btn" style="background:#0EA5E9;color:#fff;border-radius:12px;font-weight:700;padding:8px 18px;border:none;cursor:pointer;" onclick="bulkSendEmail()"><i class="fas fa-paper-plane me-2"></i>Dispatch</button>
      <button class="ph-btn" style="background:#8B5CF6;color:#fff;border-radius:12px;font-weight:700;padding:8px 18px;border:none;cursor:pointer;" onclick="exportSelectedPDF()"><i class="fas fa-file-pdf me-2"></i>Export PDF</button>
      <button class="ph-btn" style="background:#EF4444;color:#fff;border-radius:12px;font-weight:700;padding:8px 18px;border:none;cursor:pointer;" onclick="bulkDelete()"><i class="fas fa-trash me-2"></i>Delete</button>
    </div>
  </div>
</div>
<div id="panel-sent-history" style="display:none;">
  <div style="display:flex;align-items:center;gap:10px;margin-bottom:1.25rem;padding:12px 16px;background:linear-gradient(135deg,#F0FDF4,#ECFDF5);border-radius:14px;border:1px solid #6EE7B7;">
    <i class="fas fa-history" style="color:#059669;font-size:1rem;"></i>
    <div><div style="font-weight:800;color:#065F46;font-size:0.85rem;">Sent Indent History</div>
    <div style="font-size:0.72rem;color:#059669;font-weight:600;">Log of dispatched indents. Select and click "Un-send" to move back to Active Workspace.</div></div>
  </div>
  <div class="d-flex align-items-center gap-3 mb-4">
    <div class="flex-grow-1 position-relative">
      <i class="fas fa-search" style="position:absolute;left:1.25rem;top:50%;transform:translateY(-50%);color:#94A3B8;"></i>
      <input type="text" id="historySearchInput" class="ph-input" style="padding-left:3rem;height:54px;border-radius:16px;border-color:transparent;box-shadow:var(--glass-shadow);" placeholder="Search by indent no, item, supplier...">
    </div>
    <button class="ph-btn ph-btn-outline" style="height:54px;width:54px;border-radius:16px;box-shadow:var(--glass-shadow);border-color:transparent;background:white;" onclick="loadHistory()"><i class="fas fa-sync-alt"></i></button>
  </div>
  <div class="ph-table-wrap p-0" style="overflow-x: auto;">
    <table class="ph-table w-100" id="historyTable" style="min-width: 900px;">
      <thead><tr>
        <th style="width:40px; text-align:center;"><input type="checkbox" id="historySelectAll" onchange="toggleHistorySelectAll(this)" style="width:18px;height:18px;accent-color:#059669;"></th>
        <th style="width:25%">Indent No &amp; Date</th>
        <th style="width:20%">Items Count</th>
        <th style="width:25%">Supplier(s)</th>
        <th style="width:15%">Dispatch Method</th>
        <th style="width:15%" class="text-end">Actions</th>
      </tr></thead>
      <tbody id="historyBody"><tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-success" role="status"></div></td></tr></tbody>
    </table>
  </div>
  <div class="d-flex align-items-center justify-content-between mt-4">
    <span id="historyTableInfo" style="font-weight:700;color:#94A3B8;font-size:0.85rem;"></span>
    <div id="historyPager" class="ph-pagination"></div>
  </div>
  <div id="historyBulkBar" style="display:none;position:fixed;bottom:2rem;left:50%;transform:translateX(-50%);background:rgba(6,95,70,0.92);backdrop-filter:blur(16px);padding:1rem 2rem;border-radius:24px;align-items:center;gap:1.5rem;z-index:1000;box-shadow:0 20px 50px rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.1);">
    <span id="historySelectedCount" style="color:#fff;font-weight:700;"></span>
    <div style="display:flex;gap:10px;">
      <button class="ph-btn" style="background:#F59E0B;color:#fff;border-radius:12px;font-weight:700;padding:8px 18px;border:none;cursor:pointer;" onclick="bulkRevertSent()"><i class="fas fa-undo me-2"></i>Un-send</button>
    </div>
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
  #cartScrollArea::-webkit-scrollbar { width: 6px; }
  #cartScrollArea::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
  #cartScrollArea::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
  #cartScrollArea::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
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
            <h6><i class="fas fa-info-circle me-1"></i>General Details</h6>
            <div class="grid-4-cols">
                <div class="grid-item">
                  <label class="ph-label">Department / Ward</label>
                  <input type="text" class="ph-input" name="department" id="department" value="Pharmacy Store">
                </div>
                <div class="grid-item">
                  <label class="ph-label">Requested By</label>
                  <input type="text" class="ph-input" name="requested_by" id="requested_by" value="<?= htmlspecialchars($_SESSION['username'] ?? 'Pharmacist') ?>">
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
                <div class="grid-item" style="grid-column: span 2;">
                  <label class="ph-label"><i class="fas fa-envelope me-1"></i>Notify by Email (optional)</label>
                  <input type="email" class="ph-input" name="notify_email" id="notify_email" placeholder="store@hospital.com">
                </div>
                <div class="grid-item" style="grid-column: span 2;">
                  <label class="ph-label">Remarks</label>
                  <textarea class="ph-textarea" name="remarks" id="remarks" rows="1" placeholder="e.g. Stock critically low..."></textarea>
                </div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
              <h6 class="mb-0"><i class="fas fa-shopping-cart me-1"></i>Items Cart</h6>
              <div class="d-flex gap-2 align-items-center">
                <select class="ph-select" style="width:180px; height: 32px; font-size: 0.75rem;" id="bulkSupplier" onchange="applyBulkSupplier(this.value)">
                  <option value="">Bulk Set Supplier...</option>
                  <?php foreach($suppliers as $s): ?>
                    <option value="<?= $s['supplier_id'] ?>"><?= htmlspecialchars($s['company_name'] ?: $s['supplier_name']) ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-sm" style="background:#FEE2E2; color:#B91C1C; font-weight:700; border-radius:6px; padding: 4px 10px;" onclick="quickAddLowStock()">
                  <i class="fas fa-bolt me-1"></i> Low-Stock
                </button>
                <button type="button" class="btn btn-sm" style="background:#E0F2FE; color:#0369A1; font-weight:700; border-radius:6px; padding: 4px 10px;" onclick="addCartRow()">
                  <i class="fas fa-plus me-1"></i> Add Item
                </button>
              </div>
            </div>
            
            <div style="border: 1px solid rgba(31,107,74,0.15); border-radius: 8px; overflow: hidden; background: white; max-height: 40vh; overflow-y: auto; position: relative;" id="cartScrollArea">
                <table class="table table-borderless mb-0" id="cartTable" style="margin-bottom:0; width: 100%;">
                  <thead style="background:#F1F5F9; position: sticky; top: 0; z-index: 10;">
                    <tr>
                      <th style="width:5%; text-align:center; padding:8px 12px; vertical-align: middle; border-bottom: 1px solid #E2E8F0;">
                        <input type="checkbox" id="cartSelectAll" onclick="toggleCartAll(this)" style="width:16px;height:16px;accent-color:var(--proc-primary); margin:0;">
                      </th>
                      <th style="font-size:0.75rem; color:#64748B; width:40%; font-weight:800; padding:8px 12px; vertical-align: middle; border-bottom: 1px solid #E2E8F0;">Item Name *</th>
                      <th style="font-size:0.75rem; color:#64748B; width:15%; font-weight:800; padding:8px 12px; vertical-align: middle; border-bottom: 1px solid #E2E8F0;">Qty *</th>
                      <th style="font-size:0.75rem; color:#64748B; width:35%; font-weight:800; padding:8px 12px; vertical-align: middle; border-bottom: 1px solid #E2E8F0;">Supplier</th>
                      <th style="font-size:0.75rem; color:#64748B; width:5%; text-align:center; font-weight:800; padding:8px 12px; vertical-align: middle; border-bottom: 1px solid #E2E8F0;">Del</th>
                    </tr>
                  </thead>
                  <tbody id="cartBody">
                    <!-- Dynamic rows go here -->
                  </tbody>
                </table>
                <div id="emptyCartMsg" class="text-center py-5 text-muted" style="font-weight:600; font-size:0.85rem; display:none;">
                  <i class="fas fa-box-open mb-2" style="font-size: 1.5rem; color:#CBD5E1; display:block;"></i>
                  Cart is empty. Add an item or generate low-stock.
                </div>
            </div>
            
            <datalist id="lowStockList">
              <?php foreach($lowStockItems as $item): ?>
                <option value="<?= htmlspecialchars($item['product_name']) ?>" data-id="<?= $item['product_id'] ?>" data-qty="<?= $item['quantity'] ?>"></option>
              <?php endforeach; ?>
            </datalist>
            <select id="supplierTemplate" style="display:none;">
              <option value="">Select Supplier</option>
              <?php foreach($suppliers as $s): ?>
                <option value="<?= $s['supplier_id'] ?>" data-company="<?= htmlspecialchars($s['company_name']) ?>" data-email="<?= htmlspecialchars($s['email'] ?? '') ?>">
                    <?= htmlspecialchars($s['supplier_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
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

<!-- Dispatch Modal -->
<div class="modal fade" id="dispatchModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
    <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
      <div class="modal-header" style="background: #F8FAFC; border-bottom: 1px solid #E2E8F0; border-radius: 16px 16px 0 0; padding: 1rem 1.5rem;">
        <h5 class="modal-title" style="color: #0F172A; font-weight: 800;"><i class="fas fa-paper-plane me-2 text-primary"></i>Dispatch Indent</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        
        <!-- Tabs Header -->
        <ul class="nav nav-tabs px-3 pt-3" style="border-bottom: 1px solid #E2E8F0; background: #F8FAFC;" id="dispatchTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold" id="email-tab" data-bs-toggle="tab" data-bs-target="#tab-email" type="button" role="tab" style="color: #3B82F6;"><i class="fas fa-envelope me-2"></i>Email</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="whatsapp-tab" data-bs-toggle="tab" data-bs-target="#tab-whatsapp" type="button" role="tab" style="color: #10B981;"><i class="fab fa-whatsapp me-2"></i>WhatsApp</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="phone-tab" data-bs-toggle="tab" data-bs-target="#tab-phone" type="button" role="tab" style="color: #6366F1;"><i class="fas fa-phone-alt me-2"></i>Phone</button>
          </li>
        </ul>

        <!-- Tabs Content -->
        <div class="tab-content p-4" id="dispatchTabsContent">
          
          <!-- EMAIL TAB -->
          <div class="tab-pane fade show active" id="tab-email" role="tabpanel">
            <div id="smartDispatchBlock" style="display:none;"></div>
            <div class="mb-3" id="recipientBlock">
              <label class="ph-label fw-bold text-secondary mb-1">Recipient Email *</label>
              <select class="form-select ph-select" id="emailTo">
                <option value="">Select a recipient...</option>
                <?php foreach($suppliers as $s): ?>
                  <option value="<?= htmlspecialchars($s['email'] ?? '') ?>" data-id="<?= htmlspecialchars($s['supplier_id']) ?>" data-name="<?= htmlspecialchars($s['company_name'] ?: $s['supplier_name']) ?>"><?= htmlspecialchars($s['company_name'] . " (" . ($s['email'] ?? '') . ")") ?></option>
                <?php endforeach; ?>
              </select>
              <div class="small fw-bold mt-1 text-muted">Or type a custom email below:</div>
              <input type="email" class="form-control ph-input mt-1" id="customEmail" placeholder="custom@example.com">
            </div>
            <div class="mb-3">
              <label class="ph-label fw-bold text-secondary mb-1">Subject Line</label>
              <input type="text" class="form-control ph-input" id="emailSubject" value="Pharmacy Indent Request Notification">
            </div>
            <div class="mb-3">
              <label class="ph-label fw-bold text-secondary mb-1">Message Body</label>
              <textarea class="form-control ph-textarea" id="emailBody" rows="4"></textarea>
            </div>
            <div class="text-end mt-4">
                <button type="button" class="btn btn-primary fw-bold px-4" style="border-radius: 8px;" onclick="sendEmailNow()"><i class="fas fa-paper-plane me-2"></i>Send Email</button>
            </div>
          </div>

          <!-- WHATSAPP TAB -->
          <div class="tab-pane fade" id="tab-whatsapp" role="tabpanel">
            <div class="alert alert-success mb-3" style="border-radius: 8px; font-size: 0.85rem;">
                <i class="fab fa-whatsapp me-2"></i> This will open WhatsApp Web/App with the pre-filled message.
            </div>
            <div class="mb-3">
              <label class="ph-label fw-bold text-secondary mb-1">WhatsApp Number * (with country code)</label>
              <select class="form-select ph-select mb-2" id="whatsappTo">
                <option value="">Select from suppliers...</option>
                <?php foreach($suppliers as $s): ?>
                  <option value="<?= htmlspecialchars($s['phone'] ?? $s['mobile'] ?? '') ?>" data-id="<?= htmlspecialchars($s['supplier_id']) ?>"><?= htmlspecialchars($s['company_name'] . " (" . ($s['phone'] ?? $s['mobile'] ?? 'No Phone') . ")") ?></option>
                <?php endforeach; ?>
              </select>
              <input type="text" class="form-control ph-input" id="customWhatsapp" placeholder="e.g. +919876543210">
            </div>
            <div class="mb-3">
              <label class="ph-label fw-bold text-secondary mb-1">Message</label>
              <textarea class="form-control ph-textarea" id="whatsappBody" rows="5"></textarea>
            </div>
            <div class="text-end mt-4">
                <button type="button" class="btn btn-success fw-bold px-4" style="background: #10B981; border: none; border-radius: 8px;" onclick="sendWhatsappNow()"><i class="fab fa-whatsapp me-2"></i>Open WhatsApp &amp; Mark Sent</button>
            </div>
          </div>

          <!-- PHONE TAB -->
          <div class="tab-pane fade" id="tab-phone" role="tabpanel">
             <div class="alert alert-info mb-3" style="border-radius: 8px; font-size: 0.85rem;">
                <i class="fas fa-phone-alt me-2"></i> If you have communicated the requirements over a phone call, you can mark the indent as sent to maintain the workflow history.
            </div>
            <div class="text-center mt-4 mb-2">
                <button type="button" class="btn btn-primary fw-bold px-4 py-2" style="background: #6366F1; border: none; border-radius: 8px;" onclick="markPhoneSentNow()"><i class="fas fa-check-circle me-2"></i>Mark as Informed via Phone</button>
            </div>
          </div>

        </div>
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
const dispatchModal=new bootstrap.Modal(document.getElementById('dispatchModal'));

document.addEventListener('DOMContentLoaded',()=>{
  loadIndents();
  loadHistory();
  ['searchInput','statusFilter'].forEach(id=>document.getElementById(id).addEventListener(id==='searchInput'?'input':'change',()=>{currentPage=1;renderTable();}));
});

async function loadIndents(){
    try {
        const res = await phGet(API_BASE + 'pharmacy/indents?_t=' + Date.now());
        if (res.success) {
            allIndents = res.data || [];
            renderTable();
            updateActiveBadge();
        } else {
            PH.error(res.message);
        }
    } catch(e) {
        console.error(e);
        PH.error('Failed to load indents');
    }
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

    const pager = phPaginate(filteredData, currentPage, 10);
    document.getElementById('tableInfo').textContent = `Showing ${pager.items.length} of ${filteredData.length} records`;
    
    let html = '';
    if (!pager.items.length) {
        html = `<tr><td colspan="6" class="text-center py-5 text-muted">No records found matching your filters.</td></tr>`;
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
                    <div style="font-weight: 700; color: #475569;">${i.item_name}</div>
                    <div style="font-size: 0.75rem; color: var(--proc-primary); font-weight: 700; margin-top: 4px;">Dept: ${i.department || 'Pharmacy'}</div>
                </td>
                <td>
                    <div style="font-weight: 700; color: #64748B; font-size: 0.85rem;">${i.company_name || 'N/A'}</div>
                    <div style="font-size: 0.7rem; color: #94A3B8; margin-top: 4px;">ID: ${i.supplier_id || 'N/A'}</div>
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
        if (res.success) {
            PH.success('Quantity updated');
            loadIndents();
        } else {
            PH.error(res.message);
        }
    } catch (e) {
        PH.error('Failed to update quantity');
    }
}

async function bulkChangeStatus(status) {
    if (!selectedIds.size) return;
    try {
        const res = await phPost(API_BASE + 'pharmacy/indents/bulk-status', { ids: Array.from(selectedIds), status: status });
        if (res.success) {
            PH.success('Batch updated');
            selectedIds.clear();
            loadIndents();
        } else {
            PH.error(res.message);
        }
    } catch (e) {
        PH.error('Failed to update status');
    }
}

async function bulkDelete() {
    if (!selectedIds.size) return;
    PH.confirm('Remove Selected?', `Remove ${selectedIds.size} requisitions from Active Workspace?`, async () => {
        try {
            const res = await phPost(API_BASE + 'pharmacy/indents/bulk-delete', { ids: Array.from(selectedIds) });
            if (res.success) {
                PH.success('Removed');
                selectedIds.clear();
                loadIndents();
            } else {
                PH.error(res.message);
            }
        } catch (e) {
            PH.error('Failed to delete');
        }
    });
}

let cartRowIndex = 0;
const LOW_STOCK_ITEMS = <?= json_encode($lowStockItems) ?>;

function toggleCartAll(cb) {
    const checkboxes = document.querySelectorAll('.item-select');
    checkboxes.forEach(c => c.checked = cb.checked);
}

function applyBulkSupplier(supplierId) {
    if (!supplierId) return;
    const selects = document.querySelectorAll('.item-supplier');
    let applied = 0;
    selects.forEach(sel => {
        sel.value = supplierId;
        applied++;
    });
    if (applied > 0) {
        PH.success(`Supplier applied to ${applied} item(s)`);
    }
    document.getElementById('bulkSupplier').value = '';
}

function addCartRow(item = null, isChecked = true) {
  const tbody = document.getElementById('cartBody');
  document.getElementById('emptyCartMsg').style.display = 'none';
  
  const tr = document.createElement('tr');
  tr.id = 'cartRow_' + cartRowIndex;
  tr.style.borderBottom = '1px solid #F1F5F9';
  
  const itemName = item ? item.product_name : '';
  const itemQty = item ? (item.orderQty || 1) : 1;
  const itemProductId = item ? item.product_id : '';
  const checkedAttr = isChecked ? 'checked' : '';
  
  tr.innerHTML = `
    <td style="padding:8px 12px; text-align:center;">
        <input type="checkbox" class="item-select" style="width:18px;height:18px;accent-color:var(--proc-primary);" ${checkedAttr}>
    </td>
    <td style="padding:8px 12px;">
      <input type="text" class="ph-input item-name" list="lowStockList" placeholder="Search item..." required autocomplete="off" value="${itemName}" onchange="updateProductId(this, ${cartRowIndex})">
      <input type="hidden" class="product-id" id="prod_id_${cartRowIndex}" value="${itemProductId}">
    </td>
    <td style="padding:8px 12px;">
      <input type="number" class="ph-input item-qty" min="1" value="${itemQty}" required>
    </td>
    <td style="padding:8px 12px;">
      <select class="ph-select item-supplier">
        ${document.getElementById('supplierTemplate').innerHTML}
      </select>
    </td>
    <td style="padding:8px 12px; text-align:center;">
      <button type="button" class="btn btn-sm" style="color:#EF4444; background:transparent; border:none; padding:4px;" onclick="removeCartRow(${cartRowIndex})"><i class="fas fa-trash"></i></button>
    </td>
  `;
  tbody.appendChild(tr);
  cartRowIndex++;
  return tr;
}

function removeCartRow(index) {
  const row = document.getElementById('cartRow_' + index);
  if (row) row.remove();
  if (document.getElementById('cartBody').children.length === 0) {
    document.getElementById('emptyCartMsg').style.display = 'block';
  }
}

function quickAddLowStock() {
  const currentItems = Array.from(document.querySelectorAll('.item-name')).map(el => el.value);
  let added = 0;
  LOW_STOCK_ITEMS.forEach(item => {
    if (!currentItems.includes(item.product_name)) {
      item.orderQty = Math.max(50 - parseInt(item.quantity || 0), 10);
      addCartRow(item, true);
      added++;
    }
  });
  if(added > 0) PH.success(`Added ${added} low-stock items to cart.`);
  else PH.info('All low-stock items are already in the cart.');
}

function updateProductId(input, index) {
    const list = document.getElementById('lowStockList');
    const hidden = document.getElementById('prod_id_' + index);
    hidden.value = '';
    for(let option of list.options) {
        if(option.value === input.value) {
            hidden.value = option.getAttribute('data-id');
            break;
        }
    }
}

function openIndentModal(){
  document.getElementById('indentForm').reset();
  document.getElementById('id').value='';
  document.getElementById('modalTitle').textContent='New Indent Request';
  document.getElementById('department').value='Pharmacy Store';
  document.getElementById('status').value='pending';
  document.getElementById('cartBody').innerHTML = '';
  cartRowIndex = 0;
  
  // Pre-fill with low stock items unchecked by default
  if(LOW_STOCK_ITEMS.length > 0) {
      LOW_STOCK_ITEMS.forEach(item => {
          item.orderQty = Math.max(50 - parseInt(item.quantity || 0), 10);
          addCartRow(item, false);
      });
  } else {
      document.getElementById('emptyCartMsg').style.display = 'block';
  }
  
  document.getElementById('cartSelectAll').checked = false;
  modal.show();
}

function editIndent(i){
  document.getElementById('indentForm').reset();
  document.getElementById('id').value=i.id;
  document.getElementById('modalTitle').textContent='Edit Indent';
  ['department','requested_by','priority','status','remarks', 'notify_email'].forEach(f=>{
      if(document.getElementById(f)) document.getElementById(f).value=i[f]||'';
  });
  
  document.getElementById('cartBody').innerHTML = '';
  cartRowIndex = 0;
  const row = addCartRow({ product_name: i.item_name, product_id: i.product_id, orderQty: i.qty });
  
  setTimeout(() => {
      const supplierSelect = row.querySelector('.item-supplier');
      if(supplierSelect) supplierSelect.value = i.supplier_id || '';
  }, 50);
  
  modal.show();
}

async function saveIndent(e) {
  e.preventDefault();
  
  const idVal = document.getElementById('id').value;
  const formData = new FormData(e.target);
  const baseData = Object.fromEntries(formData.entries());
  
  const rows = document.getElementById('cartBody').children;
  if(rows.length === 0) {
      PH.error('Please add at least one item to the cart.');
      return;
  }
  
  const items = [];
  let valid = true;
  for(let i=0; i<rows.length; i++) {
      const row = rows[i];
      const isSelected = row.querySelector('.item-select').checked;
      if (!isSelected) continue; // Skip unchecked items
      
      const itemName = row.querySelector('.item-name').value;
      const qty = row.querySelector('.item-qty').value;
      const supplierSelect = row.querySelector('.item-supplier');
      const supplierId = supplierSelect.value;
      const productId = row.querySelector('.product-id').value;
      
      let companyName = '';
      let email = '';
      if(supplierId && supplierSelect.selectedIndex > 0) {
          const opt = supplierSelect.options[supplierSelect.selectedIndex];
          companyName = opt.getAttribute('data-company');
          email = opt.getAttribute('data-email');
      }
      
      if(!itemName || !qty) valid = false;
      
      items.push({
          item_name: itemName,
          product_id: productId,
          qty: qty,
          supplier_id: supplierId,
          company_name: companyName,
          email: email
      });
  }
  
  if (items.length === 0) {
      PH.error('Please select at least one item using the checkboxes.');
      return;
  }
  
  if(!valid) {
      PH.error('Please fill all required fields for the selected items.');
      return;
  }
  
  baseData.items = items;

  try {
      const res = await phPost(API_BASE + 'pharmacy/indents', baseData);
      if (res.success) {
          if (baseData.notify_email && baseData.notify_email.trim() !== '') {
              const indentRef = (res.data && res.data.indent_no) ? res.data.indent_no : 'DRAFT';
              await sendEmailFor(baseData.notify_email, items.map(i=>i.item_name).join(', '), indentRef);
          }
          PH.success('Saved to Workspace');
          modal.hide();
          loadIndents();
      } else {
          PH.error(res.message);
      }
  } catch(e) {
      PH.error('Failed to save indent');
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
  } catch (e) { /* Silent fail */ }
}

function deleteIndent(id){
  PH.confirm('Delete Indent Request?','This cannot be undone.',async ()=>{
    try {
        const res = await phPost(API_BASE + 'pharmacy/indents/bulk-delete', { ids: [id] });
        if (res.success) {
            PH.success('Deleted');
            loadIndents();
        } else {
            PH.error(res.message);
        }
    } catch(e) {
        PH.error('Failed to delete');
    }
  });
}

async function autoGenerateIndent() {
    try {
        const res = await phPost(API_BASE + 'pharmacy/indents/auto-generate', {});
        if (res.success) {
            PH.success(res.message || 'Draft indents generated for low-stock items.');
            loadIndents();
        } else {
            PH.error(res.message);
        }
    } catch (e) {
        PH.error('Failed to auto-generate indents');
    }
}

// -- EMAIL ----------------------------------------------------------
function generateHtmlTable(items){
  const rows = items.map(i => {
    return `
    <tr>
      <td style="border:1px solid #e2e8f0; padding:12px; font-family: sans-serif; font-size: 14px; color: #475569;">${i.indent_no || 'DRAFT'}</td>
      <td style="border:1px solid #e2e8f0; padding:12px; font-family: sans-serif; font-size: 14px; color: #1e293b;">${i.item_name}</td>
      <td style="border:1px solid #e2e8f0; padding:12px; font-family: sans-serif; font-size: 14px; text-align:center; color: #475569;">${i.qty}</td>
      <td style="border:1px solid #e2e8f0; padding:12px; font-family: sans-serif; font-size: 12px; text-align:center;">
        <span style="background-color: ${i.priority==='high'||i.priority==='urgent'?'#fee2e2':'#f1f5f9'}; color: ${i.priority==='high'||i.priority==='urgent'?'#991b1b':'#475569'}; padding: 4px 8px; border-radius: 4px; font-weight: 600; text-transform: uppercase;">
          ${i.priority || 'normal'}
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
  document.getElementById('emailSubject').value=`[APPROVAL REQUIRED] Pharmacy Indent Request: ${item.indent_no || 'DRAFT'}`; 
  document.getElementById('emailBody').value=DEFAULT_EMAIL_MSG;
  dispatchModal.show();
}

function bulkSendEmail(){
  if(!selectedIds.size)return;
  currentEmailItems = allIndents.filter(i=>selectedIds.has(i.id));
  
  const uniqueSuppliers = [...new Set(currentEmailItems.map(i => i.supplier_id).filter(Boolean))];
  const dispatchModalEl = document.getElementById('dispatchModal');
  const recipientBlock = document.getElementById('recipientBlock');
  const smartDispatchBlock = document.getElementById('smartDispatchBlock');

  document.getElementById('emailTo').value='';
  document.getElementById('customEmail').value = '';

  if (uniqueSuppliers.length > 1) {
      dispatchModalEl.dataset.smartMode = "true";
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
      dispatchModalEl.dataset.smartMode = "false";
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

  const waVendor = (currentEmailItems[0]||{}).company_name || 'Supplier';
  const waItemLines = currentEmailItems.map(i=>`  - ${i.item_name} | Qty: ${i.qty} | Priority: ${(i.priority||'').toUpperCase()}`).join('\n');
  document.getElementById('whatsappBody').value = `*GM Hospital - Procurement Requisition*\n\nDear ${waVendor},\n\nKindly arrange the following items:\n\n${waItemLines}\n\nRef No: ${(currentEmailItems[0]||{}).indent_no||''}\nDate: ${new Date().toLocaleDateString()}\n\nPlease confirm receipt.\n\n– GM Hospital Pharmacy`;

  dispatchModal.show();
}

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
  const isSmartMode = document.getElementById('dispatchModal').dataset.smartMode === "true";
  const subject = document.getElementById('emailSubject').value.trim();
  const message = document.getElementById('emailBody').value.trim().replace(/\n/g, '<br>');
  
  if (isSmartMode) {
      PH.loading('Dispatching multiple emails...');
      const groups = {};
      currentEmailItems.forEach(item => {
          const sid = item.supplier_id || 'unassigned';
          if(!groups[sid]) groups[sid] = [];
          groups[sid].push(item);
      });
      
      let successCount = 0;
      for (const [sid, items] of Object.entries(groups)) {
          if (sid === 'unassigned') continue;
          const vendor = SUPPLIERS.find(s => s.supplier_id == sid);
          if (!vendor || !vendor.email) continue;
          
          const fullHtmlBody = buildEmailTemplate(message, generateHtmlTable(items), items[0].indent_no || 'DISPATCHED');
          const res = await fetch('send_email.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ email_to: vendor.email, subject: subject, body: fullHtmlBody }) }).then(r => r.json());
          if (res.success) { 
              successCount++; 
              const ids = items.map(i=>i.id);
              await phPost(API_BASE + 'pharmacy/indents/mark-sent', { ids: ids, communication_method: 'Email' });
              const idSet = new Set(ids);
              allIndents = allIndents.filter(i => !idSet.has(i.id)); 
          }
      }
      dispatchModal.hide();
      PH.success(`Sent ${successCount} emails.`);
      selectedIds.clear();
      loadIndents();
      loadHistory();
      
  } else {
      const selectEl = document.getElementById('emailTo');
      const customTo = document.getElementById('customEmail').value.trim();
      const to = customTo || selectEl.value;
      if(!to){PH.error('Please select or enter a recipient email'); return;}
      
      if (!customTo && selectEl.selectedIndex > 0) {
          const opt = selectEl.options[selectEl.selectedIndex];
          currentEmailItems.forEach(i => {
              i.supplier_id = opt.getAttribute('data-id');
              i.company_name = opt.getAttribute('data-name');
          });
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
          dispatchModal.hide();
          try {
              const idsToMark = currentEmailItems.map(i=>i.id);
              if (!customTo && selectEl.selectedIndex > 0) {
                  const opt = selectEl.options[selectEl.selectedIndex];
                  const resAssign = await phPost(API_BASE + 'pharmacy/indents/bulk-assign', {
                      ids: idsToMark,
                      supplier_id: opt.getAttribute('data-id'),
                      company_name: opt.getAttribute('data-name'),
                      email: opt.value
                  });
                  if(!resAssign.success) { PH.error('Assign Error: ' + resAssign.message); }
              }
              const resMark = await phPost(API_BASE + 'pharmacy/indents/mark-sent', {
                  ids: idsToMark,
                  communication_method: 'Email'
              });
              
              if (resMark.success) {
                  const dispatchedIds = new Set(idsToMark);
                  allIndents = allIndents.filter(i => !dispatchedIds.has(i.id));
                  selectedIds.clear();
              } else {
                  PH.error('Mark Sent Error: ' + resMark.message);
              }
          } catch(e) { console.error(e); }
          loadIndents();
          loadHistory();
        } else PH.error(res.message);
      } catch(e) { PH.error('Dispatch failed'); }
  }
}

function sendToVendor(id) {
    const item = allIndents.find(i => i.id === id);
    if (!item) return;
    
    // Only send this specific item since we reassign indent_no upon dispatch
    currentEmailItems = [item];
    
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
    dispatchModal.show();
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

// â”€â”€ HISTORY LOAD & RENDER â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
let historyIndents = [], historyCurrentPage = 1, historySelectedIds = new Set(), historyFilteredData = [];

async function loadHistory(){
  try{
    const res=await phGet(API_BASE+'pharmacy/indents/history?_t=' + Date.now());
    if(res.success){historyIndents=res.data;renderHistoryTable();updateHistoryBadge();}
    else PH.error(res.message);
  }catch(e){PH.error('JS Error: ' + e.message); console.error(e);}
}

function renderHistoryTable() {
    const q = (document.getElementById('historySearchInput').value||'').toLowerCase();
    historyFilteredData = historyIndents.filter(ind => {
        if (q && !((ind.indent_no||'').toLowerCase().includes(q)||(ind.item_name||'').toLowerCase().includes(q)||(ind.department||'').toLowerCase().includes(q)||(ind.company_name||'').toLowerCase().includes(q))) return false;
        return true;
    });
    
    const groupMap = {};
    const groupedList = [];
    historyFilteredData.forEach(ind => {
        if(!groupMap[ind.indent_no]) {
            groupMap[ind.indent_no] = { 
                indent_no: ind.indent_no, 
                request_date: ind.request_date,
                communication_method: ind.communication_method,
                sent_by: ind.sent_by,
                suppliers: new Set(),
                items: [] 
            };
            groupedList.push(groupMap[ind.indent_no]);
        }
        if (ind.company_name) groupMap[ind.indent_no].suppliers.add(ind.company_name);
        groupMap[ind.indent_no].items.push(ind);
    });

    const pager = phPaginate(groupedList, historyCurrentPage, 10);
    document.getElementById('historyTableInfo').textContent = `Showing ${pager.items.length} Indent(s) (${historyFilteredData.length} records total)`;
    
    let html = '';
    if (!pager.items.length) {
        html = `<tr><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-history" style="font-size:2rem;color:#CBD5E1;display:block;margin-bottom:10px;"></i>No dispatched indents yet.</td></tr>`;
    } else {
        pager.items.forEach(group => {
            const allSelected = group.items.every(i => historySelectedIds.has(i.id));
            const method = group.communication_method || 'Manual';
            const sentBy = group.sent_by || 'Unknown';
            const mIcon = method==='WhatsApp'?'fab fa-whatsapp':method==='Phone'?'fas fa-phone-alt':'fas fa-envelope';
            const suppliers = Array.from(group.suppliers).join(', ') || '<span style="color:#CBD5E1;">Unassigned</span>';
            
            html += `<tr class="indent-row ${allSelected?'selected':''}" onclick="toggleHistoryRow('${group.indent_no}', !${allSelected})">
                <td><input type="checkbox" ${allSelected?'checked':''} onclick="event.stopPropagation();toggleHistoryRow('${group.indent_no}',this.checked)" style="width:20px;height:20px;accent-color:#059669;"></td>
                <td><div style="font-weight:800;color:#059669;font-size:0.95rem;">${group.indent_no}</div>
                    <div style="font-size:0.75rem;color:#94A3B8;font-weight:600;margin-top:3px;"><i class="far fa-calendar-alt me-1"></i>${fmt.date(group.request_date)}</div></td>
                <td><div style="font-weight:800;color:var(--proc-slate);font-size:1rem;">${group.items.length} Items</div></td>
                <td><div style="font-weight:700;color:#334155;font-size:0.85rem;">${suppliers}</div></td>
                <td><span style="display:inline-flex;align-items:center;gap:5px;background:#F0FDF4;color:#059669;border-radius:20px;padding:4px 10px;font-size:0.75rem;font-weight:800;"><i class="${mIcon}"></i>${method}</span>
                    <div style="font-size:0.7rem;color:#94A3B8;margin-top:4px;">By: <strong>${sentBy}</strong></div></td>
                <td class="text-end" onclick="event.stopPropagation()">
                    <button class="ph-btn ph-btn-sm" style="background: #0F172A; color: white; border-radius:10px; padding: 6px 12px; font-weight: 700;" onclick="viewHistoryIndent('${group.indent_no}')"><i class="fas fa-eye me-2"></i>View Items</button>
                </td></tr>`;
        });
    }
    document.getElementById('historyBody').innerHTML = html;
    phRenderPager(document.getElementById('historyPager'), pager.pages, historyCurrentPage, p => { historyCurrentPage = p; renderHistoryTable(); });
    updateHistoryBulkBar();
}

function toggleHistoryRow(indent_no, checked) {
    const items = historyIndents.filter(x => x.indent_no === indent_no);
    items.forEach(i => {
        if(checked) historySelectedIds.add(i.id); 
        else historySelectedIds.delete(i.id); 
    });
    renderHistoryTable(); 
}
function toggleHistorySelectAll(cb) { historyFilteredData.forEach(i => cb.checked ? historySelectedIds.add(i.id) : historySelectedIds.delete(i.id)); renderHistoryTable(); }

function updateHistoryBulkBar() {
    const bar = document.getElementById('historyBulkBar'); if(!bar) return;
    if(historySelectedIds.size>0){bar.style.display='flex';document.getElementById('historySelectedCount').innerHTML=`<i class="fas fa-check-circle me-2" style="color:#10B981"></i> ${historySelectedIds.size} Items Selected`;}
    else bar.style.display='none';
}

async function bulkRevertSent() {
    if(!historySelectedIds.size) return;
    PH.confirm('Un-send Selected?',`Revert ${historySelectedIds.size} item(s) back to Active Workspace?`,async()=>{
        const res = await phPost(API_BASE+'pharmacy/indents/revert-sent',{ids:Array.from(historySelectedIds)});
        if(res.success){PH.success('Reverted successfully');historySelectedIds.clear();loadHistory();loadIndents();}
        else PH.error(res.message);
    });
}

function viewHistoryIndent(indent_no) {
    const items = historyIndents.filter(x => x.indent_no === indent_no);
    if (!items.length) return;
    
    let tableHtml = `
    <table style="width:100%; border-collapse: collapse; text-align: left; font-size: 13px; margin-top: 15px;">
      <thead>
        <tr style="background:#f1f5f9; border-bottom:2px solid #e2e8f0;">
          <th style="padding:10px; color:#475569; font-weight:800;">Item Name</th>
          <th style="padding:10px; color:#475569; font-weight:800;">Supplier</th>
          <th style="padding:10px; text-align:center; color:#475569; font-weight:800;">Qty</th>
          <th style="padding:10px; color:#475569; font-weight:800;">Priority</th>
        </tr>
      </thead>
      <tbody>
    `;
    items.forEach(i => {
        tableHtml += `
        <tr style="border-bottom:1px solid #e2e8f0;">
          <td style="padding:10px; font-weight:700; color:#1e293b;">${i.item_name}</td>
          <td style="padding:10px; font-weight:600; color:#475569;">${i.company_name || 'N/A'}</td>
          <td style="padding:10px; text-align:center; font-weight:800; color:var(--proc-primary);">${i.qty}</td>
          <td style="padding:10px; text-transform:uppercase; font-size:11px; font-weight:700; color:#64748b;">${i.priority || 'Medium'}</td>
        </tr>`;
    });
    tableHtml += `</tbody></table>`;

    Swal.fire({
        title: `Indent Items <span style="font-size:0.9rem; color:#64748B; margin-left:10px;">${indent_no}</span>`,
        html: tableHtml,
        width: 700,
        confirmButtonColor: '#059669',
        confirmButtonText: 'Close'
    });
}

// â”€â”€ WhatsApp & Phone dispatch â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
async function sendWhatsappNow() {
    const selectEl = document.getElementById('whatsappTo');
    const customTo = document.getElementById('customWhatsapp').value.trim();
    const to = (customTo || selectEl.value).replace(/[^0-9]/g,'');
    if(!to){PH.error('Please enter a WhatsApp number');return;}
    
    if (!customTo && selectEl.selectedIndex > 0) {
        const opt = selectEl.options[selectEl.selectedIndex];
        const nameStr = opt.textContent || '';
        const companyMatch = nameStr.split(' (')[0].trim();
        currentEmailItems.forEach(i => {
            i.supplier_id = opt.getAttribute('data-id');
            i.company_name = companyMatch;
        });
    }

    const msg = document.getElementById('whatsappBody').value.trim();
    if(!msg){PH.error('Message body cannot be empty');return;}
    const url = `https://wa.me/${to}?text=${encodeURIComponent(msg)}`;
    window.open(url,'_blank');
    try{
        const idsToMark = currentEmailItems.map(i=>i.id);
        if (!customTo && selectEl.selectedIndex > 0) {
            const opt = selectEl.options[selectEl.selectedIndex];
            const nameStr = opt.textContent || '';
            const companyMatch = nameStr.split(' (')[0].trim();
            await phPost(API_BASE + 'pharmacy/indents/bulk-assign', {
                ids: idsToMark,
                supplier_id: opt.getAttribute('data-id'),
                company_name: companyMatch,
                email: ''
            });
        }
        
        const res = await phPost(API_BASE+'pharmacy/indents/mark-sent', {
            ids: idsToMark,
            communication_method: 'WhatsApp'
        });
        if(res.success){
            PH.success('Marked as sent via WhatsApp');
            const dispatchedIds = new Set(idsToMark);
            allIndents = allIndents.filter(i => !dispatchedIds.has(i.id));
            selectedIds.clear();
            dispatchModal.hide();
            loadIndents();
            loadHistory();
        } else PH.error(res.message);
    }catch(e){PH.error('Failed to update status');}
}

async function markPhoneSentNow() {
    PH.loading('Marking as sent via Phone...');
    try{
        const idsToMark = currentEmailItems.map(i=>i.id);
        const res = await phPost(API_BASE+'pharmacy/indents/mark-sent', {
            ids: idsToMark,
            communication_method: 'Phone'
        });
        if(res.success){
            PH.success('Marked as informed by Phone');
            const dispatchedIds = new Set(idsToMark);
            allIndents = allIndents.filter(i => !dispatchedIds.has(i.id));
            selectedIds.clear();
            dispatchModal.hide();
            loadIndents();
            loadHistory();
        } else PH.error(res.message);
    }catch(e){PH.error('Failed to update status');}
}

// â”€â”€ PDF Export for selected indents â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function exportSelectedPDF() {
    const data = selectedIds.size > 0 ? allIndents.filter(i=>selectedIds.has(i.id)) : filteredData.length ? filteredData : allIndents;
    if(!data.length){PH.error('No items to export');return;}
    const rows = data.map(r=>`<tr>
        <td>${r.indent_no}</td><td>${fmt.date(r.request_date)}</td><td>${r.item_name}</td>
        <td>${r.qty}</td><td>${(r.priority||'').toUpperCase()}</td><td>${r.company_name||'N/A'}</td>
        <td>${(r.status||'').toUpperCase()}</td><td>${r.department||'N/A'}</td><td>${r.requested_by||'N/A'}</td>
    </tr>`).join('');
    const html=`<!DOCTYPE html><html><head><title>Indent Requests</title>
    <style>body{font-family:Arial,sans-serif;font-size:11px;padding:20px;}
    h2{color:#0F172A;margin-bottom:5px;}p{color:#64748B;font-size:11px;margin-bottom:15px;}
    table{width:100%;border-collapse:collapse;}th,td{border:1px solid #ddd;padding:6px 8px;text-align:left;}
    th{background:#0EA5E9;color:#fff;font-weight:700;text-transform:uppercase;font-size:10px;}
    tr:nth-child(even){background:#F8FAFC;}.logo{font-size:18px;font-weight:900;color:#0EA5E9;}
    @media print{button{display:none;}}</style></head><body>
    <div class="logo">GM Hospital</div>
    <h2>Pharmacy Indent Requests</h2>
    <p>Generated: ${new Date().toLocaleString()} &nbsp;|&nbsp; Total: ${data.length} item(s)${selectedIds.size>0?' &nbsp;|&nbsp; <strong>Selected items only</strong>':''}</p>
    <table><thead><tr><th>Indent No</th><th>Date</th><th>Item</th><th>Qty</th><th>Priority</th><th>Company</th><th>Status</th><th>Department</th><th>Requested By</th></tr></thead>
    <tbody>${rows}</tbody></table>
    <script>window.onload=()=>window.print()<\/script></body></html>`;
    const w=window.open('','_blank','width=1100,height=750');w.document.write(html);w.document.close();
}

// â”€â”€ Tab Switcher â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
let currentTab = 'active';
function switchWorkspaceTab(tab) {
    currentTab = tab;
    const aP=document.getElementById('panel-active-workspace'), hP=document.getElementById('panel-sent-history');
    const bA=document.getElementById('tab-btn-active'), bH=document.getElementById('tab-btn-history');
    const bdA=document.getElementById('badge-active'), bdH=document.getElementById('badge-history');
    if(tab==='active'){
        aP.style.display='';hP.style.display='none';
        bA.style.background='var(--proc-primary)';bA.style.color='#fff';
        bdA.style.background='rgba(255,255,255,0.25)';bdA.style.color='#fff';
        bH.style.background='transparent';bH.style.color='#64748B';
        bdH.style.background='#F1F5F9';bdH.style.color='#64748B';
        const hBar=document.getElementById('historyBulkBar');if(hBar)hBar.style.display='none';
        if(typeof historySelectedIds!=='undefined')historySelectedIds.clear();
    } else {
        aP.style.display='none';hP.style.display='';
        bH.style.background='#059669';bH.style.color='#fff';
        bdH.style.background='rgba(255,255,255,0.25)';bdH.style.color='#fff';
        bA.style.background='transparent';bA.style.color='#64748B';
        bdA.style.background='#F1F5F9';bdA.style.color='#64748B';
        const aBar=document.getElementById('bulkBar');if(aBar)aBar.style.display='none';
        selectedIds.clear();loadHistory();
    }
}
function updateActiveBadge(){const b=document.getElementById('badge-active');if(b)b.textContent=allIndents.length;}
function updateHistoryBadge(){
    const b=document.getElementById('badge-history');
    if(b) {
        const uniqueCount = new Set(historyIndents.map(i => i.indent_no)).size;
        b.textContent = uniqueCount;
    }
}

document.addEventListener('DOMContentLoaded',()=>{
    const hsi=document.getElementById('historySearchInput');
    if(hsi)hsi.addEventListener('input',()=>{historyCurrentPage=1;renderHistoryTable();});
});
</script>

