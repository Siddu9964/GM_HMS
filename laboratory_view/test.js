
let allOrders = [];
let allTests  = [];
let activeOrderId = null;
let allServiceTests = [];
let prevTotalOrders = null;

async function loadOrders(isBackground = false) {
  if (!isBackground) {
    document.getElementById('table-loading').style.display = 'flex';
    document.getElementById('table-wrap').style.display   = 'none';
  }
  await loadAllServiceTests();

  const date     = document.getElementById('filter-date').value;
  const status   = document.getElementById('filter-status').value;
  const priority = document.getElementById('filter-priority').value;
  const search   = document.getElementById('filter-search').value;
  const allMode  = document.querySelector('.lo-chip.active')?.dataset.filter === 'all' ? '1' : '0';

  let url = `/api/laboratory/ipd-orders?all=${allMode}&date=${date}&status=${encodeURIComponent(status)}&priority=${encodeURIComponent(priority)}&search=${encodeURIComponent(search)}`;

  try {
    const data = await lisApi('GET', url);
    if (!isBackground) {
      document.getElementById('table-loading').style.display = 'none';
      document.getElementById('table-wrap').style.display    = 'block';
    }
    
    if (data.success) {
      const newOrders = data.data || [];
      
      // Notification logic for background polling
      if (isBackground && prevTotalOrders !== null && newOrders.length > prevTotalOrders) {
        lisToast('New Laboratory Test Ordered!', 'success');
        // If there's an existing sound file, you could play it here
      }
      prevTotalOrders = newOrders.length;
      
      allOrders = newOrders;
      renderOrders(allOrders);

      // Auto-open result entry modal if edit_result is requested
      const editResultId = "<?= $_GET['edit_result'] ?? '' ?>";
      if (editResultId) {
        const targetOrder = allOrders.find(o => o.order_id === editResultId);
        if (targetOrder && !document.getElementById('resultModal').classList.contains('open')) {
          openResultModal(targetOrder.order_id, targetOrder.test_name, targetOrder.patient_id);
        }
      }
    } else {
      if (!isBackground) renderOrders([]);
    }
  } catch(e) {
    if (!isBackground) {
      document.getElementById('table-loading').style.display = 'none';
      document.getElementById('table-wrap').style.display    = 'block';
      lisToast('Failed to load orders: ' + (e.message || e), 'error');
      console.error(e);
      renderOrders([]);
    }
  }
}

function renderOrders(orders) {
  const tbody = document.getElementById('orders-tbody');
  const empty = document.getElementById('orders-empty');
  document.getElementById('orders-count-badge').textContent = orders.length;

  // Stats
  const total    = orders.length;
  const pending  = orders.filter(o => o.status === 'Ordered').length;
  const progress = orders.filter(o => o.status === 'In Progress').length;
  const done     = orders.filter(o => o.status === 'Completed' || o.status === 'Reported').length;
  document.getElementById('stat-total').textContent   = total;
  document.getElementById('stat-pending').textContent  = pending;
  document.getElementById('stat-progress').textContent = progress;
  document.getElementById('stat-done').textContent     = done;

  if (!orders.length) {
    tbody.innerHTML = '';
    empty.style.display = 'flex';
    return;
  }
  empty.style.display = 'none';

  const statusMap = {
    'Ordered':     'b-ordered',
    'In Progress': 'b-progress',
    'Completed':   'b-completed',
    'Reported':    'b-reported'
  };
  const priorityMap = {
    'Urgent':  'b-urgent',
    'Stat':    'b-stat',
    'Routine': 'b-routine'
  };

  tbody.innerHTML = orders.map((o, i) => {
    const sCls = statusMap[o.status]   || 'b-ordered';
    const pCls = priorityMap[o.priority] || 'b-routine';
    const dt = o.order_date ? o.order_date.slice(5) : '';
    const tm = o.order_time ? o.order_time.slice(0,5) : '';
    const initials = (o.patient_name || 'P').split(' ').map(w=>w[0]||'').join('').slice(0,2).toUpperCase() || 'PT';

    let rawTestName = o.test_name || '';
    let displayTestName = rawTestName.split('|||').join(', ');
    try {
      const parsed = JSON.parse(rawTestName);
      if (Array.isArray(parsed)) {
        displayTestName = parsed.join(', ');
      }
    } catch(e) {}
    
    // Safely encode raw test name for onclick handler
    const rawTestNameB64 = btoa(unescape(encodeURIComponent(rawTestName)));

    let testNamesOnly = [];
    let testIdsOnly = [];
    displayTestName.split(',').forEach(part => {
      part = part.trim();
      const match = part.match(/(.*?)\s*\(([^)]+)\)$/);
      if (match) {
        testNamesOnly.push(match[1].trim());
        testIdsOnly.push(match[2].trim());
      } else {
        testNamesOnly.push(part);
        const found = allServiceTests.find(t => t.name.toLowerCase().startsWith(part.toLowerCase() + " ("));
        if (found) {
          testIdsOnly.push(found.id);
        }
      }
    });
    const finalTestName = testNamesOnly.join(', ');
    const finalTestId = testIdsOnly.length > 0 ? testIdsOnly.join(', ') : '-';

    return `<tr>
      <td style="color:var(--txt-mut);font-weight:700;font-size:.78rem;">${i+1}</td>
      <td>
        <code style="font-size:.72rem;background:var(--p-10);color:var(--p);padding:3px 8px;border-radius:6px;font-weight:800;">${escHtml(o.order_id)}</code>
      </td>
      <td>
        <div class="lo-pat-cell">
          <div class="lo-avatar">${escHtml(initials)}</div>
          <div>
            <div class="lo-pat-name">${escHtml(o.patient_name || '—')}</div>
            <div class="lo-pat-id">${escHtml(o.patient_id || '')}</div>
          </div>
        </div>
      </td>
      <td>
        <div style="font-size:.82rem;font-weight:700;color:var(--p);font-family:monospace;">
          ${escHtml(finalTestId)}
        </div>
      </td>
      <td>
        <div style="font-weight:700;font-size:.82rem;color:var(--txt);max-width:200px;white-space:normal;word-wrap:break-word;line-height:1.3;">
          ${escHtml(finalTestName)}
        </div>
      </td>
      <td>
        <div style="font-size:.8rem;font-weight:600;color:var(--txt);max-width:140px;white-space:normal;word-wrap:break-word;">${escHtml(o.doctor_name || '—')}</div>
        <div style="font-size:.68rem;color:var(--txt-mut);">${escHtml(o.specialization||'')}</div>
      </td>
      <td style="font-size:.75rem;color:var(--txt-mut);white-space:nowrap;">${dt} ${tm}</td>
      <td style="text-align:center; vertical-align:middle;">
        <button class="lb lb-primary" style="padding: 4px 10px; font-size: 0.75rem; width:100%; font-weight:700; gap:5px;" title="Enter Results"
                onclick="openResultModal('${escHtml(o.order_id)}', decodeURIComponent(escape(atob('${rawTestNameB64}'))), '${escHtml(o.patient_id)}')">
          <i class="fas fa-file-medical-alt"></i> Data Entry
        </button>
      </td>
      <td>
        <div class="lo-actions">
          <button class="lo-ab lo-ab-ghost" title="Update Status"
                  onclick="openStatusModal('${escHtml(o.order_id)}','${escHtml(o.status)}')">
            <i class="fas fa-edit"></i>
          </button>
          <button class="lo-ab lo-ab-outline" title="Print Result"
             onclick="printOrderResult('${escHtml(o.order_id)}')">
            <i class="fas fa-print"></i>
          </button>
        </div>
      </td>
    </tr>`;
  }).join('');
}

