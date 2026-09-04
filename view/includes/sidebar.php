<?php

// Get current page and directory for proper active state detection

$current_file = basename($_SERVER['PHP_SELF']);

$current_path = dirname($_SERVER['PHP_SELF']);

$request_uri = $_SERVER['REQUEST_URI'];


// Fetch dynamic unread discharge notifications count
try {
    if (class_exists('GM_HMS\Database\SecureDatabase')) {
        $db = GM_HMS\Database\SecureDatabase::getInstance();
        $notifConn = $db->getConnection();
        $notifCountResult = $notifConn->query("SELECT COUNT(*) as count FROM discharge_notifications WHERE status = 'Pending'");
        $notifCountRow = $notifCountResult ? $notifCountResult->fetch_assoc() : null;
        $unreadNotifCount = $notifCountRow['count'] ?? 0;
    } else {
        $notifConn = new mysqli('localhost', 'root', '', 'hmsc_basaveshwranagara');
        $notifCountResult = $notifConn->query("SELECT COUNT(*) as count FROM discharge_notifications WHERE status = 'Pending'");
        $notifCountRow = $notifCountResult ? $notifCountResult->fetch_assoc() : null;
        $unreadNotifCount = $notifCountRow['count'] ?? 0;
        $notifConn->close();
    }
} catch (Throwable $e) {
    $unreadNotifCount = 0;
}



// Function to check if current page matches the menu item

function isActive($page_file, $current_file, $current_path, $request_uri) {

    // Direct file match

    if ($current_file === $page_file) {

        return true;

    }

    

    // Check if page name appears in the request URI

    if (strpos($request_uri, $page_file) !== false) {

        return true;

    }

    

    return false;

}

?>

