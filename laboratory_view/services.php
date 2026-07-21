<?php
$pageTitle = 'Services Catalog';
$pageIcon  = 'fa-vials';
$navTitle  = 'Services Catalog';
$navSub    = 'Lab Tests • Radiology • Other Services — Full CRUD';
require_once 'includes/lab_head.php';

// Basaveshwaranagar special tests for badge highlighting
$bsnTests = ['FBS','PPBS','RFT','LFT','AMYLASE','CAT','POU','CBC','PT','APTT','ABG','SE','CRP','TROP-I','CK-MB','PSA','HBAIC','MICRO-ALBUMIN','NTPROBNP','PCT'];
?>
<?php require_once 'includes/lab_sidebar.php'; ?>

<div class="lis-main-content">
<?php require_once 'includes/lab_navbar.php'; ?>

<div class="lis-content">

  <!-- Page Header -->
  <div class="lis-page-header lis-fade-up">
    <div>
      <div class="lis-page-title">
        <div class="lis-page-title-icon"><i class="fas fa-vials"></i></div>
        <div>Services Catalog
          <div class="lis-page-subtitle">Manage lab tests, radiology and other service pricing</div>
        </div>
      </div>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
      <div class="lis-search-wrap">
        <i class="fas fa-search"></i>
        <input type="text" class="lis-input" id="services-search"
               placeholder="Search services..." oninput="filterServices()" style="min-width:220px;">
      </div>
      <button class="lis-btn lis-btn-outline" onclick="loadServices()">
        <i class="fas fa-sync-alt"></i>
      </button>
      <button class="lis-btn lis-btn-primary" onclick="openCreateModal()">
        <i class="fas fa-plus"></i> Add Service
      </button>
    </div>
  </div>

  <!-- Stats row -->
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px;" class="lis-fade-up-1">
    <div class="lis-kpi-card" style="cursor:pointer;" onclick="switchTab('lab')">
      <div class="lis-kpi-icon teal"><i class="fas fa-flask"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="stat-lab">—</div>
        <div class="lis-kpi-label">Lab Tests</div>
      </div>
      <i class="fas fa-flask lis-kpi-bg-icon"></i>
    </div>
    <div class="lis-kpi-card" style="cursor:pointer;" onclick="switchTab('radiology')">
      <div class="lis-kpi-icon violet"><i class="fas fa-x-ray"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="stat-radiology">—</div>
        <div class="lis-kpi-label">Radiology</div>
      </div>
      <i class="fas fa-x-ray lis-kpi-bg-icon"></i>
    </div>
    <div class="lis-kpi-card" style="cursor:pointer;" onclick="switchTab('other')">
      <div class="lis-kpi-icon amber"><i class="fas fa-vial"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="stat-other">—</div>
        <div class="lis-kpi-label">Other Services</div>
      </div>
      <i class="fas fa-vial lis-kpi-bg-icon"></i>
    </div>
  </div>

  <!-- Tabs + Table -->
  <div class="lis-card lis-fade-up-2">
    <!-- Tab bar -->
    <div style="display:flex;align-items:center;border-bottom:2px solid var(--lis-border);padding:0 20px;background:var(--lis-surface-2);">
      <button class="svc-tab active" data-tab="lab" onclick="switchTab('lab',this)">
        <i class="fas fa-flask"></i> Lab Tests
      </button>
      <button class="svc-tab" data-tab="radiology" onclick="switchTab('radiology',this)">
        <i class="fas fa-x-ray"></i> Radiology
      </button>
      <button class="svc-tab" data-tab="other" onclick="switchTab('other',this)">
        <i class="fas fa-vial"></i> Other Services
      </button>
      <div style="margin-left:auto;padding:8px 0;">
        <button class="lis-btn lis-btn-outline lis-btn-sm" onclick="window.print()">
          <i class="fas fa-print"></i> Price List
        </button>
      </div>
    </div>

    <div class="lis-card-body" style="padding:0;">
      <div id="svc-loading" style="display:flex;align-items:center;justify-content:center;gap:10px;padding:50px;color:var(--lis-text-muted);">
        <div class="lis-spinner"></div> Loading services...
      </div>

      <!-- Lab Tests Table -->
      <div id="tab-lab" class="tab-panel" style="display:none;">
        <div class="lis-table-wrap">
          <table class="lis-table">
            <thead><tr>
              <th>#</th>
              <th>ID</th>
              <th>Test Name</th>
              <th style="text-align:right;">OPD</th>
              <th style="text-align:right;">GW</th>
              <th style="text-align:right;">SPVT</th>
              <th style="text-align:right;">PVT/CCU</th>
              <th style="text-align:right;">Suite</th>
              <th style="text-align:center;">Actions</th>
            </tr></thead>
            <tbody id="lab-tbody"></tbody>
          </table>
          <div class="lis-empty" id="lab-empty" style="display:none;">
            <i class="fas fa-flask"></i><div class="lis-empty-title">No lab tests found</div>
          </div>
        </div>
      </div>

      <!-- Radiology Table -->
      <div id="tab-radiology" class="tab-panel" style="display:none;">
        <div class="lis-table-wrap">
          <table class="lis-table">
            <thead><tr>
              <th>#</th>
              <th>ID</th>
              <th>Name</th>
              <th>Modality</th>
              <th style="text-align:right;">OPD</th>
              <th style="text-align:right;">GW</th>
              <th style="text-align:right;">SPVT</th>
              <th style="text-align:right;">PVT/CCU</th>
              <th style="text-align:right;">Suite</th>
              <th style="text-align:center;">Actions</th>
            </tr></thead>
            <tbody id="radiology-tbody"></tbody>
          </table>
          <div class="lis-empty" id="radiology-empty" style="display:none;">
            <i class="fas fa-x-ray"></i><div class="lis-empty-title">No radiology services found</div>
          </div>
        </div>
      </div>

      <!-- Other Services Table -->
      <div id="tab-other" class="tab-panel" style="display:none;">
        <div class="lis-table-wrap">
          <table class="lis-table">
            <thead><tr>
              <th>#</th>
              <th>ID</th>
              <th>Name</th>
              <th style="text-align:right;">OP/GW</th>
              <th style="text-align:right;">SPVT</th>
              <th style="text-align:right;">PVT/CCU</th>
              <th style="text-align:right;">Suite</th>
              <th style="text-align:center;">Actions</th>
            </tr></thead>
            <tbody id="other-tbody"></tbody>
          </table>
          <div class="lis-empty" id="other-empty" style="display:none;">
            <i class="fas fa-vial"></i><div class="lis-empty-title">No other services found</div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div><!-- /.lis-content -->

