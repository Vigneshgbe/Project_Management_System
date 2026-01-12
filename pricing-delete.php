<?php
session_start();
require_once 'components/auth.php';
require_once 'components/pricing.php';

$auth = new Auth();
$auth->checkAccess('manager');

$pricing_id = $_GET['id'] ?? 0;

if ($pricing_id) {
    $pricing_obj = new Pricing();
    $pricing = $pricing_obj->getById($pricing_id);
    $project_id = $pricing['project_id'];
    $pricing_obj->delete($pricing_id);
    
    header('Location: project-detail.php?id=' . $project_id . '&tab=pricing');
    exit;
}

header('Location: projects.php');
exit;
?>
