<?php
/**
 * Helper Functions
 * Common utility functions used throughout the application
 */

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to another page
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Set flash message
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type; // success, error, warning, info
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Check if user is logged in, redirect if not
 */
function requireLogin() {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        setFlashMessage('Please login to access this page', 'warning');
        redirect('/modules/auth/login.php');
    }
}

/**
 * Check if user has specific role
 */
function requireRole($role) {
    $auth = new Auth();
    if (!$auth->hasRole($role)) {
        setFlashMessage('Access denied. Insufficient permissions.', 'error');
        redirect('/modules/dashboard/index.php');
    }
}

/**
 * Format date
 */
function formatDate($date, $format = 'M d, Y') {
    if (empty($date)) return 'N/A';
    return date($format, strtotime($date));
}

/**
 * Format currency
 */
function formatCurrency($amount, $currency = '$') {
    return $currency . number_format($amount, 2);
}

/**
 * Calculate percentage
 */
function calculatePercentage($part, $whole) {
    if ($whole == 0) return 0;
    return round(($part / $whole) * 100);
}

/**
 * Get status badge class
 */
function getStatusBadge($status) {
    $badges = [
        'active' => 'success',
        'completed' => 'primary',
        'pending' => 'warning',
        'in_progress' => 'info',
        'on_hold' => 'secondary',
        'cancelled' => 'danger',
        'inactive' => 'secondary',
        'suspended' => 'danger'
    ];
    
    return $badges[$status] ?? 'secondary';
}

/**
 * Get priority badge class
 */
function getPriorityBadge($priority) {
    $badges = [
        'low' => 'success',
        'medium' => 'info',
        'high' => 'warning',
        'critical' => 'danger'
    ];
    
    return $badges[$priority] ?? 'secondary';
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Upload file
 */
function uploadFile($file, $allowedTypes = null, $maxSize = null) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'No file uploaded or upload error occurred'];
    }
    
    $allowedTypes = $allowedTypes ?? ALLOWED_FILE_TYPES;
    $maxSize = $maxSize ?? MAX_FILE_SIZE;
    
    // Check file size
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File size exceeds maximum allowed size'];
    }
    
    // Check file type
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedTypes)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    // Generate unique filename
    $newFilename = uniqid() . '_' . time() . '.' . $extension;
    $uploadPath = UPLOAD_DIR . $newFilename;
    
    // Create upload directory if not exists
    if (!file_exists(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0777, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return [
            'success' => true,
            'filename' => $newFilename,
            'path' => $uploadPath,
            'original_name' => $file['name']
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to upload file'];
}

/**
 * Delete file
 */
function deleteFile($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Get time ago format
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return $difference . ' seconds ago';
    } elseif ($difference < 3600) {
        return floor($difference / 60) . ' minutes ago';
    } elseif ($difference < 86400) {
        return floor($difference / 3600) . ' hours ago';
    } elseif ($difference < 604800) {
        return floor($difference / 86400) . ' days ago';
    } else {
        return date('M d, Y', $timestamp);
    }
}

/**
 * Truncate text
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Generate pagination
 */
function generatePagination($currentPage, $totalPages, $baseUrl) {
    $html = '<nav><ul class="pagination">';
    
    // Previous button
    $prevDisabled = ($currentPage <= 1) ? 'disabled' : '';
    $prevPage = max(1, $currentPage - 1);
    $html .= "<li class='page-item $prevDisabled'><a class='page-link' href='$baseUrl?page=$prevPage'>Previous</a></li>";
    
    // Page numbers
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = ($i == $currentPage) ? 'active' : '';
        $html .= "<li class='page-item $active'><a class='page-link' href='$baseUrl?page=$i'>$i</a></li>";
    }
    
    // Next button
    $nextDisabled = ($currentPage >= $totalPages) ? 'disabled' : '';
    $nextPage = min($totalPages, $currentPage + 1);
    $html .= "<li class='page-item $nextDisabled'><a class='page-link' href='$baseUrl?page=$nextPage'>Next</a></li>";
    
    $html .= '</ul></nav>';
    return $html;
}

/**
 * Log activity
 */
function logActivity($userId, $action, $entityType = null, $entityId = null, $description = null) {
    $db = Database::getInstance();
    $db->query("
        INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address) 
        VALUES (:user_id, :action, :entity_type, :entity_id, :description, :ip_address)
    ");
    
    $db->bind(':user_id', $userId);
    $db->bind(':action', $action);
    $db->bind(':entity_type', $entityType);
    $db->bind(':entity_id', $entityId);
    $db->bind(':description', $description);
    $db->bind(':ip_address', $_SERVER['REMOTE_ADDR'] ?? null);
    
    return $db->execute();
}

/**
 * Send notification
 */
function sendNotification($userId, $title, $message, $type = 'info', $entityType = null, $entityId = null) {
    $db = Database::getInstance();
    $db->query("
        INSERT INTO notifications (user_id, title, message, type, entity_type, entity_id) 
        VALUES (:user_id, :title, :message, :type, :entity_type, :entity_id)
    ");
    
    $db->bind(':user_id', $userId);
    $db->bind(':title', $title);
    $db->bind(':message', $message);
    $db->bind(':type', $type);
    $db->bind(':entity_type', $entityType);
    $db->bind(':entity_id', $entityId);
    
    return $db->execute();
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    $auth = new Auth();
    return $auth->verifyCSRFToken($token);
}

/**
 * Get current page URL
 */
function getCurrentUrl() {
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
           . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
}

/**
 * Check if request is POST
 */
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Check if request is GET
 */
function isGet() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}
