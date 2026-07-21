<?php
namespace GM_HMS\Controllers;

use Exception;
use GM_HMS\Config\SecurityConfig;
use GM_HMS\Database\SecureDatabase;
use GM_HMS\Database\AuditLogger;
use GM_HMS\Security\AuthenticationManager;
use GM_HMS\Security\AuthorizationManager;
use GM_HMS\Security\InputValidator;
use GM_HMS\Security\InputSanitizer;
use GM_HMS\Security\JSONValidator;
use GM_HMS\Middleware\RateLimiter;
use GM_HMS\Middleware\SecurityHeaders;
use GM_HMS\Middleware\CORSHandler;

abstract class BaseController {
    protected $db;
    protected $config;
    protected $auth;
    protected $authz;
    protected $validator;
    protected $sanitizer;
    protected $jsonValidator;
    protected $auditLogger;
    protected $currentUser = null;
    
    public function __construct() {
        // Initialize components
        $this->db = SecureDatabase::getInstance();
        $this->config = SecurityConfig::getInstance();
        $this->auth = AuthenticationManager::getInstance();
        $this->authz = AuthorizationManager::getInstance();
        $this->validator = new InputValidator();
        $this->sanitizer = new InputSanitizer();
        $this->jsonValidator = new JSONValidator();
        $this->auditLogger = AuditLogger::getInstance();
        
        // Apply security middleware
        $this->applyMiddleware();
    }
    
    /**
     * Apply security middleware
     */
    private function applyMiddleware() {
        // Set security headers
        SecurityHeaders::getInstance()->apply();
        
        // Handle CORS
        CORSHandler::getInstance()->handle();
        
        // Set JSON content type
        header('Content-Type: application/json');
    }
    
    /**
     * Require authentication
     * 
     * @return array User data
     * @throws Exception if not authenticated
     */
    protected function requireAuth() {
        $token = $this->getBearerToken();
        
        if ($token) {
            $user = $this->auth->verifyToken($token);
            if (!$user) {
                $this->respondUnauthorized('Invalid or expired token');
            }
            $this->currentUser = $user;
        } else {
            // Fallback to Session Authentication
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            if (isset($_SESSION['user_id'])) {
                // Reconstruct user from session
                $this->currentUser = [
                    'id' => $_SESSION['user_id'],
                    'sl_no' => $_SESSION['user_id'], // For staff/admin compatibility
                    'doctor_id' => $_SESSION['user_id'], // For doctor compatibility
                    'username' => $_SESSION['username'] ?? '',
                    'full_name' => $_SESSION['full_name'] ?? '',
                    'role' => $_SESSION['role'] ?? '',
                    'email' => $_SESSION['email'] ?? ''
                ];
            } else {
                $this->respondUnauthorized('Authentication required');
            }
        }
        
        // Apply rate limiting for authenticated user
        if ($this->currentUser) {
             RateLimiter::getInstance()->handle($this->currentUser['id'], $_SERVER['REQUEST_URI']);
        }
        
        return $this->currentUser;
    }
    
    /**
     * Require permission
     * 
     * @param string $permission Permission name
     */
    protected function requirePermission($permission) {
        if (!$this->currentUser) {
            $this->requireAuth();
        }
        
        try {
            $this->authz->requirePermission($this->currentUser['id'], $permission);
        } catch (Exception $e) {
            $this->respondForbidden('Insufficient permissions');
        }
    }
    
    /**
     * Require action on resource
     * 
     * @param string $resource Resource name
     * @param string $action Action name
     */
    protected function requireAction($resource, $action) {
        if (!$this->currentUser) {
            $this->requireAuth();
        }
        
        try {
            $this->authz->requireAction($this->currentUser['id'], $resource, $action);
        } catch (Exception $e) {
            $this->respondForbidden('Insufficient permissions');
        }
    }
    
    /**
     * Restrict HTTP method
     * 
     * @param string|array $allowedMethods Allowed methods
     */
    protected function restrictMethod($allowedMethods) {
        $allowedMethods = (array)$allowedMethods;
        $currentMethod = $_SERVER['REQUEST_METHOD'];
        
        if (!in_array($currentMethod, $allowedMethods)) {
            $this->respondMethodNotAllowed($allowedMethods);
        }
    }
    
