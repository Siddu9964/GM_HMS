<?php
// Calculate base path relative to reception_view folder (case-insensitive for gm_HMS)
$currentPath = $_SERVER['PHP_SELF'];
$depth = substr_count(str_ireplace('/GM_HMS/reception_view/', '', $currentPath), '/');
$basePath = str_repeat('../', $depth);
?>
<!-- Reception Sidebar Navigation -->
<aside
    class="reception-sidebar fixed lg:relative z-50 h-full transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out"
    id="receptionSidebar">
    <div style="padding: 1.25rem 0.75rem; height: 100%; display: flex; flex-direction: column;">
        <!-- Logo & Branding -->
        <div style="display: flex; align-items: center; margin-bottom: 2rem; padding: 0 0.5rem 1rem; border-bottom: 1px solid var(--gm-border);">
            <img src="/GM_HMS/assets/images/gm_logoo.png" alt="GM Logo" style="width: 38px; height: auto; margin-right: 0.6rem;">
            <div>
                <h1 style="color: #f3efe6; font-weight: 700; font-size: 1.05rem; margin: 0; white-space: nowrap;">GM hospital</h1>
                <!-- <p style="color: #94a3b8; font-size: 0.75rem; margin: 0;">Reception Portal</p> -->
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav style="flex: 1; display: flex; flex-direction: column;">
            <div style="flex: 1;">
                <!-- Dashboard -->
                <a href="<?php echo $basePath; ?>index.php" class="sidebar-link" data-page="dashboard">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
                </a>

                <!-- OPD & IPD Section -->


                <a href="<?php echo $basePath; ?>opd_management.php" class="sidebar-link" data-page="opd-management">
                    <i class="fas fa-stethoscope"></i>
                    <span>Outpatients (OPD)</span>
                </a>
                <a href="<?php echo $basePath; ?>patient_registration.php" class="sidebar-link"
                    data-page="patient-registration">
                    <i class="fas fa-user-plus"></i>
                    <span>Patient Registration</span>
                </a>
                <a href="<?php echo $basePath; ?>ipd_management/public/index.php" class="sidebar-link"
                    data-page="ipd-admission">
                    <i class="fas fa-bed"></i>
                    <span>Inpatients (IPD)</span>
                </a>
                <a href="<?php echo $basePath; ?>doctor_availability.php" class="sidebar-link"
                    data-page="doctor-availability">
                    <i class="fas fa-user-md"></i>
                    <span>Doctors</span>
                </a>
                <a href="<?php echo $basePath; ?>prescriptions.php" class="sidebar-link" data-page="prescriptions">
                    <i class="fas fa-prescription"></i>
                    <span>Prescriptions</span>
                </a>
                <!-- <div style="margin-top: 1.5rem; margin-bottom: 0.5rem;">
                <p style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 0 1rem;">
                    <i class="fas fa-file-invoice-dollar" style="margin-right: 0.5rem;"></i>Finance
                </p>
            </div> -->
                <div class="opd-drop-wrap">
                    <button class="opd-drop-btn" id="opdBtn" onclick="toggleOpd(this)" aria-expanded="false">
                        <span class="opd-btn-left">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span>OPD Billing</span>
                        </span>
                        <i class="fas fa-chevron-right opd-arrow"></i>
                    </button>
                    <div class="opd-drop-panel" id="opdPanel">
                        <!-- Removed Registration / Appointment -->
                        <a href="<?php echo $basePath; ?>opd_billing.php" class="opd-item" data-pd="opd_billing"
                            style="--n:1">
                            <i class="fas fa-flask"></i>
                            <span>Lab Test</span>
                        </a>
                    </div>
                </div>

                <!-- Quick Actions -->
                <!-- <div style="margin-top: 2rem; padding: 1rem; background: rgba(59, 130, 246, 0.1); border-radius: 0.5rem;">
                <button onclick="window.location.href='<?php echo $basePath; ?>patient_registration.php'" class="btn btn-primary" style="width: 100%; justify-content: center;">
                    <i class="fas fa-plus-circle"></i>
                    <span>New Patient</span>
                </button>
            </div> -->
            </div>

            <!-- Quick Actions -->
            <!-- <div style="margin-top: auto; padding: 1rem; background: rgba(31, 107, 74, 0.1); border-radius: 0.5rem;">
                <button onclick="window.location.href='<?php echo $basePath; ?>patient_registration.php'" class="btn btn-primary" style="width: 100%; justify-content: center;">
                    <i class="fas fa-plus-circle"></i>
                    <span>New Patient</span>
                </button>
            </div> -->
        </nav>
    </div>
