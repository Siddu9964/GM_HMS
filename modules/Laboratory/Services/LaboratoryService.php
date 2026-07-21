<?php
namespace GM_HMS\Modules\Laboratory\Services;

use GM_HMS\Modules\Laboratory\Repositories\LaboratoryRepository;

class LaboratoryService
{
    private $repo;

    public function __construct()
    {
        $this->repo = new LaboratoryRepository();
    }

    public function getAllServices()
    {
        return [
            'lab'       => $this->repo->getLabServices(),
            'radiology' => $this->repo->getRadiologyServices(),
            'other'     => $this->repo->getOtherServices()
        ];
    }

    public function deleteService($type, $id)
    {
        switch (strtoupper($type)) {
            case 'LAB': return $this->repo->deleteLabService($id);
            case 'RADIOLOGY': return $this->repo->deleteRadiologyService($id);
            case 'OTHER': return $this->repo->deleteOtherService($id);
            default: return false;
        }
    }

    public function createService($type, $data)
    {
        switch (strtoupper($type)) {
            case 'LAB': return $this->repo->createLabService($data);
            case 'RADIOLOGY': return $this->repo->createRadiologyService($data);
            case 'OTHER': return $this->repo->createOtherService($data);
            default: return false;
        }
    }

    public function updateService($type, $id, $data)
    {
        switch (strtoupper($type)) {
            case 'LAB': return $this->repo->updateLabService($id, $data);
            case 'RADIOLOGY': return $this->repo->updateRadiologyService($id, $data);
            case 'OTHER': return $this->repo->updateOtherService($id, $data);
            default: return false;
        }
    }

    public function updateOrderStatus($orderId, $status)
    {
        $result = $this->repo->updateOrderStatus($orderId, $status);
        if (!$result) {
            throw new Exception("Failed to update order status");
        }
        return ['success' => true, 'message' => 'Status updated successfully'];
    }

    public function createOrder($data)
    {
        $data['order_date'] = $data['order_date'] ?? date('Y-m-d');
        $data['status'] = $data['status'] ?? 'Ordered';
        $data['priority'] = $data['priority'] ?? 'Routine';
        $data['clinical_notes'] = $data['clinical_notes'] ?? '';

        $result = $this->repo->createOrder($data);
        if (!$result) {
            throw new Exception("Failed to create lab order");
        }
        
        $insertId = $result['insert_id'] ?? null;
        if ($insertId) {
            return $this->repo->getOrderById($insertId);
        }
        return ['success' => true];
    }

    public function getOrderById($orderId)
    {
        $order = $this->repo->getOrderById($orderId);
        if (!$order) {
            throw new Exception("Order not found");
        }
        return $order;
    }

    public function updateOrder($orderId, $data)
    {
        $existing = $this->getOrderById($orderId);
        
        $updateData = [
            'patient_id' => $data['patient_id'] ?? $existing['patient_id'],
            'doctor_id' => $data['doctor_id'] ?? $existing['doctor_id'],
            'test_name' => $data['test_name'] ?? $existing['test_name'],
            'order_date' => $data['order_date'] ?? $existing['order_date'],
            'status' => $data['status'] ?? $existing['status'],
            'priority' => $data['priority'] ?? $existing['priority'],
            'clinical_notes' => $data['clinical_notes'] ?? $existing['clinical_notes']
        ];

        $result = $this->repo->updateOrder($orderId, $updateData);
        if (!$result) {
            throw new Exception("Failed to update order");
        }

        return $this->repo->getOrderById($orderId);
    }

    public function deleteOrder($orderId)
    {
        $existing = $this->getOrderById($orderId);
        
        $result = $this->repo->deleteOrder($orderId);
        if (!$result) {
            throw new Exception("Failed to delete order");
        }
        
        return ['success' => true, 'message' => 'Order deleted successfully'];
    }

    public function getOrders($all, $date, $status, $priority, $search)
    {
        $this->syncConsultationsToLabOrders();
        return $this->repo->getOrders($all, $date, $status, $priority, $search);
    }

