<?php
/**
 * LIS Sidebar — lab_sidebar.php
 * Collapsible, grouped navigation for the Laboratory module.
 */
$currentPage = basename($_SERVER['PHP_SELF']);
function lisSidebarActive($file, $current) {
    return $file === $current ? 'active' : '';
}
?>
<aside class="lis-sidebar" id="lisSidebar">

  <!-- Brand -->
  <div class="lis-brand">
    <a href="dashboard.php" class="lis-brand-logo">
      <div class="lis-brand-icon"><i class="fas fa-microscope"></i></div>
      <div class="lis-brand-text">
        <div class="lis-brand-name">LIS Portal</div>
        <div class="lis-brand-sub">GM Hospital</div>
      </div>
    </a>
  </div>

  <!-- Branch badge -->
  <div class="lis-branch-badge">
    <i class="fas fa-hospital-alt"></i>
    <span id="sidebar-branch-name">Loading...</span>
  </div>

  <!-- ── MAIN ───────────────────────────────────────────────────────────── -->
  <div class="lis-nav-section">Main</div>

  <a href="dashboard.php" class="lis-nav-item <?= lisSidebarActive('dashboard.php', $currentPage) ?>">
    <i class="fas fa-chart-line"></i>
    <span>Dashboard</span>
  </a>

  <a href="test_orders.php" class="lis-nav-item <?= lisSidebarActive('test_orders.php', $currentPage) ?>">
    <i class="fas fa-flask"></i>
    <span>Lab Orders</span>
    <span class="badge-count" id="sidebar-pending-count" style="display:none">0</span>
  </a>

  <!-- ── CATALOG ────────────────────────────────────────────────────────── -->
  <div class="lis-nav-section">Catalog</div>

  <a href="services.php" class="lis-nav-item <?= lisSidebarActive('services.php', $currentPage) ?>">
    <i class="fas fa-vials"></i>
    <span>Services</span>
  </a>

  <a href="patients.php" class="lis-nav-item <?= lisSidebarActive('patients.php', $currentPage) ?>">
    <i class="fas fa-user-injured"></i>
    <span>Patients</span>
  </a>

  <!-- ── ANALYTICS ─────────────────────────────────────────────────────── -->
  <div class="lis-nav-section">Analytics</div>

  <a href="reports.php" class="lis-nav-item <?= lisSidebarActive('reports.php', $currentPage) ?>">
    <i class="fas fa-chart-bar"></i>
    <span>Reports</span>
  </a>

  <!-- ── TOOLS ──────────────────────────────────────────────────────────── -->
  <div class="lis-nav-section">Tools</div>

  <a href="print_report.php" class="lis-nav-item <?= lisSidebarActive('print_report.php', $currentPage) ?>">
    <i class="fas fa-print"></i>
    <span>Print Slip</span>
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
// Load branch name into sidebar
(function(){
  const s = sessionStorage.getItem('lis_branch');
  const el = document.getElementById('sidebar-branch-name');
  if (el) {
    if (s) {
      el.textContent = s;
    } else {
      // Try to detect from session (PHP echo) or hostname
      const h = location.hostname.toLowerCase();
      const branch = h.includes('basav') ? 'Basaveshwaranagar' : 'Main Branch';
      el.textContent = branch;
      sessionStorage.setItem('lis_branch', branch);
    }
  }

  // Load pending count for sidebar badge
  fetch('/GM_HMS/api/laboratory/dashboard')
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        const cnt = data.stats?.pending ?? 0;
        const badge = document.getElementById('sidebar-pending-count');
        if (badge && cnt > 0) {
          badge.textContent = cnt;
          badge.style.display = 'inline-block';
        }
      }
    }).catch(() => {});
})();
</script>
