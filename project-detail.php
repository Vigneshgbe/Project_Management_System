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
    .project-detail-container {
        background: transparent !important;
        min-height: calc(100vh - 100px) !important;
        padding: 20px !important;
        margin: 0 !important;
        animation: fadeIn 0.5s ease !important;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .page-header {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        color: #1e293b !important;
        padding: 35px 40px !important;
        border-radius: 20px !important;
        margin-bottom: 25px !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        animation: slideDown 0.6s ease !important;
        position: relative !important;
        overflow: hidden !important;
    }
    
    .page-header::before {
        content: '' !important;
        position: absolute !important;
        top: -50% !important;
        right: -50% !important;
        width: 200% !important;
        height: 200% !important;
        background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%) !important;
        animation: rotate 20s linear infinite !important;
    }
    
    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .page-header h1 {
        margin: 0 !important;
        font-weight: 800 !important;
        font-size: 32px !important;
        position: relative !important;
        z-index: 1 !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
        margin-bottom: 8px !important;
    }
    
    .page-header h1 i {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }
    
    .page-header small {
        opacity: 0.7 !important;
        font-size: 16px !important;
        font-weight: 600 !important;
        color: #64748b !important;
        display: block !important;
        margin-top: 5px !important;
    }
    
    .page-header .row {
        align-items: center !important;
    }
    
    .badge-status {
        padding: 8px 16px !important;
        border-radius: 20px !important;
        font-size: 12px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 5px !important;
        margin-right: 10px !important;
        margin-bottom: 10px !important;
        box-shadow: 0 3px 12px rgba(0, 0, 0, 0.15) !important;
        transition: all 0.3s ease !important;
    }
    
    .badge-status:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 5px 18px rgba(0, 0, 0, 0.25) !important;
    }
    
    .badge-planning { 
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
        color: white !important;
    }
    
    .badge-in_progress { 
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
        color: white !important;
    }
    
    .badge-on_hold { 
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        color: white !important;
    }
    
    .badge-completed { 
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        color: white !important;
    }
    
    .badge-cancelled { 
        background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%) !important;
        color: white !important;
    }
    
    .badge-priority {
        padding: 8px 16px !important;
        border-radius: 20px !important;
        font-size: 12px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        margin-right: 10px !important;
        margin-bottom: 10px !important;
        display: inline-flex !important;
        align-items: center !important;
        box-shadow: 0 3px 12px rgba(0, 0, 0, 0.15) !important;
        transition: all 0.3s ease !important;
    }
    
    .badge-priority:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 5px 18px rgba(0, 0, 0, 0.25) !important;
    }
    
    .badge-low { 
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        color: white !important;
    }
    
    .badge-medium { 
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
        color: white !important;
    }
    
    .badge-high { 
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important;
        color: white !important;
    }
    
    .badge-critical { 
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        color: white !important;
        animation: pulse 2s infinite !important;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.8; transform: scale(1.05); }
    }
    
    .nav-tabs {
        border: none !important;
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        padding: 15px 20px !important;
        border-radius: 20px !important;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15) !important;
        margin-bottom: 25px !important;
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 10px !important;
    }
    
    .nav-tabs > li {
        margin-bottom: 0 !important;
        flex: 1 !important;
        min-width: 150px !important;
    }
    
    .nav-tabs > li > a {
        border: none !important;
        border-radius: 12px !important;
        padding: 14px 20px !important;
        font-weight: 600 !important;
        color: #64748b !important;
        transition: all 0.3s ease !important;
        text-align: center !important;
        background: transparent !important;
        position: relative !important;
        overflow: hidden !important;
    }
    
    .nav-tabs > li > a::before {
        content: '' !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 100% !important;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%) !important;
        opacity: 0 !important;
        transition: opacity 0.3s ease !important;
        z-index: 0 !important;
    }
    
    .nav-tabs > li > a i {
        margin-right: 6px !important;
        position: relative !important;
        z-index: 1 !important;
    }
    
    .nav-tabs > li > a:hover {
        background: transparent !important;
        color: #667eea !important;
        transform: translateY(-2px) !important;
    }
    
    .nav-tabs > li > a:hover::before {
        opacity: 1 !important;
    }
    
    .nav-tabs > li.active > a,
    .nav-tabs > li.active > a:hover,
    .nav-tabs > li.active > a:focus {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        border: none !important;
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3) !important;
        transform: translateY(-2px) !important;
        position: relative !important;
        z-index: 1 !important;
    }
    
    .nav-tabs > li.active > a::before {
        opacity: 0 !important;
    }
    
    .tab-content {
        animation: fadeInUp 0.5s ease !important;
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .panel {
        border: none !important;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15) !important;
        border-radius: 20px !important;
        overflow: hidden !important;
        transition: all 0.3s ease !important;
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        margin-bottom: 25px !important;
    }
    
    .panel:hover {
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2) !important;
        transform: translateY(-5px) !important;
    }
    
    .panel-heading {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        padding: 20px 25px !important;
        border: none !important;
        font-weight: 700 !important;
    }
    
    .panel-title {
        font-size: 18px !important;
        font-weight: 700 !important;
        margin: 0 !important;
    }
    
    .panel-body {
        padding: 30px 25px !important;
        background: white !important;
    }
    
    .panel-primary > .panel-heading {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        border: none !important;
    }
    
    .table {
        margin-bottom: 0 !important;
        background: white !important;
    }
    
    .table > thead > tr > th {
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%) !important;
        color: #1e293b !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        font-size: 12px !important;
        letter-spacing: 0.5px !important;
        padding: 16px !important;
        border-bottom: 2px solid #e2e8f0 !important;
    }
    
    .table > tbody > tr > td,
    .table > tbody > tr > th {
        padding: 16px !important;
        vertical-align: middle !important;
        border-top: 1px solid #e2e8f0 !important;
    }
    
    .table-bordered {
        border: none !important;
        border-radius: 12px !important;
        overflow: hidden !important;
    }
    
    .table-bordered > thead > tr > th,
    .table-bordered > tbody > tr > th,
    .table-bordered > tfoot > tr > th,
    .table-bordered > thead > tr > td,
    .table-bordered > tbody > tr > td,
    .table-bordered > tfoot > tr > td {
        border: 1px solid #e2e8f0 !important;
    }
    
    .table-hover > tbody > tr:hover {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%) !important;
        transform: translateX(5px) !important;
        transition: all 0.3s ease !important;
    }
    
    .requirement-item {
        background: white !important;
        padding: 25px !important;
        border-radius: 16px !important;
        margin-bottom: 20px !important;
        box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1) !important;
        border-left: 5px solid #667eea !important;
        transition: all 0.3s ease !important;
        position: relative !important;
        overflow: hidden !important;
    }
    
    .requirement-item::before {
        content: '' !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 5px !important;
        height: 100% !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        transition: width 0.3s ease !important;
    }
    
    .requirement-item:hover {
        transform: translateX(8px) !important;
        box-shadow: 0 8px 30px rgba(102, 126, 234, 0.2) !important;
    }
    
    .requirement-item:hover::before {
        width: 100% !important;
        opacity: 0.05 !important;
    }
    
    .requirement-item h4 {
        margin-top: 0 !important;
        margin-bottom: 12px !important;
        font-weight: 700 !important;
        color: #1e293b !important;
        font-size: 18px !important;
    }
    
    .requirement-item p {
        color: #64748b !important;
        margin-bottom: 15px !important;
        line-height: 1.6 !important;
    }
    
    .label {
        padding: 6px 12px !important;
        border-radius: 12px !important;
        font-weight: 600 !important;
        font-size: 11px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }
    
    .label-default {
        background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%) !important;
    }
    
    .label-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    }
    
    .member-avatar {
        display: inline-flex !important;
        width: 40px !important;
        height: 40px !important;
        border-radius: 50% !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        font-weight: 700 !important;
        align-items: center !important;
        justify-content: center !important;
        margin-right: 10px !important;
        box-shadow: 0 3px 12px rgba(102, 126, 234, 0.3) !important;
        transition: all 0.3s ease !important;
    }
    
    .member-avatar:hover {
        transform: scale(1.1) rotate(5deg) !important;
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4) !important;
    }
    
    .btn-default {
        background: white !important;
        color: #667eea !important;
        border: 2px solid #667eea !important;
        border-radius: 12px !important;
        padding: 10px 20px !important;
        font-weight: 700 !important;
        transition: all 0.3s ease !important;
        text-transform: uppercase !important;
        font-size: 13px !important;
        letter-spacing: 0.5px !important;
    }
    
    .btn-default:hover,
    .btn-default:focus {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        border-color: transparent !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3) !important;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        border: none !important;
        border-radius: 12px !important;
        padding: 12px 24px !important;
        font-weight: 700 !important;
        transition: all 0.3s ease !important;
        text-transform: uppercase !important;
        font-size: 13px !important;
        letter-spacing: 0.5px !important;
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3) !important;
        color: white !important;
    }
    
    .btn-primary:hover,
    .btn-primary:focus {
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4) !important;
        color: white !important;
    }
    
    .btn-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        border: none !important;
        border-radius: 12px !important;
        padding: 10px 20px !important;
        font-weight: 700 !important;
        transition: all 0.3s ease !important;
        text-transform: uppercase !important;
        font-size: 13px !important;
        letter-spacing: 0.5px !important;
        box-shadow: 0 5px 20px rgba(239, 68, 68, 0.3) !important;
        color: white !important;
    }
    
    .btn-danger:hover,
    .btn-danger:focus {
        background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4) !important;
        color: white !important;
    }
    
    .btn-xs {
        padding: 6px 12px !important;
        font-size: 11px !important;
    }
    
    .alert {
        border: none !important;
        border-radius: 12px !important;
        padding: 20px !important;
        font-weight: 600 !important;
        box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1) !important;
    }
    
    .alert-info {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(37, 99, 235, 0.1) 100%) !important;
        color: #1e40af !important;
        border-left: 5px solid #3b82f6 !important;
    }
    
    .text-muted {
        color: #64748b !important;
    }
    
    .text-right {
        text-align: right !important;
    }
    
    .pull-right {
        float: right !important;
    }
    
    /* STAT ROWS */
    .panel-body .row {
        padding: 8px 0 !important;
        transition: all 0.3s ease !important;
        border-radius: 8px !important;
    }
    
    .panel-body .row:hover {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%) !important;
        padding-left: 10px !important;
    }
    
    .panel-body h4 {
        color: #1e293b !important;
        font-weight: 700 !important;
        margin-top: 0 !important;
        margin-bottom: 15px !important;
        font-size: 16px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }
    
    .panel-body hr {
        border-top: 2px solid #e2e8f0 !important;
        margin: 20px 0 !important;
    }
    
    /* RESPONSIVE */
    @media (max-width: 1200px) {
        .project-detail-container {
            padding: 15px !important;
        }
        .page-header {
            padding: 25px 30px !important;
        }
        .page-header h1 {
            font-size: 28px !important;
        }
    }
    
    @media (max-width: 992px) {
        .nav-tabs > li {
            min-width: 120px !important;
        }
        .panel-body {
            padding: 20px !important;
        }
    }
    
    @media (max-width: 768px) {
        .project-detail-container {
            padding: 10px !important;
        }
        .page-header {
            padding: 20px !important;
            margin-bottom: 20px !important;
        }
        .page-header h1 {
            font-size: 24px !important;
        }
        .page-header small {
            font-size: 14px !important;
        }
        .nav-tabs {
            padding: 10px !important;
        }
        .nav-tabs > li {
            flex: 1 1 100% !important;
            min-width: 100% !important;
        }
        .nav-tabs > li > a {
            padding: 12px 15px !important;
        }
        .panel-body {
            padding: 15px !important;
        }
        .requirement-item {
            padding: 20px !important;
        }
        .btn-default,
        .btn-primary,
        .btn-danger {
            margin-bottom: 10px !important;
            display: block !important;
            width: 100% !important;
        }
        .table-responsive {
            border: none !important;
        }
    }
    
    @media (max-width: 480px) {
        .project-detail-container {
            padding: 8px !important;
        }
        .page-header h1 {
            font-size: 20px !important;
        }
        .badge-status,
        .badge-priority {
            font-size: 10px !important;
            padding: 6px 12px !important;
        }
        .nav-tabs > li > a {
            font-size: 13px !important;
        }
        .table > thead > tr > th,
        .table > tbody > tr > td {
            padding: 10px !important;
            font-size: 13px !important;
        }
    }
    
    /* SMOOTH TRANSITIONS */
    * {
        transition: background 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease !important;
    }
    
    /* LOADING OPTIMIZATION */
    .panel,
    .requirement-item,
    .table {
        will-change: transform !important;
    }
