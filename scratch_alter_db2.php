<?php
require_once "core/Autoloader.php";
use GM_HMS\Database\SecureDatabase;

$db = SecureDatabase::getInstance();
try {
    $db->execute("ALTER TABLE ph_indent_requests ADD COLUMN communication_method VARCHAR(20) DEFAULT 'None' AFTER status");
    echo "Column communication_method added.\n";
} catch (Exception $e) {
    echo "Error 1: " . $e->getMessage() . "\n";
}

try {
    $db->execute("ALTER TABLE ph_indent_requests ADD COLUMN sent_by VARCHAR(100) NULL AFTER communication_method");
    echo "Column sent_by added.\n";
} catch (Exception $e) {
    echo "Error 2: " . $e->getMessage() . "\n";
}
