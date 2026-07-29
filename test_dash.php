<?php
require 'd:/xampp/htdocs/GM_HMS/core/Autoloader.php'; 
require 'd:/xampp/htdocs/GM_HMS/reception_view/ipd_management/controllers/DashboardController.php'; 
$_SERVER['REQUEST_METHOD']='GET'; 
$_SERVER['REQUEST_URI']='/api/dashboard';
$ctrl = new DashboardController(); 
$ctrl->handleRequest();
