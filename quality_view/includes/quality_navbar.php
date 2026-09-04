<?php
/**
 * quality_navbar.php — Top Navbar for Quality Portal
 * Features:
 * - Breadcrumb / Title & Subtitle with Icon
 * - Quick Search Shortcut (logs & manifests)
 * - Notifications Bell with Badge & Dropdown Panel (Pending Dispatches / Alerts)
 * - Hospital Branch Pill
 * - Interactive User Dropdown (Role header, Profile, Password Reset, Main Portal, Logout)
 * - Mobile Sidebar Drawer Toggle
 */
$userName  = $_SESSION['full_name'] ?? $_SESSION['name'] ?? $_SESSION['username'] ?? 'User';
$userRole  = $_SESSION['role'] ?? 'Quality Manager';
$userInit  = strtoupper(substr($userName, 0, 1));
$userPhoto = !empty($_SESSION['photo']) ? htmlspecialchars($_SESSION['photo']) : null;
?>
<header class="qsc-navbar d-print-none">
  <!-- Left: Mobile Toggle + Page Title -->
  <div class="d-flex align-items-center gap-3">
    <!-- Mobile Sidebar Toggle -->
    <button class="btn-qsc-outline d-lg-none"
            type="button"
            onclick="document.getElementById('qsc-sidebar').classList.toggle('open')"
            style="padding:6px 12px;background:transparent;border-color:rgba(243,239,230,0.4);color:var(--qsc-cream);"
            title="Toggle Navigation Menu">
      <i class="fas fa-bars"></i>
    </button>

    <div>
      <div class="qsc-page-title">
        <i class="fas <?= htmlspecialchars($pageIcon ?? 'fa-shield-halved') ?>" style="margin-right:6px;color:var(--qsc-cream);opacity:0.9;"></i>
        <?= htmlspecialchars($pageTitle ?? 'Quality & Safety') ?>
      </div>
      <div class="qsc-page-sub"><?= htmlspecialchars($pageDesc ?? 'GM Hospital Quality, Safety & Compliance') ?></div>
    </div>
  </div>

  <!-- Right: Search + Notifications + Branch + User Dropdown -->
  <div class="d-flex align-items-center gap-3">

    <!-- Quick Search Shortcut (Desktop) -->
    <div class="position-relative d-none d-xl-block">
      <i class="fas fa-search" style="position:absolute; left:11px; top:50%; transform:translateY(-50%); color:rgba(243,239,230,0.6); font-size:0.8rem; pointer-events:none;"></i>
      <input type="text"
             placeholder="Quick search logs, manifest…"
             id="qsc-quick-search"
             style="padding:6px 12px 6px 32px; border-radius:8px; border:1px solid rgba(243,239,230,0.25); font-size:0.8rem; background:rgba(243,239,230,0.12); color:var(--qsc-cream); outline:none; width:210px; transition:all 0.25s ease;"
             onfocus="this.style.background='rgba(255,255,255,0.2)'; this.style.borderColor='var(--qsc-cream)'; this.style.width='270px';"
             onblur="this.style.background='rgba(243,239,230,0.12)'; this.style.borderColor='rgba(243,239,230,0.25)'; this.style.width='210px';"
             onkeydown="if(event.key==='Enter' && this.value.trim()){ window.location.href='/GM_HMS/quality_view/bmw_collection.php?q='+encodeURIComponent(this.value.trim()); }">
    </div>

    <!-- Notifications Dropdown (Pending Dispatches / Quality Alerts) -->
    <div class="dropdown position-relative">
      <button class="qsc-notif-btn border-0 position-relative"
              type="button"
              id="qscNotifDropdown"
              data-bs-toggle="dropdown"
              aria-expanded="false"
              title="Notifications & Alerts">
        <i class="fas fa-bell"></i>
        <span id="qsc-notif-badge"
              class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
              style="display:none; font-size:0.62rem; font-weight:800; padding:3px 6px; box-shadow:0 2px 6px rgba(0,0,0,0.25);">0</span>
      </button>

      <div class="dropdown-menu dropdown-menu-end shadow-lg"
           aria-labelledby="qscNotifDropdown"
           id="qsc-notif-panel"
           style="width:320px; padding:0; border-radius:12px; border:1px solid var(--qsc-border-light); overflow:hidden; margin-top:8px;">
        <div style="padding:12px 16px; border-bottom:1px solid #e2e8f0; font-weight:800; font-size:0.88rem; color:#1e293b; display:flex; justify-content:space-between; align-items:center; background:#fdfbf7;">
          <span style="display:flex;align-items:center;gap:6px;"><i class="fas fa-bell text-warning"></i> Quality Alerts</span>
          <span class="badge" id="qsc-notif-header-badge" style="background:rgba(31,107,74,0.1); color:#1f6b4a; font-size:0.7rem; font-weight:700;">0 New</span>
        </div>
        <div id="qsc-notif-list" style="max-height:260px; overflow-y:auto; padding:8px;">
          <div class="text-center text-muted py-3" style="font-size:0.82rem;">Checking alerts…</div>
        </div>
        <div style="padding:8px 14px; border-top:1px solid #e2e8f0; text-align:center; background:#f8fafc;">
          <a href="/GM_HMS/quality_view/bmw_dispatch.php" style="font-size:0.78rem; color:#1f6b4a; font-weight:700; text-decoration:none;">
            View Vendor Dispatches →
          </a>
        </div>
      </div>
    </div>

    <!-- Branch Pill -->
    <div class="qsc-branch-pill d-none d-md-flex align-items-center">
      <i class="fas fa-hospital-alt" style="margin-right:6px;"></i>
      <?= htmlspecialchars($_SESSION['hospital_branch'] ?? 'GM Hospital') ?>
    </div>

    <!-- User Menu Dropdown -->
    <div class="dropdown">
      <button class="qsc-user-chip border-0"
              type="button"
              id="qscUserDropdown"
              data-bs-toggle="dropdown"
              aria-expanded="false"
              title="User Account Menu">
        <div class="qsc-user-avatar">
          <?php if ($userPhoto): ?>
            <img src="<?= $userPhoto ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
          <?php else: ?>
            <?= $userInit ?>
          <?php endif; ?>
        </div>
        <div class="text-start d-none d-sm-block">
          <div class="qsc-user-name" style="line-height:1.2; font-weight:700;"><?= htmlspecialchars($userName) ?></div>
          <div class="qsc-user-role" style="line-height:1;"><?= htmlspecialchars($userRole) ?></div>
        </div>
        <i class="fas fa-chevron-down ms-1" style="font-size:0.65rem; color:rgba(243,239,230,0.65);"></i>
      </button>

      <ul class="dropdown-menu dropdown-menu-end shadow-lg"
          aria-labelledby="qscUserDropdown"
          style="min-width:220px; border-radius:12px; border:1px solid #e2e8f0; padding:8px; margin-top:8px;">
        <li style="padding:8px 12px 6px;">
          <div style="font-size:0.88rem; font-weight:800; color:#1e293b;"><?= htmlspecialchars($userName) ?></div>
          <div style="font-size:0.72rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;"><?= htmlspecialchars($userRole) ?></div>
          <div style="font-size:0.72rem; color:#1f6b4a; margin-top:3px;"><i class="fas fa-hospital-alt me-1"></i><?= htmlspecialchars($_SESSION['hospital_branch'] ?? 'GM Hospital') ?></div>
        </li>
        <li><hr class="dropdown-divider my-1"></li>
        <li>
          <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="javascript:void(0)" onclick="openProfileModal('profile')" style="font-size:0.83rem; border-radius:8px; font-weight:500; color:#334155;">
            <i class="fas fa-user-circle text-muted" style="width:16px;"></i> My Profile
          </a>
        </li>
        <li>
          <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="javascript:void(0)" onclick="openProfileModal('security')" style="font-size:0.83rem; border-radius:8px; font-weight:500; color:#334155;">
            <i class="fas fa-key text-muted" style="width:16px;"></i> Password Reset
          </a>
        </li>
        <li><hr class="dropdown-divider my-1"></li>
        <li>
          <a class="dropdown-item py-2 text-danger d-flex align-items-center gap-2" href="/GM_HMS/logout.php" onclick="return confirm('Are you sure you want to logout from Quality Portal?')" style="font-size:0.83rem; border-radius:8px; font-weight:600;">
            <i class="fas fa-sign-out-alt" style="width:16px;"></i> Logout
          </a>
        </li>
      </ul>
    </div>

  </div>
</header>
