document.addEventListener('DOMContentLoaded', () => {
    let unreadCount = 0;
    
    // Load known IDs from sessionStorage
    let knownNotificationIds = new Set();
    try {
        const stored = sessionStorage.getItem('known_lab_notifications');
        if (stored) {
            JSON.parse(stored).forEach(id => knownNotificationIds.add(id));
        }
    } catch (e) {}
    
    // Function to save known IDs
    function saveKnownIds() {
        sessionStorage.setItem('known_lab_notifications', JSON.stringify(Array.from(knownNotificationIds)));
    }
    
    // Polling interval (15 seconds)
    const POLLING_INTERVAL = 15000;
    
    function fetchNotifications() {
        const url = '/GM_HMS/api/laboratory/notifications';
        
        fetch(url)
            .then(res => {
                if(!res.ok) throw new Error('API Error');
                return res.json();
            })
            .then(data => {
                if(data.success && Array.isArray(data.data)) {
                    processNotifications(data.data);
                }
            })
            .catch(err => {
                // Ignore silent polling errors
            });
    }
    
    function processNotifications(notifications) {
        unreadCount = notifications.length;
        
        // Update badges
        const badges = document.querySelectorAll('.lab-notif-badge');
        badges.forEach(badge => {
            if (unreadCount > 0) {
                badge.textContent = unreadCount;
                badge.style.display = 'inline-flex';
            } else {
                badge.style.display = 'none';
            }
        });
        
        // Check for new notifications to show toast
        notifications.forEach(notif => {
            if (!knownNotificationIds.has(notif.notification_id)) {
                // Show toast for new notification
                if (window.lisToast) {
                    const type = notif.action_url.includes('ipd') ? 'IPD' : 'OPD';
                    const msg = `<strong>${type} Notification</strong><br>${notif.title}<br><small>${notif.message}</small>`;
                    // Create a clickable toast
                    lisToast(msg, 'info', 5000);
                }
                
                knownNotificationIds.add(notif.notification_id);
                saveKnownIds();
            }
        });
        
        // Render dropdown list
        renderDropdown(notifications);
    }
    
    function renderDropdown(notifications) {
        const container = document.getElementById('lab-notif-dropdown-list');
        if (!container) return;
        
        if (notifications.length === 0) {
            container.innerHTML = '<div class="p-3 text-center text-muted" style="font-size:0.8rem;">No unread notifications</div>';
            return;
        }
        
        container.innerHTML = notifications.map(notif => {
            const type = notif.action_url.includes('ipd') ? 'IPD' : 'OPD';
            const badgeColor = type === 'IPD' ? 'bg-danger' : 'bg-primary';
            return `
                <div class="dropdown-item" style="cursor:pointer; border-bottom:1px solid #f0f0f0; padding:10px; white-space:normal;" 
                     onclick="markReadAndRedirect('${notif.notification_id}', '${notif.action_url}')">
                    <div style="font-weight:600; font-size:0.85rem; margin-bottom:4px;">
                        <span class="badge ${badgeColor}" style="margin-right:5px;">${type}</span>
                        ${notif.title}
                    </div>
                    <div style="font-size:0.75rem; color:#666;">
                        ${notif.message}
                    </div>
                </div>
            `;
        }).join('');
    }
    
    window.markReadAndRedirect = function(id, url) {
        fetch(`/GM_HMS/api/laboratory/notifications/${id}/read`, { method: 'POST' })
            .then(() => {
                window.location.href = url;
            })
            .catch(err => {
                window.location.href = url;
            });
    };
    
    // Initial fetch
    fetchNotifications();
    
    // Start polling
    setInterval(fetchNotifications, POLLING_INTERVAL);
});
