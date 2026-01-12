<?php
$page_title = 'Edit Project';
require_once 'includes/header.php';
require_once 'components/project.php';

$auth->checkAccess('manager');

$project_id = $_GET['id'] ?? 0;
$project_obj = new Project();
$project = $project_obj->getById($project_id);

if (!$project) {
    header('Location: projects.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'project_name' => $_POST['project_name'],
        'description' => $_POST['description'],
        'client_name' => $_POST['client_name'],
        'start_date' => $_POST['start_date'] ?: null,
        'end_date' => $_POST['end_date'] ?: null,
        'status' => $_POST['status'],
        'priority' => $_POST['priority'],
        'budget' => $_POST['budget'] ?: 0
    ];
    
    if ($project_obj->update($project_id, $data)) {
        header('Location: project-detail.php?id=' . $project_id);
        exit;
    }
}
?>

<div class="container-fluid">
    <div class="page-header">
        <h1><i class="fa fa-edit"></i> Edit Project</h1>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="project_name">Project Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="project_name" name="project_name" value="<?php echo htmlspecialchars($project['project_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Project Code</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($project['project_code']); ?>" disabled>
                            <small class="text-muted">Project code cannot be changed</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="client_name">Client Name</label>
                            <input type="text" class="form-control" id="client_name" name="client_name" value="<?php echo htmlspecialchars($project['client_name']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($project['description']); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start_date">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $project['start_date']; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="end_date">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $project['end_date']; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="planning" <?php echo $project['status'] === 'planning' ? 'selected' : ''; ?>>Planning</option>
                                        <option value="in_progress" <?php echo $project['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="on_hold" <?php echo $project['status'] === 'on_hold' ? 'selected' : ''; ?>>On Hold</option>
                                        <option value="completed" <?php echo $project['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $project['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="priority">Priority</label>
                                    <select class="form-control" id="priority" name="priority">
                                        <option value="low" <?php echo $project['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                                        <option value="medium" <?php echo $project['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                        <option value="high" <?php echo $project['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                                        <option value="critical" <?php echo $project['priority'] === 'critical' ? 'selected' : ''; ?>>Critical</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="budget">Budget ($)</label>
                                    <input type="number" class="form-control" id="budget" name="budget" step="0.01" min="0" value="<?php echo $project['budget']; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fa fa-save"></i> Update Project
                        </button>
                        <a href="project-detail.php?id=<?php echo $project_id; ?>" class="btn btn-default btn-lg">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <h3 class="panel-title">Project Information</h3>
                </div>
                <div class="panel-body">
                    <p><strong>Created By:</strong><br><?php echo htmlspecialchars($project['creator_name']); ?></p>
                    <p><strong>Created:</strong><br><?php echo date('M d, Y H:i', strtotime($project['created_at'])); ?></p>
                    <p><strong>Last Updated:</strong><br><?php echo date('M d, Y H:i', strtotime($project['updated_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
