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
    <title>Ward Management - GM HMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        :root { --primary: #4A90E2; --primary-dark: #357ABD; --success: #28A745; --warning: #FFC107; --danger: #DC3545; --info: #17A2B8; --light: #F8F9FA; --dark: #343A40; }
        body { background: #F5F7FA; min-height: 100vh; display: flex; }
        .main-layout { display: flex; width: 100%; }
        .content-wrapper { flex: 1; display: flex; flex-direction: column; }
        .main-content { flex: 1; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-header h1 { font-size: 24px; color: var(--dark); font-weight: 700; }
        .ward-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .room-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .room-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #F1F3F5; padding-bottom: 10px; }
        .room-title { font-weight: 700; color: var(--primary-dark); }
        .bed-list { list-style: none; }
        .bed-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #F8F9FA; font-size: 14px; }
        .bed-status { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 8px; }
        .status-occupied { background: var(--danger); }
        .status-vacant { background: var(--success); }
        .status-cleaning { background: var(--warning); }
    </style>
</head>
<body>
    <div class="main-layout">
        <?php include 'includes/nurse_sidebar.php'; ?>
        <div class="content-wrapper">
            <?php include 'includes/nurse_navbar.php'; ?>
            <div class="main-content">
                <div class="container">
                    <div class="page-header">
                        <h1>Ward Overview & Management</h1>
                    </div>
                    <div class="ward-grid">
                        <!-- Simplified Placeholder Ward Overview -->
                        <?php for($i=1; $i<=6; $i++): ?>
                        <div class="room-card">
                            <div class="room-header">
                                <span class="room-title">Room <?php echo 100 + $i; ?></span>
                                <span class="badge" style="background: #E3F2FD; color: #1976D2; font-size: 11px;">ICU / General</span>
                            </div>
                            <ul class="bed-list">
                                <li class="bed-item">
                                    <span>Bed A</span>
                                    <span style="color: var(--danger); font-weight: 600;"><span class="bed-status status-occupied"></span>Occupied</span>
                                </li>
                                <li class="bed-item">
                                    <span>Bed B</span>
                                    <span style="color: var(--success); font-weight: 600;"><span class="bed-status status-vacant"></span>Vacant</span>
                                </li>
                                <li class="bed-item">
                                    <span>Bed C</span>
                                    <span style="color: var(--warning); font-weight: 600;"><span class="bed-status status-cleaning"></span>Cleaning</span>
                                </li>
                            </ul>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
