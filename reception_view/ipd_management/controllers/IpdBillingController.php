<?php
/**
 * IPD Billing Controller
 * 
 * Handles billing operations for IPD admissions
 * 
 * @package IPD_Management\Controllers
 */

require_once __DIR__ . '/../models/IpdBilling.php';

class IpdBillingController {
    private $model;
    
    public function __construct() {
        $this->model = new IpdBilling();
    }
    
    /**
     * Get all bills or filter by admission
     */
    public function index() {
        try {
            $admissionId = $_GET['admission_id'] ?? null;
            
            if ($admissionId) {
                $bills = $this->model->getBillsByAdmission($admissionId);
                $summary = $this->model->getAdmissionBillingSummary($admissionId);
                
                return $this->jsonResponse([
                    'success' => true,
                    'data' => $bills,
                    'summary' => $summary
                ]);
            }
            
            $bills = $this->model->getAll();
            return $this->jsonResponse(['success' => true, 'data' => $bills]);
            
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get single bill with items
     */
    public function show($billId) {
        try {
            $bill = $this->model->getBillWithItems($billId);
            
            if (!$bill) {
                return $this->jsonResponse(['success' => false, 'error' => 'Bill not found'], 404);
            }
            
            return $this->jsonResponse(['success' => true, 'data' => $bill]);
            
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Create new bill
     */
    public function create() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                return $this->jsonResponse(['success' => false, 'error' => 'Invalid input'], 400);
            }
            
            $billData = [
                'admission_id' => $input['admission_id'],
                'patient_id' => $input['patient_id'],
                'doctor_id' => $input['doctor_id'] ?? null,
                'admission_date' => $input['admission_date'],
                'discharge_date' => $input['discharge_date'] ?? null,
                'total_days' => $input['total_days'] ?? 0,
                'discount_percentage' => $input['discount_percentage'] ?? 0,
                'discount_amount' => $input['discount_amount'] ?? 0,
                'tax_percentage' => $input['tax_percentage'] ?? 18,
                'amount_paid' => $input['amount_paid'] ?? 0,
                'notes' => $input['notes'] ?? null,
                'created_by' => $_SESSION['user_id'] ?? 'system'
            ];
            
            $items = $input['items'] ?? [];
            
            if (empty($items)) {
                return $this->jsonResponse(['success' => false, 'error' => 'No billing items provided'], 400);
            }
            
            // Add created_by to each item
            foreach ($items as &$item) {
                $item['created_by'] = $billData['created_by'];
            }
            
            $result = $this->model->createBillWithItems($billData, $items);
            
            if ($result['success']) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Bill created successfully',
                    'bill_id' => $result['bill_id']
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => $result['error']
                ], 500);
            }
            
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Update payment
     */
    public function updatePayment() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $billId = $input['bill_id'] ?? null;
            $amount = $input['amount'] ?? 0;
            
            if (!$billId || $amount <= 0) {
                return $this->jsonResponse(['success' => false, 'error' => 'Invalid payment data'], 400);
            }
            
            $result = $this->model->updatePayment($billId, ['amount' => $amount]);
            
            if ($result['success']) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Payment updated successfully',
                    'balance_due' => $result['balance_due']
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => $result['error']
                ], 500);
            }
            
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete bill
     */
    public function delete($billId) {
        try {
            // Delete items first
            $this->model->query("DELETE FROM ipd_billing_items WHERE bill_id = ?", [$billId]);
            
            // Delete bill
            $this->model->delete($billId);
            
            return $this->jsonResponse(['success' => true, 'message' => 'Bill deleted successfully']);
            
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * JSON response helper
     */
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