<!-- Sidebar -->
<aside id="adminSidebar" class="sidebar fixed lg:relative z-50 h-full transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out" style="width: var(--gm-sidebar-w); background: var(--gm-sidebar-bg); border-right: 1px solid var(--gm-sidebar-border); display: flex; flex-direction: column;">
    <div style="padding: 1.25rem 0.75rem; height: 100%; display: flex; flex-direction: column;">
        <div style="display: flex; align-items: center; margin-bottom: 2rem; padding: 0 0.5rem 1rem; border-bottom: 1px solid var(--gm-border);">
            <img src="/GM_HMS/assets/images/gm_logoo.png" alt="GM Logo" style="width: 38px; height: auto; margin-right: 0.6rem;">
            <div>
                <h1 style="color: #f3efe6; font-weight: 700; font-size: 1.05rem; margin: 0; white-space: nowrap;">GM hospital</h1>
                <p style="color: #94a3b8; font-size: 0.75rem; margin: 0;">Admin Panel</p>
            </div>
        </div>



        <!-- Navigation Menu -->
        <nav style="flex: 1; display: flex; flex-direction: column;">

            <a href="admin_dashboard.php"

                class="sidebar-item <?php echo isActive('admin_dashboard.php', $current_file, $current_path, $request_uri) ? 'active' : ''; ?>">

                <i class="fas fa-th-large"></i>

                <span>Dashboard</span>

            </a>



            <div class="pt-4 pb-2">

                <p class="text-gray-400 text-xs uppercase px-4">OPD & Appointments</p>

            </div>



            <a href="/GM_HMS/reception_view/index.php" class="sidebar-item <?php echo (strpos($current_path, 'reception_view') !== false) ? 'active' : ''; ?>">
                <i class="fas fa-desktop"></i>
                <span>Reception View</span>
            </a>

            <a href="/GM_HMS/doctors_view/dashboard.php" class="sidebar-item <?php echo (strpos($current_path, 'doctors_view') !== false) ? 'active' : ''; ?>">
                <i class="fas fa-user-md"></i>
                <span>Doctor View</span>
            </a>

            <a href="/GM_HMS/nurse_view/dashboard.php" class="sidebar-item <?php echo (strpos($current_path, 'nurse_view') !== false) ? 'active' : ''; ?>" style="display: flex; justify-content: space-between; align-items: center;">
                <span style="display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-user-nurse"></i>
                    <span>Nurse View</span>
                </span>
                <?php if ($unreadNotifCount > 0): ?>
                    <span id="sidebar-notif-badge" style="background: var(--gm-danger); color: white; font-size: 0.65rem; font-weight: 700; height: 16px; min-width: 16px; border-radius: 10px; display: flex; align-items: center; justify-content: center; padding: 0 4px;"><?php echo $unreadNotifCount; ?></span>
                <?php endif; ?>
            </a>

            <a href="/GM_HMS/pharmacy_view/dashboard.php" class="sidebar-item <?php echo (strpos($current_path, 'pharmacy_view') !== false) ? 'active' : ''; ?>">
                <i class="fas fa-pills"></i>
                <span>Pharmacy View</span>
            </a>

            <a href="/GM_HMS/laboratory_view/dashboard.php" class="sidebar-item <?php echo (strpos($current_path, 'laboratory_view') !== false) ? 'active' : ''; ?>">
                <i class="fas fa-flask"></i>
                <span>Lab View</span>
            </a>

            <a href="/GM_HMS/quality_view/dashboard.php" class="sidebar-item <?php echo (strpos($current_path, 'quality_view') !== false) ? 'active' : ''; ?>">
                <i class="fas fa-shield-halved"></i>
                <span>Quality & Safety</span>
            </a>


            <div class="pt-4 pb-2">

                <p class="text-gray-400 text-xs uppercase px-4">Staff Management</p>

            </div>

            <a href="doctor_management.php"

                class="sidebar-item <?php echo isActive('doctor_management.php', $current_file, $current_path, $request_uri) ? 'active' : ''; ?>">

                <i class="fas fa-user-md"></i>

                <span>Doctors</span>

            </a>

            <a href="staff_management.php"

                class="sidebar-item <?php echo isActive('staff_management.php', $current_file, $current_path, $request_uri) ? 'active' : ''; ?>">

                <i class="fas fa-user-nurse"></i>

                <span>Nurses & Staff</span>

            </a>

            <!-- <a href="nurse_duty_scheduler.php"

                class="sidebar-item <?php echo isActive('nurse_duty_scheduler.php', $current_file, $current_path, $request_uri) ? 'active' : ''; ?>">

                <i class="fas fa-calendar-alt"></i>

                <span>Nurse Duty Scheduler</span>

            </a> -->

            <a href="department_management.php"

                class="sidebar-item <?php echo isActive('department_management.php', $current_file, $current_path, $request_uri) ? 'active' : ''; ?>">

                <i class="fas fa-building"></i>

                <span>Departments</span>

            </a>

            <a href="patient_registration.php"

                class="sidebar-item <?php echo isActive('patient_registration.php', $current_file, $current_path, $request_uri) ? 'active' : ''; ?>">

                <i class="fas fa-user-injured"></i>

                <span>Patients</span>

            </a>

            <div class="pt-4 pb-2">

                <p class="text-gray-400 text-xs uppercase px-4">Finance</p>

            </div>

            <div class="billing-flyout-wrap" style="position: relative;">
                <?php
                $isBillingActive = isActive('billing_management.php', $current_file, $current_path, $request_uri) || 
                                   isActive('ipd_billing.php', $current_file, $current_path, $request_uri) || 
                                   isActive('ot_billing.php', $current_file, $current_path, $request_uri);
                ?>
                <button class="billing-flyout-btn <?php echo $isBillingActive ? 'active' : ''; ?>" id="billingBtn" onclick="toggleBillingFlyout(this)" aria-expanded="false">
                    <span class="billing-btn-left">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span>Billing</span>
                    </span>
                    <i class="fas fa-chevron-right billing-arrow"></i>
                </button>
                <div class="billing-flyout-panel" id="billingPanel">
                    <a href="billing_management.php" class="billing-item <?php echo isActive('billing_management.php', $current_file, $current_path, $request_uri) ? 'active' : ''; ?>" style="--n:1">
                        <i class="fas fa-stethoscope"></i>
                        <span>OPD Billing</span>
                    </a>
                    <a href="ipd_billing.php" class="billing-item <?php echo isActive('ipd_billing.php', $current_file, $current_path, $request_uri) ? 'active' : ''; ?>" style="--n:2">
                        <i class="fas fa-bed"></i>
                        <span>IP Billing</span>
                    </a>
                    <a href="ot_billing.php" class="billing-item <?php echo isActive('ot_billing.php', $current_file, $current_path, $request_uri) ? 'active' : ''; ?>" style="--n:3">
                        <i class="fas fa-procedures"></i>
                        <span>OT Billing</span>
                    </a>
                </div>
            </div>

            <a href="ip_insurance.php" class="sidebar-item <?php echo isActive('ip_insurance.php', $current_file, $current_path, $request_uri) ? 'active' : ''; ?>">

                <i class="fas fa-shield-alt"></i>

                <span>Insurance</span>

            </a>

            <div class="pt-4 pb-2">

                <p class="text-gray-400 text-xs uppercase px-4">Hospital Services</p>

            </div>

            <a href="laboratory.php"
                class="sidebar-item <?php echo isActive('laboratory.php', $current_file, $current_path, $request_uri) ? 'active' : ''; ?>">
                <i class="fas fa-flask"></i>
                <span>Laboratory</span>
            </a>

            <a href="opd_beds.php"
                class="sidebar-item <?php echo isActive('opd_beds.php', $current_file, $current_path, $request_uri) ? 'active' : ''; ?>">
                <i class="fas fa-bed"></i>
                <span>IPD Beds Details</span>
            </a>

            <a href="#blood-bank" class="sidebar-item">

                <i class="fas fa-tint"></i>

                <span>Blood Bank</span>

            </a>

            <a href="#ambulance" class="sidebar-item">

                <i class="fas fa-ambulance"></i>

                <span>Ambulance</span>

            </a>

            <a href="#operations" class="sidebar-item">

                <i class="fas fa-procedures"></i>

                <span>Operations</span>

            </a>

            <div class="pt-4 pb-2">

                <p class="text-gray-400 text-xs uppercase px-4">System</p>

            </div>

            <a href="#reports" class="sidebar-item">

                <i class="fas fa-chart-bar"></i>

                <span>Reports</span>

            </a>

            <a href="#noticeboard" class="sidebar-item">

                <i class="fas fa-bullhorn"></i>

                <span>Noticeboard</span>

            </a>

            <a href="#users" class="sidebar-item">

                <i class="fas fa-users-cog"></i>

                <span>User Management</span>

            </a>

            <a href="#settings" class="sidebar-item">

                <i class="fas fa-cog"></i>

                <span>Settings</span>

            </a>

        </nav>

    </div>

