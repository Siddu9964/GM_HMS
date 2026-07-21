<?php
/**
 * ============================================================
 * AdminInfoController — API Reference
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api
 * Auth     : All endpoints require Auth (Session or Bearer token)
 * All endpoints are GET — read-only dashboard stats
 * ------------------------------------------------------------
 *
 * 1. GET /api/admin/dashboard-summary
 *    Response: { total_patients, total_doctors, total_revenue, total_beds,
 *                occupied_beds, available_beds, active_opd_today, active_ipd }
 *
 * 2. GET /api/admin/opd-summary
 *    Response: { total_today, pending, completed, cancelled, revenue_today }
 *
 * 3. GET /api/admin/ipd-summary
 *    Response: { total_admissions, current_admissions, total_revenue }
 *
 * 4. GET /api/admin/bed-details
 *    Response: [ { ward_name, total_beds, occupied, available } ]
 *
 * 5. GET /api/admin/opd-details
 *    Response: [ Full OPD appointment list for today ]
 *
 * 6. GET /api/admin/ipd-details
 *    Response: [ Full IPD admission list ]
 *
 * 7. GET /api/admin/bed-availability
 *    Response: { total, occupied, available, occupancy_rate_pct }
 *
 * 8. GET /api/admin/active-departments
 *    Response: [ { department_id, department_name, head_doctor, status } ]
 *
 * 9. GET /api/admin/analytics
 *    Response: { revenue_trend:[...], patient_trend:[...], department_stats:[...] }
 * ------------------------------------------------------------
 */
namespace GM_HMS\Controllers\api;

use GM_HMS\Controllers\BaseController;
use GM_HMS\Models\AppointmentModel;
use GM_HMS\Models\PatientModel;
use GM_HMS\Models\DoctorModel;
use GM_HMS\Models\InvoiceModel;
use Exception;

// For non-namespaced IPD models (require once is safe as these are small and legacy-structured)
require_once __DIR__ . '/../../reception_view/ipd_management/models/Admission.php';
require_once __DIR__ . '/../../reception_view/ipd_management/models/Bed.php';

class AdminInfoController extends BaseController {
    
    private $appointmentModel;
    private $admissionModel;
    private $bedModel;
    
    public function __construct() {
        try {
            parent::__construct();
            $this->appointmentModel = new AppointmentModel();
            
            // Check if IPD model files exist before instantiating
            if (class_exists('\Admission')) {
                $this->admissionModel = new \Admission();
            } else {
                error_log("AdminInfoController: Admission class not found.");
            }
            
            if (class_exists('\Bed')) {
                $this->bedModel = new \Bed();
            } else {
                error_log("AdminInfoController: Bed class not found.");
            }
        } catch (Exception $e) {
            error_log("AdminInfoController Init Error: " . $e->getMessage());
        }
    }
    
