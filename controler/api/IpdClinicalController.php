<?php
/**
 * ============================================================
 * IpdClinicalController — API Reference
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api
 * Auth     : All endpoints require Auth (Session or Bearer token)
 * Tables   : ipd_doctor_visits, ipd_medications, ipd_investigations
 * Key Param: ipd_no (required for all GET endpoints)
 * ------------------------------------------------------------
 *
 * ── DOCTOR VISITS ──────────────────────────────────────────
 *
 * 1. GET /api/ipd-clinical/visits?ipd_no=IPD-001
 *    Required: ipd_no
 *    Response: [ { id, ipd_no, doctor_id, doctor_name, visit_date, clinical_notes, treatment_plan } ]
 *
 * 2. POST /api/ipd-clinical/visits      [Required: ipd_no, doctor_id, visit_date]
 *    Body:
 *      {
 *        "ipd_no":         "IPD-20260626-001",
 *        "doctor_id":      "DOC-001",
 *        "visit_date":     "2026-06-26",
 *        "clinical_notes": "Patient improving. BP stable.",
 *        "treatment_plan": "Continue current medications"
 *      }
 *
 * ── MEDICATIONS ────────────────────────────────────────────
 *
 * 3. GET /api/ipd-clinical/medications?ipd_no=IPD-001
 *    Required: ipd_no
 *    Response: [ { id, ipd_no, medicine_name, dosage, frequency, route, start_date, end_date } ]
 *
 * 4. POST /api/ipd-clinical/medications [Required: ipd_no, medicine_name]
 *    Body:
 *      {
 *        "ipd_no":        "IPD-20260626-001",
 *        "medicine_name": "Aspirin",
 *        "dosage":        "75mg",
 *        "frequency":     "Once Daily",
 *        "route":         "Oral",
 *        "start_date":    "2026-06-26",
 *        "end_date":      "2026-07-10",
 *        "instructions":  "After food"
 *      }
 *
 * ── INVESTIGATIONS ─────────────────────────────────────────
 *
 * 5. GET /api/ipd-clinical/investigations?ipd_no=IPD-001
 *    Required: ipd_no
 *    Response: [ { id, ipd_no, test_name, test_date, result_summary, doctor_remarks } ]
 *
 * 6. POST /api/ipd-clinical/investigations [Required: ipd_no, test_name]
 *    Body:
 *      {
 *        "ipd_no":          "IPD-20260626-001",
 *        "test_name":       "2D Echocardiography",
 *        "test_date":       "2026-06-26",
 *        "result_summary":  "EF 55%, No regional wall motion abnormality",
 *        "doctor_remarks":  "Normal study"
 *      }
 * ------------------------------------------------------------
 */

namespace GM_HMS\Controllers\api;

require_once __DIR__ . '/../BaseController.php';

use GM_HMS\Controllers\BaseController;
use Exception;

class IpdClinicalController extends BaseController {
    
    public function __construct() {
        parent::__construct();
    }

    // --- DOCTOR VISITS ---

    /**
     * GET /api/ipd-clinical/visits
     */
    public function getVisits() {
        $this->restrictMethod('GET');
        $this->requireAuth();
        
        try {
            $ipdNo = $_GET['ipd_no'] ?? null;
            if (!$ipdNo) $this->respondBadRequest('IPD Number is required');
            
            $visits = $this->db->fetchAll(
                "SELECT v.*, d.full_name as doctor_name 
                 FROM ipd_doctor_visits v
                 LEFT JOIN doctors d ON v.doctor_id = d.doctor_id
                 WHERE v.ipd_no = ? ORDER BY v.visit_date DESC",
                [$ipdNo]
            );
            
            $this->respondSuccess($visits);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * POST /api/ipd-clinical/visits
     */
    public function addVisit() {
        $this->restrictMethod('POST');
        $this->requireAuth();
        
        $data = $this->getJsonInput();
        
        try {
            $required = ['ipd_no', 'doctor_id', 'visit_date'];
            foreach ($required as $field) {
                if (!isset($data[$field])) $this->respondBadRequest("$field is required");
            }

            $insertData = [
                'ipd_no' => $data['ipd_no'],
                'doctor_id' => $data['doctor_id'],
                'visit_date' => $data['visit_date'],
                'clinical_notes' => $data['clinical_notes'] ?? null,
                'treatment_plan' => $data['treatment_plan'] ?? null
            ];

            $this->db->insert('ipd_doctor_visits', $insertData);
            $this->respondSuccess(null, 'Doctor visit recorded successfully');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    // --- MEDICATIONS ---

    /**
     * GET /api/ipd-clinical/medications
     */
    public function getMedications() {
        $this->restrictMethod('GET');
        $this->requireAuth();
        
        try {
            $ipdNo = $_GET['ipd_no'] ?? null;
            if (!$ipdNo) $this->respondBadRequest('IPD Number is required');
            
            $meds = $this->db->fetchAll("SELECT * FROM ipd_medications WHERE ipd_no = ? ORDER BY id DESC", [$ipdNo]);
            $this->respondSuccess($meds);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * POST /api/ipd-clinical/medications
     */
    public function addMedication() {
        $this->restrictMethod('POST');
        $this->requireAuth();
        
        $data = $this->getJsonInput();
        
        try {
            if (!isset($data['ipd_no']) || !isset($data['medicine_name'])) {
                $this->respondBadRequest('IPD Number and Medicine Name are required');
            }

            $insertData = [
                'ipd_no' => $data['ipd_no'],
                'medicine_name' => $data['medicine_name'],
                'dosage' => $data['dosage'] ?? null,
                'frequency' => $data['frequency'] ?? null,
                'route' => $data['route'] ?? null,
                'start_date' => $data['start_date'] ?? date('Y-m-d'),
                'end_date' => $data['end_date'] ?? null,
                'instructions' => $data['instructions'] ?? null
            ];

            $this->db->insert('ipd_medications', $insertData);
            $this->respondSuccess(null, 'Medication added successfully');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    // --- INVESTIGATIONS ---

    /**
     * GET /api/ipd-clinical/investigations
     */
    public function getInvestigations() {
        $this->restrictMethod('GET');
        $this->requireAuth();
        
        try {
            $ipdNo = $_GET['ipd_no'] ?? null;
            if (!$ipdNo) $this->respondBadRequest('IPD Number is required');
            
            $invs = $this->db->fetchAll("SELECT * FROM ipd_investigations WHERE ipd_no = ? ORDER BY test_date DESC", [$ipdNo]);
            $this->respondSuccess($invs);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * POST /api/ipd-clinical/investigations
     */
    public function addInvestigation() {
        $this->restrictMethod('POST');
        $this->requireAuth();
        
        $data = $this->getJsonInput();
        
        try {
            if (!isset($data['ipd_no']) || !isset($data['test_name'])) {
                $this->respondBadRequest('IPD Number and Test Name are required');
            }

            $insertData = [
                'ipd_no' => $data['ipd_no'],
                'test_name' => $data['test_name'],
                'test_date' => $data['test_date'] ?? date('Y-m-d'),
                'result_summary' => $data['result_summary'] ?? null,
                'doctor_remarks' => $data['doctor_remarks'] ?? null
            ];

            $this->db->insert('ipd_investigations', $insertData);
            $this->respondSuccess(null, 'Investigation recorded successfully');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
