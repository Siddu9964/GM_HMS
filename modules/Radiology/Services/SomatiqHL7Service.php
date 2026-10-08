<?php
namespace GM_HMS\Modules\Radiology\Services;

use Exception;

/**
 * SomatiqHL7Service — Orchestrates the HL7 dispatch to Somatiq RIS.
 *
 * Workflow:
 *   1. Load Somatiq config (config/somatiq_hl7.php)
 *   2. Map GM_HMS patient + order data into HL7 arrays
 *   3. Build ORM^O01 HL7 v2.3 message  (via HL7Builder)
 *   4. Send over MLLP/TLS              (via MLLPClient)
 *   5. Parse the ACK response
 *   6. Write to logs/somatiq_hl7.log
 *
 * The sendOrder() method NEVER throws — if Somatiq is unreachable,
 * it returns ['success' => false] so the HMS workflow is not interrupted.
 */
class SomatiqHL7Service
{
    private array  $config;
    private string $logFile;

    public function __construct()
    {
        $configPath = __DIR__ . '/../../../config/somatiq_hl7.php';

        if (!file_exists($configPath)) {
            throw new Exception(
                "Somatiq HL7 config missing. Expected: {$configPath}"
            );
        }

        $this->config  = require $configPath;
        $this->logFile = __DIR__ . '/../../../logs/somatiq_hl7.log';
    }

