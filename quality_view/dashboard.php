<?php
$pageTitle = 'BMW Dashboard';
$pageIcon  = 'fa-biohazard';
$pageDesc  = 'Biomedical Waste Management Overview';
require_once __DIR__ . '/includes/quality_head.php';
?>
<?php require_once __DIR__ . '/includes/quality_sidebar.php'; ?>

<style>
.qsc-kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
.qsc-kpi-card {
  min-height: 98px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 10px 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  border-radius: 10px;
  background: #ffffff;
  border: 1px solid var(--qsc-border-light);
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.qsc-kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--qsc-shadow);
}
.qsc-kpi-card .qsc-kpi-icon {
  width: 28px;
  height: 28px;
  border-radius: 7px;
  display: grid;
  place-items: center;
  font-size: 0.82rem;
  margin-bottom: 0;
}
.qsc-kpi-card .qsc-kpi-label {
  font-size: 0.68rem;
  font-weight: 700;
  color: var(--qsc-muted);
  text-transform: uppercase;
  letter-spacing: 0.4px;
  line-height: 1.25;
}
.qsc-kpi-card .qsc-kpi-value {
  font-size: 1.35rem;
  font-weight: 800;
  line-height: 1.1;
  margin-bottom: 2px;
}
.qsc-kpi-card .qsc-kpi-unit {
  font-size: 0.72rem;
  color: var(--qsc-muted);
  font-weight: 500;
  line-height: 1.1;
}
</style>

<div class="qsc-main">
<?php require_once __DIR__ . '/includes/quality_navbar.php'; ?>

<div class="qsc-content">

  <!-- KPI Cards -->
  <div class="qsc-kpi-grid" id="kpi-grid">
    <!-- Injected by JS -->
    <div class="qsc-kpi-card"><div class="qsc-kpi-label">Loading…</div></div>
  </div>


  <!-- Recent Records -->
  <div class="qsc-card">
    <div class="qsc-card-header">
      <span class="qsc-card-title"><i class="fas fa-clock-rotate-left" style="color:var(--qsc-primary);margin-right:6px;"></i>Recent Collection Records</span>
      <a href="bmw_collection.php" class="btn-qsc-outline" style="font-size:0.8rem;padding:6px 14px;">View All</a>
    </div>
    <div class="qsc-card-body p-0">
      <table class="table table-hover mb-0" id="recentTable">
        <thead style="background:#f8fafc;">
          <tr>
            <th class="ps-3">Date & Time</th>
            <th>Location</th>
            <th>Total (Kg)</th>
            <th>Status</th>
            <th>Logged By</th>
          </tr>
        </thead>
        <tbody id="recent-tbody">
          <tr><td colspan="5" class="text-center py-3 text-muted">Loading…</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div><!-- /.qsc-content -->
</div><!-- /.qsc-main -->

<script>
const KPI_COLOURS = {
  green:  { bg: 'var(--bin-green-bg)',  text: 'var(--bin-green-text)',  icon: '#16a34a' },
  red:    { bg: 'var(--bin-red-bg)',    text: 'var(--bin-red-text)',    icon: '#dc2626' },
  yellow: { bg: 'var(--bin-yellow-bg)', text: 'var(--bin-yellow-text)', icon: '#ca8a04' },
  blue:   { bg: 'var(--bin-blue-bg)',   text: 'var(--bin-blue-text)',   icon: '#2563eb' },
  white:  { bg: 'var(--bin-white-bg)',  text: 'var(--bin-white-text)',  icon: '#94a3b8' }
};

async function loadDashboard() {
  try {
    const res = await qscApi('/api/quality/dashboard');
    const d   = res.data;

    renderKPIs(d);
    renderRecentRecords(d.recent_records);
  } catch(e) {
    qscToast('Failed to load dashboard: ' + e.message, 'error');
  }
}

