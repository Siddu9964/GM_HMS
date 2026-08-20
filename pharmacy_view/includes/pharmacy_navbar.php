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
        data-bs-toggle="dropdown" aria-expanded="false" style="border-radius:10px; overflow:visible;">
        <i class="fas fa-bell"></i>
        <span id="ph-notif-count" class="badge-count" style="display:none;position:absolute;top:-5px;right:-5px;background:var(--ph-danger);color:#fff;font-size:.58rem;font-weight:800;padding:2px 5px;border-radius:99px;line-height:1;">0</span>
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

<!-- Pharmacy Discharge Clearance Action Modal -->
<div id="phClearanceModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 35, 25, 0.6); backdrop-filter: blur(4px); z-index: 99999; align-items: center; justify-content: center;">
  <div style="background: #ffffff; border-radius: 16px; max-width: 580px; width: 92%; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 60px rgba(0,0,0,0.35); border: 2px solid #1f6b4a;">
    <div style="background: #1f6b4a; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
      <div style="font-size: 1.05rem; font-weight: 800; color: #ffffff; display: flex; align-items: center; gap: 8px;">
        <i class="fas fa-pills"></i> Pharmacy Discharge Clearance
      </div>
      <button type="button" onclick="closePhClearanceModal()" style="background: none; border: none; font-size: 1.3rem; color: #ffffff; cursor: pointer;">&times;</button>
    </div>

    <div style="padding: 20px; overflow-y: auto; flex: 1; text-align: left;">
      <!-- Patient Info Box -->
      <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; margin-bottom: 14px;">
        <div style="font-weight: 800; font-size: 1.05rem; color: #1f6b4a;" id="ph-modal-pt-name">Patient Name</div>
        <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;" id="ph-modal-pt-details">PID: – | IP#: – | Bed: –</div>
      </div>

      <!-- Multi-Department Clearance Grid -->
      <div style="background: #fdfbf7; border: 1.5px dashed #cbd5e1; border-radius: 10px; padding: 12px; margin-bottom: 14px;">
        <div style="font-size: 0.72rem; font-weight: 800; color: #1f6b4a; text-transform: uppercase; margin-bottom: 8px;">Multi-Department Clearance Status</div>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
          <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px; text-align: center;">
            <div style="font-size: 0.68rem; font-weight: 700; color: #64748b;">Reception / Billing</div>
            <div id="ph-status-rec" style="font-weight: 800; font-size: 0.8rem; margin-top: 2px; color: #f59e0b;">⏳ Pending</div>
          </div>
          <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px; text-align: center;">
            <div style="font-size: 0.68rem; font-weight: 700; color: #64748b;">Pharmacy</div>
            <div id="ph-status-ph" style="font-weight: 800; font-size: 0.8rem; margin-top: 2px; color: #f59e0b;">⏳ Pending</div>
          </div>
          <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px; text-align: center;">
            <div style="font-size: 0.68rem; font-weight: 700; color: #64748b;">Laboratory</div>
            <div id="ph-status-lab" style="font-weight: 800; font-size: 0.8rem; margin-top: 2px; color: #f59e0b;">⏳ Pending</div>
          </div>
        </div>
      </div>

      <!-- Queries Box -->
      <div id="ph-modal-queries-box" style="display: none; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 8px; padding: 10px; margin-bottom: 14px; font-size: 0.8rem;">
        <div style="font-weight: 800; color: #e11d48; margin-bottom: 4px;"><i class="fas fa-comments"></i> Department Queries & Discussion:</div>
        <div id="ph-modal-queries-list"></div>
      </div>

      <!-- Clearance Actions -->
      <div style="border-top: 1px solid #e2e8f0; padding-top: 14px;">
        <div style="font-weight: 800; font-size: 0.85rem; color: #1e293b; margin-bottom: 8px;"><i class="fas fa-check-square text-success"></i> Option A: Approve Pharmacy Clearance</div>
        <div style="margin-bottom: 8px;">
          <input type="text" id="ph-clearance-notes" style="width: 100%; padding: 8px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.85rem;" placeholder="Optional notes e.g. All unreturned medications received & adjusted...">
        </div>
        <button type="button" onclick="submitPhClearance('approve')" style="width: 100%; padding: 9px; background: #1f6b4a; color: #ffffff; font-weight: 700; border: none; border-radius: 8px; cursor: pointer;">
          <i class="fas fa-check-circle mr-1"></i> Approve Pharmacy Clearance
        </button>
      </div>

      <div style="border-top: 1px dashed #e2e8f0; margin-top: 14px; padding-top: 12px;">
        <div style="font-weight: 800; font-size: 0.85rem; color: #dc2626; margin-bottom: 8px;"><i class="fas fa-exclamation-triangle text-danger"></i> Option B: Raise Query / Medicine Return Issue</div>
        <div style="margin-bottom: 8px;">
          <input type="text" id="ph-query-text" style="width: 100%; padding: 8px 12px; border: 1.5px solid #fca5a5; border-radius: 8px; font-size: 0.85rem;" placeholder="Specify issue e.g. Pending medication return: 2 vials Ceftriaxone...">
        </div>
        <button type="button" onclick="submitPhClearance('query')" style="width: 100%; padding: 8px; background: transparent; border: 1.5px solid #dc2626; color: #dc2626; font-weight: 700; border-radius: 8px; cursor: pointer;">
          <i class="fas fa-paper-plane mr-1"></i> Raise Query to Nurse & Admin
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Pharmacy Center Discharge Clearance Reminder Modal (Auto 5-min repeating popup) -->
<div id="phDischargeCenterModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 35, 25, 0.65); backdrop-filter: blur(5px); z-index: 99998; align-items: center; justify-content: center;">
  <div style="background: #ffffff; border-radius: 18px; max-width: 520px; width: 92%; overflow: hidden; box-shadow: 0 25px 70px rgba(0,0,0,0.4); border: 2.5px solid #d97706;">
    <div style="background: linear-gradient(135deg, #d97706, #b45309); padding: 16px 20px; color: #ffffff; display: flex; align-items: center; justify-content: space-between;">
      <div style="display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 1.05rem;">
        <i class="fas fa-pills" style="font-size: 1.3rem;"></i>
        <span>Action Required: Pharmacy Discharge Clearance</span>
      </div>
      <button type="button" onclick="snoozePhReminder()" style="background: none; border: none; color: #ffffff; font-size: 1.4rem; cursor: pointer; line-height: 1;">&times;</button>
    </div>

    <div style="padding: 20px; text-align: left;">
      <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px; background: #fffbeb; border: 1.5px solid #fde68a; padding: 12px 14px; border-radius: 12px;">
        <div style="width: 42px; height: 42px; border-radius: 10px; background: #d97706; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div style="font-size: 0.84rem; color: #92400e; line-height: 1.45;">
          <strong>Discharge clearance initiated by Nursing Station.</strong><br>
          Please verify medicine returns, unbilled medicines, or raise an issue query.
        </div>
      </div>

      <div id="ph-reminder-patient-list" style="max-height: 220px; overflow-y: auto; margin-bottom: 14px;"></div>

      <p style="margin: 0 0 14px 0; font-size: 0.76rem; color: #64748b; line-height: 1.4;">
        <i class="fas fa-clock"></i> This alert will pop up every 5 minutes until pharmacy clearance feedback is submitted.
      </p>

      <div style="display: flex; gap: 10px; justify-content: flex-end;">
        <button type="button" onclick="snoozePhReminder()" style="padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 0.82rem; border: 1.5px solid #cbd5e1; background: #f8fafc; color: #475569; cursor: pointer;">
          <i class="fas fa-clock"></i> Remind in 5 Min
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Universal Center Feedback / Success Popup Modal -->
<div id="centerFeedbackModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(5px); z-index: 100000; align-items: center; justify-content: center;">
  <div style="background: #ffffff; border-radius: 20px; max-width: 440px; width: 90%; overflow: hidden; box-shadow: 0 25px 70px rgba(0,0,0,0.35); text-align: center; border: 1.5px solid #e2e8f0;">
    <div id="center-feedback-header" style="background: #1f6b4a; padding: 22px 20px 16px; color: #ffffff;">
      <div id="center-feedback-icon" style="width: 52px; height: 52px; border-radius: 50%; background: rgba(255,255,255,0.22); display: inline-flex; align-items: center; justify-content: center; font-size: 1.6rem; margin-bottom: 8px;">
        <i class="fas fa-check"></i>
      </div>
      <div id="center-feedback-title" style="font-size: 1.15rem; font-weight: 800;">Clearance Updated</div>
    </div>
    <div style="padding: 22px 24px;">
      <p id="center-feedback-msg" style="font-size: 0.92rem; color: #334155; line-height: 1.5; margin: 0 0 20px 0; font-weight: 600;">
        Clearance status updated successfully.
      </p>
      <button type="button" id="center-feedback-btn" onclick="closeCenterFeedbackModal()" style="padding: 10px 32px; background: #1f6b4a; color: #ffffff; font-weight: 800; font-size: 0.88rem; border: none; border-radius: 10px; cursor: pointer; min-width: 120px; box-shadow: 0 4px 14px rgba(31,107,74,0.3);">
        OK
      </button>
    </div>
  </div>
