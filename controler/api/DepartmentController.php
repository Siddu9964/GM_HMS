<?php
/**
 * ============================================================
 * DepartmentController — API Reference
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api
 * Auth     : All endpoints require Auth (Session or Bearer token)
 * ------------------------------------------------------------
 *
 * 1. GET /api/departments
 *    Query: search (string) - optional
 *    Response: [ { department_id, department_name, head_doctor, status } ]
 *
 * 2. GET /api/departments/{id}
 *    Response: Full department object
 *
 * 3. POST /api/departments
 *    Body: { "department_name":"Cardiology", "head_doctor":"Dr. Mehta", "status":"Active" }
 *    Response 201: { department_id, ... }
 *
 * 4. PUT /api/departments/{id}
 *    Body: Send only fields to update
 *
 * 5. DELETE /api/departments/{id}
 * ------------------------------------------------------------
 */
namespace GM_HMS\Controllers\api;

use Exception;
use GM_HMS\Controllers\BaseController;
use GM_HMS\Middleware\RateLimiter;
use GM_HMS\Security\TokenManager;

/**
 * Doctor Controller
 */
class DepartmentController extends BaseController {
    
    /**
     * GET /api/departments
     * Get all departments
     */
    public function index() {
        $this->restrictMethod('GET');
        
        try {
            $departments = $this->db->fetchAll(
                'SELECT sl_no, department_id, department_name, department_type, description,
                        floor_number, building_name, head_doctor_id, contact_number, email, status
                 FROM departments ORDER BY sl_no DESC'
            );
            
            $this->respondSuccess($departments);
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * GET /api/departments/{id}
     * Get single department by ID
     */
    public function show($id) {
        $this->restrictMethod('GET');
        
        try {
            $department = $this->db->fetchOne(
                'SELECT sl_no, department_id, department_name, department_type, description,
                        floor_number, building_name, head_doctor_id, contact_number, email, status
                 FROM departments WHERE department_id = ?',
                [$id]
            );
            
            if (!$department) {
                $this->respondNotFound('Department not found');
            }
            
            $this->respondSuccess($department);
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * POST /api/departments
     * Create new department
     */
    public function create() {
        $this->restrictMethod('POST');
        
        // Define JSON schema for validation
        $schema = [
            'required' => ['department_name', 'department_type'],
            'properties' => [
                'department_name' => ['type' => 'string', 'minLength' => 2, 'maxLength' => 100],
                'department_type' => ['type' => 'string', 'enum' => ['Clinical', 'Non-Clinical', 'Support']],
                'description' => ['type' => 'string', 'maxLength' => 500],
                'floor_number' => ['type' => 'integer'],
                'building_name' => ['type' => 'string', 'maxLength' => 100],
                'head_doctor_id' => ['type' => 'integer'],
                'contact_number' => ['type' => 'string', 'maxLength' => 20],
                'email' => ['type' => 'string', 'format' => 'email'],
                'status' => ['type' => 'string', 'enum' => ['Active', 'Inactive']]
            ],
            'additionalProperties' => false
        ];
        
        $data = $this->getJsonInput($schema);
        
        // Sanitize input
        if (isset($data['department_name'])) $data['department_name'] = $this->sanitizer->sanitizeString($data['department_name']);
        if (isset($data['description'])) $data['description'] = $this->sanitizer->sanitizeString($data['description']);
        if (isset($data['building_name'])) $data['building_name'] = $this->sanitizer->sanitizeString($data['building_name']);
        if (isset($data['contact_number'])) $data['contact_number'] = $this->sanitizer->sanitizeString($data['contact_number']);
        if (isset($data['email'])) $data['email'] = $this->sanitizer->sanitizeEmail($data['email']);
        
        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = 'Active';
        }
        
        // Generate department_id in format DEPT-001
        $lastDepartment = $this->db->fetchOne(
            "SELECT department_id FROM departments ORDER BY sl_no DESC LIMIT 1"
        );
        
        if ($lastDepartment) {
            $lastNumber = (int)substr($lastDepartment['department_id'], 5);
            $newNumber = $lastNumber + 1;
            $newSequence = str_pad($newNumber, 3, '0', STR_PAD_LEFT);
        } else {
            $newSequence = '001';
        }
        
        $data['department_id'] = 'DEPT-' . $newSequence;
        
        try {
            // Check if department name already exists
            $existing = $this->db->fetchOne(
                'SELECT department_id FROM departments WHERE department_name = ?',
                [$data['department_name']]
            );
            
            if ($existing) {
                $this->respondBadRequest('Department name already exists');
            }
            
            // Insert department
            $insertId = $this->db->insert('departments', $data);
            
            // Return created department
            $department = $this->db->fetchOne(
                'SELECT * FROM departments WHERE department_id = ?',
                [$data['department_id']]
            );
            
            $this->respondCreated($department);
            
        } catch (Exception $e) {
            error_log("Department creation error: " . $e->getMessage());
            $this->respondError('Database operation failed: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * PUT /api/departments/{id}
     * Update department
     */
    public function update($id) {
        $this->restrictMethod('PUT');
        
        $schema = [
            'properties' => [
                'department_name' => ['type' => 'string', 'minLength' => 2],
                'department_type' => ['type' => 'string', 'enum' => ['Clinical', 'Non-Clinical', 'Support']],
                'description' => ['type' => 'string'],
                'floor_number' => ['type' => 'integer'],
                'building_name' => ['type' => 'string'],
                'head_doctor_id' => ['type' => 'integer'],
                'contact_number' => ['type' => 'string'],
                'email' => ['type' => 'string', 'format' => 'email'],
                'status' => ['type' => 'string', 'enum' => ['Active', 'Inactive']]
            ],
            'additionalProperties' => false
        ];
        
        $data = $this->getJsonInput($schema);
        
        // Sanitize input
        if (isset($data['department_name'])) $data['department_name'] = $this->sanitizer->sanitizeString($data['department_name']);
        if (isset($data['description'])) $data['description'] = $this->sanitizer->sanitizeString($data['description']);
        if (isset($data['building_name'])) $data['building_name'] = $this->sanitizer->sanitizeString($data['building_name']);
        if (isset($data['contact_number'])) $data['contact_number'] = $this->sanitizer->sanitizeString($data['contact_number']);
        if (isset($data['email'])) $data['email'] = $this->sanitizer->sanitizeEmail($data['email']);
        
        try {
            // Check if department exists
            $existing = $this->db->fetchOne(
                'SELECT department_id FROM departments WHERE department_id = ?',
                [$id]
            );
            
            if (!$existing) {
                $this->respondNotFound('Department not found');
            }
            
            // Update department
            $affected = $this->db->update('departments', $data, 'department_id = ?', [$id]);
            
            // Return updated department
            $department = $this->db->fetchOne(
                'SELECT * FROM departments WHERE department_id = ?',
                [$id]
            );
            
            $this->respondSuccess($department, 'Department updated successfully');
            
        } catch (Exception $e) {
            error_log("Department update error: " . $e->getMessage());
            $this->respondError('Database operation failed: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * DELETE /api/departments/{id}
     * Delete department
     */
    public function delete($id) {
        $this->restrictMethod('DELETE');
        
        try {
            $existing = $this->db->fetchOne(
                'SELECT department_id FROM departments WHERE department_id = ?',
                [$id]
            );
            
            if (!$existing) {
                $this->respondNotFound('Department not found');
            }
            
            $affected = $this->db->delete('departments', 'department_id = ?', [$id]);
            
            $this->respondSuccess(null, 'Department deleted successfully');
            
        } catch (Exception $e) {
            error_log("Department delete error: " . $e->getMessage());
            $this->respondError('Database operation failed: ' . $e->getMessage(), 500);
        }
    }
}

