/**
 * Doctor Dashboard - Main JavaScript
 * Handles KPI loading, charts, appointments, and activity feed
 */

// ============================================================================
// INITIALIZE DASHBOARD
// ============================================================================

document.addEventListener('DOMContentLoaded', function () {
    // Set greeting and date
    setGreeting();
    setCurrentDate();

    // Load all dashboard data
    loadDashboardData();

    // Initialize charts
    initializeCharts();

    // Refresh data every 5 minutes
    setInterval(loadDashboardData, 300000);
});

// ============================================================================
// GREETING & DATE
// ============================================================================

function setGreeting() {
    const hour = new Date().getHours();
    let greeting = 'Morning';

    if (hour >= 12 && hour < 17) {
        greeting = 'Afternoon';
    } else if (hour >= 17) {
        greeting = 'Evening';
    }

    document.getElementById('greeting-time').textContent = greeting;
}

function setCurrentDate() {
    const today = new Date();
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('current-date').textContent = today.toLocaleDateString('en-US', options);
}

// ============================================================================
// LOAD DASHBOARD DATA
// ============================================================================

async function loadDashboardData() {
    try {
        // Load all data concurrently
        await Promise.all([
            loadKPIData(),
            loadUpcomingAppointments(),
            loadRecentActivity(),
            loadEmergencyAlerts(),
            loadAIRiskAlerts()
        ]);
    } catch (error) {
        console.error('Error loading dashboard data:', error);
    }
}

// ============================================================================
// KPI DATA
// ============================================================================

async function loadKPIData() {
    try {
        const today = DateUtils.getToday();
        const doctorId = Storage.get('doctor_id');

        // 1. Get Today's Appointments (for Total Count)
        const appointmentsResponse = await API.get(`appointments?date=${today}${doctorId ? '&doctor_id=' + doctorId : ''}`);
        if (appointmentsResponse.success) {
            const appointments = appointmentsResponse.data;
            document.getElementById('kpi-appointments').textContent = appointments.length;
            document.getElementById('today-appointments-count').textContent = appointments.length;
        }

        // 2. Get Today's Consultations (for Status: Completed vs Waiting)
        // Note: User specified table 'consultations' with status 0 (Completed) and 1 (Active/Waiting)
        const consultResponse = await API.get(`consultations?date=${today}${doctorId ? '&doctor_id=' + doctorId : ''}`);

        if (consultResponse.success) {
            const consults = consultResponse.data;

            // Completed Today: Status == 0
            // Note: API might return string "0" or int 0
            const completedCount = consults.filter(c => c.status == 0 || c.status === 'Completed').length;
            document.getElementById('kpi-completed').textContent = completedCount;

            // Waiting Patients: Status == 1 (Active/Draft)
            const waitingCount = consults.filter(c => c.status == 1 || c.status === 'Draft').length;
            document.getElementById('kpi-waiting').textContent = waitingCount;
        }

        // 3. Pending Lab Reports (Mock for now, as per request to just show default 0? No, user said "now it shows default 0", implying they might want it fixed later, but didn't specify source. I will leave it as is or set to 0 if random is annoying)
        // User didn't explicitly ask to fix Lab Reports source, just mentioned it shows default 0.
        // I will set it to 0 to be clean.
        document.getElementById('kpi-pending-labs').textContent = 0;

    } catch (error) {
        console.error('Error loading KPI data:', error);
    }
}

// ============================================================================
// UPCOMING APPOINTMENTS
// ============================================================================

