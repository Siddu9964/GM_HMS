<?php
/**
 * Nurse Vitals Model
 * Handles vital signs recording and retrieval
 * 
 * @package GM_HMS\Models
 * @version 1.0.0
 */

namespace GM_HMS\Models;

use GM_HMS\Database\SecureDatabase;
use Exception;

class NurseVitalsModel {
    private $db;
    
    public function __construct() {
        $this->db = SecureDatabase::getInstance();
    }
    
    /**
     * Record vital signs for a patient
     * Note: This is an legacy method, newly implemented systems should use PatientVitalsStoreModel
     */
    public function recordVitals($data) {
        $sql = "INSERT INTO nurse_vitals (
                    patient_id, visit_id, visit_type, temperature, bp_systolic, bp_diastolic,
                    pulse_rate, respiratory_rate, spo2, weight, consciousness_level,
                    recorded_by, recorded_at, remarks
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $result = $this->db->execute($sql, [
            $data['patient_id'],
            $data['visit_id'],
            $data['visit_type'],
            $data['temperature'] ?? null,
            $data['bp_systolic'] ?? null,
            $data['bp_diastolic'] ?? null,
            $data['pulse_rate'] ?? null,
            $data['respiratory_rate'] ?? null,
            $data['spo2'] ?? null,
            $data['weight'] ?? null,
            $data['consciousness_level'] ?? 'Alert',
            $data['recorded_by'],
            $data['recorded_at'] ?? date('Y-m-d H:i:s'),
            $data['remarks'] ?? null
        ]);
        
        return $result['insert_id'] ?? 0;
    }
    
    /**
     * Get recent vitals recorded by a nurse
     * Handles reconstruction from JSON columnar format
     */
    public function getRecentVitals($nurseId, $limit = 10) {
        $sql = "SELECT nv.*, 
                       CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                       p.age, p.sex
                FROM nurse_vitals nv
                INNER JOIN patient p ON nv.patient_id = p.patient_id
                WHERE nv.recorded_by = ?
                ORDER BY nv.recorded_at DESC
                LIMIT ?";
        
        $rows = $this->db->fetchAll($sql, [$nurseId, $limit]);
        
        return array_map(function($row) {
            $getLast = function($json) {
                $data = json_decode($json ?? '[]', true);
                if (is_array($data)) return end($data);
                return $json; // Fallback for legacy data
            };

            $row['temperature'] = $getLast($row['temperature']);
            $row['bp_systolic'] = $getLast($row['bp_systolic']);
            $row['bp_diastolic'] = $getLast($row['bp_diastolic']);
            $row['pulse_rate'] = $getLast($row['pulse_rate']);
            return $row;
        }, $rows);
    }
    
    /**
     * Get abnormal vitals for assigned patients
     */
    public function getAbnormalVitals($nurseId) {
        $sql = "SELECT nv.*, 
                       CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                       b.bed_number, b.room_number
                FROM nurse_vitals nv
                INNER JOIN patient p ON nv.patient_id = p.patient_id
                INNER JOIN ipd_admissions ia ON nv.patient_id = ia.patient_id AND ia.status = 'Active'
                INNER JOIN hospital_beds b ON ia.bed_id = b.bed_id
                WHERE DATE(nv.recorded_at) = CURDATE()";
        
        $rows = $this->db->fetchAll($sql);
        $abnormal = [];

        $getLastVal = function($json, $default = 0) {
            $data = json_decode($json ?? '[]', true);
            if (is_array($data)) {
                $val = end($data);
                return ($val !== false) ? $val : $default;
            }
            return ($json !== null) ? $json : $default;
        };

        foreach ($rows as $row) {
            $temp = (float)$getLastVal($row['temperature'], 0);
            $sys = (int)$getLastVal($row['bp_systolic'], 0);
            $dia = (int)$getLastVal($row['bp_diastolic'], 0);
            $spo2 = (int)$getLastVal($row['spo2'], 100);

            if ($temp > 38.5 || $temp < 35.5 || $sys > 140 || $sys < 90 || $dia > 90 || $spo2 < 95) {
                $row['temperature'] = $temp;
                $row['bp_systolic'] = $sys;
                $row['bp_diastolic'] = $dia;
                $row['spo2'] = $spo2;
                $abnormal[] = $row;
            }
        }
        
        return $abnormal;
    }
    
    /**
     * Get vitals statistics for a nurse
     */
    public function getVitalsStatistics($nurseId, $date = null) {
        $date = $date ?? date('Y-m-d');
        
        $sql = "SELECT temperature, bp_systolic, spo2 FROM nurse_vitals WHERE recorded_by = ? AND DATE(recorded_at) = ?";
        $rows = $this->db->fetchAll($sql, [$nurseId, $date]);
        
        $total_recorded = count($rows);
        $abnormal_count = 0;

        $getLastVal = function($json, $default = 0) {
            $data = json_decode($json ?? '[]', true);
            if (is_array($data)) {
                $val = end($data);
                return ($val !== false) ? $val : $default;
            }
            return ($json !== null) ? $json : $default;
        };

        foreach ($rows as $row) {
            $temp = (float)$getLastVal($row['temperature'], 0);
            $sys = (int)$getLastVal($row['bp_systolic'], 0);
            $spo2 = (int)$getLastVal($row['spo2'], 100);

            if ($temp > 38.5 || $temp < 35.5 || $sys > 140 || $spo2 < 95) {
                $abnormal_count++;
            }
        }
        
        return [
            'total_recorded' => $total_recorded,
            'abnormal_count' => $abnormal_count
        ];
    }
}
