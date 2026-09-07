<?php
$pageTitle = 'Notifications Center';
$pageIcon  = 'fa-bell';
$navTitle  = 'Radiology Notifications';
$navSub    = 'Emergency scan alerts, radiologist report reviews, and equipment notices';
require_once 'includes/rad_head.php';
?>
<?php require_once 'includes/rad_sidebar.php'; ?>

<div class="lis-main-content">
<?php require_once 'includes/rad_navbar.php'; ?>

<div class="lis-content">

  <!-- Page Header -->
  <div class="lis-page-header lis-fade-up">
    <div>
      <div class="lis-page-title">
        <div class="lis-page-title-icon" style="background:linear-gradient(135deg,var(--lis-primary),#0284c7);color:white;border:none;">
          <i class="fas fa-bell"></i>
        </div>
        <div>
          Notification Center
          <div class="lis-page-subtitle">Real-time alerts for STAT imaging, clinical clearance, and equipment health</div>
        </div>
      </div>
    </div>
    <div style="display:flex;gap:10px;">
      <button class="lis-btn lis-btn-outline" onclick="markAllRead()">
        <i class="fas fa-check-double"></i> Mark All Read
      </button>
      <button class="lis-btn lis-btn-outline" onclick="loadNotifications()">
        <i class="fas fa-sync-alt"></i>
      </button>
    </div>
  </div>

  <div class="lis-grid-3-1 lis-fade-up-1">

    <!-- Left: Notification Feed -->
    <div>
      <!-- Tab Pills -->
      <div style="display:flex;gap:4px;margin-bottom:16px;" class="lis-tab-pills">
        <button class="lis-tab-pill active" data-tab="all" onclick="switchNotifTab('all',this)"><i class="fas fa-list"></i> All</button>
        <button class="lis-tab-pill" data-tab="critical" onclick="switchNotifTab('critical',this)"><i class="fas fa-exclamation-triangle"></i> STAT & Critical</button>
        <button class="lis-tab-pill" data-tab="completed" onclick="switchNotifTab('completed',this)"><i class="fas fa-check"></i> Completed</button>
        <button class="lis-tab-pill" data-tab="pending" onclick="switchNotifTab('pending',this)"><i class="fas fa-clock"></i> Pending Review</button>
      </div>

      <div class="lis-card" style="overflow:hidden;">
        <div id="notif-feed" style="display:flex;flex-direction:column;gap:1px;background:#e2e8f0;">
          <!-- Dynamically Injected -->
        </div>
      </div>
    </div>

    <!-- Right: Summary Panel -->
    <div style="display:flex;flex-direction:column;gap:16px;">

      <!-- Stats -->
      <div class="lis-card">
        <div class="lis-card-header">
          <div class="lis-card-title"><i class="fas fa-chart-pie"></i> Alert Summary</div>
        </div>
        <div class="lis-card-body" style="display:flex;flex-direction:column;gap:12px;">
          <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:var(--lis-danger-bg);border-radius:10px;border:1px solid #fca5a5;">
            <div>
              <div style="font-size:0.68rem;font-weight:700;color:var(--lis-danger);text-transform:uppercase;">Critical / STAT</div>
              <div class="lis-kpi-value" id="notif-cnt-critical" style="font-size:1.4rem;color:var(--lis-danger);">2</div>
            </div>
            <i class="fas fa-bolt" style="font-size:1.5rem;color:var(--lis-danger);opacity:0.3;"></i>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:var(--lis-success-bg);border-radius:10px;border:1px solid #6ee7b7;">
            <div>
              <div style="font-size:0.68rem;font-weight:700;color:var(--lis-success);text-transform:uppercase;">Completed Scans</div>
              <div class="lis-kpi-value" id="notif-cnt-completed" style="font-size:1.4rem;color:var(--lis-success);">18</div>
            </div>
            <i class="fas fa-check-circle" style="font-size:1.5rem;color:var(--lis-success);opacity:0.3;"></i>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:var(--lis-warning-bg);border-radius:10px;border:1px solid #fde68a;">
            <div>
              <div style="font-size:0.68rem;font-weight:700;color:var(--lis-warning);text-transform:uppercase;">Pending Reporting</div>
              <div class="lis-kpi-value" id="notif-cnt-pending" style="font-size:1.4rem;color:var(--lis-warning);">5</div>
            </div>
            <i class="fas fa-clock" style="font-size:1.5rem;color:var(--lis-warning);opacity:0.3;"></i>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="lis-card">
        <div class="lis-card-header">
          <div class="lis-card-title"><i class="fas fa-link"></i> Quick Actions</div>
        </div>
        <div class="lis-card-body" style="display:flex;flex-direction:column;gap:8px;">
          <a href="test_orders.php" class="lis-btn lis-btn-outline" style="justify-content:flex-start;">
            <i class="fas fa-x-ray" style="color:var(--lis-primary);width:16px;"></i> OPD Imaging Queue
          </a>
          <a href="ipd_test_orders.php" class="lis-btn lis-btn-outline" style="justify-content:flex-start;">
            <i class="fas fa-procedures" style="color:#0284c7;width:16px;"></i> Inpatient Ward Scans
          </a>
          <a href="kanban.php" class="lis-btn lis-btn-outline" style="justify-content:flex-start;">
            <i class="fas fa-film" style="color:#a855f7;width:16px;"></i> View Completed Results
          </a>
        </div>
      </div>

    </div>

  </div>

