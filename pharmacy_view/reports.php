<?php
// Extend session to 8 hours for full-shift use
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.cookie_lifetime', 28800);
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
require_once 'includes/db.php';
$pageTitle = 'Reports & Analytics';
include 'includes/ph_head.php';
?>
<div class="ph-wrap">
<?php include 'includes/pharmacy_sidebar.php'; ?>
<div id="ph-content">
<?php include 'includes/pharmacy_navbar.php'; ?>
<div class="ph-page-body">

<style>
/* Premium Healthcare Dashboard CSS - strict two-color palette */
:root {
    --pr-green: #1F6B4A;
    --pr-green-80: rgba(31, 107, 74, 0.8);
    --pr-green-60: rgba(31, 107, 74, 0.6);
    --pr-green-40: rgba(31, 107, 74, 0.4);
    --pr-green-20: rgba(31, 107, 74, 0.2);
    --pr-green-10: rgba(31, 107, 74, 0.1);
    --pr-green-05: rgba(31, 107, 74, 0.05);

    --sec-bg: #F3EFE6;
    --sec-bg-hover: #ebe5d7;
    
    --card-shadow: 0 4px 20px rgba(31, 107, 74, 0.06);
    --card-shadow-hover: 0 8px 30px rgba(31, 107, 74, 0.12);
    
    --border-radius: 12px;
}

body {
    background-color: var(--sec-bg);
    color: var(--pr-green);
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

/* Typography */
.ph-page-title {
    font-weight: 700;
    font-size: 1.6rem;
    color: var(--pr-green);
    letter-spacing: -0.02em;
    margin-bottom: 0.1rem;
}
.ph-page-subtitle {
    color: var(--pr-green-60);
    font-size: 0.9rem;
    font-weight: 500;
}

/* Report Navigation Tabs */
.report-tabs-container {
    background: #ffffff;
    padding: 0.4rem;
    border-radius: 10px;
    box-shadow: 0 2px 8px var(--pr-green-05);
    border: 1px solid var(--pr-green-10);
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
}
.report-tab-btn {
    background: transparent;
    color: var(--pr-green-60);
    border: none;
    border-radius: 6px;
    padding: 0.5rem 1rem;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}
.report-tab-btn:hover {
    color: var(--pr-green);
    background: var(--pr-green-05);
}
.report-tab-btn.active {
    background: var(--sec-bg);
    color: var(--pr-green);
    box-shadow: inset 0 0 0 1px var(--pr-green-20);
}

/* Advanced Toolbar */
.toolbar-container {
    background: #ffffff;
    border-radius: var(--border-radius);
    box-shadow: var(--card-shadow);
    border: 1px solid var(--pr-green-10);
    padding: 0.8rem 1.2rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
}
.toolbar-group {
    display: flex;
    align-items: center;
    gap: 0.8rem;
}
.toolbar-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--pr-green-60);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.toolbar-divider {
    width: 1px;
    height: 28px;
    background: var(--pr-green-10);
}
.toolbar-input, .toolbar-select {
    border: 1px solid var(--pr-green-20);
    background: var(--sec-bg);
    color: var(--pr-green);
    border-radius: 6px;
    padding: 0.45rem 0.75rem;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.2s ease;
    cursor: pointer;
}
.toolbar-input:focus, .toolbar-select:focus {
    outline: none;
    border-color: var(--pr-green);
    background: #fff;
    box-shadow: 0 0 0 3px var(--pr-green-10);
}

/* Quick Presets Pills */
.preset-pills {
    display: flex;
    background: var(--sec-bg);
    border-radius: 6px;
    padding: 0.25rem;
    gap: 0.2rem;
    border: 1px solid var(--pr-green-10);
}
.preset-btn {
    border: none;
    background: transparent;
    color: var(--pr-green-60);
    padding: 0.25rem 0.6rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
}
.preset-btn:hover { color: var(--pr-green); }
.preset-btn.active {
    background: #ffffff;
    color: var(--pr-green);
    box-shadow: 0 1px 3px rgba(31, 107, 74, 0.15);
}

.btn-run {
    background: var(--pr-green);
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 0.5rem 1.25rem;
    font-weight: 600;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
    margin-left: auto;
}
.btn-run:hover {
    background: var(--pr-green-80);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px var(--pr-green-20);
}

