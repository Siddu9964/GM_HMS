/**
 * GM_HMS Laboratory JS Utility Library
 * Core helpers used across all LIS pages.
 * All existing API logic is preserved; new utilities added below.
 */

/**
 * Standard API caller for laboratory module
 */
async function lisApi(method, endpoint, body = null) {
    const url = '/GM_HMS' + (endpoint.startsWith('/') ? endpoint : '/' + endpoint);
    const options = {
        method: method.toUpperCase(),
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Hospital-Branch': window.HOSPITAL_BRANCH || ''
        }
    };

    if (body instanceof FormData) {
        delete options.headers['Content-Type'];
        options.body = body;
    } else if (body && (options.method === 'POST' || options.method === 'PUT' || options.method === 'PATCH')) {
        options.body = JSON.stringify(body);
    }

    try {
        const response = await fetch(url, options);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return await response.json();
    } catch (error) {
        console.error('LIS API Error:', error);
        throw error;
    }
}

/**
 * Animate counting up to a target number
 */
function lisCountUp(element, target, duration = 900) {
    if (!element) return;
    const targetNum = parseInt(target) || 0;
    const startNum  = parseInt((element.innerText || '0').replace(/[^0-9]/g, '')) || 0;
    if (startNum === targetNum) { element.innerText = targetNum.toLocaleString(); return; }
    const range = targetNum - startNum;
    const startTime = performance.now();
    function updateCount(now) {
        const elapsed  = now - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const easeOut  = 1 - Math.pow(1 - progress, 3);
        element.innerText = Math.round(startNum + range * easeOut).toLocaleString();
        if (progress < 1) requestAnimationFrame(updateCount);
        else element.innerText = targetNum.toLocaleString();
    }
    requestAnimationFrame(updateCount);
}

/**
 * Display a centered modal notification using SweetAlert2
 */
function lisToast(message, type = 'success', title = null) {
    if (typeof Swal !== 'undefined') {
        let defaultTitle = 'Success';
        let confirmBtnColor = '#1f6b4a';
        let showConfirm = false;
        let timerDuration = 2200;

        if (type === 'error') {
            defaultTitle = 'Action Failed';
            confirmBtnColor = '#dc2626';
            showConfirm = true;
            timerDuration = null; // Do not auto-dismiss errors so user can read message
        } else if (type === 'warning') {
            defaultTitle = 'Attention Required';
            confirmBtnColor = '#d97706';
            showConfirm = true;
            timerDuration = 3200;
        } else if (type === 'info') {
            defaultTitle = 'Notice';
            confirmBtnColor = '#1f6b4a';
            timerDuration = 2400;
        }

        Swal.fire({
            position: 'center',
            icon: type,
            title: title || defaultTitle,
            text: message,
            showCloseButton: true,
            showConfirmButton: showConfirm,
            confirmButtonText: 'OK',
            confirmButtonColor: confirmBtnColor,
            timer: timerDuration,
            timerProgressBar: Boolean(timerDuration),
            backdrop: 'rgba(15, 23, 42, 0.45)',
            allowOutsideClick: true,
            allowEscapeKey: true,
            customClass: {
                popup: 'gm-center-swal-popup'
            }
        });
    } else {
        alert(message);
    }
}

/**
 * Confirm dialog using SweetAlert2 centered
 */
function lisConfirm(message, onConfirm, options = {}) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            position: 'center',
            title: options.title || 'Are you sure?',
            text: message,
            icon: options.icon || 'warning',
            showCancelButton: true,
            confirmButtonColor: options.confirmColor || '#1f6b4a',
            cancelButtonColor: '#64748b',
            confirmButtonText: options.confirmText || 'Yes, proceed',
            cancelButtonText: options.cancelText || 'Cancel',
            backdrop: 'rgba(15, 23, 42, 0.45)',
            customClass: {
                popup: 'gm-center-swal-popup'
            }
        }).then(result => {
            if (result.isConfirmed && typeof onConfirm === 'function') {
                onConfirm();
            }
        });
    } else {
        if (confirm(message)) onConfirm();
    }
}

