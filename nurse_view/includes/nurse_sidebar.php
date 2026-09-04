<!-- Nurse Sidebar Navigation -->
<aside class="nurse-sidebar" id="nurseSidebar">
    <div style="padding: 1.5rem; height: 100%; display: flex; flex-direction: column;">
        <!-- Logo & Branding -->
        <div style="display: flex; align-items: center; margin-bottom: 2rem; padding: 0 0.5rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.1);">
            <img src="/GM_HMS/assets/images/gm_logoo.png" alt="GM Logo" style="width: 38px; height: auto; margin-right: 0.6rem;">
            <div>
                <h1 style="color: #f3efe6; font-weight: 700; font-size: 1.05rem; margin: 0; white-space: nowrap;">GM hospital</h1>
                <p style="color: rgba(255, 255, 255, 0.55); font-size: 0.75rem; margin: 0;">Nursing Portal</p>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav style="flex: 1; display: flex; flex-direction: column;">
            <div style="flex: 1;">
                <!-- Dashboard -->
                <a href="dashboard.php" class="sidebar-link" data-page="dashboard">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
                </a>

                <!-- Patient Care Section -->
                <div style="margin-top: 1.5rem; margin-bottom: 0.5rem;">
                    <p
                        style="color: rgba(255, 255, 255, 0.6); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 0 1rem; white-space: nowrap;">
                        <i class="fas fa-user-injured" style="margin-right: 0.5rem;"></i>Patient Care
                    </p>
                </div>

                <a href="patient_care.php" class="sidebar-link" data-page="patient_care">
                    <i class="fas fa-users"></i>
                    <span style="font-size: 0.8rem; letter-spacing: -0.2px;">My Patients</span>
                    <span class="badge badge-info" id="patient-count"
                        style="margin-left: auto; font-size: 0.65rem; padding: 2px 6px;">0</span>
                </a>

                <a href="nurse_workspace.php" class="sidebar-link" data-page="nurse_workspace">
                    <i class="fas fa-layer-group"></i>
                    <span>Nurse Workspace</span>
                </a>

                <!-- Tests Menu -->
                <a href="ipd_tests.php" class="sidebar-link" data-page="ipd_tests">
                    <i class="fas fa-vials"></i>
                    <span style="font-size: 0.8rem; letter-spacing: -0.2px;">Tests</span>
                </a>

                <!-- Pharmacy Menu -->
                <div class="sidebar-dropdown">
                    <a href="#" class="sidebar-link" onclick="toggleDropdown(event, 'pharmacy-menu')">
                        <i class="fas fa-prescription-bottle-alt"></i>
                        <span>Pharmacy</span>
                        <i class="fas fa-chevron-down ms-auto" style="font-size: 0.7rem; margin-left: auto; width: auto;"></i>
                    </a>
                    <div class="sidebar-submenu" id="pharmacy-menu" style="display:none; padding-left: 1.5rem; margin-top: 2px;">
                        <a href="ipd_pharmacy_order.php" class="sidebar-link" data-page="ipd_pharmacy_order">
                            <i class="fas fa-cart-plus" style="font-size:0.85rem;"></i><span>Order</span>
                        </a>
                        <a href="ipd_pharmacy_return.php" class="sidebar-link" data-page="ipd_pharmacy_return">
                            <i class="fas fa-undo" style="font-size:0.85rem;"></i><span>Return</span>
                        </a>
                    </div>
                </div>

                <a href="bed_transfer.php" class="sidebar-link" data-page="bed_transfer">
                    <i class="fas fa-bed"></i>
                    <span>Bed transfer</span>
                </a>

                <a href="k_sheet_view.php" class="sidebar-link" data-page="k_sheet_view">
                    <i class="fas fa-file-medical-alt"></i>
                    <span>Case-Sheet</span>
                </a>

                <a href="ipd_summary.php" class="sidebar-link" data-page="ipd_summary">
                    <i class="fas fa-file-medical-alt"></i>
                    <span>IPD Summary</span>
                </a>

                <!-- Schedule Section -->
                <div style="margin-top: 1.5rem; margin-bottom: 0.5rem;">
                    <p
                        style="color: rgba(255, 255, 255, 0.6); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 0 1rem; white-space: nowrap;">
                        <i class="fas fa-calendar-check" style="margin-right: 0.5rem;"></i>Schedule
                    </p>
                </div>

                <a href="my_shift.php" class="sidebar-link" data-page="my_shift">
                    <i class="fas fa-clock"></i>
                    <span>My Shift</span>
                </a>

                <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['Superintendent_Nurse', 'Superintendent Nurse', 'admin', 'Admin', 'Head Nurse'])): ?>
                <a href="shift_assignment.php" class="sidebar-link" data-page="shift_assignment">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Shift Assignment</span>
                </a>
                <a href="all_shift_assignments.php" class="sidebar-link" data-page="all_shift_assignments">
                    <i class="fas fa-list-alt"></i>
                    <span>All Assigned Shifts</span>
                </a>
                <?php endif; ?>

                <!-- Ward Management Section -->
                <div style="margin-top: 1.5rem; margin-bottom: 0.5rem;">
                    <p
                        style="color: rgba(255, 255, 255, 0.6); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 0 1rem; white-space: nowrap;">
                        <i class="fas fa-hospital" style="margin-right: 0.5rem;"></i>Ward Management
                    </p>
                </div>

                <a href="ward_management.php" class="sidebar-link" data-page="ward_management">
                    <i class="fas fa-hospital"></i>
                    <span>Ward Overview</span>
                </a>



                <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['Superintendent_Nurse', 'Superintendent Nurse', 'admin', 'Admin', 'Head Nurse'])): ?>
                <a href="reports.php" class="sidebar-link" data-page="reports">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div style="margin-top: auto; padding: 1rem; background: rgba(74, 144, 226, 0.1); border-radius: 0.5rem; margin-bottom: 0.5rem;">
                <button onclick="quickRecordVitals()" class="btn btn-primary"
                    style="width: 100%; justify-content: center;">
                    <i class="fas fa-heartbeat"></i>
                    <span>Quick Vitals</span>
                </button>
            </div>

            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'Admin'])): ?>
            <div style="padding: 0 1rem 1rem 1rem;">
                <a href="/GM_HMS/view/admin_dashboard.php" class="sidebar-link" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; justify-content: center; border: 1px solid rgba(239, 68, 68, 0.2);">
                    <i class="fas fa-arrow-left" style="color: #ef4444; margin-right: 5px;"></i>
                    <span>Exit to Admin</span>
                </a>
            </div>
            <?php endif; ?>
        </nav>
    </div>
