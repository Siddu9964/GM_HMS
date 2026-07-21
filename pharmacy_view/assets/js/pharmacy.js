/**
 * Pharmacy ERP - Global JavaScript Utilities
 */

/* ------------------------------------------------------------------ */
/* SweetAlert2 Helpers                                                  */
/* ------------------------------------------------------------------ */
const PH = {
  toast(icon, title, timer = 3000) {
    Swal.fire({ 
        toast: true, 
        position: 'top-end', 
        icon, 
        title, 
        showConfirmButton: false, 
        timer, 
        timerProgressBar: true,
        background: '#ffffff',
        color: '#1e293b'
    });
  },
  success(msg) { this.toast('success', msg); },
  error(msg) { this.toast('error', msg, 4000); },
  warning(msg) { this.toast('warning', msg, 4000); },
  info(msg) { this.toast('info', msg); },

  confirm(title, text, cb, confirmText = 'Yes, Delete!') {
    Swal.fire({
      title, text, icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#EF4444',
      cancelButtonColor: '#6B7280',
      confirmButtonText: confirmText,
      cancelButtonText: 'Cancel'
    }).then(r => { if (r.isConfirmed) cb(); });
  },

  loading(msg = 'Processing…') {
    Swal.fire({ title: msg, allowOutsideClick: false, didOpen: () => Swal.showLoading() });
  },
  close() { Swal.close(); }
};

/* ------------------------------------------------------------------ */
/* AJAX Helpers                                                         */
/* ------------------------------------------------------------------ */
async function phFetch(url, data = {}, method = 'POST') {
  const fd = new FormData();
  Object.entries(data).forEach(([k, v]) => fd.append(k, v));
  const res = await fetch(url, { method, body: method === 'POST' ? fd : undefined });
  return res.json();
}

async function phGet(url) {
  const res = await fetch(url);
  return res.json();
}

async function phPost(url, data = {}) {
  return phFetch(url, data, 'POST');
}

/* ------------------------------------------------------------------ */
/* Sidebar Toggle                                                        */
/* ------------------------------------------------------------------ */
function phToggleSidebar() {
  document.querySelector('.ph-sidebar').classList.toggle('open');
  document.getElementById('ph-overlay').classList.toggle('show');
}

document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.getElementById('ph-overlay');
  if (overlay) overlay.addEventListener('click', phToggleSidebar);
});

/* ------------------------------------------------------------------ */
/* Active Nav Detection                                                  */
/* ------------------------------------------------------------------ */
document.addEventListener('DOMContentLoaded', () => {
  const page = window.location.pathname.split('/').pop();
  document.querySelectorAll('.ph-nav-link').forEach(a => {
    if (a.getAttribute('href') === page) a.classList.add('active');
  });
});

/* ------------------------------------------------------------------ */
/* Notification Bell                                                     */
/* ------------------------------------------------------------------ */
async function loadNotifCount() {
  try {
    const r = await phGet(API_BASE + 'pharmacy/notifications/counts');
    if (!r.success) return;
    const d = r.data;
    const el = document.getElementById('ph-notif-count');
    const total = (d.low_stock || 0) + (d.expiry || 0) + (d.pending_indents || 0);
    if (el && total > 0) { el.textContent = total; el.style.display = 'inline-flex'; }
    // Update sidebar badges
    ['low-stock-badge', 'expiry-badge', 'indent-badge'].forEach((id, i) => {
      const b = document.getElementById(id);
      const v = [d.low_stock, d.expiry, d.pending_indents][i];
      if (b && v > 0) { b.textContent = v; b.style.display = 'inline'; }
    });
  } catch (e) { }
}

document.addEventListener('DOMContentLoaded', loadNotifCount);

/* ------------------------------------------------------------------ */
/* Pagination Helper                                                     */
/* ------------------------------------------------------------------ */
function phPaginate(data, page, perPage) {
  const total = data.length;
  const pages = Math.ceil(total / perPage);
  const start = (page - 1) * perPage;
  return { items: data.slice(start, start + perPage), total, pages, page };
}

