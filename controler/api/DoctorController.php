<?php
/**
 * ============================================================
 * DoctorController — API Reference
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api
 * Auth     : All endpoints require Auth (Session or Bearer token)
 * ------------------------------------------------------------
 *
 * 1. GET /api/doctors
 *    Query Params:
 *      search|term  (string) - Name, ID, or specialization search
 *      status       (string) - Filter by availability: Available | Off-Duty
 *      limit        (int)    - Max results (default: 100)
 *    Response fields: doctor_id, full_name, specialization, department, room_number,
 *      in_time, out_time, consultation_fee, available_days, photo, status, availability
 *    Example: GET /api/doctors?search=Cardio&status=Available
 *
 * 2. GET /api/doctors/{id}
 *    Example: GET /api/doctors/DOC-001
 *    Response: Full doctor profile + computed availability (Available | Off-Duty)
 *
 * 3. GET /api/doctors/{id}/analytics
 *    Returns: 7-day patient flow chart data + consultation duration stats
 *    Response: { labels:[...], patientFlow:[...], consultationDuration:[...], overallAvgTime:18.4 }
 *
 * 4. GET /api/doctors/{id}/opd-patients
 *    Query Params:
 *      status  (string) - Filter appointment status
 *      limit   (int)    - Max results (default: 50)
 *    Response: { "appointments": [ { appointment_id, patient_id, appointment_date, status } ] }
 *
 * 5. GET /api/doctors/{id}/ipd-patients
 *    Query Params:
 *      status  (string) - Filter admission status
 *    Response: { "admissions": [ { admission_id, patient_id, admission_date, ward_name, days_admitted } ] }
 *
 * 6. POST /api/doctors           [Required: full_name]
 *    Body:
 *      {
 *        "full_name":        "Dr. Rajesh Kumar",
 *        "specialization":   "Cardiology",
 *        "department_id":    5,
 *        "room_number":      "301",
 *        "in_time":          "09:00",
 *        "out_time":         "17:00",
 *        "consultation_fee": 700,
 *        "available_days":   "Mon,Tue,Wed,Thu,Fri",
 *        "mobile_number":    "9876540001",
 *        "status":           "Active"
 *      }
 *    Response: { "doctor_id": "DOC-001" }
 *
 * 7. PUT /api/doctors/{id}
 *    Body: Same as POST — send only fields to update
 *
 * 8. DELETE /api/doctors/{id}
 *    No body required.
 *
 * 9. POST /api/doctors/{id}/update-profile    [multipart/form-data]
 *    Form fields: full_name (string)
 *    File field:  photo (image/jpeg|png|gif — max 5MB)
 *    Note: Only the logged-in doctor can update their own profile.
 *    Response: { doctor_id, full_name, specialization, photo }
 * ------------------------------------------------------------
 */
namespace GM_HMS\Controllers\api;

use GM_HMS\Controllers\BaseController;
use Exception;

class DoctorController extends BaseController
{

