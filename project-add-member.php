<?php
$page_title = 'Add Team Member';
require_once 'includes/header.php';
require_once 'components/project.php';
require_once 'components/user.php';

$auth->checkAccess('manager');

$project_id = $_GET['project_id'] ?? 0;

$project_obj = new Project();
$project = $project_obj->getById($project_id);

if (!$project) {
    header('Location: projects.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $role = $_POST['role'];
    
    if ($project_obj->addMember($project_id, $user_id, $role)) {
        header('Location: project-detail.php?id=' . $project_id . '&tab=team&added=1');
        exit;
    }
}

$user = new User();
$users = $user->getActiveUsers();

$existing_members = $project_obj->getMembers($project_id);
$existing_member_ids = array_column($existing_members, 'user_id');
?>

<style>
    .add-member-container {
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
    
    .page-header {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        color: #1e293b !important;
        padding: 35px 40px !important;
        border-radius: 20px !important;
        margin-bottom: 30px !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        animation: slideDown 0.6s ease !important;
        position: relative !important;
        overflow: hidden !important;
    }
    
    .page-header::before {
        content: '' !important;
        position: absolute !important;
        top: -50% !important;
        right: -50% !important;
        width: 200% !important;
        height: 200% !important;
        background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%) !important;
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
    
    .page-header h1 {
        margin: 0 !important;
        font-weight: 800 !important;
        font-size: 32px !important;
        position: relative !important;
        z-index: 1 !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }
    
    .page-header h1 i {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }
    
    .panel {
        border: none !important;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15) !important;
        border-radius: 20px !important;
        overflow: hidden !important;
        transition: all 0.3s ease !important;
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        margin-bottom: 25px !important;
        animation: scaleIn 0.5s ease !important;
    }
    
    @keyframes scaleIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
    
    .panel:hover {
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2) !important;
        transform: translateY(-3px) !important;
    }
    
    .panel-body {
        padding: 40px 35px !important;
        background: white !important;
    }
    
    .project-info-box {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%) !important;
        border-left: 5px solid #667eea !important;
        padding: 20px !important;
        border-radius: 12px !important;
        margin-bottom: 30px !important;
        animation: slideInLeft 0.5s ease !important;
    }
    
    @keyframes slideInLeft {
        from { opacity: 0; transform: translateX(-30px); }
        to { opacity: 1; transform: translateX(0); }
    }
    
    .project-info-box h3 {
        margin: 0 0 8px !important;
        font-weight: 700 !important;
        color: #1e293b !important;
        font-size: 14px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }
    
    .project-info-box p {
        margin: 0 !important;
        color: #667eea !important;
        font-weight: 700 !important;
        font-size: 18px !important;
    }
    
    .form-group {
        margin-bottom: 28px !important;
        animation: fadeInUp 0.4s ease !important;
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .form-group:nth-child(1) { animation-delay: 0.1s !important; }
    .form-group:nth-child(2) { animation-delay: 0.2s !important; }
    
    .form-group label {
        font-weight: 700 !important;
        color: #1e293b !important;
        margin-bottom: 10px !important;
        font-size: 14px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        display: block !important;
    }
    
    .form-control {
        border: 2px solid #e2e8f0 !important;
        border-radius: 12px !important;
        padding: 14px 18px !important;
        font-size: 15px !important;
        transition: all 0.3s ease !important;
        background: white !important;
        color: #1e293b !important;
        font-weight: 500 !important;
    }
    
    .form-control:focus {
        border-color: #667eea !important;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1) !important;
        outline: none !important;
        background: white !important;
        transform: translateY(-2px) !important;
    }
    
    .form-control:hover:not(:focus) {
        border-color: #cbd5e1 !important;
        background: #f8fafc !important;
    }
    
    select.form-control {
        cursor: pointer !important;
        appearance: none !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23667eea' d='M6 9L1 4h10z'/%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important;
        background-position: right 15px center !important;
        padding-right: 45px !important;
    }
    
    select.form-control:focus {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23667eea' d='M6 9L1 4h10z'/%3E%3C/svg%3E") !important;
    }
    
    .text-danger {
        color: #ef4444 !important;
        font-weight: 700 !important;
    }
    
    .btn {
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
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3) !important;
    }
    
    .btn-primary:hover,
    .btn-primary:focus {
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%) !important;
        transform: translateY(-3px) !important;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4) !important;
        color: white !important;
    }
    
    .btn-primary:active {
        transform: translateY(-1px) !important;
        box-shadow: 0 3px 15px rgba(102, 126, 234, 0.3) !important;
    }
    
    .btn-default {
        background: white !important;
        color: #667eea !important;
        border: 2px solid #667eea !important;
        box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1) !important;
    }
    
    .btn-default:hover,
    .btn-default:focus {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        border-color: transparent !important;
        transform: translateY(-3px) !important;
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3) !important;
    }
    
    .btn-default:active {
        transform: translateY(-1px) !important;
    }
    
    .btn-lg {
        padding: 16px 32px !important;
        font-size: 14px !important;
    }
    
    hr {
        border: none !important;
        height: 2px !important;
        background: linear-gradient(90deg, transparent 0%, #e2e8f0 50%, transparent 100%) !important;
        margin: 35px 0 !important;
    }
    
    /* User Preview Card */
    .user-preview {
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%) !important;
        border-radius: 16px !important;
        padding: 25px !important;
        margin-top: 20px !important;
        border: 2px solid #e2e8f0 !important;
        display: none !important;
        animation: scaleIn 0.4s ease !important;
    }
    
    .user-preview.active {
        display: block !important;
    }
    
    .user-preview-header {
        display: flex !important;
        align-items: center !important;
        gap: 15px !important;
        margin-bottom: 15px !important;
    }
    
    .preview-avatar {
        width: 60px !important;
        height: 60px !important;
        border-radius: 50% !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        font-weight: 800 !important;
        font-size: 24px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3) !important;
    }
    
    .preview-info h4 {
        margin: 0 0 5px !important;
        font-weight: 700 !important;
        color: #1e293b !important;
        font-size: 18px !important;
    }
    
    .preview-info p {
        margin: 0 !important;
        color: #64748b !important;
        font-size: 13px !important;
        font-weight: 500 !important;
    }
    
    .role-badge {
        padding: 6px 12px !important;
        border-radius: 12px !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        display: inline-block !important;
    }
    
    /* Info Box */
    .info-box {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(37, 99, 235, 0.1) 100%) !important;
        border-left: 5px solid #3b82f6 !important;
        padding: 18px !important;
        border-radius: 12px !important;
        margin-top: 25px !important;
    }
    
    .info-box i {
        color: #3b82f6 !important;
        font-size: 18px !important;
        margin-right: 10px !important;
    }
    
    .info-box p {
        margin: 0 !important;
        color: #1e40af !important;
        font-weight: 600 !important;
        display: inline !important;
        font-size: 14px !important;
    }
    
    /* Existing Members Preview */
    .existing-members {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        border-radius: 20px !important;
        padding: 30px !important;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        animation: scaleIn 0.5s ease 0.2s both !important;
    }
    
    .existing-members h3 {
        margin: 0 0 20px !important;
        font-weight: 800 !important;
        font-size: 18px !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }
    
    .member-item {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        padding: 12px !important;
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%) !important;
        border-radius: 12px !important;
        margin-bottom: 10px !important;
        transition: all 0.3s ease !important;
    }
    
    .member-item:hover {
        transform: translateX(5px) !important;
        box-shadow: 0 3px 15px rgba(102, 126, 234, 0.15) !important;
    }
    
    .member-avatar-small {
        width: 40px !important;
        height: 40px !important;
        border-radius: 50% !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        font-weight: 700 !important;
        font-size: 16px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        box-shadow: 0 3px 12px rgba(102, 126, 234, 0.3) !important;
    }
    
    .member-details {
        flex: 1 !important;
    }
    
    .member-details strong {
        display: block !important;
        color: #1e293b !important;
        font-size: 14px !important;
        margin-bottom: 2px !important;
    }
    
    .member-details small {
        color: #64748b !important;
        font-size: 12px !important;
    }
    
    /* RESPONSIVE */
    @media (max-width: 1200px) {
        .add-member-container {
            padding: 15px !important;
        }
        .page-header {
            padding: 25px 30px !important;
        }
        .page-header h1 {
            font-size: 28px !important;
        }
    }
    
    @media (max-width: 992px) {
        .panel-body {
            padding: 30px 25px !important;
        }
    }
    
    @media (max-width: 768px) {
        .add-member-container {
            padding: 10px !important;
        }
        .page-header {
            padding: 20px !important;
            margin-bottom: 20px !important;
        }
        .page-header h1 {
            font-size: 24px !important;
        }
        .panel-body {
            padding: 25px 20px !important;
        }
        .form-control {
            padding: 12px 16px !important;
            font-size: 14px !important;
        }
        .btn-lg {
            padding: 14px 28px !important;
            width: 100% !important;
            margin-bottom: 10px !important;
        }
        .existing-members {
            padding: 20px !important;
        }
    }
    
    @media (max-width: 480px) {
        .add-member-container {
            padding: 8px !important;
        }
        .page-header h1 {
            font-size: 20px !important;
        }
        .form-group {
            margin-bottom: 20px !important;
        }
        .form-control {
            padding: 10px 14px !important;
            font-size: 13px !important;
        }
        .existing-members {
            padding: 15px !important;
        }
    }
    
    /* Input Focus States */
    input:focus,
    select:focus {
        animation: focusPulse 0.5s ease !important;
    }
    
    @keyframes focusPulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.02); }
        100% { transform: scale(1); }
    }
    
    /* Smooth Transitions */
    * {
        transition: background 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease !important;
    }
    
    /* Performance Optimization */
    .panel,
    .form-control,
    .btn,
    .preview-avatar,
    .member-avatar-small {
        will-change: transform !important;
    }
