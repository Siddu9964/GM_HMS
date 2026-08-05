<?php
$pageTitle = 'Critical Alerts';
$pageIcon  = 'fa-exclamation-triangle';
$navTitle  = 'Critical Alert Center';
$navSub    = 'High-priority lab results requiring immediate attention';
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
        <div class="lis-page-title-icon" style="background:linear-gradient(135deg,#dc2626,#991b1b);">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div>
          Critical Alert Center
          <div class="lis-page-subtitle">Urgent lab results requiring immediate physician notification</div>
        </div>
      </div>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
      <span class="lis-breadcrumb-pill" style="background:#fee2e2;color:#dc2626;border-color:#fca5a5;">
        <i class="fas fa-circle" style="font-size:0.4rem;color:#dc2626;animation:lisPulseRed 1.5s infinite;"></i>
        Live Monitoring
      </span>
      <button class="lis-btn lis-btn-outline" onclick="loadAlerts()">
        <i class="fas fa-sync-alt"></i> Refresh
      </button>
    </div>
  </div>

  <!-- Alert Tabs -->
  <div class="lis-filter-bar lis-fade-up-1">
    <span style="font-size:0.68rem;font-weight:800;color:var(--lis-text-muted);text-transform:uppercase;letter-spacing:0.08em;">Filter:</span>
    <button class="lis-filter-chip active" data-filter="all"        onclick="setFilter('all',this)">All Alerts</button>
    <button class="lis-filter-chip"        data-filter="Urgent"     onclick="setFilter('Urgent',this)"><i class="fas fa-bolt"></i> Urgent</button>
    <button class="lis-filter-chip"        data-filter="Ordered"    onclick="setFilter('Ordered',this)"><i class="fas fa-inbox"></i> Pending Review</button>
    <button class="lis-filter-chip"        data-filter="In Progress"onclick="setFilter('In Progress',this)"><i class="fas fa-spinner"></i> In Progress</button>
    <div style="margin-left:auto;">
      <span class="lis-badge" id="alertCount" style="background:var(--lis-danger-bg);color:var(--lis-danger);font-size:0.72rem;padding:5px 14px;">Loading...</span>
    </div>
  </div>

  <!-- Alerts Grid -->
  <div class="lis-grid-2 lis-fade-up-2">

    <!-- Left: Urgent / Critical Priority Orders -->
    <div>
      <div class="lis-card" style="margin-bottom:20px;">
        <div class="lis-card-header">
          <div class="lis-card-title" style="color:var(--lis-danger);">
            <i class="fas fa-bolt" style="color:var(--lis-danger);animation:lisBlink 1.2s ease-in-out infinite;"></i>
            Urgent Priority Orders
          </div>
          <span class="lis-badge lis-badge-urgent" id="urgentCount">0</span>
        </div>
        <div id="urgentList" style="padding:12px 16px;display:flex;flex-direction:column;gap:10px;">
          <div style="padding:20px;text-align:center;color:var(--lis-text-muted);">
            <div class="lis-spinner"></div>
            <div style="margin-top:8px;font-size:0.78rem;">Loading urgent orders...</div>
          </div>
        </div>
      </div>

      <!-- Critical Value Reference Panel -->
      <div class="lis-card">
        <div class="lis-card-header">
          <div class="lis-card-title"><i class="fas fa-info-circle"></i> Critical Value Reference</div>
        </div>
        <div class="lis-card-body" style="padding:16px;">
          <table class="lis-table" style="font-size:0.75rem;">
            <thead><tr>
              <th>Test</th>
              <th>Critical Low</th>
              <th>Critical High</th>
              <th>Action</th>
            </tr></thead>
            <tbody>
              <tr>
                <td><strong>Hemoglobin</strong></td>
                <td><span class="lis-badge" style="background:#cffafe;color:#0e7490;">&lt; 7 g/dL</span></td>
                <td><span class="lis-badge lis-badge-urgent">&gt; 20 g/dL</span></td>
                <td>Call physician immediately</td>
              </tr>
              <tr>
                <td><strong>Potassium</strong></td>
                <td><span class="lis-badge" style="background:#cffafe;color:#0e7490;">&lt; 2.8 mEq/L</span></td>
                <td><span class="lis-badge lis-badge-urgent">&gt; 6.2 mEq/L</span></td>
                <td>Emergency notification</td>
              </tr>
              <tr>
                <td><strong>Blood Sugar</strong></td>
                <td><span class="lis-badge" style="background:#cffafe;color:#0e7490;">&lt; 40 mg/dL</span></td>
                <td><span class="lis-badge lis-badge-urgent">&gt; 500 mg/dL</span></td>
                <td>Immediate intervention</td>
              </tr>
              <tr>
                <td><strong>Sodium</strong></td>
                <td><span class="lis-badge" style="background:#cffafe;color:#0e7490;">&lt; 120 mEq/L</span></td>
                <td><span class="lis-badge lis-badge-urgent">&gt; 160 mEq/L</span></td>
                <td>Alert ICU team</td>
              </tr>
              <tr>
                <td><strong>Creatinine</strong></td>
                <td style="color:var(--lis-text-muted);">—</td>
                <td><span class="lis-badge lis-badge-urgent">&gt; 10 mg/dL</span></td>
                <td>Renal alert protocol</td>
              </tr>
              <tr>
                <td><strong>WBC</strong></td>
                <td><span class="lis-badge" style="background:#cffafe;color:#0e7490;">&lt; 2,000/μL</span></td>
                <td><span class="lis-badge lis-badge-urgent">&gt; 100,000/μL</span></td>
                <td>Hematology consult</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Right: Today's Orders needing attention -->
    <div>
      <div class="lis-card" style="margin-bottom:20px;">
        <div class="lis-card-header">
          <div class="lis-card-title"><i class="fas fa-hourglass-half"></i> Pending Review — Today</div>
          <span class="lis-badge lis-badge-processing" id="pendingCount">0</span>
        </div>
        <div id="pendingList" style="padding:12px 16px;max-height:420px;overflow-y:auto;display:flex;flex-direction:column;gap:10px;">
          <div style="padding:20px;text-align:center;color:var(--lis-text-muted);">
            <div class="lis-spinner"></div>
            <div style="margin-top:8px;font-size:0.78rem;">Loading pending orders...</div>
          </div>
        </div>
      </div>

      <!-- Alert Action Log -->
      <div class="lis-card">
        <div class="lis-card-header">
          <div class="lis-card-title"><i class="fas fa-clipboard-list"></i> Alert Protocol</div>
        </div>
        <div class="lis-card-body">
          <div class="lis-timeline">
            <div class="lis-timeline-item">
              <div class="lis-timeline-line">
                <div class="lis-timeline-dot danger"></div>
                <div class="lis-timeline-connector"></div>
              </div>
              <div class="lis-timeline-content">
                <div class="lis-timeline-title">Step 1 — Identify Critical Value</div>
                <div class="lis-timeline-desc">Verify result accuracy. Re-run if specimen quality is questionable.</div>
              </div>
            </div>
            <div class="lis-timeline-item">
              <div class="lis-timeline-line">
                <div class="lis-timeline-dot warning"></div>
                <div class="lis-timeline-connector"></div>
              </div>
              <div class="lis-timeline-content">
                <div class="lis-timeline-title">Step 2 — Notify Ordering Physician</div>
                <div class="lis-timeline-desc">Call the responsible doctor immediately. Document time of call.</div>
              </div>
            </div>
            <div class="lis-timeline-item">
              <div class="lis-timeline-line">
                <div class="lis-timeline-dot info"></div>
                <div class="lis-timeline-connector"></div>
              </div>
              <div class="lis-timeline-content">
                <div class="lis-timeline-title">Step 3 — Update Order Status</div>
                <div class="lis-timeline-desc">Mark order as "Reported" and document physician acknowledgment.</div>
              </div>
            </div>
            <div class="lis-timeline-item">
              <div class="lis-timeline-line">
                <div class="lis-timeline-dot success"></div>
              </div>
              <div class="lis-timeline-content">
                <div class="lis-timeline-title">Step 4 — Print & Archive Report</div>
                <div class="lis-timeline-desc">Print final report, attach to patient file. Archive in system.</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>

