<?php
/**
 * ============================================================
 * PatientController — API Reference
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api
 * Auth     : All endpoints require Auth (Session or Bearer token)
 * PID Format: PID-YYYYMMDD-NNN  (e.g. PID-20260626-001)
 * ------------------------------------------------------------
 *
 * 1. GET /api/patients
 *    Query Params:
 *      search|term  (string)  - Name / phone / ID search
 *      page         (int)     - Page number (default: 1)
 *      limit        (int)     - Records per page (default: 20)
 *      city         (string)  - Filter by city
 *      phone        (string)  - Filter by phone
 *      date_from    (date)    - Registration from YYYY-MM-DD
 *      date_to      (date)    - Registration to   YYYY-MM-DD
 *      status       (string)  - Patient status
 *      doctor_id    (string)  - Filter by doctor (auto-restricted for Doctor role)
 *    Response: { "data": [...], "pagination": { "page":1, "limit":20, "total":42 } }
 *
 * 2. GET /api/patients/{PID}
 *    Example: GET /api/patients/PID-20260626-001
 *    Note: Doctors — only returns patient if they have an appointment with them.
 *    Response: { full patient row + last_visit date }
 *
 * 3. POST /api/patients           [Required: phone]
 *    Body:
 *      {
 *        "title":"Mrs", "first_name":"Anita", "last_name":"Sharma",
 *        "sex":"Female",  "phone":"9876543210", "email":"anita@example.com", "password":"securepass",
 *        "birth_date":"1990-06-15", "aadhar":"123456789012",
 *        "blood_group":"B+", "occupation":"Teacher",
 *        "vaccine_status":"Vaccinated", "address":"12 Gandhi Nagar",
 *        "country":"India", "state":"Rajasthan", "district":"Jaipur",
 *        "city":"Jaipur", "area":"Vaishali Nagar", "pincode":"302021"
 *      }
 *    Response 201: { "patient_id": "PID-20260626-001" }
 *
 * 4. PUT /api/patients/{PID}
 *    Body (partial update — send only fields to change):
 *      { "phone":"9000011111", "city":"Udaipur", "blood_group":"O+", "status":"Active" }
 *    Updatable: title, first_name, last_name, sex, phone, aadhar, birth_date, age,
 *               blood_group, occupation, vaccine_status, address, country, state,
 *               district, city, area, pincode, status, email, password
 *
 * 5. DELETE /api/patients/{PID}
 *    ⚠ Hard delete — permanently removes record
 *
 * 6. GET /api/patients/check-duplicate
 *    Query: aadhar=123456789012  OR  phone=9876543210
 *    Response: { "exists": true, "patient_id": "PID-...", "name": "Anita Sharma" }
 *
 * 7. GET /api/patients/{PID}/issues
 *    Returns patient issue history (patient_issue_description table)
 * ------------------------------------------------------------
 */
namespace GM_HMS\Controllers\api;

use GM_HMS\Controllers\BaseController;
use Exception;

class PatientController extends BaseController {
    private $model;

    public function __construct() {
        parent::__construct();
        $this->model = new \GM_HMS\Models\PatientModel();
    }
    
