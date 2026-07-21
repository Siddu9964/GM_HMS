<?php
/**
 * IPD Billing Model
 * 
 * Manages IPD billing with master-detail structure
 * Master: ipd_billing (bill header)
 * Detail: ipd_billing_items (individual charges)
 * 
 * @package IPD_Management\Models
 */

require_once __DIR__ . '/../core/BaseModel.php';

class IpdBilling extends BaseModel {
    protected $table = 'ipd_billing';
    protected $primaryKey = 'bill_id';
    protected $timestamps = true;
    
    /**
     * Generate unique bill ID
     */
    public function generateBillId() {
        $prefix = 'IPDB';
        $date = date('Ymd');
        
        $last = $this->fetchOne(
            "SELECT bill_id FROM ipd_billing WHERE bill_id LIKE ? ORDER BY bill_id DESC LIMIT 1",
            ["{$prefix}-{$date}%"]
        );
        
        if ($last) {
            $lastNum = intval(substr($last['bill_id'], -4));
            $newNum = $lastNum + 1;
        } else {
            $newNum = 1;
        }
        
        return sprintf("%s-%s-%04d", $prefix, $date, $newNum);
    }
    
    /**
     * Get bill with all items
     */
    public function getBillWithItems($billId) {
        $bill = $this->fetchOne(
            "SELECT b.*, 
                    CONCAT(p.first_name, ' ', COALESCE(p.last_name, '')) as patient_name,
                    p.phone as patient_phone,
                    p.age as patient_age,
                    p.sex as patient_gender,
                    d.full_name as doctor_name,
                    a.admission_id,
                    a.bed_id,
                    bed.bed_number,
                    bed.ward_name
             FROM ipd_billing b
             LEFT JOIN patient p ON b.patient_id = p.patient_id
             LEFT JOIN doctors d ON b.doctor_id = d.doctor_id
             LEFT JOIN ipd_admissions a ON b.admission_id = a.admission_id
             LEFT JOIN hospital_beds bed ON a.bed_id = bed.bed_id
             WHERE b.bill_id = ?",
            [$billId]
        );
        
        if ($bill) {
            $bill['items'] = $this->fetchAll(
                "SELECT * FROM ipd_billing_items WHERE bill_id = ? ORDER BY charge_date, item_id",
                [$billId]
            );
        }
        
        return $bill;
    }
    
    /**
     * Get bills by admission
     */
    public function getBillsByAdmission($admissionId) {
        return $this->fetchAll(
            "SELECT b.*, 
                    CONCAT(p.first_name, ' ', COALESCE(p.last_name, '')) as patient_name
             FROM ipd_billing b
             LEFT JOIN patient p ON b.patient_id = p.patient_id
             WHERE b.admission_id = ?
             ORDER BY b.created_at DESC",
            [$admissionId]
        );
    }
    
