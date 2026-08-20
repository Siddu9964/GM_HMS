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

<script>
let currentPhClearance = null;

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

async function submitPhClearance(action) {
  if (!currentPhClearance) return;
  const notes = document.getElementById('ph-clearance-notes').value.trim();
  const queryText = document.getElementById('ph-query-text').value.trim();

  if (action === 'query' && !queryText) {
    alert('Please enter query / medication issue details.');
    return;
  }

  const payload = {
    action: 'update_clearance',
    clearance_id: currentPhClearance.clearance_id,
    admission_id: currentPhClearance.admission_id,
    department: 'pharmacy',
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
      alert(data.message || 'Pharmacy clearance status updated!');
      closePhClearanceModal();
      fetchPharmacyNotifications();
    } else {
      alert('Error: ' + (data.message || 'Failed to update'));
    }
  } catch(err) {
    console.error('Error submitting pharmacy clearance:', err);
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
    } else {
      if (countBadge) countBadge.style.display = 'none';
      if (list) list.innerHTML = '<div class="text-center text-muted py-3" style="font-size:.82rem;">No pending discharge clearances</div>';
    }
  } catch(e) {
    if (list) list.innerHTML = '<div class="text-center text-muted py-3">Error loading</div>';
  }
}

document.addEventListener('DOMContentLoaded', () => {
  fetchPharmacyNotifications();
  setInterval(fetchPharmacyNotifications, 12000);
});
</script>
