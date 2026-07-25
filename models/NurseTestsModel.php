<?php
/**
 * Nurse Tests Model
 * Handles querying lab, radiology, and other services
 * 
 * @package GM_HMS\Models
 * @version 1.0.0
 */

namespace GM_HMS\Models;

use GM_HMS\Database\SecureDatabase;
use Exception;

class NurseTestsModel {
    private $db;
    
    public function __construct() {
        $this->db = SecureDatabase::getInstance();
    }
    
    public function searchLabTests($query) {
        $q = "%{$query}%";
        $sql = "SELECT service_id as id, test_name as name, opd_rate as price, 'Lab' as category 
                FROM lab_services 
                WHERE test_name LIKE ? OR service_id LIKE ? 
                LIMIT 20";
        return $this->db->fetchAll($sql, [$q, $q]);
    }
    
    public function searchRadiology($query) {
        $q = "%{$query}%";
        $sql = "SELECT service_id as id, billing_name as name, opd_price as price, modality_name as category 
                FROM radiology_services 
                WHERE billing_name LIKE ? OR service_id LIKE ? 
                LIMIT 20";
        return $this->db->fetchAll($sql, [$q, $q]);
    }
    
    public function searchOther($query) {
        $q = "%{$query}%";
        $sql = "SELECT service_id as id, billing_name as name, op_gw_price as price, 'Other' as category 
                FROM other_services 
                WHERE billing_name LIKE ? OR service_id LIKE ? 
                LIMIT 20";
        return $this->db->fetchAll($sql, [$q, $q]);
    }
    
    public function searchAllTests($query) {
        $q = "%{$query}%";
        
        $lab = "SELECT service_id as id, test_name as name, opd_rate as price, 'Lab' as category 
                FROM lab_services WHERE test_name LIKE ? OR service_id LIKE ? LIMIT 10";
                
        $rad = "SELECT service_id as id, billing_name as name, opd_price as price, modality_name as category 
                FROM radiology_services WHERE billing_name LIKE ? OR service_id LIKE ? LIMIT 10";
                
        $oth = "SELECT service_id as id, billing_name as name, op_gw_price as price, 'Other' as category 
                FROM other_services WHERE billing_name LIKE ? OR service_id LIKE ? LIMIT 10";
                
        $results = [];
        $results = array_merge($results, $this->db->fetchAll($lab, [$q, $q]));
        $results = array_merge($results, $this->db->fetchAll($rad, [$q, $q]));
        $results = array_merge($results, $this->db->fetchAll($oth, [$q, $q]));
        
        return $results;
    }
}
