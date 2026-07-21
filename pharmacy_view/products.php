<?php
// Extend session to 8 hours for full-shift use
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.cookie_lifetime', 28800);
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
require_once 'includes/db.php';
$pageTitle = 'Product Inventory — GM HMS';

$db = getDB();

// Advanced Stats for Bento Grid
$totalProducts = (int)$db->query("SELECT COUNT(*) FROM ph_product")->fetchColumn();
$lowStockThreshold = 20;
$lowStockItems = $db->query("SELECT product_id, product_name, quantity FROM ph_product WHERE quantity > 0 AND quantity <= $lowStockThreshold ORDER BY quantity ASC")->fetchAll();
$lowStockCount = count($lowStockItems);
$outOfStockCount = (int)$db->query("SELECT COUNT(*) FROM ph_product WHERE quantity = 0")->fetchColumn();
$expiredSoonCount = (int)$db->query("SELECT COUNT(*) FROM ph_product WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) AND expiry_date >= CURDATE()")->fetchColumn();

// Fetch unique forms, therapeutics for filters
$forms = $db->query("SELECT DISTINCT form FROM ph_product WHERE form != '' AND form IS NOT NULL ORDER BY form")->fetchAll(PDO::FETCH_COLUMN);
$therapeutics = $db->query("SELECT DISTINCT therapeutic FROM ph_product WHERE therapeutic != '' AND therapeutic IS NOT NULL ORDER BY therapeutic")->fetchAll(PDO::FETCH_COLUMN);

include 'includes/ph_head.php';
?>

<style>
/* ==========================================
   PREMIUM MEDICAL DESIGN SYSTEM (v2.0)
   Color: Medical Teal & Pure Slate
   ========================================== */
:root {
  --med-primary: #1f6b4a;    /* Deep Forest Green */
  --med-primary-dark: #12402c;
  --med-primary-light: rgba(31, 107, 74, 0.1);
  --med-bg: #f3efe6;
  --med-surface: rgba(255, 255, 255, 0.8);
  --med-border: rgba(226, 232, 240, 0.8);
  --med-text-main: #1E293B;
  --med-text-muted: #64748B;
  --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
  --glass-border: 1px solid rgba(255, 255, 255, 0.4);
}

.ph-page-body { background: var(--med-bg); font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; padding: 1.75rem !important; }

/* ===== BENTO KPI GRID ===== */
.bento-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  margin-bottom: 2rem;
}
.bento-card {
  background: var(--med-surface);
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
.bento-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.06); }
.bento-card::after {
  content: ''; position: absolute; top: -20%; right: -10%; width: 60px; height: 60px;
  background: radial-gradient(circle, var(--med-primary-light) 0%, transparent 70%);
  opacity: 0.3; border-radius: 50%;
}

