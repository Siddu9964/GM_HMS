<?php
require 'models/Database.php';
$db = new Database();
$db->connect();
print_r($db->fetchAll("DESCRIBE lab_results;"));
