<?php
session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Nurse', 'admin', 'Admin'])) {
    header("Location: /GM_HMS/login.php");
    exit();
}

$nurseId = $_SESSION['user_id'] ?? null;
$nurseName = $_SESSION['username'] ?? 'Nurse';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Dashboard - GM HMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        :root {
            --primary: #1f6b4a;
            --primary-dark: #144d34;
            --success: #16a34a;
            --warning: #f59e0b;
            --danger: #e11d48;
            --info: #0ea5e9;
            --light: #F8F9FA;
            --dark: #1e293b;
        }

        body {
            background: #F5F7FA;
            min-height: 100vh;
            display: flex;
        }

        .main-layout {
            display: flex;
            width: 100%;
        }

        .content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .main-content {
            flex: 1;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: none;
            padding: 0;
        }

        .welcome-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 20px 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(31, 107, 74, 0.3);
        }

        .welcome-card h2 {
            font-size: 22px;
            margin-bottom: 5px;
        }

        .welcome-card p {
            font-size: 14px;
            opacity: 0.9;
            margin: 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 18px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }

        .stat-icon.blue { background: var(--primary); }
        .stat-icon.green { background: var(--success); }
        .stat-icon.orange { background: var(--warning); }
        .stat-icon.red { background: var(--danger); }

        .stat-content h3 {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
            margin: 0 0 2px 0;
            line-height: 1;
        }

        .stat-content p {
            color: #6C757D;
            font-size: 13px;
            margin: 0;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #6C757D;
        }

        .loading i {
            font-size: 48px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-top: 20px;
        }

        .action-btn {
            background: white;
            border: 2px solid var(--primary);
            color: var(--primary);
            padding: 12px 15px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .action-btn:hover {
            background: var(--primary);
            color: white;
        }
    </style>
</head>
<body>
    <div class="main-layout">
        <!-- Sidebar -->
        <?php include 'includes/nurse_sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <div class="content-wrapper">
            <!-- Navbar -->
            <?php include 'includes/nurse_navbar.php'; ?>
            
            <!-- Page Content -->
            <div class="main-content">
                <div class="container">
                    <div class="welcome-card">
                        <h2>Welcome, <?php echo htmlspecialchars($nurseName); ?>! 👋</h2>
                        <p>Your nurse dashboard is ready. Here's your overview for today.</p>
                    </div>

                    <div class="stats-grid" id="statsGrid">
                        <div class="stat-card">
                            <div class="stat-icon blue">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <h3 id="totalPatients">--</h3>
                                <p>Assigned Patients</p>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon green">
                                <i class="fas fa-pills"></i>
                            </div>
                            <div class="stat-content">
                                <h3 id="pendingMeds">--</h3>
                                <p>Pending Medications</p>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon orange">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div class="stat-content">
                                <h3 id="pendingTasks">--</h3>
                                <p>Pending Tasks</p>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon red">
                                <i class="fas fa-heartbeat"></i>
                            </div>
                            <div class="stat-content">
                                <h3 id="vitalsRecorded">--</h3>
                                <p>Vitals Recorded Today</p>
                            </div>
                        </div>
                    </div>

                    <div class="quick-actions">
                        <a href="vitals.php" class="action-btn">
                            <i class="fas fa-heartbeat"></i> Record Vitals
                        </a>
                        <a href="medication.php" class="action-btn">
                            <i class="fas fa-pills"></i> Medications
                        </a>
                        <a href="nurse_notes.php" class="action-btn">
                            <i class="fas fa-notes-medical"></i> Nurse Notes
                        </a>
                        <a href="tasks.php" class="action-btn">
                            <i class="fas fa-tasks"></i> My Tasks
                        </a>
                        <a href="patient_care.php" class="action-btn">
                            <i class="fas fa-user-injured"></i> My Patients
                        </a>
                        <a href="my_shift.php" class="action-btn">
                            <i class="fas fa-clock"></i> My Shift
                        </a>
                    </div>

                    <div class="loading" id="loading">
                        <i class="fas fa-spinner"></i>
                        <p>Loading dashboard data...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Load dashboard data
        async function loadDashboard() {
            try {
                const response = await fetch('api/dashboard.php');
                const result = await response.json();

                if (result.success) {
                    const stats = result.data.statistics;
                    
                    document.getElementById('totalPatients').textContent = stats.shift.total_patients || 0;
                    document.getElementById('pendingMeds').textContent = stats.medications.pending || 0;
                    document.getElementById('pendingTasks').textContent = stats.tasks.pending || 0;
                    document.getElementById('vitalsRecorded').textContent = stats.vitals.total_recorded || 0;
                    
                    document.getElementById('loading').style.display = 'none';
                } else {
                    console.error('Failed to load dashboard:', result.message);
                    document.getElementById('loading').innerHTML = '<p style="color: var(--danger);">Failed to load dashboard data</p>';
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('loading').innerHTML = '<p style="color: var(--danger);">Error loading dashboard</p>';
            }
        }

        // Load data on page load
        loadDashboard();
    </script>
</body>
</html>
