<?php
namespace GM_HMS\Models;

/**
 * IpdInsurance Model
 * One insurance record per billing master.
 *
 * @package IPD_Management\Models
 */


class IpdInsurance extends IpdBaseModel {
    protected $table      = 'ipd_insurance';
    protected $primaryKey = 'insurance_id';
    protected $timestamps = false;

    /* ───────────────────────────────────────────────────────────────
     * 1. SAVE OR UPDATE
     * ─────────────────────────────────────────────────────────────── */
    public function saveOrUpdate(string $billId, array $data): array {
        $existing = $this->fetchOne("SELECT * FROM ipd_insurance WHERE bill_id = ?", [$billId]);
        $now      = date('Y-m-d H:i:s');
        $approvedAmt  = (float)($data['approved_amount'] ?? 0);
        $receivedAmt  = (float)($existing['received_amount'] ?? 0);
        $pendingAmt   = max(0, $approvedAmt - $receivedAmt);

        // Get grand_total for patient_payable
        $master = $this->fetchOne("SELECT grand_total FROM ipd_billing_master WHERE bill_id = ?", [$billId]);
        $grandTotal   = (float)($master['grand_total'] ?? 0);
        $patientPayable = max(0, $grandTotal - $approvedAmt);

        $record = [
            'bill_id'              => $billId,
            'admission_id'         => $data['admission_id'],
            'patient_id'           => $data['patient_id'],
            'insurance_type'       => $data['insurance_type']       ?? 'INSURANCE',
            'insurance_company_id' => $data['insurance_company_id'] ?? null,
            'company_name'         => $data['company_name']         ?? null,
            'policy_number'        => $data['policy_number']        ?? null,
            'claim_number'         => $data['claim_number']         ?? null,
            'approval_number'      => $data['approval_number']      ?? null,
            'tpa_name'             => $data['tpa_name']             ?? null,
            'tpa_reference_no'     => $data['tpa_reference_no']     ?? null,
            'approved_amount'      => $approvedAmt,
            'received_amount'      => $receivedAmt,
            'pending_amount'       => $pendingAmt,
            'patient_payable'      => $patientPayable,
            'claim_status'         => $data['claim_status']         ?? 'PENDING',
            'remarks'              => $data['remarks']              ?? null,
            'created_by'           => $data['created_by']           ?? 'system',
        ];

        if ($existing) {
            $record['updated_at'] = $now;
            $this->db->update('ipd_insurance', $record, '`insurance_id` = ?', [$existing['insurance_id']]);
        } else {
            $record['created_at'] = $now;
            $record['updated_at'] = $now;
            $this->db->insert('ipd_insurance', $record);
        }

        // Update master insurance fields
        $this->db->update('ipd_billing_master', [
            'bill_type'                  => $data['insurance_type'] === 'CORPORATE' ? 'CORPORATE' : 'INSURANCE',
            'insurance_company_id'       => $data['insurance_company_id'] ?? null,
            'policy_number'              => $data['policy_number']        ?? null,
            'approval_number'            => $data['approval_number']      ?? null,
            'insurance_approved_amount'  => $approvedAmt,
            'patient_payable'            => $patientPayable,
            'updated_at'                 => $now,
        ], '`bill_id` = ?', [$billId]);

        // Recalculate master
        require_once __DIR__ . '/IpdBillingMaster.php';
        $summary = (new IpdBillingMaster())->recalculateMaster($billId, $data['created_by'] ?? 'system');

        return ['success' => true, 'message' => 'Insurance details saved', 'financial' => $summary];
    }

    /* ───────────────────────────────────────────────────────────────
     * 2. GET BY BILL
     * ─────────────────────────────────────────────────────────────── */
    public function getByBill(string $billId): ?array {
        return $this->fetchOne("SELECT * FROM ipd_insurance WHERE bill_id = ?", [$billId]);
    }

    /* ───────────────────────────────────────────────────────────────
     * 3. UPDATE CLAIM STATUS
     * ─────────────────────────────────────────────────────────────── */
    public function updateClaimStatus(string $billId, string $status, string $updatedBy, ?string $rejectionReason = null): bool {
        $existing = $this->fetchOne("SELECT insurance_id FROM ipd_insurance WHERE bill_id = ?", [$billId]);
        if (!$existing) return false;

        $data = [
            'claim_status' => $status,
            'updated_at'   => date('Y-m-d H:i:s'),
        ];
        if ($rejectionReason) $data['rejection_reason'] = $rejectionReason;
        if ($status === 'APPROVED') $data['approved_date'] = date('Y-m-d');
        if ($status === 'SETTLED')  $data['settled_date']  = date('Y-m-d');

        $this->db->update('ipd_insurance', $data, '`insurance_id` = ?', [$existing['insurance_id']]);
        return true;
    }
}
