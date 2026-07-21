<?php
require_once __DIR__ . '/../models/Database.php';
$db = new Database();
$db->connect();
$conn = $db->getConnection();
try {
    $stmt = $conn->query("DESCRIBE patient");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "--- patient ---\n";
    echo implode(", ", $cols) . "\n";
} catch (Exception $e) { echo $e->getMessage() . "\n"; }
