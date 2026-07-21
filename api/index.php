<?php
/**
 * GM_HMS Central API Dispatcher
 * The Professional Entry Point for all API Requests
 */

// Start session for authentication (only if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error handling configuration
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/api_errors.log');

require_once __DIR__ . '/../core/Autoloader.php';

use GM_HMS\Core\Router;

// Set JSON header for all responses
header('Content-Type: application/json');

// Initialize Router
$router = new Router();

// --- DEFINING GLOBAL ROUTES ---

// Diagnostic Route
$router->add('GET', '#^/api/test-routing/?$#', function () {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'API Routing is operational', 'uri' => $_SERVER['REQUEST_URI']]);
    exit;
}, null);

// Patient Routes
$router->add('GET', '#^/api/patients/?$#', 'GM_HMS\Controllers\api\PatientController', 'index');
$router->add('GET', '#^/api/patients/(PID-\d{8}-\d{3})/?$#', 'GM_HMS\Controllers\api\PatientController', 'show');
$router->add('GET', '#^/api/patients/([^/]+)/lab-results/?$#', 'GM_HMS\Controllers\api\PatientController', 'getLabResults');
$router->add('POST', '#^/api/patients/?$#', 'GM_HMS\Controllers\api\PatientController', 'create');
$router->add('PUT', '#^/api/patients/(PID-\d{8}-\d{3})/?$#', 'GM_HMS\Controllers\api\PatientController', 'update');
$router->add('POST', '#^/api/patients/([^/]+)/image/?$#', 'GM_HMS\Controllers\api\PatientController', 'uploadImage');
$router->add('GET', '#^/api/patients/check-duplicate/?$#', 'GM_HMS\Controllers\api\PatientController', 'checkDuplicate');
$router->add('DELETE', '#^/api/patients/(PID-\d{8}-\d{3})/?$#', 'GM_HMS\Controllers\api\PatientController', 'delete');

// Auth Routes
$router->add('POST', '#^/api/auth/login/?$#', 'GM_HMS\Controllers\api\AuthController', 'login');
$router->add('POST', '#^/api/auth/logout/?$#', 'GM_HMS\Controllers\api\AuthController', 'logout');
$router->add('POST', '#^/api/auth/refresh/?$#', 'GM_HMS\Controllers\api\AuthController', 'refresh');
$router->add('GET', '#^/api/auth/me/?$#', 'GM_HMS\Controllers\api\AuthController', 'me');
$router->add('POST', '#^/api/auth/change-password/?$#', 'GM_HMS\Controllers\api\AuthController', 'changePassword');
$router->add('POST', '#^/api/auth/reset-password/?$#', 'GM_HMS\Controllers\api\AuthController', 'resetPassword');

// Admin Info routes
$router->add('GET', '#^/api/admin/opd-summary/?$#', 'GM_HMS\Controllers\api\AdminInfoController', 'getOpdSummary');
$router->add('GET', '#^/api/admin/ipd-summary/?$#', 'GM_HMS\Controllers\api\AdminInfoController', 'getIpdSummary');
$router->add('GET', '#^/api/admin/bed-details/?$#', 'GM_HMS\Controllers\api\AdminInfoController', 'getBedDetails');
$router->add('GET', '#^/api/admin/opd-details/?$#', 'GM_HMS\Controllers\api\AdminInfoController', 'getOpdDetails');
$router->add('GET', '#^/api/admin/ipd-details/?$#', 'GM_HMS\Controllers\api\AdminInfoController', 'getIpdDetails');
$router->add('GET', '#^/api/admin/dashboard-summary/?$#', 'GM_HMS\Controllers\api\AdminInfoController', 'getDashboardSummary');
$router->add('GET', '#^/api/admin/bed-availability/?$#', 'GM_HMS\Controllers\api\AdminInfoController', 'getBedAvailability');
$router->add('GET', '#^/api/admin/active-departments/?$#', 'GM_HMS\\Controllers\\api\\AdminInfoController', 'getActiveDepartments');
$router->add('GET', '#^/api/admin/analytics/?$#', 'GM_HMS\\Controllers\\api\\AdminInfoController', 'getAnalyticsData');

// Department Routes
$router->add('GET', '#^/api/departments/?$#', 'GM_HMS\Controllers\api\DepartmentController', 'index');
$router->add('GET', '#^/api/departments/([^/]+)/?$#', 'GM_HMS\Controllers\api\DepartmentController', 'show');
$router->add('POST', '#^/api/departments/?$#', 'GM_HMS\Controllers\api\DepartmentController', 'create');
$router->add('PUT', '#^/api/departments/([^/]+)/?$#', 'GM_HMS\Controllers\api\DepartmentController', 'update');
$router->add('DELETE', '#^/api/departments/([^/]+)/?$#', 'GM_HMS\Controllers\api\DepartmentController', 'delete');

