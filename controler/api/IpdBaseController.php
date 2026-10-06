<?php
namespace GM_HMS\Controllers\api;

abstract class IpdBaseController {
    protected $model;
    
    protected function jsonResponse($success, $data = null, $message = '', $statusCode = 200) {
        if (ob_get_length()) ob_clean();
        
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        // --- API SECURITY HEADERS ---
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: DENY");
        header("X-XSS-Protection: 1; mode=block");
        // ----------------------------
        
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
    
    protected function success($data = null, $message = '', $statusCode = 200) {
        $this->jsonResponse(true, $data, $message, $statusCode);
    }
    
    protected function error($message = 'Error', $statusCode = 400, $data = null) {
        $this->jsonResponse(false, $data, $message, $statusCode);
    }
    
    protected function getRequestData() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        if ($method === 'GET') {
            return $_GET;
        }
        
        $data = [];
        if (!empty($_POST)) {
            $data = $_POST;
        } else {
            $input = file_get_contents('php://input');
            if (!empty($input)) {
                $json = json_decode($input, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data = $json;
                } else {
                    parse_str($input, $data);
                }
            }
        }
        return $data;
    }
    
    protected function getParam($key, $default = null) {
        $data = $this->getRequestData();
        return isset($data[$key]) ? $data[$key] : $default;
    }
    
    protected function getPagination() {
        $page = (int)$this->getParam('page', 1);
        $limit = (int)$this->getParam('limit', 10);
        
        if ($page < 1) $page = 1;
        if ($limit < 1 || $limit > 100) $limit = 10;
        
        return [
            'limit' => $limit,
            'offset' => ($page - 1) * $limit,
            'page' => $page
        ];
    }
    
    protected function validateRequired($data, $fields) {
        $errors = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $errors[] = "Field '{$field}' is required";
            }
        }
        return $errors;
    }

    /**
     * Layer 2: Sanitize input to prevent XSS (Cross-Site Scripting)
     */
    protected function sanitizeInput($input) {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = $this->sanitizeInput($value);
            }
            return $input;
        }
        if ($input === null) return null;
        $clean = strip_tags(trim((string)$input));
        return htmlspecialchars($clean, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Layer 2: Validate string length to prevent oversized data injection (e.g., matching VARCHAR(200))
     */
    protected function validateStringLength($input, $fieldName, $maxLength = 200) {
        if ($input !== null && mb_strlen((string)$input) > $maxLength) {
            return "Security Error: Field '{$fieldName}' exceeds the maximum allowed length of {$maxLength} characters.";
        }
        return null;
    }
    
    public function handleRequest() {
        // --- API SECURITY LAYER ---
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 1. Force Authentication
        if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
            $this->error('Unauthorized API access. Please log in.', 401);
            return;
        }

        // 2. Block Automated Tools
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (empty($userAgent) || preg_match('/(curl|postman|python|wget|bot)/i', $userAgent)) {
            $this->error('Automated tools are not allowed.', 403);
            return;
        }
        // --------------------------

        try {
            $method = $_SERVER['REQUEST_METHOD'];
            
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
                case 'OPTIONS':
                    http_response_code(200);
                    exit;
                default:
                    $this->error('Method not allowed', 405);
            }
        } catch (\Exception $e) {
            error_log("IPD API Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
            $this->error($e->getMessage(), 500);
        }
    }
    
    protected function handleGet(): void { $this->error('Method not implemented', 405); }
    protected function handlePost(): void { $this->error('Method not implemented', 405); }
    protected function handlePut(): void { $this->error('Method not implemented', 405); }
    protected function handleDelete(): void { $this->error('Method not implemented', 405); }
}
