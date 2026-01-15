<?php
ob_start(); // Fix header warning

$page_title = 'Create Project';
require_once 'includes/header.php';
require_once 'components/project.php';

$auth->checkAccess('manager');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project = new Project();
    
    $data = [
        'project_name' => $_POST['project_name'] ?? '',
        'project_code' => $_POST['project_code'] ?? '',
        'description' => $_POST['description'] ?? '',
        'client_name' => $_POST['client_name'] ?? '',
        'start_date' => $_POST['start_date'] ?? null,
        'end_date' => $_POST['end_date'] ?? null,
        'status' => $_POST['status'] ?? 'planning',
        'priority' => $_POST['priority'] ?? 'medium',
        'budget' => $_POST['budget'] ?? 0,
        'created_by' => $auth->getUserId()
    ];
    
    if ($project_id = $project->create($data)) {
        ob_end_clean(); // Clear buffer before redirect
        header('Location: project-detail.php?id=' . $project_id);
        exit;
    } else {
        $error = 'Failed to create project. Please check if project code already exists.';
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
    
    .project-create-container {
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
    
    /* ALERT */
    .alert-modern {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.05));
        border-left: 4px solid var(--danger);
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #991b1b;
        font-weight: 600;
        animation: slideIn 0.3s ease;
    }
    
    @keyframes slideIn {
        from { opacity: 0; transform: translateX(-20px); }
        to { opacity: 1; transform: translateX(0); }
    }
    
    .alert-modern i {
        font-size: 20px;
        color: var(--danger);
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
    
    .info-section {
        margin-bottom: 24px;
    }
    
    .info-section:last-child {
        margin-bottom: 0;
    }
    
    .info-section-title {
        font-weight: 700;
        color: var(--dark);
        font-size: 12px;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }
    
    .info-section p {
        color: #64748b;
        font-weight: 500;
        font-size: 13px;
        line-height: 1.6;
        margin: 0 0 8px 0;
    }
    
    .info-section ul {
        margin: 0;
        padding-left: 20px;
    }
    
    .info-section li {
        color: #64748b;
        font-weight: 500;
        font-size: 13px;
        margin-bottom: 8px;
        line-height: 1.6;
    }
    
    .info-section li strong {
        color: var(--dark);
        font-weight: 700;
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
    
    /* INPUT WITH ICONS */
    .input-icon-wrapper {
        position: relative;
    }
    
    .input-icon-wrapper i {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--primary);
        font-size: 14px;
    }
    
    .input-icon-wrapper .form-control-modern {
        padding-left: 42px;
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
    
    /* PREVIEW BADGES */
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
        .project-create-container {
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
        .project-create-container {
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
        .project-create-container {
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
        .input-icon-wrapper .form-control-modern {
            padding-left: 38px;
        }
        .form-section-title {
            font-size: 13px;
        }
    }
</style>

<div class="project-create-container">
    <div class="page-header">
        <h1>
            <i class="fa fa-folder-plus"></i> Create New Project
        </h1>
        <div class="page-breadcrumb">
            <a href="projects.php">
                <i class="fa fa-folder"></i> Projects
            </a>
            <span>/</span>
            <span class="current">Create Project</span>
        </div>
    </div>
    
    <?php if ($error): ?>
    <div class="alert-modern">
        <i class="fa fa-exclamation-circle"></i>
        <span><?php echo htmlspecialchars($error); ?></span>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="form-card">
                <form method="POST" action="" id="createProjectForm">
                    <div class="form-section-title">
                        <i class="fa fa-info-circle"></i> Basic Information
                    </div>
                    
                    <div class="form-group-modern">
                        <label for="project_name">
                            Project Name <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="fa fa-folder"></i>
                            <input type="text" 
                                   class="form-control-modern" 
                                   id="project_name" 
                                   name="project_name" 
                                   placeholder="Enter project name"
                                   required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label for="project_code">
                                    Project Code <span class="required">*</span>
                                </label>
                                <div class="input-icon-wrapper">
                                    <i class="fa fa-hashtag"></i>
                                    <input type="text" 
                                           class="form-control-modern" 
                                           id="project_code" 
                                           name="project_code" 
                                           placeholder="e.g., PROJ-001"
                                           required>
                                </div>
                                <span class="form-hint">
                                    <i class="fa fa-info-circle"></i>
                                    Must be unique
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label for="client_name">Client Name</label>
                                <div class="input-icon-wrapper">
                                    <i class="fa fa-building"></i>
                                    <input type="text" 
                                           class="form-control-modern" 
                                           id="client_name" 
                                           name="client_name" 
                                           placeholder="Enter client name">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group-modern">
                        <label for="description">Description</label>
                        <textarea class="form-control-modern" 
                                  id="description" 
                                  name="description" 
                                  placeholder="Enter project description"></textarea>
                    </div>
                    
                    <div class="form-section-title">
                        <i class="fa fa-calendar"></i> Timeline
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label for="start_date">Start Date</label>
                                <div class="input-icon-wrapper">
                                    <i class="fa fa-calendar-alt"></i>
                                    <input type="date" 
                                           class="form-control-modern" 
                                           id="start_date" 
                                           name="start_date">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label for="end_date">End Date</label>
                                <div class="input-icon-wrapper">
                                    <i class="fa fa-calendar-check"></i>
                                    <input type="date" 
                                           class="form-control-modern" 
                                           id="end_date" 
                                           name="end_date">
                                </div>
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
                                    <option value="planning">Planning</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="on_hold">On Hold</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                <div class="preview-badge-container">
                                    <span class="badge-preview status-planning" id="statusPreview">Planning</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group-modern">
                                <label for="priority">Priority</label>
                                <select class="form-control-modern" id="priority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                                <div class="preview-badge-container">
                                    <span class="badge-preview priority-medium" id="priorityPreview">Medium</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group-modern">
                                <label for="budget">Budget ($)</label>
                                <div class="input-icon-wrapper">
                                    <i class="fa fa-dollar-sign"></i>
                                    <input type="number" 
                                           class="form-control-modern" 
                                           id="budget" 
                                           name="budget" 
                                           step="0.01" 
                                           min="0"
                                           placeholder="0.00">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-modern primary">
                            <i class="fa fa-save"></i> Create Project
                        </button>
                        <a href="projects.php" class="btn-modern secondary">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="info-card">
                <div class="info-card-title">
                    <i class="fa fa-lightbulb"></i> Quick Guide
                </div>
                
                <div class="info-section">
                    <div class="info-section-title">Project Code Format</div>
                    <p>Use a unique identifier like:</p>
                    <p><strong>PROJ-001, WEB-2024, APP-001</strong></p>
                </div>
                
                <div class="info-section">
                    <div class="info-section-title">Status Definitions</div>
                    <ul>
                        <li><strong>Planning:</strong> Initial planning phase</li>
                        <li><strong>In Progress:</strong> Active development</li>
                        <li><strong>On Hold:</strong> Temporarily paused</li>
                        <li><strong>Completed:</strong> Project finished</li>
                        <li><strong>Cancelled:</strong> Project terminated</li>
                    </ul>
                </div>
                
                <div class="info-section">
                    <div class="info-section-title">Priority Levels</div>
                    <ul>
                        <li><strong>Low:</strong> Non-urgent tasks</li>
                        <li><strong>Medium:</strong> Standard priority</li>
                        <li><strong>High:</strong> Important projects</li>
                        <li><strong>Critical:</strong> Urgent attention required</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // STATUS BADGE PREVIEW UPDATE
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
    
    // PRIORITY BADGE PREVIEW UPDATE
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
    
    // FORM VALIDATION
    $('#createProjectForm').on('submit', function(e) {
        let isValid = true;
        
        $('.form-control-modern[required]').each(function() {
            if ($(this).val().trim() === '') {
                isValid = false;
                $(this).css('border-color', '#ef4444');
                $(this).on('input', function() {
                    $(this).css('border-color', '#e2e8f0');
                });
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            $('html, body').animate({
                scrollTop: $('.form-control-modern[required]').filter(function() {
                    return $(this).val().trim() === '';
                }).first().offset().top - 100
            }, 300);
        }
    });
    
    // PROJECT CODE AUTO-UPPERCASE
    $('#project_code').on('input', function() {
        $(this).val($(this).val().toUpperCase());
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