    public function getDashboardStats()
    {
        $this->syncConsultationsToLabOrders();

        $labCount = $this->repo->getLabServicesCount();
        $radCount = $this->repo->getRadiologyServicesCount();
        $othCount = $this->repo->getOtherServicesCount();

        $ordersToday = $this->repo->getOrdersTodayCount();
        $pending = $this->repo->getPendingOrdersCount();
        $completed = $this->repo->getCompletedOrdersTodayCount();
        $urgentToday = $this->repo->getUrgentOrdersTodayCount();
        $monthPatients = $this->repo->getMonthPatientsCount();

        $trend = $this->repo->getDailyTrend();
        $topTests = $this->repo->getTopTests();
        $recent = $this->repo->getRecentOrders();

        return [
            'stats' => [
                'lab_services'    => (int)($labCount['cnt'] ?? 0),
                'radiology'       => (int)($radCount['cnt'] ?? 0),
                'other'           => (int)($othCount['cnt'] ?? 0),
                'orders_today'    => (int)($ordersToday['cnt'] ?? 0),
                'pending'         => (int)($pending['cnt'] ?? 0),
                'completed_today' => (int)($completed['cnt'] ?? 0),
                'urgent_today'    => (int)($urgentToday['cnt'] ?? 0),
                'month_patients'  => (int)($monthPatients['cnt'] ?? 0),
            ],
            'trend'     => $trend,
            'top_tests' => $topTests,
            'recent'    => $recent,
        ];
    }

    public function getPrescribedTests($patientId = '')
    {
        $results = $this->repo->getPrescribedTests($patientId);

        foreach ($results as &$row) {
            $idsString = $row['soap_objective'];
            $ids = array_filter(array_map('trim', explode(',', $idsString)));
            $resolvedNames = [];

            foreach ($ids as $id) {
                if (strpos($id, 'LAB') === 0) {
                    $s = $this->repo->getServiceName('LAB', $id);
                    $resolvedNames[] = $s ? $s['test_name'] : $id;
                } elseif (strpos($id, 'RDS') === 0) {
                    $s = $this->repo->getServiceName('RDS', $id);
                    $resolvedNames[] = $s ? $s['billing_name'] : $id;
                } elseif (strpos($id, 'OTH') === 0) {
                    $s = $this->repo->getServiceName('OTH', $id);
                    $resolvedNames[] = $s ? $s['billing_name'] : $id;
                } else {
                    $resolvedNames[] = $id;
                }
            }

            $row['resolved_test_names'] = implode(', ', $resolvedNames);
            $row['test_names_array'] = $resolvedNames;
        }

        return array_filter($results, function($r) {
            return count($r['test_names_array']) > 0;
        });
    }

    public function syncConsultationsToLabOrders()
    {
        // Obsolete: Consultations table is now the single source of truth for lab orders.
        return;
    }
    public function getResult($orderId)
    {
        return $this->repo->getLabResultByOrderId($orderId);
    }

    public function saveResult($orderId, $data, $file = null)
    {
        $order = $this->repo->getOrderById($orderId);
        if (!$order) {
            throw new \Exception("Order not found");
        }

        $reportFilePath = null;
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../assets/lab_report/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'report_' . $orderId . '_' . time() . '.' . $ext;
            $destination = $uploadDir . $filename;
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $reportFilePath = 'assets/lab_report/' . $filename;
            } else {
                throw new \Exception("Failed to upload report file");
            }
        }

        $resultData = [
            'order_id' => $orderId,
            'patient_id' => $order['patient_id'],
            'test_name' => $order['test_name'],
            'result_data' => $data['result_data'] ?? null,
            'abnormal_flags' => $data['abnormal_flags'] ?? null,
            'status' => 'Reviewed',
        ];

        if ($reportFilePath) {
            $resultData['report_file'] = $reportFilePath;
        }

        $this->repo->saveLabResult($resultData);
        $this->repo->updateOrderStatus($orderId, 'Reported');
        return ['success' => true];
    }
}
