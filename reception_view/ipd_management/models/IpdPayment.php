<?php
/**
 * IpdPayment Model
 * Always INSERT — never UPDATE existing payment rows.
 *
 * @package IPD_Management\Models
 */
require_once __DIR__ . '/../core/BaseModel.php';

class IpdPayment extends BaseModel {
    protected $table      = 'ipd_payment';
    protected $primaryKey = 'payment_id';
    protected $timestamps = false;

    /* ───────────────────────────────────────────────────────────────
     * 1. RECORD PATIENT PAYMENT  (always INSERT)
     * ─────────────────────────────────────────────────────────────── */
    public function recordPayment(array $data): array {
        $required = ['bill_id', 'admission_id', 'patient_id', 'payment_type', 'payment_mode', 'amount'];
        foreach ($required as $f) {
            if (empty($data[$f])) {
                return ['success' => false, 'message' => "Field '$f' is required"];
            }
        }

        $amount = (float)$data['amount'];
        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Amount must be greater than zero'];
        }

        // Duplicate submission prevention within 5 seconds
        $recentDuplicate = $this->fetchOne(
            "SELECT payment_id FROM ipd_payment 
             WHERE bill_id = ? AND amount = ? AND payment_type = ? AND payment_mode = ? 
               AND created_at >= (NOW() - INTERVAL 5 SECOND) 
             LIMIT 1",
            [$data['bill_id'], $amount, strtoupper($data['payment_type']), strtoupper($data['payment_mode'])]
        );
        if ($recentDuplicate) {
            require_once __DIR__ . '/IpdBillingMaster.php';
            $summary = (new IpdBillingMaster())->recalculateMaster($data['bill_id'], $data['created_by'] ?? 'system');
            return ['success' => true, 'message' => 'Payment already recorded (duplicate ignored)', 'financial' => $summary];
        }

        $now = date('Y-m-d H:i:s');
        $isIns = (strtoupper($data['payment_mode']) === 'INSURANCE') ? 1 : 0;
        $this->db->insert('ipd_payment', [
            'bill_id'       => $data['bill_id'],
            'admission_id'  => $data['admission_id'],
            'patient_id'    => $data['patient_id'],
            'payment_date'  => $data['payment_date'] ?? $now,
            'payment_type'  => strtoupper($data['payment_type']),
            'payment_mode'  => strtoupper($data['payment_mode']),
            'amount'        => $amount,
            'reference_no'  => $data['reference_no']  ?? null,
            'is_insurance'  => $isIns,
            'verified_status'      => ($data['payment_mode'] === 'CASH' ? 'VERIFIED' : 'PENDING'),
            'refund_reason'        => null,
            'original_payment_id'  => null,
            'approved_by'          => null,
            'remarks'       => $data['remarks']   ?? null,
            'created_by'    => $data['created_by'] ?? 'system',
            'created_at'    => $now,
        ]);

        if ($isIns) {
            $insRow = $this->fetchOne("SELECT insurance_id, received_amount, approved_amount FROM ipd_insurance WHERE bill_id = ?", [$data['bill_id']]);
            if ($insRow) {
                $newReceived = (float)$insRow['received_amount'] + $amount;
                $pending     = max(0, (float)$insRow['approved_amount'] - $newReceived);
                $this->db->update('ipd_insurance',
                    ['received_amount' => $newReceived, 'pending_amount' => $pending, 'updated_at' => $now],
                    '`insurance_id` = ?', [$insRow['insurance_id']]
                );
            }
        }

        // Recalculate master
        require_once __DIR__ . '/IpdBillingMaster.php';
        $summary = (new IpdBillingMaster())->recalculateMaster($data['bill_id'], $data['created_by'] ?? 'system');

