<?php
/**
 * Nurse Shift Model
 * Handles all nurse shift-related database operations
 * 
 * @package GM_HMS\Models
 * @version 1.0.0
 */

namespace GM_HMS\Models;

use GM_HMS\Database\SecureDatabase;
use Exception;

class NurseShiftModel
{
    private $db;

    public function __construct()
    {
        $this->db = SecureDatabase::getInstance();
    }

    /**
     * Get current active shift for a specific nurse
     * 
     * @param int $nurseId Nurse user ID
     * @return array|null Current shift details
     */
    public function getCurrentShift($roleId)
    {
        $currentShiftType = $this->getCurrentShiftType();
        $sql = "SELECT na.*
                FROM nurse_allocation na
                WHERE na.role_id = ? 
                  AND na.shift_type = ?
                  AND CURDATE() BETWEEN na.shift_date_from AND na.shift_date_to
                  AND na.status IN ('Active', 'Scheduled')
                LIMIT 1";

        return $this->db->fetchOne($sql, [$roleId, $currentShiftType]);
    }

    /**
     * Get all shifts for a role within date range
     * 
     * @param int $roleId Role ID
     * @param string $dateFrom Start date (Y-m-d)
     * @param string $dateTo End date (Y-m-d)
     * @return array List of shifts
     */
    public function getShiftsByNurse($roleId, $dateFrom = null, $dateTo = null)
    {
        $dateFrom = $dateFrom ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $dateTo ?? date('Y-m-d');

        $sql = "SELECT * FROM nurse_allocation 
                WHERE role_id = ? 
                  AND (
                    shift_date_from BETWEEN ? AND ? OR
                    shift_date_to BETWEEN ? AND ? OR
                    (shift_date_from <= ? AND shift_date_to >= ?)
                  )
                ORDER BY shift_date_from DESC, shift_type";

        return $this->db->fetchAll($sql, [$roleId, $dateFrom, $dateTo, $dateFrom, $dateTo, $dateFrom, $dateTo]);
    }

    /**
     * Get patients assigned to a role's current shift
     * 
     * @param int $roleId Role ID
     * @return array List of assigned patients
     */
    public function getAssignedPatients($roleId)
    {
        // This is the original method, keeping it for compatibility if needed.
        // However, we will implement the redesigned one below.
        return $this->getAssignedPatientsRedesigned(null, $roleId);
    }

    /**
     * Determine current shift type based on server time
     * 
     * @return string Morning, Evening, or Night
     */
    public function getCurrentShiftType()
    {
        $hour = (int)date('H');
        if ($hour >= 6 && $hour < 14) {
            return 'Morning';
        } elseif ($hour >= 14 && $hour < 22) {
            return 'Evening';
        } else {
            return 'Night';
        }
    }

    /**
     * Get patients assigned to a nurse based on their active shift and allocation
     * Redesigned to follow strict requirement: nurse-wise, shift-wise, and ward/room-wise.
     * 
     * @param int $nurseId User ID of the nurse
     * @param int $roleId Role ID of the nurse
     * @return array List of assigned patients
     */
    public function getAssignedPatientsRedesigned($nurseId, $roleId)
    {
        // 1. USER REQUEST: Display all patients from ipd_admissions regardless of allocation
        $sql = "SELECT DISTINCT 
                    p.patient_id, p.first_name, p.last_name, p.age, p.sex, p.blood_group,
                    ia.admission_id, ia.admission_date, ia.diagnosis, ia.bed_id,
                    ia.room_no as room_number, 
                    ia.room_name,
                    ia.ward_name as room_type,
                    ia.floor_name,
                    COALESCE(b.bed_number, CAST(ia.bed_id AS CHAR)) as bed_number,
                    d.full_name as doctor_name
                FROM ipd_admissions ia
                INNER JOIN patient p ON ia.patient_id = p.patient_id
                LEFT JOIN hospital_beds b ON ia.bed_id = b.bed_id
                LEFT JOIN doctors d ON ia.admitting_doctor_id = d.doctor_id
                WHERE ia.status IN ('Active', 'Admitted')
                ORDER BY ia.floor_name, ia.ward_name, ia.room_no, ia.bed_id";

        return $this->db->fetchAll($sql);
    }

