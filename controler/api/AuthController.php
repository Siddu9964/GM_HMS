<?php
/**
 * ============================================================
 * AuthController — API Reference
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api
 * Auth     : No auth required for login. Bearer token or Session for others.
 * ------------------------------------------------------------
 *
 * 1. POST /api/auth/login
 *    Request Body:
 *      { "username": "admin", "password": "Admin@1234" }
 *    Response:
 *      { "status":"success", "role":"Admin",
 *        "user": { "id":1, "username":"admin", "full_name":"Admin User" },
 *        "redirect_url": "view/admin_dashboard.php" }
 *    Role → Redirect:
 *      Doctor       → doctors_view/dashboard.php
 *      Receptionist → reception_view/index.php
 *      Nurse        → nurse_view/dashboard.php
 *      Pharmacist   → pharmacy_view/dashboard.php
 *      Admin        → view/admin_dashboard.php
 *
 * 2. POST /api/auth/logout          [Auth Required]
 *    Body: (empty)
 *    Response: { "success": true, "message": "Logged out successfully" }
 *
 * 3. POST /api/auth/refresh
 *    Body: { "refresh_token": "eyJhbGci..." }
 *    Response: { "success": true, "data": { "access_token": "...", "expires_in": 3600 } }
 *
 * 4. GET /api/auth/me               [Auth Required]
 *    Response: { "user":{...}, "role":"Doctor", "permissions":["view_patients",...] }
 *
 * 5. POST /api/auth/change-password [Auth Required]
 *    Body:
 *      { "current_password": "OldPass@123", "new_password": "NewPass@456" }
 *    Note: new_password must be >= 8 chars
 * ------------------------------------------------------------
 */
namespace GM_HMS\Controllers\api;

use GM_HMS\Controllers\BaseController;
use Exception;

class AuthController extends BaseController {
    
