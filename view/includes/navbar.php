<?php
// Determine base path relative to view folder
if (!isset($basePath)) {
    $basePath = '../';
}

// Fetch dynamic unread discharge clearances count
try {
    if (class_exists('GM_HMS\Database\SecureDatabase')) {
        $db = GM_HMS\Database\SecureDatabase::getInstance();
        $notifConn = $db->getConnection();
        $notifCountResult = $notifConn->query("SELECT COUNT(*) as count FROM discharge_clearances WHERE overall_status != 'Completed'");
        $notifCountRow = $notifCountResult ? $notifCountResult->fetch_assoc() : null;
        $unreadNotifCount = $notifCountRow['count'] ?? 0;
    } else {
        $notifConn = new mysqli('localhost', 'root', '', 'hmsc_basaveshwranagara');
        $notifCountResult = $notifConn->query("SELECT COUNT(*) as count FROM discharge_clearances WHERE overall_status != 'Completed'");
        $notifCountRow = $notifCountResult ? $notifCountResult->fetch_assoc() : null;
        $unreadNotifCount = $notifCountRow['count'] ?? 0;
        $notifConn->close();
    }
} catch (Throwable $e) {
    $unreadNotifCount = 0;
}
?>
<!-- Top Navbar -->
<header class="bg-[var(--gm-bg)] border-b border-[var(--gm-glass-border)] px-3 sm:px-6 py-2 sm:py-3 h-16 sm:h-20 sticky top-0 z-40 flex items-center justify-between shadow-xs transition-all">
    <div class="flex items-center flex-1 min-w-0 mr-2 sm:mr-4">
        <button id="sidebarToggle" class="text-gray-600 hover:text-gray-800 p-2 mr-2 lg:hidden rounded-lg hover:bg-gray-100 transition-colors shrink-0" onclick="toggleSidebar()" aria-label="Toggle Sidebar Menu">
            <i class="fas fa-bars text-lg sm:text-xl"></i>
        </button>
        <div class="relative flex-1 max-w-xs sm:max-w-md">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs sm:text-sm"></i>
            <input type="text" placeholder="Search patients, doctors..." 
                   class="w-full pl-8 sm:pl-9 pr-3 py-1.5 sm:py-2 text-xs sm:text-sm border border-[var(--gm-border)] rounded-[var(--gm-r-md)] bg-[var(--gm-bg)] text-[var(--gm-text)] outline-none focus:border-emerald-600 transition-colors">
        </div>
    </div>
    
    <div class="flex items-center gap-2 sm:gap-4 shrink-0">
        <!-- Notifications Dropdown Wrapper -->
        <div class="relative inline-block" id="admin-notifications-wrapper">
            <button onclick="toggleAdminNotifications(event)" class="relative p-2 text-gray-500 hover:text-emerald-700 transition-colors bg-transparent border-none cursor-pointer rounded-lg hover:bg-gray-100" aria-label="Notifications">
                <i class="fas fa-bell text-base sm:text-lg"></i>
                <span id="navbar-notif-badge" class="absolute top-1 right-1 bg-red-500 text-white text-[10px] font-bold h-4 min-w-[16px] px-1 rounded-full flex items-center justify-center border-2 border-white <?php echo ($unreadNotifCount > 0) ? '' : 'hidden'; ?>"><?php echo $unreadNotifCount; ?></span>
            </button>
            
            <div id="adminNotificationsDropdown" class="hidden absolute top-full mt-2 right-0 bg-white rounded-xl shadow-xl w-[90vw] max-w-[380px] border border-gray-200 overflow-hidden z-[1000] p-3 max-h-[80vh] overflow-y-auto">
                <div class="flex justify-between items-center pb-2 mb-2 border-b border-gray-100">
                    <h4 class="m-0 text-xs sm:text-sm font-bold text-gray-800 flex items-center gap-1.5"><i class="fas fa-clipboard-check text-emerald-600"></i> Discharge Clearances</h4>
                    <span id="admin-notif-count-pill" class="text-[10px] font-bold bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full">0 Active</span>
                </div>
                <div id="admin-notifications-list">
                    <p class="text-center text-gray-400 text-xs py-4 m-0">Loading clearance alerts...</p>
                </div>
            </div>
        </div>
        
        <!-- Messages -->
        <button class="relative p-2 text-gray-500 hover:text-emerald-700 transition-colors bg-transparent border-none cursor-pointer rounded-lg hover:bg-gray-100 hidden xs:block" aria-label="Messages">
            <i class="fas fa-envelope text-base sm:text-lg"></i>
            <span class="absolute top-1 right-1 bg-emerald-600 text-white text-[10px] font-bold h-4 min-w-[16px] px-1 rounded-full flex items-center justify-center border-2 border-white">3</span>
        </button>
        
        <!-- Active Hospital Branch Badge -->
        <span class="hidden md:inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-full text-xs font-bold shadow-xs">
            <i class="fas fa-hospital text-emerald-600 text-[11px]"></i>
            <span class="truncate max-w-[120px]"><?php echo ucfirst(htmlspecialchars($_SESSION['branch'] ?? $_SESSION['hospital_branch'] ?? 'Basaveshwaranagar')); ?></span>
        </span>
        
        <!-- User Profile -->
        <div class="relative pl-2 sm:pl-3 border-l border-gray-200" id="admin-profile-wrapper">
            <button onclick="toggleDropdown()" id="admin-profile-btn" class="flex items-center gap-2 bg-transparent border-none cursor-pointer text-left p-1 rounded-lg hover:bg-gray-100 transition-colors">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name'] ?? 'Admin'); ?>&background=1f6b4a&color=fff&bold=true" 
                     class="w-8 h-8 sm:w-9 sm:h-9 rounded-full border border-gray-200 object-cover shrink-0">
                <div class="hidden lg:block">
                    <p class="m-0 text-xs font-bold text-gray-800 truncate max-w-[110px]"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin User'); ?></p>
                    <p class="m-0 text-[10px] font-medium text-gray-500 truncate max-w-[110px]"><?php echo htmlspecialchars($_SESSION['designation'] ?? 'Administrator'); ?></p>
                </div>
                <i class="fas fa-chevron-down text-gray-400 text-[10px] hidden sm:inline"></i>
            </button>
            
            <!-- Dropdown -->
            <div id="userDropdown" class="hidden absolute top-full mt-2 right-0 bg-white rounded-xl shadow-xl w-48 border border-gray-200 overflow-hidden z-[1000] py-1">
                <a href="javascript:void(0)" onclick="typeof toggleProfileModal === 'function' && toggleProfileModal()" class="flex items-center px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 transition-colors font-medium">
                    <i class="fas fa-user w-5 text-gray-400"></i> Profile
                </a>
                <a href="javascript:void(0)" onclick="toggleChangePasswordModal()" class="flex items-center px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 transition-colors font-medium">
                    <i class="fas fa-key w-5 text-gray-400"></i> Change Password
                </a>
                <div class="h-px bg-gray-100 my-1"></div>
                <a href="<?php echo $basePath; ?>logout.php" class="flex items-center px-3 py-2 text-xs text-red-600 hover:bg-red-50 transition-colors font-bold">
                    <i class="fas fa-sign-out-alt w-5"></i> Logout
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

