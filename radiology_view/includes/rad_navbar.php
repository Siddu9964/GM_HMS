<?php
/**
 * RIS Navbar — rad_navbar.php
 * Top sticky navigation bar for Radiology Information System.
 */
$navTitle  = $navTitle  ?? ($pageTitle ?? 'Radiology');
$navSub    = $navSub    ?? 'Radiology Information System';
$userInit  = strtoupper(substr($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'R', 0, 2));
$userName  = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Radiologist');
$userRole  = htmlspecialchars($_SESSION['role'] ?? 'Radiologist');
$pageIcon  = $pageIcon  ?? 'fa-x-ray';
?>
<nav class="lis-navbar">

  <!-- Hamburger (mobile/tablet only) -->
  <button id="lis-hamburger" onclick="lisToggleSidebar()" aria-label="Toggle navigation">
    <i class="fas fa-bars"></i>
  </button>

  <!-- Page Title + Icon -->
  <div class="lis-navbar-brand">
    <div style="width:34px;height:34px;background:#1f6b4a;border-radius:9px;display:flex;align-items:center;justify-content:center;color:#f3efe6;font-size:0.85rem;flex-shrink:0;">
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
    <input type="text" id="lis-global-search" placeholder="Search scans, patients..." autocomplete="off">
    <span class="lis-search-kbd">⌘K</span>
  </div>

  <div class="lis-navbar-spacer"></div>

  <!-- Live Clock -->
  <div class="lis-live-clock" id="lis-clock-pill">
    <span class="lis-live-dot"></span>
    <span id="lis-clock">--:-- --</span>
  </div>

  <!-- Date pill -->
  <div id="lis-date-pill" style="font-size:0.72rem;font-weight:700;color:#1f6b4a;background:#f3efe6;padding:5px 12px;border-radius:20px;border:1px solid #1f6b4a;white-space:nowrap;">
    <i class="fas fa-calendar-alt" style="margin-right:5px;"></i>
    <span id="lis-date">--</span>
  </div>

  <!-- Notification Bell -->
  <div class="dropdown" style="position: relative;" id="rad-notif-dropdown-wrapper">
    <a href="javascript:void(0)" onclick="toggleRadNotifications(event)" class="lis-notif-btn" title="Discharge Notifications" style="position: relative; display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 10px; background: #f3efe6; border: 1.5px solid #1f6b4a; color: #1f6b4a; text-decoration: none; cursor: pointer;">
      <i class="fas fa-bell" style="font-size: 1.15rem;"></i>
      <span class="lis-notif-badge rad-notif-badge" id="navbar-notif-badge" style="display:none; position: absolute; top: -5px; right: -5px; background: #1f6b4a; color: #f3efe6; font-size: 0.65rem; font-weight: 800; border-radius: 10px; padding: 2px 6px; min-width: 18px; text-align: center; border: 2px solid #f3efe6; box-shadow: 0 2px 6px rgba(31,107,74,0.4);">0</span>
    </a>
    <div id="radNotificationsDropdown" class="dropdown-menu dropdown-menu-end shadow-sm" style="display: none; position: absolute; top: 115%; right: 0; min-width: 340px; max-width: 380px; border-radius: 12px; border: 1.5px solid #1f6b4a; padding: 0; max-height: 420px; overflow-y: auto; background: #ffffff; z-index: 10000; box-shadow: 0 15px 40px rgba(0,0,0,0.18);">
      <div style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-weight: 800; color: #1e293b; display: flex; justify-content: space-between; align-items: center; background: #fdfbf7;">
          <span style="display:flex;align-items:center;gap:6px;"><i class="fas fa-x-ray" style="color:#1f6b4a;"></i> Discharge Clearance Alerts</span>
          <span class="badge rounded-pill rad-notif-badge" style="display:none;background:#1f6b4a;color:#ffffff;font-size:0.7rem;padding:3px 8px;">0</span>
      </div>
      <div id="rad-notif-dropdown-list">
          <div class="p-4 text-center" style="font-size:0.8rem;color:#64748b;">Loading...</div>
      </div>
    </div>
  </div>

  <!-- User Dropdown -->
  <div class="dropdown">
    <button class="lis-user-chip border-0" data-bs-toggle="dropdown" aria-expanded="false">
      <div class="lis-user-avatar" style="background: #1f6b4a; color: #f3efe6;">
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
      <i class="fas fa-chevron-down" style="font-size:0.6rem;color:var(--lis-text-muted);margin-left:2px;"></i>
    </button>

    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width:200px;border-radius:12px;border:1px solid var(--lis-border);margin-top:8px;padding:6px;">
      <li>
        <div style="padding:10px 14px 6px;">
          <div style="font-size:0.82rem;font-weight:800;color:var(--lis-text);"><?= $userName ?></div>
          <div style="font-size:0.68rem;color:var(--lis-text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.05em;"><?= $userRole ?></div>
        </div>
      </li>
      <li><hr class="dropdown-divider my-1"></li>
      <li>
        <a class="dropdown-item py-2" href="javascript:void(0)" onclick="openProfileModal('profile')" style="font-size:0.82rem;border-radius:8px;">
          <i class="fas fa-user-circle me-2" style="color:var(--lis-primary);width:16px;"></i>My Profile
        </a>
      </li>
      <li>
        <a class="dropdown-item py-2" href="javascript:void(0)" onclick="openProfileModal('security')" style="font-size:0.82rem;border-radius:8px;">
          <i class="fas fa-key me-2" style="color:var(--lis-text-muted);width:16px;"></i>Change Password
        </a>
      </li>
      <li><hr class="dropdown-divider my-1"></li>
      <li>
        <a class="dropdown-item py-2" href="/GM_HMS/logout.php" onclick="return radConfirmLogout(event)" style="font-size:0.82rem;border-radius:8px;color:#c0392b;">
          <i class="fas fa-sign-out-alt me-2" style="width:16px;"></i>Logout
        </a>
      </li>
    </ul>
  </div>
