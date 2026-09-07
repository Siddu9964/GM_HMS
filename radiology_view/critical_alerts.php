<?php
$pageTitle = 'Critical Radiology Alerts';
$pageIcon  = 'fa-exclamation-triangle';
$navTitle  = 'Critical Alert Center';
$navSub    = 'STAT scan requests and red-flag radiological findings requiring immediate clinician call';
require_once 'includes/rad_head.php';
?>
<?php require_once 'includes/rad_sidebar.php'; ?>

<div class="lis-main-content">
<?php require_once 'includes/rad_navbar.php'; ?>

<div class="lis-content">

  <!-- Page Header -->
  <div class="lis-page-header lis-fade-up">
    <div>
      <div class="lis-page-title">
        <div class="lis-page-title-icon" style="background:linear-gradient(135deg,#dc2626,#991b1b);color:white;border:none;">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div>
          Critical Alert Center
          <div class="lis-page-subtitle">Immediate communication protocol for life-threatening radiological findings & STAT emergency orders</div>
        </div>
      </div>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
      <span class="lis-breadcrumb-pill" style="background:#fee2e2;color:#dc2626;border-color:#fca5a5;">
        <i class="fas fa-circle" style="font-size:0.4rem;color:#dc2626;animation:lisPulseRed 1.5s infinite;"></i>
        Live Emergency Queue
      </span>
      <button class="lis-btn lis-btn-outline" onclick="loadCriticalAlerts()">
        <i class="fas fa-sync-alt"></i> Refresh
      </button>
    </div>
  </div>

  <!-- Filter Bar -->
  <div class="lis-filter-bar lis-fade-up-1">
    <span style="font-size:0.68rem;font-weight:800;color:var(--lis-text-muted);text-transform:uppercase;letter-spacing:0.08em;">Priority Filter:</span>
    <button class="lis-filter-chip active" data-filter="all" onclick="setFilter('all',this)"><i class="fas fa-list"></i> All Alerts</button>
    <button class="lis-filter-chip" data-filter="STAT" onclick="setFilter('STAT',this)"><i class="fas fa-bolt"></i> STAT / Emergency</button>
    <button class="lis-filter-chip" data-filter="Pending" onclick="setFilter('Pending',this)"><i class="fas fa-clock"></i> Unreported</button>
    <div style="margin-left:auto;">
      <span class="lis-badge" id="alertCountBadge" style="background:#fee2e2;color:#dc2626;font-size:0.75rem;padding:5px 14px;font-weight:700;">Loading...</span>
    </div>
  </div>

  <!-- Alerts Grid -->
  <div class="lis-grid-2 lis-fade-up-2">

    <!-- Left: STAT Orders / Urgent Imaging Queue -->
    <div>
      <div class="lis-card" style="margin-bottom:20px;">
        <div class="lis-card-header" style="border-left:4px solid #dc2626;">
          <div class="lis-card-title" style="color:#dc2626;">
            <i class="fas fa-bolt" style="color:#dc2626;"></i>
            STAT & High Priority Imaging Orders
          </div>
          <span class="lis-badge lis-badge-urgent" id="statBadgeCount">0</span>
        </div>
        <div id="statOrdersList" style="padding:16px;display:flex;flex-direction:column;gap:12px;">
          <div style="padding:30px;text-align:center;color:var(--lis-text-muted);">
            <div class="lis-spinner" style="margin:0 auto 8px;"></div>
            <div>Scanning for active emergency radiology orders...</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right: Critical Diagnostic Action Protocol Reference -->
    <div>
      <div class="lis-card" style="margin-bottom:20px;">
        <div class="lis-card-header">
          <div class="lis-card-title"><i class="fas fa-bullhorn" style="color:#0284c7;"></i> Red-Flag Radiological Findings Protocol</div>
        </div>
        <div class="lis-card-body" style="padding:0;">
          <table class="lis-table" style="font-size:0.8rem;margin:0;">
            <thead>
              <tr style="background:#f8fafc;">
                <th>Modality</th>
                <th>Critical Finding</th>
                <th>Target Alert Time</th>
                <th>Immediate Action</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><span class="lis-badge" style="background:#fef3c7;color:#d97706;font-size:0.65rem;">CT Scan</span></td>
                <td><strong>Acute Intracranial Hemorrhage / Midline Shift</strong></td>
                <td><span class="lis-badge lis-badge-stat">&lt; 15 mins</span></td>
                <td>Call Trauma / Neurosurgeon</td>
              </tr>
              <tr>
                <td><span class="lis-badge" style="background:#e0f2fe;color:#0284c7;font-size:0.65rem;">X-Ray</span></td>
                <td><strong>Tension Pneumothorax / Free Peritoneal Air</strong></td>
                <td><span class="lis-badge lis-badge-stat">&lt; 15 mins</span></td>
                <td>Call ER Physician for Decompression</td>
              </tr>
              <tr>
                <td><span class="lis-badge" style="background:#fae8ff;color:#a855f7;font-size:0.65rem;">Doppler</span></td>
                <td><strong>Acute Lower Limb DVT (Proximal)</strong></td>
                <td><span class="lis-badge lis-badge-urgent">&lt; 30 mins</span></td>
                <td>Alert Inpatient Ward / Anticoagulation</td>
              </tr>
              <tr>
                <td><span class="lis-badge" style="background:#dcfce7;color:#16a34a;font-size:0.65rem;">USG</span></td>
                <td><strong>Ruptured Ectopic Pregnancy / Hemoperitoneum</strong></td>
                <td><span class="lis-badge lis-badge-stat">&lt; 15 mins</span></td>
                <td>Alert OB/GYN On-Call</td>
              </tr>
              <tr>
                <td><span class="lis-badge" style="background:#fef3c7;color:#d97706;font-size:0.65rem;">CT Scan</span></td>
                <td><strong>Acute Aortic Dissection / Pulmonary Embolism</strong></td>
                <td><span class="lis-badge lis-badge-stat">&lt; 20 mins</span></td>
                <td>Call Cardiology / Critical Care</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Quick Escalation Contacts -->
      <div class="lis-card">
        <div class="lis-card-header">
          <div class="lis-card-title"><i class="fas fa-phone-alt"></i> Emergency Escalation Extensions</div>
        </div>
        <div class="lis-card-body" style="padding:16px;display:flex;flex-direction:column;gap:8px;">
          <div style="display:flex;justify-content:space-between;padding:8px 12px;background:#f8fafc;border-radius:8px;">
            <span><i class="fas fa-ambulance" style="color:#dc2626;margin-right:8px;"></i> Emergency / Casualty (Casualty MO)</span>
            <strong style="color:#0f172a;">Ext: 101 / 102</strong>
          </div>
          <div style="display:flex;justify-content:space-between;padding:8px 12px;background:#f8fafc;border-radius:8px;">
            <span><i class="fas fa-heartbeat" style="color:#0284c7;margin-right:8px;"></i> Intensive Care Unit (ICU In-charge)</span>
            <strong style="color:#0f172a;">Ext: 201</strong>
          </div>
          <div style="display:flex;justify-content:space-between;padding:8px 12px;background:#f8fafc;border-radius:8px;">
            <span><i class="fas fa-procedures" style="color:#059669;margin-right:8px;"></i> Operation Theatre (Main OT)</span>
            <strong style="color:#0f172a;">Ext: 305</strong>
          </div>
        </div>
      </div>
    </div>

  </div>

