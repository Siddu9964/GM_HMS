<?php
/**
 * Nurse MAR (Medication Administration Record) Model
 * Handles medication scheduling and administration
 * 
 * @package GM_HMS\Models
 * @version 1.0.0
 */

namespace GM_HMS\Models;

use GM_HMS\Database\SecureDatabase;
use Exception;

class NurseMARModel {
    private $db;
    
    public function __construct() {
        $this->db = SecureDatabase::getInstance();
    }
    
    /**
     * Get medication schedule for a specific date
     * 
     * @param string $date Date (Y-m-d)
     * @param int $nurseId Nurse staff serial number (optional)
     * @return array Medication schedule
     */
    public function getMedicationSchedule($date, $nurseId = null) {
        $sql = "SELECT nm.*, 
                       CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                       p.age, p.sex,
                       ia.bed_id,
                       b.bed_number,
                       b.room_number,
                       s.full_name as administered_by_name
                FROM nurse_mar nm
                INNER JOIN patient p ON nm.patient_id = p.patient_id
                LEFT JOIN ipd_admissions ia ON nm.admission_id = ia.admission_id
                LEFT JOIN hospital_beds b ON ia.bed_id = b.bed_id
                LEFT JOIN staff s ON nm.administered_by = s.sl_no
                WHERE DATE(nm.scheduled_at) = ?";
        
        $params = [$date];
        
        if ($nurseId) {
            $sql .= " AND EXISTS (
                        SELECT 1 FROM nurse_allocation na 
                        WHERE na.role_id = ? 
                          AND ? BETWEEN na.shift_date_from AND na.shift_date_to
                          AND FIND_IN_SET(ia.bed_id, na.assigned_beds)
                      ) OR 1=1"; // Bypass allocation for now to match User Request for "All Admitted Patients"
                      // Note: User requested all patients to be visible, so strict filtering is relaxed.
            $params[] = $nurseId;
            $params[] = $date;
        }
        
        $sql .= " ORDER BY nm.scheduled_at ASC, b.room_number, b.bed_number";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Administer medication
     * 
     * @param int $marId MAR ID
     * @param array $data Administration data
     * @return bool Success status
     */
    public function administerMedication($marId, $data) {
        $sql = "UPDATE nurse_mar SET 
                    administered_at = ?,
                    administered_by = ?,
                    status = ?,
                    response_notes = ?
                WHERE mar_id = ?";
        
        return $this->db->execute($sql, [
            $data['administered_at'] ?? date('Y-m-d H:i:s'),
            $data['administered_by'],
            $data['status'], // Given, Missed, Refused, Held
            $data['response_notes'] ?? null,
            $marId
        ]);
    }
    
    /**
     * Get missed medications
     * 
     * @param string $date Date (Y-m-d)
     * @return array List of missed medications
     */
    public function getMissedMedications($date = null) {
        $date = $date ?? date('Y-m-d');
        
        $sql = "SELECT nm.*, 
                       CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                       b.bed_number, b.room_number
                FROM nurse_mar nm
                INNER JOIN patient p ON nm.patient_id = p.patient_id
                LEFT JOIN ipd_admissions ia ON nm.admission_id = ia.admission_id
                LEFT JOIN hospital_beds b ON ia.bed_id = b.bed_id
                WHERE DATE(nm.scheduled_at) = ?
                  AND nm.status = 'Missed'
                ORDER BY nm.scheduled_at DESC";
        
        return $this->db->fetchAll($sql, [$date]);
    }
    
    /**
     * Get medications for a specific patient admission
     * 
     * @param string $admissionId Admission ID
     * @return array Patient medications
     */
    public function getPatientMedications($admissionId) {
        $sql = "SELECT nm.*, s.full_name as administered_by_name
                FROM nurse_mar nm
                LEFT JOIN staff s ON nm.administered_by = s.sl_no
                WHERE nm.admission_id = ?
                ORDER BY nm.scheduled_at DESC";
        
        return $this->db->fetchAll($sql, [$admissionId]);
    }
    
