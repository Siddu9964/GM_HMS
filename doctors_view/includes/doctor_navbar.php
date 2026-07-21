<style>
/* 
   UNIVERSAL NAVBAR STYLES
*/

#universal-doctor-navbar {
    background: #f3efe6 !important;
    backdrop-filter: blur(12px) !important;
    -webkit-backdrop-filter: blur(12px) !important;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06) !important;
    padding: 0 1.75rem !important;
    height: 80px !important;
    box-shadow: 0 1px 0 rgba(0, 0, 0, .06) !important;
    position: sticky !important;
    top: 0 !important;
    z-index: 999 !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
    box-sizing: border-box !important;
    width: 100% !important;
}

#universal-doctor-navbar *:not(i) {
    font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
    box-sizing: border-box !important;
}

/* Typography Overrides */
#universal-doctor-navbar h2#page-title {
    font-size: 1.5rem !important;
    font-weight: 700 !important;
    color: #111827 !important; /* gray-900 */
    margin: 0 !important;
    line-height: 1.2 !important;
}

#universal-doctor-navbar p#page-subtitle {
    font-size: 0.875rem !important;
    color: #6b7280 !important; /* gray-500 */
    margin: 0 !important;
    line-height: 1.5 !important;
}

/* Button Overrides - STRICTLY enforce Teal */
#universal-doctor-navbar .btn {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0.5rem !important;
    padding: 0.5rem 1rem !important;
    font-size: 0.875rem !important;
    font-weight: 500 !important;
    border-radius: 0.5rem !important;
    border: 1px solid transparent !important;
    cursor: pointer !important;
    transition: all 200ms ease !important;
    text-decoration: none !important;
    white-space: nowrap !important;
    line-height: 1.5 !important;
}

/* Primary Button (Teal) */
#universal-doctor-navbar .btn-primary {
    background-color: #1f6b4a !important; /* Medical Teal */
    border-color: #1f6b4a !important;
    color: #ffffff !important;
    box-shadow: none !important;
}

#universal-doctor-navbar .btn-primary:hover {
    background-color: #144d34 !important; /* Darker Teal */
    border-color: #144d34 !important;
    transform: translateY(-1px) !important;
    color: #ffffff !important;
}

/* Outline Button (White/Teal) */
#universal-doctor-navbar .btn-outline {
    background-color: transparent !important;
    border: 2px solid #1f6b4a !important;
    color: #1f6b4a !important;
}

#universal-doctor-navbar .btn-outline:hover {
    background-color: #1f6b4a !important;
    color: #ffffff !important;
}

/* Emergency button */
#universal-doctor-navbar .btn-danger {
    background-color: #dc2626 !important;
    border-color: #dc2626 !important;
    color: #ffffff !important;
}

#universal-doctor-navbar .btn-danger:hover {
    background-color: #b91c1c !important;
    border-color: #b91c1c !important;
    transform: translateY(-1px) !important;
}

/* Icon Fixes */
#universal-doctor-navbar i {
    display: inline-block !important;
    font-style: normal !important;
}

/* Mobile Responsive Utility */
@media (max-width: 768px) {
    #universal-doctor-navbar .hide-mobile {
        display: none !important;
    }
    #universal-doctor-navbar {
        padding: 1rem !important;
    }
}

/* 
   DROPDOWN & MODAL STYLES 
*/
.navbar-dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: #374151; /* gray-700 */
    text-decoration: none;
    font-size: 0.875rem;
    transition: background 0.2s ease;
}

.navbar-dropdown-item:hover {
    background: #f9fafb; /* gray-50 */
}

.navbar-dropdown-item i {
    width: 1.25rem;
    text-align: center;
}

.profile-button {
    display: flex;
    align-items: center;
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 0.5rem;
    transition: background 0.2s ease;
}

.profile-button:hover {
    background: #f3f4f6; /* gray-100 */
}

.navbar-dropdown-menu {
    position: absolute;
    top: calc(100% + 0.5rem);
    right: 0;
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    min-width: 200px;
    z-index: 1000;
    animation: slideUp 0.2s ease-out;
}

