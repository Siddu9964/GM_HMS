<?php
namespace GM_HMS\Config;

use Exception;

/**
 * Security Configuration Manager
 */
class SecurityConfig {
    private static $instance = null;
    private $config = [];
    private $envLoaded = false;
    
    private function __construct() {
        $this->loadEnvironment();
        $this->validateConfiguration();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadEnvironment() {
        $envPath = __DIR__ . '/.env';
        $envExamplePath = __DIR__ . '/.env.example';
        
        if (!file_exists($envPath)) {
            if ($this->isProduction()) {
                throw new Exception('.env file not found.');
            }
            if (file_exists($envExamplePath)) {
                $envPath = $envExamplePath;
            } else {
                throw new Exception('No .env found.');
            }
        }
        
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim(trim($value), '"\'');
                
                // If the environment variable is already set (e.g. by Docker), use it instead
                $envValue = getenv($key);
                if ($envValue !== false) {
                    $this->config[$key] = $envValue;
                    $_ENV[$key] = $envValue;
                } else {
                    $this->config[$key] = $value;
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                }
            }
        }
        $this->envLoaded = true;
    }
    
    private function validateConfiguration() {
        $required = ['DB_HOST', 'DB_NAME', 'DB_USERNAME', 'JWT_SECRET', 'ENCRYPTION_KEY'];
        foreach ($required as $key) {
            if (empty($this->config[$key])) {
                throw new Exception("Required configuration missing: $key");
            }
        }
    }
    
    public function get($key, $default = null) {
        return $this->config[$key] ?? $default;
    }
    
    public function isProduction() {
        return ($this->config['APP_ENV'] ?? 'production') === 'production';
    }
    
    public function isDevelopment() {
        return ($this->config['APP_ENV'] ?? 'development') === 'development';
    }

    public function getDatabase() {
        return [
            'host' => $this->get('DB_HOST', 'localhost'),
            'port' => $this->get('DB_PORT', 3306),
            'name' => $this->get('DB_NAME'),
            'username' => $this->get('DB_USERNAME'),
            'password' => $this->get('DB_PASSWORD', ''),
            'charset' => $this->get('DB_CHARSET', 'utf8mb4')
        ];
    }
    
    public function getJWT() {
        return [
            'secret' => $this->get('JWT_SECRET'),
            'issuer' => $this->get('JWT_ISSUER', 'gm-hms'),
            'audience' => $this->get('JWT_AUDIENCE', 'gm-hms-api'),
            'access_expire' => (int)$this->get('JWT_ACCESS_TOKEN_EXPIRE', 3600),
            'refresh_expire' => (int)$this->get('JWT_REFRESH_TOKEN_EXPIRE', 2592000),
            'algorithm' => $this->get('JWT_ALGORITHM', 'HS256')
        ];
    }

    public function getCORS() {
        return [
            'allowed_origins' => explode(',', $this->get('CORS_ALLOWED_ORIGINS', 'http://localhost,http://127.0.0.1')),
            'allowed_methods' => explode(',', $this->get('CORS_ALLOWED_METHODS', 'GET,POST,PUT,DELETE,OPTIONS')),
            'allowed_headers' => explode(',', $this->get('CORS_ALLOWED_HEADERS', 'Content-Type,Authorization,X-Requested-With')),
            'allow_credentials' => (bool)$this->get('CORS_ALLOW_CREDENTIALS', true)
        ];
    }
    
    public function getRateLimits() {
        return [
            'per_user' => (int)$this->get('RATE_LIMIT_PER_USER', 100), // Default 100
            'per_ip' => (int)$this->get('RATE_LIMIT_PER_IP', 60),      // Default 60
            'global' => [(int)$this->get('RATE_LIMIT_GLOBAL', 60), 60],
            'login' => [(int)$this->get('RATE_LIMIT_LOGIN', 5), 300],
            'api' => [(int)$this->get('RATE_LIMIT_API', 100), 60]
        ];
    }

    public function getPasswordPolicy() {
        return [
            'min_length' => (int)$this->get('PASSWORD_MIN_LENGTH', 8),
            'require_uppercase' => filter_var($this->get('PASSWORD_REQUIRE_UPPER', true), FILTER_VALIDATE_BOOLEAN),
            'require_lowercase' => filter_var($this->get('PASSWORD_REQUIRE_LOWER', true), FILTER_VALIDATE_BOOLEAN),
            'require_numbers' => filter_var($this->get('PASSWORD_REQUIRE_NUMBER', true), FILTER_VALIDATE_BOOLEAN),
            'require_special' => filter_var($this->get('PASSWORD_REQUIRE_SPECIAL', true), FILTER_VALIDATE_BOOLEAN)
        ];
    }
}
