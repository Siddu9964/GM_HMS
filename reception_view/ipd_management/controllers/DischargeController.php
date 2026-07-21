<?php
/**
 * Discharge Controller
 * 
 * Handles all API requests for discharge management
 * 
 * @package IPD_Management\Controllers
 */

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/Discharge.php';

class DischargeController extends BaseController {
    
    public function __construct() {
        $this->model = new Discharge();
    }
    
    protected function handleGet() {
        $admissionId = $this->getParam('admission_id');
        $summary = $this->getParam('summary');
        
        if (!$admissionId) {
            $this->error('Admission ID is required', 400);
        }
        
        if ($summary) {
            // Get comprehensive discharge summary
            $dischargeSummary = $this->model->generateSummary($admissionId);
            
            if (!$dischargeSummary) {
                $this->error('Admission not found', 404);
            }
            
            $this->success($dischargeSummary, 'Discharge summary generated successfully');
        } else {
            // Get discharge details
            $discharge = $this->model->getByAdmission($admissionId);
            
            if (!$discharge) {
                $this->error('Discharge details not found', 404);
            }
            
            $this->success($discharge, 'Discharge details retrieved successfully');
        }
    }
    
    protected function handlePost() {
        $data = $this->getRequestData();
        $result = $this->model->createDischarge($data);
        
        if ($result['success']) {
            $this->success(['discharge_id' => $result['discharge_id']], 'Discharge record created successfully', 201);
        } else {
            $this->error('Failed to create discharge record', 400, $result['errors']);
        }
    }
    
    protected function handlePut() {
        $id = $this->getParam('id');
        if (!$id) $this->error('Discharge ID is required', 400);
        
        $data = $this->getRequestData();
        $result = $this->model->update($id, $data);
        
        if ($result > 0) {
            $this->success(['discharge_id' => $id], 'Discharge record updated successfully');
        } else {
            $this->error('Failed to update discharge record', 400);
        }
    }
}
