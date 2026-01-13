<?php
$page_title = 'My Tasks';
require_once 'includes/header.php';
require_once 'components/task.php';

$auth->checkAccess();

$task = new Task();
$user_tasks = $task->getByUser($auth->getUserId());

$filter_status = $_GET['status'] ?? '';
if ($filter_status) {
    $user_tasks = array_filter($user_tasks, function($t) use ($filter_status) {
        return $t['status'] === $filter_status;
    });
}
?>

<style>
    .tasks-container {
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        padding: 35px 40px !important;
        border-radius: 20px !important;
        margin-bottom: 30px !important;
        box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3) !important;
        border: none !important;
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
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%) !important;
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
    }
    
    .filter-box {
        background: white !important;
        padding: 25px 30px !important;
        border-radius: 20px !important;
        margin-bottom: 30px !important;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08) !important;
        animation: slideUp 0.5s ease !important;
    }
    
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .filter-box .form-control {
        border: 2px solid #e5e7eb !important;
        border-radius: 12px !important;
        padding: 12px 18px !important;
        font-size: 14px !important;
        font-weight: 500 !important;
        transition: all 0.3s ease !important;
        height: auto !important;
    }
    
    .filter-box .form-control:focus {
        border-color: #667eea !important;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1) !important;
        outline: none !important;
    }
    
    .filter-box label {
        font-weight: 700 !important;
        color: #374151 !important;
        margin-right: 10px !important;
        font-size: 14px !important;
    }
    
    .btn-default {
        background: white !important;
        color: #667eea !important;
        border: 2px solid #667eea !important;
        padding: 12px 24px !important;
        border-radius: 12px !important;
        font-weight: 700 !important;
        font-size: 14px !important;
        transition: all 0.3s ease !important;
    }
    
    .btn-default:hover {
        background: #667eea !important;
        color: white !important;
        border-color: #667eea !important;
        transform: translateY(-2px) !important;
    }
    
    .panel {
        border: none !important;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08) !important;
        border-radius: 20px !important;
        overflow: hidden !important;
        transition: all 0.3s ease !important;
        background: white !important;
        animation: cardSlideUp 0.5s ease !important;
        height: calc(100vh - 380px) !important;
        min-height: 500px !important;
        display: flex !important;
        flex-direction: column !important;
    }
    
    @keyframes cardSlideUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .panel:hover {
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12) !important;
        transform: translateY(-3px) !important;
    }
    
    .panel-heading {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        padding: 20px 25px !important;
        border: none !important;
        font-weight: 700 !important;
    }
    
    .panel-heading.status-todo {
        background: linear-gradient(135deg, #64748b 0%, #475569 100%) !important;
    }
    
    .panel-heading.status-in_progress {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
    }
    
    .panel-heading.status-review {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
    }
    
    .panel-heading.status-completed {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    }
    
    .panel-title {
        font-size: 16px !important;
        font-weight: 800 !important;
        margin: 0 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }
    
    .panel-title .badge {
        background: rgba(255, 255, 255, 0.3) !important;
        color: white !important;
        border-radius: 12px !important;
        padding: 4px 10px !important;
        font-size: 13px !important;
        font-weight: 700 !important;
        float: right !important;
    }
    
    .panel-body {
        padding: 20px !important;
        overflow-y: auto !important;
        flex: 1 !important;
    }
    
    .panel-body::-webkit-scrollbar {
        width: 6px !important;
    }
    
    .panel-body::-webkit-scrollbar-track {
        background: #f1f5f9 !important;
        border-radius: 3px !important;
    }
    
    .panel-body::-webkit-scrollbar-thumb {
        background: #cbd5e1 !important;
        border-radius: 3px !important;
    }
    
    .panel-body::-webkit-scrollbar-thumb:hover {
        background: #94a3b8 !important;
    }
    
    .task-item {
        padding: 18px 20px !important;
        background: white !important;
        border: 2px solid #e5e7eb !important;
        border-left: 5px solid #667eea !important;
        margin-bottom: 16px !important;
        border-radius: 12px !important;
        transition: all 0.3s ease !important;
        cursor: pointer !important;
        animation: taskSlideIn 0.4s ease !important;
    }
    
    @keyframes taskSlideIn {
        from { opacity: 0; transform: translateX(-20px); }
        to { opacity: 1; transform: translateX(0); }
    }
    
    .task-item:hover {
        transform: translateX(5px) !important;
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.15) !important;
        border-color: #667eea !important;
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
    
    .task-item h5 {
        margin: 0 0 10px 0 !important;
        color: #1e293b !important;
        font-weight: 700 !important;
        font-size: 15px !important;
        line-height: 1.4 !important;
    }
    
    .task-item small {
        color: #64748b !important;
        font-weight: 500 !important;
        font-size: 13px !important;
    }
    
    .badge-priority {
        padding: 5px 12px !important;
        border-radius: 16px !important;
        font-size: 10px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        display: inline-block !important;
        margin-top: 8px !important;
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
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        border: none !important;
        color: white !important;
        border-radius: 10px !important;
        padding: 8px 16px !important;
        font-weight: 700 !important;
        font-size: 12px !important;
        transition: all 0.3s ease !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3) !important;
        color: white !important;
    }
    
    .btn-xs {
        padding: 6px 12px !important;
        font-size: 11px !important;
    }
    
    .text-danger {
        color: #ef4444 !important;
        font-weight: 700 !important;
    }
    
    .alert {
        border-radius: 16px !important;
        padding: 20px 25px !important;
        border: none !important;
        margin-bottom: 25px !important;
        animation: slideDown 0.5s ease !important;
        font-weight: 500 !important;
        font-size: 15px !important;
    }
    
    .alert-info {
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%) !important;
        color: #1e40af !important;
    }
    
    .text-muted {
        color: #94a3b8 !important;
        font-style: italic !important;
    }
    
    .overdue-indicator {
        display: inline-block !important;
        width: 8px !important;
        height: 8px !important;
        background: #ef4444 !important;
        border-radius: 50% !important;
        margin-right: 5px !important;
        animation: blink 1.5s infinite !important;
    }
    
    @keyframes blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
    }
    
    .col-md-3 {
        margin-bottom: 20px !important;
    }
    
    /* RESPONSIVE BREAKPOINTS */
    @media (max-width: 1200px) {
        .tasks-container {
            padding: 15px !important;
        }
        .page-header {
            padding: 25px 30px !important;
        }
        .page-header h1 {
            font-size: 28px !important;
        }
    }
    
    @media (max-width: 991px) {
        .panel {
            height: auto !important;
            min-height: 400px !important;
            margin-bottom: 20px !important;
        }
    }
    
    @media (max-width: 768px) {
        .tasks-container {
            padding: 10px !important;
        }
        .page-header {
            padding: 20px !important;
        }
        .page-header h1 {
            font-size: 24px !important;
        }
        .filter-box {
            padding: 20px !important;
        }
        .filter-box .form-group {
            display: block !important;
            margin-bottom: 15px !important;
        }
        .filter-box .form-control {
            width: 100% !important;
        }
        .filter-box .btn {
            width: 100% !important;
        }
        .task-item {
            padding: 15px !important;
        }
    }
    
    @media (max-width: 480px) {
        .tasks-container {
            padding: 8px !important;
        }
        .page-header {
            padding: 15px !important;
        }
        .page-header h1 {
            font-size: 20px !important;
        }
        .filter-box {
            padding: 15px !important;
        }
        .task-item {
            padding: 12px !important;
        }
    }
