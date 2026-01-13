<?php
$page_title = 'Dashboard';
require_once 'includes/header.php';
require_once 'components/project.php';
require_once 'components/task.php';

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

<style>
    /* PERFORMANCE OPTIMIZED - LIGHT PROFESSIONAL THEME */
    
    .dashboard-container {
        padding: 20px;
        max-width: 1400px;
        margin: 0 auto;
        animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    /* PAGE HEADER */
    .page-header {
        background: white;
        padding: 28px 32px;
        border-radius: 12px;
        margin-bottom: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        border: 1px solid #e5e7eb;
    }
    
    .page-header h1 {
        margin: 0;
        font-weight: 700;
        font-size: 28px;
        color: #1f2937;
    }
    
    .page-header h1 i {
        color: #3b82f6;
        margin-right: 8px;
    }
    
    .page-header small {
        font-size: 15px;
        font-weight: 500;
        color: #6b7280;
        margin-left: 8px;
    }
    
    /* STAT BOXES */
    .stat-box {
        background: white;
        padding: 24px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        margin-bottom: 20px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: 1px solid #e5e7eb;
        border-left: 4px solid #3b82f6;
    }
    
    .stat-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
    }
    
    .stat-box h3 {
        margin: 0 0 6px 0;
        font-size: 36px;
        font-weight: 700;
        color: #1f2937;
    }
    
    .stat-box p {
        color: #6b7280;
        margin: 0;
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .stat-icon {
        font-size: 42px;
        color: #3b82f6;
        opacity: 0.9;
    }
    
    /* PANELS */
    .panel {
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        border-radius: 12px;
        overflow: hidden;
        transition: box-shadow 0.2s ease;
        background: white;
        margin-bottom: 24px;
    }
    
    .panel:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    
    .panel-heading {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        padding: 18px 24px;
        border: none;
        font-weight: 600;
    }
    
    .panel-title {
        font-size: 16px;
        font-weight: 600;
        margin: 0;
    }
    
    .panel-title i {
        margin-right: 8px;
    }
    
    .panel-body {
        padding: 24px;
        background: white;
    }
    
    /* PROJECT CARDS */
    .project-card {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 18px;
        margin-bottom: 16px;
        transition: all 0.2s ease;
    }
    
    .project-card:hover {
        background: white;
        border-color: #3b82f6;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
    }
    
    .project-card h4 {
        margin: 0 0 10px 0;
        color: #1f2937;
        font-weight: 600;
        font-size: 16px;
    }
    
    .project-card h4 a {
        color: #1f2937;
        text-decoration: none;
        transition: color 0.2s ease;
    }
    
    .project-card h4 a:hover {
        color: #3b82f6;
    }
    
    .project-meta {
        color: #6b7280;
        font-size: 13px;
    }
    
    .project-meta small {
        display: inline-block;
        margin-top: 8px;
    }
    
    /* BADGES */
    .badge-status {
        padding: 5px 12px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        display: inline-block;
        margin-right: 6px;
    }
    
    .badge-planning { 
        background: #fef3c7;
        color: #92400e;
    }
    
    .badge-in_progress { 
        background: #dbeafe;
        color: #1e40af;
    }
    
    .badge-on_hold { 
        background: #fee2e2;
        color: #991b1b;
    }
    
    .badge-completed { 
        background: #d1fae5;
        color: #065f46;
    }
    
    .badge-cancelled { 
        background: #e5e7eb;
        color: #374151;
    }
    
    .badge-priority {
        padding: 5px 12px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-right: 6px;
    }
    
    .badge-low { 
        background: #d1fae5;
        color: #065f46;
    }
    
    .badge-medium { 
        background: #fef3c7;
        color: #92400e;
    }
    
    .badge-high { 
        background: #fed7aa;
        color: #9a3412;
    }
    
    .badge-critical { 
        background: #fee2e2;
        color: #991b1b;
    }
    
    /* TASK ITEMS */
    .task-item {
        padding: 16px;
        background: #f9fafb;
        border-left: 3px solid #3b82f6;
        margin-bottom: 12px;
        border-radius: 8px;
        transition: all 0.2s ease;
    }
    
    .task-item:hover {
        background: white;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
    }
    
    .task-item.critical {
        border-left-color: #ef4444;
    }
    
    .task-item.high {
        border-left-color: #f97316;
    }
    
    .task-item.medium {
        border-left-color: #eab308;
    }
    
    .task-item.low {
        border-left-color: #10b981;
    }
    
    .task-item strong {
        color: #1f2937;
        font-size: 14px;
        font-weight: 600;
    }
    
    /* BUTTONS */
    .btn-default {
        background: white;
        color: #3b82f6;
        border: 1px solid #3b82f6;
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 600;
        transition: all 0.2s ease;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.5px;
    }
    
    .btn-default:hover {
        background: #3b82f6;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(59, 130, 246, 0.2);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 600;
        transition: all 0.2s ease;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 6px rgba(59, 130, 246, 0.2);
        color: white;
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3);
        color: white;
    }
    
    .text-center h2 {
        color: #1f2937;
        font-weight: 700;
        font-size: 40px;
        margin: 8px 0;
    }
    
    .text-muted {
        color: #6b7280;
        font-weight: 500;
    }
    
    /* RESPONSIVE */
    @media (max-width: 1200px) {
        .dashboard-container {
            padding: 16px;
        }
    }
    
    @media (max-width: 992px) {
        .stat-box {
            margin-bottom: 16px;
        }
    }
    
    @media (max-width: 768px) {
        .dashboard-container {
            padding: 12px;
        }
        .page-header {
            padding: 20px;
            margin-bottom: 16px;
        }
        .page-header h1 {
            font-size: 22px;
        }
        .page-header small {
            font-size: 14px;
            display: block;
            margin-left: 0;
            margin-top: 4px;
        }
        .stat-box {
            padding: 18px;
        }
        .stat-box h3 {
            font-size: 30px;
        }
        .panel-body {
            padding: 18px;
        }
    }
    
    @media (max-width: 480px) {
        .dashboard-container {
            padding: 10px;
        }
        .page-header {
            padding: 16px;
        }
        .page-header h1 {
            font-size: 20px;
        }
        .stat-box {
            padding: 16px;
        }
        .stat-box h3 {
            font-size: 26px;
        }
        .stat-icon {
            font-size: 32px;
        }
        .project-card, .task-item {
            padding: 14px;
        }
    }