// ── Quick filters ────────────────────────────────────────────
function quickFilter(filter, el) {
  document.querySelectorAll('.lo-chip').forEach(c => c.classList.remove('active'));
  el.classList.add('active');
  const statusEl   = document.getElementById('filter-status');
  const priorityEl = document.getElementById('filter-priority');
  statusEl.value   = '';
  priorityEl.value = '';
  if (filter === 'pending')   statusEl.value   = 'Ordered';
  if (filter === 'completed') statusEl.value   = 'Completed';
  if (filter === 'urgent')    priorityEl.value = 'Urgent';
  loadOrders();
}

function resetFilters() {
  document.getElementById('filter-date').value     = new Date().toISOString().slice(0,10);
  document.getElementById('filter-status').value   = '';
  document.getElementById('filter-priority').value = '';
  document.getElementById('filter-search').value   = '';
  quickFilter('today', document.querySelector('[data-filter="today"]'));
}

// ── Create Order Modal & UI State ─────────────────────────────
let orderMode = 'inpatient';
let selectedTests = [];
let availableTests = [];

function setOrderMode(mode) {
  orderMode = mode;
  if (mode === 'inpatient') {
    document.getElementById('btn-mode-inpatient').className = 'lb lb-primary';
    document.getElementById('btn-mode-walkin').className = 'lb lb-outline';
    document.getElementById('section-inpatient').style.display = 'block';
    document.getElementById('section-walkin').style.display = 'none';
  } else {
    document.getElementById('btn-mode-walkin').className = 'lb lb-primary';
    document.getElementById('btn-mode-inpatient').className = 'lb lb-outline';
    document.getElementById('section-inpatient').style.display = 'none';
    document.getElementById('section-walkin').style.display = 'block';
  }
}

function openCreateModal() {
  loadTestOptions();
  document.getElementById('createModal').classList.add('open');
}
function closeCreateModal() {
  document.getElementById('createModal').classList.remove('open');
  clearPatientSelection();
  selectedTests = [];
  renderTestChips();
  document.getElementById('modal-test-search').value = '';
  document.getElementById('walkin-name').value = '';
  document.getElementById('walkin-age').value = '';
  document.getElementById('walkin-phone').value = '';
  document.getElementById('walkin-doctor').value = '';
}

async function loadTestOptions() {
  try {
    const data = await lisApi('GET', '/api/laboratory/services');
    if (data.success) {
      availableTests = [];
      if (data.data.lab?.length)       data.data.lab.forEach(t => availableTests.push(`${t.test_name} (${t.service_id})`));
      if (data.data.radiology?.length) data.data.radiology.forEach(t => availableTests.push(`${t.billing_name} (${t.service_id})`));
      if (data.data.other?.length)     data.data.other.forEach(t => availableTests.push(`${t.billing_name} (${t.service_id})`));
    }
  } catch(e) {
    console.error('Failed to load test options');
  }
}

// ── Test Multi-Select Logic ────────────────────────────────────
document.getElementById('modal-test-search').addEventListener('input', function() {
  const q = this.value.trim().toLowerCase();
  const res = document.getElementById('test-results');
  if (q.length < 1) { res.style.display = 'none'; return; }
  
  const matches = availableTests.filter(t => t.toLowerCase().includes(q) && (!selectedTests.includes(t)));
  if (!matches.length) {
    res.style.display = 'block';
    res.innerHTML = '<div style="padding:10px 14px;font-size:.78rem;color:var(--txt-mut);">No matches found</div>';
    return;
  }
  
  res.style.display = 'block';
  res.innerHTML = matches.slice(0, 10).map(t => `
    <div onclick="addTest('${escHtml(t).replace(/'/g, "\\'")}')"
         style="padding:8px 14px;cursor:pointer;border-bottom:1px solid var(--bdr);font-size:0.85rem;"
         onmouseover="this.style.background='var(--p-05)'" onmouseout="this.style.background=''">
      ${escHtml(t)}
    </div>
  `).join('');
});

