<?php
$page_title = 'Project Details';
require_once 'includes/header.php';
require_once 'components/project.php';
require_once 'components/task.php';
require_once 'components/requirement.php';
require_once 'components/pricing.php';

$auth->checkAccess();

$project_id = $_GET['id'] ?? 0;
$active_tab = $_GET['tab'] ?? 'overview';

$project_obj = new Project();
$task_obj = new Task();
$req_obj = new Requirement();
$pricing_obj = new Pricing();

$project = $project_obj->getById($project_id);

if (!$project) {
    header('Location: projects.php');
    exit;
}

$tasks = $task_obj->getByProject($project_id);
$requirements = $req_obj->getByProject($project_id);
$pricing = $pricing_obj->getByProject($project_id);
$members = $project_obj->getMembers($project_id);
$stats = $project_obj->getStats($project_id);
?>

<style>
    /* MODERN ULTRA-FAST DESIGN */
    
    :root {
        --primary: #6366f1;
        --primary-dark: #4f46e5;
        --secondary: #8b5cf6;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --dark: #1e293b;
        --light: #f8fafc;
        --border: #e2e8f0;
        --shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07);
        --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
    }
    
    .project-detail-container {
        padding: 24px;
        max-width: 1400px;
        margin: 0 auto;
        animation: fadeIn 0.4s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* MODERN PAGE HEADER */
    .page-header {
        background: white;
        padding: 32px;
        border-radius: 16px;
        margin-bottom: 32px;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--border);
        position: relative;
        overflow: hidden;
    }
    
    .page-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
    }
    
    .page-header h1 {
        margin: 0 0 8px 0;
        font-weight: 700;
        font-size: 32px;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .page-header h1 i {
        color: var(--primary);
        font-size: 28px;
    }
    
    .page-header small {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #64748b;
        margin-top: 4px;
    }
    
    /* STATUS & PRIORITY BADGES */
    .badge-status, .badge-priority {
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-right: 8px;
        margin-bottom: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }
    
    .badge-status::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.6; transform: scale(1.2); }
    }
    
    .badge-status:hover, .badge-priority:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .badge-planning { background: #fef3c7; color: #92400e; }
    .badge-in_progress { background: #dbeafe; color: #1e40af; }
    .badge-on_hold { background: #fee2e2; color: #991b1b; }
    .badge-completed { background: #d1fae5; color: #065f46; }
    .badge-cancelled { background: #e5e7eb; color: #374151; }
    
    .badge-low { background: #d1fae5; color: #065f46; }
    .badge-medium { background: #fef3c7; color: #92400e; }
    .badge-high { background: #fed7aa; color: #9a3412; }
    .badge-critical { background: #fee2e2; color: #991b1b; }
    
    /* MODERN TABS */
    .nav-tabs {
        border: none;
        background: white;
        padding: 12px;
        border-radius: 12px;
        box-shadow: var(--shadow);
        margin-bottom: 24px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        border: 1px solid var(--border);
    }
    
    .nav-tabs > li {
        margin-bottom: 0;
        flex: 1;
        min-width: 140px;
    }
    
    .nav-tabs > li > a {
        border: none;
        border-radius: 8px;
        padding: 12px 18px;
        font-weight: 600;
        font-size: 14px;
        color: #64748b;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-align: center;
        background: transparent;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .nav-tabs > li > a i {
        font-size: 16px;
    }
    
    .nav-tabs > li > a:hover {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
        color: var(--primary);
        transform: translateY(-2px);
    }
    
    .nav-tabs > li.active > a,
    .nav-tabs > li.active > a:hover,
    .nav-tabs > li.active > a:focus {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        border: none;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }
    
    .tab-content {
        animation: fadeInUp 0.4s ease;
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* MODERN PANELS */
    .panel {
        border: none;
        box-shadow: var(--shadow);
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        background: white;
        border: 1px solid var(--border);
        margin-bottom: 24px;
    }
    
    .panel:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }
    
    .panel-heading {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        padding: 20px 24px;
        border: none;
    }
    
    .panel-title {
        font-size: 17px;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        color:white
    }
    
    .panel-body {
        padding: 24px;
        background: white;
    }
    
    /* MODERN TABLE */
    .table {
        margin-bottom: 0;
        background: white;
    }
    
    .table > thead > tr > th {
        background: linear-gradient(135deg, #f8fafc, #ffffff);
        color: var(--dark);
        font-weight: 700;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.8px;
        padding: 14px 16px;
        border-bottom: 2px solid var(--border);
        border-top: none;
    }
    
    .table > tbody > tr > td {
        padding: 14px 16px;
        vertical-align: middle;
        border-top: 1px solid var(--border);
    }
    
    .table-bordered {
        border: none;
        border-radius: 12px;
        overflow: hidden;
    }
    
    .table-hover > tbody > tr {
        transition: all 0.3s ease;
    }
    
    .table-hover > tbody > tr:hover {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.05));
        transform: translateX(4px);
    }
    
    /* REQUIREMENT ITEMS */
    .requirement-item {
        background: white;
        padding: 24px;
        border-radius: 12px;
        margin-bottom: 16px;
        box-shadow: var(--shadow);
        border-left: 4px solid var(--primary);
        transition: all 0.3s ease;
        border: 1px solid var(--border);
        border-left-width: 4px;
    }
    
    .requirement-item:hover {
        transform: translateX(4px);
        box-shadow: var(--shadow-md);
        border-left-color: var(--secondary);
    }
    
    .requirement-item h4 {
        margin-top: 0;
        margin-bottom: 12px;
        font-weight: 700;
        color: var(--dark);
        font-size: 17px;
    }
    
    .requirement-item p {
        color: #64748b;
        margin-bottom: 16px;
        line-height: 1.6;
    }
    
    /* LABELS */
    .label {
        padding: 6px 12px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .label-default {
        background: #e5e7eb;
        color: #374151;
    }
    
    .label-primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
    }
    
    /* MEMBER AVATAR */
    .member-avatar {
        display: inline-flex;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-weight: 700;
        font-size: 14px;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
        transition: all 0.3s ease;
    }
    
    .member-avatar:hover {
        transform: scale(1.1) rotate(5deg);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
    }
    
    /* MODERN BUTTONS */
    .btn-default {
        background: white;
        color: var(--primary);
        border: 2px solid var(--primary);
        border-radius: 10px;
        padding: 10px 20px;
        font-weight: 700;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.8px;
    }
    
    .btn-default:hover,
    .btn-default:focus {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(99, 102, 241, 0.3);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border: none;
        border-radius: 10px;
        padding: 12px 24px;
        font-weight: 700;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.8px;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        color: white;
    }
    
    .btn-primary:hover,
    .btn-primary:focus {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        color: white;
    }
    
    .btn-danger {
        background: linear-gradient(135deg, var(--danger), #dc2626);
        border: none;
        border-radius: 10px;
        padding: 10px 20px;
        font-weight: 700;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.8px;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
        color: white;
    }
    
    .btn-danger:hover,
    .btn-danger:focus {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
        color: white;
    }
    
    .btn-xs {
        padding: 6px 12px;
        font-size: 11px;
    }
    
    /* ALERTS */
    .alert {
        border: none;
        border-radius: 12px;
        padding: 16px 20px;
        font-weight: 600;
        box-shadow: var(--shadow);
        border-left: 4px solid;
    }
    
    .alert-info {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(37, 99, 235, 0.05));
        color: #1e40af;
        border-left-color: #3b82f6;
    }
    
    /* STAT ROWS */
    .stat-row {
        padding: 10px 0;
        transition: all 0.3s ease;
        border-radius: 8px;
    }
    
    .stat-row:hover {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.05));
        padding-left: 12px;
    }
    
    .panel-body h4 {
        color: var(--dark);
        font-weight: 700;
        margin-top: 0;
        margin-bottom: 16px;
        font-size: 15px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }
    
    .panel-body hr {
        border-top: 2px solid var(--border);
        margin: 20px 0;
    }
    
    /* INFO TABLE */
    .info-table {
        margin: 0;
    }
    
    .info-table tr {
        transition: all 0.3s ease;
    }
    
    .info-table tr:hover {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.05));
    }
    
    .info-table th {
        font-weight: 700;
        color: #64748b;
        padding: 12px 16px;
        width: 200px;
    }
    
    .info-table td {
        padding: 12px 16px;
        color: var(--dark);
    }
    
    /* PRICING TOTAL ROW */
    .table .info {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
        font-weight: 700;
    }
    
    /* SMOOTH SCROLLBAR */
    ::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }
    
    ::-webkit-scrollbar-track {
        background: #f1f5f9;
    }
    
    ::-webkit-scrollbar-thumb {
        background: var(--primary);
        border-radius: 5px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: var(--primary-dark);
    }
    
    /* LOADING STATES */
    .loading-skeleton {
        animation: shimmer 1.5s infinite;
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
    }
    
    @keyframes shimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    
    /* RESPONSIVE */
    @media (max-width: 1200px) {
        .project-detail-container { padding: 20px; }
        .page-header { padding: 28px; }
        .page-header h1 { font-size: 28px; }
    }
    
    @media (max-width: 992px) {
        .nav-tabs > li { min-width: 120px; }
        .panel-body { padding: 20px; }
    }
    
    @media (max-width: 768px) {
        .project-detail-container { padding: 16px; }
        .page-header {
            padding: 24px 20px;
            margin-bottom: 24px;
        }
        .page-header h1 {
            font-size: 24px;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        .page-header small { font-size: 13px; }
        .nav-tabs {
            padding: 10px;
            gap: 6px;
        }
        .nav-tabs > li {
            flex: 1 1 100%;
            min-width: 100%;
        }
        .nav-tabs > li > a {
            padding: 12px 16px;
            font-size: 13px;
        }
        .panel-body { padding: 16px; }
        .requirement-item { padding: 20px; }
        .btn-default,
        .btn-primary,
        .btn-danger {
            margin-bottom: 10px;
            width: 100%;
        }
        .info-table th { width: 120px; }
    }
    
    @media (max-width: 480px) {
        .project-detail-container { padding: 12px; }
        .page-header h1 { font-size: 20px; }
        .badge-status, .badge-priority {
            font-size: 10px;
            padding: 5px 10px;
        }
        .nav-tabs > li > a {
            font-size: 12px;
            padding: 10px 12px;
        }
        .table > thead > tr > th,
        .table > tbody > tr > td {
            padding: 10px 12px;
            font-size: 13px;
        }
        .info-table th,
        .info-table td {
            padding: 10px 12px;
            font-size: 13px;
        }
    }
</style>

<div class="project-detail-container container-fluid">
    <div class="page-header">
        <div class="row">
            <div class="col-md-8">
                <h1>
                    <i class="fa fa-folder"></i> <?php echo htmlspecialchars($project['project_name']); ?>
                </h1>
                <small><?php echo htmlspecialchars($project['project_code']); ?></small>
            </div>
            <div class="col-md-4 text-right">
                <?php if ($auth->isManager()): ?>
                <a href="project-edit.php?id=<?php echo $project_id; ?>" class="btn btn-default">
                    <i class="fa fa-edit"></i> Edit
                </a>
                <a href="project-delete.php?id=<?php echo $project_id; ?>" class="btn btn-danger delete-confirm">
                    <i class="fa fa-trash"></i> Delete
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <span class="badge-status badge-<?php echo $project['status']; ?>">
                <?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?>
            </span>
            <span class="badge-priority badge-<?php echo $project['priority']; ?>">
                <?php echo ucfirst($project['priority']); ?> Priority
            </span>
        </div>
    </div>
    
    <br>
    
    <ul class="nav nav-tabs">
        <li class="<?php echo $active_tab === 'overview' ? 'active' : ''; ?>">
            <a href="?id=<?php echo $project_id; ?>&tab=overview">
                <i class="fa fa-info-circle"></i> Overview
            </a>
        </li>
        <li class="<?php echo $active_tab === 'tasks' ? 'active' : ''; ?>">
            <a href="?id=<?php echo $project_id; ?>&tab=tasks">
                <i class="fa fa-tasks"></i> Tasks (<?php echo count($tasks); ?>)
            </a>
        </li>
        <li class="<?php echo $active_tab === 'requirements' ? 'active' : ''; ?>">
            <a href="?id=<?php echo $project_id; ?>&tab=requirements">
                <i class="fa fa-list"></i> Requirements (<?php echo count($requirements); ?>)
            </a>
        </li>
        <li class="<?php echo $active_tab === 'pricing' ? 'active' : ''; ?>">
            <a href="?id=<?php echo $project_id; ?>&tab=pricing">
                <i class="fa fa-dollar"></i> Pricing (<?php echo count($pricing); ?>)
            </a>
        </li>
        <li class="<?php echo $active_tab === 'team' ? 'active' : ''; ?>">
            <a href="?id=<?php echo $project_id; ?>&tab=team">
                <i class="fa fa-users"></i> Team (<?php echo count($members); ?>)
            </a>
        </li>
    </ul>
    
    <div class="tab-content">
        <?php if ($active_tab === 'overview'): ?>
        <div class="row">
            <div class="col-md-8">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-info-circle"></i> Project Information</h3>
                    </div>
                    <div class="panel-body">
                        <table class="table info-table">
                            <tr>
                                <th>Client:</th>
                                <td><?php echo htmlspecialchars($project['client_name'] ?: 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Description:</th>
                                <td><?php echo nl2br(htmlspecialchars($project['description'])); ?></td>
                            </tr>
                            <tr>
                                <th>Start Date:</th>
                                <td><?php echo $project['start_date'] ? date('M d, Y', strtotime($project['start_date'])) : 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <th>End Date:</th>
                                <td><?php echo $project['end_date'] ? date('M d, Y', strtotime($project['end_date'])) : 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <th>Budget:</th>
                                <td><strong>$<?php echo number_format($project['budget'], 2); ?></strong></td>
                            </tr>
                            <tr>
                                <th>Created By:</th>
                                <td><?php echo htmlspecialchars($project['creator_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Created At:</th>
                                <td><?php echo date('M d, Y H:i', strtotime($project['created_at'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-bar-chart"></i> Project Statistics</h3>
                    </div>
                    <div class="panel-body">
                        <h4>Tasks</h4>
                        <?php foreach ($stats['tasks'] as $task_stat): ?>
                        <div class="row stat-row">
                            <div class="col-xs-8">
                                <?php echo ucfirst(str_replace('_', ' ', $task_stat['status'])); ?>
                            </div>
                            <div class="col-xs-4 text-right">
                                <strong><?php echo $task_stat['total']; ?></strong>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <hr>
                        
                        <div class="row stat-row">
                            <div class="col-xs-8">Requirements</div>
                            <div class="col-xs-4 text-right">
                                <strong><?php echo $stats['requirements']; ?></strong>
                            </div>
                        </div>
                        
                        <div class="row stat-row">
                            <div class="col-xs-8">Total Pricing</div>
                            <div class="col-xs-4 text-right">
                                <strong>$<?php echo number_format($stats['total_pricing'], 2); ?></strong>
                            </div>
                        </div>
                        
                        <div class="row stat-row">
                            <div class="col-xs-8">Team Members</div>
                            <div class="col-xs-4 text-right">
                                <strong><?php echo count($members); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php elseif ($active_tab === 'tasks'): ?>
        <div class="row">
            <div class="col-md-12">
                <?php if ($auth->isManager()): ?>
                <a href="task-create.php?project_id=<?php echo $project_id; ?>" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add Task
                </a>
                <br><br>
                <?php endif; ?>
                
                <?php if (empty($tasks)): ?>
                <div class="alert alert-info">No tasks found.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Task Name</th>
                                <th>Assigned To</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Due Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($task['task_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($task['assigned_name'] ?: 'Unassigned'); ?></td>
                                <td>
                                    <span class="badge-status badge-<?php echo $task['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-priority badge-<?php echo $task['priority']; ?>">
                                        <?php echo ucfirst($task['priority']); ?>
                                    </span>
                                </td>
                                <td><?php echo $task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : 'N/A'; ?></td>
                                <td>
                                    <a href="task-edit.php?id=<?php echo $task['id']; ?>" class="btn btn-xs btn-default">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <a href="task-delete.php?id=<?php echo $task['id']; ?>" class="btn btn-xs btn-danger delete-confirm">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php elseif ($active_tab === 'requirements'): ?>
        <div class="row">
            <div class="col-md-12">
                <?php if ($auth->isManager()): ?>
                <a href="requirement-create.php?project_id=<?php echo $project_id; ?>" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add Requirement
                </a>
                <br><br>
                <?php endif; ?>
                
                <?php if (empty($requirements)): ?>
                <div class="alert alert-info">No requirements found.</div>
                <?php else: ?>
                <?php foreach ($requirements as $req): ?>
                <div class="requirement-item">
                    <h4><?php echo htmlspecialchars($req['requirement_title']); ?></h4>
                    <p><?php echo nl2br(htmlspecialchars($req['description'])); ?></p>
                    <div>
                        <span class="label label-default"><?php echo ucfirst($req['type']); ?></span>
                        <span class="badge-priority badge-<?php echo $req['priority']; ?>">
                            <?php echo ucfirst($req['priority']); ?>
                        </span>
                        <span class="badge-status badge-<?php echo $req['status']; ?>">
                            <?php echo ucfirst($req['status']); ?>
                        </span>
                        <?php if ($auth->isManager()): ?>
                        <div class="pull-right">
                            <a href="requirement-edit.php?id=<?php echo $req['id']; ?>" class="btn btn-xs btn-default">
                                <i class="fa fa-edit"></i> Edit
                            </a>
                            <a href="requirement-delete.php?id=<?php echo $req['id']; ?>" class="btn btn-xs btn-danger delete-confirm">
                                <i class="fa fa-trash"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <?php elseif ($active_tab === 'pricing'): ?>
        <div class="row">
            <div class="col-md-12">
                <?php if ($auth->isManager()): ?>
                <a href="pricing-create.php?project_id=<?php echo $project_id; ?>" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add Pricing Item
                </a>
                <br><br>
                <?php endif; ?>
                
                <?php if (empty($pricing)): ?>
                <div class="alert alert-info">No pricing items found.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Category</th>
                                <th>Unit Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $grand_total = 0;
                            foreach ($pricing as $item): 
                                $grand_total += $item['total_price'];
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                    <?php if ($item['description']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($item['description']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['category'] ?: 'N/A'); ?></td>
                                <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td><strong>$<?php echo number_format($item['total_price'], 2); ?></strong></td>
                                <td>
                                    <?php if ($auth->isManager()): ?>
                                    <a href="pricing-edit.php?id=<?php echo $item['id']; ?>" class="btn btn-xs btn-default">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <a href="pricing-delete.php?id=<?php echo $item['id']; ?>" class="btn btn-xs btn-danger delete-confirm">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="info">
                                <td colspan="4" class="text-right"><strong>Grand Total:</strong></td>
                                <td colspan="2"><strong>$<?php echo number_format($grand_total, 2); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php elseif ($active_tab === 'team'): ?>
        <div class="row">
            <div class="col-md-12">
                <?php if ($auth->isManager()): ?>
                <a href="project-add-member.php?project_id=<?php echo $project_id; ?>" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add Member
                </a>
                <br><br>
                <?php endif; ?>
                
                <?php if (empty($members)): ?>
                <div class="alert alert-info">No team members found.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>User Role</th>
                                <th>Project Role</th>
                                <th>Assigned At</th>
                                <?php if ($auth->isManager()): ?>
                                <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $member): ?>
                            <tr>
                                <td>
                                    <div class="member-avatar">
                                        <?php echo strtoupper(substr($member['full_name'], 0, 1)); ?>
                                    </div>
                                    <strong><?php echo htmlspecialchars($member['full_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($member['email']); ?></td>
                                <td><?php echo ucfirst($member['user_role']); ?></td>
                                <td>
                                    <span class="label label-primary">
                                        <?php echo ucfirst($member['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($member['assigned_at'])); ?></td>
                                <?php if ($auth->isManager()): ?>
                                <td>
                                    <a href="project-remove-member.php?project_id=<?php echo $project_id; ?>&user_id=<?php echo $member['user_id']; ?>" class="btn btn-xs btn-danger delete-confirm">
                                        <i class="fa fa-remove"></i> Remove
                                    </a>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Smooth tab switching
        $('.nav-tabs a').on('click', function(e) {
            $('.tab-content').css({
                'opacity': '0',
                'transform': 'translateY(20px)'
            });
            setTimeout(function() {
                $('.tab-content').css({
                    'opacity': '1',
                    'transform': 'translateY(0)'
                });
            }, 100);
        });
        
        // Delete confirmation
        $('.delete-confirm').on('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
        
        // Staggered animation for requirement items
        $('.requirement-item').each(function(i) {
            $(this).css({
                'animation': `fadeInUp 0.4s ease ${i * 0.08}s both`
            });
        });
        
        // Staggered animation for table rows
        $('.table tbody tr').each(function(i) {
            $(this).css({
                'animation': `fadeInUp 0.3s ease ${i * 0.04}s both`
            });
        });
        
        // Badge hover effect
        $('.badge-status, .badge-priority').hover(
            function() {
                $(this).css('transform', 'translateY(-2px) scale(1.05)');
            },
            function() {
                $(this).css('transform', 'translateY(0) scale(1)');
            }
        );
        
        // Panel hover enhancement
        $('.panel').hover(
            function() {
                $(this).css('box-shadow', '0 8px 24px rgba(0, 0, 0, 0.12)');
            },
            function() {
                $(this).css('box-shadow', '0 1px 3px rgba(0, 0, 0, 0.05)');
            }
        );
    });
</script>

<?php require_once 'includes/footer.php'; ?>