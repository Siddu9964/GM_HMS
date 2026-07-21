<?php
session_start();

// Check authentication
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Nurse', 'admin', 'Admin'])) {
    header('Location: ../login.php');
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
    <title>My Tasks - GM HMS</title>
    
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
            --primary: #4A90E2;
            --primary-dark: #357ABD;
            --success: #28A745;
            --warning: #FFC107;
            --danger: #DC3545;
            --info: #17A2B8;
            --light: #F8F9FA;
            --dark: #343A40;
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
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .page-header h1 {
            font-size: 24px;
            color: var(--dark);
            font-weight: 700;
        }

        .task-list {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .task-item {
            display: grid;
            grid-template-columns: auto 1fr auto auto;
            padding: 20px;
            border-bottom: 1px solid #F1F3F5;
            align-items: center;
            gap: 20px;
            transition: background 0.2s;
        }

        .task-item:hover {
            background: #F8F9FA;
        }

        .task-status {
            width: 25px;
            height: 25px;
            border: 2px solid #DEE2E6;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            transition: 0.2s;
        }

        .task-item.completed .task-status {
            background: var(--success);
            border-color: var(--success);
        }

        .task-content h4 {
            font-size: 16px;
            color: var(--dark);
            margin-bottom: 4px;
        }

        .task-item.completed h4 {
            text-decoration: line-through;
            color: #ADB5BD;
        }

        .task-meta {
            font-size: 12px;
            color: #6C757D;
            display: flex;
            gap: 15px;
        }

        .priority-badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .priority-urgent { background: #FFF5F5; color: var(--danger); }
        .priority-high { background: #FFF9DB; color: #F59F00; }
        .priority-normal { background: #E7F5FF; color: var(--primary); }

        .btn-add-task {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
        }

        .loading, .empty-state {
            text-align: center;
            padding: 60px;
            color: #6C757D;
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
                    <div class="page-header">
                        <h1>Personal & Ward Tasks</h1>
                        <button class="btn-add-task">
                            <i class="fas fa-plus"></i> Add Task
                        </button>
                    </div>

                    <div class="task-list" id="taskList">
                        <div class="loading">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p>Loading your tasks...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function loadTasks() {
            try {
                const response = await fetch('api/dashboard.php');
                const result = await response.json();

                if (result.success) {
                    const tasks = result.data.today_tasks;
                    const container = document.getElementById('taskList');
                    
                    if (tasks && tasks.length > 0) {
                        container.innerHTML = tasks.map(t => `
                            <div class="task-item ${t.status === 'Completed' ? 'completed' : ''}">
                                <div class="task-status">
                                    ${t.status === 'Completed' ? '<i class="fas fa-check"></i>' : ''}
                                </div>
                                <div class="task-content">
                                    <h4>${t.task_title}</h4>
                                    <div class="task-meta">
                                        <span><i class="fas fa-user-circle"></i> ${t.patient_name || 'Ward General'}</span>
                                        <span><i class="far fa-clock"></i> Due: ${new Date(t.due_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                                    </div>
                                </div>
                                <span class="priority-badge priority-${t.priority.toLowerCase()}">${t.priority}</span>
                                <div class="task-actions">
                                    <button class="btn-sm" style="background: transparent; border: none; color: #ADB5BD; cursor: pointer;">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = `
                            <div class="empty-state">
                                <i class="fas fa-tasks" style="font-size: 48px; margin-bottom: 20px; opacity: 0.3;"></i>
                                <h3>No Tasks Scheduled</h3>
                                <p>You have no pending tasks for your shift. Great job!</p>
                            </div>
                        `;
                    }
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        loadTasks();
    </script>
</body>
</html>
