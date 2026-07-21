<?php

// Get current page and directory for proper active state detection

$current_file = basename($_SERVER['PHP_SELF']);

$current_path = dirname($_SERVER['PHP_SELF']);

$request_uri = $_SERVER['REQUEST_URI'];



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

            <a href="/GM_HMS/nurse_view/dashboard.php" class="sidebar-item <?php echo (strpos($current_path, 'nurse_view') !== false) ? 'active' : ''; ?>">
                <i class="fas fa-user-nurse"></i>
                <span>Nurse View</span>
            </a>

            <a href="/GM_HMS/pharmacy_view/dashboard.php" class="sidebar-item <?php echo (strpos($current_path, 'pharmacy_view') !== false) ? 'active' : ''; ?>">
                <i class="fas fa-pills"></i>
                <span>Pharmacy View</span>
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

            <a href="nurse_assignment.php"

                class="sidebar-item <?php echo isActive('nurse_assignment.php', $current_file, $current_path, $request_uri) ? 'active' : ''; ?>">

                <i class="fas fa-calendar-alt"></i>

                <span>Nurse Assignments</span>

            </a>

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

                <p class="text-gray-400 text-xs uppercase px-4">Hospital Services</p>

            </div>



            <a href="laboratory.php"
                class="sidebar-item <?php echo isActive('laboratory.php', $current_file, $current_path, $request_uri) ? 'active' : ''; ?>">
                <i class="fas fa-flask"></i>
                <span>Laboratory</span>
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

                <p class="text-gray-400 text-xs uppercase px-4">Finance</p>

            </div>

            <a href="billing_management.php"

                class="sidebar-item <?php echo isActive('billing_management.php', $current_file, $current_path, $request_uri) ? 'active' : ''; ?>">

                <i class="fas fa-file-invoice-dollar"></i>

                <span>Billing</span>

            </a>

            <a href="#insurance" class="sidebar-item">

                <i class="fas fa-shield-alt"></i>

                <span>Insurance</span>

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

    .sidebar-item:hover {
        background: rgba(255, 255, 255, 0.06);
        color: #fff;
        transform: none;
    }

    .sidebar-item:hover i {
        color: #fff;
    }

    .sidebar-item.active {
        background: rgba(255, 255, 255, 0.1) !important;
        color: #fff !important;
        font-weight: 600;
        box-shadow: none;
    }

    .sidebar-item.active i {
        color: #fff !important;
    }

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