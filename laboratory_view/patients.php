<?php
$pageTitle = 'Patient Lab History';
$pageIcon  = 'fa-user-injured';
$navTitle  = 'Patients';
$navSub    = 'Search patients and view their lab order history';
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
        <div class="lis-page-title-icon"><i class="fas fa-user-injured"></i></div>
        <div>Patients
          <div class="lis-page-subtitle">Search patients and review their lab history</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Two-column layout: search + detail -->
  <div style="display:grid;grid-template-columns:340px 1fr;gap:20px;align-items:start;" class="lis-fade-up-1">

    <!-- LEFT: Patient Search -->
    <div class="lis-card" style="position:sticky;top:80px;">
      <div class="lis-card-header">
        <div class="lis-card-title"><i class="fas fa-search"></i> Find Patient</div>
      </div>
      <div class="lis-card-body">
        <div class="lis-form-group" style="margin-bottom:8px;">
          <div class="lis-search-wrap" style="max-width:100%;">
            <i class="fas fa-search"></i>
            <input type="text" class="lis-input" id="patient-search"
                   placeholder="Name, ID or phone..." autocomplete="off" oninput="onPatientSearch(this.value)">
          </div>
        </div>

        <div id="patient-list-results" style="display:flex;flex-direction:column;gap:6px;margin-top:10px;">
          <div class="lis-empty" style="padding:30px 10px;">
            <i class="fas fa-user-search" style="font-size:2rem;opacity:0.15;display:block;margin-bottom:8px;text-align:center;"></i>
            <div class="lis-empty-title" style="text-align:center;">Type to search patients</div>
          </div>
        </div>
      </div>
    </div>

    <!-- RIGHT: Patient detail + lab history -->
    <div>
      <div id="patient-detail-empty" class="lis-card">
        <div class="lis-card-body">
          <div class="lis-empty" style="padding:60px 20px;">
            <i class="fas fa-user-circle"></i>
            <div class="lis-empty-title">No patient selected</div>
            <div class="lis-empty-sub">Search and select a patient to see their lab history</div>
          </div>
        </div>
      </div>

      <div id="patient-detail-panel" style="display:none;">

        <!-- Patient Info Card -->
        <div class="lis-card lis-fade-up" id="patient-info-card" style="margin-bottom:20px;">
          <div class="lis-card-body">
            <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
              <div style="width:64px;height:64px;background:linear-gradient(135deg,var(--lis-primary),var(--lis-accent));border-radius:16px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;font-weight:800;flex-shrink:0;" id="pt-avatar">?</div>
              <div style="flex:1;">
                <div style="font-size:1.2rem;font-weight:800;color:var(--lis-text);letter-spacing:-0.3px;" id="pt-name">—</div>
                <div style="font-size:0.8rem;color:var(--lis-text-muted);margin-top:4px;display:flex;gap:16px;flex-wrap:wrap;">
                  <span><i class="fas fa-id-card" style="color:var(--lis-primary);margin-right:4px;"></i><span id="pt-id">—</span></span>
                  <span><i class="fas fa-birthday-cake" style="color:var(--lis-primary);margin-right:4px;"></i><span id="pt-age">—</span></span>
                  <span><i class="fas fa-venus-mars" style="color:var(--lis-primary);margin-right:4px;"></i><span id="pt-sex">—</span></span>
                  <span><i class="fas fa-phone" style="color:var(--lis-primary);margin-right:4px;"></i><span id="pt-phone">—</span></span>
                  <span><i class="fas fa-tint" style="color:var(--lis-primary);margin-right:4px;"></i><span id="pt-blood">—</span></span>
                </div>
              </div>
              <div style="display:flex;gap:8px;">
                <button class="lis-btn lis-btn-primary" onclick="newOrderForPatient()">
                  <i class="fas fa-plus"></i> New Order
                </button>
                <button class="lis-btn lis-btn-outline" onclick="window.print()">
                  <i class="fas fa-print"></i> Print History
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Lab History -->
        <div class="lis-card lis-fade-up-1">
          <div class="lis-card-header">
            <div class="lis-card-title"><i class="fas fa-history"></i> Lab Order History
              <span id="history-count-badge" style="background:#e0f2fe;color:#1f6b4a;font-size:0.65rem;font-weight:800;padding:2px 8px;border-radius:20px;margin-left:6px;">0</span>
            </div>
          </div>
          <div class="lis-card-body" style="padding:16px;">
            <div id="history-loading" style="display:none;align-items:center;justify-content:center;gap:10px;padding:30px;color:var(--lis-text-muted);">
              <div class="lis-spinner"></div> Loading history...
            </div>
            <div id="history-empty" class="lis-empty" style="display:none;">
              <i class="fas fa-flask"></i>
              <div class="lis-empty-title">No lab orders found</div>
              <div class="lis-empty-sub">This patient has no laboratory test records</div>
            </div>
            <div id="history-content" class="lis-table-wrap" style="display:none;">
              <table class="lis-table">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Order ID</th>
                    <th>Test Name</th>
                    <th>Doctor</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="history-tbody"></tbody>
              </table>
            </div>
          </div>
        </div>

      </div><!-- /#patient-detail-panel -->
    </div><!-- right col -->

  </div><!-- grid -->

