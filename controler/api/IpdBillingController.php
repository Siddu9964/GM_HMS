<?php
namespace GM_HMS\Controllers\api;

use GM_HMS\Controllers\BaseController;
use GM_HMS\Models\IpdBillingModel;
use Exception;

class IpdBillingController extends BaseController {
    private $model;
    
    public function __construct() {
        parent::__construct();
        $this->model = new IpdBillingModel();
    }
    
    /**
     * GET /api/billing/ipd
     */
    public function getAllBills() {
        $this->restrictMethod('GET');
        
        try {
            $filters = [];
            if (isset($_GET['payment_status'])) $filters['payment_status'] = $_GET['payment_status'];
            if (isset($_GET['date_from'])) $filters['date_from'] = $_GET['date_from'];
            if (isset($_GET['date_to'])) $filters['date_to'] = $_GET['date_to'];
            if (isset($_GET['patient_id'])) $filters['patient_id'] = $_GET['patient_id'];
            
            $bills = $this->model->getAllBills($filters);
            $this->respondSuccess($bills);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * GET /api/billing/ipd/{bill_id}
     */
    public function getBillById($billId) {
        $this->restrictMethod('GET');
        
        try {
            $this->model->calculateRoomCharges($billId);
            $bill = $this->model->getBillDetails($billId);
            $this->respondSuccess($bill);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * GET /api/billing/ipd/admission/{admission_id}
     */
    public function getBillByAdmission($admissionId) {
        $this->restrictMethod('GET');
        
        try {
            $bill = $this->model->getBillByAdmissionId($admissionId);
            $this->respondSuccess($bill);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * POST /api/billing/ipd/{bill_id}/add-item
     */
    public function addItem($billId) {
        $this->restrictMethod('POST');
        $this->requireAuth();
        
        try {
            $input = $this->getJsonInput();
            
            if (empty($input['charge_type']) || empty($input['item_name']) || !isset($input['unit_price'])) {
                $this->respondBadRequest('Charge type, item name, and unit price are required');
            }
            
            $itemId = $this->model->addDailyCharge($billId, [
                'charge_type' => $input['charge_type'],
                'item_name' => $input['item_name'],
                'item_description' => $input['item_description'] ?? null,
                'quantity' => $input['quantity'] ?? 1,
                'unit_price' => $input['unit_price'],
                'is_taxable' => $input['is_taxable'] ?? true,
                'tax_percentage' => $input['tax_percentage'] ?? 18.00,
                'charge_date' => $input['charge_date'] ?? date('Y-m-d')
            ]);
            
            $this->respondSuccess(['item_id' => $itemId], 'Charge added successfully');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * POST /api/billing/ipd/payment
     */
    public function recordPayment() {
        $this->restrictMethod('POST');
        $this->requireAuth();
        
        try {
            $input = $this->getJsonInput();
            
            if (empty($input['bill_id']) || empty($input['amount'])) {
                $this->respondBadRequest('Bill ID and amount are required');
            }
            
            $receiptId = $this->model->recordPayment($input['bill_id'], [
                'amount' => $input['amount'],
                'payment_method' => $input['payment_method'] ?? 'Cash',
                'transaction_id' => $input['transaction_id'] ?? null,
                'notes' => $input['notes'] ?? null
            ]);
            
            $this->respondSuccess(['receipt_id' => $receiptId], 'Payment recorded successfully');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * GET /api/billing/ipd/stats
     */
    public function getStatistics() {
        $this->restrictMethod('GET');
        
        try {
            $stats = $this->model->getStatistics();
            $this->respondSuccess($stats);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
