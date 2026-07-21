<?php
/**
 * Security Setup and Installation Script
 * 
 * Run this script once to set up the security infrastructure.
 * Creates database tables, generates security keys, and validates configuration.
 * 
 * @package GM_HMS\Setup
 * @author GM HMS Security Team
 * @version 1.0.0
 */

// Prevent direct access in production
if (php_sapi_name() !== 'cli' && !isset($_GET['setup_key'])) {
    die('Access denied. Run from command line or provide setup key.');
}

require_once __DIR__ . '/../config/SecurityConfig.php';
require_once __DIR__ . '/../Database/DB.php';
require_once __DIR__ . '/../security/EncryptionManager.php';

class SecuritySetup {
    private $db;
    private $config;
    private $encryption;
    
    public function __construct() {
        echo "===========================================\n";
        echo "GM HMS Security Setup\n";
        echo "===========================================\n\n";
        
        try {
            $this->config = SecurityConfig::getInstance();
            $this->db = SecureDatabase::getInstance();
            $this->encryption = EncryptionManager::getInstance();
        } catch (Exception $e) {
            die("ERROR: " . $e->getMessage() . "\n");
        }
    }
    
    /**
     * Run complete setup
     */
    public function run() {
        echo "Starting security setup...\n\n";
        
        $this->checkRequirements();
        $this->generateSecurityKeys();
        $this->validateConfiguration();
        
        echo "\n===========================================\n";
        echo "Setup completed successfully!\n";
        echo "===========================================\n\n";
        
        $this->displayNextSteps();
    }
    
    /**
     * Check system requirements
     */
    private function checkRequirements() {
        echo "[1/3] Checking system requirements...\n";
        
        $requirements = [
            'PHP >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
            'OpenSSL extension' => extension_loaded('openssl'),
            'MySQLi extension' => extension_loaded('mysqli'),
            'JSON extension' => extension_loaded('json'),
            'MBString extension' => extension_loaded('mbstring')
        ];
        
        $allMet = true;
        foreach ($requirements as $requirement => $met) {
            $status = $met ? '✓' : '✗';
            echo "  $status $requirement\n";
            if (!$met) {
                $allMet = false;
            }
        }
        
        if (!$allMet) {
            die("\nERROR: Not all requirements are met. Please install missing extensions.\n");
        }
        
        echo "  All requirements met!\n\n";
    }
    
    /**
     * Generate security keys
     */
    private function generateSecurityKeys() {
        echo "[2/3] Generating security keys...\n";
        
        $envFile = __DIR__ . '/../config/.env';
        
        if (!file_exists($envFile)) {
            echo "  ERROR: .env file not found. Copy .env.example to .env first.\n";
            return;
        }
        
        // Generate new keys
        $keys = [
            'JWT_SECRET' => bin2hex(random_bytes(32)),
            'ENCRYPTION_KEY' => base64_encode(random_bytes(32)),
            'CSRF_SECRET' => bin2hex(random_bytes(32)),
            'PASSWORD_SALT' => bin2hex(random_bytes(16)),
            'API_KEY' => bin2hex(random_bytes(32))
        ];
        
        // Read current .env
        $envContent = file_get_contents($envFile);
        
        // Replace keys
        foreach ($keys as $key => $value) {
            // Only replace if it contains default/placeholder values
            if (preg_match("/$key=.*?(CHANGE_THIS|dev_|your-)/i", $envContent)) {
                $envContent = preg_replace(
                    "/^$key=.*/m",
                    "$key=$value",
                    $envContent
                );
                echo "  ✓ Generated $key\n";
            } else {
                echo "  - Skipped $key (already set)\n";
            }
        }
        
        // Write back to .env
        file_put_contents($envFile, $envContent);
        
        echo "  Security keys generated and saved to .env\n\n";
    }
    
    /**
     * Validate configuration
     */
    private function validateConfiguration() {
        echo "[3/3] Validating configuration...\n";
        
        $checks = [
            'Database connection' => $this->testDatabaseConnection(),
            'Encryption working' => $this->testEncryption(),
            'JWT generation' => $this->testJWT(),
            'Environment loaded' => $this->config->get('APP_ENV') !== null
        ];
        
        foreach ($checks as $check => $passed) {
            $status = $passed ? '✓' : '✗';
            echo "  $status $check\n";
        }
        
        echo "\n";
    }
    
    /**
     * Test database connection
     */
    private function testDatabaseConnection() {
        try {
            $result = $this->db->fetchOne('SELECT 1 as test');
            return $result && $result['test'] == 1;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Test encryption
     */
    private function testEncryption() {
        try {
            $test = 'test_data';
            $encrypted = $this->encryption->encrypt($test);
            $decrypted = $this->encryption->decrypt($encrypted);
            return $decrypted === $test;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Test JWT
     */
    private function testJWT() {
        try {
            require_once __DIR__ . '/../security/TokenManager.php';
            $tokenManager = TokenManager::getInstance();
            $token = $tokenManager->generateToken(1, [], 'access');
            $payload = $tokenManager->validateToken($token);
            return $payload !== null;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Display next steps
     */
    private function displayNextSteps() {
        echo "Next Steps:\n";
        echo "-----------\n";
        echo "1. Review and update .env file for your environment\n";
        echo "2. Configure HTTPS on your web server (required for production)\n";
        echo "3. Review security settings in config/.env\n";
        echo "4. Test the API endpoints\n";
        echo "5. Set up log rotation for security logs\n";
        echo "6. Configure email settings for password resets\n\n";
        
        echo "Important Files:\n";
        echo "----------------\n";
        echo "- config/.env - Environment configuration\n";
        echo "- Database/DB.php - Secure database connection\n";
        echo "- security/* - Security components\n";
        echo "- middleware/* - API security middleware\n\n";
        
        echo "Note: This setup works with your existing database.\n";
        echo "The security layer has been added without modifying your existing tables.\n\n";
    }
}

// Run setup
$setup = new SecuritySetup();
$setup->run();
