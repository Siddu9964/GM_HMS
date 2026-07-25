<?php
$c = new mysqli('localhost', 'root', '', 'hmsc_basaveshwranagara');

echo "=== STAFF with 'Nurse' or 'Superintendent_Nurse' in designation ===\n";
$r = $c->query("SELECT sl_no, full_name, designation, status FROM staff WHERE designation LIKE '%Nurse%'");
$staff = [];
while($row = $r->fetch_assoc()) { 
    $staff[$row['sl_no']] = $row;
}
print_r($staff);

echo "\n=== USERS with role containing 'Nurse' ===\n";
$r = $c->query("SELECT sl_no, id, username, role FROM user WHERE role LIKE '%Nurse%'");
$users = [];
while($row = $r->fetch_assoc()) { 
    $users[$row['id']] = $row;
}
print_r($users);
?>
