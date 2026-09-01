<?php
/**
 * ============================================================
 * OpdController — API Reference
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api
 * Auth     : Session or Bearer token (Doctors see only their queue)
 * ------------------------------------------------------------
 *
 * 1. GET /api/opd/queue
 *    No params. Returns TODAY's OPD queue sorted: Pending first, then by time.
 *    Doctors see only their own patients.
 *    Response fields: appointment_id, token_number, appointment_time,
 *      appointment_status, patient_id, first_name, last_name, age, sex,
 *      phone, doctor_name, specialization, room_number
 *
 * 2. GET /api/opd/encounter/{APT-ID}
 *    Example: GET /api/opd/encounter/APT-20260626-0001
 *    Response:
 *      {
 *        "appointment":  { full appointment + patient basics },
 *        "consultation": { vital_signs: "{bp,pulse,temp,weight,spo2}", complaint: "..." },
 *        "prescriptions": [ { name, dosage, frequency, timing, duration, qty } ],
 *        "general_instructions": "...",
 *        "lab_orders":   [ { order_id, test_name, priority, status } ],
 *        "invoices":     [ { invoice_id, amount, status } ]
 *      }
 *
 * 3. POST /api/opd/vitals
 *    Body:
 *      {
 *        "appointment_id":  "APT-20260626-0001",
 *        "patient_id":      "PID-20260626-001",
 *        "doctor_id":       "DOC-001",
 *        "bp":              "120/80",
 *        "pulse":           "72",
 *        "temp":            "98.6",
 *        "weight":          "65",
 *        "spo2":            "99",
 *        "chief_complaint": "Fever and body ache"
 *      }
 *    Side effect: Sets appointment_status = 1 (In Progress)
 *
 * 4. POST /api/opd/invoice
 *    Body:
 *      {
 *        "patient_id":     "PID-20260626-001",
 *        "doctor_id":      "DOC-001",
 *        "appointment_id": "APT-20260626-0001",
 *        "title":          "OPD Consultation",
 *        "amount":         500,
 *        "status":         "Paid",
 *        "payment_method": "Cash"
 *      }
 *    Response: { "invoice_id": "INV-20260626-1234" }
 *
 * 5. POST /api/opd/lab-request
 *    Body:
 *      {
 *        "patient_id": "PID-20260626-001",
 *        "doctor_id":  "DOC-001",
 *        "test_name":  "Complete Blood Count (CBC)",
 *        "priority":   "Urgent",
 *        "notes":      "Check for anaemia"
 *      }
 *    Response: { "order_id": "LAB-20260626-5678" }
 *
 * 6. POST /api/opd/follow-up
 *    Body:
 *      {
 *        "patient_id":      "PID-20260626-001",
 *        "doctor_id":       "DOC-001",
 *        "appointment_id":  "APT-20260626-0001",
 *        "follow_up_date":  "2026-07-10",
 *        "clinical_notes":  "Patient improving.",
 *        "notes":           "Return if fever persists.",
 *        "plan":            "Continue Paracetamol + ORS",
 *        "dietary_advice":  "Drink plenty of fluids"
 *      }
 *    Side effect: Sets appointment_status = Completed
 *
 * 7. POST /api/opd/analyze-symptoms    [Calls Google Gemini AI]
 *    Body:
 *      {
 *        "complaint":          "High fever with chills for 3 days",
 *        "patient_age":        35,
 *        "patient_gender":     "Male",
 *        "patient_allergies":  ["Penicillin"]
 *      }
 *    Response: { "treatment_plan": "{diagnosis, medications:[...], lifestyle}", "ai_model":"gemini-1.5-flash" }
 *    Medications object keys: name, dosage, frequency, timing, duration, qty, purpose, warnings
 *
 * 8. GET /api/opd/stats
 *    Response: { "total_opd":42, "active_doctors":8, "revenue_today":21000 }
 *
 * 9. GET /api/opd/reports
 *    Response: { "daily_trend":[...], "doctor_wise":[...], "revenue":{total, count} }
 * ------------------------------------------------------------
 */
