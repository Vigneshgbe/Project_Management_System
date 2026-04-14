<?php
// logout.php
require_once 'config.php';
initSession();
if (!empty($_SESSION['crm_user_id'])) {
    $db = getCRMDB();
    logActivity('logout', 'user', $_SESSION['crm_user_id']);
}
session_destroy();
header('Location: index.php');
exit;