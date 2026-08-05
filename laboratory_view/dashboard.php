<?php
$pageTitle = 'Dashboard';
$pageIcon  = 'fa-chart-line';
$navTitle  = 'LIS Dashboard';
$navSub    = 'Laboratory Command Center · Real-time Overview';
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
          <div class="lis-page-subtitle">Good <?= (date('H') < 12 ? 'Morning' : (date('H') < 17 ? 'Afternoon' : 'Evening')) ?>, <?= htmlspecialchars(explode(' ', $_SESSION['full_name'] ?? 'there')[0]) ?> 👋 &nbsp;•&nbsp; <?= date('l, F j, Y') ?></div>
        </div>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:10px;">
      <span class="lis-breadcrumb-pill"><i class="fas fa-circle" style="font-size:0.4rem;vertical-align:middle;color:#22c55e;"></i> Live</span>
      <button class="lis-btn lis-btn-outline" onclick="loadDashboard()">
        <i class="fas fa-sync-alt"></i> Refresh
      </button>
      <a href="test_orders.php" class="lis-btn lis-btn-primary">
        <i class="fas fa-plus"></i> New Lab Order
      </a>
    </div>
  </div>

  <!-- KPI Grid — 8 cards -->
  <div class="lis-kpi-grid lis-fade-up-1" id="kpiGrid">

    <div class="lis-kpi-card clickable" onclick="location.href='services.php'">
      <div class="lis-kpi-icon c-green"><i class="fas fa-flask"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="kpi-lab"><div class="lis-skeleton lis-skeleton-kpi" style="width:60px;"></div></div>
        <div class="lis-kpi-label">Lab Tests</div>
        <div class="lis-kpi-trend neutral"><i class="fas fa-vials"></i> Service Catalog</div>
      </div>
      <i class="fas fa-flask lis-kpi-bg-icon"></i>
    </div>


    <div class="lis-kpi-card clickable" onclick="location.href='test_orders.php'">
      <div class="lis-kpi-icon c-mint"><i class="fas fa-calendar-check"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="kpi-today"><div class="lis-skeleton lis-skeleton-kpi" style="width:60px;"></div></div>
        <div class="lis-kpi-label">Orders Today</div>
        <div class="lis-kpi-trend up" id="trend-today"><i class="fas fa-arrow-up"></i> vs yesterday</div>
      </div>
      <i class="fas fa-calendar-check lis-kpi-bg-icon"></i>
    </div>

    <div class="lis-kpi-card clickable" onclick="location.href='test_orders.php?status=Ordered'">
      <div class="lis-kpi-icon c-amber"><i class="fas fa-hourglass-half"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="kpi-pending"><div class="lis-skeleton lis-skeleton-kpi" style="width:60px;"></div></div>
        <div class="lis-kpi-label">Pending Orders</div>
        <div class="lis-kpi-trend neutral"><i class="fas fa-clock"></i> Awaiting processing</div>
      </div>
      <i class="fas fa-hourglass-half lis-kpi-bg-icon"></i>
    </div>

    <div class="lis-kpi-card clickable" onclick="location.href='test_orders.php?status=Completed'">
      <div class="lis-kpi-icon c-emerald"><i class="fas fa-check-circle"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="kpi-completed"><div class="lis-skeleton lis-skeleton-kpi" style="width:60px;"></div></div>
        <div class="lis-kpi-label">Completed Today</div>
        <div class="lis-kpi-trend up"><i class="fas fa-check"></i> Result ready</div>
      </div>
      <i class="fas fa-check-circle lis-kpi-bg-icon"></i>
    </div>

    <div class="lis-kpi-card clickable" onclick="location.href='critical_alerts.php'">
      <div class="lis-kpi-icon c-red"><i class="fas fa-bolt"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="kpi-urgent"><div class="lis-skeleton lis-skeleton-kpi" style="width:60px;"></div></div>
        <div class="lis-kpi-label">Urgent Today</div>
        <div class="lis-kpi-trend down"><i class="fas fa-exclamation"></i> Needs attention</div>
      </div>
      <i class="fas fa-bolt lis-kpi-bg-icon"></i>
    </div>

    <div class="lis-kpi-card clickable" onclick="location.href='patients.php'">
      <div class="lis-kpi-icon c-lime"><i class="fas fa-users"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="kpi-patients"><div class="lis-skeleton lis-skeleton-kpi" style="width:60px;"></div></div>
        <div class="lis-kpi-label">Patients This Month</div>
        <div class="lis-kpi-trend up"><i class="fas fa-user-plus"></i> Monthly count</div>
      </div>
      <i class="fas fa-users lis-kpi-bg-icon"></i>
    </div>

    <div class="lis-kpi-card clickable" onclick="location.href='services.php'">
      <div class="lis-kpi-icon c-slate"><i class="fas fa-vial"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="kpi-other"><div class="lis-skeleton lis-skeleton-kpi" style="width:60px;"></div></div>
        <div class="lis-kpi-label">Other Services</div>
        <div class="lis-kpi-trend neutral"><i class="fas fa-layer-group"></i> Diagnostics</div>
      </div>
      <i class="fas fa-vial lis-kpi-bg-icon"></i>
    </div>

  </div><!-- /.lis-kpi-grid -->

  <!-- Charts Row -->
  <div class="lis-grid-3-1 lis-fade-up-2">

    <!-- Left: Charts stacked -->
    <div style="display:flex;flex-direction:column;gap:20px;">

      <!-- Daily Trend Chart -->
      <div class="lis-card">
        <div class="lis-card-header">
          <div class="lis-card-title"><i class="fas fa-chart-area"></i> Daily Orders — Last 7 Days</div>
          <div style="display:flex;gap:8px;align-items:center;">
            <span class="lis-breadcrumb-pill" style="font-size:0.6rem;">Live</span>
            <button class="lis-btn lis-btn-outline lis-btn-sm lis-btn-icon" onclick="loadDashboard()" title="Refresh">
              <i class="fas fa-sync-alt"></i>
            </button>
          </div>
        </div>
        <div class="lis-card-body" style="padding:16px 20px;">
          <div style="position:relative;height:200px;">
            <canvas id="trendChart"></canvas>
          </div>
        </div>
      </div>

      <!-- Bottom row: Top Tests + Department -->
      <div class="lis-grid-2">

        <!-- Top Tests Chart -->
        <div class="lis-card">
          <div class="lis-card-header">
            <div class="lis-card-title"><i class="fas fa-trophy"></i> Top Tests This Month</div>
          </div>
          <div class="lis-card-body" style="padding:14px 18px;">
            <div style="position:relative;height:180px;">
              <canvas id="topTestsChart"></canvas>
            </div>
          </div>
        </div>

        <!-- Today's Progress Ring -->
        <div class="lis-card">
          <div class="lis-card-header">
            <div class="lis-card-title"><i class="fas fa-circle-notch"></i> Today's Progress</div>
          </div>
          <div class="lis-card-body" style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:16px;padding:24px 20px;">
            <div class="lis-progress-ring" id="progressRingEl">
              <svg width="80" height="80" viewBox="0 0 80 80">
                <circle class="lis-progress-ring-bg" cx="40" cy="40" r="35"/>
                <circle class="lis-progress-ring-fill" cx="40" cy="40" r="35" id="progressRingFill" style="stroke-dasharray:220;stroke-dashoffset:220;"/>
              </svg>
              <div class="lis-progress-ring-label">
                <span id="progressRingPct">0%</span>
                <small>Done</small>
              </div>
            </div>
            <div style="text-align:center;">
              <div style="font-size:0.72rem;font-weight:700;color:var(--lis-text-muted);">Completed / Total</div>
              <div style="font-size:1rem;font-weight:900;color:var(--lis-text);">
                <span id="progressCompleted">0</span> / <span id="progressTotal">0</span>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div><!-- /left -->

    <!-- Right Panel -->
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
              <i class="fas fa-file-medical-alt"></i> Reports
            </a>
          </div>
        </div>
      </div>

      <!-- Recent Orders Feed -->
      <div class="lis-card" style="flex:1;">
        <div class="lis-card-header">
          <div class="lis-card-title"><i class="fas fa-list-alt"></i> Recent Orders</div>
          <a href="test_orders.php" class="lis-btn lis-btn-outline lis-btn-sm">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="lis-card-body" style="padding:10px 14px;max-height:360px;overflow-y:auto;">
          <div class="lis-order-feed" id="recentOrdersFeed">
            <!-- Skeleton loaders -->
            <?php for($i=0;$i<5;$i++): ?>
            <div class="lis-order-item" style="gap:12px;">
              <div class="lis-skeleton" style="width:10px;height:10px;border-radius:50%;flex-shrink:0;"></div>
              <div style="flex:1;">
                <div class="lis-skeleton lis-skeleton-text" style="width:70%;margin-bottom:5px;"></div>
                <div class="lis-skeleton lis-skeleton-text" style="width:50%;"></div>
              </div>
              <div class="lis-skeleton" style="width:60px;height:20px;border-radius:20px;"></div>
            </div>
            <?php endfor; ?>
          </div>
        </div>
      </div>

    </div><!-- /right -->
  </div><!-- /.lis-grid-3-1 -->

  <!-- Recent Activity Timeline -->
  <div class="lis-fade-up-3">
    <div class="lis-card">
      <div class="lis-card-header">
        <div class="lis-card-title"><i class="fas fa-history"></i> Today's Activity Timeline</div>
        <a href="reports.php" class="lis-btn lis-btn-outline lis-btn-sm">View Reports</a>
      </div>
      <div class="lis-card-body" style="padding:18px 22px;">
        <div class="lis-timeline" id="activityTimeline">
          <div class="lis-timeline-item">
            <div class="lis-timeline-line">
              <div class="lis-skeleton" style="width:12px;height:12px;border-radius:50%;margin-top:3px;"></div>
              <div class="lis-timeline-connector"></div>
            </div>
            <div class="lis-timeline-content">
              <div class="lis-skeleton lis-skeleton-text" style="width:80px;margin-bottom:6px;"></div>
              <div class="lis-skeleton lis-skeleton-text" style="width:260px;margin-bottom:4px;"></div>
              <div class="lis-skeleton lis-skeleton-text" style="width:160px;"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div><!-- /.lis-content -->

