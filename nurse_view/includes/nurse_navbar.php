<!-- Nurse Top Navbar -->
<nav class="nurse-navbar">
    <!-- Left Section: Mobile Menu + Page Title -->
    <div style="display: flex; align-items: center; gap: 1rem;">
        <!-- Mobile Menu Toggle -->
        <button onclick="toggleSidebar()" class="btn btn-outline" style="display: none;" id="mobile-menu-btn">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Page Title -->
        <div>
            <h2 id="page-title" style="font-size: 1.5rem; font-weight: 700; color: var(--gray-900); margin: 0;">
                <?php echo $pageTitle ?? 'Dashboard'; ?>
            </h2>
            <p id="page-subtitle" style="font-size: 0.875rem; color: var(--gray-500); margin: 0;">
                Welcome back,
                <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Nurse'); ?>
            </p>
        </div>
    </div>

    <!-- Right Section: Quick Actions + Notifications + Profile -->
    <div style="display: flex; align-items: center; gap: 1rem;">
        <!-- Quick Vitals Button -->
        <button onclick="window.location.href='vitals.php'" class="btn btn-primary" title="Record Vitals">
            <i class="fas fa-heartbeat"></i>
            <span class="hide-mobile">Record Vitals</span>
        </button>

        <!-- Quick Medication Button -->
        <button onclick="window.location.href='medication.php'" class="btn btn-success" title="Medications">
            <i class="fas fa-pills"></i>
            <span class="hide-mobile">Medications</span>
        </button>

        <!-- Notifications -->
        <div style="position: relative;">
            <button onclick="toggleNotifications()" class="btn btn-outline" style="position: relative;"
                title="Notifications">
                <i class="fas fa-bell"></i>
                <span id="navbar-notification-badge" class="notification-badge" style="display: none;">0</span>
            </button>

            <!-- Notifications Dropdown -->
            <div id="notifications-dropdown" class="dropdown-menu" style="display: none;">
                <div
                    style="padding: 1rem; border-bottom: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 1rem; font-weight: 600; margin: 0;">Notifications</h3>
                    <a href="notifications.php" style="font-size: 0.75rem; color: var(--primary-blue);">View All</a>
                </div>
                <div id="notifications-list" style="max-height: 400px; overflow-y: auto;">
                    <div style="padding: 2rem; text-align: center; color: var(--gray-400);">
                        <i class="fas fa-bell-slash" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                        <p>No new notifications</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Nurse Profile -->
        <div style="position: relative;">
            <button onclick="toggleProfileMenu()" class="profile-button">
                <img id="nurse-photo"
                    src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Nurse'); ?>&background=1f6b4a&color=fff&size=128"
                    alt="<?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Nurse'); ?>"
                    style="width: 42px; height: 42px; border-radius: 12px; object-fit: cover; border: 2px solid #1f6b4a; box-shadow: 0 4px 10px rgba(31, 107, 74, 0.2);">
                <div class="hide-mobile" style="text-align: left; margin-left: 0.75rem;">
                    <div id="nurse-name" style="font-weight: 700; font-size: 0.9375rem; color: #1e293b;">
                        <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Nurse'); ?>
                    </div>
                    <div id="nurse-designation" style="font-size: 0.75rem; color: #64748b; font-weight: 500;">
                        <?php echo htmlspecialchars($_SESSION['designation'] ?? 'Registered Nurse'); ?>
                    </div>
                </div>
                <i class="fas fa-chevron-down hide-mobile"
                    style="margin-left: 0.75rem; color: #94a3b8; font-size: 0.8rem;"></i>
            </button>

            <!-- Profile Dropdown -->
            <div id="profile-dropdown" class="dropdown-menu" style="display: none; right: 0;">
                <a href="javascript:void(0)" onclick="toggleProfileModal()" class="dropdown-item">
                    <i class="fas fa-user"></i>
                    <span>My Profile</span>
                </a>
                <a href="my_shift.php" class="dropdown-item">
                    <i class="fas fa-clock"></i>
                    <span>My Shift</span>
                </a>
                <a href="javascript:void(0)" onclick="toggleSettingsModal()" class="dropdown-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <hr style="margin: 0.5rem 0; border: none; border-top: 1px solid var(--gray-200);">
                <a href="../logout.php" class="dropdown-item" style="color: var(--status-danger);">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Profile Info Modal -->
