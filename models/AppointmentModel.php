<?php
/**
 * Appointment Model
 * Handles all appointment-related database operations
 * 
 * @package GM_HMS\Models
 * @version 2.0.0
 */

namespace GM_HMS\Models;

use GM_HMS\Database\SecureDatabase;
use GM_HMS\Models\OpdBillingModel;
use Exception;

class AppointmentModel
{
    private $db;

    public function __construct()
    {
        $this->db = SecureDatabase::getInstance();
    }

    /**
     * Get all appointments with optional filters
     * 
     * @param array $filters Filter criteria (status, doctor_id, date, type, date_from, date_to, limit)
     * @return array List of appointments
     */
    public function getAllAppointments($filters = [])
    {
        // LEFT JOIN a subquery to get the latest billing info for patients without appointments
        $sql = "SELECT COALESCE(a.appointment_id, CONCAT('NOAPT-', p.patient_id)) as appointment_id,
                       COALESCE(a.appointment_date, obm.last_date) as appointment_date,
                       COALESCE(a.appointment_time, obm.last_time) as appointment_time,
                       a.reason,
                       a.appointment_type,
                       a.remarks,
                       a.consultation_fee,
                       a.discount,
                       a.total_amount,
                       a.payment_mode,
                       a.token_number,
                       CASE 
                           WHEN COALESCE(a.appointment_status, obm.last_status) = 'Cancelled' THEN 'Cancelled'
                           WHEN (d.in_time IS NULL OR d.out_time IS NULL OR d.in_time = '' OR d.out_time = '' OR d.in_time = '00:00:00' OR d.out_time = '00:00:00') THEN 'Doctor On Leave'
                           ELSE COALESCE(a.appointment_status, obm.last_status)
                       END as appointment_status,
                       a.payment_status,
                       COALESCE(a.patient_id, p.patient_id) as patient_id,
                       a.phone as appointment_phone, 
                       p.phone as patient_phone, 
                       a.patient_id as p_patient_id, 
                       d.department_id,
                       TRIM(CONCAT(p.first_name, ' ', IFNULL(p.last_name, ''))) as patient_name,
                       COALESCE(d.full_name, obm.last_doctor) as doctor_name,
                       d.specialization
                FROM appointments a 
                RIGHT JOIN patient p ON a.patient_id COLLATE utf8mb4_unicode_ci = p.patient_id 
                LEFT JOIN doctors d ON a.doctor_id COLLATE utf8mb4_unicode_ci = d.doctor_id
                LEFT JOIN (
                    SELECT obm1.patient_id, obm1.doctor_name as last_doctor, 
                           obm1.status as last_status, obm1.bill_date as last_date, 
                           obm1.bill_time as last_time
                    FROM opd_billing_master obm1
                    JOIN (
                        SELECT patient_id, MAX(bill_id) as max_bill_id 
                        FROM opd_billing_master 
                        GROUP BY patient_id
                    ) obm2 ON obm1.bill_id = obm2.max_bill_id
                ) obm ON p.patient_id = obm.patient_id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND a.appointment_status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['doctor_id'])) {
            $sql .= " AND a.doctor_id = ?";
            $params[] = $filters['doctor_id'];
        }
        if (!empty($filters['date'])) {
            $sql .= " AND a.appointment_date = ?";
            $params[] = $filters['date'];
        }
        if (!empty($filters['type'])) {
            $sql .= " AND a.appointment_type = ?";
            $params[] = $filters['type'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND a.appointment_date >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND a.appointment_date <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (a.patient_name LIKE ? OR a.phone LIKE ? OR a.patient_id LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($filters['patient_id'])) {
            $sql .= " AND COALESCE(a.patient_id, p.patient_id) = ?";
            $params[] = $filters['patient_id'];
        }

        $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time ASC";
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int) $filters['limit'];
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get single appointment by ID
     * 
     * @param string $appointmentId Appointment ID
     * @return array|null Appointment data
     */
    public function getAppointmentById($appointmentId)
    {
        return $this->db->fetchOne(
            "SELECT a.*, d.department_id 
             FROM appointments a 
             LEFT JOIN doctors d ON a.doctor_id COLLATE utf8mb4_unicode_ci = d.doctor_id 
             WHERE a.appointment_id = ?",
            [$appointmentId]
        );
    }

    /**
     * Create new appointment with automatic billing
     * 
     * @param array $data Appointment data
     * @return string New appointment ID
     * @throws Exception If creation fails
     */
    public function createAppointment($data)
    {
        try {
            $this->db->beginTransaction();

            $appointmentId = $this->generateAppointmentId();

            $patient = $this->db->fetchOne(
                "SELECT first_name, last_name, phone FROM patient WHERE patient_id = ?",
                [$data['patient_id']]
            );

            // Auto-register patient if they don't exist (e.g. if the app sent a name instead of an ID)
            if (!$patient && !preg_match('/^PID-/', $data['patient_id'])) {
                $patientModel = new PatientModel();
                
                $nameStr = !empty($data['patient_name']) ? $data['patient_name'] : $data['patient_id'];
                $parts = explode(' ', trim($nameStr));
                $firstName = $parts[0];
                $lastName = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';
                
                $newPid = $patientModel->createPatient([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => !empty($data['phone']) ? $data['phone'] : '0000000000',
                    'email' => $data['email'] ?? '',
                    'password' => password_hash('Patient@1234', PASSWORD_DEFAULT)
                ]);
                
                if ($newPid) {
                    $data['patient_id'] = $newPid; // Overwrite with the generated true ID
                    $patient = [
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'phone' => !empty($data['phone']) ? $data['phone'] : '0000000000'
                    ];
                }
            }

            $patientName = !empty($data['patient_name']) 
                ? $data['patient_name'] 
                : ($patient ? ($patient['first_name'] . ' ' . $patient['last_name']) : 'Unknown');

            // Prevent multiple appointments for the same patient on the same day
            $existing = $this->db->fetchOne(
                "SELECT appointment_id FROM appointments WHERE patient_id = ? AND appointment_date = ? AND appointment_status != 'Cancelled'",
                [$data['patient_id'], $data['appointment_date']]
            );
            
            if ($existing) {
                throw new Exception("Patient already has an appointment scheduled on this date.");
            }

            $doctor = $this->db->fetchOne(
                "SELECT full_name, specialization FROM doctors WHERE doctor_id = ?",
                [$data['doctor_id']]
            );
            $doctorName = $doctor['full_name'] ?? 'Unknown';
            $specialization = $doctor['specialization'] ?? 'General';

            $tokenNumber = ($data['appointment_type'] ?? 'OPD') === 'OPD'
                ? $this->generateTokenNumber($data['appointment_date'])
                : null;

            // Use submitted phone if non-empty, otherwise fall back to the patient's phone from DB.
            // Cannot use ?? here because the hidden field sends '' (not null) when blank.
            $phone = (!empty($data['phone'])) ? $data['phone'] : ($patient['phone'] ?? '');

            $sql = "INSERT INTO appointments (
                        appointment_id, patient_id, patient_name, phone, email, doctor_id, doctor_name, specialization,
                        appointment_date, appointment_time, reason, appointment_status, remarks, token_number,
                        consultation_fee, discount, total_amount, appointment_type, payment_status, payment_mode
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $this->db->execute($sql, [
                $appointmentId,
                $data['patient_id'],
                $patientName,
                $phone,
                $data['email'] ?? '',
                $data['doctor_id'],
                $doctorName,
                $specialization,
                $data['appointment_date'],
                $data['appointment_time'],
                $data['reason'] ?? 'Consultation',
                1,
                $data['notes'] ?? '',
                $tokenNumber,
                floatval($data['consultation_fee'] ?? 0),
                floatval($data['discount'] ?? 0),
                floatval($data['total_amount'] ?? 0),
                $data['appointment_type'] ?? 'OPD',
                $data['payment_status'] ?? 'Pending',
                $data['payment_mode'] ?? 'Cash'
            ]);

            // Create billing if amount > 0
            if (($data['total_amount'] ?? 0) > 0) {
                try {
                    $billingModel = new OpdBillingModel();
                    $billId = $billingModel->createBill([
                        'patient_id' => $data['patient_id'],
                        'doctor_id' => $data['doctor_id'],
                        'appointment_id' => $appointmentId,
                        'created_by' => 'system_apt'
                    ], [
                        [
                            'item_type' => 'Consultation',
                            'item_name' => 'Consultation Fee',
                            'unit_price' => floatval($data['consultation_fee'] ?? $data['total_amount']),
                            'quantity' => 1,
                            'is_taxable' => true,
                            'tax_percentage' => 0,
                            'discount_amount' => floatval($data['discount'] ?? 0)
                        ]
                    ]);

                    if (($data['payment_status'] ?? 'Pending') === 'Paid') {
                        $billingModel->recordPayment($billId, [
                            'amount' => floatval($data['total_amount']),
                            'payment_mode' => $data['payment_mode'] ?? 'Cash',
                            'notes' => 'Paid at registration'
                        ]);
                    }
                } catch (\Exception $e) {
                    error_log("Billing warning during appointment creation: " . $e->getMessage());
             }
            }

            $this->db->commit();
            return $appointmentId;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function updateAppointment($id, $data)
    {
        $allowed = ['appointment_date', 'appointment_time', 'reason', 'appointment_status', 'remarks', 'payment_status', 'doctor_id', 'phone', 'email'];
        $fields = [];
        $params = [];

        // If phone is blank or missing, auto-fetch it from the patient table
        if (empty($data['phone'])) {
            $apt = $this->db->fetchOne("SELECT patient_id FROM appointments WHERE appointment_id = ?", [$id]);
            if ($apt) {
                $patient = $this->db->fetchOne("SELECT phone FROM patient WHERE patient_id = ?", [$apt['patient_id']]);
                if ($patient && !empty($patient['phone'])) {
                    $data['phone'] = $patient['phone'];
                }
            }
        }

        // Handle doctor denormalization if doctor_id is changing
        if (!empty($data['doctor_id'])) {
            $doctor = $this->db->fetchOne("SELECT full_name, specialization FROM doctors WHERE doctor_id = ?", [$data['doctor_id']]);
            if ($doctor) {
                $data['doctor_name'] = $doctor['full_name'];
                $data['specialization'] = $doctor['specialization'];
                $allowed[] = 'doctor_name';
                $allowed[] = 'specialization';
            }
        }

        foreach ($data as $k => $v) {
            if (in_array($k, $allowed)) {
                $fields[] = "`$k` = ?";
                $params[] = $v;
            }
        }

        if (empty($fields))
            return true;

        $params[] = $id;
        return $this->db->execute("UPDATE appointments SET " . implode(', ', $fields) . " WHERE appointment_id = ?", $params);
    }

    public function deleteAppointment($id)
    {
        return $this->db->execute("DELETE FROM appointments WHERE appointment_id = ?", [$id]);
    }

    public function generateAppointmentId()
    {
        $prefix = 'APT';
        $date = date('Ymd');
        $last = $this->db->fetchOne("SELECT appointment_id FROM appointments WHERE appointment_id LIKE ? ORDER BY appointment_id DESC LIMIT 1", ["$prefix-$date%"]);
        $num = $last ? (intval(substr($last['appointment_id'], -4)) + 1) : 1;
        return sprintf("%s-%s-%04d", $prefix, $date, $num);
    }

    public function generateTokenNumber($date)
    {
        $res = $this->db->fetchOne("SELECT MAX(token_number) as max_t FROM appointments WHERE appointment_date = ? AND appointment_type = 'OPD'", [$date]);
        return ($res['max_t'] ?? 0) + 1;
    }

    public function getStatistics()
    {
        $today = date('Y-m-d');
        return [
            'today_appointments' => $this->db->fetchOne("SELECT COUNT(*) as c FROM appointments WHERE appointment_date = ?", [$today])['c'],
            'upcoming_appointments' => $this->db->fetchOne("SELECT COUNT(*) as c FROM appointments WHERE appointment_date > ?", [$today])['c'],
            'completed_appointments' => $this->db->fetchOne("SELECT COUNT(*) as c FROM appointments WHERE appointment_status = 0")['c'],
            'pending_appointments' => $this->db->fetchOne("SELECT COUNT(*) as c FROM appointments WHERE appointment_status = 1")['c']
        ];
    }

    /**
     * Get total appointments count for today
     *
     * Used by Admin dashboard summary.
     */
    public function getTodayCount()
    {
        try {
            $today = date('Y-m-d');
            $row = $this->db->fetchOne(
                "SELECT COUNT(*) as cnt FROM appointments WHERE appointment_date = ?",
                [$today]
            );
            return (int) ($row['cnt'] ?? 0);
        } catch (\Throwable $e) {
            error_log("AppointmentModel::getTodayCount Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get all active departments
     */
    public function getAllDepartments()
    {
        $branch = $_SERVER['HTTP_X_HOSPITAL_BRANCH'] ?? $_SESSION['hospital_branch'] ?? $_SESSION['branch'] ?? '';
        if (strtolower($branch) === 'basaveshwaranagar') {
            return $this->db->fetchAll("SELECT DISTINCT specialization as department_id, specialization as department_name FROM doctors WHERE specialization IS NOT NULL AND specialization != '' AND (status = 'Active' OR status = '' OR status IS NULL) ORDER BY specialization ASC");
        }
        return $this->db->fetchAll("SELECT department_id, department_name FROM departments WHERE (status = 'Active' OR status = '' OR status IS NULL) ORDER BY department_name ASC");
    }

    /**
     * Get active doctors by department
     */
    public function getDoctorsByDepartment($deptId)
    {
        $branch = $_SERVER['HTTP_X_HOSPITAL_BRANCH'] ?? $_SESSION['hospital_branch'] ?? $_SESSION['branch'] ?? '';
        if (strtolower($branch) === 'basaveshwaranagar') {
            return $this->db->fetchAll("SELECT doctor_id, full_name, specialization, available_days, in_time, out_time, consultation_fee FROM doctors WHERE specialization = ? AND (status = 'Active' OR status = '' OR status IS NULL) ORDER BY full_name ASC", [$deptId]);
        }
        return $this->db->fetchAll("SELECT doctor_id, full_name, specialization, available_days, in_time, out_time, consultation_fee FROM doctors WHERE department_id = ? AND (status = 'Active' OR status = '' OR status IS NULL) ORDER BY full_name ASC", [$deptId]);
    }

    /**
     * Check if a doctor is available at a specific date and time
     */
    public function checkAvailability($doctorId, $date, $time)
    {
        $sql = "SELECT COUNT(*) as count FROM appointments 
                WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? 
                AND appointment_status NOT IN ('Cancelled', 'Rescheduled')";
        $res = $this->db->fetchOne($sql, [$doctorId, $date, $time]);
        return ($res['count'] ?? 0) == 0;
    }

    /**
     * Get all booked times for a doctor on a specific date
     */
    public function getBookedTimes($doctorId, $date)
    {
        $sql = "SELECT appointment_time FROM appointments 
                WHERE doctor_id = ? AND appointment_date = ? 
                AND appointment_status NOT IN ('Cancelled', 'Rescheduled')";
        $res = $this->db->fetchAll($sql, [$doctorId, $date]);
        return array_column($res, 'appointment_time');
    }
}
