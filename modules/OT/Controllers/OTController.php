<?php
namespace GM_HMS\Modules\OT\Controllers;

use Exception;
use GM_HMS\Controllers\BaseController;
use GM_HMS\Modules\OT\Services\OTService;

class OTController extends BaseController
{
    private $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new OTService();
    }

    /**
     * GET /api/ot/dashboard
     */
    public function getDashboard()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $stats = $this->service->getDashboardStats();
            $this->respondSuccess($stats);
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/ot/rooms
     * Fetch available OT rooms from hospital_beds
     */
    public function getOTRooms()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $rooms = $this->service->getOTRooms();
            $this->respondSuccess($rooms);
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/ot/surgeries
     */
    public function getSurgeries()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $date   = $_GET['date']   ?? null;
            $status = $_GET['status'] ?? '';
            $search = $_GET['search'] ?? '';
            $data   = $this->service->getSurgeries($date, $status, $search);
            $this->respondSuccess($data);
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/ot/roles
     */
    public function getDoctorRoles()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $data = $this->service->getDistinctDoctorRoles();
            $this->respondSuccess($data);
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/ot/surgeries/:id
     */
    public function getSurgery($id)
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $data = $this->service->getSurgeryById($id);
            $this->respondSuccess($data);
        } catch (\Throwable $e) {
            $this->respondNotFound($e->getMessage());
        }
    }

    /**
     * POST /api/ot/surgeries
     */
    public function createSurgery()
    {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $data   = $this->getJsonInput();
            $result = $this->service->createSurgery($data);
            $this->respondSuccess($result, 'Surgery scheduled successfully');
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * PUT /api/ot/surgeries/:id
     */
    public function updateSurgery($id)
    {
        $this->restrictMethod('PUT');
        $this->requireAuth();
        try {
            $data   = $this->getJsonInput();
            $result = $this->service->updateSurgery($id, $data);
            $this->respondSuccess($result, 'Surgery updated successfully');
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * PUT /api/ot/surgeries/:id/status
     */
    public function updateStatus($id)
    {
        $this->restrictMethod('PUT');
        $this->requireAuth();
        try {
            $input  = $this->getJsonInput();
            $status = $input['status']             ?? '';
            $reason = $input['status_reason_note'] ?? null;
            $result = $this->service->updateStatus($id, $status, $reason);
            $this->respondSuccess($result, 'Surgery status updated successfully');
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * PUT /api/ot/surgeries/:id/time
     */
    public function updateTime($id)
    {
        $this->restrictMethod('PUT');
        $this->requireAuth();
        try {
            $data = $this->getJsonInput();
            $result = $this->service->updateTime($id, $data);
            $this->respondSuccess($result, 'Surgery time updated successfully');
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * DELETE /api/ot/surgeries/:id
     */
    public function deleteSurgery($id)
    {
        $this->restrictMethod('DELETE');
        $this->requireAuth();
        try {
            $result = $this->service->deleteSurgery($id);
            $this->respondSuccess(null, $result['message']);
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }
    /**
     * GET /api/ot/patient/search/:id
     */
    public function searchPatient($patientId)
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $result = $this->service->searchPatient($patientId);
            $this->respondSuccess($result);
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/ot/patient/search-list
     */
    public function searchPatientsList()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $query = $_GET['q'] ?? '';
            $result = $this->service->searchPatientsList($query);
            $this->respondSuccess($result);
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/ot/pharmacy/search
     */
    public function searchPharmacyProducts()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $query = $_GET['q'] ?? '';
            $result = $this->service->searchPharmacyProducts($query);
            $this->respondSuccess($result);
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * POST /api/ot/pharmacy-order
     */
    public function createPharmacyOrder()
    {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $data = $this->getJsonInput();
            $userId = $_SESSION['user_id'] ?? 0;
            $result = $this->service->createPharmacyOrder($data, $userId);
            $this->respondSuccess($result, 'Pharmacy order created successfully');
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    public function getPendingPharmacyOrders()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $patientId = $_GET['patient_id'] ?? '';
            $admissionId = $_GET['admission_id'] ?? '';
            $result = $this->service->getPendingPharmacyOrders($patientId, $admissionId);
            $this->respondSuccess($result);
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    public function editPharmacyOrderItem()
    {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['patient_id']) || empty($input['unique_id']) || empty($input['qty'])) {
                $this->respondError('Missing required fields.', 400);
            }
            
            $userId = $_SESSION['user_id'] ?? 1;
            $this->service->editPharmacyOrderItem(
                $input['patient_id'],
                $input['admission_id'] ?? '',
                $input['unique_id'],
                $input['qty'],
                $userId
            );
            $this->respondSuccess(null, 'Order item updated successfully.');
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    public function deletePharmacyOrderItem()
    {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['patient_id']) || empty($input['unique_id'])) {
                $this->respondError('Missing required fields.', 400);
            }
            
            $userId = $_SESSION['user_id'] ?? 1;
            $this->service->deletePharmacyOrderItem(
                $input['patient_id'],
                $input['admission_id'] ?? '',
                $input['unique_id'],
                $userId
            );
            $this->respondSuccess(null, 'Order item deleted successfully.');
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    public function reconcilePharmacyOrder()
    {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $data = $this->getJsonInput();
            $userId = $_SESSION['user_id'] ?? 0;
            $result = $this->service->reconcilePharmacyOrder($data, $userId);
            $this->respondSuccess($result, 'Pharmacy order reconciled successfully');
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    public function getAllPharmacyOrders()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $result = $this->service->getAllPharmacyOrders();
            $this->respondSuccess($result);
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * POST /api/ot/check-room
     */
    public function checkRoom()
    {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $data = $this->getJsonInput();
            $result = $this->service->checkRoom($data);
            $this->respondSuccess($result);
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }
    /**
     * GET /api/ot/departments
     */
    public function getDepartments()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $data = $this->service->getDistinctDepartments();
            $this->respondSuccess($data);
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }
}
