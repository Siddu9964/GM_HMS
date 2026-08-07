<?php
/**
 * LIS Sidebar — lab_sidebar.php
 * Full 15-item navigation for the Laboratory Information System.
 */
$currentPage = basename($_SERVER['PHP_SELF']);
function lisSidebarActive($file, $current) {
    return $file === $current ? 'active' : '';
}
?>
<aside class="lis-sidebar" id="lis-sidebar">

  <!-- Brand -->
  <div class="lis-brand">
    <a href="dashboard.php" class="lis-brand-logo">
      <div class="lis-brand-icon"><i class="fas fa-microscope"></i></div>
      <div>
        <div class="lis-brand-name">LIS Portal</div>
        <div class="lis-brand-sub">GM Hospital</div>
      </div>
    </a>
  </div>

  <!-- Branch badge -->
  <div class="lis-branch-badge">
    <i class="fas fa-hospital-alt"></i>
    <span id="sidebar-branch-name">Main Branch</span>
  </div>

  <!-- ── MAIN ── -->
  <div class="lis-nav-section">Main</div>

  <a href="dashboard.php" class="lis-nav-item <?= lisSidebarActive('dashboard.php', $currentPage) ?>">
    <i class="fas fa-chart-line"></i>
    <span>Dashboard</span>
  </a>

  <a class="lis-nav-item <?= in_array($currentPage, ['test_orders.php', 'ipd_test_orders.php']) ? 'active' : '' ?>" data-bs-toggle="collapse" href="#testOrdersSubmenu" role="button" aria-expanded="<?= in_array($currentPage, ['test_orders.php', 'ipd_test_orders.php']) ? 'true' : 'false' ?>" aria-controls="testOrdersSubmenu">
    <i class="fas fa-flask"></i>
    <span>Test Orders</span>
    <i class="fas fa-chevron-down ms-auto" style="font-size: 0.8rem;"></i>
  </a>
  <div class="collapse <?= in_array($currentPage, ['test_orders.php', 'ipd_test_orders.php']) ? 'show' : '' ?>" id="testOrdersSubmenu">
      <div class="ps-4 pe-2 py-1">
          <a href="test_orders.php" class="lis-nav-item <?= lisSidebarActive('test_orders.php', $currentPage) ?>" style="font-size: 0.85rem; padding: 0.4rem 1rem;">
            <i class="fas fa-user-injured" style="font-size: 0.85rem;"></i> OPD
          </a>
          <a href="ipd_test_orders.php" class="lis-nav-item <?= lisSidebarActive('ipd_test_orders.php', $currentPage) ?>" style="font-size: 0.85rem; padding: 0.4rem 1rem;">
            <i class="fas fa-procedures" style="font-size: 0.85rem;"></i> IPD
          </a>
      </div>
  </div>

  <a class="lis-nav-item <?= lisSidebarActive('kanban.php', $currentPage) ?>" data-bs-toggle="collapse" href="#allResultSubmenu" role="button" aria-expanded="<?= lisSidebarActive('kanban.php', $currentPage) ? 'true' : 'false' ?>" aria-controls="allResultSubmenu">
    <i class="fas fa-columns"></i>
    <span>All Result</span>
    <i class="fas fa-chevron-down ms-auto" style="font-size: 0.8rem;"></i>
  </a>
  <div class="collapse <?= lisSidebarActive('kanban.php', $currentPage) ? 'show' : '' ?>" id="allResultSubmenu">
      <div class="ps-4 pe-2 py-1">
          <a href="kanban.php?source=opd" class="lis-nav-item <?= (isset($_GET['source']) && $_GET['source'] === 'opd') ? 'active' : (!isset($_GET['source']) && $currentPage === 'kanban.php' ? 'active' : '') ?>" style="font-size: 0.85rem; padding: 0.4rem 1rem;">
            <i class="fas fa-user-injured" style="font-size: 0.85rem;"></i> OPD
          </a>
          <a href="kanban.php?source=ipd" class="lis-nav-item <?= (isset($_GET['source']) && $_GET['source'] === 'ipd') ? 'active' : '' ?>" style="font-size: 0.85rem; padding: 0.4rem 1rem;">
            <i class="fas fa-procedures" style="font-size: 0.85rem;"></i> IPD
          </a>
      </div>
  </div>

  <!-- ── PATIENTS & SERVICES ── -->
  <div class="lis-nav-section">Clinical</div>

  <a href="patients.php" class="lis-nav-item <?= lisSidebarActive('patients.php', $currentPage) ?>">
    <i class="fas fa-user-injured"></i>
    <span>Patients</span>
  </a>

  <a href="services.php" class="lis-nav-item <?= lisSidebarActive('services.php', $currentPage) ?>">
    <i class="fas fa-vials"></i>
    <span>Services</span>
  </a>

  <a href="critical_alerts.php" class="lis-nav-item <?= lisSidebarActive('critical_alerts.php', $currentPage) ?>">
    <i class="fas fa-exclamation-triangle"></i>
    <span>Critical Alerts</span>
    <span class="lis-nav-badge" id="sidebar-critical-count" style="display:none">!</span>
  </a>

  <!-- ── REPORTING ── -->
  <div class="lis-nav-section">Reporting</div>

  <a href="reports.php" class="lis-nav-item <?= lisSidebarActive('reports.php', $currentPage) ?>">
    <i class="fas fa-file-medical-alt"></i>
    <span>Reports</span>
  </a>

  <a href="analytics.php" class="lis-nav-item <?= lisSidebarActive('analytics.php', $currentPage) ?>">
    <i class="fas fa-chart-pie"></i>
    <span>Analytics</span>
  </a>

  <a href="print_report.php" class="lis-nav-item <?= lisSidebarActive('print_report.php', $currentPage) ?>">
    <i class="fas fa-print"></i>
    <span>Print Reports</span>
  </a>

  <!-- ── OPERATIONS ── -->
  <div class="lis-nav-section">Operations</div>

  <a href="machine_status.php" class="lis-nav-item <?= lisSidebarActive('machine_status.php', $currentPage) ?>">
    <i class="fas fa-server"></i>
    <span>Machine Status</span>
  </a>

  <a href="inventory.php" class="lis-nav-item <?= lisSidebarActive('inventory.php', $currentPage) ?>">
    <i class="fas fa-boxes"></i>
    <span>Inventory</span>
  </a>

  <a href="notifications.php" class="lis-nav-item <?= lisSidebarActive('notifications.php', $currentPage) ?>">
    <i class="fas fa-bell"></i>
    <span>Notifications</span>
    <span class="lis-nav-badge lab-notif-badge" id="sidebar-notif-count" style="display:none">0</span>
  </a>

  <div class="lis-nav-section" style="margin-top: 1.5rem;">System</div>
  <a href="/GM_HMS/view/admin_dashboard.php" class="lis-nav-item">
    <i class="fas fa-home"></i>
    <span>Exit to Admin</span>
  </a>

  <!-- Sidebar Footer -->
  <div class="lis-sidebar-footer">
    <a href="/GM_HMS/login.php" onclick="return confirm('Logout from LIS?')">
      <i class="fas fa-sign-out-alt"></i>
      <span>Logout</span>
    </a>
  </div>
</aside>

<script>
(function(){
  // Branch detection
  const s = sessionStorage.getItem('lis_branch');
  const el = document.getElementById('sidebar-branch-name');
  if (el) {
    if (s) { el.textContent = s; }
    else {
      const branch = location.hostname.toLowerCase().includes('basav') ? 'Basaveshwaranagar' : 'Main Branch';
      el.textContent = branch;
      sessionStorage.setItem('lis_branch', branch);
    }
  }

  // Load pending badge from dashboard API
  fetch('/GM_HMS/api/laboratory/dashboard')
    .then(r => r.json())
    .then(data => {
      const payload = data.data || data;
      const cnt = payload?.stats?.pending ?? 0;
      const badge = document.getElementById('sidebar-pending-count');
      if (badge && cnt > 0) {
        badge.textContent = cnt;
        badge.style.display = 'inline-flex';
      }
    }).catch(() => {});
})();
</script>

<!-- Sidebar overlay (mobile/tablet) -->
<div id="lis-sidebar-overlay" onclick="lisCloseSidebar()"></div>
