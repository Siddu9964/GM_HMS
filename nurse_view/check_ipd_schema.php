<?php
$c = new mysqli('localhost', 'root', '', 'hmsc_basaveshwranagara');
$r = $c->query('DESCRIBE ipd_admissions');
while($row = $r->fetch_assoc()) {
    print_r($row);
}
?>
