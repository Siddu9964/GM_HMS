<?php
$pageTitle = 'BMW Dispatch to Vendor';
$pageIcon  = 'fa-truck-medical';
$pageDesc  = 'Log vendor pickup, reconcile weights & generate reference number';
require_once __DIR__ . '/includes/quality_head.php';
?>
<?php require_once __DIR__ . '/includes/quality_sidebar.php'; ?>

<div class="qsc-main">
<?php require_once __DIR__ . '/includes/quality_navbar.php'; ?>

<div class="qsc-content">

  <!-- Pending Dispatch List -->
  <div class="qsc-card">
    <div class="qsc-card-header">
      <span class="qsc-card-title"><i class="fas fa-clock" style="color:var(--qsc-primary);margin-right:6px;"></i>Pending Dispatch Queue</span>
      <span class="badge" style="background:#f59e0b;font-size:0.8rem;" id="pending-count-badge">Loading…</span>
    </div>
    <div class="qsc-card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:0.85rem;" id="pendingTable">
          <thead style="background:#fef9c3;">
            <tr>
              <th class="ps-3">#</th>
              <th>Collection Date</th>
              <th>Location</th>
              <th>H. Total (Kg)</th>
              <th><span class="bin-badge bin-green"><i class="fas fa-trash-can me-1"></i>Green</span></th>
              <th><span class="bin-badge bin-yellow"><i class="fas fa-trash-can me-1"></i>Yellow</span></th>
              <th><span class="bin-badge bin-red"><i class="fas fa-trash-can me-1"></i>Red</span></th>
              <th><span class="bin-badge bin-blue"><i class="fas fa-trash-can me-1"></i>Blue</span></th>
              <th><span class="bin-badge bin-white"><i class="fas fa-trash-can me-1"></i>White</span></th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="pending-tbody">
            <tr><td colspan="10" class="text-center py-3 text-muted">Loading…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Dispatch Form Card -->
  <div class="qsc-card" id="dispatch-form-card" style="display:none;">
    <div class="qsc-card-header" style="background:var(--qsc-green);color:var(--qsc-cream);">
      <span class="qsc-card-title" style="color:var(--qsc-cream);">
        <i class="fas fa-truck-medical me-2"></i>Dispatch to Vendor
        &nbsp;<small id="dispatch-record-label" style="opacity:0.75;font-weight:500;"></small>
      </span>
      <button class="btn-qsc-outline" onclick="closeDispatchForm()"
              style="padding:5px 12px;border-color:rgba(243,239,230,0.4);color:var(--qsc-cream);background:transparent;">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="qsc-card-body">
      <input type="hidden" id="dispatch-record-id">

      <!-- Hospital Weight Summary -->
      <div class="qsc-card mb-3" style="border-left:4px solid var(--qsc-primary);">
        <div class="qsc-card-header" style="border:none;padding:12px 16px;">
          <span class="qsc-card-title" style="font-size:0.9rem;">Hospital Recorded Weight</span>
        </div>
        <div class="qsc-card-body" id="h-weight-summary" style="padding:12px 16px;">
          <!-- Filled by JS -->
        </div>
      </div>

      <!-- Vendor & Vehicle Details -->
      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <label class="form-label fw-semibold">Vendor Name *</label>
          <input type="text" id="d-vendor"  class="form-control" placeholder="Vendor / Agency name" required>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Vehicle Number *</label>
          <input type="text" id="d-vehicle" class="form-control" placeholder="KA 01 AB 1234" required>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Receipt No. / Manifest No.</label>
          <input type="text" id="d-ref"     class="form-control" placeholder="Receipt No. (Auto if blank)">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Driver Name</label>
          <input type="text" id="d-driver"  class="form-control" placeholder="Optional">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Driver Contact</label>
          <input type="tel"  id="d-contact" class="form-control" placeholder="Optional">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Dispatch Date & Time</label>
          <input type="datetime-local" id="d-dispatch-at" class="form-control">
        </div>
      </div>

      <hr>

      <!-- Vendor Bin Weights -->
      <div class="qsc-card-title mb-2" style="font-size:0.9rem;">
        <i class="fas fa-trash-can me-1" style="color:var(--qsc-green);"></i> Vendor-Weighed Bin Weights (Kg)
      </div>
      <div class="bin-input-grid">
        <div class="bin-input-card bin-green">
          <label><i class="fas fa-trash-can me-1 text-success"></i>Green Bin</label>
          <input type="number" id="d-green"  min="0" step="0.01" placeholder="0.00">
        </div>
        <div class="bin-input-card bin-yellow">
          <label><i class="fas fa-trash-can me-1 text-warning"></i>Yellow Bin</label>
          <input type="number" id="d-yellow" min="0" step="0.01" placeholder="0.00">
        </div>
        <div class="bin-input-card bin-red">
          <label><i class="fas fa-trash-can me-1 text-danger"></i>Red Bin</label>
          <input type="number" id="d-red"    min="0" step="0.01" placeholder="0.00">
        </div>
        <div class="bin-input-card bin-blue">
          <label><i class="fas fa-trash-can me-1 text-primary"></i>Blue Bin</label>
          <input type="number" id="d-blue"   min="0" step="0.01" placeholder="0.00">
        </div>
        <div class="bin-input-card bin-white">
          <label><i class="fas fa-trash-can me-1 text-secondary"></i>White Bin</label>
          <input type="number" id="d-white"  min="0" step="0.01" placeholder="0.00">
        </div>
      </div>

      <!-- Reconciliation Preview -->
      <div class="row g-3 mt-2">
        <div class="col-md-4">
          <div class="qsc-total-badge">
            <i class="fas fa-hospital"></i> Hospital:
            <span class="qsc-total-val ms-auto" id="recon-h-total">— Kg</span>
          </div>
        </div>
        <div class="col-md-4">
          <div class="qsc-total-badge" style="background:var(--qsc-green-mid);">
            <i class="fas fa-truck"></i> Vendor:
            <span class="qsc-total-val ms-auto" id="recon-v-total">0.00 Kg</span>
          </div>
        </div>
        <div class="col-md-4">
          <div class="qsc-total-badge" id="recon-variance-card" style="background:linear-gradient(135deg,#64748b,#94a3b8);">
            <i class="fas fa-scale-balanced"></i> Variance:
            <span class="qsc-total-val ms-auto" id="recon-variance">0.00 Kg</span>
          </div>
        </div>
      </div>

      <div class="mt-3">
        <label class="form-label fw-semibold">Remarks</label>
        <textarea id="d-remarks" class="form-control" rows="2" placeholder="Optional remarks…"></textarea>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-4">
        <button class="btn-qsc-outline" onclick="closeDispatchForm()">Cancel</button>
        <button class="btn-qsc-primary" id="dispatch-save-btn" onclick="saveDispatch()">
          <i class="fas fa-check-circle me-1"></i> Confirm Dispatch
        </button>
      </div>

    </div>
  </div>

