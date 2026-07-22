<?php
$file = 'd:/xampp/htdocs/GM_HMS/reception_view/ipd_management/views/beds/index.php';
$content = file_get_contents($file);
$content = str_replace('\`', '`', $content);
$content = str_replace('\$', '$', $content);
file_put_contents($file, $content);
echo "Fixed index.php\n";
?>
