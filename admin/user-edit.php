<?php
$page_title = 'Edit User';
require_once '../includes/header.php';
require_once '../components/user.php';

$auth->checkAccess('admin');

$user_id = $_GET['id'] ?? 0;
$user_obj = new User();
$user = $user_obj->getById($user_id);

if (!$user) {
    header('Location: users.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => $_POST['username'],
        'email' => $_POST['email'],
        'full_name' => $_POST['full_name'],
        'role' => $_POST['role'],
        'status' => $_POST['status']
    ];
    
    if (!empty($_POST['password'])) {
        $data['password'] = $_POST['password'];
    }
    
    if ($user_obj->update($user_id, $data)) {
        header('Location: users.php');
        exit;
    }
}
?>

<style>
    /* MODERN PROFESSIONAL DESIGN - OPTIMIZED */
    
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
    
    .user-edit-container {
        padding: 24px;
        max-width: 1200px;
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
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* USER AVATAR SECTION */
    .user-avatar-section {
        text-align: center;
        padding: 32px;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.03));
        border-radius: 12px;
        margin-bottom: 32px;
        border: 1px solid rgba(99, 102, 241, 0.1);
    }
    
    .user-avatar-large {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 48px;
        box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3);
        margin-bottom: 16px;
    }
    
    .user-avatar-name {
        font-size: 24px;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 6px;
    }
    
    .user-avatar-role {
        font-size: 14px;
        color: #64748b;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .status-badge.active {
        background: #d1fae5;
        color: #065f46;
    }
    
    .status-badge.inactive {
        background: #f1f5f9;
        color: #475569;
    }
    
    .status-badge i {
        font-size: 8px;
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
    
    /* INPUT WITH ICONS */
    .input-icon-wrapper {
        position: relative;
    }
    
    .input-icon-wrapper i {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--primary);
        font-size: 14px;
        pointer-events: none;
    }
    
    .input-icon-wrapper .form-control-modern {
        padding-left: 42px;
    }
    
    /* FORM HINTS */
    .form-hint {
        display: block;
        margin-top: 6px;
        font-size: 12px;
        color: #64748b;
        font-weight: 500;
    }
    
    .form-hint i {
        margin-right: 4px;
        font-size: 11px;
    }
    
    /* PASSWORD STRENGTH */
    .password-strength {
        height: 4px;
        background: var(--border);
        border-radius: 4px;
        margin-top: 8px;
        overflow: hidden;
        display: none;
    }
    
    .password-strength-bar {
        height: 100%;
        width: 0;
        transition: all 0.3s ease;
        border-radius: 4px;
    }
    
    .password-strength-bar.weak {
        background: var(--danger);
        width: 33%;
    }
    
    .password-strength-bar.medium {
        background: var(--warning);
        width: 66%;
    }
    
    .password-strength-bar.strong {
        background: var(--success);
        width: 100%;
    }
    
    .password-strength-text {
        font-size: 12px;
        margin-top: 6px;
        font-weight: 600;
    }
    
    .password-strength-text.weak {
        color: var(--danger);
    }
    
    .password-strength-text.medium {
        color: var(--warning);
    }
    
    .password-strength-text.strong {
        color: var(--success);
    }
    
    /* ROLE & STATUS PREVIEW */
    .role-status-preview {
        display: flex;
        gap: 12px;
        margin-top: 8px;
        flex-wrap: wrap;
    }
    
    .preview-badge {
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
    
    .preview-badge.role-user {
        background: #f1f5f9;
        color: #475569;
    }
    
    .preview-badge.role-manager {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .preview-badge.role-admin {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .preview-badge.status-active {
        background: #d1fae5;
        color: #065f46;
    }
    
    .preview-badge.status-inactive {
        background: #f1f5f9;
        color: #475569;
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
    
    .btn-modern.secondary {
        background: white;
        color: var(--primary);
        border: 2px solid var(--primary);
    }
    
    .btn-modern.secondary:hover {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
        transform: translateY(-2px);
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
        .user-edit-container {
            padding: 20px;
        }
        .page-header {
            padding: 28px;
        }
        .page-header h1 {
            font-size: 28px;
        }
        .form-card {
            padding: 36px;
        }
    }
    
    @media (max-width: 768px) {
        .user-edit-container {
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
        .user-avatar-section {
            padding: 24px;
        }
        .user-avatar-large {
            width: 100px;
            height: 100px;
            font-size: 40px;
        }
        .user-avatar-name {
            font-size: 20px;
        }
        .user-avatar-role {
            flex-direction: column;
            gap: 6px;
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
        .user-edit-container {
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
        .user-avatar-section {
            padding: 20px;
        }
        .user-avatar-large {
            width: 80px;
            height: 80px;
            font-size: 32px;
        }
        .user-avatar-name {
            font-size: 18px;
        }
        .form-control-modern {
            padding: 12px 14px;
            font-size: 14px;
        }
        .input-icon-wrapper .form-control-modern {
            padding-left: 38px;
        }
        .form-section-title {
            font-size: 13px;
        }
    }
</style>

<div class="user-edit-container container-fluid">
    <div class="page-header">
        <h1>
            <i class="fa fa-edit"></i> Edit User
        </h1>
        <div class="page-breadcrumb">
            <a href="users.php">
                <i class="fa fa-users"></i> Users
            </a>
            <span>/</span>
            <span class="current">Edit User</span>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="form-card">
                <!-- USER AVATAR SECTION -->
                <div class="user-avatar-section">
                    <div class="user-avatar-large">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                    <div class="user-avatar-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                    <div class="user-avatar-role">
                        <span><?php echo ucfirst($user['role']); ?></span>
                        <span>•</span>
                        <span class="status-badge <?php echo $user['status']; ?>">
                            <i class="fa fa-circle"></i>
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                    </div>
                </div>
                
                <form method="POST" action="" id="editUserForm">
                    <!-- PERSONAL INFORMATION -->
                    <div class="form-section-title">
                        <i class="fa fa-user"></i> Personal Information
                    </div>
                    
                    <div class="form-group-modern">
                        <label for="full_name">
                            Full Name <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="fa fa-user"></i>
                            <input type="text" 
                                   class="form-control-modern" 
                                   id="full_name" 
                                   name="full_name" 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                   placeholder="Enter full name"
                                   maxlength="100"
                                   required>
                        </div>
                    </div>
                    
                    <!-- ACCOUNT DETAILS -->
                    <div class="form-section-title">
                        <i class="fa fa-lock"></i> Account Details
                    </div>
                    
                    <div class="form-group-modern">
                        <label for="username">
                            Username <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="fa fa-at"></i>
                            <input type="text" 
                                   class="form-control-modern" 
                                   id="username" 
                                   name="username" 
                                   value="<?php echo htmlspecialchars($user['username']); ?>" 
                                   placeholder="Enter username"
                                   maxlength="50"
                                   required>
                        </div>
                    </div>
                    
                    <div class="form-group-modern">
                        <label for="email">
                            Email Address <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="fa fa-envelope"></i>
                            <input type="email" 
                                   class="form-control-modern" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" 
                                   placeholder="Enter email address"
                                   maxlength="100"
                                   required>
                        </div>
                    </div>
                    
                    <div class="form-group-modern">
                        <label for="password">
                            New Password
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="fa fa-key"></i>
                            <input type="password" 
                                   class="form-control-modern" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Enter new password (optional)"
                                   minlength="6"
                                   maxlength="100">
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="password-strength-bar" id="passwordStrengthBar"></div>
                        </div>
                        <span class="form-hint" id="passwordHint">
                            <i class="fa fa-info-circle"></i>
                            Leave blank to keep current password. Minimum 6 characters if changing.
                        </span>
                    </div>
                    
                    <!-- PERMISSIONS & STATUS -->
                    <div class="form-section-title">
                        <i class="fa fa-shield-alt"></i> Permissions & Status
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label for="role">
                                    Role <span class="required">*</span>
                                </label>
                                <select class="form-control-modern" id="role" name="role" required>
                                    <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>Member</option>
                                    <option value="manager" <?php echo $user['role'] === 'manager' ? 'selected' : ''; ?>>Manager</option>
                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                </select>
                                <div class="role-status-preview">
                                    <span class="preview-badge role-<?php echo $user['role']; ?>" id="rolePreview">
                                        <i class="fa fa-user-tag"></i>
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label for="status">
                                    Status <span class="required">*</span>
                                </label>
                                <select class="form-control-modern" id="status" name="status" required>
                                    <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                                <div class="role-status-preview">
                                    <span class="preview-badge status-<?php echo $user['status']; ?>" id="statusPreview">
                                        <i class="fa fa-circle"></i>
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ACTION BUTTONS -->
                    <div class="form-actions">
                        <button type="submit" class="btn-modern primary">
                            <i class="fa fa-save"></i> Update User
                        </button>
                        <a href="users.php" class="btn-modern secondary">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Cache selectors for performance
    const roleSelect = $('#role');
    const rolePreview = $('#rolePreview');
    const statusSelect = $('#status');
    const statusPreview = $('#statusPreview');
    const passwordInput = $('#password');
    const passwordStrength = $('#passwordStrength');
    const passwordBar = $('#passwordStrengthBar');
    const passwordHint = $('#passwordHint');
    const form = $('#editUserForm');
    
    // ROLE PREVIEW UPDATE
    roleSelect.on('change', function() {
        const role = this.value;
        const roleText = this.options[this.selectedIndex].text;
        
        rolePreview[0].className = 'preview-badge role-' + role;
        rolePreview.html('<i class="fa fa-user-tag"></i> ' + roleText);
    });
    
    // STATUS PREVIEW UPDATE
    statusSelect.on('change', function() {
        const status = this.value;
        const statusText = this.options[this.selectedIndex].text;
        
        statusPreview[0].className = 'preview-badge status-' + status;
        statusPreview.html('<i class="fa fa-circle"></i> ' + statusText);
    });
    
    // PASSWORD STRENGTH INDICATOR
    passwordInput.on('input', function() {
        const val = this.value;
        
        if (val.length === 0) {
            passwordStrength.hide();
            passwordBar[0].className = 'password-strength-bar';
            passwordHint.html('<i class="fa fa-info-circle"></i> Leave blank to keep current password. Minimum 6 characters if changing.');
            passwordHint[0].className = 'form-hint';
            return;
        }
        
        passwordStrength.show();
        
        let strength = 0;
        if (val.length >= 6) strength++;
        if (val.length >= 10) strength++;
        if (/[A-Z]/.test(val) && /[a-z]/.test(val)) strength++;
        if (/[0-9]/.test(val)) strength++;
        if (/[^A-Za-z0-9]/.test(val)) strength++;
        
        let strengthClass, strengthText, strengthIcon;
        
        if (strength <= 2) {
            strengthClass = 'weak';
            strengthText = 'Weak password';
            strengthIcon = 'fa-exclamation-triangle';
        } else if (strength <= 4) {
            strengthClass = 'medium';
            strengthText = 'Medium strength';
            strengthIcon = 'fa-check-circle';
        } else {
            strengthClass = 'strong';
            strengthText = 'Strong password';
            strengthIcon = 'fa-check-circle';
        }
        
        passwordBar[0].className = 'password-strength-bar ' + strengthClass;
        passwordHint[0].className = 'form-hint password-strength-text ' + strengthClass;
        passwordHint.html('<i class="fa ' + strengthIcon + '"></i> ' + strengthText);
    });
    
    // FORM VALIDATION
    form.on('submit', function(e) {
        let isValid = true;
        const requiredFields = $('.form-control-modern[required]');
        
        requiredFields.each(function() {
            if (!this.value.trim()) {
                isValid = false;
                this.style.borderColor = '#ef4444';
                
                // Reset border on input
                $(this).one('input', function() {
                    this.style.borderColor = '#e2e8f0';
                });
            }
        });
        
        // Validate password length if provided
        const password = passwordInput.val();
        if (password && password.length < 6) {
            isValid = false;
            passwordInput.css('border-color', '#ef4444');
            alert('Password must be at least 6 characters long.');
        }
        
        if (!isValid) {
            e.preventDefault();
            if (!password || password.length >= 6) {
                alert('Please fill in all required fields.');
            }
            
            // Scroll to first invalid field
            const firstInvalid = requiredFields.filter(function() {
                return !this.value.trim();
            }).first();
            
            if (firstInvalid.length) {
                $('html, body').animate({
                    scrollTop: firstInvalid.offset().top - 100
                }, 300);
            }
        }
    });
    
    // INPUT FOCUS EFFECTS
    $('.form-control-modern').on('focus', function() {
        const label = $(this).closest('.form-group-modern').find('label');
        label.css({
            'color': '#6366f1',
            'transition': 'color 0.3s ease'
        });
    }).on('blur', function() {
        const label = $(this).closest('.form-group-modern').find('label');
        label.css('color', '#64748b');
    });
    
    // EMAIL VALIDATION
    const emailInput = $('#email');
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    emailInput.on('blur', function() {
        const email = this.value;
        
        if (email && !emailRegex.test(email)) {
            this.style.borderColor = '#ef4444';
            alert('Please enter a valid email address.');
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>