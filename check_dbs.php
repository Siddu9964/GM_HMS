<?php
$db = new mysqli('localhost', 'root', '');
$res = $db->query("SHOW DATABASES");
while($row = $res->fetch_array()){
    echo $row[0] . "\n";
}
