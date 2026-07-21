<?php
/**
 * Doctor Model
 * 
 * Provides read-only access to doctor data for IPD management
 * 
 * @package IPD_Management\Models
 */

require_once __DIR__ . '/../core/BaseModel.php';

class Doctor extends BaseModel {
    protected $table = 'doctors';
    protected $primaryKey = 'doctor_id';
    
    /**
     * Search doctors by name or specialization
     */
    public function searchDoctors($keyword, $limit = 20) {
        $query = "SELECT 
            doctor_id,
            full_name as name,
            specialization,
            mobile_number as contact,
            email
        FROM doctors
        WHERE (full_name LIKE ? OR specialization LIKE ? OR doctor_id LIKE ?)
        AND (status = 'Active' OR status = '' OR status IS NULL)
        ORDER BY full_name
        LIMIT ?";
        
        $searchTerm = "%{$keyword}%";
        return $this->fetchAll($query, [$searchTerm, $searchTerm, $searchTerm, (int)$limit]);
    }
    
    /**
     * Get all doctors
     */
    public function getAllDoctors() {
        $query = "SELECT 
            doctor_id,
            full_name as name,
            specialization,
            mobile_number as contact,
            email
        FROM doctors
        WHERE status = 'Active'
        ORDER BY full_name";
        
        return $this->fetchAll($query);
    }
    
    /**
     * Get doctor by ID
     */
    public function getDoctorDetails($doctorId) {
        return $this->getById($doctorId);
    }
}