</div><!-- /.lis-content -->
<?php require_once 'includes/lab_foot.php'; ?>

<script>
let selectedPatientId = null;
let patientSearchTimer;

document.addEventListener('DOMContentLoaded', () => {
  doPatientSearch(''); // Load recent patients by default
});

function onPatientSearch(q) {
  clearTimeout(patientSearchTimer);
  q = q.trim();
  if (q.length === 1) { // Only show warning if exactly 1 character is typed
    document.getElementById('patient-list-results').innerHTML = `
      <div class="lis-empty" style="padding:30px 10px;">
        <i class="fas fa-user-search" style="font-size:2rem;opacity:0.15;display:block;margin-bottom:8px;text-align:center;"></i>
        <div class="lis-empty-title" style="text-align:center;">Type at least 2 characters</div>
      </div>`;
    return;
  }
  patientSearchTimer = setTimeout(() => doPatientSearch(q), 350);
}

async function doPatientSearch(q) {
  const list = document.getElementById('patient-list-results');
  list.innerHTML = '<div style="padding:16px;text-align:center;"><div class="lis-spinner" style="margin:auto;"></div></div>';
  try {
    const data = await lisApi('GET', `/api/appointments?search=${encodeURIComponent(q)}&limit=25`);
    const appointments = data.data || (Array.isArray(data)?data:[]);
    
    // Deduplicate appointments by patient_id so we don't show the same patient 10 times
    const uniquePatients = [];
    const seen = new Set();
    for (const apt of appointments) {
      if (!seen.has(apt.patient_id)) {
        seen.add(apt.patient_id);
        uniquePatients.push(apt);
      }
    }

    if (!uniquePatients.length) {
      list.innerHTML = '<div class="lis-empty" style="padding:20px 10px;"><i class="fas fa-user-slash"></i><div class="lis-empty-title" style="text-align:center;">No patients found</div></div>';
      return;
    }
    list.innerHTML = uniquePatients.map(p => `
      <div class="patient-result-card" onclick="selectPatient(${JSON.stringify(p).replace(/"/g,'&quot;')})"
           style="padding:10px 12px;border:1.5px solid var(--lis-border);border-radius:10px;cursor:pointer;transition:all 0.2s;"
           onmouseover="this.style.borderColor='var(--lis-primary)';this.style.background='#f0f9ff';"
           onmouseout="this.style.borderColor='var(--lis-border)';this.style.background='';">
        <div style="font-weight:700;font-size:0.82rem;">${escHtml(p.patient_name || p.first_name || 'Walk-in')}</div>
        <div style="font-size:0.68rem;color:var(--lis-text-muted);margin-top:2px;">
          ${escHtml(p.patient_id)} &bull; ${escHtml(p.patient_phone || p.appointment_phone || '—')}
        </div>
      </div>`).join('');
  } catch(e) {
    list.innerHTML = '<div class="lis-empty" style="padding:20px;"><div style="color:#ef4444;text-align:center;font-size:0.8rem;">Error loading appointments</div></div>';
  }
}

