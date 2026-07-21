<?php
// Calculate base path relative to doctors_view folder
// Base path logic removed to restore original routing
?>
<!-- Doctor Sidebar Navigation -->
<aside class="sidebar transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out" id="doctorSidebar" style="background: #1f6b4a !important;">
    <div style="padding: 1.25rem 0.75rem; height: 100%; display: flex; flex-direction: column;">
        <!-- Logo & Branding -->
        <div style="display: flex; align-items: center; margin-bottom: 2rem; padding: 0 0.5rem 1rem; border-bottom: 1px solid var(--gm-border);">
            <img src="/GM_HMS/assets/images/gm_logoo.png" alt="GM Logo" style="width: 38px; height: auto; margin-right: 0.6rem;">
            <div>
                <h1 style="color: #f3efe6; font-weight: 700; font-size: 1.05rem; margin: 0; white-space: nowrap;">GM hospital</h1>
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

                <!-- Patient Management Section -->
                <div style="margin-top: 1.5rem; margin-bottom: 0.5rem;">
                    <p style="color: rgba(255, 255, 255, 0.55); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 0 1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 600;">
                        Patient Management
                    </p>
                </div>
                
                <a href="mypatient.php" class="sidebar-link" data-page="mypatient">
                    <i class="fas fa-user-injured"></i>
                    <span>My Patients</span>
                </a>

                <a href="opd_patients.php" class="sidebar-link" data-page="opd">
                    <i class="fas fa-stethoscope"></i>
                    <span>OPD Queue</span>
                    <span class="badge badge-info" id="opd-count" style="margin-left: auto; font-size: 0.7rem;">0</span>
                </a>

                <a href="ipd_patients.php" class="sidebar-link" data-page="ipd">
                    <i class="fas fa-bed"></i>
                    <span>IPD Patients</span>
                    <span class="badge badge-warning" id="ipd-count" style="margin-left: auto; font-size: 0.7rem;">0</span>
                </a>

                <!-- Clinical Tools Section -->
                <div style="margin-top: 1.5rem; margin-bottom: 0.5rem;">
                    <p style="color: rgba(255, 255, 255, 0.55); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 0 1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 600;">
                        Clinical Tools
                    </p>
                </div>
                
                <a href="consultation.php" class="sidebar-link" data-page="consultation">
                    <i class="fas fa-notes-medical"></i>
                    <span>Consultation</span>
                </a>

                <a href="ai_symptom_analysis.php" class="sidebar-link" data-page="ai-analysis">
                    <i class="fas fa-brain"></i>
                    <span>AI Symptom Analysis</span>
                    <span class="badge badge-primary" style="margin-left: auto; font-size: 0.6rem;">AI</span>
                </a>

                <a href="prescription.php" class="sidebar-link" data-page="prescription">
                    <i class="fas fa-prescription"></i>
                    <span>Prescriptions</span>
                </a>

                <a href="lab_reports.php" class="sidebar-link" data-page="lab-reports">
                    <i class="fas fa-flask"></i>
                    <span>Lab Reports</span>
                    <span class="badge badge-danger" id="pending-labs" style="margin-left: auto; font-size: 0.7rem; display: none;">0</span>
                </a>

                <!-- Reports & Analytics Section -->
                <div style="margin-top: 1.5rem; margin-bottom: 0.5rem;">
                    <p style="color: rgba(255, 255, 255, 0.55); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 0 1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 600;">
                        Reports & Analytics
                    </p>
                </div>
                
                <a href="analytics.php" class="sidebar-link" data-page="analytics">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analytics</span>
                </a>

                <a href="notifications.php" class="sidebar-link" data-page="notifications">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                    <span class="badge badge-danger" id="notification-count" style="margin-left: auto; font-size: 0.7rem; display: none;">0</span>
                </a>
            </div>
        </nav>
    </div>
</aside>

