<?php
ob_start(); // Fix header warning

$page_title = 'Remove Team Member';
require_once 'includes/header.php';
require_once 'components/project.php';

$auth->checkAccess('manager');

$project_id = $_GET['project_id'] ?? 0;
$user_id = $_GET['user_id'] ?? 0;

$project_obj = new Project();
$project = $project_obj->getById($project_id);

if (!$project || !$user_id) {
    ob_end_clean();
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
    ob_end_clean();
    header('Location: project-detail.php?id=' . $project_id . '&tab=team');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_remove'])) {
    if ($project_obj->removeMember($project_id, $user_id)) {
        ob_end_clean(); // Clear buffer before redirect
        header('Location: project-detail.php?id=' . $project_id . '&tab=team&removed=1');
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
    
    .remove-member-container {
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
    
    /* REMOVE CARD */
    .remove-card {
        background: white;
        border-radius: 16px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        overflow: hidden;
        animation: fadeInUp 0.4s ease;
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* REMOVE BODY */
    .remove-body {
        padding: 40px;
        background: white;
    }
    
    /* WARNING BOX */
    .warning-box {
        background: linear-gradient(135deg, rgba(251, 191, 36, 0.15), rgba(245, 158, 11, 0.08));
        border-left: 4px solid var(--warning);
        padding: 18px 22px;
        border-radius: 12px;
        margin-bottom: 32px;
        display: flex;
        align-items: flex-start;
        gap: 14px;
    }
    
    .warning-box i {
        color: var(--warning);
        font-size: 20px;
        flex-shrink: 0;
        margin-top: 2px;
    }
    
    .warning-box-content h4 {
        margin: 0 0 6px;
        font-weight: 700;
        color: #92400e;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .warning-box-content p {
        margin: 0;
        color: #92400e;
        font-weight: 600;
        line-height: 1.6;
        font-size: 13px;
    }
    
    /* PROJECT INFO */
    .project-info-box {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.05));
        border-left: 4px solid var(--primary);
        padding: 18px 22px;
        border-radius: 12px;
        margin-bottom: 32px;
        display: flex;
        align-items: center;
        gap: 14px;
    }
    
    .project-info-box i {
        color: var(--primary);
        font-size: 24px;
        flex-shrink: 0;
    }
    
    .project-info-content h3 {
        margin: 0 0 6px;
        font-weight: 700;
        color: #64748b;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }
    
    .project-info-content p {
        margin: 0;
        color: var(--dark);
        font-weight: 700;
        font-size: 17px;
    }
    
    /* MEMBER CARD */
    .member-removal-card {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.03));
        border-radius: 12px;
        padding: 40px 32px;
        margin-bottom: 32px;
        border: 2px solid rgba(99, 102, 241, 0.15);
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .member-removal-card:hover {
        border-color: rgba(99, 102, 241, 0.3);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
    }
    
    .member-avatar-large {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-weight: 800;
        font-size: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        box-shadow: 0 4px 16px rgba(99, 102, 241, 0.3);
    }
    
    .member-name {
        font-size: 24px;
        font-weight: 700;
        color: var(--dark);
        margin: 0 0 8px;
    }
    
    .member-email {
        color: #64748b;
        font-size: 15px;
        font-weight: 600;
        margin-bottom: 20px;
        display: block;
    }
    
    .member-info-badges {
        display: flex;
        justify-content: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    
    .info-badge {
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .info-badge.user-role {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .info-badge.project-role {
        background: #f3e8ff;
        color: #6b21a8;
    }
    
    .info-badge i {
        font-size: 12px;
    }
    
    /* SECTION TITLE */
    .section-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid var(--border);
        display: flex;
        align-items: center;
        gap: 10px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }
    
    .section-title i {
        color: var(--primary);
        font-size: 16px;
    }
    
    /* ACTION BUTTONS */
    .action-buttons {
        display: flex;
        gap: 12px;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 2px solid var(--border);
        flex-wrap: wrap;
    }
    
    .btn-modern {
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
    
    .btn-modern i {
        font-size: 14px;
    }
    
    .btn-modern.danger {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
    }
    
    .btn-modern.danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(99, 102, 241, 0.35);
    }
    
    .btn-modern.danger:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.2);
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
    .btn-modern.countdown {
        background: linear-gradient(135deg, #94a3b8, #64748b);
        cursor: not-allowed;
        box-shadow: none;
    }
    
    .btn-modern.countdown:hover {
        transform: none;
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
        .remove-member-container {
            padding: 20px;
        }
        .page-header {
            padding: 28px;
        }
        .page-header h1 {
            font-size: 28px;
        }
        .remove-body {
            padding: 32px;
        }
    }
    
    @media (max-width: 992px) {
        .remove-body {
            padding: 28px;
        }
    }
    
    @media (max-width: 768px) {
        .remove-member-container {
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
        
        .remove-body {
            padding: 24px;
        }
        
        .member-avatar-large {
            width: 85px;
            height: 85px;
            font-size: 36px;
        }
        
        .member-name {
            font-size: 22px;
        }
        
        .member-removal-card {
            padding: 32px 24px;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .btn-modern {
            width: 100%;
        }
    }
    
    @media (max-width: 480px) {
        .remove-member-container {
            padding: 12px;
        }
        
        .page-header {
            padding: 20px;
        }
        
        .page-header h1 {
            font-size: 20px;
        }
        
        .remove-body {
            padding: 20px;
        }
        
        .member-avatar-large {
            width: 75px;
            height: 75px;
            font-size: 32px;
        }
        
        .member-name {
            font-size: 20px;
        }
        
        .member-email {
            font-size: 14px;
        }
        
        .member-removal-card {
            padding: 28px 20px;
        }
        
        .btn-modern {
            padding: 12px 24px;
            font-size: 11px;
        }
        
        .warning-box,
        .project-info-box {
            padding: 16px 18px;
        }
    }
</style>

<div class="remove-member-container">
    <div class="page-header">
        <h1>
            <i class="fa fa-user-times"></i> Remove Team Member
        </h1>
        <div class="page-breadcrumb">
            <a href="project-detail.php?id=<?php echo $project_id; ?>">
                <i class="fa fa-folder"></i> <?php echo htmlspecialchars($project['project_name']); ?>
            </a>
            <span>/</span>
            <a href="project-detail.php?id=<?php echo $project_id; ?>&tab=team">
                <i class="fa fa-users"></i> Team
            </a>
            <span>/</span>
            <span class="current">Remove Member</span>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="remove-card">
                <div class="remove-body">
                    <div class="warning-box">
                        <i class="fa fa-exclamation-triangle"></i>
                        <div class="warning-box-content">
                            <h4>Warning: This action cannot be undone</h4>
                            <p>This member will immediately lose access to all project data, tasks, and resources. Any work assigned to them will need to be reassigned.</p>
                        </div>
                    </div>
                    
                    <div class="project-info-box">
                        <i class="fa fa-folder"></i>
                        <div class="project-info-content">
                            <h3>Removing From Project</h3>
                            <p><?php echo htmlspecialchars($project['project_name']); ?></p>
                        </div>
                    </div>
                    
                    <div class="section-title">
                        <i class="fa fa-user"></i> Member to Remove
                    </div>
                    
                    <div class="member-removal-card">
                        <div class="member-avatar-large">
                            <?php echo strtoupper(substr($member['full_name'], 0, 1)); ?>
                        </div>
                        <h2 class="member-name"><?php echo htmlspecialchars($member['full_name']); ?></h2>
                        <span class="member-email"><?php echo htmlspecialchars($member['email']); ?></span>
                        <div class="member-info-badges">
                            <span class="info-badge user-role">
                                <i class="fa fa-briefcase"></i> <?php echo ucfirst($member['user_role']); ?>
                            </span>
                            <span class="info-badge project-role">
                                <i class="fa fa-tag"></i> <?php echo ucfirst($member['role']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <form method="POST" action="" id="removeForm">
                        <div class="action-buttons">
                            <button type="submit" name="confirm_remove" class="btn-modern danger" id="removeBtn">
                                <i class="fa fa-user-times"></i> Remove Member
                            </button>
                            <a href="project-detail.php?id=<?php echo $project_id; ?>&tab=team" class="btn-modern secondary">
                                <i class="fa fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    let confirmCount = 0;
    
    // REMOVE CONFIRMATION WITH COUNTDOWN
    $('#removeForm').on('submit', function(e) {
        if (confirmCount === 0) {
            e.preventDefault();
            confirmCount++;
            
            const $btn = $('#removeBtn');
            const originalHtml = $btn.html();
            
            let countdown = 3;
            $btn.removeClass('danger').addClass('countdown');
            $btn.html(`<i class="fa fa-clock-o"></i> Click again in ${countdown}s`);
            $btn.prop('disabled', true);
            
            const timer = setInterval(() => {
                countdown--;
                if (countdown > 0) {
                    $btn.html(`<i class="fa fa-clock-o"></i> Click again in ${countdown}s`);
                } else {
                    clearInterval(timer);
                    $btn.removeClass('countdown').addClass('danger');
                    $btn.html('<i class="fa fa-user-times"></i> Confirm Removal');
                    $btn.prop('disabled', false);
                }
            }, 1000);
            
            return false;
        }
        
        // FINAL CONFIRMATION
        return confirm('Are you absolutely sure you want to remove <?php echo htmlspecialchars($member['full_name']); ?> from this project?\n\nThis action cannot be undone.');
    });
    
    // KEYBOARD SHORTCUT - ESC TO CANCEL
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            window.location.href = 'project-detail.php?id=<?php echo $project_id; ?>&tab=team';
        }
    });
    
    // ANIMATE ELEMENTS ON LOAD
    $('.member-removal-card').css({
        'animation': 'fadeInUp 0.5s ease 0.1s both'
    });
    
    $('.warning-box').css({
        'animation': 'fadeInUp 0.4s ease both'
    });
    
    $('.project-info-box').css({
        'animation': 'fadeInUp 0.4s ease 0.05s both'
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>