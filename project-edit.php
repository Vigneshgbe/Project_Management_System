<?php
ob_start(); // Fix header warning

$page_title = 'Edit Project';
require_once 'includes/header.php';
require_once 'components/project.php';

$auth->checkAccess('manager');

$project_id = $_GET['id'] ?? 0;
$project_obj = new Project();
$project = $project_obj->getById($project_id);

if (!$project) {
    ob_end_clean();
    header('Location: projects.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'project_name' => $_POST['project_name'],
        'description' => $_POST['description'],
        'client_name' => $_POST['client_name'],
        'start_date' => $_POST['start_date'] ?: null,
        'end_date' => $_POST['end_date'] ?: null,
        'status' => $_POST['status'],
        'priority' => $_POST['priority'],
        'budget' => $_POST['budget'] ?: 0
    ];
    
    if ($project_obj->update($project_id, $data)) {
        ob_end_clean(); // Clear buffer before redirect
        header('Location: project-detail.php?id=' . $project_id);
        exit;
    }
}
?>

<style>
    /* MODERN PROFESSIONAL DESIGN */
    
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
        --shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07);
        --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
    }
    
    .project-edit-container {
        padding: 24px;
        max-width: 1400px;
        margin: 0 auto;
        animation: fadeIn 0.4s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* PAGE HEADER */
    .page-header {
        background: white;
        padding: 32px;
        border-radius: 16px;
        margin-bottom: 32px;
        box-shadow: var(--shadow-md);
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
        margin: 0 0 8px 0;
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
    
    .page-breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 600;
        margin-top: 12px;
    }
    
    .page-breadcrumb a {
        color: var(--primary);
        text-decoration: none;
        transition: color 0.3s ease;
    }
    
    .page-breadcrumb a:hover {
        color: var(--primary-dark);
    }
    
    .page-breadcrumb span {
        color: #94a3b8;
    }
    
    .page-breadcrumb .current {
        color: #64748b;
    }
    
    /* FORM CARD */
    .form-card {
        background: white;
        border-radius: 16px;
        padding: 40px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        animation: fadeInUp 0.4s ease;
        margin-bottom: 24px;
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* INFO CARD */
    .info-card {
        background: white;
        border-radius: 16px;
        padding: 32px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        position: sticky;
        top: 20px;
        animation: fadeInUp 0.5s ease;
    }
    
    .info-card-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 24px;
        padding-bottom: 12px;
        border-bottom: 2px solid var(--border);
        display: flex;
        align-items: center;
        gap: 10px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }
    
    .info-card-title i {
        color: var(--primary);
        font-size: 16px;
    }
    
    .info-item {
        margin-bottom: 20px;
        padding: 14px 16px;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.03));
        border-radius: 10px;
        border-left: 3px solid var(--primary);
        transition: all 0.3s ease;
    }
    
    .info-item:hover {
        transform: translateX(4px);
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.05));
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.15);
    }
    
    .info-item:last-child {
        margin-bottom: 0;
    }
    
    .info-item strong {
        display: block;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        margin-bottom: 6px;
    }
    
    .info-item span {
        color: var(--dark);
        font-weight: 600;
        font-size: 14px;
    }
    
    /* SECTION TITLES */
    .form-section-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 24px;
        padding-bottom: 12px;
        border-bottom: 2px solid var(--border);
        display: flex;
        align-items: center;
        gap: 10px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }
    
    .form-section-title i {
        color: var(--primary);
        font-size: 16px;
    }
    
    .form-section-title:not(:first-child) {
        margin-top: 40px;
    }
    
    /* FORM GROUPS */
    .form-group-modern {
        margin-bottom: 24px;
    }
    
    .form-group-modern label {
        display: block;
        font-weight: 700;
        font-size: 11px;
        color: #64748b;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }
    
    .form-group-modern label .required {
        color: var(--danger);
        margin-left: 4px;
    }
    
    /* FORM CONTROLS */
    .form-control-modern {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid var(--border);
        border-radius: 10px;
        font-size: 15px;
        font-weight: 500;
        color: var(--dark);
        background: white;
        transition: all 0.3s ease;
    }
    
    .form-control-modern:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }
    
    .form-control-modern:hover {
        border-color: #cbd5e1;
    }
    
    .form-control-modern::placeholder {
        color: #94a3b8;
    }
    
    .form-control-modern:disabled {
        background: #f1f5f9;
        color: #64748b;
        cursor: not-allowed;
        opacity: 0.7;
    }
    
    textarea.form-control-modern {
        resize: vertical;
        min-height: 140px;
    }
    
    select.form-control-modern {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236366f1' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 16px center;
        padding-right: 40px;
    }
    
    /* FORM HINT */
    .form-hint {
        display: block;
        margin-top: 8px;
        font-size: 12px;
        color: #64748b;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .form-hint i {
        color: var(--primary);
        font-size: 13px;
    }
    
    /* STATUS & PRIORITY PREVIEW BADGES */
    .preview-badge-container {
        margin-top: 12px;
    }
    
    .badge-preview {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
    }
    
    .badge-preview::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.6; transform: scale(1.2); }
    }
    
    /* Status Badges */
    .status-planning { background: #fef3c7; color: #92400e; }
    .status-in_progress { background: #dbeafe; color: #1e40af; }
    .status-on_hold { background: #fee2e2; color: #991b1b; }
    .status-completed { background: #d1fae5; color: #065f46; }
    .status-cancelled { background: #e5e7eb; color: #374151; }
    
    /* Priority Badges */
    .priority-low { background: #d1fae5; color: #065f46; }
    .priority-medium { background: #fef3c7; color: #92400e; }
    .priority-high { background: #fed7aa; color: #9a3412; }
    .priority-critical { background: #fee2e2; color: #991b1b; }
    
    /* FORM ACTIONS */
    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 2px solid var(--border);
        flex-wrap: wrap;
    }
    
    /* BUTTONS */
    .btn-modern {
        padding: 12px 28px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }
    
    .btn-modern.primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
    }
    
    .btn-modern.primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(99, 102, 241, 0.35);
    }
    
    .btn-modern.secondary {
        background: white;
        color: var(--primary);
        border: 2px solid var(--primary);
    }
    
    .btn-modern.secondary:hover {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
        transform: translateY(-2px);
    }
    
    /* SMOOTH SCROLLBAR */
    ::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }
    
    ::-webkit-scrollbar-track {
        background: #f1f5f9;
    }
    
    ::-webkit-scrollbar-thumb {
        background: var(--primary);
        border-radius: 5px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: var(--primary-dark);
    }
    
    /* RESPONSIVE DESIGN */
    @media (max-width: 1200px) {
        .project-edit-container {
            padding: 20px;
        }
        .page-header {
            padding: 28px;
        }
        .page-header h1 {
            font-size: 28px;
        }
        .form-card {
            padding: 32px;
        }
        .info-card {
            padding: 28px;
        }
    }
    
    @media (max-width: 992px) {
        .form-card {
            padding: 28px;
        }
        .info-card {
            position: static;
            margin-top: 24px;
        }
    }
    
    @media (max-width: 768px) {
        .project-edit-container {
            padding: 16px;
        }
        .page-header {
            padding: 24px 20px;
            margin-bottom: 24px;
        }
        .page-header h1 {
            font-size: 24px;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        .page-breadcrumb {
            flex-wrap: wrap;
        }
        .form-card {
            padding: 24px;
        }
        .info-card {
            padding: 24px;
        }
        .form-actions {
            flex-direction: column;
        }
        .btn-modern {
            width: 100%;
            justify-content: center;
        }
    }
    
    @media (max-width: 480px) {
        .project-edit-container {
            padding: 12px;
        }
        .page-header {
            padding: 20px;
        }
        .page-header h1 {
            font-size: 20px;
        }
        .form-card {
            padding: 20px;
        }
        .info-card {
            padding: 20px;
        }
        .form-control-modern {
            padding: 12px 14px;
            font-size: 14px;
        }
        .form-section-title {
            font-size: 13px;
        }
    }
</style>

<div class="project-edit-container">
    <div class="page-header">
        <h1>
            <i class="fa fa-edit"></i> Edit Project
        </h1>
        <div class="page-breadcrumb">
            <a href="project-detail.php?id=<?php echo $project_id; ?>">
                <i class="fa fa-folder"></i> <?php echo htmlspecialchars($project['project_name']); ?>
            </a>
            <span>/</span>
            <span class="current">Edit</span>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="form-card">
                <form method="POST" action="" id="projectForm">
                    <div class="form-section-title">
                        <i class="fa fa-info-circle"></i> Basic Information
                    </div>
                    
                    <div class="form-group-modern">
                        <label for="project_name">
                            Project Name <span class="required">*</span>
                        </label>
                        <input type="text" 
                               class="form-control-modern" 
                               id="project_name" 
                               name="project_name" 
                               value="<?php echo htmlspecialchars($project['project_name']); ?>"
                               placeholder="Enter project name"
                               required>
                    </div>
                    
                    <div class="form-group-modern">
                        <label>Project Code</label>
                        <input type="text" 
                               class="form-control-modern" 
                               value="<?php echo htmlspecialchars($project['project_code']); ?>" 
                               disabled>
                        <span class="form-hint">
                            <i class="fa fa-lock"></i>
                            Project code cannot be changed
                        </span>
                    </div>
                    
                    <div class="form-group-modern">
                        <label for="client_name">Client Name</label>
                        <input type="text" 
                               class="form-control-modern" 
                               id="client_name" 
                               name="client_name" 
                               value="<?php echo htmlspecialchars($project['client_name']); ?>" 
                               placeholder="Enter client name">
                    </div>
                    
                    <div class="form-group-modern">
                        <label for="description">Description</label>
                        <textarea class="form-control-modern" 
                                  id="description" 
                                  name="description" 
                                  placeholder="Enter project description"><?php echo htmlspecialchars($project['description']); ?></textarea>
                    </div>
                    
                    <div class="form-section-title">
                        <i class="fa fa-calendar"></i> Timeline
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label for="start_date">Start Date</label>
                                <input type="date" 
                                       class="form-control-modern" 
                                       id="start_date" 
                                       name="start_date" 
                                       value="<?php echo $project['start_date']; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label for="end_date">End Date</label>
                                <input type="date" 
                                       class="form-control-modern" 
                                       id="end_date" 
                                       name="end_date" 
                                       value="<?php echo $project['end_date']; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section-title">
                        <i class="fa fa-cog"></i> Project Settings
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group-modern">
                                <label for="status">Status</label>
                                <select class="form-control-modern" id="status" name="status">
                                    <option value="planning" <?php echo $project['status'] === 'planning' ? 'selected' : ''; ?>>Planning</option>
                                    <option value="in_progress" <?php echo $project['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="on_hold" <?php echo $project['status'] === 'on_hold' ? 'selected' : ''; ?>>On Hold</option>
                                    <option value="completed" <?php echo $project['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $project['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                                <div class="preview-badge-container">
                                    <span class="badge-preview status-<?php echo $project['status']; ?>" id="statusPreview">
                                        <?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group-modern">
                                <label for="priority">Priority</label>
                                <select class="form-control-modern" id="priority" name="priority">
                                    <option value="low" <?php echo $project['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                                    <option value="medium" <?php echo $project['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="high" <?php echo $project['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                                    <option value="critical" <?php echo $project['priority'] === 'critical' ? 'selected' : ''; ?>>Critical</option>
                                </select>
                                <div class="preview-badge-container">
                                    <span class="badge-preview priority-<?php echo $project['priority']; ?>" id="priorityPreview">
                                        <?php echo ucfirst($project['priority']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group-modern">
                                <label for="budget">Budget ($)</label>
                                <input type="number" 
                                       class="form-control-modern" 
                                       id="budget" 
                                       name="budget" 
                                       step="0.01" 
                                       min="0" 
                                       value="<?php echo $project['budget']; ?>" 
                                       placeholder="0.00">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-modern primary">
                            <i class="fa fa-save"></i> Update Project
                        </button>
                        <a href="project-detail.php?id=<?php echo $project_id; ?>" class="btn-modern secondary">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="info-card">
                <div class="info-card-title">
                    <i class="fa fa-info-circle"></i> Project Information
                </div>
                
                <div class="info-item">
                    <strong>Created By:</strong>
                    <span><?php echo htmlspecialchars($project['creator_name']); ?></span>
                </div>
                
                <div class="info-item">
                    <strong>Created:</strong>
                    <span><?php echo date('M d, Y H:i', strtotime($project['created_at'])); ?></span>
                </div>
                
                <div class="info-item">
                    <strong>Last Updated:</strong>
                    <span><?php echo date('M d, Y H:i', strtotime($project['updated_at'])); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // STATUS BADGE PREVIEW
    $('#status').on('change', function() {
        const value = $(this).val();
        const $preview = $('#statusPreview');
        const labels = {
            'planning': 'Planning',
            'in_progress': 'In Progress',
            'on_hold': 'On Hold',
            'completed': 'Completed',
            'cancelled': 'Cancelled'
        };
        
        $preview.removeClass().addClass('badge-preview status-' + value);
        $preview.text(labels[value]);
    });
    
    // PRIORITY BADGE PREVIEW
    $('#priority').on('change', function() {
        const value = $(this).val();
        const $preview = $('#priorityPreview');
        const labels = {
            'low': 'Low',
            'medium': 'Medium',
            'high': 'High',
            'critical': 'Critical'
        };
        
        $preview.removeClass().addClass('badge-preview priority-' + value);
        $preview.text(labels[value]);
    });
    
    // FORM VALIDATION
    $('#projectForm').on('submit', function(e) {
        let isValid = true;
        
        $('.form-control-modern[required]').each(function() {
            if (!$(this).val().trim()) {
                isValid = false;
                $(this).css('border-color', '#ef4444');
                $(this).one('input', function() {
                    $(this).css('border-color', '#e2e8f0');
                });
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            $('html, body').animate({
                scrollTop: $('.form-control-modern[required]').filter(function() {
                    return !$(this).val().trim();
                }).first().offset().top - 100
            }, 300);
        }
    });
    
    // DATE VALIDATION
    $('#end_date').on('change', function() {
        const startDate = new Date($('#start_date').val());
        const endDate = new Date($(this).val());
        
        if (startDate && endDate && endDate < startDate) {
            alert('End date cannot be before start date.');
            $(this).val('');
            $(this).css('border-color', '#ef4444');
            setTimeout(() => {
                $(this).css('border-color', '#e2e8f0');
            }, 2000);
        }
    });
    
    $('#start_date').on('change', function() {
        const startDate = new Date($(this).val());
        const endDate = new Date($('#end_date').val());
        
        if (startDate && endDate && endDate < startDate) {
            alert('Start date cannot be after end date.');
            $(this).val('');
            $(this).css('border-color', '#ef4444');
            setTimeout(() => {
                $(this).css('border-color', '#e2e8f0');
            }, 2000);
        }
    });
    
    // INPUT FOCUS EFFECTS
    $('.form-control-modern').on('focus', function() {
        $(this).closest('.form-group-modern').find('label').css({
            'color': '#6366f1',
            'transition': 'color 0.3s ease'
        });
    }).on('blur', function() {
        $(this).closest('.form-group-modern').find('label').css({
            'color': '#64748b'
        });
    });
    
    // BADGE HOVER EFFECTS
    $('.badge-preview').hover(
        function() {
            $(this).css('transform', 'translateY(-2px) scale(1.05)');
        },
        function() {
            $(this).css('transform', 'translateY(0) scale(1)');
        }
    );
});
</script>

<?php require_once 'includes/footer.php'; ?>