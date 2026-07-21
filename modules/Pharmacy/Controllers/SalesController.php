<?php
namespace GM_HMS\Modules\Pharmacy\Controllers;

use Exception;
use GM_HMS\Controllers\BaseController;
use GM_HMS\Modules\Pharmacy\Repositories\SalesRepository;
use GM_HMS\Modules\Pharmacy\Services\InvoiceRenderer;

/**
 * SalesController
 * Handles sales history and invoice retrieval
 */
class SalesController extends BaseController {
    private $repository;

    public function __construct() {
        parent::__construct();
        $this->repository = new SalesRepository();
    }

    /** GET /api/pharmacy/sales?search=&date_from=&date_to= */
    public function index(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $filters = [
                'search'     => $_GET['search'] ?? '',
                'date_from'  => $_GET['date_from'] ?? '',
                'date_to'    => $_GET['date_to'] ?? '',
                'pharmacist' => $_GET['pharmacist'] ?? ''
            ];
            $sales = $this->repository->getSalesList($filters);
            $stats = $this->repository->getStats($filters);
            $pharmacists = $this->repository->getPharmacists();
            
            $this->respondSuccess([
                'data'        => $sales,
                'stats'       => $stats,
                'pharmacists' => $pharmacists
            ]);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** GET /api/pharmacy/sales/{id_or_invoice} */
    public function show(string $idOrInvoice): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $sale = null;
            $items = [];
            
            if (is_numeric($idOrInvoice)) {
                $sale = $this->repository->getById((int)$idOrInvoice);
                if ($sale) {
                    $items = $this->repository->getSaleItemsBySaleId((int)$idOrInvoice);
                }
            } else {
                $sale = $this->repository->getSaleByInvoice($idOrInvoice);
                if ($sale) {
                    $items = $this->repository->getSaleItems($idOrInvoice);
                }
            }
            
            if (!$sale) { $this->respondNotFound("Sale not found."); return; }
            
            if ($sale['payment_method'] === 'split') {
                $sale['split_payments'] = $this->repository->getSplitPayments($sale['invoice_no']);
            }
            
            $this->respondSuccess([
                'sale'  => $sale,
                'items' => $items
            ]);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** GET /api/pharmacy/sales/{id}/reprint */
    public function reprint(string $id): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $sale = $this->repository->getById((int)$id);
            if (!$sale) { $this->respondNotFound("Sale not found."); return; }
            $items = $this->repository->getSaleItems($sale['invoice_no']);
            $renderer = new InvoiceRenderer();
            $printedBy = $sale['created_by'] ?? $this->currentUser['username'] ?? $this->currentUser['full_name'] ?? '';
            $html = $renderer->render($sale, $items, $printedBy);
            
            $this->respondSuccess(['html' => $html]);
        } catch (Exception $e) { $this->handleException($e); }
    }
}
