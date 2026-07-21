<?php
require 'vendor/vendor_view/includes/db.php';
$db = new \GM_HMS\Controllers\SecureDatabase();
$res = $db->fetchAll('SHOW CREATE TABLE ph_indent_requests');
print_r($res);
