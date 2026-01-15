<?php
session_start();
require_once 'components/auth.php';

$auth = new Auth();
$error = '';

if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($auth->login($username, $password)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Project Management System</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <style>
        /* MODERN ULTRA-FAST INTERACTIVE LOGIN */
        
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #8b5cf6;
            --success: #10b981;
            --danger: #ef4444;
            --dark: #1e293b;
            --light: #f8fafc;
            --border: #e2e8f0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        /* Animated Particles Background */
        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        
        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            animation: float-particle linear infinite;
        }
        
        @keyframes float-particle {
            0% {
                transform: translateY(100vh) scale(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) scale(1);
                opacity: 0;
            }
        }
        
        /* Gradient Orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.6;
            animation: float-orb ease-in-out infinite;
        }
        
        .orb-1 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.4), transparent);
            top: -250px;
            right: -250px;
            animation-duration: 8s;
        }
        
        .orb-2 {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.4), transparent);
            bottom: -200px;
            left: -200px;
            animation-duration: 10s;
            animation-delay: -2s;
        }
        
        .orb-3 {
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(236, 72, 153, 0.3), transparent);
            top: 50%;
            left: 50%;
            animation-duration: 12s;
            animation-delay: -4s;
        }
        
        @keyframes float-orb {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -30px) scale(1.1); }
            66% { transform: translate(-30px, 30px) scale(0.9); }
        }
        
        .container {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        /* Modern Glass Card */
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 48px 40px;
            max-width: 460px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.2);
            animation: slideUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        /* Header */
        .login-header {
            text-align: center;
            margin-bottom: 36px;
        }
        
        .login-header .logo {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.4);
            animation: logoPulse 3s ease-in-out infinite;
            position: relative;
        }
        
        .login-header .logo::after {
            content: '';
            position: absolute;
            inset: -4px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 18px;
            z-index: -1;
            opacity: 0.3;
            animation: logoGlow 3s ease-in-out infinite;
        }
        
        @keyframes logoPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        @keyframes logoGlow {
            0%, 100% { opacity: 0.3; filter: blur(8px); }
            50% { opacity: 0.6; filter: blur(12px); }
        }
        
        .login-header .logo i {
            font-size: 34px;
            color: white;
        }
        
        .login-header h2 {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
            font-size: 28px;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        
        .login-header p {
            color: #64748b;
            font-size: 15px;
            font-weight: 500;
        }
        
        /* Alert */
        .alert {
            border-radius: 12px;
            padding: 14px 18px;
            border: none;
            margin-bottom: 24px;
            animation: shake 0.5s, fadeIn 0.3s;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }
        
        .alert i {
            font-size: 18px;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-8px); }
            20%, 40%, 60%, 80% { transform: translateX(8px); }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Form Groups */
        .form-group {
            margin-bottom: 22px;
            position: relative;
        }
        
        .form-group label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
            font-size: 14px;
            display: block;
            transition: color 0.3s ease;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            pointer-events: none;
        }
        
        .form-control {
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 14px 16px 14px 46px;
            font-size: 15px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            height: auto;
            font-weight: 500;
            width: 100%;
            background: white;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
            outline: none;
            transform: translateY(-1px);
        }
        
        .form-control:focus + .input-icon {
            color: var(--primary);
            transform: translateY(-50%) scale(1.1);
        }
        
        .form-control.has-value {
            border-color: #cbd5e1;
        }
        
        /* Password Toggle */
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94a3b8;
            transition: all 0.3s ease;
            font-size: 16px;
            z-index: 10;
            padding: 4px;
        }
        
        .password-toggle:hover {
            color: var(--primary);
            transform: translateY(-50%) scale(1.15);
        }
        
        /* Submit Button */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 12px;
            padding: 16px;
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 8px;
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.4);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            color: white;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-primary:hover::before {
            width: 400px;
            height: 400px;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(99, 102, 241, 0.5);
            color: white;
        }
        
        .btn-primary:active {
            transform: translateY(-1px);
        }
        
        .btn-text {
            position: relative;
            z-index: 1;
        }
        
        /* Loading State */
        .btn-loading {
            pointer-events: none;
            opacity: 0.8;
        }
        
        .btn-loading .btn-text {
            opacity: 0;
        }
        
        .btn-loading::after {
            content: '';
            position: absolute;
            width: 18px;
            height: 18px;
            top: 50%;
            left: 50%;
            margin-left: -9px;
            margin-top: -9px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.7s linear infinite;
            z-index: 2;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Credentials Info */
        .credentials-info {
            margin-top: 28px;
            padding: 16px 20px;
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            border-radius: 12px;
            text-align: center;
            border: 1px solid #93c5fd;
            animation: fadeIn 0.5s 0.3s both;
        }
        
        .credentials-info p {
            color: #1e40af;
            font-size: 13px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .credentials-info strong {
            background: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: var(--primary);
        }
        
        /* Footer */
        .footer-text {
            margin-top: 24px;
            text-align: center;
            color: #94a3b8;
            font-size: 13px;
            font-weight: 500;
        }
        
        /* Input Animation */
        .input-animate {
            animation: inputFocus 0.3s ease;
        }
        
        @keyframes inputFocus {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        
        /* Responsive */
        @media (max-width: 576px) {
            .login-container {
                padding: 36px 28px;
                margin: 20px;
                border-radius: 20px;
            }
            
            .login-header {
                margin-bottom: 32px;
            }
            
            .login-header h2 {
                font-size: 24px;
            }
            
            .login-header .logo {
                width: 64px;
                height: 64px;
            }
            
            .login-header .logo i {
                font-size: 30px;
            }
            
            .form-control {
                padding: 13px 16px 13px 44px;
                font-size: 14px;
            }
            
            .btn-primary {
                padding: 15px;
                font-size: 14px;
            }
            
            .orb-1, .orb-2, .orb-3 {
                display: none;
            }
        }
        
        @media (max-width: 400px) {
            .login-container {
                padding: 32px 24px;
            }
            
            .credentials-info {
                padding: 14px 16px;
            }
            
            .credentials-info p {
                flex-direction: column;
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="particles" id="particles"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <div class="logo">
                    <i class="fa fa-briefcase"></i>
                </div>
                <h2>Project Management</h2>
                <p>Sign in to continue to your dashboard</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger" id="errorAlert">
                <i class="fa fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <div class="input-wrapper">
                        <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required autofocus>
                        <i class="fa fa-user input-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                        <i class="fa fa-lock input-icon"></i>
                        <i class="fa fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block btn-lg" id="submitBtn">
                    <span class="btn-text">Sign In</span>
                </button>
            </form>
            
            <div class="credentials-info">
                <p>
                    <i class="fa fa-info-circle"></i> 
                    <span>Default:</span>
                    <strong>admin</strong> 
                    <span>/</span> 
                    <strong>password</strong>
                </p>
            </div>
            
            <div class="footer-text">
                <p>Protected by Advanced security</p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Create floating particles
            function createParticles() {
                const particlesContainer = $('#particles');
                const particleCount = 15;
                
                for (let i = 0; i < particleCount; i++) {
                    const particle = $('<div class="particle"></div>');
                    const size = Math.random() * 4 + 2;
                    const left = Math.random() * 100;
                    const duration = Math.random() * 10 + 10;
                    const delay = Math.random() * 5;
                    
                    particle.css({
                        width: size + 'px',
                        height: size + 'px',
                        left: left + '%',
                        animationDuration: duration + 's',
                        animationDelay: delay + 's'
                    });
                    
                    particlesContainer.append(particle);
                }
            }
            
            createParticles();
            
            // Password Toggle with smooth transition
            $('#togglePassword').on('click', function() {
                const passwordField = $('#password');
                const currentType = passwordField.attr('type');
                const newType = currentType === 'password' ? 'text' : 'password';
                
                passwordField.attr('type', newType);
                $(this).toggleClass('fa-eye fa-eye-slash');
                
                // Add animation
                $(this).css('transform', 'translateY(-50%) scale(1.2)');
                setTimeout(() => {
                    $(this).css('transform', 'translateY(-50%) scale(1)');
                }, 200);
            });
            
            // Form Submit with loading state
            let formSubmitted = false;
            $('#loginForm').on('submit', function(e) {
                if (formSubmitted) {
                    e.preventDefault();
                    return false;
                }
                
                formSubmitted = true;
                const btn = $('#submitBtn');
                btn.addClass('btn-loading');
                btn.find('.btn-text').text('Signing in...');
            });
            
            // Enhanced input focus effects
            $('.form-control').on('focus', function() {
                $(this).addClass('input-animate');
                $(this).siblings('.input-icon').css({
                    color: 'var(--primary)',
                    transform: 'translateY(-50%) scale(1.1)'
                });
                $(this).closest('.form-group').find('label').css('color', 'var(--primary)');
            }).on('blur', function() {
                $(this).removeClass('input-animate');
                
                if (!$(this).val()) {
                    $(this).siblings('.input-icon').css({
                        color: '#94a3b8',
                        transform: 'translateY(-50%) scale(1)'
                    });
                    $(this).closest('.form-group').find('label').css('color', 'var(--dark)');
                }
            });
            
            // Track input value
            $('.form-control').on('input', function() {
                if ($(this).val()) {
                    $(this).addClass('has-value');
                } else {
                    $(this).removeClass('has-value');
                }
            });
            
            // Auto-hide error alert
            if ($('#errorAlert').length) {
                setTimeout(function() {
                    $('#errorAlert').fadeOut('slow');
                }, 5000);
            }
            
            // Smooth entrance for credentials info
            setTimeout(function() {
                $('.credentials-info').css('opacity', '1');
            }, 300);
            
            // Add ripple effect to button
            $('.btn-primary').on('click', function(e) {
                if (!$(this).hasClass('btn-loading')) {
                    const ripple = $('<span class="ripple"></span>');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.css({
                        width: size + 'px',
                        height: size + 'px',
                        left: x + 'px',
                        top: y + 'px'
                    });
                    
                    $(this).append(ripple);
                    
                    setTimeout(() => ripple.remove(), 600);
                }
            });
            
            // Keyboard shortcuts
            $(document).on('keydown', function(e) {
                // Alt + L to focus username
                if (e.altKey && e.key === 'l') {
                    e.preventDefault();
                    $('#username').focus();
                }
            });
            
            // Form validation feedback
            $('.form-control').on('blur', function() {
                if ($(this).val() && $(this)[0].checkValidity()) {
                    $(this).css('border-color', 'var(--success)');
                    setTimeout(() => {
                        if (!$(this).is(':focus')) {
                            $(this).css('border-color', '');
                        }
                    }, 1000);
                }
            });
        });
    </script>
</body>
</html>