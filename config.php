<?php
// config.php — Padak CRM Configuration
define('CRM_VERSION', '1.0.0');
define('SITE_NAME', 'Padak CRM');
define('BASE_URL', 'http://localhost/padak-crm'); // Change for production

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'padak_crm');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', 3306);

// Upload settings
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_DOC_DIR', __DIR__ . '/uploads/documents/');
define('UPLOAD_AVATAR_DIR', __DIR__ . '/uploads/avatars/');
define('MAX_FILE_SIZE', 20 * 1024 * 1024); // 20MB
define('ALLOWED_DOC_TYPES', ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','png','jpg','jpeg','gif','zip','rar']);

// Session
define('SESSION_TIMEOUT', 3600 * 8); // 8 hours

// Timezone
date_default_timezone_set('Asia/Colombo');

// DB Connection
function getCRMDB(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli('p:'.DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        if ($conn->connect_error) {
            die(json_encode(['error' => 'DB connection failed: ' . $conn->connect_error]));
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

// Session init
function initSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('padak_crm');
        session_start();
    }
}

// Auth check
function requireLogin(): void {
    initSession();
    if (empty($_SESSION['crm_user_id'])) {
        header('Location: index.php');
        exit;
    }
    // Session timeout
    if (!empty($_SESSION['crm_last_activity']) && (time() - $_SESSION['crm_last_activity']) > SESSION_TIMEOUT) {
        session_destroy();
        header('Location: index.php?timeout=1');
        exit;
    }
    $_SESSION['crm_last_activity'] = time();
}

function requireRole(array $roles): void {
    requireLogin();
    if (!in_array($_SESSION['crm_role'] ?? '', $roles)) {
        header('Location: dashboard.php?err=access');
        exit;
    }
}

function currentUser(): array {
    return [
        'id'   => $_SESSION['crm_user_id'] ?? 0,
        'name' => $_SESSION['crm_name'] ?? '',
        'role' => $_SESSION['crm_role'] ?? '',
        'email'=> $_SESSION['crm_email'] ?? '',
        'avatar'=> $_SESSION['crm_avatar'] ?? null,
    ];
}

function isAdmin(): bool { return ($_SESSION['crm_role'] ?? '') === 'admin'; }
function isManager(): bool { return in_array($_SESSION['crm_role'] ?? '', ['admin','manager']); }

// Log activity
function logActivity(string $action, string $entity = '', int $entityId = 0, string $details = ''): void {
    $db = getCRMDB();
    $uid = $_SESSION['crm_user_id'] ?? null;
    $stmt = $db->prepare("INSERT INTO activity_log (user_id,action,entity_type,entity_id,details) VALUES (?,?,?,?,?)");
    $stmt->bind_param("issis", $uid, $action, $entity, $entityId, $details);
    $stmt->execute();
}

// Sanitize
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

// Format file size
function formatSize(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes/1048576, 1).' MB';
    if ($bytes >= 1024) return round($bytes/1024, 1).' KB';
    return $bytes.' B';
}

// Format date
function fDate(?string $d, string $fmt = 'M j, Y'): string {
    if (!$d) return '—';
    return date($fmt, strtotime($d));
}

// Status badge colors
function statusColor(string $status): string {
    return match($status) {
        'active','done','completed' => '#10b981',
        'planning','todo' => '#6366f1',
        'in_progress' => '#f59e0b',
        'review' => '#8b5cf6',
        'on_hold' => '#94a3b8',
        'cancelled','inactive' => '#ef4444',
        'prospect','lead' => '#f97316',
        'client' => '#10b981',
        'partner' => '#6366f1',
        'urgent','high' => '#ef4444',
        'medium' => '#f59e0b',
        'low' => '#10b981',
        default => '#94a3b8'
    };
}

function priorityIcon(string $p): string {
    return match($p) {
        'urgent' => '🔴',
        'high' => '🟠',
        'medium' => '🟡',
        'low' => '🟢',
        default => '⚪'
    };
}