    /**
     * Handle login request
     * POST /api/auth/login
     */
    public function login() {
        error_log("[DEBUG] AuthController::login hit");
        $this->restrictMethod('POST');
        
        // Get and validate JSON input
        $schema = [
            'required' => ['username', 'password'],
            'properties' => [
                'username' => ['type' => 'string', 'minLength' => 1],
                'password' => ['type' => 'string', 'minLength' => 1],
                'role' => ['type' => 'string'] // Optional now
            ],
            'additionalProperties' => true // Allow additional for flexibility
        ];
        
        try {
            $data = $this->getJsonInput($schema);
            
            // Sanitize identifier (can be username or ID)
            $identifier = $this->sanitizer->sanitizeString($data['username']);
            $password = $data['password'];
            
            // Attempt login using unified logic
            $result = $this->auth->login($identifier, $password);
            
            if ($result['success']) {
                $user = $result['user'];
                $detectedRole = $result['role'];
                
                // Create secure session
                $this->auth->createSession($user, $detectedRole);
                
                // Save branch explicitly
                $_SESSION['branch'] = $data['branch'] ?? 'nagarabhavi';
                
                // Determine redirect URL
                $redirectUrl = '';
                // Standardize role for redirection check
                $checkRole = strtolower($detectedRole);
                
                if ($checkRole === 'doctor') {
                    $redirectUrl = 'doctors_view/dashboard.php';
                } elseif ($checkRole === 'receptionist') {
                    $redirectUrl = 'reception_view/index.php';
                } elseif ($checkRole === 'nurse') {
                    $redirectUrl = 'nurse_view/dashboard.php';
                } elseif ($checkRole === 'pharmacist') {
                    $redirectUrl = 'pharmacy_view/dashboard.php';
                } elseif ($checkRole === 'labtechnician' || strpos($checkRole, 'lab') !== false) {
                    $redirectUrl = 'laboratory_view/dashboard.php';
                } elseif ($checkRole === 'admin') {
                    $redirectUrl = 'view/admin_dashboard.php';
                } else {
                    // Default fallback based on common designations
                    if (strpos($checkRole, 'reception') !== false) {
                        $redirectUrl = 'reception_view/index.php';
                    } elseif (strpos($checkRole, 'nurse') !== false) {
                        $redirectUrl = 'nurse_view/dashboard.php';
                    } elseif (strpos($checkRole, 'pharmacy') !== false || strpos($checkRole, 'pharmacist') !== false) {
                        $redirectUrl = 'pharmacy_view/dashboard.php';
                    } elseif (strpos($checkRole, 'lab') !== false || strpos($checkRole, 'technician') !== false) {
                        $redirectUrl = 'laboratory_view/dashboard.php';
                    } else {
                        $redirectUrl = 'view/admin_dashboard.php'; // Default for other staff
                    }
                }
                
                $this->respond([
                    'status' => 'success',
                    'message' => 'Login successful',
                    'role' => $detectedRole,
                    'user' => $result['user'],
                    'redirect_url' => $redirectUrl
                ]);
            } else {
                $this->respond([
                    'status' => 'error',
                    'message' => $result['error']
                ], 401);
            }
            
        } catch (Exception $e) {
            $this->respond([
                'status' => 'error',
                'message' => 'Invalid request format: ' . $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Handle logout request
     * POST /api/auth/logout
     */
    public function logout() {
        $this->restrictMethod('POST');
        $this->requireAuth();
        
        try {
            $token = $this->getBearerToken();
            $this->auth->logout($token);
            
            $this->respondSuccess(null, 'Logged out successfully');
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Handle token refresh request
     * POST /api/auth/refresh
     */
    public function refresh() {
        $this->restrictMethod('POST');
        
        $data = $this->getJsonInput([
            'required' => ['refresh_token'],
            'properties' => [
                'refresh_token' => ['type' => 'string']
            ]
        ]);
        
        try {
            require_once __DIR__ . '/../../security/TokenManager.php';
            $tokenManager = TokenManager::getInstance();
            
            $result = $tokenManager->refreshAccessToken($data['refresh_token']);
            
            if ($result) {
                $this->respondSuccess($result, 'Token refreshed');
            } else {
                $this->respondUnauthorized('Invalid refresh token');
            }
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Get current user info
     * GET /api/auth/me
     */
    public function me() {
        $this->restrictMethod('GET');
        $user = $this->requireAuth();
        
        try {
            // Get user permissions
            $permissions = $this->authz->getUserPermissions($user['id']);
            $role = $this->authz->getUserRole($user['id']);
            
            $this->respondSuccess([
                'user' => $user,
                'role' => $role,
                'permissions' => $permissions
            ]);
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Change password
     * POST /api/auth/change-password
     */
    public function changePassword() {
        $this->restrictMethod('POST');
        
        try {
            $user = $this->requireAuth();
            
            $schema = [
                'required' => ['current_password', 'new_password'],
                'properties' => [
                    'current_password' => ['type' => 'string'],
                    'new_password' => [
                        'type' => 'string',
                        'minLength' => 8
                    ]
                ]
            ];
            
            // $data = $this->getJsonInput($schema); is inside try block in previous edit, need to be careful with overlaps.
            // Actually, I can replace the whole function to be safe.
            $data = $this->getJsonInput($schema);
            
            // Validate password strength
            $policy = $this->config->getPasswordPolicy();
            if (!$this->validator->password($data['new_password'], 'New password', $policy)) {
                $this->respondBadRequest(implode(', ', $this->validator->getErrors()));
            }
            
            $result = $this->auth->changePassword(
                $user['id'],
                $data['current_password'],
                $data['new_password']
            );
            
            if ($result['success']) {
                $this->respondSuccess(null, 'Password changed successfully. Please login again.');
            } else {
                $this->respondBadRequest($result['error']);
            }
            
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Direct Reset password (without old password)
     * POST /api/auth/reset-password
     */
    public function resetPassword() {
        $this->restrictMethod('POST');
        
        try {
            $schema = [
                'required' => ['identifier', 'new_password'],
                'properties' => [
                    'identifier' => ['type' => 'string'],
                    'new_password' => ['type' => 'string', 'minLength' => 6]
                ]
            ];
            
            $data = $this->getJsonInput($schema);
            
            $result = $this->auth->resetPassword(
                $data['identifier'],
                $data['new_password']
            );
            
            if ($result['success']) {
                $this->respondSuccess(null, 'Password has been reset successfully.');
            } else {
                $this->respondBadRequest($result['error']);
            }
            
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Get bearer token helper
     */
    private function getBearerToken() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
}
