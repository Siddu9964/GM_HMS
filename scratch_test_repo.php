<?php
require 'models/Database.php';

$db = new Database();
$db->connect();
$patientId = 'PID-20251220-001';
$sql = "
            SELECT * FROM (
                SELECT result_data, result_date, result_time, test_name, status 
                FROM lab_results 
                WHERE patient_id = ? AND status IN ('Reviewed', 'Critical', 'Completed', 'Reported')
                UNION ALL
                SELECT result_data, result_date, result_time, test_name, status 
                FROM ipd_lab_results 
                WHERE patient_id = ? AND status IN ('Reviewed', 'Critical', 'Completed', 'Reported')
            ) AS combined_results
            ORDER BY result_date DESC, result_time DESC
        ";
$results = $db->fetchAll($sql, [$patientId, $patientId]);
print_r($results);