</div>
</div>

<script>
let currentFilter = 'all';
let statOrders = [];

document.addEventListener('DOMContentLoaded', () => {
  loadCriticalAlerts();
});

function setFilter(filter, btn) {
  currentFilter = filter;
  document.querySelectorAll('.lis-filter-chip').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  renderAlerts();
}

async function loadCriticalAlerts() {
  const container = document.getElementById('statOrdersList');
  container.innerHTML = '<div style="text-align:center;padding:30px;"><div class="lis-spinner" style="margin:0 auto 8px;"></div><div>Refreshing emergency scan orders...</div></div>';

  try {
    // Fetch pending OPD and IPD orders
    const [opdRes, ipdRes] = await Promise.all([
      fetch('/GM_HMS/api/radiology/orders?status=all').then(r => r.json()).catch(() => ({ data: [] })),
      fetch('/GM_HMS/api/radiology/ipd-orders').then(r => r.json()).catch(() => ({ data: [] }))
    ]);

    const opd = (opdRes.success && Array.isArray(opdRes.data)) ? opdRes.data : [];
    const ipd = (ipdRes.success && Array.isArray(ipdRes.data)) ? ipdRes.data : [];

    statOrders = [];

    // Tag and combine
    opd.forEach(o => {
      const isStat = (o.priority === 'STAT' || o.priority === 'Urgent' || (o.test_name && o.test_name.toLowerCase().includes('emergency')));
      if (isStat || o.status !== 'Completed') {
        statOrders.push({ ...o, source: 'OPD', is_stat: isStat });
      }
    });

    ipd.forEach(i => {
      const isStat = (i.priority === 'STAT' || i.priority === 'Urgent' || (i.ward_name && i.ward_name.toLowerCase().includes('icu')));
      if (isStat || i.status !== 'Completed') {
        statOrders.push({ ...i, source: 'IPD', is_stat: isStat });
      }
    });

    document.getElementById('statBadgeCount').textContent = statOrders.length;
    document.getElementById('alertCountBadge').textContent = `${statOrders.length} Active Queue`;
    renderAlerts();
  } catch(e) {
    container.innerHTML = '<div style="color:red;text-align:center;padding:20px;">Failed to load critical alerts.</div>';
  }
}

