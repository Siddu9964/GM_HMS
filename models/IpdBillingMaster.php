<?php
namespace GM_HMS\Models;

/**
 * IpdBillingMaster Model
 * ONE record per admission — the single source of financial truth.
 *
 * @package IPD_Management\Models
 */


class IpdBillingMaster extends IpdBaseModel {
    protected $table      = 'ipd_billing_master';
    protected $primaryKey = 'bill_id';
    protected $timestamps = false; // managed manually



    public function syncClinicalRecords(string $billId, string $admissionId, string $user, string $patientId = null): void {
        $records = $this->fetchAll("SELECT * FROM ipd_clinical_records WHERE admission_id = ?", [$admissionId]);
        if (!$records) return;
        
        $patientId = $patientId ?? ($records[0]['patient_id'] ?? null);

        $itemsAdded = false;

        $processChart = function($chartData, $chartName, $date) use ($billId, $admissionId, $patientId, $user, &$itemsAdded) {
            if (!empty($chartData) && $chartData !== '[]') {
                $names = [
                    'dialysis_chart'          => 'Dialysis',
                    'oxygen_chart'            => 'Oxygen Support',
                    'ventilation_chart'       => 'Ventilation Support',
                    'blood_transfusion_chart' => 'Blood Transfusion'
                ];
                $description = $names[$chartName] ?? ucwords(str_replace('_', ' ', $chartName));
                
                // Extract details based on chart type
                $details = [];
                $decoded = json_decode($chartData, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $entry) {
                        if (!is_array($entry)) continue;
                        
                        $line = [];
                        if ($chartName === 'dialysis_chart') {
                            if (!empty($entry['dia_date'])) $line[] = "Date: {$entry['dia_date']}";
                            if (!empty($entry['dia_start'])) $line[] = "Connected: {$entry['dia_start']}";
                            if (!empty($entry['dia_end'])) $line[] = "Disconnected: {$entry['dia_end']}";
                            if (!empty($entry['dia_dur'])) $line[] = "Duration: {$entry['dia_dur']}";
                        } 
                        elseif ($chartName === 'oxygen_chart') {
                            if (!empty($entry['oxy_date'])) $line[] = "Date: {$entry['oxy_date']}";
                            if (!empty($entry['oxy_start'])) $line[] = "Connected: {$entry['oxy_start']}";
                            if (!empty($entry['oxy_end'])) $line[] = "Disconnected: {$entry['oxy_end']}";
                            if (!empty($entry['oxy_dur'])) $line[] = "Duration: {$entry['oxy_dur']}";
                        } 
                        elseif ($chartName === 'ventilation_chart') {
                            if (!empty($entry['vent_date'])) $line[] = "Date: {$entry['vent_date']}";
                            if (!empty($entry['vent_start'])) $line[] = "Connected: {$entry['vent_start']}";
                            if (!empty($entry['vent_end'])) $line[] = "Disconnected: {$entry['vent_end']}";
                        } 
                        elseif ($chartName === 'blood_transfusion_chart') {
                            if (!empty($entry['trans_date'])) $line[] = "Date: {$entry['trans_date']}";
                            if (!empty($entry['blood_group'])) $line[] = "Blood Group: {$entry['blood_group']}";
                            if (!empty($entry['bag_number'])) $line[] = "Bag No: {$entry['bag_number']}";
                            if (!empty($entry['time_started'])) $line[] = "Started: {$entry['time_started']}";
                            if (!empty($entry['time_ended'])) $line[] = "Ended: {$entry['time_ended']}";
                        }
                        
                        if (!empty($line)) {
                            $details[] = implode(', ', $line);
                        }
                    }
                }
                
                if (!empty($details)) {
                    $description .= ' (' . implode(' | ', $details) . ')';
                }
                
                // Check if already billed to avoid duplicates (matching description and date)
                $exists = $this->fetchOne(
                    "SELECT item_id FROM ipd_billing_items 
                     WHERE bill_id = ? AND description = ? AND charge_type = 'OTHER' AND charge_date = ? AND status != 'CANCELLED'",
                    [$billId, $description, $date]
                );
                
                if (!$exists) {
                    $itemsJson = json_encode([
                        'source' => 'CLINICAL_CHART',
                        'chart_name' => $chartName,
                        'name' => $description,
                        'qty' => 1,
                        'price' => 0.00,
                        'total' => 0.00
                    ]);

                    $this->db->insert('ipd_billing_items', [
                        'bill_id'     => $billId,
                        'patient_id'  => $patientId,
                        'admission_id'=> $admissionId,
                        'charge_date' => $date,
                        'charge_type' => 'OTHER',
                        'description' => $description,
                        'total_amount'=> 0.00,
                        'items_json'  => $itemsJson,
                        'status'      => 'COMPLETED',
                        'created_by'  => $user,
                        'created_at'  => date('Y-m-d H:i:s')
                    ]);
                    $itemsAdded = true;
                }
            }
        };

