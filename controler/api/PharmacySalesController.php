<?php
/**
 * ============================================================
 * PharmacySalesController — API Reference
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api/pharmacy/sales
 * Auth     : All endpoints require Auth
 * ------------------------------------------------------------
 *
 * 1. GET /api/pharmacy/sales
 *    Query: date_from, date_to, patient_id, payment_mode
 *    Response: All sales history
 *
 * 2. GET /api/pharmacy/sales/{id}
 *    Returns full sale details with line items
 *
 * 3. GET /api/pharmacy/sales/{id}/reprint
 *    Returns printable receipt data
 * ------------------------------------------------------------
 */
namespace GM_HMS\Controllers\api;

use Exception;
use GM_HMS\Controllers\BaseController;
use GM_HMS\Models\PharmacyBillingModel;

/**
 * PharmacySalesController
 * Routes:
 *   GET /api/pharmacy/sales              â†’ list (with date filters)
 *   GET /api/pharmacy/sales/{invoice_no} â†’ view single invoice
 *   GET /api/pharmacy/sales/{invoice_no}/reprint â†’ get invoice HTML for reprint
 */
class PharmacySalesController extends BaseController {
    private $billing;
    public function __construct() { 
        parent::__construct(); 
        $this->billing = new PharmacyBillingModel();
    }

    /** GET /api/pharmacy/sales?date_from=&date_to=&search=&limit= */
    public function index(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $search    = trim($_GET['search']    ?? '');
            $dateFrom  = trim($_GET['date_from'] ?? '');
            $dateTo    = trim($_GET['date_to']   ?? '');
            $limit     = min((int)($_GET['limit'] ?? 100), 500);
            $where = ['1=1']; $params = [];
            if ($dateFrom) { $where[] = 'invoice_date >= ?'; $params[] = $dateFrom; }
            if ($dateTo)   { $where[] = 'invoice_date <= ?'; $params[] = $dateTo; }
            if ($search)   {
                $like = '%' . $search . '%';
                $where[]  = '(customer_name LIKE ? OR invoice_no LIKE ? OR customer_phone LIKE ?)';
                $params   = array_merge($params, [$like, $like, $like]);
            }
            $resultsParams = array_merge($params, [$limit]);
            $results = $this->db->fetchAll(
                "SELECT invoice_no, invoice_date, invoice_time, customer_name, customer_phone,
                        subtotal, discount_amount, tax_total, grand_total, 
                        (SELECT COUNT(*) FROM ph_sales_items WHERE ph_sales_items.invoice_no = ph_sales_master.invoice_no) as item_count,
                        payment_method, status, id
                 FROM ph_sales_master
                 WHERE " . implode(' AND ', $where) . "
                 ORDER BY invoice_date DESC, invoice_time DESC LIMIT ?",
                $resultsParams
            );

            // Calculate stats for the filtered period
            $statsSql = "SELECT COUNT(*) as total_bills, SUM(grand_total) as total_sales, SUM(tax_total) as total_tax, SUM(discount_amount) as total_disc 
                         FROM ph_sales_master WHERE " . implode(' AND ', $where);
            $stats = $this->db->fetchOne($statsSql, $params);

            $this->respondSuccess([
                'data'  => $results,
                'stats' => [
                    'total_bills' => (int)($stats['total_bills'] ?? 0),
                    'total_sales' => (float)($stats['total_sales'] ?? 0),
                    'total_tax'   => (float)($stats['total_tax']   ?? 0),
                    'total_disc'  => (float)($stats['total_disc']  ?? 0),
                ]
            ]);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** GET /api/pharmacy/sales/{id} */
    public function show(string $id): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $master = $this->db->fetchOne(
                "SELECT * FROM ph_sales_master WHERE id = ?", [$id]
            );
            if (!$master) { $this->respondNotFound("Sale record not found"); return; }
            
            $items = $this->db->fetchAll(
                "SELECT * FROM ph_sales_items WHERE invoice_no = ?", [$master['invoice_no']]
            );
            $this->respondSuccess(['sale' => $master, 'items' => $items]);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** GET /api/pharmacy/sales/{id}/reprint */
    public function reprint(string $id): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $master = $this->db->fetchOne("SELECT * FROM ph_sales_master WHERE id = ?", [$id]);
            if (!$master) { $this->respondNotFound("Invoice not found"); return; }
            $items = $this->db->fetchAll("SELECT * FROM ph_sales_items WHERE invoice_no = ?", [$master['invoice_no']]);
            
            $html = $this->billing->generateInvoiceHTML($master, $items);
            $this->respondSuccess(['html' => $html]);
        } catch (Exception $e) { $this->handleException($e); }
    }
}

