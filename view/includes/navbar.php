<?php
// Determine base path relative to view folder
if (!isset($basePath)) {
    $basePath = '../';
}

// Fetch dynamic unread discharge notifications count
try {
    $notifConn = new mysqli('localhost', 'root', '', 'hmsc_basaveshwranagara');
    $notifCountResult = $notifConn->query("SELECT COUNT(*) as count FROM discharge_notifications WHERE status = 'Pending'");
    $notifCountRow = $notifCountResult->fetch_assoc();
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
            
            <div id="adminNotificationsDropdown" style="display: none; position: absolute; top: 120%; right: 0; background: var(--gm-white); border-radius: var(--gm-r-md); box-shadow: var(--gm-shadow); min-width: 300px; border: 1px solid var(--gm-glass-border); overflow: hidden; z-index: 1000; padding: 12px; max-height: 350px; overflow-y: auto;">
                <h4 style="margin: 0 0 10px 0; padding-bottom: 8px; border-bottom: 1px solid var(--gm-glass-border); font-size: 0.85rem; font-weight: 700; color: var(--gm-text);">Discharge Notifications</h4>
                <div id="admin-notifications-list">
                    <?php
                    try {
                        $notifConn = new mysqli('localhost', 'root', '', 'hmsc_basaveshwranagara');
                        $notifListResult = $notifConn->query("SELECT * FROM discharge_notifications ORDER BY created_at DESC LIMIT 5");
                        if ($notifListResult && $notifListResult->num_rows > 0) {
                            while ($nRow = $notifListResult->fetch_assoc()) {
                                $statusColor = $nRow['status'] === 'Pending' ? '#ef4444' : '#10b981';
                                $statusBg = $nRow['status'] === 'Pending' ? '#fef2f2' : '#ecfdf5';
                                echo "<div id='notif-item-{$nRow['id']}' style='padding: 8px; border-radius: 8px; background: {$statusBg}; margin-bottom: 8px; border: 1px solid var(--gm-glass-border); font-size: 0.75rem; text-align: left;'>";
                                echo "<div style='display: flex; justify-content: space-between; margin-bottom: 4px;'><strong style='color: var(--gm-text);'>Admission: {$nRow['admission_id']}</strong><span style='color: {$statusColor}; font-weight: 700;'>{$nRow['status']}</span></div>";
                                echo "<p style='margin: 0; color: var(--gm-text-light); line-height: 1.3;'>{$nRow['message']}</p>";
                                if ($nRow['status'] === 'Pending') {
                                    echo "<button onclick='dismissAdminNotification(event, {$nRow['id']})' style='margin-top: 6px; padding: 2px 8px; font-size: 0.65rem; border-radius: 4px; background: #e2e8f0; border: none; cursor: pointer; font-weight: 600;'>Clear</button>";
                                }
                                echo "</div>";
                            }
                        } else {
                            echo "<p style='text-align: center; color: var(--gm-text-light); font-size: 0.75rem; padding: 10px; margin: 0;'>No notifications</p>";
                        }
                        $notifConn->close();
                    } catch (Throwable $e) {
                        echo "<p style='text-align: center; color: var(--gm-text-light); font-size: 0.75rem; margin: 0;'>Error loading alerts</p>";
                    }
                    ?>
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
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
}

async function dismissAdminNotification(e, id) {
    e.stopPropagation();
    if (!confirm('Mark notification as cleared?')) return;
    
    var formData = new FormData();
    formData.append('id', id);
    
    try {
        var response = await fetch('<?php echo $basePath; ?>view/api/dismiss_discharge_notification.php', {
            method: 'POST',
            body: formData
        });
        var result = await response.json();
        if (result.success) {
            var item = document.getElementById('notif-item-' + id);
            if (item) {
                item.style.opacity = '0.5';
                item.style.background = '#f1f5f9';
                var btn = item.querySelector('button');
                if (btn) btn.remove();
            }
            // Update badge count
            var badge = document.getElementById('navbar-notif-badge');
            if (badge) {
                var currentCount = parseInt(badge.textContent) || 0;
                if (currentCount > 1) {
                    badge.textContent = currentCount - 1;
                } else {
                    badge.style.display = 'none';
                }
            }
            // Update sidebar badge if it exists
            var sidebarBadge = document.getElementById('sidebar-notif-badge');
            if (sidebarBadge) {
                var currentCount = parseInt(sidebarBadge.textContent) || 0;
                if (currentCount > 1) {
                    sidebarBadge.textContent = currentCount - 1;
                } else {
                    sidebarBadge.style.display = 'none';
                }
            }
        } else {
            alert('Failed to clear notification: ' + result.message);
        }
    } catch (err) {
        console.error(err);
        alert('Network error clearing notification.');
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
</script>
