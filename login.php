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
        /* MODERN ULTRA-FAST LOGIN DESIGN */
        
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
        
        /* ANIMATED BACKGROUND PARTICLES */
        .bg-particles {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }
        
        .particle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float-particle 15s infinite;
        }
        
        .particle:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 10%;
            left: 15%;
            animation-delay: 0s;
            animation-duration: 12s;
        }
        
        .particle:nth-child(2) {
            width: 60px;
            height: 60px;
            top: 70%;
            left: 80%;
            animation-delay: 2s;
            animation-duration: 18s;
        }
        
        .particle:nth-child(3) {
            width: 100px;
            height: 100px;
            top: 40%;
            left: 5%;
            animation-delay: 4s;
            animation-duration: 20s;
        }
        
        .particle:nth-child(4) {
            width: 70px;
            height: 70px;
            top: 80%;
            left: 20%;
            animation-delay: 1s;
            animation-duration: 16s;
        }
        
        .particle:nth-child(5) {
            width: 90px;
            height: 90px;
            top: 20%;
            left: 85%;
            animation-delay: 3s;
            animation-duration: 14s;
        }
        
        @keyframes float-particle {
            0%, 100% {
                transform: translate(0, 0) scale(1);
                opacity: 0.3;
            }
            25% {
                transform: translate(30px, -30px) scale(1.1);
                opacity: 0.5;
            }
            50% {
                transform: translate(-20px, -50px) scale(0.9);
                opacity: 0.3;
            }
            75% {
                transform: translate(40px, -20px) scale(1.05);
                opacity: 0.6;
            }
        }
        
        .container {
            position: relative;
            z-index: 1;
        }
        
        /* MODERN LOGIN CONTAINER */
        .login-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 50px 45px;
            max-width: 480px;
            width: 100%;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.35);
            animation: slideUpFade 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.4);
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
        
        @keyframes slideUpFade {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        /* HEADER SECTION */
        .login-header {
            text-align: center;
            margin-bottom: 40px;
            animation: fadeIn 0.8s ease 0.2s both;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .logo-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 24px;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            box-shadow: 0 15px 35px rgba(99, 102, 241, 0.4);
            animation: logoPulse 3s ease-in-out infinite;
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .logo::before {
            content: '';
            position: absolute;
            inset: -4px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 22px;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }
        
        .logo:hover {
            transform: scale(1.05) rotate(5deg);
            box-shadow: 0 20px 45px rgba(99, 102, 241, 0.5);
        }
        
        .logo:hover::before {
            opacity: 0.3;
            animation: rotate 3s linear infinite;
        }
        
        @keyframes rotate {
            to { transform: rotate(360deg); }
        }
        
        .logo i {
            font-size: 36px;
            color: white;
            transition: transform 0.3s ease;
        }
        
        .logo:hover i {
            transform: scale(1.1);
        }
        
        @keyframes logoPulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 15px 35px rgba(99, 102, 241, 0.4);
            }
            50% {
                transform: scale(1.03);
                box-shadow: 0 20px 45px rgba(99, 102, 241, 0.5);
            }
        }
        
        .login-header h2 {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
            font-size: 32px;
            margin-bottom: 10px;
            letter-spacing: -0.8px;
        }
        
        .login-header p {
            color: #64748b;
            font-size: 15px;
            font-weight: 500;
        }
        
        /* ALERT MESSAGES */
        .alert {
            border-radius: 14px;
            padding: 16px 20px;
            border: none;
            margin-bottom: 28px;
            animation: shakeSlide 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            overflow: hidden;
        }
        
        .alert::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: currentColor;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .alert i {
            font-size: 18px;
        }
        
        @keyframes shakeSlide {
            0% {
                opacity: 0;
                transform: translateX(-20px);
            }
            10%, 30%, 50%, 70%, 90% {
                transform: translateX(-3px);
            }
            20%, 40%, 60%, 80% {
                transform: translateX(3px);
            }
            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* FORM GROUPS */
        .form-group {
            margin-bottom: 26px;
            position: relative;
            animation: slideIn 0.5s ease both;
        }
        
        .form-group:nth-child(1) { animation-delay: 0.3s; }
        .form-group:nth-child(2) { animation-delay: 0.4s; }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .form-group label {
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 10px;
            font-size: 14px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 18px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 2;
        }
        
        .form-control {
            border: 2px solid var(--border);
            border-radius: 14px;
            padding: 16px 20px 16px 52px;
            font-size: 15px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            height: auto;
            font-weight: 500;
            background: white;
            position: relative;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
            outline: none;
            background: white;
            transform: translateY(-2px);
        }
        
        .form-control:focus ~ .input-icon {
            color: var(--primary);
            transform: translateY(-50%) scale(1.1);
        }
        
        .form-control::placeholder {
            color: #94a3b8;
        }
        
        /* PASSWORD TOGGLE */
        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94a3b8;
            transition: all 0.3s ease;
            z-index: 2;
            font-size: 18px;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .password-toggle:hover {
            color: var(--primary);
            transform: translateY(-50%) scale(1.15);
        }
        
        /* SUBMIT BUTTON */
        .btn-submit-wrapper {
            animation: slideIn 0.5s ease 0.5s both;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 14px;
            padding: 17px 24px;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
            box-shadow: 0 12px 30px rgba(99, 102, 241, 0.4);
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
            background: rgba(255, 255, 255, 0.25);
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }
        
        .btn-primary:hover::before {
            width: 400px;
            height: 400px;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 18px 40px rgba(99, 102, 241, 0.5);
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            color: white;
        }
        
        .btn-primary:active {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        }
        
        .btn-primary:focus {
            color: white;
        }
        
        .btn-text {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        /* LOADING STATE */
        .btn-loading {
            pointer-events: none;
            opacity: 0.8;
        }
        
        .btn-loading .btn-text::after {
            content: '';
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.6s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* CREDENTIALS INFO */
        .credentials-info {
            margin-top: 30px;
            padding: 18px 20px;
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-radius: 14px;
            text-align: center;
            animation: slideIn 0.5s ease 0.6s both;
            border: 1px solid #93c5fd;
        }
        
        .credentials-info p {
            color: #1e40af;
            font-size: 13px;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .credentials-info strong {
            background: rgba(30, 64, 175, 0.1);
            padding: 4px 10px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
        }
        
        /* FOOTER */
        .footer-text {
            margin-top: 28px;
            text-align: center;
            color: #64748b;
            font-size: 13px;
            font-weight: 500;
            animation: fadeIn 0.5s ease 0.7s both;
        }
        
        /* INPUT FLOATING LABEL EFFECT */
        .form-control:focus::placeholder {
            transform: translateY(-20px);
            opacity: 0;
        }
        
        /* SMOOTH SCROLLBAR */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
        
        /* RESPONSIVE */
        @media (max-width: 576px) {
            .login-container {
                padding: 40px 30px;
                margin: 20px;
                border-radius: 20px;
            }
            
            .login-header h2 {
                font-size: 26px;
            }
            
            .logo {
                width: 70px;
                height: 70px;
                border-radius: 18px;
            }
            
            .logo i {
                font-size: 32px;
            }
            
            .form-control {
                padding: 14px 18px 14px 48px;
                font-size: 14px;
            }
            
            .btn-primary {
                padding: 15px 20px;
                font-size: 15px;
            }
            
            .particle {
                display: none;
            }
        }
        
        @media (max-width: 400px) {
            .login-container {
                padding: 35px 25px;
            }
            
            .login-header h2 {
                font-size: 24px;
            }
            
            .credentials-info p {
                flex-direction: column;
                gap: 6px;
            }
        }
    </style>
</head>
<body>
    <!-- ANIMATED BACKGROUND -->
    <div class="bg-particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <div class="logo-wrapper">
                    <div class="logo">
                        <i class="fa fa-briefcase"></i>
                    </div>
                </div>
                <h2>Project Management</h2>
                <p>Sign in to continue to your dashboard</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
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
                
                <div class="btn-submit-wrapper">
                    <button type="submit" class="btn btn-primary btn-block btn-lg" id="submitBtn">
                        <span class="btn-text">
                            <span>Sign In</span>
                        </span>
                    </button>
                </div>
            </form>
            
            <div class="credentials-info">
                <p>
                    <i class="fa fa-info-circle"></i> 
                    <span>Default credentials:</span>
                    <strong>admin</strong> 
                    <span>/</span> 
                    <strong>password</strong>
                </p>
            </div>
            
            <div class="footer-text">
                <p><i class="fa fa-shield"></i> Protected by secure authentication</p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Password Toggle with smooth animation
            $('#togglePassword').on('click', function() {
                const passwordField = $('#password');
                const icon = $(this);
                const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
                
                // Add animation effect
                icon.css('transform', 'translateY(-50%) scale(0.8)');
                setTimeout(() => {
                    passwordField.attr('type', type);
                    icon.toggleClass('fa-eye fa-eye-slash');
                    icon.css('transform', 'translateY(-50%) scale(1)');
                }, 150);
            });
            
            // Form Submit with Loading State
            let formSubmitted = false;
            $('#loginForm').on('submit', function(e) {
                if (formSubmitted) {
                    e.preventDefault();
                    return false;
                }
                
                formSubmitted = true;
                const btn = $('#submitBtn');
                const btnText = btn.find('.btn-text span:first');
                
                btn.addClass('btn-loading');
                btnText.text('Signing in');
                
                // Prevent going back if submission fails
                setTimeout(() => {
                    if (formSubmitted) {
                        formSubmitted = false;
                        btn.removeClass('btn-loading');
                        btnText.text('Sign In');
                    }
                }, 5000);
            });
            
            // Enhanced Input Focus Effects
            $('.form-control').on('focus', function() {
                const wrapper = $(this).closest('.input-wrapper');
                wrapper.find('.input-icon').css({
                    'color': '#6366f1',
                    'transform': 'translateY(-50%) scale(1.1)'
                });
            }).on('blur', function() {
                if (!$(this).val()) {
                    const wrapper = $(this).closest('.input-wrapper');
                    wrapper.find('.input-icon').css({
                        'color': '#94a3b8',
                        'transform': 'translateY(-50%) scale(1)'
                    });
                }
            });
            
            // Auto-hide and fade out error messages
            if ($('.alert-danger').length) {
                setTimeout(function() {
                    $('.alert-danger').fadeOut(600, function() {
                        $(this).remove();
                    });
                }, 5000);
            }
            
            // Add ripple effect on button click
            $('.btn-primary').on('mousedown', function(e) {
                const btn = $(this);
                const ripple = $('<span class="ripple"></span>');
                const x = e.pageX - btn.offset().left;
                const y = e.pageY - btn.offset().top;
                
                ripple.css({
                    top: y + 'px',
                    left: x + 'px',
                    position: 'absolute',
                    width: '0',
                    height: '0',
                    borderRadius: '50%',
                    background: 'rgba(255, 255, 255, 0.5)',
                    transform: 'translate(-50%, -50%)',
                    pointerEvents: 'none'
                });
                
                btn.append(ripple);
                
                setTimeout(() => {
                    ripple.css({
                        width: '300px',
                        height: '300px',
                        opacity: 0,
                        transition: 'all 0.6s ease'
                    });
                }, 10);
                
                setTimeout(() => ripple.remove(), 650);
            });
            
            // Prevent double submission
            $('#loginForm input').on('keypress', function(e) {
                if (e.which === 13 && formSubmitted) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Add floating effect to logo on mouse move
            let logoTimeout;
            $(document).on('mousemove', function(e) {
                clearTimeout(logoTimeout);
                const logo = $('.logo');
                const rect = logo[0].getBoundingClientRect();
                const x = (e.clientX - rect.left - rect.width / 2) / 20;
                const y = (e.clientY - rect.top - rect.height / 2) / 20;
                
                logo.css('transform', `translate(${x}px, ${y}px)`);
                
                logoTimeout = setTimeout(() => {
                    logo.css('transform', 'translate(0, 0)');
                }, 100);
            });
            
            // Enhanced particle animation on scroll
            let particles = $('.particle');
            $(window).on('scroll', function() {
                const scrollTop = $(window).scrollTop();
                particles.each(function(i) {
                    const speed = (i + 1) * 0.1;
                    $(this).css('transform', `translateY(${scrollTop * speed}px)`);
                });
            });
            
            // Preload check - Remove loading if page is cached
            if (performance.navigation.type === 2) {
                formSubmitted = false;
                $('#submitBtn').removeClass('btn-loading');
                $('#submitBtn .btn-text span:first').text('Sign In');
            }
        });
    </script>
</body>
</html>