</aside>

<script>
function toggleBillingFlyout(btn) {
    var panel = document.getElementById('billingPanel');
    
    // Move panel to body to escape sidebar overflow clipping (because sidebar has overflow-y: auto)
    if (panel.parentNode !== document.body) {
        document.body.appendChild(panel);
    }
    
    var isOpen = btn.getAttribute('aria-expanded') === 'true';
    if (!isOpen) {
        var rect = btn.getBoundingClientRect();
        var gap = 10;
        panel.style.top = rect.top + 'px';
        panel.style.left = (rect.right + gap) + 'px';
        
        btn.setAttribute('aria-expanded', 'true');
        var arrow = btn.querySelector('.billing-arrow');
        if (arrow) {
            arrow.style.transform = 'translateX(3px)';
            arrow.style.color = '#ffffff';
        }
        panel.classList.add('open');
        
        // Retrigger animations
        panel.querySelectorAll('.billing-item').forEach(function(el) {
            el.style.animation = 'none';
            el.offsetHeight;
            el.style.animation = '';
        });
    } else {
        btn.setAttribute('aria-expanded', 'false');
        var arrow = btn.querySelector('.billing-arrow');
        if (arrow) {
            arrow.style.transform = 'translateX(0)';
            arrow.style.color = 'rgba(255,255,255,0.5)';
        }
        panel.classList.remove('open');
    }
}
</script>


