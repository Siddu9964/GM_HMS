<?php
require_once __DIR__ . '/../includes/db.php';
$db = getDB();
print_r($db->fetchAll('DESCRIBE ph_purchase_orders'));
