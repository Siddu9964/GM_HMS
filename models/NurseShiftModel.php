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
    public function getCurrentShift($nurseId)
    {
        $targetDate = date('Y-m-d');
        
        $sql = "SELECT floor_name, ward_name, room_type as work_area, room_name as assigned_beds, shift_data
                FROM shift_schedules 
                WHERE ? BETWEEN start_date AND end_date";
                
        $rows = $this->db->fetchAll($sql, [$targetDate]);
        
        foreach ($rows as $row) {
            $jsonData = json_decode($row['shift_data'], true);
            if (is_array($jsonData)) {
                foreach ($jsonData as $shift) {
                    // Match nurse + date + not a Week Off (any shift type is valid)
                    if ($shift['nurse_id'] == $nurseId 
                        && $shift['shift_date'] === $targetDate 
                        && $shift['shift_type'] !== 'Week Off') {
                        return [
                            'shift_date_from' => $targetDate,
                            'shift_date_to'   => $targetDate,
                            'shift_type'      => $shift['shift_type'],
                            'ward_name'       => $row['ward_name'],
                            'floor_name'      => $row['floor_name'],
                            'work_area'       => $row['work_area'],
                            'assigned_beds'   => $row['assigned_beds'],
                            'status'          => 'Active'
                        ];
                    }
                }
            }
        }
        
        return null;
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
    public function getAssignedPatientsRedesigned($nurseId, $roleId, $currentWard = null)
    {
        // USER REQUEST: Filter by current ward if assigned
        // If the nurse has no active ward today, they should see NO patients.
        if (!$currentWard) {
            return [];
        }

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
                LEFT JOIN hospital_beds b ON ia.bed_id = b.sl_no
                LEFT JOIN doctors d ON ia.admitting_doctor_id = d.doctor_id
                WHERE ia.status IN ('Active', 'Admitted')";
        
        $params = [];
        if ($currentWard) {
            $sql .= " AND b.floor_name = ? AND b.ward_name = ? AND b.room_type = ?";
            $params[] = $currentWard['floor_name'];
            $params[] = $currentWard['ward_name'];
            $params[] = $currentWard['room_type'];
        }
        
        $sql .= " ORDER BY b.floor_name, b.ward_name, b.room_number, b.bed_number";

        return $this->db->fetchAll($sql, $params);
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
    public function getShiftStatistics($nurseId, $date = null, $currentWard = null)
    {
        $stats = [
            'total_patients' => 0,
            'pending_medications' => 0,
            'pending_tasks' => 0,
            'vitals_recorded' => 0
        ];
        
        if (!$currentWard) {
            return $stats;
        }

        // Total admitted patients (Active or Admitted)
        $sql = "SELECT COUNT(DISTINCT ia.patient_id) as count
                FROM ipd_admissions ia
                LEFT JOIN hospital_beds b ON ia.bed_id = b.sl_no
                WHERE ia.status IN ('Active', 'Admitted')";
                
        $params = [];
        if ($currentWard) {
            $sql .= " AND b.floor_name = ? AND b.ward_name = ? AND b.room_type = ?";
            $params[] = $currentWard['floor_name'];
            $params[] = $currentWard['ward_name'];
            $params[] = $currentWard['room_type'];
        }
        
        $result = $this->db->fetchOne($sql, $params);
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
    public function getUpcomingShifts($nurseId)
    {
        $startDate = date('Y-m-d');
        
        $sql = "SELECT floor_name, ward_name, room_type as work_area, room_name as assigned_beds, shift_data
                FROM shift_schedules 
                WHERE end_date >= ?";
                
        $rows = $this->db->fetchAll($sql, [$startDate]);
        
        $upcoming = [];
        
        foreach ($rows as $row) {
            $jsonData = json_decode($row['shift_data'], true);
            if (is_array($jsonData)) {
                foreach ($jsonData as $shift) {
                    if ($shift['nurse_id'] == $nurseId && $shift['shift_date'] > $startDate && $shift['shift_type'] !== 'Week Off') {
                        $upcoming[] = [
                            'shift_date_from' => $shift['shift_date'],
                            'shift_date_to' => $shift['shift_date'],
                            'shift_type' => $shift['shift_type'],
                            'ward_name' => $row['ward_name'],
                            'floor_name' => $row['floor_name'],
                            'work_area' => $row['work_area'],
                            'assigned_beds' => $row['assigned_beds'],
                            'status' => 'Scheduled'
                        ];
                    }
                }
            }
        }
        
        // Sort by date then type
        usort($upcoming, function($a, $b) {
            $dateCmp = strcmp($a['shift_date_from'], $b['shift_date_from']);
            if ($dateCmp !== 0) return $dateCmp;
            // Simplified shift type sort (Morning < Evening < Night)
            $types = ['Morning' => 1, 'Evening' => 2, 'Night' => 3];
            $tA = $types[$a['shift_type']] ?? 4;
            $tB = $types[$b['shift_type']] ?? 4;
            return $tA - $tB;
        });

        return $upcoming;
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
