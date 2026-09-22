<?php
/**
 * IpdBillingItem Model
 * Every charge = 1 row. Never deleted — only cancelled.
 *
 * @package IPD_Management\Models
 */
require_once __DIR__ . '/../core/BaseModel.php';

class IpdBillingItem extends BaseModel {
    protected $table      = 'ipd_billing_items';
    protected $primaryKey = 'item_id';
    protected $timestamps = false;

    // Maps charge_type to ipd_billing_master column
    private const TYPE_TO_MASTER_COL = [
        'ROOM_RENT'    => 'room_charges',
        'DOCTOR_VISIT' => 'doctor_charges',
        'LAB'          => 'lab_charges',
        'RADIOLOGY'    => 'radiology_charges',
        'PHARMACY'     => 'pharmacy_charges',
        'OT'           => 'ot_charges',
        'PROCEDURE'    => 'procedure_charges',
        'CONSUMABLE'        => 'consumable_charges',
        'MISC'              => 'other_charges',
        'OTHER'             => 'other_charges',
        'DIALYSIS'          => 'procedure_charges',
        'OXYGEN'            => 'other_charges',
        'VENTILATION'       => 'other_charges',
        'VENTILATOR'        => 'other_charges',
        'BLOOD_TRANSFUSION' => 'other_charges',
        'WARD_TRANSFER'        => 'other_charges',
        'BED_UPGRADE_OVERRIDE' => 'room_charges',
    ];

