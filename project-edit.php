<?php
$page_title = 'Edit Project';
require_once 'includes/header.php';
require_once 'components/project.php';

$auth->checkAccess('manager');

$project_id = $_GET['id'] ?? 0;
$project_obj = new Project();
$project = $project_obj->getById($project_id);

if (!$project) {
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
        animation: fadeIn 0.5s ease !important;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .page-header {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        color: #1e293b !important;
        padding: 35px 40px !important;
        border-radius: 20px !important;
        margin-bottom: 30px !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
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
    
    .page-header h1 {
        margin: 0 !important;
        font-weight: 800 !important;
        font-size: 32px !important;
        position: relative !important;
        z-index: 1 !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }
    
    .page-header h1 i {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }
    
    .panel {
        border: none !important;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15) !important;
        border-radius: 20px !important;
        overflow: hidden !important;
        transition: all 0.3s ease !important;
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        margin-bottom: 25px !important;
        animation: scaleIn 0.5s ease !important;
    }
    
    @keyframes scaleIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
    
    .panel:hover {
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2) !important;
        transform: translateY(-3px) !important;
    }
    
    .panel-heading {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        padding: 20px 25px !important;
        border: none !important;
        font-weight: 700 !important;
    }
    
    .panel-title {
        font-size: 18px !important;
        font-weight: 700 !important;
        margin: 0 !important;
    }
    
    .panel-body {
        padding: 35px 30px !important;
        background: white !important;
    }
    
    .panel-info > .panel-heading {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
    }
    
    .form-group {
        margin-bottom: 25px !important;
        animation: fadeInUp 0.4s ease !important;
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .form-group label {
        font-weight: 700 !important;
        color: #1e293b !important;
        margin-bottom: 10px !important;
        font-size: 14px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        display: block !important;
    }
    
    .form-control {
        border: 2px solid #e2e8f0 !important;
        border-radius: 12px !important;
        padding: 14px 18px !important;
        font-size: 15px !important;
        transition: all 0.3s ease !important;
        background: white !important;
        color: #1e293b !important;
        font-weight: 500 !important;
    }
    
    .form-control:focus {
        border-color: #667eea !important;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1) !important;
        outline: none !important;
        background: white !important;
        transform: translateY(-2px) !important;
    }
    
    .form-control:hover:not(:focus):not(:disabled) {
        border-color: #cbd5e1 !important;
        background: #f8fafc !important;
    }
    
    .form-control:disabled {
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%) !important;
        color: #64748b !important;
        cursor: not-allowed !important;
        opacity: 0.7 !important;
    }
    
    textarea.form-control {
        resize: vertical !important;
        min-height: 120px !important;
        font-family: 'Inter', sans-serif !important;
        line-height: 1.6 !important;
    }
    
    select.form-control {
        cursor: pointer !important;
        appearance: none !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23667eea' d='M6 9L1 4h10z'/%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important;
        background-position: right 15px center !important;
        padding-right: 45px !important;
    }
    
    select.form-control:focus {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23667eea' d='M6 9L1 4h10z'/%3E%3C/svg%3E") !important;
    }
    
    .text-danger {
        color: #ef4444 !important;
        font-weight: 700 !important;
    }
    
    .text-muted {
        color: #64748b !important;
        font-size: 13px !important;
        font-weight: 500 !important;
        margin-top: 6px !important;
        display: block !important;
    }
    
    small.text-muted {
        font-size: 12px !important;
    }
    
    .btn {
        border-radius: 12px !important;
        padding: 14px 28px !important;
        font-weight: 700 !important;
        transition: all 0.3s ease !important;
        text-transform: uppercase !important;
        font-size: 13px !important;
        letter-spacing: 0.5px !important;
        border: none !important;
        position: relative !important;
        overflow: hidden !important;
    }
    
    .btn::before {
        content: '' !important;
        position: absolute !important;
        top: 50% !important;
        left: 50% !important;
        width: 0 !important;
        height: 0 !important;
        border-radius: 50% !important;
        background: rgba(255, 255, 255, 0.3) !important;
        transform: translate(-50%, -50%) !important;
        transition: width 0.6s, height 0.6s !important;
    }
    
    .btn:hover::before {
        width: 300px !important;
        height: 300px !important;
    }
    
    .btn i {
        margin-right: 8px !important;
        position: relative !important;
        z-index: 1 !important;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3) !important;
    }
    
    .btn-primary:hover,
    .btn-primary:focus {
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%) !important;
        transform: translateY(-3px) !important;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4) !important;
        color: white !important;
    }
    
    .btn-primary:active {
        transform: translateY(-1px) !important;
        box-shadow: 0 3px 15px rgba(102, 126, 234, 0.3) !important;
    }
    
    .btn-default {
        background: white !important;
        color: #667eea !important;
        border: 2px solid #667eea !important;
        box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1) !important;
    }
    
    .btn-default:hover,
    .btn-default:focus {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        border-color: transparent !important;
        transform: translateY(-3px) !important;
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3) !important;
    }
    
    .btn-default:active {
        transform: translateY(-1px) !important;
    }
    
    .btn-lg {
        padding: 16px 32px !important;
        font-size: 14px !important;
    }
    
    hr {
        border: none !important;
        height: 2px !important;
        background: linear-gradient(90deg, transparent 0%, #e2e8f0 50%, transparent 100%) !important;
        margin: 35px 0 !important;
    }
    
    .panel-info .panel-body p {
        margin-bottom: 20px !important;
        padding: 15px !important;
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%) !important;
        border-radius: 12px !important;
        border-left: 4px solid #3b82f6 !important;
        transition: all 0.3s ease !important;
    }
    
    .panel-info .panel-body p:hover {
        transform: translateX(5px) !important;
        box-shadow: 0 3px 15px rgba(59, 130, 246, 0.15) !important;
    }
    
    .panel-info .panel-body p:last-child {
        margin-bottom: 0 !important;
    }
    
    .panel-info .panel-body strong {
        color: #1e293b !important;
        font-weight: 700 !important;
        display: block !important;
        margin-bottom: 5px !important;
        font-size: 12px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }
    
    /* Input Focus States */
    input[type="text"]:focus,
    input[type="date"]:focus,
    input[type="number"]:focus,
    textarea:focus,
    select:focus {
        animation: focusPulse 0.5s ease !important;
    }
    
    @keyframes focusPulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.02); }
        100% { transform: scale(1); }
    }
    
    /* Form Animation Delays */
    .form-group:nth-child(1) { animation-delay: 0.05s !important; }
    .form-group:nth-child(2) { animation-delay: 0.1s !important; }
    .form-group:nth-child(3) { animation-delay: 0.15s !important; }
    .form-group:nth-child(4) { animation-delay: 0.2s !important; }
    .form-group:nth-child(5) { animation-delay: 0.25s !important; }
    .form-group:nth-child(6) { animation-delay: 0.3s !important; }
    
    /* Row Column Animation */
    .row > div {
        animation: fadeInUp 0.4s ease !important;
    }
    
    .row > div:nth-child(1) { animation-delay: 0.1s !important; }
    .row > div:nth-child(2) { animation-delay: 0.2s !important; }
    .row > div:nth-child(3) { animation-delay: 0.3s !important; }
    
    /* RESPONSIVE */
    @media (max-width: 1200px) {
        .project-edit-container {
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
        .panel-body {
            padding: 25px 20px !important;
        }
    }
    
    @media (max-width: 768px) {
        .project-edit-container {
            padding: 10px !important;
        }
        .page-header {
            padding: 20px !important;
            margin-bottom: 20px !important;
        }
        .page-header h1 {
            font-size: 24px !important;
        }
        .panel-body {
            padding: 20px 15px !important;
        }
        .form-control {
            padding: 12px 16px !important;
            font-size: 14px !important;
        }
        .btn {
            padding: 12px 24px !important;
        }
        .btn-lg {
            padding: 14px 28px !important;
            width: 100% !important;
            margin-bottom: 10px !important;
        }
    }
    
    @media (max-width: 480px) {
        .project-edit-container {
            padding: 8px !important;
        }
        .page-header h1 {
            font-size: 20px !important;
        }
        .form-group {
            margin-bottom: 20px !important;
        }
        .form-control {
            padding: 10px 14px !important;
            font-size: 13px !important;
        }
    }
    
    /* Loading State */
    .form-control.loading {
        background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%) !important;
        background-size: 200% 100% !important;
        animation: loading 1.5s infinite !important;
    }
    
    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    
    /* Smooth Transitions */
    * {
        transition: background 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease !important;
    }
    
    /* Performance Optimization */
    .panel,
    .form-control,
    .btn {
        will-change: transform !important;
    }
