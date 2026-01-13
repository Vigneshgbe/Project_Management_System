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
    .user-create-container {
        background: transparent !important;
        min-height: calc(100vh - 100px) !important;
        padding: 20px !important;
        margin: 0 !important;
    }
    
    .user-create-header {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        color: #1e293b !important;
        padding: 30px 35px !important;
        border-radius: 20px !important;
        margin-bottom: 30px !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        position: relative !important;
        overflow: hidden !important;
    }
    
    .user-create-header::before {
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
    
    .user-create-header h1 {
        margin: 0 !important;
        font-weight: 800 !important;
        font-size: 32px !important;
        position: relative !important;
        z-index: 1 !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
        display: flex !important;
        align-items: center !important;
        gap: 15px !important;
    }
    
    .user-create-header h1 i {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }
    
    .user-create-breadcrumb {
        margin-top: 12px !important;
        position: relative !important;
        z-index: 1 !important;
    }
    
    .user-create-breadcrumb a {
        color: #667eea !important;
        text-decoration: none !important;
        font-weight: 600 !important;
        transition: color 0.3s ease !important;
    }
    
    .user-create-breadcrumb a:hover {
        color: #764ba2 !important;
    }
    
    .user-create-breadcrumb span {
        color: #64748b !important;
        margin: 0 8px !important;
    }
    
    .form-card {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        border-radius: 20px !important;
        padding: 35px !important;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
    }
    
    .alert-modern {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%) !important;
        border-left: 4px solid #ef4444 !important;
        border-radius: 12px !important;
        padding: 15px 20px !important;
        margin-bottom: 25px !important;
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        color: #991b1b !important;
        font-weight: 600 !important;
    }
    
    .alert-modern i {
        font-size: 20px !important;
        color: #ef4444 !important;
    }
    
    .form-section-title {
        font-size: 18px !important;
        font-weight: 700 !important;
        color: #1e293b !important;
        margin-bottom: 25px !important;
        padding-bottom: 15px !important;
        border-bottom: 3px solid transparent !important;
        border-image: linear-gradient(90deg, #667eea 0%, #764ba2 100%) !important;
        border-image-slice: 1 !important;
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
    }
    
    .form-section-title i {
        color: #667eea !important;
    }
    
    .form-group-modern {
        margin-bottom: 25px !important;
    }
    
    .form-group-modern label {
        display: block !important;
        font-weight: 700 !important;
        font-size: 14px !important;
        color: #1e293b !important;
        margin-bottom: 10px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }
    
    .form-group-modern label .required {
        color: #ef4444 !important;
        margin-left: 4px !important;
    }
    
    .form-control-modern {
        width: 100% !important;
        padding: 14px 18px !important;
        border: 2px solid #e2e8f0 !important;
        border-radius: 12px !important;
        font-size: 15px !important;
        font-weight: 500 !important;
        color: #1e293b !important;
        background: white !important;
        transition: all 0.3s ease !important;
    }
    
    .form-control-modern:focus {
        outline: none !important;
        border-color: #667eea !important;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1) !important;
    }
    
    .form-control-modern:hover {
        border-color: #cbd5e1 !important;
    }
    
    .form-control-modern::placeholder {
        color: #94a3b8 !important;
    }
    
    .form-hint {
        display: block !important;
        margin-top: 8px !important;
        font-size: 13px !important;
        color: #64748b !important;
        font-weight: 500 !important;
    }
    
    .form-hint i {
        margin-right: 5px !important;
    }
    
    select.form-control-modern {
        appearance: none !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23667eea' d='M6 9L1 4h10z'/%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important;
        background-position: right 18px center !important;
        padding-right: 45px !important;
    }
    
    .input-icon-wrapper {
        position: relative !important;
    }
    
    .input-icon-wrapper i {
        position: absolute !important;
        left: 18px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        color: #667eea !important;
        font-size: 16px !important;
    }
    
    .input-icon-wrapper .form-control-modern {
        padding-left: 45px !important;
    }
    
    .form-actions {
        display: flex !important;
        gap: 15px !important;
        margin-top: 35px !important;
        padding-top: 30px !important;
        border-top: 2px solid #e2e8f0 !important;
        flex-wrap: wrap !important;
    }
    
    .btn-modern {
        padding: 14px 32px !important;
        border-radius: 12px !important;
        font-weight: 700 !important;
        font-size: 14px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        transition: all 0.3s ease !important;
        border: none !important;
        cursor: pointer !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 10px !important;
        text-decoration: none !important;
    }
    
    .btn-modern.primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3) !important;
    }
    
    .btn-modern.primary:hover {
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4) !important;
    }
    
    .btn-modern.secondary {
        background: white !important;
        color: #667eea !important;
        border: 2px solid #667eea !important;
    }
    
    .btn-modern.secondary:hover {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%) !important;
        transform: translateY(-2px) !important;
    }
    
    .password-strength {
        height: 4px !important;
        background: #e2e8f0 !important;
        border-radius: 4px !important;
        margin-top: 8px !important;
        overflow: hidden !important;
        display: none !important;
    }
    
    .password-strength-bar {
        height: 100% !important;
        width: 0% !important;
        transition: all 0.3s ease !important;
        border-radius: 4px !important;
    }
    
    .password-strength-bar.weak {
        background: #ef4444 !important;
        width: 33% !important;
    }
    
    .password-strength-bar.medium {
        background: #f59e0b !important;
        width: 66% !important;
    }
    
    .password-strength-bar.strong {
        background: #10b981 !important;
        width: 100% !important;
    }
    
    .password-strength-text {
        font-size: 12px !important;
        margin-top: 6px !important;
        font-weight: 600 !important;
    }
    
    .password-strength-text.weak {
        color: #ef4444 !important;
    }
    
    .password-strength-text.medium {
        color: #f59e0b !important;
    }
    
    .password-strength-text.strong {
        color: #10b981 !important;
    }
    
    @media (max-width: 1200px) {
        .user-create-container {
            padding: 15px !important;
        }
        .user-create-header {
            padding: 25px 30px !important;
        }
        .form-card {
            padding: 30px !important;
        }
    }
    
    @media (max-width: 768px) {
        .user-create-container {
            padding: 10px !important;
        }
        .user-create-header {
            padding: 20px !important;
            margin-bottom: 20px !important;
        }
        .user-create-header h1 {
            font-size: 24px !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 10px !important;
        }
        .form-card {
            padding: 20px !important;
        }
        .form-actions {
            flex-direction: column !important;
        }
        .btn-modern {
            width: 100% !important;
            justify-content: center !important;
        }
    }
    
    @media (max-width: 480px) {
        .user-create-container {
            padding: 8px !important;
        }
        .user-create-header h1 {
            font-size: 20px !important;
        }
        .form-card {
            padding: 15px !important;
        }
        .form-control-modern {
            padding: 12px 16px !important;
            font-size: 14px !important;
        }
        .input-icon-wrapper .form-control-modern {
            padding-left: 40px !important;
        }
    }
