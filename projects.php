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

<style>
    /* MODERN PROJECTS PAGE DESIGN */
    
    :root {
        --primary: #6366f1;
        --primary-dark: #4f46e5;
        --secondary: #8b5cf6;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --dark: #1e293b;
        --light: #f8fafc;
        --border: #e2e8f0;
    }
    
    .projects-container {
        padding: 24px;
        max-width: 1400px;
        margin: 0 auto;
        animation: fadeInUp 0.4s ease;
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* MODERN PAGE HEADER */
    .page-header {
        background: white;
        padding: 32px;
        border-radius: 16px;
        margin-bottom: 28px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        border: 1px solid var(--border);
        position: relative;
        overflow: hidden;
    }
    
    .page-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
    }
    
    .page-header h1 {
        margin: 0;
        font-weight: 700;
        font-size: 32px;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .page-header h1 i {
        color: var(--primary);
        font-size: 28px;
    }
    
    .page-header .btn {
        margin-top: 0;
    }
    
    /* FILTER BOX */
    .filter-box {
        background: white;
        padding: 28px;
        border-radius: 16px;
        margin-bottom: 28px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        border: 1px solid var(--border);
    }
    
    .filter-box .form-control {
        border: 2px solid var(--border);
        border-radius: 10px;
        padding: 10px 16px;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
        height: auto;
    }
    
    .filter-box .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        outline: none;
    }
    
    .filter-box .form-group {
        margin-right: 12px;
        margin-bottom: 12px;
    }
    
    .filter-box .btn {
        padding: 10px 22px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 13px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* BUTTONS */
    .btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border: none;
        color: white;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        color: white;
    }
    
    .btn-default {
        background: white;
        color: var(--primary);
        border: 2px solid var(--primary);
    }
    
    .btn-default:hover {
        background: var(--primary);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
    }
    
    .btn-sm {
        padding: 8px 16px;
        font-size: 12px;
        border-radius: 8px;
    }
    
    /* PROJECT CARDS */
    .project-card {
        background: white;
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 28px;
        margin-bottom: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .project-card::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(180deg, var(--primary), var(--secondary));
        transform: scaleY(0);
        transition: transform 0.3s ease;
        border-radius: 16px 0 0 16px;
    }
    
    .project-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        border-color: var(--primary);
    }
    
    .project-card:hover::after {
        transform: scaleY(1);
    }
    
    .project-card h4 {
        margin-top: 0;
        color: var(--dark);
        font-weight: 700;
        font-size: 19px;
        margin-bottom: 12px;
        line-height: 1.4;
    }
    
    .project-card h4 a {
        color: var(--dark);
        text-decoration: none;
        transition: color 0.2s ease;
    }
    
    .project-card h4 a:hover {
        color: var(--primary);
    }
    
    .project-meta {
        color: #64748b;
        font-size: 14px;
        line-height: 1.6;
        flex: 1;
    }
    
    .project-meta p {
        color: #475569;
        margin-bottom: 16px;
        font-weight: 500;
        line-height: 1.5;
    }
    
    /* MODERN BADGES */
    .badge-status, .badge-priority {
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-right: 8px;
        margin-bottom: 8px;
    }
    
    .badge-status::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }
    
    .badge-planning { background: #fef3c7; color: #92400e; }
    .badge-in_progress { background: #dbeafe; color: #1e40af; }
    .badge-on_hold { background: #fee2e2; color: #991b1b; }
    .badge-completed { background: #d1fae5; color: #065f46; }
    .badge-cancelled { background: #e5e7eb; color: #374151; }
    
    .badge-low { background: #d1fae5; color: #065f46; }
    .badge-medium { background: #fef3c7; color: #92400e; }
    .badge-high { background: #fed7aa; color: #9a3412; }
    .badge-critical { background: #fee2e2; color: #991b1b; }
    
    /* PROGRESS BAR */
    .progress-custom {
        height: 10px;
        margin-top: 16px;
        margin-bottom: 0;
        border-radius: 8px;
        background: #e5e7eb;
        overflow: hidden;
    }
    
    .progress-custom .progress-bar {
        background: linear-gradient(90deg, var(--primary), var(--secondary));
        line-height: 10px;
        font-size: 10px;
        font-weight: 700;
        transition: width 1s ease;
        border-radius: 8px;
    }
    
    /* CARD FOOTER */
    .card-footer-actions {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid var(--border);
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    /* ALERT */
    .alert {
        border-radius: 12px;
        padding: 18px 24px;
        border: 1px solid var(--border);
        margin-bottom: 24px;
        animation: fadeInUp 0.4s ease;
        font-weight: 500;
        font-size: 14px;
    }
    
    .alert-info {
        background: #dbeafe;
        color: #1e40af;
        border-color: #93c5fd;
    }
    
    .alert i {
        margin-right: 8px;
    }
    
    /* RESPONSIVE */
    @media (max-width: 1200px) {
        .projects-container { padding: 20px; }
        .page-header h1 { font-size: 28px; }
    }
    
    @media (max-width: 992px) {
        .filter-box .form-inline .form-group {
            display: block;
            width: 100%;
            margin-right: 0;
            margin-bottom: 12px;
        }
        .filter-box .form-control {
            width: 100%;
        }
        .project-card {
            padding: 24px;
        }
    }
    
    @media (max-width: 768px) {
        .projects-container { padding: 16px; }
        .page-header {
            padding: 24px 20px;
            margin-bottom: 24px;
        }
        .page-header h1 {
            font-size: 24px;
            margin-bottom: 16px;
        }
        .page-header .row > div {
            text-align: left !important;
        }
        .page-header .btn {
            margin-top: 12px;
            width: 100%;
        }
        .filter-box {
            padding: 20px;
        }
        .filter-box .btn {
            width: 100%;
            margin-bottom: 8px;
        }
        .project-card {
            padding: 20px;
            margin-bottom: 20px;
        }
    }
    
    @media (max-width: 480px) {
        .projects-container { padding: 12px; }
        .page-header { padding: 20px 16px; }
        .page-header h1 { font-size: 22px; }
        .filter-box { padding: 16px; }
        .project-card { padding: 18px; }
    }
</style>

<div class="projects-container container-fluid">
    <div class="page-header">
        <div class="row">
            <div class="col-md-8 col-xs-12">
                <h1><i class="fa fa-folder"></i> Projects</h1>
            </div>
            <div class="col-md-4 col-xs-12 text-right">
                <?php if ($auth->isManager()): ?>
                <a href="project-create.php" class="btn btn-primary">
                    <i class="fa fa-plus"></i> New Project
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="filter-box">
        <form method="GET" action="" class="form-inline" id="filterForm">
            <div class="form-group">
                <input type="text" name="search" class="form-control" placeholder="🔍 Search projects..." value="<?php echo htmlspecialchars($filters['search']); ?>" style="width: 250px;">
            </div>
            
            <div class="form-group">
                <select name="status" class="form-control" style="width: 150px;">
                    <option value="">All Status</option>
                    <option value="planning" <?php echo $filters['status'] === 'planning' ? 'selected' : ''; ?>>Planning</option>
                    <option value="in_progress" <?php echo $filters['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="on_hold" <?php echo $filters['status'] === 'on_hold' ? 'selected' : ''; ?>>On Hold</option>
                    <option value="completed" <?php echo $filters['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $filters['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <div class="form-group">
                <select name="priority" class="form-control" style="width: 150px;">
                    <option value="">All Priority</option>
                    <option value="low" <?php echo $filters['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                    <option value="medium" <?php echo $filters['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="high" <?php echo $filters['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                    <option value="critical" <?php echo $filters['priority'] === 'critical' ? 'selected' : ''; ?>>Critical</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-filter"></i> Filter
            </button>
            
            <a href="projects.php" class="btn btn-default">
                <i class="fa fa-refresh"></i> Reset
            </a>
        </form>
    </div>
    
    <?php if (empty($projects)): ?>
        <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No projects found. <?php if ($auth->isManager()): ?>Click "New Project" to create one.<?php endif; ?>
        </div>
    <?php else: ?>
    <div class="row" id="projectsGrid">
        <?php foreach ($projects as $index => $proj): ?>
        <div class="col-md-4 col-sm-6">
            <div class="project-card" style="animation-delay: <?php echo $index * 0.05; ?>s;">
                <h4>
                    <a href="project-detail.php?id=<?php echo $proj['id']; ?>">
                        <?php echo htmlspecialchars($proj['project_name']); ?>
                    </a>
                </h4>
                
                <div class="project-meta">
                    <p><?php echo htmlspecialchars(substr($proj['description'], 0, 100)); ?><?php echo strlen($proj['description']) > 100 ? '...' : ''; ?></p>
                    
                    <div style="margin-bottom: 16px;">
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
                        <i class="fa fa-tasks"></i> <strong><?php echo $proj['completed_tasks'] ?? 0; ?>/<?php echo $proj['task_count'] ?? 0; ?></strong> tasks completed
                    </small>
                    
                    <?php if (isset($proj['task_count']) && $proj['task_count'] > 0): ?>
                    <div class="progress progress-custom">
                        <?php $progress = round(($proj['completed_tasks'] / $proj['task_count']) * 100); ?>
                        <div class="progress-bar" role="progressbar" style="width: 0%" data-progress="<?php echo $progress; ?>">
                            <?php echo $progress; ?>%
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="card-footer-actions">
                    <a href="project-detail.php?id=<?php echo $proj['id']; ?>" class="btn btn-sm btn-primary">
                        <i class="fa fa-eye"></i> View Details
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

<script>
    $(document).ready(function() {
        // Animate progress bars
        setTimeout(function() {
            $('.progress-bar').each(function() {
                const $bar = $(this);
                const progress = $bar.attr('data-progress');
                $bar.css('width', progress + '%');
            });
        }, 300);
        
        // Staggered card animation
        $('.project-card').each(function(index) {
            $(this).css({
                'animation': `fadeInUp 0.4s ease ${index * 0.05}s both`
            });
        });
        
        // Real-time search
        let searchTimeout;
        $('input[name="search"]').on('keyup', function() {
            clearTimeout(searchTimeout);
            const searchTerm = $(this).val().toLowerCase();
            
            if (searchTerm.length === 0) {
                $('.project-card').parent().show();
                return;
            }
            
            searchTimeout = setTimeout(function() {
                $('.project-card').each(function() {
                    const cardText = $(this).text().toLowerCase();
                    if (cardText.indexOf(searchTerm) > -1) {
                        $(this).parent().fadeIn(200);
                    } else {
                        $(this).parent().fadeOut(200);
                    }
                });
            }, 300);
        });
        
        // Auto-submit on select change
        $('select[name="status"], select[name="priority"]').on('change', function() {
            $('#filterForm').submit();
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>