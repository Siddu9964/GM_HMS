<?php
/**
 * RIS Sidebar — rad_sidebar.php
 * Full navigation for the Radiology Information System.
 */
$currentPage = basename($_SERVER['PHP_SELF']);
function risSidebarActive($file, $current) {
    return $file === $current ? 'active' : '';
}
?>
<aside class="lis-sidebar" id="lis-sidebar">

  <!-- Brand -->
  <div class="lis-brand">
    <a href="dashboard.php" class="lis-brand-logo">
      <div class="lis-brand-icon" style="background: #1f6b4a; color: #f3efe6;"><i class="fas fa-x-ray"></i></div>
      <div>
        <div class="lis-brand-name">RIS Portal</div>
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

  <a href="dashboard.php" class="lis-nav-item <?= risSidebarActive('dashboard.php', $currentPage) ?>">
    <i class="fas fa-chart-line"></i>
    <span>Dashboard</span>
  </a>

  <a class="lis-nav-item <?= in_array($currentPage, ['test_orders.php', 'ipd_test_orders.php']) ? 'active' : '' ?>" data-bs-toggle="collapse" href="#radOrdersSubmenu" role="button" aria-expanded="<?= in_array($currentPage, ['test_orders.php', 'ipd_test_orders.php']) ? 'true' : 'false' ?>" aria-controls="radOrdersSubmenu">
    <i class="fas fa-radiation"></i>
    <span>Scan Orders</span>
    <span class="lis-nav-badge" id="sidebar-orders-total-badge" style="display:none; margin-right: 6px;">0</span>
    <i class="fas fa-chevron-down ms-auto" style="font-size: 0.8rem;"></i>
  </a>
  <div class="collapse <?= in_array($currentPage, ['test_orders.php', 'ipd_test_orders.php']) ? 'show' : '' ?>" id="radOrdersSubmenu">
      <div class="ps-4 pe-2 py-1">
          <a href="test_orders.php" class="lis-nav-item <?= risSidebarActive('test_orders.php', $currentPage) ?>" style="font-size: 0.85rem; padding: 0.4rem 1rem;">
            <i class="fas fa-user-injured" style="font-size: 0.85rem;"></i> OPD
            <span class="lis-nav-badge" id="sidebar-opd-count" style="display:none; font-size:0.65rem; padding:1px 6px;">0</span>
          </a>
          <a href="ipd_test_orders.php" class="lis-nav-item <?= risSidebarActive('ipd_test_orders.php', $currentPage) ?>" style="font-size: 0.85rem; padding: 0.4rem 1rem;">
            <i class="fas fa-procedures" style="font-size: 0.85rem;"></i> IPD
            <span class="lis-nav-badge" id="sidebar-ipd-count" style="display:none; font-size:0.65rem; padding:1px 6px;">0</span>
          </a>
      </div>
  </div>

  <a class="lis-nav-item <?= risSidebarActive('kanban.php', $currentPage) ?>" data-bs-toggle="collapse" href="#allRadResultSubmenu" role="button" aria-expanded="<?= risSidebarActive('kanban.php', $currentPage) ? 'true' : 'false' ?>" aria-controls="allRadResultSubmenu">
    <i class="fas fa-images"></i>
    <span>All Results</span>
    <i class="fas fa-chevron-down ms-auto" style="font-size: 0.8rem;"></i>
  </a>
  <div class="collapse <?= risSidebarActive('kanban.php', $currentPage) ? 'show' : '' ?>" id="allRadResultSubmenu">
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

  <a href="patients.php" class="lis-nav-item <?= risSidebarActive('patients.php', $currentPage) ?>">
    <i class="fas fa-user-injured"></i>
    <span>Patients</span>
  </a>

  <a href="services.php" class="lis-nav-item <?= risSidebarActive('services.php', $currentPage) ?>">
    <i class="fas fa-list-ul"></i>
    <span>Services Catalog</span>
  </a>

  <a href="critical_alerts.php" class="lis-nav-item <?= risSidebarActive('critical_alerts.php', $currentPage) ?>">
    <i class="fas fa-exclamation-triangle"></i>
    <span>Critical Alerts</span>
  </a>

  <!-- ── REPORTING ── -->
  <div class="lis-nav-section">Reporting</div>

  <a href="reports.php" class="lis-nav-item <?= risSidebarActive('reports.php', $currentPage) ?>">
    <i class="fas fa-file-medical-alt"></i>
    <span>Reports</span>
  </a>

  <a href="analytics.php" class="lis-nav-item <?= risSidebarActive('analytics.php', $currentPage) ?>">
    <i class="fas fa-chart-pie"></i>
    <span>Analytics</span>
  </a>

  <!-- ── OPERATIONS ── -->
  <div class="lis-nav-section">Operations</div>

  <a href="notifications.php" class="lis-nav-item <?= risSidebarActive('notifications.php', $currentPage) ?>">
    <i class="fas fa-bell"></i>
    <span>Notifications</span>
    <span class="lis-nav-badge rad-notif-badge" id="sidebar-notif-count" style="display:none">0</span>
  </a>

  <div class="lis-nav-section" style="margin-top: 1.5rem;">System</div>
  <a href="/GM_HMS/view/admin_dashboard.php" class="lis-nav-item">
    <i class="fas fa-home"></i>
    <span>Exit to Admin</span>
  </a>

  <!-- Sidebar Footer -->
  <div class="lis-sidebar-footer">
    <a href="/GM_HMS/logout.php" onclick="return radConfirmLogout(event)">
      <i class="fas fa-sign-out-alt"></i>
      <span>Logout</span>
    </a>
  </div>
</aside>

<!-- Mobile/Tablet Drawer Backdrop Overlay -->
<div id="lis-sidebar-overlay" onclick="lisCloseSidebar()"></div>

<script>
(function(){
  const s = sessionStorage.getItem('lis_branch');
  const el = document.getElementById('sidebar-branch-name');
  if (el) {
    if (s) { el.textContent = s; }
    else {
      const branch = location.hostname.toLowerCase().includes('basav') ? 'Basaveshwaranagar' : 'Main Branch';
      el.textContent = branch;
    }
  }
})();
</script>
