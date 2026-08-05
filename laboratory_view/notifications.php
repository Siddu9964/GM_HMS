<?php
$pageTitle = 'Notifications';
$pageIcon  = 'fa-bell';
$navTitle  = 'Notification Center';
$navSub    = 'Lab alerts, reports, and system updates';
require_once 'includes/lab_head.php';
?>
<?php require_once 'includes/lab_sidebar.php'; ?>

<div class="lis-main-content">
<?php require_once 'includes/lab_navbar.php'; ?>

<div class="lis-content">

  <!-- Page Header -->
  <div class="lis-page-header lis-fade-up">
    <div>
      <div class="lis-page-title">
        <div class="lis-page-title-icon"><i class="fas fa-bell"></i></div>
        <div>
          Notification Center
          <div class="lis-page-subtitle">Stay updated with lab results, orders and system alerts</div>
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
        <button class="lis-tab-pill active" data-tab="all"       onclick="switchTab('all',this)"><i class="fas fa-list"></i> All</button>
        <button class="lis-tab-pill"        data-tab="critical"  onclick="switchTab('critical',this)"><i class="fas fa-exclamation-triangle"></i> Critical</button>
        <button class="lis-tab-pill"        data-tab="completed" onclick="switchTab('completed',this)"><i class="fas fa-check"></i> Completed</button>
        <button class="lis-tab-pill"        data-tab="pending"   onclick="switchTab('pending',this)"><i class="fas fa-clock"></i> Pending</button>
      </div>

      <div class="lis-card" style="overflow:hidden;">
        <div id="notif-all" class="tab-panel active" style="display:block;">
          <div id="notifAll">
            <div style="padding:40px;text-align:center;"><div class="lis-spinner" style="margin:0 auto 8px;"></div><div style="font-size:0.78rem;color:var(--lis-text-muted);">Loading...</div></div>
          </div>
        </div>
        <div id="notif-critical" class="tab-panel" style="display:none;"><div id="notifCritical"></div></div>
        <div id="notif-completed" class="tab-panel" style="display:none;"><div id="notifCompleted"></div></div>
        <div id="notif-pending" class="tab-panel" style="display:none;"><div id="notifPending"></div></div>
      </div>
    </div>

    <!-- Right: Summary Panel -->
    <div style="display:flex;flex-direction:column;gap:16px;">

      <!-- Stats -->
      <div class="lis-card">
        <div class="lis-card-header">
          <div class="lis-card-title"><i class="fas fa-chart-bar"></i> Summary</div>
        </div>
        <div class="lis-card-body" style="display:flex;flex-direction:column;gap:12px;">
          <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:var(--lis-danger-bg);border-radius:10px;border:1px solid #fca5a5;">
            <div>
              <div style="font-size:0.68rem;font-weight:700;color:var(--lis-danger);text-transform:uppercase;">Critical</div>
              <div class="lis-kpi-value" id="notif-cnt-critical" style="font-size:1.4rem;color:var(--lis-danger);">—</div>
            </div>
            <i class="fas fa-exclamation-triangle" style="font-size:1.5rem;color:var(--lis-danger);opacity:0.3;"></i>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:var(--lis-success-bg);border-radius:10px;border:1px solid #6ee7b7;">
            <div>
              <div style="font-size:0.68rem;font-weight:700;color:var(--lis-success);text-transform:uppercase;">Completed</div>
              <div class="lis-kpi-value" id="notif-cnt-completed" style="font-size:1.4rem;color:var(--lis-success);">—</div>
            </div>
            <i class="fas fa-check-circle" style="font-size:1.5rem;color:var(--lis-success);opacity:0.3;"></i>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:var(--lis-warning-bg);border-radius:10px;border:1px solid #fde68a;">
            <div>
              <div style="font-size:0.68rem;font-weight:700;color:var(--lis-warning);text-transform:uppercase;">Pending</div>
              <div class="lis-kpi-value" id="notif-cnt-pending" style="font-size:1.4rem;color:var(--lis-warning);">—</div>
            </div>
            <i class="fas fa-clock" style="font-size:1.5rem;color:var(--lis-warning);opacity:0.3;"></i>
          </div>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="lis-card">
        <div class="lis-card-header">
          <div class="lis-card-title"><i class="fas fa-link"></i> Quick Actions</div>
        </div>
        <div class="lis-card-body" style="display:flex;flex-direction:column;gap:8px;">
          <a href="test_orders.php" class="lis-btn lis-btn-outline" style="justify-content:flex-start;">
            <i class="fas fa-flask" style="color:var(--lis-primary);width:16px;"></i> View All Orders
          </a>
          <a href="critical_alerts.php" class="lis-btn lis-btn-outline" style="justify-content:flex-start;">
            <i class="fas fa-exclamation-triangle" style="color:var(--lis-danger);width:16px;"></i> Critical Alerts
          </a>
          <a href="reports.php" class="lis-btn lis-btn-outline" style="justify-content:flex-start;">
            <i class="fas fa-file-medical-alt" style="color:var(--lis-info);width:16px;"></i> Print Reports
          </a>
          <a href="kanban.php" class="lis-btn lis-btn-outline" style="justify-content:flex-start;">
            <i class="fas fa-columns" style="color:var(--lis-accent);width:16px;"></i> Kanban Board
          </a>
        </div>
      </div>

    </div>

  </div><!-- /.lis-grid -->

