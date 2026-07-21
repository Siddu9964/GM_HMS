<?php
// Extend session to 8 hours for full-shift use
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.cookie_lifetime', 28800);
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
require_once 'includes/db.php';
$pageTitle = 'Settings & Profile';
$db = getDB();

// Load all settings
$settingsRows = $db->query("SELECT setting_key, setting_value FROM ph_settings")->fetchAll();
$settings = [];
foreach ($settingsRows as $row) $settings[$row['setting_key']] = $row['setting_value'];

function sval(array $settings, string $key, string $default = ''): string {
    return htmlspecialchars($settings[$key] ?? $default);
}

include 'includes/ph_head.php';
?>
<style>
.settings-menu .list-group-item {
    padding: 14px 20px;
    font-weight: 600;
    border: none;
    color: var(--ph-muted);
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
}
.settings-menu .list-group-item:hover {
    background-color: #f1f5f9;
    color: var(--ph-primary);
}
.settings-menu .list-group-item.active {
    background-color: #eff6ff;
    color: var(--ph-primary);
    border-left-color: var(--ph-primary);
    z-index: 1;
}
.ph-form-control {
    padding: 0.6rem 1rem;
    border: 1px solid var(--ph-border);
    border-radius: 8px;
    width: 100%;
    transition: 0.2s;
}
.ph-form-control:focus {
    border-color: var(--ph-primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    outline: none;
}
</style>
<div class="ph-wrap">
<?php include 'includes/pharmacy_sidebar.php'; ?>
<div id="ph-content">
<?php include 'includes/pharmacy_navbar.php'; ?>
<div class="ph-page-body">

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="ph-page-title">Settings & Profile</h1>
    <p class="ph-page-subtitle">Manage your account and configure pharmacy preferences</p>
  </div>
</div>

<div class="row g-4">
  <!-- Sidebar Menu -->
  <div class="col-md-3 col-lg-2">
    <div class="ph-card" style="padding:0; overflow:hidden;">
      <div class="list-group list-group-flush settings-menu" id="settingsTabs">
        <button class="list-group-item list-group-item-action active" data-tab="company"><i class="fas fa-building me-2" style="width:20px;text-align:center;"></i> Company</button>
        <button class="list-group-item list-group-item-action" data-tab="billing"><i class="fas fa-receipt me-2" style="width:20px;text-align:center;"></i> Billing</button>
        <button class="list-group-item list-group-item-action" data-tab="alerts"><i class="fas fa-bell me-2" style="width:20px;text-align:center;"></i> Alerts</button>
        <button class="list-group-item list-group-item-action" data-tab="system"><i class="fas fa-cogs me-2" style="width:20px;text-align:center;"></i> System</button>
      </div>
    </div>
  </div>

  <!-- Content Area -->
  <div class="col-md-9 col-lg-10">
    
    <!-- Company Settings -->
    <div id="tab-company" class="tab-pane">
      <form onsubmit="saveSettings(event, 'company')">
        <div class="ph-card">
          <div class="ph-card-header"><span><i class="fas fa-building me-2"></i>Company Information</span></div>
          <div class="ph-card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="ph-label">Company Name</label>
                <input type="text" class="ph-form-control" name="company_name" value="<?= sval($settings,'company_name','GM Pharmacy') ?>">
              </div>
              <div class="col-md-6">
                <label class="ph-label">GSTIN Number</label>
                <input type="text" class="ph-form-control" name="company_gstin" value="<?= sval($settings,'company_gstin') ?>" style="text-transform:uppercase;">
              </div>
              <div class="col-md-6">
                <label class="ph-label">Phone Number</label>
                <input type="text" class="ph-form-control" name="company_phone" value="<?= sval($settings,'company_phone') ?>">
              </div>
              <div class="col-md-6">
                <label class="ph-label">Email Address</label>
                <input type="email" class="ph-form-control" name="company_email" value="<?= sval($settings,'company_email') ?>">
              </div>
              <div class="col-12">
                <label class="ph-label">Company Address</label>
                <textarea class="ph-form-control" name="company_address" rows="2"><?= sval($settings,'company_address') ?></textarea>
              </div>
              <div class="col-12">
                <label class="ph-label">Invoice Footer Note</label>
                <input type="text" class="ph-form-control" name="footer_note" value="<?= sval($settings,'footer_note','Thank you for your purchase. Get well soon!') ?>">
              </div>
            </div>
          </div>
          <div class="ph-card-header" style="border-top:1px solid var(--ph-border);justify-content:flex-end;background:#f8fafc;">
            <button type="submit" class="ph-btn ph-btn-primary"><i class="fas fa-save me-2"></i> Save Company Info</button>
          </div>
        </div>
      </form>
    </div>

    <!-- Billing Settings -->
    <div id="tab-billing" class="tab-pane" style="display:none;">
      <form onsubmit="saveSettings(event, 'billing')">
        <div class="ph-card">
          <div class="ph-card-header"><span><i class="fas fa-receipt me-2"></i>Billing & Tax Settings</span></div>
          <div class="ph-card-body">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="ph-label">Currency Symbol</label>
                <input type="text" class="ph-form-control" name="currency_symbol" value="<?= sval($settings,'currency_symbol','₹') ?>" maxlength="5">
              </div>
              <div class="col-md-4">
                <label class="ph-label">Default Tax Rate (%)</label>
                <input type="number" class="ph-form-control" name="tax_rate_default" value="<?= sval($settings,'tax_rate_default','12') ?>" step="0.01" min="0">
              </div>
              <div class="col-md-4">
                <label class="ph-label">Invoice Prefix</label>
                <input type="text" class="ph-form-control" name="invoice_prefix" value="<?= sval($settings,'invoice_prefix','INV') ?>" maxlength="6">
              </div>
              <div class="col-md-4">
                <label class="ph-label">Purchase Order Prefix</label>
                <input type="text" class="ph-form-control" name="po_prefix" value="<?= sval($settings,'po_prefix','PO') ?>" maxlength="6">
              </div>
              <div class="col-md-4">
                <label class="ph-label">Indent Prefix</label>
                <input type="text" class="ph-form-control" name="indent_prefix" value="<?= sval($settings,'indent_prefix','IND') ?>" maxlength="6">
              </div>
              <div class="col-md-4">
                <label class="ph-label">GRN Prefix</label>
                <input type="text" class="ph-form-control" name="grn_prefix" value="<?= sval($settings,'grn_prefix','GRN') ?>" maxlength="6">
              </div>
            </div>
          </div>
          <div class="ph-card-header" style="border-top:1px solid var(--ph-border);justify-content:flex-end;background:#f8fafc;">
            <button type="submit" class="ph-btn ph-btn-primary"><i class="fas fa-save me-2"></i> Save Billing Settings</button>
          </div>
        </div>
      </form>
    </div>

    <!-- Alert Settings -->
    <div id="tab-alerts" class="tab-pane" style="display:none;">
      <form onsubmit="saveSettings(event, 'alerts')">
        <div class="ph-card">
          <div class="ph-card-header"><span><i class="fas fa-bell me-2"></i>Alert Thresholds</span></div>
          <div class="ph-card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="ph-label">Low Stock Threshold (units)</label>
                <input type="number" class="ph-form-control" name="low_stock_threshold" value="<?= sval($settings,'low_stock_threshold','20') ?>" min="1">
                <div style="font-size:.75rem;color:var(--ph-muted);margin-top:.3rem;">Products with quantity ≤ this value will trigger low stock alerts.</div>
              </div>
              <div class="col-md-6">
                <label class="ph-label">Expiry Alert Window (days)</label>
                <input type="number" class="ph-form-control" name="expiry_alert_days" value="<?= sval($settings,'expiry_alert_days','60') ?>" min="1">
                <div style="font-size:.75rem;color:var(--ph-muted);margin-top:.3rem;">Products expiring within this many days will trigger expiry alerts.</div>
              </div>
            </div>
          </div>
          <div class="ph-card-header" style="border-top:1px solid var(--ph-border);justify-content:flex-end;background:#f8fafc;">
            <button type="submit" class="ph-btn ph-btn-primary"><i class="fas fa-save me-2"></i> Save Alert Settings</button>
          </div>
        </div>
      </form>
    </div>

    <!-- System Settings -->
    <div id="tab-system" class="tab-pane" style="display:none;">
      <div class="row g-4">
        <!-- Database Info -->
        <div class="col-md-6">
          <div class="ph-card h-100">
            <div class="ph-card-header"><span><i class="fas fa-database me-2"></i>Database Info</span></div>
            <div class="ph-card-body">
              <?php
              try {
                  $tables = $db->query("SHOW TABLES LIKE 'ph_%'")->fetchAll(PDO::FETCH_COLUMN);
                  $totalProd = $db->query("SELECT COUNT(*) FROM ph_product")->fetchColumn();
                  $totalSales = $db->query("SELECT COUNT(*) FROM ph_sales_master")->fetchColumn();
              } catch(Exception $e) { $tables = []; $totalProd = $totalSales = 0; }
              ?>
              <div class="row g-3">
                <div class="col-6"><div class="text-muted" style="font-size:0.8rem;font-weight:600;">Database</div><div style="font-size:1.1rem;font-weight:700;">hmsci</div></div>
                <div class="col-6"><div class="text-muted" style="font-size:0.8rem;font-weight:600;">ERP Tables</div><div style="font-size:1.1rem;font-weight:700;"><?= count($tables) ?></div></div>
                <div class="col-6"><div class="text-muted" style="font-size:0.8rem;font-weight:600;">Total Products</div><div style="font-size:1.1rem;font-weight:700;"><?= $totalProd ?></div></div>
                <div class="col-6"><div class="text-muted" style="font-size:0.8rem;font-weight:600;">Total Invoices</div><div style="font-size:1.1rem;font-weight:700;"><?= $totalSales ?></div></div>
              </div>
            </div>
          </div>
        </div>

        <!-- SQL Setup -->
        <div class="col-md-6">
          <div class="ph-card h-100">
            <div class="ph-card-header"><span><i class="fas fa-terminal me-2"></i>Initial Setup</span></div>
            <div class="ph-card-body">
              <p style="font-size:.85rem;color:var(--ph-muted);">Run the SQL schema to create all required tables if not already created. Open the file in phpMyAdmin and execute it.</p>
              <a href="sql/pharmacy_erp_tables.sql" download class="ph-btn ph-btn-outline mt-2 w-100">
                <i class="fas fa-download"></i> Download SQL Schema
              </a>
              <div class="mt-4">
                <div class="text-muted mb-2" style="font-size:0.8rem;font-weight:600;">ERP Tables Created:</div>
                <div class="d-flex flex-wrap gap-2">
                  <?php foreach($tables as $t): ?>
                  <span class="badge bg-success bg-opacity-10 text-success" style="padding:6px 10px;font-weight:600;font-size:0.75rem;"><?= $t ?></span>
                  <?php endforeach; ?>
                  <?php if(empty($tables)): ?>
                  <span class="badge bg-danger bg-opacity-10 text-danger" style="padding:6px 10px;font-weight:600;font-size:0.75rem;">No ph_ tables found.</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

</div></div></div>
<?php include 'includes/ph_foot.php'; ?>
<script>
// Tab switching
document.querySelectorAll('[data-tab]').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');
        document.querySelectorAll('[data-tab]').forEach(b => b.classList.remove('active'));
        document.getElementById('tab-' + this.dataset.tab).style.display = 'block';
        this.classList.add('active');
        
        // Update URL to reflect active tab without reloading
        const url = new URL(window.location);
        url.searchParams.set('tab', this.dataset.tab);
        window.history.pushState({}, '', url);
    });
});

// Auto-switch to tab from URL param on load
window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab');
    if (activeTab) {
        const tabBtn = document.querySelector(`[data-tab="${activeTab}"]`);
        if (tabBtn) tabBtn.click();
    }
});

async function saveSettings(e, group) {
    e.preventDefault();
    PH.loading('Saving settings...');
    const fd = new FormData(e.target);
    const data = Object.fromEntries(fd.entries());
    data.group = group;

    try {
        const res = await fetch(API_BASE + 'pharmacy/settings', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        }).then(r => r.json());

        if (res.success) PH.success(res.message || 'Settings saved');
        else PH.error(res.message || 'Failed to save');
    } catch (err) { PH.error('Failed to save settings'); }
}
</script>
