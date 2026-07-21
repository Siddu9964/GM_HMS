<?php
// Simulate the JS fetch request
$url = "http://localhost/GM_HMS/api/pharmacy/indents/bulk-assign";

// We need a session cookie. 
// Since we don't have one easily available from CLI, let's just make a script that mimics what the controller would do.
require_once __DIR__ . '/controler/api/PharmacyIndentController.php';

echo "Testing bulkAssignVendor is not trivial via pure HTTP without a session.\n";
