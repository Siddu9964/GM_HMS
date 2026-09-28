<?php
/**
 * ot_view/includes/ot_navbar.php
 * Reusable Navbar for the OT Module - modelled after pharmacy_navbar.php
 */
$otUserName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Scrub Nurse';
$otUserRole = $_SESSION['role'] ?? 'Scrub_Nurse';
?>
<nav class="ot-navbar">
  <!-- Left: Hamburger + Breadcrumb -->
  <div class="d-flex align-items-center gap-3">
    <button class="ot-btn ot-btn-outline ot-btn-sm d-lg-none"
            onclick="document.getElementById('ot-sidebar').classList.add('open'); document.getElementById('ot-overlay').style.display='block';">
      <i class="fas fa-bars"></i>
    </button>
    <div style="font-size:.82rem;color:var(--ot-muted);font-weight:500;">
      <i class="fas fa-procedures me-1" style="color:var(--ot-primary);"></i>
      <?= htmlspecialchars($pageTitle ?? 'Dashboard') ?>
    </div>
  </div>

  <!-- Right: Search + Bell + User -->
  <div class="d-flex align-items-center gap-3">

    <!-- Quick Search -->
    <div class="position-relative d-none d-md-block">
      <i class="fas fa-search" style="position:absolute;left:.7rem;top:50%;transform:translateY(-50%);color:var(--ot-muted);font-size:.78rem;"></i>
      <input type="text" id="ot-quick-search" placeholder="Search patient, surgery…"
        style="padding:.42rem .85rem .42rem 2.1rem;border:1.5px solid var(--ot-border);border-radius:8px;font-size:.78rem;background:#fff;outline:none;width:220px;transition:.25s;font-family:'Inter',sans-serif;"
        onfocus="this.style.borderColor='var(--ot-primary)';this.style.width='290px';"
        onblur="this.style.borderColor='var(--ot-border)';this.style.width='220px';"
        onkeydown="if(event.key==='Enter'&&this.value.trim()) window.location.href='surgery_schedule.php?search='+encodeURIComponent(this.value);">
    </div>

    <!-- Notifications Bell -->
    <div class="dropdown" style="position:relative;">
      <button class="ot-btn ot-btn-outline ot-btn-sm position-relative" id="ot-notif-btn"
              data-bs-toggle="dropdown" aria-expanded="false"
              style="border-radius:10px;padding:.42rem .65rem;">
        <i class="fas fa-bell"></i>
        <span id="ot-notif-count"
              style="display:none;position:absolute;top:-5px;right:-5px;background:var(--ot-danger);color:#fff;font-size:.58rem;font-weight:800;padding:2px 5px;border-radius:99px;line-height:1;">
          0
        </span>
      </button>
      <div class="dropdown-menu dropdown-menu-end"
           style="width:300px;padding:0;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,.12);border:1px solid var(--ot-border);">
        <div style="padding:.8rem 1rem;border-bottom:1px solid var(--ot-border);font-weight:700;font-size:.85rem;">
          <i class="fas fa-bell me-2" style="color:var(--ot-primary);"></i>Notifications
        </div>
        <div id="ot-notif-list" style="max-height:260px;overflow-y:auto;padding:.5rem;">
          <div class="text-center text-muted py-3" style="font-size:.8rem;">Loading…</div>
        </div>
        <div style="padding:.6rem 1rem;border-top:1px solid var(--ot-border);text-align:center;">
          <a href="surgery_schedule.php" style="font-size:.76rem;color:var(--ot-primary);font-weight:700;text-decoration:none;">
            View All Surgeries →
          </a>
        </div>
      </div>
    </div>

    <!-- User Dropdown -->
    <div class="dropdown">
      <button class="ot-btn ot-btn-outline d-flex align-items-center gap-2"
              data-bs-toggle="dropdown"
              style="border-radius:10px;padding:.38rem .8rem;">
        <div style="width:28px;height:28px;border-radius:7px;background:var(--ot-primary);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.78rem;overflow:hidden;">
          <?php if (!empty($_SESSION['photo'])): ?>
            <img src="<?= htmlspecialchars($_SESSION['photo']) ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
          <?php else: ?>
            <?= strtoupper(substr($otUserName, 0, 1)) ?>
          <?php endif; ?>
        </div>
        <span style="font-size:.8rem;font-weight:600;"><?= htmlspecialchars($otUserName) ?></span>
        <i class="fas fa-chevron-down" style="font-size:.62rem;color:var(--ot-muted);"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end"
          style="min-width:175px;border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,.1);border:1px solid var(--ot-border);">
        <li>
          <span class="dropdown-item-text"
                style="font-size:.7rem;color:var(--ot-muted);text-transform:uppercase;font-weight:700;letter-spacing:.05em;">
            <?= htmlspecialchars($otUserRole) ?>
          </span>
        </li>
        <li><hr class="dropdown-divider my-1"></li>
        <li>
          <a class="dropdown-item" href="javascript:void(0)" onclick="openProfileModal('profile')" style="font-size:.82rem;">
            <i class="fas fa-user-circle me-2 text-muted"></i>Profile
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="javascript:void(0)" onclick="openProfileModal('security')" style="font-size:.82rem;">
            <i class="fas fa-key me-2 text-muted"></i>Password Reset
          </a>
        </li>
        <li>
          <a class="dropdown-item text-danger" href="../logout.php" style="font-size:.82rem;">
            <i class="fas fa-sign-out-alt me-2"></i>Logout
          </a>
        </li>
        <?php if (in_array(trim($_SESSION['role'] ?? ''), ['admin', 'Admin', 'Administrator'])): ?>
        <li><hr class="dropdown-divider my-1"></li>
        <li>
          <a class="dropdown-item" href="/GM_HMS/view/admin_dashboard.php"
             style="font-size:.82rem;color:#ef4444;">
            <i class="fas fa-arrow-left me-2"></i>Exit to Admin
          </a>
        </li>
        <?php endif; ?>
      </ul>
    </div>

  </div>
