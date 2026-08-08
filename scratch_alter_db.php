<?php
require 'core/Autoloader.php';
$db = \GM_HMS\Database\SecureDatabase::getInstance();
try {
    $db->execute("ALTER TABLE ph_indent_requests ADD COLUMN communication_method VARCHAR(20) DEFAULT 'None' AFTER status");
    echo "Added communication_method column.\n";
} catch (Exception $e) {
    echo "Error adding communication_method: " . $e->getMessage() . "\n";
}

try {
    $db->execute("ALTER TABLE ph_indent_requests ADD COLUMN sent_by VARCHAR(100) NULL AFTER communication_method");
    echo "Added sent_by column.\n";
} catch (Exception $e) {
    echo "Error adding sent_by: " . $e->getMessage() . "\n";
}
