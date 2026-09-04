<?php
/**
 * quality_sidebar.php — QSC Sidebar Navigation
 * Mirrors lab_sidebar.php pattern
 */
$currentPage = basename($_SERVER['PHP_SELF']);
function qscSidebarActive($file, $current) {
    return $file === $current ? 'active' : '';
}
?>
<aside class="qsc-sidebar d-print-none" id="qsc-sidebar">

  <!-- Brand -->
  <div class="qsc-brand">
    <div class="qsc-brand-icon"><i class="fas fa-shield-halved"></i></div>
    <div>
      <div class="qsc-brand-name">Quality & Safety</div>
      <div class="qsc-brand-sub">GM Hospital</div>
    </div>
  </div>

  <nav class="qsc-nav">
    <!-- MAIN -->
    <div class="qsc-nav-section">Main</div>

    <a href="/GM_HMS/quality_view/dashboard.php"
       class="qsc-nav-item <?= qscSidebarActive('dashboard.php', $currentPage) ?>">
      <i class="fas fa-chart-line"></i>
      <span>Dashboard</span>
    </a>

    <!-- BMW SUBMODULE -->
    <div class="qsc-nav-section">Biomedical Waste</div>

    <a href="/GM_HMS/quality_view/bmw_collection.php"
       class="qsc-nav-item <?= qscSidebarActive('bmw_collection.php', $currentPage) ?>">
      <i class="fas fa-biohazard"></i>
      <span>Collection Log</span>
    </a>

    <a href="/GM_HMS/quality_view/bmw_dispatch.php"
       class="qsc-nav-item <?= qscSidebarActive('bmw_dispatch.php', $currentPage) ?>">
      <i class="fas fa-truck-medical"></i>
      <span>Dispatch to Vendor</span>
    </a>

    <a href="/GM_HMS/quality_view/reports.php"
       class="qsc-nav-item <?= qscSidebarActive('reports.php', $currentPage) ?>">
      <i class="fas fa-file-lines"></i>
      <span>Reports</span>
    </a>

    <!-- FUTURE SUBMODULES -->
    <div class="qsc-nav-section">Coming Soon</div>

    <span class="qsc-nav-item disabled">
      <i class="fas fa-bug"></i><span>Pest Control</span>
      <span class="qsc-badge-soon">Soon</span>
    </span>
    <span class="qsc-nav-item disabled">
      <i class="fas fa-pump-medical"></i><span>CSSD</span>
      <span class="qsc-badge-soon">Soon</span>
    </span>
    <span class="qsc-nav-item disabled">
      <i class="fas fa-virus-slash"></i><span>Infection Control</span>
      <span class="qsc-badge-soon">Soon</span>
    </span>
    <span class="qsc-nav-item disabled">
      <i class="fas fa-fire-extinguisher"></i><span>Fire & Safety</span>
      <span class="qsc-badge-soon">Soon</span>
    </span>
    <span class="qsc-nav-item disabled">
      <i class="fas fa-tools"></i><span>Equipment</span>
      <span class="qsc-badge-soon">Soon</span>
    </span>
    <span class="qsc-nav-item disabled">
      <i class="fas fa-clipboard-check"></i><span>Audits</span>
      <span class="qsc-badge-soon">Soon</span>
    </span>

  </nav>

  <!-- Footer -->
  <div style="padding: 12px 16px; border-top: 1px solid rgba(255,255,255,0.08);">
    <a href="/GM_HMS/logout.php" class="qsc-nav-item" style="color:#f87171;">
      <i class="fas fa-sign-out-alt"></i><span>Logout</span>
    </a>
  </div>

</aside>