    /**
     * Get MAR statistics
     * 
     * @param int $nurseId Nurse staff serial number
     * @param string $date Date (Y-m-d)
     * @return array Statistics
     */
    public function getMARStatistics($nurseId, $date = null) {
        $date = $date ?? date('Y-m-d');
        
        $stats = [];
        
        // Total scheduled
        $sql = "SELECT COUNT(*) as count
                FROM nurse_mar nm
                INNER JOIN ipd_admissions ia ON nm.admission_id = ia.admission_id
                INNER JOIN nurse_allocation na ON FIND_IN_SET(ia.bed_id, na.assigned_beds)
                WHERE na.role_id = ?
                  AND ? BETWEEN na.shift_date_from AND na.shift_date_to
                  AND DATE(nm.scheduled_at) = ?";
        $result = $this->db->fetchOne($sql, [$nurseId, $date, $date]);
        $stats['total_scheduled'] = (int)($result['count'] ?? 0);
        
        // Administered
        $sql = "SELECT COUNT(*) as count
                FROM nurse_mar nm
                WHERE nm.administered_by = ?
                  AND DATE(nm.administered_at) = ?
                  AND nm.status = 'Given'";
        $result = $this->db->fetchOne($sql, [$nurseId, $date]);
        $stats['administered'] = (int)($result['count'] ?? 0);
        
        // Pending
        $sql = "SELECT COUNT(*) as count
                FROM nurse_mar nm
                INNER JOIN ipd_admissions ia ON nm.admission_id = ia.admission_id
                INNER JOIN nurse_allocation na ON FIND_IN_SET(ia.bed_id, na.assigned_beds)
                WHERE na.role_id = ?
                  AND ? BETWEEN na.shift_date_from AND na.shift_date_to
                  AND DATE(nm.scheduled_at) = ?
                  AND nm.status = 'Scheduled'";
        $result = $this->db->fetchOne($sql, [$nurseId, $date, $date]);
        $stats['pending'] = (int)($result['count'] ?? 0);
        
        // Missed
        $sql = "SELECT COUNT(*) as count
                FROM nurse_mar nm
                INNER JOIN ipd_admissions ia ON nm.admission_id = ia.admission_id
                INNER JOIN nurse_allocation na ON FIND_IN_SET(ia.bed_id, na.assigned_beds)
                WHERE na.role_id = ?
                  AND ? BETWEEN na.shift_date_from AND na.shift_date_to
                  AND DATE(nm.scheduled_at) = ?
                  AND nm.status = 'Missed'";
        $result = $this->db->fetchOne($sql, [$nurseId, $date, $date]);
        $stats['missed'] = (int)($result['count'] ?? 0);
        
        return $stats;
    }
    
    /**
     * Get overdue medications
     * 
     * @param int $nurseId Nurse staff serial number
     * @return array Overdue medications
     */
    public function getOverdueMedications($nurseId) {
        $sql = "SELECT nm.*, 
                       CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                       b.bed_number, b.room_number,
                       TIMESTAMPDIFF(MINUTE, nm.scheduled_at, NOW()) as minutes_overdue
                FROM nurse_mar nm
                INNER JOIN patient p ON nm.patient_id = p.patient_id
                INNER JOIN ipd_admissions ia ON nm.admission_id = ia.admission_id
                INNER JOIN hospital_beds b ON ia.bed_id = b.bed_id
                INNER JOIN nurse_allocation na ON FIND_IN_SET(b.bed_id, na.assigned_beds)
                WHERE na.role_id = ?
                  AND CURDATE() BETWEEN na.shift_date_from AND na.shift_date_to
                  AND nm.scheduled_at < NOW()
                  AND nm.status = 'Scheduled'
                ORDER BY nm.scheduled_at ASC";
        
        return $this->db->fetchAll($sql, [$nurseId]);
    }
    
    /**
     * Create medication schedule entry
     * 
     * @param array $data Medication data
     * @return int New MAR ID
     */
    public function createMedicationSchedule($data) {
        $sql = "INSERT INTO nurse_mar (
                    admission_id, patient_id, medicine_name, dosage, route,
                    scheduled_at, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->execute($sql, [
            $data['admission_id'],
            $data['patient_id'],
            $data['medicine_name'],
            $data['dosage'] ?? null,
            $data['route'] ?? 'Oral',
            $data['scheduled_at'],
            'Scheduled'
        ]);
        
        return $this->db->lastInsertId();
    }
}