</div>
</div>

<script>
let currentNotifTab = 'all';

const dummyNotifs = [
  { id: 1, type: 'critical', title: 'STAT Trauma Scan: CT Brain Plain', desc: 'Emergency Casualty bed 3 • Suspected intracranial bleed', time: '10 min ago', unread: true },
  { id: 2, type: 'critical', title: 'Urgent Inpatient Scan: Bedside Chest X-Ray', desc: 'ICU Bed 4 • Acute respiratory distress post-op', time: '25 min ago', unread: true },
  { id: 3, type: 'pending', title: '3 Scans Waiting for Radiologist Signature', desc: 'Chest X-Ray PA, Knee Joint AP/LAT ready for review', time: '1 hr ago', unread: true },
  { id: 4, type: 'completed', title: 'Report Delivered: CT Abdomen & Pelvis', desc: 'Order #OPB-20260905-0012 reported & archived to PACS', time: '2 hrs ago', unread: false },
  { id: 5, type: 'completed', title: 'Report Delivered: USG Whole Abdomen', desc: 'Order #IPD-20260905-0004 reported by Dr. Radiologist', time: '3 hrs ago', unread: false },
  { id: 6, type: 'pending', title: 'Inventory Alert: Omnipaque 300mg Stock Low', desc: 'Remaining: 18 vials (Reorder threshold: 20 vials)', time: '4 hrs ago', unread: false }
];

document.addEventListener('DOMContentLoaded', () => {
  renderNotifs();
});

function switchNotifTab(tab, btn) {
  currentNotifTab = tab;
  document.querySelectorAll('.lis-tab-pill').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  renderNotifs();
}

function renderNotifs() {
  const container = document.getElementById('notif-feed');
  container.innerHTML = '';

  let list = dummyNotifs;
  if (currentNotifTab !== 'all') {
    list = dummyNotifs.filter(n => n.type === currentNotifTab);
  }

  if (list.length === 0) {
    container.innerHTML = '<div style="background:#fff;padding:40px;text-align:center;color:#94a3b8;"><i class="fas fa-check" style="font-size:2rem;margin-bottom:8px;"></i><div>No notifications in this category.</div></div>';
    return;
  }

  list.forEach(n => {
    let iconClass = 'fa-info-circle';
    let iconColor = '#0284c7';
    if (n.type === 'critical') { iconClass = 'fa-bolt'; iconColor = '#dc2626'; }
    else if (n.type === 'completed') { iconClass = 'fa-check-circle'; iconColor = '#059669'; }
    else if (n.type === 'pending') { iconClass = 'fa-clock'; iconColor = '#d97706'; }

    const div = document.createElement('div');
    div.style = `padding:16px 20px;background:${n.unread ? '#f8fafc' : '#fff'};display:flex;gap:14px;align-items:flex-start;`;
    div.innerHTML = `
      <div style="width:36px;height:36px;border-radius:50%;background:#fff;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:${iconColor};font-size:1rem;flex-shrink:0;">
        <i class="fas ${iconClass}"></i>
      </div>
      <div style="flex:1;">
        <div style="display:flex;justify-content:space-between;margin-bottom:2px;">
          <strong style="color:#1e293b;font-size:0.9rem;">${escHtml(n.title)}</strong>
          <span style="font-size:0.75rem;color:#94a3b8;">${escHtml(n.time)}</span>
        </div>
        <div style="font-size:0.82rem;color:#64748b;">${escHtml(n.desc)}</div>
      </div>
    `;
    container.appendChild(div);
  });
}

function markAllRead() {
  dummyNotifs.forEach(n => n.unread = false);
  renderNotifs();
  radToast('All notifications marked as read', 'success');
}

function loadNotifications() {
  renderNotifs();
  radToast('Notifications refreshed', 'info');
}
</script>

<?php require_once 'includes/rad_foot.php'; ?>
