/**
 * Reception Dashboard Logic
 * Handles calling APIs to populate dashboard KPIs, Charts, and Activity Feed.
 */

document.addEventListener('DOMContentLoaded', function () {
    // Initialize Dashboard
    initializeDashboard();
});

async function initializeDashboard() {
    // Update Greeting
    updateGreeting();

    // Load KPI Data
    loadKPIs();

    // Load Charts
    loadPatientFlowChart();

    // Load Recent Activity
    loadRecentActivity();

    // Load Available Doctors
    loadAvailableDoctors();
}

function updateGreeting() {
    const hour = new Date().getHours();
    let greeting = 'Morning';
    if (hour >= 12 && hour < 17) greeting = 'Afternoon';
    else if (hour >= 17) greeting = 'Evening';

    const greetingEl = document.getElementById('greeting-time');
    if (greetingEl) greetingEl.textContent = greeting;

    const dateEl = document.getElementById('current-date');
    if (dateEl) dateEl.textContent = new Date().toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Real KPI Data Loader
async function loadKPIs() {
    try {
        const response = await fetch('api/get_dashboard_kpis.php');
        const result = await response.json();
        
        if (result.success && result.data) {
            const data = result.data;
            animateValue('kpi-registrations', 0, data.today_registrations, 1000);
            animateValue('kpi-waiting', 0, data.waiting_patients, 1000);
            animateValue('kpi-ipd', 0, data.active_ipd, 1000);
        } else {
            console.error('Failed to load KPIs:', result.message);
        }
    } catch (error) {
        console.error('Failed to load KPIs:', error);
    }
}

// Chart.js implementation for Patient Flow
function loadPatientFlowChart() {
    const ctx = document.getElementById('patientFlowChart');
    if (!ctx) return;

    // Mock Data
    const labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const data = [45, 52, 38, 65, 55, 48, 20];

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Patient Registrations',
                data: data,
                borderColor: '#2563eb', // Primary Blue
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#2563eb',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 12,
                    titleFont: { size: 13 },
                    bodyFont: { size: 13 },
                    displayColors: false,
                    callbacks: {
                        label: function (context) {
                            return `${context.parsed.y} Patients`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f1f5f9',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#64748b',
                        padding: 10
                    }
                },
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        color: '#64748b',
                        padding: 10
                    }
                }
            }
        }
    });
}

// Mock Recent Activity Loader
function loadRecentActivity() {
    const container = document.getElementById('recent-activity-list');
    if (!container) return;

    const activities = [
        { type: 'registration', message: 'New patient <strong>John Doe</strong> registered', time: '10 mins ago', icon: 'user-plus', color: 'primary' },
        { type: 'appointment', message: 'Appointment booked for <strong>Dr. Smith</strong>', time: '25 mins ago', icon: 'calendar-check', color: 'success' },
        { type: 'billing', message: 'Invoice #INV-2024-001 generated', time: '1 hour ago', icon: 'file-invoice-dollar', color: 'warning' },
        { type: 'admission', message: 'Patient admitted to Ward A-101', time: '2 hours ago', icon: 'bed', color: 'info' }
    ];

    let html = '';
    activities.forEach(activity => {
        html += `
            <div style="display: flex; align-items: start; gap: 1rem; padding: 1rem 0; border-bottom: 1px solid var(--gray-100);">
                <div style="background: var(--gray-100); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-${activity.icon}" style="color: var(--status-${activity.color});"></i>
                </div>
                <div>
                    <div style="font-size: 0.875rem; color: var(--gray-800); margin-bottom: 0.25rem;">
                        ${activity.message}
                    </div>
                    <div style="font-size: 0.75rem; color: var(--gray-500);">
                        ${activity.time}
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

// Animation helper for numbers
function animateValue(id, start, end, duration) {
    const obj = document.getElementById(id);
    if (!obj) return;

    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        obj.innerHTML = Math.floor(progress * (end - start) + start);
        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    };
    window.requestAnimationFrame(step);
}

// Load Available Doctors from API
async function loadAvailableDoctors() {
    const container = document.getElementById('available-doctors-list');
    const kpiDoctors = document.getElementById('kpi-doctors');
    if (!container) return;

    try {
        const response = await fetch('api/get_available_doctors.php');
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || 'Failed to load doctors');
        }

        const doctors = result.data;
        window.allDoctors = doctors || [];
        const doctorCount = window.allDoctors.length;

        // Update KPI Card
        if (kpiDoctors) {
            animateValue('kpi-doctors', 0, doctorCount, 1000);
        }

        renderDoctorsList(window.allDoctors);

        // Attach search listener
        const searchInput = document.getElementById('doctor-search-input');
        if (searchInput && !window.doctorSearchAttached) {
            searchInput.addEventListener('input', function(e) {
                const term = e.target.value.toLowerCase();
                const filtered = window.allDoctors.filter(d => 
                    (d.full_name && d.full_name.toLowerCase().includes(term)) ||
                    (d.specialization && d.specialization.toLowerCase().includes(term))
                );
                renderDoctorsList(filtered);
            });
            window.doctorSearchAttached = true;
        }

    } catch (error) {
        console.error('Error loading available doctors:', error);
        container.innerHTML = `
            <div class="no-doctors">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Failed to load doctors. Please refresh the page.</p>
            </div>
        `;
    }
}

// Render available doctors list
function renderDoctorsList(doctors) {
    const container = document.getElementById('available-doctors-list');
    if (!container) return;

    if (!doctors || doctors.length === 0) {
        container.innerHTML = `
            <div class="no-doctors" style="text-align: center; padding: 2.5rem 1rem; color: #94a3b8; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem; background: #f8fafc; border-radius: 12px; border: 1px dashed rgba(0,0,0,0.1); margin-top: 0.5rem;">
                <i class="fas fa-search" style="font-size: 1.5rem; color: #cbd5e1; margin-bottom: 0.2rem;"></i>
                <p style="margin: 0; font-size: 0.875rem; font-weight: 500;">No doctors found matching search</p>
            </div>
        `;
        return;
    }

    // Render doctors list
    let html = '';
    doctors.forEach((doctor, index) => {
        const initials = getInitials(doctor.full_name);
        const animationDelay = index * 0.05;

        html += `
            <div class="doctor-item" style="animation-delay: ${animationDelay}s; cursor: pointer; transition: all 0.2s; margin-bottom: 0.5rem; border-radius: 10px; background: #ffffff; border: 1px solid rgba(0,0,0,0.03);" onclick="window.location.href='appointment_management.php'">
                <div class="doctor-avatar">${initials}</div>
                <div class="doctor-info">
                    <div class="doctor-name">${escapeHtml(doctor.full_name)}</div>
                    <div class="doctor-specialization">
                        <i class="fas fa-stethoscope"></i>
                        ${escapeHtml(doctor.specialization)}
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

// Helper function to get initials from full name
function getInitials(fullName) {
    if (!fullName) return '?';

    const names = fullName.trim().split(' ');
    if (names.length === 1) {
        return names[0].charAt(0).toUpperCase();
    }

    return (names[0].charAt(0) + names[names.length - 1].charAt(0)).toUpperCase();
}

// Helper function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

