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
    .user-edit-container {
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
    
    .user-edit-header {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        color: #1e293b !important;
        padding: 35px 40px !important;
        border-radius: 20px !important;
        margin-bottom: 35px !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        animation: slideDown 0.6s ease !important;
        position: relative !important;
        overflow: hidden !important;
    }
    
    .user-edit-header::before {
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
    
    .user-edit-header h1 {
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
    
    .user-edit-header h1 i {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }
    
    .user-edit-breadcrumb {
        margin-top: 15px !important;
        position: relative !important;
        z-index: 1 !important;
    }
    
    .user-edit-breadcrumb a {
        color: #667eea !important;
        text-decoration: none !important;
        font-weight: 600 !important;
        transition: color 0.3s ease !important;
    }
    
    .user-edit-breadcrumb a:hover {
        color: #764ba2 !important;
    }
    
    .user-edit-breadcrumb span {
        color: #64748b !important;
        margin: 0 8px !important;
    }
    
    .form-card {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        border-radius: 20px !important;
        padding: 40px !important;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        animation: slideUp 0.5s ease !important;
        margin-bottom: 25px !important;
    }
    
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .user-avatar-section {
        text-align: center !important;
        padding: 30px !important;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%) !important;
        border-radius: 16px !important;
        margin-bottom: 30px !important;
    }
    
    .user-avatar-large {
        width: 120px !important;
        height: 120px !important;
        border-radius: 50% !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-weight: 800 !important;
        font-size: 48px !important;
        box-shadow: 0 10px 40px rgba(102, 126, 234, 0.4) !important;
        margin-bottom: 15px !important;
    }
    
    .user-avatar-name {
        font-size: 24px !important;
        font-weight: 700 !important;
        color: #1e293b !important;
        margin-bottom: 5px !important;
    }
    
    .user-avatar-role {
        font-size: 14px !important;
        color: #64748b !important;
        font-weight: 600 !important;
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
    
    /* RESPONSIVE */
    @media (max-width: 1200px) {
        .user-edit-container {
            padding: 15px !important;
        }
        .user-edit-header {
            padding: 25px 30px !important;
        }
        .form-card {
            padding: 30px !important;
        }
    }
    
    @media (max-width: 768px) {
        .user-edit-container {
            padding: 10px !important;
        }
        .user-edit-header {
            padding: 20px !important;
            margin-bottom: 20px !important;
        }
        .user-edit-header h1 {
            font-size: 24px !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 10px !important;
        }
        .form-card {
            padding: 20px !important;
        }
        .user-avatar-section {
            padding: 20px !important;
        }
        .user-avatar-large {
            width: 90px !important;
            height: 90px !important;
            font-size: 36px !important;
        }
        .user-avatar-name {
            font-size: 20px !important;
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
        .user-edit-container {
            padding: 8px !important;
        }
        .user-edit-header h1 {
            font-size: 20px !important;
        }
        .form-card {
            padding: 15px !important;
        }
        .user-avatar-large {
            width: 80px !important;
            height: 80px !important;
            font-size: 32px !important;
        }
        .form-control-modern {
            padding: 12px 16px !important;
            font-size: 14px !important;
        }
    }
</style>

<div class="user-edit-container container-fluid">
    <div class="user-edit-header">
        <h1>
            <i class="fa fa-edit"></i> Edit User
        </h1>
        <div class="user-edit-breadcrumb">
            <a href="users.php"><i class="fa fa-users"></i> Users</a>
            <span>/</span>
            <span style="color: #1e293b; font-weight: 600;">Edit User</span>
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
                        <?php echo ucfirst($user['role']); ?> • 
                        <span style="color: <?php echo $user['status'] === 'active' ? '#059669' : '#94a3b8'; ?>;">
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                    </div>
                </div>
                
                <form method="POST" action="">
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
                                   required>
                        </div>
                    </div>
                    
                    <!-- ACCOUNT DETAILS -->
                    <div class="form-section-title" style="margin-top: 40px;">
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
                                   minlength="6">
                        </div>
                        <span class="form-hint">
                            <i class="fa fa-info-circle"></i>
                            Leave blank to keep current password. Minimum 6 characters if changing.
                        </span>
                    </div>
                    
                    <!-- PERMISSIONS -->
                    <div class="form-section-title" style="margin-top: 40px;">
                        <i class="fa fa-shield"></i> Permissions & Status
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
    // FORM ANIMATION
    $('.form-card').css({
        'animation': 'slideUp 0.5s ease both'
    });
    
    // INPUT FOCUS EFFECTS
    $('.form-control-modern').on('focus', function() {
        $(this).closest('.form-group-modern').find('label').css({
            'color': '#667eea',
            'transform': 'scale(1.05)',
            'transition': 'all 0.3s ease'
        });
    }).on('blur', function() {
        $(this).closest('.form-group-modern').find('label').css({
            'color': '#1e293b',
            'transform': 'scale(1)'
        });
    });
    
    // FORM VALIDATION ENHANCEMENT
    $('form').on('submit', function(e) {
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
    
    // PASSWORD STRENGTH INDICATOR (optional enhancement)
    $('#password').on('input', function() {
        const val = $(this).val();
        if (val.length > 0 && val.length < 6) {
            $(this).css('border-color', '#ef4444');
        } else if (val.length >= 6) {
            $(this).css('border-color', '#10b981');
        } else {
            $(this).css('border-color', '#e2e8f0');
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>