function addTest(testName) {
  if (!selectedTests.includes(testName)) {
    selectedTests.push(testName);
    renderTestChips();
  }
  document.getElementById('modal-test-search').value = '';
  document.getElementById('test-results').style.display = 'none';
}

function removeTest(testName) {
  selectedTests = selectedTests.filter(t => t !== testName);
  renderTestChips();
}

function renderTestChips() {
  const container = document.getElementById('selected-tests-container');
  container.innerHTML = selectedTests.map(t => `
    <span style="background:var(--p-10);color:var(--p);padding:6px 12px;border-radius:20px;font-size:.75rem;font-weight:600;border:1px solid var(--p-20);display:flex;align-items:center;gap:6px;">
      ${escHtml(t)}
      <i class="fas fa-times" style="cursor:pointer;opacity:0.7;" onclick="removeTest('${escHtml(t).replace(/'/g, "\\'")}')"></i>
    </span>
  `).join('');
}

// Hide dropdowns when clicking outside
document.addEventListener('click', function(e) {
  if (!e.target.closest('#modal-test-search') && !e.target.closest('#test-results')) {
    document.getElementById('test-results').style.display = 'none';
  }
  if (!e.target.closest('#modal-patient-search') && !e.target.closest('#patient-results')) {
    document.getElementById('patient-results').style.display = 'none';
  }
});

// ── Patient Live Search ──────────────────────────────────────
let patientSearchTimer;
document.getElementById('modal-patient-search').addEventListener('input', function() {
  clearTimeout(patientSearchTimer);
  const q = this.value.trim();
  if (q.length < 2) { document.getElementById('patient-results').style.display = 'none'; return; }
  patientSearchTimer = setTimeout(() => searchPatients(q), 350);
});

async function searchPatients(q) {
  const res = document.getElementById('patient-results');
  res.style.display = 'block';
  res.innerHTML = '<div style="padding:10px 14px;font-size:.78rem;color:var(--txt-mut);">Searching...</div>';
  try {
    const data = await lisApi('GET', `/api/patients?search=${encodeURIComponent(q)}`);
    const patients = data.data?.data || data.data || data.patients || (Array.isArray(data) ? data : []);
    if (!patients.length) {
      res.innerHTML = '<div style="padding:10px 14px;font-size:.78rem;color:var(--txt-mut);">No patients found</div>';
      return;
    }
    // We pass object as JSON string safely
    res.innerHTML = patients.slice(0,8).map(p => {
       const jStr = encodeURIComponent(JSON.stringify(p));
       return `
      <div onclick="selectPatient('${jStr}')"
           style="padding:10px 14px;cursor:pointer;border-bottom:1px solid var(--bdr);transition:background .15s;"
           onmouseover="this.style.background='var(--p-05)'" onmouseout="this.style.background=''">
        <div style="font-weight:700;font-size:.82rem;color:var(--txt);">${escHtml((p.first_name||'') + ' ' + (p.last_name||''))}</div>
        <div style="font-size:.68rem;color:var(--txt-mut);">${escHtml(p.patient_id)} &bull; ${p.age||'?'}y &bull; ${p.sex||'?'} &bull; ${escHtml(p.phone||'')}</div>
      </div>`;
    }).join('');
  } catch(e) {
    res.innerHTML = '<div style="padding:10px 14px;font-size:.78rem;color:#ef4444;">Error searching patients</div>';
  }
}

function selectPatient(jsonStr) {
  const p = JSON.parse(decodeURIComponent(jsonStr));
  document.getElementById('modal-patient-id').value = p.patient_id;
  document.getElementById('modal-doctor-id').value = p.doctor_id || '';
  document.getElementById('modal-patient-search').value = '';
  document.getElementById('patient-results').style.display = 'none';
  
  document.getElementById('sp-name').textContent = (p.first_name||'') + ' ' + (p.last_name||'');
  document.getElementById('sp-meta').textContent = `${p.patient_id} • ${p.age||'?'}y • ${p.sex||'?'}`;
  document.getElementById('sp-phone').textContent = p.phone || 'N/A';
  // Use generic 'Hospital Doctor' if not available in patients response
  document.getElementById('sp-ref').textContent = p.doctor_name || 'Hospital Staff'; 
  
  document.querySelector('.lis-search-wrap').style.display = 'none';
  document.getElementById('selected-patient-profile').style.display = 'block';
}

function clearPatientSelection() {
  document.getElementById('modal-patient-id').value = '';
  document.getElementById('modal-patient-search').value = '';
  document.getElementById('patient-results').style.display = 'none';
  document.querySelector('.lis-search-wrap').style.display = 'flex';
  document.getElementById('selected-patient-profile').style.display = 'none';
}

