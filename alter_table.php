<?php
require 'core/Autoloader.php';
$db = \GM_HMS\Database\SecureDatabase::getInstance();
try {
    $db->execute("ALTER TABLE rate_limit_tracking ADD COLUMN is_blocked TINYINT(1) DEFAULT 0");
    echo "Column added successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