</div>
<?php require_once 'includes/lab_foot.php'; ?>

<script>
let allNotifications = [];
let currentTab = 'all';

function switchTab(tab, el) {
  currentTab = tab;
  document.querySelectorAll('.lis-tab-pill').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('.tab-panel').forEach(p => p.style.display = 'none');
  const panel = document.getElementById(`notif-${tab}`);
  if (panel) panel.style.display = 'block';
  renderTab(tab);
}

function renderTab(tab) {
  const maps = {
    all:       { id:'notifAll',       data: allNotifications },
    critical:  { id:'notifCritical',  data: allNotifications.filter(n => n.type === 'critical') },
    completed: { id:'notifCompleted', data: allNotifications.filter(n => n.type === 'completed') },
    pending:   { id:'notifPending',   data: allNotifications.filter(n => n.type === 'pending') },
  };
  const { id, data } = maps[tab] || maps['all'];
  const el = document.getElementById(id);
  if (!el) return;

  if (!data.length) {
    el.innerHTML = `<div class="lis-empty" style="padding:48px 20px;">
      <div class="lis-empty-icon"><i class="fas fa-check-circle" style="color:var(--lis-success);"></i></div>
      <div class="lis-empty-title">All clear!</div>
      <div class="lis-empty-sub">No notifications in this category.</div>
    </div>`;
    return;
  }

  el.innerHTML = data.map(n => `
    <div class="lis-notif-item ${n.unread ? 'unread' : ''}">
      <div class="lis-notif-icon" style="background:${n.iconBg};color:${n.iconColor};">
        <i class="fas ${n.icon}"></i>
      </div>
      <div style="flex:1;min-width:0;">
        <div class="lis-notif-title">${escHtml(n.title)}</div>
        <div class="lis-notif-desc">${escHtml(n.desc)}</div>
        <div class="lis-notif-time"><i class="fas fa-clock" style="margin-right:3px;"></i>${escHtml(n.time)}</div>
      </div>
      ${n.badge ? `<span class="lis-badge ${n.badgeCls}">${n.badge}</span>` : ''}
    </div>`).join('');
}

async function loadNotifications() {
  try {
    const data = await lisApi('GET', '/api/laboratory/orders?all=0');
    if (!data.success) return;

    const orders = data.data || [];
    allNotifications = [];

    // Build notifications from orders
    orders.forEach(o => {
      const tests = (() => { try { const a=JSON.parse(o.test_name); return Array.isArray(a)?a.join(', '):o.test_name; } catch{return o.test_name||'—';} })();
      const time  = o.order_time ? o.order_time.slice(0,5) : '';

      if (o.priority === 'Urgent' || o.priority === 'STAT') {
        allNotifications.push({
          type:'critical', unread:true,
          icon:'fa-bolt', iconBg:'var(--lis-danger-bg)', iconColor:'var(--lis-danger)',
          title:`Urgent Order — ${tests}`,
          desc: `Patient: ${o.patient_name||'—'} • Dr. ${o.doctor_name||'—'}`,
          time, badge:'Urgent', badgeCls:'lis-badge-urgent'
        });
      }

      if (o.status === 'Completed' || o.status === 'Reported') {
        allNotifications.push({
          type:'completed', unread:false,
          icon:'fa-check-circle', iconBg:'var(--lis-success-bg)', iconColor:'var(--lis-success)',
          title:`Report Ready — ${tests}`,
          desc: `Patient: ${o.patient_name||'—'} • Ready for collection`,
          time, badge:'Completed', badgeCls:'lis-badge-completed'
        });
      }

      if (o.status === 'Ordered' || o.status === 'In Progress') {
        allNotifications.push({
          type:'pending', unread:true,
          icon:'fa-hourglass-half', iconBg:'var(--lis-warning-bg)', iconColor:'var(--lis-warning)',
          title:`Pending — ${tests}`,
          desc: `Patient: ${o.patient_name||'—'} • ${o.status}`,
          time, badge:'Pending', badgeCls:'lis-badge-processing'
        });
      }
    });

    const critical  = allNotifications.filter(n => n.type === 'critical').length;
    const completed = allNotifications.filter(n => n.type === 'completed').length;
    const pending   = allNotifications.filter(n => n.type === 'pending').length;

    lisCountUp(document.getElementById('notif-cnt-critical'),  critical);
    lisCountUp(document.getElementById('notif-cnt-completed'), completed);
    lisCountUp(document.getElementById('notif-cnt-pending'),   pending);

    renderTab(currentTab);

  } catch(e) {
    lisToast('Failed to load notifications', 'error');
  }
}

function markAllRead() {
  allNotifications.forEach(n => n.unread = false);
  document.querySelectorAll('.lis-notif-item.unread').forEach(el => el.classList.remove('unread'));
  lisToast('All notifications marked as read', 'success');
}

loadNotifications();
</script>
