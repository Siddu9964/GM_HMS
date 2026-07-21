<?php
require 'd:/xampp/htdocs/GM_HMS/config/SecurityConfig.php';
require 'd:/xampp/htdocs/GM_HMS/Database/SecureDatabase.php';

try {
    $db = \GM_HMS\Database\SecureDatabase::getInstance();
    $sql = "CREATE TABLE IF NOT EXISTS `lab_results` (
        `sl_no` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `result_id` varchar(50) NOT NULL,
        `order_id` varchar(50) NOT NULL,
        `patient_id` varchar(100) NOT NULL,
        `test_name` varchar(255) NOT NULL,
        `result_data` text DEFAULT NULL COMMENT 'JSON with test values',
        `abnormal_flags` text DEFAULT NULL,
        `result_date` date NOT NULL,
        `result_time` time NOT NULL,
        `report_file` varchar(255) DEFAULT NULL COMMENT 'PDF file path',
        `reviewed_by` varchar(20) DEFAULT NULL,
        `reviewed_at` datetime DEFAULT NULL,
        `status` enum('Pending Review','Reviewed','Critical') DEFAULT 'Pending Review',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp()
    )";
    $db->execute($sql);
    echo "Table lab_results created successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
