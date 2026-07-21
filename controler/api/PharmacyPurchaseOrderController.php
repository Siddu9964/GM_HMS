<?php
/**
 * ============================================================
 * PharmacyPurchaseOrderController — API Reference
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api/pharmacy/purchase-orders
 * Auth     : All endpoints require Auth
 * ------------------------------------------------------------
 *
 * 1. GET /api/pharmacy/purchase-orders
 *    Query: status, supplier_id, date_from, date_to
 *
 * 2. GET /api/pharmacy/purchase-orders/{id}
 *
 * 3. POST /api/pharmacy/purchase-orders
 *    Body: { "supplier_id":3, "expected_date":"2026-07-05", "notes":"Urgent",
 *            "items":[{"product_id":15,"qty":200,"rate":4.50}] }
 *
 * 4. DELETE /api/pharmacy/purchase-orders/{id}
 * ------------------------------------------------------------
 */
namespace GM_HMS\Controllers\api;

use Exception;
use GM_HMS\Controllers\BaseController;

/**
 * PharmacyPurchaseOrderController
 * 
 * ==========================================
 * POSTMAN API TESTING GUIDE
 * ==========================================
 * 
 * 1. GET (List all Purchase Orders)
 * URL: http://localhost/GM_HMS/api/pharmacy/purchase-orders
 * Method: GET
 * 
 * 2. GET (View a specific Purchase Order by ID, includes items)
 * URL: http://localhost/GM_HMS/api/pharmacy/purchase-orders/5
 * Method: GET
 * 
 * 3. POST (Create NEW Purchase Order)
 * URL: http://localhost/GM_HMS/api/pharmacy/purchase-orders
 * Method: POST
 * Payload (JSON):
 * {
 *     "supplier_id": "SUP-00001",
 *     "supplier_name": "MedLife Pharma Pvt Ltd",
 *     "expected_date": "2026-05-15",
 *     "status": "draft",
 *     "remarks": "Urgent delivery required",
 *     "items": [
 *         {
 *             "product_id": "P006",
 *             "item_name": "Azithromycin 250mg",
 *             "qty": 50,
 *             "rate": 10.50,
 *             "tax_percent": 5.0,
 *             "tax_amount": 26.25,
 *             "subtotal": 551.25
 *         }
 *     ]
 * }
 * 
 * 4. POST (Update EXISTING Purchase Order)
 * URL: http://localhost/GM_HMS/api/pharmacy/purchase-orders
 * Method: POST
 * Payload (JSON): 
 *   ** Provide the EXACT same JSON as above, but just include "po_id" **
 * {
 *     "po_id": 5, 
 *     "supplier_id": "SUP-00001",
 *     "items": [ ... ]
 *     ... (include other fields)
 * }
 * 
 * 5. DELETE (Delete a Purchase Order)
 * URL: http://localhost/GM_HMS/api/pharmacy/purchase-orders/5
 * Method: DELETE
 * Note: Replace '5' with the actual ID you want to delete.
 */
class PharmacyPurchaseOrderController extends BaseController {
    public function __construct() { parent::__construct(); }

