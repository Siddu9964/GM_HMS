<?php require_once __DIR__ . '/models/Database.php'; $db = new Database(); print_r($db->fetchAll('DESCRIBE lab_results'));
