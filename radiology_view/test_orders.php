<?php
$pageTitle = 'Radiology Orders';
$pageIcon  = 'fa-x-ray';
$navTitle  = 'Scan Orders';
$navSub    = 'View, schedule and report outpatient imaging requests';
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
  background:linear-gradient(135deg, #1f6b4a 0%, #0369a1 100%);
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
.ro-filter-top{padding:16px 22px;display:grid;grid-template-columns:auto auto auto auto 1fr auto;gap:12px;align-items:flex-end;background:var(--s-lt);border-bottom:1px solid var(--bdr)}
.ro-flabel{font-size:.65rem;font-weight:800;text-transform:uppercase;color:var(--txt-mut);margin-bottom:4px;display:block;}
.ro-finput{padding:9px 13px;border:1.5px solid var(--bdr);border-radius:var(--r-md);font-size:.82rem;color:var(--txt);background:white;outline:none;font-family:'Inter',sans-serif;}
.ro-chip-bar{padding:12px 22px;display:flex;gap:8px;align-items:center;background:var(--s);flex-wrap:wrap}
.ro-chip{padding:5px 14px;border-radius:20px;font-size:.74rem;font-weight:700;cursor:pointer;border:1.5px solid rgba(31,107,74,.2);background:white;color:var(--txt);transition:all .2s;}
.ro-chip.active{background:#0284c7;color:white;border-color:#0284c7;}
.ro-table-card{background:var(--s);border-radius:var(--r-lg);border:1.5px solid var(--bdr);box-shadow:0 6px 20px rgba(0,0,0,0.06);overflow:hidden}
.ro-table{width:100%;border-collapse:collapse;font-size:.82rem}
.ro-table thead tr{background:var(--s-dk)}
.ro-table thead th{padding:12px 14px;font-size:.65rem;font-weight:800;text-transform:uppercase;color:var(--txt-mut);border-bottom:2px solid rgba(31,107,74,.2);text-align:left;}
.ro-table tbody tr{border-bottom:1px solid var(--bdr);background:var(--s);transition:background .15s;}
.ro-table tbody tr:hover{background:white}
.ro-table tbody td{padding:13px 14px;vertical-align:middle}
.att-grid{display:flex;flex-wrap:wrap;gap:8px;padding:12px;min-height:60px}
.att-thumb{width:70px;height:70px;border-radius:var(--r-md);object-fit:cover;border:2px solid var(--bdr);cursor:pointer;}
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
          <i class="fas fa-radiation"></i>
        </div>
        <div>
          <h1 style="margin:0;font-size:1.55rem;font-weight:900;line-height:1.1;">Radiology Scan Orders</h1>
          <p style="margin:5px 0 0;font-size:.8rem;opacity:0.85;">Track, execute, and report outpatient diagnostic imaging procedures</p>
        </div>
      </div>
      <div style="display:flex;gap:10px;">
        <button class="lb lb-ghost" onclick="loadOrders()"><i class="fas fa-sync-alt"></i> Refresh</button>
        <button class="lb lb-cream" onclick="openCreateModal()"><i class="fas fa-plus"></i> New Scan Order</button>
      </div>
    </div>

    <!-- Stats row in header -->
    <div style="display:grid; grid-template-columns:repeat(4,1fr); border-top:1px solid rgba(255,255,255,0.2);">
      <div style="padding:16px 28px; border-right:1px solid rgba(255,255,255,0.15);">
        <div id="stat-total" style="font-size:1.8rem;font-weight:900;line-height:1;">—</div>
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.8;margin-top:4px;">Total Today</div>
      </div>
      <div style="padding:16px 28px; border-right:1px solid rgba(255,255,255,0.15);">
        <div id="stat-pending" style="font-size:1.8rem;font-weight:900;line-height:1;">—</div>
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.8;margin-top:4px;">Pending Scans</div>
      </div>
      <div style="padding:16px 28px; border-right:1px solid rgba(255,255,255,0.15);">
        <div id="stat-progress" style="font-size:1.8rem;font-weight:900;line-height:1;">—</div>
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.8;margin-top:4px;">In Progress</div>
      </div>
      <div style="padding:16px 28px;">
        <div id="stat-done" style="font-size:1.8rem;font-weight:900;line-height:1;">—</div>
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;opacity:.8;margin-top:4px;">Reported</div>
      </div>
    </div>
  </div>

  <!-- Filters -->
  <div class="ro-filters">
    <div class="ro-filter-top">
      <div>
        <label class="ro-flabel">Date</label>
        <input type="date" class="ro-finput" id="filter-date" value="<?= date('Y-m-d') ?>">
      </div>
      <div>
        <label class="ro-flabel">Modality</label>
        <select class="ro-finput" id="filter-modality" onchange="loadOrders()">
          <option value="">All Modalities</option>
          <option value="X RAY">X-Ray</option>
          <option value="CT">CT Scan</option>
          <option value="ULTRA SOUND">Ultrasound</option>
          <option value="DOPPLER">Doppler</option>
        </select>
      </div>
      <div>
        <label class="ro-flabel">Status</label>
        <select class="ro-finput" id="filter-status" onchange="loadOrders()">
          <option value="">All Statuses</option>
          <option value="Ordered">Ordered</option>
          <option value="In Progress">In Progress</option>
          <option value="Completed">Completed</option>
          <option value="Reported">Reported</option>
        </select>
      </div>
      <div>
        <label class="ro-flabel">Priority</label>
        <select class="ro-finput" id="filter-priority" onchange="loadOrders()">
          <option value="">All Priorities</option>
          <option value="Urgent">Urgent / STAT</option>
          <option value="Routine">Routine</option>
        </select>
      </div>
      <div>
        <label class="ro-flabel">Search</label>
        <input type="text" class="ro-finput" id="filter-search" placeholder="Search procedure, patient, ID..." style="width:100%;" oninput="loadOrders()">
      </div>
      <div>
        <label class="ro-flabel" style="opacity:0;">Go</label>
        <button class="lb lb-outline" onclick="resetFilters()"><i class="fas fa-undo"></i> Reset</button>
      </div>
    </div>
  </div>

  <!-- Orders Table -->
  <div class="ro-table-card">
    <div style="padding:16px 22px;display:flex;justify-content:space-between;align-items:center;border-bottom:2px solid var(--bdr);">
      <div style="font-size:1rem;font-weight:900;color:var(--txt);display:flex;align-items:center;gap:10px;">
        <i class="fas fa-list-alt" style="color:var(--p);"></i> Radiology Orders Queue
        <span class="lis-badge" id="orders-count-badge" style="background:#0284c7;color:white;">0</span>
      </div>
    </div>

    <div id="orders-table-wrap" style="overflow-x:auto;">
      <table class="ro-table">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Patient Details</th>
            <th>Procedure Name</th>
            <th>Modality</th>
            <th>Doctor</th>
            <th>Date / Time</th>
            <th style="text-align:center;">Status</th>
            <th style="text-align:center;">Actions</th>
          </tr>
        </thead>
        <tbody id="orders-tbody">
          <tr><td colspan="8" style="text-align:center;padding:40px;color:#64748b;">Loading radiology orders...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div><!-- /.ro-page -->
</div><!-- /.lis-content -->
</div><!-- /.lis-main-content -->

<!-- ══════════════════════════════════════════════════════════════
     CREATE ORDER MODAL
     ══════════════════════════════════════════════════════════════ -->
<div class="lis-modal-overlay" id="createModal" style="display:none;">
  <div class="lis-modal" style="max-width:650px;">
    <div class="lis-modal-header" style="background:linear-gradient(135deg,var(--lis-primary),#0284c7);">
      <div class="lis-modal-title">
        <div class="lis-modal-title-icon"><i class="fas fa-plus"></i></div>
        <div>
          New Radiology Scan Order
          <div style="font-size:.68rem;opacity:0.85;">Order diagnostic imaging from radiology catalog</div>
        </div>
      </div>
      <button type="button" class="lis-modal-close" onclick="closeCreateModal()" title="Close (Esc)"><i class="fas fa-times"></i></button>
    </div>
    <div class="lis-modal-body">
      
      <!-- Patient Mode Toggle -->
      <div style="display:flex; gap:10px; margin-bottom: 20px;">
         <button class="lb lb-primary" id="btn-mode-reg" onclick="setOrderMode('registered')" style="flex:1">Registered Patient</button>
         <button class="lb lb-outline" id="btn-mode-wlk" onclick="setOrderMode('walkin')" style="flex:1">Walk-In Client</button>
      </div>

      <!-- Registered Patient -->
      <div id="section-reg">
        <div class="lis-form-group" style="position:relative;">
          <label class="lis-label">Search Patient *</label>
          <div style="position:relative;">
            <i class="fas fa-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none;font-size:0.85rem;"></i>
            <input type="text" class="lis-input" id="modal-patient-search" placeholder="Type patient name, UHID (e.g. PID-...), or phone..." autocomplete="off" style="padding-left:38px;width:100%;">
          </div>
          <div id="patient-results" style="background:white;border:1px solid #cbd5e1;border-radius:10px;max-height:220px;overflow-y:auto;display:none;position:absolute;top:100%;left:0;right:0;width:100%;z-index:1050;box-shadow:0 12px 28px rgba(15,23,42,0.18);margin-top:4px;"></div>
          <input type="hidden" id="modal-patient-id">
          <input type="hidden" id="modal-doctor-id">
        </div>
        <div id="selected-patient-card" style="display:none;background:#f0fdf4;border:1.5px solid #86efac;border-radius:10px;padding:12px 16px;margin-bottom:15px;position:relative;">
           <button type="button" onclick="clearSelectedPatient()" style="position:absolute;top:10px;right:12px;background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;border-radius:6px;padding:3px 10px;cursor:pointer;font-size:0.75rem;font-weight:700;">
             <i class="fas fa-times me-1"></i>Change
           </button>
           <div style="font-weight:700;color:#166534;font-size:0.95rem;" id="sp-name"></div>
           <div style="font-size:0.8rem;color:#374151;margin-top:4px;" id="sp-meta"></div>
        </div>
      </div>

      <!-- Walk-in Patient -->
      <div id="section-wlk" style="display:none;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
          <div><label class="lis-label">Patient Name *</label><input type="text" class="lis-input" id="wlk-name" placeholder="Walk-in full name"></div>
          <div><label class="lis-label">Age *</label><input type="number" class="lis-input" id="wlk-age" placeholder="Age in years"></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div><label class="lis-label">Phone Number</label><input type="text" class="lis-input" id="wlk-phone" placeholder="10-digit mobile"></div>
          <div><label class="lis-label">Referring Doctor</label><input type="text" class="lis-input" id="wlk-doctor" placeholder="Dr name or ID"></div>
        </div>
      </div>

      <!-- Procedure Picker from radiology_services -->
      <div class="lis-form-group" style="margin-top:14px;position:relative;">
        <label class="lis-label">Select Radiology Procedures (from radiology_services) *</label>
        <div style="position:relative;">
          <input type="text" class="lis-input" id="modal-procedure-search" placeholder="Search procedure (e.g. 3D CT, Chest X-Ray, USG, MRI)..." autocomplete="off" style="width:100%;">
          <div id="procedure-results" style="background:white;border:1px solid #cbd5e1;border-radius:10px;max-height:220px;overflow-y:auto;display:none;position:absolute;top:100%;left:0;right:0;width:100%;z-index:1050;box-shadow:0 12px 28px rgba(15,23,42,0.18);margin-top:4px;"></div>
        </div>
        <div id="selected-procedures" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;"></div>
      </div>

      <div class="lis-form-group">
        <label class="lis-label">Priority</label>
        <select class="lis-input" id="modal-priority">
          <option value="Routine">Routine</option>
          <option value="Stat">STAT / Urgent</option>
        </select>
      </div>

      <div class="lis-form-group">
        <label class="lis-label">Clinical Indication / Notes</label>
        <textarea class="lis-input" id="modal-notes" rows="2" placeholder="Clinical history, suspect diagnosis, instructions..."></textarea>
      </div>

    </div>
    <div class="lis-modal-footer">
      <button type="button" class="lb lb-outline" onclick="closeCreateModal()">Cancel</button>
      <button type="button" class="lb lb-primary" id="btnSubmitScanOrder" onclick="submitCreateOrder()"><i class="fas fa-paper-plane"></i> Send Scan Order</button>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     RADIOLOGY RESULT ENTRY WORKSPACE (STRUCTURED REPORTING + DICOM)
     ══════════════════════════════════════════════════════════════ -->
<div class="lis-modal-overlay" id="resultModal" style="display:none;">
  <div class="lis-modal" style="max-width:1080px;width:96%;">
    <div class="lis-modal-header" style="background:linear-gradient(135deg,#1f6b4a,#0284c7);">
      <div class="lis-modal-title">
        <div class="lis-modal-title-icon"><i class="fas fa-radiation"></i></div>
        <div>
          Radiology Report Data Center
          <div style="font-size:.7rem;opacity:0.85;">Structured Findings, Impression & Scan Attachments</div>
        </div>
      </div>
      <button type="button" class="lis-modal-close" onclick="closeResultModal()" title="Close (Esc)"><i class="fas fa-times"></i></button>
    </div>

    <div class="lis-modal-body" style="max-height:78vh;overflow-y:auto;padding:20px;">
      
      <!-- Top Order Bar -->
      <div style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;padding:12px 16px;display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div>
          <span style="font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;">Order ID:</span>
          <strong id="res-order-id" style="font-family:monospace;color:#0284c7;font-size:1rem;margin-left:6px;"></strong>
        </div>
        <div>
          <span style="font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;">Examination:</span>
          <strong id="res-test-name" style="color:#1e293b;font-size:.95rem;margin-left:6px;"></strong>
        </div>
        <button type="button" class="lb lb-outline lb-sm" onclick="autoFillNormalTemplate()" style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;">
          <i class="fas fa-magic"></i> Auto-Fill Normal Template
        </button>
      </div>

      <!-- 2-Column Workspace: Left = Attachments, Right = Structured Report -->
      <div style="display:grid;grid-template-columns:300px 1fr;gap:20px;">

        <!-- Left: Image & Scan Attachments -->
        <div style="background:white;border:1.5px solid var(--bdr);border-radius:12px;padding:14px;display:flex;flex-direction:column;gap:12px;">
          <div style="font-weight:800;font-size:.85rem;color:var(--txt);display:flex;align-items:center;gap:6px;">
            <i class="fas fa-images" style="color:#0284c7;"></i> Scan Files & Images
          </div>
          
          <label style="display:flex;align-items:center;gap:10px;padding:12px;background:#f8fafc;border:1.5px dashed #0284c7;border-radius:10px;cursor:pointer;">
            <i class="fas fa-cloud-upload-alt" style="font-size:1.4rem;color:#0284c7;"></i>
            <div>
              <div style="font-size:.82rem;font-weight:700;color:#1e293b;">Upload Scans / PDF</div>
              <div style="font-size:.68rem;color:#64748b;">JPG, PNG, PDF, DICOM</div>
            </div>
            <input type="file" id="report-file-input" accept="image/*,application/pdf" style="display:none;" onchange="handleFileSelected(this)">
          </label>

          <div id="file-preview-card" style="display:none;background:#f1f5f9;border-radius:8px;padding:10px;border:1px solid #cbd5e1;position:relative;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
              <div style="font-size:.78rem;font-weight:700;color:#334155;word-break:break-all;" id="file-name-label"></div>
              <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeAttachedFile()" title="Close / Remove Attachment" style="padding:2px 7px;font-size:0.75rem;border-radius:6px;line-height:1;flex-shrink:0;">
                <i class="fas fa-times"></i>
              </button>
            </div>
            <img id="file-preview-img" style="max-width:100%;border-radius:6px;margin-top:8px;display:none;">
          </div>

          <div style="border-top:1px solid #e2e8f0;padding-top:10px;">
            <div style="font-size:.75rem;font-weight:700;color:#64748b;margin-bottom:6px;">Previous Patient Reports:</div>
            <div id="previous-reports-list" style="max-height:180px;overflow-y:auto;font-size:.75rem;color:#64748b;">
              None on file
            </div>
          </div>
        </div>

        <!-- Right: Structured Radiology Narrative -->
        <div style="display:flex;flex-direction:column;gap:14px;">
          <div>
            <label class="ro-flabel">Technique / Protocol</label>
            <input type="text" class="ro-finput" id="rep-technique" style="width:100%;" placeholder="e.g. Axial sections obtained from skull base to vertex with 5mm slice thickness">
          </div>
          <div>
            <label class="ro-flabel">Clinical History / Indication</label>
            <input type="text" class="ro-finput" id="rep-history" style="width:100%;" placeholder="e.g. Headache, rule out intracranial bleed">
          </div>
          <div>
            <label class="ro-flabel">Detailed Findings *</label>
            <textarea class="ro-finput" id="rep-findings" rows="6" style="width:100%;font-family:monospace;font-size:.82rem;line-height:1.4;" placeholder="Describe anatomical structures, attenuation, contours, abnormalities..."></textarea>
          </div>
          <div>
            <label class="ro-flabel">Impression / Conclusion *</label>
            <textarea class="ro-finput" id="rep-impression" rows="3" style="width:100%;font-weight:700;color:#0f172a;" placeholder="Diagnostic conclusion summary..."></textarea>
          </div>
        </div>

      </div>

    </div>

    <div class="lis-modal-footer">
      <button class="lb lb-outline" onclick="closeResultModal()">Cancel</button>
      <button class="lb lb-primary" id="btnSaveResult" onclick="submitRadiologyResult()"><i class="fas fa-check-circle"></i> Finalize & Sign Report</button>
    </div>
  </div>
</div>

<?php require_once 'includes/rad_foot.php'; ?>

<script>
let allOrders = [];
let allServices = [];
let selectedProcedures = [];
let activeOrderId = null;
let activePatientId = null;

async function loadOrders() {
  const date = document.getElementById('filter-date').value;
  const modality = document.getElementById('filter-modality').value;
  const status = document.getElementById('filter-status').value;
  const priority = document.getElementById('filter-priority').value;
  const search = document.getElementById('filter-search').value;

  try {
    const res = await radApi('GET', `/api/radiology/orders?date=${date}&modality=${encodeURIComponent(modality)}&status=${status}&priority=${priority}&search=${encodeURIComponent(search)}`);
    if (res.success) {
      allOrders = res.data || [];
      renderOrders(allOrders);
    }
  } catch(e) {
    radToast('Error loading orders', 'error');
  }
}

function renderOrders(orders) {
  const tbody = document.getElementById('orders-tbody');
  document.getElementById('orders-count-badge').textContent = orders.length;

  let total = orders.length, pending = 0, inprog = 0, done = 0;
  orders.forEach(o => {
    if (o.status === 'Ordered') pending++;
    else if (o.status === 'In Progress') inprog++;
    else if (o.status === 'Completed' || o.status === 'Reported') done++;
  });
  document.getElementById('stat-total').textContent = total;
  document.getElementById('stat-pending').textContent = pending;
  document.getElementById('stat-progress').textContent = inprog;
  document.getElementById('stat-done').textContent = done;

  if (!orders.length) {
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;color:#64748b;">No radiology orders match the filters.</td></tr>';
    return;
  }

  tbody.innerHTML = orders.map(o => {
    const priColor = (o.priority === 'Urgent' || o.priority === 'Stat') ? 'color:#dc2626;font-weight:800;' : '';
    const modalitiesBadge = (o.modalities || []).map(m => `<span class="badge bg-secondary" style="font-size:0.65rem;margin-right:2px;">${escHtml(m)}</span>`).join('');
    
    return `
      <tr>
        <td><strong style="color:#0284c7;font-family:monospace;">${escHtml(o.order_id)}</strong></td>
        <td>
          <div style="font-weight:700;color:#1e293b;">${escHtml(o.patient_name)}</div>
          <div style="font-size:.7rem;color:#64748b;">ID: ${escHtml(o.patient_id)} • ${escHtml(o.age||'')} ${escHtml(o.sex||'')}</div>
        </td>
        <td>${formatProcedureListHtml(o.resolved_test_names || o.test_name)}</td>
        <td>${modalitiesBadge || '<span class="badge bg-light text-dark">Scan</span>'}</td>
        <td>Dr. ${escHtml(o.doctor_name || 'Consultant')}</td>
        <td style="font-size:.75rem;color:#64748b;">${escHtml(o.order_date)} ${escHtml(o.order_time ? o.order_time.slice(0,5) : '')}</td>
        <td style="text-align:center;">${radStatusBadge(o.status)}</td>
        <td style="text-align:center;white-space:nowrap;">
          <button class="lb lb-primary" style="padding:4px 10px;font-size:0.75rem;" onclick="openResultModal('${escHtml(o.order_id)}', '${escHtml(o.patient_id)}', '${escHtml(o.resolved_test_names||o.test_name)}')">
            <i class="fas fa-file-medical"></i> Report
          </button>
          <a href="print_result.php?order_id=${encodeURIComponent(o.order_id)}&source=OPD&from=orders" target="_blank" onclick="window.open(this.href, '_blank'); return false;" class="lb lb-outline" style="padding:4px 10px;font-size:0.75rem;" title="Print Report">
            <i class="fas fa-print"></i>
          </a>
        </td>
      </tr>
    `;
  }).join('');
}


function resetFilters() {
  document.getElementById('filter-date').value = '<?= date('Y-m-d') ?>';
  document.getElementById('filter-modality').value = '';
  document.getElementById('filter-status').value = '';
  document.getElementById('filter-priority').value = '';
  document.getElementById('filter-search').value = '';
  loadOrders();
}

/* ── Create Modal ─────────────────────────────────────────── */
function openCreateModal() {
  const cm = document.getElementById('createModal');
  if (cm) {
    cm.style.display = 'flex';
    void cm.offsetWidth;
    cm.classList.add('open');
  }
  loadServicesList();
  setTimeout(() => {
    const input = document.getElementById('modal-patient-search');
    if (input) input.focus();
  }, 100);
}

function closeCreateModal() {
  if (typeof Swal !== 'undefined' && Swal.isVisible()) {
    Swal.close();
  }
  const cm = document.getElementById('createModal');
  if (cm) {
    cm.classList.remove('open');
    cm.style.display = 'none';
  }
  document.body.classList.remove('modal-open');
  document.body.style.removeProperty('overflow');
  document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
  resetCreateForm();
}

function resetCreateForm() {
  document.getElementById('modal-patient-id').value = '';
  document.getElementById('modal-doctor-id').value = '';
  const pSearch = document.getElementById('modal-patient-search');
  if (pSearch) { pSearch.value = ''; pSearch.style.borderColor = ''; }
  const pCard = document.getElementById('selected-patient-card');
  if (pCard) pCard.style.display = 'none';
  const pBox = document.getElementById('patient-results');
  if (pBox) { pBox.style.display = 'none'; pBox.innerHTML = ''; }
  const sBox = document.getElementById('procedure-results');
  if (sBox) { sBox.style.display = 'none'; sBox.innerHTML = ''; }
  const sSearch = document.getElementById('modal-procedure-search');
  if (sSearch) sSearch.value = '';
  const notes = document.getElementById('modal-notes');
  if (notes) notes.value = '';
  const priority = document.getElementById('modal-priority');
  if (priority) priority.value = 'Routine';
  const wName = document.getElementById('wlk-name');
  if (wName) wName.value = '';
  const wAge = document.getElementById('wlk-age');
  if (wAge) wAge.value = '';
  const wPhone = document.getElementById('wlk-phone');
  if (wPhone) wPhone.value = '';
  const wDoc = document.getElementById('wlk-doctor');
  if (wDoc) wDoc.value = '';
  selectedProcedures = [];
  renderSelectedProcedures();
  setOrderMode('registered');
}

function setOrderMode(mode) {
  if (mode === 'walkin') {
    document.getElementById('section-reg').style.display = 'none';
    document.getElementById('section-wlk').style.display = 'block';
    document.getElementById('btn-mode-wlk').className = 'lb lb-primary';
    document.getElementById('btn-mode-reg').className = 'lb lb-outline';
  } else {
    document.getElementById('section-reg').style.display = 'block';
    document.getElementById('section-wlk').style.display = 'none';
    document.getElementById('btn-mode-reg').className = 'lb lb-primary';
    document.getElementById('btn-mode-wlk').className = 'lb lb-outline';
  }
}

async function loadServicesList() {
  if (allServices && allServices.length) return allServices;
  try {
    const res = await radApi('GET', '/api/radiology/services');
    if (res && res.success && res.data) {
      allServices = res.data.services || [];
    }
  } catch(e) {
    console.error('Failed to load radiology services:', e);
  }
  return allServices;
}

document.getElementById('modal-procedure-search').addEventListener('input', async function() {
  const q = this.value.toLowerCase().trim();
  const resultsDiv = document.getElementById('procedure-results');
  if (!q) { resultsDiv.style.display = 'none'; return; }

  if (!allServices || !allServices.length) {
    await loadServicesList();
  }

  const matches = (allServices || []).filter(s => 
    (s.billing_name && s.billing_name.toLowerCase().includes(q)) || 
    (s.service_id && s.service_id.toLowerCase().includes(q)) ||
    (s.modality_name && s.modality_name.toLowerCase().includes(q))
  );

  if (!matches.length) {
    resultsDiv.innerHTML = `<div style="padding:10px 14px;font-size:0.8rem;color:#64748b;"><i class="fas fa-info-circle me-1"></i> No radiology procedures found for "${escHtml(q)}"</div>`;
    resultsDiv.style.display = 'block';
    return;
  }

  resultsDiv.innerHTML = matches.slice(0, 10).map(s => `
    <div style="padding:10px 14px;border-bottom:1px solid #f1f5f9;cursor:pointer;display:flex;justify-content:space-between;align-items:center;" 
         onclick="selectProcedure('${s.service_id}', '${escHtml(s.billing_name)}')"
         onmouseover="this.style.background='#f0f9ff'" onmouseout="this.style.background=''">
      <div>
        <strong style="color:#0f172a;font-size:0.85rem;">${escHtml(s.billing_name)}</strong> 
        <span style="font-size:0.72rem;color:#0284c7;font-weight:600;margin-left:4px;">(${escHtml(s.service_id)})</span>
        ${s.modality_name ? `<span class="badge" style="background:#e2e8f0;color:#334155;font-size:0.65rem;margin-left:6px;">${escHtml(s.modality_name)}</span>` : ''}
      </div>
      <span class="badge bg-light text-dark" style="font-size:0.75rem;font-weight:700;">₹${s.opd_price}</span>
    </div>
  `).join('');
  resultsDiv.style.display = 'block';
});

function selectProcedure(id, name) {
  if (!selectedProcedures.find(p => p.id === id)) {
    selectedProcedures.push({ id, name });
    renderSelectedProcedures();
  }
  document.getElementById('modal-procedure-search').value = '';
  document.getElementById('procedure-results').style.display = 'none';
}

function renderSelectedProcedures() {
  const container = document.getElementById('selected-procedures');
  container.innerHTML = selectedProcedures.map((p, i) => `
    <span class="badge" style="background:var(--lis-primary);color:white;padding:6px 12px;font-size:0.78rem;border-radius:20px;display:inline-flex;align-items:center;gap:6px;">
      ${escHtml(p.name)} <i class="fas fa-times-circle" style="cursor:pointer;font-size:0.85rem;" onclick="removeProcedure(${i})" title="Remove"></i>
    </span>
  `).join('');
}

function removeProcedure(idx) {
  selectedProcedures.splice(idx, 1);
  renderSelectedProcedures();
}

// Patient search autocomplete with debounce and accurate response extraction
let patientSearchDebounce = null;
document.getElementById('modal-patient-search').addEventListener('input', function() {
  clearTimeout(patientSearchDebounce);
  const q = this.value.trim();
  const box = document.getElementById('patient-results');
  this.style.borderColor = '';

  if (q.length < 2) {
    box.style.display = 'none';
    box.innerHTML = '';
    return;
  }

  box.style.display = 'block';
  box.innerHTML = '<div style="padding:10px 14px;font-size:0.8rem;color:#64748b;"><i class="fas fa-spinner fa-spin me-1"></i> Searching patients...</div>';
  patientSearchDebounce = setTimeout(() => searchPatientsForOrder(q), 280);
});

async function searchPatientsForOrder(q) {
  const box = document.getElementById('patient-results');
  try {
    const res = await radApi('GET', `/api/patients?search=${encodeURIComponent(q)}`);
    // Patient API returns { success: true, data: { data: [...], pagination: {...} } }
    const pts = (res && res.data && Array.isArray(res.data.data))
                ? res.data.data
                : ((res && Array.isArray(res.data)) ? res.data : []);

    if (!pts.length) {
      box.innerHTML = `<div style="padding:12px 14px;font-size:0.82rem;color:#64748b;"><i class="fas fa-info-circle me-1"></i> No patients found matching "<strong>${escHtml(q)}</strong>"</div>`;
      box.style.display = 'block';
      return;
    }

    box.innerHTML = pts.slice(0, 10).map(p => {
      const name = p.full_name || ((p.first_name || '') + ' ' + (p.last_name || '')).trim();
      const jsonStr = encodeURIComponent(JSON.stringify(p));
      return `
        <div onclick="selectPatient('${jsonStr}')"
             style="padding:10px 14px;border-bottom:1px solid #f1f5f9;cursor:pointer;display:flex;justify-content:space-between;align-items:center;"
             onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background=''">
          <div>
            <div style="font-weight:700;color:#0f172a;font-size:0.88rem;">${escHtml(name)}</div>
            <div style="font-size:0.75rem;color:#64748b;margin-top:2px;">
              <span style="color:#0284c7;font-weight:600;">#${escHtml(p.patient_id)}</span> &bull; 
              ${p.age || '?'} yrs &bull; ${escHtml(p.sex || 'N/A')} &bull; 
              <span><i class="fas fa-phone fa-xs"></i> ${escHtml(p.phone || 'N/A')}</span>
            </div>
          </div>
          ${p.doctor_name ? `<span class="badge" style="background:#e0f2fe;color:#0369a1;font-size:0.7rem;">Dr. ${escHtml(p.doctor_name)}</span>` : ''}
        </div>
      `;
    }).join('');
    box.style.display = 'block';
  } catch(e) {
    console.error('Patient search error:', e);
    box.innerHTML = '<div style="padding:10px 14px;font-size:0.8rem;color:#dc2626;"><i class="fas fa-exclamation-triangle me-1"></i> Error searching patients</div>';
    box.style.display = 'block';
  }
}

function selectPatient(jsonStr) {
  const p = typeof jsonStr === 'string' ? JSON.parse(decodeURIComponent(jsonStr)) : jsonStr;
  const name = p.full_name || ((p.first_name || '') + ' ' + (p.last_name || '')).trim();

  document.getElementById('modal-patient-id').value = p.patient_id || '';
  document.getElementById('modal-doctor-id').value = p.doctor_id || '';
  document.getElementById('modal-patient-search').value = name;
  document.getElementById('modal-patient-search').style.borderColor = '#16a34a';

  document.getElementById('selected-patient-card').style.display = 'block';
  document.getElementById('sp-name').innerHTML = `<i class="fas fa-user-check me-1"></i> ${escHtml(name)}`;
  document.getElementById('sp-meta').innerHTML = `
    <strong style="color:#0284c7;">#${escHtml(p.patient_id)}</strong> &bull; 
    Age: ${p.age || '?'} yrs &bull; Sex: ${escHtml(p.sex || 'N/A')} &bull; 
    Phone: ${escHtml(p.phone || 'N/A')}
    ${p.doctor_name ? ` &bull; <strong>Ref:</strong> Dr. ${escHtml(p.doctor_name)}` : ''}
  `;
  document.getElementById('patient-results').style.display = 'none';
}

function clearSelectedPatient() {
  document.getElementById('modal-patient-id').value = '';
  document.getElementById('modal-doctor-id').value = '';
  const input = document.getElementById('modal-patient-search');
  if (input) {
    input.value = '';
    input.style.borderColor = '';
    input.focus();
  }
  document.getElementById('selected-patient-card').style.display = 'none';
  document.getElementById('patient-results').style.display = 'none';
}

// Close search dropdowns when clicking outside
document.addEventListener('click', function(e) {
  const pBox = document.getElementById('patient-results');
  const pInput = document.getElementById('modal-patient-search');
  if (pBox && pInput && !pBox.contains(e.target) && e.target !== pInput) {
    pBox.style.display = 'none';
  }
  const sBox = document.getElementById('procedure-results');
  const sInput = document.getElementById('modal-procedure-search');
  if (sBox && sInput && !sBox.contains(e.target) && e.target !== sInput) {
    sBox.style.display = 'none';
  }
});

async function submitCreateOrder() {
  const btn = document.getElementById('btnSubmitScanOrder');
  const originalHtml = btn ? btn.innerHTML : '';

  const isWlk = document.getElementById('section-wlk').style.display === 'block';
  let patientId = document.getElementById('modal-patient-id').value.trim();
  let patientType = 'Registered';

  if (isWlk) {
    const name = document.getElementById('wlk-name').value.trim();
    const age = document.getElementById('wlk-age').value.trim();
    const phone = document.getElementById('wlk-phone').value.trim();
    const doctor = document.getElementById('wlk-doctor').value.trim();

    if (!name) {
      radToast('Please enter walk-in patient name', 'warning');
      document.getElementById('wlk-name').focus();
      return;
    }
    if (!age) {
      radToast('Please enter walk-in patient age', 'warning');
      document.getElementById('wlk-age').focus();
      return;
    }
    patientId = 'WLK-' + Date.now();
    patientType = `Walkin:${name}|${age}|${phone}`;
    if (doctor) {
      document.getElementById('modal-doctor-id').value = doctor;
    }
  } else {
    if (!patientId) {
      radToast('Please search and select a patient first', 'warning');
      const pSearch = document.getElementById('modal-patient-search');
      if (pSearch) {
        pSearch.focus();
        pSearch.style.borderColor = '#dc2626';
      }
      return;
    }
  }

  if (!selectedProcedures || !selectedProcedures.length) {
    radToast('Please select at least one radiology procedure', 'warning');
    const sSearch = document.getElementById('modal-procedure-search');
    if (sSearch) sSearch.focus();
    return;
  }

  const payload = {
    patient_id: patientId,
    patient_type: patientType,
    doctor_id: document.getElementById('modal-doctor-id').value || '',
    test_name: selectedProcedures.map(p => `${p.name} (${p.id})`).join('|||'),
    priority: document.getElementById('modal-priority').value || 'Routine',
    clinical_notes: document.getElementById('modal-notes').value.trim()
  };

  try {
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Sending Scan Order...';
    }

    const res = await radApi('POST', '/api/radiology/orders', payload);
    if (res && res.success) {
      const orderId = res.data?.order_id || '';
      radToast(`Radiology scan order created successfully! ${orderId ? `(ID: ${orderId})` : ''}`, 'success');
      closeCreateModal();
      loadOrders();
    } else {
      radToast(res?.message || res?.error || 'Error creating radiology order', 'error');
    }
  } catch(e) {
    console.error('Order submission error:', e);
    radToast(e.message || 'Error submitting scan order. Please try again.', 'error');
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = originalHtml || '<i class="fas fa-paper-plane"></i> Send Scan Order';
    }
  }
}

