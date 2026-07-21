<?php
/**
 * ============================================================
 * VendorController — API Reference (Vendor Portal)
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api/vendor
 * Auth     : Session or Bearer token (vendor login required)
 * ------------------------------------------------------------
 *
 * 1. GET /api/vendor/indents
 *    Returns indent requests visible to the logged-in vendor
 *    Response: [ { indent_id, requested_by, items:[...], status, created_at } ]
 *
 * 2. POST /api/vendor/quotations
 *    Body:
 *      {
 *        "indent_id":    8,
 *        "vendor_id":    2,
 *        "total_value":  2100,
 *        "validity_days": 7,
 *        "notes":        "GST inclusive",
 *        "items": [
 *          { "product_id":15, "unit_price":4.20, "qty_available":500, "delivery_days":3 }
 *        ]
 *      }
 * ------------------------------------------------------------
 */
namespace GM_HMS\Controllers\api;

use GM_HMS\Controllers\BaseController;
use Exception;

class VendorController extends BaseController {
    private $model;

    public function __construct() {
        parent::__construct();
        $this->model = new \GM_HMS\Models\VendorModel();
    }
    
    /**
     * Get pending indents for the logged-in vendor
     * GET /api/vendor/indents
     */
    public function getIndents() {
        $this->restrictMethod('GET');
        
        // Ensure vendor session exists
        if (!isset($_SESSION['vendor_id'])) {
            $this->respondUnauthorized("Please log in to your vendor account.");
        }
        
        try {
            $indents = $this->model->getPendingIndents($_SESSION['vendor_id']);
            $this->respondSuccess($indents);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Submit bulk quotations
     * POST /api/vendor/quotations
     */
    public function submitQuotation() {
        $this->restrictMethod('POST');
        
        if (!isset($_SESSION['vendor_id'])) {
            $this->respondUnauthorized("Please log in to your vendor account.");
        }
        
        try {
            $data = $this->getJsonInput();
            if (empty($data['items'])) {
                $this->respondBadRequest("No items selected for submission.");
            }
            
            $results = $this->model->submitBulkQuotation(
                $_SESSION['vendor_id'],
                $_SESSION['vendor_name'],
                $data['items']
            );
            
            $this->respondSuccess([
                'count' => count($results),
                'quotations' => $results
            ], "Successfully submitted " . count($results) . " quotations.");
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}

