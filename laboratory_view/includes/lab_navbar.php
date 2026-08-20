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

<!-- Lab Discharge Clearance Action Modal -->
<div id="labClearanceModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 35, 25, 0.6); backdrop-filter: blur(4px); z-index: 99999; align-items: center; justify-content: center;">
  <div style="background: #ffffff; border-radius: 16px; max-width: 580px; width: 92%; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 60px rgba(0,0,0,0.35); border: 2px solid #1f6b4a;">
    <div style="background: #1f6b4a; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
      <div style="font-size: 1.05rem; font-weight: 800; color: #ffffff; display: flex; align-items: center; gap: 8px;">
        <i class="fas fa-microscope"></i> Laboratory Discharge Clearance
      </div>
      <button type="button" onclick="closeLabClearanceModal()" style="background: none; border: none; font-size: 1.3rem; color: #ffffff; cursor: pointer;">&times;</button>
    </div>

    <div style="padding: 20px; overflow-y: auto; flex: 1; text-align: left;">
      <!-- Patient Info Box -->
      <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; margin-bottom: 14px;">
        <div style="font-weight: 800; font-size: 1.05rem; color: #1f6b4a;" id="lab-modal-pt-name">Patient Name</div>
        <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;" id="lab-modal-pt-details">PID: – | IP#: – | Bed: –</div>
      </div>

      <!-- Multi-Department Clearance Grid -->
      <div style="background: #fdfbf7; border: 1.5px dashed #cbd5e1; border-radius: 10px; padding: 12px; margin-bottom: 14px;">
        <div style="font-size: 0.72rem; font-weight: 800; color: #1f6b4a; text-transform: uppercase; margin-bottom: 8px;">Multi-Department Clearance Status</div>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
          <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px; text-align: center;">
            <div style="font-size: 0.68rem; font-weight: 700; color: #64748b;">Reception / Billing</div>
            <div id="lab-status-rec" style="font-weight: 800; font-size: 0.8rem; margin-top: 2px; color: #f59e0b;">⏳ Pending</div>
          </div>
          <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px; text-align: center;">
            <div style="font-size: 0.68rem; font-weight: 700; color: #64748b;">Pharmacy</div>
            <div id="lab-status-ph" style="font-weight: 800; font-size: 0.8rem; margin-top: 2px; color: #f59e0b;">⏳ Pending</div>
          </div>
          <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px; text-align: center;">
            <div style="font-size: 0.68rem; font-weight: 700; color: #64748b;">Laboratory</div>
            <div id="lab-status-lab" style="font-weight: 800; font-size: 0.8rem; margin-top: 2px; color: #f59e0b;">⏳ Pending</div>
          </div>
        </div>
      </div>

      <!-- Queries Box -->
      <div id="lab-modal-queries-box" style="display: none; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 8px; padding: 10px; margin-bottom: 14px; font-size: 0.8rem;">
        <div style="font-weight: 800; color: #e11d48; margin-bottom: 4px;"><i class="fas fa-comments"></i> Department Queries & Discussion:</div>
        <div id="lab-modal-queries-list"></div>
      </div>

      <!-- Clearance Actions -->
      <div style="border-top: 1px solid #e2e8f0; padding-top: 14px;">
        <div style="font-weight: 800; font-size: 0.85rem; color: #1e293b; margin-bottom: 8px;"><i class="fas fa-check-square text-success"></i> Option A: Approve Laboratory Clearance</div>
        <div style="margin-bottom: 8px;">
          <input type="text" id="lab-clearance-notes" style="width: 100%; padding: 8px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.85rem;" placeholder="Optional notes e.g. All ordered diagnostic reports signed & delivered...">
        </div>
        <button type="button" onclick="submitLabClearance('approve')" style="width: 100%; padding: 9px; background: #1f6b4a; color: #ffffff; font-weight: 700; border: none; border-radius: 8px; cursor: pointer;">
          <i class="fas fa-check-circle mr-1"></i> Approve Laboratory Clearance
        </button>
      </div>

      <div style="border-top: 1px dashed #e2e8f0; margin-top: 14px; padding-top: 12px;">
        <div style="font-weight: 800; font-size: 0.85rem; color: #dc2626; margin-bottom: 8px;"><i class="fas fa-exclamation-triangle text-danger"></i> Option B: Raise Query / Pending Test Issue</div>
        <div style="margin-bottom: 8px;">
          <input type="text" id="lab-query-text" style="width: 100%; padding: 8px 12px; border: 1.5px solid #fca5a5; border-radius: 8px; font-size: 0.85rem;" placeholder="Specify issue e.g. Blood culture test report pending from microbiology...">
        </div>
        <button type="button" onclick="submitLabClearance('query')" style="width: 100%; padding: 8px; background: transparent; border: 1.5px solid #dc2626; color: #dc2626; font-weight: 700; border-radius: 8px; cursor: pointer;">
          <i class="fas fa-paper-plane mr-1"></i> Raise Query to Nurse & Admin
        </button>
      </div>
    </div>
  </div>
</div>

<script>
let currentLabClearance = null;

function closeLabClearanceModal() {
  document.getElementById('labClearanceModal').style.display = 'none';
}