        foreach ($records as $record) {
            $date = $record['record_date'] ?? $record['created_at'] ?? date('Y-m-d');
            if (strlen($date) > 10) $date = substr($date, 0, 10);

            // 1. Process specific charts
            $processChart($record['dialysis_chart'], 'dialysis_chart', $date);
            $processChart($record['oxygen_chart'], 'oxygen_chart', $date);
            $processChart($record['ventilation_chart'], 'ventilation_chart', $date);
            $processChart($record['blood_transfusion_chart'], 'blood_transfusion_chart', $date);

            // 2. Process general billing_items JSON (fixing the old bug)
            if (!empty($record['billing_items'])) {
                $billingItems = json_decode($record['billing_items'], true);
                if (is_array($billingItems)) {
                    foreach ($billingItems as $item) {
                        $type     = $item['Type'] ?? $item['type'] ?? 'OTHER';
                        $itemName = $item['Description'] ?? $item['description'] ?? 'Clinical Item';
                        $qty      = (float)($item['Quantity'] ?? $item['quantity'] ?? 1);
                        $price    = (float)($item['Unit Price'] ?? $item['unit_price'] ?? 0);
                        $amount   = (float)($item['Amount'] ?? $item['amount'] ?? ($qty * $price));
                        
                        if ($amount > 0) {
                            $exists = $this->fetchOne(
                                "SELECT item_id FROM ipd_billing_items 
                                 WHERE bill_id = ? AND description = ? AND charge_type = ? AND charge_date = ? AND status != 'CANCELLED'",
                                [$billId, $itemName, $type, $date]
                            );
                            
                            if (!$exists) {
                                $itemsJson = json_encode([
                                    'name' => $itemName,
                                    'qty' => $qty,
                                    'price' => $price,
                                    'total' => $amount
                                ]);

                                $this->db->insert('ipd_billing_items', [
                                    'bill_id'     => $billId,
                                    'patient_id'  => $patientId,
                                    'admission_id'=> $admissionId,
                                    'charge_date' => $date,
                                    'charge_type' => $type,
                                    'description' => $itemName,
                                    'total_amount'=> $amount,
                                    'items_json'  => $itemsJson,
                                    'status'      => 'COMPLETED',
                                    'created_by'  => $user,
                                    'created_at'  => date('Y-m-d H:i:s')
                                ]);
                                $itemsAdded = true;
                            }
                        }
                    }
                }
            }
        }
        
