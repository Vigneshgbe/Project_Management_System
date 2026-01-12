<?php
$page_title = 'Create Task';
require_once 'includes/header.php';
require_once 'components/task.php';
require_once 'components/user.php';

$auth->checkAccess();

$project_id = $_GET['project_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task = new Task();
    
    $data = [
        'project_id' => $_POST['project_id'],
        'phase_id' => $_POST['phase_id'] ?: null,
        'task_name' => $_POST['task_name'],
        'description' => $_POST['description'],
        'assigned_to' => $_POST['assigned_to'] ?: null,
        'status' => $_POST['status'],
        'priority' => $_POST['priority'],
        'due_date' => $_POST['due_date'] ?: null,
        'estimated_hours' => $_POST['estimated_hours'] ?: null,
        'created_by' => $auth->getUserId()
    ];
    
    if ($task->create($data)) {
        header('Location: project-detail.php?id=' . $_POST['project_id'] . '&tab=tasks');
        exit;
    }
}

$user = new User();
$users = $user->getActiveUsers();
?>

<div class="container-fluid">
    <div class="page-header">
        <h1><i class="fa fa-plus"></i> Create Task</h1>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-body">
                    <form method="POST" action="">
                        <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                        
                        <div class="form-group">
                            <label for="task_name">Task Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="task_name" name="task_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="assigned_to">Assign To</label>
                                    <select class="form-control" id="assigned_to" name="assigned_to">
                                        <option value="">Unassigned</option>
                                        <?php foreach ($users as $u): ?>
                                        <option value="<?php echo $u['id']; ?>">
                                            <?php echo htmlspecialchars($u['full_name']); ?> (<?php echo ucfirst($u['role']); ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="due_date">Due Date</label>
                                    <input type="date" class="form-control" id="due_date" name="due_date">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="todo">To Do</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="review">Review</option>
                                        <option value="completed">Completed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="priority">Priority</label>
                                    <select class="form-control" id="priority" name="priority">
                                        <option value="low">Low</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="high">High</option>
                                        <option value="critical">Critical</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="estimated_hours">Estimated Hours</label>
                                    <input type="number" class="form-control" id="estimated_hours" name="estimated_hours" step="0.5" min="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="phase_id">Phase (Optional)</label>
                            <input type="number" class="form-control" id="phase_id" name="phase_id" placeholder="Phase ID">
                        </div>
                        
                        <hr>
                        
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fa fa-save"></i> Create Task
                        </button>
                        <a href="project-detail.php?id=<?php echo $project_id; ?>&tab=tasks" class="btn btn-default btn-lg">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
