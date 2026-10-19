<?php
namespace GM_HMS\Modules\Radiology\Services;

use GM_HMS\Database\SecureDatabase;

/**
 * SomatiqResultProcessor
 * 
 * Parses inbound HL7 ORU^R01 result messages from Somatiq RIS,
 * automatically detects Outpatient (OPD) vs Inpatient (IPD),
 * and saves findings, report URLs, and DICOM viewer links to the database.
 */
class SomatiqResultProcessor
{
    private $db;

    public function __construct()
    {
        $this->db = SecureDatabase::getInstance();
    }

    /**
     * Process a raw HL7 string received from Somatiq
     *
     * @param string $rawHl7 Raw HL7 ORU^R01 message
     * @return array Result of processing [success => bool, message => string, order_id => string, ...]
     */
    public function processHl7Message(string $rawHl7): array
    {
        // 1. Clean framing and split lines
        $clean = trim($rawHl7, chr(0x0B) . chr(0x1C) . chr(0x0D) . "\n\r ");
        $lines = explode("\n", str_replace("\r", "\n", $clean));

        $mshControlId = '';
        $patientId    = '';
        $patientName  = '';
        $orderId      = '';
        $testName     = '';
        $modality     = 'X-RAY';
        $reportUrl    = '';
        $imageUrl     = '';
        $radiologist  = '';
        $findings     = '';
        $impression   = '';
        $base64Pdf    = '';

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $fields = explode('|', $line);
            $segName = $fields[0] ?? '';

            // MSH — Header
            if ($segName === 'MSH') {
                $mshControlId = $fields[9] ?? '';
            }

            // PID — Patient Demographics
            if ($segName === 'PID') {
                $patientId   = $fields[3] ?? '';
                $patientName = str_replace('^', ' ', $fields[5] ?? '');
            }

            // OBR — Order Observation Request
            if ($segName === 'OBR') {
                $orderId = trim($fields[2] ?? '');
                if (empty($orderId) && !empty($fields[3])) {
                    $orderId = trim($fields[3]);
                }

                // OBR-4: Test code and name (e.g. OPB-20261012-0017^XRAY ABDOMEN (KUB))
                if (!empty($fields[4])) {
                    $obr4Parts = explode('^', $fields[4]);
                    $testName  = trim(end($obr4Parts));
                }

                // Modality detection (from OBR fields or test name)
                $detectedModality = '';
                foreach ($fields as $fVal) {
                    $upperVal = strtoupper(trim($fVal));
                    if (in_array($upperVal, ['X-RAY', 'XRAY', 'CT', 'MRI', 'USG', 'ULTRASOUND', 'ECG', 'ECHO', 'MAMMO', 'DEXA'])) {
                        $detectedModality = ($upperVal === 'XRAY') ? 'X-RAY' : $upperVal;
                        break;
                    }
                }
                if ($detectedModality) {
                    $modality = $detectedModality;
                } elseif (stripos($testName, 'X-RAY') !== false || stripos($testName, 'XRAY') !== false) {
                    $modality = 'X-RAY';
                } elseif (stripos($testName, 'CT') !== false) {
                    $modality = 'CT';
                } elseif (stripos($testName, 'MRI') !== false) {
                    $modality = 'MRI';
                } elseif (stripos($testName, 'USG') !== false) {
                    $modality = 'USG';
                }
            }

            // OBX — Observations (Report link, image link, findings, base64)
            if ($segName === 'OBX') {
                $obxType = $fields[2] ?? '';
                $obxId   = $fields[3] ?? '';
                $obxVal  = $fields[5] ?? '';
                $obxDoc  = $fields[16] ?? '';

                if (!empty($obxDoc) && empty($radiologist)) {
                    $radiologist = trim($obxDoc);
                }

                // Reference Pointer (RP) URLs: e.g. https://somatiq.ai/share/XXX^^application^pdf
                if ($obxType === 'RP' || strpos($obxVal, 'http') === 0) {
                    $url = explode('^', $obxVal)[0];
                    if (stripos($obxId, 'REPORT') !== false || stripos($obxVal, 'pdf') !== false) {
                        $reportUrl = $url;
                    } elseif (stripos($obxId, 'IMAGE') !== false || stripos($obxVal, 'html') !== false) {
                        $imageUrl = $url;
                    } else {
                        if (empty($reportUrl)) $reportUrl = $url;
                    }
                }

                // Text Findings / Impression
                if ($obxType === 'TX' || $obxType === 'ST') {
                    if (stripos($obxId, 'IMPRESSION') !== false) {
                        $impression .= ($impression ? "\n" : '') . $obxVal;
                    } else {
                        $findings .= ($findings ? "\n" : '') . $obxVal;
                    }
                }

                // Encapsulated Base64 PDF
                if ($obxType === 'ED') {
                    $parts = explode('^', $obxVal);
                    if (count($parts) >= 3 && strtoupper($parts[1]) === 'BASE64') {
                        $base64Pdf = $parts[2];
                    }
                }
            }
        }

