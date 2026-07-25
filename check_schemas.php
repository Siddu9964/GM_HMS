<?php
require_once __DIR__ . '/core/Autoloader.php';
use GM_HMS\Database\SecureDatabase;

$db = SecureDatabase::getInstance();
$result = $db->fetchAll("SHOW TABLES");
foreach($result as $row) {
    echo implode(", ", $row) . "\n";
}
