<?php
/**
 * Payments Controller
 * 
 * Handles all API requests for payments
 * 
 * @package IPD_Management\Controllers
 */

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/Payment.php';

class PaymentsController extends BaseController {
    
    public function __construct() {
        $this->model = new Payment();
    }
    
    protected function handleGet() {
        $id = $this->getParam('id');
        $admissionId = $this->getParam('admission_id');
        $stats = $this->getParam('stats');
        
        if ($stats) {
            $dateRange = $this->getParam('range', 'today');
            $statistics = $this->model->getPaymentStats($dateRange);
            $this->success($statistics, 'Payment statistics retrieved successfully');
        } elseif ($id) {
            $payment = $this->model->getById($id);
            if (!$payment) $this->error('Payment not found', 404);
            $this->success($payment);
        } elseif ($admissionId) {
            $payments = $this->model->getByAdmission($admissionId);
            $total = $this->model->getTotalPaid($admissionId);
            $this->success(['payments' => $payments, 'total_paid' => $total]);
        } else {
            $this->error('Admission ID or Payment ID is required', 400);
        }
    }
    
    protected function handlePost() {
        $data = $this->getRequestData();
        $result = $this->model->createPayment($data);
        
        if ($result['success']) {
            $this->success([
                'payment_id' => $result['payment_id'],
                'receipt_number' => $result['receipt_number']
            ], 'Payment recorded successfully', 201);
        } else {
            $this->error('Failed to record payment', 400, $result['errors']);
        }
    }
    
    protected function handlePut() {
        $id = $this->getParam('id');
        if (!$id) $this->error('Payment ID is required', 400);
        
        $data = $this->getRequestData();
        $result = $this->model->update($id, $data);
        
        if ($result > 0) {
            $this->success(['payment_id' => $id], 'Payment updated successfully');
        } else {
            $this->error('Failed to update payment', 400);
        }
    }
    
    protected function handleDelete() {
        $id = $this->getParam('id');
        if (!$id) $this->error('Payment ID is required', 400);
        
        $result = $this->model->delete($id);
        
        if ($result > 0) {
            $this->success(null, 'Payment deleted successfully');
        } else {
            $this->error('Failed to delete payment', 400);
        }
    }
}