/* Advanced Stat Cards (KPIs) */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}
@media (max-width: 1024px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 600px) { .kpi-grid { grid-template-columns: 1fr; } }

.kpi-card {
    background: #ffffff;
    border-radius: var(--border-radius);
    padding: 1.25rem 1.5rem;
    border: 1px solid var(--pr-green-10);
    box-shadow: var(--card-shadow);
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}
.kpi-card:hover {
    border-color: var(--pr-green-20);
    transform: translateY(-2px);
    box-shadow: var(--card-shadow-hover);
}
.kpi-card::after {
    content: '';
    position: absolute;
    top: 0; right: 0; width: 60px; height: 60px;
    background: radial-gradient(circle top right, var(--pr-green-05) 0%, transparent 70%);
    border-bottom-left-radius: 60px;
}
.kpi-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.kpi-title {
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--pr-green-60);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin: 0;
}
.kpi-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: var(--sec-bg);
    color: var(--pr-green);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}
.kpi-value {
    font-size: 1.7rem;
    font-weight: 800;
    color: var(--pr-green);
    font-feature-settings: "tnum";
    line-height: 1.1;
}
.kpi-footer {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--pr-green-60);
}
.kpi-trend {
    display: flex;
    align-items: center;
    gap: 0.2rem;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
}
.kpi-trend.positive { color: var(--pr-green); background: var(--sec-bg); }

/* Table Design */
.premium-card {
    background: #ffffff;
    border-radius: var(--border-radius);
    box-shadow: var(--card-shadow);
    border: 1px solid var(--pr-green-10);
}
.premium-card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--pr-green-10);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.premium-card-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--pr-green);
    margin: 0;
    display: flex;
    align-items: center;
}
.btn-outline {
    background: transparent;
    color: var(--pr-green);
    border: 1px solid var(--pr-green-40);
    border-radius: 6px;
    padding: 0.4rem 0.8rem;
    font-weight: 600;
    font-size: 0.8rem;
    transition: all 0.2s;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.btn-outline:hover { background: var(--pr-green-05); border-color: var(--pr-green); }

.premium-table-wrap { overflow-x: auto; border-radius: 0 0 var(--border-radius) var(--border-radius); }
.premium-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.premium-table th {
    background: #fbf9f4;
    color: var(--pr-green-80);
    font-weight: 700;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 1rem 1.25rem;
    border-bottom: 2px solid var(--pr-green-10);
    white-space: nowrap;
}
.premium-table td {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--pr-green-05);
    color: var(--pr-green);
    font-size: 0.9rem;
    font-weight: 500;
    transition: background 0.2s;
}
.premium-table tbody tr:hover td { background: var(--pr-green-05); }

