<?php
$page_title = 'Edit Requirement';
require_once 'includes/header.php';
require_once 'components/requirement.php';

$auth->checkAccess('manager');

$req_id = $_GET['id'] ?? 0;
$req_obj = new Requirement();
$req = $req_obj->getById($req_id);

if (!$req) {
    header('Location: projects.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'requirement_title' => $_POST['requirement_title'],
        'description' => $_POST['description'],
        'type' => $_POST['type'],
        'priority' => $_POST['priority'],
        'status' => $_POST['status']
    ];
    
    if ($req_obj->update($req_id, $data)) {
        header('Location: project-detail.php?id=' . $req['project_id'] . '&tab=requirements');
        exit;
    }
}
?>

<div class="container-fluid">
    <div class="page-header">
        <h1><i class="fa fa-edit"></i> Edit Requirement</h1>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="requirement_title">Requirement Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="requirement_title" name="requirement_title" value="<?php echo htmlspecialchars($req['requirement_title']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="6"><?php echo htmlspecialchars($req['description']); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="type">Type</label>
                                    <select class="form-control" id="type" name="type">
                                        <option value="functional" <?php echo $req['type'] === 'functional' ? 'selected' : ''; ?>>Functional</option>
                                        <option value="non_functional" <?php echo $req['type'] === 'non_functional' ? 'selected' : ''; ?>>Non-Functional</option>
                                        <option value="technical" <?php echo $req['type'] === 'technical' ? 'selected' : ''; ?>>Technical</option>
                                        <option value="business" <?php echo $req['type'] === 'business' ? 'selected' : ''; ?>>Business</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="priority">Priority</label>
                                    <select class="form-control" id="priority" name="priority">
                                        <option value="low" <?php echo $req['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                                        <option value="medium" <?php echo $req['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                        <option value="high" <?php echo $req['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                                        <option value="critical" <?php echo $req['priority'] === 'critical' ? 'selected' : ''; ?>>Critical</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="pending" <?php echo $req['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="approved" <?php echo $req['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                        <option value="in_progress" <?php echo $req['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="completed" <?php echo $req['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="rejected" <?php echo $req['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fa fa-save"></i> Update Requirement
                        </button>
                        <a href="project-detail.php?id=<?php echo $req['project_id']; ?>&tab=requirements" class="btn btn-default btn-lg">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
