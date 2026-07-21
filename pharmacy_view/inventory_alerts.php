<?php
// Extend session to 8 hours for full-shift use
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.cookie_lifetime', 28800);
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
require_once 'includes/db.php';
$pageTitle = 'Inventory Alerts';
$db        = getDB();
$threshold  = (int)getSetting('low_stock_threshold', '20');
$expiryDays = (int)getSetting('expiry_alert_days', '60');

// PHP version of expiryBadge — fixes Fatal error when called server-side
function phpExpiryBadge(?string $date): string {
    if (!$date || $date === '0000-00-00') return '<span class="ph-badge badge-muted">—</span>';
    $ts   = strtotime($date);
    $diff = (int)floor(($ts - time()) / 86400);
    if ($diff < 0)  return '<span class="ph-badge badge-danger"><i class="fas fa-skull-crossbones me-1"></i>Expired ' . abs($diff) . 'd ago</span>';
    if ($diff === 0) return '<span class="ph-badge badge-danger">Expires Today!</span>';
    if ($diff <= 15) return '<span class="ph-badge badge-danger">' . $diff . ' days</span>';
    if ($diff <= 60) return '<span class="ph-badge badge-warning">' . $diff . ' days</span>';
    return '<span class="ph-badge badge-success">' . date('d M Y', $ts) . '</span>';
}

// ── Stats ─────────────────────────────────────────────
$totalProducts = (int)$db->query("SELECT COUNT(*) FROM ph_product")->fetchColumn();
$lowStockCount = getLowStockCount();
$expiryCount   = getExpiryAlertCount();
$outOfStock    = (int)$db->query("SELECT COUNT(*) FROM ph_product WHERE quantity = 0")->fetchColumn();

// Fetch Suppliers for Quick Indent
$suppliers = $db->query("SELECT * FROM ph_suppliers WHERE status='active' ORDER BY company_name")->fetchAll();

