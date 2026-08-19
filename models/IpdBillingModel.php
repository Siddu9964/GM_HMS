<?php
namespace GM_HMS\Models;

use GM_HMS\Database\SecureDatabase;
use Exception;

/**
 * IPD Billing Model
 * 
 * Handles all IPD billing operations including daily charges,
 * room charges, procedures, medications, and discharge billing
 * 
 * @package GM_HMS\Models
 * @version 1.0.0
 */
class IpdBillingModel {
    private $db;
    
    public function __construct() {
        $this->db = SecureDatabase::getInstance();
    }
    
    public function createBill($data) {
     try {
            $this->db->beginTransaction();
            
            $billId = $this->generateBillId();
            $admissionId = $data['admission_id'];
            
            $subtotal = 0;
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $subtotal += ($item['quantity'] * $item['unit_price']);
                }
            }
            
            $discountAmt = $data['discount_amount'] ?? 0;
            $grandTotal = $subtotal - $discountAmt;
            if ($grandTotal < 0) $grandTotal = 0;
            
            $amountPaid = 0;
            if (!empty($data['payment']) && isset($data['payment']['amount'])) {
                $amountPaid = $data['payment']['amount'];
            }
            
            $balanceDue = max(0, $grandTotal - $amountPaid);
            $paymentStatus = 'Unpaid';
            if ($amountPaid >= $grandTotal && $grandTotal > 0) $paymentStatus = 'Paid';
            elseif ($amountPaid > 0) $paymentStatus = 'Partial';
            
            $admSql = "SELECT admission_date FROM ipd_admissions WHERE admission_id = ?";
            $admission = $this->db->fetchOne($admSql, [$admissionId]);
            $admissionDate = $admission['admission_date'] ?? date('Y-m-d');
            
