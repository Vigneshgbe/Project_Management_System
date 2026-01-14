<?php
$page_title = 'Create Task';
require_once 'includes/header.php';
require_once 'components/task.php';
require_once 'components/user.php';

$auth->checkAccess();

$project_id = $_GET['project_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task = new Task();
    
    $data = [
        'project_id' => $_POST['project_id'],
        'phase_id' => $_POST['phase_id'] ?: null,
        'task_name' => $_POST['task_name'],
        'description' => $_POST['description'],
        'assigned_to' => $_POST['assigned_to'] ?: null,
        'status' => $_POST['status'],
        'priority' => $_POST['priority'],
        'due_date' => $_POST['due_date'] ?: null,
        'estimated_hours' => $_POST['estimated_hours'] ?: null,
        'created_by' => $auth->getUserId()
    ];
    
    if ($task->create($data)) {
        header('Location: project-detail.php?id=' . $_POST['project_id'] . '&tab=tasks');
        exit;
    }
}

$user = new User();
$users = $user->getActiveUsers();
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
    
    .task-create-container {
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
        min-height: 120px;
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
    
    /* PREVIEW BADGES */
    .preview-badge {
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
    
    /* Status Badges */
    .status-todo { background: #fef3c7; color: #92400e; }
    .status-in_progress { background: #dbeafe; color: #1e40af; }
    .status-review { background: #fed7aa; color: #9a3412; }
    .status-completed { background: #d1fae5; color: #065f46; }
    
    /* Priority Badges */
    .priority-low { background: #d1fae5; color: #065f46; }
    .priority-medium { background: #fef3c7; color: #92400e; }
    .priority-high { background: #fed7aa; color: #9a3412; }
    .priority-critical { background: #fee2e2; color: #991b1b; }
    
    /* ASSIGNEE PREVIEW */
    .assignee-preview {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.03));
        border: 1px solid rgba(99, 102, 241, 0.15);
        border-radius: 8px;
        padding: 10px 14px;
        margin-top: 8px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 600;
        color: var(--dark);
    }
    
    .assignee-preview.unassigned {
        background: linear-gradient(135deg, rgba(148, 163, 184, 0.05), rgba(100, 116, 139, 0.03));
        border-color: rgba(148, 163, 184, 0.15);
        color: #64748b;
    }
    
    .user-avatar {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-size: 11px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    /* DATE & HOURS PREVIEW */
    .date-preview,
    .hours-preview {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.03));
        border: 1px solid rgba(99, 102, 241, 0.15);
        border-radius: 8px;
        padding: 10px 14px;
        margin-top: 8px;
        font-size: 13px;
        font-weight: 600;
        color: var(--dark);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .date-preview.no-date,
    .hours-preview.no-estimate {
        background: linear-gradient(135deg, rgba(148, 163, 184, 0.05), rgba(100, 116, 139, 0.03));
        border-color: rgba(148, 163, 184, 0.15);
        color: #64748b;
    }
    
    .date-preview i,
    .hours-preview i {
        color: var(--primary);
    }
    
    .date-preview.no-date i,
    .hours-preview.no-estimate i {
        color: #94a3b8;
    }
    
    /* INFO CARD */
    .info-card {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.03));
        padding: 16px 20px;
        border-radius: 12px;
        border: 1px solid rgba(99, 102, 241, 0.15);
        margin-bottom: 24px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }
    
    .info-card i {
        font-size: 16px;
        color: var(--primary);
        flex-shrink: 0;
        margin-top: 2px;
    }
    
    .info-card .info-content {
        flex: 1;
    }
    
    .info-card strong {
        color: var(--dark);
        font-weight: 700;
        display: block;
        margin-bottom: 4px;
        font-size: 13px;
    }
    
    .info-card .info-text {
        color: #64748b;
        font-weight: 500;
        line-height: 1.5;
        font-size: 13px;
    }
    
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
        .task-create-container {
            padding: 20px;
        }
        .page-header {
            padding: 28px;
        }
        .page-header h1 {
            font-size: 28px;
        }
        .form-card {
            padding: 36px;
        }
    }
    
    @media (max-width: 992px) {
        .form-card {
            padding: 32px;
        }
    }
    
    @media (max-width: 768px) {
        .task-create-container {
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
        .task-create-container {
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

<div class="task-create-container container-fluid">
    <div class="page-header">
        <h1>
            <i class="fa fa-plus-circle"></i> Create Task
        </h1>
        <div class="page-breadcrumb">
            <a href="project-detail.php?id=<?php echo $project_id; ?>&tab=tasks">
                <i class="fa fa-tasks"></i> Project Tasks
            </a>
            <span>/</span>
            <span class="current">Create Task</span>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="form-card">
                <form method="POST" action="" id="taskForm">
                    <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                    
                    <!-- BASIC INFORMATION -->
                    <div class="form-section-title">
                        <i class="fa fa-file-text"></i> Basic Information
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label for="task_name">
                                    Task Name <span class="required">*</span>
                                </label>
                                <div class="input-icon-wrapper">
                                    <i class="fa fa-pencil"></i>
                                    <input type="text" 
                                           class="form-control-modern" 
                                           id="task_name" 
                                           name="task_name" 
                                           placeholder="Enter task name"
                                           maxlength="200"
                                           required>
                                </div>
                                <div class="char-counter" id="taskNameCounter">0 / 200 characters</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label for="phase_id">
                                    Phase ID (Optional)
                                </label>
                                <div class="input-icon-wrapper">
                                    <i class="fa fa-layer-group"></i>
                                    <input type="number" 
                                           class="form-control-modern" 
                                           id="phase_id" 
                                           name="phase_id" 
                                           placeholder="Enter phase ID if applicable">
                                </div>
                            </div>
                        </div>
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
                                      rows="4"
                                      placeholder="Describe the task in detail"
                                      maxlength="1000"
                                      style="padding-left: 42px;"></textarea>
                        </div>
                        <div class="char-counter" id="descCounter">0 / 1000 characters</div>
                    </div>
                    
                    <!-- ASSIGNMENT & SCHEDULING -->
                    <div class="form-section-title">
                        <i class="fa fa-users"></i> Assignment & Scheduling
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group-modern">
                                <label for="assigned_to">
                                    Assign To
                                </label>
                                <div class="input-icon-wrapper select-wrapper">
                                    <i class="fa fa-user"></i>
                                    <select class="form-control-modern" id="assigned_to" name="assigned_to">
                                        <option value="">Unassigned</option>
                                        <?php foreach ($users as $u): ?>
                                        <option value="<?php echo $u['id']; ?>" data-name="<?php echo htmlspecialchars($u['full_name']); ?>" data-role="<?php echo ucfirst($u['role']); ?>">
                                            <?php echo htmlspecialchars($u['full_name']); ?> (<?php echo ucfirst($u['role']); ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="assignee-preview unassigned" id="assigneePreview">
                                    <i class="fa fa-user-times"></i> Unassigned
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group-modern">
                                <label for="due_date">
                                    Due Date
                                </label>
                                <div class="input-icon-wrapper">
                                    <i class="fa fa-calendar"></i>
                                    <input type="date" 
                                           class="form-control-modern" 
                                           id="due_date" 
                                           name="due_date">
                                </div>
                                <div class="date-preview no-date" id="dueDatePreview">
                                    <i class="fa fa-calendar-times-o"></i> No due date set
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group-modern">
                                <label for="estimated_hours">
                                    Estimated Hours
                                </label>
                                <div class="input-icon-wrapper">
                                    <i class="fa fa-clock-o"></i>
                                    <input type="number" 
                                           class="form-control-modern" 
                                           id="estimated_hours" 
                                           name="estimated_hours" 
                                           step="0.5" 
                                           min="0"
                                           placeholder="0.0">
                                </div>
                                <div class="hours-preview no-estimate" id="hoursPreview">
                                    <i class="fa fa-hourglass-o"></i> No estimate
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- CLASSIFICATION & DETAILS -->
                    <div class="form-section-title">
                        <i class="fa fa-cogs"></i> Classification & Details
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label for="status">
                                    Status
                                </label>
                                <div class="input-icon-wrapper select-wrapper">
                                    <i class="fa fa-circle"></i>
                                    <select class="form-control-modern" id="status" name="status">
                                        <option value="todo" selected>To Do</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="review">Review</option>
                                        <option value="completed">Completed</option>
                                    </select>
                                </div>
                                <div class="preview-badge status-todo" id="statusPreview">
                                    <i class="fa fa-clock-o"></i> To Do
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
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
                                <div class="preview-badge priority-medium" id="priorityPreview">
                                    <i class="fa fa-flag"></i> Medium
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- INFO CARD -->
                    <div class="info-card">
                        <i class="fa fa-info-circle"></i>
                        <div class="info-content">
                            <strong>Task Guidelines</strong>
                            <span class="info-text">Be clear and actionable. Define what needs to be done, who should do it, and when it should be completed.</span>
                        </div>
                    </div>
                    
                    <!-- ACTION BUTTONS -->
                    <div class="form-actions">
                        <button type="submit" class="btn-modern primary">
                            <i class="fa fa-save"></i> Create Task
                        </button>
                        <a href="project-detail.php?id=<?php echo $project_id; ?>&tab=tasks" class="btn-modern secondary">
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
    // CHARACTER COUNTER FOR TASK NAME
    $('#task_name').on('input', function() {
        const length = $(this).val().length;
        const max = 200;
        const $counter = $('#taskNameCounter');
        
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
    
    // ASSIGNEE PREVIEW
    $('#assigned_to').on('change', function() {
        const $selected = $(this).find(':selected');
        const $preview = $('#assigneePreview');
        
        if ($(this).val() === '') {
            $preview.removeClass().addClass('assignee-preview unassigned');
            $preview.html('<i class="fa fa-user-times"></i> Unassigned');
        } else {
            const name = $selected.data('name');
            const role = $selected.data('role');
            const initials = name.split(' ').map(n => n.charAt(0)).join('').substring(0, 2).toUpperCase();
            
            $preview.removeClass().addClass('assignee-preview');
            $preview.html('<div class="user-avatar">' + initials + '</div>' + name + ' (' + role + ')');
        }
    });
    
    // DUE DATE PREVIEW
    $('#due_date').on('change', function() {
        const $preview = $('#dueDatePreview');
        const value = $(this).val();
        
        if (value === '') {
            $preview.removeClass().addClass('date-preview no-date');
            $preview.html('<i class="fa fa-calendar-times-o"></i> No due date set');
        } else {
            const date = new Date(value);
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            const formattedDate = date.toLocaleDateString('en-US', options);
            
            $preview.removeClass().addClass('date-preview');
            $preview.html('<i class="fa fa-calendar"></i> Due: ' + formattedDate);
        }
    });
    
    // ESTIMATED HOURS PREVIEW
    $('#estimated_hours').on('input', function() {
        const $preview = $('#hoursPreview');
        const value = parseFloat($(this).val());
        
        if (isNaN(value) || value <= 0) {
            $preview.removeClass().addClass('hours-preview no-estimate');
            $preview.html('<i class="fa fa-hourglass-o"></i> No estimate');
        } else {
            const unit = value === 1 ? 'hour' : 'hours';
            $preview.removeClass().addClass('hours-preview');
            $preview.html('<i class="fa fa-hourglass-half"></i> ' + value + ' ' + unit);
        }
    });
    
    // STATUS BADGE PREVIEW
    $('#status').on('change', function() {
        const value = $(this).val();
        const $preview = $('#statusPreview');
        const labels = {
            'todo': '<i class="fa fa-clock-o"></i> To Do',
            'in_progress': '<i class="fa fa-spinner"></i> In Progress',
            'review': '<i class="fa fa-eye"></i> Review',
            'completed': '<i class="fa fa-check-circle"></i> Completed'
        };
        
        $preview.removeClass().addClass('preview-badge status-' + value);
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
        
        $preview.removeClass().addClass('preview-badge priority-' + value);
        $preview.html(labels[value]);
    });
    
    // FORM VALIDATION
    $('#taskForm').on('submit', function(e) {
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