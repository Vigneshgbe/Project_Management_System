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
    
    .remove-container {
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
    
    /* REMOVE CARD */
    .remove-card {
        background: white;
        border-radius: 16px;
        box-shadow: var(--shadow-lg);
        border: 1px solid var(--border);
        max-width: 600px;
        width: 100%;
        overflow: hidden;
        animation: fadeInUp 0.4s ease;
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .remove-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
    }
    
    /* REMOVE HEADER */
    .remove-header {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.05));
        padding: 40px 32px;
        text-align: center;
        position: relative;
        border-bottom: 2px solid var(--border);
    }
    
    .remove-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }
    
    .remove-icon i {
        font-size: 36px;
        color: white;
    }
    
    .remove-header h1 {
        margin: 0 0 8px;
        font-weight: 700;
        font-size: 32px;
        color: var(--dark);
    }
    
    .remove-header p {
        margin: 0;
        color: #64748b;
        font-size: 14px;
        font-weight: 600;
    }
    
    /* REMOVE BODY */
    .remove-body {
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
    
    /* PROJECT INFO */
    .project-info {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.03));
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 24px;
        border: 1px solid rgba(99, 102, 241, 0.15);
    }
    
    .project-info h3 {
        margin: 0 0 8px;
        font-weight: 700;
        color: var(--dark);
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        display: flex;
        align-items: center;
        gap: 6px;
        color: #64748b;
    }
    
    .project-info h3 i {
        color: var(--primary);
        font-size: 12px;
    }
    
    .project-info p {
        margin: 0;
        color: var(--dark);
        font-weight: 600;
        font-size: 15px;
    }
    
    /* MEMBER CARD */
    .member-card {
        background: white;
        border-radius: 12px;
        padding: 28px;
        margin-bottom: 24px;
        border: 2px solid var(--border);
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .member-card:hover {
        border-color: #cbd5e1;
        box-shadow: var(--shadow-md);
    }
    
    .member-avatar-large {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-weight: 800;
        font-size: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }
    
    .member-name {
        font-size: 22px;
        font-weight: 700;
        color: var(--dark);
        margin: 0 0 6px;
    }
    
    .member-email {
        color: #64748b;
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 16px;
    }
    
    .member-info {
        display: flex;
        justify-content: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .info-badge {
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
    
    .info-badge:first-child {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .info-badge:last-child {
        background: #f3e8ff;
        color: #6b21a8;
    }
    
    .info-badge i {
        font-size: 11px;
    }
    
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
    
    .btn-warning {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
    }
    
    .btn-warning:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(99, 102, 241, 0.35);
    }
    
    .btn-warning:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.2);
    }
    
    .btn-warning:disabled {
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
        .remove-container {
            padding: 20px;
        }
    }
    
    @media (max-width: 768px) {
        .remove-container {
            padding: 16px;
            align-items: flex-start;
        }
        
        .remove-card {
            margin-top: 20px;
        }
        
        .remove-header {
            padding: 32px 24px;
        }
        
        .remove-header h1 {
            font-size: 28px;
        }
        
        .remove-icon {
            width: 70px;
            height: 70px;
        }
        
        .remove-icon i {
            font-size: 32px;
        }
        
        .remove-body {
            padding: 24px;
        }
        
        .member-avatar-large {
            width: 80px;
            height: 80px;
            font-size: 34px;
        }
        
        .member-card {
            padding: 24px 20px;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
        }
    }
    
    @media (max-width: 480px) {
        .remove-container {
            padding: 12px;
        }
        
        .remove-header {
            padding: 28px 20px;
        }
        
        .remove-header h1 {
            font-size: 24px;
        }
        
        .remove-header p {
            font-size: 13px;
        }
        
        .remove-icon {
            width: 60px;
            height: 60px;
            margin-bottom: 16px;
        }
        
        .remove-icon i {
            font-size: 28px;
        }
        
        .remove-body {
            padding: 20px;
        }
        
        .member-avatar-large {
            width: 70px;
            height: 70px;
            font-size: 30px;
        }
        
        .member-name {
            font-size: 20px;
        }
        
        .member-card {
            padding: 20px 16px;
        }
        
        .btn {
            padding: 12px 24px;
            font-size: 11px;
        }
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
                    <button type="submit" name="confirm_remove" class="btn btn-warning" id="removeBtn">
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
    let confirmCount = 0;
    
    // Remove confirmation with countdown
    $('#removeForm').on('submit', function(e) {
        if (confirmCount === 0) {
            e.preventDefault();
            confirmCount++;
            
            const $btn = $('#removeBtn');
            const originalHtml = $btn.html();
            
            let countdown = 3;
            $btn.removeClass('btn-warning').addClass('btn-countdown');
            $btn.html(`<i class="fa fa-clock-o"></i> Click again in ${countdown}s`);
            $btn.prop('disabled', true);
            
            const timer = setInterval(() => {
                countdown--;
                if (countdown > 0) {
                    $btn.html(`<i class="fa fa-clock-o"></i> Click again in ${countdown}s`);
                } else {
                    clearInterval(timer);
                    $btn.removeClass('btn-countdown').addClass('btn-warning');
                    $btn.html('<i class="fa fa-user-times"></i> Confirm Removal');
                    $btn.prop('disabled', false);
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
});
</script>

<?php require_once 'includes/footer.php'; ?>