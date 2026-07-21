// ========================================
// RECEPTION PANEL JAVASCRIPT
// ========================================

class ReceptionPanel {
    constructor() {
        this.currentPage = 'dashboard';
        this.sidebarCollapsed = false;
        this.notifications = [];
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadPage('dashboard');
        this.initializeAPI();
        this.startPeriodicUpdates();
    }

    bindEvents() {
        // Sidebar navigation
        document.querySelectorAll('.nav-link[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = link.getAttribute('data-page');
                this.navigateToPage(page);
            });
        });

        // Sidebar toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => this.toggleSidebar());
        }

        // Logout
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleLogout();
            });
        }

        // Window resize
        window.addEventListener('resize', () => this.handleResize());
    }

    navigateToPage(page) {
        // Update active nav item
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        document.querySelector(`[data-page="${page}"]`).classList.add('active');

        // Update page title
        const titles = {
            'dashboard': 'Dashboard',
            'patient-registration': 'Patient Registration',
            'appointments': 'Appointments',
            'opd-management': 'OPD Management',
            'ipd-admission': 'IPD Admission',
            'billing': 'Billing',
            'doctor-availability': 'Doctor Availability',
            'profile': 'Profile'
        };

        document.getElementById('pageTitle').textContent = titles[page] || 'Dashboard';
        this.currentPage = page;
        this.loadPage(page);

        // Close mobile sidebar
        if (window.innerWidth <= 1024) {
            this.closeMobileSidebar();
        }
    }

    async loadPage(page) {
        const pageContent = document.getElementById('pageContent');
        this.showLoading();

        try {
            let content = '';

            switch (page) {
                case 'dashboard':
                    content = await this.loadDashboard();
                    break;
                case 'patient-registration':
                    content = await this.loadPatientRegistration();
                    break;
                case 'appointments':
                    content = await this.loadAppointments();
                    break;
                case 'opd-management':
                    content = await this.loadOPDManagement();
                    break;
                case 'ipd-admission':
                    content = await this.loadIPDAdmission();
                    break;
                case 'billing':
                    content = await this.loadBilling();
                    break;
                case 'doctor-availability':
                    content = await this.loadDoctorAvailability();
                    break;
                case 'profile':
                    content = await this.loadProfile();
                    break;
                default:
                    content = '<div class="text-center py-8"><h2 class="text-2xl font-bold text-gray-800">Page Not Found</h2></div>';
            }

            pageContent.innerHTML = content;
            this.initializePageComponents(page);
        } catch (error) {
            console.error('Error loading page:', error);
            pageContent.innerHTML = `
                <div class="text-center py-8">
                    <i class="fas fa-exclamation-triangle text-4xl text-red-500 mb-4"></i>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Error Loading Page</h2>
                    <p class="text-gray-600">Please try refreshing the page.</p>
                </div>
            `;
        } finally {
            this.hideLoading();
        }
    }

    async loadDashboard() {
        try {
            // Fetch dashboard data
            const [summaryData, appointmentsData, patientsData] = await Promise.all([
                this.apiCall('/controler/api/ReceptionController.php/api/dashboard/summary'),
                this.apiCall('/controler/api/ReceptionController.php/api/dashboard/today-appointments'),
                this.apiCall('/controler/api/ReceptionController.php/api/dashboard/recent-patients')
            ]);

            return `
                <div class="dashboard-page">
                    <!-- Welcome Banner -->
                    <div class="card" style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); color: white; margin-bottom: 2rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;">
                                    Welcome to Reception Dashboard! 👋
                                </h1>
                                <p style="font-size: 1rem; opacity: 0.9;">
                                    Today is ${new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}. 
                                    You have ${summaryData?.data?.appointments_scheduled || 0} appointments scheduled.
                                </p>
                            </div>
                            <div style="font-size: 4rem; opacity: 0.3;">
                                <i class="fas fa-hospital"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- KPI Cards Grid -->
                    <div class="d-grid grid-cols-4 gap-3 mb-4">
                        <!-- Today's Appointments -->
                        <div class="kpi-card card">
                            <div style="position: relative; z-index: 1;">
                                <div class="kpi-card-label">Today's Appointments</div>
                                <div class="kpi-card-value">${summaryData?.data?.appointments_scheduled || 0}</div>
                            </div>
                            <i class="fas fa-calendar-check kpi-card-icon"></i>
                        </div>
                        
                        <!-- Patients Registered Today -->
                        <div class="kpi-card card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                            <div style="position: relative; z-index: 1;">
                                <div class="kpi-card-label">Patients Today</div>
                                <div class="kpi-card-value">${summaryData?.data?.patients_today || 0}</div>
                            </div>
                            <i class="fas fa-users kpi-card-icon"></i>
                        </div>
                        
                        <!-- OPD Walk-ins -->
                        <div class="kpi-card card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                            <div style="position: relative; z-index: 1;">
                                <div class="kpi-card-label">OPD Walk-ins</div>
                                <div class="kpi-card-value">${summaryData?.data?.opd_walkins || 0}</div>
                            </div>
                            <i class="fas fa-stethoscope kpi-card-icon"></i>
                        </div>
                        
                        <!-- Pending Payments -->
                        <div class="kpi-card card" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                            <div style="position: relative; z-index: 1;">
                                <div class="kpi-card-label">Pending Payments</div>
                                <div class="kpi-card-value">${summaryData?.data?.pending_payments || 0}</div>
                            </div>
                            <i class="fas fa-file-invoice-dollar kpi-card-icon"></i>
                        </div>
                    </div>

                    <!-- Secondary KPI Cards -->
                    <div class="d-grid grid-cols-3 gap-3 mb-4">
                        <!-- Doctors Available -->
                        <div class="kpi-card card" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
                            <div style="position: relative; z-index: 1;">
                                <div class="kpi-card-label">Doctors Available</div>
                                <div class="kpi-card-value">${summaryData?.data?.doctors_available || 0}</div>
                            </div>
                            <i class="fas fa-user-md kpi-card-icon"></i>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-bolt"></i>
                                    Quick Actions
                                </h3>
                            </div>
                            <div class="card-body">
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <button class="btn btn-primary btn-sm" onclick="receptionPanel.navigateToPage('patient-registration')">
                                        <i class="fas fa-user-plus"></i>
                                        Register Patient
                                    </button>
                                    <button class="btn btn-success btn-sm" onclick="receptionPanel.navigateToPage('appointments')">
                                        <i class="fas fa-calendar-plus"></i>
                                        Book Appointment
                                    </button>
                                    <button class="btn btn-warning btn-sm" onclick="receptionPanel.navigateToPage('opd-management')">
                                        <i class="fas fa-ticket-alt"></i>
                                        Generate Token
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- System Status -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-heartbeat"></i>
                                    System Status
                                </h3>
                            </div>
                            <div class="card-body">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="width: 12px; height: 12px; background: #10b981; border-radius: 50%;"></div>
                                    <span style="font-size: 14px; color: var(--gray-600);">All Systems Operational</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Lists -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Today's Appointments -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-calendar-day"></i>
                                    Today's Appointments
                                </h3>
                                <button class="btn btn-sm btn-primary" onclick="receptionPanel.navigateToPage('appointments')">
                                    View All
                                </button>
                            </div>
                            <div class="appointments-list">
                                ${this.renderAppointmentsList(appointmentsData?.data || [])}
                            </div>
                        </div>

                        <!-- Recent Patient Registrations -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-user-plus"></i>
                                    Recent Patient Registrations
                                </h3>
                                <button class="btn btn-sm btn-primary" onclick="receptionPanel.navigateToPage('patient-registration')">
                                    Register New
                                </button>
                            </div>
                            <div class="patients-list">
                                ${this.renderPatientsList(patientsData?.data || [])}
                            </div>
                        </div>
                    </div>

                    <!-- Payment Alerts -->
                    ${summaryData?.data?.payment_alerts?.length > 0 ? `
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                                    Payment Alerts
                                </h3>
                            </div>
                            <div class="payment-alerts">
                                ${this.renderPaymentAlerts(summaryData.data.payment_alerts)}
                            </div>
                        </div>
                    ` : ''}
                </div>
            `;
        } catch (error) {
            console.error('Dashboard error:', error);
            return '<div class="text-center py-8"><p>Error loading dashboard data</p></div>';
        }
    }

    renderAppointmentsList(appointments) {
        if (!appointments.length) {
            return '<div class="text-center py-4 text-gray-500">No appointments today</div>';
        }

        return appointments.slice(0, 5).map(apt => `
            <div class="flex items-center justify-between p-3 border-b border-gray-100 hover:bg-gray-50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-blue-600 text-sm"></i>
                    </div>
                    <div>
                        <div class="font-medium text-gray-800">${apt.patient_name}</div>
                        <div class="text-sm text-gray-500">${apt.doctor_name} • ${apt.appointment_time}</div>
                    </div>
                </div>
                <span class="status-badge ${apt.appointment_status?.toLowerCase() || 'scheduled'}">
                    ${apt.appointment_status || 'Scheduled'}
                </span>
            </div>
        `).join('');
    }

    renderPatientsList(patients) {
        if (!patients.length) {
            return '<div class="text-center py-4 text-gray-500">No recent registrations</div>';
        }

        return patients.slice(0, 5).map(patient => `
            <div class="flex items-center justify-between p-3 border-b border-gray-100 hover:bg-gray-50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-green-600 text-sm"></i>
                    </div>
                    <div>
                        <div class="font-medium text-gray-800">${patient.first_name} ${patient.last_name}</div>
                        <div class="text-sm text-gray-500">ID: ${patient.patient_id} • ${patient.registration_date}</div>
                    </div>
                </div>
                <span class="status-badge active">Active</span>
            </div>
        `).join('');
    }

    renderPaymentAlerts(alerts) {
        return alerts.slice(0, 3).map(alert => `
            <div class="flex items-center justify-between p-3 bg-yellow-50 border border-yellow-200 rounded-lg mb-2">
                <div class="flex items-center gap-3">
                    <i class="fas fa-exclamation-circle text-yellow-600"></i>
                    <div>
                        <div class="font-medium text-gray-800">${alert.patient_name}</div>
                        <div class="text-sm text-gray-600">Amount: ₹${alert.amount}</div>
                    </div>
                </div>
                <button class="btn btn-sm btn-warning" onclick="receptionPanel.navigateToPage('billing')">
                    Process
                </button>
            </div>
        `).join('');
    }

    async loadPatientRegistration() {
        try {
            const response = await fetch('patient_registration_content.php');
            const content = await response.text();
            return content;
        } catch (error) {
            console.error('Error loading patient registration:', error);
            return `
                <div class="text-center py-8">
                    <i class="fas fa-exclamation-triangle text-4xl text-red-500 mb-4"></i>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Error Loading Patient Registration</h2>
                    <p class="text-gray-600">Please try refreshing the page.</p>
                </div>
            `;
        }
    }

    async loadAppointments() {
        try {
            const response = await fetch('appointment_management_content.php');
            const content = await response.text();
            return content;
        } catch (error) {
            console.error('Error loading appointments:', error);
            return `
                <div class="text-center py-8">
                    <i class="fas fa-exclamation-triangle text-4xl text-red-500 mb-4"></i>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Error Loading Appointments</h2>
                    <p class="text-gray-600">Please try refreshing the page.</p>
                </div>
            `;
        }
    }

    async loadOPDManagement() {
        return `
            <div class="opd-management">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-stethoscope"></i>
                            OPD Patient Management
                        </h3>
                        <div class="flex gap-2">
                            <button class="btn btn-primary" onclick="receptionPanel.generateOPDToken()">
                                <i class="fas fa-plus"></i>
                                Generate Token
                            </button>
                        </div>
                    </div>
                    
                    <div class="table-container">
                        <div class="table-header">
                            <div class="table-title">OPD Patients</div>
                            <div class="table-actions">
                                <input type="text" class="search-input" placeholder="Search patients..." id="opdSearch">
                                <select class="filter-select" id="opdStatusFilter">
                                    <option value="">All Status</option>
                                    <option value="Waiting">Waiting</option>
                                    <option value="Consulting">Consulting</option>
                                    <option value="Completed">Completed</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Patient ID</th>
                                        <th>Patient Name</th>
                                        <th>Visit Date</th>
                                        <th>Token Number</th>
                                        <th>Assigned Doctor</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="opdPatientsTableBody">
                                    <!-- Data will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    async loadIPDAdmission() {
        return `
            <div class="ipd-admission">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Admission Form -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-bed"></i>
                                Patient Admission
                            </h3>
                        </div>
                        
                        <form id="ipdAdmissionForm" class="form-grid cols-2">
                            <div class="form-group">
                                <label class="form-label">Patient ID <span class="required">*</span></label>
                                <input type="text" name="patient_id" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Admission Date <span class="required">*</span></label>
                                <input type="date" name="admission_date" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Ward <span class="required">*</span></label>
                                <select name="ward" class="form-control" required>
                                    <option value="">Select Ward</option>
                                    <option value="General">General Ward</option>
                                    <option value="Private">Private Ward</option>
                                    <option value="ICU">ICU</option>
                                    <option value="NICU">NICU</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Room Number <span class="required">*</span></label>
                                <input type="text" name="room_number" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Bed Number <span class="required">*</span></label>
                                <input type="text" name="bed_number" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Assigned Doctor <span class="required">*</span></label>
                                <select name="doctor_id" class="form-control" required>
                                    <option value="">Select Doctor</option>
                                </select>
                            </div>
                            
                            <div class="form-group col-span-2">
                                <label class="form-label">Reason for Admission</label>
                                <textarea name="reason" class="form-control" rows="3"></textarea>
                            </div>
                            
                            <div class="form-group col-span-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-check"></i>
                                    Admit Patient
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Admitted Patients List -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-users"></i>
                                Admitted Patients
                            </h3>
                        </div>
                        
                        <div class="admitted-patients-list" id="admittedPatientsList">
                            <!-- Data will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    async loadBilling() {
        return `
            <div class="billing">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-file-invoice-dollar"></i>
                            Billing Management
                        </h3>
                        <div class="flex gap-2">
                            <button class="btn btn-primary" onclick="receptionPanel.generateBill()">
                                <i class="fas fa-plus"></i>
                                Generate Bill
                            </button>
                        </div>
                    </div>
                    
                    <div class="table-container">
                        <div class="table-header">
                            <div class="table-title">Billing Records</div>
                            <div class="table-actions">
                                <input type="text" class="search-input" placeholder="Search bills..." id="billingSearch">
                                <select class="filter-select" id="paymentStatusFilter">
                                    <option value="">All Status</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Paid">Paid</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Bill ID</th>
                                        <th>Patient Name</th>
                                        <th>Service Type</th>
                                        <th>Amount</th>
                                        <th>Bill Date</th>
                                        <th>Payment Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="billingTableBody">
                                    <!-- Data will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    async loadDoctorAvailability() {
        return `
            <div class="doctor-availability">
                <!-- Statistics Dashboard -->
                <div class="stats-grid">
                    <div class="stat-card primary">
                        <div class="stat-icon">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value" id="totalDoctors">0</div>
                            <div class="stat-label">Total Doctors</div>
                        </div>
                    </div>
                    
                    <div class="stat-card success">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value" id="availableDoctors">0</div>
                            <div class="stat-label">Available Now</div>
                        </div>
                    </div>
                    
                    <div class="stat-card warning">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value" id="offDutyDoctors">0</div>
                            <div class="stat-label">Off Duty</div>
                        </div>
                    </div>
                    
                    <div class="stat-card info">
                        <div class="stat-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value" id="departmentCount">0</div>
                            <div class="stat-label">Departments</div>
                        </div>
                    </div>
                </div>

                <!-- Main Card -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-md"></i>
                            Doctor Directory
                        </h3>
                        <div class="header-actions">
                            <button class="btn btn-sm btn-outline" onclick="receptionPanel.refreshDoctorAvailability()">
                                <i class="fas fa-sync-alt"></i>
                                Refresh
                            </button>
                        </div>
                    </div>
                    
                    <!-- Search and Filters -->
                    <div class="filter-panel">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input 
                                type="text" 
                                id="doctorSearch" 
                                class="search-input-modern" 
                                placeholder="Search by name or specialization..."
                            >
                        </div>
                        
                        <div class="filter-group">
                            <select id="departmentFilter" class="filter-select-modern">
                                <option value="">All Departments</option>
                            </select>
                            
                            <select id="specializationFilter" class="filter-select-modern">
                                <option value="">All Specializations</option>
                            </select>
                            
                            <select id="availabilityFilter" class="filter-select-modern">
                                <option value="">All Status</option>
                                <option value="Available">Available</option>
                                <option value="Off-Duty">Off-Duty</option>
                            </select>
                            
                            <button class="btn btn-sm btn-secondary" onclick="receptionPanel.clearDoctorFilters()">
                                <i class="fas fa-times"></i>
                                Clear
                            </button>
                        </div>
                    </div>
                    
                    <!-- Loading State -->
                    <div id="doctorsLoading" class="loading-skeleton hidden">
                        <div class="skeleton-card"></div>
                        <div class="skeleton-card"></div>
                        <div class="skeleton-card"></div>
                    </div>
                    
                    <!-- Doctors Grid -->
                    <div class="doctors-grid-advanced" id="doctorsGrid">
                        <!-- Doctor cards will be loaded here -->
                    </div>
                    
                    <!-- Empty State -->
                    <div id="emptyState" class="empty-state hidden">
                        <i class="fas fa-user-md-slash"></i>
                        <h3>No Doctors Found</h3>
                        <p>Try adjusting your filters or search criteria</p>
                    </div>
                </div>
            </div>
            
            <!-- Doctor Details Modal -->
            <div id="doctorModal" class="modal">
                <div class="modal-overlay" onclick="receptionPanel.closeDoctorModal()"></div>
                <div class="modal-content modal-lg">
                    <div class="modal-header">
                        <h3 class="modal-title">
                            <i class="fas fa-user-md"></i>
                            Doctor Profile
                        </h3>
                        <button class="modal-close" onclick="receptionPanel.closeDoctorModal()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body" id="doctorModalBody">
                        <!-- Doctor details will be loaded here -->
                    </div>
                </div>
            </div>
        `;
    }

    async loadProfile() {
        return `
            <div class="profile">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Profile Card -->
                    <div class="lg:col-span-1">
                        <div class="card">
                            <div class="text-center">
                                <div class="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-user text-blue-600 text-3xl"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-800 mb-2">Receptionist</h3>
                                <p class="text-gray-500 mb-4">Front Desk Operator</p>
                                
                                <div class="space-y-2 text-left">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Employee ID:</span>
                                        <span class="font-medium">${Storage.get('staff_id') || 'N/A'}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Department:</span>
                                        <span class="font-medium">Reception</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Join Date:</span>
                                        <span class="font-medium">Jan 15, 2024</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Profile Details -->
                    <div class="lg:col-span-2">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-user-edit"></i>
                                    Profile Information
                                </h3>
                            </div>
                            
                            <form id="profileForm" class="form-grid cols-2">
                                <div class="form-group">
                                    <label class="form-label">First Name</label>
                                    <input type="text" name="first_name" class="form-control" value="John">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" name="last_name" class="form-control" value="Doe">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="reception@gmhms.com">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" name="phone" class="form-control" value="+91 9876543210">
                                </div>
                                
                                <div class="form-group col-span-2">
                                    <label class="form-label">Address</label>
                                    <textarea name="address" class="form-control" rows="2">123 Hospital Road, City - 123456</textarea>
                                </div>
                                
                                <div class="form-group col-span-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i>
                                        Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Change Password -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-lock"></i>
                                    Change Password
                                </h3>
                            </div>
                            
                            <form id="passwordForm" class="form-grid cols-2">
                                <div class="form-group">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-key"></i>
                                        Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    initializePageComponents(page) {
        switch (page) {
            case 'patient-registration':
                this.initializePatientRegistration();
                break;
            case 'appointments':
                this.initializeAppointments();
                break;
            case 'opd-management':
                this.initializeOPDManagement();
                break;
            case 'ipd-admission':
                this.initializeIPDAdmission();
                break;
            case 'billing':
                this.initializeBilling();
                break;
            case 'doctor-availability':
                this.initializeDoctorAvailability();
                break;
            case 'profile':
                this.initializeProfile();
                break;
        }
    }

    initializePatientRegistration() {
        this.loadPatients();

        // Search functionality
        document.getElementById('patientSearch')?.addEventListener('input', (e) => {
            this.filterPatients(e.target.value);
        });

        document.getElementById('genderFilter')?.addEventListener('change', (e) => {
            this.filterPatientsByGender(e.target.value);
        });
    }

    initializeAppointments() {
        this.loadAppointments();
        this.loadPatientsForSelect();
        this.loadDoctorsForSelect();

        // Search and filter functionality
        document.getElementById('appointmentSearch')?.addEventListener('input', (e) => {
            this.filterAppointments(e.target.value);
        });

        document.getElementById('statusFilter')?.addEventListener('change', (e) => {
            this.filterAppointmentsByStatus(e.target.value);
        });

        document.getElementById('dateFilter')?.addEventListener('change', (e) => {
            this.filterAppointmentsByDate(e.target.value);
        });
    }

    initializeOPDManagement() {
        this.loadOPDPatients();

        // Search functionality
        document.getElementById('opdSearch')?.addEventListener('input', (e) => {
            this.filterOPDPatients(e.target.value);
        });

        document.getElementById('opdStatusFilter')?.addEventListener('change', (e) => {
            this.filterOPDPatientsByStatus(e.target.value);
        });
    }

    initializeIPDAdmission() {
        const form = document.getElementById('ipdAdmissionForm');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleIPDAdmission(new FormData(form));
            });
        }

        this.loadAdmittedPatients();
        this.loadDoctorOptions();
    }

    initializeBilling() {
        this.loadBillingRecords();

        document.getElementById('billingSearch')?.addEventListener('input', (e) => {
            this.filterBillingRecords(e.target.value);
        });

        document.getElementById('paymentStatusFilter')?.addEventListener('change', (e) => {
            this.filterBillingByStatus(e.target.value);
        });
    }

    initializeDoctorAvailability() {
        this.loadDoctorAvailability();

        // Set up event listeners after a short delay to ensure DOM is ready
        setTimeout(() => {
            // Search input
            const searchInput = document.getElementById('doctorSearch');
            if (searchInput) {
                searchInput.addEventListener('input', () => this.applyDoctorFilters());
            }

            // Filter dropdowns
            const departmentFilter = document.getElementById('departmentFilter');
            if (departmentFilter) {
                departmentFilter.addEventListener('change', () => this.applyDoctorFilters());
            }

            const specializationFilter = document.getElementById('specializationFilter');
            if (specializationFilter) {
                specializationFilter.addEventListener('change', () => this.applyDoctorFilters());
            }

            const availabilityFilter = document.getElementById('availabilityFilter');
            if (availabilityFilter) {
                availabilityFilter.addEventListener('change', () => this.applyDoctorFilters());
            }
        }, 500);
    }

    initializeProfile() {
        const profileForm = document.getElementById('profileForm');
        if (profileForm) {
            profileForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.updateProfile(new FormData(profileForm));
            });
        }

        const passwordForm = document.getElementById('passwordForm');
        if (passwordForm) {
            passwordForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.changePassword(new FormData(passwordForm));
            });
        }
    }

    // API Methods
    async apiCall(endpoint, options = {}) {
        const url = `http://localhost/GM_HMS${endpoint}`;
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        };

        // Add auth token if available
        const token = this.getAuthToken();
        if (token) {
            defaultOptions.headers['Authorization'] = `Bearer ${token}`;
        }

        const response = await fetch(url, { ...defaultOptions, ...options });

        if (!response.ok) {
            if (response.status === 401) {
                // Redirect to login if unauthorized
                window.location.href = '../view/login.php';
                return;
            }
            throw new Error(`API call failed: ${response.statusText}`);
        }

        const data = await response.json();

        // Handle API response format
        if (data.success === false) {
            throw new Error(data.message || 'API request failed');
        }

        return data;
    }

    getAuthToken() {
        // This would get the auth token from localStorage or cookies
        return localStorage.getItem('auth_token') || '';
    }

    // UI Helper Methods
    showLoading() {
        document.getElementById('loadingOverlay').classList.remove('hidden');
    }

    hideLoading() {
        document.getElementById('loadingOverlay').classList.add('hidden');
    }

    showToast(message, type = 'info', title = '') {
        const toastContainer = document.getElementById('toastContainer');
        const toastId = 'toast-' + Date.now();

        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.id = toastId;
        toast.innerHTML = `
            <div class="toast-icon">
                <i class="${icons[type]}"></i>
            </div>
            <div class="toast-content">
                ${title ? `<div class="toast-title">${title}</div>` : ''}
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" onclick="receptionPanel.closeToast('${toastId}')">
                <i class="fas fa-times"></i>
            </button>
        `;

        toastContainer.appendChild(toast);

        // Trigger animation
        setTimeout(() => toast.classList.add('show'), 10);

        // Auto remove after 5 seconds
        setTimeout(() => this.closeToast(toastId), 5000);
    }

    closeToast(toastId) {
        const toast = document.getElementById(toastId);
        if (toast) {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }
    }

    toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        this.sidebarCollapsed = !this.sidebarCollapsed;

        if (this.sidebarCollapsed) {
            sidebar.classList.add('collapsed');
        } else {
            sidebar.classList.remove('collapsed');
        }
    }

    closeMobileSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.remove('mobile-open');
    }

    handleResize() {
        if (window.innerWidth > 1024) {
            document.getElementById('sidebar').classList.remove('mobile-open');
        }
    }

    handleLogout() {
        if (confirm('Are you sure you want to logout?')) {
            // Clear auth token
            localStorage.removeItem('auth_token');

            // Redirect to login page
            window.location.href = '../view/login.php';
        }
    }

    // Placeholder methods for functionality
    async loadOPDPatients() {
        // Implementation would load OPD patients
    }

    async loadPatients() {
        try {
            const response = await this.apiCall('/controler/api/PatientController.php/api/patients');
            const patients = response.data || [];

            const tbody = document.getElementById('patientTableBody');
            if (!tbody) return;

            tbody.innerHTML = patients.map(patient => `
                <tr>
                    <td>${patient.patient_id}</td>
                    <td>${patient.first_name} ${patient.last_name}</td>
                    <td>${this.calculateAge(patient.birth_date)}</td>
                    <td>${patient.sex}</td>
                    <td>${patient.phone || 'N/A'}</td>
                    <td>${patient.email}</td>
                    <td>${new Date(patient.created_at).toLocaleDateString()}</td>
                    <td>
                        <div class="action-icons">
                            <button class="action-icon edit" onclick="receptionPanel.editPatient('${patient.patient_id}')" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-icon view" onclick="receptionPanel.viewPatient('${patient.patient_id}')" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        } catch (error) {
            console.error('Error loading patients:', error);
            this.showToast('Failed to load patients', 'error');
        }
    }

    async loadAppointments() {
        try {
            const response = await this.apiCall('/controler/api/AppointmentController.php/api/appointments');
            const appointments = response.data || [];

            const tbody = document.getElementById('appointmentTableBody');
            if (!tbody) return;

            tbody.innerHTML = appointments.map(apt => `
                <tr>
                    <td>${apt.appointment_id}</td>
                    <td>${apt.patient_name}</td>
                    <td>${apt.doctor_name}</td>
                    <td>${apt.appointment_date}</td>
                    <td>${apt.appointment_time}</td>
                    <td>${apt.appointment_type}</td>
                    <td><span class="status-badge ${apt.appointment_status?.toLowerCase()}">${apt.appointment_status}</span></td>
                    <td>
                        <div class="action-icons">
                            <button class="action-icon edit" onclick="receptionPanel.editAppointment('${apt.appointment_id}')" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-icon delete" onclick="receptionPanel.cancelAppointment('${apt.appointment_id}')" title="Cancel">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        } catch (error) {
            console.error('Error loading appointments:', error);
            this.showToast('Failed to load appointments', 'error');
        }
    }

    async loadPatientsForSelect() {
        try {
            const response = await this.apiCall('/controler/api/PatientController.php/api/patients');
            const patients = response.data || [];

            const select = document.getElementById('patientSelect');
            if (!select) return;

            select.innerHTML = '<option value="">Select Patient</option>' +
                patients.map(patient => `
                    <option value="${patient.patient_id}">${patient.first_name} ${patient.last_name}</option>
                `).join('');
        } catch (error) {
            console.error('Error loading patients for select:', error);
        }
    }

    async loadDoctorsForSelect() {
        try {
            const response = await this.apiCall('/controler/api/DoctorController.php/api/doctors');
            const doctors = response.data || [];

            const select = document.getElementById('doctorSelect');
            if (!select) return;

            select.innerHTML = '<option value="">Select Doctor</option>' +
                doctors.map(doctor => `
                    <option value="${doctor.doctor_id}">${doctor.full_name}</option>
                `).join('');
        } catch (error) {
            console.error('Error loading doctors for select:', error);
        }
    }

    // Patient Registration Methods
    openPatientModal(patientId = null) {
        const modal = document.getElementById('patientModal');
        const form = document.getElementById('patientForm');
        const title = document.getElementById('modalTitle');

        if (patientId) {
            title.textContent = 'Edit Patient';
            this.loadPatientData(patientId);
        } else {
            title.textContent = 'Add New Patient';
            form.reset();
            document.getElementById('editPatientId').value = '';
        }

        modal.classList.add('active');
    }

    closePatientModal() {
        const modal = document.getElementById('patientModal');
        modal.classList.remove('active');
    }

    async loadPatientData(patientId) {
        try {
            const response = await this.apiCall(`/controler/api/PatientController.php/api/patients/${patientId}`);
            const patient = response.data;

            const form = document.getElementById('patientForm');
            Object.keys(patient).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) {
                    input.value = patient[key];
                }
            });

            document.getElementById('editPatientId').value = patient.patient_id;
        } catch (error) {
            console.error('Error loading patient data:', error);
        }
    }

    async savePatient() {
        const form = document.getElementById('patientForm');
        const formData = new FormData(form);
        const patientId = formData.get('patient_id');

        try {
            const data = Object.fromEntries(formData.entries());

            let response;
            if (patientId) {
                response = await this.apiCall(`/controler/api/PatientController.php/api/patients/${patientId}`, {
                    method: 'PUT',
                    body: JSON.stringify(data)
                });
            } else {
                response = await this.apiCall('/controler/api/PatientController.php/api/patients', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });
            }

            this.showToast('Patient saved successfully', 'success');
            this.closePatientModal();
            this.loadPatients();
        } catch (error) {
            console.error('Error saving patient:', error);
            this.showToast('Failed to save patient', 'error');
        }
    }

    // Appointment Management Methods
    openAppointmentModal(appointmentId = null) {
        const modal = document.getElementById('appointmentModal');
        const form = document.getElementById('appointmentForm');
        const title = document.getElementById('appointmentModalTitle');

        // Set minimum date to today
        const dateInput = document.getElementById('appointmentDate');
        if (dateInput) {
            dateInput.min = new Date().toISOString().split('T')[0];
        }

        if (appointmentId) {
            title.textContent = 'Edit Appointment';
            this.loadAppointmentData(appointmentId);
        } else {
            title.textContent = 'Book Appointment';
            form.reset();
            document.getElementById('editAppointmentId').value = '';
        }

        modal.classList.add('active');
    }

    closeAppointmentModal() {
        const modal = document.getElementById('appointmentModal');
        modal.classList.remove('active');
    }

    async loadAppointmentData(appointmentId) {
        try {
            const response = await this.apiCall(`/controler/api/AppointmentController.php/api/appointments/${appointmentId}`);
            const appointment = response.data;

            const form = document.getElementById('appointmentForm');
            Object.keys(appointment).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) {
                    input.value = appointment[key];
                }
            });

            document.getElementById('editAppointmentId').value = appointment.appointment_id;
        } catch (error) {
            console.error('Error loading appointment data:', error);
        }
    }

    async saveAppointment() {
        const form = document.getElementById('appointmentForm');
        const formData = new FormData(form);
        const appointmentId = formData.get('appointment_id');

        try {
            const data = Object.fromEntries(formData.entries());

            let response;
            if (appointmentId) {
                response = await this.apiCall(`/controler/api/AppointmentController.php/api/appointments/${appointmentId}`, {
                    method: 'PUT',
                    body: JSON.stringify(data)
                });
            } else {
                response = await this.apiCall('/controler/api/AppointmentController.php/api/appointments', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });
            }

            this.showToast('Appointment saved successfully', 'success');
            this.closeAppointmentModal();
            this.loadAppointments();
        } catch (error) {
            console.error('Error saving appointment:', error);
            this.showToast('Failed to save appointment', 'error');
        }
    }

    async cancelAppointment(appointmentId) {
        if (!confirm('Are you sure you want to cancel this appointment?')) {
            return;
        }

        try {
            await this.apiCall(`/controler/api/AppointmentController.php/api/appointments/${appointmentId}`, {
                method: 'PUT',
                body: JSON.stringify({ appointment_status: 'Cancelled' })
            });

            this.showToast('Appointment cancelled successfully', 'success');
            this.loadAppointments();
        } catch (error) {
            console.error('Error cancelling appointment:', error);
            this.showToast('Failed to cancel appointment', 'error');
        }
    }

    editPatient(patientId) {
        this.openPatientModal(patientId);
    }

    viewPatient(patientId) {
        // Implementation for viewing patient details
        this.showToast('Patient view feature coming soon', 'info');
    }

    editAppointment(appointmentId) {
        this.openAppointmentModal(appointmentId);
    }

    // Filter Methods
    filterPatients(searchTerm) {
        // Implementation for filtering patients
        console.log('Filtering patients:', searchTerm);
    }

    filterPatientsByGender(gender) {
        // Implementation for filtering patients by gender
        console.log('Filtering patients by gender:', gender);
    }

    filterAppointments(searchTerm) {
        // Implementation for filtering appointments
        console.log('Filtering appointments:', searchTerm);
    }

    filterAppointmentsByStatus(status) {
        // Implementation for filtering appointments by status
        console.log('Filtering appointments by status:', status);
    }

    filterAppointmentsByDate(date) {
        // Implementation for filtering appointments by date
        console.log('Filtering appointments by date:', date);
    }

    // Utility Methods
    calculateAge(birthDate) {
        const today = new Date();
        const birth = new Date(birthDate);
        let age = today.getFullYear() - birth.getFullYear();
        const monthDiff = today.getMonth() - birth.getMonth();

        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
            age--;
        }

        return age;
    }

    async loadAdmittedPatients() {
        // Implementation would load admitted patients
    }

    async loadBillingRecords() {
        // Implementation would load billing records
    }

    async loadDoctorAvailability() {
        try {
            // Show loading state
            const loading = document.getElementById('doctorsLoading');
            const grid = document.getElementById('doctorsGrid');
            const emptyState = document.getElementById('emptyState');

            if (loading) loading.classList.remove('hidden');
            if (grid) grid.innerHTML = '';
            if (emptyState) emptyState.classList.add('hidden');

            const response = await this.apiCall('/controler/api/ReceptionController.php/api/doctors/availability');
            const doctors = response.data || [];

            // Hide loading
            if (loading) loading.classList.add('hidden');

            if (!grid) return;

            // Store doctors data for filtering
            this.allDoctors = doctors;
            this.filteredDoctors = doctors;

            // Calculate and update statistics
            this.updateDoctorStatistics(doctors);

            // Populate filter dropdowns
            this.populateDoctorFilters(doctors);

            // Render doctors
            this.renderDoctors(doctors);

        } catch (error) {
            console.error('Error loading doctor availability:', error);
            const loading = document.getElementById('doctorsLoading');
            const grid = document.getElementById('doctorsGrid');

            if (loading) loading.classList.add('hidden');
            if (grid) {
                grid.innerHTML = `
                    <div class="error-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Failed to Load Doctors</h3>
                        <p>${error.message}</p>
                        <button class="btn btn-primary" onclick="receptionPanel.refreshDoctorAvailability()">
                            <i class="fas fa-redo"></i>
                            Try Again
                        </button>
                    </div>
                `;
            }
            this.showToast('Failed to load doctor availability', 'error');
        }
    }

    updateDoctorStatistics(doctors) {
        const total = doctors.length;
        const available = doctors.filter(d => d.availability === 'Available').length;
        const offDuty = total - available;
        const departments = new Set(doctors.map(d => d.department).filter(Boolean)).size;

        // Update stat cards with animation
        this.animateValue('totalDoctors', 0, total, 1000);
        this.animateValue('availableDoctors', 0, available, 1000);
        this.animateValue('offDutyDoctors', 0, offDuty, 1000);
        this.animateValue('departmentCount', 0, departments, 1000);
    }

    animateValue(id, start, end, duration) {
        const element = document.getElementById(id);
        if (!element) return;

        const range = end - start;
        const increment = range / (duration / 16);
        let current = start;

        const timer = setInterval(() => {
            current += increment;
            if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                current = end;
                clearInterval(timer);
            }
            element.textContent = Math.round(current);
        }, 16);
    }

    populateDoctorFilters(doctors) {
        // Populate departments
        const departments = [...new Set(doctors.map(d => d.department).filter(Boolean))].sort();
        const deptFilter = document.getElementById('departmentFilter');
        if (deptFilter) {
            // Clear previous options except the first (default)
            while (deptFilter.options.length > 1) {
                deptFilter.remove(1);
            }
            departments.forEach(dept => {
                const option = document.createElement('option');
                option.value = dept;
                option.textContent = dept;
                deptFilter.appendChild(option);
            });
        }

        // Populate specializations
        const specializations = [...new Set(doctors.map(d => d.specialization).filter(Boolean))].sort();
        const specFilter = document.getElementById('specializationFilter');
        if (specFilter) {
            // Clear previous options except the first (default)
            while (specFilter.options.length > 1) {
                specFilter.remove(1);
            }
            specializations.forEach(spec => {
                const option = document.createElement('option');
                option.value = spec;
                option.textContent = spec;
                specFilter.appendChild(option);
            });
        }
    }

    renderDoctors(doctors) {
        const grid = document.getElementById('doctorsGrid');
        const emptyState = document.getElementById('emptyState');

        if (!grid) return;

        if (doctors.length === 0) {
            grid.innerHTML = '';
            if (emptyState) emptyState.classList.remove('hidden');
            return;
        }

        if (emptyState) emptyState.classList.add('hidden');

        grid.innerHTML = doctors.map(doctor => {
            const availabilityClass = doctor.availability === 'Available' ? 'success' : 'danger';
            const availabilityIcon = doctor.availability === 'Available' ? 'fa-check-circle' : 'fa-times-circle';
            const statusDot = doctor.availability === 'Available' ? 'status-dot-online' : 'status-dot-offline';

            // Generate initials for avatar
            const initials = doctor.full_name
                .split(' ')
                .map(n => n[0])
                .join('')
                .substring(0, 2)
                .toUpperCase();

            return `
                <div class="doctor-card-advanced" data-doctor-id="${doctor.doctor_id}">
                    <div class="doctor-card-header">
                        <div class="doctor-avatar-wrapper">
                            <div class="doctor-avatar ${availabilityClass}">
                                ${doctor.photo ?
                    `<img src="${doctor.photo}" alt="${doctor.full_name}">` :
                    `<span class="avatar-initials">${initials}</span>`
                }
                            </div>
                            <span class="${statusDot}"></span>
                        </div>
                        <div class="doctor-status-badge">
                            <span class="status-badge ${availabilityClass}">
                                <i class="fas ${availabilityIcon}"></i>
                                ${doctor.availability}
                            </span>
                        </div>
                    </div>
                    
                    <div class="doctor-card-body">
                        <h4 class="doctor-name">${doctor.full_name}</h4>
                        <p class="doctor-specialization">
                            <i class="fas fa-stethoscope"></i>
                            ${doctor.specialization || 'General Physician'}
                        </p>
                        
                        <div class="doctor-info-grid">
                            <div class="info-item">
                                <i class="fas fa-building"></i>
                                <span>${doctor.department || 'General'}</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-door-open"></i>
                                <span>Room ${doctor.room_number || 'N/A'}</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-clock"></i>
                                <span>${doctor.in_time || '--:--'} - ${doctor.out_time || '--:--'}</span>
                            </div>
                            <div class="info-item highlight">
                                <i class="fas fa-rupee-sign"></i>
                                <span class="fee-amount">₹${doctor.consultation_fee || '0'}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="doctor-card-footer">
                        <button class="btn-card-action secondary" onclick="receptionPanel.viewDoctorDetails('${doctor.doctor_id}')">
                            <i class="fas fa-info-circle"></i>
                            View Details
                        </button>
                        <button class="btn-card-action primary" onclick="receptionPanel.bookAppointment('${doctor.doctor_id}')" ${doctor.availability !== 'Available' ? 'disabled' : ''}>
                            <i class="fas fa-calendar-plus"></i>
                            Book Appointment
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    refreshDoctorAvailability() {
        this.loadDoctorAvailability(); // Call the main loading function
    }

    clearDoctorFilters() {
        document.getElementById('doctorSearch').value = '';
        document.getElementById('departmentFilter').value = '';
        document.getElementById('specializationFilter').value = '';
        document.getElementById('availabilityFilter').value = '';
        this.applyDoctorFilters();
    }

    applyDoctorFilters() {
        const searchTerm = document.getElementById('doctorSearch')?.value.toLowerCase() || '';
        const department = document.getElementById('departmentFilter')?.value || '';
        const specialization = document.getElementById('specializationFilter')?.value || '';
        const availability = document.getElementById('availabilityFilter')?.value || '';

        let filtered = this.allDoctors || [];

        // Apply search filter
        if (searchTerm) {
            filtered = filtered.filter(d =>
                d.full_name.toLowerCase().includes(searchTerm) ||
                (d.specialization && d.specialization.toLowerCase().includes(searchTerm))
            );
        }

        // Apply department filter
        if (department) {
            filtered = filtered.filter(d => d.department === department);
        }

        // Apply specialization filter
        if (specialization) {
            filtered = filtered.filter(d => d.specialization === specialization);
        }

        // Apply availability filter
        if (availability) {
            filtered = filtered.filter(d => d.availability === availability);
        }

        this.filteredDoctors = filtered;
        this.renderDoctors(filtered);
    }

    viewDoctorDetails(doctorId) {
        const doctor = this.allDoctors.find(d => d.doctor_id === doctorId);
        if (!doctor) return;

        const modal = document.getElementById('doctorModal');
        const modalBody = document.getElementById('doctorModalBody');

        if (!modal || !modalBody) return;

        const initials = doctor.full_name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        const availabilityClass = doctor.availability === 'Available' ? 'success' : 'danger';

        modalBody.innerHTML = `
            <div class="doctor-profile-modal">
                <div class="profile-header">
                    <div class="profile-avatar ${availabilityClass}">
                        ${doctor.photo ?
                `<img src="${doctor.photo}" alt="${doctor.full_name}">` :
                `<span class="avatar-initials-large">${initials}</span>`
            }
                    </div>
                    <div class="profile-info">
                        <h2>${doctor.full_name}</h2>
                        <p class="profile-specialization">${doctor.specialization || 'General Physician'}</p>
                        <span class="status-badge ${availabilityClass}">
                            <i class="fas ${doctor.availability === 'Available' ? 'fa-check-circle' : 'fa-times-circle'}"></i>
                            ${doctor.availability}
                        </span>
                    </div>
                </div>
                
                <div class="profile-details-grid">
                    <div class="detail-section">
                        <h4><i class="fas fa-building"></i> Department</h4>
                        <p>${doctor.department || 'General'}</p>
                    </div>
                    <div class="detail-section">
                        <h4><i class="fas fa-door-open"></i> Room Number</h4>
                        <p>${doctor.room_number || 'N/A'}</p>
                    </div>
                    <div class="detail-section">
                        <h4><i class="fas fa-clock"></i> Timing</h4>
                        <p>${doctor.in_time || '--:--'} - ${doctor.out_time || '--:--'}</p>
                    </div>
                    <div class="detail-section">
                        <h4><i class="fas fa-rupee-sign"></i> Consultation Fee</h4>
                        <p class="fee-highlight">₹${doctor.consultation_fee || '0'}</p>
                    </div>
                </div>
                
                ${doctor.qualification ? `
                    <div class="detail-section-full">
                        <h4><i class="fas fa-graduation-cap"></i> Qualifications</h4>
                        <p>${doctor.qualification}</p>
                    </div>
                ` : ''}
                
                ${doctor.experience_years ? `
                    <div class="detail-section-full">
                        <h4><i class="fas fa-briefcase"></i> Experience</h4>
                        <p>${doctor.experience_years} years</p>
                    </div>
                ` : ''}
                
                <div class="modal-actions">
                    <button class="btn btn-secondary" onclick="receptionPanel.closeDoctorModal()">
                        <i class="fas fa-times"></i>
                        Close
                    </button>
                    <button class="btn btn-primary" onclick="receptionPanel.bookAppointment('${doctor.doctor_id}')" ${doctor.availability !== 'Available' ? 'disabled' : ''}>
                        <i class="fas fa-calendar-plus"></i>
                        Book Appointment
                    </button>
                </div>
            </div>
        `;

        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    closeDoctorModal() {
        const modal = document.getElementById('doctorModal');
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    bookAppointment(doctorId) {
        this.closeDoctorModal();
        this.navigateToPage('appointments');
        // Pre-fill doctor in appointment form
        setTimeout(() => {
            const doctorSelect = document.querySelector('select[name="doctor_id"]');
            if (doctorSelect) {
                doctorSelect.value = doctorId;
            }
        }, 500);
    }

    async generateOPDToken() {
        this.showToast('OPD token generation feature coming soon', 'info');
    }

    async handleIPDAdmission(formData) {
        this.showToast('IPD admission feature coming soon', 'info');
    }

    async generateBill() {
        this.showToast('Bill generation feature coming soon', 'info');
    }

    async updateProfile(formData) {
        this.showToast('Profile update feature coming soon', 'info');
    }

    async changePassword(formData) {
        this.showToast('Password change feature coming soon', 'info');
    }

    initializeAPI() {
        // Initialize any API configurations
    }

    startPeriodicUpdates() {
        // Start periodic updates for real-time data
        setInterval(() => {
            if (this.currentPage === 'dashboard') {
                // Refresh dashboard data
                this.loadPage('dashboard');
            }
        }, 30000); // Update every 30 seconds
    }
}

// Initialize the reception panel when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.receptionPanel = new ReceptionPanel();
});
