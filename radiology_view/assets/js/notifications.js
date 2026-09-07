(function() {
    function refreshNotifications() {
        if (typeof window.fetchRadNotifications === 'function') {
            window.fetchRadNotifications();
        } else {
            const url = '/GM_HMS/api/discharge_clearance.php?action=pending_list&module=radiology';
            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (data.success && Array.isArray(data.data)) {
                        const count = data.data.length;
                        const badges = document.querySelectorAll('.rad-notif-badge');
                        badges.forEach(badge => {
                            if (count > 0) {
                                badge.textContent = count;
                                badge.style.display = 'inline-flex';
                            } else {
                                badge.style.display = 'none';
                            }
                        });
                    }
                })
                .catch(() => {});
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', refreshNotifications);
    } else {
        refreshNotifications();
    }
    setInterval(refreshNotifications, 15000);
})();
