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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - GM Hospital Management System</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Common Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin_common.css">
    
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            /* Handled by gm-theme.css */
        }
        
        .sidebar {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            transition: all 0.3s ease;
        }
        
        .sidebar-item {
            transition: all 0.3s ease;
        }
        
        .sidebar-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        
        .sidebar-item.active {
            background: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%);
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
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
            padding-left: 16px;
            margin-bottom: 16px;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        /* Hide scrollbars */
        ::-webkit-scrollbar {
            display: none !important;
        }
        
        ::-moz-scrollbar {
            display: none !important;
        }
        
        ::-ms-scrollbar {
            display: none !important;
        }
        
        scrollbar-width: none !important;
        -ms-overflow-style: none !important;
        
        /* Hide scrollbars for specific elements */
        * {
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
        }
        
        *::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }
    </style>
</head>
<body>
    
    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            
            <!-- Top Navbar -->
            <?php include 'includes/navbar.php'; ?>
            
            <!-- Dashboard Content -->
            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                
                <!-- Welcome Section -->
                <div class="mb-8">
                    <div class="rounded-xl shadow-sm border border-gray-200 p-4" style="background: #f3efe6;">
                        <h1 class="text-3xl font-bold mb-1" style="color: #1f6b4a;">Welcome back, <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'])[0]); ?>! 👋</h1>
                        <p class="text-gray-600">Here's what's happening in your hospital today</p>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Patients -->
                    <div class="stat-card">
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-8 h-8 gradient-bg-1 rounded-lg flex items-center justify-center">
                                <i class="fas fa-user-injured text-white text-sm"></i>
                            </div>
                            <span class="text-green-500 font-semibold" style="font-size: 15px;">+12%</span>
                        </div>
                        <h3 class="text-gray-600 mb-1" style="font-size: 15px;">Total Patients</h3>
                        <p id="totalPatients" class="font-bold text-gray-800" style="font-size: 18px;">Loading...</p>
                        <p id="patientsSecondary" class="text-gray-500 mt-1" style="font-size: 15px;">Today: ... | This Month: ...</p>
                    </div>
                    
                    <!-- Total Doctors -->
                    <div class="stat-card">
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-8 h-8 gradient-bg-2 rounded-lg flex items-center justify-center">
                                <i class="fas fa-user-md text-white text-sm"></i>
                            </div>
                            <span class="text-green-500 font-semibold" style="font-size: 15px;">+3</span>
                        </div>
                        <h3 class="text-gray-600 mb-1" style="font-size: 15px;">Total Doctors</h3>
                        <p id="totalDoctors" class="font-bold text-gray-800" style="font-size: 18px;">Loading...</p>
                        <p id="doctorsSecondary" class="text-gray-500 mt-1" style="font-size: 15px;">Available: ... | On Leave: ...</p>
                    </div>
                    
                    <!-- Appointments Today -->
                    <div class="stat-card">
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-8 h-8 gradient-bg-3 rounded-lg flex items-center justify-center">
                                <i class="fas fa-calendar-check text-white text-sm"></i>
                            </div>
                            <span id="appointmentsPending" class="text-orange-500 font-semibold" style="font-size: 15px;">... Pending</span>
                        </div>
                        <h3 class="text-gray-600 mb-1" style="font-size: 15px;">Appointments Today</h3>
                        <p id="appointmentsToday" class="font-bold text-gray-800" style="font-size: 18px;">Loading...</p>
                        <p id="appointmentsSecondary" class="text-gray-500 mt-1" style="font-size: 15px;">Approved: ... | Cancelled: ...</p>
                    </div>
                    
                    <!-- Revenue Today -->
                    <div class="stat-card">
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-8 h-8 gradient-bg-4 rounded-lg flex items-center justify-center">
                                <i class="fas fa-dollar-sign text-white text-sm"></i>
                            </div>
                            <span class="text-green-500 font-semibold" style="font-size: 15px;">+8%</span>
                        </div>
                        <h3 class="text-gray-600 mb-1" style="font-size: 15px;">Revenue Today</h3>
                        <p id="revenueToday" class="font-bold text-gray-800" style="font-size: 18px;">Loading...</p>
                        <p id="revenueSecondary" class="text-gray-500 mt-1" style="font-size: 15px;">This Month: ₹...</p>
                    </div>
                </div>
                
                <!-- Additional Stats Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Bed Availability -->
                    <div class="stat-card">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-gray-700 font-semibold">Bed Availability</h3>
                            <i class="fas fa-bed text-purple-500 text-xl"></i>
                        </div>
                        <div id="bedAvailabilityContainer" class="space-y-4 max-h-96 overflow-y-auto pr-2">
                            <p class="text-sm text-gray-500">Loading bed availability...</p>
                        </div>
                    </div>
                    
                    <!-- Department Summary -->
                    <div class="stat-card">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-gray-700 font-semibold">Active Departments</h3>
                            <i class="fas fa-building text-blue-500 text-xl"></i>
                        </div>
                        <div id="activeDepartmentsContainer" class="space-y-4 max-h-80 overflow-y-auto pr-2">
                            <p class="text-sm text-gray-500">Loading departments...</p>
                        </div>
                    </div>
                    
                    <!-- Operations Schedule -->
                    <div class="stat-card">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-gray-700 font-semibold">Operations Today</h3>
                            <i class="fas fa-procedures text-red-500 text-xl"></i>
                        </div>
                        <div class="space-y-3" id="operationsContainer">
                            <p class="text-sm text-gray-500">Loading operations...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Charts and Activity -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Patient Admissions Chart -->
                    <div class="lg:col-span-2 stat-card">
                        <h3 class="text-gray-700 font-semibold mb-4">Patient Admissions (Last 7 Days)</h3>
                        <div class="chart-container">
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
                
                <!-- Revenue Chart -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="stat-card">
                        <h3 class="text-gray-700 font-semibold mb-4">Revenue Overview (Monthly)</h3>
                        <div class="chart-container">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <h3 class="text-gray-700 font-semibold mb-4">Department Performance</h3>
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
                        <h3 class="text-gray-700 font-semibold mb-4">Upcoming Appointments</h3>
                        <div class="space-y-3" id="upcomingAppointmentsContainer">
                            <p class="text-sm text-gray-500">Loading appointments...</p>
                        </div>
                    </div>
                </div>
                
            </main>
            
        </div>
        
    </div>
    
    <!-- Reports Modal -->
    <div id="reportsModal" class="fixed inset-0 z-[100] hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true" onclick="toggleReportsModal()">
                <div class="absolute inset-0 bg-gray-900 opacity-75 backdrop-blur-sm"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-gray-100">
                <div class="relative bg-white">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                        <h3 class="text-xl font-bold text-gray-800">OPD Reports & Analytics</h3>
                        <button onclick="toggleReportsModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <!-- Content -->
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Daily Trend -->
                            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                                <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
                                    <h5 class="text-sm font-semibold text-gray-700">Daily OPD Trend (Last 7 Days)</h5>
                                </div>
                                <div class="p-4" id="report-daily-trend">
                                    <div class="h-48 flex items-center justify-center text-gray-400">Loading...</div>
                                </div>
                            </div>
                            
                            <!-- Revenue -->
                            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                                <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
                                    <h5 class="text-sm font-semibold text-gray-700">Revenue Overview (This Month)</h5>
                                </div>
                                <div class="p-4" id="report-revenue">
                                    <div class="h-48 flex items-center justify-center text-gray-400">Loading...</div>
                                </div>
                            </div>
                        </div>

                        <!-- Doctor Wise -->
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                            <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
                                <h5 class="text-sm font-semibold text-gray-700">Doctor-wise OPD Count (Today)</h5>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left text-gray-500">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3">Doctor Name</th>
                                            <th scope="col" class="px-6 py-3">Patient Count</th>
                                        </tr>
                                    </thead>
                                    <tbody id="report-doctor-wise">
                                        <tr><td colspan="2" class="px-6 py-4 text-center">Loading...</td></tr>
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
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true" onclick="toggleProfileModal()">
                <div class="absolute inset-0 bg-gray-900 opacity-75 backdrop-blur-sm"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100">
                <div class="relative">
                    <!-- Header Background -->
                    <div class="h-32 gradient-bg-1"></div>
                    
                    <!-- Avatar -->
                    <div class="absolute top-16 left-1/2 transform -translate-x-1/2">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name'] ?? 'User'); ?>&background=fff&color=667eea&size=128" 
                             class="w-32 h-32 rounded-full border-4 border-white shadow-lg bg-white">
                    </div>
                    
                    <!-- Content -->
                    <div class="pt-20 pb-8 px-8 text-center">
                        <h3 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Authenticated User'); ?></h3>
                        <p class="text-purple-600 font-medium mb-6"><?php echo htmlspecialchars($_SESSION['designation'] ?? 'Staff'); ?></p>
                        
                        <div class="space-y-4 text-left">
                            <div class="flex items-center p-3 bg-gray-50 rounded-xl">
                                <i class="fas fa-envelope w-8 text-gray-400"></i>
                                <div>
                                    <p class="text-xs text-gray-400 uppercase font-bold">Email Address</p>
                                    <p class="text-sm text-gray-700"><?php echo htmlspecialchars($_SESSION['email'] ?? 'Not Set'); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center p-3 bg-gray-50 rounded-xl">
                                <i class="fas fa-phone w-8 text-gray-400"></i>
                                <div>
                                    <p class="text-xs text-gray-400 uppercase font-bold">Mobile Number</p>
                                    <p class="text-sm text-gray-700"><?php echo htmlspecialchars($_SESSION['mobile_number'] ?? 'Not Set'); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center p-3 bg-gray-50 rounded-xl">
                                <i class="fas fa-id-badge w-8 text-gray-400"></i>
                                <div>
                                    <p class="text-xs text-gray-400 uppercase font-bold">User Identifier</p>
                                    <p class="text-sm text-gray-700"><?php echo htmlspecialchars($_SESSION['user_id'] ?? 'N/A'); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center p-3 bg-gray-50 rounded-xl">
                                <i class="fas fa-shield-alt w-8 text-gray-400"></i>
                                <div>
                                    <p class="text-xs text-gray-400 uppercase font-bold">Access Role</p>
                                    <p class="text-sm text-gray-700"><?php echo ucfirst(htmlspecialchars($_SESSION['role'] ?? 'user')); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center p-3 bg-gray-50 rounded-xl">
                                <i class="fas fa-check-circle w-8 text-gray-400"></i>
                                <div>
                                    <p class="text-xs text-gray-400 uppercase font-bold">Account Status</p>
                                    <p class="text-sm text-gray-700">
                                        <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-600 font-bold">
                                            <?php echo htmlspecialchars($_SESSION['status'] ?? 'Active'); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex space-x-3">
                            <button onclick="toggleProfileModal()" class="flex-1 py-3 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-colors">
                                Close
                            </button>
                            <a href="../logout.php" class="flex-1 py-3 px-4 gradient-bg-2 hover:opacity-90 text-white font-semibold rounded-xl text-center shadow-lg transition-transform hover:scale-[1.02]">
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
        
        // Chart instances
        let admissionsChart, revenueChart, departmentChart;

        // Initialize Charts with empty/placeholder data
        function initCharts() {
            // Admissions Chart
            const admissionsCtx = document.getElementById('admissionsChart').getContext('2d');
            admissionsChart = new Chart(admissionsCtx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'OPD',
                        data: [0, 0, 0, 0, 0, 0, 0],
                        borderColor: '#1f6b4a',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'IPD',
                        data: [0, 0, 0, 0, 0, 0, 0],
                        borderColor: '#1f6b4a',
                        backgroundColor: 'rgba(240, 147, 251, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    }
                }
            });
            
            // Revenue Chart
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            revenueChart = new Chart(revenueCtx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Revenue (₹)',
                        data: [0, 0, 0, 0, 0, 0],
                        backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₹' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
            
            // Department Chart
            const departmentCtx = document.getElementById('departmentChart').getContext('2d');
            departmentChart = new Chart(departmentCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Loading...'],
                    datasets: [{
                        data: [1],
                        backgroundColor: ['#e2e8f0']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
        }

        // Load Analytics Data (Charts)
        async function loadAnalyticsData() {
            try {
                const response = await fetch('/GM_HMS/api/admin/analytics');
                if (!response.ok) throw new Error('Failed to fetch analytics');
                
                const result = await response.json();
                if (result.success && result.data) {
                    const data = result.data;
                    
                    // Update Admissions Chart
                    admissionsChart.data.labels = data.admissions.labels;
                    admissionsChart.data.datasets[0].data = data.admissions.opd;
                    admissionsChart.data.datasets[1].data = data.admissions.ipd;
                    admissionsChart.update();
                    
                    // Update Revenue Chart
                    revenueChart.data.labels = data.revenue.labels;
                    revenueChart.data.datasets[0].data = data.revenue.values;
                    revenueChart.update();
                    
                    // Update Department Chart
                    if (data.departments.labels.length > 0) {
                        departmentChart.data.labels = data.departments.labels;
                        departmentChart.data.datasets[0].data = data.departments.values;
                        departmentChart.data.datasets[0].backgroundColor = [
                            '#1f6b4a', '#144d34', '#4facfe', '#43e97b', '#fa709a'
                        ];
                    } else {
                        departmentChart.data.labels = ['No Data'];
                        departmentChart.data.datasets[0].data = [1];
                        departmentChart.data.datasets[0].backgroundColor = ['#e2e8f0'];
                    }
                    departmentChart.update();
                }
            } catch (error) {
                console.error('Error loading analytics:', error);
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
                    document.getElementById('totalPatients').textContent = data.total_patients || 0;
                    document.getElementById('patientsSecondary').textContent = `Today: ${data.patients_today || 0} | This Month: ${data.patients_month || 0}`;
                    
                    // Update Total Doctors
                    document.getElementById('totalDoctors').textContent = data.total_doctors || 0;
                    document.getElementById('doctorsSecondary').textContent = `Available: ${data.doctors_available || 0} | On Leave: ${data.doctors_on_leave || 0}`;
                    
                    // Update Appointments Today
                    document.getElementById('appointmentsToday').textContent = data.appointments_today || 0;
                    document.getElementById('appointmentsPending').textContent = `${data.appointments_pending || 0} Pending`;
                    document.getElementById('appointmentsSecondary').textContent = `Approved: ${data.appointments_approved || 0} | Cancelled: ${data.appointments_cancelled || 0}`;
                    
                    // Update Revenue Today
                    const revenue = data.revenue_today ? parseFloat(data.revenue_today) : 0;
                    const revenueMonth = data.revenue_month ? parseFloat(data.revenue_month) : 0;
                    document.getElementById('revenueToday').textContent = 
                        '₹' + revenue.toLocaleString('en-IN', { maximumFractionDigits: 0 });
                    document.getElementById('revenueSecondary').textContent = 
                        'This Month: ₹' + revenueMonth.toLocaleString('en-IN', { maximumFractionDigits: 0 });
                        
                    // Update Operations Today
                    const opsContainer = document.getElementById('operationsContainer');
                    if (data.operations_today && data.operations_today.length > 0) {
                        opsContainer.innerHTML = data.operations_today.map((op, i) => {
                            const colors = ['green', 'blue', 'orange'];
                            const color = colors[i % colors.length];
                            return `
                            <div class="border-l-4 border-${color}-500 pl-3">
                                <p class="text-sm font-semibold text-gray-800">${op.name}</p>
                                <p class="text-xs text-gray-500">${op.type} - Created at ${op.created_at}</p>
                            </div>
                            `;
                        }).join('');
                    } else {
                        opsContainer.innerHTML = '<p class="text-sm text-gray-500">No operations today</p>';
                    }
                    
                    // Update Recent Activity
                    const activityContainer = document.getElementById('recentActivityContainer');
                    if (data.recent_activity && data.recent_activity.length > 0) {
                        activityContainer.innerHTML = data.recent_activity.map(act => `
                            <div class="activity-item">
                                <p class="text-sm font-semibold text-gray-800">${act.action}</p>
                                <p class="text-xs text-gray-500">${act.entity_type} #${act.entity_id}</p>
                                <p class="text-xs text-gray-400">${act.created_at}</p>
                            </div>
                        `).join('');
                    } else {
                        activityContainer.innerHTML = '<p class="text-sm text-gray-500">No recent activity</p>';
                    }
                    
                    // Update System Alerts
                    const alertsContainer = document.getElementById('systemAlertsContainer');
                    if (data.system_alerts && data.system_alerts.length > 0) {
                        alertsContainer.innerHTML = data.system_alerts.map(alert => `
                            <div class="flex items-start p-3 bg-red-50 border-l-4 border-red-500 rounded">
                                <i class="fas fa-exclamation-circle text-red-500 mt-1 mr-3"></i>
                                <div>
                                    <p class="text-sm font-semibold text-red-800">Low Stock Alert</p>
                                    <p class="text-xs text-red-600">${alert.product_name} - Only ${alert.quantity} units left (Min: ${alert.min_stock})</p>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        alertsContainer.innerHTML = '<p class="text-sm text-gray-500">No system alerts at this time.</p>';
                    }
                    
                    // Update Upcoming Appointments
                    const appointmentsContainer = document.getElementById('upcomingAppointmentsContainer');
                    if (data.upcoming_appointments && data.upcoming_appointments.length > 0) {
                        appointmentsContainer.innerHTML = data.upcoming_appointments.map((apt, i) => {
                            const colors = ['purple', 'blue', 'green', 'orange', 'red'];
                            const color = colors[i % colors.length];
                            return `
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-${color}-100 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-user text-${color}-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">${apt.patient_name}</p>
                                        <p class="text-xs text-gray-500">Dr. ${apt.doctor_name} - ${apt.specialization}</p>
                                    </div>
                                </div>
                                <span class="text-xs font-semibold text-${color}-600">${apt.time_formatted}</span>
                            </div>
                            `;
                        }).join('');
                    } else {
                        appointmentsContainer.innerHTML = '<p class="text-sm text-gray-500">No upcoming appointments today.</p>';
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
            document.getElementById('totalDoctors').textContent = 'Error';
            document.getElementById('appointmentsToday').textContent = 'Error';
            document.getElementById('revenueToday').textContent = 'Error';
        }
        
        // Load Bed Availability Statistics
        async function loadBedAvailability() {
            try {
                const response = await fetch('/GM_HMS/api/admin/bed-availability');
                
                if (!response.ok) {
                    throw new Error('Failed to fetch bed availability');
                }
                
                const result = await response.json();
                
                if (result.success && result.data && result.data.bed_stats) {
                    const bedStats = result.data.bed_stats;
                    const container = document.getElementById('bedAvailabilityContainer');
                    
                    if (bedStats.length === 0) {
                        container.innerHTML = '<p class="text-sm text-gray-500">No bed data available</p>';
                        return;
                    }
                    
                    // Build HTML for bed stats
                    let html = '';
                    bedStats.forEach(stat => {
                        const percentage = stat.occupancy_percentage;
                        let barColor = 'bg-green-500';
                        if (percentage > 70) barColor = 'bg-red-500';
                        else if (percentage > 50) barColor = 'bg-orange-500';
                        
                        html += `
                            <div class="border-b border-gray-100 pb-3 last:border-0 last:pb-0">
                                <div class="flex justify-between items-start mb-1">
                                    <div>
                                        <p class="text-xs font-bold text-gray-800">${stat.ward_name} - ${stat.room_name}</p>
                                        <p class="text-[10px] text-gray-500">${stat.ward_type} | ${stat.room_category}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs font-semibold text-gray-800">${stat.occupied_beds}/${stat.total_beds}</span>
                                        <p class="text-[10px] text-gray-400">Avl: ${stat.available_beds}</p>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                                    <div class="${barColor} h-1.5 rounded-full" style="width: ${percentage}%"></div>
                                </div>
                            </div>
                        `;
                    });
                    
                    container.innerHTML = html;
                } else {
                    document.getElementById('bedAvailabilityContainer').innerHTML = 
                        '<p class="text-sm text-gray-500">Error loading bed data</p>';
                }
            } catch (error) {
                console.error('Error loading bed availability:', error);
                document.getElementById('bedAvailabilityContainer').innerHTML = 
                    '<p class="text-sm text-gray-500">Error loading bed data</p>';
            }
        }
        
        // Load Active Departments Statistics
        async function loadActiveDepartments() {
            try {
                const response = await fetch('/GM_HMS/api/admin/active-departments');
                
                if (!response.ok) {
                    throw new Error('Failed to fetch departments');
                }
                
                const result = await response.json();
                
                if (result.success && result.data && result.data.department_stats) {
                    const deptStats = result.data.department_stats;
                    const container = document.getElementById('activeDepartmentsContainer');
                    
                    if (deptStats.length === 0) {
                        container.innerHTML = '<p class="text-sm text-gray-500">No active departments found</p>';
                        return;
                    }
                    
                    let html = '';
                    deptStats.forEach(stat => {
                        const isEmergency = stat.department_name.toLowerCase().includes('emergency');
                        const statusBadge = isEmergency 
                            ? `<span class="px-2 py-1 bg-red-100 text-red-600 rounded text-[10px] font-medium">24/7 Active</span>`
                            : `<span class="px-2 py-1 bg-green-100 text-green-600 rounded text-[10px] font-medium">${stat.doctor_count} Doctors</span>`;
                        
                        html += `
                            <div class="flex justify-between items-center pb-2 border-b border-gray-50 last:border-0 last:pb-0">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-gray-700">${stat.department_name}</span>
                                    <span class="text-[10px] text-gray-400 font-medium tracking-wide uppercase">${stat.department_type}</span>
                                </div>
                                ${statusBadge}
                            </div>
                        `;
                    });
                    
                    container.innerHTML = html;
                } else {
                    document.getElementById('activeDepartmentsContainer').innerHTML = 
                        '<p class="text-sm text-gray-500">Error loading departments</p>';
                }
            } catch (error) {
                console.error('Error loading departments:', error);
                document.getElementById('activeDepartmentsContainer').innerHTML = 
                    '<p class="text-sm text-gray-500">Error loading departments</p>';
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
                    // Using fallback/mock data if API fails (just providing visual feedback for now)
                    document.getElementById('report-doctor-wise').innerHTML = '<tr><td colspan="2" class="px-6 py-4 text-center text-red-500">Failed to load data</td></tr>';
                    return;
                }

                const data = result.data;
                reportsLoaded = true;

                // 1. Doctor Wise Table
                const doctorTbody = document.getElementById('report-doctor-wise');
                if (data.doctor_wise && data.doctor_wise.length > 0) {
                    doctorTbody.innerHTML = data.doctor_wise.map(d => `
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">${d.full_name || 'Unknown'}</td>
                            <td class="px-6 py-4">
                                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded border border-blue-400">
                                    ${d.count} Patients
                                </span>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    doctorTbody.innerHTML = '<tr><td colspan="2" class="px-6 py-4 text-center">No appointments today</td></tr>';
                }

                // 2. Revenue (Simple Display for now)
                const revenueContainer = document.getElementById('report-revenue');
                const revenueTotal = parseFloat(data.revenue?.total || 0).toLocaleString('en-IN');
                const revenueCount = data.revenue?.count || 0;
                
                revenueContainer.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-full">
                        <div class="text-4xl font-bold text-green-600 mb-2">₹${revenueTotal}</div>
                        <div class="text-sm text-gray-500">${revenueCount} Invoices Generate</div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5 mt-4">
                            <div class="bg-green-600 h-2.5 rounded-full" style="width: 70%"></div>
                        </div>
                    </div>
                `;

                // 3. Daily Trend (Simple Bar Chart using HTML/CSS)
                const trendContainer = document.getElementById('report-daily-trend');
                if (data.daily_trend && data.daily_trend.length > 0) {
                    const maxCount = Math.max(...data.daily_trend.map(d => d.count)) || 10; // Avoid divide by zero
                    
                    trendContainer.innerHTML = `
                        <div class="flex items-end justify-between h-40 space-x-2">
                             ${data.daily_trend.map(d => {
                                 const height = (d.count / maxCount) * 100;
                                 const date = new Date(d.date).toLocaleDateString('en-US', { weekday: 'short' });
                                 return `
                                    <div class="flex flex-col items-center flex-1 group">
                                        <div class="relative w-full bg-blue-100 hover:bg-blue-200 rounded-t transition-all duration-300" style="height: ${height}%">
                                             <div class="absolute -top-4 md:p-6 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs py-1 px-2 rounded opacity-0 group-hover:opacity-100 transition-opacity">
                                                ${d.count}
                                             </div>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-2">${date}</div>
                                    </div>
                                 `;
                             }).join('')}
                        </div>
                    `;
                } else {
                    trendContainer.innerHTML = '<div class="flex items-center justify-center h-full text-gray-500">No trend data available</div>';
                }

            } catch (error) {
                console.error("Fetch error:", error);
                 document.getElementById('report-doctor-wise').innerHTML = '<tr><td colspan="2" class="px-6 py-4 text-center text-red-500">System Error</td></tr>';
            }
        }
    </script>
    
</body>
</html>
