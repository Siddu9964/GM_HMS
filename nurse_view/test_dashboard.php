<?php
session_start();
$_SESSION['user_id'] = 16;
$_SESSION['role'] = 'Superintendent_Nurse';
$_SESSION['role_id'] = 16;
$_SESSION['username'] = 'Sheela';
$_SESSION['branch'] = 'basaveshwaranagar'; // ensure correct db

// Emulate calling the dashboard api
ob_start();
require_once __DIR__ . '/api/dashboard.php';
$output = ob_get_clean();

echo "=== Dashboard API Output ===\n";
$json = json_decode($output, true);
if ($json) {
    print_r($json['data']['statistics']);
} else {
    echo $output;
}
?>
