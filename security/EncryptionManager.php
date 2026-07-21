<?php
namespace GM_HMS\Security;

use Exception;
use GM_HMS\Config\SecurityConfig;

/**
 * Encryption Manager
 */
class EncryptionManager {
    private static $instance = null;
    private $encryptionKey;
    private $cipher = 'aes-256-gcm';
    
    private function __construct() {
        $config = SecurityConfig::getInstance();
        $this->encryptionKey = hash_pbkdf2('sha256', $config->get('ENCRYPTION_KEY'), 'GM_HMS_ENCRYPTION_SALT_V1', 10000, 32, true);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    public function generateToken($length = 32) {
        return bin2hex(openssl_random_pseudo_bytes($length));
    }

    public function hmac($data, $key) {
        return hash_hmac('sha256', $data, $key);
    }
}
