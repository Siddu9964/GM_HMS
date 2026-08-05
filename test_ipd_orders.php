<?php
require 'config/gemini_config.php';
require 'Database/SecureDatabase.php';
require 'modules/Laboratory/Repositories/LaboratoryRepository.php';

require 'config/SecurityConfig.php';
session_start();
$_SESSION['hospital_branch'] = 'basaveshwranagara';

$repo = new GM_HMS\Modules\Laboratory\Repositories\LaboratoryRepository();
$orders = $repo->getIpdOrders('1', '', 'all', '');
print_r($orders);
