<?php
namespace GM_HMS\Models;

use Exception;
use GM_HMS\Database\SecureDatabase;

class StaffModel {
    private $db;
    
    public function __construct() {
        $this->db = SecureDatabase::getInstance();
    }
    
    /**
     * Get all active staff members
     * 
     * @return array List of staff
     */
    public function getAllStaff() {
        $sql = "SELECT s.*
                FROM staff s
                WHERE s.status = 'Active'
                ORDER BY s.full_name ASC";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get distinct designations from staff table
     * 
     * @return array List of designations
     */
    public function getDistinctDesignations() {
        $sql = "SELECT DISTINCT designation FROM staff WHERE designation IS NOT NULL AND designation != '' ORDER BY designation ASC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get single staff member by sl_no
     * 
     * @param int $slNo Staff Serial Number
     * @return array|null Staff data or null if not found
     */
    public function getStaffById($slNo) {
        $sql = "SELECT s.*
                FROM staff s
                WHERE s.sl_no = ? AND s.status = 'Active'";
        
        return $this->db->fetchOne($sql, [$slNo]);
    }
    
    /**
     * Create new staff member
     * 
     * @param array $data Staff data
     * @return int New staff sl_no
     */
    public function createStaff($data) {
        $this->db->beginTransaction();
        
        try {
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $data['password'] = $hashedPassword;
            
            // Auto-generate full_name
            if (!isset($data['full_name']) && isset($data['first_name']) && isset($data['last_name'])) {
                $data['full_name'] = $data['first_name'] . ' ' . $data['last_name'];
            }
            
            // Set default status and role
            $data['status'] = $data['status'] ?? 'Active';
            $data['role'] = $data['role'] ?? 'staff';
            $data['role_id'] = $this->generateRoleId();
            
            // 1. Insert into staff table
            $sqlStaff = "INSERT INTO staff (
                role_id, designation, first_name, last_name, full_name, gender,
                date_of_birth, age, blood_group, marital_status, mobile_number,
                alternate_mobile, email, address, city, state, country, pincode,
                qualification, experience_years, previous_organization,
                employment_type, joining_date, shift_type, salary, bank_name,
                bank_account_number, ifsc_code, working_hours, weekly_off,
                overtime_allowed, username, password, role, status,
                id_proof_type, id_proof_number
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $result = $this->db->execute($sqlStaff, [
                $data['role_id'] ?? null,
                $data['designation'] ?? null,
                $data['first_name'] ?? null,
                $data['last_name'] ?? null,
                $data['full_name'] ?? null,
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
                $data['experience_years'] ?? null,
                $data['previous_organization'] ?? null,
                $data['employment_type'] ?? null,
                $data['joining_date'] ?? null,
                $data['shift_type'] ?? null,
                $data['salary'] ?? null,
                $data['bank_name'] ?? null,
                $data['bank_account_number'] ?? null,
                $data['ifsc_code'] ?? null,
                $data['working_hours'] ?? null,
                $data['weekly_off'] ?? null,
                $data['overtime_allowed'] ?? null,
                $data['username'] ?? null,
                $hashedPassword,
                $data['role'],
                $data['status'],
                $data['id_proof_type'] ?? null,
                $data['id_proof_number'] ?? null
            ]);
            
            $slNo = $result['insert_id'];
            
            // 2. Insert into user table for authentication
            if (!empty($data['username']) && !empty($data['password'])) {
                $sqlUser = "INSERT INTO user (id, username, password, role) VALUES (?, ?, ?, ?)";
                // Use explicitly provided role or default to 'Staff'
                $userRole = ucfirst($data['role'] ?? 'Staff');
                $this->db->execute($sqlUser, [
                    (string)$data['role_id'],
                    $data['username'],
                    $hashedPassword,
                    $userRole
                ]);
            }
            
            $this->db->commit();
            return $slNo;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Update existing staff member
     * 
     * @param int $slNo Staff sl_no
     * @param array $data Updated data
     * @return bool Success status
     */
    public function updateStaff($slNo, $data) {
        $this->db->beginTransaction();
        try {
            // Build dynamic UPDATE query for staff table
            $fields = [];
            $params = [];
            
            $allowedFields = [
                'role_id', 'designation', 'first_name', 'last_name', 'full_name', 'gender',
                'date_of_birth', 'age', 'blood_group', 'marital_status', 'mobile_number',
                'alternate_mobile', 'email', 'address', 'city', 'state', 'country', 'pincode',
                'qualification', 'experience_years', 'previous_organization',
                'employment_type', 'joining_date', 'shift_type', 'salary', 'bank_name',
                'bank_account_number', 'ifsc_code', 'working_hours', 'weekly_off',
                'overtime_allowed', 'username', 'password', 'role', 'status', 'photo',
                'id_proof_type', 'id_proof_number'
            ];
            
            $userUpdateData = [];
            if (isset($data['username'])) $userUpdateData['username'] = $data['username'];
            if (isset($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                $userUpdateData['password'] = $data['password'];
            }
            if (isset($data['role'])) {
                $userUpdateData['role'] = ucfirst($data['role']);
            }

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "`$field` = ?";
                    $params[] = $data[$field];
                }
            }

            $current = $this->getStaffById($slNo);
            if (!$current) {
                throw new Exception("Staff not found");
            }

            // Sync full_name if first_name or last_name is updated
            if ((isset($data['first_name']) || isset($data['last_name'])) && !isset($data['full_name'])) {
                $firstName = $data['first_name'] ?? $current['first_name'];
                $lastName = $data['last_name'] ?? $current['last_name'];
                $fullName = trim($firstName . ' ' . $lastName);
                $fields[] = "`full_name` = ?";
                $params[] = $fullName;
            }

            
            if (!empty($fields)) {
                $params[] = $slNo;
                $sql = "UPDATE staff SET " . implode(', ', $fields) . " WHERE sl_no = ?";
                $this->db->execute($sql, $params);
            }

            // Sync with user table using role_id
            if (!empty($userUpdateData) && !empty($current['role_id'])) {
                $roleIdStr = (string)$current['role_id'];
                $userExists = $this->db->fetchOne("SELECT sl_no FROM user WHERE id = ?", [$roleIdStr]);
                
                if ($userExists) {
                    $userFields = [];
                    $userParams = [];
                    foreach ($userUpdateData as $col => $val) {
                        $userFields[] = "`$col` = ?";
                        $userParams[] = $val;
                    }
                    $userParams[] = $roleIdStr;
                    $sqlUser = "UPDATE user SET " . implode(', ', $userFields) . " WHERE id = ?";
                    $this->db->execute($sqlUser, $userParams);
                } else {
                    // User does not exist, insert them if we have enough data
                    if (!empty($userUpdateData['username']) && !empty($userUpdateData['password'])) {
                        $sqlUser = "INSERT INTO user (id, username, password, role) VALUES (?, ?, ?, ?)";
                        $this->db->execute($sqlUser, [
                            $roleIdStr,
                            $userUpdateData['username'],
                            $userUpdateData['password'],
                            $userUpdateData['role'] ?? 'Staff'
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
     * Delete staff member (soft delete)
     * 
     * @param int $slNo Staff sl_no
     * @return bool Success status
     */
    public function deleteStaff($slNo) {
        $this->db->beginTransaction();
        try {
            // Update staff status
            $sqlStaff = "UPDATE staff SET status = 'Inactive' WHERE sl_no = ?";
            $this->db->execute($sqlStaff, [$slNo]);

            // Hard delete from user table using role_id
            $current = $this->getStaffById($slNo);
            if ($current && !empty($current['role_id'])) {
                $sqlUser = "DELETE FROM user WHERE id = ?";
                $this->db->execute($sqlUser, [(string)$current['role_id']]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Generate sequential numeric role_id
     * 
     * @return int New role_id
     */
    private function generateRoleId() {
        $sql = "SELECT MAX(CAST(role_id AS UNSIGNED)) as max_id FROM staff";
        $row = $this->db->fetchOne($sql);
        return (int)($row['max_id'] ?? 0) + 1;
    }
}