function renderKPIs(d) {
  const col    = d.today.collected;
  const disp   = d.today.dispatched;
  const netVar = parseFloat(disp.net_variance || 0);

  const cards = [
    {
      label: 'Total Waste Generated (Today)',
      value: parseFloat(col.total_weight || 0).toFixed(2),
      unit: `Kg &nbsp;(${col.entries} collections)`,
      icon: 'fa-biohazard',
      border: 'var(--qsc-primary)',
      iconBg: '#dcfce7',
      iconColor: 'var(--qsc-primary)',
      valColor: 'var(--qsc-green-deep)'
    },
    {
      label: 'Authorized Vendor Handover (Today)',
      value: parseFloat(disp.total_weight || 0).toFixed(2),
      unit: `Kg &nbsp;(${disp.dispatches} dispatches)`,
      icon: 'fa-truck-medical',
      border: '#2563eb',
      iconBg: '#dbeafe',
      iconColor: '#2563eb',
      valColor: '#1e3a8a'
    },
    {
      label: 'Pending Vendor Handover',
      value: d.pending_dispatch,
      unit: 'records awaiting dispatch',
      icon: 'fa-clock',
      border: '#f59e0b',
      iconBg: '#fef9c3',
      iconColor: '#ca8a04',
      valColor: '#92400e'
    },
    {
      label: 'Net Weight Variance',
      value: (netVar >= 0 ? '+' : '') + netVar.toFixed(2),
      unit: 'Kg (vendor − hospital)',
      icon: 'fa-scale-balanced',
      border: netVar > 0 ? '#dc2626' : (netVar < 0 ? '#16a34a' : '#64748b'),
      iconBg: '#f1f5f9',
      iconColor: '#64748b',
      valClass: netVar > 0 ? 'variance-positive' : (netVar < 0 ? 'variance-negative' : 'variance-zero'),
      valColor: ''
    },
    {
      label: 'Green Bin',
      value: parseFloat(col.green || 0).toFixed(2),
      unit: 'Kg collected today',
      icon: 'fa-trash-can',
      border: '#16a34a',
      iconBg: '#dcfce7',
      iconColor: '#16a34a',
      valColor: '#166534'
    },
    {
      label: 'Yellow Bin',
      value: parseFloat(col.yellow || 0).toFixed(2),
      unit: 'Kg collected today',
      icon: 'fa-trash-can',
      border: '#ca8a04',
      iconBg: '#fef9c3',
      iconColor: '#ca8a04',
      valColor: '#854d0e'
    },
    {
      label: 'Red Bin',
      value: parseFloat(col.red || 0).toFixed(2),
      unit: 'Kg collected today',
      icon: 'fa-trash-can',
      border: '#dc2626',
      iconBg: '#fee2e2',
      iconColor: '#dc2626',
      valColor: '#991b1b'
    },
    {
      label: 'Blue Bin',
      value: parseFloat(col.blue || 0).toFixed(2),
      unit: 'Kg collected today',
      icon: 'fa-trash-can',
      border: '#2563eb',
      iconBg: '#dbeafe',
      iconColor: '#2563eb',
      valColor: '#1e40af'
    },
    {
      label: 'White Bin',
      value: parseFloat(col.white || 0).toFixed(2),
      unit: 'Kg collected today',
      icon: 'fa-trash-can',
      border: '#64748b',
      iconBg: '#f1f5f9',
      iconColor: '#64748b',
      valColor: '#334155'
    }
  ];

  const kpiGrid = document.getElementById('kpi-grid');
  kpiGrid.innerHTML = cards.map(c => `
    <div class="qsc-kpi-card" style="border-top: 3px solid ${c.border};">
      <div class="d-flex align-items-start justify-content-between gap-1 mb-1">
        <div class="qsc-kpi-label" title="${c.label}">${c.label}</div>
        <div class="qsc-kpi-icon flex-shrink-0" style="background: ${c.iconBg}; color: ${c.iconColor};">
          <i class="fas ${c.icon}"></i>
        </div>
      </div>
      <div class="mt-auto">
        <div class="qsc-kpi-value ${c.valClass || ''}" style="${c.valColor ? 'color:' + c.valColor + ';' : ''}">${c.value}</div>
        <div class="qsc-kpi-unit">${c.unit}</div>
      </div>
    </div>
  `).join('');
}


function renderRecentRecords(records) {
  const tbody = document.getElementById('recent-tbody');
  if (!records.length) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-muted">No records today.</td></tr>';
    return;
  }
  tbody.innerHTML = records.map(r => `
    <tr>
      <td class="ps-3">${r.collection_at ?? '—'}</td>
      <td>${r.location}</td>
      <td><strong>${parseFloat(r.h_total_weight).toFixed(2)} Kg</strong></td>
      <td>${statusBadge(r.status)}</td>
      <td>${r.logged_by_user ?? '—'}</td>
    </tr>`).join('');
}

loadDashboard();
</script>

<?php require_once __DIR__ . '/includes/quality_foot.php'; ?>