    /**
     * Create bill with items
     */
    public function createBillWithItems($billData, $items) {
        try {
            $this->beginTransaction();
            
            // Generate bill ID
            if (empty($billData['bill_id'])) {
                $billData['bill_id'] = $this->generateBillId();
            }
            
            // Calculate totals from items
            $totals = $this->calculateTotals($items, $billData);
            $billData = array_merge($billData, $totals);
            
            // Insert master bill
            $this->create($billData);
            
            // Insert items
            foreach ($items as $item) {
                $item['bill_id'] = $billData['bill_id'];
                $this->insertItem($item);
            }
            
            $this->commit();
            return ['success' => true, 'bill_id' => $billData['bill_id']];
            
        } catch (Exception $e) {
            $this->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Calculate bill totals
     */
    private function calculateTotals($items, $billData) {
        $roomCharges = 0;
        $procedureCharges = 0;
        $medicationCharges = 0;
        $investigationCharges = 0;
        $nursingCharges = 0;
        $consumableCharges = 0;
        $otherCharges = 0;
        
        foreach ($items as $item) {
            $itemTotal = $item['total_price'];
            
            switch ($item['charge_type']) {
                case 'Room':
                    $roomCharges += $itemTotal;
                    break;
                case 'Procedure':
                    $procedureCharges += $itemTotal;
                    break;
                case 'Medication':
                    $medicationCharges += $itemTotal;
                    break;
                case 'Investigation':
                    $investigationCharges += $itemTotal;
                    break;
                case 'Nursing':
                    $nursingCharges += $itemTotal;
                    break;
                case 'Consumable':
                    $consumableCharges += $itemTotal;
                    break;
                default:
                    $otherCharges += $itemTotal;
            }
        }
        
        $subtotal = $roomCharges + $procedureCharges + $medicationCharges + 
                   $investigationCharges + $nursingCharges + $consumableCharges + $otherCharges;
        
        $discountAmount = $billData['discount_amount'] ?? 0;
        $discountPercentage = $billData['discount_percentage'] ?? 0;
        
        if ($discountPercentage > 0) {
            $discountAmount = ($subtotal * $discountPercentage) / 100;
        }
        
        $taxableAmount = $subtotal - $discountAmount;
        $taxPercentage = $billData['tax_percentage'] ?? 18;
        $taxAmount = ($taxableAmount * $taxPercentage) / 100;
        $grandTotal = $taxableAmount + $taxAmount;
        
        $amountPaid = $billData['amount_paid'] ?? 0;
        $balanceDue = $grandTotal - $amountPaid;
        
        // Determine payment status
        if ($balanceDue <= 0) {
            $paymentStatus = 'Paid';
        } elseif ($amountPaid > 0) {
            $paymentStatus = 'Partial';
        } else {
            $paymentStatus = 'Pending';
        }
        
        return [
            'room_charges' => $roomCharges,
            'procedure_charges' => $procedureCharges,
            'medication_charges' => $medicationCharges,
            'investigation_charges' => $investigationCharges,
            'nursing_charges' => $nursingCharges,
            'consumable_charges' => $consumableCharges,
            'other_charges' => $otherCharges,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'discount_percentage' => $discountPercentage,
            'taxable_amount' => $taxableAmount,
            'tax_amount' => $taxAmount,
            'tax_percentage' => $taxPercentage,
            'grand_total' => $grandTotal,
            'amount_paid' => $amountPaid,
            'balance_due' => $balanceDue,
            'payment_status' => $paymentStatus
        ];
    }
    
    /**
     * Insert billing item
     */
    private function insertItem($item) {
        // Calculate total price
        $quantity = $item['quantity'] ?? 1;
        $unitPrice = $item['unit_price'];
        $totalPrice = $quantity * $unitPrice;
        
        // Apply discount
        $discountAmount = $item['discount_amount'] ?? 0;
        $totalPrice -= $discountAmount;
        
        $item['total_price'] = $totalPrice;
        $item['created_at'] = date('Y-m-d H:i:s');
        
        return $this->query(
            "INSERT INTO ipd_billing_items (bill_id, charge_date, charge_type, item_code, item_name, 
                    item_description, quantity, unit_price, total_price, is_taxable, tax_percentage, 
                    discount_amount, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $item['bill_id'],
                $item['charge_date'] ?? date('Y-m-d'),
                $item['charge_type'],
                $item['item_code'] ?? null,
                $item['item_name'],
                $item['item_description'] ?? null,
                $quantity,
                $unitPrice,
                $totalPrice,
                $item['is_taxable'] ?? 1,
                $item['tax_percentage'] ?? 18,
                $discountAmount,
                $item['created_by'],
                $item['created_at']
            ]
        );
    }
    
    /**
     * Update payment
     */
    public function updatePayment($billId, $paymentData) {
        try {
            $bill = $this->getById($billId);
            if (!$bill) {
                return ['success' => false, 'error' => 'Bill not found'];
            }
            
            $amountPaid = ($bill['amount_paid'] ?? 0) + ($paymentData['amount'] ?? 0);
            $balanceDue = $bill['grand_total'] - $amountPaid;
            
            if ($balanceDue <= 0) {
                $paymentStatus = 'Paid';
            } elseif ($amountPaid > 0) {
                $paymentStatus = 'Partial';
            } else {
                $paymentStatus = 'Pending';
            }
            
            $this->update($billId, [
                'amount_paid' => $amountPaid,
                'balance_due' => $balanceDue,
                'payment_status' => $paymentStatus
            ]);
            
            return ['success' => true, 'balance_due' => $balanceDue];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get billing summary for admission
     */
    public function getAdmissionBillingSummary($admissionId) {
        $result = $this->fetchOne(
            "SELECT 
                SUM(grand_total) as total_charges,
                SUM(amount_paid) as total_paid,
                SUM(balance_due) as balance_due
             FROM ipd_billing
             WHERE admission_id = ?",
            [$admissionId]
        );
        
        return $result ?: [
            'total_charges' => 0,
            'total_paid' => 0,
            'balance_due' => 0
        ];
    }
}