// Doctor Routes
$router->add('GET', '#^/api/doctors/?$#', 'GM_HMS\Controllers\api\DoctorController', 'index');
$router->add('GET', '#^/api/doctors/([^/]+)/?$#', 'GM_HMS\Controllers\api\DoctorController', 'show');
$router->add('GET', '#^/api/doctors/([^/]+)/analytics/?$#', 'GM_HMS\Controllers\api\DoctorController', 'getAnalytics');
$router->add('GET', '#^/api/doctors/([^/]+)/opd-patients/?$#', 'GM_HMS\Controllers\api\DoctorController', 'getOpdPatients');
$router->add('GET', '#^/api/doctors/([^/]+)/ipd-patients/?$#', 'GM_HMS\Controllers\api\DoctorController', 'getIpdPatients');
$router->add('POST', '#^/api/doctors/?$#', 'GM_HMS\Controllers\api\DoctorController', 'create');
$router->add('PUT', '#^/api/doctors/([^/]+)/?$#', 'GM_HMS\Controllers\api\DoctorController', 'update');
$router->add('POST', '#^/api/doctors/([^/]+)/update-profile/?$#', 'GM_HMS\Controllers\api\DoctorController', 'updateProfile');
$router->add('DELETE', '#^/api/doctors/([^/]+)/?$#', 'GM_HMS\Controllers\api\DoctorController', 'delete');

// Notification Routes
$router->add('GET', '#^/api/notifications/?$#', 'GM_HMS\Controllers\api\NotificationController', 'index');
$router->add('GET', '#^/api/notifications/unread-count/?$#', 'GM_HMS\Controllers\api\NotificationController', 'getUnreadCount');
$router->add('POST', '#^/api/notifications/mark-read/?$#', 'GM_HMS\Controllers\api\NotificationController', 'markAsRead');
$router->add('POST', '#^/api/notifications/?$#', 'GM_HMS\Controllers\api\NotificationController', 'create');
$router->add('DELETE', '#^/api/notifications/([^/]+)/?$#', 'GM_HMS\Controllers\api\NotificationController', 'delete');

// Prescription Routes
$router->add('GET', '#^/api/prescriptions/doctor/([^/]+)/?$#', 'GM_HMS\Controllers\api\PrescriptionController', 'getDoctorPrescriptions');
$router->add('GET', '#^/api/prescriptions/patient/([^/]+)/latest/?$#', 'GM_HMS\Controllers\api\PrescriptionController', 'getLatestByPatient');
$router->add('GET', '#^/api/prescriptions/patient/([^/]+)/?$#', 'GM_HMS\Controllers\api\PrescriptionController', 'getPatientHistoryLog');
$router->add('GET', '#^/api/prescriptions/receptionist/view/([^/]+)/?$#', 'GM_HMS\Controllers\api\PrescriptionController', 'getReceptionistView');
$router->add('GET', '#^/api/prescriptions/([^/]+)/?$#', 'GM_HMS\Controllers\api\PrescriptionController', 'show');
$router->add('GET', '#^/api/prescriptions/?$#', 'GM_HMS\Controllers\api\PrescriptionController', 'listAll');
$router->add('POST', '#^/api/prescriptions/log-print/?$#', 'GM_HMS\Controllers\api\PrescriptionController', 'logPrint');
$router->add('POST', '#^/api/prescriptions/?$#', 'GM_HMS\Controllers\api\PrescriptionController', 'create');

// Appointment Routes
$router->add('GET', '#^/api/appointments/?$#', 'GM_HMS\Controllers\api\AppointmentController', 'index');
$router->add('GET', '#^/api/appointments/stats/?$#', 'GM_HMS\Controllers\api\AppointmentController', 'getStats');
$router->add('GET', '#^/api/appointments/departments/?$#', 'GM_HMS\Controllers\api\AppointmentController', 'getDepartments');
$router->add('GET', '#^/api/appointments/doctors/?$#', 'GM_HMS\Controllers\api\AppointmentController', 'getDoctors');
$router->add('GET', '#^/api/appointments/check-availability/?$#', 'GM_HMS\Controllers\api\AppointmentController', 'checkAvailability');
$router->add('GET', '#^/api/appointments/([^/]+)/?$#', 'GM_HMS\Controllers\api\AppointmentController', 'show');
$router->add('POST', '#^/api/appointments/?$#', 'GM_HMS\Controllers\api\AppointmentController', 'create');
$router->add('PUT', '#^/api/appointments/([^/]+)/?$#', 'GM_HMS\Controllers\api\AppointmentController', 'update');
$router->add('DELETE', '#^/api/appointments/([^/]+)/?$#', 'GM_HMS\Controllers\api\AppointmentController', 'delete');