.bento-icon {
  width: 42px; height: 42px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.15rem; flex-shrink: 0;
}
.bento-val { font-size: 1.5rem; font-weight: 800; color: var(--med-text-main); line-height: 1; margin-bottom: 0.15rem; }
.bento-lbl { font-size: 0.7rem; font-weight: 700; color: var(--med-text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0; }
.bento-trend { font-size: 0.65rem; font-weight: 700; padding: 2px 6px; border-radius: 6px; display: inline-block; margin-top: 0.25rem; }

/* Colors for cards */
.bc-total   { border-left: 6px solid var(--med-primary); }
.bc-low     { border-left: 6px solid #F59E0B; }
.bc-expired { border-left: 6px solid #EF4444; }
.bc-out     { border-left: 6px solid #6366F1; }

/* ===== SMART TABLE ===== */
.smart-table-container {
  background: white;
  border-radius: 24px;
  box-shadow: var(--glass-shadow);
  border: var(--glass-border);
  overflow: hidden;
}

.ph-table { border-collapse: separate; border-spacing: 0; width: 100%; }
.ph-table thead th {
  background: #F8FAFC;
  padding: 1.25rem 1.5rem;
  font-weight: 700;
  color: var(--med-text-muted);
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 0.05em;
  border-bottom: 1px solid #E2E8F0;
}

.ph-table tbody tr { transition: all 0.2s ease; cursor: pointer; }
.ph-table tbody tr:hover { background: #F1F5F9; }
.ph-table td { padding: 1.25rem 1.5rem; border-bottom: 1px solid #F1F5F9; vertical-align: middle; }

/* ===== PRODUCT CHIPS & BADGES ===== */
.prod-id-tag {
  background: #F1F5F9; color: #475569; padding: 4px 10px; border-radius: 8px;
  font-family: 'JetBrains Mono', monospace; font-size: 0.75rem; font-weight: 700;
}
.stock-pill {
  padding: 6px 12px; border-radius: 20px; font-weight: 800; font-size: 0.7rem;
  text-transform: uppercase; display: inline-flex; align-items: center; gap: 6px;
}
.stock-normal { background: #DCFCE7; color: #166534; }
.stock-low    { background: #FEF3C7; color: #92400E; }
.stock-out    { background: #FEE2E2; color: #991B1B; }

/* ===== ACTION BUTTONS (Heroicons style) ===== */
.action-btn {
  width: 36px; height: 36px; border-radius: 10px;
  display: inline-flex; align-items: center; justify-content: center;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid #E2E8F0; background: white; color: #64748B;
}
.action-btn:hover { border-color: var(--med-primary); color: var(--med-primary); background: var(--med-primary-light); transform: scale(1.1); }
.action-btn.delete:hover { border-color: #EF4444; color: #EF4444; background: #FEE2E2; }

/* ===== SEARCH WORKSPACE ===== */
.workspace-header {
  background: white; border-radius: 20px; padding: 1rem;
  box-shadow: var(--glass-shadow); display: flex; gap: 1rem; align-items: center;
  margin-bottom: 1.5rem;
}
.search-pill {
  flex: 1; position: relative;
}
.search-pill i { position: absolute; left: 1.25rem; top: 50%; transform: translateY(-50%); color: #94A3B8; }
.search-pill input {
  width: 100%; border: 1px solid #E2E8F0; border-radius: 14px;
  padding: 0.875rem 1rem 0.875rem 3rem; font-weight: 600; font-size: 0.95rem;
  transition: all 0.3s ease;
}
.search-pill input:focus { outline: none; border-color: var(--med-primary); box-shadow: 0 0 0 4px var(--med-primary-light); }

/* Custom Modal */
.modal-content { border-radius: 24px; border: none; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); }
.modal-header { border-bottom: 1px solid #F1F5F9; padding: 1.5rem 2rem; }
.modal-title { font-weight: 800; color: var(--med-text-main); }
.ph-input, .ph-select {
  border-radius: 12px; border: 1.5px solid #E2E8F0; padding: 0.75rem 1rem;
  font-weight: 600; transition: all 0.2s ease;
}
.ph-input:focus { border-color: var(--med-primary); box-shadow: 0 0 0 3px var(--med-primary-light); }

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
</style>

<div class="ph-wrap">
<?php include 'includes/pharmacy_sidebar.php'; ?>
<div id="ph-content">
<?php include 'includes/pharmacy_navbar.php'; ?>
<div class="ph-page-body">

  <!-- Header Section -->
  <div class="d-flex justify-content-between align-items-center mb-5">
    <div>
      <h1 class="ph-page-title" style="font-size: 2.5rem; font-weight: 900; letter-spacing: -0.5px; color: var(--med-text-main);">Product Inventory — GM HMS</h1>
      <p class="ph-page-subtitle" style="font-weight: 600; color: var(--med-text-muted);">Manage medicines, stock levels, and procurement rates</p>
    </div>
    <button class="ph-btn" style="background: var(--med-primary); color: white; padding: 0.75rem 1.5rem; border-radius: 14px; font-weight: 700; box-shadow: 0 10px 20px rgba(13, 148, 136, 0.25);" onclick="openProductModal()">
      <i class="fas fa-plus me-2"></i> New Product
    </button>
  </div>

  <!-- Smart Suggestion Banner -->
  <?php if($lowStockCount > 0): ?>
  <div class="smart-alert">
    <div class="d-flex align-items-center gap-3">
      <div style="width:50px; height:50px; border-radius:14px; background: #FEF3C7; color: #D97706; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;"><i class="fas fa-lightbulb"></i></div>
      <div>
        <div style="font-weight: 800; color: var(--med-text-main); font-size: 1rem;">Stock Replenishment Suggested</div>
        <div style="font-size: 0.8rem; color: var(--med-text-muted); font-weight: 600;"><?= $lowStockCount ?> items require attention</div>
      </div>
    </div>
    <div class="low-stock-scroll">
      <?php foreach($lowStockItems as $item): ?>
        <div class="stock-item-tag">
          <i class="fas fa-pills" style="color: var(--med-primary)"></i>
          <?= htmlspecialchars($item['product_name']) ?> (<?= $item['quantity'] ?> left)
        </div>
      <?php endforeach; ?>
    </div>
    <button class="ph-btn" style="background: #0F172A; color: white; border-radius: 12px; font-weight: 700;" onclick="window.location.href='indent_request.php'">
      <i class="fas fa-shopping-cart me-2"></i> Create Indent
    </button>
  </div>
  <?php endif; ?>

  <!-- Bento KPI Grid -->
  <div class="bento-grid">
    <div class="bento-card bc-total">
      <div class="bento-icon" style="background: var(--med-primary-light); color: var(--med-primary);"><i class="fas fa-pills"></i></div>
      <div style="flex: 1;">
        <div class="bento-val"><?= $totalProducts ?></div>
        <div class="bento-lbl">Master Products</div>
        <div class="bento-trend" style="background: #E0F2FE; color: #0369A1;">Active Inventory</div>
      </div>
    </div>
    <div class="bento-card bc-low">
      <div class="bento-icon" style="background: #FEF3C7; color: #D97706;"><i class="fas fa-exclamation-triangle"></i></div>
      <div style="flex: 1;">
        <div class="bento-val" style="color: #D97706;"><?= $lowStockCount ?></div>
        <div class="bento-lbl">Low Stock Alerts</div>
        <div class="bento-trend" style="background: #FEF3C7; color: #92400E;">Refill Required</div>
      </div>
    </div>
    <div class="bento-card bc-out">
      <div class="bento-icon" style="background: #E0E7FF; color: #4F46E5;"><i class="fas fa-times-circle"></i></div>
      <div style="flex: 1;">
        <div class="bento-val" style="color: #4F46E5;"><?= $outOfStockCount ?></div>
        <div class="bento-lbl">Out of Stock</div>
        <div class="bento-trend" style="background: #E0E7FF; color: #3730A3;">Critical Action</div>
      </div>
    </div>
    <div class="bento-card bc-expired">
      <div class="bento-icon" style="background: #FEE2E2; color: #DC2626;"><i class="fas fa-history"></i></div>
      <div style="flex: 1;">
        <div class="bento-val" style="color: #DC2626;"><?= $expiredSoonCount ?></div>
        <div class="bento-lbl">Near Expiry</div>
        <div class="bento-trend" style="background: #FEE2E2; color: #991B1B;">< 90 Days left</div>
      </div>
    </div>
  </div>

  <!-- Workspace Header (Search & Quick Filters) -->
  <div class="workspace-header">
    <div class="search-pill">
      <i class="fas fa-search"></i>
      <input type="text" id="searchInput" placeholder="Search by name, composition, or ID...">
    </div>
    <select class="ph-select" id="stockFilter" style="width: 180px; height: 50px;">
      <option value="">All Stock</option>
      <option value="out">Out of Stock</option>
      <option value="low">Low Stock</option>
      <option value="normal">In Stock</option>
    </select>
    <select class="ph-select" id="expiryFilter" style="width: 180px; height: 50px;">
      <option value="">Expiry Status</option>
      <option value="expired">Expired Items</option>
      <option value="expiring_soon">Expiring Soon</option>
      <option value="valid">Safe/Valid</option>
    </select>
    <button class="ph-btn ph-btn-outline" style="height: 50px; border-radius: 14px;" data-bs-toggle="collapse" data-bs-target="#advancedFilters">
      <i class="fas fa-sliders-h me-2"></i> Advanced
    </button>
  </div>

  <!-- Collapsible Advanced Filters -->
  <div class="collapse mb-4" id="advancedFilters">
    <div class="p-4 rounded-4" style="background: white; border: 1px solid #E2E8F0; box-shadow: var(--glass-shadow);">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="ph-label">Formulation</label>
          <select class="ph-select w-100" id="formFilter">
            <option value="">All Forms</option>
            <?php foreach($forms as $f): ?><option value="<?= htmlspecialchars($f) ?>"><?= htmlspecialchars($f) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="ph-label">Therapeutic Category</label>
          <select class="ph-select w-100" id="therapeuticFilter">
            <option value="">All Categories</option>
            <?php foreach($therapeutics as $t): ?><option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4 d-flex align-items-end justify-content-end">
          <button class="ph-btn w-100" style="background:#F1F5F9; color:#475569; font-weight: 700; height: 50px;" onclick="resetFilters()">
            <i class="fas fa-times-circle me-2"></i> Clear All Filters
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Smart Table -->
  <div class="smart-table-container">
    <div class="ph-table-wrap">
      <table class="ph-table" id="productsTable">
        <thead>
          <tr>
            <th>Product Identification</th>
            <th>Categorization</th>
            <th>Form & Pack</th>
            <th class="text-end">Pur. Rate</th>
            <th class="text-end">MRP</th>
            <th class="text-end">Selling</th>
            <th class="text-center">Current Stock</th>
            <th>Batch & Expiry</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody id="productsBody">
          <tr><td colspan="9" class="text-center py-5"><div class="spinner-border text-teal" role="status"></div></td></tr>
        </tbody>
      </table>
    </div>
    <div class="p-4 border-top bg-light d-flex justify-content-between align-items-center">
      <div id="tableInfo" class="text-muted small fw-bold"></div>
      <div id="pager" class="ph-pagination m-0"></div>
    </div>
  </div>

</div><!-- body -->
</div><!-- content -->
</div><!-- wrap -->

<!-- Add/Edit Product Modal -->
<style>
  .compact-modal .ph-label { font-size: 0.65rem; font-weight: 800; color: #1F6B4A; margin-bottom: 2px; text-transform: uppercase; }
  .compact-modal .ph-input { padding: 4px 8px; font-size: 0.8rem; height: 32px; border: 1px solid rgba(31,107,74,0.2); border-radius: 6px; background: #FFF; color: #1F6B4A; font-weight: 600; width: 100%; box-shadow: none; box-sizing: border-box; }
  .compact-modal .ph-input:focus { border-color: #1F6B4A; outline: none; box-shadow: 0 0 0 2px rgba(31,107,74,0.1); }
  .compact-modal .input-group { display: flex; width: 100%; }
  .compact-modal .input-group-text { padding: 4px 8px; font-size: 0.8rem; height: 32px; border-radius: 6px 0 0 6px; border: 1px solid rgba(31,107,74,0.2); background: #F3EFE6; color: #1F6B4A; font-weight: 800; border-right: none; display: flex; align-items: center; box-sizing: border-box; }
  .compact-modal .input-group .ph-input { border-radius: 0 6px 6px 0; border-left: none; flex: 1; }
  .compact-modal h6 { font-size: 0.8rem; margin-top: 4px; margin-bottom: 8px !important; color: #1F6B4A; font-weight: 800; border-bottom: 1px solid rgba(31,107,74,0.1); padding-bottom: 4px; }
  .compact-modal .modal-body { padding: 12px 20px; }
  .compact-modal .modal-header, .compact-modal .modal-footer { padding: 10px 20px; }
  
  /* Hardcoded Grid Layouts */
  .grid-4-cols { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 16px; }
  .grid-2-cols { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
  .grid-split { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
  .grid-item { display: flex; flex-direction: column; }
</style>
<div class="modal fade compact-modal" id="productModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 1100px;">
    <div class="modal-content" style="background: #F3EFE6; border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
      <div class="modal-header" style="border-bottom: 1px solid rgba(31,107,74,0.15);">
        <h5 class="modal-title m-0" id="modalTitle" style="color: #1F6B4A; font-weight: 900; font-size: 1.1rem; letter-spacing: -0.5px;">Product Configuration</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(34%) sepia(16%) saturate(1637%) hue-rotate(107deg) brightness(97%) contrast(89%); opacity: 0.8;"></button>
      </div>
      <form id="productForm" onsubmit="saveProduct(event)">
        <div class="modal-body">
          <input type="hidden" name="sl_no" id="sl_no">
          <input type="hidden" name="action" id="formAction" value="save">
          
          <h6><i class="fas fa-info-circle me-1"></i>Basic Information</h6>
          <div class="grid-4-cols">
            <div class="grid-item">
              <label class="ph-label">Reference ID</label>
              <input type="text" class="ph-input" name="product_id" id="product_id" required readonly style="background:#E3F2EC;">
            </div>
            <div class="grid-item">
              <label class="ph-label">Commercial Name *</label>
              <input type="text" class="ph-input" name="product_name" id="product_name" required placeholder="e.g. Paracetamol 500mg">
            </div>
            <div class="grid-item">
              <label class="ph-label">Generic Composition</label>
              <input type="text" class="ph-input" name="content" id="content" placeholder="e.g. Paracetamol IP">
            </div>
            <div class="grid-item">
              <label class="ph-label">Strength</label>
              <input type="text" class="ph-input" name="strength" id="strength" placeholder="e.g. 500mg">
            </div>
            <div class="grid-item">
              <label class="ph-label">Formulation</label>
              <input type="text" class="ph-input" name="form" id="form" placeholder="Tablet, Syrup..." list="formOptions">
              <datalist id="formOptions">
                <?php foreach($forms as $f): ?><option value="<?= htmlspecialchars($f) ?>"><?php endforeach; ?>
              </datalist>
            </div>
            <div class="grid-item">
              <label class="ph-label">Therapeutic Class</label>
              <input type="text" class="ph-input" name="therapeutic" id="therapeutic" list="theraOptions">
              <datalist id="theraOptions">
                <?php foreach($therapeutics as $t): ?><option value="<?= htmlspecialchars($t) ?>"><?php endforeach; ?>
              </datalist>
            </div>
            <div class="grid-item">
              <label class="ph-label">Pack Details</label>
              <input type="text" class="ph-input" name="pack" id="pack" placeholder="e.g. 10x10 Strips">
            </div>
            <div class="grid-item">
              <label class="ph-label">Image Asset URL</label>
              <input type="text" class="ph-input" name="product_image" id="product_image" placeholder="https://image.com/med.png">
            </div>
          </div>

          <div class="grid-split">
            <div>
              <h6><i class="fas fa-warehouse me-1"></i>Inventory & Storage</h6>
              <div class="grid-2-cols">
                <div class="grid-item">
                  <label class="ph-label">Initial Stock Qty</label>
                  <input type="number" class="ph-input" name="quantity" id="quantity" value="0" min="0">
                </div>
                <div class="grid-item">
                  <label class="ph-label">Batch Identifier</label>
                  <input type="text" class="ph-input" name="batch_number" id="batch_number" placeholder="e.g. BT-9921">
                </div>
                <div class="grid-item">
                  <label class="ph-label">Expiry Date</label>
                  <input type="date" class="ph-input" name="expiry_date" id="expiry_date">
                </div>
                <div class="grid-item">
                  <label class="ph-label">Rack/Bin Location</label>
                  <input type="text" class="ph-input" name="rack_location" id="rack_location" placeholder="e.g. Shelf A">
                </div>
              </div>
            </div>
            
            <div>
              <h6><i class="fas fa-tag me-1"></i>Pricing Details</h6>
              <div class="grid-2-cols">
                <div class="grid-item">
                  <label class="ph-label">Purchase Rate</label>
                  <div class="input-group">
                    <span class="input-group-text">₹</span>
                    <input type="number" step="0.01" class="ph-input" name="purchase_rate" id="purchase_rate" value="0.00">
                  </div>
                </div>
                <div class="grid-item">
                  <label class="ph-label">Tax (GST) %</label>
                  <input type="number" step="0.1" class="ph-input" name="tax_percent" id="tax_percent" value="12.0">
                </div>
                <div class="grid-item">
                  <label class="ph-label">MRP</label>
                  <div class="input-group">
                    <span class="input-group-text">₹</span>
                    <input type="number" step="0.01" class="ph-input" name="mrp" id="mrp" value="0.00">
                  </div>
                </div>
                <div class="grid-item">
                  <label class="ph-label">Sales Price</label>
                  <div class="input-group">
                    <span class="input-group-text">₹</span>
                    <input type="number" step="0.01" class="ph-input" name="sales_price" id="sales_price" value="0.00">
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="modal-footer" style="background: #F3EFE6; border-top: 1px solid rgba(31,107,74,0.15); border-radius: 0 0 12px 12px;">
          <button type="button" class="btn btn-sm" data-bs-dismiss="modal" style="background: transparent; color: #1F6B4A; border: 1.5px solid rgba(31,107,74,0.2); border-radius: 8px; font-weight: 700;">Cancel</button>
          <button type="submit" class="btn btn-sm" style="background: #1F6B4A; color: #FFFFFF; border: none; border-radius: 8px; font-weight: 700; box-shadow: 0 4px 10px rgba(31,107,74,0.2);">
            <i class="fas fa-save me-1"></i> Commit Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'includes/ph_foot.php'; ?>
<script>
let allProducts = [];
let currentPage = 1;
const PER_PAGE = 12;
const modal = new bootstrap.Modal(document.getElementById('productModal'));
const LOW_STOCK_THRESHOLD = <?= getSetting('low_stock_threshold', '20') ?>;

document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
    ['searchInput', 'formFilter', 'therapeuticFilter', 'stockFilter', 'expiryFilter'].forEach(id => {
        document.getElementById(id).addEventListener(id === 'searchInput' ? 'input' : 'change', () => { 
            currentPage = 1; 
            renderTable(); 
        });
    });
});

function resetFilters() {
    ['searchInput', 'formFilter', 'therapeuticFilter', 'stockFilter', 'expiryFilter'].forEach(id => document.getElementById(id).value = '');
    currentPage = 1;
    renderTable();
}

async function loadProducts() {
    try {
        const res = await phGet(API_BASE + 'pharmacy/products');
        if (res.success) {
            allProducts = res.data;
            renderTable();
        } else {
            PH.error(res.message);
        }
    } catch (e) { PH.error('Sync failed'); }
}

function renderTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    const sf = document.getElementById('stockFilter').value;
    const ef = document.getElementById('expiryFilter').value;
    const ff = document.getElementById('formFilter').value;
    const tf = document.getElementById('therapeuticFilter').value;
    
    let filtered = allProducts.filter(p => {
        if (q && !((p.product_name||'').toLowerCase().includes(q) || (p.product_id||'').toLowerCase().includes(q) || (p.content||'').toLowerCase().includes(q))) return false;
        if (ff && p.form !== ff) return false;
        if (tf && p.therapeutic !== tf) return false;
        
        if (sf) {
            const qty = parseInt(p.quantity || 0);
            if (sf === 'out' && qty > 0) return false;
            if (sf === 'low' && (qty <= 0 || qty > LOW_STOCK_THRESHOLD)) return false;
            if (sf === 'normal' && qty <= LOW_STOCK_THRESHOLD) return false;
        }

        if (ef) {
            const now = new Date();
            const exp = p.expiry_date ? new Date(p.expiry_date) : null;
            if (!exp && ef !== 'valid') return false;
            if (exp) {
                const diff = (exp - now) / (86400000);
                if (ef === 'expired' && diff >= 0) return false;
                if (ef === 'expiring_soon' && (diff < 0 || diff > 90)) return false;
                if (ef === 'valid' && diff <= 90) return false;
            }
        }
        return true;
    });

    const pager = phPaginate(filtered, currentPage, PER_PAGE);
    document.getElementById('tableInfo').textContent = `Displaying ${pager.items.length} of ${filtered.length} products`;
    
    let html = '';
    if (!pager.items.length) {
        html = `<tr><td colspan="9" class="text-center py-5 text-muted">No matching inventory records found.</td></tr>`;
    } else {
        pager.items.forEach(p => {
            const qty = parseInt(p.quantity || 0);
            let sClass = 'stock-normal', sIcon = 'fa-check-circle', sLbl = 'In Stock';
            if (qty === 0) { sClass = 'stock-out'; sIcon = 'fa-times-circle'; sLbl = 'Out of Stock'; }
            else if (qty <= LOW_STOCK_THRESHOLD) { sClass = 'stock-low'; sIcon = 'fa-exclamation-triangle'; sLbl = 'Low Stock'; }

            html += `
            <tr onclick="editProduct(${JSON.stringify(p).replace(/'/g, "&apos;")})">
                <td>
                    <div class="fw-bold" style="color: var(--med-text-main);">${p.product_name}</div>
                    <span class="prod-id-tag mt-1">${p.product_id}</span>
                </td>
                <td>
                    <div style="font-weight:700; font-size:0.8rem; color:#475569;">${p.therapeutic || 'Uncategorized'}</div>
                    <div class="text-muted small text-truncate" style="max-width:180px;">${p.content || '—'}</div>
                </td>
                <td>
                    <span class="badge" style="background:var(--med-primary-light); color:var(--med-primary-dark); font-weight:800; font-size:0.65rem;">${p.form || 'UNIT'}</span>
                    <div class="small text-muted mt-1"><i class="fas fa-box-open me-1"></i>${p.pack || '—'}</div>
                </td>
                <td class="text-end fw-bold text-muted">₹${parseFloat(p.purchase_rate||0).toFixed(2)}</td>
                <td class="text-end fw-bold">₹${parseFloat(p.mrp||0).toFixed(2)}</td>
                <td class="text-end"><span style="color:var(--med-primary); font-weight:900;">₹${parseFloat(p.sales_price||0).toFixed(2)}</span></td>
                <td class="text-center">
                    <div class="stock-pill ${sClass}">
                        <i class="fas ${sIcon}"></i>
                        ${qty} ${sLbl}
                    </div>
                </td>
                <td>
                    <div style="font-weight:700; font-size:0.75rem;">${expiryBadge(p.expiry_date)}</div>
                    <div class="small text-muted">Batch: ${p.batch_number || 'N/A'}</div>
                </td>
                <td class="text-end" onclick="event.stopPropagation()">
                    <button class="action-btn" onclick='editProduct(${JSON.stringify(p).replace(/'/g, "&apos;")})'><i class="fas fa-pencil-alt"></i></button>
                    <button class="action-btn delete ms-1" onclick="deleteProduct(${p.sl_no})"><i class="fas fa-trash-alt"></i></button>
                </td>
            </tr>`;
        });
    }
    
    document.getElementById('productsBody').innerHTML = html;
    phRenderPager(document.getElementById('pager'), pager.pages, currentPage, p => { currentPage = p; renderTable(); });
}

function openProductModal() {
    document.getElementById('productForm').reset();
    document.getElementById('sl_no').value = '';
    document.getElementById('formAction').value = 'create';
    document.getElementById('product_id').value = 'PRD-' + Math.random().toString().substr(2, 6);
    modal.show();
}

function editProduct(p) {
    document.getElementById('productForm').reset();
    document.getElementById('sl_no').value = p.sl_no;
    document.getElementById('formAction').value = 'update';
    ['product_id', 'product_name', 'content', 'strength', 'form', 'therapeutic', 'quantity', 'pack', 'batch_number', 'expiry_date', 'purchase_rate', 'mrp', 'sales_price', 'pack_rate', 'tax_percent', 'unit', 'pack_size', 'min_stock', 'max_stock', 'rack_location', 'product_image'].forEach(f => {
        if(document.getElementById(f)) document.getElementById(f).value = p[f] || '';
    });
    modal.show();
}

async function saveProduct(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());
    const action = document.getElementById('formAction').value;
    const sl_no = document.getElementById('sl_no').value;
    
    PH.loading('Syncing...');
    try {
        let url = API_BASE + 'pharmacy/products' + (action === 'update' ? '/' + sl_no : '');
        const res = await fetch(url, { method: action === 'update' ? 'PUT' : 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) }).then(r => r.json());
        if (res.success) { PH.success('Inventory Updated'); modal.hide(); loadProducts(); } else PH.error(res.message);
    } catch (err) { PH.error('Write failed'); }
}

function deleteProduct(sl_no) {
    PH.confirm('Remove Product?', 'This will permanently delete this record from the master list.', async () => {
        const res = await fetch(API_BASE + 'pharmacy/products/' + sl_no, { method: 'DELETE' }).then(r => r.json());
        if (res.success) { PH.success('Removed'); loadProducts(); } else PH.error(res.message);
    });
}
</script>
