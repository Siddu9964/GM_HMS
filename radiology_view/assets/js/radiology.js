/**
 * GM_HMS Radiology JS Utility Library
 * Core helpers used across all RIS pages.
 */

async function radApi(method, endpoint, body = null) {
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
            let errMsg = `HTTP error! status: ${response.status}`;
            try {
                const errData = await response.json();
                if (errData && (errData.message || errData.error)) {
                    errMsg = errData.message || errData.error;
                }
            } catch(e) {}
            throw new Error(errMsg);
        }
        return await response.json();
    } catch (error) {
        console.error('RIS API Error:', error);
        throw error;
    }
}

// Alias for shared functions
const lisApi = radApi;

function radCountUp(element, target, duration = 900) {
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
const lisCountUp = radCountUp;

function radToast(message, type = 'success', title = null) {
    if (typeof Swal !== 'undefined') {
        let defaultTitle = 'Success';
        let confirmBtnColor = '#0284c7';
        let showConfirm = true;
        let timerDuration = 3500;

        if (type === 'error') {
            defaultTitle = 'Action Failed';
            confirmBtnColor = '#dc2626';
            showConfirm = true;
            timerDuration = null; // Don't auto-dismiss errors so user can read message
        } else if (type === 'warning') {
            defaultTitle = 'Attention Required';
            confirmBtnColor = '#d97706';
            showConfirm = true;
            timerDuration = 4000;
        } else if (type === 'info') {
            defaultTitle = 'Notice';
            confirmBtnColor = '#0284c7';
            showConfirm = true;
            timerDuration = 3500;
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
            },
            didOpen: (popup) => {
                const closeBtn = popup.querySelector('.swal2-close');
                if (closeBtn) {
                    closeBtn.style.cursor = 'pointer';
                    closeBtn.onclick = (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        Swal.close();
                    };
                }
            },
            didClose: () => {
                const openModal = document.querySelector('.lis-modal-overlay.open, .lis-modal-overlay[style*="display: flex"], .lis-modal-overlay[style*="display: block"], .modal.show');
                if (openModal) {
                    document.body.classList.add('modal-open');
                } else {
                    document.body.classList.remove('modal-open');
                    document.body.style.removeProperty('overflow');
                }
            }
        });
    } else {
        alert(message);
    }
}
const lisToast = radToast;

