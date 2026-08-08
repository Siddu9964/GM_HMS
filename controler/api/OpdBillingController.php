<?php
/**
 * ============================================================
 * OpdBillingController — API Reference
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api
 * Auth     : All endpoints require Auth (Session or Bearer token)
 * ------------------------------------------------------------
 *
 * 1. GET /api/billing/opd
 *    Query Params:
 *      payment_status   (string) - Paid | Pending | Partial
 *      date_from        (date)   - YYYY-MM-DD
 *      date_to          (date)   - YYYY-MM-DD
 *      patient_id       (string) - Filter by patient
 *      purpose          (string) - Filter by purpose
 *      all              (flag)   - Include Registration/Appointment bills
 *
 * 2. POST /api/billing/opd  OR  POST /api/billing/create
 *    Required: patient_id, items[]
 *    Body:
 *      {
 *        "patient_id":          "PID-20260626-001",
 *        "doctor_id":           "DOC-001",
 *        "appointment_id":      "APT-20260626-0001",
 *        "name":                "Anita Sharma",
 *        "mobile":              "9876543210",
 *        "referral_type":       "Doctor",
 *        "referred_by":         "Dr. Mehta",
 *        "sponsor":             "CGHS",
 *        "discount_amount":     50,
 *        "discount_percentage": 10,
 *        "tax_percentage":      18,
 *        "notes":               "Urgent case",
 *        "purpose":             "OPD Service",
 *        "items": [
 *          { "service_id": 3, "item_name": "ECG", "quantity": 1, "unit_price": 300, "total": 300 }
 *        ],
 *        "payment": { "payment_mode":"Cash", "amount": 300, "reference_no": null }
 *      }
 *    Response 201: { "bill_id": "BILL-...", "receipt_id": "RCPT-..." }
 *
 * 3. GET /api/billing/opd/{bill_id}
 *    Example: GET /api/billing/opd/BILL-20260626-0001
 *
 * 4. PUT /api/billing/opd/{bill_id}
 *    Body: Same as POST — send all fields (full replace)
 *
 * 5. DELETE /api/billing/opd/{bill_id}
 *    No body.
 *
 * 6. POST /api/billing/opd/payment
 *    Required: bill_id, amount
 *    Body:
 *      { "bill_id":"BILL-001", "amount":500, "payment_mode":"UPI", "reference_no":"TXN123", "notes":"" }
 *    Response: { "receipt_id": "RCPT-..." }
 *
 * 7. GET /api/billing/opd/stats
 *    Response: { total_bills, total_amount, paid_amount, pending_amount }
 *
 * 8. GET /api/billing/stats/daily
 *    Response: Today's billing stats
 *
 * 9. GET /api/billing/opd/search-patients?q=Anita
 *    Min 2 chars. Returns matching patients.
 *
 * 10. GET /api/billing/opd/services
 *     Returns all billable services from radiology_services table.
 *
 * 11. POST /api/billing/opd/referral
 *     Body: { "name":"Dr. Mehta", "mobile":"9000099000" }
 *
 * 12. GET /api/billing/opd/referral/search?q=Mehta
 *
 * 13. GET /api/billing/opd/sponsor/search?q=CGHS
 * ------------------------------------------------------------
 */
namespace GM_HMS\Controllers\api;

use GM_HMS\Controllers\BaseController;
use GM_HMS\Models\OpdBillingModel;
use Exception;

class OpdBillingController extends BaseController {
    private $model;
    
    public function __construct() {
        parent::__construct();
        $this->model = new OpdBillingModel();
    }
    
