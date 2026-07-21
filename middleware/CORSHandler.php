<?php
namespace GM_HMS\Middleware;

use Exception;
use GM_HMS\Config\SecurityConfig;

class CORSHandler {
    private static $instance = null;
    private $config;
    
    private function __construct() {
        $this->config = SecurityConfig::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Handle CORS
     */
    public function handle() {
        $corsConfig = $this->config->getCORS();
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // Check if origin is allowed
        if ($this->isOriginAllowed($origin, $corsConfig['allowed_origins'])) {
            header("Access-Control-Allow-Origin: $origin");
            
            if ($corsConfig['allow_credentials']) {
                header('Access-Control-Allow-Credentials: true');
            }
            
            // Handle preflight request
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                $this->handlePreflight($corsConfig);
                exit;
            }
            
            // Set allowed methods and headers for actual requests
            header('Access-Control-Allow-Methods: ' . implode(', ', $corsConfig['allowed_methods']));
            header('Access-Control-Allow-Headers: ' . implode(', ', $corsConfig['allowed_headers']));
            header('Access-Control-Max-Age: 86400'); // 24 hours
        }
    }
    
    /**
     * Handle preflight request
     */
    private function handlePreflight($corsConfig) {
        header('Access-Control-Allow-Methods: ' . implode(', ', $corsConfig['allowed_methods']));
        header('Access-Control-Allow-Headers: ' . implode(', ', $corsConfig['allowed_headers']));
        header('Access-Control-Max-Age: 86400');
        http_response_code(204);
    }
    
    /**
     * Check if origin is allowed
     */
    private function isOriginAllowed($origin, $allowedOrigins) {
        if (empty($origin)) {
            return false;
        }
        
        // Check exact match
        if (in_array($origin, $allowedOrigins)) {
            return true;
        }
        
        // Check wildcard patterns
        foreach ($allowedOrigins as $allowed) {
            if ($allowed === '*') {
                return true; // Allow all (not recommended for production)
            }
            
            if (strpos($allowed, '*') !== false) {
                $pattern = '/^' . str_replace('*', '.*', preg_quote($allowed, '/')) . '$/';
                if (preg_match($pattern, $origin)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    private function __clone() {}
    public function __wakeup() {
        throw new Exception('Cannot unserialize singleton');
    }
}