<?php require_once 'includes/lab_foot.php'; ?>

<script>
let trendChartInst    = null;
let topTestsChartInst = null;

async function loadDashboard() {
  try {
    const data = await lisApi('GET', '/api/laboratory/dashboard');
    if (!data.success) return;

    const payload = data.data || data;
    const s = payload.stats || {};

    // Animate KPIs
    lisCountUp(document.getElementById('kpi-lab'),       s.lab_services);

    lisCountUp(document.getElementById('kpi-other'),     s.other);
    lisCountUp(document.getElementById('kpi-today'),     s.orders_today);
    lisCountUp(document.getElementById('kpi-pending'),   s.pending);
    lisCountUp(document.getElementById('kpi-completed'), s.completed_today);
    lisCountUp(document.getElementById('kpi-urgent'),    s.urgent_today);
    lisCountUp(document.getElementById('kpi-patients'),  s.month_patients);

    // Progress ring
    const total     = (s.orders_today || 0);
    const completed = (s.completed_today || 0);
    const pct = total > 0 ? Math.round((completed / total) * 100) : 0;
    const fill = document.getElementById('progressRingFill');
    if (fill) {
      const circumference = 2 * Math.PI * 35;
      fill.style.strokeDasharray  = circumference;
      fill.style.strokeDashoffset = circumference - (pct / 100) * circumference;
    }
    const pctEl = document.getElementById('progressRingPct');
    if (pctEl) pctEl.textContent = pct + '%';
    const cEl = document.getElementById('progressCompleted');
    const tEl = document.getElementById('progressTotal');
    if (cEl) cEl.textContent = completed;
    if (tEl) tEl.textContent = total;

    // Charts
    buildTrendChart(payload.trend || []);
    buildTopTestsChart(payload.top_tests || []);
    buildRecentFeed(payload.recent || []);
    buildTimeline(payload.recent || []);

  } catch(e) {
    lisToast('Failed to load dashboard data', 'error');
  }
}

