<?php
$pageTitle = 'Radiology Services Catalog';
$pageIcon  = 'fa-x-ray';
$navTitle  = 'Radiology Services';
$navSub    = 'Manage imaging procedures, modalities, and multi-tier pricing';
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
        <div class="lis-page-title-icon" style="background:linear-gradient(135deg,var(--lis-primary),#0284c7);color:white;border:none;">
          <i class="fas fa-x-ray"></i>
        </div>
        <div>
          Radiology Services Catalog
          <div class="lis-page-subtitle">Standard imaging master catalog with OPD & Inpatient ward tariff tiers</div>
        </div>
      </div>
    </div>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
      <div class="lis-search-wrap">
        <i class="fas fa-search"></i>
        <input type="text" class="lis-input" id="services-search"
               placeholder="Search procedures or RDS ID..." oninput="filterServices()" style="min-width:260px;">
      </div>
      <button class="lis-btn lis-btn-outline" onclick="loadServices()">
        <i class="fas fa-sync-alt"></i> Refresh
      </button>
      <button class="lis-btn lis-btn-outline" onclick="window.print()">
        <i class="fas fa-print"></i> Print Tariff
      </button>
      <button class="lis-btn lis-btn-primary" onclick="openCreateModal()">
        <i class="fas fa-plus"></i> Add Procedure
      </button>
    </div>
  </div>

  <!-- Modality KPI Cards -->
  <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:20px;" class="lis-fade-up-1">
    <div class="lis-kpi-card" style="cursor:pointer;" onclick="switchModalityTab('ALL')">
      <div class="lis-kpi-icon teal"><i class="fas fa-th-list"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="kpi-all">391</div>
        <div class="lis-kpi-label">Total Services</div>
      </div>
      <i class="fas fa-th-list lis-kpi-bg-icon"></i>
    </div>
    <div class="lis-kpi-card" style="cursor:pointer;" onclick="switchModalityTab('X RAY')">
      <div class="lis-kpi-icon" style="background:#e0f2fe;color:#0284c7;"><i class="fas fa-x-ray"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="kpi-xray">237</div>
        <div class="lis-kpi-label">X-Ray (Digital)</div>
      </div>
      <i class="fas fa-x-ray lis-kpi-bg-icon"></i>
    </div>
    <div class="lis-kpi-card" style="cursor:pointer;" onclick="switchModalityTab('CT')">
      <div class="lis-kpi-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-circle-notch"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="kpi-ct">113</div>
        <div class="lis-kpi-label">CT Scans</div>
      </div>
      <i class="fas fa-circle-notch lis-kpi-bg-icon"></i>
    </div>
    <div class="lis-kpi-card" style="cursor:pointer;" onclick="switchModalityTab('ULTRA SOUND')">
      <div class="lis-kpi-icon" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-wave-square"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="kpi-usg">28</div>
        <div class="lis-kpi-label">Ultrasound</div>
      </div>
      <i class="fas fa-wave-square lis-kpi-bg-icon"></i>
    </div>
    <div class="lis-kpi-card" style="cursor:pointer;" onclick="switchModalityTab('DOPPLER')">
      <div class="lis-kpi-icon" style="background:#fae8ff;color:#a855f7;"><i class="fas fa-heartbeat"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="kpi-doppler">11</div>
        <div class="lis-kpi-label">Color Doppler</div>
      </div>
      <i class="fas fa-heartbeat lis-kpi-bg-icon"></i>
    </div>
  </div>

  <!-- Table Card -->
  <div class="lis-card lis-fade-up-2">
    <!-- Filter Tabs -->
    <div style="display:flex;align-items:center;gap:6px;border-bottom:2px solid var(--lis-border);padding:10px 20px;background:var(--lis-surface-2);overflow-x:auto;">
      <button class="lis-tab-pill active" data-mod="ALL" onclick="switchModalityTab('ALL', this)"><i class="fas fa-list"></i> All Modalities</button>
      <button class="lis-tab-pill" data-mod="X RAY" onclick="switchModalityTab('X RAY', this)"><i class="fas fa-x-ray"></i> X-Ray</button>
      <button class="lis-tab-pill" data-mod="CT" onclick="switchModalityTab('CT', this)"><i class="fas fa-circle-notch"></i> CT Scan</button>
      <button class="lis-tab-pill" data-mod="ULTRA SOUND" onclick="switchModalityTab('ULTRA SOUND', this)"><i class="fas fa-wave-square"></i> Ultrasound (USG)</button>
      <button class="lis-tab-pill" data-mod="DOPPLER" onclick="switchModalityTab('DOPPLER', this)"><i class="fas fa-heartbeat"></i> Doppler</button>
      <span style="margin-left:auto;font-size:0.75rem;color:var(--lis-text-muted);font-weight:700;" id="tableCountBadge">Loading...</span>
    </div>

    <div class="lis-card-body" style="padding:0;">
      <div id="svc-loading" style="display:flex;align-items:center;justify-content:center;gap:10px;padding:60px;color:var(--lis-text-muted);">
        <div class="lis-spinner"></div> Loading radiology catalog...
      </div>

      <div class="lis-table-wrap" id="svc-table-wrap" style="display:none;">
        <table class="lis-table" id="servicesTable">
          <thead>
            <tr style="background:#f8fafc;">
              <th style="width:60px;">#</th>
              <th style="width:110px;">Service ID</th>
              <th>Examination / Procedure Name</th>
              <th style="width:130px;">Modality</th>
              <th style="text-align:right;width:100px;">OPD (₹)</th>
              <th style="text-align:right;width:100px;">Gen Ward (₹)</th>
              <th style="text-align:right;width:100px;">Semi-Pvt (₹)</th>
              <th style="text-align:right;width:100px;">Pvt/ICU (₹)</th>
              <th style="text-align:right;width:100px;">Suite (₹)</th>
              <th style="text-align:center;width:110px;">Actions</th>
            </tr>
          </thead>
          <tbody id="servicesTbody"></tbody>
        </table>
        <div class="lis-empty" id="svc-empty" style="display:none;padding:50px 20px;text-align:center;">
          <i class="fas fa-x-ray" style="font-size:2.5rem;color:#94a3b8;margin-bottom:10px;"></i>
          <div class="lis-empty-title">No radiology procedures found</div>
        </div>
      </div>
    </div>
  </div>

