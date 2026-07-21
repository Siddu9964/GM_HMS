<?php
$pageTitle = 'Dashboard';
$pageIcon  = 'fa-chart-line';
$navTitle  = 'LIS Dashboard';
$navSub    = 'Laboratory Command Center';
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
        <div class="lis-page-title-icon"><i class="fas fa-chart-line"></i></div>
        <div>
          LIS Dashboard
          <div class="lis-page-subtitle">Real-time laboratory overview and statistics</div>
        </div>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:10px;">
      <span class="lis-breadcrumb"><i class="fas fa-circle" style="font-size:0.4rem;vertical-align:middle;margin-right:4px;color:#22c55e;"></i> Live</span>
      <a href="test_orders.php" class="lis-btn lis-btn-primary">
        <i class="fas fa-plus"></i> New Lab Order
      </a>
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="lis-kpi-grid lis-fade-up-1" id="kpiGrid">

    <div class="lis-kpi-card">
      <div class="lis-kpi-icon teal"><i class="fas fa-flask"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="kpi-lab">—</div>
        <div class="lis-kpi-label">Lab Tests</div>
      </div>
      <i class="fas fa-flask lis-kpi-bg-icon"></i>
    </div>

    <div class="lis-kpi-card">
      <div class="lis-kpi-icon violet"><i class="fas fa-x-ray"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="kpi-radiology">—</div>
        <div class="lis-kpi-label">Radiology</div>
      </div>
      <i class="fas fa-x-ray lis-kpi-bg-icon"></i>
    </div>

    <div class="lis-kpi-card">
      <div class="lis-kpi-icon slate"><i class="fas fa-vial"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="kpi-other">—</div>
        <div class="lis-kpi-label">Other Services</div>
      </div>
      <i class="fas fa-vial lis-kpi-bg-icon"></i>
    </div>

    <div class="lis-kpi-card">
      <div class="lis-kpi-icon cyan"><i class="fas fa-calendar-check"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="kpi-today">—</div>
        <div class="lis-kpi-label">Orders Today</div>
      </div>
      <i class="fas fa-calendar-check lis-kpi-bg-icon"></i>
    </div>

    <div class="lis-kpi-card">
      <div class="lis-kpi-icon amber"><i class="fas fa-hourglass-half"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="kpi-pending">—</div>
        <div class="lis-kpi-label">Pending</div>
      </div>
      <i class="fas fa-hourglass-half lis-kpi-bg-icon"></i>
    </div>

    <div class="lis-kpi-card">
      <div class="lis-kpi-icon green"><i class="fas fa-check-circle"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="kpi-completed">—</div>
        <div class="lis-kpi-label">Completed Today</div>
      </div>
      <i class="fas fa-check-circle lis-kpi-bg-icon"></i>
    </div>

    <div class="lis-kpi-card">
      <div class="lis-kpi-icon red"><i class="fas fa-bolt"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="kpi-urgent">—</div>
        <div class="lis-kpi-label">Urgent Today</div>
      </div>
      <i class="fas fa-bolt lis-kpi-bg-icon"></i>
    </div>

    <div class="lis-kpi-card">
      <div class="lis-kpi-icon indigo"><i class="fas fa-users"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="kpi-patients">—</div>
        <div class="lis-kpi-label">Patients This Month</div>
      </div>
      <i class="fas fa-users lis-kpi-bg-icon"></i>
    </div>

  </div><!-- /.lis-kpi-grid -->

  <!-- Charts Row -->
  <div class="lis-grid-3-1 lis-fade-up-2">

    <!-- Left: Line chart + Top tests -->
    <div style="display:flex;flex-direction:column;gap:20px;">

      <!-- Daily Trend Chart -->
      <div class="lis-card">
        <div class="lis-card-header">
          <div class="lis-card-title"><i class="fas fa-chart-line"></i> Daily Orders — Last 7 Days</div>
          <button class="lis-btn lis-btn-outline lis-btn-sm" onclick="loadDashboard()">
            <i class="fas fa-sync-alt"></i>
          </button>
        </div>
        <div class="lis-card-body" style="padding:16px 20px;">
          <div style="position:relative;height:220px;">
            <canvas id="trendChart"></canvas>
          </div>
        </div>
      </div>

      <!-- Top Tests Chart -->
      <div class="lis-card">
        <div class="lis-card-header">
          <div class="lis-card-title"><i class="fas fa-trophy"></i> Top Tests This Month</div>
        </div>
        <div class="lis-card-body" style="padding:16px 20px;">
          <div style="position:relative;height:200px;">
            <canvas id="topTestsChart"></canvas>
          </div>
        </div>
      </div>

    </div><!-- left col -->

    <!-- Right: Quick Actions + Recent Orders -->
    <div style="display:flex;flex-direction:column;gap:20px;">

      <!-- Quick Actions -->
      <div class="lis-card">
        <div class="lis-card-header">
          <div class="lis-card-title"><i class="fas fa-bolt"></i> Quick Actions</div>
        </div>
        <div class="lis-card-body">
          <div class="lis-quick-grid">
            <a href="test_orders.php" class="lis-quick-btn">
              <i class="fas fa-plus-circle"></i> New Order
            </a>
            <a href="services.php" class="lis-quick-btn">
              <i class="fas fa-vials"></i> Catalog
            </a>
            <a href="patients.php" class="lis-quick-btn">
              <i class="fas fa-user-injured"></i> Patients
            </a>
            <a href="reports.php" class="lis-quick-btn">
              <i class="fas fa-chart-bar"></i> Reports
            </a>
          </div>
        </div>
      </div>

      <!-- Recent Lab Orders -->
      <div class="lis-card" style="flex:1;">
        <div class="lis-card-header">
          <div class="lis-card-title"><i class="fas fa-list-alt"></i> Recent Orders</div>
          <a href="test_orders.php" class="lis-btn lis-btn-outline lis-btn-sm">View All</a>
        </div>
        <div class="lis-card-body" style="padding:12px 16px;max-height:380px;overflow-y:auto;">
          <div class="lis-order-feed" id="recentOrdersFeed">
            <div class="lis-empty"><i class="fas fa-flask"></i><div class="lis-empty-title">Loading...</div></div>
          </div>
        </div>
      </div>

    </div><!-- right col -->
  </div><!-- /.lis-grid-3-1 -->

