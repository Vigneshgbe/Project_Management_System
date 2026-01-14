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
    
    /* PANELS */
    .panel {
        border: none;
        box-shadow: var(--shadow);
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        background: white;
        border: 1px solid var(--border);
        margin-bottom: 24px;
        animation: fadeInUp 0.4s ease;
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .panel:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }
    
    .panel-heading {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        padding: 20px 24px;
        border: none;
    }
    
    .panel-title {
        font-size: 17px;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        color: white;
    }
    
    .panel-body {
        padding: 32px;
        background: white;
    }
    
    /* INFO PANEL */
    .panel-info > .panel-heading {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
    }
    
    .panel-info .panel-body p {
        margin-bottom: 16px;
        padding: 12px 16px;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.03));
        border-radius: 8px;
        border-left: 3px solid var(--primary);
        transition: all 0.3s ease;
    }
    
    .panel-info .panel-body p:hover {
        transform: translateX(4px);
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.05));
    }
    
    .panel-info .panel-body p:last-child {
        margin-bottom: 0;
    }
    
    .panel-info .panel-body strong {
        color: var(--dark);
        font-weight: 700;
        display: block;
        margin-bottom: 4px;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #64748b;
    }
    
    /* FORM GROUPS */
    .form-group {
        margin-bottom: 24px;
    }
    
    .form-group label {
        display: block;
        font-weight: 700;
        font-size: 11px;
        color: #64748b;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }
    
    .form-group label .text-danger {
        color: var(--danger);
        margin-left: 4px;
    }
    
    /* FORM CONTROLS */
    .form-control {
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
    
    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }
    
    .form-control::placeholder {
        color: #94a3b8;
    }
    
    .form-control:disabled {
        background: #f1f5f9;
        color: #64748b;
        cursor: not-allowed;
        opacity: 0.7;
    }
    
    textarea.form-control {
        resize: vertical;
        min-height: 120px;
        font-family: inherit;
        line-height: 1.6;
    }
    
    select.form-control {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236366f1' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 16px center;
        padding-right: 40px;
    }
    
    /* TEXT MUTED */
    .text-muted {
        color: #94a3b8;
        font-size: 12px;
        font-weight: 500;
        margin-top: 6px;
        display: block;
    }
    
    small.text-muted {
        font-size: 11px;
    }
    
    /* BUTTONS */
    .btn {
        border-radius: 10px;
        padding: 12px 28px;
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
    
    .btn i {
        font-size: 14px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(99, 102, 241, 0.35);
        color: white;
    }
    
    .btn-primary:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.2);
    }
    
    .btn-default {
        background: white;
        color: var(--primary);
        border: 2px solid var(--primary);
    }
    
    .btn-default:hover {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
        transform: translateY(-2px);
    }
    
    .btn-default:active {
        transform: translateY(0);
    }
    
    .btn-lg {
        padding: 14px 32px;
        font-size: 13px;
    }
    
    /* HORIZONTAL RULE */
    hr {
        border: none;
        height: 2px;
        background: var(--border);
        margin: 32px 0;
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
        .panel-body {
            padding: 28px;
        }
    }
    
    @media (max-width: 992px) {
        .panel-body {
            padding: 24px;
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
        .panel-body {
            padding: 20px;
        }
        .form-control {
            padding: 12px 14px;
            font-size: 14px;
        }
        .btn {
            padding: 12px 24px;
        }
        .btn-lg {
            padding: 14px 28px;
            width: 100%;
            margin-bottom: 10px;
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
        .panel-body {
            padding: 16px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-control {
            padding: 10px 14px;
            font-size: 13px;
        }
    }
</style>

<div class="project-edit-container container-fluid">
    <div class="page-header">
        <h1><i class="fa fa-edit"></i> Edit Project</h1>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-folder"></i> Project Details</h3>
                </div>
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
                            <input type="text" class="form-control" id="client_name" name="client_name" value="<?php echo htmlspecialchars($project['client_name']); ?>" placeholder="Enter client name">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Enter project description"><?php echo htmlspecialchars($project['description']); ?></textarea>
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
                                    <input type="number" class="form-control" id="budget" name="budget" step="0.01" min="0" value="<?php echo $project['budget']; ?>" placeholder="0.00">
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
                    <p>
                        <strong>Created By:</strong>
                        <?php echo htmlspecialchars($project['creator_name']); ?>
                    </p>
                    <p>
                        <strong>Created:</strong>
                        <?php echo date('M d, Y H:i', strtotime($project['created_at'])); ?>
                    </p>
                    <p>
                        <strong>Last Updated:</strong>
                        <?php echo date('M d, Y H:i', strtotime($project['updated_at'])); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Form validation
    $('#projectForm').on('submit', function(e) {
        let isValid = true;
        
        $('.form-control[required]').each(function() {
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
                scrollTop: $('.form-control[required]').filter(function() {
                    return !$(this).val().trim();
                }).first().offset().top - 100
            }, 300);
        }
    });
    
    // Date validation
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
});
</script>

<?php require_once 'includes/footer.php'; ?>