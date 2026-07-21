/**
 * Doctor Management - Frontend JavaScript
 * 
 * Handles all AJAX calls and DOM manipulation for doctor management
 * Pure vanilla JavaScript with AJAX for API communication
 */

class DoctorManager {
    constructor() {
        this.apiUrl = '/GM_HMS/api';
        this.doctors = [];
        this.filteredDoctors = [];
    }

    /**
     * Initialize the doctor management page
     */
    async init() {
        await this.loadDoctors();
        this.setupEventListeners();
        this.applyFilters(); // Apply default filters (like 'Available') on load
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // AI Search Input
        const searchInput = document.getElementById('doctorSearch');
        if (searchInput) {
            searchInput.addEventListener('input', () => this.applyFilters());
            // Add keyboard shortcut
            document.addEventListener('keydown', (e) => {
                if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                    e.preventDefault();
                    searchInput.focus();
                }
            });
        }

        // Integrated Department Filter
        const departmentFilter = document.getElementById('departmentFilter');
        if (departmentFilter) {
            departmentFilter.addEventListener('change', () => this.applyFilters());
        }

        // Filter Chips
        const chipsContainer = document.getElementById('filterChipsContainer');
        if (chipsContainer) {
            chipsContainer.addEventListener('click', (e) => {
                if (e.target.classList.contains('filter-chip')) {
                    // Remove active from all
                    document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
                    // Add active to clicked
                    e.target.classList.add('active');
                    this.applyFilters();
                }
            });
        }
    }

    /**
     * Load all doctors from API
     */
    async loadDoctors() {
        try {
            this.showLoading(true);

            const response = await this.apiCall('GET', '/doctors');

            if (response.success) {
                this.doctors = response.data;
                this.filteredDoctors = response.data;
                this.updateStatistics();
                this.populateFilters();
                this.renderDoctors();
            } else {
                this.showError('Failed to load doctors: ' + response.message);
            }
        } catch (error) {
            this.showError('Error loading doctors: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Get single doctor by ID
     */
    async getDoctorById(doctorId) {
        try {
            const response = await this.apiCall('GET', `/doctors/${doctorId}`);

            if (response.success) {
                return response.data;
            } else {
                this.showError('Failed to load doctor: ' + response.message);
                return null;
            }
        } catch (error) {
            this.showError('Error loading doctor: ' + error.message);
            return null;
        }
    }

    /**
     * Create new doctor
     */
    async createDoctor(data) {
        try {
            this.showLoading(true);

            const response = await this.apiCall('POST', '/doctors', data);

            if (response.success) {
                this.showSuccess('Doctor created successfully');
                await this.loadDoctors();
                return response.data;
            } else {
                this.showError('Failed to create doctor: ' + response.message);
                return null;
            }
        } catch (error) {
            this.showError('Error creating doctor: ' + error.message);
            return null;
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Update existing doctor
     */
    async updateDoctor(doctorId, data) {
        try {
            this.showLoading(true);

            const response = await this.apiCall('PUT', `/doctors/${doctorId}`, data);

            if (response.success) {
                this.showSuccess('Doctor updated successfully');
                await this.loadDoctors();
                return response.data;
            } else {
                this.showError('Failed to update doctor: ' + response.message);
                return null;
            }
        } catch (error) {
            this.showError('Error updating doctor: ' + error.message);
            return null;
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Delete doctor
     */
    async deleteDoctor(doctorId) {
        if (!confirm('Are you sure you want to delete this doctor?')) {
            return false;
        }

        try {
            this.showLoading(true);

            const response = await this.apiCall('DELETE', `/doctors/${doctorId}`);

            if (response.success) {
                this.showSuccess('Doctor deleted successfully');
                await this.loadDoctors();
                return true;
            } else {
                this.showError('Failed to delete doctor: ' + response.message);
                return false;
            }
        } catch (error) {
            this.showError('Error deleting doctor: ' + error.message);
            return false;
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Make API call using AJAX
     */
    apiCall(method, endpoint, data = null) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            const url = this.apiUrl + endpoint;

            xhr.open(method, url, true);
            xhr.setRequestHeader('Content-Type', 'application/json');

            xhr.onload = function () {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        resolve(response);
                    } catch (e) {
                        reject(new Error('Invalid JSON response'));
                    }
                } else {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        reject(new Error(response.message || 'Request failed'));
                    } catch (e) {
                        reject(new Error('Request failed with status: ' + xhr.status));
                    }
                }
            };

            xhr.onerror = function () {
                reject(new Error('Network error'));
            };

            if (data) {
                xhr.send(JSON.stringify(data));
            } else {
                xhr.send();
            }
        });
    }

    /**
     * Update statistics dashboard
     */
    updateStatistics(data = this.doctors) {
        if (!data) return;

        const total = data.length;
        const available = data.filter(d => d.availability === 'Available').length;
        const offDuty = total - available;
        const departments = new Set(data.map(d => d.department).filter(Boolean)).size;

        this.animateValue('totalDoctors', 0, total, 1000);
        this.animateValue('availableDoctors', 0, available, 1000);
        this.animateValue('offDutyDoctors', 0, offDuty, 1000);
        this.animateValue('departmentCount', 0, departments, 1000);
    }

    /**
     * Animate number counting
     */
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

    /**
     * Populate filter dropdowns
     */
    populateFilters() {
        // Populate integrated department dropdown
        const departments = [...new Set(this.doctors.map(d => d.department).filter(Boolean))].sort();
        const deptFilter = document.getElementById('departmentFilter');
        
        if (deptFilter) {
            deptFilter.innerHTML = '<option value="">All Departments</option>';
            departments.forEach(dept => {
                const option = document.createElement('option');
                option.value = dept;
                option.textContent = dept;
                deptFilter.appendChild(option);
            });
        }
    }

    /**
     * Apply filters
     */
    applyFilters() {
        const searchTerm = document.getElementById('doctorSearch')?.value.toLowerCase() || '';
        const deptSelect = document.getElementById('departmentFilter')?.value || '';
        
        let activeFilter = 'all';
        const activeChip = document.querySelector('.filter-chip.active');
        if (activeChip) {
            activeFilter = activeChip.getAttribute('data-filter');
        }

        let filtered = this.doctors;

        if (searchTerm) {
            filtered = filtered.filter(d =>
                (d.full_name && d.full_name.toLowerCase().includes(searchTerm)) ||
                (d.department && d.department.toLowerCase().includes(searchTerm)) ||
                (d.specialization && d.specialization.toLowerCase().includes(searchTerm))
            );
        }

        if (deptSelect) {
            filtered = filtered.filter(d => d.department === deptSelect);
        }

        if (activeFilter !== 'all') {
            if (activeFilter === 'Available') {
                filtered = filtered.filter(d => d.availability === 'Available');
            } else {
                // Filter by department or specialization
                filtered = filtered.filter(d => 
                    d.department === activeFilter || 
                    d.specialization === activeFilter
                );
            }
        }

        this.filteredDoctors = filtered;
        this.updateStatistics(this.filteredDoctors);
        this.renderDoctors();
    }

    /**
     * Clear all filters
     */
    clearFilters() {
        const searchInput = document.getElementById('doctorSearch');
        if (searchInput) searchInput.value = '';

        const deptFilter = document.getElementById('departmentFilter');
        if (deptFilter) deptFilter.value = '';
        
        document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
        const allChip = document.querySelector('.filter-chip[data-filter="all"]');
        if (allChip) allChip.classList.add('active');
        
        this.applyFilters();
    }

    /**
     * Render doctors grid
     */
    renderDoctors() {
        const board = document.getElementById('doctorsGrid');
        const emptyState = document.getElementById('emptyState');

        if (!board) return;

        if (this.filteredDoctors.length === 0) {
            board.innerHTML = '';
            if (emptyState) emptyState.classList.remove('hidden');
            return;
        }

        if (emptyState) emptyState.classList.add('hidden');

        // 1. Group Doctors by Department
        const grouped = {};
        this.filteredDoctors.forEach(doc => {
            const dept = doc.department || 'General';
            if (!grouped[dept]) grouped[dept] = [];
            grouped[dept].push(doc);
        });

        // 2. Render Kanban Columns
        let html = '';
        for (const [dept, docs] of Object.entries(grouped)) {
            html += `
                <div class="kanban-column">
                    <div class="kanban-column-header">
                        <h4 class="k-col-title"><i class="fas fa-building"></i> ${dept}</h4>
                        <span class="k-col-badge">${docs.length}</span>
                    </div>
                    <div class="kanban-cards-container">
                        ${docs.map(d => this.createKanbanCard(d)).join('')}
                    </div>
                </div>
            `;
        }

        board.innerHTML = html;
    }

    createKanbanCard(doctor) {
        const isAvailable = doctor.availability === 'Available';
        const fullName = doctor.full_name || 'Unknown Doctor';
        const initials = fullName.split(' ').map(n => n[0]).join('').substring(0,2).toUpperCase();
        
        // Simulate slot
        const slot = isAvailable ? '09:30 AM' : 'Full';

        return `
            <div class="kanban-card" data-doctor-id="${doctor.doctor_id}">
                <div class="k-card-top" onclick="doctorManager.viewDetails('${doctor.doctor_id}')">
                    <div class="k-avatar">
                        ${doctor.photo ? `<img src="${doctor.photo}" alt="${fullName}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">` : initials}
                    </div>
                    <div class="k-identity">
                        <h3 class="k-name">${fullName}</h3>
                        <p class="k-spec">${doctor.specialization || 'General Physician'}</p>
                    </div>
                    <div class="k-status-dot ${isAvailable ? '' : 'offline'}" title="${doctor.availability}"></div>
                </div>
                
                <div class="k-meta">
                    <div class="k-meta-item" title="Room Number"><i class="fas fa-door-open"></i> Rm ${doctor.room_number || '--'}</div>
                    <div class="k-meta-item" title="Fee"><i class="fas fa-rupee-sign"></i> ${doctor.consultation_fee || '0'}</div>
                </div>
                
                <div class="k-card-footer">
                    <span class="k-slot"><i class="fas fa-clock"></i> Next: ${slot}</span>
                    <button class="k-btn-book" onclick="doctorManager.bookAppointment('${doctor.doctor_id}')" ${!isAvailable ? 'disabled' : ''}>Book</button>
                </div>
            </div>
        `;
    }

    /**
     * View doctor details in modal
     */
    async viewDetails(doctorId) {
        const doctor = await this.getDoctorById(doctorId);
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
                        <h4><i class="fas fa-calendar-day"></i> Working Days</h4>
                        <p>${doctor.available_days || 'Mon, Tue, Wed, Thu, Fri, Sat, Sun'}</p>
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
                    <button class="btn btn-secondary" onclick="doctorManager.closeModal()">
                        <i class="fas fa-times"></i>
                        Close
                    </button>
                    <button class="btn btn-primary" onclick="doctorManager.bookAppointment('${doctor.doctor_id}')" ${doctor.availability !== 'Available' ? 'disabled' : ''}>
                        <i class="fas fa-calendar-plus"></i>
                        Book Appointment
                    </button>
                </div>
            </div>
        `;

        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    /**
     * Close modal
     */
    closeModal() {
        const modal = document.getElementById('doctorModal');
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    /**
     * Book appointment with doctor
     */
    bookAppointment(doctorId) {
        this.closeModal();
        window.location.href = `appointment_management.php?doctor_id=${doctorId}`;
    }

    /**
     * Show loading overlay
     */
    showLoading(show) {
        const loading = document.getElementById('loadingOverlay');
        const grid = document.getElementById('doctorsGrid');
        if (loading && grid) {
            if (show) {
                loading.classList.remove('hidden');
                grid.classList.add('hidden');
            } else {
                loading.classList.add('hidden');
                grid.classList.remove('hidden');
            }
        }
    }

    /**
     * Show success message
     */
    showSuccess(message) {
        this.showToast(message, 'success');
    }

    /**
     * Show error message
     */
    showError(message) {
        this.showToast(message, 'error');
    }

    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('show');
        }, 100);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                container.removeChild(toast);
            }, 300);
        }, 3000);
    }
}

// Initialize doctor manager when DOM is ready
let doctorManager;
document.addEventListener('DOMContentLoaded', () => {
    doctorManager = new DoctorManager();
    doctorManager.init();
});
