<?php
session_start();
require_once 'components/auth.php';
require_once 'components/requirement.php';

$auth = new Auth();
$auth->checkAccess('manager');

$req_id = $_GET['id'] ?? 0;

if ($req_id) {
    $req_obj = new Requirement();
    $req = $req_obj->getById($req_id);
    $project_id = $req['project_id'];
    $req_obj->delete($req_id);
    
    header('Location: project-detail.php?id=' . $project_id . '&tab=requirements');
    exit;
}

header('Location: projects.php');
exit;
?>
