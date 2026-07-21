<?php
/**
 * ============================================================
 * ConsultationController — API Reference
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api
 * Auth     : All endpoints require Auth (Session or Bearer token)
 * Table    : consultations
 * ------------------------------------------------------------
 *
 * 1. GET /api/consultations
 *    Query: doctor_id, patient_id, date (optional filters)
 *    Response: All consultations matching filters
 *
 * 2. GET /api/consultations/{id}
 *    Example: GET /api/consultations/CON-20260626-1234
 *    Response: Full consultation with SOAP notes, vitals, diagnosis
 *
 * 3. POST /api/consultations
 *    Body:
 *      {
 *        "patient_id":         "PID-20260626-001",
 *        "doctor_id":          "DOC-001",
 *        "appointment_id":     "APT-20260626-0001",
 *        "consultation_date":  "2026-06-26",
 *        "chief_complaint":    "Cough and cold",
 *        "soap_subjective":    "3 days of cough",
 *        "soap_objective":     "Temp 99F, throat inflamed",
 *        "soap_assessment":    "Acute pharyngitis",
 *        "soap_plan":          "{\"medications\":[...]}",
 *        "vital_signs":        "{\"bp\":\"120/80\",\"pulse\":\"78\"}",
 *        "final_diagnosis":    "Viral pharyngitis",
 *        "follow_up_date":     "2026-07-05",
 *        "status":             "Completed"
 *      }
 *
 * 4. PUT /api/consultations/{id}
 *    Body: Same as POST — send only changed fields (partial update supported)
 *
 * 5. DELETE /api/consultations/{id}
 *
 * 6. POST /api/consultations/translate-audio    [AI-powered]
 *    Body: { "audio_data": "base64_encoded...", "language": "en" }
 *    Returns transcribed text for SOAP notes
 * ------------------------------------------------------------
 */
/**
 * Consultation API Controller
 * 
 * Handles SOAP notes, consultation history, and clinical documentation
 * Database: hmsci, Table: consultations
 * 
 * @package GM_HMS\Controllers\API
 * @version 1.0.0
 */

namespace GM_HMS\Controllers\api;

require_once __DIR__ . '/../BaseController.php';

use GM_HMS\Controllers\BaseController;
use Exception;

class ConsultationController extends BaseController {
    
    public function __construct() {
        parent::__construct();
    }
    
    private function resolveServiceNames($idsString) {
        if (empty($idsString)) return '';
        $ids = array_filter(array_map('trim', explode(',', $idsString)));
        $names = [];
        foreach ($ids as $id) {
            if (strpos($id, 'LAB') === 0) {
                $row = $this->db->fetchOne("SELECT test_name FROM lab_services WHERE service_id = ?", [$id]);
                $names[] = $row ? $row['test_name'] : $id;
            } elseif (strpos($id, 'RDS') === 0) {
                $row = $this->db->fetchOne("SELECT billing_name FROM radiology_services WHERE service_id = ?", [$id]);
                $names[] = $row ? $row['billing_name'] : $id;
            } elseif (strpos($id, 'OTH') === 0) {
                $row = $this->db->fetchOne("SELECT billing_name FROM other_services WHERE service_id = ?", [$id]);
                $names[] = $row ? $row['billing_name'] : $id;
            } else {
                $names[] = $id;
            }
        }
        return implode(', ', $names);
    }
    
