<?php
$page_title = 'Edit Task';
require_once 'includes/header.php';
require_once 'components/task.php';
require_once 'components/user.php';

$auth->checkAccess();

$task_id = $_GET['id'] ?? 0;
$task_obj = new Task();
$task = $task_obj->getById($task_id);

if (!$task) {
    header('Location: tasks.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'task_name' => $_POST['task_name'],
        'description' => $_POST['description'],
        'assigned_to' => $_POST['assigned_to'] ?: null,
        'status' => $_POST['status'],
        'priority' => $_POST['priority'],
        'due_date' => $_POST['due_date'] ?: null,
        'estimated_hours' => $_POST['estimated_hours'] ?: null,
        'actual_hours' => $_POST['actual_hours'] ?: null
    ];
    
    if ($task_obj->update($task_id, $data)) {
        header('Location: project-detail.php?id=' . $task['project_id'] . '&tab=tasks');
        exit;
    }
}

$user = new User();
$users = $user->getActiveUsers();
?>

<style>
    .task-edit-container {
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
    
    .task-edit-header {
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
    
    .task-edit-header::before {
        content: '' !important;
        position: absolute !important;
        top: -50% !important;
        right: -50% !important;
        width: 200% !important;
        height: 200% !important;
        background: radial-gradient(circle, rgba(34, 197, 94, 0.1) 0%, transparent 70%) !important;
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
    
    .task-edit-header h1 {
        margin: 0 !important;
        font-weight: 800 !important;
        font-size: 32px !important;
        position: relative !important;
        z-index: 1 !important;
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
        display: flex !important;
        align-items: center !important;
        gap: 15px !important;
    }
    
    .task-edit-header h1 i {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }
    
    .task-edit-breadcrumb {
        margin-top: 15px !important;
        position: relative !important;
        z-index: 1 !important;
    }
    
    .task-edit-breadcrumb a {
        color: #22c55e !important;
        text-decoration: none !important;
        font-weight: 600 !important;
        transition: color 0.3s ease !important;
    }
    
    .task-edit-breadcrumb a:hover {
        color: #16a34a !important;
    }
    
    .task-edit-breadcrumb span {
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
    
    .info-sidebar {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        border-radius: 20px !important;
        padding: 30px !important;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        animation: slideUp 0.7s ease !important;
        height: fit-content !important;
        position: sticky !important;
        top: 20px !important;
    }
    
    .info-sidebar-title {
        font-size: 18px !important;
        font-weight: 700 !important;
        color: #1e293b !important;
        margin-bottom: 25px !important;
        padding-bottom: 15px !important;
        border-bottom: 3px solid transparent !important;
        border-image: linear-gradient(90deg, #22c55e 0%, #16a34a 100%) !important;
        border-image-slice: 1 !important;
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
    }
    
    .info-sidebar-title i {
        color: #22c55e !important;
    }
    
    .info-item {
        margin-bottom: 20px !important;
        padding: 15px !important;
        background: linear-gradient(135deg, rgba(34, 197, 94, 0.05) 0%, rgba(22, 163, 74, 0.02) 100%) !important;
        border: 1px solid rgba(34, 197, 94, 0.1) !important;
        border-radius: 12px !important;
    }
    
    .info-item:last-child {
        margin-bottom: 0 !important;
    }
    
    .info-label {
        font-size: 12px !important;
        font-weight: 700 !important;
        color: #64748b !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        margin-bottom: 6px !important;
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
    }
    
    .info-label i {
        color: #22c55e !important;
        font-size: 10px !important;
    }
    
    .info-value {
        color: #1e293b !important;
        font-weight: 600 !important;
        font-size: 14px !important;
        line-height: 1.4 !important;
    }
    
    .form-section-title {
        font-size: 18px !important;
        font-weight: 700 !important;
        color: #1e293b !important;
        margin-bottom: 25px !important;
        padding-bottom: 15px !important;
        border-bottom: 3px solid transparent !important;
        border-image: linear-gradient(90deg, #22c55e 0%, #16a34a 100%) !important;
        border-image-slice: 1 !important;
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
    }
    
    .form-section-title i {
        color: #22c55e !important;
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
        border-color: #22c55e !important;
        box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1) !important;
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
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2322c55e' d='M6 9L1 4h10z'/%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important;
        background-position: right 18px center !important;
        padding-right: 45px !important;
    }
    
    input[type="number"].form-control-modern,
    input[type="date"].form-control-modern {
        cursor: pointer !important;
    }
    
    .input-icon-wrapper {
        position: relative !important;
    }
    
    .input-icon-wrapper i {
        position: absolute !important;
        left: 18px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        color: #22c55e !important;
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
    
    .task-current-values {
        background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(22, 163, 74, 0.05) 100%) !important;
        padding: 20px !important;
        border-radius: 12px !important;
        border: 2px solid rgba(34, 197, 94, 0.2) !important;
        margin-bottom: 25px !important;
    }
    
    .task-current-values i {
        font-size: 18px !important;
        color: #22c55e !important;
        margin-right: 10px !important;
    }
    
    .task-current-values strong {
        color: #1e293b !important;
        font-weight: 700 !important;
    }
    
    .task-current-values .info-text {
        color: #64748b !important;
        font-weight: 600 !important;
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
        color: white !important;
    }
    
    .badge-preview.status-todo {
        background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%) !important;
    }
    
    .badge-preview.status-in_progress {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
    }
    
    .badge-preview.status-review {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
    }
    
    .badge-preview.status-completed {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    }
    
    .badge-preview.priority-low {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    }
    
    .badge-preview.priority-medium {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
    }
    
    .badge-preview.priority-high {
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important;
    }
    
    .badge-preview.priority-critical {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        animation: pulse 2s ease-in-out infinite !important;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.85; transform: scale(1.05); }
    }
    
    .user-avatar {
        width: 20px !important;
        height: 20px !important;
        border-radius: 50% !important;
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
        color: white !important;
        font-size: 10px !important;
        font-weight: 700 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin-right: 8px !important;
    }
    
    .assignee-preview {
        background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(22, 163, 74, 0.05) 100%) !important;
        border: 2px solid rgba(34, 197, 94, 0.2) !important;
        border-radius: 20px !important;
        padding: 8px 16px !important;
        margin-top: 8px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        color: #1e293b !important;
    }
    
    .assignee-preview.unassigned {
        background: linear-gradient(135deg, rgba(148, 163, 184, 0.1) 0%, rgba(100, 116, 139, 0.05) 100%) !important;
        border-color: rgba(148, 163, 184, 0.2) !important;
    }
    
    .due-date-preview {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(37, 99, 235, 0.05) 100%) !important;
        border: 2px solid rgba(59, 130, 246, 0.2) !important;
        border-radius: 12px !important;
        padding: 10px 16px !important;
        margin-top: 8px !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        color: #1e293b !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
    }
    
    .due-date-preview.no-date {
        background: linear-gradient(135deg, rgba(148, 163, 184, 0.1) 0%, rgba(100, 116, 139, 0.05) 100%) !important;
        border-color: rgba(148, 163, 184, 0.2) !important;
    }
    
    .due-date-preview i {
        color: #3b82f6 !important;
    }
    
    .due-date-preview.no-date i {
        color: #94a3b8 !important;
    }
    
    .hours-preview {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(124, 58, 237, 0.05) 100%) !important;
        border: 2px solid rgba(139, 92, 246, 0.2) !important;
        border-radius: 12px !important;
        padding: 10px 16px !important;
        margin-top: 8px !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        color: #1e293b !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
    }
    
    .hours-preview i {
        color: #8b5cf6 !important;
    }
    
    .hours-comparison {
        display: flex !important;
        gap: 15px !important;
        margin-top: 15px !important;
    }
    
    .hours-comparison .hours-preview {
        flex: 1 !important;
        margin-top: 0 !important;
        text-align: center !important;
        justify-content: center !important;
    }
    
    .hours-comparison .actual-hours {
        background: linear-gradient(135deg, rgba(249, 115, 22, 0.1) 0%, rgba(234, 88, 12, 0.05) 100%) !important;
        border-color: rgba(249, 115, 22, 0.2) !important;
    }
    
    .hours-comparison .actual-hours i {
        color: #f97316 !important;
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
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
        color: white !important;
        box-shadow: 0 5px 20px rgba(34, 197, 94, 0.3) !important;
    }
    
    .btn-modern.primary:hover {
        background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4) !important;
    }
    
    .btn-modern.secondary {
        background: white !important;
        color: #22c55e !important;
        border: 2px solid #22c55e !important;
    }
    
    .btn-modern.secondary:hover {
        background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(22, 163, 74, 0.1) 100%) !important;
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
    
    /* RESPONSIVE */
    @media (max-width: 1200px) {
        .task-edit-container {
            padding: 15px !important;
        }
        .task-edit-header {
            padding: 25px 30px !important;
        }
        .form-card {
            padding: 30px !important;
        }
        .info-sidebar {
            padding: 25px !important;
        }
    }
    
    @media (max-width: 768px) {
        .task-edit-container {
            padding: 10px !important;
        }
        .task-edit-header {
            padding: 20px !important;
            margin-bottom: 20px !important;
        }
        .task-edit-header h1 {
            font-size: 24px !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 10px !important;
        }
        .form-card {
            padding: 20px !important;
        }
        .info-sidebar {
            padding: 20px !important;
            margin-top: 20px !important;
        }
        .form-actions {
            flex-direction: column !important;
        }
        .btn-modern {
            width: 100% !important;
            justify-content: center !important;
        }
        .hours-comparison {
            flex-direction: column !important;
        }
    }
    
    @media (max-width: 480px) {
        .task-edit-container {
            padding: 8px !important;
        }
        .task-edit-header h1 {
            font-size: 20px !important;
        }
        .form-card {
            padding: 15px !important;
        }
        .info-sidebar {
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

<div class="task-edit-container container-fluid">
    <div class="task-edit-header">
        <h1>
            <i class="fa fa-edit"></i> Edit Task
        </h1>
        <div class="task-edit-breadcrumb">
            <a href="project-detail.php?id=<?php echo $task['project_id']; ?>&tab=tasks">
                <i class="fa fa-tasks"></i> Project Tasks
            </a>
            <span>/</span>
            <span style="color: #1e293b; font-weight: 600;">Edit Task</span>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="form-card">
                <!-- CURRENT VALUES INFO -->
                <div class="task-current-values">
                    <i class="fa fa-info-circle"></i>
                    <strong>Editing:</strong>
                    <span class="info-text"><?php echo htmlspecialchars($task['task_name']); ?></span>
                </div>
                
                <form method="POST" action="" id="taskForm">
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
                                   value="<?php echo htmlspecialchars($task['task_name']); ?>"
                                   placeholder="Enter task name"
                                   maxlength="200"
                                   required>
                        </div>
                        <div class="char-counter" id="taskNameCounter"><?php echo strlen($task['task_name']); ?> / 200 characters</div>
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
                                      style="padding-left: 45px;"><?php echo htmlspecialchars($task['description']); ?></textarea>
                        </div>
                        <div class="char-counter" id="descCounter"><?php echo strlen($task['description']); ?> / 1000 characters</div>
                    </div>
                    
                    <!-- ASSIGNMENT & SCHEDULING -->
                    <div class="form-section-title" style="margin-top: 40px;">
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
                                        <option value="<?php echo $u['id']; ?>" 
                                                data-name="<?php echo htmlspecialchars($u['full_name']); ?>" 
                                                data-role="<?php echo ucfirst($u['role']); ?>"
                                                <?php echo $task['assigned_to'] == $u['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($u['full_name']); ?> (<?php echo ucfirst($u['role']); ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="assignee-preview <?php echo !$task['assigned_to'] ? 'unassigned' : ''; ?>" id="assigneePreview">
                                    <?php if ($task['assigned_to']): ?>
                                        <?php 
                                        $assigned_user = null;
                                        foreach ($users as $u) {
                                            if ($u['id'] == $task['assigned_to']) {
                                                $assigned_user = $u;
                                                break;
                                            }
                                        }
                                        if ($assigned_user): 
                                            $initials = '';
                                            $name_parts = explode(' ', $assigned_user['full_name']);
                                            foreach ($name_parts as $part) {
                                                $initials .= strtoupper(substr($part, 0, 1));
                                            }
                                            $initials = substr($initials, 0, 2);
                                        ?>
                                        <div class="user-avatar"><?php echo $initials; ?></div>
                                        <?php echo htmlspecialchars($assigned_user['full_name']); ?> (<?php echo ucfirst($assigned_user['role']); ?>)
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <i class="fa fa-user-times"></i> Unassigned
                                    <?php endif; ?>
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
                                           name="due_date"
                                           value="<?php echo $task['due_date']; ?>">
                                </div>
                                <div class="due-date-preview <?php echo !$task['due_date'] ? 'no-date' : ''; ?>" id="dueDatePreview">
                                    <?php if ($task['due_date']): ?>
                                        <i class="fa fa-calendar"></i> Due: <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                                    <?php else: ?>
                                        <i class="fa fa-calendar-times-o"></i> No due date set
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- CLASSIFICATION -->
                    <div class="form-section-title" style="margin-top: 40px;">
                        <i class="fa fa-cogs"></i> Classification
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
                                        <option value="todo" <?php echo $task['status'] === 'todo' ? 'selected' : ''; ?>>To Do</option>
                                        <option value="in_progress" <?php echo $task['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="review" <?php echo $task['status'] === 'review' ? 'selected' : ''; ?>>Review</option>
                                        <option value="completed" <?php echo $task['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    </select>
                                </div>
                                <div class="badge-preview status-<?php echo $task['status']; ?>" id="statusPreview">
                                    <i class="fa fa-<?php 
                                        echo $task['status'] === 'todo' ? 'clock-o' : 
                                            ($task['status'] === 'in_progress' ? 'spinner' : 
                                            ($task['status'] === 'review' ? 'eye' : 'check-circle')); 
                                    ?>"></i>
                                    <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
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
                                        <option value="low" <?php echo $task['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                                        <option value="medium" <?php echo $task['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                        <option value="high" <?php echo $task['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                                        <option value="critical" <?php echo $task['priority'] === 'critical' ? 'selected' : ''; ?>>Critical</option>
                                    </select>
                                </div>
                                <div class="badge-preview priority-<?php echo $task['priority']; ?>" id="priorityPreview">
                                    <i class="fa fa-flag"></i>
                                    <?php echo ucfirst($task['priority']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- TIME TRACKING -->
                    <div class="form-section-title" style="margin-top: 40px;">
                        <i class="fa fa-clock-o"></i> Time Tracking
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label for="estimated_hours">
                                    Estimated Hours
                                </label>
                                <div class="input-icon-wrapper">
                                    <i class="fa fa-hourglass-start"></i>
                                    <input type="number" 
                                           class="form-control-modern" 
                                           id="estimated_hours" 
                                           name="estimated_hours" 
                                           step="0.5" 
                                           min="0"
                                           value="<?php echo $task['estimated_hours']; ?>"
                                           placeholder="0.0">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label for="actual_hours">
                                    Actual Hours
                                </label>
                                <div class="input-icon-wrapper">
                                    <i class="fa fa-hourglass-end"></i>
                                    <input type="number" 
                                           class="form-control-modern" 
                                           id="actual_hours" 
                                           name="actual_hours" 
                                           step="0.5" 
                                           min="0"
                                           value="<?php echo $task['actual_hours']; ?>"
                                           placeholder="0.0">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- HOURS COMPARISON PREVIEW -->
                    <div class="hours-comparison">
                        <div class="hours-preview" id="estimatedPreview">
                            <i class="fa fa-hourglass-start"></i> 
                            Estimated: <?php echo $task['estimated_hours'] ?: '0'; ?> <?php echo $task['estimated_hours'] == 1 ? 'hour' : 'hours'; ?>
                        </div>
                        <div class="hours-preview actual-hours" id="actualPreview">
                            <i class="fa fa-hourglass-end"></i> 
                            Actual: <?php echo $task['actual_hours'] ?: '0'; ?> <?php echo $task['actual_hours'] == 1 ? 'hour' : 'hours'; ?>
                        </div>
                    </div>
                    
                    <!-- ACTION BUTTONS -->
                    <div class="form-actions">
                        <button type="submit" class="btn-modern primary">
                            <i class="fa fa-save"></i> Update Task
                        </button>
                        <a href="project-detail.php?id=<?php echo $task['project_id']; ?>&tab=tasks" class="btn-modern secondary">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="info-sidebar">
                <div class="info-sidebar-title">
                    <i class="fa fa-info-circle"></i> Task Information
                </div>
                
                <div class="info-item">
                    <div class="info-label">
                        <i class="fa fa-user"></i> Created By
                    </div>
                    <div class="info-value"><?php echo htmlspecialchars($task['creator_name']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">
                        <i class="fa fa-calendar-plus-o"></i> Created
                    </div>
                    <div class="info-value"><?php echo date('M d, Y g:i A', strtotime($task['created_at'])); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">
                        <i class="fa fa-history"></i> Last Updated
                    </div>
                    <div class="info-value"><?php echo date('M d, Y g:i A', strtotime($task['updated_at'])); ?></div>
                </div>
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
        const $preview = $('#estimatedPreview');
        const value = parseFloat($(this).val());
        
        if (isNaN(value) || value <= 0) {
            $preview.html('<i class="fa fa-hourglass-start"></i> Estimated: 0 hours');
        } else {
            const unit = value === 1 ? 'hour' : 'hours';
            $preview.html('<i class="fa fa-hourglass-start"></i> Estimated: ' + value + ' ' + unit);
        }
        
        // Animate change
        $preview.css('transform', 'scale(1.05)');
        setTimeout(function() {
            $preview.css('transform', 'scale(1)');
        }, 200);
    });
    
    // ACTUAL HOURS PREVIEW
    $('#actual_hours').on('input', function() {
        const $preview = $('#actualPreview');
        const value = parseFloat($(this).val());
        
        if (isNaN(value) || value <= 0) {
            $preview.html('<i class="fa fa-hourglass-end"></i> Actual: 0 hours');
        } else {
            const unit = value === 1 ? 'hour' : 'hours';
            $preview.html('<i class="fa fa-hourglass-end"></i> Actual: ' + value + ' ' + unit);
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
            'color': '#22c55e',
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
    $('#taskForm').on('submit', function(e) {
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
});
</script>

<?php require_once 'includes/footer.php'; ?>