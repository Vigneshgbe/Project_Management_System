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
    /* OPTIMIZED CSS - REDUCED REDUNDANCY & IMPROVED PERFORMANCE */
    
    :root {
        --primary: #6366f1;
        --primary-dark: #4f46e5;
        --secondary: #8b5cf6;
        --danger: #ef4444;
        --dark: #1e293b;
        --light: #f8fafc;
        --border: #e2e8f0;
        --text-muted: #64748b;
        --shadow: 0 5px 25px rgba(0, 0, 0, 0.15);
        --shadow-lg: 0 10px 40px rgba(0, 0, 0, 0.2);
    }
    
    /* PERFORMANCE OPTIMIZATIONS */
    * {
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }
    
    /* CONTAINER */
    .user-create-container {
        min-height: calc(100vh - 100px);
        padding: 24px;
        max-width: 1200px;
        margin: 0 auto;
        animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* PAGE HEADER */
    .page-header-create {
        background: white;
        padding: 32px;
        border-radius: 16px;
        margin-bottom: 32px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        position: relative;
        overflow: hidden;
    }
    
    .page-header-create::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
    }
    
    .page-header-create h1 {
        margin: 0 0 12px 0;
        font-weight: 700;
        font-size: 32px;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .page-header-create h1 i {
        color: var(--primary);
        font-size: 28px;
    }
    
    .breadcrumb-nav {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 600;
    }
    
    .breadcrumb-nav a {
        color: var(--primary);
        text-decoration: none;
        transition: color 0.2s;
    }
    
    .breadcrumb-nav a:hover {
        color: var(--primary-dark);
    }
    
    .breadcrumb-nav span {
        color: var(--text-muted);
    }
    
    .breadcrumb-nav .current {
        color: var(--dark);
    }
    
    /* ALERT */
    .alert-error {
        background: rgba(239, 68, 68, 0.1);
        border-left: 4px solid var(--danger);
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #991b1b;
        font-weight: 600;
    }
    
    .alert-error i {
        font-size: 20px;
        color: var(--danger);
    }
    
    /* FORM CARD */
    .form-card {
        background: white;
        border-radius: 16px;
        padding: 32px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
    }
    
    /* SECTION TITLE */
    .section-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--dark);
        margin: 0 0 24px 0;
        padding-bottom: 12px;
        border-bottom: 3px solid;
        border-image: linear-gradient(90deg, var(--primary), var(--secondary)) 1;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .section-title:not(:first-child) {
        margin-top: 32px;
    }
    
    .section-title i {
        color: var(--primary);
    }
    
    /* FORM GROUP */
    .form-group {
        margin-bottom: 24px;
    }
    
    .form-group label {
        display: block;
        font-weight: 700;
        font-size: 13px;
        color: var(--dark);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: color 0.2s;
    }
    
    .form-group label .required {
        color: var(--danger);
        margin-left: 4px;
    }
    
    /* INPUT WRAPPER */
    .input-wrapper {
        position: relative;
    }
    
    .input-wrapper i {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--primary);
        font-size: 16px;
        pointer-events: none;
    }
    
    .input-wrapper .form-control {
        padding-left: 44px;
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
        transition: all 0.2s;
    }
    
    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    
    .form-control:hover {
        border-color: #cbd5e1;
    }
    
    .form-control::placeholder {
        color: #94a3b8;
    }
    
    select.form-control {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236366f1' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 16px center;
        padding-right: 40px;
        cursor: pointer;
    }
    
    /* FORM HINT */
    .form-hint {
        display: block;
        margin-top: 6px;
        font-size: 13px;
        color: var(--text-muted);
        font-weight: 500;
    }
    
    .form-hint i {
        margin-right: 4px;
        font-size: 12px;
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
        transition: all 0.3s;
        border-radius: 4px;
    }
    
    .password-strength-bar.weak { background: var(--danger); width: 33%; }
    .password-strength-bar.medium { background: #f59e0b; width: 66%; }
    .password-strength-bar.strong { background: #10b981; width: 100%; }
    
    .form-hint.weak { color: var(--danger); }
    .form-hint.medium { color: #f59e0b; }
    .form-hint.strong { color: #10b981; }
    
    /* FORM ACTIONS */
    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 2px solid var(--border);
    }
    
    /* BUTTONS */
    .btn {
        padding: 14px 28px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.2s;
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
    }
    
    .btn-secondary {
        background: white;
        color: var(--primary);
        border: 2px solid var(--primary);
    }
    
    .btn-secondary:hover {
        background: rgba(99, 102, 241, 0.05);
        transform: translateY(-2px);
    }
    
    /* RESPONSIVE */
    @media (max-width: 1200px) {
        .user-create-container { padding: 20px; }
        .page-header-create { padding: 28px; }
        .form-card { padding: 28px; }
    }
    
    @media (max-width: 768px) {
        .user-create-container { padding: 16px; }
        .page-header-create {
            padding: 24px;
            margin-bottom: 24px;
        }
        .page-header-create h1 {
            font-size: 26px;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        .form-card { padding: 24px; }
        .form-actions {
            flex-direction: column;
        }
        .btn {
            width: 100%;
            justify-content: center;
        }
    }
    
    @media (max-width: 480px) {
        .user-create-container { padding: 12px; }
        .page-header-create {
            padding: 20px;
        }
        .page-header-create h1 {
            font-size: 22px;
        }
        .form-card { padding: 20px; }
        .form-control {
            padding: 12px 14px;
            font-size: 14px;
        }
        .input-wrapper .form-control {
            padding-left: 40px;
        }
    }
</style>

<div class="user-create-container">
    <!-- PAGE HEADER -->
    <div class="page-header-create">
        <h1>
            <i class="fa fa-plus-circle"></i> Create User
        </h1>
        <div class="breadcrumb-nav">
            <a href="users.php"><i class="fa fa-users"></i> Users</a>
            <span>/</span>
            <span class="current">Create New User</span>
        </div>
    </div>
    
    <!-- ERROR ALERT -->
    <?php if ($error): ?>
    <div class="alert-error">
        <i class="fa fa-exclamation-circle"></i>
        <span><?php echo htmlspecialchars($error); ?></span>
    </div>
    <?php endif; ?>
    
    <!-- FORM CARD -->
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="form-card">
                <form method="POST" action="" id="createUserForm">
                    <!-- PERSONAL INFORMATION -->
                    <div class="section-title">
                        <i class="fa fa-user"></i> Personal Information
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">
                            Full Name <span class="required">*</span>
                        </label>
                        <div class="input-wrapper">
                            <i class="fa fa-user"></i>
                            <input type="text" 
                                   class="form-control" 
                                   id="full_name" 
                                   name="full_name" 
                                   placeholder="Enter full name"
                                   required>
                        </div>
                    </div>
                    
                    <!-- ACCOUNT DETAILS -->
                    <div class="section-title">
                        <i class="fa fa-lock"></i> Account Details
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="username">
                                    Username <span class="required">*</span>
                                </label>
                                <div class="input-wrapper">
                                    <i class="fa fa-at"></i>
                                    <input type="text" 
                                           class="form-control" 
                                           id="username" 
                                           name="username" 
                                           placeholder="Enter username"
                                           required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">
                                    Email Address <span class="required">*</span>
                                </label>
                                <div class="input-wrapper">
                                    <i class="fa fa-envelope"></i>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email" 
                                           placeholder="Enter email address"
                                           required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">
                            Password <span class="required">*</span>
                        </label>
                        <div class="input-wrapper">
                            <i class="fa fa-key"></i>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Enter password"
                                   required 
                                   minlength="6">
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                        <span class="form-hint" id="passwordHint">
                            <i class="fa fa-info-circle"></i>
                            Minimum 6 characters required
                        </span>
                    </div>
                    
                    <!-- PERMISSIONS -->
                    <div class="section-title">
                        <i class="fa fa-shield"></i> Permissions
                    </div>
                    
                    <div class="form-group">
                        <label for="role">
                            Role <span class="required">*</span>
                        </label>
                        <select class="form-control" id="role" name="role" required>
                            <option value="user">Member</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Administrator</option>
                        </select>
                        <span class="form-hint">
                            <i class="fa fa-info-circle"></i>
                            Select the appropriate access level for this user
                        </span>
                    </div>
                    
                    <!-- ACTIONS -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Create User
                        </button>
                        <a href="users.php" class="btn btn-secondary">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// OPTIMIZED JAVASCRIPT - MINIMAL DOM MANIPULATION
(function() {
    'use strict';
    
    const pwd = document.getElementById('password');
    const strength = document.getElementById('passwordStrength');
    const bar = document.getElementById('strengthBar');
    const hint = document.getElementById('passwordHint');
    
    // PASSWORD STRENGTH (DEBOUNCED)
    let timeout;
    pwd.addEventListener('input', function() {
        clearTimeout(timeout);
        timeout = setTimeout(function() {
            const val = pwd.value;
            
            if (!val) {
                strength.style.display = 'none';
                bar.className = 'password-strength-bar';
                hint.className = 'form-hint';
                hint.innerHTML = '<i class="fa fa-info-circle"></i> Minimum 6 characters required';
                return;
            }
            
            strength.style.display = 'block';
            
            let score = 0;
            if (val.length >= 6) score++;
            if (val.length >= 10) score++;
            if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;
            
            bar.className = 'password-strength-bar';
            hint.className = 'form-hint';
            
            if (score <= 2) {
                bar.classList.add('weak');
                hint.classList.add('weak');
                hint.innerHTML = '<i class="fa fa-exclamation-triangle"></i> Weak password';
            } else if (score <= 4) {
                bar.classList.add('medium');
                hint.classList.add('medium');
                hint.innerHTML = '<i class="fa fa-check-circle"></i> Medium strength';
            } else {
                bar.classList.add('strong');
                hint.classList.add('strong');
                hint.innerHTML = '<i class="fa fa-check-circle"></i> Strong password';
            }
        }, 100);
    });
    
    // LABEL FOCUS EFFECT
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(function(input) {
        input.addEventListener('focus', function() {
            const label = this.closest('.form-group').querySelector('label');
            if (label) label.style.color = '#6366f1';
        });
        
        input.addEventListener('blur', function() {
            const label = this.closest('.form-group').querySelector('label');
            if (label) label.style.color = '#1e293b';
        });
    });
    
    // FORM VALIDATION
    document.getElementById('createUserForm').addEventListener('submit', function(e) {
        const required = this.querySelectorAll('[required]');
        let valid = true;
        
        required.forEach(function(field) {
            if (!field.value.trim()) {
                valid = false;
                field.style.borderColor = '#ef4444';
                field.addEventListener('input', function() {
                    this.style.borderColor = '#e2e8f0';
                }, { once: true });
            }
        });
        
        if (!valid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });
})();
</script>

<?php require_once '../includes/footer.php'; ?>