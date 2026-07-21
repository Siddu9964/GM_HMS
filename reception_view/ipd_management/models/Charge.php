<?php
/**
 * Charge Model
 * 
 * Manages charges for IPD admissions
 * 
 * @package IPD_Management\Models
 */

require_once __DIR__ . '/../core/BaseModel.php';

class Charge extends BaseModel {
    protected $table = 'charges';
    protected $primaryKey = 'sl_no';
    
    /**
     * Get charges by admission
     */
    public function getByAdmission($admissionId) {
        $query = "SELECT * FROM charges WHERE admission_id = ? ORDER BY charge_date DESC, created_at DESC";
        return $this->fetchAll($query, [$admissionId]);
    }
    
    /**
     * Get total charges for admission
     */
    public function getTotalCharges($admissionId) {
        $result = $this->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) as total FROM charges WHERE admission_id = ?",
            [$admissionId]
        );
        return (float)$result['total'];
    }
    
    /**
     * Get charges breakdown by type
     */
    public function getChargesBreakdown($admissionId) {
        $query = "SELECT 
            description,
            COUNT(*) as count,
            COALESCE(SUM(amount), 0) as total
        FROM charges
        WHERE admission_id = ?
        GROUP BY description
        ORDER BY total DESC";
        
        return $this->fetchAll($query, [$admissionId]);
    }
    
    /**
     * Create charge (total_amount calculated by trigger)
     */
    public function createCharge($data) {
        $required = ['admission_id', 'description', 'amount', 'charge_date'];
        $errors = $this->validateRequired($data, $required);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Set default quantity if not provided
        if (!isset($data['quantity'])) {
            $data['quantity'] = 1;
        }
        
        // Calculate total amount (also done by trigger, but good to have)
        $data['total_amount'] = $data['quantity'] * $data['unit_price'];
        
        $chargeId = $this->create($data);
        
        return ['success' => true, 'charge_id' => $chargeId];
    }
}
