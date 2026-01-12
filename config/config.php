<?php
/**
 * General Configuration File
 */

// Application Settings
define('APP_NAME', 'Project Management System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'https://yourdomain.com'); // Your Hostinger domain

// Timezone
date_default_timezone_set('UTC'); // Change to your timezone

// Session Configuration
define('SESSION_LIFETIME', 7200); // 2 hours in seconds
define('SESSION_NAME', 'PMS_SESSION');

// Security Settings
define('HASH_COST', 10); // Password hashing cost
define('CSRF_TOKEN_NAME', 'csrf_token');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// File Upload Settings
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip']);

// Pagination
define('RECORDS_PER_PAGE', 10);

// Email Settings (if using email notifications)
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@yourdomain.com');
define('SMTP_PASS', 'your-email-password');
define('SMTP_FROM', 'noreply@yourdomain.com');
define('SMTP_FROM_NAME', APP_NAME);

// Development/Production Mode
define('DEBUG_MODE', true); // Set to false in production
define('ERROR_REPORTING', E_ALL); // Change to 0 in production

// Set error reporting
if (DEBUG_MODE) {
    error_reporting(ERROR_REPORTING);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Auto-load configuration
require_once __DIR__ . '/database.php';
