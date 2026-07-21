<?php
require 'vendor/vendor_view/includes/db.php';
$db = new SecureDatabase();
$items = $db->fetchAll("SELECT product_id, product_name, quantity FROM ph_product WHERE quantity <= 20 LIMIT 3");
if (empty($items)) {
    echo "No items below 20.";
    exit;
}

$db->beginTransaction();
$row_m = $db->fetchOne("SELECT MAX(CAST(SUBSTRING(indent_no, 5) AS UNSIGNED)) AS max_id FROM ph_indent_requests");
$indent_no = 'IND-' . str_pad(($row_m['max_id'] ?? 0) + 1, 5, '0', STR_PAD_LEFT);

foreach ($items as $item) {
    $orderQty = max(50 - (int)$item['quantity'], 10);
    $db->execute(
        "INSERT INTO ph_indent_requests
            (indent_no, request_date, request_time, requested_by, department,
             product_id, item_name, qty, priority, remarks, status, supplier_id, company_name, email)
         VALUES (?, CURDATE(), CURTIME(), 'System Auto', 'Pharmacy Store', ?, ?, ?, 'high', 'Auto-generated: low stock', 'pending', '', '', '')",
        [$indent_no, $item['product_id'], $item['product_name'], $orderQty]
    );
}
$db->commit();
echo "Generated $indent_no for " . count($items) . " items.\n";
