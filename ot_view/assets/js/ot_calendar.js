/**
 * ot_calendar.js
 * Vanilla JS OT Calendar Engine
 * Palette: #f3efe6 (cream) · #1f6b4a (green)
 * No external dependencies.
 */

class OTCalendar {
    /**
     * @param {string} containerId   — ID of the calendar wrapper div
     * @param {Function} onSelect    — callback(dateStr, surgeriesForDate)
     */
    constructor(containerId, onSelect) {
        this.container  = document.getElementById(containerId);
        this.onSelect   = onSelect || (() => {});
        this.today      = new Date();
        this.year       = this.today.getFullYear();
        this.month      = this.today.getMonth(); // 0-indexed
        this.selected   = this._fmt(this.today);
        this.surgeries  = [];   // all surgeries for current view month
        this._fetching  = false;

        this._render();
        this._fetchMonth(this.year, this.month);
    }

    // ── Public API ──────────────────────────────────────────────────────

    /** Reload data for current month (call after creating/updating a surgery) */
    refresh() {
        this._fetchMonth(this.year, this.month);
    }

    /** Jump to a specific date string 'YYYY-MM-DD' */
    jumpTo(dateStr) {
        const d = new Date(dateStr + 'T00:00:00');
        if (isNaN(d)) return;
        const oldYear  = this.year;
        const oldMonth = this.month;
        this.year  = d.getFullYear();
        this.month = d.getMonth();
        this.selected = dateStr;

        const dir = (this.year > oldYear || (this.year === oldYear && this.month > oldMonth))
            ? 'next' : (this.year === oldYear && this.month === oldMonth ? null : 'prev');

        this._animateGrid(dir, () => {
            this._renderGrid();
            this._markDots();
        });

        if (this.year !== oldYear || this.month !== oldMonth) {
            this._fetchMonth(this.year, this.month);
        }
        this._fireSelect(dateStr);
    }

    // ── Internal ─────────────────────────────────────────────────────────