// Staff Routes
$router->add('GET', '#^/api/staff/designations/?$#', 'GM_HMS\Controllers\api\StaffController', 'getDesignations');
$router->add('POST', '#^/api/staff/([^/]+)/update-profile/?$#', 'GM_HMS\Controllers\api\StaffController', 'updateProfile');
$router->add('GET', '#^/api/staff/?$#', 'GM_HMS\Controllers\api\StaffController', 'index');
$router->add('GET', '#^/api/staff/(\d+)/?$#', 'GM_HMS\Controllers\api\StaffController', 'show');
$router->add('POST', '#^/api/staff/?$#', 'GM_HMS\Controllers\api\StaffController', 'create');
$router->add('PUT', '#^/api/staff/(\d+)/?$#', 'GM_HMS\Controllers\api\StaffController', 'update');
$router->add('DELETE', '#^/api/staff/(\d+)/?$#', 'GM_HMS\Controllers\api\StaffController', 'delete');

// Nurse Shift Assignment Routes
$router->add('GET', '#^/api/nurse-shifts/?$#', 'GM_HMS\Controllers\api\NurseShiftController', 'index');
$router->add('POST', '#^/api/nurse-shifts/?$#', 'GM_HMS\Controllers\api\NurseShiftController', 'create');
$router->add('PUT', '#^/api/nurse-shifts/(\d+)/?$#', 'GM_HMS\Controllers\api\NurseShiftController', 'update');
$router->add('DELETE', '#^/api/nurse-shifts/(\d+)/?$#', 'GM_HMS\Controllers\api\NurseShiftController', 'delete');
$router->add('GET', '#^/api/nurse-shifts/nurses/?$#', 'GM_HMS\Controllers\api\NurseShiftController', 'getNurses');
$router->add('GET', '#^/api/nurse-shifts/wards/?$#', 'GM_HMS\Controllers\api\NurseShiftController', 'getWards');
$router->add('GET', '#^/api/nurse-shifts/floors/?$#', 'GM_HMS\Controllers\api\NurseShiftController', 'getFloors');
$router->add('GET', '#^/api/nurse-shifts/ward-types/?$#', 'GM_HMS\Controllers\api\NurseShiftController', 'getWardTypes');
$router->add('GET', '#^/api/nurse-shifts/rooms/?$#', 'GM_HMS\Controllers\api\NurseShiftController', 'getRooms');

// OPD Management Routes
$router->add('GET', '#^/api/opd/queue/?$#', 'GM_HMS\Controllers\api\OpdController', 'getLiveQueue');
$router->add('GET', '#^/api/opd/stats/?$#', 'GM_HMS\Controllers\api\OpdController', 'getOpdStats');
$router->add('GET', '#^/api/opd/reports/?$#', 'GM_HMS\Controllers\api\OpdController', 'getOpdReports');
$router->add('GET', '#^/api/opd/encounter/(APT-\d{8}-\d{4})/?$#', 'GM_HMS\Controllers\api\OpdController', 'getEncounterDetails');
$router->add('POST', '#^/api/opd/vitals/?$#', 'GM_HMS\Controllers\api\OpdController', 'saveVitals');
$router->add('POST', '#^/api/opd/invoice/?$#', 'GM_HMS\Controllers\api\OpdController', 'createInvoice');
$router->add('POST', '#^/api/opd/lab-request/?$#', 'GM_HMS\Controllers\api\OpdController', 'saveLabRequest');
$router->add('POST', '#^/api/opd/follow-up/?$#', 'GM_HMS\Controllers\api\OpdController', 'saveFollowUp');
$router->add('POST', '#^/api/opd/analyze-symptoms/?$#', 'GM_HMS\Controllers\api\OpdController', 'analyzeSymptoms');

// Patient specialized routes
$router->add('GET', '#^/api/patients/([^/]+)/issues/?$#', 'GM_HMS\Controllers\api\PatientController', 'getIssues');

// ── Vendor Portal: Indents & Quotations ───────────────────────────────────────
$router->add('GET',  '#^/api/vendor/indents/?$#',             'GM_HMS\\Controllers\\api\\VendorController', 'getIndents');
$router->add('POST', '#^/api/vendor/quotations/?$#',          'GM_HMS\\Controllers\\api\\VendorController', 'submitQuotation');