// ── Low Stock Items ────────────────────────────────────
$lowStockItems = $db->prepare("
    SELECT product_id, product_name, content, strength, form, therapeutic, quantity, batch_number, expiry_date
    FROM ph_product
    WHERE quantity <= ?
    ORDER BY quantity ASC
    LIMIT 200
");
$lowStockItems->execute([$threshold]);
$lowStockItems = $lowStockItems->fetchAll();

// ── Expiry Alerts ──────────────────────────────────────
$expiryItems = $db->prepare("
    SELECT product_id, product_name, content, strength, form, quantity, batch_number, expiry_date,
           DATEDIFF(expiry_date, CURDATE()) AS days_left
    FROM ph_product
    WHERE expiry_date IS NOT NULL
      AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
    ORDER BY expiry_date ASC
    LIMIT 200
");
$expiryItems->execute([$expiryDays]);
$expiryItems = $expiryItems->fetchAll();

// ── Expired Items ──────────────────────────────────────
$expiredItems = $db->query("
    SELECT product_id, product_name, quantity, batch_number, expiry_date,
           DATEDIFF(CURDATE(), expiry_date) AS days_past
    FROM ph_product
    WHERE expiry_date < CURDATE() AND quantity > 0
    ORDER BY expiry_date ASC
    LIMIT 200
")->fetchAll();

include 'includes/ph_head.php';
?>
<div class="ph-wrap">
<?php include 'includes/pharmacy_sidebar.php'; ?>
<div id="ph-content">
<?php include 'includes/pharmacy_navbar.php'; ?>
<div class="ph-page-body">

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="ph-page-title">Inventory Alerts</h1>
    <p class="ph-page-subtitle">Low stock, expiry warnings, and expired medicine tracking</p>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <button class="ph-btn ph-btn-outline" onclick="exportCSV('low_stock')"><i class="fas fa-file-csv"></i> Export CSV</button>
    <button class="ph-btn ph-btn-outline" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
    <button class="ph-btn ph-btn-warning text-dark" onclick="bulkIndent()"><i class="fas fa-clipboard-list"></i> Bulk Indent Low Stock</button>
    <a href="indent_request.php" class="ph-btn ph-btn-primary"><i class="fas fa-plus"></i> New Indent</a>
  </div>
</div>

<!-- Search Bar -->
<div class="ph-searchbar mb-3">
  <div class="ph-search-input-wrap"><i class="fas fa-search"></i>
    <input type="text" id="alertSearch" placeholder="Search product name, batch, category..." oninput="filterTables()">
  </div>
  <select class="ph-select" id="formFilter" style="width:140px;padding:.55rem;" onchange="filterTables()">
    <option value="">All Forms</option>
    <?php
    $forms = $db->query("SELECT DISTINCT form FROM ph_product WHERE form IS NOT NULL AND form != '' ORDER BY form")->fetchAll(PDO::FETCH_COLUMN);
    foreach($forms as $f) echo '<option value="'.htmlspecialchars($f).'">'.htmlspecialchars($f).'</option>';
    ?>
  </select>
  <button class="ph-btn ph-btn-outline" onclick="document.getElementById('alertSearch').value='';document.getElementById('formFilter').value='';filterTables();"><i class="fas fa-times"></i> Clear</button>
</div>

<!-- Alert KPIs -->
<div class="ph-stat-grid mb-4" style="grid-template-columns: repeat(5, 1fr); gap: 1rem;">
  <div class="ph-stat d-flex align-items-center" style="border-left:4px solid var(--ph-primary); padding: 1rem 1.25rem; gap: 1rem;">
    <div class="ph-stat-icon m-0" style="background:var(--ph-primary-light);color:var(--ph-primary);width:42px;height:42px;flex-shrink:0;"><i class="fas fa-pills"></i></div>
    <div class="flex-grow-1">
      <div class="ph-stat-val" style="font-size:1.5rem;margin-bottom:0;"><?= number_format($totalProducts) ?></div>
      <div class="ph-stat-lbl" style="font-size:0.7rem;margin-top:0;">Total Products</div>
    </div>
  </div>
  <div class="ph-stat d-flex align-items-center" style="border-left:4px solid var(--ph-danger); padding: 1rem 1.25rem; gap: 1rem;">
    <div class="ph-stat-icon m-0" style="background:#fee2e2;color:var(--ph-danger);width:42px;height:42px;flex-shrink:0;"><i class="fas fa-times-circle"></i></div>
    <div class="flex-grow-1">
      <div class="ph-stat-val text-danger" style="font-size:1.5rem;margin-bottom:0;"><?= $outOfStock ?></div>
      <div class="ph-stat-lbl" style="font-size:0.7rem;margin-top:0;">Out of Stock</div>
    </div>
  </div>
  <div class="ph-stat d-flex align-items-center" style="border-left:4px solid var(--ph-warning); padding: 1rem 1.25rem; gap: 1rem;">
    <div class="ph-stat-icon m-0" style="background:#fef9c3;color:var(--ph-warning);width:42px;height:42px;flex-shrink:0;"><i class="fas fa-exclamation-triangle"></i></div>
    <div class="flex-grow-1">
      <div class="ph-stat-val text-warning" style="font-size:1.5rem;margin-bottom:0;"><?= $lowStockCount ?></div>
      <div class="ph-stat-lbl" style="font-size:0.7rem;margin-top:0;">Low Stock (≤<?= $threshold ?>)</div>
    </div>
  </div>
  <div class="ph-stat d-flex align-items-center" style="border-left:4px solid var(--ph-info); padding: 1rem 1.25rem; gap: 1rem;">
    <div class="ph-stat-icon m-0" style="background:#dbeafe;color:var(--ph-info);width:42px;height:42px;flex-shrink:0;"><i class="fas fa-clock"></i></div>
    <div class="flex-grow-1">
      <div class="ph-stat-val" style="color:var(--ph-info);font-size:1.5rem;margin-bottom:0;"><?= $expiryCount ?></div>
      <div class="ph-stat-lbl" style="font-size:0.7rem;margin-top:0;">Expiring (<?= $expiryDays ?>d)</div>
    </div>
  </div>
  <div class="ph-stat d-flex align-items-center" style="border-left:4px solid #7C3AED; padding: 1rem 1.25rem; gap: 1rem;">
    <div class="ph-stat-icon m-0" style="background:#ede9fe;color:#7C3AED;width:42px;height:42px;flex-shrink:0;"><i class="fas fa-calendar-times"></i></div>
    <div class="flex-grow-1">
      <div class="ph-stat-val" style="color:#7C3AED;font-size:1.5rem;margin-bottom:0;"><?= count($expiredItems) ?></div>
      <div class="ph-stat-lbl" style="font-size:0.7rem;margin-top:0;">Expired</div>
    </div>
  </div>
</div>

<!-- Section Tabs -->
<ul class="nav nav-pills mb-3 gap-2" id="alertTabs">
  <?php
  $tabs = [
      ['low_stock', 'fa-exclamation-triangle', 'Low Stock', count($lowStockItems), 'warning'],
      ['expiry',    'fa-clock',                'Expiring Soon', count($expiryItems), 'info'],
      ['expired',   'fa-ban',                  'Expired', count($expiredItems), 'danger'],
  ];
  foreach ($tabs as [$key, $icon, $label, $count, $color]):
  ?>
    <li class="nav-item">
      <button class="ph-btn <?= $key === 'low_stock' ? 'ph-btn-primary' : 'ph-btn-outline' ?> alert-tab" data-section="<?= $key ?>">
        <i class="fas <?= $icon ?>"></i> <?= $label ?>
        <?php if ($count > 0): ?>
          <span class="ph-badge badge-<?= $color === 'warning' ? 'warning' : ($color === 'danger' ? 'danger' : 'info') ?> ms-1"><?= $count ?></span>
        <?php endif; ?>
      </button>
    </li>
  <?php endforeach; ?>
</ul>

<!-- ─────────── LOW STOCK ─────────── -->
<div id="section-low_stock">
  <div class="ph-card">
    <div class="ph-card-header">
      <span><i class="fas fa-exclamation-triangle text-warning me-2"></i>Low Stock Items (≤ <?= $threshold ?> units)</span>
      <span class="ph-badge badge-warning"><?= $lowStockCount > 200 ? 'Showing Top 200 of ' . $lowStockCount : count($lowStockItems) ?> items</span>
    </div>
    <div class="ph-table-wrap">
      <table class="ph-table">
        <thead>
          <tr>
            <th>Product Name</th>
            <th>Form</th>
            <th>Category</th>
            <th>Batch No</th>
            <th>Current Qty</th>
            <th>Expiry Date</th>
            <th class="text-end">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($lowStockItems)): ?>
            <tr><td colspan="7" class="text-center py-4 text-muted"><i class="fas fa-check-circle text-success me-2"></i>All products have sufficient stock.</td></tr>
          <?php else: foreach ($lowStockItems as $p):
            $qty = (int)$p['quantity'];
            $qtyClass  = $qty === 0 ? 'danger' : 'warning';
            $qtyLabel  = $qty === 0 ? 'Out of Stock' : $qty . ' units';
          ?>
            <tr>
              <td>
                <div class="fw-bold"><?= htmlspecialchars($p['product_name']) ?></div>
                <?php if ($p['strength']): ?><div class="fs-xs text-muted"><?= htmlspecialchars($p['strength']) ?></div><?php endif; ?>
              </td>
              <td><?= $p['form'] ? '<span class="ph-badge badge-primary">' . htmlspecialchars($p['form']) . '</span>' : '—' ?></td>
              <td class="text-muted fs-xs"><?= htmlspecialchars($p['therapeutic'] ?? '—') ?></td>
              <td class="text-muted"><?= htmlspecialchars($p['batch_number'] ?? '—') ?></td>
              <td><span class="ph-badge badge-<?= $qtyClass ?> fw-bold"><?= $qtyLabel ?></span></td>
              <td><?= phpExpiryBadge($p['expiry_date']) ?></td>
              <td class="text-end d-flex gap-1 justify-content-end">
                <button class="ph-btn ph-btn-sm ph-btn-warning text-dark" onclick="quickIndent('<?= htmlspecialchars($p['product_id']) ?>','<?= htmlspecialchars(addslashes($p['product_name'])) ?>',<?= (int)$p['quantity'] ?>)" title="Quick Indent"><i class="fas fa-clipboard-plus"></i> Indent</button>
                <button class="ph-btn ph-btn-sm ph-btn-outline" onclick="viewProduct('<?= htmlspecialchars($p['product_id']) ?>')" title="View Product"><i class="fas fa-eye"></i></button>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ─────────── EXPIRY ALERTS ─────────── -->
<div id="section-expiry" style="display:none;">
  <div class="ph-card">
    <div class="ph-card-header">
      <span><i class="fas fa-clock text-info me-2"></i>Expiring Within <?= $expiryDays ?> Days</span>
      <span class="ph-badge badge-info"><?= $expiryCount > 200 ? 'Showing Top 200 of ' . $expiryCount : count($expiryItems) ?> items</span>
    </div>
    <div class="ph-table-wrap">
      <table class="ph-table">
        <thead>
          <tr><th>Product Name</th><th>Form</th><th>Batch No</th><th>Qty</th><th>Expiry Date</th><th>Days Remaining</th></tr>
        </thead>
        <tbody>
          <?php if (empty($expiryItems)): ?>
            <tr><td colspan="6" class="text-center py-4 text-muted"><i class="fas fa-check-circle text-success me-2"></i>No products expiring within <?= $expiryDays ?> days.</td></tr>
          <?php else: foreach ($expiryItems as $p):
            $days = (int)$p['days_left'];
            $cls  = $days <= 15 ? 'danger' : ($days <= 30 ? 'warning' : 'info');
          ?>
            <tr>
              <td>
                <div class="fw-bold"><?= htmlspecialchars($p['product_name']) ?></div>
                <?php if ($p['strength']): ?><div class="fs-xs text-muted"><?= htmlspecialchars($p['strength']) ?></div><?php endif; ?>
              </td>
              <td><?= $p['form'] ? '<span class="ph-badge badge-primary">' . htmlspecialchars($p['form']) . '</span>' : '—' ?></td>
              <td class="text-muted"><?= htmlspecialchars($p['batch_number'] ?? '—') ?></td>
              <td class="fw-bold"><?= number_format($p['quantity']) ?></td>
              <td><?= date('d M Y', strtotime($p['expiry_date'])) ?></td>
              <td>
                <span class="ph-badge badge-<?= $cls ?>">
                  <?= $days === 0 ? 'Expires Today!' : ($days < 0 ? 'Expired' : $days . ' days') ?>
                </span>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ─────────── EXPIRED ─────────── -->
<div id="section-expired" style="display:none;">
  <div class="ph-card">
    <div class="ph-card-header" style="background:#fff5f5;">
      <span><i class="fas fa-ban text-danger me-2"></i>Expired Products (Still in Stock)</span>
      <span class="ph-badge badge-danger"><?= count($expiredItems) ?> items</span>
    </div>
    <?php if (!empty($expiredItems)): ?>
    <div style="background:#fff5f5;padding:.75rem 1.25rem;border-bottom:1px solid #fecaca;font-size:.82rem;color:#dc2626;">
      <i class="fas fa-exclamation-circle me-2"></i>
      <strong>Action Required:</strong> These products have expired and should not be dispensed. Raise a damage return and quarantine them immediately.
    </div>
    <?php endif; ?>
    <div class="ph-table-wrap">
      <table class="ph-table">
        <thead>
          <tr><th>Product Name</th><th>Batch No</th><th>Qty on Hand</th><th>Expiry Date</th><th>Days Past Expiry</th><th class="text-end">Action</th></tr>
        </thead>
        <tbody>
          <?php if (empty($expiredItems)): ?>
            <tr><td colspan="6" class="text-center py-4 text-muted"><i class="fas fa-check-circle text-success me-2"></i>No expired products with remaining stock.</td></tr>
          <?php else: foreach ($expiredItems as $p): ?>
            <tr style="background:#fff5f5;">
              <td><div class="fw-bold text-danger"><?= htmlspecialchars($p['product_name']) ?></div></td>
              <td><?= htmlspecialchars($p['batch_number'] ?? '—') ?></td>
              <td><span class="ph-badge badge-danger"><?= number_format($p['quantity']) ?> units</span></td>
              <td class="text-danger fw-bold"><?= date('d M Y', strtotime($p['expiry_date'])) ?></td>
              <td><span class="ph-badge badge-dark"><?= $p['days_past'] ?> days ago</span></td>
              <td class="text-end">
                <button class="ph-btn ph-btn-sm ph-btn-danger" onclick="raiseReturn('<?= htmlspecialchars($p['product_id']) ?>','<?= htmlspecialchars(addslashes($p['product_name'])) ?>','<?= htmlspecialchars($p['batch_number'] ?? '') ?>',<?= (int)$p['quantity'] ?>)"><i class="fas fa-undo-alt"></i> Raise Return</button>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

</div><!-- ph-page-body -->
</div><!-- ph-content -->
</div><!-- ph-wrap -->

<!-- Quick Indent Modal -->
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
<div class="modal fade compact-modal" id="quickIndentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 900px;">
    <div class="modal-content" style="background: #F3EFE6; border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
      <div class="modal-header" style="border-bottom: 1px solid rgba(31,107,74,0.15);">
        <h5 class="modal-title fw-bold" style="color: #1F6B4A; letter-spacing: -0.5px;"><i class="fas fa-clipboard-plus me-2"></i>Quick Indent Request</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(34%) sepia(16%) saturate(1637%) hue-rotate(107deg) brightness(97%) contrast(89%); opacity: 0.8;"></button>
      </div>
      <form id="quickIndentForm" onsubmit="submitQuickIndent(event)">
        <div class="modal-body">
          <input type="hidden" id="qi_product_id">
          
          <div>
            <h6><i class="fas fa-box me-1"></i>Product Information & Request Details</h6>
            <div class="grid-4-cols">
                <div class="grid-item" style="grid-column: span 2;">
                  <label class="ph-label">Product Name</label>
                  <input class="ph-input" id="qi_product_name" readonly style="background:#E3F2EC; font-weight:800; cursor:not-allowed;">
                </div>
                <div class="grid-item">
                  <label class="ph-label">Current Stock</label>
                  <input class="ph-input text-danger fw-bold" id="qi_current_qty" readonly style="background:#FFEBEB; cursor:not-allowed;">
                </div>
                <div class="grid-item">
                  <label class="ph-label">Request Quantity *</label>
                  <input type="number" class="ph-input" id="qi_qty" name="qty" min="1" value="100" required>
                </div>

                <div class="grid-item">
                  <label class="ph-label">Priority</label>
                  <select class="ph-select" id="qi_priority" name="priority">
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                    <option value="low">Low</option>
                  </select>
                </div>
                <div class="grid-item">
                  <label class="ph-label">Requested By</label>
                  <input class="ph-input" id="qi_by" value="Pharmacy Store" name="requested_by">
                </div>
                <div class="grid-item" style="grid-column: span 2;">
                  <label class="ph-label">Procurement Remarks</label>
                  <textarea class="ph-textarea" id="qi_remarks" name="remarks" rows="1" placeholder="Reason for this indent (e.g. Stock below safety level)"></textarea>
                </div>
            </div>
          </div>

          <div>
            <h6><i class="fas fa-truck me-1"></i>Distribution Partner Profile</h6>
            <div class="grid-4-cols">
                <div class="grid-item" style="grid-column: span 2;">
                  <label class="ph-label">Assign Supplier *</label>
                  <select class="ph-select" id="qi_supplier_select" name="supplier_id" required onchange="updateSupplierDetails(this)">
                    <option value="">-- Select Registered Supplier --</option>
                    <?php foreach($suppliers as $s): ?>
                      <option value="<?= htmlspecialchars($s['supplier_id']) ?>" 
                              data-company="<?= htmlspecialchars($s['company_name']) ?>"
                              data-email="<?= htmlspecialchars($s['email']) ?>"
                              data-phone="<?= htmlspecialchars($s['phone']) ?>"
                              data-gst="<?= htmlspecialchars($s['gst_no']) ?>"
                              data-pan="<?= htmlspecialchars($s['company_pan']) ?>"
                              data-city="<?= htmlspecialchars($s['city']) ?>"
                              data-address="<?= htmlspecialchars($s['address']) ?>"
                              data-name="<?= htmlspecialchars($s['supplier_name']) ?>">
                        <?= htmlspecialchars($s['company_name']) ?> (<?= htmlspecialchars($s['supplier_id']) ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="grid-item" style="grid-column: span 2;">
                  <label class="ph-label">Company Name</label>
                  <input type="text" class="ph-input" id="qi_company_name" name="company_name" readonly style="background:#E3F2EC; font-weight:800; cursor:not-allowed;">
                </div>
                
                <div class="grid-item" style="grid-column: span 2;">
                  <label class="ph-label">Supplier Name (Contact Person)</label>
                  <input type="text" class="ph-input" id="qi_supplier_name" readonly style="background:#E3F2EC; font-weight:800; cursor:not-allowed;">
                </div>
                <div class="grid-item" style="grid-column: span 2;">
                  <label class="ph-label">Email Address</label>
                  <input type="text" class="ph-input" id="qi_supplier_email" readonly style="background:#E3F2EC; font-weight:800; cursor:not-allowed;">
                </div>
            </div>
          </div>
        </div>
        <div class="modal-footer" style="background: #F3EFE6; border-top: 1px solid rgba(31,107,74,0.15); border-radius: 0 0 12px 12px;">
          <button type="button" class="btn btn-sm" data-bs-dismiss="modal" style="background: transparent; color: #1F6B4A; border: 1.5px solid rgba(31,107,74,0.2); border-radius: 8px; font-weight: 700;">Cancel</button>
          <button type="submit" class="btn btn-sm" style="background: #1F6B4A; color: #FFFFFF; border: none; border-radius: 8px; font-weight: 700; box-shadow: 0 4px 10px rgba(31,107,74,0.2);">
            <i class="fas fa-paper-plane me-1"></i> Submit Request
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Return Modal -->
<div class="modal fade compact-modal" id="returnModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
    <div class="modal-content" style="background: #F3EFE6; border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
      <div class="modal-header" style="border-bottom: 1px solid rgba(31,107,74,0.15);">
        <h5 class="modal-title" style="color: #1F6B4A; font-weight: 900; letter-spacing: -0.5px;"><i class="fas fa-undo-alt me-2 text-danger"></i>Raise Damage / Expiry Return</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(34%) sepia(16%) saturate(1637%) hue-rotate(107deg) brightness(97%) contrast(89%); opacity: 0.8;"></button>
      </div>
      <form id="returnForm" onsubmit="submitReturn(event)">
        <div class="modal-body">
          <input type="hidden" id="ret_product_id">
          
          <div class="grid-4-cols">
            <div class="grid-item" style="grid-column: span 2;">
              <label class="ph-label">Product</label>
              <input class="ph-input" id="ret_product_name" readonly style="background:#E3F2EC; font-weight:800; cursor:not-allowed;">
            </div>
            <div class="grid-item">
              <label class="ph-label">Batch No</label>
              <input class="ph-input" id="ret_batch" readonly style="background:#E3F2EC; font-weight:800; cursor:not-allowed;">
            </div>
            <div class="grid-item">
              <label class="ph-label">Qty to Return *</label>
              <input type="number" class="ph-input" id="ret_qty" name="qty" min="1" required>
            </div>
            <div class="grid-item" style="grid-column: span 4;">
              <label class="ph-label">Reason *</label>
              <textarea class="ph-textarea" name="reason" id="ret_reason" rows="1" required placeholder="Expired / Damaged / Quality issue..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer" style="background: #F3EFE6; border-top: 1px solid rgba(31,107,74,0.15); border-radius: 0 0 12px 12px;">
          <button type="button" class="btn btn-sm" data-bs-dismiss="modal" style="background: transparent; color: #1F6B4A; border: 1.5px solid rgba(31,107,74,0.2); border-radius: 8px; font-weight: 700;">Cancel</button>
          <button type="submit" class="btn btn-sm" style="background: #dc3545; color: #FFFFFF; border: none; border-radius: 8px; font-weight: 700; box-shadow: 0 4px 10px rgba(220,53,69,0.2);">
            <i class="fas fa-undo-alt me-1"></i> Submit Return
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'includes/ph_foot.php'; ?>
<script>
// ── Tab switching ──────────────────────────────────────
document.querySelectorAll('.alert-tab').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('[id^="section-"]').forEach(s => s.style.display = 'none');
        document.querySelectorAll('.alert-tab').forEach(b => { b.classList.remove('ph-btn-primary'); b.classList.add('ph-btn-outline'); });
        document.getElementById('section-' + this.dataset.section).style.display = '';
        this.classList.add('ph-btn-primary'); this.classList.remove('ph-btn-outline');
        currentSection = this.dataset.section;
    });
});

