<?php
namespace GM_HMS\Models;

/**
 * IpdBillingItem Model
 * Every charge = 1 row. Never deleted — only cancelled.
 *
 * @package IPD_Management\Models
 */


class IpdBillingItem extends IpdBaseModel {
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
        'MISC'         => 'other_charges',
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

        $qty       = (float)($data['quantity']   ?? 1);
        $unitPrice = (float)($data['unit_price']  ?? 0);
        $discount  = (float)($data['discount_amt'] ?? 0);
        $total     = round(($qty * $unitPrice) - $discount, 2);

        $itemsJson = json_encode([
            'quantity'       => $qty,
            'unit_price'     => $unitPrice,
            'discount_amt'   => $discount,
            'bed_rent'       => (float)($data['bed_rent'] ?? 0),
            'nursing_charge' => (float)($data['nursing_charge'] ?? 0),
            'duty_dr_charge' => (float)($data['duty_dr_charge'] ?? 0),
            'source'         => $data['source'] ?? 'MANUAL'
        ]);

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
            'total_amount'    => $total,
            'items_json'      => $itemsJson,
            'status'          => 'COMPLETED',
            'created_by'      => $data['created_by'] ?? 'system',
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        // Synchronize to ipd_clinical_records for K-Sheet visibility (WITHOUT amount)
        try {
            $this->syncToClinicalRecords($patientId, $admissionId, $data, $data['created_by'] ?? 'system');
        } catch (\Throwable $e) {
            error_log("IPD Clinical Sync Error: " . $e->getMessage());
        }

        // Trigger master recalculation
        require_once __DIR__ . '/IpdBillingMaster.php';
        $summary = (new IpdBillingMaster())->recalculateMaster($billId, $data['created_by'] ?? 'system');

