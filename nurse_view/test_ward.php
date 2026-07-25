<?php
session_start();
$_SESSION['user_id'] = 16;
$_SESSION['role'] = 'Superintendent_Nurse';
$_SESSION['role_id'] = 16;
$_SESSION['username'] = 'Sheela';
$_SESSION['branch'] = 'basaveshwaranagar'; // ensure correct db

require_once __DIR__ . '/../core/Autoloader.php';
require_once __DIR__ . '/includes/nurse_auth_helper.php';

use GM_HMS\Database\SecureDatabase;

$db = SecureDatabase::getInstance();
$conn = $db->getConnection();
$currentWard = getCurrentNurseWard($conn, 16);
echo "=== Current Ward ===\n";
var_dump($currentWard);

$shiftModel = new \NurseShiftModel();
$stats = $shiftModel->getShiftStatistics(16, null, $currentWard);
echo "=== Shift Stats ===\n";
var_dump($stats);
?>
