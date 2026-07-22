<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'Receptionist';
ob_start();
chdir('d:/xampp/htdocs/GM_HMS/reception_view/ipd_management/views/beds');
include 'index.php';
$out = ob_get_clean();
$lines = explode("\n", $out);
echo 'First 20 lines:' . "\n";
for ($i = 0; $i < 20; $i++) {
    if (isset($lines[$i])) {
        echo $i . ': ' . $lines[$i] . "\n";
    }
}
?>
