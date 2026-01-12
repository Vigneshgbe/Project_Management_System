<?php
$page_title = 'My Profile';
require_once 'includes/header.php';
require_once 'components/user.php';

$auth->checkAccess();

$user_obj = new User();
$user = $user_obj->getById($auth->getUserId());

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => $_POST['username'],
        'email' => $_POST['email'],
        'full_name' => $_POST['full_name'],
        'role' => $user['role'],
        'status' => $user['status']
    ];
    
    if (!empty($_POST['new_password'])) {
        if ($_POST['new_password'] === $_POST['confirm_password']) {
            $data['password'] = $_POST['new_password'];
        } else {
            $error = 'Passwords do not match';
        }
    }
    
    if (!$error) {
        if ($user_obj->update($auth->getUserId(), $data)) {
            $_SESSION['full_name'] = $data['full_name'];
            $_SESSION['username'] = $data['username'];
            $_SESSION['email'] = $data['email'];
            $success = 'Profile updated successfully';
            $user = $user_obj->getById($auth->getUserId());
        } else {
            $error = 'Failed to update profile';
        }
    }
}

$db = getDB();
$user_stats = [
    'projects' => $db->query("SELECT COUNT(DISTINCT project_id) as count FROM project_members WHERE user_id = " . $auth->getUserId())->fetch_assoc()['count'],
    'tasks' => $db->query("SELECT COUNT(*) as count FROM tasks WHERE assigned_to = " . $auth->getUserId())->fetch_assoc()['count'],
    'completed_tasks' => $db->query("SELECT COUNT(*) as count FROM tasks WHERE assigned_to = " . $auth->getUserId() . " AND status = 'completed'")->fetch_assoc()['count']
];
?>

<div class="container-fluid">
    <div class="page-header">
        <h1><i class="fa fa-user"></i> My Profile</h1>
    </div>
    
    <?php if ($success): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($success); ?>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Profile Information</h3>
                </div>
                <div class="panel-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Role</label>
                            <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" disabled>
                        </div>
                        
                        <hr>
                        
                        <h4>Change Password</h4>
                        <p class="text-muted">Leave blank to keep current password</p>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                        
                        <hr>
                        
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fa fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <h3 class="panel-title">Account Statistics</h3>
                </div>
                <div class="panel-body">
                    <div class="member-avatar" style="width: 80px; height: 80px; font-size: 36px; margin: 0 auto 20px;">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                    
                    <h4 class="text-center"><?php echo htmlspecialchars($user['full_name']); ?></h4>
                    <p class="text-center text-muted">
                        <span class="label label-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'manager' ? 'warning' : 'default'); ?>">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </p>
                    
                    <hr>
                    
                    <table class="table">
                        <tr>
                            <td>Projects</td>
                            <td class="text-right"><strong><?php echo $user_stats['projects']; ?></strong></td>
                        </tr>
                        <tr>
                            <td>Total Tasks</td>
                            <td class="text-right"><strong><?php echo $user_stats['tasks']; ?></strong></td>
                        </tr>
                        <tr>
                            <td>Completed Tasks</td>
                            <td class="text-right"><strong><?php echo $user_stats['completed_tasks']; ?></strong></td>
                        </tr>
                    </table>
                    
                    <hr>
                    
                    <p><strong>Member Since:</strong><br><?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
