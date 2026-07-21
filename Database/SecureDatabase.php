<?php
namespace GM_HMS\Database;

use Exception;
use mysqli;
use GM_HMS\Config\SecurityConfig;
use GM_HMS\Security\EncryptionManager;

/**
 * Secure Database Connection Manager
 */
class SecureDatabase
{
    private static $instance = null;
    private $connection = null;
    private $config;
    private $transactionDepth = 0;

    /**
     * Private constructor - use getInstance()
     */
    private function __construct()
    {
        $this->config = SecurityConfig::getInstance();
        $this->connect();
    }

    /**
     * Get singleton instance
     * 
     * @return SecureDatabase
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establish secure database connection
     */
    private function connect()
    {
        try {
            $dbConfig = $this->config->getDatabase();

            $branch = $_SERVER['HTTP_X_HOSPITAL_BRANCH'] ?? $_SESSION['hospital_branch'] ?? $_SESSION['branch'] ?? '';
            if (strtolower($branch) === 'basaveshwaranagar') {
                $dbConfig['name'] = 'hmsc_basaveshwranagara';
            }

            mysqli_report(MYSQLI_REPORT_OFF);

            $this->connection = new mysqli(
                $dbConfig['host'],
                $dbConfig['username'],
                $dbConfig['password'],
                $dbConfig['name'],
                $dbConfig['port']
                );

            if ($this->connection->connect_error) {
                throw new Exception('Database connection failed');
            }

            if (!$this->connection->set_charset($dbConfig['charset'])) {
                throw new Exception('Failed to set database charset');
            }

            $this->connection->query("SET SQL_MODE='STRICT_ALL_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE'");
            $this->connection->query("SET time_zone = '+05:30'");

        }
        catch (Exception $e) {
            throw new Exception('Database initialization failed');
        }
    }

    public function getConnection()
    {
        if ($this->connection === null || !$this->connection->ping()) {
            $this->connect();
        }
        return $this->connection;
    }

    public function execute($query, $params = [], $types = null)
    {
        if (!$this->connection->ping()) {
            $this->connect();
        }

        $stmt = $this->connection->prepare($query);
        if ($stmt === false) {
            $error = $this->connection->error;
            throw new Exception("Query preparation failed: $error. Query: $query");
        }

        if (!empty($params)) {
            if ($types === null) {
                $types = '';
                foreach ($params as $key => $param) {
                    // Convert booleans to integers for MySQL
                    if (is_bool($param)) {
                        $params[$key] = $param ? 1 : 0;
                        $types .= 'i';
                    }
                    elseif (is_int($param))
                        $types .= 'i';
                    elseif (is_float($param))
                        $types .= 'd';
                    elseif (is_string($param) || is_null($param))
                        $types .= 's';
                    else
                        $types .= 'b';
                }
            }
            $stmt->bind_param($types, ...$params);
        }

        $success = $stmt->execute();
        if (!$success) {
            $error = $stmt->error;
            throw new Exception("Query execution failed: $error. Query: $query");
        }

        $result = $stmt->get_result();

        if ($result === false) {
            $affectedRows = $stmt->affected_rows;
            $insertId = $stmt->insert_id;
            $stmt->close();
            return ['affected_rows' => $affectedRows, 'insert_id' => $insertId];
        }

        $stmt->close();
        return $result;
    }

    // Simplified for the refactor to keep it professional but clean
    public function fetchAll($query, $params = [], $types = null)
    {
        $result = $this->execute($query, $params, $types);
        if (is_array($result))
            return [];
        $rows = [];
        while ($row = $result->fetch_assoc())
            $rows[] = $row;
        $result->free();
        return $rows;
    }

    public function fetchOne($query, $params = [], $types = null)
    {
        $result = $this->execute($query, $params, $types);
        if (is_array($result))
            return null;
        $row = $result->fetch_assoc();
        $result->free();
        return $row;
    }

    public function insert($table, $data)
    {
        $columns = array_keys($data);
        $values = array_values($data);
        $columnList = implode(', ', array_map(fn($c) => "`$c`", $columns));
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $query = "INSERT INTO `$table` ($columnList) VALUES ($placeholders)";
        $result = $this->execute($query, $values);
        return $result['insert_id'];
    }

    public function update($table, $data, $where, $whereParams = [])
    {
        $setParts = [];
        $values = [];
        foreach ($data as $column => $value) {
            $setParts[] = "`$column` = ?";
            $values[] = $value;
        }
        $setClause = implode(', ', $setParts);
        $allParams = array_merge($values, $whereParams);
        $query = "UPDATE `$table` SET $setClause WHERE $where";
        $result = $this->execute($query, $allParams);
        return $result['affected_rows'];
    }

    public function delete($table, $where, $whereParams = [])
    {
        $query = "DELETE FROM `$table` WHERE $where";
        $result = $this->execute($query, $whereParams);
        return $result['affected_rows'];
    }

    public function beginTransaction()
    {
        if (!$this->connection->ping())
            $this->connect();
        $this->connection->begin_transaction();
    }

    public function commit()
    {
        $this->connection->commit();
    }

    public function rollback()
    {
        $this->connection->rollback();
    }
}