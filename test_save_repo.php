<?php
require 'd:/xampp/htdocs/GM_HMS/config/Database.php';
require 'd:/xampp/htdocs/GM_HMS/modules/Laboratory/Repositories/LaboratoryRepository.php';
$db = new \GM_HMS\Config\Database();
$repo = new \GM_HMS\Modules\Laboratory\Repositories\LaboratoryRepository($db);
try {
  $repo->saveTestParameters('LAB557', [['parameter_name' => 'test', 'unit' => 'mg']]);
  echo 'SUCCESS';
} catch (Exception $e) {
  echo 'FAILED: ' . $e->getMessage();
}
