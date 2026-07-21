<?php
/**
 * ============================================================
 * IpdSummaryController — API Reference
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api
 * Auth     : All endpoints require Auth (Session or Bearer token)
 * ------------------------------------------------------------
 *
 * 1. GET /api/ipd-summary
 *    Query: patient_id, admission_id (optional filters)
 *    Response: List of IPD summaries
 *
 * 2. GET /api/ipd-summary/draft
 *    Returns draft/in-progress summary for current active IPD patient
 *
 * 3. POST /api/ipd-summary
 *    Body:
 *      {
 *        "patient_id":            "PID-20260626-001",
 *        "admission_id":          "ADM-20260620-001",
 *        "chief_complaint":       "Chest pain",
 *        "history_of_illness":    "Sudden onset chest pain...",
 *        "diagnosis":             "Acute MI",
 *        "treatment":             "Aspirin 75mg, Heparin drip",
 *        "operative_procedure":   null,
 *        "condition_at_discharge":"Stable",
 *        "discharge_date":        "2026-06-30",
 *        "discharge_instructions":"Follow up in 1 week. Avoid exertion.",
 *        "follow_up_date":        "2026-07-07",
 *        "status":                "Final"
 *      }
 *
 * 4. DELETE /api/ipd-summary
 *    Body: { "summary_id": 12 }
 * ------------------------------------------------------------
 */
/**
 * IPD Summary API Controller
 * 
 * Professional IPD Daily Report Management System
 * Handles daily clinical reports with JSON-based historical tracking
 * 
 * Features:
 * - Single row per IPD admission
 * - JSON-encoded daily reports
 * - Doctor visits and nurse procedures
 * - Discharge summary management
 * - Comprehensive validation and error handling
 * 
 * @package GM_HMS\Controllers\API
 * @version 1.0.0
 * @author GM HMS Development Team
 */

namespace GM_HMS\Controllers\api;

require_once __DIR__ . '/../BaseController.php';

use GM_HMS\Controllers\BaseController;
use Exception;

