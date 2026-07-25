<?php
$c = new mysqli('localhost', 'root', '', 'hmsc_basaveshwranagara');
$r = $c->query("SELECT b.floor_name, b.ward_name, b.room_type FROM ipd_admissions ia LEFT JOIN hospital_beds b ON ia.bed_id = b.sl_no WHERE ia.status IN ('Active', 'Admitted')");
while($row = $r->fetch_assoc()) { print_r($row); }
?>
