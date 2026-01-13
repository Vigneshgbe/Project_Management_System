<?php
$page_title = 'Delete Task';
require_once 'includes/header.php';
require_once 'components/task.php';
require_once 'components/user.php';

$auth->checkAccess();

$task_id = $_GET['id'] ?? 0;
$confirm = $_GET['confirm'] ?? '';

if (!$task_id) {
    header('Location: projects.php');
    exit;
}

$task_obj = new Task();
$task = $task_obj->getById($task_id);

if (!$task) {
    header('Location: projects.php');
    exit;
}

$project_id = $task['project_id'];

// Get assigned user details if task is assigned
$assigned_user = null;
if ($task['assigned_to']) {
    $user_obj = new User();
    $assigned_user = $user_obj->getById($task['assigned_to']);
}

// Handle deletion confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $task_obj->delete($task_id);
    header('Location: project-detail.php?id=' . $project_id . '&tab=tasks&deleted=1');
    exit;
}
?>

<style>
    .task-delete-container {
        background: transparent !important;
        min-height: calc(100vh - 100px) !important;
        padding: 20px !important;
        margin: 0 !important;
        animation: fadeIn 0.5s ease !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .delete-modal-card {
        background: rgba(255, 255, 255, 0.98) !important;
        backdrop-filter: blur(20px) !important;
        border-radius: 24px !important;
        padding: 0 !important;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        animation: scaleIn 0.5s cubic-bezier(0.4, 0, 0.2, 1) !important;
        max-width: 700px !important;
        width: 100% !important;
        overflow: hidden !important;
        margin: 20px !important;
    }
    
    @keyframes scaleIn {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
    }
    
    .delete-modal-header {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        color: white !important;
        padding: 30px 35px !important;
        position: relative !important;
        overflow: hidden !important;
    }
    
    .delete-modal-header::before {
        content: '' !important;
        position: absolute !important;
        top: -50% !important;
        right: -50% !important;
        width: 200% !important;
        height: 200% !important;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, transparent 70%) !important;
        animation: rotate 20s linear infinite !important;
    }
    
    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .delete-modal-header h1 {
        margin: 0 !important;
        font-weight: 800 !important;
        font-size: 28px !important;
        position: relative !important;
        z-index: 1 !important;
        display: flex !important;
        align-items: center !important;
        gap: 15px !important;
    }
    
    .delete-icon-wrapper {
        width: 50px !important;
        height: 50px !important;
        background: rgba(255, 255, 255, 0.2) !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 24px !important;
        animation: pulse 2s ease-in-out infinite !important;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
    
    .delete-modal-body {
        padding: 40px 35px !important;
    }
    
    .warning-message {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.05) 100%) !important;
        border-left: 5px solid #ef4444 !important;
        padding: 20px !important;
        border-radius: 12px !important;
        margin-bottom: 30px !important;
    }
    
    .warning-message i {
        color: #ef4444 !important;
        font-size: 20px !important;
        margin-right: 12px !important;
        vertical-align: middle !important;
    }
    
    .warning-message strong {
        color: #dc2626 !important;
        font-weight: 700 !important;
        display: block !important;
        margin-bottom: 8px !important;
        font-size: 16px !important;
    }
    
    .warning-message p {
        color: #64748b !important;
        margin: 0 !important;
        font-weight: 500 !important;
        line-height: 1.6 !important;
    }
    
    .task-details-box {
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%) !important;
        border: 2px solid #e2e8f0 !important;
        border-radius: 16px !important;
        padding: 25px !important;
        margin-bottom: 30px !important;
    }
    
    .task-details-title {
        font-size: 16px !important;
        font-weight: 700 !important;
        color: #1e293b !important;
        margin-bottom: 20px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
    }
    
    .task-details-title i {
        color: #22c55e !important;
    }
    
    .detail-row {
        display: flex !important;
        justify-content: space-between !important;
        align-items: flex-start !important;
        padding: 12px 0 !important;
        border-bottom: 1px solid #e2e8f0 !important;
    }
    
    .detail-row:last-child {
        border-bottom: none !important;
    }
    
    .detail-label {
        color: #64748b !important;
        font-weight: 600 !important;
        font-size: 14px !important;
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        min-width: 150px !important;
    }
    
    .detail-label i {
        color: #22c55e !important;
        width: 18px !important;
        text-align: center !important;
    }
    
    .detail-value {
        color: #1e293b !important;
        font-weight: 600 !important;
        font-size: 15px !important;
        text-align: right !important;
        flex: 1 !important;
    }
    
    .detail-value.title-value {
        font-weight: 700 !important;
        font-size: 16px !important;
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }
    
    .detail-value.description-value {
        text-align: left !important;
        margin-top: 8px !important;
        line-height: 1.6 !important;
        color: #64748b !important;
    }
    
    .badge-display {
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        padding: 6px 14px !important;
        border-radius: 20px !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        color: white !important;
    }
    
    .badge-display.status-todo {
        background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%) !important;
    }
    
    .badge-display.status-in_progress {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
    }
    
    .badge-display.status-review {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
    }
    
    .badge-display.status-completed {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    }
    
    .badge-display.priority-low {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    }
    
    .badge-display.priority-medium {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
    }
    
    .badge-display.priority-high {
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important;
    }
    
    .badge-display.priority-critical {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        animation: pulseGlow 2s ease-in-out infinite !important;
    }
    
    @keyframes pulseGlow {
        0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
        50% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
    }
    
    .user-info {
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(22, 163, 74, 0.05) 100%) !important;
        border: 2px solid rgba(34, 197, 94, 0.2) !important;
        border-radius: 20px !important;
        padding: 6px 14px !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        color: #1e293b !important;
    }
    
    .user-avatar {
        width: 20px !important;
        height: 20px !important;
        border-radius: 50% !important;
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
        color: white !important;
        font-size: 10px !important;
        font-weight: 700 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    
    .action-buttons {
        display: flex !important;
        gap: 15px !important;
        margin-top: 30px !important;
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
        justify-content: center !important;
        gap: 10px !important;
        text-decoration: none !important;
        flex: 1 !important;
        min-width: 150px !important;
    }
    
    .btn-modern.danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        color: white !important;
        box-shadow: 0 5px 20px rgba(239, 68, 68, 0.3) !important;
    }
    
    .btn-modern.danger:hover {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4) !important;
    }
    
    .btn-modern.secondary {
        background: white !important;
        color: #64748b !important;
        border: 2px solid #e2e8f0 !important;
    }
    
    .btn-modern.secondary:hover {
        background: #f8fafc !important;
        border-color: #cbd5e1 !important;
        transform: translateY(-2px) !important;
    }
    
    .breadcrumb-wrapper {
        text-align: center !important;
        margin-bottom: 20px !important;
    }
    
    .breadcrumb-link {
        color: #22c55e !important;
        text-decoration: none !important;
        font-weight: 600 !important;
        transition: color 0.3s ease !important;
        font-size: 14px !important;
    }
    
    .breadcrumb-link:hover {
        color: #16a34a !important;
    }
    
    .breadcrumb-link i {
        margin-right: 6px !important;
    }
    
    /* RESPONSIVE */
    @media (max-width: 768px) {
        .task-delete-container {
            padding: 15px !important;
        }
        
        .delete-modal-card {
            margin: 15px !important;
        }
        
        .delete-modal-header {
            padding: 25px !important;
        }
        
        .delete-modal-header h1 {
            font-size: 24px !important;
            flex-direction: column !important;
            text-align: center !important;
        }
        
        .delete-modal-body {
            padding: 30px 25px !important;
        }
        
        .task-details-box {
            padding: 20px !important;
        }
        
        .detail-row {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 8px !important;
        }
        
        .detail-label {
            min-width: auto !important;
        }
        
        .detail-value {
            text-align: left !important;
        }
        
        .action-buttons {
            flex-direction: column-reverse !important;
        }
        
        .btn-modern {
            width: 100% !important;
        }
    }
    
    @media (max-width: 480px) {
        .task-delete-container {
            padding: 10px !important;
        }
        
        .delete-modal-card {
            margin: 10px !important;
        }
        
        .delete-modal-header {
            padding: 20px !important;
        }
        
        .delete-modal-header h1 {
            font-size: 20px !important;
        }
        
        .delete-icon-wrapper {
            width: 40px !important;
            height: 40px !important;
            font-size: 20px !important;
        }
        
        .delete-modal-body {
            padding: 25px 20px !important;
        }
        
        .task-details-box {
            padding: 15px !important;
        }
        
        .btn-modern {
            padding: 12px 24px !important;
            font-size: 13px !important;
        }
    }
