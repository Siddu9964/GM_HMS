<?php require_once 'models/Database.php'; $db = new Database(); print_r($db->fetchAll('SHOW TABLES'));
