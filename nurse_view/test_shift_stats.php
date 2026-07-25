<?php
require_once __DIR__ . '/../core/Autoloader.php';
require_once __DIR__ . '/../models/NurseShiftModel.php';
use GM_HMS\Database\SecureDatabase;

$db = SecureDatabase::getInstance();
$shiftModel = new NurseShiftModel();

$currentWard = [
  "floor_name" => "Second Floor",
  "ward_name" => "General Ward",
  "room_type" => "General Room",
];

$stats = $shiftModel->getShiftStatistics(16, null, $currentWard);
echo "=== Shift Stats ===\n";
var_dump($stats);
?>
