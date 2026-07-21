<?php
require 'd:/xampp/htdocs/GM_HMS/core/Autoloader.php';
$_SERVER['HTTP_X_HOSPITAL_BRANCH'] = 'basaveshwaranagar';
$db = GM_HMS\Database\SecureDatabase::getInstance();
$res = $db->execute('DESCRIBE hospital_beds');
while($row = $res->fetch_assoc()) {
    print_r($row);
}
