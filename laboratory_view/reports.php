<?php
$pageTitle = 'Reports & Analytics';
$pageIcon  = 'fa-chart-bar';
$navTitle  = 'Lab Reports';
$navSub    = 'Volume, category and order analytics';
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
        <div class="lis-page-title-icon"><i class="fas fa-chart-bar"></i></div>
        <div>Reports & Analytics
          <div class="lis-page-subtitle">Laboratory order volume, trends and test statistics</div>
        </div>
      </div>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
      <select class="lis-input lis-select" id="date-range" onchange="loadReports()" style="max-width:180px;">
        <option value="today">Today</option>
        <option value="week">This Week</option>
        <option value="month" selected>This Month</option>
        <option value="all">All Time</option>
      </select>
      <button class="lis-btn lis-btn-outline" onclick="loadReports()">
        <i class="fas fa-sync-alt"></i> Refresh
      </button>
      <button class="lis-btn lis-btn-primary" onclick="exportToCSV()">
        <i class="fas fa-file-csv"></i> Export CSV
      </button>
      <button class="lis-btn lis-btn-outline" onclick="window.print()">
        <i class="fas fa-print"></i> Print
      </button>
    </div>
  </div>

  <!-- KPI Row -->
  <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:20px;" class="lis-fade-up-1" id="report-kpis">
    <div class="lis-kpi-card">
      <div class="lis-kpi-icon teal"><i class="fas fa-flask"></i></div>
      <div class="lis-kpi-info"><div class="lis-kpi-value" id="rpt-total">—</div><div class="lis-kpi-label">Total Orders</div></div>
      <i class="fas fa-flask lis-kpi-bg-icon"></i>
    </div>
    <div class="lis-kpi-card">
      <div class="lis-kpi-icon red"><i class="fas fa-bolt"></i></div>
      <div class="lis-kpi-info"><div class="lis-kpi-value" id="rpt-urgent">—</div><div class="lis-kpi-label">Urgent</div></div>
      <i class="fas fa-bolt lis-kpi-bg-icon"></i>
    </div>
    <div class="lis-kpi-card">
      <div class="lis-kpi-icon amber"><i class="fas fa-hourglass-half"></i></div>
      <div class="lis-kpi-info"><div class="lis-kpi-value" id="rpt-pending">—</div><div class="lis-kpi-label">Pending</div></div>
      <i class="fas fa-hourglass-half lis-kpi-bg-icon"></i>
    </div>
    <div class="lis-kpi-card">
      <div class="lis-kpi-icon green"><i class="fas fa-check-circle"></i></div>
      <div class="lis-kpi-info"><div class="lis-kpi-value" id="rpt-completed">—</div><div class="lis-kpi-label">Completed</div></div>
      <i class="fas fa-check-circle lis-kpi-bg-icon"></i>
    </div>
    <div class="lis-kpi-card">
      <div class="lis-kpi-icon" style="background:#e0e7ff;color:#4f46e5;"><i class="fas fa-stopwatch"></i></div>
      <div class="lis-kpi-info"><div class="lis-kpi-value" id="rpt-tat">—</div><div class="lis-kpi-label">Avg TAT (hrs)</div></div>
      <i class="fas fa-stopwatch lis-kpi-bg-icon"></i>
    </div>
  </div>

  <!-- Charts Row 1 -->
  <div class="lis-grid-2 lis-fade-up-2" style="margin-bottom: 20px;">
    <!-- Daily Trend -->
    <div class="lis-card">
      <div class="lis-card-header">
        <div class="lis-card-title"><i class="fas fa-chart-area"></i> Daily Order Trend</div>
      </div>
      <div class="lis-card-body" style="padding:16px 20px;">
        <div style="position:relative;height:240px;"><canvas id="rptTrendChart"></canvas></div>
      </div>
    </div>

    <!-- Top Tests -->
    <div class="lis-card">
      <div class="lis-card-header">
        <div class="lis-card-title"><i class="fas fa-flask"></i> Top 10 Ordered Tests</div>
      </div>
      <div class="lis-card-body" style="padding:16px 20px;">
        <div style="position:relative;height:240px;"><canvas id="rptTopChart"></canvas></div>
      </div>
    </div>
  </div>

  <!-- Charts Row 2 -->
  <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:20px;" class="lis-fade-up-2">
    <!-- Top Doctors -->
    <div class="lis-card">
      <div class="lis-card-header">
        <div class="lis-card-title"><i class="fas fa-user-md"></i> Top Referring Doctors</div>
      </div>
      <div class="lis-card-body" style="padding:16px 20px;">
        <div style="position:relative;height:220px;"><canvas id="rptDocChart"></canvas></div>
      </div>
    </div>

    <!-- Status Breakdown -->
    <div class="lis-card">
      <div class="lis-card-header">
        <div class="lis-card-title"><i class="fas fa-chart-pie"></i> Status Breakdown</div>
      </div>
      <div class="lis-card-body" style="padding:16px 20px;">
        <div style="position:relative;height:220px;"><canvas id="rptStatusChart"></canvas></div>
      </div>
    </div>

    <!-- Demographics -->
    <div class="lis-card">
      <div class="lis-card-header">
        <div class="lis-card-title"><i class="fas fa-users"></i> Patient Demographics</div>
      </div>
      <div class="lis-card-body" style="padding:16px 20px;">
        <div style="position:relative;height:220px;"><canvas id="rptDemoChart"></canvas></div>
      </div>
    </div>
  </div>

  <!-- Orders Table -->
  <div class="lis-card lis-fade-up-3">
    <div class="lis-card-header">
      <div class="lis-card-title"><i class="fas fa-table"></i> Detailed Orders
        <span id="rpt-count-badge" style="background:#e0f2fe;color:#1f6b4a;font-size:0.65rem;font-weight:800;padding:2px 8px;border-radius:20px;margin-left:6px;">0</span>
      </div>
    </div>
    <div class="lis-card-body" style="padding:0;">
      <div id="rpt-loading" style="display:flex;align-items:center;justify-content:center;gap:10px;padding:40px;color:var(--lis-text-muted);">
        <div class="lis-spinner"></div> Loading report...
      </div>
      <div class="lis-table-wrap" id="rpt-table-wrap" style="display:none;">
        <table class="lis-table">
          <thead><tr>
            <th>#</th><th>Order ID</th><th>Patient</th><th>Test</th><th>Doctor</th>
            <th>Priority</th><th>Status</th><th>Date</th>
          </tr></thead>
          <tbody id="rpt-tbody"></tbody>
        </table>
      </div>
      <div class="lis-empty" id="rpt-empty" style="display:none;">
        <i class="fas fa-chart-bar"></i>
        <div class="lis-empty-title">No orders in this period</div>
      </div>
    </div>
  </div>

