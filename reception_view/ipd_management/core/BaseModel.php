<?php
/**
 * Base Model Class for IPD Management System
 * 
 * Provides common database operations and utilities for all models
 * Extends SecureDatabase functionality with IPD-specific features
 * 
 * @package IPD_Management
 * @author GM HMS Development Team
 * @version 1.0.0
 */

// Database connection is handled by the central autoloader or manual include in standalone scripts
// require_once __DIR__ . '/../../../Database/DB.php';

use GM_HMS\Database\SecureDatabase;

abstract class BaseModel {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $timestamps = true;
    
    public function __construct() {
        $this->db = SecureDatabase::getInstance();
    }
    
    /**
     * Get all records with optional filters
     * 
     * @param array $filters Associative array of column => value
     * @param int $limit Limit number of results
     * @param int $offset Offset for pagination
     * @param string $orderBy Order by column
     * @param string $orderDir Order direction (ASC/DESC)
     * @return array Array of records
     */
    public function getAll($filters = [], $limit = null, $offset = 0, $orderBy = null, $orderDir = 'ASC') {
        $query = "SELECT * FROM `{$this->table}` WHERE 1=1";
        $params = [];
        
        // Apply filters
        foreach ($filters as $column => $value) {
            if ($value !== null && $value !== '') {
                $query .= " AND `{$column}` = ?";
                $params[] = $value;
            }
        }
        
        // Order by
        if ($orderBy) {
            $query .= " ORDER BY `{$orderBy}` {$orderDir}";
        }
        
        // Limit and offset
        if ($limit) {
            $query .= " LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;
        }
        
        return $this->db->fetchAll($query, $params);
    }
    
    /**
     * Get single record by ID
     * 
     * @param int $id Primary key value
     * @return array|null Record or null if not found
     */
    public function getById($id) {
        $query = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?";
        return $this->db->fetchOne($query, [$id]);
    }
    
    /**
     * Create new record
     * 
     * @param array $data Associative array of column => value
     * @return int Insert ID
     */
    public function create($data) {
        // Add timestamps if enabled
        if ($this->timestamps) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->insert($this->table, $data);
    }
    
    /**
     * Update record by ID
     * 
     * @param int $id Primary key value
     * @param array $data Associative array of column => value
     * @return int Affected rows
     */
    public function update($id, $data) {
        // Add updated timestamp if enabled
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $where = "`{$this->primaryKey}` = ?";
        return $this->db->update($this->table, $data, $where, [$id]);
    }
    
    /**
     * Delete record by ID
     * 
     * @param int $id Primary key value
     * @return int Affected rows
     */
    public function delete($id) {
        $where = "`{$this->primaryKey}` = ?";
        return $this->db->delete($this->table, $where, [$id]);
    }
    
    /**
     * Count records with optional filters
     * 
     * @param array $filters Associative array of column => value
     * @return int Count
     */
    public function count($filters = []) {
        $query = "SELECT COUNT(*) as count FROM `{$this->table}` WHERE 1=1";
        $params = [];
        
        foreach ($filters as $column => $value) {
            if ($value !== null && $value !== '') {
                $query .= " AND `{$column}` = ?";
                $params[] = $value;
            }
        }
        
        $result = $this->db->fetchOne($query, $params);
        return (int)$result['count'];
    }
    
    /**
     * Search records by keyword in specified columns
     * 
     * @param string $keyword Search keyword
     * @param array $columns Columns to search in
     * @param int $limit Limit results
     * @return array Array of records
     */
    public function search($keyword, $columns = [], $limit = 50) {
        if (empty($columns) || empty($keyword)) {
            return [];
        }
        
        $query = "SELECT * FROM `{$this->table}` WHERE ";
        $conditions = [];
        $params = [];
        
        foreach ($columns as $column) {
            $conditions[] = "`{$column}` LIKE ?";
            $params[] = "%{$keyword}%";
        }
        
        $query .= implode(' OR ', $conditions);
        $query .= " LIMIT ?";
        $params[] = (int)$limit;
        
        return $this->db->fetchAll($query, $params);
    }
    
    /**
     * Execute custom query
     * 
     * @param string $query SQL query
     * @param array $params Parameters
     * @return mixed Query result
     */
    protected function query($query, $params = []) {
        return $this->db->execute($query, $params);
    }
    
    /**
     * Fetch all from custom query
     * 
     * @param string $query SQL query
     * @param array $params Parameters
     * @return array Results
     */
    protected function fetchAll($query, $params = []) {
        return $this->db->fetchAll($query, $params);
    }
    
    /**
     * Fetch one from custom query
     * 
     * @param string $query SQL query
     * @param array $params Parameters
     * @return array|null Result
     */
    protected function fetchOne($query, $params = []) {
        return $this->db->fetchOne($query, $params);
    }
    
    /**
     * Begin database transaction
     */
    public function beginTransaction() {
        $this->db->beginTransaction();
    }
    
    /**
     * Commit database transaction
     */
    public function commit() {
        $this->db->commit();
    }
    
    /**
     * Rollback database transaction
     */
    public function rollback() {
        $this->db->rollback();
    }
    
    /**
     * Validate required fields
     * 
     * @param array $data Data to validate
     * @param array $required Required field names
     * @return array Array of error messages (empty if valid)
     */
    protected function validateRequired($data, $required) {
        $errors = [];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        return $errors;
    }
    
    /**
     * Sanitize data for database insertion
     * 
     * @param array $data Data to sanitize
     * @param array $allowed Allowed field names
     * @return array Sanitized data
     */
    protected function sanitize($data, $allowed) {
        $sanitized = [];
        
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $sanitized[$field] = $data[$field];
            }
        }
        
        return $sanitized;
    }
}
