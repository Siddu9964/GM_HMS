<?php
namespace GM_HMS\Modules\Payment\Controllers;

use Exception;
use GM_HMS\Controllers\BaseController;
use GM_HMS\Modules\Payment\Services\PaymentService;

class PaymentController extends BaseController
{
    private $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new PaymentService();
    }

    /**
     * POST /api/payment/clinical-billing-sync
     */
    public function syncClinicalBilling()
    {
        $this->restrictMethod('POST');
        // $this->requireAuth(); // Assuming auth is handled by BaseController or router
        try {
            $data = $this->getJsonInput();
            
            $admissionId = $data['admission_id'] ?? null;
            $recordDate = $data['record_date'] ?? date('Y-m-d');
            $user = $_SESSION['username'] ?? 'system';

            if (!$admissionId) {
                $this->respondBadRequest('admission_id is required');
                return;
            }

            $this->service->syncClinicalBillingForDate($admissionId, $recordDate, $user);
            $this->respondSuccess(['sync_status' => 'completed'], 'Clinical billing synchronized successfully');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
