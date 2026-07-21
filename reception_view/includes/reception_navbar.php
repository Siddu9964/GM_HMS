<style>
/* 
   UNIVERSAL NAVBAR STYLES
   Targeting with ID (#universal-reception-navbar) to ensure HIGHEST SPECIFICITY.
   This guarantees consistency across all pages (Dashboard, OPD, IPD, etc.)
   regardless of other CSS frameworks (Bootstrap, etc.) being loaded.
*/

#universal-reception-navbar {
    background: rgba(255, 255, 255, .9) !important;
    backdrop-filter: blur(12px) !important;
    -webkit-backdrop-filter: blur(12px) !important;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06) !important;
    padding: 0 1.75rem !important;
    height: 60px !important;
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

#universal-reception-navbar *:not(i) {
    font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
    box-sizing: border-box !important;
}

/* Typography Overrides */
#universal-reception-navbar h2#page-title {
    font-size: 1.5rem !important;
    font-weight: 700 !important;
    color: #1f6b4a !important; /* Medical Teal */
    margin: 0 !important;
    line-height: 1.2 !important;
}

#universal-reception-navbar p#page-subtitle {
    font-size: 0.875rem !important;
    color: #6b7280 !important; /* gray-500 */
    margin: 0 !important;
    line-height: 1.5 !important;
}

/* Button Overrides - STRICTLY enforce Teal */
#universal-reception-navbar .btn {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0.5rem !important;
    padding: 0.5rem 1rem !important;
    font-size: 0.875rem !important;
    font-weight: 500 !important;
    border-radius: 0.5rem !important;
    border: 1px solid transparent !important; /* Ensure border is defined */
    cursor: pointer !important;
    transition: all 200ms ease !important;
    text-decoration: none !important;
    white-space: nowrap !important;
    line-height: 1.5 !important;
}

/* Primary Button (Teal) */
#universal-reception-navbar .btn-primary {
    background-color: #1f6b4a !important; /* Medical Teal */
    border-color: #1f6b4a !important;
    color: #ffffff !important;
    box-shadow: none !important;
}


#universal-reception-navbar .btn-primary:hover {
    background-color: #144d34 !important; /* Darker Teal */
    border-color: #144d34 !important;
    transform: translateY(-1px) !important;
    color: #ffffff !important;
}

/* Outline Button (White/Teal) */
#universal-reception-navbar .btn-outline {
    background-color: transparent !important;
    border: 2px solid #1f6b4a !important;
    color: #1f6b4a !important;
}

#universal-reception-navbar .btn-outline:hover {
    background-color: #1f6b4a !important;
    color: #ffffff !important;
}

/* Icon Fixes */
#universal-reception-navbar i {
    display: inline-block !important;
    font-style: normal !important;
}

/* Mobile Responsive Utility */
@media (max-width: 768px) {
    #universal-reception-navbar .hide-mobile {
        display: none !important;
    }
    #universal-reception-navbar {
        padding: 1rem !important;
    }
}

/* 
   RESTORED DROPDOWN & MODAL STYLES 
   Moved from bottom to top to prevent FOUC and leakage
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
    color: #3b82f6;
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 25px;
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
}

.btn-secondary {
    background: #e9ecef;
    color: #495057;
}

/* Dark Mode Styles */
body.dark-mode {
    background-color: #1a202c !important;
    color: #e2e8f0;
}

body.dark-mode .sidebar,
body.dark-mode .doctor-navbar, 
body.dark-mode .reception-navbar,
body.dark-mode .card, 
body.dark-mode .profile-card-modal,
body.dark-mode .navbar-dropdown-menu,
body.dark-mode .detail-item {
    background-color: #2d3748 !important;
    border-color: #4a5568 !important;
    color: #e2e8f0 !important;
}

body.dark-mode h1, body.dark-mode h2, body.dark-mode h3, 
body.dark-mode h4, body.dark-mode h5, body.dark-mode h6,
body.dark-mode strong, body.dark-mode .profile-card-name {
    color: #f7fafc !important;
}

body.dark-mode .text-muted, 
body.dark-mode .text-secondary,
body.dark-mode .detail-text span {
    color: #a0aec0 !important;
}

body.dark-mode .detail-item {
    background-color: #2d3748 !important;
}

body.dark-mode .detail-item i {
    background-color: #4a5568 !important;
    color: #e2e8f0 !important;
}

