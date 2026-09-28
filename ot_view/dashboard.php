<?php
/**
 * ot_view/dashboard.php
 * OT Module - Dashboard (v2 — Modern Calendar UI)
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
// ── OT Module Access Guard ────────────────────────────────────────────────────
$_allowedOtRoles = ['Scrub_Nurse', 'admin', 'Admin', 'Administrator'];
$_currentRole = trim($_SESSION['role'] ?? '');
if (!in_array($_currentRole, $_allowedOtRoles)) {
    header('Location: ../login.php?error=' . urlencode('Access Denied: OT Module is restricted to Scrub Nurses only.'));
    exit;
}
$pageTitle = 'Dashboard';
require_once 'includes/ot_head.php';
// Load dashboard-specific CSS
echo '<link rel="stylesheet" href="assets/css/ot_dashboard.css">';
?>
<div class="ot-wrap">

  <!-- Sidebar (untouched) -->
  <div id="ot-content">
  <?php require_once 'includes/ot_navbar.php'; ?>

  <div class="d-flex">
    <?php require_once 'includes/ot_sidebar.php'; ?>

    <!-- ════════════════════════════════════════════════════════════
         PAGE BODY
         ════════════════════════════════════════════════════════════ -->
    <div class="ot-page-body w-100">

      <!-- Month Summary Bar -->
      <div class="ot-month-bar-card mb-3" id="month-bar-card">
        <i class="fas fa-chart-bar" style="color:var(--d-green);font-size:1.1rem;flex-shrink:0;"></i>
        <span class="ot-month-bar-label" id="month-bar-label">This Month</span>
        <div class="ot-month-sparkbar">
          <div class="ot-month-sparkbar-fill" id="month-sparkbar-fill" style="width:0%"></div>
        </div>
        <span class="ot-month-bar-count" id="month-bar-count">—</span>
        <span class="ot-month-bar-sub" id="month-bar-sub">surgeries</span>
      </div>

      <!-- ── 2-Column Grid ── -->
      <div class="ot-dash-grid">

        <!-- ════ LEFT: Calendar Panel ════ -->
        <div class="ot-cal-panel">

          <!-- Calendar widget (JS-rendered) -->
          <div id="ot-calendar"></div>

          <!-- Selected-day quick stats -->
          <div class="ot-selected-day-box" id="selected-day-box">
            <div class="ot-selected-day-title">Selected Day</div>
            <div class="ot-selected-day-date" id="selected-day-label">—</div>
            <div class="ot-selected-day-stats">
              <div class="ot-sds-item">
                <div class="ot-sds-val" id="sds-total">—</div>
                <div class="ot-sds-lbl">Total</div>
              </div>
              <div class="ot-sds-item">
                <div class="ot-sds-val" id="sds-ongoing">—</div>
                <div class="ot-sds-lbl">Ongoing</div>
              </div>
              <div class="ot-sds-item">
                <div class="ot-sds-val" id="sds-completed">—</div>
                <div class="ot-sds-lbl">Done</div>
              </div>
              <div class="ot-sds-item">
                <div class="ot-sds-val" id="sds-cancelled">—</div>
                <div class="ot-sds-lbl">Cancelled</div>
              </div>
            </div>
          </div>

        </div><!-- /ot-cal-panel -->

        <!-- ════ RIGHT: Main Panel ════ -->
        <div class="ot-main-panel">

          <!-- Stat Cards (today stats) -->
          <div class="ot-stat-grid-v2" id="stat-grid">

            <div class="ot-stat-v2 appear">
              <div class="ot-stat-v2-icon"><i class="fas fa-calendar-check"></i></div>
              <div class="ot-stat-v2-val" id="stat-scheduled">—</div>
              <div class="ot-stat-v2-lbl">Scheduled Today</div>
              <div class="ot-stat-v2-sub" id="stat-scheduled-sub"></div>
            </div>

            <div class="ot-stat-v2 appear">
              <div class="ot-stat-v2-icon"><i class="fas fa-spinner fa-spin-slow"></i></div>
              <div class="ot-stat-v2-val" id="stat-ongoing">—</div>
              <div class="ot-stat-v2-lbl">Ongoing Now</div>
              <div class="ot-stat-v2-sub" id="stat-ongoing-sub"></div>
            </div>

            <div class="ot-stat-v2 appear">
              <div class="ot-stat-v2-icon"><i class="fas fa-check-circle"></i></div>
              <div class="ot-stat-v2-val" id="stat-completed">—</div>
              <div class="ot-stat-v2-lbl">Completed Today</div>
              <div class="ot-stat-v2-sub" id="stat-completed-sub"></div>
            </div>

            <div class="ot-stat-v2 appear">
              <div class="ot-stat-v2-icon"><i class="fas fa-ban"></i></div>
              <div class="ot-stat-v2-val" id="stat-cancelled">—</div>
              <div class="ot-stat-v2-lbl">Cancelled / Postponed</div>
              <div class="ot-stat-v2-sub" id="stat-cancelled-sub"></div>
            </div>

          </div><!-- /stat-grid -->

          <!-- Surgery Timeline for selected day -->
          <div class="ot-timeline-card">
            <div class="ot-timeline-header">
              <div class="ot-section-title">
                <div class="ot-section-title-icon"><i class="fas fa-stream"></i></div>
                <span id="timeline-heading">Today's Timeline</span>
              </div>
              <span class="ot-section-badge" id="timeline-count">0 surgeries</span>
            </div>
            <div class="ot-timeline-body">
              <div id="timeline-list">
                <div class="ot-empty-state">
                  <div class="ot-empty-icon"><i class="fas fa-stream"></i></div>
                  <div class="ot-empty-title">Loading timeline…</div>
                </div>
              </div>
            </div>
          </div>

          <!-- All OT Surgeries Table -->
          <div class="ot-table-card-v2">
            <div class="ot-table-card-v2-header">
              <div class="ot-section-title">
                <div class="ot-section-title-icon"><i class="fas fa-procedures"></i></div>
                <span id="table-heading">All OT Surgeries</span>
              </div>
              <div style="display:flex;align-items:center;gap:.65rem;">
                <button onclick="loadAllSurgeries()" class="ot-btn ot-btn-outline ot-btn-sm" title="Refresh list">
                  <i class="fas fa-sync-alt"></i>
                </button>
                <a href="surgery_schedule.php?action=new"
                   class="ot-btn ot-btn-primary ot-btn-sm">
                  <i class="fas fa-plus"></i> New Surgery
                </a>
              </div>
            </div>
            <div style="overflow-x:auto;">
              <table class="ot-table-v2">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Patient</th>
                    <th>Surgery</th>
                    <th>OT Room</th>
                    <th>Date</th>
                    <th>Time</th>
                  </tr>
                </thead>
                <tbody id="surgeries-tbody">
                  <tr>
                    <td colspan="6" class="text-center py-4 text-muted">Loading…</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div><!-- /table-card-v2 -->

        </div><!-- /ot-main-panel -->
      </div><!-- /ot-dash-grid -->

    </div><!-- /ot-page-body -->
  </div><!-- /d-flex -->
  </div><!-- /ot-content -->
</div><!-- /ot-wrap -->

<style>
/* ═══════════════════════════════════════════════════════════════
   TIMELINE v2 — Card-style, user-friendly time display
   ═══════════════════════════════════════════════════════════════ */

