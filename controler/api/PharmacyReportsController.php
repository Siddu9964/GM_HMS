<?php
/**
 * ============================================================
 * PharmacyReportsController — API Reference
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api/pharmacy/reports
 * Auth     : All endpoints require Auth — All GET, no body
 * ------------------------------------------------------------
 *
 * 1. GET /api/pharmacy/reports                  — Dashboard report index
 * 2. GET /api/pharmacy/reports/sales            — Sales report (date_from, date_to params)
 * 3. GET /api/pharmacy/reports/expiry           — Expiry report (days_before param)
 * 4. GET /api/pharmacy/reports/low-stock        — Low stock report
 * 5. GET /api/pharmacy/reports/top-products     — Top selling products (limit param)
 * ------------------------------------------------------------
 */
namespace GM_HMS\Controllers\api;

use Exception;
use GM_HMS\Controllers\BaseController;

/**
 * PharmacyReportsController
 * Routes:
 *   GET /api/pharmacy/reports/sales
 *   GET /api/pharmacy/reports/purchase
 *   GET /api/pharmacy/reports/expiry
 *   GET /api/pharmacy/reports/low-stock
 *   GET /api/pharmacy/reports/top-products
 *   GET /api/pharmacy/reports/tax
 */
class PharmacyReportsController extends BaseController {
    public function __construct() { parent::__construct(); }
    
    /** GET /api/pharmacy/reports?type=sales|expiry|low-stock|... */
    public function index(): void {
        $type = $_GET['type'] ?? '';
        switch ($type) {
            case 'sales':        $this->sales(); break;
            case 'expiry':       $this->expiry(); break;
            case 'low-stock':    $this->lowStock(); break;
            case 'top-products': $this->topProducts(); break;
            case 'tax':          $this->tax(); break;
            default:
                $this->respondError('Invalid report type');
        }
    }

    /** GET /api/pharmacy/reports/sales?date_from=&date_to= */
    public function sales(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $from = $_GET['date_from'] ?? date('Y-m-01');
            $to   = $_GET['date_to']   ?? date('Y-m-d');
            $this->respondSuccess($this->db->fetchAll(
                "SELECT invoice_date,
                        COUNT(*) AS total_bills,
                        SUM(grand_total) AS total_amount,
                        SUM(discount_amount) AS total_discount,
                        SUM(tax_total) AS total_tax
                 FROM ph_sales_master
                 WHERE invoice_date BETWEEN ? AND ?
                 GROUP BY invoice_date ORDER BY invoice_date DESC",
                [$from, $to]
            ));
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** GET /api/pharmacy/reports/expiry?days=60 */
    public function expiry(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $days = (int)($_GET['days'] ?? 60);
            $this->respondSuccess($this->db->fetchAll(
                "SELECT product_id, product_name, batch_number, expiry_date, quantity
                 FROM ph_product
                 WHERE expiry_date IS NOT NULL AND expiry_date != '0000-00-00'
                   AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                 ORDER BY expiry_date ASC",
                [$days]
            ));
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** GET /api/pharmacy/reports/low-stock?threshold=20 */
    public function lowStock(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $threshold = (int)($_GET['threshold'] ?? 20);
            $this->respondSuccess($this->db->fetchAll(
                "SELECT product_id, product_name, batch_number, quantity, expiry_date
                 FROM ph_product WHERE quantity <= ? ORDER BY quantity ASC",
                [$threshold]
            ));
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** GET /api/pharmacy/reports/top-products?limit=10&date_from=&date_to= */
    public function topProducts(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $from  = $_GET['date_from'] ?? date('Y-m-01');
            $to    = $_GET['date_to']   ?? date('Y-m-d');
            $limit = (int)($_GET['limit'] ?? 10);
            $this->respondSuccess($this->db->fetchAll(
                "SELECT si.product_name, SUM(si.qty) AS total_qty, SUM(si.total) AS total_amount
                 FROM ph_sales_items si
                 JOIN ph_sales_master sm ON sm.invoice_no = si.invoice_no
                 WHERE sm.invoice_date BETWEEN ? AND ?
                 GROUP BY si.product_name ORDER BY total_qty DESC LIMIT ?",
                [$from, $to, $limit]
            ));
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** GET /api/pharmacy/reports/tax?date_from=&date_to= */
    public function tax(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $from = $_GET['date_from'] ?? date('Y-m-01');
            $to   = $_GET['date_to']   ?? date('Y-m-d');
            $this->respondSuccess($this->db->fetchAll(
                "SELECT si.tax_percent,
                        SUM(si.qty * si.rate) AS taxable_amount,
                        SUM(si.tax_amount) AS tax_collected,
                        COUNT(DISTINCT sm.invoice_no) AS invoice_count
                 FROM ph_sales_items si
                 JOIN ph_sales_master sm ON sm.invoice_no = si.invoice_no
                 WHERE sm.invoice_date BETWEEN ? AND ?
                 GROUP BY si.tax_percent ORDER BY si.tax_percent",
                [$from, $to]
            ));
        } catch (Exception $e) { $this->handleException($e); }
    }
}

