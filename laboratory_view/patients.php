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

  <style>
    /* Premium UI Styles for Patients Page */
    .pat-search-hero {
      background: #fff;
      border-radius: 16px;
      padding: 40px 20px;
      text-align: center;
      box-shadow: 0 10px 30px rgba(31, 107, 74, 0.05);
      margin-bottom: 30px;
      position: relative;
      overflow: hidden;
    }
    .pat-search-hero::before {
      content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
      background: linear-gradient(90deg, var(--lis-primary), var(--lis-accent));
    }
    .pat-search-hero h2 {
      font-weight: 800; color: #1e293b; margin-bottom: 10px; font-size: 1.8rem;
    }
    .pat-search-hero p {
      color: var(--lis-text-muted); margin-bottom: 25px;
    }
    .pat-search-hero input {
      font-size: 1.1rem;
      padding: 16px 24px 16px 50px;
      border-radius: 50px;
      border: 2px solid #e2e8f0;
      width: 100%;
      max-width: 600px;
      transition: all 0.3s ease;
      background: #f8fafc;
    }
    .pat-search-hero input:focus {
      border-color: var(--lis-primary);
      background: #fff;
      box-shadow: 0 0 0 5px rgba(31, 107, 74, 0.1);
      outline: none;
    }
    .pat-search-icon {
      position: absolute; left: 20px; top: 50%; transform: translateY(-50%);
      color: #94a3b8; font-size: 1.2rem; transition: color 0.3s;
    }
    .pat-search-wrap:focus-within .pat-search-icon { color: var(--lis-primary); }
    .pat-search-wrap { position: relative; display: inline-block; width: 100%; max-width: 600px; }

    /* Result List */
    .pat-result-list {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 12px;
      max-width: 800px;
      margin: 0 auto;
    }
    .pat-list-item {
      width: 100%;
      background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
      padding: 16px 20px; cursor: pointer; transition: all 0.2s;
      display: flex; align-items: center; text-align: left; gap: 20px;
      position: relative; overflow: hidden;
    }
    .pat-list-item:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(31, 107, 74, 0.08);
      border-color: var(--lis-primary);
    }
    
    .pat-list-avatar {
      width: 50px; height: 50px; border-radius: 50%;
      background: linear-gradient(135deg, var(--lis-primary), #115035);
      color: #fff; font-size: 1.4rem; font-weight: 800;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; box-shadow: 0 4px 10px rgba(31, 107, 74, 0.2);
    }
    .pat-list-content { flex: 1; }
    .pat-list-name { font-weight: 800; font-size: 1.1rem; color: #1e293b; margin-bottom: 2px; }
    .pat-list-details { font-size: 0.85rem; color: var(--lis-text-muted); display:flex; align-items:center; gap:12px; }
    .pat-list-id { color: var(--lis-primary); font-weight: 700; background: #eef2ff; padding: 2px 8px; border-radius: 8px; }

    /* Offcanvas */
    .pat-offcanvas {
      position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      z-index: 1050; display: none;
    }
    .pat-offcanvas.show { display: block; }
    .pat-offcanvas-overlay {
      position: absolute; top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);
      opacity: 0; transition: opacity 0.3s ease;
    }
    .pat-offcanvas.show .pat-offcanvas-overlay { opacity: 1; }
    
    .pat-offcanvas-content {
      position: absolute; top: 0; right: -100%; width: 100%; max-width: 750px;
      height: 100%; background: #f8fafc;
      box-shadow: -10px 0 40px rgba(0,0,0,0.2);
      transition: right 0.35s cubic-bezier(0.4, 0, 0.2, 1);
      display: flex; flex-direction: column;
    }
    .pat-offcanvas.show .pat-offcanvas-content { right: 0; }
    
    .pat-offcanvas-close {
      position: absolute; top: 20px; right: 20px;
      background: rgba(255,255,255,0.2); border: none; color: #fff;
      width: 40px; height: 40px; border-radius: 50%;
      cursor: pointer; z-index: 10; display:flex; align-items:center; justify-content:center;
      backdrop-filter: blur(10px); transition: background 0.2s;
    }
    .pat-offcanvas-close:hover { background: rgba(255,255,255,0.4); }

    .pat-offcanvas-header {
      background: linear-gradient(135deg, var(--lis-primary), #115035);
      padding: 50px 40px 40px; color: #fff; position: relative;
    }
    .pat-oc-avatar {
      width: 90px; height: 90px; border-radius: 50%; background: #fff; color: var(--lis-primary);
      font-size: 2.5rem; font-weight: 800; display:flex; align-items:center; justify-content:center;
      box-shadow: 0 10px 25px rgba(0,0,0,0.3); margin-bottom: 20px;
    }
    .pat-oc-name { font-size: 1.8rem; font-weight: 800; margin-bottom: 6px; letter-spacing: -0.5px; }
    .pat-oc-id { font-size: 1rem; opacity: 0.9; font-family: monospace; }
    
    .pat-oc-stats { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
    .pat-oc-pill {
      background: rgba(255,255,255,0.15); padding: 6px 14px; border-radius: 20px;
      font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 6px;
      backdrop-filter: blur(5px); border: 1px solid rgba(255,255,255,0.1);
    }

    .pat-oc-actions {
      display: flex; gap: 10px; margin-top: 25px;
    }
    .pat-oc-actions button {
      flex: 1; padding: 12px; border-radius: 12px; font-weight: 700; border: none; cursor: pointer; transition: all 0.2s;
    }
    .btn-oc-primary { background: #fff; color: var(--lis-primary); }
    .btn-oc-primary:hover { background: #f1f5f9; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
    .btn-oc-secondary { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.3) !important; }
    .btn-oc-secondary:hover { background: rgba(255,255,255,0.2); transform: translateY(-2px); }

    .pat-offcanvas-body { padding: 30px; flex: 1; overflow-y: auto; }
  </style>

  <!-- Central Search Hero -->
  <div class="pat-search-hero lis-fade-up">
    <h2><i class="fas fa-search-heart" style="color:var(--lis-primary); margin-right:10px;"></i> Find a Patient</h2>
    <p>Search by name, ID, or phone number to view comprehensive lab history.</p>
    <div class="pat-search-wrap">
      <i class="fas fa-search pat-search-icon"></i>
      <input type="text" id="patient-search" placeholder="Enter patient details..." autocomplete="off" oninput="onPatientSearch(this.value)">
    </div>
  </div>

  <!-- Search Results Area -->
  <div id="patient-results-area" class="lis-fade-up-1">
    <div class="lis-empty" style="padding: 60px 20px;" id="patient-initial-empty">
      <i class="fas fa-users-medical" style="font-size:3.5rem; color:var(--lis-primary); opacity:0.2; margin-bottom:15px;"></i>
      <div class="lis-empty-title">Patient Directory</div>
      <div class="lis-empty-sub">Start typing above to quickly find patients and access their lab records.</div>
    </div>
    
    <div id="patient-list-results" class="pat-result-list" style="display:none;"></div>
  </div>

  <!-- Offcanvas Patient Detail Panel -->
  <div class="pat-offcanvas" id="pat-offcanvas">
    <div class="pat-offcanvas-overlay" onclick="closeOffcanvas()"></div>
    <div class="pat-offcanvas-content">
      <button class="pat-offcanvas-close" onclick="closeOffcanvas()"><i class="fas fa-times"></i></button>
      
      <div class="pat-offcanvas-header">
        <div class="pat-oc-avatar" id="pt-avatar">?</div>
        <div class="pat-oc-name" id="pt-name">—</div>
        <div class="pat-oc-id"><i class="fas fa-id-card"></i> <span id="pt-id">—</span></div>
        
        <div class="pat-oc-stats">
          <div class="pat-oc-pill"><i class="fas fa-birthday-cake"></i> <span id="pt-age">—</span></div>
          <div class="pat-oc-pill"><i class="fas fa-venus-mars"></i> <span id="pt-sex">—</span></div>
          <div class="pat-oc-pill"><i class="fas fa-phone"></i> <span id="pt-phone">—</span></div>
          <div class="pat-oc-pill"><i class="fas fa-tint"></i> <span id="pt-blood">—</span></div>
        </div>

        <div class="pat-oc-actions">
          <button class="btn-oc-primary" onclick="newOrderForPatient()"><i class="fas fa-plus-circle"></i> New Order</button>
          <button class="btn-oc-secondary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
        </div>
      </div>

      <div class="pat-offcanvas-body">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
          <h4 style="font-weight:800; color:#1e293b; margin:0;"><i class="fas fa-history" style="color:var(--lis-primary);"></i> Lab History</h4>
          <span id="history-count-badge" style="background:#e0f2fe; color:var(--lis-primary); font-size:0.75rem; font-weight:800; padding:4px 10px; border-radius:20px;">0 Records</span>
        </div>

        <div id="history-loading" style="display:none; text-align:center; padding:40px; color:var(--lis-text-muted);">
          <div class="lis-spinner" style="margin:auto; margin-bottom:10px;"></div> Loading records...
        </div>

        <div id="history-empty" class="lis-empty" style="display:none; padding:40px 10px; background:#fff; border-radius:12px; border:1px dashed #cbd5e1;">
          <i class="fas fa-flask" style="opacity:0.3; margin-bottom:10px;"></i>
          <div class="lis-empty-title" style="font-size:1.1rem;">No lab records found</div>
          <div class="lis-empty-sub">This patient hasn't had any tests ordered yet.</div>
        </div>

        <div id="history-content" style="display:none;">
          <!-- Using lis-card to wrap the table beautifully -->
          <div class="lis-card" style="box-shadow:none; border:1px solid #e2e8f0;">
            <div class="lis-table-wrap">
              <table class="lis-table">
                <thead style="background:#f8fafc;">
                  <tr>
                    <th>Order ID</th>
                    <th>Test Name</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody id="history-tbody"></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

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
  
  const initialEmpty = document.getElementById('patient-initial-empty');
  const resultsGrid = document.getElementById('patient-list-results');
  
  if (q.length === 1) { 
    initialEmpty.style.display = 'none';
    resultsGrid.style.display = 'flex';
    resultsGrid.innerHTML = `
      <div style="width: 100%;">
        <div class="lis-empty" style="padding:40px 10px;">
          <i class="fas fa-keyboard" style="font-size:2.5rem;opacity:0.15;display:block;margin-bottom:15px;text-align:center;"></i>
          <div class="lis-empty-title" style="text-align:center;">Keep typing...</div>
          <div class="lis-empty-sub" style="text-align:center;">Enter at least 2 characters to search</div>
        </div>
      </div>`;
    return;
  }
  
  patientSearchTimer = setTimeout(() => doPatientSearch(q), 350);
}

async function doPatientSearch(q) {
  const list = document.getElementById('patient-list-results');
  document.getElementById('patient-initial-empty').style.display = 'none';
  list.style.display = 'flex';
  list.innerHTML = '<div style="width:100%; padding:40px; text-align:center;"><div class="lis-spinner" style="margin:auto; width:40px; height:40px; border-width:4px;"></div></div>';
  
  try {
    const data = await lisApi('GET', `/api/appointments?search=${encodeURIComponent(q)}&limit=25`);
    const appointments = data.data || (Array.isArray(data)?data:[]);
    
    // Deduplicate appointments by patient_id
    const uniquePatients = [];
    const seen = new Set();
    for (const apt of appointments) {
      if (!seen.has(apt.patient_id)) {
        seen.add(apt.patient_id);
        uniquePatients.push(apt);
      }
    }

    if (!uniquePatients.length) {
      list.innerHTML = `
        <div style="width: 100%;">
          <div class="lis-empty" style="padding:40px 10px;">
            <i class="fas fa-user-slash" style="font-size:3rem; color:#ef4444; opacity:0.2; margin-bottom:15px;"></i>
            <div class="lis-empty-title" style="text-align:center;">No patients found</div>
            <div class="lis-empty-sub" style="text-align:center;">Try a different search term</div>
          </div>
        </div>`;
      return;
    }
    
    list.innerHTML = uniquePatients.map(p => {
      const name = p.patient_name || p.first_name || 'Walk-in';
      const initial = name.charAt(0).toUpperCase();
      const phone = p.patient_phone || p.appointment_phone || 'No phone';
      return `
      <div class="pat-list-item" onclick="selectPatient(${JSON.stringify(p).replace(/"/g,'&quot;')})">
        <div class="pat-list-avatar">${initial}</div>
        <div class="pat-list-content">
          <div class="pat-list-name">${escHtml(name)}</div>
          <div class="pat-list-details">
            <span class="pat-list-id">${escHtml(p.patient_id)}</span>
            <span><i class="fas fa-phone-alt"></i> ${escHtml(phone)}</span>
          </div>
        </div>
        <div><i class="fas fa-chevron-right" style="color:var(--lis-primary); opacity:0.5;"></i></div>
      </div>`;
    }).join('');
  } catch(e) {
    list.innerHTML = '<div style="width:100%; color:#ef4444; text-align:center; padding:40px; font-weight:600;"><i class="fas fa-exclamation-circle"></i> Error loading patients.</div>';
  }
}

function selectPatient(p) {
  selectedPatientId = p.patient_id;
  const name = (p.patient_name || p.first_name || 'Walk-in Patient').trim();
  const init = name.charAt(0).toUpperCase();

  // Populate offcanvas
  document.getElementById('pt-avatar').textContent = init;
  document.getElementById('pt-name').textContent   = name;
  document.getElementById('pt-id').textContent     = p.patient_id || '—';
  document.getElementById('pt-age').textContent    = p.age ? (p.age + ' yrs') : 'Age N/A';
  document.getElementById('pt-sex').textContent    = p.sex || 'N/A';
  document.getElementById('pt-phone').textContent  = p.patient_phone || p.appointment_phone || p.phone || '—';
  document.getElementById('pt-blood').textContent  = p.blood_group || '—';

  // Show offcanvas with small delay to ensure rendering
  const oc = document.getElementById('pat-offcanvas');
  oc.style.display = 'block';
  setTimeout(() => oc.classList.add('show'), 10);
  document.body.style.overflow = 'hidden'; // Prevent background scrolling

  loadPatientHistory(p.patient_id);
}

function closeOffcanvas() {
  const oc = document.getElementById('pat-offcanvas');
  oc.classList.remove('show');
  setTimeout(() => {
    oc.style.display = 'none';
    document.body.style.overflow = '';
  }, 300); // match transition time
}

async function loadPatientHistory(patientId) {
  document.getElementById('history-loading').style.display  = 'block';
  document.getElementById('history-content').style.display  = 'none';
  document.getElementById('history-empty').style.display    = 'none';
  document.getElementById('history-count-badge').textContent = 'Loading...';

  try {
    const data = await lisApi('GET', `/api/laboratory/orders?all=1&search=${encodeURIComponent(patientId)}`);
    const orders = data.data || [];

    const filtered = orders.filter(o => o.patient_id === patientId);

    document.getElementById('history-loading').style.display = 'none';
    document.getElementById('history-count-badge').textContent = filtered.length + ' Records';

    if (!filtered.length) {
      document.getElementById('history-empty').style.display = 'block';
      return;
    }

    document.getElementById('history-content').style.display = 'block';
    const tbody = document.getElementById('history-tbody');
    tbody.innerHTML = filtered.map((o,i) => {
      const statCls = {'Ordered':'lis-badge-ordered','In Progress':'lis-badge-progress','Completed':'lis-badge-completed','Reported':'lis-badge-reported'}[o.status]||'lis-badge-ordered';
      // format date simpler
      // Clean up raw JSON arrays or ||| separators in test names
      let niceTestName = o.test_name || '';
      try {
        let arr = JSON.parse(niceTestName);
        if(Array.isArray(arr)) niceTestName = arr.join(', ');
      } catch(e) {}
      niceTestName = niceTestName.replace(/\|\|\|/g, ', ');
      
      const dateParts = o.order_date ? o.order_date.split(' ') : ['—'];

      return `<tr>
        <td style="white-space:nowrap;"><span style="font-weight:700; color:#475569;">${escHtml(o.order_id)}</span></td>
        <td style="font-weight:700; color:#1e293b; min-width:250px; white-space:normal; word-break:break-word; line-height:1.4;">${escHtml(niceTestName)}</td>
        <td style="white-space:nowrap;"><span class="lis-badge ${statCls}">${escHtml(o.status)}</span></td>
        <td style="font-size:0.75rem;color:var(--lis-text-muted); white-space:nowrap;">${escHtml(dateParts[0])}</td>
        <td style="text-align:right;">
          <a href="print_result.php?order_id=${encodeURIComponent(o.order_id)}" target="_blank"
             class="lis-btn lis-btn-outline lis-btn-sm lis-btn-icon" title="Print" style="border-radius:8px;">
            <i class="fas fa-print"></i>
          </a>
        </td>
      </tr>`;
    }).join('');

  } catch(e) {
    document.getElementById('history-loading').style.display = 'none';
    document.getElementById('history-count-badge').textContent = 'Error';
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

