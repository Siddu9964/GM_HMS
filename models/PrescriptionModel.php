<?php
namespace GM_HMS\Models;

use GM_HMS\Database\SecureDatabase;
use Exception;

class PrescriptionModel
{
    protected $db;
    protected $table = 'consultations';

    public function __construct()
    {
        $this->db = SecureDatabase::getInstance();
    }

    /**
     * STEP 11: Fetch Patient details by Patient ID, UHID, or Phone
     */
    public function fetchPatient($patientId)
    {
        if (empty($patientId)) return null;

        $sql = "SELECT patient_id, uhid, first_name, last_name, birth_date, age, 
                       sex as gender, blood_group, phone, address, city, pincode, 
                       state, country, district, region, division, area
                FROM patient 
                WHERE patient_id = ? OR phone = ? OR uhid = ?
                LIMIT 1";

        $patient = $this->db->fetchOne($sql, [$patientId, $patientId, $patientId]);
        if ($patient) {
            if (empty($patient['age']) && !empty($patient['birth_date'])) {
                $patient['age'] = $this->calculateAge($patient['birth_date']);
            }
        }
        return $patient;
    }

    /**
     * STEP 11: Fetch Doctor details by Doctor ID
     */
    public function fetchDoctor($doctorId)
    {
        if (empty($doctorId)) return null;

        $sql = "SELECT doctor_id, full_name as doctor_name, department_id as department, qualification, specialization, signature
                FROM doctors 
                WHERE doctor_id = ?
                LIMIT 1";

        return $this->db->fetchOne($sql, [$doctorId]);
    }

    /**
     * STEP 11: Fetch Consultations for a Patient by consultations.patient_id
     * Returns full consultation records ordered by consultation_date DESC, consultation_time DESC
     */
    public function fetchConsultationsByPatient($patientId)
    {
        if (empty($patientId)) return [];

        // Resolve patient if searched by phone or uhid first
        $patient = $this->fetchPatient($patientId);
        $realPatientId = $patient ? $patient['patient_id'] : $patientId;

        $sql = "SELECT c.consultation_id,
                       c.patient_id,
                       c.doctor_id,
                       c.appointment_id,
                       c.consultation_date,
                       c.consultation_time,
                       c.soap_subjective,
                       c.soap_objective,
                       c.soap_assessment,
                       c.soap_plan,
                       c.vital_signs,
                       c.physical_examination,
                       c.final_diagnosis,
                       c.clinical_notes,
                       c.follow_up_date,
                       c.follow_up_instructions,
                       c.prescription_image,
                       c.status,
                       pat.uhid, pat.first_name, pat.last_name, pat.sex as gender, pat.birth_date, pat.age,
                       pat.blood_group, pat.phone as patient_phone, pat.address, pat.city, pat.pincode, pat.state,
                       doc.full_name as doctor_name, doc.department_id as department, doc.qualification, doc.specialization
                FROM consultations c
                LEFT JOIN patient pat ON c.patient_id = pat.patient_id
                LEFT JOIN doctors doc ON c.doctor_id = doc.doctor_id
                WHERE c.patient_id = ?
                ORDER BY c.consultation_date DESC, c.consultation_time DESC";

        $rows = $this->db->fetchAll($sql, [$realPatientId]);
        $settings = $this->getSystemSettings();

        foreach ($rows as &$row) {
            $row['prescription_id'] = $row['consultation_id'];
            $row['prescription_date'] = $row['consultation_date'];
            $row['complaint'] = !empty($row['soap_subjective']) ? $row['soap_subjective'] : ($row['final_diagnosis'] ?? 'General Consultation');
            
            if (empty($row['age']) && !empty($row['birth_date'])) {
                $row['age'] = $this->calculateAge($row['birth_date']);
            }

            // Normalize Prescription Image Web URL
            $row['prescription_image_url'] = $this->normalizeWebUrl($row['prescription_image']);
            $row['has_prescription_image'] = !empty($row['prescription_image_url']);

            // Decode vital_signs JSON if string
            $row['parsed_vitals'] = [];
            if (!empty($row['vital_signs'])) {
                if (is_array($row['vital_signs'])) {
                    $row['parsed_vitals'] = $row['vital_signs'];
                } else {
                    $decodedVitals = json_decode($row['vital_signs'], true);
                    if (is_array($decodedVitals)) {
                        $row['parsed_vitals'] = $decodedVitals;
                    }
                }
            }

            // Decode medicines array and plan_text from soap_plan
            $parsedPlan = $this->parseSoapPlanData($row['soap_plan'] ?? null);
            $row['medicines'] = $parsedPlan['medicines'];
            $row['plan_text'] = $parsedPlan['plan_text'];

            // Inject system settings
            $row['hospital_name'] = $settings['system_name'] ?? 'GM HMS Multispeciality';
            $row['hospital_logo'] = $settings['institution_logo'] ?? null;
            $row['hospital_address'] = $settings['address'] ?? 'Main Road, Health City';
            $row['hospital_phone'] = $settings['phone'] ?? '+91 98765 43210';
            $row['hospital_email'] = $settings['email'] ?? 'contact@gmhms.com';
        }

        return $rows;
    }

