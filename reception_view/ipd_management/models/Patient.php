<?php
/**
 * Patient Model
 * 
 * Provides read-only access to patient data for IPD management
 * 
 * @package IPD_Management\Models
 */

require_once __DIR__ . '/../core/BaseModel.php';

class Patient extends BaseModel {
    protected $table = 'patient';
    protected $primaryKey = 'patient_id';
    
    /**
     * Search patients by name or phone
     */
    public function searchPatients($keyword, $limit = 20) {
        $query = "SELECT 
            patient_id,
            first_name,
            last_name,
            CONCAT(first_name, ' ', COALESCE(last_name, '')) as name,
            phone as contact,
            age,
            sex as gender,
            blood_group
        FROM patient
        WHERE (first_name LIKE ? OR last_name LIKE ? OR phone LIKE ? OR patient_id LIKE ?)
        AND (status = 'Active' OR status = 'Registered' OR status = '' OR status IS NULL)
        ORDER BY first_name, last_name
        LIMIT ?";
        
        $searchTerm = "%{$keyword}%";
        return $this->fetchAll($query, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, (int)$limit]);
    }
    
    /**
     * Get patient by ID with full details
     */
    public function getPatientDetails($patientId) {
        return $this->getById($patientId);
    }

    /**
     * Get the latest doctor assigned to a patient
     */
    public function getLatestDoctor($patientId) {
        $query = "SELECT doctor_id, doctor_name 
                  FROM appointments 
                  WHERE patient_id = ? 
                  ORDER BY appointment_date DESC, appointment_time DESC 
                  LIMIT 1";
        return $this->fetchOne($query, [$patientId]);
    }
}