<style>
    /* Scoped Reset to protect Sidebar from Bootstrap */
    .sidebar {
        font-family: 'Inter', sans-serif !important;
        box-sizing: border-box;
        width: var(--gm-sidebar-w);
        background: #1f6b4a !important; /* Matches reception_view Medical Teal exactly */
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        min-height: 100vh;
        overflow-y: auto;
        z-index: 1000;
        border-right: 1px solid var(--gm-border, #e2e8f0);
        display: flex;
        flex-direction: column;
    }

    .sidebar *:not(i) {
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
        background: linear-gradient(90deg, rgba(31, 107, 74, .22), rgba(31, 107, 74, .09));
        color: #f3efe6;
        border-color: rgba(31, 107, 74, .38);
        border: 1px solid transparent;
        animation: drChipIn .22s ease forwards, chipGlow 2.5s 1s ease-in-out infinite;
    }

    .sidebar-link.active i {
        background: #1f6b4a;
        color: #f3efe6;
        box-shadow: 0 0 8px rgba(31, 107, 74, .5);
        border-radius: 50%;
        padding: 4px;
        width: 1.5rem;
        height: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.65rem;
    }

    @keyframes chipGlow {
        0%,
        100% {
            box-shadow: 0 0 0 1px rgba(31, 107, 74, .18);
        }

        50% {
            box-shadow: 0 0 0 3px rgba(31, 107, 74, .12), 0 0 12px rgba(31, 107, 74, .1);
        }
    }

    /* Mobile Toggle */
    @media (max-width: 1024px) {
        .sidebar.mobile-open {
            transform: translateX(0) !important;
            left: 0 !important;
        }
        
        aside.sidebar.mobile-open {
            transform: translateX(0) !important;
            left: 0 !important;
        }
        
        #doctorSidebar.mobile-open {
            transform: translateX(0) !important;
            left: 0 !important;
        }
    }

    .badge {
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 700;
        background: rgba(255, 255, 255, 0.1);
    }
    
    .badge-info { color: #38bdf8; }
    .badge-warning { color: #fbbf24; }
    .badge-danger { color: #f87171; background: rgba(248, 113, 113, 0.2); }
    .badge-primary { color: #818cf8; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var path = window.location.pathname;
    var page = path.split('/').pop().replace('.php', '');

    const pageMapping = {
        'dashboard': 'dashboard',
        'mypatient': 'mypatient',
        'opd_patients': 'opd',
        'ipd_patients': 'ipd',
        'consultation': 'consultation',
        'ai_symptom_analysis': 'ai-analysis',
        'prescription': 'prescription',
        'lab_reports': 'lab-reports',
        'analytics': 'analytics',
        'notifications': 'notifications'
    };
    
    const activePage = pageMapping[page] || page;

    // Standard sidebar link active state
    document.querySelectorAll('.sidebar-link').forEach(function (link) {
        if (link.dataset.page === activePage) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
    
    // Load notification count
    if (typeof loadNotificationCount === 'function') loadNotificationCount();
    
    // Load patient counts
    if (typeof loadPatientCounts === 'function') loadPatientCounts();
});

// Load notification count
async function loadNotificationCount() {
    try {
        if (typeof API !== 'undefined') {
            const response = await API.get('notifications/unread-count');
            if (response.success && response.data.count > 0) {
                const badge = document.getElementById('notification-count');
                if (badge) {
                    badge.textContent = response.data.count;
                    badge.style.display = 'inline-block';
                }
            }
        }
    } catch (error) {
        console.error('Failed to load notification count:', error);
    }
}

// Load patient counts
async function loadPatientCounts() {
    try {
        if (typeof API !== 'undefined') {
            const doctorId = "<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; ?>";
            
            if (!doctorId) return;
            
            // OPD count (Scheduled)
            const opdResponse = await API.get(`doctors/${doctorId}/opd-patients?status=Scheduled`);
            if (opdResponse.success) {
                const count = opdResponse.data.appointments.length;
                const badge = document.getElementById('opd-count');
                if (badge) {
                    badge.textContent = count;
                    badge.style.display = count > 0 ? 'inline-block' : 'none';
                }
            }
            
            // IPD count (Admitted)
            const ipdResponse = await API.get(`doctors/${doctorId}/ipd-patients?status=Admitted`);
            if (ipdResponse.success) {
                const count = ipdResponse.data.admissions.length;
                const badge = document.getElementById('ipd-count');
                if (badge) {
                    badge.textContent = count;
                    badge.style.display = count > 0 ? 'inline-block' : 'none';
                }
            }
        }
    } catch (error) {
        console.error('Failed to load patient counts:', error);
    }
}
</script>
