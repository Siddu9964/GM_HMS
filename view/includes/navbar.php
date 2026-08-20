<?php
// Determine base path relative to view folder
if (!isset($basePath)) {
    $basePath = '../';
}

// Fetch dynamic unread discharge clearances count
try {
    $notifConn = new mysqli('localhost', 'root', '', 'hmsc_basaveshwranagara');
    $notifCountResult = $notifConn->query("SELECT COUNT(*) as count FROM discharge_clearances WHERE overall_status != 'Completed'");
    $notifCountRow = $notifCountResult ? $notifCountResult->fetch_assoc() : null;
    $unreadNotifCount = $notifCountRow['count'] ?? 0;
    $notifConn->close();
} catch (Throwable $e) {
    $unreadNotifCount = 0;
}
?>
<!-- Top Navbar -->
<header style="background: var(--gm-bg); border-bottom: 1px solid var(--gm-glass-border); padding: 1.1rem 1.5rem; height: 80px; position: sticky; top: 0; z-index: 40; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
    <div style="display: flex; align-items: center; flex: 1;">
        <button id="sidebarToggle" class="text-gray-600 hover:text-gray-800 mr-4 lg:hidden" onclick="toggleSidebar()">
            <i class="fas fa-bars text-xl"></i>
        </button>
        <div class="relative flex-1 max-w-md" style="position: relative;">
            <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--gm-text-light);"></i>
            <input type="text" placeholder="Search patients, doctors, appointments..." 
                   style="width: 100%; padding: 0.6rem 1rem 0.6rem 2.5rem; border: 1px solid var(--gm-border); border-radius: var(--gm-r-md); background: var(--gm-bg); font-size: 0.9rem; color: var(--gm-text); outline: none; transition: border-color 0.2s;">
        </div>
    </div>
    
    <div style="display: flex; align-items: center; gap: 1.5rem;">
        <!-- Notifications Dropdown Wrapper -->
        <div style="position: relative; display: inline-block;" id="admin-notifications-wrapper">
            <button onclick="toggleAdminNotifications(event)" style="position: relative; color: var(--gm-text-light); transition: color 0.2s; background: none; border: none; cursor: pointer;" onmouseover="this.style.color='var(--gm-accent)'" onmouseout="this.style.color='var(--gm-text-light)'">
                <i class="fas fa-bell" style="font-size: 1.25rem;"></i>
                <span id="navbar-notif-badge" style="position: absolute; top: -4px; right: -6px; background: var(--gm-danger); color: white; font-size: 0.65rem; font-weight: 700; height: 16px; min-width: 16px; border-radius: 10px; display: flex; align-items: center; justify-content: center; border: 2px solid var(--gm-white); <?php echo ($unreadNotifCount > 0) ? '' : 'display: none;'; ?>"><?php echo $unreadNotifCount; ?></span>
            </button>
            
            <div id="adminNotificationsDropdown" style="display: none; position: absolute; top: 120%; right: 0; background: var(--gm-white); border-radius: var(--gm-r-md); box-shadow: var(--gm-shadow); min-width: 380px; max-width: 420px; border: 1px solid var(--gm-glass-border); overflow: hidden; z-index: 1000; padding: 14px; max-height: 480px; overflow-y: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin: 0 0 10px 0; padding-bottom: 8px; border-bottom: 1px solid var(--gm-glass-border);">
                    <h4 style="margin: 0; font-size: 0.9rem; font-weight: 800; color: var(--gm-text);"><i class="fas fa-clipboard-check text-primary mr-1"></i> Discharge Clearances</h4>
                    <span id="admin-notif-count-pill" style="font-size: 0.7rem; font-weight: 700; background: var(--gm-primary-light); color: var(--gm-primary); padding: 2px 8px; border-radius: 10px;">0 Active</span>
                </div>
                <div id="admin-notifications-list">
                    <p style="text-align: center; color: var(--gm-text-light); font-size: 0.8rem; padding: 15px; margin: 0;">Loading clearance alerts...</p>
                </div>
            </div>
        </div>
        
        <!-- Messages -->
        <button style="position: relative; color: var(--gm-text-light); transition: color 0.2s; background: none; border: none; cursor: pointer;" onmouseover="this.style.color='var(--gm-accent)'" onmouseout="this.style.color='var(--gm-text-light)'">
            <i class="fas fa-envelope" style="font-size: 1.25rem;"></i>
            <span style="position: absolute; top: -4px; right: -6px; background: var(--gm-primary); color: white; font-size: 0.65rem; font-weight: 700; height: 16px; min-width: 16px; border-radius: 10px; display: flex; align-items: center; justify-content: center; border: 2px solid var(--gm-white);">3</span>
        </button>
        
        <!-- User Profile -->
        <div class="relative" id="admin-profile-wrapper" style="position: relative; margin-left: 0.5rem; padding-left: 1.5rem; border-left: 1px solid var(--gm-glass-border);">
            <button onclick="toggleDropdown()" id="admin-profile-btn" style="display: flex; align-items: center; gap: 0.75rem; background: none; border: none; cursor: pointer; text-align: left;">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name'] ?? 'Admin'); ?>&background=1f6b4a&color=fff&bold=true" 
                     style="width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--gm-glass-border); object-fit: cover;">
                <div class="hidden md:block">
                    <p style="margin: 0; font-size: 0.9rem; font-weight: 700; color: var(--gm-text);"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin User'); ?></p>
                    <p style="margin: 0; font-size: 0.75rem; font-weight: 600; color: var(--gm-text-light);"><?php echo htmlspecialchars($_SESSION['designation'] ?? 'Administrator'); ?></p>
                </div>
                <i class="fas fa-chevron-down" style="color: var(--gm-text-light); font-size: 0.8rem; margin-left: 0.25rem;"></i>
            </button>
            
            <!-- Dropdown -->
            <div id="userDropdown" style="display: none; position: absolute; top: 110%; right: 0; background: var(--gm-white); border-radius: var(--gm-r-md); box-shadow: var(--gm-shadow); min-width: 220px; border: 1px solid var(--gm-glass-border); overflow: hidden; z-index: 1000;">
                <a href="javascript:void(0)" onclick="typeof toggleProfileModal === 'function' && toggleProfileModal()" style="display: flex; align-items: center; padding: 0.8rem 1rem; color: var(--gm-text); text-decoration: none; font-size: 0.9rem; font-weight: 500; transition: background 0.2s;" onmouseover="this.style.background='var(--gm-bg)'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-user" style="width: 20px; color: var(--gm-text-light); margin-right: 0.5rem;"></i> Profile
                </a>
                <a href="javascript:void(0)" onclick="toggleChangePasswordModal()" style="display: flex; align-items: center; padding: 0.8rem 1rem; color: var(--gm-text); text-decoration: none; font-size: 0.9rem; font-weight: 500; transition: background 0.2s;" onmouseover="this.style.background='var(--gm-bg)'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-key" style="width: 20px; color: var(--gm-text-light); margin-right: 0.5rem;"></i> Change Password
                </a>
                <div style="height: 1px; background: var(--gm-glass-border); margin: 0.25rem 0;"></div>
                <a href="<?php echo $basePath; ?>logout.php" style="display: flex; align-items: center; padding: 0.8rem 1rem; color: var(--gm-danger); text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: background 0.2s;" onmouseover="this.style.background='var(--gm-danger-light)'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-sign-out-alt" style="width: 20px; margin-right: 0.5rem;"></i> Logout
                </a>
            </div>
        </div>
    </div>
