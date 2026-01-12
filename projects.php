<?php
$page_title = 'Projects';
require_once 'includes/header.php';
require_once 'components/project.php';

$auth->checkAccess();

$project = new Project();
$filters = [
    'status' => $_GET['status'] ?? '',
    'priority' => $_GET['priority'] ?? '',
    'search' => $_GET['search'] ?? ''
];

if ($auth->isAdmin() || $auth->isManager()) {
    $projects = $project->getAll($filters);
} else {
    $projects = $project->getUserProjects($auth->getUserId());
}
?>

<div class="container-fluid">
    <div class="page-header">
        <div class="row">
            <div class="col-md-8">
                <h1><i class="fa fa-folder"></i> Projects</h1>
            </div>
            <div class="col-md-4 text-right">
                <?php if ($auth->isManager()): ?>
                <a href="project-create.php" class="btn btn-primary">
                    <i class="fa fa-plus"></i> New Project
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="filter-box">
        <form method="GET" action="" class="form-inline">
            <div class="form-group">
                <input type="text" name="search" class="form-control" placeholder="Search projects..." value="<?php echo htmlspecialchars($filters['search']); ?>">
            </div>
            
            <div class="form-group">
                <select name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="planning" <?php echo $filters['status'] === 'planning' ? 'selected' : ''; ?>>Planning</option>
                    <option value="in_progress" <?php echo $filters['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="on_hold" <?php echo $filters['status'] === 'on_hold' ? 'selected' : ''; ?>>On Hold</option>
                    <option value="completed" <?php echo $filters['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $filters['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <div class="form-group">
                <select name="priority" class="form-control">
                    <option value="">All Priority</option>
                    <option value="low" <?php echo $filters['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                    <option value="medium" <?php echo $filters['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="high" <?php echo $filters['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                    <option value="critical" <?php echo $filters['priority'] === 'critical' ? 'selected' : ''; ?>>Critical</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-default">
                <i class="fa fa-filter"></i> Filter
            </button>
            
            <a href="projects.php" class="btn btn-default">
                <i class="fa fa-refresh"></i> Reset
            </a>
        </form>
    </div>
    
    <?php if (empty($projects)): ?>
        <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No projects found.
        </div>
    <?php else: ?>
    <div class="row">
        <?php foreach ($projects as $proj): ?>
        <div class="col-md-4 col-sm-6">
            <div class="project-card">
                <h4>
                    <a href="project-detail.php?id=<?php echo $proj['id']; ?>">
                        <?php echo htmlspecialchars($proj['project_name']); ?>
                    </a>
                </h4>
                
                <div class="project-meta">
                    <p><?php echo htmlspecialchars(substr($proj['description'], 0, 100)); ?><?php echo strlen($proj['description']) > 100 ? '...' : ''; ?></p>
                    
                    <div style="margin-bottom: 10px;">
                        <span class="badge-status badge-<?php echo $proj['status']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $proj['status'])); ?>
                        </span>
                        <span class="badge-priority badge-<?php echo $proj['priority']; ?>">
                            <?php echo ucfirst($proj['priority']); ?>
                        </span>
                    </div>
                    
                    <small>
                        <i class="fa fa-code"></i> <?php echo htmlspecialchars($proj['project_code']); ?><br>
                        <?php if ($proj['client_name']): ?>
                        <i class="fa fa-user"></i> <?php echo htmlspecialchars($proj['client_name']); ?><br>
                        <?php endif; ?>
                        <?php if ($proj['start_date']): ?>
                        <i class="fa fa-calendar"></i> <?php echo date('M d, Y', strtotime($proj['start_date'])); ?>
                        <?php endif; ?>
                        <?php if ($proj['end_date']): ?>
                        - <?php echo date('M d, Y', strtotime($proj['end_date'])); ?>
                        <?php endif; ?>
                        <br>
                        <i class="fa fa-tasks"></i> <?php echo $proj['completed_tasks'] ?? 0; ?>/<?php echo $proj['task_count'] ?? 0; ?> tasks completed
                    </small>
                    
                    <?php if (isset($proj['task_count']) && $proj['task_count'] > 0): ?>
                    <div class="progress progress-custom" style="margin-top: 10px;">
                        <?php $progress = round(($proj['completed_tasks'] / $proj['task_count']) * 100); ?>
                        <div class="progress-bar progress-bar-success" style="width: <?php echo $progress; ?>%">
                            <?php echo $progress; ?>%
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div style="margin-top: 10px;">
                    <a href="project-detail.php?id=<?php echo $proj['id']; ?>" class="btn btn-sm btn-primary">
                        <i class="fa fa-eye"></i> View
                    </a>
                    <?php if ($auth->isManager()): ?>
                    <a href="project-edit.php?id=<?php echo $proj['id']; ?>" class="btn btn-sm btn-default">
                        <i class="fa fa-edit"></i> Edit
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