body.dark-mode .navbar-dropdown-item {
    color: #e2e8f0 !important;
}

body.dark-mode .navbar-dropdown-item:hover {
    background-color: #4a5568 !important;
}

body.dark-mode .form-control {
    background-color: #1a202c !important;
    border-color: #4a5568 !important;
    color: #e2e8f0 !important;
}

/* Compact Mode Styles */
body.compact-mode .container,
body.compact-mode .container-fluid {
    padding-left: 10px !important;
    padding-right: 10px !important;
}

body.compact-mode .card {
    margin-bottom: 10px !important;
}

body.compact-mode .table td, 
body.compact-mode .table th {
    padding: 0.25rem 0.5rem !important;
}

</style>

<!-- Reception Top Navbar -->
<nav class="reception-navbar" id="universal-reception-navbar">
    <!-- Left Section: Mobile Menu + Page Title -->
    <div style="display: flex; align-items: center; gap: 1rem;">
        <!-- Mobile Menu Toggle -->
        <button onclick="toggleSidebar()" class="btn btn-outline" id="desktop-menu-btn" style="padding: 0.5rem; display: inline-flex;">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Page Title -->
        <div>
            <h2 id="page-title" style="font-size: 1.5rem; font-weight: 700; color: #1f6b4a; margin: 0;">
                <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard'; ?>
            </h2>
            <p id="page-subtitle" style="font-size: 0.875rem; color: var(--gray-500); margin: 0;">
                Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>
            </p>
        </div>
    </div>
    
    <!-- Right Section: Quick Actions + Notifications + Profile -->
    <div style="display: flex; align-items: center; gap: 1rem;">
        
        <!-- New Appointment Button -->
        <button onclick="window.location.href='appointment_management.php'" class="btn btn-primary" title="New Appointment">
            <i class="fas fa-calendar-plus"></i>
            <span class="hide-mobile">New Appointment</span>
        </button>

         <button onclick="window.location.href='patient_registration.php'" class="btn btn-primary" title="New Registration">
            <i class="fas fa-calendar-plus"></i>
            <span class="hide-mobile">New Registration</span>
        </button>
        
        <!-- Notifications -->
        <div style="position: relative;">
            <button onclick="toggleNotifications()" class="btn btn-outline" style="position: relative;" title="Notifications">
                <i class="fas fa-bell"></i>
                <span id="navbar-notification-badge" class="notification-badge" style="display: none;">0</span>
            </button>
            
            <!-- Notifications Dropdown -->
            <div id="notifications-dropdown" class="navbar-dropdown-menu" style="display: none;">
                <div style="padding: 1rem; border-bottom: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 1rem; font-weight: 600; margin: 0;">Notifications</h3>
                    <a href="#" style="font-size: 0.75rem; color: var(--primary-blue);">View All</a>
                </div>
                <div id="notifications-list" style="max-height: 400px; overflow-y: auto;">
                    <div style="padding: 2rem; text-align: center; color: var(--gray-400);">
                        <i class="fas fa-bell-slash" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                        <p>No new notifications</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Reception Profile -->
        <div style="position: relative;">
<?php
if (!isset($basePath)) {
    $currentPath = $_SERVER['PHP_SELF'];
    $depth = substr_count(str_replace('/GM_HMS/reception_view/', '', $currentPath), '/');
    $basePath = str_repeat('../', $depth);
}
?>
            <button onclick="toggleProfileMenu()" class="profile-button">
                <?php 
                $photo = $_SESSION['photo'] ?? null;
                $photoSrc = ($photo && file_exists(str_replace('/GM_HMS', $_SERVER['DOCUMENT_ROOT'] . '/GM_HMS', $photo))) ? $photo : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['full_name']);
                ?>
                <img id="reception-photo" src="<?php echo $photoSrc; ?>" alt="<?php echo htmlspecialchars($_SESSION['full_name']); ?>" 
                     style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary-blue-light);"
                     onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name']); ?>&background=random'">
                <div class="hide-mobile" style="text-align: left; margin-left: 0.75rem;">
                    <div id="reception-name" style="font-weight: 600; font-size: 0.875rem; color: var(--gray-900);">
                        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                    </div>
                    <div style="font-size: 0.75rem; color: var(--gray-500);">
                        <?php echo htmlspecialchars($_SESSION['designation']); ?>
                    </div>
                </div>
                <i class="fas fa-chevron-down hide-mobile" style="margin-left: 0.5rem; color: var(--gray-400);"></i>
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
                <hr style="margin: 0.5rem 0; border: none; border-top: 1px solid var(--gray-200);">
                <a href="<?php echo $basePath; ?>../logout.php" class="navbar-dropdown-item" style="color: var(--status-danger);">
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
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name'] ?? 'Receptionist'); ?>&background=1f6b4a&color=fff&size=128" alt="Avatar">
            </div>
            <h3 class="profile-card-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Receptionist'); ?></h3>
            <p class="profile-card-role"><?php echo htmlspecialchars($_SESSION['designation'] ?? 'Hospital Staff'); ?></p>
            
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
                        <span>Staff Identifier</span>
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
                        <strong><?php echo ucfirst(htmlspecialchars($_SESSION['role'] ?? 'Receptionist')); ?></strong>
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
                <a href="<?php echo $basePath; ?>../logout.php" class="btn-primary">Sign Out</a>
            </div>
        </div>
    </div>
