<?php require 'core/Autoloader.php'; $db = \GM_HMS\Database\SecureDatabase::getInstance(); $res = $db->fetchAll('SELECT * FROM ipd_admissions ORDER BY admission_id DESC LIMIT 1'); print_r($res);
