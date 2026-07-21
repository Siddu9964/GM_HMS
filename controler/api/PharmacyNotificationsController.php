<?php
/**
 * ============================================================
 * PharmacyNotificationsController — API Reference
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api/pharmacy/notifications
 * Auth     : All endpoints require Auth — All GET, no body
 * ------------------------------------------------------------
 *
 * 1. GET /api/pharmacy/notifications/counts
 *    Response: { low_stock:12, expiry_alerts:5, pending_orders:3 }
 *
 * 2. GET /api/pharmacy/notifications/list
 *    Response: Full notification list with details
 * ------------------------------------------------------------
 */
namespace GM_HMS\Controllers\api;

use Exception;
use GM_HMS\Controllers\BaseController;

/**
 * PharmacyNotificationsController
 * 
 * ==========================================
 * POSTMAN API TESTING GUIDE
 * ==========================================
 * 
 * 1. GET (Get Notification Summary Counts)
 * URL: http://localhost/GM_HMS/api/pharmacy/notifications/counts
 * Method: GET
 * Returns: { "success": true, "data": { "low_stock": 5, "expiry": 2, "pending_indents": 1 } }
 * 
 * 2. GET (Get Full List of Alerts)
 * URL: http://localhost/GM_HMS/api/pharmacy/notifications/list
 * Method: GET
 * Returns: Array of alert objects for UI rendering.
 */
class PharmacyNotificationsController extends BaseController {
    public function __construct() { parent::__construct(); }

    /** GET /api/pharmacy/notifications/counts */
    public function counts(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $row_t = $this->db->fetchOne("SELECT setting_value FROM ph_settings WHERE setting_key = 'low_stock_threshold'");
            $threshold = (int)($row_t['setting_value'] ?? 20);

            $row_e = $this->db->fetchOne("SELECT setting_value FROM ph_settings WHERE setting_key = 'expiry_alert_days'");
            $expiryDays = (int)($row_e['setting_value'] ?? 60);

            $low_stock = $this->db->fetchOne("SELECT COUNT(*) as count FROM ph_product WHERE quantity <= ?", [$threshold]);
            $expiry = $this->db->fetchOne("SELECT COUNT(*) as count FROM ph_product WHERE expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY) AND expiry_date >= CURDATE()", [$expiryDays]);
            $pending_indents = $this->db->fetchOne("SELECT COUNT(*) as count FROM ph_indent_requests WHERE status='pending'");

            $this->respondSuccess([
                'low_stock'       => (int)($low_stock['count'] ?? 0),
                'expiry'          => (int)($expiry['count'] ?? 0),
                'pending_indents' => (int)($pending_indents['count'] ?? 0),
            ]);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** GET /api/pharmacy/notifications/list */
    public function index(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $row_t = $this->db->fetchOne("SELECT setting_value FROM ph_settings WHERE setting_key = 'low_stock_threshold'");
            $threshold = (int)($row_t['setting_value'] ?? 20);

            $row_e = $this->db->fetchOne("SELECT setting_value FROM ph_settings WHERE setting_key = 'expiry_alert_days'");
            $expiryDays = (int)($row_e['setting_value'] ?? 60);

            $items = [];

            // Low stock alerts
            $lowStockItems = $this->db->fetchAll("SELECT product_name, quantity FROM ph_product WHERE quantity <= ? ORDER BY quantity ASC LIMIT 5", [$threshold]);
            foreach ($lowStockItems as $p) {
                $items[] = [
                    'type' => 'danger',
                    'icon' => 'fas fa-exclamation-triangle',
                    'title'=> 'Low Stock: ' . $p['product_name'],
                    'body' => "Only {$p['quantity']} units remaining",
                    'link' => 'inventory_alerts.php'
                ];
            }

            // Expiry alerts
            $expiryItems = $this->db->fetchAll("SELECT product_name, expiry_date FROM ph_product WHERE expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY) AND expiry_date >= CURDATE() ORDER BY expiry_date ASC LIMIT 5", [$expiryDays]);
            foreach ($expiryItems as $p) {
                $days = ceil((strtotime($p['expiry_date']) - time()) / 86400);
                $items[] = [
                    'type' => 'warning',
                    'icon' => 'fas fa-calendar-times',
                    'title'=> 'Expiring: ' . $p['product_name'],
                    'body' => "Expires in {$days} days",
                    'link' => 'inventory_alerts.php'
                ];
            }

            // Pending indents
            $pendingRow = $this->db->fetchOne("SELECT COUNT(*) as count FROM ph_indent_requests WHERE status='pending'");
            $pending = (int)($pendingRow['count'] ?? 0);
            if ($pending > 0) {
                $items[] = [
                    'type' => 'info',
                    'icon' => 'fas fa-clipboard-list',
                    'title'=> "{$pending} Pending Indent Requests",
                    'body' => 'Review and approve requisitions',
                    'link' => 'indent_request.php'
                ];
            }

            $this->respondSuccess($items);
        } catch (Exception $e) { $this->handleException($e); }
    }
}

