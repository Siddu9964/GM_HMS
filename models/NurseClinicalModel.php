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
            'doctor_visits', 'lab_tests', 'lab_tests_id', 'radiology_tests', 'radiology_test_id', 'other_tests', 'other_test_id',
            'pharmacy_orders', 'pharmacy_returns', 'nursing_notes', 
            'vitals', 'medications', 'grbs_records', 'procedures', 'billing_items'
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
        
        return true;
    }
}
