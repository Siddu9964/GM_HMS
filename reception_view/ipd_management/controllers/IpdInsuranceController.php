<?php
/**
 * IpdInsuranceController
 */
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/IpdInsurance.php';

class IpdInsuranceController extends BaseController {
    protected $model;
    public function __construct() { $this->model = new IpdInsurance(); }

    protected function handleGet(): void {
        $billId = $this->getParam('bill_id');
        if (!$billId) { $this->error('bill_id required', 400); return; }
        $result = $this->model->getByBill($billId);
        $this->success($result);
    }

    protected function handlePost(): void {
        $data   = $this->getRequestData();
        $action = $data['action'] ?? 'save';
        $user   = $_SESSION['username'] ?? 'system';
        $data['created_by'] = $user;

        switch ($action) {
            case 'save':
                if (empty($data['bill_id'])) { $this->error('bill_id required', 400); return; }
                $result = $this->model->saveOrUpdate($data['bill_id'], $data);
                if ($result['success']) $this->success($result, 'Insurance details saved');
                else $this->error($result['message'], 400);
                break;

            case 'status':
                if (empty($data['bill_id'])) { $this->error('bill_id required', 400); return; }
                $ok = $this->model->updateClaimStatus(
                    $data['bill_id'],
                    $data['claim_status'] ?? 'PENDING',
                    $user,
                    $data['rejection_reason'] ?? null
                );
                $this->success(null, $ok ? 'Status updated' : 'Not found');
                break;

            default:
                $this->error('Unknown action', 400);
        }
    }
}
