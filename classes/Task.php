<?php
/**
 * Task Class
 * Handles task CRUD operations and task-related queries
 */

require_once __DIR__ . '/Database.php';

class Task {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all tasks with filters
     */
    public function getAll($filters = []) {
        $sql = "SELECT t.*, 
                p.project_name, p.project_code,
                u1.full_name as assigned_to_name,
                u2.full_name as created_by_name
                FROM tasks t
                LEFT JOIN projects p ON t.project_id = p.project_id
                LEFT JOIN users u1 ON t.assigned_to = u1.user_id
                LEFT JOIN users u2 ON t.created_by = u2.user_id
                WHERE 1=1";
        
        // Apply filters
        if (!empty($filters['project_id'])) {
            $sql .= " AND t.project_id = :project_id";
        }
        if (!empty($filters['assigned_to'])) {
            $sql .= " AND t.assigned_to = :assigned_to";
        }
        if (!empty($filters['status'])) {
            $sql .= " AND t.status = :status";
        }
        if (!empty($filters['priority'])) {
            $sql .= " AND t.priority = :priority";
        }
        if (!empty($filters['search'])) {
            $sql .= " AND t.task_name LIKE :search";
        }
        
        $sql .= " ORDER BY t.created_at DESC";
        
        $this->db->query($sql);
        
        if (!empty($filters['project_id'])) {
            $this->db->bind(':project_id', $filters['project_id']);
        }
        if (!empty($filters['assigned_to'])) {
            $this->db->bind(':assigned_to', $filters['assigned_to']);
        }
        if (!empty($filters['status'])) {
            $this->db->bind(':status', $filters['status']);
        }
        if (!empty($filters['priority'])) {
            $this->db->bind(':priority', $filters['priority']);
        }
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $this->db->bind(':search', $searchTerm);
        }
        
