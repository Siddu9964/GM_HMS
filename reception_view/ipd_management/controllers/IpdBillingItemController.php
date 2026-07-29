<?php
/**
 * IpdBillingItemController
 */
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/IpdBillingItem.php';

class IpdBillingItemController extends BaseController {
    protected $model;
    public function __construct() { $this->model = new IpdBillingItem(); }

    protected function handleGet(): void {
        $action  = $this->getParam('action', 'list');
        $billId  = $this->getParam('bill_id');
        $admId   = $this->getParam('admission_id');

        switch ($action) {
            case 'list':
                if (!$billId) { $this->error('bill_id required', 400); return; }
                $type  = $this->getParam('charge_type', '');
                $items = $this->model->getByBill($billId, $type);
                $this->success(['items' => $items, 'count' => count($items)]);
                break;

            case 'summary':
                if (!$billId) { $this->error('bill_id required', 400); return; }
                $this->success($this->model->getCategorySummary($billId));
                break;

            case 'room_rent_preview':
                if (!$billId || !$admId) { $this->error('bill_id and admission_id required', 400); return; }
                $from = $this->getParam('from_date');
                $to   = $this->getParam('to_date');
                if (!$from || !$to) { $this->error('from_date and to_date required', 400); return; }
                $this->success($this->model->previewRoomRent($billId, $admId, $from, $to));
                break;

            default:
                $this->error('Unknown action', 400);
        }
    }

    protected function handlePost(): void {
        $data   = $this->getRequestData();
        $action = $data['action'] ?? 'add';
        $user   = $_SESSION['username'] ?? 'system';

        switch ($action) {
            case 'add':
                $required = ['bill_id', 'admission_id', 'patient_id', 'charge_type', 'description'];
                $errors   = $this->validateRequired($data, $required);
                if ($errors) { $this->error('Validation failed', 400, $errors); return; }
                $result = $this->model->addItem(
                    $data['bill_id'], $data['admission_id'], $data['patient_id'],
                    array_merge($data, ['created_by' => $user])
                );
                if ($result['success']) $this->success($result, 'Charge added');
                else $this->error($result['message'], isset($result['duplicate']) ? 409 : 400);
                break;

            case 'room_rent':
                $required = ['bill_id', 'admission_id', 'patient_id', 'from_date', 'to_date'];
                $errors   = $this->validateRequired($data, $required);
                if ($errors) { $this->error('Validation failed', 400, $errors); return; }
                $result = $this->model->generateRoomRent(
                    $data['bill_id'], $data['admission_id'], $data['patient_id'],
                    $data['from_date'], $data['to_date'], $user
                );
                if ($result['success']) $this->success($result, "Room rent generated: {$result['added']} days");
                else $this->error($result['message'], 400);
                break;

            case 'cancel':
                if (empty($data['item_id'])) { $this->error('item_id required', 400); return; }
                $result = $this->model->cancelItem((int)$data['item_id'], $user);
                if ($result['success']) $this->success($result, 'Charge cancelled');
                else $this->error($result['message'], 400);
                break;

            default:
                $this->error('Unknown action', 400);
        }
    }
}