</header>

<!-- Change Password Modal -->
<div id="adminChangePasswordModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); backdrop-filter:blur(5px); z-index:10002; align-items:center; justify-content:center;">
    <div style="background:white; width:100%; max-width:400px; border-radius:20px; overflow:hidden; position:relative; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">
        <div style="height:100px; background:#1f6b4a; position:relative;">
            <button onclick="toggleChangePasswordModal()" style="position:absolute; top:15px; right:15px; background:rgba(255,255,255,0.2); border:none; color:white; font-size:24px; width:32px; height:32px; border-radius:50%; cursor:pointer; line-height:1;">&times;</button>
            <h3 style="color:white; margin:0; position:absolute; bottom:15px; left:30px; font-size:1.25rem; font-weight:700;">Change Password</h3>
        </div>
        <div style="padding:30px;">
            <form id="admin-change-password-form">
                <div style="margin-bottom:15px; text-align:left;">
                    <label style="display:block; font-size:0.875rem; font-weight:600; color:#374151; margin-bottom:5px;">Current Password</label>
                    <div style="position:relative;">
                        <input type="password" name="current_password" id="admin-pw-current" style="width:100%; padding:10px 2.5rem 10px 14px; border:2px solid #e5e7eb; border-radius:10px; font-size:0.875rem; outline:none; box-sizing:border-box;" required>
                        <button type="button" onclick="togglePwVis('admin-pw-current','admin-eye-cur')" tabindex="-1" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;">
                            <i id="admin-eye-cur" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div style="margin-bottom:15px; text-align:left;">
                    <label style="display:block; font-size:0.875rem; font-weight:600; color:#374151; margin-bottom:5px;">New Password</label>
                    <div style="position:relative;">
                        <input type="password" name="new_password" id="admin-pw-new" style="width:100%; padding:10px 2.5rem 10px 14px; border:2px solid #e5e7eb; border-radius:10px; font-size:0.875rem; outline:none; box-sizing:border-box;" minlength="8" required>
                        <button type="button" onclick="togglePwVis('admin-pw-new','admin-eye-new')" tabindex="-1" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;">
                            <i id="admin-eye-new" class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small style="color:#64748b; font-size:0.75rem;">Minimum 8 characters</small>
                </div>
                <div id="admin-pw-message" style="display:none; margin-bottom:10px; padding:10px; border-radius:8px; font-size:0.875rem;"></div>
                <div style="display:flex; gap:15px; margin-top:20px;">
                    <button type="button" onclick="toggleChangePasswordModal()" style="flex:1; padding:12px; border-radius:12px; font-weight:600; font-size:14px; border:none; cursor:pointer; background:#e9ecef; color:#495057;">Cancel</button>
                    <button type="submit" id="admin-pw-submit-btn" style="flex:1; padding:12px; border-radius:12px; font-weight:600; font-size:14px; border:none; cursor:pointer; background:#1f6b4a; color:white;">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Base dropdown logic */
