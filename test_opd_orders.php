<?php
require 'config/gemini_config.php';
require 'config/SecurityConfig.php';
require 'Database/SecureDatabase.php';
require 'modules/Laboratory/Repositories/LaboratoryRepository.php';

session_start();
$_SESSION['hospital_branch'] = 'basaveshwranagara';

$db = new GM_HMS\Database\SecureDatabase();
print_r($db->fetchAll("SELECT * FROM opd_billing_master WHERE bill_id='OPB-20260805-0003'"));
