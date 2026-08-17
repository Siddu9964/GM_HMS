<?php
/**
 * Nurse Clinical Model
 * Handles the central Daily IPD Clinical Records unified JSON architecture
 * 
 * @package GM_HMS\Models
 * @version 1.0.0
 */

namespace GM_HMS\Models;

use GM_HMS\Database\SecureDatabase;
use Exception;

class NurseClinicalModel {
    private $db;
    
    public function __construct() {
        $this->db = SecureDatabase::getInstance();
    }
    
    /**
     * Appends an item to the specified JSON column for today's record.
     * Follows the rule: Check if exists -> Update if YES, Create if NO.
     */
    public function appendToDailyRecord($patientId, $admissionId, $columnName, $itemData, $nurseId) {
        $validColumns = [
            'consultant_visits', 'lab_tests', 'radiology_tests', 'other_tests', 
            'pharmacy_orders', 'pharmacy_returns', 'grbs_chart', 'nebulization_chart', 
            'dialysis_chart', 'bp_test', 'oxygen_chart', 'ventilation_chart', 'blood_transfusion_chart', 
            'nurses_record', 'vitals', 'nursing_notes', 'procedures', 'billing_items', 'attachments'
        ];
        
        if (!in_array($columnName, $validColumns)) {
            throw new Exception("Invalid column name: {$columnName}");
        }
        
        $today = date('Y-m-d');
        
        // Wrap data with strict audit block
        $auditBlock = [
            'created_date' => date('Y-m-d'),
            'created_time' => date('H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $nurseId,
            'updated_at' => date('Y-m-d H:i:s'),
            'status' => 'Active',
            'data' => $itemData
        ];
        
        // Check if record exists for today
        $sql = "SELECT id, {$columnName} FROM ipd_clinical_records 
                WHERE patient_id = ? AND admission_id = ? AND record_date = ?";
        $existing = $this->db->fetchOne($sql, [$patientId, $admissionId, $today]);
        
        if ($existing) {
            // Record exists, update
            $currentArr = json_decode($existing[$columnName] ?? '[]', true);
            if (!is_array($currentArr)) $currentArr = [];
            
            $currentArr[] = $auditBlock;
            $newJson = json_encode($currentArr);
            
            $updateSql = "UPDATE ipd_clinical_records SET {$columnName} = ? WHERE id = ?";
            $this->db->execute($updateSql, [$newJson, $existing['id']]);
        } else {
            // Record does not exist, create
            $arr = [$auditBlock];
            $json = json_encode($arr);
            
            $insertSql = "INSERT INTO ipd_clinical_records 
                          (patient_id, admission_id, record_date, {$columnName}) 
                          VALUES (?, ?, ?, ?)";
            $this->db->execute($insertSql, [$patientId, $admissionId, $today, $json]);
        }
        
        if ($columnName === 'lab_tests') {
            // Insert Notification for Laboratory (staff)
            $nid = 'NOT-' . strtoupper(substr(uniqid(), -6));
            $title = "IPD Test Updated";
            $message = "A test has been added/updated for IPD Patient ({$patientId}).";
            $this->db->execute(
                "INSERT INTO notifications (notification_id, recipient_id, recipient_type, title, message, category, priority, action_url) 
                 VALUES (?, 'staff', 'staff', ?, ?, 'lab_result', 'normal', 'ipd_test_orders.php')",
                [$nid, $title, $message]
            );
        }

        return true;
    }
}