    /**
     * Get Comprehensive OPD Summary
     */
    public function getOpdSummary() {
        try {
            $stats = $this->appointmentModel->getStatistics();
            $appointments = $this->appointmentModel->getAllAppointments();
            
            // Aggregate revenue from OPD invoices if possible
            $invoiceModel = new InvoiceModel();
            $revenue = $invoiceModel->getStatistics();
            
            $data = [
                'stats' => $stats,
                'revenue' => $revenue,
                'recent_appointments' => array_slice($appointments, 0, 10)
            ];
            
            $this->respondSuccess($data);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Get Comprehensive IPD Summary
     */
    public function getIpdSummary() {
        try {
            $stats = $this->admissionModel->getStatistics('month');
            $bedStats = $this->bedModel->getBedOccupancy();
            $admissions = $this->admissionModel->getAllWithDetails(['status' => 'Admitted']);
            
            $data = [
                'stats' => $stats,
                'bed_stats' => $bedStats,
                'active_admissions' => $admissions
            ];
            
            $this->respondSuccess($data);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Get Detailed Bed Status
     */
    public function getBedDetails() {
        try {
            $beds = $this->bedModel->getAllWithDetails();
            $wards = $this->bedModel->getOccupancyByWard();
            
            $data = [
                'beds' => $beds,
                'wards' => $wards
            ];
            
            $this->respondSuccess($data);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get Detailed OPD Data for Table
     */
    public function getOpdDetails() {
        try {
            $filters = [
                'status' => $_GET['status'] ?? null,
                'doctor_id' => $_GET['doctor_id'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null,
            ];
            
            $appointments = $this->appointmentModel->getAllAppointments($filters);
            $this->respondSuccess(['appointments' => $appointments]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get Detailed IPD Data for Table
     */
    public function getIpdDetails() {
        try {
            $filters = [
                'status' => $_GET['status'] ?? null,
                'search' => $_GET['search'] ?? null,
            ];
            
            $admissions = $this->admissionModel->getAllWithDetails($filters);
            
            // Enrich with financial info
            foreach ($admissions as &$adm) {
                $adm['financials'] = $this->admissionModel->getBalance($adm['admission_id']);
            }
            
            $this->respondSuccess(['admissions' => $admissions]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get Dashboard Summary Statistics
     */
    public function getDashboardSummary() {
        try {
            $patientModel = new PatientModel();
            $doctorModel = new DoctorModel();
            $invoiceModel = new InvoiceModel();
            
            // Get total patients count
            $totalPatients = $patientModel->getTotalCount();
            
            // Get total doctors count
            $totalDoctors = $doctorModel->getTotalCount();
            
            // Get appointments today
            $appointmentsToday = $this->appointmentModel->getTodayCount();
            
            // Get revenue today
            $revenueToday = $invoiceModel->getTodayRevenue();
            
            // Get upcoming appointments
            $upcomingSql = "SELECT patient_name, doctor_name, specialization, DATE_FORMAT(appointment_time, '%h:%i %p') as time_formatted 
                            FROM appointments 
                            WHERE appointment_date = CURDATE() AND appointment_status != 'Cancelled'
                            ORDER BY appointment_time ASC LIMIT 5";
            $upcomingAppointments = $this->db->fetchAll($upcomingSql);
            
            // Get system alerts (low stock)
            $alertsSql = "SELECT product_name, quantity, min_stock 
                          FROM ph_product 
                          WHERE quantity <= min_stock AND is_active = 1 
                          LIMIT 5";
            $systemAlerts = $this->db->fetchAll($alertsSql);
            
            // Get recent activity
            $activitySql = "SELECT action, event_type as entity_type, resource as entity_id, created_at 
                            FROM audit_logs 
                            ORDER BY created_at DESC LIMIT 5";
            $recentActivity = $this->db->fetchAll($activitySql);

            // Get additional patient stats
            $patientsToday = $this->db->fetchOne("SELECT COUNT(*) as count FROM patient WHERE DATE(date) = CURDATE()")['count'] ?? 0;
            $patientsMonth = $this->db->fetchOne("SELECT COUNT(*) as count FROM patient WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())")['count'] ?? 0;

            // Get additional doctor stats
            $doctorsAvailable = $this->db->fetchOne("SELECT COUNT(*) as count FROM doctors WHERE status = 'Active'")['count'] ?? 0;
            $doctorsOnLeave = $this->db->fetchOne("SELECT COUNT(*) as count FROM doctors WHERE status != 'Active'")['count'] ?? 0;

            // Get additional appointment stats (Today)
            $appointmentsPending = $this->db->fetchOne("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = CURDATE() AND (appointment_status = 'Pending' OR appointment_status = 0)")['count'] ?? 0;
            $appointmentsApproved = $this->db->fetchOne("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = CURDATE() AND (appointment_status = 'Approved' OR appointment_status = 1)")['count'] ?? 0;
            $appointmentsCancelled = $this->db->fetchOne("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = CURDATE() AND (appointment_status = 'Cancelled' OR appointment_status = 2)")['count'] ?? 0;

            // Get revenue this month
            $revenueMonth = $this->db->fetchOne("SELECT COALESCE(SUM(amount), 0) as total FROM opd_invoice WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())")['total'] ?? 0;
            
            // Operations today (Currently no explicit operations table, returning empty)
            $operationsToday = [];

            $data = [
                'total_patients' => $totalPatients,
                'patients_today' => $patientsToday,
                'patients_month' => $patientsMonth,
                'total_doctors' => $totalDoctors,
                'doctors_available' => $doctorsAvailable,
                'doctors_on_leave' => $doctorsOnLeave,
                'appointments_today' => $appointmentsToday,
                'appointments_pending' => $appointmentsPending,
                'appointments_approved' => $appointmentsApproved,
                'appointments_cancelled' => $appointmentsCancelled,
                'revenue_today' => $revenueToday,
                'revenue_month' => $revenueMonth,
                'upcoming_appointments' => $upcomingAppointments,
                'system_alerts' => $systemAlerts,
                'recent_activity' => $recentActivity,
                'operations_today' => $operationsToday
            ];
            
            $this->respondSuccess($data);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get Bed Availability Statistics
     */
    public function getBedAvailability() {
        try {
            // Query detailed bed statistics from hospital_beds table
            $sql = "SELECT 
                        ward_name,
                        ward_type,
                        room_name,
                        room_category,
                        COUNT(*) as total_beds,
                        SUM(CASE WHEN bed_status = 'Occupied' THEN 1 ELSE 0 END) as occupied_beds,
                        SUM(CASE WHEN bed_status = 'Available' THEN 1 ELSE 0 END) as available_beds,
                        SUM(CASE WHEN bed_status = 'Blocked' THEN 1 ELSE 0 END) as blocked_beds,
                        SUM(CASE WHEN bed_status = 'Maintenance' THEN 1 ELSE 0 END) as maintenance_beds
                    FROM hospital_beds
                    GROUP BY ward_name, ward_type, room_name, room_category
                    ORDER BY ward_name, room_name";
            
            $bedStats = $this->db->fetchAll($sql);
            
            // Format the data for the dashboard
            $formattedStats = [];
            foreach ($bedStats as $stat) {
                $total = (int)$stat['total_beds'];
                $occupied = (int)$stat['occupied_beds'];
                
                $formattedStats[] = [
                    'ward_name' => $stat['ward_name'],
                    'ward_type' => $stat['ward_type'],
                    'room_name' => $stat['room_name'],
                    'room_category' => $stat['room_category'],
                    'total_beds' => $total,
                    'occupied_beds' => $occupied,
                    'available_beds' => (int)$stat['available_beds'],
                    'blocked_beds' => (int)$stat['blocked_beds'],
                    'maintenance_beds' => (int)$stat['maintenance_beds'],
                    'occupancy_percentage' => $total > 0 
                        ? round(($occupied / $total) * 100) 
                        : 0
                ];
            }
            
            $this->respondSuccess(['bed_stats' => $formattedStats]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get Active Departments Statistics
     */
    public function getActiveDepartments() {
        try {
            // Query active departments and count doctors in each
            // Assuming doctors table has a department_id column
            $sql = "SELECT 
                        d.department_name,
                        d.department_type,
                        d.status,
                        COUNT(doc.sl_no) as doctor_count
                    FROM departments d
                    LEFT JOIN doctors doc ON d.department_id = doc.department_id AND doc.status = 'Active'
                    WHERE d.status = 'Active'
                    GROUP BY d.department_id, d.department_name, d.department_type
                    ORDER BY d.department_name";
            
            $deptStats = $this->db->fetchAll($sql);
            
            $this->respondSuccess(['department_stats' => $deptStats]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get Analytics Data for Charts
     */
    public function getAnalyticsData() {
        try {
            // 1. Patient Admissions (Last 7 Days)
            // OPD (Appointments)
            $opdSql = "SELECT DATE(appointment_date) as day, COUNT(*) as count 
                       FROM appointments 
                       WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                       GROUP BY day ORDER BY day ASC";
            $opdData = $this->db->fetchAll($opdSql);
            
            // IPD (Admissions)
            $ipdSql = "SELECT DATE(admission_date) as day, COUNT(*) as count 
                       FROM ipd_admissions 
                       WHERE admission_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                       GROUP BY day ORDER BY day ASC";
            $ipdData = $this->db->fetchAll($ipdSql);
            
            // Format 7 days labels
            $last7Days = [];
            for ($i = 6; $i >= 0; $i--) {
                $last7Days[] = date('Y-m-d', strtotime("-$i days"));
            }
            
            $formattedAdmissions = [
                'labels' => [],
                'opd' => [],
                'ipd' => []
            ];
            
            foreach ($last7Days as $day) {
                $formattedAdmissions['labels'][] = date('D', strtotime($day));
                
                $opdCount = 0;
                foreach ($opdData as $d) {
                    if ($d['day'] == $day) {
                        $opdCount = (int)$d['count'];
                        break;
                    }
                }
                $formattedAdmissions['opd'][] = $opdCount;
                
                $ipdCount = 0;
                foreach ($ipdData as $d) {
                    if ($d['day'] == $day) {
                        $ipdCount = (int)$d['count'];
                        break;
                    }
                }
                $formattedAdmissions['ipd'][] = $ipdCount;
            }
            
            // Get revenue for last 6 months
            $revSql = "SELECT DATE_FORMAT(date, '%b %Y') as month_label, 
                              DATE_FORMAT(date, '%Y-%m') as month_key, 
                              SUM(amount) as amount 
                       FROM opd_invoice 
                       WHERE date >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 5 MONTH)
                       GROUP BY month_key
                       ORDER BY month_key ASC";
            $revData = $this->db->fetchAll($revSql);
            
            $formattedRevenue = [
                'labels' => [],
                'values' => []
            ];
            
            foreach ($revData as $r) {
                $formattedRevenue['labels'][] = $r['month_label'];
                $formattedRevenue['values'][] = (float)$r['amount'];
            }
            
            // 3. Department Performance (Patient Distribution)
            $deptPerfSql = "SELECT d.department_name, COUNT(a.appointment_id) as patient_count
                            FROM departments d
                            LEFT JOIN doctors doc ON d.department_id = doc.department_id
                            LEFT JOIN appointments a ON doc.doctor_id = a.doctor_id
                            WHERE d.status = 'Active'
                            GROUP BY d.department_id, d.department_name
                            ORDER BY patient_count DESC
                            LIMIT 5";
            $deptPerfData = $this->db->fetchAll($deptPerfSql);
            
            $formattedDept = [
                'labels' => [],
                'values' => []
            ];
            
            foreach ($deptPerfData as $dp) {
                $formattedDept['labels'][] = $dp['department_name'];
                $formattedDept['values'][] = (int)$dp['patient_count'];
            }
            
            $this->respondSuccess([
                'admissions' => $formattedAdmissions,
                'revenue' => $formattedRevenue,
                'departments' => $formattedDept
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}