        if (empty($orderId)) {
            return [
                'success' => false,
                'message' => 'No Order ID (OBR-2) found in HL7 message.',
            ];
        }

        // 2. Determine Patient Type: OPD vs IPD
        $isIpd = $this->determineIfIpd($orderId);

        // 3. Fallback Test Name & Patient ID from database if missing in HL7
        if (empty($testName) || empty($patientId)) {
            $existingOrder = $this->lookupOrder($orderId, $isIpd);
            if ($existingOrder) {
                if (empty($patientId)) $patientId = $existingOrder['patient_id'] ?? '';
                if (empty($testName))  $testName  = $existingOrder['test_name'] ?? 'Radiology Examination';
            }
        }

        // 4. Download local PDF copy if Base64 was received or if Report URL exists
        $localReportPath = '';
        if (!empty($base64Pdf)) {
            $localReportPath = $this->saveBase64Pdf($orderId, $base64Pdf, $isIpd);
        }

        // 5. Build structured result_data JSON
        $resultData = [
            'report_url'  => $reportUrl,
            'image_url'   => $imageUrl,
            'radiologist' => $radiologist,
            'findings'    => $findings,
            'impression'  => $impression,
            'somatiq_msg' => $mshControlId,
            'received_at' => date('Y-m-d H:i:s'),
        ];

        // 6. Save to Database
        $targetTable = $isIpd ? 'ipd_radiology_results' : 'radiology_results';
        $reportFileToSave = $reportUrl ?: $localReportPath;

        $saveSuccess = $this->saveToDatabase(
            $targetTable,
            $orderId,
            $patientId,
            $testName ?: 'Radiology Examination',
            $modality ?: 'X-RAY',
            $findings,
            $impression,
            json_encode($resultData),
            $reportFileToSave,
            $radiologist,
            $isIpd ? 'IPD' : 'OPD'
        );

        // 7. Update Order Status in OPD or IPD Billing/Clinical tables
        $this->updateOrderStatus($orderId, $isIpd);