</div><!-- /.lis-content -->
<?php require_once 'includes/lab_foot.php'; ?>

<script>
let rptTrend = null, rptTop = null, rptDoc = null, rptStatus = null, rptDemo = null;
let currentReportData = [];

async function loadReports() {
  const range = document.getElementById('date-range').value;
  document.getElementById('rpt-loading').style.display   = 'flex';
  document.getElementById('rpt-table-wrap').style.display= 'none';
  document.getElementById('rpt-empty').style.display     = 'none';

  // Determine all=1 or date range
  let url = '/api/laboratory/orders?all=1';

  try {
    const data = await lisApi('GET', url);
    const orders = data.data || [];

    // Apply date filter client-side
    const today = new Date();
    const filtered = orders.filter(o => {
      if (!o.order_date) return false;
      const d = new Date(o.order_date);
      if (range === 'today') return d.toDateString() === today.toDateString();
      if (range === 'week') {
        const weekAgo = new Date(today); weekAgo.setDate(today.getDate()-7);
        return d >= weekAgo;
      }
      if (range === 'month') {
        return d.getMonth() === today.getMonth() && d.getFullYear() === today.getFullYear();
      }
      return true; // all
    });

    document.getElementById('rpt-loading').style.display = 'none';

    // KPIs
    const urgent    = filtered.filter(o => o.priority === 'Urgent').length;
    const pending   = filtered.filter(o => o.status   === 'Ordered').length;
    const completed = filtered.filter(o => ['Completed','Reported'].includes(o.status)).length;
    lisCountUp(document.getElementById('rpt-total'),     filtered.length);
    lisCountUp(document.getElementById('rpt-urgent'),    urgent);
    lisCountUp(document.getElementById('rpt-pending'),   pending);
    lisCountUp(document.getElementById('rpt-completed'), completed);

    // TAT Calculation (in hours)
    let totalTatHrs = 0;
    let tatCount = 0;
    filtered.forEach(o => {
      if (['Completed', 'Reported'].includes(o.status) && o.order_date && o.updated_at) {
        // If order_time exists, combine it. Otherwise just use order_date
        const startTime = o.order_time ? `${o.order_date}T${o.order_time}` : `${o.order_date}T00:00:00`;
        const start = new Date(startTime).getTime();
        const end = new Date(o.updated_at).getTime();
        if (!isNaN(start) && !isNaN(end) && end >= start) {
          totalTatHrs += (end - start) / (1000 * 60 * 60);
          tatCount++;
        }
      }
    });
    const avgTat = tatCount > 0 ? (totalTatHrs / tatCount).toFixed(1) : 0;
    document.getElementById('rpt-tat').textContent = avgTat;
    
    currentReportData = filtered;

    // Daily trend from filtered
    buildRptTrend(filtered, range);

    // Top tests
    const testCounts = {};
    filtered.forEach(o => { 
      let tNames = [];
      try { tNames = JSON.parse(o.test_name); } catch(e) { tNames = [o.test_name]; }
      if (!Array.isArray(tNames)) tNames = [tNames];
      tNames.forEach(t => {
        if(t) testCounts[t] = (testCounts[t]||0)+1;
      });
    });
    const top = Object.entries(testCounts).sort((a,b)=>b[1]-a[1]).slice(0,10);
    buildRptTop(top);

    // Build New Charts
    buildRptDoc(filtered);
    buildRptStatus(filtered);
    buildRptDemo(filtered);

    // Table
    document.getElementById('rpt-count-badge').textContent = filtered.length;
    if (!filtered.length) {
      document.getElementById('rpt-empty').style.display = 'block';
      return;
    }
    document.getElementById('rpt-table-wrap').style.display = 'block';
    document.getElementById('rpt-tbody').innerHTML = filtered.map((o,i) => {
      const priCls  = {Urgent:'lis-badge-urgent',Stat:'lis-badge-stat',Routine:'lis-badge-routine'}[o.priority]||'lis-badge-routine';
      const statCls = {'Ordered':'lis-badge-ordered','In Progress':'lis-badge-progress','Completed':'lis-badge-completed','Reported':'lis-badge-reported'}[o.status]||'lis-badge-ordered';
      
      let displayTestName = o.test_name;
      try {
        const parsed = JSON.parse(o.test_name);
        if (Array.isArray(parsed)) displayTestName = parsed.join(', ');
      } catch(e) {}

      return `<tr>
        <td style="color:var(--lis-text-muted);">${i+1}</td>
        <td><code style="font-size:0.68rem;background:#f1f5f9;padding:2px 6px;border-radius:5px;">${escHtml(o.order_id)}</code></td>
        <td style="font-weight:700;">${escHtml(o.patient_name||'—')}</td>
        <td style="font-weight:700;">${escHtml(displayTestName)}</td>
        <td style="font-size:0.78rem;">${escHtml(o.doctor_name||'—')}</td>
        <td><span class="lis-badge ${priCls}">${escHtml(o.priority||'Routine')}</span></td>
        <td><span class="lis-badge ${statCls}">${escHtml(o.status)}</span></td>
        <td style="font-size:0.75rem;color:var(--lis-text-muted);">${o.order_date||'—'}</td>
      </tr>`;
    }).join('');

  } catch(e) {
    document.getElementById('rpt-loading').style.display = 'none';
    lisToast('Failed to load report data', 'error');
  }
}

