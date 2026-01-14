<?php
require_once __DIR__ . '/../config.php'; 

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT id, username, email, password, full_name, role, status FROM users WHERE (username = ? OR email = ?) AND status = 'active'");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                
                $this->logActivity($user['id'], null, 'login', 'User logged in');
                return true;
            }
        }
        return false;
    }
    
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], null, 'logout', 'User logged out');
        }
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public function getRole() {
        return $_SESSION['role'] ?? null;
    }
    
    public function isAdmin() {
        return $this->getRole() === 'admin';
    }
    
    public function isManager() {
        return in_array($this->getRole(), ['admin', 'manager']);
    }
    
    public function checkAccess($required_role = null) {
        if (!$this->isLoggedIn()) {
            header('Location: /login.php');
            exit;
        }
        
        if ($required_role) {
            if ($required_role === 'admin' && !$this->isAdmin()) {
                header('Location: /dashboard.php');
                exit;
            }
            if ($required_role === 'manager' && !$this->isManager()) {
                header('Location: /dashboard.php');
                exit;
            }
        }
    }
    
    public function register($username, $email, $password, $full_name, $role = 'user') {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $email, $password_hash, $full_name, $role);
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }
    
    private function logActivity($user_id, $project_id, $action, $description) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt = $this->db->prepare("INSERT INTO activity_log (user_id, project_id, action, description, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $user_id, $project_id, $action, $description, $ip);
        $stmt->execute();
    }
}
?>