.ot-tl2-item {
    display: flex;
    gap: .85rem;
    padding-bottom: .65rem;
    animation: tlItemIn .35s ease both;
    opacity: 0;
}

/* Numbered left strip */
.ot-tl2-strip {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex-shrink: 0;
    width: 24px;
    padding-top: .2rem;
}

.ot-tl2-num {
    width: 22px; height: 22px;
    border-radius: 50%;
    background: var(--d-green-12);
    color: var(--d-green);
    font-size: .65rem;
    font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    border: 1.5px solid var(--d-green-20);
}

.ot-tl2-dot {
    width: 10px; height: 10px;
    border-radius: 50%;
    margin: 5px 0 0;
    flex-shrink: 0;
}

.ot-tl2-line {
    flex: 1;
    width: 2px;
    background: var(--d-green-12);
    border-radius: 2px;
    margin: 4px 0 0;
    min-height: 20px;
}

/* Card */
.ot-tl2-card {
    flex: 1;
    min-width: 0;
    background: var(--d-white);
    border: 1px solid var(--d-green-12);
    border-radius: 14px;
    overflow: hidden;
    transition: var(--d-transition);
    margin-bottom: .5rem;
    box-shadow: 0 1px 4px rgba(31,107,74,.06);
}

.ot-tl2-card:hover {
    border-color: var(--d-green-20);
    box-shadow: 0 4px 16px rgba(31,107,74,.10);
    transform: translateY(-2px);
}