function buildRptTrend(orders, range) {
  if (rptTrend) rptTrend.destroy();
  const dayMap = {};
  orders.forEach(o => {
    const d = (o.order_date||'').slice(0,10);
    if (d) dayMap[d] = (dayMap[d]||0)+1;
  });
  const days = Object.keys(dayMap).sort();
  const vals = days.map(d => dayMap[d]);
  const labels = days.map(d => {
    const dt = new Date(d);
    return dt.toLocaleDateString('en-US',{month:'short',day:'numeric'});
  });

  const ctx = document.getElementById('rptTrendChart').getContext('2d');
  const grad = ctx.createLinearGradient(0,0,0,240);
  grad.addColorStop(0, 'rgba(31,107,74,0.3)');
  grad.addColorStop(1, 'rgba(31,107,74,0.01)');

  rptTrend = new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels.length ? labels : ['No data'],
      datasets: [{ label:'Orders', data: vals, borderColor:'#1f6b4a', backgroundColor: grad,
        borderWidth:2.5, tension:0.4, fill:true, pointBackgroundColor:'#1f6b4a', pointRadius:3 }]
    },
    options: { responsive:true, maintainAspectRatio:false,
      plugins:{ legend:{ display:false } },
      scales: { x:{grid:{display:false},ticks:{font:{family:'Inter',size:10}}},
                y:{beginAtZero:true, grid:{color:'#f1f5f9'}, ticks:{font:{family:'Inter',size:10},stepSize:1}} } }
  });
}

function buildRptTop(top) {
  if (rptTop) rptTop.destroy();
  if (!top.length) return;
  const ctx = document.getElementById('rptTopChart').getContext('2d');
  const colors = ['#1f6b4a','#2a8c62','#06b6d4','#22d3ee','#67e8f9','#a5f3fc','#cffafe','#e0f7fa','#b2ebf2','#80deea'];
  rptTop = new Chart(ctx, {
    type:'bar', data: {
      labels: top.map(t=>t[0]),
      datasets:[{ label:'Orders', data:top.map(t=>t[1]), backgroundColor:colors, borderRadius:5, borderSkipped:false }]
    },
    options:{ indexAxis:'y', responsive:true, maintainAspectRatio:false,
      plugins:{ legend:{display:false} },
      scales:{ x:{beginAtZero:true,grid:{color:'#f1f5f9'},ticks:{font:{family:'Inter',size:10},stepSize:1}},
               y:{grid:{display:false},ticks:{font:{family:'Inter',size:10}}} } }
  });
}

