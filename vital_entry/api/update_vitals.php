<?php
require_once dirname(__DIR__) . '/controller/VitalsController.php';
$controller = new VitalsController();
$controller->updateVitals();
