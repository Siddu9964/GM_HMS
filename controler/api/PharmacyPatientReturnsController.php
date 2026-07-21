<?php
/**
 * ============================================================
 * PharmacyPatientReturnsController — API Reference (OPD/IPD Returns)
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api/pharmacy/patient-returns
 * Auth     : All endpoints require Auth
 * ------------------------------------------------------------
 *
 * 1. GET /api/pharmacy/patient-returns
 *    Query: patient_id, date_from, date_to
 *
 * 2. POST /api/pharmacy/patient-returns
 *    Body: { "patient_id":"PID-001", "sale_id":55, "reason":"Allergy",
 *            "items":[{"product_id":15,"qty":5,"return_price":6.00}] }
 *
 * 3. DELETE /api/pharmacy/patient-returns/{id}
 *
 * 4. GET /api/pharmacy/patient-returns/receipt/{id}
 *    Returns return receipt details
 * ------------------------------------------------------------
 */
namespace GM_HMS\Controllers\api;

use Exception;
use GM_HMS\Controllers\BaseController;

/**
 * PharmacyPatientReturnsController
 * Handles OPD and IPD Medicine returns
 */
class PharmacyPatientReturnsController extends BaseController {
    public function __construct() { parent::__construct(); }

    /** GET /api/pharmacy/patient-returns */
    public function index(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $this->respondSuccess($this->db->fetchAll("SELECT * FROM ph_patient_returns ORDER BY id DESC"));
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** POST /api/pharmacy/patient-returns */
    public function save(): void {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $d = $_POST;
            if (empty($d)) {
                $d = $this->getJsonInput();
            }
            
            if (!empty($d['items']) && is_string($d['items'])) {
                $d['items'] = json_decode($d['items'], true);
            }

            $id           = $d['id'] ?? null;
            $patient_type = $d['patient_type'] ?? '';
            $patient_id   = trim($d['patient_id'] ?? '');
            $patient_name = trim($d['patient_name'] ?? '');
            $receipt_no   = trim($d['receipt_no'] ?? '');
            $reason       = trim($d['reason'] ?? '');
            $status       = 'processed';

            $imagePath    = $d['existing_image'] ?? null;
            $docPath      = $d['existing_doc'] ?? null;

            // Determine paths based on patient_type
            $imgSubDir = ($patient_type === 'IPD') ? 'ip_returns' : 'op_returns';
            $docSubDir = ($patient_type === 'IPD') ? 'ip_doc' : 'op_doc';

            // Handle Image Upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../../assets/Pharmacy_return/patiet returns/' . $imgSubDir . '/';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = 'PRET_IMG_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                    $imagePath = 'assets/Pharmacy_return/patiet returns/' . $imgSubDir . '/' . $filename;
                }
            }

