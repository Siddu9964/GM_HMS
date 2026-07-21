<?php
namespace GM_HMS\Controllers\api;

use Exception;
use GM_HMS\Controllers\BaseController;
use GM_HMS\Models\StaffModel;

/**
 * Staff API Controller
 * 
 * API for staff management (nurses and other hospital staff)
 * Database: hmsci, Table: staff
 * 
 * @package GM_HMS\Controllers\API
 * @version 1.0.0
 * 
 * ============================================================================
 * API ENDPOINTS DOCUMENTATION
 * ============================================================================
 * 
 * BASE URL: http://localhost/GM_HMS/controler/api/StaffController.php
 * 
 * ----------------------------------------------------------------------------
 * 1. GET ALL STAFF
 * ----------------------------------------------------------------------------
 * Endpoint: GET /api/staff
 * Description: Retrieve all staff members
 * Authentication: None
 * 
 * Request Example:
 * GET http://localhost/GM_HMS/controler/api/StaffController.php/api/staff
 * 
 * Response Example (Success):
 * {
 *   "success": true,
 *   "data": [
 *     {
 *       "sl_no": 1,
 *       "role_id": 2,
 *       "designation": "Nurse",
 *       "first_name": "Jane",
 *       "last_name": "Smith",
 *       "full_name": "Jane Smith",
 *       "gender": "Female",
 *       "date_of_birth": "1990-05-15",
 *       "age": 33,
 *       "blood_group": "O+",
 *       "marital_status": "Single",
 *       "mobile_number": "9876543210",
 *       "alternate_mobile": "9876543211",
 *       "email": "jane.smith@hospital.com",
 *       "address": "123 Main Street",
 *       "city": "Mumbai",
 *       "state": "Maharashtra",
 *       "country": "India",
 *       "pincode": "400001",
 *       "qualification": "B.Sc Nursing",
 *       "experience_years": 5,
 *       "previous_organization": "City Hospital",
 *       "employment_type": "Full-time",
 *       "joining_date": "2020-01-15",
 *       "shift_type": "Morning",
 *       "salary": 35000.00,
 *       "bank_name": "HDFC Bank",
 *       "bank_account_number": "1234567890",
 *       "ifsc_code": "HDFC0001234",
 *       "working_hours": "9 AM - 5 PM",
 *       "weekly_off": "Sunday",
 *       "overtime_allowed": "Yes",
 *       "photo": null,
 *       "id_proof_type": "Aadhar Card",
 *       "id_proof_number": "1234-5678-9012",
 *       "resume": null,
 *       "username": "janesmith",
 *       "role": "staff",
 *       "last_login": null,
 *       "status": "Active"
 *     }
 *   ]
 * }
 * 
 * ----------------------------------------------------------------------------
 * 2. GET SINGLE STAFF
 * ----------------------------------------------------------------------------
 * Endpoint: GET /api/staff/{id}
 * Description: Retrieve a single staff member by ID
 * Authentication: None
 * 
 * Request Example:
 * GET http://localhost/GM_HMS/controler/api/StaffController.php/api/staff/1
 * 
 * Response Example (Success):
 * {
 *   "success": true,
 *   "data": {
 *     "sl_no": 1,
 *     "role_id": 2,
 *     "designation": "Nurse",
 *     "first_name": "Jane",
 *     "last_name": "Smith",
 *     "full_name": "Jane Smith",
 *     "gender": "Female",
 *     "date_of_birth": "1990-05-15",
 *     "age": 33,
 *     "blood_group": "O+",
 *     "marital_status": "Single",
 *     "mobile_number": "9876543210",
 *     "email": "jane.smith@hospital.com",
 *     "status": "Active"
 *   }
 * }
 * 
 * Response Example (Not Found):
 * {
 *   "success": false,
 *   "error": "Staff not found"
 * }
 * 
 * ----------------------------------------------------------------------------
 * 3. CREATE STAFF
 * ----------------------------------------------------------------------------
 * Endpoint: POST /api/staff
 * Description: Create a new staff member
 * Authentication: None
 * Content-Type: application/json
 * 
 * Request Body (JSON):
 * {
 *   "role_id": 2,
 *   "designation": "Nurse",
 *   "first_name": "Jane",
 *   "last_name": "Smith",
 *   "gender": "Female",
 *   "date_of_birth": "1990-05-15",
 *   "age": 33,
 *   "blood_group": "O+",
 *   "marital_status": "Single",
 *   "mobile_number": "9876543210",
 *   "alternate_mobile": "9876543211",
 *   "email": "jane.smith@hospital.com",
 *   "address": "123 Main Street",
 *   "city": "Mumbai",
 *   "state": "Maharashtra",
 *   "country": "India",
 *   "pincode": "400001",
 *   "qualification": "B.Sc Nursing",
 *   "experience_years": 5,
 *   "previous_organization": "City Hospital",
 *   "employment_type": "Full-time",
 *   "joining_date": "2020-01-15",
 *   "shift_type": "Morning",
 *   "salary": 35000.00,
 *   "bank_name": "HDFC Bank",
 *   "bank_account_number": "1234567890",
 *   "ifsc_code": "HDFC0001234",
 *   "working_hours": "9 AM - 5 PM",
 *   "weekly_off": "Sunday",
 *   "overtime_allowed": "Yes",
 *   "id_proof_type": "Aadhar Card",
 *   "id_proof_number": "1234-5678-9012",
 *   "username": "janesmith",
 *   "password": "SecurePass123",
 *   "role": "staff",
 *   "status": "Active"
 * }
 * 
 * Required Fields:
 * - first_name (string, min 2 chars)
 * - last_name (string, min 2 chars)
 * - gender (enum: Male, Female, Other)
 * - mobile_number (string)
 * - email (string, valid email format)
 * - designation (string, min 2 chars)
 * 
 * Response Example (Success):
 * {
 *   "success": true,
 *   "data": {
 *     "sl_no": 1,
 *     "first_name": "Jane",
 *     "last_name": "Smith",
 *     "full_name": "Jane Smith",
 *     "email": "jane.smith@hospital.com",
 *     "status": "Active"
 *   },
 *   "message": "Resource created successfully"
 * }
 * 
 * Response Example (Validation Error):
 * {
 *   "success": false,
 *   "error": "Validation failed: first_name is required"
 * }
 * 
 * Response Example (Duplicate Email):
 * {
 *   "success": false,
 *   "error": "Email already exists"
 * }
 * 
 * ----------------------------------------------------------------------------
 * 4. UPDATE STAFF
 * ----------------------------------------------------------------------------
 * Endpoint: PUT /api/staff/{id}
 * Description: Update an existing staff member
 * Authentication: None
 * Content-Type: application/json
 * 
 * Request Example:
 * PUT http://localhost/GM_HMS/controler/api/StaffController.php/api/staff/1
 * 
 * Request Body (JSON) - All fields optional:
 * {
 *   "first_name": "Jane",
 *   "last_name": "Doe",
 *   "mobile_number": "9876543999",
 *   "email": "jane.doe@hospital.com",
 *   "designation": "Senior Nurse",
 *   "salary": 45000.00,
 *   "status": "Active"
 * }
 * 
 * Response Example (Success):
 * {
 *   "success": true,
 *   "data": {
 *     "sl_no": 1,
 *     "first_name": "Jane",
 *     "last_name": "Doe",
 *     "full_name": "Jane Doe",
 *     "email": "jane.doe@hospital.com",
 *     "status": "Active"
 *   },
 *   "message": "Staff updated successfully"
 * }
 * 
 * Response Example (Not Found):
 * {
 *   "success": false,
 *   "error": "Staff not found"
 * }
 * 
 * ----------------------------------------------------------------------------
 * 5. DELETE STAFF
 * ----------------------------------------------------------------------------
 * Endpoint: DELETE /api/staff/{id}
 * Description: Delete a staff member
 * Authentication: None
 * 
 * Request Example:
 * DELETE http://localhost/GM_HMS/controler/api/StaffController.php/api/staff/1
 * 
 * Response Example (Success):
 * {
 *   "success": true,
 *   "data": null,
 *   "message": "Staff deleted successfully"
 * }
 * 
 * Response Example (Not Found):
 * {
 *   "success": false,
 *   "error": "Staff not found"
 * }
 * 
 * ============================================================================
 * FIELD SPECIFICATIONS
 * ============================================================================
 * 
 * ENUM Fields:
 * - gender: Male, Female, Other
 * - marital_status: Single, Married, Other
 * - employment_type: Full-time, Part-time, Contract
 * - shift_type: Morning, Evening, Night, Rotational
 * - overtime_allowed: Yes, No
 * - status: Active, Inactive
 * 
 * Auto-Generated Fields:
 * - full_name: Automatically generated from first_name + last_name
 * - password: Automatically hashed using PASSWORD_DEFAULT
 * 
 * Default Values:
 * - status: Active (if not provided)
 * - role: staff (if not provided)
 * 
 * ============================================================================
 */