</style>

<div class="project-detail-container container-fluid">
    <div class="page-header">
        <div class="row">
            <div class="col-md-8">
                <h1>
                    <i class="fa fa-folder"></i> <?php echo htmlspecialchars($project['project_name']); ?>
                    <small><?php echo htmlspecialchars($project['project_code']); ?></small>
                </h1>
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
    
    <div class="tab-content" style="padding-top: 20px;">
        <?php if ($active_tab === 'overview'): ?>
        <div class="row">
            <div class="col-md-8">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-info-circle"></i> Project Information</h3>
                    </div>
                    <div class="panel-body">
                        <table class="table">
                            <tr>
                                <th width="200">Client:</th>
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
                                <td>$<?php echo number_format($project['budget'], 2); ?></td>
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
                        <div class="row">
                            <div class="col-xs-8">
                                <?php echo ucfirst(str_replace('_', ' ', $task_stat['status'])); ?>
                            </div>
                            <div class="col-xs-4 text-right">
                                <strong><?php echo $task_stat['total']; ?></strong>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-xs-8">Requirements</div>
                            <div class="col-xs-4 text-right">
                                <strong><?php echo $stats['requirements']; ?></strong>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-xs-8">Total Pricing</div>
                            <div class="col-xs-4 text-right">
                                <strong>$<?php echo number_format($stats['total_pricing'], 2); ?></strong>
                            </div>
                        </div>
                        
                        <div class="row">
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
                                <td><?php echo htmlspecialchars($task['task_name']); ?></td>
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
                    <table class="table table-bordered">
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
                    <table class="table table-bordered">
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
                                    <?php echo htmlspecialchars($member['full_name']); ?>
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
        // Smooth tab switching with fade animation
        $('.nav-tabs a').on('click', function(e) {
            $('.tab-content').css('opacity', '0');
            setTimeout(function() {
                $('.tab-content').css('opacity', '1');
            }, 100);
        });
        
        // Delete confirmation
        $('.delete-confirm').on('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
        
        // Add entrance animation to requirement items
        $('.requirement-item').each(function(index) {
            $(this).css({
                'animation': `fadeInUp 0.5s ease ${index * 0.1}s both`
            });
        });
        
        // Add entrance animation to table rows
        $('.table tbody tr').each(function(index) {
            $(this).css({
                'animation': `fadeInUp 0.3s ease ${index * 0.05}s both`
            });
        });
        
        // Smooth scroll
        $('html').css('scroll-behavior', 'smooth');
    });
</script>

<?php require_once 'includes/footer.php'; ?>