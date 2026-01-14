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
        --radius: 16px;
        --radius-sm: 10px;
    }
    
    .profile-container {
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
        padding: 40px;
        border-radius: var(--radius);
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
    
    /* ALERTS */
    .alert-modern {
        border-radius: var(--radius-sm);
        padding: 16px 20px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
        font-size: 14px;
        border: none;
    }
    
    .alert-modern.success {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.08), rgba(5, 150, 105, 0.08));
        border-left: 4px solid var(--success);
        color: #065f46;
    }
    
    .alert-modern.error {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.08), rgba(220, 38, 38, 0.08));
        border-left: 4px solid var(--danger);
        color: #991b1b;
    }
    
    .alert-modern i {
        font-size: 18px;
    }
    
    /* FORM CARD */
    .form-card {
        background: white;
        border-radius: var(--radius);
        padding: 32px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        margin-bottom: 24px;
    }
    
    /* STATS CARD */
    .stats-card {
        background: white;
        border-radius: var(--radius);
        padding: 32px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        position: sticky;
        top: 20px;
    }
    
    /* FORM SECTIONS */
    .form-section-title {
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
    
    .form-section-title i {
        color: var(--primary);
        font-size: 14px;
    }
    
    /* FORM GROUPS */
    .form-group-modern {
        margin-bottom: 20px;
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
        border-radius: var(--radius-sm);
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
    
    .form-control-modern:hover:not(:disabled) {
        border-color: #cbd5e1;
    }
    
    .form-control-modern:disabled {
        background: #f1f5f9;
        cursor: not-allowed;
        color: #94a3b8;
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
    
    /* FORM HINT */
    .form-hint {
        display: block;
        margin-top: 6px;
        font-size: 12px;
        color: #64748b;
        font-weight: 500;
    }
    
    .form-hint i {
        margin-right: 4px;
        color: #94a3b8;
    }
    
    /* FORM ACTIONS */
    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 2px solid var(--border);
    }
    
    /* BUTTONS */
    .btn-modern {
        padding: 12px 28px;
        border-radius: var(--radius-sm);
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
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
    }
    
    .btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(99, 102, 241, 0.35);
    }
    
    /* PROFILE AVATAR SECTION */
    .profile-avatar-section {
        text-align: center;
        padding: 24px;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.05));
        border-radius: var(--radius-sm);
        margin-bottom: 24px;
    }
    
    .profile-avatar-large {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 40px;
        box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3);
        margin-bottom: 16px;
        border: 4px solid white;
    }
    
    .profile-name {
        font-size: 20px;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 8px;
    }
    
    /* ROLE BADGE */
    .role-badge-large {
        display: inline-flex;
        align-items: center;
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .role-badge-large.admin {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .role-badge-large.manager {
        background: #fef3c7;
        color: #92400e;
    }
    
    .role-badge-large.user {
        background: #e0e7ff;
        color: #3730a3;
    }
    
    /* STATS GRID */
    .stats-grid {
        display: grid;
        gap: 12px;
        margin-bottom: 24px;
    }
    
    .stat-item {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.05));
        padding: 16px;
        border-radius: var(--radius-sm);
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: 2px solid transparent;
        transition: all 0.2s ease;
    }
    
    .stat-item:hover {
        border-color: var(--primary);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }
    
    .stat-label {
        color: #64748b;
        font-weight: 600;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .stat-label i {
        color: var(--primary);
        font-size: 14px;
    }
    
    .stat-value {
        font-size: 24px;
        font-weight: 800;
        color: var(--primary);
    }
    
    /* COMPLETION BAR */
    .completion-bar-wrapper {
        margin-bottom: 24px;
    }
    
    .completion-bar-label {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 12px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .completion-bar-track {
        height: 10px;
        background: var(--border);
        border-radius: 10px;
        overflow: hidden;
    }
    
    .completion-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
        border-radius: 10px;
        transition: width 1.5s ease;
    }
    
    /* MEMBER SINCE */
    .member-since {
        padding: 16px;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.05));
        border-radius: var(--radius-sm);
        text-align: center;
    }
    
    .member-since-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #64748b;
        margin-bottom: 4px;
    }
    
    .member-since-date {
        font-size: 15px;
        font-weight: 700;
        color: var(--dark);
    }
    
    /* PASSWORD SECTION */
    .password-section {
        margin-top: 32px;
        padding-top: 32px;
        border-top: 2px solid var(--border);
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
    
    /* PASSWORD MATCH INDICATOR */
    .password-match-icon {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        display: none;
        font-size: 16px;
    }
    
    .password-match-icon.match {
        color: var(--success);
        display: block;
    }
    
    .password-match-icon.mismatch {
        color: var(--danger);
        display: block;
    }
    
    /* RESPONSIVE DESIGN */
    @media (max-width: 1200px) {
        .profile-container {
            padding: 20px;
        }
        .page-header {
            padding: 32px;
        }
    }
    
    @media (max-width: 992px) {
        .stats-card {
            position: static;
            margin-top: 24px;
        }
    }
    
    @media (max-width: 768px) {
        .profile-container {
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
        .form-card, .stats-card {
            padding: 24px;
        }
        .profile-avatar-large {
            width: 80px;
            height: 80px;
            font-size: 32px;
        }
        .profile-name {
            font-size: 18px;
        }
    }
    
    @media (max-width: 480px) {
        .profile-container {
            padding: 12px;
        }
        .page-header {
            padding: 20px;
        }
        .page-header h1 {
            font-size: 20px;
        }
        .form-card, .stats-card {
            padding: 20px;
        }
        .form-control-modern {
            padding: 12px 14px;
            font-size: 14px;
        }
        .input-icon-wrapper .form-control-modern {
            padding-left: 38px;
        }
        .btn-modern {
            width: 100%;
            justify-content: center;
        }
        .form-actions {
            flex-direction: column;
        }
    }
</style>

<div class="profile-container container-fluid">
    <div class="page-header">
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
                                        <i class="fa fa-check-circle password-match-icon" id="matchIcon"></i>
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
                        <?php 
                        $initials = '';
                        $parts = explode(' ', $user['full_name']);
                        foreach ($parts as $part) {
                            $initials .= strtoupper(substr($part, 0, 1));
                            if (strlen($initials) >= 2) break;
                        }
                        echo $initials;
                        ?>
                    </div>
                    <div class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                    <span class="role-badge-large <?php echo $user['role']; ?>">
                        <?php echo ucfirst($user['role']); ?>
                    </span>
                </div>
                
                <div class="form-section-title">
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
// OPTIMIZED: Pure JavaScript implementation
function initCounters() {
    const counters = document.querySelectorAll('.counter');
    const duration = 1500;
    
    counters.forEach((counter) => {
        const target = parseInt(counter.getAttribute('data-target'));
        const increment = target / (duration / 16);
        let current = 0;
        
        const updateCounter = () => {
            current += increment;
            if (current < target) {
                counter.textContent = Math.floor(current);
                requestAnimationFrame(updateCounter);
            } else {
                counter.textContent = target;
            }
        };
        updateCounter();
    });
}

// COMPLETION RATE ANIMATION
function animateCompletionRate() {
    const completionRate = <?php echo $completion_rate; ?>;
    const bar = document.getElementById('completionBar');
    const rateDisplay = document.getElementById('completionRate');
    
    setTimeout(() => {
        bar.style.width = completionRate + '%';
        
        let current = 0;
        const increment = completionRate / (1500 / 16);
        
        const updateRate = () => {
            current += increment;
            if (current < completionRate) {
                rateDisplay.textContent = Math.floor(current) + '%';
                requestAnimationFrame(updateRate);
            } else {
                rateDisplay.textContent = completionRate + '%';
            }
        };
        updateRate();
    }, 500);
}

// PASSWORD STRENGTH INDICATOR
function initPasswordStrength() {
    const passwordInput = document.getElementById('new_password');
    const strengthBar = document.getElementById('passwordStrengthBar');
    const strengthContainer = document.getElementById('passwordStrength');
    
    passwordInput.addEventListener('input', function() {
        const val = this.value;
        
        if (val.length === 0) {
            strengthContainer.style.display = 'none';
            strengthBar.className = 'password-strength-bar';
            return;
        }
        
        strengthContainer.style.display = 'block';
        
        let strength = 0;
        if (val.length >= 6) strength++;
        if (val.length >= 10) strength++;
        if (/[A-Z]/.test(val) && /[a-z]/.test(val)) strength++;
        if (/[0-9]/.test(val)) strength++;
        if (/[^A-Za-z0-9]/.test(val)) strength++;
        
        strengthBar.className = 'password-strength-bar';
        
        if (strength <= 2) {
            strengthBar.classList.add('weak');
        } else if (strength <= 4) {
            strengthBar.classList.add('medium');
        } else {
            strengthBar.classList.add('strong');
        }
    });
}

// PASSWORD MATCH VALIDATION
function initPasswordMatch() {
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const matchIcon = document.getElementById('matchIcon');
    
    confirmPassword.addEventListener('input', function() {
        const newPass = newPassword.value;
        const confirmPass = this.value;
        
        if (confirmPass.length === 0) {
            this.style.borderColor = '#e2e8f0';
            matchIcon.className = 'fa password-match-icon';
            return;
        }
        
        if (newPass === confirmPass) {
            this.style.borderColor = '#10b981';
            matchIcon.className = 'fa fa-check-circle password-match-icon match';
        } else {
            this.style.borderColor = '#ef4444';
            matchIcon.className = 'fa fa-times-circle password-match-icon mismatch';
        }
    });
}

// INPUT FOCUS EFFECTS
function initFocusEffects() {
    const inputs = document.querySelectorAll('.form-control-modern');
    
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            const label = this.closest('.form-group-modern')?.querySelector('label');
            if (label) {
                label.style.color = '#6366f1';
                label.style.transition = 'color 0.3s ease';
            }
        });
        
        input.addEventListener('blur', function() {
            const label = this.closest('.form-group-modern')?.querySelector('label');
            if (label) {
                label.style.color = '#64748b';
            }
        });
    });
}

// FORM VALIDATION
function initFormValidation() {
    const form = document.getElementById('profileForm');
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    form.addEventListener('submit', function(e) {
        const newPass = newPassword.value;
        const confirmPass = confirmPassword.value;
        
        if (newPass && newPass !== confirmPass) {
            e.preventDefault();
            alert('Passwords do not match. Please check and try again.');
            confirmPassword.style.borderColor = '#ef4444';
            confirmPassword.focus();
            return false;
        }
        
        if (newPass && newPass.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters long.');
            newPassword.style.borderColor = '#ef4444';
            newPassword.focus();
            return false;
        }
    });
}

// Initialize all functionality
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initCounters();
        animateCompletionRate();
        initPasswordStrength();
        initPasswordMatch();
        initFocusEffects();
        initFormValidation();
    });
} else {
    initCounters();
    animateCompletionRate();
    initPasswordStrength();
    initPasswordMatch();
    initFocusEffects();
    initFormValidation();
}
</script>

<?php require_once 'includes/footer.php'; ?>