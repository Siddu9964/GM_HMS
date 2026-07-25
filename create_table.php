<?php
require_once __DIR__ . '/core/Autoloader.php';
use GM_HMS\Database\SecureDatabase;

$db = SecureDatabase::getInstance();
$sql = "
CREATE TABLE IF NOT EXISTS `ipd_clinical_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` varchar(50) NOT NULL,
  `admission_id` varchar(50) NOT NULL,
  `record_date` date NOT NULL,
  `doctor_visits` json DEFAULT NULL,
  `lab_tests` json DEFAULT NULL,
  `radiology_tests` json DEFAULT NULL,
  `other_tests` json DEFAULT NULL,
  `pharmacy_orders` json DEFAULT NULL,
  `pharmacy_returns` json DEFAULT NULL,
  `nursing_notes` json DEFAULT NULL,
  `vitals` json DEFAULT NULL,
  `medications` json DEFAULT NULL,
  `grbs_records` json DEFAULT NULL,
  `procedures` json DEFAULT NULL,
  `billing_items` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_daily_record` (`patient_id`, `admission_id`, `record_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

try {
    $db->execute($sql);
    echo "Table 'ipd_clinical_records' created successfully.\n";
} catch (Exception $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