<!-- ── Create Service Modal ───────────────────────────────────────────── -->
<div class="lis-modal-overlay" id="createModal">
  <div class="lis-modal" style="max-width:640px;">
    <div class="lis-modal-header">
      <div class="lis-modal-title">
        <div class="lis-modal-title-icon"><i class="fas fa-plus"></i></div>
        <div>Add New Service</div>
      </div>
      <button class="lis-modal-close" onclick="closeModal('createModal')"><i class="fas fa-times"></i></button>
    </div>
    <div class="lis-modal-body">
      <div class="lis-form-group">
        <label class="lis-label">Category</label>
        <select class="lis-input lis-select" id="create-category" onchange="renderCreateFields()">
          <option value="lab">Lab Test</option>
          <option value="radiology">Radiology</option>
          <option value="other">Other Service</option>
        </select>
      </div>
      <div id="create-fields" class="" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;"></div>
    </div>
    <div class="lis-modal-footer">
      <button class="lis-btn lis-btn-outline" onclick="closeModal('createModal')">Cancel</button>
      <button class="lis-btn lis-btn-primary" onclick="submitCreate()">
        <i class="fas fa-save"></i> Add Service
      </button>
    </div>
  </div>
</div>

<!-- ── Edit Service Modal ─────────────────────────────────────────────── -->
<div class="lis-modal-overlay" id="editModal">
  <div class="lis-modal" style="max-width:640px;">
    <div class="lis-modal-header">
      <div class="lis-modal-title">
        <div class="lis-modal-title-icon" style="background:linear-gradient(135deg,#f59e0b,#fbbf24);"><i class="fas fa-edit"></i></div>
        <div>Edit Service</div>
      </div>
      <button class="lis-modal-close" onclick="closeModal('editModal')"><i class="fas fa-times"></i></button>
    </div>
    <div class="lis-modal-body">
      <input type="hidden" id="edit-type">
      <input type="hidden" id="edit-id">
      <div id="edit-fields" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;"></div>
    </div>
    <div class="lis-modal-footer">
      <button class="lis-btn lis-btn-outline" onclick="closeModal('editModal')">Cancel</button>
      <button class="lis-btn lis-btn-primary" onclick="submitEdit()">
        <i class="fas fa-save"></i> Save Changes
      </button>
    </div>
  </div>