function buildTrendChart(trend) {
  const today = new Date();
  const labels = [], values = [];
  for (let i = 6; i >= 0; i--) {
    const d = new Date(today);
    d.setDate(d.getDate() - i);
    const key = d.toISOString().slice(0,10);
    const match = trend.find(r => r.day === key);
    labels.push(d.toLocaleDateString('en-US', {weekday:'short', month:'short', day:'numeric'}));
    values.push(match ? parseInt(match.cnt) : 0);
  }
  if (trendChartInst) trendChartInst.destroy();
  const ctx = document.getElementById('trendChart').getContext('2d');
  const gradient = ctx.createLinearGradient(0, 0, 0, 200);
  gradient.addColorStop(0, 'rgba(31,107,74,0.22)');
  gradient.addColorStop(1, 'rgba(31,107,74,0.01)');

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
        tension: 0.45,
        fill: true,
        pointBackgroundColor: '#fff',
        pointBorderColor: '#1f6b4a',
        pointBorderWidth: 2.5,
        pointRadius: 5,
        pointHoverRadius: 7,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false }, tooltip: {
        backgroundColor: '#1f6b4a',
        titleFont: { family: 'Inter', size: 12, weight: '700' },
        bodyFont: { family: 'Inter', size: 11 },
        cornerRadius: 8, padding: 10,
        callbacks: { label: ctx => ` ${ctx.parsed.y} Orders` }
      }},
      scales: {
        x: { grid: { display: false }, ticks: { font: { family:'Inter', size:10 }, color:'#94a3b8' } },
        y: { beginAtZero: true, grid: { color: '#f1ede6', lineWidth: 1 }, ticks: { font: { family:'Inter', size:10 }, color:'#94a3b8', stepSize: 1 } }
      }
    }
  });
}

