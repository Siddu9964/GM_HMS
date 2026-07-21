<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
require_once 'includes/db.php';
$pageTitle = 'Dashboard';
include 'includes/ph_head.php';
?>
<style>
/* ═══════════════════════════════════════════
   DASHBOARD — PREMIUM REDESIGN
   ═══════════════════════════════════════════ */

.dash-hero {
    background: #f3efe6;
    padding: 1rem 1.75rem;
    position: relative;
    flex-shrink: 0;
}
.hero-inner { position: relative; z-index: 1; }
.hero-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.hero-greeting { color: #64748b; font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; margin-bottom: .2rem; }
.hero-name { font-size: 1.4rem; font-weight: 800; color: #0f172a; letter-spacing: -.4px; line-height: 1.1; }
.hero-date { font-size: .7rem; color: #64748b; margin-top: .2rem; }
.hero-actions { display: flex; gap: .6rem; align-items: center; flex-shrink: 0; }
.hero-btn {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .55rem 1.1rem; border-radius: 10px;
    font-size: .78rem; font-weight: 700;
    cursor: pointer; text-decoration: none;
    transition: all .2s;
}
.hero-btn-primary {
    background: #1f6b4a; color: #fff;
    border: none;
    box-shadow: 0 4px 14px rgba(31,107,74,.2);
}
.hero-btn-primary:hover { background: #164e33; color: #fff; transform: translateY(-1px); box-shadow: 0 8px 20px rgba(31,107,74,.25); }
.hero-btn-ghost {
    background: transparent;
    color: #1f6b4a; border: 1.5px solid #1f6b4a;
}
.hero-btn-ghost:hover { background: rgba(31,107,74,.05); color: #1f6b4a; }
.live-pill {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.3);
    padding: .3rem .75rem; border-radius: 99px;
    font-size: .62rem; font-weight: 800; color: #059669; letter-spacing: .05em;
}
.live-dot { width: 6px; height: 6px; border-radius: 50%; background: #10b981; animation: blink 1.4s infinite; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }
.clock-txt { font-size: .82rem; font-weight: 700; color: #0f172a; }

/* KPI Strip — all 8 cards in one horizontal row */
.kpi-strip {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: .6rem;
}
.kpi-card {
    background: #fff;
    border-radius: 12px;
    padding: .75rem .85rem;
    box-shadow: 0 2px 10px rgba(15,23,42,.07);
    border: 1px solid rgba(226,232,240,.8);
    border-top: 3px solid var(--kc, #1f6b4a);
    transition: all .22s cubic-bezier(.4,0,.2,1);
    display: flex; flex-direction: column; gap: .2rem;
    min-width: 0;
}
.kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(15,23,42,.12); }
.kpi-icon {
    width: 28px; height: 28px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: .75rem; flex-shrink: 0; margin-bottom: .3rem;
    background: var(--ki-bg, rgba(31,107,74,.1));
    color: var(--kc, #1f6b4a);
}
.kpi-val { font-size: 1.15rem; font-weight: 900; color: #0f172a; letter-spacing: -.5px; line-height: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.kpi-lbl { font-size: .56rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .06em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.kpi-link { font-size: .56rem; font-weight: 700; color: var(--kc, #1f6b4a); text-decoration: none; margin-top: .15rem; display: inline-block; }
.kpi-link:hover { text-decoration: underline; }

/* Dashboard body */
.dash-body {
    padding: 1.25rem 2rem 2.5rem;
    background: var(--ph-bg);
    display: grid;
    gap: 1.25rem;
    flex-shrink: 0;
}

/* Section title */
.section-hdr {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: .85rem;
}
.section-title {
    font-size: .8rem; font-weight: 800; color: #0f172a;
    text-transform: uppercase; letter-spacing: .08em;
    display: flex; align-items: center; gap: .5rem;
}
.section-title i { color: var(--ph-primary); }
.section-link {
    font-size: .7rem; font-weight: 700; color: var(--ph-primary);
    text-decoration: none; display: flex; align-items: center; gap: .25rem;
}
.section-link:hover { text-decoration: underline; }

/* Grid rows */
.mid-row { display: grid; grid-template-columns: 1fr 300px; gap: 1.25rem; }
.bot-row { display: grid; grid-template-columns: 1fr 1fr 260px; gap: 1.25rem; }

/* Panel */
.panel {
    background: #fff;
    border: 1px solid var(--ph-border);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(15,23,42,.06);
}
.panel-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: .9rem 1.25rem;
    border-bottom: 1px solid var(--ph-border);
    background: #fafbfc;
}
.panel-title {
    display: flex; align-items: center; gap: .5rem;
    font-size: .78rem; font-weight: 800; color: #0f172a;
}
.panel-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.panel-action {
    font-size: .65rem; font-weight: 700; color: #64748b;
    background: #f1f5f9; border: 1px solid var(--ph-border);
    padding: 4px 10px; border-radius: 6px; text-decoration: none; transition: .2s;
}
.panel-action:hover { color: var(--ph-primary); border-color: var(--ph-primary); background: rgba(31,107,74,.05); }
.panel-body { padding: 1.1rem 1.25rem; }

/* Stock Donut legend */
.stock-legend { padding: .5rem 1.25rem 1rem; display: flex; flex-direction: column; gap: .5rem; }
.legend-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: .55rem .85rem; border-radius: 10px;
    background: var(--ph-bg); border: 1px solid var(--ph-border);
}
.legend-label { font-size: .72rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: .5rem; }
.legend-pip { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }
.legend-val { font-size: .95rem; font-weight: 900; color: #0f172a; }

/* Alert Feed */
.alert-feed { max-height: 300px; overflow-y: auto; }
.alert-feed::-webkit-scrollbar { width: 3px; }
.alert-feed::-webkit-scrollbar-thumb { background: var(--ph-border); border-radius: 4px; }
.alert-item {
    display: flex; align-items: center; gap: .75rem;
    padding: .75rem 1.25rem; border-bottom: 1px solid var(--ph-border);
    transition: .15s; cursor: pointer;
    border-left: 3px solid transparent;
}
.alert-item:last-child { border-bottom: none; }
.alert-item:hover { background: var(--ph-bg); }
.alert-item.a-low  { border-left-color: #ef4444; }
.alert-item.a-exp  { border-left-color: #f59e0b; }
.alert-icon {
    width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: .78rem;
}
.alert-name { font-size: .75rem; font-weight: 700; color: #0f172a; }
.alert-desc { font-size: .64rem; color: #64748b; margin-top: 1px; }
.alert-badge { margin-left: auto; flex-shrink: 0; font-size: .55rem; font-weight: 800; padding: 3px 8px; border-radius: 6px; text-transform: uppercase; letter-spacing: .3px; }

/* Top Products */
.product-item {
    display: flex; align-items: center; gap: .75rem;
    padding: .7rem 1.25rem; border-bottom: 1px solid var(--ph-border); transition: .15s;
}
.product-item:last-child { border-bottom: none; }
.product-item:hover { background: var(--ph-bg); }
.rank-badge {
    width: 26px; height: 26px; border-radius: 7px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: .62rem; font-weight: 900;
}
.rb-1 { background: rgba(245,158,11,.12); color: #b45309; }
.rb-2 { background: rgba(100,116,139,.1); color: #475569; }
.rb-3 { background: rgba(239,68,68,.1); color: #dc2626; }
.rb-n { background: var(--ph-bg); color: #64748b; }
.prod-bar-wrap { flex: 1; min-width: 0; }
.prod-name { font-size: .73rem; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.prod-bar-track { height: 4px; background: var(--ph-border); border-radius: 4px; margin-top: 5px; }
.prod-bar-fill  { height: 4px; border-radius: 4px; background: linear-gradient(90deg, #1f6b4a, #5de8f0); }
.prod-rev { font-size: .73rem; font-weight: 900; color: #1f6b4a; white-space: nowrap; }

/* Quick Actions */
.qa-grid { padding: .85rem; display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; }
.qa-btn {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: .55rem; padding: 1rem .5rem; border-radius: 12px;
    background: var(--ph-bg); border: 1.5px solid var(--ph-border);
    text-decoration: none; transition: all .22s cubic-bezier(.4,0,.2,1); cursor: pointer;
}
.qa-btn:hover {
    background: #fff; border-color: var(--qa-c, var(--ph-primary));
    box-shadow: 0 6px 18px rgba(15,23,42,.1); transform: translateY(-2px);
}
.qa-icon {
    width: 38px; height: 38px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center; font-size: .9rem;
}
.qa-lbl { font-size: .6rem; font-weight: 800; color: #64748b; text-align: center; text-transform: uppercase; letter-spacing: .3px; }

/* Responsive */
@media (max-width: 1280px) {
    .kpi-float-row { grid-template-columns: repeat(2, 1fr); }
    .mid-row, .bot-row { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .kpi-float-row { grid-template-columns: 1fr 1fr; padding: 0 1rem; margin-top: -1.25rem; }
    .dash-hero { padding: 1.5rem 1rem 3rem; }
    .dash-body { padding: 1rem; }
    .hero-name { font-size: 1.4rem; }
}

/* Force scrolling on this page (overrides any cached pharmacy.css) */
body {
    overflow-y: auto !important;
    overflow-x: hidden !important;
}
.ph-wrap, #ph-content {
    height: auto !important;
    min-height: 100vh !important;
    overflow: visible !important;
}
</style>

<div class="ph-wrap">
<?php include 'includes/pharmacy_sidebar.php'; ?>
<div id="ph-content">
<?php include 'includes/pharmacy_navbar.php'; ?>

<!-- ── Hero Banner ── -->
<div class="dash-hero">
  <div class="hero-inner">
    <div class="hero-top">
      <div>
        <div class="hero-greeting">Pharmacy Command Center</div>
        <div class="hero-name">Welcome back, <?= htmlspecialchars($_SESSION['username'] ?? 'Pharmacist') ?> 👋</div>
        <div class="hero-date"><?= date('l, d F Y') ?></div>
      </div>
      <div class="hero-actions">
        <div class="live-pill"><div class="live-dot"></div> LIVE</div>
        <div class="clock-txt" id="liveClock"></div>
        <a href="billing_pos.php" class="hero-btn hero-btn-primary"><i class="fas fa-cash-register"></i> New Sale</a>
        <a href="indent_request.php" class="hero-btn hero-btn-ghost"><i class="fas fa-clipboard-list"></i> Indent</a>
      </div>
    </div>
  </div>
</div>

<!-- ── Floating KPI Cards ── -->

<!-- ── Dashboard Body ── -->
<div class="dash-body">

  <!-- ── KPI Strip: all 8 stats in one horizontal row ── -->
  <div class="kpi-strip">
    <div class="kpi-card" style="--kc:#1f6b4a;--ki-bg:rgba(31,107,74,.1)">
      <div class="kpi-icon"><i class="fas fa-pills"></i></div>
      <div class="kpi-val" id="kpi-products">—</div>
      <div class="kpi-lbl">Total Products</div>
    </div>
    <div class="kpi-card" style="--kc:#f59e0b;--ki-bg:rgba(245,158,11,.1)">
      <div class="kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
      <div class="kpi-val" id="kpi-lowstock">—</div>
      <div class="kpi-lbl">Low Stock</div>
      <a href="inventory_alerts.php" class="kpi-link">View →</a>
    </div>
    <div class="kpi-card" style="--kc:#ef4444;--ki-bg:rgba(239,68,68,.1)">
      <div class="kpi-icon"><i class="fas fa-calendar-times"></i></div>
      <div class="kpi-val" id="kpi-expiry">—</div>
      <div class="kpi-lbl">Expiring Soon</div>
      <a href="inventory_alerts.php" class="kpi-link">View →</a>
    </div>
    <div class="kpi-card" style="--kc:#22c55e;--ki-bg:rgba(34,197,94,.1)">
      <div class="kpi-icon"><i class="fas fa-rupee-sign"></i></div>
      <div class="kpi-val" id="kpi-sales">—</div>
      <div class="kpi-lbl">Today's Sales</div>
    </div>
    <div class="kpi-card" style="--kc:#3b82f6;--ki-bg:rgba(59,130,246,.1)">
      <div class="kpi-icon"><i class="fas fa-chart-line"></i></div>
      <div class="kpi-val" id="kpi-month">—</div>
      <div class="kpi-lbl">Month Sales</div>
    </div>
    <div class="kpi-card" style="--kc:#8b5cf6;--ki-bg:rgba(139,92,246,.1)">
      <div class="kpi-icon"><i class="fas fa-clipboard-list"></i></div>
      <div class="kpi-val" id="kpi-indents">—</div>
      <div class="kpi-lbl">Pending Indents</div>
    </div>
    <div class="kpi-card" style="--kc:#1f6b4a;--ki-bg:rgba(31,107,74,.1)">
      <div class="kpi-icon"><i class="fas fa-truck"></i></div>
      <div class="kpi-val" id="kpi-suppliers">—</div>
      <div class="kpi-lbl">Suppliers</div>
    </div>
    <div class="kpi-card" style="--kc:#ec4899;--ki-bg:rgba(236,72,153,.1)">
      <div class="kpi-icon"><i class="fas fa-users"></i></div>
      <div class="kpi-val" id="kpi-customers">—</div>
      <div class="kpi-lbl">Customers</div>
    </div>
  </div>

  <!-- Mid Row: Chart + Stock Donut -->
  <div class="mid-row">
    <div class="panel">
      <div class="panel-head">
        <div class="panel-title"><div class="panel-dot" style="background:#1f6b4a;"></div> Sales Revenue — Last 7 Days</div>
        <a href="reports.php" class="panel-action">FULL REPORT →</a>
      </div>
      <div class="panel-body" style="height:220px;position:relative;">
        <canvas id="salesChart"></canvas>
      </div>
    </div>
    <div class="panel">
      <div class="panel-head">
        <div class="panel-title"><div class="panel-dot" style="background:#22c55e;"></div> Inventory Health</div>
      </div>
      <div class="panel-body" style="height:150px;position:relative;">
        <canvas id="stockChart"></canvas>
      </div>
      <div class="stock-legend">
        <div class="legend-row">
          <div class="legend-label"><div class="legend-pip" style="background:#22c55e;"></div> In Stock</div>
          <div class="legend-val" id="stock-in">—</div>
        </div>
        <div class="legend-row">
          <div class="legend-label"><div class="legend-pip" style="background:#f59e0b;"></div> Low Stock</div>
          <div class="legend-val" id="stock-low">—</div>
        </div>
        <div class="legend-row">
          <div class="legend-label"><div class="legend-pip" style="background:#ef4444;"></div> Out of Stock</div>
          <div class="legend-val" id="stock-out">—</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bottom Row: Alerts + Top Products + Quick Actions -->
  <div class="bot-row">
    <!-- Critical Alerts -->
    <div class="panel">
      <div class="panel-head">
        <div class="panel-title"><div class="panel-dot" style="background:#ef4444;box-shadow:0 0 6px #ef4444;"></div> Critical Alerts</div>
        <a href="inventory_alerts.php" class="panel-action">VIEW ALL →</a>
      </div>
      <div class="alert-feed" id="alertsList">
        <div style="text-align:center;padding:2.5rem;color:#64748b;font-size:.8rem;">
          <i class="fas fa-circle-notch fa-spin" style="font-size:1.2rem;color:#1f6b4a;margin-bottom:.6rem;display:block;"></i>
          Loading alerts…
        </div>
      </div>
    </div>

    <!-- Top Products -->
    <div class="panel">
      <div class="panel-head">
        <div class="panel-title"><div class="panel-dot" style="background:#f59e0b;"></div> Top Selling — This Month</div>
      </div>
      <div id="topProductsList" style="padding:4px 0;">
        <div style="text-align:center;padding:2.5rem;color:#64748b;font-size:.8rem;">
          <i class="fas fa-circle-notch fa-spin" style="font-size:1.2rem;color:#1f6b4a;margin-bottom:.6rem;display:block;"></i>
          Loading…
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="panel">
      <div class="panel-head">
        <div class="panel-title"><div class="panel-dot" style="background:#8b5cf6;"></div> Quick Actions</div>
      </div>
      <div class="qa-grid">
        <?php $actions = [
          ['billing_pos.php',   'fa-cash-register',  'New Sale',     '#1f6b4a'],
          ['products.php',      'fa-plus-circle',    'Add Product',  '#22c55e'],
          ['indent_request.php','fa-clipboard-list', 'Indent',       '#8b5cf6'],
          ['purchase_order.php','fa-shopping-cart',  'Purchase',     '#f59e0b'],
          ['stock_receive.php', 'fa-boxes',          'Receive',      '#3b82f6'],
          ['reports.php',       'fa-chart-pie',      'Reports',      '#ec4899'],
        ];
        foreach ($actions as $a): ?>
        <a href="<?= $a[0] ?>" class="qa-btn" style="--qa-c:<?= $a[3] ?>;">
          <div class="qa-icon" style="background:<?= $a[3] ?>18;color:<?= $a[3] ?>;"><i class="fas <?= $a[1] ?>"></i></div>
          <div class="qa-lbl"><?= $a[2] ?></div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</div><!-- /.dash-body -->
</div><!-- /#ph-content -->
</div><!-- /.ph-wrap -->

<?php include 'includes/ph_foot.php'; ?>
<script>
/* ── Live Clock ── */
(function tick() {
  document.getElementById('liveClock').textContent =
    new Date().toLocaleTimeString('en-IN', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
  setTimeout(tick, 1000);
})();

/* ── Count-Up ── */
function animateCount(el, to, prefix='') {
  if (!el) return;
  const dur = 1000, start = performance.now();
  (function step(now) {
    const p = Math.min((now - start) / dur, 1);
    const ease = 1 - Math.pow(1 - p, 3);
    el.textContent = prefix + Math.round(to * ease).toLocaleString('en-IN');
    if (p < 1) requestAnimationFrame(step);
  })(start);
}

/* ── Load Dashboard Data ── */
document.addEventListener('DOMContentLoaded', async () => {
  try {
    const json = await fetch('../api/pharmacy/dashboard').then(r => r.json());
    if (!json.success) throw new Error(json.error || 'Failed');
    const { stats, charts, recent_alerts, top_products } = json.data;

    /* KPI cards */
    animateCount(document.getElementById('kpi-products'),  +stats.total_products);
    animateCount(document.getElementById('kpi-lowstock'),   +stats.low_stock);
    animateCount(document.getElementById('kpi-expiry'),     +stats.expiry_soon);
    animateCount(document.getElementById('kpi-month'),      +stats.month_sales, '₹');
    animateCount(document.getElementById('kpi-indents'),    +stats.pending_indents);
    animateCount(document.getElementById('kpi-suppliers'),  +stats.total_suppliers);
    animateCount(document.getElementById('kpi-customers'),  +stats.total_customers);
    document.getElementById('kpi-sales').textContent = stats.today_sales_formatted;

    /* Stock Donut */
    const sd = charts.stock_distribution;
    animateCount(document.getElementById('stock-in'),  +sd.in_stock);
    animateCount(document.getElementById('stock-low'), +sd.low_stock);
    animateCount(document.getElementById('stock-out'), +sd.out_of_stock);

    new Chart(document.getElementById('stockChart'), {
      type: 'doughnut',
      data: {
        labels: ['In Stock','Low Stock','Out of Stock'],
        datasets: [{ data: [sd.in_stock, sd.low_stock, sd.out_of_stock],
          backgroundColor: ['#22c55e','#f59e0b','#ef4444'],
          borderWidth: 3, borderColor: '#fff', hoverOffset: 8 }]
      },
      options: { responsive:true, maintainAspectRatio:false, cutout:'76%',
        plugins: { legend:{display:false},
          tooltip: { callbacks:{ label: c => ` ${c.label}: ${c.parsed.toLocaleString('en-IN')}` }}}}
    });

    /* Sales Line Chart */
    const map = {};
    charts.sales_history.forEach(h => map[h.date] = h.total);
    const labels = [], vals = [];
    for (let i = 6; i >= 0; i--) {
      const d = new Date(); d.setDate(d.getDate() - i);
      labels.push(d.toLocaleDateString('en-IN', { weekday:'short', day:'2-digit' }));
      vals.push(parseFloat(map[d.toISOString().split('T')[0]] || 0));
    }
    const ctx = document.getElementById('salesChart').getContext('2d');
    const grad = ctx.createLinearGradient(0,0,0,200);
    grad.addColorStop(0,'rgba(31,107,74,.18)');
    grad.addColorStop(1,'rgba(31,107,74,.01)');
    new Chart(ctx, {
      type: 'line',
      data: { labels, datasets: [{ label:'Sales (₹)', data:vals,
        borderColor:'#1f6b4a', backgroundColor: grad,
        fill:true, tension:0.45, borderWidth:2.5,
        pointBackgroundColor:'#1f6b4a', pointBorderColor:'#fff',
        pointBorderWidth:2.5, pointRadius:5, pointHoverRadius:7 }]},
      options: { responsive:true, maintainAspectRatio:false,
        plugins: { legend:{display:false}, tooltip:{
          backgroundColor:'#0f172a', borderColor:'rgba(31,107,74,.3)', borderWidth:1,
          titleColor:'#94a3b8', bodyColor:'#f1f5f9', bodyFont:{weight:700,size:13}, padding:12,
          callbacks:{ label: c=>'  ₹'+c.parsed.y.toLocaleString('en-IN',{minimumFractionDigits:2}) }}},
        scales: {
          y:{ beginAtZero:true, suggestedMax:100,
            grid:{color:'rgba(226,232,240,.8)',drawBorder:false},
            ticks:{color:'#94a3b8',font:{size:10},maxTicksLimit:7,
              callback:v=>'₹'+(v>=1000?(v/1000).toFixed(0)+'k':v)}},
          x:{ grid:{display:false}, ticks:{color:'#94a3b8',font:{size:10}}}
        }}
    });

    /* Alerts Feed */
    const alertsEl = document.getElementById('alertsList');
    if (!recent_alerts.length) {
      alertsEl.innerHTML = `<div style="text-align:center;padding:2.5rem 1rem;">
        <i class="fas fa-shield-alt" style="font-size:1.8rem;color:#22c55e;margin-bottom:.6rem;display:block;"></i>
        <div style="font-weight:700;font-size:.8rem;color:#64748b;">All Clear — No Critical Alerts</div>
      </div>`;
    } else {
      alertsEl.innerHTML = recent_alerts.map(a => {
        const isLow = a.alert_type === 'low';
        const c  = isLow ? '#ef4444' : '#f59e0b';
        const bg = isLow ? 'rgba(239,68,68,.1)' : 'rgba(245,158,11,.1)';
        const ic = isLow ? 'fa-exclamation-circle' : 'fa-calendar-times';
        const msg = isLow ? `Only ${a.quantity} units left`
          : `Expires in ${Math.ceil((new Date(a.expiry_date)-new Date())/86400000)} days`;
        return `<div class="alert-item ${isLow?'a-low':'a-exp'}">
          <div class="alert-icon" style="background:${bg};color:${c};"><i class="fas ${ic}"></i></div>
          <div style="flex:1;min-width:0;">
            <div class="alert-name">${a.product_name}</div>
            <div class="alert-desc">${msg}</div>
          </div>
          <div class="alert-badge" style="background:${bg};color:${c};">${isLow?'LOW':'EXPIRY'}</div>
        </div>`;
      }).join('');
    }

    /* Top Products */
    const topEl = document.getElementById('topProductsList');
    const maxQ  = top_products[0] ? +top_products[0].total_qty : 1;
    if (!top_products.length) {
      topEl.innerHTML = `<div style="text-align:center;padding:2.5rem;color:#64748b;font-size:.78rem;">No sales data this month yet.</div>`;
    } else {
      topEl.innerHTML = top_products.map((p,i) => {
        const pct = (+p.total_qty / maxQ * 100).toFixed(1);
        const rc  = ['rb-1','rb-2','rb-3'][i] ?? 'rb-n';
        const rev = parseFloat(p.total_revenue ?? p.total_amt ?? 0);
        return `<div class="product-item">
          <div class="rank-badge ${rc}">#${i+1}</div>
          <div class="prod-bar-wrap">
            <div class="prod-name">${p.product_name}</div>
            <div class="prod-bar-track"><div class="prod-bar-fill" style="width:${pct}%;"></div></div>
          </div>
          <div class="prod-rev">₹${rev.toLocaleString('en-IN',{minimumFractionDigits:0})}</div>
        </div>`;
      }).join('');
    }
  } catch(e) { console.error('Dashboard Error:', e.message); }
});
</script>