function buildRptDoc(orders) {
  if (rptDoc) rptDoc.destroy();
  const docCounts = {};
  orders.forEach(o => {
    const doc = o.doctor_name || 'Self/Walk-in';
    docCounts[doc] = (docCounts[doc]||0)+1;
  });
  const top = Object.entries(docCounts).sort((a,b)=>b[1]-a[1]).slice(0,5);
  if (!top.length) return;
  
  const ctx = document.getElementById('rptDocChart').getContext('2d');
  rptDoc = new Chart(ctx, {
    type:'bar', data: {
      labels: top.map(t=>t[0]),
      datasets:[{ label:'Orders', data:top.map(t=>t[1]), backgroundColor:'#1f6b4a', borderRadius:4 }]
    },
    options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:false} },
      scales:{ x:{grid:{display:false},ticks:{font:{family:'Inter',size:10}}},
               y:{beginAtZero:true,grid:{color:'#f1f5f9'},ticks:{font:{family:'Inter',size:10},stepSize:1}} } }
  });
}

function buildRptStatus(orders) {
  if (rptStatus) rptStatus.destroy();
  const st = { 'Ordered':0, 'In Progress':0, 'Completed':0, 'Reported':0 };
  orders.forEach(o => { if(st[o.status] !== undefined) st[o.status]++; });
  
  const ctx = document.getElementById('rptStatusChart').getContext('2d');
  rptStatus = new Chart(ctx, {
    type:'doughnut', data: {
      labels: ['Ordered','In Progress','Completed','Reported'],
      datasets:[{ data:[st['Ordered'],st['In Progress'],st['Completed'],st['Reported']],
                  backgroundColor:['#94a3b8','#f59e0b','#10b981','#1f6b4a'], borderWidth:0 }]
    },
    options:{ responsive:true, maintainAspectRatio:false, cutout:'70%',
      plugins:{ legend:{position:'right',labels:{font:{family:'Inter',size:10},usePointStyle:true,boxWidth:8}} } }
  });
}

function buildRptDemo(orders) {
  if (rptDemo) rptDemo.destroy();
  const ageGroups = { '0-18':0, '19-40':0, '41-60':0, '60+':0 };
  orders.forEach(o => {
    let age = parseInt(o.age);
    if (!isNaN(age)) {
      if (age <= 18) ageGroups['0-18']++;
      else if (age <= 40) ageGroups['19-40']++;
      else if (age <= 60) ageGroups['41-60']++;
      else ageGroups['60+']++;
    }
  });
  
  const ctx = document.getElementById('rptDemoChart').getContext('2d');
  rptDemo = new Chart(ctx, {
    type:'bar', data: {
      labels: Object.keys(ageGroups),
      datasets:[{ label:'Patients', data:Object.values(ageGroups), backgroundColor:'#38bdf8', borderRadius:4 }]
    },
    options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:false} },
      scales:{ x:{grid:{display:false},ticks:{font:{family:'Inter',size:10}}},
               y:{beginAtZero:true,grid:{color:'#f1f5f9'},ticks:{font:{family:'Inter',size:10},stepSize:1}} } }
  });
}

function exportToCSV() {
  if (!currentReportData || currentReportData.length === 0) {
    lisToast('No data to export', 'warning');
    return;
  }
  
  const headers = ['Order ID', 'Patient Name', 'Age', 'Gender', 'Phone', 'Doctor', 'Tests', 'Priority', 'Status', 'Order Date', 'Order Time'];
  const rows = [headers];
  
  currentReportData.forEach(o => {
    let displayTestName = o.test_name;
    try {
      const parsed = JSON.parse(o.test_name);
      if (Array.isArray(parsed)) displayTestName = parsed.join('; ');
    } catch(e) {}
    
    const row = [
      o.order_id,
      o.patient_name || 'Walk-in',
      o.age || '',
      o.sex || '',
      o.phone || '',
      o.doctor_name || '',
      displayTestName,
      o.priority,
      o.status,
      o.order_date,
      o.order_time || ''
    ];
    // Escape quotes and wrap in quotes for CSV
    rows.push(row.map(val => '"' + String(val||'').replace(/"/g, '""') + '"'));
  });
  
  const csvContent = "data:text/csv;charset=utf-8," + rows.map(e => e.join(",")).join("\\n");
  const encodedUri = encodeURI(csvContent);
  const link = document.createElement("a");
  link.setAttribute("href", encodedUri);
  link.setAttribute("download", "Lab_Orders_Report_" + new Date().toISOString().slice(0,10) + ".csv");
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

function escHtml(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

loadReports();
</script>
