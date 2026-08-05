<?php
$pageTitle = 'Analytics';
$pageIcon  = 'fa-chart-pie';
$navTitle  = 'Laboratory Analytics';
$navSub    = 'Performance insights, trends and operational data';
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
        <div class="lis-page-title-icon"><i class="fas fa-chart-pie"></i></div>
        <div>
          Laboratory Analytics
          <div class="lis-page-subtitle">Operational performance & trend analysis</div>
        </div>
      </div>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
      <select id="analytics-period" class="lis-input lis-select" style="width:auto;" onchange="loadAnalytics()">
        <option value="week">This Week</option>
        <option value="month" selected>This Month</option>
        <option value="year">This Year</option>
      </select>
      <button class="lis-btn lis-btn-outline" onclick="loadAnalytics()">
        <i class="fas fa-sync-alt"></i>
      </button>
    </div>
  </div>

  <!-- Summary KPIs -->
  <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:24px;" class="lis-fade-up-1">
    <div class="lis-kpi-card" style="padding:16px;">
      <div class="lis-kpi-icon c-green" style="width:40px;height:40px;font-size:1rem;"><i class="fas fa-flask"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="an-total" style="font-size:1.5rem;">—</div>
        <div class="lis-kpi-label">Total Orders</div>
      </div>
    </div>
    <div class="lis-kpi-card" style="padding:16px;">
      <div class="lis-kpi-icon c-emerald" style="width:40px;height:40px;font-size:1rem;"><i class="fas fa-check"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="an-completed" style="font-size:1.5rem;">—</div>
        <div class="lis-kpi-label">Completed</div>
      </div>
    </div>
    <div class="lis-kpi-card" style="padding:16px;">
      <div class="lis-kpi-icon c-amber" style="width:40px;height:40px;font-size:1rem;"><i class="fas fa-hourglass"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="an-pending" style="font-size:1.5rem;">—</div>
        <div class="lis-kpi-label">Pending</div>
      </div>
    </div>
    <div class="lis-kpi-card" style="padding:16px;">
      <div class="lis-kpi-icon c-red" style="width:40px;height:40px;font-size:1rem;"><i class="fas fa-bolt"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="an-urgent" style="font-size:1.5rem;">—</div>
        <div class="lis-kpi-label">Urgent</div>
      </div>
    </div>
    <div class="lis-kpi-card" style="padding:16px;">
      <div class="lis-kpi-icon c-mint" style="width:40px;height:40px;font-size:1rem;"><i class="fas fa-users"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="an-patients" style="font-size:1.5rem;">—</div>
        <div class="lis-kpi-label">Patients</div>
      </div>
    </div>
  </div>

  <!-- Charts Row 1 -->
  <div class="lis-grid-2 lis-fade-up-2">

    <!-- Weekly Trend -->
    <div class="lis-card">
      <div class="lis-card-header">
        <div class="lis-card-title"><i class="fas fa-chart-line"></i> 7-Day Order Trend</div>
      </div>
      <div class="lis-card-body" style="padding:16px 20px;">
        <div style="position:relative;height:220px;">
          <canvas id="anTrendChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Service Breakdown -->
    <div class="lis-card">
      <div class="lis-card-header">
        <div class="lis-card-title"><i class="fas fa-chart-pie"></i> Service Category Breakdown</div>
      </div>
      <div class="lis-card-body" style="display:flex;align-items:center;gap:24px;padding:20px;">
        <div style="position:relative;height:180px;width:180px;flex-shrink:0;">
          <canvas id="anPieChart"></canvas>
        </div>
        <div id="anPieLegend" style="flex:1;display:flex;flex-direction:column;gap:10px;"></div>
      </div>
    </div>

  </div>

  <!-- Charts Row 2 -->
  <div class="lis-grid-2 lis-fade-up-3">

    <!-- Top Tests -->
    <div class="lis-card">
      <div class="lis-card-header">
        <div class="lis-card-title"><i class="fas fa-trophy"></i> Most Ordered Tests</div>
      </div>
      <div class="lis-card-body" style="padding:14px 18px;">
        <div style="position:relative;height:240px;">
          <canvas id="anTopChart"></canvas>
        </div>
      </div>
    </div>

    <!-- TAT Performance -->
    <div class="lis-card">
      <div class="lis-card-header">
        <div class="lis-card-title"><i class="fas fa-stopwatch"></i> Performance Metrics</div>
      </div>
      <div class="lis-card-body">
        <div style="display:flex;flex-direction:column;gap:18px;" id="anMetrics">
          <!-- Loaded dynamically -->
          <div style="display:flex;flex-direction:column;gap:10px;">
            <?php foreach(['Overall Completion Rate','Lab Test Rate','Radiology Rate','Urgent TAT'] as $m): ?>
            <div>
              <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                <span style="font-size:0.78rem;font-weight:700;color:var(--lis-text);"><?= $m ?></span>
                <span class="lis-skeleton" style="width:40px;height:16px;border-radius:4px;"></span>
              </div>
              <div class="lis-progress-bar-wrap">
                <div class="lis-progress-bar-fill" style="width:0%;transition:width 1s ease;"></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- Recent Activity Table -->
  <div class="lis-card lis-fade-up-4">
    <div class="lis-card-header">
      <div class="lis-card-title"><i class="fas fa-table"></i> Recent Orders Overview</div>
      <a href="reports.php" class="lis-btn lis-btn-outline lis-btn-sm">Full Report</a>
    </div>
    <div class="lis-table-wrap">
      <table class="lis-table" id="anTable">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Test(s)</th>
            <th>Patient</th>
            <th>Doctor</th>
            <th>Date</th>
            <th>Priority</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody id="anTableBody">
          <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--lis-text-muted);">
            <div class="lis-spinner" style="margin:0 auto 8px;"></div>
            Loading analytics data...
          </td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div><!-- /.lis-content -->

