<?php
/**
 * ============================================================
 * ipd-summary-api.php — IPD Summary API Reference (Standalone Router)
 * ============================================================
 * Base URL : http://localhost/GM_HMS/controler/api/ipd-summary-api.php
 * Auth     : Session or Bearer token
 * Note     : This is a standalone router (not via central /api/index.php)
 *            It delegates to IpdSummaryController
 * ------------------------------------------------------------
 *
 * 1. GET /ipd-summary-api.php?ipd_no=IPD-001
 *    Returns complete IPD summary for a patient admission
 *
 * 2. PUT /ipd-summary-api.php
 *    Body: { "ipd_no":"IPD-001", ...fields to update... }
 *    Updates IPD summary header info
 *
 * 3. GET /ipd-summary-api.php/daily-reports?ipd_no=IPD-001
 *    Returns all daily progress reports for this IPD
 *
 * 4. POST /ipd-summary-api.php/daily-reports
 *    Body: { "ipd_no":"IPD-001", "report_date":"2026-06-26",
 *            "clinical_notes":"Stable condition", "vitals":{...} }
 *
 * 5. PUT /ipd-summary-api.php/daily-reports
 *    Body: { "id":12, "clinical_notes":"Updated notes" }
 *
 * 6. DELETE /ipd-summary-api.php/daily-reports?ipd_no=IPD-001&date=2026-06-26
 *
 * 7. GET /ipd-summary-api.php/discharge?ipd_no=IPD-001
 *    Returns discharge summary
 *
 * 8. POST /ipd-summary-api.php/discharge  OR  PUT /ipd-summary-api.php/discharge
 *    Body: { "ipd_no":"IPD-001", "condition_at_discharge":"Stable",
 *            "discharge_date":"2026-06-30", "discharge_instructions":"...",
 *            "follow_up_date":"2026-07-07" }
 *
 * 9. GET /ipd-summary-api.php/admissions
 *    Returns all active IPD admissions (for dropdown/search)
 * ------------------------------------------------------------
 */

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in production
ini_set('log_errors', 1);

// Set JSON header
header('Content-Type: application/json');

// Include autoloader
require_once __DIR__ . '/../../core/Autoloader.php';

// Include controller
require_once __DIR__ . '/../api/IpdSummaryController.php';

use GM_HMS\Controllers\api\IpdSummaryController;

try {
    // Initialize controller
    $controller = new IpdSummaryController();
    
    // Get request URI and method
    $requestUri = $_SERVER['REQUEST_URI'];
    $requestMethod = $_SERVER['REQUEST_METHOD'];
    
    // Parse the route
    $basePath = '/GM_HMS/controler/api/ipd-summary-api.php';
    $route = str_replace($basePath, '', $requestUri);
    $route = strtok($route, '?'); // Remove query string
    $route = trim($route, '/');
    
    // Route to appropriate method
    switch ($route) {
        // Active Admissions (for dropdown)
        case 'admissions':
            if ($requestMethod === 'GET') {
                $controller->getActiveAdmissions();
            } else {
                http_response_code(405);
                echo json_encode([
                    'success' => false,
                    'status' => 'error',
                    'error' => 'Method not allowed'
                ]);
            }
            break;
            
        // Daily Reports
        case 'daily-reports':
            switch ($requestMethod) {
                case 'GET':
                    $controller->getDailyReports();
                    break;
                case 'POST':
                    $controller->addDailyReport();
                    break;
                case 'PUT':
                    $controller->updateDailyReport();
                    break;
                case 'DELETE':
                    $controller->deleteDailyReport();
                    break;
                default:
                    http_response_code(405);
                    echo json_encode([
                        'success' => false,
                        'status' => 'error',
                        'error' => 'Method not allowed'
                    ]);
            }
            break;
            
        // Discharge Summary
        case 'discharge':
            switch ($requestMethod) {
                case 'GET':
                    $controller->getDischargeSummary();
                    break;
                case 'POST':
                case 'PUT':
                    $controller->updateDischargeSummary();
                    break;
                default:
                    http_response_code(405);
                    echo json_encode([
                        'success' => false,
                        'status' => 'error',
                        'error' => 'Method not allowed'
                    ]);
            }
            break;
            
        // IPD Summary (Base)
        case '':
        case 'summary':
            switch ($requestMethod) {
                case 'GET':
                    $controller->getIpdSummary();
                    break;
                case 'PUT':
                    $controller->updateIpdSummary();
                    break;
                default:
                    http_response_code(405);
                    echo json_encode([
                        'success' => false,
                        'status' => 'error',
                        'error' => 'Method not allowed'
                    ]);
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'status' => 'error',
                'error' => 'Endpoint not found',
                'available_endpoints' => [
                    'GET /daily-reports?ipd_no=IPD001' => 'Get all daily reports',
                    'POST /daily-reports' => 'Add new daily report',
                    'PUT /daily-reports' => 'Update daily report',
                    'DELETE /daily-reports?ipd_no=IPD001&date=2026-02-11' => 'Delete daily report',
                    'GET /discharge?ipd_no=IPD001' => 'Get discharge summary',
                    'POST /discharge' => 'Update discharge summary',
                    'GET /?ipd_no=IPD001' => 'Get complete IPD summary',
                    'PUT /' => 'Update IPD summary info'
                ]
            ]);
    }
    
} catch (Exception $e) {
    error_log('IPD Summary API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'error' => 'Internal server error',
        'message' => $e->getMessage() // Remove in production
    ]);
}
