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

<div class="container-fluid">
    <div class="page-header">
        <h1>Dashboard <small>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?></small></h1>
    </div>
    
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="stat-box">
                <div class="row">
                    <div class="col-xs-8">
                        <h3><?php echo $total_projects; ?></h3>
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
                        <h3><?php echo $active_projects; ?></h3>
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
                        <h3><?php echo count($pending_tasks); ?></h3>
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
                        <h3><?php echo count($overdue_tasks); ?></h3>
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
                            <h2><?php echo $total_users; ?></h2>
                            <p class="text-muted">Active Users</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h2><?php echo $total_projects; ?></h2>
                            <p class="text-muted">Total Projects</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h2><?php echo $total_tasks; ?></h2>
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

<?php require_once 'includes/footer.php'; ?>
