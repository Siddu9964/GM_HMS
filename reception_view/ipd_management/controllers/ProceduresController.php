<?php
/**
 * Procedures Controller
 * 
 * Handles all API requests for medical procedures
 * 
 * @package IPD_Management\Controllers
 */

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/Procedure.php';

class ProceduresController extends BaseController {
    
    public function __construct() {
        $this->model = new Procedure();
    }
    
    protected function handleGet() {
        $id = $this->getParam('id');
        $admissionId = $this->getParam('admission_id');
        
        if ($id) {
            $procedure = $this->model->getById($id);
            if (!$procedure) {
                $this->error('Procedure not found', 404);
            }
            $this->success($procedure);
        } elseif ($admissionId) {
            $procedures = $this->model->getByAdmission($admissionId);
            $this->success(['procedures' => $procedures]);
        } else {
            $pagination = $this->getPagination();
            $filters = ['admission_id' => $this->getParam('admission_id'), 'doctor_id' => $this->getParam('doctor_id')];
            $procedures = $this->model->getAllWithDetails($filters, $pagination['limit'], $pagination['offset']);
            $this->success(['procedures' => $procedures]);
        }
    }
    
    protected function handlePost() {
        $data = $this->getRequestData();
        $result = $this->model->createProcedure($data);
        
        if ($result['success']) {
            $this->success(['procedure_id' => $result['procedure_id']], 'Procedure created successfully', 201);
        } else {
            $this->error('Failed to create procedure', 400, $result['errors']);
        }
    }
    
    protected function handlePut() {
        $id = $this->getParam('id');
        if (!$id) $this->error('Procedure ID is required', 400);
        
        $data = $this->getRequestData();
        $result = $this->model->update($id, $data);
        
        if ($result > 0) {
            $this->success(['procedure_id' => $id], 'Procedure updated successfully');
        } else {
            $this->error('Failed to update procedure', 400);
        }
    }
    
    protected function handleDelete() {
        $id = $this->getParam('id');
        if (!$id) $this->error('Procedure ID is required', 400);
        
        $result = $this->model->delete($id);
        
        if ($result > 0) {
            $this->success(null, 'Procedure deleted successfully');
        } else {
            $this->error('Failed to delete procedure', 400);
        }
    }
}
