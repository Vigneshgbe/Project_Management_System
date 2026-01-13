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
        margin-bottom: 35px !important;
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
    }
    
    .page-header h1 i {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }
    
    .page-header small {
        opacity: 0.8 !important;
        font-size: 16px !important;
        font-weight: 500 !important;
        color: #64748b !important;
    }
    
    .stat-box {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        padding: 30px !important;
        border-radius: 20px !important;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15) !important;
        margin-bottom: 25px !important;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        position: relative !important;
        overflow: hidden !important;
        animation: scaleIn 0.5s ease !important;
    }
    
    @keyframes scaleIn {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
    }
    
    .stat-box::before {
        content: '' !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 5px !important;
        height: 100% !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        transition: width 0.3s ease !important;
    }
    
    .stat-box:hover::before {
        width: 100% !important;
        opacity: 0.05 !important;
    }
    
    .stat-box:hover {
        transform: translateY(-8px) !important;
        box-shadow: 0 15px 45px rgba(102, 126, 234, 0.3) !important;
    }
    
    .stat-box h3 {
        margin-top: 0 !important;
        font-size: 42px !important;
        font-weight: 800 !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
        margin-bottom: 8px !important;
    }
    
    .stat-box p {
        color: #64748b !important;
        margin: 0 !important;
        font-weight: 600 !important;
        font-size: 14px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }
    
    .stat-icon {
        font-size: 48px !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
        transition: all 0.3s ease !important;
    }
    
    .stat-box:hover .stat-icon {
        transform: scale(1.1) rotate(5deg) !important;
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
        animation: slideUp 0.5s ease !important;
    }
    
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .panel:hover {
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2) !important;
        transform: translateY(-3px) !important;
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
    
    .project-card {
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%) !important;
        border: 2px solid #e2e8f0 !important;
        border-radius: 16px !important;
        padding: 20px !important;
        margin-bottom: 20px !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        position: relative !important;
        overflow: hidden !important;
    }
    
    .project-card::before {
        content: '' !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 5px !important;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%) !important;
        transform: scaleX(0) !important;
        transition: transform 0.3s ease !important;
    }
    
    .project-card:hover::before {
        transform: scaleX(1) !important;
    }
    
    .project-card:hover {
        transform: translateX(8px) !important;
        box-shadow: 0 8px 30px rgba(102, 126, 234, 0.15) !important;
        border-color: #667eea !important;
    }
    
    .project-card h4 {
        margin-top: 0 !important;
        color: #1e293b !important;
        font-weight: 700 !important;
        font-size: 18px !important;
        margin-bottom: 12px !important;
    }
    
    .project-card h4 a {
        color: #1e293b !important;
        text-decoration: none !important;
        transition: color 0.3s ease !important;
    }
    
    .project-card h4 a:hover {
        color: #667eea !important;
    }
    
    .project-meta {
        color: #64748b !important;
        font-size: 13px !important;
    }
    
    .badge-status {
        padding: 6px 14px !important;
        border-radius: 20px !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 5px !important;
        margin-right: 8px !important;
    }
    
    .badge-planning { 
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
        color: white !important;
        box-shadow: 0 3px 12px rgba(251, 191, 36, 0.3) !important;
    }
    
    .badge-in_progress { 
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
        color: white !important;
        box-shadow: 0 3px 12px rgba(59, 130, 246, 0.3) !important;
    }
    
    .badge-on_hold { 
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        color: white !important;
        box-shadow: 0 3px 12px rgba(239, 68, 68, 0.3) !important;
    }
    
    .badge-completed { 
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        color: white !important;
        box-shadow: 0 3px 12px rgba(16, 185, 129, 0.3) !important;
    }
    
    .badge-cancelled { 
        background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%) !important;
        color: white !important;
        box-shadow: 0 3px 12px rgba(107, 114, 128, 0.3) !important;
    }
    
    .badge-priority {
        padding: 6px 14px !important;
        border-radius: 20px !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        margin-right: 8px !important;
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
    
    .task-item {
        padding: 18px 20px !important;
        background: white !important;
        border-left: 5px solid #667eea !important;
        margin-bottom: 16px !important;
        border-radius: 12px !important;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05) !important;
        transition: all 0.3s ease !important;
    }
    
    .task-item:hover {
        transform: translateX(8px) !important;
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.15) !important;
    }
    
    .task-item.critical {
        border-left-color: #ef4444 !important;
        background: linear-gradient(90deg, rgba(239, 68, 68, 0.05) 0%, white 100%) !important;
    }
    
    .task-item.high {
        border-left-color: #f97316 !important;
        background: linear-gradient(90deg, rgba(249, 115, 22, 0.05) 0%, white 100%) !important;
    }
    
    .task-item.medium {
        border-left-color: #fbbf24 !important;
        background: linear-gradient(90deg, rgba(251, 191, 36, 0.05) 0%, white 100%) !important;
    }
    
    .task-item.low {
        border-left-color: #10b981 !important;
        background: linear-gradient(90deg, rgba(16, 185, 129, 0.05) 0%, white 100%) !important;
    }
    
    .task-item strong {
        color: #1e293b !important;
        font-size: 15px !important;
    }
    
    .btn-default {
        background: white !important;
        color: #667eea !important;
        border: 2px solid #667eea !important;
        border-radius: 12px !important;
        padding: 12px !important;
        font-weight: 700 !important;
        transition: all 0.3s ease !important;
        text-transform: uppercase !important;
        font-size: 13px !important;
        letter-spacing: 0.5px !important;
    }
    
    .btn-default:hover {
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
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4) !important;
        color: white !important;
    }
    
    .text-center h2 {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
        font-weight: 800 !important;
        font-size: 48px !important;
        margin: 10px 0 !important;
    }
    
    .text-muted {
        color: #64748b !important;
        font-weight: 500 !important;
    }
    
    /* RESPONSIVE BREAKPOINTS */
    @media (max-width: 1200px) {
        .dashboard-container {
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
        .stat-box {
            margin-bottom: 20px !important;
        }
        .panel-body {
            padding: 20px !important;
        }
    }
    
    @media (max-width: 768px) {
        .dashboard-container {
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
        .stat-box {
            padding: 20px !important;
            margin-bottom: 15px !important;
        }
        .stat-box h3 {
            font-size: 32px !important;
        }
        .project-card, .task-item {
            padding: 15px !important;
        }
    }
    
    @media (max-width: 480px) {
        .dashboard-container {
            padding: 8px !important;
        }
        .stat-box {
            padding: 15px !important;
        }
        .stat-icon {
            font-size: 36px !important;
        }
        .panel-heading {
            padding: 15px 20px !important;
        }
        .panel-body {
            padding: 15px !important;
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
        // Animated Counter
        $('.counter').each(function() {
            const $this = $(this);
            const countTo = parseInt($this.attr('data-target'));
            
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
        });
        
        // Add entrance animation to cards
        $('.project-card, .task-item').each(function(index) {
            $(this).css({
                'animation': `slideUp 0.5s ease ${index * 0.1}s both`
            });
        });
        
        // Smooth scroll
        $('html').css('scroll-behavior', 'smooth');
    });
</script>

<?php require_once 'includes/footer.php'; ?>