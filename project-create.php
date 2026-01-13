<?php
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
        header('Location: project-detail.php?id=' . $project_id);
        exit;
    } else {
        $error = 'Failed to create project. Please check if project code already exists.';
    }
}
?>

<style>
    .project-create-container {
        background: transparent !important;
        min-height: calc(100vh - 100px) !important;
        padding: 20px !important;
        margin: 0 !important;
    }
    
    .project-create-header {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        color: #1e293b !important;
        padding: 30px 35px !important;
        border-radius: 20px !important;
        margin-bottom: 30px !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        position: relative !important;
        overflow: hidden !important;
    }
    
    .project-create-header::before {
        content: '' !important;
        position: absolute !important;
        top: -50% !important;
        right: -50% !important;
        width: 200% !important;
        height: 200% !important;
        background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%) !important;
        animation: rotate 20s linear infinite !important;
    }
    
    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .project-create-header h1 {
        margin: 0 !important;
        font-weight: 800 !important;
        font-size: 32px !important;
        position: relative !important;
        z-index: 1 !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
        display: flex !important;
        align-items: center !important;
        gap: 15px !important;
    }
    
    .project-create-header h1 i {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }
    
    .project-create-breadcrumb {
        margin-top: 12px !important;
        position: relative !important;
        z-index: 1 !important;
    }
    
    .project-create-breadcrumb a {
        color: #667eea !important;
        text-decoration: none !important;
        font-weight: 600 !important;
        transition: color 0.3s ease !important;
    }
    
    .project-create-breadcrumb a:hover {
        color: #764ba2 !important;
    }
    
    .project-create-breadcrumb span {
        color: #64748b !important;
        margin: 0 8px !important;
    }
    
    .alert-modern {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%) !important;
        border-left: 4px solid #ef4444 !important;
        border-radius: 12px !important;
        padding: 15px 20px !important;
        margin-bottom: 25px !important;
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        color: #991b1b !important;
        font-weight: 600 !important;
    }
    
    .alert-modern i {
        font-size: 20px !important;
        color: #ef4444 !important;
    }
    
    .form-card {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        border-radius: 20px !important;
        padding: 35px !important;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        margin-bottom: 25px !important;
    }
    
    .info-card {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        border-radius: 20px !important;
        padding: 30px !important;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        position: sticky !important;
        top: 20px !important;
    }
    
    .info-card-title {
        font-size: 18px !important;
        font-weight: 700 !important;
        color: #1e293b !important;
        margin-bottom: 20px !important;
        padding-bottom: 15px !important;
        border-bottom: 3px solid transparent !important;
        border-image: linear-gradient(90deg, #667eea 0%, #764ba2 100%) !important;
        border-image-slice: 1 !important;
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
    }
    
    .info-card-title i {
        color: #667eea !important;
    }
    
    .info-section {
        margin-bottom: 25px !important;
    }
    
    .info-section:last-child {
        margin-bottom: 0 !important;
    }
    
    .info-section-title {
        font-weight: 700 !important;
        color: #1e293b !important;
        font-size: 14px !important;
        margin-bottom: 10px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }
    
    .info-section p {
        color: #64748b !important;
        font-weight: 500 !important;
        font-size: 14px !important;
        line-height: 1.6 !important;
        margin: 0 0 8px 0 !important;
    }
    
    .info-section ul {
        margin: 0 !important;
        padding-left: 20px !important;
    }
    
    .info-section li {
        color: #64748b !important;
        font-weight: 500 !important;
        font-size: 14px !important;
        margin-bottom: 8px !important;
        line-height: 1.6 !important;
    }
    
    .info-section li strong {
        color: #1e293b !important;
    }
    
    .form-section-title {
        font-size: 18px !important;
        font-weight: 700 !important;
        color: #1e293b !important;
        margin-bottom: 25px !important;
        padding-bottom: 15px !important;
        border-bottom: 3px solid transparent !important;
        border-image: linear-gradient(90deg, #667eea 0%, #764ba2 100%) !important;
        border-image-slice: 1 !important;
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
    }
    
    .form-section-title i {
        color: #667eea !important;
    }
    
    .form-group-modern {
        margin-bottom: 25px !important;
    }
    
    .form-group-modern label {
        display: block !important;
        font-weight: 700 !important;
        font-size: 14px !important;
        color: #1e293b !important;
        margin-bottom: 10px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }
    
    .form-group-modern label .required {
        color: #ef4444 !important;
        margin-left: 4px !important;
    }
    
    .form-control-modern {
        width: 100% !important;
        padding: 14px 18px !important;
        border: 2px solid #e2e8f0 !important;
        border-radius: 12px !important;
        font-size: 15px !important;
        font-weight: 500 !important;
        color: #1e293b !important;
        background: white !important;
        transition: all 0.3s ease !important;
    }
    
    .form-control-modern:focus {
        outline: none !important;
        border-color: #667eea !important;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1) !important;
    }
    
    .form-control-modern:hover {
        border-color: #cbd5e1 !important;
    }
    
    .form-control-modern::placeholder {
        color: #94a3b8 !important;
    }
    
    textarea.form-control-modern {
        resize: vertical !important;
        min-height: 120px !important;
    }
    
    select.form-control-modern {
        appearance: none !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23667eea' d='M6 9L1 4h10z'/%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important;
        background-position: right 18px center !important;
        padding-right: 45px !important;
    }
    
    .input-icon-wrapper {
        position: relative !important;
    }
    
    .input-icon-wrapper i {
        position: absolute !important;
        left: 18px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        color: #667eea !important;
        font-size: 16px !important;
    }
    
    .input-icon-wrapper .form-control-modern {
        padding-left: 45px !important;
    }
    
    .form-hint {
        display: block !important;
        margin-top: 8px !important;
        font-size: 13px !important;
        color: #64748b !important;
        font-weight: 500 !important;
    }
    
    .form-hint i {
        margin-right: 5px !important;
    }
    
    .priority-badge-preview, .status-badge-preview {
        display: inline-flex !important;
        align-items: center !important;
        padding: 6px 14px !important;
        border-radius: 20px !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        margin-top: 8px !important;
    }
    
    .priority-badge-preview.low {
        background: linear-gradient(135deg, rgba(107, 114, 128, 0.15) 0%, rgba(75, 85, 99, 0.15) 100%) !important;
        color: #4b5563 !important;
    }
    
    .priority-badge-preview.medium {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(37, 99, 235, 0.15) 100%) !important;
        color: #2563eb !important;
    }
    
    .priority-badge-preview.high {
        background: linear-gradient(135deg, rgba(251, 191, 36, 0.15) 0%, rgba(245, 158, 11, 0.15) 100%) !important;
        color: #f59e0b !important;
    }
    
    .priority-badge-preview.critical {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(220, 38, 38, 0.15) 100%) !important;
        color: #dc2626 !important;
    }
    
    .status-badge-preview.planning {
        background: linear-gradient(135deg, rgba(107, 114, 128, 0.15) 0%, rgba(75, 85, 99, 0.15) 100%) !important;
        color: #4b5563 !important;
    }
    
    .status-badge-preview.in_progress {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(37, 99, 235, 0.15) 100%) !important;
        color: #2563eb !important;
    }
    
    .status-badge-preview.on_hold {
        background: linear-gradient(135deg, rgba(251, 191, 36, 0.15) 0%, rgba(245, 158, 11, 0.15) 100%) !important;
        color: #f59e0b !important;
    }
    
    .status-badge-preview.completed {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(5, 150, 105, 0.15) 100%) !important;
        color: #059669 !important;
    }
    
    .status-badge-preview.cancelled {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(220, 38, 38, 0.15) 100%) !important;
        color: #dc2626 !important;
    }
    
    .form-actions {
        display: flex !important;
        gap: 15px !important;
        margin-top: 35px !important;
        padding-top: 30px !important;
        border-top: 2px solid #e2e8f0 !important;
        flex-wrap: wrap !important;
    }
    
    .btn-modern {
        padding: 14px 32px !important;
        border-radius: 12px !important;
        font-weight: 700 !important;
        font-size: 14px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        transition: all 0.3s ease !important;
        border: none !important;
        cursor: pointer !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 10px !important;
        text-decoration: none !important;
    }
    
    .btn-modern.primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3) !important;
    }
    
    .btn-modern.primary:hover {
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4) !important;
    }
    
    .btn-modern.secondary {
        background: white !important;
        color: #667eea !important;
        border: 2px solid #667eea !important;
    }
    
    .btn-modern.secondary:hover {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%) !important;
        transform: translateY(-2px) !important;
    }
    
    @media (max-width: 1200px) {
        .project-create-container {
            padding: 15px !important;
        }
        .project-create-header, .form-card, .info-card {
            padding: 25px 30px !important;
        }
    }
    
    @media (max-width: 992px) {
        .info-card {
            position: static !important;
            margin-top: 25px !important;
        }
    }
    
    @media (max-width: 768px) {
        .project-create-container {
            padding: 10px !important;
        }
        .project-create-header {
            padding: 20px !important;
            margin-bottom: 20px !important;
        }
        .project-create-header h1 {
            font-size: 24px !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 10px !important;
        }
        .form-card, .info-card {
            padding: 20px !important;
        }
        .form-actions {
            flex-direction: column !important;
        }
        .btn-modern {
            width: 100% !important;
            justify-content: center !important;
        }
    }
    
    @media (max-width: 480px) {
        .project-create-container {
            padding: 8px !important;
        }
        .project-create-header h1 {
            font-size: 20px !important;
        }
        .form-card, .info-card {
            padding: 15px !important;
        }
        .form-control-modern {
            padding: 12px 16px !important;
            font-size: 14px !important;
        }
        .input-icon-wrapper .form-control-modern {
            padding-left: 40px !important;
        }
    }