    /** GET /api/pharmacy/purchase-orders */
    public function index(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            // Excluding 'attachment' from list view to prevent large payload if it contains base64 data, 
            // but fetching all other fields based on the schema.
            $this->respondSuccess($this->db->fetchAll("SELECT id, po_no, po_date, supplier_id, supplier_name, expected_date, subtotal, tax_total, grand_total, status, remarks FROM ph_purchase_orders ORDER BY id DESC"));
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** GET /api/pharmacy/purchase-orders/{id} */
    public function show(string $id): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $poData = $this->db->fetchOne("SELECT * FROM ph_purchase_orders WHERE id=? OR po_no=?", [$id, $id]);
            if (!$poData) {
                $this->respondNotFound('Purchase Order not found');
                return;
            }
            $items = $this->db->fetchAll("SELECT * FROM ph_purchase_order_items WHERE po_no=?", [$poData['po_no']]);
            $this->respondSuccess(['po' => $poData, 'items' => $items]);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** POST /api/pharmacy/purchase-orders */
    public function save(): void {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $d = $this->getJsonInput();
            $po_id        = $d['po_id'] ?? null;
            $supplier_id  = trim($d['supplier_id'] ?? '');
            $supplier_name= trim($d['supplier_name'] ?? '');
            $expected_date= !empty($d['expected_date']) ? $d['expected_date'] : null;
            $status       = $d['status'] ?? 'draft';
            $remarks      = trim($d['remarks'] ?? '');
            $attachment   = $d['attachment'] ?? null;
            $items        = $d['items'] ?? [];

            if (empty($supplier_id)) {
                $this->respondBadRequest('Supplier is required');
                return;
            }
            // We ONLY require items if creating a new PO.
            if (empty($po_id) && empty($items)) {
                $this->respondBadRequest('Add at least one item');
                return;
            }

            $conn = $this->db->getConnection();
            $conn->begin_transaction();

            if (empty($po_id)) {
                // Calculate totals
                $subtotal = $tax_total = $grand_total = 0;
                $validItems = [];
                foreach ($items as $item) {
                    $pid   = trim($item['product_id'] ?? '');
                    $iname = trim($item['item_name']   ?? '');
                    $qty   = (int)($item['qty']         ?? 0);
                    $rate  = (float)($item['rate']      ?? 0);
                    $taxP  = (float)($item['tax_percent'] ?? 0);
                    $taxA  = (float)($item['tax_amount']  ?? 0);
                    $sub   = (float)($item['subtotal']    ?? 0);
                    if (empty($pid) || $qty <= 0) continue;
                    $subtotal   += ($qty * $rate);
                    $tax_total  += $taxA;
                    $grand_total += $sub;
                    $validItems[] = [
                        'product_id'  => $pid,
                        'item_name'   => $iname,
                        'qty'         => $qty,
                        'rate'        => $rate,
                        'tax_percent' => $taxP,
                        'tax_amount'  => $taxA,
                        'subtotal'    => $sub
                    ];
                }
                if (empty($validItems)) {
                    $this->respondBadRequest('No valid items found');
                    return;
                }

                // Generate ID: PO-XXXXX
                $row = $this->db->fetchOne("SELECT MAX(CAST(SUBSTRING(po_no, 4) AS UNSIGNED)) AS max_id FROM ph_purchase_orders");
                $next = ($row['max_id'] ?? 0) + 1;
                $po_no = 'PO-' . str_pad($next, 5, '0', STR_PAD_LEFT);

                $this->db->execute(
                    "INSERT INTO ph_purchase_orders (po_no, po_date, supplier_id, supplier_name, expected_date, subtotal, tax_total, grand_total, status, remarks, attachment, invoice_no) VALUES (?,CURDATE(),?,?,?,?,?,?,?,?,?,?,?)",
                    [$po_no, $supplier_id, $supplier_name, $expected_date, $subtotal, $tax_total, $grand_total, $status, $remarks, $attachment ?? '', '']
                );
                
                foreach ($validItems as $item) {
                    $this->db->execute(
                        "INSERT INTO ph_purchase_order_items (po_no,product_id,item_name,qty,rate,tax_percent,tax_amount,subtotal) VALUES (?,?,?,?,?,?,?,?)",
                        [$po_no, $item['product_id'], $item['item_name'], $item['qty'], $item['rate'], $item['tax_percent'], $item['tax_amount'], $item['subtotal']]
                    );
                }
                $msg = 'Purchase Order created: ' . $po_no;
            } else {
                // Updating existing PO - Items and totals are NOT modified from the UI.
                $row = $this->db->fetchOne("SELECT po_no FROM ph_purchase_orders WHERE id=?", [$po_id]);
                $po_no = $row['po_no'] ?? null;
                if (!$po_no) {
                    $conn->rollback();
                    $this->respondNotFound('PO not found');
                    return;
                }
                $this->db->execute(
                    "UPDATE ph_purchase_orders SET supplier_id=?,supplier_name=?,expected_date=?,status=?,remarks=? WHERE id=?",
                    [$supplier_id, $supplier_name, $expected_date, $status, $remarks, $po_id]
                );
                $msg = 'Purchase Order updated';
            }
            $conn->commit();
            $this->respondSuccess(['po_no' => $po_no ?? ''], $msg);
        } catch (Exception $e) {
            if (isset($conn)) $conn->rollback();
            $this->handleException($e);
        }
    }

    /** DELETE /api/pharmacy/purchase-orders/{id} */
    public function delete(string $id): void {
        $this->restrictMethod('DELETE');
        $this->requireAuth();
        try {
            $row = $this->db->fetchOne("SELECT po_no FROM ph_purchase_orders WHERE id=?", [$id]);
            $po_no = $row['po_no'] ?? null;
            if (!$po_no) {
                $this->respondNotFound('PO not found');
                return;
            }
            $conn = $this->db->getConnection();
            $conn->begin_transaction();
            $this->db->execute("DELETE FROM ph_purchase_order_items WHERE po_no=?", [$po_no]);
            $this->db->execute("DELETE FROM ph_purchase_orders WHERE id=?", [$id]);
            $conn->commit();
            $this->respondSuccess(null, 'Purchase Order deleted');
        } catch (Exception $e) {
            if (isset($conn)) $conn->rollback();
            $this->handleException($e);
        }
    }
}