    /**
     * Send a radiology order to Somatiq RIS via HL7 ORM^O01.
     *
     * @param  array  $gmOrder   Row from radiology_test_orders (must have order_id, patient_id, etc.)
     * @param  array  $patient   Row from patients table
     * @return array  {
     *     success: bool,
     *     ack:     string (raw HL7 ACK from Somatiq),
     *     message: string (human-readable status)
     * }
     */
    public function sendOrder(array $gmOrder, array $patient): array
    {
        try {
            // 1. Map to HL7 patient array
            $hl7Patient = $this->mapPatient($patient);

            // 2. Map to HL7 order array
            $hl7Order = $this->mapOrder($gmOrder);

            // 3. Map visit type and referring doctor
            $hl7Visit = $this->mapVisit($gmOrder, $patient);

            // 4. Build the HL7 ORM^O01 message
            $builder = new HL7Builder($this->config);
            $hl7     = $builder->buildOrder($hl7Patient, $hl7Order, $hl7Visit);

            // 5. Send via MLLP over TLS
            $client = new MLLPClient(
                $this->config['host'],
                $this->config['port'],
                $this->config['timeout']
            );
            $ack = $client->send($hl7);

            // 6. Parse Somatiq's ACK
            $isAccepted = (bool) preg_match('/MSA\|AA\|/', $ack);
            $isRejected = (bool) preg_match('/MSA\|AE\|/', $ack);

            if ($isAccepted) {
                $msg = 'Somatiq accepted the order (ACK: AA).';
            } elseif ($isRejected) {
                $msg = 'Somatiq rejected the order (ACK: AE). Check your CENTRE_CODE.';
            } else {
                $msg = 'ACK received but status is unclear — check somatiq_hl7.log.';
            }

            $this->log([
                'ts'       => date('Y-m-d H:i:s'),
                'order_id' => $gmOrder['order_id'] ?? 'unknown',
                'patient'  => $patient['patient_id'] ?? 'unknown',
                'success'  => $isAccepted,
                'ack'      => $ack,
                'message'  => $msg,
            ]);

            return [
                'success' => $isAccepted,
                'ack'     => $ack,
                'message' => $msg,
            ];

        } catch (\Throwable $e) {
            $this->log([
                'ts'       => date('Y-m-d H:i:s'),
                'order_id' => $gmOrder['order_id'] ?? 'unknown',
                'patient'  => $patient['patient_id'] ?? 'unknown',
                'success'  => false,
                'error'    => $e->getMessage(),
            ]);

            // Return soft failure — order is already saved in GM_HMS DB
            return [
                'success' => false,
                'ack'     => '',
                'message' => 'Somatiq HL7 error (non-fatal): ' . $e->getMessage(),
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Data Mapping Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Map a GM_HMS patients table row to the HL7 patient array.
     *
     * GM_HMS columns used:
     *   patient_id, first_name, last_name, full_name,
     *   date_of_birth (or dob), sex (or gender), phone (or mobile)
     */
    private function mapPatient(array $p): array
    {
        // Full name: prefer full_name, fall back to first+last
        $name = trim(
            !empty($p['full_name'])
                ? $p['full_name']
                : (($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''))
        );

        // DOB: try multiple column names
        $dob = $p['date_of_birth'] ?? $p['dob'] ?? '1900-01-01';
        if (empty(trim($dob))) $dob = '1900-01-01';

        // Sex: must be M or F for HL7
        $sex = strtoupper(substr($p['sex'] ?? $p['gender'] ?? 'U', 0, 1));
        if (!in_array($sex, ['M', 'F'], true)) $sex = 'U';

        return [
            'id'            => $p['patient_id'],
            'name'          => $name ?: 'Unknown',
            'date_of_birth' => $dob,
            'sex'           => $sex,
            'phone'         => $p['phone'] ?? $p['mobile'] ?? '',
        ];
    }

    /**
     * Map a GM_HMS radiology order row to the HL7 order array.
     *
     * GM_HMS columns used:
     *   order_id, test_name, service_id (or procedure_code),
     *   modality / modality_name, priority
     */
    private function mapOrder(array $o): array
    {
        // Handle GM_HMS format which could be JSON or comma-separated string
        $rawName       = $o['resolved_test_names'] ?? $o['test_name'] ?? 'SCAN';
        if (is_string($rawName) && strpos($rawName, '[') === 0) {
            $decoded = json_decode($rawName, true);
            if (is_array($decoded) && !empty($decoded)) {
                $rawName = $decoded[0];
            }
        }
        $firstName     = trim(explode(',', $rawName)[0]); // Take first test if comma-separated
        $procedureName = trim(preg_replace('/\s*\([^)]+\)$/', '', $firstName));

        // procedure_code: use explicit field or extract from bracket in test_name
        $procedureCode = $o['service_id'] ?? $o['procedure_code'] ?? '';
        if (empty($procedureCode)) {
            // Try to extract "(RDS171)" from test_name
            if (preg_match('/\(([^)]+)\)$/', $firstName, $m)) {
                $procedureCode = $m[1];
            } else {
                $procedureCode = 'RAD';
            }
        }

        // Modality
        $modality = $o['modality'] ?? $o['modality_name'] ?? 'X-RAY';

        // Priority: GM_HMS uses "Routine" / "Stat" / "Urgent" → HL7 uses R / S
        $prio = strtolower($o['priority'] ?? 'routine');
        $priority = ($prio === 'stat' || $prio === 'urgent') ? 'S' : 'R';

        return [
            'id'             => $o['order_id'],
            'procedure_code' => $procedureCode,
            'procedure_name' => $procedureName ?: 'RADIOLOGY EXAM',
            'modality'       => strtoupper($modality),
            'priority'       => $priority,
        ];
    }

    /**
     * Map visit class (O=Outpatient, I=Inpatient) and referring doctor.
     * IPD orders always have an admission_id.
     */
    private function mapVisit(array $o, array $p): array
    {
        $type   = !empty($o['admission_id']) ? 'I' : 'O';
        $doctor = $p['doctor_name'] ?? $o['doctor_name'] ?? $o['referring_doctor'] ?? '';

        return [
            'type'             => $type,
            'referring_doctor' => $doctor,
        ];
    }

    /** Append one JSON log line to logs/somatiq_hl7.log */
    private function log(array $data): void
    {
        @file_put_contents(
            $this->logFile,
            json_encode($data) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}