</style>

<div class="add-member-container container-fluid">
    <div class="page-header">
        <h1><i class="fa fa-user-plus"></i> Add Team Member</h1>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-body">
                    <div class="project-info-box">
                        <h3><i class="fa fa-folder"></i> Adding to Project</h3>
                        <p><?php echo htmlspecialchars($project['project_name']); ?></p>
                    </div>
                    
                    <form method="POST" action="" id="addMemberForm">
                        <div class="form-group">
                            <label for="user_id">Select User <span class="text-danger">*</span></label>
                            <select class="form-control" id="user_id" name="user_id" required>
                                <option value="">-- Select User --</option>
                                <?php foreach ($users as $u): ?>
                                    <?php if (!in_array($u['id'], $existing_member_ids)): ?>
                                    <option value="<?php echo $u['id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($u['full_name']); ?>"
                                            data-email="<?php echo htmlspecialchars($u['email']); ?>"
                                            data-role="<?php echo ucfirst($u['role']); ?>">
                                        <?php echo htmlspecialchars($u['full_name']); ?> (<?php echo ucfirst($u['role']); ?>)
                                    </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="user-preview" id="userPreview">
                            <div class="user-preview-header">
                                <div class="preview-avatar" id="previewAvatar">U</div>
                                <div class="preview-info">
                                    <h4 id="previewName">User Name</h4>
                                    <p id="previewEmail">user@email.com</p>
                                </div>
                            </div>
                            <span class="role-badge" id="previewRole">User Role</span>
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Project Role <span class="text-danger">*</span></label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="member">Member - Can view and edit assigned tasks</option>
                                <option value="lead">Lead - Can manage tasks and members</option>
                                <option value="viewer">Viewer - Read-only access</option>
                            </select>
                        </div>
                        
                        <div class="info-box">
                            <i class="fa fa-info-circle"></i>
                            <p>The selected user will be notified and given access to the project.</p>
                        </div>
                        
                        <hr>
                        
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fa fa-user-plus"></i> Add Member
                        </button>
                        <a href="project-detail.php?id=<?php echo $project_id; ?>&tab=team" class="btn btn-default btn-lg">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="existing-members">
                <h3><i class="fa fa-users"></i> Current Team Members (<?php echo count($existing_members); ?>)</h3>
                <?php if (empty($existing_members)): ?>
                    <p style="color: #64748b; text-align: center; padding: 20px;">No team members yet. Add the first member!</p>
                <?php else: ?>
                    <?php foreach ($existing_members as $member): ?>
                    <div class="member-item">
                        <div class="member-avatar-small">
                            <?php echo strtoupper(substr($member['full_name'], 0, 1)); ?>
                        </div>
                        <div class="member-details">
                            <strong><?php echo htmlspecialchars($member['full_name']); ?></strong>
                            <small><?php echo ucfirst($member['role']); ?> • <?php echo ucfirst($member['user_role']); ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // User selection preview
        $('#user_id').on('change', function() {
            const selectedOption = $(this).find('option:selected');
            const name = selectedOption.data('name');
            const email = selectedOption.data('email');
            const role = selectedOption.data('role');
            
            if ($(this).val()) {
                $('#userPreview').addClass('active');
                $('#previewAvatar').text(name ? name.charAt(0).toUpperCase() : 'U');
                $('#previewName').text(name || 'User Name');
                $('#previewEmail').text(email || 'user@email.com');
                $('#previewRole').text(role || 'User Role');
            } else {
                $('#userPreview').removeClass('active');
            }
        });
        
        // Form validation with visual feedback
        $('#addMemberForm').on('submit', function(e) {
            let isValid = true;
            
            $('.form-control[required]').each(function() {
                if (!$(this).val()) {
                    isValid = false;
                    $(this).css('border-color', '#ef4444');
                    setTimeout(() => {
                        $(this).css('border-color', '#e2e8f0');
                    }, 2000);
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });
        
        // Input animations
        $('.form-control').on('focus', function() {
            $(this).parent().find('label').css({
                'color': '#667eea',
                'transform': 'translateY(-2px)'
            });
        });
        
        $('.form-control').on('blur', function() {
            $(this).parent().find('label').css({
                'color': '#1e293b',
                'transform': 'translateY(0)'
            });
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
        
        // Animate member items on load
        $('.member-item').each(function(index) {
            $(this).css({
                'animation': `fadeInUp 0.3s ease ${index * 0.05}s both`
            });
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>