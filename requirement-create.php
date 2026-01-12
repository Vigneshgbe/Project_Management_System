<?php
$page_title = 'Create Requirement';
require_once 'includes/header.php';
require_once 'components/requirement.php';

$auth->checkAccess('manager');

$project_id = $_GET['project_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $req = new Requirement();
    
    $data = [
        'project_id' => $_POST['project_id'],
        'requirement_title' => $_POST['requirement_title'],
        'description' => $_POST['description'],
        'type' => $_POST['type'],
        'priority' => $_POST['priority'],
        'status' => $_POST['status'],
        'created_by' => $auth->getUserId()
    ];
    
    if ($req->create($data)) {
        header('Location: project-detail.php?id=' . $_POST['project_id'] . '&tab=requirements');
        exit;
    }
}
?>

<div class="container-fluid">
    <div class="page-header">
        <h1><i class="fa fa-plus"></i> Create Requirement</h1>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-body">
                    <form method="POST" action="">
                        <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                        
                        <div class="form-group">
                            <label for="requirement_title">Requirement Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="requirement_title" name="requirement_title" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="6"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="type">Type</label>
                                    <select class="form-control" id="type" name="type">
                                        <option value="functional">Functional</option>
                                        <option value="non_functional">Non-Functional</option>
                                        <option value="technical">Technical</option>
                                        <option value="business">Business</option>
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
                                    <label for="status">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="pending">Pending</option>
                                        <option value="approved">Approved</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="completed">Completed</option>
                                        <option value="rejected">Rejected</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fa fa-save"></i> Create Requirement
                        </button>
                        <a href="project-detail.php?id=<?php echo $project_id; ?>&tab=requirements" class="btn btn-default btn-lg">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
