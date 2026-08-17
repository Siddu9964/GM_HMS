<?php
require_once __DIR__ . '/core/Autoloader.php';
$db = \GM_HMS\Database\SecureDatabase::getInstance();
$conn = $db->getConnection();
$res1 = $conn->query("SHOW COLUMNS FROM ipd_admissions");
$cols1 = [];
while($r = $res1->fetch_assoc()) $cols1[] = $r['Field'];
$res2 = $conn->query("SHOW COLUMNS FROM hospital_beds");
$cols2 = [];
while($r = $res2->fetch_assoc()) $cols2[] = $r['Field'];
echo "ipd_admissions: " . implode(', ', $cols1) . "\n";
echo "hospital_beds: " . implode(', ', $cols2) . "\n";
