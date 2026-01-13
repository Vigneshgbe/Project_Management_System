<?php
$page_title = 'Delete Project';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    if ($project_obj->delete($project_id)) {
        header('Location: projects.php?deleted=1');
        exit;
    }
}
?>

<style>
    .delete-container {
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
    
    .delete-card {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        border-radius: 24px !important;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        max-width: 600px !important;
        width: 100% !important;
        overflow: hidden !important;
        animation: scaleIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
        position: relative !important;
    }
    
    @keyframes scaleIn {
        from { opacity: 0; transform: scale(0.8) translateY(30px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    
    .delete-card::before {
        content: '' !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        height: 8px !important;
        background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%) !important;
    }
    
    .delete-header {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%) !important;
        padding: 40px 35px 35px !important;
        text-align: center !important;
        position: relative !important;
    }
    
    .delete-icon {
        width: 80px !important;
        height: 80px !important;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin: 0 auto 20px !important;
        box-shadow: 0 10px 30px rgba(239, 68, 68, 0.4) !important;
        animation: pulse 2s infinite !important;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); box-shadow: 0 10px 30px rgba(239, 68, 68, 0.4); }
        50% { transform: scale(1.05); box-shadow: 0 15px 40px rgba(239, 68, 68, 0.6); }
    }
    
    .delete-icon i {
        font-size: 40px !important;
        color: white !important;
    }
    
    .delete-header h1 {
        margin: 0 0 10px !important;
        font-weight: 800 !important;
        font-size: 28px !important;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }
    
    .delete-header p {
        margin: 0 !important;
        color: #64748b !important;
        font-size: 15px !important;
        font-weight: 500 !important;
    }
    
    .delete-body {
        padding: 35px !important;
        background: white !important;
    }
    
    .warning-box {
        background: linear-gradient(135deg, rgba(251, 191, 36, 0.1) 0%, rgba(245, 158, 11, 0.1) 100%) !important;
        border-left: 5px solid #f59e0b !important;
        padding: 20px !important;
        border-radius: 12px !important;
        margin-bottom: 25px !important;
        animation: shake 0.5s ease 0.3s !important;
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
    
    .warning-box i {
        color: #f59e0b !important;
        font-size: 20px !important;
        margin-right: 10px !important;
    }
    
    .warning-box p {
        margin: 0 !important;
        color: #92400e !important;
        font-weight: 600 !important;
        display: inline !important;
    }
    
    .project-details {
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%) !important;
        border-radius: 16px !important;
        padding: 25px !important;
        margin-bottom: 25px !important;
        border: 2px solid #e2e8f0 !important;
        transition: all 0.3s ease !important;
    }
    
    .project-details:hover {
        border-color: #cbd5e1 !important;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1) !important;
    }
    
    .project-details h3 {
        margin: 0 0 15px !important;
        font-weight: 700 !important;
        color: #1e293b !important;
        font-size: 16px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }
    
    .detail-row {
        display: flex !important;
        justify-content: space-between !important;
        padding: 12px 0 !important;
        border-bottom: 1px solid #e2e8f0 !important;
        transition: all 0.3s ease !important;
    }
    
    .detail-row:last-child {
        border-bottom: none !important;
    }
    
    .detail-row:hover {
        background: rgba(102, 126, 234, 0.05) !important;
        padding-left: 10px !important;
        border-radius: 8px !important;
    }
    
    .detail-label {
        font-weight: 600 !important;
        color: #64748b !important;
        font-size: 13px !important;
    }
    
    .detail-value {
        font-weight: 600 !important;
        color: #1e293b !important;
        text-align: right !important;
        font-size: 13px !important;
    }
    
    .badge-status {
        padding: 6px 12px !important;
        border-radius: 12px !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        display: inline-block !important;
    }
    
    .badge-planning { 
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%) !important;
        color: white !important;
    }
    
    .badge-in_progress { 
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
        color: white !important;
    }
    
    .badge-on_hold { 
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        color: white !important;
    }
    
    .badge-completed { 
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        color: white !important;
    }
    
    .badge-cancelled { 
        background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%) !important;
        color: white !important;
    }
    
    .action-buttons {
        display: flex !important;
        gap: 15px !important;
        margin-top: 30px !important;
    }
    
    .btn {
        flex: 1 !important;
        border-radius: 12px !important;
        padding: 16px 28px !important;
        font-weight: 700 !important;
        transition: all 0.3s ease !important;
        text-transform: uppercase !important;
        font-size: 13px !important;
        letter-spacing: 0.5px !important;
        border: none !important;
        position: relative !important;
        overflow: hidden !important;
        cursor: pointer !important;
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
    
    .btn-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        color: white !important;
        box-shadow: 0 5px 20px rgba(239, 68, 68, 0.3) !important;
    }
    
    .btn-danger:hover {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
        transform: translateY(-3px) !important;
        box-shadow: 0 8px 25px rgba(239, 68, 68, 0.5) !important;
        animation: shake-btn 0.5s ease !important;
    }
    
    @keyframes shake-btn {
        0%, 100% { transform: translateY(-3px) translateX(0); }
        25% { transform: translateY(-3px) translateX(-3px); }
        75% { transform: translateY(-3px) translateX(3px); }
    }
    
    .btn-danger:active {
        transform: translateY(-1px) !important;
        box-shadow: 0 3px 15px rgba(239, 68, 68, 0.3) !important;
    }
    
    .btn-default {
        background: white !important;
        color: #64748b !important;
        border: 2px solid #cbd5e1 !important;
        box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1) !important;
    }
    
    .btn-default:hover {
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%) !important;
        color: #1e293b !important;
        border-color: #94a3b8 !important;
        transform: translateY(-3px) !important;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15) !important;
    }
    
    .btn-default:active {
        transform: translateY(-1px) !important;
    }
    
    /* RESPONSIVE */
    @media (max-width: 768px) {
        .delete-container {
            padding: 15px !important;
            align-items: flex-start !important;
        }
        
        .delete-card {
            margin-top: 20px !important;
        }
        
        .delete-header {
            padding: 30px 25px 25px !important;
        }
        
        .delete-header h1 {
            font-size: 24px !important;
        }
        
        .delete-icon {
            width: 70px !important;
            height: 70px !important;
        }
        
        .delete-icon i {
            font-size: 35px !important;
        }
        
        .delete-body {
            padding: 25px 20px !important;
        }
        
        .action-buttons {
            flex-direction: column !important;
        }
        
        .btn {
            width: 100% !important;
        }
    }
    
    @media (max-width: 480px) {
        .delete-container {
            padding: 10px !important;
        }
        
        .delete-header {
            padding: 25px 20px 20px !important;
        }
        
        .delete-header h1 {
            font-size: 22px !important;
        }
        
        .delete-header p {
            font-size: 14px !important;
        }
        
        .delete-icon {
            width: 60px !important;
            height: 60px !important;
            margin-bottom: 15px !important;
        }
        
        .delete-icon i {
            font-size: 30px !important;
        }
        
        .delete-body {
            padding: 20px 15px !important;
        }
        
        .project-details {
            padding: 20px !important;
        }
        
        .btn {
            padding: 14px 20px !important;
            font-size: 12px !important;
        }
    }
    
    /* Smooth Transitions */
    * {
        transition: background 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease !important;
    }
    
    /* Performance Optimization */
    .delete-card,
    .btn,
    .delete-icon {
        will-change: transform !important;
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
                    <button type="submit" name="confirm_delete" class="btn btn-danger">
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
        // Delete confirmation with countdown
        let confirmCount = 0;
        
        $('#deleteForm').on('submit', function(e) {
            if (confirmCount === 0) {
                e.preventDefault();
                confirmCount++;
                
                const $btn = $(this).find('.btn-danger');
                const originalText = $btn.html();
                
                let countdown = 3;
                $btn.html(`<i class="fa fa-clock-o"></i> Click again in ${countdown}s`);
                $btn.prop('disabled', true);
                
                const timer = setInterval(() => {
                    countdown--;
                    if (countdown > 0) {
                        $btn.html(`<i class="fa fa-clock-o"></i> Click again in ${countdown}s`);
                    } else {
                        clearInterval(timer);
                        $btn.html('<i class="fa fa-trash"></i> Confirm Delete');
                        $btn.prop('disabled', false);
                        $btn.css({
                            'animation': 'shake-btn 0.5s ease infinite'
                        });
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
        
        // Smooth scroll
        $('html').css('scroll-behavior', 'smooth');
        
        // Warning animation on page load
        setTimeout(() => {
            $('.warning-box').css({
                'animation': 'shake 0.5s ease'
            });
        }, 500);
    });
</script>

<?php require_once 'includes/footer.php'; ?>