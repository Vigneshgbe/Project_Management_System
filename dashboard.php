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
    .dashboard-container {
        background: transparent;
        min-height: calc(100vh - 80px);
        padding: 20px;
    }
    
    .page-header {
        background: white;
        padding: 30px;
        border-radius: 12px;
        margin-bottom: 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    
    .page-header h1 {
        margin: 0;
        font-weight: 700;
        font-size: 28px;
        color: #667eea;
    }
    
    .page-header small {
        color: #64748b;
        font-size: 15px;
        font-weight: 500;
    }
    
    .stat-box {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin-bottom: 20px;
        border-left: 4px solid #667eea;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .stat-box:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 12px rgba(102,126,234,0.2);
    }
    
    .stat-box h3 {
        margin: 0 0 5px 0;
        font-size: 36px;
        font-weight: 700;
        color: #667eea;
    }
    
    .stat-box p {
        color: #64748b;
        margin: 0;
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
    }
    
    .stat-icon {
        font-size: 40px;
        color: #667eea;
        opacity: 0.8;
    }
    
    .panel {
        border: none;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border-radius: 12px;
        overflow: hidden;
        background: white;
        margin-bottom: 20px;
    }
    
    .panel-heading {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 18px 20px;
        border: none;
    }
    
    .panel-title {
        font-size: 16px;
        font-weight: 700;
        margin: 0;
    }
    
    .panel-body {
        padding: 20px;
    }
    
    .project-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 18px;
        margin-bottom: 15px;
        transition: all 0.2s;
    }
    
    .project-card:hover {
        border-color: #667eea;
        box-shadow: 0 2px 8px rgba(102,126,234,0.15);
    }
    
    .project-card h4 {
        margin: 0 0 10px 0;
        color: #1e293b;
        font-weight: 700;
        font-size: 16px;
    }
    
    .project-card h4 a {
        color: #1e293b;
        text-decoration: none;
    }
    
    .project-card h4 a:hover {
        color: #667eea;
    }
    
    .project-meta {
        color: #64748b;
        font-size: 13px;
    }
    
    .badge-status {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        display: inline-block;
        margin-right: 6px;
    }
    
    .badge-planning { background: #fbbf24; color: white; }
    .badge-in_progress { background: #3b82f6; color: white; }
    .badge-on_hold { background: #ef4444; color: white; }
    .badge-completed { background: #10b981; color: white; }
    .badge-cancelled { background: #6b7280; color: white; }
    
    .badge-priority {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        margin-right: 6px;
    }
    
    .badge-low { background: #10b981; color: white; }
    .badge-medium { background: #fbbf24; color: white; }
    .badge-high { background: #f97316; color: white; }
    .badge-critical { background: #ef4444; color: white; }
    
    .task-item {
        padding: 15px;
        background: white;
        border-left: 4px solid #667eea;
        margin-bottom: 12px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        transition: all 0.2s;
    }
    
    .task-item:hover {
        box-shadow: 0 2px 8px rgba(102,126,234,0.15);
    }
    
    .task-item.critical { border-left-color: #ef4444; }
    .task-item.high { border-left-color: #f97316; }
    .task-item.medium { border-left-color: #fbbf24; }
    .task-item.low { border-left-color: #10b981; }
    
    .task-item strong {
        color: #1e293b;
        font-size: 14px;
    }
    
    .btn-default {
        background: white;
        color: #667eea;
        border: 2px solid #667eea;
        border-radius: 8px;
        padding: 10px;
        font-weight: 700;
        transition: all 0.2s;
        text-transform: uppercase;
        font-size: 12px;
    }
    
    .btn-default:hover {
        background: #667eea;
        color: white;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 700;
        transition: all 0.2s;
        text-transform: uppercase;
        font-size: 12px;
        color: white;
    }
    
    .btn-primary:hover {
        opacity: 0.9;
        transform: translateY(-2px);
        color: white;
    }
    
    .text-center h2 {
        color: #667eea;
        font-weight: 700;
        font-size: 36px;
        margin: 10px 0;
    }
    
    .text-muted {
        color: #64748b;
        font-weight: 500;
    }
    
    @media (max-width: 768px) {
        .dashboard-container { padding: 10px; }
        .page-header { padding: 20px; margin-bottom: 20px; }
        .page-header h1 { font-size: 22px; }
        .stat-box { padding: 20px; margin-bottom: 15px; }
        .stat-box h3 { font-size: 28px; }
        .project-card, .task-item { padding: 15px; }
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