// ── Pharmacy: Dashboard / Alerts / Prescriptions ────────────────────────────
$router->add('GET',  '#^/api/pharmacy/dashboard/?$#',             'GM_HMS\\Modules\\Pharmacy\\Controllers\\DashboardController', 'index');
$router->add('POST', '#^/api/pharmacy/billing/checkout/?$#',      'GM_HMS\\Modules\\Pharmacy\\Controllers\\BillingController', 'checkout');
$router->add('GET',  '#^/api/pharmacy/billing/print/?$#',         'GM_HMS\\Modules\\Pharmacy\\Controllers\\BillingController', 'printInvoice');
$router->add('GET',  '#^/api/pharmacy/billing/patients/?$#',      'GM_HMS\\Modules\\Pharmacy\\Controllers\\BillingController', 'searchPatients');
$router->add('GET',  '#^/api/pharmacy/billing/products/?$#',      'GM_HMS\\Modules\\Pharmacy\\Controllers\\BillingController', 'searchProducts');
$router->add('GET',  '#^/api/pharmacy/sales/?$#',                  'GM_HMS\\Modules\\Pharmacy\\Controllers\\SalesController', 'index');
$router->add('GET',  '#^/api/pharmacy/dashboard-summary/?$#',      'GM_HMS\\Controllers\\api\\PharmacyController', 'getDashboardSummary');
$router->add('GET',  '#^/api/pharmacy/low-stock-alerts/?$#',        'GM_HMS\\Controllers\\api\\PharmacyController', 'getLowStockAlerts');
$router->add('GET',  '#^/api/pharmacy/expiry-alerts/?$#',           'GM_HMS\\Controllers\\api\\PharmacyController', 'getExpiryAlerts');
$router->add('GET',  '#^/api/pharmacy/prescriptions/?$#',           'GM_HMS\\Controllers\\api\\PharmacyController', 'getPrescriptions');

// ── Pharmacy: POS / Billing ──────────────────────────────────────────────────
$router->add('GET',  '#^/api/pharmacy/billing/patients/?$#',        'GM_HMS\\Modules\\Pharmacy\\Controllers\\BillingController', 'searchPatients');
$router->add('GET',  '#^/api/pharmacy/billing/products/?$#',        'GM_HMS\\Modules\\Pharmacy\\Controllers\\BillingController', 'searchProducts');
$router->add('GET',  '#^/api/pharmacy/billing/prescriptions/?$#',    'GM_HMS\\Modules\\Pharmacy\\Controllers\\BillingController', 'getPrescriptions');
$router->add('GET',  '#^/api/pharmacy/billing/sponsors/?$#',         'GM_HMS\\Modules\\Pharmacy\\Controllers\\BillingController', 'getSponsors');

$router->add('POST', '#^/api/pharmacy/billing/checkout/?$#',        'GM_HMS\\Modules\\Pharmacy\\Controllers\\BillingController', 'checkout');

$router->add('GET',  '#^/api/pharmacy/patient-prescription/?$#',    'GM_HMS\\Controllers\\api\\PharmacyController', 'getPatientPrescription');


// ── Pharmacy: Products CRUD ───────────────────────────────────────────────────
$router->add('GET',    '#^/api/pharmacy/products/?$#',              'GM_HMS\\Modules\\Pharmacy\\Controllers\\ProductController', 'index');
$router->add('POST',   '#^/api/pharmacy/products/?$#',              'GM_HMS\\Modules\\Pharmacy\\Controllers\\ProductController', 'create');
$router->add('PUT',    '#^/api/pharmacy/products/([^/]+)/?$#',      'GM_HMS\\Modules\\Pharmacy\\Controllers\\ProductController', 'update');
$router->add('DELETE', '#^/api/pharmacy/products/([^/]+)/?$#',      'GM_HMS\\Modules\\Pharmacy\\Controllers\\ProductController', 'delete');

// ── Pharmacy: Suppliers ───────────────────────────────────────────────────────
$router->add('GET',    '#^/api/pharmacy/suppliers/?$#',             'GM_HMS\\Controllers\\api\\PharmacySuppliersController', 'index');
$router->add('POST',   '#^/api/pharmacy/suppliers/?$#',             'GM_HMS\\Controllers\\api\\PharmacySuppliersController', 'save');
$router->add('DELETE', '#^/api/pharmacy/suppliers/([^/]+)/?$#',     'GM_HMS\\Controllers\\api\\PharmacySuppliersController', 'delete');

// ── Pharmacy: Sales History ───────────────────────────────────────────────────
$router->add('GET',    '#^/api/pharmacy/sales/?$#',                 'GM_HMS\\Modules\\Pharmacy\\Controllers\\SalesController', 'index');
$router->add('GET',    '#^/api/pharmacy/sales/([^/]+)/?$#',         'GM_HMS\\Modules\\Pharmacy\\Controllers\\SalesController', 'show');
$router->add('GET',    '#^/api/pharmacy/sales/([^/]+)/reprint/?$#', 'GM_HMS\\Modules\\Pharmacy\\Controllers\\SalesController', 'reprint');

