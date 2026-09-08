<?php
namespace GM_HMS\Modules\Radiology\Repositories;

use GM_HMS\Database\SecureDatabase;

class RadiologyRepository
{
    private $db;
    private $hasRadResultsTable = null;
    private $hasIpdRadResultsTable = null;

    public function __construct()
    {
        $this->db = SecureDatabase::getInstance();
    }

    /**
     * Check if dedicated radiology_results table exists, otherwise fallback to lab_results
     */
    private function checkRadTable(): string
    {
        if ($this->hasRadResultsTable === null) {
            try {
                $check = $this->db->fetchOne("SHOW TABLES LIKE 'radiology_results'");
                $this->hasRadResultsTable = !empty($check);
            } catch (\Throwable $e) {
                $this->hasRadResultsTable = false;
            }
        }
        return $this->hasRadResultsTable ? 'radiology_results' : 'lab_results';
    }

    /**
     * Check if dedicated ipd_radiology_results table exists, otherwise fallback to ipd_lab_results
     */
    private function checkIpdRadTable(): string
    {
        if ($this->hasIpdRadResultsTable === null) {
            try {
                $check = $this->db->fetchOne("SHOW TABLES LIKE 'ipd_radiology_results'");
                $this->hasIpdRadResultsTable = !empty($check);
            } catch (\Throwable $e) {
                $this->hasIpdRadResultsTable = false;
            }
        }
        return $this->hasIpdRadResultsTable ? 'ipd_radiology_results' : 'ipd_lab_results';
    }

    /* =========================================================================
       1. RADIOLOGY SERVICES (CATALOG)
       ========================================================================= */

    public function getRadiologyServices($modality = null)
    {
        if (!empty($modality) && strtolower($modality) !== 'all') {
            return $this->db->fetchAll(
                "SELECT * FROM radiology_services WHERE modality_name = ? ORDER BY billing_name ASC",
                [$modality]
            );
        }
        return $this->db->fetchAll("SELECT * FROM radiology_services ORDER BY billing_name ASC");
    }

    public function getModalities()
    {
        return $this->db->fetchAll(
            "SELECT modality_name, COUNT(*) as count FROM radiology_services GROUP BY modality_name ORDER BY count DESC"
        );
    }

    public function getServiceById($serviceId)
    {
        return $this->db->fetchOne("SELECT * FROM radiology_services WHERE service_id = ?", [$serviceId]);
    }

    public function generateNextId($prefix = 'RDS')
    {
        $prefix = strtoupper($prefix);
        $row = $this->db->fetchOne(
            "SELECT service_id FROM radiology_services WHERE service_id LIKE ? ORDER BY LENGTH(service_id) DESC, service_id DESC LIMIT 1",
            [$prefix . '%']
        );
        if ($row && !empty($row['service_id'])) {
            if (preg_match('/(\d+)/', $row['service_id'], $m)) {
                return $prefix . ((int)$m[1] + 1);
            }
        }
        return $prefix . '100';
    }

