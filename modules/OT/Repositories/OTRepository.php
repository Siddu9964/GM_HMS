<?php
namespace GM_HMS\Modules\OT\Repositories;

use GM_HMS\Database\SecureDatabase;

class OTRepository
{
    private $db;

    public function __construct()
    {
        $this->db = SecureDatabase::getInstance();
    }

    /**
     * Auto-update surgery statuses based on current date & time.
     *
     * Rules (only touches Scheduled / Ongoing — never Cancelled / Postponed / Preponed):
     *
     * 1. Past date  + Scheduled                     → Completed
     * 2. Today      + start_time reached + Scheduled → Ongoing
     * 3. Today      + end_time   passed  + (Scheduled|Ongoing) → Completed
     *
     * Called silently before every data-fetch so the UI always reflects reality.
     */
    private function autoUpdateStatuses(): void
    {
        $today = date('Y-m-d');
        $nowTime = date('H:i:s');

        // 1. Today (or past date), start_time reached → Ongoing
        // Only if it's currently Scheduled.
        $this->db->execute(
            "UPDATE ot_surgeries
             SET    status = 'Ongoing'
             WHERE  status = 'Scheduled'
               AND  (
                   schedule_date < ? OR 
                   (schedule_date = ? AND start_time <= ?)
               )",
            [$today, $today, $nowTime]
        );

        // 2. Passed end_time (today or past date) AND end_time IS NOT NULL → Completed
        // A surgery will NEVER auto-complete if end_time is NULL. It will stay Ongoing.
        $this->db->execute(
            "UPDATE ot_surgeries
             SET    status = 'Completed'
             WHERE  status IN ('Scheduled', 'Ongoing')
               AND  end_time IS NOT NULL
               AND  (
                   schedule_date < ? OR 
                   (schedule_date = ? AND end_time <= ?)
               )",
            [$today, $today, $nowTime]
        );
    }

