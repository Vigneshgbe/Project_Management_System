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
    }
    
    .task-create-container {
        background: transparent;
        min-height: calc(100vh - 100px);
        padding: 24px;
        margin: 0 auto;
        animation: fadeIn 0.4s ease;
        max-width: 1400px;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .task-create-header {
        background: white;
        padding: 32px;
        border-radius: 16px;
        margin-bottom: 32px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        border: 1px solid var(--border);
        position: relative;
        overflow: hidden;
    }
    
    .task-create-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
    }
    
    .task-create-header h1 {
        margin: 0;
        font-weight: 700;
        font-size: 32px;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .task-create-header h1 i {
        color: var(--primary);
        font-size: 28px;
    }
    
    .task-create-breadcrumb {
        margin-top: 12px;
        font-size: 14px;
        font-weight: 600;
    }
    
    .task-create-breadcrumb a {
        color: var(--primary);
        text-decoration: none;
        transition: color 0.3s ease;
    }
    
    .task-create-breadcrumb a:hover {
        color: var(--primary-dark);
    }
    
    .task-create-breadcrumb span {
        color: #64748b;
        margin: 0 8px;
    }
    
    .form-card {
        background: white;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--border);
        animation: slideUp 0.4s ease;
        margin-bottom: 24px;
    }
    
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .form-section-title {
        font-size: 17px;
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
        font-size: 18px;
    }
    
    .form-group-modern {
        margin-bottom: 24px;
    }
    
    .form-group-modern label {
        display: block;
        font-weight: 700;
        font-size: 13px;
        color: var(--dark);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .form-group-modern label .required {
        color: var(--danger);
        margin-left: 4px;
    }
    
    .form-control-modern {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid var(--border);
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
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
    
    input[type="number"].form-control-modern,
    input[type="date"].form-control-modern {
        cursor: pointer;
    }
    
    .input-icon-wrapper {
        position: relative;
    }
    
    .input-icon-wrapper i {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--primary);
        font-size: 16px;
    }
    
    .input-icon-wrapper .form-control-modern {
        padding-left: 44px;
    }
    
    .input-icon-wrapper.textarea-wrapper i {
        top: 18px;
        transform: none;
    }
    
    .input-icon-wrapper.select-wrapper i {
        pointer-events: none;
        z-index: 1;
    }
    
    .task-info-card {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.05));
        padding: 16px 20px;
        border-radius: 12px;
        border-left: 4px solid var(--primary);
        margin-bottom: 24px;
        border: 1px solid rgba(99, 102, 241, 0.2);
        border-left-width: 4px;
    }
    
    .task-info-card i {
        font-size: 16px;
        color: var(--primary);
        margin-right: 10px;
    }
    
    .task-info-card strong {
        color: var(--dark);
        font-weight: 700;
    }
    
    .task-info-card .info-text {
        color: #64748b;
        font-weight: 600;
    }
    
    .badge-preview {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 8px;
        transition: all 0.3s ease;
    }
    
    .badge-preview.status-todo {
        background: #e5e7eb;
        color: #374151;
    }
    
    .badge-preview.status-in_progress {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .badge-preview.status-review {
        background: #fef3c7;
        color: #92400e;
    }
    
    .badge-preview.status-completed {
        background: #d1fae5;
        color: #065f46;
    }
    
    .badge-preview.priority-low {
        background: #d1fae5;
        color: #065f46;
    }
    
    .badge-preview.priority-medium {
        background: #fef3c7;
        color: #92400e;
    }
    
    .badge-preview.priority-high {
        background: #fed7aa;
        color: #9a3412;
    }
    
    .badge-preview.priority-critical {
        background: #fee2e2;
        color: #991b1b;
        animation: pulse 2s ease-in-out infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.85; transform: scale(1.03); }
    }
    
    .user-avatar {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-size: 10px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 8px;
    }
    
    .assignee-preview {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.05));
        border: 2px solid rgba(99, 102, 241, 0.2);
        border-radius: 12px;
        padding: 8px 14px;
        margin-top: 8px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 600;
        color: var(--dark);
        transition: all 0.3s ease;
    }
    
    .assignee-preview.unassigned {
        background: linear-gradient(135deg, rgba(148, 163, 184, 0.1), rgba(100, 116, 139, 0.05));
        border-color: rgba(148, 163, 184, 0.2);
    }
    
    .due-date-preview {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(37, 99, 235, 0.05));
        border: 2px solid rgba(59, 130, 246, 0.2);
        border-radius: 12px;
        padding: 8px 14px;
        margin-top: 8px;
        font-size: 12px;
        font-weight: 600;
        color: var(--dark);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }
    
    .due-date-preview.no-date {
        background: linear-gradient(135deg, rgba(148, 163, 184, 0.1), rgba(100, 116, 139, 0.05));
        border-color: rgba(148, 163, 184, 0.2);
    }
    
    .due-date-preview i {
        color: #3b82f6;
    }
    
    .due-date-preview.no-date i {
        color: #94a3b8;
    }
    
    .hours-preview {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(124, 58, 237, 0.05));
        border: 2px solid rgba(139, 92, 246, 0.2);
        border-radius: 12px;
        padding: 8px 14px;
        margin-top: 8px;
        font-size: 12px;
        font-weight: 600;
        color: var(--dark);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .hours-preview i {
        color: var(--secondary);
    }
    
    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 2px solid var(--border);
        flex-wrap: wrap;
    }
    
    .btn-modern {
        padding: 12px 24px;
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
    
    .btn-modern.primary:hover,
    .btn-modern.primary:focus {
        background: linear-gradient(135deg, var(--primary-dark), var(--primary));
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        color: white;
    }
    
    .btn-modern.secondary {
        background: white;
        color: var(--primary);
        border: 2px solid var(--primary);
    }
    
    .btn-modern.secondary:hover,
    .btn-modern.secondary:focus {
        background: var(--primary);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(99, 102, 241, 0.3);
    }
    
    .char-counter {
        font-size: 11px;
        color: #94a3b8;
        font-weight: 600;
        margin-top: 4px;
        text-align: right;
    }
    
    .char-counter.warning {
        color: var(--warning);
    }
    
    .char-counter.danger {
        color: var(--danger);
    }
    
    /* RESPONSIVE */
    @media (max-width: 1200px) {
        .task-create-container { padding: 20px; }
        .task-create-header { padding: 28px; }
        .form-card { padding: 28px; }
    }
    
    @media (max-width: 768px) {
        .task-create-container { padding: 16px; }
        .task-create-header {
            padding: 24px 20px;
            margin-bottom: 24px;
        }
        .task-create-header h1 {
            font-size: 24px;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        .form-card { padding: 20px; }
        .form-actions {
            flex-direction: column;
        }
        .btn-modern {
            width: 100%;
            justify-content: center;
        }
    }
    
    @media (max-width: 480px) {
        .task-create-container { padding: 12px; }
        .task-create-header { padding: 20px; }
        .task-create-header h1 { font-size: 20px; }
        .form-card { padding: 16px; }
        .form-control-modern {
            padding: 10px 14px;
            font-size: 13px;
        }
        .input-icon-wrapper .form-control-modern {
            padding-left: 40px;
        }
    }
</style>

<div class="task-create-container container-fluid">
    <div class="task-create-header">
        <h1>
            <i class="fa fa-plus-circle"></i> Create Task
        </h1>
        <div class="task-create-breadcrumb">
            <a href="project-detail.php?id=<?php echo $project_id; ?>&tab=tasks">
                <i class="fa fa-tasks"></i> Project Tasks
            </a>
            <span>/</span>
            <span style="color: #1e293b; font-weight: 600;">Create Task</span>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="form-card">
                <form method="POST" action="" id="taskForm">
                    <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                    
                    <!-- BASIC INFORMATION -->
                    <div class="form-section-title">
                        <i class="fa fa-file-text"></i> Basic Information
                    </div>
                    
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
                                      style="padding-left: 44px;"></textarea>
                        </div>
                        <div class="char-counter" id="descCounter">0 / 1000 characters</div>
                    </div>
                    
                    <!-- ASSIGNMENT & SCHEDULING -->
                    <div class="form-section-title" style="margin-top: 32px;">
                        <i class="fa fa-users"></i> Assignment & Scheduling
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
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
                        <div class="col-md-6">
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
                                <div class="due-date-preview no-date" id="dueDatePreview">
                                    <i class="fa fa-calendar-times-o"></i> No due date set
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- CLASSIFICATION & DETAILS -->
                    <div class="form-section-title" style="margin-top: 32px;">
                        <i class="fa fa-cogs"></i> Classification & Details
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
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
                                <div class="badge-preview status-todo" id="statusPreview">
                                    <i class="fa fa-clock-o"></i> To Do
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
                                <div class="hours-preview" id="hoursPreview">
                                    <i class="fa fa-hourglass-o"></i> No estimate
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- OPTIONAL DETAILS -->
                    <div class="form-section-title" style="margin-top: 32px;">
                        <i class="fa fa-plus"></i> Optional Details
                    </div>
                    
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
                    
                    <!-- INFO CARD -->
                    <div class="task-info-card">
                        <i class="fa fa-info-circle"></i>
                        <strong>Task Guidelines:</strong>
                        <span class="info-text">Be clear and actionable. Define what needs to be done, who should do it, and when it should be completed.</span>
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
    
    // ASSIGNEE PREVIEW
    $('#assigned_to').on('change', function() {
        const $selected = $(this).find(':selected');
        const $preview = $('#assigneePreview');
        
        if ($(this).val() === '') {
            $preview.attr('class', 'assignee-preview unassigned');
            $preview.html('<i class="fa fa-user-times"></i> Unassigned');
        } else {
            const name = $selected.data('name');
            const role = $selected.data('role');
            const initials = name.split(' ').map(n => n.charAt(0)).join('').substring(0, 2);
            
            $preview.attr('class', 'assignee-preview');
            $preview.html('<div class="user-avatar">' + initials + '</div>' + name + ' (' + role + ')');
        }
        
        // Animate change
        $preview.css('transform', 'scale(1.05)');
        setTimeout(function() {
            $preview.css('transform', 'scale(1)');
        }, 200);
    });
    
    // DUE DATE PREVIEW
    $('#due_date').on('change', function() {
        const $preview = $('#dueDatePreview');
        const value = $(this).val();
        
        if (value === '') {
            $preview.attr('class', 'due-date-preview no-date');
            $preview.html('<i class="fa fa-calendar-times-o"></i> No due date set');
        } else {
            const date = new Date(value);
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            const formattedDate = date.toLocaleDateString('en-US', options);
            
            $preview.attr('class', 'due-date-preview');
            $preview.html('<i class="fa fa-calendar"></i> Due: ' + formattedDate);
        }
        
        // Animate change
        $preview.css('transform', 'scale(1.05)');
        setTimeout(function() {
            $preview.css('transform', 'scale(1)');
        }, 200);
    });
    
    // ESTIMATED HOURS PREVIEW
    $('#estimated_hours').on('input', function() {
        const $preview = $('#hoursPreview');
        const value = parseFloat($(this).val());
        
        if (isNaN(value) || value <= 0) {
            $preview.html('<i class="fa fa-hourglass-o"></i> No estimate');
        } else {
            const unit = value === 1 ? 'hour' : 'hours';
            $preview.html('<i class="fa fa-hourglass-half"></i> ' + value + ' ' + unit);
        }
        
        // Animate change
        $preview.css('transform', 'scale(1.05)');
        setTimeout(function() {
            $preview.css('transform', 'scale(1)');
        }, 200);
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
        
        $preview.attr('class', 'badge-preview status-' + value);
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
    
    // INPUT FOCUS EFFECTS
    $('.form-control-modern').on('focus', function() {
        $(this).closest('.form-group-modern').find('label').css({
            'color': 'var(--primary)',
            'transform': 'scale(1.05)',
            'transition': 'all 0.3s ease'
        });
    }).on('blur', function() {
        $(this).closest('.form-group-modern').find('label').css({
            'color': 'var(--dark)',
            'transform': 'scale(1)'
        });
    });
    
    // FORM VALIDATION ENHANCEMENT
    $('#taskForm').on('submit', function(e) {
        let isValid = true;
        
        $('.form-control-modern[required]').each(function() {
            if ($(this).val().trim() === '') {
                isValid = false;
                $(this).css('border-color', 'var(--danger)');
                $(this).on('input', function() {
                    $(this).css('border-color', 'var(--border)');
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
});
</script>

<?php require_once 'includes/footer.php'; ?>