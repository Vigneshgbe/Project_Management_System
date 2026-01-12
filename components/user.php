<?php
require_once __DIR__ . '/../config.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function create($data) {
        $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", 
            $data['username'], 
            $data['email'], 
            $password_hash, 
            $data['full_name'], 
            $data['role']
        );
        return $stmt->execute() ? $this->db->insert_id : false;
    }
    
    public function update($id, $data) {
        if (isset($data['password']) && !empty($data['password'])) {
            $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET username = ?, email = ?, password = ?, full_name = ?, role = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssssssi", 
                $data['username'], 
                $data['email'], 
                $password_hash, 
                $data['full_name'], 
                $data['role'], 
                $data['status'], 
                $id
            );
        } else {
            $stmt = $this->db->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, role = ?, status = ? WHERE id = ?");
            $stmt->bind_param("sssssi", 
                $data['username'], 
                $data['email'], 
                $data['full_name'], 
                $data['role'], 
                $data['status'], 
                $id
            );
        }
        return $stmt->execute();
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT id, username, email, full_name, role, status, created_at FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function getAll($filters = []) {
        $sql = "SELECT id, username, email, full_name, role, status, created_at FROM users WHERE 1=1";
        $params = [];
        $types = "";
        
        if (isset($filters['role']) && $filters['role'] !== '') {
            $sql .= " AND role = ?";
            $params[] = $filters['role'];
            $types .= "s";
        }
        
        if (isset($filters['status']) && $filters['status'] !== '') {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }
        
        if (isset($filters['search']) && $filters['search'] !== '') {
            $sql .= " AND (full_name LIKE ? OR username LIKE ? OR email LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $types .= "sss";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
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
    
    public function getActiveUsers() {
        $result = $this->db->query("SELECT id, username, full_name, email, role FROM users WHERE status = 'active' ORDER BY full_name");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>