function selectPatient(p) {
  selectedPatientId = p.patient_id;
  const name = (p.patient_name || p.first_name || 'Walk-in Patient').trim();
  const init = name.charAt(0).toUpperCase();

  document.getElementById('patient-detail-empty').style.display = 'none';
  document.getElementById('patient-detail-panel').style.display  = 'block';

  document.getElementById('pt-avatar').textContent = init;
  document.getElementById('pt-name').textContent   = name;
  document.getElementById('pt-id').textContent     = p.patient_id || '—';
  
  // The appointments API doesn't return age/sex natively, so we just hide or put N/A
  document.getElementById('pt-age').textContent    = p.age ? (p.age + ' yrs') : 'Age N/A';
  document.getElementById('pt-sex').textContent    = p.sex || 'N/A';
  
  document.getElementById('pt-phone').textContent  = p.patient_phone || p.appointment_phone || p.phone || '—';
  document.getElementById('pt-blood').textContent  = p.blood_group || '—';

  loadPatientHistory(p.patient_id);
}

async function loadPatientHistory(patientId) {
  document.getElementById('history-loading').style.display  = 'flex';
  document.getElementById('history-content').style.display  = 'none';
  document.getElementById('history-empty').style.display    = 'none';

  try {
    const data = await lisApi('GET', `/api/laboratory/orders?all=1&search=${encodeURIComponent(patientId)}`);
    const orders = data.data || [];

    // Filter strictly by this patient
    const filtered = orders.filter(o => o.patient_id === patientId);

    document.getElementById('history-loading').style.display = 'none';
    document.getElementById('history-count-badge').textContent = filtered.length;

    if (!filtered.length) {
      document.getElementById('history-empty').style.display = 'block';
      return;
    }

    document.getElementById('history-content').style.display = 'block';
    const tbody = document.getElementById('history-tbody');
    tbody.innerHTML = filtered.map((o,i) => {
      const priCls  = {Urgent:'lis-badge-urgent',Stat:'lis-badge-stat',Routine:'lis-badge-routine'}[o.priority]||'lis-badge-routine';
      const statCls = {'Ordered':'lis-badge-ordered','In Progress':'lis-badge-progress','Completed':'lis-badge-completed','Reported':'lis-badge-reported'}[o.status]||'lis-badge-ordered';
      return `<tr>
        <td style="color:var(--lis-text-muted);font-weight:700;">${i+1}</td>
        <td><code style="font-size:0.68rem;background:#f1f5f9;padding:2px 6px;border-radius:5px;">${escHtml(o.order_id)}</code></td>
        <td style="font-weight:700;">${escHtml(o.test_name)}</td>
        <td style="font-size:0.78rem;">${escHtml(o.doctor_name||'—')}</td>
        <td><span class="lis-badge ${priCls}">${escHtml(o.priority||'Routine')}</span></td>
        <td><span class="lis-badge ${statCls}">${escHtml(o.status)}</span></td>
        <td style="font-size:0.75rem;color:var(--lis-text-muted);">${o.order_date||'—'}</td>
        <td>
          <a href="print_report.php?order_id=${encodeURIComponent(o.order_id)}" target="_blank"
             class="lis-btn lis-btn-outline lis-btn-sm lis-btn-icon" title="Print">
            <i class="fas fa-print"></i>
          </a>
        </td>
      </tr>`;
    }).join('');

  } catch(e) {
    document.getElementById('history-loading').style.display = 'none';
    lisToast('Failed to load history', 'error');
  }
}

function newOrderForPatient() {
  window.location.href = 'test_orders.php';
}

function escHtml(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