/* ── Result Reporting Workspace ───────────────────────────── */
async function openResultModal(orderId, patientId, testName) {
  activeOrderId = orderId;
  activePatientId = patientId;
  document.getElementById('res-order-id').textContent = orderId;
  document.getElementById('res-test-name').textContent = testName;

  document.getElementById('rep-technique').value = '';
  document.getElementById('rep-history').value = '';
  document.getElementById('rep-findings').value = '';
  document.getElementById('rep-impression').value = '';
  document.getElementById('file-preview-card').style.display = 'none';
  document.getElementById('file-preview-img').style.display = 'none';
  document.getElementById('file-preview-img').src = '';
  document.getElementById('file-name-label').innerHTML = '';
  document.getElementById('report-file-input').value = '';

  const rm = document.getElementById('resultModal');
  if (rm) {
    rm.style.display = 'flex';
    void rm.offsetWidth;
    rm.classList.add('open');
  }

  // Load existing result if already reported
  try {
    const rData = await radApi('GET', `/api/radiology/orders/${encodeURIComponent(orderId)}/result`);
    if (rData.success && rData.data) {
      const d = rData.data;
      let parsed = {};
      try { parsed = typeof d.result_data === 'string' ? JSON.parse(d.result_data) : (d.result_data || {}); } catch(e) {}
      
      document.getElementById('rep-technique').value = d.technique || parsed.technique || '';
      document.getElementById('rep-history').value = d.clinical_history || parsed.clinical_history || '';
      document.getElementById('rep-findings').value = d.findings || parsed.findings || (typeof d.result_data === 'string' ? d.result_data : '');
      document.getElementById('rep-impression').value = d.impression || parsed.impression || '';
      
      if (d.report_file) {
        document.getElementById('file-preview-card').style.display = 'block';
        const fName = d.report_file.split('/').pop();
        document.getElementById('file-name-label').innerHTML = `<a href="/GM_HMS/${escHtml(d.report_file)}" target="_blank" style="color:#0284c7;font-weight:700;text-decoration:underline;"><i class="fas fa-file-medical"></i> ${escHtml(fName)}</a>`;
        if (d.report_file.match(/\.(jpeg|jpg|png|gif|webp)$/i)) {
          const img = document.getElementById('file-preview-img');
          img.src = '/GM_HMS/' + d.report_file;
          img.style.display = 'block';
        }
      }
    }
  } catch(e) {}

  // Load patient previous results
  if (patientId) {
    try {
      const prev = await radApi('GET', `/api/radiology/patients/${encodeURIComponent(patientId)}/previous-results`);
      if (prev.success && prev.data && prev.data.length) {
        document.getElementById('previous-reports-list').innerHTML = prev.data.slice(0, 5).map(p => `
          <div style="padding:4px 0;border-bottom:1px dashed #e2e8f0;">
            <strong>${escHtml(p.test_name)}</strong> • <small>${p.result_date}</small>
          </div>
        `).join('');
      } else {
        document.getElementById('previous-reports-list').innerHTML = 'No previous scans on record.';
      }
    } catch(e) {}
  }
}