<style>
    .sidebar {
        background: var(--gm-sidebar-bg) !important;
        border-right: 1px solid rgba(255,255,255,0.05);
        box-shadow: 4px 0 20px rgba(0,0,0,0.02);
    }

    .sidebar-item {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .6rem .85rem;
        border-radius: 10px;
        color: rgba(255, 255, 255, 0.75);
        text-decoration: none !important;
        font-size: .81rem;
        font-weight: 500;
        transition: all .22s cubic-bezier(.4, 0, .2, 1);
        margin-bottom: 2px;
    }

    .sidebar-item i {
        font-size: 1.05rem;
        width: 20px;
        min-width: 20px;
        text-align: center;
        color: rgba(255, 255, 255, 0.5);
        transition: color 0.2s ease;
        flex-shrink: 0;
        margin-right: 0;
    }

    .sidebar-item:hover, .sidebar-item.active {
        background: rgba(255, 255, 255, 0.06);
        color: #fff;
        transform: none;
    }

    .sidebar-item:hover i, .sidebar-item.active i {
        color: #fff;
    }

    /* Billing Flyout Styles */
    .billing-flyout-btn {
        display: flex; align-items: center; justify-content: space-between; width: 100%; padding: .6rem .85rem;
        background: transparent; border: none; border-radius: 10px; cursor: pointer; transition: background .2s, transform .2s; margin-bottom: 2px;
    }
    .billing-btn-left { display: flex; align-items: center; gap: .75rem; }
    .billing-btn-left i { width: 20px; text-align: center; font-size: 1.05rem; color: rgba(255, 255, 255, 0.5); transition: color .2s; flex-shrink: 0; }
    .billing-btn-left span { font-size: .81rem; font-weight: 500; color: rgba(255, 255, 255, 0.75); transition: color .2s; }
    
    .billing-flyout-btn:hover { background: rgba(255, 255, 255, 0.06); }
    .billing-flyout-btn:hover .billing-btn-left span, .billing-flyout-btn.active .billing-btn-left span { color: #fff !important; }
    .billing-flyout-btn:hover .billing-btn-left i, .billing-flyout-btn.active .billing-btn-left i { color: #fff !important; }
    .billing-flyout-btn.active { background: rgba(255, 255, 255, 0.1) !important; }
    
    .billing-arrow { font-size: .62rem; color: rgba(255, 255, 255, 0.5); transition: color .25s, transform .25s cubic-bezier(.34, 1.56, .64, 1); }
    .billing-flyout-btn.active .billing-arrow { color: #fff !important; }
    
    .billing-flyout-panel {
        position: fixed; z-index: 99999; min-width: 200px; padding: .5rem; border-radius: .75rem;
        background: #1f6b4a; border: 1px solid rgba(255, 255, 255, 0.2); box-shadow: 0 10px 40px rgba(0, 0, 0, .55);
        opacity: 0; visibility: hidden; transform: translateX(-10px) scale(.96); transform-origin: left center;
        transition: opacity .22s ease, transform .25s cubic-bezier(.34, 1.56, .64, 1), visibility 0s linear .25s; pointer-events: none;
    }
    .billing-flyout-panel::before {
        content: ''; position: absolute; left: -6px; top: 50%; transform: translateY(-50%);
        border-top: 6px solid transparent; border-bottom: 6px solid transparent; border-right: 6px solid #1f6b4a;
    }
    .billing-flyout-panel::after {
        content: 'BILLING'; display: block; font-size: .6rem; font-weight: 700; color: #f3efe6; padding: 0 .5rem .35rem .5rem;
        border-bottom: 1px solid rgba(255, 255, 255, .15); margin-bottom: .3rem; opacity: .8; letter-spacing: 0.05em;
    }
    .billing-flyout-panel.open {
        opacity: 1; visibility: visible; transform: translateX(0) scale(1);
        transition: opacity .22s ease, transform .25s cubic-bezier(.34, 1.56, .64, 1), visibility 0s linear 0s; pointer-events: auto;
    }
    .billing-item {
        display: flex; align-items: center; gap: .55rem; padding: .4rem .65rem; margin-bottom: .18rem; color: #f3efe6;
        text-decoration: none; border-radius: 8px; font-size: .8rem; font-weight: 500; opacity: 0; transform: translateX(-8px) scale(.96);
    }
    .billing-flyout-panel.open .billing-item { animation: flyChipIn .22s ease forwards; animation-delay: calc(.06s + var(--n) * .07s); }
    @keyframes flyChipIn { to { opacity: 1; transform: translateX(0) scale(1); } }
    .billing-item i {
        display: flex; align-items: center; justify-content: center; width: 1.5rem; height: 1.5rem; border-radius: 50%;
        font-size: .65rem; background: rgba(255, 255, 255, 0.15); color: #fff; flex-shrink: 0;
    }
    .billing-item:hover, .billing-item.active { background: rgba(255, 255, 255, 0.15); color: #fff; transform: translateX(5px); }

    .sidebar p.uppercase {
        color: rgba(255, 255, 255, 0.6) !important;
        font-size: 0.7rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.08em !important;
        padding: 0 1rem !important;
        margin-top: 1.5rem !important;
        margin-bottom: 0.5rem !important;
        font-weight: 700 !important;
    }
</style>