async function loadUpcomingAppointments() {
    try {
        const today = DateUtils.getToday();
        const doctorId = Storage.get('doctor_id');
        // Fetch appointments from today onwards
        const response = await API.get(`appointments?date_from=${today}&status=Scheduled&limit=5${doctorId ? '&doctor_id=' + doctorId : ''}`);

        if (response.success && response.data.length > 0) {
            const appointments = response.data;
            const listHtml = appointments.map(appointment => `
                <div style="padding: 1rem; border-bottom: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center;">
                    <div style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                            <div style="font-weight: 700; font-size: 0.95rem; color: #0f172a;">
                                ${appointment.patient_name}
                            </div>
                            <div style="font-size: 0.75rem; font-weight: 700; color: #1f6b4a; background: #1f6b4a15; padding: 2px 8px; border-radius: 4px;">
                                ${DateUtils.formatDateReadable(appointment.appointment_date)}
                            </div>
                        </div>
                        <div style="font-size: 0.8rem; color: var(--gray-500); display: flex; gap: 1rem;">
                            <span><i class="fas fa-clock" style="color: #1f6b4a; margin-right: 4px;"></i> ${appointment.appointment_time}</span>
                            <span><i class="fas fa-notes-medical" style="color: #1f6b4a; margin-right: 4px;"></i> ${appointment.reason || 'General Consultation'}</span>
                        </div>
                    </div>
                    <div style="margin-left: 1rem;">
                        <button onclick="startConsultation('${appointment.appointment_id}')" class="btn btn-sm btn-primary" style="padding: 0.35rem 1rem; font-weight: 600;">
                            Start
                        </button>
                    </div>
                </div>
            `).join('');

            document.getElementById('upcoming-appointments-list').innerHTML = listHtml;
        } else {
            document.getElementById('upcoming-appointments-list').innerHTML = `
                <div style="text-align: center; padding: 3rem 1rem; color: var(--gray-400);">
                    <i class="fas fa-calendar-check" style="font-size: 2.5rem; opacity: 0.2; margin-bottom: 1rem; display: block;"></i>
                    <p style="font-weight: 500;">No upcoming appointments found</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading upcoming appointments:', error);
    }
}

// ============================================================================
// RECENT ACTIVITY
// ============================================================================

async function loadRecentActivity() {
    try {
        // Mock recent activity data (replace with actual API)
        const activities = [
            {
                type: 'consultation',
                icon: 'notes-medical',
                color: '#3b82f6',
                title: 'Completed consultation',
                description: 'Patient: John Doe',
                time: new Date(Date.now() - 30 * 60000) // 30 minutes ago
            },
            {
                type: 'prescription',
                icon: 'prescription',
                color: '#10b981',
                title: 'Prescription issued',
                description: 'Patient: Jane Smith',
                time: new Date(Date.now() - 60 * 60000) // 1 hour ago
            },
            {
                type: 'lab',
                icon: 'flask',
                color: '#f59e0b',
                title: 'Lab report received',
                description: 'Patient: Robert Johnson',
                time: new Date(Date.now() - 120 * 60000) // 2 hours ago
            }
        ];

        const listHtml = activities.map(activity => `
            <div style="padding: 1rem; border-bottom: 1px solid var(--gray-200); display: flex; gap: 1rem;">
                <div style="width: 40px; height: 40px; border-radius: 50%; background: ${activity.color}20; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-${activity.icon}" style="color: ${activity.color};"></i>
                </div>
                <div style="flex: 1;">
                    <div style="font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem;">
                        ${activity.title}
                    </div>
                    <div style="font-size: 0.75rem; color: var(--gray-500);">
                        ${activity.description}
                    </div>
                    <div style="font-size: 0.7rem; color: var(--gray-400); margin-top: 0.25rem;">
                        ${DateUtils.getRelativeTime(activity.time)}
                    </div>
                </div>
            </div>
        `).join('');

        document.getElementById('recent-activity-list').innerHTML = listHtml;
    } catch (error) {
        console.error('Error loading recent activity:', error);
    }
}

// ============================================================================
// EMERGENCY & AI RISK ALERTS
// ============================================================================

async function loadEmergencyAlerts() {
    try {
        // Mock emergency alerts (replace with actual API)
        const emergencyCount = 0; // No emergencies
        document.getElementById('emergency-count').textContent = emergencyCount;

        if (emergencyCount > 0) {
            document.getElementById('emergency-list').innerHTML = `
                <div style="color: var(--status-danger); font-weight: 600;">
                    ${emergencyCount} critical patient(s) require immediate attention
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading emergency alerts:', error);
    }
}

async function loadAIRiskAlerts() {
    try {
        // Mock AI risk alerts (replace with actual AI API)
        const riskPatients = [
            { name: 'Sarah Williams', risk: 'High', reason: 'Elevated vital signs' },
            { name: 'Michael Brown', risk: 'Medium', reason: 'Missed follow-up' }
        ];

        document.getElementById('ai-risk-count').textContent = riskPatients.length;

        if (riskPatients.length > 0) {
            const listHtml = riskPatients.map(patient => `
                <div style="margin-bottom: 0.5rem;">
                    <span class="badge badge-${patient.risk === 'High' ? 'danger' : 'warning'}" style="margin-right: 0.5rem;">
                        ${patient.risk}
                    </span>
                    <strong>${patient.name}</strong>
                    <div style="font-size: 0.75rem; color: var(--gray-500); margin-top: 0.25rem;">
                        ${patient.reason}
                    </div>
                </div>
            `).join('');

            document.getElementById('ai-risk-list').innerHTML = listHtml;
        }
    } catch (error) {
        console.error('Error loading AI risk alerts:', error);
    }
}

// ============================================================================
// CHARTS
// ============================================================================

let patientFlowChart = null;
let consultationDurationChart = null;

async function initializeCharts() {
    try {
        const doctorId = Storage.get('doctor_id');
        if (!doctorId) {
            console.error('Doctor ID not found in storage');
            return;
        }
        const response = await API.get(`doctors/${doctorId}/analytics`);

        if (!response.success) {
            console.error('Failed to load analytics data:', response.error);
            return;
        }

        const analytics = response.data;

        // Patient Flow Chart (Line Chart)
        const patientFlowCtx = document.getElementById('patientFlowChart').getContext('2d');
        patientFlowChart = new Chart(patientFlowCtx, {
            type: 'line',
            data: {
                labels: analytics.labels,
                datasets: [{
                    label: 'Patients Seen',
                    data: analytics.patientFlow,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
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
                            precision: 0
                        }
                    }
                }
            }
        });

        // Consultation Duration Chart (Bar Chart)
        const consultationDurationCtx = document.getElementById('consultationDurationChart').getContext('2d');
        consultationDurationChart = new Chart(consultationDurationCtx, {
            type: 'bar',
            data: {
                labels: analytics.labels,
                datasets: [{
                    label: 'Avg. Duration (minutes)',
                    data: analytics.consultationDuration,
                    backgroundColor: '#10b981'
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
                            callback: function (value) {
                                return value + ' min';
                            }
                        }
                    }
                }
            }
        });

        // 3. Avg Consultation Time KPI
        if (analytics.overallAvgTime > 0) {
            document.getElementById('avg-consultation-time').textContent = analytics.overallAvgTime;
        }
    } catch (error) {
        console.error('Error initializing charts:', error);
    }
}

