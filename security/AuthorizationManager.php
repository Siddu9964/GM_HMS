<?php
namespace GM_HMS\Security;

use Exception;
use GM_HMS\Database\SecureDatabase;
use GM_HMS\Database\AuditLogger;

class AuthorizationManager {
    private static $instance = null;
    private $db;
    private $auditLogger;
    private $permissionCache = [];
    
    /**
     * Private constructor
     */
    private function __construct() {
        $this->db = SecureDatabase::getInstance();
        $this->auditLogger = AuditLogger::getInstance();
    }
    
    /**
     * Get singleton instance
     * 
     * @return AuthorizationManager
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Check if user has permission
     * 
     * @param int $userId User ID
     * @param string $permission Permission name
     * @return bool Has permission
     */
    public function hasPermission($userId, $permission) {
        // Check cache first
        $cacheKey = "$userId:$permission";
        if (isset($this->permissionCache[$cacheKey])) {
            return $this->permissionCache[$cacheKey];
        }
        
        try {
            $query = "
                SELECT COUNT(*) as count
                FROM users u
                INNER JOIN user_roles r ON u.role_id = r.id
                INNER JOIN role_permissions rp ON r.id = rp.role_id
                INNER JOIN permissions p ON rp.permission_id = p.id
                WHERE u.id = ? AND p.name = ? AND u.is_active = 1 AND r.is_active = 1
            ";
            
            $result = $this->db->fetchOne($query, [$userId, $permission]);
            $hasPermission = $result && $result['count'] > 0;
            
            // Cache result
            $this->permissionCache[$cacheKey] = $hasPermission;
            
            return $hasPermission;
            
        } catch (Exception $e) {
            error_log('Permission check error: ' . $e->getMessage());
            return false; // Fail secure
        }
    }
    
    /**
     * Check if user can perform action on resource
     * 
     * @param int $userId User ID
     * @param string $resource Resource name
     * @param string $action Action name
     * @return bool Can perform action
     */
    public function can($userId, $resource, $action) {
        $permission = "$resource.$action";
        $granted = $this->hasPermission($userId, $permission);
        
        // Log authorization check
        $this->auditLogger->logAuthorization($resource, $action, $granted, [
            'user_id' => $userId
        ]);
        
        return $granted;
    }
    
    /**
     * Require permission (throws exception if not authorized)
     * 
     * @param int $userId User ID
     * @param string $permission Permission name
     * @throws Exception if not authorized
     */
    public function requirePermission($userId, $permission) {
        if (!$this->hasPermission($userId, $permission)) {
            $this->auditLogger->logSecurityEvent(
                'authorization_denied',
                AuditLogger::SEVERITY_WARNING,
                "User $userId denied access to $permission",
                ['user_id' => $userId, 'permission' => $permission]
            );
            
            throw new Exception('Access denied');
        }
    }
    
    /**
     * Require action on resource
     * 
     * @param int $userId User ID
     * @param string $resource Resource name
     * @param string $action Action name
     * @throws Exception if not authorized
     */
    public function requireAction($userId, $resource, $action) {
        if (!$this->can($userId, $resource, $action)) {
            throw new Exception('Access denied');
        }
    }
    