</div><!-- /.lis-content -->

<?php require_once 'includes/lab_foot.php'; ?>

<script>
let trendChartInst   = null;
let topTestsChartInst = null;

async function loadDashboard() {
  try {
    const data = await lisApi('GET', '/api/laboratory/dashboard');
    if (!data.success) return;
    
    // Support both direct response and nested 'data' response
    const payload = data.data || data;
    const s = payload.stats || {};

    // KPI count-up
    lisCountUp(document.getElementById('kpi-lab'),       s.lab_services);
    lisCountUp(document.getElementById('kpi-radiology'), s.radiology);
    lisCountUp(document.getElementById('kpi-other'),     s.other);
    lisCountUp(document.getElementById('kpi-today'),     s.orders_today);
    lisCountUp(document.getElementById('kpi-pending'),   s.pending);
    lisCountUp(document.getElementById('kpi-completed'), s.completed_today);
    lisCountUp(document.getElementById('kpi-urgent'),    s.urgent_today);
    lisCountUp(document.getElementById('kpi-patients'),  s.month_patients);

    // Daily trend chart
    buildTrendChart(payload.trend || []);

    // Top tests chart
    buildTopTestsChart(payload.top_tests || []);

    // Recent orders feed
    buildRecentFeed(payload.recent || []);

  } catch(e) {
    lisToast('Failed to load dashboard data', 'error');
  }
}

function buildTrendChart(trend) {
  // Fill missing days
  const today = new Date();
  const labels = [], values = [];
  for (let i = 6; i >= 0; i--) {
    const d = new Date(today);
    d.setDate(d.getDate() - i);
    const key = d.toISOString().slice(0,10);
    const match = trend.find(r => r.day === key);
    labels.push(d.toLocaleDateString('en-US',{weekday:'short'}));
    values.push(match ? parseInt(match.cnt) : 0);
  }

  if (trendChartInst) trendChartInst.destroy();
  const ctx = document.getElementById('trendChart').getContext('2d');
  const gradient = ctx.createLinearGradient(0,0,0,220);
  gradient.addColorStop(0, 'rgba(31, 107, 74, 0.25)');
  gradient.addColorStop(1, 'rgba(31, 107, 74, 0.02)');

  trendChartInst = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Orders',
        data: values,
        borderColor: '#1f6b4a',
        backgroundColor: gradient,
        borderWidth: 2.5,
        tension: 0.4,
        fill: true,
        pointBackgroundColor: '#1f6b4a',
        pointRadius: 4,
        pointHoverRadius: 6,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { font: { family:'Inter', size:11 } } },
        y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { family:'Inter', size:11 }, stepSize: 1 } }
      }
    }
  });
}

function buildTopTestsChart(tests) {
  if (topTestsChartInst) topTestsChartInst.destroy();
  if (!tests.length) return;
  const ctx = document.getElementById('topTestsChart').getContext('2d');
  const colors = ['#144d34','#1f6b4a','#2a8c62','#36a978','#4ec491','#71d6aa','#9cf2cd','#cbfaf1'];

  topTestsChartInst = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: tests.map(t => t.test_name),
      datasets: [{
        label: 'Count',
        data: tests.map(t => parseInt(t.cnt)),
        backgroundColor: colors.slice(0, tests.length),
        borderRadius: 6,
        borderSkipped: false,
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { family:'Inter', size:10 }, stepSize: 1 } },
        y: { grid: { display: false }, ticks: { font: { family:'Inter', size:10 } } }
      }
    }
  });
}

function buildRecentFeed(orders) {
  const feed = document.getElementById('recentOrdersFeed');
  if (!orders.length) {
    feed.innerHTML = `<div class="lis-empty">
      <i class="fas fa-flask"></i>
      <div class="lis-empty-title">No orders yet today</div>
      <div class="lis-empty-sub">Orders from OPD will appear here</div>
    </div>`;
    return;
  }

  feed.innerHTML = orders.map(o => {
    const pri = (o.priority || 'Routine').toLowerCase();
    const statusCls = {
      'Ordered':     'lis-badge-ordered',
      'In Progress': 'lis-badge-progress',
      'Completed':   'lis-badge-completed',
      'Reported':    'lis-badge-reported',
    }[o.status] || 'lis-badge-ordered';

    const time = o.order_time ? o.order_time.slice(0,5) : '';
    return `
    <div class="lis-order-item">
      <div class="lis-order-priority-dot ${pri}"></div>
      <div class="lis-order-info">
        <div class="lis-order-test">${escHtml(o.test_name)}</div>
        <div class="lis-order-meta">${escHtml(o.patient_name || '—')} &bull; ${escHtml(o.doctor_name || '—')} &bull; ${time}</div>
      </div>
      <span class="lis-badge ${statusCls}">${escHtml(o.status)}</span>
    </div>`;
  }).join('');
}

function escHtml(s) {
  return String(s || '')
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;');
}

// Initial load
loadDashboard();
// Auto-refresh every 60 seconds
setInterval(loadDashboard, 60000);
</script>
