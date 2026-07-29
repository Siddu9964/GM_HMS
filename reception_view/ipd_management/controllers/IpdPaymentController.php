<?php
/**
 * IpdPaymentController
 */
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/IpdPayment.php';

class IpdPaymentController extends BaseController {
    protected $model;
    public function __construct() { $this->model = new IpdPayment(); }

    protected function handleGet(): void {
        $billId = $this->getParam('bill_id');
        if (!$billId) { $this->error('bill_id required', 400); return; }
        $action = $this->getParam('action', 'list');
        if ($action === 'summary') {
            $this->success($this->model->getSummary($billId));
        } else {
            $payments = $this->model->getByBill($billId);
            $this->success(['payments' => $payments, 'count' => count($payments)]);
        }
    }

    protected function handlePost(): void {
        $data   = $this->getRequestData();
        $action = $data['action'] ?? 'pay';
        $user   = $_SESSION['username'] ?? 'system';
        $data['created_by'] = $user;

        switch ($action) {
            case 'pay':
                $result = $this->model->recordPayment($data);
                if ($result['success']) $this->success($result, 'Payment recorded');
                else $this->error($result['message'], 400);
                break;

            case 'insurance_receipt':
                $result = $this->model->recordInsuranceReceipt($data);
                if ($result['success']) $this->success($result, 'Insurance receipt recorded');
                else $this->error($result['message'], 400);
                break;

            case 'refund':
                $result = $this->model->recordRefund($data);
                if ($result['success']) $this->success($result, 'Refund recorded');
                else $this->error($result['message'], 400);
                break;

            default:
                $this->error('Unknown action', 400);
        }
    }
}