</div>
</div>

<!-- Add / Edit Service Modal -->
<div class="modal fade" id="svcModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="border-radius:14px;border:none;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
      <div class="modal-header" style="background:#f8fafc;border-bottom:1px solid #e2e8f0;padding:16px 24px;">
        <h5 class="modal-title" id="svcModalTitle" style="font-weight:700;color:#1e293b;display:flex;align-items:center;gap:8px;">
          <i class="fas fa-x-ray" style="color:var(--lis-primary);"></i> Radiology Procedure
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="padding:24px;">
        <form id="svcForm" onsubmit="saveService(event)">
          <input type="hidden" id="svc-sl-no">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
            <div>
              <label class="lis-form-label">Service ID <span style="color:red;">*</span></label>
              <input type="text" class="lis-input" id="svc-service-id" placeholder="e.g. RDS0123" required>
            </div>
            <div>
              <label class="lis-form-label">Modality <span style="color:red;">*</span></label>
              <select class="lis-input lis-select" id="svc-modality" required>
                <option value="X RAY">X-Ray (Digital)</option>
                <option value="CT">CT Scan</option>
                <option value="ULTRA SOUND">Ultrasound (USG)</option>
                <option value="DOPPLER">Color Doppler</option>
              </select>
            </div>
          </div>
          <div style="margin-bottom:16px;">
            <label class="lis-form-label">Procedure / Billing Name <span style="color:red;">*</span></label>
            <input type="text" class="lis-input" id="svc-billing-name" placeholder="e.g., CT BRAIN PLAIN, CHEST X RAY PA VIEW" required>
          </div>

          <h6 style="font-weight:800;color:#475569;margin:20px 0 10px 0;font-size:0.85rem;text-transform:uppercase;letter-spacing:0.05em;">Multi-Tier Tariff Pricing (₹)</h6>
          <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:24px;">
            <div>
              <label class="lis-form-label">OPD Price</label>
              <input type="number" step="0.01" class="lis-input" id="svc-opd" placeholder="0.00" required>
            </div>
            <div>
              <label class="lis-form-label">Gen Ward</label>
              <input type="number" step="0.01" class="lis-input" id="svc-gw" placeholder="0.00">
            </div>
            <div>
              <label class="lis-form-label">Semi-Pvt</label>
              <input type="number" step="0.01" class="lis-input" id="svc-spvt" placeholder="0.00">
            </div>
            <div>
              <label class="lis-form-label">Pvt / ICU</label>
              <input type="number" step="0.01" class="lis-input" id="svc-icu" placeholder="0.00">
            </div>
            <div>
              <label class="lis-form-label">Suite</label>
              <input type="number" step="0.01" class="lis-input" id="svc-suite" placeholder="0.00">
            </div>
          </div>

          <div style="display:flex;justify-content:flex-end;gap:10px;">
            <button type="button" class="lis-btn lis-btn-outline" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="lis-btn lis-btn-primary" id="svc-btn-save">
              <i class="fas fa-save"></i> Save Procedure
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
let allServices = [];
let activeModality = 'ALL';
let svcBsModal = null;

