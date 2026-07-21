<?php
/**
 * Dashboard Controller
 * 
 * Provides statistics and summary data for IPD dashboard
 * 
 * @package IPD_Management\Controllers
 */

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/Admission.php';
require_once __DIR__ . '/../models/Bed.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../models/Doctor.php';
require_once __DIR__ . '/../models/Appointment.php';

class DashboardController extends BaseController
{

    protected function handleGet()
    {
        // Check for action in query parameter or URI path
        $action = $this->getParam('action');

        // Also check URI for /dashboard/patients, /dashboard/doctors, or /dashboard/appointments
        if (!$action) {
            $uri = $_SERVER['REQUEST_URI'];
            if (strpos($uri, '/patients') !== false) {
                $action = 'patients';
            } elseif (strpos($uri, '/doctors') !== false) {
                $action = 'doctors';
            } elseif (strpos($uri, '/appointments') !== false) {
                $action = 'appointments';
            }
        }

        if ($action === 'patients') {
            // Search patients for dropdown
            $search = $this->getParam('search', '');
            $patientModel = new Patient();
            $patients = $patientModel->searchPatients($search);
            $this->success(['patients' => $patients]);
        } elseif ($action === 'appointments') {
            // Search appointments for dropdown
            $search = $this->getParam('search', '');
            $aptModel = new Appointment();
            $appointments = $aptModel->searchAppointments($search);
            $this->success(['appointments' => $appointments]);
        } elseif ($action === 'doctors') {
            // Search doctors for dropdown
            $search = $this->getParam('search', '');
            $doctorModel = new Doctor();
            $doctors = $doctorModel->searchDoctors($search);
            $this->success(['doctors' => $doctors]);
        } else {
            // Get dashboard statistics
            $admissionModel = new Admission();
            $bedModel = new Bed();
            $paymentModel = new Payment();

            $stats = [
                'admissions' => [
                    'today' => $admissionModel->getStatistics('today'),
                    'week' => $admissionModel->getStatistics('week'),
                    'month' => $admissionModel->getStatistics('month'),
                    'active' => $admissionModel->count(['status' => 'Admitted'])
                ],
                'beds' => $bedModel->getBedOccupancy(),
                'payments' => [
                    'today' => $paymentModel->getPaymentStats('today'),
                    'week' => $paymentModel->getPaymentStats('week'),
                    'month' => $paymentModel->getPaymentStats('month')
                ]
            ];

            $this->success($stats, 'Dashboard statistics retrieved successfully');
        }
    }
}