        return ['success' => true, 'message' => 'Charge added successfully', 'total' => $total, 'financial' => $summary];
    }

    /**
     * Synchronize added billing charge to ipd_clinical_records (WITHOUT prices/amounts)
     */
    public function syncToClinicalRecords(string $patientId, string $admissionId, array $data, string $createdBy): void {
        $type = strtoupper($data['charge_type'] ?? '');
        $date = $data['charge_date'] ?? date('Y-m-d');
        $desc = trim($data['description'] ?? '');
        $qty  = (float)($data['quantity'] ?? 1);
        $dept = trim($data['department'] ?? '');
        $code = trim($data['item_code'] ?? $data['reference_id'] ?? '');

        $column = null;
        $entry  = null;

        switch ($type) {
            case 'LAB':
            case 'LABORATORY':
                $column = 'lab_tests';
                $entry = [
                    'entry_id'        => uniqid('ent_'),
                    'created_date'    => $date,
                    'date'            => $date,
                    'data'            => [
                        'id'        => $code ?: 'LAB',
                        'test_id'   => $code ?: 'LAB',
                        'name'      => $desc,
                        'test_name' => $desc,
                        'category'  => $dept ?: 'Laboratory',
                        'qty'       => $qty,
                        'quantity'  => $qty
                    ],
                    'created_by_name' => $createdBy,
                    'created_at'      => date('Y-m-d H:i:s')
                ];
                break;

            case 'RADIOLOGY':
                $column = 'radiology_tests';
                $entry = [
                    'entry_id'        => uniqid('ent_'),
                    'created_date'    => $date,
                    'date'            => $date,
                    'data'            => [
                        'id'        => $code ?: 'RAD',
                        'test_id'   => $code ?: 'RAD',
                        'name'      => $desc,
                        'test_name' => $desc,
                        'category'  => $dept ?: 'Radiology',
                        'qty'       => $qty,
                        'quantity'  => $qty
                    ],
                    'created_by_name' => $createdBy,
                    'created_at'      => date('Y-m-d H:i:s')
                ];
                break;

            case 'PHARMACY':
                $column = 'pharmacy_orders';
                $batch  = trim($data['batch_number'] ?? $data['batch'] ?? 'N/A');
                $entry = [
                    'entry_id'        => uniqid('ent_'),
                    'created_date'    => $date,
                    'date'            => $date,
                    'data'            => [
                        'id'            => $code ?: 'PHR',
                        'product_id'    => $code ?: 'PHR',
                        'name'          => $desc,
                        'medicine_name' => $desc,
                        'batch'         => $batch,
                        'batch_no'      => $batch,
                        'qty'           => $qty,
                        'quantity'      => $qty
                    ],
                    'created_by_name' => $createdBy,
                    'created_at'      => date('Y-m-d H:i:s')
                ];
                break;

            case 'DOCTOR_VISIT':
                $column = 'consultant_visits';
                $docName = str_replace(['Dr. ', 'Dr '], '', $data['doctor_name'] ?? $desc);
                $entry = [
                    'entry_id'        => uniqid('ent_'),
                    'date'            => $date,
                    'visit_date'      => $date,
                    'time'            => $data['visit_time'] ?? date('H:i'),
                    'visit_time'      => $data['visit_time'] ?? date('H:i'),
                    'consultant'      => $docName,
                    'doctor_name'     => $docName,
                    'shift'           => $data['shift_type'] ?? 'Morning',
                    'shift_type'      => $data['shift_type'] ?? 'Morning',
                    'remarks'         => $data['notes'] ?? 'Consultant Round',
                    'notes'           => $data['notes'] ?? 'Consultant Round',
                    'created_by_name' => $createdBy,
                    'created_at'      => date('Y-m-d H:i:s')
                ];
                break;

            case 'OT':
            case 'PROCEDURE':
                $column = 'procedures';
                $docName = $data['doctor_name'] ?? $data['person'] ?? 'Attending Doctor';
                $entry = [
                    'entry_id'        => uniqid('ent_'),
                    'date'            => $date,
                    'created_date'    => $date,
                    'procedure'       => $desc,
                    'procedure_name'  => $desc,
                    'name'            => $desc,
                    'doctor'          => $docName,
                    'person'          => $docName,
                    'remarks'         => $data['notes'] ?? $dept,
                    'created_by_name' => $createdBy,
                    'created_at'      => date('Y-m-d H:i:s')
                ];
                break;

            case 'DIALYSIS':
                $column = 'dialysis_chart';
                $docName = $data['doctor_name'] ?? 'Attending Nephrologist / Doctor';
                $entry = [
                    'entry_id'        => uniqid('ent_'),
                    'date'            => $date,
                    'created_date'    => $date,
                    'dia_date'        => $date,
                    'doctor_name'     => $docName,
                    'doctor'          => $docName,
                    'start_time'      => $data['start_time'] ?? '',
                    'dia_start'       => $data['start_time'] ?? '',
                    'end_time'        => $data['end_time'] ?? '',
                    'dia_end'         => $data['end_time'] ?? '',
                    'duration'        => $data['duration'] ?? '4h',
                    'dia_dur'         => $data['duration'] ?? '4h',
                    'dialysis_type'   => $desc,
                    'nurse_sign'      => $createdBy,
                    'dia_nurse'       => $createdBy,
                    'remarks'         => $data['notes'] ?? '',
                    'created_by_name' => $createdBy,
                    'created_at'      => date('Y-m-d H:i:s')
                ];
                break;

            case 'OXYGEN':
                $column = 'oxygen_chart';
                $docName = $data['doctor_name'] ?? 'Attending Doctor';
                $entry = [
                    'entry_id'        => uniqid('ent_'),
                    'date'            => $date,
                    'created_date'    => $date,
                    'oxy_date'        => $date,
                    'doctor_name'     => $docName,
                    'doctor'          => $docName,
                    'flow_rate'       => $data['flow_rate'] ?? '2 L/min',
                    'oxy_flow'        => $data['flow_rate'] ?? '2 L/min',
                    'device'          => $desc,
                    'start_time'      => $data['start_time'] ?? '',
                    'oxy_start'       => $data['start_time'] ?? '',
                    'end_time'        => $data['end_time'] ?? '',
                    'oxy_end'         => $data['end_time'] ?? '',
                    'duration'        => $data['duration'] ?? '2h',
                    'oxy_dur'         => $data['duration'] ?? '2h',
                    'nurse_sign'      => $createdBy,
                    'oxy_nurse'       => $createdBy,
                    'remarks'         => $data['notes'] ?? '',
                    'created_by_name' => $createdBy,
                    'created_at'      => date('Y-m-d H:i:s')
                ];
                break;

            case 'VENTILATION':
            case 'VENTILATOR':
                $column = 'ventilation_chart';
                $docName = $data['doctor_name'] ?? 'Attending Intensivist / Doctor';
                $entry = [
                    'entry_id'        => uniqid('ent_'),
                    'date'            => $date,
                    'created_date'    => $date,
                    'vent_date'       => $date,
                    'doctor_name'     => $docName,
                    'doctor'          => $docName,
                    'mode'            => $data['vent_mode'] ?? 'CPAP',
                    'vent_mode'       => $data['vent_mode'] ?? 'CPAP',
                    'start_time'      => $data['start_time'] ?? '',
                    'vent_start'      => $data['start_time'] ?? '',
                    'end_time'        => $data['end_time'] ?? '',
                    'vent_end'        => $data['end_time'] ?? '',
                    'duration'        => $data['duration'] ?? '6h',
                    'vent_dur'        => $data['duration'] ?? '6h',
                    'nurse_sign'      => $createdBy,
                    'vent_nurse'      => $createdBy,
                    'remarks'         => $data['notes'] ?? '',
                    'created_by_name' => $createdBy,
                    'created_at'      => date('Y-m-d H:i:s')
                ];
                break;

            case 'BLOOD_TRANSFUSION':
                $column = 'blood_transfusion';
                $docName = $data['doctor_name'] ?? 'Prescribing Doctor';
                $entry = [
                    'entry_id'        => uniqid('ent_'),
                    'date'            => $date,
                    'created_date'    => $date,
                    'trans_date'      => $date,
                    'doctor_name'     => $docName,
                    'doctor'          => $docName,
                    'blood_group'     => $data['blood_group'] ?? 'O+',
                    'component'       => $desc,
                    'bag_number'      => $data['bag_number'] ?? '',
                    'quantity'        => $data['trans_qty'] ?? $qty,
                    'vitals_during'   => $data['vitals_during'] ?? '',
                    'nurse_sign'      => $createdBy,
                    'remarks'         => $data['notes'] ?? '',
                    'created_by_name' => $createdBy,
                    'created_at'      => date('Y-m-d H:i:s')
                ];
                break;

            case 'WARD_TRANSFER':
                $column = 'ward_transfer';
                $docName = $data['doctor_name'] ?? 'Authorizing Doctor';
                $entry = [
                    'entry_id'        => uniqid('ent_'),
                    'date'            => $date,
                    'created_date'    => $date,
                    'transfer_date'   => $date,
                    'doctor_name'     => $docName,
                    'doctor'          => $docName,
                    'from_ward'       => $data['from_ward'] ?? '',
                    'to_ward'         => $data['to_ward'] ?? '',
                    'reason'          => $desc,
                    'remarks'         => $data['notes'] ?? '',
                    'created_by_name' => $createdBy,
                    'created_at'      => date('Y-m-d H:i:s')
                ];
                break;

            case 'CONSUMABLE':
            case 'MISC':
            case 'OTHER':
                $column = 'billing_items';
                $entry = [
                    'entry_id'        => uniqid('ent_'),
                    'date'            => $date,
                    'created_date'    => $date,
                    'item_name'       => $desc,
                    'description'     => $desc,
                    'category'        => $dept ?: ucfirst(strtolower($type)),
                    'qty'             => $qty,
                    'quantity'        => $qty,
                    'created_by_name' => $createdBy,
                    'created_at'      => date('Y-m-d H:i:s')
                ];
                break;
        }

        if (!$column || !$entry) {
            return;
        }

        $userId = !empty($_SESSION['user_id']) ? intval($_SESSION['user_id']) : (is_numeric($createdBy) ? intval($createdBy) : 1);
        $createdByName = !empty($_SESSION['username']) ? $_SESSION['username'] : (is_string($createdBy) && !is_numeric($createdBy) ? $createdBy : 'Staff');

        // Fetch existing record or insert new
        $rec = $this->fetchOne(
            "SELECT id, `{$column}` FROM ipd_clinical_records 
             WHERE (patient_id = ? OR (admission_id = ? AND admission_id != '')) 
             ORDER BY record_date DESC, id DESC LIMIT 1",
            [$patientId, $admissionId]
        );

        if ($rec) {
            $existing = json_decode($rec[$column] ?? '[]', true) ?: [];
            if (!is_array($existing)) $existing = [];
            // If associative array instead of indexed list, normalize
            if (!empty($existing) && array_keys($existing) !== range(0, count($existing) - 1)) {
                $existing = [$existing];
            }
            $existing[] = $entry;

            $this->db->update('ipd_clinical_records', [
                $column      => json_encode($existing),
                'updated_by' => $userId,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$rec['id']]);
        } else {
            $this->db->insert('ipd_clinical_records', [
                'patient_id'       => $patientId,
                'admission_id'     => $admissionId,
                'record_date'      => $date,
                'admission_status' => 'Active',
                $column            => json_encode([$entry]),
                'created_by'       => $userId,
                'updated_by'       => $userId,
                'created_at'       => date('Y-m-d H:i:s'),
                'updated_at'       => date('Y-m-d H:i:s')
            ]);
        }
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
                    hb.amount_per_day, hb.nursig_charge, hb.doctor_charge, hb.total_bed_amount, hb.service_charge
             FROM ipd_admissions ia
             JOIN hospital_beds hb ON ia.bed_id = hb.sl_no
             WHERE ia.admission_id = ?",
            [$admissionId]
        );

        if (!$bedInfo) {
            return ['success' => false, 'message' => 'Bed information not found for this admission'];
        }

        $totalBedAmount = (float)$bedInfo['total_bed_amount'];
        $bedRent      = $totalBedAmount;
        $nursingChg   = 0; // Included in total_bed_amount
        $dutyDrChg    = 0; // Included in total_bed_amount
        $serviceChg   = 0; // Included in total_bed_amount
        $foodChg      = 570.00;
        
        $totalPerDay = $bedRent;
        
        $baseBedRent = (float)$bedInfo['amount_per_day'];
        $baseNursing = (float)$bedInfo['nursig_charge'];
        $baseDoctor = (float)$bedInfo['doctor_charge'];
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
                    'total_amount'   => $totalPerDay,
                    'items_json'     => json_encode([
                        ['name' => 'Room Rent', 'qty' => 1, 'price' => $bedRent, 'total' => $bedRent]
                    ]),
                    'status'      => 'COMPLETED',
                    'created_by'  => $createdBy,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);

                // Also add food charge under MISC if not already present
                if ($foodChg > 0) {
                    $dupFood = $this->fetchOne(
                        "SELECT item_id FROM ipd_billing_items 
                         WHERE bill_id = ? AND charge_type = 'MISC' AND charge_date = ? AND description LIKE 'Food Charge%' AND status != 'CANCELLED'",
                        [$billId, $dateStr]
                    );
                    if (!$dupFood) {
                        $this->db->insert('ipd_billing_items', [
                            'bill_id'     => $billId,
                            'patient_id'  => $patientId,
                            'admission_id'=> $admissionId,
                            'charge_date' => $dateStr,
                            'charge_type' => 'MISC',
                            'description' => 'Food Charge – ' . date('d-M-Y', $current),
                            'total_amount'=> $foodChg,
                            'status'      => 'COMPLETED',
                            'created_by'  => $createdBy,
                            'created_at'  => $now,
                            'updated_at'  => $now,
                        ]);
                    }
                }

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
                'bed_rent'    => $baseBedRent,
                'nursing'     => $baseNursing,
                'duty_dr'     => $baseDoctor,
                'service'     => $baseService,
                'food'        => $foodChg,
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
                    hb.amount_per_day, hb.nursig_charge, hb.doctor_charge, hb.total_bed_amount, hb.service_charge
             FROM ipd_admissions ia
             JOIN hospital_beds hb ON ia.bed_id = hb.sl_no
             WHERE ia.admission_id = ?",
            [$admissionId]
        );

        if (!$bedInfo) return ['success' => false, 'message' => 'Bed not found'];

        $totalBedAmount = (float)$bedInfo['total_bed_amount'];
        $baseBedRent = (float)$bedInfo['amount_per_day'];
        $baseNursing = (float)$bedInfo['nursig_charge'];
        $baseDoctor = (float)$bedInfo['doctor_charge'];
        $baseService = isset($bedInfo['service_charge']) ? (float)$bedInfo['service_charge'] : 0;
        $foodChg     = 570.00;
        
        $totalPerDay = $totalBedAmount;

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
                'bed_rent'      => $baseBedRent,
                'nursing'       => $baseNursing,
                'duty_dr'       => $baseDoctor,
                'service'       => $baseService,
                'food'          => $foodChg,
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
                'service' => $serviceChg,
                'food'    => $foodChg,
            ],
        ];
    }

    /* ───────────────────────────────────────────────────────────────
     * 4. CANCEL ITEM  (never delete — set status = CANCELLED)
     * ─────────────────────────────────────────────────────────────── */
    public function cancelItem(int $itemId, string $updatedBy): array {
        $item = $this->fetchOne(
            "SELECT item_id, bill_id, items_json, charge_type FROM ipd_billing_items WHERE item_id = ?",
            [$itemId]
        );

        if (!$item) return ['success' => false, 'message' => 'Item not found'];
        
        $meta = json_decode($item['items_json'] ?? '{}', true);
        $source = (is_array($meta) && isset($meta['source'])) ? $meta['source'] : '';
        
        if ($source !== '' && $source !== 'MANUAL' && $source !== 'SYSTEM') {
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
     * 4.5. UPDATE TOTAL
     * ─────────────────────────────────────────────────────────────── */
    public function updateTotal(int $itemId, float $newTotal, string $updatedBy): array {
        $item = $this->fetchOne(
            "SELECT item_id, bill_id, items_json FROM ipd_billing_items WHERE item_id = ?",
            [$itemId]
        );

        if (!$item) return ['success' => false, 'message' => 'Item not found'];
        
        $meta = json_decode($item['items_json'] ?? '{}', true);
        $qty = (float)($meta['quantity'] ?? 1);
        if ($qty <= 0) $qty = 1;

        $newUnitPrice = round($newTotal / $qty, 2);
        
        $meta['unit_price'] = $newUnitPrice;
        $meta['discount_amt'] = 0;

        $this->db->update('ipd_billing_items',
            [
                'total_amount' => $newTotal, 
                'items_json' => json_encode($meta),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            '`item_id` = ?', [$itemId]
        );

        require_once __DIR__ . '/IpdBillingMaster.php';
        $summary = (new IpdBillingMaster())->recalculateMaster($item['bill_id'], $updatedBy);

        return ['success' => true, 'message' => 'Total updated', 'financial' => $summary];
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
        $items = $this->fetchAll(
            "SELECT * FROM ipd_billing_items $where ORDER BY charge_date ASC, created_at ASC",
            $params
        );
        
        foreach ($items as &$item) {
            if (!empty($item['items_json'])) {
                $meta = json_decode($item['items_json'], true) ?: [];
                foreach ($meta as $k => $v) {
                    if (!isset($item[$k])) {
                        $item[$k] = $v;
                    }
                }
            }
        }
        return $items;
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
