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
        margin: 0;
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
    
    /* PANELS */
    .panel {
        border: none;
        box-shadow: var(--shadow);
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        background: white;
        border: 1px solid var(--border);
        margin-bottom: 24px;
        animation: fadeInUp 0.4s ease;
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .panel:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }
    
    .panel-body {
        padding: 32px;
        background: white;
    }
    
    /* PROJECT INFO BOX */
    .project-info-box {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.03));
        border-left: 4px solid var(--primary);
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 24px;
    }
    
    .project-info-box h3 {
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
    
    .project-info-box h3 i {
        color: var(--primary);
        font-size: 12px;
    }
    
    .project-info-box p {
        margin: 0;
        color: var(--dark);
        font-weight: 600;
        font-size: 16px;
    }
    
    /* FORM GROUPS */
    .form-group {
        margin-bottom: 24px;
    }
    
    .form-group label {
        display: block;
        font-weight: 700;
        font-size: 11px;
        color: #64748b;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }
    
    .form-group label .text-danger {
        color: var(--danger);
        margin-left: 4px;
    }
    
    /* FORM CONTROLS */
    .form-control {
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
    
    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }
    
    .form-control::placeholder {
        color: #94a3b8;
    }
    
    select.form-control {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236366f1' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 16px center;
        padding-right: 40px;
    }
    
    /* USER PREVIEW */
    .user-preview {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.03));
        border-radius: 12px;
        padding: 20px;
        margin-top: 16px;
        margin-bottom: 8px;
        border: 1px solid rgba(99, 102, 241, 0.15);
        display: none;
    }
    
    .user-preview.active {
        display: block;
    }
    
    .user-preview-header {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 12px;
    }
    
    .preview-avatar {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-weight: 700;
        font-size: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
    }
    
    .preview-info h4 {
        margin: 0 0 4px;
        font-weight: 700;
        color: var(--dark);
        font-size: 16px;
    }
    
    .preview-info p {
        margin: 0;
        color: #64748b;
        font-size: 13px;
        font-weight: 500;
    }
    
    .role-badge {
        padding: 6px 12px;
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
        line-height: 1.5;
        font-size: 13px;
    }
    
    /* EXISTING MEMBERS */
    .existing-members {
        background: white;
        border-radius: 16px;
        padding: 32px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        animation: fadeInUp 0.4s ease 0.1s both;
    }
    
    .existing-members h3 {
        margin: 0 0 20px;
        font-weight: 700;
        font-size: 17px;
        color: var(--dark);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 15px;
    }
    
    .existing-members h3 i {
        color: var(--primary);
        font-size: 16px;
    }
    
    .member-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        background: white;
        border-radius: 10px;
        margin-bottom: 10px;
        border: 1px solid var(--border);
        transition: all 0.3s ease;
    }
    
    .member-item:hover {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.03));
        transform: translateX(4px);
        border-color: rgba(99, 102, 241, 0.2);
    }
    
    .member-avatar-small {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-weight: 700;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.2);
    }
    
    .member-details {
        flex: 1;
    }
    
    .member-details strong {
        display: block;
        color: var(--dark);
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 2px;
    }
    
    .member-details small {
        color: #64748b;
        font-size: 12px;
        font-weight: 500;
    }
    
    /* BUTTONS */
    .btn {
        border-radius: 10px;
        padding: 12px 28px;
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
    
    .btn i {
        font-size: 14px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(99, 102, 241, 0.35);
        color: white;
    }
    
    .btn-primary:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.2);
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
    
    .btn-lg {
        padding: 14px 32px;
        font-size: 13px;
    }
    
    /* HORIZONTAL RULE */
    hr {
        border: none;
        height: 2px;
        background: var(--border);
        margin: 32px 0;
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
        .panel-body {
            padding: 28px;
        }
    }
    
    @media (max-width: 992px) {
        .panel-body {
            padding: 24px;
        }
        .existing-members {
            padding: 28px;
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
        .panel-body {
            padding: 20px;
        }
        .form-control {
            padding: 12px 14px;
            font-size: 14px;
        }
        .btn-lg {
            padding: 14px 28px;
            width: 100%;
            margin-bottom: 10px;
            justify-content: center;
        }
        .existing-members {
            padding: 24px;
            margin-top: 24px;
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
        .panel-body {
            padding: 16px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-control {
            padding: 10px 14px;
            font-size: 13px;
        }
        .existing-members {
            padding: 20px;
        }
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
                    <p style="color: #64748b; text-align: center; padding: 20px; font-size: 14px;">No team members yet. Add the first member!</p>
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
    
    // Form validation
    $('#addMemberForm').on('submit', function(e) {
        let isValid = true;
        
        $('.form-control[required]').each(function() {
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
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>