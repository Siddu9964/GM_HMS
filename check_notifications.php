<?php
$dsn = "mysql:host=localhost;dbname=hmsci;charset=utf8mb4";
$pdo = new PDO($dsn, "root", "");
$stmt = $pdo->query("SHOW CREATE TABLE notifications");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo $row['Create Table'];
