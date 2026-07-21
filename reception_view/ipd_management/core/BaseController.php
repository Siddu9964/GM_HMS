<?php
/**
 * Base Controller Class for IPD Management System
 * 
 * Provides common controller functionality including:
 * - JSON response formatting
 * - HTTP method handling
 * - Error handling
 * - Input validation
 * 
 * @package IPD_Management
 * @author GM HMS Development Team
 * @version 1.0.0
 */

abstract class BaseController {
    protected $model;
    
    /**
     * Send JSON response
     * 
     * @param bool $success Success status
     * @param mixed $data Response data
     * @param string $message Response message
     * @param int $statusCode HTTP status code
     */
    protected function jsonResponse($success, $data = null, $message = '', $statusCode = 200) {
        // Clean any buffered output (e.g. warnings/errors) before sending JSON
        if (ob_get_length()) ob_clean();
        
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        $response = [
            'success' => $success,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response);
        exit;
    }
    
    /**
     * Send success response
     * 
     * @param mixed $data Response data
     * @param string $message Success message
     * @param int $statusCode HTTP status code
     */
    protected function success($data = null, $message = 'Operation successful', $statusCode = 200) {
        $this->jsonResponse(true, $data, $message, $statusCode);
    }
    
    /**
     * Send error response
     * 
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param mixed $errors Additional error details
     */
    protected function error($message = 'Operation failed', $statusCode = 400, $errors = null) {
        // Log the error
        $logMessage = date('[Y-m-d H:i:s] ') . "HTTP {$statusCode}: {$message}";
        if ($errors) {
            $logMessage .= ' | Errors: ' . json_encode($errors);
        }
        $logMessage .= PHP_EOL;
        file_put_contents(__DIR__ . '/../public/debug_log.txt', $logMessage, FILE_APPEND);

        $data = $errors ? ['errors' => $errors] : null;
        $this->jsonResponse(false, $data, $message, $statusCode);
    }
    
    /**
     * Get request method
     * 
     * @return string HTTP method (GET, POST, PUT, DELETE)
     */
    protected function getMethod() {
        return $_SERVER['REQUEST_METHOD'];
    }
    
    /**
     * Get JSON input data
     * 
     * @return array Decoded JSON data
     */
    protected function getJsonInput() {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }
    
    /**
     * Get request data (supports both JSON and form data)
     * 
     * @return array Request data
     */
    protected function getRequestData() {
        $method = $this->getMethod();
        
        if ($method === 'GET') {
            return $_GET;
        }
        
        if ($method === 'POST' && !empty($_POST)) {
            return $_POST;
        }
        
        // For PUT, DELETE, or POST with JSON
        return $this->getJsonInput();
    }
    
    /**
     * Get query parameter
     * 
     * @param string $key Parameter name
     * @param mixed $default Default value
     * @return mixed Parameter value
     */
    protected function getParam($key, $default = null) {
        return $_GET[$key] ?? $default;
    }
    
    /**
     * Validate required fields in data
     * 
     * @param array $data Data to validate
     * @param array $required Required field names
     * @return array Array of error messages (empty if valid)
     */
    protected function validateRequired($data, $required) {
        $errors = [];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        return $errors;
    }
    
    /**
     * Set CORS headers
     */
    protected function setCorsHeaders() {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        // Handle preflight requests
        if ($this->getMethod() === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
    
    /**
     * Require specific HTTP method
     * 
     * @param string|array $methods Allowed method(s)
     */
    protected function requireMethod($methods) {
        $methods = (array)$methods;
        $currentMethod = $this->getMethod();
        
        if (!in_array($currentMethod, $methods)) {
            $this->error('Method not allowed', 405);
        }
    }
    
    /**
     * Get pagination parameters
     * 
     * @return array ['limit' => int, 'offset' => int, 'page' => int]
     */
    protected function getPagination() {
        $page = max(1, (int)$this->getParam('page', 1));
        $limit = min(100, max(1, (int)$this->getParam('limit', 10)));
        $offset = ($page - 1) * $limit;
        
        return [
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset
        ];
    }
    
    /**
     * Log error
     * 
     * @param string $message Error message
     * @param Exception $e Exception object
     */
    protected function logError($message, $e = null) {
        $logMessage = "[IPD Error] {$message}";
        
        if ($e) {
            $logMessage .= " | Exception: " . $e->getMessage();
            $logMessage .= " | File: " . $e->getFile();
            $logMessage .= " | Line: " . $e->getLine();
        }
        
        error_log($logMessage);
    }
    
    /**
     * Handle controller action
     * Routes request to appropriate method based on HTTP method
     */
    public function handleRequest() {
        try {
            $this->setCorsHeaders();
            
            $method = $this->getMethod();
            
            switch ($method) {
                case 'GET':
                    $this->handleGet();
                    break;
                case 'POST':
                    $this->handlePost();
                    break;
                case 'PUT':
                    $this->handlePut();
                    break;
                case 'DELETE':
                    $this->handleDelete();
                    break;
                default:
                    $this->error('Method not allowed', 405);
            }
        } catch (Exception $e) {
            $this->logError('Controller error', $e);
            $this->error('Internal server error: ' . $e->getMessage(), 500, ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }
    
    /**
     * Handle GET requests (to be implemented by child classes)
     */
    protected function handleGet() {
        $this->error('GET method not implemented', 501);
    }
    
    /**
     * Handle POST requests (to be implemented by child classes)
     */
    protected function handlePost() {
        $this->error('POST method not implemented', 501);
    }
    
    /**
     * Handle PUT requests (to be implemented by child classes)
     */
    protected function handlePut() {
        $this->error('PUT method not implemented', 501);
    }
    
    /**
     * Handle DELETE requests (to be implemented by child classes)
     */
    protected function handleDelete() {
        $this->error('DELETE method not implemented', 501);
    }
}
