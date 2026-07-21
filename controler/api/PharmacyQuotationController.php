<?php
/**
 * ============================================================
 * PharmacyQuotationController — API Reference
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api/pharmacy/quotations
 * Auth     : All endpoints require Auth
 * ------------------------------------------------------------
 *
 * 1. GET /api/pharmacy/quotations
 *    Query: indent_id, vendor_id, status
 *
 * 2. POST /api/pharmacy/quotations
 *    Body: { "indent_id":8, "vendor_id":2, "total_value":2100, "validity_days":7,
 *            "items":[{"product_id":15,"unit_price":4.20,"qty_available":500}] }
 *
 * 3. DELETE /api/pharmacy/quotations/{id}
 * ------------------------------------------------------------
 */
namespace GM_HMS\Controllers\api;

use Exception;
use GM_HMS\Controllers\BaseController;

/**
 * PharmacyQuotationController
 * 
 * ==========================================
 * POSTMAN API TESTING GUIDE
 * ==========================================
 * 
 * 1. GET (List all Quotations)
 * URL: http://localhost/GM_HMS/api/pharmacy/quotations
 * Method: GET
 * 
 * 2. POST (Create NEW Quotation)
 * URL: http://localhost/GM_HMS/api/pharmacy/quotations
 * Method: POST
 * Payload (JSON):
 * {
 *     "supplier_id": "SUP-00001",
 *     "supplier_name": "MedLife Pharma Pvt Ltd",
 *     "indent_no": "IND-1234",
 *     "item_name": "New Medicine",
 *     "qty": 50,
 *     "unit": "Pieces",
 *     "rate": 10.50,
 *     "tax_percent": 5.0,
 *     "tax_amount": 26.25,
 *     "total_amount": 551.25,
 *     "delivery_days": 3,
 *     "validity_date": "2026-12-31",
 *     "status": "pending",
 *     "remarks": "Test from Postman"
 * }
 * 
 * 3. POST (Update EXISTING Quotation)
 * URL: http://localhost/GM_HMS/api/pharmacy/quotations
 * Method: POST
 * Payload (JSON): 
 *   ** Provide the EXACT same JSON as above, but just include the "id" **
 * {
 *     "id": 5, 
 *     "supplier_id": "SUP-00001",
 *     "item_name": "Updated Medicine Name",
 *     "qty": 100
 *     ... (include other fields)
 * }
 * 
 * 4. DELETE (Delete a Quotation)
 * URL: http://localhost/GM_HMS/api/pharmacy/quotations/5
 * Method: DELETE
 * Note: Replace '5' with the actual ID you want to delete.
 */
class PharmacyQuotationController extends BaseController {
    public function __construct() { parent::__construct(); }

    /** GET /api/pharmacy/quotations */
    public function index(): void {
        $this->restrictMethod('GET');
        // $this->requireAuth(); // Temporarily disabled for Postman testing
        try {
            $this->respondSuccess($this->db->fetchAll("SELECT * FROM ph_quotations WHERE status != 'order sent' ORDER BY id DESC"));
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** POST /api/pharmacy/quotations */
    public function save(): void {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $d = $this->getJsonInput();
            $id = $d['id'] ?? null;
            $f = [
                'supplier_id'  => trim($d['supplier_id'] ?? ''),
                'supplier_name'=> trim($d['supplier_name'] ?? ''),
                'indent_no'    => trim($d['indent_no'] ?? ''),
                'item_name'    => trim($d['item_name'] ?? ''),
                'qty'          => (int)($d['qty'] ?? 0),
                'unit'         => trim($d['unit'] ?? 'Pieces'),
                'rate'         => (float)($d['rate'] ?? 0),
                'tax_percent'  => (float)($d['tax_percent'] ?? 0),
                'tax_amount'   => (float)($d['tax_amount'] ?? 0),
                'total_amount' => (float)($d['total_amount'] ?? 0),
                'delivery_days'=> (int)($d['delivery_days'] ?? 0),
                'validity_date'=> !empty($d['validity_date']) ? $d['validity_date'] : null,
                'status'       => $d['status'] ?? 'pending',
                'remarks'      => trim($d['remarks'] ?? ''),
            ];

            if (empty($f['supplier_id']) || empty($f['item_name'])) {
                $this->respondBadRequest('Supplier and Item are required.');
                return;
            }

            if (empty($id)) {
                // Generate ID: QUO-XXXXX
                $row = $this->db->fetchOne("SELECT MAX(CAST(SUBSTRING(quotation_no, 5) AS UNSIGNED)) AS max_id FROM ph_quotations");
                $next = ($row['max_id'] ?? 0) + 1;
                $quotation_no = 'QUO-' . str_pad($next, 5, '0', STR_PAD_LEFT);

                $this->db->execute(
                    "INSERT INTO ph_quotations (quotation_no,quotation_date,supplier_id,supplier_name,indent_no,item_name,qty,unit,rate,tax_percent,tax_amount,total_amount,delivery_days,validity_date,status,remarks)
                    VALUES (?,CURDATE(),?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                    [$quotation_no, $f['supplier_id'], $f['supplier_name'], $f['indent_no'], $f['item_name'], $f['qty'], $f['unit'], $f['rate'], $f['tax_percent'], $f['tax_amount'], $f['total_amount'], $f['delivery_days'], $f['validity_date'], $f['status'], $f['remarks']]
                );
                $this->respondCreated(['quotation_no' => $quotation_no]);
            } else {
                $this->db->execute(
                    "UPDATE ph_quotations SET supplier_id=?,supplier_name=?,indent_no=?,item_name=?,qty=?,unit=?,rate=?,tax_percent=?,tax_amount=?,total_amount=?,delivery_days=?,validity_date=?,status=?,remarks=? WHERE id=?",
                    [$f['supplier_id'], $f['supplier_name'], $f['indent_no'], $f['item_name'], $f['qty'], $f['unit'], $f['rate'], $f['tax_percent'], $f['tax_amount'], $f['total_amount'], $f['delivery_days'], $f['validity_date'], $f['status'], $f['remarks'], $id]
                );
                $this->respondSuccess(null, 'Quotation updated successfully');
            }
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** DELETE /api/pharmacy/quotations/{id} */
    public function delete(string $id): void {
        $this->restrictMethod('DELETE');
        $this->requireAuth();
        try {
            $this->db->execute("DELETE FROM ph_quotations WHERE id=?", [$id]);
            $this->respondSuccess(null, 'Quotation deleted');
        } catch (Exception $e) { $this->handleException($e); }
    }
}