function renderAlerts() {
  const container = document.getElementById('statOrdersList');
  container.innerHTML = '';

  let list = statOrders;
  if (currentFilter === 'STAT') {
    list = list.filter(o => o.is_stat);
  } else if (currentFilter === 'Pending') {
    list = list.filter(o => o.status !== 'Completed');
  }

  if (list.length === 0) {
    container.innerHTML = `
      <div style="text-align:center;padding:40px;color:#059669;background:#f0fdf4;border-radius:10px;">
        <i class="fas fa-check-circle" style="font-size:2.2rem;margin-bottom:10px;"></i>
        <div style="font-weight:700;font-size:1rem;">All Clear! No Pending Critical Alerts</div>
        <div style="font-size:0.8rem;color:#15803d;margin-top:4px;">No unprocessed STAT emergency imaging scans in the active queue.</div>
      </div>
    `;
    return;
  }

  list.forEach(o => {
    let testName = o.test_name || o.item_name || 'Radiology Scan';
    try {
      const d = JSON.parse(testName);
      if (Array.isArray(d)) testName = d.join(', ');
    } catch(e){}

    const pName = o.patient_name || 'Patient';
    const bedInfo = (o.source === 'IPD' && o.bed_number) ? `Bed ${o.bed_number} (${o.ward_name || 'Ward'})` : 'Outpatient';
    const isStat = o.is_stat;

    const div = document.createElement('div');
    div.style = `border-radius:10px;padding:14px;background:#fff;border:1px solid ${isStat ? '#fca5a5' : '#e2e8f0'};box-shadow:0 2px 6px rgba(0,0,0,0.03);`;
    div.innerHTML = `
      <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;">
        <div>
          <span class="lis-badge" style="${isStat ? 'background:#fee2e2;color:#dc2626;font-weight:800;' : 'background:#e0f2fe;color:#0284c7;'}font-size:0.7rem;padding:2px 8px;">
            ${isStat ? '<i class="fas fa-bolt"></i> STAT SCAN' : 'ROUTINE'}
          </span>
          <span class="lis-badge" style="background:#f1f5f9;color:#475569;font-size:0.7rem;margin-left:4px;">${o.source}</span>
          <strong style="margin-left:8px;color:#0f172a;font-size:0.95rem;">${escHtml(pName)}</strong>
        </div>
        <span style="font-weight:700;font-size:0.8rem;color:#0284c7;">#${escHtml(o.order_id || '')}</span>
      </div>
      <div style="font-size:0.85rem;font-weight:600;color:#334155;margin-bottom:4px;">
        <i class="fas fa-x-ray" style="color:var(--lis-primary);margin-right:6px;"></i> ${escHtml(testName)}
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;font-size:0.78rem;color:#64748b;margin-top:8px;padding-top:8px;border-top:1px dashed #e2e8f0;">
        <span><i class="fas fa-map-marker-alt"></i> ${escHtml(bedInfo)}</span>
        <div style="display:flex;gap:6px;">
          <a href="${o.source === 'IPD' ? 'ipd_test_orders.php' : 'test_orders.php'}?order_id=${encodeURIComponent(o.order_id)}" class="lis-btn lis-btn-primary lis-btn-sm" style="font-size:0.72rem;padding:4px 10px;">
            <i class="fas fa-edit"></i> Report Scan
          </a>
        </div>
      </div>
    `;
    container.appendChild(div);
  });
}
</script>

<?php require_once 'includes/rad_foot.php'; ?>
