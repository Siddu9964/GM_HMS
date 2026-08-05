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
  <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin-bottom:20px;" class="lis-fade-up-1">
    <div class="lis-kpi-card" style="cursor:pointer;" onclick="switchTab('lab')">
      <div class="lis-kpi-icon teal"><i class="fas fa-flask"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="stat-lab">—</div>
        <div class="lis-kpi-label">Lab Tests</div>
      </div>
      <i class="fas fa-flask lis-kpi-bg-icon"></i>
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

<!-- Parameter Modal (Compact Card View) -->
<div class="lis-modal-overlay" id="parameterModal">
  <div class="lis-modal" style="max-width: 95%; width: 95%; max-height: 95vh; display: flex; flex-direction: column; padding: 0;">
    <div class="lis-modal-header" style="display: flex; justify-content: space-between; align-items: center; background-color: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 15px 20px;">
      <h2 style="margin: 0; color: #1e293b; font-size: 1.25rem;"><i class="fas fa-list-ol"></i> Manage Test Parameters</h2>
      <div style="display: flex; gap: 10px;">
        <button type="button" class="lis-btn lis-btn-primary" onclick="autoFetchParameters()" style="background-color: #8b5cf6; border-color: #7c3aed;">
          <i class="fas fa-magic"></i> Auto-Generate via AI
        </button>
        <button type="button" class="lis-btn lis-btn-primary" onclick="addParamRow()">
          <i class="fas fa-plus"></i> Add Parameter
        </button>
        <span class="lis-modal-close" onclick="closeModal('parameterModal')" style="cursor:pointer; font-size: 1.5rem; color: #64748b;">&times;</span>
      </div>
    </div>
    
    <div class="lis-modal-body" style="flex: 1; overflow-y: auto; padding: 20px; background-color: #f1f5f9;">
      <!-- Main container for parameter cards -->
      <div id="param-container" style="display: flex; flex-direction: column; gap: 15px;">
        <!-- Parameter Cards will be injected here -->
      </div>
    </div>

    <div class="lis-modal-footer" style="background-color: #fff; padding: 15px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px;">
      <button type="button" class="lis-btn lis-btn-secondary" onclick="closeModal('parameterModal')">Cancel</button>
      <button type="button" class="lis-btn lis-btn-primary" onclick="saveParameters()">
        <i class="fas fa-save"></i> Save Parameters
      </button>
    </div>
  </div>