    _fmt(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    _monthLabel(year, month) {
        return new Date(year, month, 1).toLocaleString('default', { month: 'long', year: 'numeric' });
    }

    _render() {
        this.container.innerHTML = `
            <div class="ot-cal-card">
                <div class="ot-cal-head">
                    <span class="ot-cal-month-label" id="otcal-label"></span>
                    <div class="ot-cal-nav">
                        <button class="ot-cal-nav-btn" id="otcal-prev" title="Previous month">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="ot-cal-nav-btn" id="otcal-today" title="Go to today" style="width:auto;padding:0 .55rem;font-size:.65rem;font-weight:700;letter-spacing:.04em;">
                            Today
                        </button>
                        <button class="ot-cal-nav-btn" id="otcal-next" title="Next month">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <div class="ot-cal-dow-row">
                    ${['Su','Mo','Tu','We','Th','Fr','Sa'].map(d =>
                        `<div class="ot-cal-dow">${d}</div>`).join('')}
                </div>

                <div class="ot-cal-grid" id="otcal-grid"></div>

                <div class="ot-cal-legend">
                    <div class="ot-cal-legend-item">
                        <div class="ot-cal-dot ot-cal-dot-scheduled"></div> Scheduled
                    </div>
                    <div class="ot-cal-legend-item">
                        <div class="ot-cal-dot ot-cal-dot-ongoing"></div> Ongoing
                    </div>
                    <div class="ot-cal-legend-item">
                        <div class="ot-cal-dot ot-cal-dot-completed"></div> Done
                    </div>
                    <div class="ot-cal-legend-item">
                        <div class="ot-cal-dot ot-cal-dot-cancelled"></div> Cancelled
                    </div>
                </div>
            </div>`;

        document.getElementById('otcal-prev').addEventListener('click', () => this._prev());
        document.getElementById('otcal-next').addEventListener('click', () => this._next());
        document.getElementById('otcal-today').addEventListener('click', () => this._goToday());

        this._renderLabel();
        this._renderGrid();
    }

    _renderLabel() {
        const el = document.getElementById('otcal-label');
        if (el) el.textContent = this._monthLabel(this.year, this.month);
    }

    _renderGrid() {
        const grid = document.getElementById('otcal-grid');
        if (!grid) return;

        const firstDay  = new Date(this.year, this.month, 1).getDay(); // 0=Sun
        const daysInMon = new Date(this.year, this.month + 1, 0).getDate();
        const prevDays  = new Date(this.year, this.month, 0).getDate();
        const todayStr  = this._fmt(this.today);

        let cells = '';

        // Fill leading empty cells (prev month days, greyed)
        for (let i = 0; i < firstDay; i++) {
            const day = prevDays - firstDay + 1 + i;
            cells += `<div class="ot-cal-day ot-cal-other ot-cal-empty">
                <div class="ot-cal-day-num">${day}</div>
                <div class="ot-cal-dots"></div>
            </div>`;
        }

        // Current month days
        for (let d = 1; d <= daysInMon; d++) {
            const dateStr = `${this.year}-${String(this.month + 1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
            const isToday    = dateStr === todayStr;
            const isSelected = dateStr === this.selected;
            let cls = 'ot-cal-day';
            if (isToday)    cls += ' ot-cal-today';
            if (isSelected) cls += ' ot-cal-selected';

            cells += `<div class="${cls}" data-date="${dateStr}">
                <div class="ot-cal-day-num">${d}</div>
                <div class="ot-cal-dots" id="otcal-dots-${dateStr}"></div>
            </div>`;
        }

        // Trailing cells to complete last row
        const totalCells = firstDay + daysInMon;
        const trailing = (7 - (totalCells % 7)) % 7;
        for (let i = 1; i <= trailing; i++) {
            cells += `<div class="ot-cal-day ot-cal-other ot-cal-empty">
                <div class="ot-cal-day-num">${i}</div>
                <div class="ot-cal-dots"></div>
            </div>`;
        }

        grid.innerHTML = cells;

        // Attach click listeners
        grid.querySelectorAll('.ot-cal-day[data-date]').forEach(el => {
            el.addEventListener('click', () => {
                const date = el.dataset.date;
                this._selectDay(date);
            });
        });

        this._markDots();
    }

    _markDots() {
        // Group surgeries by date
        const byDate = {};
        this.surgeries.forEach(s => {
            const d = (s.schedule_date || '').substring(0, 10);
            if (!byDate[d]) byDate[d] = [];
            byDate[d].push(s);
        });

        Object.entries(byDate).forEach(([date, list]) => {
            const el = document.getElementById(`otcal-dots-${date}`);
            if (!el) return;
            // Show up to 4 unique-status dots
            const seen = new Set();
            let html = '';
            list.forEach(s => {
                const st = (s.status || 'Scheduled').toLowerCase();
                if (!seen.has(st) && seen.size < 4) {
                    seen.add(st);
                    html += `<div class="ot-cal-dot ot-cal-dot-${st}"></div>`;
                }
            });
            el.innerHTML = html;
        });
    }

    _selectDay(dateStr) {
        // Update selected class
        const grid = document.getElementById('otcal-grid');
        if (grid) {
            grid.querySelectorAll('.ot-cal-selected').forEach(el => el.classList.remove('ot-cal-selected'));
            const target = grid.querySelector(`[data-date="${dateStr}"]`);
            if (target) target.classList.add('ot-cal-selected');
        }
        this.selected = dateStr;
        this._fireSelect(dateStr);
    }

    _fireSelect(dateStr) {
        const daySurgeries = this.surgeries.filter(s =>
            (s.schedule_date || '').substring(0, 10) === dateStr
        );
        this.onSelect(dateStr, daySurgeries);
    }

    _prev() {
        this.month--;
        if (this.month < 0) { this.month = 11; this.year--; }
        this._renderLabel();
        this._animateGrid('prev', () => {
            this._renderGrid();
            this._fetchMonth(this.year, this.month);
        });
    }

    _next() {
        this.month++;
        if (this.month > 11) { this.month = 0; this.year++; }
        this._renderLabel();
        this._animateGrid('next', () => {
            this._renderGrid();
            this._fetchMonth(this.year, this.month);
        });
    }

    _goToday() {
        const oldYear  = this.year;
        const oldMonth = this.month;
        this.year  = this.today.getFullYear();
        this.month = this.today.getMonth();
        const todayStr = this._fmt(this.today);

        const dir = (this.year > oldYear || (this.year === oldYear && this.month > oldMonth))
            ? 'next' : (this.year === oldYear && this.month === oldMonth ? null : 'prev');

        this._renderLabel();
        this._animateGrid(dir, () => {
            this._renderGrid();
            if (this.year !== oldYear || this.month !== oldMonth) {
                this._fetchMonth(this.year, this.month);
            }
        });
        this._selectDay(todayStr);
    }

    _animateGrid(dir, callback) {
        const grid = document.getElementById('otcal-grid');
        if (!grid || !dir) { if (callback) callback(); return; }

        const outClass = dir === 'next' ? 'slide-out-left' : 'slide-out-right';
        const inClass  = dir === 'next' ? 'slide-in-right' : 'slide-in-left';

        grid.classList.add(outClass);
        setTimeout(() => {
            grid.classList.remove(outClass);
            if (callback) callback();
            const newGrid = document.getElementById('otcal-grid');
            if (newGrid) {
                newGrid.classList.add(inClass);
                setTimeout(() => newGrid.classList.remove(inClass), 220);
            }
        }, 200);
    }

    _fetchMonth(year, month) {
        if (this._fetching) return;
        this._fetching = true;

        // Fetch entire month's surgeries for dot marking
        // We pass a broader range using two separate calls or rely on
        // the API returning all when no date given — here we use ?date=YYYY-MM-DD
        // for each visible day. Simpler: fetch all with no filter then filter client-side.
        // The API supports exact date only, so we fetch all and filter by month client-side.
        const url = (typeof API_BASE !== 'undefined' ? API_BASE : '/api/')
            + 'ot/surgeries';

        fetch(url)
            .then(r => r.json())
            .then(res => {
                if (res.success && Array.isArray(res.data)) {
                    // Keep only the month we care about for efficiency
                    const prefix = `${year}-${String(month + 1).padStart(2, '0')}`;
                    this.surgeries = res.data.filter(s =>
                        (s.schedule_date || '').startsWith(prefix)
                    );
                } else {
                    this.surgeries = [];
                }
                this._markDots();
            })
            .catch(() => { this.surgeries = []; })
            .finally(() => { this._fetching = false; });
    }
}