</div><!-- /.lis-content -->

<?php require_once 'includes/lab_foot.php'; ?>

<script>
let currentFilter = 'all';

function setFilter(filter, el) {
  currentFilter = filter;
  document.querySelectorAll('.lis-filter-chip').forEach(c => c.classList.remove('active'));
  el.classList.add('active');
  renderAlerts();
}

let urgentOrders  = [];
let pendingOrders = [];

async function loadAlerts() {
  try {
    // Load all today's orders
    const data = await lisApi('GET', '/api/laboratory/orders?all=0');
    if (!data.success) return;

    const orders = data.data || [];
    urgentOrders  = orders.filter(o => o.priority === 'Urgent' || o.priority === 'STAT');
    pendingOrders = orders.filter(o => o.status === 'Ordered' || o.status === 'In Progress');

    const totalAlerts = urgentOrders.length + pendingOrders.length;
    document.getElementById('alertCount').textContent = totalAlerts + ' Total Alerts';
    document.getElementById('urgentCount').textContent = urgentOrders.length;
    document.getElementById('pendingCount').textContent = pendingOrders.length;

    renderAlerts();

  } catch(e) {
    lisToast('Failed to load alerts', 'error');
  }
}

function renderAlerts() {
  renderList('urgentList',  urgentOrders,  'urgent');
  renderList('pendingList', pendingOrders, 'pending');
}

