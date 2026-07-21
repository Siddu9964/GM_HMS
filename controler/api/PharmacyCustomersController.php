<?php
/**
 * ============================================================
 * PharmacyCustomersController — API Reference
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api/pharmacy/customers
 * Auth     : All endpoints require Auth
 * ------------------------------------------------------------
 *
 * 1. GET /api/pharmacy/customers
 *    Response: [ { id, name, phone, email, address } ]
 *
 * 2. POST /api/pharmacy/customers
 *    Body: { "name":"Walk-in Customer", "phone":"9000000000", "email":"", "address":"" }
 *
 * 3. DELETE /api/pharmacy/customers/{id}
 * ------------------------------------------------------------
 */
namespace GM_HMS\Controllers\api;

use Exception;
use GM_HMS\Controllers\BaseController;

/**
 * PharmacyCustomersController
 * Routes:
 *   GET    /api/pharmacy/customers       â†’ list
 *   POST   /api/pharmacy/customers       â†’ save (create/update)
 *   DELETE /api/pharmacy/customers/{id}  â†’ delete
 */
class PharmacyCustomersController extends BaseController {
    public function __construct() { parent::__construct(); }

    /** GET /api/pharmacy/customers */
    public function index(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $this->respondSuccess($this->db->fetchAll("SELECT * FROM ph_customers ORDER BY id DESC"));
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** POST /api/pharmacy/customers */
    public function save(): void {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $d = $this->getJsonInput();
            $id            = $d['id'] ?? null;
            $customer_name = trim($d['customer_name'] ?? '');
            $phone         = trim($d['phone'] ?? '');
            $email         = trim($d['email'] ?? '');
            $address       = trim($d['address'] ?? '');
            $credit_limit  = (float)($d['credit_limit'] ?? 0);

            if (empty($customer_name) || empty($phone)) {
                $this->respondBadRequest('Customer Name and Phone are required.');
                return;
            }

            if (empty($id)) {
                // Generate ID: CUS-XXXXX
                $row = $this->db->fetchOne("SELECT MAX(CAST(SUBSTRING(customer_id, 5) AS UNSIGNED)) AS max_id FROM ph_customers");
                $next = ($row['max_id'] ?? 0) + 1;
                $customer_id = 'CUS-' . str_pad($next, 5, '0', STR_PAD_LEFT);

                $this->db->execute(
                    "INSERT INTO ph_customers (customer_id, customer_name, phone, email, address, credit_limit) VALUES (?, ?, ?, ?, ?, ?)",
                    [$customer_id, $customer_name, $phone, $email, $address, $credit_limit]
                );
                $this->respondCreated(['customer_id' => $customer_id]);
            } else {
                $this->db->execute(
                    "UPDATE ph_customers SET customer_name=?, phone=?, email=?, address=?, credit_limit=? WHERE id=?",
                    [$customer_name, $phone, $email, $address, $credit_limit, $id]
                );
                $this->respondSuccess(null, 'Customer updated successfully');
            }
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** DELETE /api/pharmacy/customers/{id} */
    public function delete(string $id): void {
        $this->restrictMethod('DELETE');
        $this->requireAuth();
        try {
            $this->db->execute("DELETE FROM ph_customers WHERE id = ?", [$id]);
            $this->respondSuccess(null, 'Customer deleted');
        } catch (Exception $e) {
            $this->respondBadRequest('Cannot delete customer. They may have sales history.');
        }
    }
}