function radConfirm(message, onConfirm, options = {}) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            position: 'center',
            title: options.title || 'Are you sure?',
            text: message,
            icon: options.icon || 'warning',
            showCloseButton: true,
            showCancelButton: true,
            confirmButtonColor: options.confirmColor || '#0284c7',
            cancelButtonColor: '#64748b',
            confirmButtonText: options.confirmText || 'Yes, proceed',
            cancelButtonText: options.cancelText || 'Cancel',
            backdrop: 'rgba(15, 23, 42, 0.45)',
            allowOutsideClick: true,
            allowEscapeKey: true,
            customClass: {
                popup: 'gm-center-swal-popup'
            },
            didOpen: (popup) => {
                const closeBtn = popup.querySelector('.swal2-close');
                if (closeBtn) {
                    closeBtn.style.cursor = 'pointer';
                    closeBtn.onclick = (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        Swal.close();
                    };
                }
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
const lisConfirm = radConfirm;

function radConfirmLogout(e) {
    if (e && typeof e.preventDefault === 'function') e.preventDefault();
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            position: 'center',
            icon: 'question',
            title: 'Sign Out Confirmation',
            text: 'Are you sure you want to log out of the Radiology Information System?',
            showCloseButton: true,
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fas fa-sign-out-alt"></i> Yes, Log Out',
            cancelButtonText: 'Cancel',
            backdrop: 'rgba(15, 23, 42, 0.5)',
            allowOutsideClick: true,
            allowEscapeKey: true,
            reverseButtons: true,
            customClass: {
                popup: 'gm-center-swal-popup'
            },
            didOpen: (popup) => {
                const closeBtn = popup.querySelector('.swal2-close');
                if (closeBtn) {
                    closeBtn.style.cursor = 'pointer';
                    closeBtn.onclick = (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        Swal.close();
                    };
                }
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

function radFormatDate(d, opts = {}) {
    if (!d) return '—';
    const date = new Date(d);
    if (isNaN(date)) return String(d);
    if (opts.time) {
        return date.toLocaleString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }
    return date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
}
const lisFormatDate = radFormatDate;

function radFmt(v) {
    if (v === null || v === undefined || v === '') return '—';
    return '₹' + parseFloat(v).toLocaleString('en-IN', { minimumFractionDigits: 0 });
}
const lisFmt = radFmt;

function escHtml(s) {
    return String(s || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function radStatusBadge(status) {
    const map = {
        'Ordered':     '<span class="lis-badge lis-badge-ordered"><i class="fas fa-clock"></i> Ordered</span>',
        'In Progress': '<span class="lis-badge lis-badge-progress"><i class="fas fa-spinner fa-spin"></i> In Progress</span>',
        'Completed':   '<span class="lis-badge lis-badge-completed"><i class="fas fa-check"></i> Completed</span>',
        'Reported':    '<span class="lis-badge lis-badge-reported"><i class="fas fa-file-medical"></i> Reported</span>'
    };
    return map[status] || `<span class="lis-badge lis-badge-ordered">${escHtml(status || 'Ordered')}</span>`;
}
const lisStatusBadge = radStatusBadge;

function formatProcedureListHtml(raw) {
    if (!raw) return '<span style="color:#94a3b8;">—</span>';
    let items = [];
    if (Array.isArray(raw)) {
        items = raw;
    } else {
        let str = String(raw).trim();
        if (str.startsWith('[') || str.startsWith('{')) {
            try { items = JSON.parse(str); } catch(e) {}
        }
        if (!items || items.length === 0) {
            const firstPass = str.split('|||');
            firstPass.forEach(fp => {
                fp = fp.trim();
                if (!fp) return;
                const sub = fp.split(/(?<=\))\s*,\s*|,\s*(?=(?:X-?RAY|CT|HRCT|CECT|NCCT|USG|ULTRASOUND|ULTRA SOUND|MRI|DOPPLER|MAMMO|RDS\d+)\b)/i);
                sub.forEach(s => {
                    if (s.trim()) items.push(s.trim());
                });
            });
        }
    }

    // Filter only genuine radiology procedures
    items = items.filter(it => {
        const s = (typeof it === 'object' ? (it.name || it.test_name || '') : String(it)).toUpperCase();
        if (/\bLAB\d+\b/i.test(s)) return false;
        if (/\b(CBC|ELECTROLYTE|UREA|CREATININE|URINE|SUGAR|GLUCOSE|LIPID|BILIRUBIN|WIDAL|CULTURE|HEMOGLOBIN|BLOOD GAS)\b/i.test(s) && !/\bRDS\d*\b/i.test(s)) return false;
        return true;
    });

    if (items.length === 0) return '<span style="color:#94a3b8;">—</span>';

    return `<div class="rad-tests-stack" style="display:flex; flex-direction:column; gap:5px; max-width:380px;">` +
        items.map(it => {
            const s = typeof it === 'object' ? (it.name || it.test_name || '') : String(it);
            let name = s;
            let code = '';
            const m = s.match(/^(.*?)\s*\(([A-Za-z0-9_-]+)\)\s*$/);
            if (m) { name = m[1]; code = m[2]; }
            
            let icon = 'fa-film';
            let accent = '#0284c7';
            let iconBg = '#e0f2fe';
            let iconColor = '#0284c7';
            const u = s.toUpperCase();
            if (/\b(CT|HRCT|CECT|NCCT)\b/i.test(u)) { icon = 'fa-circle-notch'; accent = '#d97706'; iconBg = '#fef3c7'; iconColor = '#b45309'; }
            else if (/\b(MRI)\b/i.test(u)) { icon = 'fa-magnet'; accent = '#7c3aed'; iconBg = '#ede9fe'; iconColor = '#6d28d9'; }
            else if (/\b(USG|ULTRASOUND|ULTRA SOUND|SONOGRAPHY)\b/i.test(u)) { icon = 'fa-wave-square'; accent = '#059669'; iconBg = '#d1fae5'; iconColor = '#047857'; }
            else if (/\b(DOPPLER)\b/i.test(u)) { icon = 'fa-heartbeat'; accent = '#db2777'; iconBg = '#fce7f3'; iconColor = '#be185d'; }
            else if (/\b(MAMMOGRAPHY|MAMMO)\b/i.test(u)) { icon = 'fa-ribbon'; accent = '#e11d48'; iconBg = '#ffe4e6'; iconColor = '#be123c'; }
            else if (/\b(X-?RAY|X RAY)\b/i.test(u)) { icon = 'fa-x-ray'; accent = '#0284c7'; iconBg = '#e0f2fe'; iconColor = '#0369a1'; }

            return `
                <div class="rad-test-card" style="display:flex; align-items:center; justify-content:space-between; gap:8px; background:#fff; border:1px solid #e2e8f0; border-left:3.5px solid ${accent}; border-radius:6px; padding:5px 9px; box-shadow:0 1px 2px rgba(0,0,0,0.02);">
                    <div style="display:flex; align-items:center; gap:7px; min-width:0; flex:1;">
                        <span style="display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; border-radius:4px; background:${iconBg}; color:${iconColor}; font-size:0.7rem; flex-shrink:0;">
                            <i class="fas ${icon}"></i>
                        </span>
                        <span style="font-size:0.82rem; font-weight:600; color:#1e293b; line-height:1.25;">${escHtml(name)}</span>
                    </div>
                    ${code ? `<span style="font-family:ui-monospace, monospace; font-size:0.68rem; font-weight:700; color:#0284c7; background:#f0f9ff; border:1px solid #bae6fd; border-radius:4px; padding:1px 5px; white-space:nowrap; flex-shrink:0;">${escHtml(code)}</span>` : ''}
                </div>
            `;
        }).join('') +
        `</div>`;
}

