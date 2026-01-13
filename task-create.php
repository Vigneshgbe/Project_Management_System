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
    /* CSS VARIABLES */
    :root {
        --primary: #6366f1;
        --primary-hover: #4f46e5;
        --secondary: #8b5cf6;
        --bg-light: #f8fafc;
        --border: #e2e8f0;
        --text-dark: #1e293b;
        --text-muted: #64748b;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
        --shadow-md: 0 4px 6px rgba(0,0,0,0.07);
        --radius: 12px;
        --transition: all 0.2s ease;
    }
    
    /* BASE LAYOUT */
    .task-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 24px 16px;
    }
    
    /* HEADER */
    .page-header {
        background: white;
        padding: 20px 24px;
        border-radius: var(--radius);
        margin-bottom: 24px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border);
    }
    
    .page-header h1 {
        font-size: 24px;
        font-weight: 700;
        color: var(--text-dark);
        margin: 0 0 8px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .page-header h1 i {
        color: var(--primary);
        font-size: 22px;
    }
    
    .breadcrumb {
        font-size: 13px;
        color: var(--text-muted);
        font-weight: 500;
    }
    
    .breadcrumb a {
        color: var(--primary);
        text-decoration: none;
        transition: var(--transition);
    }
    
    .breadcrumb a:hover {
        color: var(--primary-hover);
    }
    
    .breadcrumb-sep {
        margin: 0 8px;
        color: var(--border);
    }
    
    /* FORM CARD */
    .form-card {
        background: white;
        border-radius: var(--radius);
        padding: 32px 24px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border);
    }
    
    /* FORM SECTIONS */
    .form-section {
        margin-bottom: 28px;
    }
    
    .section-title {
        font-size: 14px;
        font-weight: 700;
        color: var(--text-dark);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 16px;
        padding-bottom: 10px;
        border-bottom: 2px solid var(--border);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .section-title i {
        color: var(--primary);
        font-size: 15px;
    }
    
    /* FORM GROUPS */
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 6px;
    }
    
    .form-group label .required {
        color: var(--danger);
        margin-left: 2px;
    }
    
    /* FORM CONTROLS */
    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1.5px solid var(--border);
        border-radius: 8px;
        font-size: 14px;
        color: var(--text-dark);
        background: white;
        transition: var(--transition);
        font-family: inherit;
    }
    
    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
    }
    
    .form-control::placeholder {
        color: #94a3b8;
    }
    
    textarea.form-control {
        resize: vertical;
        min-height: 100px;
    }
    
    select.form-control {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 10 10'%3E%3Cpath fill='%236366f1' d='M5 7L1 3h8z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 36px;
    }
    
    input[type="number"].form-control,
    input[type="date"].form-control {
        cursor: pointer;
    }
    
    /* INPUT WITH ICON */
    .input-icon {
        position: relative;
    }
    
    .input-icon i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        font-size: 14px;
    }
    
    .input-icon .form-control {
        padding-left: 38px;
    }
    
    .input-icon.textarea-icon i {
        top: 14px;
        transform: none;
    }
    
    /* CHARACTER COUNTER */
    .char-count {
        font-size: 11px;
        color: var(--text-muted);
        margin-top: 4px;
        text-align: right;
    }
    
    .char-count.warning {
        color: var(--warning);
    }
    
    .char-count.danger {
        color: var(--danger);
    }
    
    /* PREVIEW BADGES */
    .preview-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        margin-top: 6px;
        transition: var(--transition);
    }
    
    /* STATUS BADGES */
    .preview-badge.status-todo {
        background: #f1f5f9;
        color: #475569;
    }
    
    .preview-badge.status-in_progress {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .preview-badge.status-review {
        background: #fef3c7;
        color: #92400e;
    }
    
    .preview-badge.status-completed {
        background: #d1fae5;
        color: #065f46;
    }
    
    /* PRIORITY BADGES */
    .preview-badge.priority-low {
        background: #d1fae5;
        color: #065f46;
    }
    
    .preview-badge.priority-medium {
        background: #fef3c7;
        color: #92400e;
    }
    
    .preview-badge.priority-high {
        background: #fed7aa;
        color: #9a3412;
    }
    
    .preview-badge.priority-critical {
        background: #fee2e2;
        color: #991b1b;
    }
    
    /* ASSIGNEE PREVIEW */
    .assignee-preview {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        background: #f1f5f9;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        color: var(--text-dark);
        margin-top: 6px;
    }
    
    .assignee-preview.has-user {
        background: #ede9fe;
    }
    
    .user-avatar {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-size: 9px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* DATE & HOURS PREVIEW */
    .date-preview,
    .hours-preview {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: #f1f5f9;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        color: var(--text-dark);
        margin-top: 6px;
    }
    
    .date-preview.has-date {
        background: #dbeafe;
    }
    
    .hours-preview.has-hours {
        background: #ede9fe;
    }
    
    /* INFO BOX */
    .info-box {
        background: #f0f9ff;
        border-left: 3px solid var(--primary);
        padding: 12px 16px;
        border-radius: 6px;
        font-size: 13px;
        color: var(--text-dark);
        margin: 20px 0;
    }
    
    .info-box i {
        color: var(--primary);
        margin-right: 8px;
    }
    
    /* BUTTONS */
    .form-actions {
        display: flex;
        gap: 12px;
        padding-top: 24px;
        border-top: 2px solid var(--border);
        margin-top: 28px;
    }
    
    .btn {
        padding: 11px 24px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: var(--transition);
        border: none;
        text-transform: none;
    }
    
    .btn-primary {
        background: var(--primary);
        color: white;
        box-shadow: 0 2px 8px rgba(99,102,241,0.2);
    }
    
    .btn-primary:hover {
        background: var(--primary-hover);
        box-shadow: 0 4px 12px rgba(99,102,241,0.3);
        transform: translateY(-1px);
        color: white;
    }
    
    .btn-secondary {
        background: white;
        color: var(--text-dark);
        border: 1.5px solid var(--border);
    }
    
    .btn-secondary:hover {
        background: var(--bg-light);
        border-color: var(--text-muted);
        color: var(--text-dark);
    }
    
    /* GRID LAYOUT */
    .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    
    .grid-3 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 16px;
    }
    
    /* RESPONSIVE */
    @media (max-width: 768px) {
        .task-container {
            padding: 16px 12px;
        }
        
        .page-header {
            padding: 16px 20px;
        }
        
        .page-header h1 {
            font-size: 20px;
        }
        
        .form-card {
            padding: 24px 20px;
        }
        
        .grid-2,
        .grid-3 {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .form-actions {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
            justify-content: center;
        }
    }
    
    @media (max-width: 480px) {
        .page-header h1 {
            font-size: 18px;
        }
        
        .form-card {
            padding: 20px 16px;
        }
    }
</style>

<div class="task-container">
    <!-- HEADER -->
    <div class="page-header">
        <h1>
            <i class="fa fa-plus-circle"></i>
            Create Task
        </h1>
        <div class="breadcrumb">
            <a href="project-detail.php?id=<?php echo $project_id; ?>&tab=tasks">
                <i class="fa fa-tasks"></i> Project Tasks
            </a>
            <span class="breadcrumb-sep">/</span>
            <span>Create Task</span>
        </div>
    </div>
    
    <!-- FORM -->
    <div class="form-card">
        <form method="POST" id="taskForm">
            <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
            
            <!-- BASIC INFORMATION -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fa fa-file-text"></i>
                    Basic Information
                </div>
                
                <div class="form-group">
                    <label for="task_name">
                        Task Name <span class="required">*</span>
                    </label>
                    <div class="input-icon">
                        <i class="fa fa-pencil"></i>
                        <input type="text" 
                               class="form-control" 
                               id="task_name" 
                               name="task_name" 
                               placeholder="Enter task name"
                               maxlength="200"
                               required>
                    </div>
                    <div class="char-count" id="nameCount">0 / 200</div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <div class="input-icon textarea-icon">
                        <i class="fa fa-align-left"></i>
                        <textarea class="form-control" 
                                  id="description" 
                                  name="description" 
                                  placeholder="Describe the task in detail"
                                  maxlength="1000"
                                  style="padding-left: 38px;"></textarea>
                    </div>
                    <div class="char-count" id="descCount">0 / 1000</div>
                </div>
            </div>
            
            <!-- ASSIGNMENT & SCHEDULING -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fa fa-users"></i>
                    Assignment & Scheduling
                </div>
                
                <div class="grid-2">
                    <div class="form-group">
                        <label for="assigned_to">Assign To</label>
                        <div class="input-icon">
                            <i class="fa fa-user"></i>
                            <select class="form-control" id="assigned_to" name="assigned_to">
                                <option value="">Unassigned</option>
                                <?php foreach ($users as $u): ?>
                                <option value="<?php echo $u['id']; ?>" 
                                        data-name="<?php echo htmlspecialchars($u['full_name']); ?>" 
                                        data-role="<?php echo ucfirst($u['role']); ?>">
                                    <?php echo htmlspecialchars($u['full_name']); ?> (<?php echo ucfirst($u['role']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="assignee-preview" id="assigneePreview">
                            <i class="fa fa-user-times"></i> Unassigned
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="due_date">Due Date</label>
                        <div class="input-icon">
                            <i class="fa fa-calendar"></i>
                            <input type="date" 
                                   class="form-control" 
                                   id="due_date" 
                                   name="due_date">
                        </div>
                        <div class="date-preview" id="datePreview">
                            <i class="fa fa-calendar-times-o"></i> No due date
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- CLASSIFICATION -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fa fa-cogs"></i>
                    Classification & Details
                </div>
                
                <div class="grid-3">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <div class="input-icon">
                            <i class="fa fa-circle"></i>
                            <select class="form-control" id="status" name="status">
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
                    
                    <div class="form-group">
                        <label for="priority">Priority</label>
                        <div class="input-icon">
                            <i class="fa fa-flag"></i>
                            <select class="form-control" id="priority" name="priority">
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
                    
                    <div class="form-group">
                        <label for="estimated_hours">Estimated Hours</label>
                        <div class="input-icon">
                            <i class="fa fa-clock-o"></i>
                            <input type="number" 
                                   class="form-control" 
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
            
            <!-- OPTIONAL -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fa fa-plus"></i>
                    Optional Details
                </div>
                
                <div class="form-group">
                    <label for="phase_id">Phase ID</label>
                    <div class="input-icon">
                        <i class="fa fa-layer-group"></i>
                        <input type="number" 
                               class="form-control" 
                               id="phase_id" 
                               name="phase_id" 
                               placeholder="Enter phase ID if applicable">
                    </div>
                </div>
            </div>
            
            <div class="info-box">
                <i class="fa fa-info-circle"></i>
                <strong>Task Guidelines:</strong> Be clear and actionable. Define what needs to be done, who should do it, and when it should be completed.
            </div>
            
            <!-- ACTIONS -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> Create Task
                </button>
                <a href="project-detail.php?id=<?php echo $project_id; ?>&tab=tasks" class="btn btn-secondary">
                    <i class="fa fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Character counters
    $('#task_name').on('input', function() {
        const len = $(this).val().length;
        const $counter = $('#nameCount');
        $counter.text(len + ' / 200');
        
        if (len > 180) $counter.addClass('danger').removeClass('warning');
        else if (len > 150) $counter.addClass('warning').removeClass('danger');
        else $counter.removeClass('warning danger');
    });
    
    $('#description').on('input', function() {
        const len = $(this).val().length;
        const $counter = $('#descCount');
        $counter.text(len + ' / 1000');
        
        if (len > 900) $counter.addClass('danger').removeClass('warning');
        else if (len > 750) $counter.addClass('warning').removeClass('danger');
        else $counter.removeClass('warning danger');
    });
    
    // Assignee preview
    $('#assigned_to').on('change', function() {
        const $opt = $(this).find(':selected');
        const $prev = $('#assigneePreview');
        
        if (!$(this).val()) {
            $prev.removeClass('has-user').html('<i class="fa fa-user-times"></i> Unassigned');
        } else {
            const name = $opt.data('name');
            const role = $opt.data('role');
            const initials = name.split(' ').map(n => n.charAt(0)).join('').substring(0,2);
            $prev.addClass('has-user').html(
                '<div class="user-avatar">' + initials + '</div>' + 
                name + ' <span style="color: var(--text-muted);">(' + role + ')</span>'
            );
        }
    });
    
    // Due date preview
    $('#due_date').on('change', function() {
        const $prev = $('#datePreview');
        if (!$(this).val()) {
            $prev.removeClass('has-date').html('<i class="fa fa-calendar-times-o"></i> No due date');
        } else {
            const date = new Date($(this).val());
            const formatted = date.toLocaleDateString('en-US', {year:'numeric', month:'short', day:'numeric'});
            $prev.addClass('has-date').html('<i class="fa fa-calendar"></i> ' + formatted);
        }
    });
    
    // Hours preview
    $('#estimated_hours').on('input', function() {
        const $prev = $('#hoursPreview');
        const val = parseFloat($(this).val());
        
        if (isNaN(val) || val <= 0) {
            $prev.removeClass('has-hours').html('<i class="fa fa-hourglass-o"></i> No estimate');
        } else {
            const unit = val === 1 ? 'hour' : 'hours';
            $prev.addClass('has-hours').html('<i class="fa fa-hourglass-half"></i> ' + val + ' ' + unit);
        }
    });
    
    // Status preview
    $('#status').on('change', function() {
        const val = $(this).val();
        const labels = {
            'todo': '<i class="fa fa-clock-o"></i> To Do',
            'in_progress': '<i class="fa fa-spinner"></i> In Progress',
            'review': '<i class="fa fa-eye"></i> Review',
            'completed': '<i class="fa fa-check-circle"></i> Completed'
        };
        $('#statusPreview').attr('class', 'preview-badge status-' + val).html(labels[val]);
    });
    
    // Priority preview
    $('#priority').on('change', function() {
        const val = $(this).val();
        const labels = {
            'low': '<i class="fa fa-flag"></i> Low',
            'medium': '<i class="fa fa-flag"></i> Medium',
            'high': '<i class="fa fa-flag"></i> High',
            'critical': '<i class="fa fa-exclamation-triangle"></i> Critical'
        };
        $('#priorityPreview').attr('class', 'preview-badge priority-' + val).html(labels[val]);
    });
    
    // Form validation
    $('#taskForm').on('submit', function(e) {
        let valid = true;
        $('.form-control[required]').each(function() {
            if (!$(this).val().trim()) {
                valid = false;
                $(this).css('border-color', 'var(--danger)');
            }
        });
        
        if (!valid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });
    
    // Reset border on input
    $('.form-control').on('input', function() {
        $(this).css('border-color', 'var(--border)');
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>