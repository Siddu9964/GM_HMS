<?php
/**
 * ot_view/includes/ot_sidebar.php
 * Reusable sidebar for the OT module.
 */
$cur = basename($_SERVER['PHP_SELF']);
function otNavLink($href, $icon, $label, $cur) {
    $active = ($cur === $href) ? 'active' : '';
    return "<li><a href='$href' class='ot-nav-link $active'><i class='$icon'></i><span>$label</span></a></li>";
}
?>
<div id="ot-overlay"></div>
<nav class="ot-sidebar" id="ot-sidebar">
  <div class="ot-sidebar-header">
    <a href="dashboard.php" class="ot-logo">
      <div class="ot-logo-icon"><i class="fas fa-procedures"></i></div>
      <span class="ot-logo-text">GM OT</span>
    </a>
    <i class="fas fa-times ot-sidebar-close" onclick="document.getElementById('ot-sidebar').classList.remove('open'); document.getElementById('ot-overlay').style.display='none';"></i>
  </div>

  <div class="ot-sidebar-body">

    <div class="ot-nav-section">
      <span class="ot-nav-label">Overview</span>
      <ul class="ot-nav-list">
        <?= otNavLink('dashboard.php', 'fas fa-th-large', 'Dashboard', $cur) ?>
      </ul>
    </div>

    <div class="ot-nav-section">
      <span class="ot-nav-label">Operations</span>
      <ul class="ot-nav-list">
        <?= otNavLink('surgery_schedule.php', 'fas fa-calendar-alt', 'Surgery Schedule', $cur) ?>
        <?= otNavLink('pharmacy_order.php', 'fas fa-pills', 'Pharmacy', $cur) ?>
      </ul>
    </div>

    <div class="ot-nav-section">
      <span class="ot-nav-label">System</span>
      <ul class="ot-nav-list">
        <li><a href="../logout.php" class="ot-nav-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
        <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'Admin'])): ?>
        <li style="margin-top:10px;">
          <a href="/GM_HMS/view/admin_dashboard.php" class="ot-nav-link" style="background:rgba(239,68,68,0.1);color:#ef4444;border-radius:8px;">
            <i class="fas fa-arrow-left" style="color:#ef4444;"></i><span>Exit to Admin</span>
          </a>
        </li>
        <?php endif; ?>
      </ul>
    </div>

  </div>
</nav>
