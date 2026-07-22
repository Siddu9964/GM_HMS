<?php
/**
 * Admission Model
 * 
 * Manages IPD patient admissions including CRUD operations,
 * bed allocation, discharge, and financial calculations
 * 
 * @package IPD_Management\Models
 */

require_once __DIR__ . '/../core/BaseModel.php';

class Admission extends BaseModel {
    protected $table = 'ipd_admissions';
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
     * Get all admissions with patient, doctor, and bed details
     */
    public function getAllWithDetails($filters = [], $limit = null, $offset = 0) {
        $query = "SELECT 
            a.*,
            CONCAT(p.first_name, ' ', COALESCE(p.last_name, '')) as patient_name,
            p.phone as patient_contact,
            p.age as patient_age,
            p.sex as patient_gender,
            d.full_name as doctor_name,
            d.specialization as doctor_specialization,
            b.bed_number,
            b.ward_name,
            b.{$this->getRoomCatCol()} as room_category,
            DATEDIFF(COALESCE(a.discharge_date, NOW()), a.admission_date) as days_admitted
        FROM ipd_admissions a
        LEFT JOIN patient p ON a.patient_id = p.patient_id
        LEFT JOIN doctors d ON a.admitting_doctor_id = d.doctor_id
        LEFT JOIN hospital_beds b ON a.bed_no = b.sl_no
        WHERE 1=1";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $query .= " AND a.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['patient_id'])) {
            $query .= " AND a.patient_id = ?";
            $params[] = $filters['patient_id'];
        }
        
        if (!empty($filters['doctor_id'])) {
            $query .= " AND a.admitting_doctor_id = ?";
            $params[] = $filters['doctor_id'];
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (CONCAT(p.first_name, ' ', COALESCE(p.last_name, '')) LIKE ? 
                        OR b.bed_number LIKE ? 
                        OR p.phone LIKE ? 
                        OR p.patient_id LIKE ? 
                        OR a.admission_id LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $query .= " ORDER BY a.admission_date DESC";
        
        if ($limit) {
            $query .= " LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;
        }
        
        return $this->fetchAll($query, $params);
    }
    
    /**
     * Get admission by ID with full details
     * Accepts both admission_id (string) or sl_no (integer)
     */
    public function getByIdWithDetails($id) {
        // Determine if searching by sl_no or admission_id
        $whereClause = is_numeric($id) ? "a.sl_no = ?" : "a.admission_id = ?";
        
        $query = "SELECT 
            a.*,
            CONCAT(p.first_name, ' ', COALESCE(p.last_name, '')) as patient_name,
            p.phone as patient_contact,
            p.age as patient_age,
            p.sex as patient_gender,
            p.address as patient_address,
            d.full_name as doctor_name,
            d.specialization as doctor_specialization,
            d.mobile_number as doctor_contact,
            b.bed_number,
            b.ward_name,
            b.{$this->getRoomCatCol()} as room_category,
            DATEDIFF(COALESCE(a.discharge_date, NOW()), a.admission_date) as days_admitted
        FROM ipd_admissions a
        LEFT JOIN patient p ON a.patient_id = p.patient_id
        LEFT JOIN doctors d ON a.admitting_doctor_id = d.doctor_id
        LEFT JOIN hospital_beds b ON a.bed_no = b.sl_no
        WHERE {$whereClause}";
        
        return $this->fetchOne($query, [$id]);
    }
    
    /**
     * Get active admissions
     */
    public function getActiveAdmissions() {
        return $this->getAllWithDetails(['status' => 'Admitted']);
    }

    /**
     * Override count to handle search filters correctly
     * 
     * @param array $filters Associative array of column => value
     * @return int Count
     */
    public function count($filters = []) {
        $query = "SELECT COUNT(*) as count 
                  FROM ipd_admissions a
                  LEFT JOIN patient p ON a.patient_id = p.patient_id
                  LEFT JOIN doctors d ON a.admitting_doctor_id = d.doctor_id
                  LEFT JOIN hospital_beds b ON a.bed_no = b.sl_no
                  WHERE 1=1";
        
        $params = [];
        
        // Apply filters (same logic as getAllWithDetails)
        if (!empty($filters['status'])) {
            $query .= " AND a.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['patient_id'])) {
            $query .= " AND a.patient_id = ?";
            $params[] = $filters['patient_id'];
        }
        
        if (!empty($filters['doctor_id'])) {
            $query .= " AND a.admitting_doctor_id = ?";
            $params[] = $filters['doctor_id'];
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (CONCAT(p.first_name, ' ', COALESCE(p.last_name, '')) LIKE ? 
                        OR b.bed_number LIKE ? 
                        OR p.phone LIKE ? 
                        OR p.patient_id LIKE ? 
                        OR a.admission_id LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $result = $this->fetchOne($query, $params);
        return (int)$result['count'];
    }

    /**
     * Delete admission and all related records
     * Handles manual cleanup of related data since foreign keys are removed
     * 
     * @param int $slNo Primary key (sl_no)
     * @return array Success status and detailed message
     */
    public function deleteAdmission($slNo) {
        try {
            $this->beginTransaction();
            
            // Get admission details
            $admission = $this->getById($slNo);
            if (!$admission) {
                return ['success' => false, 'error' => 'Admission not found. The record may have been already deleted.'];
            }
            
            $admissionId = $admission['admission_id'];
            $patientId = $admission['patient_id'];
            
            // Check if patient is currently admitted - CHECK REMOVED to allow force delete
            // if ($admission['status'] === 'Admitted') {
            //    return ['success' => false, 'error' => 'Cannot delete an active admission. Please discharge the patient first.'];
            // }
            
            // Delete related records in order (child tables first)
            // Using admission_id for foreign key relationships
            
            try {
                // 1. Delete discharge details (uses admission_sl_no)
                $this->query("DELETE FROM discharge_details WHERE admission_sl_no = ?", [$slNo]);
            } catch (Exception $e) {
                error_log("Could not delete discharge_details: " . $e->getMessage());
            }
            
            try {
                // 2. Delete procedures (uses admission_sl_no)
                $this->query("DELETE FROM procedures_performed WHERE admission_sl_no = ?", [$slNo]);
            } catch (Exception $e) {
                error_log("Could not delete procedures: " . $e->getMessage());
            }
            
            try {
                // 3. Delete visitor log for this patient during admission period
                $this->query(
                    "DELETE FROM visitor_log WHERE patient_id = ? AND visit_date BETWEEN ? AND COALESCE(?, NOW())",
                    [$patientId, $admission['admission_date'], $admission['discharge_date']]
                );
            } catch (Exception $e) {
                error_log("Could not delete visitors: " . $e->getMessage());
            }
            
            try {
                // 4. Delete charges (uses admission_id)
                $this->query("DELETE FROM charges WHERE admission_id = ?", [$admissionId]);
            } catch (Exception $e) {
                error_log("Could not delete charges: " . $e->getMessage());
            }
            
            try {
                // 5. Delete payments (uses admission_sl_no)
                $this->query("DELETE FROM payments WHERE admission_sl_no = ?", [$slNo]);
            } catch (Exception $e) {
                error_log("Could not delete payments: " . $e->getMessage());
            }
            
            try {
                // 6. Delete billing items first (child of ipd_billing)
                $this->query(
                    "DELETE FROM ipd_billing_items WHERE bill_id IN (SELECT bill_id FROM ipd_billing WHERE admission_id = ?)",
                    [$admissionId]
                );
            } catch (Exception $e) {
                error_log("Could not delete billing items: " . $e->getMessage());
            }
            
            try {
                // 7. Delete billing master (uses admission_id)
                $this->query("DELETE FROM ipd_billing WHERE admission_id = ?", [$admissionId]);
            } catch (Exception $e) {
                error_log("Could not delete billing: " . $e->getMessage());
            }
            
            // 8. Release bed if assigned
            if ($admission['bed_no']) {
                try {
                    $this->query(
                        "UPDATE hospital_beds SET bed_status = 'Available', patient_id = NULL, released_at = NOW() WHERE sl_no = ?",
                        [$admission['bed_no']]
                    );
                } catch (Exception $e) {
                    error_log("Could not release bed: " . $e->getMessage());
                }
            }
            
            // 9. Finally, delete the admission itself using admission_id to avoid FK issues
            $result = $this->query("DELETE FROM ipd_admissions WHERE admission_id = ?", [$admissionId]);
            
            if ($result === 0) {
                throw new Exception('Failed to delete admission record. Please contact system administrator.');
            }
            
            $this->commit();
            
            return ['success' => true, 'message' => 'Admission and all related records deleted successfully'];
        } catch (Exception $e) {
            $this->rollback();
            $errorMsg = $e->getMessage();
            
            // Provide user-friendly error messages
            if (strpos($errorMsg, 'foreign key constraint') !== false) {
                return ['success' => false, 'error' => 'Cannot delete admission due to related records. Please contact system administrator.'];
            } elseif (strpos($errorMsg, 'Admission not found') !== false) {
                return ['success' => false, 'error' => 'Admission record not found. It may have been already deleted.'];
            } else {
                return ['success' => false, 'error' => 'Failed to delete admission: ' . $errorMsg];
            }
        }
    }
    
    /**
     * Create new admission with validation
     */
    public function createAdmission($data) {
        // Generate admission_id if not provided
        if (empty($data['admission_id'])) {
            $data['admission_id'] = $this->generateAdmissionId();
        }
        
        // Filter only fields that exist in the table
        $allowedFields = [
            'admission_id',
            'patient_id',
            'admitting_doctor_id',
            'ward',
            'floor_number',
            'floor_name',
            'ward_name',
            'ward_type',
            'room_no',
            'room_name',
            'admission_date',
            'admission_time',
            'admission_type',
            'chief_complaint',
            'diagnosis',
            'discharge_date',
            'bed_no',
            'status',
            'emergency_contact_name',
            'emergency_contact_phone'
        ];
        
        // Map frontend bed_id to db bed_no if provided
        if (isset($data['bed_id']) && !empty($data['bed_id'])) {
            $data['bed_no'] = $data['bed_id'];
        }
        
        $filteredData = [];
        foreach ($allowedFields as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $filteredData[$field] = $data[$field];
            }
        }
        
        // Validate required fields
        $required = ['patient_id', 'admitting_doctor_id', 'bed_no', 'admission_date'];
        $errors = $this->validateRequired($filteredData, $required);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Fetch full bed details to populate admission record
        $bedDetails = $this->fetchOne(
            "SELECT * FROM hospital_beds WHERE sl_no = ?",
            [$filteredData['bed_no']]
        );
        
        if (!$bedDetails) {
            return ['success' => false, 'errors' => ['Bed not found']];
        }
        
        if ($bedDetails['bed_status'] !== 'Available') {
            return ['success' => false, 'errors' => ['Bed is not available. Current status: ' . $bedDetails['bed_status']]];
        }

        // Auto-fill bed assignment details into admission record
        $filteredData['ward'] = $bedDetails['ward_name'];
        $filteredData['floor_number'] = $bedDetails['floor_number'];
        $filteredData['floor_name'] = $bedDetails['floor_name'];
        $filteredData['ward_name'] = $bedDetails['ward_name'];
        $filteredData['ward_type'] = $bedDetails['ward_type'];
        $filteredData['room_no'] = $bedDetails['room_number'];
        $filteredData['room_name'] = $bedDetails['room_name'];
        
        try {
            $this->beginTransaction();
            
            // Set default status
            $filteredData['status'] = $filteredData['status'] ?? 'Admitted';
            
            // Create admission
            $admissionId = $this->create($filteredData);
            
            // Update bed status (trigger will handle this, but we can do it explicitly)
            $this->query(
                "UPDATE hospital_beds SET bed_status = 'Occupied', patient_id = ?, allocated_at = NOW() WHERE sl_no = ?",
                [$filteredData['patient_id'], $filteredData['bed_no']]
            );
            
            $this->commit();
            
            return ['success' => true, 'admission_id' => $data['admission_id'], 'sl_no' => $admissionId];
        } catch (Exception $e) {
            $this->rollback();
            return ['success' => false, 'errors' => ['Failed to create admission: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Generate unique admission ID
     * Format: ADM-YYYYMMDD-XXXX
     */
    private function generateAdmissionId() {
        $prefix = 'ADM';
        $date = date('Ymd');
        
        // Get last admission ID for today
        $lastAdmission = $this->fetchOne(
            "SELECT admission_id FROM ipd_admissions WHERE admission_id LIKE ? ORDER BY sl_no DESC LIMIT 1",
            ["{$prefix}-{$date}-%"]
        );
        
        if ($lastAdmission) {
            // Extract sequence number and increment
            $lastNumber = (int)substr($lastAdmission['admission_id'], -4);
            $sequence = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $sequence = '0001';
        }
        
        return "{$prefix}-{$date}-{$sequence}";
    }
    
    /**
     * Update admission with bed change support
     */
    public function updateAdmission($id, $data) {
        $transactionStarted = false;
        
        try {
            // Get current admission
            $currentAdmission = $this->getById($id);
            if (!$currentAdmission) {
                throw new Exception('Admission not found with ID: ' . $id);
            }
            
            // Check if bed is being changed
            $newBedId = $data['bed_id'] ?? ($data['bed_no'] ?? null);
            $oldBedId = $currentAdmission['bed_no'];
            
            if ($newBedId && $newBedId != $oldBedId) {
                // Fetch new bed details to update admission record
                $bedDetails = $this->fetchOne(
                    "SELECT * FROM hospital_beds WHERE sl_no = ?",
                    [$newBedId]
                );

                if (!$bedDetails) {
                    throw new Exception('Bed not found with ID: ' . $newBedId);
                }

                if ($bedDetails['bed_status'] !== 'Available') {
                    throw new Exception('Selected bed is not available. Current status: ' . $bedDetails['bed_status']);
                }

                $this->beginTransaction();
                $transactionStarted = true;

                // Release old bed if exists
                if ($oldBedId) {
                    try {
                        $this->query(
                            "UPDATE hospital_beds SET bed_status = 'Available', patient_id = NULL, released_at = NOW() WHERE sl_no = ?",
                            [$oldBedId]
                        );
                    } catch (Exception $e) {
                        error_log("Could not release old bed: " . $e->getMessage());
                    }
                }

                // Populate bed details for admission update
                $data['ward'] = $bedDetails['ward_name'];
                $data['floor_number'] = $bedDetails['floor_number'];
                $data['floor_name'] = $bedDetails['floor_name'];
                $data['ward_name'] = $bedDetails['ward_name'];
                $data['ward_type'] = $bedDetails['ward_type'];
                $data['room_no'] = $bedDetails['room_number'];
                $data['room_name'] = $bedDetails['room_name'];

                // Allocate new bed
                try {
                    $this->query(
                        "UPDATE hospital_beds SET bed_status = 'Occupied', patient_id = ?, allocated_at = NOW() WHERE sl_no = ?",
                        [$currentAdmission['patient_id'], $newBedId]
                    );
                } catch (Exception $e) {
                    throw new Exception("Could not allocate new bed: " . $e->getMessage());
                }
                
                // Update admission
                $result = $this->update($id, $data);
                
                if ($result === 0) {
                    throw new Exception('No changes were made to the admission record');
                }
                
                $this->commit();
                $transactionStarted = false;
                return $result;
            } else {
                // No bed change, just update
                unset($data['bed_id']); // Don't update bed_id if not changing
                unset($data['bed_no']); // Also unset bed_no
                $result = $this->update($id, $data);
                
                if ($result === 0) {
                    throw new Exception('No changes were made to the admission record');
                }
                
                return $result;
            }
        } catch (Exception $e) {
            if ($transactionStarted) {
                $this->rollback();
            }
            error_log("Update admission error: " . $e->getMessage());
            throw $e; // Re-throw to let controller handle it
        }
    }
    
    /**
     * Discharge patient with comprehensive validation
     */
    public function dischargePatient($admissionId, $dischargeData) {
        try {
            $this->beginTransaction();
            
            // Validate admission_id
            if (empty($admissionId)) {
                return ['success' => false, 'errors' => ['Admission ID is required']];
            }
            
            // Get admission details
            $admission = $this->fetchOne("SELECT * FROM ipd_admissions WHERE admission_id = ?", [$admissionId]);
            if (!$admission) {
                return ['success' => false, 'errors' => ['Admission not found. The patient may have been already discharged or the record does not exist.']];
            }
            
            // Check if already discharged
            if ($admission['status'] === 'Discharged') {
                return ['success' => false, 'errors' => ['Patient has already been discharged on ' . date('d-M-Y', strtotime($admission['discharge_date']))]];
            }
            
            // Prepare discharge date and time
            $dischargeDate = $dischargeData['discharge_date'] ?? date('Y-m-d');
            $dischargeTime = $dischargeData['discharge_time'] ?? date('H:i:s');
            
            // Validate discharge date is not before admission date
            if (strtotime($dischargeDate) < strtotime($admission['admission_date'])) {
                return ['success' => false, 'errors' => ['Discharge date cannot be before admission date']];
            }
            
            // Update admission status
            $updateResult = $this->update($admission['sl_no'], [
                'status' => 'Discharged',
                'discharge_date' => $dischargeDate,
                'discharge_time' => $dischargeTime
            ]);
            
            if ($updateResult === 0) {
                throw new Exception('Failed to update admission status');
            }
            
            // Release bed if assigned
            if ($admission['bed_no']) {
                try {
                    $this->query(
                        "UPDATE hospital_beds SET bed_status = 'Available', patient_id = NULL, released_at = NOW() WHERE sl_no = ?",
                        [$admission['bed_no']]
                    );
                } catch (Exception $e) {
                    // Log but don't fail if bed table doesn't exist
                    error_log("Could not release bed: " . $e->getMessage());
                }
            }
            
            $this->commit();
            
            return [
                'success' => true, 
                'message' => 'Patient discharged successfully',
                'discharge_date' => $dischargeDate
            ];
        } catch (Exception $e) {
            $this->rollback();
            $errorMsg = $e->getMessage();
            
            // Provide user-friendly error messages
            if (strpos($errorMsg, 'Duplicate entry') !== false) {
                return ['success' => false, 'errors' => ['Discharge record already exists for this admission']];
            } else {
                return ['success' => false, 'errors' => ['Failed to discharge patient: ' . $errorMsg]];
            }
        }
    }
    
    /**
     * Calculate total charges for admission
     */
    public function calculateTotalCharges($admissionId) {
        try {
            $result = $this->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) as total FROM charges WHERE admission_id = ?",
                [$admissionId]
            );
            
            return (float)$result['total'];
        } catch (Exception $e) {
            // If charges table doesn't exist, return 0
            error_log("Charges table not found: " . $e->getMessage());
            return 0.0;
        }
    }
    
    /**
     * Calculate total payments for admission
     */
    public function calculateTotalPayments($admissionId) {
        try {
            $result = $this->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE admission_sl_no = (SELECT sl_no FROM ipd_admissions WHERE admission_id = ?) AND status = 'Completed'",
                [$admissionId]
            );
            
            return (float)$result['total'];
        } catch (Exception $e) {
            // If payments table doesn't exist, return 0
            error_log("Payments table not found: " . $e->getMessage());
            return 0.0;
        }
    }
    
    /**
     * Get balance due for admission
     */
    public function getBalance($admissionId) {
        $charges = $this->calculateTotalCharges($admissionId);
        $payments = $this->calculateTotalPayments($admissionId);
        
        return [
            'total_charges' => $charges,
            'total_payments' => $payments,
            'balance_due' => $charges - $payments
        ];
    }
    
    /**
     * Get admission statistics for dashboard
     */
    public function getStatistics($dateRange = 'today') {
        $dateCondition = '';
        $params = [];
        
        switch ($dateRange) {
            case 'today':
                $dateCondition = "DATE(admission_date) = CURDATE()";
                break;
            case 'week':
                $dateCondition = "admission_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $dateCondition = "admission_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
        }
        
        $query = "SELECT 
            COUNT(*) as total_admissions,
            SUM(CASE WHEN status = 'Admitted' THEN 1 ELSE 0 END) as active_admissions,
            SUM(CASE WHEN status = 'Discharged' THEN 1 ELSE 0 END) as discharged
        FROM ipd_admissions";
        
        if ($dateCondition) {
            $query .= " WHERE {$dateCondition}";
        }
        
        return $this->fetchOne($query, $params);
    }
    
    /**
     * Convert admission_id to sl_no
     * Helper method for controllers to get primary key from admission_id
     * 
     * @param string|int $id Can be admission_id (string) or sl_no (int)
     * @return int|null Returns sl_no or null if not found
     */
    public function getSlNoFromId($id) {
        // If already numeric, return as is
        if (is_numeric($id)) {
            return (int)$id;
        }
        
        // Otherwise, lookup by admission_id
        $admission = $this->fetchOne(
            "SELECT sl_no FROM ipd_admissions WHERE admission_id = ?",
            [$id]
        );
        
        return $admission ? (int)$admission['sl_no'] : null;
    }
}