</nav>

<!-- Radiology Discharge Clearance Action Modal -->
<div id="radClearanceModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 99999; align-items: center; justify-content: center;">
  <div style="background: #ffffff; border-radius: 16px; max-width: 580px; width: 92%; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 60px rgba(0,0,0,0.35); border: 2px solid #1f6b4a;">
    <div style="background: #1f6b4a; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
      <div style="font-size: 1.05rem; font-weight: 800; color: #f3efe6; display: flex; align-items: center; gap: 8px;">
        <i class="fas fa-x-ray"></i> Radiology Discharge Clearance
      </div>
      <button type="button" onclick="closeRadClearanceModal()" style="background: none; border: none; font-size: 1.3rem; color: #f3efe6; cursor: pointer;">&times;</button>
    </div>

    <div style="padding: 20px; overflow-y: auto; flex: 1; text-align: left;">
      <!-- Patient Info Box -->
      <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; margin-bottom: 14px;">
        <div style="font-weight: 800; font-size: 1.05rem; color: #1f6b4a;" id="rad-modal-pt-name">Patient Name</div>
        <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;" id="rad-modal-pt-details">PID: – | IP#: – | Bed: –</div>
      </div>

      <!-- Multi-Department Clearance Grid -->
      <div style="background: #fdfbf7; border: 1.5px dashed #cbd5e1; border-radius: 10px; padding: 12px; margin-bottom: 14px;">
        <div style="font-size: 0.72rem; font-weight: 800; color: #1f6b4a; text-transform: uppercase; margin-bottom: 8px;">Department Clearance Status</div>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;">
          <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px; text-align: center;">
            <div style="font-size: 0.65rem; font-weight: 700; color: #64748b;">Reception</div>
            <div id="rad-status-rec" style="font-weight: 800; font-size: 0.78rem; margin-top: 2px; color: #f59e0b;">Pending</div>
          </div>
          <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px; text-align: center;">
            <div style="font-size: 0.65rem; font-weight: 700; color: #64748b;">Pharmacy</div>
            <div id="rad-status-ph" style="font-weight: 800; font-size: 0.78rem; margin-top: 2px; color: #f59e0b;">Pending</div>
          </div>
          <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px; text-align: center;">
            <div style="font-size: 0.65rem; font-weight: 700; color: #64748b;">Laboratory</div>
            <div id="rad-status-lab" style="font-weight: 800; font-size: 0.78rem; margin-top: 2px; color: #f59e0b;">Pending</div>
          </div>
          <div style="background: #f0fdf4; border: 1.5px solid #1F6B4A; border-radius: 8px; padding: 8px; text-align: center;">
            <div style="font-size: 0.65rem; font-weight: 800; color: #1F6B4A;">Radiology</div>
            <div id="rad-status-rad" style="font-weight: 800; font-size: 0.78rem; margin-top: 2px; color: #f59e0b;">Pending</div>
          </div>
        </div>
      </div>

      <!-- Queries Box -->
      <div id="rad-modal-queries-box" style="display: none; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 8px; padding: 10px; margin-bottom: 14px; font-size: 0.8rem;">
        <div style="font-weight: 800; color: #e11d48; margin-bottom: 4px;"><i class="fas fa-comments"></i> Department Queries & Discussion:</div>
        <div id="rad-modal-queries-list"></div>
      </div>

      <!-- Clearance Actions -->
      <div style="border-top: 1px solid #e2e8f0; padding-top: 14px;">
        <div style="font-weight: 800; font-size: 0.85rem; color: #1e293b; margin-bottom: 8px;"><i class="fas fa-check-square text-success"></i> Option A: Approve Radiology Clearance</div>
        <div style="margin-bottom: 8px;">
          <input type="text" id="rad-clearance-notes" style="width: 100%; padding: 8px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.85rem;" placeholder="Optional notes e.g. All ordered scans/reports verified...">
        </div>
        <button type="button" onclick="submitRadClearance('approve')" style="width: 100%; padding: 9px; background: #1f6b4a; color: #f3efe6; font-weight: 700; border: none; border-radius: 8px; cursor: pointer;">
          <i class="fas fa-check-circle mr-1"></i> Approve Radiology Clearance
        </button>
      </div>

      <div style="border-top: 1px dashed #e2e8f0; margin-top: 14px; padding-top: 12px;">
        <div style="font-weight: 800; font-size: 0.85rem; color: #dc2626; margin-bottom: 8px;"><i class="fas fa-exclamation-triangle text-danger"></i> Option B: Raise Query / Pending Scan Issue</div>
        <div style="margin-bottom: 8px;">
          <input type="text" id="rad-query-text" style="width: 100%; padding: 8px 12px; border: 1.5px solid #fca5a5; border-radius: 8px; font-size: 0.85rem;" placeholder="Specify issue e.g. CT chest report pending sign-off...">
        </div>
        <button type="button" onclick="submitRadClearance('query')" style="width: 100%; padding: 8px; background: transparent; border: 1.5px solid #dc2626; color: #dc2626; font-weight: 700; border-radius: 8px; cursor: pointer;">
          <i class="fas fa-paper-plane mr-1"></i> Raise Query to Nurse & Admin
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Universal Center Feedback / Success Popup Modal -->
<div id="centerFeedbackModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(5px); z-index: 100000; align-items: center; justify-content: center;">
  <div style="background: #ffffff; border-radius: 20px; max-width: 440px; width: 90%; overflow: hidden; box-shadow: 0 25px 70px rgba(0,0,0,0.35); text-align: center; border: 1.5px solid #e2e8f0; animation: lisModalPop 0.25s cubic-bezier(0.16, 1, 0.3, 1);">
    <div id="center-feedback-header" style="background: #1f6b4a; padding: 22px 20px 16px; color: #f3efe6;">
      <div id="center-feedback-icon" style="width: 54px; height: 54px; border-radius: 50%; background: rgba(255,255,255,0.25); display: inline-flex; align-items: center; justify-content: center; font-size: 1.6rem; margin-bottom: 8px;">
        <i class="fas fa-check"></i>
      </div>
      <div id="center-feedback-title" style="font-size: 1.15rem; font-weight: 800;">Action Completed</div>
    </div>
    <div style="padding: 24px;">
      <p id="center-feedback-msg" style="font-size: 0.92rem; color: #334155; line-height: 1.5; margin: 0 0 20px 0; font-weight: 600;">
        Action executed successfully.
      </p>
      <button type="button" id="center-feedback-btn" onclick="closeCenterFeedbackModal()" style="padding: 10px 34px; background: #1f6b4a; color: #f3efe6; font-weight: 800; font-size: 0.88rem; border: none; border-radius: 10px; cursor: pointer; min-width: 120px; box-shadow: 0 4px 14px rgba(31,107,74,0.3);">
        OK
      </button>
    </div>
  </div>