</div><!-- /.qsc-content -->
</div><!-- /.qsc-main -->

<script>
let _hTotalWeight = 0;
let _selectedRecord = null;

// ─── Live Vendor Total + Variance ────────────────────────────────────────────
['d-green','d-yellow','d-red','d-blue','d-white'].forEach(id => {
  document.getElementById(id).addEventListener('input', updateReconciliation);
});

function updateReconciliation() {
  const vg = parseFloat(document.getElementById('d-green').value  || 0);
  const vy = parseFloat(document.getElementById('d-yellow').value || 0);
  const vr = parseFloat(document.getElementById('d-red').value    || 0);
  const vb = parseFloat(document.getElementById('d-blue').value   || 0);
  const vw = parseFloat(document.getElementById('d-white').value  || 0);
  const vTotal     = vg + vy + vr + vb + vw;
  const variance   = vTotal - _hTotalWeight;

  document.getElementById('recon-v-total').textContent   = vTotal.toFixed(2) + ' Kg';
  document.getElementById('recon-variance').textContent  = (variance >= 0 ? '+' : '') + variance.toFixed(2) + ' Kg';

  const varCard = document.getElementById('recon-variance-card');
  if (Math.abs(variance) < 0.01) {
    varCard.style.background = 'linear-gradient(135deg,#16a34a,#4ade80)';
  } else if (variance > 0) {
    varCard.style.background = 'linear-gradient(135deg,#dc2626,#f87171)';
  } else {
    varCard.style.background = 'linear-gradient(135deg,#d97706,#fbbf24)';
  }
}

// ─── Load Pending Queue ──────────────────────────────────────────────────────
async function loadPendingQueue() {
  const res = await qscApi('/api/quality/bmw/records?status=Collected&limit=100');
  const rows = res.data?.data ?? [];
  document.getElementById('pending-count-badge').textContent = `${rows.length} pending`;
  const tbody = document.getElementById('pending-tbody');

  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="10" class="text-center py-3 text-success"><i class="fas fa-check-circle me-2"></i>All records have been dispatched!</td></tr>';
    return;
  }

  tbody.innerHTML = rows.map((r, i) => `
    <tr>
      <td class="ps-3">${i+1}</td>
      <td>${r.collection_at ?? '—'}</td>
      <td><strong>${r.location}</strong></td>
      <td><strong>${r.h_total_weight.toFixed(2)} Kg</strong></td>
      <td>${r.h_green_weight  > 0 ? '<span class="bin-badge bin-green">'  +r.h_green_weight.toFixed(2)+'</span>'  : '—'}</td>
      <td>${r.h_yellow_weight > 0 ? '<span class="bin-badge bin-yellow">'+r.h_yellow_weight.toFixed(2)+'</span>' : '—'}</td>
      <td>${r.h_red_weight    > 0 ? '<span class="bin-badge bin-red">'   +r.h_red_weight.toFixed(2)+'</span>'    : '—'}</td>
      <td>${r.h_blue_weight   > 0 ? '<span class="bin-badge bin-blue">'  +r.h_blue_weight.toFixed(2)+'</span>'   : '—'}</td>
      <td>${r.h_white_weight  > 0 ? '<span class="bin-badge bin-white">' +r.h_white_weight.toFixed(2)+'</span>'  : '—'}</td>
      <td>
        <button class="btn btn-sm btn-outline-success" onclick="openDispatchForm(${JSON.stringify(r).replace(/"/g,'&quot;')})">
          <i class="fas fa-truck-medical me-1"></i> Dispatch
        </button>
      </td>
    </tr>`).join('');
}

