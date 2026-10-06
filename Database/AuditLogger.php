<?php
namespace GM_HMS\Database;

use Exception;
use GM_HMS\Config\SecurityConfig;

/**
 * Audit Logger
 */
class AuditLogger {
    private static $instance = null;
    private $db;
    private $config;
    private $enabled = true;
    
    const SEVERITY_INFO = 'info';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_ERROR = 'error';
    const SEVERITY_CRITICAL = 'critical';
    
    private function __construct() {
        $this->db = SecureDatabase::getInstance();
        $this->config = SecurityConfig::getInstance();
        $this->enabled = filter_var($this->config->get('AUDIT_LOG_ENABLED', true), FILTER_VALIDATE_BOOLEAN);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function logSecurityEvent($eventType, $severity, $message, $data = []) {
        if (!$this->enabled) return;
        
        // Get exactly when it happened and who did it
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN_IP';
        
        // Format the text nicely
        $logEntry = "[$timestamp] [IP: $ip] [Severity: $severity] [Event: $eventType] - $message";
        if (!empty($data)) {
            $logEntry .= " | Data: " . json_encode($data);
        }
        $logEntry .= PHP_EOL; // Add a new line at the end
        
        // Save to a dedicated text file inside the project folder
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        $logFile = $logDir . '/security_audit.log';
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    public function logLoginAttempt($username, $success, $reason = null) {
        $this->logSecurityEvent('login_attempt', $success ? self::SEVERITY_INFO : self::SEVERITY_WARNING, "Login attempt for $username", ['success' => $success, 'reason' => $reason]);
    }

    public function clearFailedLoginAttempts($username) {}
    public function getFailedLoginAttempts($username, $minutes) { return 0; }
    public function logTokenGeneration($userId, $type) {}
    public function logLogout($userId) {}
    public function logRateLimitViolation($identifier, $endpoint) {
        $this->logSecurityEvent(
            'rate_limit_exceeded',
            self::SEVERITY_WARNING,
            "Rate limit exceeded for $identifier at $endpoint"
        );
    }
}
