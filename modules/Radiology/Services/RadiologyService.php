<?php
namespace GM_HMS\Modules\Radiology\Services;

use GM_HMS\Modules\Radiology\Repositories\RadiologyRepository;
use Exception;

class RadiologyService
{
    private $repo;

    public function __construct()
    {
        $this->repo = new RadiologyRepository();
    }

    public function getAllServices($modality = null)
    {
        return [
            'services'   => $this->repo->getRadiologyServices($modality),
            'modalities' => $this->repo->getModalities()
        ];
    }

    public function createService($data)
    {
        return $this->repo->createService($data);
    }

    public function updateService($id, $data)
    {
        return $this->repo->updateService($id, $data);
    }

    public function deleteService($id)
    {
        return $this->repo->deleteService($id);
    }

    public function getOrders($all, $date, $status, $priority, $search, $modality = '')
    {
        return $this->repo->getOrders($all, $date, $status, $priority, $search, $modality);
    }

    public function getOrderById($orderId)
    {
        $order = $this->repo->getOrderById($orderId);
        if (!$order) {
            throw new Exception("Radiology order not found");
        }
        return $order;
    }

    public function createOrder($data)
    {
        if (!empty($data['admission_id'])) {
            $db = \GM_HMS\Database\SecureDatabase::getInstance();
            $adm = $db->fetchOne("SELECT status, discharge_date FROM ipd_admissions WHERE admission_id = ?", [$data['admission_id']]);
            if ($adm && ($adm['status'] === 'Discharged' || !empty($adm['discharge_date']))) {
                throw new Exception("This patient has already been discharged.");
            }
        }

        $data['order_date'] = $data['order_date'] ?? date('Y-m-d');
        $data['status'] = $data['status'] ?? 'Ordered';
        $data['priority'] = $data['priority'] ?? 'Routine';
        $data['clinical_notes'] = $data['clinical_notes'] ?? '';

        $result = $this->repo->createOrder($data);
        if (!$result) {
            throw new Exception("Failed to create radiology order");
        }
        return $result;
    }

    public function updateOrderStatus($orderId, $status)
    {
        $res = $this->repo->updateOrderStatus($orderId, $status);
        if (!$res) {
            throw new Exception("Failed to update radiology order status");
        }
        if (in_array(strtolower(trim($status)), ['completed', 'reported'])) {
            $this->repo->markNotificationCompletedForOrder($orderId);
        }
        return ['success' => true];
    }

    public function getIpdOrders($all, $date, $statusFilter = 'all', $search = '', $modality = '')
    {
        return $this->repo->getIpdOrders($all, $date, $statusFilter, $search, $modality);
    }

    public function updateIpdOrderStatus($orderId, $status)
    {
        $res = $this->repo->updateIpdOrderStatus($orderId, $status);
        if (in_array(strtolower(trim($status)), ['completed', 'reported'])) {
            $this->repo->markNotificationCompletedForOrder($orderId);
        }
        return $res;
    }

    public function getResult($orderId)
    {
        $res = (strpos($orderId, 'IPD-') === 0) 
            ? $this->repo->getIpdRadiologyResultByOrderId($orderId)
            : $this->repo->getRadiologyResultByOrderId($orderId);

        if ($res && !empty($res['result_data'])) {
            $parsed = is_array($res['result_data']) ? $res['result_data'] : json_decode($res['result_data'], true);
            if (is_array($parsed)) {
                if (empty($res['technique']) && !empty($parsed['technique'])) $res['technique'] = $parsed['technique'];
                if (empty($res['clinical_history']) && !empty($parsed['clinical_history'])) $res['clinical_history'] = $parsed['clinical_history'];
                if (empty($res['findings']) && !empty($parsed['findings'])) $res['findings'] = $parsed['findings'];
                if (empty($res['impression']) && !empty($parsed['impression'])) $res['impression'] = $parsed['impression'];
                if (empty($res['modality']) && !empty($parsed['modality'])) $res['modality'] = $parsed['modality'];
            }
        }
        return $res;
    }

