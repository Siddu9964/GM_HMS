<?php
/**
 * Database Connection and Query Wrapper Class
 * 
 * Provides a simple PDO wrapper for database operations with
 * prepared statements, transactions, and error handling.
 * 
 * @package GM_HMS
 * @version 2.0.0
 * @deprecated Use SecureDatabase class instead for new code
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'hmsci';
    private $username = 'root';
    private $password = '';
    private $conn;
    
    /**
     * Establish database connection
     * 
     * @return Database Current instance for method chaining
     * @throws Exception If connection fails
     */
    public function connect() {
        if ($this->conn !== null) {
            return $this;
        }
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $branch = $_SERVER['HTTP_X_HOSPITAL_BRANCH'] ?? $_SESSION['hospital_branch'] ?? $_SESSION['branch'] ?? '';
        if (strtolower($branch) === 'basaveshwaranagar') {
            $this->db_name = 'hmsc_basaveshwranagara';
        }
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            throw new Exception("Connection Error: " . $e->getMessage());
        }
        
        return $this;
    }
    
    /**
     * Execute a query and return all results
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters for prepared statement
     * @return array Array of result rows
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Execute a query and return one result
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters for prepared statement
     * @return array|false Single result row or false
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Execute a query (INSERT, UPDATE, DELETE)
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters for prepared statement
     * @return bool Success status
     */
    public function execute($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Get last insert ID
     * 
     * @return string Last inserted ID
     */
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    /**
     * Begin transaction
     * 
     * @return bool Success status
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    /**
     * Commit transaction
     * 
     * @return bool Success status
     */
    public function commit() {
        return $this->conn->commit();
    }
    
    /**
     * Rollback transaction
     * 
     * @return bool Success status
     */
    public function rollback() {
        return $this->conn->rollBack();
    }
    
    /**
     * Get PDO connection instance
     * 
     * @return PDO PDO connection object
     */
    public function getConnection() {
        return $this->conn;
    }
}