document.addEventListener('DOMContentLoaded', () => {
  svcBsModal = new bootstrap.Modal(document.getElementById('svcModal'));
  loadServices();
});

async function loadServices() {
  document.getElementById('svc-loading').style.display = 'flex';
  document.getElementById('svc-table-wrap').style.display = 'none';

  try {
    let json;
    if (typeof radApi === 'function') {
      json = await radApi('GET', '/api/radiology/services');
    } else {
      const res = await fetch('/GM_HMS/api/radiology/services', {
        headers: {
          'Accept': 'application/json',
          'X-Hospital-Branch': window.HOSPITAL_BRANCH || ''
        }
      });
      json = await res.json();
    }

    const list = (json && json.data && Array.isArray(json.data.services))
      ? json.data.services
      : (json && Array.isArray(json.data) ? json.data : null);

    if (json && json.success && Array.isArray(list)) {
      allServices = list;
      updateKpis();
      renderServices();
    } else {
      radToast((json && json.message) || 'Failed to load services', 'error');
    }
  } catch(e) {
    radToast('Network error loading catalog', 'error');
  } finally {
    document.getElementById('svc-loading').style.display = 'none';
    document.getElementById('svc-table-wrap').style.display = 'block';
  }
}

function updateKpis() {
  const all = allServices.length;
  let xray = 0, ct = 0, usg = 0, doppler = 0;
  allServices.forEach(s => {
    const m = (s.modality_name || '').toUpperCase();
    if (m.includes('CT')) ct++;
    else if (m.includes('DOPPLER')) doppler++;
    else if (m.includes('ULTRA') || m.includes('USG')) usg++;
    else xray++;
  });
  document.getElementById('kpi-all').textContent = all;
  document.getElementById('kpi-xray').textContent = xray;
  document.getElementById('kpi-ct').textContent = ct;
  document.getElementById('kpi-usg').textContent = usg;
  document.getElementById('kpi-doppler').textContent = doppler;
}

function switchModalityTab(mod, btn) {
  activeModality = mod.toUpperCase();
  if (btn) {
    document.querySelectorAll('.lis-tab-pill').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
  } else {
    document.querySelectorAll('.lis-tab-pill').forEach(b => {
      b.classList.toggle('active', b.dataset.mod === activeModality);
    });
  }
  renderServices();
}

function filterServices() {
  renderServices();
}

function renderServices() {
  const query = (document.getElementById('services-search').value || '').toLowerCase().trim();
  const tbody = document.getElementById('servicesTbody');
  const empty = document.getElementById('svc-empty');
  tbody.innerHTML = '';

  const filtered = allServices.filter(s => {
    const m = (s.modality_name || '').toUpperCase();
    const matchesMod = (activeModality === 'ALL') || m.includes(activeModality);
    const text = ((s.service_id || '') + ' ' + (s.billing_name || '') + ' ' + m).toLowerCase();
    const matchesQuery = !query || text.includes(query);
    return matchesMod && matchesQuery;
  });

  document.getElementById('tableCountBadge').textContent = `${filtered.length} Procedures`;

  if (filtered.length === 0) {
    empty.style.display = 'block';
    return;
  }
  empty.style.display = 'none';

  filtered.forEach((s, idx) => {
    const tr = document.createElement('tr');
    tr.style.borderBottom = '1px solid #f1f5f9';
    const m = (s.modality_name || 'X RAY').toUpperCase();
    let modBadgeClass = 'background:#e0f2fe;color:#0284c7;';
    if (m.includes('CT')) modBadgeClass = 'background:#fef3c7;color:#d97706;';
    else if (m.includes('DOPPLER')) modBadgeClass = 'background:#fae8ff;color:#a855f7;';
    else if (m.includes('ULTRA') || m.includes('USG')) modBadgeClass = 'background:#dcfce7;color:#16a34a;';

    tr.innerHTML = `
      <td style="color:#94a3b8;font-size:0.8rem;">${idx + 1}</td>
      <td><span style="font-weight:700;color:var(--lis-primary);font-family:monospace;">${escHtml(s.service_id)}</span></td>
      <td style="font-weight:600;color:#1e293b;">${escHtml(s.billing_name)}</td>
      <td><span class="lis-badge" style="${modBadgeClass}font-size:0.7rem;padding:2px 8px;font-weight:700;">${escHtml(s.modality_name || 'X RAY')}</span></td>
      <td style="text-align:right;font-weight:700;color:#059669;">₹${parseFloat(s.opd_price || 0).toFixed(2)}</td>
      <td style="text-align:right;color:#475569;">₹${parseFloat(s.general_ward_price || 0).toFixed(2)}</td>
      <td style="text-align:right;color:#475569;">₹${parseFloat(s.semi_private_price || 0).toFixed(2)}</td>
      <td style="text-align:right;color:#475569;">₹${parseFloat(s.private_icu_price || 0).toFixed(2)}</td>
      <td style="text-align:right;color:#475569;">₹${parseFloat(s.suite_price || 0).toFixed(2)}</td>
      <td style="text-align:center;">
        <button type="button" class="lis-btn lis-btn-outline lis-btn-sm" style="padding:3px 8px;" onclick="openEditModal(${escHtml(JSON.stringify(s))})">
          <i class="fas fa-edit"></i> Edit
        </button>
      </td>
    `;
    tbody.appendChild(tr);
  });
}