function buildTopTestsChart(tests) {
  if (topTestsChartInst) topTestsChartInst.destroy();
  if (!tests.length) return;
  const ctx = document.getElementById('topTestsChart').getContext('2d');
  const greens = ['#144d34','#1f6b4a','#2a8c62','#36a978','#4ec491','#71d6aa','#9cf2cd','#cbfaf1'];

  topTestsChartInst = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: tests.map(t => {
        const n = String(t.test_name || '');
        return n.length > 18 ? n.slice(0,16) + '…' : n;
      }),
      datasets: [{
        label: 'Orders',
        data: tests.map(t => parseInt(t.cnt)),
        backgroundColor: greens.slice(0, tests.length),
        borderRadius: 6,
        borderSkipped: false,
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { beginAtZero: true, grid: { color: '#f1ede6' }, ticks: { font: { family:'Inter', size:9 }, stepSize: 1, color:'#94a3b8' } },
        y: { grid: { display: false }, ticks: { font: { family:'Inter', size:9 }, color:'#1a1f2e' } }
      }
    }
  });
}

function buildRecentFeed(orders) {
  const feed = document.getElementById('recentOrdersFeed');
  if (!feed) return;
  if (!orders.length) {
    feed.innerHTML = `<div class="lis-empty">
      <div class="lis-empty-icon"><i class="fas fa-flask"></i></div>
      <div class="lis-empty-title">No orders today yet</div>
      <div class="lis-empty-sub">Orders from OPD consultations will appear here</div>
    </div>`;
    return;
  }

  feed.innerHTML = orders.slice(0, 8).map(o => {
    const pri = (o.priority || 'routine').toLowerCase();
    const time = o.order_time ? o.order_time.slice(0,5) : '';
    const tests = (() => {
      try { const a = JSON.parse(o.test_name); return Array.isArray(a) ? a.join(', ') : o.test_name; }
      catch { return o.test_name || '—'; }
    })();
    return `
    <div class="lis-order-item" onclick="location.href='test_orders.php'" style="cursor:pointer;">
      <div class="lis-order-priority-dot ${pri}"></div>
      <div class="lis-order-info">
        <div class="lis-order-test">${escHtml(tests)}</div>
        <div class="lis-order-meta">${escHtml(o.patient_name || '—')} &bull; ${escHtml(o.doctor_name || '—')} &bull; ${time}</div>
      </div>
      ${lisStatusBadge(o.status)}
    </div>`;
  }).join('');
}

function buildTimeline(orders) {
  const tl = document.getElementById('activityTimeline');
  if (!tl) return;
  if (!orders.length) {
    tl.innerHTML = `<div class="lis-empty" style="padding:30px 0;"><div class="lis-empty-icon"><i class="fas fa-history"></i></div><div class="lis-empty-title">No recent activity</div></div>`;
    return;
  }
  const icons = { 'Ordered':'fa-plus-circle', 'Completed':'fa-check-circle', 'Reported':'fa-file-medical', 'In Progress':'fa-spinner' };
  const dotClass = { 'Ordered':'', 'Completed':'success', 'Reported':'info', 'In Progress':'warning' };

  tl.innerHTML = orders.slice(0, 6).map((o, i) => {
    const tests = (() => {
      try { const a = JSON.parse(o.test_name); return Array.isArray(a) ? a.join(', ') : o.test_name; }
      catch { return o.test_name || '—'; }
    })();
    const ico   = icons[o.status] || 'fa-flask';
    const dc    = dotClass[o.status] || '';
    const isLast = i === orders.slice(0,6).length - 1;
    return `
    <div class="lis-timeline-item">
      <div class="lis-timeline-line">
        <div class="lis-timeline-dot ${dc}"></div>
        ${!isLast ? '<div class="lis-timeline-connector"></div>' : ''}
      </div>
      <div class="lis-timeline-content">
        <div class="lis-timeline-time">${o.order_time ? o.order_time.slice(0,5) : o.order_date || ''}</div>
        <div class="lis-timeline-title">
          <i class="fas ${ico}" style="margin-right:6px;color:var(--lis-primary);font-size:0.75rem;"></i>
          ${escHtml(tests)}
        </div>
        <div class="lis-timeline-desc">${escHtml(o.patient_name || '—')} &bull; Dr. ${escHtml(o.doctor_name || '—')} &bull; ${lisStatusBadge(o.status)}</div>
      </div>
    </div>`;
  }).join('');
}

// Initial load
loadDashboard();
// Auto-refresh every 60s
setInterval(loadDashboard, 60000);
</script>