</style>

<div class="task-delete-container container-fluid">
    <div class="delete-modal-card">
        <!-- HEADER -->
        <div class="delete-modal-header">
            <h1>
                <div class="delete-icon-wrapper">
                    <i class="fa fa-trash"></i>
                </div>
                <span>Delete Task</span>
            </h1>
        </div>
        
        <!-- BODY -->
        <div class="delete-modal-body">
            <div class="breadcrumb-wrapper">
                <a href="project-detail.php?id=<?php echo $project_id; ?>&tab=tasks" class="breadcrumb-link">
                    <i class="fa fa-arrow-left"></i> Back to Project Tasks
                </a>
            </div>
            
            <!-- WARNING MESSAGE -->
            <div class="warning-message">
                <i class="fa fa-exclamation-triangle"></i>
                <strong>Warning: This action cannot be undone!</strong>
                <p>You are about to permanently delete this task. This will remove all task details, time logs, and progress information. This action cannot be recovered.</p>
            </div>
            
            <!-- TASK DETAILS -->
            <div class="task-details-box">
                <div class="task-details-title">
                    <i class="fa fa-info-circle"></i> Task Details
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">
                        <i class="fa fa-tasks"></i> Task Name
                    </span>
                    <span class="detail-value title-value"><?php echo htmlspecialchars($task['task_name']); ?></span>
                </div>
                
                <?php if ($task['description']): ?>
                <div class="detail-row" style="flex-direction: column; align-items: flex-start;">
                    <span class="detail-label">
                        <i class="fa fa-align-left"></i> Description
                    </span>
                    <span class="detail-value description-value"><?php echo nl2br(htmlspecialchars($task['description'])); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="detail-row">
                    <span class="detail-label">
                        <i class="fa fa-user"></i> Assigned To
                    </span>
                    <span class="detail-value">
                        <?php if ($assigned_user): ?>
                            <div class="user-info">
                                <div class="user-avatar">
                                    <?php 
                                    $initials = '';
                                    $name_parts = explode(' ', $assigned_user['full_name']);
                                    foreach ($name_parts as $part) {
                                        $initials .= strtoupper(substr($part, 0, 1));
                                    }
                                    echo substr($initials, 0, 2);
                                    ?>
                                </div>
                                <?php echo htmlspecialchars($assigned_user['full_name']); ?> (<?php echo ucfirst($assigned_user['role']); ?>)
                            </div>
                        <?php else: ?>
                            <span style="color: #94a3b8; font-style: italic;">Unassigned</span>
                        <?php endif; ?>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">
                        <i class="fa fa-circle"></i> Status
                    </span>
                    <span class="detail-value">
                        <span class="badge-display status-<?php echo $task['status']; ?>">
                            <i class="fa fa-<?php 
                                echo $task['status'] === 'todo' ? 'clock-o' : 
                                    ($task['status'] === 'in_progress' ? 'spinner' : 
                                    ($task['status'] === 'review' ? 'eye' : 'check-circle')); 
                            ?>"></i>
                            <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                        </span>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">
                        <i class="fa fa-exclamation-circle"></i> Priority
                    </span>
                    <span class="detail-value">
                        <span class="badge-display priority-<?php echo $task['priority']; ?>">
                            <i class="fa fa-flag"></i>
                            <?php echo ucfirst($task['priority']); ?>
                        </span>
                    </span>
                </div>
                
                <?php if ($task['due_date']): ?>
                <div class="detail-row">
                    <span class="detail-label">
                        <i class="fa fa-calendar"></i> Due Date
                    </span>
                    <span class="detail-value"><?php echo date('M d, Y', strtotime($task['due_date'])); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($task['estimated_hours']): ?>
                <div class="detail-row">
                    <span class="detail-label">
                        <i class="fa fa-clock-o"></i> Estimated Hours
                    </span>
                    <span class="detail-value"><?php echo $task['estimated_hours']; ?> <?php echo $task['estimated_hours'] == 1 ? 'hour' : 'hours'; ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (isset($task['created_at'])): ?>
                <div class="detail-row">
                    <span class="detail-label">
                        <i class="fa fa-calendar"></i> Created
                    </span>
                    <span class="detail-value"><?php echo date('M d, Y', strtotime($task['created_at'])); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- ACTION BUTTONS -->
            <form method="POST" action="" id="deleteForm">
                <div class="action-buttons">
                    <a href="project-detail.php?id=<?php echo $project_id; ?>&tab=tasks" class="btn-modern secondary">
                        <i class="fa fa-times"></i> Cancel
                    </a>
                    <button type="submit" name="confirm_delete" class="btn-modern danger" id="deleteBtn">
                        <i class="fa fa-trash"></i> Delete Task
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // CONFIRMATION ON DELETE
    $('#deleteForm').on('submit', function(e) {
        e.preventDefault();
        
        if (confirm('Are you absolutely sure you want to delete this task? This action is permanent and cannot be undone.')) {
            this.submit();
        }
    });
    
    // ADD ENTRANCE ANIMATION
    $('.delete-modal-card').css({
        'animation': 'scaleIn 0.5s cubic-bezier(0.4, 0, 0.2, 1) both'
    });
    
    // ESCAPE KEY TO CANCEL
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            window.location.href = 'project-detail.php?id=<?php echo $project_id; ?>&tab=tasks';
        }
    });
    
    // FOCUS DELETE BUTTON
    setTimeout(function() {
        $('#deleteBtn').focus();
    }, 600);
    
    // BUTTON HOVER EFFECTS
    $('.btn-modern').on('mouseenter', function() {
        $(this).find('i').css({
            'transform': 'scale(1.2) rotate(5deg)',
            'transition': 'transform 0.3s ease'
        });
    }).on('mouseleave', function() {
        $(this).find('i').css({
            'transform': 'scale(1) rotate(0deg)'
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>