</style>

<div class="tasks-container container-fluid">
    <div class="page-header">
        <h1><i class="fa fa-tasks"></i> My Tasks</h1>
    </div>
    
    <div class="filter-box">
        <form method="GET" action="" class="form-inline">
            <div class="form-group" style="margin-right: 15px;">
                <label>Filter by Status:</label>
                <select name="status" class="form-control" onchange="this.form.submit()" style="width: 180px;">
                    <option value="">All Tasks</option>
                    <option value="todo" <?php echo $filter_status === 'todo' ? 'selected' : ''; ?>>To Do</option>
                    <option value="in_progress" <?php echo $filter_status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="review" <?php echo $filter_status === 'review' ? 'selected' : ''; ?>>Review</option>
                    <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
            <a href="tasks.php" class="btn btn-default">
                <i class="fa fa-refresh"></i> Reset
            </a>
        </form>
    </div>
    
    <?php if (empty($user_tasks)): ?>
    <div class="alert alert-info">
        <i class="fa fa-info-circle"></i> No tasks assigned to you.
    </div>
    <?php else: ?>
    
    <div class="row">
        <?php
        $status_groups = [
            'todo' => [],
            'in_progress' => [],
            'review' => [],
            'completed' => []
        ];
        
        foreach ($user_tasks as $t) {
            if (isset($status_groups[$t['status']])) {
                $status_groups[$t['status']][] = $t;
            }
        }
        ?>
        
        <?php 
        $status_labels = [
            'todo' => 'To Do',
            'in_progress' => 'In Progress',
            'review' => 'Review',
            'completed' => 'Completed'
        ];
        $column_index = 0;
        ?>
        
        <?php foreach ($status_groups as $status => $tasks): ?>
        <div class="col-md-3" style="animation-delay: <?php echo $column_index * 0.1; ?>s;">
            <div class="panel panel-default">
                <div class="panel-heading status-<?php echo $status; ?>">
                    <h3 class="panel-title">
                        <strong><?php echo $status_labels[$status]; ?></strong>
                        <span class="badge"><?php echo count($tasks); ?></span>
                    </h3>
                </div>
                <div class="panel-body">
                    <?php if (empty($tasks)): ?>
                        <p class="text-muted text-center" style="padding: 20px;">No tasks</p>
                    <?php else: ?>
                        <?php foreach ($tasks as $task_index => $t): ?>
                        <div class="task-item <?php echo $t['priority']; ?>" style="animation-delay: <?php echo ($column_index * 0.1) + ($task_index * 0.05); ?>s;">
                            <h5><?php echo htmlspecialchars($t['task_name']); ?></h5>
                            <small class="text-muted">
                                <i class="fa fa-folder"></i> <?php echo htmlspecialchars($t['project_name']); ?>
                            </small>
                            <br>
                            <span class="badge-priority badge-<?php echo $t['priority']; ?>">
                                <i class="fa fa-flag"></i> <?php echo ucfirst($t['priority']); ?>
                            </span>
                            <?php if ($t['due_date']): ?>
                            <br>
                            <small style="margin-top: 8px; display: inline-block;">
                                <i class="fa fa-calendar"></i> 
                                <?php 
                                $due = strtotime($t['due_date']);
                                $now = time();
                                $diff_days = floor(($due - $now) / 86400);
                                
                                if ($diff_days < 0 && $t['status'] !== 'completed') {
                                    echo '<span class="text-danger"><span class="overdue-indicator"></span>Overdue by ' . abs($diff_days) . ' days</span>';
                                } else {
                                    echo date('M d, Y', $due);
                                }
                                ?>
                            </small>
                            <?php endif; ?>
                            <div style="margin-top: 12px; padding-top: 12px; border-top: 2px solid #f1f5f9;">
                                <a href="task-edit.php?id=<?php echo $t['id']; ?>" class="btn btn-xs btn-primary">
                                    <i class="fa fa-edit"></i> Update Status
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php 
        $column_index++; 
        endforeach; ?>
    </div>
    
    <?php endif; ?>
