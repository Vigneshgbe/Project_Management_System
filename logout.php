<?php
session_start();
require_once 'components/auth.php';

$auth = new Auth();
$auth->logout();

header('Location: login.php');
exit;
?>