// ── Submit Order ──────────────────────────────────────────────
async function createOrder() {
  const btn = document.getElementById('createOrderBtn');
  btn.disabled = true;
  btn.innerHTML = '<div class="lis-spinner"></div> Sending...';
  
  try {
    let patientId = '';
    let notes = document.getElementById('modal-notes').value.trim();
    
    // 1. Process Patient based on mode
    if (orderMode === 'inpatient') {
      patientId = document.getElementById('modal-patient-id').value.trim();
      if (!patientId) { lisToast('Please select a patient', 'warning'); btn.disabled=false; btn.innerHTML='<i class="fas fa-paper-plane"></i> Send Order'; return; }
    } else {
      // Walkin Patient (No database registration)
      const name = document.getElementById('walkin-name').value.trim();
      const age = document.getElementById('walkin-age').value.trim();
      const phone = document.getElementById('walkin-phone').value.trim();
      const refDoctor = document.getElementById('walkin-doctor').value.trim();
      
      if (!name || !age || !phone) {
        lisToast('Please fill Name, Age and Phone for Walk-in', 'warning');
        btn.disabled=false; btn.innerHTML='<i class="fas fa-paper-plane"></i> Send Order'; return;
      }
      
      // Use a temporary ID for Walk-in orders
      patientId = 'WLK-' + Date.now().toString().slice(-6);
    }

    if (selectedTests.length === 0) { 
       lisToast('Please select at least one test', 'warning'); 
       btn.disabled=false; btn.innerHTML='<i class="fas fa-paper-plane"></i> Send Order'; 
       return; 
    }

    const priority = document.getElementById('modal-priority').value;
    
    // For walk-in, pack the patient details into the patient_type field (max 150 chars)
    let patientType = 'In-patient';
    let docId = null;
    
    if (orderMode === 'walkin') {
      const wName = document.getElementById('walkin-name').value.trim().replace(/\|/g, '');
      const wAge = document.getElementById('walkin-age').value.trim().replace(/\|/g, '');
      const wPhone = document.getElementById('walkin-phone').value.trim().replace(/\|/g, '');
      patientType = `Walkin:${wName}|${wAge}|${wPhone}`;
      docId = document.getElementById('walkin-doctor').value.trim();
    } else {
      const storedDocId = document.getElementById('modal-doctor-id').value;
      if (storedDocId) docId = storedDocId;
    }
    
    const oData = await lisApi('POST', '/api/laboratory/ipd-orders', {
      patient_id: patientId,
      doctor_id:  docId, 
      test_name:  JSON.stringify(selectedTests),
      priority:   priority,
      notes:      notes,
      patient_type: patientType
    });

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Order';

    if (oData.success || oData.order_id) {
      lisToast('Successfully created lab order', 'success');
      closeCreateModal();
      loadOrders();
    } else {
      lisToast('Failed to create orders', 'error');
    }
  } catch(e) {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Order';
    lisToast(e.message || 'Error occurred while creating order', 'error');
  }
}
// ── Status Modal ─────────────────────────────────────────────
function openStatusModal(orderId, currentStatus) {
  activeOrderId = orderId;
  document.getElementById('status-order-id').textContent = orderId;
  document.getElementById('status-select').value         = currentStatus;
  document.getElementById('statusModal').classList.add('open');
}
function closeStatusModal() {
  document.getElementById('statusModal').classList.remove('open');
  activeOrderId = null;
}

async function saveStatus() {
  if (!activeOrderId) return;
  const status = document.getElementById('status-select').value;
  try {
    const data = await lisApi('PUT', `/api/laboratory/ipd-orders/${encodeURIComponent(activeOrderId)}/status`, { status });
    if (data.success) {
      lisToast('Status updated successfully', 'success');
      closeStatusModal();
      loadOrders();
    } else {
      lisToast(data.message || 'Failed to update status', 'error');
    }
  } catch(e) {
    lisToast('Network error', 'error');
  }
}

// ── Helpers ──────────────────────────────────────────────────
function escHtml(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/\n/g,' ').replace(/\r/g,'');
}

// Close modals on overlay click
document.querySelectorAll('.lis-modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => {
    if (e.target === overlay) overlay.classList.remove('open');
  });
});

// Keyboard shortcut N = new order
document.addEventListener('keydown', e => {
  if (e.key === 'n' && !e.ctrlKey && !e.metaKey &&
      document.activeElement.tagName !== 'INPUT' &&
      document.activeElement.tagName !== 'TEXTAREA') {
    openCreateModal();
  }
  if (e.key === 'Escape') {
    document.querySelectorAll('.lis-modal-overlay.open').forEach(m => m.classList.remove('open'));
  }
});

// Auto-polling for new tests every 15 seconds
setInterval(() => {
  // Don't poll if a modal is open to prevent UI disruption
  if (document.querySelector('.lis-modal-overlay.open')) return;
  loadOrders(true);
}, 15000);

// Initial load
loadOrders();

// ══════════════════════════════════════════════════════════════
//  RESULT ENTRY MODAL
// ══════════════════════════════════════════════════════════════
let activeResultOrderId = null;
let rmAttachments = [];          // { dataUrl, blob, name, source }
let cameraStream  = null;

// ── Load all service tests (lab + radiology + other) ─────────
async function loadAllServiceTests() {
  if (allServiceTests.length) return;
  try {
    const data = await lisApi('GET', '/api/laboratory/services');
    if (data.success) {
      (data.data.lab       || []).forEach(t => allServiceTests.push({ name: `${t.test_name} (${t.service_id})`,    cat: 'lab', id: t.service_id }));
      (data.data.radiology || []).forEach(t => allServiceTests.push({ name: `${t.billing_name} (${t.service_id})`, cat: 'rad', id: t.service_id }));
      (data.data.other     || []).forEach(t => allServiceTests.push({ name: `${t.billing_name} (${t.service_id})`, cat: 'oth', id: t.service_id }));
    }
  } catch(e) { /* silent */ }
}

