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
    }
    
    .tasks-container {
        background: transparent;
        min-height: calc(100vh - 100px);
        padding: 24px;
        margin: 0;
        animation: fadeIn 0.4s ease;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .page-header {
        background: white;
        padding: 32px;
        border-radius: 16px;
        margin-bottom: 32px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
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
    
    .filter-box {
        background: white;
        padding: 24px;
        border-radius: 16px;
        margin-bottom: 32px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--border);
        animation: slideUp 0.4s ease;
    }
    
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .filter-box .form-control {
        border: 2px solid var(--border);
        border-radius: 10px;
        padding: 10px 16px;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
        height: auto;
        color: var(--dark);
    }
    
    .filter-box .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        outline: none;
    }
    
    .filter-box label {
        font-weight: 700;
        color: var(--dark);
        margin-right: 10px;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .btn-default {
        background: white;
        color: var(--primary);
        border: 2px solid var(--primary);
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .btn-default:hover,
    .btn-default:focus {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(99, 102, 241, 0.3);
    }
    
    .panel {
        border: none;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        background: white;
        border: 1px solid var(--border);
        animation: cardSlideUp 0.4s ease;
        height: calc(100vh - 380px);
        min-height: 500px;
        display: flex;
        flex-direction: column;
        margin-bottom: 24px;
    }
    
    @keyframes cardSlideUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .panel:hover {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        transform: translateY(-2px);
    }
    
    .panel-heading {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        padding: 20px 24px;
        border: none;
    }
    
    .panel-heading.status-todo {
        background: linear-gradient(135deg, #64748b, #475569);
    }
    
    .panel-heading.status-in_progress {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
    }
    
    .panel-heading.status-review {
        background: linear-gradient(135deg, #f59e0b, #d97706);
    }
    
    .panel-heading.status-completed {
        background: linear-gradient(135deg, #10b981, #059669);
    }
    
    .panel-title {
        font-size: 15px;
        font-weight: 700;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .panel-title .badge {
        background: rgba(255, 255, 255, 0.25);
        color: white;
        border-radius: 8px;
        padding: 4px 10px;
        font-size: 13px;
        font-weight: 700;
    }
    
    .panel-body {
        padding: 20px;
        overflow-y: auto;
        flex: 1;
    }
    
    .panel-body::-webkit-scrollbar {
        width: 8px;
    }
    
    .panel-body::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    
    .panel-body::-webkit-scrollbar-thumb {
        background: var(--primary);
        border-radius: 4px;
    }
    
    .panel-body::-webkit-scrollbar-thumb:hover {
        background: var(--primary-dark);
    }
    
    .task-item {
        padding: 20px;
        background: white;
        border: 2px solid var(--border);
        border-left: 4px solid var(--primary);
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
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
        border-color: var(--primary);
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
        color: var(--dark);
        font-weight: 700;
        font-size: 15px;
        line-height: 1.4;
    }
    
    .task-item small {
        color: #64748b;
        font-weight: 600;
        font-size: 13px;
    }
    
    .badge-priority {
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 8px;
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
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.85; transform: scale(1.03); }
    }
    
    .btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border: none;
        color: white;
        border-radius: 10px;
        padding: 8px 16px;
        font-weight: 700;
        font-size: 12px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
    }
    
    .btn-primary:hover,
    .btn-primary:focus {
        background: linear-gradient(135deg, var(--primary-dark), var(--primary));
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        color: white;
    }
    
    .btn-xs {
        padding: 6px 12px;
        font-size: 11px;
    }
    
    .text-danger {
        color: var(--danger);
        font-weight: 700;
    }
    
    .alert {
        border-radius: 12px;
        padding: 16px 20px;
        border: none;
        margin-bottom: 24px;
        animation: slideDown 0.4s ease;
        font-weight: 600;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        border-left: 4px solid;
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .alert-info {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(37, 99, 235, 0.05));
        color: #1e40af;
        border-left-color: #3b82f6;
    }
    
    .text-muted {
        color: #94a3b8;
        font-style: italic;
    }
    
    .overdue-indicator {
        display: inline-block;
        width: 8px;
        height: 8px;
        background: var(--danger);
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
    
    /* RESPONSIVE */
    @media (max-width: 1200px) {
        .tasks-container { padding: 20px; }
        .page-header { padding: 28px; }
        .page-header h1 { font-size: 28px; }
    }
    
    @media (max-width: 991px) {
        .panel {
            height: auto;
            min-height: 400px;
            margin-bottom: 20px;
        }
    }
    
    @media (max-width: 768px) {
        .tasks-container { padding: 16px; }
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
        .filter-box { padding: 20px; }
        .filter-box .form-group {
            display: block;
            margin-bottom: 15px;
        }
        .filter-box .form-control { width: 100%; }
        .filter-box .btn { width: 100%; }
        .task-item { padding: 16px; }
    }
    
    @media (max-width: 480px) {
        .tasks-container { padding: 12px; }
        .page-header { padding: 20px; }
        .page-header h1 { font-size: 20px; }
        .filter-box { padding: 16px; }
        .task-item { padding: 12px; }
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
                $(this).css('transform', 'translateX(4px) scale(1.02)');
            },
            function() {
                $(this).css('transform', 'translateX(0) scale(1)');
            }
        );
    });
</script>

<?php require_once 'includes/footer.php'; ?>