#userDropdown.show {
    display: block !important;
}

@media (max-width: 1023px) {
    #sidebarToggle { display: block !important; }
}
@media (min-width: 1024px) {
    #sidebarToggle { display: none !important; }
}
</style>

<script>
<?php
// Dynamically calculate the project root URL relative to the web root
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$fullPath = str_replace('\\', '/', dirname(__DIR__, 2));
$projectRoot = str_ireplace($docRoot, '', $fullPath);
$apiBase = rtrim($projectRoot, '/') . '/api/';
?>
const API_BASE = '<?php echo $apiBase; ?>';

function togglePwVis(inputId, iconId) {
    var input = document.getElementById(inputId);
    var icon  = document.getElementById(iconId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
function toggleDropdown() {
    document.getElementById('userDropdown').classList.toggle('show');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('#admin-profile-wrapper')) {
        var dd = document.getElementById('userDropdown');
        if (dd) dd.classList.remove('show');
    }
});

function toggleChangePasswordModal() {
    var modal = document.getElementById('adminChangePasswordModal');
    var isVisible = modal.style.display === 'flex';
    modal.style.display = isVisible ? 'none' : 'flex';
    if (!isVisible) {
        document.getElementById('admin-change-password-form').reset();
        var msg = document.getElementById('admin-pw-message');
        msg.style.display = 'none';
        document.getElementById('userDropdown').classList.remove('show');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('admin-change-password-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        var btn = document.getElementById('admin-pw-submit-btn');
        var msgDiv = document.getElementById('admin-pw-message');
        var originalText = btn.textContent;
        btn.textContent = 'Updating...';
        btn.disabled = true;
        msgDiv.style.display = 'none';

        try {
            var data = {
                current_password: document.getElementById('admin-pw-current').value,
                new_password: document.getElementById('admin-pw-new').value
            };

            // Use the dynamically calculated API base
            var response = await fetch(API_BASE + 'auth/change-password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            var contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Invalid server response');
            }

            var json = await response.json();

            if (json.success) {
                msgDiv.style.display = 'block';
                msgDiv.style.background = '#d1fae5';
                msgDiv.style.color = '#065f46';
                msgDiv.textContent = 'Password updated! Redirecting to login...';
                setTimeout(function() { window.location.href = '<?php echo $basePath; ?>logout.php'; }, 1500);
            } else {
                msgDiv.style.display = 'block';
                msgDiv.style.background = '#fee2e2';
                msgDiv.style.color = '#991b1b';
                msgDiv.textContent = json.message || json.error || 'Failed to update password';
            }
        } catch (error) {
            msgDiv.style.display = 'block';
            msgDiv.style.background = '#fee2e2';
            msgDiv.style.color = '#991b1b';
            msgDiv.textContent = 'An error occurred. Please try again.';
        } finally {
            btn.textContent = originalText;
            btn.disabled = false;
        }
    });
});

