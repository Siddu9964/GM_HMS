<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['vendor_id'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access.']));
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['items'])) {
    die(json_encode(['success' => false, 'message' => 'No data received.']));
}

$db = getDB();
$vendor_id = $_SESSION['vendor_id'];
$vendor_name = $_SESSION['vendor_name'];
$date = date('Y-m-d');
$time = date('H:i:s');

// Generate a batch Quotation No (e.g. QTN-20260506-0001)
$lastIdRow = $db->fetchOne("SELECT MAX(id) as max_id FROM ph_quotations");
$lastId = $lastIdRow['max_id'] ?? 0;
    $i = 1;
    foreach ($data['items'] as $item) {
        $unique_qtn_no = "QTN-" . date('Ymd') . "-" . str_pad($lastId + $i, 4, '0', STR_PAD_LEFT);
        
        // Sanitize inputs
        $validity = (!empty($item['validity_date'])) ? $item['validity_date'] : null;
        $qty = floatval($item['qty'] ?? 0);
        $rate = floatval($item['rate'] ?? 0);
        $total = $qty * $rate;
        $product_id = $item['product_id'] ?? 'N/A';
        $item_name = $item['item_name'] ?? 'Unknown Item';
        $indent_no = $item['indent_no'] ?? 'N/A';

        $db->execute(
            "INSERT INTO ph_quotations (
                quotation_no, indent_no, quotation_date, time, validity_date, 
                supplier_id, supplier_name, product_id, item_name, 
                qty, rate, tax_percent, tax_amount, total_amount, 
                delivery_days, remarks, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $unique_qtn_no,
                $indent_no,
                $date,
                $time,
                $validity,
                $vendor_id,
                $vendor_name,
                $product_id,
                $item_name,
                $qty,
                $rate,
                0, // tax_percent
                0, // tax_amount
                $total,
                0, // delivery_days
                '', // remarks
                'pending'
            ]
        );
        $i++;
    }
    
    // Clean any accidental output (warnings/notices) to ensure pure JSON
    if (ob_get_length()) ob_clean();
    
    echo json_encode([
        'success' => true, 
        'message' => count($data['items']) . ' Quotations submitted successfully!', 
        'last_quotation' => $unique_qtn_no ?? 'BATCH'
    ]);
} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
