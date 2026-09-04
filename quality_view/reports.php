<?php
$pageTitle = 'BMW Reports';
$pageIcon  = 'fa-file-lines';
$pageDesc  = 'Daily, Monthly & Reconciliation Reports';
require_once __DIR__ . '/includes/quality_head.php';
?>
<?php require_once __DIR__ . '/includes/quality_sidebar.php'; ?>

<!-- Print Stylesheet -->
<style>
@media print {
  @page {
    size: A4 portrait;
    margin: 12mm 14mm;
  }
  body, html {
    background: #ffffff !important;
    color: #000000 !important;
    font-size: 9pt !important;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
  }
  .qsc-layout, .qsc-main {
    margin: 0 !important;
    padding: 0 !important;
    display: block !important;
    background: #ffffff !important;
  }
  .qsc-content {
    padding: 0 !important;
    margin: 0 !important;
  }
  .qsc-sidebar,
  .qsc-navbar,
  .d-print-none,
  #report-filter-card {
    display: none !important;
  }
  .qsc-card {
    border: 1px solid #94a3b8 !important;
    box-shadow: none !important;
    background: #ffffff !important;
    margin-bottom: 16px !important;
    break-inside: avoid;
    page-break-inside: avoid;
  }
  .qsc-card-header {
    background: #f8fafc !important;
    border-bottom: 1.5px solid #64748b !important;
    color: #0f172a !important;
    padding: 7px 12px !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
  .qsc-card-title {
    color: #0f172a !important;
    font-size: 0.92rem !important;
    font-weight: 700 !important;
  }
  .table {
    width: 100% !important;
    border-collapse: collapse !important;
    font-size: 8pt !important;
    margin-bottom: 0 !important;
  }
  .table thead th {
    background: #1f6b4a !important;
    color: #ffffff !important;
    border: 1px solid #174f37 !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    padding: 5px 6px !important;
  }
  .table tbody td, .table tfoot td {
    border: 1px solid #cbd5e1 !important;
    padding: 5px 6px !important;
    color: #0f172a !important;
  }
  .table tfoot td {
    background: #f8fafc !important;
    font-weight: 700 !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
  .status-collected, .status-dispatched, .status-completed {
    border: 1px solid #64748b !important;
    padding: 1.5px 6px !important;
    border-radius: 4px !important;
    font-size: 7.5pt !important;
    font-weight: 600 !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
  .variance-positive { color: #dc2626 !important; font-weight: 700 !important; }
  .variance-negative { color: #16a34a !important; font-weight: 700 !important; }
  .variance-zero     { color: #64748b !important; }
}
</style>

<div class="qsc-main">
<?php require_once __DIR__ . '/includes/quality_navbar.php'; ?>

<div class="qsc-content">

  <!-- Official Print Letterhead Header (Visible ONLY on printout) -->
  <div class="d-none d-print-block mb-3" id="official-print-header">
    <div class="d-flex justify-content-between align-items-start border-bottom pb-2 mb-2">
      <div>
        <h3 class="fw-bold mb-0" style="color:#1f6b4a;letter-spacing:0.5px;">GM HOSPITAL</h3>
        <div style="font-size:0.85rem;color:#475569;font-weight:600;">
          <i class="fas fa-hospital me-1"></i> <span id="ph-branch"><?= htmlspecialchars($_SESSION['hospital_branch'] ?? 'Main Hospital') ?></span> Branch &bull; Biomedical Waste Management Department
        </div>
      </div>
      <div class="text-end" style="font-size:0.8rem;color:#334155;">
        <div class="fw-bold" id="ph-report-title" style="font-size:1.05rem;color:#0f3324;">Monthly Biomedical Waste Report</div>
        <div>Report Period: <strong id="ph-period">September 2026</strong></div>
        <div>Printed: <strong><?= date('d-m-Y H:i') ?></strong> &bull; By: <strong><?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></strong></div>
      </div>
    </div>
  </div>

  <!-- Filter & Controls Card (Hidden during printing) -->
  <div class="qsc-card mb-4 d-print-none" id="report-filter-card">
    <div class="qsc-card-body" style="padding:16px 20px;">
      <div class="d-flex flex-wrap align-items-end gap-3">

        <!-- Report Type -->
        <div>
          <label class="form-label fw-semibold mb-1">Report Type</label>
          <div class="btn-group" role="group">
            <input type="radio" class="btn-check" name="reportType" id="rtype-daily"   value="daily"           autocomplete="off">
            <label class="btn btn-outline-success" for="rtype-daily">   <i class="fas fa-calendar-day"></i>   Daily</label>

            <input type="radio" class="btn-check" name="reportType" id="rtype-monthly" value="monthly"         autocomplete="off" checked>
            <label class="btn btn-outline-success" for="rtype-monthly"> <i class="fas fa-calendar-alt"></i>    Monthly</label>

            <input type="radio" class="btn-check" name="reportType" id="rtype-recon"   value="reconciliation"  autocomplete="off">
            <label class="btn btn-outline-success" for="rtype-recon">   <i class="fas fa-scale-balanced"></i>  Reconciliation</label>
          </div>
        </div>

        <!-- Date Filters -->
        <div id="daily-filter" style="display:none;">
          <label class="form-label fw-semibold mb-1">Date</label>
          <input type="date" id="filter-date" class="form-control" value="<?= date('Y-m-d') ?>">
        </div>

        <div id="monthly-filter" style="display:flex;gap:8px;" class="align-items-end">
          <div>
            <label class="form-label fw-semibold mb-1">Month</label>
            <select id="filter-month" class="form-select">
              <?php for($m=1;$m<=12;$m++): ?>
                <option value="<?= $m ?>" <?= $m==date('n')?'selected':'' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div>
            <label class="form-label fw-semibold mb-1">Year</label>
            <select id="filter-year" class="form-select">
              <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
                <option value="<?= $y ?>"><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>
        </div>

        <button class="btn-qsc-primary" onclick="generateReport()">
          <i class="fas fa-search me-1"></i> Generate
        </button>
        <button class="btn-qsc-outline" onclick="printReport()">
          <i class="fas fa-print me-1"></i> Print Report
        </button>
      </div>
    </div>
  </div>

  <!-- Report Output Container -->
  <div id="report-output">
    <div class="text-center text-muted py-5">
      <i class="fas fa-spinner fa-spin fa-2x mb-3" style="color:var(--qsc-primary);"></i>
      <p>Loading monthly biomedical waste report…</p>
    </div>
  </div>

</div><!-- /.qsc-content -->
</div><!-- /.qsc-main -->

<script>
// Toggle filter inputs based on report type
document.querySelectorAll('input[name="reportType"]').forEach(radio => {
  radio.addEventListener('change', function() {
    const type = this.value;
    document.getElementById('daily-filter').style.display   = type === 'daily'   ? '' : 'none';
    document.getElementById('monthly-filter').style.display = type !== 'daily'   ? 'flex' : 'none';
    generateReport(); // Auto-refresh data on radio switch
  });
});

async function generateReport() {
  const type  = document.querySelector('input[name="reportType"]:checked').value;
  const date  = document.getElementById('filter-date').value;
  const month = document.getElementById('filter-month').value;
  const year  = document.getElementById('filter-year').value;

  const qs = new URLSearchParams({ type, date, month, year });
  document.getElementById('report-output').innerHTML = '<div class="text-center py-5 text-muted"><i class="fas fa-spinner fa-spin fa-2x mb-3" style="color:var(--qsc-green);"></i><br>Generating report data…</div>';

  try {
    const res  = await qscApi(`/api/quality/reports?${qs}`);
    const data = res.data;

    if (type === 'daily')         renderDailyReport(data);
    else if (type === 'monthly')  renderMonthlyReport(data);
    else                          renderReconReport(data);
  } catch(e) {
    document.getElementById('report-output').innerHTML = `<div class="alert alert-danger"><i class="fas fa-triangle-exclamation me-2"></i>${e.message}</div>`;
  }
}

function binCell(val) {
  return parseFloat(val) > 0 ? parseFloat(val).toFixed(2) : '<span class="text-muted">—</span>';
}

function renderDailyReport(data) {
  const t = data.totals;
  const periodStr = data.date;
  document.getElementById('ph-report-title').textContent = `Daily Biomedical Waste Report — ${periodStr}`;
  document.getElementById('ph-period').textContent = periodStr;

  const out = `
    <div class="qsc-card mb-4">
      <div class="qsc-card-header d-print-none">
        <span class="qsc-card-title"><i class="fas fa-calendar-day me-2 text-success"></i>Daily BMW Report — ${data.date}</span>
      </div>
      <div class="qsc-card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered mb-0" style="font-size:0.85rem;" id="report-table">
            <thead style="background:var(--qsc-primary);color:#fff;">
              <tr>
                <th class="ps-3">Time</th>
                <th>Location / Ward</th>
                <th>Green</th>
                <th>Yellow</th>
                <th>Red</th>
                <th>Blue</th>
                <th>White</th>
                <th>Total (Kg)</th>
                <th>Status</th>
                <th>Receipt / Ref</th>
                <th>Vendor</th>
              </tr>
            </thead>
            <tbody>
              ${!data.rows.length ? '<tr><td colspan="11" class="text-center py-4 text-muted">No records found for this date.</td></tr>' : data.rows.map(r => `<tr>
                <td class="ps-3">${r.time}</td>
                <td><strong>${r.location}</strong></td>
                <td>${binCell(r.green)}</td>
                <td>${binCell(r.yellow)}</td>
                <td>${binCell(r.red)}</td>
                <td>${binCell(r.blue)}</td>
                <td>${binCell(r.white)}</td>
                <td><strong>${parseFloat(r.total).toFixed(2)}</strong></td>
                <td>${statusBadge(r.status)}</td>
                <td>${r.reference_no ?? '—'}</td>
                <td>${r.vendor_name ?? '—'}</td>
              </tr>`).join('')}
            </tbody>
            <tfoot style="background:#f8fafc;font-weight:700;">
              <tr>
                <td colspan="2">TOTAL</td>
                <td>${parseFloat(t.green).toFixed(2)}</td>
                <td>${parseFloat(t.yellow).toFixed(2)}</td>
                <td>${parseFloat(t.red).toFixed(2)}</td>
                <td>${parseFloat(t.blue).toFixed(2)}</td>
                <td>${parseFloat(t.white).toFixed(2)}</td>
                <td>${parseFloat(t.total).toFixed(2)} Kg</td>
                <td colspan="3"></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
    
    <!-- Signatures for Print -->
    <div class="d-none d-print-block mt-4 pt-3">
      <div class="d-flex justify-content-between text-center" style="margin-top: 40px;">
        <div style="width: 28%; border-top: 1.5px solid #000; padding-top: 5px; font-size: 0.8rem; font-weight: 700;">Infection Control Nurse</div>
        <div style="width: 28%; border-top: 1.5px solid #000; padding-top: 5px; font-size: 0.8rem; font-weight: 700;">Quality Manager</div>
        <div style="width: 28%; border-top: 1.5px solid #000; padding-top: 5px; font-size: 0.8rem; font-weight: 700;">Medical Superintendent</div>
      </div>
    </div>`;
  document.getElementById('report-output').innerHTML = out;
}

function renderMonthlyReport(data) {
  const months = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
  const monthName = months[data.month] || '';
  const periodStr = `${monthName} ${data.year}`;

  document.getElementById('ph-report-title').textContent = `Monthly Biomedical Waste Report — ${periodStr}`;
  document.getElementById('ph-period').textContent = periodStr;

  const g = data.grand_total;
  const out = `
    <!-- 1. Daily Aggregated Summary Table (With Status) -->
    <div class="qsc-card mb-4">
      <div class="qsc-card-header d-print-none">
        <span class="qsc-card-title"><i class="fas fa-calendar-check me-2 text-success"></i>Monthly BMW Daily Summary — ${periodStr}</span>
      </div>
      <div class="qsc-card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered mb-0" style="font-size:0.85rem;">
            <thead style="background:var(--qsc-primary);color:#fff;">
              <tr>
                <th class="ps-3">Date</th>
                <th>Entries</th>
                <th>Green</th>
                <th>Yellow</th>
                <th>Red</th>
                <th>Blue</th>
                <th>White</th>
                <th>H. Total (Kg)</th>
                <th>V. Total (Kg)</th>
                <th>Variance</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              ${!data.rows.length ? '<tr><td colspan="11" class="text-center py-4 text-muted">No records found for this month.</td></tr>' : data.rows.map(r => {
                const pCount = parseInt(r.pending_count || 0);
                const cCount = parseInt(r.completed_count || 0);
                let statusBadgeHtml = '<span class="text-muted">—</span>';
                if (pCount > 0 && cCount === 0) {
                  statusBadgeHtml = '<span class="status-collected">Pending</span>';
                } else if (pCount === 0 && cCount > 0) {
                  statusBadgeHtml = '<span class="status-completed">Completed</span>';
                } else if (pCount > 0 && cCount > 0) {
                  statusBadgeHtml = `<span class="status-dispatched">${cCount} Done, ${pCount} Pending</span>`;
                } else if (r.statuses) {
                  statusBadgeHtml = statusBadge(r.statuses);
                }

                return `<tr>
                  <td class="ps-3">${r.date}</td>
                  <td>${r.entries}</td>
                  <td>${binCell(r.green)}</td>
                  <td>${binCell(r.yellow)}</td>
                  <td>${binCell(r.red)}</td>
                  <td>${binCell(r.blue)}</td>
                  <td>${binCell(r.white)}</td>
                  <td><strong>${parseFloat(r.h_total).toFixed(2)}</strong></td>
                  <td>${parseFloat(r.v_total).toFixed(2)}</td>
                  <td class="${parseFloat(r.variance)>0?'variance-positive':parseFloat(r.variance)<0?'variance-negative':'variance-zero'}">
                    ${parseFloat(r.variance)>=0?'+':''}${parseFloat(r.variance).toFixed(2)}
                  </td>
                  <td>${statusBadgeHtml}</td>
                </tr>`;
              }).join('')}
            </tbody>
            <tfoot style="background:#f8fafc;font-weight:700;">
              <tr>
                <td>TOTAL</td>
                <td>${g.entries}</td>
                <td colspan="5">—</td>
                <td>${parseFloat(g.h_total).toFixed(2)}</td>
                <td>${parseFloat(g.v_total).toFixed(2)}</td>
                <td class="${g.variance>0?'variance-positive':g.variance<0?'variance-negative':'variance-zero'}">
                  ${g.variance>=0?'+':''}${parseFloat(g.variance).toFixed(2)}
                </td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- 2. Detailed Itemized Collection Log for the Month (Displays the data fully) -->
    ${(data.records && data.records.length > 0) ? `
    <div class="qsc-card mb-4">
      <div class="qsc-card-header d-print-none">
        <span class="qsc-card-title"><i class="fas fa-list me-2 text-success"></i>Monthly Itemized Collection Log (${data.records.length} records)</span>
      </div>
      <div class="qsc-card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered mb-0" style="font-size:0.83rem;">
            <thead style="background:var(--qsc-primary);color:#fff;">
              <tr>
                <th class="ps-3">Collection Date/Time</th>
                <th>Location / Department</th>
                <th>Green</th>
                <th>Yellow</th>
                <th>Red</th>
                <th>Blue</th>
                <th>White</th>
                <th>Total (Kg)</th>
                <th>Status</th>
                <th>Receipt / Ref</th>
                <th>Vendor</th>
              </tr>
            </thead>
            <tbody>
              ${data.records.map(rec => `<tr>
                <td class="ps-3">${rec.collection_time}</td>
                <td><strong>${rec.location}</strong></td>
                <td>${binCell(rec.green)}</td>
                <td>${binCell(rec.yellow)}</td>
                <td>${binCell(rec.red)}</td>
                <td>${binCell(rec.blue)}</td>
                <td>${binCell(rec.white)}</td>
                <td><strong>${parseFloat(rec.total).toFixed(2)}</strong></td>
                <td>${statusBadge(rec.status)}</td>
                <td>${rec.reference_no ?? '—'}</td>
                <td>${rec.vendor_name ?? '—'}</td>
              </tr>`).join('')}
            </tbody>
          </table>
        </div>
      </div>
    </div>` : ''}

    <!-- 3. Location-wise Breakdown Table -->
    <div class="qsc-card mb-4">
      <div class="qsc-card-header d-print-none">
        <span class="qsc-card-title"><i class="fas fa-location-dot me-2 text-danger"></i>Location / Ward-wise Breakdown</span>
      </div>
      <div class="qsc-card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered mb-0" style="font-size:0.85rem;">
            <thead style="background:#f8fafc;">
              <tr>
                <th class="ps-3">Location</th>
                <th>Green</th>
                <th>Yellow</th>
                <th>Red</th>
                <th>Blue</th>
                <th>White</th>
                <th>Total</th>
                <th>Entries</th>
              </tr>
            </thead>
            <tbody>
              ${!data.locations.length ? '<tr><td colspan="8" class="text-center py-3 text-muted">No location records.</td></tr>' : data.locations.map(l => `<tr>
                <td class="ps-3"><strong>${l.location}</strong></td>
                <td>${binCell(l.green)}</td>
                <td>${binCell(l.yellow)}</td>
                <td>${binCell(l.red)}</td>
                <td>${binCell(l.blue)}</td>
                <td>${binCell(l.white)}</td>
                <td><strong>${parseFloat(l.h_total).toFixed(2)} Kg</strong></td>
                <td>${l.entries}</td>
              </tr>`).join('')}
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Signatures for Print -->
    <div class="d-none d-print-block mt-4 pt-3">
      <div class="d-flex justify-content-between text-center" style="margin-top: 40px;">
        <div style="width: 28%; border-top: 1.5px solid #000; padding-top: 5px; font-size: 0.8rem; font-weight: 700;">Infection Control Nurse</div>
        <div style="width: 28%; border-top: 1.5px solid #000; padding-top: 5px; font-size: 0.8rem; font-weight: 700;">Quality Manager</div>
        <div style="width: 28%; border-top: 1.5px solid #000; padding-top: 5px; font-size: 0.8rem; font-weight: 700;">Medical Superintendent</div>
      </div>
    </div>`;

  document.getElementById('report-output').innerHTML = out;
}

function renderReconReport(data) {
  const t = data.totals;
  const months = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
  const monthName = months[data.month] || '';
  const periodStr = `${monthName} ${data.year}`;

  document.getElementById('ph-report-title').textContent = `BMW Reconciliation Report — ${periodStr}`;
  document.getElementById('ph-period').textContent = periodStr;

  const out = `
    <div class="qsc-card mb-4">
      <div class="qsc-card-header d-print-none">
        <span class="qsc-card-title"><i class="fas fa-scale-balanced me-2 text-success"></i>Reconciliation Report — ${periodStr}</span>
      </div>
      <div class="qsc-card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered mb-0" style="font-size:0.85rem;">
            <thead style="background:var(--qsc-primary);color:#fff;">
              <tr>
                <th class="ps-3">Dispatch Date</th>
                <th>Vendor</th>
                <th>Receipt / Ref</th>
                <th>H. Total (Kg)</th>
                <th>V. Total (Kg)</th>
                <th>Variance</th>
              </tr>
            </thead>
            <tbody>
              ${!data.rows.length ? '<tr><td colspan="6" class="text-center py-4 text-muted">No reconciliation records for this period.</td></tr>' : data.rows.map(r => `<tr>
                <td class="ps-3">${r.dispatch_date}</td>
                <td>${r.vendor_name ?? '—'}</td>
                <td>${r.reference_no ?? '—'}</td>
                <td>${parseFloat(r.h_total).toFixed(2)}</td>
                <td>${parseFloat(r.v_total).toFixed(2)}</td>
                <td class="${parseFloat(r.variance)>0?'variance-positive':parseFloat(r.variance)<0?'variance-negative':'variance-zero'}">
                  ${parseFloat(r.variance)>=0?'+':''}${parseFloat(r.variance).toFixed(2)}
                </td>
              </tr>`).join('')}
            </tbody>
            <tfoot style="background:#f8fafc;font-weight:700;">
              <tr>
                <td colspan="3">TOTAL</td>
                <td>${parseFloat(t.h_total).toFixed(2)}</td>
                <td>${parseFloat(t.v_total).toFixed(2)}</td>
                <td class="${t.variance>0?'variance-positive':t.variance<0?'variance-negative':'variance-zero'}">${t.variance>=0?'+':''}${parseFloat(t.variance).toFixed(2)}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- Signatures for Print -->
    <div class="d-none d-print-block mt-4 pt-3">
      <div class="d-flex justify-content-between text-center" style="margin-top: 40px;">
        <div style="width: 28%; border-top: 1.5px solid #000; padding-top: 5px; font-size: 0.8rem; font-weight: 700;">Infection Control Nurse</div>
        <div style="width: 28%; border-top: 1.5px solid #000; padding-top: 5px; font-size: 0.8rem; font-weight: 700;">Quality Manager</div>
        <div style="width: 28%; border-top: 1.5px solid #000; padding-top: 5px; font-size: 0.8rem; font-weight: 700;">Medical Superintendent</div>
      </div>
    </div>`;

  document.getElementById('report-output').innerHTML = out;
}

function printReport() {
  window.print();
}

// Auto-load default Monthly report immediately on page load
document.addEventListener('DOMContentLoaded', () => {
  generateReport();
});
// Fallback if DOMContentLoaded already fired
if (document.readyState === 'complete' || document.readyState === 'interactive') {
  generateReport();
}
</script>

<?php require_once __DIR__ . '/includes/quality_foot.php'; ?>
