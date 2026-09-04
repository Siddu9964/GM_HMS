<?php
namespace GM_HMS\Modules\Quality\Controllers;

use Exception;
use GM_HMS\Controllers\BaseController;
use GM_HMS\Modules\Quality\Services\ReportService;

class ReportController extends BaseController
{
    private $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ReportService();
    }

    /**
     * GET /api/quality/reports?type=daily|monthly|reconciliation&date=&month=&year=
     */
    public function getReports()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $type   = $_GET['type']  ?? 'daily';
            $params = [
                'date'  => $_GET['date']  ?? date('Y-m-d'),
                'month' => (int)($_GET['month'] ?? date('m')),
                'year'  => (int)($_GET['year']  ?? date('Y'))
            ];
            $data = $this->service->generateReport($type, $params);
            $this->respondSuccess($data);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
