/* ============================================================
   NEXUS VENDOR PORTAL — Advanced Frontend Engine
   ============================================================ */

let currentMode = 'list';
let globalIndents = [];
let selectedIds = new Set();
let formState = {};

// ── Toast System ──────────────────────────────────────────────
function showToast(type, title, msg, duration = 3500) {
    const icons = { success: 'fa-check', error: 'fa-times', info: 'fa-info' };
    const t = document.createElement('div');
    t.className = `nexus-toast`;
    t.innerHTML = `
        <div class="toast-icon ${type}"><i class="fas ${icons[type] || 'fa-info'}"></i></div>
        <div><div class="toast-title">${title}</div><div class="toast-msg">${msg}</div></div>
        <i class="fas fa-times" style="margin-left:auto;color:#9ca3af;cursor:pointer;font-size:0.8rem;" onclick="this.parentElement.remove()"></i>`;
    document.body.appendChild(t);
    setTimeout(() => { t.classList.add('out'); setTimeout(() => t.remove(), 400); }, duration);
}

// ── Counter Animation ─────────────────────────────────────────
function animateCounter(el, target, prefix = '', suffix = '', decimals = 0) {
    const duration = 900;
    const start = performance.now();
    const startVal = 0;
    const update = (now) => {
        const elapsed = now - start;
        const progress = Math.min(elapsed / duration, 1);
        const ease = 1 - Math.pow(1 - progress, 3);
        const current = startVal + (target - startVal) * ease;
        el.textContent = prefix + current.toLocaleString('en-IN', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }) + suffix;
        if (progress < 1) requestAnimationFrame(update);
    };
    requestAnimationFrame(update);
}

// ── Search ────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadIndents();
    initSearch();
    initRipple();
});

function initSearch() {
    const gs = document.getElementById('nexusSearch');
    const ls = document.getElementById('nexusLocalSearch');
    const search = (q) => {
        const query = q.toLowerCase();
        const filtered = globalIndents.filter(i =>
            (i.item_name || '').toLowerCase().includes(query) ||
            (i.indent_no || '').toLowerCase().includes(query)
        );
        renderIndents(filtered);
    };
    if (gs) gs.addEventListener('input', e => { if (ls) ls.value = e.target.value; search(e.target.value); });
    if (ls) ls.addEventListener('input', e => { if (gs) gs.value = e.target.value; search(e.target.value); });
    document.addEventListener('keydown', e => {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') { e.preventDefault(); (ls || gs)?.focus(); }
    });
}

// ── Ripple Effect ─────────────────────────────────────────────
function initRipple() {
    document.addEventListener('click', e => {
        const btn = e.target.closest('.nexus-btn-submit,.nexus-view-btn');
        if (!btn) return;
        const r = document.createElement('span');
        const rect = btn.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        r.style.cssText = `position:absolute;width:${size}px;height:${size}px;border-radius:50%;background:rgba(255,255,255,0.3);transform:scale(0);animation:ripple 0.5s linear;pointer-events:none;left:${e.clientX-rect.left-size/2}px;top:${e.clientY-rect.top-size/2}px;`;
        if (getComputedStyle(btn).position === 'static') btn.style.position = 'relative';
        btn.appendChild(r);
        setTimeout(() => r.remove(), 600);
    });
    const style = document.createElement('style');
    style.textContent = '@keyframes ripple{to{transform:scale(2.5);opacity:0;}}';
    document.head.appendChild(style);
}

// ── Selection ─────────────────────────────────────────────────
document.addEventListener('change', e => {
    if (e.target.id === 'selectAll') {
        document.querySelectorAll('.row-check').forEach(c => {
            c.checked = e.target.checked;
            e.target.checked ? selectedIds.add(c.value) : selectedIds.delete(c.value);
        });
        updateBulkSummary();
    }
    if (e.target.classList.contains('row-check')) {
        e.target.checked ? selectedIds.add(e.target.value) : selectedIds.delete(e.target.value);
        updateBulkSummary();
    }
});