function closeResultModal() {
  if (typeof Swal !== 'undefined' && Swal.isVisible()) {
    Swal.close();
  }
  const rm = document.getElementById('resultModal');
  if (rm) {
    rm.classList.remove('open');
    rm.style.display = 'none';
  }
  document.body.classList.remove('modal-open');
  document.body.style.removeProperty('overflow');
  document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
}

function removeAttachedFile() {
  const fi = document.getElementById('report-file-input');
  if (fi) fi.value = '';
  const card = document.getElementById('file-preview-card');
  if (card) card.style.display = 'none';
  const lbl = document.getElementById('file-name-label');
  if (lbl) lbl.innerHTML = '';
  const img = document.getElementById('file-preview-img');
  if (img) {
    img.style.display = 'none';
    img.src = '';
  }
}

async function autoFillNormalTemplate() {
  const examName = document.getElementById('res-test-name').textContent;
  try {
    const res = await radApi('POST', '/api/radiology/templates/auto-generate', { exam_name: examName });
    if (res.success && res.data) {
      const t = res.data;
      if (t.technique) document.getElementById('rep-technique').value = t.technique;
      if (t.clinical_history) document.getElementById('rep-history').value = t.clinical_history;
      if (t.findings) document.getElementById('rep-findings').value = t.findings;
      if (t.impression) document.getElementById('rep-impression').value = t.impression;
      radToast('Normal template loaded', 'success');
    }
  } catch(e) {
    radToast('Error loading template', 'error');
  }
}

