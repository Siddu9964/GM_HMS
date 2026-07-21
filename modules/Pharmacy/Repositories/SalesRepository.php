<?php
namespace GM_HMS\Modules\Pharmacy\Repositories;

use GM_HMS\Database\SecureDatabase;

/**
 * SalesRepository
 * Handles retrieval of sales history and details
 */
class SalesRepository {
    private $db;

    public function __construct() {
        $this->db = SecureDatabase::getInstance();
    }

    /**
     * Get sales history with filters
     */
    public function getSalesList(array $filters = [], int $limit = 100): array {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['date_from'])) {
            $where[] = 'invoice_date >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'invoice_date <= ?';
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $where[] = '(invoice_no LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ?)';
            $params[] = $like; $params[] = $like; $params[] = $like;
        }
        if (!empty($filters['pharmacist'])) {
            $where[] = 'created_by = ?';
            $params[] = $filters['pharmacist'];
        }

        $params[] = $limit;
        return $this->db->fetchAll(
            "SELECT sm.*, 
                    (SELECT COUNT(*) FROM ph_sales_items si WHERE si.invoice_no = sm.invoice_no) as item_count
             FROM ph_sales_master sm
             WHERE " . implode(' AND ', $where) . " 
             ORDER BY sm.invoice_date DESC, sm.invoice_time DESC LIMIT ?",
            $params
        );
    }

    /**
     * Get sale master by ID
     */
    public function getById(int $id): ?array {
        return $this->db->fetchOne(
            "SELECT * FROM ph_sales_master WHERE id = ?",
            [$id]
        ) ?: null;
    }

    /**
     * Get sales stats for a period
     */
    public function getStats(array $filters = []): array {
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['date_from'])) { $where[] = 'invoice_date >= ?'; $params[] = $filters['date_from']; }
        if (!empty($filters['date_to']))   { $where[] = 'invoice_date <= ?'; $params[] = $filters['date_to']; }
        if (!empty($filters['pharmacist'])){ $where[] = 'created_by = ?'; $params[] = $filters['pharmacist']; }

        return $this->db->fetchOne(
            "SELECT 
                COUNT(*) as total_bills,
                SUM(grand_total) as total_sales,
                SUM(tax_total) as total_tax,
                SUM(discount_amount) as total_disc
             FROM ph_sales_master 
             WHERE " . implode(' AND ', $where),
            $params
        );
    }

    /**
     * Get sale items by invoice number
     */
    public function getSaleItems(string $invoiceNo): array {
        return $this->db->fetchAll(
            "SELECT si.*, 
                    COALESCE(p.hsn_code, '') AS hsn_code, 
                    COALESCE(p.manufacturer, '') AS manufacturer, 
                    COALESCE(p.expiry_date, '') AS expiry_date 
             FROM ph_sales_items si
             LEFT JOIN ph_product p ON p.product_id = si.product_id
             WHERE si.invoice_no = ?",
            [$invoiceNo]
        );
    }

    /**
     * Get sale items by sale ID (joins with master)
     */
    public function getSaleItemsBySaleId(int $saleId): array {
        return $this->db->fetchAll(
            "SELECT si.* FROM ph_sales_items si
             JOIN ph_sales_master sm ON si.invoice_no = sm.invoice_no
             WHERE sm.id = ?",
            [$saleId]
        );
    }

    /**
     * Get unique pharmacists (created_by) who made sales
     */
    public function getPharmacists(): array {
        return $this->db->fetchAll(
            "SELECT DISTINCT created_by FROM ph_sales_master WHERE created_by IS NOT NULL AND created_by != '' ORDER BY created_by ASC"
        );
    }

    /**
     * Get split payments for an invoice
     */
    public function getSplitPayments(string $invoiceNo): array {
        return $this->db->fetchAll(
            "SELECT payment_method, amount FROM ph_sales_payments WHERE invoice_no = ? ORDER BY id ASC",
            [$invoiceNo]
        );
    }
}
