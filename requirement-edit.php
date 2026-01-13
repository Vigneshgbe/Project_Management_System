<?php
$page_title = 'Edit Requirement';
require_once 'includes/header.php';
require_once 'components/requirement.php';

$auth->checkAccess('manager');

$req_id = $_GET['id'] ?? 0;
$req_obj = new Requirement();
$req = $req_obj->getById($req_id);

if (!$req) {
    header('Location: projects.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'requirement_title' => $_POST['requirement_title'],
        'description' => $_POST['description'],
        'type' => $_POST['type'],
        'priority' => $_POST['priority'],
        'status' => $_POST['status']
    ];
    
    if ($req_obj->update($req_id, $data)) {
        header('Location: project-detail.php?id=' . $req['project_id'] . '&tab=requirements');
        exit;
    }
}
?>

<style>
    .requirement-edit-container {
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
    
    .requirement-edit-header {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        color: #1e293b !important;
        padding: 35px 40px !important;
        border-radius: 20px !important;
        margin-bottom: 35px !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        animation: slideDown 0.6s ease !important;
        position: relative !important;
        overflow: hidden !important;
    }
    
    .requirement-edit-header::before {
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
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .requirement-edit-header h1 {
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
    
    .requirement-edit-header h1 i {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }
    
    .requirement-edit-breadcrumb {
        margin-top: 15px !important;
        position: relative !important;
        z-index: 1 !important;
    }
    
    .requirement-edit-breadcrumb a {
        color: #667eea !important;
        text-decoration: none !important;
        font-weight: 600 !important;
        transition: color 0.3s ease !important;
    }
    
    .requirement-edit-breadcrumb a:hover {
        color: #764ba2 !important;
    }
    
    .requirement-edit-breadcrumb span {
        color: #64748b !important;
        margin: 0 8px !important;
    }
    
    .form-card {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        border-radius: 20px !important;
        padding: 40px !important;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        animation: slideUp 0.5s ease !important;
        margin-bottom: 25px !important;
    }
    
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
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
        cursor: pointer !important;
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
    
    .input-icon-wrapper.textarea-wrapper i {
        top: 20px !important;
        transform: none !important;
    }
    
    .input-icon-wrapper.select-wrapper i {
        pointer-events: none !important;
        z-index: 1 !important;
    }
    
    .badge-preview {
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        padding: 8px 16px !important;
        border-radius: 20px !important;
        font-size: 12px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        margin-top: 8px !important;
    }
    
    .badge-preview.type-functional {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
        color: white !important;
    }
    
    .badge-preview.type-non_functional {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%) !important;
        color: white !important;
    }
    
    .badge-preview.type-technical {
        background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%) !important;
        color: white !important;
    }
    
    .badge-preview.type-business {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
        color: white !important;
    }
    
    .badge-preview.priority-low {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        color: white !important;
    }
    
    .badge-preview.priority-medium {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
        color: white !important;
    }
    
    .badge-preview.priority-high {
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important;
        color: white !important;
    }
    
    .badge-preview.priority-critical {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        color: white !important;
        animation: pulse 2s ease-in-out infinite !important;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.85; transform: scale(1.05); }
    }
    
    .badge-preview.status-pending {
        background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%) !important;
        color: white !important;
    }
    
    .badge-preview.status-approved {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        color: white !important;
    }
    
    .badge-preview.status-in_progress {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
        color: white !important;
    }
    
    .badge-preview.status-completed {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%) !important;
        color: white !important;
    }
    
    .badge-preview.status-rejected {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        color: white !important;
    }
    
    .requirement-current-values {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.05) 100%) !important;
        padding: 20px !important;
        border-radius: 12px !important;
        border: 2px solid rgba(16, 185, 129, 0.2) !important;
        margin-bottom: 25px !important;
    }
    
    .requirement-current-values i {
        font-size: 18px !important;
        color: #10b981 !important;
        margin-right: 10px !important;
    }
    
    .requirement-current-values strong {
        color: #1e293b !important;
        font-weight: 700 !important;
    }
    
    .requirement-current-values .info-text {
        color: #64748b !important;
        font-weight: 600 !important;
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
    
    .char-counter {
        font-size: 12px !important;
        color: #94a3b8 !important;
        font-weight: 600 !important;
        margin-top: 5px !important;
        text-align: right !important;
    }
    
    .char-counter.warning {
        color: #f97316 !important;
    }
    
    .char-counter.danger {
        color: #ef4444 !important;
    }
    
    .last-modified-info {
        background: linear-gradient(135deg, #f1f5f9 0%, #ffffff 100%) !important;
        border: 2px solid #e2e8f0 !important;
        border-radius: 12px !important;
        padding: 20px !important;
        margin-bottom: 25px !important;
        text-align: center !important;
    }
    
    .last-modified-info i {
        color: #667eea !important;
        margin-right: 8px !important;
    }
    
    .last-modified-info .modified-text {
        color: #64748b !important;
        font-weight: 600 !important;
        font-size: 14px !important;
    }
    
    /* RESPONSIVE */
    @media (max-width: 1200px) {
        .requirement-edit-container {
            padding: 15px !important;
        }
        .requirement-edit-header {
            padding: 25px 30px !important;
        }
        .form-card {
            padding: 30px !important;
        }
    }
    
    @media (max-width: 768px) {
        .requirement-edit-container {
            padding: 10px !important;
        }
        .requirement-edit-header {
            padding: 20px !important;
            margin-bottom: 20px !important;
        }
        .requirement-edit-header h1 {
            font-size: 24px !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 10px !important;
        }
        .form-card {
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
        .requirement-edit-container {
            padding: 8px !important;
        }
        .requirement-edit-header h1 {
            font-size: 20px !important;
        }
        .form-card {
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

<div class="requirement-edit-container container-fluid">
    <div class="requirement-edit-header">
        <h1>
            <i class="fa fa-edit"></i> Edit Requirement
        </h1>
        <div class="requirement-edit-breadcrumb">
            <a href="project-detail.php?id=<?php echo $req['project_id']; ?>&tab=requirements">
                <i class="fa fa-list-alt"></i> Project Requirements
            </a>
            <span>/</span>
            <span style="color: #1e293b; font-weight: 600;">Edit Requirement</span>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="form-card">
                <!-- CURRENT VALUES INFO -->
                <div class="requirement-current-values">
                    <i class="fa fa-info-circle"></i>
                    <strong>Editing:</strong>
                    <span class="info-text"><?php echo htmlspecialchars($req['requirement_title']); ?></span>
                </div>
                
                <?php if (isset($req['updated_at'])): ?>
                <div class="last-modified-info">
                    <i class="fa fa-clock-o"></i>
                    <span class="modified-text">
                        Last modified: <?php echo date('M d, Y g:i A', strtotime($req['updated_at'])); ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="requirementForm">
                    <!-- BASIC INFORMATION -->
                    <div class="form-section-title">
                        <i class="fa fa-file-text"></i> Basic Information
                    </div>
                    
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
                                   value="<?php echo htmlspecialchars($req['requirement_title']); ?>"
                                   placeholder="Enter requirement title"
                                   maxlength="200"
                                   required>
                        </div>
                        <div class="char-counter" id="titleCounter"><?php echo strlen($req['requirement_title']); ?> / 200 characters</div>
                    </div>
                    
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
                                      style="padding-left: 45px;"><?php echo htmlspecialchars($req['description']); ?></textarea>
                        </div>
                        <div class="char-counter" id="descCounter"><?php echo strlen($req['description']); ?> / 1000 characters</div>
                    </div>
                    
                    <!-- CLASSIFICATION -->
                    <div class="form-section-title" style="margin-top: 40px;">
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
                                        <option value="functional" <?php echo $req['type'] === 'functional' ? 'selected' : ''; ?>>Functional</option>
                                        <option value="non_functional" <?php echo $req['type'] === 'non_functional' ? 'selected' : ''; ?>>Non-Functional</option>
                                        <option value="technical" <?php echo $req['type'] === 'technical' ? 'selected' : ''; ?>>Technical</option>
                                        <option value="business" <?php echo $req['type'] === 'business' ? 'selected' : ''; ?>>Business</option>
                                    </select>
                                </div>
                                <div class="badge-preview type-<?php echo $req['type']; ?>" id="typePreview">
                                    <i class="fa fa-<?php 
                                        echo $req['type'] === 'functional' ? 'cog' : 
                                            ($req['type'] === 'non_functional' ? 'shield' : 
                                            ($req['type'] === 'technical' ? 'code' : 'briefcase')); 
                                    ?>"></i>
                                    <?php echo ucfirst(str_replace('_', ' ', $req['type'])); ?>
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
                                        <option value="low" <?php echo $req['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                                        <option value="medium" <?php echo $req['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                        <option value="high" <?php echo $req['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                                        <option value="critical" <?php echo $req['priority'] === 'critical' ? 'selected' : ''; ?>>Critical</option>
                                    </select>
                                </div>
                                <div class="badge-preview priority-<?php echo $req['priority']; ?>" id="priorityPreview">
                                    <i class="fa fa-flag"></i>
                                    <?php echo ucfirst($req['priority']); ?>
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
                                        <option value="pending" <?php echo $req['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="approved" <?php echo $req['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                        <option value="in_progress" <?php echo $req['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="completed" <?php echo $req['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="rejected" <?php echo $req['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    </select>
                                </div>
                                <div class="badge-preview status-<?php echo $req['status']; ?>" id="statusPreview">
                                    <i class="fa fa-<?php 
                                        echo $req['status'] === 'pending' ? 'clock-o' : 
                                            ($req['status'] === 'approved' ? 'check-circle' : 
                                            ($req['status'] === 'in_progress' ? 'spinner' : 
                                            ($req['status'] === 'completed' ? 'check-square' : 'times-circle'))); 
                                    ?>"></i>
                                    <?php echo ucfirst(str_replace('_', ' ', $req['status'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ACTION BUTTONS -->
                    <div class="form-actions">
                        <button type="submit" class="btn-modern primary">
                            <i class="fa fa-save"></i> Update Requirement
                        </button>
                        <a href="project-detail.php?id=<?php echo $req['project_id']; ?>&tab=requirements" class="btn-modern secondary">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // FORM ANIMATION
    $('.form-card').css({
        'animation': 'slideUp 0.5s ease both'
    });
    
    // CHARACTER COUNTER FOR TITLE
    $('#requirement_title').on('input', function() {
        const length = $(this).val().length;
        const max = 200;
        const $counter = $('#titleCounter');
        
        $counter.text(length + ' / ' + max + ' characters');
        
        if (length > max * 0.9) {
            $counter.addClass('danger');
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
            $counter.addClass('danger');
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
        
        $preview.attr('class', 'badge-preview type-' + value);
        $preview.html(labels[value]);
        
        // Animate change
        $preview.css('transform', 'scale(1.1)');
        setTimeout(function() {
            $preview.css('transform', 'scale(1)');
        }, 200);
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
        
        $preview.attr('class', 'badge-preview priority-' + value);
        $preview.html(labels[value]);
        
        // Animate change
        $preview.css('transform', 'scale(1.1)');
        setTimeout(function() {
            $preview.css('transform', 'scale(1)');
        }, 200);
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
        
        $preview.attr('class', 'badge-preview status-' + value);
        $preview.html(labels[value]);
        
        // Animate change
        $preview.css('transform', 'scale(1.1)');
        setTimeout(function() {
            $preview.css('transform', 'scale(1)');
        }, 200);
    });
    
    // INPUT FOCUS EFFECTS
    $('.form-control-modern').on('focus', function() {
        $(this).closest('.form-group-modern').find('label').css({
            'color': '#667eea',
            'transform': 'scale(1.05)',
            'transition': 'all 0.3s ease'
        });
    }).on('blur', function() {
        $(this).closest('.form-group-modern').find('label').css({
            'color': '#1e293b',
            'transform': 'scale(1)'
        });
    });
    
    // FORM VALIDATION ENHANCEMENT
    $('#requirementForm').on('submit', function(e) {
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
            }, 500);
        }
    });
    
    // SET INITIAL COUNTER STATES
    $('#titleCounter').trigger();
    $('#descCounter').trigger();
});
</script>

<?php require_once 'includes/footer.php'; ?>