<?php
namespace GM_HMS\Security;

use Normalizer;
use HTMLPurifier;
use HTMLPurifier_Config;

class InputSanitizer {
    
    /**
     * Sanitize string for HTML output (XSS prevention)
     * 
     * @param string $value Value to sanitize
     * @param bool $allowHtml Allow safe HTML tags
     * @return string Sanitized value
     */
    public function sanitizeString($value, $allowHtml = false) {
        if (!is_string($value)) {
            return '';
        }
        
        // Normalize Unicode
        $value = $this->normalizeUnicode($value);
        
        if ($allowHtml) {
            // Allow only safe HTML tags
            $allowedTags = '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3>';
            $value = strip_tags($value, $allowedTags);
            
            // Remove dangerous attributes
            $value = $this->removeDangerousAttributes($value);
        } else {
            // Convert all HTML entities
            $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        
        return $value;
    }
    
    /**
     * Sanitize for database (additional layer, use prepared statements!)
     * 
     * @param string $value Value to sanitize
     * @return string Sanitized value
     */
    public function sanitizeForDatabase($value) {
        // Note: This is NOT a replacement for prepared statements!
        // It's an additional security layer
        
        if (!is_string($value)) {
            return $value;
        }
        
        // Remove null bytes
        $value = str_replace("\0", '', $value);
        
        // Normalize Unicode
        $value = $this->normalizeUnicode($value);
        
        return $value;
    }
    
    /**
     * Sanitize email
     * 
     * @param string $email Email to sanitize
     * @return string Sanitized email
     */
    public function sanitizeEmail($email) {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
    
    /**
     * Sanitize URL
     * 
     * @param string $url URL to sanitize
     * @return string Sanitized URL
     */
    public function sanitizeUrl($url) {
        $url = filter_var($url, FILTER_SANITIZE_URL);
        
        // Prevent javascript: and data: URLs
        if (preg_match('/^(javascript|data|vbscript):/i', $url)) {
            return '';
        }
        
        return $url;
    }
    
    /**
     * Sanitize integer
     * 
     * @param mixed $value Value to sanitize
     * @return int Sanitized integer
     */
    public function sanitizeInt($value) {
        return (int)filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }
    
    /**
     * Sanitize float
     * 
     * @param mixed $value Value to sanitize
     * @return float Sanitized float
     */
    public function sanitizeFloat($value) {
        return (float)filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
    
    /**
     * Sanitize filename
     * 
     * @param string $filename Filename to sanitize
     * @return string Sanitized filename
     */
    public function sanitizeFilename($filename) {
        // Remove path traversal attempts
        $filename = basename($filename);
        
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Prevent double extensions
        $filename = preg_replace('/\.+/', '.', $filename);
        
        // Limit length
        if (strlen($filename) > 255) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = substr($filename, 0, 255 - strlen($ext) - 1);
            $filename = $name . '.' . $ext;
        }
        
        return $filename;
    }
    
    /**
     * Sanitize path (prevent directory traversal)
     * 
     * @param string $path Path to sanitize
     * @return string Sanitized path
     */
    public function sanitizePath($path) {
        // Remove null bytes
        $path = str_replace("\0", '', $path);
        
        // Remove path traversal attempts
        $path = str_replace(['../', '..\\', '../', '..\\'], '', $path);
        
        // Normalize slashes
        $path = str_replace('\\', '/', $path);
        
        // Remove multiple slashes
        $path = preg_replace('#/+#', '/', $path);
        
        return $path;
    }
    
    /**
     * Sanitize array recursively
     * 
     * @param array $data Array to sanitize
     * @param callable $sanitizer Sanitizer function
     * @return array Sanitized array
     */
    public function sanitizeArray($data, $sanitizer = null) {
        if (!is_array($data)) {
            return [];
        }
        
        $sanitizer = $sanitizer ?? [$this, 'sanitizeString'];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value, $sanitizer);
            } else {
                $data[$key] = call_user_func($sanitizer, $value);
            }
        }
        