class StaffController extends BaseController {
    private $staffModel;

    public function __construct() {
        parent::__construct();
        $this->staffModel = new StaffModel();
    }
    
    /**
     * GET /api/staff
     * Get all staff
     */
    public function index() {
        $this->restrictMethod('GET');
        
        try {
            $staff = $this->staffModel->getAllStaff();
            
            // Remove sensitive data
            foreach ($staff as &$s) {
                unset($s['password']);
            }
            
            $this->respondSuccess($staff);
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * GET /api/staff/designations
     * Get unique designations
     */
    public function getDesignations() {
        $this->restrictMethod('GET');
        
        try {
            $designations = $this->staffModel->getDistinctDesignations();
            $result = array_map(function($item) { return $item['designation']; }, $designations);
            $this->respondSuccess($result);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * GET /api/staff/{id}
     * Get single staff by ID
     */
    public function show($id) {
        $this->restrictMethod('GET');
        
        try {
            $staff = $this->staffModel->getStaffById($id);
            
            if (!$staff) {
                $this->respondNotFound('Staff not found');
            }
            
            unset($staff['password']);
            $this->respondSuccess($staff);
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * POST /api/staff
     * Create new staff
     */
    public function create() {
        $this->restrictMethod('POST');
        
        // Define JSON schema for validation
        $schema = [
            'required' => ['first_name', 'last_name', 'gender', 'mobile_number', 'email', 'designation', 'username', 'password'],
            'properties' => [
                'role_id' => ['type' => 'integer'],
                'designation' => ['type' => 'string', 'minLength' => 2, 'maxLength' => 100],
                'first_name' => ['type' => 'string', 'minLength' => 2, 'maxLength' => 100],
                'last_name' => ['type' => 'string', 'minLength' => 2, 'maxLength' => 100],
                'full_name' => ['type' => 'string', 'maxLength' => 200],
                'gender' => ['type' => 'string', 'enum' => ['Male', 'Female', 'Other']],
                'date_of_birth' => ['type' => 'string', 'format' => 'date'],
                'age' => ['type' => 'integer', 'minimum' => 18, 'maximum' => 100],
                'blood_group' => ['type' => 'string'],
                'marital_status' => ['type' => 'string'],
                'mobile_number' => ['type' => 'string', 'maxLength' => 20],
                'alternate_mobile' => ['type' => 'string', 'maxLength' => 20],
                'email' => ['type' => 'string', 'format' => 'email'],
                'address' => ['type' => 'string', 'maxLength' => 500],
                'city' => ['type' => 'string', 'maxLength' => 100],
                'state' => ['type' => 'string', 'maxLength' => 100],
                'country' => ['type' => 'string', 'maxLength' => 100],
                'pincode' => ['type' => 'string', 'maxLength' => 10],
                'qualification' => ['type' => 'string', 'maxLength' => 200],
                'experience_years' => ['type' => 'integer'],
                'previous_organization' => ['type' => 'string', 'maxLength' => 200],
                'employment_type' => ['type' => 'string'],
                'joining_date' => ['type' => 'string', 'format' => 'date'],
                'shift_type' => ['type' => 'string'],
                'salary' => ['type' => 'number'],
                'bank_name' => ['type' => 'string', 'maxLength' => 100],
                'bank_account_number' => ['type' => 'string', 'maxLength' => 50],
                'ifsc_code' => ['type' => 'string', 'maxLength' => 20],
                'working_hours' => ['type' => 'string'],
                'weekly_off' => ['type' => 'string'],
                'overtime_allowed' => ['type' => 'string'],
                'id_proof_type' => ['type' => 'string'],
                'id_proof_number' => ['type' => 'string'],
                'username' => ['type' => 'string', 'maxLength' => 50],
                'password' => ['type' => 'string', 'minLength' => 6],
                'role' => ['type' => 'string'],
                'status' => ['type' => 'string']
            ],
            'additionalProperties' => false
        ];
        
        $data = $this->getJsonInput($schema);
        
        try {
            $insertId = $this->staffModel->createStaff($data);
            $staff = $this->staffModel->getStaffById($insertId);
            
            if ($staff) {
                unset($staff['password']);
            }
            
            $this->respondCreated($staff, 'Staff registered successfully');
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * PUT /api/staff/{id}
     * Update staff
     */
    public function update($id) {
        $this->restrictMethod('PUT');
        
        $schema = [
            'properties' => [
                'role_id' => ['type' => 'integer'],
                'designation' => ['type' => 'string'],
                'first_name' => ['type' => 'string'],
                'last_name' => ['type' => 'string'],
                'full_name' => ['type' => 'string'],
                'gender' => ['type' => 'string', 'enum' => ['Male', 'Female', 'Other']],
                'date_of_birth' => ['type' => 'string'],
                'age' => ['type' => 'integer'],
                'blood_group' => ['type' => 'string'],
                'marital_status' => ['type' => 'string'],
                'mobile_number' => ['type' => 'string'],
                'alternate_mobile' => ['type' => 'string'],
                'email' => ['type' => 'string', 'format' => 'email'],
                'address' => ['type' => 'string'],
                'city' => ['type' => 'string'],
                'state' => ['type' => 'string'],
                'country' => ['type' => 'string'],
                'pincode' => ['type' => 'string'],
                'qualification' => ['type' => 'string'],
                'experience_years' => ['type' => 'integer'],
                'previous_organization' => ['type' => 'string'],
                'employment_type' => ['type' => 'string'],
                'joining_date' => ['type' => 'string'],
                'shift_type' => ['type' => 'string'],
                'salary' => ['type' => 'number'],
                'bank_name' => ['type' => 'string'],
                'bank_account_number' => ['type' => 'string'],
                'ifsc_code' => ['type' => 'string'],
                'working_hours' => ['type' => 'string'],
                'weekly_off' => ['type' => 'string'],
                'overtime_allowed' => ['type' => 'string'],
                'id_proof_type' => ['type' => 'string'],
                'id_proof_number' => ['type' => 'string'],
                'username' => ['type' => 'string'],
                'password' => ['type' => 'string'],
                'role' => ['type' => 'string'],
                'status' => ['type' => 'string']
            ],
            'additionalProperties' => false
        ];
        
        $data = $this->getJsonInput($schema);
        
        try {
            $this->staffModel->updateStaff($id, $data);
            $staff = $this->staffModel->getStaffById($id);
            
            if ($staff) {
                unset($staff['password']);
            }
            
            $this->respondSuccess($staff, 'Staff updated successfully');
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Update staff profile (including photo upload)
     * POST /api/staff/{id}/update-profile
     */
    public function updateProfile($id)
    {
        error_log("[DEBUG] StaffController::updateProfile hit for ID: $id");
        $this->restrictMethod('POST');
        $this->requireAuth();

        try {
            // Verify the logged-in staff is updating their own profile
            if ((string)$_SESSION['user_id'] !== (string)$id) {
                error_log("[DEBUG] Unauthorized: Session User ID (" . $_SESSION['user_id'] . ") !== Target ID ($id)");
                $this->respondError('Unauthorized: You can only update your own profile', 403);
                return;
            }

            $updateData = [];

            // Handle photo upload
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                error_log("[DEBUG] Photo upload detected");
                $file = $_FILES['photo'];

                // Validate file type
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                finfo_close($finfo);

                if (!in_array($mimeType, $allowedMimeTypes)) {
                    error_log("[DEBUG] Invalid mime type: $mimeType");
                    $this->respondError('Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.', 400);
                    return;
                }

                // Validate file size (max 5MB)
                if ($file['size'] > 5 * 1024 * 1024) {
                    error_log("[DEBUG] File too large: " . $file['size']);
                    $this->respondError('File too large. Maximum size is 5MB.', 400);
                    return;
                }

                // Create upload directory if it doesn't exist
                $uploadDir = dirname(__DIR__, 2) . '/assets/profile_photos/';
                if (!file_exists($uploadDir)) {
                    if (!mkdir($uploadDir, 0755, true)) {
                        error_log("[DEBUG] Failed to create directory: $uploadDir");
                        $this->respondError('Failed to create upload directory', 500);
                        return;
                    }
                }

                // Generate unique filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'staff_' . $id . '_' . time() . '.' . $extension;
                $uploadPath = $uploadDir . $filename;

                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    error_log("[DEBUG] File uploaded successfully to $uploadPath");
                    // Delete old photo if exists
                    $oldPhotoRow = $this->staffModel->getStaffById($id);
                    if ($oldPhotoRow && !empty($oldPhotoRow['photo'])) {
                        $oldPhotoRel = str_replace('/GM_HMS/', '', $oldPhotoRow['photo']);
                        $oldPhotoPath = dirname(__DIR__, 2) . '/' . $oldPhotoRel;
                        if (file_exists($oldPhotoPath)) {
                            unlink($oldPhotoPath);
                        }
                    }

                    // Store relative path in database
                    $updateData['photo'] = '/GM_HMS/assets/profile_photos/' . $filename;
                } else {
                    error_log("[DEBUG] Failed to move uploaded file");
                    $this->respondError('Failed to save uploaded file', 500);
                    return;
                }
            }

            // Handle other profile updates
            $allowedFields = ['full_name', 'email', 'mobile_number', 'gender'];
            foreach ($allowedFields as $field) {
                if (isset($_POST[$field]) && !empty(trim($_POST[$field]))) {
                    $updateData[$field] = trim($_POST[$field]);
                }
            }

            // Update database if there's data to update
            if (!empty($updateData)) {
                error_log("[DEBUG] Updating database for staff $id with " . json_encode($updateData));
                $this->staffModel->updateStaff($id, $updateData);

                // Update session
                if (isset($updateData['full_name'])) {
                    $_SESSION['full_name'] = $updateData['full_name'];
                }
                if (isset($updateData['email'])) {
                    $_SESSION['email'] = $updateData['email'];
                }
                if (isset($updateData['mobile_number'])) {
                    $_SESSION['mobile_number'] = $updateData['mobile_number'];
                }
                if (isset($updateData['photo'])) {
                    $_SESSION['photo'] = $updateData['photo'];
                }

                // Get updated staff data
                $staff = $this->staffModel->getStaffById($id);
                unset($staff['password']);

                $this->respondSuccess($staff, 'Profile updated successfully');
            } else {
                error_log("[DEBUG] No data to update for staff $id");
                $this->respondError('No data to update', 400);
            }

        } catch (Exception $e) {
            error_log("[DEBUG] Exception in updateProfile: " . $e->getMessage());
            $this->handleException($e);
        }
    }
}