</div>

<script>
let currentPhClearance = null;
let phReminderSnoozedUntil = 0;
const PH_REMINDER_INTERVAL_MS = 5 * 60 * 1000; // 5 minutes
let centerFeedbackTimer = null;

function showCenterFeedback(msg, type = 'success', title = '') {
  const modal = document.getElementById('centerFeedbackModal');
  if (!modal) {
    alert(msg);
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
    titleEl.textContent = title || 'Clearance Approved';
    if (btn) btn.style.background = '#1f6b4a';
  } else if (type === 'error') {
    header.style.background = '#dc2626';
    icon.innerHTML = '<i class="fas fa-times"></i>';
    titleEl.textContent = title || 'Update Failed';
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
  centerFeedbackTimer = setTimeout(() => {
    closeCenterFeedbackModal();
  }, 6000);
}

function closeCenterFeedbackModal() {
  const modal = document.getElementById('centerFeedbackModal');
  if (modal) modal.style.display = 'none';
  if (centerFeedbackTimer) clearTimeout(centerFeedbackTimer);
}

function closePhClearanceModal() {
  document.getElementById('phClearanceModal').style.display = 'none';
}

function openPhClearanceModal(item) {
  currentPhClearance = item;
  document.getElementById('ph-modal-pt-name').textContent = item.patient_name || 'Patient';
  document.getElementById('ph-modal-pt-details').textContent = `PID: ${item.patient_id} | IP#: ${item.admission_id} | Location: ${item.bed_info || 'Ward'} | Doctor: Dr. ${item.doctor_name || 'Consultant'}`;

  const setStatus = (elId, status) => {
    const el = document.getElementById(elId);
    if (!el) return;
    if (status === 'Approved') el.innerHTML = `<span style="color:#16a34a;"><i class="fas fa-check-circle"></i> Cleared</span>`;
    else if (status === 'Query') el.innerHTML = `<span style="color:#dc2626;"><i class="fas fa-exclamation-triangle"></i> Query</span>`;
    else el.innerHTML = `<span style="color:#f59e0b;"><i class="fas fa-clock"></i> Pending</span>`;
  };

  setStatus('ph-status-rec', item.reception_status);
  setStatus('ph-status-ph', item.pharmacy_status);
  setStatus('ph-status-lab', item.lab_status);

  document.getElementById('ph-clearance-notes').value = '';
  document.getElementById('ph-query-text').value = '';

  fetch(`/GM_HMS/api/discharge_clearance.php?action=status&admission_id=${encodeURIComponent(item.admission_id)}`)
    .then(r => r.json())
    .then(res => {
      const qBox = document.getElementById('ph-modal-queries-box');
      const qList = document.getElementById('ph-modal-queries-list');
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

  document.getElementById('phClearanceModal').style.display = 'flex';
}

function openPhClearanceFromReminder(item) {
  const centerModal = document.getElementById('phDischargeCenterModal');
  if (centerModal) centerModal.style.display = 'none';
  openPhClearanceModal(item);
}

function snoozePhReminder() {
  const centerModal = document.getElementById('phDischargeCenterModal');
  if (centerModal) centerModal.style.display = 'none';
  phReminderSnoozedUntil = Date.now() + PH_REMINDER_INTERVAL_MS;
}

function checkAndShowPhDischargeReminder(items) {
  const pendingForPh = (items || []).filter(item => item.pharmacy_status === 'Pending');
  const centerModal = document.getElementById('phDischargeCenterModal');
  if (!centerModal) return;

  if (pendingForPh.length > 0) {
    const now = Date.now();
    if (now >= phReminderSnoozedUntil) {
      const listEl = document.getElementById('ph-reminder-patient-list');
      if (listEl) {
        listEl.innerHTML = pendingForPh.map(item => `
          <div style="background:#ffffff; border:1.5px solid #fed7aa; border-radius:10px; padding:10px 12px; margin-bottom:8px; display:flex; justify-content:space-between; align-items:center; gap:8px;">
            <div>
              <div style="font-weight:800; font-size:0.92rem; color:#1e293b;"><i class="fas fa-user-injured text-warning"></i> ${item.patient_name || 'Patient'}</div>
              <div style="font-size:0.74rem; color:#64748b; margin-top:2px;">
                ${item.bed_info || 'Ward'} • IP: <strong>${item.admission_id}</strong>
              </div>
            </div>
            <button type="button" onclick='openPhClearanceFromReminder(${JSON.stringify(item)})' style="padding:6px 12px; font-size:0.76rem; font-weight:800; background:#1f6b4a; color:#ffffff; border:none; border-radius:8px; cursor:pointer; white-space:nowrap; display:inline-flex; align-items:center; gap:4px;">
              <i class="fas fa-clipboard-check"></i> Review & Clear
            </button>
          </div>
        `).join('');
      }
      centerModal.style.display = 'flex';
    }
  } else {
    centerModal.style.display = 'none';
  }
}

async function submitPhClearance(action) {
  if (!currentPhClearance) return;
  const notes = document.getElementById('ph-clearance-notes').value.trim();
  const queryText = document.getElementById('ph-query-text').value.trim();

  if (action === 'query' && !queryText) {
    showCenterFeedback('Please enter query / medication issue details before submitting.', 'warning', 'Query Details Required');
    return;
  }

  const payload = {
    action: 'update_clearance',
    status_action: action,
    clearance_action: action,
    clearance_id: currentPhClearance.clearance_id,
    admission_id: currentPhClearance.admission_id,
    department: 'pharmacy',
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
      closePhClearanceModal();
      snoozePhReminder();
      showCenterFeedback(data.message || 'Pharmacy clearance approved successfully!', 'success', action === 'query' ? 'Query Submitted' : 'Clearance Approved');
      fetchPharmacyNotifications();
    } else {
      showCenterFeedback(data.message || 'Failed to update pharmacy clearance.', 'error', 'Error');
    }
  } catch(err) {
    console.error('Error submitting pharmacy clearance:', err);
    showCenterFeedback('Network error while updating pharmacy clearance.', 'error', 'Network Error');
  }
}

async function fetchPharmacyNotifications() {
  const countBadge = document.getElementById('ph-notif-count');
  const list = document.getElementById('ph-notif-list');
  try {
    const r = await fetch('/GM_HMS/api/discharge_clearance.php?action=pending_list&module=pharmacy');
    const d = await r.json();

    if (d.success && Array.isArray(d.data) && d.data.length > 0) {
      if (countBadge) {
        countBadge.textContent = d.data.length;
        countBadge.style.display = 'inline-block';
      }
      if (list) {
        list.innerHTML = d.data.map(item => `
          <div style="padding: .6rem; border-radius: 8px; margin-bottom: .35rem; border: 1px solid ${item.pharmacy_status==='Pending'?'#fde68a':'#dcfce7'}; background: ${item.pharmacy_status==='Pending'?'#fffbeb':'#f0fdf4'};">
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <strong style="font-size: .8rem; color: #1e293b;"><i class="fas fa-pills text-warning"></i> ${item.patient_name || 'Patient'}</strong>
              <span style="font-size: .65rem; font-weight: 700; color: ${item.pharmacy_status==='Approved'?'#15803d':'#b45309'};">${item.pharmacy_status}</span>
            </div>
            <div style="font-size: .72rem; color: #64748b; margin: 2px 0;">${item.bed_info || 'Ward'} • IP: ${item.admission_id}</div>
            <button type="button" onclick='openPhClearanceModal(${JSON.stringify(item)})' style="margin-top: 4px; padding: 3px 8px; font-size: .7rem; font-weight: 700; background: #1f6b4a; color: #fff; border: none; border-radius: 6px; cursor: pointer;">
              <i class="fas fa-clipboard-check"></i> Review & Clear
            </button>
          </div>
        `).join('');
      }
      checkAndShowPhDischargeReminder(d.data);
    } else {
      if (countBadge) countBadge.style.display = 'none';
      if (list) list.innerHTML = '<div class="text-center text-muted py-3" style="font-size:.82rem;">No pending discharge clearances</div>';
      checkAndShowPhDischargeReminder([]);
    }
  } catch(e) {
    if (list) list.innerHTML = '<div class="text-center text-muted py-3">Error loading</div>';
  }
}

document.addEventListener('DOMContentLoaded', () => {
  fetchPharmacyNotifications();
  setInterval(fetchPharmacyNotifications, 10000);
});
</script>