</div>



<!-- Settings Modal -->
<div id="settingsModal" class="navbar-modal-overlay" style="display: none; z-index: 10001;">
    <div class="profile-card-modal">
        <div class="profile-card-header">
            <button class="close-modal" onclick="toggleSettingsModal()">&times;</button>
            <h3 style="color: white; margin: 0; position: absolute; bottom: 15px; left: 30px; font-size: 1.25rem;">Settings</h3>
        </div>
        <div class="profile-card-content" style="padding-top: 30px; text-align: left;">

            
            <div style="margin-bottom: 15px;">
                <h4 style="color: #4a5568; margin-bottom: 15px; font-size: 1rem; border-bottom: 2px solid #edf2f7; padding-bottom: 5px;">Preferences</h4>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #f8f9fa; border-radius: 10px; margin-bottom: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-moon" style="color: #6c757d;"></i>
                        <span style="font-weight: 500; font-size: 0.95rem;">Dark Mode</span>
                    </div>
                    <label class="switch" style="position: relative; display: inline-block; width: 40px; height: 24px;">
                        <input type="checkbox" id="darkModeToggle" onchange="toggleDarkMode()">
                        <span class="slider round" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px;"></span>
                        <span class="slider-knob" style="position: absolute; content: ''; height: 16px; width: 16px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%;"></span>
                    </label>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #f8f9fa; border-radius: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-bell" style="color: #6c757d;"></i>
                        <span style="font-weight: 500; font-size: 0.95rem;">Notifications</span>
                    </div>
                    <label class="switch" style="position: relative; display: inline-block; width: 40px; height: 24px;">
                        <input type="checkbox" id="notificationsToggle" checked onchange="toggleNotificationPref()">
                        <span class="slider round" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px;"></span>
                        <span class="slider-knob" style="position: absolute; content: ''; height: 16px; width: 16px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%;"></span>
                    </label>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #f8f9fa; border-radius: 10px; margin-top: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-compress-alt" style="color: #6c757d;"></i>
                        <span style="font-weight: 500; font-size: 0.95rem;">Compact Mode</span>
                    </div>
                    <label class="switch" style="position: relative; display: inline-block; width: 40px; height: 24px;">
                        <input type="checkbox" id="compactModeToggle" onchange="toggleCompactMode()">
                        <span class="slider round" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px;"></span>
                        <span class="slider-knob" style="position: absolute; content: ''; height: 16px; width: 16px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%;"></span>
                    </label>
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <h4 style="color: #4a5568; margin-bottom: 15px; font-size: 1rem; border-bottom: 2px solid #edf2f7; padding-bottom: 5px;">Account</h4>
                <div onclick="toggleEditProfileModal(); toggleSettingsModal();" style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #f8f9fa; border-radius: 10px; cursor: pointer; transition: background 0.2s; margin-bottom: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-user-edit" style="color: #1f6b4a;"></i>
                        <span style="font-weight: 500; font-size: 0.95rem;">Edit Profile</span>
                    </div>
                    <i class="fas fa-chevron-right" style="color: #cbd5e0; font-size: 0.8rem;"></i>
                </div>
                <div onclick="toggleChangePasswordModal(); toggleSettingsModal();" style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #f8f9fa; border-radius: 10px; cursor: pointer; transition: background 0.2s;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-key" style="color: #6c757d;"></i>
                        <span style="font-weight: 500; font-size: 0.95rem;">Change Password</span>
                    </div>
                    <i class="fas fa-chevron-right" style="color: #cbd5e0; font-size: 0.8rem;"></i>
                </div>
            </div>
            <div style="margin-bottom: 15px;">
                <h4 style="color: #4a5568; margin-bottom: 15px; font-size: 1rem; border-bottom: 2px solid #edf2f7; padding-bottom: 5px;">Support</h4>
                <a href="mailto:support@hospital.com" style="text-decoration: none; color: inherit;">
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #f8f9fa; border-radius: 10px; cursor: pointer; transition: background 0.2s; margin-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-question-circle" style="color: #6c757d;"></i>
                            <span style="font-weight: 500; font-size: 0.95rem;">Help Center</span>
                        </div>
                        <i class="fas fa-external-link-alt" style="color: #cbd5e0; font-size: 0.8rem;"></i>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div id="editProfileModal" class="navbar-modal-overlay" style="display: none; z-index: 10002;">
    <div class="profile-card-modal">
        <div class="profile-card-header">
            <button class="close-modal" onclick="toggleEditProfileModal()">&times;</button>
            <h3 style="color: white; margin: 0; position: absolute; bottom: 15px; left: 30px; font-size: 1.25rem;">Edit Profile</h3>
        </div>
        <div class="profile-card-content" style="padding-top: 30px;">
            <form id="edit-profile-form" onsubmit="handleProfileUpdate(event)">
                <div style="text-align: center; margin-bottom: 20px; position: relative; width: 100px; margin-left: auto; margin-right: auto;">
                    <img id="edit-profile-preview" src="https://ui-avatars.com/api/?name=User" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #1f6b4a;">
                    <label for="profile-photo-input" style="position: absolute; bottom: 0; right: 0; background: #1f6b4a; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                        <i class="fas fa-camera" style="font-size: 14px;"></i>
                    </label>
                    <input type="file" id="profile-photo-input" name="photo" accept="image/*" style="display: none;" onchange="previewProfilePhoto(this)">
                </div>
                <p style="text-align: center; font-size: 0.75rem; color: #6c757d; margin-bottom: 20px;">Click the camera icon to upload a profile photo (optional)</p>
                
                <div class="form-group" style="text-align: left;">
                    <label class="form-label">Full Name <span style="color: #6c757d; font-size: 0.75rem;">(optional)</span></label>
                    <input type="text" id="edit-profile-name" name="full_name" class="form-control" placeholder="Enter your full name">
                </div>
                
                <div class="profile-card-actions">
                    <button type="button" onclick="toggleEditProfileModal()" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div id="changePasswordModal" class="navbar-modal-overlay" style="display: none; z-index: 10002;">
    <div class="profile-card-modal">
        <div class="profile-card-header">
            <button class="close-modal" onclick="toggleChangePasswordModal()">&times;</button>
            <h3 style="color: white; margin: 0; position: absolute; bottom: 15px; left: 30px; font-size: 1.25rem;">Change Password</h3>
        </div>
        <div class="profile-card-content" style="padding-top: 30px;">
            <form id="change-password-form">
                <div class="form-group" style="text-align: left;">
                    <label class="form-label">Current Password</label>
                    <div style="position:relative;">
                        <input type="password" name="current_password" id="rec-pw-current" class="form-control" required style="padding-right:2.5rem;">
                        <button type="button" onclick="togglePwVis('rec-pw-current','rec-eye-cur')" tabindex="-1" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;">
                            <i id="rec-eye-cur" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group" style="text-align: left;">
                    <label class="form-label">New Password</label>
                    <div style="position:relative;">
                        <input type="password" name="new_password" id="rec-pw-new" class="form-control" minlength="8" required style="padding-right:2.5rem;">
                        <button type="button" onclick="togglePwVis('rec-pw-new','rec-eye-new')" tabindex="-1" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;">
                            <i id="rec-eye-new" class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small style="color: #64748b; font-size: 0.75rem;">Minimum 8 characters</small>
                </div>
                <div id="change-pw-msg" style="display:none; padding:10px; border-radius:8px; font-size:0.875rem; margin-bottom:10px;"></div>
                <div class="profile-card-actions">
                    <button type="button" onclick="toggleChangePasswordModal()" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>


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
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.profile-button') && !event.target.closest('#profile-dropdown')) {
            document.getElementById('profile-dropdown').style.display = 'none';
        }
        if (!event.target.closest('[onclick="toggleNotifications()"]') && !event.target.closest('#notifications-dropdown')) {
            document.getElementById('notifications-dropdown').style.display = 'none';
        }
    });
});

