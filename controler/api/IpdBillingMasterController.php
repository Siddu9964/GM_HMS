<?php
namespace GM_HMS\Controllers\api;
use GM_HMS\Models\IpdBillingMaster;

/**
 * IpdBillingMasterController
 * Handles billing master API endpoints.
 */



class IpdBillingMasterController extends IpdBaseController {
    protected $model;

    public function __construct() {
        $this->model = new IpdBillingMaster();
    }

    protected function handleGet(): void {
        $action      = $this->getParam('action', 'get');
        $admissionId = $this->getParam('admission_id');
        $billId      = $this->getParam('bill_id');
        $q           = $this->getParam('q', '');

        switch ($action) {
            case 'search_admissions':
                if (strlen($q) < 1) { $this->success([]); return; }
                $this->success($this->model->searchActiveAdmissions($q));
                break;

            case 'get':
                if ($admissionId) {
                    $result = $this->model->getByAdmission($admissionId);
                    if ($result) { $this->success($result); }
                    else { $this->success(null, 'No billing master found'); }
                } elseif ($billId) {
                    $result = $this->model->getFullDetails($billId);
                    if ($result) { $this->success($result); }
                    else { $this->error('Bill not found', 404); }
                } else {
                    // List all bills
                    $filters = [
                        'payment_status' => $this->getParam('payment_status'),
                        'billing_status' => $this->getParam('billing_status'),
                        'search'         => $this->getParam('search'),
                        'date_from'      => $this->getParam('date_from'),
                        'date_to'        => $this->getParam('date_to'),
                    ];
                    $pg = $this->getPagination();
                    $this->success($this->model->getAllBills($filters, $pg['limit'], $pg['offset']));
                }
                break;

            case 'stats':
                $this->success($this->model->getDashboardStats());
                break;

            default:
                $this->error('Unknown action', 400);
        }
    }

    protected function handlePost(): void {
        $data   = $this->getRequestData();
        $action = $data['action'] ?? 'create';
        $user   = $_SESSION['username'] ?? 'system';

        switch ($action) {
            case 'create':
                $required = ['admission_id', 'patient_id'];
                $errors   = $this->validateRequired($data, $required);
                if ($errors) { $this->error('Validation failed', 400, $errors); return; }
                $result = $this->model->getOrCreateForAdmission(array_merge($data, ['created_by' => $user]));
                
                // Automatically sync clinical records to the bill items
                if (isset($result['data']['bill_id'])) {
                    $this->model->syncClinicalRecords($result['data']['bill_id'], $data['admission_id'], $user);
                    // Fetch updated master after potential sync
                    $result['data'] = $this->model->getFullDetails($result['data']['bill_id']);
                }

                $this->success($result['data'], $result['created'] ? 'Billing master created & clinical records synced' : 'Billing master loaded & synced');
                break;

            case 'discount':
                if (empty($data['bill_id'])) { $this->error('bill_id required', 400); return; }
                $discAmt = (float)($data['discount_amount'] ?? 0);
                $discPct = (float)($data['discount_percentage'] ?? 0);
                $reason  = $data['reason'] ?? '';
                $result  = $this->model->applyDiscount($data['bill_id'], $discAmt, $discPct, $reason, $user);
                $this->success($result, 'Discount applied');
                break;

            case 'status':
                if (empty($data['bill_id'])) { $this->error('bill_id required', 400); return; }
                $ok = $this->model->updateBillingStatus(
                    $data['bill_id'],
                    $data['billing_status'] ?? 'OPEN',
                    $user,
                    $data['discharge_date'] ?? null
                );
                $this->success(null, $ok ? 'Status updated' : 'Update failed');
                break;

            case 'bill_type':
                if (empty($data['bill_id'])) { $this->error('bill_id required', 400); return; }
                $ok = $this->model->updateBillType($data['bill_id'], $data['bill_type'] ?? 'SELF', $data, $user);
                $this->success(null, 'Bill type updated');
                break;

            default:
                $this->error('Unknown action', 400);
        }
    }
}
