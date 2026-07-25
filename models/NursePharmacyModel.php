<?php
/**
 * Nurse Pharmacy Model
 * Handles querying pharmacy products and bills
 * 
 * @package GM_HMS\Models
 * @version 1.0.0
 */

namespace GM_HMS\Models;

use GM_HMS\Database\SecureDatabase;
use Exception;

class NursePharmacyModel {
    private $db;
    
    public function __construct() {
        $this->db = SecureDatabase::getInstance();
    }
    
    public function searchMedicine($query) {
        $q = "%{$query}%";
        $sql = "SELECT product_id, product_name, content as generic_name, batch_number, quantity as available_stock, unit, mrp, sales_price 
                FROM ph_product 
                WHERE product_name LIKE ? OR content LIKE ? OR product_id LIKE ? OR batch_number LIKE ? 
                LIMIT 20";
        return $this->db->fetchAll($sql, [$q, $q, $q, $q]);
    }
    
    public function searchBill($query) {
        $q = "%{$query}%";
        $sql = "SELECT id, invoice_no, invoice_date, customer_name, customer_id, customer_phone, grand_total 
                FROM ph_sales_master 
                WHERE invoice_no LIKE ? OR customer_id LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ? 
                ORDER BY invoice_date DESC LIMIT 20";
        $bills = $this->db->fetchAll($sql, [$q, $q, $q, $q]);
        
        foreach($bills as &$bill) {
            $itemSql = "SELECT product_id, product_name, batch_no, qty as purchased_qty, rate, total FROM ph_sales_items WHERE invoice_no = ?";
            $bill['items'] = $this->db->fetchAll($itemSql, [$bill['invoice_no']]);
        }
        
        return $bills;
    }
}
