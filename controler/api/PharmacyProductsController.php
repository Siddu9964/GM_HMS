<?php
/**
 * ============================================================
 * PharmacyProductsController — API Reference
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api/pharmacy/products
 * Auth     : All endpoints require Auth
 * ------------------------------------------------------------
 *
 * 1. GET /api/pharmacy/products
 *    Response: Full product list with stock levels
 *
 * 2. POST /api/pharmacy/products
 *    Body: { "product_name":"Paracetamol 500mg", "category":"Analgesic",
 *            "unit":"Strip", "reorder_level":50, "selling_price":6.00 }
 *
 * 3. PUT /api/pharmacy/products/{id}
 *    Body: Send only changed fields
 *
 * 4. DELETE /api/pharmacy/products/{id}
 * ------------------------------------------------------------
 */
namespace GM_HMS\Controllers\api;

use Exception;
use GM_HMS\Controllers\BaseController;

/**
 * PharmacyProductsController
 * Routes:
 *   GET  /api/pharmacy/products          â†’ list (with search/filter)
 *   POST /api/pharmacy/products          â†’ create
 *   PUT  /api/pharmacy/products/{sl_no}  â†’ update
 *   DELETE /api/pharmacy/products/{sl_no}â†’ delete
 */
class PharmacyProductsController extends BaseController {

    public function __construct() { parent::__construct(); }

    /** GET /api/pharmacy/products?search=&page=&limit= */
    public function index(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $search = trim($_GET['search'] ?? '');
            $limit  = min((int)($_GET['limit'] ?? 5000), 10000);
            $params = [];
            $where  = '1=1';
            if ($search) {
                $like   = '%' . $search . '%';
                $where  = "(product_name LIKE ? OR product_id LIKE ? OR batch_number LIKE ? OR therapeutic LIKE ? OR content LIKE ?)";
                $params = [$like, $like, $like, $like, $like];
            }
            $params[] = $limit;
            $rows = $this->db->fetchAll(
                "SELECT * FROM ph_product WHERE {$where} ORDER BY product_name LIMIT ?", $params
            );
            $this->respondSuccess($rows);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** POST /api/pharmacy/products */
    public function create(): void {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $d = $this->getJsonInput();
            $this->db->execute(
                "INSERT INTO ph_product (product_id,product_name,content,strength,form,therapeutic,
                  quantity,pack,batch_number,expiry_date,individual_rate,pack_rate,mrp,
                  tax_percent,pack_size,unit,product_image)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $d['product_id']    ?? '', $d['product_name']   ?? '',
                    $d['content']       ?? '', $d['strength']       ?? '',
                    $d['form']          ?? '', $d['therapeutic']    ?? '',
                    (int)($d['quantity']     ?? 0), $d['pack']      ?? '',
                    $d['batch_number']  ?? '', $d['expiry_date']    ?? null,
                    (float)($d['individual_rate'] ?? 0), (float)($d['pack_rate'] ?? 0),
                    (float)($d['mrp']    ?? 0), (float)($d['tax_percent'] ?? 12),
                    (int)($d['pack_size']     ?? 1), $d['unit']    ?? 'Tablet',
                    $d['product_image'] ?? null,
                ]
            );
            $this->respondCreated(['product_id' => $d['product_id'] ?? '']);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** PUT /api/pharmacy/products/{sl_no} */
    public function update(string $sl_no): void {
        $this->restrictMethod('PUT');
        $this->requireAuth();
        try {
            $d = $this->getJsonInput();
            $allowed = ['product_id','product_name','content','strength','form','therapeutic','quantity','pack',
                        'batch_number','expiry_date','individual_rate','pack_rate','mrp',
                        'tax_percent','pack_size','unit','product_image'];
            $sets = []; $params = [];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $d)) { $sets[] = "`{$f}` = ?"; $params[] = $d[$f]; }
            }
            if (empty($sets)) { $this->respondBadRequest('No fields to update'); return; }
            $params[] = $sl_no;
            $this->db->execute("UPDATE ph_product SET " . implode(', ', $sets) . " WHERE sl_no = ?", $params);
            $this->respondSuccess(null, "Product {$sl_no} updated");
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** DELETE /api/pharmacy/products/{sl_no} */
    public function delete(string $sl_no): void {
        $this->restrictMethod('DELETE');
        $this->requireAuth();
        try {
            $this->db->execute("DELETE FROM ph_product WHERE sl_no = ?", [$sl_no]);
            $this->respondSuccess(null, "Product {$sl_no} deleted");
        } catch (Exception $e) { $this->handleException($e); }
    }
}