function handleFileSelected(input) {
  if (input.files && input.files[0]) {
    const file = input.files[0];
    document.getElementById('file-name-label').textContent = file.name + ` (${(file.size/1024).toFixed(1)} KB)`;
    document.getElementById('file-preview-card').style.display = 'block';
    if (file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = e => {
        const img = document.getElementById('file-preview-img');
        img.src = e.target.result;
        img.style.display = 'block';
      };
      reader.readAsDataURL(file);
    } else {
      document.getElementById('file-preview-img').style.display = 'none';
      document.getElementById('file-preview-img').src = '';
    }
  }
}

async function submitRadiologyResult() {
  const findings = document.getElementById('rep-findings').value.trim();
  const impression = document.getElementById('rep-impression').value.trim();
  const technique = document.getElementById('rep-technique').value.trim();
  const history = document.getElementById('rep-history').value.trim();

  if (!findings || !impression) {
    radToast('Please enter both Findings and Impression before signing report', 'warning');
    return;
  }

  const resultData = {
    technique: technique,
    clinical_history: history,
    findings: findings,
    impression: impression
  };

  const btn = document.getElementById('btnSaveResult');
  btn.disabled = true;
  btn.innerHTML = '<div class="lis-spinner"></div> Saving...';

  const formData = new FormData();
  formData.append('technique', technique);
  formData.append('clinical_history', history);
  formData.append('findings', findings);
  formData.append('impression', impression);
  formData.append('result_data', JSON.stringify(resultData));
  formData.append('status', 'Reported');
  formData.append('test_name', document.getElementById('res-test-name').textContent);
  formData.append('patient_id', activePatientId || '');

  const fileInput = document.getElementById('report-file-input');
  if (fileInput.files && fileInput.files[0]) {
    formData.append('report_file', fileInput.files[0]);
  }

  try {
    const res = await radApi('POST', `/api/radiology/orders/${encodeURIComponent(activeOrderId)}/result`, formData);
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-check-circle"></i> Finalize & Sign Report';

    if (res.success) {
      radToast('Radiology report saved and finalized!', 'success');
      closeResultModal();
      loadOrders();
      if (typeof fetchRadNotifications === 'function') fetchRadNotifications();
    } else {
      radToast(res.message || 'Error saving report', 'error');
    }
  } catch(e) {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-check-circle"></i> Finalize & Sign Report';
    radToast('Network error saving report', 'error');
  }
}


// Close modals on backdrop click and Esc key
['resultModal', 'createModal'].forEach(id => {
  const el = document.getElementById(id);
  if (el) {
    el.addEventListener('click', function(e) {
      if (e.target === this) {
        if (id === 'resultModal') closeResultModal();
        else closeCreateModal();
      }
    });
  }
});

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    if (typeof Swal !== 'undefined' && Swal.isVisible()) {
      Swal.close();
    } else {
      closeResultModal();
      closeCreateModal();
    }
  }
});

loadOrders();
</script>
