<?php
session_start();
require_once 'components/auth.php';
require_once 'components/project.php';

$auth = new Auth();
$auth->checkAccess('manager');

$project_id = $_GET['id'] ?? 0;

if ($project_id) {
    $project = new Project();
    $project->delete($project_id);
}

header('Location: projects.php');
exit;
?>
