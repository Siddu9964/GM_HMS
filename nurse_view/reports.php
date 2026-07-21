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
    <title>Reports - GM HMS</title>
    
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
        .report-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 20px; }
        .report-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); transition: 0.3s; }
        .report-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .report-icon { font-size: 32px; color: var(--primary); margin-bottom: 15px; }
        .report-card h3 { font-size: 18px; margin-bottom: 10px; color: var(--dark); }
        .report-card p { font-size: 14px; color: #6C757D; margin-bottom: 20px; line-height: 1.5; }
        .btn-generate { background: #E3F2FD; color: #1976D2; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; text-decoration: none; display: inline-block; }
        .btn-generate:hover { background: var(--primary); color: white; }
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
                        <h1>Nursing Reports & Analytics</h1>
                    </div>
                    <div class="report-grid">
                        <div class="report-card">
                            <div class="report-icon"><i class="fas fa-file-waveform"></i></div>
                            <h3>Daily Census Report</h3>
                            <p>Overview of patient admissions, discharges, and bed occupancy for the last 24 hours.</p>
                            <a href="#" class="btn-generate">Generate PDF</a>
                        </div>
                        <div class="report-card">
                            <div class="report-icon"><i class="fas fa-pills"></i></div>
                            <h3>Medication Variance Report</h3>
                            <p>Analysis of administered, missed, and refused medications within your shift period.</p>
                            <a href="#" class="btn-generate">View Details</a>
                        </div>
                        <div class="report-card">
                            <div class="report-icon"><i class="fas fa-user-check"></i></div>
                            <h3>Handover Summary</h3>
                            <p>Consolidated nursing notes and SOAP assessments marked for clinical handover.</p>
                            <a href="#" class="btn-generate">Prepare Handover</a>
                        </div>
                        <div class="report-card">
                            <div class="report-icon"><i class="fas fa-heart-circle-exclamation"></i></div>
                            <h3>Vitals Trend Analysis</h3>
                            <p>Visual trends for patients with abnormal vital readings recorded today.</p>
                            <a href="#" class="btn-generate">Open Analytics</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
