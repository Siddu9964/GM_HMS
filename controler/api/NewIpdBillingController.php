<?php
namespace GM_HMS\Controllers\api;

use GM_HMS\Controllers\BaseController;
use GM_HMS\Models\NewIpdBillingModel;
use Exception;

class NewIpdBillingController extends BaseController {
    private $model;
    
    public function __construct() {
        parent::__construct();
        $this->model = new NewIpdBillingModel();
    }
    
    /**
     * GET /api/new-ipd-billing/admission/{admission_id}
     */
    public function getAdmissionDetails($admissionId) {
        $this->restrictMethod('GET');
        
        try {
            $data = $this->model->getAdmissionBillingDetails($admissionId);
            $this->respondSuccess($data);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