</style>

<div class="project-create-container container-fluid">
    <div class="project-create-header">
        <h1>
            <i class="fa fa-folder-plus"></i> Create New Project
        </h1>
        <div class="project-create-breadcrumb">
            <a href="projects.php"><i class="fa fa-folder"></i> Projects</a>
            <span>/</span>
            <span style="color: #1e293b; font-weight: 600;">Create Project</span>
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
                    
                    <div class="form-section-title" style="margin-top: 35px;">
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
                    
                    <div class="form-section-title" style="margin-top: 35px;">
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
                                <span class="status-badge-preview planning" id="statusPreview">Planning</span>
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
                                <span class="priority-badge-preview medium" id="priorityPreview">Medium</span>
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
        const status = $(this).val();
        const statusText = $(this).find('option:selected').text();
        const $preview = $('#statusPreview');
        
        $preview.removeClass('planning in_progress on_hold completed cancelled');
        $preview.addClass(status);
        $preview.text(statusText);
    });
    
    // PRIORITY BADGE PREVIEW UPDATE
    $('#priority').on('change', function() {
        const priority = $(this).val();
        const priorityText = $(this).find('option:selected').text();
        const $preview = $('#priorityPreview');
        
        $preview.removeClass('low medium high critical');
        $preview.addClass(priority);
        $preview.text(priorityText);
    });
    
    // DATE VALIDATION
    $('#end_date').on('change', function() {
        const startDate = $('#start_date').val();
        const endDate = $(this).val();
        
        if (startDate && endDate && endDate < startDate) {
            alert('End date cannot be before start date.');
            $(this).val('');
        }
    });
    
    // INPUT FOCUS EFFECTS
    $('.form-control-modern').on('focus', function() {
        $(this).closest('.form-group-modern').find('label').css({
            'color': '#667eea',
            'transition': 'color 0.3s ease'
        });
    }).on('blur', function() {
        $(this).closest('.form-group-modern').find('label').css({
            'color': '#1e293b'
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
        }
    });
    
    // PROJECT CODE AUTO-UPPERCASE
    $('#project_code').on('input', function() {
        $(this).val($(this).val().toUpperCase());
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>