<?php require_once 'includes/lab_foot.php'; ?>

<script>
let anTrendInst = null;
let anPieInst   = null;
let anTopInst   = null;

async function loadAnalytics() {
  try {
    const data = await lisApi('GET', '/api/laboratory/dashboard');
    if (!data.success) return;

    const payload = data.data || data;
    const s = payload.stats || {};

    // KPIs
    lisCountUp(document.getElementById('an-total'),    s.orders_today);
    lisCountUp(document.getElementById('an-completed'),s.completed_today);
    lisCountUp(document.getElementById('an-pending'),  s.pending);
    lisCountUp(document.getElementById('an-urgent'),   s.urgent_today);
    lisCountUp(document.getElementById('an-patients'), s.month_patients);

    buildAnTrend(payload.trend || []);
    buildAnPie(s);
    buildAnTop(payload.top_tests || []);
    buildAnMetrics(s);

    // Load orders table
    const ordersData = await lisApi('GET', '/api/laboratory/orders?all=1');
    if (ordersData.success) renderAnTable(ordersData.data || []);

  } catch(e) {
    lisToast('Failed to load analytics', 'error');
  }
}

function buildAnTrend(trend) {
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
  if (anTrendInst) anTrendInst.destroy();
  const ctx = document.getElementById('anTrendChart').getContext('2d');
  const gradient = ctx.createLinearGradient(0,0,0,220);
  gradient.addColorStop(0,'rgba(31,107,74,0.2)');
  gradient.addColorStop(1,'rgba(31,107,74,0.01)');
  anTrendInst = new Chart(ctx, {
    type: 'line',
    data: { labels, datasets: [{ label:'Orders', data: values, borderColor:'#1f6b4a', backgroundColor: gradient, borderWidth:2.5, tension:0.4, fill:true, pointBackgroundColor:'#fff', pointBorderColor:'#1f6b4a', pointBorderWidth:2.5, pointRadius:5 }] },
    options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:false} }, scales: { x:{ grid:{display:false}, ticks:{font:{family:'Inter',size:10},color:'#94a3b8'} }, y:{ beginAtZero:true, grid:{color:'#f1ede6'}, ticks:{font:{family:'Inter',size:10},stepSize:1,color:'#94a3b8'} } } }
  });
}

function buildAnPie(s) {
  const lab  = s.lab_services  || 0;
  const rad  = s.radiology     || 0;
  const oth  = s.other         || 0;
  const total= lab + rad + oth || 1;

  if (anPieInst) anPieInst.destroy();
  const ctx = document.getElementById('anPieChart').getContext('2d');
  anPieInst = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Lab Tests', 'Radiology', 'Other'],
      datasets: [{ data:[lab,rad,oth], backgroundColor:['#1f6b4a','#0891b2','#d97706'], borderWidth:0, hoverOffset:6 }]
    },
    options: { responsive:true, maintainAspectRatio:false, cutout:'70%', plugins:{ legend:{display:false} } }
  });

  const legend = document.getElementById('anPieLegend');
  const colors = ['#1f6b4a','#0891b2','#d97706'];
  const labels = ['Lab Tests','Radiology','Other'];
  const vals   = [lab,rad,oth];
  legend.innerHTML = labels.map((l,i) => `
    <div style="display:flex;align-items:center;gap:10px;">
      <div style="width:12px;height:12px;border-radius:4px;background:${colors[i]};flex-shrink:0;"></div>
      <div>
        <div style="font-size:0.78rem;font-weight:700;color:var(--lis-text);">${l}</div>
        <div style="font-size:0.68rem;color:var(--lis-text-muted);">${vals[i]} services (${Math.round((vals[i]/total)*100)}%)</div>
      </div>
    </div>`).join('');
}

