<?php
namespace GM_HMS\Models;

use GM_HMS\Database\SecureDatabase;
use Exception;

class NewIpdBillingModel {
    private $db;
    
    public function __construct() {
        $this->db = SecureDatabase::getInstance();
    }
    
    public function getAdmissionBillingDetails($admissionId) {
        // 1. Fetch admission & patient details
        $admSql = "SELECT a.*, p.*, a.room_name AS room_type 
                   FROM ipd_admissions a 
                   LEFT JOIN patient p ON a.patient_id = p.patient_id
                   WHERE a.admission_id = ?";
        
        $admission = $this->db->fetchOne($admSql, [$admissionId]);
        
        if (!$admission) {
            throw new Exception("Admission not found");
        }
        
        $roomType = strtolower($admission['room_type'] ?? 'general_ward');
        
        $billingItems = [];
        
        $clinicalSql = "SELECT lab_tests, radiology_tests, other_tests 
                        FROM ipd_clinical_records 
                        WHERE admission_id = ? 
                        ORDER BY id DESC LIMIT 1";
        $clinicalRecord = $this->db->fetchOne($clinicalSql, [$admissionId]);
        
        if ($clinicalRecord) {
            $this->extractAndLookupTests($clinicalRecord['lab_tests'], 'lab_services', $roomType, $billingItems);
            $this->extractAndLookupTests($clinicalRecord['radiology_tests'], 'radiology_services', $roomType, $billingItems);
            $this->extractAndLookupTests($clinicalRecord['other_tests'], 'other_services', $roomType, $billingItems);
        }
        
        return [
            'admission' => $admission,
            'billing_items' => $billingItems
        ];
    }
    
    private function extractAndLookupTests($jsonString, $tableName, $roomType, &$billingItems) {
        if (empty($jsonString)) return;
        
        $decoded = json_decode($jsonString, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) return;
        
        foreach ($decoded as $item) {
            if (isset($item['status']) && strtolower($item['status']) !== 'active') {
                continue;
            }
            
            if (isset($item['data']) && is_array($item['data'])) {
                $testId = $item['data']['id'] ?? null;
                $testName = $item['data']['name'] ?? '';
                $qty = isset($item['data']['qty']) ? (int)$item['data']['qty'] : 1;
                $category = $item['data']['category'] ?? 'Test';
                
                if (empty($testId) && empty($testName)) continue;
                
                // Determine actual table based on category since frontend might save XRAY in other_tests
                $actualTableName = $tableName;
                $catLower = strtolower($category);
                if (strpos($catLower, 'x ray') !== false || strpos($catLower, 'radiology') !== false || strpos($catLower, 'scan') !== false || strpos($catLower, 'mri') !== false || strpos($catLower, 'ct ') !== false) {
                    $actualTableName = 'radiology_services';
                } elseif ($catLower === 'other' || $catLower === 'others') {
                    $actualTableName = 'other_services';
                } elseif ($catLower === 'lab' || $catLower === 'pathology') {
                    $actualTableName = 'lab_services';
                }
                
                $serviceData = $this->lookupServiceData($testId, $testName, $actualTableName, $roomType);
                
                $billingItems[] = [
                    'category' => $category,
                    'item_name' => !empty($serviceData['name']) ? $serviceData['name'] : $testName,
                    'quantity' => $qty,
                    'unit_price' => (float)$serviceData['price']
                ];
            }
        }
    }
    
    private function lookupServiceData($testId, $testName, $tableName, $roomType) {
        $rateColumn = '`General Ward`'; // default fallback for all tables now
        
        if ($tableName === 'lab_services') {
            if (strpos($roomType, 'semi') !== false) {
                $rateColumn = '`Semi Private Room`';
            } elseif (strpos($roomType, 'private') !== false || strpos($roomType, 'icu') !== false || strpos($roomType, 'ccu') !== false || strpos($roomType, 'suite') !== false) {
                $rateColumn = '`Private Room`';
            } else {
                $rateColumn = '`General Ward`';
            }
        } elseif ($tableName === 'other_services') {
            if (strpos($roomType, 'semi') !== false) {
                $rateColumn = '`Semi Private Room`';
            } elseif (strpos($roomType, 'private') !== false || strpos($roomType, 'icu') !== false || strpos($roomType, 'ccu') !== false || strpos($roomType, 'suite') !== false) {
                $rateColumn = '`Private Room`';
            } else {
                $rateColumn = '`op_gw_price`';
            }
        } elseif ($tableName === 'radiology_services') {
            if (strpos($roomType, 'semi') !== false) {
                $rateColumn = '`Semi Private Room`';
            } elseif (strpos($roomType, 'private') !== false || strpos($roomType, 'icu') !== false || strpos($roomType, 'ccu') !== false) {
                $rateColumn = '`Private Room`';
            } elseif (strpos($roomType, 'suite') !== false) {
                // If they have suite_price use it, otherwise fallback to Private Room
                $rateColumn = '`suite_price`';
            } else {
                $rateColumn = '`General Ward`';
            }
        }
        
        $nameColumn = ($tableName === 'radiology_services' || $tableName === 'other_services') ? 'billing_name' : 'test_name';
        
        $sqlById = "SELECT {$rateColumn} as price, {$nameColumn} as name FROM {$tableName} WHERE service_id = ?";
        
        try {
            if (!empty($testId)) {
                $result = $this->db->fetchOne($sqlById, [$testId]);
                if ($result) return ['price' => $result['price'], 'name' => $result['name']];
            }
        } catch (\Exception $e) {
            // ID lookup failed, fallback to name
        }
        
        // Fallback: match by name
        $sqlByName = "SELECT {$rateColumn} as price, {$nameColumn} as name FROM {$tableName} WHERE {$nameColumn} = ?";
        if ($tableName === 'lab_services') {
            $sqlByName = "SELECT {$rateColumn} as price, {$nameColumn} as name FROM {$tableName} WHERE {$nameColumn} = ? OR test_name = ?";
        }
        
        try {
            if ($tableName === 'lab_services') {
                $result = $this->db->fetchOne($sqlByName, [$testName, $testName]);
            } else {
                $result = $this->db->fetchOne($sqlByName, [$testName]);
            }
            if ($result) return ['price' => $result['price'], 'name' => $result['name']];
        } catch (\Exception $e) {
            // Might fail if test_name column does not exist
            try {
                $sqlFallback = "SELECT {$rateColumn} as price, {$nameColumn} as name FROM {$tableName} WHERE {$nameColumn} = ?";
                $result = $this->db->fetchOne($sqlFallback, [$testName]);
                if ($result) return ['price' => $result['price'], 'name' => $result['name']];
            } catch (\Exception $e2) {
                return ['price' => 0, 'name' => ''];
            }
        }
        
        return ['price' => 0, 'name' => ''];
    }
}
