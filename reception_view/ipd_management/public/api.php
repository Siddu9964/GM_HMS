<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// STRICTLY DISABLE ALL ERROR OUTPUT
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

/**
 * API Entry Point
 * 
 * Single entry point for all IPD Management API requests
 * Handles routing, error handling, and CORS
 * 
 * @package IPD_Management
 */

// Start output buffering to catch any stray output
ob_start();

// Include Main Project Autoloader
require_once __DIR__ . '/../../../core/Autoloader.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Load router
    $router = require_once __DIR__ . '/../routes/api.php';
    
    // Get request URI
    $requestUri = $_SERVER['REQUEST_URI'];
    
    // Remove base path and api.php
    $basePath = '/GM_HMS/reception_view/ipd_management/public/';
    if (stripos($requestUri, $basePath) === 0) {
        $requestUri = substr($requestUri, strlen($basePath));
    }
    
    // Remove api.php if present
    if (stripos($requestUri, 'api.php/') === 0) {
        $requestUri = substr($requestUri, 8); // Remove 'api.php/'
    }
    
    // Route request
    $controllerName = $router->route($requestUri);
    
    if (!$controllerName) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Endpoint not found'
        ]);
        exit;
    }
    
    // Load and instantiate controller
    $controllerFile = __DIR__ . '/../controllers/' . $controllerName . '.php';
    
    if (!file_exists($controllerFile)) {
        throw new Exception('Controller file not found');
    }
    
    require_once $controllerFile;
    
    if (!class_exists($controllerName)) {
        throw new Exception('Controller class not found');
    }
    
    $controller = new $controllerName();
    $controller->handleRequest();
    
} catch (Throwable $e) {
    // Log error
    $logMessage = date('[Y-m-d H:i:s] ') . 'API Error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine() . PHP_EOL;
    file_put_contents(__DIR__ . '/debug_log.txt', $logMessage, FILE_APPEND);
    error_log('API Error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
    
    // Send error response
    if (ob_get_length()) ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'debug_error' => $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()
    ]);
}
