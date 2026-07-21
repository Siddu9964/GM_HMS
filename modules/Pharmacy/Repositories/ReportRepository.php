<?php
namespace GM_HMS\Modules\Pharmacy\Repositories;

use GM_HMS\Database\SecureDatabase;
use Exception;

class ReportRepository {
    private $db;

    public function __construct() {
        $this->db = SecureDatabase::getInstance();
    }

    public function getTotalProducts(): int {
        $row = $this->db->fetchOne("SELECT COUNT(*) as count FROM ph_product");
        return (int)($row['count'] ?? 0);
    }

    public function getLowStockCount(int $threshold): int {
        $row = $this->db->fetchOne("SELECT COUNT(*) as count FROM ph_product WHERE quantity <= ?", [$threshold]);
        return (int)($row['count'] ?? 0);
    }

    public function getExpirySoonCount(int $days): int {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM ph_product 
             WHERE quantity > 0 AND expiry_date IS NOT NULL 
             AND expiry_date != '0000-00-00'
             AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY) 
             AND expiry_date >= CURDATE()", 
            [$days]
        );
        return (int)($row['count'] ?? 0);
    }

    public function getTodaySalesTotal(): float {
        $row = $this->db->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) as total 
             FROM (
                 SELECT MAX(grand_total) as amount 
                 FROM ph_sales_master 
                 WHERE invoice_date = CURDATE() AND status != 'cancelled'
                 GROUP BY invoice_no
             ) as unique_sales"
        );
        return (float)($row['total'] ?? 0.0);
    }

    public function getMonthSalesTotal(): float {
        $row = $this->db->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) as total 
             FROM (
                 SELECT MAX(grand_total) as amount 
                 FROM ph_sales_master 
                 WHERE MONTH(invoice_date) = MONTH(CURDATE()) 
                 AND YEAR(invoice_date) = YEAR(CURDATE()) 
                 AND status != 'cancelled'
                 GROUP BY invoice_no
             ) as unique_sales"
        );
        return (float)($row['total'] ?? 0.0);
    }

    public function getPendingIndentsCount(): int {
        $row = $this->db->fetchOne("SELECT COUNT(*) as count FROM ph_indent_requests WHERE status = 'pending'");
        return (int)($row['count'] ?? 0);
    }

    public function getTotalSuppliersCount(): int {
        $row = $this->db->fetchOne("SELECT COUNT(*) as count FROM ph_suppliers WHERE status = 'active'");
        return (int)($row['count'] ?? 0);
    }

    public function getTotalCustomersCount(): int {
        $row = $this->db->fetchOne("SELECT COUNT(*) as count FROM ph_customers");
        return (int)($row['count'] ?? 0);
    }

    public function getLast7DaysSales(): array {
        return $this->db->fetchAll(
            "SELECT date, SUM(amount) as total
             FROM (
                 SELECT DATE(invoice_date) as date, invoice_no, MAX(grand_total) as amount
                 FROM ph_sales_master 
                 WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
                 AND status != 'cancelled'
                 GROUP BY DATE(invoice_date), invoice_no
             ) as unique_sales
             GROUP BY date 
             ORDER BY date"
        );
    }

    public function getTopSellingProducts(int $limit = 5): array {
        return $this->db->fetchAll(
            "SELECT si.product_name, 
                    SUM(si.qty) as total_qty, 
                    SUM(si.total) as total_revenue,
                    ROUND(AVG(si.rate), 2) as avg_rate
             FROM ph_sales_items si
             JOIN ph_sales_master sm ON sm.invoice_no = si.invoice_no
             WHERE MONTH(sm.invoice_date) = MONTH(CURDATE()) 
             AND YEAR(sm.invoice_date) = YEAR(CURDATE()) 
             AND sm.status != 'cancelled'
             GROUP BY si.product_name 
             ORDER BY total_qty DESC 
             LIMIT ?", 
            [$limit]
        );
    }

    public function getRecentAlerts(int $threshold, int $expiryDays, int $limit = 8): array {
        return $this->db->fetchAll(
            "SELECT product_name, quantity, expiry_date, 'expiry' as alert_type
             FROM ph_product
             WHERE quantity > 0 
             AND expiry_date IS NOT NULL 
             AND expiry_date != '0000-00-00'
             AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY) 
             AND expiry_date >= CURDATE()
             
             UNION
             
             SELECT product_name, quantity, expiry_date, 'low' as alert_type
             FROM ph_product
             WHERE quantity <= ?
             
             ORDER BY quantity ASC 
             LIMIT ?", 
            [$expiryDays, $threshold, $limit]
        );
    }

    public function getSalesReport(string $from, string $to, string $payment = ''): array {
        // payment_method enum uses lowercase: cash, card, upi, credit
        $params = [$from, $to];
        $paymentFilter = '';

        if (!empty($payment)) {
            $paymentFilter = ' AND sm.payment_method = ?';
            $params[] = strtolower($payment);
        }

        $data = $this->db->fetchAll(
            "SELECT sm.*,
                    (SELECT COUNT(*) FROM ph_sales_items WHERE invoice_no = sm.invoice_no) as item_count
             FROM ph_sales_master sm
             WHERE sm.invoice_date BETWEEN ? AND ? $paymentFilter
             ORDER BY sm.invoice_date DESC",
            $params
        );

        $statsParams = [$from, $to];
        if (!empty($payment)) $statsParams[] = strtolower($payment);

        $stats = $this->db->fetchOne(
            "SELECT COUNT(*) as total_bills,
                    COALESCE(SUM(grand_total), 0) as total_revenue,
                    COALESCE(SUM(tax_total), 0) as total_tax,
                    COALESCE(SUM(discount_amount), 0) as total_discount
             FROM ph_sales_master sm
             WHERE sm.invoice_date BETWEEN ? AND ? AND sm.status != 'cancelled' $paymentFilter",
            $statsParams
        );

        return ['data' => $data, 'stats' => $stats];
    }

    public function getPurchaseReport(string $from, string $to): array {
        $data = $this->db->fetchAll(
            "SELECT po_no, po_date, invoice_no, supplier_id, supplier_name, expected_date,
                    subtotal, tax_total, grand_total, status
             FROM ph_purchase_orders
             WHERE po_date BETWEEN ? AND ?
             ORDER BY po_date DESC",
            [$from, $to]
        );

        $stats = $this->db->fetchOne(
            "SELECT COUNT(*) as total_orders,
                    COALESCE(SUM(grand_total), 0) as total_value,
                    COALESCE(SUM(tax_total), 0) as total_tax
             FROM ph_purchase_orders
             WHERE po_date BETWEEN ? AND ? AND status != 'cancelled'",
            [$from, $to]
        );

        return ['data' => $data, 'stats' => $stats];
    }

    public function getSupplierReport(): array {
        $data = $this->db->fetchAll(
            "SELECT s.supplier_id, s.company_name, s.supplier_name, s.phone, s.city, s.gst_no, s.status,
                    COUNT(po.id) as po_count,
                    COALESCE(SUM(po.grand_total), 0) as total_value
             FROM ph_suppliers s
             LEFT JOIN ph_purchase_orders po ON po.supplier_id = s.supplier_id AND po.status != 'cancelled'
             GROUP BY s.supplier_id
             ORDER BY total_value DESC"
        );

        $stats = $this->db->fetchOne(
            "SELECT COUNT(*) as total_suppliers,
                    SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active_suppliers
             FROM ph_suppliers"
        );

        return ['data' => $data, 'stats' => $stats];
    }

    public function getCustomerReport(): array {
        $data = $this->db->fetchAll(
            "SELECT c.customer_id, c.customer_name, c.phone, c.email, c.credit_limit,
                    COUNT(sm.id) as total_bills,
                    COALESCE(SUM(sm.grand_total), 0) as total_spent
             FROM ph_customers c
             LEFT JOIN ph_sales_master sm ON sm.customer_id = c.customer_id AND sm.status != 'cancelled'
             GROUP BY c.customer_id
             ORDER BY total_spent DESC"
        );

        $stats = $this->db->fetchOne(
            "SELECT COUNT(*) as total_customers,
                    COALESCE(SUM(credit_limit), 0) as total_credit
             FROM ph_customers"
        );

        return ['data' => $data, 'stats' => $stats];
    }

    public function getTaxReport(string $from, string $to): array {
        $data = $this->db->fetchAll(
            "SELECT sm.invoice_no, sm.invoice_date, sm.customer_name,
                    sm.subtotal, sm.tax_total, sm.grand_total,
                    CASE WHEN sm.subtotal > 0 THEN ROUND((sm.tax_total / sm.subtotal) * 100, 2) ELSE 0 END as avg_tax_pct
             FROM ph_sales_master sm
             WHERE sm.invoice_date BETWEEN ? AND ? AND sm.status != 'cancelled'
             ORDER BY sm.invoice_date DESC",
            [$from, $to]
        );

        $stats = $this->db->fetchOne(
            "SELECT COUNT(*) as total_invoices,
                    COALESCE(SUM(subtotal), 0) as total_taxable,
                    COALESCE(SUM(tax_total), 0) as total_tax_collected
             FROM ph_sales_master
             WHERE invoice_date BETWEEN ? AND ? AND status != 'cancelled'",
            [$from, $to]
        );

        return ['data' => $data, 'stats' => $stats];
    }


    public function getExpiryReport(int $days): array {
        $data = $this->db->fetchAll(
            "SELECT product_id, product_name, strength, form, 
                    batch_number, expiry_date, quantity
             FROM ph_product
             WHERE expiry_date IS NOT NULL 
             AND expiry_date != '0000-00-00'
             AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
             ORDER BY expiry_date ASC",
            [$days]
        );
        return ['data' => $data];
    }

    public function getLowStockReport(int $threshold): array {
        $data = $this->db->fetchAll(
            "SELECT product_id, product_name, form, therapeutic, quantity, expiry_date, batch_number
             FROM ph_product
             WHERE quantity <= ?
             ORDER BY quantity ASC",
            [$threshold]
        );
        return ['data' => $data];
    }

    public function getStockDistribution(int $threshold): array {
        $in   = $this->db->fetchOne("SELECT COALESCE(SUM(quantity), 0) as total FROM ph_product WHERE quantity > ?", [$threshold]);
        $low  = $this->db->fetchOne("SELECT COALESCE(SUM(quantity), 0) as total FROM ph_product WHERE quantity > 0 AND quantity <= ?", [$threshold]);
        $out  = $this->db->fetchOne("SELECT COUNT(*) as total FROM ph_product WHERE quantity = 0");

        return [
            'in_stock'  => (int)($in['total'] ?? 0),
            'low_stock' => (int)($low['total'] ?? 0),
            'out_of_stock' => (int)($out['total'] ?? 0)
        ];
    }
}
