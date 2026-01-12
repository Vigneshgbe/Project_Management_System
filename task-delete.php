<?php
session_start();
require_once 'components/auth.php';
require_once 'components/task.php';

$auth = new Auth();
$auth->checkAccess();

$task_id = $_GET['id'] ?? 0;

if ($task_id) {
    $task_obj = new Task();
    $task = $task_obj->getById($task_id);
    $project_id = $task['project_id'];
    $task_obj->delete($task_id);
    
    header('Location: project-detail.php?id=' . $project_id . '&tab=tasks');
    exit;
}

header('Location: tasks.php');
exit;
?>
