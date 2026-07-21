<!-- Nurse Sidebar Navigation -->
<aside
    class="nurse-sidebar fixed lg:relative z-50 h-full transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out"
    id="nurseSidebar">
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

                <a href="vitals.php" class="sidebar-link" data-page="vitals">
                    <i class="fas fa-heartbeat"></i>
                    <span>Vital Signs</span>
                </a>

                <a href="medication.php" class="sidebar-link" data-page="medication">
                    <i class="fas fa-pills"></i>
                    <span style="font-size: 0.8rem; letter-spacing: -0.2px;">Medications</span>
                    <span class="badge badge-warning" id="pending-meds"
                        style="margin-left: auto; font-size: 0.65rem; padding: 2px 6px;">0</span>
                </a>

                <a href="nurse_notes.php" class="sidebar-link" data-page="nurse_notes">
                    <i class="fas fa-notes-medical"></i>
                    <span>Nurse Notes</span>
                </a>

                <a href="ipd_summary.php" class="sidebar-link" data-page="ipd_summary">
                    <i class="fas fa-file-medical-alt"></i>
                    <span>IPD Summary</span>
                </a>

                <!-- Tasks & Schedule Section -->
                <div style="margin-top: 1.5rem; margin-bottom: 0.5rem;">
                    <p
                        style="color: rgba(255, 255, 255, 0.6); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 0 1rem; white-space: nowrap;">
                        <i class="fas fa-calendar-check" style="margin-right: 0.5rem;"></i>Tasks & Schedule
                    </p>
                </div>

                <a href="tasks.php" class="sidebar-link" data-page="tasks">
                    <i class="fas fa-tasks"></i>
                    <span style="font-size: 0.8rem; letter-spacing: -0.2px;">My Tasks</span>
                    <span class="badge badge-danger" id="pending-tasks"
                        style="margin-left: auto; font-size: 0.65rem; padding: 2px 6px;">0</span>
                </a>

                <a href="my_shift.php" class="sidebar-link" data-page="my_shift">
                    <i class="fas fa-clock"></i>
                    <span>My Shift</span>
                </a>

                <!-- Ward Management Section -->
                <div style="margin-top: 1.5rem; margin-bottom: 0.5rem;">
                    <p
                        style="color: rgba(255, 255, 255, 0.6); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 0 1rem; white-space: nowrap;">
                        <i class="fas fa-hospital" style="margin-right: 0.5rem;"></i>Ward Management
                    </p>
                </div>

                <a href="ward_management.php" class="sidebar-link" data-page="ward_management">
                    <i class="fas fa-bed"></i>
                    <span>Ward Overview</span>
                </a>



                <a href="reports.php" class="sidebar-link" data-page="reports">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </div>

            <!-- Quick Actions -->
            <div style="margin-top: auto; padding: 1rem; background: rgba(74, 144, 226, 0.1); border-radius: 0.5rem;">
                <button onclick="quickRecordVitals()" class="btn btn-primary"
                    style="width: 100%; justify-content: center;">
                    <i class="fas fa-heartbeat"></i>
                    <span>Quick Vitals</span>
                </button>
            </div>
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
        width: var(--gm-sidebar-w, 220px);
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
        white-space: nowrap;
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

    @media (max-width: 1024px) {
        .nurse-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out;
        }
        .nurse-sidebar.open {
            transform: translateX(0) !important;
            left: 0 !important;
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

                // Update pending tasks
                const tasksBadge = document.getElementById('pending-tasks');
                if (tasksBadge) {
                    tasksBadge.textContent = stats.tasks.pending || 0;
                    tasksBadge.style.display = 'inline-block';
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
</script>