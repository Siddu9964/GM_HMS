<?php
require_once __DIR__ . '/config/Database.php';
$db = new \GM_HMS\Config\Database();
$stmt = $db->query("SELECT * FROM ipd_billing_items LIMIT 1");
$row = $stmt->fetch(\PDO::FETCH_ASSOC);
print_r($row);
