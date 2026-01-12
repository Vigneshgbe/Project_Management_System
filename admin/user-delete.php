<?php
session_start();
require_once '../components/auth.php';
require_once '../components/user.php';

$auth = new Auth();
$auth->checkAccess('admin');

$user_id = $_GET['id'] ?? 0;

if ($user_id && $user_id != $auth->getUserId()) {
    $user = new User();
    $user->delete($user_id);
}

header('Location: users.php');
exit;
?>
