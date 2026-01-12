<?php
require_once __DIR__ . '/../config.php';

class Pricing {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO pricing (project_id, item_name, description, category, unit_price, quantity) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssdi", 
            $data['project_id'], 
            $data['item_name'], 
            $data['description'], 
            $data['category'], 
            $data['unit_price'], 
            $data['quantity']
        );
        return $stmt->execute() ? $this->db->insert_id : false;
    }
    
    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE pricing SET item_name = ?, description = ?, category = ?, unit_price = ?, quantity = ? WHERE id = ?");
        $stmt->bind_param("sssdii", 
            $data['item_name'], 
            $data['description'], 
            $data['category'], 
            $data['unit_price'], 
            $data['quantity'], 
            $id
        );
        return $stmt->execute();
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM pricing WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM pricing WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function getByProject($project_id) {
        $sql = "SELECT * FROM pricing WHERE project_id = ? ORDER BY category, item_name";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getTotalByProject($project_id) {
        $stmt = $this->db->prepare("SELECT SUM(total_price) as total FROM pricing WHERE project_id = ?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'] ?? 0;
    }
    
    public function getByCategory($project_id) {
        $sql = "SELECT category, SUM(total_price) as total, COUNT(*) as item_count 
                FROM pricing 
                WHERE project_id = ? 
                GROUP BY category";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
