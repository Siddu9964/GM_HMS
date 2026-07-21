<?php
/**
 * Vendor Portal - Database Bridge
 */

// Error handling
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

if (!ob_get_level()) ob_start();

// Bootstrap autoloader
require_once __DIR__ . '/../../../core/Autoloader.php';
use GM_HMS\Database\SecureDatabase;

/**
 * PDO-compatible wrapper for SecureDatabase
 */
class DBWrapper {
    private SecureDatabase $db;
    public function __construct(SecureDatabase $db) { $this->db = $db; }
    public function fetchOne(string $sql, array $params = []): ?array { return $this->db->fetchOne($sql, $params); }
    public function fetchAll(string $sql, array $params = []): array { return $this->db->fetchAll($sql, $params); }
    public function execute(string $sql, array $params = []): mixed { return $this->db->execute($sql, $params); }
}

function getDB(): DBWrapper {
    static $wrapper = null;
    if ($wrapper === null) {
        $wrapper = new DBWrapper(SecureDatabase::getInstance());
    }
    return $wrapper;
}
