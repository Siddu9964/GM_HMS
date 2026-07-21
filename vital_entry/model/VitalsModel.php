<?php
class VitalsModel {
    private $db;

    public function __construct() {
        require_once dirname(__DIR__, 2) . '/core/Autoloader.php';
        require_once dirname(__DIR__, 2) . '/Database/SecureDatabase.php';
        $this->db = \GM_HMS\Database\SecureDatabase::getInstance();
    }

    public function getAppointments() {
        $query = "
            SELECT 
                appointment_id, 
                patient_id, 
                doctor_id, 
                patient_name, 
                appointment_date, 
                appointment_time, 
                appointment_status AS status 
            FROM appointments 
            WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 2 DAY)
            ORDER BY appointment_date DESC, appointment_time DESC
        ";
        return $this->db->fetchAll($query);
    }

    public function getConsultationByAppointment($appointmentId) {
        $query = "
            SELECT 
                patient_id, 
                doctor_id, 
                appointment_id, 
                consultation_date, 
                consultation_time, 
                vital_signs 
            FROM consultations 
            WHERE appointment_id = ?
        ";
        return $this->db->fetchOne($query, [$appointmentId]);
    }

    public function updateConsultationVitals($appointmentId, $vitalsJson) {
        // Only update vital_signs where appointment_id matches
        $data = ['vital_signs' => $vitalsJson];
        return $this->db->update('consultations', $data, 'appointment_id = ?', [$appointmentId]);
    }

    public function getAppointmentById($appointmentId) {
        $query = "SELECT * FROM appointments WHERE appointment_id = ?";
        return $this->db->fetchOne($query, [$appointmentId]);
    }

    public function createConsultation($appointment, $vitalsJson) {
        $consultationId = 'CON-' . date('Ymd') . '-' . rand(1000, 9999);
        $data = [
            'consultation_id' => $consultationId,
            'appointment_id' => $appointment['appointment_id'],
            'patient_id' => $appointment['patient_id'],
            'doctor_id' => $appointment['doctor_id'],
            'consultation_date' => date('Y-m-d'),
            'consultation_time' => date('H:i:s'),
            'vital_signs' => $vitalsJson,
            'status' => 1
        ];
        return $this->db->insert('consultations', $data);
    }
}