    /**
     * Get all OT surgeries with optional filters
     */
    public function getAll($date = null, $status = '', $search = '')
    {
        $this->autoUpdateStatuses();  // keep statuses fresh on every fetch
        $sql = "SELECT * FROM ot_surgeries WHERE 1=1";
        $params = [];

        if ($date) {
            $sql .= " AND schedule_date = ?";
            $params[] = $date;
        }

        if ($status !== '') {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        if ($search !== '') {
            $sql .= " AND (patient_id LIKE ? OR patient_name LIKE ? OR surgery_name LIKE ? OR ot_room_name LIKE ?)";
            $s = "%$search%";
            array_push($params, $s, $s, $s, $s);
        }

        $sql .= " ORDER BY schedule_date DESC, start_time ASC";
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get a single surgery by ID
     */
    public function getById($id)
    {
        return $this->db->fetchOne(
            "SELECT * FROM ot_surgeries WHERE id = ?",
            [$id]
        );
    }

    /**
     * Get distinct doctor roles from the JSON type column
     */
    public function getDistinctDoctorRoles()
    {
        $rows = $this->db->fetchAll("SELECT type FROM ot_surgeries WHERE type IS NOT NULL AND type != ''");
        $roles = [];
        foreach ($rows as $row) {
            try {
                $decoded = json_decode($row['type'], true);
                if (is_array($decoded)) {
                    foreach ($decoded as $role) {
                        $role = trim($role);
                        if (!empty($role) && !in_array($role, $roles)) {
                            $roles[] = $role;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Ignore invalid JSON
            }
        }
        return $roles;
    }

    /**
     * Check if an OT room is available for a given date and time range
     * Returns the conflicting surgery if found, else null.
     */
    public function checkRoomAvailability($otRoomName, $date, $startTime, $endTime, $excludeId = null)
    {
        $sql = "SELECT * FROM ot_surgeries 
                WHERE ot_room_name = ? 
                AND schedule_date = ? 
                AND status NOT IN ('Cancelled', 'Postponed')";
        $params = [$otRoomName, $date];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        // Time overlap logic: (NewStart < ExistingEnd) AND (NewEnd > ExistingStart)
        // 1. If 'Ongoing', blocks indefinitely (23:59:59).
        // 2. If 'Completed' but legacy bad data has no end_time, don't block the future.
        // 3. Otherwise, use scheduled end_time. If no end_time exists, assume rest of day.
        $newEnd = $endTime ? $endTime : '23:59:59';

        $sql .= " AND (
                    ? < CASE 
                          WHEN status = 'Ongoing' THEN '23:59:59' 
                          WHEN status = 'Completed' AND end_time IS NULL THEN start_time
                          ELSE IFNULL(end_time, '23:59:59') 
                        END 
                    AND ? > start_time
                  ) LIMIT 1";
        array_push($params, $startTime, $newEnd);

        $conflicts = $this->db->fetchAll($sql, $params);
        return count($conflicts) > 0 ? $conflicts[0] : null;
    }

    /**
     * Create a new surgery record
     */
    public function create($data)
    {
        $res = $this->db->execute(
            "INSERT INTO ot_surgeries 
            (patient_id, patient_name, type, name, ot_room_name, surgery_name, department, anesthesia_type, description, schedule_date, start_time, end_time, status, status_reason_note)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['patient_id']           ?? '',
                $data['patient_name']          ?? '',
                $data['type']                  ?? '',
                $data['name']                  ?? '',
                $data['ot_room_name']          ?? '',
                $data['surgery_name']          ?? '',
                $data['department']            ?? null,
                $data['anesthesia_type']       ?? null,
                $data['description']           ?? null,
                $data['schedule_date']         ?? date('Y-m-d'),
                $data['start_time']            ?? '00:00:00',
                $data['end_time']              ?? null,
                $data['status']                ?? 'Scheduled',
                $data['status_reason_note']    ?? null,
            ]
        );
        if ($res && isset($res['insert_id'])) {
            return $this->getById($res['insert_id']);
        }
        return $res;
    }

    /**
     * Update an existing surgery
     */
    public function update($id, $data)
    {
        return $this->db->execute(
            "UPDATE ot_surgeries SET
                patient_id         = ?,
                patient_name       = ?,
                type               = ?,
                name               = ?,
                ot_room_name       = ?,
                surgery_name       = ?,
                department         = ?,
                anesthesia_type    = ?,
                description        = ?,
                schedule_date      = ?,
                start_time         = ?,
                end_time           = ?,
                status             = ?,
                status_reason_note = ?
            WHERE id = ?",
            [
                $data['patient_id']           ?? '',
                $data['patient_name']          ?? '',
                $data['type']                  ?? '',
                $data['name']                  ?? '',
                $data['ot_room_name']          ?? '',
                $data['surgery_name']          ?? '',
                $data['department']            ?? null,
                $data['anesthesia_type']       ?? null,
                $data['description']           ?? null,
                $data['schedule_date']         ?? date('Y-m-d'),
                $data['start_time']            ?? '00:00:00',
                $data['end_time']              ?? null,
                $data['status']                ?? 'Scheduled',
                $data['status_reason_note']    ?? null,
                $id,
            ]
        );
    }

    /**
     * Update only the status and reason note of a surgery
     */
    public function updateStatus($id, $status, $reason = null)
    {
        return $this->db->execute(
            "UPDATE ot_surgeries SET status = ?, status_reason_note = ? WHERE id = ?",
            [$status, $reason, $id]
        );
    }

    /**
     * Update start and end time of a surgery
     */
    public function updateTime($id, $startTime, $endTime)
    {
        return $this->db->execute(
            "UPDATE ot_surgeries SET start_time = ?, end_time = ? WHERE id = ?",
            [$startTime, $endTime, $id]
        );
    }

    /**
     * Delete a surgery record
     */
    public function delete($id)
    {
        return $this->db->execute("DELETE FROM ot_surgeries WHERE id = ?", [$id]);
    }

    /**
     * Get OT rooms from existing hospital_beds table
     */
    public function getOTRooms()
    {
        return $this->db->fetchAll(
            "SELECT DISTINCT ward_name, room_name, room_type, bed_status 
             FROM hospital_beds 
             WHERE room_type = 'OT'
             ORDER BY room_name"
        );
    }

    /**
     * Get dashboard stats for today
     */
    public function getDashboardStats()
    {
        $this->autoUpdateStatuses();  // ensure counts reflect current time
        $today = date('Y-m-d');

        $scheduledToday = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM ot_surgeries WHERE schedule_date = ? AND status = 'Scheduled'",
            [$today]
        );
        $ongoingToday = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM ot_surgeries WHERE schedule_date = ? AND status = 'Ongoing'",
            [$today]
        );
        $completedToday = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM ot_surgeries WHERE schedule_date = ? AND status = 'Completed'",
            [$today]
        );
        $cancelledToday = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM ot_surgeries WHERE schedule_date = ? AND status IN ('Cancelled','Postponed')",
            [$today]
        );
        $monthTotal = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM ot_surgeries 
             WHERE MONTH(schedule_date) = MONTH(CURDATE()) AND YEAR(schedule_date) = YEAR(CURDATE())"
        );
        $recent = $this->db->fetchAll(
            "SELECT * FROM ot_surgeries ORDER BY created_at DESC LIMIT 5"
        );

        return [
            'scheduled_today'  => (int)($scheduledToday['cnt']  ?? 0),
            'ongoing_today'    => (int)($ongoingToday['cnt']    ?? 0),
            'completed_today'  => (int)($completedToday['cnt']  ?? 0),
            'cancelled_today'  => (int)($cancelledToday['cnt']  ?? 0),
            'month_total'      => (int)($monthTotal['cnt']       ?? 0),
            'recent_surgeries' => $recent,
        ];
    }
    /**
     * Search patient ID in ipd_admissions, then fallback to patient table
     */
    public function searchPatient($patientId)
    {
        // Check ipd_admissions first
        $ipdSql = "
            SELECT a.patient_id, TRIM(CONCAT(IFNULL(p.first_name, ''), ' ', IFNULL(p.last_name, ''))) AS patient_name, a.admission_id 
            FROM ipd_admissions a
            JOIN patient p ON a.patient_id = p.patient_id
            WHERE a.patient_id = ? AND a.status = 'Admitted'
        ";
        $ipdData = $this->db->fetchOne($ipdSql, [$patientId]);

        if ($ipdData) {
            return [
                'found' => true,
                'source' => 'ipd',
                'patient_id' => $ipdData['patient_id'],
                'patient_name' => $ipdData['patient_name'],
                'admission_id' => $ipdData['admission_id']
            ];
        }

        // Fallback to patient table
        $patientSql = "
            SELECT patient_id, TRIM(CONCAT(IFNULL(first_name, ''), ' ', IFNULL(last_name, ''))) AS patient_name 
            FROM patient 
            WHERE patient_id = ?
        ";
        $patientData = $this->db->fetchOne($patientSql, [$patientId]);

        if ($patientData) {
            return [
                'found' => true,
                'source' => 'opd',
                'patient_id' => $patientData['patient_id'],
                'patient_name' => $patientData['patient_name']
            ];
        }

        return ['found' => false];
    }

    /**
     * Search patient for OT/IPD dropdown lists
     */
    public function searchPatientsList($query)
    {
        $like = '%' . $query . '%';
        $sql = "SELECT 
                    p.patient_id,
                    TRIM(CONCAT(p.first_name, ' ', IFNULL(p.last_name, ''))) AS patient_name,
                    p.phone,
                    p.age,
                    p.sex,
                    p.blood_group,
                    ia.admission_id,
                    ia.admission_date
                FROM patient p
                LEFT JOIN ipd_admissions ia ON BINARY p.patient_id = BINARY ia.patient_id AND ia.status = 'Admitted'
                WHERE p.patient_id LIKE ? 
                   OR p.phone LIKE ? 
                   OR TRIM(CONCAT(p.first_name, ' ', IFNULL(p.last_name, ''))) LIKE ?
                ORDER BY (ia.admission_id IS NOT NULL) DESC, p.patient_id ASC
                LIMIT 20";
        $results = $this->db->fetchAll($sql, [$like, $like, $like]);
        
        foreach ($results as &$r) {
            $r['ipd_status'] = !empty($r['admission_id']) ? 'Admitted' : 'OPD/Discharged';
        }
        return $results;
    }
    
    /**
     * Search pharmacy products by name
     */
    public function searchPharmacyProducts($query)
    {
        $sql = "SELECT product_id as id, product_name as name, COALESCE(sales_price, mrp, 0.00) as price, quantity as stock, mrp 
                FROM ph_product 
                WHERE product_name LIKE ? AND is_active = 1
                ORDER BY CASE WHEN product_name LIKE ? THEN 0 ELSE 1 END, product_name ASC 
                LIMIT 30";
        return $this->db->fetchAll($sql, ["%{$query}%", "{$query}%"]);
    }

    public function createPharmacyOrder($patientId, $admissionId, $items, $userId, $source = 'OT')
    {
        $recordDate = date('Y-m-d');
        
        // 1. Check if patient ID and record_date already exist
        if (!empty($admissionId)) {
            $record = $this->db->fetchOne(
                "SELECT id, pharmacy_orders FROM ipd_clinical_records WHERE patient_id = ? AND admission_id = ? AND record_date = ?",
                [$patientId, $admissionId, $recordDate]
            );
        } else {
            $record = $this->db->fetchOne(
                "SELECT id, pharmacy_orders FROM ipd_clinical_records WHERE patient_id = ? AND record_date = ?",
                [$patientId, $recordDate]
            );
        }

        $orders = [];
        $recordId = null;
        
        if ($record) {
            $recordId = $record['id'];
            $orders = json_decode($record['pharmacy_orders'] ?? '[]', true) ?? [];
        }

        // 2. Append new items and deduct stock
        foreach ($items as $item) {
            $orders[] = [
                'unique_id' => uniqid('med_'),
                'status' => 'Pending',
                'charge_type' => 'PHARMACY',
                'data' => [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'qty' => $item['qty'],
                    'mrp' => $item['mrp'],
                    'pending_qty' => $item['qty'],
                    'used_qty' => 0,
                    'returned_qty' => 0
                ],
                'created_by' => $userId,
                'ordered_at' => date('Y-m-d H:i:s'),
                'source' => $source
            ];
            
            // Deduct stock from pharmacy (can go negative as requested)
            $this->db->execute(
                "UPDATE ph_product SET quantity = quantity - ? WHERE product_id = ?",
                [$item['qty'], $item['id']]
            );
        }

        $ordersJson = json_encode($orders);

        // 3. Update or Insert
        if ($recordId) {
            $this->db->execute(
                "UPDATE ipd_clinical_records SET pharmacy_orders = ? WHERE id = ?",
                [$ordersJson, $recordId]
            );
            return ['id' => $recordId, 'status' => 'updated'];
        } else {
            $res = $this->db->execute(
                "INSERT INTO ipd_clinical_records (patient_id, admission_id, record_date, pharmacy_orders, created_by) 
                 VALUES (?, ?, ?, ?, ?)",
                [$patientId, $admissionId ?: null, $recordDate, $ordersJson, $userId]
            );
            return ['id' => $res['insert_id'] ?? null, 'status' => 'inserted'];
        }
    }

    public function getPendingPharmacyOrders($patientId, $admissionId)
    {
        if (!empty($admissionId)) {
            $records = $this->db->fetchAll(
                "SELECT pharmacy_orders FROM ipd_clinical_records WHERE patient_id = ? AND admission_id = ?",
                [$patientId, $admissionId]
            );
        } else {
            $records = $this->db->fetchAll(
                "SELECT pharmacy_orders FROM ipd_clinical_records WHERE patient_id = ?",
                [$patientId]
            );
        }

        $pending = [];
        
        foreach ($records as $record) {
            if (empty($record['pharmacy_orders'])) continue;
            
            $orders = json_decode($record['pharmacy_orders'] ?: '[]', true) ?? [];
            foreach ($orders as $order) {
                if (($order['source'] ?? '') !== 'OT') continue;
                $status = $order['status'] ?? 'Pending';
                if (in_array($status, ['Cancelled', 'Rejected'])) continue;
                
                $qty = (int)($order['data']['qty'] ?? 0);
                $used = (int)($order['data']['used_qty'] ?? 0);
                $returned = (int)($order['data']['returned_qty'] ?? 0);
                $pendingQty = $qty - $used - $returned;
                
                if ($pendingQty > 0) {
                    $pending[] = [
                        'unique_id' => $order['unique_id'] ?? null,
                        'id' => $order['data']['id'],
                        'name' => $order['data']['name'] ?? 'Unknown',
                        'ordered_qty' => $qty,
                        'pending_qty' => $pendingQty,
                        'used_qty_so_far' => $used,
                        'returned_qty_so_far' => $returned,
                        'mrp' => $order['data']['mrp'] ?? 0
                    ];
                }
            }
        }
        
        return $pending;
    }

    public function editPharmacyOrderItem($patientId, $admissionId, $uniqueId, $newQty, $userId)
    {
        if (!empty($admissionId)) {
            $records = $this->db->fetchAll("SELECT id, pharmacy_orders FROM ipd_clinical_records WHERE patient_id = ? AND admission_id = ?", [$patientId, $admissionId]);
        } else {
            $records = $this->db->fetchAll("SELECT id, pharmacy_orders FROM ipd_clinical_records WHERE patient_id = ?", [$patientId]);
        }

        $found = false;
        
        foreach ($records as $record) {
            if (empty($record['pharmacy_orders'])) continue;
            $orders = json_decode($record['pharmacy_orders'] ?: '[]', true) ?? [];
            
            foreach ($orders as &$o) {
                if (isset($o['unique_id']) && $o['unique_id'] === $uniqueId) {
                    if ($o['status'] !== 'Pending') {
                        throw new \Exception("Cannot edit item. It is already being processed.");
                    }
                    
                    $oldQty = (int)$o['data']['qty'];
                    $newQty = (int)$newQty;
                    if ($newQty <= 0) {
                        throw new \Exception("Quantity must be greater than zero.");
                    }
                    
                    $diff = $newQty - $oldQty;
                    
                    // Deduct or refund stock
                    if ($diff != 0) {
                        $this->db->execute(
                            "UPDATE ph_product SET quantity = quantity - ? WHERE product_id = ?",
                            [$diff, $o['data']['id']]
                        );
                    }
                    
                    $o['data']['qty'] = $newQty;
                    $o['data']['pending_qty'] = $newQty; 
                    
                    $found = true;
                    
                    // Save this record
                    $this->db->execute(
                        "UPDATE ipd_clinical_records SET pharmacy_orders = ? WHERE id = ?",
                        [json_encode($orders), $record['id']]
                    );
                    break;
                }
            }
            if ($found) break;
        }
        
        if (!$found) throw new \Exception("Item not found in order.");
        
        return true;
    }

    public function deletePharmacyOrderItem($patientId, $admissionId, $uniqueId, $userId)
    {
        if (!empty($admissionId)) {
            $records = $this->db->fetchAll("SELECT id, pharmacy_orders FROM ipd_clinical_records WHERE patient_id = ? AND admission_id = ?", [$patientId, $admissionId]);
        } else {
            $records = $this->db->fetchAll("SELECT id, pharmacy_orders FROM ipd_clinical_records WHERE patient_id = ?", [$patientId]);
        }

        $found = false;
        
        foreach ($records as $record) {
            if (empty($record['pharmacy_orders'])) continue;
            $orders = json_decode($record['pharmacy_orders'] ?: '[]', true) ?? [];
            $foundIndex = -1;
            
            foreach ($orders as $index => $o) {
                if (isset($o['unique_id']) && $o['unique_id'] === $uniqueId) {
                    if ($o['status'] !== 'Pending') {
                        throw new \Exception("Cannot delete item. It is already being processed.");
                    }
                    $foundIndex = $index;
                    $found = true;
                    break;
                }
            }
            
            if ($foundIndex !== -1) {
                $item = $orders[$foundIndex];
                
                // Refund full quantity to stock
                $this->db->execute(
                    "UPDATE ph_product SET quantity = quantity + ? WHERE product_id = ?",
                    [$item['data']['qty'], $item['data']['id']]
                );
                
                // Remove item from array
                array_splice($orders, $foundIndex, 1);
                
                $this->db->execute(
                    "UPDATE ipd_clinical_records SET pharmacy_orders = ? WHERE id = ?",
                    [json_encode(array_values($orders)), $record['id']]
                );
                break;
            }
        }
        
        if (!$found) throw new \Exception("Item not found in order.");
        
        return true;
    }

    public function reconcilePharmacyOrder($patientId, $admissionId, $items, $userId)
    {
        if (!empty($admissionId)) {
            $records = $this->db->fetchAll("SELECT id, pharmacy_orders FROM ipd_clinical_records WHERE patient_id = ? AND admission_id = ?", [$patientId, $admissionId]);
        } else {
            $records = $this->db->fetchAll("SELECT id, pharmacy_orders FROM ipd_clinical_records WHERE patient_id = ?", [$patientId]);
        }

        if (empty($records)) {
            throw new \Exception("No clinical records found.");
        }

        $billId = null;
        if (!empty($admissionId)) {
            $billingMaster = $this->db->fetchOne("SELECT bill_id FROM ipd_billing_master WHERE admission_id = ?", [$admissionId]);
            if ($billingMaster) {
                $billId = $billingMaster['bill_id'];
            }
        }

        $totalBilledAmount = 0;
        
        // Track which records were modified
        $modifiedRecords = [];
        $decodedOrdersByRecordId = [];
        
        foreach ($records as $record) {
            $jsonStr = $record['pharmacy_orders'] ?: '[]';
            $decodedOrdersByRecordId[$record['id']] = json_decode($jsonStr, true) ?? [];
        }

        foreach ($items as $uiItem) {
            $medId = $uiItem['id'];
            $uniqueId = $uiItem['unique_id'] ?? null;
            $uiUsed = (int) ($uiItem['used_qty'] ?? 0);
            $uiReturned = (int) ($uiItem['returned_qty'] ?? 0);
            $mrp = (float) ($uiItem['mrp'] ?? 0);
            
            if ($uiUsed == 0 && $uiReturned == 0) continue; // Nothing to process
            
            // Search across all records to find the matching item
            $matchFound = false;
            foreach ($decodedOrdersByRecordId as $recordId => &$orders) {
                foreach ($orders as &$o) {
                    $oUniqueId = $o['unique_id'] ?? null;
                    $oId = $o['data']['id'];
                    $oQty = (int)($o['data']['qty'] ?? 0);
                    $oUsed = (int)($o['data']['used_qty'] ?? 0);
                    $oReturned = (int)($o['data']['returned_qty'] ?? 0);
                    $oPending = $o['data']['pending_qty'] ?? ($oQty - $oUsed - $oReturned);
                    
                    $match = false;
                    if ($uniqueId && $oUniqueId) {
                        $match = ($uniqueId === $oUniqueId);
                    } else {
                        $match = ($oId == $medId && $oPending > 0);
                    }
                    
                    if ($match && $oPending > 0) {
                        if ($uiUsed + $uiReturned > $oPending) {
                            throw new \Exception("Cannot process more than pending quantity for {$o['data']['name']}.");
                        }
                        
                        if ($uiReturned > 0) {
                            $returnAmount = $uiReturned * $mrp;
                            $this->db->execute(
                                "INSERT INTO ipd_pharmacy_return_requests 
                                (patient_id, admission_id, bill_id, item_id, medicine_name, original_qty, return_qty, return_amount, status, requested_by, requested_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'PENDING', ?, NOW())",
                                [$patientId, $admissionId ?: '', $billId ?: null, $medId, $uiItem['name'], $o['data']['qty'], $uiReturned, $returnAmount, $userId]
                            );
                            $o['data']['returned_qty'] = ($o['data']['returned_qty'] ?? 0) + $uiReturned;
                        }
                        
                        if ($uiUsed > 0 && $billId) {
                            $itemTotal = $uiUsed * $mrp;
                            $totalBilledAmount += $itemTotal;
                            $description = $uiItem['name'] . " (" . $uiUsed . " @ " . $mrp . ")";
                            
                            $this->db->execute(
                                "INSERT INTO ipd_billing_items (bill_id, patient_id, admission_id, charge_date, charge_type, description, amount, quantity, unit_price, total_amount, status, created_by)
                                 VALUES (?, ?, ?, NOW(), 'PHARMACY', ?, ?, ?, ?, ?, 'COMPLETED', ?)",
                                [$billId, $patientId, $admissionId, $description, $itemTotal, $uiUsed, $mrp, $itemTotal, $userId]
                            );
                            $o['data']['used_qty'] = ($o['data']['used_qty'] ?? 0) + $uiUsed;
                        }
                        
                        $o['data']['pending_qty'] = $oPending - $uiUsed - $uiReturned;
                        if ($o['data']['pending_qty'] <= 0) {
                            $o['status'] = 'Completed';
                        } else {
                            $o['status'] = 'Partial';
                        }
                        
                        $modifiedRecords[$recordId] = true;
                        $matchFound = true;
                        break;
                    }
                }
                if ($matchFound) break;
            }
            
            if (!$matchFound) {
                throw new \Exception("Item not found or no pending quantity for: {$uiItem['name']}");
            }
        }
        
        // Save modified records back to DB
        foreach ($modifiedRecords as $recordId => $val) {
            $ordersJson = json_encode(array_values($decodedOrdersByRecordId[$recordId]));
            $this->db->execute("UPDATE ipd_clinical_records SET pharmacy_orders = ? WHERE id = ?", [$ordersJson, $recordId]);
        }
        
        if ($totalBilledAmount > 0 && $billId) {
            try {
                $billingModel = new \GM_HMS\Models\IpdBillingMaster();
                $billingModel->recalculateMaster($billId, $userId);
            } catch (\Exception $e) {
                // Ignore recalculation errors
            }
        }

        return ['status' => 'success', 'billed_amount' => $totalBilledAmount];
    }

    public function getAllPharmacyOrders()
    {
        $records = $this->db->fetchAll(
            "SELECT c.patient_id, p.first_name, p.last_name, c.admission_id, c.record_date, c.pharmacy_orders 
             FROM ipd_clinical_records c 
             JOIN patient p ON BINARY c.patient_id = BINARY p.patient_id 
             WHERE c.pharmacy_orders IS NOT NULL AND c.pharmacy_orders != '' AND c.pharmacy_orders != '[]'
             ORDER BY c.record_date DESC"
        );

        $groupedOrders = [];
        foreach ($records as $record) {
            $orders = json_decode($record['pharmacy_orders'] ?: '[]', true) ?? [];
            foreach ($orders as $order) {
                if (isset($order['source']) && $order['source'] === 'OT') {
                    $orderedAt = $order['ordered_at'] ?? $record['record_date'];
                    $patientId = $record['patient_id'];
                    // Group by patient and date, not the exact second, so added medicines merge into the same row
                    $groupKey = $patientId . '_' . $record['record_date'];
                    
                    if (!isset($groupedOrders[$groupKey])) {
                        $groupedOrders[$groupKey] = [
                            'patient_id' => $patientId,
                            'patient_name' => trim($record['first_name'] . ' ' . ($record['last_name'] ?? '')),
                            'admission_id' => $record['admission_id'],
                            'ordered_at' => $orderedAt, // Use the time of the first item

                            'status' => 'Pending',
                            'total_items' => 0,
                            'total_ordered_qty' => 0,
                            'total_used_qty' => 0,
                            'total_returned_qty' => 0,
                            'items' => []
                        ];
                    }
                    
                    $qty = (int)($order['data']['qty'] ?? 0);
                    $used = (int)($order['data']['used_qty'] ?? 0);
                    $returned = (int)($order['data']['returned_qty'] ?? 0);

                    $groupedOrders[$groupKey]['items'][] = [
                        'unique_id' => $order['unique_id'] ?? null,
                        'medicine_name' => $order['data']['name'] ?? 'Unknown',
                        'ordered_qty' => $qty,
                        'used_qty' => $used,
                        'returned_qty' => $returned,
                        'mrp' => $order['data']['mrp'] ?? 0,
                        'status' => $order['status'] ?? 'Pending'
                    ];
                    $groupedOrders[$groupKey]['total_items']++;
                    $groupedOrders[$groupKey]['total_ordered_qty'] += $qty;
                    $groupedOrders[$groupKey]['total_used_qty'] += $used;
                    $groupedOrders[$groupKey]['total_returned_qty'] += $returned;
                }
            }
        }

        foreach ($groupedOrders as &$group) {
            $allCompleted = true;
            $anyCompleted = false;
            foreach ($group['items'] as $item) {
                if ($item['status'] === 'Completed') {
                    $anyCompleted = true;
                } else {
                    $allCompleted = false;
                }
            }
            if ($allCompleted && count($group['items']) > 0) {
                $group['status'] = 'Completed';
            } elseif ($anyCompleted) {
                $group['status'] = 'Partial';
            } else {
                $group['status'] = 'Pending';
            }
        }

        $allOrders = array_values($groupedOrders);

        // Sort by ordered_at DESC
        usort($allOrders, function($a, $b) {
            return strtotime($b['ordered_at']) - strtotime($a['ordered_at']);
        });

        return $allOrders;
    }
    
    /**
     * Get distinct departments / specializations from doctors and ot_surgeries tables
     */
    public function getDistinctDepartments()
    {
        $sql = "SELECT DISTINCT specialization as department_name FROM doctors WHERE specialization IS NOT NULL AND specialization != ''
                UNION
                SELECT DISTINCT department as department_name FROM ot_surgeries WHERE department IS NOT NULL AND department != ''
                ORDER BY department_name ASC";
        $result = $this->db->fetchAll($sql);
        
        $departments = [];
        if ($result) {
            foreach ($result as $row) {
                if (!empty($row['department_name'])) {
                    $departments[] = $row['department_name'];
                }
            }
        }
        return $departments;
    }
}
