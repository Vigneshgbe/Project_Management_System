<?php
$page_title = 'Dashboard';
require_once 'components/auth.php';
require_once 'components/project.php';
require_once 'components/task.php';

session_start();
$auth = new Auth();
$auth->checkAccess();

$project = new Project();
$task = new Task();

$user_id = $auth->getUserId();
$user_projects = $project->getUserProjects($user_id);
$user_tasks = $task->getByUser($user_id);

// Get statistics
$db = getDB();

if ($auth->isAdmin()) {
    $total_projects = $db->query("SELECT COUNT(*) as count FROM projects")->fetch_assoc()['count'];
    $total_users = $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'")->fetch_assoc()['count'];
    $active_projects = $db->query("SELECT COUNT(*) as count FROM projects WHERE status = 'in_progress'")->fetch_assoc()['count'];
    $total_tasks = $db->query("SELECT COUNT(*) as count FROM tasks")->fetch_assoc()['count'];
} else {
    $total_projects = count($user_projects);
    $active_projects = count(array_filter($user_projects, function($p) { return $p['status'] === 'in_progress'; }));
    $total_tasks = count($user_tasks);
    $completed_tasks = count(array_filter($user_tasks, function($t) { return $t['status'] === 'completed'; }));
}

$pending_tasks = array_filter($user_tasks, function($t) { 
    return in_array($t['status'], ['todo', 'in_progress']); 
});
$overdue_tasks = array_filter($user_tasks, function($t) {
    return $t['due_date'] && strtotime($t['due_date']) < time() && $t['status'] !== 'completed';
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Project Management System</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-attachment: fixed;
            min-height: 100vh;
            color: #1f2937;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 5px;
        }
        
        /* Modern Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border: none;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 0;
        }
        
        .navbar-brand {
            font-weight: 800;
            font-size: 22px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .navbar-nav > li > a {
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .navbar-nav > li > a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 3px;
            bottom: 0;
            left: 50%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }
        
        .navbar-nav > li > a:hover::after,
        .navbar-nav > li.active > a::after {
            width: 80%;
        }
        
        /* Main Container */
        .container-fluid {
            background: #f9fafb;
            min-height: calc(100vh - 50px);
            padding: 30px;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 35px 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
            border: none;
            animation: slideDown 0.6s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .page-header h1 {
            margin: 0;
            font-weight: 800;
            font-size: 32px;
            letter-spacing: -0.5px;
        }
        
        .page-header small {
            opacity: 0.95;
            font-size: 16px;
            font-weight: 500;
        }
        
        /* Stat Boxes */
        .stat-box {
            background: white;
            padding: 28px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 5px solid transparent;
            animation: scaleIn 0.5s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-box::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-radius: 50%;
            transform: translate(30%, -30%);
        }
        
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .stat-box:nth-child(1) { border-left-color: #667eea; animation-delay: 0.1s; }
        .stat-box:nth-child(2) { border-left-color: #764ba2; animation-delay: 0.2s; }
        .stat-box:nth-child(3) { border-left-color: #f59e0b; animation-delay: 0.3s; }
        .stat-box:nth-child(4) { border-left-color: #ef4444; animation-delay: 0.4s; }
        
        .stat-box:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
        }
        
        .stat-box h3 {
            margin: 0 0 5px 0;
            font-size: 38px;
            font-weight: 900;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-box p {
            color: #6b7280;
            margin: 0;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-icon {
            font-size: 42px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            transition: transform 0.3s ease;
        }
        
        .stat-box:hover .stat-icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        /* Panels */
        .panel {
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            background: white;
            margin-bottom: 25px;
        }
        
        .panel:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }
        
        .panel-heading {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 18px 25px;
            border: none;
            font-weight: 700;
        }
        
        .panel-title {
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .panel-body {
            padding: 25px;
        }
        
        /* Project Cards */
        .project-card {
            background: linear-gradient(135deg, #f9fafb 0%, #ffffff 100%);
            border: none;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border-left: 4px solid transparent;
        }
        
        .project-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }
        
        .project-card:hover {
            transform: translateX(8px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
            border-left-color: #667eea;
        }
        
        .project-card h4 {
            margin: 0 0 12px 0;
            font-weight: 700;
            font-size: 17px;
        }
        
        .project-card h4 a {
            color: #1f2937;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .project-card h4 a:hover {
            color: #667eea;
        }
        
        .project-meta {
            color: #6b7280;
            font-size: 13px;
        }
        
        .project-meta small {
            display: inline-block;
            margin-top: 8px;
        }
        
        /* Badges */
        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            margin-right: 6px;
        }
        
        .badge-planning { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: white; }
        .badge-in_progress { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; }
        .badge-on_hold { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }
        .badge-completed { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
        .badge-cancelled { background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); color: white; }
        .badge-todo { background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%); color: white; }
        .badge-review { background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%); color: white; }
        
        .badge-priority {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }
        
        .badge-low { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
        .badge-medium { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: white; }
        .badge-high { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: white; }
        .badge-critical { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; animation: pulse 2s infinite; }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        /* Task Items */
        .task-item {
            padding: 18px;
            background: white;
            border-left: 4px solid #667eea;
            margin-bottom: 12px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .task-item:hover {
            transform: translateX(6px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.15);
        }
        
        .task-item.critical {
            border-left-color: #ef4444;
            background: linear-gradient(90deg, rgba(239, 68, 68, 0.05) 0%, white 100%);
        }
        
        .task-item.high {
            border-left-color: #f97316;
            background: linear-gradient(90deg, rgba(249, 115, 22, 0.05) 0%, white 100%);
        }
        
        .task-item.medium {
            border-left-color: #fbbf24;
            background: linear-gradient(90deg, rgba(251, 191, 36, 0.05) 0%, white 100%);
        }
        
        .task-item.low {
            border-left-color: #10b981;
            background: linear-gradient(90deg, rgba(16, 185, 129, 0.05) 0%, white 100%);
        }
        
        .task-item strong {
            font-weight: 700;
            color: #1f2937;
        }
        
        /* Buttons */
        .btn {
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 13px;
        }
        
        .btn-default {
            background: white;
            color: #374151;
            border: 2px solid #e5e7eb;
        }
        
        .btn-default:hover {
            background: #f9fafb;
            border-color: #667eea;
            color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        /* System Overview */
        .system-stat {
            padding: 25px;
            text-align: center;
        }
        
        .system-stat h2 {
            font-size: 42px;
            font-weight: 900;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0 0 10px 0;
        }
        
        .system-stat p {
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
        }
        
        /* Empty State */
        .text-muted {
            color: #9ca3af;
            font-style: italic;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container-fluid {
                padding: 15px;
            }
            
            .page-header {
                padding: 25px 20px;
            }
            
            .page-header h1 {
                font-size: 24px;
            }
            
            .stat-box {
                margin-bottom: 15px;
            }
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-in {
            animation: fadeInUp 0.6s ease forwards;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-inverse navbar-fixed-top">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#mainNavbar">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="/dashboard.php"><i class="fa fa-briefcase"></i> PM System</a>
            </div>
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="nav navbar-nav">
                    <li class="active">
                        <a href="/dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a>
                    </li>
                    <li>
                        <a href="/projects.php"><i class="fa fa-folder"></i> Projects</a>
                    </li>
                    <li>
                        <a href="/tasks.php"><i class="fa fa-tasks"></i> My Tasks</a>
                    </li>
                    <?php if ($auth->isAdmin()): ?>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <i class="fa fa-cog"></i> Admin <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="/admin/users.php"><i class="fa fa-users"></i> Users</a></li>
                            <li><a href="/admin/analytics.php"><i class="fa fa-bar-chart"></i> Analytics</a></li>
                            <li><a href="/admin/activity.php"><i class="fa fa-history"></i> Activity Log</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <i class="fa fa-user"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?> <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="/profile.php"><i class="fa fa-user"></i> Profile</a></li>
                            <li class="divider"></li>
                            <li><a href="/logout.php"><i class="fa fa-sign-out"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div style="margin-top: 70px;"></div>

    <!-- Main Content -->
    <div class="container-fluid">
        <div class="page-header">
            <h1><i class="fa fa-dashboard"></i> Dashboard <small>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?></small></h1>
        </div>
        
        <!-- Stats Row -->
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="stat-box">
                    <div class="row">
                        <div class="col-xs-8">
                            <h3 class="counter"><?php echo $total_projects; ?></h3>
                            <p>Total Projects</p>
                        </div>
                        <div class="col-xs-4 text-right">
                            <i class="fa fa-folder stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="stat-box">
                    <div class="row">
                        <div class="col-xs-8">
                            <h3 class="counter"><?php echo $active_projects; ?></h3>
                            <p>Active Projects</p>
                        </div>
                        <div class="col-xs-4 text-right">
                            <i class="fa fa-briefcase stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="stat-box">
                    <div class="row">
                        <div class="col-xs-8">
                            <h3 class="counter"><?php echo count($pending_tasks); ?></h3>
                            <p>Pending Tasks</p>
                        </div>
                        <div class="col-xs-4 text-right">
                            <i class="fa fa-tasks stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="stat-box">
                    <div class="row">
                        <div class="col-xs-8">
                            <h3 class="counter"><?php echo count($overdue_tasks); ?></h3>
                            <p>Overdue Tasks</p>
                        </div>
                        <div class="col-xs-4 text-right">
                            <i class="fa fa-exclamation-triangle stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Projects and Tasks Row -->
        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-default panel-custom">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-folder"></i> Recent Projects</h3>
                    </div>
                    <div class="panel-body">
                        <?php if (empty($user_projects)): ?>
                            <p class="text-muted">No projects found.</p>
                        <?php else: ?>
                            <?php foreach (array_slice($user_projects, 0, 5) as $proj): ?>
                            <div class="project-card">
                                <h4>
                                    <a href="project-detail.php?id=<?php echo $proj['id']; ?>">
                                        <?php echo htmlspecialchars($proj['project_name']); ?>
                                    </a>
                                </h4>
                                <div class="project-meta">
                                    <span class="badge-status badge-<?php echo $proj['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $proj['status'])); ?>
                                    </span>
                                    <span class="badge-priority badge-<?php echo $proj['priority']; ?>">
                                        <?php echo ucfirst($proj['priority']); ?>
                                    </span>
                                    <br>
                                    <small>
                                        <i class="fa fa-code"></i> <?php echo htmlspecialchars($proj['project_code']); ?> | 
                                        <i class="fa fa-tasks"></i> <?php echo $proj['completed_tasks']; ?>/<?php echo $proj['task_count']; ?> tasks
                                    </small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <a href="projects.php" class="btn btn-default btn-block">View All Projects</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="panel panel-default panel-custom">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-tasks"></i> My Tasks</h3>
                    </div>
                    <div class="panel-body">
                        <?php if (empty($user_tasks)): ?>
                            <p class="text-muted">No tasks assigned.</p>
                        <?php else: ?>
                            <?php foreach (array_slice($user_tasks, 0, 8) as $t): ?>
                            <div class="task-item <?php echo $t['priority']; ?>">
                                <div class="row">
                                    <div class="col-xs-8">
                                        <strong><?php echo htmlspecialchars($t['task_name']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($t['project_name']); ?>
                                        </small>
                                    </div>
                                    <div class="col-xs-4 text-right">
                                        <span class="badge-status badge-<?php echo $t['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $t['status'])); ?>
                                        </span>
                                        <?php if ($t['due_date']): ?>
                                        <br><small><?php echo date('M d', strtotime($t['due_date'])); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <a href="tasks.php" class="btn btn-default btn-block">View All Tasks</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Admin System Overview -->
        <?php if ($auth->isAdmin()): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default panel-custom">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-bar-chart"></i> System Overview</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-3 system-stat">
                                <h2 class="counter"><?php echo $total_users; ?></h2>
                                <p>Active Users</p>
                            </div>
                            <div class="col-md-3 system-stat">
                                <h2 class="counter"><?php echo $total_projects; ?></h2>
                                <p>Total Projects</p>
                            </div>
                            <div class="col-md-3 system-stat">
                                <h2 class="counter"><?php echo $total_tasks; ?></h2>
                                <p>Total Tasks</p>
                            </div>
                            <div class="col-md-3 system-stat">
                                <a href="admin/analytics.php" class="btn btn-primary btn-lg">
                                    <i class="fa fa-bar-chart"></i> View Analytics
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Counter Animation
            $('.counter').each(function() {
                const $this = $(this);
                const countTo = parseInt($this.text());
                
                if (!isNaN(countTo)) {
                    $({ countNum: 0 }).animate({
                        countNum: countTo
                    }, {
                        duration: 2000,
                        easing: 'swing',
                        step: function() {
                            $this.text(Math.floor(this.countNum));
                        },
                        complete: function() {
                            $this.text(this.countNum);
                        }
                    });
                }
            });
            
            // Intersection Observer for scroll animations
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('animate-in');
                        }
                    });
                }, {
                    threshold: 0.1
                });
                
                document.querySelectorAll('.project-card, .task-item, .panel').forEach(el => {
                    observer.observe(el);
                });
            }
            
            // Smooth scroll
            $('a[href^="#"]').on('click', function(e) {
                const target = $(this.getAttribute('href'));
                if(target.length) {
                    e.preventDefault();
                    $('html, body').stop().animate({
                        scrollTop: target.offset().top - 80
                    }, 800);
                }
            });
        });
    </script>
</body>
</html>