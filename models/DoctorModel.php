<?php
namespace GM_HMS\Models;

use Exception;
use GM_HMS\Database\SecureDatabase;

class DoctorModel {
    private $db;
    
    public function __construct() {
        $this->db = SecureDatabase::getInstance();
    }
    
    /**
     * Get all active doctors with availability status
     * 
     * @return array List of doctors
     */
    public function getAllDoctors() {
        $sql = "SELECT d.*
                FROM doctors d
                WHERE d.status = 'Active'
                ORDER BY d.full_name ASC";
        
        $doctors = $this->db->fetchAll($sql);
        
        $formattedDoctors = [];
        foreach ($doctors as $row) {
            $formattedDoctors[] = $this->formatDoctorData($row);
        }
        
        return $formattedDoctors;
    }
    
    /**
     * Get single doctor by ID
     * 
     * @param string $doctorId Doctor ID
     * @return array|null Doctor data or null if not found
     */
    public function getDoctorById($doctorId) {
        $sql = "SELECT d.*
                FROM doctors d
                WHERE d.doctor_id = ? AND d.status = 'Active'";
        
        $row = $this->db->fetchOne($sql, [$doctorId]);
        
        if (!$row) {
            return null;
        }
        
        return $this->formatDoctorData($row);
    }
    
    /**
     * Create new doctor
     * 
     * @param array $data Doctor data
     * @return string New doctor ID
     */
    public function createDoctor($data) {
        $this->db->beginTransaction();
        try {
            // Generate doctor ID
            $doctorId = $this->generateDoctorId();
            $hashedPassword = isset($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null;
            
            // 1. Insert into doctors table
            $sqlDoctor = "INSERT INTO doctors (
                        doctor_id, full_name, gender, date_of_birth, age, blood_group,
                        marital_status, mobile_number, alternate_mobile, email, address,
                        city, state, country, pincode, qualification, specialization,
                        sub_specialization, medical_council, registration_number,
                        registration_year, experience_years, department_id, designation,
                        employment_type, joining_date, shift_type, consultation_fee,
                        salary, room_number, status, available_days, in_time, out_time,
                        emergency_available, username, password, role, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $paramsDoctor = [
                $doctorId,
                $data['full_name'],
                $data['gender'] ?? null,
                $data['date_of_birth'] ?? null,
                $data['age'] ?? null,
                $data['blood_group'] ?? null,
                $data['marital_status'] ?? null,
                $data['mobile_number'] ?? null,
                $data['alternate_mobile'] ?? null,
                $data['email'] ?? null,
                $data['address'] ?? null,
                $data['city'] ?? null,
                $data['state'] ?? null,
                $data['country'] ?? null,
                $data['pincode'] ?? null,
                $data['qualification'] ?? null,
                $data['specialization'] ?? null,
                $data['sub_specialization'] ?? null,
                $data['medical_council'] ?? null,
                $data['registration_number'] ?? null,
                $data['registration_year'] ?? null,
                $data['experience_years'] ?? null,
                $data['department_id'] ?? null,
                $data['designation'] ?? null,
                $data['employment_type'] ?? null,
                $data['joining_date'] ?? null,
                $data['shift_type'] ?? null,
                $data['consultation_fee'] ?? null,
                $data['salary'] ?? null,
                $data['room_number'] ?? null,
                'Active',
                $data['available_days'] ?? null,
                $data['in_time'] ?? null,
                $data['out_time'] ?? null,
                $data['emergency_available'] ?? 'No',
                $data['username'] ?? null,
                $hashedPassword,
                'Doctor',
                $data['created_by'] ?? 'system'
            ];
            
            $this->db->execute($sqlDoctor, $paramsDoctor);

            // 2. Insert into user table for authentication
            if (!empty($data['username']) && !empty($hashedPassword)) {
                $sqlUser = "INSERT INTO user (id, username, password, role) VALUES (?, ?, ?, ?)";
                $this->db->execute($sqlUser, [
                    $doctorId,
                    $data['username'],
                    $hashedPassword,
                    'Doctor' // Match convention
                ]);
            }
            
            $this->db->commit();
            return $doctorId;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Update existing doctor
     * 
     * @param string $doctorId Doctor ID
     * @param array $data Updated data
     * @return bool Success status
     */
    public function updateDoctor($doctorId, $data) {
        $this->db->beginTransaction();
        try {
            // Build dynamic UPDATE query for doctors table
            $fields = [];
            $params = [];
            
            $allowedFields = [
                'full_name', 'gender', 'date_of_birth', 'age', 'blood_group',
                'marital_status', 'mobile_number', 'alternate_mobile', 'email',
                'address', 'city', 'state', 'country', 'pincode', 'qualification',
                'specialization', 'sub_specialization', 'medical_council',
                'registration_number', 'registration_year', 'experience_years',
                'department_id', 'designation', 'employment_type', 'joining_date',
                'shift_type', 'consultation_fee', 'salary', 'room_number',
                'available_days', 'in_time', 'out_time', 'emergency_available', 'photo',
                'username', 'password'
            ];
            
            $userUpdateData = [];
            if (isset($data['username'])) $userUpdateData['username'] = $data['username'];
            if (isset($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                $userUpdateData['password'] = $data['password'];
            }

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "`$field` = ?";
                    $params[] = $data[$field];
                }
            }
            
            if (!empty($fields)) {
                $params[] = $doctorId;
                $sql = "UPDATE doctors SET " . implode(', ', $fields) . " WHERE doctor_id = ?";
                $this->db->execute($sql, $params);
            }

            // Sync with user table
            if (!empty($userUpdateData) && !empty($doctorId)) {
                $userExists = $this->db->fetchOne("SELECT id FROM user WHERE id = ?", [$doctorId]);
                
                if ($userExists) {
                    $userFields = [];
                    $userParams = [];
                    foreach ($userUpdateData as $col => $val) {
                        $userFields[] = "`$col` = ?";
                        $userParams[] = $val;
                    }
                    $userParams[] = $doctorId;
                    $sqlUser = "UPDATE user SET " . implode(', ', $userFields) . " WHERE id = ?";
                    $this->db->execute($sqlUser, $userParams);
                } else {
                    if (!empty($userUpdateData['username']) && !empty($userUpdateData['password'])) {
                        $sqlUser = "INSERT INTO user (id, username, password, role) VALUES (?, ?, ?, ?)";
                        $this->db->execute($sqlUser, [
                            $doctorId,
                            $userUpdateData['username'],
                            $userUpdateData['password'],
                            'Doctor'
                        ]);
                    }
                }
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Delete doctor (soft delete - set status to Inactive)
     * 
     * @param string $doctorId Doctor ID
     * @return bool Success status
     */
    public function deleteDoctor($doctorId) {
        $this->db->beginTransaction();
        try {
            // Update doctor status
            $sqlDoctor = "UPDATE doctors SET status = 'Inactive' WHERE doctor_id = ?";
            $this->db->execute($sqlDoctor, [$doctorId]);

            // Hard delete from user table for consistency
            $sqlUser = "DELETE FROM user WHERE id = ?";
            $this->db->execute($sqlUser, [$doctorId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Get department name by ID
     * 
     * @param int $departmentId Department ID
     * @return string Department name
     */
    private function getDepartmentName($departmentId) {
        if (empty($departmentId)) {
            return 'General';
        }
        
        $sql = "SELECT department_name FROM departments WHERE department_id = ?";
        $row = $this->db->fetchOne($sql, [$departmentId]);
        
        if ($row) {
            return $row['department_name'];
        }
        
        return 'General';
    }
    
    /**
     * Calculate availability status based on check-in/check-out AND available days
     * 
     * Logic:
     * 1. Doctor must be checked in (in_time set, out_time NULL)
     * 2. AND today's day must be in available_days list
     * 
     * @param string $inTime Check-in time
     * @param string $outTime Check-out time
     * @param string $availableDays Comma-separated days (e.g., "Mon,Tue,Wed,Thu,Fri")
     * @return string Availability status
     */
    private function calculateAvailability($inTime, $outTime, $availableDays = null) {
        // First check: Doctor must be checked in (in_time set, out_time NULL)
        if (empty($inTime) || !empty($outTime)) {
            return 'Off-Duty';  // Not checked in or already checked out
        }
        
        // Second check: If available_days is set, check if today is in the list
        if (!empty($availableDays)) {
            // Get today's day abbreviation (Mon, Tue, Wed, etc.)
            $today = date('D');  // Returns: Mon, Tue, Wed, Thu, Fri, Sat, Sun
            
            // Convert available_days string to array
            $daysArray = array_map('trim', explode(',', $availableDays));
            
            // Check if today is in the available days
            if (!in_array($today, $daysArray)) {
                return 'Off-Duty';  // Today is not a working day for this doctor
            }
        }
        
        // Both checks passed: checked in AND today is a working day
        return 'Available';
    }
    
    /**
     * Format doctor data for API response
     * 
     * @param array $row Database row
     * @return array Formatted doctor data
     */
    private function formatDoctorData($row) {
        return [
            'doctor_id' => $row['doctor_id'],
            'full_name' => $row['full_name'],
            'gender' => $row['gender'] ?? null,
            'date_of_birth' => $row['date_of_birth'] ?? null,
            'age' => $row['age'] ?? null,
            'blood_group' => $row['blood_group'] ?? null,
            'marital_status' => $row['marital_status'] ?? null,
            'mobile_number' => $row['mobile_number'] ?? null,
            'alternate_mobile' => $row['alternate_mobile'] ?? null,
            'email' => $row['email'] ?? null,
            'address' => $row['address'] ?? null,
            'city' => $row['city'] ?? null,
            'state' => $row['state'] ?? null,
            'country' => $row['country'] ?? null,
            'pincode' => $row['pincode'] ?? null,
            'qualification' => $row['qualification'] ?? null,
            'specialization' => $row['specialization'] ?? null,
            'sub_specialization' => $row['sub_specialization'] ?? null,
            'medical_council' => $row['medical_council'] ?? null,
            'registration_number' => $row['registration_number'] ?? null,
            'registration_year' => $row['registration_year'] ?? null,
            'experience_years' => $row['experience_years'] ?? null,
            'department_id' => $row['department_id'] ?? null,
            'department' => $this->getDepartmentName($row['department_id'] ?? null),
            'designation' => $row['designation'] ?? null,
            'employment_type' => $row['employment_type'] ?? null,
            'joining_date' => $row['joining_date'] ?? null,
            'shift_type' => $row['shift_type'] ?? null,
            'consultation_fee' => $row['consultation_fee'] ?? '0',
            'salary' => $row['salary'] ?? null,
            'room_number' => $row['room_number'] ?? null,
            'status' => $row['status'] ?? 'Active',
            'available_days' => $row['available_days'] ?? null,
            'in_time' => $row['in_time'] ?? null,
            'out_time' => $row['out_time'] ?? null,
            'emergency_available' => $row['emergency_available'] ?? 'No',
            'photo' => $row['photo'] ?? null,
            'availability' => $this->calculateAvailability(
                $row['in_time'] ?? null, 
                $row['out_time'] ?? null,
                $row['available_days'] ?? null
            )
        ];
    }
    
    /**
     * Get total count of active doctors
     * 
     * @return int Total count
     */
    public function getTotalCount() {
        try {
            $sql = "SELECT COUNT(*) as total FROM doctors WHERE status = 'Active'";
            $result = $this->db->fetchOne($sql);
            return (int)($result['total'] ?? 0);
        } catch (\Exception $e) {
            error_log("DoctorModel::getTotalCount Error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Generate unique doctor ID
     * 
     * @return string Doctor ID
     */
    private function generateDoctorId() {
        $prefix = 'DOC';
        $year = date('Y');
        
        // Get last doctor ID for this year
        $sql = "SELECT doctor_id FROM doctors 
                WHERE doctor_id LIKE ? 
                ORDER BY doctor_id DESC LIMIT 1";
        
        $row = $this->db->fetchOne($sql, [$prefix . $year . '%']);
        
        if ($row) {
            $lastId = $row['doctor_id'];
            $number = intval(substr($lastId, -4)) + 1;
        } else {
            $number = 1;
        }
        
        return $prefix . $year . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