</div>

<script>
let centerFeedbackTimer = null;
let currentRadClearance = null;
window.radClearanceData = [];

function showCenterFeedback(msg, type = 'success', title = '') {
  const modal = document.getElementById('centerFeedbackModal');
  if (!modal) {
    if (typeof radToast === 'function') {
      radToast(msg, type, title);
    } else {
      alert(msg);
    }
    return;
  }
  const header = document.getElementById('center-feedback-header');
  const icon = document.getElementById('center-feedback-icon');
  const titleEl = document.getElementById('center-feedback-title');
  const msgEl = document.getElementById('center-feedback-msg');
  const btn = document.getElementById('center-feedback-btn');

  if (type === 'success') {
    header.style.background = '#1f6b4a';
    icon.innerHTML = '<i class="fas fa-check"></i>';
    titleEl.textContent = title || 'Success';
    if (btn) btn.style.background = '#1f6b4a';
  } else if (type === 'error') {
    header.style.background = '#dc2626';
    icon.innerHTML = '<i class="fas fa-times"></i>';
    titleEl.textContent = title || 'Error Occurred';
    if (btn) btn.style.background = '#dc2626';
  } else {
    header.style.background = '#d97706';
    icon.innerHTML = '<i class="fas fa-exclamation"></i>';
    titleEl.textContent = title || 'Attention';
    if (btn) btn.style.background = '#d97706';
  }

  const cleanMsg = (msg || '').replace(/^[✅❌⚠️\s]+/, '');
  msgEl.textContent = cleanMsg;

  modal.style.display = 'flex';

  if (centerFeedbackTimer) clearTimeout(centerFeedbackTimer);
  if (type !== 'error') {
    centerFeedbackTimer = setTimeout(() => {
      closeCenterFeedbackModal();
    }, 3500);
  }
}

