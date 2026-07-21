/**
 * Doctor Dashboard - JavaScript Utilities
 * API helpers, Toast notifications, Loading states, Form validation
 */

// ============================================================================
// API HELPER FUNCTIONS
// ============================================================================

const API = {
    baseURL: '/GM_HMS/api/',

    /**
     * Generic AJAX request handler
     */
    async request(endpoint, method = 'GET', data = null, headers = {}) {
        const config = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                ...headers
            }
        };

        if (data && (method === 'POST' || method === 'PUT')) {
            config.body = JSON.stringify(data);
        }

        try {
            showLoading();
            const response = await fetch(this.baseURL + endpoint, config);
            const result = await response.json();
            hideLoading();

            if (!response.ok) {
                throw new Error(result.error || 'Request failed');
            }

            return result;
        } catch (error) {
            hideLoading();
            console.error('API Error:', error);
            showToast(error.message || 'An error occurred', 'error');
            throw error;
        }
    },

    /**
     * GET request
     */
    async get(endpoint) {
        return this.request(endpoint, 'GET');
    },

    /**
     * POST request
     */
    async post(endpoint, data) {
        return this.request(endpoint, 'POST', data);
    },

    /**
     * PUT request
     */
    async put(endpoint, data) {
        return this.request(endpoint, 'PUT', data);
    },

    /**
     * DELETE request
     */
    async delete(endpoint) {
        return this.request(endpoint, 'DELETE');
    }
};

// ============================================================================
// TOAST NOTIFICATION SYSTEM
// ============================================================================

let toastContainer = null;

function initToastContainer() {
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container';
        document.body.appendChild(toastContainer);
    }
}

