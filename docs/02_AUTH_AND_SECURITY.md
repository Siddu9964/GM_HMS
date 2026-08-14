# GM_HMS — Authentication & Security Documentation

---

> **Document Type:** Security Reference  
> **Version:** 2.0.0  
> **Audience:** Technical Team · IT Management  
> **Date:** August 2026

---

## Table of Contents
1. [Authentication Flow](#1-authentication-flow)
2. [Session Management](#2-session-management)
3. [Role-Based Access Control](#3-role-based-access-control)
4. [Password Security](#4-password-security)
5. [SQL Injection Prevention](#5-sql-injection-prevention)
6. [Input Validation](#6-input-validation)
7. [Error Handling & Logging](#7-error-handling--logging)
8. [Rate Limiting](#8-rate-limiting)
9. [CSRF Protection](#9-csrf-protection)
10. [Audit Logging](#10-audit-logging)
11. [Security Flow Diagram](#11-security-flow-diagram)

---

## 1. Authentication Flow

### Login Process

```
Step 1: User submits credentials
        POST /api/auth/login
        Body: { username, password }

Step 2: AuthController::login()
        → Validates required fields
        → Sanitizes input

Step 3: AuthenticationManager::authenticate()
        SELECT id, username, password, role, is_active
        FROM user WHERE username = ?
        [Parameterized query — no SQL injection possible]

Step 4: Verify password
        password_verify($inputPassword, $storedHash)
        [bcrypt comparison — timing-safe]

Step 5: If inactive account
        Return 401: "Account is disabled"

Step 6: On success:
        session_regenerate_id(true)  // Prevent session fixation
        $_SESSION['user_id']    = $user['id']
        $_SESSION['role']       = strtolower($user['role'])
        $_SESSION['username']   = $user['username']
        $_SESSION['full_name']  = $user['full_name']
        // Doctor-specific:
        $_SESSION['doctor_id']  = $user['doctor_id']

Step 7: Return redirect URL based on role:
        admin        → /view/admin_dashboard.php
        doctor       → /doctors_view/dashboard.php
        receptionist → /reception_view/index.php
        nurse        → /nurse_view/dashboard.php
        pharmacist   → /pharmacy_view/dashboard.php
```

### Logout Process

```
POST /api/auth/logout
→ $_SESSION = []             // Clear all session data
→ session_destroy()           // Destroy server-side session
→ setcookie(session_name(), '', time() - 3600)  // Delete cookie
→ Return: { success: true }
```

---

## 2. Session Management

### Session Variables

| Variable | Set At | Used By |
|----------|--------|---------|
| `user_id` | Login | All controllers |
| `role` | Login | Role guards |
| `username` | Login | UI display |
| `full_name` | Login | UI display |
| `doctor_id` | Login (doctors) | Doctor endpoints |
| `staff_id` | Login (staff) | Staff operations |
| `hospital_branch` | Login | DB selection |

### Session Security Measures

1. **Session Regeneration:** `session_regenerate_id(true)` on login prevents session fixation attacks

2. **Session Data Clearance on Logout:** All `$_SESSION` data cleared before destroy

3. **Timeout:** Configured in PHP `session.gc_maxlifetime` (typically 30-60 minutes)

4. **Cookie Flags:**
   ```php
   session.cookie_httponly = 1  // Prevent JS access to session cookie
   session.cookie_samesite = Lax  // CSRF protection
   ```

---

## 3. Role-Based Access Control

### Guard Implementation

Every role-specific page includes a guard at the top:

```php
// Admin pages:
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /GM_HMS/login.php?reason=unauthorized');
    exit();
}

// Doctor pages:
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    header('Location: /GM_HMS/login.php');
    exit();
}
```

### API Authorization

In `BaseController::__construct()`:

```php
protected function requireAuth($allowedRoles = []) {
    if (!isset($_SESSION['user_id'])) {
        $this->error('Authentication required', 401);
    }
    if (!empty($allowedRoles) && !in_array($_SESSION['role'], $allowedRoles)) {
        $this->error('Insufficient permissions', 403);
    }
}
```

### Role Permission Summary

| Resource | Admin | Doctor | Receptionist | Nurse | Pharmacist |
|---------|:-----:|:------:|:------------:|:-----:|:----------:|
| Create patients | ✅ | ❌ | ✅ | ❌ | ❌ |
| Delete patients | ✅ | ❌ | ❌ | ❌ | ❌ |
| SOAP notes | ❌ | ✅ | ❌ | ❌ | ❌ |
| Prescriptions | ❌ | ✅ | 👁 | 👁 | 👁 |
| IPD billing | ✅ | ❌ | ✅ | ❌ | ❌ |
| Discharge | ✅ | ❌ | ✅ | 🔔 | ❌ |
| Pharmacy sales | ❌ | ❌ | ❌ | ❌ | ✅ |
| Staff management | ✅ | ❌ | ❌ | ❌ | ❌ |
| Lab results | ✅ | ✅ | ❌ | 👁 | ❌ |
| Reports | ✅ | ❌ | 👁 | ❌ | 👁 |

✅ Full | 👁 View Only | 🔔 Notification Only | ❌ No Access

---

## 4. Password Security

### Storage

All passwords are stored as bcrypt hashes:
```php
// On account creation:
$hash = password_hash($plainText, PASSWORD_BCRYPT, ['cost' => 12]);
// INSERT user SET password = $hash

// On login verification:
if (!password_verify($inputPassword, $storedHash)) {
    return false; // Auth failed
}
```

### bcrypt Properties
- **Algorithm:** Blowfish cipher based
- **Cost factor:** 12 (configurable — higher = slower = more secure)
- **Salt:** Automatically generated and included in hash
- **Output:** 60-character string

### Password Change

```php
// POST /api/auth/change-password
1. Verify current password: password_verify(current, stored_hash)
2. Validate new password: strlen >= 8
3. Generate new hash: password_hash(new_password, PASSWORD_BCRYPT)
4. UPDATE user SET password = new_hash WHERE id = session_user_id
```

---

## 5. SQL Injection Prevention

### SecureDatabase Pattern

All database queries go through `SecureDatabase` which enforces parameterized queries:

```php
// ❌ NEVER DONE (vulnerable):
$query = "SELECT * FROM patient WHERE phone = '$phone'";
$result = $conn->query($query);

// ✅ ALWAYS DONE (safe):
$result = $this->db->fetchAll(
    "SELECT * FROM patient WHERE phone = ?",
    [$phone]
);
```

### How it Works

```php
// SecureDatabase::execute($query, $params):
$stmt = $this->connection->prepare($query);
// Prepare separates SQL from data

$stmt->bind_param($types, ...$params);
// Data bound as typed parameters — never interpolated into SQL

$stmt->execute();
// SQL and data sent to MySQL separately
// Injection impossible — SQL structure fixed at prepare time
```

### Coverage

- 100% of model queries use `SecureDatabase`
- Direct `mysqli` connections (legacy code, e.g., `dismiss_discharge_notification.php`) still use prepared statements
- No raw `$_GET` or `$_POST` is ever directly interpolated into SQL

---

## 6. Input Validation

### BaseController Validation

```php
protected function validateInput($data, $schema) {
    $errors = [];
    
    // Check required fields
    foreach ($schema['required'] as $field) {
        if (empty($data[$field])) {
            $errors[] = "$field is required";
        }
    }
    
    // Type validation
    foreach ($schema['properties'] as $field => $rules) {
        if (!isset($data[$field])) continue;
        
        if ($rules['type'] === 'email' && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "$field must be a valid email";
        }
        if (isset($rules['minLength']) && strlen($data[$field]) < $rules['minLength']) {
            $errors[] = "$field must be at least {$rules['minLength']} characters";
        }
        if (isset($rules['pattern']) && !preg_match($rules['pattern'], $data[$field])) {
            $errors[] = "$field format is invalid";
        }
    }
    
    if (!empty($errors)) {
        $this->error('Validation failed', 400, $errors);
    }
}
```

### Patient Validation Schema
```php
$schema = [
    'required' => ['first_name', 'age', 'sex', 'phone'],
    'properties' => [
        'first_name' => ['type' => 'string', 'minLength' => 2],
        'age'        => ['type' => 'integer', 'min' => 0, 'max' => 120],
        'phone'      => ['type' => 'string', 'pattern' => '/^\d{10}$/'],
        'email'      => ['type' => 'email'],
        'aadhar'     => ['type' => 'string', 'pattern' => '/^\d{12}$/']
    ]
];
```

---

## 7. Error Handling & Logging

### Production Error Handling

```php
// api/index.php
error_reporting(E_ALL);
ini_set('display_errors', 0);  // Never show errors to users in production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/api_errors.log');
```

### Exception Handling Pattern

```php
try {
    $result = $model->createBill($data);
    $this->success($result, 'Bill created successfully');
} catch (Exception $e) {
    error_log("[BillingController] " . $e->getMessage() . "\n" . $e->getTraceAsString());
    $this->error('An error occurred. Please try again.', 500);
    // Note: Error details NOT sent to client — only logged
}
```

### Error Log Files

| Log File | Contains |
|----------|---------|
| `api_errors.log` | PHP errors, unhandled exceptions |
| `debug.log` | Debug outputs from development |
| Apache `error.log` | Server-level errors |

---

## 8. Rate Limiting

### Implementation in BaseController

```php
protected function checkRateLimit($maxRequests = 100, $windowSeconds = 60) {
    $key = 'rate_limit_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    
    // Uses session-based counter (simple implementation)
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'start' => time()];
    }
    
    $elapsed = time() - $_SESSION[$key]['start'];
    
    if ($elapsed > $windowSeconds) {
        // Reset window
        $_SESSION[$key] = ['count' => 1, 'start' => time()];
    } else {
        $_SESSION[$key]['count']++;
        
        if ($_SESSION[$key]['count'] > $maxRequests) {
            $this->error('Too many requests. Please wait.', 429);
        }
    }
}
```

Rate limit: **100 requests per 60 seconds** per IP/session

---

## 9. CSRF Protection

### Form Token Validation

For sensitive form submissions:

```php
// Token generation (on page load):
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// In HTML form:
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

// On form submission:
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('CSRF token mismatch');
}
```

### AJAX Requests

For AJAX calls, CSRF protection is handled by:
1. Session-based authentication (session cookie not accessible to other sites)
2. `SameSite=Lax` cookie policy
3. Content-Type check (`application/json` only accepted for APIs)

---

## 10. Audit Logging

### AuditLogger

```php
// Database/AuditLogger.php
class AuditLogger {
    public static function log($action, $userId, $resource, $details = []) {
        // Logs to audit_log table or file
        INSERT INTO audit_log (
            action, user_id, resource, details_json, ip_address, created_at
        ) VALUES (?, ?, ?, ?, ?, NOW())
    }
}
```

### Logged Events

| Event | Trigger |
|-------|---------|
| User login | Successful authentication |
| Failed login | Failed password attempt |
| User logout | Logout action |
| Bill created | OPD/IPD bill creation |
| Bill finalized | IPD discharge billing |
| Payment recorded | Any payment transaction |
| Patient deleted | Soft delete action |
| Password changed | Password update |
| Staff created/deleted | HR changes |

---

## 11. Security Flow Diagram

```
Client Request
      │
      ▼
Apache (.htaccess)
  ├── URL Rewriting
  └── No directory traversal (AllowOverride None for parent dirs)
      │
      ▼
PHP Entry Point (api/index.php or view/*.php)
  ├── session_start()
  ├── Error display disabled
  └── Autoloader loaded
      │
      ▼
Role Guard Check
  ├── Session exists? → No → Redirect to login
  ├── Role matches page? → No → Redirect to login
  └── Yes → Continue
      │
      ▼
Controller Instantiation
  ├── BaseController::__construct()
  │   ├── requireAuth() check
  │   └── checkRateLimit()
  └── Specific controller method
      │
      ▼
Input Processing
  ├── getRequestData() → JSON parse or form decode
  ├── validateInput() → Schema validation
  └── Sanitize: trim(), htmlspecialchars() where needed
      │
      ▼
Model Operation
  ├── SecureDatabase::execute($sql, $params)
  │   └── mysqli::prepare() → bind_param() → execute()
  └── Never raw interpolation
      │
      ▼
Response
  ├── success($data, $message) → HTTP 200/201 JSON
  ├── error($msg, $code) → HTTP 4xx/5xx JSON
  └── Error details logged, not exposed
```

---

*End of Document — Authentication & Security Documentation*

---
**Document Control** | Version 2.0 | August 2026