</div>

<?php require_once 'includes/lab_foot.php'; ?>

<style>
.svc-tab {
  padding: 14px 20px;
  border: none;
  background: none;
  font-family: 'Inter', sans-serif;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--lis-text-muted);
  cursor: pointer;
  border-bottom: 2.5px solid transparent;
  margin-bottom: -2px;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 7px;
}
.svc-tab:hover { color: var(--lis-primary); }
.svc-tab.active {
  color: var(--lis-primary);
  border-bottom-color: var(--lis-primary);
  font-weight: 800;
}
</style>

<script>
const BSN_TESTS = <?= json_encode($bsnTests) ?>;
let allServices = { lab: [], radiology: [], other: [] };
let currentTab  = 'lab';

// ── Load services ──────────────────────────────────────────────────────
async function loadServices() {
  document.getElementById('svc-loading').style.display = 'flex';
  document.querySelectorAll('.tab-panel').forEach(p => p.style.display = 'none');

  try {
    const data = await lisApi('GET', '/api/laboratory/services');
    document.getElementById('svc-loading').style.display = 'none';

    if (data.success) {
      allServices = data.data || { lab:[], radiology:[], other:[] };
      document.getElementById('stat-lab').textContent      = allServices.lab?.length      ?? 0;
      document.getElementById('stat-radiology').textContent = allServices.radiology?.length ?? 0;
      document.getElementById('stat-other').textContent    = allServices.other?.length    ?? 0;

      renderLabTable(allServices.lab      || []);
      renderRadiologyTable(allServices.radiology || []);
      renderOtherTable(allServices.other    || []);

      switchTab(currentTab);
    }
  } catch(e) {
    document.getElementById('svc-loading').style.display = 'none';
    lisToast('Failed to load services', 'error');
  }
}

function fmt(v) { return v !== null && v !== undefined ? '₹' + parseFloat(v).toFixed(2) : '—'; }

function isBSN(name) {
  const upper = (name||'').toUpperCase().trim();
  return BSN_TESTS.some(t => upper === t || upper.includes(t));
}

function bsnBadge(name) {
  return isBSN(name) ? `<span class="lis-badge lis-badge-branch" style="margin-left:5px;" title="Basaveshwaranagar Branch">BSN</span>` : '';
}

function renderLabTable(rows) {
  const tbody = document.getElementById('lab-tbody');
  const empty = document.getElementById('lab-empty');
  if (!rows.length) { tbody.innerHTML=''; empty.style.display='block'; return; }
  empty.style.display='none';
  tbody.innerHTML = rows.map((r,i) => `<tr class="svc-row" data-name="${escHtml(r.test_name||'').toLowerCase()}">
    <td style="color:var(--lis-text-muted);font-weight:700;">${i+1}</td>
    <td><code style="font-size:0.68rem;background:#f1f5f9;padding:2px 6px;border-radius:5px;">${escHtml(r.service_id)}</code></td>
    <td style="font-weight:700;">${escHtml(r.test_name)}${bsnBadge(r.test_name)}</td>
    <td style="text-align:right;font-weight:600;">${fmt(r.opd_rate)}</td>
    <td style="text-align:right;font-weight:600;">${fmt(r.gw_rate)}</td>
    <td style="text-align:right;font-weight:600;">${fmt(r.spvt_rate)}</td>
    <td style="text-align:right;font-weight:600;">${fmt(r.pvt_ccu_rate)}</td>
    <td style="text-align:right;font-weight:600;">${fmt(r.suite_rate)}</td>
    <td style="text-align:center;">${actionBtns('lab', r.service_id, JSON.stringify(r))}</td>
  </tr>`).join('');
}

