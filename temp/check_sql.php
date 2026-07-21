<?php
$sql = file_get_contents('d:/xampp/htdocs/GM_HMS/hmsci.sql');
preg_match_all('/CREATE TABLE `([^`]+)`/', $sql, $matches);
foreach ($matches[1] as $table) {
    if (strpos($table, 'lab_') !== false || strpos($table, 'radiology_') !== false || strpos($table, 'other_') !== false) {
        echo "Table: $table\n";
    }
}
