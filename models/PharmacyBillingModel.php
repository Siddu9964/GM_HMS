<?php
namespace GM_HMS\Models;

use Exception;
use GM_HMS\Database\SecureDatabase;

/**
 * Pharmacy Billing Model
 */
class PharmacyBillingModel {
    private $db;
    
    public function __construct() {
        $this->db = SecureDatabase::getInstance();
    }

    public function getConnection() {
        return $this->db->getConnection();
    }

    public function getAllPatients() {
        return $this->db->fetchAll("SELECT * FROM patient");
    }

    public function searchPatients($q) {
        $q = '%' . $q . '%';
        return $this->db->fetchAll("SELECT * FROM patient WHERE patient_id LIKE ? OR first_name LIKE ? OR phone LIKE ?", [$q, $q, $q]);
    }

    public function getPatientById($pid) {
        return $this->db->fetchOne("SELECT * FROM patient WHERE patient_id = ?", [$pid]);
    }

    public function getPatientConsultations($pid) {
        return $this->db->fetchAll("SELECT * FROM consultations WHERE patient_id = ? ORDER BY consultation_date DESC", [$pid]);
    }

    public function searchProducts($q) {
        $q = '%' . $q . '%';
        return $this->db->fetchAll("SELECT * FROM ph_product WHERE product_name LIKE ? OR content LIKE ? LIMIT 20", [$q, $q]);
    }

    public function getAllSponsors() {
        return [];
    }

    public function getProductStock($pid) {
        $res = $this->db->fetchOne("SELECT quantity FROM ph_product WHERE product_id = ?", [$pid]);
        return $res ? (int)$res['quantity'] : 0;
    }

    public function deductStock($pid, $qty) {
        return $this->db->execute("UPDATE ph_product SET quantity = quantity - ? WHERE product_id = ?", [$qty, $pid]);
    }

    public function generateInvoiceNo() {
        return 'INV-' . time() . '-' . rand(100, 999);
    }

    public function insertSalesMaster($data) {
        // Dummy implementation
        return true;
    }

    public function insertSalesItem($invoice_no, $customer_id, $item) {
        // Dummy implementation
        return true;
    }

    public function generateInvoiceHTML($master, $items, $printedBy) {
        return "<h1>Invoice</h1><p>Invoice generated.</p>";
    }
}
