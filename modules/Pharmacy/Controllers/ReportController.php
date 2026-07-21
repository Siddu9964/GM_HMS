<?php
namespace GM_HMS\Modules\Pharmacy\Controllers;

use Exception;
use GM_HMS\Controllers\BaseController;
use GM_HMS\Modules\Pharmacy\Repositories\ReportRepository;

/**
 * ReportController
 * Handles specific pharmacy reports
 */
class ReportController extends BaseController {
    private $repository;

    public function __construct() {
        parent::__construct();
        $this->repository = new ReportRepository();
    }

    /** GET /api/pharmacy/reports/sales */
    public function sales(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $from    = $_GET['date_from']       ?? date('Y-m-01');
            $to      = $_GET['date_to']         ?? date('Y-m-d');
            $payment = strtolower(trim($_GET['payment_method'] ?? ''));
            $data    = $this->repository->getSalesReport($from, $to, $payment);
            $this->respondSuccess($data);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** GET /api/pharmacy/reports/expiry */
    public function expiry(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $days = (int)($_GET['days'] ?? 90);
            $data = $this->repository->getExpiryReport($days);
            $this->respondSuccess($data);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** GET /api/pharmacy/reports/low-stock */
    public function lowStock(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $threshold = (int)($_GET['threshold'] ?? 20);
            $data = $this->repository->getLowStockReport($threshold);
            $this->respondSuccess($data);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** GET /api/pharmacy/reports/top-products */
    public function topProducts(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $limit = (int)($_GET['limit'] ?? 10);
            $data  = $this->repository->getTopSellingProducts($limit);
            $this->respondSuccess(['data' => $data]);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** GET /api/pharmacy/reports?type=purchase */
    public function purchase(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $from = $_GET['date_from'] ?? date('Y-m-01');
            $to   = $_GET['date_to']   ?? date('Y-m-d');
            $data = $this->repository->getPurchaseReport($from, $to);
            $this->respondSuccess($data);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** GET /api/pharmacy/reports?type=supplier */
    public function supplier(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $data = $this->repository->getSupplierReport();
            $this->respondSuccess($data);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** GET /api/pharmacy/reports?type=customer */
    public function customer(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $data = $this->repository->getCustomerReport();
            $this->respondSuccess($data);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** GET /api/pharmacy/reports?type=tax */
    public function tax(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $from = $_GET['date_from'] ?? date('Y-m-01');
            $to   = $_GET['date_to']   ?? date('Y-m-d');
            $data = $this->repository->getTaxReport($from, $to);
            $this->respondSuccess($data);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** GET /api/pharmacy/reports?type= */
    public function index(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $type = $_GET['type'] ?? 'sales';
            switch ($type) {
                case 'expiry':       $this->expiry();      break;
                case 'low_stock':    $this->lowStock();    break;
                case 'top_products': $this->topProducts(); break;
                case 'purchase':     $this->purchase();    break;
                case 'supplier':     $this->supplier();    break;
                case 'customer':     $this->customer();    break;
                case 'tax':          $this->tax();         break;
                default:             $this->sales();       break;
            }
        } catch (Exception $e) { $this->handleException($e); }
    }
}
