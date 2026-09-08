/**
 * Radiology Notification & Live Polling Engine
 * Handles OPD/IPD test order notifications, sidebar badges, audio chimes, and popup toasts.
 * Ensures:
 * 1. Only today's notifications show popup messages and active counts.
 * 2. When a test is completed/reported, its notification and popup message automatically disappear.
 */
(function() {
    'use strict';

    let knownNotificationIds = new Set();
    window.activeOrderToastId = null;

    try {
        const stored = sessionStorage.getItem('known_rad_notifications');
        if (stored) {
            JSON.parse(stored).forEach(id => knownNotificationIds.add(String(id)));
        }
    } catch(e) {}

    function saveKnownIds() {
        try {
            sessionStorage.setItem('known_rad_notifications', JSON.stringify(Array.from(knownNotificationIds)));
        } catch(e) {}
    }

    function playNotificationChime() {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
            osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.12); // A5

            gain.gain.setValueAtTime(0.2, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.35);

            osc.connect(gain);
            gain.connect(ctx.destination);

            osc.start();
            osc.stop(ctx.currentTime + 0.35);
        } catch(e) {
            // Audio context may be restricted by autoplay policy
        }
    }

    window.markRadNotifReadAndGo = function(id, rawUrl) {
        if (!rawUrl) rawUrl = 'test_orders.php';
        let targetUrl = rawUrl;
        if (!targetUrl.startsWith('http') && !targetUrl.startsWith('/')) {
            if (targetUrl.startsWith('radiology_view/')) {
                targetUrl = '/GM_HMS/' + targetUrl;
            } else {
                targetUrl = '/GM_HMS/radiology_view/' + targetUrl;
            }
        }

        if (id) {
            fetch('/GM_HMS/api/radiology/notifications/' + encodeURIComponent(id) + '/read', { method: 'POST' })
                .then(() => { window.location.href = targetUrl; })
                .catch(() => { window.location.href = targetUrl; });
        } else {
            window.location.href = targetUrl;
        }
    };

    function processTestNotifications(rawNotifications) {
        // Strict Filter: ONLY today's notifications are active for popups and badges
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const todayStr = `${year}-${month}-${day}`;

        window.radTestNotificationsData = (Array.isArray(rawNotifications) ? rawNotifications : []).filter(notif => {
            if (!notif.created_at) return true;
            return notif.created_at.slice(0, 10) === todayStr;
        });

        // If a popup was showing for a test that has now been completed, automatically dismiss it
        const currentActiveIds = new Set(window.radTestNotificationsData.map(n => String(n.notification_id || n.id)));
        if (window.activeOrderToastId && !currentActiveIds.has(window.activeOrderToastId)) {
            if (typeof Swal !== 'undefined' && Swal.isVisible()) {
                Swal.close();
            }
            window.activeOrderToastId = null;
        }

        let opdCount = 0;
        let ipdCount = 0;

        window.radTestNotificationsData.forEach(notif => {
            const url = (notif.action_url || '').toLowerCase();
            const title = (notif.title || '').toLowerCase();
            if (url.includes('ipd') || title.includes('ipd')) {
                ipdCount++;
            } else {
                opdCount++;
            }
        });

        // 1. Update Sidebar OPD badge
        const opdBadge = document.getElementById('sidebar-opd-count');
        if (opdBadge) {
            if (opdCount > 0) {
                opdBadge.textContent = opdCount;
                opdBadge.style.display = 'inline-flex';
            } else {
                opdBadge.style.display = 'none';
            }
        }

        // 2. Update Sidebar IPD badge
        const ipdBadge = document.getElementById('sidebar-ipd-count');
        if (ipdBadge) {
            if (ipdCount > 0) {
                ipdBadge.textContent = ipdCount;
                ipdBadge.style.display = 'inline-flex';
            } else {
                ipdBadge.style.display = 'none';
            }
        }

        // 3. Update Sidebar Scan Orders (Parent) badge
        const totalOrders = opdCount + ipdCount;
        const totalOrdersBadge = document.getElementById('sidebar-orders-total-badge');
        if (totalOrdersBadge) {
            if (totalOrders > 0) {
                totalOrdersBadge.textContent = totalOrders;
                totalOrdersBadge.style.display = 'inline-flex';
            } else {
                totalOrdersBadge.style.display = 'none';
            }
        }

        // 4. Check for brand-new notifications to trigger Toast Popup & Audio
        let hasNewAlert = false;
        window.radTestNotificationsData.forEach(notif => {
            const notifId = String(notif.notification_id || notif.id);
            if (!knownNotificationIds.has(notifId)) {
                hasNewAlert = true;
                knownNotificationIds.add(notifId);
                window.activeOrderToastId = notifId;

                const isIpd = (notif.action_url && notif.action_url.toLowerCase().includes('ipd')) ||
                              (notif.title && notif.title.toLowerCase().includes('ipd'));
                const typeLabel = isIpd ? 'IPD Inpatient Scan' : 'OPD Scan Order';
                const targetUrl = notif.action_url || (isIpd ? 'ipd_test_orders.php' : 'test_orders.php');

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'info',
                        title: `<strong>${typeLabel}</strong>: ${notif.title || 'New Test Added'}`,
                        html: `<div style="font-size:0.8rem;color:#475569;margin-top:4px;">${notif.message || 'A new scan order has arrived.'}</div>`,
                        showConfirmButton: true,
                        confirmButtonText: '<i class="fas fa-external-link-alt"></i> View Order',
                        confirmButtonColor: '#1f6b4a',
                        showCancelButton: true,
                        cancelButtonText: 'Dismiss',
                        timer: 9000,
                        timerProgressBar: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.markRadNotifReadAndGo(notifId, targetUrl);
                        }
                    });
                } else if (typeof window.radToast === 'function') {
                    window.radToast(`[${typeLabel}] ${notif.title}: ${notif.message}`, 'info');
                }
            }
        });

        if (hasNewAlert) {
            playNotificationChime();
            saveKnownIds();
        }

        updateCombinedBadges();
    }

    function updateCombinedBadges() {
        const testCount = (window.radTestNotificationsData || []).length;
        const clearanceCount = (window.radClearanceData || []).length;
        const totalCount = testCount + clearanceCount;

        const badges = document.querySelectorAll('.rad-notif-badge');
        badges.forEach(badge => {
            if (totalCount > 0) {
                badge.textContent = totalCount;
                badge.style.display = 'inline-flex';
            } else {
                badge.style.display = 'none';
            }
        });

        const sidebarNotifBadge = document.getElementById('sidebar-notif-count');
        if (sidebarNotifBadge) {
            if (totalCount > 0) {
                sidebarNotifBadge.textContent = totalCount;
                sidebarNotifBadge.style.display = 'inline-flex';
            } else {
                sidebarNotifBadge.style.display = 'none';
            }
        }
    }

    async function fetchRadNotifications() {
        try {
            // 1. Fetch unread test notifications (OPD & IPD, today's only)
            const testPromise = fetch('/GM_HMS/api/radiology/notifications')
                .then(r => r.ok ? r.json() : { success: false, data: [] })
                .catch(() => ({ success: false, data: [] }));

            // 2. Fetch discharge clearance alerts
            const clearancePromise = fetch('/GM_HMS/api/discharge_clearance.php?action=pending_list&module=radiology')
                .then(r => r.ok ? r.json() : { success: false, data: [] })
                .catch(() => ({ success: false, data: [] }));

            const [testRes, clearanceRes] = await Promise.all([testPromise, clearancePromise]);

            if (testRes && testRes.success && Array.isArray(testRes.data)) {
                processTestNotifications(testRes.data);
            } else {
                processTestNotifications([]);
            }

            if (clearanceRes && clearanceRes.success && Array.isArray(clearanceRes.data)) {
                window.radClearanceData = clearanceRes.data;
            } else {
                window.radClearanceData = [];
            }

            updateCombinedBadges();

            if (typeof window.renderRadNotificationsList === 'function') {
                window.renderRadNotificationsList();
            }
        } catch(err) {
            console.error('Error fetching radiology notifications:', err);
        }
    }

    window.fetchRadNotifications = fetchRadNotifications;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fetchRadNotifications);
    } else {
        fetchRadNotifications();
    }

    // Poll every 12 seconds
    setInterval(fetchRadNotifications, 12000);
})();