// ── Pharmacy: GRN (Goods Receipt) ────────────────────────────────────────────
$router->add('GET',    '#^/api/pharmacy/grn/?$#',                   'GM_HMS\\Controllers\\api\\PharmacyGrnController', 'index');
$router->add('GET',    '#^/api/pharmacy/grn/([^/]+)/check-delete/?$#', 'GM_HMS\\Controllers\\api\\PharmacyGrnController', 'checkDelete');
$router->add('GET',    '#^/api/pharmacy/grn-item/([0-9]+)/check-delete/?$#', 'GM_HMS\\Controllers\\api\\PharmacyGrnController', 'checkDeleteItem');
$router->add('GET',    '#^/api/pharmacy/grn/([^/]+)/?$#',           'GM_HMS\\Controllers\\api\\PharmacyGrnController', 'show');
$router->add('POST',   '#^/api/pharmacy/grn/bulk-submit/?$#',       'GM_HMS\\Controllers\\api\\PharmacyGrnController', 'bulkSubmit');
$router->add('POST',   '#^/api/pharmacy/grn/?$#',                   'GM_HMS\\Controllers\\api\\PharmacyGrnController', 'create');
$router->add('DELETE', '#^/api/pharmacy/grn/([^/]+)/?$#',           'GM_HMS\\Controllers\\api\\PharmacyGrnController', 'delete');
$router->add('DELETE', '#^/api/pharmacy/grn-item/([0-9]+)/?$#',      'GM_HMS\\Controllers\\api\\PharmacyGrnController', 'deleteItem');

// ── Pharmacy: Reports ─────────────────────────────────────────────────────────
$router->add('GET',    '#^/api/pharmacy/reports/sales/?$#',         'GM_HMS\\Modules\\Pharmacy\\Controllers\\ReportController', 'sales');
$router->add('GET',    '#^/api/pharmacy/reports/expiry/?$#',        'GM_HMS\\Modules\\Pharmacy\\Controllers\\ReportController', 'expiry');
$router->add('GET',    '#^/api/pharmacy/reports/low-stock/?$#',     'GM_HMS\\Modules\\Pharmacy\\Controllers\\ReportController', 'lowStock');
$router->add('GET',    '#^/api/pharmacy/reports/top-products/?$#',  'GM_HMS\\Modules\\Pharmacy\\Controllers\\ReportController', 'topProducts');
$router->add('GET',    '#^/api/pharmacy/reports/?$#',               'GM_HMS\\Modules\\Pharmacy\\Controllers\\ReportController', 'index');

// ── Pharmacy: Settings ────────────────────────────────────────────────────────
$router->add('GET',    '#^/api/pharmacy/settings/?$#',              'GM_HMS\\Modules\\Pharmacy\\Controllers\\SettingsController', 'index');
$router->add('POST',   '#^/api/pharmacy/settings/?$#',              'GM_HMS\\Modules\\Pharmacy\\Controllers\\SettingsController', 'save');

// ── Pharmacy: Customers ───────────────────────────────────────────────────────
$router->add('GET',    '#^/api/pharmacy/customers/?$#',             'GM_HMS\\Controllers\\api\\PharmacyCustomersController', 'index');
$router->add('POST',   '#^/api/pharmacy/customers/?$#',             'GM_HMS\\Controllers\\api\\PharmacyCustomersController', 'save');
$router->add('DELETE', '#^/api/pharmacy/customers/([^/]+)/?$#',     'GM_HMS\\Controllers\\api\\PharmacyCustomersController', 'delete');

// ── Pharmacy: Indents ─────────────────────────────────────────────────────────
$router->add('GET',    '#^/api/pharmacy/indents/?$#',                  'GM_HMS\\Controllers\\api\\PharmacyIndentController', 'index');
$router->add('POST',   '#^/api/pharmacy/indents/?$#',                  'GM_HMS\\Controllers\\api\\PharmacyIndentController', 'save');
$router->add('POST',   '#^/api/pharmacy/indents/auto-generate/?$#',    'GM_HMS\\Controllers\\api\\PharmacyIndentController', 'autoGenerate');
$router->add('POST',   '#^/api/pharmacy/indents/update-qty/?$#',       'GM_HMS\\Controllers\\api\\PharmacyIndentController', 'updateQty');
$router->add('POST',   '#^/api/pharmacy/indents/bulk-assign/?$#',      'GM_HMS\\Controllers\\api\\PharmacyIndentController', 'bulkAssignVendor');
$router->add('POST',   '#^/api/pharmacy/indents/bulk-status/?$#',      'GM_HMS\\Controllers\\api\\PharmacyIndentController', 'bulkStatus');
$router->add('POST',   '#^/api/pharmacy/indents/bulk-delete/?$#',      'GM_HMS\\Controllers\\api\\PharmacyIndentController', 'bulkDelete');
$router->add('DELETE', '#^/api/pharmacy/indents/([^/]+)/?$#',          'GM_HMS\\Controllers\\api\\PharmacyIndentController', 'delete');

