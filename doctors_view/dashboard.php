<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Doctor', 'admin', 'Admin'])) {
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
    <title>Doctor Dashboard - GM HMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/doctor_dashboard.css?v=<?= time() ?>">
</head>
<body>
    <div class="doctor-layout">
        <!-- Sidebar -->
        <?php include 'includes/doctor_sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="doctor-main-content">
            <!-- Top Navbar -->
            <?php include 'includes/doctor_navbar.php'; ?>
            
            <!-- Dashboard Content -->
            <div class="doctor-content">
                <!-- Premium Welcome Banner -->
                <div class="welcome-banner fade-in-up">
                    <h1 class="welcome-title">
                        Good <span id="greeting-time">Morning</span>, <span id="doctor-greeting-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>! 👋
                    </h1>
                    <p class="welcome-subtitle">
                        Today is <span id="current-date" style="font-weight: 600;"></span>. You have <span id="today-appointments-count" style="font-weight: 700; background: rgba(255,255,255,0.2); padding: 4px 10px; border-radius: 8px;">0</span> appointments scheduled today.
                    </p>
                    <i class="fas fa-stethoscope welcome-icon-bg"></i>
                </div>
                
                <!-- Bento KPI Grid -->
                <div class="bento-grid">
                    
                    <!-- Today's Appointments -->
                    <div class="bento-card gm-kpi-card col-span-3 fade-in-up delay-1">
                        <div>
                            <div class="gm-kpi-icon-wrap icon-teal">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="gm-kpi-value" id="kpi-appointments">0</div>
                            <div class="gm-kpi-label">Today's Appointments</div>
                        </div>
                        <i class="fas fa-users bg-icon"></i>
                    </div>
                    
                    <!-- Waiting Patients -->
                    <div class="bento-card gm-kpi-card col-span-3 fade-in-up delay-1">
                        <div>
                            <div class="gm-kpi-icon-wrap icon-orange">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="gm-kpi-value" id="kpi-waiting">0</div>
                            <div class="gm-kpi-label">Waiting Patients</div>
                        </div>
                        <i class="fas fa-hourglass-half bg-icon"></i>
                    </div>
                    
                    <!-- Completed Consultations -->
                    <div class="bento-card gm-kpi-card col-span-3 fade-in-up delay-2">
                        <div>
                            <div class="gm-kpi-icon-wrap icon-blue">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="gm-kpi-value" id="kpi-completed">0</div>
                            <div class="gm-kpi-label">Completed Today</div>
                        </div>
                        <i class="fas fa-clipboard-check bg-icon"></i>
                    </div>
                    
                    <!-- Pending Lab Reports -->
                    <div class="bento-card gm-kpi-card col-span-3 fade-in-up delay-2">
                        <div>
                            <div class="gm-kpi-icon-wrap icon-red">
                                <i class="fas fa-flask"></i>
                            </div>
                            <div class="gm-kpi-value" id="kpi-pending-labs">0</div>
                            <div class="gm-kpi-label">Pending Labs</div>
                        </div>
                        <i class="fas fa-vial bg-icon"></i>
                    </div>
                    
                </div>
                
                <!-- Secondary Bento Row -->
                <div class="bento-grid">
                    
                    <!-- Performance Sync (Wider Card) -->
                    <div class="bento-card col-span-6 fade-in-up delay-3" style="border-left: 4px solid #1A237E;">
                        <h3 style="font-size: 1.1rem; font-weight: 700; color: #1A237E; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-stopwatch"></i> Performance Sync
                        </h3>
                        <div style="display: flex; align-items: baseline; gap: 0.5rem;">
                            <div style="font-size: 3rem; font-weight: 800; color: #111827;" id="avg-consultation-time">--</div>
                            <div style="font-size: 1rem; color: #6b7280; font-weight: 600;">min / patient</div>
                        </div>
                        <div style="margin-top: 1.5rem; height: 6px; background: rgba(26, 35, 126, 0.1); border-radius: 3px; overflow: hidden;">
                            <div style="width: 75%; height: 100%; background: #1A237E; border-radius: 3px;"></div>
                        </div>
                        <p style="font-size: 0.85rem; color: #6b7280; margin-top: 0.75rem; font-weight: 500;">75% efficiency vs target. Great job!</p>
                    </div>
                    
                    <!-- Emergency Alerts -->
                    <div class="bento-card col-span-3 fade-in-up delay-3" style="border-top: 4px solid #DC2626;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <h3 style="font-size: 1rem; font-weight: 700; color: #DC2626; display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                                <i class="fas fa-exclamation-circle"></i> Emergencies
                            </h3>
                            <span id="emergency-count" style="background: #DC2626; color: white; border-radius: 20px; padding: 2px 10px; font-size: 0.75rem; font-weight: 700;">0</span>
                        </div>
                        <div id="emergency-list" style="flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                            <i class="fas fa-shield-alt" style="font-size: 2.5rem; color: rgba(220, 38, 38, 0.15); margin-bottom: 0.5rem;"></i>
                            <span style="color: #6b7280; font-size: 0.85rem; font-weight: 500;">Clear</span>
                        </div>
                    </div>
                    
                    <!-- AI Risk Alerts -->
                    <div class="bento-card col-span-3 fade-in-up delay-3" style="border-top: 4px solid #D97706;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <h3 style="font-size: 1rem; font-weight: 700; color: #D97706; display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                                <i class="fas fa-brain"></i> AI Risks
                            </h3>
                            <span id="ai-risk-count" style="background: #D97706; color: white; border-radius: 20px; padding: 2px 10px; font-size: 0.75rem; font-weight: 700;">0</span>
                        </div>
                        <div id="ai-risk-list" style="flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                            <i class="fas fa-robot" style="font-size: 2.5rem; color: rgba(217, 119, 6, 0.15); margin-bottom: 0.5rem;"></i>
                            <span style="color: #6b7280; font-size: 0.85rem; font-weight: 500;">Monitoring...</span>
                        </div>
                    </div>
                    
                </div>
                
                <!-- Charts Row -->
                <div class="bento-grid">
                    <!-- Patient Flow Chart -->
                    <div class="bento-card col-span-6 fade-in-up delay-4">
                        <h3 style="font-size: 1.1rem; font-weight: 700; color: #111827; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-chart-line" style="color: #1f6b4a;"></i> Patient Flow (Last 7 Days)
                        </h3>
                        <div style="position: relative; height: 250px; width: 100%;">
                            <canvas id="patientFlowChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Consultation Duration Chart -->
                    <div class="bento-card col-span-6 fade-in-up delay-4">
                        <h3 style="font-size: 1.1rem; font-weight: 700; color: #111827; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-chart-bar" style="color: #1A237E;"></i> Consultation Duration Trends
                        </h3>
                        <div style="position: relative; height: 250px; width: 100%;">
                            <canvas id="consultationDurationChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Bottom Row: Upcoming Appointments + Recent Activity -->
                <div class="bento-grid">
                    <!-- Upcoming Appointments -->
                    <div class="bento-card col-span-6 fade-in-up delay-4">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <h3 style="font-size: 1.1rem; font-weight: 700; color: #111827; display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                                <i class="fas fa-calendar-alt" style="color: #1f6b4a;"></i> Upcoming Appointments
                            </h3>
                            <a href="mypatient.php" style="font-size: 0.85rem; color: #1f6b4a; font-weight: 600; text-decoration: none;">View All &rarr;</a>
                        </div>
                        <div id="upcoming-appointments-list" style="min-height: 150px; display: flex; align-items: center; justify-content: center;">
                            <div style="text-align: center; color: #9ca3af;">
                                <i class="fas fa-calendar-times" style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.5;"></i>
                                <p style="font-weight: 500; font-size: 0.9rem; margin: 0;">No upcoming appointments</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity Feed -->
                    <div class="bento-card col-span-6 fade-in-up delay-4">
                        <h3 style="font-size: 1.1rem; font-weight: 700; color: #111827; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-history" style="color: #D97706;"></i> Recent Activity
                        </h3>
                        <div id="recent-activity-list" style="min-height: 150px; display: flex; align-items: center; justify-content: center;">
                            <div style="text-align: center; color: #9ca3af;">
                                <i class="fas fa-inbox" style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.5;"></i>
                                <p style="font-weight: 500; font-size: 0.9rem; margin: 0;">No recent activity</p>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="assets/js/doctor_utils.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>
