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

    public function getLabServices()
    {
        return $this->db->fetchAll("SELECT * FROM lab_services ORDER BY test_name ASC");
    }

    public function getRadiologyServices()
    {
        return $this->db->fetchAll("SELECT * FROM radiology_services ORDER BY billing_name ASC");
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
            "INSERT INTO lab_services (service_id, test_name, opd_rate, gw_rate, spvt_rate, pvt_ccu_rate, suite_rate) VALUES (?, ?, ?, ?, ?, ?, ?)",
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
            "UPDATE lab_services SET test_name = ?, opd_rate = ?, gw_rate = ?, spvt_rate = ?, pvt_ccu_rate = ?, suite_rate = ? WHERE service_id = ?",
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
        // Append lab status to clinical_notes
        return $this->db->execute(
            "UPDATE consultations SET clinical_notes = CONCAT(COALESCE(clinical_notes, ''), ' | LabStatus: ', ?) WHERE consultation_id = ?",
            [$status, $orderId]
        );
    }

    public function getOrders($all, $date, $status, $priority, $search)
    {
        $sql = "SELECT c.consultation_id AS order_id, 
                       c.soap_objective AS test_name, 
                       c.consultation_date AS order_date, 
                       c.consultation_time AS order_time,
                       c.status AS lab_status, 
                       c.clinical_notes AS notes,
                       c.patient_id,
                       CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
                       p.age, p.sex, p.phone,
                       c.doctor_id,
                       d.full_name AS doctor_name, d.specialization,
                       c.updated_at
                FROM consultations c
                LEFT JOIN patient p ON CONVERT(c.patient_id USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(p.patient_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
                LEFT JOIN doctors d ON CONVERT(c.doctor_id USING utf8mb4) COLLATE utf8mb4_unicode_ci  = CONVERT(d.doctor_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
                WHERE c.soap_objective IS NOT NULL AND c.soap_objective != ''";

        $params = [];

        if ($all !== '1') {
            $sql .= " AND DATE(c.consultation_date) = ?";
            $params[] = $date;
        }

        // We skip exact status and priority filtering in SQL since consultations structure differs.
        
        if ($search !== '') {
            $sql .= " AND (c.soap_objective LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ? OR c.consultation_id LIKE ? OR c.patient_id LIKE ?)";
            $s = "%$search%";
            array_push($params, $s, $s, $s, $s, $s);
        }

        $sql .= " ORDER BY c.consultation_date DESC, c.created_at DESC";

        $results = $this->db->fetchAll($sql, $params);
        
        $finalResults = [];
        foreach ($results as $row) {
            // Map status text (Consultations status might be 1 = active, 0 = inactive, we default to Ordered)
            $row['status'] = 'Ordered';
            $row['priority'] = 'Routine'; // Default priority

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

            // Test name resolution
            $testIds = [];
            $rawTests = $row['test_name'];
            
            // It could be a JSON array (from manual entry) or comma separated IDs
            $decoded = json_decode($rawTests, true);
            if (is_array($decoded)) {
                $testIds = $decoded;
            } else {
                $testIds = array_map('trim', explode(',', $rawTests));
            }

            $resolvedNames = [];
            foreach ($testIds as $tId) {
                // Check if it's an ID format (e.g. LAB123, RDS123, OTH123)
                if (preg_match('/^(LAB|RDS|OTH)/i', $tId, $matches)) {
                    $prefix = strtoupper($matches[1]);
                    $service = $this->getServiceName($prefix, $tId);
                    if ($service) {
                        $resolvedNames[] = $prefix === 'LAB' ? ($service['test_name'] ?? $tId) : ($service['billing_name'] ?? $tId);
                    } else {
                        $resolvedNames[] = $tId;
                    }
                } else {
                    $resolvedNames[] = $tId; // Already a name or unknown ID
                }
            }

            $row['test_name'] = json_encode($resolvedNames);
            
            // Simple PHP-side filter for status/priority if really needed
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
        return $this->db->fetchOne("SELECT COUNT(*) AS cnt FROM consultations WHERE soap_objective IS NOT NULL AND soap_objective != '' AND DATE(consultation_date) = CURDATE()");
    }

    public function getPendingOrdersCount()
    {
        // Consultations don't track lab status natively; we consider all active consultations as Ordered
        return $this->db->fetchOne("SELECT COUNT(*) AS cnt FROM consultations WHERE soap_objective IS NOT NULL AND soap_objective != '' AND status = 1");
    }

    public function getCompletedOrdersTodayCount()
    {
        // Consultations don't track lab status natively; this will just be 0 or based on result checking
        return ['cnt' => 0]; 
    }

    public function getUrgentOrdersTodayCount()
    {
        // Priority is embedded in clinical_notes
        return $this->db->fetchOne("SELECT COUNT(*) AS cnt FROM consultations WHERE soap_objective IS NOT NULL AND soap_objective != '' AND clinical_notes LIKE '%Priority: Urgent%' AND DATE(consultation_date) = CURDATE()");
    }

    public function getMonthPatientsCount()
    {
        return $this->db->fetchOne("SELECT COUNT(DISTINCT patient_id) AS cnt FROM consultations WHERE soap_objective IS NOT NULL AND soap_objective != '' AND MONTH(consultation_date) = MONTH(CURDATE()) AND YEAR(consultation_date) = YEAR(CURDATE())");
    }

    public function getDailyTrend()
    {
        return $this->db->fetchAll(
            "SELECT DATE(consultation_date) AS day, COUNT(*) AS cnt
             FROM consultations
             WHERE soap_objective IS NOT NULL AND soap_objective != '' AND consultation_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY DATE(consultation_date)
             ORDER BY day ASC"
        );
    }

    public function getTopTests()
    {
        return $this->db->fetchAll(
            "SELECT soap_objective AS test_name, COUNT(*) AS cnt
             FROM consultations
             WHERE soap_objective IS NOT NULL AND soap_objective != '' AND MONTH(consultation_date) = MONTH(CURDATE()) AND YEAR(consultation_date) = YEAR(CURDATE())
             GROUP BY soap_objective
             ORDER BY cnt DESC
             LIMIT 8"
        );
    }

    public function getRecentOrders()
    {
        $results = $this->db->fetchAll(
            "SELECT c.consultation_id AS order_id, c.soap_objective AS test_name, c.status AS lab_status, c.clinical_notes AS notes,
                    c.consultation_date AS order_date, c.consultation_time AS order_time,
                    CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
                    d.full_name AS doctor_name
             FROM consultations c
             LEFT JOIN patient p  ON CONVERT(c.patient_id USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(p.patient_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
             LEFT JOIN doctors d  ON CONVERT(c.doctor_id USING utf8mb4) COLLATE utf8mb4_unicode_ci  = CONVERT(d.doctor_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
             WHERE c.soap_objective IS NOT NULL AND c.soap_objective != ''
             ORDER BY c.consultation_date DESC, c.created_at DESC
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
            $testIds = [];
            $rawTests = $row['test_name'];
            $decoded = json_decode($rawTests, true);
            if (is_array($decoded)) {
                $testIds = $decoded;
            } else {
                $testIds = array_map('trim', explode(',', $rawTests));
            }

            $resolvedNames = [];
            foreach ($testIds as $tId) {
                if (preg_match('/^(LAB|RDS|OTH)/i', $tId, $matches)) {
                    $prefix = strtoupper($matches[1]);
                    $name = $this->getServiceName($prefix, $tId);
                    $resolvedNames[] = $name ? $name : $tId;
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
        $sql = "SELECT c.sl_no as consultation_sl_no, c.consultation_id, c.patient_id, c.doctor_id, c.consultation_date, c.soap_objective, c.appointment_id,
                       p.*, d.full_name as doctor_name,
                       a.appointment_date, a.appointment_time, a.appointment_type, a.reason
                FROM consultations c
                JOIN patient p ON CONVERT(c.patient_id USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(p.patient_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
                LEFT JOIN doctors d ON CONVERT(c.doctor_id USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(d.doctor_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
                LEFT JOIN appointments a ON CONVERT(c.appointment_id USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(a.appointment_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
                WHERE c.soap_objective IS NOT NULL AND c.soap_objective != '' AND (c.appointment_id != 'LAB-MANUAL' OR c.appointment_id IS NULL)";
        
        $params = [];
        if ($patientId !== '') {
            $sql .= " AND c.patient_id = ?";
            $params[] = $patientId;
        }
        $sql .= " ORDER BY c.consultation_date DESC";
        
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
        $consultationId = 'CONS-' . date('Ymd') . '-' . rand(100, 999);

        // We embed priority, patient_type, and notes inside clinical_notes since consultations doesn't have these
        $combinedNotes = [];
        if (!empty($data['patient_type'])) $combinedNotes[] = $data['patient_type'];
        if (!empty($data['priority'])) $combinedNotes[] = "Priority: " . $data['priority'];
        if (!empty($data['clinical_notes'])) $combinedNotes[] = "Notes: " . $data['clinical_notes'];
        
        $clinicalNotesStr = implode(" | ", $combinedNotes);

        $appointmentId = 'LAB-MANUAL';
        if (strpos($data['patient_id'], 'WLK-') !== 0) {
            // It's an in-patient or registered patient, fetch their latest appointment
            $latestAppt = $this->db->fetchOne(
                "SELECT appointment_id FROM appointments WHERE patient_id = ? ORDER BY created_at DESC LIMIT 1",
                [$data['patient_id']]
            );
            if ($latestAppt && !empty($latestAppt['appointment_id'])) {
                $appointmentId = $latestAppt['appointment_id'];
            }
        }

        $res = $this->db->execute(
            "INSERT INTO consultations (consultation_id, patient_id, doctor_id, appointment_id, consultation_date, consultation_time, status, soap_objective, clinical_notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $consultationId,
                $data['patient_id'],
                $data['doctor_id'],
                $appointmentId, // Dynamically set appointment ID
                $data['order_date'],
                date('H:i:s'),
                1, // status active
                $data['test_name'], // Stores the JSON array of test IDs
                $clinicalNotesStr
            ]
        );

        if ($res && isset($res['insert_id'])) {
            $res['order_id'] = $consultationId; // Return consultation_id as the new order_id
        }

        return $res;
    }

    public function getOrderById($orderId)
    {
        $result = $this->db->fetchOne(
            "SELECT c.consultation_id AS order_id, 
                    c.soap_objective AS test_name, 
                    c.consultation_date AS order_date, 
                    c.status AS lab_status, 
                    c.clinical_notes AS notes,
                    c.patient_id, c.doctor_id,
                    CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
                    p.age, p.sex, p.phone,
                    d.full_name AS doctor_name, d.specialization
             FROM consultations c
             LEFT JOIN patient p  ON CONVERT(c.patient_id USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(p.patient_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
             LEFT JOIN doctors d  ON CONVERT(c.doctor_id USING utf8mb4) COLLATE utf8mb4_unicode_ci  = CONVERT(d.doctor_id USING utf8mb4) COLLATE utf8mb4_unicode_ci
             WHERE c.consultation_id = ?",
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
            $testIds = [];
            $rawTests = $result['test_name'];
            $decoded = json_decode($rawTests, true);
            if (is_array($decoded)) {
                $testIds = $decoded;
            } else {
                $testIds = array_map('trim', explode(',', $rawTests));
            }

            $resolvedNames = [];
            foreach ($testIds as $tId) {
                if (preg_match('/^(LAB|RDS|OTH)/i', $tId, $matches)) {
                    $prefix = strtoupper($matches[1]);
                    $name = $this->getServiceName($prefix, $tId);
                    $resolvedNames[] = $name ? $name : $tId;
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
        // For consultations, we embed priority, patient_type, and notes inside clinical_notes
        $combinedNotes = [];
        if (!empty($data['patient_type'])) $combinedNotes[] = $data['patient_type'];
        if (!empty($data['priority'])) $combinedNotes[] = "Priority: " . $data['priority'];
        if (!empty($data['clinical_notes'])) $combinedNotes[] = "Notes: " . $data['clinical_notes'];
        
        $clinicalNotesStr = implode(" | ", $combinedNotes);

        return $this->db->execute(
            "UPDATE consultations SET patient_id = ?, doctor_id = ?, soap_objective = ?, consultation_date = ?, clinical_notes = ? WHERE consultation_id = ?",
            [
                $data['patient_id'],
                $data['doctor_id'],
                $data['test_name'],
                $data['order_date'],
                $clinicalNotesStr,
                $orderId
            ]
        );
    }

    public function deleteOrder($id)
    {
        return $this->db->execute("DELETE FROM consultations WHERE consultation_id = ?", [$id]);
    }

    public function getOrderByConsultationRef($ref)
    {
        return null;
    }

    public function getLabResultByOrderId($orderId)
    {
        return $this->db->fetchOne(
            "SELECT * FROM lab_results WHERE order_id = ?",
            [$orderId]
        );
    }

    public function saveLabResult($data)
    {
        $existing = $this->getLabResultByOrderId($data['order_id']);
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