document.addEventListener('click', e => {
    const card = e.target.closest('.nexus-product-card,.nexus-row');
    if (card && !['INPUT','SELECT','BUTTON','I'].includes(e.target.tagName)) {
        const chk = card.querySelector('.row-check');
        if (chk) {
            chk.checked = !chk.checked;
            chk.checked ? selectedIds.add(chk.value) : selectedIds.delete(chk.value);
            updateBulkSummary();
        }
    }
});

// ── Calc ──────────────────────────────────────────────────────
function saveValue(id, key, val) {
    if (!formState[id]) formState[id] = {};
    formState[id][key] = val;
    calcTotal(id);
}

function calcTotal(id) {
    const qty = parseFloat(document.getElementById(`qty-${id}`)?.value || 0);
    const rate = parseFloat(document.getElementById(`rate-${id}`)?.value || 0);
    const tax = parseFloat(document.getElementById(`tax-${id}`)?.value || 0);
    const subtotal = qty * rate;
    const taxAmt = (subtotal * tax) / 100;
    const total = subtotal + taxAmt;
    const taxEl = document.getElementById(`tax-amt-${id}`);
    const totalEl = document.getElementById(`total-${id}`);
    if (taxEl) taxEl.textContent = `₹ ${taxAmt.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
    if (totalEl) totalEl.textContent = `₹ ${total.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
    updateBulkSummary();
}

function updateBulkSummary() {
    let total = 0; let count = 0;
    globalIndents.forEach(i => {
        const id = i.id.toString();
        const card = document.getElementById(`row-${id}`);
        const chk = card?.querySelector('.row-check');
        if (selectedIds.has(id)) {
            card?.classList.add('selected');
            if (chk) chk.checked = true;
            count++;
            const qty = parseFloat(formState[id]?.qty ?? i.qty ?? 0);
            const rate = parseFloat(formState[id]?.rate ?? 0);
            const tax = parseFloat(formState[id]?.tax ?? 0);
            const sub = qty * rate;
            total += sub + (sub * tax / 100);
        } else {
            card?.classList.remove('selected');
            if (chk) chk.checked = false;
        }
    });
    const cntEl = document.getElementById('selectedCount');
    const totEl = document.getElementById('grandTotal');
    if (cntEl) cntEl.textContent = count;
    if (totEl) {
        totEl.textContent = `₹ ${total.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
    }
    // Animate floating bar
    const bar = document.querySelector('.nexus-floating-bar');
    if (bar) bar.style.transform = count > 0 ? 'translateY(0)' : '';
}

// ── View Switch ───────────────────────────────────────────────
function switchView(mode) {
    currentMode = mode;
    document.getElementById('btn-list-view')?.classList.toggle('active', mode === 'list');
    document.getElementById('btn-grid-view')?.classList.toggle('active', mode === 'grid');
    const header = document.querySelector('.nexus-table-header');
    const container = document.getElementById('indent-container');
    if (mode === 'grid') {
        if (header) header.style.display = 'none';
        container?.classList.add('nexus-grid-view');
    } else {
        if (header) header.style.display = 'grid';
        container?.classList.remove('nexus-grid-view');
    }
    renderIndents(globalIndents);
    updateBulkSummary();
}

// ── Skeleton Loader ───────────────────────────────────────────
function showSkeleton(container) {
    container.innerHTML = Array.from({ length: 5 }, (_, i) => `
        <div class="nexus-row" style="animation-delay:${i * 0.07}s">
            <div class="skeleton" style="height:28px;border-radius:8px;"></div>
            <div class="skeleton" style="height:28px;border-radius:8px;"></div>
            <div class="skeleton" style="height:28px;border-radius:8px;"></div>
            <div class="skeleton" style="height:28px;border-radius:8px;"></div>
            <div class="skeleton" style="height:28px;border-radius:8px;"></div>
            <div class="skeleton" style="height:28px;border-radius:8px;"></div>
            <div class="skeleton" style="height:28px;border-radius:8px;"></div>
            <div class="skeleton" style="height:28px;border-radius:8px;"></div>
        </div>`).join('');
}

// ── Load Indents ──────────────────────────────────────────────
async function loadIndents() {
    const container = document.getElementById('indent-container');
    if (!container) return;
    showSkeleton(container);
    try {
        const res = await fetch('api.php?action=getIndents');
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const result = await res.json();
        if (result.success) {
            globalIndents = result.data || [];
            renderIndents(globalIndents);
            updateStats(globalIndents);
            showToast('success', 'Marketplace Ready', `${globalIndents.length} indent(s) loaded`);
        } else {
            container.innerHTML = emptyState('No Active Indents', result.message || 'No procurement requests assigned to your account.');
            showToast('info', 'No Data', result.message || 'No indents available');
        }
    } catch (err) {
        container.innerHTML = emptyState('Connection Failed', err.message, true);
        showToast('error', 'Connection Error', err.message);
    }
}

function emptyState(title, subtitle, isError = false) {
    return `<div style="text-align:center;padding:80px 0;">
        <div style="width:72px;height:72px;border-radius:20px;background:${isError ? 'rgba(239,68,68,0.08)' : 'rgba(6,182,212,0.08)'};display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:1.8rem;color:${isError ? '#ef4444' : '#06b6d4'};">
            <i class="fas ${isError ? 'fa-exclamation-triangle' : 'fa-inbox'}"></i></div>
        <div style="font-weight:700;font-size:1rem;color:#0f172a;margin-bottom:8px;">${title}</div>
        <div style="font-size:0.82rem;color:#9ca3af;">${subtitle}</div>
    </div>`;
}

// ── Update Stat Cards ─────────────────────────────────────────
function updateStats(indents) {
    const totalEl = document.getElementById('stat-total-indents');
    const pendingEl = document.getElementById('stat-pending');
    const valueEl = document.getElementById('stat-value');
    const productsEl = document.getElementById('stat-products');
    if (totalEl) animateCounter(totalEl, indents.length);
    if (pendingEl) animateCounter(pendingEl, indents.length);
    const products = new Set(indents.map(i => i.product_id)).size;
    if (productsEl) animateCounter(productsEl, products);
    const totalQty = indents.reduce((s, i) => s + parseFloat(i.qty || 0), 0);
    if (valueEl) animateCounter(valueEl, totalQty, '', ' units');
}

// ── Render ────────────────────────────────────────────────────
function renderIndents(indents) {
    const container = document.getElementById('indent-container');
    if (!indents || !indents.length) {
        container.innerHTML = emptyState('No Results', 'Try adjusting your search query.');
        if (typeof window.onAfterRender === 'function') window.onAfterRender([]);
        return;
    }
    if (currentMode === 'list') {
        container.innerHTML = indents.map((i, idx) => renderListRow(i, idx)).join('');
    } else {
        container.innerHTML = indents.map((i, idx) => renderGridCard(i, idx)).join('');
    }
    // Notify page-level hooks (e.g. click handlers) after DOM is ready
    setTimeout(() => {
        if (typeof window.onAfterRender === 'function') window.onAfterRender(indents);
    }, 30);
}
window.renderIndents = renderIndents;

function renderListRow(i, idx) {
    const id = i.id;
    const st = formState[id] || {};
    const qty = st.qty ?? i.qty ?? 1;
    const rate = st.rate ?? '';
    const tax = st.tax ?? 0;
    const expiry = st.expiry ?? '';
    const sub = parseFloat(qty) * parseFloat(rate || 0);
    const taxAmt = sub * parseFloat(tax) / 100;
    const total = sub + taxAmt;
    const isSelected = selectedIds.has(id.toString());
    return `<div id="row-${id}" class="nexus-row ${isSelected ? 'selected' : ''}"
         data-indent="${i.indent_no}" data-product="${i.product_id}" data-item="${i.item_name}"
         style="animation-delay:${idx * 0.04}s">
        <div style="display:flex;align-items:center;gap:10px;">
            <input type="checkbox" class="row-check" value="${id}" ${isSelected ? 'checked' : ''}
                style="accent-color:#06b6d4;width:15px;height:15px;cursor:pointer;">
            <span class="nexus-card-badge">${i.indent_no}</span>
        </div>
        <div>
            <div style="font-weight:700;font-size:0.88rem;color:#0f172a;">${i.item_name}</div>
            <div style="font-size:0.68rem;color:#9ca3af;font-weight:600;margin-top:2px;">SKU-${1000+parseInt(id)}</div>
        </div>
        <input type="number" class="nexus-input" value="${qty}" oninput="saveValue(${id},'qty',this.value)" id="qty-${id}" style="text-align:center;padding:6px 8px;font-size:0.82rem;">
        <input type="number" class="nexus-input" value="${rate}" placeholder="0.00" oninput="saveValue(${id},'rate',this.value)" id="rate-${id}" style="text-align:center;padding:6px 8px;font-size:0.82rem;">
        <input type="number" class="nexus-input" value="${tax}" oninput="saveValue(${id},'tax',this.value)" id="tax-${id}" style="text-align:center;padding:6px 8px;font-size:0.82rem;">
        <div id="tax-amt-${id}" style="font-size:0.8rem;font-weight:700;color:#6b7280;text-align:center;">₹ ${taxAmt.toLocaleString('en-IN',{minimumFractionDigits:2})}</div>
        <input type="date" class="nexus-input" value="${expiry}" onchange="saveValue(${id},'expiry',this.value)" id="expiry-${id}" style="font-size:0.75rem;padding:6px 8px;">
        <div id="total-${id}" style="text-align:right;font-weight:800;color:#06b6d4;font-size:0.92rem;">₹ ${total.toLocaleString('en-IN',{minimumFractionDigits:2})}</div>
    </div>`;
}

function renderGridCard(i, idx) {
    const id = i.id;
    const st = formState[id] || {};
    const qty = st.qty ?? i.qty ?? 1;
    const rate = st.rate ?? '';
    const tax = st.tax ?? 0;
    const expiry = st.expiry ?? '';
    const sub = parseFloat(qty) * parseFloat(rate || 0);
    const taxAmt = sub * parseFloat(tax) / 100;
    const total = sub + taxAmt;
    const isSelected = selectedIds.has(id.toString());
    return `<div id="row-${id}" class="nexus-product-card ${isSelected ? 'selected' : ''}"
         data-indent="${i.indent_no}" data-product="${i.product_id}" data-item="${i.item_name}"
         style="animation-delay:${idx * 0.06}s">
        <div class="nexus-card-header">
            <div>
                <div class="nexus-card-badge" style="margin-bottom:8px;">${i.indent_no}</div>
                <div style="font-weight:800;font-size:1.05rem;color:#0f172a;line-height:1.2;">${i.item_name}</div>
                <div style="font-size:0.7rem;color:#9ca3af;font-weight:600;margin-top:4px;">SKU-${1000+parseInt(id)}</div>
            </div>
            <input type="checkbox" class="row-check" value="${id}" ${isSelected ? 'checked' : ''}
                style="accent-color:#06b6d4;width:18px;height:18px;cursor:pointer;flex-shrink:0;">
        </div>
        <div class="nexus-card-form">
            <div class="nexus-form-field">
                <label><i class="fas fa-boxes" style="margin-right:4px;"></i>Quantity</label>
                <input type="number" class="nexus-input" value="${qty}" oninput="saveValue(${id},'qty',this.value)" id="qty-${id}">
            </div>
            <div class="nexus-form-field">
                <label><i class="fas fa-tag" style="margin-right:4px;"></i>Rate (₹)</label>
                <input type="number" class="nexus-input" value="${rate}" placeholder="0.00" oninput="saveValue(${id},'rate',this.value)" id="rate-${id}">
            </div>
            <div class="nexus-form-field">
                <label><i class="fas fa-percent" style="margin-right:4px;"></i>Tax %</label>
                <input type="number" class="nexus-input" value="${tax}" oninput="saveValue(${id},'tax',this.value)" id="tax-${id}">
            </div>
            <div class="nexus-form-field">
                <label><i class="fas fa-calendar" style="margin-right:4px;"></i>Expiry</label>
                <input type="date" class="nexus-input" value="${expiry}" onchange="saveValue(${id},'expiry',this.value)" id="expiry-${id}" style="font-size:0.75rem;">
            </div>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:flex-end;padding-top:8px;border-top:1px solid rgba(0,0,0,0.05);">
            <div>
                <div style="font-size:0.6rem;font-weight:800;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;">Net Valuation</div>
                <div id="total-${id}" style="font-weight:900;font-size:1.4rem;background:linear-gradient(135deg,#06b6d4,#22d3ee);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;letter-spacing:-0.5px;">₹ ${total.toLocaleString('en-IN',{minimumFractionDigits:2})}</div>
            </div>
            <div id="tax-amt-${id}" style="font-size:0.72rem;font-weight:700;color:#10b981;background:rgba(16,185,129,0.08);padding:4px 10px;border-radius:20px;border:1px solid rgba(16,185,129,0.15);">
                +₹ ${taxAmt.toLocaleString('en-IN',{minimumFractionDigits:2})} tax
            </div>
        </div>
    </div>`;
}

// ── Submit ────────────────────────────────────────────────────
async function submitBulkQuotation() {
    const selected = document.querySelectorAll('.row-check:checked');
    if (!selected.length) {
        showToast('error', 'No Selection', 'Please select at least one indent.');
        return;
    }
    let items = [], valid = true;
    selected.forEach(c => {
        const id = c.value;
        const row = document.getElementById(`row-${id}`);
        const rate = document.getElementById(`rate-${id}`)?.value;
        const qty = document.getElementById(`qty-${id}`)?.value;
        const tax = document.getElementById(`tax-${id}`)?.value;
        const expiry = document.getElementById(`expiry-${id}`)?.value;
        if (!rate || parseFloat(rate) <= 0) valid = false;
        const sub = parseFloat(qty) * parseFloat(rate);
        const taxAmt = (sub * parseFloat(tax)) / 100;
        items.push({
            id, indent_no: row?.dataset.indent, product_id: row?.dataset.product,
            item_name: row?.dataset.item, rate, qty,
            tax_percent: tax, tax_amount: taxAmt,
            total_amount: sub + taxAmt, validity_date: expiry
        });
    });
    if (!valid) { showToast('error', 'Incomplete Data', 'Enter a valid rate for all selected items.'); return; }

    const ok = await Swal.fire({
        title: `Submit ${items.length} Quotation${items.length > 1 ? 's' : ''}?`,
        html: `<p style="color:#6b7280;font-size:0.9rem;">This action will submit your pricing to the procurement team.</p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-paper-plane"></i> Submit',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#06b6d4',
        customClass: { popup: 'swal-glass' }
    });
    if (!ok.isConfirmed) return;

    Swal.fire({ title: 'Submitting...', html: '<p style="color:#6b7280;">Processing your quotations securely.</p>', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    try {
        const res = await fetch('api.php?action=submitQuotation', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items })
        });
        const result = await res.json();
        Swal.close();
        if (result.success) {
            showToast('success', 'Submitted!', result.message);
            selectedIds.clear();
            await loadIndents();
            document.getElementById('selectedCount').textContent = '0';
            document.getElementById('grandTotal').textContent = '₹ 0.00';
        } else {
            showToast('error', 'Submission Failed', result.message);
        }
    } catch (err) {
        Swal.close();
        showToast('error', 'Connection Error', err.message);
    }
}

window.onload = loadIndents;
