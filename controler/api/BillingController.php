<?php
/**
 * ============================================================
 * BillingController — API Reference (Pharmacy POS Billing)
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api/pharmacy/billing
 * Auth     : All endpoints require Auth
 * Note     : This is the Pharmacy Module Billing (not OPD Billing)
 * ------------------------------------------------------------
 *
 * 1. GET /api/pharmacy/billing/patients?q=Anita
 *    Search patients for POS. Min 2 chars.
 *
 * 2. GET /api/pharmacy/billing/products?q=Para
 *    Search products for POS. Returns: id, product_name, selling_price, stock_qty
 *
 * 3. GET /api/pharmacy/billing/prescriptions?patient_id=PID-001
 *    Returns pending prescriptions for a patient
 *
 * 4. GET /api/pharmacy/billing/sponsors
 *    Returns available sponsors/insurance schemes
 *
 * 5. POST /api/pharmacy/billing/checkout
 *    Body:
 *      {
 *        "patient_id":    "PID-20260626-001",
 *        "prescription_id": "PRE-001",
 *        "sub_total":     55,
 *        "discount":      5,
 *        "net_amount":    50,
 *        "payment_mode":  "Cash",
 *        "payment_status":"Paid",
 *        "items": [
 *          { "product_id":15, "qty":10, "unit_price":5.50, "total":55 }
 *        ]
 *      }
 *
 * 6. GET /api/pharmacy/billing/print?sale_id=55
 *    Returns printable invoice data
 * ------------------------------------------------------------
 */
/**
 * Billing Controller
 * 
 * Handles all billing related operations for the reception view.
 * 
 * @package GM_HMS\Controllers\API
 * @version 1.0.0
 */

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/../../models/InvoiceModel.php';

class BillingController extends BaseController {
    private $model;
    
    public function __construct() {
        parent::__construct();
        $this->model = new InvoiceModel();
    }
    
    public function route() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $pathParts = explode('/', trim($path, '/'));
        $action = end($pathParts);
        
        if (strpos($path, '/invoices') !== false && $method === 'GET') {
            $this->getAllInvoices();
        } elseif (strpos($path, '/create') !== false && $method === 'POST') {
            $this->createInvoice();
        } elseif (strpos($path, '/stats') !== false && $method === 'GET') {
            $this->getStats();
        } elseif (strpos($path, '/search-patients') !== false && $method === 'GET') {
            $this->searchPatients();
        } elseif (strpos($path, '/search-doctors') !== false && $method === 'GET') {
            $this->searchDoctors();
        } else {
            $this->respondMethodNotAllowed();
        }
    }

    /**
     * GET /api/billing/invoices
     * Get all invoices with filters
     */
    public function getAllInvoices() {
        $this->restrictMethod('GET');
        
        try {
            // Get filter parameters
            $filters = [];
            if (!empty($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }
            if (!empty($_GET['payment_method'])) {
                $filters['payment_method'] = $_GET['payment_method'];
            }
            if (!empty($_GET['date_from'])) {
                $filters['date_from'] = $_GET['date_from'];
            }
            if (!empty($_GET['date_to'])) {
                $filters['date_to'] = $_GET['date_to'];
            }
            
            // Get invoices from model
            $invoices = $this->model->getAllInvoices($filters);
            $this->respondSuccess($invoices);
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * POST /api/billing/create
     * Create a new invoice
     */
    public function createInvoice() {
        $this->restrictMethod('POST');
        $data = $this->getJsonInput();
        
        try {
            // Basic validation
            if (empty($data['patient_id']) || empty($data['amount'])) {
                $this->respondBadRequest('Patient ID and Amount are required');
            }
            
            // Create invoice using model
            $invoiceId = $this->model->createInvoice($data);
            
            // Get created invoice
            $invoice = $this->model->getInvoiceById($invoiceId);
            
            $this->respondSuccess([
                'invoice_id' => $invoiceId,
                'message' => 'Invoice created successfully',
                'data' => $invoice
            ]);

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/billing/stats
     * Get billing statistics
     */
    public function getStats() {
        $this->restrictMethod('GET');
        
        try {
            // Get statistics from model
            $stats = $this->model->getStatistics();
            $this->respondSuccess($stats);

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/billing/search-patients?term=...
     * Search patients by name or ID
     */
    public function searchPatients() {
        $this->restrictMethod('GET');
        $term = $_GET['term'] ?? '';

        try {
            // Search patients using model
            $patients = $this->model->searchPatients($term);
            
            // Return in Select2 format
            $this->respondSuccess(['results' => $patients]);

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/billing/search-doctors?term=...
     * Search doctors by name
     */
    public function searchDoctors() {
        $this->restrictMethod('GET');
        $term = $_GET['term'] ?? '';

        try {
            // Search doctors using model
            $doctors = $this->model->searchDoctors($term);
            
            $this->respondSuccess(['results' => $doctors]);

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}

$controller = new BillingController();
$controller->route();

