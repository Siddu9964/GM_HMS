<?php
namespace GM_HMS\Modules\Laboratory\Repositories;

use GM_HMS\Database\SecureDatabase;

class LaboratoryRepository
{
    private $db;

    public function __construct()
    {
        $this->db = SecureDatabase::getInstance();
    }

    public function getLabTestParameters($serviceId)
    {
        // Fetch all parameters ordered by sl_no
        return $this->db->fetchAll(
            "SELECT * FROM lab_test_parameters WHERE service_id = ? OR test_name = ? ORDER BY sl_no ASC",
            [$serviceId, $serviceId]
        );
    }

    public function saveTestParameters($serviceId, $parameters)
    {
        // Get test_name from lab_services
        $testDataResult = $this->db->fetchAll("SELECT test_name FROM lab_services WHERE service_id = ?", [$serviceId]);
        $testName = !empty($testDataResult) ? ($testDataResult[0]['test_name'] ?? '') : '';

        $this->db->execute("DELETE FROM lab_test_parameters WHERE service_id = ?", [$serviceId]);
        
        foreach ($parameters as $index => $p) {
            $this->db->execute(
                "INSERT INTO lab_test_parameters 
                (service_id, test_name, parameter_name, unit, normal_range, normal_range_male, normal_range_female, normal_range_child, normal_range_newborn, `normal_range_Infant(29 days 12 months)`, `normal_range_toddler(1 & 3 years)`, `normal_range_preschool_child(4 & 5 years)`, `normal_range_school_child(6 & 12 years)`, `normal_range_adolescent(13 & 17 years)`, `normal_range_adult(18 & 59 years)`, `normal_range_elderly(60 & 74 years)`, `normal_range_senior_elderly(75+ years)`, critical_low, critical_high) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $serviceId,
                    $testName,
                    $p['parameter_name'] ?? '',
                    $p['unit'] ?? null,
                    $p['normal_range'] ?? null,
                    $p['normal_range_male'] ?? null,
                    $p['normal_range_female'] ?? null,
                    $p['normal_range_child'] ?? null,
                    $p['normal_range_newborn'] ?? null,
                    $p['normal_range_Infant(29 days 12 months)'] ?? null,
                    $p['normal_range_toddler(1 & 3 years)'] ?? null,
                    $p['normal_range_preschool_child(4 & 5 years)'] ?? null,
                    $p['normal_range_school_child(6 & 12 years)'] ?? null,
                    $p['normal_range_adolescent(13 & 17 years)'] ?? null,
                    $p['normal_range_adult(18 & 59 years)'] ?? null,
                    $p['normal_range_elderly(60 & 74 years)'] ?? null,
                    $p['normal_range_senior_elderly(75+ years)'] ?? null,
                    $p['critical_low'] ?? null,
                    $p['critical_high'] ?? null
                ]
            );
        }
        return true;
    }

    public function getLabServices()
    {
        return $this->db->fetchAll("SELECT sl_no, service_id, test_name, opd_rate, `General Ward` AS gw_rate, `Semi Private Room` AS spvt_rate, `Private Room` AS pvt_ccu_rate, suite_rate FROM lab_services ORDER BY test_name ASC");
    }

    public function getRadiologyServices()
    {
        return $this->db->fetchAll("SELECT * FROM radiology_services ORDER BY billing_name ASC");
    }

    public function getAllPatients()
    {
        return $this->db->fetchAll("SELECT * FROM patient");
    }

    public function getIpdOrders($all, $date, $statusFilter = 'all', $search = '')
    {
        // For IPD, we consider records in ipd_clinical_records that have lab_tests
        $sql = "SELECT cr.id AS order_id, 
                       cr.lab_tests,
                       cr.created_at AS order_date,
                       'IPD' AS source,
                       cr.patient_id, 
                       CONCAT(p.first_name, ' ', IFNULL(p.last_name, '')) AS patient_name,
                       p.age, p.sex, p.phone,
                       '' AS doctor_name, '' AS specialization
                FROM ipd_clinical_records cr
                LEFT JOIN ipd_admissions a ON CONVERT(cr.admission_id USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(a.admission_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
                LEFT JOIN patient p ON CONVERT(cr.patient_id USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(p.patient_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
                WHERE cr.lab_tests IS NOT NULL";

        $params = [];
        
        if ($all !== '1') {
            $sql .= " AND DATE(cr.created_at) = ?";
            $params[] = $date;
        }

        if ($search) {
            $sql .= " AND (cr.id LIKE ? OR cr.patient_id LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ?)";
            $searchParam = "%$search%";
            $params = [$searchParam, $searchParam, $searchParam, $searchParam];
        }

        $sql .= " ORDER BY cr.created_at DESC LIMIT 500";

        $records = $this->db->fetchAll($sql, $params);
        $orders = [];

        foreach ($records as $result) {
            $testNames = [];
            $labTests = @json_decode($result['lab_tests'], true);
            if (is_array($labTests)) {
                foreach ($labTests as $lt) {
                    if (is_string($lt)) {
                        if (preg_match('/^(LAB|RDS|OTH)/i', $lt, $matches)) {
                            $prefix = strtoupper($matches[1]);
                            $service = $this->getServiceName($prefix, $lt);
                            if ($service) {
                                $name = $prefix === 'LAB' ? ($service['test_name'] ?? $lt) : ($service['billing_name'] ?? $lt);
                                $testNames[] = $name . ' (' . $lt . ')';
                            } else {
                                $testNames[] = $lt;
                            }
                        } else {
                            $testNames[] = $lt;
                        }
                    } else {
                        $name = $lt['data']['name'] ?? $lt['name'] ?? 'Lab Test';
                        $id = $lt['data']['id'] ?? $lt['id'] ?? null;
                        if ($id) {
                            $testNames[] = "$name ($id)";
                        } else {
                            $testNames[] = $name;
                        }
                    }
                }
            }
            
            if (empty($testNames)) continue;

            $result['test_name'] = json_encode($testNames);
            $result['order_id'] = 'IPD-' . $result['order_id'];
            $result['status'] = 'Ordered';
            $result['priority'] = 'Routine';
            
            $labResult = $this->getLabResultByOrderId($result['order_id']);
            if ($labResult && $labResult['status']) {
                $result['status'] = $labResult['status'];
            }

            if ($statusFilter !== 'all') {
                if ($statusFilter === 'completed' && $result['status'] !== 'Completed' && $result['status'] !== 'Reported') continue;
                if ($statusFilter === 'pending' && ($result['status'] === 'Completed' || $result['status'] === 'Reported')) continue;
            }

            $orders[] = $result;
        }

        return $orders;
    }

    public function updateIpdOrderStatus($orderId, $status)
    {
        if (strpos($orderId, 'IPD-') === 0) {
            $labResult = $this->getLabResultByOrderId($orderId);
            if ($labResult) {
                return $this->db->execute(
                    "UPDATE ipd_lab_results SET status = ? WHERE order_id = ?",
                    [$status, $orderId]
                );
            } else {
                $resultId = 'RES-' . strtoupper(substr(uniqid(), -6));
                return $this->db->execute(
                    "INSERT INTO ipd_lab_results (result_id, order_id, patient_id, test_name, result_date, result_time, status, result_data, abnormal_flags) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$resultId, $orderId, '', '', date('Y-m-d'), date('H:i:s'), $status, '[]', '[]']
                );
            }
        }
        return false;
    }

    public function getOtherServices()
    {
        return $this->db->fetchAll("SELECT * FROM other_services ORDER BY billing_name ASC");
    }

    public function deleteLabService($id)
    {
        return $this->db->execute("DELETE FROM lab_services WHERE service_id = ?", [$id]);
    }

    public function deleteRadiologyService($id)
    {
        return $this->db->execute("DELETE FROM radiology_services WHERE service_id = ?", [$id]);
    }

    public function deleteOtherService($id)
    {
        return $this->db->execute("DELETE FROM other_services WHERE service_id = ?", [$id]);
    }

    public function createLabService($data)
    {
        return $this->db->execute(
            "INSERT INTO lab_services (service_id, test_name, opd_rate, `General Ward`, `Semi Private Room`, `Private Room`, suite_rate) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $data['service_id'],
                $data['test_name'],
                $data['opd_rate'],
                $data['gw_rate'],
                $data['spvt_rate'],
                $data['pvt_ccu_rate'],
                $data['suite_rate']
            ]
        );
    }

    public function createRadiologyService($data)
    {
        return $this->db->execute(
            "INSERT INTO radiology_services (service_id, billing_name, modality_name, opd_price, general_ward_price, semi_private_price, private_icu_price, suite_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['service_id'],
                $data['billing_name'],
                $data['modality_name'],
                $data['opd_price'],
                $data['general_ward_price'],
                $data['semi_private_price'],
                $data['private_icu_price'],
                $data['suite_price']
            ]
        );
    }

    public function createOtherService($data)
    {
        return $this->db->execute(
            "INSERT INTO other_services (service_id, billing_name, op_gw_price, semi_private_price, private_icu_price, suite_price) VALUES (?, ?, ?, ?, ?, ?)",
            [
                $data['service_id'],
                $data['billing_name'],
                $data['op_gw_price'],
                $data['semi_private_price'],
                $data['private_icu_price'],
                $data['suite_price']
            ]
        );
    }

    public function updateLabService($id, $data)
    {
        return $this->db->execute(
            "UPDATE lab_services SET test_name = ?, opd_rate = ?, `General Ward` = ?, `Semi Private Room` = ?, `Private Room` = ?, suite_rate = ? WHERE service_id = ?",
            [
                $data['test_name'],
                $data['opd_rate'],
                $data['gw_rate'],
                $data['spvt_rate'],
                $data['pvt_ccu_rate'],
                $data['suite_rate'],
                $id
            ]
        );
    }

    public function updateRadiologyService($id, $data)
    {
        return $this->db->execute(
            "UPDATE radiology_services SET billing_name = ?, modality_name = ?, opd_price = ?, general_ward_price = ?, semi_private_price = ?, private_icu_price = ?, suite_price = ? WHERE service_id = ?",
            [
                $data['billing_name'],
                $data['modality_name'],
                $data['opd_price'],
                $data['general_ward_price'],
                $data['semi_private_price'],
                $data['private_icu_price'],
                $data['suite_price'],
                $id
            ]
        );
    }

    public function updateOtherService($id, $data)
    {
        return $this->db->execute(
            "UPDATE other_services SET billing_name = ?, op_gw_price = ?, semi_private_price = ?, private_icu_price = ?, suite_price = ? WHERE service_id = ?",
            [
                $data['billing_name'],
                $data['op_gw_price'],
                $data['semi_private_price'],
                $data['private_icu_price'],
                $data['suite_price'],
                $id
            ]
        );
    }

    public function updateOrderStatus($orderId, $status)
    {
        // Append lab status to notes
        return $this->db->execute(
            "UPDATE opd_billing_master SET notes = CONCAT(COALESCE(notes, ''), ' | LabStatus: ', ?) WHERE bill_id = ?",
            [$status, $orderId]
        );
    }

    public function getOrders($all, $date, $status, $priority, $search)
    {
        $sql = "SELECT obm.bill_id AS order_id, 
                       GROUP_CONCAT(obi.item_code SEPARATOR '|||') AS test_name, 
                       obm.bill_date AS order_date, 
                       obm.bill_time AS order_time,
                       'Ordered' AS lab_status, 
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
                WHERE (obi.item_code LIKE 'LAB%' OR obi.item_code LIKE 'OTH%')";

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
                if (preg_match('/LabStatus:\s*([A-Za-z\s]+)(?:\||$)/', $row['notes'], $m)) {
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

            $testIds = array_filter(array_map('trim', explode('|||', $row['test_name'])));
            
            $resolvedNames = [];
            foreach ($testIds as $tId) {
                if (preg_match('/^(LAB|RDS|OTH)/i', $tId, $matches)) {
                    $prefix = strtoupper($matches[1]);
                    $service = $this->getServiceName($prefix, $tId);
                    if ($service) {
                        $name = $prefix === 'LAB' ? ($service['test_name'] ?? $tId) : ($service['billing_name'] ?? $tId);
                        $resolvedNames[] = $name . ' (' . $tId . ')';
                    } else {
                        $resolvedNames[] = $tId;
                    }
                } else {
                    $resolvedNames[] = $tId;
                }
            }

            $row['test_name'] = json_encode($resolvedNames);
            
            if ($status !== '' && $row['status'] !== $status) continue;
            if ($priority !== '' && $row['priority'] !== $priority) continue;
            
            $finalResults[] = $row;
        }
        
        return $finalResults;
    }

    public function getLabServicesCount()
    {
        return $this->db->fetchOne("SELECT COUNT(*) AS cnt FROM lab_services");
    }

    public function getRadiologyServicesCount()
    {
        return $this->db->fetchOne("SELECT COUNT(*) AS cnt FROM radiology_services");
    }

    public function getOtherServicesCount()
    {
        return $this->db->fetchOne("SELECT COUNT(*) AS cnt FROM other_services");
    }

    public function getOrdersTodayCount()
    {
        return $this->db->fetchOne("
            SELECT COUNT(DISTINCT obm.bill_id) AS cnt 
            FROM opd_billing_master obm 
            JOIN opd_billing_items obi ON obm.bill_id = obi.bill_id 
            WHERE (obi.item_code LIKE 'LAB%' OR obi.item_code LIKE 'OTH%') 
            AND DATE(obm.bill_date) = CURDATE()
        ");
    }

    public function getPendingOrdersCount()
    {
        return $this->db->fetchOne("
            SELECT COUNT(DISTINCT obm.bill_id) AS cnt 
            FROM opd_billing_master obm 
            JOIN opd_billing_items obi ON obm.bill_id = obi.bill_id 
            WHERE (obi.item_code LIKE 'LAB%' OR obi.item_code LIKE 'OTH%') 
            AND (obm.notes IS NULL OR (obm.notes NOT LIKE '%LabStatus: Completed%' AND obm.notes NOT LIKE '%LabStatus: Reported%'))
        ");
    }

    public function getCompletedOrdersTodayCount()
    {
        return $this->db->fetchOne("
            SELECT COUNT(DISTINCT obm.bill_id) AS cnt 
            FROM opd_billing_master obm 
            JOIN opd_billing_items obi ON obm.bill_id = obi.bill_id 
            WHERE (obi.item_code LIKE 'LAB%' OR obi.item_code LIKE 'OTH%') 
            AND (obm.notes LIKE '%LabStatus: Completed%' OR obm.notes LIKE '%LabStatus: Reported%')
            AND DATE(obm.bill_date) = CURDATE()
        "); 
    }

    public function getUrgentOrdersTodayCount()
    {
        return $this->db->fetchOne("
            SELECT COUNT(DISTINCT obm.bill_id) AS cnt 
            FROM opd_billing_master obm 
            JOIN opd_billing_items obi ON obm.bill_id = obi.bill_id 
            WHERE (obi.item_code LIKE 'LAB%' OR obi.item_code LIKE 'OTH%') 
            AND obm.notes LIKE '%Priority: Urgent%' 
            AND DATE(obm.bill_date) = CURDATE()
        ");
    }

    public function getMonthPatientsCount()
    {
        return $this->db->fetchOne("
            SELECT COUNT(DISTINCT obm.patient_id) AS cnt 
            FROM opd_billing_master obm 
            JOIN opd_billing_items obi ON obm.bill_id = obi.bill_id 
            WHERE (obi.item_code LIKE 'LAB%' OR obi.item_code LIKE 'OTH%') 
            AND MONTH(obm.bill_date) = MONTH(CURDATE()) 
            AND YEAR(obm.bill_date) = YEAR(CURDATE())
        ");
    }

    public function getDailyTrend()
    {
        return $this->db->fetchAll("
            SELECT DATE(obm.bill_date) AS day, COUNT(DISTINCT obm.bill_id) AS cnt
            FROM opd_billing_master obm 
            JOIN opd_billing_items obi ON obm.bill_id = obi.bill_id 
            WHERE (obi.item_code LIKE 'LAB%' OR obi.item_code LIKE 'OTH%') 
            AND obm.bill_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY DATE(obm.bill_date)
            ORDER BY day ASC
        ");
    }

    public function getTopTests()
    {
        return $this->db->fetchAll("
            SELECT obi.item_code AS test_name, COUNT(*) AS cnt
            FROM opd_billing_master obm 
            JOIN opd_billing_items obi ON obm.bill_id = obi.bill_id 
            WHERE (obi.item_code LIKE 'LAB%' OR obi.item_code LIKE 'OTH%') 
            AND MONTH(obm.bill_date) = MONTH(CURDATE()) 
            AND YEAR(obm.bill_date) = YEAR(CURDATE())
            GROUP BY obi.item_code
            ORDER BY cnt DESC
            LIMIT 8
        ");
    }

    public function getRecentOrders()
    {
        $results = $this->db->fetchAll(
            "SELECT obm.bill_id AS order_id, 
                    GROUP_CONCAT(obi.item_code SEPARATOR '|||') AS test_name, 
                    'Ordered' AS lab_status, 
                    obm.notes AS notes,
                    obm.bill_date AS order_date, 
                    obm.bill_time AS order_time,
                    COALESCE(NULLIF(TRIM(CONCAT(p.first_name, ' ', IFNULL(p.last_name, ''))), ''), obm.name, 'Walking Patient') AS patient_name,
                    COALESCE(d.full_name, obm.doctor_name) AS doctor_name
             FROM opd_billing_master obm
             JOIN opd_billing_items obi ON obm.bill_id = obi.bill_id
             LEFT JOIN patient p  ON CONVERT(obm.patient_id USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(p.patient_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
             LEFT JOIN doctors d  ON CONVERT(obm.doctor_id USING utf8mb4) COLLATE utf8mb4_unicode_ci  = CONVERT(d.doctor_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
             WHERE (obi.item_code LIKE 'LAB%' OR obi.item_code LIKE 'OTH%')
             GROUP BY obm.bill_id
             ORDER BY obm.bill_date DESC, obm.created_at DESC
             LIMIT 10"
        );

        foreach ($results as &$row) {
            $row['status'] = 'Ordered';
            $row['priority'] = 'Routine';
            if (!empty($row['notes'])) {
                if (preg_match('/LabStatus:\s*([A-Za-z\s]+)(?:\||$)/', $row['notes'], $m)) {
                    $row['status'] = trim($m[1]);
                }
                if (preg_match('/Priority:\s*([A-Za-z]+)(?:\||$)/', $row['notes'], $m)) {
                    $row['priority'] = trim($m[1]);
                }
                if (preg_match('/Walkin:([^|]+)\|([^|]+)\|([^|]+)/', $row['notes'], $m)) {
                    $row['patient_name'] = trim($m[1]);
                }
            }

            // Test name resolution
            $testIds = array_filter(array_map('trim', explode('|||', $row['test_name'])));

            $resolvedNames = [];
            foreach ($testIds as $tId) {
                if (preg_match('/^(LAB|RDS|OTH)/i', $tId, $matches)) {
                    $prefix = strtoupper($matches[1]);
                    $name = $this->getServiceName($prefix, $tId);
                    $resolvedNames[] = $name ? (is_array($name) ? array_values($name)[0] : $name) : $tId;
                } else {
                    $resolvedNames[] = $tId;
                }
            }

            $row['test_name'] = json_encode($resolvedNames);
        }
        return $results;
    }

    public function getPrescribedTests($patientId = '')
    {
        $sql = "SELECT obm.bill_id as consultation_sl_no, obm.bill_id as consultation_id, obm.patient_id, obm.doctor_id, obm.bill_date as consultation_date, GROUP_CONCAT(obi.item_code SEPARATOR '|||') as soap_objective, obm.appointment_id,
                       p.*, d.full_name as doctor_name,
                       a.appointment_date, a.appointment_time, a.appointment_type, a.reason
                FROM opd_billing_master obm
                JOIN opd_billing_items obi ON obm.bill_id = obi.bill_id
                LEFT JOIN patient p ON CONVERT(obm.patient_id USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(p.patient_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
                LEFT JOIN doctors d ON CONVERT(obm.doctor_id USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(d.doctor_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
                LEFT JOIN appointments a ON CONVERT(obm.appointment_id USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(a.appointment_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
                WHERE (obi.item_code LIKE 'LAB%' OR obi.item_code LIKE 'OTH%') 
                AND (obm.appointment_id != 'LAB-MANUAL' OR obm.appointment_id IS NULL)";
        
        $params = [];
        if ($patientId !== '') {
            $sql .= " AND obm.patient_id = ?";
            $params[] = $patientId;
        }
        $sql .= " GROUP BY obm.bill_id ORDER BY obm.bill_date DESC";
        
        return $this->db->fetchAll($sql, $params);
    }

    public function getServiceName($type, $serviceId)
    {
        if ($type === 'LAB') {
            return $this->db->fetchOne("SELECT test_name FROM lab_services WHERE service_id = ?", [$serviceId]);
        } elseif ($type === 'RDS') {
            return $this->db->fetchOne("SELECT billing_name FROM radiology_services WHERE service_id = ?", [$serviceId]);
        } elseif ($type === 'OTH') {
            return $this->db->fetchOne("SELECT billing_name FROM other_services WHERE service_id = ?", [$serviceId]);
        }
        return null;
    }

    public function createOrder($data, $skipConsultation = false)
    {
        $billId = 'OPB-' . date('Ymd') . '-' . rand(1000, 9999);

        // We embed priority, patient_type, and notes inside notes
        $combinedNotes = [];
        if (!empty($data['patient_type'])) $combinedNotes[] = $data['patient_type'];
        if (!empty($data['priority'])) $combinedNotes[] = "Priority: " . $data['priority'];
        if (!empty($data['clinical_notes'])) $combinedNotes[] = "Notes: " . $data['clinical_notes'];
        
        $clinicalNotesStr = implode(" | ", $combinedNotes);

        $appointmentId = 'LAB-MANUAL';
        if (strpos($data['patient_id'], 'WLK-') !== 0) {
            $latestAppt = $this->db->fetchOne(
                "SELECT appointment_id FROM appointments WHERE patient_id = ? ORDER BY created_at DESC LIMIT 1",
                [$data['patient_id']]
            );
            if ($latestAppt && !empty($latestAppt['appointment_id'])) {
                $appointmentId = $latestAppt['appointment_id'];
            }
        }

        $res = $this->db->execute(
            "INSERT INTO opd_billing_master (bill_id, patient_id, doctor_id, appointment_id, bill_date, bill_time, purpose, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $billId,
                $data['patient_id'],
                $data['doctor_id'],
                $appointmentId, // Dynamically set appointment ID
                $data['order_date'],
                date('H:i:s'),
                'Lab Order',
                $clinicalNotesStr
            ]
        );

        $tests = json_decode($data['test_name'], true);
        if(!is_array($tests)) {
            $tests = array_filter(array_map('trim', explode('|||', $data['test_name'])));
        }
        foreach($tests as $testId) {
            $prefix = strtoupper(substr($testId, 0, 3));
            $nameRow = $this->getServiceName($prefix, $testId);
            $itemName = $nameRow ? (is_array($nameRow) ? array_values($nameRow)[0] : $nameRow) : $testId;
            
            $this->db->execute(
                "INSERT INTO opd_billing_items (bill_id, item_code, item_name, item_type, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $billId,
                    $testId,
                    $itemName,
                    'Investigation',
                    1,
                    0,
                    0
                ]
            );
        }

        if ($res && isset($res['insert_id'])) {
            $res['order_id'] = $billId; 
        }
        $res['order_id'] = $billId;

        return $res;
    }

    public function getOrderById($orderId)
    {
        if (strpos($orderId, 'IPD-') === 0) {
            $recordId = substr($orderId, 4);
            $result = $this->db->fetchOne(
                "SELECT cr.id AS order_id, 
                        cr.lab_tests, cr.other_tests, 
                        cr.created_at AS order_date, 
                        'Ordered' AS lab_status, 
                        cr.patient_id, 
                        CONCAT(p.first_name, ' ', IFNULL(p.last_name, '')) AS patient_name,
                        p.age, p.sex, p.phone,
                        '' AS doctor_name, '' AS specialization
                 FROM ipd_clinical_records cr
                 LEFT JOIN ipd_admissions a ON CONVERT(cr.admission_id USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(a.admission_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
                 LEFT JOIN patient p ON CONVERT(cr.patient_id USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(p.patient_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
                 WHERE cr.id = ?",
                [$recordId]
            );
            
            if ($result) {
                // Parse JSON lab_tests and other_tests to get test names
                $testNames = [];
                $labTests = @json_decode($result['lab_tests'], true);
                if (is_array($labTests)) {
                    foreach ($labTests as $lt) {
                        if (is_string($lt)) {
                            $testNames[] = $lt;
                        } else {
                            $name = $lt['data']['name'] ?? $lt['name'] ?? 'Lab Test';
                            $id = $lt['data']['id'] ?? $lt['id'] ?? null;
                            if ($id) {
                                $testNames[] = "$name ($id)";
                            } else {
                                $testNames[] = $name;
                            }
                        }
                    }
                }
                
                $otherTests = @json_decode($result['other_tests'], true);
                if (is_array($otherTests)) {
                    foreach ($otherTests as $ot) {
                        if (is_string($ot)) {
                            $testNames[] = $ot;
                        } else {
                            $name = $ot['data']['name'] ?? $ot['name'] ?? 'Other Test';
                            $id = $ot['data']['id'] ?? $ot['id'] ?? null;
                            if ($id) {
                                $testNames[] = "$name ($id)";
                            } else {
                                $testNames[] = $name;
                            }
                        }
                    }
                }
                
                $result['test_name'] = implode('|||', $testNames);
                $result['status'] = 'Ordered';
                $result['priority'] = 'Routine';
                $result['clinical_notes'] = '';
                $result['order_id'] = 'IPD-' . $result['order_id'];
                
                // Adjust status based on lab_results table if present
                $labResult = $this->getLabResultByOrderId($result['order_id']);
                if ($labResult && $labResult['status']) {
                     $result['status'] = $labResult['status'];
                }
                
                return $result;
            }
            return null;
        }
        $result = $this->db->fetchOne(
            "SELECT obm.bill_id AS order_id, 
                    GROUP_CONCAT(obi.item_code SEPARATOR '|||') AS test_name, 
                    obm.bill_date AS order_date, 
                    'Ordered' AS lab_status, 
                    obm.notes AS notes,
                    obm.patient_id, obm.doctor_id,
                    COALESCE(NULLIF(TRIM(CONCAT(p.first_name, ' ', IFNULL(p.last_name, ''))), ''), obm.name, 'Walking Patient') AS patient_name,
                    p.age, p.sex, COALESCE(p.phone, obm.mobile) AS phone,
                    COALESCE(d.full_name, obm.doctor_name) AS doctor_name, d.specialization
             FROM opd_billing_master obm
             JOIN opd_billing_items obi ON obm.bill_id = obi.bill_id
             LEFT JOIN patient p  ON CONVERT(obm.patient_id USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(p.patient_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
             LEFT JOIN doctors d  ON CONVERT(obm.doctor_id USING utf8mb4) COLLATE utf8mb4_unicode_ci  = CONVERT(d.doctor_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
             WHERE obm.bill_id = ?
             GROUP BY obm.bill_id",
            [$orderId]
        );

        if ($result) {
            $result['status'] = 'Ordered';
            $result['priority'] = 'Routine';
            $result['clinical_notes'] = $result['notes']; // Ensure clinical_notes exists

            if (!empty($result['notes'])) {
                if (preg_match('/LabStatus:\s*([A-Za-z\s]+)(?:\||$)/', $result['notes'], $m)) {
                    $result['status'] = trim($m[1]);
                }
                if (preg_match('/Priority:\s*([A-Za-z]+)(?:\||$)/', $result['notes'], $m)) {
                    $result['priority'] = trim($m[1]);
                }
                if (preg_match('/Walkin:([^|]+)\|([^|]+)\|([^|]+)/', $result['notes'], $m)) {
                    $result['patient_name'] = trim($m[1]);
                    $result['age'] = trim($m[2]);
                    $result['phone'] = trim($m[3]);
                }
            }

            // Test name resolution
            $testIds = array_filter(array_map('trim', explode('|||', $result['test_name'])));

            $resolvedNames = [];
            foreach ($testIds as $tId) {
                if (preg_match('/^(LAB|RDS|OTH)/i', $tId, $matches)) {
                    $prefix = strtoupper($matches[1]);
                    $name = $this->getServiceName($prefix, $tId);
                    $resolvedNames[] = $name ? (is_array($name) ? array_values($name)[0] : $name) : $tId;
                } else {
                    $resolvedNames[] = $tId;
                }
            }

            $result['test_name'] = json_encode($resolvedNames);
        }

        return $result;
    }

    public function updateOrder($orderId, $data)
    {
        $combinedNotes = [];
        if (!empty($data['patient_type'])) $combinedNotes[] = $data['patient_type'];
        if (!empty($data['priority'])) $combinedNotes[] = "Priority: " . $data['priority'];
        if (!empty($data['clinical_notes'])) $combinedNotes[] = "Notes: " . $data['clinical_notes'];
        
        $clinicalNotesStr = implode(" | ", $combinedNotes);

        $this->db->execute(
            "UPDATE opd_billing_master SET patient_id = ?, doctor_id = ?, bill_date = ?, notes = ? WHERE bill_id = ?",
            [
                $data['patient_id'],
                $data['doctor_id'],
                $data['order_date'],
                $clinicalNotesStr,
                $orderId
            ]
        );
        
        $this->db->execute("DELETE FROM opd_billing_items WHERE bill_id = ?", [$orderId]);
        
        $tests = json_decode($data['test_name'], true);
        if(!is_array($tests)) {
            $tests = array_filter(array_map('trim', explode('|||', $data['test_name'])));
        }
        foreach($tests as $testId) {
            $prefix = strtoupper(substr($testId, 0, 3));
            $nameRow = $this->getServiceName($prefix, $testId);
            $itemName = $nameRow ? (is_array($nameRow) ? array_values($nameRow)[0] : $nameRow) : $testId;
            
            $this->db->execute(
                "INSERT INTO opd_billing_items (bill_id, item_code, item_name, item_type, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $orderId,
                    $testId,
                    $itemName,
                    'Investigation',
                    1,
                    0,
                    0
                ]
            );
        }
        return true;
    }

    public function deleteOrder($id)
    {
        $this->db->execute("DELETE FROM opd_billing_items WHERE bill_id = ?", [$id]);
        return $this->db->execute("DELETE FROM opd_billing_master WHERE bill_id = ?", [$id]);
    }

    public function getOrderByConsultationRef($ref)
    {
        return null;
    }

    public function getLabResultByOrderId($orderId)
    {
        if (strpos($orderId, 'IPD-') === 0) {
            return $this->db->fetchOne(
                "SELECT * FROM ipd_lab_results WHERE order_id = ?",
                [$orderId]
            );
        } else {
            return $this->db->fetchOne(
                "SELECT * FROM lab_results WHERE order_id = ?",
                [$orderId]
            );
        }
    }

    public function getPatientPreviousResults($patientId, $testName = null)
    {
        // Query both OPD and IPD lab results for the patient's most recent completed test
        // We look for 'Completed' or 'Reported' status.
        $sql = "
            SELECT * FROM (
                SELECT result_data, result_date, result_time, test_name, status 
                FROM lab_results 
                WHERE patient_id = ? AND status IN ('Reviewed', 'Critical', 'Completed', 'Reported')
                UNION ALL
                SELECT result_data, result_date, result_time, test_name, status 
                FROM ipd_lab_results 
                WHERE patient_id = ? AND status IN ('Reviewed', 'Critical', 'Completed', 'Reported')
            ) AS combined_results
            ORDER BY result_date DESC, result_time DESC
        ";
        
        $params = [$patientId, $patientId];
        $results = $this->db->fetchAll($sql, $params);
        
        $latestParams = [];
        
        foreach ($results as $row) {
            // Optional: filter by testName if we only want exact test matches
            // However, often parameter names are what we actually match against.
            $data = json_decode($row['result_data'], true);
            if (is_array($data)) {
                foreach ($data as $param) {
                    $name = trim(strtolower($param['name']));
                    if (!isset($latestParams[$name])) {
                        $latestParams[$name] = $param['value'];
                    }
                }
            }
        }
        
        return $latestParams;
    }

    public function saveLabResult($data)
    {
        $existing = $this->getLabResultByOrderId($data['order_id']);
        
        if (strpos($data['order_id'], 'IPD-') === 0) {
            if ($existing) {
                return $this->db->execute(
                    "UPDATE ipd_lab_results 
                     SET result_data = ?, abnormal_flags = ?, report_file = ?, status = ?, result_date = ?, result_time = ?, test_name = ?, reviewed_by = ?, reviewed_at = ?
                     WHERE order_id = ?",
                    [
                        $data['result_data'],
                        $data['abnormal_flags'] ?? null,
                        $data['report_file'] ?? $existing['report_file'],
                        $data['status'] ?? 'Reported',
                        $data['result_date'] ?? date('Y-m-d'),
                        $data['result_time'] ?? date('H:i:s'),
                        $data['test_name'] ?? $existing['test_name'],
                        $_SESSION['user_id'] ?? null,
                        date('Y-m-d H:i:s'),
                        $data['order_id']
                    ]
                );
            } else {
                $resultId = 'RES-' . strtoupper(substr(uniqid(), -6));
                return $this->db->execute(
                    "INSERT INTO ipd_lab_results (result_id, order_id, patient_id, test_name, result_data, abnormal_flags, report_file, status, result_date, result_time, reviewed_by, reviewed_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $resultId,
                        $data['order_id'],
                        $data['patient_id'],
                        $data['test_name'],
                        $data['result_data'],
                        $data['abnormal_flags'] ?? null,
                        $data['report_file'] ?? null,
                        $data['status'] ?? 'Reported',
                        $data['result_date'] ?? date('Y-m-d'),
                        $data['result_time'] ?? date('H:i:s'),
                        $_SESSION['user_id'] ?? null,
                        date('Y-m-d H:i:s')
                    ]
                );
            }
        } else {
            if ($existing) {
                return $this->db->execute(
                    "UPDATE lab_results 
                     SET result_data = ?, abnormal_flags = ?, report_file = ?, status = ?, result_date = ?, result_time = ?, test_name = ?
                     WHERE order_id = ?",
                    [
                        $data['result_data'],
                        $data['abnormal_flags'] ?? null,
                        $data['report_file'] ?? $existing['report_file'],
                        $data['status'] ?? 'Reviewed',
                        $data['result_date'] ?? date('Y-m-d'),
                        $data['result_time'] ?? date('H:i:s'),
                        $data['test_name'] ?? $existing['test_name'],
                        $data['order_id']
                    ]
                );
            } else {
                $resultId = 'RES-' . strtoupper(substr(uniqid(), -6));
                return $this->db->execute(
                    "INSERT INTO lab_results (result_id, order_id, patient_id, test_name, result_data, abnormal_flags, report_file, status, result_date, result_time) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $resultId,
                        $data['order_id'],
                        $data['patient_id'],
                        $data['test_name'],
                        $data['result_data'],
                        $data['abnormal_flags'] ?? null,
                        $data['report_file'] ?? null,
                        $data['status'] ?? 'Reviewed',
                        $data['result_date'] ?? date('Y-m-d'),
                        $data['result_time'] ?? date('H:i:s')
                    ]
                );
            }
        }
    }

    public function getUnreadNotifications($recipientType, $category = null)
    {
        $sql = "SELECT * FROM notifications WHERE recipient_type = ? AND is_read = 0";
        $params = [$recipientType];
        
        if ($category) {
            $sql .= " AND category = ?";
            $params[] = $category;
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
}