    /**
     * Get all permissions for user
     * 
     * @param int $userId User ID
     * @return array Permissions
     */
    public function getUserPermissions($userId) {
        try {
            $query = "
                SELECT p.name, p.resource, p.action, p.description
                FROM users u
                INNER JOIN user_roles r ON u.role_id = r.id
                INNER JOIN role_permissions rp ON r.id = rp.role_id
                INNER JOIN permissions p ON rp.permission_id = p.id
                WHERE u.id = ? AND u.is_active = 1 AND r.is_active = 1
                ORDER BY p.resource, p.action
            ";
            
            return $this->db->fetchAll($query, [$userId]);
            
        } catch (Exception $e) {
            error_log('Get permissions error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user role
     * 
     * @param int $userId User ID
     * @return array|null Role data
     */
    public function getUserRole($userId) {
        try {
            $query = "
                SELECT r.*
                FROM users u
                INNER JOIN user_roles r ON u.role_id = r.id
                WHERE u.id = ?
            ";
            
            return $this->db->fetchOne($query, [$userId]);
            
        } catch (Exception $e) {
            error_log('Get role error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if user has role
     * 
     * @param int $userId User ID
     * @param string $roleName Role name
     * @return bool Has role
     */
    public function hasRole($userId, $roleName) {
        $role = $this->getUserRole($userId);
        return $role && $role['name'] === $roleName;
    }
    
    /**
     * Check if user has any of the roles
     * 
     * @param int $userId User ID
     * @param array $roleNames Role names
     * @return bool Has any role
     */
    public function hasAnyRole($userId, $roleNames) {
        $role = $this->getUserRole($userId);
        return $role && in_array($role['name'], $roleNames);
    }
    
    /**
     * Check if user role level is at least the specified level
     * 
     * @param int $userId User ID
     * @param int $minLevel Minimum level
     * @return bool Has level
     */
    public function hasRoleLevel($userId, $minLevel) {
        $role = $this->getUserRole($userId);
        return $role && $role['level'] >= $minLevel;
    }
    
    /**
     * Assign role to user
     * 
     * @param int $userId User ID
     * @param int $roleId Role ID
     * @return bool Success
     */
    public function assignRole($userId, $roleId) {
        try {
            $this->db->update(
                'users',
                ['role_id' => $roleId],
                'id = ?',
                [$userId]
            );
            
            // Clear permission cache for user
            $this->clearUserCache($userId);
            
            // Log event
            $this->auditLogger->logSecurityEvent(
                'role_assigned',
                AuditLogger::SEVERITY_INFO,
                "Role $roleId assigned to user $userId",
                ['user_id' => $userId, 'role_id' => $roleId]
            );
            
            return true;
            
        } catch (Exception $e) {
            error_log('Assign role error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Grant permission to role
     * 
     * @param int $roleId Role ID
     * @param int $permissionId Permission ID
     * @return bool Success
     */
    public function grantPermission($roleId, $permissionId) {
        try {
            $this->db->insert('role_permissions', [
                'role_id' => $roleId,
                'permission_id' => $permissionId
            ]);
            
            // Clear all permission cache
            $this->permissionCache = [];
            
            // Log event
            $this->auditLogger->logSecurityEvent(
                'permission_granted',
                AuditLogger::SEVERITY_INFO,
                "Permission $permissionId granted to role $roleId",
                ['role_id' => $roleId, 'permission_id' => $permissionId]
            );
            
            return true;
            
        } catch (Exception $e) {
            error_log('Grant permission error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Revoke permission from role
     * 
     * @param int $roleId Role ID
     * @param int $permissionId Permission ID
     * @return bool Success
     */
    public function revokePermission($roleId, $permissionId) {
        try {
            $this->db->delete(
                'role_permissions',
                'role_id = ? AND permission_id = ?',
                [$roleId, $permissionId]
            );
            
            // Clear all permission cache
            $this->permissionCache = [];
            
            // Log event
            $this->auditLogger->logSecurityEvent(
                'permission_revoked',
                AuditLogger::SEVERITY_INFO,
                "Permission $permissionId revoked from role $roleId",
                ['role_id' => $roleId, 'permission_id' => $permissionId]
            );
            
            return true;
            
        } catch (Exception $e) {
            error_log('Revoke permission error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clear permission cache for user
     * 
     * @param int $userId User ID
     */
    private function clearUserCache($userId) {
        foreach (array_keys($this->permissionCache) as $key) {
            if (strpos($key, "$userId:") === 0) {
                unset($this->permissionCache[$key]);
            }
        }
    }
    
    /**
     * Clear all permission cache
     */
    public function clearCache() {
        $this->permissionCache = [];
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception('Cannot unserialize singleton');
    }
}