// ── Test search dropdown ──────────────────────────────────────
function searchTestServices(q) {
  const dd = document.getElementById('test-search-dd');
  
  const ql = (q || '').trim().toLowerCase();
  
  let hits = [];
  if (ql) {
    hits = allServiceTests.filter(t => t.name.toLowerCase().includes(ql)).slice(0, 30);
  } else {
    hits = allServiceTests.slice(0, 60); // Show top 60 tests when clicking empty search
  }

  if (!hits.length && !ql) { dd.style.display = 'none'; return; }
  
  let html = '';
  if (ql) {
    html += `<div class="test-dd-item" onclick="selectSearchTest('${escHtml(q.trim())}')">
      <span class="test-dd-badge oth" style="background:var(--p);color:white;border-color:var(--p-dk);">Custom</span>
      <strong>Use manual test: "${escHtml(q.trim())}"</strong>
    </div>`;
  }
  
  if (hits.length) {
    const catLabels = { lab: 'Lab Services', rad: 'Radiology', oth: 'Other Services' };
    const badgeCls  = { lab: 'lab', rad: 'rad', oth: 'oth' };
    ['lab','rad','oth'].forEach(cat => {
      const catHits = hits.filter(h => h.cat === cat);
      if (!catHits.length) return;
      html += `<div class="test-dd-cat">${catLabels[cat]}</div>`;
      catHits.forEach(h => {
        html += `<div class="test-dd-item" onclick="selectSearchTest('${escHtml(h.name)}')"><span class="test-dd-badge ${badgeCls[h.cat]}">${catLabels[cat]}</span>${escHtml(h.name)}</div>`;
      });
    });
  }
  
  dd.innerHTML = html;
  dd.style.display = 'block';
}

function selectSearchTest(name) {
  document.getElementById('test-search-input').value = ''; // clear input after selection
  document.getElementById('test-search-dd').style.display = 'none';
  // We no longer change result-test-name, to allow appending extra tests to the primary order
  
  // Find category and serviceId
  const match = allServiceTests.find(st => st.name === name);
  const cat = match ? match.cat : 'lab';
  const serviceId = match ? match.id : null;
  
  document.getElementById('template-select').value = '';
  
  if (serviceId && cat === 'lab') {
    lisApi('GET', '/api/laboratory/services/parameters/' + encodeURIComponent(serviceId)).then(data => {
      if (data.success && data.data && data.data.length > 0) {
        data.data.forEach(p => {
          addResultRow(p.parameter_name, p.unit, p.normal_range, cat);
        });
      } else {
        addResultRow(name, '', '', cat);
      }
    }).catch(e => {
      addResultRow(name, '', '', cat);
    });
  } else {
    addResultRow(name, '', '', cat);
  }
}

// Close test search dd on outside click
document.addEventListener('click', e => {
  if (!e.target.closest('.test-search-wrap')) {
    const dd = document.getElementById('test-search-dd');
    if (dd) dd.style.display = 'none';
  }
});

// ── Patient Mode Toggle ───────────────────────────────────────
function switchPatientMode(mode) {
  const isWalkin = mode === 'walkin';
  document.getElementById('btn-walkin').classList.toggle('active', isWalkin);
  document.getElementById('btn-registered').classList.toggle('active', !isWalkin);
  document.getElementById('walkin-form').style.display     = isWalkin ? 'block' : 'none';
  document.getElementById('registered-form').style.display = isWalkin ? 'none'  : 'block';
}

// ── Registered patient search (inside result modal) ───────────
let rmPatientTimer;
function rmSearchPatient(q) {
  const dd = document.getElementById('rm-patient-dd');
  clearTimeout(rmPatientTimer);
  if (!q || q.length < 2) { dd.style.display = 'none'; return; }
  rmPatientTimer = setTimeout(async () => {
    dd.style.display = 'block';
    dd.innerHTML = '<div style="padding:10px 14px;font-size:.78rem;color:var(--txt-mut);">Searching...</div>';
    try {
      const data = await lisApi('GET', `/api/patients?search=${encodeURIComponent(q)}`);
      const patients = data.data || data.patients || (Array.isArray(data) ? data : []);
      if (!patients.length) { dd.innerHTML = '<div style="padding:10px 14px;font-size:.78rem;color:var(--txt-mut);">No patients found</div>'; return; }
      dd.innerHTML = patients.slice(0,8).map(p => {
        const name = `${p.first_name||''} ${p.last_name||''}`.trim();
        return `<div style="padding:10px 14px;cursor:pointer;border-bottom:1px solid var(--bdr);transition:background .15s;" onmouseover="this.style.background='var(--p-05)'" onmouseout="this.style.background=''" onclick="selectRmPatient('${escHtml(p.patient_id)}','${escHtml(name)}','${p.age||''}','${escHtml(p.sex||'')}','${escHtml(p.phone||'')}')">
          <div style="font-weight:700;font-size:.82rem;color:var(--txt);">${escHtml(name)}</div>
          <div style="font-size:.68rem;color:var(--txt-mut);">${escHtml(p.patient_id)} &bull; ${p.age||'?'}y &bull; ${p.sex||'?'} &bull; ${escHtml(p.phone||'')}</div>
        </div>`;
      }).join('');
    } catch(e) {
      dd.innerHTML = '<div style="padding:10px 14px;font-size:.78rem;color:#ef4444;">Error</div>';
    }
  }, 300);
}

function selectRmPatient(id, name, age, sex, phone) {
  document.getElementById('rm-patient-id').value          = id;
  document.getElementById('rm-patient-dd').style.display  = 'none';
  document.getElementById('rm-patient-search').value      = name;
  document.getElementById('rm-patient-avatar').textContent = name.split(' ').map(w=>w[0]||'').join('').slice(0,2).toUpperCase();
  document.getElementById('rm-patient-name-disp').textContent = name;
  document.getElementById('rm-patient-meta').textContent  = `${id} · ${age||'?'}y · ${sex||'?'} · ${phone||'N/A'}`;
  document.getElementById('rm-patient-card').style.display = 'block';
}

function clearRmPatient() {
  document.getElementById('rm-patient-id').value           = '';
  document.getElementById('rm-patient-search').value       = '';
  document.getElementById('rm-patient-card').style.display = 'none';
}

// ── Attachment Handling ───────────────────────────────────────
function handleAttachFile(input, source) {
  const files = Array.from(input.files);
  files.forEach(file => {
    const reader = new FileReader();
    reader.onload = e => {
      const dataUrl = e.target.result;
      rmAttachments.push({ dataUrl, blob: file, name: file.name, source });
      renderAttachGrid();
    };
    reader.readAsDataURL(file);
  });
  input.value = '';
}