@keyframes slideUp {
    from {
        transform: translateY(10px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #dc2626;
    color: white;
    font-size: 0.65rem;
    font-weight: 700;
    padding: 0.15rem 0.4rem;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
}

/* Profile Modal Styles */
.navbar-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(5px);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.profile-card-modal {
    background: white;
    width: 100%;
    max-width: 400px;
    border-radius: 20px;
    overflow: hidden;
    position: relative;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    animation: modalFadeIn 0.3s ease-out;
}

@keyframes modalFadeIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

.profile-card-header {
    height: 100px;
    background: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%);
    position: relative;
}

.close-modal {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    font-size: 24px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    line-height: 1;
}

.profile-card-content {
    padding: 0 30px 30px;
    text-align: center;
}

.profile-card-avatar {
    margin-top: -50px;
    margin-bottom: 15px;
    position: relative;
    z-index: 10;
}

.profile-card-avatar img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    border: 5px solid white;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.profile-card-name {
    font-size: 22px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 5px;
}

.profile-card-role {
    color: #1f6b4a;
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 25px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.profile-card-details {
    text-align: left;
    margin-bottom: 30px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 12px;
    margin-bottom: 10px;
}

.detail-item i {
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    color: #6c757d;
    border-radius: 10px;
    font-size: 16px;
}

.detail-text span {
    display: block;
    font-size: 11px;
    color: #adb5bd;
    text-transform: uppercase;
    font-weight: 700;
}

.detail-text strong {
    display: block;
    font-size: 14px;
    color: #495057;
}

.profile-card-actions {
    display: flex;
    gap: 15px;
}

.profile-card-actions button, .profile-card-actions a {
    flex: 1;
    padding: 12px;
    border-radius: 12px;
    font-weight: 600;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
    text-align: center;
}

.btn-secondary {
    background: #e9ecef;
    color: #495057;
}

/* Ensure Emergency button is always visible */
nav.doctor-navbar .btn-danger[onclick*="triggerEmergencyAlert"] {
    display: flex !important;
    opacity: 1 !important;
    visibility: visible !important;
    position: relative !important;
    z-index: 1001 !important;
}
</style>

<!-- Doctor Top Navbar -->
<nav class="doctor-navbar" id="universal-doctor-navbar">
    <!-- Left Section: Mobile Menu + Page Title -->
    <div style="display: flex; align-items: center; gap: 1rem;">
        <!-- Mobile Menu Toggle -->
        <button onclick="toggleSidebar()" class="btn btn-outline" id="mobile-menu-btn" style="padding: 0.5rem; display: none;">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Page Title -->
        <div>
            <h2 id="page-title">
                <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard'; ?>
            </h2>
            <p id="page-subtitle">
                Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>
            </p>
        </div>
    </div>
    
    <!-- Right Section: Quick Actions + Notifications + Profile -->
    <div style="display: flex; align-items: center; gap: 1rem;">
        
        <!-- Emergency Alert Button -->
        <button onclick="triggerEmergencyAlert()" class="btn btn-danger" title="Emergency Alert">
            <i class="fas fa-exclamation-triangle"></i>
            <span class="hide-mobile">Emergency</span>
        </button>
        
        <!-- Quick Consultation Button -->
        <button onclick="window.location.href='consultation.php'" class="btn btn-primary" title="Start Consultation">
            <i class="fas fa-notes-medical"></i>
            <span class="hide-mobile">New Consultation</span>
        </button>
        
        <!-- Notifications -->
        <div style="position: relative;">
            <button onclick="toggleNotifications()" class="btn btn-outline" style="position: relative;" title="Notifications">
                <i class="fas fa-bell"></i>
                <span id="navbar-notification-badge" class="notification-badge" style="display: none;">0</span>
            </button>
            
            <!-- Notifications Dropdown -->
            <div id="notifications-dropdown" class="navbar-dropdown-menu" style="display: none;">
                <div style="padding: 1rem; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 1rem; font-weight: 600; margin: 0;">Notifications</h3>
                    <a href="notifications.php" style="font-size: 0.75rem; color: #1f6b4a;">View All</a>
                </div>
                <div id="notifications-list" style="max-height: 400px; overflow-y: auto;">
                    <div style="padding: 2rem; text-align: center; color: #9ca3af;">
                        <i class="fas fa-bell-slash" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                        <p>No new notifications</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Doctor Profile -->
        <div style="position: relative;">
            <button onclick="toggleProfileMenu()" class="profile-button">
                <?php 
                $photo = $_SESSION['photo'] ?? null;
                $photoSrc = ($photo && file_exists(str_replace('/GM_HMS', $_SERVER['DOCUMENT_ROOT'] . '/GM_HMS', $photo))) ? $photo : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['full_name']) . '&background=1f6b4a&color=fff';
                ?>
                <img id="doctor-photo" src="<?php echo $photoSrc; ?>" alt="<?php echo htmlspecialchars($_SESSION['full_name']); ?>" 
                     style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #1f6b4a;">
                <div class="hide-mobile" style="text-align: left; margin-left: 0.75rem;">
                    <div id="doctor-name" style="font-weight: 600; font-size: 0.875rem; color: #111827;">
                        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                    </div>
                    <div id="doctor-specialization" style="font-size: 0.75rem; color: #6b7280;">
                        <?php echo htmlspecialchars($_SESSION['designation']); ?>
                    </div>
                </div>
                <i class="fas fa-chevron-down hide-mobile" style="margin-left: 0.5rem; color: #9ca3af;"></i>
            </button>
            
            <!-- Profile Dropdown -->
            <div id="profile-dropdown" class="navbar-dropdown-menu" style="display: none; right: 0;">
                <a href="javascript:void(0)" onclick="toggleProfileModal()" class="navbar-dropdown-item">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
                <a href="javascript:void(0)" onclick="toggleSettingsModal()" class="navbar-dropdown-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="schedule.php" class="navbar-dropdown-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>My Schedule</span>
                </a>
                <hr style="margin: 0.5rem 0; border: none; border-top: 1px solid #e5e7eb;">
                <a href="../logout.php" class="navbar-dropdown-item" style="color: #dc2626;">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Profile Info Modal -->
<div id="profileModal" class="navbar-modal-overlay" style="display: none;">
    <div class="profile-card-modal">
        <div class="profile-card-header">
            <button class="close-modal" onclick="toggleProfileModal()">&times;</button>
        </div>
        <div class="profile-card-content">
            <div class="profile-card-avatar">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name'] ?? 'Doctor'); ?>&background=1f6b4a&color=fff&size=128" alt="Avatar">
            </div>
            <h3 class="profile-card-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Doctor'); ?></h3>
            <p class="profile-card-role"><?php echo htmlspecialchars($_SESSION['designation'] ?? 'Medical Professional'); ?></p>
            
            <div class="profile-card-details" style="max-height: 400px; overflow-y: auto;">
                <div class="detail-item">
                    <i class="fas fa-envelope"></i>
                    <div class="detail-text">
                        <span>Email Address</span>
                        <strong><?php echo htmlspecialchars($_SESSION['email'] ?? 'Not Set'); ?></strong>
                    </div>
                </div>
                <div class="detail-item">
                    <i class="fas fa-phone"></i>
                    <div class="detail-text">
                        <span>Mobile Number</span>
                        <strong><?php echo htmlspecialchars($_SESSION['mobile_number'] ?? 'Not Set'); ?></strong>
                    </div>
                </div>
                <div class="detail-item">
                    <i class="fas fa-id-badge"></i>
                    <div class="detail-text">
                        <span>Doctor Identifier</span>
                        <strong><?php echo htmlspecialchars($_SESSION['user_id'] ?? 'N/A'); ?></strong>
                    </div>
                </div>

                 <!-- Personal Info -->
                 <div class="detail-item">
                    <i class="fas fa-venus-mars"></i>
                    <div class="detail-text">
                        <span>Gender</span>
                        <strong><?php echo htmlspecialchars($_SESSION['gender'] ?? 'Not Set'); ?></strong>
                    </div>
                </div>
                <div class="detail-item">
                    <i class="fas fa-birthday-cake"></i>
                    <div class="detail-text">
                        <span>Date of Birth</span>
                         <strong><?php echo htmlspecialchars($_SESSION['date_of_birth'] ?? 'Not Set'); ?></strong>
                    </div>
                </div>
                <div class="detail-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <div class="detail-text">
                        <span>Address</span>
                        <strong>
                            <?php 
                                $addrElements = array_filter([
                                    $_SESSION['address'] ?? '',
                                    $_SESSION['city'] ?? '',
                                    $_SESSION['state'] ?? '',
                                    $_SESSION['country'] ?? '',
                                    $_SESSION['pincode'] ?? ''
                                ]);
                                echo !empty($addrElements) ? implode(', ', $addrElements) : 'Not Set';
                            ?>
                        </strong>
                    </div>
                </div>

                <div class="detail-item">
                    <i class="fas fa-user-lock"></i>
                    <div class="detail-text">
                        <span>Security Group</span>
                        <strong><?php echo ucfirst(htmlspecialchars($_SESSION['role'] ?? 'Doctor')); ?></strong>
                    </div>
                </div>
                <div class="detail-item">
                    <i class="fas fa-check-circle"></i>
                    <div class="detail-text">
                        <span>Account Status</span>
                        <strong style="color: #28a745;"><?php echo htmlspecialchars($_SESSION['status'] ?? 'Active'); ?></strong>
                    </div>
                </div>
            </div>

            <div class="profile-card-actions">
                <button onclick="toggleProfileModal()" class="btn-secondary">Close</button>
                <a href="../logout.php" class="btn-primary" style="text-decoration: none; display: flex; align-items: center; justify-content: center; background: #1f6b4a; color: white;">Sign Out</a>
            </div>
        </div>
    </div>
</div>

<!-- Settings Modal (Placeholder) -->
<div id="settingsModal" class="navbar-modal-overlay" style="display: none; z-index: 10001;">
    <div class="profile-card-modal">
        <div class="profile-card-header">
            <button class="close-modal" onclick="toggleSettingsModal()">&times;</button>
            <h3 style="color: white; margin: 0; position: absolute; bottom: 15px; left: 30px; font-size: 1.25rem;">Settings</h3>
        </div>
        <div class="profile-card-content" style="padding-top: 30px; text-align: left;">
            <p>Settings panel implementation here...</p>
        </div>
    </div>
</div>

<script>
<?php
// Dynamically calculate the project root URL relative to the web root
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$fullPath = str_replace('\\', '/', dirname(__DIR__, 2));
$projectRoot = str_ireplace($docRoot, '', $fullPath);
$apiBase = rtrim($projectRoot, '/') . '/api/';
?>
const API_BASE = '<?php echo $apiBase; ?>';

// Toggle password visibility
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
document.addEventListener('DOMContentLoaded', function() {
    loadDoctorProfile();
    loadNavbarNotifications();
    setDynamicPageTitle();
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.profile-button') && !event.target.closest('#profile-dropdown')) {
            const profileDropdown = document.getElementById('profile-dropdown');
            if (profileDropdown) profileDropdown.style.display = 'none';
        }
        if (!event.target.closest('[onclick="toggleNotifications()"]') && !event.target.closest('#notifications-dropdown')) {
            const notifDropdown = document.getElementById('notifications-dropdown');
            if (notifDropdown) notifDropdown.style.display = 'none';
        }
    });

    // Mobile menu toggle visibility
    if (window.innerWidth <= 1024) {
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        if (mobileMenuBtn) {
            mobileMenuBtn.style.display = 'inline-flex';
        }
    }
});

