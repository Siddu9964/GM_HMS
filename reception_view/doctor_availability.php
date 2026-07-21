<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Receptionist', 'admin', 'Admin'])) {
    header("Location: /GM_HMS/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Availability - GM HMS</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/reception_dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/reception.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/doctor_premium.css?v=<?= time() ?>">

    <style>
        /* Fix modal blur issue */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9999;
            overflow-y: auto;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            z-index: 9998;
        }

        .modal-content {
            position: relative;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            z-index: 9999;
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--primary-gradient);
            color: white;
            border-radius: 16px 16px 0 0;
        }

        .modal-title {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 2rem;
        }

        .hidden {
            display: none !important;
        }
    </style>
</head>

<body>
    <div class="reception-layout">
        <!-- Sidebar -->
        <?php include 'includes/reception_sidebar.php'; ?>

        <!-- Main Content -->
        <div class="reception-main-content">
            <!-- Navbar -->
            <?php
            $pageTitle = 'Doctor Availability';
            include 'includes/reception_navbar.php';
            ?>

            <!-- Page Content -->
            <div class="page-content">
                
                <!-- Premium Sticky Header -->
                <div class="premium-header">
                    <div class="header-left">
                        <h1>Doctor Directory</h1>
                        <p>Find and book appointments with healthcare specialists</p>
                    </div>
                    <div class="header-right">
                        <button class="btn-header-action btn-icon-only">
                            <i class="fas fa-bell"></i>
                        </button>
                        <button class="btn-header-action" onclick="doctorManager.loadDoctors()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <button class="btn-header-action">
                            <i class="fas fa-download"></i> Export
                        </button>
                        <div class="header-avatar">
                            <?= substr($_SESSION['username'] ?? 'R', 0, 1) ?>
                        </div>
                    </div>
                </div>

                <!-- Kanban Top Controls -->
                <div class="kanban-controls">
                    <div class="ai-search-bar advanced-search">
                        <select id="departmentFilter" class="integrated-dropdown">
                            <option value="">All Departments</option>
                            <!-- Populated by JS -->
                        </select>
                        <div class="search-divider"></div>
                        <i class="fas fa-search"></i>
                        <input type="text" id="doctorSearch" placeholder="Search doctor or specialization..." autocomplete="off">
                        <span class="kb-shortcut">⌘ K</span>
                    </div>

                    <div class="filter-chips-container" id="filterChipsContainer">
                        <div class="filter-chip active" data-filter="all">All</div>
                        <div class="filter-chip" data-filter="Available">Available Today</div>
                    </div>
                </div>

                <!-- Kanban Board Container -->
                <div class="kanban-board" id="doctorsGrid">
                    <!-- Columns will be injected here via JS -->
                </div>
                
                <!-- Skeleton Loading State for Kanban -->
                <div class="kanban-board hidden" id="loadingOverlay">
                    <div class="sk-column skeleton-box">
                        <div class="sk-card"></div>
                        <div class="sk-card"></div>
                    </div>
                    <div class="sk-column skeleton-box">
                        <div class="sk-card"></div>
                    </div>
                    <div class="sk-column skeleton-box">
                        <div class="sk-card"></div>
                        <div class="sk-card"></div>
                        <div class="sk-card"></div>
                    </div>
                </div>

                <!-- Empty State -->
                <div id="emptyState" class="premium-empty hidden">
                    <i class="fas fa-user-md-slash"></i>
                    <h3>No Doctors Found</h3>
                    <p>Try changing the selected filters or search another specialty.</p>
                    <button class="btn-reset" onclick="doctorManager.clearFilters()">
                        Reset Filters
                    </button>
                </div>

            </div>

            <!-- Floating Quick Actions -->
            <div class="floating-actions">
                <button class="fab-btn" title="Emergency"><i class="fas fa-ambulance" style="color: var(--pr-danger);"></i></button>
                <button class="fab-btn" title="Contact"><i class="fas fa-comment-dots"></i></button>
                <button class="fab-btn" title="Scroll to Top" onclick="window.scrollTo({top: 0, behavior: 'smooth'})"><i class="fas fa-arrow-up"></i></button>
            </div>
        </div>
    </div>

    <!-- Doctor Details Modal -->
    <div id="doctorModal" class="modal">
        <div class="modal-overlay" onclick="doctorManager.closeModal()"></div>
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-user-md"></i>
                    Doctor Profile
                </h3>
                <button class="modal-close" onclick="doctorManager.closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="doctorModalBody">
                <!-- Doctor details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay hidden">
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading...</p>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- JavaScript - MVC View Layer -->
    <script src="assets/js/doctor.js?v=<?= time() ?>"></script>
</body>

</html>