<?php
/**
 * Pharmacy ERP - Reusable Navbar
 */
$userName = $_SESSION['username'] ?? $_SESSION['name'] ?? 'Pharmacist';
$userRole = $_SESSION['role']     ?? 'pharmacy';
?>
<nav class="ph-navbar">
  <div class="d-flex align-items-center gap-3">
    <button class="ph-btn ph-btn-outline ph-btn-icon d-lg-none" onclick="phToggleSidebar()">
      <i class="fas fa-bars"></i>
    </button>
    <div id="ph-breadcrumb" style="font-size:.82rem;color:var(--ph-muted);font-weight:500;">
      <?= htmlspecialchars($pageTitle ?? 'Dashboard') ?>
    </div>
  </div>

  <div class="d-flex align-items-center gap-3">
    <!-- Search quick shortcut -->
    <div class="position-relative d-none d-md-block">
      <i class="fas fa-search" style="position:absolute;left:.7rem;top:50%;transform:translateY(-50%);color:var(--ph-muted);font-size:.8rem;"></i>
      <input type="text" placeholder="Advanced search (name, composition)…" id="ph-quick-search"
        style="padding:.45rem .85rem .45rem 2.1rem;border:1.5px solid var(--ph-border);border-radius:8px;font-size:.8rem;background:#F8FAFC;outline:none;width:240px;transition:.2s;"
        onfocus="this.style.borderColor='var(--ph-primary)';this.style.width='320px'"
        onblur="this.style.borderColor='var(--ph-border)';this.style.width='240px'"
        onkeydown="if(event.key==='Enter') window.location.href='products.php?q='+encodeURIComponent(this.value)">
    </div>

    <!-- Notifications -->
    <div class="dropdown" style="position:relative;">
      <button class="ph-btn ph-btn-outline ph-btn-icon position-relative" id="ph-notif-btn"
        data-bs-toggle="dropdown" aria-expanded="false" style="border-radius:10px;">
        <i class="fas fa-bell"></i>
        <span id="ph-notif-count" style="display:none;position:absolute;top:-5px;right:-5px;background:var(--ph-danger);color:#fff;font-size:.58rem;font-weight:800;padding:2px 5px;border-radius:99px;line-height:1;">0</span>
      </button>
      <div class="dropdown-menu dropdown-menu-end" style="width:320px;padding:0;border-radius:12px;box-shadow:var(--ph-shadow-lg);border:1px solid var(--ph-border);" id="ph-notif-panel">
        <div style="padding:.85rem 1rem;border-bottom:1px solid var(--ph-border);font-weight:700;font-size:.88rem;">Notifications</div>
        <div id="ph-notif-list" style="max-height:280px;overflow-y:auto;padding:.5rem;">
          <div class="text-center text-muted py-3" style="font-size:.82rem;">Loading…</div>
        </div>
        <div style="padding:.65rem 1rem;border-top:1px solid var(--ph-border);text-align:center;">
          <a href="inventory_alerts.php" style="font-size:.78rem;color:var(--ph-primary);font-weight:600;text-decoration:none;">View All Alerts →</a>
        </div>
      </div>
    </div>

    <!-- User Menu -->
    <div class="dropdown">
      <button class="ph-btn ph-btn-outline d-flex align-items-center gap-2" data-bs-toggle="dropdown" style="border-radius:10px;padding:.4rem .85rem;">
        <div style="width:28px;height:28px;border-radius:7px;background:var(--ph-primary);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.78rem;overflow:hidden;">
          <?php if(!empty($_SESSION['photo'])): ?>
            <img src="<?= htmlspecialchars($_SESSION['photo']) ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
          <?php else: ?>
            <?= strtoupper(substr($userName, 0, 1)) ?>
          <?php endif; ?>
        </div>
        <span style="font-size:.82rem;font-weight:600;"><?= htmlspecialchars($userName) ?></span>
        <i class="fas fa-chevron-down" style="font-size:.65rem;color:var(--ph-muted);"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end" style="min-width:180px;border-radius:10px;box-shadow:var(--ph-shadow-md);border:1px solid var(--ph-border);">
        <li><span class="dropdown-item-text" style="font-size:.72rem;color:var(--ph-muted);text-transform:uppercase;font-weight:700;letter-spacing:.05em;"><?= htmlspecialchars($userRole) ?></span></li>
        <li><hr class="dropdown-divider my-1"></li>
        <li><a class="dropdown-item" href="javascript:void(0)" onclick="openProfileModal('profile')" style="font-size:.83rem;"><i class="fas fa-user-circle me-2 text-muted"></i>Profile</a></li>
        <li><a class="dropdown-item" href="javascript:void(0)" onclick="openProfileModal('security')" style="font-size:.83rem;"><i class="fas fa-key me-2 text-muted"></i>Password Reset</a></li>
        <li><a class="dropdown-item text-danger" href="../logout.php" style="font-size:.83rem;"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
      </ul>
    </div>
  </div>
</nav>
<script>
// Load notification list into dropdown
document.getElementById('ph-notif-btn')?.addEventListener('click', async () => {
  const list = document.getElementById('ph-notif-list');
  try {
    const r = await fetch(API_BASE + 'pharmacy/notifications/list');
    const d = await r.json();
    if (!d.success || !d.data.length) { list.innerHTML = '<div class="text-center text-muted py-3" style="font-size:.82rem;">No active alerts</div>'; return; }
    list.innerHTML = d.data.map(n => `
      <a href="${n.link||'#'}" class="ph-alert-item ${n.type} text-decoration-none" style="display:flex;align-items:flex-start;gap:.6rem;padding:.6rem;border-radius:8px;margin-bottom:.35rem;border:1px solid ${n.type==='danger'?'#fecaca':n.type==='warning'?'#fde68a':'#bfdbfe'};background:${n.type==='danger'?'#fff5f5':n.type==='warning'?'#fffbeb':'#eff6ff'};">
        <i class="${n.icon}" style="color:${n.type==='danger'?'#ef4444':n.type==='warning'?'#f59e0b':'#3b82f6'};margin-top:2px;"></i>
        <div>
          <div style="font-size:.78rem;font-weight:700;color:var(--ph-text);">${n.title}</div>
          <div style="font-size:.7rem;color:var(--ph-muted);">${n.body}</div>
        </div>
      </a>`).join('');
  } catch(e) { list.innerHTML = '<div class="text-center text-muted py-3">Error loading</div>'; }
});
</script>
