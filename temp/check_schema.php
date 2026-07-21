<?php
require_once dirname(__DIR__) . '/core/Autoloader.php';
require_once dirname(__DIR__) . '/Database/SecureDatabase.php';

$db = \GM_HMS\Database\SecureDatabase::getInstance();
$result = $db->fetchAll("DESCRIBE consultations");
print_r($result);
