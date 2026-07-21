<?php
namespace GM_HMS\Security;

use DateTime;

class InputValidator {
    private $errors = [];
    
    /**
     * Validate required field
     * 
     * @param mixed $value Value to validate
     * @param string $fieldName Field name for error messages
     * @return bool Valid
     */
    public function required($value, $fieldName = 'Field') {
        if (empty($value) && $value !== '0' && $value !== 0) {
            $this->errors[] = "$fieldName is required";
            return false;
        }
        return true;
    }
    
    /**
     * Validate string
     * 
     * @param mixed $value Value to validate
     * @param string $fieldName Field name
     * @param int $minLength Minimum length
     * @param int $maxLength Maximum length
     * @return bool Valid
     */
    public function string($value, $fieldName = 'Field', $minLength = null, $maxLength = null) {
        if (!is_string($value)) {
            $this->errors[] = "$fieldName must be a string";
            return false;
        }
        
        $length = mb_strlen($value, 'UTF-8');
        
        if ($minLength !== null && $length < $minLength) {
            $this->errors[] = "$fieldName must be at least $minLength characters";
            return false;
        }
        
        if ($maxLength !== null && $length > $maxLength) {
            $this->errors[] = "$fieldName must not exceed $maxLength characters";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate integer
     * 
     * @param mixed $value Value to validate
     * @param string $fieldName Field name
     * @param int $min Minimum value
     * @param int $max Maximum value
     * @return bool Valid
     */
    public function integer($value, $fieldName = 'Field', $min = null, $max = null) {
        if (!is_numeric($value) || (int)$value != $value) {
            $this->errors[] = "$fieldName must be an integer";
            return false;
        }
        
        $intValue = (int)$value;
        
        if ($min !== null && $intValue < $min) {
            $this->errors[] = "$fieldName must be at least $min";
            return false;
        }
        
        if ($max !== null && $intValue > $max) {
            $this->errors[] = "$fieldName must not exceed $max";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate float/decimal
     * 
     * @param mixed $value Value to validate
     * @param string $fieldName Field name
     * @param float $min Minimum value
     * @param float $max Maximum value
     * @return bool Valid
     */
    public function float($value, $fieldName = 'Field', $min = null, $max = null) {
        if (!is_numeric($value)) {
            $this->errors[] = "$fieldName must be a number";
            return false;
        }
        
        $floatValue = (float)$value;
        
        if ($min !== null && $floatValue < $min) {
            $this->errors[] = "$fieldName must be at least $min";
            return false;
        }
        
        if ($max !== null && $floatValue > $max) {
            $this->errors[] = "$fieldName must not exceed $max";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate email address
     * 
     * @param string $value Email to validate
     * @param string $fieldName Field name
     * @return bool Valid
     */
    public function email($value, $fieldName = 'Email') {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "$fieldName must be a valid email address";
            return false;
        }
        
        // DNS check disabled for local development
        // Additional check for common typos
        // $domain = substr(strrchr($value, "@"), 1);
        // if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
        //     $this->errors[] = "$fieldName domain does not exist";
        //     return false;
        // }
        
        return true;
    }
    
    /**
     * Validate URL
     * 
     * @param string $value URL to validate
     * @param string $fieldName Field name
     * @param bool $requireHttps Require HTTPS
     * @return bool Valid
     */
    public function url($value, $fieldName = 'URL', $requireHttps = false) {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[] = "$fieldName must be a valid URL";
            return false;
        }
        
        if ($requireHttps && strpos($value, 'https://') !== 0) {
            $this->errors[] = "$fieldName must use HTTPS";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate IP address
     * 
     * @param string $value IP to validate
     * @param string $fieldName Field name
     * @param bool $allowIPv6 Allow IPv6
     * @return bool Valid
     */
    public function ip($value, $fieldName = 'IP Address', $allowIPv6 = true) {
        $flags = FILTER_FLAG_IPV4;
        if ($allowIPv6) {
            $flags = FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6;
        }
        
        if (!filter_var($value, FILTER_VALIDATE_IP, $flags)) {
            $this->errors[] = "$fieldName must be a valid IP address";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate date
     * 
     * @param string $value Date to validate
     * @param string $fieldName Field name
     * @param string $format Expected format (default: Y-m-d)
     * @return bool Valid
     */
    public function date($value, $fieldName = 'Date', $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $value);
        if (!$d || $d->format($format) !== $value) {
            $this->errors[] = "$fieldName must be a valid date in format $format";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate pattern (regex)
     * 
     * @param string $value Value to validate
     * @param string $pattern Regex pattern
     * @param string $fieldName Field name
     * @param string $message Custom error message
     * @return bool Valid
     */
    public function pattern($value, $pattern, $fieldName = 'Field', $message = null) {
        if (!preg_match($pattern, $value)) {
            $this->errors[] = $message ?? "$fieldName format is invalid";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate phone number
     * 
     * @param string $value Phone to validate
     * @param string $fieldName Field name
     * @return bool Valid
     */
    public function phone($value, $fieldName = 'Phone') {
        // Remove common formatting characters
        $cleaned = preg_replace('/[^0-9+]/', '', $value);
        
        // Check length (international format)
        if (strlen($cleaned) < 10 || strlen($cleaned) > 15) {
            $this->errors[] = "$fieldName must be a valid phone number";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate username
     * 
     * @param string $value Username to validate
     * @param string $fieldName Field name
     * @return bool Valid
     */
    public function username($value, $fieldName = 'Username') {
        // Alphanumeric, underscore, hyphen, 3-30 characters
        if (!preg_match('/^[a-zA-Z0-9_-]{3,30}$/', $value)) {
            $this->errors[] = "$fieldName must be 3-30 characters (letters, numbers, underscore, hyphen)";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate password strength
     * 
     * @param string $value Password to validate
     * @param string $fieldName Field name
     * @param array $requirements Custom requirements
     * @return bool Valid
     */
    public function password($value, $fieldName = 'Password', $requirements = []) {
        $defaults = [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_special' => true
        ];
        
        $req = array_merge($defaults, $requirements);
        
        if (strlen($value) < $req['min_length']) {
            $this->errors[] = "$fieldName must be at least {$req['min_length']} characters";
            return false;
        }
        
        if ($req['require_uppercase'] && !preg_match('/[A-Z]/', $value)) {
            $this->errors[] = "$fieldName must contain at least one uppercase letter";
            return false;
        }
        
        if ($req['require_lowercase'] && !preg_match('/[a-z]/', $value)) {
            $this->errors[] = "$fieldName must contain at least one lowercase letter";
            return false;
        }
        
        if ($req['require_numbers'] && !preg_match('/[0-9]/', $value)) {
            $this->errors[] = "$fieldName must contain at least one number";
            return false;
        }
        
        if ($req['require_special'] && !preg_match('/[^a-zA-Z0-9]/', $value)) {
            $this->errors[] = "$fieldName must contain at least one special character";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate value is in array (whitelist)
     * 
     * @param mixed $value Value to validate
     * @param array $allowed Allowed values
     * @param string $fieldName Field name
     * @return bool Valid
     */
    public function inArray($value, $allowed, $fieldName = 'Field') {
        if (!in_array($value, $allowed, true)) {
            $this->errors[] = "$fieldName must be one of: " . implode(', ', $allowed);
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate array
     * 
     * @param mixed $value Value to validate
     * @param string $fieldName Field name
     * @param int $minItems Minimum items
     * @param int $maxItems Maximum items
     * @return bool Valid
     */
    public function isArray($value, $fieldName = 'Field', $minItems = null, $maxItems = null) {
        if (!is_array($value)) {
            $this->errors[] = "$fieldName must be an array";
            return false;
        }
        
        $count = count($value);
        
        if ($minItems !== null && $count < $minItems) {
            $this->errors[] = "$fieldName must have at least $minItems items";
            return false;
        }
        
        if ($maxItems !== null && $count > $maxItems) {
            $this->errors[] = "$fieldName must not exceed $maxItems items";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate boolean
     * 
     * @param mixed $value Value to validate
     * @param string $fieldName Field name
     * @return bool Valid
     */
    public function boolean($value, $fieldName = 'Field') {
        if (!is_bool($value) && $value !== '0' && $value !== '1' && $value !== 0 && $value !== 1) {
            $this->errors[] = "$fieldName must be a boolean";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate file upload
     * 
     * @param array $file $_FILES array element
     * @param string $fieldName Field name
     * @param array $options Validation options
     * @return bool Valid
     */
    public function file($file, $fieldName = 'File', $options = []) {
        if (!isset($file['error']) || is_array($file['error'])) {
            $this->errors[] = "$fieldName upload failed";
            return false;
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = "$fieldName upload error: " . $this->getUploadErrorMessage($file['error']);
            return false;
        }
        
        // Check file size
        $maxSize = $options['max_size'] ?? 5242880; // 5MB default
        if ($file['size'] > $maxSize) {
            $this->errors[] = "$fieldName exceeds maximum size of " . ($maxSize / 1048576) . "MB";
            return false;
        }
        
        // Check file type
        if (isset($options['allowed_types'])) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $options['allowed_types'])) {
                $this->errors[] = "$fieldName type not allowed. Allowed: " . implode(', ', $options['allowed_types']);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get upload error message
     * 
     * @param int $code Error code
     * @return string Error message
     */
    private function getUploadErrorMessage($code) {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        
        return $messages[$code] ?? 'Unknown upload error';
    }
    
    /**
     * Validate multiple fields
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return bool All valid
     */
    public function validate($data, $rules) {
        $this->errors = [];
        $allValid = true;
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            
            foreach ($fieldRules as $rule => $params) {
                if ($rule === 'required') {
                    if (!$this->required($value, $field)) {
                        $allValid = false;
                        break; // Skip other rules if required fails
                    }
                } elseif (method_exists($this, $rule)) {
                    $params = is_array($params) ? $params : [$params];
                    array_unshift($params, $value, $field);
                    
                    if (!call_user_func_array([$this, $rule], $params)) {
                        $allValid = false;
                    }
                }
            }
        }
        
        return $allValid;
    }
    
    /**
     * Get validation errors
     * 
     * @return array Errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Get first error
     * 
     * @return string|null First error
     */
    public function getFirstError() {
        return $this->errors[0] ?? null;
    }
    
    /**
     * Clear errors
     */
    public function clearErrors() {
        $this->errors = [];
    }
    
    /**
     * Check if has errors
     * 
     * @return bool Has errors
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
}