function renderRadiologyTable(rows) {
  const tbody = document.getElementById('radiology-tbody');
  const empty = document.getElementById('radiology-empty');
  if (!rows.length) { tbody.innerHTML=''; empty.style.display='block'; return; }
  empty.style.display='none';
  tbody.innerHTML = rows.map((r,i) => `<tr class="svc-row" data-name="${escHtml(r.billing_name||'').toLowerCase()}">
    <td style="color:var(--lis-text-muted);font-weight:700;">${i+1}</td>
    <td><code style="font-size:0.68rem;background:#f1f5f9;padding:2px 6px;border-radius:5px;">${escHtml(r.service_id)}</code></td>
    <td style="font-weight:700;">${escHtml(r.billing_name)}</td>
    <td><span class="lis-badge lis-badge-radiology">${escHtml(r.modality_name||'—')}</span></td>
    <td style="text-align:right;font-weight:600;">${fmt(r.opd_price)}</td>
    <td style="text-align:right;font-weight:600;">${fmt(r.general_ward_price)}</td>
    <td style="text-align:right;font-weight:600;">${fmt(r.semi_private_price)}</td>
    <td style="text-align:right;font-weight:600;">${fmt(r.private_icu_price)}</td>
    <td style="text-align:right;font-weight:600;">${fmt(r.suite_price)}</td>
    <td style="text-align:center;">${actionBtns('radiology', r.service_id, JSON.stringify(r))}</td>
  </tr>`).join('');
}

function renderOtherTable(rows) {
  const tbody = document.getElementById('other-tbody');
  const empty = document.getElementById('other-empty');
  if (!rows.length) { tbody.innerHTML=''; empty.style.display='block'; return; }
  empty.style.display='none';
  tbody.innerHTML = rows.map((r,i) => `<tr class="svc-row" data-name="${escHtml(r.billing_name||'').toLowerCase()}">
    <td style="color:var(--lis-text-muted);font-weight:700;">${i+1}</td>
    <td><code style="font-size:0.68rem;background:#f1f5f9;padding:2px 6px;border-radius:5px;">${escHtml(r.service_id)}</code></td>
    <td style="font-weight:700;">${escHtml(r.billing_name)}</td>
    <td style="text-align:right;font-weight:600;">${fmt(r.op_gw_price)}</td>
    <td style="text-align:right;font-weight:600;">${fmt(r.semi_private_price)}</td>
    <td style="text-align:right;font-weight:600;">${fmt(r.private_icu_price)}</td>
    <td style="text-align:right;font-weight:600;">${fmt(r.suite_price)}</td>
    <td style="text-align:center;">${actionBtns('other', r.service_id, JSON.stringify(r))}</td>
  </tr>`).join('');
}