function openLabClearanceModal(item) {
  currentLabClearance = item;
  document.getElementById('lab-modal-pt-name').textContent = item.patient_name || 'Patient';
  document.getElementById('lab-modal-pt-details').textContent = `PID: ${item.patient_id} | IP#: ${item.admission_id} | Location: ${item.bed_info || 'Ward'} | Doctor: Dr. ${item.doctor_name || 'Consultant'}`;

  const setStatus = (elId, status) => {
    const el = document.getElementById(elId);
    if (!el) return;
    if (status === 'Approved') el.innerHTML = `<span style="color:#16a34a;"><i class="fas fa-check-circle"></i> Cleared</span>`;
    else if (status === 'Query') el.innerHTML = `<span style="color:#dc2626;"><i class="fas fa-exclamation-triangle"></i> Query</span>`;
    else el.innerHTML = `<span style="color:#f59e0b;"><i class="fas fa-clock"></i> Pending</span>`;
  };

  setStatus('lab-status-rec', item.reception_status);
  setStatus('lab-status-ph', item.pharmacy_status);
  setStatus('lab-status-lab', item.lab_status);

  document.getElementById('lab-clearance-notes').value = '';
  document.getElementById('lab-query-text').value = '';

  fetch(`/GM_HMS/api/discharge_clearance.php?action=status&admission_id=${encodeURIComponent(item.admission_id)}`)
    .then(r => r.json())
    .then(res => {
      const qBox = document.getElementById('lab-modal-queries-box');
      const qList = document.getElementById('lab-modal-queries-list');
      if (res.queries && res.queries.length > 0) {
        qBox.style.display = 'block';
        qList.innerHTML = res.queries.map(q => `
          <div style="margin-bottom:6px;padding-bottom:4px;border-bottom:1px dashed #fecdd3;">
            <strong>[${q.department.toUpperCase()}] ${q.user_name || 'Staff'}:</strong> ${q.query_text}
            <span class="badge" style="float:right;font-size:0.65rem;background:${q.status==='Resolved'?'#dcfce7':'#fee2e2'};color:${q.status==='Resolved'?'#15803d':'#b91c1c'};">${q.status}</span>
          </div>
        `).join('');
      } else {
        qBox.style.display = 'none';
      }
    });

  document.getElementById('labClearanceModal').style.display = 'flex';
}

async function submitLabClearance(action) {
  if (!currentLabClearance) return;
  const notes = document.getElementById('lab-clearance-notes').value.trim();
  const queryText = document.getElementById('lab-query-text').value.trim();

  if (action === 'query' && !queryText) {
    alert('Please enter query / pending test details.');
    return;
  }

  const payload = {
    action: 'update_clearance',
    clearance_id: currentLabClearance.clearance_id,
    admission_id: currentLabClearance.admission_id,
    department: 'lab',
    action: action,
    notes: notes,
    query_text: queryText
  };

  try {
    const res = await fetch('/GM_HMS/api/discharge_clearance.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) {
      alert(data.message || 'Laboratory clearance status updated!');
      closeLabClearanceModal();
      fetchLabNotifications();
    } else {
      alert('Error: ' + (data.message || 'Failed to update'));
    }
  } catch(err) {
    console.error('Error submitting lab clearance:', err);
  }
}

async function fetchLabNotifications() {
  const badgeElements = document.querySelectorAll('.lab-notif-badge');
  const list = document.getElementById('lab-notif-dropdown-list');
  try {
    const r = await fetch('/GM_HMS/api/discharge_clearance.php?action=pending_list&module=lab');
    const d = await r.json();

    if (d.success && Array.isArray(d.data) && d.data.length > 0) {
      badgeElements.forEach(el => {
        el.textContent = d.data.length;
        el.style.display = 'inline-block';
      });
      if (list) {
        list.innerHTML = d.data.map(item => `
          <div style="padding: 10px 14px; border-bottom: 1px solid var(--r-green-tint); text-align: left; background: ${item.lab_status==='Pending'?'#fdfbf7':'#ffffff'};">
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <strong style="font-size: 0.85rem; color: var(--r-txt);"><i class="fas fa-microscope" style="color:var(--r-green);"></i> ${item.patient_name || 'Patient'}</strong>
              <span style="font-size: 0.68rem; font-weight: 700; color: ${item.lab_status==='Approved'?'#15803d':'#b45309'};">${item.lab_status}</span>
            </div>
            <div style="font-size: 0.74rem; color: var(--r-txt-muted); margin: 2px 0;">${item.bed_info || 'Ward'} • IP: ${item.admission_id}</div>
            <button type="button" onclick='openLabClearanceModal(${JSON.stringify(item)})' style="margin-top: 4px; padding: 3px 8px; font-size: 0.72rem; font-weight: 700; background: var(--r-green); color: #fff; border: none; border-radius: 6px; cursor: pointer;">
              <i class="fas fa-clipboard-check"></i> Review & Clear
            </button>
          </div>
        `).join('');
      }
    } else {
      badgeElements.forEach(el => el.style.display = 'none');
      if (list) list.innerHTML = '<div class="p-4 text-center" style="font-size:0.8rem;color:var(--r-txt-muted);">No pending discharge clearances</div>';
    }
  } catch(e) {
    if (list) list.innerHTML = '<div class="p-4 text-center" style="font-size:0.8rem;color:var(--r-txt-muted);">Error loading alerts</div>';
  }
}

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

  document.addEventListener('DOMContentLoaded', () => {
    fetchLabNotifications();
    setInterval(fetchLabNotifications, 12000);
  });
})();
</script>