function phRenderPager(pagerEl, pages, current, onPage) {
  let html = '';
  const btn = (p, label, disabled = false, active = false) =>
    `<button class="ph-page-btn ${active ? 'active' : ''}" ${disabled ? 'disabled' : ''} data-p="${p}">${label}</button>`;
  html += btn(current - 1, '<i class="fas fa-chevron-left"></i>', current <= 1);
  for (let i = 1; i <= pages; i++) {
    if (pages > 7 && i > 2 && i < pages - 1 && Math.abs(i - current) > 1) {
      if (i === 3 || i === pages - 2) html += '<span style="padding:0 .25rem">…</span>';
      continue;
    }
    html += btn(i, i, false, i === current);
  }
  html += btn(current + 1, '<i class="fas fa-chevron-right"></i>', current >= pages);
  pagerEl.innerHTML = html;
  pagerEl.querySelectorAll('.ph-page-btn:not([disabled])').forEach(b => {
    b.addEventListener('click', () => onPage(+b.dataset.p));
  });
}

/* ------------------------------------------------------------------ */
/* Format Helpers                                                        */
/* ------------------------------------------------------------------ */
const CUR = '₹';
const fmt = {
  currency: v => CUR + parseFloat(v || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 }),
  date: d => d ? new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '—',
  time: t => {
    if (!t) return '';
    try {
      const [h, m] = t.split(':');
      const hh = parseInt(h);
      const ampm = hh >= 12 ? 'PM' : 'AM';
      const h12 = hh % 12 || 12;
      return `${h12}:${m} ${ampm}`;
    } catch (e) { return t; }
  },
  number: v => parseInt(v || 0).toLocaleString('en-IN'),
};

/* ------------------------------------------------------------------ */
/* Search + Filter Utility                                              */
/* ------------------------------------------------------------------ */
function phSearch(data, q, fields) {
  if (!q) return data;
  q = q.toLowerCase();
  return data.filter(row => fields.some(f => String(row[f] ?? '').toLowerCase().includes(q)));
}

/* ------------------------------------------------------------------ */
/* Print Iframe Helper                                                   */
/* ------------------------------------------------------------------ */
function phPrint(html) {
  const w = window.open('', '_blank', 'width=800,height=600');
  w.document.write(`<!DOCTYPE html><html><head>
    <title>Print</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>body{font-family:'Inter',sans-serif;padding:20px}@media print{.no-print{display:none}}</style>
    </head><body>${html}<script>window.onload=()=>window.print()<\/script></body></html>`);
  w.document.close();
}

/* ------------------------------------------------------------------ */
/* Expiry Color Utility                                                  */
/* ------------------------------------------------------------------ */
function expiryBadge(dateStr) {
  if (!dateStr) return '<span class="ph-badge badge-muted">No Expiry</span>';
  const days = Math.ceil((new Date(dateStr) - new Date()) / 86400000);
  if (days < 0) return `<span class="ph-badge badge-dark">EXPIRED</span>`;
  if (days <= 15) return `<span class="ph-badge badge-danger">Critical (${days}d)</span>`;
  if (days <= 30) return `<span class="ph-badge badge-warning">Urgent (${days}d)</span>`;
  if (days <= 60) return `<span class="ph-badge badge-info">Soon (${days}d)</span>`;
  return `<span class="ph-badge badge-success">${fmt.date(dateStr)}</span>`;
}

/* ------------------------------------------------------------------ */
/* Status Badge                                                          */
/* ------------------------------------------------------------------ */
function statusBadge(status) {
  const map = {
    active: 'success', inactive: 'muted', pending: 'warning', approved: 'success',
    rejected: 'danger', cancelled: 'danger', ordered: 'info', received: 'success',
    draft: 'muted', completed: 'success', partial: 'warning', 'low critical': 'danger',
    'low stock': 'warning', urgent: 'danger', high: 'danger', medium: 'warning', low: 'success'
  };
  const cls = map[status?.toLowerCase()] || 'muted';
  return `<span class="ph-badge badge-${cls}">${status}</span>`;
}
