<?php
$page_title = 'Project Details';
require_once 'includes/header.php';
require_once 'components/project.php';
require_once 'components/task.php';
require_once 'components/requirement.php';
require_once 'components/pricing.php';

$auth->checkAccess();

$project_id = $_GET['id'] ?? 0;
$active_tab = $_GET['tab'] ?? 'overview';

$project_obj = new Project();
$task_obj = new Task();
$req_obj = new Requirement();
$pricing_obj = new Pricing();

$project = $project_obj->getById($project_id);

if (!$project) {
    header('Location: projects.php');
    exit;
}

$tasks = $task_obj->getByProject($project_id);
$requirements = $req_obj->getByProject($project_id);
$pricing = $pricing_obj->getByProject($project_id);
$members = $project_obj->getMembers($project_id);
$stats = $project_obj->getStats($project_id);
?>

<div class="container-fluid">
    <div class="page-header">
        <div class="row">
            <div class="col-md-8">
                <h1>
                    <i class="fa fa-folder"></i> <?php echo htmlspecialchars($project['project_name']); ?>
                    <small><?php echo htmlspecialchars($project['project_code']); ?></small>
                </h1>
            </div>
            <div class="col-md-4 text-right">
                <?php if ($auth->isManager()): ?>
                <a href="project-edit.php?id=<?php echo $project_id; ?>" class="btn btn-default">
                    <i class="fa fa-edit"></i> Edit
                </a>
                <a href="project-delete.php?id=<?php echo $project_id; ?>" class="btn btn-danger delete-confirm">
                    <i class="fa fa-trash"></i> Delete
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <span class="badge-status badge-<?php echo $project['status']; ?>">
                <?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?>
            </span>
            <span class="badge-priority badge-<?php echo $project['priority']; ?>">
                <?php echo ucfirst($project['priority']); ?> Priority
            </span>
        </div>
    </div>
    
    <br>
    
    <ul class="nav nav-tabs">
        <li class="<?php echo $active_tab === 'overview' ? 'active' : ''; ?>">
            <a href="?id=<?php echo $project_id; ?>&tab=overview">
                <i class="fa fa-info-circle"></i> Overview
            </a>
        </li>
        <li class="<?php echo $active_tab === 'tasks' ? 'active' : ''; ?>">
            <a href="?id=<?php echo $project_id; ?>&tab=tasks">
                <i class="fa fa-tasks"></i> Tasks (<?php echo count($tasks); ?>)
            </a>
        </li>
        <li class="<?php echo $active_tab === 'requirements' ? 'active' : ''; ?>">
            <a href="?id=<?php echo $project_id; ?>&tab=requirements">
                <i class="fa fa-list"></i> Requirements (<?php echo count($requirements); ?>)
            </a>
        </li>
        <li class="<?php echo $active_tab === 'pricing' ? 'active' : ''; ?>">
            <a href="?id=<?php echo $project_id; ?>&tab=pricing">
                <i class="fa fa-dollar"></i> Pricing (<?php echo count($pricing); ?>)
            </a>
        </li>
        <li class="<?php echo $active_tab === 'team' ? 'active' : ''; ?>">
            <a href="?id=<?php echo $project_id; ?>&tab=team">
                <i class="fa fa-users"></i> Team (<?php echo count($members); ?>)
            </a>
        </li>
    </ul>
    
    <div class="tab-content" style="padding-top: 20px;">
        <?php if ($active_tab === 'overview'): ?>
        <div class="row">
            <div class="col-md-8">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Project Information</h3>
                    </div>
                    <div class="panel-body">
                        <table class="table">
                            <tr>
                                <th width="200">Client:</th>
                                <td><?php echo htmlspecialchars($project['client_name'] ?: 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Description:</th>
                                <td><?php echo nl2br(htmlspecialchars($project['description'])); ?></td>
                            </tr>
                            <tr>
                                <th>Start Date:</th>
                                <td><?php echo $project['start_date'] ? date('M d, Y', strtotime($project['start_date'])) : 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <th>End Date:</th>
                                <td><?php echo $project['end_date'] ? date('M d, Y', strtotime($project['end_date'])) : 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <th>Budget:</th>
                                <td>$<?php echo number_format($project['budget'], 2); ?></td>
                            </tr>
                            <tr>
                                <th>Created By:</th>
                                <td><?php echo htmlspecialchars($project['creator_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Created At:</th>
                                <td><?php echo date('M d, Y H:i', strtotime($project['created_at'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">Project Statistics</h3>
                    </div>
                    <div class="panel-body">
                        <h4>Tasks</h4>
                        <?php foreach ($stats['tasks'] as $task_stat): ?>
                        <div class="row">
                            <div class="col-xs-8">
                                <?php echo ucfirst(str_replace('_', ' ', $task_stat['status'])); ?>
                            </div>
                            <div class="col-xs-4 text-right">
                                <strong><?php echo $task_stat['total']; ?></strong>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-xs-8">Requirements</div>
                            <div class="col-xs-4 text-right">
                                <strong><?php echo $stats['requirements']; ?></strong>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-xs-8">Total Pricing</div>
                            <div class="col-xs-4 text-right">
                                <strong>$<?php echo number_format($stats['total_pricing'], 2); ?></strong>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-xs-8">Team Members</div>
                            <div class="col-xs-4 text-right">
                                <strong><?php echo count($members); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php elseif ($active_tab === 'tasks'): ?>
        <div class="row">
            <div class="col-md-12">
                <?php if ($auth->isManager()): ?>
                <a href="task-create.php?project_id=<?php echo $project_id; ?>" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add Task
                </a>
                <br><br>
                <?php endif; ?>
                
                <?php if (empty($tasks)): ?>
                <div class="alert alert-info">No tasks found.</div>
                <?php else: ?>
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Task Name</th>
                            <th>Assigned To</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Due Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($task['task_name']); ?></td>
                            <td><?php echo htmlspecialchars($task['assigned_name'] ?: 'Unassigned'); ?></td>
                            <td>
                                <span class="badge-status badge-<?php echo $task['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge-priority badge-<?php echo $task['priority']; ?>">
                                    <?php echo ucfirst($task['priority']); ?>
                                </span>
                            </td>
                            <td><?php echo $task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : 'N/A'; ?></td>
                            <td>
                                <a href="task-edit.php?id=<?php echo $task['id']; ?>" class="btn btn-xs btn-default">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <a href="task-delete.php?id=<?php echo $task['id']; ?>" class="btn btn-xs btn-danger delete-confirm">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
        
        <?php elseif ($active_tab === 'requirements'): ?>
        <div class="row">
            <div class="col-md-12">
                <?php if ($auth->isManager()): ?>
                <a href="requirement-create.php?project_id=<?php echo $project_id; ?>" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add Requirement
                </a>
                <br><br>
                <?php endif; ?>
                
                <?php if (empty($requirements)): ?>
                <div class="alert alert-info">No requirements found.</div>
                <?php else: ?>
                <?php foreach ($requirements as $req): ?>
                <div class="requirement-item">
                    <h4><?php echo htmlspecialchars($req['requirement_title']); ?></h4>
                    <p><?php echo nl2br(htmlspecialchars($req['description'])); ?></p>
                    <div>
                        <span class="label label-default"><?php echo ucfirst($req['type']); ?></span>
                        <span class="badge-priority badge-<?php echo $req['priority']; ?>">
                            <?php echo ucfirst($req['priority']); ?>
                        </span>
                        <span class="badge-status badge-<?php echo $req['status']; ?>">
                            <?php echo ucfirst($req['status']); ?>
                        </span>
                        <?php if ($auth->isManager()): ?>
                        <div class="pull-right">
                            <a href="requirement-edit.php?id=<?php echo $req['id']; ?>" class="btn btn-xs btn-default">
                                <i class="fa fa-edit"></i> Edit
                            </a>
                            <a href="requirement-delete.php?id=<?php echo $req['id']; ?>" class="btn btn-xs btn-danger delete-confirm">
                                <i class="fa fa-trash"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <?php elseif ($active_tab === 'pricing'): ?>
        <div class="row">
            <div class="col-md-12">
                <?php if ($auth->isManager()): ?>
                <a href="pricing-create.php?project_id=<?php echo $project_id; ?>" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add Pricing Item
                </a>
                <br><br>
                <?php endif; ?>
                
                <?php if (empty($pricing)): ?>
                <div class="alert alert-info">No pricing items found.</div>
                <?php else: ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Unit Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $grand_total = 0;
                        foreach ($pricing as $item): 
                            $grand_total += $item['total_price'];
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                <?php if ($item['description']): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($item['description']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['category'] ?: 'N/A'); ?></td>
                            <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><strong>$<?php echo number_format($item['total_price'], 2); ?></strong></td>
                            <td>
                                <?php if ($auth->isManager()): ?>
                                <a href="pricing-edit.php?id=<?php echo $item['id']; ?>" class="btn btn-xs btn-default">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <a href="pricing-delete.php?id=<?php echo $item['id']; ?>" class="btn btn-xs btn-danger delete-confirm">
                                    <i class="fa fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="info">
                            <td colspan="4" class="text-right"><strong>Grand Total:</strong></td>
                            <td colspan="2"><strong>$<?php echo number_format($grand_total, 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
        
        <?php elseif ($active_tab === 'team'): ?>
        <div class="row">
            <div class="col-md-12">
                <?php if ($auth->isManager()): ?>
                <a href="project-add-member.php?project_id=<?php echo $project_id; ?>" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add Member
                </a>
                <br><br>
                <?php endif; ?>
                
                <?php if (empty($members)): ?>
                <div class="alert alert-info">No team members found.</div>
                <?php else: ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>User Role</th>
                            <th>Project Role</th>
                            <th>Assigned At</th>
                            <?php if ($auth->isManager()): ?>
                            <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member): ?>
                        <tr>
                            <td>
                                <div class="member-avatar">
                                    <?php echo strtoupper(substr($member['full_name'], 0, 1)); ?>
                                </div>
                                <?php echo htmlspecialchars($member['full_name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                            <td><?php echo ucfirst($member['user_role']); ?></td>
                            <td>
                                <span class="label label-primary">
                                    <?php echo ucfirst($member['role']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($member['assigned_at'])); ?></td>
                            <?php if ($auth->isManager()): ?>
                            <td>
                                <a href="project-remove-member.php?project_id=<?php echo $project_id; ?>&user_id=<?php echo $member['user_id']; ?>" class="btn btn-xs btn-danger delete-confirm">
                                    <i class="fa fa-remove"></i> Remove
                                </a>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
