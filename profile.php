<?php
$page_title = 'My Profile';
require_once 'includes/header.php';
require_once 'components/user.php';

$auth->checkAccess();

$user_obj = new User();
$user = $user_obj->getById($auth->getUserId());

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => $_POST['username'],
        'email' => $_POST['email'],
        'full_name' => $_POST['full_name'],
        'role' => $user['role'],
        'status' => $user['status']
    ];
    
    if (!empty($_POST['new_password'])) {
        if ($_POST['new_password'] === $_POST['confirm_password']) {
            $data['password'] = $_POST['new_password'];
        } else {
            $error = 'Passwords do not match';
        }
    }
    
    if (!$error) {
        if ($user_obj->update($auth->getUserId(), $data)) {
            $_SESSION['full_name'] = $data['full_name'];
            $_SESSION['username'] = $data['username'];
            $_SESSION['email'] = $data['email'];
            $success = 'Profile updated successfully';
            $user = $user_obj->getById($auth->getUserId());
        } else {
            $error = 'Failed to update profile';
        }
    }
}

$db = getDB();
$user_stats = [
    'projects' => $db->query("SELECT COUNT(DISTINCT project_id) as count FROM project_members WHERE user_id = " . $auth->getUserId())->fetch_assoc()['count'],
    'tasks' => $db->query("SELECT COUNT(*) as count FROM tasks WHERE assigned_to = " . $auth->getUserId())->fetch_assoc()['count'],
    'completed_tasks' => $db->query("SELECT COUNT(*) as count FROM tasks WHERE assigned_to = " . $auth->getUserId() . " AND status = 'completed'")->fetch_assoc()['count']
];

$completion_rate = $user_stats['tasks'] > 0 ? round(($user_stats['completed_tasks'] / $user_stats['tasks']) * 100) : 0;
?>