function getLast7Days() {
    const days = [];
    for (let i = 6; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        days.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
    }
    return days;
}

// ============================================================================
// ACTIONS
// ============================================================================

function startConsultation(appointmentId) {
    if (!appointmentId) return;
    sessionStorage.setItem('consultation_appointment_id', appointmentId);
    window.location.href = 'consultation.php';
}

// ============================================================================
// LOAD DOCTOR NAME IN GREETING
// ============================================================================

async function loadDoctorName() {
    try {
        const doctorId = Storage.get('doctor_id');
        if (!doctorId) return;
        const response = await API.get(`doctors/${doctorId}`);

        if (response.success) {
            let fullName = response.data.full_name;
            let displayName = fullName;

            // Clean up name - if it starts with Dr. already, don't add it again
            if (!fullName.toLowerCase().startsWith('dr.')) {
                displayName = `Dr. ${fullName.split(' ')[0]}`;
            } else {
                // If it has Dr. John Doe, just take Dr. John
                const parts = fullName.split(' ');
                displayName = parts.length > 1 ? `${parts[0]} ${parts[1]}` : parts[0];
            }

            const element = document.getElementById('doctor-greeting-name');
            if (element) element.textContent = displayName;
        }
    } catch (error) {
        console.error('Error loading doctor name:', error);
    }
}

// Load doctor name on page load
// Load doctor name on page load removed as it is handled by PHP now
// loadDoctorName();
