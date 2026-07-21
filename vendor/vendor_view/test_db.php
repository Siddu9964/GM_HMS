<?php
require_once __DIR__ . '/includes/db.php';
$_SESSION['hospital_branch'] = 'basaveshwaranagar';
$db = getDB();

echo "Database used: ";
print_r($db->fetchOne("SELECT DATABASE()"));
echo "\n";

$db->execute("UPDATE ph_indent_requests SET supplier_id = 'SUP-00001', company_name = 'siddesh' WHERE id = (SELECT MAX(id) FROM ph_indent_requests)");

echo "All Indent Requests with Supplier:\n";
$indents = $db->fetchAll("SELECT * FROM ph_indent_requests WHERE supplier_id != '' ORDER BY id DESC LIMIT 10");
foreach ($indents as $ind) {
    echo "ID: {$ind['id']}, Indent: {$ind['indent_no']}, Supplier: {$ind['supplier_id']}, Status: {$ind['status']}\n";
}

echo "\nAll Suppliers:\n";
$suppliers = $db->fetchAll("SELECT * FROM ph_suppliers");
foreach ($suppliers as $sup) {
    echo "ID: {$sup['supplier_id']}, Name: {$sup['supplier_name']}\n";
}
