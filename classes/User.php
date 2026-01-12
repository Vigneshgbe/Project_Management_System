<?php
/**
 * User Class
 * Handles user CRUD operations and user-related queries
 */

require_once __DIR__ . '/Database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all users with pagination and filters
     */
    public function getAll($page = 1, $perPage = RECORDS_PER_PAGE, $filters = []) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT user_id, username, email, full_name, role, phone, status, created_at, last_login 
                FROM users WHERE 1=1";
        
        // Apply filters
        if (!empty($filters['role'])) {
            $sql .= " AND role = :role";
        }
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (full_name LIKE :search OR email LIKE :search OR username LIKE :search)";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        
        $this->db->query($sql);
        
        if (!empty($filters['role'])) {
            $this->db->bind(':role', $filters['role']);
        }
        if (!empty($filters['status'])) {
            $this->db->bind(':status', $filters['status']);
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
     * Get total user count
     */
    public function getTotalCount($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM users WHERE 1=1";
        
        if (!empty($filters['role'])) {
            $sql .= " AND role = :role";
        }
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (full_name LIKE :search OR email LIKE :search OR username LIKE :search)";
        }
        
        $this->db->query($sql);
        
        if (!empty($filters['role'])) {
            $this->db->bind(':role', $filters['role']);
        }
        if (!empty($filters['status'])) {
            $this->db->bind(':status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $this->db->bind(':search', $searchTerm);
        }
        
        $result = $this->db->single();
        return $result['total'] ?? 0;
    }
    
    /**
     * Get user by ID
     */
    public function getById($userId) {
        $this->db->query("SELECT * FROM users WHERE user_id = :user_id");
        $this->db->bind(':user_id', $userId);
        return $this->db->single();
    }
    
    /**
     * Get user by email
     */
    public function getByEmail($email) {
        $this->db->query("SELECT * FROM users WHERE email = :email");
        $this->db->bind(':email', $email);
        return $this->db->single();
    }
    
    /**
     * Create new user
     */
    public function create($data) {
        // Validate email uniqueness
        if ($this->getByEmail($data['email'])) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        $this->db->query("
            INSERT INTO users (username, email, password_hash, full_name, role, phone, status) 
            VALUES (:username, :email, :password_hash, :full_name, :role, :phone, :status)
        ");
        
        $this->db->bind(':username', $data['username']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':password_hash', password_hash($data['password'], PASSWORD_BCRYPT));
        $this->db->bind(':full_name', $data['full_name']);
        $this->db->bind(':role', $data['role'] ?? 'team_member');
        $this->db->bind(':phone', $data['phone'] ?? null);
        $this->db->bind(':status', $data['status'] ?? 'active');
        
        if ($this->db->execute()) {
            return [
                'success' => true,
                'message' => 'User created successfully',
                'user_id' => $this->db->lastInsertId()
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to create user'];
    }
    
    /**
     * Update user
     */
    public function update($userId, $data) {
        $sql = "UPDATE users SET 
                full_name = :full_name,
                role = :role,
                phone = :phone,
                status = :status";
        
        // Update password only if provided
        if (!empty($data['password'])) {
            $sql .= ", password_hash = :password_hash";
        }
        
        $sql .= " WHERE user_id = :user_id";
        
        $this->db->query($sql);
        
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':full_name', $data['full_name']);
        $this->db->bind(':role', $data['role']);
        $this->db->bind(':phone', $data['phone'] ?? null);
        $this->db->bind(':status', $data['status']);
        
        if (!empty($data['password'])) {
            $this->db->bind(':password_hash', password_hash($data['password'], PASSWORD_BCRYPT));
        }
        
        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'User updated successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to update user'];
    }
    
    /**
     * Delete user
     */
    public function delete($userId) {
        $this->db->query("DELETE FROM users WHERE user_id = :user_id");
        $this->db->bind(':user_id', $userId);
        
        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'User deleted successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to delete user'];
    }
    
    /**
     * Get users by role
     */
    public function getByRole($role) {
        $this->db->query("SELECT user_id, username, full_name, email FROM users WHERE role = :role AND status = 'active'");
        $this->db->bind(':role', $role);
        return $this->db->resultSet();
    }
    
    /**
     * Get user statistics
     */
    public function getStatistics() {
        $this->db->query("
            SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as total_admins,
                SUM(CASE WHEN role = 'manager' THEN 1 ELSE 0 END) as total_managers,
                SUM(CASE WHEN role = 'team_member' THEN 1 ELSE 0 END) as total_members,
                SUM(CASE WHEN role = 'client' THEN 1 ELSE 0 END) as total_clients,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users
            FROM users
        ");
        
        return $this->db->single();
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $data) {
        $this->db->query("
            UPDATE users SET 
                full_name = :full_name,
                phone = :phone,
                profile_image = :profile_image
            WHERE user_id = :user_id
        ");
        
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':full_name', $data['full_name']);
        $this->db->bind(':phone', $data['phone'] ?? null);
        $this->db->bind(':profile_image', $data['profile_image'] ?? null);
        
        return $this->db->execute();
    }
    
    /**
     * Change password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        // Verify current password
        $user = $this->getById($userId);
        
        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        
        // Update password
        $this->db->query("UPDATE users SET password_hash = :password_hash WHERE user_id = :user_id");
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':password_hash', password_hash($newPassword, PASSWORD_BCRYPT));
        
        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Password changed successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to change password'];
    }
}