<!-- Universal Center Feedback / Success Popup Modal -->
<div id="centerFeedbackModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(5px); z-index: 100000; align-items: center; justify-content: center;">
  <div style="background: #ffffff; border-radius: 20px; max-width: 440px; width: 90%; overflow: hidden; box-shadow: 0 25px 70px rgba(0,0,0,0.35); text-align: center; border: 1.5px solid #e2e8f0;">
    <div id="center-feedback-header" style="background: #1f6b4a; padding: 22px 20px 16px; color: #ffffff;">
      <div id="center-feedback-icon" style="width: 52px; height: 52px; border-radius: 50%; background: rgba(255,255,255,0.22); display: inline-flex; align-items: center; justify-content: center; font-size: 1.6rem; margin-bottom: 8px;">
        <i class="fas fa-check"></i>
      </div>
      <div id="center-feedback-title" style="font-size: 1.15rem; font-weight: 800;">Clearance Updated</div>
    </div>
    <div style="padding: 22px 24px;">
      <p id="center-feedback-msg" style="font-size: 0.92rem; color: #334155; line-height: 1.5; margin: 0 0 20px 0; font-weight: 600;">
        Clearance status updated successfully.
      </p>
      <button type="button" id="center-feedback-btn" onclick="closeCenterFeedbackModal()" style="padding: 10px 32px; background: #1f6b4a; color: #ffffff; font-weight: 800; font-size: 0.88rem; border: none; border-radius: 10px; cursor: pointer; min-width: 120px; box-shadow: 0 4px 14px rgba(31,107,74,0.3);">
        OK
      </button>
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

var centerFeedbackTimer = null;
function showCenterFeedback(msg, type, title) {
  type = type || 'success';
  title = title || '';
  var modal = document.getElementById('centerFeedbackModal');
  if (!modal) {
    alert(msg);
    return;
  }
  var header = document.getElementById('center-feedback-header');
  var icon = document.getElementById('center-feedback-icon');
  var titleEl = document.getElementById('center-feedback-title');
  var msgEl = document.getElementById('center-feedback-msg');
  var btn = document.getElementById('center-feedback-btn');

  if (type === 'success') {
    header.style.background = '#1f6b4a';
    icon.innerHTML = '<i class="fas fa-check"></i>';
    titleEl.textContent = title || 'Discharge Finalized';
    if (btn) btn.style.background = '#1f6b4a';
  } else if (type === 'error') {
    header.style.background = '#dc2626';
    icon.innerHTML = '<i class="fas fa-times"></i>';
    titleEl.textContent = title || 'Action Failed';
    if (btn) btn.style.background = '#dc2626';
  } else {
    header.style.background = '#d97706';
    icon.innerHTML = '<i class="fas fa-exclamation"></i>';
    titleEl.textContent = title || 'Attention';
    if (btn) btn.style.background = '#d97706';
  }

  var cleanMsg = (msg || '').replace(/^[✅❌⚠️\s]+/, '');
  msgEl.textContent = cleanMsg;

  modal.style.display = 'flex';

  if (centerFeedbackTimer) clearTimeout(centerFeedbackTimer);
  centerFeedbackTimer = setTimeout(function() {
    closeCenterFeedbackModal();
  }, 6000);
}

function closeCenterFeedbackModal() {
  var modal = document.getElementById('centerFeedbackModal');
  if (modal) modal.style.display = 'none';
  if (centerFeedbackTimer) clearTimeout(centerFeedbackTimer);
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
            showCenterFeedback(data.message || 'Patient discharge finalized by Admin!', 'success', 'Discharge Finalized');
            fetchAdminDischargeClearances();
        } else {
            showCenterFeedback(data.message || 'Failed to finalize discharge', 'error', 'Error');
        }
    } catch (e) {
        console.error(e);
        showCenterFeedback('Network error finalizing discharge.', 'error', 'Network Error');
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
