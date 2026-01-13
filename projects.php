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
    .projects-container {
        background: transparent !important;
        min-height: calc(100vh - 100px) !important;
        padding: 20px !important;
        margin: 0 !important;
        animation: fadeIn 0.5s ease !important;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        padding: 35px 40px !important;
        border-radius: 20px !important;
        margin-bottom: 30px !important;
        box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3) !important;
        border: none !important;
        animation: slideDown 0.6s ease !important;
        position: relative !important;
        overflow: hidden !important;
    }
    
    .page-header::before {
        content: '' !important;
        position: absolute !important;
        top: -50% !important;
        right: -50% !important;
        width: 200% !important;
        height: 200% !important;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%) !important;
        animation: rotate 20s linear infinite !important;
    }
    
    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .page-header h1 {
        margin: 0 !important;
        font-weight: 800 !important;
        font-size: 32px !important;
        position: relative !important;
        z-index: 1 !important;
    }
    
    .page-header .btn {
        position: relative !important;
        z-index: 1 !important;
    }
    
    .filter-box {
        background: white !important;
        padding: 25px 30px !important;
        border-radius: 20px !important;
        margin-bottom: 30px !important;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08) !important;
        animation: slideUp 0.5s ease !important;
    }
    
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .filter-box .form-control {
        border: 2px solid #e5e7eb !important;
        border-radius: 12px !important;
        padding: 12px 18px !important;
        font-size: 14px !important;
        font-weight: 500 !important;
        transition: all 0.3s ease !important;
        height: auto !important;
    }
    
    .filter-box .form-control:focus {
        border-color: #667eea !important;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1) !important;
        outline: none !important;
    }
    
    .filter-box .form-group {
        margin-right: 15px !important;
        margin-bottom: 10px !important;
    }
    
    .filter-box .btn {
        padding: 12px 24px !important;
        border-radius: 12px !important;
        font-weight: 700 !important;
        font-size: 14px !important;
        transition: all 0.3s ease !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        border: none !important;
        color: white !important;
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3) !important;
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4) !important;
        color: white !important;
    }
    
    .btn-default {
        background: white !important;
        color: #667eea !important;
        border: 2px solid #667eea !important;
    }
    
    .btn-default:hover {
        background: #667eea !important;
        color: white !important;
        border-color: #667eea !important;
        transform: translateY(-2px) !important;
    }
    
    .btn-sm {
        padding: 8px 16px !important;
        font-size: 13px !important;
        border-radius: 10px !important;
    }
    
    .project-card {
        background: white !important;
        border: none !important;
        border-radius: 20px !important;
        padding: 30px !important;
        margin-bottom: 25px !important;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08) !important;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        position: relative !important;
        overflow: hidden !important;
        animation: cardSlideUp 0.5s ease !important;
        height: 100% !important;
    }
    
    @keyframes cardSlideUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .project-card::before {
        content: '' !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 5px !important;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%) !important;
        transform: scaleX(0) !important;
        transition: transform 0.4s ease !important;
    }
    
    .project-card:hover::before {
        transform: scaleX(1) !important;
    }
    
    .project-card:hover {
        transform: translateY(-10px) !important;
        box-shadow: 0 15px 45px rgba(102, 126, 234, 0.2) !important;
    }
    
    .project-card h4 {
        margin-top: 0 !important;
        color: #1e293b !important;
        font-weight: 800 !important;
        font-size: 20px !important;
        margin-bottom: 15px !important;
        line-height: 1.4 !important;
    }
    
    .project-card h4 a {
        color: #1e293b !important;
        text-decoration: none !important;
        transition: color 0.3s ease !important;
    }
    
    .project-card h4 a:hover {
        color: #667eea !important;
    }
    
    .project-meta {
        color: #64748b !important;
        font-size: 14px !important;
        line-height: 1.6 !important;
    }
    
    .project-meta p {
        color: #475569 !important;
        margin-bottom: 15px !important;
        font-weight: 500 !important;
    }
    
    .badge-status {
        padding: 6px 14px !important;
        border-radius: 20px !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 5px !important;
        margin-right: 8px !important;
        margin-bottom: 8px !important;
    }
    
    .badge-planning { 
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
        color: white !important;
        box-shadow: 0 3px 12px rgba(251, 191, 36, 0.3) !important;
    }
    
    .badge-in_progress { 
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
        color: white !important;
        box-shadow: 0 3px 12px rgba(59, 130, 246, 0.3) !important;
    }
    
    .badge-on_hold { 
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        color: white !important;
        box-shadow: 0 3px 12px rgba(239, 68, 68, 0.3) !important;
    }
    
    .badge-completed { 
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        color: white !important;
        box-shadow: 0 3px 12px rgba(16, 185, 129, 0.3) !important;
    }
    
    .badge-cancelled { 
        background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%) !important;
        color: white !important;
        box-shadow: 0 3px 12px rgba(107, 114, 128, 0.3) !important;
    }
    
    .badge-priority {
        padding: 6px 14px !important;
        border-radius: 20px !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        margin-bottom: 8px !important;
    }
    
    .badge-low { 
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        color: white !important;
    }
    
    .badge-medium { 
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
        color: white !important;
    }
    
    .badge-high { 
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important;
        color: white !important;
    }
    
    .badge-critical { 
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        color: white !important;
        animation: pulse 2s infinite !important;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.8; transform: scale(1.05); }
    }
    
    .progress-custom {
        height: 12px !important;
        margin-top: 15px !important;
        margin-bottom: 0 !important;
        border-radius: 10px !important;
        background: #e5e7eb !important;
        overflow: hidden !important;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1) !important;
    }
    
    .progress-custom .progress-bar {
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%) !important;
        line-height: 12px !important;
        font-size: 10px !important;
        font-weight: 700 !important;
        transition: width 1s ease !important;
        border-radius: 10px !important;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4) !important;
    }
    
    .alert {
        border-radius: 16px !important;
        padding: 20px 25px !important;
        border: none !important;
        margin-bottom: 25px !important;
        animation: slideDown 0.5s ease !important;
        font-weight: 500 !important;
        font-size: 15px !important;
    }
    
    .alert-info {
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%) !important;
        color: #1e40af !important;
    }
    
    .col-md-4, .col-sm-6 {
        margin-bottom: 0 !important;
    }
    
    /* RESPONSIVE BREAKPOINTS */
    @media (max-width: 1200px) {
        .projects-container {
            padding: 15px !important;
        }
        .page-header {
            padding: 25px 30px !important;
        }
        .page-header h1 {
            font-size: 28px !important;
        }
    }
    
    @media (max-width: 992px) {
        .filter-box .form-inline .form-group {
            display: block !important;
            width: 100% !important;
            margin-right: 0 !important;
            margin-bottom: 15px !important;
        }
        .filter-box .form-control {
            width: 100% !important;
        }
        .project-card {
            padding: 25px !important;
        }
    }
    
    @media (max-width: 768px) {
        .projects-container {
            padding: 10px !important;
        }
        .page-header {
            padding: 20px !important;
            margin-bottom: 20px !important;
        }
        .page-header h1 {
            font-size: 24px !important;
            margin-bottom: 15px !important;
        }
        .filter-box {
            padding: 20px !important;
        }
        .filter-box .btn {
            width: 100% !important;
            margin-bottom: 10px !important;
        }
        .project-card {
            padding: 20px !important;
            margin-bottom: 20px !important;
        }
    }
    
    @media (max-width: 480px) {
        .projects-container {
            padding: 8px !important;
        }
        .page-header {
            padding: 15px !important;
        }
        .page-header h1 {
            font-size: 20px !important;
        }
        .filter-box {
            padding: 15px !important;
        }
        .project-card {
            padding: 15px !important;
        }
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
        <div class="col-md-4 col-sm-6" style="animation-delay: <?php echo $index * 0.1; ?>s;">
            <div class="project-card">
                <h4>
                    <a href="project-detail.php?id=<?php echo $proj['id']; ?>">
                        <?php echo htmlspecialchars($proj['project_name']); ?>
                    </a>
                </h4>
                
                <div class="project-meta">
                    <p><?php echo htmlspecialchars(substr($proj['description'], 0, 100)); ?><?php echo strlen($proj['description']) > 100 ? '...' : ''; ?></p>
                    
                    <div style="margin-bottom: 15px;">
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
                
                <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #f1f5f9;">
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
        $('.progress-bar').each(function() {
            const $bar = $(this);
            const progress = $bar.attr('data-progress');
            setTimeout(function() {
                $bar.css('width', progress + '%');
            }, 500);
        });
        
        // Staggered card animation
        $('.project-card').each(function(index) {
            $(this).css({
                'animation-delay': (index * 0.1) + 's'
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
                        $(this).parent().fadeIn(300);
                    } else {
                        $(this).parent().fadeOut(300);
                    }
                });
            }, 300);
        });
        
        // Auto-submit on select change
        $('select[name="status"], select[name="priority"]').on('change', function() {
            $('#filterForm').submit();
        });
        
        // Add hover effect to cards
        $('.project-card').hover(
            function() {
                $(this).find('.btn').addClass('btn-hover');
            },
            function() {
                $(this).find('.btn').removeClass('btn-hover');
            }
        );
        
        // Smooth scroll
        $('html').css('scroll-behavior', 'smooth');
    });
</script>

<?php require_once 'includes/footer.php'; ?>