    /**
     * Update shift status
     * 
     * @param int $allocationId Allocation ID
     * @param string $status New status (Scheduled/Active/Completed)
     * @return bool Success status
     */
    public function updateShiftStatus($allocationId, $status)
    {
        $sql = "UPDATE nurse_allocation SET status = ? WHERE id = ?";
        return $this->db->execute($sql, [$status, $allocationId]);
    }

    /**
     * Get shift statistics for a specific nurse
     * 
     * @param int $nurseId Nurse user ID
     * @param string $date Date (Y-m-d), defaults to today
     * @return array Statistics
     */
    public function getShiftStatistics($nurseId, $date = null)
    {
        // 1. USER REQUEST: Statistics should reflect all admitted patients
        $stats = [];

        // Total admitted patients (Active or Admitted)
        $sql = "SELECT COUNT(DISTINCT patient_id) as count
                FROM ipd_admissions 
                WHERE status IN ('Active', 'Admitted')";
        
        $result = $this->db->fetchOne($sql);
        $stats['total_patients'] = (int) ($result['count'] ?? 0);

        // Placeholder for other stats
        $stats['pending_medications'] = 0;
        $stats['pending_tasks'] = 0;
        $stats['vitals_recorded'] = 0;

        return $stats;
    }

    /**
     * Get upcoming shifts for a specific nurse
     * 
     * @param int $nurseId Nurse user ID
     * @param int $days Number of days to look ahead
     * @return array List of upcoming shifts
     */
    public function getUpcomingShifts($roleId, $days = 7)
    {
        // Fetch future shifts
        $sql = "SELECT * FROM nurse_allocation 
                WHERE role_id = ? 
                  AND shift_date_from > CURDATE()
                  AND shift_date_from <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                  ORDER BY shift_date_from, shift_type";

        return $this->db->fetchAll($sql, [$roleId, $days]);
    }

    /**
     * Create new allocation
     * 
     * @param array $data Allocation data
     * @return int New allocation ID
     */
    public function createShift($data)
    {
        $sql = "INSERT INTO nurse_allocation (
                    role_id, shift_date_from, shift_date_to, shift_date, shift_type, work_area,
                    ward_name, floor_name, floor_number, ward_type, room_number, room_name, assigned_beds, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        // For backward compatibility, set shift_date to shift_date_from
        $shiftDate = $data['shift_date'] ?? $data['shift_date_from'];

        $result = $this->db->execute($sql, [
            $data['role_id'],
            $data['shift_date_from'],
            $data['shift_date_to'],
            $shiftDate,
            $data['shift_type'],
            $data['work_area'] ?? null,
            $data['ward_name'] ?? '',
            $data['floor_name'] ?? null,
            $data['floor_number'] ?? null,
            $data['ward_type'] ?? null,
            $data['room_number'] ?? $data['assigned_beds'] ?? '',
            $data['room_name'] ?? '',
            $data['assigned_beds'] ?? null,
            $data['status'] ?? 'Scheduled'
        ]);

        return (int) $result['insert_id'];
    }

    /**
     * Create shift assignment with date range
     * Stores the date range in a single record
     * 
     * @param array $data Shift data including shift_date_from and shift_date_to
     * @return array Array with 'id' and 'count' (always 1)
     */
    public function createBulkShifts($data)
    {
        // Validate date range
        $dateFrom = new \DateTime($data['shift_date_from']);
        $dateTo = new \DateTime($data['shift_date_to']);

        if ($dateTo < $dateFrom) {
            throw new \Exception("End date must be after or equal to start date");
        }

        // Calculate number of days for validation
        $interval = $dateFrom->diff($dateTo);
        $days = $interval->days + 1;

        // Limit to prevent accidental long-term assignments
        if ($days > 365) {
            throw new \Exception("Date range cannot exceed 365 days");
        }

        // Create single shift record with date range
        $id = $this->createShift($data);

        return [
            'ids' => [$id],
            'count' => 1,
            'days' => $days
        ];
    }
}
