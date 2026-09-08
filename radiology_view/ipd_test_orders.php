<?php
$pageTitle = 'IPD Radiology Orders';
$pageIcon  = 'fa-procedures';
$navTitle  = 'Inpatient Scans';
$navSub    = 'Inpatient imaging requisitions, bedside scans, and ward reports';
require_once 'includes/rad_head.php';
?>
<?php require_once 'includes/rad_sidebar.php'; ?>

<style>
:root {
  --p: #1F6B4A; --p-dk: #154c34; --p-lt: #2a8f62;
  --s: #F3EFE6; --s-dk: #e8e0d0; --s-lt: #f9f7f2;
  --txt: #0f2419; --txt-mut: #4a7560; --bdr: rgba(31,107,74,.12);
  --r-md: 12px; --r-lg: 16px; --r-xl: 20px;
}
.ro-page{padding:14px;font-family:'Inter',sans-serif;background:var(--s);min-height:100%}
.ro-header{
  background:linear-gradient(135deg, #154c34 0%, #0369a1 100%);
  border-radius:var(--r-xl);margin-bottom:20px;
  display:flex;justify-content:space-between;align-items:center;
  box-shadow:0 10px 30px rgba(0,0,0,0.12);color:white;
}
.lb{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:var(--r-md);font-size:.82rem;font-weight:700;cursor:pointer;border:none;transition:all .2s;text-decoration:none;}
.lb-ghost{background:rgba(255,255,255,.14);color:white;border:1.5px solid rgba(255,255,255,.25)}
.lb-cream{background:var(--s);color:var(--p);font-weight:800;}
.lb-primary{background:var(--p);color:white;}
.lb-outline{background:white;color:var(--p);border:1.5px solid var(--bdr);}
.ro-filters{background:white;border-radius:var(--r-lg);border:1.5px solid var(--bdr);box-shadow:0 2px 8px rgba(0,0,0,0.04);margin-bottom:20px;overflow:hidden}
.ro-filter-top{padding:16px 22px;display:grid;grid-template-columns:auto auto auto 1fr auto;gap:12px;align-items:flex-end;background:var(--s-lt);border-bottom:1px solid var(--bdr)}
.ro-flabel{font-size:.65rem;font-weight:800;text-transform:uppercase;color:var(--txt-mut);margin-bottom:4px;display:block;}
.ro-finput{padding:9px 13px;border:1.5px solid var(--bdr);border-radius:var(--r-md);font-size:.82rem;color:var(--txt);background:white;outline:none;font-family:'Inter',sans-serif;}
.ro-table-card{background:var(--s);border-radius:var(--r-lg);border:1.5px solid var(--bdr);box-shadow:0 6px 20px rgba(0,0,0,0.06);overflow:hidden}
.ro-table{width:100%;border-collapse:collapse;font-size:.82rem}
.ro-table thead tr{background:var(--s-dk)}
.ro-table thead th{padding:12px 14px;font-size:.65rem;font-weight:800;text-transform:uppercase;color:var(--txt-mut);border-bottom:2px solid rgba(31,107,74,.2);text-align:left;}
.ro-table tbody tr{border-bottom:1px solid var(--bdr);background:var(--s);transition:background .15s;}
.ro-table tbody tr:hover{background:white}
.ro-table tbody td{padding:13px 14px;vertical-align:middle}
</style>

<div class="lis-main-content">
<?php require_once 'includes/rad_navbar.php'; ?>

<div class="lis-content">
<div class="ro-page">

  <!-- Header -->
  <div class="ro-header" style="flex-direction:column; align-items:stretch; padding:0;">
    <div style="display:flex; justify-content:space-between; align-items:center; padding:24px 32px;">
      <div style="display:flex; align-items:center; gap:16px;">
        <div style="width:52px;height:52px;background:rgba(255,255,255,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:white;">
          <i class="fas fa-procedures"></i>
        </div>
        <div>
          <h1 style="margin:0;font-size:1.55rem;font-weight:900;line-height:1.1;">Inpatient (IPD) Radiology</h1>
          <p style="margin:5px 0 0;font-size:.8rem;opacity:0.85;">Manage ward imaging requests, bedside radiographs, and inpatient PACS reporting</p>
        </div>
      </div>
      <div style="display:flex;gap:10px;">
        <button class="lb lb-ghost" onclick="loadIpdOrders()"><i class="fas fa-sync-alt"></i> Refresh</button>
      </div>
    </div>
  </div>

  <!-- Filters -->
  <div class="ro-filters">
    <div class="ro-filter-top">
      <div>
        <label class="ro-flabel">Date</label>
        <input type="date" class="ro-finput" id="filter-date" value="<?= date('Y-m-d') ?>" onchange="loadIpdOrders()">
      </div>
      <div>
        <label class="ro-flabel">Status</label>
        <select class="ro-finput" id="filter-status" onchange="loadIpdOrders()">
          <option value="all">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="completed">Completed / Reported</option>
        </select>
      </div>
      <div>
        <label class="ro-flabel">Modality</label>
        <select class="ro-finput" id="filter-modality" onchange="loadIpdOrders()">
          <option value="">All Modalities</option>
          <option value="X RAY">X-Ray</option>
          <option value="CT">CT Scan</option>
          <option value="ULTRA SOUND">Ultrasound</option>
          <option value="DOPPLER">Doppler</option>
        </select>
      </div>
      <div>
        <label class="ro-flabel">Search</label>
        <input type="text" class="ro-finput" id="filter-search" placeholder="Search patient, ward, admission ID, scan..." style="width:100%;" oninput="loadIpdOrders()">
      </div>
      <div>
        <label class="ro-flabel" style="opacity:0;">All</label>
        <button class="lb lb-outline" onclick="toggleAllDate()"><i class="fas fa-calendar"></i> Toggle All</button>
      </div>
    </div>
  </div>

  <!-- Orders Table -->
  <div class="ro-table-card">
    <div style="padding:16px 22px;display:flex;justify-content:space-between;align-items:center;border-bottom:2px solid var(--bdr);">
      <div style="font-size:1rem;font-weight:900;color:var(--txt);display:flex;align-items:center;gap:10px;">
        <i class="fas fa-bed" style="color:var(--p);"></i> Inpatient Imaging Queue
        <span class="lis-badge" id="ipd-count-badge" style="background:#0284c7;color:white;">0</span>
      </div>
    </div>

    <div style="overflow-x:auto;">
      <table class="ro-table">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Patient Details</th>
            <th>Ward & Bed Location</th>
            <th>Requested Scan</th>
            <th>Modality</th>
            <th>Admitting Doctor</th>
            <th>Date</th>
            <th style="text-align:center;">Status</th>
            <th style="text-align:center;">Actions</th>
          </tr>
        </thead>
        <tbody id="ipd-tbody">
          <tr><td colspan="9" style="text-align:center;padding:40px;color:#64748b;">Loading inpatient imaging orders...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div>
</div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     IPD REPORTING MODAL
     ══════════════════════════════════════════════════════════════ -->
<div class="lis-modal-overlay" id="ipdResultModal" style="display:none;">
  <div class="lis-modal" style="max-width:1000px;width:96%;">
    <div class="lis-modal-header" style="background:linear-gradient(135deg,#154c34,#0284c7);">
      <div class="lis-modal-title">
        <div class="lis-modal-title-icon"><i class="fas fa-procedures"></i></div>
        <div>
          Inpatient Radiology Report
          <div style="font-size:.7rem;opacity:0.85;">Ward scan reporting and findings verification</div>
        </div>
      </div>
      <button type="button" class="lis-modal-close" onclick="closeIpdResultModal()" title="Close (Esc)"><i class="fas fa-times"></i></button>
    </div>

    <div class="lis-modal-body" style="max-height:78vh;overflow-y:auto;padding:20px;">
      <div style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;padding:12px 16px;display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div>
          <span style="font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;">Order ID:</span>
          <strong id="ipd-res-order-id" style="font-family:monospace;color:#0284c7;font-size:1rem;margin-left:6px;"></strong>
          <div style="font-size:.82rem;color:#334155;margin-top:2px;" id="ipd-res-meta"></div>
        </div>
        <button type="button" class="lb lb-outline lb-sm" onclick="autoFillIpdTemplate()" style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;">
          <i class="fas fa-magic"></i> Auto-Fill Normal Template
        </button>
      </div>

      <div style="display:grid;grid-template-columns:280px 1fr;gap:20px;">
        <div style="background:white;border:1.5px solid var(--bdr);border-radius:12px;padding:14px;">
          <label style="display:flex;align-items:center;gap:10px;padding:12px;background:#f8fafc;border:1.5px dashed #0284c7;border-radius:10px;cursor:pointer;">
            <i class="fas fa-cloud-upload-alt" style="font-size:1.4rem;color:#0284c7;"></i>
            <div>
              <div style="font-size:.82rem;font-weight:700;">Attach Ward Scan</div>
              <div style="font-size:.68rem;color:#64748b;">JPG, PNG, PDF</div>
            </div>
            <input type="file" id="ipd-report-file" accept="image/*,application/pdf" style="display:none;" onchange="handleIpdFile(this)">
          </label>
          <div id="ipd-file-preview-card" style="display:none;background:#f1f5f9;border-radius:8px;padding:10px;margin-top:10px;border:1px solid #cbd5e1;position:relative;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
              <div id="ipd-file-preview" style="font-size:.75rem;color:#475569;word-break:break-all;"></div>
              <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeIpdAttachedFile()" title="Close / Remove Attachment" style="padding:2px 7px;font-size:0.75rem;border-radius:6px;line-height:1;flex-shrink:0;">
                <i class="fas fa-times"></i>
              </button>
            </div>
            <img id="ipd-file-preview-img" style="max-width:100%;border-radius:6px;margin-top:8px;display:none;">
          </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:14px;">
          <div>
            <label class="ro-flabel">Examination Technique</label>
            <input type="text" class="ro-finput" id="ipd-rep-tech" style="width:100%;" placeholder="e.g. Bedside portable chest radiograph AP projection">
          </div>
          <div>
            <label class="ro-flabel">Clinical History / Indication</label>
            <input type="text" class="ro-finput" id="ipd-rep-history" style="width:100%;" placeholder="e.g. Inpatient trauma, pain in bilateral hands, rule out fracture">
          </div>
          <div>
            <label class="ro-flabel">Detailed Findings *</label>
            <textarea class="ro-finput" id="ipd-rep-find" rows="6" style="width:100%;font-family:monospace;font-size:.82rem;line-height:1.4;" placeholder="Detailed radiological findings..."></textarea>
          </div>
          <div>
            <label class="ro-flabel">Impression / Conclusion *</label>
            <textarea class="ro-finput" id="ipd-rep-imp" rows="3" style="width:100%;font-weight:700;color:#0f172a;" placeholder="Conclusion summary..."></textarea>
          </div>
        </div>
      </div>
    </div>

    <div class="lis-modal-footer">
      <button class="lb lb-outline" onclick="closeIpdResultModal()">Cancel</button>
      <button class="lb lb-primary" id="btnSaveIpdResult" onclick="submitIpdResult()"><i class="fas fa-check-circle"></i> Save Inpatient Report</button>
    </div>
  </div>
</div>

<?php require_once 'includes/rad_foot.php'; ?>

<script>
let ipdOrders = [];
let allMode = '0';
let activeIpdOrderId = null;
let activeIpdPatientId = null;

async function loadIpdOrders() {
  const date = document.getElementById('filter-date').value;
  const status = document.getElementById('filter-status').value;
  const modality = document.getElementById('filter-modality').value;
  const search = document.getElementById('filter-search').value;

  try {
    const res = await radApi('GET', `/api/radiology/ipd-orders?all=${allMode}&date=${date}&status=${status}&modality=${encodeURIComponent(modality)}&search=${encodeURIComponent(search)}`);
    if (res.success) {
      ipdOrders = res.data || [];
      renderIpdOrders(ipdOrders);
    }
  } catch(e) {
    radToast('Error loading inpatient orders', 'error');
  }
}

function toggleAllDate() {
  allMode = (allMode === '0') ? '1' : '0';
  loadIpdOrders();
}

function renderIpdOrders(orders) {
  const tbody = document.getElementById('ipd-tbody');
  document.getElementById('ipd-count-badge').textContent = orders.length;

  if (!orders.length) {
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:40px;color:#64748b;">No inpatient imaging orders found.</td></tr>';
    return;
  }

  tbody.innerHTML = orders.map(o => {
    const modalitiesBadge = (o.modalities || []).map(m => `<span class="badge bg-secondary" style="font-size:0.65rem;margin-right:2px;">${escHtml(m)}</span>`).join('');
    const bedInfo = [o.ward_name, o.room_no ? `Room ${o.room_no}` : '', o.bed_id ? `Bed ${o.bed_id}` : ''].filter(Boolean).join(' • ');

    return `
      <tr>
        <td><strong style="color:#0284c7;font-family:monospace;">${escHtml(o.order_id)}</strong></td>
        <td>
          <div style="font-weight:700;color:#1e293b;">${escHtml(o.patient_name)}</div>
          <div style="font-size:.7rem;color:#64748b;">PID: ${escHtml(o.patient_id)} • IP#: ${escHtml(o.admission_id||'')}</div>
        </td>
        <td><span class="badge bg-light text-dark" style="border:1px solid #cbd5e1;"><i class="fas fa-bed"></i> ${escHtml(bedInfo || 'Ward')}</span></td>
        <td>${formatProcedureListHtml(o.resolved_test_names || o.test_name)}</td>
        <td>${modalitiesBadge || '<span class="badge bg-light text-dark">Scan</span>'}</td>
        <td>Dr. ${escHtml(o.doctor_name || 'Consultant')}</td>
        <td style="font-size:.75rem;color:#64748b;">${escHtml(o.order_date ? o.order_date.slice(0,10) : '')}</td>
        <td style="text-align:center;">${radStatusBadge(o.status)}</td>
        <td style="text-align:center;white-space:nowrap;">
          <button class="lb lb-primary" style="padding:4px 10px;font-size:0.75rem;" onclick="openIpdResultModal('${escHtml(o.order_id)}', '${escHtml(o.patient_id)}', '${escHtml(o.resolved_test_names||o.test_name)}', '${escHtml(bedInfo)}')">
            <i class="fas fa-file-medical"></i> Report
          </button>
          <a href="print_result.php?order_id=${encodeURIComponent(o.order_id)}&source=IPD&from=ipd_orders" target="_blank" onclick="window.open(this.href, '_blank'); return false;" class="lb lb-outline" style="padding:4px 10px;font-size:0.75rem;" title="Print Inpatient Report">
            <i class="fas fa-print"></i>
          </a>
        </td>
      </tr>
    `;
  }).join('');
}

async function openIpdResultModal(orderId, patientId, testName, bedInfo) {
  activeIpdOrderId = orderId;
  activeIpdPatientId = patientId;

  document.getElementById('ipd-res-order-id').textContent = orderId;
  document.getElementById('ipd-res-meta').textContent = `${testName} • ${bedInfo}`;
  document.getElementById('ipd-rep-tech').value = '';
  document.getElementById('ipd-rep-history').value = '';
  document.getElementById('ipd-rep-find').value = '';
  document.getElementById('ipd-rep-imp').value = '';
  document.getElementById('ipd-file-preview').innerHTML = '';
  document.getElementById('ipd-file-preview-img').style.display = 'none';
  document.getElementById('ipd-file-preview-img').src = '';
  document.getElementById('ipd-file-preview-card').style.display = 'none';
  document.getElementById('ipd-report-file').value = '';

  const im = document.getElementById('ipdResultModal');
  if (im) {
    im.style.display = 'flex';
    void im.offsetWidth;
    im.classList.add('open');
  }

  // Load existing inpatient result if already reported
  try {
    const rData = await radApi('GET', `/api/radiology/ipd-orders/${encodeURIComponent(orderId)}/result`);
    if (rData.success && rData.data) {
      const d = rData.data;
      let parsed = {};
      try { parsed = typeof d.result_data === 'string' ? JSON.parse(d.result_data) : (d.result_data || {}); } catch(e) {}
      
      document.getElementById('ipd-rep-tech').value = d.technique || parsed.technique || '';
      document.getElementById('ipd-rep-history').value = d.clinical_history || parsed.clinical_history || '';
      document.getElementById('ipd-rep-find').value = d.findings || parsed.findings || (typeof d.result_data === 'string' ? d.result_data : '');
      document.getElementById('ipd-rep-imp').value = d.impression || parsed.impression || '';
      
      if (d.report_file) {
        document.getElementById('ipd-file-preview-card').style.display = 'block';
        const fName = d.report_file.split('/').pop();
        document.getElementById('ipd-file-preview').innerHTML = `<a href="/GM_HMS/${escHtml(d.report_file)}" target="_blank" style="color:#0284c7;font-weight:700;text-decoration:underline;"><i class="fas fa-file-medical"></i> ${escHtml(fName)}</a>`;
        if (d.report_file.match(/\.(jpeg|jpg|png|gif|webp)$/i)) {
          const img = document.getElementById('ipd-file-preview-img');
          img.src = '/GM_HMS/' + d.report_file;
          img.style.display = 'block';
        }
      }
    }
  } catch(e) {}
}

function closeIpdResultModal() {
  if (typeof Swal !== 'undefined' && Swal.isVisible()) {
    Swal.close();
  }
  const im = document.getElementById('ipdResultModal');
  if (im) {
    im.classList.remove('open');
    im.style.display = 'none';
  }
  document.body.classList.remove('modal-open');
  document.body.style.removeProperty('overflow');
  document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
}

function removeIpdAttachedFile() {
  const fi = document.getElementById('ipd-report-file');
  if (fi) fi.value = '';
  const card = document.getElementById('ipd-file-preview-card');
  if (card) card.style.display = 'none';
  const lbl = document.getElementById('ipd-file-preview');
  if (lbl) lbl.innerHTML = '';
  const img = document.getElementById('ipd-file-preview-img');
  if (img) {
    img.style.display = 'none';
    img.src = '';
  }
}

async function autoFillIpdTemplate() {
  const metaText = document.getElementById('ipd-res-meta').textContent || '';
  const examName = (metaText.split(' • ')[0] || '').trim();
  try {
    const res = await radApi('POST', '/api/radiology/templates/auto-generate', { exam_name: examName });
    if (res.success && res.data) {
      const t = res.data;
      if (t.technique) document.getElementById('ipd-rep-tech').value = t.technique;
      if (t.clinical_history) document.getElementById('ipd-rep-history').value = t.clinical_history;
      if (t.findings) document.getElementById('ipd-rep-find').value = t.findings;
      if (t.impression) document.getElementById('ipd-rep-imp').value = t.impression;
      radToast('Normal template loaded', 'success');
    }
  } catch(e) {
    radToast('Error loading template', 'error');
  }
}

function handleIpdFile(input) {
  if (input.files && input.files[0]) {
    const file = input.files[0];
    document.getElementById('ipd-file-preview').textContent = file.name + ` (${(file.size/1024).toFixed(1)} KB)`;
    document.getElementById('ipd-file-preview-card').style.display = 'block';
    if (file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = e => {
        const img = document.getElementById('ipd-file-preview-img');
        img.src = e.target.result;
        img.style.display = 'block';
      };
      reader.readAsDataURL(file);
    } else {
      document.getElementById('ipd-file-preview-img').style.display = 'none';
      document.getElementById('ipd-file-preview-img').src = '';
    }
  }
}

async function submitIpdResult() {
  const findings = document.getElementById('ipd-rep-find').value.trim();
  const impression = document.getElementById('ipd-rep-imp').value.trim();
  const technique = document.getElementById('ipd-rep-tech').value.trim();
  const history = document.getElementById('ipd-rep-history').value.trim();

  if (!findings || !impression) {
    radToast('Please enter Findings and Impression before saving', 'warning');
    return;
  }

  const resultData = {
    technique: technique,
    clinical_history: history,
    findings: findings,
    impression: impression
  };

  const btn = document.getElementById('btnSaveIpdResult');
  btn.disabled = true;
  btn.innerHTML = '<div class="lis-spinner"></div> Saving...';

  const formData = new FormData();
  formData.append('technique', technique);
  formData.append('clinical_history', history);
  formData.append('findings', findings);
  formData.append('impression', impression);
  formData.append('result_data', JSON.stringify(resultData));
  formData.append('status', 'Reported');
  formData.append('patient_id', activeIpdPatientId || '');
  formData.append('test_name', (document.getElementById('ipd-res-meta').textContent.split(' • ')[0] || '').trim());

  const fileInput = document.getElementById('ipd-report-file');
  if (fileInput.files && fileInput.files[0]) {
    formData.append('report_file', fileInput.files[0]);
  }

  try {
    const res = await radApi('POST', `/api/radiology/ipd-orders/${encodeURIComponent(activeIpdOrderId)}/result`, formData);
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-check-circle"></i> Save Inpatient Report';
    if (res.success) {
      radToast('Inpatient radiology report saved successfully!', 'success');
      closeIpdResultModal();
      loadIpdOrders();
      if (typeof fetchRadNotifications === 'function') fetchRadNotifications();
    } else {
      radToast(res.message || 'Error saving report', 'error');
    }
  } catch(e) {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-check-circle"></i> Save Inpatient Report';
    radToast('Error saving report', 'error');
  }
}


// Close modal on backdrop click and Esc key
const ipdModalEl = document.getElementById('ipdResultModal');
if (ipdModalEl) {
  ipdModalEl.addEventListener('click', function(e) {
    if (e.target === this) closeIpdResultModal();
  });
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    if (typeof Swal !== 'undefined' && Swal.isVisible()) {
      Swal.close();
    } else {
      closeIpdResultModal();
    }
  }
});

loadIpdOrders();
</script>