    public function saveResult($orderId, $data, $file = null)
    {
        $isIpd = (strpos($orderId, 'IPD-') === 0);
        $patientId = $data['patient_id'] ?? '';
        $testName = $data['test_name'] ?? '';

        if (!$isIpd) {
            $order = $this->repo->getOrderById($orderId);
            if ($order) {
                $patientId = $patientId ?: ($order['patient_id'] ?? '');
                $testName = $testName ?: ($order['resolved_test_names'] ?: ($order['test_name'] ?? ''));
            }
        } else {
            if (empty($patientId) || empty($testName)) {
                $ipdOrders = $this->repo->getIpdOrders('1', date('Y-m-d'), 'all', '', '');
                foreach ($ipdOrders as $io) {
                    if ($io['order_id'] === $orderId) {
                        if (empty($patientId)) $patientId = $io['patient_id'] ?? '';
                        if (empty($testName)) $testName = $io['resolved_test_names'] ?? ($io['test_name'] ?? '');
                        break;
                    }
                }
            }
            if (empty($patientId) || empty($testName)) {
                $rawId = preg_replace('/[^0-9]/', '', $orderId);
                if (!empty($rawId)) {
                    $db = (new \Database())->connect();
                    $cr = $db->fetchOne("SELECT patient_id, radiology_tests, lab_tests FROM ipd_clinical_records WHERE id = ?", [$rawId]);
                    if ($cr) {
                        if (empty($patientId)) $patientId = $cr['patient_id'] ?? '';
                        if (empty($testName)) {
                            $rt = @json_decode($cr['radiology_tests'] ?? '', true);
                            if (is_array($rt) && !empty($rt[0])) {
                                $tName = $rt[0]['data']['name'] ?? $rt[0]['name'] ?? '';
                                $tId = $rt[0]['data']['id'] ?? $rt[0]['id'] ?? '';
                                if ($tId) $tName .= " ($tId)";
                                $testName = $tName;
                            }
                        }
                    }
                }
            }
        }

        // Unpack structured clinical narrative from result_data if not explicitly passed
        if (!empty($data['result_data'])) {
            $parsed = is_array($data['result_data']) ? $data['result_data'] : json_decode($data['result_data'], true);
            if (is_array($parsed)) {
                if (empty($data['technique']) && !empty($parsed['technique'])) $data['technique'] = $parsed['technique'];
                if (empty($data['clinical_history']) && !empty($parsed['clinical_history'])) $data['clinical_history'] = $parsed['clinical_history'];
                if (empty($data['findings']) && !empty($parsed['findings'])) $data['findings'] = $parsed['findings'];
                if (empty($data['impression']) && !empty($parsed['impression'])) $data['impression'] = $parsed['impression'];
                if (empty($data['modality']) && !empty($parsed['modality'])) $data['modality'] = $parsed['modality'];
            }
        }

        $reportFilePath = null;
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $subFolder = $isIpd ? 'ip_patient' : 'op_patient';
            $uploadDir = __DIR__ . '/../../../assets/radiology_reports/' . $subFolder . '/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = 'rad_report_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $orderId) . '_' . time() . '.' . $ext;
            $destination = $uploadDir . $filename;
            $uploaded = is_uploaded_file($file['tmp_name']) 
                ? move_uploaded_file($file['tmp_name'], $destination) 
                : copy($file['tmp_name'], $destination);
            if ($uploaded) {
                $reportFilePath = 'assets/radiology_reports/' . $subFolder . '/' . $filename;
            }
        }

        $resultPayload = [
            'order_id'         => $orderId,
            'patient_id'       => $patientId,
            'test_name'        => $testName ?: 'Radiology Examination',
            'modality'         => $data['modality'] ?? 'X-RAY',
            'clinical_history' => $data['clinical_history'] ?? null,
            'technique'        => $data['technique'] ?? null,
            'findings'         => $data['findings'] ?? null,
            'impression'       => $data['impression'] ?? null,
            'result_data'      => isset($data['result_data']) ? (is_string($data['result_data']) ? $data['result_data'] : json_encode($data['result_data'])) : null,
            'status'           => $data['status'] ?? 'Reported',
            'result_date'      => date('Y-m-d'),
            'result_time'      => date('H:i:s'),
            'reviewed_by'      => $data['reporting_radiologist'] ?? $data['reviewed_by'] ?? ($_SESSION['user_id'] ?? null),
        ];

        if ($reportFilePath) {
            $resultPayload['report_file'] = $reportFilePath;
        }

        $this->repo->saveResult($resultPayload);
        if (!$isIpd) {
            $this->repo->updateOrderStatus($orderId, 'Reported');
        } else {
            $this->repo->updateIpdOrderStatus($orderId, 'Reported');
        }
        $this->repo->markNotificationCompletedForOrder($orderId, $patientId);

        return ['success' => true, 'report_file' => $reportFilePath];
    }

    public function getPatientPreviousResults($patientId)
    {
        return $this->repo->getPatientPreviousResults($patientId);
    }

    public function getDashboardStats()
    {
        return $this->repo->getDashboardStats();
    }

    public function getUnreadNotifications($recipientType = 'staff', $category = 'radiology_result', $onlyToday = true)
    {
        return $this->repo->getUnreadNotifications($recipientType, $category, $onlyToday);
    }

    public function markNotificationRead($id)
    {
        return $this->repo->markNotificationRead($id);
    }
}
