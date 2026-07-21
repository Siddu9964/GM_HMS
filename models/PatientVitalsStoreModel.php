<?php
/**
 * Patient Vitals Store Model
 * Manages daily vitals stored in a JSON format within a single database row per patient.
 * 
 * @package GM_HMS\Models
 * @version 1.0.0
 */

namespace GM_HMS\Models;

use GM_HMS\Database\SecureDatabase;
use Exception;

class PatientVitalsStoreModel {
    private $db;
    
    public function __construct() {
        $this->db = SecureDatabase::getInstance();
    }

    /**
     * Get all vital records for a patient
     * This reconstructions the history from multiple JSON-array columns
     * 
     * @param string $patientId
     * @return array
     */
    public function getHistory($patientId) {
        $sql = "SELECT * FROM nurse_vitals WHERE patient_id = ?";
        $row = $this->db->fetchOne($sql, [$patientId]);
        
        if (!$row) return [];

        // Columns that store JSON arrays
        $jsonCols = ['date', 'time', 'temperature', 'bp_systolic', 'bp_diastolic', 'pulse_rate', 'respiratory_rate', 'spo2', 'weight', 'remarks', 'recorded_by'];
        $data = [];
        
        foreach ($jsonCols as $col) {
            $data[$col] = json_decode($row[$col] ?? '[]', true) ?: [];
        }

        // Zip arrays back into objects for the UI
        $reconstructed = [];
        $count = count($data['date']);
        
        for ($i = 0; $i < $count; $i++) {
            $reconstructed[] = [
                'date' => $data['date'][$i] ?? null,
                'time' => $data['time'][$i] ?? null,
                'temperature' => $data['temperature'][$i] ?? null,
                'bp_systolic' => $data['bp_systolic'][$i] ?? null,
                'bp_diastolic' => $data['bp_diastolic'][$i] ?? null,
                'pulse_rate' => $data['pulse_rate'][$i] ?? null,
                'respiratory_rate' => $data['respiratory_rate'][$i] ?? null,
                'spo2' => $data['spo2'][$i] ?? null,
                'weight' => $data['weight'][$i] ?? null,
                'remarks' => $data['remarks'][$i] ?? '',
                'recorded_by' => $data['recorded_by'][$i] ?? 'Staff',
                'visit_id' => $row['visit_id'],
                'consciousness_level' => $row['consciousness_level']
            ];
        }
        
        return $reconstructed;
    }

    /**
     * Add a record to the columnar JSON history
     * 
     * @param string $patientId
     * @param array $vitalsData
     * @return bool
     */
    public function saveDailyVitals($patientId, $vitalsData) {
        $sql = "SELECT * FROM nurse_vitals WHERE patient_id = ?";
        $row = $this->db->fetchOne($sql, [$patientId]);
        
        $jsonCols = ['date', 'time', 'temperature', 'bp_systolic', 'bp_diastolic', 'pulse_rate', 'respiratory_rate', 'spo2', 'weight', 'remarks', 'recorded_by'];
        $currentData = [];

        foreach ($jsonCols as $col) {
            $currentData[$col] = json_decode($row[$col] ?? '[]', true) ?: [];
        }

        // Append new data with IST Timezone
        date_default_timezone_set('Asia/Kolkata');
        $currentData['date'][] = date('Y-m-d');
        $currentData['time'][] = date('H:i');
        $currentData['temperature'][] = $vitalsData['temperature'];
        $currentData['bp_systolic'][] = $vitalsData['bp_systolic'];
        $currentData['bp_diastolic'][] = $vitalsData['bp_diastolic'];
        $currentData['pulse_rate'][] = $vitalsData['pulse_rate'];
        $currentData['respiratory_rate'][] = $vitalsData['respiratory_rate'];
        $currentData['spo2'][] = $vitalsData['spo2'];
        $currentData['weight'][] = $vitalsData['weight'];
        $currentData['remarks'][] = $vitalsData['remarks'] ?? '';
        $currentData['recorded_by'][] = $vitalsData['recorded_by_name'] ?? 'Staff';

        // Prepare UPDATE/INSERT
        $updateParts = [];
        $params = [];
        foreach ($jsonCols as $col) {
            $updateParts[] = "$col = ?";
            $params[] = json_encode($currentData[$col]);
        }
        
        $params[] = $vitalsData['visit_id'] ?? null;
        $params[] = $vitalsData['visit_type'] ?? 'IPD';
        $params[] = $vitalsData['consciousness_level'] ?? 'Alert';
        $params[] = date('Y-m-d H:i:s');
        $params[] = $patientId;

        if ($row) {
            $sql = "UPDATE nurse_vitals SET " . implode(', ', $updateParts) . ", visit_id = ?, visit_type = ?, consciousness_level = ?, recorded_at = ? WHERE patient_id = ?";
            return $this->db->execute($sql, $params);
        } else {
            $colsStr = implode(', ', $jsonCols) . ", visit_id, visit_type, consciousness_level, recorded_at, patient_id";
            $placeholders = str_repeat('?, ', count($jsonCols) + 4) . "?";
            $sql = "INSERT INTO nurse_vitals ($colsStr) VALUES ($placeholders)";
            return $this->db->execute($sql, $params);
        }
    }

    /**
     * Delete all records for a patient
     */
    public function deleteHistory($patientId) {
        $sql = "DELETE FROM nurse_vitals WHERE patient_id = ?";
        return $this->db->execute($sql, [$patientId]);
    }

    // Daily record deletion is handled by resetting arrays (not implemented here to keep focus)
    public function deleteDailyRecord($patientId, $date) {
        return false; 
    }
}