            $sql = "INSERT INTO ipd_billing_master (
                        bill_id, admission_id, patient_id, doctor_id, admission_date,
                        subtotal, discount_amount, discount_percentage,
                        grand_total, amount_paid, balance_due, payment_status,
                        notes, created_by, payment_mode, referral_type, referred_by, sponsor
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
            $this->db->execute($sql, [
                $billId,
                $admissionId,
                $data['patient_id'],
                $data['doctor_id'] ?? null,
                $admissionDate,
                $subtotal,
                $discountAmt,
                $data['discount_percentage'] ?? 0,
                $grandTotal,
                $amountPaid,
                $balanceDue,
                $paymentStatus,
                $data['remarks'] ?? $data['notes'] ?? null,
                $_SESSION['user_id'] ?? 'system',
                $data['payment']['payment_mode'] ?? null,
                $data['referral_type'] ?? null,
                $data['referred_by'] ?? null,
                $data['sponsor'] ?? null
            ]);
            
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $this->addDailyCharge($billId, [
                        'charge_type' => $item['item_type'],
                        'item_code' => $item['item_code'] ?? null,
                        'item_name' => $item['item_name'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'is_taxable' => $item['is_taxable'] ?? 0,
                        'tax_percentage' => $item['tax_percentage'] ?? 0,
                        'discount_amount' => $item['discount_amount'] ?? 0,
                        'payment_mode' => $data['payment']['payment_mode'] ?? null
                    ]);
                }
            }
            
            $receiptId = null;
            if ($amountPaid > 0) {
                // recordPayment updates the master table balances
                $receiptId = $this->recordPayment($billId, [
                    'amount' => $amountPaid,
                    'payment_method' => $data['payment']['payment_mode'] ?? 'Cash',
                    'transaction_id' => $data['payment']['reference_no'] ?? null,
                    'notes' => 'Initial payment'
                ]);
            }
            
            $this->db->commit();
            return ['bill_id' => $billId, 'receipt_id' => $receiptId];
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception("Failed to generate IPD bill: " . $e->getMessage());
        }
    }
    
    /**
     * Create IPD bill for admission
     * 
     * @param string $admissionId Admission ID
     * @param array $billData Additional bill data
     * @return string Bill ID
     */
    public function createAdmissionBill($admissionId, $billData = []) {
        try {
            $this->db->beginTransaction();
            
            // Get admission details
            $admSql = "SELECT * FROM ipd_admissions WHERE admission_id = ?";
            $admission = $this->db->fetchOne($admSql, [$admissionId]);
            
            if (!$admission) {
                throw new Exception("Admission not found");
            }
            
            // Generate Bill ID
            $billId = $this->generateBillId();
            
            // Insert bill master
            $sql = "INSERT INTO ipd_billing_master (
                        bill_id, admission_id, patient_id, doctor_id,
                        admission_date, created_by, referral_type, referred_by, sponsor
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $this->db->execute($sql, [
                $billId,
                $admissionId,
                $admission['patient_id'],
                $admission['doctor_id'],
                $admission['admission_date'],
                $billData['created_by'] ?? ($_SESSION['user_id'] ?? 'system'),
                $billData['referral_type'] ?? null,
                $billData['referred_by'] ?? null,
                $billData['sponsor'] ?? null
            ]);
            
            // Log action
            $this->logBillingAction($billId, 'Created', 'IPD bill created for admission');
            
            $this->db->commit();
            return $billId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception("Failed to create IPD bill: " . $e->getMessage());
        }
    }
    
    /**
     * Add daily charge to IPD bill
     * 
     * @param string $billId Bill ID
     * @param array $charge Charge data
     * @return int Item ID
     */
    public function addDailyCharge($billId, $charge) {
        $quantity = $charge['quantity'] ?? 1;
        $unitPrice = $charge['unit_price'];
        $totalPrice = $quantity * $unitPrice;
        
        $allowedTypes = ['Room', 'Procedure', 'Medication', 'Investigation', 'Nursing', 'Consumable', 'Other'];
        $incomingType = $charge['charge_type'] ?? 'Other';
        $mappedType = 'Other';
        
        if (in_array($incomingType, $allowedTypes)) {
            $mappedType = $incomingType;
        } else {
            $t = strtolower($incomingType);
            if (strpos($t, 'medicine') !== false || strpos($t, 'drug') !== false) {
                $mappedType = 'Medication';
            } elseif (strpos($t, 'investigation') !== false || strpos($t, 'lab') !== false || strpos($t, 'blood') !== false || strpos($t, 'scan') !== false || strpos($t, 'x-ray') !== false || strpos($t, 'x ray') !== false || strpos($t, 'radiology') !== false) {
                $mappedType = 'Investigation';
            } elseif (strpos($t, 'procedure') !== false || strpos($t, 'surgery') !== false) {
                $mappedType = 'Procedure';
            }
        }

        $chargeDate = $charge['charge_date'] ?? date('Y-m-d');
        
        // Fetch patient_id and admission_id from master table
        $masterSql = "SELECT patient_id, admission_id FROM ipd_billing_master WHERE bill_id = ?";
        $masterData = $this->db->fetchOne($masterSql, [$billId]);
        $patientId = $masterData['patient_id'] ?? null;
        $admissionId = $masterData['admission_id'] ?? null;

        $newItem = [
            'charge_type' => $mappedType,
            'item_code' => $charge['item_code'] ?? null,
            'item_name' => $charge['item_name'],
            'quantity' => $quantity,
            'unit_price' => (float)$unitPrice,
            'total_price' => (float)$totalPrice,
            'discount_amount' => (float)($charge['discount_amount'] ?? 0.00),
            'is_taxable' => $charge['is_taxable'] ?? 0,
            'tax_percentage' => (float)($charge['tax_percentage'] ?? 0),
            'payment_mode' => $charge['payment_mode'] ?? null
        ];

        // Check if there is already a row for this date
        $checkSql = "SELECT item_id, items_json FROM ipd_billing_items WHERE bill_id = ? AND charge_date = ?";
        $existing = $this->db->fetchOne($checkSql, [$billId, $chargeDate]);

        if ($existing) {
            $itemsArray = [];
            if (!empty($existing['items_json'])) {
                $itemsArray = json_decode($existing['items_json'], true) ?: [];
            }
            $itemsArray[] = $newItem;
            $itemsJson = json_encode($itemsArray);
            
            $updateSql = "UPDATE ipd_billing_items SET items_json = ? WHERE item_id = ?";
            $this->db->execute($updateSql, [$itemsJson, $existing['item_id']]);
            $itemId = $existing['item_id'];
        } else {
            $itemsArray = [$newItem];
            $itemsJson = json_encode($itemsArray);
            
            $insertSql = "INSERT INTO ipd_billing_items (
                            bill_id, patient_id, admission_id, charge_date, items_json, created_by
                        ) VALUES (?, ?, ?, ?, ?, ?)";
            
            $execResult = $this->db->execute($insertSql, [
                $billId,
                $patientId,
                $admissionId,
                $chargeDate,
                $itemsJson,
                $charge['created_by'] ?? ($_SESSION['user_id'] ?? 'system')
            ]);
            $itemId = $execResult['insert_id'] ?? 0;
        }
        
        $this->calculateTotals($billId);
        
        return $itemId;
    }
    
    /**
     * Calculate room charges automatically
     * 
     * @param string $billId Bill ID
     * @param string $fromDate Start date
     * @param string $toDate End date
     * @return int Number of charges added
     */
    public function calculateRoomCharges($billId, $fromDate = null, $toDate = null) {
        // Get bill details
        $billSql = "SELECT ibm.*, ia.bed_id 
                    FROM ipd_billing_master ibm
                    LEFT JOIN ipd_admissions ia ON ibm.admission_id = ia.admission_id
                    WHERE ibm.bill_id = ?";
        $bill = $this->db->fetchOne($billSql, [$billId]);
        
        if (!$bill || !$bill['bed_id']) {
            return 0;
        }
        
        // Get bed/room details
        $bedSql = "SELECT b.*, r.room_type, r.room_number, f.floor_name, bl.block_name
                   FROM ipd_beds b
                   LEFT JOIN ipd_rooms r ON b.room_id = r.room_id
                   LEFT JOIN ipd_floors f ON r.floor_id = f.floor_id
                   LEFT JOIN ipd_blocks bl ON f.block_id = bl.block_id
                   WHERE b.bed_id = ?";
        $bed = $this->db->fetchOne($bedSql, [$bill['bed_id']]);
        
        // Get room charge from service catalog
        // Calculate date range
        $startDate = $fromDate ?? $bill['admission_date'];
        $endDate = $toDate ?? date('Y-m-d');
        
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $interval = $start->diff($end);
        $days = $interval->days;
        
        if ($days <= 0) {
            $days = 1; // Minimum 1 day charge
        }
        
        // Check if charges already exist
        $checkSql = "SELECT items_json FROM ipd_billing_items 
                     WHERE bill_id = ? AND charge_date BETWEEN ? AND ?";
        $rows = $this->db->fetchAll($checkSql, [$billId, $startDate, $endDate]);
        
        $count = 0;
        foreach ($rows as $row) {
            $items = json_decode($row['items_json'], true) ?: [];
            foreach ($items as $item) {
                if (($item['charge_type'] ?? '') === 'Room') {
                    $count++;
                    break;
                }
            }
        }
        
        if ($count > 0) {
            return 0; // Already calculated
        }
        
        // Add room charges for each day
        $chargesAdded = 0;
        $currentDate = clone $start;
        
        $bedAmount = $bed['total_bed_amount'] ?? $bed['amount_per_day'] ?? 0;
        
        while ($currentDate <= $end) {
            $this->addDailyCharge($billId, [
                'charge_date' => $currentDate->format('Y-m-d'),
                'charge_type' => 'Room',
                'item_code' => $this->getRoomServiceCode($bed['room_type']),
                'item_name' => "Room charge - " . $bed['room_number'],
                'item_description' => "Room charge for {$bed['room_type']} - Bed {$bed['bed_number']}",
                'quantity' => 1,
                'unit_price' => $bedAmount,
                'is_taxable' => 0,
                'tax_percentage' => 0
            ]);
            
            $chargesAdded++;
            $currentDate->modify('+1 day');
        }
        
        return $chargesAdded;
    }
    
    /**
     * Get room service code based on room type
     */
    private function getRoomServiceCode($roomType) {
        $mapping = [
            'General Ward' => 'ROOM-GEN',
            'Semi-Private' => 'ROOM-SEMI',
            'Private' => 'ROOM-PVT',
            'ICU' => 'ROOM-ICU'
        ];
        
        return $mapping[$roomType] ?? 'ROOM-GEN';
    }
    
    /**
     * Generate discharge bill
     * 
     * @param string $admissionId Admission ID
     * @param string $dischargeDate Discharge date
     * @return array Bill details
     */
    public function generateDischargeBill($admissionId, $dischargeDate = null) {
        try {
            $this->db->beginTransaction();
            
            $dischargeDate = $dischargeDate ?? date('Y-m-d');
            
            // Get or create bill
            $billSql = "SELECT bill_id FROM ipd_billing_master WHERE admission_id = ?";
            $existing = $this->db->fetchOne($billSql, [$admissionId]);
            
            if ($existing) {
                $billId = $existing['bill_id'];
            } else {
                $billId = $this->createAdmissionBill($admissionId);
            }
            
            // Calculate room charges up to discharge date
            $this->calculateRoomCharges($billId, null, $dischargeDate);
            
            // Update discharge date and calculate total days
            $billData = $this->db->fetchOne("SELECT admission_date FROM ipd_billing_master WHERE bill_id = ?", [$billId]);
            $admissionDate = new \DateTime($billData['admission_date']);
            $discharge = new \DateTime($dischargeDate);
            $totalDays = $admissionDate->diff($discharge)->days + 1; // Include admission day
            
            $updateSql = "UPDATE ipd_billing_master SET 
                            discharge_date = ?,
                            total_days = ?
                          WHERE bill_id = ?";
            $this->db->execute($updateSql, [$dischargeDate, $totalDays, $billId]);
            
            // Calculate final totals
            $this->calculateTotals($billId);
            
            // Log action
            $this->logBillingAction($billId, 'Updated', 'Discharge bill generated');
            
            $this->db->commit();
            
            return $this->getBillDetails($billId);
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception("Failed to generate discharge bill: " . $e->getMessage());
        }
    }
    
    /**
     * Calculate bill totals with category-wise breakdown
     * 
     * @param string $billId Bill ID
     * @return array Calculated totals
     */
    public function calculateTotals($billId) {
        // Get all items grouped by charge type from json arrays
        $sql = "SELECT items_json FROM ipd_billing_items WHERE bill_id = ?";
        
        $rows = $this->db->fetchAll($sql, [$billId]);
        
        $roomCharges = 0;
        $procedureCharges = 0;
        $medicationCharges = 0;
        $investigationCharges = 0;
        $nursingCharges = 0;
        $consumableCharges = 0;
        $otherCharges = 0;
        $totalTax = 0;
        
        foreach ($rows as $row) {
            $items = json_decode($row['items_json'], true) ?: [];
            foreach ($items as $item) {
                $amount = (float)($item['total_price'] ?? 0) - (float)($item['discount_amount'] ?? 0);
                
                $isTaxable = !empty($item['is_taxable']);
                $taxPercentage = (float)($item['tax_percentage'] ?? 0);
                if ($isTaxable && $taxPercentage > 0) {
                    $totalTax += ($amount * $taxPercentage / 100);
                }
                
                $type = $item['charge_type'] ?? 'Other';
                switch ($type) {
                    case 'Room':
                        $roomCharges += $amount;
                        break;
                    case 'Procedure':
                        $procedureCharges += $amount;
                        break;
                    case 'Medication':
                        $medicationCharges += $amount;
                        break;
                    case 'Investigation':
                        $investigationCharges += $amount;
                        break;
                    case 'Nursing':
                        $nursingCharges += $amount;
                        break;
                    case 'Consumable':
                        $consumableCharges += $amount;
                        break;
                    default:
                        $otherCharges += $amount;
                }
            }
        }
        
        $subtotal = $roomCharges + $procedureCharges + $medicationCharges + 
                    $investigationCharges + $nursingCharges + $consumableCharges + $otherCharges;
        
        // Get bill-level discount
        $billSql = "SELECT discount_amount, discount_percentage FROM ipd_billing_master WHERE bill_id = ?";
        $billData = $this->db->fetchOne($billSql, [$billId]);
        
        $billDiscount = $billData['discount_amount'] ?? 0;
        if ($billData['discount_percentage'] > 0) {
            $billDiscount = ($subtotal * $billData['discount_percentage']) / 100;
        }
        
        $taxableAmount = $subtotal - $billDiscount;
        $grandTotal = $taxableAmount + $totalTax;
        
        // Update bill master (only columns that exist in the schema)
        $updateSql = "UPDATE ipd_billing_master SET 
                        room_charges = ?,
                        consumable_charges = ?,
                        other_charges = ?,
                        subtotal = ?,
                        discount_amount = ?,
                        grand_total = ?,
                        balance_due = CASE WHEN (grand_total - amount_paid) < 0 THEN 0 ELSE (grand_total - amount_paid) END
                      WHERE bill_id = ?";
        
        $this->db->execute($updateSql, [
            $roomCharges,
            $consumableCharges,
            // Roll procedure, medication, investigation, nursing and tax into other_charges if needed, 
            // but actually we just sum the rest as other_charges for the DB column
            $procedureCharges + $medicationCharges + $investigationCharges + $nursingCharges + $otherCharges + $totalTax,
            $subtotal,
            $billDiscount,
            $grandTotal,
            $billId
        ]);
        
        return [
            'room_charges' => $roomCharges,
            'procedure_charges' => $procedureCharges,
            'medication_charges' => $medicationCharges,
            'investigation_charges' => $investigationCharges,
            'nursing_charges' => $nursingCharges,
            'consumable_charges' => $consumableCharges,
            'other_charges' => $otherCharges,
            'subtotal' => $subtotal,
            'tax_amount' => $totalTax,
            'grand_total' => $grandTotal
        ];
    }
    
    /**
     * Record payment for IPD bill
     * 
     * @param string $billId Bill ID
     * @param array $paymentData Payment details
     * @return string Receipt ID
     */
    public function recordPayment($billId, $paymentData) {
        try {
            $this->db->beginTransaction();
            
            // Generate receipt ID
            $receiptId = $this->generateReceiptId('IPD');
            
            // Get bill details
            $billSql = "SELECT patient_id, grand_total, amount_paid FROM ipd_billing_master WHERE bill_id = ?";
            $bill = $this->db->fetchOne($billSql, [$billId]);
            
            $amount = $paymentData['amount'];
            $newAmountPaid = $bill['amount_paid'] + $amount;
            $balanceDue = max(0, $bill['grand_total'] - $newAmountPaid);
            
            // Determine payment status
            if ($balanceDue <= 0) {
                $paymentStatus = 'Paid';
            } elseif ($newAmountPaid > 0) {
                $paymentStatus = 'Partial';
            } else {
                $paymentStatus = 'Pending';
            }
            
            // Insert payment receipt
            $receiptSql = "INSERT INTO payment_receipts (
                            receipt_id, bill_id, bill_type, patient_id,
                            payment_date, payment_time, amount, payment_method,
                            transaction_id, card_last_digits, cheque_number, bank_name,
                            insurance_company, insurance_claim_number,
                            received_by, notes
                          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $this->db->execute($receiptSql, [
                $receiptId,
                $billId,
                'IPD',
                $bill['patient_id'],
                $paymentData['payment_date'] ?? date('Y-m-d'),
                $paymentData['payment_time'] ?? date('H:i:s'),
                $amount,
                $paymentData['payment_method'],
                $paymentData['transaction_id'] ?? null,
                $paymentData['card_last_digits'] ?? null,
                $paymentData['cheque_number'] ?? null,
                $paymentData['bank_name'] ?? null,
                $paymentData['insurance_company'] ?? null,
                $paymentData['insurance_claim_number'] ?? null,
                $paymentData['received_by'] ?? ($_SESSION['user_id'] ?? 'system'),
                $paymentData['notes'] ?? null
            ]);
            
            // Update bill master
            $updateSql = "UPDATE ipd_billing_master SET 
                            amount_paid = ?,
                            balance_due = ?,
                            payment_status = ?
                          WHERE bill_id = ?";
            
            $this->db->execute($updateSql, [$newAmountPaid, $balanceDue, $paymentStatus, $billId]);
            
            // Log action
            $this->logBillingAction($billId, 'Payment Received', "Payment of ₹{$amount} received");
            
            $this->db->commit();
            return $receiptId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception("Failed to record payment: " . $e->getMessage());
        }
    }
    
    /**
     * Get bill details with items
     * 
     * @param string $billId Bill ID
     * @return array Bill details
     */
    public function getBillDetails($billId) {
        $billSql = "SELECT ibm.*, 
                           TRIM(CONCAT(COALESCE(p.first_name, ''), ' ', COALESCE(p.last_name, ''))) AS patient_name,
                           p.age, p.sex, p.phone, p.address, p.aadhar,
                           d.full_name AS doctor_name, d.specialization,
                           ia.admission_type, ia.diagnosis
                    FROM ipd_billing_master ibm
                    LEFT JOIN patient p ON ibm.patient_id = p.patient_id
                    LEFT JOIN doctors d ON ibm.doctor_id = d.doctor_id
                    LEFT JOIN ipd_admissions ia ON ibm.admission_id = ia.admission_id
                    WHERE ibm.bill_id = ?";
        
        $bill = $this->db->fetchOne($billSql, [$billId]);
        
        if (!$bill) {
            throw new Exception("Bill not found");
        }
        
        // Get items grouped by date and flatten them
        $itemsSql = "SELECT * FROM ipd_billing_items 
                     WHERE bill_id = ? 
                     ORDER BY charge_date DESC";
        $dbItems = $this->db->fetchAll($itemsSql, [$billId]);
        
        $items = [];
        foreach ($dbItems as $dbItem) {
            $jsonItems = [];
            if (!empty($dbItem['items_json'])) {
                $jsonItems = json_decode($dbItem['items_json'], true) ?: [];
            }
            foreach ($jsonItems as $jItem) {
                $jItem['item_id'] = $dbItem['item_id'];
                $jItem['bill_id'] = $dbItem['bill_id'];
                $jItem['charge_date'] = $dbItem['charge_date'];
                $jItem['created_by'] = $dbItem['created_by'];
                $items[] = $jItem;
            }
        }
        
        // Get payments
        $paymentsSql = "SELECT * FROM payment_receipts 
                        WHERE bill_id = ? AND is_cancelled = FALSE 
                        ORDER BY payment_date DESC";
        $payments = $this->db->fetchAll($paymentsSql, [$billId]);
        
        $bill['items'] = $items;
        $bill['payments'] = $payments;
        
        return $bill;
    }
    
    /**
     * Get bill by admission ID (creates one if it doesn't exist)
     * @param string $admissionId
     * @return array Bill details
     */
    public function getBillByAdmissionId($admissionId) {
        $billRecord = $this->db->fetchOne("SELECT bill_id FROM ipd_billing_master WHERE admission_id = ?", [$admissionId]);
        
        if ($billRecord) {
            $billId = $billRecord['bill_id'];
        } else {
            $billId = $this->createAdmissionBill($admissionId);
        }
        
        // Auto-calculate daily charges before returning
        $this->calculateRoomCharges($billId);
        
        return $this->getBillDetails($billId);
    }
    
    /**
     * Get all IPD bills with filters
     * 
     * @param array $filters Filter criteria
     * @return array List of bills
     */
    public function getAllBills($filters = []) {
        $sql = "SELECT ibm.*, 
                       TRIM(CONCAT(COALESCE(p.first_name, ''), ' ', COALESCE(p.last_name, ''))) AS patient_name,
                       TRIM(CONCAT(COALESCE(p.first_name, ''), ' ', COALESCE(p.last_name, ''))) AS name,
                       d.full_name AS doctor_name
                FROM ipd_billing_master ibm
                LEFT JOIN patient p ON ibm.patient_id = p.patient_id
                LEFT JOIN doctors d ON ibm.doctor_id = d.doctor_id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['payment_status'])) {
            $sql .= " AND payment_status = ?";
            $params[] = $filters['payment_status'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND admission_date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND admission_date <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['patient_id'])) {
            $sql .= " AND patient_id = ?";
            $params[] = $filters['patient_id'];
        }
        
        $sql .= " ORDER BY admission_date DESC, bill_id DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Generate unique bill ID
     * Format: IPD-YYYYMMDD-XXXX
     * 
     * @return string Bill ID
     */
    private function generateBillId() {
        $prefix = 'IPD';
        $dateStr = date('Ymd');
        
        $sql = "SELECT bill_id FROM ipd_billing_master 
                WHERE bill_id LIKE ? 
                ORDER BY bill_id DESC LIMIT 1";
        
        $lastBill = $this->db->fetchOne($sql, ["{$prefix}-{$dateStr}%"]);
        
        if ($lastBill) {
            $parts = explode('-', $lastBill['bill_id']);
            $newNum = intval(end($parts)) + 1;
        } else {
            $newNum = 1;
        }
        
        return sprintf("%s-%s-%04d", $prefix, $dateStr, $newNum);
    }
    
    /**
     * Generate unique receipt ID
     * Format: RCP-IPD-YYYYMMDD-XXXX
     * 
     * @param string $type Bill type (OPD/IPD)
     * @return string Receipt ID
     */
    private function generateReceiptId($type = 'IPD') {
        $prefix = "RCP-{$type}";
        $dateStr = date('Ymd');
        
        $sql = "SELECT receipt_id FROM payment_receipts 
                WHERE receipt_id LIKE ? 
                ORDER BY receipt_id DESC LIMIT 1";
        
        $lastReceipt = $this->db->fetchOne($sql, ["{$prefix}-{$dateStr}%"]);
        
        if ($lastReceipt) {
            $parts = explode('-', $lastReceipt['receipt_id']);
            $newNum = intval(end($parts)) + 1;
        } else {
            $newNum = 1;
        }
        
        return sprintf("%s-%s-%04d", $prefix, $dateStr, $newNum);
    }
    
    /**
     * Log billing action
     * 
     * @param string $billId Bill ID
     * @param string $action Action type
     * @param string $remarks Remarks
     */
    private function logBillingAction($billId, $action, $remarks = null) {
        $sql = "INSERT INTO billing_audit_log (bill_id, bill_type, action, action_by, remarks)
                VALUES (?, 'IPD', ?, ?, ?)";
        
        $this->db->execute($sql, [
            $billId,
            $action,
            $_SESSION['user_id'] ?? 'system',
            $remarks
        ]);
    }
    
    /**
     * Get billing statistics
     * 
     * @return array Statistics
     */
    public function getStatistics() {
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');
        
        $stats = [];
        
        // Today's revenue
        $sql = "SELECT COALESCE(SUM(amount_paid), 0) as revenue 
                FROM ipd_billing_master 
                WHERE DATE(created_at) = ?";
        $result = $this->db->fetchOne($sql, [$today]);
        $stats['today_revenue'] = $result['revenue'];
        
        // Month's revenue
        $sql = "SELECT COALESCE(SUM(amount_paid), 0) as revenue 
                FROM ipd_billing_master 
                WHERE DATE(created_at) >= ?";
        $result = $this->db->fetchOne($sql, [$monthStart]);
        $stats['month_revenue'] = $result['revenue'];
        
        // Active admissions with pending bills
        $sql = "SELECT COUNT(*) as count 
                FROM ipd_billing_master 
                WHERE payment_status IN ('Pending', 'Partial') 
                AND discharge_date IS NULL";
        $result = $this->db->fetchOne($sql);
        $stats['active_bills'] = $result['count'];
        
        // Outstanding amount
        $sql = "SELECT COALESCE(SUM(balance_due), 0) as amount 
                FROM ipd_billing_master 
                WHERE payment_status IN ('Pending', 'Partial')";
        $result = $this->db->fetchOne($sql);
        $stats['outstanding_amount'] = $result['amount'];
        
        return $stats;
    }
}
