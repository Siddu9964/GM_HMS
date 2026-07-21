<?php
namespace GM_HMS\Models;

use Exception;
use GM_HMS\Database\SecureDatabase;

/**
 * Pharmacy Model
 * Handles data for the ph_product table
 */
class PharmacyModel {
    private $db;
    
    public function __construct() {
        $this->db = SecureDatabase::getInstance();
    }

    /** Expose DB instance for direct queries in controller */
    public function getDb() { return $this->db; }

    public function getDashboardStats() {
        // 1. Total Products
        $totalProducts = $this->db->fetchOne("SELECT COUNT(*) as count FROM ph_product");
        
        // 2. Low Stock (Quantity < 20)
        $lowStock = $this->db->fetchOne("SELECT COUNT(*) as count FROM ph_product WHERE quantity < 20 AND quantity > 0");
        
        // 3. Out of Stock (Quantity = 0)
        $outOfStock = $this->db->fetchOne("SELECT COUNT(*) as count FROM ph_product WHERE quantity = 0");
        
        // 4. Expiring in the next 2 months (60 days)
        $expiringSoon = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM ph_product 
             WHERE expiry_date >= CURDATE() 
             AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 2 MONTH)"
        );
        
        // 5. Already Expired
        $expired = $this->db->fetchOne("SELECT COUNT(*) as count FROM ph_product WHERE expiry_date < CURDATE()");

        return [
            'total_products' => (int)$totalProducts['count'],
            'low_stock' => (int)$lowStock['count'],
            'out_of_stock' => (int)$outOfStock['count'],
            'expiring_soon' => (int)$expiringSoon['count'],
            'expired' => (int)$expired['count']
        ];
    }
    
    /**
     * Get list of products expiring in the next 2 months
     */
    public function getExpiringProductsList($limit = 5) {
        $sql = "SELECT product_name, expiry_date, quantity, pack 
                FROM ph_product 
                WHERE expiry_date >= CURDATE() 
                AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 2 MONTH)
                ORDER BY expiry_date ASC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }
    
    /**
     * Get list of low stock products (< 20)
     */
    public function getLowStockProductsList($limit = 5) {
        $sql = "SELECT product_name, quantity, pack, batch_number, expiry_date 
                FROM ph_product 
                WHERE quantity < 20 
                ORDER BY quantity ASC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Get ALL products sorted by stock level (lowest first)
     */
    public function getAllProductsSortedByStock() {
        $sql = "SELECT product_id, product_name, content, strength, form, therapeutic, quantity, pack, batch_number, expiry_date 
                FROM ph_product 
                ORDER BY quantity ASC";
        return $this->db->fetchAll($sql);
    }

    /**
     * Get ALL products sorted by expiry date (closest first)
     */
    public function getAllProductsSortedByExpiry() {
        $sql = "SELECT product_id, product_name, content, strength, form, therapeutic, quantity, pack, batch_number, expiry_date 
                FROM ph_product 
                WHERE expiry_date IS NOT NULL AND expiry_date != '0000-00-00'
                ORDER BY expiry_date ASC";
        return $this->db->fetchAll($sql);
    }

    /**
     * Get ALL prescriptions with resolved doctor and patient names
     */
    public function getAllPrescriptions() {
        $sql = "SELECT 
                    c.consultation_id,
                    c.patient_id,
                    c.doctor_id,
                    c.consultation_date,
                    c.consultation_time,
                    c.prescription_image,
                    c.soap_plan,
                    CONCAT(IFNULL(p.first_name,''), ' ', IFNULL(p.last_name,'')) AS patient_name,
                    p.sex   AS patient_sex,
                    p.age   AS patient_age,
                    p.phone AS patient_phone,
                    d.full_name      AS doctor_name,
                    d.specialization AS doctor_specialization,
                    d.photo          AS doctor_photo
                FROM consultations c
                LEFT JOIN patient p ON p.patient_id = c.patient_id
                LEFT JOIN doctors d ON d.doctor_id  = c.doctor_id
                ORDER BY c.consultation_date DESC, c.consultation_time DESC";
        
        $results = $this->db->fetchAll($sql);

        foreach ($results as &$row) {
            // 1. Process prescription_image (extract filename and use confirmed path)
            if (!empty($row['prescription_image'])) {
                $raw = $row['prescription_image'];
                // Split by any slash and get the last part
                $parts = preg_split('/[\\\\\/]/', $raw);
                $filename = end($parts);
                // Clean JSON/array artifacts
                $filename = trim($filename, '"\'[] ');
                
                if (strlen($filename) > 4) {
                    $row['prescription_image'] = '../assets/precision_data/' . $filename;
                }
            }

            // 2. Process doctor_photo
            if (!empty($row['doctor_photo'])) {
                $raw = $row['doctor_photo'];
                $parts = preg_split('/[\\\\\/]/', $raw);
                $filename = end($parts);
                $filename = trim($filename, '"\'[] ');
                
                if (strlen($filename) > 4) {
                    $row['doctor_photo'] = '../assets/profile_photos/' . $filename;
                }
            }
        }

        return $results;
    }
}