// Toggle notifications dropdown
function toggleNotifications() {
    const dropdown = document.getElementById('notifications-dropdown');
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    
    // Close profile dropdown
    document.getElementById('profile-dropdown').style.display = 'none';
}

// Toggle profile modal
function toggleProfileModal() {
    const modal = document.getElementById('profileModal');
    const isVisible = modal.style.display === 'flex';
    modal.style.display = isVisible ? 'none' : 'flex';
    document.body.style.overflow = isVisible ? 'auto' : 'hidden';
    
    // Close dropdown
    document.getElementById('profile-dropdown').style.display = 'none';
}

// Close modal on outside click
window.onclick = function(event) {
    const modal = document.getElementById('profileModal');
    if (event.target == modal) {
        toggleProfileModal();
    }
}

// Toggle profile menu
function toggleProfileMenu() {
    const dropdown = document.getElementById('profile-dropdown');
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    
    // Close notifications dropdown
    document.getElementById('notifications-dropdown').style.display = 'none';
}

// Dark Mode Toggle
function toggleDarkMode() {
    const isDark = document.getElementById('darkModeToggle').checked;
    
    // Toggle style for input
    const slider = document.querySelector('#darkModeToggle + .slider');
    if (isDark) {
        document.body.classList.add('dark-mode');
        slider.style.backgroundColor = '#1f6b4a';
        // Persist
        localStorage.setItem('theme', 'dark');
    } else {
        document.body.classList.remove('dark-mode');
        slider.style.backgroundColor = '#ccc';
        localStorage.setItem('theme', 'light');
    }
}