// ── Search & Filter ────────────────────────────────────
let currentSection = 'low_stock';
function filterTables() {
    const q    = document.getElementById('alertSearch').value.toLowerCase();
    const form = document.getElementById('formFilter').value.toLowerCase();
    document.querySelectorAll('[id^="section-"] tbody tr').forEach(tr => {
        const text = tr.textContent.toLowerCase();
        const show = (!q || text.includes(q)) && (!form || text.includes(form));
        tr.style.display = show ? '' : 'none';
    });
}

// ── Quick Indent ───────────────────────────────────────
const qiModal = new bootstrap.Modal(document.getElementById('quickIndentModal'));
function quickIndent(pid, pname, currentQty) {
    document.getElementById('qi_product_id').value   = pid;
    document.getElementById('qi_product_name').value = pname;
    document.getElementById('qi_current_qty').value  = currentQty + ' units';
    document.getElementById('qi_qty').value          = Math.max(50, (100 - currentQty));
    qiModal.show();
}

function updateSupplierDetails(sel) {
    const opt = sel.options[sel.selectedIndex];
    const fields = {
        'qi_company_name': 'company',
        'qi_supplier_email': 'email',
        'qi_supplier_name': 'name'
    };
    
    if (!opt.value) {
        Object.keys(fields).forEach(id => document.getElementById(id).value = '');
        return;
    }
    
    Object.entries(fields).forEach(([id, dataKey]) => {
        const el = document.getElementById(id);
        if (el) el.value = opt.dataset[dataKey] || '';
    });
}