            // Handle Doc Upload
            if (isset($_FILES['doc']) && $_FILES['doc']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../../assets/Pharmacy_return/patiet returns/' . $docSubDir . '/';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
                $ext = pathinfo($_FILES['doc']['name'], PATHINFO_EXTENSION);
                $filename = 'PRET_DOC_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                if (move_uploaded_file($_FILES['doc']['tmp_name'], $uploadDir . $filename)) {
                    $docPath = 'assets/Pharmacy_return/patiet returns/' . $docSubDir . '/' . $filename;
                }
            }

            $this->db->beginTransaction();
            $transaction_started = true;

            if (!empty($d['items']) && is_array($d['items'])) {
                // BULK RETURN MODE
                if (empty($patient_type) || empty($reason)) {
                    $this->respondBadRequest('Patient type and reason are required.');
                    return;
                }

                $row = $this->db->fetchOne("SELECT MAX(CAST(SUBSTRING(return_no, 6) AS UNSIGNED)) AS max_id FROM ph_patient_returns");
                $next = ($row['max_id'] ?? 0) + 1;
                $return_no = 'PRET-' . str_pad($next, 5, '0', STR_PAD_LEFT);

                $inserted = 0;
                foreach ($d['items'] as $item) {
                    $product_id   = trim($item['product_id'] ?? '');
                    $prod_name    = trim($item['product_name'] ?? '');
                    $batch_no     = trim($item['batch_no'] ?? '');
                    $qty          = (int)($item['return_qty'] ?? 0);
                    $rate         = (float)($item['rate'] ?? 0);
                    $total_amount = $qty * $rate;

                    if ($qty > 0 && !empty($product_id)) {
                        $this->db->execute(
                            "INSERT INTO ph_patient_returns (return_no,return_date,patient_type,patient_id,patient_name,reference_no,receipt_no,product_id,product_name,batch_no,qty,rate,total_amount,reason,status,image,doc)
                            VALUES (?,CURDATE(),?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                            [$return_no, $patient_type, $patient_id, $patient_name, $receipt_no, $receipt_no, $product_id, $prod_name, $batch_no, $qty, $rate, $total_amount, $reason, $status, $imagePath, $docPath]
                        );
                        $this->db->execute("UPDATE ph_product SET quantity = quantity + ? WHERE product_id = ?", [$qty, $product_id]);
                        $inserted++;
                    }
                }

                if ($inserted === 0) {
                    $conn->rollback();
                    $this->respondBadRequest('No valid items selected for return.');
                    return;
                }
                $msg = "Processed $inserted item(s) under $return_no";
            } else {
                // SINGLE EDIT MODE
                $product_id   = trim($d['product_id']   ?? '');
                $product_name = trim($d['product_name']  ?? '');
                $batch_no     = trim($d['batch_no']      ?? '');
                $qty          = (int)($d['qty']           ?? 0);
                $rate         = (float)($d['rate']        ?? 0);
                $total_amount = (float)($d['total_amount'] ?? 0);
                
                if (empty($id)) {
                    $this->respondBadRequest('Invalid payload for return.');
                    return;
                }

                $this->db->execute(
                    "UPDATE ph_patient_returns SET patient_type=?,patient_id=?,patient_name=?,reference_no=?,receipt_no=?,product_id=?,product_name=?,batch_no=?,qty=?,rate=?,total_amount=?,reason=?,status=?,image=?,doc=? WHERE id=?",
                    [$patient_type, $patient_id, $patient_name, $receipt_no, $receipt_no, $product_id, $product_name, $batch_no, $qty, $rate, $total_amount, $reason, $status, $imagePath, $docPath, $id]
                );
                $msg = 'Patient Return updated successfully';
            }

            $this->db->commit();
            $this->respondSuccess(null, $msg);
        } catch (Exception $e) {
            if (isset($transaction_started)) $this->db->rollback();
            $this->handleException($e);
        }
    }

    /** DELETE /api/pharmacy/patient-returns/{id} */
    public function delete(string $id): void {
        $this->restrictMethod('DELETE');
        $this->requireAuth();
        try {
            if (str_starts_with($id, 'PRET-')) {
                $this->db->execute("DELETE FROM ph_patient_returns WHERE return_no=?", [$id]);
            } else {
                $this->db->execute("DELETE FROM ph_patient_returns WHERE id=?", [$id]);
            }
            $this->respondSuccess(null, 'Patient Return deleted');
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** GET /api/pharmacy/patient-returns/receipt/{receipt_no} */
    public function fetchReceipt(string $receiptNo): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $master = $this->db->fetchOne("SELECT * FROM ph_sales_master WHERE invoice_no = ?", [$receiptNo]);
            if (!$master) {
                $this->respondNotFound('Receipt not found');
                return;
            }

            $items = $this->db->fetchAll("SELECT * FROM ph_sales_items WHERE invoice_no = ?", [$receiptNo]);
            $this->respondSuccess([
                'patient_id' => $master['customer_id'] ?? '',
                'patient_name' => $master['customer_name'] ?? '',
                'payment' => [
                    'subtotal' => $master['subtotal'] ?? 0,
                    'discount_amount' => $master['discount_amount'] ?? 0,
                    'tax_total' => $master['tax_total'] ?? 0,
                    'grand_total' => $master['grand_total'] ?? 0,
                    'payment_method' => $master['payment_method'] ?? 'N/A'
                ],
                'items' => array_map(function($item) {
                    return [
                        'product_id' => $item['product_id'],
                        'product_name' => $item['product_name'],
                        'batch_no' => $item['batch_no'] ?? '',
                        'rate' => $item['rate'],
                        'qty' => $item['qty'],
                        'amount' => $item['total_amount'] ?? ($item['rate'] * $item['qty'])
                    ];
                }, $items)
            ]);
        } catch (Exception $e) { $this->handleException($e); }
    }
}

