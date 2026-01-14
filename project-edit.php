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
    .project-edit-container {
        background: transparent !important;
        min-height: calc(100vh - 100px) !important;
        padding: 20px !important;
        margin: 0 !important;
    }
    
    .project-edit-header {
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
    
    .project-edit-header::before {
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
    
    .project-edit-header h1 {
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
    
    .project-edit-header h1 i {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
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
    
    .info-item {
        margin-bottom: 20px !important;
        padding: 12px 16px !important;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.03)) !important;
        border-radius: 12px !important;
        border-left: 3px solid #667eea !important;
        transition: all 0.3s ease !important;
    }
    
    .info-item:hover {
        transform: translateX(4px) !important;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.05)) !important;
    }
    
    .info-item:last-child {
        margin-bottom: 0 !important;
    }
    
    .info-item strong {
        display: block !important;
        color: #64748b !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.8px !important;
        margin-bottom: 6px !important;
    }
    
    .info-item span {
        color: #1e293b !important;
        font-weight: 600 !important;
        font-size: 14px !important;
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
    
    .form-control-modern:disabled {
        background: #f1f5f9 !important;
        color: #64748b !important;
        cursor: not-allowed !important;
        opacity: 0.7 !important;
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
        .project-edit-container {
            padding: 15px !important;
        }
        .project-edit-header, .form-card, .info-card {
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
        .project-edit-container {
            padding: 10px !important;
        }
        .project-edit-header {
            padding: 20px !important;
            margin-bottom: 20px !important;
        }
        .project-edit-header h1 {
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
        .project-edit-container {
            padding: 8px !important;
        }
        .project-edit-header h1 {
            font-size: 20px !important;
        }
        .form-card, .info-card {
            padding: 15px !important;
        }
        .form-control-modern {
            padding: 12px 16px !important;
            font-size: 14px !important;
        }
    }
</style>

<div class="project-edit-container container-fluid">
    <div class="project-edit-header">
        <h1>
            <i class="fa fa-edit"></i> Edit Project
        </h1>
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
                               required>
                    </div>
                    
                    <div class="form-group-modern">
                        <label>Project Code</label>
                        <input type="text" 
                               class="form-control-modern" 
                               value="<?php echo htmlspecialchars($project['project_code']); ?>" 
                               disabled>
                        <span class="form-hint">
                            <i class="fa fa-info-circle"></i>
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
                    
                    <div class="form-section-title" style="margin-top: 35px;">
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
                    
                    <div class="form-section-title" style="margin-top: 35px;">
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
            'color': '#667eea',
            'transition': 'color 0.3s ease'
        });
    }).on('blur', function() {
        $(this).closest('.form-group-modern').find('label').css({
            'color': '#1e293b'
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>