<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../components/auth.php';
$auth = new Auth();

// DETERMINE BASE PATH DYNAMICALLY
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$script_name = $_SERVER['SCRIPT_NAME'];
$base_path = str_replace(basename($script_name), '', $script_name);
$base_url = $protocol . "://" . $host . rtrim($base_path, '/');

// Get current page info
$current_file = basename($_SERVER['PHP_SELF']);
$current_path = $_SERVER['REQUEST_URI'];
$current_full_path = $_SERVER['PHP_SELF'];

// Check sections
$is_admin_section = strpos($current_full_path, '/admin/') !== false;
$is_project_page = strpos($current_file, 'project-') === 0 || $current_file === 'projects.php';
$is_task_page = strpos($current_file, 'task-') === 0 || $current_file === 'tasks.php';
$is_user_page = strpos($current_file, 'user-') === 0 || $current_file === 'users.php';
$is_pricing_page = strpos($current_file, 'pricing-') === 0;
$is_requirement_page = strpos($current_file, 'requirement-') === 0;

// Helper function for navigation links
function nav_url($path) {
    global $base_url;
    return $base_url . '/' . ltrim($path, '/');
}

// Helper function for active class
function is_active($check_file, $check_prefix = null) {
    global $current_file;
    if ($check_prefix && strpos($current_file, $check_prefix) === 0) {
        return 'active';
    }
    return $current_file === $check_file ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Project Management System</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0 !important;
            padding: 0 !important;
            box-sizing: border-box !important;
        }
        
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif !important;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            background-attachment: fixed !important;
            min-height: 100vh !important;
            padding-top: 0 !important;
            margin-top: 0 !important;
        }
        
        .main-content {
            margin-top: 0 !important;
            padding-top: 80px !important;
            min-height: 100vh !important;
            position: relative !important;
        }
        
        /* NAVBAR */
        .navbar-inverse {
            background: rgba(255, 255, 255, 0.98) !important;
            backdrop-filter: blur(20px) !important;
            border: none !important;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1) !important;
            border-bottom: 1px solid rgba(102, 126, 234, 0.1) !important;
            margin-bottom: 0 !important;
            min-height: 65px !important;
            transition: all 0.3s ease !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 99999 !important;
            width: 100% !important;
        }
        
        .navbar-inverse .container-fluid {
            position: relative !important;
        }
        
        .navbar-inverse .navbar-collapse {
            position: relative !important;
            overflow: visible !important;
        }
        
        .navbar-inverse .navbar-brand {
            color: transparent !important;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            font-weight: 800 !important;
            font-size: 22px !important;
            letter-spacing: -0.5px !important;
            padding: 20px 15px !important;
            transition: all 0.3s ease !important;
        }
        
        .navbar-inverse .navbar-nav {
            position: relative !important;
        }
        
        .navbar-inverse .navbar-nav > li {
            position: relative !important;
        }
        
        .navbar-inverse .navbar-nav > li > a {
            color: #374151 !important;
            font-weight: 600 !important;
            font-size: 14px !important;
            padding: 20px 18px !important;
            transition: all 0.3s ease !important;
            position: relative !important;
        }
        
        .navbar-inverse .navbar-nav > li > a::after {
            content: '' !important;
            position: absolute !important;
            width: 0 !important;
            height: 3px !important;
            bottom: 0 !important;
            left: 50% !important;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%) !important;
            transition: all 0.3s ease !important;
            transform: translateX(-50%) !important;
            border-radius: 2px !important;
        }
        
        .navbar-inverse .navbar-nav > li.active > a::after,
        .navbar-inverse .navbar-nav > li > a:hover::after {
            width: 80% !important;
        }
        
        .navbar-inverse .navbar-nav > li.active > a,
        .navbar-inverse .navbar-nav > li > a:hover,
        .navbar-inverse .navbar-nav > li > a:focus {
            color: #667eea !important;
            background: transparent !important;
        }
        
        /* DROPDOWN */
        .navbar-inverse .navbar-nav .dropdown {
            position: relative !important;
        }
        
        .navbar-inverse .navbar-nav .dropdown-menu {
            background: white !important;
            border: none !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2) !important;
            border-radius: 12px !important;
            padding: 8px 0 !important;
            margin-top: 0 !important;
            position: absolute !important;
            top: 100% !important;
            left: 0 !important;
            z-index: 999999 !important;
            min-width: 200px !important;
            display: none !important;
            opacity: 0 !important;
            transform: translateY(-10px) !important;
            transition: all 0.3s ease !important;
        }
        
        .navbar-inverse .navbar-nav .dropdown.open .dropdown-menu {
            display: block !important;
            opacity: 1 !important;
            transform: translateY(0) !important;
        }
        
        .navbar-inverse .navbar-nav .dropdown-menu > li {
            margin: 0 !important;
        }
        
        .navbar-inverse .navbar-nav .dropdown-menu > li > a {
            padding: 12px 20px !important;
            color: #374151 !important;
            font-weight: 600 !important;
            transition: all 0.3s ease !important;
            display: block !important;
            clear: both !important;
            white-space: nowrap !important;
        }
        
        .navbar-inverse .navbar-nav .dropdown-menu > li.active > a,
        .navbar-inverse .navbar-nav .dropdown-menu > li > a:hover,
        .navbar-inverse .navbar-nav .dropdown-menu > li > a:focus {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.15) 0%, rgba(118, 75, 162, 0.15) 100%) !important;
            color: #667eea !important;
            text-decoration: none !important;
        }
        
        .navbar-inverse .navbar-nav .dropdown-menu > li > a i {
            margin-right: 8px !important;
            width: 18px !important;
            text-align: center !important;
        }
        
        .navbar-inverse .navbar-nav .dropdown-menu .divider {
            height: 1px !important;
            margin: 8px 0 !important;
            overflow: hidden !important;
            background-color: #e2e8f0 !important;
        }
        
        .navbar-inverse .navbar-nav.navbar-right .dropdown-menu {
            left: auto !important;
            right: 0 !important;
        }
        
        .navbar-inverse .navbar-nav.navbar-right .dropdown-toggle {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%) !important;
            border-radius: 25px !important;
            padding: 10px 18px !important;
            margin-top: 10px !important;
            transition: all 0.3s ease !important;
        }
        
        .navbar-inverse .navbar-nav.navbar-right .dropdown-toggle:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: white !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3) !important;
        }
        
        .navbar-inverse .navbar-toggle {
            border: 2px solid #667eea !important;
            border-radius: 8px !important;
            margin-top: 15px !important;
            margin-right: 15px !important;
            transition: all 0.3s ease !important;
        }
        
        .navbar-inverse .navbar-toggle:hover,
        .navbar-inverse .navbar-toggle:focus {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            border-color: transparent !important;
        }
        
        .navbar-inverse .navbar-toggle .icon-bar {
            background-color: #667eea !important;
            transition: all 0.3s ease !important;
        }
        
        .navbar-inverse .navbar-toggle:hover .icon-bar,
        .navbar-inverse .navbar-toggle:focus .icon-bar {
            background-color: white !important;
        }
        
        .navbar-inverse .navbar-nav .dropdown .caret {
            transition: transform 0.3s ease !important;
        }
        
        .navbar-inverse .navbar-nav .dropdown.open .caret {
            transform: rotate(180deg) !important;
        }
        
        /* RESPONSIVE */
        @media (min-width: 1200px) {
            .main-content { padding-top: 80px !important; }
            .navbar-inverse { min-height: 70px !important; }
        }
        
        @media (min-width: 992px) and (max-width: 1199px) {
            .main-content { padding-top: 75px !important; }
            .navbar-inverse .navbar-nav > li > a {
                padding: 18px 15px !important;
                font-size: 13px !important;
            }
        }
        
        @media (min-width: 768px) and (max-width: 991px) {
            .main-content { padding-top: 70px !important; }
            .navbar-inverse .navbar-nav > li > a {
                padding: 16px 12px !important;
                font-size: 13px !important;
            }
            .navbar-inverse .navbar-brand {
                font-size: 20px !important;
                padding: 18px 15px !important;
            }
        }
        
        @media (max-width: 767px) {
            .main-content { padding-top: 65px !important; }
            .navbar-inverse { min-height: 60px !important; }
            .navbar-inverse .navbar-brand {
                font-size: 18px !important;
                padding: 15px !important;
            }
            .navbar-inverse .navbar-nav {
                margin: 0 !important;
                background: white !important;
                border-radius: 12px !important;
                padding: 10px 0 !important;
                margin-top: 10px !important;
                margin-left: 15px !important;
                margin-right: 15px !important;
                box-shadow: 0 5px 20px rgba(0,0,0,0.1) !important;
            }
            .navbar-inverse .navbar-nav > li > a {
                border-radius: 8px !important;
                margin: 2px 10px !important;
                padding: 12px 15px !important;
                font-size: 14px !important;
            }
            .navbar-inverse .navbar-nav > li > a::after {
                display: none !important;
            }
            .navbar-inverse .navbar-nav > li.active > a {
                background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%) !important;
            }
            .navbar-inverse .navbar-nav .dropdown-menu {
                position: static !important;
                float: none !important;
                width: auto !important;
                margin: 5px 10px !important;
                background: #f8fafc !important;
                box-shadow: none !important;
                border: none !important;
                opacity: 1 !important;
                transform: none !important;
            }
            .navbar-inverse .navbar-nav .dropdown.open .dropdown-menu {
                display: block !important;
            }
            .navbar-inverse .navbar-nav.navbar-right .dropdown-toggle {
                margin: 2px 10px !important;
                padding: 12px 15px !important;
            }
        }
        
        @media (max-width: 480px) {
            .main-content { padding-top: 60px !important; }
            .navbar-inverse { min-height: 55px !important; }
            .navbar-inverse .navbar-brand {
                font-size: 16px !important;
                padding: 12px !important;
            }
        }
        
        .container-fluid {
            padding-left: 15px !important;
            padding-right: 15px !important;
            margin: 0 !important;
        }
        
        .navbar-collapse {
            transition: all 0.3s ease !important;
        }
        
        .navbar-inverse .navbar-nav > li > a i {
            margin-right: 6px !important;
            transition: transform 0.3s ease !important;
        }
        
        .navbar-inverse .navbar-nav > li > a:hover i {
            transform: scale(1.1) !important;
        }
        
        .navbar-fixed-top {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <?php if ($auth->isLoggedIn()): ?>
        <nav class="navbar navbar-inverse navbar-fixed-top">
            <div class="container-fluid">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#mainNavbar">
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="<?php echo nav_url('dashboard.php'); ?>">
                        <i class="fa fa-briefcase"></i> PMS
                    </a>
                </div>
                <div class="collapse navbar-collapse" id="mainNavbar">
                    <ul class="nav navbar-nav">
                        <li class="<?php echo is_active('dashboard.php'); ?>">
                            <a href="<?php echo nav_url('dashboard.php'); ?>">
                                <i class="fa fa-dashboard"></i> Dashboard
                            </a>
                        </li>
                        <li class="<?php echo $is_project_page ? 'active' : ''; ?>">
                            <a href="<?php echo nav_url('projects.php'); ?>">
                                <i class="fa fa-folder"></i> Projects
                            </a>
                        </li>
                        <li class="<?php echo $is_task_page ? 'active' : ''; ?>">
                            <a href="<?php echo nav_url('tasks.php'); ?>">
                                <i class="fa fa-tasks"></i> My Tasks
                            </a>
                        </li>
                        <?php if ($auth->isAdmin()): ?>
                        <li class="dropdown <?php echo $is_admin_section ? 'active' : ''; ?>">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-cog"></i> Admin <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li class="<?php echo $is_user_page ? 'active' : ''; ?>">
                                    <a href="<?php echo nav_url('admin/users.php'); ?>">
                                        <i class="fa fa-users"></i> Users
                                    </a>
                                </li>
                                <li class="<?php echo is_active('analytics.php'); ?>">
                                    <a href="<?php echo nav_url('admin/analytics.php'); ?>">
                                        <i class="fa fa-bar-chart"></i> Analytics
                                    </a>
                                </li>
                                <li class="<?php echo is_active('activity.php'); ?>">
                                    <a href="<?php echo nav_url('admin/activity.php'); ?>">
                                        <i class="fa fa-history"></i> Activity Log
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <?php endif; ?>
                    </ul>
                    <ul class="nav navbar-nav navbar-right">
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?> <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li class="<?php echo is_active('profile.php'); ?>">
                                    <a href="<?php echo nav_url('profile.php'); ?>">
                                        <i class="fa fa-user"></i> My Profile
                                    </a>
                                </li>
                                <li class="divider"></li>
                                <li>
                                    <a href="<?php echo nav_url('logout.php'); ?>">
                                        <i class="fa fa-sign-out"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <?php endif; ?>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    
    <script>
        $(document).ready(function() {
            function adjustMainContent() {
                const navbarHeight = $('.navbar-fixed-top').outerHeight();
                $('.main-content').css('padding-top', (navbarHeight + 10) + 'px');
            }
            
            setTimeout(adjustMainContent, 100);
            $(window).on('resize', function() {
                setTimeout(adjustMainContent, 100);
            });
            
            $('.navbar-toggle').on('click', function() {
                setTimeout(adjustMainContent, 350);
            });
            
            $('.navbar-nav li a').on('click', function() {
                if ($(window).width() < 768 && !$(this).parent().hasClass('dropdown')) {
                    $('.navbar-collapse').collapse('hide');
                    setTimeout(adjustMainContent, 350);
                }
            });
            
            if ($(window).width() >= 768) {
                $('.navbar-nav .dropdown').on('mouseenter', function() {
                    $(this).addClass('open');
                }).on('mouseleave', function() {
                    $(this).removeClass('open');
                });
            }
            
            $('a[href^="#"]').on('click', function(e) {
                var target = $(this.getAttribute('href'));
                if(target.length) {
                    e.preventDefault();
                    const navbarHeight = $('.navbar-fixed-top').outerHeight();
                    $('html, body').stop().animate({
                        scrollTop: target.offset().top - (navbarHeight + 20)
                    }, 800);
                }
            });
            
            $(window).on('scroll', function() {
                if ($(this).scrollTop() > 50) {
                    $('.navbar-fixed-top').css({
                        'box-shadow': '0 4px 30px rgba(0, 0, 0, 0.15)',
                        'background': 'rgba(255, 255, 255, 0.99)'
                    });
                } else {
                    $('.navbar-fixed-top').css({
                        'box-shadow': '0 4px 30px rgba(0, 0, 0, 0.1)',
                        'background': 'rgba(255, 255, 255, 0.98)'
                    });
                }
            });
        });
    </script>