<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../components/auth.php';
$auth = new Auth();

// Get current page for active state detection
$current_page = basename($_SERVER['PHP_SELF']);
$current_path = $_SERVER['REQUEST_URI'];

// Check if we're in admin section
$is_admin_section = strpos($current_path, '/admin/') !== false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Project Management System</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            background-attachment: fixed !important;
            min-height: 100vh;
            padding-top: 75px !important;
        }
        
        /* Modern Navbar */
        .navbar-inverse {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border: none;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(102, 126, 234, 0.1);
            margin-bottom: 0;
            min-height: 70px;
            transition: all 0.3s ease;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
        }
        
        .navbar-fixed-top {
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .navbar-inverse .navbar-brand {
            color: transparent;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
            font-size: 24px;
            letter-spacing: -0.5px;
            padding: 22px 15px;
            transition: all 0.3s ease;
        }
        
        .navbar-inverse .navbar-brand:hover {
            transform: scale(1.05);
            opacity: 0.9;
        }
        
        .navbar-inverse .navbar-nav > li > a {
            color: #374151;
            font-weight: 600;
            font-size: 15px;
            padding: 22px 20px;
            transition: all 0.3s ease;
            position: relative;
        }
        
        /* Active state underline */
        .navbar-inverse .navbar-nav > li > a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 3px;
            bottom: 0;
            left: 50%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
            transform: translateX(-50%);
            border-radius: 2px;
        }
        
        .navbar-inverse .navbar-nav > li.active > a::after,
        .navbar-inverse .navbar-nav > li > a:hover::after {
            width: 80%;
        }
        
        .navbar-inverse .navbar-nav > li.active > a,
        .navbar-inverse .navbar-nav > li > a:hover,
        .navbar-inverse .navbar-nav > li > a:focus {
            color: #667eea;
            background: transparent;
        }
        
        .navbar-inverse .navbar-nav > li.active > a {
            font-weight: 700;
        }
        
        /* Dropdown Styles */
        .navbar-inverse .navbar-nav .dropdown-menu {
            background: white;
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            border-radius: 12px;
            padding: 8px 0;
            margin-top: 8px;
            animation: dropdownSlide 0.3s ease;
        }
        
        @keyframes dropdownSlide {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .navbar-inverse .navbar-nav .dropdown-menu > li > a {
            padding: 12px 20px;
            color: #374151;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .navbar-inverse .navbar-nav .dropdown-menu > li > a:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            color: #667eea;
            padding-left: 25px;
        }
        
        .navbar-inverse .navbar-nav .dropdown-menu > li > a i {
            margin-right: 8px;
            width: 18px;
            text-align: center;
        }
        
        .navbar-inverse .navbar-nav .open .dropdown-toggle {
            background: transparent !important;
            color: #667eea !important;
        }
        
        /* User Dropdown */
        .navbar-inverse .navbar-nav.navbar-right .dropdown-toggle {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-radius: 25px;
            padding: 12px 20px !important;
            margin-top: 12px;
            transition: all 0.3s ease;
        }
        
        .navbar-inverse .navbar-nav.navbar-right .dropdown-toggle:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .navbar-inverse .navbar-nav.navbar-right .open .dropdown-toggle {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: white !important;
        }
        
        /* Mobile Toggle Button */
        .navbar-inverse .navbar-toggle {
            border: 2px solid #667eea;
            border-radius: 8px;
            margin-top: 17px;
            transition: all 0.3s ease;
        }
        
        .navbar-inverse .navbar-toggle:hover,
        .navbar-inverse .navbar-toggle:focus {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: transparent;
        }
        
        .navbar-inverse .navbar-toggle .icon-bar {
            background-color: #667eea;
            transition: all 0.3s ease;
        }
        
        .navbar-inverse .navbar-toggle:hover .icon-bar,
        .navbar-inverse .navbar-toggle:focus .icon-bar {
            background-color: white;
        }
        
        /* Badge for notifications (optional) */
        .nav-badge {
            position: absolute;
            top: 12px;
            right: 8px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 10px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        /* Divider */
        .navbar-inverse .navbar-nav .dropdown-menu .divider {
            background: linear-gradient(90deg, transparent 0%, #667eea 50%, transparent 100%);
            height: 1px;
            margin: 8px 0;
        }
        
        /* Responsive */
        @media (max-width: 767px) {
            body {
                padding-top: 70px !important;
            }
            
            .navbar-inverse .navbar-nav {
                margin: 0;
                background: white;
                border-radius: 12px;
                padding: 10px 0;
                margin-top: 10px;
            }
            
            .navbar-inverse .navbar-nav > li > a {
                border-radius: 8px;
                margin: 2px 10px;
                padding: 15px 20px;
            }
            
            .navbar-inverse .navbar-nav > li > a::after {
                display: none;
            }
            
            .navbar-inverse .navbar-nav > li.active > a {
                background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            }
            
            .navbar-inverse .navbar-nav .dropdown-menu {
                box-shadow: none;
                background: #f8fafc;
                margin: 5px 10px;
            }
            
            .navbar-inverse .navbar-nav.navbar-right .dropdown-toggle {
                margin: 2px 10px;
            }
        }
        
        /* Smooth transitions */
        .navbar-collapse {
            transition: all 0.3s ease;
        }
        
        /* Icon styling */
        .navbar-inverse .navbar-nav > li > a i {
            margin-right: 6px;
            transition: transform 0.3s ease;
        }
        
        .navbar-inverse .navbar-nav > li > a:hover i {
            transform: scale(1.1);
        }
        
        /* Caret styling */
        .navbar-inverse .caret {
            border-top-color: #374151;
            transition: all 0.3s ease;
        }
        
        .navbar-inverse .dropdown-toggle:hover .caret,
        .navbar-inverse .open .dropdown-toggle .caret {
            border-top-color: #667eea;
        }
        
        .navbar-inverse .navbar-nav.navbar-right .dropdown-toggle:hover .caret,
        .navbar-inverse .navbar-nav.navbar-right .open .dropdown-toggle .caret {
            border-top-color: white;
        }
    </style>
</head>
<body>
    <?php if ($auth->isLoggedIn()): ?>
    <nav class="navbar navbar-inverse navbar-fixed-top">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#mainNavbar">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="/dashboard.php">
                    <i class="fa fa-briefcase"></i> PM System
                </a>
            </div>
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="nav navbar-nav">
                    <li class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                        <a href="/dashboard.php">
                            <i class="fa fa-dashboard"></i> Dashboard
                        </a>
                    </li>
                    <li class="<?php echo $current_page == 'projects.php' || strpos($current_page, 'project-') === 0 ? 'active' : ''; ?>">
                        <a href="/projects.php">
                            <i class="fa fa-folder"></i> Projects
                        </a>
                    </li>
                    <li class="<?php echo $current_page == 'tasks.php' || strpos($current_page, 'task-') === 0 ? 'active' : ''; ?>">
                        <a href="/tasks.php">
                            <i class="fa fa-tasks"></i> My Tasks
                        </a>
                    </li>
                    <?php if ($auth->isAdmin()): ?>
                    <li class="dropdown <?php echo $is_admin_section ? 'active' : ''; ?>">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <i class="fa fa-cog"></i> Admin <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li class="<?php echo $current_page == 'users.php' || strpos($current_page, 'user-') === 0 ? 'active' : ''; ?>">
                                <a href="/admin/users.php">
                                    <i class="fa fa-users"></i> Users
                                </a>
                            </li>
                            <li class="<?php echo $current_page == 'analytics.php' ? 'active' : ''; ?>">
                                <a href="/admin/analytics.php">
                                    <i class="fa fa-bar-chart"></i> Analytics
                                </a>
                            </li>
                            <li class="<?php echo $current_page == 'activity.php' ? 'active' : ''; ?>">
                                <a href="/admin/activity.php">
                                    <i class="fa fa-history"></i> Activity Log
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <i class="fa fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?> <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                                <a href="/profile.php">
                                    <i class="fa fa-user"></i> My Profile
                                </a>
                            </li>
                            <li class="divider"></li>
                            <li>
                                <a href="/logout.php">
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
            // Close mobile menu when clicking a link
            $('.navbar-nav li a').on('click', function() {
                if ($(window).width() < 768) {
                    $('.navbar-collapse').collapse('hide');
                }
            });
            
            // Add smooth scroll behavior
            $('a[href^="#"]').on('click', function(e) {
                var target = $(this.getAttribute('href'));
                if(target.length) {
                    e.preventDefault();
                    $('html, body').stop().animate({
                        scrollTop: target.offset().top - 80
                    }, 800);
                }
            });
            
            // Navbar scroll effect (optional - adds shadow on scroll)
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
            
            // Dropdown hover effect (desktop only)
            if ($(window).width() > 767) {
                $('.navbar-nav .dropdown').hover(
                    function() {
                        $(this).find('.dropdown-menu').first().stop(true, true).fadeIn(200);
                        $(this).addClass('open');
                    },
                    function() {
                        $(this).find('.dropdown-menu').first().stop(true, true).fadeOut(200);
                        $(this).removeClass('open');
                    }
                );
            }
            
            // Prevent dropdown from closing when clicking inside
            $('.dropdown-menu').on('click', function(e) {
                e.stopPropagation();
            });
        });
    </script>