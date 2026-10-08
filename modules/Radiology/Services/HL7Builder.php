<?php
namespace GM_HMS\Modules\Radiology\Services;

/**
 * HL7Builder — Builds HL7 v2.3 ORM^O01 messages for Somatiq RIS.
 *
 * Segments built:
 *   MSH — Message Header
 *   PID — Patient Identification
 *   PV1 — Patient Visit (O=OPD, I=IPD)
 *   ORC — Common Order (NW = New Order)
 *   OBR — Observation Request (procedure + modality)
 */
class HL7Builder
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Build HL7 v2.3 ORM^O01 message.
     *
     * @param array $patient  Required: id, name, date_of_birth, sex. Optional: phone
     * @param array $order    Required: id, procedure_code, procedure_name, modality. Optional: priority
     * @param array $visit    Optional: type (O/I), referring_doctor
     * @return string  Complete HL7 message (CR-delimited segments)
     */
    public function buildOrder(array $patient, array $order, array $visit = []): string
    {
        /*
        |--------------------------------------------------------------------------
        | Validate mandatory patient fields
        |--------------------------------------------------------------------------
        */
        if (empty($patient['id']))            throw new \Exception('Patient ID is required.');
        if (empty($patient['name']))          throw new \Exception('Patient name is required.');
        if (empty($patient['date_of_birth'])) throw new \Exception('Patient date_of_birth is required.');
        if (empty($patient['sex']))           throw new \Exception('Patient sex is required.');

        /*
        |--------------------------------------------------------------------------
        | Date of Birth → YYYYMMDD
        |--------------------------------------------------------------------------
        */
        $timestamp = strtotime($patient['date_of_birth']);
        if ($timestamp === false) {
            throw new \Exception('Invalid date_of_birth. Use YYYY-MM-DD format.');
        }
        $dob = date('Ymd', $timestamp);

        /*
        |--------------------------------------------------------------------------
        | Patient Name → HL7 XPN: FAMILY^GIVEN
        |--------------------------------------------------------------------------
        */
        $nameParts  = preg_split('/\s+/', trim($patient['name']));
        $givenName  = $nameParts[0] ?? '';
        $familyName = count($nameParts) > 1 ? end($nameParts) : '';
        $hl7Name    = $this->escape($familyName) . '^' . $this->escape($givenName);

        /*
        |--------------------------------------------------------------------------
        | Message Control ID + Timestamp
        |--------------------------------------------------------------------------
        */
        $msgControlId = 'MSG' . date('YmdHis') . random_int(100, 999);
        $dateTime     = date('YmdHis');

        /*
        |--------------------------------------------------------------------------
        | MSH — Message Header
        |--------------------------------------------------------------------------
        */
        $segments   = [];
        $segments[] =
            'MSH|^~\&|' .
            $this->escape($this->config['sending_application']) . '|' .
            $this->escape($this->config['sending_facility'])    . '|' .
            $this->escape($this->config['receiving_application']) . '||' .
            $dateTime . '||ORM^O01|' . $msgControlId . '||' .
            $this->config['hl7_version'];

        /*
        |--------------------------------------------------------------------------
        | PID — Patient Identification
        |--------------------------------------------------------------------------
        */
        $phone      = $patient['phone'] ?? '';
        $segments[] =
            'PID|1|' .
            $this->escape($patient['id']) . '|' .
            $this->escape($patient['id']) . '||' .
            $hl7Name . '||' .
            $dob . '|' .
            $this->escape($patient['sex']) . '||||' .
            $this->escape($phone);

        /*
        |--------------------------------------------------------------------------
        | PV1 — Patient Visit
        | PV1-2 = Patient Class (O=Outpatient, I=Inpatient)
        | PV1-8 = Referring Doctor
        |--------------------------------------------------------------------------
        */
        $visitType  = $visit['type'] ?? 'O';
        $doctor     = $visit['referring_doctor'] ?? '';
        $segments[] =
            'PV1|1|' .
            $this->escape($visitType) . '||||||' .
            $this->escape($doctor);

        /*
        |--------------------------------------------------------------------------
        | ORC — Common Order
        | ORC-1 = NW (New Order)
        | ORC-2 = Placer Order Number (GM_HMS order_id)
        | ORC-3 = Filler Order Number
        |--------------------------------------------------------------------------
        */
        $orderId    = $order['id'];
        $fillNumber = 'FILL-' . $orderId;
        $segments[] =
            'ORC|NW|' .
            $this->escape($orderId) . '|' .
            $this->escape($fillNumber);

        /*
        |--------------------------------------------------------------------------
        | OBR — Observation Request
        | OBR-1  = Set ID
        | OBR-2  = Placer Order Number
        | OBR-3  = Filler Order Number
        | OBR-4  = Universal Service ID (procedure_code^procedure_name)
        | OBR-5  = Priority (R=Routine, S=Stat)
        | OBR-24 = Diagnostic Modality (CT, MRI, X-RAY, USG, etc.)
        |--------------------------------------------------------------------------
        */
        $obrFields     = array_fill(0, 24, '');
        $obrFields[0]  = '1';
        $obrFields[1]  = $this->escape($orderId);
        $obrFields[2]  = $this->escape($fillNumber);
        $obrFields[3]  = $this->escape($order['procedure_code']) . '^' .
                         $this->escape($order['procedure_name']);
        $obrFields[4]  = $this->escape($order['priority'] ?? 'R');
        $obrFields[23] = $this->escape($order['modality']);  // index 23 = OBR-24

        $segments[] = 'OBR|' . implode('|', $obrFields);

        /*
        |--------------------------------------------------------------------------
        | Join segments with CR (HL7 segment terminator)
        |--------------------------------------------------------------------------
        */
        return implode("\r", $segments) . "\r";
    }

    /**
     * Escape HL7 special characters.
     * Prevents field/component separator injection.
     */
    private function escape(string $value): string
    {
        return str_replace(
            ['\\',    '|',     '^',     '&',     '~'],
            ['\\E\\', '\\F\\', '\\S\\', '\\T\\', '\\R\\'],
            $value
        );
    }
}