function labConfirmLogout(e) {
    if (e && typeof e.preventDefault === 'function') e.preventDefault();
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            position: 'center',
            icon: 'question',
            title: 'Sign Out Confirmation',
            text: 'Are you sure you want to log out of the Laboratory Information System?',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fas fa-sign-out-alt"></i> Yes, Log Out',
            cancelButtonText: 'Cancel',
            backdrop: 'rgba(15, 23, 42, 0.5)',
            reverseButtons: true,
            customClass: {
                popup: 'gm-center-swal-popup'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    position: 'center',
                    icon: 'info',
                    title: 'Signing Out...',
                    text: 'Please wait while your session is terminated.',
                    showConfirmButton: false,
                    timer: 800,
                    backdrop: 'rgba(15, 23, 42, 0.5)'
                }).then(() => {
                    window.location.href = '/GM_HMS/logout.php';
                });
            }
        });
    } else {
        if (confirm('Are you sure you want to log out?')) {
            window.location.href = '/GM_HMS/logout.php';
        }
    }
    return false;
}

/**
 * Format date to readable string
 */
function lisFormatDate(d, opts = {}) {
    if (!d) return '—';
    const date = new Date(d);
    if (isNaN(date)) return String(d);
    if (opts.time) {
        return date.toLocaleString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }
    return date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
}

/**
 * Format INR currency
 */
function lisFmt(v) {
    if (v === null || v === undefined || v === '') return '—';
    return '₹' + parseFloat(v).toLocaleString('en-IN', { minimumFractionDigits: 0 });
}

/**
 * Escape HTML to prevent XSS
 */
function escHtml(s) {
    return String(s || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/**
 * Get initials from a name
 */
function lisInitials(name) {
    return (name || 'U').split(' ').slice(0, 2).map(w => w[0]?.toUpperCase() || '').join('');
}

/**
 * Elapsed time string (e.g. "2h 14m ago")
 */
function lisElapsed(dateStr) {
    if (!dateStr) return '—';
    const then = new Date(dateStr);
    const now  = new Date();
    const diff = Math.floor((now - then) / 1000);
    if (diff < 60)   return `${diff}s ago`;
    if (diff < 3600) return `${Math.floor(diff/60)}m ago`;
    if (diff < 86400)return `${Math.floor(diff/3600)}h ${Math.floor((diff%3600)/60)}m ago`;
    return lisFormatDate(dateStr);
}

/**
 * Render skeleton loader rows
 */
function lisSkeletonRows(n = 5, cols = 6) {
    return Array.from({length: n}, () =>
        `<tr>${Array.from({length: cols}, () =>
            `<td><div class="lis-skeleton lis-skeleton-text" style="width:${60+Math.random()*35}%"></div></td>`
        ).join('')}</tr>`
    ).join('');
}

/**
 * Animate progress ring
 */
function lisAnimateRing(el, pct) {
    if (!el) return;
    const circumference = 220;
    const offset = circumference - (pct / 100) * circumference;
    const fill = el.querySelector('.lis-progress-ring-fill');
    if (fill) fill.style.strokeDashoffset = offset;
}

/**
 * Get status badge HTML
 */
function lisStatusBadge(status) {
    const map = {
        'Ordered':          'lis-badge-ordered',
        'Sample Collected': 'lis-badge-collected',
        'Processing':       'lis-badge-processing',
        'Verification':     'lis-badge-verify',
        'Completed':        'lis-badge-completed',
        'Reported':         'lis-badge-reported',
        'Delivered':        'lis-badge-delivered',
        'In Progress':      'lis-badge-processing',
    };
    const cls = map[status] || 'lis-badge-ordered';
    return `<span class="lis-badge ${cls}">${escHtml(status || 'Ordered')}</span>`;
}

/**
 * Get priority badge HTML
 */
function lisPriorityBadge(priority) {
    const map = {
        'Urgent':  'lis-badge-urgent',
        'STAT':    'lis-badge-stat',
        'Routine': 'lis-badge-routine',
        'Critical':'lis-badge-critical',
    };
    const cls = map[priority] || 'lis-badge-routine';
    return `<span class="lis-badge ${cls}">${escHtml(priority || 'Routine')}</span>`;
}
