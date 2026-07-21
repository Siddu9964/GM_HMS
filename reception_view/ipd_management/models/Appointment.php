<?php
/**
 * Appointment Model for IPD
 * 
 * Provides search access to appointment data for patient selection
 * 
 * @package IPD_Management\Models
 */

require_once __DIR__ . '/../core/BaseModel.php';

class Appointment extends BaseModel
{
    protected $table = 'appointments';
    protected $primaryKey = 'appointment_id';

    /**
     * Search appointments by patient name or phone
     */
    public function searchAppointments($keyword, $limit = 10)
    {
        $query = "SELECT 
            a.patient_name,
            a.phone,
            a.doctor_name,
            a.patient_id,
            a.doctor_id
        FROM appointments a
        WHERE (a.patient_name LIKE ? OR a.phone LIKE ?)
        GROUP BY a.patient_name, a.phone, a.doctor_name, a.patient_id, a.doctor_id
        ORDER BY MAX(a.appointment_id) DESC
        LIMIT ?";

        $searchTerm = "%{$keyword}%";
        return $this->fetchAll($query, [$searchTerm, $searchTerm, (int) $limit]);
    }
}
