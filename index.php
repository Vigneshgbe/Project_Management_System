<?php
/**
 * Main Entry Point
 * Redirects to appropriate page based on authentication status
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Auth.php';

$auth = new Auth();

// Redirect to dashboard if logged in, otherwise to login page
if ($auth->isLoggedIn()) {
    header('Location: /modules/dashboard/index.php');
} else {
    header('Location: /modules/auth/login.php');
}
exit();