</div>

<script>
    $(document).ready(function() {
        // Staggered animation for columns
        $('.panel').each(function(index) {
            $(this).css({
                'animation-delay': (index * 0.1) + 's'
            });
        });
        
        // Staggered animation for task items
        $('.task-item').each(function(index) {
            $(this).css({
                'animation-delay': (index * 0.05) + 's'
            });
        });
        
        // Add click to view task details
        $('.task-item').on('click', function(e) {
            if (!$(e.target).is('a, button')) {
                $(this).find('.btn').click();
            }
        });
        
        // Highlight overdue tasks
        $('.text-danger').closest('.task-item').addClass('critical');
        
        // Count tasks in each column
        $('.panel').each(function() {
            const count = $(this).find('.task-item').length;
            $(this).find('.badge').text(count);
        });
        
        // Smooth scroll
        $('html').css('scroll-behavior', 'smooth');
        
        // Auto-refresh badge counts on filter
        function updateBadgeCounts() {
            $('.panel').each(function() {
                const visibleTasks = $(this).find('.task-item:visible').length;
                $(this).find('.badge').text(visibleTasks);
            });
        }
        
        // Add hover effect
        $('.task-item').hover(
            function() {
                $(this).css('transform', 'translateX(5px) scale(1.02)');
            },
            function() {
                $(this).css('transform', 'translateX(0) scale(1)');
            }
        );
    });
</script>

<?php require_once 'includes/footer.php'; ?>