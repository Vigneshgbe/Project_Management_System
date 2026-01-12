<?php
session_start();
require_once 'components/auth.php';
require_once 'components/project.php';

$auth = new Auth();
$auth->checkAccess('manager');

$project_id = $_GET['project_id'] ?? 0;
$user_id = $_GET['user_id'] ?? 0;

if ($project_id && $user_id) {
    $project = new Project();
    $project->removeMember($project_id, $user_id);
}

header('Location: project-detail.php?id=' . $project_id . '&tab=team');
exit;
?>
