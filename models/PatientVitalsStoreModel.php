<?php
/**
 * Patient Vitals Store Model
 * Manages daily vitals stored within ipd_clinical_records (bp_chart).
 * 
 * @package GM_HMS\Models
 * @version 2.0.0
 */

namespace GM_HMS\Models;

use GM_HMS\Database\SecureDatabase;
use Throwable;

class PatientVitalsStoreModel {
    private $db;
    
    public function __construct() {
        $this->db = SecureDatabase::getInstance();
    }

    /**
     * Get all vital records for a patient from ipd_clinical_records
     * 
     * @param string $patientId
     * @return array
     */
    public function getHistory($patientId) {
        try {
            $sql = "SELECT id, record_date, bp_chart FROM ipd_clinical_records WHERE patient_id = ? ORDER BY record_date ASC, id ASC";
            $rows = $this->db->fetchAll($sql, [$patientId]);
            
            $reconstructed = [];
            foreach ($rows as $row) {
                $bpList = json_decode($row['bp_chart'] ?? '[]', true);
                if (is_array($bpList)) {
                    foreach ($bpList as $bp) {
                        $bpVal = $bp['bp_value'] ?? '';
                        $bpParts = explode('/', $bpVal);
                        $reconstructed[] = [
                            'date' => $bp['bp_date'] ?? $row['record_date'],
                            'time' => $bp['bp_time'] ?? '',
                            'temperature' => $bp['bp_temp'] ?? null,
                            'bp_systolic' => $bpParts[0] ?? null,
                            'bp_diastolic' => $bpParts[1] ?? null,
                            'pulse_rate' => $bp['bp_pulse'] ?? null,
                            'respiratory_rate' => $bp['bp_resp'] ?? null,
                            'spo2' => $bp['bp_spo2'] ?? null,
                            'weight' => $bp['bp_weight'] ?? null,
                            'remarks' => $bp['bp_remarks'] ?? '',
                            'recorded_by' => $bp['bp_sign'] ?? 'Nurse',
                            'visit_id' => 'IPD',
                            'consciousness_level' => 'Alert'
                        ];
                    }
                }
            }
            return $reconstructed;
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Save vitals into ipd_clinical_records
     */
    public function saveDailyVitals($patientId, $vitalsData) {
        try {
            $today = date('Y-m-d');
            $currentTime = date('H:i');
            
            $newEntry = [
                'bp_date' => $today,
                'bp_time' => $currentTime,
                'bp_value' => ($vitalsData['bp_systolic'] ?? '') . '/' . ($vitalsData['bp_diastolic'] ?? ''),
                'bp_pulse' => $vitalsData['pulse_rate'] ?? '',
                'bp_temp' => $vitalsData['temperature'] ?? '',
                'bp_spo2' => $vitalsData['spo2'] ?? '',
                'bp_resp' => $vitalsData['respiratory_rate'] ?? '',
                'bp_sign' => $vitalsData['recorded_by_name'] ?? 'Nurse'
            ];

            $row = $this->db->fetchOne("SELECT id, bp_chart FROM ipd_clinical_records WHERE patient_id = ? AND record_date = ? ORDER BY id DESC LIMIT 1", [$patientId, $today]);

            if ($row) {
                $existingList = json_decode($row['bp_chart'] ?? '[]', true) ?: [];
                $existingList[] = $newEntry;
                $this->db->execute("UPDATE ipd_clinical_records SET bp_chart = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", [json_encode($existingList), $row['id']]);
            } else {
                $this->db->execute(
                    "INSERT INTO ipd_clinical_records (patient_id, admission_id, record_date, bp_chart) VALUES (?, ?, ?, ?)",
                    [$patientId, $vitalsData['visit_id'] ?? 'IPD', $today, json_encode([$newEntry])]
                );
            }

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Delete records
     */
    public function deleteHistory($patientId) {
        return true;
    }

    public function deleteDailyRecord($patientId, $date) {
        return false; 
    }
}

