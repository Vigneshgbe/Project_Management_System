<?php
ob_start(); // Fix header warning

$page_title = 'Create User';
require_once '../includes/header.php';
require_once '../components/user.php';

$auth->checkAccess('admin');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = new User();
    
    $data = [
        'username' => $_POST['username'],
        'email' => $_POST['email'],
        'password' => $_POST['password'],
        'full_name' => $_POST['full_name'],
        'role' => $_POST['role']
    ];
    
    if ($user->create($data)) {
        ob_end_clean(); // Clear buffer before redirect
        header('Location: users.php');
        exit;
    } else {
        $error = 'Failed to create user. Username or email may already exist.';
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
    
    .user-create-container {
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
    
    /* ALERT */
    .alert-modern {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.05));
        border-left: 4px solid var(--danger);
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideDown 0.3s ease;
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .alert-modern i {
        font-size: 18px;
        color: var(--danger);
        flex-shrink: 0;
    }
    
    .alert-modern span {
        color: #991b1b;
        font-weight: 600;
        font-size: 14px;
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
    
    /* INFO SIDEBAR */
    .info-card {
        background: white;
        border-radius: 16px;
        padding: 32px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        position: sticky;
        top: 20px;
        animation: fadeInUp 0.4s ease 0.1s both;
    }
    
    .info-card-title {
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
    
    .info-card-title i {
        color: var(--primary);
        font-size: 16px;
    }
    
    .info-section {
        margin-bottom: 24px;
    }
    
    .info-section:last-child {
        margin-bottom: 0;
    }
    
    .info-section-title {
        font-weight: 700;
        color: var(--dark);
        font-size: 12px;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .info-section p {
        color: #64748b;
        font-weight: 500;
        font-size: 13px;
        line-height: 1.6;
        margin: 0 0 6px 0;
    }
    
    .info-section ul {
        margin: 0;
        padding-left: 20px;
    }
    
    .info-section li {
        color: #64748b;
        font-weight: 500;
        font-size: 13px;
        margin-bottom: 6px;
        line-height: 1.6;
    }
    
    .info-section li strong {
        color: var(--dark);
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
    
    /* ROLE BADGE PREVIEW */
    .role-badge-preview {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 10px;
        transition: all 0.3s ease;
    }
    
    .role-badge-preview i {
        font-size: 12px;
    }
    
    .role-badge-preview.user {
        background: #f1f5f9;
        color: #475569;
    }
    
    .role-badge-preview.manager {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .role-badge-preview.admin {
        background: #fee2e2;
        color: #991b1b;
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
        .user-create-container {
            padding: 20px;
        }
        .page-header {
            padding: 28px;
        }
        .page-header h1 {
            font-size: 28px;
        }
        .form-card, .info-card {
            padding: 32px;
        }
    }
    
    @media (max-width: 992px) {
        .info-card {
            position: static;
            margin-top: 24px;
        }
        .form-card {
            padding: 28px;
        }
    }
    
    @media (max-width: 768px) {
        .user-create-container {
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
        .form-card, .info-card {
            padding: 24px;
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
        .user-create-container {
            padding: 12px;
        }
        .page-header {
            padding: 20px;
        }
        .page-header h1 {
            font-size: 20px;
        }
        .form-card, .info-card {
            padding: 20px;
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

<div class="user-create-container">
    <div class="page-header">
        <h1>
            <i class="fa fa-user-plus"></i> Create New User
        </h1>
        <div class="page-breadcrumb">
            <a href="users.php">
                <i class="fa fa-users"></i> Users
            </a>
            <span>/</span>
            <span class="current">Create User</span>
        </div>
    </div>
    
    <?php if ($error): ?>
    <div class="alert-modern">
        <i class="fa fa-exclamation-circle"></i>
        <span><?php echo htmlspecialchars($error); ?></span>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="form-card">
                <form method="POST" action="" id="createUserForm">
                    <!-- PERSONAL INFORMATION -->
                    <div class="form-section-title">
                        <i class="fa fa-info-circle"></i> Personal Information
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
                                   placeholder="Enter full name"
                                   maxlength="100"
                                   required>
                        </div>
                    </div>
                    
                    <!-- ACCOUNT DETAILS -->
                    <div class="form-section-title">
                        <i class="fa fa-lock"></i> Account Details
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
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
                                           placeholder="Enter username"
                                           maxlength="50"
                                           required>
                                </div>
                                <span class="form-hint">
                                    <i class="fa fa-info-circle"></i>
                                    Must be unique
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
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
                                           placeholder="Enter email address"
                                           maxlength="100"
                                           required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group-modern">
                        <label for="password">
                            Password <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="fa fa-key"></i>
                            <input type="password" 
                                   class="form-control-modern" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Enter password"
                                   required 
                                   minlength="6"
                                   maxlength="100">
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="password-strength-bar" id="passwordStrengthBar"></div>
                        </div>
                        <span class="form-hint" id="passwordHint">
                            <i class="fa fa-info-circle"></i>
                            Minimum 6 characters required
                        </span>
                    </div>
                    
                    <!-- PERMISSIONS -->
                    <div class="form-section-title">
                        <i class="fa fa-shield"></i> Permissions
                    </div>
                    
                    <div class="form-group-modern">
                        <label for="role">
                            User Role <span class="required">*</span>
                        </label>
                        <select class="form-control-modern" id="role" name="role" required>
                            <option value="user">Member</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Administrator</option>
                        </select>
                        <span class="role-badge-preview user" id="rolePreview">
                            <i class="fa fa-user"></i> Member
                        </span>
                    </div>
                    
                    <!-- ACTION BUTTONS -->
                    <div class="form-actions">
                        <button type="submit" class="btn-modern primary">
                            <i class="fa fa-save"></i> Create User
                        </button>
                        <a href="users.php" class="btn-modern secondary">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="info-card">
                <div class="info-card-title">
                    <i class="fa fa-lightbulb-o"></i> Quick Guide
                </div>
                
                <div class="info-section">
                    <div class="info-section-title">Username Requirements</div>
                    <p>Choose a unique username for login</p>
                    <p><strong>Must be unique across all users</strong></p>
                </div>
                
                <div class="info-section">
                    <div class="info-section-title">Password Guidelines</div>
                    <ul>
                        <li><strong>Minimum 6 characters</strong></li>
                        <li>Mix uppercase & lowercase</li>
                        <li>Include numbers for strength</li>
                        <li>Add special characters for security</li>
                    </ul>
                </div>
                
                <div class="info-section">
                    <div class="info-section-title">Role Descriptions</div>
                    <ul>
                        <li><strong>Member:</strong> Basic access to projects</li>
                        <li><strong>Manager:</strong> Can manage projects & teams</li>
                        <li><strong>Administrator:</strong> Full system access</li>
                    </ul>
                </div>
                
                <div class="info-section">
                    <div class="info-section-title">Security Best Practices</div>
                    <ul>
                        <li>Use strong, unique passwords</li>
                        <li>Verify email addresses are correct</li>
                        <li>Assign appropriate role levels</li>
                        <li>Review user permissions regularly</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // ROLE BADGE PREVIEW UPDATE
    const roleSelect = $('#role');
    const rolePreview = $('#rolePreview');
    
    const roleIcons = {
        'user': 'fa-user',
        'manager': 'fa-users',
        'admin': 'fa-shield'
    };
    
    roleSelect.on('change', function() {
        const role = this.value;
        const roleText = this.options[this.selectedIndex].text;
        const icon = roleIcons[role];
        
        rolePreview[0].className = 'role-badge-preview ' + role;
        rolePreview.html('<i class="fa ' + icon + '"></i> ' + roleText);
    });
    
    // PASSWORD STRENGTH INDICATOR
    const passwordInput = $('#password');
    const passwordStrength = $('#passwordStrength');
    const passwordBar = $('#passwordStrengthBar');
    const passwordHint = $('#passwordHint');
    
    passwordInput.on('input', function() {
        const val = this.value;
        
        if (val.length === 0) {
            passwordStrength.hide();
            passwordBar[0].className = 'password-strength-bar';
            passwordHint.html('<i class="fa fa-info-circle"></i> Minimum 6 characters required');
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
    const form = $('#createUserForm');
    
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
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            
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
    
    // ANIMATE ELEMENTS ON LOAD
    $('.form-card').css('animation', 'fadeInUp 0.4s ease both');
    $('.info-card').css('animation', 'fadeInUp 0.4s ease 0.1s both');
});
</script>

<?php require_once '../includes/footer.php'; ?>