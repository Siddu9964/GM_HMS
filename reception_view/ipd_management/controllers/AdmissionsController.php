<?php
/**
 * Admissions Controller
 * 
 * Handles all API requests for IPD admissions
 * 
 * @package IPD_Management\Controllers
 */

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/Admission.php';

class AdmissionsController extends BaseController {
    
    public function __construct() {
        $this->model = new Admission();
    }
    
    /**
     * Handle GET requests
     * GET /api/admissions - List all admissions
     * GET /api/admissions?id=123 - Get specific admission
     */
    protected function handleGet() {
        $id = $this->getParam('id');
        
        if ($id) {
            // Get specific admission - id can be admission_id (string) or sl_no (int)
            $admission = $this->model->getByIdWithDetails($id);
            
            if (!$admission) {
                $this->error('Admission not found', 404);
            }
            
            // Get financial details
            $balance = $this->model->getBalance($id);
            $admission['financials'] = $balance;
            
            $this->success($admission, 'Admission retrieved successfully');
        } elseif ($this->getParam('action') === 'get_latest_doctor') {
            require_once __DIR__ . '/../models/Patient.php';
            $patientModel = new Patient();
            $doctor = $patientModel->getLatestDoctor($this->getParam('patient_id'));
            $this->success($doctor ? $doctor : null);
        } else {
            // List admissions with filters
            $filters = [
                'status' => $this->getParam('status'),
                'patient_id' => $this->getParam('patient_id'),
                'doctor_id' => $this->getParam('doctor_id'),
                'search' => $this->getParam('search')
            ];
            
            $pagination = $this->getPagination();
            
            $admissions = $this->model->getAllWithDetails(
                $filters,
                $pagination['limit'],
                $pagination['offset']
            );
            
            $total = $this->model->count(array_filter($filters, function($v) { return $v !== null && $v !== ''; }));
            
            $this->success([
                'admissions' => $admissions,
                'pagination' => [
                    'page' => $pagination['page'],
                    'limit' => $pagination['limit'],
                    'total' => $total,
                    'pages' => ceil($total / $pagination['limit'])
                ]
            ], 'Admissions retrieved successfully');
        }
    }
    
    /**
     * Handle POST requests
     * POST /api/admissions - Create new admission
     * POST /api/admissions/discharge - Discharge patient
     */
    protected function handlePost() {
        $data = $this->getRequestData();
        $action = $this->getParam('action');
        
        if ($action === 'discharge') {
            // Discharge patient
            $admissionId = $data['admission_id'] ?? null;
            
            if (!$admissionId) {
                $this->error('Admission ID is required', 400);
            }
            
            $result = $this->model->dischargePatient($admissionId, $data);
            
            if ($result['success']) {
                $this->success([
                    'admission_id' => $admissionId,
                    'discharge_date' => $result['discharge_date'] ?? date('Y-m-d')
                ], $result['message'] ?? 'Patient discharged successfully');
            } else {
                // Send detailed error message
                $errorMessage = isset($result['errors']) && is_array($result['errors']) 
                    ? implode(', ', $result['errors']) 
                    : 'Failed to discharge patient';
                $this->error($errorMessage, 400);
            }
        } else {
            // Create new admission
            $result = $this->model->createAdmission($data);
            
            if ($result['success']) {
                $this->success([
                    'admission_id' => $result['admission_id']
                ], 'Admission created successfully', 201);
            } else {
                $this->error('Failed to create admission', 400, $result['errors']);
            }
        }
    }
    
    /**
     * Handle PUT requests
     * PUT /api/admissions?id=123 - Update admission
     */
    protected function handlePut() {
        $id = $this->getParam('id');
        
        if (!$id) {
            $this->error('Admission ID is required', 400);
        }
        
        // Convert admission_id to sl_no if needed
        $slNo = $this->model->getSlNoFromId($id);
        if (!$slNo) {
            $this->error('Admission not found', 404);
        }
        
        $data = $this->getRequestData();
        
        try {
            $result = $this->model->updateAdmission($slNo, $data);
            
            if ($result > 0) {
                $this->success(['admission_id' => $id], 'Admission updated successfully');
            } else {
                $this->error('Failed to update admission or no changes made', 400);
            }
        } catch (Exception $e) {
            $this->error($e->getMessage(), 400);
        }
    }
    
    /**
     * Handle DELETE requests
     * DELETE /api/admissions?id=123 - Delete admission
     */
    protected function handleDelete() {
        $id = $this->getParam('id');
        
        if (!$id) {
            $this->error('Admission ID is required', 400);
        }
        
        // Convert admission_id to sl_no if needed
        $slNo = $this->model->getSlNoFromId($id);
        if (!$slNo) {
            $this->error('Admission not found', 404);
        }
        
        // Use custom delete method that handles foreign key constraints
        $result = $this->model->deleteAdmission($slNo);
        
        if ($result['success']) {
            $this->success(null, 'Admission deleted successfully');
        } else {
            $this->error('Failed to delete admission: ' . ($result['error'] ?? 'Unknown error'), 400);
        }
    }
}
