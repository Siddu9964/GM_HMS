<?php
namespace GM_HMS\Middleware;

use Exception;
use GM_HMS\Config\SecurityConfig;
use GM_HMS\Database\SecureDatabase;
use GM_HMS\Database\AuditLogger;

class RateLimiter {
    private static $instance = null;
    private $config;
    private $db;
    private $auditLogger;
    
    private function __construct() {
        $this->config = SecurityConfig::getInstance();
        $this->db = SecureDatabase::getInstance();
        $this->auditLogger = AuditLogger::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Check rate limit
     * 
     * @param string $identifier User ID or IP address
     * @param string $type Identifier type (user/ip)
     * @param string $endpoint Endpoint being accessed
     * @param int $limit Request limit
     * @param int $windowSeconds Time window in seconds
     * @return array Result with allowed status and headers
     */
    public function checkLimit($identifier, $type, $endpoint, $limit = null, $windowSeconds = 60) {
        $rateLimits = $this->config->getRateLimits();
        
        // Use default limits if not specified
        if ($limit === null) {
            $limit = $type === 'user' ? $rateLimits['per_user'] : $rateLimits['per_ip'];
        }
        
        // Get current window
        $windowStart = date('Y-m-d H:i:s', time() - $windowSeconds);
        $windowEnd = date('Y-m-d H:i:s');
        
        // Get or create rate limit record
        $record = $this->getRateLimitRecord($identifier, $type, $endpoint, $windowStart);
        
        if (!$record) {
            // Create new record
            $this->createRateLimitRecord($identifier, $type, $endpoint, $windowStart, $windowEnd);
            $requestCount = 1;
        } else {
            $requestCount = $record['request_count'] + 1;
            
            // Update record
            $this->updateRateLimitRecord($record['id'], $requestCount);
        }
        
        // Calculate remaining requests
        $remaining = max(0, $limit - $requestCount);
        $resetTime = time() + $windowSeconds;
        
        // Check if limit exceeded
        $allowed = $requestCount <= $limit;
        
        if (!$allowed) {
            // Log rate limit violation
            $this->auditLogger->logRateLimitViolation($identifier, $endpoint);
        }
        
        return [
            'allowed' => $allowed,
            'limit' => $limit,
            'remaining' => $remaining,
            'reset' => $resetTime,
            'headers' => [
                'X-RateLimit-Limit' => $limit,
                'X-RateLimit-Remaining' => $remaining,
                'X-RateLimit-Reset' => $resetTime
            ]
        ];
    }
    
    /**
     * Middleware handler
     * 
     * @param int|null $userId User ID (null for unauthenticated)
     * @param string $endpoint Current endpoint
     * @return bool Allowed
     */
    public function handle($userId, $endpoint) {
        $identifier = $userId ?? $this->getClientIP();
        $type = $userId ? 'user' : 'ip';
        
        $result = $this->checkLimit($identifier, $type, $endpoint);
        
        // Set rate limit headers
        foreach ($result['headers'] as $header => $value) {
            header("$header: $value");
        }
        
        if (!$result['allowed']) {
            http_response_code(429);
            header('Retry-After: ' . ($result['reset'] - time()));
            echo json_encode([
                'error' => 'Too many requests',
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => $result['reset']
            ]);
            exit;
        }
        
        return true;
    }
    
    /**
     * Get rate limit record
     */
    private function getRateLimitRecord($identifier, $type, $endpoint, $windowStart) {
        try {
            return $this->db->fetchOne(
                'SELECT * FROM rate_limit_tracking 
                 WHERE identifier = ? AND identifier_type = ? AND endpoint = ? AND window_start >= ?
                 ORDER BY window_start DESC LIMIT 1',
                [$identifier, $type, $endpoint, $windowStart]
            );
        } catch (Exception $e) {
            error_log('Rate limit check error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create rate limit record
     */
    private function createRateLimitRecord($identifier, $type, $endpoint, $windowStart, $windowEnd) {
        try {
            $this->db->insert('rate_limit_tracking', [
                'identifier' => $identifier,
                'identifier_type' => $type,
                'endpoint' => $endpoint,
                'request_count' => 1,
                'window_start' => $windowStart,
                'window_end' => $windowEnd
            ]);
        } catch (Exception $e) {
            error_log('Rate limit create error: ' . $e->getMessage());
        }
    }
    
    /**
     * Update rate limit record
     */
    private function updateRateLimitRecord($id, $requestCount) {
        try {
            $this->db->update(
                'rate_limit_tracking',
                ['request_count' => $requestCount],
                'id = ?',
                [$id]
            );
        } catch (Exception $e) {
            error_log('Rate limit update error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get client IP
     */
    private function getClientIP() {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }
    
    /**
     * Clean old records (run periodically)
     */
    public function cleanOldRecords() {
        try {
            $this->db->execute(
                'DELETE FROM rate_limit_tracking WHERE window_end < DATE_SUB(NOW(), INTERVAL 1 HOUR)'
            );
        } catch (Exception $e) {
            error_log('Rate limit cleanup error: ' . $e->getMessage());
        }
    }
    
    private function __clone() {}
    public function __wakeup() {
        throw new Exception('Cannot unserialize singleton');
    }
}
