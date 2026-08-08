<?php
require_once 'd:\xampp\htdocs\GM_HMS\config\EnvLoader.php';
require_once 'd:\xampp\htdocs\GM_HMS\config\Database.php';
$db = new \Core\Database();
$stmt = $db->query("SELECT COUNT(*) FROM ph_indent_requests WHERE status='ordered'");
echo $stmt->fetchColumn();
