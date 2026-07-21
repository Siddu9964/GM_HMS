<?php
namespace GM_HMS\Security;

class JSONValidator {
    private $errors = [];
    private $validator;
    
    public function __construct() {
        $this->validator = new InputValidator();
    }
    
    /**
     * Validate JSON request
     * 
     * @param string $json JSON string
     * @param int $maxSize Maximum JSON size in bytes
     * @return array|null Decoded data or null on error
     */
    public function validateRequest($json, $maxSize = 1048576) {
        $this->errors = [];
        
        // Check if input is string
        if (!is_string($json)) {
            $this->errors[] = 'Invalid JSON input';
            return null;
        }
        
        // Check size limit
        if (strlen($json) > $maxSize) {
            $this->errors[] = 'JSON payload too large';
            return null;
        }
        
        // Decode JSON
        $data = json_decode($json, true);
        
        // Check for JSON errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->errors[] = 'Invalid JSON: ' . json_last_error_msg();
            return null;
        }
        
        // Check depth (prevent deeply nested attacks)
        if ($this->getDepth($data) > 10) {
            $this->errors[] = 'JSON nesting too deep';
            return null;
        }
        
        return $data;
    }
    
    /**
     * Validate against schema
     * 
     * @param array $data Data to validate
     * @param array $schema Validation schema
     * @return bool Valid
     */
    public function validateSchema($data, $schema) {
        $this->errors = [];
        
        if (!is_array($data)) {
            $this->errors[] = 'Data must be an array';
            return false;
        }
        
        return $this->validateObject($data, $schema, '');
    }
    
    /**
     * Validate object against schema
     * 
     * @param array $data Data to validate
     * @param array $schema Schema definition
     * @param string $path Current path (for error messages)
     * @return bool Valid
     */
    private function validateObject($data, $schema, $path) {
        $valid = true;
        
        // Check required fields
        if (isset($schema['required'])) {
            foreach ($schema['required'] as $field) {
                if (!isset($data[$field])) {
                    $this->errors[] = "Required field missing: $path$field";
                    $valid = false;
                }
            }
        }
        
        // Validate fields
        if (isset($schema['properties'])) {
            foreach ($schema['properties'] as $field => $rules) {
                $fieldPath = $path ? "$path.$field" : $field;
                
                // Skip if field not present and not required
                if (!isset($data[$field])) {
                    continue;
                }
                
                $value = $data[$field];
                
                // Validate type
                if (isset($rules['type'])) {
                    if (!$this->validateType($value, $rules['type'], $fieldPath)) {
                        $valid = false;
                        continue;
                    }
                }
                
                // Validate specific rules
                if (!$this->validateFieldRules($value, $rules, $fieldPath)) {
                    $valid = false;
                }
            }
        }
        
        // Check for additional properties
        if (isset($schema['additionalProperties']) && $schema['additionalProperties'] === false) {
            $allowedFields = array_keys($schema['properties'] ?? []);
            $extraFields = array_diff(array_keys($data), $allowedFields);
            
            if (!empty($extraFields)) {
                $this->errors[] = "Additional properties not allowed: " . implode(', ', $extraFields);
                $valid = false;
            }
        }
        
        return $valid;
    }
    
    /**
     * Validate field type
     * 
     * @param mixed $value Value to validate
     * @param string $type Expected type
     * @param string $field Field name
     * @return bool Valid
     */
    private function validateType($value, $type, $field) {
        $valid = false;
        
        switch ($type) {
            case 'string':
                $valid = is_string($value);
                break;
            case 'integer':
                $valid = is_int($value);
                break;
            case 'number':
                $valid = is_numeric($value);
                break;
            case 'boolean':
                $valid = is_bool($value);
                break;
            case 'array':
                $valid = is_array($value) && array_values($value) === $value; // Indexed array
                break;
            case 'object':
                $valid = is_array($value) && array_values($value) !== $value; // Associative array
                break;
            case 'null':
                $valid = is_null($value);
                break;
        }
        
        if (!$valid) {
            $this->errors[] = "$field must be of type $type";
        }
        
        return $valid;
    }
    
    /**
     * Validate field-specific rules
     * 
     * @param mixed $value Value to validate
     * @param array $rules Validation rules
     * @param string $field Field name
     * @return bool Valid
     */
    private function validateFieldRules($value, $rules, $field) {
        $valid = true;
        
        // String validations
        if (isset($rules['minLength']) && is_string($value)) {
            if (mb_strlen($value) < $rules['minLength']) {
                $this->errors[] = "$field must be at least {$rules['minLength']} characters";
                $valid = false;
            }
        }
        
        if (isset($rules['maxLength']) && is_string($value)) {
            if (mb_strlen($value) > $rules['maxLength']) {
                $this->errors[] = "$field must not exceed {$rules['maxLength']} characters";
                $valid = false;
            }
        }
        
        if (isset($rules['pattern']) && is_string($value)) {
            if (!preg_match($rules['pattern'], $value)) {
                $this->errors[] = "$field format is invalid";
                $valid = false;
            }
        }
        
        // Number validations
        if (isset($rules['minimum']) && is_numeric($value)) {
            if ($value < $rules['minimum']) {
                $this->errors[] = "$field must be at least {$rules['minimum']}";
                $valid = false;
            }
        }
        
        if (isset($rules['maximum']) && is_numeric($value)) {
            if ($value > $rules['maximum']) {
                $this->errors[] = "$field must not exceed {$rules['maximum']}";
                $valid = false;
            }
        }
        
        // Array validations
        if (isset($rules['minItems']) && is_array($value)) {
            if (count($value) < $rules['minItems']) {
                $this->errors[] = "$field must have at least {$rules['minItems']} items";
                $valid = false;
            }
        }
        
        if (isset($rules['maxItems']) && is_array($value)) {
            if (count($value) > $rules['maxItems']) {
                $this->errors[] = "$field must not exceed {$rules['maxItems']} items";
                $valid = false;
            }
        }
        
        // Enum validation
        if (isset($rules['enum']) && is_array($rules['enum'])) {
            if (!in_array($value, $rules['enum'], true)) {
                $this->errors[] = "$field must be one of: " . implode(', ', $rules['enum']);
                $valid = false;
            }
        }
        
        // Format validation
        if (isset($rules['format'])) {
            if (!$this->validateFormat($value, $rules['format'], $field)) {
                $valid = false;
            }
        }
        
        // Nested object validation
        if (isset($rules['properties']) && is_array($value)) {
            if (!$this->validateObject($value, $rules, $field)) {
                $valid = false;
            }
        }
        
        // Array items validation
        if (isset($rules['items']) && is_array($value)) {
            foreach ($value as $index => $item) {
                $itemPath = "$field[$index]";
                
                if (isset($rules['items']['type'])) {
                    if (!$this->validateType($item, $rules['items']['type'], $itemPath)) {
                        $valid = false;
                    }
                }
                
                if (!$this->validateFieldRules($item, $rules['items'], $itemPath)) {
                    $valid = false;
                }
            }
        }
        
        return $valid;
    }
    
    /**
     * Validate format
     * 
     * @param mixed $value Value to validate
     * @param string $format Format type
     * @param string $field Field name
     * @return bool Valid
     */
    private function validateFormat($value, $format, $field) {
        $valid = true;
        
        switch ($format) {
            case 'email':
                $valid = $this->validator->email($value, $field);
                break;
            case 'url':
                $valid = $this->validator->url($value, $field);
                break;
            case 'date':
                $valid = $this->validator->date($value, $field);
                break;
            case 'datetime':
                $valid = $this->validator->date($value, $field, 'Y-m-d H:i:s');
                break;
            case 'ip':
                $valid = $this->validator->ip($value, $field);
                break;
            case 'phone':
                $valid = $this->validator->phone($value, $field);
                break;
            case 'username':
                $valid = $this->validator->username($value, $field);
                break;
        }
        
        if (!$valid) {
            $this->errors = array_merge($this->errors, $this->validator->getErrors());
            $this->validator->clearErrors();
        }
        
        return $valid;
    }
    
    /**
     * Get maximum depth of nested array
     * 
     * @param array $data Array to check
     * @param int $depth Current depth
     * @return int Maximum depth
     */
    private function getDepth($data, $depth = 0) {
        if (!is_array($data)) {
            return $depth;
        }
        
        $maxDepth = $depth;
        foreach ($data as $value) {
            if (is_array($value)) {
                $maxDepth = max($maxDepth, $this->getDepth($value, $depth + 1));
            }
        }
        
        return $maxDepth;
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
     * Check if has errors
     * 
     * @return bool Has errors
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
}
