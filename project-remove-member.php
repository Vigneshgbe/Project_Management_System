<?php
$page_title = 'Remove Team Member';
require_once 'includes/header.php';
require_once 'components/project.php';

$auth->checkAccess('manager');

$project_id = $_GET['project_id'] ?? 0;
$user_id = $_GET['user_id'] ?? 0;

$project_obj = new Project();
$project = $project_obj->getById($project_id);

if (!$project || !$user_id) {
    header('Location: projects.php');
    exit;
}

// Get member details
$members = $project_obj->getMembers($project_id);
$member = null;
foreach ($members as $m) {
    if ($m['user_id'] == $user_id) {
        $member = $m;
        break;
    }
}

if (!$member) {
    header('Location: project-detail.php?id=' . $project_id . '&tab=team');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_remove'])) {
    if ($project_obj->removeMember($project_id, $user_id)) {
        header('Location: project-detail.php?id=' . $project_id . '&tab=team&removed=1');
        exit;
    }
}
?>

<style>
    .remove-container {
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
    
    .remove-card {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        border-radius: 24px !important;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        max-width: 550px !important;
        width: 100% !important;
        overflow: hidden !important;
        animation: scaleIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
        position: relative !important;
    }
    
    @keyframes scaleIn {
        from { opacity: 0; transform: scale(0.8) translateY(30px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    
    .remove-card::before {
        content: '' !important;
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        height: 8px !important;
        background: linear-gradient(90deg, #f97316 0%, #ea580c 100%) !important;
    }
    
    .remove-header {
        background: linear-gradient(135deg, rgba(249, 115, 22, 0.1) 0%, rgba(234, 88, 12, 0.1) 100%) !important;
        padding: 40px 35px 35px !important;
        text-align: center !important;
        position: relative !important;
    }
    
    .remove-icon {
        width: 80px !important;
        height: 80px !important;
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin: 0 auto 20px !important;
        box-shadow: 0 10px 30px rgba(249, 115, 22, 0.4) !important;
        animation: pulse 2s infinite !important;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); box-shadow: 0 10px 30px rgba(249, 115, 22, 0.4); }
        50% { transform: scale(1.05); box-shadow: 0 15px 40px rgba(249, 115, 22, 0.6); }
    }
    
    .remove-icon i {
        font-size: 40px !important;
        color: white !important;
    }
    
    .remove-header h1 {
        margin: 0 0 10px !important;
        font-weight: 800 !important;
        font-size: 28px !important;
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }
    
    .remove-header p {
        margin: 0 !important;
        color: #64748b !important;
        font-size: 15px !important;
        font-weight: 500 !important;
    }
    
    .remove-body {
        padding: 35px !important;
        background: white !important;
    }
    
    .warning-box {
        background: linear-gradient(135deg, rgba(251, 191, 36, 0.1) 0%, rgba(245, 158, 11, 0.1) 100%) !important;
        border-left: 5px solid #f59e0b !important;
        padding: 18px !important;
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
        font-size: 18px !important;
        margin-right: 10px !important;
    }
    
    .warning-box p {
        margin: 0 !important;
        color: #92400e !important;
        font-weight: 600 !important;
        display: inline !important;
        font-size: 14px !important;
    }
    
    .member-card {
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%) !important;
        border-radius: 16px !important;
        padding: 30px !important;
        margin-bottom: 25px !important;
        border: 2px solid #e2e8f0 !important;
        transition: all 0.3s ease !important;
        text-align: center !important;
    }
    
    .member-card:hover {
        border-color: #cbd5e1 !important;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1) !important;
        transform: translateY(-3px) !important;
    }
    
    .member-avatar-large {
        width: 100px !important;
        height: 100px !important;
        border-radius: 50% !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        font-weight: 800 !important;
        font-size: 42px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin: 0 auto 20px !important;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3) !important;
        transition: all 0.3s ease !important;
    }
    
    .member-card:hover .member-avatar-large {
        transform: scale(1.1) rotate(5deg) !important;
        box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4) !important;
    }
    
    .member-name {
        font-size: 22px !important;
        font-weight: 800 !important;
        color: #1e293b !important;
        margin: 0 0 8px !important;
    }
    
    .member-email {
        color: #64748b !important;
        font-size: 14px !important;
        font-weight: 500 !important;
        margin-bottom: 15px !important;
    }
    
    .member-info {
        display: flex !important;
        justify-content: center !important;
        gap: 15px !important;
        flex-wrap: wrap !important;
    }
    
    .info-badge {
        padding: 8px 16px !important;
        border-radius: 20px !important;
        font-size: 12px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        box-shadow: 0 3px 12px rgba(102, 126, 234, 0.3) !important;
    }
    
    .info-badge i {
        font-size: 13px !important;
    }
    
    .project-info {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%) !important;
        border-radius: 12px !important;
        padding: 20px !important;
        margin-bottom: 25px !important;
        border: 2px solid rgba(102, 126, 234, 0.2) !important;
    }
    
    .project-info h3 {
        margin: 0 0 10px !important;
        font-weight: 700 !important;
        color: #1e293b !important;
        font-size: 14px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }
    
    .project-info p {
        margin: 0 !important;
        color: #64748b !important;
        font-weight: 600 !important;
        font-size: 16px !important;
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
    
    .btn-warning {
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important;
        color: white !important;
        box-shadow: 0 5px 20px rgba(249, 115, 22, 0.3) !important;
    }
    
    .btn-warning:hover {
        background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%) !important;
        transform: translateY(-3px) !important;
        box-shadow: 0 8px 25px rgba(249, 115, 22, 0.5) !important;
        animation: shake-btn 0.5s ease !important;
    }
    
    @keyframes shake-btn {
        0%, 100% { transform: translateY(-3px) translateX(0); }
        25% { transform: translateY(-3px) translateX(-3px); }
        75% { transform: translateY(-3px) translateX(3px); }
    }
    
    .btn-warning:active {
        transform: translateY(-1px) !important;
        box-shadow: 0 3px 15px rgba(249, 115, 22, 0.3) !important;
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
        .remove-container {
            padding: 15px !important;
            align-items: flex-start !important;
        }
        
        .remove-card {
            margin-top: 20px !important;
        }
        
        .remove-header {
            padding: 30px 25px 25px !important;
        }
        
        .remove-header h1 {
            font-size: 24px !important;
        }
        
        .remove-icon {
            width: 70px !important;
            height: 70px !important;
        }
        
        .remove-icon i {
            font-size: 35px !important;
        }
        
        .remove-body {
            padding: 25px 20px !important;
        }
        
        .member-avatar-large {
            width: 80px !important;
            height: 80px !important;
            font-size: 36px !important;
        }
        
        .member-card {
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
        .remove-container {
            padding: 10px !important;
        }
        
        .remove-header {
            padding: 25px 20px 20px !important;
        }
        
        .remove-header h1 {
            font-size: 22px !important;
        }
        
        .remove-header p {
            font-size: 14px !important;
        }
        
        .remove-icon {
            width: 60px !important;
            height: 60px !important;
            margin-bottom: 15px !important;
        }
        
        .remove-icon i {
            font-size: 30px !important;
        }
        
        .remove-body {
            padding: 20px 15px !important;
        }
        
        .member-avatar-large {
            width: 70px !important;
            height: 70px !important;
            font-size: 32px !important;
        }
        
        .member-name {
            font-size: 20px !important;
        }
        
        .member-card {
            padding: 20px 15px !important;
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
    .remove-card,
    .btn,
    .remove-icon,
    .member-avatar-large {
        will-change: transform !important;
    }
</style>

<div class="remove-container container-fluid">
    <div class="remove-card">
        <div class="remove-header">
            <div class="remove-icon">
                <i class="fa fa-user-times"></i>
            </div>
            <h1>Remove Team Member</h1>
            <p>Remove member from project team</p>
        </div>
        
        <div class="remove-body">
            <div class="warning-box">
                <i class="fa fa-info-circle"></i>
                <p>This member will lose access to all project data and tasks.</p>
            </div>
            
            <div class="project-info">
                <h3><i class="fa fa-folder"></i> From Project</h3>
                <p><?php echo htmlspecialchars($project['project_name']); ?></p>
            </div>
            
            <div class="member-card">
                <div class="member-avatar-large">
                    <?php echo strtoupper(substr($member['full_name'], 0, 1)); ?>
                </div>
                <h2 class="member-name"><?php echo htmlspecialchars($member['full_name']); ?></h2>
                <p class="member-email"><?php echo htmlspecialchars($member['email']); ?></p>
                <div class="member-info">
                    <span class="info-badge">
                        <i class="fa fa-briefcase"></i> <?php echo ucfirst($member['user_role']); ?>
                    </span>
                    <span class="info-badge">
                        <i class="fa fa-tag"></i> <?php echo ucfirst($member['role']); ?>
                    </span>
                </div>
            </div>
            
            <form method="POST" action="" id="removeForm">
                <div class="action-buttons">
                    <button type="submit" name="confirm_remove" class="btn btn-warning">
                        <i class="fa fa-user-times"></i> Remove Member
                    </button>
                    <a href="project-detail.php?id=<?php echo $project_id; ?>&tab=team" class="btn btn-default">
                        <i class="fa fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Remove confirmation with countdown
        let confirmCount = 0;
        
        $('#removeForm').on('submit', function(e) {
            if (confirmCount === 0) {
                e.preventDefault();
                confirmCount++;
                
                const $btn = $(this).find('.btn-warning');
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
                        $btn.html('<i class="fa fa-user-times"></i> Confirm Removal');
                        $btn.prop('disabled', false);
                        $btn.css({
                            'animation': 'shake-btn 0.5s ease infinite'
                        });
                    }
                }, 1000);
                
                return false;
            }
            
            // Final confirmation
            return confirm('Are you sure you want to remove <?php echo htmlspecialchars($member['full_name']); ?> from this project?');
        });
        
        // Keyboard shortcut - ESC to cancel
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                window.location.href = 'project-detail.php?id=<?php echo $project_id; ?>&tab=team';
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
        
        // Member card entrance animation
        setTimeout(() => {
            $('.member-card').css({
                'animation': 'scaleIn 0.5s ease'
            });
        }, 200);
    });
</script>

<?php require_once 'includes/footer.php'; ?>