        return ['success' => true, 'message' => 'Payment recorded', 'financial' => $summary];
    }

    /* ───────────────────────────────────────────────────────────────
     * 2. RECORD INSURANCE RECEIPT  (is_insurance = 1)
     * ─────────────────────────────────────────────────────────────── */
    public function recordInsuranceReceipt(array $data): array {
        $required = ['bill_id', 'admission_id', 'patient_id', 'amount'];
        foreach ($required as $f) {
            if (empty($data[$f])) {
                return ['success' => false, 'message' => "Field '$f' is required"];
            }
        }

        $now = date('Y-m-d H:i:s');
        $this->db->insert('ipd_payment', [
            'bill_id'      => $data['bill_id'],
            'admission_id' => $data['admission_id'],
            'patient_id'   => $data['patient_id'],
            'payment_date' => $data['payment_date'] ?? $now,
            'payment_type' => 'FINAL',
            'payment_mode' => 'INSURANCE',
            'amount'       => (float)$data['amount'],
            'reference_no' => $data['reference_no'] ?? null,
            'is_insurance' => 1,
            'verified_status' => 'VERIFIED',
            'remarks'      => $data['remarks'] ?? null,
            'created_by'   => $data['created_by'] ?? 'system',
            'created_at'   => $now,
        ]);

        // Update ipd_insurance received amount
        $insRow = $this->fetchOne("SELECT insurance_id, received_amount, approved_amount FROM ipd_insurance WHERE bill_id = ?", [$data['bill_id']]);
        if ($insRow) {
            $newReceived = (float)$insRow['received_amount'] + (float)$data['amount'];
            $pending     = max(0, (float)$insRow['approved_amount'] - $newReceived);
            $this->db->update('ipd_insurance',
                ['received_amount' => $newReceived, 'pending_amount' => $pending, 'updated_at' => $now],
                '`insurance_id` = ?', [$insRow['insurance_id']]
            );
        }

        require_once __DIR__ . '/IpdBillingMaster.php';
        $summary = (new IpdBillingMaster())->recalculateMaster($data['bill_id'], $data['created_by'] ?? 'system');

        return ['success' => true, 'message' => 'Insurance receipt recorded', 'financial' => $summary];
    }

    /* ───────────────────────────────────────────────────────────────
     * 3. RECORD REFUND
     * ─────────────────────────────────────────────────────────────── */
    public function recordRefund(array $data): array {
        if (empty($data['refund_reason'])) {
            return ['success' => false, 'message' => 'Refund reason is required'];
        }
        if (empty($data['approved_by'])) {
            return ['success' => false, 'message' => 'Refund approval authorization is required'];
        }

        $now = date('Y-m-d H:i:s');
        $this->db->insert('ipd_payment', [
            'bill_id'             => $data['bill_id'],
            'admission_id'        => $data['admission_id'],
            'patient_id'          => $data['patient_id'],
            'payment_date'        => $data['payment_date'] ?? $now,
            'payment_type'        => 'REFUND',
            'payment_mode'        => strtoupper($data['payment_mode'] ?? 'CASH'),
            'amount'              => abs((float)$data['amount']),
            'reference_no'        => $data['reference_no'] ?? null,
            'is_insurance'        => 0,
            'verified_status'     => 'VERIFIED',
            'refund_reason'       => $data['refund_reason'],
            'original_payment_id' => $data['original_payment_id'] ?? null,
            'approved_by'         => $data['approved_by'],
            'remarks'             => $data['remarks'] ?? null,
            'created_by'          => $data['created_by'] ?? 'system',
            'created_at'          => $now,
        ]);

        require_once __DIR__ . '/IpdBillingMaster.php';
        $summary = (new IpdBillingMaster())->recalculateMaster($data['bill_id'], $data['created_by'] ?? 'system');

        return ['success' => true, 'message' => 'Refund recorded', 'financial' => $summary];
    }

    /* ───────────────────────────────────────────────────────────────
     * 4. GET PAYMENT HISTORY
     * ─────────────────────────────────────────────────────────────── */
    public function getByBill(string $billId): array {
        return $this->fetchAll(
            "SELECT * FROM ipd_payment WHERE bill_id = ? ORDER BY payment_date ASC, created_at ASC",
            [$billId]
        );
    }

    /* ───────────────────────────────────────────────────────────────
     * 5. GET PAYMENT SUMMARY
     * ─────────────────────────────────────────────────────────────── */
    public function getSummary(string $billId): array {
        $row = $this->fetchOne(
            "SELECT
                COALESCE(SUM(CASE WHEN is_insurance=0 AND payment_type!='REFUND' THEN amount ELSE 0 END),0) AS total_paid,
                COALESCE(SUM(CASE WHEN payment_type='REFUND' THEN amount ELSE 0 END),0)                     AS total_refunded,
                COALESCE(SUM(CASE WHEN is_insurance=1 THEN amount ELSE 0 END),0)                            AS insurance_received,
                COALESCE(SUM(CASE WHEN payment_type='ADVANCE' AND is_insurance=0 THEN amount ELSE 0 END),0) AS advance_paid,
                COUNT(*) AS transaction_count
             FROM ipd_payment WHERE bill_id = ?",
            [$billId]
        );
        return $row ?? [];
    }
    /* ───────────────────────────────────────────────────────────────
     * 6. GET PAYMENT STATS (DASHBOARD)
     * ─────────────────────────────────────────────────────────────── */
    public function getPaymentStats(string $dateRange = 'today'): array {
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
            SUM(CASE WHEN payment_mode = 'CASH' THEN amount ELSE 0 END) as cash_payments,
            SUM(CASE WHEN payment_mode = 'CARD' THEN amount ELSE 0 END) as card_payments,
            SUM(CASE WHEN payment_mode = 'UPI' THEN amount ELSE 0 END) as upi_payments
        FROM ipd_payment
        WHERE payment_type != 'REFUND'";
        
        if ($dateCondition) {
            $query .= " AND {$dateCondition}";
        }
        
        return $this->fetchOne($query, $params) ?: [];
    }
}
