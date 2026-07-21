/**
 * IPD Management System - Main JavaScript
 * Common utilities and AJAX helpers
 */

const IPD = {
    API_BASE: '/GM_HMS/reception_view/ipd_management/public/api.php/api',

    /**
     * Make AJAX request
     */
    ajax: function (endpoint, method = 'GET', data = null) {
        return new Promise((resolve, reject) => {
            const url = `${this.API_BASE}/${endpoint}`;

            $.ajax({
                url: url,
                method: method,
                data: method === 'GET' ? data : JSON.stringify(data),
                contentType: method === 'GET' ? undefined : 'application/json',
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        resolve(response);
                    } else {
                        reject(response);
                    }
                },
                error: function (xhr, status, error) {
                    // Try to parse JSON error response
                    let errorMessage = error || 'Request failed';
                    try {
                        const errorResponse = JSON.parse(xhr.responseText);
                        if (errorResponse.message) {
                            errorMessage = errorResponse.message;
                            if (errorResponse.debug_error) {
                                errorMessage += ' (' + errorResponse.debug_error + ')';
                            }
                        }
                    } catch (e) {
                        // Use default error message
                    }

                    reject({
                        success: false,
                        message: errorMessage,
                        status: xhr.status
                    });
                }
            });
        });
    },

    /**
     * Show toast notification
     */
    toast: function (message, type = 'success') {
        const bgColor = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#06b6d4'
        }[type] || '#10b981';

        Toastify({
            text: message,
            duration: 3000,
            gravity: 'top',
            position: 'right',
            style: {
                background: bgColor
            }
        }).showToast();
    },

    /**
     * Show confirmation dialog
     */
    confirm: function (message, onConfirm) {
        if (confirm(message)) {
            onConfirm();
        }
    },


    /**
     * Show loading spinner
     */
    showLoading: function (element) {
        $(element).html('<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div>');
    },

    /**
     * Format date
     */
    formatDate: function (dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-IN', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },

    /**
     * Format datetime
     */
    formatDateTime: function (dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleString('en-IN', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },

    /**
     * Format time
     */
    formatTime: function (timeString) {
        if (!timeString) return '-';
        // Parse time string (HH:MM:SS or HH:MM)
        const timeParts = timeString.split(':');
        const hours = parseInt(timeParts[0]);
        const minutes = timeParts[1];

        // Convert to 12-hour format
        const period = hours >= 12 ? 'PM' : 'AM';
        const displayHours = hours % 12 || 12;

        return `${displayHours}:${minutes} ${period}`;
    },


    /**
     * Format currency
     */
    formatCurrency: function (amount) {
        return '₹' + parseFloat(amount).toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    },

    /**
     * Initialize patient search dropdown
     */
    initPatientSearch: function (selector, dropdownParent = null, onSelect) {
        const options = {
            ajax: {
                url: `${this.API_BASE}/dashboard/patients`,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        search: params.term || ''
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.data.patients.map(p => ({
                            id: p.patient_id,
                            text: `${p.patient_id} - ${p.name}`,
                            data: p
                        }))
                    };
                }
            },
            placeholder: 'Search by Patient ID or Name',
            minimumInputLength: 0,
            allowClear: true,
            dropdownParent: $('body')
        };

        $(selector).select2(options).on('select2:select', function (e) {
            if (onSelect) onSelect(e.params.data.data);
        });
    },

    /**
     * Initialize doctor search dropdown
     */
    initDoctorSearch: function (selector, dropdownParent = null, onSelect) {
        const options = {
            ajax: {
                url: `${this.API_BASE}/dashboard/doctors`,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        search: params.term || ''
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.data.doctors.map(d => ({
                            id: d.doctor_id,
                            text: `${d.name} - ${d.specialization}`,
                            data: d
                        }))
                    };
                }
            },
            placeholder: 'Search doctor by name or specialization',
            minimumInputLength: 0,
            allowClear: true,
            dropdownParent: $('body')
        };

        $(selector).select2(options).on('select2:select', function (e) {
            if (onSelect) onSelect(e.params.data.data);
        });
    },

    /**
     * Initialize admission search dropdown
     */
    initAdmissionSearch: function (selector, dropdownParent = null, onSelect) {
        const options = {
            ajax: {
                url: `${this.API_BASE}/admissions`,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        search: params.term,
                        status: 'Admitted'
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.data.map(a => ({
                            id: a.admission_id,
                            text: `${a.admission_id} - ${a.patient_name} (Bed: ${a.bed_number})`,
                            patient_id: a.patient_id,
                            doctor_id: a.admitting_doctor_id,
                            admission_date: a.admission_date,
                            bed_number: a.bed_number,
                            patient_name: a.patient_name
                        }))
                    };
                }
            },
            placeholder: 'Search by admission ID or patient name',
            minimumInputLength: 0,
            allowClear: true
        };

        if (dropdownParent) {
            options.dropdownParent = $(dropdownParent);
        }

        $(selector).select2(options).on('select2:select', function (e) {
            if (onSelect) onSelect(e.params.data);
        });
    },

    /**
     * Confirm dialog
     */
    confirm: function (message, callback) {
        if (confirm(message)) {
            callback();
        }
    }
};

// Initialize tooltips and popovers on page load
$(document).ready(function () {
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize date pickers
    if ($.fn.datepicker) {
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        });
    }
});