        return [
            'success'     => $saveSuccess,
            'order_id'    => $orderId,
            'patient_id'  => $patientId,
            'patient_type'=> $isIpd ? 'IPD' : 'OPD',
            'test_name'   => $testName,
            'report_url'  => $reportUrl,
            'image_url'   => $imageUrl,
            'radiologist' => $radiologist,
            'table'       => $targetTable,
        ];
    }

    /**
     * Differentiate IPD from OPD
     */
    private function determineIfIpd(string $orderId): bool
    {
        // Check order prefix
        if (strpos($orderId, 'IPD-') === 0 || strpos($orderId, 'IPB-') === 0) {
            return true;
        }
        if (strpos($orderId, 'OPD-') === 0 || strpos($orderId, 'OPB-') === 0) {
            return false;
        }

        // Database lookup: check if order ID exists in IPD clinical records
        $cleanId = preg_replace('/[^0-9]/', '', $orderId);
        if ($cleanId) {
            $ipd = $this->db->fetchOne("SELECT id FROM ipd_clinical_records WHERE id = ? LIMIT 1", [$cleanId]);
            if ($ipd) return true;
        }

        return false;
    }

    /**
     * Lookup order info from OPD or IPD tables
     */
    private function lookupOrder(string $orderId, bool $isIpd): ?array
    {
        if (!$isIpd) {
            $row = $this->db->fetchOne(
                "SELECT obm.patient_id, obi.item_name AS test_name 
                 FROM opd_billing_master obm
                 LEFT JOIN opd_billing_items obi ON obm.bill_id = obi.bill_id
                 WHERE obm.bill_id = ? LIMIT 1",
                [$orderId]
            );
            return $row ?: null;
        } else {
            $cleanId = preg_replace('/[^0-9]/', '', $orderId);
            $row = $this->db->fetchOne(
                "SELECT patient_id, radiology_tests FROM ipd_clinical_records WHERE id = ? LIMIT 1",
                [$cleanId]
            );
            if ($row) {
                $testName = 'Radiology Examination';
                $tests = @json_decode($row['radiology_tests'] ?? '', true);
                if (is_array($tests) && !empty($tests[0]['name'])) {
                    $testName = $tests[0]['name'];
                }
                return ['patient_id' => $row['patient_id'], 'test_name' => $testName];
            }
        }
        return null;
    }

    /**
     * Save/Update into radiology_results or ipd_radiology_results
     */
    private function saveToDatabase(
        string $table,
        string $orderId,
        string $patientId,
        string $testName,
        string $modality,
        string $findings,
        string $impression,
        string $resultDataJson,
        string $reportFile,
        string $radiologist,
        string $patientType
    ): bool {
        $existing = $this->db->fetchOne("SELECT result_id FROM {$table} WHERE order_id = ? LIMIT 1", [$orderId]);

        if ($existing) {
            return (bool)$this->db->execute(
                "UPDATE {$table} 
                 SET status = 'Completed',
                     modality = COALESCE(NULLIF(?, ''), modality),
                     patient_id = CASE WHEN patient_id IS NULL OR patient_id = '' THEN ? ELSE patient_id END,
                     test_name = CASE WHEN test_name IS NULL OR test_name = '' THEN ? ELSE test_name END,
                     findings = COALESCE(NULLIF(?, ''), findings),
                     impression = COALESCE(NULLIF(?, ''), impression),
                     result_data = ?,
                     report_file = COALESCE(NULLIF(?, ''), report_file),
                     reviewed_by = COALESCE(NULLIF(?, ''), reviewed_by),
                     reviewed_at = NOW(),
                     result_date = CURDATE(),
                     result_time = CURTIME()
                 WHERE order_id = ?",
                [
                    $modality,
                    $patientId,
                    $testName,
                    $findings,
                    $impression,
                    $resultDataJson,
                    $reportFile,
                    $radiologist,
                    $orderId
                ]
            );
        } else {
            $resultId = 'RES-RAD-' . strtoupper(substr(uniqid(), -6));
            return (bool)$this->db->execute(
                "INSERT INTO {$table} (
                    result_id, order_id, patient_id, test_name, modality,
                    findings, impression, result_data, report_file,
                    status, result_date, result_time, reviewed_by, reviewed_at, patient_type
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Completed', CURDATE(), CURTIME(), ?, NOW(), ?)",
                [
                    $resultId,
                    $orderId,
                    $patientId,
                    $testName,
                    $modality,
                    $findings,
                    $impression,
                    $resultDataJson,
                    $reportFile,
                    $radiologist,
                    $patientType
                ]
            );
        }
    }

    /**
     * Update order notes in opd_billing_master
     */
    private function updateOrderStatus(string $orderId, bool $isIpd): void
    {
        if (!$isIpd) {
            $master = $this->db->fetchOne("SELECT notes FROM opd_billing_master WHERE bill_id = ? LIMIT 1", [$orderId]);
            if ($master) {
                $notes = $master['notes'] ?? '';
                if (strpos($notes, 'RadStatus: Completed') === false) {
                    $notes = trim($notes . ' | RadStatus: Completed');
                    $this->db->execute("UPDATE opd_billing_master SET notes = ? WHERE bill_id = ?", [$notes, $orderId]);
                }
            }
        }
    }

    /**
     * Save raw Base64 PDF to local directory
     */
    private function saveBase64Pdf(string $orderId, string $base64, bool $isIpd): string
    {
        $folder = $isIpd ? 'ip_patient' : 'op_patient';
        $dir = __DIR__ . '/../../../assets/radiology_reports/' . $folder . '/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $filename = 'rad_report_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $orderId) . '_' . time() . '.pdf';
        $filePath = $dir . $filename;
        $decoded = @base64_decode($base64);
        if ($decoded) {
            file_put_contents($filePath, $decoded);
            return 'assets/radiology_reports/' . $folder . '/' . $filename;
        }
        return '';
    }
}
