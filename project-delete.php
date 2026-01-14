<?php
ob_start(); // Fix header warning

$page_title = 'Delete Project';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    if ($project_obj->delete($project_id)) {
        ob_end_clean(); // Clear buffer before redirect
        header('Location: projects.php?deleted=1');
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
    
    .delete-container {
        padding: 24px;
        max-width: 1400px;
        margin: 0 auto;
        animation: fadeIn 0.4s ease;
        min-height: calc(100vh - 100px);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* DELETE CARD */
    .delete-card {
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
    
    .delete-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--danger), #dc2626);
    }
    
    /* DELETE HEADER */
    .delete-header {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.05));
        padding: 40px 32px;
        text-align: center;
        position: relative;
        border-bottom: 2px solid var(--border);
    }
    
    .delete-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, var(--danger), #dc2626);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }
    
    .delete-icon i {
        font-size: 36px;
        color: white;
    }
    
    .delete-header h1 {
        margin: 0 0 8px;
        font-weight: 700;
        font-size: 32px;
        color: var(--dark);
    }
    
    .delete-header p {
        margin: 0;
        color: #64748b;
        font-size: 14px;
        font-weight: 600;
    }
    
    /* DELETE BODY */
    .delete-body {
        padding: 32px;
        background: white;
    }
    
    /* WARNING BOX */
    .warning-box {
        background: linear-gradient(135deg, rgba(251, 191, 36, 0.1), rgba(245, 158, 11, 0.05));
        border-left: 4px solid var(--warning);
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 24px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }
    
    .warning-box i {
        color: var(--warning);
        font-size: 18px;
        flex-shrink: 0;
        margin-top: 2px;
    }
    
    .warning-box p {
        margin: 0;
        color: #92400e;
        font-weight: 600;
        line-height: 1.5;
        font-size: 13px;
    }
    
    /* PROJECT DETAILS */
    .project-details {
        background: white;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        border: 2px solid var(--border);
        transition: all 0.3s ease;
    }
    
    .project-details:hover {
        border-color: #cbd5e1;
        box-shadow: var(--shadow-md);
    }
    
    .project-details h3 {
        margin: 0 0 20px;
        font-weight: 700;
        color: var(--dark);
        font-size: 15px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        display: flex;
        align-items: center;
        gap: 8px;
        padding-bottom: 12px;
        border-bottom: 2px solid var(--border);
    }
    
    .project-details h3 i {
        color: var(--primary);
        font-size: 16px;
    }
    
    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid var(--border);
        transition: all 0.3s ease;
    }
    
    .detail-row:last-child {
        border-bottom: none;
    }
    
    .detail-row:hover {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.03));
        padding-left: 12px;
        border-radius: 8px;
    }
    
    .detail-label {
        font-weight: 700;
        color: #64748b;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }
    
    .detail-value {
        font-weight: 600;
        color: var(--dark);
        text-align: right;
        font-size: 13px;
    }
    
    /* BADGES */
    .badge-status {
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .badge-planning { background: #fef3c7; color: #92400e; }
    .badge-in_progress { background: #dbeafe; color: #1e40af; }
    .badge-on_hold { background: #fee2e2; color: #991b1b; }
    .badge-completed { background: #d1fae5; color: #065f46; }
    .badge-cancelled { background: #e5e7eb; color: #374151; }
    
    /* ACTION BUTTONS */
    .action-buttons {
        display: flex;
        gap: 12px;
        margin-top: 24px;
        flex-wrap: wrap;
    }
    
    .btn {
        flex: 1;
        border-radius: 10px;
        padding: 14px 28px;
        font-weight: 700;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.8px;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        text-decoration: none;
    }
    
    .btn i {
        font-size: 14px;
    }
    
    .btn-danger {
        background: linear-gradient(135deg, var(--danger), #dc2626);
        color: white;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
    }
    
    .btn-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(239, 68, 68, 0.35);
    }
    
    .btn-danger:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.2);
    }
    
    .btn-danger:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
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
        .delete-container {
            padding: 20px;
        }
    }
    
    @media (max-width: 768px) {
        .delete-container {
            padding: 16px;
            align-items: flex-start;
        }
        
        .delete-card {
            margin-top: 20px;
        }
        
        .delete-header {
            padding: 32px 24px;
        }
        
        .delete-header h1 {
            font-size: 28px;
        }
        
        .delete-icon {
            width: 70px;
            height: 70px;
        }
        
        .delete-icon i {
            font-size: 32px;
        }
        
        .delete-body {
            padding: 24px;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
        }
    }
    
    @media (max-width: 480px) {
        .delete-container {
            padding: 12px;
        }
        
        .delete-header {
            padding: 28px 20px;
        }
        
        .delete-header h1 {
            font-size: 24px;
        }
        
        .delete-header p {
            font-size: 13px;
        }
        
        .delete-icon {
            width: 60px;
            height: 60px;
            margin-bottom: 16px;
        }
        
        .delete-icon i {
            font-size: 28px;
        }
        
        .delete-body {
            padding: 20px;
        }
        
        .project-details {
            padding: 20px;
        }
        
        .btn {
            padding: 12px 24px;
            font-size: 11px;
        }
    }
</style>

<div class="delete-container container-fluid">
    <div class="delete-card">
        <div class="delete-header">
            <div class="delete-icon">
                <i class="fa fa-exclamation-triangle"></i>
            </div>
            <h1>Delete Project</h1>
            <p>This action cannot be undone</p>
        </div>
        
        <div class="delete-body">
            <div class="warning-box">
                <i class="fa fa-warning"></i>
                <p>Warning: All associated tasks, requirements, and pricing will be permanently deleted.</p>
            </div>
            
            <div class="project-details">
                <h3><i class="fa fa-folder"></i> Project Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Project Name:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($project['project_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Project Code:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($project['project_code']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Client:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($project['client_name'] ?: 'N/A'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span class="badge-status badge-<?php echo $project['status']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?>
                        </span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Budget:</span>
                    <span class="detail-value">$<?php echo number_format($project['budget'], 2); ?></span>
                </div>
            </div>
            
            <form method="POST" action="" id="deleteForm">
                <div class="action-buttons">
                    <button type="submit" name="confirm_delete" class="btn btn-danger" id="deleteBtn">
                        <i class="fa fa-trash"></i> Yes, Delete Project
                    </button>
                    <a href="project-detail.php?id=<?php echo $project_id; ?>" class="btn btn-default">
                        <i class="fa fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    let confirmCount = 0;
    
    // Delete confirmation with countdown
    $('#deleteForm').on('submit', function(e) {
        if (confirmCount === 0) {
            e.preventDefault();
            confirmCount++;
            
            const $btn = $('#deleteBtn');
            const originalHtml = $btn.html();
            
            let countdown = 3;
            $btn.removeClass('btn-danger').addClass('btn-countdown');
            $btn.html(`<i class="fa fa-clock-o"></i> Click again in ${countdown}s`);
            $btn.prop('disabled', true);
            
            const timer = setInterval(() => {
                countdown--;
                if (countdown > 0) {
                    $btn.html(`<i class="fa fa-clock-o"></i> Click again in ${countdown}s`);
                } else {
                    clearInterval(timer);
                    $btn.removeClass('btn-countdown').addClass('btn-danger');
                    $btn.html('<i class="fa fa-trash"></i> Confirm Delete');
                    $btn.prop('disabled', false);
                }
            }, 1000);
            
            return false;
        }
        
        // Final confirmation
        return confirm('Are you absolutely sure? This will permanently delete the project and all related data.');
    });
    
    // Keyboard shortcut - ESC to cancel
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            window.location.href = 'project-detail.php?id=<?php echo $project_id; ?>';
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>