</nav>

<script>
// ── OT Notification Bell ────────────────────────────────────────────────
function fetchOtNotifications() {
    const countBadge = document.getElementById('ot-notif-count');
    const list       = document.getElementById('ot-notif-list');

    fetch(API_BASE + 'ot/dashboard')
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            const d = res.data;
            const scheduled = d.scheduled_today ?? 0;
            const ongoing   = d.ongoing_today   ?? 0;
            const total     = scheduled + ongoing;

            if (total > 0) {
                countBadge.textContent     = total;
                countBadge.style.display   = 'inline-block';
                list.innerHTML = `
                  <div style="padding:.55rem;border-radius:8px;border:1.5px solid rgba(31,107,74,.2);background:rgba(31,107,74,.05);margin-bottom:.3rem;">
                    <div style="font-size:.78rem;font-weight:700;color:var(--ot-primary);">
                      <i class="fas fa-calendar-check me-1"></i>${scheduled} Surgery(s) Scheduled Today
                    </div>
                  </div>
                  <div style="padding:.55rem;border-radius:8px;border:1.5px solid #fde68a;background:#fffbeb;margin-bottom:.3rem;">
                    <div style="font-size:.78rem;font-weight:700;color:#92400e;">
                      <i class="fas fa-spinner me-1"></i>${ongoing} Ongoing Surgery(s)
                    </div>
                  </div>`;
            } else {
                countBadge.style.display = 'none';
                list.innerHTML = '<div class="text-center text-muted py-3" style="font-size:.8rem;">No active surgeries today</div>';
            }
        })
        .catch(() => {
            if (list) list.innerHTML = '<div class="text-center text-muted py-3">Error loading</div>';
        });
}

document.addEventListener('DOMContentLoaded', () => {
    fetchOtNotifications();
    setInterval(fetchOtNotifications, 30000);
    document.getElementById('ot-notif-btn')?.addEventListener('click', fetchOtNotifications);
});
</script>
