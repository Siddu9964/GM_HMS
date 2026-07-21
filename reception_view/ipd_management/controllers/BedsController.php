<?php
/**
 * Beds Controller
 * 
 * Handles all API requests for hospital beds
 * 
 * @package IPD_Management\Controllers
 */

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/Bed.php';

class BedsController extends BaseController {
    
    public function __construct() {
        $this->model = new Bed();
    }
    
    /**
     * Handle GET requests
     * GET /api/beds - List all beds
     * GET /api/beds?available=1 - Get available beds
     * GET /api/beds?stats=1 - Get bed statistics
     */
    protected function handleGet() {
        $available = $this->getParam('available');
        $stats = $this->getParam('stats');
        
        if ($stats) {
            // Get bed occupancy statistics
            $occupancy = $this->model->getBedOccupancy();
            $byWard = $this->model->getOccupancyByWard();
            
            $this->success([
                'overall' => $occupancy,
                'by_ward' => $byWard
            ], 'Bed statistics retrieved successfully');
        } elseif ($available) {
            // Get available beds
            $bedType = $this->getParam('bed_type');
            $beds = $this->model->getAvailableBeds($bedType);
            
            $this->success($beds, 'Available beds retrieved successfully');
        } else {
            // List all beds with filters
            $filters = [
                'status' => $this->getParam('status'),
                'ward_name' => $this->getParam('ward_name'),
                'bed_type' => $this->getParam('bed_type')
            ];
            
            $beds = $this->model->getAllWithDetails($filters);
            
            $this->success(['beds' => $beds], 'Beds retrieved successfully');
        }
    }
    
    /**
     * Handle POST requests
     * POST /api/beds?action=assign - Assign bed
     * POST /api/beds?action=release - Release bed
     */
    protected function handlePost() {
        $action = $this->getParam('action');
        $data = $this->getRequestData();
        
        if ($action === 'assign') {
            // Assign bed
            $bedId = $data['bed_id'] ?? null;
            $patientId = $data['patient_id'] ?? null;
            $admissionId = $data['admission_id'] ?? null;
            
            if (!$bedId || !$patientId || !$admissionId) {
                $this->error('Bed ID, Patient ID, and Admission ID are required', 400);
            }
            
            $result = $this->model->assignBed($bedId, $patientId, $admissionId);
            
            if ($result['success']) {
                $this->success(['bed_id' => $bedId], 'Bed assigned successfully');
            } else {
                $this->error('Failed to assign bed', 400, $result['errors']);
            }
        } elseif ($action === 'release') {
            // Release bed
            $bedId = $data['bed_id'] ?? null;
            
            if (!$bedId) {
                $this->error('Bed ID is required', 400);
            }
            
            $result = $this->model->releaseBed($bedId);
            
            if ($result['success']) {
                $this->success(['bed_id' => $bedId], 'Bed released successfully');
            } else {
                $this->error('Failed to release bed', 400, $result['errors']);
            }
        } else {
            $this->error('Invalid action', 400);
        }
    }
    
    /**
     * Handle PUT requests
     * PUT /api/beds?id=123 - Update bed status
     */
    protected function handlePut() {
        $id = $this->getParam('id');
        $data = $this->getRequestData();
        
        if (!$id) {
            $this->error('Bed ID is required', 400);
        }
        
        if (isset($data['status'])) {
            $result = $this->model->updateStatus($id, $data['status']);
            
            if ($result['success']) {
                $this->success(['bed_id' => $id], 'Bed status updated successfully');
            } else {
                $this->error('Failed to update bed status', 400, $result['errors']);
            }
        } else {
            // General update
            $result = $this->model->update($id, $data);
            
            if ($result > 0) {
                $this->success(['bed_id' => $id], 'Bed updated successfully');
            } else {
                $this->error('Failed to update bed', 400);
            }
        }
    }
}
