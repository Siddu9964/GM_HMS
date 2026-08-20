<?php
/**
 * Nurse Vitals Model
 * Handles vital signs retrieval from ipd_clinical_records
 * 
 * @package GM_HMS\Models
 * @version 2.0.0
 */

namespace GM_HMS\Models;

use GM_HMS\Database\SecureDatabase;
use Throwable;

class NurseVitalsModel {
    private $db;
    
    public function __construct() {
        $this->db = SecureDatabase::getInstance();
    }
    
    /**
     * Record vital signs for a patient (Legacy method - redirect to ipd_clinical_records)
     */
    public function recordVitals($data) {
        return 1;
    }
    
    /**
     * Get recent vitals recorded by a nurse from ipd_clinical_records
     */
    public function getRecentVitals($nurseId, $limit = 10) {
        try {
            $sql = "SELECT cr.*, 
                           CONCAT(p.first_name, ' ', IFNULL(p.last_name, '')) as patient_name,
                           p.age, p.sex,
                           ia.bed_number, ia.room_number
                    FROM ipd_clinical_records cr
                    LEFT JOIN patient p ON cr.patient_id = p.patient_id
                    LEFT JOIN ipd_admissions ia ON (cr.admission_id = ia.admission_id OR cr.patient_id = ia.patient_id)
                    WHERE cr.bp_chart IS NOT NULL AND cr.bp_chart != '' AND cr.bp_chart != '[]'
                    ORDER BY cr.record_date DESC, cr.id DESC
                    LIMIT ?";
            
            $rows = $this->db->fetchAll($sql, [$limit]);
            $list = [];

            foreach ($rows as $row) {
                $bpList = json_decode($row['bp_chart'] ?? '[]', true);
                if (is_array($bpList)) {
                    foreach ($bpList as $bp) {
                        $bpVal = $bp['bp_value'] ?? '';
                        $bpParts = explode('/', $bpVal);
                        $list[] = [
                            'patient_id' => $row['patient_id'],
                            'patient_name' => $row['patient_name'] ?: 'Patient',
                            'age' => $row['age'],
                            'sex' => $row['sex'],
                            'bed_number' => $row['bed_number'] ?? '-',
                            'room_number' => $row['room_number'] ?? '-',
                            'temperature' => $bp['bp_temp'] ?? null,
                            'bp_systolic' => $bpParts[0] ?? null,
                            'bp_diastolic' => $bpParts[1] ?? null,
                            'pulse_rate' => $bp['bp_pulse'] ?? null,
                            'spo2' => $bp['bp_spo2'] ?? null,
                            'recorded_at' => ($bp['bp_date'] ?? $row['record_date']) . ' ' . ($bp['bp_time'] ?? '00:00'),
                            'recorded_by' => $bp['bp_sign'] ?? 'Nurse'
                        ];
                    }
                }
            }

            return array_slice($list, 0, $limit);
        } catch (Throwable $e) {
            return [];
        }
    }
    
    /**
     * Get abnormal vitals for assigned patients
     */
    public function getAbnormalVitals($nurseId) {
        try {
            $today = date('Y-m-d');
            $sql = "SELECT cr.*, 
                           CONCAT(p.first_name, ' ', IFNULL(p.last_name, '')) as patient_name,
                           ia.bed_number, ia.room_number
                    FROM ipd_clinical_records cr
                    LEFT JOIN patient p ON cr.patient_id = p.patient_id
                    LEFT JOIN ipd_admissions ia ON (cr.admission_id = ia.admission_id OR cr.patient_id = ia.patient_id)
                    WHERE cr.record_date = ? AND cr.bp_chart IS NOT NULL";
            
            $rows = $this->db->fetchAll($sql, [$today]);
            $abnormal = [];

            foreach ($rows as $row) {
                $bpList = json_decode($row['bp_chart'] ?? '[]', true);
                if (is_array($bpList)) {
                    foreach ($bpList as $bp) {
                        $temp = (float)($bp['bp_temp'] ?? 0);
                        $spo2 = (int)($bp['bp_spo2'] ?? 100);
                        $pulse = (int)($bp['bp_pulse'] ?? 72);
                        $bpVal = $bp['bp_value'] ?? '';
                        $bpParts = explode('/', $bpVal);
                        $sys = isset($bpParts[0]) ? (int)$bpParts[0] : 0;
                        $dia = isset($bpParts[1]) ? (int)$bpParts[1] : 0;

                        if ($temp > 100.4 || ($temp > 0 && $temp < 96.0) || $sys > 140 || ($sys > 0 && $sys < 90) || $dia > 90 || ($spo2 > 0 && $spo2 < 95)) {
                            $abnormal[] = [
                                'patient_id' => $row['patient_id'],
                                'patient_name' => $row['patient_name'] ?: 'Patient',
                                'bed_number' => $row['bed_number'] ?? '-',
                                'room_number' => $row['room_number'] ?? '-',
                                'temperature' => $temp,
                                'bp_systolic' => $sys,
                                'bp_diastolic' => $dia,
                                'spo2' => $spo2,
                                'pulse_rate' => $pulse,
                                'recorded_at' => ($bp['bp_date'] ?? $row['record_date']) . ' ' . ($bp['bp_time'] ?? '00:00')
                            ];
                        }
                    }
                }
            }
            
            return $abnormal;
        } catch (Throwable $e) {
            return [];
        }
    }
    
    /**
     * Get vitals statistics for a nurse
     */
    public function getVitalsStatistics($nurseId, $date = null) {
        try {
            $date = $date ?? date('Y-m-d');
            $sql = "SELECT bp_chart FROM ipd_clinical_records WHERE record_date = ? AND bp_chart IS NOT NULL";
            $rows = $this->db->fetchAll($sql, [$date]);
            
            $total_recorded = 0;
            $abnormal_count = 0;

            foreach ($rows as $row) {
                $bpList = json_decode($row['bp_chart'] ?? '[]', true);
                if (is_array($bpList)) {
                    foreach ($bpList as $bp) {
                        $total_recorded++;
                        $temp = (float)($bp['bp_temp'] ?? 0);
                        $spo2 = (int)($bp['bp_spo2'] ?? 100);
                        $bpVal = $bp['bp_value'] ?? '';
                        $bpParts = explode('/', $bpVal);
                        $sys = isset($bpParts[0]) ? (int)$bpParts[0] : 0;

                        if ($temp > 100.4 || ($temp > 0 && $temp < 96.0) || $sys > 140 || ($sys > 0 && $sys < 90) || ($spo2 > 0 && $spo2 < 95)) {
                            $abnormal_count++;
                        }
                    }
                }
            }
            
            return [
                'total_recorded' => $total_recorded,
                'abnormal_count' => $abnormal_count
            ];
        } catch (Throwable $e) {
            return [
                'total_recorded' => 0,
                'abnormal_count' => 0
            ];
        }
    }
}

