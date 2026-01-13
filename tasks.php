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
    .container-fluid {
        background: #f8fafc;
        min-height: calc(100vh - 120px);
        padding: 30px;
        animation: fadeIn 0.5s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 35px 40px;
        border-radius: 20px;
        margin-bottom: 30px;
        box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
        border: none;
        animation: slideDown 0.6s ease;
        position: relative;
        overflow: hidden;
    }
    
    .page-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: rotate 20s linear infinite;
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
        margin: 0;
        font-weight: 800;
        font-size: 32px;
        position: relative;
        z-index: 1;
    }
    
    .filter-box {
        background: white;
        padding: 25px 30px;
        border-radius: 20px;
        margin-bottom: 30px;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
        animation: slideUp 0.5s ease;
    }
    
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .filter-box .form-control {
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 12px 18px;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
        height: auto;
    }
    
    .filter-box .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        outline: none;
    }
    
    .filter-box label {
        font-weight: 700;
        color: #374151;
        margin-right: 10px;
        font-size: 14px;
    }
    
    .btn-default {
        background: white;
        color: #667eea;
        border: 2px solid #667eea;
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 14px;
        transition: all 0.3s ease;
    }
    
    .btn-default:hover {
        background: #667eea;
        color: white;
        border-color: #667eea;
        transform: translateY(-2px);
    }
    
    .panel {
        border: none;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
        border-radius: 20px;
        overflow: hidden;
        transition: all 0.3s ease;
        background: white;
        animation: cardSlideUp 0.5s ease;
        height: calc(100vh - 350px);
        min-height: 500px;
        display: flex;
        flex-direction: column;
    }
    
    @keyframes cardSlideUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .panel:hover {
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12);
        transform: translateY(-3px);
    }
    
    .panel-heading {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px 25px;
        border: none;
        font-weight: 700;
    }
    
    .panel-heading.status-todo {
        background: linear-gradient(135deg, #64748b 0%, #475569 100%);
    }
    
    .panel-heading.status-in_progress {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    }
    
    .panel-heading.status-review {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }
    
    .panel-heading.status-completed {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }
    
    .panel-title {
        font-size: 16px;
        font-weight: 800;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .panel-title .badge {
        background: rgba(255, 255, 255, 0.3);
        color: white;
        border-radius: 12px;
        padding: 4px 10px;
        font-size: 13px;
        font-weight: 700;
        float: right;
    }
    
    .panel-body {
        padding: 20px;
        overflow-y: auto;
        flex: 1;
    }
    
    .panel-body::-webkit-scrollbar {
        width: 6px;
    }
    
    .panel-body::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 3px;
    }
    
    .panel-body::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }
    
    .panel-body::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    
    .task-item {
        padding: 18px 20px;
        background: white;
        border: 2px solid #e5e7eb;
        border-left: 5px solid #667eea;
        margin-bottom: 16px;
        border-radius: 12px;
        transition: all 0.3s ease;
        cursor: pointer;
        animation: taskSlideIn 0.4s ease;
    }
    
    @keyframes taskSlideIn {
        from { opacity: 0; transform: translateX(-20px); }
        to { opacity: 1; transform: translateX(0); }
    }
    
    .task-item:hover {
        transform: translateX(5px);
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.15);
        border-color: #667eea;
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
    
    .task-item h5 {
        margin: 0 0 10px 0;
        color: #1e293b;
        font-weight: 700;
        font-size: 15px;
        line-height: 1.4;
    }
    
    .task-item small {
        color: #64748b;
        font-weight: 500;
        font-size: 13px;
    }
    
    .badge-priority {
        padding: 5px 12px;
        border-radius: 16px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-block;
        margin-top: 8px;
    }
    
    .badge-low { 
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }
    
    .badge-medium { 
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        color: white;
    }
    
    .badge-high { 
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        color: white;
    }
    
    .badge-critical { 
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.8; transform: scale(1.05); }
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        border-radius: 10px;
        padding: 8px 16px;
        font-weight: 700;
        font-size: 12px;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        color: white;
    }
    
    .btn-xs {
        padding: 6px 12px;
        font-size: 11px;
    }
    
    .text-danger {
        color: #ef4444;
        font-weight: 700;
    }
    
    .alert {
        border-radius: 16px;
        padding: 20px 25px;
        border: none;
        margin-bottom: 25px;
        animation: slideDown 0.5s ease;
        font-weight: 500;
        font-size: 15px;
    }
    
    .alert-info {
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        color: #1e40af;
    }
    
    .text-muted {
        color: #94a3b8;
        font-style: italic;
    }
    
    .overdue-indicator {
        display: inline-block;
        width: 8px;
        height: 8px;
        background: #ef4444;
        border-radius: 50%;
        margin-right: 5px;
        animation: blink 1.5s infinite;
    }
    
    @keyframes blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
    }
    
    .col-md-3 {
        margin-bottom: 20px;
    }
    
    @media (max-width: 991px) {
        .panel {
            height: auto;
            min-height: 400px;
            margin-bottom: 20px;
        }
    }
    
    @media (max-width: 768px) {
        .page-header h1 {
            font-size: 24px;
        }
        
        .container-fluid {
            padding: 15px;
        }
        
        .filter-box {
            padding: 20px;
        }
        
        .filter-box .form-group {
            display: block;
            margin-bottom: 15px;
        }
        
        .filter-box .form-control {
            width: 100%;
        }
        
        .filter-box .btn {
            width: 100%;
        }
        
        .task-item {
            padding: 15px;
        }
    }
</style>

<div class="container-fluid">
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