<?php
/**
 * IpdInsurance Model
 * One insurance record per billing master.
 *
 * @package IPD_Management\Models
 */
require_once __DIR__ . '/../core/BaseModel.php';

class IpdInsurance extends BaseModel {
    protected $table      = 'ipd_insurance';
    protected $primaryKey = 'insurance_id';
    protected $timestamps = false;

    /* ───────────────────────────────────────────────────────────────
     * 1. SAVE OR UPDATE
     * ─────────────────────────────────────────────────────────────── */
    public function saveOrUpdate(string $billId, array $data): array {
        $existing = $this->fetchOne("SELECT * FROM ipd_insurance WHERE bill_id = ?", [$billId]);
        $now      = date('Y-m-d H:i:s');
        $approvedAmt  = isset($data['approved_amount']) ? (float)$data['approved_amount'] : (float)($existing['approved_amount'] ?? 0);
        $receivedAmt  = isset($data['received_amount']) ? (float)$data['received_amount'] : (float)($existing['received_amount'] ?? 0);
        $pendingAmt   = max(0, $approvedAmt - $receivedAmt);

        // Get grand_total for patient_payable
        $master = $this->fetchOne("SELECT grand_total FROM ipd_billing_master WHERE bill_id = ?", [$billId]);
        $grandTotal   = (float)($master['grand_total'] ?? 0);
        $patientPayable = isset($data['patient_payable']) ? (float)$data['patient_payable'] : max(0, $grandTotal - $approvedAmt);

        $record = [
            'bill_id'              => $billId,
            'admission_id'         => !empty($data['admission_id']) ? $data['admission_id'] : ($existing['admission_id'] ?? null),
            'patient_id'           => !empty($data['patient_id']) ? $data['patient_id'] : ($existing['patient_id'] ?? null),
            'insurance_type'       => !empty($data['insurance_type']) ? $data['insurance_type'] : ($existing['insurance_type'] ?? 'INSURANCE'),
            'insurance_company_id' => isset($data['insurance_company_id']) ? $data['insurance_company_id'] : ($existing['insurance_company_id'] ?? null),
            'company_name'         => !empty($data['company_name']) ? $data['company_name'] : ($existing['company_name'] ?? null),
            'policy_number'        => isset($data['policy_number']) ? $data['policy_number'] : ($existing['policy_number'] ?? null),
            'claim_number'         => isset($data['claim_number']) ? $data['claim_number'] : ($existing['claim_number'] ?? null),
            'approval_number'      => isset($data['approval_number']) ? $data['approval_number'] : ($existing['approval_number'] ?? null),
            'tpa_name'             => isset($data['tpa_name']) ? $data['tpa_name'] : ($existing['tpa_name'] ?? null),
            'tpa_reference_no'     => isset($data['tpa_reference_no']) ? $data['tpa_reference_no'] : ($existing['tpa_reference_no'] ?? null),
            'approved_amount'      => $approvedAmt,
            'received_amount'      => $receivedAmt,
            'pending_amount'       => $pendingAmt,
            'patient_payable'      => $patientPayable,
            'claim_status'         => !empty($data['claim_status']) ? $data['claim_status'] : ($existing['claim_status'] ?? 'PENDING'),
            'remarks'              => isset($data['remarks']) ? $data['remarks'] : ($existing['remarks'] ?? null),
            'created_by'           => !empty($data['created_by']) ? $data['created_by'] : ($existing['created_by'] ?? 'system'),
        ];

        if ($existing) {
            $record['updated_at'] = $now;
            $this->db->update('ipd_insurance', $record, '`insurance_id` = ?', [$existing['insurance_id']]);
        } else {
            $record['created_at'] = $now;
            $record['updated_at'] = $now;
            $this->db->insert('ipd_insurance', $record);
        }

        $sponsorName = !empty($data['company_name']) ? $data['company_name'] : (!empty($data['tpa_name']) ? $data['tpa_name'] : ($data['insurance_type'] ?? 'INSURANCE'));

        // Update master insurance fields
        $this->db->update('ipd_billing_master', [
            'sponsor'                    => $sponsorName,
            'bill_type'                  => $data['insurance_type'] === 'CORPORATE' ? 'CORPORATE' : 'INSURANCE',
            'insurance_company_id'       => $data['insurance_company_id'] ?? null,
            'policy_number'              => $data['policy_number']        ?? null,
            'approval_number'            => $data['approval_number']      ?? null,
            'insurance_approved_amount'  => $approvedAmt,
            'patient_payable'            => $patientPayable,
            'updated_at'                 => $now,
        ], '`bill_id` = ?', [$billId]);

        // Also sync to ipd_admissions if admission_id is present
        $admId = $data['admission_id'] ?? null;
        if (!$admId) {
            $bm = $this->fetchOne("SELECT admission_id FROM ipd_billing_master WHERE bill_id = ?", [$billId]);
            $admId = $bm['admission_id'] ?? null;
        }
        if ($admId) {
            $this->db->update('ipd_admissions', [
                'sponsor'        => $sponsorName,
                'admission_type' => 'Insurance',
                'credit_type'    => $data['insurance_type'] ?? 'INSURANCE',
                'updated_at'     => $now,
            ], '`admission_id` = ?', [$admId]);
        }

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
     * 3. GET BY ID (Full Details with Patient, Admission & Bill Info)
     * ─────────────────────────────────────────────────────────────── */
    public function getById($id) {
        $sql = "SELECT ins.*, 
                       p.first_name, p.last_name, p.phone AS patient_phone, p.sex AS patient_gender, p.age AS patient_age,
                       adm.ward_name, adm.room_no, adm.room_type, adm.admission_date, adm.discharge_date, adm.status AS admission_status, adm.sponsor AS admission_sponsor,
                       bm.grand_total AS bill_grand_total, bm.amount_paid AS bill_paid_amount, bm.balance_due AS bill_balance_amount, bm.billing_status AS bill_status
                FROM ipd_insurance ins
                LEFT JOIN patient p ON ins.patient_id = p.patient_id
                LEFT JOIN ipd_admissions adm ON ins.admission_id = adm.admission_id
                LEFT JOIN ipd_billing_master bm ON ins.bill_id = bm.bill_id
                WHERE ins.insurance_id = ?";
        return $this->fetchOne($sql, [$id]);
    }

    /* ───────────────────────────────────────────────────────────────
     * 4. UPDATE CLAIM STATUS
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

    /* ───────────────────────────────────────────────────────────────
     * 4.1 CANCEL INSURANCE (Revert bill & admission to Self-Pay / Cash)
     * ─────────────────────────────────────────────────────────────── */
    public function cancelInsurance(string $billId, string $updatedBy = 'system'): array {
        $now = date('Y-m-d H:i:s');

        // 1. Mark existing insurance record as CANCELLED with approved_amount = 0
        try {
            $this->db->execute(
                "UPDATE ipd_insurance 
                 SET claim_status = 'CANCELLED', approved_amount = 0, pending_amount = 0, 
                     remarks = CONCAT(COALESCE(remarks, ''), ' [Cancelled / Reverted to Self-Pay]'), 
                     updated_at = ? 
                 WHERE bill_id = ?",
                [$now, $billId]
            );
        } catch (\Throwable $e) {}

        // 2. Revert billing master sponsor and insurance fields
        $this->db->execute(
            "UPDATE ipd_billing_master 
             SET bill_type = 'SELF', sponsor = 'SELF', insurance_approved_amount = 0, 
                 insurance_company_id = NULL, policy_number = NULL, approval_number = NULL, 
                 updated_by = ?, updated_at = ? 
             WHERE bill_id = ?",
            [$updatedBy, $now, $billId]
        );

        // 3. Revert admission credit type and sponsor
        $bm = $this->fetchOne("SELECT admission_id FROM ipd_billing_master WHERE bill_id = ?", [$billId]);
        $admId = $bm['admission_id'] ?? null;
        if ($admId) {
            $this->db->execute(
                "UPDATE ipd_admissions 
                 SET sponsor = 'SELF', admission_type = 'Cash', credit_type = 'CASH', updated_at = ? 
                 WHERE admission_id = ?",
                [$now, $admId]
            );
        }

        // 4. Recalculate billing master
        require_once __DIR__ . '/IpdBillingMaster.php';
        $summary = (new IpdBillingMaster())->recalculateMaster($billId, $updatedBy);

        return [
            'success' => true, 
            'message' => 'Insurance cancelled successfully. Bill reverted to Self-Pay / Cash.', 
            'financial' => $summary
        ];
    }

    /* ───────────────────────────────────────────────────────────────
     * 5. UPDATE FULL RECORD (All 25 columns & sync with Master)
     * ─────────────────────────────────────────────────────────────── */
    public function updateFullRecord(int $insuranceId, array $data, string $updatedBy = 'system'): array {
        $existing = $this->getById($insuranceId);
        if (!$existing) {
            return ['success' => false, 'message' => 'Insurance record not found'];
        }

        $now = date('Y-m-d H:i:s');
        $approvedAmt = isset($data['approved_amount']) ? (float)$data['approved_amount'] : (float)$existing['approved_amount'];
        $receivedAmt = isset($data['received_amount']) ? (float)$data['received_amount'] : (float)$existing['received_amount'];
        $pendingAmt  = isset($data['pending_amount']) ? (float)$data['pending_amount'] : max(0, $approvedAmt - $receivedAmt);
        
        $billId = $data['bill_id'] ?? $existing['bill_id'];
        
        // Calculate patient payable
        $grandTotal = (float)($existing['bill_grand_total'] ?? 0);
        if ($billId && $grandTotal <= 0) {
            $master = $this->fetchOne("SELECT grand_total FROM ipd_billing_master WHERE bill_id = ?", [$billId]);
            $grandTotal = (float)($master['grand_total'] ?? 0);
        }
        $patientPayable = isset($data['patient_payable']) ? (float)$data['patient_payable'] : max(0, $grandTotal - $approvedAmt);

        $record = [
            'insurance_type'       => !empty($data['insurance_type']) ? trim($data['insurance_type']) : ($existing['insurance_type'] ?? 'INSURANCE'),
            'insurance_company_id' => !empty($data['insurance_company_id']) ? trim($data['insurance_company_id']) : null,
            'company_name'         => !empty($data['company_name']) ? trim($data['company_name']) : null,
            'tpa_name'             => !empty($data['tpa_name']) ? trim($data['tpa_name']) : null,
            'tpa_reference_no'     => !empty($data['tpa_reference_no']) ? trim($data['tpa_reference_no']) : null,
            'policy_number'        => !empty($data['policy_number']) ? trim($data['policy_number']) : null,
            'claim_number'         => !empty($data['claim_number']) ? trim($data['claim_number']) : null,
            'approval_number'      => !empty($data['approval_number']) ? trim($data['approval_number']) : null,
            'approved_amount'      => $approvedAmt,
            'received_amount'      => $receivedAmt,
            'pending_amount'       => $pendingAmt,
            'patient_payable'      => $patientPayable,
            'claim_status'         => !empty($data['claim_status']) ? trim($data['claim_status']) : ($existing['claim_status'] ?? 'PENDING'),
            'submitted_date'       => !empty($data['submitted_date']) ? $data['submitted_date'] : null,
            'approved_date'        => !empty($data['approved_date']) ? $data['approved_date'] : null,
            'settled_date'         => !empty($data['settled_date']) ? $data['settled_date'] : null,
            'rejection_reason'     => !empty($data['rejection_reason']) ? trim($data['rejection_reason']) : null,
            'remarks'              => !empty($data['remarks']) ? trim($data['remarks']) : null,
            'updated_at'           => $now,
        ];

        if ($record['claim_status'] === 'APPROVED' && empty($record['approved_date'])) {
            $record['approved_date'] = date('Y-m-d');
        }
        if ($record['claim_status'] === 'SETTLED' && empty($record['settled_date'])) {
            $record['settled_date'] = date('Y-m-d');
        }
        if ($record['claim_status'] === 'SUBMITTED' && empty($record['submitted_date'])) {
            $record['submitted_date'] = date('Y-m-d');
        }

        $this->db->update('ipd_insurance', $record, '`insurance_id` = ?', [$insuranceId]);

        $sponsorName = !empty($record['company_name']) ? $record['company_name'] : (!empty($record['tpa_name']) ? $record['tpa_name'] : $record['insurance_type']);

        if ($billId) {
            $this->db->update('ipd_billing_master', [
                'sponsor'                   => $sponsorName,
                'bill_type'                 => $record['insurance_type'] === 'CORPORATE' ? 'CORPORATE' : 'INSURANCE',
                'insurance_company_id'      => $record['insurance_company_id'],
                'policy_number'             => $record['policy_number'],
                'approval_number'           => $record['approval_number'],
                'insurance_approved_amount' => $approvedAmt,
                'patient_payable'           => $patientPayable,
                'updated_at'                => $now,
            ], '`bill_id` = ?', [$billId]);

            require_once __DIR__ . '/IpdBillingMaster.php';
            (new IpdBillingMaster())->recalculateMaster($billId, $updatedBy);
        }

        $admId = $data['admission_id'] ?? $existing['admission_id'];
        if ($admId) {
            $this->db->update('ipd_admissions', [
                'sponsor'        => $sponsorName,
                'admission_type' => 'Insurance',
                'credit_type'    => $record['insurance_type'],
                'updated_at'     => $now,
            ], '`admission_id` = ?', [$admId]);
        }

        $updatedRecord = $this->getById($insuranceId);
        return [
            'success' => true,
            'message' => 'Insurance record updated successfully',
            'data'    => $updatedRecord
        ];
    }

    /* ───────────────────────────────────────────────────────────────
     * 6. SERVER-SIDE PAGINATED LIST WITH ADVANCED FILTERING & STATS
     * ─────────────────────────────────────────────────────────────── */
    public function getPaginatedList(array $filters = [], int $page = 1, int $limit = 25, string $sortBy = 'insurance_id', string $sortDir = 'DESC'): array {
        $page  = max(1, $page);
        $limit = max(1, min(100, $limit));
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        // Global Universal Search (Searches across all fields)
        if (!empty($filters['search'])) {
            $s = '%' . trim($filters['search']) . '%';
            $where[] = "(
                ins.policy_number LIKE ? OR
                ins.claim_number LIKE ? OR
                ins.approval_number LIKE ? OR
                ins.company_name LIKE ? OR
                ins.tpa_name LIKE ? OR
                ins.tpa_reference_no LIKE ? OR
                ins.bill_id LIKE ? OR
                ins.admission_id LIKE ? OR
                ins.patient_id LIKE ? OR
                p.first_name LIKE ? OR
                p.last_name LIKE ? OR
                CONCAT(p.first_name, ' ', p.last_name) LIKE ? OR
                p.phone LIKE ? OR
                adm.ward_name LIKE ? OR
                adm.room_no LIKE ? OR
                ins.claim_status LIKE ? OR
                ins.insurance_type LIKE ?
            )";
            for ($i = 0; $i < 17; $i++) {
                $params[] = $s;
            }
        }

        if (!empty($filters['patient_name'])) {
            $pn = '%' . trim($filters['patient_name']) . '%';
            $where[] = "(p.first_name LIKE ? OR p.last_name LIKE ? OR CONCAT(p.first_name, ' ', p.last_name) LIKE ?)";
            $params[] = $pn;
            $params[] = $pn;
            $params[] = $pn;
        }

        if (!empty($filters['patient_id'])) {
            $where[] = "ins.patient_id LIKE ?";
            $params[] = '%' . trim($filters['patient_id']) . '%';
        }

        if (!empty($filters['admission_id'])) {
            $where[] = "ins.admission_id LIKE ?";
            $params[] = '%' . trim($filters['admission_id']) . '%';
        }

        if (!empty($filters['bill_id'])) {
            $where[] = "ins.bill_id LIKE ?";
            $params[] = '%' . trim($filters['bill_id']) . '%';
        }

        if (!empty($filters['company_name'])) {
            $where[] = "ins.company_name LIKE ?";
            $params[] = '%' . trim($filters['company_name']) . '%';
        }

        if (!empty($filters['tpa_name'])) {
            $where[] = "ins.tpa_name LIKE ?";
            $params[] = '%' . trim($filters['tpa_name']) . '%';
        }

        if (!empty($filters['insurance_type']) && strtoupper($filters['insurance_type']) !== 'ALL') {
            $where[] = "ins.insurance_type = ?";
            $params[] = trim($filters['insurance_type']);
        }

        if (!empty($filters['policy_number'])) {
            $where[] = "ins.policy_number LIKE ?";
            $params[] = '%' . trim($filters['policy_number']) . '%';
        }

        if (!empty($filters['claim_number'])) {
            $where[] = "ins.claim_number LIKE ?";
            $params[] = '%' . trim($filters['claim_number']) . '%';
        }

        if (!empty($filters['approval_number'])) {
            $where[] = "ins.approval_number LIKE ?";
            $params[] = '%' . trim($filters['approval_number']) . '%';
        }

        if (!empty($filters['claim_status']) && strtoupper($filters['claim_status']) !== 'ALL') {
            $where[] = "ins.claim_status = ?";
            $params[] = trim($filters['claim_status']);
        }

        $dateType = in_array($filters['date_type'] ?? '', ['created_at', 'submitted_date', 'approved_date', 'settled_date']) 
                    ? $filters['date_type'] 
                    : 'created_at';
        
        if (!empty($filters['date_from'])) {
            if ($dateType === 'created_at') {
                $where[] = "ins.created_at >= ?";
                $params[] = $filters['date_from'] . ' 00:00:00';
            } else {
                $where[] = "ins.{$dateType} >= ?";
                $params[] = $filters['date_from'];
            }
        }

        if (!empty($filters['date_to'])) {
            if ($dateType === 'created_at') {
                $where[] = "ins.created_at <= ?";
                $params[] = $filters['date_to'] . ' 23:59:59';
            } else {
                $where[] = "ins.{$dateType} <= ?";
                $params[] = $filters['date_to'];
            }
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $allowedSort = [
            'insurance_id'    => 'ins.insurance_id',
            'created_at'      => 'ins.created_at',
            'approved_amount' => 'ins.approved_amount',
            'received_amount' => 'ins.received_amount',
            'pending_amount'  => 'ins.pending_amount',
            'patient_payable' => 'ins.patient_payable',
            'claim_status'    => 'ins.claim_status',
            'company_name'    => 'ins.company_name',
            'policy_number'   => 'ins.policy_number',
            'patient_name'    => 'p.first_name',
            'submitted_date'  => 'ins.submitted_date',
            'approved_date'   => 'ins.approved_date',
            'settled_date'    => 'ins.settled_date',
        ];
        $orderCol = $allowedSort[$sortBy] ?? 'ins.insurance_id';
        $orderDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

        $countSql = "SELECT COUNT(*) as total
                     FROM ipd_insurance ins
                     LEFT JOIN patient p ON ins.patient_id = p.patient_id
                     LEFT JOIN ipd_admissions adm ON ins.admission_id = adm.admission_id
                     LEFT JOIN ipd_billing_master bm ON ins.bill_id = bm.bill_id
                     {$whereClause}";
        $countRow = $this->fetchOne($countSql, $params);
        $totalRecords = (int)($countRow['total'] ?? 0);

        $dataSql = "SELECT ins.*, 
                           p.first_name, p.last_name, p.phone AS patient_phone, p.sex AS patient_gender, p.age AS patient_age,
                           adm.ward_name, adm.room_no, adm.room_type, adm.admission_date, adm.discharge_date, adm.status AS admission_status, adm.sponsor AS admission_sponsor,
                           bm.grand_total AS bill_grand_total, bm.amount_paid AS bill_paid_amount, bm.balance_due AS bill_balance_amount, bm.billing_status AS bill_status
                    FROM ipd_insurance ins
                    LEFT JOIN patient p ON ins.patient_id = p.patient_id
                    LEFT JOIN ipd_admissions adm ON ins.admission_id = adm.admission_id
                    LEFT JOIN ipd_billing_master bm ON ins.bill_id = bm.bill_id
                    {$whereClause}
                    ORDER BY {$orderCol} {$orderDir}
                    LIMIT {$limit} OFFSET {$offset}";
        $records = $this->fetchAll($dataSql, $params);

        $statsSql = "SELECT 
                        COUNT(*) as total_count,
                        COALESCE(SUM(ins.approved_amount), 0) as total_approved,
                        COALESCE(SUM(ins.received_amount), 0) as total_received,
                        COALESCE(SUM(ins.pending_amount), 0) as total_pending,
                        COALESCE(SUM(ins.patient_payable), 0) as total_patient_payable,
                        SUM(CASE WHEN ins.claim_status = 'PENDING' THEN 1 ELSE 0 END) as pending_count,
                        SUM(CASE WHEN ins.claim_status = 'SUBMITTED' THEN 1 ELSE 0 END) as submitted_count,
                        SUM(CASE WHEN ins.claim_status = 'APPROVED' THEN 1 ELSE 0 END) as approved_count,
                        SUM(CASE WHEN ins.claim_status = 'SETTLED' THEN 1 ELSE 0 END) as settled_count,
                        SUM(CASE WHEN ins.claim_status = 'REJECTED' THEN 1 ELSE 0 END) as rejected_count
                     FROM ipd_insurance ins
                     LEFT JOIN patient p ON ins.patient_id = p.patient_id
                     LEFT JOIN ipd_admissions adm ON ins.admission_id = adm.admission_id
                     LEFT JOIN ipd_billing_master bm ON ins.bill_id = bm.bill_id
                     {$whereClause}";
        $stats = $this->fetchOne($statsSql, $params);

        $totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 1;

        return [
            'success'    => true,
            'records'    => $records,
            'pagination' => [
                'total_records' => $totalRecords,
                'current_page'  => $page,
                'per_page'      => $limit,
                'total_pages'   => $totalPages,
                'from_record'   => $totalRecords > 0 ? $offset + 1 : 0,
                'to_record'     => min($offset + $limit, $totalRecords)
            ],
            'stats'      => $stats
        ];
    }

    /* ───────────────────────────────────────────────────────────────
     * 7. GET DISTINCT COMPANIES & TPAS
     * ─────────────────────────────────────────────────────────────── */
    public function getDistinctCompanies(): array {
        $companies = $this->fetchAll("SELECT DISTINCT company_name FROM ipd_insurance WHERE company_name IS NOT NULL AND company_name != '' ORDER BY company_name ASC");
        $tpas = $this->fetchAll("SELECT DISTINCT tpa_name FROM ipd_insurance WHERE tpa_name IS NOT NULL AND tpa_name != '' ORDER BY tpa_name ASC");

        $compList = array_map(function($r) { return $r['company_name']; }, $companies);
        $tpaList  = array_map(function($r) { return $r['tpa_name']; }, $tpas);

        return [
            'companies' => array_values(array_unique($compList)),
            'tpas'      => array_values(array_unique($tpaList))
        ];
    }
}