    /**
     * Get JSON request body
     * 
     * @param array|null $schema Optional JSON schema for validation
     * @return array Parsed and validated JSON data
     */
    protected function getJsonInput($schema = null) {
        $json = file_get_contents('php://input');
        
        $data = $this->jsonValidator->validateRequest($json);
        
        if ($data === null) {
            $this->respondBadRequest('Invalid JSON: ' . implode(', ', $this->jsonValidator->getErrors()));
        }
        
        if ($schema !== null) {
            if (!$this->jsonValidator->validateSchema($data, $schema)) {
                $this->respondBadRequest('Validation failed: ' . implode(', ', $this->jsonValidator->getErrors()));
            }
        }
        
        return $data;
    }
    
    /**
     * Validate input data
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return array Validated data
     */
    protected function validate($data, $rules) {
        if (!$this->validator->validate($data, $rules)) {
            $this->respondBadRequest('Validation failed: ' . implode(', ', $this->validator->getErrors()));
        }
        
        return $data;
    }
    
    /**
     * Sanitize input data
     * 
     * @param mixed $data Data to sanitize
     * @return mixed Sanitized data
     */
    protected function sanitize($data) {
        if (is_array($data)) {
            return $this->sanitizer->sanitizeArray($data);
        }
        return $this->sanitizer->sanitizeString($data);
    }
    
    /**
     * Get bearer token from header
     * 
     * @return string|null Token
     */
    private function getBearerToken() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Send JSON response
     * 
     * @param mixed $data Response data
     * @param int $statusCode HTTP status code
     */
    protected function respond($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    /**
     * Send success response
     * 
     * @param mixed $data Response data
     * @param string $message Success message
     */
    protected function respondSuccess($data = null, $message = 'Success') {
        $response = ['success' => true, 'status' => 'success', 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        $this->respond($response, 200);
    }
    
    /**
     * Send created response
     * 
     * @param mixed $data Created resource data
     */
    protected function respondCreated($data) {
        $this->respond(['success' => true, 'status' => 'success', 'data' => $data], 201);
    }
    
    /**
     * Send bad request response
     * 
     * @param string $message Error message
     */
    protected function respondBadRequest($message) {
        $this->respond(['success' => false, 'status' => 'error', 'error' => $message], 400);
    }
    
    /**
     * Send error response
     * 
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     */
    protected function respondError($message, $statusCode = 400) {
        $this->respond(['success' => false, 'status' => 'error', 'error' => $message], $statusCode);
    }
    
    /**
     * Send unauthorized response
     * 
     * @param string $message Error message
     */
    protected function respondUnauthorized($message = 'Unauthorized') {
        $this->respond(['success' => false, 'status' => 'error', 'error' => $message], 401);
    }
    
    /**
     * Send forbidden response
     * 
     * @param string $message Error message
     */
    protected function respondForbidden($message = 'Forbidden') {
        $this->respond(['success' => false, 'status' => 'error', 'error' => $message], 403);
    }
    
    /**
     * Send not found response
     * 
     * @param string $message Error message
     */
    protected function respondNotFound($message = 'Resource not found') {
        $this->respond(['success' => false, 'status' => 'error', 'error' => $message], 404);
    }
    
    /**
     * Send method not allowed response
     * 
     * @param array $allowedMethods Allowed methods
     */
    protected function respondMethodNotAllowed($allowedMethods) {
        header('Allow: ' . implode(', ', $allowedMethods));
        $this->respond(['success' => false, 'status' => 'error', 'error' => 'Method not allowed'], 405);
    }
    
    /**
     * Send server error response
     * 
     * @param string $message Error message
     */
    protected function respondServerError($message = 'Internal server error') {
        // Log error but don't expose details in production
        if ($this->config->isDevelopment()) {
            $this->respond(['success' => false, 'status' => 'error', 'error' => $message], 500);
        } else {
            $this->respond(['success' => false, 'status' => 'error', 'error' => 'Internal server error'], 500);
        }
    }
    
    /**
     * Handle exceptions
     * 
     * @param Exception $e Exception
     */
    protected function handleException($e) {
        error_log('Controller exception: ' . $e->getMessage());
        
        $this->auditLogger->logSecurityEvent(
            'controller_exception',
            AuditLogger::SEVERITY_ERROR,
            $e->getMessage(),
            ['trace' => $e->getTraceAsString()]
        );
        
        $this->respondServerError($e->getMessage());
    }
}