/* Status-based left border */
.ot-tlc-scheduled  .ot-tl2-card { border-left: 4px solid var(--d-green); }
.ot-tlc-ongoing    .ot-tl2-card { border-left: 4px solid var(--d-green-80); }
.ot-tlc-completed  .ot-tl2-card { border-left: 4px solid var(--d-green-40); }
.ot-tlc-cancelled  .ot-tl2-card { border-left: 4px solid var(--d-cream-darker); opacity: .75; }
.ot-tlc-postponed  .ot-tl2-card { border-left: 4px solid var(--d-green-20); }
.ot-tlc-preponed   .ot-tl2-card { border-left: 4px solid var(--d-green-dark); }

/* Top row: Surgery name + badge */
.ot-tl2-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: .5rem;
    padding: .85rem 1rem .6rem;
    border-bottom: 1px solid var(--d-green-06);
    background: var(--d-surface);
    flex-wrap: wrap;
}

.ot-tl2-surgery {
    font-size: .88rem;
    font-weight: 800;
    color: var(--d-text);
    flex: 1;
    min-width: 0;
}

/* Time Range Row */
.ot-tl2-time-row {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .75rem 1rem;
    background: var(--d-green-06);
    border-bottom: 1px solid var(--d-green-06);
    flex-wrap: wrap;
}

.ot-tl2-time-block {
    display: flex;
    flex-direction: column;
    gap: .12rem;
    min-width: 80px;
}

.ot-tl2-time-lbl {
    font-size: .58rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--d-green-60);
}

.ot-tl2-time-val {
    font-size: .88rem;
    font-weight: 800;
    color: var(--d-green);
    font-variant-numeric: tabular-nums;
    letter-spacing: -.3px;
    display: flex;
    align-items: center;
    gap: .3rem;
}

.ot-tl2-time-val .fa-clock {
    font-size: .75rem;
    opacity: .6;
}

.ot-tl2-time-arrow {
    color: var(--d-green-40);
    font-size: 1rem;
    flex-shrink: 0;
    margin-top: .6rem;
}

.ot-tl2-duration {
    margin-left: auto;
    background: var(--d-green-12);
    color: var(--d-green);
    border: 1px solid var(--d-green-20);
    border-radius: 99px;
    padding: .25rem .75rem;
    font-size: .7rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: .35rem;
    white-space: nowrap;
    flex-shrink: 0;
}

/* Patient + Room meta row */
.ot-tl2-meta-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: .7rem 1rem;
    flex-wrap: wrap;
}

.ot-tl2-meta-item {
    display: flex;
    align-items: center;
    gap: .55rem;
    flex-shrink: 0;
}

.ot-tl2-avatar {
    width: 32px; height: 32px;
    border-radius: 50%;
    background: var(--d-green-12);
    color: var(--d-green);
    font-size: .72rem;
    font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    border: 2px solid var(--d-white);
    box-shadow: 0 2px 6px var(--d-green-20);
    flex-shrink: 0;
}

.ot-tl2-room-icon {
    width: 32px; height: 32px;
    border-radius: 10px;
    background: var(--d-green-06);
    color: var(--d-green);
    font-size: .8rem;
    display: flex; align-items: center; justify-content: center;
    border: 1px solid var(--d-green-12);
    flex-shrink: 0;
}

.ot-tl2-meta-lbl {
    font-size: .6rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: var(--d-text-muted);
    line-height: 1;
}

