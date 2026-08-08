<?php
require_once "core/Autoloader.php";
use GM_HMS\Database\SecureDatabase;

$db = SecureDatabase::getInstance();
try {
    $rows = $db->fetchAll("DESCRIBE ph_suppliers");
    foreach($rows as $row) {
        echo $row['Field'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
