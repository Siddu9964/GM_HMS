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
     * GET /api/laboratory/services/parameters/:serviceId
     */
    public function getTestParameters($serviceId)
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $serviceId = urldecode($serviceId);
            $data = $this->service->getLabTestParameters($serviceId);
            $this->respondSuccess($data);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * POST /api/laboratory/services/parameters/:serviceId
     */
    public function saveTestParameters($serviceId)
    {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $body = $this->getJsonInput();
            if (!isset($body['parameters']) || !is_array($body['parameters'])) {
                $this->respondError('Invalid or missing parameters array', 400);
            }
            $this->service->saveTestParameters($serviceId, $body['parameters']);
            $this->respondSuccess(['message' => 'Parameters saved successfully']);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            exit;
        }
    }

    /**
     * POST /api/laboratory/services/auto-generate-parameters
     */
    public function autoGenerateParameters()
    {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $body = $this->getJsonInput();
            $testName = $body['test_name'] ?? '';
            
            if (empty($testName)) {
                $this->respondError('Test name is required', 400);
            }

            require_once __DIR__ . '/../../../config/gemini_config.php';
            
            $prompt = "You are a laboratory information system assistant. Given the lab test name '{$testName}', provide a comprehensive list of standard parameters for this test. Return ONLY a raw JSON array of objects without any markdown formatting or code blocks. Each object must strictly have these keys: 'parameter_name', 'unit', 'normal_range', 'normal_range_male', 'normal_range_female', 'normal_range_child', 'normal_range_newborn', 'normal_range_Infant(29 days–12 months)', 'normal_range_toddler(1–3 years)', 'normal_range_preschool_child(4–5 years)', 'normal_range_school_child(6–12 years)', 'normal_range_adolescent(13–17 years)', 'normal_range_adult(18–59 years)', 'normal_range_elderly(60–74 years)', 'normal_range_senior_elderly(75+ years)'. Leave age fields as null or empty string if not applicable.";
            
            $payload = [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ]
            ];
            
            $ch = curl_init(getGeminiApiUrl());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200 || !$response) {
                $errorMsg = 'Failed to communicate with AI service.';
                if ($response) {
                    $errData = json_decode($response, true);
                    if (isset($errData['error']['message'])) {
                        $errorMsg .= ' API Error: ' . $errData['error']['message'];
                    }
                }
                $this->respondError($errorMsg, 200);
            }
            
            $resultData = json_decode($response, true);
            $textResp = $resultData['candidates'][0]['content']['parts'][0]['text'] ?? '';
            
            // Robustly extract the JSON array part from the AI response
            $start = strpos($textResp, '[');
            $end = strrpos($textResp, ']');
            if ($start !== false && $end !== false && $end > $start) {
                $textResp = substr($textResp, $start, $end - $start + 1);
            }
            $textResp = trim($textResp);
            
            $parameters = json_decode($textResp, true);
            
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($parameters)) {
                $this->respondError('Failed to parse AI response as JSON', 200);
            }
            
            $this->respondSuccess($parameters);
            
        } catch (\Throwable $e) {
            $this->respondError($e->getMessage(), 200);
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
     * GET /api/laboratory/ipd-orders
     */
    public function getIpdOrders()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        
        try {
            $all = $_GET['all'] ?? '0';
            $date = $_GET['date'] ?? date('Y-m-d');
            $statusFilter = $_GET['status'] ?? 'all';
            $search = $_GET['search'] ?? '';
            $orders = $this->service->getIpdOrders($all, $date, $statusFilter, $search);
            $this->respondSuccess($orders);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * PUT /api/laboratory/ipd-orders/:id/status
     */
    public function updateIpdOrderStatus($orderId)
    {
        $this->restrictMethod('PUT');
        $this->requireAuth();
        
        try {
            $data = $this->getJsonInput();
            if (!isset($data['status'])) {
                $this->respondError("Status is required", 400);
            }
            $this->service->updateIpdOrderStatus($orderId, $data['status']);
            $this->respondSuccess(null, "Status updated successfully");
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
     * GET /api/laboratory/patients/:id/previous-results
     */
    public function getPreviousResults($patientId)
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        
        try {
            $testName = $_GET['test_name'] ?? null;
            $result = $this->service->getPatientPreviousResults($patientId, $testName);
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

    public function getNotifications()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        
        try {
            // Only LabTechnician should see these notifications
            if (($_SESSION['role'] ?? '') !== 'LabTechnician') {
                $this->respondSuccess([]);
                return;
            }
            
            // We use 'staff' as recipient_type for LabTechnician role
            $notifications = $this->service->getUnreadNotifications('staff', 'lab_result');
            $this->respondSuccess($notifications);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function markNotificationRead($id)
    {
        $this->restrictMethod('POST');
        $this->requireAuth();
        
        try {
            if (($_SESSION['role'] ?? '') !== 'LabTechnician') {
                $this->respondError("Unauthorized", 403);
            }
            $this->service->markNotificationRead($id);
            $this->respondSuccess([], 'Notification marked as read');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
