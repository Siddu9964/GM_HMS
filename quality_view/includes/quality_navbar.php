<?php
/**
 * quality_navbar.php — Top Navbar for Quality Portal
 */
?>
<header class="qsc-navbar d-print-none">
  <!-- Left: Page Title -->
  <div>
    <div class="qsc-page-title">
      <i class="fas <?= htmlspecialchars($pageIcon ?? 'fa-shield-halved') ?>" style="margin-right:6px;color:var(--qsc-cream);opacity:0.85;"></i>
      <?= htmlspecialchars($pageTitle ?? 'Quality & Safety') ?>
    </div>
    <div class="qsc-page-sub"><?= htmlspecialchars($pageDesc ?? 'GM Hospital Quality, Safety & Compliance') ?></div>
  </div>

  <!-- Right: Branch + User -->
  <div style="display:flex;align-items:center;gap:14px;">

    <!-- Branch Pill -->
    <div class="qsc-branch-pill">
      <i class="fas fa-hospital-alt" style="margin-right:5px;"></i>
      <?= htmlspecialchars($_SESSION['hospital_branch'] ?? 'GM Hospital') ?>
    </div>

    <!-- Mobile Sidebar Toggle -->
    <button class="btn-qsc-outline d-lg-none"
            onclick="document.getElementById('qsc-sidebar').classList.toggle('open')"
            style="padding:6px 12px;background:transparent;border-color:rgba(243,239,230,0.4);color:var(--qsc-cream);">
      <i class="fas fa-bars"></i>
    </button>

    <!-- User Pill -->
    <div style="display:flex;align-items:center;gap:9px;">
      <div class="qsc-user-avatar">
        <?= strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1)) ?>
      </div>
      <div>
        <div class="qsc-user-name"><?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></div>
        <div class="qsc-user-role"><?= htmlspecialchars($_SESSION['role'] ?? '') ?></div>
      </div>
    </div>

  </div>
</header>