function renderList(containerId, orders, type) {
  const el = document.getElementById(containerId);
  if (!el) return;

  const filtered = currentFilter === 'all' ? orders
    : currentFilter === 'Urgent' ? (type === 'urgent' ? orders : [])
    : orders.filter(o => o.status === currentFilter);

  if (!filtered.length) {
    el.innerHTML = `<div class="lis-empty" style="padding:32px 0;">
      <div class="lis-empty-icon" style="width:52px;height:52px;margin:0 auto 12px;"><i class="fas fa-check-circle" style="color:var(--lis-success);"></i></div>
      <div class="lis-empty-title" style="color:var(--lis-success);">All Clear</div>
      <div class="lis-empty-sub">No ${type} alerts at this time</div>
    </div>`;
    return;
  }

  el.innerHTML = filtered.map(o => {
    const tests = (() => { try { const a = JSON.parse(o.test_name); return Array.isArray(a) ? a.join(', ') : o.test_name; } catch { return o.test_name || '—'; } })();
    const severity = o.priority === 'Urgent' ? 'critical' : 'warning';
    return `
    <div class="lis-alert-card ${severity}">
      <div class="lis-alert-icon"><i class="fas ${severity === 'critical' ? 'fa-exclamation-triangle' : 'fa-exclamation-circle'}"></i></div>
      <div class="lis-alert-body">
        <div class="lis-alert-title">${escHtml(tests)}</div>
        <div class="lis-alert-meta">
          <strong>${escHtml(o.patient_name || '—')}</strong> &bull;
          Dr. ${escHtml(o.doctor_name || '—')} &bull;
          ${o.order_time ? o.order_time.slice(0,5) : o.order_date || ''}
        </div>
        <div style="margin-top:6px;display:flex;gap:6px;flex-wrap:wrap;">
          ${lisPriorityBadge(o.priority)} ${lisStatusBadge(o.status)}
        </div>
      </div>
      <div class="lis-alert-actions">
        <button class="lis-btn lis-btn-sm lis-btn-success" onclick="markReviewed('${escHtml(o.order_id)}')">
          <i class="fas fa-check"></i> Review
        </button>
        <a href="test_orders.php" class="lis-btn lis-btn-sm lis-btn-outline">
          <i class="fas fa-eye"></i> View
        </a>
      </div>
    </div>`;
  }).join('');
}

async function markReviewed(orderId) {
  try {
    const res = await lisApi('PUT', `/api/laboratory/orders/${encodeURIComponent(orderId)}/status`, { status: 'Reported' });
    if (res.success) {
      lisToast('Order marked as Reported', 'success');
      loadAlerts();
    } else {
      lisToast(res.error || 'Failed to update', 'error');
    }
  } catch(e) { lisToast('Network error', 'error'); }
}

loadAlerts();
setInterval(loadAlerts, 30000);
</script>
