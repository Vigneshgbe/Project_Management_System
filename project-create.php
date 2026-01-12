<?php
$page_title = 'Create Project';
require_once 'includes/header.php';
require_once 'components/project.php';

$auth->checkAccess('manager');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project = new Project();
    
    $data = [
        'project_name' => $_POST['project_name'] ?? '',
        'project_code' => $_POST['project_code'] ?? '',
        'description' => $_POST['description'] ?? '',
        'client_name' => $_POST['client_name'] ?? '',
        'start_date' => $_POST['start_date'] ?? null,
        'end_date' => $_POST['end_date'] ?? null,
        'status' => $_POST['status'] ?? 'planning',
        'priority' => $_POST['priority'] ?? 'medium',
        'budget' => $_POST['budget'] ?? 0,
        'created_by' => $auth->getUserId()
    ];
    
    if ($project_id = $project->create($data)) {
        header('Location: project-detail.php?id=' . $project_id);
        exit;
    } else {
        $error = 'Failed to create project. Please check if project code already exists.';
    }
}
?>

<div class="container-fluid">
    <div class="page-header">
        <h1><i class="fa fa-plus"></i> Create New Project</h1>
    </div>
    
    <?php if ($error): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="project_name">Project Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="project_name" name="project_name" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="project_code">Project Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="project_code" name="project_code" required placeholder="e.g., PROJ-001">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="client_name">Client Name</label>
                                    <input type="text" class="form-control" id="client_name" name="client_name">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start_date">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="end_date">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="planning">Planning</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="on_hold">On Hold</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
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
                                    <label for="budget">Budget ($)</label>
                                    <input type="number" class="form-control" id="budget" name="budget" step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fa fa-save"></i> Create Project
                        </button>
                        <a href="projects.php" class="btn btn-default btn-lg">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-info-circle"></i> Information</h3>
                </div>
                <div class="panel-body">
                    <p><strong>Project Code Format:</strong></p>
                    <p>Use a unique code like PROJ-001, WEB-2024, etc.</p>
                    
                    <hr>
                    
                    <p><strong>Status Options:</strong></p>
                    <ul>
                        <li><strong>Planning:</strong> Initial phase</li>
                        <li><strong>In Progress:</strong> Active development</li>
                        <li><strong>On Hold:</strong> Temporarily paused</li>
                        <li><strong>Completed:</strong> Finished</li>
                        <li><strong>Cancelled:</strong> Terminated</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