function closeCenterFeedbackModal() {
  const modal = document.getElementById('centerFeedbackModal');
  if (modal) modal.style.display = 'none';
  if (centerFeedbackTimer) clearTimeout(centerFeedbackTimer);
}

function renderRadNotificationsList() {
  const list = document.getElementById('rad-notif-dropdown-list');
  if (!list) return;

  const items = window.radClearanceData || [];
  if (!Array.isArray(items) || items.length === 0) {
    list.innerHTML = '<div class="p-4 text-center text-muted" style="font-size:0.82rem;"><i class="fas fa-check-circle text-success me-1"></i> No pending discharge clearances</div>';
    return;
  }

  list.innerHTML = items.map((item, idx) => {
    const isApproved = item.radiology_status === 'Approved' || item.overall_status === 'All Cleared';
    const isQuery = item.radiology_status === 'Query';
    const statusColor = isApproved ? '#15803d' : (isQuery ? '#dc2626' : '#b45309');
    const statusBg = isApproved ? '#dcfce7' : (isQuery ? '#fee2e2' : '#fef3c7');
    const displayStatus = isApproved ? 'Cleared' : (isQuery ? 'Query Raised' : 'Pending');

    return `
      <div style="padding: 12px 14px; border-bottom: 1px solid #e2e8f0; text-align: left; background: #ffffff; transition: background 0.15s;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 4px;">
          <strong style="font-size: 0.88rem; color: #1e293b;">
            <i class="fas fa-user-injured" style="color:#1f6b4a;margin-right:4px;"></i> ${item.patient_name || 'Patient'}
          </strong>
          <span style="font-size: 0.68rem; font-weight: 800; color: ${statusColor}; background: ${statusBg}; padding: 2px 8px; border-radius: 6px;">
            ${displayStatus}
          </span>
        </div>
        <div style="font-size: 0.74rem; color: #64748b; margin: 2px 0 6px 0;">
          ${item.bed_info || 'Ward'} • IP: <strong>${item.admission_id}</strong>
        </div>
        <button type="button" onclick="openRadClearanceModalByIndex(${idx})" style="padding: 5px 12px; font-size: 0.74rem; font-weight: 700; background: #1f6b4a; color: #f3efe6; border: none; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
          <i class="fas fa-clipboard-check"></i> Review & Clear
        </button>
      </div>
    `;
  }).join('');
}

function closeRadClearanceModal() {
  const m = document.getElementById('radClearanceModal');
  if (m) m.style.display = 'none';
}

