<?php
/**
 * Authentication Class
 * Handles user login, logout, registration, and session management
 */

require_once __DIR__ . '/Database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }
    }
    
    /**
     * Login user
     */
    public function login($email, $password, $remember = false) {
        // Check login attempts
        if ($this->isLockedOut($email)) {
            return [
                'success' => false,
                'message' => 'Too many login attempts. Please try again later.'
            ];
        }
        
        // Get user by email
        $this->db->query("SELECT * FROM users WHERE email = :email AND status = 'active' LIMIT 1");
        $this->db->bind(':email', $email);
        $user = $this->db->single();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Clear login attempts
            $this->clearLoginAttempts($email);
            
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            
            // Generate CSRF token
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
            
            // Update last login
            $this->db->query("UPDATE users SET last_login = NOW() WHERE user_id = :user_id");
            $this->db->bind(':user_id', $user['user_id']);
            $this->db->execute();
            
            // Log activity
            $this->logActivity($user['user_id'], 'login', 'user', $user['user_id'], 'User logged in');
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => $user
            ];
        } else {
            // Record failed attempt
            $this->recordLoginAttempt($email);
            
            return [
                'success' => false,
                'message' => 'Invalid email or password'
            ];
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'user', $_SESSION['user_id'], 'User logged out');
        }
        
        // Destroy session
        $_SESSION = [];
        if (isset($_COOKIE[SESSION_NAME])) {
            setcookie(SESSION_NAME, '', time() - 3600, '/');
        }
        session_destroy();
        
        return true;
    }
    
    /**
     * Register new user
     */
    public function register($data) {
        // Validate required fields
        $required = ['username', 'email', 'password', 'full_name'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return [
                    'success' => false,
                    'message' => ucfirst($field) . ' is required'
                ];
            }
        }
        
        // Check if email exists
        $this->db->query("SELECT user_id FROM users WHERE email = :email");
        $this->db->bind(':email', $data['email']);
        if ($this->db->single()) {
            return [
                'success' => false,
                'message' => 'Email already exists'
            ];
        }
        
        // Check if username exists
        $this->db->query("SELECT user_id FROM users WHERE username = :username");
        $this->db->bind(':username', $data['username']);
        if ($this->db->single()) {
            return [
                'success' => false,
                'message' => 'Username already exists'
            ];
        }
        
        // Hash password
        $password_hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => HASH_COST]);
        
        // Insert user
        $this->db->query("
            INSERT INTO users (username, email, password_hash, full_name, role, phone, status) 
            VALUES (:username, :email, :password_hash, :full_name, :role, :phone, 'active')
        ");
        
        $this->db->bind(':username', $data['username']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':password_hash', $password_hash);
        $this->db->bind(':full_name', $data['full_name']);
        $this->db->bind(':role', $data['role'] ?? 'team_member');
        $this->db->bind(':phone', $data['phone'] ?? null);
        
        if ($this->db->execute()) {
            $userId = $this->db->lastInsertId();
            $this->logActivity($userId, 'register', 'user', $userId, 'New user registered');
            
            return [
                'success' => true,
                'message' => 'Registration successful',
                'user_id' => $userId
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Registration failed. Please try again.'
            ];
        }
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Check if user has specific role
     */
    public function hasRole($role) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        if (is_array($role)) {
            return in_array($_SESSION['role'], $role);
        }
        
        return $_SESSION['role'] === $role;
    }
    
    /**
     * Get current user ID
     */
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user data
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $this->db->query("SELECT * FROM users WHERE user_id = :user_id");
        $this->db->bind(':user_id', $_SESSION['user_id']);
        return $this->db->single();
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }
    
    /**
     * Verify CSRF token
     */
    public function verifyCSRFToken($token) {
        return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
    
    /**
     * Record login attempt
     */
    private function recordLoginAttempt($email) {
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }
        
        $_SESSION['login_attempts'][$email] = [
            'count' => ($_SESSION['login_attempts'][$email]['count'] ?? 0) + 1,
            'time' => time()
        ];
    }
    
    /**
     * Clear login attempts
     */
    private function clearLoginAttempts($email) {
        if (isset($_SESSION['login_attempts'][$email])) {
            unset($_SESSION['login_attempts'][$email]);
        }
    }
    
    /**
     * Check if account is locked out
     */
    private function isLockedOut($email) {
        if (!isset($_SESSION['login_attempts'][$email])) {
            return false;
        }
        
        $attempts = $_SESSION['login_attempts'][$email];
        
        if ($attempts['count'] >= MAX_LOGIN_ATTEMPTS) {
            $lockoutExpiry = $attempts['time'] + LOGIN_LOCKOUT_TIME;
            if (time() < $lockoutExpiry) {
                return true;
            } else {
                // Lockout expired, clear attempts
                $this->clearLoginAttempts($email);
                return false;
            }
        }
        
        return false;
    }
    
    /**
     * Log user activity
     */
    private function logActivity($userId, $action, $entityType = null, $entityId = null, $description = null) {
        $this->db->query("
            INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address) 
            VALUES (:user_id, :action, :entity_type, :entity_id, :description, :ip_address)
        ");
        
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':action', $action);
        $this->db->bind(':entity_type', $entityType);
        $this->db->bind(':entity_id', $entityId);
        $this->db->bind(':description', $description);
        $this->db->bind(':ip_address', $_SERVER['REMOTE_ADDR'] ?? null);
        
        $this->db->execute();
    }
}