    public function createService($data)
    {
        $serviceId = !empty(trim($data['service_id'] ?? '')) ? strtoupper(trim($data['service_id'])) : $this->generateNextId('RDS');
        $billingName = trim($data['billing_name'] ?? '');
        $modalityName = strtoupper(trim($data['modality_name'] ?? 'X-RAY'));
        $opdPrice = isset($data['opd_price']) && $data['opd_price'] !== '' ? (float)$data['opd_price'] : 0.00;
        $gwPrice = isset($data['general_ward_price']) && $data['general_ward_price'] !== '' ? (float)$data['general_ward_price'] : 0.00;
        $spPrice = isset($data['semi_private_price']) && $data['semi_private_price'] !== '' ? (float)$data['semi_private_price'] : 0.00;
        $pvtPrice = isset($data['private_icu_price']) && $data['private_icu_price'] !== '' ? (float)$data['private_icu_price'] : 0.00;
        $suitePrice = isset($data['suite_price']) && $data['suite_price'] !== '' ? (float)$data['suite_price'] : 0.00;

        $res = $this->db->execute(
            "INSERT INTO radiology_services (service_id, billing_name, modality_name, opd_price, general_ward_price, semi_private_price, private_icu_price, suite_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $serviceId,
                $billingName,
                $modalityName,
                $opdPrice,
                $gwPrice,
                $spPrice,
                $pvtPrice,
                $suitePrice
            ]
        );
        return $res ? ['service_id' => $serviceId] : false;
    }

    public function updateService($id, $data)
    {
        $billingName = trim($data['billing_name'] ?? '');
        $modalityName = strtoupper(trim($data['modality_name'] ?? 'X-RAY'));
        $opdPrice = isset($data['opd_price']) && $data['opd_price'] !== '' ? (float)$data['opd_price'] : 0.00;
        $gwPrice = isset($data['general_ward_price']) && $data['general_ward_price'] !== '' ? (float)$data['general_ward_price'] : 0.00;
        $spPrice = isset($data['semi_private_price']) && $data['semi_private_price'] !== '' ? (float)$data['semi_private_price'] : 0.00;
        $pvtPrice = isset($data['private_icu_price']) && $data['private_icu_price'] !== '' ? (float)$data['private_icu_price'] : 0.00;
        $suitePrice = isset($data['suite_price']) && $data['suite_price'] !== '' ? (float)$data['suite_price'] : 0.00;

        return $this->db->execute(
            "UPDATE radiology_services SET billing_name = ?, modality_name = ?, opd_price = ?, general_ward_price = ?, semi_private_price = ?, private_icu_price = ?, suite_price = ? WHERE service_id = ?",
            [
                $billingName,
                $modalityName,
                $opdPrice,
                $gwPrice,
                $spPrice,
                $pvtPrice,
                $suitePrice,
                $id
            ]
        );
    }

    public function deleteService($id)
    {
        return $this->db->execute("DELETE FROM radiology_services WHERE service_id = ?", [$id]);
    }

    /* =========================================================================
       2. OPD RADIOLOGY ORDERS (from opd_billing_master & items)
       ========================================================================= */

    public function getOrders($all, $date, $status, $priority, $search, $modality = '')
    {
        $sql = "SELECT obm.bill_id AS order_id, 
                       GROUP_CONCAT(obi.item_code SEPARATOR '|||') AS test_name, 
                       GROUP_CONCAT(obi.item_name SEPARATOR '|||') AS item_names,
                       obm.bill_date AS order_date, 
                       obm.bill_time AS order_time,
                       'Ordered' AS rad_status, 
                       obm.notes AS notes,
                       obm.patient_id,
                       COALESCE(NULLIF(TRIM(CONCAT(p.first_name, ' ', IFNULL(p.last_name, ''))), ''), obm.name, 'Walking Patient') AS patient_name,
                       p.age, p.sex, COALESCE(p.phone, obm.mobile) AS phone,
                       obm.doctor_id,
                       COALESCE(d.full_name, obm.doctor_name) AS doctor_name, d.specialization,
                       obm.created_at AS updated_at
                FROM opd_billing_master obm
                JOIN opd_billing_items obi ON obm.bill_id = obi.bill_id
                LEFT JOIN patient p ON CONVERT(obm.patient_id USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(p.patient_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
                LEFT JOIN doctors d ON CONVERT(obm.doctor_id USING utf8mb4) COLLATE utf8mb4_unicode_ci  = CONVERT(d.doctor_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
                WHERE obi.item_code LIKE 'RDS%'";

        $params = [];

        if ($all !== '1') {
            $sql .= " AND DATE(obm.bill_date) = ?";
            $params[] = $date;
        }
        
        if ($search !== '') {
            $sql .= " AND (obi.item_name LIKE ? OR obi.item_code LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ? OR obm.name LIKE ? OR obm.bill_id LIKE ? OR obm.patient_id LIKE ?)";
            $s = "%$search%";
            array_push($params, $s, $s, $s, $s, $s, $s, $s);
        }

        $sql .= " GROUP BY obm.bill_id ORDER BY obm.bill_date DESC, obm.created_at DESC";

        $results = $this->db->fetchAll($sql, $params);
        $finalResults = [];

        foreach ($results as $row) {
            $row['status'] = 'Ordered';
            $row['priority'] = 'Routine'; 

            if (!empty($row['notes'])) {
                if (preg_match('/(?:RadStatus|LabStatus):\s*([A-Za-z\s]+)(?:\||$)/', $row['notes'], $m)) {
                    $row['status'] = trim($m[1]);
                }
                if (preg_match('/Priority:\s*([A-Za-z]+)(?:\||$)/', $row['notes'], $m)) {
                    $row['priority'] = trim($m[1]);
                }
                if (preg_match('/Walkin:([^|]+)\|([^|]+)\|([^|]+)/', $row['notes'], $m)) {
                    $row['patient_name'] = trim($m[1]);
                    $row['age'] = trim($m[2]);
                    $row['phone'] = trim($m[3]);
                }
            }

            // Check results table
            $radResult = $this->getRadiologyResultByOrderId($row['order_id']);
            if ($radResult && !empty($radResult['status'])) {
                $row['status'] = $radResult['status'] === 'Reviewed' ? 'Reported' : $radResult['status'];
            }

            // Resolve test names from radiology_services
            $testIds = array_filter(array_map('trim', explode('|||', $row['test_name'])));
            $resolvedNames = [];
            $modalityTags = [];

            foreach ($testIds as $tId) {
                $svc = $this->getServiceById($tId);
                if ($svc) {
                    $resolvedNames[] = $svc['billing_name'] . ' (' . $tId . ')';
                    if (!empty($svc['modality_name'])) $modalityTags[] = $svc['modality_name'];
                } else {
                    $resolvedNames[] = $tId;
                }
            }

            $row['resolved_test_names'] = implode(', ', $resolvedNames);
            $row['test_name'] = json_encode($resolvedNames);
            $row['modalities'] = array_unique($modalityTags);

            // Filter by modality if requested
            if (!empty($modality) && strtolower($modality) !== 'all') {
                if (!in_array(strtoupper($modality), array_map('strtoupper', $row['modalities']))) {
                    continue;
                }
            }

            if (!empty($status) && strtolower($status) !== 'all') {
                if (strcasecmp($row['status'], $status) !== 0) continue;
            }

            if (!empty($priority) && strtolower($priority) !== 'all') {
                if (strcasecmp($row['priority'], $priority) !== 0) continue;
            }

            $finalResults[] = $row;
        }

        return $finalResults;
    }

    public function getOrderById($orderId)
    {
        $orders = $this->getOrders('1', date('Y-m-d'), '', '', $orderId);
        foreach ($orders as $o) {
            if ($o['order_id'] === $orderId) return $o;
        }
        return null;
    }

    public function createOrder($data)
    {
        $billId = 'OPB-RAD-' . date('Ymd') . '-' . rand(1000, 9999);

        $combinedNotes = [];
        if (!empty($data['patient_type'])) $combinedNotes[] = $data['patient_type'];
        if (!empty($data['priority'])) $combinedNotes[] = "Priority: " . $data['priority'];
        if (!empty($data['clinical_notes'])) $combinedNotes[] = "Notes: " . $data['clinical_notes'];
        $combinedNotes[] = "RadStatus: Ordered";
        
        $clinicalNotesStr = implode(" | ", $combinedNotes);
        $appointmentId = 'RAD-MANUAL';
        $patientName = '';
        $patientMobile = 0;

        if (!empty($data['patient_type']) && strpos($data['patient_type'], 'Walkin:') === 0) {
            $parts = explode('|', substr($data['patient_type'], 7));
            $patientName = $parts[0] ?? 'Walkin Patient';
            $patientMobile = isset($parts[2]) ? (int)$parts[2] : 0;
        }

        if (strpos($data['patient_id'], 'WLK-') !== 0) {
            $latestAppt = $this->db->fetchOne(
                "SELECT appointment_id FROM appointments WHERE patient_id = ? ORDER BY created_at DESC LIMIT 1",
                [$data['patient_id']]
            );
            if ($latestAppt && !empty($latestAppt['appointment_id'])) {
                $appointmentId = $latestAppt['appointment_id'];
            }
            if (empty($patientName)) {
                $patientInfo = $this->db->fetchOne("SELECT first_name, last_name, phone FROM patient WHERE patient_id = ?", [$data['patient_id']]);
                if ($patientInfo) {
                    $patientName = trim(($patientInfo['first_name'] ?? '') . ' ' . ($patientInfo['last_name'] ?? ''));
                    $patientMobile = (int)($patientInfo['phone'] ?? 0);
                }
            }
        }

        if (empty($patientName)) $patientName = 'Walkin Patient';
        $createdBy = $_SESSION['user_id'] ?? $_SESSION['username'] ?? 'system';

        $this->db->execute(
            "INSERT INTO opd_billing_master (bill_id, patient_id, doctor_id, appointment_id, bill_date, bill_time, purpose, notes, name, mobile, referral_type, referred_by, sponsor, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $billId,
                $data['patient_id'],
                $data['doctor_id'] ?? '',
                $appointmentId,
                $data['order_date'] ?? date('Y-m-d'),
                date('H:i:s'),
                'Radiology Order',
                $clinicalNotesStr,
                $patientName,
                $patientMobile,
                'None',
                '',
                '',
                $createdBy
            ]
        );

        $tests = is_array($data['test_name']) ? $data['test_name'] : array_filter(array_map('trim', explode('|||', (string)$data['test_name'])));

        foreach ($tests as $testId) {
            $itemName = $testId;
            if (preg_match('/\(([A-Z]{2,4}-?\d+)\)$/i', trim($testId), $matches)) {
                $testId = strtoupper($matches[1]);
                $itemName = trim(substr($itemName, 0, strrpos($itemName, '(')));
            }
            
            $svc = $this->getServiceById($testId);
            $unitPrice = $svc ? (float)($svc['opd_price'] ?? 0) : 0;
            $itemName = $svc ? $svc['billing_name'] : $itemName;

            $this->db->execute(
                "INSERT INTO opd_billing_items (bill_id, item_code, item_name, item_type, quantity, unit_price, total_price, bill_purpose) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $billId,
                    $testId,
                    $itemName,
                    'Radiology',
                    1,
                    $unitPrice,
                    $unitPrice,
                    'Radiology Examination'
                ]
            );
        }

        // Insert Notification for Radiology
        $nid = 'NOT-' . strtoupper(substr(uniqid(), -6));
        $patientName = $data['patient_name'] ?? 'Walking Patient';
        $patientId = $data['patient_id'] ?? '';
        $title = "New OPD Scan Order Added";
        $message = "A new scan order ({$billId}) has been added for {$patientName} ({$patientId}).";
        try {
            $this->db->execute(
                "INSERT INTO notifications (notification_id, recipient_id, recipient_type, title, message, category, priority, action_url) 
                 VALUES (?, 'staff', 'staff', ?, ?, 'radiology_result', 'normal', ?)",
                [$nid, $title, $message, "radiology_view/test_orders.php?order_id={$billId}"]
            );
        } catch (\Throwable $ne) {}

        return ['order_id' => $billId];
    }

    public function updateOrderStatus($orderId, $status)
    {
        return $this->db->execute(
            "UPDATE opd_billing_master SET notes = CONCAT(COALESCE(notes, ''), ' | RadStatus: ', ?) WHERE bill_id = ?",
            [$status, $orderId]
        );
    }

    /* =========================================================================
       3. IPD RADIOLOGY ORDERS (from ipd_clinical_records & ipd_admissions)
       ========================================================================= */

    public function getIpdOrders($all, $date, $statusFilter = 'all', $search = '', $modality = '')
    {
        $sql = "SELECT cr.id AS order_id, 
                       cr.radiology_tests,
                       cr.lab_tests,
                       cr.admission_id,
                       cr.created_at AS order_date,
                       'IPD' AS source,
                       cr.patient_id, 
                       CONCAT(p.first_name, ' ', IFNULL(p.last_name, '')) AS patient_name,
                       p.age, p.sex, p.phone,
                       a.ward_name, a.floor_name, a.room_no, a.room_name, a.bed_id,
                       d.full_name AS doctor_name, d.specialization
                FROM ipd_clinical_records cr
                LEFT JOIN ipd_admissions a ON CONVERT(cr.admission_id USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(a.admission_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
                LEFT JOIN patient p ON CONVERT(cr.patient_id USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(p.patient_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
                LEFT JOIN doctors d ON CONVERT(a.admitting_doctor_id USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(d.doctor_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
                WHERE (
                    (cr.radiology_tests IS NOT NULL AND cr.radiology_tests != '' AND cr.radiology_tests != '[]')
                    OR (cr.lab_tests LIKE '%RDS%')
                )";

        $params = [];
        if ($all !== '1') {
            $sql .= " AND DATE(cr.created_at) = ?";
            $params[] = $date;
        }

        if ($search) {
            $sql .= " AND (cr.id LIKE ? OR cr.patient_id LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ? OR a.admission_id LIKE ?)";
            $searchParam = "%$search%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
        }

        $sql .= " ORDER BY cr.created_at DESC LIMIT 500";
        $records = $this->db->fetchAll($sql, $params);
        $orders = [];

        foreach ($records as $row) {
            $testNames = [];
            $modalityTags = [];

            // Parse radiology_tests JSON
            $radTests = @json_decode($row['radiology_tests'] ?? '', true);
            if (is_array($radTests)) {
                foreach ($radTests as $rt) {
                    $name = $rt['data']['name'] ?? $rt['name'] ?? $rt['data']['test_name'] ?? 'Radiology Exam';
                    $id = $rt['data']['id'] ?? $rt['id'] ?? $rt['data']['test_id'] ?? null;
                    if ($id) {
                        $svc = $this->getServiceById($id);
                        if ($svc && !empty($svc['modality_name'])) $modalityTags[] = $svc['modality_name'];
                        $testNames[] = "$name ($id)";
                    } else {
                        $testNames[] = $name;
                    }
                }
            }

            // Also check lab_tests if RDS was put there
            $labTests = @json_decode($row['lab_tests'] ?? '', true);
            if (is_array($labTests)) {
                foreach ($labTests as $lt) {
                    $id = is_array($lt) ? ($lt['data']['id'] ?? $lt['id'] ?? null) : (string)$lt;
                    if ($id && strpos(strtoupper($id), 'RDS') !== false) {
                        $name = is_array($lt) ? ($lt['data']['name'] ?? $lt['name'] ?? $id) : $id;
                        $svc = $this->getServiceById($id);
                        if ($svc) {
                            $name = $svc['billing_name'];
                            if (!empty($svc['modality_name'])) $modalityTags[] = $svc['modality_name'];
                        }
                        $testNames[] = "$name ($id)";
                    }
                }
            }

            if (empty($testNames)) continue;

            $row['test_name'] = json_encode(array_unique($testNames));
            $row['resolved_test_names'] = implode(', ', array_unique($testNames));
            $row['order_id'] = 'IPD-RAD-' . $row['order_id'];
            $row['status'] = 'Ordered';
            $row['priority'] = 'Routine';
            $row['modalities'] = array_unique($modalityTags);

            $radResult = $this->getIpdRadiologyResultByOrderId($row['order_id']);
            if ($radResult && !empty($radResult['status'])) {
                $row['status'] = $radResult['status'] === 'Reviewed' ? 'Reported' : $radResult['status'];
            }

            if ($statusFilter !== 'all') {
                if ($statusFilter === 'completed' && $row['status'] !== 'Completed' && $row['status'] !== 'Reported') continue;
                if ($statusFilter === 'pending' && ($row['status'] === 'Completed' || $row['status'] === 'Reported')) continue;
            }

            if (!empty($modality) && strtolower($modality) !== 'all') {
                if (!in_array(strtoupper($modality), array_map('strtoupper', $row['modalities']))) continue;
            }

            $orders[] = $row;
        }

        return $orders;
    }

    public function updateIpdOrderStatus($orderId, $status)
    {
        $table = $this->checkIpdRadTable();
        $existing = $this->getIpdRadiologyResultByOrderId($orderId);
        if ($existing) {
            return $this->db->execute("UPDATE {$table} SET status = ? WHERE order_id = ?", [$status, $orderId]);
        } else {
            $resultId = 'RES-RAD-' . strtoupper(substr(uniqid(), -6));
            return $this->db->execute(
                "INSERT INTO {$table} (result_id, order_id, patient_id, test_name, result_date, result_time, status, result_data) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [$resultId, $orderId, '', 'Radiology Examination', date('Y-m-d'), date('H:i:s'), $status, '[]']
            );
        }
    }

    /* =========================================================================
       4. RESULTS (OPD & IPD)
       ========================================================================= */

    public function getRadiologyResultByOrderId($orderId)
    {
        $table = $this->checkRadTable();
        return $this->db->fetchOne("SELECT * FROM {$table} WHERE order_id = ?", [$orderId]);
    }

    public function getIpdRadiologyResultByOrderId($orderId)
    {
        $table = $this->checkIpdRadTable();
        return $this->db->fetchOne("SELECT * FROM {$table} WHERE order_id = ?", [$orderId]);
    }

    public function saveResult($data)
    {
        $isIpd = (strpos($data['order_id'], 'IPD-') === 0);
        $table = $isIpd ? $this->checkIpdRadTable() : $this->checkRadTable();
        $existing = $isIpd ? $this->getIpdRadiologyResultByOrderId($data['order_id']) : $this->getRadiologyResultByOrderId($data['order_id']);

        $rawStatus = $data['status'] ?? 'Reported';
        $statusMap = [
            'pending review'   => 'Reviewed',
            'pending'          => 'In Progress',
            'in progress'      => 'In Progress',
            'ordered'          => 'Ordered',
            'completed'        => 'Completed',
            'reported'         => 'Reported',
            'reviewed'         => 'Reviewed',
            'critical finding' => 'Critical',
            'critical'         => 'Critical',
        ];
        $normStatus = $statusMap[strtolower(trim($rawStatus))] ?? 'Reported';

        if ($existing) {
            return $this->db->execute(
                "UPDATE {$table} 
                 SET modality = COALESCE(NULLIF(?, ''), modality),
                     patient_id = CASE WHEN patient_id IS NULL OR patient_id = '' THEN COALESCE(NULLIF(?, ''), patient_id) ELSE patient_id END,
                     clinical_history = COALESCE(NULLIF(?, ''), clinical_history),
                     technique = COALESCE(NULLIF(?, ''), technique),
                     findings = COALESCE(NULLIF(?, ''), findings),
                     impression = COALESCE(NULLIF(?, ''), impression),
                     result_data = COALESCE(NULLIF(?, ''), result_data), 
                     report_file = COALESCE(NULLIF(?, ''), report_file), 
                     status = ?, 
                     result_date = ?, 
                     result_time = ?, 
                     test_name = CASE WHEN test_name IS NULL OR test_name = '' OR test_name = 'Radiology Examination' THEN COALESCE(NULLIF(?, ''), test_name) ELSE test_name END, 
                     reviewed_by = ?, 
                     reviewed_at = ?
                 WHERE order_id = ?",
                [
                    $data['modality'] ?? null,
                    $data['patient_id'] ?? null,
                    $data['clinical_history'] ?? null,
                    $data['technique'] ?? null,
                    $data['findings'] ?? null,
                    $data['impression'] ?? null,
                    $data['result_data'] ?? null,
                    $data['report_file'] ?? null,
                    $normStatus,
                    $data['result_date'] ?? date('Y-m-d'),
                    $data['result_time'] ?? date('H:i:s'),
                    $data['test_name'] ?? null,
                    $data['reviewed_by'] ?? $_SESSION['user_id'] ?? null,
                    date('Y-m-d H:i:s'),
                    $data['order_id']
                ]
            );
        } else {
            $resultId = 'RES-RAD-' . strtoupper(substr(uniqid(), -6));
            return $this->db->execute(
                "INSERT INTO {$table} (result_id, order_id, patient_id, test_name, modality, clinical_history, technique, findings, impression, result_data, report_file, status, result_date, result_time, reviewed_by, reviewed_at, patient_type) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $resultId,
                    $data['order_id'],
                    $data['patient_id'] ?? '',
                    $data['test_name'] ?? 'Radiology Examination',
                    $data['modality'] ?? 'X-RAY',
                    $data['clinical_history'] ?? null,
                    $data['technique'] ?? null,
                    $data['findings'] ?? null,
                    $data['impression'] ?? null,
                    $data['result_data'] ?? null,
                    $data['report_file'] ?? null,
                    $normStatus,
                    $data['result_date'] ?? date('Y-m-d'),
                    $data['result_time'] ?? date('H:i:s'),
                    $data['reviewed_by'] ?? $_SESSION['user_id'] ?? null,
                    date('Y-m-d H:i:s'),
                    $isIpd ? 'IPD' : 'OPD'
                ]
            );
        }
    }

    public function getPatientPreviousResults($patientId)
    {
        $radTable = $this->checkRadTable();
        $ipdTable = $this->checkIpdRadTable();

        $sql = "
            SELECT order_id, test_name, result_date, result_time, result_data, report_file, status, 'OPD' as source
            FROM {$radTable} WHERE patient_id = ?
            UNION ALL
            SELECT order_id, test_name, result_date, result_time, result_data, report_file, status, 'IPD' as source
            FROM {$ipdTable} WHERE patient_id = ?
            ORDER BY result_date DESC, result_time DESC
        ";
        return $this->db->fetchAll($sql, [$patientId, $patientId]);
    }

    /* =========================================================================
       5. DASHBOARD & REPORTING METRICS
       ========================================================================= */

    public function getDashboardStats()
    {
        // 1. Modality counts from radiology_services
        $modalityRows = $this->db->fetchAll("SELECT modality_name, COUNT(*) as cnt FROM radiology_services GROUP BY modality_name");
        $modalities = [];
        foreach ($modalityRows as $m) {
            $modalities[strtoupper(trim($m['modality_name']))] = (int)$m['cnt'];
        }

        // 2. Orders Today (OPD)
        $today = date('Y-m-d');
        $opdToday = $this->getOrders('0', $today, '', '', '');
        $ordersTodayCount = count($opdToday);

        // 3. Status breakdown today
        $pending = 0;
        $completedToday = 0;
        $urgentToday = 0;

        foreach ($opdToday as $o) {
            if ($o['status'] === 'Ordered' || $o['status'] === 'In Progress') $pending++;
            if ($o['status'] === 'Completed' || $o['status'] === 'Reported') $completedToday++;
            if ($o['priority'] === 'Urgent' || $o['priority'] === 'Stat') $urgentToday++;
        }

        // 4. IPD Orders count
        $ipdToday = $this->getIpdOrders('0', $today);
        $ipdTodayCount = count($ipdToday);

        // 5. Total Patients this month
        $monthStart = date('Y-m-01');
        $monthPatientsRow = $this->db->fetchOne(
            "SELECT COUNT(DISTINCT obm.patient_id) as cnt FROM opd_billing_master obm 
             JOIN opd_billing_items obi ON obm.bill_id = obi.bill_id 
             WHERE obi.item_code LIKE 'RDS%' AND obm.bill_date >= ?",
            [$monthStart]
        );

        // 6. Trend - Last 7 Days
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $countRow = $this->db->fetchOne(
                "SELECT COUNT(DISTINCT obm.bill_id) as cnt FROM opd_billing_master obm 
                 JOIN opd_billing_items obi ON obm.bill_id = obi.bill_id 
                 WHERE obi.item_code LIKE 'RDS%' AND DATE(obm.bill_date) = ?",
                [$d]
            );
            $trend[] = [
                'day' => $d,
                'cnt' => (int)($countRow['cnt'] ?? 0)
            ];
        }

        // 7. Top Tests
        $topTests = $this->db->fetchAll(
            "SELECT obi.item_name as test_name, COUNT(*) as cnt 
             FROM opd_billing_items obi 
             WHERE obi.item_code LIKE 'RDS%' 
             GROUP BY obi.item_code, obi.item_name 
             ORDER BY cnt DESC LIMIT 8"
        );

        return [
            'stats' => [
                'total_services'  => array_sum($modalities),
                'xray_services'   => $modalities['X RAY'] ?? $modalities['X-RAY'] ?? 0,
                'ct_services'     => $modalities['CT'] ?? 0,
                'usg_services'    => $modalities['ULTRA SOUND'] ?? $modalities['USG'] ?? 0,
                'doppler_services'=> $modalities['DOPPLER'] ?? 0,
                'orders_today'    => $ordersTodayCount + $ipdTodayCount,
                'pending'         => $pending,
                'completed_today' => $completedToday,
                'urgent_today'    => $urgentToday,
                'month_patients'  => (int)($monthPatientsRow['cnt'] ?? 0),
            ],
            'trend'     => $trend,
            'top_tests' => $topTests,
            'recent'    => array_slice($opdToday, 0, 8),
        ];
    }

    public function getUnreadNotifications($recipientType = 'staff', $category = 'radiology_result', $onlyToday = true)
    {
        $sql = "SELECT * FROM notifications 
                WHERE is_read = 0 
                  AND (category = ? OR recipient_id IN ('radiology', 'Radiologist'))";
        $params = [$category];

        if ($onlyToday) {
            $sql .= " AND DATE(created_at) = CURDATE()";
        }

        $sql .= " ORDER BY created_at DESC LIMIT 50";
        return $this->db->fetchAll($sql, $params);
    }

    public function markNotificationRead($id)
    {
        return $this->db->execute(
            "UPDATE notifications SET is_read = 1, read_at = ? WHERE notification_id = ?",
            [date('Y-m-d H:i:s'), $id]
        );
    }

    public function markNotificationCompletedForOrder($orderId, $patientId = null)
    {
        $sql = "UPDATE notifications 
                SET is_read = 1, read_at = ? 
                WHERE is_read = 0 
                  AND category = 'radiology_result' 
                  AND (
                      message LIKE CONCAT('%', ?, '%') 
                      OR action_url LIKE CONCAT('%', ?, '%')
                      OR (? IS NOT NULL AND ? != '' AND message LIKE CONCAT('%', ?, '%'))
                  )";
        return $this->db->execute($sql, [
            date('Y-m-d H:i:s'),
            $orderId,
            $orderId,
            $patientId,
            $patientId,
            $patientId
        ]);
    }
}