function openRadClearanceModalByIndex(idx) {
  const item = (window.radClearanceData || [])[idx];
  if (!item) return;
  currentRadClearance = item;

  document.getElementById('rad-modal-pt-name').textContent = item.patient_name || 'Patient';
  document.getElementById('rad-modal-pt-details').textContent = `PID: ${item.patient_id} | IP#: ${item.admission_id} | Location: ${item.bed_info || 'Ward'} | Doctor: Dr. ${item.doctor_name || 'Consultant'}`;

  const setStatus = (elId, status) => {
    const el = document.getElementById(elId);
    if (!el) return;
    if (status === 'Approved') el.innerHTML = `<span style="color:#16a34a;"><i class="fas fa-check-circle"></i> Cleared</span>`;
    else if (status === 'Query') el.innerHTML = `<span style="color:#dc2626;"><i class="fas fa-exclamation-triangle"></i> Query</span>`;
    else el.innerHTML = `<span style="color:#f59e0b;"><i class="fas fa-clock"></i> Pending</span>`;
  };

  setStatus('rad-status-rec', item.reception_status);
  setStatus('rad-status-ph', item.pharmacy_status);
  setStatus('rad-status-lab', item.lab_status);
  setStatus('rad-status-rad', item.radiology_status);

  document.getElementById('rad-clearance-notes').value = '';
  document.getElementById('rad-query-text').value = '';

  fetch(`/GM_HMS/api/discharge_clearance.php?action=status&admission_id=${encodeURIComponent(item.admission_id)}`)
    .then(r => r.json())
    .then(res => {
      const qBox = document.getElementById('rad-modal-queries-box');
      const qList = document.getElementById('rad-modal-queries-list');
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
    }).catch(() => {});

  document.getElementById('radClearanceModal').style.display = 'flex';
}

async function submitRadClearance(action) {
  if (!currentRadClearance) return;
  const notes = document.getElementById('rad-clearance-notes').value.trim();
  const queryText = document.getElementById('rad-query-text').value.trim();

  if (action === 'query' && !queryText) {
    showCenterFeedback('Please enter query / pending scan details before submitting.', 'warning', 'Query Details Required');
    return;
  }

  const payload = {
    action: 'update_clearance',
    status_action: action,
    clearance_action: action,
    clearance_id: currentRadClearance.clearance_id,
    admission_id: currentRadClearance.admission_id,
    department: 'radiology',
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
      closeRadClearanceModal();
      showCenterFeedback(data.message || 'Radiology clearance updated successfully!', 'success', action === 'query' ? 'Query Submitted' : 'Clearance Approved');
      fetchRadNotifications();
    } else {
      showCenterFeedback(data.message || 'Failed to update clearance.', 'error', 'Error');
    }
  } catch(err) {
    console.error('Error submitting radiology clearance:', err);
    showCenterFeedback('Network error while updating clearance.', 'error', 'Network Error');
  }
}

function toggleRadNotifications(e) {
  if (e) e.stopPropagation();
  const dropdown = document.getElementById('radNotificationsDropdown');
  if (dropdown) {
    const isShown = dropdown.style.display === 'block';
    if (!isShown) {
      renderRadNotificationsList();
      dropdown.style.display = 'block';
      fetchRadNotifications();
    } else {
      dropdown.style.display = 'none';
    }
  }
}

document.addEventListener('click', function(e) {
  const wrapper = document.getElementById('rad-notif-dropdown-wrapper');
  const dropdown = document.getElementById('radNotificationsDropdown');
  if (wrapper && dropdown && !wrapper.contains(e.target)) {
    dropdown.style.display = 'none';
  }
});

async function fetchRadNotifications() {
  const badgeElements = document.querySelectorAll('.rad-notif-badge');
  const list = document.getElementById('rad-notif-dropdown-list');
  try {
    const r = await fetch('/GM_HMS/api/discharge_clearance.php?action=pending_list&module=radiology');
    const d = await r.json();

    if (d.success && Array.isArray(d.data)) {
      window.radClearanceData = d.data;
      if (d.data.length > 0) {
        badgeElements.forEach(el => {
          el.textContent = d.data.length;
          el.style.display = 'inline-block';
        });
      } else {
        badgeElements.forEach(el => el.style.display = 'none');
      }
      renderRadNotificationsList();
    } else {
      window.radClearanceData = [];
      badgeElements.forEach(el => el.style.display = 'none');
      renderRadNotificationsList();
    }
  } catch(e) {
    console.error('Error fetching radiology discharge alerts:', e);
    if (list && (!window.radClearanceData || window.radClearanceData.length === 0)) {
      list.innerHTML = '<div class="p-4 text-center text-muted" style="font-size:0.8rem;"><i class="fas fa-exclamation-circle text-warning me-1"></i> Unable to load alerts</div>';
    }
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

  document.addEventListener('keydown', e => {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
      e.preventDefault();
      document.getElementById('lis-global-search')?.focus();
    }
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      fetchRadNotifications();
      setInterval(fetchRadNotifications, 10000);
    });
  } else {
    fetchRadNotifications();
    setInterval(fetchRadNotifications, 10000);
  }
})();
</script>
