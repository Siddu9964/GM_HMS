<?php
require 'd:/xampp/htdocs/GM_HMS/Database/SecureDatabase.php';
$db = \GM_HMS\Database\SecureDatabase::getInstance();
$tables = $db->fetchAll('SHOW TABLES');
print_r($tables);