// Compact Mode Toggle
function toggleCompactMode() {
    const isCompact = document.getElementById('compactModeToggle').checked;
    const slider = document.querySelector('#compactModeToggle + .slider');
    
    if (isCompact) {
        document.body.classList.add('compact-mode');
        slider.style.backgroundColor = '#1f6b4a';
        localStorage.setItem('compactMode', 'enabled');
    } else {
        document.body.classList.remove('compact-mode');
        slider.style.backgroundColor = '#ccc';
        localStorage.setItem('compactMode', 'disabled');
    }
}

// Toggle Edit Profile Modal
function toggleEditProfileModal() {
    const modal = document.getElementById('editProfileModal');
    const isVisible = modal.style.display === 'flex';
    modal.style.display = isVisible ? 'none' : 'flex';
    
    // Close settings modal if opening this one
    if (!isVisible) {
        document.getElementById('settingsModal').style.display = 'none';
        // Pre-fill data if needed
        const currentName = document.querySelector('.profile-card-name').textContent.trim();
        document.getElementById('edit-profile-name').value = currentName;
        
        // Sync photo to edit modal
        const currentPhotoSrc = document.getElementById('reception-photo').src;
        document.getElementById('edit-profile-preview').src = currentPhotoSrc;
    } else {
        document.getElementById('settingsModal').style.display = 'flex';
    }
}

// Preview Profile Photo
function previewProfilePhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('edit-profile-preview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Handle Profile Update
async function handleProfileUpdate(event) {
    event.preventDefault();
    const btn = event.target.querySelector('button[type="submit"]');
    const originalText = btn.textContent;
    btn.textContent = 'Saving...';
    btn.disabled = true;

    try {
        const form = document.getElementById('edit-profile-form');
        const formData = new FormData(form);

        // Use absolute path to ensure IT works from any subfolder
        const response = await fetch(`/GM_HMS/api/reception/profile/update`, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            toggleEditProfileModal();
            if(typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true, position: 'top-end', icon: 'success', 
                    title: 'Profile updated successfully!', showConfirmButton: false, timer: 1500
                });
            }
            
            // Update UI with returned data
            const user = result.data;
            if (user.full_name) {
                const nameElements = document.querySelectorAll('.profile-card-name, #reception-name');
                nameElements.forEach(el => el.textContent = user.full_name);
            }
            
            // Update photo if changed
            if (user.photo) {
                const profileImgs = document.querySelectorAll('.profile-card-avatar img, #reception-photo');
                profileImgs.forEach(img => img.src = user.photo);
            }
            
            // Reload page after 1 second to refresh session and all UI elements
            setTimeout(() => {
                window.location.reload();
            }, 1500);

        } else {
            throw new Error(result.error || result.message || 'Update failed');
        }
    } catch (error) {
        console.error('Profile update failed:', error);
        if(typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true, position: 'top-end', icon: 'error', 
                title: error.message, showConfirmButton: false, timer: 3000
            });
        }
    } finally {
        btn.textContent = originalText;
        btn.disabled = false;
    }
}

