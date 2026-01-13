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
    /* MODERN INTERACTIVE DESIGN - ULTRA FAST */
    
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
    
    .dashboard-container {
        padding: 24px;
        max-width: 1400px;
        margin: 0 auto;
        animation: fadeInUp 0.4s ease;
    }
    
    @keyframes fadeInUp {
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
        margin: 0;
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
        font-size: 16px;
        font-weight: 500;
        color: #64748b;
        margin-left: auto;
    }
    
    /* MODERN STAT CARDS */
    .stat-box {
        background: white;
        padding: 28px;
        border-radius: 16px;
        box-shadow: var(--shadow);
        margin-bottom: 24px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid var(--border);
        position: relative;
        overflow: hidden;
        cursor: pointer;
    }
    
    .stat-box::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.05));
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .stat-box:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-lg);
        border-color: var(--primary);
    }
    
    .stat-box:hover::before {
        opacity: 1;
    }
    
    .stat-box h3 {
        margin: 0 0 8px 0;
        font-size: 40px;
        font-weight: 800;
        color: var(--dark);
        position: relative;
    }
    
    .stat-box p {
        color: #64748b;
        margin: 0;
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        position: relative;
    }
    
    .stat-icon {
        font-size: 48px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        transition: transform 0.3s ease;
    }
    
    .stat-box:hover .stat-icon {
        transform: scale(1.1);
    }
    
    /* MODERN PANELS */
    .panel {
        background: white;
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        margin-bottom: 24px;
    }
    
    .panel:hover {
        box-shadow: var(--shadow-md);
    }
    
    .panel-heading {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        padding: 20px 28px;
        border: none;
    }
    
    .panel-title {
        font-size: 17px;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        color: white;
    }
    
    .panel-body {
        padding: 28px;
    }
    
    /* MODERN PROJECT CARDS */
    .project-card {
        background: linear-gradient(135deg, #fafafa 0%, white 100%);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 16px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    
    .project-card::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(180deg, var(--primary), var(--secondary));
        transform: scaleY(0);
        transition: transform 0.3s ease;
    }
    
    .project-card:hover {
        transform: translateX(4px);
        box-shadow: var(--shadow-md);
        background: white;
        border-color: var(--primary);
    }
    
    .project-card:hover::after {
        transform: scaleY(1);
    }
    
    .project-card h4 {
        margin: 0 0 12px 0;
        color: var(--dark);
        font-weight: 700;
        font-size: 17px;
    }
    
    .project-card h4 a {
        color: var(--dark);
        text-decoration: none;
        transition: color 0.2s ease;
    }
    
    .project-card h4 a:hover {
        color: var(--primary);
    }
    
    .project-meta {
        color: #64748b;
        font-size: 13px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }
    
    .project-meta small {
        display: block;
        margin-top: 8px;
        width: 100%;
    }
    
    /* MODERN BADGES */
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
    }
    
    .badge-status::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
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
    
    /* MODERN TASK ITEMS */
    .task-item {
        padding: 18px 20px;
        background: white;
        border: 1px solid var(--border);
        border-left: 3px solid var(--primary);
        margin-bottom: 12px;
        border-radius: 10px;
        transition: all 0.3s ease;
    }
    
    .task-item:hover {
        transform: translateX(4px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary);
    }
    
    .task-item.critical { border-left-color: var(--danger); }
    .task-item.high { border-left-color: #f97316; }
    .task-item.medium { border-left-color: var(--warning); }
    .task-item.low { border-left-color: var(--success); }
    
    .task-item strong {
        color: var(--dark);
        font-size: 15px;
        font-weight: 700;
    }
    
    /* MODERN BUTTONS */
    .btn-default {
        background: white;
        color: var(--primary);
        border: 2px solid var(--primary);
        border-radius: 10px;
        padding: 12px 24px;
        font-weight: 700;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.8px;
    }
    
    .btn-default:hover {
        background: var(--primary);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(99, 102, 241, 0.3);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border: none;
        border-radius: 10px;
        padding: 12px 28px;
        font-weight: 700;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.8px;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        color: white;
    }
    
    .text-center h2 {
        color: var(--dark);
        font-weight: 800;
        font-size: 48px;
        margin: 10px 0;
    }
    
    .text-muted {
        color: #64748b;
        font-weight: 500;
    }
    
    /* SMOOTH SCROLLBAR */
    ::-webkit-scrollbar {
        width: 10px;
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
    
    /* RESPONSIVE */
    @media (max-width: 1200px) {
        .dashboard-container { padding: 20px; }
        .page-header h1 { font-size: 28px; }
    }
    
    @media (max-width: 992px) {
        .stat-box { margin-bottom: 20px; }
    }
    
    @media (max-width: 768px) {
        .dashboard-container { padding: 16px; }
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
        .page-header small {
            margin-left: 0;
            font-size: 14px;
        }
        .stat-box {
            padding: 20px;
        }
        .stat-box h3 {
            font-size: 32px;
        }
        .panel-body {
            padding: 20px;
        }
    }
    
    @media (max-width: 480px) {
        .dashboard-container { padding: 12px; }
        .page-header { padding: 20px 16px; }
        .page-header h1 { font-size: 22px; }
        .stat-box { padding: 18px; }
        .stat-box h3 { font-size: 28px; }
        .stat-icon { font-size: 38px; }
        .project-card, .task-item { padding: 16px; }
    }
</style>

<div class="dashboard-container container-fluid">
    <div class="page-header">
        <h1>
            <i class="fa fa-dashboard"></i> 
            Dashboard 
            <small>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</small>
        </h1>
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
            <div class="panel panel-default">
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
            <div class="panel panel-default">
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
            <div class="panel panel-default">
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
        // Smooth Counter Animation
        $('.counter').each(function() {
            const $this = $(this);
            const countTo = parseInt($this.attr('data-target'));
            
            $({ countNum: 0 }).animate({
                countNum: countTo
            }, {
                duration: 1200,
                easing: 'swing',
                step: function() {
                    $this.text(Math.floor(this.countNum));
                },
                complete: function() {
                    $this.text(this.countNum);
                }
            });
        });
        
        // Add staggered animation to cards
        $('.project-card, .task-item').each(function(i) {
            $(this).css({
                'animation': `fadeInUp 0.4s ease ${i * 0.05}s both`
            });
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>