<style>
    .profile-container {
        background: transparent !important;
        min-height: calc(100vh - 100px) !important;
        padding: 20px !important;
        margin: 0 !important;
    }
    
    .profile-header {
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
    
    .profile-header::before {
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
    
    .profile-header h1 {
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
    
    .profile-header h1 i {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }
    
    .alert-modern {
        border-radius: 12px !important;
        padding: 15px 20px !important;
        margin-bottom: 25px !important;
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        font-weight: 600 !important;
        border: none !important;
    }
    
    .alert-modern.success {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%) !important;
        border-left: 4px solid #10b981 !important;
        color: #065f46 !important;
    }
    
    .alert-modern.error {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%) !important;
        border-left: 4px solid #ef4444 !important;
        color: #991b1b !important;
    }
    
    .alert-modern i {
        font-size: 20px !important;
    }
    
    .form-card {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        border-radius: 20px !important;
        padding: 35px !important;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        margin-bottom: 25px !important;
    }
    
    .stats-card {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        border-radius: 20px !important;
        padding: 35px !important;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        position: sticky !important;
        top: 20px !important;
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
    
    .form-control-modern:hover:not(:disabled) {
        border-color: #cbd5e1 !important;
    }
    
    .form-control-modern:disabled {
        background: #f1f5f9 !important;
        cursor: not-allowed !important;
    }
    
    .form-hint {
        display: block !important;
        margin-top: 8px !important;
        font-size: 13px !important;
        color: #64748b !important;
        font-weight: 500 !important;
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3) !important;
    }
    
    .btn-modern:hover {
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4) !important;
    }
    
    .profile-avatar-section {
        text-align: center !important;
        padding: 25px !important;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%) !important;
        border-radius: 16px !important;
        margin-bottom: 25px !important;
    }
    
    .profile-avatar-large {
        width: 100px !important;
        height: 100px !important;
        border-radius: 50% !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-weight: 800 !important;
        font-size: 40px !important;
        box-shadow: 0 10px 40px rgba(102, 126, 234, 0.4) !important;
        margin-bottom: 15px !important;
        border: 4px solid white !important;
    }
    
    .profile-name {
        font-size: 22px !important;
        font-weight: 700 !important;
        color: #1e293b !important;
        margin-bottom: 8px !important;
    }
    
    .role-badge-large {
        display: inline-flex !important;
        align-items: center !important;
        padding: 8px 20px !important;
        border-radius: 20px !important;
        font-size: 12px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }
    
    .role-badge-large.admin {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(220, 38, 38, 0.15) 100%) !important;
        color: #dc2626 !important;
    }
    
    .role-badge-large.manager {
        background: linear-gradient(135deg, rgba(251, 191, 36, 0.15) 0%, rgba(245, 158, 11, 0.15) 100%) !important;
        color: #f59e0b !important;
    }
    
    .role-badge-large.user {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.15) 0%, rgba(118, 75, 162, 0.15) 100%) !important;
        color: #667eea !important;
    }
    
    .stats-grid {
        display: grid !important;
        gap: 15px !important;
        margin-bottom: 25px !important;
    }
    
    .stat-item {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%) !important;
        padding: 20px !important;
        border-radius: 12px !important;
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        border: 2px solid transparent !important;
        transition: all 0.3s ease !important;
    }
    
    .stat-item:hover {
        border-color: #667eea !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.15) !important;
    }
    
    .stat-label {
        color: #64748b !important;
        font-weight: 600 !important;
        font-size: 14px !important;
    }
    
    .stat-value {
        font-size: 24px !important;
        font-weight: 800 !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }
    
    .completion-bar-wrapper {
        margin-bottom: 25px !important;
    }
    
    .completion-bar-label {
        display: flex !important;
        justify-content: space-between !important;
        margin-bottom: 8px !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        color: #64748b !important;
    }
    
    .completion-bar-track {
        height: 10px !important;
        background: #e2e8f0 !important;
        border-radius: 10px !important;
        overflow: hidden !important;
    }
    
    .completion-bar-fill {
        height: 100% !important;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%) !important;
        border-radius: 10px !important;
        transition: width 1s ease !important;
    }
    
    .member-since {
        padding: 20px !important;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%) !important;
        border-radius: 12px !important;
        text-align: center !important;
    }
    
    .member-since-label {
        font-size: 12px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        color: #64748b !important;
        margin-bottom: 5px !important;
    }
    
    .member-since-date {
        font-size: 16px !important;
        font-weight: 700 !important;
        color: #1e293b !important;
    }
    
    .password-section {
        margin-top: 35px !important;
        padding-top: 35px !important;
        border-top: 2px solid #e2e8f0 !important;
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
    
    @media (max-width: 1200px) {
        .profile-container {
            padding: 15px !important;
        }
        .profile-header, .form-card, .stats-card {
            padding: 25px 30px !important;
        }
    }
    
    @media (max-width: 992px) {
        .stats-card {
            position: static !important;
            margin-top: 25px !important;
        }
    }
    
    @media (max-width: 768px) {
        .profile-container {
            padding: 10px !important;
        }
        .profile-header {
            padding: 20px !important;
            margin-bottom: 20px !important;
        }
        .profile-header h1 {
            font-size: 24px !important;
        }
        .form-card, .stats-card {
            padding: 20px !important;
        }
        .profile-avatar-large {
            width: 80px !important;
            height: 80px !important;
            font-size: 32px !important;
        }
        .profile-name {
            font-size: 20px !important;
        }
    }
    
    @media (max-width: 480px) {
        .profile-container {
            padding: 8px !important;
        }
        .profile-header h1 {
            font-size: 20px !important;
        }
        .form-card, .stats-card {
            padding: 15px !important;
        }
        .form-control-modern {
            padding: 12px 16px !important;
            font-size: 14px !important;
        }
        .input-icon-wrapper .form-control-modern {
            padding-left: 40px !important;
        }
        .btn-modern {
            width: 100% !important;
            justify-content: center !important;
        }
    }
</style>

<div class="profile-container container-fluid">
    <div class="profile-header">
        <h1>
            <i class="fa fa-user-circle"></i> My Profile
        </h1>
    </div>
    
    <?php if ($success): ?>
    <div class="alert-modern success">
        <i class="fa fa-check-circle"></i>
        <span><?php echo htmlspecialchars($success); ?></span>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert-modern error">
        <i class="fa fa-exclamation-circle"></i>
        <span><?php echo htmlspecialchars($error); ?></span>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="form-card">
                <form method="POST" action="" id="profileForm">
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
                                   required>
                        </div>
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
                                           value="<?php echo htmlspecialchars($user['username']); ?>" 
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
                                           value="<?php echo htmlspecialchars($user['email']); ?>" 
                                           required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group-modern">
                        <label for="role_display">Role</label>
                        <div class="input-icon-wrapper">
                            <i class="fa fa-shield"></i>
                            <input type="text" 
                                   class="form-control-modern" 
                                   id="role_display" 
                                   value="<?php echo ucfirst($user['role']); ?>" 
                                   disabled>
                        </div>
                        <span class="form-hint">
                            <i class="fa fa-info-circle"></i>
                            Contact an administrator to change your role
                        </span>
                    </div>
                    
                    <div class="password-section">
                        <div class="form-section-title">
                            <i class="fa fa-lock"></i> Change Password
                        </div>
                        
                        <span class="form-hint" style="display: block; margin-bottom: 20px;">
                            <i class="fa fa-info-circle"></i>
                            Leave blank to keep your current password
                        </span>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label for="new_password">New Password</label>
                                    <div class="input-icon-wrapper">
                                        <i class="fa fa-key"></i>
                                        <input type="password" 
                                               class="form-control-modern" 
                                               id="new_password" 
                                               name="new_password" 
                                               minlength="6">
                                    </div>
                                    <div class="password-strength" id="passwordStrength">
                                        <div class="password-strength-bar" id="passwordStrengthBar"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label for="confirm_password">Confirm Password</label>
                                    <div class="input-icon-wrapper">
                                        <i class="fa fa-key"></i>
                                        <input type="password" 
                                               class="form-control-modern" 
                                               id="confirm_password" 
                                               name="confirm_password">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-modern">
                            <i class="fa fa-save"></i> Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stats-card">
                <div class="profile-avatar-section">
                    <div class="profile-avatar-large">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                    <div class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                    <span class="role-badge-large <?php echo $user['role']; ?>">
                        <?php echo ucfirst($user['role']); ?>
                    </span>
                </div>
                
                <div class="form-section-title" style="margin-bottom: 20px;">
                    <i class="fa fa-chart-bar"></i> Statistics
                </div>
                
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-label">
                            <i class="fa fa-project-diagram"></i> Projects
                        </div>
                        <div class="stat-value counter" data-target="<?php echo $user_stats['projects']; ?>">0</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">
                            <i class="fa fa-tasks"></i> Total Tasks
                        </div>
                        <div class="stat-value counter" data-target="<?php echo $user_stats['tasks']; ?>">0</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">
                            <i class="fa fa-check-circle"></i> Completed
                        </div>
                        <div class="stat-value counter" data-target="<?php echo $user_stats['completed_tasks']; ?>">0</div>
                    </div>
                </div>
                
                <div class="completion-bar-wrapper">
                    <div class="completion-bar-label">
                        <span>Completion Rate</span>
                        <span id="completionRate">0%</span>
                    </div>
                    <div class="completion-bar-track">
                        <div class="completion-bar-fill" id="completionBar" style="width: 0%;"></div>
                    </div>
                </div>
                
                <div class="member-since">
                    <div class="member-since-label">Member Since</div>
                    <div class="member-since-date"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // ANIMATED COUNTERS
    $('.counter').each(function() {
        const $this = $(this);
        const countTo = parseInt($this.attr('data-target'));
        
        $({ countNum: 0 }).animate({
            countNum: countTo
        }, {
            duration: 1500,
            easing: 'swing',
            step: function() {
                $this.text(Math.floor(this.countNum));
            },
            complete: function() {
                $this.text(this.countNum);
            }
        });
    });
    
    // COMPLETION RATE ANIMATION
    const completionRate = <?php echo $completion_rate; ?>;
    setTimeout(function() {
        $('#completionBar').css('width', completionRate + '%');
        $({ rate: 0 }).animate({
            rate: completionRate
        }, {
            duration: 1500,
            easing: 'swing',
            step: function() {
                $('#completionRate').text(Math.floor(this.rate) + '%');
            },
            complete: function() {
                $('#completionRate').text(completionRate + '%');
            }
        });
    }, 500);
    
    // PASSWORD STRENGTH INDICATOR
    $('#new_password').on('input', function() {
        const val = $(this).val();
        const $strength = $('#passwordStrength');
        const $bar = $('#passwordStrengthBar');
        
        if (val.length === 0) {
            $strength.hide();
            $bar.removeClass('weak medium strong');
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
        
        if (strength <= 2) {
            $bar.addClass('weak');
        } else if (strength <= 4) {
            $bar.addClass('medium');
        } else {
            $bar.addClass('strong');
        }
    });
    
    // PASSWORD MATCH VALIDATION
    $('#confirm_password').on('input', function() {
        const newPass = $('#new_password').val();
        const confirmPass = $(this).val();
        
        if (confirmPass.length === 0) {
            $(this).css('border-color', '#e2e8f0');
            return;
        }
        
        if (newPass === confirmPass) {
            $(this).css('border-color', '#10b981');
        } else {
            $(this).css('border-color', '#ef4444');
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
    $('#profileForm').on('submit', function(e) {
        const newPass = $('#new_password').val();
        const confirmPass = $('#confirm_password').val();
        
        if (newPass && newPass !== confirmPass) {
            e.preventDefault();
            alert('Passwords do not match. Please check and try again.');
            $('#confirm_password').css('border-color', '#ef4444').focus();
            return false;
        }
        
        if (newPass && newPass.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters long.');
            $('#new_password').css('border-color', '#ef4444').focus();
            return false;
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>