function renderAttachGrid() {
  const grid  = document.getElementById('att-grid');
  const empty = document.getElementById('att-empty');
  if (!rmAttachments.length) {
    grid.innerHTML  = '';
    empty.style.display = 'block';
    return;
  }
  empty.style.display = 'none';
  grid.innerHTML = rmAttachments.map((a, i) => {
    const isImg = a.dataUrl.startsWith('data:image');
    const thumb = isImg
      ? `<img class="att-thumb" src="${a.dataUrl}" title="${escHtml(a.name)}" onclick="previewAttach(${i})">`
      : `<div class="att-thumb" style="display:flex;flex-direction:column;align-items:center;justify-content:center;background:var(--s);color:var(--p);font-size:.6rem;font-weight:700;padding:4px;text-align:center;" title="${escHtml(a.name)}"><i class="fas fa-file-pdf" style="font-size:1.5rem;margin-bottom:4px;"></i>${escHtml(a.name.substring(0,8))}...</div>`;
    return `<div class="att-wrap">${thumb}<button class="att-del" onclick="removeAttach(${i})" title="Remove"><i class="fas fa-times"></i></button></div>`;
  }).join('');
}

function removeAttach(i) {
  rmAttachments.splice(i, 1);
  renderAttachGrid();
}

function previewAttach(i) {
  const a = rmAttachments[i];
  if (!a) return;
  const w = window.open();
  w.document.write(`<img src="${a.dataUrl}" style="max-width:100%;height:auto;">`);
}

// ── Camera ────────────────────────────────────────────────────
async function openCameraModal() {
  try {
    cameraStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
    document.getElementById('cameraVideo').srcObject = cameraStream;
    document.getElementById('cameraModal').classList.add('show');
  } catch(e) {
    lisToast('Camera access denied or not available', 'error');
  }
}

function closeCameraModal() {
  if (cameraStream) { cameraStream.getTracks().forEach(t => t.stop()); cameraStream = null; }
  document.getElementById('cameraModal').classList.remove('show');
}

function capturePhoto() {
  const video  = document.getElementById('cameraVideo');
  const canvas = document.getElementById('cameraCanvas');
  canvas.width  = video.videoWidth;
  canvas.height = video.videoHeight;
  canvas.getContext('2d').drawImage(video, 0, 0);
  const dataUrl = canvas.toDataURL('image/jpeg', .85);
  canvas.toBlob(blob => {
    rmAttachments.push({ dataUrl, blob, name: `capture_${Date.now()}.jpg`, source: 'camera' });
    renderAttachGrid();
  }, 'image/jpeg', .85);
  closeCameraModal();
  lisToast('Photo captured!', 'success');
}

// ── Smart templates ───────────────────────────────────────────
// Hardcoded templates removed per user request

let previousPatientResults = {};

function getNormalRange(param, ageStr, sex) {
    if (!ageStr) return param.normal_range || '';
    
    let ageInYears = 0;
    let ageInDays = 0;
    const val = parseFloat(ageStr);
    if (isNaN(val)) return param.normal_range || '';
    
    const str = ageStr.toString().toLowerCase();
    if (str.includes('d')) {
        ageInDays = val;
        ageInYears = val / 365;
    } else if (str.includes('m')) {
        ageInDays = val * 30;
        ageInYears = val / 12;
    } else {
        ageInYears = val;
        ageInDays = val * 365;
    }

    let range = '';
    const s = (sex || '').toLowerCase();

    if (ageInDays <= 28) range = param.normal_range_newborn;
    else if (ageInDays > 28 && ageInYears <= 1) range = param["normal_range_Infant(29 days 12 months)"] || param["normal_range_Infant(29 days-12 months)"];
    else if (ageInYears > 1 && ageInYears <= 3) range = param["normal_range_toddler(1 & 3 years)"] || param["normal_range_toddler(1-3 years)"];
    else if (ageInYears > 3 && ageInYears <= 5) range = param["normal_range_preschool_child(4 & 5 years)"] || param["normal_range_preschool_child(4-5 years)"];
    else if (ageInYears > 5 && ageInYears <= 12) range = param["normal_range_school_child(6 & 12 years)"] || param["normal_range_school_child(6-12 years)"];
    else if (ageInYears > 12 && ageInYears <= 17) range = param["normal_range_adolescent(13 & 17 years)"] || param["normal_range_adolescent(13-17 years)"];
    else if (ageInYears >= 60 && ageInYears <= 74) range = param["normal_range_elderly(60 & 74 years)"] || param["normal_range_elderly(60-74 years)"];
    else if (ageInYears >= 75) range = param["normal_range_senior_elderly(75+ years)"];
    
    if (!range) {
        if (s.startsWith('m') && param.normal_range_male) range = param.normal_range_male;
        else if (s.startsWith('f') && param.normal_range_female) range = param.normal_range_female;
        else if (ageInYears >= 18 && ageInYears <= 59 && param["normal_range_adult(18 & 59 years)"]) range = param["normal_range_adult(18 & 59 years)"];
        else if (ageInYears >= 18 && ageInYears <= 59 && param["normal_range_adult(18-59 years)"]) range = param["normal_range_adult(18-59 years)"];
        else range = param.normal_range;
    }

    return range || param.normal_range || '';
}

