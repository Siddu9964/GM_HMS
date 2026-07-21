<?php
require_once dirname(__DIR__) . '/model/VitalsModel.php';

class VitalsService {
    private $model;

    public function __construct() {
        $this->model = new VitalsModel();
    }

    public function getPatients() {
        $appointments = $this->model->getAppointments();
        return [
            'status' => true,
            'data' => $appointments
        ];
    }

    public function getVitals($appointmentId) {
        if (empty($appointmentId)) {
            return [
                'status' => false,
                'message' => 'Appointment ID is required'
            ];
        }

        $consultation = $this->model->getConsultationByAppointment($appointmentId);

        if ($consultation) {
            $vitals = [];
            if (!empty($consultation['vital_signs'])) {
                $decoded = json_decode($consultation['vital_signs'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $vitals = $decoded;
                }
            }
            return [
                'status' => true,
                'patient' => [
                    'patient_id' => $consultation['patient_id'],
                    'doctor_id' => $consultation['doctor_id'],
                    'appointment_id' => $consultation['appointment_id']
                ],
                'vitals' => $vitals
            ];
        } else {
            return [
                'status' => true,
                'patient' => null,
                'vitals' => [] // Return empty values if no vitals exist or consultation not found yet
            ];
        }
    }

    public function updateVitals($data) {
        $appointmentId = $data['appointment_id'] ?? null;
        
        if (empty($appointmentId)) {
            return [
                'status' => false,
                'message' => 'Appointment ID is required'
            ];
        }

        // Check if consultation exists
        $consultation = $this->model->getConsultationByAppointment($appointmentId);
        
        $vitalsData = [
            'bp' => $data['bp'] ?? '',
            'pulse' => $data['pulse'] ?? '',
            'temperature' => $data['temperature'] ?? '',
            'spo2' => $data['spo2'] ?? '',
            'weight' => $data['weight'] ?? '',
            'height' => $data['height'] ?? ''
        ];
        $vitalsJson = json_encode($vitalsData);

        if (!$consultation) {
            // Fetch appointment details to create consultation
            $appointment = $this->model->getAppointmentById($appointmentId);
            if (!$appointment) {
                return [
                    'status' => false,
                    'message' => 'Appointment not found. Cannot save vitals.'
                ];
            }
            // Create new consultation
            $insertId = $this->model->createConsultation($appointment, $vitalsJson);
            if ($insertId !== false) {
                return [
                    'status' => true,
                    'message' => 'Vitals saved successfully (new consultation created).'
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'Failed to create consultation record.'
                ];
            }
        }

        // Update vitals
        $affectedRows = $this->model->updateConsultationVitals($appointmentId, $vitalsJson);
        
        if ($affectedRows !== false) {
            return [
                'status' => true,
                'message' => 'Vitals updated successfully.'
            ];
        } else {
            return [
                'status' => false,
                'message' => 'Failed to update vitals.'
            ];
        }
    }
}
