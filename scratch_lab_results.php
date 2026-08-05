<?php
require 'models/Database.php';
$db = new Database();
$db->connect();
print_r($db->fetchAll("SELECT patient_id, test_name, status, result_data FROM lab_results WHERE patient_id != '' LIMIT 5;"));
print_r($db->fetchAll("SELECT * FROM patient LIMIT 5;"));
