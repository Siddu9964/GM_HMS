<?php
namespace GM_HMS\Modules\Quality\Controllers;

use Exception;
use GM_HMS\Controllers\BaseController;
use GM_HMS\Modules\Quality\Services\DashboardService;
use GM_HMS\Modules\Quality\Resources\DashboardResource;

class DashboardController extends BaseController
{
    private $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new DashboardService();
    }

    /**
     * GET /api/quality/dashboard
     */
    public function getDashboardStats()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $data = $this->service->getStats();
            $this->respondSuccess(DashboardResource::toArray($data));
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