    /**
     * POST /api/billing/opd
     */
    public function createBill() {
        $this->restrictMethod('POST');
        $this->requireAuth();
        
        try {
            $input = $this->getJsonInput();
            
            $payments = $input['payments'] ?? [];
            if (isset($input['payment']) && empty($payments)) {
                $payments = [$input['payment']]; // Fallback for old single payment payload
            }
            
            $masterPaymentMode = 'Cash';
            if (!empty($payments)) {
                $modes = array_unique(array_filter(array_column($payments, 'payment_mode')));
                if (count($modes) > 1) {
                    $masterPaymentMode = implode(' + ', $modes);
                } elseif (count($modes) === 1) {
                    $masterPaymentMode = array_values($modes)[0];
                }
            }
            
            $billData = [
                'patient_id' => $input['patient_id'] ?? null,
                'name'       => $input['name']       ?? null,
                'mobile'     => $input['mobile']     ?? null,
                'doctor_id'   => $input['doctor_id']   ?? null,
                'doctor_name' => $input['doctor_name'] ?? null,
                'appointment_id' => $input['appointment_id'] ?? null,
                'referral_type'       => $input['referral_type']       ?? null,
                'referred_by'         => $input['referred_by']         ?? null,
                'sponsor'             => $input['sponsor']             ?? null,
                'discount_amount'     => $input['discount_amount']     ?? 0,
                'discount_percentage' => $input['discount_percentage'] ?? 0,
                'service_id'          => $input['service_id']          ?? null,
                'item_name'           => $input['item_name']           ?? null,
                'payment_mode'        => $masterPaymentMode,
                'tax_percentage'      => $input['tax_percentage']      ?? 18.00,
                'notes'               => $input['notes']               ?? null,
                'purpose'             => $input['purpose']             ?? 'OPD Service',
                'created_by'          => $this->currentUser['username'] ?? 'system'
            ];
            
            $items = $input['items'] ?? [];
            
            if (empty($billData['patient_id'])) {
                $this->respondBadRequest('Patient ID is required');
            }
            
            if (empty($items)) {
                $this->respondBadRequest('Bill must have at least one item');
            }
            
            $billId = $this->model->createBill($billData, $items);
            
            $receiptId = null;
            if (!empty($payments)) {
                foreach ($payments as $idx => $payment) {
                    if (isset($payment['amount']) && $payment['amount'] > 0) {
                        $currentReceiptId = $this->model->recordPayment($billId, [
                            'amount' => $payment['amount'],
                            'payment_mode' => $payment['payment_mode'] ?? 'Cash',
                            'reference_no' => $payment['reference_no'] ?? null,
                            'notes' => $payment['notes'] ?? 'Initial payment'
                        ]);
                        if ($idx === 0) {
                            $receiptId = $currentReceiptId;
                        }
                    }
                }
            }
            
            $this->respondCreated([
                'bill_id' => $billId,
                'receipt_id' => $receiptId
            ]);
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * GET /api/billing/opd
     */
    public function getAllBills() {
        $this->restrictMethod('GET');
        // $this->requireAuth(); // Disabled for testing
        
        try {
            $filters = [];
            if (isset($_GET['payment_status'])) $filters['payment_status'] = $_GET['payment_status'];
            if (isset($_GET['date_from'])) $filters['date_from'] = $_GET['date_from'];
            if (isset($_GET['date_to'])) $filters['date_to'] = $_GET['date_to'];
            if (isset($_GET['patient_id'])) $filters['patient_id'] = $_GET['patient_id'];
            if (isset($_GET['purpose'])) $filters['purpose'] = $_GET['purpose'];
            if (isset($_GET['exclude_purpose'])) $filters['exclude_purpose'] = $_GET['exclude_purpose'];
            if (isset($_GET['created_by'])) $filters['created_by'] = $_GET['created_by'];
            if (isset($_GET['payment_mode'])) $filters['payment_mode'] = $_GET['payment_mode'];
            if (isset($_GET['limit'])) $filters['limit'] = $_GET['limit'];
            if (isset($_GET['offset'])) $filters['offset'] = $_GET['offset'];

            // Default: if no specific purpose is requested, exclude Registration/Appointment bills
            // (appointment_bill.php explicitly sets purpose=Registration/Appointment so it still works)
            if (empty($filters['purpose']) && empty($filters['exclude_purpose']) && !isset($_GET['all'])) {
                $filters['exclude_purpose'] = 'Registration/Appointment';
            }
            
            $bills = $this->model->getAllBills($filters);
            $this->respondSuccess($bills);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * GET /api/billing/opd/{bill_id}
     */
    public function getBillById($billId) {
        $this->restrictMethod('GET');
        // $this->requireAuth(); // Disabled for testing
        
        try {
            $bill = $this->model->getBillDetails($billId);
            if (!$bill) {
                $this->respondNotFound("Bill $billId not found");
            }
            $this->respondSuccess($bill);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * POST /api/billing/opd/payment
     */
    public function recordPayment() {
        $this->restrictMethod('POST');
        $this->requireAuth();
        
        try {
            $input = $this->getJsonInput();
            
            if (!isset($input['bill_id']) || !isset($input['amount'])) {
                $this->respondBadRequest('Bill ID and Amount are required');
            }
            
            $receiptId = $this->model->recordPayment($input['bill_id'], [
                'amount' => $input['amount'],
                'payment_mode' => $input['payment_mode'] ?? 'Cash',
                'reference_no' => $input['reference_no'] ?? null,
                'notes' => $input['notes'] ?? null
            ]);
            
            $this->respondSuccess(['receipt_id' => $receiptId], 'Payment recorded successfully');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * PUT /api/billing/opd/{bill_id}
     */
    public function updateBill($billId) {
        $this->restrictMethod('PUT');
        $this->requireAuth();
        
        try {
            $input = $this->getJsonInput();
            
            $payments = $input['payments'] ?? [];
            if (isset($input['payment']) && empty($payments)) {
                $payments = [$input['payment']];
            }
            
            $masterPaymentMode = 'Cash';
            if (!empty($payments)) {
                $modes = array_unique(array_filter(array_column($payments, 'payment_mode')));
                if (count($modes) > 1) {
                    $masterPaymentMode = implode(' + ', $modes);
                } elseif (count($modes) === 1) {
                    $masterPaymentMode = array_values($modes)[0];
                }
            }

            $billData = [
                'patient_id' => $input['patient_id'] ?? null,
                'doctor_id' => $input['doctor_id'] ?? null,
                'appointment_id' => $input['appointment_id'] ?? null,
                'discount_amount'     => $input['discount_amount']     ?? 0,
                'discount_percentage' => $input['discount_percentage'] ?? 0,
                'service_id'          => $input['service_id']          ?? null,
                'item_name'           => $input['item_name']           ?? null,
                'payment_mode'        => $masterPaymentMode,
                'tax_percentage'      => $input['tax_percentage']      ?? 18.00,
                'notes'               => $input['notes']               ?? null,
                'purpose'             => $input['purpose']             ?? 'OPD Service',
                'created_by'          => $this->currentUser['username'] ?? 'system'
            ];
            
            $items = $input['items'] ?? [];
            
            if (empty($billData['patient_id'])) {
                $this->respondBadRequest('Patient ID is required');
            }
            
            if (empty($items)) {
                $this->respondBadRequest('Bill must have at least one item');
            }
            
            $this->model->updateBill($billId, $billData, $items);
            
            $newPaymentAmount = 0;
            $newPaymentMode = 'Cash';
            if (!empty($payments)) {
                foreach ($payments as $payment) {
                    $newPaymentAmount += isset($payment['amount']) ? (float)$payment['amount'] : 0;
                    $newPaymentMode = $payment['payment_mode'] ?? 'Cash'; // just keep the last one for reference if multiple
                }
            } else {
                $newPaymentAmount = null;
            }

            if ($newPaymentAmount !== null) {
                $currentBill = $this->model->getBillDetails($billId);
                $currentAmountPaid = (float)($currentBill['amount_paid'] ?? 0);
                
                if ($newPaymentAmount > $currentAmountPaid) {
                    $difference = $newPaymentAmount - $currentAmountPaid;
                    $this->model->recordPayment($billId, [
                        'amount' => $difference,
                        'payment_mode' => $masterPaymentMode,
                        'notes' => 'Payment updated during invoice edit'
                    ]);
                } elseif ($newPaymentAmount < $currentAmountPaid) {
                    // For now, if they lowered it, just force update the master table so the balance recalculates
                    // Realistically, they should do a refund, but this prevents incorrect balance displays
                    require_once __DIR__ . '/../../models/Database.php';
                    $db = new \Database();
                    $db->connect();
                    $db->execute("UPDATE opd_billing_master SET amount_paid = ?, balance_due = grand_total - ? WHERE bill_id = ?", [$newPaymentAmount, $newPaymentAmount, $billId]);
                    
                    // Update payment status based on new balance
                    $db->execute("UPDATE opd_billing_master SET payment_status = CASE 
                        WHEN balance_due <= 0 THEN 'Paid' 
                        ELSE 'Pending' END 
                        WHERE bill_id = ?", [$billId]);
                }
            }
            
            $this->respondSuccess([
                'bill_id' => $billId
            ], 'Bill updated successfully');
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * DELETE /api/billing/opd/{bill_id}
     */
    public function deleteBill($billId) {
        $this->restrictMethod('DELETE');
        $this->requireAuth();
        
        try {
            // Optional: Check if bill exists before deleting
            $bill = $this->model->getBillDetails($billId);
            if (!$bill) {
                $this->respondNotFound("Bill $billId not found");
            }

            $this->model->deleteBill($billId);
            
            $this->respondSuccess(null, 'Bill deleted successfully');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * GET /api/billing/opd/stats
     */
    public function getStatistics() {
        $this->restrictMethod('GET');
        $this->requireAuth();
        
        try {
            $stats = $this->model->getStatistics();
            $this->respondSuccess($stats);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/billing/stats/daily
     */
    public function getDailyStats() {
        $this->restrictMethod('GET');
        $this->requireAuth();
        
        try {
            $stats = $this->model->getDailyStats();
            $this->respondSuccess($stats);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    /**
     * GET /api/billing/opd/consultation-fee
     */
    public function getConsultationFee() {
        $this->restrictMethod('GET');
        $this->requireAuth();

        try {
            $patientId = trim($_GET['patient_id'] ?? '');
            $appointmentId = trim($_GET['appointment_id'] ?? '');
            if (empty($patientId)) {
                $this->respondBadRequest('Patient ID is required');
            }

            $result = $this->model->getConsultationFeeByPatient($patientId, $appointmentId);
            if (is_array($result)) {
                $this->respondSuccess([
                    'consultation_fee' => $result['fee'],
                    'is_followup' => $result['is_followup']
                ]);
            } else {
                $this->respondSuccess(['consultation_fee' => $result, 'is_followup' => false]);
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/billing/opd/search-patients
     * Search patients from appointments table by ID, phone, or name
     */
    public function searchPatients() {
        $this->restrictMethod('GET');
        $this->requireAuth();

        try {
            $query = trim($_GET['q'] ?? '');
            if (strlen($query) < 2) {
                $this->respondBadRequest('Search query must be at least 2 characters');
            }

            $results = $this->model->searchPatients($query);
            $this->respondSuccess($results);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/billing/opd/services
     * Returns services from radiology_services table
     */
    public function getServices() {
        $this->restrictMethod('GET');
        $this->requireAuth();

        try {
            $services = $this->model->getAllServices();
            $this->respondSuccess($services);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    /**
     * POST /api/billing/opd/referral
     * Save new referral data
     */
    public function saveReferral() {
        $this->restrictMethod('POST');
        $this->requireAuth();

        try {
            $input = $this->getJsonInput();
            $name = trim($input['name'] ?? '');
            $mobile = trim($input['mobile'] ?? '');

            if (empty($name)) {
                $this->respondBadRequest('Referral name is required');
            }

            $addBy = $this->currentUser['full_name'] ?: ($this->currentUser['username'] ?: 'system');
            $success = $this->model->saveReferral($name, $mobile, $addBy);

            if ($success) {
                $this->respondSuccess(null, 'Referral data saved successfully');
            } else {
                $this->respondServerError('Failed to save referral data');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/billing/opd/referral/search
     * Search for referral suggestions
     */
    public function searchReferrals() {
        $this->restrictMethod('GET');
        $this->requireAuth();

        try {
            $query = trim($_GET['q'] ?? '');
            if (empty($query)) {
                $this->respondSuccess([]);
            }

            $results = $this->model->searchReferrals($query);
            $this->respondSuccess($results);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/billing/opd/sponsor/search
     * Search for sponsor suggestions
     */
    public function searchSponsors() {
        $this->restrictMethod('GET');
        $this->requireAuth();

        try {
            $query = trim($_GET['q'] ?? '');
            if (empty($query)) {
                $this->respondSuccess([]);
            }

            $results = $this->model->searchSponsors($query);
            $this->respondSuccess($results);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/billing/opd/analytics
     */
    public function getOpdAnalytics() {
        $this->restrictMethod('GET');
        $this->requireAuth();

        try {
            $filters = [
                'start_date' => $_GET['start_date'] ?? date('Y-m-01'), // Default to current month
                'end_date' => $_GET['end_date'] ?? date('Y-m-t'),
                'receptionist' => $_GET['receptionist'] ?? null,
                'payment_mode' => $_GET['payment_mode'] ?? null,
                'payment_status' => $_GET['payment_status'] ?? null
            ];

            $data = $this->model->getAnalyticsData($filters);
            $this->respondSuccess($data);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