// ─── Open Dispatch Form ──────────────────────────────────────────────────────
function openDispatchForm(r) {
  _hTotalWeight    = parseFloat(r.h_total_weight);
  _selectedRecord  = r;

  document.getElementById('dispatch-record-id').value = r.id;
  document.getElementById('dispatch-record-label').textContent = `— Record #${r.id} / ${r.location}`;
  document.getElementById('d-dispatch-at').value = getLocalDateTimeString();
  document.getElementById('recon-h-total').textContent = _hTotalWeight.toFixed(2) + ' Kg';

  // Hospital summary
  const summary = document.getElementById('h-weight-summary');
  summary.innerHTML = ['green','yellow','red','blue','white'].map(c => {
    const v = r[`h_${c}_weight`];
    return v > 0 ? `<span class="bin-badge bin-${c} me-2"><i class="fas fa-trash-can me-1"></i>${c.charAt(0).toUpperCase()+c.slice(1)}: ${parseFloat(v).toFixed(2)} Kg</span>` : '';
  }).join('') + `<span class="ms-3 fw-bold">Total: ${_hTotalWeight.toFixed(2)} Kg</span>`;

  document.getElementById('dispatch-form-card').style.display = 'block';
  document.getElementById('dispatch-form-card').scrollIntoView({ behavior: 'smooth' });
  updateReconciliation();
}

function closeDispatchForm() {
  document.getElementById('dispatch-form-card').style.display = 'none';
  document.getElementById('d-vendor').value = '';
  document.getElementById('d-vehicle').value = '';
  ['d-green','d-yellow','d-red','d-blue','d-white'].forEach(id => document.getElementById(id).value = '');
  updateReconciliation();
}

// ─── Save Dispatch ────────────────────────────────────────────────────────────
async function saveDispatch() {
  const id  = document.getElementById('dispatch-record-id').value;
  const btn = document.getElementById('dispatch-save-btn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Confirming…';

  const dispatchVal = document.getElementById('d-dispatch-at').value || getLocalDateTimeString();
  const body = {
    dispatch_at:     dispatchVal.replace('T',' '),
    dispatch_time:   (dispatchVal.includes('T') ? dispatchVal.split('T')[1] : getLocalTimeString()).slice(0,5) + ':00',
    vendor_name:     document.getElementById('d-vendor').value,
    vehicle_number:  document.getElementById('d-vehicle').value,
    driver_name:     document.getElementById('d-driver').value,
    driver_contact:  document.getElementById('d-contact').value,
    reference_no:    document.getElementById('d-ref').value,
    v_green_weight:  parseFloat(document.getElementById('d-green').value  || 0),
    v_yellow_weight: parseFloat(document.getElementById('d-yellow').value || 0),
    v_red_weight:    parseFloat(document.getElementById('d-red').value    || 0),
    v_blue_weight:   parseFloat(document.getElementById('d-blue').value   || 0),
    v_white_weight:  parseFloat(document.getElementById('d-white').value  || 0),
    remarks:         document.getElementById('d-remarks').value
  };

  try {
    const res = await qscApi(`/api/quality/bmw/records/${id}/dispatch`, { method: 'POST', body: JSON.stringify(body) });
    const d   = res.data;
    const confirm = await Swal.fire({
      icon: 'success',
      title: 'Dispatch Confirmed!',
      html: `Receipt / Ref No: <strong>${d.reference_no}</strong><br>
             Hospital: ${parseFloat(d.h_total_weight).toFixed(2)} Kg &nbsp;|&nbsp;
             Vendor: ${parseFloat(d.v_total_weight).toFixed(2)} Kg &nbsp;|&nbsp;
             Variance: ${parseFloat(d.weight_difference) >= 0 ? '+' : ''}${parseFloat(d.weight_difference).toFixed(2)} Kg`,
      confirmButtonColor: '#1f6b4a',
      confirmButtonText: 'Print Manifest',
      showCancelButton: true,
      cancelButtonText: 'Done'
    });
    if (confirm.isConfirmed) {
      window.open(`/GM_HMS/quality_view/manifest_print.php?id=${id}`, '_blank');
    }
    closeDispatchForm();
    loadPendingQueue();
  } catch(e) {
    qscToast(e.message, 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-check-circle me-1"></i> Confirm Dispatch';
  }
}

// Init
loadPendingQueue();
</script>

<?php require_once __DIR__ . '/includes/quality_foot.php'; ?>
