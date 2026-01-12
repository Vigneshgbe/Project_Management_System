<?php
/**
 * Project Class
 * Handles project CRUD operations and project-related queries
 */

require_once __DIR__ . '/Database.php';

class Project {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all projects with pagination and filters
     */
    public function getAll($page = 1, $perPage = RECORDS_PER_PAGE, $filters = []) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT p.*, 
                u1.full_name as manager_name,
                u2.full_name as client_name,
                COUNT(DISTINCT pm.user_id) as team_size,
                COUNT(DISTINCT t.task_id) as total_tasks,
                SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
                FROM projects p
                LEFT JOIN users u1 ON p.manager_id = u1.user_id
                LEFT JOIN users u2 ON p.client_id = u2.user_id
                LEFT JOIN project_members pm ON p.project_id = pm.project_id
                LEFT JOIN tasks t ON p.project_id = t.project_id
                WHERE 1=1";
        
        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = :status";
        }
        if (!empty($filters['priority'])) {
            $sql .= " AND p.priority = :priority";
        }
        if (!empty($filters['manager_id'])) {
            $sql .= " AND p.manager_id = :manager_id";
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (p.project_name LIKE :search OR p.project_code LIKE :search)";
        }
        
        $sql .= " GROUP BY p.project_id ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        
        $this->db->query($sql);
        
        if (!empty($filters['status'])) {
            $this->db->bind(':status', $filters['status']);
        }
        if (!empty($filters['priority'])) {
            $this->db->bind(':priority', $filters['priority']);
        }
        if (!empty($filters['manager_id'])) {
            $this->db->bind(':manager_id', $filters['manager_id']);
        }
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $this->db->bind(':search', $searchTerm);
        }
        
        $this->db->bind(':limit', $perPage, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }
    
    /**
     * Get project by ID with details
     */
    public function getById($projectId) {
        $this->db->query("
            SELECT p.*, 
                   u1.full_name as manager_name, u1.email as manager_email,
                   u2.full_name as client_name, u2.email as client_email
            FROM projects p
            LEFT JOIN users u1 ON p.manager_id = u1.user_id
            LEFT JOIN users u2 ON p.client_id = u2.user_id
            WHERE p.project_id = :project_id
        ");
        $this->db->bind(':project_id', $projectId);
        return $this->db->single();
    }
    
    /**
     * Create new project
     */
    public function create($data) {
        $this->db->query("
            INSERT INTO projects (
                project_name, project_code, description, client_id, manager_id,
                start_date, end_date, estimated_hours, budget, status, priority
            ) VALUES (
                :project_name, :project_code, :description, :client_id, :manager_id,
                :start_date, :end_date, :estimated_hours, :budget, :status, :priority
            )
        ");
        
        $this->db->bind(':project_name', $data['project_name']);
        $this->db->bind(':project_code', $data['project_code']);
        $this->db->bind(':description', $data['description'] ?? null);
        $this->db->bind(':client_id', $data['client_id'] ?? null);
        $this->db->bind(':manager_id', $data['manager_id']);
        $this->db->bind(':start_date', $data['start_date'] ?? null);
        $this->db->bind(':end_date', $data['end_date'] ?? null);
        $this->db->bind(':estimated_hours', $data['estimated_hours'] ?? null);
        $this->db->bind(':budget', $data['budget'] ?? null);
        $this->db->bind(':status', $data['status'] ?? 'planning');
        $this->db->bind(':priority', $data['priority'] ?? 'medium');
        
        if ($this->db->execute()) {
            return [
                'success' => true,
                'message' => 'Project created successfully',
                'project_id' => $this->db->lastInsertId()
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to create project'];
    }
    
    /**
     * Update project
     */
    public function update($projectId, $data) {
        $this->db->query("
            UPDATE projects SET 
                project_name = :project_name,
                description = :description,
                client_id = :client_id,
                manager_id = :manager_id,
                start_date = :start_date,
                end_date = :end_date,
                estimated_hours = :estimated_hours,
                budget = :budget,
                status = :status,
                priority = :priority,
                progress = :progress
            WHERE project_id = :project_id
        ");
        
        $this->db->bind(':project_id', $projectId);
        $this->db->bind(':project_name', $data['project_name']);
        $this->db->bind(':description', $data['description'] ?? null);
        $this->db->bind(':client_id', $data['client_id'] ?? null);
        $this->db->bind(':manager_id', $data['manager_id']);
        $this->db->bind(':start_date', $data['start_date'] ?? null);
        $this->db->bind(':end_date', $data['end_date'] ?? null);
        $this->db->bind(':estimated_hours', $data['estimated_hours'] ?? null);
        $this->db->bind(':budget', $data['budget'] ?? null);
        $this->db->bind(':status', $data['status']);
        $this->db->bind(':priority', $data['priority']);
        $this->db->bind(':progress', $data['progress'] ?? 0);
        
        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Project updated successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to update project'];
    }
    
    /**
     * Delete project
     */
    public function delete($projectId) {
        $this->db->query("DELETE FROM projects WHERE project_id = :project_id");
        $this->db->bind(':project_id', $projectId);
        
        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Project deleted successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to delete project'];
    }
    
    /**
     * Add member to project
     */
    public function addMember($projectId, $userId, $role = 'member') {
        $this->db->query("
            INSERT INTO project_members (project_id, user_id, role) 
            VALUES (:project_id, :user_id, :role)
        ");
        
        $this->db->bind(':project_id', $projectId);
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':role', $role);
        
        return $this->db->execute();
    }
    
    /**
     * Remove member from project
     */
    public function removeMember($projectId, $userId) {
        $this->db->query("DELETE FROM project_members WHERE project_id = :project_id AND user_id = :user_id");
        $this->db->bind(':project_id', $projectId);
        $this->db->bind(':user_id', $userId);
        
        return $this->db->execute();
    }
    
    /**
     * Get project members
     */
    public function getMembers($projectId) {
        $this->db->query("
            SELECT pm.*, u.full_name, u.email, u.role as user_role
            FROM project_members pm
            JOIN users u ON pm.user_id = u.user_id
            WHERE pm.project_id = :project_id
            ORDER BY pm.joined_at DESC
        ");
        $this->db->bind(':project_id', $projectId);
        return $this->db->resultSet();
    }
    
    /**
     * Get project statistics
     */
    public function getStatistics($projectId = null) {
        if ($projectId) {
            // Statistics for specific project
            $this->db->query("
                SELECT 
                    p.project_id,
                    p.project_name,
                    p.budget,
                    p.actual_cost,
                    p.progress,
                    COUNT(DISTINCT t.task_id) as total_tasks,
                    SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                    SUM(t.actual_hours) as total_hours_spent,
                    COUNT(DISTINCT pm.user_id) as team_size
                FROM projects p
                LEFT JOIN tasks t ON p.project_id = t.project_id
                LEFT JOIN project_members pm ON p.project_id = pm.project_id
                WHERE p.project_id = :project_id
                GROUP BY p.project_id
            ");
            $this->db->bind(':project_id', $projectId);
            return $this->db->single();
        } else {
            // Overall statistics
            $this->db->query("
                SELECT 
                    COUNT(*) as total_projects,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_projects,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_projects,
                    SUM(CASE WHEN status = 'on_hold' THEN 1 ELSE 0 END) as on_hold_projects,
                    SUM(budget) as total_budget,
                    SUM(actual_cost) as total_spent
                FROM projects
            ");
            return $this->db->single();
        }
    }
    
    /**
     * Get user's projects
     */
    public function getUserProjects($userId) {
        $this->db->query("
            SELECT DISTINCT p.*, u.full_name as manager_name
            FROM projects p
            LEFT JOIN users u ON p.manager_id = u.user_id
            LEFT JOIN project_members pm ON p.project_id = pm.project_id
            WHERE p.manager_id = :user_id OR pm.user_id = :user_id
            ORDER BY p.created_at DESC
        ");
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }
    
    /**
     * Update project progress
     */
    public function updateProgress($projectId) {
        // Calculate progress based on completed tasks
        $this->db->query("
            UPDATE projects p
            SET p.progress = (
                SELECT ROUND(
                    (COUNT(CASE WHEN t.status = 'completed' THEN 1 END) * 100.0) / 
                    NULLIF(COUNT(*), 0)
                )
                FROM tasks t
                WHERE t.project_id = p.project_id
            )
            WHERE p.project_id = :project_id
        ");
        $this->db->bind(':project_id', $projectId);
        return $this->db->execute();
    }
}