    /* ───────────────────────────────────────────────────────────────
     * 1. ADD A SINGLE ITEM  (any category)
     * ─────────────────────────────────────────────────────────────── */
    public function addItem(string $billId, string $admissionId, string $patientId, array $data): array {
        // Validate charge_type
        $validTypes = array_keys(self::TYPE_TO_MASTER_COL);
        if (!in_array($data['charge_type'], $validTypes)) {
            return ['success' => false, 'message' => 'Invalid charge type'];
        }

        // Block adding charges if patient is discharged or billing is finalized
        $master = $this->fetchOne(
            "SELECT bm.billing_status, ia.status AS admission_status, ia.discharge_date
             FROM ipd_billing_master bm
             LEFT JOIN ipd_admissions ia ON bm.admission_id = ia.admission_id
             WHERE bm.bill_id = ?",
            [$billId]
        );
        if ($master && empty($data['force'])) {
            if ($master['billing_status'] === 'FINALIZED' || $master['billing_status'] === 'CANCELLED' || $master['admission_status'] === 'Discharged' || !empty($master['discharge_date'])) {
                return [
                    'success' => false,
                    'message' => 'This patient has already been discharged.'
                ];
            }
        }

        $isUpgrade = !empty($data['is_bed_upgrade']) || ($data['charge_type'] ?? '') === 'BED_UPGRADE_OVERRIDE';
        if ($isUpgrade) {
            $data['charge_type'] = 'ROOM_RENT';

            // Update admission room tariff (physical bed allocation is untouched)
            $updFields = [];
            $updParams = [];
            if (!empty($data['upgraded_room_type'])) {
                $updFields[] = "room_type = ?";
                $updParams[] = $data['upgraded_room_type'];
            }
            if (isset($data['amount_per_day']) && (float)$data['amount_per_day'] > 0) {
                $updFields[] = "amount_per_day = ?";
                $updParams[] = (int)round((float)$data['amount_per_day']);
            }
            if (isset($data['nursing_charge']) || isset($data['nursig_charge'])) {
                $updFields[] = "nursig_charge = ?";
                $updParams[] = (int)round((float)($data['nursing_charge'] ?? $data['nursig_charge'] ?? 0));
            }
            if (isset($data['doctor_charge'])) {
                $updFields[] = "doctor_charge = ?";
                $updParams[] = (int)round((float)$data['doctor_charge']);
            }
            if (isset($data['service_charge'])) {
                $updFields[] = "service_charge = ?";
                $updParams[] = (int)round((float)$data['service_charge']);
            }
            $bedTotal = (float)($data['total_bed_amount'] ?? $data['unit_price'] ?? 0);
            if ($bedTotal > 0) {
                $updFields[] = "total_bed_amount = ?";
                $updParams[] = (int)round($bedTotal);
            }

            if (!empty($updFields)) {
                $updFields[] = "updated_at = NOW()";
                $updParams[] = $admissionId;
                $this->db->execute(
                    "UPDATE ipd_admissions SET " . implode(', ', $updFields) . " WHERE admission_id = ?",
                    $updParams
                );
            }

            // Cancel any old room rent for this date so the upgrade replaces it cleanly
            $chgDate = $data['charge_date'] ?? date('Y-m-d');
            $existingRoom = $this->fetchOne(
                "SELECT item_id FROM ipd_billing_items 
                 WHERE bill_id = ? AND charge_type = 'ROOM_RENT' AND charge_date = ? AND status != 'CANCELLED'",
                [$billId, $chgDate]
            );
            if ($existingRoom) {
                $this->db->execute(
                    "UPDATE ipd_billing_items SET status = 'CANCELLED', updated_at = NOW() WHERE item_id = ?",
                    [$existingRoom['item_id']]
                );
            }

            // Insert separated items for the upgrade
            $bedRent    = (float)($data['amount_per_day'] ?? $data['bed_rent'] ?? 0);
            $nursingChg = (float)($data['nursing_charge'] ?? $data['nursig_charge'] ?? 0);
            $dutyDrChg  = (float)($data['doctor_charge'] ?? $data['duty_dr_charge'] ?? 0);
            $serviceChg = (float)($data['service_charge'] ?? 0);
            $roomName   = strtoupper($data['upgraded_room_type'] ?? 'ROOM');
            $dateLabel  = date('d/m/Y', strtotime($chgDate));
            $createdBy  = $data['created_by'] ?? 'system';
            $now        = date('Y-m-d H:i:s');

            if ($bedRent > 0) {
                $this->db->insert('ipd_billing_items', [
                    'bill_id'      => $billId,
                    'patient_id'   => $patientId,
                    'admission_id' => $admissionId,
                    'charge_date'  => $chgDate,
                    'charge_type'  => 'ROOM_RENT',
                    'description'  => "FROM {$dateLabel} TO {$dateLabel} ( ROOM RENT CHARGES-{$roomName} )",
                    'unit_price'   => $bedRent,
                    'total_amount' => $bedRent,
                    'source'       => 'SYSTEM',
                    'status'       => 'COMPLETED',
                    'created_by'   => $createdBy,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            }
            if ($nursingChg > 0) {
                $this->db->insert('ipd_billing_items', [
                    'bill_id'      => $billId,
                    'patient_id'   => $patientId,
                    'admission_id' => $admissionId,
                    'charge_date'  => $chgDate,
                    'charge_type'  => 'PROCEDURE',
                    'description'  => "FROM {$dateLabel} TO {$dateLabel} ( NURSING CHARGES-{$roomName} )",
                    'unit_price'   => $nursingChg,
                    'total_amount' => $nursingChg,
                    'source'       => 'SYSTEM',
                    'status'       => 'COMPLETED',
                    'created_by'   => $createdBy,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            }
            if ($dutyDrChg > 0) {
                $this->db->insert('ipd_billing_items', [
                    'bill_id'      => $billId,
                    'patient_id'   => $patientId,
                    'admission_id' => $admissionId,
                    'charge_date'  => $chgDate,
                    'charge_type'  => 'DOCTOR_VISIT',
                    'description'  => "FROM {$dateLabel} TO {$dateLabel} ( DUTY DOCTOR CHARGES-{$roomName} )",
                    'unit_price'   => $dutyDrChg,
                    'total_amount' => $dutyDrChg,
                    'source'       => 'SYSTEM',
                    'status'       => 'COMPLETED',
                    'created_by'   => $createdBy,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            }
            if ($serviceChg > 0) {
                $this->db->insert('ipd_billing_items', [
                    'bill_id'      => $billId,
                    'patient_id'   => $patientId,
                    'admission_id' => $admissionId,
                    'charge_date'  => $chgDate,
                    'charge_type'  => 'MISC',
                    'description'  => "FROM {$dateLabel} TO {$dateLabel} ( SERVICE CHARGES-{$roomName} )",
                    'unit_price'   => $serviceChg,
                    'total_amount' => $serviceChg,
                    'source'       => 'SYSTEM',
                    'status'       => 'COMPLETED',
                    'created_by'   => $createdBy,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            }

            require_once __DIR__ . '/IpdBillingMaster.php';
            (new IpdBillingMaster())->recalculateMaster($billId, $createdBy);
            return ['success' => true, 'message' => 'Bed upgrade charges updated successfully'];
        }

        // Duplicate check for ROOM_RENT
        if ($data['charge_type'] === 'ROOM_RENT' && !$isUpgrade) {
            $dup = $this->fetchOne(
                "SELECT item_id FROM ipd_billing_items
                 WHERE bill_id = ? AND charge_type = 'ROOM_RENT'
                   AND charge_date = ? AND status != 'CANCELLED'",
                [$billId, $data['charge_date']]
            );
            if ($dup && empty($data['force'])) {
                return [
                    'success'   => false,
                    'duplicate' => true,
                    'message'   => 'Room rent for ' . $data['charge_date'] . ' already exists.',
                ];
            }
        }

        // Duplicate check for one-time Admission Charge and MRD Charge (only once per admission)
        $chargeDesc = trim($data['description'] ?? '');
        if (strcasecmp($chargeDesc, 'Admission Charge') === 0 || strcasecmp($chargeDesc, 'MRD Charge') === 0) {
            $dupOneTime = $this->fetchOne(
                "SELECT item_id FROM ipd_billing_items
                 WHERE bill_id = ? AND description = ? AND status != 'CANCELLED'",
                [$billId, $chargeDesc]
            );
            if ($dupOneTime && empty($data['force'])) {
                return [
                    'success'   => false,
                    'duplicate' => true,
                    'message'   => "{$chargeDesc} has already been added for this admission and cannot be added again.",
                ];
            }
        }

        $qty       = (float)($data['quantity']   ?? 1);
        $unitPrice = (float)($data['unit_price']  ?? 0);
        $discount  = (float)($data['discount_amt'] ?? 0);
        $total     = round(($qty * $unitPrice) - $discount, 2);

        $itemsJson = null;
        if (!empty($data['items_json'])) {
            $itemsJson = is_array($data['items_json']) ? json_encode($data['items_json']) : $data['items_json'];
        }

        $now = date('Y-m-d H:i:s');
        $this->db->insert('ipd_billing_items', [
            'bill_id'         => $billId,
            'patient_id'      => $patientId,
            'admission_id'    => $admissionId,
            'charge_date'     => $data['charge_date'] ?? date('Y-m-d'),
            'charge_type'     => $data['charge_type'],
            'department'      => $data['department']      ?? null,
            'reference_table' => $data['reference_table'] ?? null,
            'reference_id'    => $data['reference_id']    ?? null,
            'description'     => $data['description']     ?? '',
            'quantity'        => $qty,
            'unit_price'      => $unitPrice,
            'discount_amt'    => $discount,
            'bed_rent'        => (float)($data['bed_rent']        ?? 0),
            'nursing_charge'  => (float)($data['nursing_charge']  ?? 0),
            'duty_dr_charge'  => (float)($data['duty_dr_charge']  ?? 0),
            'total_amount'    => $total,
            'items_json'      => $itemsJson,
            'source'          => $data['source'] ?? 'MANUAL',
            'status'          => 'COMPLETED',
            'created_by'      => $data['created_by'] ?? 'system',
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        // Trigger master recalculation
        require_once __DIR__ . '/IpdBillingMaster.php';
        (new IpdBillingMaster())->recalculateMaster($billId, $data['created_by'] ?? 'system');

        return ['success' => true, 'message' => 'Charge added successfully', 'total' => $total];
    }

    /* ───────────────────────────────────────────────────────────────
     * 2. GENERATE ROOM RENT (bulk — one row per day)
     * ─────────────────────────────────────────────────────────────── */
    public function generateRoomRent(
        string $billId,
        string $admissionId,
        string $patientId,
        string $fromDate,
        string $toDate,
        string $createdBy
    ): array {
        // Get bed details from ipd_admissions → hospital_beds, prioritizing updated tariff on admission if set
        $bedInfo = $this->fetchOne(
            "SELECT hb.sl_no, hb.ward_name, hb.room_name, hb.bed_number,
                    COALESCE(NULLIF(ia.room_type, ''), hb.room_type) AS room_type,
                    COALESCE(NULLIF(ia.amount_per_day, 0), hb.amount_per_day) AS amount_per_day,
                    COALESCE(NULLIF(ia.nursig_charge, 0), hb.nursig_charge) AS nursig_charge,
                    COALESCE(NULLIF(ia.doctor_charge, 0), hb.doctor_charge) AS doctor_charge,
                    COALESCE(NULLIF(ia.service_charge, 0), hb.service_charge) AS service_charge,
                    COALESCE(NULLIF(ia.total_bed_amount, 0), hb.total_bed_amount) AS total_bed_amount
             FROM ipd_admissions ia
             JOIN hospital_beds hb ON ia.bed_id = hb.sl_no
             WHERE ia.admission_id = ?",
            [$admissionId]
        );

        if (!$bedInfo) {
            return ['success' => false, 'message' => 'Bed information not found for this admission'];
        }

        $totalBedAmount = (float)$bedInfo['total_bed_amount'];
        $baseBedRent = (float)$bedInfo['amount_per_day'];
        $baseNursing = (float)$bedInfo['nursig_charge'];
        $baseDoctor = (float)$bedInfo['doctor_charge'];
        $baseService = isset($bedInfo['service_charge']) ? (float)$bedInfo['service_charge'] : 0;

        // Check if admission/bill is under Insurance
        $admRow = $this->fetchOne(
            "SELECT ia.admission_type, ia.credit_type, ia.sponsor, bm.bill_type 
             FROM ipd_admissions ia 
             LEFT JOIN ipd_billing_master bm ON ia.admission_id = bm.admission_id 
             WHERE ia.admission_id = ?",
            [$admissionId]
        );
        $isInsurance = false;
        if ($admRow) {
            if (!empty($admRow['admission_type']) && strcasecmp($admRow['admission_type'], 'Insurance') === 0) $isInsurance = true;
            if (!empty($admRow['bill_type']) && strcasecmp($admRow['bill_type'], 'INSURANCE') === 0) $isInsurance = true;
            if (!empty($admRow['credit_type']) && strcasecmp($admRow['credit_type'], 'INSURANCE') === 0) $isInsurance = true;
            if (!empty($admRow['sponsor']) && strcasecmp($admRow['sponsor'], 'SELF') !== 0 && trim($admRow['sponsor']) !== '') $isInsurance = true;
        }
        if (!$isInsurance) {
            $insCheck = $this->fetchOne("SELECT insurance_id FROM ipd_insurance WHERE bill_id = ? AND (approved_amount > 0 OR (company_name IS NOT NULL AND company_name != ''))", [$billId]);
            if ($insCheck) $isInsurance = true;
        }

        // For all patients (insurance or non-insurance): generate separate line items per component per day
        $roomTypeLabel = !empty($bedInfo['room_type']) ? strtoupper($bedInfo['room_type']) : strtoupper($bedInfo['ward_name']);
        $fromLabel     = date('d/m/Y', strtotime($fromDate));
        $toLabel       = date('d/m/Y', strtotime($toDate));

        $addedDates   = [];
        $skippedDates = [];
        $now          = date('Y-m-d H:i:s');

        $current = strtotime($fromDate);
        $end     = strtotime($toDate);

        $days = (int) round(($end - $current) / 86400) + 1;
        if ($days <= 0) return ['success' => true, 'message' => 'No days to generate.'];

        $endDateLabel = date('d/m/Y', $end);

        $processCumulativeCharge = function($chargeType, $descPattern, $descTemplate, $baseRate) use ($billId, $patientId, $admissionId, $createdBy, $now, $days, $endDateLabel, $current) {
            if ($baseRate <= 0) return;
            
            $existing = $this->fetchOne(
                "SELECT * FROM ipd_billing_items WHERE bill_id = ? AND charge_type = ? AND description LIKE ? AND status != 'CANCELLED' ORDER BY item_id DESC LIMIT 1",
                [$billId, $chargeType, $descPattern]
            );
            
            if ($existing) {
                $firstDate = $endDateLabel;
                if (preg_match('/FROM\s+(\d{2}\/\d{2}\/\d{4})\s+TO/', $existing['description'], $matches)) {
                    $firstDate = $matches[1];
                }
                
                $newQty = (int)$existing['quantity'] + $days;
                $unitPrice = (float)$existing['unit_price']; 
                $newTotal = $unitPrice * $newQty;
                $newDesc = str_replace(['{first}', '{end}'], [$firstDate, $endDateLabel], $descTemplate);
                
                $this->db->update('ipd_billing_items', [
                    'quantity' => $newQty,
                    'total_amount' => $newTotal,
                    'description' => $newDesc,
                    'updated_by' => $createdBy,
                    'updated_at' => $now
                ], 'item_id = ?', [$existing['item_id']]);
            } else {
                $firstDate = date('d/m/Y', $current);
                $newDesc = str_replace(['{first}', '{end}'], [$firstDate, $endDateLabel], $descTemplate);
                
                $this->db->insert('ipd_billing_items', [
                    'bill_id'      => $billId,
                    'patient_id'   => $patientId,
                    'admission_id' => $admissionId,
                    'charge_date'  => date('Y-m-d', $current), 
                    'charge_type'  => $chargeType,
                    'description'  => $newDesc,
                    'quantity'     => $days,
                    'unit_price'   => $baseRate,
                    'total_amount' => $baseRate * $days,
                    'status'       => 'COMPLETED',
                    'created_by'   => $createdBy,
                    'created_at'   => $now,
                    'updated_at'   => $now
                ]);
            }
        };

        $processCumulativeCharge('ROOM_RENT', "%ROOM RENT CHARGES-{$roomTypeLabel} )", "FROM {first} TO {end} ( ROOM RENT CHARGES-{$roomTypeLabel} )", $baseBedRent);
        $processCumulativeCharge('NURSING_CHARGE', "%NURSING CHARGES-{$roomTypeLabel} )", "FROM {first} TO {end} ( NURSING CHARGES-{$roomTypeLabel} )", $baseNursing);
        $processCumulativeCharge('DUTY_DOCTOR', "%DUTY DOCTOR CHARGES-{$roomTypeLabel} )", "FROM {first} TO {end} ( DUTY DOCTOR CHARGES-{$roomTypeLabel} )", $baseDoctor);
        $processCumulativeCharge('SERVICE_CHARGE', "%SERVICE CHARGES-{$roomTypeLabel} )", "FROM {first} TO {end} ( SERVICE CHARGES-{$roomTypeLabel} )", $baseService);


        // Recalculate master
        require_once __DIR__ . '/IpdBillingMaster.php';
        $summary = (new IpdBillingMaster())->recalculateMaster($billId, $createdBy);

        return [
            'success'       => true,
            'added'         => count($addedDates),
            'skipped'       => count($skippedDates),
            'added_dates'   => $addedDates,
            'skipped_dates' => $skippedDates,
            'total_added'   => count($addedDates) * $totalPerDay,
            'bed_info'      => [
                'ward'        => $bedInfo['ward_name'],
                'room'        => $bedInfo['room_name'],
                'bed'         => $bedInfo['bed_number'],
                'bed_rent'    => $bedRent,
                'nursing'     => $nursingChg,
                'duty_dr'     => $dutyDrChg,
                'per_day'     => $totalPerDay,
            ],
            'financial'     => $summary,
        ];
    }

    /* ───────────────────────────────────────────────────────────────
     * 3. PREVIEW ROOM RENT (no save — just shows what will be added)
     * ─────────────────────────────────────────────────────────────── */
    public function previewRoomRent(string $billId, string $admissionId, string $fromDate, string $toDate): array {
        $bedInfo = $this->fetchOne(
            "SELECT hb.sl_no, hb.ward_name, hb.room_name, hb.bed_number,
                    COALESCE(NULLIF(ia.room_type, ''), hb.room_type) AS room_type,
                    COALESCE(NULLIF(ia.amount_per_day, 0), hb.amount_per_day) AS amount_per_day,
                    COALESCE(NULLIF(ia.nursig_charge, 0), hb.nursig_charge) AS nursig_charge,
                    COALESCE(NULLIF(ia.doctor_charge, 0), hb.doctor_charge) AS doctor_charge,
                    COALESCE(NULLIF(ia.service_charge, 0), hb.service_charge) AS service_charge,
                    COALESCE(NULLIF(ia.total_bed_amount, 0), hb.total_bed_amount) AS total_bed_amount
             FROM ipd_admissions ia
             JOIN hospital_beds hb ON ia.bed_id = hb.sl_no
             WHERE ia.admission_id = ?",
            [$admissionId]
        );

        if (!$bedInfo) return ['success' => false, 'message' => 'Bed not found'];

        $bedRent    = (float)$bedInfo['amount_per_day'];
        $nursingChg = (float)$bedInfo['nursig_charge'];
        $dutyDrChg  = (float)$bedInfo['doctor_charge'];
        $serviceChg = (float)($bedInfo['service_charge'] ?? 0);
        $totalPerDay = $bedRent + $nursingChg + $dutyDrChg + $serviceChg;

        $rows    = [];
        $current = strtotime($fromDate);
        $end     = strtotime($toDate);

        while ($current <= $end) {
            $dateStr = date('Y-m-d', $current);
            $dup = $this->fetchOne(
                "SELECT item_id FROM ipd_billing_items
                 WHERE bill_id = ? AND charge_type = 'ROOM_RENT' AND charge_date = ? AND status != 'CANCELLED'",
                [$billId, $dateStr]
            );
            $rows[] = [
                'date'           => $dateStr,
                'display_date'   => date('d/m/Y', $current),
                'bed_rent'       => $bedRent,
                'nursing'        => $nursingChg,
                'duty_dr'        => $dutyDrChg,
                'service'        => $serviceChg,
                'total'          => $totalPerDay,
                'already_exists' => (bool)$dup,
            ];
            $current = strtotime('+1 day', $current);
        }

        $newRows = array_filter($rows, fn($r) => !$r['already_exists']);
        return [
            'success'     => true,
            'rows'        => $rows,
            'new_count'   => count($newRows),
            'skip_count'  => count($rows) - count($newRows),
            'new_total'   => count($newRows) * $totalPerDay,
            'per_day'     => $totalPerDay,
            'bed_info'    => [
                'ward'       => $bedInfo['ward_name'],
                'room'       => $bedInfo['room_name'],
                'bed'        => $bedInfo['bed_number'],
                'room_type'  => !empty($bedInfo['room_type']) ? $bedInfo['room_type'] : $bedInfo['ward_name'],
                'bed_rent'   => $bedRent,
                'nursing'    => $nursingChg,
                'duty_dr'    => $dutyDrChg,
                'service'    => $serviceChg,
            ],
        ];
    }

    /* ───────────────────────────────────────────────────────────────
     * 4. CANCEL ITEM  (never delete — set status = CANCELLED)
     * ─────────────────────────────────────────────────────────────── */
    public function cancelItem(int $itemId, string $updatedBy): array {
        $item = $this->fetchOne(
            "SELECT item_id, bill_id, source, charge_type FROM ipd_billing_items WHERE item_id = ?",
            [$itemId]
        );

        if (!$item) return ['success' => false, 'message' => 'Item not found'];
        // Validation removed: Allow cancelling system-generated items (they won't be re-synced)
        $this->db->update('ipd_billing_items',
            ['status' => 'CANCELLED', 'updated_at' => date('Y-m-d H:i:s')],
            '`item_id` = ?', [$itemId]
        );

        require_once __DIR__ . '/IpdBillingMaster.php';
        $summary = (new IpdBillingMaster())->recalculateMaster($item['bill_id'], $updatedBy);

        return ['success' => true, 'message' => 'Charge cancelled', 'financial' => $summary];
    }

    /* ───────────────────────────────────────────────────────────────
     * 5. GET ITEMS BY BILL
     * ─────────────────────────────────────────────────────────────── */
    public function getByBill(string $billId, string $chargeType = ''): array {
        $where  = "WHERE bill_id = ?";
        $params = [$billId];
        if ($chargeType) {
            $where   .= " AND charge_type = ?";
            $params[] = $chargeType;
        }
        return $this->fetchAll(
            "SELECT * FROM ipd_billing_items $where ORDER BY charge_date ASC, created_at ASC",
            $params
        );
    }

    /* ───────────────────────────────────────────────────────────────
     * 6. GET CATEGORY SUMMARY
     * ─────────────────────────────────────────────────────────────── */
    public function getCategorySummary(string $billId): array {
        return $this->fetchAll(
            "SELECT charge_type,
                    COUNT(*)                   AS item_count,
                    SUM(total_amount)          AS category_total
             FROM ipd_billing_items
             WHERE bill_id = ? AND status != 'CANCELLED'
             GROUP BY charge_type
             ORDER BY FIELD(charge_type,'ROOM_RENT','DOCTOR_VISIT','LAB','RADIOLOGY','PHARMACY','OT','PROCEDURE','CONSUMABLE','OTHER')",
            [$billId]
        );
    }
}
