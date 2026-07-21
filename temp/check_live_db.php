<?php
require 'd:/xampp/htdocs/GM_HMS/config/SecurityConfig.php';
require 'd:/xampp/htdocs/GM_HMS/Database/SecureDatabase.php';

try {
    $db = \GM_HMS\Database\SecureDatabase::getInstance();
    $tables = $db->fetchAll("SHOW TABLES");
    foreach($tables as $t) {
        $name = array_values($t)[0];
        if (strpos($name, 'lab_') !== false) {
            echo "Found table: $name\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
