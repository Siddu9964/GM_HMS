<?php
/**
 * Nurse Notes Model
 * Handles SOAP notes and handover documentation
 * 
 * @package GM_HMS\Models
 * @version 1.0.0
 */

namespace GM_HMS\Models;

use GM_HMS\Database\SecureDatabase;
use Exception;

class NurseNotesModel {
    private $db;
    
    public function __construct() {
        $this->db = SecureDatabase::getInstance();
    }
    
    /**
     * Create new nurse note
     * 
     * @param array $data Note data
     * @return int New note ID
     */
    public function createNote($data) {
        $sql = "INSERT INTO nurse_notes (
                    patient_id, admission_id, subjective, objective, assessment, plan,
                    handover_important, nurse_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->execute($sql, [
            $data['patient_id'],
            $data['admission_id'] ?? null,
            $data['subjective'] ?? null,
            $data['objective'] ?? null,
            $data['assessment'] ?? null,
            $data['plan'] ?? null,
            $data['handover_important'] ?? 0,
            $data['nurse_id']
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Get notes for a specific patient
     * 
     * @param string $patientId Patient ID
     * @return array List of notes
     */
    public function getNotesByPatient($patientId) {
        $sql = "SELECT nn.*, s.full_name as nurse_name
                FROM nurse_notes nn
                LEFT JOIN staff s ON nn.nurse_id = s.sl_no
                WHERE nn.patient_id = ?
                ORDER BY nn.created_at DESC";
        
        return $this->db->fetchAll($sql, [$patientId]);
    }
    
    /**
     * Get notes created by a specific nurse
     * 
     * @param int $nurseId Nurse staff serial number
     * @param string $dateFrom Start date
     * @param string $dateTo End date
     * @return array List of notes
     */
    public function getNotesByNurse($nurseId, $dateFrom = null, $dateTo = null) {
        $dateFrom = $dateFrom ?? date('Y-m-d');
        $dateTo = $dateTo ?? date('Y-m-d');
        
        $sql = "SELECT nn.*, 
                       CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                       p.age, p.sex
                FROM nurse_notes nn
                INNER JOIN patient p ON nn.patient_id = p.patient_id
                WHERE nn.nurse_id = ?
                  AND DATE(nn.created_at) BETWEEN ? AND ?
                ORDER BY nn.created_at DESC";
        
        return $this->db->fetchAll($sql, [$nurseId, $dateFrom, $dateTo]);
    }
    
    /**
     * Get handover notes for a specific date
     * 
     * @param string $date Date (Y-m-d)
     * @return array Handover notes
     */
    public function getHandoverNotes($date = null) {
        $date = $date ?? date('Y-m-d');
        
        $sql = "SELECT nn.*, 
                       CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                       p.age, p.sex,
                       s.full_name as nurse_name,
                       b.bed_number, b.room_number
                FROM nurse_notes nn
                INNER JOIN patient p ON nn.patient_id = p.patient_id
                LEFT JOIN staff s ON nn.nurse_id = s.sl_no
                LEFT JOIN ipd_admissions ia ON nn.admission_id = ia.admission_id
                LEFT JOIN hospital_beds b ON ia.bed_id = b.bed_id
                WHERE nn.handover_important = 1
                  AND DATE(nn.created_at) = ?
                ORDER BY nn.created_at DESC";
        
        return $this->db->fetchAll($sql, [$date]);
    }
    
    /**
     * Update existing note
     * 
     * @param int $noteId Note ID
     * @param array $data Updated data
     * @return bool Success status
     */
    public function updateNote($noteId, $data) {
        $fields = [];
        $params = [];
        
        $allowedFields = ['subjective', 'objective', 'assessment', 'plan', 'handover_important'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $noteId;
        $sql = "UPDATE nurse_notes SET " . implode(', ', $fields) . " WHERE note_id = ?";
        
        return $this->db->execute($sql, $params);
    }
    
    /**
     * Get recent notes for assigned patients
     * 
     * @param int $nurseId Nurse staff serial number
     * @param int $limit Number of records
     * @return array Recent notes
     */
    public function getRecentNotes($nurseId, $limit = 10) {
        $sql = "SELECT nn.*, 
                       CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                       s.full_name as nurse_name
                FROM nurse_notes nn
                INNER JOIN patient p ON nn.patient_id = p.patient_id
                LEFT JOIN staff s ON nn.nurse_id = s.sl_no
                INNER JOIN ipd_admissions ia ON nn.admission_id = ia.admission_id
                INNER JOIN nurse_allocation na ON FIND_IN_SET(ia.bed_id, na.assigned_beds)
                WHERE na.role_id = ?
                  AND CURDATE() BETWEEN na.shift_date_from AND na.shift_date_to
                ORDER BY nn.created_at DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$nurseId, $limit]);
    }
}
