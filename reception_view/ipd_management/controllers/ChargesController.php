<?php
/**
 * Charges Controller
 * 
 * Handles all API requests for charges
 * 
 * @package IPD_Management\Controllers
 */

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/Charge.php';

class ChargesController extends BaseController {
    
    public function __construct() {
        $this->model = new Charge();
    }
    
    protected function handleGet() {
        $id = $this->getParam('id');
        $admissionId = $this->getParam('admission_id');
        $breakdown = $this->getParam('breakdown');
        
        if ($id) {
            $charge = $this->model->getById($id);
            if (!$charge) $this->error('Charge not found', 404);
            $this->success($charge);
        } elseif ($admissionId) {
            if ($breakdown) {
                $chargesBreakdown = $this->model->getChargesBreakdown($admissionId);
                $total = $this->model->getTotalCharges($admissionId);
                $this->success(['breakdown' => $chargesBreakdown, 'total' => $total]);
            } else {
                $charges = $this->model->getByAdmission($admissionId);
                $total = $this->model->getTotalCharges($admissionId);
                $this->success(['charges' => $charges, 'total_charges' => $total]);
            }
        } else {
            $this->error('Admission ID or Charge ID is required', 400);
        }
    }
    
    protected function handlePost() {
        $data = $this->getRequestData();
        $result = $this->model->createCharge($data);
        
        if ($result['success']) {
            $this->success(['charge_id' => $result['charge_id']], 'Charge added successfully', 201);
        } else {
            $this->error('Failed to add charge', 400, $result['errors']);
        }
    }
    
    protected function handlePut() {
        $id = $this->getParam('id');
        if (!$id) $this->error('Charge ID is required', 400);
        
        $data = $this->getRequestData();
        $result = $this->model->update($id, $data);
        
        if ($result > 0) {
            $this->success(['charge_id' => $id], 'Charge updated successfully');
        } else {
            $this->error('Failed to update charge', 400);
        }
    }
    
    protected function handleDelete() {
        $id = $this->getParam('id');
        if (!$id) $this->error('Charge ID is required', 400);
        
        $result = $this->model->delete($id);
        
        if ($result > 0) {
            $this->success(null, 'Charge deleted successfully');
        } else {
            $this->error('Failed to delete charge', 400);
        }
    }
}