// ── Pharmacy: Notifications ───────────────────────────────────────────────────
$router->add('GET',    '#^/api/pharmacy/notifications/counts/?$#',  'GM_HMS\\Controllers\\api\\PharmacyNotificationsController', 'counts');
$router->add('GET',    '#^/api/pharmacy/notifications/list/?$#',    'GM_HMS\\Controllers\\api\\PharmacyNotificationsController', 'index');

// ── Pharmacy: Purchase Orders ─────────────────────────────────────────────────
$router->add('GET',    '#^/api/pharmacy/purchase-orders/?$#',       'GM_HMS\\Controllers\\api\\PharmacyPurchaseOrderController', 'index');
$router->add('GET',    '#^/api/pharmacy/purchase-orders/([^/]+)/?$#','GM_HMS\\Controllers\\api\\PharmacyPurchaseOrderController', 'show');
$router->add('POST',   '#^/api/pharmacy/purchase-orders/?$#',       'GM_HMS\\Controllers\\api\\PharmacyPurchaseOrderController', 'save');
$router->add('DELETE', '#^/api/pharmacy/purchase-orders/([^/]+)/?$#','GM_HMS\\Controllers\\api\\PharmacyPurchaseOrderController', 'delete');

// ── Pharmacy: Quotations ──────────────────────────────────────────────────────
$router->add('GET',    '#^/api/pharmacy/quotations/?$#',            'GM_HMS\\Controllers\\api\\PharmacyQuotationController', 'index');
$router->add('POST',   '#^/api/pharmacy/quotations/?$#',            'GM_HMS\\Controllers\\api\\PharmacyQuotationController', 'save');
$router->add('DELETE', '#^/api/pharmacy/quotations/([^/]+)/?$#',    'GM_HMS\\Controllers\\api\\PharmacyQuotationController', 'delete');

// ── Pharmacy: Returns ─────────────────────────────────────────────────────────
$router->add('GET',    '#^/api/pharmacy/returns/?$#',               'GM_HMS\\Controllers\\api\\PharmacyReturnsController', 'index');
$router->add('POST',   '#^/api/pharmacy/returns/?$#',               'GM_HMS\\Controllers\\api\\PharmacyReturnsController', 'save');
$router->add('DELETE', '#^/api/pharmacy/returns/([^/]+)/?$#',       'GM_HMS\\Controllers\\api\\PharmacyReturnsController', 'delete');

// ── Pharmacy: Patient Returns (OPD/IPD) ───────────────────────────────────────
$router->add('GET',    '#^/api/pharmacy/patient-returns/?$#',           'GM_HMS\\Controllers\\api\\PharmacyPatientReturnsController', 'index');
$router->add('POST',   '#^/api/pharmacy/patient-returns/?$#',           'GM_HMS\\Controllers\\api\\PharmacyPatientReturnsController', 'save');
$router->add('DELETE', '#^/api/pharmacy/patient-returns/([^/]+)/?$#',   'GM_HMS\\Controllers\\api\\PharmacyPatientReturnsController', 'delete');
$router->add('GET',    '#^/api/pharmacy/patient-returns/receipt/([^/]+)/?$#', 'GM_HMS\\Controllers\\api\\PharmacyPatientReturnsController', 'fetchReceipt');

// ── Pharmacy: Export / Import ─────────────────────────────────────────────────
$router->add('GET',    '#^/api/pharmacy/export/csv/?$#',            'GM_HMS\\Controllers\\api\\PharmacyExportController', 'csv');
$router->add('GET',    '#^/api/pharmacy/export/print/?$#',          'GM_HMS\\Controllers\\api\\PharmacyExportController', 'printView');
$router->add('POST',   '#^/api/pharmacy/import/products/?$#',       'GM_HMS\\Controllers\\api\\PharmacyImportController', 'products');


// Consultation Routes
$router->add('POST', '#^/api/consultations/translate-audio/?$#', 'GM_HMS\\Controllers\\api\\ConsultationController', 'translateAudio');
$router->add('GET', '#^/api/consultations/?$#', 'GM_HMS\\Controllers\\api\\ConsultationController', 'index');
$router->add('GET', '#^/api/consultations/([^/]+)/?$#', 'GM_HMS\\Controllers\\api\\ConsultationController', 'show');
$router->add('POST', '#^/api/consultations/?$#', 'GM_HMS\\Controllers\\api\\ConsultationController', 'create');
$router->add('PUT', '#^/api/consultations/([^/]+)/?$#', 'GM_HMS\\Controllers\\api\\ConsultationController', 'update');
$router->add('DELETE', '#^/api/consultations/([^/]+)/?$#', 'GM_HMS\\Controllers\\api\\ConsultationController', 'delete');

