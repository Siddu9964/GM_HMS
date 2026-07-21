<?php
/**
 * Pharmacy ERP - Database Bridge
 *
 * This file is a THIN WRAPPER that uses the project's central SecureDatabase
 * (via Autoloader + Config/.env) — single DB config for the whole project.
 *
 * It also exposes a PDO-compatible DBResult wrapper so all existing
 * pharmacy_view pages that call $db->query(...)->fetchAll() / ->fetchColumn()
 * / ->prepare() continue to work without any changes to those pages.
 */

// Suppress PHP notices/warnings from corrupting JSON in AJAX calls
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

// Start output buffering so stray output never corrupts JSON
if (!ob_get_level()) ob_start();

// ── Bootstrap the project's autoloader & SecureDatabase ──────────────────────
require_once __DIR__ . '/../../core/Autoloader.php';

use GM_HMS\Database\SecureDatabase;

define('CURRENCY', '₹');
define('LOW_STOCK_THRESHOLD', 20);
define('EXPIRY_ALERT_DAYS', 60);

// ─────────────────────────────────────────────────────────────────────────────
// DBResult — lightweight PDO-compatible result wrapper around mysqli result
// Allows existing pages to use:  $db->query($sql)->fetchAll()
//                                $db->query($sql)->fetchAll(PDO::FETCH_COLUMN)
//                                $db->query($sql)->fetchColumn()
//                                $db->prepare($sql)->execute($params)->fetchAll()
// ─────────────────────────────────────────────────────────────────────────────
class DBResult {
    private array $rows;
    private int   $pos = 0;

    public function __construct(array $rows) {
        $this->rows = $rows;
    }

    /** fetchAll() or fetchAll(PDO::FETCH_COLUMN) */
    public function fetchAll(int $mode = 0): array {
        if ($mode === \PDO::FETCH_COLUMN) {
            return array_map(fn($r) => reset($r), $this->rows);
        }
        return $this->rows;
    }

    /** fetchColumn() — returns first column of first row */
    public function fetchColumn(): mixed {
        if (empty($this->rows)) return false;
        return reset($this->rows[0]);
    }