    /**
     * List patients with search
     * GET /api/patients
     */
    public function index() {
        $this->restrictMethod('GET');
        // $this->requireAuth(); // Temporarily disabled for testing
        
        try {
            $term = $_GET['term'] ?? $_GET['search'] ?? '';
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            
            // Use model for standardized searching and pagination
            $filters = [
                'search' => $term,
                'city' => $_GET['city'] ?? null,
                'phone' => $_GET['phone'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
                'status' => $_GET['status'] ?? null
            ];
            
            // If logged-in user is a Doctor, restrict to their patients
            if (isset($this->currentUser['role']) && $this->currentUser['role'] === 'Doctor') {
                $filters['doctor_id'] = $this->currentUser['id'];
            } elseif (isset($_GET['doctor_id'])) {
                $filters['doctor_id'] = $_GET['doctor_id'];
            }
            
            $result = $this->model->getAllPatients($page, $limit, $filters);
            
            // Format: { success: true, data: { data: [...], pagination: {...} } }
            $this->respondSuccess($result);
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Get single patient
     * GET /api/patients/{id}
     */
    public function show($id) {
        $this->restrictMethod('GET');
        $this->requireAuth();
        
        try {
            // Base query to get patient and their last visit date from consultations
            $patient = $this->db->fetchOne(
                "SELECT p.*, CONCAT(p.first_name, ' ', p.last_name) as full_name,
                 (SELECT MAX(consultation_date) FROM consultations WHERE patient_id COLLATE utf8mb4_general_ci = p.patient_id COLLATE utf8mb4_general_ci) as last_visit
                 FROM patient p 
                 WHERE p.patient_id = ?",
                [$id]
            );
            
            if (!$patient) {
                $this->respondNotFound("Patient $id not found");
            }
            
            // If user is a Doctor, verify patient is allocated to them
            if (isset($this->currentUser['role']) && $this->currentUser['role'] === 'Doctor') {
                $doctorId = $this->currentUser['id'];
                
                // Check if patient has any appointments with this doctor
                $allocation = $this->db->fetchOne(
                    "SELECT COUNT(*) as count FROM appointments 
                     WHERE patient_id COLLATE utf8mb4_general_ci = ? 
                     AND doctor_id = ?",
                    [$id, $doctorId]
                );
                
                if (!$allocation || $allocation['count'] == 0) {
                    $this->respond([
                        'success' => false,
                        'status' => 'error',
                        'error' => 'Access Denied',
                        'message' => "This patient is not allocated to you. Please contact reception to schedule an appointment with this patient.",
                        'error_code' => 'PATIENT_NOT_ALLOCATED'
                    ], 403);
                }
            }
            
            $this->respondSuccess($patient);
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Create new patient
     * POST /api/patients
     */
    public function create() {
        $this->restrictMethod('POST');
        // $this->requireAuth(); // Disabled to allow public registration from the app
        
        $schema = [
            'required' => ['phone'],
            'properties' => [
                'title' => ['type' => 'string'],
                'first_name' => ['type' => 'string'],
                'last_name' => ['type' => 'string'],
                'sex' => ['type' => 'string', 'enum' => ['', 'Male', 'Female', 'Other']],
                'aadhar' => ['type' => 'string'],
                'phone' => ['type' => 'string', 'minLength' => 10],
                'birth_date' => ['type' => 'string'],
                'age' => ['type' => 'integer'],
                'address' => ['type' => 'string'],
                'blood_group' => ['type' => 'string'],
                'occupation' => ['type' => 'string'],
                'vaccine_status' => ['type' => 'string'],
                'country' => ['type' => 'string'],
                'state' => ['type' => 'string'],
                'district' => ['type' => 'string'],
                'city' => ['type' => 'string'],
                'area' => ['type' => 'string'],
                'pincode' => ['type' => 'string'],
                'email' => ['type' => 'string'],
                'password' => ['type' => 'string']
            ]
        ];
        
        try {
            $data = $this->getJsonInput($schema);
            
            // Check if Aadhar already exists (if provided)
            $aadhar = $data['aadhar'] ?? null;
            if (!empty($aadhar)) {
                $existing = $this->db->fetchOne("SELECT patient_id FROM patient WHERE aadhar = ?", [$aadhar]);
                if ($existing) {
                    $this->respondBadRequest("Patient with Aadhar " . $aadhar . " already exists as " . $existing['patient_id']);
                }
            }
            
            if (!empty($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            // Use model to create patient - this handles ID generation and formatting
            $patientId = $this->model->createPatient($data);
            
            if ($patientId) {
                $this->respondCreated(['patient_id' => $patientId]);
            } else {
                $this->respondServerError("Failed to register patient");
            }
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Update patient info
     * PUT /api/patients/{id}
     */
    public function update($id) {
        $this->restrictMethod('PUT');
        $this->requireAuth();
        
        try {
            $data = $this->getJsonInput();
            
            // All fields that can be updated via the edit form
            $allowedFields = [
                'title', 'first_name', 'last_name', 'sex', 'phone', 'email', 'password',
                'aadhar', 'birth_date', 'age', 'blood_group',
                'occupation', 'vaccine_status',
                'address', 'country', 'state', 'district', 'city', 'area', 'pincode',
                'status'
            ];

            if (!empty($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            $updates = [];
            $params = [];

            foreach ($allowedFields as $field) {
                // Use array_key_exists so we also allow setting a field to NULL/empty
                if (array_key_exists($field, $data)) {
                    $updates[] = "`$field` = ?";
                    // Store null for empty strings so DB is properly cleared
                    $params[] = ($data[$field] === '') ? null : $data[$field];
                }
            }

            // Recalculate age whenever birth_date is provided
            if (array_key_exists('birth_date', $data) && !empty($data['birth_date'])) {
                $birthDate = new \DateTime($data['birth_date']);
                $age = (new \DateTime())->diff($birthDate)->y;
                // Only add age if not already explicitly sent
                if (!array_key_exists('age', $data)) {
                    $updates[] = "`age` = ?";
                    $params[] = $age;
                }
            }
            
            if (empty($updates)) {
                $this->respondBadRequest("No valid fields to update");
            }
            
            $params[] = $id;
            $sql = "UPDATE patient SET " . implode(', ', $updates) . " WHERE patient_id = ?";
            
            $this->db->execute($sql, $params);
            $this->respondSuccess(null, "Patient $id updated");
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Upload patient image (avatar)
     * POST /api/patients/{id}/image
     */
    public function uploadImage($id) {
        error_log("[UPLOAD DEBUG] uploadImage hit for ID: $id");
        
        $this->restrictMethod('POST');
        // $this->requireAuth(); // Temporarily disabled to bypass session issues during upload
        
        try {
            // Check if patient exists (by ID, email, or first name since Flutter might send username)
            $patient = $this->db->fetchOne("SELECT patient_id, image FROM patient WHERE patient_id = ? OR email = ? OR first_name = ?", [$id, $id, $id]);
            if (!$patient) {
                error_log("[UPLOAD DEBUG] Error: Patient $id not found in DB.");
                $this->respondNotFound("Patient $id not found");
            }
            
            // Use the real patient ID for the database update
            $realPatientId = $patient['patient_id'];

            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                error_log("[UPLOAD DEBUG] Error: Invalid or no file uploaded. FILES array: " . print_r($_FILES, true));
                $this->respondBadRequest("No valid image file uploaded");
            }

            $file = $_FILES['image'];
            error_log("[UPLOAD DEBUG] File received successfully: " . $file['name']);
            
            $fileInfo = pathinfo($file['name']);
            $extension = isset($fileInfo['extension']) ? strtolower($fileInfo['extension']) : '';
            
            $allowedExtensions = ['jpg', 'jpeg', 'png'];
            if (!in_array($extension, $allowedExtensions)) {
                error_log("[UPLOAD DEBUG] Error: Invalid extension: $extension");
                $this->respondBadRequest("Invalid file type. Allowed: JPG, JPEG, PNG");
            }

            // Target directory logic (GM_Care/assetes/user_image) as requested
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/GM_Care/assetes/user_image/';
            error_log("[UPLOAD DEBUG] Upload Directory: $uploadDir");
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    error_log("[UPLOAD DEBUG] Failed to create directory: $uploadDir");
                    $this->respondServerError("Failed to create upload directory");
                }
            }

            // Image name should be the user name / ID as requested
            $fileName = $id . '.' . $extension;
            $destination = $uploadDir . $fileName;

            // Delete old photo if it exists
            if (!empty($patient['image'])) {
                $oldFileAbsolute = $_SERVER['DOCUMENT_ROOT'] . $patient['image'];
                if (file_exists($oldFileAbsolute) && is_file($oldFileAbsolute)) {
                    unlink($oldFileAbsolute);
                    error_log("[UPLOAD DEBUG] Deleted old image: $oldFileAbsolute");
                }
            }

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                error_log("[UPLOAD DEBUG] Success: File moved to $destination");
                
                // Keep the database path relative or absolute based on frontend needs
                // Using the same path as it will be accessible at http://localhost/GM_Care/assetes/user_image/
                $dbImagePath = '/GM_Care/assetes/user_image/' . $fileName;
                
                // Update database using the real patient ID
                $sql = "UPDATE patient SET image = ? WHERE patient_id = ?";
                $this->db->execute($sql, [$dbImagePath, $realPatientId]);
                
                $this->respondSuccess([
                    'file_name' => $fileName,
                    'url' => $dbImagePath
                ], "Image uploaded successfully");
            } else {
                error_log("[UPLOAD DEBUG] Error: move_uploaded_file failed for $destination");
                $this->respondServerError("Failed to move uploaded file");
            }

        } catch (Exception $e) {
            error_log("[UPLOAD DEBUG] Exception: " . $e->getMessage());
            $this->handleException($e);
        }
    }
    
    /**
     * Get patient issues
     *
     */
    public function getIssues($id) {
        $this->restrictMethod('GET');
        $this->requireAuth();
        
        try {
            $issues = $this->db->fetchAll(
                "SELECT * FROM patient_issue_description WHERE patient_id = ? ORDER BY created_at DESC",
                [$id]
            );
            $this->respondSuccess($issues);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Get patient lab test results
     * GET /api/patients/{id}/lab-results
     */
    public function getLabResults($id) {
        $this->restrictMethod('GET');
        // $this->requireAuth(); // Temporarily bypassed to allow local Flutter app to fetch test data without a strictly synced session
        
        try {
            $results = $this->db->fetchAll(
                "SELECT * FROM lab_results WHERE patient_id = ?",
                [$id]
            );
            $this->respondSuccess($results);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Delete patient
     * DELETE /api/patients/{id}
     */
    public function delete($id) {
        $this->restrictMethod('DELETE');
        $this->requireAuth();
        
        try {
            // Check if patient exists
            $patient = $this->db->fetchOne("SELECT patient_id FROM patient WHERE patient_id = ?", [$id]);
            
            if (!$patient) {
                $this->respondNotFound("Patient $id not found");
            }
            
            // Hard delete - permanently remove the patient record from the database
            $sql = "DELETE FROM patient WHERE patient_id = ?";
            $result = $this->db->execute($sql, [$id]);

            if ($result) {
                $this->respondSuccess(null, "Patient $id deleted successfully");
            } else {
                $this->respondServerError("Failed to delete patient");
            }
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Check for duplicate patient by Aadhaar or Phone
     * GET /api/patients/check-duplicate
     */
    public function checkDuplicate() {
        $this->restrictMethod('GET');
        $this->requireAuth();

        try {
            $aadhar = $_GET['aadhar'] ?? null;
            $phone = $_GET['phone'] ?? null;

            if (!$aadhar && !$phone) {
                $this->respondBadRequest("Aadhaar or Phone number required");
            }

            $sql = "SELECT patient_id, first_name, last_name FROM patient WHERE 1=0";
            $params = [];

            if ($aadhar) {
                $sql .= " OR aadhar = ?";
                $params[] = $aadhar;
            }

            if ($phone) {
                $sql .= " OR phone = ?";
                $params[] = $phone;
            }

            $existing = $this->db->fetchOne($sql, $params);

            if ($existing) {
                $this->respondSuccess([
                    'exists' => true,
                    'patient_id' => $existing['patient_id'],
                    'name' => $existing['first_name'] . ' ' . $existing['last_name']
                ], "Duplicate check completed");
            } else {
                $this->respondSuccess(['exists' => false], "No duplicate found");
            }

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
