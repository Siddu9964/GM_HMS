<?php
namespace GM_HMS\Controllers\api;

use GM_HMS\Database\SecureDatabase;

/**
 * IpdCatalogSearchController
 * Live real-time catalog search for IPD charges (Lab, Radiology, Pharmacy, Doctor, Procedures)
 * Automatically calculates service pricing based on patient's Room Type / Ward.
 */
class IpdCatalogSearchController extends IpdBaseController {
    private $db;

    public function __construct() {
        $this->db = SecureDatabase::getInstance();
    }

    protected function handleGet(): void {
        $type        = strtoupper(trim($this->getParam('type', 'LAB')));
        $query       = trim($this->getParam('q', ''));
        $roomType    = trim($this->getParam('room_type', ''));
        $admissionId = trim($this->getParam('admission_id', ''));

        // Resolve current bed / ward details if admission_id is provided
        $currentBedStr = '';
        if (!empty($admissionId)) {
            try {
                $admBed = $this->db->fetchOne("
                    SELECT ia.ward_name as adm_ward, ia.room_name as adm_room, ia.room_type as adm_type,
                           hb.ward_name, hb.room_name, hb.bed_number, hb.room_type
                    FROM ipd_admissions ia
                    LEFT JOIN hospital_beds hb ON ia.bed_id = hb.sl_no
                    WHERE ia.admission_id = ? LIMIT 1
                ", [$admissionId]);
                if ($admBed) {
                    $w = $admBed['ward_name'] ?: $admBed['adm_ward'] ?: 'General Ward';
                    $r = $admBed['room_name'] ?: $admBed['adm_room'] ?: '';
                    $b = $admBed['bed_number'] ?: '';
                    $parts = array_filter([$w, ($r !== $w && $r !== '') ? $r : '', $b ? "Bed {$b}" : '']);
                    $currentBedStr = implode(' - ', $parts);
                    if (empty($roomType)) {
                        $roomType = $admBed['room_type'] ?: $admBed['adm_type'] ?: $w;
                    }
                }
            } catch (\Exception $e) {}
        }
        if (empty($currentBedStr)) {
            $currentBedStr = $roomType ?: 'Present Room';
        }

        $results = [];

        try {
            switch ($type) {
                case 'ALL':
                case '':
                    $results = $this->searchAllServices($query, $roomType, $currentBedStr);
                    break;

                case 'LAB':
                case 'LABORATORY':
                    $results = $this->searchLabServices($query, $roomType);
                    break;

                case 'RADIOLOGY':
                    $results = $this->searchRadiologyServices($query, $roomType);
                    break;

                case 'PHARMACY':
                    $results = $this->searchPharmacyProducts($query);
                    break;

                case 'DOCTOR':
                case 'DOCTOR_VISIT':
                    $results = $this->searchDoctors($query);
                    break;

                case 'PROCEDURE':
                case 'OT':
                case 'OTHER':
                case 'CONSUMABLE':
                case 'MISC':
                case 'DIALYSIS':
                case 'OXYGEN':
                case 'VENTILATION':
                case 'VENTILATOR':
                case 'BLOOD_TRANSFUSION':
                case 'WARD_TRANSFER':
                    $results = $this->searchOtherServices($query, $roomType, $type, $currentBedStr, $admissionId);
                    break;

                case 'BED_UPGRADE_OVERRIDE':
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    $role = strtolower($_SESSION['role'] ?? '');
                    if ($role !== 'admin') {
                        $this->error('Bed Upgrade / Rate Override is restricted to Administrator access only.', 403);
                        return;
                    }
                    $results = $this->searchOtherServices($query, $roomType, $type, $currentBedStr, $admissionId);
                    break;

                case 'SPONSOR':
                case 'INSURANCE':
                case 'TPA':
                case 'TPS':
                    $results = $this->searchSponsors($query, $type);
                    break;

                default:
                    $this->error("Unsupported catalog type: {$type}", 400);
                    return;
            }

            $this->success($results, 'Search completed');
        } catch (\Throwable $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Search Lab Services with room-type pricing
     */
    private function searchLabServices(string $query, string $roomType): array {
        // Room type classification
        $rtLower = strtolower($roomType);
        if (strpos($rtLower, 'semi') !== false) {
            $priceCol = "COALESCE(`Semi Private Room`, `General Ward`, `opd_rate`, 0.00)";
            $tierName = "Semi Private";
        } elseif (strpos($rtLower, 'suite') !== false) {
            $priceCol = "COALESCE(`suite_rate`, `Private Room`, `General Ward`, `opd_rate`, 0.00)";
            $tierName = "Suite";
        } elseif (strpos($rtLower, 'private') !== false || strpos($rtLower, 'icu') !== false || strpos($rtLower, 'deluxe') !== false) {
            $priceCol = "COALESCE(`Private Room`, `Semi Private Room`, `General Ward`, `opd_rate`, 0.00)";
            $tierName = "Private Room";
        } else {
            $priceCol = "COALESCE(`General Ward`, `opd_rate`, 0.00)";
            $tierName = "General Ward";
        }

        if ($query === '') {
            $sql = "SELECT service_id as id, 
                           test_name as name, 
                           {$priceCol} as price, 
                           'LAB' as category, 
                           '{$tierName}' as room_tier,
                           'Laboratory' as department
                    FROM lab_services 
                    WHERE test_name IS NOT NULL AND test_name != ''
                    ORDER BY test_name ASC LIMIT 30";
            return $this->db->fetchAll($sql);
        }

        // Multi-word search for better match
        $words = preg_split('/\s+/', $query);
        $whereClauses = [];
        $params = [];
        foreach ($words as $w) {
            if ($w === '') continue;
            $whereClauses[] = "(test_name LIKE ? OR service_id LIKE ?)";
            $params[] = "%{$w}%";
            $params[] = "%{$w}%";
        }

        $whereSql = !empty($whereClauses) ? implode(' AND ', $whereClauses) : "1=1";

        $sql = "SELECT service_id as id, 
                       test_name as name, 
                       {$priceCol} as price, 
                       'LAB' as category, 
                       '{$tierName}' as room_tier,
                       'Laboratory' as department
                FROM lab_services 
                WHERE {$whereSql}
                ORDER BY CASE WHEN test_name LIKE ? THEN 0 ELSE 1 END, test_name ASC 
                LIMIT 30";
        $params[] = "{$query}%";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Search Radiology Services with room-type pricing
     */
    private function searchRadiologyServices(string $query, string $roomType): array {
        $rtLower = strtolower($roomType);
        if (strpos($rtLower, 'semi') !== false) {
            $priceCol = "COALESCE(semi_private_price, general_ward_price, opd_price, 0.00)";
            $tierName = "Semi Private";
        } elseif (strpos($rtLower, 'suite') !== false) {
            $priceCol = "COALESCE(suite_price, private_icu_price, general_ward_price, opd_price, 0.00)";
            $tierName = "Suite";
        } elseif (strpos($rtLower, 'private') !== false || strpos($rtLower, 'icu') !== false || strpos($rtLower, 'deluxe') !== false) {
            $priceCol = "COALESCE(private_icu_price, semi_private_price, general_ward_price, opd_price, 0.00)";
            $tierName = "Private / ICU";
        } else {
            $priceCol = "COALESCE(general_ward_price, opd_price, 0.00)";
            $tierName = "General Ward";
        }

        if ($query === '') {
            $sql = "SELECT service_id as id, 
                           billing_name as name, 
                           {$priceCol} as price, 
                           COALESCE(modality_name, 'RADIOLOGY') as category, 
                           '{$tierName}' as room_tier,
                           COALESCE(modality_name, 'Radiology') as department
                    FROM radiology_services 
                    WHERE billing_name IS NOT NULL AND billing_name != ''
                    ORDER BY billing_name ASC LIMIT 30";
            return $this->db->fetchAll($sql);
        }

        $words = preg_split('/\s+/', $query);
        $whereClauses = [];
        $params = [];
        foreach ($words as $w) {
            if ($w === '') continue;
            $whereClauses[] = "(billing_name LIKE ? OR service_id LIKE ? OR modality_name LIKE ?)";
            $params[] = "%{$w}%";
            $params[] = "%{$w}%";
            $params[] = "%{$w}%";
        }

        $whereSql = !empty($whereClauses) ? implode(' AND ', $whereClauses) : "1=1";

        $sql = "SELECT service_id as id, 
                       billing_name as name, 
                       {$priceCol} as price, 
                       COALESCE(modality_name, 'RADIOLOGY') as category, 
                       '{$tierName}' as room_tier,
                       COALESCE(modality_name, 'Radiology') as department
                FROM radiology_services 
                WHERE {$whereSql}
                ORDER BY CASE WHEN billing_name LIKE ? THEN 0 ELSE 1 END, billing_name ASC 
                LIMIT 30";
        $params[] = "{$query}%";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Search Pharmacy products
     */
    private function searchPharmacyProducts(string $query): array {
        if ($query === '') {
            $sql = "SELECT product_id as id, 
                           product_name as name, 
                           COALESCE(sales_price, mrp, 0.00) as price, 
                           batch_number as batch, 
                           quantity as stock, 
                           unit, 
                           content as generic, 
                           mrp 
                    FROM ph_product 
                    WHERE product_name IS NOT NULL AND product_name != ''
                    ORDER BY product_name ASC LIMIT 30";
            return $this->db->fetchAll($sql);
        }

        $words = preg_split('/\s+/', $query);
        $whereClauses = [];
        $params = [];
        foreach ($words as $w) {
            if ($w === '') continue;
            $whereClauses[] = "(product_name LIKE ? OR content LIKE ? OR product_id LIKE ? OR batch_number LIKE ?)";
            $params[] = "%{$w}%";
            $params[] = "%{$w}%";
            $params[] = "%{$w}%";
            $params[] = "%{$w}%";
        }

        $whereSql = !empty($whereClauses) ? implode(' AND ', $whereClauses) : "1=1";

        $sql = "SELECT product_id as id, 
                       product_name as name, 
                       COALESCE(sales_price, mrp, 0.00) as price, 
                       batch_number as batch, 
                       quantity as stock, 
                       unit, 
                       content as generic, 
                       mrp 
                FROM ph_product 
                WHERE {$whereSql}
                ORDER BY CASE WHEN product_name LIKE ? THEN 0 ELSE 1 END, product_name ASC 
                LIMIT 30";
        $params[] = "{$query}%";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Search Doctors with advanced multi-field query
     */
    private function searchDoctors(string $query): array {
        if ($query === '') {
            $sql = "SELECT doctor_id as id, 
                           full_name as name,
                           COALESCE(specialization, designation, 'General Medicine') as department,
                           COALESCE(designation, specialization, 'Consultant') as designation,
                           COALESCE(consultation_fee, 500.00) as price
                    FROM doctors 
                    WHERE status = 'Active' OR status IS NULL OR status = ''
                    ORDER BY full_name ASC LIMIT 30";
            return $this->db->fetchAll($sql);
        }

        $words = preg_split('/\s+/', $query);
        $whereClauses = [];
        $params = [];
        foreach ($words as $w) {
            if ($w === '') continue;
            $whereClauses[] = "(full_name LIKE ? OR doctor_id LIKE ? OR specialization LIKE ? OR designation LIKE ?)";
            $params[] = "%{$w}%";
            $params[] = "%{$w}%";
            $params[] = "%{$w}%";
            $params[] = "%{$w}%";
        }

        $whereSql = !empty($whereClauses) ? implode(' AND ', $whereClauses) : "1=1";

        $sql = "SELECT doctor_id as id, 
                       full_name as name,
                       COALESCE(specialization, designation, 'General Medicine') as department,
                       COALESCE(designation, specialization, 'Consultant') as designation,
                       COALESCE(consultation_fee, 500.00) as price
                FROM doctors 
                WHERE ({$whereSql}) AND (status = 'Active' OR status IS NULL OR status = '')
                ORDER BY CASE WHEN full_name LIKE ? THEN 0 ELSE 1 END, full_name ASC 
                LIMIT 30";
        $params[] = "{$query}%";

        $results = $this->db->fetchAll($sql, $params);

        // Fallback to staff if empty
        if (empty($results)) {
            $q = "%{$query}%";
            $sql = "SELECT sl_no as id, 
                           TRIM(CONCAT('Dr. ', COALESCE(first_name,''), ' ', COALESCE(last_name,''))) as name,
                           COALESCE(designation, role, 'General Medicine') as department,
                           COALESCE(designation, role, 'Consultant') as designation,
                           500.00 as price
                    FROM staff 
                    WHERE (first_name LIKE ? OR last_name LIKE ? OR designation LIKE ? OR role LIKE ?)
                    LIMIT 25";
            $results = $this->db->fetchAll($sql, [$q, $q, $q, $q]);
        }

        return $results;
    }

    /**
     * Search Other Services / Procedures with room-type pricing
     */
    private function searchOtherServices(string $query, string $roomType, string $specificType = 'PROCEDURE', string $currentBedStr = '', string $admissionId = ''): array {
        $rtLower = strtolower($roomType);
        if (strpos($rtLower, 'semi') !== false) {
            $priceCol = "COALESCE(`Semi Private Room`, op_gw_price, 0.00)";
            $tierName = "Semi Private";
        } elseif (strpos($rtLower, 'suite') !== false) {
            $priceCol = "COALESCE(suite_price, `Private Room`, op_gw_price, 0.00)";
            $tierName = "Suite";
        } elseif (strpos($rtLower, 'private') !== false || strpos($rtLower, 'icu') !== false || strpos($rtLower, 'deluxe') !== false) {
            $priceCol = "COALESCE(`Private Room`, `Semi Private Room`, op_gw_price, 0.00)";
            $tierName = "Private Room";
        } else {
            $priceCol = "COALESCE(op_gw_price, 0.00)";
            $tierName = "General Ward";
        }

        $specificType = strtoupper($specificType);

        // Predefined packages / services for specialized categories
        $specialDefaults = [];
        if ($specificType === 'DIALYSIS') {
            $specialDefaults = [
                ['id' => 'DIA001', 'name' => 'Hemodialysis (Per Session)', 'price' => 2500.00, 'category' => 'DIALYSIS', 'room_tier' => $tierName, 'department' => 'Dialysis Unit'],
                ['id' => 'DIA002', 'name' => 'Peritoneal Dialysis', 'price' => 3000.00, 'category' => 'DIALYSIS', 'room_tier' => $tierName, 'department' => 'Dialysis Unit'],
                ['id' => 'DIA003', 'name' => 'Continuous Renal Replacement Therapy (CRRT)', 'price' => 6500.00, 'category' => 'DIALYSIS', 'room_tier' => $tierName, 'department' => 'Dialysis Unit'],
                ['id' => 'DIA004', 'name' => 'Dialysis Dialyzer & Tubing Consumables', 'price' => 1200.00, 'category' => 'DIALYSIS', 'room_tier' => $tierName, 'department' => 'Dialysis Unit']
            ];
        } elseif ($specificType === 'OXYGEN') {
            $specialDefaults = [
                ['id' => 'OXY001', 'name' => 'Oxygen Therapy (Per Hour)', 'price' => 250.00, 'category' => 'OXYGEN', 'room_tier' => $tierName, 'department' => 'Respiratory Care'],
                ['id' => 'OXY002', 'name' => 'Oxygen Concentrator Support (Per Day)', 'price' => 800.00, 'category' => 'OXYGEN', 'room_tier' => $tierName, 'department' => 'Respiratory Care'],
                ['id' => 'OXY003', 'name' => 'High Flow Nasal Cannula (HFNC Oxygen Therapy)', 'price' => 1500.00, 'category' => 'OXYGEN', 'room_tier' => $tierName, 'department' => 'Respiratory Care'],
                ['id' => 'OXY004', 'name' => 'Emergency Oxygen Support (O2 Cylinder Administration)', 'price' => 500.00, 'category' => 'OXYGEN', 'room_tier' => $tierName, 'department' => 'Respiratory Care'],
                ['id' => 'OXY005', 'name' => 'Oxygen Inhalation / Face Mask Administration', 'price' => 350.00, 'category' => 'OXYGEN', 'room_tier' => $tierName, 'department' => 'Respiratory Care']
            ];
        } elseif ($specificType === 'VENTILATION' || $specificType === 'VENTILATOR') {
            $specialDefaults = [
                ['id' => 'VENT001', 'name' => 'Invasive Mechanical Ventilator Support (Per Day)', 'price' => 5000.00, 'category' => 'VENTILATION', 'room_tier' => $tierName, 'department' => 'Critical Care / ICU'],
                ['id' => 'VENT002', 'name' => 'Non-Invasive Ventilator (BiPAP / CPAP Per Day)', 'price' => 2000.00, 'category' => 'VENTILATION', 'room_tier' => $tierName, 'department' => 'Critical Care / ICU'],
                ['id' => 'VENT003', 'name' => 'SIMV / Pressure Support Ventilator Mode (Per Shift)', 'price' => 2500.00, 'category' => 'VENTILATION', 'room_tier' => $tierName, 'department' => 'Critical Care / ICU'],
                ['id' => 'VENT004', 'name' => 'Ventilator Circuit & Humidifier Kit', 'price' => 1800.00, 'category' => 'VENTILATION', 'room_tier' => $tierName, 'department' => 'Critical Care / ICU'],
                ['id' => 'VENT005', 'name' => 'Ventilator Weaning / Trial Monitoring', 'price' => 1200.00, 'category' => 'VENTILATION', 'room_tier' => $tierName, 'department' => 'Critical Care / ICU']
            ];
        } elseif ($specificType === 'BLOOD_TRANSFUSION') {
            $specialDefaults = [
                ['id' => 'BT001', 'name' => 'Blood Transfusion Charges (Per Unit / Bag)', 'price' => 1200.00, 'category' => 'BLOOD_TRANSFUSION', 'room_tier' => $tierName, 'department' => 'Transfusion Medicine'],
                ['id' => 'BT002', 'name' => 'Packed Red Blood Cells (PRBC) Transfusion', 'price' => 1500.00, 'category' => 'BLOOD_TRANSFUSION', 'room_tier' => $tierName, 'department' => 'Transfusion Medicine'],
                ['id' => 'BT003', 'name' => 'Fresh Frozen Plasma (FFP) Transfusion', 'price' => 1000.00, 'category' => 'BLOOD_TRANSFUSION', 'room_tier' => $tierName, 'department' => 'Transfusion Medicine'],
                ['id' => 'BT004', 'name' => 'Platelet Concentrate Transfusion', 'price' => 1200.00, 'category' => 'BLOOD_TRANSFUSION', 'room_tier' => $tierName, 'department' => 'Transfusion Medicine'],
                ['id' => 'BT005', 'name' => 'Blood Cross-Matching & Compatibility Testing', 'price' => 500.00, 'category' => 'BLOOD_TRANSFUSION', 'room_tier' => $tierName, 'department' => 'Transfusion Medicine'],
                ['id' => 'BT006', 'name' => 'Blood Transfusion Set & Filter Consumables', 'price' => 350.00, 'category' => 'BLOOD_TRANSFUSION', 'room_tier' => $tierName, 'department' => 'Transfusion Medicine']
            ];
        } elseif ($specificType === 'WARD_TRANSFER' || $specificType === 'BED_UPGRADE_OVERRIDE') {
            $specialDefaults = [];

            // Standard fallback room rates based on total_bed_amount (room + nursing + doctor charges)
            $getBedRent = function($roomType, $wardName, $amt) {
                if (!empty($amt) && floatval($amt) > 0) {
                    return floatval($amt);
                }
                $str = strtolower(($roomType ?: '') . ' ' . ($wardName ?: ''));
                if (strpos($str, 'icu') !== false) return 11000.00;
                if (strpos($str, 'ccu') !== false || strpos($str, 'cardiac') !== false) return 11000.00;
                if (strpos($str, 'deluxe') !== false || strpos($str, 'suite') !== false) return 7200.00;
                if (strpos($str, 'private') !== false && strpos($str, 'semi') === false) return 6700.00;
                if (strpos($str, 'semi') !== false) return 4100.00;
                if (strpos($str, 'double') !== false) return 3500.00;
                if (strpos($str, 'emergency') !== false) return 3000.00;
                if (strpos($str, 'general') !== false) return 3000.00;
                return 3000.00;
            };

            // ONLY Available hospital beds with clear bed numbers
            try {
                $excludeBedSql = "";
                $excludeBedParams = [];
                if (!empty($admissionId)) {
                    $currBedRow = $this->db->fetchOne("SELECT bed_id FROM ipd_admissions WHERE admission_id = ? LIMIT 1", [$admissionId]);
                    if (!empty($currBedRow['bed_id'])) {
                        $excludeBedSql = " AND sl_no != ?";
                        $excludeBedParams[] = $currBedRow['bed_id'];
                    }
                }

                $availBeds = $this->db->fetchAll("
                    SELECT sl_no, ward_name, room_name, bed_number, room_type, amount_per_day, nursig_charge, doctor_charge, service_charge, total_bed_amount
                    FROM hospital_beds
                    WHERE bed_status = 'Available' AND bed_number IS NOT NULL AND bed_number != '' {$excludeBedSql}
                    ORDER BY 
                      CASE 
                        WHEN room_type LIKE '%ICU%' THEN 1
                        WHEN room_type LIKE '%Deluxe%' THEN 2
                        WHEN room_type LIKE '%Private%' THEN 3
                        WHEN room_type LIKE '%Semi%' THEN 4
                        WHEN room_type LIKE '%Double%' THEN 5
                        WHEN room_type LIKE '%General%' THEN 6
                        ELSE 7
                      END,
                      ward_name, room_name, bed_number
                ", $excludeBedParams);

                if (!empty($availBeds)) {
                    foreach ($availBeds as $ab) {
                        // User requirement: Consider total_bed_amount column instead of amount_per_day
                        $totalBedAmount = !empty($ab['total_bed_amount']) && floatval($ab['total_bed_amount']) > 0 
                            ? floatval($ab['total_bed_amount']) 
                            : 0.0;
                        $roomRent = floatval($ab['amount_per_day'] ?? 0);
                        $nursingCharge = floatval($ab['nursig_charge'] ?? 0);
                        $doctorCharge = floatval($ab['doctor_charge'] ?? 0);

                        // If total_bed_amount is missing, compute from room + nursing + doctor
                        if ($totalBedAmount <= 0) {
                            $totalBedAmount = $roomRent + $nursingCharge + $doctorCharge;
                        }

                        $rent = $getBedRent($ab['room_type'], $ab['ward_name'], $totalBedAmount);
                        $rName = $ab['room_name'] ?: $ab['room_type'] ?: $ab['ward_name'];
                        $wName = $ab['ward_name'] ?: 'Ward';
                        $bedNum = trim($ab['bed_number']);

                        // Prominent Bed Number format
                        $roomInfo = ($rName !== $wName) ? "{$rName} ({$wName})" : $wName;
                        $bedTitle = "Bed {$bedNum} — {$roomInfo}";

                        // Department label showing total bed charge with breakdown
                        $breakdownParts = [];
                        if ($roomRent > 0) $breakdownParts[] = "Room: ₹" . number_format($roomRent, 0);
                        if ($nursingCharge > 0) $breakdownParts[] = "Nursing: ₹" . number_format($nursingCharge, 0);
                        if ($doctorCharge > 0) $breakdownParts[] = "Doctor: ₹" . number_format($doctorCharge, 0);
                        $breakdownText = !empty($breakdownParts) ? " (" . implode(' + ', $breakdownParts) . ")" : "";
                        $deptLabel = "Total Bed Charge: ₹" . number_format($rent, 2) . "/day" . $breakdownText;

                        $specialDefaults[] = [
                            'id'              => 'BED_' . $ab['sl_no'],
                            'name'            => $bedTitle,
                            'price'           => $rent, // Considering total_bed_amount!
                            'category'        => $specificType,
                            'room_tier'       => $ab['room_type'] ?: 'Available Bed',
                            'department'      => $deptLabel,
                            'bed_number'      => $bedNum,
                            'room_name'       => $rName,
                            'ward_name'       => $wName,
                            'room_type'       => $ab['room_type'] ?: $rName,
                            'destination_bed' => "Bed {$bedNum} ({$rName})",
                            'room_rent'       => $rent,
                            'amount_per_day'  => $roomRent,
                            'nursing_charge'  => $nursingCharge,
                            'doctor_charge'   => $doctorCharge,
                            'total_bed_amount'=> $rent
                        ];
                    }
                }
            } catch (\Throwable $e) {}
        }

        $specializedTypes = [
            'WARD_TRANSFER',
            'BED_UPGRADE_OVERRIDE',
            'DIALYSIS',
            'OXYGEN',
            'VENTILATION',
            'VENTILATOR',
            'BLOOD_TRANSFUSION'
        ];

        // 1. If searching within a specialized category, do NOT return unrelated procedures
        if (in_array($specificType, $specializedTypes)) {
            $keyword = '';
            if ($specificType === 'WARD_TRANSFER' || $specificType === 'BED_UPGRADE_OVERRIDE') $keyword = ''; // Strictly show available beds
            elseif ($specificType === 'DIALYSIS') $keyword = 'dialysis';
            elseif ($specificType === 'OXYGEN') $keyword = 'oxygen';
            elseif ($specificType === 'VENTILATION' || $specificType === 'VENTILATOR') $keyword = 'ventilat';
            elseif ($specificType === 'BLOOD_TRANSFUSION') $keyword = 'transfusion';

            $dbRows = [];
            if ($keyword !== '') {
                $kwParams = ["%{$keyword}%"];
                $kwSql = "SELECT service_id as id, billing_name as name, {$priceCol} as price, '{$specificType}' as category, '{$tierName}' as room_tier, 'Procedure' as department FROM other_services WHERE (billing_name LIKE ?)";
                if ($query !== '') {
                    $kwSql .= " AND billing_name LIKE ?";
                    $kwParams[] = "%{$query}%";
                }
                $dbRows = $this->db->fetchAll($kwSql . " LIMIT 15", $kwParams) ?: [];
            }

            $matchedSpecial = [];
            if ($query === '') {
                $matchedSpecial = $specialDefaults;
            } else {
                $qWords = preg_split('/\s+/', strtolower(trim($query)));
                $aliasMap = [
                    'wrd'   => 'ward',
                    'shift' => 'shifting',
                    'vent'  => 'ventilator',
                    'o2'    => 'oxygen',
                    'bt'    => 'blood',
                    'trans' => 'transfusion'
                ];
                foreach ($specialDefaults as $sd) {
                    $sdText = strtolower(($sd['name'] ?? '') . ' ' . ($sd['id'] ?? '') . ' ' . ($sd['department'] ?? '') . ' ' . ($sd['category'] ?? '') . ' ' . ($sd['room_type'] ?? '') . ' ' . ($sd['ward_name'] ?? '') . ' ' . ($sd['bed_number'] ?? ''));
                    $match = false;
                    foreach ($qWords as $qw) {
                        if ($qw === '') continue;
                        $alt = $aliasMap[$qw] ?? $qw;
                        if (strpos($sdText, $qw) !== false || strpos($sdText, $alt) !== false) {
                            $match = true;
                            break;
                        }
                    }
                    if ($match) {
                        $matchedSpecial[] = $sd;
                    }
                }
            }

            return array_merge($matchedSpecial, $dbRows);
        }

        // 2. Consumables specific filtering
        if ($specificType === 'CONSUMABLE') {
            $conKw = "(billing_name LIKE '%cannula%' OR billing_name LIKE '%catheter%' OR billing_name LIKE '%syringe%' OR billing_name LIKE '%glove%' OR billing_name LIKE '%dressing%' OR billing_name LIKE '%needle%' OR billing_name LIKE '%cotton%' OR billing_name LIKE '%gauze%' OR billing_name LIKE '%tape%' OR billing_name LIKE '%tube%' OR billing_name LIKE '%mask%' OR billing_name LIKE '%kit%' OR billing_name LIKE '%consumable%')";
            $params = [];
            if ($query !== '') {
                $conKw .= " AND (billing_name LIKE ? OR service_id LIKE ?)";
                $params[] = "%{$query}%";
                $params[] = "%{$query}%";
            }
            $sql = "SELECT service_id as id, billing_name as name, {$priceCol} as price, 'CONSUMABLE' as category, '{$tierName}' as room_tier, 'Consumables' as department FROM other_services WHERE {$conKw} ORDER BY billing_name ASC LIMIT 30";
            return $this->db->fetchAll($sql, $params) ?: [];
        }

        // 3. General Procedures / Other Services
        if ($query === '') {
            $sql = "SELECT service_id as id, 
                           billing_name as name, 
                           {$priceCol} as price, 
                           '{$specificType}' as category,
                           '{$tierName}' as room_tier,
                           'Procedure' as department
                    FROM other_services 
                    WHERE billing_name IS NOT NULL AND billing_name != ''
                    ORDER BY billing_name ASC LIMIT 30";
            return $this->db->fetchAll($sql);
        }

        $words = preg_split('/\s+/', $query);
        $whereClauses = [];
        $params = [];
        foreach ($words as $w) {
            if ($w === '') continue;
            $whereClauses[] = "(billing_name LIKE ? OR service_id LIKE ?)";
            $params[] = "%{$w}%";
            $params[] = "%{$w}%";
        }

        $whereSql = !empty($whereClauses) ? implode(' AND ', $whereClauses) : "1=1";

        $sql = "SELECT service_id as id, 
                       billing_name as name, 
                       {$priceCol} as price, 
                       '{$specificType}' as category,
                       '{$tierName}' as room_tier,
                       'Procedure' as department
                FROM other_services 
                WHERE {$whereSql}
                ORDER BY CASE WHEN billing_name LIKE ? THEN 0 ELSE 1 END, billing_name ASC 
                LIMIT 30";
        $params[] = "{$query}%";

        return $this->db->fetchAll($sql, $params) ?: [];
    }

    /**
     * Search Sponsors / Insurance / TPA from sponsors_data and standard catalog
     */
    private function searchSponsors(string $query, string $type): array {
        $targetType = (in_array(strtoupper($type), ['TPA', 'TPS'])) ? 'TPA' : 'Insurance';

        $standardCatalog = [
            'Insurance' => [
                'Star Health & Allied Insurance',
                'HDFC ERGO General Insurance',
                'ICICI Lombard General Insurance',
                'Care Health Insurance (Religare)',
                'Niva Bupa Health Insurance (Max Bupa)',
                'Bajaj Allianz General Insurance',
                'Tata AIG General Insurance',
                'Aditya Birla Health Insurance',
                'SBI General Insurance',
                'United India Insurance Company',
                'The New India Assurance Company',
                'National Insurance Company',
                'Oriental Insurance Company',
                'Reliance General Insurance',
                'Chola MS General Insurance',
                'Go Digit General Insurance',
                'Navi General Insurance',
                'ManipalCigna Health Insurance',
                'Future Generali India Insurance',
                'Royal Sundaram General Insurance',
                'IFFCO Tokio General Insurance',
                'Universal Sompo General Insurance',
                'Magma HDI General Insurance',
                'Kotak Mahindra General Insurance'
            ],
            'TPA' => [
                'Medi Assist Insurance TPA',
                'Paramount Health Services & Insurance TPA',
                'Vidal Health Insurance TPA',
                'MDIndia Health Insurance TPA',
                'Heritage Health Insurance TPA',
                'Raksha Health Insurance TPA',
                'Health India Insurance TPA',
                'Family Health Plan Insurance TPA',
                'Vipul MedCorp Insurance TPA',
                'Dedicated Healthcare Services TPA',
                'Genins India Insurance TPA',
                'Park Mediclaim TPA',
                'Ericson Insurance TPA',
                'Safe Way Insurance TPA',
                'Alankit Insurance TPA',
                'Good Health Insurance TPA'
            ]
        ];

        $dbResults = [];
        try {
            if ($query === '') {
                $sql = "SELECT sl_no as id, 
                               sponsor_name as name, 
                               sponsor_type, 
                               tpa_name, 
                               status 
                        FROM sponsors_data 
                        WHERE sponsor_type LIKE ? AND sponsor_name IS NOT NULL AND sponsor_name != ''
                        ORDER BY sponsor_name ASC LIMIT 50";
                $dbResults = $this->db->fetchAll($sql, ["%{$targetType}%"]);
            } else {
                $sql = "SELECT sl_no as id, 
                               sponsor_name as name, 
                               sponsor_type, 
                               tpa_name, 
                               status 
                        FROM sponsors_data 
                        WHERE sponsor_type LIKE ? AND sponsor_name LIKE ? 
                        ORDER BY CASE WHEN sponsor_name LIKE ? THEN 0 ELSE 1 END, sponsor_name ASC 
                        LIMIT 50";
                $dbResults = $this->db->fetchAll($sql, ["%{$targetType}%", "%{$query}%", "{$query}%"]);
            }
        } catch (\Throwable $e) {
            $dbResults = [];
        }

        $existingNames = [];
        $finalResults = [];

        foreach ($dbResults as $r) {
            $cleanName = trim($r['name']);
            $norm = strtolower($cleanName);
            if (!isset($existingNames[$norm])) {
                $existingNames[$norm] = true;
                $finalResults[] = [
                    'id'           => $r['id'],
                    'name'         => $cleanName,
                    'sponsor_type' => $r['sponsor_type'] ?: $targetType,
                    'tpa_name'     => $r['tpa_name'] ?? null,
                    'status'       => $r['status'] ?? 'Active'
                ];
            }
        }

        // Merge standard catalog items
        $catalogList = $standardCatalog[$targetType] ?? [];
        $queryLower = strtolower(trim($query));

        foreach ($catalogList as $item) {
            $itemLower = strtolower($item);
            if ($queryLower === '' || strpos($itemLower, $queryLower) !== false) {
                // Deduplicate with normalized comparison
                $isDupe = false;
                foreach (array_keys($existingNames) as $existing) {
                    if (strpos($itemLower, $existing) !== false || strpos($existing, $itemLower) !== false) {
                        $isDupe = true;
                        break;
                    }
                }
                if (!$isDupe) {
                    $existingNames[$itemLower] = true;
                    $finalResults[] = [
                        'id'           => 'std_' . substr(md5($item), 0, 8),
                        'name'         => $item,
                        'sponsor_type' => $targetType,
                        'tpa_name'     => null,
                        'status'       => 'Active'
                    ];
                }
            }
        }

        return $finalResults;
    }

    /**
     * Universal search across all charge categories (Procedures, Lab, Radiology, Pharmacy, Doctors)
     */
    private function searchAllServices(string $query, string $roomType, string $currentBedStr = ''): array {
        $results = [];

        // 1. Procedures / Other Services (e.g. GRBS, ECG, ECHO, Dressing, Injection, etc.)
        $oth = $this->searchOtherServices($query, $roomType, 'PROCEDURE');
        if (!empty($oth)) {
            $results = array_merge($results, array_slice($oth, 0, 10));
        }

        // 2. Lab Services (e.g. Blood tests, Urine tests, CBC, Glucose, etc.)
        $lab = $this->searchLabServices($query, $roomType);
        if (!empty($lab)) {
            foreach ($lab as &$item) {
                $item['category'] = 'LAB';
            }
            $results = array_merge($results, array_slice($lab, 0, 10));
        }

        // 3. Radiology Services (e.g. X-Ray, CT Scan, MRI, Ultrasound, etc.)
        $rad = $this->searchRadiologyServices($query, $roomType);
        if (!empty($rad)) {
            foreach ($rad as &$item) {
                $item['category'] = 'RADIOLOGY';
            }
            $results = array_merge($results, array_slice($rad, 0, 8));
        }

        // 4. Pharmacy Products
        $ph = $this->searchPharmacyProducts($query);
        if (!empty($ph)) {
            foreach ($ph as &$item) {
                $item['category'] = 'PHARMACY';
                $item['department'] = 'Pharmacy';
            }
            $results = array_merge($results, array_slice($ph, 0, 5));
        }

        // 5. Doctors (Consultation)
        $doc = $this->searchDoctors($query);
        if (!empty($doc)) {
            foreach ($doc as &$item) {
                $item['category'] = 'DOCTOR_VISIT';
            }
            $results = array_merge($results, array_slice($doc, 0, 5));
        }

        // 6. Check special categories by keyword
        $qLower = strtolower($query);
        if (strpos($qLower, 'dialy') !== false) {
            $dia = $this->searchOtherServices($query, $roomType, 'DIALYSIS');
            $results = array_merge($results, array_slice($dia, 0, 5));
        }
        if (strpos($qLower, 'oxy') !== false || strpos($qLower, 'o2') !== false || strpos($qLower, 'cannula') !== false) {
            $oxy = $this->searchOtherServices($query, $roomType, 'OXYGEN');
            $results = array_merge($results, array_slice($oxy, 0, 5));
        }
        if (strpos($qLower, 'vent') !== false || strpos($qLower, 'bipap') !== false || strpos($qLower, 'cpap') !== false || strpos($qLower, 'simv') !== false) {
            $vent = $this->searchOtherServices($query, $roomType, 'VENTILATION');
            $results = array_merge($results, array_slice($vent, 0, 5));
        }
        if (strpos($qLower, 'blood') !== false || strpos($qLower, 'transfus') !== false || strpos($qLower, 'prbc') !== false || strpos($qLower, 'ffp') !== false || strpos($qLower, 'platelet') !== false) {
            $bt = $this->searchOtherServices($query, $roomType, 'BLOOD_TRANSFUSION');
            $results = array_merge($results, array_slice($bt, 0, 5));
        }
        if (strpos($qLower, 'shift') !== false || strpos($qLower, 'transfer') !== false || strpos($qLower, 'wrd') !== false || strpos($qLower, 'ward') !== false || strpos($qLower, 'bed') !== false || strpos($qLower, 'room') !== false) {
            $wt = $this->searchOtherServices($query, $roomType, 'WARD_TRANSFER', $currentBedStr);
            $results = array_merge($results, array_slice($wt, 0, 5));
        }

        // Deduplicate
        $unique = [];
        $seen = [];
        foreach ($results as $r) {
            $key = ($r['category'] ?? '') . '_' . ($r['name'] ?? '');
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $r;
            }
        }

        // Relevance sort
        if ($query !== '') {
            usort($unique, function($a, $b) use ($query) {
                $aStarts = (stripos($a['name'] ?? '', $query) === 0) ? 0 : 1;
                $bStarts = (stripos($b['name'] ?? '', $query) === 0) ? 0 : 1;
                if ($aStarts !== $bStarts) return $aStarts - $bStarts;
                return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
            });
        }

        return array_slice($unique, 0, 30);
    }
}


