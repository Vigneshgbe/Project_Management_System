<?php
require_once __DIR__ . '/../config.php';

class Task {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO tasks (project_id, phase_id, task_name, description, assigned_to, status, priority, due_date, estimated_hours, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // CRITICAL FIX: Correct type string - assigned_to is INT ('i'), status is ENUM/string ('s')
        // Type string: i=project_id, i=phase_id, s=task_name, s=description, i=assigned_to, s=status, s=priority, s=due_date, d=estimated_hours, i=created_by
        $stmt->bind_param("iissiissdi", 
            $data['project_id'], 
            $data['phase_id'], 
            $data['task_name'], 
            $data['description'], 
            $data['assigned_to'], 
            $data['status'], 
            $data['priority'], 
            $data['due_date'], 
            $data['estimated_hours'], 
            $data['created_by']
        );
        
        try {
            return $stmt->execute() ? $this->db->insert_id : false;
        } catch (mysqli_sql_exception $e) {
            error_log("Task creation failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE tasks SET task_name = ?, description = ?, assigned_to = ?, status = ?, priority = ?, due_date = ?, estimated_hours = ?, actual_hours = ? WHERE id = ?");
        $stmt->bind_param("ssisssddi", 
            $data['task_name'], 
            $data['description'], 
            $data['assigned_to'], 
            $data['status'], 
            $data['priority'], 
            $data['due_date'], 
            $data['estimated_hours'], 
            $data['actual_hours'], 
            $id
        );
        return $stmt->execute();
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT t.*, u1.full_name as assigned_name, u2.full_name as creator_name FROM tasks t LEFT JOIN users u1 ON t.assigned_to = u1.id LEFT JOIN users u2 ON t.created_by = u2.id WHERE t.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function getByProject($project_id) {
        $sql = "SELECT t.*, u1.full_name as assigned_name, u2.full_name as creator_name, pp.phase_name 
                FROM tasks t 
                LEFT JOIN users u1 ON t.assigned_to = u1.id 
                LEFT JOIN users u2 ON t.created_by = u2.id
                LEFT JOIN project_phases pp ON t.phase_id = pp.id
                WHERE t.project_id = ? 
                ORDER BY t.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getByUser($user_id) {
        $sql = "SELECT t.*, p.project_name, p.project_code, u.full_name as creator_name 
                FROM tasks t 
                JOIN projects p ON t.project_id = p.id 
                LEFT JOIN users u ON t.created_by = u.id
                WHERE t.assigned_to = ? 
                ORDER BY t.due_date ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>