// Load doctor profile
async function loadDoctorProfile() {
    try {
        const doctorId = "<?php echo $_SESSION['user_id']; ?>";
        if (!doctorId) return;
        
        // This is optional if the session variables are perfectly correct
        // but it helps if profile changes
        if (typeof API !== 'undefined') {
            const response = await API.get(`doctors/${doctorId}`);
            if (response && response.success) {
                const doctor = response.data;
                let fullName = doctor.full_name;
                
                let displayName = fullName;
                if (!fullName.toLowerCase().startsWith('dr.')) {
                    displayName = `Dr. ${fullName}`;
                }
                
                document.getElementById('doctor-name').textContent = displayName;
                document.getElementById('doctor-specialization').textContent = doctor.specialization || 'Specialist';
                
                const firstName = fullName.toLowerCase().startsWith('dr.') ? fullName.split(' ')[1] : fullName.split(' ')[0];
                const greetingElement = document.getElementById('page-subtitle');
                if (greetingElement) {
                    greetingElement.textContent = `Welcome back, Dr. ${firstName}`;
                }
            }
        }
    } catch (error) {
        console.error('Failed to load doctor profile:', error);
    }
}

// Load navbar notifications
async function loadNavbarNotifications() {
    try {
        if (typeof API !== 'undefined') {
            const response = await API.get('notifications?limit=5');
            if (response && response.success && response.data && response.data.length > 0) {
                const badge = document.getElementById('navbar-notification-badge');
                if (badge) {
                    const unreadCount = response.data.filter(n => !n.is_read).length;
                    if (unreadCount > 0) {
                        badge.textContent = unreadCount;
                        badge.style.display = 'inline-block';
                    }
                }
            }
        }
    } catch (error) {
        console.error('Failed to load navbar notifications:', error);
    }
}

