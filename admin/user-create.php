<?php
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
        header('Location: users.php');
        exit;
    } else {
        $error = 'Failed to create user. Username or email may already exist.';
    }
}
?>

<style>
    /* MODERN PROFESSIONAL DESIGN - OPTIMIZED FOR PERFORMANCE */
    
    :root {
        --primary: #667eea;
        --primary-dark: #5a67d8;
        --secondary: #764ba2;
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
        animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(15px); }
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
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.08), rgba(220, 38, 38, 0.08));
        border-left: 4px solid var(--danger);
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 32px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #991b1b;
        font-weight: 600;
        animation: slideIn 0.3s ease;
    }
    
    @keyframes slideIn {
        from { opacity: 0; transform: translateX(-20px); }
        to { opacity: 1; transform: translateX(0); }
    }
    
    .alert-modern i {
        font-size: 20px;
        color: var(--danger);
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
    
    /* INFO CARD */
    .info-card {
        background: white;
        border-radius: 16px;
        padding: 32px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        position: sticky;
        top: 20px;
        animation: fadeInUp 0.5s ease;
    }
    
    .info-card-title {
        font-size: 16px;
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
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }
    
    .info-section p {
        color: #64748b;
        font-weight: 500;
        font-size: 14px;
        line-height: 1.6;
        margin: 0 0 8px 0;
    }
    
    .info-section ul {
        margin: 0;
        padding-left: 20px;
    }
    
    .info-section li {
        color: #64748b;
        font-weight: 500;
        font-size: 14px;
        margin-bottom: 8px;
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
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
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
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23667eea' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
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
    }
    
    .input-icon-wrapper .form-control-modern {
        padding-left: 42px;
    }
    
    /* FORM HINTS */
    .form-hint {
        display: block;
        margin-top: 8px;
        font-size: 12px;
        color: #64748b;
        font-weight: 500;
    }
    
    .form-hint i {
        margin-right: 5px;
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
        width: 0%;
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
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 8px;
    }
    
    .role-badge-preview.user {
        background: #e0e7ff;
        color: #3730a3;
    }
    
    .role-badge-preview.manager {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .role-badge-preview.admin {
        background: #fee2e2;
        color: #991b1b;
    }
    
    /* EMAIL VALIDATION FEEDBACK */
    .email-validation {
        display: none;
        align-items: center;
        gap: 6px;
        margin-top: 8px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .email-validation.valid {
        display: flex;
        color: var(--success);
    }
    
    .email-validation.invalid {
        display: flex;
        color: var(--danger);
    }
    
    /* USERNAME VALIDATION FEEDBACK */
    .username-hint {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-top: 8px;
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
    }
    
    .username-hint.checking {
        color: var(--primary);
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
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
    }
    
    .btn-modern.primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(102, 126, 234, 0.35);
    }
    
    .btn-modern.secondary {
        background: white;
        color: var(--primary);
        border: 2px solid var(--primary);
    }
    
    .btn-modern.secondary:hover {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
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
            padding: 36px;
        }
    }
    
    @media (max-width: 992px) {
        .info-card {
            position: static;
            margin-top: 32px;
        }
        .form-card, .info-card {
            padding: 32px;
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

<div class="user-create-container container-fluid">
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
                                   placeholder="Enter full name"
                                   required>
                        </div>
                    </div>
                    
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
                                           required>
                                </div>
                                <div class="username-hint">
                                    <i class="fa fa-info-circle"></i>
                                    <span>Must be unique</span>
                                </div>
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
                                           required>
                                </div>
                                <div class="email-validation" id="emailValidation"></div>
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
                                   minlength="6">
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="password-strength-bar" id="passwordStrengthBar"></div>
                        </div>
                        <span class="form-hint" id="passwordHint">
                            <i class="fa fa-info-circle"></i>
                            Minimum 6 characters required
                        </span>
                    </div>
                    
                    <div class="form-section-title">
                        <i class="fa fa-shield-alt"></i> Permissions
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
                        <div class="role-badge-preview user" id="rolePreview">
                            <i class="fa fa-user"></i> Member
                        </div>
                    </div>
                    
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
                    <i class="fa fa-lightbulb"></i> Quick Guide
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
    $('#role').on('change', function() {
        const role = $(this).val();
        const $preview = $('#rolePreview');
        const roleData = {
            'user': { icon: 'fa-user', text: 'Member' },
            'manager': { icon: 'fa-users', text: 'Manager' },
            'admin': { icon: 'fa-shield-alt', text: 'Administrator' }
        };
        
        const data = roleData[role];
        $preview.removeClass('user manager admin').addClass(role);
        $preview.html('<i class="fa ' + data.icon + '"></i> ' + data.text);
    });
    
    // PASSWORD STRENGTH INDICATOR
    let passwordTimeout;
    $('#password').on('input', function() {
        clearTimeout(passwordTimeout);
        passwordTimeout = setTimeout(() => {
            const val = $(this).val();
            const $strength = $('#passwordStrength');
            const $bar = $('#passwordStrengthBar');
            const $hint = $('#passwordHint');
            
            if (val.length === 0) {
                $strength.hide();
                $bar.removeClass('weak medium strong');
                $hint.html('<i class="fa fa-info-circle"></i> Minimum 6 characters required');
                $hint.removeClass('password-strength-text weak medium strong');
                return;
            }
            
            $strength.show();
            
            let strength = 0;
            if (val.length >= 6) strength++;
            if (val.length >= 10) strength++;
            if (/[A-Z]/.test(val) && /[a-z]/.test(val)) strength++;
            if (/[0-9]/.test(val)) strength++;
            if (/[^A-Za-z0-9]/.test(val)) strength++;
            
            $bar.removeClass('weak medium strong');
            $hint.removeClass('password-strength-text weak medium strong');
            
            if (strength <= 2) {
                $bar.addClass('weak');
                $hint.html('<i class="fa fa-exclamation-triangle"></i> Weak password').addClass('password-strength-text weak');
            } else if (strength <= 4) {
                $bar.addClass('medium');
                $hint.html('<i class="fa fa-check-circle"></i> Medium strength').addClass('password-strength-text medium');
            } else {
                $bar.addClass('strong');
                $hint.html('<i class="fa fa-check-circle"></i> Strong password').addClass('password-strength-text strong');
            }
        }, 150);
    });
    
    // EMAIL VALIDATION WITH FEEDBACK
    let emailTimeout;
    $('#email').on('input', function() {
        clearTimeout(emailTimeout);
        const $validation = $('#emailValidation');
        
        emailTimeout = setTimeout(() => {
            const email = $(this).val();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email.length === 0) {
                $validation.removeClass('valid invalid').hide();
                return;
            }
            
            if (emailRegex.test(email)) {
                $validation.removeClass('invalid').addClass('valid').show();
                $validation.html('<i class="fa fa-check-circle"></i> Valid email format');
                $(this).css('border-color', var(--success));
            } else {
                $validation.removeClass('valid').addClass('invalid').show();
                $validation.html('<i class="fa fa-exclamation-circle"></i> Invalid email format');
            }
        }, 300);
    });
    
    $('#email').on('blur', function() {
        const email = $(this).val();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email && !emailRegex.test(email)) {
            $(this).css('border-color', var(--danger));
        } else if (email) {
            $(this).css('border-color', var(--success));
        }
    });
    
    // INPUT FOCUS EFFECTS (OPTIMIZED)
    $('.form-control-modern').on('focus', function() {
        $(this).closest('.form-group-modern').find('label').css('color', '#667eea');
    }).on('blur', function() {
        $(this).closest('.form-group-modern').find('label').css('color', '#64748b');
    });
    
    // FORM VALIDATION WITH SCROLL TO ERROR
    $('#createUserForm').on('submit', function(e) {
        let isValid = true;
        let firstInvalidField = null;
        
        $('.form-control-modern[required]').each(function() {
            if ($(this).val().trim() === '') {
                isValid = false;
                $(this).css('border-color', '#ef4444');
                
                if (!firstInvalidField) {
                    firstInvalidField = $(this);
                }
                
                $(this).one('input', function() {
                    $(this).css('border-color', '#e2e8f0');
                });
            }
        });
        
        // Validate email format
        const email = $('#email').val();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            isValid = false;
            $('#email').css('border-color', '#ef4444');
            if (!firstInvalidField) {
                firstInvalidField = $('#email');
            }
        }
        
        // Validate password length
        const password = $('#password').val();
        if (password && password.length < 6) {
            isValid = false;
            $('#password').css('border-color', '#ef4444');
            if (!firstInvalidField) {
                firstInvalidField = $('#password');
            }
        }
        
        if (!isValid) {
            e.preventDefault();
            
            // Scroll to first invalid field
            if (firstInvalidField) {
                $('html, body').animate({
                    scrollTop: firstInvalidField.offset().top - 100
                }, 300);
                firstInvalidField.focus();
            }
            
            // Show alert
            const errorMsg = 'Please fill in all required fields correctly.';
            if (!$('.alert-modern').length) {
                const alertHtml = '<div class="alert-modern" style="animation: slideIn 0.3s ease;">' +
                    '<i class="fa fa-exclamation-circle"></i>' +
                    '<span>' + errorMsg + '</span>' +
                    '</div>';
                $('.page-header').after(alertHtml);
                
                setTimeout(function() {
                    $('.alert-modern').fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 4000);
            }
        }
    });
    
    // USERNAME LOWERCASE CONVERSION (OPTIONAL)
    $('#username').on('input', function() {
        const val = $(this).val();
        if (val !== val.toLowerCase()) {
            $(this).val(val.toLowerCase());
        }
    });
    
    // CLEAR ERROR STYLING ON INPUT
    $('.form-control-modern').on('input', function() {
        if ($(this).val().trim() !== '') {
            $(this).css('border-color', '#e2e8f0');
        }
    });
    
    // PREVENT FORM RESUBMISSION
    let formSubmitted = false;
    $('#createUserForm').on('submit', function() {
        if (formSubmitted) {
            return false;
        }
        
        const isValid = $(this).find('.form-control-modern[required]').toArray().every(function(field) {
            return $(field).val().trim() !== '';
        });
        
        if (isValid) {
            formSubmitted = true;
            $(this).find('.btn-modern.primary').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Creating...');
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>