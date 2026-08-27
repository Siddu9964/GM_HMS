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
        LEFT JOIN hospital_beds b ON a.bed_id = b.sl_no
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
        LEFT JOIN hospital_beds b ON a.bed_id = b.sl_no
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
                  LEFT JOIN hospital_beds b ON a.bed_id = b.sl_no
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
            if ($admission['bed_id']) {
                try {
                    $this->query(
                        "UPDATE hospital_beds SET bed_status = 'Available', patient_id = NULL, released_at = NOW() WHERE sl_no = ?",
                        [$admission['bed_id']]
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
        
        // Dynamically fetch valid columns for the current database schema
        $schema = $this->db->fetchAll("DESCRIBE ipd_admissions");
        $validColumns = [];
        foreach ($schema as $col) {
            $validColumns[] = $col['Field'];
        }
        
        // Map frontend bed_id to db bed_id if provided
        if (isset($data['bed_id']) && !empty($data['bed_id'])) {
            $data['bed_id'] = $data['bed_id'];
        }
        
        $filteredData = [];
        // Only allow fields that exist in the database table
        foreach ($data as $field => $value) {
            if (in_array($field, $validColumns) && $value !== '') {
                $filteredData[$field] = $value;
            }
        }
        
        // Validate required fields
        $required = ['patient_id', 'admitting_doctor_id', 'bed_id', 'admission_date'];
        $errors = $this->validateRequired($filteredData, $required);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Fetch full bed details to populate admission record
        $bedDetails = $this->fetchOne(
            "SELECT * FROM hospital_beds WHERE sl_no = ?",
            [$filteredData['bed_id']]
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
        $filteredData['room_no'] = $bedDetails['room_number'];
        $filteredData['room_name'] = $bedDetails['room_name'];
        $filteredData['room_name'] = $bedDetails['room_name'];
        
        // Dynamically map room/ward type depending on which column the branch's schema has
        if (in_array('room_type', $validColumns)) {
            $filteredData['room_type'] = $bedDetails['room_type'];
        }
        if (in_array('ward_type', $validColumns)) {
            $filteredData['ward_type'] = $bedDetails['room_type'];
        }
        
        // Auto-fill bed charges into admission record only if columns exist
        if (in_array('amount_per_day', $validColumns)) $filteredData['amount_per_day'] = $bedDetails['amount_per_day'];
        if (in_array('nursig_charge', $validColumns)) $filteredData['nursig_charge'] = $bedDetails['nursig_charge'];
        if (in_array('doctor_charge', $validColumns)) $filteredData['doctor_charge'] = $bedDetails['doctor_charge'];
        if (in_array('service_charge', $validColumns)) $filteredData['service_charge'] = $bedDetails['service_charge'];
        
        
        try {
            $this->beginTransaction();
            
            // Set default status
            $filteredData['status'] = $filteredData['status'] ?? 'Admitted';
            
            // Create admission
            $admissionId = $this->create($filteredData);
            
            // Update bed status (trigger will handle this, but we can do it explicitly)
            $this->query(
                "UPDATE hospital_beds SET bed_status = 'Occupied', patient_id = ?, allocated_at = NOW() WHERE sl_no = ?",
                [$filteredData['patient_id'], $filteredData['bed_id']]
            );
            
            // Auto-create Billing Master row
            $billingMaster = new \GM_HMS\Models\IpdBillingMaster();
            $masterRecord = $billingMaster->getOrCreateForAdmission([
                'admission_id' => $data['admission_id'],
                'patient_id' => $filteredData['patient_id'],
                'doctor_id' => $filteredData['admitting_doctor_id'],
                'admission_date' => $filteredData['admission_date'],
                'bill_type' => (isset($filteredData['admission_type']) && $filteredData['admission_type'] === 'Insurance') ? 'INSURANCE' : 'IPD',
                'created_by' => $_SESSION['username'] ?? 'system'
            ]);
            
            $billId = $masterRecord['data']['bill_id'];
            
            // Auto-add Initial Charges (One-time only on admission)
            $admCharge = (isset($data['admission_charge']) && is_numeric($data['admission_charge'])) ? (float)$data['admission_charge'] : 350;
            $mrdCharge = (isset($data['mrd_charge']) && is_numeric($data['mrd_charge'])) ? (float)$data['mrd_charge'] : 400;
            $foodCharge = (isset($data['food_charge']) && is_numeric($data['food_charge'])) ? (float)$data['food_charge'] : 570;
            $roomCharge = (isset($bedDetails['total_bed_amount']) && is_numeric($bedDetails['total_bed_amount'])) ? (float)$bedDetails['total_bed_amount'] : 0;
            
            $roomRentBase = (isset($bedDetails['amount_per_day']) && is_numeric($bedDetails['amount_per_day'])) ? (float)$bedDetails['amount_per_day'] : 0;
            $nursingCharge = (isset($bedDetails['nursig_charge']) && is_numeric($bedDetails['nursig_charge'])) ? (float)$bedDetails['nursig_charge'] : 0;
            $doctorCharge = (isset($bedDetails['doctor_charge']) && is_numeric($bedDetails['doctor_charge'])) ? (float)$bedDetails['doctor_charge'] : 0;
            $serviceCharge = (isset($bedDetails['service_charge']) && is_numeric($bedDetails['service_charge'])) ? (float)$bedDetails['service_charge'] : 0;
            
            $roomName = $bedDetails['room_name'] ?? 'General Room';
            $breakdown = "Room Rent: ₹" . number_format($roomRentBase, 0) . " | Nursing Charges: ₹" . number_format($nursingCharge, 0) . " | Duty Doctor Charges: ₹" . number_format($doctorCharge, 0) . " | Service Charges: ₹" . number_format($serviceCharge, 0);
            $roomDesc = "Room Rent - " . $roomName . " - Day 1<br><small style='color: #6c757d; font-size: 0.85em;'>" . $breakdown . "</small>";
            
            $createdBy = $_SESSION['username'] ?? 'system';
            
            // 1. One-time Admission Charge check (only once per admission)
            $existingAdm = $this->fetchOne(
                "SELECT item_id FROM ipd_billing_items WHERE bill_id = ? AND description = 'Admission Charge' AND status != 'CANCELLED'",
                [$billId]
            );
            if (!$existingAdm && $admCharge > 0) {
                $this->query(
                    "INSERT INTO ipd_billing_items (bill_id, patient_id, admission_id, charge_date, charge_type, description, total_amount, status, created_by, created_at) VALUES (?, ?, ?, CURDATE(), 'MISC', 'Admission Charge', ?, 'COMPLETED', ?, NOW())",
                    [$billId, $filteredData['patient_id'], $data['admission_id'], $admCharge, $createdBy]
                );
            }

            // 2. One-time MRD Charge check (only once per admission)
            $existingMrd = $this->fetchOne(
                "SELECT item_id FROM ipd_billing_items WHERE bill_id = ? AND description = 'MRD Charge' AND status != 'CANCELLED'",
                [$billId]
            );
            if (!$existingMrd && $mrdCharge > 0) {
                $this->query(
                    "INSERT INTO ipd_billing_items (bill_id, patient_id, admission_id, charge_date, charge_type, description, total_amount, status, created_by, created_at) VALUES (?, ?, ?, CURDATE(), 'MISC', 'MRD Charge', ?, 'COMPLETED', ?, NOW())",
                    [$billId, $filteredData['patient_id'], $data['admission_id'], $mrdCharge, $createdBy]
                );
            }

            // 3. Food Charge Day 1 check
            $existingFood = $this->fetchOne(
                "SELECT item_id FROM ipd_billing_items WHERE bill_id = ? AND charge_date = CURDATE() AND charge_type = 'MISC' AND description LIKE 'Food Charge%' AND status != 'CANCELLED'",
                [$billId]
            );
            if (!$existingFood && $foodCharge > 0) {
                $this->query(
                    "INSERT INTO ipd_billing_items (bill_id, patient_id, admission_id, charge_date, charge_type, description, total_amount, status, created_by, created_at) VALUES (?, ?, ?, CURDATE(), 'MISC', 'Food Charge - Day 1', ?, 'COMPLETED', ?, NOW())",
                    [$billId, $filteredData['patient_id'], $data['admission_id'], $foodCharge, $createdBy]
                );
            }

            // Check if patient is admitted under insurance
            $isInsurance = (isset($filteredData['admission_type']) && strcasecmp($filteredData['admission_type'], 'Insurance') === 0) 
                        || (isset($data['bill_type']) && strcasecmp($data['bill_type'], 'INSURANCE') === 0)
                        || (isset($filteredData['credit_type']) && strcasecmp($filteredData['credit_type'], 'INSURANCE') === 0)
                        || (isset($filteredData['sponsor']) && strcasecmp($filteredData['sponsor'], 'SELF') !== 0 && trim($filteredData['sponsor']) !== '');

            // 4. Room Rent Day 1 check
            $existingRoom = $this->fetchOne(
                "SELECT item_id FROM ipd_billing_items WHERE bill_id = ? AND charge_date = CURDATE() AND charge_type = 'ROOM_RENT' AND status != 'CANCELLED'",
                [$billId]
            );
            if (!$existingRoom) {
                if ($isInsurance) {
                    // Under insurance, Room Rent MUST NOT include Nursing, Duty Doctor, or Service charges
                    if ($roomRentBase > 0) {
                        $this->query(
                            "INSERT INTO ipd_billing_items (bill_id, patient_id, admission_id, charge_date, charge_type, description, total_amount, status, created_by, created_at) VALUES (?, ?, ?, CURDATE(), 'ROOM_RENT', ?, ?, 'COMPLETED', ?, NOW())",
                            [$billId, $filteredData['patient_id'], $data['admission_id'], "Room Rent - {$roomName} - Day 1", $roomRentBase, $createdBy]
                        );
                    }
                    if ($nursingCharge > 0) {
                        $this->query(
                            "INSERT INTO ipd_billing_items (bill_id, patient_id, admission_id, charge_date, charge_type, description, total_amount, status, created_by, created_at) VALUES (?, ?, ?, CURDATE(), 'PROCEDURE', ?, ?, 'COMPLETED', ?, NOW())",
                            [$billId, $filteredData['patient_id'], $data['admission_id'], "Nursing Charges - Day 1", $nursingCharge, $createdBy]
                        );
                    }
                    if ($doctorCharge > 0) {
                        $this->query(
                            "INSERT INTO ipd_billing_items (bill_id, patient_id, admission_id, charge_date, charge_type, description, total_amount, status, created_by, created_at) VALUES (?, ?, ?, CURDATE(), 'DOCTOR_VISIT', ?, ?, 'COMPLETED', ?, NOW())",
                            [$billId, $filteredData['patient_id'], $data['admission_id'], "Duty Doctor Charges - Day 1", $doctorCharge, $createdBy]
                        );
                    }
                    if ($serviceCharge > 0) {
                        $this->query(
                            "INSERT INTO ipd_billing_items (bill_id, patient_id, admission_id, charge_date, charge_type, description, total_amount, status, created_by, created_at) VALUES (?, ?, ?, CURDATE(), 'MISC', ?, ?, 'COMPLETED', ?, NOW())",
                            [$billId, $filteredData['patient_id'], $data['admission_id'], "Service Charges - Day 1", $serviceCharge, $createdBy]
                        );
                    }
                } else if ($roomCharge > 0) {
                    $this->query(
                        "INSERT INTO ipd_billing_items (bill_id, patient_id, admission_id, charge_date, charge_type, description, total_amount, status, created_by, created_at) VALUES (?, ?, ?, CURDATE(), 'ROOM_RENT', ?, ?, 'COMPLETED', ?, NOW())",
                        [$billId, $filteredData['patient_id'], $data['admission_id'], $roomDesc, $roomCharge, $createdBy]
                    );
                }
            }

            // Recalculate Master accurately using IpdBillingMaster
            $billingMaster->recalculateMaster($billId, $createdBy);

            // Process Advance Payment if any
            require_once __DIR__ . '/../../../Models/IpdPayment.php';
            $ipdPayment = new \GM_HMS\Models\IpdPayment();
            
            if (isset($data['payments']) && is_array($data['payments'])) {
                // New logic: Multiple payment splits
                foreach ($data['payments'] as $payment) {
                    $amt = (float)($payment['amount'] ?? 0);
                    $mode = $payment['mode'] ?? 'CASH';
                    if ($amt > 0) {
                        $remarks = null;
                        if (strtoupper($mode) === 'CREDIT') {
                            $creditType = $payment['credit_type'] ?? '';
                            $sponsor = $payment['sponsor'] ?? '';
                            if ($creditType || $sponsor) {
                                $remarks = trim("Credit Category: {$creditType} | Provider: {$sponsor}");
                            }
                        }
                        $ipdPayment->recordPayment([
                            'bill_id' => $billId,
                            'admission_id' => $data['admission_id'],
                            'patient_id' => $filteredData['patient_id'],
                            'payment_type' => 'ADVANCE',
                            'payment_mode' => $mode,
                            'amount' => $amt,
                            'remarks' => $remarks,
                            'created_by' => $_SESSION['username'] ?? 'system'
                        ]);
                    }
                }
            } elseif (isset($data['advance_payment']) && is_numeric($data['advance_payment']) && (float)$data['advance_payment'] > 0) {
                // Fallback: Legacy single payment
                $paymentMethod = $data['payment_method'] ?? 'CASH';
                $ipdPayment->recordPayment([
                    'bill_id' => $billId,
                    'admission_id' => $data['admission_id'],
                    'patient_id' => $filteredData['patient_id'],
                    'payment_type' => 'ADVANCE',
                    'payment_mode' => $paymentMethod,
                    'amount' => (float)$data['advance_payment'],
                    'created_by' => $_SESSION['username'] ?? 'system'
                ]);
            }
            
            // Recalculate Master
            $billingMaster->recalculateMaster($billId, 'system');
            
            $this->commit();
            
            return ['success' => true, 'admission_id' => $data['admission_id'], 'sl_no' => $admissionId];
        } catch (Exception $e) {
            $this->rollback();
            error_log("IPD ADMISSION CREATION FAILED: " . $e->getMessage());
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
            $newBedId = $data['bed_id'] ?? ($data['bed_id'] ?? null);
            $oldBedId = $currentAdmission['bed_id'];
            
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

                // Get valid schema columns
                $schema = $this->db->fetchAll("DESCRIBE ipd_admissions");
                $validColumns = array_column($schema, 'Field');

                // Populate bed details for admission update
                $data['ward'] = $bedDetails['ward_name'];
                $data['floor_number'] = $bedDetails['floor_number'];
                $data['floor_name'] = $bedDetails['floor_name'];
                $data['ward_name'] = $bedDetails['ward_name'];
                $data['room_no'] = $bedDetails['room_number'];
                $data['room_name'] = $bedDetails['room_name'];
                if (in_array('room_type', $validColumns)) $data['room_type'] = $bedDetails['room_type'];
                if (in_array('ward_type', $validColumns)) $data['ward_type'] = $bedDetails['room_type'];
                if (in_array('amount_per_day', $validColumns)) $data['amount_per_day'] = $bedDetails['amount_per_day'];
                if (in_array('nursig_charge', $validColumns)) $data['nursig_charge'] = $bedDetails['nursig_charge'];
                if (in_array('doctor_charge', $validColumns)) $data['doctor_charge'] = $bedDetails['doctor_charge'];
                if (in_array('service_charge', $validColumns)) $data['service_charge'] = $bedDetails['service_charge'];
                $data['bed_id'] = $newBedId;

                // Allocate new bed
                try {
                    $this->query(
                        "UPDATE hospital_beds SET bed_status = 'Occupied', patient_id = ?, allocated_at = NOW() WHERE sl_no = ?",
                        [$currentAdmission['patient_id'], $newBedId]
                    );
                } catch (Exception $e) {
                    throw new Exception("Could not allocate new bed: " . $e->getMessage());
                }
                
                // Filter data to valid columns
                $filteredData = [];
                foreach ($data as $field => $value) {
                    if (in_array($field, $validColumns)) {
                        $filteredData[$field] = $value;
                    }
                }

                // Update admission
                $result = $this->update($id, $filteredData);
                
                $this->commit();
                $transactionStarted = false;
                return true;
            } else {
                // No bed change, get valid columns and filter
                $schema = $this->db->fetchAll("DESCRIBE ipd_admissions");
                $validColumns = array_column($schema, 'Field');
                
                $filteredData = [];
                foreach ($data as $field => $value) {
                    if (in_array($field, $validColumns)) {
                        $filteredData[$field] = $value;
                    }
                }

                $result = $this->update($id, $filteredData);
                return true;
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
            
            // Release bed and make it Available for next patient
            if (!empty($admission['bed_id'])) {
                try {
                    $this->query(
                        "UPDATE hospital_beds SET bed_status = 'Available', patient_id = NULL, admission_id = NULL, released_at = NOW() WHERE sl_no = ?",
                        [$admission['bed_id']]
                    );
                } catch (Exception $e) {
                    error_log("Could not release bed by bed_id: " . $e->getMessage());
                }
            }
            if (!empty($admission['admission_id'])) {
                try {
                    $this->query(
                        "UPDATE hospital_beds SET bed_status = 'Available', patient_id = NULL, admission_id = NULL, released_at = NOW() WHERE admission_id = ?",
                        [$admission['admission_id']]
                    );
                } catch (Exception $e) {
                    error_log("Could not release bed by admission_id: " . $e->getMessage());
                }
            }
            if (!empty($admission['patient_id'])) {
                try {
                    $this->query(
                        "UPDATE hospital_beds SET bed_status = 'Available', patient_id = NULL, admission_id = NULL, released_at = NOW() WHERE patient_id = ?",
                        [$admission['patient_id']]
                    );
                } catch (Exception $e) {
                    error_log("Could not release bed by patient_id: " . $e->getMessage());
                }
            }

            // Lock and finalize billing master with discharge date
            try {
                $this->query(
                    "UPDATE ipd_billing_master SET discharge_date = ?, billing_status = 'FINALIZED', updated_at = NOW() WHERE admission_id = ?",
                    [$dischargeDate, $admissionId]
                );

                require_once __DIR__ . '/IpdBillingMaster.php';
                $bm = new IpdBillingMaster();
                $master = $bm->fetchOne("SELECT bill_id FROM ipd_billing_master WHERE admission_id = ?", [$admissionId]);
                if ($master) {
                    $bm->recalculateMaster($master['bill_id'], 'system');
                }
            } catch (Exception $e) {
                error_log("Discharge billing finalization warning: " . $e->getMessage());
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
                "SELECT COALESCE(SUM(grand_total), 0) as total FROM ipd_billing_master WHERE admission_id = ?",
                [$admissionId]
            );
            return (float)$result['total'];
        } catch (Exception $e) {
            error_log("Error fetching charges from ipd_billing_master: " . $e->getMessage());
            return 0.0;
        }
    }
    
    /**
     * Calculate total payments for admission
     */
    public function calculateTotalPayments($admissionId) {
        try {
            $result = $this->fetchOne(
                "SELECT COALESCE(SUM(amount_paid), 0) as total FROM ipd_billing_master WHERE admission_id = ?",
                [$admissionId]
            );
            return (float)$result['total'];
        } catch (Exception $e) {
            error_log("Error fetching payments from ipd_billing_master: " . $e->getMessage());
            return 0.0;
        }
    }
    
    /**
     * Get balance due for admission
     */
    public function getBalance($admissionId) {
        try {
            // Fetch directly from ipd_billing_master since it maintains grand_total, amount_paid, and balance_due
            $result = $this->fetchOne(
                "SELECT COALESCE(SUM(grand_total), 0) as total_charges, 
                        COALESCE(SUM(amount_paid), 0) as total_payments,
                        COALESCE(SUM(balance_due), 0) as balance_due 
                 FROM ipd_billing_master 
                 WHERE admission_id = ?",
                [$admissionId]
            );
            
            if ($result) {
                return [
                    'total_charges' => (float)$result['total_charges'],
                    'total_payments' => (float)$result['total_payments'],
                    'balance_due' => (float)$result['balance_due']
                ];
            }
        } catch (Exception $e) {
            error_log("Error fetching balance from ipd_billing_master: " . $e->getMessage());
        }
        
        // Fallback
        return [
            'total_charges' => 0.0,
            'total_payments' => 0.0,
            'balance_due' => 0.0
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
