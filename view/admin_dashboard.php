<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /GM_HMS/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <title>Admin Dashboard - GM Hospital Management System</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    screens: {
                        'xs': '475px',
                        '3xl': '1920px'
                    },
                    colors: {
                        primary: '#1f6b4a',
                        'primary-dark': '#144d34',
                        'primary-light': '#2a8c62',
                    }
                }
            }
        }
    </script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Common Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin_common.css">
    
    <style>
        * {
            font-family: 'Inter', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        
        .sidebar {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .sidebar-item {
            transition: all 0.2s ease;
        }
        
        .sidebar-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(4px);
        }
        
        .sidebar-item.active {
            background: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%);
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.06);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @media (min-width: 640px) {
            .stat-card {
                padding: 24px;
            }
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -6px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
        }
        
        .gradient-bg-1 {
            background: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%);
        }
        
        .gradient-bg-2 {
            background: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%);
        }
        
        .gradient-bg-3 {
            background: linear-gradient(135deg, #2a8c62 0%, #1f6b4a 100%);
        }
        
        .gradient-bg-4 {
            background: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%);
        }
        
        .gradient-bg-5 {
            background: linear-gradient(135deg, #2a8c62 0%, #144d34 100%);
        }
        
        .gradient-bg-6 {
            background: linear-gradient(135deg, #1f6b4a 0%, #2a8c62 100%);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: #ffffff;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
        }
        
        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            min-width: 200px;
            z-index: 1000;
        }
        
        .dropdown-menu.show {
            display: block;
        }
        
        .activity-item {
            border-left: 3px solid #1f6b4a;
            padding-left: 14px;
            margin-bottom: 14px;
        }
        
        .chart-container {
            position: relative;
            height: 260px;
        }
        
        @media (min-width: 640px) {
            .chart-container {
                height: 300px;
            }
        }
        
        @media (min-width: 1024px) {
            .chart-container {
                height: 320px;
            }
        }

        /* Modal Pop & Fade Animations */
        @keyframes modalPopIn {
            0% { opacity: 0; transform: scale(0.96) translateY(8px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }
        .animate-modal-pop {
            animation: modalPopIn 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Interactive Clickable Card Hover Glow */
        .interactive-stat-card {
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .interactive-stat-card:hover {
            transform: translateY(-4px) scale(1.01);
            box-shadow: 0 16px 24px -6px rgba(0, 0, 0, 0.08), 0 6px 10px -4px rgba(0, 0, 0, 0.04);
            border-color: rgba(31, 107, 74, 0.35);
        }
        .interactive-stat-card:active {
            transform: translateY(-1px) scale(0.995);
        }
        .interactive-stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: transparent;
            transition: background 0.3s ease;
        }
        .interactive-stat-card:hover::after {
            background: linear-gradient(90deg, #10b981, #8b5cf6);
        }
        
        /* Modern subtle custom scrollbars for tables & lists */
        .overflow-x-auto::-webkit-scrollbar,
        .overflow-y-auto::-webkit-scrollbar {
            display: block !important;
            width: 5px !important;
            height: 5px !important;
        }
        
        .overflow-x-auto::-webkit-scrollbar-track,
        .overflow-y-auto::-webkit-scrollbar-track {
            background: rgba(241, 245, 249, 0.6);
            border-radius: 9999px;
        }
        
        .overflow-x-auto::-webkit-scrollbar-thumb,
        .overflow-y-auto::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.5) !important;
            border-radius: 9999px !important;
        }
        
        .overflow-x-auto::-webkit-scrollbar-thumb:hover,
        .overflow-y-auto::-webkit-scrollbar-thumb:hover {
            background: rgba(100, 116, 139, 0.75) !important;
        }
        
        /* Smooth touch scrolling */
        .overflow-x-auto, .overflow-y-auto {
            -webkit-overflow-scrolling: touch;
        }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 antialiased selection:bg-emerald-500 selection:text-white">
    
    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- Top Navbar -->
            <?php include 'includes/navbar.php'; ?>
            
            <!-- Dashboard Content Scroll Area -->
            <main class="flex-1 overflow-y-auto p-3 sm:p-4 md:p-6 lg:p-8 max-w-[1800px] w-full mx-auto">
                
                <!-- Welcome Section -->
                <div class="mb-6 sm:mb-8">
                    <div class="rounded-xl sm:rounded-2xl shadow-xs border border-amber-200/60 p-4 sm:p-5 md:p-6 transition-all" style="background: #f3efe6;">
                        <h1 class="text-xl sm:text-2xl md:text-3xl font-extrabold mb-1 tracking-tight" style="color: #1f6b4a;">
                            Welcome back, <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'])[0]); ?>! 👋
                        </h1>
                        <p class="text-xs sm:text-sm text-gray-600 font-medium">
                            Here's what's happening across your hospital's OPD and IPD operations today
                        </p>
                    </div>
                </div>
                
                <!-- Quick Stats (Dual OPD & IPD Stream - Interactive Drilldown Cards) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4 md:gap-6 mb-6 sm:mb-8">
                    <!-- Total Patients Card -->
                    <div onclick="openPatientDrilldown()" class="stat-card interactive-stat-card group" title="Click to view all patient details">
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-9 h-9 gradient-bg-1 rounded-lg flex items-center justify-center shadow-md">
                                <i class="fas fa-hospital-user text-white text-base"></i>
                            </div>
                            <span class="text-green-600 font-semibold text-xs bg-green-50 px-2 py-0.5 rounded-full border border-green-200 flex items-center gap-1">
                                Hospital Total <i class="fas fa-arrow-up-right-from-square text-[9px]"></i>
                            </span>
                        </div>
                        <h3 class="text-gray-600 mb-1 flex items-center justify-between" style="font-size: 14px; font-weight: 600;">
                            <span>Total Patients</span>
                            <span class="text-[10px] text-gray-400 group-hover:text-emerald-700 font-medium transition-colors">Details <i class="fas fa-chevron-right text-[8px]"></i></span>
                        </h3>
                        <p id="totalPatients" class="font-bold text-gray-800 text-2xl tracking-tight">Loading...</p>
                        <p id="patientsSecondary" class="text-gray-500 mt-1 text-xs">Today: ... | This Month: ...</p>
                    </div>
                    
                    <!-- Inpatients (IPD) Active Census Card -->
                    <div onclick="openIpdDrilldown()" class="stat-card interactive-stat-card group" title="Click to view admitted inpatients and bed census">
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center shadow-md" style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);">
                                <i class="fas fa-procedures text-white text-base"></i>
                            </div>
                            <span id="ipdOccupancyBadge" class="text-purple-600 font-semibold text-xs bg-purple-50 px-2 py-0.5 rounded-full border border-purple-200 flex items-center gap-1">
                                ... Inpatients <i class="fas fa-arrow-up-right-from-square text-[9px]"></i>
                            </span>
                        </div>
                        <h3 class="text-gray-600 mb-1 flex items-center justify-between" style="font-size: 14px; font-weight: 600;">
                            <span>Active Inpatients (IPD)</span>
                            <span class="text-[10px] text-gray-400 group-hover:text-purple-700 font-medium transition-colors">Census <i class="fas fa-chevron-right text-[8px]"></i></span>
                        </h3>
                        <p id="activeIpd" class="font-bold text-gray-800 text-2xl tracking-tight">Loading...</p>
                        <p id="ipdSecondary" class="text-gray-500 mt-1 text-xs">Admitted Today: ... | Discharges: ...</p>
                    </div>
                    
                    <!-- Outpatient (OPD) Visits Today Card -->
                    <div onclick="openOpdDrilldown()" class="stat-card interactive-stat-card group" title="Click to view OPD consultations and queue">
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-9 h-9 gradient-bg-3 rounded-lg flex items-center justify-center shadow-md">
                                <i class="fas fa-calendar-check text-white text-base"></i>
                            </div>
                            <span id="appointmentsPending" class="text-orange-600 font-semibold text-xs bg-orange-50 px-2 py-0.5 rounded-full border border-orange-200 flex items-center gap-1">
                                ... Pending <i class="fas fa-arrow-up-right-from-square text-[9px]"></i>
                            </span>
                        </div>
                        <h3 class="text-gray-600 mb-1 flex items-center justify-between" style="font-size: 14px; font-weight: 600;">
                            <span>OPD Visits Today</span>
                            <span class="text-[10px] text-gray-400 group-hover:text-emerald-700 font-medium transition-colors">Queue <i class="fas fa-chevron-right text-[8px]"></i></span>
                        </h3>
                        <p id="appointmentsToday" class="font-bold text-gray-800 text-2xl tracking-tight">Loading...</p>
                        <p id="appointmentsSecondary" class="text-gray-500 mt-1 text-xs">Approved: ... | Cancelled: ...</p>
                    </div>
                    
                    <!-- Consolidated Revenue Today Card -->
                    <div onclick="openRevenueDrilldown()" class="stat-card interactive-stat-card group" title="Click to view revenue breakdown and ledger">
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-9 h-9 gradient-bg-4 rounded-lg flex items-center justify-center shadow-md">
                                <i class="fas fa-hand-holding-usd text-white text-base"></i>
                            </div>
                            <span class="text-green-700 font-bold text-xs bg-green-50 px-2 py-0.5 rounded-full border border-green-200 flex items-center gap-1">
                                OPD + IPD <i class="fas fa-arrow-up-right-from-square text-[9px]"></i>
                            </span>
                        </div>
                        <h3 class="text-gray-600 mb-1 flex items-center justify-between" style="font-size: 14px; font-weight: 600;">
                            <span>Total Revenue Today</span>
                            <span class="text-[10px] text-gray-400 group-hover:text-emerald-700 font-medium transition-colors">Ledger <i class="fas fa-chevron-right text-[8px]"></i></span>
                        </h3>
                        <p id="revenueToday" class="font-bold text-emerald-700 text-2xl tracking-tight">Loading...</p>
                        <p id="revenueSecondary" class="text-gray-500 mt-1 text-xs">OPD: ₹... | IPD: ₹...</p>
                    </div>
                </div>
                
                <!-- Additional Stats Row (Compact Height) -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-6">
                    <!-- Bed Availability -->
                    <div class="stat-card !p-4">
                        <div class="flex items-center justify-between mb-2.5 pb-2 border-b border-gray-100">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-bed text-purple-600 text-sm"></i>
                                <h3 class="text-gray-800 font-bold text-xs sm:text-sm">Bed Availability</h3>
                            </div>
                        </div>
                        <div id="bedAvailabilityContainer" class="space-y-2 max-h-48 overflow-y-auto pr-1 text-xs">
                            <p class="text-xs text-gray-500">Loading bed availability...</p>
                        </div>
                    </div>
                    
                    <!-- Department Summary -->
                    <div class="stat-card !p-4">
                        <div class="flex items-center justify-between mb-2.5 pb-2 border-b border-gray-100">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-building text-blue-600 text-sm"></i>
                                <h3 class="text-gray-800 font-bold text-xs sm:text-sm">Active Departments</h3>
                            </div>
                        </div>
                        <div id="activeDepartmentsContainer" class="space-y-1.5 max-h-48 overflow-y-auto pr-1 text-xs">
                            <p class="text-xs text-gray-500">Loading departments...</p>
                        </div>
                    </div>
                    
                    <!-- Operations Schedule -->
                    <div class="stat-card !p-4">
                        <div class="flex items-center justify-between mb-2.5 pb-2 border-b border-gray-100">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-procedures text-rose-600 text-sm"></i>
                                <h3 class="text-gray-800 font-bold text-xs sm:text-sm">Surgeries Today</h3>
                            </div>
                        </div>
                        <div class="space-y-2 max-h-48 overflow-y-auto pr-1 text-xs" id="operationsContainer">
                            <p class="text-xs text-gray-500">Loading operations...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Charts and Activity (Advanced Patient Flow Stream) -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Patient Admissions & Consultations Chart (Advanced Multi-Range Analytics) -->
                    <div class="lg:col-span-2 stat-card">
                        <!-- Chart Top Header & Controls -->
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                            <div>
                                <h3 class="text-gray-800 font-bold text-sm md:text-base flex items-center gap-2">
                                    <i class="fas fa-chart-area text-emerald-600"></i>
                                    <span>Patient Flow: OPD Visits vs IPD Admissions</span>
                                </h3>
                                <p class="text-xs text-gray-500 mt-0.5">Dual-stream patient ingress, bed conversion & footfall comparison</p>
                            </div>
                            
                            <!-- Control Toolbar -->
                            <div class="flex flex-wrap items-center gap-2">
                                <!-- Time Range Pills -->
                                <div class="flex items-center bg-gray-100 p-1 rounded-xl text-xs font-semibold">
                                    <button id="flowBtn7d" onclick="switchPatientFlowRange(7, this)" class="flow-range-btn px-2.5 py-1 rounded-lg bg-white text-emerald-800 shadow-sm font-bold transition-all">7 Days</button>
                                    <button id="flowBtn14d" onclick="switchPatientFlowRange(14, this)" class="flow-range-btn px-2.5 py-1 rounded-lg text-gray-600 hover:text-gray-900 transition-all">14 Days</button>
                                    <button id="flowBtn30d" onclick="switchPatientFlowRange(30, this)" class="flow-range-btn px-2.5 py-1 rounded-lg text-gray-600 hover:text-gray-900 transition-all">30 Days</button>
                                </div>

                                <!-- Chart Style Switcher -->
                                <div class="flex items-center bg-gray-100 p-1 rounded-xl text-xs font-semibold">
                                    <button id="flowViewLine" onclick="togglePatientFlowChartType('line', this)" class="flow-view-btn px-2.5 py-1 rounded-lg bg-white text-gray-800 shadow-sm font-bold transition-all" title="Smooth Area Spline">
                                        <i class="fas fa-chart-line text-emerald-600"></i>
                                    </button>
                                    <button id="flowViewBar" onclick="togglePatientFlowChartType('bar', this)" class="flow-view-btn px-2.5 py-1 rounded-lg text-gray-500 hover:text-gray-900 transition-all" title="Grouped Bar Chart">
                                        <i class="fas fa-chart-bar text-purple-600"></i>
                                    </button>
                                </div>

                                <!-- Deep-Dive Drawer Button -->
                                <button onclick="openPatientFlowDrilldown()" class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-sm transition-all">
                                    <i class="fas fa-table-list text-xs"></i>
                                    <span>Deep Dive</span>
                                </button>
                            </div>
                        </div>

                        <!-- 4-Column Live Metric Flow Ribbon -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-4 p-3 bg-gray-50/80 rounded-xl border border-gray-100">
                            <!-- OPD Metric -->
                            <div class="bg-white p-2.5 rounded-lg border border-emerald-100 shadow-xs">
                                <div class="flex items-center justify-between text-[11px] font-semibold text-emerald-800">
                                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span> OPD Visits</span>
                                    <span id="flowBadgeOpdShare" class="text-[10px] bg-emerald-50 text-emerald-700 px-1.5 py-0.2 rounded font-bold">...%</span>
                                </div>
                                <p id="flowSummaryOpd" class="text-lg font-black text-gray-800 mt-0.5">0</p>
                                <p id="flowSummaryOpdAvg" class="text-[10px] text-gray-400 font-medium">Avg: 0 / day</p>
                            </div>

                            <!-- IPD Metric -->
                            <div class="bg-white p-2.5 rounded-lg border border-purple-100 shadow-xs">
                                <div class="flex items-center justify-between text-[11px] font-semibold text-purple-800">
                                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-purple-500 inline-block"></span> IPD Admissions</span>
                                    <span id="flowBadgeIpdShare" class="text-[10px] bg-purple-50 text-purple-700 px-1.5 py-0.2 rounded font-bold">...%</span>
                                </div>
                                <p id="flowSummaryIpd" class="text-lg font-black text-purple-900 mt-0.5">0</p>
                                <p id="flowSummaryIpdAvg" class="text-[10px] text-gray-400 font-medium">Avg: 0 / day</p>
                            </div>

                            <!-- Total Traffic Metric -->
                            <div class="bg-white p-2.5 rounded-lg border border-slate-200 shadow-xs">
                                <div class="flex items-center justify-between text-[11px] font-semibold text-slate-700">
                                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-slate-700 inline-block"></span> Net Footfall</span>
                                    <span class="text-[10px] bg-slate-100 text-slate-700 px-1.5 py-0.2 rounded font-bold">Total</span>
                                </div>
                                <p id="flowSummaryTotal" class="text-lg font-black text-slate-800 mt-0.5">0</p>
                                <p id="flowSummaryDailyAvg" class="text-[10px] text-gray-400 font-medium">Avg: 0 / day</p>
                            </div>

                            <!-- Conversion & Peak Metric -->
                            <div class="bg-white p-2.5 rounded-lg border border-amber-200 shadow-xs">
                                <div class="flex items-center justify-between text-[11px] font-semibold text-amber-800">
                                    <span>IPD Conversion</span>
                                    <span class="text-[10px] bg-amber-50 text-amber-700 px-1.5 py-0.2 rounded font-bold">Ratio</span>
                                </div>
                                <p id="flowSummaryConv" class="text-lg font-black text-amber-600 mt-0.5">0%</p>
                                <p id="flowSummaryPeak" class="text-[10px] text-gray-500 font-medium truncate" title="Peak Day">Peak: -</p>
                            </div>
                        </div>

                        <!-- Chart Canvas Container -->
                        <div class="chart-container relative">
                            <canvas id="admissionsChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="stat-card">
                        <h3 class="text-gray-700 font-semibold mb-4">Recent Activity</h3>
                        <div class="space-y-4 max-h-80 overflow-y-auto" id="recentActivityContainer">
                            <p class="text-sm text-gray-500">Loading recent activity...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Revenue & Department Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="stat-card">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-gray-700 font-semibold text-sm md:text-base">Hospital Revenue Stream: OPD vs IPD (Monthly)</h3>
                            <div class="flex items-center gap-3 text-xs font-semibold">
                                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-emerald-500 inline-block"></span> OPD</span>
                                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-purple-500 inline-block"></span> IPD</span>
                                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-sky-600 inline-block"></span> Total</span>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <h3 class="text-gray-700 font-semibold mb-4">Department Workload Distribution (OPD + IPD)</h3>
                        <div class="chart-container">
                            <canvas id="departmentChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-8">
                    <a href="patient_registration.php" class="stat-card text-center hover:shadow-lg block">
                        <i class="fas fa-user-plus text-3xl gradient-bg-1 bg-clip-text text-transparent mb-2"></i>
                        <p class="text-sm font-semibold text-gray-700">Add Patient</p>
                    </a>
                    <button class="stat-card text-center hover:shadow-lg">
                        <i class="fas fa-calendar-plus text-3xl gradient-bg-2 bg-clip-text text-transparent mb-2"></i>
                        <p class="text-sm font-semibold text-gray-700">New Appointment</p>
                    </button>
                    <button class="stat-card text-center hover:shadow-lg">
                        <i class="fas fa-file-invoice text-3xl gradient-bg-3 bg-clip-text text-transparent mb-2"></i>
                        <p class="text-sm font-semibold text-gray-700">Generate Bill</p>
                    </button>
                    <button class="stat-card text-center hover:shadow-lg">
                        <i class="fas fa-flask text-3xl gradient-bg-4 bg-clip-text text-transparent mb-2"></i>
                        <p class="text-sm font-semibold text-gray-700">Lab Test</p>
                    </button>
                    <button class="stat-card text-center hover:shadow-lg">
                        <i class="fas fa-pills text-3xl gradient-bg-5 bg-clip-text text-transparent mb-2"></i>
                        <p class="text-sm font-semibold text-gray-700">Pharmacy</p>
                    </button>
                    <button onclick="toggleReportsModal()" class="stat-card text-center hover:shadow-lg">
                        <i class="fas fa-chart-line text-3xl gradient-bg-6 bg-clip-text text-transparent mb-2"></i>
                        <p class="text-sm font-semibold text-gray-700">Reports</p>
                    </button>
                </div>
                
                <!-- Alerts & Notifications -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="stat-card">
                        <h3 class="text-gray-700 font-semibold mb-4">System Alerts</h3>
                        <div class="space-y-3" id="systemAlertsContainer">
                            <p class="text-sm text-gray-500">Loading system alerts...</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-gray-700 font-semibold flex items-center gap-2">
                                <i class="fas fa-calendar-check text-blue-500"></i>
                                <span>Upcoming Appointments (Today)</span>
                            </h3>
                            <span id="upcomingAppointmentsCount" class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200">0 Today</span>
                        </div>
                        <div class="space-y-3" id="upcomingAppointmentsContainer">
                            <p class="text-sm text-gray-500">Loading appointments...</p>
                        </div>
                    </div>
                </div>
                
            </main>
            
    </div>
    
    <!-- ============================================================ -->
    <!-- 1. TOTAL PATIENTS DRILLDOWN MODAL                            -->
    <!-- ============================================================ -->
    <div id="patientDrilldownModal" class="fixed inset-0 z-[100] hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-2 sm:p-4 text-center">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true" onclick="closePatientDrilldown()">
                <div class="absolute inset-0 bg-gray-900/80 backdrop-blur-md"></div>
            </div>
            <div class="relative z-10 inline-block bg-white rounded-xl sm:rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all my-auto align-middle w-full max-w-full sm:max-w-4xl lg:max-w-5xl xl:max-w-6xl border border-gray-100 animate-modal-pop max-h-[92vh] flex flex-col">
                <!-- Modal Header -->
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-100 flex justify-between items-center bg-gradient-to-r from-emerald-800 to-emerald-950 text-white shrink-0">
                    <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-white/15 flex items-center justify-center text-white text-base sm:text-lg shadow-inner shrink-0">
                            <i class="fas fa-hospital-user"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-base sm:text-lg md:text-xl font-bold truncate">Hospital Patient Registry</h3>
                            <p class="text-[10px] sm:text-xs text-emerald-200 line-clamp-1 sm:line-clamp-none">Unified roster of Outpatient (OPD) & Inpatient (IPD) registered patients</p>
                        </div>
                    </div>
                    <button onclick="closePatientDrilldown()" class="text-white/70 hover:text-white transition-colors p-1.5 sm:p-2 rounded-lg hover:bg-white/10 shrink-0">
                        <i class="fas fa-times text-lg sm:text-xl"></i>
                    </button>
                </div>
                
                <!-- Quick KPI Summary Strip -->
                <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3 p-3 sm:p-4 bg-emerald-50/50 border-b border-emerald-100 shrink-0">
                    <div class="bg-white p-2.5 sm:p-3 rounded-xl border border-emerald-100 shadow-xs">
                        <span class="text-[11px] sm:text-xs text-gray-500 font-medium block truncate">Total Registered</span>
                        <p id="patientModalTotal" class="text-lg sm:text-xl font-bold text-gray-800 mt-0.5">...</p>
                    </div>
                    <div class="bg-white p-2.5 sm:p-3 rounded-xl border border-emerald-100 shadow-xs">
                        <span class="text-[11px] sm:text-xs text-gray-500 font-medium block truncate">Registered Today</span>
                        <p id="patientModalToday" class="text-lg sm:text-xl font-bold text-emerald-600 mt-0.5">...</p>
                    </div>
                    <div class="bg-white p-2.5 sm:p-3 rounded-xl border border-emerald-100 shadow-xs">
                        <span class="text-[11px] sm:text-xs text-gray-500 font-medium block truncate">This Month</span>
                        <p id="patientModalMonth" class="text-lg sm:text-xl font-bold text-teal-600 mt-0.5">...</p>
                    </div>
                    <div class="bg-white p-2.5 sm:p-3 rounded-xl border border-emerald-100 shadow-xs">
                        <span class="text-[11px] sm:text-xs text-gray-500 font-medium block truncate">Active Inpatients</span>
                        <p id="patientModalIpd" class="text-lg sm:text-xl font-bold text-purple-600 mt-0.5">...</p>
                    </div>
                </div>

                <!-- Controls & Filters Bar -->
                <div class="p-3 sm:p-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2.5 sm:gap-3 bg-white shrink-0">
                    <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[200px]">
                        <div class="relative flex-1 min-w-[160px] max-w-xs">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs sm:text-sm"></i>
                            <input type="text" id="patientSearchInput" onkeyup="filterPatientTable()" placeholder="Search patient ID, name, phone..." 
                                   class="w-full pl-8 sm:pl-9 pr-3 py-1.5 sm:py-2 text-xs md:text-sm bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white transition-all">
                        </div>
                        <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-xl text-xs font-semibold overflow-x-auto max-w-full">
                            <button onclick="setPatientFilter('all', this)" class="patient-filter-btn px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-lg bg-white text-emerald-800 shadow-sm transition-all whitespace-nowrap">All</button>
                            <button onclick="setPatientFilter('ipd', this)" class="patient-filter-btn px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-lg text-gray-600 hover:text-gray-900 transition-all whitespace-nowrap">IPD Inpatients</button>
                            <button onclick="setPatientFilter('opd', this)" class="patient-filter-btn px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-lg text-gray-600 hover:text-gray-900 transition-all whitespace-nowrap">OPD Patients</button>
                            <button onclick="setPatientFilter('new_today', this)" class="patient-filter-btn px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-lg text-gray-600 hover:text-gray-900 transition-all whitespace-nowrap">New Today</button>
                        </div>
                    </div>
                    <a href="patient_registration.php" class="px-3 sm:px-4 py-1.5 sm:py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs md:text-sm font-semibold flex items-center gap-1.5 sm:gap-2 shadow-sm transition-all shrink-0">
                        <i class="fas fa-user-plus text-xs"></i> <span>Add Patient</span>
                    </a>
                </div>

                <!-- Table Container -->
                <div class="flex-1 overflow-x-auto overflow-y-auto p-2 sm:p-4 min-h-0">
                    <table class="w-full text-left text-xs sm:text-sm min-w-[620px] sm:min-w-full">
                        <thead class="text-xs uppercase bg-gray-50 text-gray-600 sticky top-0 border-b border-gray-200">
                            <tr>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3">Patient ID</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3">Patient Name</th>
                                <th class="px-2 sm:px-3 py-2.5 sm:py-3 text-center">Age / Sex</th>
                                <th class="px-2 sm:px-3 py-2.5 sm:py-3 text-center">Blood</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3">Contact / City</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3 text-center">Status</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3">Reg. Date</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody id="patientModalTableBody" class="divide-y divide-gray-100">
                            <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">Loading patients...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- 2. ACTIVE INPATIENTS (IPD) DRILLDOWN MODAL                    -->
    <!-- ============================================================ -->
    <div id="ipdDrilldownModal" class="fixed inset-0 z-[100] hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-2 sm:p-4 text-center">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true" onclick="closeIpdDrilldown()">
                <div class="absolute inset-0 bg-gray-900/80 backdrop-blur-md"></div>
            </div>
            <div class="relative z-10 inline-block bg-white rounded-xl sm:rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all my-auto align-middle w-full max-w-full sm:max-w-4xl lg:max-w-5xl xl:max-w-6xl border border-gray-100 animate-modal-pop max-h-[92vh] flex flex-col">
                <!-- Modal Header -->
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-100 flex justify-between items-center bg-gradient-to-r from-purple-800 to-indigo-950 text-white shrink-0">
                    <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-white/15 flex items-center justify-center text-white text-base sm:text-lg shadow-inner shrink-0">
                            <i class="fas fa-procedures"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-base sm:text-lg md:text-xl font-bold truncate">Inpatient Census & Bed Allocations (IPD)</h3>
                            <p class="text-[10px] sm:text-xs text-purple-200 line-clamp-1 sm:line-clamp-none">Active ward occupancy, admitting doctors, and financial tracking</p>
                        </div>
                    </div>
                    <button onclick="closeIpdDrilldown()" class="text-white/70 hover:text-white transition-colors p-1.5 sm:p-2 rounded-lg hover:bg-white/10 shrink-0">
                        <i class="fas fa-times text-lg sm:text-xl"></i>
                    </button>
                </div>
                
                <!-- Quick KPI Summary Strip -->
                <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3 p-3 sm:p-4 bg-purple-50/50 border-b border-purple-100 shrink-0">
                    <div class="bg-white p-2.5 sm:p-3 rounded-xl border border-purple-100 shadow-xs">
                        <span class="text-[11px] sm:text-xs text-gray-500 font-medium block truncate">Currently Admitted</span>
                        <p id="ipdModalActive" class="text-lg sm:text-xl font-bold text-purple-700 mt-0.5">...</p>
                    </div>
                    <div class="bg-white p-2.5 sm:p-3 rounded-xl border border-purple-100 shadow-xs">
                        <span class="text-[11px] sm:text-xs text-gray-500 font-medium block truncate">Today's Admissions</span>
                        <p id="ipdModalAdmittedToday" class="text-lg sm:text-xl font-bold text-emerald-600 mt-0.5">...</p>
                    </div>
                    <div class="bg-white p-2.5 sm:p-3 rounded-xl border border-purple-100 shadow-xs">
                        <span class="text-[11px] sm:text-xs text-gray-500 font-medium block truncate">Today's Discharges</span>
                        <p id="ipdModalDischargesToday" class="text-lg sm:text-xl font-bold text-blue-600 mt-0.5">...</p>
                    </div>
                    <div class="bg-white p-2.5 sm:p-3 rounded-xl border border-purple-100 shadow-xs">
                        <span class="text-[11px] sm:text-xs text-gray-500 font-medium block truncate">Bed Occupancy Rate</span>
                        <p id="ipdModalOccupancyRate" class="text-lg sm:text-xl font-bold text-orange-600 mt-0.5">...</p>
                    </div>
                </div>

                <!-- Controls & Filters Bar -->
                <div class="p-3 sm:p-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2.5 sm:gap-3 bg-white shrink-0">
                    <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[200px]">
                        <div class="relative flex-1 min-w-[160px] max-w-xs">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs sm:text-sm"></i>
                            <input type="text" id="ipdSearchInput" onkeyup="filterIpdTable()" placeholder="Search patient, bed, doctor, date..." 
                                   class="w-full pl-8 sm:pl-9 pr-3 py-1.5 sm:py-2 text-xs md:text-sm bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:bg-white transition-all">
                        </div>
                        <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-xl text-xs font-semibold overflow-x-auto max-w-full">
                            <button onclick="setIpdFilter('all', this)" class="ipd-filter-btn px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-lg bg-white text-purple-900 shadow-sm font-bold transition-all whitespace-nowrap">All</button>
                            <button onclick="setIpdFilter('admitted', this)" class="ipd-filter-btn px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-lg text-gray-600 hover:text-gray-900 transition-all whitespace-nowrap">Currently Admitted</button>
                            <button onclick="setIpdFilter('discharged', this)" class="ipd-filter-btn px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-lg text-gray-600 hover:text-gray-900 transition-all whitespace-nowrap">Discharged</button>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="ipd_billing.php" class="px-3 sm:px-4 py-1.5 sm:py-2 bg-purple-700 hover:bg-purple-800 text-white rounded-xl text-xs md:text-sm font-semibold flex items-center gap-1.5 sm:gap-2 shadow-sm transition-all">
                            <i class="fas fa-file-invoice-dollar text-xs"></i> <span>Open IPD Billing</span>
                        </a>
                    </div>
                </div>

                <!-- Table Container -->
                <div class="flex-1 overflow-x-auto overflow-y-auto p-2 sm:p-4 min-h-0">
                    <table class="w-full text-left text-xs sm:text-sm min-w-[650px] sm:min-w-full">
                        <thead class="text-xs uppercase bg-gray-50 text-gray-600 sticky top-0 border-b border-gray-200">
                            <tr>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3">Admission ID</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3">Patient Name</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3">Ward & Bed</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3">Admitting Doctor</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3">Admitted Date</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3 text-right">Balance Due</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3 text-center">Status</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody id="ipdModalTableBody" class="divide-y divide-gray-100">
                            <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">Loading IPD admissions...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- INPATIENT DOSSIER & BILLING DETAILS CARD MODAL               -->
    <!-- Theme strictly: #f3efe6 (Cream) & #1f6b4a (Forest Green)    -->
    <!-- ============================================================ -->
    <div id="ipdPatientCardModal" class="fixed inset-0 z-[110] hidden overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-2 sm:p-4 text-center">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true" onclick="closeIpdPatientCardModal()">
                <div class="absolute inset-0 bg-[#1f6b4a]/40 backdrop-blur-sm"></div>
            </div>
            <div class="relative z-10 inline-block bg-[#f3efe6] rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all my-auto align-middle w-full max-w-full sm:max-w-4xl lg:max-w-5xl xl:max-w-6xl border-2 border-[#1f6b4a] max-h-[92vh] flex flex-col">
                
                <!-- Modal Top Header Strip (Strictly #f3efe6 & #1f6b4a) -->
                <div class="bg-[#f3efe6] px-4 sm:px-6 py-4 border-b-2 border-[#1f6b4a] flex flex-wrap items-center justify-between gap-3 shrink-0">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <div id="ipdCardAvatar" class="w-12 h-12 rounded-full bg-[#1f6b4a] text-[#f3efe6] font-black text-xl flex items-center justify-center border-2 border-[#1f6b4a] shadow-sm shrink-0">
                            P
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 id="ipdCardPatientName" class="text-base sm:text-lg font-black text-[#1f6b4a] truncate">Patient Details</h3>
                                <span id="ipdCardStatusBadge" class="px-2.5 py-0.5 text-[11px] font-bold rounded-full bg-[#1f6b4a] text-white border border-[#1f6b4a]">Active</span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-[#1f6b4a] font-semibold flex-wrap mt-1">
                                <span class="font-mono bg-white border border-[#1f6b4a] px-2 py-0.5 rounded text-[11px] font-bold text-[#1f6b4a]" id="ipdCardPid">PID</span>
                                <span>•</span>
                                <span class="font-mono bg-white border border-[#1f6b4a] px-2 py-0.5 rounded text-[11px] font-bold text-[#1f6b4a]" id="ipdCardAdmId">ADM</span>
                                <span>•</span>
                                <span id="ipdCardAgeGender" class="bg-white border border-[#1f6b4a] px-2 py-0.5 rounded text-[11px] font-semibold text-[#1f6b4a]">Age/Sex</span>
                                <span>•</span>
                                <span id="ipdCardBloodGroup" class="bg-white border border-[#1f6b4a] px-2 py-0.5 rounded text-[11px] font-semibold text-[#1f6b4a]">Blood Group</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex flex-col text-right text-xs">
                            <span class="font-black text-[#1f6b4a]" id="ipdCardBedWard">Bed &amp; Ward</span>
                            <span class="text-[#1f6b4a]/80 font-semibold text-[11px]" id="ipdCardDoctor">Attending Doctor</span>
                        </div>
                        <button onclick="closeIpdPatientCardModal()" class="w-8 h-8 rounded-lg bg-white hover:bg-[#1f6b4a] text-[#1f6b4a] hover:text-[#f3efe6] border border-[#1f6b4a] flex items-center justify-center transition-colors shadow-xs cursor-pointer" title="Close Modal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Financial & Stay KPI Strip (Strictly #f3efe6 & #1f6b4a) -->
                <div class="bg-[#f3efe6] px-4 sm:px-6 py-3 border-b-2 border-[#1f6b4a]/30 grid grid-cols-2 sm:grid-cols-5 gap-2 sm:gap-3 shrink-0">
                    <div class="bg-white border-2 border-[#1f6b4a] rounded-xl p-2.5 text-center">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-[#1f6b4a]">Grand Total</div>
                        <div class="text-base sm:text-lg font-black text-[#1f6b4a]" id="ipdCardGrandTotal">₹0.00</div>
                    </div>
                    <div class="bg-white border-2 border-[#1f6b4a] rounded-xl p-2.5 text-center">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-[#1f6b4a]">Amount Paid</div>
                        <div class="text-base sm:text-lg font-black text-[#1f6b4a]" id="ipdCardAmountPaid">₹0.00</div>
                    </div>
                    <div class="bg-white border-2 border-[#1f6b4a] rounded-xl p-2.5 text-center">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-[#1f6b4a]">Balance Due</div>
                        <div class="text-base sm:text-lg font-black text-[#1f6b4a]" id="ipdCardBalanceDue">₹0.00</div>
                    </div>
                    <div class="bg-white border-2 border-[#1f6b4a] rounded-xl p-2.5 text-center">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-[#1f6b4a]">Length of Stay</div>
                        <div class="text-base sm:text-lg font-black text-[#1f6b4a]" id="ipdCardStayDays">0 Days</div>
                    </div>
                    <div class="col-span-2 sm:col-span-1 bg-white border-2 border-[#1f6b4a] rounded-xl p-2.5 text-center">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-[#1f6b4a]">Payment Status</div>
                        <div class="text-xs sm:text-sm font-black text-[#1f6b4a] mt-1" id="ipdCardPaymentStatus">Pending</div>
                    </div>
                </div>

                <!-- Dossier Navigation Tabs (Strictly #f3efe6 & #1f6b4a) -->
                <div class="bg-[#f3efe6] px-4 sm:px-6 py-2.5 border-b-2 border-[#1f6b4a] flex gap-2 overflow-x-auto shrink-0">
                    <button id="ipdTabBtnBill" onclick="switchIpdCardTab('bill')" class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all bg-[#1f6b4a] text-[#f3efe6] border border-[#1f6b4a] shadow-xs cursor-pointer">
                        <i class="fas fa-file-invoice-dollar mr-1"></i> Billing Master &amp; Breakdown
                    </button>
                    <button id="ipdTabBtnItems" onclick="switchIpdCardTab('items')" class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all bg-[#f3efe6] text-[#1f6b4a] hover:bg-[#1f6b4a] hover:text-[#f3efe6] border border-[#1f6b4a] cursor-pointer">
                        <i class="fas fa-list-check mr-1"></i> Itemized Charges (<span id="ipdCardItemsCount">0</span>)
                    </button>
                    <button id="ipdTabBtnClinical" onclick="switchIpdCardTab('clinical')" class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all bg-[#f3efe6] text-[#1f6b4a] hover:bg-[#1f6b4a] hover:text-[#f3efe6] border border-[#1f6b4a] cursor-pointer">
                        <i class="fas fa-notes-medical mr-1"></i> Clinical Records (<span id="ipdCardClinicalCount">0</span>)
                    </button>
                </div>

                <!-- Modal Body Content (Strictly #f3efe6 canvas) -->
                <div class="p-4 sm:p-6 overflow-y-auto flex-1 bg-[#f3efe6]" id="ipdCardModalBody">
                    <!-- Loading placeholder -->
                    <div id="ipdCardLoading" class="py-16 text-center text-[#1f6b4a]">
                        <i class="fas fa-spinner fa-spin text-3xl mb-3"></i>
                        <p class="font-bold text-sm">Loading patient clinical and billing dossier...</p>
                    </div>

                    <!-- TAB 1: Billing Master & Department Breakdowns -->
                    <div id="ipdTabPaneBill" class="hidden space-y-5">
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3" id="ipdCategoriesGrid">
                            <!-- Populated dynamically -->
                        </div>

                        <!-- Insurance & Billing Master Details -->
                        <div class="bg-white border-2 border-[#1f6b4a] rounded-xl p-4 shadow-sm">
                            <h4 class="text-xs font-black uppercase tracking-wider text-[#1f6b4a] mb-3 flex items-center gap-1.5">
                                <i class="fas fa-shield-alt"></i> Coverage &amp; Billing Account Details
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                                <div class="bg-[#f3efe6] border border-[#1f6b4a] p-2.5 rounded-lg">
                                    <span class="text-[#1f6b4a]/70 block text-[10px] font-bold">BILL ID</span>
                                    <span class="font-mono font-bold text-[#1f6b4a]" id="ipdCardBillNo">-</span>
                                </div>
                                <div class="bg-[#f3efe6] border border-[#1f6b4a] p-2.5 rounded-lg">
                                    <span class="text-[#1f6b4a]/70 block text-[10px] font-bold">BILL TYPE</span>
                                    <span class="font-bold text-[#1f6b4a]" id="ipdCardBillType">SELF</span>
                                </div>
                                <div class="bg-[#f3efe6] border border-[#1f6b4a] p-2.5 rounded-lg">
                                    <span class="text-[#1f6b4a]/70 block text-[10px] font-bold">SPONSOR / TPA</span>
                                    <span class="font-bold text-[#1f6b4a]" id="ipdCardSponsor">None</span>
                                </div>
                                <div class="bg-[#f3efe6] border border-[#1f6b4a] p-2.5 rounded-lg">
                                    <span class="text-[#1f6b4a]/70 block text-[10px] font-bold">POLICY / APPROVAL NO</span>
                                    <span class="font-mono font-bold text-[#1f6b4a]" id="ipdCardPolicyNo">None</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: Itemized Charges Table -->
                    <div id="ipdTabPaneItems" class="hidden">
                        <div class="border-2 border-[#1f6b4a] rounded-xl overflow-hidden shadow-sm bg-white">
                            <div class="max-h-[50vh] overflow-y-auto">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead class="bg-[#1f6b4a] text-[#f3efe6] sticky top-0 font-bold">
                                        <tr>
                                            <th class="px-3.5 py-2.5 border-b border-[#1f6b4a]">Date</th>
                                            <th class="px-3.5 py-2.5 border-b border-[#1f6b4a]">Type</th>
                                            <th class="px-3.5 py-2.5 border-b border-[#1f6b4a]">Charge Description</th>
                                            <th class="px-3.5 py-2.5 text-right border-b border-[#1f6b4a]">Amount (₹)</th>
                                            <th class="px-3.5 py-2.5 text-center border-b border-[#1f6b4a]">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ipdItemsTableBody" class="divide-y divide-[#1f6b4a]/20">
                                        <!-- Populated dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: Clinical Records & Diagnostic Tests -->
                    <div id="ipdTabPaneClinical" class="hidden space-y-4">
                        <div id="ipdClinicalList" class="space-y-4">
                            <!-- Populated dynamically -->
                        </div>
                    </div>
                </div>

                <!-- Modal Footer (Strictly #f3efe6 & #1f6b4a) -->
                <div class="bg-[#f3efe6] px-4 sm:px-6 py-3 border-t-2 border-[#1f6b4a] flex items-center justify-end gap-3 shrink-0">
                    <button onclick="closeIpdPatientCardModal()" class="px-5 py-2 bg-[#1f6b4a] hover:bg-[#18543a] text-[#f3efe6] border border-[#1f6b4a] rounded-xl text-xs font-bold transition-all cursor-pointer shadow-sm">
                        Close
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- 3. OPD VISITS & QUEUE DRILLDOWN MODAL                         -->
    <!-- ============================================================ -->
    <div id="opdDrilldownModal" class="fixed inset-0 z-[100] hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-2 sm:p-4 text-center">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true" onclick="closeOpdDrilldown()">
                <div class="absolute inset-0 bg-gray-900/80 backdrop-blur-md"></div>
            </div>
            <div class="relative z-10 inline-block bg-white rounded-xl sm:rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all my-auto align-middle w-full max-w-full sm:max-w-4xl lg:max-w-5xl xl:max-w-6xl border border-gray-100 animate-modal-pop max-h-[92vh] flex flex-col">
                <!-- Modal Header -->
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-100 flex justify-between items-center bg-gradient-to-r from-teal-800 to-emerald-950 text-white shrink-0">
                    <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-white/15 flex items-center justify-center text-white text-base sm:text-lg shadow-inner shrink-0">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-base sm:text-lg md:text-xl font-bold truncate">OPD Appointments & Queue</h3>
                            <p class="text-[10px] sm:text-xs text-teal-200 line-clamp-1 sm:line-clamp-none">Outpatient clinical visits, consultation queue, and doctor roster</p>
                        </div>
                    </div>
                    <button onclick="closeOpdDrilldown()" class="text-white/70 hover:text-white transition-colors p-1.5 sm:p-2 rounded-lg hover:bg-white/10 shrink-0">
                        <i class="fas fa-times text-lg sm:text-xl"></i>
                    </button>
                </div>
                
                <!-- Quick KPI Summary Strip -->
                <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3 p-3 sm:p-4 bg-teal-50/50 border-b border-teal-100 shrink-0">
                    <div class="bg-white p-2.5 sm:p-3 rounded-xl border border-teal-100 shadow-xs">
                        <div class="flex justify-between items-center">
                            <span class="text-[11px] sm:text-xs text-gray-500 font-medium truncate">Filtered Visits</span>
                            <span id="opdDateRangeBadge" class="text-[10px] font-bold text-teal-800 bg-teal-50 px-1.5 py-0.2 rounded">Today</span>
                        </div>
                        <p id="opdModalToday" class="text-lg sm:text-xl font-bold text-teal-800 mt-1">0 Visits</p>
                    </div>
                    <div class="bg-white p-2.5 sm:p-3 rounded-xl border border-teal-100 shadow-xs">
                        <span class="text-[11px] sm:text-xs text-gray-500 font-medium block truncate">Approved / Active</span>
                        <p id="opdModalApproved" class="text-lg sm:text-xl font-bold text-emerald-600 mt-1">0</p>
                    </div>
                    <div class="bg-white p-2.5 sm:p-3 rounded-xl border border-teal-100 shadow-xs">
                        <span class="text-[11px] sm:text-xs text-gray-500 font-medium block truncate">Pending Queue</span>
                        <p id="opdModalPending" class="text-lg sm:text-xl font-bold text-orange-500 mt-1">0</p>
                    </div>
                    <div class="bg-white p-2.5 sm:p-3 rounded-xl border border-teal-100 shadow-xs">
                        <span class="text-[11px] sm:text-xs text-gray-500 font-medium block truncate">Cancelled</span>
                        <p id="opdModalCancelled" class="text-lg sm:text-xl font-bold text-red-500 mt-1">0</p>
                    </div>
                </div>

                <!-- Controls & Filters Bar -->
                <div class="p-3 sm:p-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2.5 sm:gap-3 bg-white shrink-0">
                    <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[200px]">
                        <div class="relative min-w-[150px] max-w-xs flex-1">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs sm:text-sm"></i>
                            <input type="text" id="opdSearchInput" onkeyup="filterOpdTable()" placeholder="Search patient, doctor, token..." 
                                   class="w-full pl-8 sm:pl-9 pr-3 py-1.5 text-xs bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition-all">
                        </div>

                        <!-- Date Preset & Date Picker -->
                        <div class="flex items-center gap-1 bg-gray-50 border border-gray-200 px-2 py-1 rounded-xl">
                            <i class="fas fa-calendar-day text-gray-400 text-xs"></i>
                            <input type="date" id="opdDateInput" onchange="onOpdDateSelect(this.value)" 
                                   class="bg-transparent text-xs text-gray-700 font-semibold focus:outline-none cursor-pointer">
                            <button onclick="clearOpdDateFilter()" class="text-[10px] text-gray-400 hover:text-red-500 ml-1 px-1 font-bold" title="Clear Date Filter">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <!-- Quick Date Scope Pills -->
                        <div class="flex items-center bg-gray-100 p-1 rounded-xl text-xs font-semibold">
                            <button id="opdScopeTodayBtn" onclick="setOpdDateScope('today', this)" class="opd-scope-btn px-2.5 py-1 rounded-lg bg-white text-teal-800 shadow-sm transition-all">Today</button>
                            <button id="opdScopeAllBtn" onclick="setOpdDateScope('all', this)" class="opd-scope-btn px-2.5 py-1 rounded-lg text-gray-600 hover:text-gray-900 transition-all">All Dates</button>
                        </div>

                        <!-- Status Filter Pills -->
                        <div class="flex items-center bg-gray-100 p-1 rounded-xl text-xs font-semibold overflow-x-auto max-w-full">
                            <button onclick="setOpdFilter('all', this)" class="opd-filter-btn px-2.5 py-1 rounded-lg bg-white text-teal-800 shadow-sm transition-all whitespace-nowrap">All</button>
                            <button onclick="setOpdFilter('Approved', this)" class="opd-filter-btn px-2.5 py-1 rounded-lg text-gray-600 hover:text-gray-900 transition-all whitespace-nowrap">Approved</button>
                            <button onclick="setOpdFilter('Pending', this)" class="opd-filter-btn px-2.5 py-1 rounded-lg text-gray-600 hover:text-gray-900 transition-all whitespace-nowrap">Pending</button>
                            <button onclick="setOpdFilter('Cancelled', this)" class="opd-filter-btn px-2.5 py-1 rounded-lg text-gray-600 hover:text-gray-900 transition-all whitespace-nowrap">Cancelled</button>
                        </div>
                    </div>
                </div>

                <!-- Table Container -->
                <div class="flex-1 overflow-x-auto overflow-y-auto p-2 sm:p-4 min-h-0">
                    <table class="w-full text-left text-xs sm:text-sm min-w-[620px] sm:min-w-full">
                        <thead class="text-xs uppercase bg-gray-50 text-gray-600 sticky top-0 border-b border-gray-200">
                            <tr>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3 text-center">Queue / Token</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3">Patient Name</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3">Contact</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3">Doctor & Specialization</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3">Scheduled Time</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3 text-right">Fee (₹)</th>
                            </tr>
                        </thead>
                        <tbody id="opdModalTableBody" class="divide-y divide-gray-100">
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Loading OPD consultations...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- 4. TOTAL REVENUE DRILLDOWN MODAL                              -->
    <!-- ============================================================ -->
    <div id="revenueDrilldownModal" class="fixed inset-0 z-[100] hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-2 sm:p-4 text-center">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true" onclick="closeRevenueDrilldown()">
                <div class="absolute inset-0 bg-gray-900/80 backdrop-blur-md"></div>
            </div>
            <div class="relative z-10 inline-block bg-white rounded-xl sm:rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all my-auto align-middle w-full max-w-full sm:max-w-4xl lg:max-w-5xl xl:max-w-6xl border border-gray-100 animate-modal-pop max-h-[92vh] flex flex-col">
                <!-- Modal Header -->
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-100 flex justify-between items-center bg-gradient-to-r from-emerald-800 via-teal-900 to-indigo-950 text-white shrink-0">
                    <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-white/15 flex items-center justify-center text-white text-base sm:text-lg shadow-inner shrink-0">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-base sm:text-lg md:text-xl font-bold truncate">Consolidated Hospital Financials & Revenue Ledger</h3>
                            <p class="text-[10px] sm:text-xs text-emerald-200 line-clamp-1 sm:line-clamp-none">Real-time combined collections from Outpatient (OPD) & Inpatient (IPD) billing</p>
                        </div>
                    </div>
                    <button onclick="closeRevenueDrilldown()" class="text-white/70 hover:text-white transition-colors p-1.5 sm:p-2 rounded-lg hover:bg-white/10 shrink-0">
                        <i class="fas fa-times text-lg sm:text-xl"></i>
                    </button>
                </div>
                
                <!-- Quick Financial Matrix Strip (Dynamically Filtered by Date & Stream) -->
                <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3 p-3 sm:p-4 bg-emerald-50/50 border-b border-emerald-100 shrink-0">
                    <!-- Total Revenue Card -->
                    <div class="bg-white p-2.5 sm:p-3.5 rounded-xl border border-emerald-100 shadow-xs">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-[11px] sm:text-xs text-gray-500 font-semibold uppercase tracking-wider truncate">Filtered Revenue</span>
                            <span id="revFilteredDateBadge" class="text-[9px] sm:text-[10px] font-bold text-emerald-800 bg-emerald-50 px-1.5 py-0.5 rounded-full border border-emerald-200">All Time</span>
                        </div>
                        <p id="revModalTotalFiltered" class="text-xl sm:text-2xl font-black text-emerald-700 mt-0.5">₹0</p>
                        <p class="text-[10px] sm:text-[11px] text-gray-400 mt-0.5 font-medium"><span id="revModalTxnCount">0</span> transactions</p>
                    </div>

                    <!-- OPD Revenue Card -->
                    <div class="bg-white p-2.5 sm:p-3.5 rounded-xl border border-teal-100 shadow-xs">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-[11px] sm:text-xs text-teal-800 font-semibold uppercase tracking-wider flex items-center gap-1 truncate">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span> OPD Revenue
                            </span>
                            <span id="revModalOpdShare" class="text-[9px] sm:text-[10px] font-bold text-teal-700 bg-teal-50 px-1.5 py-0.5 rounded">0%</span>
                        </div>
                        <p id="revModalOpdTotal" class="text-xl sm:text-2xl font-black text-teal-700 mt-0.5">₹0</p>
                        <p class="text-[10px] sm:text-[11px] text-gray-400 mt-0.5 font-medium truncate">Consultations & fees</p>
                    </div>

                    <!-- IPD Revenue Card -->
                    <div class="bg-white p-2.5 sm:p-3.5 rounded-xl border border-purple-100 shadow-xs">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-[11px] sm:text-xs text-purple-800 font-semibold uppercase tracking-wider flex items-center gap-1 truncate">
                                <span class="w-2 h-2 rounded-full bg-purple-500 shrink-0"></span> IPD Revenue
                            </span>
                            <span id="revModalIpdShare" class="text-[9px] sm:text-[10px] font-bold text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded">0%</span>
                        </div>
                        <p id="revModalIpdTotal" class="text-xl sm:text-2xl font-black text-purple-900 mt-0.5">₹0</p>
                        <p class="text-[10px] sm:text-[11px] text-gray-400 mt-0.5 font-medium truncate">Inpatients & surgeries</p>
                    </div>

                    <!-- Payment Mode Distribution -->
                    <div class="bg-white p-2.5 sm:p-3.5 rounded-xl border border-gray-200 shadow-xs">
                        <span class="text-[11px] sm:text-xs text-gray-500 font-semibold uppercase tracking-wider mb-1 block truncate">Payment Modes</span>
                        <div class="grid grid-cols-2 gap-1 text-[10px] sm:text-[11px]">
                            <div class="bg-gray-50 px-1.5 py-0.5 rounded flex justify-between"><span>Cash:</span> <b id="pmCash">₹0</b></div>
                            <div class="bg-gray-50 px-1.5 py-0.5 rounded flex justify-between"><span>UPI:</span> <b id="pmUpi">₹0</b></div>
                            <div class="bg-gray-50 px-1.5 py-0.5 rounded flex justify-between"><span>Card:</span> <b id="pmCard">₹0</b></div>
                            <div class="bg-gray-50 px-1.5 py-0.5 rounded flex justify-between"><span>Insur:</span> <b id="pmInsurance">₹0</b></div>
                        </div>
                    </div>
                </div>

                <!-- Controls & Filters Bar -->
                <div class="p-3 sm:p-3.5 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2.5 sm:gap-3 bg-white shrink-0">
                    <!-- Left: Search & Date Filter -->
                    <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[200px]">
                        <!-- Search Box -->
                        <div class="relative min-w-[160px] max-w-xs flex-1">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input type="text" id="revenueSearchInput" onkeyup="filterRevenueTable()" placeholder="Search patient, bill ID, doctor..." 
                                   class="w-full pl-8 pr-3 py-1.5 text-xs bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white transition-all">
                        </div>

                        <!-- Date Picker & Clear -->
                        <div class="flex items-center gap-1 bg-gray-50 border border-gray-200 px-2 py-1 rounded-xl">
                            <i class="fas fa-calendar-alt text-gray-400 text-xs"></i>
                            <input type="date" id="revenueDateInput" onchange="onRevenueDateSelect(this.value)" 
                                   class="bg-transparent text-xs text-gray-700 font-semibold focus:outline-none cursor-pointer">
                            <button onclick="clearRevenueDateFilter()" class="text-[10px] text-gray-400 hover:text-red-500 ml-1 px-1 font-bold" title="Clear Date">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <!-- Stream Filter Pills -->
                        <div class="flex items-center bg-gray-100 p-1 rounded-xl text-xs font-semibold overflow-x-auto max-w-full">
                            <button onclick="setRevenueFilter('all', this)" class="revenue-filter-btn px-2.5 py-1 rounded-lg bg-white text-emerald-800 shadow-sm transition-all whitespace-nowrap">All Streams</button>
                            <button onclick="setRevenueFilter('OPD', this)" class="revenue-filter-btn px-2.5 py-1 rounded-lg text-gray-600 hover:text-gray-900 transition-all whitespace-nowrap">OPD Only</button>
                            <button onclick="setRevenueFilter('IPD', this)" class="revenue-filter-btn px-2.5 py-1 rounded-lg text-gray-600 hover:text-gray-900 transition-all whitespace-nowrap">IPD Only</button>
                        </div>
                    </div>

                    <!-- Right: View Mode Tabs -->
                    <div class="flex items-center bg-gray-100 p-1 rounded-xl text-xs font-semibold shrink-0">
                        <button id="revTabDaily" onclick="switchRevenueTab('daily', this)" class="rev-tab-btn px-2.5 sm:px-3 py-1 rounded-lg bg-white text-emerald-900 shadow-sm font-bold flex items-center gap-1.5 transition-all">
                            <i class="fas fa-calendar-day text-emerald-600 text-xs"></i>
                            <span class="hidden xs:inline">Day-by-Day Matrix</span>
                            <span class="xs:hidden">Daily</span>
                        </button>
                        <button id="revTabLedger" onclick="switchRevenueTab('ledger', this)" class="rev-tab-btn px-2.5 sm:px-3 py-1 rounded-lg text-gray-600 hover:text-gray-900 flex items-center gap-1.5 transition-all">
                            <i class="fas fa-list-ul text-xs"></i>
                            <span class="hidden xs:inline">Detailed Ledger</span>
                            <span class="xs:hidden">Ledger</span>
                        </button>
                    </div>
                </div>

                <!-- Tab 1: Day-by-Day OPD vs IPD Breakdown Table -->
                <div id="revenueDailyViewContainer" class="flex-1 overflow-x-auto overflow-y-auto p-2 sm:p-4 min-h-0">
                    <table class="w-full text-left text-xs sm:text-sm min-w-[580px] sm:min-w-full">
                        <thead class="text-xs uppercase bg-gray-50 text-gray-600 sticky top-0 border-b border-gray-200">
                            <tr>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3">Date</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3 text-right text-emerald-800">OPD Revenue (₹)</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3 text-right text-purple-900">IPD Revenue (₹)</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3 text-right text-gray-900">Total Day Revenue (₹)</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3 text-center">Transactions</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3 text-right">Quick Filter</th>
                            </tr>
                        </thead>
                        <tbody id="revenueDailyTableBody" class="divide-y divide-gray-100">
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Loading daily revenue breakdown...</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Tab 2: Itemized Invoices & Transactions Ledger Table -->
                <div id="revenueLedgerViewContainer" class="hidden flex-1 overflow-x-auto overflow-y-auto p-2 sm:p-4 min-h-0">
                    <table class="w-full text-left text-xs sm:text-sm min-w-[650px] sm:min-w-full">
                        <thead class="text-xs uppercase bg-gray-50 text-gray-600 sticky top-0 border-b border-gray-200">
                            <tr>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3">Invoice / Bill ID</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3">Date & Time</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3">Patient Details</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3 text-center">Stream</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3 text-center">Payment Mode</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3 text-right">Amount Paid (₹)</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3 text-right">Balance Due (₹)</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="revenueModalTableBody" class="divide-y divide-gray-100">
                            <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">Loading financial transactions...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- 5. PATIENT FLOW DAY-BY-DAY DEEP-DIVE MODAL                   -->
    <!-- ============================================================ -->
    <div id="patientFlowDrilldownModal" class="fixed inset-0 z-[100] hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-2 sm:p-4 text-center">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true" onclick="closePatientFlowDrilldown()">
                <div class="absolute inset-0 bg-gray-900/80 backdrop-blur-md"></div>
            </div>
            <div class="relative z-10 inline-block bg-white rounded-xl sm:rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all my-auto align-middle w-full max-w-full sm:max-w-4xl lg:max-w-5xl xl:max-w-6xl border border-gray-100 animate-modal-pop max-h-[92vh] flex flex-col">
                <!-- Modal Header -->
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-100 flex justify-between items-center bg-gradient-to-r from-slate-900 via-emerald-950 to-teal-900 text-white shrink-0">
                    <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-white/15 flex items-center justify-center text-white text-base sm:text-lg shadow-inner shrink-0">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-base sm:text-lg md:text-xl font-bold truncate">Patient Flow: Census & Ingress Roster</h3>
                            <p class="text-[10px] sm:text-xs text-emerald-200 line-clamp-1 sm:line-clamp-none">Daily breakdown of Outpatient consultations, Inpatient bed admissions, and footfall</p>
                        </div>
                    </div>
                    <button onclick="closePatientFlowDrilldown()" class="text-white/70 hover:text-white transition-colors p-1.5 sm:p-2 rounded-lg hover:bg-white/10 shrink-0">
                        <i class="fas fa-times text-lg sm:text-xl"></i>
                    </button>
                </div>
                
                <!-- Quick KPI Summary Strip -->
                <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3 p-3 sm:p-4 bg-gray-50 border-b border-gray-100 shrink-0">
                    <div class="bg-white p-2.5 sm:p-3 rounded-xl border border-gray-200 shadow-xs">
                        <span class="text-[11px] sm:text-xs text-gray-500 font-medium block truncate">Selected Period</span>
                        <p id="flowModalPeriod" class="text-lg sm:text-xl font-bold text-gray-800 mt-0.5">Last 7 Days</p>
                    </div>
                    <div class="bg-white p-2.5 sm:p-3 rounded-xl border border-emerald-100 shadow-xs">
                        <span class="text-[11px] sm:text-xs text-gray-500 font-medium block truncate">Total OPD Consultations</span>
                        <p id="flowModalOpd" class="text-lg sm:text-xl font-bold text-emerald-700 mt-0.5">0</p>
                    </div>
                    <div class="bg-white p-2.5 sm:p-3 rounded-xl border border-purple-100 shadow-xs">
                        <span class="text-[11px] sm:text-xs text-gray-500 font-medium block truncate">Total IPD Admissions</span>
                        <p id="flowModalIpd" class="text-lg sm:text-xl font-bold text-purple-700 mt-0.5">0</p>
                    </div>
                    <div class="bg-white p-2.5 sm:p-3 rounded-xl border border-amber-100 shadow-xs">
                        <span class="text-[11px] sm:text-xs text-gray-500 font-medium block truncate">IPD Admission Rate</span>
                        <p id="flowModalConv" class="text-lg sm:text-xl font-bold text-amber-600 mt-0.5">0%</p>
                    </div>
                </div>

                <!-- Controls & Filters Bar -->
                <div class="p-3 sm:p-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2.5 sm:gap-3 bg-white shrink-0">
                    <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[200px]">
                        <div class="relative flex-1 min-w-[160px] max-w-xs">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs sm:text-sm"></i>
                            <input type="text" id="flowModalSearchInput" oninput="filterFlowModalTable()" onkeyup="filterFlowModalTable()" placeholder="Filter by date, day..." 
                                   class="w-full pl-8 sm:pl-9 pr-3 py-1.5 sm:py-2 text-xs md:text-sm bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white transition-all">
                        </div>
                        <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-xl text-xs font-semibold overflow-x-auto max-w-full">
                            <button id="flowFilterBtnAll" onclick="setFlowModalFilter('all', this)" class="flow-modal-filter-btn px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-lg bg-white text-emerald-800 shadow-sm transition-all font-bold whitespace-nowrap">All Days</button>
                            <button id="flowFilterBtnActive" onclick="setFlowModalFilter('active', this)" class="flow-modal-filter-btn px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-lg text-gray-600 hover:text-gray-900 transition-all font-medium whitespace-nowrap">Active Flow (>0)</button>
                            <button id="flowFilterBtnIpd" onclick="setFlowModalFilter('ipd_only', this)" class="flow-modal-filter-btn px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-lg text-gray-600 hover:text-gray-900 transition-all font-medium whitespace-nowrap">Has IPD Admissions</button>
                        </div>
                    </div>
                </div>

                <!-- Table Container -->
                <div class="flex-1 overflow-x-auto overflow-y-auto p-2 sm:p-4 min-h-0">
                    <table class="w-full text-left text-xs sm:text-sm min-w-[620px] sm:min-w-full">
                        <thead class="text-xs uppercase bg-gray-50 text-gray-600 sticky top-0 border-b border-gray-200">
                            <tr>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3">Date & Day</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3 text-center">OPD Visits</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3 text-center">IPD Admissions</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3 text-center">Net Footfall</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3">Volume Distribution</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3 text-center">Peak Status</th>
                                <th class="px-3 sm:px-4 py-2.5 sm:py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody id="flowModalTableBody" class="divide-y divide-gray-100">
                            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">Loading day-by-day roster...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Reports & Analytics Modal (Dual OPD & IPD Stream) -->
    <div id="reportsModal" class="fixed inset-0 z-[100] hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-2 sm:p-4 text-center">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true" onclick="toggleReportsModal()">
                <div class="absolute inset-0 bg-gray-900 opacity-75 backdrop-blur-sm"></div>
            </div>
            <div class="relative z-10 inline-block bg-white rounded-xl sm:rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all my-auto align-middle w-full max-w-full sm:max-w-4xl border border-gray-100 max-h-[92vh] flex flex-col">
                <div class="relative bg-white flex-1 flex flex-col min-h-0">
                    <!-- Header -->
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50 shrink-0">
                        <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                            <div class="w-8 h-8 rounded-lg gradient-bg-1 flex items-center justify-center text-white text-sm shrink-0">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-base sm:text-lg md:text-xl font-bold text-gray-800 truncate">Hospital Performance & Analytics</h3>
                                <p class="text-[10px] sm:text-xs text-gray-500 line-clamp-1 sm:line-clamp-none">Summary of patient traffic, clinical workload, and collections</p>
                            </div>
                        </div>
                        <button onclick="toggleReportsModal()" class="text-gray-400 hover:text-gray-600 transition-colors p-1.5 sm:p-2 rounded-lg hover:bg-gray-100 shrink-0">
                            <i class="fas fa-times text-lg sm:text-xl"></i>
                        </button>
                    </div>
                    
                    <!-- Content -->
                    <div class="p-3 sm:p-6 flex-1 overflow-y-auto">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 mb-6">
                            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                                <div class="px-3 sm:px-4 py-2.5 sm:py-3 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                                    <h5 class="text-xs sm:text-sm font-semibold text-gray-700">Patient Census (Last 7 Days)</h5>
                                    <div class="flex items-center gap-2 text-[10px] sm:text-[11px]">
                                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded bg-emerald-500 inline-block"></span> OPD</span>
                                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded bg-purple-500 inline-block"></span> IPD</span>
                                    </div>
                                </div>
                                <div class="p-3 sm:p-4" id="report-daily-trend">
                                    <div class="h-44 sm:h-48 flex items-center justify-center text-gray-400 text-xs sm:text-sm">Loading daily trend...</div>
                                </div>
                            </div>
                            
                            <!-- Revenue Overview -->
                            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                                <div class="px-3 sm:px-4 py-2.5 sm:py-3 bg-gray-50 border-b border-gray-100">
                                    <h5 class="text-xs sm:text-sm font-semibold text-gray-700">Consolidated Revenue (Month to Date)</h5>
                                </div>
                                <div class="p-3 sm:p-4" id="report-revenue">
                                    <div class="h-44 sm:h-48 flex items-center justify-center text-gray-400 text-xs sm:text-sm">Loading revenue...</div>
                                </div>
                            </div>
                        </div>

                        <!-- Doctor Workload Table -->
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                            <div class="px-3 sm:px-4 py-2.5 sm:py-3 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                                <h5 class="text-xs sm:text-sm font-semibold text-gray-700">Doctor Clinical Workload (Today)</h5>
                                <span class="text-[10px] sm:text-xs text-gray-500 font-medium">OPD & IPD</span>
                            </div>
                            <div class="overflow-x-auto max-h-60 overflow-y-auto">
                                <table class="w-full text-xs sm:text-sm text-left text-gray-500 min-w-[500px] sm:min-w-full">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 sticky top-0">
                                        <tr>
                                            <th scope="col" class="px-4 sm:px-6 py-2.5 sm:py-3">Doctor</th>
                                            <th scope="col" class="px-4 sm:px-6 py-2.5 sm:py-3">Specialization</th>
                                            <th scope="col" class="px-3 sm:px-4 py-2.5 sm:py-3 text-center">OPD Visits</th>
                                            <th scope="col" class="px-3 sm:px-4 py-2.5 sm:py-3 text-center">IPD Inpatients</th>
                                            <th scope="col" class="px-4 sm:px-6 py-2.5 sm:py-3 text-right">Total Load</th>
                                        </tr>
                                    </thead>
                                    <tbody id="report-doctor-wise">
                                        <tr><td colspan="5" class="px-4 sm:px-6 py-4 text-center text-gray-400">Loading doctor workload...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div id="profileModal" class="fixed inset-0 z-[100] hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-2 sm:p-4 text-center">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true" onclick="toggleProfileModal()">
                <div class="absolute inset-0 bg-gray-900/80 backdrop-blur-md"></div>
            </div>
            <div class="relative z-10 inline-block bg-white rounded-xl sm:rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all my-auto align-middle w-full max-w-sm sm:max-w-md border border-gray-100 animate-modal-pop">
                <div class="relative">
                    <!-- Header Background -->
                    <div class="h-28 sm:h-32 gradient-bg-1"></div>
                    
                    <!-- Avatar -->
                    <div class="absolute top-14 sm:top-16 left-1/2 transform -translate-x-1/2">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name'] ?? 'User'); ?>&background=fff&color=1f6b4a&size=128" 
                             class="w-24 h-24 sm:w-28 sm:h-28 rounded-full border-4 border-white shadow-lg bg-white object-cover">
                    </div>
                    
                    <!-- Content -->
                    <div class="pt-16 sm:pt-20 pb-6 px-4 sm:px-6 text-center">
                        <h3 class="text-lg sm:text-xl font-bold text-gray-800"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Authenticated User'); ?></h3>
                        <p class="text-emerald-700 font-semibold text-xs sm:text-sm mb-4"><?php echo htmlspecialchars($_SESSION['designation'] ?? 'Staff'); ?></p>
                        
                        <div class="space-y-2.5 text-left text-xs sm:text-sm">
                            <div class="flex items-center p-2.5 bg-gray-50 rounded-xl">
                                <i class="fas fa-envelope w-7 text-gray-400 text-sm"></i>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[10px] text-gray-400 uppercase font-bold">Email Address</p>
                                    <p class="text-gray-700 truncate"><?php echo htmlspecialchars($_SESSION['email'] ?? 'Not Set'); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center p-2.5 bg-gray-50 rounded-xl">
                                <i class="fas fa-phone w-7 text-gray-400 text-sm"></i>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[10px] text-gray-400 uppercase font-bold">Mobile Number</p>
                                    <p class="text-gray-700 truncate"><?php echo htmlspecialchars($_SESSION['mobile_number'] ?? 'Not Set'); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center p-2.5 bg-gray-50 rounded-xl">
                                <i class="fas fa-id-badge w-7 text-gray-400 text-sm"></i>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[10px] text-gray-400 uppercase font-bold">User Identifier</p>
                                    <p class="text-gray-700 truncate"><?php echo htmlspecialchars($_SESSION['user_id'] ?? 'N/A'); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center p-2.5 bg-gray-50 rounded-xl">
                                <i class="fas fa-shield-alt w-7 text-gray-400 text-sm"></i>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[10px] text-gray-400 uppercase font-bold">Access Role</p>
                                    <p class="text-gray-700"><?php echo ucfirst(htmlspecialchars($_SESSION['role'] ?? 'user')); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center p-2.5 bg-gray-50 rounded-xl">
                                <i class="fas fa-check-circle w-7 text-emerald-500 text-sm"></i>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[10px] text-gray-400 uppercase font-bold">Account Status</p>
                                    <p class="text-gray-700">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-green-100 text-green-700 font-bold">
                                            <?php echo htmlspecialchars($_SESSION['status'] ?? 'Active'); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex space-x-2.5">
                            <button onclick="toggleProfileModal()" class="flex-1 py-2.5 px-3 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs sm:text-sm font-semibold rounded-xl transition-colors">
                                Close
                            </button>
                            <a href="../logout.php" class="flex-1 py-2.5 px-3 gradient-bg-2 hover:opacity-90 text-white text-xs sm:text-sm font-semibold rounded-xl text-center shadow-md transition-transform hover:scale-[1.02]">
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function toggleProfileModal() {
            const modal = document.getElementById('profileModal');
            if (modal && modal.classList.contains('hidden')) {
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            } else if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
        }
    </script>
    <script src="assets/js/admin_common.js"></script>
    <script>
        // Chart instances and Patient Flow State
        let admissionsChart, revenueChart, departmentChart;
        let patientFlowCurrentDays = 7;
        let patientFlowChartType = 'line';
        let patientFlowDailyData = [];
        let patientFlowSummaryData = null;
        let patientFlowFilter = 'all';

        // Helper to Build or Rebuild Admissions/Patient Flow Chart
        function createAdmissionsChart(labels, opdData, ipdData, totalData, chartType = 'line') {
            const admissionsCanvas = document.getElementById('admissionsChart');
            if (!admissionsCanvas) return;
            const admissionsCtx = admissionsCanvas.getContext('2d');

            if (admissionsChart) {
                admissionsChart.destroy();
            }

            const isLine = (chartType === 'line');

            admissionsChart = new Chart(admissionsCtx, {
                type: isLine ? 'line' : 'bar',
                data: {
                    labels: labels && labels.length ? labels : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [
                        {
                            label: 'OPD Consultations',
                            data: opdData && opdData.length ? opdData : [0, 0, 0, 0, 0, 0, 0],
                            borderColor: '#10b981',
                            backgroundColor: isLine ? 'rgba(16, 185, 129, 0.15)' : '#10b981',
                            tension: 0.35,
                            fill: isLine,
                            borderWidth: isLine ? 2.5 : 0,
                            borderRadius: isLine ? 0 : 6,
                            pointRadius: isLine ? 4 : 0,
                            pointHoverRadius: isLine ? 6 : 0
                        },
                        {
                            label: 'IPD Admissions',
                            data: ipdData && ipdData.length ? ipdData : [0, 0, 0, 0, 0, 0, 0],
                            borderColor: '#8b5cf6',
                            backgroundColor: isLine ? 'rgba(139, 92, 246, 0.15)' : '#8b5cf6',
                            tension: 0.35,
                            fill: isLine,
                            borderWidth: isLine ? 2.5 : 0,
                            borderRadius: isLine ? 0 : 6,
                            pointRadius: isLine ? 4 : 0,
                            pointHoverRadius: isLine ? 6 : 0
                        },
                        {
                            label: 'Total Net Footfall',
                            data: totalData && totalData.length ? totalData : [0, 0, 0, 0, 0, 0, 0],
                            borderColor: '#0f172a',
                            backgroundColor: isLine ? 'transparent' : '#0f172a',
                            borderDash: isLine ? [5, 5] : [],
                            borderWidth: isLine ? 2 : 0,
                            borderRadius: isLine ? 0 : 6,
                            pointRadius: isLine ? 3 : 0,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 8,
                                font: { size: 12, weight: '600' }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return ` ${context.dataset.label}: ${context.raw} patients`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            },
                            grid: {
                                color: 'rgba(226, 232, 240, 0.6)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // Initialize Charts
        function initCharts() {
            createAdmissionsChart([], [], [], [], 'line');
            
            // Revenue Chart (Monthly Multi-Stream OPD vs IPD vs Total)
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            revenueChart = new Chart(revenueCtx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [
                        {
                            label: 'OPD Revenue (₹)',
                            data: [0, 0, 0, 0, 0, 0],
                            backgroundColor: '#10b981',
                            borderRadius: 6
                        },
                        {
                            label: 'IPD Revenue (₹)',
                            data: [0, 0, 0, 0, 0, 0],
                            backgroundColor: '#8b5cf6',
                            borderRadius: 6
                        },
                        {
                            label: 'Total Hospital Revenue (₹)',
                            data: [0, 0, 0, 0, 0, 0],
                            backgroundColor: '#0284c7',
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 8,
                                font: { size: 12, weight: '600' }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const val = context.raw || 0;
                                    return ` ${context.dataset.label}: ₹${val.toLocaleString('en-IN')}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    if (value >= 100000) {
                                        return '₹' + (value / 100000).toFixed(1) + 'L';
                                    } else if (value >= 1000) {
                                        return '₹' + (value / 1000).toFixed(0) + 'k';
                                    }
                                    return '₹' + value;
                                }
                            },
                            grid: {
                                color: 'rgba(226, 232, 240, 0.6)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
            
            // Department Workload Doughnut Chart
            const departmentCtx = document.getElementById('departmentChart').getContext('2d');
            departmentChart = new Chart(departmentCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Loading...'],
                    datasets: [{
                        data: [1],
                        backgroundColor: ['#e2e8f0'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 8,
                                font: { size: 11 }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const index = context.dataIndex;
                                    const opd = departmentChart.customOpd?.[index] ?? 0;
                                    const ipd = departmentChart.customIpd?.[index] ?? 0;
                                    const total = context.raw || 0;
                                    return ` ${context.label}: ${total} patients (OPD: ${opd}, IPD: ${ipd})`;
                                }
                            }
                        }
                    },
                    cutout: '65%'
                }
            });
        }

        // Switch Time Range for Patient Flow (7, 14, 30 Days)
        function switchPatientFlowRange(days, btn) {
            patientFlowCurrentDays = days;
            document.querySelectorAll('.flow-range-btn').forEach(b => {
                b.className = 'flow-range-btn px-2.5 py-1 rounded-lg text-gray-600 hover:text-gray-900 transition-all font-medium';
            });
            if (btn) {
                btn.className = 'flow-range-btn px-2.5 py-1 rounded-lg bg-white text-emerald-800 shadow-sm font-bold transition-all';
            }
            loadAnalyticsData(days);
        }

        // Toggle Chart View Type (Line Area vs Grouped Bar)
        function togglePatientFlowChartType(type, btn) {
            patientFlowChartType = type;
            document.querySelectorAll('.flow-view-btn').forEach(b => {
                b.className = 'flow-view-btn px-2.5 py-1 rounded-lg text-gray-500 hover:text-gray-900 transition-all';
            });
            if (btn) {
                btn.className = 'flow-view-btn px-2.5 py-1 rounded-lg bg-white text-gray-800 shadow-sm font-bold transition-all';
            }
            if (admissionsChart) {
                createAdmissionsChart(
                    admissionsChart.data.labels,
                    admissionsChart.data.datasets[0].data,
                    admissionsChart.data.datasets[1].data,
                    admissionsChart.data.datasets[2].data,
                    type
                );
            }
        }

        // Render Ribbon KPIs for Patient Flow
        function renderPatientFlowMetrics(summary, days) {
            if (!summary) return;
            patientFlowSummaryData = summary;
            
            const opdEl = document.getElementById('flowSummaryOpd');
            const opdAvgEl = document.getElementById('flowSummaryOpdAvg');
            const opdShareEl = document.getElementById('flowBadgeOpdShare');
            const ipdEl = document.getElementById('flowSummaryIpd');
            const ipdAvgEl = document.getElementById('flowSummaryIpdAvg');
            const ipdShareEl = document.getElementById('flowBadgeIpdShare');
            const totalEl = document.getElementById('flowSummaryTotal');
            const totalAvgEl = document.getElementById('flowSummaryDailyAvg');
            const convEl = document.getElementById('flowSummaryConv');
            const peakEl = document.getElementById('flowSummaryPeak');

            if (opdEl) opdEl.textContent = summary.total_opd || 0;
            if (opdAvgEl) opdAvgEl.textContent = `Avg: ${summary.avg_daily_opd || 0} / day`;
            if (opdShareEl) opdShareEl.textContent = `${summary.opd_share || 0}%`;

            if (ipdEl) ipdEl.textContent = summary.total_ipd || 0;
            if (ipdAvgEl) ipdAvgEl.textContent = `Avg: ${summary.avg_daily_ipd || 0} / day`;
            if (ipdShareEl) ipdShareEl.textContent = `${summary.ipd_share || 0}%`;

            if (totalEl) totalEl.textContent = summary.total_flow || 0;
            if (totalAvgEl) totalAvgEl.textContent = `Avg: ${summary.avg_daily_total || 0} / day`;

            if (convEl) convEl.textContent = `${summary.conversion_rate || 0}%`;
            if (peakEl) {
                if (summary.peak_day_name && summary.peak_day_count > 0) {
                    peakEl.textContent = `Peak: ${summary.peak_day_name} (${summary.peak_day_count})`;
                    peakEl.title = `Peak Flow on ${summary.peak_day_name}: ${summary.peak_day_count} patients`;
                } else {
                    peakEl.textContent = `Peak: -`;
                }
            }
        }

        // Load Analytics Data (Charts & Patient Flow)
        async function loadAnalyticsData(days = patientFlowCurrentDays) {
            try {
                const response = await fetch(`/GM_HMS/api/admin/analytics?days=${days}`);
                if (!response.ok) throw new Error('Failed to fetch analytics');
                
                const result = await response.json();
                if (result.success && result.data) {
                    const data = result.data;
                    
                    // Update Admissions Chart
                    if (data.admissions) {
                        patientFlowDailyData = data.admissions.daily_breakdown || [];
                        createAdmissionsChart(
                            data.admissions.labels,
                            data.admissions.opd || [],
                            data.admissions.ipd || [],
                            data.admissions.total || [],
                            patientFlowChartType
                        );
                        
                        if (data.admissions.summary) {
                            renderPatientFlowMetrics(data.admissions.summary, days);
                        }
                    }
                    
                    // Update Revenue Chart
                    if (data.revenue && revenueChart) {
                        revenueChart.data.labels = data.revenue.labels;
                        revenueChart.data.datasets[0].data = data.revenue.opd || [];
                        revenueChart.data.datasets[1].data = data.revenue.ipd || [];
                        revenueChart.data.datasets[2].data = data.revenue.total || data.revenue.values || [];
                        revenueChart.update();
                    }
                    
                    // Update Department Chart
                    if (departmentChart) {
                        if (data.departments && data.departments.labels && data.departments.labels.length > 0) {
                            departmentChart.data.labels = data.departments.labels;
                            departmentChart.data.datasets[0].data = data.departments.values;
                            departmentChart.customOpd = data.departments.opd || [];
                            departmentChart.customIpd = data.departments.ipd || [];
                            departmentChart.data.datasets[0].backgroundColor = [
                                '#10b981', '#8b5cf6', '#0284c7', '#f59e0b', '#ec4899', '#6366f1'
                            ];
                        } else {
                            departmentChart.data.labels = ['No Data'];
                            departmentChart.data.datasets[0].data = [1];
                            departmentChart.data.datasets[0].backgroundColor = ['#e2e8f0'];
                        }
                        departmentChart.update();
                    }
                }
            } catch (error) {
                console.error('Error loading analytics:', error);
            }
        }

        // ============================================================
        // PATIENT FLOW DAY-BY-DAY DRILLDOWN MODAL CONTROLLERS
        // ============================================================
        function openPatientFlowDrilldown() {
            const modal = document.getElementById('patientFlowDrilldownModal');
            if (!modal) return;
            modal.classList.remove('hidden');

            const periodEl = document.getElementById('flowModalPeriod');
            const opdEl = document.getElementById('flowModalOpd');
            const ipdEl = document.getElementById('flowModalIpd');
            const convEl = document.getElementById('flowModalConv');

            if (periodEl) periodEl.textContent = `Last ${patientFlowCurrentDays} Days`;
            if (patientFlowSummaryData) {
                if (opdEl) opdEl.textContent = patientFlowSummaryData.total_opd || 0;
                if (ipdEl) ipdEl.textContent = patientFlowSummaryData.total_ipd || 0;
                if (convEl) convEl.textContent = `${patientFlowSummaryData.conversion_rate || 0}%`;
            }

            renderPatientFlowTable(patientFlowDailyData);
        }

        function closePatientFlowDrilldown() {
            const modal = document.getElementById('patientFlowDrilldownModal');
            if (modal) modal.classList.add('hidden');
        }

        function setFlowModalFilter(filter, btn) {
            patientFlowFilter = filter;
            document.querySelectorAll('.flow-modal-filter-btn').forEach(b => {
                b.className = 'flow-modal-filter-btn px-3 py-1.5 rounded-lg text-gray-600 hover:text-gray-900 transition-all';
            });
            if (btn) {
                btn.className = 'flow-modal-filter-btn px-3 py-1.5 rounded-lg bg-white text-emerald-800 shadow-sm transition-all font-semibold';
            }
            filterFlowModalTable();
        }

        function filterFlowModalTable() {
            const q = (document.getElementById('flowModalSearchInput')?.value || '').toLowerCase().trim();
            let filtered = patientFlowDailyData;

            if (patientFlowFilter === 'active') {
                filtered = filtered.filter(item => (item.total || 0) > 0);
            } else if (patientFlowFilter === 'ipd_only') {
                filtered = filtered.filter(item => (item.ipd_count || 0) > 0);
            }

            if (q) {
                filtered = filtered.filter(item => 
                    (item.date && item.date.toLowerCase().includes(q)) ||
                    (item.day_name && item.day_name.toLowerCase().includes(q)) ||
                    (item.label && item.label.toLowerCase().includes(q))
                );
            }

            renderPatientFlowTable(filtered);
        }

        function renderPatientFlowTable(list) {
            const tbody = document.getElementById('flowModalTableBody');
            if (!tbody) return;

            if (!list || list.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No patient flow recorded for this selection.</td></tr>`;
                return;
            }

            tbody.innerHTML = list.map(item => {
                const opd = item.opd_count || 0;
                const ipd = item.ipd_count || 0;
                const total = item.total || 0;
                const opdPct = item.opd_pct || (total > 0 ? Math.round((opd / total) * 100) : 0);
                const ipdPct = item.ipd_pct || (total > 0 ? Math.round((ipd / total) * 100) : 0);
                const isPeak = (patientFlowSummaryData && patientFlowSummaryData.peak_day_count > 0 && total === patientFlowSummaryData.peak_day_count);

                return `
                    <tr class="hover:bg-gray-50/80 transition-colors ${isPeak ? 'bg-amber-50/40' : ''}">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-800 text-sm flex items-center gap-1.5">
                                <span>${item.day_name || ''}</span>
                                <span class="text-xs text-gray-400 font-normal">(${item.date || ''})</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold ${opd > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-400'}">
                                <span class="w-1.5 h-1.5 rounded-full ${opd > 0 ? 'bg-emerald-500' : 'bg-gray-300'}"></span>
                                ${opd}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold ${ipd > 0 ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-400'}">
                                <span class="w-1.5 h-1.5 rounded-full ${ipd > 0 ? 'bg-purple-500' : 'bg-gray-300'}"></span>
                                ${ipd}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center font-bold text-gray-800">
                            ${total}
                        </td>
                        <td class="px-4 py-3">
                            <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden flex shadow-inner">
                                <div class="bg-emerald-500 h-3 transition-all" style="width: ${opdPct}%" title="OPD: ${opd} (${opdPct}%)"></div>
                                <div class="bg-purple-500 h-3 transition-all" style="width: ${ipdPct}%" title="IPD: ${ipd} (${ipdPct}%)"></div>
                            </div>
                            <div class="flex justify-between text-[10px] text-gray-400 mt-1">
                                <span>OPD: ${opdPct}%</span>
                                <span>IPD: ${ipdPct}%</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            ${isPeak ? '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200"><i class="fas fa-crown text-[10px]"></i> Peak Flow</span>' : '<span class="text-xs text-gray-400">-</span>'}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button onclick="inspectDayPatientFlow('${item.date}')" class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg text-xs font-semibold transition-colors flex items-center gap-1 ml-auto">
                                <i class="fas fa-search text-[10px]"></i> Inspect
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Quick Inspect Day Shortcut: Opens Patient Drilldown Filtered to that Day
        function inspectDayPatientFlow(dateStr) {
            closePatientFlowDrilldown();
            openPatientDrilldown();
            const search = document.getElementById('patientSearchInput');
            if (search) {
                search.value = dateStr;
                filterPatientTable();
            }
        }

        // Initialize charts immediately
        initCharts();

        // Load Dashboard Statistics
        async function loadDashboardStats() {
            try {
                const response = await fetch('/GM_HMS/api/admin/dashboard-summary');
                if (!response.ok) {
                    throw new Error('Failed to fetch dashboard statistics');
                }
                const result = await response.json();
                if (result.success && result.data) {
                    const data = result.data;
                    
                    // Update Total Patients
                    const totalPatients = (data.total_patients || 0).toLocaleString();
                    document.getElementById('totalPatients').textContent = totalPatients;
                    document.getElementById('patientsSecondary').textContent = `OPD Today: ${data.patients_today || 0} | Inpatients: ${data.active_ipd || 0} | Month: ${data.patients_month || 0}`;
                    
                    // Update Active Inpatients (IPD)
                    const activeIpd = (data.active_ipd || 0).toLocaleString();
                    const activeIpdEl = document.getElementById('activeIpd');
                    if (activeIpdEl) {
                        activeIpdEl.textContent = `${activeIpd} Admitted`;
                    }
                    const ipdSecondaryEl = document.getElementById('ipdSecondary');
                    if (ipdSecondaryEl) {
                        ipdSecondaryEl.textContent = `Admitted Today: ${data.ipd_admissions_today || 0} | Discharged: ${data.ipd_discharges_today || 0}`;
                    }
                    const ipdBadge = document.getElementById('ipdOccupancyBadge');
                    if (ipdBadge) {
                        ipdBadge.textContent = `${data.bed_occupancy_rate || 0}% Bed Occupancy`;
                    }
                    
                    // Update Appointments / OPD Visits Today
                    document.getElementById('appointmentsToday').textContent = `${data.appointments_today || 0} Visits`;
                    document.getElementById('appointmentsPending').textContent = `${data.appointments_pending || 0} Pending`;
                    document.getElementById('appointmentsSecondary').textContent = `Approved: ${data.appointments_approved || 0} | Cancelled: ${data.appointments_cancelled || 0}`;
                    
                    // Update Revenue Today & Month (Consolidated OPD + IPD)
                    const revenueToday = data.revenue_today ? parseFloat(data.revenue_today) : 0;
                    const opdRevToday = data.opd_revenue_today ? parseFloat(data.opd_revenue_today) : 0;
                    const ipdRevToday = data.ipd_revenue_today ? parseFloat(data.ipd_revenue_today) : 0;
                    const revenueMonth = data.revenue_month ? parseFloat(data.revenue_month) : 0;
                    
                    document.getElementById('revenueToday').textContent = 
                        '₹' + revenueToday.toLocaleString('en-IN', { maximumFractionDigits: 0 });
                    document.getElementById('revenueSecondary').textContent = 
                        `OPD: ₹${opdRevToday.toLocaleString('en-IN')} | IPD: ₹${ipdRevToday.toLocaleString('en-IN')} | Month: ₹${revenueMonth.toLocaleString('en-IN')}`;
                        
                    // Update Operations Today / Surgeries
                    const opsContainer = document.getElementById('operationsContainer');
                    if (opsContainer) {
                        if (data.operations_today && data.operations_today.length > 0) {
                            opsContainer.innerHTML = data.operations_today.map((op, i) => {
                                const colors = ['purple', 'emerald', 'sky', 'amber', 'rose'];
                                const color = colors[i % colors.length];
                                const amt = op.amount ? `₹${parseFloat(op.amount).toLocaleString('en-IN')}` : '';
                                return `
                                <div class="p-2 bg-gray-50/80 hover:bg-gray-100/90 rounded-lg border border-gray-100 flex items-start justify-between gap-2 transition-all">
                                    <div class="flex items-start gap-2 min-w-0">
                                        <div class="w-7 h-7 rounded-md bg-${color}-100 text-${color}-700 flex items-center justify-center shrink-0 mt-0.5 text-xs font-bold shadow-2xs">
                                            <i class="fas fa-procedures"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-bold text-gray-900 truncate">${op.name || 'Surgical Procedure'}</p>
                                            <p class="text-[10px] text-gray-500 font-medium truncate">${op.patient_name || 'Patient'} • <span class="text-[9px] bg-${color}-50 text-${color}-700 font-semibold px-1 py-0.2 rounded border border-${color}-200">${op.type || 'OT'}</span></p>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="text-xs font-bold text-gray-800">${amt}</span>
                                        <p class="text-[9px] text-gray-400 font-medium">${op.formatted_date || op.created_at || 'Today'}</p>
                                    </div>
                                </div>
                                `;
                            }).join('');
                        } else {
                            opsContainer.innerHTML = `
                                <div class="p-3 bg-gray-50 rounded-xl text-center border border-dashed border-gray-200">
                                    <i class="fas fa-check-circle text-emerald-500 text-base mb-0.5"></i>
                                    <p class="text-xs font-semibold text-gray-700">No Surgeries Scheduled Today</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">OT theater is in readiness mode</p>
                                </div>
                            `;
                        }
                    }
                    
                    // Update Recent Activity (Unified Live Stream)
                    const activityContainer = document.getElementById('recentActivityContainer');
                    if (activityContainer) {
                        if (data.recent_activity && data.recent_activity.length > 0) {
                            activityContainer.innerHTML = data.recent_activity.map(act => {
                                const color = act.color || 'emerald';
                                const icon = act.icon || 'fa-bell';
                                return `
                                <div class="flex items-start gap-3 p-2.5 bg-gray-50/70 hover:bg-gray-100/80 rounded-xl border border-gray-100 transition-colors">
                                    <div class="w-8 h-8 rounded-lg bg-${color}-100 text-${color}-700 flex items-center justify-center shrink-0 text-xs font-bold shadow-xs">
                                        <i class="fas ${icon}"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-1">
                                            <p class="text-xs font-bold text-gray-800 truncate">${act.action}</p>
                                            <span class="text-[10px] text-gray-400 shrink-0">${act.created_at ? new Date(act.created_at).toLocaleDateString('en-GB', {day:'2-digit', month:'short'}) : 'Recent'}</span>
                                        </div>
                                        <p class="text-[11px] text-gray-600 truncate mt-0.5">${act.patient_name ? `<strong class="text-gray-900">${act.patient_name}</strong> • ` : ''}${act.entity_details || act.entity_id || ''}</p>
                                    </div>
                                </div>
                                `;
                            }).join('');
                        } else {
                            activityContainer.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">No recent activity recorded</p>';
                        }
                    }
                    
                    // Update System Alerts
                    const alertsContainer = document.getElementById('systemAlertsContainer');
                    if (alertsContainer) {
                        if (data.system_alerts && data.system_alerts.length > 0) {
                            alertsContainer.innerHTML = data.system_alerts.map(alert => `
                                <div class="flex items-start p-3 bg-red-50 border-l-4 border-red-500 rounded">
                                    <i class="fas fa-exclamation-circle text-red-500 mt-1 mr-3"></i>
                                    <div>
                                        <p class="text-sm font-semibold text-red-800">Pharmacy Low Stock Alert</p>
                                        <p class="text-xs text-red-600">${alert.product_name} - Only ${alert.quantity} units left (Min: ${alert.min_stock})</p>
                                    </div>
                                </div>
                            `).join('');
                        } else {
                            alertsContainer.innerHTML = '<p class="text-sm text-gray-500">No critical alerts at this time.</p>';
                        }
                    }
                    
                    // Update Upcoming Appointments (Today Only + Count Badge)
                    const appointmentsContainer = document.getElementById('upcomingAppointmentsContainer');
                    const countBadge = document.getElementById('upcomingAppointmentsCount');
                    const aptsToday = data.upcoming_appointments || [];
                    const aptCount = data.appointments_today ?? aptsToday.length;

                    if (countBadge) {
                        countBadge.textContent = `${aptCount} Today`;
                        countBadge.className = aptCount > 0 
                            ? 'text-xs font-bold px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-800 border border-blue-300'
                            : 'text-xs font-semibold px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-600 border border-gray-200';
                    }

                    if (appointmentsContainer) {
                        if (aptsToday.length > 0) {
                            appointmentsContainer.innerHTML = aptsToday.map((apt, i) => {
                                const colors = ['purple', 'blue', 'emerald', 'amber', 'rose'];
                                const color = colors[i % colors.length];
                                const initial = (apt.patient_name || 'P').charAt(0).toUpperCase();
                                const timeStr = apt.time_formatted || 'Today';
                                const tokenStr = apt.token_number ? `#${apt.token_number}` : `#${i + 1}`;
                                return `
                                <div class="flex items-center justify-between p-2.5 bg-gray-50/80 hover:bg-gray-100/90 rounded-xl border border-gray-100 transition-all">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-${color}-100 text-${color}-700 flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                                            ${initial}
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-gray-800">${apt.patient_name || 'Patient'} <span class="text-[10px] text-gray-400 font-mono font-medium">${tokenStr}</span></p>
                                            <p class="text-[11px] text-gray-500 font-medium">Dr. ${apt.doctor_name || 'Doctor'} • <span class="text-gray-400">${apt.specialization || 'OPD'}</span></p>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="text-xs font-bold text-${color}-700 bg-${color}-50 px-2 py-0.5 rounded-lg border border-${color}-200">${timeStr}</span>
                                        <p class="text-[10px] text-blue-600 font-semibold mt-0.5">Today</p>
                                    </div>
                                </div>
                                `;
                            }).join('');
                        } else {
                            appointmentsContainer.innerHTML = `
                                <div class="p-4 bg-gray-50/80 rounded-xl text-center border border-dashed border-gray-200">
                                    <i class="fas fa-calendar-day text-blue-400 text-xl mb-1.5 block"></i>
                                    <p class="text-xs font-bold text-gray-700">0 Appointments Scheduled Today</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">No upcoming outpatient consultations booked for today</p>
                                </div>
                            `;
                        }
                    }
                } else {
                    console.error('Invalid response format:', result);
                    showError();
                }
            } catch (error) {
                console.error('Error loading dashboard statistics:', error);
                showError();
            }
        }

        function showError() {
            document.getElementById('totalPatients').textContent = 'Error';
            if (document.getElementById('activeIpd')) document.getElementById('activeIpd').textContent = 'Error';
            document.getElementById('appointmentsToday').textContent = 'Error';
            document.getElementById('revenueToday').textContent = 'Error';
        }
        
        // Load Bed Availability Statistics
        async function loadBedAvailability() {
            try {
                const response = await fetch('/GM_HMS/api/admin/bed-availability');
                if (!response.ok) throw new Error('Failed to fetch bed availability');
                const result = await response.json();
                
                if (result.success && result.data && result.data.bed_stats) {
                    const bedStats = result.data.bed_stats;
                    const container = document.getElementById('bedAvailabilityContainer');
                    if (!container) return;
                    
                    if (bedStats.length === 0) {
                        container.innerHTML = '<p class="text-xs text-gray-500">No bed data available</p>';
                        return;
                    }
                    
                    let html = '';
                    bedStats.forEach(stat => {
                        const percentage = stat.occupancy_percentage;
                        let barColor = 'bg-green-500';
                        if (percentage > 70) barColor = 'bg-red-500';
                        else if (percentage > 50) barColor = 'bg-orange-500';
                        
                        html += `
                            <div class="border-b border-gray-100 pb-2 mb-2 last:border-0 last:pb-0 last:mb-0">
                                <div class="flex justify-between items-start mb-0.5">
                                    <div class="min-w-0 pr-2">
                                        <p class="text-xs font-bold text-gray-800 truncate">${stat.ward_name} - ${stat.room_name}</p>
                                        <p class="text-[10px] text-gray-400 font-medium">${stat.ward_type} | ${stat.room_category}</p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="text-xs font-bold text-gray-800">${stat.occupied_beds}/${stat.total_beds}</span>
                                        <p class="text-[10px] text-gray-400">Avl: ${stat.available_beds}</p>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-1.5 mt-1">
                                    <div class="${barColor} h-1.5 rounded-full" style="width: ${percentage}%"></div>
                                </div>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                } else {
                    document.getElementById('bedAvailabilityContainer').innerHTML = '<p class="text-xs text-gray-500">Error loading bed data</p>';
                }
            } catch (error) {
                console.error('Error loading bed availability:', error);
                document.getElementById('bedAvailabilityContainer').innerHTML = '<p class="text-xs text-gray-500">Error loading bed data</p>';
            }
        }
        
        // Load Active Departments Statistics
        async function loadActiveDepartments() {
            try {
                const response = await fetch('/GM_HMS/api/admin/active-departments');
                if (!response.ok) throw new Error('Failed to fetch departments');
                const result = await response.json();
                
                if (result.success && result.data && result.data.department_stats) {
                    const deptStats = result.data.department_stats;
                    const container = document.getElementById('activeDepartmentsContainer');
                    if (!container) return;
                    
                    if (deptStats.length === 0) {
                        container.innerHTML = '<p class="text-xs text-gray-500">No active departments found</p>';
                        return;
                    }
                    
                    let html = '';
                    deptStats.forEach(stat => {
                        const isEmergency = stat.department_name.toLowerCase().includes('emergency');
                        const statusBadge = isEmergency 
                            ? `<span class="px-2 py-0.5 bg-red-100 text-red-600 rounded text-[10px] font-medium">24/7 Active</span>`
                            : `<span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-[10px] font-medium">${stat.doctor_count} Drs</span>`;
                        
                        html += `
                            <div class="flex justify-between items-center py-1 border-b border-gray-50 last:border-0 last:pb-0">
                                <div class="flex flex-col min-w-0 pr-2">
                                    <span class="text-xs font-semibold text-gray-800 truncate">${stat.department_name}</span>
                                    <span class="text-[9px] text-gray-400 font-medium tracking-wide uppercase">${stat.department_type}</span>
                                </div>
                                ${statusBadge}
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                } else {
                    document.getElementById('activeDepartmentsContainer').innerHTML = '<p class="text-xs text-gray-500">Error loading departments</p>';
                }
            } catch (error) {
                console.error('Error loading departments:', error);
                document.getElementById('activeDepartmentsContainer').innerHTML = '<p class="text-xs text-gray-500">Error loading departments</p>';
            }
        }
        
        // Load stats on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardStats();
            loadBedAvailability();
            loadActiveDepartments();
            loadAnalyticsData();
        });

        // Reports Modal Functionality
        let reportsLoaded = false;
        
        async function toggleReportsModal() {
            const modal = document.getElementById('reportsModal');
            if (modal && modal.classList.contains('hidden')) {
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
                if (!reportsLoaded) {
                    loadReports();
                }
            } else if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
        }

        async function loadReports() {
            try {
                // Fetch report data
                const response = await fetch('/GM_HMS/api/opd/reports');
                const result = await response.json();

                if (!result.success) {
                    console.error("API Error - Loading sample data");
                    document.getElementById('report-doctor-wise').innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-red-500">Failed to load reports</td></tr>';
                    return;
                }

                const data = result.data;
                reportsLoaded = true;

                // 1. Doctor Wise Workload Table (OPD + IPD)
                const doctorTbody = document.getElementById('report-doctor-wise');
                if (data.doctor_wise && data.doctor_wise.length > 0) {
                    doctorTbody.innerHTML = data.doctor_wise.map(d => `
                        <tr class="bg-white border-b hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-3.5 font-semibold text-gray-900 flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold">
                                    ${(d.full_name || 'Dr').charAt(0)}
                                </div>
                                <span>${d.full_name || 'Unknown'}</span>
                            </td>
                            <td class="px-6 py-3.5 text-gray-600 text-xs">${d.specialization || 'General'}</td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="bg-emerald-50 text-emerald-700 text-xs font-semibold px-2 py-0.5 rounded border border-emerald-200">
                                    ${d.opd_count || 0}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="bg-purple-50 text-purple-700 text-xs font-semibold px-2 py-0.5 rounded border border-purple-200">
                                    ${d.ipd_count || 0}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 text-right font-bold text-gray-800">
                                <span class="bg-gray-100 text-gray-800 text-xs font-bold px-2.5 py-1 rounded-full">
                                    ${d.count || (parseInt(d.opd_count || 0) + parseInt(d.ipd_count || 0))} Patients
                                </span>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    doctorTbody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No clinical activity recorded today</td></tr>';
                }

                // 2. Revenue Overview (Dual Stream OPD + IPD)
                const revenueContainer = document.getElementById('report-revenue');
                const revenueTotal = parseFloat(data.revenue?.total || 0);
                const opdTotal = parseFloat(data.revenue?.opd_total || 0);
                const ipdTotal = parseFloat(data.revenue?.ipd_total || 0);
                
                const opdPercent = revenueTotal > 0 ? Math.round((opdTotal / revenueTotal) * 100) : 50;
                const ipdPercent = revenueTotal > 0 ? (100 - opdPercent) : 50;
                
                revenueContainer.innerHTML = `
                    <div class="flex flex-col justify-center h-full space-y-3">
                        <div class="text-center">
                            <div class="text-3xl font-extrabold text-gray-800">₹${revenueTotal.toLocaleString('en-IN')}</div>
                            <div class="text-xs text-gray-500 mt-0.5">${data.revenue?.count || 0} Total Transactions (Month to Date)</div>
                        </div>
                        
                        <!-- Dual Progress Bar -->
                        <div class="w-full bg-gray-100 rounded-full h-3 flex overflow-hidden shadow-inner">
                            <div class="bg-emerald-500 h-full transition-all duration-500" style="width: ${opdPercent}%" title="OPD: ${opdPercent}%"></div>
                            <div class="bg-purple-500 h-full transition-all duration-500" style="width: ${ipdPercent}%" title="IPD: ${ipdPercent}%"></div>
                        </div>
                        
                        <!-- Breakdown details -->
                        <div class="grid grid-cols-2 gap-2 pt-1 border-t border-gray-100 text-xs">
                            <div class="flex items-center justify-between p-2 bg-emerald-50 rounded border border-emerald-100">
                                <span class="font-medium text-emerald-800 flex items-center gap-1">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span> OPD
                                </span>
                                <span class="font-bold text-emerald-900">₹${opdTotal.toLocaleString('en-IN')}</span>
                            </div>
                            <div class="flex items-center justify-between p-2 bg-purple-50 rounded border border-purple-100">
                                <span class="font-medium text-purple-800 flex items-center gap-1">
                                    <span class="w-2 h-2 rounded-full bg-purple-500 inline-block"></span> IPD
                                </span>
                                <span class="font-bold text-purple-900">₹${ipdTotal.toLocaleString('en-IN')}</span>
                            </div>
                        </div>
                    </div>
                `;

                // 3. Daily Patient Flow Trend (Comparative Bar Chart)
                const trendContainer = document.getElementById('report-daily-trend');
                if (data.daily_trend && data.daily_trend.length > 0) {
                    const maxVal = Math.max(...data.daily_trend.map(d => Math.max(d.opd_count || 0, d.ipd_count || 0, d.count || 0))) || 10;
                    
                    trendContainer.innerHTML = `
                        <div class="flex items-end justify-between h-40 space-x-2 pt-4">
                             ${data.daily_trend.map(d => {
                                 const opdHeight = Math.max(8, ((d.opd_count || 0) / maxVal) * 100);
                                 const ipdHeight = Math.max(8, ((d.ipd_count || 0) / maxVal) * 100);
                                 const date = new Date(d.date).toLocaleDateString('en-US', { weekday: 'short' });
                                 return `
                                                                     <div class="flex items-end justify-center gap-1 w-full h-32 relative">
                                             <!-- OPD Bar -->
                                             <div class="w-1/2 bg-emerald-400 hover:bg-emerald-500 rounded-t transition-all duration-300 relative" style="height: ${opdHeight}%">
                                                 <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-[10px] py-0.5 px-1.5 rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-10">
                                                     OPD: ${d.opd_count || 0}
                                                 </div>
                                             </div>
                                             <!-- IPD Bar -->
                                             <div class="w-1/2 bg-purple-400 hover:bg-purple-500 rounded-t transition-all duration-300 relative" style="height: ${ipdHeight}%">
                                                 <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 bg-purple-900 text-white text-[10px] py-0.5 px-1.5 rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-10">
                                                     IPD: ${d.ipd_count || 0}
                                                 </div>
                                             </div>
                                         </div>
                                         <div class="text-[11px] font-semibold text-gray-500 mt-2">${date}</div>
                                     </div>
                                 `;
                             }).join('')}
                        </div>
                    `;
                } else {
                    trendContainer.innerHTML = '<div class="flex items-center justify-center h-full text-gray-500 text-sm">No trend data available</div>';
                }

            } catch (error) {
                console.error("Fetch error:", error);
                document.getElementById('report-doctor-wise').innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-red-500">System Error</td></tr>';
            }
        }

        // ============================================================
        // 1. PATIENT DRILLDOWN MODAL CONTROLLER
        // ============================================================
        let cachedPatients = [];
        let currentPatientFilter = 'all';

        async function openPatientDrilldown() {
            const modal = document.getElementById('patientDrilldownModal');
            if (!modal) return;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            const tbody = document.getElementById('patientModalTableBody');
            tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-gray-400"><i class="fas fa-spinner fa-spin text-emerald-600 text-xl mb-2"></i><br>Loading patient directory...</td></tr>';

            try {
                const response = await fetch('/GM_HMS/api/admin/patients-details');
                const result = await response.json();

                if (result.success && result.data) {
                    const { summary, patients } = result.data;
                    cachedPatients = patients || [];

                    // Update KPI strip
                    document.getElementById('patientModalTotal').textContent = (summary.total || 0).toLocaleString();
                    document.getElementById('patientModalToday').textContent = (summary.today || 0).toLocaleString();
                    document.getElementById('patientModalMonth').textContent = (summary.this_month || 0).toLocaleString();
                    document.getElementById('patientModalIpd').textContent = (summary.active_ipd || 0).toLocaleString();

                    renderPatientTable();
                } else {
                    tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-red-500">Failed to load patient records</td></tr>';
                }
            } catch (err) {
                console.error('Error fetching patients:', err);
                tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-red-500">Error loading patient records</td></tr>';
            }
        }

        function closePatientDrilldown() {
            const modal = document.getElementById('patientDrilldownModal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
        }

        function setPatientFilter(filter, btn) {
            currentPatientFilter = filter;
            document.querySelectorAll('.patient-filter-btn').forEach(b => {
                b.className = 'patient-filter-btn px-3 py-1.5 rounded-lg text-gray-600 hover:text-gray-900 transition-all';
            });
            if (btn) {
                btn.className = 'patient-filter-btn px-3 py-1.5 rounded-lg bg-white text-emerald-800 shadow-sm font-bold transition-all';
            }
            renderPatientTable();
        }

        function filterPatientTable() {
            renderPatientTable();
        }

        function renderPatientTable() {
            const tbody = document.getElementById('patientModalTableBody');
            const search = (document.getElementById('patientSearchInput')?.value || '').toLowerCase().trim();

            let list = cachedPatients.filter(p => {
                // Filter tag match
                if (currentPatientFilter === 'ipd' && !p.active_admission_id) return false;
                if (currentPatientFilter === 'opd' && (p.active_admission_id || p.patient_status !== 'OPD Patient')) return false;
                if (currentPatientFilter === 'new_today') {
                    const todayStr = new Date().toISOString().slice(0, 10);
                    if (!p.registration_date || !p.registration_date.startsWith(todayStr)) return false;
                }

                // Search term match across all patient properties
                if (search) {
                    const regDate = p.registration_date ? String(p.registration_date).slice(0, 10) : '';
                    let regDateFormatted = '';
                    try {
                        if (p.registration_date) {
                            const d = new Date(p.registration_date);
                            regDateFormatted = `${d.getDate()}/${d.getMonth()+1}/${d.getFullYear()} ${d.toLocaleDateString('en-GB')}`;
                        }
                    } catch(e) {}

                    const searchClean = search.replace(/[-\/\s]/g, '');
                    const haystack = `${p.patient_id || ''} ${p.full_name || ''} ${p.first_name || ''} ${p.last_name || ''} ${p.phone || ''} ${p.city || ''} ${p.address || ''} ${p.blood_group || ''} ${regDate} ${regDateFormatted} ${p.patient_status || ''} ${p.active_bed_number || ''} ${p.active_ward_name || ''} ${p.ipd_doctor_name || ''}`.toLowerCase();
                    const haystackClean = haystack.replace(/[-\/\s]/g, '');
                    return haystack.includes(search) || (searchClean.length >= 4 && haystackClean.includes(searchClean));
                }
                return true;
            });

            if (list.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No matching patient records found</td></tr>';
                return;
            }

            tbody.innerHTML = list.map(p => {
                const initial = (p.first_name || p.full_name || 'P').charAt(0).toUpperCase();
                const isIpd = !!p.active_admission_id;
                const statusBadge = isIpd
                    ? `<span class="bg-purple-100 text-purple-800 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-purple-300" title="Bed: ${p.active_bed_number} (${p.active_ward_name})"><i class="fas fa-bed text-[10px] mr-1"></i> IPD (${p.active_bed_number || 'Bed'})</span>`
                    : (p.patient_status === 'OPD Patient' 
                        ? `<span class="bg-emerald-100 text-emerald-800 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-emerald-300">OPD Patient</span>`
                        : `<span class="bg-gray-100 text-gray-700 text-xs font-semibold px-2.5 py-0.5 rounded-full">Registered</span>`);

                const bloodBadge = p.blood_group && p.blood_group !== '-' 
                    ? `<span class="bg-red-50 text-red-600 font-bold text-xs px-2 py-0.5 rounded-md border border-red-200">${p.blood_group}</span>`
                    : `<span class="text-gray-400 text-xs">-</span>`;

                return `
                    <tr class="bg-white hover:bg-gray-50/80 transition-colors">
                        <td class="px-4 py-3 font-mono font-bold text-xs text-emerald-800">${p.patient_id}</td>
                        <td class="px-4 py-3 font-semibold text-gray-900 flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold shrink-0">
                                ${initial}
                            </div>
                            <span>${p.full_name || `${p.first_name || ''} ${p.last_name || ''}`}</span>
                        </td>
                        <td class="px-3 py-3 text-center text-xs text-gray-600">${p.age || '-'} / ${p.sex || '-'}</td>
                        <td class="px-3 py-3 text-center">${bloodBadge}</td>
                        <td class="px-4 py-3 text-xs text-gray-600">
                            <div>${p.phone || '-'}</div>
                            <div class="text-[10px] text-gray-400">${p.city || p.address || ''}</div>
                        </td>
                        <td class="px-4 py-3 text-center">${statusBadge}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">${p.registration_date ? new Date(p.registration_date).toLocaleDateString('en-GB') : '-'}</td>
                        <td class="px-4 py-3 text-right">
                            <button onclick="openPatientCard('${p.patient_id}')" class="px-2.5 py-1 text-xs bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-semibold rounded-lg border border-emerald-200 transition-colors inline-flex items-center gap-1 cursor-pointer" title="View Patient Card">
                                <i class="fas fa-id-card text-[10px]"></i> Card
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Direct navigation to individual patient card/profile via sessionStorage (no URL variables)
        function openPatientCard(patientId) {
            if (!patientId) return;
            sessionStorage.setItem('currentPatientId', patientId);
            window.location.href = '/GM_HMS/reception_view/patient_profile.php';
        }

        // ============================================================
        // 2. IPD INPATIENT DRILLDOWN MODAL CONTROLLER
        // ============================================================
        let cachedIpdAdmissions = [];
        let currentIpdFilter = 'all';

        async function openIpdDrilldown() {
            const modal = document.getElementById('ipdDrilldownModal');
            if (!modal) return;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            const tbody = document.getElementById('ipdModalTableBody');
            tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-gray-400"><i class="fas fa-spinner fa-spin text-purple-600 text-xl mb-2"></i><br>Loading inpatient census...</td></tr>';

            try {
                const [ipdRes, dashRes] = await Promise.all([
                    fetch('/GM_HMS/api/admin/ipd-details'),
                    fetch('/GM_HMS/api/admin/dashboard-summary')
                ]);
                const ipdResult = await ipdRes.json();
                const dashResult = await dashRes.json();

                if (dashResult.success && dashResult.data) {
                    const d = dashResult.data;
                    document.getElementById('ipdModalActive').textContent = (d.active_ipd || 0).toLocaleString();
                    document.getElementById('ipdModalAdmittedToday').textContent = (d.ipd_admissions_today || 0).toLocaleString();
                    document.getElementById('ipdModalDischargesToday').textContent = (d.ipd_discharges_today || 0).toLocaleString();
                    document.getElementById('ipdModalOccupancyRate').textContent = `${d.bed_occupancy_rate || 0}%`;
                }

                if (ipdResult.success && ipdResult.data) {
                    cachedIpdAdmissions = ipdResult.data.admissions || [];
                    renderIpdTable();
                } else {
                    tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-red-500">Failed to load inpatient admissions</td></tr>';
                }
            } catch (err) {
                console.error('Error fetching IPD data:', err);
                tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-red-500">Error loading inpatient records</td></tr>';
            }
        }

        function closeIpdDrilldown() {
            const modal = document.getElementById('ipdDrilldownModal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
        }

        function setIpdFilter(filter, btn) {
            currentIpdFilter = filter;
            document.querySelectorAll('.ipd-filter-btn').forEach(b => {
                b.className = 'ipd-filter-btn px-3 py-1.5 rounded-lg text-gray-600 hover:text-gray-900 transition-all';
            });
            if (btn) {
                btn.className = 'ipd-filter-btn px-3 py-1.5 rounded-lg bg-white text-purple-900 shadow-sm font-bold transition-all';
            }
            renderIpdTable();
        }

        function filterIpdTable() {
            renderIpdTable();
        }

        function renderIpdTable() {
            const tbody = document.getElementById('ipdModalTableBody');
            const search = (document.getElementById('ipdSearchInput')?.value || '').toLowerCase().trim();

            let list = cachedIpdAdmissions.filter(a => {
                const status = (a.status || '').toLowerCase();
                if (currentIpdFilter === 'admitted' && status !== 'admitted') return false;
                if (currentIpdFilter === 'discharged' && status !== 'discharged') return false;

                if (search) {
                    const admDate = a.admission_date ? String(a.admission_date).slice(0, 10) : '';
                    let admFormatted = '';
                    try {
                        if (a.admission_date) {
                            const d = new Date(a.admission_date);
                            admFormatted = `${d.getDate()}/${d.getMonth()+1}/${d.getFullYear()} ${d.toLocaleDateString('en-GB')}`;
                        }
                    } catch(e) {}

                    const searchClean = search.replace(/[-\/\s]/g, '');
                    const haystack = `${a.admission_id || ''} ${a.patient_name || ''} ${a.patient_id || ''} ${a.bed_number || ''} ${a.ward_name || ''} ${a.doctor_name || ''} ${a.doctor_specialization || ''} ${admDate} ${admFormatted} ${a.status || ''}`.toLowerCase();
                    const haystackClean = haystack.replace(/[-\/\s]/g, '');
                    return haystack.includes(search) || (searchClean.length >= 4 && haystackClean.includes(searchClean));
                }
                return true;
            });

            if (list.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No inpatient records found</td></tr>';
                return;
            }

            tbody.innerHTML = list.map(a => {
                const days = a.days_admitted ?? 0;
                const daysBadge = `<span class="bg-indigo-50 text-indigo-700 text-[10px] font-bold px-2 py-0.5 rounded-full border border-indigo-200 ml-1.5">${days} days</span>`;
                const balanceDue = a.financials?.balance_due ?? (a.balance_amount ?? 0);
                const balanceFormatted = '₹' + parseFloat(balanceDue || 0).toLocaleString('en-IN');
                
                const isAdmitted = (a.status || '').toLowerCase() === 'admitted';
                const isDischarged = (a.status || '').toLowerCase() === 'discharged';
                let statusBadge = '';
                if (isAdmitted) {
                    statusBadge = `
                        <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-green-300 flex items-center justify-center gap-1 w-fit mx-auto">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span> Admitted
                        </span>
                    `;
                } else if (isDischarged) {
                    statusBadge = `
                        <span class="bg-gray-100 text-gray-700 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-gray-300 flex items-center justify-center gap-1 w-fit mx-auto">
                            <i class="fas fa-check-circle text-gray-500 text-[10px]"></i> Discharged
                        </span>
                    `;
                } else {
                    statusBadge = `
                        <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-blue-300 flex items-center justify-center gap-1 w-fit mx-auto">
                            ${a.status || 'Active'}
                        </span>
                    `;
                }
                
                return `
                    <tr class="bg-white hover:bg-gray-50/80 transition-colors">
                        <td class="px-4 py-3 font-mono font-bold text-xs text-purple-900">${a.admission_id}</td>
                        <td class="px-4 py-3 font-semibold text-gray-900">
                            <div>${a.patient_name || 'Unknown'}</div>
                            <div class="text-[10px] text-gray-400 font-mono">${a.patient_id}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="bg-purple-100 text-purple-800 text-xs font-bold px-2 py-0.5 rounded border border-purple-300">
                                Bed ${a.bed_number || 'N/A'}
                            </span>
                            <span class="text-[10px] text-gray-500 block mt-0.5">${a.ward_name || 'Ward'}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-700">
                            <div class="font-medium">Dr. ${a.doctor_name || 'N/A'}</div>
                            <div class="text-[10px] text-gray-400">${a.doctor_specialization || ''}</div>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-600">
                            <span>${a.admission_date ? new Date(a.admission_date).toLocaleDateString('en-GB') : '-'}</span>
                            ${daysBadge}
                        </td>
                        <td class="px-4 py-3 text-right text-xs font-bold ${balanceDue > 0 ? 'text-red-600' : 'text-emerald-600'}">
                            ${balanceFormatted}
                        </td>
                        <td class="px-4 py-3 text-center">
                            ${statusBadge}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button onclick="openIpdPatientFullCard('${a.admission_id}', '${a.patient_id || ''}')" class="px-2.5 py-1 text-xs bg-[#f3efe6] hover:bg-[#1f6b4a] text-[#1f6b4a] hover:text-[#f3efe6] font-bold rounded-lg border border-[#1f6b4a] transition-all inline-flex items-center gap-1 cursor-pointer shadow-xs" title="View Inpatient Dossier & Billing Card">
                                <i class="fas fa-file-invoice text-[10px]"></i> Bill &amp; Details
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // ============================================================
        // INPATIENT DOSSIER & FULL CARD CONTROLLER (NO REDIRECT)
        // Strictly Theme: #f3efe6 (Warm Cream) & #1f6b4a (Forest Green)
        // ============================================================
        let currentIpdCardData = null;

        async function openIpdPatientFullCard(admissionId, patientId) {
            const modal = document.getElementById('ipdPatientCardModal');
            if (!modal) return;

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            // Show loading state
            const loading = document.getElementById('ipdCardLoading');
            if (loading) {
                loading.innerHTML = `
                    <i class="fas fa-spinner fa-spin text-3xl mb-3 text-[#1f6b4a]"></i>
                    <p class="font-bold text-sm text-[#1f6b4a]">Loading patient clinical and billing dossier...</p>
                `;
                loading.classList.remove('hidden');
            }
            document.getElementById('ipdTabPaneBill').classList.add('hidden');
            document.getElementById('ipdTabPaneItems').classList.add('hidden');
            document.getElementById('ipdTabPaneClinical').classList.add('hidden');

            // Reset tabs to Bill
            switchIpdCardTab('bill');

            try {
                const url = `/GM_HMS/api/admin/ipd-patient-details?admission_id=${encodeURIComponent(admissionId)}&patient_id=${encodeURIComponent(patientId || '')}`;
                const res = await fetch(url);
                const json = await res.json();

                if (!json.success || !json.data) {
                    throw new Error(json.error || 'Unable to load patient dossier');
                }

                currentIpdCardData = json.data;
                renderIpdPatientFullCard(json.data);
            } catch (err) {
                console.error('Error loading patient dossier:', err);
                const loading = document.getElementById('ipdCardLoading');
                if (loading) {
                    loading.innerHTML = `
                        <div class="py-12 text-center text-[#1f6b4a]">
                            <i class="fas fa-exclamation-triangle text-3xl mb-2 text-[#1f6b4a]"></i>
                            <p class="font-bold text-sm text-[#1f6b4a]">Failed to load patient records</p>
                            <p class="text-xs text-[#1f6b4a]/80 mt-1">${err.message}</p>
                        </div>
                    `;
                }
            }
        }

        function closeIpdPatientCardModal() {
            const modal = document.getElementById('ipdPatientCardModal');
            if (modal) modal.classList.add('hidden');
            document.body.style.overflow = '';
        }

        function switchIpdCardTab(tab) {
            ['bill', 'items', 'clinical'].forEach(t => {
                const btn = document.getElementById(`ipdTabBtn${t.charAt(0).toUpperCase() + t.slice(1)}`);
                const pane = document.getElementById(`ipdTabPane${t.charAt(0).toUpperCase() + t.slice(1)}`);
                if (btn && pane) {
                    if (t === tab) {
                        btn.className = 'px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all bg-[#1f6b4a] text-[#f3efe6] border border-[#1f6b4a] shadow-xs cursor-pointer';
                        if (!document.getElementById('ipdCardLoading').classList.contains('hidden')) {
                            // still loading
                        } else {
                            pane.classList.remove('hidden');
                        }
                    } else {
                        btn.className = 'px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all bg-[#f3efe6] text-[#1f6b4a] hover:bg-[#1f6b4a] hover:text-[#f3efe6] border border-[#1f6b4a] cursor-pointer';
                        pane.classList.add('hidden');
                    }
                }
            });
        }

        function renderIpdPatientFullCard(data) {
            const adm = data.admission || {};
            const m = data.billing_master || {};
            const items = data.billing_items || [];
            const clinical = data.clinical_records || [];

            // Hide loading, show default tab
            document.getElementById('ipdCardLoading').classList.add('hidden');
            document.getElementById('ipdTabPaneBill').classList.remove('hidden');

            // Header info
            const name = adm.full_name || 'Patient';
            document.getElementById('ipdCardAvatar').textContent = (name.charAt(0) || 'P').toUpperCase();
            document.getElementById('ipdCardPatientName').textContent = name;
            document.getElementById('ipdCardPid').textContent = adm.patient_id || 'N/A';
            document.getElementById('ipdCardAdmId').textContent = adm.admission_id || 'N/A';
            document.getElementById('ipdCardAgeGender').textContent = `${adm.age || '-'} yrs • ${adm.sex || '-'}`;
            document.getElementById('ipdCardBloodGroup').textContent = `Blood: ${adm.blood_group || 'N/A'}`;
            document.getElementById('ipdCardBedWard').textContent = `Bed ${adm.bed_number || 'N/A'} • ${adm.ward_name || 'General Ward'}`;
            document.getElementById('ipdCardDoctor').textContent = `Dr. ${adm.doctor_name || 'Attending'}`;

            // Status badge (High contrast text-white on #1f6b4a)
            document.getElementById('ipdCardStatusBadge').className = 'px-2.5 py-0.5 text-[11px] font-bold rounded-full bg-[#1f6b4a] text-white border border-[#1f6b4a]';
            document.getElementById('ipdCardStatusBadge').textContent = adm.admission_status || 'Admitted';

            // Financial KPIs (Strictly #1f6b4a & #f3efe6)
            const grandTotal = parseFloat(m.grand_total || 0);
            const amountPaid = parseFloat(m.amount_paid || 0);
            const balanceDue = parseFloat(m.balance_due || (grandTotal - amountPaid));
            const stayDays = adm.stay_days || m.total_days || 1;

            document.getElementById('ipdCardGrandTotal').textContent = `₹${grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
            document.getElementById('ipdCardGrandTotal').className = 'text-base sm:text-lg font-black text-[#1f6b4a]';

            document.getElementById('ipdCardAmountPaid').textContent = `₹${amountPaid.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
            document.getElementById('ipdCardAmountPaid').className = 'text-base sm:text-lg font-black text-[#1f6b4a]';

            document.getElementById('ipdCardBalanceDue').textContent = `₹${balanceDue.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
            document.getElementById('ipdCardBalanceDue').className = 'text-base sm:text-lg font-black text-[#1f6b4a]';

            document.getElementById('ipdCardStayDays').textContent = `${stayDays} ${stayDays === 1 ? 'Day' : 'Days'}`;
            document.getElementById('ipdCardStayDays').className = 'text-base sm:text-lg font-black text-[#1f6b4a]';

            document.getElementById('ipdCardPaymentStatus').textContent = m.payment_status || (balanceDue <= 0 ? 'Paid' : 'Pending');
            document.getElementById('ipdCardPaymentStatus').className = 'text-xs sm:text-sm font-black text-[#1f6b4a] mt-1';

            // Counts on tabs
            document.getElementById('ipdCardItemsCount').textContent = items.length;
            document.getElementById('ipdCardClinicalCount').textContent = clinical.length;

            // Tab 1: Category Breakdown Cards (Strictly #f3efe6 & #1f6b4a)
            const catGrid = document.getElementById('ipdCategoriesGrid');
            const categories = [
                { label: 'Room & Bed Charges', val: m.room_charges, icon: 'fa-bed' },
                { label: 'Doctor Consultations', val: m.doctor_charges, icon: 'fa-user-md' },
                { label: 'Pharmacy & Meds', val: m.pharmacy_charges, icon: 'fa-pills' },
                { label: 'Laboratory Tests', val: m.lab_charges, icon: 'fa-flask' },
                { label: 'Radiology / Imaging', val: m.radiology_charges, icon: 'fa-x-ray' },
                { label: 'Operation Theatre', val: m.ot_charges, icon: 'fa-hospital' },
                { label: 'Clinical Procedures', val: m.procedure_charges, icon: 'fa-procedures' },
                { label: 'Consumables & Misc', val: (parseFloat(m.consumable_charges || 0) + parseFloat(m.other_charges || 0)), icon: 'fa-box' }
            ];

            catGrid.innerHTML = categories.map(c => `
                <div class="border-2 border-[#1f6b4a] rounded-xl p-3 bg-white hover:bg-[#f3efe6]/40 transition-all shadow-xs">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[11px] font-bold text-[#1f6b4a] truncate">${c.label}</span>
                        <div class="w-7 h-7 rounded-lg bg-[#1f6b4a] text-[#f3efe6] flex items-center justify-center text-xs shadow-xs">
                            <i class="fas ${c.icon}"></i>
                        </div>
                    </div>
                    <div class="text-sm font-black text-[#1f6b4a]">₹${parseFloat(c.val || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})}</div>
                </div>
            `).join('');

            // Coverage details
            document.getElementById('ipdCardBillNo').textContent = m.bill_id || 'Not Finalized';
            document.getElementById('ipdCardBillType').textContent = m.bill_type || 'SELF';
            document.getElementById('ipdCardSponsor').textContent = m.sponsor || 'Self Pay';
            document.getElementById('ipdCardPolicyNo').textContent = m.policy_number || 'N/A';

            // Tab 2: Itemized Charges Table (Strictly #f3efe6 & #1f6b4a)
            const tbody = document.getElementById('ipdItemsTableBody');
            if (items.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-8 text-center text-[#1f6b4a] font-semibold">No individual billing items logged yet for this admission.</td></tr>`;
            } else {
                tbody.innerHTML = items.map(it => `
                    <tr class="hover:bg-[#f3efe6]/50 transition-colors">
                        <td class="px-3.5 py-2 text-[#1f6b4a] font-mono text-[11px] font-semibold">${it.charge_date || '-'}</td>
                        <td class="px-3.5 py-2">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#1f6b4a] text-[#f3efe6] border border-[#1f6b4a]">${it.charge_type || 'OTHER'}</span>
                        </td>
                        <td class="px-3.5 py-2 font-bold text-[#1f6b4a]">${it.description || '-'}</td>
                        <td class="px-3.5 py-2 text-right font-black text-[#1f6b4a]">₹${parseFloat(it.total_amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                        <td class="px-3.5 py-2 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#f3efe6] text-[#1f6b4a] border border-[#1f6b4a]">${it.status || 'LOGGED'}</span>
                        </td>
                    </tr>
                `).join('');
            }

            // Tab 3: Clinical Records (Strictly #f3efe6 & #1f6b4a)
            const clinicalList = document.getElementById('ipdClinicalList');
            if (clinical.length === 0) {
                clinicalList.innerHTML = `<div class="py-12 text-center text-[#1f6b4a] font-semibold">No clinical entries recorded for this patient yet.</div>`;
            } else {
                clinicalList.innerHTML = clinical.map((rec, idx) => {
                    const visits = Array.isArray(rec.consultant_visits) ? rec.consultant_visits : [];
                    const labs = Array.isArray(rec.lab_tests) ? rec.lab_tests : [];
                    const rx = Array.isArray(rec.pharmacy_orders) ? rec.pharmacy_orders : [];

                    return `
                        <div class="border-2 border-[#1f6b4a] rounded-xl p-4 bg-white shadow-xs">
                            <div class="flex items-center justify-between pb-2.5 mb-3 border-b-2 border-[#1f6b4a]/20">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-[#1f6b4a] text-[#f3efe6] text-xs font-black flex items-center justify-center">${idx + 1}</span>
                                    <span class="font-black text-sm text-[#1f6b4a]">Clinical Day: ${rec.record_date || 'Date N/A'}</span>
                                </div>
                                <span class="text-xs text-[#1f6b4a] font-mono font-bold">${rec.created_at || ''}</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                                <!-- Labs -->
                                <div class="bg-[#f3efe6] border border-[#1f6b4a] rounded-lg p-3 text-[#1f6b4a]">
                                    <div class="font-black text-[#1f6b4a] mb-1.5 flex items-center gap-1.5"><i class="fas fa-flask"></i> Laboratory Investigations (${labs.length})</div>
                                    ${labs.length > 0 ? labs.map(l => `<div class="py-0.5 text-[#1f6b4a] font-semibold">• ${l.data ? (l.data.name || l.data.test_name) : (l.test_name || l.name || (typeof l === 'string' ? l : 'Investigation'))}</div>`).join('') : '<div class="text-[#1f6b4a]/60 italic font-medium">No lab tests ordered</div>'}
                                </div>

                                <!-- Pharmacy -->
                                <div class="bg-[#f3efe6] border border-[#1f6b4a] rounded-lg p-3 text-[#1f6b4a]">
                                    <div class="font-black text-[#1f6b4a] mb-1.5 flex items-center gap-1.5"><i class="fas fa-pills"></i> Pharmacy Orders (${rx.length})</div>
                                    ${rx.length > 0 ? rx.map(p => `<div class="py-0.5 text-[#1f6b4a] font-semibold">• ${p.medicine_name || p.name || (typeof p === 'string' ? p : 'Medication')}</div>`).join('') : '<div class="text-[#1f6b4a]/60 italic font-medium">No medications ordered</div>'}
                                </div>

                                <!-- Consultant Visits -->
                                <div class="bg-[#f3efe6] border border-[#1f6b4a] rounded-lg p-3 text-[#1f6b4a]">
                                    <div class="font-black text-[#1f6b4a] mb-1.5 flex items-center gap-1.5"><i class="fas fa-user-md"></i> Consultant Visits (${visits.length})</div>
                                    ${visits.length > 0 ? visits.map(v => `<div class="py-0.5 text-[#1f6b4a] font-semibold">• ${v.doctor_name || v.name || (typeof v === 'string' ? v : 'Consultation')}</div>`).join('') : '<div class="text-[#1f6b4a]/60 italic font-medium">Routine rounds recorded</div>'}
                                </div>

                                <!-- Nursing Notes -->
                                <div class="bg-[#f3efe6] border border-[#1f6b4a] rounded-lg p-3 text-[#1f6b4a]">
                                    <div class="font-black text-[#1f6b4a] mb-1.5 flex items-center gap-1.5"><i class="fas fa-notes-medical"></i> Nursing &amp; Ward Notes</div>
                                    <div class="text-[#1f6b4a] font-semibold">${rec.nursing_notes ? (typeof rec.nursing_notes === 'string' ? rec.nursing_notes : JSON.stringify(rec.nursing_notes)) : '<span class="text-[#1f6b4a]/60 italic font-medium">Patient stable, vitals monitored</span>'}</div>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
            }
        }

        // ============================================================
        // 3. OPD VISITS DRILLDOWN MODAL CONTROLLER
        // ============================================================
        let cachedOpdAppointments = [];
        let currentOpdFilter = 'all'; // 'all', 'Approved', 'Pending', 'Cancelled'
        let currentOpdDateScope = 'today'; // 'today', 'all', 'custom'
        let currentOpdDate = null; // 'YYYY-MM-DD' or null

        async function openOpdDrilldown() {
            const modal = document.getElementById('opdDrilldownModal');
            if (!modal) return;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            const tbody = document.getElementById('opdModalTableBody');
            tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-400"><i class="fas fa-spinner fa-spin text-teal-600 text-xl mb-2"></i><br>Loading OPD queue...</td></tr>';

            try {
                const response = await fetch('/GM_HMS/api/admin/opd-details');
                const opdResult = await response.json();

                if (opdResult.success && opdResult.data) {
                    cachedOpdAppointments = opdResult.data.appointments || [];
                    // Default to today view
                    currentOpdDateScope = 'today';
                    currentOpdDate = null;
                    const dateInput = document.getElementById('opdDateInput');
                    if (dateInput) dateInput.value = '';
                    
                    const todayBtn = document.getElementById('opdScopeTodayBtn');
                    const allBtn = document.getElementById('opdScopeAllBtn');
                    if (todayBtn) todayBtn.className = 'opd-scope-btn px-2.5 py-1 rounded-lg bg-white text-teal-800 shadow-sm font-bold transition-all';
                    if (allBtn) allBtn.className = 'opd-scope-btn px-2.5 py-1 rounded-lg text-gray-600 hover:text-gray-900 transition-all';

                    renderOpdTable();
                } else {
                    tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-red-500">Failed to load OPD appointments</td></tr>';
                }
            } catch (err) {
                console.error('Error fetching OPD data:', err);
                tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-red-500">Error loading OPD appointments</td></tr>';
            }
        }

        function closeOpdDrilldown() {
            const modal = document.getElementById('opdDrilldownModal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
        }

        function setOpdDateScope(scope, btn) {
            currentOpdDateScope = scope;
            currentOpdDate = null;
            const dateInput = document.getElementById('opdDateInput');
            if (dateInput) dateInput.value = '';

            document.querySelectorAll('.opd-scope-btn').forEach(b => {
                b.className = 'opd-scope-btn px-2.5 py-1 rounded-lg text-gray-600 hover:text-gray-900 transition-all';
            });
            if (btn) {
                btn.className = 'opd-scope-btn px-2.5 py-1 rounded-lg bg-white text-teal-800 shadow-sm font-bold transition-all';
            }
            renderOpdTable();
        }

        function onOpdDateSelect(dateVal) {
            if (dateVal) {
                currentOpdDate = dateVal.trim();
                currentOpdDateScope = 'custom';
                document.querySelectorAll('.opd-scope-btn').forEach(b => {
                    b.className = 'opd-scope-btn px-2.5 py-1 rounded-lg text-gray-600 hover:text-gray-900 transition-all';
                });
            } else {
                currentOpdDate = null;
                currentOpdDateScope = 'all';
            }
            renderOpdTable();
        }

        function clearOpdDateFilter() {
            const dateInput = document.getElementById('opdDateInput');
            if (dateInput) dateInput.value = '';
            currentOpdDate = null;
            currentOpdDateScope = 'all';
            const allBtn = document.getElementById('opdScopeAllBtn');
            if (allBtn) setOpdDateScope('all', allBtn);
            else renderOpdTable();
        }

        function setOpdFilter(filter, btn) {
            currentOpdFilter = filter;
            document.querySelectorAll('.opd-filter-btn').forEach(b => {
                b.className = 'opd-filter-btn px-2.5 py-1 rounded-lg text-gray-600 hover:text-gray-900 transition-all';
            });
            if (btn) {
                btn.className = 'opd-filter-btn px-2.5 py-1 rounded-lg bg-white text-teal-800 shadow-sm font-bold transition-all';
            }
            renderOpdTable();
        }

        function filterOpdTable() {
            renderOpdTable();
        }

        function renderOpdTable() {
            const tbody = document.getElementById('opdModalTableBody');
            const search = (document.getElementById('opdSearchInput')?.value || '').toLowerCase().trim();
            const todayStr = new Date().toISOString().slice(0, 10);

            // Compute date badge text
            const dateBadge = document.getElementById('opdDateRangeBadge');
            if (dateBadge) {
                if (currentOpdDateScope === 'today') dateBadge.textContent = 'Today';
                else if (currentOpdDateScope === 'all') dateBadge.textContent = 'All Dates';
                else if (currentOpdDate) {
                    const d = new Date(currentOpdDate);
                    dateBadge.textContent = d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
                }
            }

            // Step 1: Filter by date scope
            let dateFilteredList = cachedOpdAppointments.filter(apt => {
                if (apt.appointment_id && String(apt.appointment_id).startsWith('NOAPT-') && !apt.appointment_date) {
                    return false;
                }
                const aptDate = apt.appointment_date ? String(apt.appointment_date).slice(0, 10) : '';
                if (currentOpdDateScope === 'today' && aptDate !== todayStr) return false;
                if (currentOpdDateScope === 'custom' && currentOpdDate && aptDate !== currentOpdDate) return false;
                return true;
            });

            // Update top 4 summary metric counters
            let countTotal = dateFilteredList.length;
            let countApproved = 0;
            let countPending = 0;
            let countCancelled = 0;

            dateFilteredList.forEach(apt => {
                const s = String(apt.appointment_status || 'Pending').toLowerCase();
                if (s === 'approved' || s === '1') countApproved++;
                else if (s === 'cancelled' || s === '2') countCancelled++;
                else countPending++;
            });

            document.getElementById('opdModalToday').textContent = `${countTotal} Visits`;
            document.getElementById('opdModalApproved').textContent = `${countApproved}`;
            document.getElementById('opdModalPending').textContent = `${countPending}`;
            document.getElementById('opdModalCancelled').textContent = `${countCancelled}`;

            // Step 2: Filter by status & search
            let list = dateFilteredList.filter(apt => {
                if (currentOpdFilter !== 'all') {
                    const status = String(apt.appointment_status || 'Pending').toLowerCase();
                    const target = currentOpdFilter.toLowerCase();
                    if (!status.includes(target)) return false;
                }

                if (search) {
                    const aptDate = apt.appointment_date ? String(apt.appointment_date).slice(0, 10) : '';
                    let aptDateFormatted = '';
                    try {
                        if (apt.appointment_date) {
                            const d = new Date(apt.appointment_date);
                            aptDateFormatted = `${d.getDate()}/${d.getMonth()+1}/${d.getFullYear()} ${d.toLocaleDateString('en-GB')}`;
                        }
                    } catch(e) {}

                    const searchClean = search.replace(/[-\/\s]/g, '');
                    const haystack = `${apt.appointment_id || ''} ${apt.patient_name || ''} ${apt.patient_id || ''} ${apt.doctor_name || ''} ${apt.specialization || ''} ${apt.token_number || ''} ${aptDate} ${aptDateFormatted} ${apt.appointment_status || ''} ${apt.payment_status || ''}`.toLowerCase();
                    const haystackClean = haystack.replace(/[-\/\s]/g, '');
                    return haystack.includes(search) || (searchClean.length >= 4 && haystackClean.includes(searchClean));
                }
                return true;
            });

            if (list.length === 0) {
                if (currentOpdDateScope === 'today') {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center">
                                <div class="w-12 h-12 rounded-full bg-teal-50 text-teal-600 flex items-center justify-center mx-auto mb-2 text-lg">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <p class="text-sm font-semibold text-gray-700">No OPD appointments scheduled for today</p>
                                <p class="text-xs text-gray-400 mt-1">Switch to <button onclick="setOpdDateScope('all', document.getElementById('opdScopeAllBtn'))" class="text-teal-700 font-bold underline hover:text-teal-900">All Dates</button> or select a date above to review past visits.</p>
                            </td>
                        </tr>
                    `;
                } else {
                    tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No matching OPD appointments found for selected filters</td></tr>';
                }
                return;
            }

            tbody.innerHTML = list.map((apt, index) => {
                const token = apt.token_number || (index + 1);
                const fee = apt.consultation_fee || apt.total_amount || 0;
                const formattedDate = apt.appointment_date ? new Date(apt.appointment_date).toLocaleDateString('en-GB') : 'Today';

                return `
                    <tr class="bg-white hover:bg-gray-50/80 transition-colors">
                        <td class="px-4 py-3 text-center">
                            <span class="bg-teal-100 text-teal-800 font-mono font-bold text-xs px-2.5 py-1 rounded-lg border border-teal-200">
                                #${token}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-semibold text-gray-900">
                            <div>${apt.patient_name || 'Patient'}</div>
                            <div class="text-[10px] text-gray-400 font-mono">${apt.patient_id || ''}</div>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-600">${apt.patient_phone || apt.appointment_phone || '-'}</td>
                        <td class="px-4 py-3 text-xs text-gray-700">
                            <div class="font-medium">Dr. ${apt.doctor_name || 'Assigned Doctor'}</div>
                            <div class="text-[10px] text-gray-400">${apt.specialization || 'OPD'}</div>
                        </td>
                        <td class="px-4 py-3 text-xs font-medium text-gray-700">
                            <div>${apt.appointment_time || 'General'}</div>
                            <div class="text-[10px] text-gray-400">${formattedDate}</div>
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-xs text-gray-900">₹${parseFloat(fee).toLocaleString('en-IN')}</td>
                    </tr>
                `;
            }).join('');
        }

        // ============================================================
        // 4. REVENUE DRILLDOWN MODAL CONTROLLER
        // ============================================================
        let cachedRevenueTransactions = [];
        let cachedDailyRevenue = [];
        let cachedRevenueSummary = {};
        let currentRevenueFilter = 'all'; // 'all', 'OPD', 'IPD'
        let currentRevenueDate = null;    // 'YYYY-MM-DD' or null
        let currentRevenueTab = 'daily';  // 'daily', 'ledger'

        async function openRevenueDrilldown() {
            const modal = document.getElementById('revenueDrilldownModal');
            if (!modal) return;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            const dailyTbody = document.getElementById('revenueDailyTableBody');
            const ledgerTbody = document.getElementById('revenueModalTableBody');
            if (dailyTbody) dailyTbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-400"><i class="fas fa-spinner fa-spin text-emerald-600 text-xl mb-2"></i><br>Loading daily revenue matrix...</td></tr>';
            if (ledgerTbody) ledgerTbody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-gray-400"><i class="fas fa-spinner fa-spin text-emerald-600 text-xl mb-2"></i><br>Loading financial transactions...</td></tr>';

            try {
                const response = await fetch('/GM_HMS/api/admin/revenue-details');
                const result = await response.json();

                if (result.success && result.data) {
                    const { summary, daily_breakdown, transactions, payment_modes } = result.data;
                    cachedRevenueTransactions = transactions || [];
                    cachedDailyRevenue = daily_breakdown || [];
                    cachedRevenueSummary = summary || {};

                    // Render both views and update matrix cards
                    renderRevenueDailyTable();
                    renderRevenueLedgerTable();
                    updateRevenueCards();
                } else {
                    if (dailyTbody) dailyTbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-red-500">Failed to load revenue data</td></tr>';
                    if (ledgerTbody) ledgerTbody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-red-500">Failed to load financial ledger</td></tr>';
                }
            } catch (err) {
                console.error('Error fetching revenue ledger:', err);
                if (dailyTbody) dailyTbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-red-500">Error loading revenue data</td></tr>';
                if (ledgerTbody) ledgerTbody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-red-500">Error loading revenue ledger</td></tr>';
            }
        }

        function closeRevenueDrilldown() {
            const modal = document.getElementById('revenueDrilldownModal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
        }

        function switchRevenueTab(tab, btn) {
            currentRevenueTab = tab;
            const dailyContainer = document.getElementById('revenueDailyViewContainer');
            const ledgerContainer = document.getElementById('revenueLedgerViewContainer');
            const tabDailyBtn = document.getElementById('revTabDaily');
            const tabLedgerBtn = document.getElementById('revTabLedger');

            if (tab === 'daily') {
                dailyContainer?.classList.remove('hidden');
                ledgerContainer?.classList.add('hidden');
                if (tabDailyBtn) tabDailyBtn.className = 'rev-tab-btn px-3 py-1 rounded-lg bg-white text-emerald-900 shadow-sm font-bold flex items-center gap-1.5 transition-all';
                if (tabLedgerBtn) tabLedgerBtn.className = 'rev-tab-btn px-3 py-1 rounded-lg text-gray-600 hover:text-gray-900 flex items-center gap-1.5 transition-all';
            } else {
                dailyContainer?.classList.add('hidden');
                ledgerContainer?.classList.remove('hidden');
                if (tabLedgerBtn) tabLedgerBtn.className = 'rev-tab-btn px-3 py-1 rounded-lg bg-white text-emerald-900 shadow-sm font-bold flex items-center gap-1.5 transition-all';
                if (tabDailyBtn) tabDailyBtn.className = 'rev-tab-btn px-3 py-1 rounded-lg text-gray-600 hover:text-gray-900 flex items-center gap-1.5 transition-all';
            }
        }

        function onRevenueDateSelect(dateVal) {
            currentRevenueDate = dateVal ? dateVal.trim() : null;
            renderRevenueDailyTable();
            renderRevenueLedgerTable();
            updateRevenueCards();
        }

        function clearRevenueDateFilter() {
            const dateInput = document.getElementById('revenueDateInput');
            if (dateInput) dateInput.value = '';
            currentRevenueDate = null;
            renderRevenueDailyTable();
            renderRevenueLedgerTable();
            updateRevenueCards();
        }

        function setRevenueFilter(filter, btn) {
            currentRevenueFilter = filter;
            document.querySelectorAll('.revenue-filter-btn').forEach(b => {
                b.className = 'revenue-filter-btn px-2.5 py-1 rounded-lg text-gray-600 hover:text-gray-900 transition-all';
            });
            if (btn) {
                btn.className = 'revenue-filter-btn px-2.5 py-1 rounded-lg bg-white text-emerald-800 shadow-sm font-bold transition-all';
            }
            renderRevenueDailyTable();
            renderRevenueLedgerTable();
            updateRevenueCards();
        }

        function filterRevenueTable() {
            renderRevenueDailyTable();
            renderRevenueLedgerTable();
            updateRevenueCards();
        }

        function selectSpecificRevenueDate(dateStr) {
            const dateInput = document.getElementById('revenueDateInput');
            if (dateInput) dateInput.value = dateStr;
            currentRevenueDate = dateStr;
            // Switch to ledger view for quick inspection of that date
            switchRevenueTab('ledger');
            renderRevenueDailyTable();
            renderRevenueLedgerTable();
            updateRevenueCards();
        }

        // Dynamically compute and update the 4 top financial matrix cards based on active filters
        function updateRevenueCards() {
            const search = (document.getElementById('revenueSearchInput')?.value || '').toLowerCase().trim();

            let filteredList = cachedRevenueTransactions.filter(tx => {
                // Date filter
                if (currentRevenueDate && tx.transaction_date !== currentRevenueDate) return false;

                // Stream filter
                if (currentRevenueFilter === 'OPD' && tx.stream !== 'OPD') return false;
                if (currentRevenueFilter === 'IPD' && tx.stream !== 'IPD') return false;

                // Search query
                if (search) {
                    const searchClean = search.replace(/[-\/\s]/g, '');
                    const haystack = `${tx.transaction_id || ''} ${tx.patient_name || ''} ${tx.patient_id || ''} ${tx.category || ''} ${tx.doctor_name || ''} ${tx.payment_mode || ''} ${tx.formatted_date || ''} ${tx.transaction_date || ''}`.toLowerCase();
                    const haystackClean = haystack.replace(/[-\/\s]/g, '');
                    return haystack.includes(search) || (searchClean.length >= 4 && haystackClean.includes(searchClean));
                }
                return true;
            });

            let totalFiltered = 0;
            let opdFiltered = 0;
            let ipdFiltered = 0;
            let modes = { cash: 0, upi: 0, card: 0, insurance: 0 };

            filteredList.forEach(tx => {
                const amt = parseFloat(tx.amount_paid || 0);
                totalFiltered += amt;
                if (tx.stream === 'OPD') opdFiltered += amt;
                else ipdFiltered += amt;

                const pm = (tx.payment_mode || 'Cash').toLowerCase();
                if (pm.includes('cash')) modes.cash += amt;
                else if (pm.includes('upi') || pm.includes('online')) modes.upi += amt;
                else if (pm.includes('card')) modes.card += amt;
                else if (pm.includes('insur')) modes.insurance += amt;
                else modes.cash += amt;
            });

            const opdPct = totalFiltered > 0 ? Math.round((opdFiltered / totalFiltered) * 100) : 0;
            const ipdPct = totalFiltered > 0 ? Math.round((ipdFiltered / totalFiltered) * 100) : 0;

            // Date Badge label
            const dateBadge = document.getElementById('revFilteredDateBadge');
            if (dateBadge) {
                if (currentRevenueDate) {
                    const d = new Date(currentRevenueDate);
                    dateBadge.textContent = d.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });
                } else if (search) {
                    dateBadge.textContent = 'Searched';
                } else {
                    dateBadge.textContent = 'All Time';
                }
            }

            document.getElementById('revModalTotalFiltered').textContent = '₹' + totalFiltered.toLocaleString('en-IN', { maximumFractionDigits: 2 });
            document.getElementById('revModalTxnCount').textContent = filteredList.length;

            document.getElementById('revModalOpdTotal').textContent = '₹' + opdFiltered.toLocaleString('en-IN', { maximumFractionDigits: 2 });
            document.getElementById('revModalOpdShare').textContent = `${opdPct}%`;

            document.getElementById('revModalIpdTotal').textContent = '₹' + ipdFiltered.toLocaleString('en-IN', { maximumFractionDigits: 2 });
            document.getElementById('revModalIpdShare').textContent = `${ipdPct}%`;

            document.getElementById('pmCash').textContent = '₹' + modes.cash.toLocaleString('en-IN', { maximumFractionDigits: 2 });
            document.getElementById('pmUpi').textContent = '₹' + modes.upi.toLocaleString('en-IN', { maximumFractionDigits: 2 });
            document.getElementById('pmCard').textContent = '₹' + modes.card.toLocaleString('en-IN', { maximumFractionDigits: 2 });
            document.getElementById('pmInsurance').textContent = '₹' + modes.insurance.toLocaleString('en-IN', { maximumFractionDigits: 2 });
        }

        // Render Tab 1: Day-by-Day OPD vs IPD Table
        function renderRevenueDailyTable() {
            const tbody = document.getElementById('revenueDailyTableBody');
            if (!tbody) return;

            const search = (document.getElementById('revenueSearchInput')?.value || '').toLowerCase().trim();

            let list = cachedDailyRevenue.filter(row => {
                if (currentRevenueDate && row.date !== currentRevenueDate) return false;
                if (search) {
                    const searchClean = search.replace(/[-\/\s]/g, '');
                    const haystack = `${row.date} ${row.formatted_date} ${row.day_name}`.toLowerCase();
                    const haystackClean = haystack.replace(/[-\/\s]/g, '');
                    return haystack.includes(search) || (searchClean.length >= 4 && haystackClean.includes(searchClean));
                }
                return true;
            });

            if (list.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No daily revenue records found for selected criteria</td></tr>';
                return;
            }

            tbody.innerHTML = list.map(row => {
                const opdAmt = parseFloat(row.opd_amount || 0);
                const ipdAmt = parseFloat(row.ipd_amount || 0);
                const totalAmt = parseFloat(row.total_amount || 0);

                return `
                    <tr class="bg-white hover:bg-gray-50/80 transition-colors">
                        <td class="px-4 py-3.5">
                            <div class="font-bold text-gray-900">${row.formatted_date}</div>
                            <div class="text-[11px] text-gray-400 font-medium">${row.day_name}</div>
                        </td>
                        <td class="px-4 py-3.5 text-right font-bold text-teal-700">
                            ₹${opdAmt.toLocaleString('en-IN', { maximumFractionDigits: 2 })}
                        </td>
                        <td class="px-4 py-3.5 text-right font-bold text-purple-900">
                            ₹${ipdAmt.toLocaleString('en-IN', { maximumFractionDigits: 2 })}
                        </td>
                        <td class="px-4 py-3.5 text-right font-black text-emerald-800 text-sm">
                            ₹${totalAmt.toLocaleString('en-IN', { maximumFractionDigits: 2 })}
                        </td>
                        <td class="px-4 py-3.5 text-center">
                            <span class="bg-gray-100 text-gray-700 text-xs font-semibold px-2.5 py-1 rounded-full">
                                ${row.tx_count} bills
                            </span>
                        </td>
                        <td class="px-4 py-3.5 text-right">
                            <button onclick="selectSpecificRevenueDate('${row.date}')" class="px-2.5 py-1 text-xs bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-semibold rounded-lg border border-emerald-200 transition-colors inline-flex items-center gap-1 shadow-xs">
                                <span>Inspect</span>
                                <i class="fas fa-arrow-right text-[10px]"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Render Tab 2: Itemized Invoices & Receipts Ledger Table
        function renderRevenueLedgerTable() {
            const tbody = document.getElementById('revenueModalTableBody');
            if (!tbody) return;

            const search = (document.getElementById('revenueSearchInput')?.value || '').toLowerCase().trim();

            let list = cachedRevenueTransactions.filter(tx => {
                if (currentRevenueDate && tx.transaction_date !== currentRevenueDate) return false;
                if (currentRevenueFilter === 'OPD' && tx.stream !== 'OPD') return false;
                if (currentRevenueFilter === 'IPD' && tx.stream !== 'IPD') return false;

                if (search) {
                    const searchClean = search.replace(/[-\/\s]/g, '');
                    const haystack = `${tx.transaction_id || ''} ${tx.patient_name || ''} ${tx.patient_id || ''} ${tx.category || ''} ${tx.doctor_name || ''} ${tx.payment_mode || ''} ${tx.formatted_date || ''} ${tx.transaction_date || ''}`.toLowerCase();
                    const haystackClean = haystack.replace(/[-\/\s]/g, '');
                    return haystack.includes(search) || (searchClean.length >= 4 && haystackClean.includes(searchClean));
                }
                return true;
            });

            if (list.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No matching revenue transactions found</td></tr>';
                return;
            }

            tbody.innerHTML = list.map(tx => {
                const isIpd = tx.stream === 'IPD';
                const streamBadge = isIpd
                    ? `<span class="bg-purple-100 text-purple-800 text-xs font-bold px-2 py-0.5 rounded border border-purple-200 flex items-center gap-1 w-fit mx-auto"><i class="fas fa-bed text-[10px]"></i> IPD</span>`
                    : `<span class="bg-teal-100 text-teal-800 text-xs font-bold px-2 py-0.5 rounded border border-teal-200 flex items-center gap-1 w-fit mx-auto"><i class="fas fa-stethoscope text-[10px]"></i> OPD</span>`;

                const mode = (tx.payment_mode || 'CASH').toUpperCase();
                let modeBadge = `<span class="bg-gray-100 text-gray-800 text-[11px] font-bold px-2 py-0.5 rounded">${mode}</span>`;
                if (mode.includes('CASH')) modeBadge = `<span class="bg-emerald-50 text-emerald-700 text-[11px] font-bold px-2 py-0.5 rounded border border-emerald-200">CASH</span>`;
                else if (mode.includes('UPI') || mode.includes('ONLINE')) modeBadge = `<span class="bg-blue-50 text-blue-700 text-[11px] font-bold px-2 py-0.5 rounded border border-blue-200">UPI</span>`;
                else if (mode.includes('CARD')) modeBadge = `<span class="bg-purple-50 text-purple-700 text-[11px] font-bold px-2 py-0.5 rounded border border-purple-200">CARD</span>`;
                else if (mode.includes('INSUR') || mode.includes('TPA')) modeBadge = `<span class="bg-amber-50 text-amber-700 text-[11px] font-bold px-2 py-0.5 rounded border border-amber-200">INSURANCE</span>`;

                const amtPaid = parseFloat(tx.amount_paid || 0);
                const balDue = parseFloat(tx.balance_due || 0);

                let billLinkHtml;
                if (isIpd && (tx.admission_id || tx.bill_id)) {
                    billLinkHtml = `<button onclick="openIpdPatientBilling('${tx.admission_id || tx.bill_id}', '${tx.patient_id || ''}')" class="font-mono font-bold text-xs text-emerald-700 hover:underline cursor-pointer">#${tx.transaction_id}</button>`;
                } else {
                    const billLink = isIpd
                        ? `ipd_billing.php`
                        : `opd_billing_entry.php?bill_id=${encodeURIComponent(tx.bill_id || tx.transaction_id)}`;
                    billLinkHtml = `<a href="${billLink}" class="font-mono font-bold text-xs text-emerald-700 hover:underline">#${tx.transaction_id}</a>`;
                }

                return `
                    <tr class="bg-white hover:bg-gray-50/80 transition-colors">
                        <td class="px-4 py-3">
                            ${billLinkHtml}
                            <div class="text-[10px] text-gray-400 font-medium">${tx.category || ''}</div>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-600">
                            <div>${tx.formatted_date || tx.transaction_date || '-'}</div>
                            <div class="text-[10px] text-gray-400">${tx.transaction_time || ''}</div>
                        </td>
                        <td class="px-4 py-3 font-semibold text-gray-900">
                            <div>${tx.patient_name || 'Patient'}</div>
                            <div class="text-[10px] text-gray-400 font-mono">${tx.patient_id || ''}</div>
                        </td>
                        <td class="px-4 py-3 text-center">${streamBadge}</td>
                        <td class="px-4 py-3 text-center">${modeBadge}</td>
                        <td class="px-4 py-3 text-right font-bold text-xs text-emerald-700">₹${amtPaid.toLocaleString('en-IN', { maximumFractionDigits: 2 })}</td>
                        <td class="px-4 py-3 text-right font-semibold text-xs ${balDue > 0 ? 'text-red-600' : 'text-gray-400'}">
                            ₹${balDue.toLocaleString('en-IN', { maximumFractionDigits: 2 })}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="bg-green-100 text-green-700 text-[10px] font-bold px-2 py-0.5 rounded-full border border-green-300 uppercase">
                                ${tx.payment_status || 'PAID'}
                            </span>
                        </td>
                    </tr>
                `;
            }).join('');
        }
    </script>
    
</body>
</html>
