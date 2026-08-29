<?php
namespace GM_HMS\Controllers\api;
use GM_HMS\Models\IpdInsurance;

/**
 * IpdInsuranceController
 */



class IpdInsuranceController extends IpdBaseController {
    protected $model;
    public function __construct() { $this->model = new IpdInsurance(); }

    protected function handleGet(): void {
        $action = $this->getParam('action');
        $insuranceId = $this->getParam('insurance_id');
        $billId = $this->getParam('bill_id');

        // 1. Single Insurance record by ID
        if ($insuranceId) {
            $result = $this->model->getById((int)$insuranceId);
            if ($result) {
                $this->success($result);
            } else {
                $this->error('Insurance record not found', 404);
            }
            return;
        }

        // 2. Insurance record by Bill ID (Backwards compatible for IPD Billing modal)
        if ($billId && $action !== 'list') {
            $result = $this->model->getByBill($billId);
            $this->success($result);
            return;
        }

        // 3. Distinct Companies & TPAs
        if ($action === 'companies') {
            $result = $this->model->getDistinctCompanies();
            $this->success($result);
            return;
        }

        // 4. Paginated List with Advanced Filters (Default when no specific ID requested)
        $filters = [
            'search'          => $this->getParam('search'),
            'patient_name'    => $this->getParam('patient_name'),
            'patient_id'      => $this->getParam('patient_id'),
            'admission_id'    => $this->getParam('admission_id'),
            'bill_id'         => $this->getParam('bill_id'),
            'company_name'    => $this->getParam('company_name'),
            'tpa_name'        => $this->getParam('tpa_name'),
            'insurance_type'  => $this->getParam('insurance_type'),
            'policy_number'   => $this->getParam('policy_number'),
            'claim_number'    => $this->getParam('claim_number'),
            'approval_number' => $this->getParam('approval_number'),
            'claim_status'    => $this->getParam('claim_status'),
            'date_type'       => $this->getParam('date_type', 'created_at'),
            'date_from'       => $this->getParam('date_from'),
            'date_to'         => $this->getParam('date_to'),
        ];

        $page    = (int)$this->getParam('page', 1);
        $limit   = (int)$this->getParam('limit', 25);
        $sortBy  = $this->getParam('sort_by', 'insurance_id');
        $sortDir = $this->getParam('sort_dir', 'DESC');

        $result = $this->model->getPaginatedList($filters, $page, $limit, $sortBy, $sortDir);
        $this->success($result);
    }

    protected function handlePost(): void {
        $data   = $this->getRequestData();
        $action = $data['action'] ?? 'save';
        $user   = $_SESSION['username'] ?? 'system';
        $data['created_by'] = $user;

        switch ($action) {
            case 'update_full':
                $insuranceId = (int)($data['insurance_id'] ?? 0);
                if (!$insuranceId) {
                    $this->error('insurance_id required for update', 400);
                    return;
                }
                $result = $this->model->updateFullRecord($insuranceId, $data, $user);
                if ($result['success']) {
                    $this->success($result['data'], $result['message']);
                } else {
                    $this->error($result['message'] ?? 'Failed to update record', 400);
                }
                break;

            case 'save':
                if (empty($data['bill_id'])) { $this->error('bill_id required', 400); return; }
                $result = $this->model->saveOrUpdate($data['bill_id'], $data);
                if ($result['success']) $this->success($result, 'Insurance details saved');
                else $this->error($result['message'], 400);
                break;

            case 'cancel':
                if (empty($data['bill_id'])) { $this->error('bill_id required', 400); return; }
                $result = $this->model->cancelInsurance($data['bill_id'], $user);
                if ($result['success']) $this->success($result, $result['message']);
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