.ot-tl2-meta-val {
    font-size: .79rem;
    font-weight: 600;
    color: var(--d-text);
    margin-top: .08rem;
    white-space: nowrap;
    max-width: 140px;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ot-tl2-edit-btn {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .3rem .75rem;
    border-radius: 7px;
    border: 1.5px solid var(--d-green-20);
    color: var(--d-green);
    font-size: .7rem;
    font-weight: 700;
    text-decoration: none;
    transition: var(--d-transition);
    background: var(--d-white);
    white-space: nowrap;
    flex-shrink: 0;
}

.ot-tl2-edit-btn:hover {
    background: var(--d-green);
    color: #fff;
    border-color: var(--d-green);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px var(--d-green-40);
}

/* Ongoing pulse on dot */
.ot-tl2-dot.ot-tl-dot-ongoing {
    animation: dotPulse 1.4s ease-in-out infinite;
}

@media (max-width: 600px) {
    .ot-tl2-time-row { flex-direction: column; align-items: flex-start; }
    .ot-tl2-time-arrow { transform: rotate(90deg); margin: 0; }
    .ot-tl2-duration { margin-left: 0; }
    .ot-tl2-meta-val { max-width: 110px; }
}

/* ── Inline Status Select ── */
.ot-status-sel {
    border-radius: 99px;
    padding: .28rem .75rem .28rem .65rem;
    font-size: .65rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .06em;
    border: 1.5px solid;
    cursor: pointer;
    outline: none;
    appearance: auto;
    font-family: 'Inter', sans-serif;
    transition: all .2s ease;
    min-width: 110px;
}
.ot-status-sel:hover { transform: scale(1.03); }
.ot-status-sel:disabled { opacity: .6; cursor: wait; }

.ot-ss-scheduled  { background: rgba(31,107,74,.10); color: #1f6b4a; border-color: rgba(31,107,74,.25); }
.ot-ss-ongoing    { background: #1f6b4a; color: #fff;  border-color: #144d34; }
.ot-ss-completed  { background: rgba(31,107,74,.06); color: #144d34; border-color: rgba(31,107,74,.15); }
.ot-ss-cancelled  { background: #e8e3d8; color: #6b7280; border-color: #ddd8cc; }
.ot-ss-postponed  { background: rgba(31,107,74,.06); color: rgba(31,107,74,.7); border-color: rgba(31,107,74,.15); }
.ot-ss-preponed   { background: rgba(31,107,74,.20); color: #144d34; border-color: rgba(31,107,74,.35); }
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/ot_main.js"></script>
<script src="assets/js/ot_calendar.js"></script>
<script>
/* ═══════════════════════════════════════════════════════════════
   OT DASHBOARD — Controller
   ═══════════════════════════════════════════════════════════════ */

// ── Helpers ────────────────────────────────────────────────────
const esc = OT.esc;

function fmtDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('en-IN', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
}

function fmtTime(t) {
    if (!t) return '—';
    const [h, m] = t.split(':');
    const hh = parseInt(h, 10);
    const ampm = hh >= 12 ? 'PM' : 'AM';
    return `${hh % 12 || 12}:${m} ${ampm}`;
}

function getInitials(name) {
    return (name || '?').trim().split(/\s+/).slice(0, 2).map(w => w[0].toUpperCase()).join('');
}

function statusBadgeV2(status) {
    const cls = 'ot-bv2-' + (status || 'scheduled').toLowerCase();
    return `<span class="ot-badge-v2 ${cls}"><span class="ot-badge-dot"></span>${esc(status)}</span>`;
}

function countUp(el, target, duration = 800) {
    const start  = performance.now();
    const from   = 0;
    const update = (now) => {
        const elapsed = now - start;
        const progress = Math.min(elapsed / duration, 1);
        const ease = 1 - Math.pow(1 - progress, 3); // ease-out-cubic
        el.textContent = Math.round(from + (target - from) * ease);
        if (progress < 1) requestAnimationFrame(update);
    };
    requestAnimationFrame(update);
}

// ── Time Helpers ───────────────────────────────────────────────
function calcDuration(start, end) {
    if (!start || !end) return null;
    const toMins = t => { const [h, m] = t.split(':').map(Number); return h * 60 + m; };
    const diff = toMins(end) - toMins(start);
    if (diff <= 0) return null;
    const hrs = Math.floor(diff / 60);
    const mins = diff % 60;
    if (hrs === 0) return `${mins} min`;
    if (mins === 0) return `${hrs} hr`;
    return `${hrs} hr ${mins} min`;
}

// ── Render Timeline ────────────────────────────────────────────
function renderTimeline(surgeries, dateStr) {
    const list  = document.getElementById('timeline-list');
    const count = document.getElementById('timeline-count');
    const head  = document.getElementById('timeline-heading');

    const today = new Date().toISOString().slice(0, 10);
    head.textContent = dateStr === today ? "Today's Surgery Timeline" : fmtDate(dateStr);

    // Sort by start_time
    const sorted = [...(surgeries || [])].sort((a, b) => (a.start_time || '').localeCompare(b.start_time || ''));
    count.textContent = `${sorted.length} surger${sorted.length === 1 ? 'y' : 'ies'}`;

    if (!sorted.length) {
        list.innerHTML = `<div class="ot-empty-state">
            <div class="ot-empty-icon"><i class="fas fa-calendar-times"></i></div>
            <div class="ot-empty-title">No surgeries on this day</div>
            <div class="ot-empty-sub">Select a different date or schedule a new surgery.</div>
        </div>`;
        return;
    }

    list.innerHTML = sorted.map((s, idx) => {
        const stCls    = (s.status || 'scheduled').toLowerCase();
        const dotCls   = 'ot-tl-dot-' + stCls;
        const cardCls  = 'ot-tlc-' + stCls;
        const duration = calcDuration(s.start_time, s.end_time);
        const startFmt = fmtTime(s.start_time);
        const endFmt   = s.end_time ? fmtTime(s.end_time) : null;
        const initials = getInitials(s.patient_name);

        return `
        <div class="ot-tl2-item ${cardCls}" style="animation-delay:${idx * 0.07}s">

            <!-- Left accent strip + index -->
            <div class="ot-tl2-strip">
                <div class="ot-tl2-num">${idx + 1}</div>
                <div class="ot-tl2-dot ${dotCls}"></div>
                ${idx < sorted.length - 1 ? '<div class="ot-tl2-line"></div>' : ''}
            </div>

            <!-- Card body -->
            <div class="ot-tl2-card">

                <!-- Top row: surgery name + status badge -->
                <div class="ot-tl2-top">
                    <div class="ot-tl2-surgery">${esc(s.surgery_name || s.name || 'Unnamed Surgery')}</div>
                    ${statusBadgeV2(s.status)}
                </div>

                <!-- Time Range Row -->
                <div class="ot-tl2-time-row">
                    <div class="ot-tl2-time-block">
                        <div class="ot-tl2-time-lbl">Start Time</div>
                        <div class="ot-tl2-time-val"><i class="far fa-clock"></i> ${startFmt}</div>
                    </div>
                    <div class="ot-tl2-time-arrow">
                        <i class="fas fa-long-arrow-alt-right"></i>
                    </div>
                    <div class="ot-tl2-time-block">
                        <div class="ot-tl2-time-lbl">End Time</div>
                        <div class="ot-tl2-time-val">${endFmt ? `<i class="far fa-clock"></i> ${endFmt}` : '<span style="color:var(--d-cream-darker)">Not set</span>'}</div>
                    </div>
                    ${duration ? `<div class="ot-tl2-duration"><i class="fas fa-hourglass-half"></i> ${duration}</div>` : ''}
                </div>

                <!-- Patient + Room row -->
                <div class="ot-tl2-meta-row">
                    <div class="ot-tl2-meta-item">
                        <div class="ot-tl2-avatar">${initials}</div>
                        <div>
                            <div class="ot-tl2-meta-lbl">Patient</div>
                            <div class="ot-tl2-meta-val">${esc(s.patient_name || '—')}</div>
                        </div>
                    </div>
                    ${s.ot_room_name ? `
                    <div class="ot-tl2-meta-item">
                        <div class="ot-tl2-room-icon"><i class="fas fa-door-open"></i></div>
                        <div>
                            <div class="ot-tl2-meta-lbl">OT Room</div>
                            <div class="ot-tl2-meta-val">${esc(s.ot_room_name)}</div>
                        </div>
                    </div>` : ''}

                </div>

            </div>
        </div>`;
    }).join('');
}

// ── Change Surgery Status (inline) ────────────────────────────
function changeStatus(id, newStatus, selectEl) {
    const prev = selectEl.dataset.prev;
    selectEl.disabled = true;
    fetch(API_BASE + 'ot/surgeries/' + id + '/status', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status: newStatus })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            // Update select colour class
            selectEl.className = selectEl.className.replace(/ot-ss-\S+/, '');
            selectEl.classList.add('ot-ss-' + newStatus.toLowerCase());
            selectEl.dataset.prev = newStatus;
            // Refresh calendar dots & today stats
            if (window._otCalendar) window._otCalendar.refresh();
            // Re-fetch dashboard stats to update top cards
            fetchDashboardStats();
        } else {
            OT.showToast(res.message || 'Failed to update status', 'error');
            selectEl.value = prev;
        }
    })
    .catch(() => {
        OT.showToast('Network error — status not saved', 'error');
        selectEl.value = prev;
    })
    .finally(() => { selectEl.disabled = false; });
}

// ── Render Table (ALL OT Surgeries) ───────────────────────────
function renderTable(surgeries) {
    const tbody = document.getElementById('surgeries-tbody');

    if (!surgeries || !surgeries.length) {
        tbody.innerHTML = `<tr><td colspan="6">
            <div class="ot-empty-state">
                <div class="ot-empty-icon"><i class="fas fa-procedures"></i></div>
                <div class="ot-empty-title">No surgeries found</div>
                <div class="ot-empty-sub">No OT procedures have been scheduled yet.</div>
            </div>
        </td></tr>`;
        return;
    }

    const statuses = ['Scheduled','Ongoing','Completed','Cancelled','Postponed','Preponed'];

    tbody.innerHTML = surgeries.map((s, i) => {
        const curStatus = s.status || 'Scheduled';
        const options = statuses.map(st =>
            `<option value="${st}" ${curStatus === st ? 'selected' : ''}>${st}</option>`
        ).join('');
        return `
        <tr>
            <td><strong style="color:var(--d-green)">${i + 1}</strong></td>
            <td>
                <div class="ot-patient-cell">
                    <div class="ot-patient-avatar">${getInitials(s.patient_name)}</div>
                    <div>
                        <div class="ot-patient-name-text">${esc(s.patient_name)}</div>
                        <div class="ot-patient-id-text">${esc(s.patient_id)}</div>
                    </div>
                </div>
            </td>
            <td><strong>${esc(s.surgery_name || s.name || '—')}</strong></td>
            <td>
                <span style="font-size:.75rem;color:var(--d-green);font-weight:600;">
                    <i class="fas fa-door-open me-1"></i>${esc(s.ot_room_name)}
                </span>
            </td>
            <td style="font-size:.76rem;color:var(--d-text-muted)">${esc(s.schedule_date)}</td>
            <td style="font-weight:600;color:var(--d-green);font-size:.76rem;font-variant-numeric:tabular-nums;">
                ${fmtTime(s.start_time)}
            </td>
        </tr>`;
    }).join('');
}

// ── Load All Surgeries into table ─────────────────────────────
function loadAllSurgeries() {
    const tbody = document.getElementById('surgeries-tbody');
    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-3">
        <span style="color:var(--d-text-muted);font-size:.8rem;"><i class="fas fa-circle-notch fa-spin me-2"></i>Loading all surgeries…</span>
    </td></tr>`;
    fetch(API_BASE + 'ot/surgeries')
        .then(r => r.json())
        .then(res => {
            if (res.success) renderTable(res.data);
            else renderTable([]);
        })
        .catch(() => renderTable([]));
}

// ── Render Selected-Day Box ────────────────────────────────────
function renderSelectedDayBox(dateStr, surgeries) {
    document.getElementById('selected-day-label').textContent = fmtDate(dateStr);
    document.getElementById('sds-total').textContent     = surgeries.length;
    document.getElementById('sds-ongoing').textContent   = surgeries.filter(s => s.status === 'Ongoing').length;
    document.getElementById('sds-completed').textContent = surgeries.filter(s => s.status === 'Completed').length;
    document.getElementById('sds-cancelled').textContent = surgeries.filter(s => ['Cancelled','Postponed'].includes(s.status)).length;
}

// ── Calendar day-select callback ───────────────────────────────
// Calendar only drives the timeline + day stats box; table is independent
function onCalendarSelect(dateStr, daySurgeries) {
    renderSelectedDayBox(dateStr, daySurgeries);
    renderTimeline(daySurgeries, dateStr);
}

// ── Fetch dashboard stats (reusable) ─────────────────────────
function fetchDashboardStats() {
    fetch(API_BASE + 'ot/dashboard')
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            const d = res.data;
            countUp(document.getElementById('stat-scheduled'), d.scheduled_today  ?? 0);
            countUp(document.getElementById('stat-ongoing'),   d.ongoing_today    ?? 0);
            countUp(document.getElementById('stat-completed'), d.completed_today  ?? 0);
            countUp(document.getElementById('stat-cancelled'), d.cancelled_today  ?? 0);
            const monthTotal = d.month_total ?? 0;
            const maxForBar  = Math.max(monthTotal, 30);
            document.getElementById('month-bar-count').textContent = monthTotal;
            document.getElementById('month-bar-label').textContent =
                new Date().toLocaleString('default', { month: 'long', year: 'numeric' });
            setTimeout(() => {
                document.getElementById('month-sparkbar-fill').style.width =
                    Math.round((monthTotal / maxForBar) * 100) + '%';
            }, 200);
        })
        .catch(err => console.error('Dashboard stats error:', err));
}

// ── Boot ───────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

    const todayStr = new Date().toISOString().slice(0, 10);

    // 1. Init Calendar (drives timeline + day stats)
    window._otCalendar = new OTCalendar('ot-calendar', onCalendarSelect);

    // 2. Top stat cards
    fetchDashboardStats();

    // 3. Load ALL surgeries into the table (independent of calendar)
    loadAllSurgeries();
});
</script>
<?php require_once 'includes/ot_foot.php'; ?>