        if ($itemsAdded) {
            $this->recalculateMaster($billId, $user);
        }
    }

    /* ───────────────────────────────────────────────────────────────
     * 1. GET OR CREATE BILLING MASTER
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
                    ia.sponsor, ia.credit_type, ia.total_bed_amount AS adm_total_bed_amount,
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
                                ia.sponsor, ia.credit_type, ia.total_bed_amount AS adm_total_bed_amount,
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
                    ia.sponsor, ia.credit_type, ia.total_bed_amount AS adm_total_bed_amount,
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
        $this->recalculateMaster($billId);
        return $this->fetchOne(
            "SELECT bm.*,
                    COALESCE(ia.patient_id, bm.patient_id) AS patient_id,
                    TRIM(CONCAT(COALESCE(p.first_name, ''), ' ', COALESCE(p.last_name, ''))) AS patient_name,
                    p.age, p.sex, p.phone, p.address,
                    COALESCE(d.full_name, '') AS doctor_name, d.specialization,
                    COALESCE(hb.ward_name, ia.ward_name, '') AS ward_name,
                    COALESCE(hb.room_name, ia.room_name, '') AS room_name,
                    COALESCE(hb.bed_number, '') AS bed_number,
                    COALESCE(hb.room_type, ia.room_type, '') AS room_type,
                    COALESCE(hb.room_number, ia.room_no, '') AS room_no,
                    hb.amount_per_day, hb.nursig_charge, hb.doctor_charge, hb.total_bed_amount,
                    ia.admission_id AS adm_id, ia.bed_id, ia.sponsor, ia.credit_type, ia.total_bed_amount AS adm_total_bed_amount,
                    COALESCE(ia.admission_date, bm.admission_date) AS admission_date,
                    COALESCE(ia.discharge_date, bm.discharge_date) AS discharge_date,
                    CASE WHEN bm.bill_type = 'SELF' THEN 'SELF' ELSE COALESCE(ins.insurance_type, bm.bill_type) END AS insurance_type,
                    CASE WHEN bm.bill_type = 'SELF' THEN '' ELSE COALESCE(ins.company_name, bm.sponsor, '') END AS insurance_company_name,
                    CASE WHEN bm.bill_type = 'SELF' THEN '' ELSE COALESCE(ins.tpa_name, '') END AS tpa_name,
                    CASE WHEN bm.bill_type = 'SELF' THEN '' ELSE COALESCE(ins.policy_number, bm.policy_number, '') END AS policy_number,
                    CASE WHEN bm.bill_type = 'SELF' THEN '' ELSE COALESCE(ins.claim_number, '') END AS claim_number,
                    CASE WHEN bm.bill_type = 'SELF' THEN '' ELSE COALESCE(ins.approval_number, bm.approval_number, '') END AS approval_number,
                    CASE WHEN bm.bill_type = 'SELF' THEN 0 ELSE COALESCE(ins.approved_amount, bm.insurance_approved_amount, 0) END AS insurance_approved_amount,
                    CASE WHEN bm.bill_type = 'SELF' THEN 0 ELSE COALESCE(ins.patient_payable, bm.patient_payable, 0) END AS patient_payable,
                    CASE WHEN bm.bill_type = 'SELF' THEN 'NONE' ELSE COALESCE(ins.claim_status, 'PENDING') END AS insurance_claim_status
             FROM ipd_billing_master bm
             LEFT JOIN ipd_admissions ia ON bm.admission_id = ia.admission_id
             LEFT JOIN patient p ON COALESCE(ia.patient_id, bm.patient_id) = p.patient_id
             LEFT JOIN doctors d ON COALESCE(ia.admitting_doctor_id, bm.doctor_id) = d.doctor_id
             LEFT JOIN hospital_beds hb ON ia.bed_id = hb.sl_no
             LEFT JOIN ipd_insurance ins ON (bm.bill_id = ins.bill_id AND ins.claim_status != 'CANCELLED')
             WHERE bm.bill_id = ?",
            [$billId]
        );
    }

    public function getByAdmission(string $admissionId): ?array {
        $billId = $this->fetchOne("SELECT bill_id FROM ipd_billing_master WHERE admission_id = ?", [$admissionId])['bill_id'] ?? null;
        if ($billId) {
            $this->recalculateMaster($billId);
        }
        return $this->fetchOne(
            "SELECT bm.*,
                    COALESCE(ia.patient_id, bm.patient_id) AS patient_id,
                    TRIM(CONCAT(COALESCE(p.first_name, ''), ' ', COALESCE(p.last_name, ''))) AS patient_name,
                    p.age, p.sex, p.phone,
                    COALESCE(d.full_name, '') AS doctor_name,
                    COALESCE(hb.ward_name, ia.ward_name, '') AS ward_name,
                    COALESCE(hb.room_name, ia.room_name, '') AS room_name,
                    COALESCE(hb.bed_number, '') AS bed_number,
                    COALESCE(hb.room_type, ia.room_type, '') AS room_type,
                    COALESCE(hb.room_number, ia.room_no, '') AS room_no,
                    hb.amount_per_day, hb.nursig_charge, hb.doctor_charge, hb.total_bed_amount,
                    ia.sponsor, ia.credit_type, ia.total_bed_amount AS adm_total_bed_amount,
                    COALESCE(ia.admission_date, bm.admission_date) AS admission_date,
                    COALESCE(ia.discharge_date, bm.discharge_date) AS discharge_date,
                    CASE WHEN bm.bill_type = 'SELF' THEN 'SELF' ELSE COALESCE(ins.insurance_type, bm.bill_type) END AS insurance_type,
                    CASE WHEN bm.bill_type = 'SELF' THEN '' ELSE COALESCE(ins.company_name, bm.sponsor, '') END AS insurance_company_name,
                    CASE WHEN bm.bill_type = 'SELF' THEN '' ELSE COALESCE(ins.tpa_name, '') END AS tpa_name,
                    CASE WHEN bm.bill_type = 'SELF' THEN '' ELSE COALESCE(ins.policy_number, bm.policy_number, '') END AS policy_number,
                    CASE WHEN bm.bill_type = 'SELF' THEN '' ELSE COALESCE(ins.claim_number, '') END AS claim_number,
                    CASE WHEN bm.bill_type = 'SELF' THEN '' ELSE COALESCE(ins.approval_number, bm.approval_number, '') END AS approval_number,
                    CASE WHEN bm.bill_type = 'SELF' THEN 0 ELSE COALESCE(ins.approved_amount, bm.insurance_approved_amount, 0) END AS insurance_approved_amount,
                    CASE WHEN bm.bill_type = 'SELF' THEN 0 ELSE COALESCE(ins.patient_payable, bm.patient_payable, 0) END AS patient_payable,
                    CASE WHEN bm.bill_type = 'SELF' THEN 'NONE' ELSE COALESCE(ins.claim_status, 'PENDING') END AS insurance_claim_status
             FROM ipd_billing_master bm
             LEFT JOIN ipd_admissions ia ON bm.admission_id = ia.admission_id
             LEFT JOIN patient p ON COALESCE(ia.patient_id, bm.patient_id) = p.patient_id
             LEFT JOIN doctors d ON COALESCE(ia.admitting_doctor_id, bm.doctor_id) = d.doctor_id
             LEFT JOIN hospital_beds hb ON ia.bed_id = hb.sl_no
             LEFT JOIN ipd_insurance ins ON (bm.bill_id = ins.bill_id AND ins.claim_status != 'CANCELLED')
             WHERE bm.admission_id = ?",
            [$admissionId]
        );
    }

    /* ───────────────────────────────────────────────────────────────
     * 3. RECALCULATE MASTER  (the heart of the system)
     * ─────────────────────────────────────────────────────────────── */
    public function recalculateMaster(string $billId, string $updatedBy = 'system'): array {
        // Step 0: Auto-Deduplicate any historical duplicate one-time charges on this bill
        $this->deduplicateOneTimeCharges($billId);

        // Step 0.5: 24-Hour Catch-Up Mechanism for Bed Charges
        $this->catchUpBedCharges($billId, $updatedBy);

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
            'MISC'         => 'other_charges',
            'MISCELLANEOUS'=> 'other_charges',
            'OTHER'        => 'other_charges',
        ];

        foreach ($itemSums as $row) {
            $key = $typeMap[$row['charge_type']] ?? 'other_charges';
            $charges[$key] += (float)$row['cat_total'];
        }

        // Step 2: Subtotal
        $subtotal = array_sum($charges);

        // Step 3: Get current discount from master
        $master = $this->fetchOne(
            "SELECT admission_id, bill_type, sponsor, discount_amount, discount_percentage, insurance_approved_amount FROM ipd_billing_master WHERE bill_id = ?",
            [$billId]
        );
        $discountAmt = (float)($master['discount_amount'] ?? 0);
        $discountPct = (float)($master['discount_percentage'] ?? 0);
        if ($discountPct > 0 && $discountAmt == 0) {
            $discountAmt = round($subtotal * $discountPct / 100, 2);
        }
        $grandTotal = max(0, $subtotal - $discountAmt);

        // Step 3.5: Auto-heal any dummy payment rows created by previous bugs & stale insurance rows for self bills
        try {
            $this->db->execute(
                "DELETE FROM ipd_payment 
                 WHERE bill_id = ? AND payment_mode = 'INSURANCE' 
                   AND (reference_no IS NULL OR reference_no = '') 
                   AND (remarks LIKE 'Sponsor:%' OR remarks LIKE '%(INSURANCE)%' OR remarks LIKE '%(TPA)%')",
                [$billId]
            );

            if (($master['bill_type'] ?? 'SELF') === 'SELF') {
                $this->db->execute("DELETE FROM ipd_insurance WHERE bill_id = ?", [$billId]);
                if (!empty($master['sponsor'])) {
                    $this->db->execute("UPDATE ipd_billing_master SET sponsor = NULL, insurance_company_id = NULL, policy_number = NULL, approval_number = NULL, insurance_approved_amount = 0, insurance_received_amount = 0, patient_payable = 0 WHERE bill_id = ?", [$billId]);
                }
                if (!empty($master['admission_id'])) {
                    $this->db->execute("UPDATE ipd_admissions SET sponsor = NULL, admission_type = 'Cash', credit_type = 'CASH' WHERE admission_id = ? AND (sponsor = 'SELF' OR sponsor IS NULL)", [$master['admission_id']]);
                }
            }
        } catch (\Throwable $e) {}

        // Step 4: Payment totals from ipd_payment
        $payRow = $this->fetchOne(
            "SELECT 
                COALESCE(SUM(CASE WHEN payment_mode!='INSURANCE' AND payment_type!='REFUND' THEN amount ELSE 0 END),0) AS paid,
                COALESCE(SUM(CASE WHEN payment_type='REFUND' THEN amount ELSE 0 END),0)                   AS refunded,
                COALESCE(SUM(CASE WHEN payment_mode='INSURANCE' THEN amount ELSE 0 END),0)                          AS ins_rcvd,
                COALESCE(SUM(CASE WHEN payment_type='ADVANCE' AND payment_mode!='INSURANCE' THEN amount ELSE 0 END),0) AS advance
             FROM ipd_payment WHERE bill_id = ?",
            [$billId]
        );
        $amountPaid   = max(0, (float)$payRow['paid'] - (float)$payRow['refunded']);
        $insReceived  = (float)$payRow['ins_rcvd'];
        $advanceAmt   = (float)$payRow['advance'];

        // Step 5: Insurance
        $insApproved = 0;
        $patientPayable = 0;
        if (($master['bill_type'] ?? 'SELF') !== 'SELF') {
            $insApproved = (float)($master['insurance_approved_amount'] ?? 0);
            // Re-fetch from ipd_insurance if exists and not cancelled
            $insRow = $this->fetchOne("SELECT approved_amount FROM ipd_insurance WHERE bill_id = ? AND claim_status != 'CANCELLED'", [$billId]);
            if ($insRow) {
                $insApproved = (float)$insRow['approved_amount'];
            }
            $patientPayable = max(0, $grandTotal - $insApproved);
        }

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
        return $rows > 0;
    }

    /* ───────────────────────────────────────────────────────────────
     * 6. UPDATE BILL TYPE (SELF / INSURANCE / CORPORATE)
     * ─────────────────────────────────────────────────────────────── */
    public function updateBillType(string $billId, string $billType, array $insuranceData, string $updatedBy): bool {
        $now = date('Y-m-d H:i:s');
        $data = [
            'bill_type'   => $billType,
            'updated_by'  => $updatedBy,
            'updated_at'  => $now,
        ];
        if ($billType !== 'SELF') {
            if (!empty($insuranceData['company_name'])) {
                $data['sponsor'] = $insuranceData['company_name'];
            } elseif (!empty($insuranceData['sponsor'])) {
                $data['sponsor'] = $insuranceData['sponsor'];
            }
            $data['insurance_company_id'] = $insuranceData['insurance_company_id'] ?? null;
            $data['policy_number']        = $insuranceData['policy_number']        ?? null;
            $data['approval_number']      = $insuranceData['approval_number']      ?? null;
            $data['insurance_approved_amount'] = $insuranceData['approved_amount'] ?? 0;
        } else {
            $data['sponsor'] = null;
            $data['insurance_company_id'] = null;
            $data['policy_number'] = null;
            $data['approval_number'] = null;
            $data['insurance_approved_amount'] = 0;
            $data['insurance_received_amount'] = 0;
            $data['patient_payable'] = 0;

            // Remove insurance table row for this bill
            try {
                $this->db->execute("DELETE FROM ipd_insurance WHERE bill_id = ?", [$billId]);
            } catch (\Throwable $e) {}
        }
        $this->db->update('ipd_billing_master', $data, "`bill_id` = ?", [$billId]);

        // Sync to ipd_admissions
        $bm = $this->fetchOne("SELECT admission_id FROM ipd_billing_master WHERE bill_id = ?", [$billId]);
        if (!empty($bm['admission_id'])) {
            $admUpdate = [
                'admission_type' => ($billType === 'SELF' ? 'Cash' : 'Insurance'),
                'credit_type'    => ($billType === 'SELF' ? 'CASH' : 'INSURANCE'),
                'updated_at'     => $now
            ];
            $admUpdate['sponsor'] = ($billType === 'SELF' ? null : ($data['sponsor'] ?? null));
            $this->db->update('ipd_admissions', $admUpdate, "`admission_id` = ?", [$bm['admission_id']]);
        }

        if ($billType !== 'SELF') {
            // Unbundle any past lumped ROOM_RENT charges for this bill
            $lumpedItems = $this->fetchAll(
                "SELECT bi.*, hb.amount_per_day, hb.nursig_charge, hb.doctor_charge, COALESCE(hb.service_charge, 500) as service_charge, hb.total_bed_amount, hb.room_name, hb.bed_number, ia.admission_id, ia.patient_id
                 FROM ipd_billing_items bi
                 JOIN ipd_billing_master bm ON bi.bill_id = bm.bill_id
                 JOIN ipd_admissions ia ON bm.admission_id = ia.admission_id
                 JOIN hospital_beds hb ON ia.bed_id = hb.sl_no
                 WHERE bi.bill_id = ? AND bi.charge_type = 'ROOM_RENT' AND bi.status != 'CANCELLED'
                   AND bi.total_amount >= hb.total_bed_amount AND hb.total_bed_amount > hb.amount_per_day",
                [$billId]
            );

            foreach ($lumpedItems as $lump) {
                $baseRent = (float)$lump['amount_per_day'];
                $nursing = (float)$lump['nursig_charge'];
                $doc = (float)$lump['doctor_charge'];
                $svc = (float)$lump['service_charge'];
                $cDate = $lump['charge_date'];
                $admId = $lump['admission_id'];
                $patId = $lump['patient_id'];

                // 1. Update the original ROOM_RENT item to pure room rent
                $this->db->update('ipd_billing_items', [
                    'description' => "Room Rent - " . ($lump['room_name'] ?? 'Room') . " (Bed " . ($lump['bed_number'] ?? '') . ")",
                    'total_amount'=> $baseRent,
                    'items_json'  => json_encode([['name' => 'Room Rent', 'qty' => 1, 'price' => $baseRent, 'total' => $baseRent]]),
                    'updated_at'  => $now
                ], 'item_id = ?', [$lump['item_id']]);

                // 2. Add Nursing
                if ($nursing > 0) {
                    $this->db->insert('ipd_billing_items', [
                        'bill_id'     => $billId,
                        'admission_id'=> $admId,
                        'patient_id'  => $patId,
                        'charge_type' => 'PROCEDURE',
                        'description' => "Nursing Charges - " . ($lump['room_name'] ?? 'Room') . " (Bed " . ($lump['bed_number'] ?? '') . ")",
                        'total_amount'=> $nursing,
                        'charge_date' => $cDate,
                        'status'      => 'COMPLETED',
                        'created_by'  => $updatedBy,
                        'created_at'  => $now,
                        'updated_at'  => $now
                    ]);
                }

                // 3. Add Doctor
                if ($doc > 0) {
                    $this->db->insert('ipd_billing_items', [
                        'bill_id'     => $billId,
                        'admission_id'=> $admId,
                        'patient_id'  => $patId,
                        'charge_type' => 'DOCTOR_VISIT',
                        'description' => "Duty Doctor Charges - " . ($lump['room_name'] ?? 'Room') . " (Bed " . ($lump['bed_number'] ?? '') . ")",
                        'total_amount'=> $doc,
                        'charge_date' => $cDate,
                        'status'      => 'COMPLETED',
                        'created_by'  => $updatedBy,
                        'created_at'  => $now,
                        'updated_at'  => $now
                    ]);
                }

                // 4. Add Service
                if ($svc > 0) {
                    $this->db->insert('ipd_billing_items', [
                        'bill_id'     => $billId,
                        'admission_id'=> $admId,
                        'patient_id'  => $patId,
                        'charge_type' => 'MISC',
                        'description' => "Service Charges - " . ($lump['room_name'] ?? 'Room') . " (Bed " . ($lump['bed_number'] ?? '') . ")",
                        'total_amount'=> $svc,
                        'charge_date' => $cDate,
                        'status'      => 'COMPLETED',
                        'created_by'  => $updatedBy,
                        'created_at'  => $now,
                        'updated_at'  => $now
                    ]);
                }
            }
        }

        $this->recalculateMaster($billId, $updatedBy);
        return true;
    }

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
     * 10. CATCH-UP 24-HOUR CYCLE BED CHARGES
     * ─────────────────────────────────────────────────────────────── */
    private function catchUpBedCharges(string $billId, string $updatedBy = 'system'): void {
        // Fetch Admission Details and Bed Info
        $admission = $this->fetchOne(
            "SELECT COALESCE(ia.patient_id, bm.patient_id) AS patient_id, bm.admission_id, 
                    COALESCE(ia.admission_date, bm.admission_date) AS admission_date, 
                    ia.admission_time, 
                    COALESCE(ia.discharge_date, bm.discharge_date) AS discharge_date, 
                    ia.discharge_time,
                    hb.room_name, hb.amount_per_day, hb.nursig_charge, hb.doctor_charge, hb.service_charge, hb.total_bed_amount
             FROM ipd_billing_master bm
             JOIN ipd_admissions ia ON bm.admission_id = ia.admission_id
             LEFT JOIN hospital_beds hb ON ia.bed_id = hb.sl_no
             WHERE bm.bill_id = ?",
            [$billId]
        );

        if (!$admission) return;

        // Combine date and time to create exact admission timestamp
        $admissionDateTime = trim(($admission['admission_date'] ?? '') . ' ' . ($admission['admission_time'] ?: '00:00:00'));
        $admTimestamp = strtotime($admissionDateTime);

        if (!$admTimestamp || $admTimestamp < 0) {
            $admTimestamp = time(); // fallback if invalid date
        }

        // Determine current end timestamp (Discharge or Now)
        if (!empty($admission['discharge_date']) && $admission['discharge_date'] !== '0000-00-00') {
            $dischargeTime = !empty($admission['discharge_time']) ? $admission['discharge_time'] : '23:59:59';
            $endTimestamp = strtotime($admission['discharge_date'] . ' ' . $dischargeTime);
            if (!$endTimestamp || $endTimestamp < 0) {
                $endTimestamp = time();
            }
        } else {
            $endTimestamp = time();
        }

        // Calculate exact hours elapsed
        $hoursElapsed = ($endTimestamp - $admTimestamp) / 3600;
        if ($hoursElapsed < 0) $hoursElapsed = 0;
        
        // Use ceil to charge for any started 24-hour block, ensuring at least 1 day is charged.
        $totalPeriods = max(1, (int) ceil($hoursElapsed / 24));

        // Sanity check to prevent loop timeouts (max 365 days)
        if ($totalPeriods > 365) {
            $totalPeriods = 365;
        }

        // We expect $totalPeriods number of ROOM_RENT charges to exist
        // Fetch existing generated daily charges for this bill (excluding cancelled)
        $existingCount = (int) $this->fetchOne(
            "SELECT COUNT(*) AS c FROM ipd_billing_items 
             WHERE bill_id = ? AND charge_type = 'ROOM_RENT' AND status != 'CANCELLED'",
            [$billId]
        )['c'];

        $periodsToAdd = $totalPeriods - $existingCount;

        if ($periodsToAdd > 0) {
            $baseBedRent = (float)($admission['amount_per_day'] ?? 0);
            $baseNursing = (float)($admission['nursig_charge'] ?? 0);
            $baseDoctor = (float)($admission['doctor_charge'] ?? 0);
            $baseService = isset($admission['service_charge']) ? (float)$admission['service_charge'] : 0;
            $roomPrice = (float)($admission['total_bed_amount'] ?? 0);
            $foodPrice = 570.00; // Default fixed food charge

            // Determine if patient is admitted under insurance
            $isInsurance = false;
            if (!empty($admission['admission_type']) && strcasecmp($admission['admission_type'], 'Insurance') === 0) $isInsurance = true;
            if (!empty($admission['bill_type']) && strcasecmp($admission['bill_type'], 'INSURANCE') === 0) $isInsurance = true;
            if (!empty($admission['credit_type']) && strcasecmp($admission['credit_type'], 'INSURANCE') === 0) $isInsurance = true;
            if (!empty($admission['sponsor']) && strcasecmp($admission['sponsor'], 'SELF') !== 0 && trim($admission['sponsor']) !== '') $isInsurance = true;
            if (!$isInsurance) {
                $insCheck = $this->fetchOne("SELECT insurance_id FROM ipd_insurance WHERE bill_id = ? AND (approved_amount > 0 OR (company_name IS NOT NULL AND company_name != ''))", [$billId]);
                if ($insCheck) $isInsurance = true;
            }

            // Loop and add the missing periods
            for ($i = 0; $i < $periodsToAdd; $i++) {
                $dayNumber = $existingCount + $i + 1;
                // Calculate the exact date for this period (Day 1 is admission date = dayNumber - 1)
                $chargeDate = date('Y-m-d', $admTimestamp + (($dayNumber - 1) * 86400));
                $roomName = $admission['room_name'] ?? 'Room';

                // Prevent duplicate room rent on the exact same date
                $dup = $this->fetchOne(
                    "SELECT item_id FROM ipd_billing_items 
                     WHERE bill_id = ? AND charge_type = 'ROOM_RENT' AND charge_date = ? AND status != 'CANCELLED'",
                    [$billId, $chargeDate]
                );

                if (!$dup) {
                    if ($isInsurance) {
                        // Under insurance, Room Rent MUST NOT include Nursing, Duty Doctor, or Service charges
                        if ($baseBedRent > 0) {
                            $this->db->insert('ipd_billing_items', [
                                'bill_id'     => $billId,
                                'patient_id'  => $admission['patient_id'],
                                'admission_id'=> $admission['admission_id'],
                                'charge_date' => $chargeDate,
                                'charge_type' => 'ROOM_RENT',
                                'description' => "Room Rent - " . $roomName . " - Day " . $dayNumber,
                                'total_amount'=> $baseBedRent,
                                'items_json'  => json_encode([['name' => 'Room Rent', 'qty' => 1, 'price' => $baseBedRent, 'total' => $baseBedRent]]),
                                'status'      => 'COMPLETED',
                                'created_by'  => $updatedBy,
                                'created_at'  => date('Y-m-d H:i:s')
                            ]);
                        }

                        // Nursing Charges separate item
                        if ($baseNursing > 0) {
                            $dupNurse = $this->fetchOne(
                                "SELECT item_id FROM ipd_billing_items WHERE bill_id = ? AND charge_date = ? AND charge_type = 'PROCEDURE' AND description LIKE 'Nursing Charges%' AND status != 'CANCELLED'",
                                [$billId, $chargeDate]
                            );
                            if (!$dupNurse) {
                                $this->db->insert('ipd_billing_items', [
                                    'bill_id'     => $billId,
                                    'patient_id'  => $admission['patient_id'],
                                    'admission_id'=> $admission['admission_id'],
                                    'charge_date' => $chargeDate,
                                    'charge_type' => 'PROCEDURE',
                                    'description' => "Nursing Charges - Day " . $dayNumber,
                                    'total_amount'=> $baseNursing,
                                    'status'      => 'COMPLETED',
                                    'created_by'  => $updatedBy,
                                    'created_at'  => date('Y-m-d H:i:s')
                                ]);
                            }
                        }

                        // Duty Doctor Charges separate item
                        if ($baseDoctor > 0) {
                            $dupDoc = $this->fetchOne(
                                "SELECT item_id FROM ipd_billing_items WHERE bill_id = ? AND charge_date = ? AND charge_type = 'DOCTOR_VISIT' AND description LIKE 'Duty Doctor Charges%' AND status != 'CANCELLED'",
                                [$billId, $chargeDate]
                            );
                            if (!$dupDoc) {
                                $this->db->insert('ipd_billing_items', [
                                    'bill_id'     => $billId,
                                    'patient_id'  => $admission['patient_id'],
                                    'admission_id'=> $admission['admission_id'],
                                    'charge_date' => $chargeDate,
                                    'charge_type' => 'DOCTOR_VISIT',
                                    'description' => "Duty Doctor Charges - Day " . $dayNumber,
                                    'total_amount'=> $baseDoctor,
                                    'status'      => 'COMPLETED',
                                    'created_by'  => $updatedBy,
                                    'created_at'  => date('Y-m-d H:i:s')
                                ]);
                            }
                        }

                        // Service Charges separate item
                        if ($baseService > 0) {
                            $dupServ = $this->fetchOne(
                                "SELECT item_id FROM ipd_billing_items WHERE bill_id = ? AND charge_date = ? AND charge_type = 'MISC' AND description LIKE 'Service Charges%' AND status != 'CANCELLED'",
                                [$billId, $chargeDate]
                            );
                            if (!$dupServ) {
                                $this->db->insert('ipd_billing_items', [
                                    'bill_id'     => $billId,
                                    'patient_id'  => $admission['patient_id'],
                                    'admission_id'=> $admission['admission_id'],
                                    'charge_date' => $chargeDate,
                                    'charge_type' => 'MISC',
                                    'description' => "Service Charges - Day " . $dayNumber,
                                    'total_amount'=> $baseService,
                                    'status'      => 'COMPLETED',
                                    'created_by'  => $updatedBy,
                                    'created_at'  => date('Y-m-d H:i:s')
                                ]);
                            }
                        }
                    } else if ($roomPrice > 0) {
                        $breakdownText = "Room Rent: ₹" . number_format($baseBedRent, 0) . " | Nursing Charges: ₹" . number_format($baseNursing, 0) . " | Duty Doctor Charges: ₹" . number_format($baseDoctor, 0) . " | Service Charges: ₹" . number_format($baseService, 0);
                        $roomDesc = "Room Rent - " . $roomName . " - Day " . $dayNumber . "<br><small style='color: #6c757d; font-size: 0.85em;'>" . $breakdownText . "</small>";
                        $this->db->insert('ipd_billing_items', [
                            'bill_id'     => $billId,
                            'patient_id'  => $admission['patient_id'],
                            'admission_id'=> $admission['admission_id'],
                            'charge_date' => $chargeDate,
                            'charge_type' => 'ROOM_RENT',
                            'description' => $roomDesc,
                            'total_amount'=> $roomPrice,
                            'items_json'  => json_encode([['name' => 'Room Rent', 'qty' => 1, 'price' => $roomPrice, 'total' => $roomPrice]]),
                            'status'      => 'COMPLETED',
                            'created_by'  => $updatedBy,
                            'created_at'  => date('Y-m-d H:i:s')
                        ]);
                    }

                    // Add Food Charge for this day under MISC if not already present
                    if ($foodPrice > 0) {
                        $dupFood = $this->fetchOne(
                            "SELECT item_id FROM ipd_billing_items 
                             WHERE bill_id = ? AND charge_type = 'MISC' AND charge_date = ? AND description LIKE 'Food Charge%' AND status != 'CANCELLED'",
                            [$billId, $chargeDate]
                        );
                        if (!$dupFood) {
                            $this->db->insert('ipd_billing_items', [
                                'bill_id'     => $billId,
                                'patient_id'  => $admission['patient_id'],
                                'admission_id'=> $admission['admission_id'],
                                'charge_date' => $chargeDate,
                                'charge_type' => 'MISC',
                                'description' => 'Food Charge - Day ' . $dayNumber,
                                'total_amount'=> $foodPrice,
                                'status'      => 'COMPLETED',
                                'created_by'  => $updatedBy,
                                'created_at'  => date('Y-m-d H:i:s')
                            ]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Auto-deduplicate one-time admission charges (Admission Charge, MRD Charge) on existing bills
     */
    public function deduplicateOneTimeCharges(string $billId): void {
        foreach (['Admission Charge', 'MRD Charge'] as $desc) {
            $rows = $this->fetchAll(
                "SELECT item_id FROM ipd_billing_items 
                 WHERE bill_id = ? AND description = ? AND status != 'CANCELLED'
                 ORDER BY item_id ASC",
                [$billId, $desc]
            );
            if (count($rows) > 1) {
                // Keep the first active row, cancel/remove duplicate rows
                $cancelIds = array_slice(array_column($rows, 'item_id'), 1);
                $placeholders = implode(',', array_fill(0, count($cancelIds), '?'));
                $this->db->execute(
                    "UPDATE ipd_billing_items SET status = 'CANCELLED', updated_at = NOW() WHERE item_id IN ($placeholders)",
                    $cancelIds
                );
            }
        }
    }

    /* ───────────────────────────────────────────────────────────────
     * 10. GENERATE BILL ID
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
