<?php
/**
 * ============================================================
 * PharmacyReturnsController — API Reference (Supplier Returns)
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api/pharmacy/returns
 * Auth     : All endpoints require Auth
 * ------------------------------------------------------------
 *
 * 1. GET /api/pharmacy/returns
 *    Query: supplier_id, date_from, date_to
 *
 * 2. POST /api/pharmacy/returns
 *    Body: { "supplier_id":3, "return_date":"2026-06-26", "reason":"Damaged",
 *            "items":[{"product_id":15,"batch_no":"B2026A","qty":20}] }
 *
 * 3. DELETE /api/pharmacy/returns/{id}
 * ------------------------------------------------------------
 */
namespace GM_HMS\Controllers\api;

use Exception;
use GM_HMS\Controllers\BaseController;

/**
 * PharmacyReturnsController
 * 
 * ==========================================
 * POSTMAN API TESTING GUIDE
 * ==========================================
 * 
 * 1. GET (List all Returns)
 * URL: http://localhost/GM_HMS/api/pharmacy/returns
 * Method: GET
 * 
 * 2. POST (Create NEW Return)
 * URL: http://localhost/GM_HMS/api/pharmacy/returns
 * Method: POST
 * Payload (JSON):
 * {
 *     "return_type": "sales",
 *     "reference_no": "INV-12345",
 *     "product_id": "P001",
 *     "product_name": "Paracetamol 500mg",
 *     "batch_no": "B1234",
 *     "qty": 5,
 *     "rate": 10.50,
 *     "total_amount": 52.50,
 *     "status": "pending",
 *     "reason": "Expired item"
 * }
 * 
 * 3. POST (Update EXISTING Return)
 * URL: http://localhost/GM_HMS/api/pharmacy/returns
 * Method: POST
 * Payload (JSON): 
 *   ** Provide the EXACT same JSON as above, but just include "id" **
 * {
 *     "id": 5, 
 *     "return_type": "sales",
 *     "product_name": "Updated Medicine Name"
 *     ... (include other fields)
 * }
 * 
 * 4. DELETE (Delete a Return)
 * URL: http://localhost/GM_HMS/api/pharmacy/returns/5
 * Method: DELETE
 * Note: Replace '5' with the actual ID you want to delete.
 */
class PharmacyReturnsController extends BaseController {
    public function __construct() { parent::__construct(); }

    /** GET /api/pharmacy/returns */
    public function index(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $this->respondSuccess($this->db->fetchAll("SELECT * FROM ph_returns ORDER BY id DESC"));
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** POST /api/pharmacy/returns */
    public function save(): void {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $d = $_POST;
            if (empty($d)) {
                $d = $this->getJsonInput();
            }
            
            $id           = $d['id'] ?? null;
            $return_type  = $d['return_type']  ?? '';
            $reference_no = trim($d['reference_no'] ?? '');
            $product_id   = trim($d['product_id']   ?? '');
            $product_name = trim($d['product_name']  ?? '');
            $batch_no     = trim($d['batch_no']      ?? '');
            $qty          = (int)($d['qty']           ?? 0);
            $rate         = (float)($d['rate']        ?? 0);
            $total_amount = (float)($d['total_amount'] ?? 0);
            $status       = $d['status'] ?? 'pending';
            $reason       = trim($d['reason'] ?? '');
            
            $imagePath    = $d['existing_image'] ?? null;
            $docPath      = $d['existing_doc'] ?? null;

            // Handle Image Upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../../assets/pharmacy_details/pharmacy_img/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = 'RET_IMG_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                    $imagePath = 'assets/pharmacy_details/pharmacy_img/' . $filename;
                }
            }

            // Handle Doc Upload
            if (isset($_FILES['doc']) && $_FILES['doc']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../../assets/pharmacy_details/pharmacy_doc/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $ext = pathinfo($_FILES['doc']['name'], PATHINFO_EXTENSION);
                $filename = 'RET_DOC_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                if (move_uploaded_file($_FILES['doc']['tmp_name'], $uploadDir . $filename)) {
                    $docPath = 'assets/pharmacy_details/pharmacy_doc/' . $filename;
                }
            }

            if (empty($return_type) || empty($product_id) || $qty <= 0 || empty($reason)) {
                $this->respondBadRequest('Return type, product, quantity, and reason are required.');
                return;
            }

            $conn = $this->db->getConnection();
            $conn->begin_transaction();

            if (empty($id)) {
                // Generate ID: RET-XXXXX
                $row = $this->db->fetchOne("SELECT MAX(CAST(SUBSTRING(return_no, 5) AS UNSIGNED)) AS max_id FROM ph_returns");
                $next = ($row['max_id'] ?? 0) + 1;
                $return_no = 'RET-' . str_pad($next, 5, '0', STR_PAD_LEFT);

                $this->db->execute(
                    "INSERT INTO ph_returns (return_no,return_date,return_type,reference_no,product_id,product_name,batch_no,qty,rate,total_amount,reason,status,image,doc)
                    VALUES (?,CURDATE(),?,?,?,?,?,?,?,?,?,?,?,?)",
                    [$return_no, $return_type, $reference_no, $product_id, $product_name, $batch_no, $qty, $rate, $total_amount, $reason, $status, $imagePath, $docPath]
                );

                // If status processed, update stock
                if ($status === 'processed') {
                    if ($return_type === 'sales') {
                        // Sales return: Add back to stock
                        $this->db->execute("UPDATE ph_product SET quantity = quantity + ? WHERE product_id = ?", [$qty, $product_id]);
                    } elseif ($return_type === 'purchase' || $return_type === 'damage') {
                        // Purchase/Damage return: Deduct from stock
                        $this->db->execute("UPDATE ph_product SET quantity = quantity - ? WHERE product_id = ? AND quantity >= ?", [$qty, $product_id, $qty]);
                    }
                }

                $msg = 'Return recorded: ' . $return_no;
            } else {
                // For updates
                $this->db->execute(
                    "UPDATE ph_returns SET return_type=?,reference_no=?,product_id=?,product_name=?,batch_no=?,qty=?,rate=?,total_amount=?,reason=?,status=?,image=?,doc=? WHERE id=?",
                    [$return_type, $reference_no, $product_id, $product_name, $batch_no, $qty, $rate, $total_amount, $reason, $status, $imagePath, $docPath, $id]
                );
                $msg = 'Return updated successfully';
            }
            $conn->commit();
            $this->respondSuccess(null, $msg);
        } catch (Exception $e) {
            if (isset($conn)) $conn->rollback();
            $this->handleException($e);
        }
    }

    /** DELETE /api/pharmacy/returns/{id} */
    public function delete(string $id): void {
        $this->restrictMethod('DELETE');
        $this->requireAuth();
        try {
            $this->db->execute("DELETE FROM ph_returns WHERE id=?", [$id]);
            $this->respondSuccess(null, 'Return deleted');
        } catch (Exception $e) { $this->handleException($e); }
    }
}

