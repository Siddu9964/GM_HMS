<?php
/**
 * Nurse Task Model
 * Handles task assignment and management
 * 
 * @package GM_HMS\Models
 * @version 1.0.0
 */

namespace GM_HMS\Models;

use GM_HMS\Database\SecureDatabase;
use Exception;

class NurseTaskModel {
    private $db;
    
    public function __construct() {
        $this->db = SecureDatabase::getInstance();
    }
    
    /**
     * Get tasks assigned to a nurse
     * 
     * @param int $nurseId Nurse staff serial number
     * @param string $status Task status filter
     * @return array List of tasks
     */
    public function getTasksByNurse($nurseId, $status = null) {
        $sql = "SELECT nt.*, 
                       CONCAT(p.first_name, ' ', p.last_name) as patient_name
                FROM nurse_tasks nt
                LEFT JOIN patient p ON nt.patient_id = p.patient_id
                WHERE nt.assigned_to = ?";
        
        $params = [$nurseId];
        
        if ($status) {
            $sql .= " AND nt.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY 
                    CASE nt.priority 
                        WHEN 'Urgent' THEN 1 
                        WHEN 'High' THEN 2 
                        WHEN 'Normal' THEN 3 
                        WHEN 'Low' THEN 4 
                    END,
                    nt.due_at ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Create new task
     * 
     * @param array $data Task data
     * @return int New task ID
     */
    public function createTask($data) {
        $sql = "INSERT INTO nurse_tasks (
                    patient_id, assigned_to, task_title, task_details,
                    priority, due_at, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->execute($sql, [
            $data['patient_id'] ?? null,
            $data['assigned_to'],
            $data['task_title'],
            $data['task_details'] ?? null,
            $data['priority'] ?? 'Normal',
            $data['due_at'] ?? null,
            'Pending'
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update task status
     * 
     * @param int $taskId Task ID
     * @param string $status New status
     * @param string $remarks Completion remarks
     * @return bool Success status
     */
    public function updateTaskStatus($taskId, $status, $remarks = null) {
        $sql = "UPDATE nurse_tasks SET 
                    status = ?,
                    remarks = ?,
                    completed_at = ?
                WHERE task_id = ?";
        
        $completedAt = ($status === 'Completed') ? date('Y-m-d H:i:s') : null;
        
        return $this->db->execute($sql, [$status, $remarks, $completedAt, $taskId]);
    }
    
    /**
     * Get overdue tasks
     * 
     * @param int $nurseId Nurse staff serial number
     * @return array Overdue tasks
     */
    public function getOverdueTasks($nurseId) {
        $sql = "SELECT nt.*, 
                       CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                       TIMESTAMPDIFF(HOUR, nt.due_at, NOW()) as hours_overdue
                FROM nurse_tasks nt
                LEFT JOIN patient p ON nt.patient_id = p.patient_id
                WHERE nt.assigned_to = ?
                  AND nt.due_at < NOW()
                  AND nt.status IN ('Pending', 'In Progress')
                ORDER BY nt.due_at ASC";
        
        return $this->db->fetchAll($sql, [$nurseId]);
    }
    
    /**
     * Get task statistics
     * 
     * @param int $nurseId Nurse staff serial number
     * @return array Statistics
     */
    public function getTaskStatistics($nurseId) {
        $stats = [];
        
        // Total pending
        $sql = "SELECT COUNT(*) as count FROM nurse_tasks 
                WHERE assigned_to = ? AND status = 'Pending'";
        $result = $this->db->fetchOne($sql, [$nurseId]);
        $stats['pending'] = (int)($result['count'] ?? 0);
        
        // In progress
        $sql = "SELECT COUNT(*) as count FROM nurse_tasks 
                WHERE assigned_to = ? AND status = 'In Progress'";
        $result = $this->db->fetchOne($sql, [$nurseId]);
        $stats['in_progress'] = (int)($result['count'] ?? 0);
        
        // Completed today
        $sql = "SELECT COUNT(*) as count FROM nurse_tasks 
                WHERE assigned_to = ? 
                  AND status = 'Completed'
                  AND DATE(completed_at) = CURDATE()";
        $result = $this->db->fetchOne($sql, [$nurseId]);
        $stats['completed_today'] = (int)($result['count'] ?? 0);
        
        // Overdue
        $sql = "SELECT COUNT(*) as count FROM nurse_tasks 
                WHERE assigned_to = ? 
                  AND due_at < NOW()
                  AND status IN ('Pending', 'In Progress')";
        $result = $this->db->fetchOne($sql, [$nurseId]);
        $stats['overdue'] = (int)($result['count'] ?? 0);
        
        // Urgent
        $sql = "SELECT COUNT(*) as count FROM nurse_tasks 
                WHERE assigned_to = ? 
                  AND priority = 'Urgent'
                  AND status IN ('Pending', 'In Progress')";
        $result = $this->db->fetchOne($sql, [$nurseId]);
        $stats['urgent'] = (int)($result['count'] ?? 0);
        
        return $stats;
    }
    
    /**
     * Get today's tasks
     * 
     * @param int $nurseId Nurse staff serial number
     * @return array Today's tasks
     */
    public function getTodayTasks($nurseId) {
        $sql = "SELECT nt.*, 
                       CONCAT(p.first_name, ' ', p.last_name) as patient_name
                FROM nurse_tasks nt
                LEFT JOIN patient p ON nt.patient_id = p.patient_id
                WHERE nt.assigned_to = ?
                  AND DATE(nt.due_at) = CURDATE()
                  AND nt.status IN ('Pending', 'In Progress')
                ORDER BY 
                    CASE nt.priority 
                        WHEN 'Urgent' THEN 1 
                        WHEN 'High' THEN 2 
                        WHEN 'Normal' THEN 3 
                        WHEN 'Low' THEN 4 
                    END,
                    nt.due_at ASC";
        
        return $this->db->fetchAll($sql, [$nurseId]);
    }
}