</style>

<div class="project-edit-container container-fluid">
    <div class="page-header">
        <h1><i class="fa fa-edit"></i> Edit Project</h1>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-body">
                    <form method="POST" action="" id="projectForm">
                        <div class="form-group">
                            <label for="project_name">Project Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="project_name" name="project_name" value="<?php echo htmlspecialchars($project['project_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Project Code</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($project['project_code']); ?>" disabled>
                            <small class="text-muted">Project code cannot be changed</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="client_name">Client Name</label>
                            <input type="text" class="form-control" id="client_name" name="client_name" value="<?php echo htmlspecialchars($project['client_name']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($project['description']); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start_date">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $project['start_date']; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="end_date">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $project['end_date']; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="planning" <?php echo $project['status'] === 'planning' ? 'selected' : ''; ?>>Planning</option>
                                        <option value="in_progress" <?php echo $project['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="on_hold" <?php echo $project['status'] === 'on_hold' ? 'selected' : ''; ?>>On Hold</option>
                                        <option value="completed" <?php echo $project['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $project['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="priority">Priority</label>
                                    <select class="form-control" id="priority" name="priority">
                                        <option value="low" <?php echo $project['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                                        <option value="medium" <?php echo $project['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                        <option value="high" <?php echo $project['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                                        <option value="critical" <?php echo $project['priority'] === 'critical' ? 'selected' : ''; ?>>Critical</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="budget">Budget ($)</label>
                                    <input type="number" class="form-control" id="budget" name="budget" step="0.01" min="0" value="<?php echo $project['budget']; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fa fa-save"></i> Update Project
                        </button>
                        <a href="project-detail.php?id=<?php echo $project_id; ?>" class="btn btn-default btn-lg">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-info-circle"></i> Project Information</h3>
                </div>
                <div class="panel-body">
                    <p><strong>Created By:</strong><br><?php echo htmlspecialchars($project['creator_name']); ?></p>
                    <p><strong>Created:</strong><br><?php echo date('M d, Y H:i', strtotime($project['created_at'])); ?></p>
                    <p><strong>Last Updated:</strong><br><?php echo date('M d, Y H:i', strtotime($project['updated_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Form validation with visual feedback
        $('#projectForm').on('submit', function(e) {
            let isValid = true;
            
            $('.form-control[required]').each(function() {
                if (!$(this).val()) {
                    isValid = false;
                    $(this).css('border-color', '#ef4444');
                    setTimeout(() => {
                        $(this).css('border-color', '#e2e8f0');
                    }, 2000);
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });
        
        // Input animations
        $('.form-control').on('focus', function() {
            $(this).parent().find('label').css({
                'color': '#667eea',
                'transform': 'translateY(-2px)'
            });
        });
        
        $('.form-control').on('blur', function() {
            $(this).parent().find('label').css({
                'color': '#1e293b',
                'transform': 'translateY(0)'
            });
        });
        
        // Character counter for textarea
        $('#description').on('input', function() {
            const length = $(this).val().length;
            if (length > 450) {
                $(this).css('border-color', '#f59e0b');
            } else {
                $(this).css('border-color', '#e2e8f0');
            }
        });
        
        // Date validation
        $('#end_date').on('change', function() {
            const startDate = new Date($('#start_date').val());
            const endDate = new Date($(this).val());
            
            if (startDate && endDate && endDate < startDate) {
                alert('End date cannot be before start date');
                $(this).val('');
            }
        });
        
        // Auto-save indication (visual feedback only)
        $('.form-control').on('change', function() {
            const $input = $(this);
            $input.addClass('loading');
            setTimeout(() => {
                $input.removeClass('loading');
            }, 300);
        });
        
        // Smooth scroll
        $('html').css('scroll-behavior', 'smooth');
        
        // Add ripple effect to buttons
        $('.btn').on('click', function(e) {
            const $btn = $(this);
            const x = e.pageX - $btn.offset().left;
            const y = e.pageY - $btn.offset().top;
            
            $btn.css({
                '--ripple-x': x + 'px',
                '--ripple-y': y + 'px'
            });
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>