</aside>

<style>
    /* Scoped Reset to protect Sidebar from Bootstrap */
    :root {
        --gm-sidebar-w: 185px; /* Restored to compact size */
    }

    .nurse-sidebar {
        font-family: 'Inter', sans-serif !important;
        box-sizing: border-box;
        width: var(--gm-sidebar-w, 185px);
        background: #1f6b4a !important; /* Medical Teal */
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        min-height: 100vh;
        overflow-y: auto;
        z-index: 1000;
        border-right: 1px solid rgba(255,255,255,0.1);
        display: flex;
        flex-direction: column;
        transition: transform 0.3s ease-in-out;
    }

    .nurse-sidebar *:not(i) {
        font-family: 'Inter', sans-serif !important;
        box-sizing: border-box;
    }

    .sidebar-link {
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

    .sidebar-link span:not(.badge) {
        flex: 1;
        line-height: 1.25;
        word-wrap: break-word;
    }

    .sidebar-link i {
        font-size: 1.05rem;
        width: 20px;
        min-width: 20px;
        text-align: center;
        color: rgba(255, 255, 255, 0.5);
        transition: color 0.2s ease;
        flex-shrink: 0;
    }

    .sidebar-link:hover {
        background: rgba(255, 255, 255, 0.06);
        color: #fff;
    }

    .sidebar-link:hover i {
        color: #fff;
    }

    .sidebar-link.active {
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
        font-weight: 600;
    }

    .sidebar-link.active i {
        color: #fff;
    }

    .badge {
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 700;
    }

    .badge-info { background: rgba(56, 189, 248, 0.2); color: #38bdf8; }
    .badge-warning { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
    .badge-danger { background: rgba(248, 113, 113, 0.2); color: #f87171; }

    /* Desktop layout adjustments */
    @media (min-width: 1024px) {
        .nurse-sidebar {
            transform: translateX(0) !important;
        }
        
        /* Push content wrapper to the right globally for nurse pages */
        body .content-wrapper {
            margin-left: var(--gm-sidebar-w, 185px);
            width: calc(100% - var(--gm-sidebar-w, 185px));
        }
    }

    /* Mobile layout adjustments */
    @media (max-width: 1023px) {
        .nurse-sidebar {
            transform: translateX(-100%);
        }
        .nurse-sidebar.open {
            transform: translateX(0) !important;
        }
        body .content-wrapper {
            margin-left: 0;
            width: 100%;
        }
    }
</style>

<script>
    // Set active link based on current page
    document.addEventListener('DOMContentLoaded', function () {
        const currentPage = window.location.pathname.split('/').pop().replace('.php', '');
        const links = document.querySelectorAll('.sidebar-link');

        links.forEach(link => {
            if (link.dataset.page === currentPage) {
                link.classList.add('active');
            }
        });

        // Load counts
        loadNurseCounts();
    });

    // Load nurse dashboard counts
    async function loadNurseCounts() {
        try {
            const response = await fetch('api/dashboard.php');
            const result = await response.json();

            if (result.success) {
                const stats = result.data.statistics;

                // Update patient count
                const patientBadge = document.getElementById('patient-count');
                if (patientBadge) {
                    patientBadge.textContent = stats.shift.total_patients || 0;
                }

                // Update pending medications
                const medsBadge = document.getElementById('pending-meds');
                if (medsBadge) {
                    medsBadge.textContent = stats.medications.pending || 0;
                    medsBadge.style.display = 'inline-block';
                }


            }
        } catch (error) {
            console.error('Failed to load nurse counts:', error);
        }
    }

    // Quick vitals function
    function quickRecordVitals() {
        window.location.href = 'vitals.php';
    }

    // Toggle sidebar for mobile
    function toggleSidebar() {
        const sidebar = document.getElementById('nurseSidebar');
        sidebar.classList.toggle('open');
    }

    // Toggle dropdown menus
    function toggleDropdown(event, menuId) {
        event.preventDefault();
        const menu = document.getElementById(menuId);
        const icon = event.currentTarget.querySelector('.fa-chevron-down, .fa-chevron-up');
        
        if (menu.style.display === 'none') {
            menu.style.display = 'block';
            if(icon) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
        } else {
            menu.style.display = 'none';
            if(icon) {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        }
    }
</script>