function toggleAdminNotifications(e) {
    e.stopPropagation();
    var dropdown = document.getElementById('adminNotificationsDropdown');
    var isShowing = dropdown.style.display === 'block';
    dropdown.style.display = isShowing ? 'none' : 'block';
    if (!isShowing) {
        fetchAdminDischargeClearances();
    }
}

async function fetchAdminDischargeClearances() {
    var list = document.getElementById('admin-notifications-list');
    var badge = document.getElementById('navbar-notif-badge');
    var pill = document.getElementById('admin-notif-count-pill');
    
    try {
        var response = await fetch('/GM_HMS/api/discharge_clearance.php?action=pending_list&module=admin');
        var res = await response.json();
        
        if (res.success && Array.isArray(res.data) && res.data.length > 0) {
            var count = res.data.length;
            if (badge) {
                badge.textContent = count;
                badge.style.display = 'flex';
            }
            if (pill) {
                pill.textContent = count + ' Active';
            }
            
            var html = '';
            res.data.forEach(function(item) {
                var isAllCleared = item.overall_status === 'All Cleared';
                var hasQueries = item.overall_status === 'Queries Raised';
                
                var borderCol = isAllCleared ? '#86efac' : hasQueries ? '#fca5a5' : '#fde68a';
                var bgCol = isAllCleared ? '#f0fdf4' : hasQueries ? '#fef2f2' : '#fffbeb';
                var statusText = isAllCleared ? '🎉 All Cleared' : hasQueries ? '⚠️ Query Active' : '⏳ Clearance Pending';
                var statusBadgeCol = isAllCleared ? '#15803d' : hasQueries ? '#b91c1c' : '#b45309';
                
                var rCol = item.reception_status === 'Approved' ? '#15803d' : item.reception_status === 'Query' ? '#dc2626' : '#d97706';
                var pCol = item.pharmacy_status === 'Approved' ? '#15803d' : item.pharmacy_status === 'Query' ? '#dc2626' : '#d97706';
                var lCol = item.lab_status === 'Approved' ? '#15803d' : item.lab_status === 'Query' ? '#dc2626' : '#d97706';
                
                var querySnippet = '';
                if (item.reception_query) querySnippet += '<div><small><strong>Reception Query:</strong> ' + item.reception_query + '</small></div>';
                if (item.pharmacy_query) querySnippet += '<div><small><strong>Pharmacy Query:</strong> ' + item.pharmacy_query + '</small></div>';
                if (item.lab_query) querySnippet += '<div><small><strong>Lab Query:</strong> ' + item.lab_query + '</small></div>';
                
                html += `
                    <div id="clearance-card-${item.clearance_id}" style="padding: 10px 12px; border-radius: 10px; background: ${bgCol}; border: 1.5px solid ${borderCol}; margin-bottom: 10px; font-size: 0.78rem; text-align: left;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
                            <div>
                                <strong style="font-size: 0.88rem; color: #1e293b;">${item.patient_name || 'Patient'}</strong>
                                <div style="font-size: 0.72rem; color: #64748b;">${item.bed_info || 'Ward'} • IP: ${item.admission_id}</div>
                            </div>
                            <span style="font-size: 0.7rem; font-weight: 800; color: ${statusBadgeCol}; padding: 2px 6px; border-radius: 6px; background: rgba(255,255,255,0.7);">${statusText}</span>
                        </div>
                        
                        <!-- Department Matrix -->
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px; margin: 6px 0; background: #ffffff; padding: 6px; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 0.68rem; text-align: center;">
                            <div><span style="color:#64748b;">Reception</span><br><strong style="color:${rCol};">${item.reception_status}</strong></div>
                            <div><span style="color:#64748b;">Pharmacy</span><br><strong style="color:${pCol};">${item.pharmacy_status}</strong></div>
                            <div><span style="color:#64748b;">Laboratory</span><br><strong style="color:${lCol};">${item.lab_status}</strong></div>
                        </div>
                        
                        ${querySnippet ? '<div style="margin-top:4px; padding:4px 6px; background:#fee2e2; color:#991b1b; border-radius:4px; font-size:0.7rem;">' + querySnippet + '</div>' : ''}
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
                            <span style="font-size: 0.68rem; color: #94a3b8;"><i class="fas fa-user-nurse"></i> ${item.nurse_name || 'Nurse'}</span>
                            ${isAllCleared ? 
                                `<button type="button" onclick="confirmAdminDischarge('${item.clearance_id}', '${item.admission_id}')" style="padding: 4px 12px; font-size: 0.72rem; font-weight: 800; background: #16a34a; color: #ffffff; border: none; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="fas fa-check-double"></i> Confirm Final Discharge
                                </button>` :
                                `<button type="button" onclick="window.location.href='/GM_HMS/view/ipd_billing.php?admission_id=${encodeURIComponent(item.admission_id)}'" style="padding: 3px 8px; font-size: 0.7rem; font-weight: 700; background: #ffffff; color: #1f6b4a; border: 1px solid #1f6b4a; border-radius: 6px; cursor: pointer;">
                                    <i class="fas fa-file-invoice-dollar"></i> View Billing
                                </button>`
                            }
                        </div>
                    </div>
                `;
            });
            if (list) list.innerHTML = html;
        } else {
            if (badge) badge.style.display = 'none';
            if (pill) pill.textContent = '0 Active';
            if (list) list.innerHTML = '<p style="text-align: center; color: var(--gm-text-light); font-size: 0.8rem; padding: 15px; margin: 0;">No active discharge clearances</p>';
        }
    } catch (err) {
        console.error('Error fetching admin clearances:', err);
        if (list) list.innerHTML = '<p style="text-align: center; color: #ef4444; font-size: 0.75rem; margin: 0;">Error loading alerts</p>';
    }
}

async function confirmAdminDischarge(clearanceId, admissionId) {
    if (!confirm('Confirm final discharge clearance for this patient? All department approvals will be finalized.')) return;
    
    var payload = {
        action: 'admin_confirm',
        clearance_id: clearanceId,
        admission_id: admissionId
    };
    
    try {
        var res = await fetch('/GM_HMS/api/discharge_clearance.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        var data = await res.json();
        if (data.success) {
            alert('✅ ' + (data.message || 'Patient discharge finalized by Admin!'));
            fetchAdminDischargeClearances();
        } else {
            alert('Error: ' + (data.message || 'Failed to finalize'));
        }
    } catch (e) {
        console.error(e);
        alert('Network error finalizing discharge.');
    }
}

// Click outside dropdown handler
document.addEventListener('click', function(e) {
    var wrapper = document.getElementById('admin-notifications-wrapper');
    var dropdown = document.getElementById('adminNotificationsDropdown');
    if (wrapper && dropdown && !wrapper.contains(e.target)) {
        dropdown.style.display = 'none';
    }
});

document.addEventListener('DOMContentLoaded', function() {
    fetchAdminDischargeClearances();
    setInterval(fetchAdminDischargeClearances, 12000);
});
</script>