function actionBtns(type, id, dataStr) {
  const safe = escHtml(dataStr).replace(/'/g,'&#39;');
  return `<div style="display:flex;gap:6px;justify-content:center;">
    <button class="lis-btn lis-btn-outline lis-btn-sm lis-btn-icon" title="Edit"
            onclick='openEditModal("${type}","${escHtml(id)}",${dataStr})'>
      <i class="fas fa-edit"></i>
    </button>
    <button class="lis-btn lis-btn-sm lis-btn-icon" title="Delete"
            style="background:#fef2f2;border:1px solid #fecaca;color:#ef4444;"
            onclick="deleteService('${type}','${escHtml(id)}')">
      <i class="fas fa-trash-alt"></i>
    </button>
  </div>`;
}

function switchTab(tab, el) {
  currentTab = tab;
  document.querySelectorAll('.svc-tab').forEach(t => t.classList.remove('active'));
  if (el) el.classList.add('active');
  else {
    const btn = document.querySelector(`[data-tab="${tab}"]`);
    if (btn) btn.classList.add('active');
  }
  document.querySelectorAll('.tab-panel').forEach(p => p.style.display = 'none');
  const panel = document.getElementById(`tab-${tab}`);
  if (panel) panel.style.display = 'block';
  filterServices();
}

function filterServices() {
  const q = (document.getElementById('services-search')?.value || '').toLowerCase();
  document.querySelectorAll('.svc-row').forEach(row => {
    row.style.display = row.dataset.name?.includes(q) ? '' : 'none';
  });
}

// ── Create Modal ──────────────────────────────────────────────────────
function openCreateModal() {
  renderCreateFields();
  document.getElementById('createModal').classList.add('open');
}

function renderCreateFields() {
  const cat = document.getElementById('create-category').value;
  const container = document.getElementById('create-fields');
  container.innerHTML = buildFields(cat, {});
}

function buildFields(cat, vals) {
  const fld = (label, name, val='', type='text', full=false) =>
    `<div style="${full?'grid-column:1/-1;':''}">
      <label class="lis-label">${label}</label>
      <input type="${type}" name="${name}" value="${escHtml(String(val))}" class="lis-input" step="0.01">
    </div>`;

  if (cat === 'lab') return [
    fld('Service ID', 'service_id', vals.service_id||''),
    fld('Test Name', 'test_name', vals.test_name||''),
    fld('OPD Rate (₹)', 'opd_rate', vals.opd_rate||0, 'number'),
    fld('GW Rate (₹)', 'gw_rate', vals.gw_rate||0, 'number'),
    fld('SPVT Rate (₹)', 'spvt_rate', vals.spvt_rate||0, 'number'),
    fld('PVT/CCU Rate (₹)', 'pvt_ccu_rate', vals.pvt_ccu_rate||0, 'number'),
    fld('Suite Rate (₹)', 'suite_rate', vals.suite_rate||0, 'number'),
  ].join('');

  if (cat === 'radiology') return [
    fld('Service ID', 'service_id', vals.service_id||''),
    fld('Billing Name', 'billing_name', vals.billing_name||''),
    fld('Modality', 'modality_name', vals.modality_name||''),
    fld('OPD Price (₹)', 'opd_price', vals.opd_price||0, 'number'),
    fld('General Ward (₹)', 'general_ward_price', vals.general_ward_price||0, 'number'),
    fld('Semi Private (₹)', 'semi_private_price', vals.semi_private_price||0, 'number'),
    fld('Private/ICU (₹)', 'private_icu_price', vals.private_icu_price||0, 'number'),
    fld('Suite (₹)', 'suite_price', vals.suite_price||0, 'number'),
  ].join('');

  // other
  return [
    fld('Service ID', 'service_id', vals.service_id||''),
    fld('Billing Name', 'billing_name', vals.billing_name||''),
    fld('OP/GW Price (₹)', 'op_gw_price', vals.op_gw_price||0, 'number'),
    fld('Semi Private (₹)', 'semi_private_price', vals.semi_private_price||0, 'number'),
    fld('Private/ICU (₹)', 'private_icu_price', vals.private_icu_price||0, 'number'),
    fld('Suite (₹)', 'suite_price', vals.suite_price||0, 'number'),
  ].join('');
}

function collectFields(containerId) {
  const data = {};
  document.querySelectorAll(`#${containerId} input`).forEach(inp => {
    data[inp.name] = inp.type === 'number' ? parseFloat(inp.value)||0 : inp.value;
  });
  return data;
}

async function submitCreate() {
  const category = document.getElementById('create-category').value;
  const body     = collectFields('create-fields');
  body.category  = category;

  try {
    const res = await lisApi('POST', '/api/laboratory/services', body);
    if (res.success || res.message?.includes('success')) {
      lisToast('Service added successfully', 'success');
      closeModal('createModal');
      loadServices();
    } else {
      lisToast(res.error || res.message || 'Failed to add service', 'error');
    }
  } catch(e) { lisToast('Network error', 'error'); }
}

function openEditModal(type, id, data) {
  document.getElementById('edit-type').value = type;
  document.getElementById('edit-id').value   = id;
  document.getElementById('edit-fields').innerHTML = buildFields(type, data);
  document.getElementById('editModal').classList.add('open');
}

async function submitEdit() {
  const type = document.getElementById('edit-type').value;
  const id   = document.getElementById('edit-id').value;
  const body = collectFields('edit-fields');

  try {
    const res = await lisApi('PUT', `/api/laboratory/services/${type}/${encodeURIComponent(id)}`, body);
    if (res.success || res.message?.includes('success')) {
      lisToast('Service updated successfully', 'success');
      closeModal('editModal');
      loadServices();
    } else {
      lisToast(res.error || res.message || 'Failed to update', 'error');
    }
  } catch(e) { lisToast('Network error', 'error'); }
}

async function deleteService(type, id) {
  lisConfirm(`Delete this ${type} service?`, async () => {
    try {
      const res = await lisApi('DELETE', `/api/laboratory/services/${type}/${encodeURIComponent(id)}`);
      if (res.success) {
        lisToast('Service deleted', 'success');
        loadServices();
      } else {
        lisToast(res.error || 'Failed to delete', 'error');
      }
    } catch(e) { lisToast('Network error', 'error'); }
  });
}

function closeModal(id) { document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('.lis-modal-overlay').forEach(o => {
  o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
});

function escHtml(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Initial load ──────────────────────────────────────────────────────
loadServices().then(() => switchTab('lab'));
</script>
