<?php
/**
 * Logout Script
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$auth = new Auth();
$auth->logout();

setFlashMessage('You have been logged out successfully', 'success');
redirect('/modules/auth/login.php');
