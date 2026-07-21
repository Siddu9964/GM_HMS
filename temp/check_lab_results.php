<?php
$sql = file_get_contents('d:/xampp/htdocs/GM_HMS/hmsci.sql');
if (preg_match('/CREATE TABLE `lab_results`(.*?);/is', $sql, $matches)) {
    echo $matches[0];
} else {
    echo "Table not found.";
}
