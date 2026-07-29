<?php
$pdo = new PDO('mysql:host=localhost;dbname=hmsci', 'root', '');
$stmt = $pdo->query("DESCRIBE ipd_billing_items");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