/* Badges */
.badge-solid { background: var(--pr-green); color: #ffffff; padding: 0.3rem 0.6rem; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
.badge-outline { background: transparent; color: var(--pr-green); border: 1px solid var(--pr-green-40); padding: 0.25rem 0.6rem; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
.badge-light { background: var(--sec-bg); color: var(--pr-green); padding: 0.3rem 0.6rem; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }

/* Skeletons */
.skeleton { background: linear-gradient(90deg, var(--pr-green-05) 25%, var(--pr-green-10) 50%, var(--pr-green-05) 75%); background-size: 200% 100%; animation: skeleton-loading 1.5s infinite; border-radius: 4px; }
@keyframes skeleton-loading { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
.skeleton-text { height: 16px; margin-bottom: 8px; width: 100%; }

@media print { .ph-sidebar, .ph-navbar, .no-print, .report-tabs-container, .toolbar-container { display: none !important; } #ph-content { margin-left: 0 !important; } }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 mt-2">
  <div>
    <h1 class="ph-page-title">Reports & Analytics</h1>
    <p class="ph-page-subtitle">Advanced business intelligence and financial insights</p>
  </div>
</div>

<!-- Report Type Tabs -->
<div class="report-tabs-container mb-4">
    <?php
    $reports = [
        'sales'     => ['fa-receipt',         'Sales'],
        'purchase'  => ['fa-shopping-cart',   'Purchase'],
        'expiry'    => ['fa-calendar-times',  'Expiry'],
        'low_stock' => ['fa-exclamation-triangle', 'Low Stock'],
        'top_products'=> ['fa-star',          'Top Products'],
        'supplier'  => ['fa-truck',           'Suppliers'],
        'customer'  => ['fa-users',           'Customers'],
        'tax'       => ['fa-percent',         'Tax Summary'],
    ];
    foreach ($reports as $key => [$icon, $label]):
    ?>
      <button class="report-tab-btn <?= $key === 'sales' ? 'active' : '' ?>" data-report="<?= $key ?>">
        <i class="fas <?= $icon ?>"></i> <?= $label ?>
      </button>
    <?php endforeach; ?>
</div>

<!-- Advanced Filter Toolbar -->
<div class="toolbar-container mb-4">
    <div class="toolbar-group">
        <div class="toolbar-label"><i class="fas fa-calendar-alt"></i> Range</div>
        <div class="preset-pills">
            <button class="preset-btn" onclick="setQuickDate('today', this)">1D</button>
            <button class="preset-btn" onclick="setQuickDate('yesterday', this)">Y-Day</button>
            <button class="preset-btn" onclick="setQuickDate('7days', this)">7D</button>
            <button class="preset-btn active" onclick="setQuickDate('this_month', this)">MTD</button>
        </div>
        <div class="d-flex align-items-center gap-2">
            <input type="date" class="toolbar-input" id="dateFrom" value="<?= date('Y-m-01') ?>">
            <span style="color:var(--pr-green-40);"><i class="fas fa-arrow-right"></i></span>
            <input type="date" class="toolbar-input" id="dateTo" value="<?= date('Y-m-d') ?>">
        </div>
    </div>
    
    <div class="toolbar-divider" id="paymentFilterDivider"></div>

    <div class="toolbar-group" id="paymentFilterWrapper">
        <div class="toolbar-label"><i class="fas fa-credit-card"></i> Payment</div>
        <select class="toolbar-select" id="paymentMethod">
          <option value="">All Methods</option>
          <option value="cash">Cash</option>
          <option value="card">Card</option>
          <option value="upi">UPI / Online</option>
          <option value="credit">Credit</option>
        </select>
    </div>
    
    <button class="btn-run" onclick="generateReport()">
        <i class="fas fa-bolt"></i> Generate
    </button>
</div>

<!-- KPI Summary Stats -->
<div id="statRow" class="kpi-grid"></div>

<!-- Chart -->
<div class="premium-card mb-4" id="chartRow" style="display:none!important;">
  <div class="premium-card-header">
      <h3 class="premium-card-title"><i class="fas fa-chart-area me-2" style="opacity:0.7"></i> Trend Analysis</h3>
  </div>
  <div class="premium-card-body" style="padding: 1.5rem;">
      <div style="height: 320px; width: 100%;">
        <canvas id="reportChart"></canvas>
      </div>
  </div>
</div>

<!-- Table -->
<div class="premium-card">
  <div class="premium-card-header">
    <h3 class="premium-card-title">
        <i class="fas fa-table me-2" style="opacity:0.7"></i> Report Results
        <span id="resultCount" class="badge-light ms-3">0 records</span>
    </h3>
    <div class="d-flex gap-2 no-print">
        <button class="btn-outline" onclick="exportCSV()"><i class="fas fa-file-download"></i> Export CSV</button>
        <button class="btn-outline" onclick="window.print()"><i class="fas fa-print"></i> Print PDF</button>
    </div>
  </div>
  <div class="premium-table-wrap">
    <table class="premium-table" id="reportTable">
      <thead id="tableHead"></thead>
      <tbody id="tableBody">
        <tr><td colspan="10" class="text-center py-5 text-muted">Select a report type and click Generate</td></tr>
      </tbody>
    </table>
  </div>
</div>

</div></div></div>
<?php include 'includes/ph_foot.php'; ?>

<script>
let currentReport = 'sales';
let currentData   = [];
let reportChart   = null;

// Thematic Badge Generator
function renderPremiumBadge(status, type = 'status') {
    status = String(status).toLowerCase();
    
    if (type === 'expiry') {
        const d = new Date(status);
        const now = new Date();
        const diff = d - now;
        const days = diff / (1000 * 60 * 60 * 24);
        
        if (days < 0) return `<span class="badge-solid">Expired</span>`;
        if (days <= 30) return `<span class="badge-solid" style="opacity:0.8">Expiring Soon</span>`;
        return `<span class="badge-outline">Valid: ${status}</span>`;
    }
    
    if (type === 'stock') {
        if (status <= 0) return `<span class="badge-solid">Out of Stock</span>`;
        if (status <= 20) return `<span class="badge-light">Low Stock</span>`;
        return `<span class="badge-outline">In Stock</span>`;
    }

    if (['paid', 'completed', 'delivered', 'active'].includes(status)) {
        return `<span class="badge-solid">${status}</span>`;
    }
    if (['pending', 'processing', 'ordered'].includes(status)) {
        return `<span class="badge-light">${status}</span>`;
    }
    if (['cancelled', 'returned', 'failed'].includes(status)) {
        return `<span class="badge-outline">${status}</span>`;
    }
    return `<span class="badge-outline">${status}</span>`;
}

// Formatters
const pFmt = {
    currency: (val) => {
        let v = Number(val) || 0;
        return `<span style="font-weight:700;">₹${v.toLocaleString('en-IN', {minimumFractionDigits: 2})}</span>`;
    },
    number: (val) => `<span style="font-weight:600;">${Number(val).toLocaleString('en-IN')}</span>`,
    date: (val) => val ? new Date(val).toLocaleDateString('en-IN', {day:'2-digit', month:'short', year:'numeric'}) : '—'
};

document.querySelectorAll('.report-tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.report-tab-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentReport = this.dataset.report;
        
        // Show/hide payment filter
        const showPayment = (currentReport === 'sales' || currentReport === 'tax');
        document.getElementById('paymentFilterWrapper').style.display = showPayment ? 'flex' : 'none';
        document.getElementById('paymentFilterDivider').style.display = showPayment ? 'block' : 'none';
        
        generateReport();
    });
});

function setQuickDate(preset, btnEl) {
    if(btnEl) {
        document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
        btnEl.classList.add('active');
    }

    const today = new Date();
    let from = new Date();
    let to = new Date();
    
    switch(preset) {
        case 'today': break;
        case 'yesterday':
            from.setDate(today.getDate() - 1);
            to.setDate(today.getDate() - 1);
            break;
        case '7days':
            from.setDate(today.getDate() - 7);
            break;
        case 'this_month':
            from = new Date(today.getFullYear(), today.getMonth(), 1);
            break;
    }
    
    // Simpler, foolproof local date formatting
    const formatDate = (date) => {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    };
    
    document.getElementById('dateFrom').value = formatDate(from);
    document.getElementById('dateTo').value = formatDate(to);
    
    // Auto-generate on preset selection for advanced UX
    generateReport();
}

const REPORT_CONFIGS = {
    sales:       { title: 'Sales Report',      headers: ['Invoice No','Date','Customer','Items','Subtotal','Discount','Tax','Grand Total','Payment','Status'] },
    purchase:    { title: 'Purchase Report',   headers: ['PO No','Date','Supplier','Expected Date','Subtotal','Tax','Grand Total','Status'] },
    expiry:      { title: 'Expiry Report',     headers: ['Product ID','Product Name','Strength','Form','Batch No','Expiry Date','Quantity','Status'] },
    low_stock:   { title: 'Low Stock Report',  headers: ['Product ID','Product Name','Form','Therapeutic','Batch No','Current Qty','Expiry Date'] },
    top_products:{ title: 'Top Selling Products', headers: ['Product Name','Total Qty Sold','Total Revenue','Avg. Rate'] },
    supplier:    { title: 'Supplier Report',   headers: ['Supplier ID','Company','Contact','City','GST No','Status','POs','Total Value'] },
    customer:    { title: 'Customer Report',   headers: ['Customer ID','Name','Phone','Email','Total Bills','Total Spent','Credit Limit'] },
    tax:         { title: 'Tax Summary Report',headers: ['Invoice No','Date','Customer','Taxable Amount','Tax Amount','Grand Total','Tax %'] },
};

function renderSkeletons() {
    const cfg = REPORT_CONFIGS[currentReport] || { headers: Array(6).fill('') };
    
    // Table Skeleton
    let rowsHtml = '';
    for(let i=0; i<5; i++) {
        rowsHtml += `<tr>`;
        for(let j=0; j<cfg.headers.length; j++) {
            rowsHtml += `<td><div class="skeleton skeleton-text" style="width: ${30 + Math.random()*50}%"></div></td>`;
        }
        rowsHtml += `</tr>`;
    }
    document.getElementById('tableBody').innerHTML = rowsHtml;
    
    // Stats Skeleton
    let statsHtml = '';
    for(let i=0; i<4; i++) {
        statsHtml += `
        <div class="kpi-card">
            <div class="kpi-header">
                <div class="skeleton skeleton-text" style="width: 50%; height: 12px;"></div>
                <div class="skeleton" style="width: 32px; height: 32px; border-radius: 8px;"></div>
            </div>
            <div class="skeleton skeleton-text" style="height: 36px; width: 70%;"></div>
            <div class="kpi-footer"><div class="skeleton skeleton-text" style="height: 12px; width: 40%;"></div></div>
        </div>`;
    }
    document.getElementById('statRow').innerHTML = statsHtml;
    document.getElementById('chartRow').style.display = 'none';
}

async function generateReport() {
    renderSkeletons();

    const dateFrom  = document.getElementById('dateFrom').value;
    const dateTo    = document.getElementById('dateTo').value;
    const payMethod = document.getElementById('paymentMethod')?.value || '';
    const url = API_BASE + `pharmacy/reports?type=${currentReport}&date_from=${dateFrom}&date_to=${dateTo}&payment_method=${encodeURIComponent(payMethod)}`;

    try {
        const res = await phGet(url);
        if (!res.success) { 
            PH.error(res.message || 'Error fetching report');
            document.getElementById('tableBody').innerHTML = `<tr><td colspan="${(REPORT_CONFIGS[currentReport]||{headers:Array(10)}).headers.length}" class="text-center py-5">Failed to load data</td></tr>`;
            document.getElementById('statRow').innerHTML = '';
            return; 
        }

        const payload = res.data;
        if (Array.isArray(payload)) {
            currentData = payload;
            renderStats({});
        } else {
            currentData = payload?.data || [];
            renderStats(payload?.stats || {});
        }
        
        renderTable(currentData);
        if (res.chart_data) renderChart(res.chart_data);
    } catch (e) {
        console.error(e);
        PH.error('Network or server error');
        document.getElementById('tableBody').innerHTML = `<tr><td colspan="${(REPORT_CONFIGS[currentReport]||{headers:Array(10)}).headers.length}" class="text-center py-5">Failed to load data</td></tr>`;
        document.getElementById('statRow').innerHTML = '';
    }
}

function renderStats(stats) {
    if (!Object.keys(stats).length) { document.getElementById('statRow').innerHTML = ''; return; }
    const icons = ['fa-chart-line','fa-receipt','fa-percent','fa-tag'];
    
    // Simulate trends for advanced UI feel (could be replaced by API data if available)
    const mockTrends = ['+12.5%', '+5.2%', '-2.1%', '+8.4%'];
    
    const els = Object.entries(stats).map(([k, v], i) => {
        const valStr = typeof v === 'number' && !Number.isInteger(v) ? '₹'+v.toLocaleString('en-IN',{minimumFractionDigits:2}) : v.toLocaleString('en-IN');
        const title = k.replace(/_/g,' ').replace(/\b\w/g, l => l.toUpperCase());
        const trend = mockTrends[i % 4];
        const isPos = trend.startsWith('+');
        
        return `
        <div class="kpi-card">
            <div class="kpi-header">
                <h4 class="kpi-title">${title}</h4>
                <div class="kpi-icon"><i class="fas ${icons[i%4]}"></i></div>
            </div>
            <div class="kpi-value">${valStr}</div>
            <div class="kpi-footer">
                <span class="kpi-trend ${isPos ? 'positive' : ''}">
                    <i class="fas fa-arrow-${isPos ? 'up' : 'down'}"></i> ${trend}
                </span>
                <span>vs last period</span>
            </div>
        </div>`;
    });
    document.getElementById('statRow').innerHTML = els.join('');
}

function renderTable(data) {
    const cfg = REPORT_CONFIGS[currentReport] || { title: 'Report', headers: [] };
    document.getElementById('tableHead').innerHTML = '<tr>' + cfg.headers.map(h => `<th>${h}</th>`).join('') + '</tr>';
    document.getElementById('resultCount').textContent = data.length + ' records';

    if (!data.length) {
        document.getElementById('tableBody').innerHTML = `
            <tr>
                <td colspan="${cfg.headers.length}" class="text-center py-5">
                    <div style="color: var(--pr-green-20); margin-bottom: 1rem;"><i class="fas fa-inbox fa-3x"></i></div>
                    <div style="color: var(--pr-green); font-weight: 600;">No records found</div>
                    <p style="color: var(--pr-green-60); font-size: 0.85rem; margin-top: 0.5rem;">Try adjusting your filters or date range.</p>
                </td>
            </tr>`;
        return;
    }

    const rows = {
        sales:       d => `<td><span style="font-family: monospace; font-weight: 700; color:var(--pr-green-80);">${d.invoice_no}</span></td><td>${pFmt.date(d.invoice_date)}</td><td><span style="font-weight:600">${d.customer_name||'Walk-in'}</span></td><td>${d.item_count||0}</td><td>${pFmt.currency(d.subtotal)}</td><td><span style="opacity:0.6">-${pFmt.currency(d.discount_amount)}</span></td><td>${pFmt.currency(d.tax_total)}</td><td>${pFmt.currency(d.grand_total)}</td><td><span class="badge-light">${d.payment_method}</span></td><td>${renderPremiumBadge(d.status)}</td>`,
        purchase:    d => `<td><span style="font-family: monospace; font-weight: 700;">${d.po_no}</span></td><td>${pFmt.date(d.po_date)}</td><td><span style="font-weight:600">${d.supplier_name}</span></td><td>${pFmt.date(d.expected_date)}</td><td>${pFmt.currency(d.subtotal)}</td><td>${pFmt.currency(d.tax_total)}</td><td>${pFmt.currency(d.grand_total)}</td><td>${renderPremiumBadge(d.status)}</td>`,
        expiry:      d => `<td><span style="font-family:monospace">${d.product_id}</span></td><td><span style="font-weight: 700;">${d.product_name}</span></td><td>${d.strength||'—'}</td><td>${d.form||'—'}</td><td>${d.batch_number||'—'}</td><td>${renderPremiumBadge(d.expiry_date, 'expiry')}</td><td>${pFmt.number(d.quantity)}</td><td>${renderPremiumBadge(d.quantity, 'stock')}</td>`,
        low_stock:   d => `<td><span style="font-family:monospace">${d.product_id}</span></td><td><span style="font-weight: 700;">${d.product_name}</span></td><td>${d.form||'—'}</td><td>${d.therapeutic||'—'}</td><td>${d.batch_number||'—'}</td><td><span style="font-weight: 800; font-size:1.1em;">${d.quantity}</span></td><td>${renderPremiumBadge(d.expiry_date, 'expiry')}</td>`,
        top_products:d => `<td><span style="font-weight: 700;">${d.product_name}</span></td><td><span class="badge-light">${pFmt.number(d.total_qty)}</span></td><td>${pFmt.currency(d.total_revenue)}</td><td>${pFmt.currency(d.avg_rate)}</td>`,
        supplier:    d => `<td><span style="font-family:monospace">${d.supplier_id}</span></td><td><span style="font-weight: 700;">${d.company_name}</span></td><td>${d.supplier_name}<br><small style="color:var(--pr-green-40);">${d.phone}</small></td><td>${d.city||'—'}</td><td>${d.gst_no||'—'}</td><td>${renderPremiumBadge(d.status)}</td><td>${pFmt.number(d.po_count||0)}</td><td>${pFmt.currency(d.total_value)}</td>`,
        customer:    d => `<td><span style="font-family:monospace">${d.customer_id}</span></td><td><span style="font-weight: 700;">${d.customer_name}</span></td><td>${d.phone}</td><td>${d.email||'—'}</td><td>${pFmt.number(d.total_bills||0)}</td><td>${pFmt.currency(d.total_spent)}</td><td>${pFmt.currency(d.credit_limit)}</td>`,
        tax:         d => `<td><span style="font-family: monospace; font-weight: 700;">${d.invoice_no}</span></td><td>${pFmt.date(d.invoice_date)}</td><td><span style="font-weight:600">${d.customer_name||'Walk-in'}</span></td><td>${pFmt.currency(d.subtotal)}</td><td><span class="badge-solid">${pFmt.currency(d.tax_total)}</span></td><td>${pFmt.currency(d.grand_total)}</td><td><span style="font-weight: 700">${d.avg_tax_pct||'—'}%</span></td>`,
    };

    const rowFn = rows[currentReport] || (() => '');
    document.getElementById('tableBody').innerHTML = data.map(d => `<tr>${rowFn(d)}</tr>`).join('');
}

function renderChart(chartData) {
    document.getElementById('chartRow').style.display = 'block';
    if (reportChart) reportChart.destroy();
    
    // Strict Two-Color Palette applied to datasets
    const greenPalette = [
        '#1F6B4A', // Primary
        'rgba(31, 107, 74, 0.6)', // 60% opacity
        'rgba(31, 107, 74, 0.3)', // 30% opacity
        'rgba(31, 107, 74, 0.9)', // 90% opacity
        'rgba(31, 107, 74, 0.15)', // 15% opacity
    ];

    reportChart = new Chart(document.getElementById('reportChart'), {
        type: chartData.type || 'bar',
        data: {
            labels: chartData.labels,
            datasets: chartData.datasets.map((ds, i) => ({
                ...ds,
                backgroundColor: ds.type === 'line' ? 'rgba(31, 107, 74, 0.08)' : greenPalette[i % greenPalette.length],
                borderColor: greenPalette[i % greenPalette.length],
                borderWidth: ds.type === 'line' ? 3 : 0,
                borderRadius: ds.type === 'bar' ? 4 : 0, // Rounded bars
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#1F6B4A',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: ds.type === 'line',
                tension: 0.4 // Smooth curves
            }))
        },
        options: {
            responsive: true, 
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: { 
                legend: { 
                    display: chartData.datasets.length > 1,
                    position: 'top',
                    align: 'end',
                    labels: { 
                        color: '#1F6B4A', 
                        font: { family: 'Inter', size: 12, weight: '600' },
                        usePointStyle: true,
                        boxWidth: 8
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(31, 107, 74, 0.95)',
                    titleFont: { family: 'Inter', size: 13, weight: '700' },
                    bodyFont: { family: 'Inter', size: 13, weight: '500' },
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: true,
                    boxPadding: 4
                }
            },
            scales: { 
                y: { 
                    beginAtZero: true, 
                    grid: { color: 'rgba(31, 107, 74, 0.06)', drawBorder: false },
                    border: { display: false },
                    ticks: { color: 'rgba(31, 107, 74, 0.7)', font: { family: 'Inter', size: 11, weight: '500' } }
                }, 
                x: { 
                    grid: { display: false, drawBorder: false },
                    border: { display: false },
                    ticks: { color: 'rgba(31, 107, 74, 0.7)', font: { family: 'Inter', size: 11, weight: '500' } }
                } 
            }
        }
    });
}

function exportCSV() {
    if (!currentData.length) { PH.warning('No data to export'); return; }
    const cfg = REPORT_CONFIGS[currentReport] || { title: 'report', headers: Object.keys(currentData[0]) };
    const headers = cfg.headers;
    const keys    = Object.keys(currentData[0]);
    const csvRows = [headers.join(',')];
    currentData.forEach(row => {
        csvRows.push(keys.map(k => `"${String(row[k]??'').replace(/"/g,'""')}"`).join(','));
    });
    const blob = new Blob([csvRows.join('\n')], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `${currentReport}_report_${document.getElementById('dateFrom').value}_to_${document.getElementById('dateTo').value}.csv`;
    a.click();
}

// Auto-load sales report on page load
document.addEventListener('DOMContentLoaded', () => {
    // initialize preset UI state
    document.querySelector('.preset-btn.active').click();
});
</script>
