<?php
$page_title = 'Edit Task';
require_once 'includes/header.php';
require_once 'components/task.php';
require_once 'components/user.php';

$auth->checkAccess();

$task_id = $_GET['id'] ?? 0;
$task_obj = new Task();
$task = $task_obj->getById($task_id);

if (!$task) {
    header('Location: tasks.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'task_name' => $_POST['task_name'],
        'description' => $_POST['description'],
        'assigned_to' => $_POST['assigned_to'] ?: null,
        'status' => $_POST['status'],
        'priority' => $_POST['priority'],
        'due_date' => $_POST['due_date'] ?: null,
        'estimated_hours' => $_POST['estimated_hours'] ?: null,
        'actual_hours' => $_POST['actual_hours'] ?: null
    ];
    
    if ($task_obj->update($task_id, $data)) {
        header('Location: project-detail.php?id=' . $task['project_id'] . '&tab=tasks');
        exit;
    }
}

$user = new User();
$users = $user->getActiveUsers();
?>

<div class="container-fluid">
    <div class="page-header">
        <h1><i class="fa fa-edit"></i> Edit Task</h1>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="task_name">Task Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="task_name" name="task_name" value="<?php echo htmlspecialchars($task['task_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($task['description']); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="assigned_to">Assign To</label>
                                    <select class="form-control" id="assigned_to" name="assigned_to">
                                        <option value="">Unassigned</option>
                                        <?php foreach ($users as $u): ?>
                                        <option value="<?php echo $u['id']; ?>" <?php echo $task['assigned_to'] == $u['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($u['full_name']); ?> (<?php echo ucfirst($u['role']); ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="due_date">Due Date</label>
                                    <input type="date" class="form-control" id="due_date" name="due_date" value="<?php echo $task['due_date']; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="todo" <?php echo $task['status'] === 'todo' ? 'selected' : ''; ?>>To Do</option>
                                        <option value="in_progress" <?php echo $task['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="review" <?php echo $task['status'] === 'review' ? 'selected' : ''; ?>>Review</option>
                                        <option value="completed" <?php echo $task['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="priority">Priority</label>
                                    <select class="form-control" id="priority" name="priority">
                                        <option value="low" <?php echo $task['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                                        <option value="medium" <?php echo $task['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                        <option value="high" <?php echo $task['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                                        <option value="critical" <?php echo $task['priority'] === 'critical' ? 'selected' : ''; ?>>Critical</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="estimated_hours">Estimated Hours</label>
                                    <input type="number" class="form-control" id="estimated_hours" name="estimated_hours" step="0.5" min="0" value="<?php echo $task['estimated_hours']; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="actual_hours">Actual Hours</label>
                                    <input type="number" class="form-control" id="actual_hours" name="actual_hours" step="0.5" min="0" value="<?php echo $task['actual_hours']; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fa fa-save"></i> Update Task
                        </button>
                        <a href="project-detail.php?id=<?php echo $task['project_id']; ?>&tab=tasks" class="btn btn-default btn-lg">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <h3 class="panel-title">Task Information</h3>
                </div>
                <div class="panel-body">
                    <p><strong>Created By:</strong><br><?php echo htmlspecialchars($task['creator_name']); ?></p>
                    <p><strong>Created:</strong><br><?php echo date('M d, Y H:i', strtotime($task['created_at'])); ?></p>
                    <p><strong>Last Updated:</strong><br><?php echo date('M d, Y H:i', strtotime($task['updated_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