    /** fetch() — returns next row */
    public function fetch(): mixed {
        return $this->rows[$this->pos++] ?? false;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// DBWrapper — wraps SecureDatabase and exposes query() / prepare()
// ─────────────────────────────────────────────────────────────────────────────
class DBWrapper {
    private SecureDatabase $db;

    public function __construct(SecureDatabase $db) {
        $this->db = $db;
    }

    /**
     * Run a plain SQL query (no parameters).
     * Returns a DBResult with PDO-compatible fetchAll() / fetchColumn().
     */
    public function query(string $sql): DBResult {
        $rows = $this->db->fetchAll($sql);
        return new DBResult($rows);
    }

    /**
     * Prepare a statement — returns a DBStatement for ->execute($params).
     */
    public function prepare(string $sql): DBStatement {
        return new DBStatement($this->db, $sql);
    }

    /**
     * Direct fetchAll with params (used by db.php helpers).
     */
    public function fetchAll(string $sql, array $params = []): array {
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Direct fetchOne with params (used by db.php helpers).
     */
    public function fetchOne(string $sql, array $params = []): ?array {
        return $this->db->fetchOne($sql, $params);
    }

    /**
     * Execute a write query (INSERT / UPDATE / DELETE) with params.
     */
    public function execute(string $sql, array $params = []): mixed {
        return $this->db->execute($sql, $params);
    }

    /**
     * Get raw mysqli connection (used by PharmacyIndentController autoGenerate etc.)
     */
    public function getConnection(): mixed {
        return $this->db->getConnection();
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// DBStatement — returned by prepare(), supports execute($params)->fetchAll()
// ─────────────────────────────────────────────────────────────────────────────
class DBStatement {
    private SecureDatabase $db;
    private string         $sql;
    private ?DBResult      $result = null;

    public function __construct(SecureDatabase $db, string $sql) {
        $this->db  = $db;
        $this->sql = $sql;
    }

    public function execute(array $params = []): static {
        $rows        = $this->db->fetchAll($this->sql, $params);
        $this->result = new DBResult($rows);
        return $this;
    }

    public function fetchAll(int $mode = 0): array {
        return $this->result?->fetchAll($mode) ?? [];
    }

    public function fetchColumn(): mixed {
        return $this->result?->fetchColumn() ?? false;
    }

    public function fetch(): mixed {
        return $this->result?->fetch() ?? false;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Internal SecureDatabase singleton
// ─────────────────────────────────────────────────────────────────────────────
function _getSecureDB(): SecureDatabase {
    static $db = null;
    if ($db === null) {
        $db = SecureDatabase::getInstance();
    }
    return $db;
}

// ─────────────────────────────────────────────────────────────────────────────
// getDB() — returns DBWrapper (PDO-compatible) used by all pharmacy_view pages
// ─────────────────────────────────────────────────────────────────────────────
function getDB(): DBWrapper {
    static $wrapper = null;
    if ($wrapper === null) {
        $wrapper = new DBWrapper(_getSecureDB());
    }
    return $wrapper;
}

// ── getSetting() ──────────────────────────────────────────────────────────────
function getSetting(string $key, string $default = ''): string {
    try {
        $row = _getSecureDB()->fetchOne(
            "SELECT setting_value FROM ph_settings WHERE setting_key = ?",
            [$key]
        );
        return $row ? (string)$row['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

// ── generateId() ──────────────────────────────────────────────────────────────
function generateId(string $table, string $column, string $prefix): string {
    $prefixLen = strlen($prefix) + 2;
    $row = _getSecureDB()->fetchOne(
        "SELECT MAX(CAST(SUBSTRING($column, $prefixLen) AS UNSIGNED)) AS max_id FROM $table"
    );
    $next = ($row['max_id'] ?? 0) + 1;
    return $prefix . '-' . str_pad($next, 5, '0', STR_PAD_LEFT);
}

// ── jsonResponse() ────────────────────────────────────────────────────────────
function jsonResponse(bool $success, string $message = '', $data = []): void {
    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    $isIndexed = is_array($data) && ($data === [] || array_is_list($data));
    if ($isIndexed) {
        $payload = ['success' => $success, 'message' => $message, 'data' => $data];
    } else {
        $payload = array_merge(['success' => $success, 'message' => $message], (array)$data);
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── clean() ───────────────────────────────────────────────────────────────────
function clean(mixed $v): string {
    return htmlspecialchars(trim((string)$v), ENT_QUOTES, 'UTF-8');
}

// ── getLowStockCount() ────────────────────────────────────────────────────────
function getLowStockCount(): int {
    try {
        $threshold = (int)getSetting('low_stock_threshold', (string)LOW_STOCK_THRESHOLD);
        $row = _getSecureDB()->fetchOne(
            "SELECT COUNT(*) AS cnt FROM ph_product WHERE quantity <= ?", [$threshold]
        );
        return (int)($row['cnt'] ?? 0);
    } catch (Exception $e) { return 0; }
}

// ── getExpiryAlertCount() ─────────────────────────────────────────────────────
function getExpiryAlertCount(): int {
    try {
        $days = (int)getSetting('expiry_alert_days', (string)EXPIRY_ALERT_DAYS);
        $row  = _getSecureDB()->fetchOne(
            "SELECT COUNT(*) AS cnt FROM ph_product
             WHERE expiry_date IS NOT NULL
               AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
               AND expiry_date >= CURDATE()",
            [$days]
        );
        return (int)($row['cnt'] ?? 0);
    } catch (Exception $e) { return 0; }
}

// ── getPendingIndentsCount() ──────────────────────────────────────────────────
function getPendingIndentsCount(): int {
    try {
        $row = _getSecureDB()->fetchOne(
            "SELECT COUNT(*) AS cnt FROM ph_indent_requests WHERE status='pending'"
        );
        return (int)($row['cnt'] ?? 0);
    } catch (Exception $e) { return 0; }
}

// ── getTodaySales() ───────────────────────────────────────────────────────────
function getTodaySales(): float {
    try {
        $row = _getSecureDB()->fetchOne(
            "SELECT COALESCE(SUM(grand_total),0) AS total
             FROM ph_sales_master
             WHERE invoice_date = CURDATE() AND status != 'cancelled'"
        );
        return (float)($row['total'] ?? 0.0);
    } catch (Exception $e) { return 0.0; }
}
