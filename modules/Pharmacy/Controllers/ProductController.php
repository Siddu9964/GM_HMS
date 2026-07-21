<?php
namespace GM_HMS\Modules\Pharmacy\Controllers;

use Exception;
use GM_HMS\Controllers\BaseController;
use GM_HMS\Modules\Pharmacy\Repositories\ProductRepository;

/**
 * ProductController
 * Handles Product CRUD API requests
 */
class ProductController extends BaseController {
    private $repository;

    public function __construct() {
        parent::__construct();
        $this->repository = new ProductRepository();
    }

    /** GET /api/pharmacy/products */
    public function index(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $filters = [
                'search'      => $_GET['search'] ?? '',
                'form'        => $_GET['form'] ?? '',
                'therapeutic' => $_GET['therapeutic'] ?? ''
            ];
            $products = $this->repository->list($filters);
            $this->respondSuccess($products);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** POST /api/pharmacy/products */
    public function create(): void {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $data = $this->getJsonInput();
            // TODO: Add Request Validation
            $id = $this->repository->create($data);
            $this->respondCreated(['sl_no' => $id]);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** PUT /api/pharmacy/products/{sl_no} */
    public function update(string $slNo): void {
        $this->restrictMethod(['PUT', 'POST']); // Allow POST if sent via _method=PUT
        $this->requireAuth();
        try {
            $data = $this->getJsonInput();
            $this->repository->update((int)$slNo, $data);
            $this->respondSuccess(null, "Product updated.");
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** DELETE /api/pharmacy/products/{sl_no} */
    public function delete(string $slNo): void {
        $this->restrictMethod('DELETE');
        $this->requireAuth();
        try {
            $this->repository->delete((int)$slNo);
            $this->respondSuccess(null, "Product deleted.");
        } catch (Exception $e) { $this->handleException($e); }
    }
}