function setDynamicPageTitle() {
    // Already set in PHP by page, this is just a fallback
}

// Toggle notifications dropdown
function toggleNotifications() {
    const dropdown = document.getElementById('notifications-dropdown');
    if (dropdown) {
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        // Close profile dropdown
        const profileDropdown = document.getElementById('profile-dropdown');
        if (profileDropdown) profileDropdown.style.display = 'none';
    }
}

// Toggle profile modal
function toggleProfileModal() {
    const modal = document.getElementById('profileModal');
    if (modal) {
        const isVisible = modal.style.display === 'flex';
        modal.style.display = isVisible ? 'none' : 'flex';
        document.body.style.overflow = isVisible ? 'auto' : 'hidden';
        
        // Close dropdown
        const profileDropdown = document.getElementById('profile-dropdown');
        if (profileDropdown) profileDropdown.style.display = 'none';
    }
}

function toggleSettingsModal() {
    const modal = document.getElementById('settingsModal');
    if (modal) {
        const isVisible = modal.style.display === 'flex';
        modal.style.display = isVisible ? 'none' : 'flex';
        document.body.style.overflow = isVisible ? 'auto' : 'hidden';
        
        // Close dropdown
        const profileDropdown = document.getElementById('profile-dropdown');
        if (profileDropdown) profileDropdown.style.display = 'none';
    }
}

// Close modal on outside click
window.onclick = function(event) {
    const profileModal = document.getElementById('profileModal');
    if (event.target == profileModal) {
        toggleProfileModal();
    }
    const settingsModal = document.getElementById('settingsModal');
    if (event.target == settingsModal) {
        toggleSettingsModal();
    }
}

// Toggle profile menu
function toggleProfileMenu() {
    const dropdown = document.getElementById('profile-dropdown');
    if (dropdown) {
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        
        // Close notifications dropdown
        const notifDropdown = document.getElementById('notifications-dropdown');
        if (notifDropdown) notifDropdown.style.display = 'none';
    }
}

// Sidebar toggle for mobile
function toggleSidebar() {
    const sidebar = document.getElementById('doctorSidebar');
    if (sidebar) {
        sidebar.classList.toggle('mobile-open');
    }
}
</script>
