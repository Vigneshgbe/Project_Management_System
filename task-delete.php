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
    
    .task-delete-container {
        padding: 24px;
        max-width: 1400px;
        margin: 0 auto;
        min-height: calc(100vh - 100px);
        display: flex;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.4s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* DELETE MODAL CARD */
    .delete-modal-card {
        background: white;
        border-radius: 16px;
        box-shadow: var(--shadow-lg);
        border: 1px solid var(--border);
        max-width: 700px;
        width: 100%;
        overflow: hidden;
        animation: fadeInUp 0.4s ease;
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .delete-modal-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--danger), #dc2626);
    }
    
    /* DELETE MODAL HEADER */
    .delete-modal-header {
        background: linear-gradient(135deg, var(--danger), #dc2626);
        color: white;
        padding: 32px;
        position: relative;
    }
    
    .delete-modal-header h1 {
        margin: 0;
        font-weight: 700;
        font-size: 28px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .delete-icon-wrapper {
        width: 48px;
        height: 48px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
    }
    
    /* DELETE MODAL BODY */
    .delete-modal-body {
        padding: 32px;
    }
    
    /* BREADCRUMB */
    .breadcrumb-wrapper {
        text-align: center;
        margin-bottom: 24px;
    }
    
    .breadcrumb-link {
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
        font-size: 14px;
    }
    
    .breadcrumb-link:hover {
        color: var(--primary-dark);
    }
    
    .breadcrumb-link i {
        margin-right: 6px;
    }
    
    /* WARNING MESSAGE */
    .warning-message {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.05));
        border-left: 4px solid var(--danger);
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 24px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }
    
    .warning-message i {
        color: var(--danger);
        font-size: 18px;
        flex-shrink: 0;
        margin-top: 2px;
    }
    
    .warning-message .warning-content strong {
        color: var(--danger);
        font-weight: 700;
        display: block;
        margin-bottom: 6px;
        font-size: 14px;
    }
    
    .warning-message .warning-content p {
        color: #64748b;
        margin: 0;
        font-weight: 500;
        line-height: 1.5;
        font-size: 13px;
    }
    
    /* TASK DETAILS BOX */
    .task-details-box {
        background: white;
        border: 2px solid var(--border);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
    }
    
    .task-details-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid var(--border);
        text-transform: uppercase;
        letter-spacing: 0.8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .task-details-title i {
        color: var(--primary);
        font-size: 16px;
    }
    
    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 12px 0;
        border-bottom: 1px solid var(--border);
    }
    
    .detail-row:last-child {
        border-bottom: none;
    }
    
    .detail-label {
        color: #64748b;
        font-weight: 600;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 150px;
    }
    
    .detail-label i {
        color: var(--primary);
        width: 16px;
        text-align: center;
        font-size: 14px;
    }
    
    .detail-value {
        color: var(--dark);
        font-weight: 600;
        font-size: 14px;
        text-align: right;
        flex: 1;
    }
    
    .detail-value.title-value {
        font-weight: 700;
        font-size: 15px;
        color: var(--primary);
    }
    
    .detail-value.description-value {
        text-align: left;
        margin-top: 8px;
        line-height: 1.6;
        color: #64748b;
        font-size: 13px;
    }
    
    /* BADGES */
    .badge-display {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .status-todo { background: #fef3c7; color: #92400e; }
    .status-in_progress { background: #dbeafe; color: #1e40af; }
    .status-review { background: #fed7aa; color: #9a3412; }
    .status-completed { background: #d1fae5; color: #065f46; }
    
    .priority-low { background: #d1fae5; color: #065f46; }
    .priority-medium { background: #fef3c7; color: #92400e; }
    .priority-high { background: #fed7aa; color: #9a3412; }
    .priority-critical { background: #fee2e2; color: #991b1b; }
    
    /* USER INFO */
    .user-info {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.03));
        border: 1px solid rgba(99, 102, 241, 0.15);
        border-radius: 8px;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 600;
        color: var(--dark);
    }
    
    .user-avatar {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-size: 10px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* ACTION BUTTONS */
    .action-buttons {
        display: flex;
        gap: 12px;
        margin-top: 24px;
        flex-wrap: wrap;
    }
    
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
        justify-content: center;
        gap: 8px;
        text-decoration: none;
        flex: 1;
        min-width: 150px;
    }
    
    .btn-modern.danger {
        background: linear-gradient(135deg, var(--danger), #dc2626);
        color: white;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
    }
    
    .btn-modern.danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(239, 68, 68, 0.35);
    }
    
    .btn-modern.danger:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.2);
    }
    
    .btn-modern.danger:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
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
    
    .btn-modern.secondary:active {
        transform: translateY(0);
    }
    
    /* COUNTDOWN STATE */
    .btn-countdown {
        background: linear-gradient(135deg, #94a3b8, #64748b);
        cursor: not-allowed;
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
        .task-delete-container {
            padding: 20px;
        }
    }
    
    @media (max-width: 768px) {
        .task-delete-container {
            padding: 16px;
            align-items: flex-start;
        }
        
        .delete-modal-card {
            margin-top: 20px;
        }
        
        .delete-modal-header {
            padding: 28px 24px;
        }
        
        .delete-modal-header h1 {
            font-size: 24px;
            flex-direction: column;
            text-align: center;
        }
        
        .delete-modal-body {
            padding: 24px;
        }
        
        .task-details-box {
            padding: 20px;
        }
        
        .detail-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        
        .detail-label {
            min-width: auto;
        }
        
        .detail-value {
            text-align: left;
        }
        
        .action-buttons {
            flex-direction: column-reverse;
        }
        
        .btn-modern {
            width: 100%;
        }
    }
    
    @media (max-width: 480px) {
        .task-delete-container {
            padding: 12px;
        }
        
        .delete-modal-header {
            padding: 24px 20px;
        }
        
        .delete-modal-header h1 {
            font-size: 20px;
        }
        
        .delete-icon-wrapper {
            width: 40px;
            height: 40px;
            font-size: 18px;
        }
        
        .delete-modal-body {
            padding: 20px;
        }
        
        .task-details-box {
            padding: 16px;
        }
        
        .btn-modern {
            padding: 12px 24px;
            font-size: 11px;
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
                <div class="warning-content">
                    <strong>Warning: This action cannot be undone!</strong>
                    <p>You are about to permanently delete this task. This will remove all task details, time logs, and progress information.</p>
                </div>
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
                                    $initials = strtoupper(substr($assigned_user['full_name'], 0, 1));
                                    $name_parts = explode(' ', $assigned_user['full_name']);
                                    if (count($name_parts) > 1) {
                                        $initials .= strtoupper(substr($name_parts[1], 0, 1));
                                    }
                                    echo $initials;
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
                        <i class="fa fa-calendar-plus-o"></i> Created
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
    let confirmCount = 0;
    
    // DELETE CONFIRMATION WITH COUNTDOWN
    $('#deleteForm').on('submit', function(e) {
        if (confirmCount === 0) {
            e.preventDefault();
            confirmCount++;
            
            const $btn = $('#deleteBtn');
            const originalHtml = $btn.html();
            
            let countdown = 3;
            $btn.removeClass('danger').addClass('btn-countdown');
            $btn.html(`<i class="fa fa-clock-o"></i> Click again in ${countdown}s`);
            $btn.prop('disabled', true);
            
            const timer = setInterval(() => {
                countdown--;
                if (countdown > 0) {
                    $btn.html(`<i class="fa fa-clock-o"></i> Click again in ${countdown}s`);
                } else {
                    clearInterval(timer);
                    $btn.removeClass('btn-countdown').addClass('danger');
                    $btn.html('<i class="fa fa-trash"></i> Confirm Deletion');
                    $btn.prop('disabled', false);
                }
            }, 1000);
            
            return false;
        }
        
        // Final confirmation
        return confirm('Are you absolutely sure you want to delete this task? This action is permanent and cannot be undone.');
    });
    
    // ESCAPE KEY TO CANCEL
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            window.location.href = 'project-detail.php?id=<?php echo $project_id; ?>&tab=tasks';
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>