function buildAnTop(tests) {
  if (anTopInst) anTopInst.destroy();
  if (!tests.length) return;
  const ctx = document.getElementById('anTopChart').getContext('2d');
  const greens = ['#144d34','#1f6b4a','#2a8c62','#36a978','#4ec491','#71d6aa','#9cf2cd','#cbfaf1'];
  anTopInst = new Chart(ctx, {
    type:'bar',
    data: { labels: tests.map(t => { const n=String(t.test_name||''); return n.length>16?n.slice(0,14)+'…':n; }), datasets:[{ data:tests.map(t=>parseInt(t.cnt)), backgroundColor:greens.slice(0,tests.length), borderRadius:6, borderSkipped:false }] },
    options: { indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ x:{beginAtZero:true,grid:{color:'#f1ede6'},ticks:{font:{family:'Inter',size:9},stepSize:1}}, y:{grid:{display:false},ticks:{font:{family:'Inter',size:9}}} } }
  });
}

function buildAnMetrics(s) {
  const total     = s.orders_today || 1;
  const completed = s.completed_today || 0;
  const urgent    = s.urgent_today || 0;
  const lab       = s.lab_services || 0;
  const rad       = s.radiology || 0;

  const metrics = [
    { label:'Overall Completion Rate', value: Math.round((completed/total)*100), color:'#1f6b4a' },
    { label:'Lab Tests Available', value: Math.min(Math.round((lab/(lab+rad||1))*100), 100), color:'#059669' },
    { label:'Radiology Services', value: Math.min(Math.round((rad/(lab+rad||1))*100), 100), color:'#0891b2' },
    { label:'Urgent Cases Today', value: Math.min(Math.round((urgent/total)*100)*3, 100), color:'#dc2626' },
  ];

  document.getElementById('anMetrics').innerHTML = `<div style="display:flex;flex-direction:column;gap:16px;">
    ${metrics.map(m => `<div>
      <div style="display:flex;justify-content:space-between;margin-bottom:7px;">
        <span style="font-size:0.78rem;font-weight:700;color:var(--lis-text);">${m.label}</span>
        <span style="font-size:0.78rem;font-weight:800;color:${m.color};">${m.value}%</span>
      </div>
      <div class="lis-progress-bar-wrap">
        <div class="lis-progress-bar-fill" style="width:${m.value}%;background:${m.color};transition:width 1s ease;"></div>
      </div>
    </div>`).join('')}
  </div>`;
}

function renderAnTable(orders) {
  const tbody = document.getElementById('anTableBody');
  if (!orders.length) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--lis-text-muted);">No orders found</td></tr>';
    return;
  }
  tbody.innerHTML = orders.slice(0, 20).map(o => {
    const tests = (() => { try { const a = JSON.parse(o.test_name); return Array.isArray(a) ? a.slice(0,2).join(', ')+(a.length>2?'…':'') : (o.test_name||'').slice(0,40); } catch { return (o.test_name||'').slice(0,40); } })();
    return `<tr>
      <td><span class="lis-code">${escHtml(String(o.order_id).slice(-10))}</span></td>
      <td style="font-weight:700;max-width:180px;" class="lis-truncate">${escHtml(tests)}</td>
      <td>
        <div style="display:flex;align-items:center;gap:8px;">
          <div class="lis-kanban-avatar" style="width:28px;height:28px;font-size:0.62rem;">${lisInitials(o.patient_name)}</div>
          <span style="font-size:0.8rem;font-weight:600;">${escHtml(o.patient_name||'—')}</span>
        </div>
      </td>
      <td style="font-size:0.78rem;">Dr. ${escHtml(o.doctor_name||'—')}</td>
      <td style="font-size:0.75rem;color:var(--lis-text-muted);">${escHtml(o.order_date||'—')}</td>
      <td>${lisPriorityBadge(o.priority)}</td>
      <td>${lisStatusBadge(o.status)}</td>
    </tr>`;
  }).join('');
}

loadAnalytics();
</script>