</style>

<div class="user-create-container container-fluid">
    <div class="user-create-header">
        <h1>
            <i class="fa fa-plus-circle"></i> Create User
        </h1>
        <div class="user-create-breadcrumb">
            <a href="users.php"><i class="fa fa-users"></i> Users</a>
            <span>/</span>
            <span style="color: #1e293b; font-weight: 600;">Create New User</span>
        </div>
    </div>
    
    <?php if ($error): ?>
    <div class="alert-modern">
        <i class="fa fa-exclamation-circle"></i>
        <span><?php echo htmlspecialchars($error); ?></span>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
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
                    
                    <div class="form-section-title" style="margin-top: 35px;">
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
                    
                    <div class="form-section-title" style="margin-top: 35px;">
                        <i class="fa fa-shield"></i> Permissions
                    </div>
                    
                    <div class="form-group-modern">
                        <label for="role">
                            Role <span class="required">*</span>
                        </label>
                        <select class="form-control-modern" id="role" name="role" required>
                            <option value="user">Member</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Administrator</option>
                        </select>
                        <span class="form-hint">
                            <i class="fa fa-info-circle"></i>
                            Select the appropriate access level for this user
                        </span>
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
    </div>
</div>

<script>
$(document).ready(function() {
    // PASSWORD STRENGTH INDICATOR
    $('#password').on('input', function() {
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
    });
    
    // INPUT FOCUS EFFECTS
    $('.form-control-modern').on('focus', function() {
        $(this).closest('.form-group-modern').find('label').css({
            'color': '#667eea',
            'transition': 'color 0.3s ease'
        });
    }).on('blur', function() {
        $(this).closest('.form-group-modern').find('label').css({
            'color': '#1e293b'
        });
    });
    
    // FORM VALIDATION
    $('#createUserForm').on('submit', function(e) {
        let isValid = true;
        
        $('.form-control-modern[required]').each(function() {
            if ($(this).val().trim() === '') {
                isValid = false;
                $(this).css('border-color', '#ef4444');
                $(this).on('input', function() {
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

<?php require_once '../includes/footer.php'; ?>