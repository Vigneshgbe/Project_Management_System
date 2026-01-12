<?php
$page_title = 'Add Team Member';
require_once 'includes/header.php';
require_once 'components/project.php';
require_once 'components/user.php';

$auth->checkAccess('manager');

$project_id = $_GET['project_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project = new Project();
    
    $user_id = $_POST['user_id'];
    $role = $_POST['role'];
    
    if ($project->addMember($project_id, $user_id, $role)) {
        header('Location: project-detail.php?id=' . $project_id . '&tab=team');
        exit;
    }
}

$user = new User();
$users = $user->getActiveUsers();

$project_obj = new Project();
$existing_members = $project_obj->getMembers($project_id);
$existing_member_ids = array_column($existing_members, 'user_id');
?>

<div class="container-fluid">
    <div class="page-header">
        <h1><i class="fa fa-plus"></i> Add Team Member</h1>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="user_id">Select User <span class="text-danger">*</span></label>
                            <select class="form-control" id="user_id" name="user_id" required>
                                <option value="">-- Select User --</option>
                                <?php foreach ($users as $u): ?>
                                    <?php if (!in_array($u['id'], $existing_member_ids)): ?>
                                    <option value="<?php echo $u['id']; ?>">
                                        <?php echo htmlspecialchars($u['full_name']); ?> (<?php echo ucfirst($u['role']); ?>)
                                    </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Project Role <span class="text-danger">*</span></label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="member">Member</option>
                                <option value="lead">Lead</option>
                                <option value="viewer">Viewer</option>
                            </select>
                        </div>
                        
                        <hr>
                        
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fa fa-plus"></i> Add Member
                        </button>
                        <a href="project-detail.php?id=<?php echo $project_id; ?>&tab=team" class="btn btn-default btn-lg">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