async function openResultModal(orderId, rawTestName, patientId = null) {
  await loadAllServiceTests(); // Load 3-table data if not loaded
  activeResultOrderId = orderId;
  rmAttachments = [];
  document.getElementById('result-order-id').textContent  = orderId;

  const targetOrder = allOrders.find(o => o.order_id === orderId);
  const pAge = targetOrder ? targetOrder.age : '';
  const pSex = targetOrder ? targetOrder.sex : '';


  // Attempt to parse JSON array, otherwise fallback to simple string handling
  let testArray = [];
  let displayTestName = rawTestName;
  try {
    const parsed = JSON.parse(rawTestName);
    if (Array.isArray(parsed)) {
      testArray = parsed;
      displayTestName = testArray.join(', ');
    } else {
      testArray = rawTestName.split('|||');
    }
  } catch(e) {
    testArray = rawTestName.split('|||');
  }
  if (testArray.length === 0) testArray.push('');
  
  displayTestName = testArray.join(', ');

  document.getElementById('result-test-name').textContent = displayTestName;

  // Reset attachment grid
  renderAttachGrid();

  // Patient form removed

  // Reset test search
  document.getElementById('test-search-input').value = '';
  document.getElementById('test-search-dd').style.display = 'none';
  document.getElementById('template-select').value = '';
  document.getElementById('result-params-tbody').innerHTML = '';

  previousPatientResults = {};
  if (patientId) {
    try {
      const data = await lisApi('GET', '/api/laboratory/patients/' + encodeURIComponent(patientId) + '/previous-results');
      if (data.success && data.data) {
        previousPatientResults = data.data;
      }
    } catch(e) {
      console.error("Failed to fetch previous results", e);
    }
  }

  testArray.forEach(async tName => {
    const match = allServiceTests.find(st => st.name.toLowerCase() === tName.toLowerCase());
    const cat = match ? match.cat : 'lab';
    let queryKey = tName;
    if (match && match.id) {
        queryKey = match.id;
    } else {
        const matchId = tName.match(/\(([^)]+)\)$/);
        if (matchId) queryKey = matchId[1];
    }

    if (cat === 'lab') {
        try {
          const data = await lisApi('GET', '/api/laboratory/services/parameters/' + encodeURIComponent(queryKey));
          if (data.success && data.data && data.data.length > 0) {
            data.data.forEach(p => {
              const bestRange = getNormalRange(p, pAge, pSex);
              addResultRow(p.parameter_name, p.unit, bestRange, cat);
            });
          } else {
            if(typeof showCenterMessage === 'function') {
                showCenterMessage(false, 'Missing Parameters', 'Test parameters are not available for ' + tName + '.');
            } else {
                lisToast('Test parameters are not available for ' + tName + '.', 'warning');
            }
          }
        } catch(e) {
            if(typeof showCenterMessage === 'function') {
                showCenterMessage(false, 'Missing Parameters', 'Test parameters are not available for ' + tName + '.');
            } else {
                lisToast('Test parameters are not available for ' + tName + '.', 'warning');
            }
        }
    } else {
      addResultRow(tName, '', '', cat);
    }
  });

  document.getElementById('resultModal').classList.add('open');
}

function closeResultModal() {
  document.getElementById('resultModal').classList.remove('open');
  activeResultOrderId = null;
}


