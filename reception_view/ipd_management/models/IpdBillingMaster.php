<?php
/**
 * IpdBillingMaster Model
 * ONE record per admission — the single source of financial truth.
 *
 * @package IPD_Management\Models
 */
require_once __DIR__ . '/../core/BaseModel.php';

class IpdBillingMaster extends BaseModel {
    protected $table      = 'ipd_billing_master';
    protected $primaryKey = 'bill_id';
    protected $timestamps = false; // managed manually

    /* ───────────────────────────────────────────────────────────────
     * 1. GET OR CREATE  (ONE master per admission rule)
     * ─────────────────────────────────────────────────────────────── */

    public function getOrCreateForAdmission(array $admissionData): array {
        // Fetch the authoritative admission record first
        $adm = $this->fetchOne(
            "SELECT ia.*, 
                    hb.ward_name, hb.room_name, hb.bed_number, hb.room_type,
                    hb.amount_per_day, hb.nursig_charge, hb.doctor_charge, hb.total_bed_amount
             FROM ipd_admissions ia
             LEFT JOIN hospital_beds hb ON ia.bed_id = hb.sl_no
             WHERE ia.admission_id = ?",
            [$admissionData['admission_id']]
        );

        // Check existing master
        $existing = $this->fetchOne(
            "SELECT bm.*, 
                    bm.patient_id AS bm_patient_id,
                    bm.doctor_id AS bm_doctor_id,
                    bm.admission_date AS bm_admission_date,
                    COALESCE(ia.patient_id, bm.patient_id) AS patient_id,
                    TRIM(CONCAT(COALESCE(p.first_name, ''), ' ', COALESCE(p.last_name, ''))) AS patient_name,
                    p.age, p.sex, p.phone,
                    COALESCE(d.full_name, '') AS doctor_name,
                    hb.ward_name, hb.room_name, hb.bed_number, hb.room_type,
                    hb.amount_per_day, hb.nursig_charge, hb.doctor_charge, hb.total_bed_amount,
                    ia.sponsor, ia.total_bed_amount AS adm_total_bed_amount,
                    COALESCE(ia.admission_date, bm.admission_date) AS admission_date,
                    COALESCE(ia.discharge_date, bm.discharge_date) AS discharge_date
             FROM ipd_billing_master bm
             LEFT JOIN ipd_admissions ia ON bm.admission_id = ia.admission_id
             LEFT JOIN patient p ON COALESCE(ia.patient_id, bm.patient_id) = p.patient_id
             LEFT JOIN doctors d ON COALESCE(ia.admitting_doctor_id, bm.doctor_id) = d.doctor_id
             LEFT JOIN hospital_beds hb ON ia.bed_id = hb.sl_no
             WHERE bm.admission_id = ?",
            [$admissionData['admission_id']]
        );

        if ($existing) {
            // Auto-heal / synchronize if patient_id, doctor_id, or admission_date was mismatched
            if ($adm) {
                $needsUpdate = false;
                $updateFields = [];
                if (!empty($adm['patient_id']) && ($existing['bm_patient_id'] ?? '') !== $adm['patient_id']) {
                    $updateFields['patient_id'] = $adm['patient_id'];
                    $needsUpdate = true;
                }
                if (!empty($adm['admitting_doctor_id']) && ($existing['bm_doctor_id'] ?? '') !== $adm['admitting_doctor_id']) {
                    $updateFields['doctor_id'] = $adm['admitting_doctor_id'];
                    $needsUpdate = true;
                }
                if (!empty($adm['admission_date']) && ($existing['bm_admission_date'] ?? '') !== $adm['admission_date']) {
                    $updateFields['admission_date'] = $adm['admission_date'];
                    $needsUpdate = true;
                }
                if ($needsUpdate) {
                    $updateFields['updated_at'] = date('Y-m-d H:i:s');
                    $this->db->update('ipd_billing_master', $updateFields, "`bill_id` = ?", [$existing['bill_id']]);
                    if (!empty($adm['patient_id'])) {
                        $this->db->update('ipd_billing_items', ['patient_id' => $adm['patient_id']], "`bill_id` = ?", [$existing['bill_id']]);
                    }
                    // Re-fetch with fresh data
                    $existing = $this->fetchOne(
                        "SELECT bm.*, 
                                COALESCE(ia.patient_id, bm.patient_id) AS patient_id,
                                TRIM(CONCAT(COALESCE(p.first_name, ''), ' ', COALESCE(p.last_name, ''))) AS patient_name,
                                p.age, p.sex, p.phone,
                                COALESCE(d.full_name, '') AS doctor_name,
                                hb.ward_name, hb.room_name, hb.bed_number, hb.room_type,
                                hb.amount_per_day, hb.nursig_charge, hb.doctor_charge, hb.total_bed_amount,
                                ia.sponsor, ia.total_bed_amount AS adm_total_bed_amount,
                                COALESCE(ia.admission_date, bm.admission_date) AS admission_date,
                                COALESCE(ia.discharge_date, bm.discharge_date) AS discharge_date
                         FROM ipd_billing_master bm
                         LEFT JOIN ipd_admissions ia ON bm.admission_id = ia.admission_id
                         LEFT JOIN patient p ON COALESCE(ia.patient_id, bm.patient_id) = p.patient_id
                         LEFT JOIN doctors d ON COALESCE(ia.admitting_doctor_id, bm.doctor_id) = d.doctor_id
                         LEFT JOIN hospital_beds hb ON ia.bed_id = hb.sl_no
                         WHERE bm.admission_id = ?",
                        [$admissionData['admission_id']]
                    );
                }
            }
            return ['created' => false, 'data' => $existing];
        }

        // Create new master
        $billId    = $this->generateBillId();
        $now       = date('Y-m-d H:i:s');
        $patientId = $adm['patient_id'] ?? $admissionData['patient_id'];
        $doctorId  = $adm['admitting_doctor_id'] ?? $admissionData['doctor_id'] ?? null;
        $admDate   = $adm['admission_date'] ?? $admissionData['admission_date'] ?? date('Y-m-d');
        $disDate   = $adm['discharge_date'] ?? null;

        $this->db->insert('ipd_billing_master', [
            'bill_id'          => $billId,
            'admission_id'     => $admissionData['admission_id'],
            'patient_id'       => $patientId,
            'doctor_id'        => $doctorId,
            'admission_date'   => $admDate,
            'discharge_date'   => $disDate,
            'total_days'       => 0,
            'bill_type'        => $admissionData['bill_type']        ?? 'SELF',
            'room_charges'     => 0,
            'doctor_charges'   => 0,
            'lab_charges'      => 0,
            'radiology_charges'=> 0,
            'pharmacy_charges' => 0,
            'ot_charges'       => 0,
            'procedure_charges'=> 0,
            'consumable_charges'=> 0,
            'other_charges'    => 0,
            'subtotal'         => 0,
            'discount_amount'  => 0,
            'discount_percentage' => 0,
            'grand_total'      => 0,
            'insurance_approved_amount' => 0,
            'insurance_received_amount' => 0,
            'patient_payable'  => 0,
            'advance_amount'   => 0,
            'amount_paid'      => 0,
            'balance_due'      => 0,
            'payment_status'   => 'Pending',
            'billing_status'   => 'OPEN',
            'amendment_count'  => 0,
            'created_by'       => $admissionData['created_by'] ?? 'system',
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        $newRecord = $this->fetchOne(
            "SELECT bm.*,
                    COALESCE(ia.patient_id, bm.patient_id) AS patient_id,
                    TRIM(CONCAT(COALESCE(p.first_name, ''), ' ', COALESCE(p.last_name, ''))) AS patient_name,
                    p.age, p.sex, p.phone,
                    COALESCE(d.full_name, '') AS doctor_name,
                    hb.ward_name, hb.room_name, hb.bed_number, hb.room_type,
                    hb.amount_per_day, hb.nursig_charge, hb.doctor_charge, hb.total_bed_amount,
                    ia.sponsor, ia.total_bed_amount AS adm_total_bed_amount,
                    COALESCE(ia.admission_date, bm.admission_date) AS admission_date,
                    COALESCE(ia.discharge_date, bm.discharge_date) AS discharge_date
             FROM ipd_billing_master bm
             LEFT JOIN ipd_admissions ia ON bm.admission_id = ia.admission_id
             LEFT JOIN patient p ON COALESCE(ia.patient_id, bm.patient_id) = p.patient_id
             LEFT JOIN doctors d ON COALESCE(ia.admitting_doctor_id, bm.doctor_id) = d.doctor_id
             LEFT JOIN hospital_beds hb ON ia.bed_id = hb.sl_no
             WHERE bm.bill_id = ?",
            [$billId]
        );

        return ['created' => true, 'data' => $newRecord];
    }

    /* ───────────────────────────────────────────────────────────────
     * 2. GET FULL DETAILS  (with all joins)
     * ─────────────────────────────────────────────────────────────── */
    public function getFullDetails(string $billId): ?array {
        return $this->fetchOne(
            "SELECT bm.*,
                    COALESCE(ia.patient_id, bm.patient_id) AS patient_id,
                    TRIM(CONCAT(COALESCE(p.first_name, ''), ' ', COALESCE(p.last_name, ''))) AS patient_name,
                    p.age, p.sex, p.phone, p.address,
                    COALESCE(d.full_name, '') AS doctor_name, d.specialization,
                    hb.ward_name, hb.room_name, hb.bed_number, hb.room_type,
                    hb.amount_per_day, hb.nursig_charge, hb.doctor_charge, hb.total_bed_amount,
                    ia.admission_id AS adm_id, ia.bed_id, ia.sponsor, ia.total_bed_amount AS adm_total_bed_amount,
                    COALESCE(ia.admission_date, bm.admission_date) AS admission_date,
                    COALESCE(ia.discharge_date, bm.discharge_date) AS discharge_date
             FROM ipd_billing_master bm
             LEFT JOIN ipd_admissions ia ON bm.admission_id = ia.admission_id
             LEFT JOIN patient p ON COALESCE(ia.patient_id, bm.patient_id) = p.patient_id
             LEFT JOIN doctors d ON COALESCE(ia.admitting_doctor_id, bm.doctor_id) = d.doctor_id
             LEFT JOIN hospital_beds hb ON ia.bed_id = hb.sl_no
             WHERE bm.bill_id = ?",
            [$billId]
        );
    }

    public function getByAdmission(string $admissionId): ?array {
        return $this->fetchOne(
            "SELECT bm.*,
                    COALESCE(ia.patient_id, bm.patient_id) AS patient_id,
                    TRIM(CONCAT(COALESCE(p.first_name, ''), ' ', COALESCE(p.last_name, ''))) AS patient_name,
                    p.age, p.sex, p.phone,
                    COALESCE(d.full_name, '') AS doctor_name,
                    hb.ward_name, hb.room_name, hb.bed_number, hb.room_type,
                    hb.amount_per_day, hb.nursig_charge, hb.doctor_charge, hb.total_bed_amount,
                    ia.referral_type, ia.referral_name, ia.sponsor, ia.total_bed_amount AS adm_total_bed_amount,
                    ins.company_name AS insurance_company_name, ins.tpa_name,
                    COALESCE(ia.admission_date, bm.admission_date) AS admission_date,
                    COALESCE(ia.discharge_date, bm.discharge_date) AS discharge_date
             FROM ipd_billing_master bm
             LEFT JOIN ipd_admissions ia ON bm.admission_id = ia.admission_id
             LEFT JOIN patient p ON COALESCE(ia.patient_id, bm.patient_id) = p.patient_id
             LEFT JOIN doctors d ON COALESCE(ia.admitting_doctor_id, bm.doctor_id) = d.doctor_id
             LEFT JOIN hospital_beds hb ON ia.bed_id = hb.sl_no
             LEFT JOIN ipd_insurance ins ON bm.bill_id = ins.bill_id
             WHERE bm.admission_id = ?",
            [$admissionId]
        );
    }

    /* ───────────────────────────────────────────────────────────────
     * 3. RECALCULATE MASTER  (the heart of the system)
     * ─────────────────────────────────────────────────────────────── */
    public function recalculateMaster(string $billId, string $updatedBy = 'system'): array {
        // Step 1: Sum billing items by charge_type
        $itemSums = $this->fetchAll(
            "SELECT charge_type, COALESCE(SUM(total_amount),0) AS cat_total
             FROM ipd_billing_items
             WHERE bill_id = ? AND status != 'CANCELLED'
             GROUP BY charge_type",
            [$billId]
        );

        $charges = [
            'room_charges'      => 0,
            'doctor_charges'    => 0,
            'lab_charges'       => 0,
            'radiology_charges' => 0,
            'pharmacy_charges'  => 0,
            'ot_charges'        => 0,
            'procedure_charges' => 0,
            'consumable_charges'=> 0,
            'other_charges'     => 0,
        ];

        $typeMap = [
            'ROOM_RENT'    => 'room_charges',
            'DOCTOR_VISIT' => 'doctor_charges',
            'LAB'          => 'lab_charges',
            'RADIOLOGY'    => 'radiology_charges',
            'PHARMACY'     => 'pharmacy_charges',
            'OT'           => 'ot_charges',
            'PROCEDURE'    => 'procedure_charges',
            'CONSUMABLE'   => 'consumable_charges',
            'OTHER'        => 'other_charges',
        ];

        foreach ($itemSums as $row) {
            $key = $typeMap[$row['charge_type']] ?? 'other_charges';
            $charges[$key] = (float)$row['cat_total'];
        }

        // Step 2: Subtotal
        $subtotal = array_sum($charges);

        // Step 3: Get current discount and payments from master
        $master = $this->fetchOne(
            "SELECT discount_amount, discount_percentage, insurance_approved_amount, amount_paid, advance_amount, insurance_received_amount FROM ipd_billing_master WHERE bill_id = ?",
            [$billId]
        );
        $discountAmt = (float)($master['discount_amount'] ?? 0);
        $discountPct = (float)($master['discount_percentage'] ?? 0);
        if ($discountPct > 0 && $discountAmt == 0) {
            $discountAmt = round($subtotal * $discountPct / 100, 2);
        }
        $grandTotal = max(0, $subtotal - $discountAmt);

        // Step 4: Payment totals (Read directly from master, ignoring ipd_payment table per user request)
        $amountPaid   = (float)($master['amount_paid'] ?? 0);
        $insReceived  = (float)($master['insurance_received_amount'] ?? 0);
        $advanceAmt   = (float)($master['advance_amount'] ?? 0);

        // Step 5: Insurance
        $insApproved = (float)($master['insurance_approved_amount'] ?? 0);
        
        // Re-fetch from ipd_insurance if table exists (handled gracefully by skipping if error)
        try {
            $insRow = $this->fetchOne("SELECT approved_amount FROM ipd_insurance WHERE bill_id = ?", [$billId]);
            if ($insRow) {
                $insApproved = (float)$insRow['approved_amount'];
            }
        } catch (\Exception $e) {}
        
        $patientPayable = max(0, $grandTotal - $insApproved);

        // Step 6: Balance
        $balanceDue = max(0, $grandTotal - $amountPaid - $insReceived);

        // Step 7: Payment status
        if ($amountPaid == 0 && $insReceived == 0) {
            $paymentStatus = 'Pending';
        } elseif ($balanceDue <= 0) {
            $paymentStatus = 'Paid';
        } else {
            $paymentStatus = 'Partial';
        }

        // Step 8: Total days
        $masterFull = $this->fetchOne("SELECT admission_date, discharge_date FROM ipd_billing_master WHERE bill_id = ?", [$billId]);
        $admDate    = $masterFull['admission_date'] ?? date('Y-m-d');
        $disDate    = $masterFull['discharge_date'] ?? null;
        $totalDays  = $disDate
            ? (int)floor((strtotime($disDate) - strtotime($admDate)) / 86400)
            : (int)floor((time() - strtotime($admDate)) / 86400);
        $totalDays  = max(0, $totalDays);

        // Step 9: UPDATE
        $updateData = array_merge($charges, [
            'subtotal'                   => round($subtotal, 2),
            'discount_amount'            => round($discountAmt, 2),
            'discount_percentage'        => round($discountPct, 2),
            'grand_total'                => round($grandTotal, 2),
            'insurance_approved_amount'  => round($insApproved, 2),
            'insurance_received_amount'  => round($insReceived, 2),
            'patient_payable'            => round($patientPayable, 2),
            'advance_amount'             => round($advanceAmt, 2),
            'amount_paid'                => round($amountPaid, 2),
            'balance_due'                => round($balanceDue, 2),
            'payment_status'             => $paymentStatus,
            'total_days'                 => $totalDays,
            'updated_by'                 => $updatedBy,
            'updated_at'                 => date('Y-m-d H:i:s')
        ]);

        $this->db->update('ipd_billing_master', $updateData, "`bill_id` = ?", [$billId]);

        return array_merge($updateData, ['bill_id' => $billId]);
    }


    /* ───────────────────────────────────────────────────────────────
     * 4. APPLY DISCOUNT
     * ─────────────────────────────────────────────────────────────── */
    public function applyDiscount(string $billId, float $discountAmt, float $discountPct, string $reason, string $updatedBy): array {
        $this->db->update('ipd_billing_master', [
            'discount_amount'     => $discountAmt,
            'discount_percentage' => $discountPct,
            'notes'               => $reason,
            'updated_at'          => date('Y-m-d H:i:s'),
        ], "`bill_id` = ?", [$billId]);

        return $this->recalculateMaster($billId, $updatedBy);
    }

    /* ───────────────────────────────────────────────────────────────
     * 5. UPDATE BILLING STATUS
     * ─────────────────────────────────────────────────────────────── */
    public function updateBillingStatus(string $billId, string $status, string $updatedBy, ?string $dischargeDate = null): bool {
        $data = [
            'billing_status' => $status,
            'updated_by'     => $updatedBy,
            'updated_at'     => date('Y-m-d H:i:s'),
        ];
        if ($status === 'FINALIZED' && $dischargeDate) {
            $data['discharge_date'] = $dischargeDate;
        }
        $rows = $this->db->update('ipd_billing_master', $data, "`bill_id` = ?", [$billId]);
        $this->recalculateMaster($billId, $updatedBy);
        return $rows >= 0;
    }

    /* ───────────────────────────────────────────────────────────────
     * 6. UPDATE BILL TYPE (SELF / INSURANCE / CORPORATE)
     * ─────────────────────────────────────────────────────────────── */
    public function updateBillType(string $billId, string $billType, array $insuranceData, string $updatedBy): bool {
        $data = [
            'bill_type'   => $billType,
            'updated_by'  => $updatedBy,
            'updated_at'  => date('Y-m-d H:i:s'),
        ];
        if ($billType !== 'SELF') {
            $data['insurance_company_id'] = $insuranceData['insurance_company_id'] ?? null;
            $data['policy_number']        = $insuranceData['policy_number']        ?? null;
            $data['approval_number']      = $insuranceData['approval_number']      ?? null;
            $data['insurance_approved_amount'] = $insuranceData['approved_amount'] ?? 0;
        }
        $this->db->update('ipd_billing_master', $data, "`bill_id` = ?", [$billId]);
        $this->recalculateMaster($billId, $updatedBy);
        return true;
    }

    /* ───────────────────────────────────────────────────────────────
     * 7. SEARCH ACTIVE ADMISSIONS (for Select2 dropdown)
     * ─────────────────────────────────────────────────────────────── */
    public function searchActiveAdmissions(string $q): array {
        $like = "%{$q}%";
        return $this->fetchAll(
            "SELECT ia.admission_id, ia.patient_id, ia.status, ia.discharge_date,
                    TRIM(CONCAT(COALESCE(p.first_name, ''), ' ', COALESCE(p.last_name, ''))) AS patient_name,
                    p.phone, p.age, p.sex,
                    d.full_name AS doctor_name,
                    hb.ward_name, hb.room_name, hb.bed_number,
                    ia.admission_date,
                    bm.bill_id, bm.payment_status, bm.balance_due, bm.billing_status
             FROM ipd_admissions ia
             JOIN patient p ON ia.patient_id = p.patient_id
             LEFT JOIN doctors d ON ia.admitting_doctor_id = d.doctor_id
             LEFT JOIN hospital_beds hb ON ia.bed_id = hb.sl_no
             LEFT JOIN ipd_billing_master bm ON ia.admission_id = bm.admission_id
             WHERE (p.first_name LIKE ? OR p.last_name LIKE ? OR ia.admission_id LIKE ? OR p.phone LIKE ? OR ia.patient_id LIKE ?)
             ORDER BY ia.admission_date DESC
             LIMIT 500",
            [$like, $like, $like, $like, $like]
        );
    }

    /* ───────────────────────────────────────────────────────────────
     * 8. ALL BILLS (for billing list page)
     * ─────────────────────────────────────────────────────────────── */
    public function getAllBills(array $filters = [], int $limit = 50, int $offset = 0): array {
        $where  = "WHERE 1=1";
        $params = [];

        if (!empty($filters['payment_status'])) {
            $where   .= " AND bm.payment_status = ?";
            $params[] = $filters['payment_status'];
        }
        if (!empty($filters['billing_status'])) {
            $where   .= " AND bm.billing_status = ?";
            $params[] = $filters['billing_status'];
        }
        if (!empty($filters['search'])) {
            $like     = "%{$filters['search']}%";
            $where   .= " AND (bm.bill_id LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ? OR bm.admission_id LIKE ?)";
            $params   = array_merge($params, [$like, $like, $like, $like]);
        }
        if (!empty($filters['date_from'])) {
            $where   .= " AND DATE(bm.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where   .= " AND DATE(bm.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        $countRow = $this->fetchOne(
            "SELECT COUNT(*) AS total FROM ipd_billing_master bm
             LEFT JOIN ipd_admissions ia ON bm.admission_id = ia.admission_id
             LEFT JOIN patient p ON COALESCE(ia.patient_id, bm.patient_id) = p.patient_id
             $where",
            $params
        );

        $rows = $this->fetchAll(
            "SELECT bm.*,
                    COALESCE(ia.patient_id, bm.patient_id) AS patient_id,
                    TRIM(CONCAT(COALESCE(p.first_name, ''), ' ', COALESCE(p.last_name, ''))) AS patient_name,
                    p.phone, p.age, p.sex,
                    COALESCE(d.full_name, '') AS doctor_name,
                    hb.ward_name, hb.room_name, hb.bed_number
             FROM ipd_billing_master bm
             LEFT JOIN ipd_admissions ia ON bm.admission_id = ia.admission_id
             LEFT JOIN patient p ON COALESCE(ia.patient_id, bm.patient_id) = p.patient_id
             LEFT JOIN doctors d ON COALESCE(ia.admitting_doctor_id, bm.doctor_id) = d.doctor_id
             LEFT JOIN hospital_beds hb ON ia.bed_id = hb.sl_no
             $where
             ORDER BY bm.created_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [(int)$limit, (int)$offset])
        );

        return [
            'total' => (int)($countRow['total'] ?? 0),
            'rows'  => $rows,
        ];
    }

    /* ───────────────────────────────────────────────────────────────
     * 9. DASHBOARD STATS
     * ─────────────────────────────────────────────────────────────── */
    public function getDashboardStats(): array {
        return $this->fetchOne(
            "SELECT
                COUNT(*) AS total_bills,
                COALESCE(SUM(grand_total),0) AS total_revenue,
                COALESCE(SUM(amount_paid),0) AS total_collected,
                COALESCE(SUM(balance_due),0)  AS total_pending,
                SUM(CASE WHEN payment_status='Paid'    THEN 1 ELSE 0 END) AS paid_count,
                SUM(CASE WHEN payment_status='Partial' THEN 1 ELSE 0 END) AS partial_count,
                SUM(CASE WHEN payment_status='Pending' THEN 1 ELSE 0 END) AS pending_count
             FROM ipd_billing_master
             WHERE billing_status != 'CANCELLED'"
        ) ?? [];
    }

    /* ───────────────────────────────────────────────────────────────
     * 10. ADD PAYMENT
     * ─────────────────────────────────────────────────────────────── */
    public function addPayment(string $billId, float $amount, string $user, string $mode = 'CASH', string $type = 'PARTIAL', string $ref = ''): array {
        require_once __DIR__ . '/IpdPayment.php';
        $master = $this->fetchOne("SELECT admission_id, patient_id FROM ipd_billing_master WHERE bill_id = ?", [$billId]);
        if (!$master) {
            throw new \Exception("Billing master record not found for bill_id: $billId");
        }
        
        $this->db->execute(
            "UPDATE ipd_billing_master SET amount_paid = amount_paid + ?, updated_by = ?, updated_at = NOW() WHERE bill_id = ?",
            [$amount, $user, $billId]
        );
        
        $paymentData = [
            'bill_id'      => $billId,
            'admission_id' => $master['admission_id'],
            'patient_id'   => $master['patient_id'],
            'amount'       => $amount,
            'payment_mode' => $mode,
            'payment_type' => $type,
            'reference_no' => $ref,
            'created_by'   => $user
        ];
        
        $paymentModel = new IpdPayment();
        $result = $paymentModel->recordPayment($paymentData);
        if (!$result['success']) {
            throw new \Exception($result['message'] ?? 'Failed to record payment');
        }
        return $result;
    }



    /* ───────────────────────────────────────────────────────────────
     * 11. GENERATE BILL ID
     * ─────────────────────────────────────────────────────────────── */
    private function generateBillId(): string {
        $prefix = 'BILL-' . date('Ymd') . '-';
        $last   = $this->fetchOne(
            "SELECT bill_id FROM ipd_billing_master WHERE bill_id LIKE ? ORDER BY bill_id DESC LIMIT 1",
            [$prefix . '%']
        );
        $seq = 1;
        if ($last) {
            $parts = explode('-', $last['bill_id']);
            $seq   = (int)end($parts) + 1;
        }
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
