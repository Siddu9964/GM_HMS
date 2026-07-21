<?php
namespace GM_HMS\Middleware;

use Exception;
use GM_HMS\Config\SecurityConfig;

class SecurityHeaders {
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
     * Apply all security headers
     */
    public function apply() {
        $this->setContentSecurityPolicy();
        $this->setXFrameOptions();
        $this->setXContentTypeOptions();
        $this->setXXSSProtection();
        $this->setStrictTransportSecurity();
        $this->setReferrerPolicy();
        $this->setPermissionsPolicy();
        $this->removeServerHeader();
    }
    
    /**
     * Content Security Policy
     */
    private function setContentSecurityPolicy() {
        $csp = [
            "default-src " . $this->config->get('CSP_DEFAULT_SRC', "'self'"),
            "script-src " . $this->config->get('CSP_SCRIPT_SRC', "'self'"),
            "style-src " . $this->config->get('CSP_STYLE_SRC', "'self'"),
            "img-src " . $this->config->get('CSP_IMG_SRC', "'self' data: https:"),
            "font-src " . $this->config->get('CSP_FONT_SRC', "'self'"),
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'"
        ];
        
        header('Content-Security-Policy: ' . implode('; ', $csp));
    }
    
    /**
     * X-Frame-Options (Clickjacking protection)
     */
    private function setXFrameOptions() {
        header('X-Frame-Options: DENY');
    }
    
    /**
     * X-Content-Type-Options (MIME sniffing protection)
     */
    private function setXContentTypeOptions() {
        header('X-Content-Type-Options: nosniff');
    }
    
    /**
     * X-XSS-Protection
     */
    private function setXXSSProtection() {
        header('X-XSS-Protection: 1; mode=block');
    }
    
    /**
     * Strict-Transport-Security (HSTS)
     */
    private function setStrictTransportSecurity() {
        if ($this->config->get('FORCE_HTTPS', false)) {
            $maxAge = $this->config->get('HSTS_MAX_AGE', 31536000);
            header("Strict-Transport-Security: max-age=$maxAge; includeSubDomains; preload");
        }
    }
    
    /**
     * Referrer-Policy
     */
    private function setReferrerPolicy() {
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
    
    /**
     * Permissions-Policy
     */
    private function setPermissionsPolicy() {
        $policies = [
            'geolocation=()',
            'microphone=()',
            'camera=()',
            'payment=()',
            'usb=()',
            'magnetometer=()',
            'gyroscope=()',
            'accelerometer=()'
        ];
        
        header('Permissions-Policy: ' . implode(', ', $policies));
    }
    
    /**
     * Remove server identification header
     */
    private function removeServerHeader() {
        header_remove('X-Powered-By');
        header_remove('Server');
    }
    
    private function __clone() {}
    public function __wakeup() {
        throw new Exception('Cannot unserialize singleton');
    }
}