// Reception Routes (Centralized)
$router->add('POST', '#^/api/reception/profile/update/?$#', 'GM_HMS\Controllers\api\ReceptionController', 'updateProfile');
$router->add('GET', '#^/api/reception/dashboard/summary/?$#', 'GM_HMS\Controllers\api\ReceptionController', 'getDashboardSummary');
$router->add('GET', '#^/api/reception/dashboard/today-appointments/?$#', 'GM_HMS\Controllers\api\ReceptionController', 'getTodayAppointments');
$router->add('GET', '#^/api/reception/dashboard/recent-patients/?$#', 'GM_HMS\Controllers\api\ReceptionController', 'getRecentPatients');
$router->add('GET', '#^/api/referred-doctors/?$#', 'GM_HMS\Controllers\api\ReceptionController', 'getReferredDoctors');
$router->add('POST', '#^/api/referred-doctors/?$#', 'GM_HMS\Controllers\api\ReceptionController', 'createReferredDoctor');

// OPD Billing Routes
$router->add('GET', '#^/api/billing/opd/?$#', 'GM_HMS\Controllers\api\OpdBillingController', 'getAllBills');
$router->add('GET', '#^/api/billing/opd/stats/?$#', 'GM_HMS\Controllers\api\OpdBillingController', 'getStatistics');
$router->add('GET', '#^/api/billing/stats/daily/?$#', 'GM_HMS\Controllers\api\OpdBillingController', 'getDailyStats');
$router->add('GET', '#^/api/billing/opd/consultation-fee/?$#', 'GM_HMS\Controllers\api\OpdBillingController', 'getConsultationFee');
$router->add('GET', '#^/api/billing/opd/search-patients/?$#', 'GM_HMS\Controllers\api\OpdBillingController', 'searchPatients');
$router->add('GET', '#^/api/billing/opd/services/?$#', 'GM_HMS\Controllers\api\OpdBillingController', 'getServices');
$router->add('GET', '#^/api/billing/opd/([^/]+)/?$#', 'GM_HMS\Controllers\api\OpdBillingController', 'getBillById');
$router->add('PUT', '#^/api/billing/opd/([^/]+)/?$#', 'GM_HMS\Controllers\api\OpdBillingController', 'updateBill');
$router->add('DELETE', '#^/api/billing/opd/([^/]+)/?$#', 'GM_HMS\Controllers\api\OpdBillingController', 'deleteBill');
$router->add('POST', '#^/api/billing/opd/?$#', 'GM_HMS\Controllers\api\OpdBillingController', 'createBill');
$router->add('POST', '#^/api/billing/create/?$#', 'GM_HMS\Controllers\api\OpdBillingController', 'createBill');
$router->add('POST', '#^/api/billing/opd/payment/?$#', 'GM_HMS\Controllers\api\OpdBillingController', 'recordPayment');
$router->add('POST', '#^/api/billing/opd/referral/?$#', 'GM_HMS\Controllers\api\OpdBillingController', 'saveReferral');
$router->add('GET', '#^/api/billing/opd/referral/search/?$#', 'GM_HMS\Controllers\api\OpdBillingController', 'searchReferrals');
$router->add('GET', '#^/api/billing/opd/sponsor/search/?$#', 'GM_HMS\Controllers\api\OpdBillingController', 'searchSponsors');

// Laboratory Routes
$router->add('GET', '#^/api/laboratory/services/?$#', 'GM_HMS\Modules\Laboratory\Controllers\LaboratoryController', 'getServices');
$router->add('POST', '#^/api/laboratory/services/?$#', 'GM_HMS\Modules\Laboratory\Controllers\LaboratoryController', 'createService');
$router->add('PUT', '#^/api/laboratory/services/([^/]+)/([^/]+)/?$#', 'GM_HMS\Modules\Laboratory\Controllers\LaboratoryController', 'updateService');
$router->add('DELETE', '#^/api/laboratory/services/([^/]+)/([^/]+)/?$#', 'GM_HMS\Modules\Laboratory\Controllers\LaboratoryController', 'deleteService');

