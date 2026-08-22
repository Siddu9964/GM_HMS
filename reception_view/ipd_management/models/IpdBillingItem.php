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
        'CONSUMABLE'   => 'consumable_charges',
        'OTHER'        => 'other_charges',
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

        // Duplicate check for ROOM_RENT
        if ($data['charge_type'] === 'ROOM_RENT') {
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
        // Get bed details from ipd_admissions → hospital_beds
        $bedInfo = $this->fetchOne(
            "SELECT hb.sl_no, hb.ward_name, hb.room_name, hb.bed_number, hb.room_type,
                    hb.amount_per_day, hb.nursig_charge, hb.doctor_charge, hb.service_charge, hb.total_bed_amount
             FROM ipd_admissions ia
             JOIN hospital_beds hb ON ia.bed_id = hb.sl_no
             WHERE ia.admission_id = ?",
            [$admissionId]
        );

        if (!$bedInfo) {
            return ['success' => false, 'message' => 'Bed information not found for this admission'];
        }

        $totalPerDay  = (float)$bedInfo['total_bed_amount'];
        $bedRent      = $totalPerDay;
        $nursingChg   = 0;
        $dutyDrChg    = 0;
        
        $baseBedRent = (float)$bedInfo['amount_per_day'];
        $baseNursing = (float)$bedInfo['nursig_charge'];
        $baseDoctor = (float)$bedInfo['doctor_charge'];
        // Note: service charge is not always in bedInfo here, so we skip it if not queried or assume 0
        $baseService = isset($bedInfo['service_charge']) ? (float)$bedInfo['service_charge'] : 0;
        
        $breakdownText = "Room Rent: ₹" . number_format($baseBedRent, 0) . " | Nursing Charges: ₹" . number_format($baseNursing, 0) . " | Duty Doctor Charges: ₹" . number_format($baseDoctor, 0) . " | Service Charges: ₹" . number_format($baseService, 0);

        $descriptionBase  = "Room Rent – {$bedInfo['ward_name']} – {$bedInfo['bed_number']}";

        $addedDates   = [];
        $skippedDates = [];
        $now          = date('Y-m-d H:i:s');

        $current = strtotime($fromDate);
        $end     = strtotime($toDate);

        while ($current <= $end) {
            $dateStr = date('Y-m-d', $current);

            // Check duplicate
            $dup = $this->fetchOne(
                "SELECT item_id FROM ipd_billing_items
                 WHERE bill_id = ? AND charge_type = 'ROOM_RENT' AND charge_date = ? AND status != 'CANCELLED'",
                [$billId, $dateStr]
            );

            if ($dup) {
                $skippedDates[] = $dateStr;
            } else {
                $this->db->insert('ipd_billing_items', [
                    'bill_id'        => $billId,
                    'patient_id'     => $patientId,
                    'admission_id'   => $admissionId,
                    'charge_date'    => $dateStr,
                    'charge_type'    => 'ROOM_RENT',
                    'department'     => $bedInfo['ward_name'],
                    'description'    => $descriptionBase . ' – ' . date('d-M-Y', $current) . "<br><small style='color: #6c757d; font-size: 0.85em;'>" . $breakdownText . "</small>",
                    'quantity'       => 1,
                    'unit_price'     => $totalPerDay,
                    'discount_amt'   => 0,
                    'bed_rent'       => $bedRent,
                    'nursing_charge' => $nursingChg,
                    'duty_dr_charge' => $dutyDrChg,
                    'total_amount'   => $totalPerDay,
                    'items_json'     => json_encode([
                        'ward'        => $bedInfo['ward_name'],
                        'room'        => $bedInfo['room_name'],
                        'bed'         => $bedInfo['bed_number'],
                        'bed_rent'    => $bedRent,
                        'nursing'     => $nursingChg,
                        'duty_dr'     => $dutyDrChg,
                    ]),
                    'source'      => 'SYSTEM',
                    'status'      => 'COMPLETED',
                    'created_by'  => $createdBy,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
                $addedDates[] = $dateStr;
            }

            $current = strtotime('+1 day', $current);
        }

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
            "SELECT hb.ward_name, hb.room_name, hb.bed_number,
                    hb.amount_per_day, hb.nursig_charge, hb.doctor_charge, hb.total_bed_amount
             FROM ipd_admissions ia
             JOIN hospital_beds hb ON ia.bed_id = hb.sl_no
             WHERE ia.admission_id = ?",
            [$admissionId]
        );

        if (!$bedInfo) return ['success' => false, 'message' => 'Bed not found'];

        $totalPerDay = (float)$bedInfo['total_bed_amount'];
        $bedRent     = $totalPerDay;
        $nursingChg  = 0;
        $dutyDrChg   = 0;

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
                'date'          => $dateStr,
                'display_date'  => date('d-M-Y', $current),
                'bed_rent'      => $bedRent,
                'nursing'       => $nursingChg,
                'duty_dr'       => $dutyDrChg,
                'total'         => $totalPerDay,
                'already_exists'=> (bool)$dup,
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
                'ward'    => $bedInfo['ward_name'],
                'room'    => $bedInfo['room_name'],
                'bed'     => $bedInfo['bed_number'],
                'bed_rent'=> $bedRent,
                'nursing' => $nursingChg,
                'duty_dr' => $dutyDrChg,
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
        if ($item['source'] !== 'MANUAL' && $item['source'] !== 'SYSTEM') {
            return ['success' => false, 'message' => 'System-generated items from external modules cannot be cancelled here'];
        }

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