    /**
     * GET /api/consultations
     * Get all consultations
     */
    public function index() {
        $this->restrictMethod('GET');
        
        try {
            // Get query parameters
            $patientId = $_GET['patient_id'] ?? null;
            $doctorId = $_GET['doctor_id'] ?? null;
            $date = $_GET['date'] ?? null;
            $limit = $_GET['limit'] ?? 50;
            
            $query = 'SELECT c.*, d.full_name as doctor_name 
                      FROM consultations c 
                      LEFT JOIN doctors d ON c.doctor_id = d.doctor_id 
                      WHERE 1=1';
            $params = [];
            
            if ($patientId) {
                $query .= ' AND c.patient_id = ?';
                $params[] = $patientId;
            }
            
            if ($doctorId) {
                $query .= ' AND c.doctor_id = ?';
                $params[] = $doctorId;
            }
            
            if ($date) {
                $query .= ' AND DATE(c.consultation_date) = ?';
                $params[] = $date;
            }
            
            $query .= ' ORDER BY c.consultation_date DESC, c.consultation_time DESC LIMIT ?';
            $params[] = (int)$limit;
            
            $consultations = $this->db->fetchAll($query, $params);
            
            // Resolve clinical findings IDs to Full Names
            foreach ($consultations as &$c) {
                if (!empty($c['soap_objective'])) {
                    $c['soap_objective'] = $this->resolveServiceNames($c['soap_objective']);
                }
            }
            
            $this->respondSuccess($consultations);
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * GET /api/consultations/{id}
     * Get single consultation
     */
    public function show($id) {
        $this->restrictMethod('GET');
        
        try {
            $consultation = $this->db->fetchOne(
                'SELECT c.*, d.full_name as doctor_name 
                 FROM consultations c 
                 LEFT JOIN doctors d ON c.doctor_id = d.doctor_id 
                 WHERE c.consultation_id = ?',
                [$id]
            );
            
            if (!$consultation) {
                $this->respondNotFound('Consultation not found');
            }
            
            if (!empty($consultation['soap_objective'])) {
                $consultation['soap_objective'] = $this->resolveServiceNames($consultation['soap_objective']);
            }
            
            $this->respondSuccess($consultation);
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * POST /api/consultations
     * Create new consultation
     */
    public function create() {
        $this->restrictMethod('POST');
        
        $schema = [
            'required' => ['patient_id', 'doctor_id', 'soap_subjective'],
            'properties' => [
                'patient_id' => ['type' => 'string'],
                'doctor_id' => ['type' => 'string'],
                'appointment_id' => ['type' => 'string'],
                'issue_description_id' => ['type' => 'integer'],
                'soap_subjective' => ['type' => 'string'],
                'soap_objective' => ['type' => 'string'],
                'soap_assessment' => ['type' => 'string'],
                'soap_plan' => ['type' => 'string'],
                'vital_signs' => ['type' => 'string'],
                'physical_examination' => ['type' => 'string'],
                'final_diagnosis' => ['type' => 'string'],
                'diagnosis' => ['type' => 'string'],
                'notes' => ['type' => 'string'],
                'clinical_notes' => ['type' => 'string'],
                'follow_up_date' => ['type' => 'string'],
                'follow_up_instructions' => ['type' => 'string'],
                'consultation_duration' => ['type' => 'integer'],
                'general_instructions' => ['type' => 'string'],
                'dietary_advice' => ['type' => 'string'],
                'status' => ['type' => 'integer']
            ],
            'additionalProperties' => true
        ];
        
        $data = $this->getJsonInput($schema);
        
        // Generate consultation_id
        $today = date('Ymd');
        $prefix = 'CONS-' . $today . '-';
        
        $lastConsultation = $this->db->fetchOne(
            "SELECT consultation_id FROM consultations WHERE consultation_id LIKE ? ORDER BY sl_no DESC LIMIT 1",
            [$prefix . '%']
        );
        
        if ($lastConsultation) {
            $lastSequence = (int)substr($lastConsultation['consultation_id'], -3);
            $newSequence = str_pad($lastSequence + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newSequence = '001';
        }
        
        $data['consultation_id'] = $prefix . $newSequence;
        $data['consultation_date'] = date('Y-m-d');
        $data['consultation_time'] = date('H:i:s');
        if (!isset($data['status'])) {
            $data['status'] = 0; // Default to Completed if not provided
        }
        
        try {
            // Define whitelisted columns for consultations table
            $allowedConsultationColumns = [
                'consultation_id', 'patient_id', 'doctor_id', 'appointment_id', 'issue_description_id',
                'soap_subjective', 'soap_objective', 'soap_assessment', 'soap_plan',
                'vital_signs', 'physical_examination', 'final_diagnosis', 'clinical_notes', 
                'follow_up_date', 'follow_up_instructions', 'consultation_duration',
                'consultation_date', 'consultation_time', 'status'
            ];

            // Separate prescription data if it exists
            $medicines = $data['medicines'] ?? null;
            $generalInstructions = $data['general_instructions'] ?? null;
            $dietaryAdvice = $data['dietary_advice'] ?? null;
            $followUpInstructions = $data['follow_up_instructions'] ?? null;
            
            // Ensure status is set (already handled above, but keeping comment for clarity)
            // $data['status'] = 'Completed'; // Removed hardcode
            
            // Prepare clean data for consultation table
            $consultationInsertData = [];
            foreach ($allowedConsultationColumns as $col) {
                if (isset($data[$col])) {
                    $consultationInsertData[$col] = $data[$col];
                }
            }

            // Perform consultation insert
            $this->db->insert('consultations', $consultationInsertData);

            // Create an 'Active' prescription for completed consultations
            // We create a record if there are medicines OR if there are other clinical details suitable for a prescription/report
            $hasMedicines = ($medicines && !empty(json_decode($medicines, true)));
            $hasClinicalDetails = !empty($data['diagnosis']) || !empty($generalInstructions) || !empty($followUpInstructions) || !empty($data['soap_plan']);

            if ($hasMedicines || $hasClinicalDetails) {
                $prescriptionId = 'RX-' . $today . '-' . $newSequence;
                $prescriptionData = [
                    'prescription_id' => $prescriptionId,
                    'patient_id' => $data['patient_id'],
                    'doctor_id' => $data['doctor_id'],
                    'appointment_id' => $data['appointment_id'] ?? null,
                    'medicines' => $medicines,
                    'diagnosis' => $data['diagnosis'] ?? $data['soap_assessment'] ?? 'General Consultation',
                    'general_instructions' => $generalInstructions,
                    'dietary_advice' => $dietaryAdvice,
                    'follow_up_date' => $data['follow_up_date'] ?? null,
                    'follow_up_instructions' => $followUpInstructions,
                    'prescription_date' => date('Y-m-d'),
                    'status' => '1' // '1' represents 'Active' status
                ];
                
                try {
                    $this->db->insert('prescriptions', $prescriptionData);
                    error_log("Prescription $prescriptionId created for consultation " . $data['consultation_id']);
                } catch (Exception $e) {
                    error_log("Prescription insert failed: " . $e->getMessage());
                }
            }

            // --- UPDATE APPOINTMENT STATUS TO 0 (INACTIVE) ---
            $targetAptId = $data['appointment_id'] ?? null;
            
            if (empty($targetAptId)) {
                $todayDate = date('Y-m-d');
                $sql = "SELECT appointment_id FROM appointments 
                        WHERE patient_id COLLATE utf8mb4_general_ci = ? 
                          AND doctor_id COLLATE utf8mb4_general_ci = ? 
                          AND appointment_date = ? 
                          AND (appointment_status = '1' OR appointment_status = 'Scheduled')
                        ORDER BY appointment_time DESC LIMIT 1";
                $found = $this->db->fetchOne($sql, [$data['patient_id'], $data['doctor_id'], $todayDate]);
                if ($found) {
                    $targetAptId = $found['appointment_id'];
                }
            }

            if (!empty($targetAptId)) {
                try {
                    $this->db->execute(
                        "UPDATE appointments SET appointment_status = '0' WHERE appointment_id = ?",
                        [$targetAptId]
                    );
                } catch (Exception $e) {
                    error_log("Failed to update appointment status: " . $e->getMessage());
                }
            }
            
            $result = $this->db->fetchOne(
                'SELECT * FROM consultations WHERE consultation_id = ?',
                [$data['consultation_id']]
            );
            
            $this->respondCreated($result);
            
        } catch (Exception $e) {
            error_log("Consultation creation error: " . $e->getMessage());
            $this->respondServerError('Database operation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * PUT /api/consultations/{id}
     * Update consultation
     */
    public function update($id) {
        $this->restrictMethod('PUT');
        
        $schema = [
            'properties' => [
                'soap_subjective' => ['type' => 'string'],
                'soap_objective' => ['type' => 'string'],
                'soap_assessment' => ['type' => 'string'],
                'soap_plan' => ['type' => 'string'],
                'vital_signs' => ['type' => 'string'],
                'diagnosis' => ['type' => 'string'],
                'notes' => ['type' => 'string'],
                'clinical_notes' => ['type' => 'string'],
                'follow_up_date' => ['type' => 'string'],
                'follow_up_instructions' => ['type' => 'string'],
                'status' => ['type' => 'integer'],
                'medicines' => ['type' => 'string'],
                'general_instructions' => ['type' => 'string']
            ],
            'additionalProperties' => true
        ];
        
        $data = $this->getJsonInput($schema);
        
        try {
            $existing = $this->db->fetchOne(
                'SELECT consultation_id FROM consultations WHERE consultation_id = ?',
                [$id]
            );
            
            if (!$existing) {
                $this->respondNotFound('Consultation not found');
            }
            
            $this->db->update('consultations', $data, 'consultation_id = ?', [$id]);
            
            $consultation = $this->db->fetchOne(
                'SELECT * FROM consultations WHERE consultation_id = ?',
                [$id]
            );
            
            $this->respondSuccess($consultation, 'Consultation updated successfully');
            
        } catch (Exception $e) {
            error_log("Consultation update error: " . $e->getMessage());
            $this->respondServerError('Database operation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * DELETE /api/consultations/{id}
     * Delete consultation
     */
    public function delete($id) {
        $this->restrictMethod('DELETE');
        
        try {
            $existing = $this->db->fetchOne(
                'SELECT consultation_id FROM consultations WHERE consultation_id = ?',
                [$id]
            );
            
            if (!$existing) {
                $this->respondNotFound('Consultation not found');
            }
            
            $this->db->delete('consultations', 'consultation_id = ?', [$id]);
            $this->respondSuccess(null, 'Consultation deleted successfully');
            
        } catch (Exception $e) {
            error_log("Consultation delete error: " . $e->getMessage());
            $this->respondServerError('Database operation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/consultations/translate-audio
     * Translate audio to English text using Groq API
     */
    public function translateAudio() {
        try {
            // Load Groq configuration
            $config = require __DIR__ . '/../../config/groq_config.php';
            
            // Validate audio file upload
            if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
                $this->respondBadRequest('No audio file uploaded or upload error occurred');
                return;
            }
            
            $audioFile = $_FILES['audio'];
            
            // Validate file size
            if ($audioFile['size'] > $config['max_file_size']) {
                $this->respondBadRequest('Audio file too large. Maximum size: 25MB');
                return;
            }
            
            // Validate file format
            $fileExtension = strtolower(pathinfo($audioFile['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExtension, $config['supported_formats'])) {
                $this->respondBadRequest('Unsupported audio format. Supported: ' . implode(', ', $config['supported_formats']));
                return;
            }
            
            // Create temp directory if it doesn't exist
            if (!file_exists($config['temp_dir'])) {
                mkdir($config['temp_dir'], 0777, true);
            }
            
            // Save uploaded file temporarily
            $tempFilePath = $config['temp_dir'] . uniqid('audio_') . '.' . $fileExtension;
            if (!move_uploaded_file($audioFile['tmp_name'], $tempFilePath)) {
                $this->respondServerError('Failed to save audio file');
                return;
            }
            
            // Prepare Groq API request
            $ch = curl_init();
            
            // Optimized medical prompt - 223 chars (under 224 limit)
            // Focus on medical accuracy for Kannada/Indian languages
            $postFields = [
                'file' => new \CURLFile($tempFilePath, 'audio/webm', basename($tempFilePath)),
                'model' => $config['model'],
                'response_format' => 'json',
                'prompt' => 'Medical consultation: patient tells doctor symptoms in Kannada/Hindi/Tamil/Telugu. Translate to English. Preserve: fever, pain, cough, feeling unwell, sick, ill, duration (days/weeks), body parts, severity.'
            ];
            
            curl_setopt_array($ch, [
                CURLOPT_URL => $config['api_endpoint'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postFields,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $config['api_key']
                ],
                CURLOPT_TIMEOUT => $config['timeout'],
                CURLOPT_VERBOSE => true
            ]);
            
            // Execute request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            // Log the response for debugging
            error_log("Groq API Response Code: " . $httpCode);
            error_log("Groq API Response Body: " . $response);
            
            // Delete temporary file immediately
            if (file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
            
            // Handle cURL errors
            if ($curlError) {
                error_log("Groq API cURL error: " . $curlError);
                $this->respondServerError('Failed to connect to translation service: ' . $curlError);
                return;
            }
            
            // Handle HTTP errors - return actual Groq error message
            if ($httpCode !== 200) {
                error_log("Groq API HTTP error: " . $httpCode . " - " . $response);
                
                // Try to parse Groq error message
                $errorData = json_decode($response, true);
                $errorMessage = 'Translation service error: ' . $httpCode;
                
                if ($errorData && isset($errorData['error'])) {
                    if (is_array($errorData['error'])) {
                        $errorMessage .= ' - ' . ($errorData['error']['message'] ?? json_encode($errorData['error']));
                    } else {
                        $errorMessage .= ' - ' . $errorData['error'];
                    }
                }
                
                // Return full error details for debugging
                $this->respond([
                    'success' => false,
                    'error' => $errorMessage,
                    'debug' => [
                        'http_code' => $httpCode,
                        'response' => $response,
                        'api_key_prefix' => substr($config['api_key'], 0, 10) . '...',
                        'endpoint' => $config['api_endpoint'],
                        'model' => $config['model']
                    ]
                ], 500);
                return;
            }
            
            // Parse response
            $result = json_decode($response, true);
            
            if (!$result || !isset($result['text'])) {
                error_log("Groq API invalid response: " . $response);
                $this->respondServerError('Invalid response from translation service');
                return;
            }
            
            // Return translated text
            $this->respondSuccess([
                'text' => $result['text'],
                'model' => $config['model']
            ], 'Audio translated successfully');
            
        } catch (Exception $e) {
            error_log("Audio translation error: " . $e->getMessage());
            $this->respondServerError('Translation failed: ' . $e->getMessage());
        }
    }
}

