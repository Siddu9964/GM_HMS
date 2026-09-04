<?php
/**
 * Ward / Bed Transfer Module Forwarder
 */
$qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: bed_transfer.php" . $qs);
exit();