namespace GM_HMS\Controllers\api;

use GM_HMS\Controllers\BaseController;
use Exception;

/**
 * OPD Controller
 *
 * Aggregates logic for the OPD Reception View.
 * Handles Queue, Vitals, Billing, Prescriptions, and Lab Requests.
 *
 * @package GM_HMS\Controllers\api
 * @version 1.1.0
 */
class OpdController extends BaseController
{

    /**
     * GET /api/opd/queue
     * Get today's OPD appointments with live status
     */
    public function getLiveQueue()
    {
        $this->restrictMethod('GET');

        try {
            $today = date('Y-m-d');

            $sql = "SELECT a.appointment_id, a.token_number, a.appointment_time, 
                           CASE 
                               WHEN a.appointment_status = '0' THEN 'Completed'
                               WHEN a.appointment_status = '1' THEN 'Pending'
                               ELSE a.appointment_status 
                           END as appointment_status, 
                           p.patient_id, p.first_name, p.last_name, p.age, p.sex, p.phone,
                           COALESCE(NULLIF(a.specialization, ''), NULLIF(d.specialization, ''), 'General Medicine') as department,
                           d.doctor_id, d.full_name as doctor_name, d.specialization, d.room_number,
                           c.vital_signs, c.consultation_id, c.status as consult_status
                    FROM appointments a
                JOIN patient p ON a.patient_id = p.patient_id
                LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
                LEFT JOIN consultations c ON a.appointment_id COLLATE utf8mb4_unicode_ci = c.appointment_id
                WHERE a.appointment_date = CURDATE() AND (a.appointment_type = 'OPD' OR a.appointment_type IS NULL)";

            $params = [];

            // SECURITY: Filter by Doctor ID if logged in as Doctor
            if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                session_start();
            }
            if (isset($_SESSION['role']) && $_SESSION['role'] === 'Doctor' && isset($_SESSION['user_id'])) {
                $sql .= " AND a.doctor_id = ?";
                $params[] = $_SESSION['user_id'];
            }

            $sql .= " ORDER BY (a.appointment_status = '1' OR a.appointment_status = 'Scheduled') DESC, a.appointment_time ASC";

            $result = $this->db->fetchAll($sql, $params);

            $this->respondSuccess($result);

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/opd/encounter/{appointment_id}
     * Get full encounter details
     */
    public function getEncounterDetails($appointmentId)
    {
        $this->restrictMethod('GET');

        try {
            // 1. Get Appointment & Patient Basics
            $appointment = $this->db->fetchOne(
                 "SELECT a.*, p.first_name, p.last_name, p.age, p.sex, p.blood_group 
                 FROM appointments a 
                 JOIN patient p ON a.patient_id = p.patient_id 
                 WHERE a.appointment_id = ?",
                [$appointmentId]
            );

            if (!$appointment) {
                $this->respondNotFound('Appointment not found');
            }

            // 2. Get Vitals & Clinical Notes (from consultations)
            $consultation = $this->db->fetchOne(
                "SELECT * FROM consultations WHERE appointment_id = ?",
                [$appointmentId]
            );

            // 3. Extract Prescriptions/Medicines from Consultation (soap_plan)
            $allMedicines = [];
            $generalInstructions = [];

            if ($consultation) {
                if (!empty($consultation['clinical_notes'])) {
                    $generalInstructions[] = $consultation['clinical_notes'];
                }
                if (!empty($consultation['soap_plan'])) {
                    $planData = json_decode($consultation['soap_plan'], true);
                    if (is_array($planData)) {
                        if (isset($planData['medications']) && is_array($planData['medications'])) {
                            $allMedicines = $planData['medications'];
                        } elseif (isset($planData[0])) {
                            $allMedicines = $planData;
                        }
                    }
                }
            }

            // 4. Get Lab Orders
            $labOrders = $this->db->fetchAll(
                "SELECT * FROM lab_orders WHERE patient_id = ? AND order_date = ?",
                [$appointment['patient_id'], $appointment['appointment_date']]
            );

            // 5. Get Invoices from opd_billing_master
            $invoices = $this->db->fetchAll(
                "SELECT bill_id as invoice_id, bill_id, patient_id, doctor_id, purpose as title, 
                        grand_total as amount, bill_date as date, payment_status as status, 
                        payment_mode as payment_method 
                 FROM opd_billing_master 
                 WHERE patient_id = ? AND (bill_date = ? OR DATE(created_at) = ?)",
                [$appointment['patient_id'], $appointment['appointment_date'], $appointment['appointment_date']]
            );

            $data = [
                'appointment' => $appointment,
                'consultation' => $consultation,
                'prescriptions' => $allMedicines,
                'general_instructions' => implode("\n---\n", $generalInstructions),
                'prescription_records' => $allMedicines,
                'lab_orders' => $labOrders,
                'invoices' => $invoices
            ];

            $this->respondSuccess($data);

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * POST /api/opd/vitals
     * Save or Update Vitals
     */
    public function saveVitals()
    {
        $this->restrictMethod('POST');
        $data = $this->getJsonInput(); // Validate required fields if necessary
        
        error_log("SAVE VITALS PAYLOAD: " . print_r($data, true));

        try {
            // Check if consultation exists for this appointment
            $exists = $this->db->fetchOne("SELECT consultation_id FROM consultations WHERE appointment_id = ?", [$data['appointment_id']]);

            $vitalsJson = json_encode([
                'bp' => $data['bp'] ?? '',
                'pulse' => $data['pulse'] ?? '',
                'temp' => $data['temp'] ?? '',
                'weight' => $data['weight'] ?? '',
                'spo2' => $data['spo2'] ?? ''
            ]);

            if ($exists) {
                // Update
                $this->db->update(
                    'consultations',
                    ['vital_signs' => $vitalsJson, 'complaint' => $data['chief_complaint'] ?? '', 'patient_id' => $data['patient_id']],
                    'consultation_id = ?',
                    [$exists['consultation_id']]
                );
            } else {
                // Create new consultation record just for vitals
                // Need to generate ID
                $consultationId = 'CONS-' . date('Ymd') . '-' . rand(100, 999);
                $this->db->insert('consultations', [
                    'consultation_id' => $consultationId,
                    'appointment_id' => $data['appointment_id'],
                    'patient_id' => $data['patient_id'],
                    'doctor_id' => $data['doctor_id'],
                    'consultation_date' => date('Y-m-d'),
                    'consultation_time' => date('H:i:s'),
                    'vital_signs' => $vitalsJson,
                    'complaint' => $data['chief_complaint'] ?? '',
                    'status' => 1
                ]);
            }

            // Update Appointment Status to 'In Progress' (1 means Active/In Progress)
            $this->db->update(
                'appointments',
                ['appointment_status' => 1],
                'appointment_id = ?',
                [$data['appointment_id']]
            );

            $this->respondSuccess(['message' => 'Vitals saved successfully']);

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * POST /api/opd/invoice
     * Create Invoice
     */
    public function createInvoice()
    {
        $this->restrictMethod('POST');
        $data = $this->getJsonInput();

        try {
            $billId = 'OPD-' . date('Ymd') . '-' . rand(1000, 9999);
            $amount = (float)($data['amount'] ?? 0);
            $status = ($data['status'] ?? 'Pending') === 'Paid' ? 'Paid' : 'Pending';
            $paidAmt = $status === 'Paid' ? $amount : 0;
            $dueAmt = $amount - $paidAmt;

            $insertData = [
                'bill_id' => $billId,
                'patient_id' => $data['patient_id'],
                'doctor_id' => $data['doctor_id'] ?? null,
                'appointment_id' => $data['appointment_id'] ?? null,
                'purpose' => $data['title'] ?? 'OPD Consultation',
                'subtotal' => $amount,
                'grand_total' => $amount,
                'amount_paid' => $paidAmt,
                'balance_due' => $dueAmt,
                'bill_date' => date('Y-m-d'),
                'payment_status' => $status,
                'payment_mode' => $data['payment_method'] ?? 'Cash'
            ];

            $this->db->insert('opd_billing_master', $insertData);
            $this->respondSuccess(['invoice_id' => $billId, 'message' => 'Invoice created']);

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * POST /api/opd/lab-request
     * Save Lab Request
     */
    public function saveLabRequest()
    {
        $this->restrictMethod('POST');
        $data = $this->getJsonInput();

        try {
            // Check for existing appointment/consultation linkage if needed
            // For now, insert directly

            $orderId = 'LAB-' . date('Ymd') . '-' . rand(1000, 9999);
            $insertData = [
                'order_id' => $orderId,
                'patient_id' => $data['patient_id'],
                'doctor_id' => $data['doctor_id'],
                'test_name' => $data['test_name'],
                'order_date' => date('Y-m-d'),
                'order_time' => date('H:i:s'),
                'status' => 'Ordered',
                'priority' => $data['priority'] ?? 'Routine',
                'notes' => $data['notes'] ?? ''
            ];

            $this->db->insert('lab_orders', $insertData);
            $this->respondSuccess(['order_id' => $orderId, 'message' => 'Lab request sent successfully']);

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * POST /api/opd/follow-up
     * Schedule Follow-up
     */
    public function saveFollowUp()
    {
        $this->restrictMethod('POST');
        $data = $this->getJsonInput();

        try {
            $today = date('Y-m-d');

            // Find existing consultation for this appointment or patient/doctor today
            $existingConsultation = null;
            if (!empty($data['appointment_id'])) {
                $existingConsultation = $this->db->fetchOne(
                    "SELECT consultation_id FROM consultations WHERE appointment_id = ?",
                    [$data['appointment_id']]
                );
            }
            if (!$existingConsultation) {
                $existingConsultation = $this->db->fetchOne(
                    "SELECT consultation_id FROM consultations WHERE patient_id = ? AND doctor_id = ? AND consultation_date = ?",
                    [$data['patient_id'], $data['doctor_id'], $today]
                );
            }

            // Combine notes: Clinical Notes + Follow-up Instructions
            $combinedNotes = "";
            if (!empty($data['clinical_notes'])) {
                $combinedNotes .= "Clinical Notes: " . $data['clinical_notes'] . "\n";
            }
            if (!empty($data['notes'])) {
                $combinedNotes .= "Instructions: " . $data['notes'];
            }
            $combinedNotes = trim($combinedNotes);

            $medicinesJson = json_encode([]);
            if (!empty($data['plan'])) {
                $medicinesJson = json_encode([
                    'plan' => $data['plan'],
                    'medications' => [
                        [
                            'name' => $data['plan'],
                            'dosage' => '-',
                            'frequency' => '-',
                            'duration' => '-',
                            'instructions' => 'See Plan'
                        ]
                    ]
                ]);
            }

            if ($existingConsultation) {
                $updateData = [
                    'follow_up_date' => $data['follow_up_date'] ?? null,
                    'clinical_notes' => $combinedNotes
                ];
                if (!empty($data['plan'])) {
                    $updateData['soap_plan'] = $medicinesJson;
                }

                $this->db->update(
                    'consultations',
                    $updateData,
                    'consultation_id = ?',
                    [$existingConsultation['consultation_id']]
                );
            } else {
                $consultId = 'CON-' . date('Ymd') . '-' . rand(100, 999);
                $insertData = [
                    'consultation_id' => $consultId,
                    'patient_id' => $data['patient_id'],
                    'doctor_id' => $data['doctor_id'],
                    'appointment_id' => $data['appointment_id'] ?? null,
                    'consultation_date' => $today,
                    'consultation_time' => date('H:i:s'),
                    'soap_plan' => $medicinesJson,
                    'clinical_notes' => $combinedNotes,
                    'follow_up_date' => $data['follow_up_date'] ?? null,
                    'status' => 'Completed'
                ];
                $this->db->insert('consultations', $insertData);
            }

            // Update Appointment Status to 'Completed'
            if (!empty($data['appointment_id'])) {
                $this->db->update(
                    'appointments',
                    ['appointment_status' => 'Completed'],
                    'appointment_id = ?',
                    [$data['appointment_id']]
                );
            }

            $this->respondSuccess(['message' => 'Follow-up and Plan saved successfully']);

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

   /**
     * GET /api/opd/reports
     * Comprehensive Reports
     */
    public function getOpdReports()
    {
        $this->restrictMethod('GET');

        try {
            $today = date('Y-m-d');
            $startOfMonth = date('Y-m-01');

            $reports = [];

            // 1. Daily Trend (Last 7 Days - OPD & IPD)
            $last7Days = [];
            for ($i = 6; $i >= 0; $i--) {
                $last7Days[] = date('Y-m-d', strtotime("-$i days"));
            }

            $opdDaily = $this->db->fetchAll(
                "SELECT DATE(appointment_date) as date, COUNT(*) as count 
                 FROM appointments 
                 WHERE appointment_date >= DATE_SUB(?, INTERVAL 6 DAY)
                 GROUP BY DATE(appointment_date)",
                [$today]
            );

            $ipdDaily = $this->db->fetchAll(
                "SELECT DATE(admission_date) as date, COUNT(*) as count 
                 FROM ipd_admissions 
                 WHERE admission_date >= DATE_SUB(?, INTERVAL 6 DAY)
                 GROUP BY DATE(admission_date)",
                [$today]
            );

            $dailyTrend = [];
            foreach ($last7Days as $day) {
                $opdCnt = 0;
                foreach ($opdDaily as $od) {
                    if ($od['date'] == $day) {
                        $opdCnt = (int)$od['count'];
                        break;
                    }
                }
                $ipdCnt = 0;
                foreach ($ipdDaily as $id) {
                    if ($id['date'] == $day) {
                        $ipdCnt = (int)$id['count'];
                        break;
                    }
                }
                $dailyTrend[] = [
                    'date' => $day,
                    'count' => $opdCnt + $ipdCnt, // total
                    'opd_count' => $opdCnt,
                    'ipd_count' => $ipdCnt
                ];
            }
            $reports['daily_trend'] = $dailyTrend;

            // 2. Doctor-wise Workload (Today - OPD & IPD)
            $doctorWiseSql = "SELECT 
                                d.doctor_id,
                                d.full_name,
                                d.specialization,
                                COALESCE(opd.opd_count, 0) as opd_count,
                                COALESCE(ipd.ipd_count, 0) as ipd_count,
                                (COALESCE(opd.opd_count, 0) + COALESCE(ipd.ipd_count, 0)) as count
                             FROM doctors d
                             LEFT JOIN (
                                 SELECT doctor_id, COUNT(*) as opd_count
                                 FROM appointments
                                 WHERE appointment_date = ?
                                 GROUP BY doctor_id
                             ) opd ON d.doctor_id = opd.doctor_id
                             LEFT JOIN (
                                 SELECT admitting_doctor_id as doctor_id, COUNT(*) as ipd_count
                                 FROM ipd_admissions
                                 WHERE DATE(admission_date) = ?
                                 GROUP BY admitting_doctor_id
                             ) ipd ON d.doctor_id = ipd.doctor_id
                             WHERE d.status = 'Active' AND (COALESCE(opd.opd_count, 0) + COALESCE(ipd.ipd_count, 0)) > 0
                             ORDER BY count DESC";
            $reports['doctor_wise'] = $this->db->fetchAll($doctorWiseSql, [$today, $today]);
            
            // If no active appointments today, return list of all active doctors
            if (empty($reports['doctor_wise'])) {
                $reports['doctor_wise'] = $this->db->fetchAll(
                    "SELECT doctor_id, full_name, specialization, 0 as opd_count, 0 as ipd_count, 0 as count
                     FROM doctors 
                     WHERE status = 'Active' 
                     ORDER BY full_name ASC LIMIT 10"
                );
            }

            // 3. Revenue Breakdown (Month to Date - OPD vs IPD vs Total)
            $opdRev = $this->db->fetchOne(
                "SELECT COALESCE(SUM(amount_paid), 0) as total, COUNT(bill_id) as count 
                 FROM opd_billing_master 
                 WHERE (bill_date >= ? OR created_at >= ?)",
                [$startOfMonth, $startOfMonth]
            );

            $ipdRevTotal = 0;
            $ipdRevCount = 0;
            try {
                $ipdRev = $this->db->fetchOne(
                    "SELECT COALESCE(SUM(CASE WHEN payment_type = 'REFUND' THEN -amount ELSE amount END), 0) as total, COUNT(payment_id) as count 
                     FROM ipd_payment 
                     WHERE COALESCE(payment_date, created_at) >= ?",
                    [$startOfMonth]
                );
                $ipdRevTotal = (float)($ipdRev['total'] ?? 0);
                $ipdRevCount = (int)($ipdRev['count'] ?? 0);
            } catch (Exception $e) {
                // fallback
            }

            $opdTotal = (float)($opdRev['total'] ?? 0);
            $opdCount = (int)($opdRev['count'] ?? 0);
            $totalRev = $opdTotal + $ipdRevTotal;
            $totalCount = $opdCount + $ipdRevCount;

            $reports['revenue'] = [
                'total' => $totalRev,
                'count' => $totalCount,
                'opd_total' => $opdTotal,
                'opd_count' => $opdCount,
                'ipd_total' => $ipdRevTotal,
                'ipd_count' => $ipdRevCount
            ];

            $this->respondSuccess($reports);

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/opd/stats
     * Dashboard Stats
     */
    public function getOpdStats()
    {
        try {
            $today = date('Y-m-d');

            $stats = [];

            // Total OPD Today
            $res = $this->db->fetchOne("SELECT count(*) as cnt FROM appointments WHERE appointment_date = CURDATE() AND (appointment_type = 'OPD' OR appointment_type IS NULL)", []);
            $stats['total_opd'] = (int)($res['cnt'] ?? 0);

            // Waiting in Queue Today
            $res = $this->db->fetchOne("SELECT count(*) as cnt FROM appointments WHERE appointment_date = CURDATE() AND (appointment_type = 'OPD' OR appointment_type IS NULL) AND (appointment_status = '1' OR appointment_status = 'Pending' OR appointment_status = 'Scheduled')", []);
            $stats['waiting_opd'] = (int)($res['cnt'] ?? 0);

            // Completed Today
            $res = $this->db->fetchOne("SELECT count(*) as cnt FROM appointments WHERE appointment_date = CURDATE() AND (appointment_type = 'OPD' OR appointment_type IS NULL) AND (appointment_status = '0' OR appointment_status = 'Completed')", []);
            $stats['completed_opd'] = (int)($res['cnt'] ?? 0);

            // Doctors Active
            $res = $this->db->fetchOne("SELECT count(*) as cnt FROM doctors WHERE status = 'Active'");
            $stats['active_doctors'] = (int)($res['cnt'] ?? 0);

            // Revenue Today (from opd_billing_master)
            $res = $this->db->fetchOne("SELECT COALESCE(SUM(amount_paid), 0) as total FROM opd_billing_master WHERE bill_date = ? OR DATE(created_at) = ?", [$today, $today]);
            $stats['revenue_today'] = $res['total'] ?? 0;

            $this->respondSuccess($stats);

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    /**
     * POST /api/opd/analyze-symptoms
     * Call Gemini AI for symptom analysis
     */
    public function analyzeSymptoms()
    {
        $this->restrictMethod('POST');

        try {
            require_once __DIR__ . '/../../config/gemini_config.php';

            $input = $this->getJsonInput();

            if (!isset($input['complaint']) || empty(trim($input['complaint']))) {
                $this->respondBadRequest('Patient complaint is required');
            }

            $complaint = trim($input['complaint']);
            $patientAge = $input['patient_age'] ?? 'Not specified';
            $patientGender = $input['patient_gender'] ?? 'Not specified';
            $patientAllergies = $input['patient_allergies'] ?? [];
            $allergyText = empty($patientAllergies) ? 'None reported' : implode(', ', $patientAllergies);

            // Build medical analysis prompt
            $prompt = "PATIENT INFORMATION:
- Chief Complaint: {$complaint}
- Age: {$patientAge} years
- Gender: {$patientGender}
- Known Allergies: {$allergyText}

Provide a comprehensive and professional treatment plan in structured JSON format. 
CRITICAL RULE: You MUST suggest AT LEAST FIVE (5) separate medications to ensure a complete clinical course. 

Use this structure for the \"medications\" array:
1. Primary Medication (e.g. Antibiotic/Antiviral)
2. Symptomatic Relief (e.g. Pain/Fever relief)
3. Supportive Care (e.g. ORS, Cough Syrup, or Multi-vitamins)
4. Gastric Protection (e.g. Pantoprazole or Antacid)
5. Pro-biotic or additional supplement

Each medication object MUST have ALL these keys (NO EXCEPTIONS):
   - \"name\": Generic medicine name (e.g. \"Paracetamol\")
   - \"dosage\": Strength (e.g. \"500mg\" or \"1 tablet\")
   - \"frequency\": How often per day (e.g. \"1-0-1\" means Morning-Afternoon-Night, \"OD\" means Once Daily, \"BD\" means Twice Daily)
   - \"timing\": MUST BE EXACTLY one of these: \"After Food\", \"Before Food\", \"With Food\", \"Empty Stomach\"
   - \"duration\": MANDATORY - How many days (e.g. \"5 Days\", \"7 Days\", \"10 Days\") - ALWAYS include the word \"Days\"
   - \"qty\": Total number of tablets (e.g. if OD for 5 days = 5, if 1-0-1 for 5 days = 15)
   - \"purpose\": Why prescribing (e.g. \"To reduce fever\")
   - \"warnings\": Safety note (e.g. \"Take full course\")

Remaining keys:
\"diagnosis\": Summary diagnosis
\"purpose_summary\": Why this regimen is chosen
\"safety_warnings\": Precautions for the patient
\"lifestyle\": Diet and rest advice

IMPORTANT: ALWAYS suggest 5+ medications. Return ONLY valid JSON. Check allergies: {$allergyText}.";

            // Call Gemini API
            $url = GEMINI_API_ENDPOINT . '?key=' . GEMINI_API_KEY;

            $requestBody = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1, // Lower temperature for more consistent JSON
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 4096,
                ]
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, GEMINI_TIMEOUT);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new Exception('cURL Error: ' . $error);
            }

            if ($httpCode !== 200) {
                error_log('Gemini API Error: HTTP ' . $httpCode . ' - ' . $response);
                throw new Exception('Gemini API returned HTTP ' . $httpCode);
            }

            $data = json_decode($response, true);

            if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                throw new Exception('Invalid API response structure');
            }

            $aiResponse = $data['candidates'][0]['content']['parts'][0]['text'];

            // Robust JSON extraction: Find the first '{' and final '}'
            $startPos = strpos($aiResponse, '{');
            $endPos = strrpos($aiResponse, '}');

            if ($startPos !== false && $endPos !== false) {
                $aiResponse = substr($aiResponse, $startPos, $endPos - $startPos + 1);
            }
            $aiResponse = trim($aiResponse);

            $this->respondSuccess([
                'treatment_plan' => $aiResponse,
                'generated_at' => date('Y-m-d H:i:s'),
                'ai_model' => GEMINI_MODEL
            ]);

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
