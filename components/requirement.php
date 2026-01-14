<?php 
require_once __DIR__ . '/../config.php';

class Requirement {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO requirements (project_id, requirement_title, description, type, priority, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssi", 
            $data['project_id'], 
            $data['requirement_title'], 
            $data['description'], 
            $data['type'], 
            $data['priority'], 
            $data['status'], 
            $data['created_by']
        );
        return $stmt->execute() ? $this->db->insert_id : false;
    }
    
    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE requirements SET requirement_title = ?, description = ?, type = ?, priority = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sssssi", 
            $data['requirement_title'], 
            $data['description'], 
            $data['type'], 
            $data['priority'], 
            $data['status'], 
            $id
        );
        return $stmt->execute();
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM requirements WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT r.*, u.full_name as creator_name FROM requirements r LEFT JOIN users u ON r.created_by = u.id WHERE r.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function getByProject($project_id) {
        $sql = "SELECT r.*, u.full_name as creator_name 
                FROM requirements r 
                LEFT JOIN users u ON r.created_by = u.id
                WHERE r.project_id = ? 
                ORDER BY r.priority DESC, r.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
