<?php
/**
 * Bed Model
 * 
 * Manages hospital beds including status tracking,
 * allocation, and occupancy statistics
 * 
 * @package IPD_Management\Models
 */

require_once __DIR__ . '/../core/BaseModel.php';

class Bed extends BaseModel {
    protected $table = 'hospital_beds';
    protected $primaryKey = 'sl_no';
    
    private $roomCatCol = null;
    
    private function getRoomCatCol() {
        if ($this->roomCatCol === null) {
            $col = $this->fetchOne("SHOW COLUMNS FROM hospital_beds LIKE 'room_category'");
            $this->roomCatCol = $col ? 'room_category' : 'room_type';
        }
        return $this->roomCatCol;
    }
    
    /**
     * Get all beds with current patient details
     */
    public function getAllWithDetails($filters = []) {
        $query = "SELECT 
            b.*,
            b.sl_no as bed_id,
            CONCAT(p.first_name, ' ', COALESCE(p.last_name, '')) as patient_name,
            a.admission_date,
            a.admission_id
        FROM hospital_beds b
        LEFT JOIN patient p ON b.patient_id COLLATE utf8mb4_general_ci = p.patient_id COLLATE utf8mb4_general_ci
        LEFT JOIN (
            SELECT admission_id, patient_id, admission_date 
            FROM ipd_admissions 
            WHERE admission_id IN (
                SELECT MAX(admission_id) FROM ipd_admissions GROUP BY patient_id
            )
        ) a ON b.patient_id COLLATE utf8mb4_general_ci = a.patient_id COLLATE utf8mb4_general_ci
        WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['status'])) {
            $query .= " AND b.bed_status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['ward_name'])) {
            $query .= " AND b.ward_name = ?";
            $params[] = $filters['ward_name'];
        }
        
        if (!empty($filters['bed_type'])) {
            $col = $this->getRoomCatCol();
            $query .= " AND b.{$col} = ?";
            $params[] = $filters['bed_type'];
        }
        
        $col = $this->getRoomCatCol();
        $query .= " ORDER BY b.floor_number ASC, b.ward_name ASC, b.{$col} ASC, b.room_number ASC, b.bed_number ASC";
        
        return $this->fetchAll($query, $params);
    }
    
    /**
     * Get available (vacant) beds
     */
    public function getAvailableBeds($bedType = null) {
        $col = $this->getRoomCatCol();
        $query = "SELECT 
            sl_no as bed_id,
            bed_number,
            floor_number,
            floor_name,
            ward_name,
            {$col} as room_type,
            room_number,
            room_name,
            bed_status,
            amount_per_day,
            nursig_charge,
            doctor_charge,
            service_charge,
            total_bed_amount
        FROM hospital_beds
        WHERE LOWER(bed_status) = 'available'
        ORDER BY floor_number, ward_name, room_number, bed_number";

        return $this->fetchAll($query, []);
    }
    
    /**
     * Assign bed to patient
     */
    public function assignBed($bedId, $patientId, $admissionId) {
        // Check if bed is available
        $bed = $this->getById($bedId);
        
        if (!$bed) {
            return ['success' => false, 'errors' => ['Bed not found']];
        }
        
        if ($bed['bed_status'] !== 'Available') {
            return ['success' => false, 'errors' => ['Bed is not available']];
        }
        
        // Update bed
        $result = $this->update($bedId, [
            'bed_status' => 'Occupied',
            'patient_id' => $patientId,
            'allocated_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($result > 0) {
            return ['success' => true];
        }
        
        return ['success' => false, 'errors' => ['Failed to assign bed']];
    }
    
    /**
     * Release bed
     */
    public function releaseBed($bedId) {
        $result = $this->update($bedId, [
            'bed_status' => 'Available',
            'patient_id' => null,
            'released_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($result > 0) {
            return ['success' => true];
        }
        
        return ['success' => false, 'errors' => ['Failed to release bed']];
    }
    
    /**
     * Update bed status
     */
    public function updateStatus($bedId, $status) {
        $validStatuses = ['Available', 'Occupied', 'Blocked', 'Maintenance'];
        
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'errors' => ['Invalid status']];
        }
        
        // If changing to Vacant, clear patient info
        $data = ['bed_status' => $status];
        if ($status === 'Available') {
            $data['patient_id'] = null;
            $data['released_at'] = date('Y-m-d H:i:s');
        }
        
        $result = $this->update($bedId, $data);
        
        if ($result > 0) {
            return ['success' => true];
        }
        
        return ['success' => false, 'errors' => ['Failed to update status']];
    }
    
    /**
     * Get bed occupancy statistics
     */
    public function getBedOccupancy() {
        $query = "SELECT 
            COUNT(*) as total_beds,
            SUM(CASE WHEN bed_status = 'Available' THEN 1 ELSE 0 END) as vacant_beds,
            SUM(CASE WHEN bed_status = 'Occupied' THEN 1 ELSE 0 END) as occupied_beds,
            SUM(CASE WHEN bed_status = 'Maintenance' THEN 1 ELSE 0 END) as maintenance_beds,
            SUM(CASE WHEN bed_status = 'Blocked' THEN 1 ELSE 0 END) as blocked_beds,
            ROUND((SUM(CASE WHEN bed_status = 'Occupied' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as occupancy_percentage
        FROM hospital_beds";
        
        return $this->fetchOne($query);
    }
    
    /**
     * Get occupancy by ward
     */
    public function getOccupancyByWard() {
        $col = $this->getRoomCatCol();
        $query = "SELECT 
            ward_name,
            {$col} as room_category,
            COUNT(*) as total_beds,
            SUM(CASE WHEN bed_status = 'Available' THEN 1 ELSE 0 END) as vacant_beds,
            SUM(CASE WHEN bed_status = 'Occupied' THEN 1 ELSE 0 END) as occupied_beds,
            ROUND((SUM(CASE WHEN bed_status = 'Occupied' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as occupancy_percentage
        FROM hospital_beds
        GROUP BY ward_name, {$col}
        ORDER BY ward_name, {$col}";
        
        return $this->fetchAll($query);
    }
    
    /**
     * Get distinct ward names
     */
    public function getWards() {
        $query = "SELECT DISTINCT ward_name FROM hospital_beds ORDER BY ward_name";
        return $this->fetchAll($query);
    }
    
    /**
     * Get distinct bed types
     */
    public function getBedTypes() {
        $col = $this->getRoomCatCol();
        $query = "SELECT DISTINCT {$col} as room_category FROM hospital_beds ORDER BY {$col}";
        return $this->fetchAll($query);
    }
}
