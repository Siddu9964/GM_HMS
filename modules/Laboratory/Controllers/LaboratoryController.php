<?php
namespace GM_HMS\Modules\Laboratory\Controllers;

use Exception;
use GM_HMS\Controllers\BaseController;
use GM_HMS\Modules\Laboratory\Services\LaboratoryService;

class LaboratoryController extends BaseController
{
    private $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new LaboratoryService();
    }

    /**
     * GET /api/laboratory/services
     */
    public function getServices()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $data = $this->service->getAllServices();
            $this->respondSuccess($data);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * POST /api/laboratory/services
     */
    public function createService()
    {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $data = $this->getJsonInput();
            $type = $data['type'] ?? '';
            unset($data['type']);
            
            $result = $this->service->createService($type, $data);
            if ($result) {
                $this->respondSuccess(null, 'Service created successfully');
            } else {
                $this->respondBadRequest('Failed to create service or invalid type');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * PUT /api/laboratory/services/:id
     */
    public function updateService($type, $id)
    {
        $this->restrictMethod('PUT');
        $this->requireAuth();
        try {
            $data = $this->getJsonInput();
            
            $result = $this->service->updateService($type, $id, $data);
            if ($result) {
                $this->respondSuccess(null, 'Service updated successfully');
            } else {
                $this->respondNotFound('Service not found or update failed');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * DELETE /api/laboratory/services/:id
     */
    public function deleteService($type, $id)
    {
        $this->restrictMethod('DELETE');
        $this->requireAuth();
        try {
            $result = $this->service->deleteService($type, $id);
            if ($result) {
                $this->respondSuccess(null, 'Service deleted successfully');
            } else {
                $this->respondNotFound('Service not found or delete failed');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * PUT /api/laboratory/orders/:orderId/status
     */
    public function updateOrderStatus($orderId)
    {
        $this->restrictMethod('PUT');
        $this->requireAuth();

        try {
            $input = $this->getJsonInput();
            $status = $input['status'] ?? '';
            $allowed = ['Ordered', 'In Progress', 'Completed', 'Reported'];

            if (!in_array($status, $allowed)) {
                $this->respondBadRequest('Invalid status. Allowed: ' . implode(', ', $allowed));
            }

            $result = $this->service->updateOrderStatus($orderId, $status);

            if ($result) {
                $this->respondSuccess(null, 'Order status updated successfully');
            } else {
                $this->respondNotFound('Lab order not found or update failed');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/laboratory/orders
     */
    public function getOrders()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();

        try {
            $date     = $_GET['date']     ?? date('Y-m-d');
            $status   = $_GET['status']   ?? '';
            $priority = $_GET['priority'] ?? '';
            $search   = $_GET['search']   ?? '';
            $all      = $_GET['all']      ?? '0';

            $orders = $this->service->getOrders($all, $date, $status, $priority, $search);
            $this->respondSuccess($orders);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    /**
     * POST /api/laboratory/orders
     */
    public function createOrder()
    {
        $this->restrictMethod('POST');
        $this->requireAuth();
        
        try {
            $data = $this->getJsonInput();
            $order = $this->service->createOrder($data);
            $this->respondSuccess($order, 'Order created successfully');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/laboratory/orders/:id
     */
    public function getOrder($id)
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        
        try {
            $order = $this->service->getOrderById($id);
            $this->respondSuccess($order);
        } catch (Exception $e) {
            $this->respondNotFound($e->getMessage());
        }
    }

    /**
     * PUT /api/laboratory/orders/:id
     */
    public function updateOrder($id)
    {
        $this->restrictMethod('PUT');
        $this->requireAuth();
        
        try {
            $data = $this->getJsonInput();
            $order = $this->service->updateOrder($id, $data);
            $this->respondSuccess($order, 'Order updated successfully');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * DELETE /api/laboratory/orders/:id
     */
    public function deleteOrder($id)
    {
        $this->restrictMethod('DELETE');
        $this->requireAuth();
        
        try {
            $result = $this->service->deleteOrder($id);
            $this->respondSuccess(null, $result['message']);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/laboratory/dashboard
     */
    public function getDashboard()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();

        try {
            $stats = $this->service->getDashboardStats();
            $this->respondSuccess($stats);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/laboratory/prescribed-tests
     */
    public function getPrescribedTests()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        
        try {
            $patientId = $_GET['patient_id'] ?? '';
            $results = $this->service->getPrescribedTests($patientId);
            $this->respondSuccess(array_values($results));
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/laboratory/orders/:id/result
     */
    public function getResult($orderId)
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        
        try {
            $result = $this->service->getResult($orderId);
            $this->respondSuccess($result);
        } catch (Exception $e) {
            $this->respondNotFound($e->getMessage());
        }
    }

    /**
     * POST /api/laboratory/orders/:id/result
     */
    public function saveResult($orderId)
    {
        $this->restrictMethod('POST');
        $this->requireAuth();
        
        try {
            // Support form-data for file uploads and json strings
            $data = [];
            if (isset($_POST['result_data'])) {
                $data['result_data'] = $_POST['result_data'];
                $data['abnormal_flags'] = $_POST['abnormal_flags'] ?? null;
            } else {
                $data = $this->getJsonInput();
            }

            $file = $_FILES['report_file'] ?? null;
            
            $result = $this->service->saveResult($orderId, $data, $file);
            $this->respondSuccess($result, 'Result saved successfully');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
