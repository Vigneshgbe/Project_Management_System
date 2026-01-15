<?php
ob_start(); // Fix header warning

$page_title = 'Add Team Member';
require_once 'includes/header.php';
require_once 'components/project.php';
require_once 'components/user.php';

$auth->checkAccess('manager');

$project_id = $_GET['project_id'] ?? 0;

$project_obj = new Project();
$project = $project_obj->getById($project_id);

if (!$project) {
    ob_end_clean();
    header('Location: projects.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $role = $_POST['role'];
    
    if ($project_obj->addMember($project_id, $user_id, $role)) {
        ob_end_clean(); // Clear buffer before redirect
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
    
    .add-member-container {
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
    
    /* FORM CARD */
    .form-card {
        background: white;
        border-radius: 16px;
        padding: 40px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        animation: fadeInUp 0.4s ease;
        margin-bottom: 24px;
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* PROJECT INFO BOX */
    .project-info-box {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.05));
        border-left: 4px solid var(--primary);
        padding: 18px 22px;
        border-radius: 12px;
        margin-bottom: 32px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .project-info-box i {
        color: var(--primary);
        font-size: 20px;
    }
    
    .project-info-box-content h3 {
        margin: 0 0 4px;
        font-weight: 700;
        color: #64748b;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }
    
    .project-info-box-content p {
        margin: 0;
        color: var(--dark);
        font-weight: 600;
        font-size: 16px;
    }
    
    /* SECTION TITLES */
    .form-section-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 24px;
        padding-bottom: 12px;
        border-bottom: 2px solid var(--border);
        display: flex;
        align-items: center;
        gap: 10px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }
    
    .form-section-title i {
        color: var(--primary);
        font-size: 16px;
    }
    
    .form-section-title:not(:first-child) {
        margin-top: 40px;
    }
    
    /* FORM GROUPS */
    .form-group-modern {
        margin-bottom: 24px;
    }
    
    .form-group-modern label {
        display: block;
        font-weight: 700;
        font-size: 11px;
        color: #64748b;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }
    
    .form-group-modern label .required {
        color: var(--danger);
        margin-left: 4px;
    }
    
    /* FORM CONTROLS */
    .form-control-modern {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid var(--border);
        border-radius: 10px;
        font-size: 15px;
        font-weight: 500;
        color: var(--dark);
        background: white;
        transition: all 0.3s ease;
    }
    
    .form-control-modern:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }
    
    .form-control-modern:hover {
        border-color: #cbd5e1;
    }
    
    .form-control-modern::placeholder {
        color: #94a3b8;
    }
    
    select.form-control-modern {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236366f1' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 16px center;
        padding-right: 40px;
    }
    
    /* USER PREVIEW CARD */
    .user-preview {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.08), rgba(139, 92, 246, 0.05));
        border-radius: 12px;
        padding: 24px;
        margin-top: 16px;
        margin-bottom: 8px;
        border: 2px solid rgba(99, 102, 241, 0.2);
        display: none;
        animation: slideDown 0.3s ease;
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .user-preview.active {
        display: block;
    }
    
    .user-preview-header {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 12px;
    }
    
    .preview-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-weight: 700;
        font-size: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        flex-shrink: 0;
    }
    
    .preview-info h4 {
        margin: 0 0 6px;
        font-weight: 700;
        color: var(--dark);
        font-size: 17px;
    }
    
    .preview-info p {
        margin: 0;
        color: #64748b;
        font-size: 13px;
        font-weight: 500;
    }
    
    .role-badge {
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background: #dbeafe;
        color: #1e40af;
        display: inline-block;
    }
    
    /* INFO BOX */
    .info-box {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(37, 99, 235, 0.05));
        border-left: 4px solid #3b82f6;
        padding: 16px 20px;
        border-radius: 12px;
        margin-top: 24px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }
    
    .info-box i {
        color: #3b82f6;
        font-size: 18px;
        flex-shrink: 0;
        margin-top: 2px;
    }
    
    .info-box p {
        margin: 0;
        color: #1e40af;
        font-weight: 600;
        line-height: 1.6;
        font-size: 13px;
    }
    
    /* EXISTING MEMBERS CARD */
    .existing-members-card {
        background: white;
        border-radius: 16px;
        padding: 32px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        animation: fadeInUp 0.5s ease;
        position: sticky;
        top: 20px;
    }
    
    .existing-members-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 24px;
        padding-bottom: 12px;
        border-bottom: 2px solid var(--border);
        display: flex;
        align-items: center;
        gap: 10px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }
    
    .existing-members-title i {
        color: var(--primary);
        font-size: 16px;
    }
    
    .existing-members-title .count {
        margin-left: auto;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
    }
    
    .member-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px;
        background: white;
        border-radius: 10px;
        margin-bottom: 10px;
        border: 1px solid var(--border);
        transition: all 0.3s ease;
    }
    
    .member-item:last-child {
        margin-bottom: 0;
    }
    
    .member-item:hover {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.03));
        transform: translateX(4px);
        border-color: rgba(99, 102, 241, 0.2);
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.15);
    }
    
    .member-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-weight: 700;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
    }
    
    .member-details {
        flex: 1;
    }
    
    .member-details strong {
        display: block;
        color: var(--dark);
        font-size: 14px;
        font-weight: 700;
        margin-bottom: 4px;
    }
    
    .member-details small {
        color: #64748b;
        font-size: 12px;
        font-weight: 500;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #94a3b8;
    }
    
    .empty-state i {
        font-size: 48px;
        color: #cbd5e1;
        margin-bottom: 12px;
        display: block;
    }
    
    .empty-state p {
        margin: 0;
        font-size: 14px;
        font-weight: 500;
    }
    
    /* FORM ACTIONS */
    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 2px solid var(--border);
        flex-wrap: wrap;
    }
    
    /* BUTTONS */
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
        gap: 8px;
        text-decoration: none;
    }
    
    .btn-modern.primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
    }
    
    .btn-modern.primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(99, 102, 241, 0.35);
    }
    
    .btn-modern.primary:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.2);
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
        .add-member-container {
            padding: 20px;
        }
        .page-header {
            padding: 28px;
        }
        .page-header h1 {
            font-size: 28px;
        }
        .form-card {
            padding: 32px;
        }
        .existing-members-card {
            padding: 28px;
        }
    }
    
    @media (max-width: 992px) {
        .form-card {
            padding: 28px;
        }
        .existing-members-card {
            position: static;
            margin-top: 24px;
        }
    }
    
    @media (max-width: 768px) {
        .add-member-container {
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
        .form-card {
            padding: 24px;
        }
        .existing-members-card {
            padding: 24px;
        }
        .form-control-modern {
            padding: 12px 14px;
            font-size: 14px;
        }
        .form-actions {
            flex-direction: column;
        }
        .btn-modern {
            width: 100%;
            justify-content: center;
        }
    }
    
    @media (max-width: 480px) {
        .add-member-container {
            padding: 12px;
        }
        .page-header {
            padding: 20px;
        }
        .page-header h1 {
            font-size: 20px;
        }
        .form-card {
            padding: 20px;
        }
        .existing-members-card {
            padding: 20px;
        }
        .form-control-modern {
            padding: 10px 14px;
            font-size: 13px;
        }
        .form-section-title {
            font-size: 13px;
        }
        .user-preview-header {
            flex-direction: column;
            text-align: center;
        }
    }
</style>

<div class="add-member-container">
    <div class="page-header">
        <h1>
            <i class="fa fa-user-plus"></i> Add Team Member
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
            <span class="current">Add Member</span>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-card">
                <div class="project-info-box">
                    <i class="fa fa-folder"></i>
                    <div class="project-info-box-content">
                        <h3>Adding to Project</h3>
                        <p><?php echo htmlspecialchars($project['project_name']); ?></p>
                    </div>
                </div>
                
                <form method="POST" action="" id="addMemberForm">
                    <div class="form-section-title">
                        <i class="fa fa-user"></i> User Selection
                    </div>
                    
                    <div class="form-group-modern">
                        <label for="user_id">Select User <span class="required">*</span></label>
                        <select class="form-control-modern" id="user_id" name="user_id" required>
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
                    
                    <div class="form-section-title">
                        <i class="fa fa-shield"></i> Project Role
                    </div>
                    
                    <div class="form-group-modern">
                        <label for="role">Assign Role <span class="required">*</span></label>
                        <select class="form-control-modern" id="role" name="role" required>
                            <option value="member">Member - Can view and edit assigned tasks</option>
                            <option value="lead">Lead - Can manage tasks and members</option>
                            <option value="viewer">Viewer - Read-only access</option>
                        </select>
                    </div>
                    
                    <div class="info-box">
                        <i class="fa fa-info-circle"></i>
                        <p>The selected user will be notified and given access to the project based on their assigned role.</p>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-modern primary">
                            <i class="fa fa-user-plus"></i> Add Member
                        </button>
                        <a href="project-detail.php?id=<?php echo $project_id; ?>&tab=team" class="btn-modern secondary">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="existing-members-card">
                <div class="existing-members-title">
                    <i class="fa fa-users"></i> Current Team
                    <span class="count"><?php echo count($existing_members); ?></span>
                </div>
                
                <?php if (empty($existing_members)): ?>
                    <div class="empty-state">
                        <i class="fa fa-users"></i>
                        <p>No team members yet. Add the first member!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($existing_members as $member): ?>
                    <div class="member-item">
                        <div class="member-avatar">
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
    // USER SELECTION PREVIEW
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
    
    // FORM VALIDATION
    $('#addMemberForm').on('submit', function(e) {
        let isValid = true;
        
        $('.form-control-modern[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).css('border-color', '#ef4444');
                $(this).one('change', function() {
                    $(this).css('border-color', '#e2e8f0');
                });
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            $('html, body').animate({
                scrollTop: $('.form-control-modern[required]').filter(function() {
                    return !$(this).val();
                }).first().offset().top - 100
            }, 300);
        }
    });
    
    // INPUT FOCUS EFFECTS
    $('.form-control-modern').on('focus', function() {
        $(this).closest('.form-group-modern').find('label').css({
            'color': '#6366f1',
            'transition': 'color 0.3s ease'
        });
    }).on('blur', function() {
        $(this).closest('.form-group-modern').find('label').css({
            'color': '#64748b'
        });
    });
    
    // STAGGERED ANIMATION FOR MEMBER ITEMS
    $('.member-item').each(function(i) {
        $(this).css({
            'animation': `fadeInUp 0.3s ease ${i * 0.05}s both`
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>