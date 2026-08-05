<?php
$db = new mysqli('localhost', 'root', '', 'hmsc_basaveshwaranagar');
$res = $db->query("SELECT test_name FROM lab_test_parameters WHERE test_name LIKE '%CBC%'");
while($row = $res->fetch_assoc()){
    print_r($row);
}
