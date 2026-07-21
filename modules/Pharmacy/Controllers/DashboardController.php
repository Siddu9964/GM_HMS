<?php
namespace GM_HMS\Modules\Pharmacy\Controllers;

use Exception;
use GM_HMS\Controllers\BaseController;
use GM_HMS\Modules\Pharmacy\Services\DashboardService;

class DashboardController extends BaseController {
    private $dashboardService;

    public function __construct() {
        parent::__construct();
        $this->dashboardService = new DashboardService();
    }

    /** GET /api/pharmacy/dashboard */
    public function index(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $data = $this->dashboardService->getDashboardData();
            $this->respondSuccess(\GM_HMS\Modules\Pharmacy\Resources\DashboardResource::format($data));
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
