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
     * Get Comprehensive Patient Details for Drilldown Modal
     */
    public function getPatientsDetails() {
        try {
            $search = $_GET['search'] ?? null;
            $filter = $_GET['filter'] ?? 'all'; // all, opd, ipd, new_today
            
            $sql = "SELECT 
                        p.patient_id,
                        TRIM(CONCAT(COALESCE(p.first_name, ''), ' ', COALESCE(p.last_name, ''))) as full_name,
                        p.first_name,
                        p.last_name,
                        p.phone,
                        p.age,
                        p.sex,
                        p.blood_group,
                        p.city,
                        p.address,
                        p.date as registration_date,
                        COALESCE(ipd.admission_id, '') as active_admission_id,
                        COALESCE(ipd.bed_number, '') as active_bed_number,
                        COALESCE(ipd.ward_name, '') as active_ward_name,
                        COALESCE(ipd.doctor_name, '') as ipd_doctor_name,
                        CASE 
                            WHEN ipd.admission_id IS NOT NULL THEN 'IPD Inpatient'
                            WHEN opd.appointment_id IS NOT NULL THEN 'OPD Patient'
                            ELSE 'Registered'
                        END as patient_status
                    FROM patient p
                    LEFT JOIN (
                        SELECT ia.patient_id, ia.admission_id, ia.admission_date, hb.bed_number, hb.ward_name, d.full_name as doctor_name
                        FROM ipd_admissions ia
                        LEFT JOIN hospital_beds hb ON ia.bed_id = hb.sl_no
                        LEFT JOIN doctors d ON ia.admitting_doctor_id = d.doctor_id
                        WHERE ia.status = 'Admitted'
                    ) ipd ON p.patient_id = ipd.patient_id
                    LEFT JOIN (
                        SELECT patient_id, MAX(appointment_id) as appointment_id
                        FROM appointments
                        GROUP BY patient_id
                    ) opd ON p.patient_id = opd.patient_id
                    WHERE 1=1";
            
            $params = [];
            
            if (!empty($search)) {
                $sql .= " AND (p.patient_id LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ? OR p.phone LIKE ? OR p.city LIKE ?)";
                $sTerm = "%$search%";
                $params = array_merge($params, [$sTerm, $sTerm, $sTerm, $sTerm, $sTerm]);
            }

            if ($filter === 'ipd') {
                $sql .= " AND ipd.admission_id IS NOT NULL";
            } elseif ($filter === 'opd') {
                $sql .= " AND opd.appointment_id IS NOT NULL AND ipd.admission_id IS NULL";
            } elseif ($filter === 'new_today') {
                $sql .= " AND DATE(p.date) = CURDATE()";
            }

            $sql .= " ORDER BY p.date DESC LIMIT 100";

            $patients = $this->db->fetchAll($sql, $params);
            
            $summary = [
                'total' => (int)($this->db->fetchOne("SELECT COUNT(*) as c FROM patient")['c'] ?? 0),
                'today' => (int)($this->db->fetchOne("SELECT COUNT(*) as c FROM patient WHERE DATE(date) = CURDATE()")['c'] ?? 0),
                'this_month' => (int)($this->db->fetchOne("SELECT COUNT(*) as c FROM patient WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())")['c'] ?? 0),
                'active_ipd' => (int)($this->db->fetchOne("SELECT COUNT(*) as c FROM ipd_admissions WHERE status = 'Admitted'")['c'] ?? 0)
            ];

            $this->respondSuccess([
                'summary' => $summary,
                'patients' => $patients
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get Comprehensive Revenue Breakdown & Transaction Ledger for Drilldown Modal
     */
    public function getRevenueDetails() {
        try {
            $transactions = [];

            // 1. OPD Transactions (opd_billing_master)
            $opdSql = "SELECT 
                        obm.bill_id as transaction_id,
                        obm.bill_id,
                        obm.patient_id,
                        COALESCE(obm.name, CONCAT(p.first_name, ' ', COALESCE(p.last_name, ''))) as patient_name,
                        p.phone as patient_phone,
                        obm.doctor_name,
                        'OPD' as stream,
                        'OPD Consultation' as category,
                        COALESCE(obm.bill_date, DATE(obm.created_at)) as transaction_date,
                        DATE_FORMAT(COALESCE(obm.bill_date, DATE(obm.created_at)), '%d %b %Y') as formatted_date,
                        DATE_FORMAT(COALESCE(obm.bill_time, obm.created_at), '%h:%i %p') as transaction_time,
                        COALESCE(obm.payment_mode, 'Cash') as payment_mode,
                        COALESCE(obm.grand_total, 0) as grand_total,
                        COALESCE(obm.amount_paid, 0) as amount_paid,
                        COALESCE(obm.balance_due, 0) as balance_due,
                        COALESCE(obm.payment_status, 'Paid') as payment_status
                     FROM opd_billing_master obm
                     LEFT JOIN patient p ON obm.patient_id = p.patient_id
                     WHERE obm.amount_paid > 0
                     ORDER BY transaction_date DESC, obm.created_at DESC";
            $opdTransactions = $this->db->fetchAll($opdSql);
            foreach ($opdTransactions as $r) {
                $r['amount_paid'] = (float)$r['amount_paid'];
                $r['grand_total'] = (float)$r['grand_total'];
                $r['balance_due'] = (float)$r['balance_due'];
                $transactions[] = $r;
            }

            // 2. IPD Transactions (ipd_billing_master)
            $ipdSql = "SELECT 
                        ibm.bill_id as transaction_id,
                        ibm.bill_id,
                        ibm.admission_id,
                        ibm.patient_id,
                        CONCAT(p.first_name, ' ', COALESCE(p.last_name, '')) as patient_name,
                        p.phone as patient_phone,
                        d.full_name as doctor_name,
                        'IPD' as stream,
                        'IPD Inpatient Bill' as category,
                        COALESCE(DATE(ibm.updated_at), DATE(ibm.created_at), ibm.admission_date) as transaction_date,
                        DATE_FORMAT(COALESCE(DATE(ibm.updated_at), DATE(ibm.created_at), ibm.admission_date), '%d %b %Y') as formatted_date,
                        DATE_FORMAT(COALESCE(ibm.updated_at, ibm.created_at), '%h:%i %p') as transaction_time,
                        COALESCE(ibm.payment_mode, 'Cash') as payment_mode,
                        COALESCE(ibm.grand_total, 0) as grand_total,
                        COALESCE(ibm.amount_paid, 0) as amount_paid,
                        COALESCE(ibm.balance_due, 0) as balance_due,
                        COALESCE(ibm.payment_status, 'Paid') as payment_status
                     FROM ipd_billing_master ibm
                     LEFT JOIN patient p ON ibm.patient_id = p.patient_id
                     LEFT JOIN ipd_admissions ia ON ibm.admission_id = ia.admission_id
                     LEFT JOIN doctors d ON ia.admitting_doctor_id = d.doctor_id
                     WHERE ibm.amount_paid > 0
                     ORDER BY transaction_date DESC, ibm.updated_at DESC";
            $ipdTransactions = $this->db->fetchAll($ipdSql);
            foreach ($ipdTransactions as $r) {
                $r['amount_paid'] = (float)$r['amount_paid'];
                $r['grand_total'] = (float)$r['grand_total'];
                $r['balance_due'] = (float)$r['balance_due'];
                $transactions[] = $r;
            }

            // Sort all transactions by date & time DESC
            usort($transactions, function($a, $b) {
                return strcmp($b['transaction_date'] . ' ' . $b['transaction_time'], $a['transaction_date'] . ' ' . $a['transaction_time']);
            });

            // Group by Date for Date-Wise OPD & IPD Breakdown
            $dailyBreakdown = [];
            $totalOpd = 0;
            $totalIpd = 0;
            $paymentModes = ['cash' => 0, 'upi' => 0, 'card' => 0, 'insurance' => 0];

            $todayStr = date('Y-m-d');
            $thisMonthStr = date('Y-m');

            $todayOpd = 0;
            $todayIpd = 0;
            $monthOpd = 0;
            $monthIpd = 0;

            foreach ($transactions as $tx) {
                $d = $tx['transaction_date'];
                if (!isset($dailyBreakdown[$d])) {
                    $dailyBreakdown[$d] = [
                        'date' => $d,
                        'formatted_date' => date('d M Y', strtotime($d)),
                        'day_name' => date('l', strtotime($d)),
                        'opd_amount' => 0,
                        'ipd_amount' => 0,
                        'total_amount' => 0,
                        'tx_count' => 0
                    ];
                }
                
                $amt = $tx['amount_paid'];
                if ($tx['stream'] === 'OPD') {
                    $dailyBreakdown[$d]['opd_amount'] += $amt;
                    $totalOpd += $amt;
                    if ($d === $todayStr) $todayOpd += $amt;
                    if (substr($d, 0, 7) === $thisMonthStr) $monthOpd += $amt;
                } else {
                    $dailyBreakdown[$d]['ipd_amount'] += $amt;
                    $totalIpd += $amt;
                    if ($d === $todayStr) $todayIpd += $amt;
                    if (substr($d, 0, 7) === $thisMonthStr) $monthIpd += $amt;
                }
                $dailyBreakdown[$d]['total_amount'] += $amt;
                $dailyBreakdown[$d]['tx_count']++;

                $pm = strtolower($tx['payment_mode']);
                if (strpos($pm, 'cash') !== false) $paymentModes['cash'] += $amt;
                elseif (strpos($pm, 'upi') !== false || strpos($pm, 'online') !== false) $paymentModes['upi'] += $amt;
                elseif (strpos($pm, 'card') !== false) $paymentModes['card'] += $amt;
                elseif (strpos($pm, 'insur') !== false) $paymentModes['insurance'] += $amt;
                else $paymentModes['cash'] += $amt;
            }

            $dailyBreakdown = array_values($dailyBreakdown);

            $summary = [
                'revenue_today' => $todayOpd + $todayIpd,
                'opd_revenue_today' => $todayOpd,
                'ipd_revenue_today' => $todayIpd,
                'revenue_month' => $monthOpd + $monthIpd,
                'opd_revenue_month' => $monthOpd,
                'ipd_revenue_month' => $monthIpd,
                'total_revenue_all' => $totalOpd + $totalIpd,
                'total_opd_all' => $totalOpd,
                'total_ipd_all' => $totalIpd,
                'total_transactions' => count($transactions)
            ];

            $this->respondSuccess([
                'summary' => $summary,
                'daily_breakdown' => $dailyBreakdown,
                'transactions' => $transactions,
                'payment_modes' => $paymentModes
            ]);
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
            
            // Get appointments today (OPD)
            $appointmentsToday = $this->appointmentModel->getTodayCount();
            
            // Get upcoming appointments today strictly
            $upcomingSql = "SELECT 
                                a.appointment_id,
                                a.patient_name, 
                                a.doctor_name, 
                                a.specialization, 
                                DATE_FORMAT(a.appointment_time, '%h:%i %p') as time_formatted,
                                DATE_FORMAT(a.appointment_date, '%d %b %Y') as date_formatted,
                                'Today' as day_label,
                                a.appointment_status,
                                a.payment_status,
                                a.token_number
                            FROM appointments a
                            WHERE DATE(a.appointment_date) = CURDATE() AND a.appointment_status NOT IN ('Cancelled', '2')
                            ORDER BY a.appointment_time ASC, a.token_number ASC 
                            LIMIT 10";
            $upcomingAppointments = $this->db->fetchAll($upcomingSql);
            
            // Get system alerts (low stock)
            $alertsSql = "SELECT product_name, quantity, min_stock 
                          FROM ph_product 
                          WHERE quantity <= min_stock AND is_active = 1 
                          LIMIT 5";
            $systemAlerts = $this->db->fetchAll($alertsSql);
            
            // Get rich live recent activity from admissions, registrations, appointments, payments & audit logs
            $recentActivity = [];
            try {
                // 1. IPD Admissions & Discharges
                $admActivities = $this->db->fetchAll(
                    "SELECT 
                        'IPD' as event_type,
                        CASE WHEN a.status = 'Discharged' THEN 'Inpatient Discharged' ELSE 'New Inpatient Admission' END as action,
                        CONCAT(p.first_name, ' ', COALESCE(p.last_name, '')) as patient_name,
                        CONCAT('Bed ', COALESCE(b.bed_number, 'N/A'), ' (', COALESCE(b.ward_name, 'Ward'), ')') as entity_details,
                        a.admission_id as entity_id,
                        COALESCE(a.updated_at, a.created_at) as created_at,
                        'purple' as color,
                        'fa-bed' as icon
                     FROM ipd_admissions a
                     LEFT JOIN patient p ON a.patient_id = p.patient_id
                     LEFT JOIN hospital_beds b ON a.bed_id = b.sl_no
                     ORDER BY COALESCE(a.updated_at, a.created_at) DESC LIMIT 4"
                );

                // 2. Patient Registrations
                $regActivities = $this->db->fetchAll(
                    "SELECT 
                        'PATIENT' as event_type,
                        'New Patient Registered' as action,
                        CONCAT(p.first_name, ' ', COALESCE(p.last_name, '')) as patient_name,
                        CONCAT(p.patient_id, ' • ', COALESCE(p.city, 'Registered')) as entity_details,
                        p.patient_id as entity_id,
                        CONCAT(p.date, ' ', COALESCE(SUBSTRING(p.time, 1, 8), '12:00:00')) as created_at,
                        'emerald' as color,
                        'fa-user-plus' as icon
                     FROM patient p
                     ORDER BY p.date DESC, p.time DESC LIMIT 4"
                );

                // 3. OPD Appointments
                $aptActivities = $this->db->fetchAll(
                    "SELECT 
                        'OPD' as event_type,
                        'OPD Visit Scheduled' as action,
                        a.patient_name,
                        CONCAT('Dr. ', a.doctor_name, ' (', a.specialization, ')') as entity_details,
                        a.appointment_id as entity_id,
                        COALESCE(a.created_at, CONCAT(a.appointment_date, ' ', a.appointment_time)) as created_at,
                        'blue' as color,
                        'fa-calendar-check' as icon
                     FROM appointments a
                     ORDER BY COALESCE(a.created_at, a.appointment_date) DESC LIMIT 4"
                );

                // 4. IPD / OPD Billing Collections
                $billActivities = $this->db->fetchAll(
                    "SELECT 
                        'BILLING' as event_type,
                        'Payment Received' as action,
                        CONCAT(p.first_name, ' ', COALESCE(p.last_name, '')) as patient_name,
                        CONCAT('₹', FORMAT(ibm.amount_paid, 0), ' • ', ibm.bill_id) as entity_details,
                        ibm.bill_id as entity_id,
                        COALESCE(ibm.updated_at, ibm.created_at) as created_at,
                        'amber' as color,
                        'fa-receipt' as icon
                     FROM ipd_billing_master ibm
                     LEFT JOIN patient p ON ibm.patient_id = p.patient_id
                     WHERE ibm.amount_paid > 0
                     ORDER BY COALESCE(ibm.updated_at, ibm.created_at) DESC LIMIT 4"
                );

                $recentActivity = array_merge($admActivities, $regActivities, $aptActivities, $billActivities);
                usort($recentActivity, function($a, $b) {
                    return strcmp($b['created_at'], $a['created_at']);
                });
                $recentActivity = array_slice($recentActivity, 0, 7);
            } catch (Exception $e) {
                $recentActivity = [];
            }

            // Get patient stats (OPD & General)
            $patientsToday = (int)($this->db->fetchOne("SELECT COUNT(*) as count FROM patient WHERE DATE(date) = CURDATE()")['count'] ?? 0);
            $patientsMonth = (int)($this->db->fetchOne("SELECT COUNT(*) as count FROM patient WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())")['count'] ?? 0);

            // Get IPD Inpatient stats
            $activeIpd = (int)($this->db->fetchOne("SELECT COUNT(*) as count FROM ipd_admissions WHERE status = 'Admitted'")['count'] ?? 0);
            $ipdAdmissionsToday = (int)($this->db->fetchOne("SELECT COUNT(*) as count FROM ipd_admissions WHERE DATE(admission_date) = CURDATE()")['count'] ?? 0);
            $ipdDischargesToday = (int)($this->db->fetchOne("SELECT COUNT(*) as count FROM ipd_admissions WHERE DATE(discharge_date) = CURDATE() OR (status = 'Discharged' AND DATE(updated_at) = CURDATE())")['count'] ?? 0);
            $ipdAdmissionsMonth = (int)($this->db->fetchOne("SELECT COUNT(*) as count FROM ipd_admissions WHERE MONTH(admission_date) = MONTH(CURDATE()) AND YEAR(admission_date) = YEAR(CURDATE())")['count'] ?? 0);

            // Bed & ICU Stats
            $totalBeds = (int)($this->db->fetchOne("SELECT COUNT(*) as count FROM hospital_beds")['count'] ?? 0);
            $occupiedBeds = (int)($this->db->fetchOne("SELECT COUNT(*) as count FROM hospital_beds WHERE bed_status = 'Occupied'")['count'] ?? 0);
            $availableBeds = max(0, $totalBeds - $occupiedBeds);
            $bedOccupancyRate = $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100) : 0;

            $icuStats = $this->db->fetchOne("SELECT COUNT(*) as total_icu, SUM(CASE WHEN bed_status = 'Occupied' THEN 1 ELSE 0 END) as occupied_icu FROM hospital_beds WHERE ward_name LIKE '%ICU%' OR ward_name LIKE '%CCU%' OR room_type LIKE '%ICU%'");
            $totalIcu = (int)($icuStats['total_icu'] ?? 0);
            $occupiedIcu = (int)($icuStats['occupied_icu'] ?? 0);
            $icuOccupancyPct = $totalIcu > 0 ? round(($occupiedIcu / $totalIcu) * 100) : 0;

            // Doctor stats
            $doctorsAvailable = (int)($this->db->fetchOne("SELECT COUNT(*) as count FROM doctors WHERE status = 'Active'")['count'] ?? 0);
            $doctorsOnLeave = (int)($this->db->fetchOne("SELECT COUNT(*) as count FROM doctors WHERE status != 'Active'")['count'] ?? 0);

            // Appointment stats (Today)
            $appointmentsPending = (int)($this->db->fetchOne("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = CURDATE() AND (appointment_status = 'Pending' OR appointment_status = 0)")['count'] ?? 0);
            $appointmentsApproved = (int)($this->db->fetchOne("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = CURDATE() AND (appointment_status = 'Approved' OR appointment_status = 1)")['count'] ?? 0);
            $appointmentsCancelled = (int)($this->db->fetchOne("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = CURDATE() AND (appointment_status = 'Cancelled' OR appointment_status = 2)")['count'] ?? 0);

            // OPD Revenue (opd_billing_master)
            $opdRevenueTodayRes = $this->db->fetchOne("SELECT COALESCE(SUM(amount_paid), 0) as total FROM opd_billing_master WHERE DATE(bill_date) = CURDATE() OR DATE(created_at) = CURDATE()");
            $opdRevenueToday = (float)($opdRevenueTodayRes['total'] ?? 0);
            if ($opdRevenueToday <= 0) {
                $opdRevenueToday = (float)($this->db->fetchOne("SELECT COALESCE(SUM(amount), 0) as total FROM opd_invoice WHERE DATE(date) = CURDATE()")['total'] ?? 0);
            }

            $opdRevenueMonthRes = $this->db->fetchOne("SELECT COALESCE(SUM(amount_paid), 0) as total FROM opd_billing_master WHERE MONTH(COALESCE(bill_date, created_at)) = MONTH(CURDATE()) AND YEAR(COALESCE(bill_date, created_at)) = YEAR(CURDATE())");
            $opdRevenueMonth = (float)($opdRevenueMonthRes['total'] ?? 0);
            if ($opdRevenueMonth <= 0) {
                $opdRevenueMonth = (float)($this->db->fetchOne("SELECT COALESCE(SUM(amount), 0) as total FROM opd_invoice WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())")['total'] ?? 0);
            }

            // IPD Revenue (ipd_billing_master collections)
            $ipdRevenueToday = 0;
            try {
                $ipdMasterToday = $this->db->fetchOne("SELECT COALESCE(SUM(amount_paid), 0) as total FROM ipd_billing_master WHERE DATE(updated_at) = CURDATE() AND amount_paid > 0");
                $ipdRevenueToday = (float)($ipdMasterToday['total'] ?? 0);
                if ($ipdRevenueToday <= 0) {
                    $ipdPaymentToday = $this->db->fetchOne("SELECT COALESCE(SUM(CASE WHEN payment_type = 'REFUND' THEN -amount ELSE amount END), 0) as total FROM ipd_payment WHERE DATE(payment_date) = CURDATE() OR DATE(created_at) = CURDATE()");
                    $ipdRevenueToday = (float)($ipdPaymentToday['total'] ?? 0);
                }
            } catch (Exception $e) {
                $ipdRevenueToday = 0;
            }

            $ipdRevenueMonth = 0;
            try {
                $ipdMasterMonth = $this->db->fetchOne("SELECT COALESCE(SUM(amount_paid), 0) as total FROM ipd_billing_master WHERE MONTH(COALESCE(updated_at, created_at, admission_date)) = MONTH(CURDATE()) AND YEAR(COALESCE(updated_at, created_at, admission_date)) = YEAR(CURDATE())");
                $ipdRevenueMonth = (float)($ipdMasterMonth['total'] ?? 0);
                if ($ipdRevenueMonth <= 0) {
                    $ipdPaymentMonth = $this->db->fetchOne("SELECT COALESCE(SUM(CASE WHEN payment_type = 'REFUND' THEN -amount ELSE amount END), 0) as total FROM ipd_payment WHERE MONTH(COALESCE(payment_date, created_at)) = MONTH(CURDATE()) AND YEAR(COALESCE(payment_date, created_at)) = YEAR(CURDATE())");
                    $ipdRevenueMonth = (float)($ipdPaymentMonth['total'] ?? 0);
                }
            } catch (Exception $e) {
                $ipdRevenueMonth = 0;
            }

            // Total Consolidated Revenue
            $revenueToday = $opdRevenueToday + $ipdRevenueToday;
            $revenueMonth = $opdRevenueMonth + $ipdRevenueMonth;
            
            // Operations (OT / Clinical Procedures)
            $operationsToday = [];
            try {
                $otSql = "SELECT 
                            bi.item_id,
                            bi.description as name, 
                            bi.charge_type as type, 
                            bi.total_amount as amount,
                            CONCAT(p.first_name, ' ', COALESCE(p.last_name, '')) as patient_name,
                            bi.patient_id,
                            DATE_FORMAT(bi.charge_date, '%d %b %Y') as formatted_date,
                            DATE_FORMAT(bi.created_at, '%h:%i %p') as time_formatted,
                            bi.status
                          FROM ipd_billing_items bi
                          LEFT JOIN patient p ON bi.patient_id = p.patient_id
                          WHERE bi.charge_type IN ('OT', 'PROCEDURE') AND bi.status != 'CANCELLED'
                          ORDER BY bi.charge_date DESC, bi.created_at DESC LIMIT 5";
                $operationsToday = $this->db->fetchAll($otSql);
            } catch (Exception $e) {
                $operationsToday = [];
            }

            $data = [
                'total_patients' => $totalPatients,
                'patients_today' => $patientsToday,
                'patients_month' => $patientsMonth,
                'active_ipd' => $activeIpd,
                'ipd_admissions_today' => $ipdAdmissionsToday,
                'ipd_discharges_today' => $ipdDischargesToday,
                'ipd_admissions_month' => $ipdAdmissionsMonth,
                'total_beds' => $totalBeds,
                'occupied_beds' => $occupiedBeds,
                'available_beds' => $availableBeds,
                'bed_occupancy_rate' => $bedOccupancyRate,
                'total_icu' => $totalIcu,
                'occupied_icu' => $occupiedIcu,
                'icu_occupancy_pct' => $icuOccupancyPct,
                'total_doctors' => $totalDoctors,
                'doctors_available' => $doctorsAvailable,
                'doctors_on_leave' => $doctorsOnLeave,
                'appointments_today' => $appointmentsToday,
                'appointments_pending' => $appointmentsPending,
                'appointments_approved' => $appointmentsApproved,
                'appointments_cancelled' => $appointmentsCancelled,
                'opd_revenue_today' => $opdRevenueToday,
                'opd_revenue_month' => $opdRevenueMonth,
                'ipd_revenue_today' => $ipdRevenueToday,
                'ipd_revenue_month' => $ipdRevenueMonth,
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
                        COALESCE(ward_name, 'General Ward') as ward_name,
                        COALESCE(room_type, 'General') as ward_type,
                        COALESCE(room_name, CONCAT('Room ', room_number)) as room_name,
                        COALESCE(floor_name, room_type, 'Standard') as room_category,
                        COUNT(*) as total_beds,
                        SUM(CASE WHEN bed_status = 'Occupied' THEN 1 ELSE 0 END) as occupied_beds,
                        SUM(CASE WHEN bed_status = 'Available' THEN 1 ELSE 0 END) as available_beds,
                        SUM(CASE WHEN bed_status = 'Blocked' THEN 1 ELSE 0 END) as blocked_beds,
                        SUM(CASE WHEN bed_status = 'Maintenance' THEN 1 ELSE 0 END) as maintenance_beds
                    FROM hospital_beds
                    GROUP BY ward_name, room_type, room_name, floor_name
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
            // 1. Patient Admissions & Consultations (Dynamic Range: 7, 14, 30 Days)
            $days = intval($_GET['days'] ?? 7);
            if ($days <= 0 || $days > 90) {
                $days = 7;
            }
            $intervalDays = $days - 1;

            // OPD (Appointments)
            $opdSql = "SELECT DATE(appointment_date) as day, COUNT(*) as count 
                       FROM appointments 
                       WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL $intervalDays DAY)
                       GROUP BY day ORDER BY day ASC";
            $opdData = $this->db->fetchAll($opdSql);
            
            // IPD (Admissions)
            $ipdSql = "SELECT DATE(admission_date) as day, COUNT(*) as count 
                       FROM ipd_admissions 
                       WHERE admission_date >= DATE_SUB(CURDATE(), INTERVAL $intervalDays DAY)
                       GROUP BY day ORDER BY day ASC";
            $ipdData = $this->db->fetchAll($ipdSql);
            
            // Generate full date list
            $dateList = [];
            for ($i = $intervalDays; $i >= 0; $i--) {
                $dateList[] = date('Y-m-d', strtotime("-$i days"));
            }
            
            $formattedAdmissions = [
                'range_days' => $days,
                'labels' => [],
                'raw_dates' => [],
                'opd' => [],
                'ipd' => [],
                'total' => [],
                'summary' => [
                    'total_opd' => 0,
                    'total_ipd' => 0,
                    'total_flow' => 0,
                    'avg_daily' => 0,
                    'conversion_rate' => 0,
                    'peak_day' => null
                ],
                'daily_breakdown' => []
            ];
            
            $maxTotal = -1;
            $peakDayInfo = null;

            foreach ($dateList as $day) {
                $dayLabel = ($days <= 7) ? date('D (M j)', strtotime($day)) : date('M j', strtotime($day));
                $dayName = date('l', strtotime($day));
                $formattedAdmissions['labels'][] = $dayLabel;
                $formattedAdmissions['raw_dates'][] = $day;
                
                $opdCount = 0;
                foreach ($opdData as $d) {
                    if ($d['day'] == $day) {
                        $opdCount = (int)$d['count'];
                        break;
                    }
                }
                
                $ipdCount = 0;
                foreach ($ipdData as $d) {
                    if ($d['day'] == $day) {
                        $ipdCount = (int)$d['count'];
                        break;
                    }
                }

                $totalCount = $opdCount + $ipdCount;
                
                $formattedAdmissions['opd'][] = $opdCount;
                $formattedAdmissions['ipd'][] = $ipdCount;
                $formattedAdmissions['total'][] = $totalCount;

                $formattedAdmissions['summary']['total_opd'] += $opdCount;
                $formattedAdmissions['summary']['total_ipd'] += $ipdCount;
                $formattedAdmissions['summary']['total_flow'] += $totalCount;

                if ($totalCount > $maxTotal) {
                    $maxTotal = $totalCount;
                    $peakDayInfo = [
                        'date' => $day,
                        'label' => date('D, M j', strtotime($day)),
                        'count' => $totalCount,
                        'opd' => $opdCount,
                        'ipd' => $ipdCount
                    ];
                }

                $opdPct = $totalCount > 0 ? round(($opdCount / $totalCount) * 100) : 0;
                $ipdPct = $totalCount > 0 ? (100 - $opdPct) : 0;

                $formattedAdmissions['daily_breakdown'][] = [
                    'date' => $day,
                    'day_name' => $dayName,
                    'label' => $dayLabel,
                    'full_label' => date('l, d M Y', strtotime($day)),
                    'opd_count' => $opdCount,
                    'ipd_count' => $ipdCount,
                    'total' => $totalCount,
                    'opd_pct' => $opdPct,
                    'ipd_pct' => $ipdPct
                ];
            }

            $totalFlow = $formattedAdmissions['summary']['total_flow'];
            $totalIpd = $formattedAdmissions['summary']['total_ipd'];
            $formattedAdmissions['summary']['avg_daily'] = round($totalFlow / $days, 1);
            $formattedAdmissions['summary']['conversion_rate'] = $totalFlow > 0 ? round(($totalIpd / $totalFlow) * 100, 1) : 0;
            $formattedAdmissions['summary']['peak_day'] = $peakDayInfo;
            
            // 2. Revenue for Last 6 Months (OPD vs IPD vs Total)
            $last6Months = [];
            for ($i = 5; $i >= 0; $i--) {
                $mKey = date('Y-m', strtotime("-$i months"));
                $mLabel = date('M Y', strtotime("-$i months"));
                $last6Months[$mKey] = [
                    'label' => $mLabel,
                    'opd' => 0,
                    'ipd' => 0,
                    'total' => 0
                ];
            }

            // OPD monthly revenue
            $opdRevSql = "SELECT DATE_FORMAT(date, '%Y-%m') as month_key, 
                                 COALESCE(SUM(amount), 0) as amount 
                          FROM opd_invoice 
                          WHERE date >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 5 MONTH)
                          GROUP BY month_key";
            $opdRevData = $this->db->fetchAll($opdRevSql);
            foreach ($opdRevData as $row) {
                if (isset($last6Months[$row['month_key']])) {
                    $last6Months[$row['month_key']]['opd'] = (float)$row['amount'];
                }
            }

            // IPD monthly revenue
            try {
                $ipdRevSql = "SELECT DATE_FORMAT(COALESCE(payment_date, created_at), '%Y-%m') as month_key, 
                                     COALESCE(SUM(CASE WHEN payment_type = 'REFUND' THEN -amount ELSE amount END), 0) as amount 
                              FROM ipd_payment 
                              WHERE COALESCE(payment_date, created_at) >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 5 MONTH)
                              GROUP BY month_key";
                $ipdRevData = $this->db->fetchAll($ipdRevSql);
                foreach ($ipdRevData as $row) {
                    if (isset($last6Months[$row['month_key']])) {
                        $last6Months[$row['month_key']]['ipd'] = (float)$row['amount'];
                    }
                }
            } catch (Exception $e) {
                // fallback
            }

            $formattedRevenue = [
                'labels' => [],
                'opd' => [],
                'ipd' => [],
                'total' => [],
                'values' => [] // backward compatibility
            ];
            
            foreach ($last6Months as $m) {
                $total = $m['opd'] + $m['ipd'];
                $formattedRevenue['labels'][] = $m['label'];
                $formattedRevenue['opd'][] = $m['opd'];
                $formattedRevenue['ipd'][] = $m['ipd'];
                $formattedRevenue['total'][] = $total;
                $formattedRevenue['values'][] = $total;
            }
            
            // 3. Department Performance (Combined OPD + IPD Patient Distribution)
            $deptPerfSql = "SELECT 
                                d.department_name,
                                (COALESCE(opd.opd_count, 0) + COALESCE(ipd.ipd_count, 0)) as patient_count,
                                COALESCE(opd.opd_count, 0) as opd_count,
                                COALESCE(ipd.ipd_count, 0) as ipd_count
                            FROM departments d
                            LEFT JOIN (
                                SELECT doc.department_id, COUNT(a.appointment_id) as opd_count
                                FROM doctors doc
                                JOIN appointments a ON doc.doctor_id = a.doctor_id
                                GROUP BY doc.department_id
                            ) opd ON d.department_id = opd.department_id
                            LEFT JOIN (
                                SELECT doc.department_id, COUNT(ia.admission_id) as ipd_count
                                FROM doctors doc
                                JOIN ipd_admissions ia ON doc.doctor_id = ia.admitting_doctor_id
                                GROUP BY doc.department_id
                            ) ipd ON d.department_id = ipd.department_id
                            WHERE d.status = 'Active'
                            ORDER BY patient_count DESC
                            LIMIT 6";
            $deptPerfData = $this->db->fetchAll($deptPerfSql);
            
            $formattedDept = [
                'labels' => [],
                'values' => [],
                'opd' => [],
                'ipd' => []
            ];
            
            foreach ($deptPerfData as $dp) {
                $formattedDept['labels'][] = $dp['department_name'];
                $formattedDept['values'][] = (int)$dp['patient_count'];
                $formattedDept['opd'][] = (int)$dp['opd_count'];
                $formattedDept['ipd'][] = (int)$dp['ipd_count'];
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


