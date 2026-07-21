<?php
/**
 * LIS Navbar — lab_navbar.php
 * Top sticky navigation bar with page title, live clock, user chip.
 * $navTitle and $navSub are set per-page before including this file.
 */
$navTitle = $navTitle ?? ($pageTitle ?? 'Laboratory');
$navSub   = $navSub   ?? 'Laboratory Information System';
$userInit = strtoupper(substr($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'U', 0, 1));
$userName = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User');
$userRole = htmlspecialchars($_SESSION['role'] ?? 'Lab Staff');
?>
<nav class="lis-navbar">
  <!-- Page title -->
  <div>
    <div class="lis-navbar-title"><?= htmlspecialchars($navTitle) ?></div>
    <div class="lis-navbar-subtitle"><?= htmlspecialchars($navSub) ?></div>
  </div>

  <div class="lis-navbar-spacer"></div>

  <!-- Live clock -->
  <div class="lis-live-clock">
    <span class="lis-live-dot"></span>
    <span id="lis-clock">--:-- --</span>
  </div>

  <!-- Today date pill -->
  <div style="font-size:0.75rem;font-weight:700;color:var(--lis-primary);background:var(--lis-primary-light);padding:5px 12px;border-radius:20px;" id="lis-date-pill">
    <i class="fas fa-calendar-alt" style="margin-right:5px;"></i>
    <span id="lis-date">--</span>
  </div>

  <!-- User Menu Dropdown -->
  <div class="dropdown ms-3">
    <button class="btn btn-outline-secondary d-flex align-items-center gap-2 border-0" data-bs-toggle="dropdown" style="border-radius:10px;padding:.4rem .85rem;background:transparent;outline:none;box-shadow:none;">
      <div style="width:32px;height:32px;border-radius:8px;background:var(--lis-primary);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.85rem;overflow:hidden;">
        <?php if(!empty($_SESSION['photo'])): ?>
          <img src="<?= htmlspecialchars($_SESSION['photo']) ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
        <?php else: ?>
          <?= $userInit ?>
        <?php endif; ?>
      </div>
      <div class="text-start d-none d-md-block" style="line-height:1.2;">
        <div style="font-size:.82rem;font-weight:700;color:var(--lis-text);"><?= htmlspecialchars($userName) ?></div>
        <div style="font-size:.7rem;color:var(--lis-text-muted);"><?= htmlspecialchars($userRole) ?></div>
      </div>
      <i class="fas fa-chevron-down ms-1" style="font-size:.65rem;color:var(--lis-text-muted);"></i>
    </button>
    
    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width:200px;border-radius:10px;border:1px solid var(--lis-border);margin-top:8px;">
      <li><span class="dropdown-item-text" style="font-size:.72rem;color:var(--lis-text-muted);text-transform:uppercase;font-weight:700;letter-spacing:.05em;"><?= htmlspecialchars($userRole) ?></span></li>
      <li><hr class="dropdown-divider my-1"></li>
      <!-- We will attach generic openProfileModal methods if present, or link to specific pages -->
      <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="openProfileModal('profile')" style="font-size:.83rem;"><i class="fas fa-user-circle me-2 text-muted"></i>Profile</a></li>
      <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="openProfileModal('security')" style="font-size:.83rem;"><i class="fas fa-key me-2 text-muted"></i>Password Reset</a></li>
      <li><hr class="dropdown-divider my-1"></li>
      <li><a class="dropdown-item text-danger py-2" href="../logout.php" style="font-size:.83rem;" onclick="return confirm('Logout from LIS?')"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
    </ul>
  </div>
</nav>

<script>
(function(){
  function padZ(n){return String(n).padStart(2,'0');}
  function tickClock(){
    const now = new Date();
    let h = now.getHours(), m = now.getMinutes(), s = now.getSeconds();
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    const el = document.getElementById('lis-clock');
    if(el) el.textContent = `${padZ(h)}:${padZ(m)}:${padZ(s)} ${ampm}`;

    const days   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const dateEl = document.getElementById('lis-date');
    if(dateEl) dateEl.textContent = `${days[now.getDay()]}, ${months[now.getMonth()]} ${now.getDate()}, ${now.getFullYear()}`;
  }
  tickClock();
  setInterval(tickClock, 1000);
})();
</script>