<div id="profileModal" class="modal-overlay" style="display: none;">
    <div class="profile-card-modal">
        <div class="profile-card-header">
            <button class="close-modal" onclick="toggleProfileModal()">&times;</button>
        </div>
        <div class="profile-card-content">
            <div class="profile-card-avatar">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Nurse'); ?>&background=1f6b4a&color=fff&size=128"
                    alt="Avatar">
            </div>
            <h3 class="profile-card-name">
                <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Nurse'); ?></h3>
            <p class="profile-card-role"><?php echo htmlspecialchars($_SESSION['designation'] ?? 'Registered Nurse'); ?>
            </p>

            <div class="profile-card-details" style="max-height: 400px; overflow-y: auto;">
                <div class="detail-item">
                    <i class="fas fa-id-card"></i>
                    <div class="detail-text">
                        <span>Staff ID</span>
                        <strong><?php echo htmlspecialchars($_SESSION['user_id'] ?? 'N/A'); ?></strong>
                    </div>
                </div>
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
                    <i class="fas fa-user-shield"></i>
                    <div class="detail-text">
                        <span>Access Level</span>
                        <strong><?php echo ucfirst(htmlspecialchars($_SESSION['role'] ?? 'Nurse')); ?></strong>
                    </div>
                </div>
                <div class="detail-item">
                    <i class="fas fa-check-circle"></i>
                    <div class="detail-text">
                        <span>Account Status</span>
                        <strong
                            style="color: #28a745;"><?php echo htmlspecialchars($_SESSION['status'] ?? 'Active'); ?></strong>
                    </div>
                </div>
            </div>

            <div class="profile-card-actions">
                <button onclick="toggleProfileModal()" class="btn-secondary">Close</button>
                <a href="../logout.php" class="btn-primary">Log Out</a>
            </div>
        </div>
    </div>
</div>

