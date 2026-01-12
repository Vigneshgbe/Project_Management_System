<?php
$page_title = 'User Management';
require_once '../includes/header.php';
require_once '../components/user.php';

$auth->checkAccess('admin');

$user = new User();
$users = $user->getAll();
?>

<div class="container-fluid">
    <div class="page-header">
        <div class="row">
            <div class="col-md-8">
                <h1><i class="fa fa-users"></i> User Management</h1>
            </div>
            <div class="col-md-4 text-right">
                <a href="user-create.php" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add User
                </a>
            </div>
        </div>
    </div>
    
    <div class="panel panel-default">
        <div class="panel-body">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div class="member-avatar">
                                <?php echo strtoupper(substr($u['full_name'], 0, 1)); ?>
                            </div>
                            <?php echo htmlspecialchars($u['full_name']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td>
                            <span class="label label-<?php 
                                echo $u['role'] === 'admin' ? 'danger' : ($u['role'] === 'manager' ? 'warning' : 'default'); 
                            ?>">
                                <?php echo ucfirst($u['role']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="label label-<?php echo $u['status'] === 'active' ? 'success' : 'default'; ?>">
                                <?php echo ucfirst($u['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                        <td>
                            <a href="user-edit.php?id=<?php echo $u['id']; ?>" class="btn btn-xs btn-default">
                                <i class="fa fa-edit"></i> Edit
                            </a>
                            <?php if ($u['id'] != $auth->getUserId()): ?>
                            <a href="user-delete.php?id=<?php echo $u['id']; ?>" class="btn btn-xs btn-danger delete-confirm">
                                <i class="fa fa-trash"></i> Delete
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
