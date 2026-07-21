<?php
/**
 * ============================================================
 * PharmacySuppliersController — API Reference
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api/pharmacy/suppliers
 * Auth     : All endpoints require Auth
 * ------------------------------------------------------------
 *
 * 1. GET /api/pharmacy/suppliers
 *    Response: [ { id, supplier_name, contact_person, phone, email, address } ]
 *
 * 2. POST /api/pharmacy/suppliers
 *    Body: { "supplier_name":"MedCo Ltd", "contact_person":"Ravi Sharma",
 *            "phone":"9876540000", "email":"medco@example.com", "address":"Mumbai" }
 *
 * 3. DELETE /api/pharmacy/suppliers/{id}
 * ------------------------------------------------------------
 */
namespace GM_HMS\Controllers\api;

use Exception;
use GM_HMS\Controllers\BaseController;

/**
 * PharmacySuppliersController
 * 
 * ==========================================
 * POSTMAN API TESTING GUIDE
 * ==========================================
 * 
 * 1. GET (List all Suppliers)
 * URL: http://localhost/GM_HMS/api/pharmacy/suppliers
 * Method: GET
 * 
 * 2. POST (Create NEW Supplier)
 * URL: http://localhost/GM_HMS/api/pharmacy/suppliers
 * Method: POST
 * Payload (JSON):
 * {
 *     "supplier_id": "SUP-00010",
 *     "supplier_name": "John Doe",
 *     "company_name": "PharmaCorp Ltd",
 *     "phone": "+91 9876543210",
 *     "email": "contact@pharmacorp.com",
 *     "password": "securepassword",
 *     "gst_no": "22AAAAA0000A1Z5",
 *     "company_pan": "ABCDE1234F",
 *     "address": "123 Business Park",
 *     "city": "Mumbai",
 *     "status": "active"
 * }
 * 
 * 3. POST (Update EXISTING Supplier)
 * URL: http://localhost/GM_HMS/api/pharmacy/suppliers
 * Method: POST
 * Payload (JSON): 
 *   ** Provide the EXACT same JSON as above, but just include "id" **
 * {
 *     "id": 5, 
 *     "supplier_id": "SUP-00010",
 *     "company_name": "PharmaCorp Updated Ltd"
 *     ... (include other fields)
 * }
 * 
 * 4. DELETE (Delete a Supplier)
 * URL: http://localhost/GM_HMS/api/pharmacy/suppliers/5
 * Method: DELETE
 * Note: Replace '5' with the actual ID you want to delete.
 */
class PharmacySuppliersController extends BaseController {
    public function __construct() { parent::__construct(); }

    public function index(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            // Updated to order by company_name which is more standard for business directories
            $this->respondSuccess($this->db->fetchAll(
                "SELECT * FROM ph_suppliers ORDER BY company_name ASC"
            ));
        } catch (Exception $e) { $this->handleException($e); }
    }

    public function save(): void {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $d = $this->getJsonInput();
            $id = $d['id'] ?? ''; // Primary Key (Numeric)
            
            $fields = [
                'supplier_id'   => $d['supplier_id'] ?? '',
                'supplier_name' => $d['supplier_name'] ?? '',
                'company_name'  => $d['company_name'] ?? '',
                'phone'         => $d['phone'] ?? '',
                'email'         => $d['email'] ?? '',
                'password'      => $d['password'] ?? '',
                'gst_no'        => $d['gst_no'] ?? '',
                'company_pan'   => $d['company_pan'] ?? '',
                'address'       => $d['address'] ?? '',
                'city'          => $d['city'] ?? '',
                'status'        => $d['status'] ?? 'active',
                'account_number'=> $d['account_number'] ?? '',
                'account_holder'=> $d['account_holder'] ?? '',
                'bank_name'     => $d['bank_name'] ?? '',
                'branch_name'   => $d['branch_name'] ?? '',
                'ifsc_code'     => $d['ifsc_code'] ?? '',
                'bank_address'  => $d['bank_address'] ?? '',
                'credit_unit'   => $d['credit_unit'] ?? '',
                'supplier_type' => $d['supplier_type'] ?? '',
                'st_no'         => $d['st_no'] ?? '',
                'is_msme'       => isset($d['is_msme']) && ($d['is_msme'] === 'true' || $d['is_msme'] == 1 || $d['is_msme'] === 'on') ? 1 : 0
            ];

            if ($id) {
                // Update existing record
                $sql = "UPDATE ph_suppliers SET 
                        supplier_id = ?, supplier_name = ?, company_name = ?, 
                        phone = ?, email = ?, password = ?, 
                        gst_no = ?, company_pan = ?, address = ?, 
                        city = ?, status = ?, account_number = ?, account_holder = ?, bank_name = ?, branch_name = ?, ifsc_code = ?, bank_address = ?, credit_unit = ?, supplier_type = ?, st_no = ?, is_msme = ? 
                        WHERE id = ?";
                $params = array_values($fields);
                $params[] = $id;
                
                $this->db->execute($sql, $params);
                $this->respondSuccess(null, 'Partner profile updated successfully');
            } else {
                // Create new record
                $sql = "INSERT INTO ph_suppliers 
                        (supplier_id, supplier_name, company_name, phone, email, password, gst_no, company_pan, address, city, status, account_number, account_holder, bank_name, branch_name, ifsc_code, bank_address, credit_unit, supplier_type, st_no, is_msme) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $params = array_values($fields);
                
                $result = $this->db->execute($sql, $params);
                $newId = is_array($result) ? ($result['insert_id'] ?? 0) : 0;
                
                $this->respondCreated(['id' => $newId], 'New partner onboarded successfully');
            }
        } catch (Exception $e) { $this->handleException($e); }
    }

    public function delete(string $id): void {
        $this->restrictMethod('DELETE');
        $this->requireAuth();
        try {
            // Delete by primary numeric ID
            $this->db->execute("DELETE FROM ph_suppliers WHERE id = ?", [$id]);
            $this->respondSuccess(null, "Partner record removed");
        } catch (Exception $e) { $this->handleException($e); }
    }
}

