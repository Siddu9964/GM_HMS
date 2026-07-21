<?php
/**
 * ============================================================
 * PharmacyController — API Reference (Core Dashboard)
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api
 * Auth     : All endpoints require Auth (Session or Bearer token)
 * ------------------------------------------------------------
 *
 * 1. GET /api/pharmacy/dashboard-summary
 *    Response: { low_stock_count, expiry_alerts_count, today_sales, pending_prescriptions }
 *
 * 2. GET /api/pharmacy/low-stock-alerts
 *    Response: Products where qty < reorder_level
 *
 * 3. GET /api/pharmacy/expiry-alerts
 *    Response: Products expiring within 90 days
 *
 * 4. GET /api/pharmacy/prescriptions
 *    Response: Pending (unfulfilled) prescriptions
 *
 * 5. GET /api/pharmacy/patient-prescription?patient_id=PID-001
 *    Returns latest prescription for a patient
 * ------------------------------------------------------------
 */
namespace GM_HMS\Controllers\api;

use Exception;
use GM_HMS\Controllers\BaseController;
use GM_HMS\Models\PharmacyModel;
use GM_HMS\Models\PharmacyBillingModel;

/**
 * PharmacyController
 * Handles all Pharmacy API endpoints
 *
 * Routes registered in /api/index.php:
 *   GET  /api/pharmacy/dashboard-summary
 *   GET  /api/pharmacy/low-stock-alerts
 *   GET  /api/pharmacy/expiry-alerts
 *   GET  /api/pharmacy/prescriptions
 *   GET  /api/pharmacy/patients/all
 *   GET  /api/pharmacy/search-patients?q=
 *   GET  /api/pharmacy/patient-prescription?patient_id=
 *   GET  /api/pharmacy/products/search?q=
 *   POST /api/pharmacy/billing/create
 */
class PharmacyController extends BaseController {

    private PharmacyModel        $model;
    private PharmacyBillingModel $billing;

    public function __construct() {
        parent::__construct();
        $this->model   = new PharmacyModel();
        $this->billing = new PharmacyBillingModel();
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // DASHBOARD  GET /api/pharmacy/dashboard-summary
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getDashboardSummary(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $this->respondSuccess([
                'stats'          => $this->model->getDashboardStats(),
                'expiring_list'  => $this->model->getExpiringProductsList(5),
                'low_stock_list' => $this->model->getLowStockProductsList(5),
            ]);
        } catch (Exception $e) { $this->handleException($e); }
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // LOW STOCK  GET /api/pharmacy/low-stock-alerts
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getLowStockAlerts(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $this->respondSuccess($this->model->getAllProductsSortedByStock());
        } catch (Exception $e) { $this->handleException($e); }
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // EXPIRY  GET /api/pharmacy/expiry-alerts
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getExpiryAlerts(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $this->respondSuccess($this->model->getAllProductsSortedByExpiry());
        } catch (Exception $e) { $this->handleException($e); }
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // PRESCRIPTIONS  GET /api/pharmacy/prescriptions
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getPrescriptions(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $this->respondSuccess($this->model->getAllPrescriptions());
        } catch (Exception $e) { $this->handleException($e); }
    }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // PATIENT ENDPOINTS
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

