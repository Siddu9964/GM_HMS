<?php
/**
 * Pharmacy ERP - Reusable Sidebar
 */
$cur = basename($_SERVER['PHP_SELF']);
function navLink($href, $icon, $label, $cur, $badgeId = '') {
    $active = ($cur === $href) ? 'active' : '';
    $badge  = $badgeId ? "<span class='badge-count' id='$badgeId' style='display:none'>0</span>" : '';
    return "<li><a href='$href' class='ph-nav-link $active'><i class='$icon'></i><span>$label</span>$badge</a></li>";
}

$userDesignation = $_SESSION['designation'] ?? '';
$isAssistant = ($userDesignation === 'Assistant Pharmacist');
?>
<div id="ph-overlay"></div>
<nav class="ph-sidebar" id="ph-sidebar">
  <div class="ph-sidebar-header">
    <a href="dashboard.php" class="ph-logo">
      <div class="ph-logo-icon"><i class="fas fa-prescription-bottle-alt"></i></div>
      <span class="ph-logo-text">GM Pharmacy</span>
    </a>
    <i class="fas fa-times ph-sidebar-close" onclick="phToggleSidebar()"></i>
  </div>

  <div class="ph-sidebar-body">

    <div class="ph-nav-section">
      <span class="ph-nav-label">Overview</span>
      <ul class="ph-nav-list">
        <?= navLink('dashboard.php',     'fas fa-th-large',           'Dashboard',        $cur) ?>
        <?= navLink('prescriptions.php', 'fas fa-file-prescription',  'Prescriptions',    $cur) ?>
      </ul>
    </div>

    <div class="ph-nav-section">
      <span class="ph-nav-label">Inventory</span>
      <ul class="ph-nav-list">
        <?= navLink('products.php',        'fas fa-pills',           'Products',         $cur) ?>
        <?php if (!$isAssistant): ?>
        <?= navLink('inventory_alerts.php','fas fa-bell',            'Stock Alerts',     $cur, 'expiry-badge') ?>
        <?= navLink('product_import.php',  'fas fa-file-import',     'Import / Export',  $cur) ?>
        <?php endif; ?>
      </ul>
    </div>

    <?php if (!$isAssistant): ?>
    <div class="ph-nav-section">
      <span class="ph-nav-label">Procurement</span>
      <ul class="ph-nav-list">
        <?= navLink('suppliers.php',      'fas fa-truck',            'Suppliers',        $cur) ?>
        <?= navLink('indent_request.php', 'fas fa-clipboard-list',   'Indent Requests',  $cur, 'indent-badge') ?>
        <?= navLink('quotation.php',      'fas fa-file-alt',         'Quotations',       $cur) ?>
        <?= navLink('purchase_order.php', 'fas fa-shopping-cart',    'Purchase Orders',  $cur) ?>
        <?= navLink('stock_receive.php',  'fas fa-boxes',            'Stock Receive',    $cur) ?>
      </ul>
    </div>
    <?php endif; ?>

    <div class="ph-nav-section">
      <span class="ph-nav-label">Sales</span>
      <ul class="ph-nav-list">
        <?= navLink('billing_pos.php','fas fa-cash-register',       'Billing / POS',    $cur) ?>
        <?php if (!$isAssistant): ?>
        <?= navLink('sales.php',      'fas fa-receipt',             'Sales History',    $cur) ?>
        <?= navLink('returns.php',    'fas fa-undo-alt',            'Returns',          $cur) ?>
        <?php endif; ?>
        <?= navLink('opd_ipd_returns.php', 'fas fa-hand-holding-medical', 'Patient Returns', $cur) ?>
      </ul>
    </div>

    <?php if (!$isAssistant): ?>
    <div class="ph-nav-section">
      <span class="ph-nav-label">Analytics</span>
      <ul class="ph-nav-list">
        <?= navLink('reports.php', 'fas fa-chart-pie', 'Reports', $cur) ?>
      </ul>
    </div>
    <?php endif; ?>

    <div class="ph-nav-section">
      <span class="ph-nav-label">System</span>
      <ul class="ph-nav-list">
        <?php if (!$isAssistant): ?>
        <?= navLink('settings.php', 'fas fa-cog', 'Settings', $cur) ?>
        <?php endif; ?>
        <li><a href="../logout.php" class="ph-nav-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
      </ul>
    </div>

  </div>
</nav>
