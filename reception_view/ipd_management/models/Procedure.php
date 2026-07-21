<?php
/**
 * Procedure Model
 * 
 * Manages medical procedures performed during IPD admissions
 * 
 * @package IPD_Management\Models
 */

require_once __DIR__ . '/../core/BaseModel.php';

class Procedure extends BaseModel {
    protected $table = 'procedures_performed';
    protected $primaryKey = 'sl_no';
    
    /**
     * Get procedures by admission with doctor details
     */
    public function getByAdmission($admissionId) {
        $query = "SELECT 
            p.*,
            d.full_name as doctor_name,
            d.specialization as doctor_specialization
        FROM procedures_performed p
        LEFT JOIN doctors d ON p.performed_by = d.doctor_id
        WHERE p.admission_id = ?
        ORDER BY p.procedure_date DESC";
        
        return $this->fetchAll($query, [$admissionId]);
    }
    
    /**
     * Get all procedures with details
     */
    public function getAllWithDetails($filters = [], $limit = null, $offset = 0) {
        $query = "SELECT 
            p.*,
            d.full_name as doctor_name,
            a.patient_id,
            CONCAT(pat.first_name, ' ', COALESCE(pat.last_name, '')) as patient_name
        FROM procedures_performed p
        LEFT JOIN doctors d ON p.performed_by = d.doctor_id
        LEFT JOIN ipd_admissions a ON p.admission_id = a.admission_id
        LEFT JOIN patient pat ON a.patient_id = pat.patient_id
        WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['admission_id'])) {
            $query .= " AND p.admission_id = ?";
            $params[] = $filters['admission_id'];
        }
        
        if (!empty($filters['doctor_id'])) {
            $query .= " AND p.performed_by = ?";
            $params[] = $filters['doctor_id'];
        }
        
        $query .= " ORDER BY p.procedure_date DESC";
        
        if ($limit) {
            $query .= " LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;
        }
        
        return $this->fetchAll($query, $params);
    }
    
    /**
     * Create procedure with validation
     */
    public function createProcedure($data) {
        $required = ['admission_id', 'procedure_name', 'performed_by', 'procedure_date', 'charges'];
        $errors = $this->validateRequired($data, $required);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $procedureId = $this->create($data);
        
        return ['success' => true, 'procedure_id' => $procedureId];
    }
}
