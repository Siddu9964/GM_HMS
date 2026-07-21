<?php
namespace GM_HMS\Security;

use Exception;
use GM_HMS\Config\SecurityConfig;
use GM_HMS\Database\SecureDatabase;
use GM_HMS\Database\AuditLogger;

/**
 * Authentication Manager
 */
class AuthenticationManager {
    private static $instance = null;
    private $config;
    private $db;
    private $encryption;
    private $tokenManager;
    private $auditLogger;
    
    private function __construct() {
        $this->config = SecurityConfig::getInstance();
        $this->db = SecureDatabase::getInstance();
        $this->encryption = EncryptionManager::getInstance();
        $this->tokenManager = TokenManager::getInstance();
        $this->auditLogger = AuditLogger::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Authenticate user by credentials (ID or Username)
     * 
     * @param string $identifier User ID or Username
     * @param string $password
     * @param string|null $role Not used anymore as we detect it from the user table
     * @return array Result with success, user data, and role
     */
    public function login($identifier, $password, $role = null) {
        try {
            // 1. Search in unified user table by ID or Username
            $userAuth = $this->db->fetchOne(
                "SELECT sl_no, id, username, password, role FROM user WHERE id = ? OR username = ?",
                [$identifier, $identifier]
            );

            if ($userAuth && $this->encryption->verifyPassword($password, $userAuth['password'])) {
                $detectedRole = $userAuth['role'];
                $mapId = $userAuth['id'];
                
                $userProfile = null;
                
                // 2. Fetch full profile based on detected role
                if ($detectedRole === 'Doctor') {
                    $userProfile = $this->db->fetchOne(
                        "SELECT doctor_id as sl_no, username, 'Doctor' as designation, full_name, email, mobile_number, status FROM doctors WHERE doctor_id = ?",
                        [$mapId]
                    );
                } else {
                    // Admin or Receptionist
                    $userProfile = $this->db->fetchOne(
                        "SELECT sl_no, username, designation, full_name, email, mobile_number, status FROM staff WHERE sl_no = ?",
                        [$mapId]
                    );
                    if ($userProfile) {
                        $detectedRole = $userProfile['designation'];
                    }
                }

                if ($userProfile) {
                    // Log successful login
                    $this->auditLogger->logSecurityEvent(
                        'login_success', 
                        AuditLogger::SEVERITY_INFO, 
                        "User {$identifier} logged in as {$detectedRole}"
                    );
                    
                    return [
                        'success' => true, 
                        'user' => $userProfile, 
                        'role' => $detectedRole
                    ];
                }
            }

            // Log failed attempt
            $this->auditLogger->logSecurityEvent(
                'login_failed', 
                AuditLogger::SEVERITY_WARNING, 
                "Failed login attempt for {$identifier}"
            );

            if (!$userAuth) {
                // Check patient table
                $patientAuth = $this->db->fetchOne(
                    "SELECT sl_no, patient_id as id, first_name as username, CONCAT(first_name, ' ', last_name) as full_name, email, phone as mobile_number, password, age, sex, 'Active' as status FROM patient 
                     WHERE first_name = ? OR email = ? OR phone = ? OR patient_id = ?",
                    [$identifier, $identifier, $identifier, $identifier]
                );

                if ($patientAuth && password_verify($password, $patientAuth['password'])) {
                    $detectedRole = 'Patient';
                    
                    // Remove password from returned array
                    unset($patientAuth['password']);
                    $patientAuth['designation'] = 'Patient';
                    
                    $this->auditLogger->logSecurityEvent(
                        'login_success', 
                        AuditLogger::SEVERITY_INFO, 
                        "Patient {$identifier} logged in"
                    );
                    
                    return [
                        'success' => true, 
                        'user' => $patientAuth, 
                        'role' => 'Patient'
                    ];
                }

                if ($patientAuth) {
                    return ['success' => false, 'error' => "Invalid password."];
                }

                return ['success' => false, 'error' => "User account not found."];
            }
            return ['success' => false, 'error' => "Invalid password for user '{$identifier}'."];

        } catch (Exception $e) {
            $this->auditLogger->logSecurityEvent('login_error', AuditLogger::SEVERITY_ERROR, $e->getMessage());
            return ['success' => false, 'error' => 'An internal error occurred during login: ' . $e->getMessage()];
        }
    }

    /**
     * Create a secure session for the user
     * 
     * @param array $user User data
     * @param string $role User role
     */
    public function createSession($user, $role) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['sl_no'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['mobile_number'] = $user['mobile_number'];
        $_SESSION['status'] = $user['status'];
        $_SESSION['designation'] = $user['designation'];
        $_SESSION['role'] = $role;
        $_SESSION['admin_user_text'] = 'Admin User'; // For navbar as requested
        
        if (isset($_SERVER['HTTP_X_HOSPITAL_BRANCH'])) {
            $_SESSION['hospital_branch'] = $_SERVER['HTTP_X_HOSPITAL_BRANCH'];
        }
        
        return true;
    }
    
    public function verifyToken($token) {
        $payload = $this->tokenManager->validateToken($token);
        if (!$payload) return null;
        // Simplified for refactor
        return ['id' => $payload['sub'], 'emp_id' => 'system', 'name' => 'Authenticated User'];
    }

    /**
     * Change user password
     * 
     * @param int|string $userId User ID (sl_no or doctor_id)
     * @param string $currentPassword
     * @param string $newPassword
     * @return array Result
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        error_log("Attempting password change for UserID: $userId");
        
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $role = $_SESSION['role'] ?? null;
            error_log("Password change role: " . ($role ?? 'NULL'));

            // --- Step 1: Verify current user in central `user` table ---
            // Use sl_no if numeric, otherwise use id string to avoid MySQL 
            // "Truncated incorrect DECIMAL value" when comparing int vs varchar
            $isNumeric = is_numeric($userId);
            $query = $isNumeric ? 
                "SELECT sl_no, id, password, role FROM user WHERE sl_no = ?" : 
                "SELECT sl_no, id, password, role FROM user WHERE id = ?";
            
            $userRow = $this->db->fetchOne($query, [$userId]);

            if (!$userRow) {
                error_log("User not found in user table with ID $userId (Search Type: " . ($isNumeric ? 'sl_no' : 'id') . ")");
                return ['success' => false, 'error' => 'User not found'];
            }
            
            // Use the actual internal IDs for subsequent updates to be safe
            $internalSlNo = $userRow['sl_no'];
            $internalStringId = $userRow['id'];
            $detectedRole = $userRow['role'];

            if (!$this->encryption->verifyPassword($currentPassword, $userRow['password'])) {
                error_log("Password verification failed for user $userId");
                return ['success' => false, 'error' => 'Current password is incorrect'];
            }

            // --- Step 2: Hash new password ---
            $newHash = $this->encryption->hashPassword($newPassword);

            // --- Step 3: Update central `user` table ---
            $userUpdated = $this->db->execute(
                "UPDATE user SET password = ? WHERE sl_no = ?",
                [$newHash, $internalSlNo]
            );
            if (is_array($userUpdated)) {
                $userUpdated = ($userUpdated['affected_rows'] >= 0);
            }
            if (!$userUpdated) {
                error_log("Failed to update user table for ID $userId");
                return ['success' => false, 'error' => 'Failed to update password in database'];
            }

            // --- Step 4: Update role-specific table ---
            if ($role === 'Doctor' || $detectedRole === 'Doctor') {
                $this->db->execute(
                    "UPDATE doctors SET password = ? WHERE doctor_id = ?",
                    [$newHash, $internalStringId]
                );
            } elseif (in_array($role, ['Receptionist', 'admin', 'Admin', 'Nurse']) || in_array($detectedRole, ['Receptionist', 'Admin', 'Nurse'])) {
                // Determine which ID to use for staff. Most HMS use sl_no or role_id
                $this->db->execute(
                    "UPDATE staff SET password = ? WHERE sl_no = ?",
                    [$newHash, $internalSlNo]
                );
            }

            $this->auditLogger->logSecurityEvent(
                'password_change_success',
                AuditLogger::SEVERITY_INFO,
                "Password changed for user ID $userId ($role)"
            );
            return ['success' => true];

        } catch (\Throwable $e) {
            error_log("Exception in changePassword: " . $e->getMessage());
            error_log($e->getTraceAsString());
            try {
                $this->auditLogger->logSecurityEvent('password_change_error', AuditLogger::SEVERITY_ERROR, $e->getMessage());
            } catch (\Throwable $logError) {
                error_log("Failed to log security event: " . $logError->getMessage());
            }
            return ['success' => false, 'error' => 'Internal error: ' . $e->getMessage()];
        }
    }
    public function resetPassword($identifier, $newPassword) {
        try {
            // Check patient table first
            $patientAuth = $this->db->fetchOne(
                "SELECT sl_no, patient_id FROM patient 
                 WHERE first_name = ? OR email = ? OR phone = ? OR patient_id = ?",
                [$identifier, $identifier, $identifier, $identifier]
            );

            if ($patientAuth) {
                // Update patient password using password_hash
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $this->db->execute(
                    "UPDATE patient SET password = ? WHERE sl_no = ?",
                    [$newHash, $patientAuth['sl_no']]
                );
                $this->auditLogger->logSecurityEvent('password_reset', AuditLogger::SEVERITY_INFO, "Password reset for patient {$identifier}");
                return ['success' => true];
            }

            // Check generic user table (for staff)
            $userAuth = $this->db->fetchOne(
                "SELECT sl_no, id, role FROM user WHERE id = ? OR username = ?",
                [$identifier, $identifier]
            );

            if ($userAuth) {
                $newHash = $this->encryption->hashPassword($newPassword);
                $internalSlNo = $userAuth['sl_no'];
                $internalStringId = $userAuth['id'];
                $detectedRole = $userAuth['role'];

                $this->db->execute("UPDATE user SET password = ? WHERE sl_no = ?", [$newHash, $internalSlNo]);

                if ($detectedRole === 'Doctor') {
                    $this->db->execute("UPDATE doctors SET password = ? WHERE doctor_id = ?", [$newHash, $internalStringId]);
                } elseif (in_array($detectedRole, ['Receptionist', 'Admin', 'Nurse'])) {
                    $this->db->execute("UPDATE staff SET password = ? WHERE sl_no = ?", [$newHash, $internalSlNo]);
                }
                
                $this->auditLogger->logSecurityEvent('password_reset', AuditLogger::SEVERITY_INFO, "Password reset for staff {$identifier}");
                return ['success' => true];
            }

            return ['success' => false, 'error' => 'User not found.'];

        } catch (\Throwable $e) {
            error_log("Exception in resetPassword: " . $e->getMessage());
            return ['success' => false, 'error' => 'Internal error: ' . $e->getMessage()];
        }
    }
}
