<?php
$pageTitle = 'Patient Imaging History';
$pageIcon  = 'fa-user-injured';
$navTitle  = 'Patients Archive';
$navSub    = 'Search patients and view their radiological imaging history';
require_once 'includes/rad_head.php';
?>
<?php require_once 'includes/rad_sidebar.php'; ?>

<div class="lis-main-content">
<?php require_once 'includes/rad_navbar.php'; ?>

<div class="lis-content">

  <style>
    .pat-search-hero {
      background: #fff;
      border-radius: 16px;
      padding: 36px 20px;
      text-align: center;
      box-shadow: 0 10px 30px rgba(14, 116, 144, 0.05);
      margin-bottom: 24px;
      position: relative;
      overflow: hidden;
    }
    .pat-search-hero::before {
      content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
      background: linear-gradient(90deg, var(--lis-primary), #0284c7);
    }
    .pat-search-hero h2 {
      font-weight: 800; color: #1e293b; margin-bottom: 8px; font-size: 1.8rem;
    }
    .pat-search-hero p {
      color: var(--lis-text-muted); margin-bottom: 24px; font-size: 0.95rem;
    }
    .pat-search-wrap { position: relative; display: inline-block; width: 100%; max-width: 600px; }
    .pat-search-hero input {
      font-size: 1.05rem;
      padding: 14px 24px 14px 48px;
      border-radius: 50px;
      border: 2px solid #e2e8f0;
      width: 100%;
      transition: all 0.3s ease;
      background: #f8fafc;
    }
    .pat-search-hero input:focus {
      border-color: var(--lis-primary);
      background: #fff;
      box-shadow: 0 0 0 5px rgba(14, 116, 144, 0.12);
      outline: none;
    }
    .pat-search-icon {
      position: absolute; left: 18px; top: 50%; transform: translateY(-50%);
      color: #94a3b8; font-size: 1.15rem;
    }
    .pat-search-wrap:focus-within .pat-search-icon { color: var(--lis-primary); }

    .pat-result-list {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 12px;
      max-width: 850px;
      margin: 0 auto;
    }
    .pat-list-item {
      width: 100%;
      background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
      padding: 16px 20px; cursor: pointer; transition: all 0.2s;
      display: flex; align-items: center; text-align: left; gap: 18px;
    }
    .pat-list-item:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(14, 116, 144, 0.1);
      border-color: var(--lis-primary);
    }
    .pat-list-avatar {
      width: 48px; height: 48px; border-radius: 12px;
      background: linear-gradient(135deg, var(--lis-primary), #0369a1);
      color: #fff; font-size: 1.3rem; font-weight: 800;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; box-shadow: 0 4px 10px rgba(14, 116, 144, 0.2);
    }
    .pat-list-content { flex: 1; }
    .pat-list-name { font-weight: 800; font-size: 1.05rem; color: #1e293b; margin-bottom: 3px; }
    .pat-list-details { font-size: 0.82rem; color: var(--lis-text-muted); display:flex; align-items:center; gap:12px; }
    .pat-list-id { color: var(--lis-primary); font-weight: 700; background: #e0f2fe; padding: 2px 8px; border-radius: 6px; }

    /* Offcanvas */
    .pat-offcanvas {
      position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      z-index: 1050; display: none;
    }
    .pat-offcanvas.show { display: block; }
    .pat-offcanvas-overlay {
      position: absolute; top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px);
    }
    .pat-offcanvas-panel {
      position: absolute; top: 0; right: 0; width: 100%; max-width: 600px;
      height: 100%; background: #fff; box-shadow: -10px 0 30px rgba(0,0,0,0.15);
      display: flex; flex-direction: column; z-index: 1;
      transform: translateX(100%); transition: transform 0.3s ease-out;
    }
    .pat-offcanvas.show .pat-offcanvas-panel { transform: translateX(0); }
    .pat-offcanvas-header {
      padding: 20px 24px; border-bottom: 1px solid #e2e8f0;
      display: flex; align-items: center; justify-content: space-between;
      background: #f8fafc;
    }
    .pat-offcanvas-body {
      padding: 24px; overflow-y: auto; flex: 1;
    }
    .timeline-item {
      position: relative; padding-left: 28px; margin-bottom: 24px;
    }
    .timeline-item::before {
      content: ''; position: absolute; left: 8px; top: 8px; bottom: -24px;
      width: 2px; background: #e2e8f0;
    }
    .timeline-item:last-child::before { display: none; }
    .timeline-dot {
      position: absolute; left: 0; top: 3px; width: 18px; height: 18px;
      border-radius: 50%; background: #fff; border: 3px solid var(--lis-primary);
    }
  </style>

  <!-- Hero Search -->
  <div class="pat-search-hero lis-fade-up">
    <h2><i class="fas fa-search-plus" style="color:var(--lis-primary);margin-right:8px;"></i> Patient Imaging Archive</h2>
    <p>Search by Patient Name, UHID / Medical Record Number, or Phone Number</p>
    <div class="pat-search-wrap">
      <i class="fas fa-search pat-search-icon"></i>
      <input type="text" id="patSearch" placeholder="Type name, UHID (e.g. PID-...) or phone number..." autocomplete="off">
    </div>
  </div>

  <!-- Results List -->
  <div class="pat-result-list lis-fade-up-1" id="patResults">
    <div style="text-align:center;padding:40px;color:#94a3b8;">
      <i class="fas fa-hospital-user" style="font-size:3rem;margin-bottom:12px;opacity:0.4;"></i>
      <div>Search for a patient above to inspect imaging examinations, scan attachments, and radiology history.</div>
    </div>
  </div>

</div>
</div>

<!-- Patient Imaging Drawer Offcanvas -->
<div class="pat-offcanvas" id="patDrawer">
  <div class="pat-offcanvas-overlay" onclick="closePatDrawer()"></div>
  <div class="pat-offcanvas-panel">
    <div class="pat-offcanvas-header">
      <div style="display:flex;align-items:center;gap:12px;">
        <div class="pat-list-avatar" id="drw-avatar">P</div>
        <div>
          <div style="font-weight:800;font-size:1.15rem;color:#1e293b;" id="drw-name">—</div>
          <div style="font-size:0.8rem;color:#64748b;" id="drw-meta">—</div>
        </div>
      </div>
      <button type="button" class="lis-btn lis-btn-outline lis-btn-sm" onclick="closePatDrawer()" style="border-radius:50%;width:32px;height:32px;padding:0;">
        <i class="fas fa-times"></i>
      </button>
    </div>
    
    <div class="pat-offcanvas-body">
      <div style="margin-bottom:20px;display:flex;gap:10px;">
        <a href="#" id="drw-new-scan-btn" class="lis-btn lis-btn-primary" style="flex:1;justify-content:center;">
          <i class="fas fa-plus"></i> Order New Scan
        </a>
      </div>

      <h5 style="font-weight:800;font-size:0.95rem;color:#334155;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-history" style="color:var(--lis-primary);"></i> Radiological Examination History
      </h5>

      <div id="drw-history-list">
        <div style="text-align:center;padding:30px;color:#94a3b8;">
          <div class="lis-spinner" style="margin:0 auto 10px;"></div>
          Loading patient examinations...
        </div>
      </div>
    </div>
  </div>
</div>

<script>
let searchTimeout = null;
const searchInput = document.getElementById('patSearch');
const resultsContainer = document.getElementById('patResults');

searchInput.addEventListener('input', (e) => {
  clearTimeout(searchTimeout);
  const q = e.target.value.trim();
  if (q.length < 2) {
    resultsContainer.innerHTML = `
      <div style="text-align:center;padding:40px;color:#94a3b8;">
        <i class="fas fa-hospital-user" style="font-size:3rem;margin-bottom:12px;opacity:0.4;"></i>
        <div>Search for a patient above to inspect imaging examinations, scan attachments, and radiology history.</div>
      </div>
    `;
    return;
  }
  searchTimeout = setTimeout(() => executeSearch(q), 300);
});

async function executeSearch(q) {
  resultsContainer.innerHTML = '<div style="text-align:center;padding:30px;"><div class="lis-spinner" style="margin:0 auto 8px;"></div><div style="font-size:0.85rem;color:#64748b;">Searching patient records...</div></div>';
  try {
    const res = await fetch('/GM_HMS/api/radiology/patients/search?q=' + encodeURIComponent(q));
    const json = await res.json();
    if (json.success && json.data && json.data.length > 0) {
      renderPatientList(json.data);
    } else {
      resultsContainer.innerHTML = `
        <div style="text-align:center;padding:40px;background:#fff;border-radius:12px;border:1px solid #e2e8f0;width:100%;">
          <i class="fas fa-user-slash" style="font-size:2.5rem;color:#94a3b8;margin-bottom:10px;"></i>
          <div style="font-weight:700;color:#334155;">No patients found matching "${escHtml(q)}"</div>
          <div style="font-size:0.82rem;color:#64748b;margin-top:4px;">Check spelling or try searching by complete phone number.</div>
        </div>
      `;
    }
  } catch (err) {
    resultsContainer.innerHTML = '<div style="color:#ef4444;text-align:center;padding:20px;">Search request failed. Please check network.</div>';
  }
}

function renderPatientList(patients) {
  resultsContainer.innerHTML = '';
  patients.forEach(p => {
    const fullName = `${p.first_name || ''} ${p.last_name || ''}`.trim() || 'Unknown';
    const initial = fullName.charAt(0).toUpperCase();
    const item = document.createElement('div');
    item.className = 'pat-list-item';
    item.onclick = () => openPatDrawer(p);
    item.innerHTML = `
      <div class="pat-list-avatar">${initial}</div>
      <div class="pat-list-content">
        <div class="pat-list-name">${escHtml(fullName)}</div>
        <div class="pat-list-details">
          <span class="pat-list-id">UHID: ${escHtml(p.patient_id)}</span>
          <span><i class="fas fa-venus-mars"></i> ${escHtml(p.gender || '—')}, ${escHtml(p.age || '—')} yrs</span>
          <span><i class="fas fa-phone"></i> ${escHtml(p.phone || '—')}</span>
        </div>
      </div>
      <div style="color:var(--lis-primary);font-size:1.1rem;">
        <i class="fas fa-chevron-right"></i>
      </div>
    `;
    resultsContainer.appendChild(item);
  });
}

function openPatDrawer(p) {
  const drawer = document.getElementById('patDrawer');
  const fullName = `${p.first_name || ''} ${p.last_name || ''}`.trim() || 'Unknown';
  document.getElementById('drw-avatar').textContent = fullName.charAt(0).toUpperCase();
  document.getElementById('drw-name').textContent = fullName;
  document.getElementById('drw-meta').textContent = `UHID: ${p.patient_id} • ${p.gender || ''}, ${p.age || ''} yrs • Phone: ${p.phone || '—'}`;
  document.getElementById('drw-new-scan-btn').href = `test_orders.php?patient_id=${encodeURIComponent(p.patient_id)}`;
  
  loadPatientHistory(p.patient_id);
  drawer.classList.add('show');
}

function closePatDrawer() {
  document.getElementById('patDrawer').classList.remove('show');
}

async function loadPatientHistory(patientId) {
  const container = document.getElementById('drw-history-list');
  container.innerHTML = '<div style="text-align:center;padding:30px;"><div class="lis-spinner" style="margin:0 auto 10px;"></div>Loading history...</div>';
  
  try {
    const res = await fetch('/GM_HMS/api/radiology/patients/' + encodeURIComponent(patientId) + '/results');
    const json = await res.json();
    
    if (json.success && json.data && json.data.length > 0) {
      let html = '';
      json.data.forEach(item => {
        let testName = item.test_name;
        try {
          const d = JSON.parse(testName);
          if (Array.isArray(d)) testName = d.join(', ');
        } catch(e){}

        const mod = item.modality || 'RADIOLOGY';
        html += `
          <div class="timeline-item">
            <div class="timeline-dot"></div>
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;">
              <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;">
                <div>
                  <span class="lis-badge" style="background:#e0f2fe;color:#0284c7;font-size:0.7rem;padding:2px 8px;font-weight:700;">${escHtml(mod)}</span>
                  <strong style="color:#1e293b;font-size:0.95rem;margin-left:6px;">${escHtml(testName)}</strong>
                </div>
                <span style="font-size:0.75rem;color:#64748b;">${escHtml(item.result_date || '')}</span>
              </div>
              ${item.impression ? `<div style="font-size:0.82rem;color:#334155;margin-bottom:8px;background:#fff;padding:8px 10px;border-radius:6px;border-left:3px solid var(--lis-primary);"><strong>Impression:</strong> ${escHtml(item.impression)}</div>` : ''}
              <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px;">
                ${item.report_file ? `<a href="/GM_HMS/${escHtml(item.report_file)}" target="_blank" class="lis-btn lis-btn-outline lis-btn-sm"><i class="fas fa-file-image"></i> Scan</a>` : ''}
                <a href="print_result.php?order_id=${encodeURIComponent(item.order_id)}&source=${encodeURIComponent(item.patient_type || 'OPD')}" target="_blank" class="lis-btn lis-btn-outline lis-btn-sm" style="border-color:#0284c7;color:#0284c7;">
                  <i class="fas fa-print"></i> Report
                </a>
              </div>
            </div>
          </div>
        `;
      });
      container.innerHTML = html;
    } else {
      container.innerHTML = `
        <div style="text-align:center;padding:30px;color:#94a3b8;background:#f8fafc;border-radius:10px;">
          <i class="fas fa-folder-open" style="font-size:2rem;margin-bottom:8px;opacity:0.5;"></i>
          <div>No past radiology reports recorded for this patient.</div>
        </div>
      `;
    }
  } catch(e) {
    container.innerHTML = '<div style="color:#ef4444;text-align:center;padding:20px;">Failed to load patient radiology history.</div>';
  }
}
</script>

<?php require_once 'includes/rad_foot.php'; ?>
