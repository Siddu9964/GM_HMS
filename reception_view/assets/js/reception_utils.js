/**
 * Reception Utility Library - Core Functionality
 * Handles API calls, Toast Notifications, Date Formatting, Storage, and Modal Dialogs
 * Designed for High-Performance Hospital Management Systems
 */

// ============================================================================
// CONFIGURATION
// ============================================================================
const CONFIG = {
    API_BASE_URL: '/GM_HMS/api/',
    DEBUG_MODE: true,
    ANIMATION_DURATION: 300
};

// ============================================================================
// API HANDLER (Fetch Wrapper)
// ============================================================================
const API = {
    async request(endpoint, method = 'GET', data = null) {
        try {
            const url = `${CONFIG.API_BASE_URL}${endpoint}`;
            const options = {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            };

            if (data) {
                options.body = JSON.stringify(data);
            }

            if (CONFIG.DEBUG_MODE) {
                console.log(`📡 API Request: [${method}] ${endpoint}`, data);
            }

            const response = await fetch(url, options);
            const result = await response.json();

            if (CONFIG.DEBUG_MODE) {
                console.log(`📥 API Response:`, result);
            }

            if (!result.success) {
                throw new Error(result.message || 'API request failed');
            }

            return result;
        } catch (error) {
            console.error('❌ API Error:', error);
            showToast(error.message, 'error');
            throw error;
        }
    },

    get(endpoint) {
        return this.request(endpoint, 'GET');
    },

    post(endpoint, data) {
        return this.request(endpoint, 'POST', data);
    },

    put(endpoint, data) {
        return this.request(endpoint, 'PUT', data);
    },

    delete(endpoint) {
        return this.request(endpoint, 'DELETE');
    }
};

// ============================================================================
// TOAST NOTIFICATIONS
// ============================================================================
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container') || createToastContainer();

    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;

    // Icon based on type
    let icon = 'info-circle';
    if (type === 'success') icon = 'check-circle';
    if (type === 'error') icon = 'exclamation-circle';
    if (type === 'warning') icon = 'exclamation-triangle';

    toast.innerHTML = `
        <i class="fas fa-${icon}" style="font-size: 1.25rem; color: var(--status-${type})"></i>
        <div style="flex: 1;">
            <div style="font-weight: 600; font-size: 0.9rem; text-transform: capitalize;">
                ${type}
            </div>
            <div style="color: var(--gray-600); font-size: 0.85rem;">
                ${message}
            </div>
        </div>
        <button onclick="this.parentElement.remove()" style="background:none; border:none; color:var(--gray-400); cursor:pointer;">
            <i class="fas fa-times"></i>
        </button>
    `;

    container.appendChild(toast);

    // Auto remove after 5 seconds
    setTimeout(() => {
        toast.style.animation = 'slideInRight 0.3s reverse forwards';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container';
    document.body.appendChild(container);
    return container;
}

// ============================================================================
// DATE & TIME UTILS
// ============================================================================
const DateUtils = {
    formatDate(dateString) {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },

    formatDateReadable(date) {
        if (!date) return '-';
        if (!(date instanceof Date)) {
            date = new Date(date);
        }
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    },

    formatTime(timeString) {
        if (!timeString) return '-';
        return new Date(`1970-01-01T${timeString}`).toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });
    },

    getRelativeTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);

        if (seconds < 60) return 'Just now';
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) return `${minutes}m ago`;
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return `${hours}h ago`;
        return this.formatDate(dateString);
    }
};

// ============================================================================
// LOCAL STORAGE WRAPPER
// ============================================================================
const Storage = {
    set(key, value) {
        localStorage.setItem(`gm_hms_${key}`, JSON.stringify(value));
    },

    get(key) {
        const item = localStorage.getItem(`gm_hms_${key}`);
        return item ? JSON.parse(item) : null;
    },

    remove(key) {
        localStorage.removeItem(`gm_hms_${key}`);
    },

    clear() {
        localStorage.clear();
    }
};

// ============================================================================
// LOADING SPINNER
// ============================================================================
function showLoading(message = 'Loading...') {
    const overlay = document.createElement('div');
    overlay.id = 'global-loading-overlay';
    overlay.className = 'loading-overlay';
    overlay.innerHTML = `
        <div class="card p-4" style="text-align: center; min-width: 200px;">
            <div class="spinner" style="margin: 0 auto 1rem;"></div>
            <div style="font-weight: 600; color: var(--gray-700);">${message}</div>
        </div>
    `;
    document.body.appendChild(overlay);
}

function hideLoading() {
    const overlay = document.getElementById('global-loading-overlay');
    if (overlay) overlay.remove();
}

// ============================================================================
// MODAL DIALOGS
// ============================================================================
const Modal = {
    confirm(message, onConfirm) {
        if (confirm(message)) {
            onConfirm();
        }
    }
};

// ============================================================================
// SIDEBAR & RESPONSIVENESS HANDLERS
// ============================================================================

// Sidebar functions have been moved to reception_navbar.php for better mobile support

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    // Add toast container if not exists
    if (!document.getElementById('toast-container')) {
        createToastContainer();
    }
});
