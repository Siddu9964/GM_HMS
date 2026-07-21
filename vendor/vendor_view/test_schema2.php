<?php
require 'includes/db.php';
$db = new SecureDatabase();
$res = $db->fetchAll('SHOW CREATE TABLE ph_indent_requests');
print_r($res);