// LIS (Laboratory Information System) Routes
$router->add('GET',  '#^/api/laboratory/dashboard/?$#',                         'GM_HMS\Modules\Laboratory\Controllers\LaboratoryController', 'getDashboard');
$router->add('GET',  '#^/api/laboratory/orders/?$#',                            'GM_HMS\Modules\Laboratory\Controllers\LaboratoryController', 'getOrders');
$router->add('POST', '#^/api/laboratory/orders/?$#',                            'GM_HMS\Modules\Laboratory\Controllers\LaboratoryController', 'createOrder');
$router->add('GET',  '#^/api/laboratory/orders/([^/]+)/?$#',                    'GM_HMS\Modules\Laboratory\Controllers\LaboratoryController', 'getOrder');
$router->add('PUT',  '#^/api/laboratory/orders/([^/]+)/?$#',                    'GM_HMS\Modules\Laboratory\Controllers\LaboratoryController', 'updateOrder');
$router->add('DELETE','#^/api/laboratory/orders/([^/]+)/?$#',                   'GM_HMS\Modules\Laboratory\Controllers\LaboratoryController', 'deleteOrder');
$router->add('PUT',  '#^/api/laboratory/orders/([^/]+)/status/?$#',             'GM_HMS\Modules\Laboratory\Controllers\LaboratoryController', 'updateOrderStatus');
$router->add('GET',  '#^/api/laboratory/orders/([^/]+)/result/?$#',             'GM_HMS\Modules\Laboratory\Controllers\LaboratoryController', 'getResult');
$router->add('POST', '#^/api/laboratory/orders/([^/]+)/result/?$#',             'GM_HMS\Modules\Laboratory\Controllers\LaboratoryController', 'saveResult');
$router->add('GET',  '#^/api/laboratory/prescribed-tests/?$#',                  'GM_HMS\Modules\Laboratory\Controllers\LaboratoryController', 'getPrescribedTests');

// IPD Summary Routes
$router->add('GET', '#^/api/ipd-summary/draft/?$#', 'GM_HMS\Controllers\api\IpdSummaryController', 'getDraft');
$router->add('GET', '#^/api/ipd-summary/?$#', 'GM_HMS\Controllers\api\IpdSummaryController', 'index');
$router->add('POST', '#^/api/ipd-summary/?$#', 'GM_HMS\Controllers\api\IpdSummaryController', 'save');
$router->add('DELETE', '#^/api/ipd-summary/?$#', 'GM_HMS\Controllers\api\IpdSummaryController', 'delete');

// IPD Clinical Routes (Visits, Meds, Invs)
$router->add('GET', '#^/api/ipd-clinical/visits/?$#', 'GM_HMS\Controllers\api\IpdClinicalController', 'getVisits');
$router->add('POST', '#^/api/ipd-clinical/visits/?$#', 'GM_HMS\Controllers\api\IpdClinicalController', 'addVisit');
$router->add('GET', '#^/api/ipd-clinical/medications/?$#', 'GM_HMS\Controllers\api\IpdClinicalController', 'getMedications');
$router->add('POST', '#^/api/ipd-clinical/medications/?$#', 'GM_HMS\Controllers\api\IpdClinicalController', 'addMedication');
$router->add('GET', '#^/api/ipd-clinical/investigations/?$#', 'GM_HMS\Controllers\api\IpdClinicalController', 'getInvestigations');
$router->add('POST', '#^/api/ipd-clinical/investigations/?$#', 'GM_HMS\Controllers\api\IpdClinicalController', 'addInvestigation');

// IPD Bridge (Future improvement: move IPD into this router)
// $router->add('ANY', '#^/api/ipd/(.*)#', 'GM_HMS\Controllers\api\IpdBridgeController', 'handle');


// --- DISPATCH REQUEST ---

// Ensure we don't output HTML errors
ini_set('display_errors', 0);
error_reporting(E_ALL);

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting()& $errno))
        return;
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
    'success' => false,
    'error' => 'PHP Error: ' . $errstr,
    'file' => $errfile,
    'line' => $errline
    ]);
    exit;
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json');
        }
        echo json_encode([
        'success' => false,
        'error' => 'Fatal Error: ' . $error['message'],
        'file' => $error['file'],
        'line' => $error['line']
        ]);
    }
});

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove query string for routing
$path = parse_url($requestUri, PHP_URL_PATH);

// Robustly find the API path by stripping everything before /api/
if (($apiPos = stripos($path, '/api/')) !== false) {
    $path = substr($path, $apiPos);
}

error_log("[DEBUG] Routing - Processed Path: $path, Method: $requestMethod");

// Remove index.php from routing path
$path = str_replace('/api/index.php', '', $path);

$route = $router->dispatch($path, $requestMethod);

if ($route) {
    try {
        $handler = $route['controller'];
        $action = $route['action'];
        $params = $route['params'];

        if (is_callable($handler)) {
            // It's a callback (diagnostic routes, etc.)
            call_user_func_array($handler, $params);
        }
        else {
            // It's a controller string
            if (!class_exists($handler)) {
                throw new Exception("Controller $handler not found");
            }

            $controller = new $handler();
            if (!method_exists($controller, $action)) {
                throw new Exception("Action $action not found in controller $handler");
            }

            call_user_func_array([$controller, $action], $params);
        }

    }
    catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
else {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => "Endpoint not found: [$requestMethod] $path"]);
}
