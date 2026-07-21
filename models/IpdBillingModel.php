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
                        admission_date, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?)";
            
            $this->db->execute($sql, [
                $billId,
                $admissionId,
                $admission['patient_id'],
                $admission['doctor_id'],
                $admission['admission_date'],
                $billData['created_by'] ?? $_SESSION['user_id']
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
        
        $sql = "INSERT INTO ipd_billing_items (
                    bill_id, charge_date, charge_type, item_code, item_name, item_description,
                    quantity, unit_price, total_price, is_taxable, tax_percentage, 
                    discount_amount, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->execute($sql, [
            $billId,
            $charge['charge_date'] ?? date('Y-m-d'),
            $charge['charge_type'],
            $charge['item_code'] ?? null,
            $charge['item_name'],
            $charge['item_description'] ?? null,
            $quantity,
            $unitPrice,
            $totalPrice,
            $charge['is_taxable'] ?? true,
            $charge['tax_percentage'] ?? 18.00,
            $charge['discount_amount'] ?? 0.00,
            $charge['created_by'] ?? $_SESSION['user_id']
        ]);
        
        $itemId = $this->db->lastInsertId();
        
        // Recalculate totals
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
        $roomType = $bed['room_type'];
        $serviceCode = $this->getRoomServiceCode($roomType);
        
        $serviceSql = "SELECT * FROM billing_service_catalog WHERE service_code = ?";
        $service = $this->db->fetchOne($serviceSql, [$serviceCode]);
        
        if (!$service) {
            return 0;
        }
        
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
        $checkSql = "SELECT COUNT(*) as count FROM ipd_billing_items 
                     WHERE bill_id = ? AND charge_type = 'Room' AND charge_date BETWEEN ? AND ?";
        $existing = $this->db->fetchOne($checkSql, [$billId, $startDate, $endDate]);
        
        if ($existing['count'] > 0) {
            return 0; // Already calculated
        }
        
        // Add room charges for each day
        $chargesAdded = 0;
        $currentDate = clone $start;
        
        while ($currentDate <= $end) {
            $this->addDailyCharge($billId, [
                'charge_date' => $currentDate->format('Y-m-d'),
                'charge_type' => 'Room',
                'item_code' => $service['service_code'],
                'item_name' => $service['service_name'] . " - " . $bed['room_number'],
                'item_description' => "Room charge for {$bed['room_type']} - Bed {$bed['bed_number']}",
                'quantity' => 1,
                'unit_price' => $service['unit_price'],
                'is_taxable' => $service['is_taxable'],
                'tax_percentage' => $service['tax_percentage']
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
        // Get all items grouped by charge type
        $sql = "SELECT charge_type, 
                       SUM(total_price - discount_amount) as category_total,
                       SUM(CASE WHEN is_taxable THEN (total_price - discount_amount) * tax_percentage / 100 ELSE 0 END) as category_tax
                FROM ipd_billing_items 
                WHERE bill_id = ? 
                GROUP BY charge_type";
        
        $categories = $this->db->fetchAll($sql, [$billId]);
        
        $roomCharges = 0;
        $procedureCharges = 0;
        $medicationCharges = 0;
        $investigationCharges = 0;
        $nursingCharges = 0;
        $consumableCharges = 0;
        $otherCharges = 0;
        $totalTax = 0;
        
        foreach ($categories as $cat) {
            $amount = $cat['category_total'];
            $tax = $cat['category_tax'];
            $totalTax += $tax;
            
            switch ($cat['charge_type']) {
                case 'Room':
                    $roomCharges = $amount;
                    break;
                case 'Procedure':
                    $procedureCharges = $amount;
                    break;
                case 'Medication':
                    $medicationCharges = $amount;
                    break;
                case 'Investigation':
                    $investigationCharges = $amount;
                    break;
                case 'Nursing':
                    $nursingCharges = $amount;
                    break;
                case 'Consumable':
                    $consumableCharges = $amount;
                    break;
                default:
                    $otherCharges += $amount;
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
        
        // Update bill master
        $updateSql = "UPDATE ipd_billing_master SET 
                        room_charges = ?,
                        procedure_charges = ?,
                        medication_charges = ?,
                        investigation_charges = ?,
                        nursing_charges = ?,
                        consumable_charges = ?,
                        other_charges = ?,
                        subtotal = ?,
                        discount_amount = ?,
                        taxable_amount = ?,
                        tax_amount = ?,
                        grand_total = ?,
                        balance_due = grand_total - amount_paid
                      WHERE bill_id = ?";
        
        $this->db->execute($updateSql, [
            $roomCharges,
            $procedureCharges,
            $medicationCharges,
            $investigationCharges,
            $nursingCharges,
            $consumableCharges,
            $otherCharges,
            $subtotal,
            $billDiscount,
            $taxableAmount,
            $totalTax,
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
            $balanceDue = $bill['grand_total'] - $newAmountPaid;
            
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
                $paymentData['received_by'] ?? $_SESSION['user_id'],
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
                           CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
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
        
        // Get items grouped by date
        $itemsSql = "SELECT * FROM ipd_billing_items 
                     WHERE bill_id = ? 
                     ORDER BY charge_date DESC, charge_type, item_id";
        $items = $this->db->fetchAll($itemsSql, [$billId]);
        
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
     * Get all IPD bills with filters
     * 
     * @param array $filters Filter criteria
     * @return array List of bills
     */
    public function getAllBills($filters = []) {
        $sql = "SELECT * FROM v_ipd_billing_summary WHERE 1=1";
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