// Notification Pref Toggle
function toggleNotificationPref() {
    const isEnabled = document.getElementById('notificationsToggle').checked;
    const slider = document.querySelector('#notificationsToggle + .slider');
    
    if (isEnabled) {
        slider.style.backgroundColor = '#1f6b4a';
        localStorage.setItem('notifications', 'enabled');
        if(typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true, position: 'top-end', icon: 'success', 
                title: 'Notifications enabled', showConfirmButton: false, timer: 1500
            });
        }
    } else {
        slider.style.backgroundColor = '#ccc';
        localStorage.setItem('notifications', 'disabled');
        if(typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true, position: 'top-end', icon: 'info', 
                title: 'Notifications disabled', showConfirmButton: false, timer: 1500
            });
        }
    }
}

// Initialize Preferences on Load
document.addEventListener('DOMContentLoaded', () => {
    // Check Dark Mode
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        const toggle = document.getElementById('darkModeToggle');
        if(toggle) {
            toggle.checked = true;
            toggle.nextElementSibling.style.backgroundColor = '#1f6b4a';
        }
    }
    
    // Check Notifications
    const savedNotif = localStorage.getItem('notifications');
    const notifToggle = document.getElementById('notificationsToggle');
    if(notifToggle) {
        if (savedNotif === 'disabled') {
            notifToggle.checked = false;
            notifToggle.nextElementSibling.style.backgroundColor = '#ccc';
        } else {
            notifToggle.checked = true; // Default enabled
            notifToggle.nextElementSibling.style.backgroundColor = '#1f6b4a';
        }
    }
    
    // Initialize mobile menu button visibility AND event delegation
    const mobileBtn = document.getElementById('mobile-menu-btn');
    if (mobileBtn) {
        // Show on mobile/tablet, hide on desktop
        if (window.innerWidth <= 1024) {
            mobileBtn.style.display = 'block';
            console.log('Mobile menu button visible on mobile/tablet');
        } else {
            mobileBtn.style.display = 'none';
        }
    }
    
    // Close sidebar on page load for mobile
    if (window.innerWidth <= 1024) {
        closeSidebar();
        console.log('Sidebar closed on mobile page load');
    }
    
    // EVENT DELEGATION - ensures click works even if JS reloads
    document.addEventListener('click', function(e) {
        // Check if clicked element is mobile menu button or its child
        if (e.target.closest('#mobile-menu-btn')) {
            e.preventDefault();
            console.log('Mobile menu button clicked via delegation');
            toggleSidebar();
        }
    });
    
    // Window resize handler
    window.addEventListener('resize', function() {
        const mobileBtn = document.getElementById('mobile-menu-btn');
        if (mobileBtn) {
            if (window.innerWidth <= 1024) {
                mobileBtn.style.display = 'block';
            } else {
                mobileBtn.style.display = 'none';
                closeSidebar(); // Close sidebar when switching to desktop
            }
        }
    });
});

// Update page title dynamically
function setPageTitle(title, subtitle = '') {
    document.getElementById('page-title').textContent = title;
    if (subtitle) {
        document.getElementById('page-subtitle').textContent = subtitle;
    }
}
// Toggle Change Password Modal
function toggleChangePasswordModal() {
    const modal = document.getElementById('changePasswordModal');
    const isVisible = modal.style.display === 'flex';
    modal.style.display = isVisible ? 'none' : 'flex';
    
    // Close profile modal if opening this one
    if (!isVisible) {
        document.getElementById('profileModal').style.display = 'none';
        document.getElementById('change-password-form').reset();
    } else {
        // Re-open profile modal when closing this one
        document.getElementById('profileModal').style.display = 'flex';
    }
}