</div>

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
      document.getElementById('stat-other').textContent    = allServices.other?.length    ?? 0;

      renderLabTable(allServices.lab      || []);
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
  let paramBtn = '';
  if (type === 'lab') {
    paramBtn = `
    <button class="lis-btn lis-btn-outline lis-btn-sm lis-btn-icon" style="color:#0f172a; border-color:#cbd5e1;" title="Manage Parameters"
            onclick='openParameterModal("${escHtml(id)}")'>
      <i class="fas fa-list-ol"></i>
    </button>`;
  }
  return `<div style="display:flex;gap:6px;justify-content:center; white-space:nowrap;">
    ${paramBtn}
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

async function openParameterModal(serviceId) {
  activeParamServiceId = serviceId;
  const container = document.getElementById('param-container');
  container.innerHTML = '<div style="text-align:center;padding:20px;"><div class="lis-spinner"></div> Loading...</div>';
  
  document.getElementById('parameterModal').classList.add('open');
  
  try {
    const res = await lisApi('GET', `/api/laboratory/services/parameters/${encodeURIComponent(serviceId)}`);
    container.innerHTML = '';
    
    if (res.success && res.data && res.data.length > 0) {
      res.data.forEach(p => addParamRow(p));
    } else {
      addParamRow(); 
    }
  } catch(e) {
    container.innerHTML = '<div style="text-align:center;color:red;padding:20px;">Failed to load parameters</div>';
  }
}

function addParamRow(data = {}) {
  const div = document.createElement('div');
  div.className = 'param-card';
  div.style.cssText = 'background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); display: flex; flex-direction: column; overflow: hidden;';
  
  div.innerHTML = `
    <div style="display: flex; gap: 15px; padding: 15px; background: #fff; align-items: center; border-bottom: 1px solid #f1f5f9;">
      <div style="flex: 2; min-width: 250px;">
        <label style="font-size: 0.75rem; font-weight: 700; color: #475569; margin-bottom: 4px; display: block; text-transform: uppercase;">Parameter Name *</label>
        <div style="position: relative;">
          <i class="fas fa-flask" style="position: absolute; left: 12px; top: 10px; color: #94a3b8;"></i>
          <input type="text" class="lis-input param-name" value="${escHtml(data.parameter_name||'')}" placeholder="e.g. Hemoglobin" style="width: 100%; padding-left: 35px; border: 1px solid #cbd5e1; border-radius: 6px;" required>
        </div>
      </div>
      
      <div style="flex: 1; min-width: 100px;">
        <label style="font-size: 0.75rem; font-weight: 700; color: #475569; margin-bottom: 4px; display: block; text-transform: uppercase;">Unit</label>
        <input type="text" class="lis-input param-unit" value="${escHtml(data.unit||'')}" placeholder="e.g. mg/dL" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px;">
      </div>

      <div style="flex: 1; min-width: 150px;">
        <label style="font-size: 0.75rem; font-weight: 700; color: #475569; margin-bottom: 4px; display: block; text-transform: uppercase;">General Range</label>
        <input type="text" class="lis-input param-normal" value="${escHtml(data.normal_range||'')}" placeholder="e.g. 12-16" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; background-color: #f8fafc;">
      </div>

      <div style="display: flex; gap: 10px; align-items: flex-end; margin-top: 20px;">
        <button type="button" class="lis-btn" onclick="const el = this.closest('.param-card').querySelector('.advanced-ranges'); el.style.display = (el.style.display === 'none') ? 'block' : 'none';" style="background: #e0e7ff; color: #4338ca; border: none; border-radius: 6px; padding: 8px 12px;" title="Toggle Advanced Ranges">
          <i class="fas fa-sliders-h"></i>
        </button>
        <button type="button" class="lis-btn lis-btn-danger" onclick="this.closest('.param-card').remove()" style="border-radius: 6px; padding: 8px 12px;" title="Remove Parameter">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>
    
    <div class="advanced-ranges" style="padding: 15px; background: #fafafa; border-top: 1px dashed #cbd5e1; display: none;">
      <div style="display: flex; flex-wrap: wrap; gap: 15px;">
        
        <div style="flex: 1; min-width: 200px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px;">
          <div style="font-size: 0.7rem; font-weight: 700; color: #991b1b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
            <i class="fas fa-venus-mars"></i> Gender & Newborn
          </div>
          <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
            <div>
              <label style="font-size: 0.7rem; color: #991b1b; margin-bottom: 4px; display: block;">Male Range</label>
              <input type="text" class="lis-input param-male" value="${escHtml(data.normal_range_male||'')}" style="width: 100%; border-color:#fca5a5; background:#fff; font-size: 0.8rem; padding: 4px 6px;">
            </div>
            <div>
              <label style="font-size: 0.7rem; color: #991b1b; margin-bottom: 4px; display: block;">Female Range</label>
              <input type="text" class="lis-input param-female" value="${escHtml(data.normal_range_female||'')}" style="width: 100%; border-color:#fca5a5; background:#fff; font-size: 0.8rem; padding: 4px 6px;">
            </div>
            <div>
              <label style="font-size: 0.7rem; color: #991b1b; margin-bottom: 4px; display: block;">Child</label>
              <input type="text" class="lis-input param-child" value="${escHtml(data.normal_range_child||'')}" style="width: 100%; border-color:#fca5a5; background:#fff; font-size: 0.8rem; padding: 4px 6px;">
            </div>
            <div>
              <label style="font-size: 0.7rem; color: #991b1b; margin-bottom: 4px; display: block;">Newborn</label>
              <input type="text" class="lis-input param-newborn" value="${escHtml(data.normal_range_newborn||'')}" style="width: 100%; border-color:#fca5a5; background:#fff; font-size: 0.8rem; padding: 4px 6px;">
            </div>
          </div>
        </div>
        
        <!-- Pediatric Ranges -->
        <div style="flex: 1; min-width: 250px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px;">
          <div style="font-size: 0.7rem; font-weight: 700; color: #166534; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
            <i class="fas fa-baby-carriage"></i> Pediatric Ranges
          </div>
          <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
            <div>
              <label style="font-size: 0.7rem; color: #166534; margin-bottom: 4px; display: block;">Infant (29d–12m)</label>
              <input type="text" class="lis-input param-infant" value="${escHtml(data['normal_range_Infant(29 days 12 months)']||'')}" style="width: 100%; border-color:#86efac; background:#fff; font-size: 0.8rem; padding: 4px 6px;">
            </div>
            <div>
              <label style="font-size: 0.7rem; color: #166534; margin-bottom: 4px; display: block;">Toddler (1–3y)</label>
              <input type="text" class="lis-input param-toddler" value="${escHtml(data['normal_range_toddler(1 & 3 years)']||'')}" style="width: 100%; border-color:#86efac; background:#fff; font-size: 0.8rem; padding: 4px 6px;">
            </div>
            <div>
              <label style="font-size: 0.7rem; color: #166534; margin-bottom: 4px; display: block;">Preschool (4–5y)</label>
              <input type="text" class="lis-input param-preschool" value="${escHtml(data['normal_range_preschool_child(4 & 5 years)']||'')}" style="width: 100%; border-color:#86efac; background:#fff; font-size: 0.8rem; padding: 4px 6px;">
            </div>
            <div>
              <label style="font-size: 0.7rem; color: #166534; margin-bottom: 4px; display: block;">School (6–12y)</label>
              <input type="text" class="lis-input param-school" value="${escHtml(data['normal_range_school_child(6 & 12 years)']||'')}" style="width: 100%; border-color:#86efac; background:#fff; font-size: 0.8rem; padding: 4px 6px;">
            </div>
          </div>
        </div>

        <!-- Adult Ranges -->
        <div style="flex: 1; min-width: 250px; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 12px;">
          <div style="font-size: 0.7rem; font-weight: 700; color: #075985; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
            <i class="fas fa-user-tie"></i> Adult & Geriatric Ranges
          </div>
          <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
            <div>
              <label style="font-size: 0.7rem; color: #075985; margin-bottom: 4px; display: block;">Adolescent (13–17y)</label>
              <input type="text" class="lis-input param-adolescent" value="${escHtml(data['normal_range_adolescent(13 & 17 years)']||'')}" style="width: 100%; border-color:#7dd3fc; background:#fff; font-size: 0.8rem; padding: 4px 6px;">
            </div>
            <div>
              <label style="font-size: 0.7rem; color: #075985; margin-bottom: 4px; display: block;">Adult (18–59y)</label>
              <input type="text" class="lis-input param-adult" value="${escHtml(data['normal_range_adult(18 & 59 years)']||'')}" style="width: 100%; border-color:#7dd3fc; background:#fff; font-size: 0.8rem; padding: 4px 6px;">
            </div>
            <div>
              <label style="font-size: 0.7rem; color: #075985; margin-bottom: 4px; display: block;">Elderly (60–74y)</label>
              <input type="text" class="lis-input param-elderly" value="${escHtml(data['normal_range_elderly(60-74 years)']||'')}" style="width: 100%; border-color:#7dd3fc; background:#fff; font-size: 0.8rem; padding: 4px 6px;">
            </div>
            <div>
              <label style="font-size: 0.7rem; color: #075985; margin-bottom: 4px; display: block;">Senior (75+y)</label>
              <input type="text" class="lis-input param-senior" value="${escHtml(data['normal_range_senior_elderly(75+ years)']||'')}" style="width: 100%; border-color:#7dd3fc; background:#fff; font-size: 0.8rem; padding: 4px 6px;">
            </div>
          </div>
        </div>
      </div>
    </div>
  `;
  document.getElementById('param-container').appendChild(div);
}

async function saveParameters() {
  const serviceId = activeParamServiceId;
  if (!serviceId) return;

  const cards = document.querySelectorAll('.param-card');
  const params = [];
  let order = 1;

  cards.forEach(card => {
    const pName = card.querySelector('.param-name').value.trim();
    if (!pName) return; 
    
    params.push({
      service_id: serviceId,
      test_name: serviceId, // Usually test name, but we can pass serviceId back
      parameter_name: pName,
      display_order: order++,
      unit: card.querySelector('.param-unit').value,
      normal_range: card.querySelector('.param-normal').value,
      normal_range_male: card.querySelector('.param-male').value,
      normal_range_female: card.querySelector('.param-female').value,
      normal_range_child: card.querySelector('.param-child').value,
      normal_range_newborn: card.querySelector('.param-newborn').value,
      'normal_range_Infant(29 days 12 months)': card.querySelector('.param-infant').value,
      'normal_range_toddler(1 & 3 years)': card.querySelector('.param-toddler').value,
      'normal_range_preschool_child(4 & 5 years)': card.querySelector('.param-preschool').value,
      'normal_range_school_child(6 & 12 years)': card.querySelector('.param-school').value,
      'normal_range_adolescent(13 & 17 years)': card.querySelector('.param-adolescent').value,
      'normal_range_adult(18 & 59 years)': card.querySelector('.param-adult').value,
      'normal_range_elderly(60 & 74 years)': card.querySelector('.param-elderly').value,
      'normal_range_senior_elderly(75+ years)': card.querySelector('.param-senior').value
    });
  });

  try {
    const res = await lisApi('POST', `/api/laboratory/services/parameters/${encodeURIComponent(serviceId)}`, { parameters: params });
    if (res.success || res.message?.includes('success')) {
      lisToast('Parameters saved successfully', 'success');
      closeModal('parameterModal');
    } else {
      lisToast(res.error || 'Failed to save parameters', 'error');
    }
  } catch(e) { lisToast('Network error', 'error'); }
}

async function autoFetchParameters() {
  if (!activeParamServiceId) return;
  
  // Find the test name from allServices based on the active ID
  let testName = activeParamServiceId; 
  const allServicesFlat = [...(allServices.lab || []), ...(allServices.other || [])];
  const svc = allServicesFlat.find(s => s.service_id === activeParamServiceId);
  if (svc && (svc.test_name || svc.billing_name)) {
    testName = svc.test_name || svc.billing_name;
  }
  
  // Show loading state on button
  document.getElementById('param-container').innerHTML = '<div style="text-align:center;padding:40px;"><div class="lis-spinner"></div> Generating parameters via AI...</div>';
  
  try {
    const res = await lisApi('POST', '/api/laboratory/services/auto-generate-parameters', { test_name: testName });
    document.getElementById('param-container').innerHTML = '';
    
    if (res.success && res.data && res.data.length > 0) {
      lisToast('AI generated ' + res.data.length + ' parameters!', 'success');
      res.data.forEach(p => addParamRow(p));
    } else {
      const errMsg = res.message || res.error || 'AI could not generate parameters for this test.';
      lisToast(errMsg, 'warning');
      addParamRow();
    }
  } catch (e) {
    document.getElementById('param-container').innerHTML = '<div style="text-align:center;color:red;padding:20px;">Failed to generate parameters</div>';
    addParamRow();
  }
}

// ── Initial load ──────────────────────────────────────────────────────
loadServices().then(() => switchTab('lab'));
</script>