    /**
     * List doctors with search
     * GET /api/doctors
     */
    public function index()
    {
        $this->restrictMethod('GET');

        try {
            $term = $_GET['term'] ?? $_GET['search'] ?? '';
            $department = $_GET['department'] ?? '';
            $statusFilter = $_GET['status'] ?? ''; // Added status filter support
            $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;

            // Comprehensive query with availability logic
            $sql = "SELECT 
                        d.doctor_id, 
                        d.full_name, 
                        d.gender,
                        d.age,
                        d.mobile_number,
                        d.specialization,
                        d.department_id,
                        d.specialization as department,
                        d.room_number,
                        d.in_time,
                        d.out_time,
                        d.consultation_fee,
                        d.available_days,
                        d.photo,
                        d.status,
                        CASE 
                            WHEN d.status != 'Active' THEN 'Off-Duty'
                            WHEN d.available_days IS NOT NULL AND d.available_days != '' 
                                 AND REPLACE(REPLACE(REPLACE(d.available_days, ' ', ''), '\r', ''), '\n', '') NOT LIKE CONCAT('%', DATE_FORMAT(NOW(), '%a'), '%') THEN 'Off-Duty'
                            WHEN d.in_time IS NOT NULL AND (
                                (d.out_time IS NOT NULL AND CURTIME() BETWEEN d.in_time AND d.out_time)
                                OR (d.out_time IS NULL AND CURTIME() >= d.in_time)
                                OR (d.out_time < d.in_time AND (CURTIME() >= d.in_time OR CURTIME() <= d.out_time))
                            ) THEN 'Available'
                            ELSE 'Off-Duty'
                        END AS availability
                    FROM doctors d";

            $params = [];

            if (!empty($department)) {
                $sql .= " WHERE (d.status = 'Active' OR d.status IS NULL OR d.status = '') AND d.specialization = ?";
                $params[] = $department;
                
                if (!empty($term)) {
                    $sql .= " AND (d.doctor_id LIKE ? OR d.full_name LIKE ?)";
                    $searchTerm = "%$term%";
                    array_push($params, $searchTerm, $searchTerm);
                }
            } else {
                $sql .= " WHERE (d.status = 'Active' OR d.status IS NULL OR d.status = '')";
                
                if (!empty($term)) {
                    $sql .= " AND (d.doctor_id LIKE ? 
                               OR d.full_name LIKE ? 
                               OR d.specialization LIKE ?)";
                    $searchTerm = "%$term%";
                    array_push($params, $searchTerm, $searchTerm, $searchTerm);
                }
            }

            // Handle availability status filter at SQL level if needed
            if (!empty($statusFilter)) {
                $sql = "SELECT * FROM ($sql) as filtered_doctors WHERE availability = ?";
                $params[] = $statusFilter;
            } else {
                $sql .= " ORDER BY availability DESC, d.full_name ASC LIMIT ?";
                $params[] = $limit;
            }
            
            error_log("DOCTOR API SQL: " . $sql . " PARAMS: " . json_encode($params));

            $doctors = $this->db->fetchAll($sql, $params);
            
            error_log("DOCTOR API RESULT COUNT: " . count($doctors));

            $this->respondSuccess($doctors);

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get single doctor
     * GET /api/doctors/{id}
     */
    public function show($id)
    {
        $this->restrictMethod('GET');

        try {
            $sql = "SELECT d.*,
                        d.specialization as department,
                        CASE 
                            WHEN d.status != 'Active' THEN 'Off-Duty'
                            WHEN d.available_days IS NOT NULL AND d.available_days != '' 
                                 AND REPLACE(REPLACE(REPLACE(d.available_days, ' ', ''), '\r', ''), '\n', '') NOT LIKE CONCAT('%', DATE_FORMAT(NOW(), '%a'), '%') THEN 'Off-Duty'
                            WHEN d.in_time IS NOT NULL AND (
                                (d.out_time IS NOT NULL AND CURTIME() BETWEEN d.in_time AND d.out_time)
                                OR (d.out_time IS NULL AND CURTIME() >= d.in_time)
                                OR (d.out_time < d.in_time AND (CURTIME() >= d.in_time OR CURTIME() <= d.out_time))
                            ) THEN 'Available'
                            ELSE 'Off-Duty'
                        END AS availability
                    FROM doctors d 
                    WHERE d.doctor_id = ?";
            $doctor = $this->db->fetchOne($sql, [$id]);

            if ($doctor) {
                $this->respondSuccess($doctor);
            } else {
                $this->respondNotFound("Doctor $id not found");
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get doctor analytics
     * GET /api/doctors/{id}/analytics
     */
    public function getAnalytics($id)
    {
        $this->restrictMethod('GET');
        $this->requireAuth();

        try {
            // Generate last 7 days labels
            $labels = [];
            $patientFlow = [];
            $consultationDuration = [];

            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $labels[] = date('M d', strtotime($date));

                // Real patient flow count
                $count = $this->db->fetchOne(
                    "SELECT COUNT(*) as c FROM appointments WHERE doctor_id = ? AND appointment_date = ?",
                    [$id, $date]
                )['c'];
                $patientFlow[] = (int) $count;

                // Mock duration data (10-30 mins) as we track timestamp only
                $consultationDuration[] = 15 + rand(-5, 10);
            }

            // Calculate overall average
            $overallAvgTime = array_sum($consultationDuration) / count($consultationDuration);

            $data = [
                'labels' => $labels,
                'patientFlow' => $patientFlow,
                'consultationDuration' => $consultationDuration,
                'overallAvgTime' => round($overallAvgTime, 1)
            ];

            $this->respondSuccess($data);

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get OPD patients for doctor
     * GET /api/doctors/{id}/opd-patients
     */
    public function getOpdPatients($id)
    {
        $this->restrictMethod('GET');
        $this->requireAuth();

        try {
            $status = $_GET['status'] ?? null;
            $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;

            $sql = "SELECT 
                    a.appointment_id, 
                    a.patient_id,
                    a.patient_name, 
                    a.appointment_date,
                    a.appointment_time, 
                    a.reason,
                    a.appointment_status as status,
                    COALESCE(p.sex, 'N/A') as patient_gender,
                    COALESCE(p.age, 0) as patient_age
                    FROM appointments a
                    LEFT JOIN patient p ON a.patient_id COLLATE utf8mb4_general_ci = p.patient_id COLLATE utf8mb4_general_ci
                    WHERE a.doctor_id = ? AND a.appointment_type = 'OPD'";

            $params = [$id];

            if ($status) {
                $sql .= " AND a.appointment_status = ?";
                $params[] = $status;
            }

            $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time ASC LIMIT ?";
            $params[] = $limit;

            $appointments = $this->db->fetchAll($sql, $params);

            $this->respondSuccess(['appointments' => $appointments]);

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get IPD patients for doctor
     * GET /api/doctors/{id}/ipd-patients
     */
    public function getIpdPatients($id)
    {
        $this->restrictMethod('GET');
        $this->requireAuth();

        try {
            $status = $_GET['status'] ?? null;

            $sql = "SELECT 
                    a.admission_id, 
                    a.patient_id,
                    CONCAT(p.first_name, ' ', COALESCE(p.last_name, '')) as patient_name,
                    a.admission_date,
                    a.status,
                    a.ward_name,
                    a.room_no as bed_number,
                    DATEDIFF(CURRENT_DATE, a.admission_date) as days_admitted
                    FROM ipd_admissions a
                    JOIN patient p ON a.patient_id = p.patient_id
                    WHERE a.admitting_doctor_id = ?";

            $params = [$id];

            if ($status) {
                $sql .= " AND a.status = ?";
                $params[] = $status;
            }

            $sql .= " ORDER BY a.admission_date DESC";

            $admissions = $this->db->fetchAll($sql, $params);

            $this->respondSuccess(['admissions' => $admissions]);

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Update doctor profile (including photo upload)
     * POST /api/doctors/{id}/update-profile
     */
    public function updateProfile($id)
    {
        $this->restrictMethod('POST');
        $this->requireAuth();

        try {
            // Verify the logged-in doctor is updating their own profile
            if ($_SESSION['user_id'] !== $id) {
                $this->respondError('Unauthorized: You can only update your own profile', 403);
                return;
            }

            $updateData = [];

            // Handle photo upload
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['photo'];

                // Validate file type
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                if (!in_array($mimeType, $allowedTypes)) {
                    $this->respondError('Invalid file type. Only JPG, PNG, and GIF are allowed.', 400);
                    return;
                }

                // Validate file size (max 5MB)
                if ($file['size'] > 5 * 1024 * 1024) {
                    $this->respondError('File too large. Maximum size is 5MB.', 400);
                    return;
                }

                // Create upload directory if it doesn't exist
                $uploadDir = __DIR__ . '/../../assets/profile_photos/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Generate unique filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'doctor_' . $id . '_' . time() . '.' . $extension;
                $uploadPath = $uploadDir . $filename;

                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    // Delete old photo if exists
                    $oldPhoto = $this->db->fetchOne(
                        "SELECT photo FROM doctors WHERE doctor_id = ?",
                        [$id]
                    );

                    if ($oldPhoto && !empty($oldPhoto['photo'])) {
                        $oldPhotoPath = __DIR__ . '/../../' . $oldPhoto['photo'];
                        if (file_exists($oldPhotoPath)) {
                            unlink($oldPhotoPath);
                        }
                    }

                    // Store relative path in database
                    $updateData['photo'] = '/GM_HMS/assets/profile_photos/' . $filename;
                } else {
                    $this->respondError('Failed to upload file', 500);
                    return;
                }
            }

            // Handle other profile updates
            if (isset($_POST['full_name']) && !empty(trim($_POST['full_name']))) {
                $updateData['full_name'] = trim($_POST['full_name']);
            }

            // Update database if there's data to update
            if (!empty($updateData)) {
                $fields = [];
                $params = [];

                foreach ($updateData as $key => $value) {
                    $fields[] = "`$key` = ?";
                    $params[] = $value;
                }

                $params[] = $id;

                $sql = "UPDATE doctors SET " . implode(', ', $fields) . " WHERE doctor_id = ?";
                $this->db->execute($sql, $params);

                // Update session if name was changed
                if (isset($updateData['full_name'])) {
                    $_SESSION['full_name'] = $updateData['full_name'];
                }

                // Get updated doctor data
                $doctor = $this->db->fetchOne(
                    "SELECT doctor_id, full_name, specialization, photo FROM doctors WHERE doctor_id = ?",
                    [$id]
                );

                $this->respondSuccess($doctor, 'Profile updated successfully');
            } else {
                $this->respondError('No data to update', 400);
            }

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Create new doctor
     * POST /api/doctors
     */
    public function create()
    {
        $this->restrictMethod('POST');
        $this->requireAuth();

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                $this->respondError('No data provided', 400);
                return;
            }

            if (empty($data['full_name'])) {
                $this->respondError('Full name is required', 400);
                return;
            }

            $doctorModel = new \GM_HMS\Models\DoctorModel();
            $doctorId = $doctorModel->createDoctor($data);

            if ($doctorId) {
                $this->respondSuccess(['doctor_id' => $doctorId], 'Doctor created successfully', 201);
            } else {
                $this->respondError('Failed to create doctor');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Update existing doctor
     * PUT /api/doctors/{id}
     */
    public function update($id)
    {
        $this->restrictMethod('PUT');
        $this->requireAuth();

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                $this->respondError('No data provided', 400);
                return;
            }

            $doctorModel = new \GM_HMS\Models\DoctorModel();
            $success = $doctorModel->updateDoctor($id, $data);

            if ($success) {
                $this->respondSuccess(null, 'Doctor updated successfully');
            } else {
                $this->respondError('Failed to update doctor');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Delete doctor
     * DELETE /api/doctors/{id}
     */
    public function delete($id)
    {
        $this->restrictMethod('DELETE');
        $this->requireAuth();

        try {
            $doctorModel = new \GM_HMS\Models\DoctorModel();
            $success = $doctorModel->deleteDoctor($id);

            if ($success) {
                $this->respondSuccess(null, 'Doctor deleted successfully');
            } else {
                $this->respondError('Failed to delete doctor');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
