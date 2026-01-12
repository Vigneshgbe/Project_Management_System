<?php
require_once __DIR__ . '/../config.php';

class Project {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO projects (project_name, project_code, description, client_name, start_date, end_date, status, priority, budget, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssdi", 
            $data['project_name'], 
            $data['project_code'], 
            $data['description'], 
            $data['client_name'], 
            $data['start_date'], 
            $data['end_date'], 
            $data['status'], 
            $data['priority'], 
            $data['budget'], 
            $data['created_by']
        );
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }
    
    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE projects SET project_name = ?, description = ?, client_name = ?, start_date = ?, end_date = ?, status = ?, priority = ?, budget = ? WHERE id = ?");
        $stmt->bind_param("sssssssdi", 
            $data['project_name'], 
            $data['description'], 
            $data['client_name'], 
            $data['start_date'], 
            $data['end_date'], 
            $data['status'], 
            $data['priority'], 
            $data['budget'], 
            $id
        );
        return $stmt->execute();
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT p.*, u.full_name as creator_name FROM projects p LEFT JOIN users u ON p.created_by = u.id WHERE p.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    public function getAll($filters = []) {
        $sql = "SELECT p.*, u.full_name as creator_name, 
                (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) as member_count,
                (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as task_count,
                (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status = 'completed') as completed_tasks
                FROM projects p 
                LEFT JOIN users u ON p.created_by = u.id 
                WHERE 1=1";
        
        $params = [];
        $types = "";
        
        if (isset($filters['status']) && $filters['status'] !== '') {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }
        
        if (isset($filters['priority']) && $filters['priority'] !== '') {
            $sql .= " AND p.priority = ?";
            $params[] = $filters['priority'];
            $types .= "s";
        }
        
        if (isset($filters['search']) && $filters['search'] !== '') {
            $sql .= " AND (p.project_name LIKE ? OR p.project_code LIKE ? OR p.client_name LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $types .= "sss";
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
        }
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getUserProjects($user_id) {
        $sql = "SELECT DISTINCT p.*, u.full_name as creator_name,
                (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as task_count,
                (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status = 'completed') as completed_tasks
                FROM projects p 
                LEFT JOIN users u ON p.created_by = u.id
                LEFT JOIN project_members pm ON p.id = pm.project_id
                WHERE p.created_by = ? OR pm.user_id = ?
                ORDER BY p.updated_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function addMember($project_id, $user_id, $role = 'member') {
        $stmt = $this->db->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE role = ?");
        $stmt->bind_param("iiss", $project_id, $user_id, $role, $role);
        return $stmt->execute();
    }
    
    public function removeMember($project_id, $user_id) {
        $stmt = $this->db->prepare("DELETE FROM project_members WHERE project_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $project_id, $user_id);
        return $stmt->execute();
    }
    
    public function getMembers($project_id) {
        $sql = "SELECT pm.*, u.full_name, u.email, u.role as user_role 
                FROM project_members pm 
                JOIN users u ON pm.user_id = u.id 
                WHERE pm.project_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getStats($project_id) {
        $stats = [];
        
        $stmt = $this->db->prepare("SELECT COUNT(*) as total, status FROM tasks WHERE project_id = ? GROUP BY status");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['tasks'] = $result->fetch_all(MYSQLI_ASSOC);
        
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM requirements WHERE project_id = ?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['requirements'] = $result->fetch_assoc()['total'];
        
        $stmt = $this->db->prepare("SELECT SUM(total_price) as total FROM pricing WHERE project_id = ?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['total_pricing'] = $result->fetch_assoc()['total'] ?? 0;
        
        return $stats;
    }
}
?>
