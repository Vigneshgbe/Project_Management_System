<?php
/**
 * Delete Project Handler
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Project.php';
require_once __DIR__ . '/../../includes/functions.php';

// Check authentication and permissions
requireLogin();
requireRole('admin'); // Only admins can delete

$auth = new Auth();
$projectClass = new Project();

$projectId = $_GET['id'] ?? null;

if (!$projectId) {
    setFlashMessage('Invalid project ID', 'error');
    redirect('/modules/projects/index.php');
}

// Get project details for logging
$project = $projectClass->getById($projectId);

if (!$project) {
    setFlashMessage('Project not found', 'error');
    redirect('/modules/projects/index.php');
}

// Delete project
$result = $projectClass->delete($projectId);

if ($result['success']) {
    logActivity($auth->getUserId(), 'delete', 'project', $projectId, 'Deleted project: ' . $project['project_name']);
    setFlashMessage('Project deleted successfully', 'success');
} else {
    setFlashMessage($result['message'], 'error');
}

redirect('/modules/projects/index.php');
