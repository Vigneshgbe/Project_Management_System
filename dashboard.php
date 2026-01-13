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
        background: transparent !important;
        min-height: calc(100vh - 80px) !important;
        padding: 20px !important;
        margin: 0 !important;
    }
    
    .page-header {
        background: rgba(255, 255, 255, 0.98) !important;
        color: #2d3748 !important;
        padding: 25px 30px !important;
        border-radius: 12px !important;
        margin-bottom: 25px !important;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08) !important;
        border-left: 4px solid #FF9800 !important;
    }
    
    .page-header h1 {
        margin: 0 !important;
        font-weight: 700 !important;
        font-size: 28px !important;
        color: #FF9800 !important;
    }
    
    .page-header small {
        color: #718096 !important;
        font-size: 15px !important;
        font-weight: 500 !important;
    }
    
    .stat-box {
        background: white !important;
        padding: 25px !important;
        border-radius: 12px !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06) !important;
        margin-bottom: 20px !important;
        transition: transform 0.2s, box-shadow 0.2s !important;
        border-left: 4px solid #FF9800 !important;
    }
    
    .stat-box:hover {
        transform: translateY(-4px) !important;
        box-shadow: 0 4px 16px rgba(255, 152, 0, 0.15) !important;
    }
    
    .stat-box h3 {
        margin: 0 0 8px 0 !important;
        font-size: 36px !important;
        font-weight: 700 !important;
        color: #FF9800 !important;
    }
    
    .stat-box p {
        color: #718096 !important;
        margin: 0 !important;
        font-weight: 600 !important;
        font-size: 13px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }
    
    .stat-icon {
        font-size: 42px !important;
        color: #FFC107 !important;
        opacity: 0.8 !important;
    }
    
    .panel {
        border: none !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06) !important;
        border-radius: 12px !important;
        overflow: hidden !important;
        background: white !important;
        margin-bottom: 20px !important;
    }
    
    .panel-heading {
        background: linear-gradient(135deg, #FF9800 0%, #FFC107 100%) !important;
        color: white !important;
        padding: 18px 22px !important;
        border: none !important;
        font-weight: 600 !important;
    }
    
    .panel-title {
        font-size: 17px !important;
        font-weight: 600 !important;
        margin: 0 !important;
    }
    
    .panel-body {
        padding: 22px !important;
        background: white !important;
    }
    
    .project-card {
        background: #fafafa !important;
        border: 1px solid #e5e7eb !important;
        border-radius: 10px !important;
        padding: 18px !important;
        margin-bottom: 15px !important;
        transition: all 0.2s !important;
        border-left: 3px solid #FF9800 !important;
    }
    
    .project-card:hover {
        background: white !important;
        box-shadow: 0 3px 12px rgba(255, 152, 0, 0.12) !important;
        transform: translateX(4px) !important;
    }
    
    .project-card h4 {
        margin: 0 0 10px 0 !important;
        color: #2d3748 !important;
        font-weight: 600 !important;
        font-size: 16px !important;
    }
    
    .project-card h4 a {
        color: #2d3748 !important;
        text-decoration: none !important;
    }
    
    .project-card h4 a:hover {
        color: #FF9800 !important;
    }
    
    .project-meta {
        color: #718096 !important;
        font-size: 13px !important;
    }
    
    .badge-status {
        padding: 5px 12px !important;
        border-radius: 12px !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.3px !important;
        display: inline-block !important;
        margin-right: 6px !important;
    }
    
    .badge-planning { 
        background: #FFC107 !important;
        color: #fff !important;
    }
    
    .badge-in_progress { 
        background: #2196F3 !important;
        color: #fff !important;
    }
    
    .badge-on_hold { 
        background: #f44336 !important;
        color: #fff !important;
    }
    
    .badge-completed { 
        background: #4CAF50 !important;
        color: #fff !important;
    }
    
    .badge-cancelled { 
        background: #9e9e9e !important;
        color: #fff !important;
    }
    
    .badge-priority {
        padding: 5px 12px !important;
        border-radius: 12px !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        margin-right: 6px !important;
    }
    
    .badge-low { 
        background: #4CAF50 !important;
        color: #fff !important;
    }
    
    .badge-medium { 
        background: #FFC107 !important;
        color: #fff !important;
    }
    
    .badge-high { 
        background: #FF9800 !important;
        color: #fff !important;
    }
    
    .badge-critical { 
        background: #f44336 !important;
        color: #fff !important;
    }
    
    .task-item {
        padding: 15px 18px !important;
        background: #fafafa !important;
        border-left: 3px solid #FFC107 !important;
        margin-bottom: 12px !important;
        border-radius: 8px !important;
        transition: all 0.2s !important;
    }
    
    .task-item:hover {
        background: white !important;
        box-shadow: 0 2px 8px rgba(255, 193, 7, 0.15) !important;
        transform: translateX(4px) !important;
    }
    
    .task-item.critical {
        border-left-color: #f44336 !important;
    }
    
    .task-item.high {
        border-left-color: #FF9800 !important;
    }
    
    .task-item.medium {
        border-left-color: #FFC107 !important;
    }
    
    .task-item.low {
        border-left-color: #4CAF50 !important;
    }
    
    .task-item strong {
        color: #2d3748 !important;
        font-size: 14px !important;
    }
    
    .btn-default {
        background: white !important;
        color: #FF9800 !important;
        border: 2px solid #FF9800 !important;
        border-radius: 8px !important;
        padding: 10px 20px !important;
        font-weight: 600 !important;
        transition: all 0.2s !important;
        text-transform: uppercase !important;
        font-size: 12px !important;
        letter-spacing: 0.3px !important;
    }
    
    .btn-default:hover {
        background: #FF9800 !important;
        color: white !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3) !important;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #FF9800 0%, #FFC107 100%) !important;
        border: none !important;
        border-radius: 8px !important;
        padding: 10px 20px !important;
        font-weight: 600 !important;
        transition: all 0.2s !important;
        text-transform: uppercase !important;
        font-size: 12px !important;
        letter-spacing: 0.3px !important;
        color: white !important;
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #F57C00 0%, #FF9800 100%) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3) !important;
        color: white !important;
    }
    
    .text-center h2 {
        color: #FF9800 !important;
        font-weight: 700 !important;
        font-size: 36px !important;
        margin: 10px 0 !important;
    }
    
    .text-muted {
        color: #718096 !important;
        font-weight: 500 !important;
    }
    
    @media (max-width: 1200px) {
        .dashboard-container { padding: 15px !important; }
        .page-header { padding: 22px 25px !important; }
        .page-header h1 { font-size: 26px !important; }
    }
    
    @media (max-width: 992px) {
        .stat-box { margin-bottom: 15px !important; }
        .panel-body { padding: 18px !important; }
    }
    
    @media (max-width: 768px) {
        .dashboard-container { padding: 12px !important; }
        .page-header { padding: 18px 20px !important; margin-bottom: 18px !important; }
        .page-header h1 { font-size: 22px !important; }
        .page-header small { font-size: 14px !important; }
        .stat-box { padding: 18px !important; margin-bottom: 12px !important; }
        .stat-box h3 { font-size: 30px !important; }
        .project-card, .task-item { padding: 14px !important; }
    }
    
    @media (max-width: 480px) {
        .dashboard-container { padding: 10px !important; }
        .stat-box { padding: 15px !important; }
        .stat-icon { font-size: 36px !important; }
        .panel-heading { padding: 15px 18px !important; }
        .panel-body { padding: 15px !important; }
        .page-header h1 { font-size: 20px !important; }
        .stat-box h3 { font-size: 28px !important; }
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
        
        $('html').css('scroll-behavior', 'smooth');
    });
</script>

<?php require_once 'includes/footer.php'; ?>