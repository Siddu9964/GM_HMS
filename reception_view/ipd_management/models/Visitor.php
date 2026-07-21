<?php
/**
 * Visitor Model
 * 
 * Manages visitor logs for admitted patients
 * 
 * @package IPD_Management\Models
 */

require_once __DIR__ . '/../core/BaseModel.php';

class Visitor extends BaseModel {
    protected $table = 'visitor_log';
    protected $primaryKey = 'visitor_id';
    
    /**
     * Get visitors by patient with filters
     */
    public function getByPatient($patientId, $dateFilter = null) {
        $query = "SELECT v.* FROM visitor_log v WHERE v.patient_id = ?";
        
        $params = [$patientId];
        
        if ($dateFilter) {
            $query .= " AND v.visit_date = ?";
            $params[] = $dateFilter;
        }
        
        $query .= " ORDER BY v.visit_date DESC, v.visit_time DESC";
        
        return $this->fetchAll($query, $params);
    }
    
    /**
     * Get all visitors with details
     */
    public function getAllWithDetails($filters = [], $limit = null, $offset = 0) {
        $query = "SELECT 
            v.*,
            p.first_name as patient_first_name,
            p.last_name as patient_last_name,
            CONCAT(p.first_name, ' ', COALESCE(p.last_name, '')) as patient_name,
            b.bed_number
        FROM visitor_log v
        LEFT JOIN patient p ON v.patient_id = p.patient_id COLLATE utf8mb4_unicode_ci
        LEFT JOIN hospital_beds b ON v.patient_id = b.patient_id COLLATE utf8mb4_unicode_ci
        WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['patient_id'])) {
            $query .= " AND v.patient_id = ?";
            $params[] = $filters['patient_id'];
        }
        
        if (!empty($filters['admission_id'])) {
            $query .= " AND v.admission_id = ?";
            $params[] = $filters['admission_id'];
        }
        
        if (!empty($filters['visit_date'])) {
            $query .= " AND v.visit_date = ?";
            $params[] = $filters['visit_date'];
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (v.visitor_name LIKE ? OR CONCAT(p.first_name, ' ', COALESCE(p.last_name, '')) LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $query .= " ORDER BY v.visit_date DESC, v.visit_time DESC";
        
        if ($limit) {
            $query .= " LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;
        }
        
        return $this->fetchAll($query, $params);
    }
    
    /**
     * Create visitor log entry
     */
    public function createVisitor($data) {
        $required = ['patient_id', 'visitor_name', 'visit_date'];
        $errors = $this->validateRequired($data, $required);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $visitorId = $this->create($data);
        
        return ['success' => true, 'visitor_id' => $visitorId];
    }
}
