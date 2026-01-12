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

<div class="container-fluid">
    <div class="page-header">
        <h1><i class="fa fa-tasks"></i> My Tasks</h1>
    </div>
    
    <div class="filter-box">
        <form method="GET" action="" class="form-inline">
            <div class="form-group">
                <label>Filter by Status:</label>
                <select name="status" class="form-control" onchange="this.form.submit()">
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
        
        <?php foreach ($status_groups as $status => $tasks): ?>
        <div class="col-md-3">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <strong><?php echo ucfirst(str_replace('_', ' ', $status)); ?></strong>
                        <span class="badge"><?php echo count($tasks); ?></span>
                    </h3>
                </div>
                <div class="panel-body" style="max-height: 600px; overflow-y: auto;">
                    <?php if (empty($tasks)): ?>
                        <p class="text-muted text-center">No tasks</p>
                    <?php else: ?>
                        <?php foreach ($tasks as $t): ?>
                        <div class="task-item <?php echo $t['priority']; ?>" style="margin-bottom: 10px;">
                            <h5 style="margin-top: 0;">
                                <?php echo htmlspecialchars($t['task_name']); ?>
                            </h5>
                            <small class="text-muted">
                                <i class="fa fa-folder"></i> <?php echo htmlspecialchars($t['project_name']); ?>
                            </small>
                            <br>
                            <span class="badge-priority badge-<?php echo $t['priority']; ?>">
                                <?php echo ucfirst($t['priority']); ?>
                            </span>
                            <?php if ($t['due_date']): ?>
                            <br>
                            <small>
                                <i class="fa fa-calendar"></i> 
                                <?php 
                                $due = strtotime($t['due_date']);
                                $now = time();
                                $diff_days = floor(($due - $now) / 86400);
                                
                                if ($diff_days < 0 && $t['status'] !== 'completed') {
                                    echo '<span class="text-danger">Overdue by ' . abs($diff_days) . ' days</span>';
                                } else {
                                    echo date('M d, Y', $due);
                                }
                                ?>
                            </small>
                            <?php endif; ?>
                            <div style="margin-top: 10px;">
                                <a href="task-edit.php?id=<?php echo $t['id']; ?>" class="btn btn-xs btn-primary">
                                    <i class="fa fa-edit"></i> Update
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
