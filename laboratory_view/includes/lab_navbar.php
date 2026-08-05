<?php
/**
 * LIS Navbar — lab_navbar.php
 * Sticky top navigation bar with search, live clock, notifications, user profile.
 */
$navTitle  = $navTitle  ?? ($pageTitle ?? 'Laboratory');
$navSub    = $navSub    ?? 'Laboratory Information System';
$userInit  = strtoupper(substr($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'U', 0, 2));
$userName  = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User');
$userRole  = htmlspecialchars($_SESSION['role'] ?? 'Lab Staff');
$pageIcon  = $pageIcon  ?? 'fa-microscope';
?>
<nav class="lis-navbar">

  <!-- Hamburger (mobile/tablet only) -->
  <button id="lis-hamburger" onclick="lisToggleSidebar()" aria-label="Toggle navigation">
    <i class="fas fa-bars"></i>
  </button>

  <!-- Page Title + Icon -->
  <div class="lis-navbar-brand">
    <div style="width:34px;height:34px;background:linear-gradient(135deg,var(--r-green-dk),var(--r-green));border-radius:9px;display:flex;align-items:center;justify-content:center;color:var(--r-cream);font-size:0.85rem;flex-shrink:0;">
      <i class="fas <?= htmlspecialchars($pageIcon) ?>"></i>
    </div>
    <div>
      <div class="lis-navbar-title"><?= htmlspecialchars($navTitle) ?></div>
      <div class="lis-navbar-subtitle"><?= htmlspecialchars($navSub) ?></div>
    </div>
  </div>

  <div class="lis-navbar-divider"></div>

  <!-- Global Search -->
  <div class="lis-nav-search">
    <i class="fas fa-search"></i>
    <input type="text" id="lis-global-search" placeholder="Search orders, patients..." autocomplete="off">
    <span class="lis-search-kbd">⌘K</span>
  </div>

  <div class="lis-navbar-spacer"></div>

  <!-- Live Clock (hidden on mobile via CSS) -->
  <div class="lis-live-clock" id="lis-clock-pill">
    <span class="lis-live-dot"></span>
    <span id="lis-clock">--:-- --</span>
  </div>

  <!-- Date pill (hidden on mobile via CSS) -->
  <div id="lis-date-pill" style="font-size:0.72rem;font-weight:700;color:var(--r-green);background:var(--r-green-ultra);padding:5px 12px;border-radius:20px;border:1px solid var(--r-green-tint);white-space:nowrap;">
    <i class="fas fa-calendar-alt" style="margin-right:5px;"></i>
    <span id="lis-date">--</span>
  </div>

  <!-- Notification Bell -->
  <a href="notifications.php" class="lis-notif-btn" title="Notifications" data-bs-toggle="dropdown" aria-expanded="false">
    <i class="fas fa-bell"></i>
    <span class="lis-notif-badge lab-notif-badge" id="navbar-notif-badge" style="display:none">0</span>
  </a>
  <div class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width: 320px; border-radius: 12px; border: 1px solid var(--r-green-tint); padding: 0; max-height: 400px; overflow-y: auto;">
    <div style="padding: 12px 16px; border-bottom: 1px solid var(--r-green-tint); font-weight: 800; color: var(--r-txt); display: flex; justify-content: space-between; align-items: center;">
        Notifications
        <span class="badge rounded-pill lab-notif-badge" style="display:none;background:var(--r-green);color:var(--r-cream);">0</span>
    </div>
    <div id="lab-notif-dropdown-list">
        <div class="p-4 text-center" style="font-size:0.8rem;color:var(--r-txt-muted);">Loading...</div>
    </div>
  </div>

  <!-- User Dropdown -->
  <div class="dropdown">
    <button class="lis-user-chip border-0" data-bs-toggle="dropdown" aria-expanded="false">
      <div class="lis-user-avatar">
        <?php if (!empty($_SESSION['photo'])): ?>
          <img src="<?= htmlspecialchars($_SESSION['photo']) ?>" alt="Avatar">
        <?php else: ?>
          <?= $userInit ?>
        <?php endif; ?>
      </div>
      <div class="d-none d-md-block">
        <div class="lis-user-name"><?= $userName ?></div>
        <div class="lis-user-role"><?= $userRole ?></div>
      </div>
      <i class="fas fa-chevron-down" style="font-size:0.6rem;color:var(--r-txt-light);margin-left:2px;"></i>
    </button>

    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width:200px;border-radius:12px;border:1px solid var(--r-green-tint);margin-top:8px;padding:6px;">
      <li>
        <div style="padding:10px 14px 6px;">
          <div style="font-size:0.82rem;font-weight:800;color:var(--r-txt);"><?= $userName ?></div>
          <div style="font-size:0.68rem;color:var(--r-txt-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.05em;"><?= $userRole ?></div>
        </div>
      </li>
      <li><hr class="dropdown-divider my-1" style="border-color:var(--r-green-tint);"></li>
      <li>
        <a class="dropdown-item py-2" href="javascript:void(0)" onclick="openProfileModal('profile')" style="font-size:0.82rem;border-radius:8px;color:var(--r-txt);">
          <i class="fas fa-user-circle me-2" style="color:var(--r-green);width:16px;"></i>My Profile
        </a>
      </li>
      <li>
        <a class="dropdown-item py-2" href="javascript:void(0)" onclick="openProfileModal('security')" style="font-size:0.82rem;border-radius:8px;color:var(--r-txt);">
          <i class="fas fa-key me-2" style="color:var(--r-txt-muted);width:16px;"></i>Change Password
        </a>
      </li>
      <li><hr class="dropdown-divider my-1" style="border-color:var(--r-green-tint);"></li>
      <li>
        <a class="dropdown-item py-2" href="../logout.php" onclick="return confirm('Logout from LIS?')" style="font-size:0.82rem;border-radius:8px;color:#c0392b;">
          <i class="fas fa-sign-out-alt me-2" style="width:16px;"></i>Logout
        </a>
      </li>
    </ul>
  </div>
</nav>

<script>
(function(){
  function padZ(n){ return String(n).padStart(2,'0'); }
  function tickClock(){
    const now   = new Date();
    let h = now.getHours(), m = now.getMinutes(), s = now.getSeconds();
    const ampm  = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    const el = document.getElementById('lis-clock');
    if (el) el.textContent = `${padZ(h)}:${padZ(m)}:${padZ(s)} ${ampm}`;
    const days   = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const dateEl = document.getElementById('lis-date');
    if (dateEl) dateEl.textContent = `${days[now.getDay()].slice(0,3)}, ${months[now.getMonth()]} ${now.getDate()}, ${now.getFullYear()}`;
  }
  tickClock();
  setInterval(tickClock, 1000);

  // Keyboard shortcut for search
  document.addEventListener('keydown', e => {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
      e.preventDefault();
      document.getElementById('lis-global-search')?.focus();
    }
    if (e.key === 'Escape') {
      const s = document.getElementById('lis-global-search');
      if (document.activeElement === s) s?.blur();
    }
  });
})();
</script>
