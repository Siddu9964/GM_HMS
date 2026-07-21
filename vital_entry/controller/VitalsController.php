<?php
require_once dirname(__DIR__) . '/service/VitalsService.php';

class VitalsController {
    private $service;

    public function __construct() {
        $this->service = new VitalsService();
    }

    public function getPatients() {
        error_reporting(0);
        header('Content-Type: application/json');
        try {
            $response = $this->service->getPatients();
            if (ob_get_length()) ob_clean();
            echo json_encode($response);
        } catch (Exception $e) {
            if (ob_get_length()) ob_clean();
            echo json_encode([
                'status' => false,
                'message' => 'An error occurred while fetching patients: ' . $e->getMessage()
            ]);
        }
    }

    public function getVitals() {
        error_reporting(0);
        header('Content-Type: application/json');
        $appointmentId = $_GET['appointment_id'] ?? null;
        
        try {
            $response = $this->service->getVitals($appointmentId);
            if (ob_get_length()) ob_clean();
            echo json_encode($response);
        } catch (Exception $e) {
            if (ob_get_length()) ob_clean();
            echo json_encode([
                'status' => false,
                'message' => 'An error occurred while fetching vitals: ' . $e->getMessage()
            ]);
        }
    }

    public function updateVitals() {
        error_reporting(0);
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if (ob_get_length()) ob_clean();
            echo json_encode([
                'status' => false,
                'message' => 'Invalid request method. Please use POST.'
            ]);
            return;
        }

        // Get JSON input
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, true);

        if (!$input) {
            // Fallback to $_POST if not JSON
            $input = $_POST;
        }

        try {
            $response = $this->service->updateVitals($input);
            if (ob_get_length()) ob_clean();
            echo json_encode($response);
        } catch (Exception $e) {
            if (ob_get_length()) ob_clean();
            echo json_encode([
                'status' => false,
                'message' => 'An error occurred while updating vitals: ' . $e->getMessage()
            ]);
        }
    }
}