    /**
     * GET /api/pharmacy/patients/all
     * Returns all patients (id, name, phone, age, sex) for instant client-side search
     */
    public function getAllPatients(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $this->respondSuccess($this->billing->getAllPatients());
        } catch (Exception $e) { $this->handleException($e); }
    }

    /**
     * GET /api/pharmacy/search-patients?q=
     * Search patients by ID, name, or phone
     */
    public function searchPatients(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) { $this->respondSuccess([]); return; }
        try {
            $this->respondSuccess($this->billing->searchPatients($q));
        } catch (Exception $e) { $this->handleException($e); }
    }

    /**
     * GET /api/pharmacy/patient-prescription?patient_id=
     * Returns full patient info + consultations with soap_plan medicines
     */
    public function getPatientPrescription(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        $pid = trim($_GET['patient_id'] ?? '');
        if (!$pid) { $this->respondBadRequest('patient_id is required'); return; }
        try {
            $patient = $this->billing->getPatientById($pid);
            if (!$patient) { $this->respondNotFound("Patient {$pid} not found"); return; }

            $consultations = $this->billing->getPatientConsultations($pid);

            $this->respondSuccess([
                'patient'       => $patient,
                'consultations' => $consultations,
            ]);
        } catch (Exception $e) { $this->handleException($e); }
    }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // PRODUCT / MEDICINE SEARCH
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

    /**
     * GET /api/pharmacy/products/search?q=
     * Search medicines from ph_product (in-stock only)
     */
    public function searchProducts(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 1) { $this->respondSuccess([]); return; }
        try {
            $this->respondSuccess($this->billing->searchProducts($q));
        } catch (Exception $e) { $this->handleException($e); }
    }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // SPONSOR ENDPOINTS
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    public function getSponsors(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $this->respondSuccess($this->billing->getAllSponsors());
        } catch (Exception $e) { $this->handleException($e); }
    }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // BILLING  POST /api/pharmacy/billing/create
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

    /**
     * POST /api/pharmacy/billing/create
     * JSON Body:
     * {
     *   "cart": [{ product_id, product_name, batch_no, qty, rate, discount_pct, tax_pct }],
     *   "customer_name":   "Patient Name",
     *   "customer_phone":  "9876543210",
     *   "payment_method":  "cash|card|upi|insurance",
     *   "paid_amount":     500,
     *   "discount_amount": 0
     * }
     */
    public function createBill(): void {
        $this->restrictMethod('POST');
        $this->requireAuth();
        $body = $this->getJsonInput();

        $cart          = $body['cart']             ?? [];
        $customerId    = trim($body['customer_id']     ?? '');
        $customerName  = trim($body['customer_name']   ?? 'Walk-in Customer');
        $customerPhone = trim($body['customer_phone']  ?? '');
        $payMethod     = $body['payment_method']   ?? 'cash';
        $sponsorId     = $body['sponsor_id']       ?? null;
        $paidAmt       = (float)($body['paid_amount']    ?? 0);
        $discountAmt   = (float)($body['discount_amount'] ?? 0);

        if (empty($cart)) { $this->respondBadRequest('Cart is empty'); return; }

        // â”€â”€ Server-side recalculation â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $subtotal = $taxTotal = $grandTotal = 0;
        $itemRows = [];

        foreach ($cart as $c) {
            $pid   = trim($c['product_id']   ?? '');
            $pnm   = trim($c['product_name'] ?? '');
            $qty   = (int)($c['qty']         ?? 0);
            $rate  = (float)($c['rate']      ?? 0);
            $disc  = (float)($c['discount_pct'] ?? 0);
            $tax   = (float)($c['tax_pct']   ?? 0);
            $batch = trim($c['batch_no']     ?? '');
            if (empty($pid) || $qty <= 0) continue;

            $gross   = $qty * $rate;
            $discAmt = $gross * $disc / 100;
            $taxAmt  = ($gross - $discAmt) * $tax / 100;
            $sub     = $gross - $discAmt + $taxAmt;

            $subtotal   += $gross;
            $taxTotal   += $taxAmt;
            $grandTotal += $sub;

            $itemRows[] = [
                'product_id'       => $pid,
                'product_name'     => $pnm,
                'batch_no'         => $batch,
                'qty'              => $qty,
                'rate'             => $rate,
                'discount_percent' => $disc,
                'tax_percent'      => $tax,
                'tax_amount'       => $taxAmt,
                'subtotal'         => $sub,
            ];
        }

        if (empty($itemRows)) { $this->respondBadRequest('No valid items in cart'); return; }

        $grandTotal   -= $discountAmt;
        $balanceFinal  = $paidAmt - $grandTotal;
        $conn          = $this->billing->getConnection();

        try {
            mysqli_begin_transaction($conn);

            // â”€â”€ Stock check & deduct â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            foreach ($itemRows as $item) {
                $available = $this->billing->getProductStock($item['product_id']);
                if ($available < $item['qty']) {
                    mysqli_rollback($conn);
                    $this->respondBadRequest(
                        "Insufficient stock for {$item['product_name']}. Available: {$available}"
                    );
                    return;
                }
                $this->billing->deductStock($item['product_id'], $item['qty']);
            }

            // â”€â”€ Save bill â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            $invoice_no = $this->billing->generateInvoiceNo();

            $this->billing->insertSalesMaster([
                'invoice_no'      => $invoice_no,
                'customer_id'     => $customerId,
                'customer_name'   => $customerName,
                'customer_phone'  => $customerPhone,
                'subtotal'        => $subtotal,
                'discount_amount' => $discountAmt,
                'tax_total'       => $taxTotal,
                'grand_total'     => $grandTotal,
                'paid_amount'     => $paidAmt,
                'balance'         => $balanceFinal,
                'payment_method'  => $payMethod,
                'sponsor_id'      => $sponsorId,
            ]);

            foreach ($itemRows as $item) {
                $this->billing->insertSalesItem($invoice_no, $customerId, $item);
            }

            mysqli_commit($conn);

            // â”€â”€ Build invoice HTML â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            $printedBy   = $this->currentUser['full_name'] ?? $this->currentUser['username'] ?? 'Pharmacist';
            $invoiceHtml = $this->billing->generateInvoiceHTML(
                [
                    'invoice_no'      => $invoice_no,
                    'customer_name'   => $customerName,
                    'customer_phone'  => $customerPhone,
                    'payment_method'  => $payMethod,
                    'subtotal'        => $subtotal,
                    'discount_amount' => $discountAmt,
                    'tax_total'       => $taxTotal,
                    'grand_total'     => $grandTotal,
                    'paid_amount'     => $paidAmt,
                    'balance'         => $balanceFinal
                ], 
                $itemRows, 
                $printedBy
            );

            $this->respondSuccess([
                'invoice_no'   => $invoice_no,
                'grand_total'  => $grandTotal,
                'invoice_html' => $invoiceHtml,
            ], 'Bill created successfully');

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $this->handleException($e);
        }
    }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // PRIVATE HELPERS
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

}


