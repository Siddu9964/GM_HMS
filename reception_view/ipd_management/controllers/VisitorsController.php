<?php
/**
 * Visitors Controller
 * 
 * Handles all API requests for visitor logs
 * 
 * @package IPD_Management\Controllers
 */

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/Visitor.php';

class VisitorsController extends BaseController {
    
    public function __construct() {
        $this->model = new Visitor();
    }
    
    protected function handleGet() {
        $id = $this->getParam('id');
        $patientId = $this->getParam('patient_id');
        
        if ($id) {
            $visitor = $this->model->getById($id);
            if (!$visitor) $this->error('Visitor not found', 404);
            $this->success($visitor);
        } else {
            $filters = [
                'patient_id' => $patientId,
                'admission_id' => $this->getParam('admission_id'),
                'visit_date' => $this->getParam('visit_date'),
                'search' => $this->getParam('search')
            ];
            
            $pagination = $this->getPagination();
            $visitors = $this->model->getAllWithDetails($filters, $pagination['limit'], $pagination['offset']);
            
            $this->success(['visitors' => $visitors]);
        }
    }
    
    protected function handlePost() {
        $data = $this->getRequestData();
        $result = $this->model->createVisitor($data);
        
        if ($result['success']) {
            $this->success(['visitor_id' => $result['visitor_id']], 'Visitor log created successfully', 201);
        } else {
            $this->error('Failed to create visitor log', 400, $result['errors']);
        }
    }
    
    protected function handlePut() {
        $id = $this->getParam('id');
        if (!$id) $this->error('Visitor ID is required', 400);
        
        $data = $this->getRequestData();
        $result = $this->model->update($id, $data);
        
        if ($result > 0) {
            $this->success(['visitor_id' => $id], 'Visitor log updated successfully');
        } else {
            $this->error('Failed to update visitor log', 400);
        }
    }
    
    protected function handleDelete() {
        $id = $this->getParam('id');
        if (!$id) $this->error('Visitor ID is required', 400);
        
        $result = $this->model->delete($id);
        
        if ($result > 0) {
            $this->success(null, 'Visitor log deleted successfully');
        } else {
            $this->error('Failed to delete visitor log', 400);
        }
    }
}
