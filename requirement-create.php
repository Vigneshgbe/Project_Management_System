<?php
ob_start(); // Fix header warning

$page_title = 'Create Requirement';
require_once 'includes/header.php';
require_once 'components/requirement.php';

$auth->checkAccess('manager');

$project_id = $_GET['project_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $req = new Requirement();
    
    $data = [
        'project_id' => $_POST['project_id'],
        'requirement_title' => $_POST['requirement_title'],
        'description' => $_POST['description'],
        'type' => $_POST['type'],
        'priority' => $_POST['priority'],
        'status' => $_POST['status'],
        'created_by' => $auth->getUserId()
    ];
    
    if ($req->create($data)) {
        ob_end_clean(); // Clear buffer before redirect
        header('Location: project-detail.php?id=' . $_POST['project_id'] . '&tab=requirements');
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
    
    .requirement-create-container {
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
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
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
    
    .input-icon-wrapper.textarea-wrapper i {
        top: 18px;
        transform: none;
    }
    
    .input-icon-wrapper.select-wrapper i {
        pointer-events: none;
        z-index: 1;
    }
    
    /* CHARACTER COUNTER */
    .char-counter {
        font-size: 11px;
        color: #94a3b8;
        font-weight: 600;
        margin-top: 6px;
        text-align: right;
    }
    
    .char-counter.warning {
        color: var(--warning);
    }
    
    .char-counter.danger {
        color: var(--danger);
    }
    
    /* INFO CARD */
    .requirement-info-card {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(37, 99, 235, 0.05));
        border-left: 4px solid #3b82f6;
        padding: 16px 20px;
        border-radius: 12px;
        margin-top: 24px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }
    
    .requirement-info-card i {
        color: #3b82f6;
        font-size: 18px;
        flex-shrink: 0;
        margin-top: 2px;
    }
    
    .requirement-info-card strong {
        color: var(--dark);
        font-weight: 700;
        font-size: 13px;
    }
    
    .requirement-info-card .info-text {
        color: #64748b;
        font-weight: 500;
        font-size: 13px;
    }
    
    /* PREVIEW BADGES */
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
        margin-top: 8px;
    }
    
    /* Type Badges */
    .type-functional { background: #dbeafe; color: #1e40af; }
    .type-non_functional { background: #f3e8ff; color: #6b21a8; }
    .type-technical { background: #cffafe; color: #0e7490; }
    .type-business { background: #fed7aa; color: #9a3412; }
    
    /* Priority Badges */
    .priority-low { background: #d1fae5; color: #065f46; }
    .priority-medium { background: #fef3c7; color: #92400e; }
    .priority-high { background: #fed7aa; color: #9a3412; }
    .priority-critical { background: #fee2e2; color: #991b1b; }
    
    /* Status Badges */
    .status-pending { background: #f1f5f9; color: #475569; }
    .status-approved { background: #d1fae5; color: #065f46; }
    .status-in_progress { background: #dbeafe; color: #1e40af; }
    .status-completed { background: #f3e8ff; color: #6b21a8; }
    .status-rejected { background: #fee2e2; color: #991b1b; }
    
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
        .requirement-create-container {
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
    }
    
    @media (max-width: 992px) {
        .form-card {
            padding: 28px;
        }
    }
    
    @media (max-width: 768px) {
        .requirement-create-container {
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
        .form-actions {
            flex-direction: column;
        }
        .btn-modern {
            width: 100%;
            justify-content: center;
        }
    }
    
    @media (max-width: 480px) {
        .requirement-create-container {
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

<div class="requirement-create-container">
    <div class="page-header">
        <h1>
            <i class="fa fa-plus-circle"></i> Create Requirement
        </h1>
        <div class="page-breadcrumb">
            <a href="project-detail.php?id=<?php echo $project_id; ?>&tab=requirements">
                <i class="fa fa-list-alt"></i> Project Requirements
            </a>
            <span>/</span>
            <span class="current">Create Requirement</span>
        </div>
    </div>
    
    <div class="form-card">
        <form method="POST" action="" id="requirementForm">
            <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
            
            <!-- BASIC INFORMATION -->
            <div class="form-section-title">
                <i class="fa fa-file-text"></i> Basic Information
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group-modern">
                        <label for="requirement_title">
                            Requirement Title <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="fa fa-pencil"></i>
                            <input type="text" 
                                   class="form-control-modern" 
                                   id="requirement_title" 
                                   name="requirement_title" 
                                   placeholder="Enter requirement title"
                                   maxlength="200"
                                   required>
                        </div>
                        <div class="char-counter" id="titleCounter">0 / 200 characters</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group-modern">
                        <label for="description">
                            Description
                        </label>
                        <div class="input-icon-wrapper textarea-wrapper">
                            <i class="fa fa-align-left"></i>
                            <textarea class="form-control-modern" 
                                      id="description" 
                                      name="description" 
                                      rows="6"
                                      placeholder="Describe the requirement in detail"
                                      maxlength="1000"
                                      style="padding-left: 42px;"></textarea>
                        </div>
                        <div class="char-counter" id="descCounter">0 / 1000 characters</div>
                    </div>
                </div>
            </div>
            
            <!-- CLASSIFICATION -->
            <div class="form-section-title">
                <i class="fa fa-tags"></i> Classification
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group-modern">
                        <label for="type">
                            Type
                        </label>
                        <div class="input-icon-wrapper select-wrapper">
                            <i class="fa fa-tag"></i>
                            <select class="form-control-modern" id="type" name="type">
                                <option value="functional">Functional</option>
                                <option value="non_functional">Non-Functional</option>
                                <option value="technical">Technical</option>
                                <option value="business">Business</option>
                            </select>
                        </div>
                        <div class="badge-preview type-functional" id="typePreview">
                            <i class="fa fa-cog"></i> Functional
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group-modern">
                        <label for="priority">
                            Priority
                        </label>
                        <div class="input-icon-wrapper select-wrapper">
                            <i class="fa fa-exclamation-circle"></i>
                            <select class="form-control-modern" id="priority" name="priority">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div class="badge-preview priority-medium" id="priorityPreview">
                            <i class="fa fa-flag"></i> Medium
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group-modern">
                        <label for="status">
                            Status
                        </label>
                        <div class="input-icon-wrapper select-wrapper">
                            <i class="fa fa-circle"></i>
                            <select class="form-control-modern" id="status" name="status">
                                <option value="pending" selected>Pending</option>
                                <option value="approved">Approved</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="badge-preview status-pending" id="statusPreview">
                            <i class="fa fa-clock-o"></i> Pending
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- INFO CARD -->
            <div class="requirement-info-card">
                <i class="fa fa-info-circle"></i>
                <div>
                    <strong>Requirement Guidelines:</strong>
                    <span class="info-text">Be clear and specific. Define what the system should do, who will use it, and what value it provides.</span>
                </div>
            </div>
            
            <!-- ACTION BUTTONS -->
            <div class="form-actions">
                <button type="submit" class="btn-modern primary">
                    <i class="fa fa-save"></i> Create Requirement
                </button>
                <a href="project-detail.php?id=<?php echo $project_id; ?>&tab=requirements" class="btn-modern secondary">
                    <i class="fa fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // CHARACTER COUNTER FOR TITLE
    $('#requirement_title').on('input', function() {
        const length = $(this).val().length;
        const max = 200;
        const $counter = $('#titleCounter');
        
        $counter.text(length + ' / ' + max + ' characters');
        
        if (length > max * 0.9) {
            $counter.addClass('danger').removeClass('warning');
        } else if (length > max * 0.75) {
            $counter.addClass('warning').removeClass('danger');
        } else {
            $counter.removeClass('warning danger');
        }
    });
    
    // CHARACTER COUNTER FOR DESCRIPTION
    $('#description').on('input', function() {
        const length = $(this).val().length;
        const max = 1000;
        const $counter = $('#descCounter');
        
        $counter.text(length + ' / ' + max + ' characters');
        
        if (length > max * 0.9) {
            $counter.addClass('danger').removeClass('warning');
        } else if (length > max * 0.75) {
            $counter.addClass('warning').removeClass('danger');
        } else {
            $counter.removeClass('warning danger');
        }
    });
    
    // TYPE BADGE PREVIEW
    $('#type').on('change', function() {
        const value = $(this).val();
        const $preview = $('#typePreview');
        const labels = {
            'functional': '<i class="fa fa-cog"></i> Functional',
            'non_functional': '<i class="fa fa-shield"></i> Non-Functional',
            'technical': '<i class="fa fa-code"></i> Technical',
            'business': '<i class="fa fa-briefcase"></i> Business'
        };
        
        $preview.removeClass().addClass('badge-preview type-' + value);
        $preview.html(labels[value]);
    });
    
    // PRIORITY BADGE PREVIEW
    $('#priority').on('change', function() {
        const value = $(this).val();
        const $preview = $('#priorityPreview');
        const labels = {
            'low': '<i class="fa fa-flag"></i> Low',
            'medium': '<i class="fa fa-flag"></i> Medium',
            'high': '<i class="fa fa-flag"></i> High',
            'critical': '<i class="fa fa-exclamation-triangle"></i> Critical'
        };
        
        $preview.removeClass().addClass('badge-preview priority-' + value);
        $preview.html(labels[value]);
    });
    
    // STATUS BADGE PREVIEW
    $('#status').on('change', function() {
        const value = $(this).val();
        const $preview = $('#statusPreview');
        const labels = {
            'pending': '<i class="fa fa-clock-o"></i> Pending',
            'approved': '<i class="fa fa-check-circle"></i> Approved',
            'in_progress': '<i class="fa fa-spinner"></i> In Progress',
            'completed': '<i class="fa fa-check-square"></i> Completed',
            'rejected': '<i class="fa fa-times-circle"></i> Rejected'
        };
        
        $preview.removeClass().addClass('badge-preview status-' + value);
        $preview.html(labels[value]);
    });
    
    // FORM VALIDATION
    $('#requirementForm').on('submit', function(e) {
        let isValid = true;
        
        $('.form-control-modern[required]').each(function() {
            if ($(this).val().trim() === '') {
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
                    return $(this).val().trim() === '';
                }).first().offset().top - 100
            }, 300);
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>