class IpdSummaryController extends BaseController {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
    }


    // ========================================================================
    // IPD ADMISSIONS LOOKUP
    // ========================================================================

    /**
     * GET /api/ipd-summary/admissions
     * 
     * Get list of active IPD admissions for dropdown
     * 
     * Query Parameters:
     * - status (optional): Filter by status (default: 'Admitted')
     * - search (optional): Search by admission_id or patient_id
     * 
     * Response:
     * {
     *   "success": true,
     *   "data": [
     *     {
     *       "admission_id": "ADM001",
     *       "patient_id": "P001",
     *       "patient_name": "John Doe",
     *       "ward_name": "General Ward",
     *       "room_no": "101",
     *       "bed_id": "1",
     *       "admission_date": "2026-02-01",
     *       "status": "Admitted"
     *     }
     *   ]
     * }
     */
    public function getActiveAdmissions() {
        $this->restrictMethod('GET');
        $this->requireAuth();
        
        try {
            $status = $_GET['status'] ?? 'Admitted';
            $search = $_GET['search'] ?? '';
            
            // Build query
            $query = "SELECT 
                        a.admission_id,
                        a.patient_id,
                        CONCAT(p.first_name, ' ', COALESCE(p.last_name, '')) as patient_name,
                        p.phone as patient_phone,
                        a.admitting_doctor_id,
                        d.full_name as doctor_name,
                        a.ward_name,
                        a.room_no,
                        a.room_name,
                        a.bed_id,
                        a.admission_date,
                        a.admission_time,
                        a.discharge_date,
                        a.diagnosis,
                        a.status
                      FROM ipd_admissions a
                      LEFT JOIN patient p ON a.patient_id = p.patient_id
                      LEFT JOIN doctors d ON a.admitting_doctor_id = d.doctor_id
                      WHERE 1=1";
            
            $params = [];
            
            // Filter by status
            if ($status) {
                $query .= " AND a.status = ?";
                $params[] = $status;
            }
            
            // Search filter
            if ($search) {
                $query .= " AND (a.admission_id LIKE ? OR a.patient_id LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ? OR p.phone LIKE ?)";
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $query .= " ORDER BY a.admission_date DESC, a.admission_time DESC LIMIT 100";
            
            $admissions = $this->db->fetchAll($query, $params);
            
            // Format response
            $formatted = array_map(function($admission) {
                $phoneDisplay = !empty($admission['patient_phone']) ? ' [' . $admission['patient_phone'] . ']' : '';
                return [
                    'admission_id' => $admission['admission_id'],
                    'patient_id' => $admission['patient_id'],
                    'patient_name' => $admission['patient_name'] ?? 'Unknown',
                    'patient_phone' => $admission['patient_phone'],
                    'doctor_id' => $admission['admitting_doctor_id'],
                    'doctor_name' => $admission['doctor_name'] ?? 'Unknown',
                    'ward_name' => $admission['ward_name'],
                    'room_no' => $admission['room_no'],
                    'room_name' => $admission['room_name'],
                    'bed_id' => $admission['bed_id'],
                    'admission_date' => $admission['admission_date'],
                    'admission_time' => $admission['admission_time'],
                    'discharge_date' => $admission['discharge_date'],
                    'diagnosis' => $admission['diagnosis'],
                    'status' => $admission['status'],
                    'display_text' => $admission['admission_id'] . ' - ' . ($admission['patient_name'] ?? 'Unknown') . $phoneDisplay . ' (' . $admission['ward_name'] . ' - ' . $admission['room_no'] . ')'
                ];
            }, $admissions);
            
            $this->respondSuccess($formatted, 'Active admissions retrieved successfully');
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    // ========================================================================
    // DAILY REPORTS MANAGEMENT
    // ========================================================================

    /**
     * GET /api/ipd-summary/daily-reports
     * 
     * Retrieve all daily reports for a specific IPD admission
     * 
     * Query Parameters:
     * - ipd_no (required): IPD admission number
     * - date (optional): Filter by specific date (YYYY-MM-DD)
     * 
     * Response:
     * {
     *   "success": true,
     *   "status": "success",
     *   "data": {
     *     "ipd_no": "IPD001",
     *     "patient_id": "P001",
     *     "admission_date": "2026-02-01 10:30:00",
     *     "daily_reports": [...],
     *     "total_days": 10
     *   }
     * }
     */
    public function getDailyReports() {
        $this->restrictMethod('GET');
        $this->requireAuth();
        
        try {
            $ipdNo = $_GET['ipd_no'] ?? null;
            if (!$ipdNo) {
                $this->respondBadRequest('IPD Number is required');
            }
            
            // Try to fetch from ipd_summary first
            $summary = $this->db->fetchOne(
                "SELECT id, ipd_no, patient_id, doctor_id, admission_date, discharge_date,
                        ward, room_no, bed_no, department, provisional_diagnosis, 
                        final_diagnosis, patient_condition, daily_reports, discharge_summary
                 FROM ipd_summary 
                 WHERE ipd_no = ?",
                [$ipdNo]
            );
            
            if (!$summary) {
                // Not in summary table yet, check if it's a valid admission
                $admission = $this->db->fetchOne(
                    "SELECT 
                        a.admission_id as ipd_no, 
                        a.patient_id, 
                        a.admitting_doctor_id as doctor_id, 
                        a.admission_date,
                        a.ward_name as ward,
                        a.room_no,
                        a.bed_id as bed_no,
                        a.diagnosis as provisional_diagnosis,
                        a.status
                     FROM ipd_admissions a
                     WHERE a.admission_id = ?",
                    [$ipdNo]
                );
                
                if (!$admission) {
                    $this->respondNotFound('Admission record not found');
                }
                
                // Construct default response from admission data
                $response = [
                    'ipd_no' => $admission['ipd_no'],
                    'patient_id' => $admission['patient_id'],
                    'doctor_id' => $admission['doctor_id'],
                    'admission_date' => $admission['admission_date'],
                    'discharge_date' => null,
                    'ward' => $admission['ward'],
                    'room_no' => $admission['room_no'],
                    'bed_no' => $admission['bed_no'],
                    'department' => null,
                    'provisional_diagnosis' => $admission['provisional_diagnosis'],
                    'patient_condition' => 'Stable',
                    'daily_reports' => [],
                    'total_days' => 0,
                    'is_discharged' => ($admission['status'] === 'Discharged')
                ];
            } else {
                // Record exists in summary table
                $dailyReports = [];
                if (!empty($summary['daily_reports'])) {
                    $decoded = json_decode($summary['daily_reports'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $dailyReports = $decoded;
                    }
                }
                
                $response = [
                    'ipd_no' => $summary['ipd_no'],
                    'patient_id' => $summary['patient_id'],
                    'doctor_id' => $summary['doctor_id'],
                    'admission_date' => $summary['admission_date'],
                    'discharge_date' => $summary['discharge_date'],
                    'ward' => $summary['ward'],
                    'room_no' => $summary['room_no'],
                    'bed_no' => $summary['bed_no'],
                    'department' => $summary['department'],
                    'provisional_diagnosis' => $summary['provisional_diagnosis'],
                    'patient_condition' => $summary['patient_condition'],
                    'daily_reports' => $dailyReports,
                    'total_days' => count($dailyReports),
                    'is_discharged' => !empty($summary['discharge_date'])
                ];
            }
            
            $this->respondSuccess($response, 'Daily reports retrieved successfully');
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * POST /api/ipd-summary/daily-reports
     * 
     * Add a new daily report entry
     * 
     * Request Body:
     * {
     *   "ipd_no": "IPD001",
     *   "patient_id": "P001",
     *   "doctor_id": "DOC001",
     *   "date": "2026-02-11",
     *   "doctor_visit": {
     *     "doctor_id": "DOC001",
     *     "doctor_name": "Dr. Smith",
     *     "visit_time": "10:30",
     *     "summary": "Patient showing improvement"
     *   },
     *   "medical_changes": "Increased dosage of medication X",
     *   "observations": "Vital signs stable",
     *   "nurse_procedures": [
     *     {"time": "08:00", "procedure": "Blood pressure check"},
     *     {"time": "14:00", "procedure": "Temperature monitoring"}
     *   ],
     *   "extra_data": {
     *     "entered_by": "Dr. Smith",
     *     "notes": "Continue monitoring"
     *   }
     * }
     */
    public function addDailyReport() {
        $this->restrictMethod('POST');
        $this->requireAuth();
        
        $data = $this->getJsonInput();
        
        try {
            // Validate required fields
            $required = ['ipd_no', 'patient_id', 'doctor_id', 'date'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $this->respondBadRequest("Field '$field' is required");
                }
            }
            
            // Validate date format
            if (!$this->isValidDate($data['date'])) {
                $this->respondBadRequest('Invalid date format. Use YYYY-MM-DD');
            }
            
            $ipdNo = $data['ipd_no'];
            $reportDate = $data['date'];
            
            // Check if IPD summary exists
            $existing = $this->db->fetchOne(
                "SELECT id, daily_reports, discharge_date FROM ipd_summary WHERE ipd_no = ?",
                [$ipdNo]
            );
            
            // Block if already discharged
            if ($existing && !empty($existing['discharge_date'])) {
                $this->respondBadRequest('Cannot add daily report. Patient has been discharged on ' . 
                    date('d-M-Y', strtotime($existing['discharge_date'])));
            }
            
            // Prepare new daily report entry
            $newReport = [
                'date' => $reportDate,
                'doctor_visit' => $data['doctor_visit'] ?? null,
                'medical_changes' => $data['medical_changes'] ?? '',
                'observations' => $data['observations'] ?? '',
                'nurse_procedures' => $data['nurse_procedures'] ?? [],
                'extra_data' => array_merge(
                    $data['extra_data'] ?? [],
                    [
                        'created_at' => date('Y-m-d H:i:s'),
                        'created_by' => $this->currentUser['full_name'] ?? 'System'
                    ]
                )
            ];
            
            if ($existing) {
                // Update existing record
                $dailyReports = [];
                if (!empty($existing['daily_reports'])) {
                    $decoded = json_decode($existing['daily_reports'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $dailyReports = $decoded;
                    }
                }
                
                // Append new report
                $dailyReports[] = $newReport;
                
                // Sort by date (newest first) and then by time (newest first)
                usort($dailyReports, function($a, $b) {
                    $dateCompare = strcmp($b['date'], $a['date']);
                    if ($dateCompare === 0) {
                        $timeA = $a['doctor_visit']['visit_time'] ?? '00:00';
                        $timeB = $b['doctor_visit']['visit_time'] ?? '00:00';
                        return strcmp($timeB, $timeA);
                    }
                    return $dateCompare;
                });
                
                // Update database
                $this->db->update(
                    'ipd_summary',
                    ['daily_reports' => json_encode($dailyReports, JSON_UNESCAPED_UNICODE)],
                    'ipd_no = ?',
                    [$ipdNo]
                );
                
                $this->respondSuccess([
                    'ipd_no' => $ipdNo,
                    'report_date' => $reportDate,
                    'total_reports' => count($dailyReports)
                ], 'Daily report added successfully');
                
            } else {
                // Create new IPD summary record
                $insertData = [
                    'ipd_no' => $ipdNo,
                    'patient_id' => $data['patient_id'],
                    'doctor_id' => $data['doctor_id'],
                    'admission_date' => $data['admission_date'] ?? date('Y-m-d H:i:s'),
                    'ward' => $data['ward'] ?? null,
                    'room_no' => $data['room_no'] ?? null,
                    'bed_no' => $data['bed_no'] ?? null,
                    'department' => $data['department'] ?? null,
                    'provisional_diagnosis' => $data['provisional_diagnosis'] ?? null,
                    'patient_condition' => $data['patient_condition'] ?? 'Stable',
                    'daily_reports' => json_encode([$newReport], JSON_UNESCAPED_UNICODE)
                ];
                
                $this->db->insert('ipd_summary', $insertData);
                
                $this->respondCreated([
                    'ipd_no' => $ipdNo,
                    'report_date' => $reportDate,
                    'message' => 'IPD summary created with first daily report'
                ]);
            }
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * PUT /api/ipd-summary/daily-reports
     * 
     * Update an existing daily report
     * 
     * Request Body:
     * {
     *   "ipd_no": "IPD001",
     *   "date": "2026-02-11",
     *   "doctor_visit": {...},
     *   "medical_changes": "...",
     *   "observations": "...",
     *   "nurse_procedures": [...]
     * }
     */
    public function updateDailyReport() {
        $this->restrictMethod('PUT');
        $this->requireAuth();
        
        $data = $this->getJsonInput();
        
        try {
            // Validate required fields
            if (!isset($data['ipd_no']) || !isset($data['date'])) {
                $this->respondBadRequest('IPD Number and date are required');
            }
            
            $ipdNo = $data['ipd_no'];
            $reportDate = $data['date'];
            
            // Fetch existing record
            $existing = $this->db->fetchOne(
                "SELECT id, daily_reports, discharge_date FROM ipd_summary WHERE ipd_no = ?",
                [$ipdNo]
            );
            
            if (!$existing) {
                $this->respondNotFound('IPD admission not found');
            }
            
            // Block if discharged
            if (!empty($existing['discharge_date'])) {
                $this->respondBadRequest('Cannot update daily report. Patient has been discharged.');
            }
            
            // Decode daily reports
            $dailyReports = [];
            if (!empty($existing['daily_reports'])) {
                $decoded = json_decode($existing['daily_reports'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $dailyReports = $decoded;
                }
            }
            
            // Find and update the specific date
            $found = false;
            foreach ($dailyReports as $index => $report) {
                if (isset($report['date']) && $report['date'] === $reportDate) {
                    // Merge updates
                    $dailyReports[$index] = array_merge($report, [
                        'doctor_visit' => $data['doctor_visit'] ?? $report['doctor_visit'] ?? null,
                        'medical_changes' => $data['medical_changes'] ?? $report['medical_changes'] ?? '',
                        'observations' => $data['observations'] ?? $report['observations'] ?? '',
                        'nurse_procedures' => $data['nurse_procedures'] ?? $report['nurse_procedures'] ?? [],
                        'extra_data' => array_merge(
                            $report['extra_data'] ?? [],
                            $data['extra_data'] ?? [],
                            [
                                'updated_at' => date('Y-m-d H:i:s'),
                                'updated_by' => $this->currentUser['full_name'] ?? 'System'
                            ]
                        )
                    ]);
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $this->respondNotFound("Daily report for date '$reportDate' not found");
            }
            
            // Update database
            $this->db->update(
                'ipd_summary',
                ['daily_reports' => json_encode($dailyReports, JSON_UNESCAPED_UNICODE)],
                'ipd_no = ?',
                [$ipdNo]
            );
            
            $this->respondSuccess([
                'ipd_no' => $ipdNo,
                'report_date' => $reportDate
            ], 'Daily report updated successfully');
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * DELETE /api/ipd-summary/daily-reports
     * 
     * Delete a specific daily report
     * 
     * Query Parameters:
     * - ipd_no (required)
     * - date (required)
     */
    public function deleteDailyReport() {
        $this->restrictMethod('DELETE');
        $this->requireAuth();
        
        try {
            $ipdNo = $_GET['ipd_no'] ?? null;
            $reportDate = $_GET['date'] ?? null;
            
            if (!$ipdNo || !$reportDate) {
                $this->respondBadRequest('IPD Number and date are required');
            }
            
            // Fetch existing record
            $existing = $this->db->fetchOne(
                "SELECT id, daily_reports, discharge_date FROM ipd_summary WHERE ipd_no = ?",
                [$ipdNo]
            );
            
            if (!$existing) {
                $this->respondNotFound('IPD admission not found');
            }
            
            // Block if discharged
            if (!empty($existing['discharge_date'])) {
                $this->respondBadRequest('Cannot delete daily report. Patient has been discharged.');
            }
            
            // Decode and filter
            $dailyReports = [];
            if (!empty($existing['daily_reports'])) {
                $decoded = json_decode($existing['daily_reports'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $dailyReports = $decoded;
                }
            }
            
            $originalCount = count($dailyReports);
            $dailyReports = array_filter($dailyReports, function($report) use ($reportDate) {
                return !isset($report['date']) || $report['date'] !== $reportDate;
            });
            $dailyReports = array_values($dailyReports); // Re-index
            
            if (count($dailyReports) === $originalCount) {
                $this->respondNotFound("Daily report for date '$reportDate' not found");
            }
            
            // Update database
            $this->db->update(
                'ipd_summary',
                ['daily_reports' => json_encode($dailyReports, JSON_UNESCAPED_UNICODE)],
                'ipd_no = ?',
                [$ipdNo]
            );
            
            $this->respondSuccess([
                'ipd_no' => $ipdNo,
                'deleted_date' => $reportDate,
                'remaining_reports' => count($dailyReports)
            ], 'Daily report deleted successfully');
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    // ========================================================================
    // DISCHARGE SUMMARY MANAGEMENT
    // ========================================================================

    /**
     * GET /api/ipd-summary/discharge
     * 
     * Get discharge summary for an IPD admission
     */
    public function getDischargeSummary() {
        $this->restrictMethod('GET');
        $this->requireAuth();
        
        try {
            $ipdNo = $_GET['ipd_no'] ?? null;
            if (!$ipdNo) {
                $this->respondBadRequest('IPD Number is required');
            }
            
            $summary = $this->db->fetchOne(
                "SELECT ipd_no, patient_id, discharge_date, final_diagnosis, 
                        discharge_summary, patient_condition
                 FROM ipd_summary 
                 WHERE ipd_no = ?",
                [$ipdNo]
            );
            
            if (!$summary) {
                $this->respondNotFound('IPD admission not found');
            }
            
            // Decode discharge summary
            $dischargeSummary = null;
            if (!empty($summary['discharge_summary'])) {
                $decoded = json_decode($summary['discharge_summary'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $dischargeSummary = $decoded;
                }
            }
            
            $response = [
                'ipd_no' => $summary['ipd_no'],
                'patient_id' => $summary['patient_id'],
                'discharge_date' => $summary['discharge_date'],
                'final_diagnosis' => $summary['final_diagnosis'],
                'patient_condition' => $summary['patient_condition'],
                'discharge_summary' => $dischargeSummary,
                'is_discharged' => !empty($summary['discharge_date'])
            ];
            
            $this->respondSuccess($response, 'Discharge summary retrieved successfully');
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * POST /api/ipd-summary/discharge
     * 
     * Create or update discharge summary
     * 
     * Request Body:
     * {
     *   "ipd_no": "IPD001",
     *   "discharge_date": "2026-02-15 14:30:00",
     *   "final_diagnosis": "Complete recovery",
     *   "discharge_summary": {
     *     "treatment_summary": "...",
     *     "medications_on_discharge": [...],
     *     "follow_up_instructions": "...",
     *     "diet_instructions": "...",
     *     "activity_restrictions": "...",
     *     "next_visit_date": "2026-03-01"
     *   }
     * }
     */
    public function updateDischargeSummary() {
        $this->restrictMethod(['POST', 'PUT']);
        $this->requireAuth();
        
        $data = $this->getJsonInput();
        
        try {
            if (!isset($data['ipd_no'])) {
                $this->respondBadRequest('IPD Number is required');
            }
            
            $ipdNo = $data['ipd_no'];
            
            // Check if exists
            $existing = $this->db->fetchOne(
                "SELECT id, discharge_date FROM ipd_summary WHERE ipd_no = ?",
                [$ipdNo]
            );
            
            if (!$existing) {
                $this->respondNotFound('IPD admission not found');
            }
            
            // Prepare update data
            $updateData = [];
            
            if (isset($data['discharge_date'])) {
                $updateData['discharge_date'] = $data['discharge_date'];
            }
            
            if (isset($data['final_diagnosis'])) {
                $updateData['final_diagnosis'] = $data['final_diagnosis'];
            }
            
            if (isset($data['patient_condition'])) {
                $updateData['patient_condition'] = $data['patient_condition'];
            }
            
            if (isset($data['discharge_summary'])) {
                $dischargeSummary = array_merge(
                    $data['discharge_summary'],
                    [
                        'created_at' => date('Y-m-d H:i:s'),
                        'created_by' => $this->currentUser['full_name'] ?? 'System'
                    ]
                );
                $updateData['discharge_summary'] = json_encode($dischargeSummary, JSON_UNESCAPED_UNICODE);
            }
            
            if (empty($updateData)) {
                $this->respondBadRequest('No data provided for update');
            }
            
            // Update database
            $this->db->update('ipd_summary', $updateData, 'ipd_no = ?', [$ipdNo]);
            
            $this->respondSuccess([
                'ipd_no' => $ipdNo,
                'discharge_date' => $updateData['discharge_date'] ?? $existing['discharge_date']
            ], 'Discharge summary updated successfully');
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    // ========================================================================
    // IPD SUMMARY MANAGEMENT
    // ========================================================================

    /**
     * GET /api/ipd-summary
     * 
     * Get complete IPD summary
     */
    public function getIpdSummary() {
        $this->restrictMethod('GET');
        $this->requireAuth();
        
        try {
            $ipdNo = $_GET['ipd_no'] ?? null;
            if (!$ipdNo) {
                $this->respondBadRequest('IPD Number is required');
            }
            
            $summary = $this->db->fetchOne(
                "SELECT * FROM ipd_summary WHERE ipd_no = ?",
                [$ipdNo]
            );
            
            if (!$summary) {
                $this->respondNotFound('IPD admission not found');
            }
            
            // Decode JSON fields
            if (!empty($summary['daily_reports'])) {
                $decoded = json_decode($summary['daily_reports'], true);
                $summary['daily_reports'] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : [];
            } else {
                $summary['daily_reports'] = [];
            }
            
            if (!empty($summary['discharge_summary'])) {
                $decoded = json_decode($summary['discharge_summary'], true);
                $summary['discharge_summary'] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
            } else {
                $summary['discharge_summary'] = null;
            }
            
            $summary['total_days'] = count($summary['daily_reports']);
            $summary['is_discharged'] = !empty($summary['discharge_date']);
            
            $this->respondSuccess($summary, 'IPD summary retrieved successfully');
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * PUT /api/ipd-summary
     * 
     * Update IPD summary basic information
     */
    public function updateIpdSummary() {
        $this->restrictMethod('PUT');
        $this->requireAuth();
        
        $data = $this->getJsonInput();
        
        try {
            if (!isset($data['ipd_no'])) {
                $this->respondBadRequest('IPD Number is required');
            }
            
            $ipdNo = $data['ipd_no'];
            
            // Check if exists
            $existing = $this->db->fetchOne(
                "SELECT id FROM ipd_summary WHERE ipd_no = ?",
                [$ipdNo]
            );
            
            if (!$existing) {
                $this->respondNotFound('IPD admission not found');
            }
            
            // Prepare update data (only allowed fields)
            $allowedFields = [
                'ward', 'room_no', 'bed_no', 'department',
                'provisional_diagnosis', 'patient_condition'
            ];
            
            $updateData = [];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            if (empty($updateData)) {
                $this->respondBadRequest('No valid fields provided for update');
            }
            
            // Update database
            $this->db->update('ipd_summary', $updateData, 'ipd_no = ?', [$ipdNo]);
            
            $this->respondSuccess([
                'ipd_no' => $ipdNo,
                'updated_fields' => array_keys($updateData)
            ], 'IPD summary updated successfully');
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    // ========================================================================
    // HELPER METHODS
    // ========================================================================

    /**
     * Validate date format (YYYY-MM-DD)
     * 
     * @param string $date Date string
     * @return bool
     */
    private function isValidDate($date) {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}