</style>

<div class="dashboard-container container-fluid">
    <div class="page-header">
        <h1><i class="fa fa-dashboard"></i> Dashboard <small>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</small></h1>
    </div>
    
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="stat-box">
                <div class="row">
                    <div class="col-xs-8">
                        <h3 class="counter" data-target="<?php echo $total_projects; ?>">0</h3>
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
                        <h3 class="counter" data-target="<?php echo $active_projects; ?>">0</h3>
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
                        <h3 class="counter" data-target="<?php echo count($pending_tasks); ?>">0</h3>
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
                        <h3 class="counter" data-target="<?php echo count($overdue_tasks); ?>">0</h3>
                        <p>Overdue Tasks</p>
                    </div>
                    <div class="col-xs-4 text-right">
                        <i class="fa fa-exclamation-triangle stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
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
    
    <?php if ($auth->isAdmin()): ?>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default panel-custom">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-bar-chart"></i> System Overview</h3>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <h2 class="counter" data-target="<?php echo $total_users; ?>">0</h2>
                            <p class="text-muted">Active Users</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h2 class="counter" data-target="<?php echo $total_projects; ?>">0</h2>
                            <p class="text-muted">Total Projects</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h2 class="counter" data-target="<?php echo $total_tasks; ?>">0</h2>
                            <p class="text-muted">Total Tasks</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <a href="admin/analytics.php" class="btn btn-primary">
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

<script>
    $(document).ready(function() {
        // Optimized Counter Animation
        $('.counter').each(function() {
            const $this = $(this);
            const countTo = parseInt($this.attr('data-target'));
            
            $({ countNum: 0 }).animate({
                countNum: countTo
            }, {
                duration: 1500,
                easing: 'swing',
                step: function() {
                    $this.text(Math.floor(this.countNum));
                },
                complete: function() {
                    $this.text(this.countNum);
                }
            });
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>