function addResultRow(name = '', unit = '', range = '', category = 'lab') {
  const tbody = document.getElementById('result-params-tbody');
  
  // Prevent duplicate parameters
  if (name) {
    const existing = Array.from(tbody.querySelectorAll('input[name="param_name"]')).map(inp => inp.value.trim().toLowerCase());
    if (existing.includes(name.trim().toLowerCase())) {
      return;
    }
  }

  const tr    = document.createElement('tr');
  tr.className = 'row-anim';
  
  // Previous Result
  let prevResultText = 'N/A';
  let prevResultVal = null;
  if (name && previousPatientResults[name.trim().toLowerCase()] !== undefined) {
    prevResultText = previousPatientResults[name.trim().toLowerCase()];
    prevResultVal = parseFloat(prevResultText);
  }

  if (category === 'rad' || category === 'oth') {
    const label = category === 'rad' ? 'Radiology Report' : 'Service Notes';
    const icon = category === 'rad' ? 'fa-x-ray' : 'fa-clipboard-list';
    tr.innerHTML = `
      <td colspan="5" style="padding:16px;background:var(--s);border-bottom:1px solid var(--bdr);">
        <div style="font-size:.85rem;font-weight:700;color:var(--p);margin-bottom:8px;display:flex;align-items:center;justify-content:space-between;">
          <span><i class="fas ${icon}" style="margin-right:6px;"></i>${escHtml(name)} (${label})</span>
          <div style="display:flex;align-items:center;gap:10px;">
            <button type="button" onclick="document.getElementById('result-report-file').click()" style="background:var(--p-10);color:var(--p);border:1px solid var(--p-30);padding:4px 10px;border-radius:var(--r-sm);font-size:.72rem;font-weight:700;cursor:pointer;transition:all .2s;" onmouseover="this.style.background='var(--p)';this.style.color='white';" onmouseout="this.style.background='var(--p-10)';this.style.color='var(--p)';"><i class="fas fa-paperclip" style="margin-right:4px;"></i> Attach File</button>
            <button type="button" class="rm-del-btn" onclick="this.closest('tr').remove()"><i class="fas fa-trash-alt"></i></button>
          </div>
        </div>
        <input type="hidden" name="param_name" value="${escHtml(name)}">
        <textarea class="rm-pinput" name="param_value" placeholder="Type your ${label.toLowerCase()} or findings here..." style="width:100%;height:80px;resize:vertical;padding:12px;"></textarea>
        <input type="hidden" name="param_unit" value="">
        <input type="hidden" name="param_range" value="">
      </td>`;
    tbody.appendChild(tr);
    return;
  }

  // Live value check
  const checkVal = (input) => {
    const v = parseFloat(input.value);
    
    // Check trend
    const trendIconContainer = input.parentElement.querySelector('.trend-indicator');
    if (trendIconContainer) {
      trendIconContainer.innerHTML = '';
      if (!isNaN(v) && prevResultVal !== null && !isNaN(prevResultVal)) {
        if (v > prevResultVal) {
          trendIconContainer.innerHTML = '<i class="fas fa-arrow-up" style="color:#e74c3c;" title="Increased from previous"></i>';
        } else if (v < prevResultVal) {
          trendIconContainer.innerHTML = '<i class="fas fa-arrow-down" style="color:#3498db;" title="Decreased from previous"></i>';
        } else {
          trendIconContainer.innerHTML = '<i class="fas fa-minus" style="color:#95a5a6;" title="Unchanged"></i>';
        }
      }
    }

    if (isNaN(v) || !range) { input.className = 'rm-pinput p-result'; return; }
    const parts = range.replace(/[<>]/g,'').trim().split('-').map(p => parseFloat(p.trim()));
    if (parts.length === 2 && !isNaN(parts[0]) && !isNaN(parts[1])) {
      if (v < parts[0])      input.className = 'rm-pinput p-result p-low';
      else if (v > parts[1]) input.className = 'rm-pinput p-result p-high';
      else                   input.className = 'rm-pinput p-result p-normal';
    }
  };

  tr.innerHTML = `
    <td><input type="text" class="rm-pinput" placeholder="Parameter name" name="param_name" value="${escHtml(name)}"></td>
    <td><div style="padding: 8px; background: var(--bga); border-radius: 4px; font-weight: 600; color: var(--txt); text-align: center;">${escHtml(prevResultText)}</div></td>
    <td style="position:relative;">
      <input type="text" class="rm-pinput p-result" placeholder="Value" name="param_value" style="padding-right:25px;">
      <span class="trend-indicator" style="position:absolute; right:8px; top:50%; transform:translateY(-50%);"></span>
    </td>
    <td><input type="text" class="rm-pinput p-unit" placeholder="Unit" name="param_unit" value="${escHtml(unit)}"></td>
    <td><input type="text" class="rm-pinput p-range" placeholder="Range" name="param_range" value="${escHtml(range)}"></td>
    <td style="text-align:center;">
      <button type="button" class="rm-del-btn" onclick="this.closest('tr').remove()"><i class="fas fa-trash-alt"></i></button>
    </td>`;
  const valInp = tr.querySelector('[name="param_value"]');
  if (valInp) {
    valInp.addEventListener('input', function() { checkVal(this); });
    valInp.addEventListener('keydown', function(e) {
      if ((e.key === 'Tab' && !e.shiftKey) || e.key === 'Enter') {
        e.preventDefault();
        const rows = Array.from(document.querySelectorAll('#result-params-tbody tr'));
        const idx = rows.indexOf(this.closest('tr'));
        if (idx >= 0 && idx < rows.length - 1) {
          const next = rows[idx + 1].querySelector('[name="param_value"]');
          if (next) next.focus();
        }
      } else if (e.key === 'Tab' && e.shiftKey) {
        e.preventDefault();
        const rows = Array.from(document.querySelectorAll('#result-params-tbody tr'));
        const idx = rows.indexOf(this.closest('tr'));
        if (idx > 0) {
          const prev = rows[idx - 1].querySelector('[name="param_value"]');
          if (prev) prev.focus();
        }
      }
    });
  }
  tbody.appendChild(tr);
}

async function saveResult() {
  if (!activeResultOrderId) return;

  const rows   = document.querySelectorAll('#result-params-tbody tr');
  const params = [];
  rows.forEach(tr => {
    const name = tr.querySelector('[name="param_name"]').value.trim();
    if (name) {
      params.push({
        name,
        value: tr.querySelector('[name="param_value"]').value.trim(),
        unit:  tr.querySelector('[name="param_unit"]').value.trim(),
        range: tr.querySelector('[name="param_range"]').value.trim(),
      });
    }
  });

  if (params.length === 0 && rmAttachments.length === 0) {
    lisToast('Please enter at least one parameter or add an attachment', 'warning');
    return;
  }

  // Patient info panel removed, only send test name
  let patientInfo = {
    test_name: document.getElementById('test-search-input') ? document.getElementById('test-search-input').value.trim() : ''
  };

  const btn = document.getElementById('saveResultBtn');
  btn.disabled  = true;
  btn.innerHTML = '<div class="lis-spinner"></div> Saving...';

  try {
    const formData = new FormData();
    formData.append('result_data', JSON.stringify(params));
    formData.append('patient_info', JSON.stringify(patientInfo));
    
    // attach all files
    rmAttachments.forEach((a, i) => {
      // The backend LaboratoryController expects $_FILES['report_file']
      const key = (i === 0) ? 'report_file' : `report_file_${i}`;
      formData.append(key, a.blob, a.name);
    });
    // legacy key for compatibility
    if (rmAttachments.length) formData.append('report_file', rmAttachments[0].blob, rmAttachments[0].name);

    const data = await lisApi('POST', `/api/laboratory/ipd-orders/${encodeURIComponent(activeResultOrderId)}/result`, formData);

    btn.disabled  = false;
    btn.innerHTML = '<i class="fas fa-check-circle"></i> Submit Final Results';

    if (data.success) {
      lisToast('Results saved successfully!', 'success');
      closeResultModal();
      loadOrders();
    } else {
      lisToast(data.message || 'Error saving results', 'error');
    }
  } catch(e) {
    btn.disabled  = false;
    btn.innerHTML = '<i class="fas fa-check-circle"></i> Submit Final Results';
    lisToast(e.message || 'Failed to save results', 'error');
  }
}
function printOrderResult(orderId) {
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = 'print_result.php';
  
  const input = document.createElement('input');
  input.type = 'hidden';
  input.name = 'order_id';
  input.value = orderId;
  
  form.appendChild(input);
  document.body.appendChild(form);
  form.submit();
}