function openCreateModal() {
  document.getElementById('svcModalTitle').innerHTML = '<i class="fas fa-plus" style="color:var(--lis-primary);"></i> Add Radiology Procedure';
  document.getElementById('svc-sl-no').value = '';
  document.getElementById('svc-service-id').value = '';
  document.getElementById('svc-service-id').readOnly = false;
  document.getElementById('svc-modality').value = 'X RAY';
  document.getElementById('svc-billing-name').value = '';
  document.getElementById('svc-opd').value = '';
  document.getElementById('svc-gw').value = '';
  document.getElementById('svc-spvt').value = '';
  document.getElementById('svc-icu').value = '';
  document.getElementById('svc-suite').value = '';
  svcBsModal.show();
}

function openEditModal(s) {
  document.getElementById('svcModalTitle').innerHTML = '<i class="fas fa-edit" style="color:var(--lis-primary);"></i> Edit ' + escHtml(s.service_id);
  document.getElementById('svc-sl-no').value = s.sl_no || s.service_id || '';
  document.getElementById('svc-service-id').value = s.service_id || '';
  document.getElementById('svc-service-id').readOnly = true;
  document.getElementById('svc-modality').value = s.modality_name || 'X RAY';
  document.getElementById('svc-billing-name').value = s.billing_name || '';
  document.getElementById('svc-opd').value = s.opd_price || '';
  document.getElementById('svc-gw').value = s.general_ward_price || '';
  document.getElementById('svc-spvt').value = s.semi_private_price || '';
  document.getElementById('svc-icu').value = s.private_icu_price || '';
  document.getElementById('svc-suite').value = s.suite_price || '';
  svcBsModal.show();
}

async function saveService(e) {
  e.preventDefault();
  const btn = document.getElementById('svc-btn-save');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

  const serviceId = document.getElementById('svc-service-id').value.trim();
  const isEdit = Boolean(document.getElementById('svc-sl-no').value || document.getElementById('svc-service-id').readOnly);
  const payload = {
    service_id: serviceId,
    modality_name: document.getElementById('svc-modality').value,
    billing_name: document.getElementById('svc-billing-name').value.trim(),
    opd_price: parseFloat(document.getElementById('svc-opd').value) || 0,
    general_ward_price: parseFloat(document.getElementById('svc-gw').value) || 0,
    semi_private_price: parseFloat(document.getElementById('svc-spvt').value) || 0,
    private_icu_price: parseFloat(document.getElementById('svc-icu').value) || 0,
    suite_price: parseFloat(document.getElementById('svc-suite').value) || 0
  };

  try {
    const endpoint = isEdit ? '/api/radiology/services/' + encodeURIComponent(serviceId) : '/api/radiology/services';
    let json;
    if (typeof radApi === 'function') {
      json = await radApi(isEdit ? 'PUT' : 'POST', endpoint, payload);
    } else {
      const url = '/GM_HMS' + endpoint;
      const res = await fetch(url, {
        method: isEdit ? 'PUT' : 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Hospital-Branch': window.HOSPITAL_BRANCH || ''
        },
        body: JSON.stringify(payload)
      });
      json = await res.json();
    }

    if (json && json.success) {
      radToast(isEdit ? 'Procedure updated successfully' : 'Procedure added to catalog', 'success');
      svcBsModal.hide();
      loadServices();
    } else {
      radToast((json && json.message) || 'Failed to save procedure', 'error');
    }
  } catch(err) {
    radToast('Network error saving procedure', 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-save"></i> Save Procedure';
  }
}
</script>

<?php require_once 'includes/rad_foot.php'; ?>