function showToast(message, type = 'info', duration = 5000) {
    initToastContainer();

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;

    const icons = {
        success: '<i class="fas fa-check-circle"></i>',
        error: '<i class="fas fa-exclamation-circle"></i>',
        warning: '<i class="fas fa-exclamation-triangle"></i>',
        info: '<i class="fas fa-info-circle"></i>'
    };

    toast.innerHTML = `
        <div style="font-size: 1.25rem; color: var(--${type === 'error' ? 'status-danger' : 'status-' + type});">
            ${icons[type] || icons.info}
        </div>
        <div style="flex: 1;">
            <div style="font-weight: 600; margin-bottom: 0.25rem; text-transform: capitalize;">
                ${type}
            </div>
            <div style="font-size: 0.875rem; color: var(--gray-600);">
                ${message}
            </div>
        </div>
        <button onclick="this.parentElement.remove()" style="background: none; border: none; cursor: pointer; color: var(--gray-400); font-size: 1.25rem;">
            <i class="fas fa-times"></i>
        </button>
    `;

    toastContainer.appendChild(toast);

    // Auto remove after duration
    setTimeout(() => {
        toast.style.animation = 'slideInRight 0.3s ease-out reverse';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// ============================================================================
// LOADING STATE MANAGEMENT
// ============================================================================

let loadingOverlay = null;

function showLoading(message = 'Loading...') {
    if (!loadingOverlay) {
        loadingOverlay = document.createElement('div');
        loadingOverlay.className = 'loading-overlay';
        loadingOverlay.innerHTML = `
            <div style="text-align: center; color: white;">
                <div class="spinner" style="margin: 0 auto 1rem;"></div>
                <div id="loading-message">${message}</div>
            </div>
        `;
        document.body.appendChild(loadingOverlay);
    } else {
        loadingOverlay.style.display = 'flex';
        document.getElementById('loading-message').textContent = message;
    }
}

function hideLoading() {
    if (loadingOverlay) {
        loadingOverlay.style.display = 'none';
    }
}

// ============================================================================
// FORM VALIDATION UTILITIES
// ============================================================================

const Validator = {
    /**
     * Validate required fields
     */
    required(value) {
        return value !== null && value !== undefined && value.toString().trim() !== '';
    },

    /**
     * Validate email format
     */
    email(value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(value);
    },

    /**
     * Validate phone number
     */
    phone(value) {
        const phoneRegex = /^[0-9]{10}$/;
        return phoneRegex.test(value.replace(/[\s-]/g, ''));
    },

    /**
     * Validate minimum length
     */
    minLength(value, min) {
        return value.length >= min;
    },

    /**
     * Validate maximum length
     */
    maxLength(value, max) {
        return value.length <= max;
    },

    /**
     * Validate number range
     */
    range(value, min, max) {
        const num = parseFloat(value);
        return num >= min && num <= max;
    },

    /**
     * Validate date format (YYYY-MM-DD)
     */
    date(value) {
        const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
        if (!dateRegex.test(value)) return false;
        const date = new Date(value);
        return date instanceof Date && !isNaN(date);
    },

    /**
     * Validate form
     */
    validateForm(formId, rules) {
        const form = document.getElementById(formId);
        if (!form) return false;

        let isValid = true;
        const errors = {};

        for (const [fieldName, fieldRules] of Object.entries(rules)) {
            const field = form.elements[fieldName];
            if (!field) continue;

            const value = field.value;
            const errorElement = document.getElementById(`${fieldName}-error`);

            for (const [ruleName, ruleValue] of Object.entries(fieldRules)) {
                let valid = true;
                let errorMessage = '';

                switch (ruleName) {
                    case 'required':
                        valid = this.required(value);
                        errorMessage = `${fieldName} is required`;
                        break;
                    case 'email':
                        valid = this.email(value);
                        errorMessage = 'Invalid email format';
                        break;
                    case 'phone':
                        valid = this.phone(value);
                        errorMessage = 'Invalid phone number';
                        break;
                    case 'minLength':
                        valid = this.minLength(value, ruleValue);
                        errorMessage = `Minimum ${ruleValue} characters required`;
                        break;
                    case 'maxLength':
                        valid = this.maxLength(value, ruleValue);
                        errorMessage = `Maximum ${ruleValue} characters allowed`;
                        break;
                    case 'date':
                        valid = this.date(value);
                        errorMessage = 'Invalid date format';
                        break;
                }

                if (!valid) {
                    isValid = false;
                    errors[fieldName] = errorMessage;
                    field.classList.add('is-invalid');
                    if (errorElement) {
                        errorElement.textContent = errorMessage;
                        errorElement.style.display = 'block';
                    }
                    break;
                } else {
                    field.classList.remove('is-invalid');
                    if (errorElement) {
                        errorElement.style.display = 'none';
                    }
                }
            }
        }

        return isValid;
    }
};

// ============================================================================
// DATE/TIME FORMATTING HELPERS
// ============================================================================

const DateUtils = {
    /**
     * Format date to YYYY-MM-DD
     */
    formatDate(date) {
        if (!(date instanceof Date)) {
            date = new Date(date);
        }
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    },

    /**
     * Format date to readable format (e.g., "Dec 26, 2025")
     */
    formatDateReadable(date) {
        if (!date || date === '0000-00-00') return 'Never';
        try {
            const d = new Date(date);
            if (isNaN(d.getTime())) return 'Never';

            return d.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        } catch (e) {
            return 'Never';
        }
    },

    /**
     * Format time to HH:MM
     */
    formatTime(date) {
        if (!(date instanceof Date)) {
            date = new Date(date);
        }
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${hours}:${minutes}`;
    },

    /**
     * Format datetime to readable format
     */
    formatDateTime(date) {
        if (!(date instanceof Date)) {
            date = new Date(date);
        }
        return `${this.formatDateReadable(date)} ${this.formatTime(date)}`;
    },

    /**
     * Get relative time (e.g., "2 hours ago")
     */
    getRelativeTime(date) {
        if (!(date instanceof Date)) {
            date = new Date(date);
        }
        const now = new Date();
        const diffMs = now - date;
        const diffSec = Math.floor(diffMs / 1000);
        const diffMin = Math.floor(diffSec / 60);
        const diffHour = Math.floor(diffMin / 60);
        const diffDay = Math.floor(diffHour / 24);

        if (diffSec < 60) return 'Just now';
        if (diffMin < 60) return `${diffMin} minute${diffMin > 1 ? 's' : ''} ago`;
        if (diffHour < 24) return `${diffHour} hour${diffHour > 1 ? 's' : ''} ago`;
        if (diffDay < 7) return `${diffDay} day${diffDay > 1 ? 's' : ''} ago`;
        return this.formatDateReadable(date);
    },

    /**
     * Get today's date in YYYY-MM-DD format
     */
    getToday() {
        return this.formatDate(new Date());
    },

    /**
     * Calculate age from birth date
     */
    calculateAge(birthDate) {
        if (!(birthDate instanceof Date)) {
            birthDate = new Date(birthDate);
        }
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        return age;
    }
};

// ============================================================================
// MODAL UTILITIES
// ============================================================================

const Modal = {
    /**
     * Show modal
     */
    show(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    },

    /**
     * Hide modal
     */
    hide(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    },

    /**
     * Confirm dialog
     */
    confirm(message, onConfirm, onCancel = null) {
        if (confirm(message)) {
            if (onConfirm) onConfirm();
        } else {
            if (onCancel) onCancel();
        }
    }
};

// ============================================================================
// DATA TABLE UTILITIES
// ============================================================================

const DataTable = {
    /**
     * Initialize sortable table
     */
    initSortable(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;

        const headers = table.querySelectorAll('th[data-sortable]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                this.sortTable(table, header.cellIndex);
            });
        });
    },

    /**
     * Sort table by column
     */
    sortTable(table, columnIndex) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        const isAscending = table.dataset.sortOrder !== 'asc';
        table.dataset.sortOrder = isAscending ? 'asc' : 'desc';

        rows.sort((a, b) => {
            const aValue = a.cells[columnIndex].textContent.trim();
            const bValue = b.cells[columnIndex].textContent.trim();

            if (!isNaN(aValue) && !isNaN(bValue)) {
                return isAscending ? aValue - bValue : bValue - aValue;
            }

            return isAscending
                ? aValue.localeCompare(bValue)
                : bValue.localeCompare(aValue);
        });

        rows.forEach(row => tbody.appendChild(row));
    },

    /**
     * Filter table rows
     */
    filterTable(tableId, searchValue) {
        const table = document.getElementById(tableId);
        if (!table) return;

        const rows = table.querySelectorAll('tbody tr');
        const searchLower = searchValue.toLowerCase();

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchLower) ? '' : 'none';
        });
    }
};

// ============================================================================
// LOCAL STORAGE UTILITIES
// ============================================================================

const Storage = {
    /**
     * Set item in localStorage
     */
    set(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
            return true;
        } catch (error) {
            console.error('Storage error:', error);
            return false;
        }
    },

    /**
     * Get item from localStorage
     * Handles both JSON and plain string values
     */
    get(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(key);
            if (!item) return defaultValue;

            // Try to parse as JSON first
            try {
                return JSON.parse(item);
            } catch (parseError) {
                // If JSON parse fails, return the raw string value
                // This handles cases where plain strings like "DOC001" are stored
                return item;
            }
        } catch (error) {
            console.error('Storage error:', error);
            return defaultValue;
        }
    },

    /**
     * Remove item from localStorage
     */
    remove(key) {
        try {
            localStorage.removeItem(key);
            return true;
        } catch (error) {
            console.error('Storage error:', error);
            return false;
        }
    },

    /**
     * Clear all localStorage
     */
    clear() {
        try {
            localStorage.clear();
            return true;
        } catch (error) {
            console.error('Storage error:', error);
            return false;
        }
    }
};

// ============================================================================
// NUMBER FORMATTING
// ============================================================================

const NumberUtils = {
    /**
     * Format number with commas
     */
    formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    },

    /**
     * Format currency (Indian Rupees)
     */
    formatCurrency(amount) {
        return '₹' + this.formatNumber(parseFloat(amount).toFixed(2));
    },

    /**
     * Format percentage
     */
    formatPercentage(value, decimals = 1) {
        return parseFloat(value).toFixed(decimals) + '%';
    }
};

// ============================================================================
// DEBOUNCE UTILITY
// ============================================================================

function debounce(func, wait = 300) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ============================================================================
// EXPORT FOR USE IN OTHER FILES
// ============================================================================

// ============================================================================
// PATIENT DETAIL COMPONENT
// ============================================================================

const PatientDetail = {
    async show(patientId) {
        try {
            showLoading('Loading patient record...');
            const response = await API.get(`patients/${patientId}`);

            if (response.success) {
                const patient = response.data;
                this.renderModal(patient);
            }
        } catch (error) {
            console.error('Patient Detail Error:', error);
        }
    },

    renderModal(patient) {
        let modal = document.getElementById('global-patient-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'global-patient-modal';
            modal.className = 'modal';
            document.body.appendChild(modal);
        }

        const fName = patient.first_name || '';
        const lName = patient.last_name || '';
        const initials = `${fName.charAt(0) || ''}${lName.charAt(0) || ''}`.toUpperCase() || 'P';
        const age = patient.age || DateUtils.calculateAge(patient.birth_date);

        modal.innerHTML = `
            <div class="modal-content" style="max-width: 850px;">
                <div class="modal-header">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 50px; height: 50px; border-radius: 50%; background: var(--primary-blue); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.25rem;">
                            ${initials}
                        </div>
                        <div>
                            <h2 style="margin: 0; font-size: 1.5rem;">${patient.first_name} ${patient.last_name}</h2>
                            <span style="color: var(--gray-500); font-size: 0.875rem;">Patient ID: ${patient.patient_id}</span>
                        </div>
                    </div>
                    <button onclick="Modal.hide('global-patient-modal')" class="btn btn-sm btn-outline"><i class="fas fa-times"></i></button>
                </div>
                <div class="modal-body">
                    <div class="d-grid grid-cols-2 gap-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h3 style="font-size: 1rem; border-bottom: 2px solid var(--primary-blue); padding-bottom: 0.5rem; margin-bottom: 1rem;">
                                    <i class="fas fa-info-circle mr-2"></i> Clinical Profile
                                </h3>
                                <table style="width: 100%; font-size: 0.9rem; line-height: 2;">
                                    <tr><td><strong>Age & Gender:</strong></td><td class="text-right">${age} Years / ${patient.sex}</td></tr>
                                    <tr><td><strong>Blood Group:</strong></td><td class="text-right"><span class="badge badge-info">${patient.blood_group || 'N/A'}</span></td></tr>
                                    <tr><td><strong>Phone:</strong></td><td class="text-right">${patient.phone || 'N/A'}</td></tr>
                                    <tr><td><strong>Allergies:</strong></td><td class="text-right"><span style="color: var(--status-danger); font-weight: 700;">${patient.allergies || 'None Reported'}</span></td></tr>
                                </table>
                            </div>
                        </div>
                        <div class="card bg-light">
                            <div class="card-body">
                                <h3 style="font-size: 1rem; border-bottom: 2px solid var(--primary-blue); padding-bottom: 0.5rem; margin-bottom: 1rem;">
                                    <i class="fas fa-history mr-2"></i> Quick Actions
                                </h3>
                                <div class="d-grid gap-2">
                                    <button onclick="startConsultationSession('${patient.patient_id}')" class="btn btn-primary" style="width: 100%;">
                                        <i class="fas fa-notes-medical"></i> Start Consultation
                                    </button>
                                    <button onclick="viewMedicalHistory('${patient.patient_id}')" class="btn btn-outline" style="width: 100%;">
                                        <i class="fas fa-history"></i> Full Medical History
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        Modal.show('global-patient-modal');
    }
};

// ============================================================================
// SIDEBAR & RESPONSIVENESS HANDLERS
// ============================================================================

function toggleSidebar() {
    let sidebar = document.getElementById('doctorSidebar');
    if (!sidebar) return;

    const isOpen = sidebar.style.transform === 'translateX(0px)' ||
        sidebar.classList.contains('translate-x-0') ||
        sidebar.classList.contains('open');

    if (isOpen && !sidebar.classList.contains('-translate-x-full')) {
        // Close sidebar
        sidebar.classList.add('-translate-x-full');
        sidebar.classList.remove('translate-x-0');
        sidebar.classList.remove('open');
        removeSidebarOverlay();
        document.body.style.overflow = '';
    } else {
        // Open sidebar
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
        sidebar.classList.add('open');
        createSidebarOverlay();
        document.body.style.overflow = 'hidden';
    }
}

function createSidebarOverlay() {
    if (document.getElementById('sidebarOverlay')) return;

    const overlay = document.createElement('div');
    overlay.id = 'sidebarOverlay';
    overlay.style.position = 'fixed';
    overlay.style.top = '0';
    overlay.style.left = '0';
    overlay.style.width = '100vw';
    overlay.style.height = '100vh';
    overlay.style.backgroundColor = 'rgba(0,0,0,0.5)';
    overlay.style.zIndex = '40';
    overlay.style.transition = 'opacity 0.3s ease';
    overlay.onclick = toggleSidebar;
    document.body.appendChild(overlay);
}

function removeSidebarOverlay() {
    const overlay = document.getElementById('sidebarOverlay');
    if (overlay) {
        overlay.style.opacity = '0';
        setTimeout(() => overlay.remove(), 300);
    }
}

// Window resize handler
window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024) {
        let sidebar = document.getElementById('doctorSidebar');
        if (sidebar) {
            sidebar.classList.remove('translate-x-0');
            sidebar.classList.remove('open');
            sidebar.classList.remove('-translate-x-full');
            document.body.style.overflow = '';
            removeSidebarOverlay();
        }
    }
});

