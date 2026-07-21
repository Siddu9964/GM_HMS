<?php
/**
 * Medicine Return API for Nurse Dashboard
 * Handles fetching products and submitting returns
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../../core/Autoloader.php';

use GM_HMS\Database\SecureDatabase;

header('Content-Type: application/json');
session_start();

// Check authentication
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Nurse', 'admin', 'Admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$db = SecureDatabase::getInstance();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Fetch list of products for dropdown
        // In a real scenario, you might want to limit this or search by term
        // But since this is a local dropdown, we fetch active products
        $sql = "SELECT product_id, product_name, batch_number, purchase_rate as rate FROM ph_product WHERE is_active = 1 ORDER BY product_name ASC";
        $products = $db->fetchAll($sql);
        
        echo json_encode([
            'success' => true,
            'data' => $products
        ]);
        exit();
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Process a new medicine return
        
        // Ensure request is parsed properly
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $patient_id = $input['patient_id'] ?? '';
        $product_id = $input['product_id'] ?? '';
        $qty = (int)($input['qty'] ?? 0);
        $reason = $input['reason'] ?? '';

        if (empty($patient_id) || empty($product_id) || $qty <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Patient, Product, and valid Quantity are required.']);
            exit();
        }

        // 1. Fetch Patient details
        $patient = $db->fetchOne("SELECT first_name, last_name FROM patient WHERE patient_id = ?", [$patient_id]);
        if (!$patient) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Patient not found.']);
            exit();
        }
        $patient_name = trim($patient['first_name'] . ' ' . $patient['last_name']);

        // 2. Fetch Product details
        $product = $db->fetchOne("SELECT product_name, batch_number, purchase_rate FROM ph_product WHERE product_id = ?", [$product_id]);
        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Product not found.']);
            exit();
        }
        $product_name = $product['product_name'];
        $batch_no = $product['batch_number'];
        $rate = (float)($product['purchase_rate'] ?? 0);

        // 3. Calculate fields
        $total_amount = $qty * $rate;
        $return_no = 'RET-' . date('Ymd') . '-' . mt_rand(1000, 9999);
        $return_date = date('Y-m-d');
        $patient_type = 'IPD';
        $status = 'pending';

        // 4. Insert into ph_patient_returns
        $sql = "INSERT INTO ph_patient_returns (
            return_no, return_date, patient_type, patient_id, patient_name,
            product_id, product_name, batch_no, qty, rate, total_amount, reason, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $result = $db->execute($sql, [
            $return_no, $return_date, $patient_type, $patient_id, $patient_name,
            $product_id, $product_name, $batch_no, $qty, $rate, $total_amount, $reason, $status
        ]);

        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => 'Medicine successfully returned to pharmacy.',
                'data' => [
                    'return_no' => $return_no
                ]
            ]);
        } else {
            throw new Exception("Failed to insert return record.");
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