        return $this->db->resultSet();
    }
    
    /**
     * Get task by ID
     */
    public function getById($taskId) {
        $this->db->query("
            SELECT t.*, 
                   p.project_name, p.project_code,
                   u1.full_name as assigned_to_name, u1.email as assigned_to_email,
                   u2.full_name as created_by_name
            FROM tasks t
            LEFT JOIN projects p ON t.project_id = p.project_id
            LEFT JOIN users u1 ON t.assigned_to = u1.user_id
            LEFT JOIN users u2 ON t.created_by = u2.user_id
            WHERE t.task_id = :task_id
        ");
        $this->db->bind(':task_id', $taskId);
        return $this->db->single();
    }
    
    /**
     * Create new task
     */
    public function create($data) {
        $this->db->query("
            INSERT INTO tasks (
                project_id, task_name, description, assigned_to, created_by,
                status, priority, start_date, due_date, estimated_hours, parent_task_id
            ) VALUES (
                :project_id, :task_name, :description, :assigned_to, :created_by,
                :status, :priority, :start_date, :due_date, :estimated_hours, :parent_task_id
            )
        ");
        
        $this->db->bind(':project_id', $data['project_id']);
        $this->db->bind(':task_name', $data['task_name']);
        $this->db->bind(':description', $data['description'] ?? null);
        $this->db->bind(':assigned_to', $data['assigned_to'] ?? null);
        $this->db->bind(':created_by', $data['created_by']);
        $this->db->bind(':status', $data['status'] ?? 'pending');
        $this->db->bind(':priority', $data['priority'] ?? 'medium');
        $this->db->bind(':start_date', $data['start_date'] ?? null);
        $this->db->bind(':due_date', $data['due_date'] ?? null);
        $this->db->bind(':estimated_hours', $data['estimated_hours'] ?? null);
        $this->db->bind(':parent_task_id', $data['parent_task_id'] ?? null);
        
        if ($this->db->execute()) {
            return [
                'success' => true,
                'message' => 'Task created successfully',
                'task_id' => $this->db->lastInsertId()
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to create task'];
    }
    
    /**
     * Update task
     */
    public function update($taskId, $data) {
        $sql = "UPDATE tasks SET 
                task_name = :task_name,
                description = :description,
                assigned_to = :assigned_to,
                status = :status,
                priority = :priority,
                start_date = :start_date,
                due_date = :due_date,
                estimated_hours = :estimated_hours,
                progress = :progress";
        
        // Set completed_at if status is completed
        if ($data['status'] === 'completed') {
            $sql .= ", completed_at = NOW()";
        }
        
        $sql .= " WHERE task_id = :task_id";
        
        $this->db->query($sql);
        
        $this->db->bind(':task_id', $taskId);
        $this->db->bind(':task_name', $data['task_name']);
        $this->db->bind(':description', $data['description'] ?? null);
        $this->db->bind(':assigned_to', $data['assigned_to'] ?? null);
        $this->db->bind(':status', $data['status']);
        $this->db->bind(':priority', $data['priority']);
        $this->db->bind(':start_date', $data['start_date'] ?? null);
        $this->db->bind(':due_date', $data['due_date'] ?? null);
        $this->db->bind(':estimated_hours', $data['estimated_hours'] ?? null);
        $this->db->bind(':progress', $data['progress'] ?? 0);
        
        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Task updated successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to update task'];
    }
    
    /**
     * Delete task
     */
    public function delete($taskId) {
        $this->db->query("DELETE FROM tasks WHERE task_id = :task_id");
        $this->db->bind(':task_id', $taskId);
        
        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Task deleted successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to delete task'];
    }
    
    /**
     * Get tasks by project
     */
    public function getByProject($projectId) {
        return $this->getAll(['project_id' => $projectId]);
    }
    
    /**
     * Get tasks assigned to user
     */
    public function getByAssignedUser($userId) {
        return $this->getAll(['assigned_to' => $userId]);
    }
    
    /**
     * Get task statistics
     */
    public function getStatistics($filters = []) {
        $sql = "SELECT 
                COUNT(*) as total_tasks,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                SUM(CASE WHEN status = 'review' THEN 1 ELSE 0 END) as review_tasks,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                SUM(CASE WHEN priority = 'critical' THEN 1 ELSE 0 END) as critical_tasks,
                SUM(CASE WHEN due_date < CURDATE() AND status != 'completed' THEN 1 ELSE 0 END) as overdue_tasks,
                SUM(estimated_hours) as total_estimated_hours,
                SUM(actual_hours) as total_actual_hours
                FROM tasks WHERE 1=1";
        
        if (!empty($filters['project_id'])) {
            $sql .= " AND project_id = :project_id";
        }
        if (!empty($filters['assigned_to'])) {
            $sql .= " AND assigned_to = :assigned_to";
        }
        
        $this->db->query($sql);
        
        if (!empty($filters['project_id'])) {
            $this->db->bind(':project_id', $filters['project_id']);
        }
        if (!empty($filters['assigned_to'])) {
            $this->db->bind(':assigned_to', $filters['assigned_to']);
        }
        
        return $this->db->single();
    }
    
    /**
     * Get overdue tasks
     */
    public function getOverdue($userId = null) {
        $sql = "SELECT t.*, p.project_name, u.full_name as assigned_to_name
                FROM tasks t
                LEFT JOIN projects p ON t.project_id = p.project_id
                LEFT JOIN users u ON t.assigned_to = u.user_id
                WHERE t.due_date < CURDATE() 
                AND t.status NOT IN ('completed', 'cancelled')";
        
        if ($userId) {
            $sql .= " AND t.assigned_to = :user_id";
        }
        
        $sql .= " ORDER BY t.due_date ASC";
        
        $this->db->query($sql);
        
        if ($userId) {
            $this->db->bind(':user_id', $userId);
        }
        
        return $this->db->resultSet();
    }
    
    /**
     * Log time for task
     */
    public function logTime($taskId, $userId, $hoursSpent, $date, $description = null) {
        $this->db->query("
            INSERT INTO time_logs (task_id, user_id, hours_spent, log_date, description) 
            VALUES (:task_id, :user_id, :hours_spent, :log_date, :description)
        ");
        
        $this->db->bind(':task_id', $taskId);
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':hours_spent', $hoursSpent);
        $this->db->bind(':log_date', $date);
        $this->db->bind(':description', $description);
        
        if ($this->db->execute()) {
            // Update actual hours in task
            $this->updateActualHours($taskId);
            return ['success' => true, 'message' => 'Time logged successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to log time'];
    }
    
    /**
     * Update actual hours for task
     */
    private function updateActualHours($taskId) {
        $this->db->query("
            UPDATE tasks 
            SET actual_hours = (
                SELECT COALESCE(SUM(hours_spent), 0) 
                FROM time_logs 
                WHERE task_id = :task_id
            )
            WHERE task_id = :task_id
        ");
        $this->db->bind(':task_id', $taskId);
        return $this->db->execute();
    }
    
    /**
     * Get time logs for task
     */
    public function getTimeLogs($taskId) {
        $this->db->query("
            SELECT tl.*, u.full_name as user_name
            FROM time_logs tl
            LEFT JOIN users u ON tl.user_id = u.user_id
            WHERE tl.task_id = :task_id
            ORDER BY tl.log_date DESC
        ");
        $this->db->bind(':task_id', $taskId);
        return $this->db->resultSet();
    }
}