async function submitQuickIndent(e) {
    e.preventDefault();
    PH.loading('Submitting indent...');
    const data = {
        product_id:   document.getElementById('qi_product_id').value,
        item_name:    document.getElementById('qi_product_name').value,
        qty:          document.getElementById('qi_qty').value,
        priority:     document.getElementById('qi_priority').value,
        requested_by: document.getElementById('qi_by').value,
        remarks:      document.getElementById('qi_remarks').value,
        supplier_id:  document.getElementById('qi_supplier_select').value,
        company_name: document.getElementById('qi_company_name').value,
        email:        document.getElementById('qi_supplier_email').value,
        department:   'Pharmacy Store',
        status:       'pending'
    };
    try {
        const res = await fetch(API_BASE + 'pharmacy/indents', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        }).then(r => r.json());
        
        if (res.success) { 
            PH.success('Indent ' + res.data.indent_no + ' created successfully'); 
            qiModal.hide(); 
        } else {
            PH.error(res.message);
        }
    } catch (err) {
        PH.error('Failed to submit indent request');
        console.error(err);
    }
}

async function bulkIndent() {
    const rows = document.querySelectorAll('#section-low_stock tbody tr:not([style*="display: none"])');
    if (!rows.length) { PH.warning('No low stock items to indent.'); return; }
    PH.confirm('Auto-generate all indents?', 'This will create indents for all low-stock products.', async () => {
        PH.loading('Generating...');
        try {
            const res = await fetch(API_BASE + 'pharmacy/indents/auto-generate', { method: 'POST' }).then(r => r.json());
            if (res.success) {
                PH.success(res.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                PH.error(res.message);
            }
        } catch (e) { PH.error('Bulk indent failed'); }
    }, 'Yes, Create All');
}

// ── Return / Damage ────────────────────────────────────
const retModal = new bootstrap.Modal(document.getElementById('returnModal'));
function raiseReturn(pid, pname, batch, qty) {
    document.getElementById('ret_product_id').value   = pid;
    document.getElementById('ret_product_name').value = pname;
    document.getElementById('ret_batch').value        = batch;
    document.getElementById('ret_qty').value          = qty;
    document.getElementById('ret_reason').value       = 'Expired — quarantine required';
    retModal.show();
}

async function submitReturn(e) {
    e.preventDefault();
    PH.loading('Creating Return...');
    const data = {
        return_type:  'damage',
        product_id:   document.getElementById('ret_product_id').value,
        product_name: document.getElementById('ret_product_name').value,
        batch_no:     document.getElementById('ret_batch').value,
        qty:          document.getElementById('ret_qty').value,
        reason:       document.getElementById('ret_reason').value,
        rate:         0,
        total_amount: 0,
        status:       'processed'
    };
    const res = await fetch(API_BASE + 'pharmacy/returns', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    }).then(r => r.json());
    if (res.success) { PH.success(res.message); retModal.hide(); setTimeout(() => location.reload(), 1500); }
    else PH.error(res.message);
}

// ── View Product ───────────────────────────────────────
function viewProduct(pid) {
    window.location.href = 'products.php?highlight=' + encodeURIComponent(pid);
}

// ── CSV Export ─────────────────────────────────────────
function exportCSV(type) {
    window.location.href = API_BASE + 'pharmacy/export/csv?type=' + type;
}
</script>
