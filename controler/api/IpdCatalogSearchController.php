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

        // If room_type is empty but admission_id is provided, resolve from ipd_admissions
        if (empty($roomType) && !empty($admissionId)) {
            try {
                $adm = $this->db->fetchOne("SELECT room_type, ward, ward_name, room_name FROM ipd_admissions WHERE admission_id = ? LIMIT 1", [$admissionId]);
                if ($adm) {
                    $roomType = $adm['room_type'] ?: $adm['ward_name'] ?: $adm['ward'] ?: $adm['room_name'] ?: 'General Ward';
                }
            } catch (\Exception $e) {}
        }

        $results = [];

        try {
            switch ($type) {
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
                    $results = $this->searchOtherServices($query, $roomType);
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
    private function searchOtherServices(string $query, string $roomType): array {
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

        if ($query === '') {
            $sql = "SELECT service_id as id, 
                           billing_name as name, 
                           {$priceCol} as price, 
                           'PROCEDURE' as category,
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
                       'PROCEDURE' as category,
                       '{$tierName}' as room_tier,
                       'Procedure' as department
                FROM other_services 
                WHERE {$whereSql}
                ORDER BY CASE WHEN billing_name LIKE ? THEN 0 ELSE 1 END, billing_name ASC 
                LIMIT 30";
        $params[] = "{$query}%";

        return $this->db->fetchAll($sql, $params);
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
}