    /**
     * STEP 11: Fetch Lab Results for a Patient (lab_results.patient_id = patient_id)
     */
    public function fetchLabResults($patientId)
    {
        if (empty($patientId)) return [];

        // Resolve patient if searched by phone or uhid
        $patient = $this->fetchPatient($patientId);
        $realPatientId = $patient ? $patient['patient_id'] : $patientId;

        $sql = "SELECT result_id, order_id, patient_id, test_name, result_data, abnormal_flags, 
                       report_file, reviewed_by, reviewed_at, status, result_date, result_time
                FROM lab_results
                WHERE patient_id = ?
                ORDER BY result_date DESC, result_time DESC";

        $rows = $this->db->fetchAll($sql, [$realPatientId]);

        foreach ($rows as &$lab) {
            // Normalize PDF report URL
            $lab['report_file_url'] = $this->normalizeWebUrl($lab['report_file']);
            $lab['has_report_file'] = !empty($lab['report_file_url']);

            // Parse abnormal flags JSON if present
            $lab['parsed_abnormal_flags'] = [];
            if (!empty($lab['abnormal_flags'])) {
                if (is_array($lab['abnormal_flags'])) {
                    $lab['parsed_abnormal_flags'] = $lab['abnormal_flags'];
                } else {
                    $decodedFlags = json_decode($lab['abnormal_flags'], true);
                    if (is_array($decodedFlags)) {
                        $lab['parsed_abnormal_flags'] = $decodedFlags;
                    }
                }
            }

            // Decode result_data JSON string into clean structured parameter rows
            $lab['parsed_parameters'] = [];
            if (!empty($lab['result_data'])) {
                $rawResult = is_array($lab['result_data']) ? $lab['result_data'] : json_decode($lab['result_data'], true);
                if (is_array($rawResult)) {
                    foreach ($rawResult as $key => $val) {
                        if (is_array($val)) {
                            $paramName = $val['parameter'] ?? $val['name'] ?? (string)$key;
                            $paramVal = $val['value'] ?? $val['result'] ?? '-';
                            $paramRange = $val['range'] ?? $val['unit'] ?? $val['reference_range'] ?? '-';
                            $paramStatus = $val['status'] ?? $val['flag'] ?? 'Normal';
                        } else {
                            $paramName = (string)$key;
                            $paramVal = (string)$val;
                            $paramRange = '-';
                            $paramStatus = 'Normal';
                        }

                        $isAbnormal = (
                            in_array($paramName, $lab['parsed_abnormal_flags']) ||
                            stripos($paramStatus, 'abnormal') !== false ||
                            stripos($paramStatus, 'high') !== false ||
                            stripos($paramStatus, 'low') !== false ||
                            stripos($paramStatus, 'critical') !== false
                        );

                        $lab['parsed_parameters'][] = [
                            'parameter' => $paramName,
                            'value' => $paramVal,
                            'range' => $paramRange,
                            'status' => $paramStatus,
                            'is_abnormal' => $isAbnormal
                        ];
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * Normalize file paths (Windows or relative) to web-accessible URLs
     */
    public function normalizeWebUrl($path)
    {
        if (empty($path)) return null;

        // Decode JSON array strings like ["C:\/xampp\/htdocs\\GM_HMS\\assets\\precision_data\\file.png"]
        if (is_string($path) && (strpos($path, '[') === 0 || strpos($path, '{') === 0)) {
            $decoded = json_decode($path, true);
            if (is_array($decoded) && !empty($decoded)) {
                $path = reset($decoded);
            }
        }

        if (!is_string($path) || trim($path) === '') return null;

        // Normalize backslashes to forward slashes
        $cleanPath = str_replace('\\', '/', trim($path));

        // Determine base URL path prefix (/GM_HMS/ or /) dynamically
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePrefix = (strpos($uri, '/GM_HMS/') !== false || strpos($script, '/GM_HMS/') !== false) ? '/GM_HMS/' : '/';

        // Extract relative assets or uploads path
        if (preg_match('/(assets\/.*|uploads\/.*)/i', $cleanPath, $matches)) {
            return rtrim($basePrefix, '/') . '/' . ltrim($matches[1], '/');
        }

        if (preg_match('/htdocs\/[^\/]+\/(.*)$/i', $cleanPath, $matches)) {
            return rtrim($basePrefix, '/') . '/' . ltrim($matches[1], '/');
        }

        if (strpos($cleanPath, 'http://') === 0 || strpos($cleanPath, 'https://') === 0) {
            return $cleanPath;
        }

        return rtrim($basePrefix, '/') . '/' . ltrim($cleanPath, '/');
    }

    public function getAllPrescriptions($limit = 50)
    {
        $sql = "SELECT c.consultation_id,
                       c.consultation_id as prescription_id,
                       c.consultation_date as prescription_date,
                       c.consultation_time,
                       c.patient_id, c.doctor_id, c.status,
                       c.soap_subjective, c.vital_signs, c.soap_plan, 
                       c.final_diagnosis as diagnosis,
                       c.follow_up_date,
                       c.prescription_image,
                       pat.first_name, pat.last_name, pat.sex as gender, pat.birth_date, pat.phone as patient_phone,
                       doc.full_name as doctor_name, doc.specialization, doc.department_id as department
                FROM consultations c
                LEFT JOIN patient pat ON c.patient_id = pat.patient_id
                LEFT JOIN doctors doc ON c.doctor_id = doc.doctor_id
                ORDER BY c.consultation_date DESC, c.consultation_time DESC LIMIT ?";

        $prescriptions = $this->db->fetchAll($sql, [$limit]);
        $settings = $this->getSystemSettings();
        foreach ($prescriptions as &$p) {
            $p['prescription_image_url'] = $this->normalizeWebUrl($p['prescription_image'] ?? null);
            $p['has_prescription_image'] = !empty($p['prescription_image_url']);
            
            // Extract medicines array and plan_text
            $parsedPlan = $this->parseSoapPlanData($p['soap_plan'] ?? null);
            $p['medicines'] = $parsedPlan['medicines'];
            $p['plan_text'] = $parsedPlan['plan_text'];

            // Extract vitals array
            $p['parsed_vitals'] = [];
            if (!empty($p['vital_signs'])) {
                $v = json_decode($p['vital_signs'], true);
                if (is_array($v)) $p['parsed_vitals'] = $v;
            }

            $p['hospital_name'] = $settings['system_name'] ?? 'GM HMS Multispeciality';
            $p['hospital_logo'] = $settings['institution_logo'] ?? null;
            $p['hospital_address'] = $settings['address'] ?? 'Main Road, Health City';
            $p['hospital_phone'] = $settings['phone'] ?? '+91 98765 43210';
            $p['hospital_email'] = $settings['email'] ?? 'contact@gmhms.com';
        }

        return $prescriptions;
    }

    public function getPrescriptionsByPatient($patientId)
    {
        return $this->fetchConsultationsByPatient($patientId);
    }

    public function getPrescriptionById($prescriptionId)
    {
        $sql = "SELECT c.consultation_id as prescription_id,
                       c.consultation_date as prescription_date,
                       c.patient_id, c.doctor_id, c.status,
                       c.soap_plan, c.vital_signs, c.physical_examination,
                       c.final_diagnosis as diagnosis, c.clinical_notes, c.follow_up_date, c.prescription_image,
                       pat.first_name, pat.last_name, pat.sex as gender, pat.birth_date, pat.phone as patient_phone, pat.address,
                       doc.full_name as doctor_name, doc.specialization, doc.signature as signature_path
                FROM consultations c
                LEFT JOIN patient pat ON c.patient_id = pat.patient_id
                LEFT JOIN doctors doc ON c.doctor_id = doc.doctor_id
                WHERE c.consultation_id = ? OR c.appointment_id = ?";

        $p = $this->db->fetchOne($sql, [$prescriptionId, $prescriptionId]);
        if ($p) {
            $parsedPlan = $this->parseSoapPlanData($p['soap_plan'] ?? null);
            $p['medicines'] = $parsedPlan['medicines'];
            $p['plan_text'] = $parsedPlan['plan_text'];
            $p['prescription_image_url'] = $this->normalizeWebUrl($p['prescription_image']);
            $settings = $this->getSystemSettings();
            $p['hospital_name'] = $settings['system_name'] ?? 'GM HMS Multispeciality';
            $p['hospital_logo'] = $settings['institution_logo'] ?? null;
            $p['hospital_address'] = $settings['address'] ?? 'Main Road, Health City';
            $p['hospital_phone'] = $settings['phone'] ?? '+91 98765 43210';
            $p['hospital_email'] = $settings['email'] ?? 'contact@gmhms.com';
        }
        return $p;
    }

    public function getSystemSettings()
    {
        $sql = "SELECT type, description FROM settings";
        $results = $this->db->fetchAll($sql);
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['type']] = $row['description'];
        }
        return $settings;
    }

    public function logPrintActivity($prescriptionId, $userId)
    {
        $sql = "INSERT INTO audit_logs (event_type, event_category, severity, resource, action, user_id, ip_address, request_data) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $requestData = json_encode([
            'prescription_id' => $prescriptionId,
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        return $this->db->execute($sql, [
            'PRINT',
            'Clinical',
            'Info',
            'Prescriptions',
            'PRINT_PRESCRIPTION',
            $userId,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $requestData
        ]);
    }

    public function calculateAge($birthDate)
    {
        if (!$birthDate) return 'N/A';
        $birthDate = new \DateTime($birthDate);
        $today = new \DateTime();
        $age = $today->diff($birthDate);
        return $age->y;
    }

    public function parseSoapPlanData($soapPlan)
    {
        $medicines = [];
        $planText = '';

        if (empty($soapPlan)) {
            return ['medicines' => [], 'plan_text' => ''];
        }

        if (is_array($soapPlan)) {
            $data = $soapPlan;
        } else if (is_string($soapPlan)) {
            $trimmed = trim($soapPlan);
            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_object($decoded))) {
                $data = (array)$decoded;
            } else {
                return ['medicines' => [], 'plan_text' => $trimmed];
            }
        } else {
            return ['medicines' => [], 'plan_text' => ''];
        }

        if (isset($data['medications']) && is_array($data['medications'])) {
            $medicines = $data['medications'];
            if (isset($data['plan']) && is_string($data['plan'])) {
                $planText = $data['plan'];
            } else if (isset($data['instructions']) && is_string($data['instructions'])) {
                $planText = $data['instructions'];
            } else if (isset($data['notes']) && is_string($data['notes'])) {
                $planText = $data['notes'];
            }
        } else if (isset($data[0]) && (is_array($data[0]) || is_object($data[0]))) {
            $medicines = $data;
        } else if (isset($data['name']) || isset($data['medicine_name'])) {
            $medicines = [$data];
        } else {
            if (isset($data['plan']) && is_string($data['plan'])) {
                $planText = $data['plan'];
            } else if (isset($data['instructions']) && is_string($data['instructions'])) {
                $planText = $data['instructions'];
            } else if (isset($data['notes']) && is_string($data['notes'])) {
                $planText = $data['notes'];
            } else {
                $parts = [];
                foreach ($data as $k => $v) {
                    if (is_string($v) && !empty($v)) {
                        $parts[] = ucfirst($k) . ": " . $v;
                    }
                }
                $planText = implode("\n", $parts);
            }
        }

        return ['medicines' => $medicines, 'plan_text' => $planText];
    }
}
