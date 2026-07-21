<?php
namespace GM_HMS\Security;

use Exception;
use GM_HMS\Config\SecurityConfig;
use GM_HMS\Database\SecureDatabase;

/**
 * Token Manager
 */
class TokenManager {
    private static $instance = null;
    private $config;
    private $db;
    private $encryption;
    
    private function __construct() {
        $this->config = SecurityConfig::getInstance();
        $this->db = SecureDatabase::getInstance();
        $this->encryption = EncryptionManager::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function generateToken($userId, $claims = [], $type = 'access') {
        $jwtConfig = $this->config->getJWT();
        $payload = array_merge([
            'iss' => $jwtConfig['issuer'],
            'iat' => time(),
            'exp' => time() + ($type === 'refresh' ? $jwtConfig['refresh_expire'] : $jwtConfig['access_expire']),
            'sub' => $userId,
            'type' => $type
        ], $claims);
        
        return $this->createJWT($payload);
    }
    
    private function createJWT($payload) {
        $jwtConfig = $this->config->getJWT();
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => $jwtConfig['algorithm']]));
        $payload = base64_encode(json_encode($payload));
        $signature = $this->encryption->hmac("$header.$payload", $jwtConfig['secret']);
        return "$header.$payload." . base64_encode(hex2bin($signature));
    }

    public function validateToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        return json_decode(base64_decode($parts[1]), true);
    }

    public function revokeAllUserTokens($userId) {}
}
