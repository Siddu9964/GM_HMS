<?php
require_once 'd:/xampp/htdocs/GM_HMS/Database/SecureDatabase.php';
$db = \GM_HMS\Database\SecureDatabase::getInstance();

$beds = $db->fetchAll("DESCRIBE hospital_beds");
echo "=== hospital_beds ===\n";
foreach($beds as $b) {
    echo $b['Field'] . " - " . $b['Type'] . "\n";
}

$adm = $db->fetchAll("DESCRIBE ipd_admissions");
echo "\n=== ipd_admissions ===\n";
foreach($adm as $a) {
    echo $a['Field'] . " - " . $a['Type'] . "\n";
}
