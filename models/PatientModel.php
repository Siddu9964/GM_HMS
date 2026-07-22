<?php
namespace GM_HMS\Models;

use Exception;
use DateTime;
use GM_HMS\Database\SecureDatabase;

class PatientModel
{
    private $db;

    public function __construct()
    {
        $this->db = SecureDatabase::getInstance();
    }

    /**
     * Get all patients with pagination and filters
     * 
     * @param int $page Page number
     * @param int $limit Records per page
     * @param array $filters Filter criteria
     * @return array List of patients
     */
    public function getAllPatients($page = 1, $limit = 10, $filters = [])
    {
        $offset = ($page - 1) * $limit;

        $sql = "SELECT DISTINCT p.*, 
                (SELECT appointment_status FROM appointments WHERE patient_id COLLATE utf8mb4_general_ci = p.patient_id COLLATE utf8mb4_general_ci ORDER BY appointment_date DESC, appointment_time DESC LIMIT 1) as latest_appointment_status,
                (SELECT status FROM consultations WHERE patient_id COLLATE utf8mb4_general_ci = p.patient_id COLLATE utf8mb4_general_ci ORDER BY consultation_date DESC, consultation_time DESC LIMIT 1) as latest_consultation_status,
                (SELECT d.full_name FROM appointments a JOIN doctors d ON a.doctor_id = d.doctor_id WHERE a.patient_id COLLATE utf8mb4_general_ci = p.patient_id COLLATE utf8mb4_general_ci ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 1) as doctor_name,
                (SELECT a.doctor_id FROM appointments a WHERE a.patient_id COLLATE utf8mb4_general_ci = p.patient_id COLLATE utf8mb4_general_ci ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 1) as doctor_id
                FROM patient p";

        // Join with appointments if filtering by doctor
        if (!empty($filters['doctor_id'])) {
            $sql .= " INNER JOIN appointments a ON p.patient_id COLLATE utf8mb4_general_ci = a.patient_id COLLATE utf8mb4_general_ci";
        }

        $sql .= " WHERE 1=1";
        $params = [];

        // Apply filters
        if (!empty($filters['doctor_id'])) {
            $sql .= " AND a.doctor_id = ?";
            $params[] = $filters['doctor_id'];
        }

        if (!empty($filters['gender'])) {
            $sql .= " AND p.sex = ?";
            $params[] = $filters['gender'];
        }

        // By default, exclude soft-deleted (Inactive) patients.
        // Only show Inactive records when the status filter is explicitly set to 'Inactive'.
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'Active') {
                $sql .= " AND (p.status = 'Active' OR p.status IS NULL OR p.status = '')";
            } else {
                $sql .= " AND p.status = ?";
                $params[] = $filters['status'];
            }
        } else {
            // Default: hide soft-deleted patients so they never reappear after deletion
            $sql .= " AND (p.status IS NULL OR p.status = '' OR p.status != 'Inactive')";
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (p.first_name LIKE ? OR p.last_name LIKE ? OR p.patient_id LIKE ? OR p.aadhar LIKE ? OR p.phone LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Advanced Filters
        if (!empty($filters['city'])) {
            $sql .= " AND p.city LIKE ?";
            $params[] = "%" . $filters['city'] . "%";
        }

        if (!empty($filters['phone'])) {
            $sql .= " AND p.phone LIKE ?";
            $params[] = "%" . $filters['phone'] . "%";
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND p.date >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND p.date <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= " ORDER BY p.sl_no DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $patients = $this->db->fetchAll($sql, $params);

        $formattedPatients = [];
        foreach ($patients as $row) {
            $formattedPatients[] = $this->formatPatientData($row);
        }

        return [
            'data' => $formattedPatients,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $this->getTotalCount($filters)
            ]
        ];
    }

    /**
     * Get total count of patients
     * 
     * @param array $filters Filter criteria
     * @return int Total count
     */
    public function getTotalCount($filters = [])
    {
        $sql = "SELECT COUNT(DISTINCT p.patient_id) as total FROM patient p";

        // Join with appointments if filtering by doctor
        if (!empty($filters['doctor_id'])) {
            $sql .= " INNER JOIN appointments a ON p.patient_id COLLATE utf8mb4_general_ci = a.patient_id COLLATE utf8mb4_general_ci";
        }

        $sql .= " WHERE 1=1";
        $params = [];

        if (!empty($filters['doctor_id'])) {
            $sql .= " AND a.doctor_id = ?";
            $params[] = $filters['doctor_id'];
        }

        if (!empty($filters['gender'])) {
            $sql .= " AND p.sex = ?";
            $params[] = $filters['gender'];
        }

        // By default, exclude soft-deleted (Inactive) patients.
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        } else {
            $sql .= " AND (p.status IS NULL OR p.status = '' OR p.status != 'Inactive')";
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (p.first_name LIKE ? OR p.last_name LIKE ? OR p.patient_id LIKE ? OR p.aadhar LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Advanced Filters
        if (!empty($filters['city'])) {
            $sql .= " AND p.city LIKE ?";
            $params[] = "%" . $filters['city'] . "%";
        }

        if (!empty($filters['phone'])) {
            $sql .= " AND p.phone LIKE ?";
            $params[] = "%" . $filters['phone'] . "%";
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND p.date >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND p.date <= ?";
            $params[] = $filters['date_to'];
        }

        $result = $this->db->fetchOne($sql, $params);
        return (int) $result['total'];
    }

    /**
     * Get single patient by ID
     * 
     * @param string $patientId Patient ID
     * @return array|null Patient data or null if not found
     */
    public function getPatientById($patientId)
    {
        $sql = "SELECT * FROM patient WHERE patient_id = ?";

        $row = $this->db->fetchOne($sql, [$patientId]);

        if (!$row) {
            return null;
        }

        return $this->formatPatientData($row);
    }

    /**
     * Search patients by query
     * 
     * @param string $query Search query
     * @return array List of matching patients
     */
    public function searchPatients($query)
    {
        $sql = "SELECT p.*,
                (SELECT d.full_name FROM appointments a JOIN doctors d ON a.doctor_id = d.doctor_id WHERE a.patient_id COLLATE utf8mb4_general_ci = p.patient_id COLLATE utf8mb4_general_ci ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 1) as doctor_name,
                (SELECT a.doctor_id FROM appointments a WHERE a.patient_id COLLATE utf8mb4_general_ci = p.patient_id COLLATE utf8mb4_general_ci ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 1) as doctor_id
                FROM patient p 
                WHERE p.first_name LIKE ? 
                   OR p.last_name LIKE ? 
                   OR p.patient_id LIKE ? 
                   OR p.aadhar LIKE ? 
                   OR p.phone LIKE ?
                LIMIT 20";

        $searchTerm = '%' . $query . '%';
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];

        $patients = $this->db->fetchAll($sql, $params);

        $formattedPatients = [];
        foreach ($patients as $row) {
            $formattedPatients[] = $this->formatPatientData($row);
        }

        return $formattedPatients;
    }

    /**
     * Create new patient
     * 
     * @param array $data Patient data
     * @return string New patient ID
     */
    public function createPatient($data)
    {
        // Generate patient ID
        $patientId = $this->generatePatientId();

        // Calculate age from birth date, otherwise use explicit age
        $age = !empty($data['birth_date']) ? $this->calculateAge($data['birth_date']) : ($data['age'] ?? null);

        $sql = "INSERT INTO patient (
                    `patient_id`, `title`, `first_name`, `last_name`, `sex`, `aadhar`, `phone`, `email`, `password`,
                    `birth_date`, `age`, `blood_group`, `occupation`, `vaccine_status`,
                    `address`, `country`, `state`, `district`, `city`, `area`, `pincode`,
                    `date`, `time`, `account_opening_timestamp`, `referral_type`, `referral_name`
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = [
            $patientId,
            $data['title'] ?? null,
            $data['first_name'] ?? null,
            $data['last_name'] ?? null,
            $data['sex'] ?? null,
            $data['aadhar'] ?? null,
            $data['phone'] ?? '',
            $data['email'] ?? null,
            $data['password'] ?? ($data['phone'] ?? '123456'),
            $data['birth_date'] ?? null,
            $age,
            $data['blood_group'] ?? '',
            $data['occupation'] ?? null,
            $data['vaccine_status'] ?? null,
            $data['address'] ?? '',
            $data['country'] ?? null,
            $data['state'] ?? null,
            $data['district'] ?? null,
            $data['city'] ?? null,
            $data['area'] ?? null,
            $data['pincode'] ?? null,
            date('Y-m-d'),
            date('H:i:s') . '.000000',
            time(),
            $data['referral_type'] ?? null,
            $data['referral_name'] ?? null
        ];

        $this->db->execute($sql, $params);

        return $patientId;
    }

    /**
     * Update existing patient
     * 
     * @param string $patientId Patient ID
     * @param array $data Updated data
     * @return bool Success status
     */
    public function updatePatient($patientId, $data)
    {
        // Build dynamic UPDATE query based on provided fields
        $fields = [];
        $params = [];

        $allowedFields = [
            'title',
            'first_name',
            'last_name',
            'sex',
            'aadhar',
            'phone',
            'email',
            'password',
            'birth_date',
            'age',
            'blood_group',
            'occupation',
            'vaccine_status',
            'address',
            'country',
            'state',
            'district',
            'city',
            'area',
            'pincode'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "`$field` = ?";
                $params[] = $data[$field];
            }
        }

        // Recalculate age if birth_date is updated
        if (isset($data['birth_date'])) {
            $fields[] = "`age` = ?";
            $params[] = $this->calculateAge($data['birth_date']);
        }

        if (empty($fields)) {
            throw new Exception('No fields to update');
        }

        $params[] = $patientId;

        $sql = "UPDATE patient SET " . implode(', ', $fields) . " WHERE patient_id = ?";

        $this->db->execute($sql, $params);

        return true;
    }

    /**
     * Delete patient (permanent hard delete)
     * 
     * @param string $patientId Patient ID
     * @return bool Success status
     */
    public function deletePatient($patientId)
    {
        $sql = "DELETE FROM patient WHERE patient_id = ?";

        $this->db->execute($sql, [$patientId]);

        return true;
    }

    /**
     * Calculate age from birth date
     * 
     * @param string $birthDate Birth date (YYYY-MM-DD)
     * @return int Age in years
     */
    private function calculateAge($birthDate)
    {
        if (empty($birthDate)) {
            return null;
        }

        $birthDateTime = new DateTime($birthDate);
        $today = new DateTime();
        $age = $today->diff($birthDateTime)->y;

        return $age;
    }

    /**
     * Format patient data for API response
     * 
     * @param array $row Database row
     * @return array Formatted patient data
     */
    private function formatPatientData($row)
    {
        return [
            'sl_no' => $row['sl_no'] ?? null,
            'patient_id' => $row['patient_id'],
            'title' => $row['title'] ?? null,
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'full_name' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
            'sex' => $row['sex'] ?? null,
            'aadhar' => $row['aadhar'] ?? null,
            'phone' => $row['phone'] ?? null,
            'email' => $row['email'] ?? null,
            'birth_date' => $row['birth_date'] ?? null,
            'age' => $row['age'] ?? null,
            'blood_group' => $row['blood_group'] ?? null,
            'occupation' => $row['occupation'] ?? null,
            'vaccine_status' => $row['vaccine_status'] ?? null,
            'address' => $row['address'] ?? null,
            'country' => $row['country'] ?? null,
            'state' => $row['state'] ?? null,
            'district' => $row['district'] ?? null,
            'city' => $row['city'] ?? null,
            'area' => $row['area'] ?? null,
            'pincode' => $row['pincode'] ?? null,
            'date' => $row['date'] ?? null,
            'time' => $row['time'] ?? null,
            'account_opening_timestamp' => $row['account_opening_timestamp'] ?? null,
            'status' => $row['status'] ?? 'Active',
            'image' => $row['image'] ?? null,
            'latest_appointment_status' => $row['latest_appointment_status'] ?? null,
            'latest_consultation_status' => $row['latest_consultation_status'] ?? null,
            'doctor_name' => $row['doctor_name'] ?? null,
            'doctor_id' => $row['doctor_id'] ?? null
        ];
    }

    /**
     * Generate unique patient ID
     * 
     * @return string Patient ID
     */
    private function generatePatientId()
    {
        $prefix = 'PID';
        $date = date('Ymd');

        // Get last patient ID for today
        $sql = "SELECT patient_id FROM patient 
                WHERE patient_id LIKE ? 
                ORDER BY patient_id DESC LIMIT 1";

        $row = $this->db->fetchOne($sql, [$prefix . '-' . $date . '%']);

        if ($row) {
            $lastId = $row['patient_id'];
            $number = intval(substr($lastId, -3)) + 1;
        } else {
            $number = 1;
        }

        return $prefix . '-' . $date . '-' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }
}
