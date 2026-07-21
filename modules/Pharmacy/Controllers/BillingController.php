<?php
namespace GM_HMS\Modules\Pharmacy\Controllers;

use Exception;
use GM_HMS\Controllers\BaseController;
use GM_HMS\Modules\Pharmacy\Services\BillingService;
use GM_HMS\Modules\Pharmacy\Services\InvoiceRenderer;

/**
 * BillingController
 * Handles POS API requests
 */
class BillingController extends BaseController {
    private $service;

    public function __construct() {
        parent::__construct();
        $this->service = new BillingService();
    }

    /** GET /api/pharmacy/billing/sponsors */
    public function getSponsors(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $repo = new \GM_HMS\Modules\Pharmacy\Repositories\BillingRepository();
            $this->respondSuccess($repo->getAllSponsors());
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** GET /api/pharmacy/billing/patients?q= */
    public function searchPatients(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $q = $_GET['q'] ?? '';
            $results = $this->service->searchPatients($q);
            $this->respondSuccess($results);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** GET /api/pharmacy/billing/products?q= */
    public function searchProducts(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $q = $_GET['q'] ?? '';
            $results = $this->service->searchProducts($q);
            $this->respondSuccess($results);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** GET /api/pharmacy/billing/prescriptions?patient_id= */
    public function getPrescriptions(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $patientId = $_GET['patient_id'] ?? '';
            if (!$patientId) { $this->respondBadRequest("patient_id is required"); return; }
            
            $results = $this->service->getPatientPrescriptions($patientId);
            $this->respondSuccess($results);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** POST /api/pharmacy/billing/checkout */
    public function checkout(): void {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $data = $this->getJsonInput();
            \GM_HMS\Modules\Pharmacy\Requests\BillingCreateRequest::validate($data);
            
            // Map 'cart' from frontend to 'items' for service
            $data['items'] = $data['cart'];
            unset($data['cart']);
            
            $result = $this->service->processSale($data);
            
            // Fetch items for rendering
            $items = (new \GM_HMS\Modules\Pharmacy\Repositories\SalesRepository())->getSaleItems($result['invoice_no']);
            
            $renderer = new InvoiceRenderer();
            $printedBy = $result['created_by'] ?? $this->currentUser['username'] ?? $this->currentUser['full_name'] ?? '';
            $result['invoice_html'] = $renderer->render($result, $items, $printedBy);
            
            $this->respondCreated($result);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** GET /api/pharmacy/billing/print/{invoice_no} */
    public function printInvoice(string $invoiceNo): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            // Usually we'd fetch the sale from a SalesRepository
            // For now, let's assume we have it or use BillingModel for quick access
            $model = new \GM_HMS\Models\PharmacyBillingModel();
            $master = $this->db->fetchOne("SELECT * FROM ph_sales_master WHERE invoice_no = ?", [$invoiceNo]);
            if (!$master) { $this->respondNotFound("Invoice not found."); return; }
            
            $items = $model->getSaleItems($invoiceNo);
            
            $renderer = new InvoiceRenderer();
            echo $renderer->render($master, $items, $this->currentUser['full_name'] ?? 'Pharmacist');
            exit;
        } catch (Exception $e) { $this->handleException($e); }
    }
}