<!-- Settings Modal -->
<div id="settingsModal" class="modal-overlay" style="display: none; z-index: 10001;">
    <div class="profile-card-modal">
        <div class="profile-card-header">
            <button class="close-modal" onclick="toggleSettingsModal()">&times;</button>
            <h3 style="color: white; margin: 0; position: absolute; bottom: 15px; left: 30px; font-size: 1.25rem;">
                Settings</h3>
        </div>
        <div class="profile-card-content" style="padding-top: 30px; text-align: left;">
            <div style="margin-bottom: 15px;">
                <h4
                    style="color: #4a5568; margin-bottom: 15px; font-size: 1rem; border-bottom: 2px solid #edf2f7; padding-bottom: 5px;">
                    Preferences</h4>
                <div
                    style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #f8f9fa; border-radius: 10px; margin-bottom: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-bell" style="color: #6c757d;"></i>
                        <span style="font-weight: 500; font-size: 0.95rem;">Notifications</span>
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="notificationsToggle" checked>
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <h4
                    style="color: #4a5568; margin-bottom: 15px; font-size: 1rem; border-bottom: 2px solid #edf2f7; padding-bottom: 5px;">
                    Account</h4>
                <div onclick="alert('Change password feature coming soon')"
                    style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #f8f9fa; border-radius: 10px; cursor: pointer;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-key" style="color: #6c757d;"></i>
                        <span style="font-weight: 500; font-size: 0.95rem;">Change Password</span>
                    </div>
                    <i class="fas fa-chevron-right" style="color: #cbd5e0; font-size: 0.8rem;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --primary-blue: #1f6b4a;
        --gray-900: #1a202c;
        --gray-500: #718096;
        --gray-200: #e2e8f0;
        --gray-400: #cbd5e0;
        --status-danger: #DC3545;
        --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    .nurse-navbar {
        background: rgba(255, 255, 255, .9) !important;
        backdrop-filter: blur(12px) !important;
        -webkit-backdrop-filter: blur(12px) !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.06) !important;
        padding: 0 1.5rem !important;
        height: 60px !important;
        box-shadow: 0 1px 0 rgba(0, 0, 0, .06) !important;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .btn {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        border: none;
        font-size: 0.875rem;
    }

    .btn-primary {
        background: #1f6b4a !important;
        border-color: #1f6b4a !important;
        color: white !important;
        box-shadow: none !important;
    }

    .btn-primary:hover {
        background: #144d34 !important;
        border-color: #144d34 !important;
        transform: translateY(-1px);
    }

    .btn-success {
        background: #28A745;
        color: white;
    }

    .btn-success:hover {
        background: #218838;
    }

    .btn-outline {
        background: transparent !important;
        border: 2px solid #1f6b4a !important;
        color: #1f6b4a !important;
    }

    .btn-outline:hover {
        background: #1f6b4a !important;
        color: white !important;
    }

    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #DC3545;
        color: white;
        font-size: 0.65rem;
        font-weight: 700;
        padding: 0.15rem 0.4rem;
        border-radius: 10px;
        min-width: 18px;
        text-align: center;
    }

    .profile-button {
        display: flex;
        align-items: center;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        cursor: pointer;
        padding: 0.4rem 0.75rem;
        border-radius: 12px;
        transition: all 0.3s ease;
    }

    .profile-button:hover {
        background: #fff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border-color: #1f6b4a;
    }

    .dropdown-menu {
        position: absolute;
        top: calc(100% + 0.5rem);
        right: 0;
        background: white;
        border-radius: 0.5rem;
        box-shadow: var(--shadow-xl);
        min-width: 200px;
        z-index: 1000;
        animation: slideUp 0.2s ease-out;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        color: #4a5568;
        text-decoration: none;
        font-size: 0.875rem;
        transition: background 0.2s ease;
    }

    .dropdown-item:hover {
        background: #f7fafc;
    }

    .dropdown-item i {
        width: 1.25rem;
        text-align: center;
    }

    .modal-overlay {
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
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        animation: modalFadeIn 0.3s ease-out;
    }

    @keyframes modalFadeIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
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
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .profile-card-name {
        font-size: 22px;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 5px;
    }

    .profile-card-role {
        color: #1f6b4a;
        font-weight: 700;
        font-size: 15px;
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

    .profile-card-actions button,
    .profile-card-actions a {
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

    .btn-primary {
        background: #007bff;
        color: white;
    }

    .btn-primary:hover {
        background: #0056b3;
    }

    .switch {
        position: relative;
        display: inline-block;
        width: 40px;
        height: 24px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
    }

    .slider.round {
        border-radius: 34px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }

    input:checked+.slider {
        background-color: #1f6b4a;
    }

    input:checked+.slider:before {
        transform: translateX(16px);
    }

    @media (max-width: 768px) {
        #mobile-menu-btn {
            display: inline-flex !important;
        }

        .hide-mobile {
            display: none !important;
        }
    }
</style>

<script>
    // Toggle notifications dropdown
    function toggleNotifications() {
        const dropdown = document.getElementById('notifications-dropdown');
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        document.getElementById('profile-dropdown').style.display = 'none';
    }

    // Toggle profile menu
    function toggleProfileMenu() {
        const dropdown = document.getElementById('profile-dropdown');
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        document.getElementById('notifications-dropdown').style.display = 'none';
    }

    // Toggle profile modal
    function toggleProfileModal() {
        const modal = document.getElementById('profileModal');
        const isVisible = modal.style.display === 'flex';
        modal.style.display = isVisible ? 'none' : 'flex';
        document.body.style.overflow = isVisible ? 'auto' : 'hidden';
        document.getElementById('profile-dropdown').style.display = 'none';
    }

    // Toggle settings modal
    function toggleSettingsModal() {
        const modal = document.getElementById('settingsModal');
        const isVisible = modal.style.display === 'flex';
        modal.style.display = isVisible ? 'none' : 'flex';
        document.body.style.overflow = isVisible ? 'auto' : 'hidden';
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function (event) {
        if (!event.target.closest('.profile-button') && !event.target.closest('#profile-dropdown')) {
            document.getElementById('profile-dropdown').style.display = 'none';
        }
        if (!event.target.closest('[onclick="toggleNotifications()"]') && !event.target.closest('#notifications-dropdown')) {
            document.getElementById('notifications-dropdown').style.display = 'none';
        }
    });

    // Close modal on outside click
    window.onclick = function (event) {
        const profileModal = document.getElementById('profileModal');
        const settingsModal = document.getElementById('settingsModal');
        if (event.target == profileModal) {
            toggleProfileModal();
        }
        if (event.target == settingsModal) {
            toggleSettingsModal();
        }
    }
</script>