<?php
session_start();
$_SESSION['vendor_id'] = 'SUP-00001';
$_SESSION['hospital_branch'] = 'basaveshwaranagar';

$_GET['action'] = 'getIndents';
$_SERVER['REQUEST_METHOD'] = 'GET';
require 'api.php';
