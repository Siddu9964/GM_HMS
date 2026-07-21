<?php
/**
 * Payment Model
 * 
 * Manages payments for IPD admissions
 * 
 * @package IPD_Management\Models
 */

require_once __DIR__ . '/../core/BaseModel.php';

class Payment extends BaseModel {
    protected $table = 'payments';
    protected $primaryKey = 'payment_id';
    
    /**
     * Get payments by admission
     */
    public function getByAdmission($admissionSlNo) {
        $query = "SELECT * FROM payments WHERE admission_sl_no = ? ORDER BY payment_date DESC";
        return $this->fetchAll($query, [$admissionSlNo]);
    }
    
    /**
     * Get total paid for admission
     */
    public function getTotalPaid($admissionSlNo) {
        $result = $this->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE admission_sl_no = ? AND status = 'Completed'",
            [$admissionSlNo]
        );
        return (float)$result['total'];
    }
    
    /**
     * Create payment with receipt number generation
     */
    public function createPayment($data) {
        // Generate payment_id if not provided
        if (empty($data['payment_id'])) {
            $data['payment_id'] = $this->generatePaymentId();
        }
        
        // Validate required fields (payment_id now auto-generated)
        $required = ['patient_id', 'admission_sl_no', 'payment_date', 'amount', 'payment_mode'];
        $errors = $this->validateRequired($data, $required);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Generate receipt number if not provided
        if (empty($data['receipt_number'])) {
            $data['receipt_number'] = $this->generateReceiptNumber();
        }
        
        // Set default status
        if (empty($data['status'])) {
            $data['status'] = 'Completed';
        }
        
        $paymentId = $this->create($data);
        
        return ['success' => true, 'payment_id' => $data['payment_id'], 'receipt_number' => $data['receipt_number']];
    }
    
    /**
     * Generate unique payment ID
     * Format: PAY-YYYYMMDD-XXXX
     */
    private function generatePaymentId() {
        $prefix = 'PAY';
        $date = date('Ymd');
        
        // Get last payment ID for today
        $lastPayment = $this->fetchOne(
            "SELECT payment_id FROM payments WHERE payment_id LIKE ? ORDER BY created_at DESC LIMIT 1",
            ["{$prefix}-{$date}-%"]
        );
        
        if ($lastPayment) {
            // Extract sequence number and increment
            $lastNumber = (int)substr($lastPayment['payment_id'], -4);
            $sequence = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $sequence = '0001';
        }
        
        return "{$prefix}-{$date}-{$sequence}";
    }
    
    /**
     * Generate unique receipt number
     */
    private function generateReceiptNumber() {
        $prefix = 'RCP';
        $date = date('Ymd');
        
        // Get last receipt number for today
        $lastReceipt = $this->fetchOne(
            "SELECT receipt_number FROM payments WHERE receipt_number LIKE ? ORDER BY created_at DESC LIMIT 1",
            ["{$prefix}{$date}%"]
        );
        
        if ($lastReceipt) {
            // Extract sequence number and increment
            $lastNumber = (int)substr($lastReceipt['receipt_number'], -4);
            $sequence = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $sequence = '0001';
        }
        
        return "{$prefix}{$date}{$sequence}";
    }
    
    /**
     * Get payment statistics
     */
    public function getPaymentStats($dateRange = 'today') {
        $dateCondition = '';
        $params = [];
        
        switch ($dateRange) {
            case 'today':
                $dateCondition = "DATE(payment_date) = CURDATE()";
                break;
            case 'week':
                $dateCondition = "payment_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $dateCondition = "payment_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
        }
        
        $query = "SELECT 
            COUNT(*) as total_payments,
            COALESCE(SUM(amount), 0) as total_amount,
            COALESCE(AVG(amount), 0) as average_amount,
            SUM(CASE WHEN payment_mode = 'Cash' THEN amount ELSE 0 END) as cash_payments,
            SUM(CASE WHEN payment_mode = 'Card' THEN amount ELSE 0 END) as card_payments,
            SUM(CASE WHEN payment_mode = 'UPI' THEN amount ELSE 0 END) as upi_payments
        FROM payments
        WHERE status = 'Completed'";
        
        if ($dateCondition) {
            $query .= " AND {$dateCondition}";
        }
        
        return $this->fetchOne($query, $params);
    }
}