</aside>

<style>
    /* Scoped Reset to protect Sidebar from Bootstrap */
    .reception-sidebar {
        font-family: 'Inter', sans-serif !important;
        box-sizing: border-box;
    }

    .reception-sidebar *:not(i) {
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
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
        font-weight: 600;
    }

    .sidebar-link.active i {
        color: #fff;
    }

    /* Mobile Toggle */
    @media (max-width: 1024px) {
        .reception-sidebar {
            /* Managed by transform classes */
        }
        
        .reception-sidebar.mobile-open {
            transform: translateX(0) !important;
            left: 0 !important;
        }
        
        /* Higher specificity to ensure it works */
        aside.reception-sidebar.mobile-open {
            transform: translateX(0) !important;
            left: 0 !important;
        }
        
        #receptionSidebar.mobile-open {
            transform: translateX(0) !important;
            left: 0 !important;
        }
        
        /* MOBILE OPD SUBMENU FIX - Position inside sidebar */
        .opd-drop-panel {
            position: relative !important;
            top: auto !important;
            left: auto !important;
            z-index: auto !important;
            min-width: 100% !important;
            margin: 0.5rem 0 !important;
            transform: none !important;
            opacity: 1 !important;
            visibility: visible !important;
            pointer-events: auto !important;
            display: none !important;
            background: rgba(31, 107, 74, 0.1) !important;
            border: 1px solid rgba(31, 107, 74, 0.3) !important;
            border-radius: 0.5rem !important;
            padding: 0.5rem !important;
            box-shadow: none !important;
            backdrop-filter: none !important;
        }
        
        .opd-drop-panel.open {
            display: block !important;
            transform: none !important;
            opacity: 1 !important;
            visibility: visible !important;
        }
        
        /* Remove arrow on mobile */
        .opd-drop-panel::before {
            display: none !important;
        }
        
        /* Remove section label on mobile */
        .opd-drop-panel::after {
            display: none !important;
        }
        
        /* Mobile OPD items styling */
        .opd-item {
            opacity: 1 !important;
            transform: none !important;
            animation: none !important;
            margin: 0.25rem 0 !important;
            padding: 0.75rem 1rem !important;
            background: transparent !important;
            border: 1px solid rgba(31, 107, 74, 0.2) !important;
            border-radius: 0.375rem !important;
            color: #cbd5e1 !important;
            font-size: 0.875rem !important;
        }
        
        .opd-item:hover {
        background: rgba(255, 255, 255, 0.15);
        color: #ffffff;
        transform: translateX(5px);
        border-radius: 8px;
    }
        
        .opd-item i {
            background: rgba(31, 107, 74, 0.2) !important;
            color: #1f6b4a !important;
        }
        
        .opd-item:hover i {
            background: rgba(31, 107, 74, 0.4) !important;
            color: var(--gm-text) !important;
        }
    }

    /* ══════════════════════════════════════════════
   OPD BILLING — Drop-Right Flyout
   ══════════════════════════════════════════════ */
    .opd-drop-wrap {
        margin-bottom: .2rem;
        position: relative;
    }

    .opd-drop-btn {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: .6rem 1rem;
        background: transparent;
        border: none;
        border-radius: .5rem;
        cursor: pointer;
        gap: .5rem;
        transition: background .2s, transform .2s;
    }

    .opd-btn-left {
        display: flex;
        align-items: center;
        gap: .7rem;
    }

    .opd-btn-left i {
        width: 1.25rem;
        text-align: center;
        font-size: .95rem;
        color: #f3efe6; opacity: 0.8;
        transition: color .2s, filter .2s;
    }

    .opd-btn-left span {
        font-size: .875rem;
        font-weight: 500;
        font-family: inherit;
        color: #f3efe6; opacity: 0.8;
        transition: color .2s;
    }

    .opd-drop-btn:hover .opd-btn-left span,
    .opd-drop-btn[aria-expanded="true"] .opd-btn-left span {
        color: #f3efe6;
    }

    .opd-drop-btn:hover {
        background: rgba(255, 255, 255, 0.15);
        color: #ffffff;
        border-radius: 8px;
    }

    .opd-drop-btn[aria-expanded="true"] {
        background: rgba(255, 255, 255, 0.2);
    }

    .opd-drop-btn[aria-expanded="true"] .opd-btn-left i {
        color: #ffffff;
        filter: drop-shadow(0 0 5px rgba(255, 255, 255, 0.3));
    }

    /* Caret — always points right, nudges on open */
    .opd-arrow {
        font-size: .62rem;
        color: #f3efe6; opacity: 0.8;
        transition: color .25s, transform .25s cubic-bezier(.34, 1.56, .64, 1),
            filter .25s;
        flex-shrink: 0;
    }

    .opd-drop-btn[aria-expanded="true"] .opd-arrow {
        color: #ffffff;
        transform: translateX(3px);
        filter: drop-shadow(0 0 4px rgba(255, 255, 255, .3));
    }

    /* ══ DROP-RIGHT PANEL ══════════════════════════════════
   Floats to the RIGHT of the sidebar button
   ════════════════════════════════════════════════════ */
    .opd-drop-panel {
        position: fixed;
        /* escape sidebar overflow */
        top: 0;
        left: 0;
        /* JS sets exact values */
        z-index: 99999;
        min-width: 220px;
        padding: .55rem .5rem;
        border-radius: .75rem;
        background: #1f6b4a; /* Pine green background */
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 10px 40px rgba(0, 0, 0, .55),
            0 0 0 1px rgba(255, 255, 255, .1),
            inset 0 1px 0 rgba(255, 255, 255, .07);
        opacity: 0;
        visibility: hidden;
        transform: translateX(-10px) scale(.96);
        transform-origin: left center;
        transition: opacity .22s ease,
            transform .25s cubic-bezier(.34, 1.56, .64, 1),
            visibility 0s linear .25s;
        pointer-events: none;
    }

    /* Left-pointing arrow tip */
    .opd-drop-panel::before {
        content: '';
        position: absolute;
        left: -6px;
        top: 50%;
        transform: translateY(-50%);
        border-top: 6px solid transparent;
        border-bottom: 6px solid transparent;
        border-right: 6px solid #1f6b4a;
    }

    /* Section label */
    .opd-drop-panel::after {
        content: 'OPD Billing';
        display: block;
        font-size: .6rem;
        font-weight: 700;
        font-family: inherit;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #f3efe6;
        padding: 0 .55rem .35rem .55rem;
        border-bottom: 1px solid rgba(255, 255, 255, .15);
        margin-bottom: .3rem;
        opacity: .8;
    }

    .opd-drop-panel.open {
        opacity: 1;
        visibility: visible;
        transform: translateX(0) scale(1);
        transition: opacity .22s ease,
            transform .25s cubic-bezier(.34, 1.56, .64, 1),
            visibility 0s linear 0s;
        pointer-events: auto;
    }

    /* ── Pill chip items ── */
    .opd-item {
        display: flex;
        align-items: center;
        gap: .55rem;
        padding: .38rem .65rem;
        margin-bottom: .18rem;
        color: #94a3b8;
        text-decoration: none;
        border-radius: 2rem;
        font-size: .78rem;
        font-weight: 500;
        font-family: inherit;
        border: 1px solid transparent;
        white-space: nowrap;
        opacity: 0;
        transform: translateX(-8px) scale(.96);
        transition: background .18s, color .18s, transform .18s,
            box-shadow .18s, border-color .18s;
    }

    .opd-drop-panel.open .opd-item {
        animation: drChipIn .22s ease forwards;
        animation-delay: calc(.06s + var(--n) * .07s);
    }

    @keyframes drChipIn {
        to {
            opacity: 1;
            transform: translateX(0) scale(1);
        }
    }

    .opd-item i {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 50%;
        font-size: .65rem;
        background: rgba(255, 255, 255, 0.15);
        color: #ffffff;
        flex-shrink: 0;
        transition: background .18s, color .18s, box-shadow .18s;
    }

    .opd-item:hover {
        background: rgba(255, 255, 255, 0.15);
        color: #ffffff;
        transform: translateX(5px);
        border-radius: 8px;
    }

    .opd-item:hover i {
        background: rgba(255, 255, 255, 0.3);
        color: #f3efe6;
        box-shadow: 0 0 8px rgba(255, 255, 255, .3);
    }

    .opd-item.active {
        background: linear-gradient(90deg, rgba(31, 107, 74, .22), rgba(31, 107, 74, .09));
        color: #f3efe6;
        border-color: rgba(31, 107, 74, .38);
        animation: drChipIn .22s ease forwards, chipGlow 2.5s 1s ease-in-out infinite;
    }

    .opd-item.active i {
        background: #1f6b4a;
        color: #f3efe6;
        box-shadow: 0 0 8px rgba(31, 107, 74, .5);
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

    /* Responsive fine-tuning */
    @media (max-width: 768px) {
        .opd-drop-btn {
            padding: .7rem 1rem;
        }

        .opd-item {
            padding: .42rem .65rem;
        }
    }
</style>

<script>
    function toggleOpd(btn) {
        var panel = document.getElementById('opdPanel');
        var isOpen = btn.getAttribute('aria-expanded') === 'true';

        // Check if we're on mobile/tablet
        var isMobile = window.innerWidth <= 1024;

        if (!isOpen) {
            if (isMobile) {
                // MOBILE: Don't set fixed positioning - let CSS handle it
                // Panel is positioned relative inside sidebar
                console.log('OPD dropdown opened on mobile');
            } else {
                // DESKTOP: Position the fixed panel right of the button using viewport coords
                var rect = btn.getBoundingClientRect();
                var gap = 10;                      // gap between sidebar edge and card
                panel.style.top = rect.top + 'px';
                panel.style.left = (rect.right + gap) + 'px';

                // Re-trigger stagger animation
                panel.querySelectorAll('.opd-item').forEach(function (el) {
                    el.style.animation = 'none';
                    el.offsetHeight;
                    el.style.animation = '';
                });
            }
        }

        btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        panel.classList.toggle('open', !isOpen);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var path = window.location.pathname;
        var page = path.split('/').pop().replace('.php', '');

        // Standard sidebar link active state
        document.querySelectorAll('.sidebar-link').forEach(function (link) {
            var active = false;
            if (path.includes('/ipd_management/')) {
                active = link.dataset.page === 'ipd-admission';
            } else if (path.includes('opd_management.php')) {
                active = link.dataset.page === 'opd-management';
            } else {
                var pn = (page === 'index' || page === '') ? 'dashboard' : page.replace(/_/g, '-');
                active = link.dataset.page === pn;
            }
            if (active) link.classList.add('active');
        });

        // OPD item active state
        var OPD = ['patient_registration', 'opd_billing'];
        document.querySelectorAll('.opd-item').forEach(function (item) {
            if (item.dataset.pd === page) item.classList.add('active');
        });

        // Close flyout when clicking outside
        document.addEventListener('click', function (e) {
            var btn = document.getElementById('opdBtn');
            var panel = document.getElementById('opdPanel');
            if (btn && panel && !btn.contains(e.target) && !panel.contains(e.target)) {
                btn.setAttribute('aria-expanded', 'false');
                panel.classList.remove('open');
            }
        });
    });
</script>