function toggleDropdown(id) {
    const dropdown = document.getElementById(id);
    if (dropdown) {
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    }
}

/**
 * Start a consultation session with ID handling
 * Saves ID and previous instructions to storage and redirects cleanly
 */
async function startConsultationSession(patientId) {
    if (!patientId) return;

    try {
        // Show loading state if called from a button context
        const btn = document.activeElement;
        let originalText = '';
        if (btn && btn.tagName === 'BUTTON') {
            originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            btn.disabled = true;
        }

        sessionStorage.setItem('consultation_patient_id', patientId);

        // Fetch latest general instructions
        try {
            const response = await API.get(`prescriptions/patient/${patientId}/latest`);
            if (response.success && response.data && response.data.general_instructions) {
                sessionStorage.setItem('consultation_prev_instructions', response.data.general_instructions);
            } else {
                sessionStorage.removeItem('consultation_prev_instructions');
            }
        } catch (err) {
            console.error('Failed to fetch previous instructions', err);
            // Continue anyway
        }

        window.location.href = 'consultation.php';

    } catch (error) {
        console.error('Error starting consultation:', error);
        window.location.href = 'consultation.php'; // Fallback
    }
}

/**
 * View medical history with hidden ID - Redirects to Consultation for full notebook view
 */
function viewMedicalHistory(patientId) {
    if (!patientId) return;
    sessionStorage.setItem('consultation_patient_id', patientId);
    sessionStorage.setItem('consultation_show_history', 'true');
    window.location.href = 'consultation.php';
}

// Global Exports
window.API = API;
window.showToast = showToast;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.Validator = Validator;
window.DateUtils = DateUtils;
window.Modal = Modal;
window.DataTable = DataTable;
window.Storage = Storage;
window.NumberUtils = NumberUtils;
window.debounce = debounce;
window.PatientDetail = PatientDetail;
window.toggleSidebar = toggleSidebar;
window.toggleDropdown = toggleDropdown;
window.startConsultationSession = startConsultationSession;
window.viewMedicalHistory = viewMedicalHistory;