        return $data;
    }
    
    /**
     * Sanitize JSON input
     * 
     * @param string $json JSON string
     * @return array|null Sanitized data or null on error
     */
    public function sanitizeJson($json) {
        if (!is_string($json)) {
            return null;
        }
        
        // Decode JSON
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        
        // Sanitize the decoded data
        return $this->sanitizeArray($data);
    }
    
    /**
     * Sanitize SQL LIKE pattern
     * 
     * @param string $value Value to sanitize
     * @return string Sanitized pattern
     */
    public function sanitizeLikePattern($value) {
        // Escape special LIKE characters
        $value = str_replace(['%', '_'], ['\\%', '\\_'], $value);
        return $value;
    }
    
    /**
     * Remove dangerous HTML attributes
     * 
     * @param string $html HTML to clean
     * @return string Cleaned HTML
     */
    private function removeDangerousAttributes($html) {
        // Remove event handlers (onclick, onload, etc.)
        $html = preg_replace('/\s*on\w+\s*=\s*["\']?[^"\']*["\']?/i', '', $html);
        
        // Remove javascript: URLs
        $html = preg_replace('/href\s*=\s*["\']?\s*javascript:/i', 'href="#"', $html);
        
        // Remove data: URLs
        $html = preg_replace('/src\s*=\s*["\']?\s*data:/i', 'src="#"', $html);
        
        // Remove style attributes (can contain expressions)
        $html = preg_replace('/\s*style\s*=\s*["\']?[^"\']*["\']?/i', '', $html);
        
        return $html;
    }
    
    /**
     * Normalize Unicode to prevent homograph attacks
     * 
     * @param string $value Value to normalize
     * @return string Normalized value
     */
    private function normalizeUnicode($value) {
        if (!function_exists('normalizer_normalize')) {
            return $value;
        }
        
        // Normalize to NFC (Canonical Decomposition followed by Canonical Composition)
        return normalizer_normalize($value, Normalizer::FORM_C);
    }
    
    /**
     * Sanitize for shell command (use with extreme caution!)
     * 
     * @param string $value Value to sanitize
     * @return string Sanitized value
     */
    public function sanitizeShellArg($value) {
        // WARNING: Avoid shell commands when possible!
        // Use PHP functions instead
        return escapeshellarg($value);
    }
    
    /**
     * Sanitize HTML for output
     * 
     * @param string $html HTML to sanitize
     * @return string Sanitized HTML
     */
    public function sanitizeHtml($html) {
        // Use HTMLPurifier if available, otherwise basic sanitization
        if (class_exists('HTMLPurifier')) {
            $config = HTMLPurifier_Config::createDefault();
            $purifier = new HTMLPurifier($config);
            return $purifier->purify($html);
        }
        
        // Basic sanitization
        $allowedTags = '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><code><pre>';
        $html = strip_tags($html, $allowedTags);
        $html = $this->removeDangerousAttributes($html);
        
        return $html;
    }
    
    /**
     * Sanitize phone number
     * 
     * @param string $phone Phone to sanitize
     * @return string Sanitized phone
     */
    public function sanitizePhone($phone) {
        // Keep only numbers and +
        return preg_replace('/[^0-9+]/', '', $phone);
    }
    
    /**
     * Sanitize alphanumeric
     * 
     * @param string $value Value to sanitize
     * @return string Sanitized value
     */
    public function sanitizeAlphanumeric($value) {
        return preg_replace('/[^a-zA-Z0-9]/', '', $value);
    }
    
    /**
     * Sanitize username
     * 
     * @param string $username Username to sanitize
     * @return string Sanitized username
     */
    public function sanitizeUsername($username) {
        // Allow alphanumeric, underscore, hyphen
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $username);
    }
    
    /**
     * Strip all tags and special characters
     * 
     * @param string $value Value to sanitize
     * @return string Sanitized value
     */
    public function stripAll($value) {
        $value = strip_tags($value);
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return $value;
    }
    
    /**
     * Sanitize boolean
     * 
     * @param mixed $value Value to sanitize
     * @return bool Sanitized boolean
     */
    public function sanitizeBoolean($value) {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
    
    /**
     * Sanitize request data (GET/POST)
     * 
     * @param array $data Request data
     * @return array Sanitized data
     */
    public function sanitizeRequest($data) {
        return $this->sanitizeArray($data, function($value) {
            if (is_string($value)) {
                return $this->sanitizeString($value, false);
            }
            return $value;
        });
    }
    
    /**
     * Remove invisible characters
     * 
     * @param string $value Value to clean
     * @return string Cleaned value
     */
    public function removeInvisibleCharacters($value) {
        // Remove control characters except tab, newline, carriage return
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
    }
}