// Toggle Settings Modal
function toggleSettingsModal() {
    const modal = document.getElementById('settingsModal');
    
    if (modal.style.display === 'none' || modal.style.display === '') {
        modal.style.display = 'flex';
        // Hide profile dropdown if open
        document.getElementById('profile-dropdown').style.display = 'none';
        
        // Ensure strictly one modal is open
        document.getElementById('profileModal').style.display = 'none';
    } else {
        modal.style.display = 'none';
    }
}

// Handle Password Change Submit
document.getElementById('change-password-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<div class="spinner-sm"></div> Updating...';
    btn.disabled = true;
    
    const msgDiv = document.getElementById('change-pw-msg');
    msgDiv.style.display = 'none';
    
    try {
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        
        const apiPath = '<?php echo $basePath; ?>api/auth/change-password';
        
        const headers = {
            'Content-Type': 'application/json'
        };
        
        const authToken = '<?php echo $_SESSION['auth_token'] ?? ''; ?>';
        if (authToken) {
            headers['Authorization'] = 'Bearer ' + authToken;
        }

        const response = await fetch(API_BASE + 'auth/change-password', {
            method: 'POST',
            headers: headers,
            body: JSON.stringify(data)
        });
        
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            throw new Error("Invalid server response");
        }
        
        const json = await response.json();
        
        msgDiv.style.display = 'block';
        if (json.success) {
            msgDiv.style.background = '#d1fae5';
            msgDiv.style.color = '#065f46';
            msgDiv.textContent = 'Password updated! Redirecting to login...';
            setTimeout(function() { window.location.href = '../logout.php'; }, 1500);
        } else {
            msgDiv.style.background = '#fee2e2';
            msgDiv.style.color = '#991b1b';
            msgDiv.textContent = json.message || json.error || 'Failed to update password';
        }
        
    } catch (error) {
        console.error('Password reset error', error);
        const msgDiv = document.getElementById('change-pw-msg');
        msgDiv.style.display = 'block';
        msgDiv.style.background = '#fee2e2';
        msgDiv.style.color = '#991b1b';
        msgDiv.textContent = 'An error occurred. Please try again.';
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
});

// IMMEDIATE EXECUTION - Ensure sidebar functions are available
(function() {
    console.log('Initializing global sidebar functions...');
    
    // Make functions global immediately
    window.toggleSidebar = function() {
        const sidebar = document.getElementById('receptionSidebar');
        if (!sidebar) {
            console.error('Sidebar not found!');
            return;
        }
        
        const isMobile = window.innerWidth <= 1024;
        console.log('toggleSidebar called, isMobile:', isMobile);
        
        if (isMobile) {
            sidebar.classList.toggle('mobile-open');
            
            let overlay = document.getElementById('sidebar-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'sidebar-overlay';
                overlay.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                    z-index: 998;
                    display: none;
                    cursor: pointer;
                `;
                document.body.appendChild(overlay);
                overlay.addEventListener('click', window.closeSidebar);
            }
            
            if (sidebar.classList.contains('mobile-open')) {
                overlay.style.display = 'block';
                document.body.style.overflow = 'hidden';
                console.log('Sidebar opened');
            } else {
                overlay.style.display = 'none';
                document.body.style.overflow = '';
                console.log('Sidebar closed');
            }
        }
    };
    
    window.closeSidebar = function() {
        const sidebar = document.getElementById('receptionSidebar');
        const overlay = document.getElementById('sidebar-overlay');
        
        if (sidebar) {
            sidebar.classList.remove('mobile-open');
        }
        if (overlay) {
            overlay.style.display = 'none';
        }
        document.body.style.overflow = '';
        console.log('Sidebar force closed');
    };
    
    // Initialize immediately
    const mobileBtn = document.getElementById('mobile-menu-btn');
    if (mobileBtn) {
        if (window.innerWidth <= 1024) {
            mobileBtn.style.display = 'block';
            console.log('Mobile button shown immediately');
        } else {
            mobileBtn.style.display = 'none';
        }
    }
    
    // Force event delegation
    document.addEventListener('click', function(e) {
        if (e.target.closest('#mobile-menu-btn')) {
            e.preventDefault();
            console.log('Mobile button clicked via immediate delegation');
            window.toggleSidebar();
        }
    });
    
    console.log('Global sidebar functions initialized successfully');
})();
</script>
